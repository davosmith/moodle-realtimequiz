<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Internal functions
 *
 * @package   mod_realtimequiz
 * @copyright 2014 Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('REALTIMEQUIZ_STATUS_NOTRUNNING', 0);
define('REALTIMEQUIZ_STATUS_READYTOSTART', 10);
define('REALTIMEQUIZ_STATUS_PREVIEWQUESTION', 15);
define('REALTIMEQUIZ_STATUS_SHOWQUESTION', 20);
define('REALTIMEQUIZ_STATUS_SHOWRESULTS', 30);
define('REALTIMEQUIZ_STATUS_FINALRESULTS', 40);

function realtimequiz_start_response() {
    header('content-type: text/xml');
    echo '<?xml version="1.0" ?><realtimequiz>';
}

function realtimequiz_end_response() {
    echo '</realtimequiz>';
}

function realtimequiz_send_error($msg) {
    echo "<status>error</status><message><![CDATA[{$msg}]]></message>";
}

function realtimequiz_send_question($quizid, $context, $preview = false) {
    global $DB;

    if (!$quiz = $DB->get_record('realtimequiz', array('id' => $quizid))) {
        realtimequiz_send_error(get_string('badquizid', 'realtimequiz').$quizid);
    } else {
        $questionid = $quiz->currentquestion;
        if (!$question = $DB->get_record('realtimequiz_question', array('id' => $questionid))) {
            realtimequiz_send_error(get_string('badcurrentquestion', 'realtimequiz').$questionid);
        } else {
            $answers = $DB->get_records('realtimequiz_answer', array('questionid' => $questionid), 'id');
            $questioncount = $DB->count_records('realtimequiz_question', array('quizid' => $quizid));
            echo '<status>showquestion</status>';
            echo "<question><questionnumber>{$question->questionnum}</questionnumber>";
            echo "<questioncount>{$questioncount}</questioncount>";
            $questiontext = format_text($question->questiontext, $question->questiontextformat);
            $questiontext = file_rewrite_pluginfile_urls($questiontext, 'pluginfile.php', $context->id, 'mod_realtimequiz',
                                                         'question', $questionid);
            echo "<questiontext><![CDATA[{$questiontext}]]></questiontext>";
            if ($preview) {
                $previewtime = $quiz->nextendtime - time();
                if ($previewtime > 0) {
                    echo "<delay>{$previewtime}</delay>";
                }
                $questiontime = $question->questiontime;
                if ($questiontime == 0) {
                    $questiontime = $quiz->questiontime;
                }
                echo "<questiontime>{$questiontime}</questiontime>";
            } else {
                $questiontime = $quiz->nextendtime - time();
                if ($questiontime < 0) {
                    $questiontime = 0;
                }
                echo "<questiontime>{$questiontime}</questiontime>";
            }
            echo '<answers>';
            foreach ($answers as $answer) {
                $answertext = $answer->answertext;
                echo "<answer id='{$answer->id}'><![CDATA[{$answertext}]]></answer>";
            }
            echo '</answers>';
            echo '</question>';
        }
    }
}

function realtimequiz_send_results($quizid) {
    global $DB;

    if (!$quiz = $DB->get_record('realtimequiz', array('id' => $quizid))) {
        realtimequiz_send_error(get_string('badquizid', 'realtimequiz').$quizid);
    } else {
        $questionid = $quiz->currentquestion;
        if (!$question = $DB->get_record('realtimequiz_question', array('id' => $questionid))) {
            realtimequiz_send_error(get_string('badcurrentquestion', 'realtimequiz').$questionid);
        } else {
            // Do not worry about question number not matching request
            // client should sort out correct state, if they do not match
            // just get on with sending current results.
            $totalanswers = 0;
            $totalcorrect = 0;
            $answers = $DB->get_records('realtimequiz_answer', array('questionid' => $questionid), 'id');
            echo '<status>showresults</status>';
            echo '<questionnum>'.$question->questionnum.'</questionnum>';
            echo '<results>';
            $numberofcorrectanswers = 0; // To detect questions that have no 'correct' answers.
            foreach ($answers as $answer) {
                $result = $DB->count_records('realtimequiz_submitted', array(
                    'questionid' => $questionid, 'answerid' => $answer->id, 'sessionid' => $quiz->currentsessionid
                ));
                $totalanswers += $result;
                $correct = 'false';
                if ($answer->correct == 1) {
                    $correct = 'true';
                    $totalcorrect += $result;
                    $numberofcorrectanswers++;
                }
                echo "<result id='{$answer->id}' correct='{$correct}'>{$result}</result>";
            }
            if ($numberofcorrectanswers == 0) {
                $newresult = 100;
            } else if ($totalanswers > 0) {
                $newresult = intval((100 * $totalcorrect) / $totalanswers);
            } else {
                $newresult = 0;
            }
            if ($newresult != $quiz->questionresult) {
                $quiz->questionresult = $newresult;
                $upd = new stdClass;
                $upd->id = $quiz->id;
                $upd->questionresult = $quiz->questionresult;
                $DB->update_record('realtimequiz', $upd);
            }
            $classresult = intval(($quiz->classresult + $quiz->questionresult) / $question->questionnum);
            echo '</results>';
            if ($numberofcorrectanswers == 0) {
                echo '<nocorrect/>';
            }
            echo '<statistics>';
            echo '<questionresult>'.$quiz->questionresult.'</questionresult>';
            echo '<classresult>'.$classresult.'</classresult>';
            echo '</statistics>';
        }
    }
}

function realtimequiz_record_answer($quizid, $questionnum, $userid, $answerid, $context) {
    global $DB;

    $quiz = $DB->get_record('realtimequiz', array('id' => $quizid));
    $question = $DB->get_record('realtimequiz_question', array('id' => $quiz->currentquestion));
    $answer = $DB->get_record('realtimequiz_answer', array('id' => $answerid));

    if (($answer->questionid == $quiz->currentquestion)
        && ($question->questionnum == $questionnum)
    ) {
        $conditions = array(
            'questionid' => $question->id, 'sessionid' => $quiz->currentsessionid, 'userid' => $userid
        );
        if (!$DB->record_exists('realtimequiz_submitted', $conditions)) {
            // If we already have an answer from them, do not send error, as this is likely to be the
            // result of lost network packets & resends, just ignore silently.
            $submitted = new stdClass;
            $submitted->questionid = $question->id;
            $submitted->sessionid = $quiz->currentsessionid;
            $submitted->userid = $userid;     // FIXME: make sure the userid is on the course.
            $submitted->answerid = $answerid;
            $DB->insert_record('realtimequiz_submitted', $submitted);
        }
        echo '<status>answerreceived</status>';

    } else {

        // Answer is not for the current question - so send the current question.
        realtimequiz_send_question($quizid, $context);
    }
}

function realtimequiz_number_students($quizid) {
    global $CFG, $DB, $USER;
    if ($realtimequiz = $DB->get_record("realtimequiz", array('id' => $quizid))) {
        if ($course = $DB->get_record("course", array('id' => $realtimequiz->course))) {
            if ($cm = get_coursemodule_from_instance("realtimequiz", $realtimequiz->id, $course->id)) {
                if ($CFG->version < 2011120100) {
                    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
                } else {
                    $context = context_module::instance($cm->id);
                }
                // Is it a student and not a teacher?
                if (!has_capability('mod/realtimequiz:control', $context, $USER->id)) {
                    $cond = array(
                        'userid' => $USER->id, 'questionid' => 0, 'answerid' => 0,
                        'sessionid' => $realtimequiz->currentsessionid,
                    );
                    if (!$DB->record_exists("realtimequiz_submitted", $cond)) {
                        $data = new stdClass();
                        $data->questionid = 0;
                        $data->userid = $USER->id;
                        $data->answerid = 0;
                        $data->sessionid = $realtimequiz->currentsessionid;
                        $DB->insert_record('realtimequiz_submitted', $data);
                    }
                }
            }
        }
        echo "<numberstudents>";
        echo ($DB->count_records('realtimequiz_submitted', array(
            'questionid' => 0, 'answerid' => 0, 'sessionid' => $realtimequiz->currentsessionid
        )));
        echo "</numberstudents>";
    }
}

function realtimequiz_send_running() {
    echo '<status>quizrunning</status>';
}

function realtimequiz_send_not_running() {
    echo '<status>quiznotrunning</status>';
}

function realtimequiz_send_await_question() {
    $waittime = get_config('realtimequiz', 'awaittime');
    echo '<status>waitforquestion</status>';
    echo "<waittime>{$waittime}</waittime>";
}

function realtimequiz_send_await_results($timeleft) {
    $waittime = (int)get_config('realtimequiz', 'awaittime');
    // We need to randomise the waittime a little, otherwise all clients will
    // start sending 'waitforquestion' simulatiniously after the first question -
    // it can cause a problem is there is a large number of clients.
    // If waittime is 1 sec, there is no point to randomise it.
    $waittime = mt_rand(1, $waittime) + $timeleft;
    echo '<status>waitforresults</status>';
    echo "<waittime>{$waittime}</waittime>";
}

function realtimequiz_send_final_results($quizid) {
    global $DB;

    $quiz = $DB->get_record('realtimequiz', array('id' => $quizid));
    $questionnum = $DB->get_field('realtimequiz_question', 'questionnum', array('id' => $quiz->currentquestion));
    echo '<status>finalresults</status>';
    echo '<classresult>'.intval($quiz->classresult / $questionnum).'</classresult>';
}

// Check if the current status should change due to a timeout.
function realtimequiz_update_status($quizid, $status) {
    global $DB;

    if ($status == REALTIMEQUIZ_STATUS_PREVIEWQUESTION) {
        $quiz = $DB->get_record('realtimequiz', array('id' => $quizid));
        if ($quiz->nextendtime < time()) {
            $questiontime = $DB->get_field('realtimequiz_question', 'questiontime', array('id' => $quiz->currentquestion));
            if ($questiontime == 0) {
                $questiontime = $quiz->questiontime;
            }
            $timeleft = $quiz->nextendtime - time() + $questiontime;
            if ($timeleft > 0) {
                $quiz->status = REALTIMEQUIZ_STATUS_SHOWQUESTION;
                $quiz->nextendtime = time() + $timeleft;
            } else {
                $quiz->status = REALTIMEQUIZ_STATUS_SHOWRESULTS;
            }
            $upd = new stdClass;
            $upd->id = $quiz->id;
            $upd->status = $quiz->status;
            $upd->nextendtime = $quiz->nextendtime;
            $DB->update_record('realtimequiz', $upd);

            $status = $quiz->status;
        }
    } else if ($status == REALTIMEQUIZ_STATUS_SHOWQUESTION) {
        $nextendtime = $DB->get_field('realtimequiz', 'nextendtime', array('id' => $quizid));
        if ($nextendtime < time()) {
            $status = REALTIMEQUIZ_STATUS_SHOWRESULTS;
            $DB->set_field('realtimequiz', 'status', $status, array('id' => $quizid));
        }
    } else if (($status != REALTIMEQUIZ_STATUS_NOTRUNNING) && ($status != REALTIMEQUIZ_STATUS_READYTOSTART)
        && ($status != REALTIMEQUIZ_STATUS_SHOWRESULTS) && ($status != REALTIMEQUIZ_STATUS_FINALRESULTS)) {
        // Bad status = probably should set it back to 0.
        $status = REALTIMEQUIZ_STATUS_NOTRUNNING;
        $DB->set_field('realtimequiz', 'status', REALTIMEQUIZ_STATUS_NOTRUNNING, array('id' => $quizid));
    }

    return $status;
}

function realtimequiz_is_running($status) {
    return ($status > REALTIMEQUIZ_STATUS_NOTRUNNING && $status < REALTIMEQUIZ_STATUS_FINALRESULTS);
}

// Check the question requested matches the current question.
function realtimequiz_current_question($quizid, $questionnumber) {
    global $DB;

    $questionid = $DB->get_field('realtimequiz', 'currentquestion', array('id' => $quizid));
    if (!$questionid) {
        return false;
    }
    if ($questionnumber != $DB->get_field('realtimequiz_question', 'questionnum', array('id' => $questionid))) {
        return false;
    }

    return true;
}

function realtimequiz_goto_question($context, $quizid, $questionnum) {
    global $DB;

    if (has_capability('mod/realtimequiz:control', $context)) {
        $quiz = $DB->get_record('realtimequiz', array('id' => $quizid));
        // Update the question statistics.
        $quiz->classresult += $quiz->questionresult;
        $quiz->questionresult = 0;
        $questionid = $DB->get_field('realtimequiz_question', 'id', array('quizid' => $quizid, 'questionnum' => $questionnum));
        if ($questionid) {
            $quiz->currentquestion = $questionid;
            $quiz->status = REALTIMEQUIZ_STATUS_PREVIEWQUESTION;
            $quiz->nextendtime = time() + 2;    // Give everyone a chance to get the question before starting
            $DB->update_record('realtimequiz', $quiz); // FIXME - not update all fields?
            realtimequiz_send_question($quizid, $context, true);
        } else { // Assume we have run out of questions.
            $quiz->status = REALTIMEQUIZ_STATUS_FINALRESULTS;
            $DB->update_record('realtimequiz', $quiz); // FIXME - not update all fields?
            realtimequiz_send_final_results($quizid);
        }
    } else {
        realtimequiz_send_error(get_string('notauthorised', 'realtimequiz'));
    }
}

<?php
/**
 * This dynamically sends quiz data to clients
 *
 * @author: Davosmith
 * @package realtimequiz
 **/

define('AJAX_SCRIPT', true);

require_once('../../config.php');
global $CFG;
require_once($CFG->dirroot.'/mod/realtimequiz/lib.php');
require_once($CFG->libdir.'/filelib.php');

require_login();
require_sesskey();
$requesttype = required_param('requesttype', PARAM_ALPHA);
$quizid = required_param('quizid', PARAM_INT);

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

function realtimequiz_send_question($quizid, $context, $preview=false) {
    global $DB;

    if (!$quiz = $DB->get_record('realtimequiz', array('id' => $quizid))) {
        realtimequiz_send_error(get_string('badquizid','realtimequiz').$quizid);
    } else {
        $questionid = $quiz->currentquestion;
        if (!$question = $DB->get_record('realtimequiz_question', array('id' => $questionid))) {
            realtimequiz_send_error(get_string('badcurrentquestion','realtimequiz').$questionid);
        } else {
            $answers = $DB->get_records('realtimequiz_answer', array('questionid' => $questionid),'id');
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

function realtimequiz_send_results($quizid, $questionnum) {
    global $DB;

    if (!$quiz = $DB->get_record('realtimequiz', array('id' => $quizid))) {
        realtimequiz_send_error(get_string('badquizid','realtimequiz').$quizid);
    } else {
        $questionid = $quiz->currentquestion;
        if (!$question = $DB->get_record('realtimequiz_question', array('id' => $questionid))) {
            realtimequiz_send_error(get_string('badcurrentquestion','realtimequiz').$questionid);
        } else {
            // Do not worry about question number not matching request
            // client should sort out correct state, if they do not match
            // just get on with sending current results
            $total_answers = 0;
            $total_correct = 0;
            $answers = $DB->get_records('realtimequiz_answer', array('questionid' => $questionid),'id');
            echo '<status>showresults</status>';
            echo '<questionnum>'.$question->questionnum.'</questionnum>';
            echo '<results>';
            $number_of_correct_answers = 0; // To detect questions that have no 'correct' answers
            foreach ($answers as $answer) {
                $result = $DB->count_records('realtimequiz_submitted', array('questionid' => $questionid, 'answerid' => $answer->id, 'sessionid' => $quiz->currentsessionid) );
                $total_answers += $result;
                $correct = 'false';
                if ($answer->correct == 1) {
                    $correct = 'true';
                    $total_correct += $result;
                    $number_of_correct_answers++;
                }
                echo "<result id='{$answer->id}' correct='{$correct}'>{$result}</result>";
            }
            if ($number_of_correct_answers == 0) {
                $newresult = 100;
            } else if ($total_answers > 0) {
                $newresult = intval((100 * $total_correct)/$total_answers);
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
            $classresult = intval(($quiz->classresult + $quiz->questionresult) / $questionnum);
            echo '</results>';
            if ($number_of_correct_answers == 0) {
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
        && ($question->questionnum == $questionnum)) {
        if (0 < $DB->count_records('realtimequiz_submitted',array('questionid' => $question->id, 'sessionid' => $quiz->currentsessionid, 'userid' => $userid))) {
            // Already got an answer from them - send an error so we know something is amiss
            //realtimequiz_send_error(get_string('alreadyanswered','realtimequiz'));
            // Do not send error, as this is likely to be the result of lost network packets & resends, just ignore silently
        } else {
            $submitted = new stdClass;
            $submitted->questionid = $question->id;
            $submitted->sessionid = $quiz->currentsessionid;
            $submitted->userid = $userid;     //FIXME: make sure the userid is on the course
            $submitted->answerid = $answerid;
            $DB->insert_record('realtimequiz_submitted', $submitted);

        }
        echo '<status>answerreceived</status>';

    } else {

        // Answer is not for the current question - so send the current question
        realtimequiz_send_question($quizid, $context);
    }
}

function realtimequiz_send_running() {
    echo '<status>quizrunning</status>';
}

function realtimequiz_send_not_running() {
    echo '<status>quiznotrunning</status>';
}

function realtimequiz_send_await_question($waittime=2.0) {
    echo '<status>waitforquestion</status>';
    echo "<waittime>{$waittime}</waittime>";
}

function realtimequiz_send_await_results($waittime=2.0) {
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

// Check if the current status should change due to a timeout 
function realtimequiz_update_status($quizid, $status) {
    global $DB;

    if ($status == REALTIMEQUIZ_STATUS_PREVIEWQUESTION) {
        $quiz = $DB->get_record('realtimequiz', array('id' => $quizid));
        if ($quiz->nextendtime < time()) {
            $questiontime = $DB->get_field('realtimequiz_question','questiontime',array('id' => $quiz->currentquestion));
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
    } else if (($status != REALTIMEQUIZ_STATUS_NOTRUNNING) && ($status != REALTIMEQUIZ_STATUS_READYTOSTART) && ($status != REALTIMEQUIZ_STATUS_SHOWRESULTS) && ($status != REALTIMEQUIZ_STATUS_FINALRESULTS)) {
        // Bad status = probably should set it back to 0
        $status = REALTIMEQUIZ_STATUS_NOTRUNNING;
        $DB->set_field('realtimequiz','status', REALTIMEQUIZ_STATUS_NOTRUNNING, array('id' => $quizid));
    }

    return $status;
}

// Check the question requested matches the current question
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
        // Update the question statistics:
        $quiz->classresult += $quiz->questionresult;
        $quiz->questionresult = 0;
        $questionid = $DB->get_field('realtimequiz_question', 'id', array('quizid' => $quizid, 'questionnum' => $questionnum));
        if ($questionid) {
            $quiz->currentquestion = $questionid;
            $quiz->status = REALTIMEQUIZ_STATUS_PREVIEWQUESTION;
            $quiz->nextendtime = time() + 2;    // Give everyone a chance to get the question before starting
            $DB->update_record('realtimequiz', $quiz); // FIXME - not update all fields?
            realtimequiz_send_question($quizid, $context, true);
        } else { // Assume we have run out of questions
            $quiz->status = REALTIMEQUIZ_STATUS_FINALRESULTS;
            $DB->update_record('realtimequiz', $quiz); // FIXME - not update all fields?
            realtimequiz_send_final_results($quizid);
        }
    } else {
        realtimequiz_send_error(get_string('notauthorised','realtimequiz'));
    }
}


/***********************************************************
 * End of functions - start of main code
 ***********************************************************/

realtimequiz_start_response();

if (! $realtimequiz = $DB->get_record("realtimequiz", array('id' => $quizid))) {
    realtimequiz_send_error("Quiz ID incorrect");
    realtimequiz_end_response();
    die();
}
if (! $course = $DB->get_record("course", array('id' => $realtimequiz->course))) {
    realtimequiz_send_error("Course is misconfigured");
    realtimequiz_end_response();
    die();
}
if (! $cm = get_coursemodule_from_instance("realtimequiz", $realtimequiz->id, $course->id)) {
    realtimequiz_send_error("Course Module ID was incorrect");
    realtimequiz_end_response();
    die();
}
if ($CFG->version < 2011120100) {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
} else {
    $context = context_module::instance($cm->id);
}

if (!has_capability('mod/realtimequiz:attempt', $context)) {
    realtimequiz_send_error(get_string('notallowedattempt','realtimequiz'));
    realtimequiz_end_response();
    die();
}

$status = $realtimequiz->status;
if ($status === false) {
    realtimequiz_send_error(get_string('badquizid','realtimequiz').$quizid);
} else {
    $status = realtimequiz_update_status($quizid, $status); // Check if the current status should change due to a timeout

    if ($requesttype == 'quizrunning') {
        if (($status == REALTIMEQUIZ_STATUS_NOTRUNNING) || ($status == REALTIMEQUIZ_STATUS_FINALRESULTS)) { // Not running / finished
            realtimequiz_send_not_running();
        } else {
            realtimequiz_send_running();
        }
    } else if ($requesttype == 'startquiz') {
        $userid = required_param('userid', PARAM_INT);
        if (has_capability('mod/realtimequiz:control', $context)) {
            $session = new Object();
            $session->timestamp = time();
            $session->name = optional_param('sessionname', '', PARAM_CLEAN);
            $session->quizid = $quizid;
            $session->id = $DB->insert_record('realtimequiz_session', $session);

            $quiz = $DB->get_record('realtimequiz', array('id' => $quizid));
            $quiz->currentsessionid = $session->id;
            $quiz->status = REALTIMEQUIZ_STATUS_READYTOSTART;
            $quiz->currentquestion = 0;
            $quiz->classresult = 0;
            $quiz->questionresult = 0;
            $DB->update_record('realtimequiz', $quiz);

            realtimequiz_send_running();
        } else {
            realtimequiz_send_error(get_string('notauthorised','realtimequiz'));
        }

    } else {

        switch ($status) {

        case REALTIMEQUIZ_STATUS_NOTRUNNING:   // Quiz is not running
            realtimequiz_send_not_running(); // (don't care what they asked for)
            break;

        case REALTIMEQUIZ_STATUS_READYTOSTART: // Quiz is ready to start
            if ($requesttype == 'nextquestion') {
                $userid = required_param('userid', PARAM_INT);
                realtimequiz_goto_question($context, $quizid, 1);
            } else {
                realtimequiz_send_await_question(); //  (don't care what they asked for)
            }
            break;

        case REALTIMEQUIZ_STATUS_PREVIEWQUESTION: // Previewing question (send it out, but ask them to wait before showing)
            realtimequiz_send_question($quizid, $context, true); // (don't care what they asked for)
            break;

        case REALTIMEQUIZ_STATUS_SHOWQUESTION: // Question being displayed
            if (($requesttype == 'getquestion') || ($requesttype == 'nextquestion')) { // Student asked for a question - so send it
                realtimequiz_send_question($quizid, $context);

            } else if ($requesttype == 'postanswer') {
                $questionnum = required_param('question', PARAM_INT);
                $userid = required_param('userid', PARAM_INT);
                $answerid = required_param('answer', PARAM_INT);
                realtimequiz_record_answer($quizid, $questionnum, $userid, $answerid, $context);

            } else if ($requesttype == 'getresults') {
                $questionnum = required_param('question', PARAM_INT);
                if (realtimequiz_current_question($quizid, $questionnum)) {
                    $timeleft = $DB->get_field('realtimequiz','nextendtime',array('id' => $quizid)) - time();
                    if ($timeleft < 0) {
                        $timeleft = 0;
                    }
                    realtimequiz_send_await_results($timeleft + 0.1); // results not yet ready
                } else {
                    realtimequiz_send_question($quizid, $context); // asked for results for wrong question
                }

            } else {
                realtimequiz_send_error(get_string('unknownrequest','realtimequiz').$requesttype.'\'');
            }
            break;

        case REALTIMEQUIZ_STATUS_SHOWRESULTS: // Results being displayed
            if ($requesttype == 'getquestion') { // Asking for the next question
                realtimequiz_send_await_question();

            } else if ($requesttype == 'postanswer' || $requesttype == 'getresults') {
                $questionnum = required_param('question', PARAM_INT);
                realtimequiz_send_results($quizid, $questionnum);

            } else if ($requesttype == 'nextquestion') {
                $clientquestionnum = required_param('currentquestion', PARAM_INT);
                $questionid = $DB->get_field('realtimequiz', 'currentquestion', array('id' => $quizid));
                $questionnum = $DB->get_field('realtimequiz_question', 'questionnum', array('id' => $questionid));
                if ($clientquestionnum != $questionnum) {
                    realtimequiz_send_results($quizid, $questionnum);
                } else {
                    $questionnum++;
                    realtimequiz_goto_question($context, $quizid, $questionnum);
                }

            } else {
                realtimequiz_send_error(get_string('unknownrequest','realtimequiz').$requesttype.'\'');
            }
            break;

        case REALTIMEQUIZ_STATUS_FINALRESULTS: // Showing the final totals, etc
            realtimequiz_send_final_results($quizid);
            break;

        default:
            realtimequiz_send_error(get_string('incorrectstatus','realtimequiz').$status.'\'');
            break;
        }
    }
}

realtimequiz_end_response();

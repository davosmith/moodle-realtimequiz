<?php
/**
 * This dynamically sends quiz data to clients
 *
 * @author: Davosmith
 * @package realtimequiz
 **/

require_once('../../config.php');
require_once('lib.php');

require_login();
if (!confirm_sesskey()) {
    error(get_string('badsesskey','realtimequiz'));
} 
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

function realtimequiz_send_question($quizid, $preview=false) {
    if (!record_exists('realtimequiz', 'id', $quizid)) {
        realtimequiz_send_error(get_string('badquizid','realtimequiz').$quizid);
    } else {
        $quiz = get_record('realtimequiz', 'id', $quizid);
        $questionid = $quiz->currentquestion;
        if (!record_exists('realtimequiz_question', 'id', $questionid)) {
            realtimequiz_send_error(get_string('badcurrentquestion','realtimequiz').$questionid);
        } else {
			$question = get_record('realtimequiz_question', 'id', $questionid);
            $answers = get_records('realtimequiz_answer', 'questionid', $questionid,'id');
			$questioncount = count_records('realtimequiz_question', 'quizid', $quizid);
            echo '<status>showquestion</status>';
            echo "<question><questionnumber>{$question->questionnum}</questionnumber>";
			echo "<questioncount>{$questioncount}</questioncount>";
            echo "<questiontext><![CDATA[{$question->questiontext}]]></questiontext>";
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
                echo "<answer id='{$answer->id}'><![CDATA[{$answer->answertext}]]></answer>";
            }
            echo '</answers>';
            echo '</question>';
        }
    }
}

function realtimequiz_send_results($quizid, $questionnum) {
    if (!record_exists('realtimequiz', 'id', $quizid)) {
        realtimequiz_send_error(get_string('badquizid','realtimequiz').$quizid);
    } else {
        $quiz = get_record('realtimequiz', 'id', $quizid);
        $questionid = $quiz->currentquestion;
        if (!record_exists('realtimequiz_question', 'id', $questionid)) {
            realtimequiz_send_error(get_string('badcurrentquestion','realtimequiz').$questionid);
        } else {
            $question = get_record('realtimequiz_question', 'id', $questionid);
            if ($question->questionnum != $questionnum) {  // Request for results for the question we aren't displaying
                realtimequiz_send_wait_question();  // Shouldn't happen, ask them to wait for the next question
    
            } else { // FIXME: cache the results here
                $total_answers = 0;
                $total_correct = 0;
                $answers = get_records('realtimequiz_answer', 'questionid', $questionid,'id');
                echo '<status>showresults</status>';
                echo '<results>';
                foreach ($answers as $answer) {
                    $result = count_records('realtimequiz_submitted', 'questionid', $questionid, 'answerid', $answer->id, 'sessionid', $quiz->currentsessionid );
                    $total_answers += $result;
                    $correct = 'false';
                    if ($answer->correct == 1) {
                        $correct = 'true';
                        $total_correct += $result;
                    }
                    echo "<result id='{$answer->id}' correct='{$correct}'>{$result}</result>";
                }
                if ($total_answers > 0) {
                    $quiz->questionresult = intval((100 * $total_correct)/$total_answers);
                } else {
                    $quiz->questionresult = 0;
                }
                update_record('realtimequiz', $quiz);
                $classresult = intval(($quiz->classresult + $quiz->questionresult) / $questionnum);
                echo '</results>';
                echo '<statistics>';
                echo '<questionresult>'.$quiz->questionresult.'</questionresult>';
                echo '<classresult>'.$classresult.'</classresult>';
                echo '</statistics>';
            }
        }
    }
}

function realtimequiz_record_answer($quizid, $questionnum, $userid, $answerid) {
    $quiz = get_record('realtimequiz', 'id', $quizid);
    $question = get_record('realtimequiz_question', 'id', $quiz->currentquestion);
    $answer = get_record('realtimequiz_answer', 'id', $answerid);
    
    if (($answer->questionid == $quiz->currentquestion) 
        && ($question->questionnum == $questionnum)) {
        if (0 < count_records('realtimequiz_submitted','questionid', $question->id, 'sessionid', $quiz->currentsessionid, 'userid', $userid)) {
            // Already got an answer from them - send an error so we know something is amiss
            realtimequiz_send_error(get_string('alreadyanswered','realtimequiz'));
        } else {
            $submitted = new Object();
            $submitted->questionid = $question->id;
            $submitted->sessionid = $quiz->currentsessionid;
            $submitted->userid = $userid;     //FIXME: make sure the userid is on the course
            $submitted->answerid = $answerid;
            insert_record('realtimequiz_submitted', $submitted);
            
            echo '<status>answerreceived</status>';
        }
        
    } else {
    
        // Answer is not for the current question - so send the current question
        realtimequiz_send_question($quizid);
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
    $quiz = get_record('realtimequiz', 'id', $quizid);
    $questionnum = get_field('realtimequiz_question', 'questionnum', 'id', $quiz->currentquestion);
    echo '<status>finalresults</status>';
    echo '<classresult>'.intval($quiz->classresult / $questionnum).'</classresult>';
}

// Check if the current status should change due to a timeout 
function realtimequiz_update_status($quizid, $status) {
    if ($status == REALTIMEQUIZ_STATUS_PREVIEWQUESTION) {
		$question = get_record('realtimequiz', 'id', $quizid);
		if ($question->nextendtime < time()) {
			$questiontime = get_field('realtimequiz_question','questiontime','id',$question->currentquestion);
			if ($questiontime == 0) {
				$questiontime = $question->questiontime;
			}
            $timeleft = $question->nextendtime - time() + $questiontime; 
            if ($timeleft > 0) {
    			$question->status = REALTIMEQUIZ_STATUS_SHOWQUESTION;
    			$question->nextendtime = time() + $timeleft;
            } else {
                $question->status = REALTIMEQUIZ_STATUS_SHOWRESULTS;
            }
			update_record('realtimequiz', $question);
			$status = $question->status;
		}			
    } else if ($status == REALTIMEQUIZ_STATUS_SHOWQUESTION) {
		$nextendtime = get_field('realtimequiz', 'nextendtime', 'id', $quizid);
		if ($nextendtime < time()) {
			$status = REALTIMEQUIZ_STATUS_SHOWRESULTS;
			set_field('realtimequiz', 'status', $status, 'id', $quizid);
		}
    } else if (($status != REALTIMEQUIZ_STATUS_NOTRUNNING) && ($status != REALTIMEQUIZ_STATUS_READYTOSTART) && ($status != REALTIMEQUIZ_STATUS_SHOWRESULTS) && ($status != REALTIMEQUIZ_STATUS_FINALRESULTS)) {
        // Bad status = probably should set it back to 0
        $status = REALTIMEQUIZ_STATUS_NOTRUNNING;
        set_field('realtimequiz','status', REALTIMEQUIZ_STATUS_NOTRUNNING, 'id',$quizid);
    }
        
    return $status;
}

// Check the question requested matches the current question
function realtimequiz_current_question($quizid, $questionnumber) {
    $questionid = get_field('realtimequiz', 'currentquestion', 'id', $quizid);
    if (!$questionid) {
		return false;
    }
	if ($questionnumber != get_field('realtimequiz_question', 'questionnum', 'id', $questionid)) {
		return false;
    }

    return true;
}

function realtimequiz_goto_question($context, $quizid, $questionnum) {
    if (has_capability('mod/realtimequiz:control', $context)) {
        $quiz = get_record('realtimequiz', 'id', $quizid);
        // Update the question statistics:
        $quiz->classresult += $quiz->questionresult;
        $quiz->questionresult = 0;
        $questionid = get_field('realtimequiz_question', 'id', 'quizid', $quizid, 'questionnum', $questionnum);
        if ($questionid) {
            $quiz->currentquestion = $questionid;
            $quiz->status = REALTIMEQUIZ_STATUS_PREVIEWQUESTION;
            $quiz->nextendtime = time() + 2;    // Give everyone a chance to get the question before starting
            update_record('realtimequiz', $quiz);
            realtimequiz_send_question($quizid, true);
        } else { // Assume we have run out of questions
            $quiz->status = REALTIMEQUIZ_STATUS_FINALRESULTS;
            update_record('realtimequiz', $quiz);
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

if (! $realtimequiz = get_record("realtimequiz", "id", $quizid)) {
	realtimequiz_send_error("Quiz ID incorrect");
	realtimequiz_end_response();
	die();
}
if (! $course = get_record("course", "id", $realtimequiz->course)) {
	realtimequiz_send_error("Course is misconfigured");
	realtimequiz_end_response();
	die();
}
if (! $cm = get_coursemodule_from_instance("realtimequiz", $realtimequiz->id, $course->id)) {
	realtimequiz_send_error("Course Module ID was incorrect");
	realtimequiz_end_response();
	die();
}
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

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
			$session->id = insert_record('realtimequiz_session', $session);
            		
            $quiz = get_record('realtimequiz', 'id', $quizid);
			$quiz->currentsessionid = $session->id;
            $quiz->status = REALTIMEQUIZ_STATUS_READYTOSTART;
            $quiz->currentquestion = 0;
            $quiz->classresult = 0;
            $quiz->questionresult = 0;
            update_record('realtimequiz', $quiz);
			
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
            realtimequiz_send_question($quizid, true); // (don't care what they asked for)
            break;

        case REALTIMEQUIZ_STATUS_SHOWQUESTION: // Question being displayed
            if (($requesttype == 'getquestion') || ($requesttype == 'nextquestion')) { // Student asked for a question - so send it
                realtimequiz_send_question($quizid);

            } else if ($requesttype == 'postanswer') {
                $questionnum = required_param('question', PARAM_INT); 
                $userid = required_param('userid', PARAM_INT);
                $answerid = required_param('answer', PARAM_INT);
                realtimequiz_record_answer($quizid, $questionnum, $userid, $answerid);

            } else if ($requesttype == 'getresults') {
                $questionnum = required_param('question', PARAM_INT);
                if (realtimequiz_current_question($quizid, $questionnum)) {
                    $timeleft = get_field('realtimequiz','nextendtime','id',$quizid) - time();
                    if ($timeleft < 0) {
                        $timeleft = 0;
                    }
                    realtimequiz_send_await_results($timeleft + 0.1); // results not yet ready
                } else {
                    realtimequiz_send_question($quizid); // asked for results for wrong question
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
                $questionid = get_field('realtimequiz', 'currentquestion', 'id', $quizid);
				$questionnum = get_field('realtimequiz_question', 'questionnum', 'id', $questionid);
                $questionnum++;
                realtimequiz_goto_question($context, $quizid, $questionnum);
            
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

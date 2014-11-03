<?php
/**
 * This dynamically sends quiz data to clients
 *
 * @author: Davosmith
 * @package realtimequiz
 **/

define('AJAX_SCRIPT', true);

require_once('../../config.php');
global $CFG, $DB, $USER, $PAGE;
require_once($CFG->dirroot.'/mod/realtimequiz/lib.php');
require_once($CFG->dirroot.'/mod/realtimequiz/locallib.php');
require_once($CFG->libdir.'/filelib.php');

require_login();
require_sesskey();
$requesttype = required_param('requesttype', PARAM_ALPHA);
$quizid = required_param('quizid', PARAM_INT);

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
$PAGE->set_context($context);

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
                realtimequiz_goto_question($context, $quizid, 1);
            } else {
                realtimequiz_send_await_question(); //  (don't care what they asked for)
            }
            break;

        case REALTIMEQUIZ_STATUS_PREVIEWQUESTION: // Previewing question (send it out, but ask them to wait before showing)
            realtimequiz_send_question($quizid, $context, true); // (don't care what they asked for)
            break;

        case REALTIMEQUIZ_STATUS_SHOWQUESTION: // Question being displayed
            if ($requesttype == 'getquestion' || $requesttype == 'nextquestion' || $requesttype == 'teacherrejoin') {
                // Student asked for a question - so send it.
                realtimequiz_send_question($quizid, $context);

            } else if ($requesttype == 'postanswer') {
                $questionnum = required_param('question', PARAM_INT);
                $userid = $USER->id;
                $answerid = required_param('answer', PARAM_INT);
                realtimequiz_record_answer($quizid, $questionnum, $userid, $answerid, $context);

            } else if ($requesttype == 'getresults') {
                $questionnum = required_param('question', PARAM_INT);
                if (realtimequiz_current_question($quizid, $questionnum)) {
                    $timeleft = $DB->get_field('realtimequiz','nextendtime',array('id' => $quizid)) - time();
                    if ($timeleft < 0) {
                        $timeleft = 0;
                    }
                    realtimequiz_send_await_results($timeleft); // results not yet ready
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

            } else if ($requesttype == 'postanswer' || $requesttype == 'getresults' || $requesttype == 'teacherrejoin') {
                realtimequiz_send_results($quizid);

            } else if ($requesttype == 'nextquestion') {
                $clientquestionnum = required_param('currentquestion', PARAM_INT);
                $questionid = $DB->get_field('realtimequiz', 'currentquestion', array('id' => $quizid));
                $questionnum = $DB->get_field('realtimequiz_question', 'questionnum', array('id' => $questionid));
                if ($clientquestionnum != $questionnum) {
                    realtimequiz_send_results($quizid);
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

<?php
/**
 * This allows you to edit questions for a realtimequiz
 *
 * @author: Davosmith
 * @package mod_realtimequiz
 **/


require_once('../../config.php');
global $CFG, $DB, $PAGE;
require_once($CFG->dirroot.'/mod/realtimequiz/lib.php');

$id = optional_param('id', false, PARAM_INT);
$quizid = optional_param('quizid', false, PARAM_INT);
$action = optional_param('action', 'listquestions', PARAM_ALPHA);
$questionid = optional_param('questionid', 0, PARAM_INT);

$addanswers = optional_param('addanswers', false, PARAM_BOOL);
$saveadd = optional_param('saveadd', false, PARAM_BOOL);
$canceledit = optional_param('cancel', false, PARAM_BOOL);

$removeimage = optional_param('removeimage', false, PARAM_BOOL);

if ($id) {
    $cm = get_coursemodule_from_id('realtimequiz', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $quiz = $DB->get_record('realtimequiz', array('id' => $cm->instance), '*', MUST_EXIST);
    $quizid = $quiz->id;
} else {
    $quiz = $DB->get_record('realtimequiz', array('id' => $quizid), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('realtimequiz', $quiz->id, $course->id, false, MUST_EXIST);
}

$PAGE->set_url(new moodle_url('/mod/realtimequiz/edit.php', array('id' => $cm->id)));

require_login($course->id, false, $cm);

$PAGE->set_pagelayout('incourse');
if ($CFG->version < 2011120100) {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
} else {
    $context = context_module::instance($cm->id);
}
require_capability('mod/realtimequiz:editquestions', $context);

// Log this visit.
if ($CFG->version > 2014051200) { // Moodle 2.7+
    $params = array(
        'courseid' => $course->id,
        'context' => $context,
        'other' => array(
            'quizid' => $quiz->id
        )
    );
    $event = \mod_realtimequiz\event\edit_page_viewed::create($params);
    $event->trigger();
} else {
    add_to_log($course->id, "realtimequiz", "update: $action", "edit.php?quizid=$quizid");
}

// Some useful functions:
function realtimequiz_list_questions($quizid, $cm) {
    global $DB, $OUTPUT;

    echo '<h2>'.get_string('questionslist','realtimequiz').'</h2>';

    $questions = $DB->get_records('realtimequiz_question', array('quizid' => $quizid), 'questionnum');
    $questioncount = count($questions);
    $expectednumber = 1;
    echo '<ol>';
    foreach ($questions as $question) {
        // A good place to double-check the question numbers and fix any that are broken
        if ($question->questionnum != $expectednumber) {
            $question->questionnum = $expectednumber;
            $DB->update_record('realtimequiz_question', $question);
        }

        $editurl = new moodle_url('/mod/realtimequiz/editquestion.php', array('quizid' => $quizid, 'questionid' => $question->id));
        $qtext = format_string($question->questiontext);
        echo "<li><span class='realtimequiz_editquestion'>";
        echo html_writer::link($editurl, $qtext);
        echo " </span><span class='realtimequiz_editicons'>";
        if ($question->questionnum > 1) {
            $alt = get_string('questionmoveup', 'mod_realtimequiz', $question->questionnum);
            echo "<a href='edit.php?quizid=$quizid&amp;action=moveup&amp;questionid=$question->id'><img src='".$OUTPUT->pix_url('t/up')."' alt='{$alt}' title='{$alt}' /></a> ";
        } else {
            echo '<img src="'.$OUTPUT->pix_url('spacer').'" width="15px" />';
        }
        if ($question->questionnum < $questioncount) {
            $alt = get_string('questionmovedown', 'mod_realtimequiz', $question->questionnum);
            echo "<a href='edit.php?quizid=$quizid&amp;action=movedown&amp;questionid=$question->id'><img src='".$OUTPUT->pix_url('t/down')."' alt='{$alt}' title='{$alt}' /></a> ";
        } else {
            echo '<img src="'.$OUTPUT->pix_url('spacer').'" width="15px" />';
        }
        echo '&nbsp;';
        $alt = get_string('questiondelete', 'mod_realtimequiz', $question->questionnum);
        echo "<a href='edit.php?quizid=$quizid&amp;action=deletequestion&amp;questionid=$question->id'><img src='".$OUTPUT->pix_url('t/delete')."' alt='{$alt}' title='{$alt}' /></a>";
        echo '</span></li>';
        $expectednumber++;
    }
    echo '</ol>';
    $url = new moodle_url('/mod/realtimequiz/editquestion.php', array('quizid'=>$quizid));
    echo $OUTPUT->single_button($url, get_string('addquestion','realtimequiz'), 'GET');
}

function realtimequiz_confirm_deletequestion($quizid, $questionid, $context) {
    global $DB;

    $question = $DB->get_record('realtimequiz_question', array('id' => $questionid, 'quizid' => $quizid), '*', MUST_EXIST);

    echo '<center><h2>'.get_string('deletequestion', 'realtimequiz').'</h2>';
    echo '<p>'.get_string('checkdelete','realtimequiz').'</p><p>';
    $questiontext = format_text($question->questiontext, $question->questiontextformat);
    $questiontext =  file_rewrite_pluginfile_urls($questiontext, 'pluginfile.php', $context->id, 'mod_realtimequiz',
                                                  'question', $questionid);
    echo $questiontext;
    echo '</p>';

    $url = new moodle_url('/mod/realtimequiz/edit.php',array('quizid'=>$quizid));
    echo '<form method="post" action="'.$url.'">';
    echo '<input type="hidden" name="action" value="dodeletequestion" />';
    echo '<input type="hidden" name="questionid" value="'.$questionid.'" />';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<input type="submit" name="yes" value="'.get_string('yes').'" /> ';
    echo '<input type="submit" name="no" value="'.get_string('no').'" />';
    echo '</form></center>';
}

// Back to the main code
$strrealtimequizzes = get_string("modulenameplural", "realtimequiz");
$strrealtimequiz  = get_string("modulename", "realtimequiz");

$PAGE->set_title(strip_tags($course->shortname.': '.$strrealtimequiz.': '.format_string($quiz->name,true)));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

realtimequiz_view_tabs('edit', $cm->id, $context);

echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter realtimequizbox');

if ($action == 'dodeletequestion') {

    if (!confirm_sesskey()) {
        error(get_string('badsesskey','realtimequiz'));
    }

    if (optional_param('yes', false, PARAM_BOOL)) {
        if ($question = $DB->get_record('realtimequiz_question', array('id' => $questionid, 'quizid' => $quiz->id))) {
            $answers = $DB->get_records('realtimequiz_answer', array('questionid' => $question->id));
            if (!empty($answers)) {
                foreach ($answers as $answer) { // Get each answer for that question.
                    $DB->delete_records('realtimequiz_submitted', array('answerid' => $answer->id)); // Delete any submissions for that answer.
                }
            }
            $DB->delete_records('realtimequiz_answer', array('questionid' => $question->id)); // Delete each answer.
            $DB->delete_records('realtimequiz_question', array('id' => $question->id));

            // Delete files embedded in the heading.
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, 'mod_realtimequiz', 'question', $questionid);
            // Questionnumbers sorted out when we display the list of questions
        }
    }

    $action = 'listquestions';

} elseif ($action == 'moveup') {

    $thisquestion = $DB->get_record('realtimequiz_question', array('id' => $questionid));
    if ($thisquestion) {
        $questionnum = $thisquestion->questionnum;
        if ($questionnum > 1) {
            $swapquestion = $DB->get_record('realtimequiz_question', array('quizid' => $quizid, 'questionnum' => ($questionnum - 1)));
            if ($swapquestion) {
                $upd = new stdClass;
                $upd->id = $thisquestion->id;
                $upd->questionnum = $questionnum - 1;
                $DB->update_record('realtimequiz_question', $upd);

                $upd = new stdClass;
                $upd->id = $swapquestion->id;
                $upd->questionnum = $questionnum;
                $DB->update_record('realtimequiz_question', $upd);
                //} else {
                // FIXME? Is it safe just to ignore this?
            }
        }
        //} else {
        // FIXME? Not really that important - can we get away with just ignoring it?
    }

    $action = 'listquestions';

} elseif ($action == 'movedown') {
    $thisquestion = $DB->get_record('realtimequiz_question', array('id' => $questionid));
    if ($thisquestion) {
        $questionnum = $thisquestion->questionnum;
        $swapquestion = $DB->get_record('realtimequiz_question', array('quizid' => $quizid, 'questionnum' => ($questionnum + 1)));
        if ($swapquestion) {
            $upd = new stdClass;
            $upd->id = $thisquestion->id;
            $upd->questionnum = $questionnum + 1;
            $DB->update_record('realtimequiz_question', $upd);

            $upd = new stdClass;
            $upd->id = $swapquestion->id;
            $upd->questionnum = $questionnum;
            $DB->update_record('realtimequiz_question', $upd);
            //} else {
            // FIXME? Is it safe just to ignore this?
        }
        //} else {
        // FIXME? Not really that important - can we get away with just ignoring it?
    }

    $action = 'listquestions';
}

switch ($action) {

case 'listquestions': //Show all the currently available questions
    realtimequiz_list_questions($quizid, $cm);
    break;

case 'deletequestion': // Deleting a question - ask 'Are you sure?'
    realtimequiz_confirm_deletequestion($quizid, $questionid, $context);
    break;

}

echo $OUTPUT->box_end();

/// Finish the page
echo $OUTPUT->footer();


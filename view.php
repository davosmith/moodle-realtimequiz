<?php
/**
 * This page prints a particular instance of realtimequiz
 *
 * @author  Davosmith
 * @package realtimequiz
 **/

require_once("../../config.php");
global $CFG, $DB, $PAGE, $OUTPUT;
require_once($CFG->dirroot.'/mod/realtimequiz/lib.php');
require_once($CFG->dirroot.'/mod/realtimequiz/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // realtimequiz ID

if ($id) {
    $cm = get_coursemodule_from_id('realtimequiz', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $realtimequiz = $DB->get_record('realtimequiz', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $realtimequiz = $DB->get_record('realtimequiz', array('id' => $bid), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('realtimequiz', $realtimequiz->id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $id = $cm->id;
}

$PAGE->set_url(new moodle_url('/mod/realtimequiz/view.php', array('id' => $cm->id)));

require_login($course->id, false, $cm);
$PAGE->set_pagelayout('incourse');

if ($CFG->version < 2011120100) {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
} else {
    $context = context_module::instance($cm->id);
}

$questioncount = $DB->count_records('realtimequiz_question', array('quizid' => $realtimequiz->id));
if ($questioncount == 0 && has_capability('mod/realtimequiz:editquestions', $context)) {
    redirect('edit.php?id='.$id);
}

require_capability('mod/realtimequiz:attempt', $context);

if ($CFG->version > 2014051200) { // Moodle 2.7+
    $params = array(
        'context' => $context,
        'objectid' => $realtimequiz->id
    );
    $event = \mod_realtimequiz\event\course_module_viewed::create($params);
    $event->add_record_snapshot('realtimequiz', $realtimequiz);
    $event->trigger();
} else {
    add_to_log($course->id, 'realtimequiz', 'view all', "index.php?id=$course->id", "");
}

$quizstatus = realtimequiz_update_status($realtimequiz->id, $realtimequiz->status);

/// Print the page header

$strrealtimequizzes = get_string("modulenameplural", "realtimequiz");
$strrealtimequiz  = get_string("modulename", "realtimequiz");

$PAGE->set_title(strip_tags($course->shortname.': '.$strrealtimequiz.': '.format_string($realtimequiz->name,true)));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($realtimequiz->name));

realtimequiz_view_tabs('view', $cm->id, $context);

echo format_text($realtimequiz->intro, $realtimequiz->introformat);


/// Print the main part of the page

if ($CFG->version < 2013111800) {
    $tickimg = $OUTPUT->pix_url('i/tick_green_big');
    $crossimg = $OUTPUT->pix_url('i/cross_red_big');
    $spacer = $OUTPUT->pix_url('spacer');
} else if ($CFG->branch < 33) {
    $tickimg = $OUTPUT->pix_url('i/grade_correct');
    $crossimg = $OUTPUT->pix_url('i/grade_incorrect');
    $spacer = $OUTPUT->pix_url('spacer');
} else {
    $tickimg = $OUTPUT->image_url('i/grade_correct');
    $crossimg = $OUTPUT->image_url('i/grade_incorrect');
    $spacer = $OUTPUT->image_url('spacer');
}


echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter realtimequizbox');
?>
<div id="questionarea"></div>
<!--    <div id="debugarea" style="border: 1px dashed black; width: 600px; height: 100px; overflow: scroll; "></div>
    <button onclick="realtimequiz_debug_stopall();">Stop</button> -->
<script type="text/javascript" src="<?php echo $CFG->wwwroot; ?>/mod/realtimequiz/view_student.js"></script>
<script type="text/javascript">
    realtimequiz_set_maxanswers(10);
    realtimequiz_set_quizid(<?php echo $realtimequiz->id; ?>);
    realtimequiz_set_userid(<?php echo $USER->id; ?>);
    realtimequiz_set_sesskey('<?php echo sesskey(); ?>');
    realtimequiz_set_coursepage('<?php echo "$CFG->wwwroot/course/view.php?id=$course->id"; ?>');
    realtimequiz_set_siteroot('<?php echo "$CFG->wwwroot"; ?>');
    realtimequiz_set_running(<?php echo (realtimequiz_is_running($quizstatus) ? 'true' : 'false'); ?>);

    realtimequiz_set_image('tick',"<?php echo $tickimg ?>");
    realtimequiz_set_image('cross',"<?php echo $crossimg ?>");
    realtimequiz_set_image('blank',"<?php echo $spacer ?>");

    //Pass all the text strings into the javascript (to allow for translation)
    // Used by view_student.js
    realtimequiz_set_text('joinquiz',"<?php print_string('joinquiz', 'realtimequiz') ?>");
    realtimequiz_set_text('joininstruct',"<?php print_string('joininstruct', 'realtimequiz') ?>");
    realtimequiz_set_text('waitstudent',"<?php print_string('waitstudent', 'realtimequiz') ?>");
    realtimequiz_set_text('clicknext',"<?php print_string('clicknext', 'realtimequiz') ?>");
    realtimequiz_set_text('waitfirst',"<?php print_string('waitfirst', 'realtimequiz') ?>");
    realtimequiz_set_text('question',"<?php print_string('question', 'realtimequiz') ?>");
    realtimequiz_set_text('invalidanswer',"<?php print_string('invalidanswer', 'realtimequiz') ?>");
    realtimequiz_set_text('finalresults',"<?php print_string('finalresults', 'realtimequiz') ?>");
    realtimequiz_set_text('quizfinished',"<?php print_string('quizfinished', 'realtimequiz') ?>");
    realtimequiz_set_text('classresult',"<?php print_string('classresult', 'realtimequiz') ?>");
    realtimequiz_set_text('classresultcorrect',"<?php print_string('classresultcorrect', 'realtimequiz') ?>");
    realtimequiz_set_text('questionfinished',"<?php print_string('questionfinished', 'realtimequiz') ?>");
    realtimequiz_set_text('httprequestfail',"<?php print_string('httprequestfail', 'realtimequiz') ?>");
    realtimequiz_set_text('noquestion',"<?php print_string('noquestion', 'realtimequiz') ?>");
    realtimequiz_set_text('tryagain',"<?php print_string('tryagain', 'realtimequiz') ?>");
    realtimequiz_set_text('resultthisquestion',"<?php print_string('resultthisquestion', 'realtimequiz') ?>");
    realtimequiz_set_text('resultoverall',"<?php print_string('resultoverall', 'realtimequiz') ?>");
    realtimequiz_set_text('resultcorrect',"<?php print_string('resultcorrect', 'realtimequiz') ?>");
    realtimequiz_set_text('answersent',"<?php print_string('answersent', 'realtimequiz') ?>");
    realtimequiz_set_text('quiznotrunning',"<?php print_string('quiznotrunning', 'realtimequiz') ?>");
    realtimequiz_set_text('servererror',"<?php print_string('servererror', 'realtimequiz') ?>");
    realtimequiz_set_text('badresponse',"<?php print_string('badresponse', 'realtimequiz') ?>");
    realtimequiz_set_text('httperror',"<?php print_string('httperror', 'realtimequiz') ?>");
    realtimequiz_set_text('yourresult',"<?php print_string('yourresult', 'realtimequiz') ?>");

    realtimequiz_set_text('timeleft',"<?php print_string('timeleft', 'realtimequiz') ?>");
    realtimequiz_set_text('displaynext', "<?php print_string('displaynext', 'realtimequiz') ?>");
    realtimequiz_set_text('sendinganswer', "<?php print_string('sendinganswer', 'realtimequiz') ?>");
    realtimequiz_set_text('tick', "<?php print_string('tick', 'realtimequiz') ?>");
    realtimequiz_set_text('cross', "<?php print_string('cross', 'realtimequiz') ?>");


    // Used by view_teacher.js
    realtimequiz_set_text('joinquizasstudent',"<?php print_string('joinquizasstudent', 'realtimequiz') ?>");
    realtimequiz_set_text('next',"<?php print_string('next', 'realtimequiz') ?>");
    realtimequiz_set_text('startquiz',"<?php print_string('startquiz', 'realtimequiz') ?>");
    realtimequiz_set_text('startnewquiz',"<?php print_string('startnewquiz', 'realtimequiz') ?>");
    realtimequiz_set_text('startnewquizconfirm',"<?php print_string('startnewquizconfirm', 'realtimequiz') ?>");
    realtimequiz_set_text('teacherstartinstruct',"<?php print_string('teacherstartinstruct', 'realtimequiz') ?>");
    realtimequiz_set_text('teacherstartnewinstruct',"<?php print_string('teacherstartnewinstruct', 'realtimequiz') ?>");
    realtimequiz_set_text('teacherjoinquizinstruct',"<?php print_string('teacherjoinquizinstruct', 'realtimequiz') ?>");
    realtimequiz_set_text('reconnectquiz',"<?php print_string('reconnectquiz', 'realtimequiz') ?>");
    realtimequiz_set_text('reconnectinstruct',"<?php print_string('reconnectinstruct', 'realtimequiz') ?>");
</script>

<?php

if (has_capability('mod/realtimequiz:control', $context)) {
    ?>
<script type="text/javascript" src="<?php echo $CFG->wwwroot; ?>/mod/realtimequiz/view_teacher.js"></script>
<script type="text/javascript">
    realtimequiz_init_teacher_view();
</script>
<?php
} else {
    echo '<script type="text/javascript">realtimequiz_init_student_view();</script>';
}

echo $OUTPUT->box_end();

/// Finish the page
echo $OUTPUT->footer();


<?php
/**
 * This page prints a particular instance of realtimequiz
 * 
 * @author  Davosmith
 * @package realtimequiz
 **/
 
    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // realtimequiz ID

    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }
    
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
    
        if (! $realtimequiz = get_record("realtimequiz", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $realtimequiz = get_record("realtimequiz", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $realtimequiz->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("realtimequiz", $realtimequiz->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }
    
    require_login($course->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	
	$questioncount = count_records('realtimequiz_question', 'quizid', $realtimequiz->id);
    if ($questioncount == 0 && has_capability('mod/realtimequiz:editquestions', $context)) {
        redirect('edit.php?id='.$realtimequiz->id);
    }

    require_capability('mod/realtimequiz:attempt', $context);

    add_to_log($course->id, "realtimequiz", "view", "view.php?id=$cm->id", "$realtimequiz->id");

/// Print the page header

    $strrealtimequizzes = get_string("modulenameplural", "realtimequiz");
    $strrealtimequiz  = get_string("modulename", "realtimequiz");

    if ($CFG->version < 2007101500) { // < Moodle 1.9
        if ($course->category) {
            $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
        } else {
            $navigation = '';
        }

        print_header("$course->shortname: $realtimequiz->name", "$course->fullname",
                     "$navigation <a href=index.php?id=$course->id>$strrealtimequizzes</a> -> $realtimequiz->name", 
                     "", "", true, update_module_button($cm->id, $course->id, $strrealtimequiz), 
                     navmenu($course, $cm));
    } else { // Moodle 1.9
        $navlinks = array();
        $navlinks[] = array('name' => $strrealtimequizzes, 'link' => "index.php?id={$course->id}", 'type' => 'activity');
        $navlinks[] = array('name' => format_string($realtimequiz->name), 'link' => '', 'type' => 'activityinstance');

        $navigation = build_navigation($navlinks);
        
        $pagetitle = strip_tags($course->shortname.': '.$strrealtimequiz.': '.format_string($realtimequiz->name,true));

        print_header_simple($pagetitle, '', $navigation, '', '', true,
                            update_module_button($cm->id, $course->id, $strrealtimequiz), navmenu($course, $cm));
        
    }
                  
	if (has_capability('mod/realtimequiz:editquestions', $context)) {
		echo "<div class='reportlink'><a href='$CFG->wwwroot/mod/realtimequiz/edit.php?id=$realtimequiz->id'>".get_string('editquestions','realtimequiz').'</a></div>';
	}
				  
    if (has_capability('mod/realtimequiz:seeresponses', $context)) {
        echo "<div class='reportlink'><a href='$CFG->wwwroot/mod/realtimequiz/responses.php?id=$cm->id'>".get_string('seeresponses','realtimequiz').'</a></div>';
    }

/// Print the main part of the page

?>
    <div id="questionarea"></div>
<!--    <div id="debugarea" style="border: 1px dashed black; width: 600px; height: 100px; overflow: scroll; "></div> -->
    <script type="text/javascript" src="<?php echo $CFG->wwwroot; ?>/mod/realtimequiz/view_student.js"></script>
    <script type="text/javascript">
        realtimequiz_set_maxanswers(10);
        realtimequiz_set_quizid(<?php echo $realtimequiz->id; ?>);
        realtimequiz_set_userid(<?php echo $USER->id; ?>);
        realtimequiz_set_sesskey('<?php echo sesskey(); ?>');
        realtimequiz_set_coursepage('<?php echo "$CFG->wwwroot/course/view.php?id=$course->id"; ?>');
        realtimequiz_set_siteroot('<?php echo "$CFG->wwwroot"; ?>');

        realtimequiz_set_image('tick',"<?php echo $CFG->pixpath.'/i/tick_green_big.gif'; ?>");
        realtimequiz_set_image('cross',"<?php echo $CFG->pixpath.'/i/cross_red_big.gif'; ?>");
        
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
        realtimequiz_set_text('next',"<?php print_string('next', 'realtimequiz') ?>");
        realtimequiz_set_text('startquiz',"<?php print_string('startquiz', 'realtimequiz') ?>");
        realtimequiz_set_text('teacherstartinstruct',"<?php print_string('teacherstartinstruct', 'realtimequiz') ?>");
        realtimequiz_set_text('teacherjoinquizinstruct',"<?php print_string('teacherjoinquizinstruct', 'realtimequiz') ?>");
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
    

/// Finish the page
    print_footer($course);

?>

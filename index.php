<?php 
/**
 * This page lists all the instances of realtimequiz in a particular course
 *
 * @author: Davosmith
 * @package realtimequiz
 **/

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "realtimequiz", "view all", "index.php?id=$course->id", "");


/// Get all required strings

    $strrealtimequizzes = get_string("modulenameplural", "realtimequiz");
    $strrealtimequiz  = get_string("modulename", "realtimequiz");

    if ($CFG->version < 2007101500) { // < Moodle 1.9
        if ($course->category) {
            $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
        } else {
            $navigation = '';
        }

        print_header("$course->shortname: $strrealtimequizzes", "$course->fullname", "$navigation $strrealtimequizzes", "", "", true, "", navmenu($course));

    } else { // Moodle 1.9
        $navlinks = array();
        $navlinks[] = array('name' => $strrealtimequizzes, 'link' => '');

        $navigation = build_navigation($navlinks);
        
        $pagetitle = strip_tags($course->shortname.': '.$strrealtimequizzes);

        print_header_simple($pagetitle, '', $navigation, '', '', true, '', navmenu($course));
        
    }

/// Get all the appropriate data

    if (! $realtimequizs = get_all_instances_in_course("realtimequiz", $course)) {
        notice("There are no realtimequizs", "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname);
        $table->align = array ("center", "left");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname);
        $table->align = array ("center", "left", "left", "left");
    } else {
        $table->head  = array ($strname);
        $table->align = array ("left", "left", "left");
    }

    foreach ($realtimequizs as $realtimequiz) {
        if (!$realtimequiz->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$realtimequiz->coursemodule\">$realtimequiz->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$realtimequiz->coursemodule\">$realtimequiz->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($realtimequiz->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<br />";
    
    print_table($table);

/// Finish the page

    
    print_footer($course);


?>

<?php 
/**
 * This page prints a particular instance of realtimequiz
 * 
 * @author  Davo
 * @package realtimequiz
 **/

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // realtimequiz ID
	$showsession = optional_param('showsession', 0, PARAM_INT);
	$questionid = optional_param('questionid', 0, PARAM_INT);

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
    require_capability('mod/realtimequiz:seeresponses', $context);

    add_to_log($course->id, "realtimequiz", "seeresponses", "responses.php?id=$cm->id", "$realtimequiz->id");

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
                  
	$sessions = get_records('realtimequiz_session', 'quizid', $realtimequiz->id, 'timestamp');
	if (!$sessions) {
		error(get_string('nosessions','realtimequiz'));
	}
	$sessions = array_reverse($sessions);
		
	echo '<center><form method="get" action="'.$CFG->wwwroot.'/mod/realtimequiz/responses.php?id='.$cm->id.'">';
	echo '<b>'.get_string('choosesession','realtimequiz').'</b>';
	echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
	echo '<input type="hidden" name="questionid" value="'.$questionid.'" />';
	echo '<select name="showsession" size="1" >';
	if ($showsession == 0) {
		echo '<option value="0" selected="selected">'.get_string('allsessions','realtimequiz').'</option>';
	} else {
		echo '<option value="0">'.get_string('allsessions','realtimequiz').'</option>';
	}
	foreach ($sessions as $session) {
		$sesstext = '';
		if ($session->name) {
			$sesstext = $session->name.' '; // session name (if it exits) + date
		}
		$sesstext .= date('j/m/Y H:i', $session->timestamp);

		if ($showsession == $session->id) {
			echo "<option value='$session->id' selected='selected'>$sesstext</option>";
		} else {
			echo "<option value='$session->id'>$sesstext</option>";
		}
	}
	echo '</select> <input type="submit" value="'.get_string('showsession','realtimequiz').'" /></form></center>';
	
	if ($questionid == 0) {
		$questions = get_records('realtimequiz_question', 'quizid', $realtimequiz->id, 'questionnum');
		$linkurl = "$CFG->wwwroot/mod/realtimequiz/responses.php?id=$cm->id&showsession=$showsession&questionid=";
		
		echo '<br /><table border="1">';
		if (!empty($questions)) {
			foreach ($questions as $question) {
				echo '<tr><td width="30%">'.$question->questionnum.'</td>';
				$answers = get_records('realtimequiz_answer', 'questionid', $question->id, 'id');
				if (!empty($answers)) {
					foreach ($answers as $answer) {
						if ($answer->correct == 1) {
							echo '<td width="10%"><b>'.s($answer->answertext).'</b></td>';
						} else {
							echo '<td width="10%">'.s($answer->answertext).'</td>';
						}
					}
					echo '</tr><tr><td><a href="'.$linkurl.$question->id.'">'.s($question->questiontext).'</a></td>';
					foreach ($answers as $answer) {
						if ($showsession == 0) {
							$count = count_records('realtimequiz_submitted', 'answerid', $answer->id);
						} else {
							$count = count_records('realtimequiz_submitted', 'answerid', $answer->id, 'sessionid', $showsession);
						}
						if ($answer->correct == 1) {
							echo '<td align="center"><b>'.$count.'</b></td>';
						} else {
							echo '<td align="center">'.$count.'</td>';
						}
					}
				}
				echo '</tr><tr><td colspan="99">&nbsp;</td></tr>';
			}
		}
		echo '</table>';
	} else {
		$question = get_record('realtimequiz_question', 'questionid', $questionid);
		
		echo '<h2>'.get_string('question','realtimequiz').$question->questionnum.'</h2>';
		echo '<p>'.s($question->questiontext).'</p><br />';
		echo '<table border="1"><tr><td width="30%">&nbsp;</td>';
		$answers = get_records('realtimequiz_answer', 'questionid', $questionid,'id');
		if (!empty($answers)) {
			foreach ($answers as $answer) {
				if ($answer->correct == 1) {
					echo '<td width="10%"><b>'.s($answer->answertext).'</b></td>';
				} else {
					echo '<td width="10%">'.s($answer->answertext).'</td>';
				}
			}
		}
		echo '</tr>';
		if ($showsession == 0) {
			$submitted = get_records('realtimequiz_submitted', 'questionid', $questionid, 'userid');
		} else {
			$submitted = get_records_select('realtimequiz_submitted', "questionid='$questionid' AND sessionid='$showsession'", 'userid');
		}
		foreach ($submitted as $submission) {
			$user = get_record('user', 'id', $submission->userid);
			$fullname = fullname($user, has_capability('moodle/site:viewfullnames', $context));
			echo '<tr><td>'.$fullname.'</td>';

			foreach ($answers as $answer) {
				echo '<td align="center">';
				if ($answer->id == $submission->answerid) {
					if ($answer->correct == 1) {
						echo '<b>1</b>';
					} else {
						echo '1';
					}
				} else {
					echo '&nbsp;';
				}
				echo '</td>';
			}
			echo '</tr>';
		}
		echo '</table>';
		
		echo '<br /><div><a href="'.$CFG->wwwroot.'/mod/realtimequiz/responses.php?id='.$cm->id.'&showsession='.$showsession.'">';
		echo get_string('backresponses','realtimequiz').'</a></div>';
	}
	

	echo '<br /><div><a href=\''.$CFG->wwwroot.'/mod/realtimequiz/view.php?id='.$cm->id.'\'>'.get_string('backquiz','realtimequiz').'</a></div></center>';
              
    print_footer($course);
?>

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
    $nextquestion = optional_param('nextquestion', false, PARAM_TEXT);
    $prevquestion = optional_param('prevquestion', false, PARAM_TEXT);
    $allquestions = optional_param('allquestions', false, PARAM_TEXT);

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

    realtimequiz_view_tabs('responses', $cm->id, $context);
                  
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
	
    $tickimg = "<img src='{$CFG->pixpath}/i/tick_green_big.gif' alt='".get_string('tick','realtimequiz')."' />";
    $crossimg = "<img src='{$CFG->pixpath}/i/cross_red_big.gif' alt='".get_string('cross','realtimequiz')."' />";

    if ($questionid != 0) {
        if ($allquestions) {
            $questionid = 0;
        } elseif ($nextquestion) {
            $question = get_record('realtimequiz_question', 'id', $questionid);
            $newquestion = get_record('realtimequiz_question', 'quizid', $question->quizid, 'questionnum', $question->questionnum + 1);

            if ($newquestion) {
                $questionid = $newquestion->id;
            } else {
                $questionid = 0;
            }
        } elseif ($prevquestion) {
            $question = get_record('realtimequiz_question', 'id', $questionid);
            $newquestion = get_record('realtimequiz_question', 'quizid', $question->quizid, 'questionnum', $question->questionnum - 1);

            if ($newquestion) {
                $questionid = $newquestion->id;
            } else {
                $questionid = 0;
            }
        }
    }
        
    if ($questionid == 0) {
		$questions = get_records('realtimequiz_question', 'quizid', $realtimequiz->id, 'questionnum');
		$linkurl = "$CFG->wwwroot/mod/realtimequiz/responses.php?id=$cm->id&showsession=$showsession&questionid=";
		
		echo '<br /><table border="1" style="border-style: none;">';
		if (!empty($questions)) {
			foreach ($questions as $question) {
				echo '<tr class="realtimequiz_report_question"><td width="30%">'.$question->questionnum.'</td>';
				$answers = get_records('realtimequiz_answer', 'questionid', $question->id, 'id');
				if (!empty($answers)) {
					foreach ($answers as $answer) {
						if ($answer->correct == 1) {
							echo '<td width="10%" class="realtimequiz_report_question_correct"><b>'.s($answer->answertext).'</b></td>';
						} else {
							echo '<td width="10%">'.s($answer->answertext).'</td>';
						}
					}
					echo '</tr><tr class="realtimequiz_report_answer"><td><a href="'.$linkurl.$question->id.'">'.s($question->questiontext).'</a></td>';
					foreach ($answers as $answer) {
						if ($showsession == 0) {
							$count = count_records('realtimequiz_submitted', 'answerid', $answer->id);
						} else {
							$count = count_records('realtimequiz_submitted', 'answerid', $answer->id, 'sessionid', $showsession);
						}
						if ($answer->correct == 1) {
							echo '<td align="center" class="realtimequiz_report_answer_correct" ><b>'.$count.'</b>&nbsp;'.$tickimg.'</td>';
						} else {
							echo '<td align="center">'.$count.'&nbsp;'.$crossimg.'</td>';
						}
					}
				}
				echo '</tr><tr style="border-style: none;">';
                echo '<td style="border-style: none;">&nbsp;</td>';
                echo '</tr>';
			}
		}
		echo '</table>';
	} else {
        print_box_start('generalbox boxwidthwide boxaligncenter');

        $question = get_record('realtimequiz_question', 'id', $questionid);
		
		echo '<h2>'.get_string('question','realtimequiz').$question->questionnum.'</h2>';
		echo '<p>'.s($question->questiontext).'</p><br />';
		echo '<table border="1" class="realtimequiz_report_answer"><tr class="realtimequiz_report_question"><td width="30%">&nbsp;</td>';
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

        if (!$submitted) {
            echo '<tr><td colspan="99">'.get_string('noanswers','realtimequiz').'</td></tr>';
        } else {
            foreach ($submitted as $submission) {
                $user = get_record('user', 'id', $submission->userid);
                $fullname = fullname($user, has_capability('moodle/site:viewfullnames', $context));
                echo '<tr><td>'.$fullname.'</td>';

                foreach ($answers as $answer) {
                    echo '<td align="center">';

                    if ($answer->id == $submission->answerid) {
                        if ($answer->correct == 1) {
                            echo $tickimg;
                        } else {
                            echo $crossimg;
                        }
                    } else {
                        echo '&nbsp;';
                    }
                    echo '</td>';
                }
                echo '</tr>';
            }
        }
		echo '</table>';

        $thisurl = $CFG->wwwroot.'/mod/realtimequiz/responses.php';
        echo '<br /><form action="'.$thisurl.'" method="get">';
        echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
        echo '<input type="hidden" name="showsession" value="'.$showsession.'" />';
        echo '<input type="hidden" name="questionid" value="'.$questionid.'" />';

        echo '<input type="submit" name="prevquestion" value="'.get_string('prevquestion','realtimequiz').'" />&nbsp;';
        echo '<input type="submit" name="allquestions" value="'.get_string('allquestions','realtimequiz').'" />&nbsp;';
        echo '<input type="submit" name="nextquestion" value="'.get_string('nextquestion','realtimequiz').'" />';

        echo '</form>';
		

        print_box_end();
	}
	
    print_footer($course);
?>

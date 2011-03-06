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
        if (! $cm = $DB->get_record("course_modules", array('id' => $id))) {
            error("Course Module ID was incorrect");
        }
    
        if (! $course = $DB->get_record("course", array('id' => $cm->course))) {
            error("Course is misconfigured");
        }
    
        if (! $realtimequiz = $DB->get_record("realtimequiz", array('id' => $cm->instance))) {
            error("Course module is incorrect");
        }

    } else {
        if (! $realtimequiz = $DB->get_record("realtimequiz", array('id' => $a))) {
            error("Course module is incorrect");
        }
        if (! $course = $DB->get_record("course", array('id' => $realtimequiz->course))) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("realtimequiz", $realtimequiz->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }
    
    $PAGE->set_url(new moodle_url('/mod/realtimequiz/responses.php', array('id' => $cm->id)));

    require_login($course->id, false, $cm);
    $PAGE->set_pagelayout('incourse');
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/realtimequiz:seeresponses', $context);

    add_to_log($course->id, "realtimequiz", "seeresponses", "responses.php?id=$cm->id", "$realtimequiz->id");

/// Print the page header

    $strrealtimequizzes = get_string("modulenameplural", "realtimequiz");
    $strrealtimequiz  = get_string("modulename", "realtimequiz");

    $PAGE->set_title(strip_tags($course->shortname.': '.$strrealtimequiz.': '.format_string($realtimequiz->name,true)));
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($realtimequiz->name));

    realtimequiz_view_tabs('responses', $cm->id, $context);

    $sessions = $DB->get_records('realtimequiz_session', array('quizid' => $realtimequiz->id), 'timestamp');
    if (empty($sessions)) {
        echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter realtimequizbox');
        print_string('nosessions','realtimequiz');
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        die();
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
	
    $tickimg = '<img src="'.$OUTPUT->pix_url('i/tick_green_big').'" alt="'.get_string('tick','realtimequiz').'" />';
    $crossimg = '<img src="'.$OUTPUT->pix_url('i/cross_red_big').'" alt="'.get_string('cross','realtimequiz').'" />';

    if ($questionid != 0) {
        if ($allquestions) {
            $questionid = 0;
        } elseif ($nextquestion) {
            $question = $DB->get_record('realtimequiz_question', array('id' => $questionid) );
            $newquestion = $DB->get_record('realtimequiz_question', array('quizid' => $question->quizid, 'questionnum' => ($question->questionnum + 1)) );

            if ($newquestion) {
                $questionid = $newquestion->id;
            } else {
                $questionid = 0;
            }
        } elseif ($prevquestion) {
            $question = $DB->get_record('realtimequiz_question', array('id' => $questionid) );
            $newquestion = $DB->get_record('realtimequiz_question', array('quizid' => $question->quizid, 'questionnum' => ($question->questionnum - 1)) );

            if ($newquestion) {
                $questionid = $newquestion->id;
            } else {
                $questionid = 0;
            }
        }
    }

    if ($questionid == 0) { // Show all of the questions
        if (check_browser_version('Gecko')) {
            $blankcolspan = 'colspan="999" ';
        } else {
            $blankcolspan = '';
        }

		$questions = $DB->get_records('realtimequiz_question', array('quizid' => $realtimequiz->id), 'questionnum');
        $linkurl = new moodle_url('/mod/realtimequiz/responses.php', array('id'=>$cm->id, 'showsession'=>$session->id));
		
        echo '<br /><table border="1" style="border-style: none;">';
		if (!empty($questions)) {
            foreach ($questions as $question) {
				echo '<tr class="realtimequiz_report_question"><td width="30%">'.$question->questionnum.'</td>';
				$answers = $DB->get_records('realtimequiz_answer', array('questionid' => $question->id), 'id');
				if (!empty($answers)) {
					foreach ($answers as $answer) {
						if ($answer->correct == 1) {
							echo '<td width="10%" class="realtimequiz_report_question_correct"><b>'.s($answer->answertext).'</b></td>';
						} else {
							echo '<td width="10%">'.s($answer->answertext).'</td>';
						}
					}
					echo '</tr><tr class="realtimequiz_report_answer"><td><a href="'.$linkurl->out(true, array('questionid'=>$question->id)).'">'.s($question->questiontext).'</a></td>';

					foreach ($answers as $answer) {
						if ($showsession == 0) {
                            $count = $DB->count_records('realtimequiz_submitted', array('answerid' => $answer->id) );
						} else {
							$count = $DB->count_records('realtimequiz_submitted', array('answerid' => $answer->id, 'sessionid' => $showsession) );
						}
						if ($answer->correct == 1) {
							echo '<td align="center" class="realtimequiz_report_answer_correct" ><b>'.$count.'</b>&nbsp;'.$tickimg.'</td>';
						} else {
							echo '<td align="center">'.$count.'&nbsp;'.$crossimg.'</td>';
						}
                    }
                }
				echo '</tr>';
                echo '<tr style="border-style: none;"><td style="border-style: none;" '.$blankcolspan.' >&nbsp;</td></tr>';
            }
		}
		echo '</table>';
	} else { // Show a single question
        echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter realtimequizplainbox');

        $question = $DB->get_record('realtimequiz_question', array('id' => $questionid) );
		
		echo '<h2>'.get_string('question','realtimequiz').$question->questionnum.'</h2>';
		echo '<p>'.s($question->questiontext).'</p><br />';
		echo '<table border="1" class="realtimequiz_report_answer"><tr class="realtimequiz_report_question"><td width="30%">&nbsp;</td>';
		$answers = $DB->get_records('realtimequiz_answer', array('questionid' => $questionid),'id');
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
			$submitted = $DB->get_records('realtimequiz_submitted', array('questionid' => $questionid), 'userid');
		} else {
			$submitted = $DB->get_records('realtimequiz_submitted', array('questionid' => $questionid, 'sessionid' => $showsession), 'userid');
		}

        if (empty($submitted)) {
            echo '<tr><td colspan="99">'.get_string('noanswers','realtimequiz').'</td></tr>';
        } else {
            foreach ($submitted as $submission) {
                $user = $DB->get_record('user', array('id' => $submission->userid) );
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

        $thisurl = new moodle_url('/mod/realtimequiz/responses.php');
        echo '<br /><form action="'.$thisurl.'" method="get">';
        echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
        echo '<input type="hidden" name="showsession" value="'.$showsession.'" />';
        echo '<input type="hidden" name="questionid" value="'.$questionid.'" />';

        echo '<input type="submit" name="prevquestion" value="'.get_string('prevquestion','realtimequiz').'" />&nbsp;';
        echo '<input type="submit" name="allquestions" value="'.get_string('allquestions','realtimequiz').'" />&nbsp;';
        echo '<input type="submit" name="nextquestion" value="'.get_string('nextquestion','realtimequiz').'" />';

        echo '</form>';
		

        echo $OUTPUT->box_end();
	}
	
    echo $OUTPUT->footer();
?>

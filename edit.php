<?php
/**
 * This allows you to edit questions for a realtimequiz
 *
 * @author: Davosmith
 * @package realtimequiz
 **/


	require_once('../../config.php');
	require_once('lib.php');

    $id = optional_param('id', false, PARAM_INT);
    $quizid = optional_param('quizid', false, PARAM_INT);
	$action = optional_param('action', 'listquestions', PARAM_ALPHA);
	$questionid = optional_param('questionid', 0, PARAM_INT);

    $addanswers = optional_param('addanswers', false, PARAM_BOOL);
    $saveadd = optional_param('saveadd', false, PARAM_BOOL);
    $canceledit = optional_param('cancel', false, PARAM_BOOL);

    $removeimage = optional_param('removeimage', false, PARAM_BOOL);

    if ($id) {
        if (! $cm = $DB->get_record('course_modules', array('id' => $id))) {
            error("Course Module ID was incorrect");
        }
    
        if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
            error("Course is misconfigured");
        }
    
        if (! $quiz = $DB->get_record('realtimequiz', array('id' => $cm->instance))) {
            error("Course module is incorrect");
        }

        $quizid = $quiz->id;
        
    } else {
        if (! $quiz = $DB->get_record('realtimequiz', array('id' => $quizid))) {
            error("Quiz id ($quizid) is incorrect");
        }
        if (! $course = $DB->get_record('course', array('id' => $quiz->course))) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance('realtimequiz', $quiz->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    $PAGE->set_url(new moodle_url('/mod/realtimequiz/edit.php', array('id' => $cm->id)));

    require_login($course->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	require_capability('mod/realtimequiz:editquestions', $context);
	add_to_log($course->id, "realtimequiz", "update: $action", "edit.php?quizid=$quizid");

		
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
			
            $qtext = $question->questiontext;
            echo "<li><span class='realtimequiz_editquestion'>";
            echo "<a href='edit.php?quizid=$quizid&amp;action=editquestion&amp;questionid=$question->id'>";
            echo "$qtext</a> </span><span class='realtimequiz_editicons'>";
            if ($question->questionnum > 1) {
                echo "<a href='edit.php?quizid=$quizid&amp;action=moveup&amp;questionid=$question->id'><img src='".$OUTPUT->pix_url('t/up')."' alt='Move Question $question->questionnum Up' /></a> ";	//FIXME - translate alt text
            } else {
                echo '<img src="'.$OUTPUT->pix_url('spacer').'" width="15px" />';
            }
            if ($question->questionnum < $questioncount) {
                echo "<a href='edit.php?quizid=$quizid&amp;action=movedown&amp;questionid=$question->id'><img src='".$OUTPUT->pix_url('t/down')."' alt='Move Question $question->questionnum Down' /></a> ";	//FIXME - translate alt text
            } else {
                echo '<img src="'.$OUTPUT->pix_url('spacer').'" width="15px" />';
            }
            echo '&nbsp;';
            echo "<a href='edit.php?quizid=$quizid&amp;action=deletequestion&amp;questionid=$question->id'><img src='".$OUTPUT->pix_url('t/delete')."' alt='Delete Question $question->questionnum' /></a>";	//FIXME - translate alt text
            echo '</span></li>';
            $expectednumber++;
        }
		echo '</ol>';
        $url = new moodle_url('/mod/realtimequiz/edit.php', array('quizid'=>$quizid, 'action'=>'addquestion'));
		echo '<form method="post" action="'.$url.'">';
		echo '<input type="submit" value="'.get_string('addquestion','realtimequiz').'"></input></form>';
	}
	
function realtimequiz_edit_question($quizid, $maxfilesize, $questionid='', $minanswers=4) {
		global $DB;
			
		echo '<center>';
		if ($questionid=='') {
			$action = 'doaddquestion';
			$question = new stdClass();
			$question->id = 0;
			$question->quizid = $quizid;
			$question->questionnum = $DB->count_records('realtimequiz_question', array('quizid' => $quizid)) + 1;
			$question->questiontext = '';
			$question->questiontime = 0;
			$question->image = '';
			echo '<h2>'.get_string('addingquestion','realtimequiz').$question->questionnum.'</h2>';
			
			$answers = array();
		} else {
			$action = 'doeditquestion';
			$question = $DB->get_record('realtimequiz_question', array('id' => $questionid));
			if (!$question) {
				error("Question not found");
			}
			echo '<h2>'.get_string('edittingquestion','realtimequiz').$question->questionnum.'</h2>';
			
			$answers = $DB->get_records('realtimequiz_answer', array('questionid' => $questionid), 'id');
		}
		
        if (optional_param('saveadd', false, PARAM_BOOL)) {
            // When clicking 'save and add another' we want to ignore any parameters hanging around
            $answertexts = false;
            $answercorrects = false;
            $answerids = false;

        } else {
            // Override the above values with any parameters passed in
            $question->id = optional_param('questionid',$question->id, PARAM_INT);
            $question->questiontext = optional_param('questiontext', $question->questiontext, PARAM_TEXT);
            $answertexts = optional_param('answertext', false, PARAM_TEXT);
            $answercorrect = optional_param('answercorrect', false, PARAM_INT);
            $answerids = optional_param('answerid', false, PARAM_INT);
            $question->questiontime = optional_param('questiontime', $question->questiontime, PARAM_INT);
        }
		
		if ($answertexts !== false && $answercorrect !== false && $answerids !== false) {
			$answers = array();
			$answercount = count($answertexts);
			for ($i=1; $i<=$answercount; $i++) {
				$answers[$i] = new stdClass();
				$answers[$i]->id = $answerids[$i];
				$answers[$i]->answertext = $answertexts[$i];
				$answers[$i]->correct = ($answercorrect == $i);
			}
		}
		
        $url = new moodle_url('/mod/realtimequiz/edit.php', array('quizid'=>$quizid));
		echo '<form method="post" action="'.$url.'" enctype="multipart/form-data">';
		echo '<table cellpadding="5">';
        /*
          // FIXME Need to fix this by adding a proper Moodle form (but leaving out for the time being)
          
        echo '<tr><td colspan="2">';
        if ($question->image) {
		    $filename = $CFG->dataroot.'/'.$question->image;
		    if (file_exists($filename)) {
                $size = getimagesize($filename);
                if ($size) {
                    $imagewidth = $size[0];
                    $imageheight = $size[1];
                    if ($imagewidth > 400) {
                        $scale = 400 / $imagewidth;
                        $imagewidth = 400;
                        $imageheight *= $scale;
                    }
                    if ($imageheight > 400) {
                        $scale = 400 / $imageheight;
                        $imageheight = 400;
                        $imagewidth *= $scale;
                    }
		            $imgsrc = $CFG->wwwroot.'/file.php?file=/'.$question->image.'&t='.time();
		            echo '<center><image src="'.$imgsrc.'" style="border: 1px solid black;" width="'.$imagewidth.'px" height="'.$imageheight.'px" /></center>';
	            }
	        }
		}
        echo '</td></tr>';
        */
        echo '<tr valign="top">
		<td align="right"><b>'.get_string('questiontext','realtimequiz').'</b></td>
		<td align="left">';		
		echo '<textarea name="questiontext" rows="5" cols="50">'.$question->questiontext.'</textarea><br style="clear:both;" /></td>
		</tr><tr>
		<td align="right"><b>'.get_string('editquestiontime','realtimequiz').': </b></td>
		<td align="left"><input type="text" name="questiontime" size="30" value="'.$question->questiontime.'" /></td>
		</tr><tr>
		<td align="right"><b>'.get_string('questionimage','realtimequiz').'</b></td>
		<td align="left">
        <input type="hidden" value="'.$maxfilesize.'" name="MAX_FILE_SIZE" />
        <input type="file" name="imagefile" />';
        if ($question->image) {
            echo '<br/><input type="submit" name="removeimage" value="'.get_string('removeimage','realtimequiz').'" />';
        }
        echo '</td></tr>';

		while (count($answers) < $minanswers) {
			$extraanswer = new stdClass();
			$extraanswer->id = 0;
			$extraanswer->answertext = '';
			$extraanswer->correct = (count($answers) == 0); // Select first item, if it is the only item
			$answers[] = $extraanswer;
		}

        if (count($answers) > $minanswers) {
            $minanswers = count($answers);
        }
			
		$answernum = 1;
		foreach ($answers as $answer) {
            if ($answernum == 1) {
                echo '<tr id="realtimequiz_first_answer">';
            } else {
                echo '<tr>';
            }
            echo '<td align="right"><label for="realtimequiz_answerradio'.$answernum.'" > <b>'.get_string('answer','realtimequiz').$answernum.': </b></label></td>';
            echo '<td align="left">';
            echo '<input type="radio" name="answercorrect" alt="'.get_string('choosecorrect','realtimequiz').'" value="'.$answernum.'" class="realtimequiz_answerradio" id="realtimequiz_answerradio'.$answernum.'" onclick="highlight_correct();" ';
            echo $answer->correct ? 'checked="checked" ' : '';
            echo '/><input type="text" name="answertext['.$answernum.']" size="30" value="'.$answer->answertext.'" />';
			echo '<input type="hidden" name="answerid['.$answernum.']" value="'.$answer->id.'" />';
            echo '</td>';
            echo '</tr>';

			$answernum++;
		}
		
		echo '</table>';
		echo '<input type="hidden" name="action" value="'.$action.'" />';
		echo '<input type="hidden" name="questionid" value="'.$question->id.'" />';
		echo '<input type="hidden" name="questionnum" value="'.$question->questionnum.'" />';
		echo '<input type="hidden" name="minanswers" value="'.$minanswers.'" />';
		echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
		echo '<input type="submit" name="addanswers" value="'.get_string('addanswers', 'realtimequiz').'" onclick="add_answers(3); return false;" />';
		echo '<p>';
        echo '<input type="submit" name="updatequestion" value="'.get_string('updatequestion', 'realtimequiz').'" />&nbsp;';
        echo '<input type="submit" name="saveadd" value="'.get_string('saveadd', 'realtimequiz').'" />&nbsp;';
        echo '<input type="submit" name="cancel" value="'.get_string('cancel').'" />';
        echo '</p>';
        echo '</form></center>';
        echo '<script type="text/javascript">highlight_correct();</script>';
	}
	
	function realtimequiz_confirm_deletequestion($quizid, $questionid) {
        global $DB;

		echo '<center><h2>'.get_string('deletequestion', 'realtimequiz').'</h2>';
		echo '<p>'.get_string('checkdelete','realtimequiz').'</p><p>"';
		$question = $DB->get_record('realtimequiz_question', array('id' => $questionid));
		echo $question->questiontext;
		echo '"</p>';
		
        $url = new moodle_url('/mod/realtimequiz/edit.php',array('quizid'=>$quizid));
		echo '<form method="post" action="'.$url.'">';
		echo '<input type="hidden" name="action" value="dodeletequestion" />';
		echo '<input type="hidden" name="questionid" value="'.$questionid.'" />';
		echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
		echo '<input type="submit" name="yes" value="'.get_string('yes','realtimequiz').'" /> ';
		echo '<input type="submit" name="no" value="'.get_string('no','realtimequiz').'" />';
		echo '</form></center>';
	}

    $PAGE->requires->yui2_lib('yahoo');
    $PAGE->requires->yui2_lib('dom');
    $PAGE->requires->js('/mod/realtimequiz/editquestions.js');
	
	// Back to the main code
    $strrealtimequizzes = get_string("modulenameplural", "realtimequiz");
    $strrealtimequiz  = get_string("modulename", "realtimequiz");

    $PAGE->set_title(strip_tags($course->shortname.': '.$strrealtimequiz.': '.format_string($quiz->name,true)));
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
   
    realtimequiz_view_tabs('edit', $cm->id, $context);

    echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter realtimequizbox');
				  
	if (($action == 'doaddquestion') || ($action == 'doeditquestion')) {
	
		if (!confirm_sesskey()) {
			error(get_string('badsesskey', 'realtimequiz'));
		}
	
		if ($addanswers) {
			$minanswers = optional_param('minanswers', 4, PARAM_INT);
			realtimequiz_edit_question($quizid, $course->maxbytes, $questionid, $minanswers + 3);
        } elseif ($removeimage) {
            /* FIXME
            if ($action == 'doeditquestion') {
                $q = $DB->get_record('realtimequiz_question', array('id' => $questionid));
                if ($q && $q->image) {
                    $fullpath = $CFG->dataroot.'/'.$q->image;
                    if (file_exists($fullpath)) {
                        unlink($fullpath);
                    }
                    $question = new stdClass;
                    $question->id = $questionid;
                    $question->image = ''; 
                    update_record('realtimequiz_question', $question);
                }
                }*/
			$minanswers = optional_param('minanswers', 4, PARAM_INT);
            realtimequiz_edit_question($quizid, $course->maxbytes, $questionid, $minanswers);

        } elseif ($canceledit) {
            $action = 'listquestions';
			
		} else {
				
			$question = new stdClass();
			$question->quizid = $quizid;
			$question->questionnum = required_param('questionnum', PARAM_INT);
			$question->questiontext = required_param('questiontext', PARAM_TEXT);
			$question->questiontime = required_param('questiontime', PARAM_INT);
			$answertexts = required_param('answertext', PARAM_TEXT);
			$answercorrect = optional_param('answercorrect', false, PARAM_INT);
			$answerids = required_param('answerid', PARAM_INT);

			// Copy the answers into a suitable array and count how many (valid) correct answers there are
			$correctcount = 0;
			if ($answercorrect !== false) {
				$answers = array();
				$answercount = count($answertexts);
				for ($i=1; $i<=$answercount; $i++) {
					$answers[$i] = new stdClass();
					$answers[$i]->id = $answerids[$i];
					$answers[$i]->answertext = $answertexts[$i];
					$answers[$i]->correct = ($answercorrect == $i) ? 1 : 0; // FIX IN CVS
					if ($answers[$i]->correct == 1 && $answers[$i]->answertext != '') {
						$correctcount++;
					}
				}
			}

			// Check there is exactly 1 correct answer
			if ($question->questiontext == '') {
				echo '<div class="errorbox">';
				print_string('errorquestiontext','realtimequiz');
				echo '</div>';
				$minanswers = optional_param('minanswers', 4, PARAM_INT);
				realtimequiz_edit_question($quizid, $course->maxbytes, $questionid, $minanswers);
				
			} else if ($correctcount != 1) {
				echo '<div class="errorbox">';
				print_string('onecorrect','realtimequiz');
				echo '</div>';
				$minanswers = optional_param('minanswers', 4, PARAM_INT);
				realtimequiz_edit_question($quizid, $course->maxbytes, $questionid, $minanswers);
				
			} else {
				// Update the question
				if ($action == 'doaddquestion') {
					$question->id = $DB->insert_record('realtimequiz_question', $question);
				} else {
					$question->id = $questionid;
					$DB->update_record('realtimequiz_question', $question);
				}
						
                /* FIXME
                // Upload the image
                $dir = $course->id.'/'.$CFG->moddata.'/realtimequiz/'.$question->quizid;
                $fulldir = $CFG->dataroot.'/'.$dir;

                require_once($CFG->dirroot.'/lib/uploadlib.php');
                $um = new upload_manager('imagefile',false,true,$course,false,$course->maxbytes,true);

                if ($um->process_file_uploads($fulldir)) {
                    $fp = $um->get_new_filepath();
                    $fn = $um->get_new_filename();

                    if ($fp && $fn) {
                        $size = getimagesize($fp);
                        if ($size) {
                            if ($size[2] == IMAGETYPE_GIF) { $fext = '.gif'; }
                            else if ($size[2] == IMAGETYPE_PNG) { $fext = '.png'; }
                            else if ($size[2] == IMAGETYPE_JPEG) { $fext = '.jpg'; }
                            else { $fext = false; }
                                
                            if ($fext) {
                                $q = get_record('realtimequiz_question', 'id', $questionid);
                                if ($q && $q->image) {
                                    if (pathinfo($q->image, PATHINFO_EXTENSION) != $fext) {
                                        if (file_exists($CFG->dataroot.'/'.$q->image)) {
                                            unlink($CFG->dataroot.'/'.$q->image);
                                            // Delete the old file, if it was a different type
                                            // (it will get overwritten below, if it is the same type)
                                        }
                                    }
                                }
                                
                                $destname = sprintf('%02d',$question->id).$fext;
                                $dest = $fulldir.'/'.$destname;
                                rename($fp, $dest);

                                $question->image = $dir.'/'.$destname;
                                update_record('realtimequiz_question', $question);
                            }
                        }
                    }
                }    
                */     
				
				// Update the answers
				foreach ($answers as $answer) {
					$answer->questionid = $question->id;
				
					if ($answer->id == 0) {	// A new answer to add to the database
						if ($answer->answertext != '') { // Only add it if there is some text there
							$DB->insert_record('realtimequiz_answer', $answer, false);
						}
					} else {
						if ($answer->answertext == '') { // Empty answer = remove it
						    $DB->delete_records('realtimequiz_submitted', array('answerid' => $answer->id)); // Delete any submissions for that answer
							$DB->delete_records('realtimequiz_answer', array('id' => $answer->id));
							
						} else { // Update the answer
							$DB->update_record('realtimequiz_answer', $answer);
						}
					}
				}

                if ($saveadd) {
                    $action = 'addquestion';
                } else {
                    $action = 'listquestions';
                }
			}		
		}
	
	} elseif ($action == 'dodeletequestion') {
	
		if (!confirm_sesskey()) {
			error(get_string('badsesskey','realtimequiz'));
		}  

		if (optional_param('yes', false, PARAM_BOOL)) {
		    $answers = $DB->get_records('realtimequiz_answer', array('questionid' => $questionid));
			if (!empty($answers)) {
			    foreach ($answers as $answer) { // Get each answer for that question
				    $DB->delete_records('realtimequiz_submitted', array('answerid' => $answer->id)); // Delete any submissions for that answer
			    }
			}
		    $DB->delete_records('realtimequiz_answer', array('questionid' => $questionid)); // Delete each answer
			$DB->delete_records('realtimequiz_question', array('id' => $questionid));
			// Questionnumbers sorted out when we display the list of questions
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
	
	case 'listquestions':	//Show all the currently available questions
		realtimequiz_list_questions($quizid, $cm);
		break;
	
	case 'addquestion':	// Adding a new question
		realtimequiz_edit_question($quizid, $course->maxbytes);
		break;
	
	case 'editquestion': // Editing the question
		realtimequiz_edit_question($quizid, $course->maxbytes, $questionid);
		break;
		
	case 'deletequestion': // Deleting a question - ask 'Are you sure?'
		realtimequiz_confirm_deletequestion($quizid, $questionid);
		break;
		
	}

    echo $OUTPUT->box_end();
	
/// Finish the page
    echo $OUTPUT->footer();

?>

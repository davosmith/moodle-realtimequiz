<?php 
    //Based on php script for restoring forum mods
    //This php script contains all the stuff to backup/restore
    //realtimequiz mods

    //This is the "graphical" structure of the realtimequiz mod:
    //
    //                            realtimequiz                                      
    //                            (CL,pk->id)
    //                                 |
    //         ---------------------------------------------------        
    //         |                                                 |
    //    realtimequiz_session                          realtimequiz_question
    //    (UL,pk->id, fk->quizid)                      (UL,pk->id, fk->quizid)
    //                                                           |
    //                                                           |
    //                                                           |
    //                                                   realtimequiz_answer
    //                                                (UL,pk->id,fk->questionid) 
    //                                                           |
    //                                                           |
    //                                                           |
    //                                                realtimequiz_submitted
    //                            (UL,pk->id,fk->questionid,fk->sessionid, fk->userid, fk->answerid)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          CL->course level info
    //          UL->user level info
 //
    //-----------------------------------------------------------

    function realtimequiz_restore_mods($mod,$restore) {
        
        global $CFG,$db;
		
        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //if necessary, write to restorelog and adjust date/time fields
            if ($restore->course_startdateoffset) {
                restore_log_date_changes('Realtimequiz', $restore, $info['MOD']['#'], array('ASSESSTIMESTART', 'ASSESSTIMEFINISH'));
            }
            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //Now, build the REALTIMEQUIZ record structure
            $quiz->course = $restore->course_id;
            $quiz->type = backup_todb($info['MOD']['#']['TYPE']['0']['#']);
            $quiz->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $quiz->questiontime = backup_todb($info['MOD']['#']['QUESTIONTIME']['0']['#']);
             
            $newid = insert_record("realtimequiz", $quiz);


            //Do some output
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","realtimequiz")." \"".format_string(stripslashes($quiz->name),true)."\"</li>";
            }
            backup_flush(300);
			
            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);

                $quiz->id = $newid;
                
                $restore_user = false;

                //Now check if want to restore user data and do it.
                if (restore_userdata_selected($restore,'realtimequiz',$mod->id)) {
                    $status = realtimequiz_sessions_restore($newid,$info,$restore);
                    $restore_user = true;
                }

                // Restore the questions and answers (and any submissions)
                if ($status) $status = realtimequiz_questions_restore($newid,$info,$restore,$restore_user);
            
            } else {
                $status = false;
            }
			
            
        } else {
            $status = false;
        }

        return $status;
    }
    
    function realtimequiz_questions_restore($quiz_id,$info,$restore,$restore_user) {
    
        global $CFG;

        $status = true;

        //Get the discussions array
        $questions = array();
        
        if (!empty($info['MOD']['#']['QUESTIONS']['0']['#']['QUESTION'])) {
            $questions = $info['MOD']['#']['QUESTIONS']['0']['#']['QUESTION'];
        }
        
        //Iterate over discussions
        for($i = 0; $i < sizeof($questions); $i++) {
            $q_info = $questions[$i];
            //traverse_xmlize($dis_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($q_info['#']['ID']['0']['#']);

            //Now, build the REALTIMEQUIZ_QUESTION record structure
            $question->quizid = $quiz_id;
            $question->questionnum = backup_todb($q_info['#']['QUESTIONNUM']['0']['#']);
            $question->questiontext = backup_todb($q_info['#']['QUESTIONTEXT']['0']['#']);
            $question->questiontime = backup_todb($q_info['#']['QUESTIONTIME']['0']['#']);

            //The structure is equal to the db, so insert the forum_discussions
            $newid = insert_record ("realtimequiz_question",$question);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"realtimequiz_question",$oldid,
                             $newid);
                //Restore answers
                $status = realtimequiz_answers_restore ($newid,$q_info,$restore,$restore_user);
                
            } else {
                $status = false;
            }
        }

        return $status;
    
    
    }

    //This function restores the realtimequiz_answers
    function realtimequiz_answers_restore($question_id,$info,$restore,$restore_user) {

        global $CFG;

        $status = true;

        //Get the answers array
	    $answers = array();
        if (!empty($info['#']['ANSWERS']['0']['#']['ANSWER'])) {
	        $answers = $info['#']['ANSWERS']['0']['#']['ANSWER'];
        }

        //Iterate over answers
        for($i = 0; $i < sizeof($answers); $i++) {
            $a_info = $answers[$i];
            //traverse_xmlize($pos_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                             //Debug

            //We'll need this later!!
            $oldid = backup_todb($a_info['#']['ID']['0']['#']);

            //Now, build the REALTIMEQUIZ_ANSWER record structure
            $answer->questionid = $question_id;
            $answer->answertext = backup_todb($a_info['#']['ANSWERTEXT']['0']['#']);
            $answer->correct = backup_todb($a_info['#']['CORRECT']['0']['#']);   

            //The structure is equal to the db, so insert the realtimequiz_answer
            $newid = insert_record ("realtimequiz_answer",$answer);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"realtimequiz_answer",$oldid,$newid);
                             
                if ($restore_user) {
                    $status = realtimequiz_submissions_restore($question_id, $newid, $a_info, $restore);
                }

            } else {
                $status = false;
            }
        }

        return $status;
    }
    
    function realtimequiz_submissions_restore($questionid, $answerid, $info, $restore) {
        global $CFG;

        $status = true;
		
        //Get the discussions array
        $submissions = array();
        if (!empty($info['#']['SUBMISSIONS']['0']['#']['SUBMISSION'])) {
	        $submissions = $info['#']['SUBMISSIONS']['0']['#']['SUBMISSION'];
        }
		
        //Iterate over answers
        for($i = 0; $i < sizeof($submissions); $i++) {
            $s_info = $submissions[$i];
            //traverse_xmlize($pos_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($s_info['#']['ID']['0']['#']);

            //Now, build the REALTIMEQUIZ_ANSWER record structure
            $submission->questionid = $questionid;
            $submission->answerid = $answerid;
            $submission->userid = backup_todb($s_info['#']['USERID']['0']['#']);
            $submission->sessionid = backup_todb($s_info['#']['SESSIONID']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$submission->userid);
            if ($user) {
                $submission->userid = $user->new_id;
            }

            //We have to recode the sessionid field
            $session = backup_getid($restore->backup_unique_code,"realtimequiz_session",$submission->sessionid);
            if ($session) {
                $submission->sessionid = $session->new_id;
            }
			
            //The structure is equal to the db, so insert the realtimequiz_submission
            $newid = insert_record ("realtimequiz_submitted",$submission);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"realtimequiz_submitted",$oldid,
                             $newid);
            } else {
                $status = false;
            }

        }

        return $status;
    }

    function realtimequiz_sessions_restore($quizid,$info,$restore) {
    
        global $CFG;

        $status = true;
		
        //Get the discussions array
        $sessions = array();
        if (!empty($info['MOD']['#']['SESSIONS']['0']['#']['SESSION'])) {
            $sessions = $info['MOD']['#']['SESSIONS']['0']['#']['SESSION'];
        }
        
        //Iterate over discussions
        for($i = 0; $i < sizeof($sessions); $i++) {
            $s_info = $sessions[$i];
            //traverse_xmlize($dis_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($s_info['#']['ID']['0']['#']);

            //Now, build the REALTIMEQUIZ_SESSION record structure
            $session->quizid = $quizid;
            $session->name = backup_todb($s_info['#']['NAME']['0']['#']);
            $session->timestamp = backup_todb($s_info['#']['TIMESTAMP']['0']['#']);
			
            //The structure is equal to the db, so insert the realtimequiz_session
            $newid = insert_record ("realtimequiz_session",$session);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"realtimequiz_session",$oldid,
                             $newid);

            } else {
                $status = false;
            }
        }

        return $status;
    
     }
?>

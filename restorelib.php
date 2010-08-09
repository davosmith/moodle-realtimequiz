<?php //$Id: restorelib.php,v 1.60 2006/12/19 07:00:14 vyshane Exp $
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
            $question->questiontime = backup_todb($dis_info['#']['QUESTIONTIME']['0']['#']);

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

        //Get the posts array
        $answers = $info['#']['ANSWERS']['0']['#']['ANSWER'];

        //Iterate over posts
        for($i = 0; $i < sizeof($answers); $i++) {
            $a_info = $answers[$i];
            //traverse_xmlize($pos_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

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
                backup_putid($restore->backup_unique_code,"realtimequiz_answer",$oldid,
                             $newid);
                             
                if ($restore_user) {
                    $status = realtimequiz_submissions_restore($question_id, $newid, $info, $restore);
                }

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

            //Now, build the REALTIMEQUIZ_QUESTION record structure
            $session->quizid = $quiz_id;
            $session->name = backup_todb($q_info['#']['NAME']['0']['#']);
            $session->timestamp = backup_todb($q_info['#']['TIMESTAMP']['0']['#']);

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
     
     function realtimequiz_submissions_restore($questionid, $answerid, $info, $restore) {
     //FIXME: Write this function
        global $CFG;

        $status = true;

        //Get the discussions array
        $submissions = array();
        
  /*
  CREATE TABLE mdl_realtimequiz_submitted (
    id BIGINT(10) NOT NULL auto_increment,
    questionid BIGINT(10) unsigned NOT NULL DEFAULT 0,  - passed in
    sessionid BIGINT(10) unsigned DEFAULT 0,    - look up 
    userid BIGINT(10) unsigned NOT NULL DEFAULT 0,  - look up
    answerid BIGINT(10) unsigned NOT NULL DEFAULT 0,    - passed in
CONSTRAINT  PRIMARY KEY (id)
);

  */   
     
        return $status;
     }

/*
    //This function restores the forum_subscriptions
    function forum_subscriptions_restore_mods($forum_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the discussions array
        $subscriptions = array();
        if (isset($info['MOD']['#']['SUBSCRIPTIONS']['0']['#']['SUBSCRIPTION'])) {
            $subscriptions = $info['MOD']['#']['SUBSCRIPTIONS']['0']['#']['SUBSCRIPTION'];
        }

        //Iterate over subscriptions
        for($i = 0; $i < sizeof($subscriptions); $i++) {
            $sus_info = $subscriptions[$i];
            //traverse_xmlize($sus_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($sus_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($sus_info['#']['USERID']['0']['#']);

            //Now, build the FORUM_SUBSCRIPTIONS record structure
            $subscription->forum = $forum_id;
            $subscription->userid = backup_todb($sus_info['#']['USERID']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$subscription->userid);
            if ($user) {
                $subscription->userid = $user->new_id;
            }

            //The structure is equal to the db, so insert the forum_subscription
            $newid = insert_record ("forum_subscriptions",$subscription);

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
                backup_putid($restore->backup_unique_code,"forum_subscriptions",$oldid,
                             $newid);
            } else {
                $status = false;
            }
        }

        return $status;
    }

    //This function restores the forum_discussions
    function forum_discussions_restore_mods($forum_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the discussions array
        $discussions = array();
        
        if (!empty($info['MOD']['#']['DISCUSSIONS']['0']['#']['DISCUSSION'])) {
            $discussions = $info['MOD']['#']['DISCUSSIONS']['0']['#']['DISCUSSION'];
        }

        //Iterate over discussions
        for($i = 0; $i < sizeof($discussions); $i++) {
            $dis_info = $discussions[$i];
            //traverse_xmlize($dis_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($dis_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($dis_info['#']['USERID']['0']['#']);

            //Now, build the FORUM_DISCUSSIONS record structure
            $discussion->forum = $forum_id;
            $discussion->course = $restore->course_id;
            $discussion->name = backup_todb($dis_info['#']['NAME']['0']['#']);
            $discussion->firstpost = backup_todb($dis_info['#']['FIRSTPOST']['0']['#']);
            $discussion->userid = backup_todb($dis_info['#']['USERID']['0']['#']);
            $discussion->groupid = backup_todb($dis_info['#']['GROUPID']['0']['#']);
            $discussion->assessed = backup_todb($dis_info['#']['ASSESSED']['0']['#']);
            $discussion->timemodified = backup_todb($dis_info['#']['TIMEMODIFIED']['0']['#']);
            $discussion->timemodified += $restore->course_startdateoffset;
            $discussion->usermodified = backup_todb($dis_info['#']['USERMODIFIED']['0']['#']);  
            $discussion->timestart = backup_todb($dis_info['#']['TIMESTART']['0']['#']);
            $discussion->timestart += $restore->course_startdateoffset;
            $discussion->timeend = backup_todb($dis_info['#']['TIMEEND']['0']['#']);
            $discussion->timeend += $restore->course_startdateoffset;
            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$discussion->userid);
            if ($user) {
                $discussion->userid = $user->new_id;
            }

            //We have to recode the groupid field
            $group = backup_getid($restore->backup_unique_code, 'groups', $discussion->groupid);
            if ($group) {
                $discussion->groupid = $group->new_id;
            }

            //We have to recode the usermodified field
            $user = backup_getid($restore->backup_unique_code,"user",$discussion->usermodified);
            if ($user) {
                $discussion->usermodified = $user->new_id;
            }

            //The structure is equal to the db, so insert the forum_discussions
            $newid = insert_record ("forum_discussions",$discussion);

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
                backup_putid($restore->backup_unique_code,"forum_discussions",$oldid,
                             $newid);
                //Restore forum_posts
                $status = forum_posts_restore_mods ($forum_id,$newid,$dis_info,$restore);
                //Now recalculate firstpost field
                $old_firstpost = $discussion->firstpost;
                //Get its new post_id from backup_ids table
                $rec = backup_getid($restore->backup_unique_code,"forum_posts",$old_firstpost);
                if ($rec) {
                    //Put its new firstpost
                    $discussion->firstpost = $rec->new_id;
                    if ($post = get_record("forum_posts", "id", $discussion->firstpost)) {
                        $discussion->userid = $post->userid;
                    }
                } else {
                     $discussion->firstpost = 0;
                     $discussion->userid = 0;
                }
                //Create temp discussion record
                $temp_discussion->id = $newid;
                $temp_discussion->firstpost = $discussion->firstpost;
                $temp_discussion->userid = $discussion->userid;
                //Update discussion (only firstpost and userid will be changed)
                $status = update_record("forum_discussions",$temp_discussion);
                //echo "Updated firstpost ".$old_firstpost." to ".$temp_discussion->firstpost."<br />";                //Debug
            } else {
                $status = false;
            }
        }

        return $status;
    }

    //This function restores the forum_read
    function forum_read_restore_mods($forum_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the read array
        $readposts = array();
        if (isset($info['MOD']['#']['READPOSTS']['0']['#']['READ'])) {
            $readposts = $info['MOD']['#']['READPOSTS']['0']['#']['READ'];
        }

        //Iterate over readposts
        for($i = 0; $i < sizeof($readposts); $i++) {
            $rea_info = $readposts[$i];
            //traverse_xmlize($rea_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($rea_info['#']['ID']['0']['#']);

            //Now, build the FORUM_READ record structure
            $read->forumid = $forum_id;
            $read->userid = backup_todb($rea_info['#']['USERID']['0']['#']);
            $read->discussionid = backup_todb($rea_info['#']['DISCUSSIONID']['0']['#']);
            $read->postid = backup_todb($rea_info['#']['POSTID']['0']['#']);
            $read->firstread = backup_todb($rea_info['#']['FIRSTREAD']['0']['#']);
            $read->lastread = backup_todb($rea_info['#']['LASTREAD']['0']['#']);

            //Some recoding and check are performed now
            $toinsert = true;

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$read->userid);
            if ($user) {
                $read->userid = $user->new_id;
            } else {
                $toinsert = false;
            }

            //We have to recode the discussionid field
            $discussion = backup_getid($restore->backup_unique_code,"forum_discussions",$read->discussionid);
            if ($discussion) {
                $read->discussionid = $discussion->new_id;
            } else {
                $toinsert = false;
            }

            //We have to recode the postid field
            $post = backup_getid($restore->backup_unique_code,"forum_posts",$read->postid);
            if ($post) {
                $read->postid = $post->new_id;
            } else {
                $toinsert = false;
            }

            //The structure is equal to the db, so insert the forum_read
            $newid = 0;
            if ($toinsert) {
                $newid = insert_record ("forum_read",$read);
            }

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
                backup_putid($restore->backup_unique_code,"forum_read",$oldid,
                             $newid);
            } else {
                $status = false;
            }
        }

        return $status;
    }

    //This function restores the forum_posts
    function forum_posts_restore_mods($new_forum_id,$discussion_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the posts array
        $posts = $info['#']['POSTS']['0']['#']['POST'];

        //Iterate over posts
        for($i = 0; $i < sizeof($posts); $i++) {
            $pos_info = $posts[$i];
            //traverse_xmlize($pos_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($pos_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($pos_info['#']['USERID']['0']['#']);

            //Now, build the FORUM_POSTS record structure
            $post->discussion = $discussion_id;
            $post->parent = backup_todb($pos_info['#']['PARENT']['0']['#']);
            $post->userid = backup_todb($pos_info['#']['USERID']['0']['#']);   
            $post->created = backup_todb($pos_info['#']['CREATED']['0']['#']);
            $post->created += $restore->course_startdateoffset;
            $post->modified = backup_todb($pos_info['#']['MODIFIED']['0']['#']);
            $post->modified += $restore->course_startdateoffset;             
            $post->mailed = backup_todb($pos_info['#']['MAILED']['0']['#']);
            $post->subject = backup_todb($pos_info['#']['SUBJECT']['0']['#']);
            $post->message = backup_todb($pos_info['#']['MESSAGE']['0']['#']);
            $post->format = backup_todb($pos_info['#']['FORMAT']['0']['#']);
            $post->attachment = backup_todb($pos_info['#']['ATTACHMENT']['0']['#']);
            $post->totalscore = backup_todb($pos_info['#']['TOTALSCORE']['0']['#']);
            $post->mailnow = backup_todb($pos_info['#']['MAILNOW']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$post->userid);
            if ($user) {
                $post->userid = $user->new_id;
            }

            //The structure is equal to the db, so insert the forum_posts
            $newid = insert_record ("forum_posts",$post);

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
                backup_putid($restore->backup_unique_code,"forum_posts",$oldid,
                             $newid);

                //Get old forum id from backup_ids
                $rec = get_record("backup_ids","backup_code",$restore->backup_unique_code,
                                               "table_name","forum",
                                               "new_id",$new_forum_id);
                //Now copy moddata associated files
                $status = forum_restore_files ($rec->old_id, $new_forum_id,
                                                    $oldid, $newid, $restore);

                //Now restore post ratings
                $status = forum_ratings_restore_mods($newid,$pos_info,$restore);

            } else {
                $status = false;
            }
        }

        //Now we get every post in this discussion_id and recalculate its parent post
        $posts = get_records ("forum_posts","discussion",$discussion_id);
        if ($posts) {
            //Iterate over each post
            foreach ($posts as $post) {
                //Get its parent
                $old_parent = $post->parent;
                //Get its new post_id from backup_ids table
                $rec = backup_getid($restore->backup_unique_code,"forum_posts",$old_parent);
                if ($rec) {
                    //Put its new parent
                    $post->parent = $rec->new_id;
                } else {
                     $post->parent = 0;
                }
                //Create temp post record
                $temp_post->id = $post->id;
                $temp_post->parent = $post->parent;
                //echo "Updated parent ".$old_parent." to ".$temp_post->parent."<br />";                //Debug
                //Update post (only parent will be changed)
                $status = update_record("forum_posts",$temp_post);
            }
        }

        return $status;
    }

    //This function restores the forum_ratings
    function forum_ratings_restore_mods($new_post_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the ratings array
        $ratings = array();
        if (isset($info['#']['RATINGS']['0']['#']['RATING'])) {
            $ratings = $info['#']['RATINGS']['0']['#']['RATING'];
        }

        //Iterate over ratings
        for($i = 0; $i < sizeof($ratings); $i++) {
            $rat_info = $ratings[$i];
            //traverse_xmlize($rat_info);                                                                 //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug

            //We'll need this later!!
            $oldid = backup_todb($rat_info['#']['ID']['0']['#']);
            $olduserid = backup_todb($rat_info['#']['USERID']['0']['#']);

            //Now, build the FORM_RATINGS record structure
            $rating->post = $new_post_id;
            $rating->userid = backup_todb($rat_info['#']['USERID']['0']['#']);
            $rating->time = backup_todb($rat_info['#']['TIME']['0']['#']);
            $rating->rating = backup_todb($rat_info['#']['POST_RATING']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$rating->userid);
            if ($user) {
                $rating->userid = $user->new_id;
            }

            //The structure is equal to the db, so insert the forum_ratings
            $newid = insert_record ("forum_ratings",$rating);

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
                backup_putid($restore->backup_unique_code,"forum_ratings",$oldid,
                             $newid);
            } else {
                $status = false;
            }
        }

        return $status;
    }


*/
?>

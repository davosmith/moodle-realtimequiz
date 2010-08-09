<?php 
    //Based on php script for backing up forum mods
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

    function realtimequiz_backup_mods($bf,$preferences) {
       
        global $CFG;

        $status = true;

        //Iterate over forum table
        $quizzes = get_records ("realtimequiz","course",$preferences->backup_course,"id");
        if ($quizzes) {
            foreach ($quizzes as $quiz) {
                if (backup_mod_selected($preferences,'realtimequiz',$quiz->id)) {
                    $status = realtimequiz_backup_one_mod($bf,$preferences,$quiz);
                }
            }
        }
        return $status;
    }


    function realtimequiz_backup_one_mod($bf,$preferences,$quiz) {
    
        global $CFG;
        
        if (is_numeric($quiz)) {
            $quiz = get_record('realtimequiz','id',$quiz);
        }
        $instanceid = $quiz->id;
        
        $status = true;
        
        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        
        //Print realtimequiz data
		
        fwrite ($bf,full_tag("ID",4,false,$quiz->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"realtimequiz"));
        fwrite ($bf,full_tag("NAME",4,false,$quiz->name));
        fwrite ($bf,full_tag("QUESTIONTIME",4,false,$quiz->questiontime));
/*  
        fwrite ($bf,full_tag("STATUS",4,false,0));
        fwrite ($bf,full_tag("CURRENTQUESTION",4,false,0));
        fwrite ($bf,full_tag("NEXTENDTIME",4,false,0));
        fwrite ($bf,full_tag("CURRENTSESSIONID",4,false,0));
        fwrite ($bf,full_tag("CLASSRESULT",4,false,0));
        fwrite ($bf,full_tag("QUESTIONRESULT",4,false,0));
*/		
	    if (backup_userdata_selected($preferences,'realtimequiz',$quiz->id)) {
	        // Back up sessions
	        $status = backup_realtimequiz_sessions($bf,$preferences,$quiz->id);
	    }
		if ($status) $status = backup_realtimequiz_questions($bf,$preferences,$quiz->id);

        $status =fwrite ($bf,end_tag("MOD",3,true));
        return $status;
    }


    function realtimequiz_check_backup_mods_instances($instance,$backup_unique_code) {
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';
        $info[$instance->id.'1'][0] = get_string("questions","realtimequiz");
        if ($ids = realtimequiz_question_ids_by_instance ($instance->id)) {
                $info[$instance->id.'1'][1] = count($ids);        
        } else {
                $info[$instance->id.'1'][1] = 0;
        }
        $info[$instance->id.'2'][0] = get_string("answers","realtimequiz");
        if ($ids = realtimequiz_answer_ids_by_instance ($instance->id)) {
                $info[$instance->id.'2'][1] = count($ids);        
        } else {
                $info[$instance->id.'2'][1] = 0;
        }
        if (!empty($instance->userdata)) {
            $info[$instance->id.'3'][0] = get_string("sessions","realtimequiz");
            if ($ids = realtimequiz_session_ids_by_instance ($instance->id)) {
                    $info[$instance->id.'3'][1] = count($ids);        
            } else {
                    $info[$instance->id.'3'][1] = 0;
            }
            $info[$instance->id.'4'][0] = get_string("submissions","realtimequiz");
            if ($ids = realtimequiz_submission_ids_by_instance ($instance->id)) {
                    $info[$instance->id.'4'][1] = count($ids);        
            } else {
                    $info[$instance->id.'4'][1] = 0;
            }   
        }
        
         return $info;
    }

	function backup_realtimequiz_questions($bf, $preferences,$quiz) {
		global $CFG;
		
		$status = true;
		
		$quiz_questions = get_records("realtimequiz_question","quizid",$quiz,"id");
		if ($quiz_questions) {
			$status = fwrite($bf, start_tag("QUESTIONS",4,true));
			foreach ($quiz_questions as $question) {
				if ($status) $status = fwrite($bf, start_tag("QUESTION", 5, true));
				if ($status) $status = fwrite ($bf, full_tag("ID",6,false,$question->id));
				if ($status) $status = fwrite ($bf, full_tag("QUIZID",6,false,$question->quizid));
				if ($status) $status = fwrite ($bf, full_tag("QUESTIONNUM",6,false,$question->questionnum));
				if ($status) $status = fwrite ($bf, full_tag("QUESTIONTEXT",6,false,$question->questiontext));
				if ($status) $status = fwrite ($bf, full_tag("QUESTIONTIME",6,false,$question->questiontime));
				
				if ($status) $status = backup_realtimequiz_answers($bf, $preferences, $question->id, $quiz);
				if ($status) $status = fwrite($bf, end_tag("QUESTION", 5, true));
			}
			if ($status) $status = fwrite($bf, end_tag("QUESTIONS",4,true));
		}
		
		return $status;
	}
	
	function backup_realtimequiz_answers($bf, $preferences, $question, $quiz) {
		global $CFG;
		
		$status = true;
		
		$quiz_answers = get_records("realtimequiz_answer","questionid",$question,"id");
		if ($quiz_answers) {
		    $status = fwrite($bf, start_tag("ANSWERS",7,true));
		    foreach ($quiz_answers as $answer) {
		        if ($status) $status = fwrite($bf, start_tag("ANSWER", 8, true));
		        if ($status) $status = fwrite($bf, full_tag("ID",9,false,$answer->id));
		        if ($status) $status = fwrite($bf, full_tag("QUESTIONID",9,false,$answer->questionid));
		        if ($status) $status = fwrite($bf, full_tag("ANSWERTEXT",9,false,$answer->answertext));
		        if ($status) $status = fwrite($bf, full_tag("CORRECT",9,false,$answer->correct));
		        
        	    if (backup_userdata_selected($preferences,'realtimequiz',$quiz)) {
        	        if ($status) $status = backup_realtimequiz_submitted($bf, $preferences, $answer->id);
        	    }

		        if ($status) $status = fwrite($bf, end_tag("ANSWER", 8, true));		        
		    }
		
		    if ($status) $status = fwrite($bf, end_tag("ANSWERS",7,true));
		}
				
		return $status;
	
	}
	
	function backup_realtimequiz_submitted($bf, $preferences, $answer) {
	    global $CFG;
	    
	    $status = true;
	    
		$quiz_submissions = get_records("realtimequiz_submitted","answerid",$answer,"id");
		if ($quiz_submissions) {
		    $status = fwrite($bf, start_tag("SUBMISSIONS",9,true));
		    foreach ($quiz_submissions as $submission) {
		        if ($status) $status = fwrite($bf, start_tag("SUBMISSION", 10, true));
		        if ($status) $status = fwrite($bf, full_tag("ID", 11, false, $submission->id));
		        if ($status) $status = fwrite($bf, full_tag("QUESTIONID", 11, false, $submission->questionid));
		        if ($status) $status = fwrite($bf, full_tag("SESSIONID", 11, false, $submission->sessionid));
		        if ($status) $status = fwrite($bf, full_tag("USERID", 11, false, $submission->userid));
		        if ($status) $status = fwrite($bf, full_tag("ANSWERID", 11, false, $submission->answerid));
		        if ($status) $status = fwrite($bf, end_tag("SUBMISSION", 10, true));
		    }
            if ($status) $status = fwrite($bf, end_tag("SUBMISSIONS", 9, true));		    
		}	    
	    
	    return $status;
	}

	function backup_realtimequiz_sessions($bf, $preferences, $quiz) {
		global $CFG;
		
		$status = true;
		
		$quiz_sessions = get_records("realtimequiz_session","quizid",$quiz,"id");
		if ($quiz_sessions) {
		    $status = fwrite($bf, start_tag("SESSIONS",4,true));
		    foreach ($quiz_sessions as $session) {
		        if ($status) $status = fwrite($bf, start_tag("SESSION", 5, true));
		        if ($status) $status = fwrite($bf, full_tag("ID", 6, false, $session->id));
		        if ($status) $status = fwrite($bf, full_tag("QUIZID", 6, false, $session->quizid));
		        if ($status) $status = fwrite($bf, full_tag("NAME", 6, false, $session->name));
		        if ($status) $status = fwrite($bf, full_tag("TIMESTAMP", 6, false, $session->timestamp));
		        if ($status) $status = fwrite($bf, end_tag("SESSION", 5, true));
		    }
            if ($status) $status = fwrite($bf, end_tag("SESSIONS", 4, true));		    
		}
		
		return $status;
	}
	
   ////Return an array of info (name,value)
   function realtimequiz_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
       
       if (!empty($instances) && is_array($instances) && count($instances)) {
           $info = array();
           foreach ($instances as $id => $instance) {
               $info += realtimequiz_check_backup_mods_instances($instance,$backup_unique_code);
           }
           return $info;
       }
        //First the course data
        $info[0][0] = get_string("modulenameplural","realtimequiz");
        if ($ids = realtimequiz_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }
        
        $info[1][0] = get_string("questions","realtimequiz");
        if ($ids = realtimequiz_question_ids_by_course($course)) {
            $info[1][1] = count($ids);
        } else {
            $info[1][1] = 0;
        }

        $info[2][0] = get_string("answers","realtimequiz");
        if ($ids = realtimequiz_answer_ids_by_course($course)) {
            $info[2][1] = count($ids);
        } else {
            $info[2][1] = 0;
        }

        if ($user_data) {
            $info[3][0] = get_string("sessions","realtimequiz");
            if ($ids = realtimequiz_session_ids_by_course($course)) {
                $info[3][1] = count($ids);
            } else {
                $info[3][1] = 0;
            }

            $info[4][0] = get_string("submissions","realtimequiz");
            if ($ids = realtimequiz_submission_ids_by_course($course)) {
                $info[4][1] = count($ids);
            } else {
                $info[4][1] = 0;
            }
        }

        return $info;
    }

/*
    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function forum_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of forums
        $buscar="/(".$base."\/mod\/forum\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@FORUMINDEX*$2@$',$content);

        //Link to forum view by moduleid
        $buscar="/(".$base."\/mod\/forum\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@FORUMVIEWBYID*$2@$',$result);

        //Link to forum view by forumid
        $buscar="/(".$base."\/mod\/forum\/view.php\?f\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@FORUMVIEWBYF*$2@$',$result);

        //Link to forum discussion with parent syntax
        $buscar="/(".$base."\/mod\/forum\/discuss.php\?d\=)([0-9]+)\&parent\=([0-9]+)/";
        $result= preg_replace($buscar,'$@FORUMDISCUSSIONVIEWPARENT*$2*$3@$',$result);

        //Link to forum discussion with relative syntax
        $buscar="/(".$base."\/mod\/forum\/discuss.php\?d\=)([0-9]+)\#([0-9]+)/";
        $result= preg_replace($buscar,'$@FORUMDISCUSSIONVIEWINSIDE*$2*$3@$',$result);

        //Link to forum discussion by discussionid
        $buscar="/(".$base."\/mod\/forum\/discuss.php\?d\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@FORUMDISCUSSIONVIEW*$2@$',$result);

        return $result;
    }
*/
    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of realtimequiz id
    function realtimequiz_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}realtimequiz a
                                 WHERE a.course = '$course'");
    }

    function realtimequiz_question_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT q.id, q.quizid      
                                 FROM {$CFG->prefix}realtimequiz_question q,    
                                      {$CFG->prefix}realtimequiz r 
                                 WHERE r.course = '$course' AND
                                       q.quizid = r.id"); 
    }
    
    function realtimequiz_question_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT q.id, q.quizid      
                                 FROM {$CFG->prefix}realtimequiz_question q    
                                 WHERE q.quizid = $instanceid"); 
    }
    
    function realtimequiz_answer_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id, a.questionid, q.quizid      
                                 FROM {$CFG->prefix}realtimequiz_answer a,
                                      {$CFG->prefix}realtimequiz_question q,    
                                      {$CFG->prefix}realtimequiz r 
                                 WHERE r.course = '$course' AND
                                       q.quizid = r.id AND
                                       a.questionid = q.id"); 
    }
    
    function realtimequiz_answer_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT a.id, a.questionid, q.quizid      
                                 FROM {$CFG->prefix}realtimequiz_answer a,
                                      {$CFG->prefix}realtimequiz_question q     
                                 WHERE q.quizid = $instanceid AND
                                       a.questionid = q.id"); 
    }
    
    function realtimequiz_submission_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT s.id, s.answerid, a.questionid, q.quizid      
                                 FROM {$CFG->prefix}realtimequiz_submission s,
                                      {$CFG->prefix}realtimequiz_answer a,
                                      {$CFG->prefix}realtimequiz_question q,    
                                      {$CFG->prefix}realtimequiz r 
                                 WHERE r.course = '$course' AND
                                       q.quizid = r.id AND
                                       a.questionid = q.id AND
                                       s.answerid = a.id"); 
    }
    
    function realtimequiz_submission_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT s.id, s.answerid, a.questionid, q.quizid      
                                 FROM {$CFG->prefix}realtimequiz_submitted s,
                                      {$CFG->prefix}realtimequiz_answer a,
                                      {$CFG->prefix}realtimequiz_question q    
                                 WHERE q.quizid = $instanceid AND
                                       a.questionid = q.id AND
                                       s.answerid = a.id"); 
    }
    
    function realtimequiz_session_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT s.id, s.quizid      
                                 FROM {$CFG->prefix}realtimequiz_session s,    
                                      {$CFG->prefix}realtimequiz r 
                                 WHERE r.course = '$course' AND
                                       s.quizid = r.id"); 
    }
    
    function realtimequiz_session_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT s.id, s.quizid      
                                 FROM {$CFG->prefix}realtimequiz_session s    
                                 WHERE s.quizid = $instanceid"); 
    }
?>

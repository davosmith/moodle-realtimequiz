<?php
/**
 * Library of functions and constants for module realtimequiz
 *
 * @author : Davosmith
 * @package realtimequiz
 **/


$realtimequiz_CONSTANT = 7;     /// for example

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted realtimequiz record
 **/
function realtimequiz_add_instance($realtimequiz) {
    
    $realtimequiz->timemodified = time();

    $realtimequiz->status = 0;
    $realtimequiz->currentquestion = 0;
    $realtimequiz->nextendtime = 0;
    $realtimequiz->currentsessionid = 0;
    $realtimequiz->classresult = 0;
    $realtimequiz->questionresult = 0;
	    
    return insert_record("realtimequiz", $realtimequiz);
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function realtimequiz_update_instance($realtimequiz) {

    $realtimequiz->timemodified = time();
    $realtimequiz->id = $realtimequiz->instance;
    
    $realtimequiz->status = 0;
    $realtimequiz->currentquestion = 0;
    $realtimequiz->nextendtime = 0;
    $realtimequiz->currentsessionid = 0;
    $realtimequiz->classresult = 0;
    $realtimequiz->questionresult = 0;

    return update_record("realtimequiz", $realtimequiz);
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it. 
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function realtimequiz_delete_instance($id) {

    if (! $realtimequiz = get_record("realtimequiz", "id", "$id")) {
        return false;
    }

    $result = true;

	$questions = get_records('realtimequiz_question', 'quizid', "$id");
	foreach ($questions as $question) { // Get each question
		$answers = get_records('realtimequiz_answer', 'questionid', "$question->id");
		foreach ($answers as $answer) { // Get each answer for that question
			delete_records('realtimequiz_submission', 'answerid', "$answer->id"); // Delete each submission for that answer
		}
		delete_records('realtimequiz_answer', 'questionid', "$question->id"); // Delete each answer
	}
	delete_records('realtimequiz_question', 'quizid', "$id"); // Delete each question
	delete_records('realtimequiz_session', 'quizid', "$id"); // Delete each session
		
	# Delete any dependent records here #

    if (! delete_records("realtimequiz", "id", "$realtimequiz->id")) {
        $result = false;
    }

    return $result;
}

/**
 * Return a small object with summary information about what a 
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 **/
function realtimequiz_user_outline($course, $user, $mod, $realtimequiz) {
    return $return;
}

/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function realtimequiz_user_complete($course, $user, $mod, $realtimequiz) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity 
 * that has occurred in realtimequiz activities and print it out. 
 * Return true if there was output, or false is there was none. 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function realtimequiz_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such 
 * as sending out mail, toggling flags etc ... 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function realtimequiz_cron () {
    global $CFG;

    return true;
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user.  It also returns a maximum allowed grade.
 * 
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $realtimequizid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function realtimequiz_grades($realtimequizid) {
   return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of realtimequiz. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $realtimequizid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function realtimequiz_get_participants($realtimequizid) {
    return false;
}

/**
 * This function returns if a scale is being used by one realtimequiz
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $realtimequizid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function realtimequiz_scale_used ($realtimequizid,$scaleid) {
    $return = false;

    //$rec = get_record("realtimequiz","id","$realtimequizid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other realtimequiz functions go here.  Each of them must have a name that 
/// starts with realtimequiz_


?>

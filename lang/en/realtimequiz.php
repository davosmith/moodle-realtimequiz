<?php 

$string['modulename'] = 'Realtime quiz';
$string['modulenameplural'] = 'Realtime quizzes';
$string['editquestions'] = 'Edit the questions';
$string['seeresponses'] = 'View the responses';
$string['pluginadministration'] = 'Realtime quiz administration';

// Used by backuplib.php
$string['questions'] = 'Questions';
$string['answers'] = 'Answers';
$string['sessions'] = 'Sessions';
$string['submissions'] = 'Submissions';

// Capabilities
$string['realtimequiz:control'] = 'Start / control a quiz'; 
$string['realtimequiz:attempt'] = 'Attempt a quiz';
$string['realtimequiz:seeresponses'] = 'View the responses to a quiz';
$string['realtimequiz:editquestions'] = 'Edit the questions for a quiz';

// Editing the realtime quiz settings
$string['questiontime'] = 'Default time to display each question (seconds): ';
$string['questionimage'] = '(Optional) image: ';
$string['realtimequizsettings'] = 'Realtime quiz settings';
$string['removeimage'] = 'Remove image';

// Editing the realtime quiz questions
$string['addquestion'] = 'Add question';
$string['backquiz'] = 'Back to the Realtime quiz';
$string['questiontext'] = 'Question text:';
$string['editquestiontime'] = 'Question time (0 for default)';
$string['answertext'] = 'Answer text:';
$string['correct'] = 'Correct answer?';
$string['updatequestion'] = 'Save question';
$string['saveadd'] = 'Save question and add another';
$string['addanswers'] = 'Add space for 3 more answers';
$string['errorquestiontext'] = 'Error: You have not filled in the question';
$string['onecorrect'] = 'Error: There must be exactly one correct answer';
$string['deletequestion'] = 'Delete question';
$string['checkdelete'] = 'Are you sure you want to delete this question?';
$string['questionslist'] = 'Questions in this Realtime quiz: ';
$string['yes'] = 'Yes';
$string['no'] = 'No';
$string['addingquestion'] = 'Adding question ';
$string['edittingquestion'] = 'Editing question ';
$string['answer'] = 'Answer ';
$string['view'] = 'View quiz';
$string['responses'] = 'View responses';
$string['edit'] = 'Edit quiz';
$string['choosecorrect'] = 'Set this as the correct answer';

// Viewing the responses from different students
$string['nosessions'] = 'This Realtime quiz has not yet been attempted';
$string['choosesession'] = 'Choose a session to display: ';
$string['showsession'] = 'Show';
$string['allsessions'] = 'All Sessions';
$string['backresponses'] = 'Back to the full results';
$string['prevquestion'] = 'Previous question';
$string['nextquestion'] = 'Next question';
$string['allquestions'] = 'Back to full results';
$string['noanswers'] = 'No answers given';

// Used by quizdata.php
$string['notallowedattempt'] = 'You are not allowed to attempt this quiz';
$string['badsesskey'] = 'Bad session key';
$string['badquizid'] = 'Bad quizid: '; // Do not translate 'quizid'
$string['badcurrentquestion'] = 'Bad currentquestion: '; // Do not translate 'currentquestion'
$string['alreadyanswered'] = 'You have already answered this question';
$string['notauthorised'] = 'You are not authorised to control this quiz';
$string['unknownrequest'] = 'Unknown request: \'';
$string['incorrectstatus'] = 'Quiz has incorrect status: \'';

// Used by view_student.js
// Important - do not use any double-quotes (") in this text as it will cause problems when passing
// the text into javascript (edit 'view.php' if this is a major problem)
$string['joinquiz'] = 'Join Quiz';
$string['joininstruct'] = 'Wait until your teacher tells you before you click on this';
$string['waitstudent'] = 'Waiting for students to connect';
$string['clicknext'] = 'Click \'Next\' when everyone is ready';
$string['waitfirst'] = 'Waiting for the first question to be sent';
$string['question'] = 'Question ';
$string['invalidanswer'] = 'Invalid answer number ';
$string['finalresults'] = 'Final results';
$string['classresult'] = 'Class result: ';
$string['classresultcorrect'] = ' correct';
$string['questionfinished'] = 'Question finished, waiting for results';
$string['httprequestfail'] = 'Giving up :( Cannot create an XMLHTTP instance';
$string['noquestion'] = 'Bad response - no question data: ';
$string['tryagain'] = 'Do you want to try again?';
$string['resultthisquestion'] = 'This question: ';
$string['resultoverall'] = ' correct. Overall: ';
$string['resultcorrect'] = ' correct.';
$string['answersent'] = 'Answer sent - waiting for question to finish: ';
$string['quiznotrunning'] = 'Quiz not running at the moment - wait for your teacher to start it';
$string['servererror'] = 'Server returned error: ';
$string['badresponse'] = 'Unexpected response from server - ';
$string['httperror'] = 'There was a problem with the request - status: ';
$string['yourresult'] = 'Your result: ';
$string['displaynext'] = 'About to display next question:';
$string['sendinganswer'] = 'Sending answer';
$string['timeleft'] = 'Time left to answer:';
$string['tick'] = 'Correct answer';
$string['cross'] = 'Wrong answer';

// Used by view_teacher.js
// Important - do not use any double-quotes (") in this text as it will cause problems when passing
// the text into javascript (edit 'view.php' if this is a major problem)
$string['next'] = 'Next >>';
$string['startquiz'] = 'Start quiz';
$string['teacherstartinstruct'] = 'Use this to start a quiz for the students to take<br />Use the textbox to define a name for this session (to help when looking through the results at a later date).';
$string['teacherjoinquizinstruct'] = 'Use this if you want to try out a quiz yourself<br />(you will also need to start the quiz in a separate window)';


?>

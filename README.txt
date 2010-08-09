REALTIME QUIZ v0.7 (internal dev version)
------------------

IMPORTANT NOTE:
This Moodle module has been tested reasonably extensively, but I cannot garuntee that there are no problems with it. Please feel free to contact me if you do have any difficulty.
This does not mess around with anything other than the new database tables 'realtimequiz', 'realtimequiz_question', 'realtimequiz_answer', 'realtimequiz_session', so it should not be capable of breaking anything else (but I cannot promise anything).

What is it?
-----------
This is a type of quiz designed to be used in face-to-face lessons, with a classroom full of computers.
The teacher creates the quiz in advance - adding multiple-choice questions, with a range of answers (and indicating which is the correct answer).

During the lesson, the teacher starts the quiz (optionally giving the quiz a 'session name'). Students can now connect to this quiz. Once the teacher is satisfied that all students have connected to the quiz, they can click on 'Next' to show the first question. The question will be displayed for a pre-defined amount of time, after which the correct answer will be displayed, along with a count of how many students gave each answer. The teacher can then discuss the question, before clicking on 'Next' to show the next question. Once all the questions have been shown, the final result for the class is displayed.

The teacher can, at a later date, go back through the results and, for each question, see exactly what answer each student gave.

Installation:
-------------
Unzip all the files into a temporary directory.
Copy the 'realtimequiz' folder into '<moodlehomedir>/mod'.

Uninstalling:
-------------
Delete the module from the 'Activities' module list in the amin section.

Feedback:
---------

You can contact me on 'moodle AT davosmith DOT co DOT uk, or at http://www.davosmith.co.uk/contact.php 
Whilst I am a reasonably comptetent programmer, I am not massively experienced with Javascript / PHP / SQL (and this is the first bit of Moodle programming I've done). I am sure that there are lots of things I've written which are not done properly, so I would really appreciate any more experienced moodle developpers having a look at what I've written.

Things I would really like comments on are:

SQL - have I done anything stupid (especially with indexes)
PHP / Javascript - any really silly / ineffcient code, any major security problems
HTML / CSS - yes, there is some very bad stuff with tables in there - any suggestions to tidy this up would be great

Moodle - have I followed the guidelines properly (I've tried my best), are there anythings there that are using out of date functions, etc.

Bugs - anything you notice
General feedback - what works well / what should be improved / any extra features (the last one will not be a high priority - I want to take a bit of a break, now that I've got the basics working).

Thanks in advance for any help,

Davo

Changes:
--------

v0.8 () - Fixed: deleting associated answers/submissions when deleting questions
v0.7 (15/11/2008) - NOT RELEASED. Now able to backup (but not restore) realtime quizzes.
v0.6 (4/10/2008) - Made the client computer resend requests if nothing comes back from the server within 2 seconds (should stop quiz from getting stuck in heavy network traffic). Moved the language files into the same folder as the rest of the files.
v0.5 (18/7/2008) - Fixed bug where '&' '<' '>' symbols in questions / answers would cause quiz to break.
v0.4 (22/11/2007) - you can now have different times for each question (set to '0' for the default time set for the quiz)
v0.3 - added individual scores for students, display total number of questions, 
v0.2 - fixed 404 errors for non-admin logins
v0.1 - initial release

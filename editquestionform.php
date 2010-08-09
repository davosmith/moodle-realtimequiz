<?php

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class realtimequiz_editquestion_form extends moodleform_mod {	

	function definition() {

	global $CFG;
	$mform    =& $this->_form;

    $mform->addElement('header', 'general', get_string('general', 'form'));
   
	$mform->addElement('htmleditor', 'questiontext', get_string('questiontext','realtimequiz')));
	$mform->setType('questiontext', PARAM_RAW);
	$mform->addRule('questiontext', null, 'required', null, 'client');
	
    $this->add_action_buttons();
}

/*
		echo "<form method='post' action='$CFG->wwwroot/mod/realtimequiz/edit.php?id=$quizid'>";
		echo '<table cellpadding="5">
		<tr valign="top">
		<td align="right"><b>'.get_string('questiontext','realtimequiz').'</b></td>
		<td><textarea name="questiontext" rows="5" cols="50">'.$question->questiontext.'</textarea></td>
		</tr>';
		
		while (count($answers) < $minanswers) {
			$extraanswer = new stdClass();
			$extraanswer->id = 0;
			$extraanswer->answertext = '';
			$extraanswer->correct = 0;
			$answers[] = $extraanswer;
		}
		
		$answernum = 1;
		foreach ($answers as $answer) {
			echo '<tr><td><b>'.get_string('answer','realtimequiz').$answernum.'</b></td></tr>';
			echo '<tr valign="top">
			<td>'.get_string('answertext','realtimequiz').'</td>
			<td><input type="text" name="answertext['.$answernum.']" size="30" value="'.$answer->answertext.'" /></td>
			</tr>';
			echo '<tr valign="top">
			<td>'.get_string('correct','realtimequiz').'</td>
			<td><input name="answercorrect['.$answernum.']" type="checkbox" value="1" ';
			if ($answer->correct == 1) {
				echo 'checked="checked" ';
			}
			echo '/></td></tr>';
			echo '<input type="hidden" name="answerid['.$answernum.']" value="'.$answer->id.'" />';
			
			$answernum++;
		}
		
		echo '</table>';
		echo '<input type="hidden" name="action" value="'.$action.'" />';
		echo '<input type="hidden" name="questionid" value="'.$question->id.'" />';
		echo '<input type="hidden" name="questionnum" value="'.$question->questionnum.'" />';
		echo '<input type="hidden" name="minanswers" value="'.$minanswers.'" />';
		echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
		echo '<p><input type="submit" name="updatequestion" value="'.get_string('updatequestion', 'realtimequiz').'" /></p>';
		echo '<input type="submit" name="addanswers" value="'.get_string('addanswers', 'realtimequiz').'" /></form></center>';
		*/

?>
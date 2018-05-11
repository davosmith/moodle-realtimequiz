<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

class realtimequiz_editquestion_form extends moodleform {

    protected function definition() {

        $mform = $this->_form;
        $numanswers = $this->_customdata['numanswers'];
        $editoroptions = $this->_customdata['editoroptions'];
        $mform->addElement('hidden', 'quizid');
        $mform->setType('quizid', PARAM_INT);
        $mform->addElement('hidden', 'questionid');
        $mform->setType('questionid', PARAM_INT);
        $mform->addElement('hidden', 'numanswers');
        $mform->setType('numanswers', PARAM_INT);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('editor', 'questiontext_editor', get_string('questiontext', 'mod_realtimequiz'), null, $editoroptions);
        $mform->addRule('questiontext_editor', null, 'required', null, 'client');

        $mform->addElement('text', 'questiontime', get_string('editquestiontime', 'mod_realtimequiz'), 0);
        $mform->setType('questiontime', PARAM_INT);

        // Answers.
        for ($i = 1; $i <= $numanswers; $i++) {
            $ansgroup = array(
                $mform->createElement('radio', 'answercorrect', '', '', $i,
                                      array('class' => 'realtimequiz_answerradio')),
                $mform->createElement('text', "answertext[$i]", '', array('size' => 30)),
            );
            $mform->addGroup($ansgroup, 'answer', get_string('answer', 'realtimequiz').$i, array(' '), false);
            $mform->setType("answertext[$i]", PARAM_RAW);
        }
        $mform->addElement('radio', 'answercorrect', get_string('nocorrect', 'realtimequiz'), '', 0,
                           array('class' => 'realtimequiz_answerradio'));
        $mform->addElement('submit', 'addanswers', get_string('addanswers', 'realtimequiz'));

        // Action buttons.
        $actiongrp = array(
            $mform->createElement('submit', 'save', get_string('updatequestion', 'realtimequiz')),
            $mform->createElement('submit', 'saveadd', get_string('saveadd', 'realtimequiz')),
            $mform->createElement('cancel'),
        );
        $mform->addGroup($actiongrp, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function definition_after_data() {
        // Override any 'numanswers' value from the form submission (as it will be wrong if the 'add answers' button
        // was clicked).
        $mform = $this->_form;
        $numanswers = $this->_customdata['numanswers'];
        $el = $mform->getElement('numanswers');
        $el->setValue($numanswers);
    }
}


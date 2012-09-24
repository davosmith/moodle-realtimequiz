<?php

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class realtimequiz_editquestion_form extends moodleform_mod {

    function definition() {

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('editor', 'questiontext', get_string('questiontext','realtimequiz'));
        $mform->setType('questiontext', PARAM_RAW);
        $mform->addRule('questiontext', null, 'required', null, 'client');

        $this->add_action_buttons();
    }
}


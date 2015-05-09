<?php

/**
 * This file defines the main checklist configuration form
 * It uses the standard core Moodle (>1.8) formslib. For
 * more info about them, please visit:
 *
 * http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * The form must provide support for, at least these fields:
 *   - name: text element of 64cc max
 *
 * Also, it's usual to use these fields:
 *   - intro: one htmlarea element to describe the activity
 *            (will be showed in the list of activities of
 *             newmodule type (index.php) and in the header
 *             of the checklist main page (view.php).
 *   - introformat: The format used to write the contents
 *             of the intro field. It automatically defaults
 *             to HTML when the htmleditor is used and can be
 *             manually selected if the htmleditor is not used
 *             (standard formats are: MOODLE, HTML, PLAIN, MARKDOWN)
 *             See lib/weblib.php Constants and the format_text()
 *             function for more info
 */

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_realtimequiz_mod_form extends moodleform_mod {

    function definition() {
        global $CFG;

        $mform = $this->_form;

        //-------------------------------------------------------------------------------
        /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('modulename', 'realtimequiz'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        if ($CFG->branch < 29) {
            $this->add_intro_editor(true, get_string('realtimequizintro', 'realtimequiz'));
        } else {
            $this->standard_intro_elements(get_string('realtimequizintro', 'realtimequiz'));
        }

        //-------------------------------------------------------------------------------

        $mform->addElement('header', 'realtimequizsettings', get_string('realtimequizsettings', 'realtimequiz'));

        $mform->addElement('text', 'questiontime', get_string('questiontime', 'realtimequiz'));
        $mform->addRule('questiontime', null, 'numeric', null, 'client');
        $mform->setDefault('questiontime', 30);
        $mform->setType('questiontime', PARAM_INT);
        $mform->addHelpButton('questiontime', 'questiontime', 'realtimequiz');

        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }
}

<?php
/**
 * Settings for module realtimequiz
 *
 * @author : Davosmith
 * @package realtimequiz
 **/

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/realtimequiz/adminlib.php');

if ($ADMIN->fulltree) {

    $settings->add(new realtimequiz_awaittime_setting('realtimequiz/awaittime',
            get_string('awaittime', 'realtimequiz'), get_string('awaittimedesc', 'realtimequiz'), 2, PARAM_INT));
}

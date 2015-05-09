<?php
/**
 * Code fragment to define the version of realtimequiz
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author: Davo Smith
 * @package realtimequiz
 **/

defined('MOODLE_INTERNAL') || die();

$plugin = new stdClass(); // Avoid a warning in earlier Moodle versions.
$plugin->version   = 2014103001;  // The current module version (Date: YYYYMMDDXX).
$plugin->requires  = 2010112400;  // Moodle 2.0 (or above).
$plugin->cron      = 0;           // Period for cron to check this module (secs).
$plugin->component = 'mod_realtimequiz';
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '2.x (Build: 2014103001)';

if (isset($module)) {
    // Support the '$module' value used in earlier Moodle versions.
    foreach ($plugin as $key => $val) {
        $module->$key = $val;
    }
}

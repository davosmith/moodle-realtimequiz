<?php
/**
 * Code fragment to define the version of realtimequiz
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author: Davo Smith
 * @package realtimequiz
 **/

defined('MOODLE_INTERNAL') || die();
global $CFG;

if (!isset($plugin)) {
    $plugin = new stdClass(); // Avoid warnings in Moodle 2.5 and below.
}

$plugin->version = 2017110900;  // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2010112400;  // Moodle 2.0 (or above).
$plugin->cron = 0;           // Period for cron to check this module (secs).
$plugin->component = 'mod_realtimequiz';
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '2.x (Build: 2017110900)';

if (isset($CFG->branch) && $CFG->branch < 26) {
    $module->version = $plugin->version;
    $module->requires = $plugin->requires;
    $module->cron = $plugin->cron;
    $module->component = $plugin->component;
    $module->maturity = $plugin->maturity;
    $module->release = $plugin->release;
}

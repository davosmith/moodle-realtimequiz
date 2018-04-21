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

$plugin->version = 2018042100;  // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2017111300;  // Moodle 3.4 (or above).
$plugin->component = 'mod_realtimequiz';
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '3.4+ (Build: 2018042100)';

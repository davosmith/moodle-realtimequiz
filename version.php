<?php
/**
 * Code fragment to define the version of realtimequiz
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author: Davosmith
 * @package realtimequiz
 **/

defined('MOODLE_INTERNAL') || die();

$module->version   = 2013112800;  // The current module version (Date: YYYYMMDDXX)
$module->requires  = 2010112400;  // Moodle 2.0 (or above)
$module->cron      = 0;           // Period for cron to check this module (secs)
$module->component = 'mod_realtimequiz';
$module->maturity  = MATURITY_STABLE;
$module->release   = '2.x (Build: 2013112800)';

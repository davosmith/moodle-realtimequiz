<?php
/**
 * Code fragment to define the version of realtimequiz
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author: Davosmith
 * @package realtimequiz
 **/

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2013112800;  // The current module version (Date: YYYYMMDDXX)
$plugin->requires  = 2010112400;  // Moodle 2.0 (or above)
$plugin->cron      = 0;           // Period for cron to check this module (secs)
$plugin->component = 'mod_realtimequiz';
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '2.x (Build: 2013112800)';

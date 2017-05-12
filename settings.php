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

/**
 * Settings for module realtimequiz
 *
 * @author : Davosmith
 * @package realtimequiz
 **/

require_once($CFG->dirroot.'/mod/realtimequiz/adminlib.php');

if ($ADMIN->fulltree) {

    $settings->add(new realtimequiz_awaittime_setting('realtimequiz/awaittime',
                                                      get_string('awaittime', 'realtimequiz'),
                                                      get_string('awaittimedesc', 'realtimequiz'), 2, PARAM_INT));
}

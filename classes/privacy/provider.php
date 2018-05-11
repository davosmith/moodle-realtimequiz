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

/**
 * GDPR information
 *
 * @package   mod_realtimequiz
 * @copyright 2018 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_realtimequiz\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\provider,
                          \core_privacy\local\request\plugin\provider {

    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'realtimequiz_submitted',
            [
                'questionid' => 'privacy:metadata:realtimequiz_submitted:questionid',
                'sessionid' => 'privacy:metadata:realtimequiz_submitted:sessionid',
                'userid' => 'privacy:metadata:realtimequiz_submitted:userid',
                'answerid' => 'privacy:metadata:realtimequiz_submitted:answerid',
            ],
            'privacy:metadata:realtimequiz_submitted'
        );
        return $collection;
    }

    private static $modid;
    private static function get_modid() {
        global $DB;
        if (self::$modid === null) {
            self::$modid = $DB->get_field('modules', 'id', ['name' => 'realtimequiz']);
        }
        return self::$modid;
    }

    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();
        $modid = self::get_modid();
        if (!$modid) {
            return $contextlist; // Checklist module not installed.
        }

        $params = [
            'modid' => $modid,
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        // Quiz responses.
        $sql = '
           SELECT c.id
             FROM {context} c
             JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                                      AND cm.module = :modid
             JOIN {realtimequiz} q ON q.id = cm.instance
             JOIN {realtimequiz_question} qq ON qq.quizid = q.id
             JOIN {realtimequiz_submitted} qs ON qs.questionid = qq.id
            WHERE qs.userid = :userid
        ';
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!$contextlist->count()) {
            return;
        }

        $user = $contextlist->get_user();
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT cm.id AS cmid,
                       sess.name AS sessionname,
                       sess.timestamp AS sessiontimestamp,
                       qq.questiontext,
                       qa.answertext,
                       qa.correct

                 FROM {context} c
                 JOIN {course_modules} cm ON cm.id = c.instanceid
                 JOIN {realtimequiz} q ON q.id = cm.instance
                 JOIN {realtimequiz_question} qq ON qq.quizid = q.id
                 JOIN {realtimequiz_submitted} qs ON qs.questionid = qq.id
                 JOIN {realtimequiz_answer} qa ON qa.id = qs.answerid
                 JOIN {realtimequiz_session} sess ON sess.id = qs.sessionid

                WHERE c.id $contextsql
                  AND qs.userid = :userid

                ORDER BY cm.id, sess.timestamp, qq.questionnum
        ";
        $params = ['userid' => $user->id] + $contextparams;
        $lastcmid = null;
        $responsedata = [];

        $responses = $DB->get_recordset_sql($sql, $params);
        foreach ($responses as $response) {
            if ($lastcmid !== $response->cmid) {
                if ($responsedata) {
                    self::export_realtimequiz_data_for_user($responsedata, $lastcmid, $user);
                }
                $responsedata = [];
                $lastcmid = $response->cmid;
            }
            $responsedata[] = (object)[
                'session' => $response->sessionname,
                'sessiontime' => $response->sessiontimestamp ? transform::datetime($response->sessiontimestamp) : '',
                'questiontext' => $response->questiontext,
                'answertext' => $response->answertext,
                'correct' => $response->correct,
            ];
        }
        $responses->close();
        if ($responsedata) {
            self::export_realtimequiz_data_for_user($responsedata, $lastcmid, $user);
        }
    }

    /**
     * Export the supplied personal data for a single checklist activity, along with any generic data or area files.
     *
     * @param array $responses the data for each of the items in the checklist
     * @param int $cmid
     * @param \stdClass $user
     */
    protected static function export_realtimequiz_data_for_user(array $responses, int $cmid, \stdClass $user) {
        // Fetch the generic module data for the choice.
        $context = \context_module::instance($cmid);
        $contextdata = helper::get_context_data($context, $user);

        // Merge with checklist data and write it.
        $contextdata = (object)array_merge((array)$contextdata, ['responses' => $responses]);
        writer::with_context($context)->export_data([], $contextdata);

        // Write generic module intro files.
        helper::export_context_files($context, $user);
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if (!$context) {
            return;
        }
        $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
        $questionids = $DB->get_fieldset_select('realtimequiz_question', 'id', 'quizid = ?', [$instanceid]);
        if ($questionids) {
            $DB->delete_records_list('realtimequiz_submitted', 'questionid', $questionids);
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        if (!$contextlist->count()) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
            $questionids = $DB->get_fieldset_select('realtimequiz_question', 'id', 'quizid = ?', [$instanceid]);
            if ($questionids) {
                list($qsql, $params) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);
                $params['userid'] = $userid;
                $DB->delete_records_select('realtimequiz_submitted', "questionid $qsql AND userid = :userid", $params);
            }
        }
    }
}
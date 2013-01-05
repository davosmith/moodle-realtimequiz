<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_realtimequiz_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Add fields that were missing in the Moodle 1.9 version of this plugin.
    if ($oldversion < 2012102100) {

        $table = new xmldb_table('realtimequiz');

        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, FORMAT_HTML, 'intro');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'introformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecreated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2012102100, 'realtimequiz');
    }

    if ($oldversion < 2012102101) {

        // Define field questiontextformat to be added to realtimequiz_question
        $table = new xmldb_table('realtimequiz_question');
        $field = new xmldb_field('questiontextformat', XMLDB_TYPE_INTEGER, FORMAT_PLAIN, null, null, null, '1', 'questiontext');

        // Conditionally launch add field questiontextformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // realtimequiz savepoint reached
        upgrade_mod_savepoint(true, 2012102101, 'realtimequiz');
    }

    return true;
}

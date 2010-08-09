<?php // $Id: mysql.php,v 1.3 2006/08/28 16:41:20 mark-nielsen Exp $
/**
 * Upgrade procedures for realtimequiz
 *
 * @author 
 * @version $Id: mysql.php,v 1.3 2006/08/28 16:41:20 mark-nielsen Exp $
 * @package realtimequiz
 **/

/**
 * This function does anything necessary to upgrade 
 * older versions to match current functionality 
 *
 * @uses $CFG
 * @param int $oldversion The prior version number
 * @return boolean Success/Failure
 **/
function realtimequiz_upgrade($oldversion) {
    global $CFG;

    $result = true;

    if ($oldversion < 2007070600) {

    /// Define table realtimequiz to be created
        $table = new XMLDBTable('realtimequiz');

    /// Adding fields to table realtimequiz
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('status', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('currentquestion', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('nextendtime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');
        $table->addFieldInfo('currentsessionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Adding keys to table realtimequiz
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for realtimequiz
        $result = $result && create_table($table);

    /// Define table realtimequiz_question to be created
        $table = new XMLDBTable('realtimequiz_question');

    /// Adding fields to table realtimequiz_question
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('quizid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('questionnum', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('questiontext', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('questiontime', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, null, null, '0');

    /// Adding keys to table realtimequiz_question
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for realtimequiz_question
        $result = $result && create_table($table);

    /// Define table realtimequiz_answer to be created
        $table = new XMLDBTable('realtimequiz_answer');

    /// Adding fields to table realtimequiz_answer
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('questionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('answertext', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('correct', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

    /// Adding keys to table realtimequiz_answer
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for realtimequiz_answer
        $result = $result && create_table($table);

    /// Define table realtimequiz_submitted to be created
        $table = new XMLDBTable('realtimequiz_submitted');

    /// Adding fields to table realtimequiz_submitted
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('questionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('sessionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('answerid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

    /// Adding keys to table realtimequiz_submitted
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for realtimequiz_submitted
        $result = $result && create_table($table);
    }

    return $result;
}

?>

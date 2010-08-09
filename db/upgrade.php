<?php

function xmldb_realtimequiz_upgrade($oldversion) {
    global $CFG;

    $result = true;

    if ($result && $oldversion < 2007072001) {

        // Add 'classresult' field to store cumulative total % for class
        $table = new XMLDBTable('realtimequiz');
        $field = new XMLDBField('classresult');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $result = $result && add_field($table, $field);

        // Add index to question table
        $table = new XMLDBTable('realtimequiz_question');
        $index = new XMLDBIndex('quizid_questionnum');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('quizid', 'questionnum'));
        $result = $result && add_index($table, $index);

        // Add index to answer table
        $table = new XMLDBTable('realtimequiz_answer');
        $index = new XMLDBIndex('questionid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('questionid'));
        $result = $result && add_index($table, $index);

        // Add indicies to submitted answer table
        $table = new XMLDBTable('realtimequiz_submitted');
        $index = new XMLDBIndex('questionid_sessionid_answerid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('questionid', 'sessionid', 'answerid'));
        $result = $result && add_index($table, $index);
        $index = new XMLDBIndex('userid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $result = $result && add_index($table, $index);

        // Create a new table to store session data
        $table = new XMLDBTable('realtimequiz_session');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        $table->addFieldInfo('quizid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timestamp', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('quizid', XMLDB_INDEX_NOTUNIQUE, array('quizid'));

        $result = $result && create_table($table);        
    }
    
    if ($result && $oldversion < 2007072003) {
        
        $table = new XMLDBTable('realtimequiz');
        $field = new XMLDBField('questionresult');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'classresult');
        $result = $result && add_field($table, $field);        
    }
	
	if ($result && $oldversion < 2007072400) {

        $table = new XMLDBTable('realtimequiz');
        $field = new XMLDBField('nextendtime');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'currentquestion');
        $result = $result && change_field_notnull($table, $field);
        $field = new XMLDBField('currentsessionid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'nextendtime');
        $result = $result && change_field_notnull($table, $field);	
		
		$table = new XMLDBTable('realtimequiz_session');
        $field = new XMLDBField('name');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, '', 'id');
        $result = $result && change_field_notnull($table, $field);	
        $result = $result && change_field_default($table, $field);
        $field = new XMLDBField('timestamp');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'quizid');
				
		$table = new XMLDBTable('realtimequiz_submitted');
        $field = new XMLDBField('sessionid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'questionid');
        $result = $result && change_field_notnull($table, $field);
    }
	
	if ($result && $oldversion <  2007112000) {
        $table = new XMLDBTable('realtimequiz_question');
        $field = new XMLDBField('questiontime');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'questiontext');
        $result = $result && add_field($table, $field);
	}
    
    return $result;
}
?>

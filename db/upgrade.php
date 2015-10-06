<?php

function xmldb_local_moodec_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2015100600) {

        // Define field error to be added to local_moodec_transaction.
        $table = new xmldb_table('local_moodec_transaction');
        $field = new xmldb_field('error', XMLDB_TYPE_TEXT, null, null, null, null, null, 'purchase_date');

        // Conditionally launch add field error.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Moodec savepoint reached.
        upgrade_plugin_savepoint(true, 2015100600, 'local', 'moodec');
    }

    return true;
}
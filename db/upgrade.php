<?php

function xmldb_local_moodec_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2015080400) {

        // Define field product_tags to be added to local_moodec_course.
        $table = new xmldb_table('local_moodec_course');
        $field = new xmldb_field('product_tags', XMLDB_TYPE_TEXT, null, null, null, null, null, 'additional_info');

        // Conditionally launch add field product_tags.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Moodec savepoint reached.
        upgrade_plugin_savepoint(true, 2015080400, 'local', 'moodec');
    }

    return true;
}
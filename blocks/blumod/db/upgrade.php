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
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @since Moodle 2.0
 * @package block_blumod
 * @copyright 2025 Galan
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Handles upgrading instances of this block.
 *
 * @param int $oldversion
 */
function xmldb_block_blumod_upgrade($oldversion) {

    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 202503021805) {
        $table  = new xmldb_table('block_blucompetency');

        $field1 = new xmldb_field('id', XMLDB_TYPE_INTEGER, '19', null, XMLDB_NOTNULL, sequence: true);
        $field2 = new xmldb_field('competencyid', XMLDB_TYPE_INTEGER, '19', null, XMLDB_NOTNULL, sequence: null, default: '0');
        $field3 = new xmldb_field('bluid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, sequence: null, default: '0'); 

        $primarykey = new xmldb_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $index1     = new xmldb_index('ix_competency', XMLDB_INDEX_NOTUNIQUE, array('competencyid'));
        $index2     = new xmldb_index('ix_blu', XMLDB_INDEX_NOTUNIQUE, array('bluid'));

        if (!$dbman->table_exists($table)) {
            $table->addField($field1);
            $table->addField($field2);
            $table->addField($field3);
            $table->addKey($primarykey);
            $table->addIndex($index1);
            $table->addIndex($index2);
            $dbman->create_table($table);
        }       
        upgrade_block_savepoint(true, 202503021805, 'blumod', allowabort: false);
    
}
    return true;
}
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
 * Version details
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_format_ludic_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020093002) {

        // add target field to sections table.
        $table = new xmldb_table('format_ludic_cs');
        $field = new xmldb_field('target', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'skinid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // add targetmin and targetmax fields to course modules table.
        $table = new xmldb_table('format_ludic_cm');
        $field = new xmldb_field('targetmin', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'weight');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('targetmax', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'targetmin');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2020093002, 'format', 'ludic');
    }

    return true;
}

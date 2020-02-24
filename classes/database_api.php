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
 * Database interface
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/ludic/lib.php');

class database_api {

    private $db;
    private $contexthelper;

    /**
     * database_api constructor.
     *
     * @param $contexthelper
     */
    public function __construct($contexthelper) {
        global $DB;
        $this->db            = $DB;
        $this->contexthelper = $contexthelper;
    }

    /**
     * @param $sectionid
     * @return mixed
     * @throws \dml_exception
     */
    public function get_section_idx_by_id($sectionid) {
        return $this->db->get_field('course_sections', 'section', ['id' => $sectionid]);
    }

    /**
     * @param $sectionid
     * @return mixed
     * @throws \dml_exception
     */
    public function get_section_sequence_by_id($sectionid) {
        return $this->db->get_field('course_sections', 'sequence', ['id' => $sectionid]);
    }

    /**
     * @param $courseid
     * @param $sectionidx
     * @return mixed
     * @throws \dml_exception
     */
    public function get_sectionid_by_courseid_and_sectionidx($courseid, $sectionidx) {
        return $this->db->get_field('course_sections', 'id', [
                'course' => $courseid, 'section' => $sectionidx
        ]);
    }

    /**
     * @param $courseid
     * @return array
     * @throws \dml_exception
     */
    public function get_course_sections_by_courseid($courseid) {
        return $this->db->get_records('course_sections', ['course' => $courseid], 'section');
    }

    /**
     * @param $id
     * @return mixed
     * @throws \dml_exception
     */
    public function get_section_by_id($id) {
        return $this->db->get_record('course_sections', ['id' => $id]);
    }

    /**
     * @param $courseid
     * @return array
     * @throws \dml_exception
     */
    public function get_course_modules_by_courseid($courseid) {
        return $this->db->get_records_sql('
            SELECT cm.id, cm.id as cmid, cm.instance, m.name as modname, cm.section, cm.visible
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            WHERE cm.course = ?
            AND cm.deletioninprogress = 0', ['course' => $courseid]);
    }

    /**
     * @param $id
     * @return mixed
     * @throws \dml_exception
     */
    public function get_course_module_by_id($id) {
        return $this->db->get_record_sql('
            SELECT cm.id, cm.id as cmid, cm.instance, m.name as modname, cm.section, cm.visible
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            WHERE cm.id = ?
            AND cm.deletioninprogress = 0', ['id' => $id]);
    }

    /**
     * @param $roleshortname
     * @return mixed
     * @throws \dml_exception
     */
    public function get_role_id_by_role_shortname($roleshortname) {
        return $this->db->get_field('role', 'id', ['shortname' => $roleshortname]);
    }

    public function update_section($dbsection) {
        return $this->db->update_record('course_sections', $dbsection);
    }
}


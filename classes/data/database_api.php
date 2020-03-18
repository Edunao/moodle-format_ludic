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
 * Database interface.
 * All database calls must be made in this class.
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
    protected $contexthelper;

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
     * Get skin id from a section id.
     *
     * @param $sectionid
     * @return mixed
     * @throws \dml_exception
     */
    public function get_skin_id_by_section_id($sectionid) {
        return $this->db->get_field('format_ludic_cs', 'skinid', ['sectionid' => $sectionid]);
    }

    /**
     * Get skin id from a course module id (cmid).
     *
     * @param $cmid
     * @return mixed
     * @throws \dml_exception
     */
    public function get_skin_id_by_course_module_id($cmid) {
        return $this->db->get_field('format_ludic_cm', 'skinid', ['cmid' => $cmid]);
    }

    /**
     * Get format_ludic_cm db record.
     *
     * @param $cmid
     * @return mixed
     * @throws \dml_exception
     */
    public function get_format_ludic_cm_by_cmid($cmid) {
        return $this->db->get_record('format_ludic_cm', ['cmid' => $cmid]);
    }

    /**
     * Get format_ludic_cs db record.
     *
     * @param $sectionid
     * @return mixed
     * @throws \dml_exception
     */
    public function get_format_ludic_cs_by_sectionid($sectionid) {
        return $this->db->get_record('format_ludic_cs', ['sectionid' => $sectionid]);
    }

    /**
     * Add new format_ludic_cm db record.
     * Return new id.
     *
     * @param $dataobject
     * @return bool|int
     * @throws \dml_exception
     */
    public function add_format_ludic_cm_record($dataobject) {
        return $this->db->insert_record('format_ludic_cm', $dataobject, true);
    }

    /**
     * Add new format_ludic_cs db record.
     * Return new id.
     *
     * @param $dataobject
     * @return bool|int
     * @throws \dml_exception
     */
    public function add_format_ludic_cs_record($dataobject) {
        return $this->db->insert_record('format_ludic_cs', $dataobject, true);
    }

    /**
     * Set skin id for a section, update skin id if record exists, else insert record.
     *
     * @param $courseid
     * @param $sectionid
     * @param $skinid
     * @return bool|int
     * @throws \dml_exception
     */
    public function set_section_skin_id($courseid, $sectionid, $skinid) {
        $dbrecord = $this->db->get_record('format_ludic_cs', ['sectionid' => $sectionid]);
        if ($dbrecord) {
            $dbrecord->skinid = $skinid;
            return $this->db->update_record('format_ludic_cs', $dbrecord);
        }
        $dbrecord            = new \stdClass();
        $dbrecord->courseid  = $courseid;
        $dbrecord->sectionid = $sectionid;
        $dbrecord->skinid    = $skinid;
        return $this->db->insert_record('format_ludic_cs', $dbrecord);
    }

    /**
     * Remove skin for a given section id.
     *
     * @param $sectionid
     * @return bool
     * @throws \dml_exception
     */
    public function delete_section_skin_id($sectionid) {
        return $this->db->delete_records('format_ludic_cs', ['sectionid' => $sectionid]);
    }

    /**
     * Remove ludic record for a given course module id.
     *
     * @param $cmid
     * @return bool
     * @throws \dml_exception
     */
    public function delete_format_ludic_cm($cmid) {
        return $this->db->delete_records('format_ludic_cm', ['cmid' => $cmid]);
    }

    /**
     * Set skin id, weight, access for a course module.
     * Update if record exists, else insert record.
     *
     * @param $courseid
     * @param $cmid
     * @param $skinid
     * @param $weight
     * @param $access
     * @return bool|int
     * @throws \dml_exception
     */
    public function set_format_ludic_cm($courseid, $cmid, $skinid, $weight, $access) {
        $dbrecord = $this->db->get_record('format_ludic_cm', ['cmid' => $cmid]);
        if ($dbrecord) {
            $dbrecord->skinid = $skinid;
            $dbrecord->weight = $weight;
            $dbrecord->access = $access;
            return $this->db->update_record('format_ludic_cm', $dbrecord);
        }
        $dbrecord           = new \stdClass();
        $dbrecord->courseid = $courseid;
        $dbrecord->cmid     = $cmid;
        $dbrecord->skinid   = $skinid;
        $dbrecord->weight   = $weight;
        $dbrecord->access   = $access;
        return $this->db->insert_record('format_ludic_cm', $dbrecord);
    }

    /**
     * Get course id from a sectionid.
     *
     * @param $sectionid
     * @return mixed
     * @throws \dml_exception
     */
    public function get_course_id_by_section_id($sectionid) {
        return $this->db->get_field('course_sections', 'course', ['id' => $sectionid]);
    }

    /**
     * Get section idx (number) from a sectionid.
     *
     * @param $sectionid
     * @return mixed
     * @throws \dml_exception
     */
    public function get_section_idx_by_id($sectionid) {
        return $this->db->get_field('course_sections', 'section', ['id' => $sectionid]);
    }

    /**
     * Get section sequence (1,2,3,...) from a sectionid.
     *
     * @param $sectionid
     * @return mixed
     * @throws \dml_exception
     */
    public function get_section_sequence_by_id($sectionid) {
        return $this->db->get_field('course_sections', 'sequence', ['id' => $sectionid]);
    }

    /**
     * Get section id from a courseid and a section idx (number).
     *
     * @param $courseid
     * @param $sectionidx
     * @return mixed
     * @throws \dml_exception
     */
    public function get_section_id_by_courseid_and_sectionidx($courseid, $sectionidx) {
        return $this->db->get_field('course_sections', 'id', [
                'course' => $courseid, 'section' => $sectionidx
        ]);
    }

    /**
     * Get section name from a courseid and a section idx (number).
     *
     * @param $courseid
     * @param $sectionidx
     * @return mixed
     * @throws \dml_exception
     */
    public function get_section_name_by_courseid_and_sectionidx($courseid, $sectionidx) {
        return $this->db->get_field('course_sections', 'name', [
                'course' => $courseid, 'section' => $sectionidx
        ]);
    }

    /**
     * Get course_sections records by courseid.
     *
     * @param $courseid
     * @return array
     * @throws \dml_exception
     */
    public function get_course_sections_by_courseid($courseid) {
        return $this->db->get_records('course_sections', ['course' => $courseid], 'section');
    }

    /**
     * Get course_sections record by id.
     *
     * @param $id
     * @return mixed
     * @throws \dml_exception
     */
    public function get_section_by_id($id) {
        return $this->db->get_record('course_sections', ['id' => $id]);
    }

    /**
     * Get course_modules record by id.
     *
     * @param $id
     * @return mixed
     * @throws \dml_exception
     */
    public function get_course_module_by_id($id) {
        return $this->db->get_record_sql('
            SELECT cm.*, m.name as modname
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            WHERE cm.id = ?', ['id' => $id]);
    }

    /**
     * Get module name (forum, ...) by cmid.
     *
     * @param $cmid
     * @return mixed
     * @throws \dml_exception
     */
    public function get_module_name_by_course_module_id($cmid) {
        return $this->db->get_field_sql('
            SELECT m.name
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            WHERE cm.id = ?', ['id' => $cmid]);
    }

    /**
     * Get role id by his shortname.
     *
     * @param $roleshortname
     * @return mixed
     * @throws \dml_exception
     */
    public function get_role_id_by_role_shortname($roleshortname) {
        return $this->db->get_field('role', 'id', ['shortname' => $roleshortname]);
    }

    /**
     * Update section using moodle function.
     *
     * @param $moodlecourse
     * @param $dbrecord
     * @param $data
     * @throws \moodle_exception
     */
    public function update_section($moodlecourse, $dbrecord, $data) {
        course_update_section($moodlecourse, $dbrecord, $data);
    }

    /**
     * Update course module name only.
     *
     * @param $cmid
     * @param $name
     * @return bool
     * @throws \dml_exception
     */
    public function update_course_module_name($cmid, $name) {
        $cmrecord = $this->get_course_module_by_id($cmid);
        if (!$cmrecord) {
            return false;
        }
        $updaterecord = (object) ['id' => $cmrecord->instance, 'name' => $name];
        return $this->db->update_record($cmrecord->modname, $updaterecord);
    }

    /**
     * Update course module name only.
     *
     * @param $cmid
     * @param $visible
     * @return bool
     * @throws \dml_exception
     */
    public function update_course_module_visible($cmid, $visible) {
        $updaterecord = (object) ['id' => $cmid, 'visible' => $visible];
        return $this->db->update_record('course_modules', $updaterecord);
    }

    /**
     * @param $record
     * @return bool
     * @throws \dml_exception
     */
    public function update_section_record($record) {
        return $this->db->update_record('course_sections', $record);
    }

    /**
     * @param int $courseid
     * @param int $nbsections
     * @return bool
     */
    public function create_section($courseid, $nbsections) {
        return course_create_sections_if_missing($courseid, [$nbsections]);
    }

    /**
     * Count all course sections
     *
     * @param int $courseid
     * @return int
     * @throws \dml_exception
     */
    public function count_course_sections($courseid) {
        return $this->db->count_records('course_sections', array('course' => $courseid));
    }

    /**
     * @param int $courseid
     * @return mixed
     * @throws \dml_exception
     */
    public function get_course_last_section($courseid) {
        return $this->db->get_record_sql('SELECT * FROM {course_sections} WHERE course = :courseid ORDER BY section DESC LIMIT 1',
                ['courseid' => $courseid]);
    }

    /**
     * Check if a file exists in draft by itemid.
     *
     * @param $itemid
     * @return bool
     * @throws \dml_exception
     */
    public function file_exists_in_draft($itemid) {
        return $this->db->record_exists_sql('
        SELECT *
        FROM {files}
        WHERE component = :component
            AND filearea = :filearea
            AND filename <> :filename
            AND itemid = :itemid
        ', [
                'component' => 'user',
                'filearea'  => 'draft',
                'filename'  => '.',
                'itemid'    => $itemid
        ]);
    }
}


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
            $dbrecord->skinid    = $skinid;
            return $this->db->update_record('format_ludic_cs', $dbrecord);
        }
        $dbrecord            = new \stdClass();
        $dbrecord->courseid  = $courseid;
        $dbrecord->sectionid = $sectionid;
        $dbrecord->skinid    = $skinid;
        return $this->db->insert_record('format_ludic_cs', $dbrecord);
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
     * Get sectionid from a courseid and a section idx (number).
     *
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
     * Get course_modules records of a course.
     *
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
     * Get course_modules record by id.
     *
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


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
 * Ludic section class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

class section extends model {

    private $course;

    public $dbrecord;
    public $courseid;
    public $section;
    public $sectioninfo;
    public $name;
    public $sequence;
    public $visible;
    public $coursemodules;
    public $skinid;

    /**
     * section constructor.
     *
     * @param $section \stdClass course_sections record
     * @throws \moodle_exception
     */
    public function __construct($section) {
        parent::__construct($section);
        $this->dbrecord    = $section;
        $this->courseid    = $section->course;
        $this->section     = $section->section;
        $this->name        = $section->name;
        $this->sequence    = array_filter(explode(',', $section->sequence));
        $this->visible     = $section->visible;
        $modinfo           = $this->contexthelper->get_fast_modinfo($this->courseid);
        $this->sectioninfo = $modinfo->get_section_info($this->section);
    }

    /**
     * Get all ludic course modules of section.
     *
     * @return course_module[]
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_course_modules() {
        $dataapi  = $this->contexthelper->get_data_api();
        $courseid = $this->courseid;
        $userid   = $this->contexthelper->get_user_id();
        return $dataapi->get_section_course_modules($courseid, $userid, $this->id);
    }

    /**
     * Update section sequence.
     *
     * @param $newsequence
     * @throws \dml_exception
     */
    public function update_sequence($newsequence) {
        $dbapi            = $this->contexthelper->get_database_api();
        $moodlecourse     = $this->get_moodle_course();
        $this->sequence   = $newsequence;
        $data             = [];
        $data['sequence'] = implode(',', $newsequence);
        $dbapi->update_section($moodlecourse, $this->dbrecord, $data);
    }

    /**
     * Move this section after another section.
     *
     * @param $sectionidx
     * @return bool
     */
    public function move_section_to($sectionidx) {
        $course = $this->get_course()->moodlecourse;
        return move_section_to($course, $this->section, $sectionidx);
    }

    /**
     * Get ludic course.
     *
     * @return course
     */
    public function get_course() {
        $dataapi      = $this->contexthelper->get_data_api();
        $this->course = $dataapi->get_course_by_id($this->courseid);
        return $this->course;
    }

    public function get_moodle_course() {
        $course = $this->get_course();
        return $course->moodlecourse;
    }

    public function update($data) {
        $dbapi        = $this->contexthelper->get_database_api();
        $moodlecourse = $this->get_moodle_course();

        if (!isset($data['id']) || $data['id'] !== $this->dbrecord->id) {
            return false;
        }
        if (isset($data['name']) && $data['name'] !== $this->dbrecord->name) {
            $dbapi->update_section($moodlecourse, $this->dbrecord, $data);
        }
        return true;
    }

    public function get_context() {
        return \context_course::instance($this->courseid);
    }
}
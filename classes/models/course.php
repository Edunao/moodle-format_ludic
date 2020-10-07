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
 * Ludic course class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/model.php');

class course extends model {

    public $moodlecourse;
    public $coursemodules;

    /**
     * course constructor.
     *
     * @param $course
     */
    public function __construct($course) {
        $this->moodlecourse = $course;
        parent::__construct($this->moodlecourse);
    }

    /**
     * Return array of course sections.
     *
     * @param bool $globalsection
     * @return section[]
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_sections($globalsection = false) {

        // Get sections.
        $dbapi          = $this->contexthelper->get_database_api();
        $sectionrecords = $dbapi->get_course_sections_by_courseid($this->id);

        // Return section object.
        $sections = [];
        foreach ($sectionrecords as $section) {

            // Ignore section 0.
            if (!$globalsection && $section->section == 0) {
                continue;
            }

            $section                     = new section($section);
            $sections[$section->section] = $section;
        }

        return $sections;
    }

    /**
     * Get course context.
     *
     * @return \context_course
     */
    public function get_context() {
        return \context_course::instance($this->id);
    }

    /**
     * Create an section in the course defined in $courseid.
     *
     * @param
     * @return false|section
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function create_section() {
        $dbapi      = $this->contexthelper->get_database_api();
        $nbsections = $dbapi->count_course_sections($this->id);

        if (!$dbapi->create_section($this->id, $nbsections)) {
            return false;
        }

        if (!$newsection = $dbapi->get_course_last_section($this->id)) {
            return false;
        }

        return $this->contexthelper->get_section_by_id($newsection->id);
    }

    /**
     * This includes information about the course-modules and the sections on the course.
     * It can also include dynamic data that has been updated for the current user.
     *
     * @return \course_modinfo
     * @throws \moodle_exception
     */
    public function get_course_info() {
        return get_fast_modinfo($this->id, $this->contexthelper->get_user_id());
    }

}
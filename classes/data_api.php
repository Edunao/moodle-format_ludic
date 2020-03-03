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
 * Data interface.
 * All data must be retrieved in this class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/ludic/lib.php');

class data_api {

    private $contexthelper;
    private $course        = null;
    private $sections      = null;
    private $coursemodules = null;

    /**
     * data_api constructor.
     *
     * @param $contexthelper context_helper
     */
    public function __construct($contexthelper) {
        $this->contexthelper = $contexthelper;
    }

    /**
     * Get course by id.
     *
     * @return course
     * @throws \dml_exception
     */
    public function get_course_by_id($courseid) {
        if ($this->course == null) {
            $course       = \get_course($courseid);
            $this->course = new course($course);
        }
        return $this->course;
    }

    /**
     * Return all course sections of a course.
     *
     * @return section[]
     * @throws \moodle_exception
     */
    public function get_sections_by_course_id($courseid) {

        // If the value of the attribute has already been retrieved then we return it.
        // TODO if ($this->sections !== null) {.
        //    return $this->sections;
        //}

        // Get sections list.
        $databaseapi    = $this->contexthelper->get_database_api();
        $sectionrecords = $databaseapi->get_course_sections_by_courseid($courseid);

        // Return section object.
        $sections = [];
        foreach ($sectionrecords as $section) {
            if ($section->section == 0) {
                continue;
            }
            $section                     = new section($section);
            $sections[$section->section] = $section;
        }
        $this->sections = count($sections) > 0 ? $sections : [];
        return $this->sections;
    }

    /**
     * Get section by id.
     *
     * @param $sectionid
     * @return section
     * @throws \dml_exception
     */
    public function get_section_by_id($sectionid) {
        $databaseapi = $this->contexthelper->get_database_api();
        $sectionidx  = $databaseapi->get_section_idx_by_id($sectionid);

        // If the value of the attribute has already been retrieved then we return it.
        //if (isset($this->sections[$sectionidx])) {
        //    return $this->sections[$sectionidx];
        //}

        // Get section.
        $sectionrecord = $databaseapi->get_section_by_id($sectionid);
        return new section($sectionrecord);
    }

    /**
     * Get all course modules.
     *
     * @param $courseid
     * @param $userid
     * @return course_module[]|null
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_course_modules($courseid, $userid) {
        //if ($this->coursemodules == null) {
        $modinfocms    = $this->contexthelper->get_modinfo_cms($courseid, $userid);
        $coursemodules = [];
        foreach ($modinfocms as $modinfocm) {
            $coursemodules[] = new course_module($modinfocm);
        }
        $this->coursemodules = $coursemodules;
        //}
        return $this->coursemodules;
    }

    /**
     * Get course module by id.
     *
     * @param $courseid
     * @param $userid
     * @param $cmid
     * @return course_module
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_course_module_by_id($courseid, $userid, $cmid) {
        $modinfo      = $this->contexthelper->get_fast_modinfo($courseid, $userid);
        $modinfocm    = $modinfo->get_cm($cmid);
        $coursemodule = new course_module($modinfocm);
        return $coursemodule;
    }

    /**
     * Get all course modules of section.
     * @param $courseid
     * @param $userid
     * @param $sectionid
     * @return course_module[]
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_section_course_modules($courseid, $userid, $sectionid) {
        $coursemodules = $this->get_course_modules($courseid, $userid);
        $sectioncms    = [];

        $sequence = $this->get_section_sequence_by_id($sectionid);
        foreach ($sequence as $order => $cmid) {
            foreach ($coursemodules as $coursemodule) {
                if ($coursemodule->id == $cmid) {
                    $sectioncms[] = $coursemodule;
                }
            }
        }
        return $sectioncms;
    }

    /**
     * Get section sequence array.
     *
     * @param $sectionid
     * @return array
     * @throws \dml_exception
     */
    public function get_section_sequence_by_id($sectionid) {
        $databaseapi = $this->contexthelper->get_database_api();
        $sequencestr = $databaseapi->get_section_sequence_by_id($sectionid);
        return array_filter(explode(',', $sequencestr));
    }

}


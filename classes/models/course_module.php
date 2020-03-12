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
 * Ludic course module class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

class course_module extends model {

    public $name;
    public $order;
    public $cminfo;
    public $courseid;
    public $section;
    public $sectionid;
    public $accessible;

    /**
     * course_module constructor.
     *
     * @param \cm_info $cminfo
     * @throws \dml_exception
     */
    public function __construct(\cm_info $cminfo) {
        parent::__construct($cminfo);
        $this->courseid  = $cminfo->course;
        $this->sectionid = $cminfo->section;
        $this->section   = $this->contexthelper->get_section_by_id($this->sectionid);
        $this->name      = $cminfo->get_formatted_name();
        $this->cminfo    = $cminfo;
    }

    /**
     * Move a course module to another section.
     *
     * @param $sectionid
     * @param null $beforeid
     * @return int
     * @throws \dml_exception
     */
    public function move_to_section($sectionid, $beforeid = null) {
        $section = $this->contexthelper->get_section_by_id($sectionid);
        if ($sectionid == $this->sectionid) {
            return $this->accessible;
        }
        $this->section    = $section;
        $this->sectionid  = $sectionid;
        $movetosection    = (object) [
                'id'      => $section->id,
                'section' => $section->section,
                'course'  => $section->courseid,
                'visible' => $section->visible
        ];
        $this->accessible = moveto_module($this->cminfo, $movetosection, $beforeid);
        return $this->accessible;
    }

    /**
     * Move a course module after a course module on the same section.
     *
     * @param $cmidtomove
     * @param $aftercmid
     * @throws \dml_exception
     */
    public function move_on_section($cmidtomove, $aftercmid) {
        $sequence    = $this->section->sequence;
        $newsequence = [];
        foreach ($sequence as $key => $id) {
            if ($id != $cmidtomove) {
                $newsequence[] = $id;
            }
            if ($id == $aftercmid) {
                $newsequence[] = $cmidtomove;
            }
        }
        $this->section->update_sequence($newsequence);
    }

    /**
     * Duplicate the module
     *
     * @param $course
     * @return course_module
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function duplicate($course) {
        $coursemodule = (object) [
                'id'      => $this->id,
                'course'  => $this->courseid,
                'section' => $this->sectionid,
                'name'    => $this->name,
                'modname' => $this->cminfo->modname
        ];
        $newcm        = duplicate_module($course, $coursemodule);
        return new course_module($newcm);
    }
}
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
    public $access;
    public $visible;
    public $skinid;
    public $skin;
    public $weight;

    /**
     * course_module constructor.
     *
     * @param \cm_info $cminfo
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct(\cm_info $cminfo) {
        parent::__construct($cminfo);
        $this->courseid      = $cminfo->course;
        $this->sectionid     = $cminfo->section;
        $this->section       = $this->contexthelper->get_section_by_id($this->sectionid);
        $this->name          = $cminfo->get_formatted_name();
        $this->visible       = $cminfo->visible;
        $this->cminfo        = $cminfo;

        $dbrecord     = $this->contexthelper->get_format_ludic_cm_by_cmid($this->courseid, $this->id);
        $this->skinid = $dbrecord->skinid;
        $this->weight = $dbrecord->weight;
        $this->access = $dbrecord->access;
        $this->skin   = skin::get_by_id($this->skinid);
    }

    /**
     * Move a course module to another section.
     *
     * @param $sectionid
     * @param null $beforeid
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function move_to_section($sectionid, $beforeid = null) {
        $section = $this->contexthelper->get_section_by_id($sectionid);
        if ($sectionid == $this->sectionid) {
            return;
        }
        $this->section    = $section;
        $this->sectionid  = $sectionid;
        $movetosection    = (object) [
                'id'      => $section->id,
                'section' => $section->section,
                'course'  => $section->courseid,
                'visible' => $section->visible
        ];
        moveto_module($this->cminfo, $movetosection, $beforeid);
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
     * @param $course \stdClass
     * @param $movetoend bool
     * @return course_module
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function duplicate($course, $movetoend = false) {

        $coursemodule = (object) [
                'id'      => $this->id,
                'course'  => $this->courseid,
                'section' => $this->sectionid,
                'name'    => $this->name,
                'modname' => $this->cminfo->modname
        ];

        $newcm = duplicate_module($course, $coursemodule);

        $dbapi = $this->contexthelper->get_database_api();

        $dbapi->set_format_ludic_cm($this->courseid, $newcm->id, $this->skinid, $this->weight, $this->access);

        if ($movetoend) {
            $sequence = $this->section->sequence;
            $lastcmid = end($sequence);
            $this->move_on_section($newcm->id, $lastcmid);
        }

        rebuild_course_cache($this->courseid, true);

        return new course_module($newcm);
    }

    public function get_edit_buttons() {
        global $CFG;

        $editcmurl   = $CFG->wwwroot . '/course/modedit.php?update=' . $this->id . '&return=0';
        $deletecmurl = $CFG->wwwroot . '/course/mod.php?sesskey=' . sesskey() . '&sr=' . $this->section->section . '&delete=' .
                       $this->id . '&confirm=1';
        $assignurl   = $CFG->wwwroot . '/admin/roles/assign.php?contextid=' . $this->cminfo->context->id;

        return [
                [
                    // Submit form button.
                        'identifier' => 'form-save',
                        'action'     => 'saveForm',
                        'order'      => 1
                ],
                [
                    // Revert form button.
                        'identifier' => 'form-revert',
                        'action'     => 'revertForm',
                        'order'      => 2
                ],
                [
                    // Edit buttons : section settings, duplicate section, delete section.
                        'identifier'    => 'edit',
                        'order'         => 3,
                        'hassubbuttons' => true,
                        'action'        => 'showSubButtons',
                        'subbuttons'    => [
                                [
                                        'identifier' => 'edit-settings', 'action' => 'getDataLinkAndRedirectTo',
                                        'link'       => $editcmurl
                                ],
                                [
                                        'identifier' => 'duplicate', 'controller' => 'section',
                                        'action'     => 'duplicate_course_module',
                                        'callback'   => 'displayCourseModulesHtml'
                                ],
                                [
                                        'identifier' => 'assign',
                                        'link'       => $assignurl,
                                        'action'     => 'getDataLinkAndRedirectTo'
                                ],
                                [
                                        'identifier' => 'delete',
                                        'link'       => $deletecmurl,
                                        'action'     => 'confirmAndDeleteCourseModule'
                                ]
                        ]
                ],
                [
                    // Preview student section view button.
                        'identifier' => 'item-preview',
                        'order'      => 4
                ]
        ];
    }

    /**
     * @param $data
     * @return bool
     * @throws \dml_exception
     */
    public function update($data) {
        $dbapi = $this->contexthelper->get_database_api();

        if (!isset($data['id']) || $data['id'] !== $this->id) {
            return false;
        }

        if (isset($data['name']) && $data['name'] !== $this->name) {
            $dbapi->update_course_module_name($this->id, $data['name']);
        }

        if (isset($data['skinid']) && $data['skinid'] !== $this->skinid ||
            isset($data['weight']) && $data['weight'] !== $this->weight ||
            isset($data['access']) && $data['access'] !== $this->access
        ) {
            $dbapi->set_format_ludic_cm($this->courseid, $this->id, $data['skinid'], $data['weight'], $data['access']);
        }

        rebuild_course_cache($this->courseid, true);

        return true;
    }

    /**
     * Return course module icon.
     *
     * @return object
     */
    public function get_mod_icon() {
        return (object) [
                'imgsrc' => $this->cminfo->get_icon_url()->out(false),
                'imgalt' => $this->cminfo->modname
        ];
    }

}
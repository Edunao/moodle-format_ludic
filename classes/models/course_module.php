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

class course_module extends model implements skinnable_interface {

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
    private $weight;
    private $results;

    /**
     * course_module constructor.
     *
     * @param \cm_info $cminfo
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct(\cm_info $cminfo) {
        parent::__construct($cminfo);

        // Course module properties.
        $this->courseid  = $cminfo->course;
        $this->sectionid = $cminfo->section;
        $this->section   = $this->contexthelper->get_section_by_id($this->sectionid);
        $this->name      = $cminfo->get_formatted_name();
        $this->visible   = $cminfo->uservisible && $cminfo->visible;
        $this->cminfo    = $cminfo;

        // Ludic properties.
        $skinrelation = $this->get_skin_relation();
        $this->skinid = $skinrelation->skinid;
        $this->weight = $skinrelation->weight;
        $this->access = $skinrelation->access;
        $this->skin   = skin::get_by_id($this->skinid, $this);

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
        $this->section   = $section;
        $this->sectionid = $sectionid;
        $movetosection   = (object) [
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
     * @throws \moodle_exception
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
     * @param $movetoend bool
     * @return course_module
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function duplicate($movetoend = false) {
        // Required data.
        $dbapi        = $this->contexthelper->get_database_api();
        $course       = $this->contexthelper->get_moodle_course();
        $coursemodule = (object) [
                'id'      => $this->id,
                'course'  => $this->courseid,
                'section' => $this->sectionid,
                'name'    => $this->name,
                'modname' => $this->cminfo->modname
        ];

        // Duplicate course module.
        $newcm = duplicate_module($course, $coursemodule);

        // Copy skin, weight and access from this course module.
        $dbapi->set_format_ludic_cm($this->courseid, $newcm->id, $this->skinid, $this->weight, $this->access);

        // Move course module to end.
        if ($movetoend) {
            $sequence = $this->section->sequence;
            $lastcmid = end($sequence);
            $this->move_on_section($newcm->id, $lastcmid);
        }

        // Rebuild cache to add new course module in it.
        rebuild_course_cache($this->courseid, true);
        $this->contexthelper->rebuild_course_info();

        return new course_module($newcm);
    }

    /**
     * Edit buttons :
     * Button 1 : Save form.
     * Button 2 : Revert form.
     * Button 3 : Edit (open sub buttons).
     * Button 3 - 1 : Edit settings.
     * Button 3 - 2 : Duplicate.
     * Button 3 - 3 : Assign.
     * Button 3 - 4 : Delete.
     * Button 4 : Preview.
     *
     * @return array
     */
    public function get_edit_buttons() {
        global $CFG;

        // Defines url here.
        $editcmurl   = $CFG->wwwroot . '/course/modedit.php?update=' . $this->id . '&return=0';
        $deletecmurl = $CFG->wwwroot . '/course/mod.php?sesskey=' . sesskey() . '&sr=' . $this->section->section
                       . '&delete=' . $this->id . '&confirm=1';
        $assignurl   = $CFG->wwwroot . '/admin/roles/assign.php?contextid=' . $this->cminfo->context->id;

        return [
                [
                        'identifier' => 'form-save',
                        'action'     => 'saveForm',
                        'order'      => 1
                ],
                [
                        'identifier' => 'form-revert',
                        'action'     => 'revertForm',
                        'order'      => 2
                ],
                [
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
                        'identifier' => 'item-open',
                        'link'       => $this->get_link(),
                        'action'     => 'getDataLinkAndRedirectTo',
                        'order'      => 4
                ]
        ];
    }

    /**
     * Update course module.
     *
     * @param $data
     * @return bool
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function update($data) {
        $dbapi = $this->contexthelper->get_database_api();

        // Check if data id is current id.
        if (!isset($data['id']) || $data['id'] != $this->id) {
            return false;
        }

        // Update name if required.
        if (isset($data['name']) && $data['name'] !== $this->name) {
            $dbapi->update_course_module_name($this->id, $data['name']);
        }

        // Update skin id, weight or access if required.
        if (isset($data['skinid']) && $data['skinid'] != $this->skinid ||
            isset($data['weight']) && $data['weight'] != $this->weight ||
            isset($data['access']) && $data['access'] != $this->access
        ) {
            $dbapi->set_format_ludic_cm($this->courseid, $this->id, $data['skinid'], $data['weight'], $data['access']);
        }

        // Rebuild cache after update.
        rebuild_course_cache($this->courseid, true);

        return true;
    }

    /**
     * Return course module icon.
     *
     * @return \stdClass
     */
    public function get_mod_icon() {
        return (object) [
                'imgsrc' => $this->cminfo->get_icon_url()->out(false),
                'imgalt' => $this->cminfo->modname
        ];
    }

    /**
     * Get available course module skins.
     * Returns skins with grades only if the module course supports grades.
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_available_skins() {
        if ($this->section->section == 0) {
            return $this->contexthelper->get_global_section_skins();
        }

        // Label can use only inline skin.
        $modname = $this->cminfo->modname;
        if ($modname === 'label') {
            return [coursemodule\inline::get_instance()];
        }

        $skins   = $this->contexthelper->get_course_module_skins();

        // True if this course module supports grades.
        $isgraded = $modname ? plugin_supports('mod', $modname, FEATURE_GRADE_HAS_GRADE, false) : false;

        // Keep skins with grades only if the module course supports grades.
        $coursemodulesskins = [];
        foreach ($skins as $skin) {
            if ($skin->require_grade() && !$isgraded) {
                continue;
            }
            $coursemodulesskins[$skin->id] = $skin;
        }

        // Return filtered skins.
        return $coursemodulesskins;
    }

    /**
     * Get the first available skin for a course module.
     *
     * @return mixed
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_default_skin() {

        // Default skin for section 0 is inline for resources and menubar for activities.
        if ($this->section->section == 0) {
            $modname = $this->cminfo->modname;
            $isresource = $modname ? plugin_supports('mod', $modname, FEATURE_MOD_ARCHETYPE, false) : false;
            return $isresource ? coursemodule\inline::get_instance() : coursemodule\menubar::get_instance();
        }

        // Get available skins.
        $skins = $this->get_available_skins();

        // Search one skin available and return it.
        foreach ($skins as $skin) {
            if (!in_array($skin->id, [FORMAT_LUDIC_CM_SKIN_INLINE_ID])) {
                return $skin;
            }
        }

        // No skins found, return inline by default.
        return coursemodule\inline::get_instance();
    }

    /**
     * Get course module skin relation record ('format_ludic_cm').
     * If exists return it, else create one.
     *
     * @return \stdClass
     * @throws \dml_exception
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function get_skin_relation() {
        // Get data.
        $dbapi    = $this->contexthelper->get_database_api();
        $dbrecord = $dbapi->get_format_ludic_cm_by_cmid($this->id);

        // If we found relation record, return it.
        if ($dbrecord) {
            return $dbrecord;
        }

        // Create one record with default values.
        $skin               = $this->get_default_skin();
        $dbrecord           = new \stdClass();
        $dbrecord->courseid = $this->courseid;
        $dbrecord->cmid     = $this->id;
        $dbrecord->skinid   = $skin->id;
        $dbrecord->weight   = format_ludic_get_default_weight();
        $dbrecord->access   = 1;
        $newid              = $dbapi->add_format_ludic_cm_record($dbrecord);

        // Return record.
        return $dbrecord;
    }

    /**
     * Return an array of stdClass with grade and completion state.
     *
     * @return \stdClass[]
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_user_results() {

        // Results already calculated, return them.
        if ($this->results !== null) {
            return $this->results;
        }

        // Get user data from data api.
        $datapi = $this->contexthelper->get_data_api();

        // Get grade.
        $grade = $datapi->get_course_module_user_grade($this->cminfo);

        // Get completion.
        $state = $datapi->get_course_module_user_completion($this->cminfo);

        // Return data.
        $this->results = [
                'gradeinfo'      => $grade,
                'completioninfo' => $state
        ];

        return $this->results;
    }

    /**
     * Get course module weight.
     *
     * @return int
     */
    public function get_weight() {
        return (int) $this->weight;
    }

    /**
     * Get skin title.
     *
     * @return string
     */
    public function get_skinned_tile_title() {
        return $this->name;
    }

    /**
     * Get course module link.
     *
     * @return string
     */
    public function get_link() {
        global $CFG;
        return $CFG->wwwroot . '/mod/' . $this->cminfo->modname . '/view.php?id=' . $this->id;
    }

    /**
     * Get sequence for collection skin.
     *
     * @return array
     */
    public function get_collection_sequence() {
        return [];
    }

}
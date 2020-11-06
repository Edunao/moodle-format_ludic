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

// Define an addition constant for the 'perfect' completion state.
define('COMPLETION_COMPLETE_PERFECT', 100);

require_once(__DIR__ . '/model.php');
require_once(__DIR__ . '/../data/skin_manager.php');
require_once(__DIR__ . '/skinnable_interface.php');
require_once(__DIR__ . '/course_module_skin_types/menubar.php');

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
    private $targetmin;
    private $targetmax;
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
        $this->skinid    = $skinrelation->skinid;
        $this->weight    = $skinrelation->weight;
        $this->targetmin = $skinrelation->targetmin;
        $this->targetmax = $skinrelation->targetmax;
        $this->access    = $skinrelation->access;
        $this->skin      = skin_manager::get_instance()->skin_course_module($this->skinid, $this);
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
        $section = $this->contexthelper->get_section_by_id($this->sectionid);
        $sequence    = $section->sequence;
        $newsequence = [];
        foreach ($sequence as $key => $id) {
            if ($id != $cmidtomove) {
                $newsequence[] = $id;
            }
            if ($id == $aftercmid) {
                $newsequence[] = $cmidtomove;
            }
        }
        $section->update_sequence($newsequence);
    }

    public function update_cm_order($cmidtomove, $newindex) {
        $section = $this->contexthelper->get_section_by_id($this->sectionid);

        $sequence    = $section->sequence;
        $newsequence = [];
        $newsequence[$newindex] = $cmidtomove;
        foreach ($sequence as $index => $cmid) {
            if ($cmid == $cmidtomove) {
                continue;
            }

            if ($index < $newindex) {
                $newsequence[$index] = $cmid;
            }

            if ($index >= $newindex) {
                $newsequence[($index + 1)] = $cmid;
            }
        }
        ksort($newsequence);
        $section->update_sequence($newsequence);
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

        // Copy skin, weight, target weights and access from this course module.
        $dbapi->set_format_ludic_cm($this->courseid, $newcm->id, $this->skinid, $this->weight, $this->targetmin, $this->targetmax, $this->access);

        // Move course module to end.
        if ($movetoend) {
            $section = $this->contexthelper->get_section_by_id($this->sectionid);
            $sequence = $section->sequence;
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
        $section = $this->contexthelper->get_section_by_id($this->sectionid);
        // Defines url here.
        $editcmurl   = $CFG->wwwroot . '/course/modedit.php?update=' . $this->id . '&return=0';
        $deletecmurl = $CFG->wwwroot . '/course/mod.php?sesskey=' . sesskey() . '&sr=' . $section->section
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
                'identifier' => 'edit',
                'order'      => 3,
                'isdropdown' => true,
                'action'     => 'showSubButtons',
                'subbuttons' => [
                    [
                        'identifier' => 'edit-settings',
                        'action'     => 'getDataLinkAndRedirectTo',
                        'link'       => $editcmurl
                    ],
                    [
                        'identifier' => 'duplicate',
                        'controller' => 'section',
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
        if ($data['id'] != $this->id) {
            return false;
        }

        // Update name if required.
        if ($data['name'] !== $this->name) {
            $dbapi->update_course_module_name($this->id, $data['name']);
        }

        // Update visibility if needed.
        if ($data['visible'] !== $this->name) {
            $dbapi->update_course_module_visible($this->id, $data['visible']);
        }

        // Update parameters if required.
        $havechanges = false;
        $havechanges = $havechanges || ($data['skinid'] != $this->skinid);
        $havechanges = $havechanges || ($data['weight'] != $this->weight);
        $havechanges = $havechanges || ($data['targetmin'] != $this->targetmin);
        $havechanges = $havechanges || ($data['targetmax'] != $this->targetmax);
        if ($havechanges === true) {
            $dbapi->set_format_ludic_cm($this->courseid, $this->id, $data['skinid'], $data['weight'], $data['targetmin'], $data['targetmax'], 1);
        }

        // Update section.
        if (isset($data['section']) && $data['section'] !== $this->sectionid) {
            $this->move_to_section($data['section']);
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
        $section = $this->contexthelper->get_section_by_id($this->sectionid);
        if ($section->section == 0) {
            return $this->contexthelper->get_global_section_skins();
        }

        $modname = $this->cminfo->modname;
        $skins = $this->contexthelper->get_course_module_skins();

        // Filter skins is cm has no grade or is inline activity.
        $isinline = $modname ? plugin_supports('mod', $modname, FEATURE_NO_VIEW_LINK, false) : false;
        $coursemodulesskins = [];
        foreach ($skins as $key => $skin) {
            if ($skin->type == 'inline') {
                $coursemodulesskins[$skin->id] = $skin;
                continue;
            }
            if ($isinline && $skin->type != 'inline') {
                continue;
            }

            $coursemodulesskins[$skin->id] = $skin;
        }

        return $coursemodulesskins;
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

        if ($dbrecord) {

            // Check if skin exist and put default skin if needed.
            if (!skin_manager::get_instance()->get_course_module_skin($dbrecord->skinid)) {
                $section = $this->contexthelper->get_section_by_id($this->sectionid);
                $defaultskin = skin_manager::get_instance()->get_course_module_default_skin(
                    $section->section,
                    $this->cminfo->modname
                );
                $dbrecord->skinid = $defaultskin->id;
                $dbapi->set_format_ludic_cm(
                    $dbrecord->courseid,
                    $dbrecord->cmid,
                    $dbrecord->skinid,
                    $dbrecord->weight,
                    $dbrecord->targetmin,
                    $dbrecord->targetmax,
                    $dbrecord->access
                );
            }

            return $dbrecord;
        }

        // Create one record with default values.
        $section = $this->contexthelper->get_section_by_id($this->sectionid);
        $skin = skin_manager::get_instance()->get_course_module_default_skin($section->section, $this->cminfo->modname);
        $dbrecord            = new \stdClass();
        $dbrecord->courseid  = $this->courseid;
        $dbrecord->cmid      = $this->id;
        $dbrecord->skinid    = $skin->id;
        $dbrecord->weight    = format_ludic_get_default_weight();
        $dbrecord->targetmin = 0;
        $dbrecord->targetmax = 0;
        $dbrecord->access    = 1;
        $dbapi->add_format_ludic_cm_record($dbrecord);

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
        $dataapi = $this->contexthelper->get_data_api();

        // Get grade.
        $grade = $dataapi->get_course_module_user_grade($this->cminfo);

        // Get completion.
        $state = $dataapi->get_course_module_user_completion($this->cminfo);

        // If the grademax is 0 then base the grade on the completion.
        if ($grade->grademax == 0 && $state->type != COMPLETION_DISABLED) {
            $grade->grademax   = 1;
            $grade->grade      = ($state->state == COMPLETION_COMPLETE || $state->state == COMPLETION_COMPLETE_PASS) ? 1 : 0;
            $grade->proportion = $grade->grade;
        }

        // If achievement tracking is dissabled then try to base the achievement on the grade.
        if ($state->type == COMPLETION_DISABLED && $grade->grademax != 0) {
            $state->state = ($grade->grade == $grade->grademax) ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }

        // Add a default score value to the result as WEIGHT * proprtion, rounded to a nice round number.
        $grade->score = ceil($grade->proportion * 20) * $this->weight / 20;

        // Determine whether we have a perfect result.
        $state->richstate = $state->state;
        if ($state->richstate == COMPLETION_COMPLETE) {
            $state->richstate = COMPLETION_COMPLETE_PASS;
        }
        if ($state->richstate == COMPLETION_COMPLETE_PASS && $grade->grade == $grade->grademax) {
            $state->richstate = COMPLETION_COMPLETE_PERFECT;
        }

        // Return data.
        $this->results = (object)((array) $grade + (array) $state);

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
     * Get course module target min value.
     *
     * @return int
     */
    public function get_targetmin() {
        return (int) $this->targetmin;
    }

    /**
     * Get course module target max value.
     *
     * @return int
     */
    public function get_targetmax() {
        return (int) $this->targetmax;
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

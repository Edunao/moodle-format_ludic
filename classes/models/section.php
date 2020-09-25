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

class section extends model implements skinnable_interface {

    private $course = null;
    private $results;

    public $dbrecord;
    public $courseid;
    public $section;
    public $sectioninfo;
    public $name;
    public $sequence;
    public $visible;
    public $coursemodules;
    public $skinid;
    public $skin;
    public $contextview;

    /**
     * section constructor.
     *
     * @param $section \stdClass course_sections record
     * @throws \moodle_exception
     */
    public function __construct($section) {
        parent::__construct($section);

        // Section properties.
        $this->dbrecord    = $section;
        $this->courseid    = $section->course;
        $this->section     = $section->section;
        $this->name        = $section->name == '' ? get_string('sectionname', 'format_ludic') . ' ' . $section->section : $section->name ;
        $this->sequence    = array_filter(explode(',', $section->sequence));
        $this->visible     = $section->visible;
        $courseinfo        = $this->contexthelper->get_course_info();
        $this->sectioninfo = $courseinfo->get_section_info($this->section);

        // Ludic properties.
        // Section 0 has no skin.
        if ($section->section != 0) {
            $skinrelation = $this->get_section_skin_relation();
            $this->skinid = $skinrelation->skinid;
            $this->skin   = skin::get_by_id($this->skinid, $this);
        }
    }

    /**
     * @return string
     * @throws \coding_exception
     */
    public function get_title() {
        if ($this->section == 0) {
            return get_string('section0name', 'format_ludic');
        }
        $defaulttitle = get_string('default-section-title', 'format_ludic', $this->section);
        return !empty($this->name) ? $this->name : $defaulttitle;
    }

    /**
     * Get all ludic course modules of section.
     *
     * @return course_module[]
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_course_modules() {
        $this->coursemodules = [];
        $coursemodules       = $this->contexthelper->get_course_modules();

        if (!$coursemodules) {
            return $this->coursemodules;
        }

        // Take all course modules from sequence.
        foreach ($this->sequence as $order => $cmid) {
            foreach ($coursemodules as $coursemodule) {
                if ($coursemodule->id == $cmid) {
                    $coursemodule->order   = $order;
                    $this->coursemodules[$cmid] = $coursemodule;
                }
            }
        }

        return $this->coursemodules;
    }

    /**
     * Update section sequence.
     *
     * @param $newsequence
     * @throws \dml_exception
     * @throws \moodle_exception
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
     * @throws \dml_exception
     */
    public function move_section_to($sectionidx) {
        $moodlecourse = $this->get_course()->moodlecourse;
        return move_section_to($moodlecourse, $this->section, $sectionidx);
    }

    /**
     * Get ludic course.
     *
     * @return course
     * @throws \dml_exception
     */
    public function get_course() {
        if ($this->course == null) {
            $this->course = $this->contexthelper->get_course_by_id($this->courseid);
        }
        return $this->course;
    }

    /**
     * @return mixed
     * @throws \dml_exception
     */
    public function get_moodle_course() {
        return $this->get_course()->moodlecourse;
    }

    /**
     * @param $data
     * @return bool
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function update($data) {
        $dbapi        = $this->contexthelper->get_database_api();
        $moodlecourse = $this->get_moodle_course();

        if (!isset($data['id']) || $data['id'] != $this->id) {
            return false;
        }
        if (isset($data['name']) && $data['name'] != $this->dbrecord->name || isset($data['visible']) && $data['visible'] != $this->dbrecord->visible) {
            $dbapi->update_section($moodlecourse, $this->dbrecord, $data);
        }

        if (isset($data['skinid']) && $data['skinid'] != $this->skinid) {
            $dbapi->set_section_skin_id($this->courseid, $this->id, $data['skinid']);
        }

        return true;
    }

    /**
     * A section is can be deleted if she has no course modules.
     *
     * @return bool
     */
    public function has_course_modules() {
        return count($this->sequence) > 0;
    }

    /**
     * Edit buttons :
     * Button 1 : Save form.
     * Button 2 : Revert form.
     * Button 3 : Edit (open sub buttons).
     * Button 3 - 1 : Edit settings.
     * Button 3 - 2 : Duplicate.
     * Button 3 - 3 : Delete.
     * Button 4 : Preview.
     *
     * @return array
     */
    public function get_edit_buttons() {
        global $CFG;

        // Disabled all buttons for section 0.
        $disabled = $this->section == 0;

        $baseurl        = $CFG->wwwroot . '/course/editsection.php?id=' . $this->id;
        $editsectionurl = $baseurl . '&sr=' . $this->section;

        // Submit form button.
        $savebutton = [
            'identifier' => 'form-save',
            'action'     => !$disabled ? 'saveForm' : '',
            'order'      => 1,
            'disabled'   => $disabled
        ];

        // Revert form button.
        $revertbutton = [
            'identifier' => 'form-revert',
            'action'     => !$disabled ? 'revertForm' : '',
            'order'      => 2,
            'disabled'   => $disabled
        ];

        if ($disabled) {
            $editbuttons = [
                'identifier' => 'edit-settings',
                'action'     => 'getDataLinkAndRedirectTo',
                'link'       => $editsectionurl,
                'order'      => 3,
            ];
        } else {
            // Edit buttons : section settings, duplicate section, delete section.
            $editbuttons               = [
                'identifier' => 'edit',
                'order'      => 3,
                'isdropdown' => true,
                'action'     => 'showSubButtons'
            ];
            $editbuttons['subbuttons'] = [
                [
                    'identifier' => 'edit-settings',
                    'action'     => 'getDataLinkAndRedirectTo',
                    'link'       => $editsectionurl,
                ],
                [
                    'identifier' => 'duplicate',
                    'controller' => 'section',
                    'action'     => 'duplicate_section',
                    'callback'   => 'displaySections',
                    'itemid'     => $this->id,
                    'disabled'   => $disabled,
                ],
                [
                    'identifier' => 'delete',
                    'action'     => 'confirmAndDeleteSection',
                    'link'       => $baseurl . '&sr=1&delete=1&sesskey=' . sesskey(),
                    'disabled'   => $this->has_course_modules() || $disabled
                ]
            ];
        }

        // Preview student section view button.
        $previewbutton = [
            'identifier' => 'item-preview',
            'controller' => !$disabled ? 'section' : '',
            'action'     => !$disabled ? 'get_section_view_of_student' : '',
            'callback'   => !$disabled ? 'displayPopup' : '',
            'order'      => 4,
            'disabled'   => $disabled
        ];

        return [
            $savebutton,
            $revertbutton,
            $editbuttons,
            $previewbutton
        ];

    }

    /**
     * Duplicate this section.
     *
     * @return section|false
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function duplicate() {
        // Get data.
        $dbapi      = $this->contexthelper->get_database_api();
        $course     = $this->contexthelper->get_course();
        $newsection = $course->create_section();

        // Copy course section name.
        $newsection->name           = $this->get_title() . get_string('duplicate-suffix', 'format_ludic');
        $newsection->dbrecord->name = $newsection->name;
        $dbapi->update_section_record($newsection->dbrecord);

        // Copy skin.
        if ($this->skinid) {
            $dbapi->set_section_skin_id($this->courseid, $newsection->id, $this->skinid);
            $newsection->skinid = $this->skinid;
            $newsection->skin   = $this->skin;
        }

        // Copy course modules.
        $coursemodules = $this->get_course_modules();
        $course        = $this->get_moodle_course();
        foreach ($coursemodules as $coursemodule) {
            $newcm = $coursemodule->duplicate($course);
            $newcm->move_to_section($newsection->id);
        }

        // Rebuild cache to add new course section in it.
        rebuild_course_cache($this->courseid, true);
        $this->contexthelper->rebuild_course_info();

        return $newsection;
    }

    /**
     * Get course section skin relation record ('format_ludic_cs').
     * If exists return it, else create one.
     *
     * @return \stdClass
     * @throws \dml_exception
     */
    public function get_section_skin_relation() {
        // Get data.
        $dbapi    = $this->contexthelper->get_database_api();
        $dbrecord = $dbapi->get_format_ludic_cs_by_sectionid($this->id);

        // If we found relation record, return it.
        if ($dbrecord) {
            if(!$this->contexthelper->get_skin_by_id($dbrecord->skinid)){
                $defaultskin = $this->get_default_skin();
                $dbrecord->skinid = $defaultskin->id;
                $dbapi->set_section_skin_id($dbrecord->courseid, $dbrecord->sectionid, $dbrecord->skinid);
            }

            return $dbrecord;
        }

        // Create one record with default values.
        $skin                = $this->get_default_skin();
        $dbrecord            = new \stdClass();
        $dbrecord->courseid  = $this->courseid;
        $dbrecord->sectionid = $this->id;
        $dbrecord->skinid    = $skin->id;
        $newid               = $dbapi->add_format_ludic_cs_record($dbrecord);

        // Return record.
        return $dbrecord;
    }

    /**
     * Return the first section skin.
     *
     * @return skin
     */
    public function get_default_skin() {
        return current($this->contexthelper->get_section_skins());
    }

    /**
     * Return section description.
     *
     * @return string
     */
    public function get_description() {
        return $this->sectioninfo->summary;
    }

    /**
     * Return an array of stdClass with grade info and completion info.
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

        // The section completion is a summary of all these course modules.
        $coursemodules = $this->get_course_modules();

        // Initialize completion info.
        $completioninfo = [
            'completion-incomplete'    => [
                'state'    => COMPLETION_INCOMPLETE,
                'count'    => 0,
                'sequence' => []
            ],
            'completion-complete'      => [
                'state'    => COMPLETION_COMPLETE,
                'count'    => 0,
                'sequence' => []
            ],
            'completion-complete-pass' => [
                'state'    => COMPLETION_COMPLETE_PASS,
                'count'    => 0,
                'sequence' => []
            ],
            'completion-complete-fail' => [
                'state'    => COMPLETION_COMPLETE_FAIL,
                'count'    => 0,
                'sequence' => []
            ],
            'perfect'                  => count($coursemodules) > 0
        ];

        $resultsdetails = [

        ];

        $score    = 0;
        $scoremax = 0;
        foreach ($coursemodules as $coursemodule) {
            if (method_exists($coursemodule->skin, 'get_score')) {
                $score    += $coursemodule->skin->get_score();
                $scoremax += $coursemodule->skin->get_weight();
            }

            // Update completion info.
            $results          = $coursemodule->get_user_results();
            $resultsdetails[] = [
                "cmid"    => $coursemodule->id,
                "results" => $results,
            ];

            $data = $results['completioninfo'];
            if (!isset($completioninfo[$data->completion])) {
                continue;
            }
            $completioninfo['perfect'] = $completioninfo['perfect'] && $completioninfo[$data->completion]['state'] == COMPLETION_COMPLETE_PASS;
            $completioninfo[$data->completion]['count']++;
            $completioninfo[$data->completion]['sequence'][] = $coursemodule->id;
        }

        // Return data.
        $this->results = [
            'gradeinfo'      => (object) [
                'score'      => $score,
                'scoremax'   => $scoremax,
                'proportion' => $scoremax > 0 ? ($score / $scoremax) : 0
            ],
            'completioninfo' => $completioninfo,
            'resultsdetails' => $resultsdetails,
        ];

        return $this->results;
    }

    public function get_user_skin_data($userid) {
        return $this->contexthelper->get_database_api()->get_section_user_skin_data($this->id, $userid);
    }

    public function update_user_skin_data($userid, $data) {
        return $this->contexthelper->get_database_api()->update_section_user_skin_data($this->courseid, $this->id, $userid, $data);
    }

    /**
     * Get section weight (total of all course modules weight).
     *
     * @return int
     * @throws \dml_exception
     */
    public function get_weight() {
        $dbapi        = $this->contexthelper->get_database_api();
        $sequencelist = "'" . join("','", $this->sequence) . "'";
        $weight       = $dbapi->get_section_weight($sequencelist);
        return $weight ? $weight : format_ludic_get_default_weight();
    }

    /**
     * Get skin title.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_skinned_tile_title() {
        return $this->get_title();
    }

    /**
     * Get sequence for collection skin.
     * Array of index => id.
     * Index must begin by 1.
     *
     * @return array
     */
    public function get_collection_sequence() {

        $sequence = [];

        foreach ($this->sequence as $key => $id) {

            $sequence[$key + 1] = $id;
        }
        return $sequence;
    }

}
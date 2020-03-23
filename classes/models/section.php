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

    private $course = null;

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
        $this->name        = $section->name;
        $this->sequence    = array_filter(explode(',', $section->sequence));
        $this->visible     = $section->visible;
        $courseinfo        = $this->contexthelper->get_course_info();
        $this->sectioninfo = $courseinfo->get_section_info($this->section);

        // Ludic properties.
        $skinrelation = $this->get_section_skin_relation();
        $this->skinid = $skinrelation->skinid;
        $this->skin   = skin::get_by_id($this->skinid);
    }

    /**
     * @return string
     * @throws \coding_exception
     */
    public function get_title() {
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
                    $this->coursemodules[] = $coursemodule;
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

        if (!isset($data['id']) || $data['id'] !== $this->id) {
            return false;
        }
        if (isset($data['name']) && $data['name'] !== $this->dbrecord->name
            || isset($data['visible']) && $data['visible'] !== $this->dbrecord->visible
        ) {
            $dbapi->update_section($moodlecourse, $this->dbrecord, $data);
        }

        if (isset($data['skinid']) && $data['skinid'] !== $this->skinid) {
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
        $baseurl        = $CFG->wwwroot . '/course/editsection.php?id=' . $this->id;
        $editsectionurl = $baseurl . '&sr=' . $this->section;

        // Define delete button.
        $deletebutton = ['identifier' => 'delete'];

        // Check if section can be deleted.
        if (!$this->has_course_modules()) {
            $deletebutton['link']   = $baseurl . '&sr=1&delete=1&sesskey=' . sesskey();
            $deletebutton['action'] = 'confirmAndDeleteSection';
        } else {
            // Section can not be deleted, disable this button.
            $deletebutton['disabled'] = true;
        }

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
                                        'link'       => $editsectionurl
                                ],
                                [
                                        'identifier' => 'duplicate', 'controller' => 'section', 'action' => 'duplicate_section',
                                        'callback'   => 'displaySections', 'itemid' => $this->id
                                ],
                                $deletebutton
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

    public function get_description() {
        return $this->sectioninfo->summary;
    }
}
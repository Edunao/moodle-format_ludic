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
        $dbapi             = $this->contexthelper->get_database_api();
        $this->dbrecord    = $section;
        $this->courseid    = $section->course;
        $this->section     = $section->section;
        $this->name        = $section->name;
        $this->sequence    = array_filter(explode(',', $section->sequence));
        $this->visible     = $section->visible;
        $modinfo           = $this->contexthelper->get_fast_modinfo();
        $this->sectioninfo = $modinfo->get_section_info($this->section);
        $this->skinid      = $dbapi->get_skin_id_by_section_id($this->id);
        if ($this->skinid) {
            $this->skin = skin::get_by_id($this->skinid);
        }
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
        $moodlecourse = $this->get_course()->moodlecourse;
        return move_section_to($moodlecourse, $this->section, $sectionidx);
    }

    /**
     * Get ludic course.
     *
     * @return course
     */
    public function get_course() {
        if ($this->course == null) {
            $this->course = $this->contexthelper->get_course_by_id($this->courseid);
        }
        return $this->course;
    }

    public function get_moodle_course() {
        $course = $this->get_course();
        return $course->moodlecourse;
    }

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

    public function has_course_modules() {
        return count($this->sequence) > 0;
    }

    public function get_edit_buttons() {
        global $CFG;

        $editsectionurl = $CFG->wwwroot . '/course/editsection.php?id=' . $this->id . '&sr=' . $this->section;

        // Check if section can be deleted.
        $deletebutton = ['identifier' => 'delete'];
        if (!$this->has_course_modules()) {
            $deletebutton['link']   = $CFG->wwwroot . '/course/editsection.php?id=' . $this->id . '&sr=1&delete=1&sesskey=' .
                                      sesskey();
            $deletebutton['action'] = 'confirmAndDeleteSection';
        } else {
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
        $dbapi      = $this->contexthelper->get_database_api();
        $newsection = $this->contexthelper->create_section($this->courseid);

        // Copy course section name.
        $newsection->name = $this->get_title() . get_string('duplicate-suffix', 'format_ludic');
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

        rebuild_course_cache($this->courseid, true);
        return $newsection;
    }

}
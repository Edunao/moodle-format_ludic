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
 * This file contains main class for the course format Ludic
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
    public $defaultname = 'Section';
    public $skinid;

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

    public function get_course_modules() {
        $dataapi  = $this->contexthelper->get_data_api();
        $courseid = $this->courseid;
        $userid   = $this->contexthelper->get_user_id();
        return $dataapi->get_section_course_modules($courseid, $userid, $this->id);
    }

    public function update_sequence($newsequence) {
        $this->sequence = $newsequence;

        $newsequencestr      = implode(',', $newsequence);
        $dbsection           = $this->dbrecord;
        $dbsection->sequence = $newsequencestr;
        $dbapi               = $this->contexthelper->get_database_api();
        return $dbapi->update_section($dbsection);
    }

    public function move_section_to($sectionidx) {
        $course = $this->get_course()->moodlecourse;
        return move_section_to($course, $this->section, $sectionidx);
    }

    public function get_course() {
        $dataapi      = $this->contexthelper->get_data_api();
        $this->course = $dataapi->get_course_by_id($this->courseid);
        return $this->course;
    }

    public function render_form() {
        $form = new section_form($this->id);
        return $form->render();
    }

    public function render_edit_buttons() {
        global $PAGE, $CFG;
        $renderer       = $PAGE->get_renderer('format_ludic');
        $editsectionurl = $CFG->wwwroot . '/course/editsection.php?id=' . $this->id;
        $buttons        = [];
        $buttons[]      = [
                'identifier' => 'form-save',
                'order'      => 1
        ];
        $buttons[]      = [
                'identifier' => 'form-revert',
                'order'      => 2
        ];
        $buttons[]      = [
                'identifier' => 'item-preview',
                'order'      => 4
        ];
        $buttons[]      = [
                'identifier'    => 'edit',
                'order'         => 3,
                'hassubbuttons' => true,
                'subbuttons'    => [
                        ['identifier' => 'edit-settings', 'link' => $editsectionurl],
                        ['identifier' => 'duplicate'],
                        ['identifier' => 'delete']
                ]
        ];
        return $renderer->render_buttons($buttons, $this->id, 'section');
    }

    public function update($data) {
        if (!isset($data['id']) || $data['id'] !== $this->dbrecord->id) {
            return false;
        }
        if (isset($data['name']) && $data['name'] !== $this->dbrecord->name) {
            $this->dbrecord->name = $data['name'];
            $this->contexthelper->get_database_api()->update_section($this->dbrecord);
            $this->trigger_update();
        }
        return true;
    }

    /**
     * Trigger update event when the page is updated
     */
    public function trigger_update() {
        $event = \core\event\course_section_updated::create(
                array(
                        'objectid' => $this->id,
                        'courseid' => $this->courseid,
                        'context'  => $this->get_context(),
                        'other'    => array('sectionnum' => $this->section)
                )
        );
        $event->trigger();
    }

    public function get_context() {
        return \context_course::instance($this->courseid);
    }
}
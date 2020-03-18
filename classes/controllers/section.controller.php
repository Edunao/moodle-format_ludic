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
 * Section controller class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/ludic/lib.php');

class section_controller extends controller_base {

    /**
     * Execute an action
     *
     * @return false|string
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function execute() {
        $action = $this->get_param('action');
        switch ($action) {
            case 'validate_form' :
                $sectionid = $this->get_param('id', PARAM_INT);
                $data      = $this->get_param('data');
                return $this->validate_form($sectionid, $data);
            case 'move_to_section' :
                $cmid      = $this->get_param('idtomove', PARAM_INT);
                $sectionid = $this->get_param('toid', PARAM_INT);
                return $this->move_to_section($cmid, $sectionid);
            case 'move_on_section' :
                $cmidtomove = $this->get_param('idtomove', PARAM_INT);
                $aftercmid  = $this->get_param('toid', PARAM_INT);
                return $this->move_on_section($cmidtomove, $aftercmid);
            case 'move_section_to' :
                $sectionidtomove = $this->get_param('idtomove', PARAM_INT);
                $aftersectionid  = $this->get_param('toid', PARAM_INT);
                return $this->move_section_to($sectionidtomove, $aftersectionid);
            case 'duplicate_section' :
                $sectionid = $this->get_param('id', PARAM_INT);
                return $this->duplicate_section($sectionid);
            case 'duplicate_course_module' :
                $cmid = $this->get_param('id', PARAM_INT);
                return $this->duplicate_course_module($cmid);
            case 'get_properties' :
                $sectionid = $this->get_param('id', PARAM_INT);
                return $this->get_properties($sectionid);
            case 'get_course_modules' :
                $sectionid = $this->get_param('id', PARAM_INT);
                return $this->get_course_modules($sectionid);
            case 'delete_section_skin_id' :
                $sectionid = $this->get_param('id', PARAM_INT);
                return $this->delete_section_skin_id($sectionid);
            // Default case if no parameter is necessary.
            default :
                return $this->$action();
        }
    }

    /**
     * Return sections html.
     *
     * @return string
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_course_sections() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');
        $course   = $this->contexthelper->get_course();
        $sections = $course->get_sections();

        // Render sections.
        $output = '';
        foreach ($sections as $section) {
            $output .= $renderer->render_section($section);
        }

        // Add section button.
        $output .= $renderer->render_add_section_button($course->id, count($sections) + 1);

        // Render container for course modules.
        $output .= $renderer->render_container_children('coursemodules');
        return $output;
    }

    /**
     * Return course modules html.
     *
     * @param int $sectionid
     * @return string
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_course_modules($sectionid, $selectedcmid = false) {
        global $PAGE;
        $section    = $this->contexthelper->get_section_by_id($sectionid);
        $course     = $section->get_course()->moodlecourse;
        $sectionidx = $section->section;

        $coursemodules = $section->get_course_modules();

        $output   = '';
        $renderer = $PAGE->get_renderer('format_ludic');
        foreach ($coursemodules as $order => $coursemodule) {
            $coursemodule->order = $order;
            $coursemodule        = new \format_ludic_course_module($coursemodule);

            // Selected course module.
            if ($selectedcmid && $selectedcmid == $coursemodule->id) {
                $coursemodule->selected = true;
            }

            $output .= $renderer->render($coursemodule);
        }

        $output .= $renderer->render_modchooser($course, $sectionidx, count($coursemodules));

        return $output;
    }

    /**
     * Return section form with edit buttons.
     *
     * @param $sectionid
     * @return string
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_properties($sectionid) {
        global $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');

        // Get edit buttons.
        $section     = $this->contexthelper->get_section_by_id($sectionid);
        $editbuttons = $section->get_edit_buttons();

        // Render section form with edit buttons.
        $output = $renderer->render_section_form($sectionid);
        $output .= $renderer->render_buttons($editbuttons, $section->id, 'section');
        return $output;
    }

    /**
     * Move a course module to a section.
     * Return course modules html.
     *
     * @param $cmid
     * @param $sectionid
     * @return string
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function move_to_section($cmid, $sectionid) {
        $coursemodule = $this->contexthelper->get_course_module_by_id($cmid);
        $oldsectionid = $coursemodule->sectionid;
        $isvisible    = $coursemodule->move_to_section($sectionid);
        return $this->get_course_modules($oldsectionid);
    }

    /**
     * Move course module on section, change order.
     * Return course modules html.
     *
     * @param $cmidtomove
     * @param $aftercmid
     * @return string
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function move_on_section($cmidtomove, $aftercmid) {
        $coursemodule = $this->contexthelper->get_course_module_by_id($cmidtomove);
        $sectionid    = $coursemodule->sectionid;
        $coursemodule->move_on_section($cmidtomove, $aftercmid);
        return $this->get_course_modules($sectionid);
    }

    /**
     * Move section on course, change order.
     * Return sections html.
     *
     * @param $sectionidtomove
     * @param $aftersectionid
     * @return string
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function move_section_to($sectionidtomove, $aftersectionid) {
        $sectiontomove   = $this->contexthelper->get_section_by_id($sectionidtomove);
        $aftersection    = $this->contexthelper->get_section_by_id($aftersectionid);
        $aftersectionidx = $aftersection->section;
        $sectiontomove->move_section_to($aftersectionidx);
        return $this->get_course_sections();
    }

    /**
     * Validate form.
     * If everything is valid => update and return a success message.
     * Else does not update and return an error message.
     *
     * @param $sectionid
     * @param $data
     * @return false|string
     * @throws \coding_exception
     */
    public function validate_form($sectionid, $data) {
        $form    = new section_form($sectionid);
        $success = $form->validate_and_update($data);
        if ($success) {
            $return = array('success' => 1, 'value' => $form->get_success_message());
        } else {
            $return = array('success' => 0, 'value' => $form->get_error_message());
        }
        return json_encode($return);
    }

    /**
     * Duplicate section from id = $sectionid
     * Return sections html
     *
     * @param $sectionid
     * @return string
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function duplicate_section($sectionid) {
        $section = $this->contexthelper->get_section_by_id($sectionid);

        if (!$section) {
            return false;
        }

        $newsection = $section->duplicate();

        // Trigger event update course.
        $event = \core\event\course_updated::create(array('context' => $this->get_context(), 'objectid' => $newsection->courseid));
        $event->trigger();

        return $this->get_course_sections();
    }

    /**
     * Duplicate course module from id = $cmid
     * Return course modules html
     *
     * @param $cmid
     * @return string
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function duplicate_course_module($cmid) {
        $coursemodule = $this->contexthelper->get_course_module_by_id($cmid);

        if (!$coursemodule) {
            return false;
        }

        $course          = $this->contexthelper->get_course_by_id($coursemodule->courseid)->moodlecourse;
        $newcoursemodule = $coursemodule->duplicate($course, true);

        // Trigger event update course.
        $event = \core\event\course_updated::create(array('context'  => $this->get_context(),
                                                          'objectid' => $newcoursemodule->courseid
        ));
        $event->trigger();

        // Reset cache.
        rebuild_course_cache($this->get_course_id(), true);
        $this->contexthelper->rebuild_fast_modinfo();

        return $this->get_course_modules($newcoursemodule->sectionid, $newcoursemodule->id);
    }

    /**
     * Remove skin for a given section id.
     *
     * @param $sectionid
     * @return bool
     * @throws \dml_exception
     */
    public function delete_section_skin_id($sectionid) {
        $dbapi = $this->contexthelper->get_database_api();
        return $dbapi->delete_section_skin_id($sectionid);
    }

}

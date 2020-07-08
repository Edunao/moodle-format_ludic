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
            case 'update_cm_order' :
                $cmidtomove = $this->get_param('cmid', PARAM_INT);
                $newindex  = $this->get_param('newindex', PARAM_INT);
                return $this->update_cm_order($cmidtomove, $newindex);
            case 'update_section_order':
                $sectionidtomove = $this->get_param('sectionid', PARAM_INT);
                $newindex  = $this->get_param('newindex', PARAM_INT);
                return $this->update_section_order($sectionidtomove, $newindex);
            case 'move_section_to' :
                $sectionidtomove = $this->get_param('idtomove', PARAM_INT);
                $aftersectionid  = $this->get_param('toid', PARAM_INT);
                return $this->move_section_to($sectionidtomove, $aftersectionid);
            default :
                // Default case if the only parameter is id.
                $id = $this->get_param('id', PARAM_INT);
                return $this->$action($id);
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
        return $renderer->render_course_sections(true);
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

        // Get data.
        $renderer = $PAGE->get_renderer('format_ludic');
        $section  = $this->contexthelper->get_section_by_id($sectionid);
        $course   = $this->contexthelper->get_moodle_course();

        $output = $renderer->render_course_modules($sectionid, $selectedcmid);

        // In edit view, render mod chooser (add a new activity).
        if ($this->contexthelper->is_editing()) {
            $output .= $renderer->render_modchooser($course, $section->section, count($section->sequence));
        }

        // Return html.
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

        // Get data.
        $renderer = $PAGE->get_renderer('format_ludic');
        $section  = $this->contexthelper->get_section_by_id($sectionid);

        // Get edit buttons.
        $editbuttons = $section->get_edit_buttons();

        // Render section form with edit buttons.
        $output = $renderer->render_section_form($sectionid);
        $output .= $renderer->render_buttons($editbuttons, $section->id, 'section');

        // Return html.
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
        // Get course module.
        $coursemodule = $this->contexthelper->get_course_module_by_id($cmid);

        // Keep old section id to render his course modules.
        $oldsectionid = $coursemodule->sectionid;

        // Can't move a course module from section 0.
        if ($coursemodule->section->section > 0) {

            // Move a course module to another section.
            $coursemodule->move_to_section($sectionid);
        }

        // Return course modules html.
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
        // Get course module.
        $coursemodule = $this->contexthelper->get_course_module_by_id($cmidtomove);

        // Keep section id to render his course modules.
        $sectionid = $coursemodule->sectionid;

        // Move a course module after another course module.
        $coursemodule->move_on_section($cmidtomove, $aftercmid);

        // Return course modules html.
        return $this->get_course_modules($sectionid);
    }

    public function update_cm_order($cmid, $newindex) {
        // Get course module.
        $coursemodule = $this->contexthelper->get_course_module_by_id($cmid);

        // Keep section id to render his course modules.
        $sectionid = $coursemodule->sectionid;

        // Move a course module after another course module.
        $coursemodule->update_cm_order($cmid, $newindex);

        // Return course modules html.
        return $this->get_course_modules($sectionid);
    }

    public function update_section_order($sectionidtomove, $newindex) {
        // Get section to move.
        $sectiontomove = $this->contexthelper->get_section_by_id($sectionidtomove);

        if($newindex == 0){
            $newindex++;
        }

        // Can't move section 0.
        if ($sectiontomove->section > 0) {
            $sectiontomove->move_section_to($newindex);
        }

        // Return course section html.
        return $this->get_course_sections();
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
        // Get section to move.
        $sectiontomove = $this->contexthelper->get_section_by_id($sectionidtomove);

        // Can't move section 0.
        if ($sectiontomove->section > 0) {
            // Get section idx to move after.
            $dbapi           = $this->contexthelper->get_database_api();
            $aftersectionidx = $dbapi->get_section_idx_by_id($aftersectionid);

            // Move section after section.
            $sectiontomove->move_section_to($aftersectionidx);
        }

        // Return course section html.
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
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function validate_form($sectionid, $data) {
        // Create form.
        $form = new section_form($sectionid);

        // Update successful or errors ?
        $success = $form->validate_and_update($data);

        // Define return.
        if ($success) {
            $return = array(
                'success' => 1,
                'value'   => $form->get_success_message()
            );
        } else {
            $return = array(
                'success' => 0,
                'value'   => $form->get_error_message()
            );
        }

        // Return a json encode array with success and message.
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
        // Get section.
        $section = $this->contexthelper->get_section_by_id($sectionid);

        // Undefined section or section 0, return false.
        if (!$section || $section->section == 0) {
            return false;
        }

        // Duplicate section.
        $newsection = $section->duplicate();

        // Trigger event update course.
        $data  = [
            'context'  => $this->get_context(),
            'objectid' => $newsection->courseid
        ];
        $event = \core\event\course_updated::create($data);
        $event->trigger();

        // Return course sections html.
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
        // Get course module and $COURSE.
        $coursemodule = $this->contexthelper->get_course_module_by_id($cmid);

        // Undefined course module, return false.
        if (!$coursemodule) {
            return false;
        }

        // Duplicate course module.
        $newcoursemodule = $coursemodule->duplicate(true);

        // Trigger event update course.
        $data  = [
            'context'  => $this->get_context(),
            'objectid' => $newcoursemodule->courseid
        ];
        $event = \core\event\course_updated::create($data);
        $event->trigger();

        // Reset cache to add new course module in course info.
        rebuild_course_cache($this->get_course_id(), true);
        $this->contexthelper->rebuild_course_info();

        // Return course modules html.
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

    /**
     * Render student section view in a popup.
     *
     * @param $sectionid
     * @return mixed
     * @throws \coding_exception
     */
    public function get_section_view_of_student($sectionid) {
        global $PAGE;

        // Force student view.
        $this->contexthelper->enable_student_view();

        // Render section in popup.
        $renderer     = $PAGE->get_renderer('format_ludic');
        $popupcontent = $renderer->render_header_bar();
        $popupcontent .= $renderer->render_section_page($sectionid);
        $popup        = $renderer->render_popup('section-popup', get_string('section-preview', 'format_ludic'), $popupcontent);

        // Disable force student view.
        $this->contexthelper->disable_student_view();

        // Return section in popup.
        return $popup;
    }
}

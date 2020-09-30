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
 * Course module controller class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/controller_base.php');

class coursemodule_controller extends controller_base {

    /**
     * Execute an action
     *
     * @return mixed
     * @throws \moodle_exception
     */
    public function execute() {
        $action = $this->get_param('action');
        switch ($action) {
            case 'validate_form' :
                $cmid = $this->get_param('id', PARAM_INT);
                $data = $this->get_param('data');
                return $this->validate_form($cmid, $data);
            default :
                // Default case if the only parameter is id.
                $id = $this->get_param('id', PARAM_INT);
                return $this->$action($id);
        }
    }

    /**
     * @param $cmid
     * @return string
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_properties($cmid) {
        global $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');

        // Get edit buttons.
        $coursemodule = $this->contexthelper->get_course_module_by_id($cmid);
        $editbuttons  = $coursemodule->get_edit_buttons();

        // Render section form with edit buttons.
        $output = $renderer->render_course_module_form($cmid);
        $output .= $renderer->render_buttons($editbuttons, $coursemodule->id, 'coursemodule');

        return $output;
    }

    /**
     * Validate form.
     * If everything is valid => update and return a success message.
     * Else does not update and return an error message.
     *
     * @param $cmid
     * @param $data
     * @return false|string
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function validate_form($cmid, $data) {
        require_once(__DIR__ . '/../forms/coursemodule_form.php');

        // Create form.
        $form = new coursemodule_form($cmid);

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
     * Delete course module <-> skin relation, then delete course module with moodle function.
     *
     * @param $cmid
     * @return bool
     * @throws \dml_exception
     */
    public function delete_format_ludic_cm($cmid) {
        $dbapi = $this->contexthelper->get_database_api();
        return $dbapi->delete_format_ludic_cm($cmid);
    }

    /**
     * Return html to displaying a popup with label content.
     *
     * @param $cmid
     * @return string
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_label_popup($cmid) {
        global $PAGE;
        // Get coursemodule.
        $coursemodule = $this->contexthelper->get_course_module_by_id($cmid);

        // Check course module is a label.
        if ($coursemodule->cminfo->modname !== 'label') {
            print_error('course module must be a label');
        }

        // Render popup.
        $renderer = $PAGE->get_renderer('format_ludic');
        $popupcontent = label_get_coursemodule_info($coursemodule->cminfo)->content;
        $popup = $renderer->render_popup('label-popup', $coursemodule->name, $popupcontent);

        // Return popup html.
        return $popup;
    }
}

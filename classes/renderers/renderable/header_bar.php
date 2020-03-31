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
 * Additional header bar for ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class format_ludic_header_bar implements renderable {

    /**
     * Context helper.
     *
     * @var \format_ludic\context_helper
     */
    private $contexthelper;

    /**
     * Course modules (from section 0 and with skin = menu bar).
     *
     * @var \format_ludic\course_module[]
     */
    private $coursemodules = null;

    public $optionslist;
    public $hasoptions;
    public $display;

    /**
     * model constructor.
     *
     * @throws moodle_exception
     */
    public function __construct() {
        global $PAGE, $USER;

        $this->contexthelper = \format_ludic\context_helper::get_instance($PAGE);
        $this->optionslist   = $this->get_options_list();
        $this->hasoptions    = count($this->optionslist) > 0;
        $editmode            = $this->contexthelper->is_editing();

        // Don't display header bar in edit mode or if empty.
        $this->display = !$editmode && $this->hasoptions;

        $params = [
                'courseid'  => $this->contexthelper->get_course_id(),
                'userid'    => $USER->id,
                'editmode'  => $editmode,
                'sectionid' => $this->contexthelper->get_section_id()
        ];

        // Requires format ludic javascript here because header bar is present in all pages.
        $PAGE->requires->strings_for_js(format_ludic_get_strings_for_js($editmode), 'format_ludic');
        $PAGE->requires->js('/course/format/ludic/format.js');
        $PAGE->requires->js_call_amd('format_ludic/format_ludic', 'init', ['params' => $params]);

    }

    /**
     * Get a list of all options for drop down menu.
     *
     * @return array
     * @throws moodle_exception
     */
    public function get_options_list() {

        $coursemoduleslist = $this->get_course_modules_list();
        $studentlist       = $this->get_student_options_list();
        $teacherlist       = $this->get_teacher_options_list();

        $list = array_merge($studentlist, $teacherlist, $coursemoduleslist);

        return $list;
    }

    /**
     * Get course modules (from section 0 and with skin = menu bar).
     *
     * @return \format_ludic\course_module[]
     * @throws \moodle_exception
     */
    private function get_course_modules_in_list() {

        // We have stored course modules, then return them.
        if ($this->coursemodules !== null) {
            return $this->coursemodules;
        }

        // Get section 0 course modules.
        $globalsection = $this->contexthelper->get_global_section();
        $coursemodules = $globalsection->get_course_modules();

        // Keep course modules with menu bar skin.
        $this->coursemodules = [];
        foreach ($coursemodules as $coursemodule) {
            if ($coursemodule->skin->id === FORMAT_LUDIC_CM_SKIN_MENUBAR_ID) {
                $this->coursemodules[] = $coursemodule;
            }
        }

        // Store course modules, then return them.
        return $this->coursemodules;
    }

    /**
     * Get list (name, link) of course modules (from section 0 and with skin = menu bar).
     *
     * @return array
     * @throws \moodle_exception
     */
    private function get_course_modules_list() {

        // Initialize list.
        $list = [];

        // Fulfill list with menu bar course modules.
        $coursemodules = $this->get_course_modules_in_list();
        foreach ($coursemodules as $coursemodule) {
            $modicon = $coursemodule->get_mod_icon();
            $islabel = $coursemodule->cminfo->modname === 'label';

            $option = [
                    'iconsrc' => $modicon->imgsrc,
                    'iconalt' => $modicon->imgalt,
                    'name'    => $coursemodule->name,
            ];

            // Don't add action in preview student view.
            if ($this->contexthelper->is_student_view_forced()) {
                $list[] = $option;
                continue;
            }

            // Label is displayed in a popup.
            if ($islabel) {
                $option['controller'] = 'coursemodule';
                $option['action']     = 'get_label_popup';
                $option['callback']   = 'displayPopup';
                $option['id']         = $coursemodule->id;
            } else {
                // Others are just links.
                $option['link']   = $coursemodule->get_link();
                $option['action'] = 'getDataLinkAndRedirectTo';
            }

            $list[] = $option;
        }

        // Return list.
        return $list;

    }

    /**
     * Get student options.
     * - Option : Preview section in course module.
     *
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    private function get_student_options_list() {
        global $CFG;

        // Initialize list.
        $list = [];

        // Exit in different cases.
        if (!$this->contexthelper->user_has_student_role() ||
            $this->contexthelper->get_location() != 'coursemodule') {

            // If current user is not student, don't show options.
            // Add an option only in course module.
            return $list;
        }

        // Student in course module can preview his section.
        $name   = get_string('header-bar-preview-section', 'format_ludic');
        $option = [
                'iconsrc' => $CFG->wwwroot . '/course/format/ludic/pix/view-section.png',
                'iconalt' => $name,
                'name'    => $name,
        ];

        // Don't add action in preview student view.
        if (!$this->contexthelper->is_student_view_forced()) {
            $option['controller'] = 'section';
            $option['action']     = 'get_section_view_of_student';
            $option['callback']   = 'displayPopup';
            $option['id']         = $this->contexthelper->get_section_id();
        }

        $list[] = $option;

        // Return list.
        return $list;

    }

    /**
     * Get teacher options (different for editing teacher).
     * - Option : Toggle to edit mode.
     * - Option : Toggle to student mode.
     *
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    private function get_teacher_options_list() {
        global $OUTPUT, $CFG;
        // Initialize list.
        $list = [];

        $isteacher        = $this->contexthelper->user_has_role_in_course('teacher');
        $iseditingteacher = $this->contexthelper->user_has_role_in_course('editingteacher');
        $isadmin          = $this->contexthelper->is_user_admin();
        $courseid         = $this->contexthelper->get_course_id();
        $isstudent        = $this->contexthelper->user_has_student_role();

        // User must be admin or editing teacher or teacher.
        if (!$isadmin && !$iseditingteacher && !$isteacher) {
            return $list;
        }

        // Options for editing teacher.
        if (!$isstudent && ($isadmin || $iseditingteacher)) {
            $editmode = $this->contexthelper->is_editing();

            // First option : edit mode.
            $editname = $editmode ? get_string('turneditingoff') : get_string('turneditingon');
            $editicon = $OUTPUT->image_url('i/edit')->out();

            $editoption = [
                    'iconsrc' => $editicon,
                    'iconalt' => $editname,
                    'name'    => $editname,
            ];

            // Don't add action in preview student view.
            if (!$this->contexthelper->is_student_view_forced()) {
                $editlink = $CFG->wwwroot . '/course/view.php?id=' . $courseid . '&sesskey=' . sesskey() . '&edit=';
                $editlink .= $editmode ? 'off' : 'on';

                // Add link and action to redirect.
                $editoption['link']   = $editlink;
                $editoption['action'] = 'getDataLinkAndRedirectTo';

            }

            $list[] = $editoption;

        }

        // Options for editing teacher and teacher.
        if ($isadmin || $iseditingteacher || $isteacher) {

            // Switch role for student view option.
            $nameidentifier = $isstudent ? 'header-bar-teacher-view' : 'header-bar-student-view';
            $name           = get_string($nameidentifier, 'format_ludic');

            $switchoption = [
                    'iconsrc' => $CFG->wwwroot . '/course/format/ludic/pix/student-view.svg',
                    'iconalt' => $name,
                    'name'    => $name,
            ];

            // Don't add action in preview student view.
            if (!$this->contexthelper->is_student_view_forced()) {

                // To "unswitch" pass 0 as the roleid. To switch to student pass student role id.
                $switchlink = $CFG->wwwroot . '/course/switchrole.php?id=' . $courseid . '&sesskey=' . sesskey() . '&switchrole=';
                $switchlink .= $isstudent ? '0' :
                        $this->contexthelper->get_database_api()->get_role_id_by_role_shortname('student');

                // Add link and action to redirect.
                $switchoption['link']   = $switchlink;
                $switchoption['action'] = 'getDataLinkAndRedirectTo';

            }

            $list[] = $switchoption;

        }

        // Return list.
        return $list;

    }
}

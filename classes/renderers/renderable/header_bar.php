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
    private $coursemodules;

    public $notstudentview;
    public $optionslist;
    public $hasoptions;
    public $display;
    public $sectionscontent;
    public $sections;

    /**
     * model constructor.
     *
     * @throws moodle_exception
     */
    public function __construct() {
        global $PAGE, $USER, $CFG;

        $this->contexthelper  = \format_ludic\context_helper::get_instance($PAGE);
        $this->optionslist    = $this->get_options_list();
        $this->hasoptions     = count($this->optionslist) > 0;
        $this->notstudentview = !$this->contexthelper->is_student_view_forced();
        $editmode             = $this->contexthelper->is_editing();

        // Sections.
        if (!$editmode) {
            $course         = $this->contexthelper->get_course();
            $this->sections = array_values($course->get_sections(true));
            foreach ($this->sections as $key => $section) {

                if (!$editmode && !$section->visible) {
                    unset($this->sections[$key]);
                    continue;
                }

                if ($section->section == 0) {
                    $section->name = get_string('section0name', 'format_ludic');
                    $section->link = $CFG->wwwroot . '/course/view.php?id=' . $section->courseid;
                } else {
                    $section->name = $section->name != '' ? format_string($section->name, true) : get_string('default-section-title', 'format_ludic', $section->section);
                    $section->link = $CFG->wwwroot . '/course/view.php?id=' . $section->courseid . '&section=' . $section->section;
                }
            }

            $this->sections = array_values($this->sections);
        }

        // Javascript parameters.
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

        // Get all options.
        $coursemoduleslist = $this->get_course_modules_list();
        $studentlist       = $this->get_student_options_list();
        $teacherlist       = $this->get_teacher_options_list();

        // Merge options.
        $list = array_merge($studentlist, $teacherlist, $coursemoduleslist);

        // Return options.
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
            if ($coursemodule->skin->get_type_name() === 'menubar') {
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
        if (!$this->contexthelper->user_has_student_role() || $this->contexthelper->get_domain() != 'coursemodule' || $this->contexthelper->get_section_idx() <= 0) {

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
     * @throws moodle_exception
     */
    private function get_teacher_options_list() {
        global $OUTPUT, $CFG, $PAGE;
        // Initialize list.
        $list = [];

        $isadmin          = $this->contexthelper->is_user_admin();
        $courseid         = $this->contexthelper->get_course_id();
        $context          = context_course::instance($courseid);
        $isstudent        = $this->contexthelper->user_has_student_role();
        $iseditingteacher = has_capability('moodle/course:manageactivities', $context);
        $isteacher        = has_capability('moodle/course:viewhiddencourses', $context);

        // User must be admin or editing teacher or teacher.
        if (!$isadmin && !$iseditingteacher && !$isteacher) {
            return $list;
        }

        // Options for editing teacher and teacher.
        if ($isadmin || $iseditingteacher || $isteacher) {

            if (!$isstudent) {
                if (strpos($PAGE->pagetype, 'view-ludic') === false) {
                    // Edit course.
                    $editname   = get_string('editcourse', 'format_ludic');
                    $editicon   = $OUTPUT->image_url('i/settings')->out();
                    $editlink   = $CFG->wwwroot . '/course/view.php?id=' . $courseid;
                    $editoption = [
                        'action'  => 'getDataLinkAndRedirectTo',
                        'link'    => $editlink,
                        'iconsrc' => $editicon,
                        'iconalt' => $editname,
                        'name'    => $editname,
                    ];
                    $list[]     = $editoption;
                }

                // Edit skins.
                if (strpos($PAGE->pagetype, 'edit_ludic_config') === false) {
                    $editname   = get_string('editskins', 'format_ludic');
                    $editicon   = $OUTPUT->image_url('i/settings')->out();
                    $editlink   = $CFG->wwwroot . '/course/format/ludic/edit_ludic_config.php?id=' . $courseid;
                    $editoption = [
                        'action'  => 'getDataLinkAndRedirectTo',
                        'link'    => $editlink,
                        'iconsrc' => $editicon,
                        'iconalt' => $editname,
                        'name'    => $editname,
                    ];

                    $list[] = $editoption;
                }
            }
        }

        // Return list.
        return $list;
    }
}

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
 * Renderer for outputting the ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/renderer.php');
require_once($CFG->dirroot . '/course/format/ludic/lib.php');

/**
 * Basic renderer for ludic format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ludic_renderer extends format_section_renderer_base {

    /**
     * Generate the starting container html for a list of sections
     *
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('div', array('class' => 'container-parents'));
    }

    /**
     * Generate the closing container html for a list of sections
     *
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('div');
    }

    /**
     * Generate the title for this section page
     *
     * @return string the page title
     */
    protected function page_title() {
        // Old : get_string('topicoutline'); .
        return 'page title';
    }

    /**
     * @param format_ludic_modchooser $popup
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_modchooser(format_ludic_modchooser $modchooser) {
        return $this->render_from_template('format_ludic/modchooser', $modchooser);
    }

    /**
     * @param $course
     * @param $sectionidx
     * @param $order
     * @return string
     */
    public function render_modchooser($course, $sectionidx, $order) {
        $this->page->course->id = $course->id;
        $modchooser = $this->courserenderer->course_section_add_cm_control($course, $sectionidx);
        $modchooser = new format_ludic_modchooser($modchooser, $sectionidx, $order);
        return $this->render($modchooser);
    }

    /**
     * @param format_ludic_popup $popup
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_popup(format_ludic_popup $popup) {
        return $this->render_from_template('format_ludic/popup', $popup);
    }

    /**
     * @param format_ludic_popup $popup
     * @return string
     */
    public function render_popup(format_ludic_popup $popup = null) {
        $popup = $popup ? $popup : new format_ludic_popup();
        return $this->render($popup);
    }

    /**
     * @param format_ludic_buttons $buttons
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_buttons(format_ludic_buttons $buttons) {
        return $this->render_from_template('format_ludic/buttons', $buttons);
    }


    public function render_buttons($buttons, $itemid = null, $type = null) {
        $buttons = new format_ludic_buttons($buttons, $itemid, $type);
        return $this->render($buttons);
    }

    /**
     * @param format_ludic_section $section
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_section(format_ludic_section $section) {
        return $this->render_from_template('format_ludic/section', $section);
    }

    /**
     * @param $section
     * @return string
     */
    public function render_section($section) {
        $section = new format_ludic_section($section);
        return $this->render($section);
    }

    /**
     * @param format_ludic_course_module $coursemodule
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_course_module(format_ludic_course_module $coursemodule) {
        return $this->render_from_template('format_ludic/course_module', $coursemodule);
    }

    /**
     * @param $coursemodule
     * @return string
     */
    public function render_course_module($coursemodule) {
        $coursemodule = new format_ludic_course_module($coursemodule);
        return $this->render($coursemodule);
    }

    /**
     * @param format_ludic_form $form
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_form(format_ludic_form $form) {
        return $this->render_from_template('format_ludic/form', $form);
    }

    /**
     * @param $form
     * @return string
     */
    public function render_form($form) {
        $form = new format_ludic_form($form);
        return $this->render($form);
    }

    public function render_edit_page() {
        return $this->render_from_template('format_ludic/editpage', []);
    }

    public function render_page() {
        return $this->render_from_template('format_ludic/page', []);
    }

    public function render_container_children() {
        return $this->render_from_template('format_ludic/container_children', []);
    }
    /**
     * @param format_ludic_hidden_form_element $element
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_hidden_form_element(format_ludic_hidden_form_element $element) {
        return $this->render_from_template('format_ludic/hidden_form_element', $element);
    }

    /**
     * @param format_ludic_text_form_element $element
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_text_form_element(format_ludic_text_form_element $element) {
        return $this->render_from_template('format_ludic/text_form_element', $element);
    }

    /**
     * @param format_ludic_number_form_element $element
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_number_form_element(format_ludic_number_form_element $element) {
        return $this->render_from_template('format_ludic/number_form_element', $element);
    }

    /**
     * @param format_ludic_checkbox_form_element $element
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_checkbox_form_element(format_ludic_checkbox_form_element $element) {
        return $this->render_from_template('format_ludic/checkbox_form_element', $element);
    }

    /**
     * @param format_ludic_textarea_form_element $element
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_textarea_form_element(format_ludic_textarea_form_element $element) {
        return $this->render_from_template('format_ludic/textarea_form_element', $element);
    }

    /**
     * @param format_ludic_select_form_element $element
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_select_form_element(format_ludic_select_form_element $element) {
        return $this->render_from_template('format_ludic/select_form_element', $element);
    }

    /**
     * @param format_ludic_filepicker_form_element $element
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_filepicker_form_element(format_ludic_filepicker_form_element $element) {
        return $this->render_from_template('format_ludic/filepicker_form_element', $element);
    }

    /**
     * @param format_ludic_selection_popup_form_element $element
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_selection_popup_form_element(format_ludic_selection_popup_form_element $element) {
        return $this->render_from_template('format_ludic/selection_popup_form_element', $element);
    }

    /**
     * @param  $errors
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_form_errors($errors) {
        return $this->render_from_template('format_ludic/form_errors', $errors);
    }
}

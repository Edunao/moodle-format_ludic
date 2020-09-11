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

/**
 * Basic renderer for ludic format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ludic_renderer extends format_section_renderer_base {

    private $contexthelper;

    public function __construct(moodle_page $page, $target) {
        $this->contexthelper = \format_ludic\context_helper::get_instance($page);
        parent::__construct($page, $target);
    }

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
     * Generate the title for this section page.
     * No longer used kept for legacy versions.
     *
     * @return string the page title
     * @throws coding_exception
     */
    protected function page_title() {
        return get_string('topicoutline', 'format_ludic');
    }

    /**
     * @param $id
     * @param string $title
     * @param string $content
     * @return string
     */
    public function render_popup($id, $title = '', $content = '') {
        $popup = new format_ludic_popup($id, $title, $content);
        return $this->render($popup);
    }

    /**
     * @param $id
     * @param string $title
     * @param array $slots
     * @return string
     */
    public function render_avatar_shop($slots = []) {
        $avatarshop = new format_ludic_avatar_shop($slots);
        return $this->render($avatarshop);
    }

    /**
     * @param $course
     * @param $sectionidx
     * @param $order
     * @return string
     */
    public function render_modchooser($course, $sectionidx, $order) {
        $this->page->course->id = $course->id;
        $modchooser             = $this->courserenderer->course_section_add_cm_control($course, $sectionidx);
        $modchooser             = new format_ludic_modchooser($modchooser, $sectionidx, $order);
        return $this->render($modchooser);
    }

    /**
     * @param $sectionid
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function render_section_form($sectionid) {
        $form = new \format_ludic\section_form($sectionid);
        return $form->render();
    }

    /**
     * @param $cmid
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function render_course_module_form($cmid) {
        $form = new \format_ludic\coursemodule_form($cmid);
        return $form->render();
    }

    /**
     * @param $sectionid
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function render_edit_skins_form($courseid, $skin) {
        $output = '';
        $form = new \format_ludic\edit_skins_form($courseid, $skin);
        $output .= $form->render();
        $editbuttons = $skin->get_edit_buttons();
        $output .= $this->render_buttons($editbuttons, $skin->id, 'skin');

        return $output;
    }

    /**
     * @param $buttons
     * @param null $itemid
     * @param null $type
     * @return string
     * @throws coding_exception
     */
    public function render_buttons($buttons, $itemid = null, $type = null) {
        $buttons = new format_ludic_buttons($buttons, $itemid, $type);
        return $this->render($buttons);
    }

    /**
     * @param $courseid
     * @param $order
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_add_section_button($courseid, $order) {
        global $CFG;

        $addsectionurl = $CFG->wwwroot . '/course/changenumsections.php?courseid=' . $courseid . '&insertsection=0&sesskey=' . sesskey() . '&sectionreturn=' . ($order - 1) . '&numsections=1';

        $button = [
            'buttonclass' => 'ludic-add-button',
            'action'      => 'getDataLinkAndRedirectTo',
            'order'       => $order,
            'link'        => $addsectionurl,
            'name'        => get_string('addsection-button', 'format_ludic')
        ];

        return $this->render_from_template('format_ludic/button', $button);
    }

    public function render_skins_list($skinsinfo) {
        $skinslist = new format_ludic_skins_list($skinsinfo);
        return $this->render($skinslist);
    }

    public function render_skin_skin_types_list($skinid, $skintypes) {
        $skintypeslist = new format_ludic_skins_types_list($skinid, $skintypes);
        return $this->render($skintypeslist);
    }

    /**
     * @param $section
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function render_section($section) {
        $section = new format_ludic_section($section);
        return $this->render($section);
    }

    /**
     * @param $skin
     * @return string
     */
    public function render_skin($skin) {
        $skin = new format_ludic_skin($skin);
        return $this->render($skin);
    }

    /**
     * @param $skin
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function render_skinned_tile($skin) {
        $skinnedtile = new format_ludic_skinned_tile($skin);
        return $this->render($skinnedtile);
    }

    /**
     * @param $form
     * @return string
     */
    public function render_form($form) {
        $form = new format_ludic_form($form);
        return $this->render($form);
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
     * @param $coursemodule
     * @return string
     */
    public function render_course_module_inline($coursemodule) {
        $coursemodule = new format_ludic_course_module_inline($coursemodule);
        return $this->render($coursemodule);
    }

    /**
     * @param format_ludic_modchooser $popup
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_modchooser(format_ludic_modchooser $modchooser) {
        return $this->render_from_template('format_ludic/modchooser', $modchooser);
    }

    protected function render_format_ludic_avatar_shop(format_ludic_avatar_shop $avatarshop) {
        return $this->render_from_template('format_ludic/avatar_shop', $avatarshop);
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
     * @param format_ludic_buttons $buttons
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_buttons(format_ludic_buttons $buttons) {
        return $this->render_from_template('format_ludic/buttons', $buttons);
    }

    /**
     * @param format_ludic_skin $skin
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_skin(format_ludic_skin $skin) {
        return $this->render_from_template('format_ludic/skin', $skin);
    }

    protected function render_format_ludic_skins_list(format_ludic_skins_list $skinsinfo) {
        return $this->render_from_template('format_ludic/skins_list', $skinsinfo);
    }

    protected function render_format_ludic_skins_types_list(format_ludic_skins_types_list $skintypes) {
        return $this->render_from_template('format_ludic/skins_types_list', $skintypes);
    }

    /**
     * @param format_ludic_skin $skin
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_skinned_tile(format_ludic_skinned_tile $skin) {
        return $this->render_from_template('format_ludic/skinned_tile', $skin);
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
     * @param format_ludic_course_module $coursemodule
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_course_module(format_ludic_course_module $coursemodule) {
        return $this->render_from_template('format_ludic/course_module', $coursemodule);
    }

    /**
     * @param format_ludic_course_module_inline $coursemodule
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_course_module_inline(format_ludic_course_module_inline $coursemodule) {
        return $this->render_from_template('format_ludic/course_module_inline', $coursemodule);
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
     * @param $type
     * @param string $parentscontent
     * @param string $propertiescontent
     * @param string $helpcontent
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_container_items($type, $editmode, $parentscontent = '', $propertiescontent = '', $helpcontent = '', $parenttitle = '') {
        return $this->render_from_template('format_ludic/container_items', [
            'parentstype'       => $type,
            'parenttitle'       => $parenttitle,
            'editmode'          => $editmode,
            'parentscontent'    => $parentscontent,
            'propertiescontent' => $propertiescontent,
            'propertieshelp'    => $helpcontent,
        ]);

    }

    /**
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_container_parents($type, $content = '', $title = '') {
        return $this->render_from_template('format_ludic/container_parents', [
            'title'          => $title,
            'parentstype'    => $type,
            'parentscontent' => $content
        ]);
    }

    /**
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_container_properties($content = '', $helpcontent = '') {
        return $this->render_from_template('format_ludic/container_properties', [
            'propertiescontent' => $content,
            'propertieshelp'    => $helpcontent
        ]);
    }

    /**
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_edit_page() {
        return $this->render_from_template('format_ludic/editpage', ['editmode' => true]);
    }

    public function render_edit_skins_page() {
        return $this->render_from_template('format_ludic/edit_skins', ['editskins' => true]);
    }

    /**
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_page() {
        return $this->render_from_template('format_ludic/page', [
            'globaldescription'   => $this->contexthelper->get_global_description(),
            'parentstype'         => 'section',
            'parenttitle'         => get_string('edit-title-section', 'format_ludic'),
            'parentscontent'      => $this->render_course_sections(),
            'globalcoursemodules' => $this->render_course_modules($this->contexthelper->get_global_section_id())
        ]);
    }

    /**
     * @param $sectionid int
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_section_page($sectionid) {
        $sectionobj = $this->contexthelper->get_section_by_id($sectionid);
        return $this->render_from_template('format_ludic/section_page', [
            'section'       => $this->render_section($sectionobj),
            'coursemodules' => $this->render_course_modules($sectionid),
            'description'   => $sectionobj->get_description()
        ]);
    }

    /**
     * Render header bar.
     * The header bar is present in all pages of course.
     *
     * @return string
     * @throws moodle_exception
     */
    public function render_header_bar() {
        $headerbar = new format_ludic_header_bar();
        return $this->render($headerbar);
    }

    /**
     * @param format_ludic_header_bar $element
     * @return bool|string
     * @throws moodle_exception
     */
    protected function render_format_ludic_header_bar(format_ludic_header_bar $element) {
        return $this->render_from_template('format_ludic/header_bar', $element);
    }

    /**
     * @param $childrentype string
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_container_children($childrentype, $childrentitle = '') {
        return $this->render_from_template('format_ludic/container_children', [
            'childrentype'  => $childrentype,
            'childrentitle' => $childrentitle
        ]);
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

    /**
     * Render course sections of current course.
     *
     * @param bool $globalsection
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function render_course_sections($globalsection = false) {
        // Get data.
        $course   = $this->contexthelper->get_course();
        $sections = $course->get_sections($globalsection);

        // Render sections.
        $output = '';
        if($this->contexthelper->is_editing()){
            $output .= '<div class="sections-container-title">'.get_string('edit-title-section', 'format_ludic').'</div>';
        }
        foreach ($sections as $section) {
            $output .= $this->render_section($section);
        }

        // Add content in edit view.
        if ($this->contexthelper->is_editing()) {
            // Render add new section button.
            $output .= $this->render_add_section_button($course->id, count($sections) + 1);

            // Render container for course modules.
            $output .= $this->render_container_children('coursemodules', get_string('edit-title-coursemodule', 'format_ludic'));
        }

        if (count($sections) == 0 && !$this->contexthelper->is_editing()) {
            if ($this->contexthelper->can_edit()) {
                $output .= '<div class="help-message">' . get_string('no-section-help', 'format_ludic') . '</div>';
            } else {
                $output .= '<div class="help-message">' . get_string('no-section', 'format_ludic') . '</div>';
            }

        }
        $output .= '</div>';

        return $output;
    }

    public function render_header_course_sections() {
        // Get data.
        $course   = $this->contexthelper->get_course();
        $sections = $course->get_sections();
        $output   = '';
        foreach ($sections as $section) {
            $section->contextview = 'header';
            $output               .= $this->render_section($section);
        }

        return $output;
    }

    /**
     * Render course modules of given section id.
     *
     * @param $sectionid
     * @param bool $selectedcmid , add a “selected” class for the course module with this id.
     * @return string
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function render_course_modules($sectionid, $selectedcmid = false) {
        // Get data.
        $section       = $this->contexthelper->get_section_by_id($sectionid);
        $coursemodules = $section->get_course_modules();

        // Render course modules.
        $output = '';
        foreach ($coursemodules as $order => $coursemodule) {
            $renderable = null;

            // In student view.
            if (!$this->contexthelper->is_editing()) {

                // Don't render hidden course module.
                if (!$coursemodule->visible) {
                    continue;
                }

                // In course module skin type is "inline", render it inline.
                if ($coursemodule->skin->id === FORMAT_LUDIC_CM_SKIN_INLINE_ID) {
                    $renderable = new format_ludic_course_module_inline($coursemodule);
                }

                // Skins menubar and stealth are render differently.
                if ($coursemodule->skin->id === FORMAT_LUDIC_CM_SKIN_MENUBAR_ID || $coursemodule->skin->id === FORMAT_LUDIC_CM_SKIN_STEALTH_ID) {
                    continue;
                }

            }

            // In the edit view or if the skin is not online, render it normally.
            $renderable = $renderable ? $renderable : new \format_ludic_course_module($coursemodule);

            // Add class "selected" on selected course module.
            if ($selectedcmid && $selectedcmid == $coursemodule->id) {
                $renderable->selected = true;
            }

            // Render course module from renderable object.
            $output .= $this->render($renderable);
        }

        return $output;
    }

}

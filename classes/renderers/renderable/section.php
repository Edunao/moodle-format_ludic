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
 * Section item for ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/item.php');

class format_ludic_section extends format_ludic_item {

    /**
     * Required because we add to need some html for a section.
     *
     * @var bool
     */
    public $requiresectionhtmlforjs;

    /**
     * format_ludic_section constructor.
     *
     * @param \format_ludic\section $section
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function __construct(\format_ludic\section $section) {
        global $PAGE, $CFG, $OUTPUT;

        // General data.
        $contexthelper      = \format_ludic\context_helper::get_instance($PAGE);
        $this->selectorid   = 'ludic-section-' . $section->section;
        $this->itemtype     = 'section';
        $this->id           = $section->id;
        $this->tooltip      = $section->get_title();
        $this->order        = $section->section;
        $this->isnotvisible = !$section->visible;
        $this->parent       = true;
        $this->skinid       = $section->skinid;

        // Action.
        $domain = $contexthelper->get_domain();
        if (($domain === 'course' || $domain === 'section') && !$contexthelper->is_student_view_forced() && !$contexthelper->is_single_section()) {
            $this->action = 'getDataLinkAndRedirectTo';
            $sectionarg   = ($contexthelper->get_viewmode() == 'overview') ? '&section=' . $section->section : '';
            $this->link   = $CFG->wwwroot . '/course/view.php?id=' . $section->courseid . $sectionarg;
        }

        // Edit mode.
        if ($contexthelper->is_editing()) {

            // Section 0 has a little different display.
            $isnotglobalsection = $section->section != 0;

            // Add in-edition class.
            $this->editmode = true;

            // Action.
            $this->action           = 'get_course_modules';
            $this->controller       = 'section';
            $this->callback         = 'displayCourseModulesHtml';
            $this->propertiesaction = 'get_properties';

            // Section 0 has no image.
            if ($isnotglobalsection) {
                // Image.
                $skininfo     = $section->skin->get_edit_info();
                $this->imgsrc = $skininfo->imgsrc;
                $this->imgalt = '';

            } else {
                $this->imgsrc = $OUTPUT->image_url('system-skins/section-zero', 'format_ludic')->out();
                $this->imgalt = '';
            }

            if ($contexthelper->count_sections() == 0) {

                // Select section 0 if there is no other section in course.
                $this->selected = true;
            }

            // Title.
            $this->title = $this->tooltip;

            // Enable drag and drop (except for section 0).
            $this->requiresectionhtmlforjs = true;
            $this->draggable               = $isnotglobalsection;
            $this->droppable               = $isnotglobalsection;

        } else {

            // The skin will render all section content.
            $this->content = $section->skin->render_skinned_tile();

            if ($contexthelper->get_section_id() == $section->id) {
                $this->selected = true;
            }

        }

    }

}

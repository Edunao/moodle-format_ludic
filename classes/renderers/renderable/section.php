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
     */
    public function __construct(\format_ludic\section $section) {
        global $PAGE, $CFG;

        // General data.
        $contexthelper = \format_ludic\context_helper::get_instance($PAGE);
        $this->selectorid   = 'ludic-section-' . $section->section;
        $this->itemtype     = 'section';
        $this->id           = $section->id;
        $this->tooltip      = $section->get_title();
        $this->order        = $section->section;
        $this->isnotvisible = !$section->visible;
        $this->parent       = true;
        $this->skinid       = $section->skinid;

        // Action.
        $location = $contexthelper->get_location();
        if ($location === 'course' && !$contexthelper->is_student_view_forced()) {
            $this->action = 'getDataLinkAndRedirectTo';
            $this->link   = $CFG->wwwroot . '/course/view.php?id=' . $section->courseid . '&section=' . $section->section;
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
                $imageobject  = $section->skin->get_edit_image();
                $this->imgsrc = $imageobject->imgsrc;
                $this->imgalt = $imageobject->imgalt;

            }

            // Title
            $this->title = $this->tooltip;

            // Enable drag and drop (except for section 0).
            $this->requiresectionhtmlforjs = true;
            $this->draggable               = $isnotglobalsection;
            $this->droppable               = $isnotglobalsection;

            // Add selected class on current section or first.
            $sectiontoselect = $contexthelper->get_section_id();
            $sectiontoselect = $sectiontoselect > 0 ? $sectiontoselect : 0;
            $this->selected = $section->section == $sectiontoselect;

        } else {

            // The skin will render all section content.
            $this->content = $section->skin->render_skinned_tile();

        }

    }

}
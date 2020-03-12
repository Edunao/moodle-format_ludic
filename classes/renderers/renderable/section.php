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

    public function __construct(\format_ludic\section $section) {
        $this->selectorid = 'ludic-section-' . $section->section;
        $this->id         = $section->id;
        $this->itemtype   = 'section';
        $this->issection  = true;
        $this->order      = $section->section;

        $this->title = $section->get_title();

        $this->skinid = isset($section->skinid) && !empty($section->skinid) ? $section->skinid : null;
        $imageobject  = !empty($section->skinid) ? $section->skin->get_edit_image() :
                \format_ludic\skin::get_undefined_skin_image('section');
        $this->imgsrc = $imageobject->imgsrc;
        $this->imgalt = $imageobject->imgalt;

        $this->isnotvisible = !$section->visible;

        $this->action           = 'get_course_modules';
        $this->propertiesaction = 'get_properties';
        $this->controller       = 'section';
        $this->callback         = 'displayCourseModulesHtml';

        $this->draggable = true;
        $this->droppable = true;
    }

}
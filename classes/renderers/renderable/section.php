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

    public $iconsrc;

    public function __construct(\format_ludic\section $section) {
        $this->selectorid   = 'section-' . $section->section;
        $this->id           = $section->id;
        $this->order        = $section->section;
        $defaultname        = get_string('default-section-title', 'format_ludic', $section);
        $this->title        = !empty($section->name) ? $section->name : $defaultname;
        $this->isnotvisible = !$section->visible;
        $this->itemtype     = 'section';
        $this->draggable    = true;
        $this->droppable    = true;
        $this->imgsrc       = $this->get_section_img_src();
        $this->imgalt       = $this->get_section_img_alt();
        $this->iconsrc      = isset($section->iconsrc) ? $section->iconsrc : false;
        $this->issection    = true;
    }

    public function get_section_img_info() {
        return ['imgsrc' => 'https://picsum.photos/id/' . $this->id . '2/80/80', 'imgalt' => '. . . . .sectionid => ' . $this->id];
    }

    public function get_section_img_src() {
        return $this->get_section_img_info()['imgsrc'];
    }

    public function get_section_img_alt() {
        return $this->get_section_img_info()['imgalt'];
    }
}
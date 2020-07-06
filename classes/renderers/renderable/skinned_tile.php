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
 * Skinned tile for ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class format_ludic_skinned_tile implements renderable {

    public $skinid;
    public $skintype;
    public $weight;
    public $classes;
    public $images;
    public $texts;
    public $title;
    public $css;
    public $emptydiv;
    public $hiddentexts;
    public $extrahtml;

    /**
     * format_ludic_skin constructor.
     *
     * @param \format_ludic\skin $skin
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function __construct(\format_ludic\skin $skin) {
        $this->skinid      = 'skin-' . $skin->location . '-' . $skin->item->id;
        $this->skintype    = $skin->type;
        $this->title       = $skin->item->get_skinned_tile_title();
        $this->weight      = $skin->get_weight();
        $this->classes     = $skin->get_classes();
        $this->images      = $skin->get_images_to_render();
        $this->hiddentexts = $skin->get_texts_to_render();
        $this->css         = $skin->get_css($this->skinid);
        $this->emptydiv    = [
            ['number' => 1],
            ['number' => 2],
            ['number' => 3],
            ['number' => 4],
            ['number' => 5],
            ['number' => 6],
            ['number' => 7],
            ['number' => 8],
            ['number' => 9],
            ['number' => 10]
        ];
        $this->extrahtml   = $skin->get_extra_html_to_render();
    }

}
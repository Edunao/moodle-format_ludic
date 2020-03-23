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
 * skin item for ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class format_ludic_skin extends format_ludic_item {

    public $selected;

    /**
     * format_ludic_skin constructor.
     *
     * @param \format_ludic\skin $skin
     */
    public function __construct(\format_ludic\skin $skin) {

        // General data.
        $this->selectorid = 'ludic-skin-' . $skin->id;
        $this->id         = $skin->id;
        $this->itemtype   = 'skin';
        $this->order      = $skin->id;
        $this->selected   = $skin->selected;
        $this->title      = $skin->title;

        // Action.
        $this->propertiesaction = 'get_description';

        // Image.
        $imageobject  = $skin->get_edit_image();
        $this->imgsrc = $imageobject->imgsrc;
        $this->imgalt = $imageobject->imgalt;

    }

}
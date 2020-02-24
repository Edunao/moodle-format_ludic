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
 * Items (sections, bravos, skins) for ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ludic_number_form_element extends format_ludic_form_element {

    public $min;
    public $max;
    public $step;

    public function __construct(\format_ludic\form_element $element) {
        parent::__construct($element);
        $this->min  = isset($element->attributes['min']) ? $element->attributes['min'] : false;
        $this->max  = isset($element->attributes['max']) ? $element->attributes['max'] : false;
        $this->step = isset($element->attributes['step']) ? $element->attributes['step'] : false;
    }

}
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
 * Select form element.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/form_element.php');

class format_ludic_select_form_element extends format_ludic_form_element {

    public $options;
    public $hasdescription;

    public $size;
    public $multiple;

    /**
     * format_ludic_select_form_element constructor.
     *
     * @param \format_ludic\form_element $element
     */
    public function __construct(\format_ludic\form_element $element) {
        parent::__construct($element);
        $this->options        = $element->options;
        $this->hasdescription = $element->hasdescription;

        $this->size     = isset($element->attributes['size']) ? $element->attributes['size'] : false;
        $this->multiple = isset($element->attributes['multiple']) ? $element->attributes['multiple'] : false;
    }
}

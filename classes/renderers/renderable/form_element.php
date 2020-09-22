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
 * Form element for ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class format_ludic_form_element implements renderable {

    public $id;
    public $name;
    public $value;
    public $defaultvalue;
    public $label;
    public $labelclass;
    public $class;
    public $groupclass;
    public $attributes;
    public $specific;
    public $placeholder;
    public $autofocus;
    public $disabled;
    public $readonly;
    public $required;

    /**
     * format_ludic_form_element constructor.
     *
     * @param \format_ludic\form_element $element
     */
    public function __construct(\format_ludic\form_element $element) {
        $this->id         = $element->id;
        $this->name       = $element->name;
        $this->attributes = $element->attributes;

        $this->specific   = $element->specific;

        $this->value        = $element->value;

        $this->defaultvalue = $element->defaultvalue;

        $this->class = isset($this->specific['class']) ? $this->specific['class'] : '';
        $this->class = is_array($this->class) ? implode(' ', $this->class) : $this->class;
        $this->groupclass = $element->groupclass;

        $this->label      = $element->label;
        $this->labelclass = isset($this->specific['labelclass']) ? $this->specific['labelclass'] : '';
        $this->labelclass = is_array($this->labelclass) ? implode(' ', $this->labelclass) : $this->labelclass;

        $this->placeholder = isset($element->attributes['placeholder']) ? $element->attributes['placeholder'] : false;
        $this->autofocus   = isset($element->attributes['autofocus']) ? $element->attributes['autofocus'] : false;
        $this->disabled    = isset($element->attributes['disabled']) ? $element->attributes['disabled'] : false;
        $this->readonly    = isset($element->attributes['readonly']) ? $element->attributes['readonly'] : false;
        $this->required    = isset($element->attributes['required']) ? $element->attributes['required'] : false;
    }
}
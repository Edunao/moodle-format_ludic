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
 * Abstract form element class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

abstract class form_element {

    /**
     * Context helper
     *
     * @var context_helper
     */
    protected $contexthelper;

    public $type;
    public $name;
    public $id;
    public $value;
    public $defaultvalue;
    public $label;
    public $attributes;
    public $specific;

    // Attributes which are common to all input types but have special behaviors when used on a given input type.
    public $autofocus;
    public $disabled;
    public $required;
    public $readonly;

    /**
     * form_element constructor.
     *
     * @param $name
     * @param $id
     * @param $value
     * @param $defaultvalue
     * @param string $label
     * @param array $attributes
     * @param array $specific
     */
    public function __construct($name, $id, $value, $defaultvalue, $label = '', $attributes = [], $specific = []) {
        global $PAGE;
        $this->contexthelper = context_helper::get_instance($PAGE);
        $this->name          = $name;
        $this->id            = $id;
        $this->value         = $value;
        $this->defaultvalue  = $defaultvalue;
        $this->label         = $label;
        $this->attributes    = $attributes;
        $this->specific      = $specific;
        $this->autofocus     = isset($this->attributes['autofocus']) ? $this->attributes['autofocus'] : null;
        $this->disabled      = isset($this->attributes['disabled']) ? $this->attributes['disabled'] : null;
        $this->required      = isset($this->attributes['required']) ? $this->attributes['required'] : null;
        $this->readonly      = isset($this->attributes['readonly']) ? $this->attributes['readonly'] : null;
    }

    /**
     * Returns the element's html from its mustache model.
     *
     * @return string
     * @throws \coding_exception
     */
    public function render() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');
        $class    = '\\format_ludic_' . $this->type . '_form_element';
        $element  = new $class($this);
        return $renderer->render($element);
    }

    /**
     * Each element must implement this function in order to validate its value.
     * Return an array with success (element is valid) and value (error message or typed value).
     *
     * @param $value
     * @return array
     */
    public abstract function validate_value($value);

}
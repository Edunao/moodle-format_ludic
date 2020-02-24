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
 * This file contains main class for the course format Ludic form
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

class form_element {

    public $type;
    public $name;
    public $selectorid;
    public $value;
    public $defaultvalue;
    public $label;
    public $attributes;
    public $specific;

    public function __construct($type, $name, $selectorid, $value, $defaultvalue, $label = '', $attributes = [], $specific = []) {
        $this->type         = $type;
        $this->name         = $name;
        $this->selectorid   = $selectorid;
        $this->value        = $value;
        $this->defaultvalue = $defaultvalue;
        $this->label        = $label;
        $this->attributes   = $attributes;
        $this->specific     = $specific;
    }

    public function render() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');
        $class    = '\\format_ludic_' . $this->type . '_form_element';
        $element  = new $class($this);
        return $renderer->render($element);
    }

}
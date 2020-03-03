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
 * Text form element class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

class text_form_element extends form_element {

    public $minlength;
    public $maxlength;

    public function __construct($name, $id, $value, $defaultvalue, $label = '', $attributes = [], $specific = []) {
        $this->type = 'text';
        parent::__construct($name, $id, $value, $defaultvalue, $label, $attributes, $specific);
        $this->minlength = isset($attributes['minlength']) ? $attributes['minlength'] : false;
        $this->maxlength = isset($attributes['maxlength']) ? $attributes['maxlength'] : false;
    }

    public function validate_value($value) {
        $value = clean_param($value, PARAM_RAW);
        if ($this->required && empty($value)) {
            return ['success' => 0,  'value' => get_string('error-required', 'format_ludic')];
        }
        if ($this->minlength && strlen($value) < $this->minlength) {
            return ['success' => 0,  'value' => get_string('error-str-min-length', 'format_ludic', $this->minlength)];
        }
        if ($this->maxlength && strlen($value) > $this->maxlength) {
            return ['success' => 0,  'value' => get_string('error-str-max-length', 'format_ludic', $this->maxlength)];
        }
        return ['success' => 1,  'value' => (string) ($value)];
    }


}
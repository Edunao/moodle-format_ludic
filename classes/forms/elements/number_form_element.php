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
 * Number form element class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

class number_form_element extends form_element {

    public $min;
    public $max;
    public $step;

    /**
     * number_form_element constructor.
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
        $this->type = 'number';
        parent::__construct($name, $id, $value, $defaultvalue, $label, $attributes, $specific);
        $this->min = isset($attributes['min']) ? $attributes['min'] : false;
        $this->max = isset($attributes['max']) ? $attributes['max'] : false;
        $this->step = isset($attributes['step']) ? $attributes['step'] : false;
    }

    /**
     * @param $value
     * @return array
     * @throws \coding_exception
     */
    public function validate_value($value) {
        // Required validation.
        if ($this->required && $value === '') {
            return [
                'success' => 0,
                'value'   => get_string('error-required', 'format_ludic')
            ];
        }

        // Convert value to int.
        $value = clean_param($value, PARAM_INT);

        // Value >= min validation.
        if ($this->min >= 0 && $value < $this->min) {
            return [
                'success' => 0,
                'value'   => get_string('error-int-min', 'format_ludic', $this->min)
            ];
        }

        // Value <= max validation.
        if ($this->max >= 0 && $value > $this->max) {
            return [
                'success' => 0,
                'value'   => get_string('error-int-max', 'format_ludic', $this->max)
            ];
        }

        // Value is a desired step validation.
        if ($this->step && $value % $this->step != 0) {
            return [
                'success' => 0,
                'value'   => get_string('error-int-step', 'format_ludic', $this->step)
            ];
        }

        // Success.
        return [
            'success' => 1,
            'value'   => $value
        ];
    }

}
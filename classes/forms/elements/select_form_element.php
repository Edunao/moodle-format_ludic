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
 * Select form element class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

class select_form_element extends form_element {

    public $options;
    public $hasdescription;

    /**
     * select_form_element constructor.
     */
    public function __construct($name, $id, $value, $defaultvalue, $label = '', $attributes = [], $specific = []) {
        $this->type = 'select';
        parent::__construct($name, $id, $value, $defaultvalue, $label, $attributes, $specific);
        $options = isset($this->specific['options']) ? $this->specific['options'] : [];
        foreach ($options as $key => $value) {
            $options[$key] = [];
            if (is_array($value)) {
                $options[$key]['value'] = isset($value['value']) ? $value['value'] : '';
            } else {
                $options[$key]['value'] = $value;
            }
            $options[$key]['name']  = isset($value['name']) ? $value['name'] : $options[$key]['value'];
            $options[$key]['description']  = isset($value['description']) ? $value['description'] : null;
            $options[$key]['selected'] = $this->value == $options[$key]['value'];
            $this->hasdescription = $this->hasdescription || !empty($options[$key]['description']);
        }
        $this->options = $options;
    }

    /**
     * Search if given value is in options or not.
     *
     * @param $value
     * @return array
     */
    public function validate_value($value) {
        $valueinoptions = false;
        foreach ($this->options as $option) {
            if ($value == $option['value']) {
                $valueinoptions = true;
            }
        }
        if (!$valueinoptions) {
            return ['success' => 0];
        }

        return ['success' => 1, 'value' => $value];
    }

}
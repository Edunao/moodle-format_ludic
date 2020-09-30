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

require_once(__DIR__ . '/form_element.php');

class select_form_element extends form_element {

    public $options;
    public $hasdescription;

    /**
     * select_form_element constructor.
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
        $this->type = 'select';
        parent::__construct($name, $id, $value, $defaultvalue, $label, $attributes, $specific);

        // Select options.
        // Options can be an array with values.
        // Options can also be an associative array with keys :
        // value => hidden value.
        // name => visible name of option.
        // description => an optional description to display at the top of the name.
        $options = isset($this->specific['options']) ? $this->specific['options'] : [];
        foreach ($options as $key => $value) {

            // Always convert options to array.
            $options[$key] = [];

            // Set value from value key or real value.
            if (is_array($value)) {
                $options[$key]['value'] = isset($value['value']) ? $value['value'] : '';
            } else {
                $options[$key]['value'] = $value;
            }

            // Set name from name key, if empty use value instead.
            $options[$key]['name'] = isset($value['name']) ? $value['name'] : $options[$key]['value'];

            // Optional description.
            $options[$key]['description'] = isset($value['description']) ? $value['description'] : null;

            // Selected current value.
            $options[$key]['selected'] = $this->value == $options[$key]['value'];

            // Indicator to know easily if select has description.
            $this->hasdescription = $this->hasdescription || !empty($options[$key]['description']);
        }

        // Set options.
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

        return [
            'success' => 1,
            'value'   => $value
        ];
    }

}

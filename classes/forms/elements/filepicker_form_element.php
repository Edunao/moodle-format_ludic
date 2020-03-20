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
 * Filepicker form element class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

class filepicker_form_element extends form_element {

    /**
     * filepicker_form_element constructor.
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
        $this->type = 'filepicker';
        parent::__construct($name, $id, $value, $defaultvalue, $label, $attributes, $specific);
    }

    /**
     * @param $value
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function validate_value($value) {

        // Filepicker value is never empty.
        $itemid = clean_param($value, PARAM_INT);

        // Required validation.
        // Search if file exists in draft.
        $dbapi = $this->contexthelper->get_database_api();
        if ($this->required && !$dbapi->file_exists_in_draft($itemid)) {
            return ['success' => 0, 'value' => get_string('error-required', 'format_ludic')];
        }

        // Success.
        return ['success' => 1, 'value' => $itemid];
    }

}
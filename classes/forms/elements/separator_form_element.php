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

require_once(__DIR__ . '/form_element.php');

class separator_form_element extends form_element {

    /**
     * separator_form_element constructor.
     *
     * @param $id
     * @param string $label
     * @param array $attributes
     * @param array $specific
     */
    public function __construct($id, $label = '', $attributes = [], $specific = []) {
        $this->type = 'separator';
        parent::__construct($label, $id, '', '', $label, $attributes, $specific);
    }


    public function validate_value($value) {
        return true;
    }

}

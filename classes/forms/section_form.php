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

class section_form extends form {

    public function __construct($id = null) {
        parent::__construct('section', $id);
    }

    public function get_definition() {
        if ($this->id) {
            $dataapi      = $this->contexthelper->get_data_api();
            $this->object = $dataapi->get_section_by_id($this->id);
        }

        $id          = isset($this->object->id) ? $this->object->id : null;
        $name          = isset($this->object->name) ? $this->object->name : null;
        $defaultname   = isset($this->object->defaultname) ? 'Section ' . $id : 'Section sans id';
        $skinid        = isset($this->object->skinid) ? $this->object->skinid : null;

        $selectdefault = [
                'options' => [
        ['name' => 'section ' . $id . ' option 1', 'value' => 1],
        ['name' => 'section ' . $id . ' option 2', 'value' => 2],
        ['name' => 'section ' . $id . ' option 3', 'value' => 3],
        ['name' => 'section ' . $id . ' option 4', 'value' => 4, 'selected' => true],
        ['name' => 'section ' . $id . ' option 5', 'value' => 5]
                ]
        ];
        $elements   = [];
        $elements[] = new form_element('hidden', 'id', 'section-id', $id, 0);
        $elements[] = new form_element('text', 'name', 'section-name', $name, $defaultname, 'section text label');
        $elements[] = new form_element('number', 'weight', 'section-weight', null, 800, 'section number label', ['min' => 0, 'max' => 1000, 'step' => 100]);
        $elements[] = new form_element('checkbox', 'hidden', 'section-hidden', null, 0, 'section checkbox label');
        $elements[] = new form_element('textarea', 'css', 'section-css', null, '[section-tile] {
        }', 'section textarea label');
        $elements[] = new form_element('select', 'sectiontype', 'section-type', null, null, 'section select label', [],
                $selectdefault);
        $elements[] = new form_element('selection_popup', 'skinid', 'section-skinid', $skinid, 0, 'section skinid label', ['multiple' => false], ['selecttype' => 'skin']);
        $elements[] = new form_element('filepicker', 'image-1', 'section-image-1', null, null, 'section filepicker label');
        return $elements;
    }



}
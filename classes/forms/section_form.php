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
 * Section form.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

class section_form extends form {

    public function __construct($id) {
        parent::__construct('section', $id);
        $this->object   = $this->contexthelper->get_section_by_id($id);
        $this->elements = $this->get_definition();
    }

    /**
     * Hidden : id.
     * Text : name.
     * Checkbox : visible.
     * Selection popup : skin id.
     *
     * @return form_element[]
     * @throws \coding_exception
     */
    public function get_definition() {

        $id = $this->object->id;

        $title        = $this->object->name;
        $defaulttitle = get_string('default-section-title', 'format_ludic', $this->object->section);
        $labeltitle   = get_string('label-section-title', 'format_ludic');

        $elements   = [];
        $elements[] = new hidden_form_element('id', 'section-id', $id, 0);

        $elements[] = new text_form_element('name', 'section-title', $title, $defaulttitle, $labeltitle, [
                'required' => true, 'maxlength' => 30
        ]);

        $visible        = $this->object->visible;
        $defaultvisible = 1;
        $labelvisible   = get_string('label-section-visible', 'format_ludic');
        $elements[]     = new checkbox_form_element('visible', 'section-visible', $visible, $defaultvisible, $labelvisible,
                ['required' => true]);

        $elements[] = new selection_popup_form_element('skinid', 'section-skinid', $this->object->skinid, 0,
                get_string('label-skin-selection', 'format_ludic'),
                ['required' => true, 'multiple' => false],
                [
                        'icon'           => $this->object->skin->get_edit_image(),
                        'itemid'         => $id,
                        'itemcontroller' => 'skin',
                        'itemaction'     => 'get_section_skin_selector',
                        'popuptitle'     => get_string('section-skin-selection', 'format_ludic')
                ]
        );

        // ces éléments ne font pas parties de la section, ils sont là pour test uniquement.

        //$elements[]    = new filepicker_form_element('image-1', 'section-image-1', null, null, 'section filepicker label', ['required' => true]);
        //$elements[]    = new filepicker_form_element('image-2', 'section-image-2', null, null, 'section filepicker label');
        //$elements[]    = new number_form_element('weight', 'section-weight', null, 800, 'section number label',
        //        ['min' => 0, 'max' => 1000, 'step' => 100]);
        //$elements[]    = new textarea_form_element('css', 'section-css', null, '[section-tile] {
        //}', 'section textarea label', ['rows' => 10]);

        return $elements;
    }

    public function update_child() {
        $this->object->update($this->formvalues);
        return true;
    }

    public function validate_child() {
        return true;
    }
}
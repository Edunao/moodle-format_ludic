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

class coursemodule_form extends form {

    /**
     * coursemodule_form constructor.
     *
     * @param $id
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct($id) {
        parent::__construct('coursemodule', $id);
        $this->object   = $this->contexthelper->get_course_module_by_id($id);
        $this->elements = $this->get_definition();
    }

    /**
     * @return form_element[]
     * @throws \coding_exception
     */
    public function get_definition() {
        $id = $this->object->id;

        $elements   = [];
        $elements[] = new hidden_form_element('id', 'course-module-id', $id, 0);

        $elements[] = new text_form_element('name', 'course-module-title', $this->object->name, '',
                get_string('label-course-module-title', 'format_ludic'), [
                        'required' => true, 'maxlength' => 30
                ]);

        $elements[] = new checkbox_form_element('visible', 'section-visible', $this->object->visible, 1,
                get_string('label-section-visible', 'format_ludic'), ['required' => true]);

        $skinid     = isset($this->object->skinid) ? $this->object->skinid : 0;
        $elements[] = new selection_popup_form_element('skinid', 'course-module-skinid', $skinid, 0,
                get_string('label-skin-selection', 'format_ludic'),
                ['required' => true, 'multiple' => false],
                [
                        'icon'           => !empty($this->object->skinid) ? $this->object->skin->get_edit_image() :
                                skin::get_undefined_skin_image('section'),
                        'itemid'         => $id,
                        'itemcontroller' => 'skin',
                        'itemaction'     => 'get_course_module_skin_selector',
                        'popuptitle'     => get_string('course-module-skin-selection', 'format_ludic')
                ]
        );

        $elements[] = new select_form_element('weight', 'coursemodule-weight', $this->object->weight, null,
                get_string('label-select-weight', 'format_ludic'), ['required' => true, 'multiple' => false],
                ['options' => $this->contexthelper->get_course_module_weight_options()]);

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
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
 * Course module form.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/form.php');

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
     * Hidden : id.
     * Text : name.
     * Selection popup : skin id.
     * Select : weight.
     * Select : access.
     *
     * @return form_element[]
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_definition() {
        require_once(__DIR__ . '/elements/hidden_form_element.php');
        require_once(__DIR__ . '/elements/text_form_element.php');
        require_once(__DIR__ . '/elements/selection_popup_form_element.php');
        require_once(__DIR__ . '/elements/select_form_element.php');
        require_once(__DIR__ . '/elements/checkbox_form_element.php');

        $sectionidx = $this->object->section->dbrecord->section;
        $elements   = [];

        // Course module id.
        $id         = $this->object->id;
        $elements[] = new hidden_form_element('id', 'course-module-id', $id, 0);

        // Course module name.
        $elements[] = new text_form_element('name', 'course-module-title', $this->object->name, '', get_string('label-course-module-title', 'format_ludic'), ['required' => true]);

        // Course module skin id.
        $isinlineonly = plugin_supports('mod', $this->object->cminfo->modname, FEATURE_NO_VIEW_LINK, false);
        if(!$isinlineonly){
            $elements[] = new selection_popup_form_element('skinid', 'course-module-skinid', $this->object->skinid, 0, get_string('label-skin-selection', 'format_ludic'), [
                'required' => true,
                'multiple' => false
            ], [
                'icon'           => $this->object->skin->get_edit_image(),
                'itemid'         => $id,
                'itemcontroller' => 'skin',
                'itemaction'     => ($sectionidx == 0) ? 'get_course_module_skin_selector_section_zero' : 'get_course_module_skin_selector_main',
                'popuptitle'     => get_string('course-module-skin-selection', 'format_ludic')
            ]);
        }

        // Course module weight.
        $elements[] = new select_form_element('weight', 'coursemodule-weight', $this->object->get_weight(), null, get_string('label-select-weight', 'format_ludic'), [
                'required' => true,
                'multiple' => false
            ], ['options' => format_ludic_get_weight_options()]);

        // Course module access.
        $elements[] = new checkbox_form_element('visible', 'coursemodule-visible', $this->object->visible, 1, get_string('label-section-visible', 'format_ludic'), [
            'required' => true,
        ]);

        // Course module section
        $sections = $this->contexthelper->get_course()->get_sections(true);
        $options = [];
        foreach ($sections as $section){
            $options[] = [
                'value'       => $section->id,
                'name'        => $section->name,
            ];
        }
        $elements[] = new select_form_element('section', 'coursemodule-section', $this->object->sectionid, null, 'Section', [
            'required' => true,
            'multiple' => false
        ], ['options' => $options]);


        //$elements[] = new select_form_element('access', 'coursemodule-access', $this->object->access, null, get_string('label-select-access', 'format_ludic'), [
        //        'required' => true,
        //        'multiple' => false
        //    ], ['options' => format_ludic_get_access_options()]);

        return $elements;
    }

    /**
     * Update course module.
     *
     * @return bool
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function update_child() {
        $this->object->update($this->formvalues);
        return true;
    }

    /**
     * More course module validation.
     *
     * @return bool
     */
    public function validate_child() {
        return true;
    }
}
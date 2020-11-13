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

        $isinlineonly = plugin_supports('mod', $this->object->cminfo->modname, FEATURE_NO_VIEW_LINK, false);
        $sectionidx = $this->object->section->dbrecord->section;
        $elements   = [];

        // Course module id.
        $id         = $this->object->id;
        $elements[] = new hidden_form_element('id', 'course-module-id', $id, 0);

        // Course module name.
        $elements[] = new text_form_element(
            'name',
            'course-module-title',
            $this->object->name,
            '',
            get_string('label-course-module-title', 'format_ludic'),
            ['required' => true]
        );


        // Course module skin id.

        if (!$isinlineonly) {
            $editinfo = $this->object->skin->get_edit_info();
            $elements[] = new selection_popup_form_element(
                'skinid',
                'course-module-skinid',
                $this->object->skinid,
                0,
                get_string('label-skin-selection', 'format_ludic'),
                [],
                [
                    'icon'            => (object)[ 'imgsrc' => $editinfo->imgsrc, 'imgalt' => '' ],
                    'itemid'          => $id,
                    'itemcontroller'  => 'skin',
                    'itemaction'      => ($sectionidx == 0) ?
                        'get_course_module_skin_selector_section_zero'
                        : 'get_course_module_skin_selector_main',
                    'popuptitle'      => get_string('course-module-skin-selection', 'format_ludic'),
                    'skintitle'       => $editinfo->title,
                    'skindescription' => $editinfo->description,
                ]
            );
        }else{
            $elements[] = new hidden_form_element('skinid', 'course-module-skinid', $this->object->skinid, 0);
        }

        // Course module weight.
        $elements[] = new text_form_element(
            'weight',
            'coursemodule-weight',
            $this->object->get_weight(),
            format_ludic_get_default_weight(),
            get_string('label-weight', 'format_ludic'),
            ['required' => true]
        );

        // Course module target min value (if there is one).
        $targetminid = $this->object->skin->get_targetmin_string_id();
        if ($targetminid) {
            $elements[] = new text_form_element(
                'targetmin',
                'coursemodule-target-min',
                $this->object->get_targetmin(),
                '',
                get_string($targetminid . '-title', 'format_ludic'),
                []
            );
        } else {
            $elements[] = new hidden_form_element('targetmin', 'coursemodule-target-min', $this->object->get_targetmin(), 0);
        }

        // Course module target max value (if there is one).
        $targetmaxid = $this->object->skin->get_targetmax_string_id();
        if ($targetmaxid) {
            $elements[] = new text_form_element(
                'targetmax',
                'coursemodule-target-max',
                $this->object->get_targetmax(),
                '',
                get_string($targetmaxid . '-title', 'format_ludic'),
                []
            );
        } else {
            $elements[] = new hidden_form_element('targetmax', 'coursemodule-target-max', $this->object->get_targetmax(), 0);
        }

        // Course module access.
        $elements[] = new checkbox_form_element(
            'visible',
            'coursemodule-visible',
            $this->object->visible,
            1,
            get_string('label-section-visible', 'format_ludic'),
            []
        );

        // Course module section.
        $sections = $this->contexthelper->get_course()->get_sections(true);
        $options = [];
        foreach ($sections as $section) {
            $options[] = [
                'value'       => $section->id,
                'name'        => $section->name,
            ];
        }
        $elements[] = new select_form_element(
            'section',
            'coursemodule-section',
            $this->object->sectionid,
            null,
            get_string('label-move-section', 'format_ludic'),
            [],
            ['options' => $options]
        );

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
        return $this->object->update($this->formvalues);
    }

    /**
     * More course module validation.
     *
     * @return bool
     */
    public function validate_child() {
        // start by verifying that we have all of the properties that we expect
        foreach(['id', 'visible', 'skinid', 'weight', 'targetmin', 'targetmax'] as $fieldname) {
            if (!array_key_exists($fieldname, $this->formvalues)) {
                throw new \Exception('Missing field in inpout data: '. $fieldname);
            }
        }
        return true;
    }
}
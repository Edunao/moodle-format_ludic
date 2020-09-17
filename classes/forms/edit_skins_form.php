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
 *
 *
 * @package    TODO
 * @subpackage TODO
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     Céline Hernandez <celine@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

require_once 'form.php';

class edit_skins_form extends form {
    private $skin;
    private $courseid;

    public function __construct($courseid, $skin) {
        parent::__construct('skin', $skin->id);
        $this->skin     = $skin;
        $this->object   = $skin;
        $this->courseid = $courseid;
        $this->elements = $this->get_definition();
    }

    public function get_definition() {

        // Course id.
        $courseid   = $this->courseid;
        $elements[] = new hidden_form_element('courseid', 'course-id', $courseid, 0);

        // Skin type id
        $skintypeid = $this->skin->get_unique_name();
        $elements[] = new hidden_form_element('skintypeid', 'skintype-id', $skintypeid, 0);

        // Skin id
        $skinid     = $this->skin->id;
        $elements[] = new hidden_form_element('id', 'id', $skinid, 0);

        // Skin settings
        $settings = $this->skin->get_editor_config();
        foreach ($settings as $section => $options) {

            foreach ($options as $elementname => $elementtype) {
                if (is_array($elementtype)) {

                    // Parcourir les settings et faire une enveloppe vide qu'on pourra copier
                    $index     = 0;
                    $groupname = $elementname;
                    $elements[] = new separator_form_element($groupname,$groupname);
                    $elements[] = new separator_form_element($groupname . ' '. 0,$groupname . ' '. 0);
                    foreach ($elementtype as $subname => $subtype) {

                        $elements = array_merge($elements, $this->get_group_element($groupname, $subname, $subtype, false));
                        $elements[] = new button_form_element('deletestep', 'deletestep', '', '', 'Supprimer l\'étape', [
                            'action' => 'editSkindeleteStep',
                            'itemid' => $groupname . '-' . 0,
                            'class'  => 'delete-group'
                        ]);
                    }

                    // Parcourir les propriétés pour créer les enveloppes pleines, chaque enveloppe devra pouvoir être supprimés
                    $groupvalue = $this->skin->get_properties($groupname);
                    if($groupvalue){
                        foreach ($groupvalue as $index => $subelements) {
                            $elements[] = new separator_form_element($groupname . ' '. $index ,$groupname . ' '. $index);
                            foreach ($elementtype as $subname => $subtype) {
                                $elements = array_merge($elements, $this->get_group_element($groupname, $subname, $subtype, $index));
                            }
                            // Delete step button
                            $elements[] = new button_form_element('deletestep', 'deletestep', '', '', 'Supprimer l\'étape', [
                                'action' => 'editSkindeleteStep',
                                'itemid' => $groupname . '-' . $index,
                                'class'  => 'delete-group'
                            ]);
                        }
                    }

                    $elements[] = new button_form_element('addstep', 'addstep', '', '', 'Ajouter une étape', [
                        'action' => 'editSkinAddStep',
                        'itemid' => $index,
                        'class'  => 'add-group'
                    ]);

                } else {
                    $elements = array_merge($elements, $this->get_element($elementname, $elementtype));
                }

            }

        }

        //$elements[] = new filepicker_form_element('image-1', 'section-image-1', null, null, 'section filepicker label', ['required' => true]);

        return $elements;

    }

    private function get_group_element($groupname, $elementname, $elementtype, $index) {
        $elements   = [];
        $groupvalue = $this->skin->get_properties($groupname);
        //print_object($groupvalue);
        $currentvalue = '';
        if ($index !== false) {
            $currentvalue = $groupvalue[$index]->$elementname;
        } else {
            $index = 'empty';
        }

        $elementname = $groupname . '_' . $elementname . '_' . $index;

        // Parcourir les settings et faire une enveloppe vide qu'on pourra copier

        // Parcourir les propriétés pour créer les enveloppes pleines, chaque enveloppe devra pouvoir être supprimés

        // On doit pouvoir ajouter une enveloppe vide
        $attributes = [
            'data-group' => $groupname,
            'data-name'  => $elementname,
            'data-index' => $index
        ];
        switch ($elementtype) {
            case 'text':
                $elements[] = new text_form_element($elementname, $elementname, $currentvalue, '', $elementname, $attributes);
                break;
            case 'textarea':
                $elements[] = new textarea_form_element($elementname, $elementname, $currentvalue, '', $elementname, array_merge($attributes, ['rows' => 5]));
                break;
            case 'int':
                $elements[] = new number_form_element($elementname, $elementname, '', '', $elementname, $attributes);
                break;
            case 'css':
                $elements[] = new filepicker_form_element($elementname, $elementname, '', '', $elementname, $attributes);
                break;
            case 'image':
                $altvalue = isset($currentvalue->imgalt) ? $currentvalue->imgalt : '';

                $elements[] = new filepicker_form_element($elementname . '-img', $elementname . '-img', '', '', $elementname, [
                    'required'  => true,
                    'data-test' => 'coucou',
                    'groupclass' => $groupname,
                ], ['required' => true]);
                $elements[] = new text_form_element($elementname . '-alt', $elementname . '-alt', $altvalue, '', $elementname . ' alt text');
                break;
            default:
                break;
        }

        return $elements;
    }

    private function get_element($elementname, $elementtype) {
        $elements = [];
        // Get current value
        $currentvalue = $this->skin->get_properties($elementname);
        //print_object('name ' . $elementname);
        //print_object($currentvalue);

        switch ($elementtype) {
            case 'text':
                $elements[] = new text_form_element($elementname, $elementname, $currentvalue, '', $elementname);
                break;
            case 'textarea':
                $elements[] = new textarea_form_element($elementname, $elementname, $currentvalue, '', $elementname, ['rows' => 5]);
                break;
            case 'int':
                $elements[] = new number_form_element($elementname, $elementname, '', '', $elementname);
                break;
            case 'css':
                $elements[] = new filepicker_form_element($elementname, $elementname, '', '', $elementname);
                break;
            case 'image':
                $altvalue = isset($currentvalue->imgalt) ? $currentvalue->imgalt : '';
                $elements[] = new filepicker_form_element($elementname . '-img', $elementname . '-img', 31948528, '', $elementname, [
                    'accepted_types' => 'png, jpg, gif, svg, jpeg',
                    'required'       => true,
                ]);
                $elements[] = new text_form_element($elementname . '-alt', $elementname . '-alt', $altvalue, '', $elementname . ' alt text');
                break;
            default:
                break;
        }

        return $elements;
    }

    /**
     * Update section.
     *
     * @return bool
     */
    public function update_child() {
        $this->object->update($this->formvalues);
        return true;
    }

    /**
     * More section validation.
     *
     * @return bool
     */
    public function validate_child() {
        return true;
    }
}
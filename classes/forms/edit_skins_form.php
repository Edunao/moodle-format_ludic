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

                    $index     = 0;
                    $groupname = $elementname;
                    $elements[] = new separator_form_element($groupname,$groupname,['groupclass' => 'group-separator']);

                    // Create element for each current value
                    $groupvalue = $this->skin->get_properties($groupname);
                    if($groupvalue){
                        foreach ($groupvalue as $index => $subelements) {
                            $elements[] = new separator_form_element($groupname . ' '. $index ,$groupname . ' '. $index, [
                                'groupclass' => $groupname . ' ' . $groupname . '_' .$index . ' group-index-'.$index,
                            ]);

                            foreach ($elementtype as $subname => $subtype) {
                                    $elements = array_merge($elements, $this->get_group_element($groupname, $subname, $subtype, $index));

                            }
                            // Delete step button
                            $elements[] = new button_form_element('deletestep', 'deletestep', '', '', 'Supprimer l\'étape', [
                                'action' => 'editSkinDeleteStep',
                                'itemid' => $groupname . '_' . $index,
                                'class'  => 'delete-group ' . $groupname . '_' .$index . ' ' . $groupname
                            ]);
                        }
                    }

                    // Add empty groups
                    /*$index++;
                    if($index < 8){
                        for($index; $index <= 8 ; $index++){
                            $elements[] = new separator_form_element($groupname . '_' . $index, $groupname . '_' . $index,
                                ['groupclass' => 'group-index-'. $index]);
                            foreach ($elementtype as $subname => $subtype) {

                                $elements = array_merge($elements, $this->get_group_element($groupname, $subname, $subtype, $index));
                            }
                            $elements[] = new button_form_element('deletestep', 'deletestep', '', '', 'Supprimer l\'étape', [
                                'action' => 'editSkinDeleteStep',
                                'itemid' => $groupname . '_' . $index,
                                'class'  => 'delete-group group group-index-' . $index,
                            ]);
                        }
                    }*/
                } else {
                    $elements = array_merge($elements, $this->get_element($elementname, $elementtype));
                }

            }

        }

        return $elements;

    }

    private function get_group_element($groupname, $elementname, $elementtype, $index, $class = '') {
        $elements   = [];
        $groupvalue = $this->skin->get_properties($groupname);

        $currentvalue = '';
        if (isset($groupvalue[$index])) {
            $currentvalue = $groupvalue[$index]->$elementname;
        }

        $elementlabel = $elementname;
        $elementname = $groupname . '_' . $elementname . '_' . $index;

        $attributes = [
            'groupclass' => $groupname . ' ' . $groupname . '_' .$index . ' group-index-'.$index . ' ' . $class ,
        ];

        switch ($elementtype) {
            case 'text':
                $elements[] = new text_form_element($elementname, $elementname, $currentvalue, '', $elementlabel, $attributes);
                break;
            case 'textarea':
                $elements[] = new textarea_form_element($elementname, $elementname, $currentvalue, '', $elementlabel, array_merge($attributes, ['rows' => 5]));
                break;
            case 'int':
                $elements[] = new number_form_element($elementname, $elementname, $currentvalue, 1, $elementlabel, $attributes);
                break;
            case 'image':
                $imgsrc = isset($currentvalue->imgsrc) ? $currentvalue->imgsrc : '';
                $altvalue = isset($currentvalue->imgalt) ? $currentvalue->imgalt : '';
                $elements[] = new filepicker_form_element($elementname . '-img', $elementname . '-img', '', '', $elementlabel, array_merge($attributes,[
                ]), ['previewsrc' => $imgsrc]);
                $elements[] = new text_form_element($elementname . '-alt', $elementname . '-alt', $altvalue, '', $elementlabel . ' alt text',$attributes);
                break;
            case 'images':
                foreach($currentvalue as $imgindex => $image){
                    $imgsrc = isset($image->imgsrc) ? $image->imgsrc : '';
                    $altvalue = isset($image->imgalt) ? $image->imgalt : '';
                    $elementname .= '_' . $imgindex;
                    $elements[] = new filepicker_form_element($elementname . '-img', $elementname . '-img', '', '', $elementlabel, array_merge($attributes,[
                    ]), ['previewsrc' => $imgsrc]);
                    $elements[] = new text_form_element($elementname . '-alt', $elementname . '-alt', $altvalue, '', $elementlabel . ' alt text',$attributes);
                }

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

        switch ($elementtype) {
            case 'text':
                $elements[] = new text_form_element($elementname, $elementname, $currentvalue, '', $elementname);
                break;
            case 'textarea':
                $elements[] = new textarea_form_element($elementname, $elementname, $currentvalue, '', $elementname, ['rows' => 5]);
                break;
            case 'int':
                $elements[] = new number_form_element($elementname, $elementname, $currentvalue, '', $elementname);
                break;
            case 'image':
                $itemid = '';
                if(isset($currentvalue->imgfileid)){
                    $itemid = $this->contexthelper->fileapi->get_draft_itemid_from_fileid($currentvalue->imgfileid);
                }

                $imgsrc = isset($currentvalue->imgsrc) ? $currentvalue->imgsrc : '';
                $altvalue = isset($currentvalue->imgalt) ? $currentvalue->imgalt : '';
                $elements[] = new filepicker_form_element($elementname . '-img', $elementname . '-img', $itemid, '', $elementname, [
                    'accepted_types' => 'png, jpg, gif, svg, jpeg',
                    'required'       => true,
                ],['previewsrc' => $imgsrc]);
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
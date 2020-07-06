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

    public function __construct($courseid, $skin) {
        parent::__construct('course', $courseid);
        $this->skin     = $skin;
        $this->object   = $this->contexthelper->get_course_by_id($courseid);
        $this->elements = $this->get_definition();
    }

    public function get_definition() {

        // Course id.
        $id         = $this->object->id;
        $elements[] = new hidden_form_element('id', 'course-id', $id, 0);

        // Skin
        $id         = $this->skin->get_unique_name();
        $elements[] = new hidden_form_element('skinid', 'skin-id', $id, 0);

        // Skin settings
        $settings = $this->skin->get_editor_config();

        foreach ($settings as $section => $options) {

            if ($section == 'steps') {
                continue;
            }

            foreach ($options as $elementname => $type) {
                switch ($type) {
                    case 'int':
                        $elements[] = new number_form_element($elementname, $elementname, '', '', $elementname);
                        break;
                    case 'css':
                        $elements[] = new filepicker_form_element($elementname, $elementname, '', '', $elementname);
                        break;
                    case 'image':
                        //$elements[] = new filepicker_form_element($elementname, $elementname,'','', $elementname);
                        break;
                    default:
                        break;
                }
            }

        }

        $elements[] = new filepicker_form_element('image-1', 'section-image-1', null, null, 'section filepicker label', ['required' => true]);

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
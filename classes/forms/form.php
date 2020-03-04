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
 * Abstract form class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

abstract class form {

    public $id;
    public $type;
    public $content;
    public $object;
    public $errors;
    public $formvalues = null;

    /**
     * Context helper
     *
     * @var context_helper
     */
    protected $contexthelper;

    /**
     * @var form_element[]
     */
    protected $elements;

    /**
     * form constructor.
     *
     * @param $type
     * @param null $id
     */
    public function __construct($type, $id = null) {
        global $PAGE;
        $this->id            = $id;
        $this->type          = $type;
        $this->content       = '';
        $this->contexthelper = context_helper::get_instance($PAGE);
        $this->errors        = [];
    }

    /**
     * @return form_element[]
     */
    public abstract function get_definition();

    /**
     * Validation of child specific form.
     * @return bool all is valid or not.
     */
    public abstract function validate_child();

    /**
     *
     * @return bool update success.
     */
    public abstract function update_child();

    /**
     * Returns the html of the form.
     *
     * @return string
     * @throws \coding_exception
     */
    public function render() {
        global $PAGE;
        $elements = $this->elements;
        foreach ($elements as $element) {
            $this->content .= $element->render();
        }
        $renderer = $PAGE->get_renderer('format_ludic');
        return $renderer->render_form($this);
    }

    /**
     * Validate the form, if everything is valid.
     * Update and return update success.
     *
     * @param $data
     * @return bool
     */
    public function validate_and_update($data) {
        $this->set_form_values($data);
        if (!$this->validate_elements()) {
            return false;
        }
        if (!$this->validate_child()) {
            return false;
        }
        return $this->update_child();
    }

    /**
     * Set data from serializeArray in an array [name => value].
     * Return form values.
     *
     * @param $data
     * @return array|bool
     */
    public function set_form_values($data) {
        $values = [];
        foreach ($data as $input) {
            if (!isset($input['name']) || !isset($input['value'])) {
                return false;
            }
            $values[$input['name']] = $input['value'];
        }
        $this->formvalues = $values;
        return $this->formvalues;
    }

    /**
     * Validate elements.
     * Each element validates its value and returns it if valid, otherwise an error message.
     * @return bool
     * @throws \coding_exception
     */
    public function validate_elements() {
        if (!$this->formvalues) {
            return false;
        }
        $valid    = true;
        foreach ($this->elements as $element) {

            // Javascript serializeArray function returns the checkbox elements only if they are checked.
            // If they are not it means that the value is 0.
            if ($element->type === 'checkbox' && !isset($this->formvalues[$element->name])) {
                $this->formvalues[$element->name] = 0;
            }

            // Validate value of element.
            $value             = $this->formvalues[$element->name];
            $elementvalidation = $element->validate_value($value);

            // If element is valid, update his value, else add error.
            if ($elementvalidation['success']) {
                // Update the value to be sure it is of the expected type and format.
                $this->formvalues[$element->name] = $elementvalidation['value'];
            } else {
                // Add an error to the error list.
                $defaulterror = get_string('default-error', 'format_ludic');
                $this->errors[] = [
                        'id'    => $element->id,
                        'label' => $element->label,
                        'name'  => $element->name,
                        'error' => isset($elementvalidation['value']) ? $elementvalidation['value'] : $defaulterror
                ];
                $valid = false;
            }
        }
        return $valid;
    }

    /**
     * Add an element in form.
     *
     * @param form_element $element
     */
    public function add_element($element) {
        $this->elements[] = $element;
    }

    /**
     * Return success message.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_success_message() {
        return get_string('form-success', 'format_ludic');
    }

    /**
     * Return error message.
     *
     * @return string
     */
    public function get_error_message() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');
        return $renderer->render_form_errors(['errors' => $this->errors]);
    }
}
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
 * Filepicker form element.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/form/filepicker.php');

class format_ludic_filepicker_form_element extends format_ludic_form_element {

    public $filepicker;
    public $content;
    public $options;
    public $previewsrc;

    /**
     * format_ludic_filepicker_form_element constructor.
     *
     * @param \format_ludic\form_element $element
     */
    public function __construct(\format_ludic\form_element $element) {
        parent::__construct($element);

        $this->filepicker = new MoodleQuickForm_filepicker($this->name, $this->name, array_merge(['id' => 'id_' . $this->name], $this->attributes));
        $this->filepicker->setValue($this->value);
        $this->content    = $this->filepicker->toHtml();
        $this->options    = json_encode($this->get_js_options());
        $this->previewsrc = isset($element->specific['previewsrc']) ? $element->specific['previewsrc'] : '';
    }

    /**
     * Get required options for js.
     *
     * @return stdClass
     */
    public function get_js_options() {
        global $PAGE, $CFG;

        // TODO : Définir un nombre max pour le format.
        $fpmaxbytes     = 0;
        $coursemaxbytes = 0;
        if (!empty($PAGE->course->maxbytes)) {
            $coursemaxbytes = $PAGE->course->maxbytes;
        }
        $coursemaxbytes = get_user_max_upload_file_size($PAGE->context, $CFG->maxbytes, $coursemaxbytes, $fpmaxbytes);

        $args = new stdClass();
        // Need these three to filter repositories list.
        $args->accepted_types = 'web_image';
        $args->return_types   = FILE_INTERNAL;
        $args->itemid         = $this->filepicker->getValue();
        $args->maxbytes       = $coursemaxbytes;
        $args->context        = $PAGE->context;
        $args->buttonname     = $this->filepicker->getName() . 'choose';
        $args->elementname    = $this->filepicker->getName();

        $fp                 = new file_picker($args);
        $options            = $fp->options;
        $options->context   = $PAGE->context;
        $options->savepath  = 'custom/savepath/';//TODO define savepath !
        $options->client_id = $this->extract_client_id();

        return $options;
    }

    /**
     * Extract the client id from the html output
     *
     * @return mixed
     */
    public function extract_client_id() {
        preg_match('/(?<=\id="filepicker-wrapper-)[^"]*/', $this->content, $matches);
        return $matches[0];
    }

}
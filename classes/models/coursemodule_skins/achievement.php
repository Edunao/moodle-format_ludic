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
 * Activity skin achievement.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic\coursemodule;

defined('MOODLE_INTERNAL') || die();

class achievement extends \format_ludic\skin {

    /**
     * Return best image for course edition.
     *
     * @return \stdClass
     */
    public function get_edit_image() {
        global $CFG;
        $editimage = (object) [
                'imgsrc' => $CFG->wwwroot . '/course/format/ludic/pix/default.svg',
                'imgalt' => 'Default image.'
        ];

        // Select image for best completion step.
        foreach ($this->steps as $step) {
            if ($step->state === COMPLETION_COMPLETE_PASS) {
                $editimage->imgsrc = $step->imgsrc;
                $editimage->imgalt = $step->imgalt;
            }
        }

        return $editimage;
    }

    /**
     * Return current completion step.
     *
     * @return \stdClass
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_current_step() {

        // Object with state property.
        $completioninfo = $this->get_completion_info();

        // Current step is step with same completion state.
        $currentstep    = null;
        foreach ($this->steps as $step) {

            //  Ensure to have a step.
            if ($currentstep === null || $step->state == $completioninfo->state) {
                $currentstep = $step;
            }

        }

        return $currentstep;
    }

    /**
     * This skin does not require grade.
     *
     * @return false
     */
    public function require_grade() {
        return false;
    }

    /**
     * This skin return only current step image.
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_images_to_render() {
        $step = $this->get_current_step();
        return [
                [
                        'imgsrc' => $step->imgsrc,
                        'imgalt' => isset($step->imgalt) ? $step->imgalt : ''
                ]
        ];
    }

    /**
     * Return all skin texts to render, each text with a class to select it in css.
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_texts_to_render() {
        $completioninfo = $this->get_completion_info();
        $step           = $this->get_current_step();
        return [
                ['text' => $completioninfo->completionstr, 'class' => 'completion'],
                ['text' => isset($step->extratext) ? $step->extratext : '', 'class' => 'extratext']
        ];
    }

}


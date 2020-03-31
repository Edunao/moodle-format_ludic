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
     * Return best image.
     *
     * @return \stdClass
     */
    public function get_edit_image() {
        // TODO default here
        $editimage = (object) [
                'imgsrc' => '',
                'imgalt' => 'zzdzz'
        ];

        $steps = $this->get_steps();
        foreach ($steps as $step) {
            if ($step->state === COMPLETION_COMPLETE_PASS) {
                $editimage->imgsrc = $step->imgsrc;
                $editimage->imgalt = $step->imgalt;
            }
        }

        return $editimage;
    }

    /**
     * Return all images.
     *
     * @return \stdClass[]
     */
    public function get_steps() {
        $properties = $this->get_properties();
        return isset($properties['steps']) ? $properties['steps'] : [];
    }

    public function get_current_step() {
        $completioninfo = $this->results['completioninfo'];
        $steps          = $this->get_steps();
        $currentstep    = null;
        foreach ($steps as $step) {
            if ($currentstep === null || $step->state === $completioninfo->state) {
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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function get_texts_to_render() {
        $completioninfo = $this->results['completioninfo'];
        $step           = $this->get_current_step();
        return [
                ['text' => $completioninfo->completionstr, 'class' => 'completion'],
                ['text' => isset($step->extratext) ? $step->extratext : '', 'class' => 'extratext']
        ];
    }

}


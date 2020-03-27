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
 * Section skin score class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic\section;

defined('MOODLE_INTERNAL') || die();

class score extends \format_ludic\skin {

    private $currentstep = null;

    /**
     * @return \stdClass
     */
    public function get_edit_image() {
        $image  = $this->get_default_image();
        $images = $this->get_steps();
        return count($images) > 0 ? end($images) : $image;
    }

    /**
     * @return array
     */
    public function get_steps() {
        $properties = $this->get_properties();
        return isset($properties['steps']) ? $properties['steps'] : [];
    }

    /**
     * @return \stdClass
     */
    public function get_default_image() {
        return (object) [
                'imgsrc' => 'https://picsum.photos/id/55/80/80',
                'imgalt' => 'score de section'
        ];
    }

    /**
     * This skin use and require grade.
     *
     * @return bool
     */
    public function require_grade() {
        return true;
    }

    public function get_current_step() {

        if ($this->currentstep !== null) {
            return $this->currentstep;
        }

        // Define default case.
        $currentstep = (object) [
                'threshold' => 0,
                'text'      => '',
                'imgsrc'    => '',
                'imgalt'    => ''
        ];

        $score         = 0;
        $totalscore    = 0;
        $coursemodules = $this->item->get_course_modules();
        foreach ($coursemodules as $coursemodule) {
            if (!$coursemodule->skin->require_grade()) {
                continue;
            }
            $coursemodule->skin->apply_settings();
            $cmstep     = $coursemodule->skin->get_current_step();
            $score      += isset($cmstep->score) ? $cmstep->score : 0;
            $totalscore += $coursemodule->get_weight();
        }

        $threshold = $totalscore === 0 ? 0 : ($score / $totalscore) * 100;

        $steps       = $this->get_steps();
        $sortedsteps = [];
        foreach ($steps as $step) {
            $sortedsteps[$step->threshold] = $step;
        }

        // Sort the steps.
        ksort($sortedsteps, SORT_NUMERIC);

        // The threshold for the first item is clamped down to 0.​
        if (!isset($sortedsteps[0])) {
            $keys           = array_keys($sortedsteps);
            $firstkey       = $keys[0];
            $firststep      = $sortedsteps[$firstkey];
            unset($sortedsteps[$firstkey]);

            // Add first step to start of array.
            $firststep->threshold = 0;
            $sortedsteps = [0 => $firststep] + $sortedsteps;
        }

        foreach ($sortedsteps as $step) {
            if ($threshold >= $step->threshold) {
                $currentstep = $step;
            }
        }

        $this->currentstep = $currentstep;
        return $this->currentstep;
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
        $step = $this->get_current_step();
        return [
                ['text' => isset($step->extratext) ? $step->extratext : '']
        ];
    }
}

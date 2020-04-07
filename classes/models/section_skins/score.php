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
     * Get the best image.
     *
     * @return \stdClass
     */
    public function get_edit_image() {
        $image = $this->get_default_image();
        return count($this->steps) > 0 ? end($this->steps) : $image;
    }

    /**
     * Return default image which is displayed to prevent an error.
     *
     * @return object
     */
    public function get_default_image() {
        global $CFG;
        return (object) [
                'imgsrc' => $CFG->wwwroot . '/course/format/ludic/pix/default.svg',
                'imgalt' => 'Default image.'
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

    /**
     * Return user current score step.
     *
     * @return mixed|object|null
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_current_step() {

        if ($this->currentstep !== null) {
            return $this->currentstep;
        }

        // Define default case.
        $currentstep = (object) [
                'threshold'  => 0,
                'proportion' => 0,
                'extratext'  => '',
                'extracss'   => '',
                'imgsrc'     => '',
                'imgalt'     => '',
        ];

        $gradeinfo       = $this->get_grade_info();
        $gradeproportion = $gradeinfo->proportion;

        // Copy steps into an associative array, indexed by threshold and calculate the total value parts score.
        $sortedsteps = [];
        foreach ($this->steps as $step) {
            $sortedsteps[$step->threshold] = $step;
        }

        // Sort the steps.
        ksort($sortedsteps, SORT_NUMERIC);

        // The threshold for the first item is clamped down to 0.​
        if (!isset($sortedsteps[0])) {
            $keys      = array_keys($sortedsteps);
            $firstkey  = $keys[0];
            $firststep = $sortedsteps[$firstkey];
            unset($sortedsteps[$firstkey]);

            // Add first step to start of array.
            $firststep->threshold = 0;
            $sortedsteps          = [0 => $firststep] + $sortedsteps;
        }

        // Find current step.
        foreach ($sortedsteps as $step) {
            if (($gradeproportion * 100) >= $step->threshold) {
                $currentstep = $step;
            }
        }

        // Set data.
        $currentstep->proportion = $gradeproportion;
        $currentstep->score      = $gradeinfo->score;
        $currentstep->scoremax   = $gradeinfo->scoremax;

        // Return step.
        $this->currentstep = $currentstep;
        return $this->currentstep;
    }

    /**
     * This skin return only current step image.
     *
     * @return array
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
     * Add additional css if required.
     *
     * @return string
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_additional_css() {
        $step = $this->get_current_step();
        return isset($step->extracss) ? $step->extracss : '';
    }

    /**
     * Return all skin texts to render, each text with a class to select it in css.
     *
     * @return array
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_texts_to_render() {
        $step   = $this->get_current_step();
        return [
                ['text' => $step->score, 'class' => 'score'],
                ['text' => '/', 'class' => 'fractionbar'],
                ['text' => $step->scoremax, 'class' => 'scoremax'],
                ['text' => $step->score . '/' . $step->scoremax, 'class' => 'fullscore'],
                ['text' => $step->proportion . '%', 'class' => 'percentage'],
                ['text' => isset($step->extratext) ? $step->extratext : '', 'class' => 'extratext']
        ];
    }
}

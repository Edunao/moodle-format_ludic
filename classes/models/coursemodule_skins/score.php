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
 * Activity skin score.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic\coursemodule;

defined('MOODLE_INTERNAL') || die();

class score extends \format_ludic\skin {

    private $currentstep = null;

    /**
     * The resulting score is calculated a
     *
     * @return \stdClass
     */
    public function get_current_step() {

        // We have a stored current step then return it.
        if ($this->currentstep !== null) {
            return $this->currentstep;
        }

        // Define default case.
        $currentstep = (object) [
                'threshold'  => 0,
                'proportion' => 0,
                'score'      => 0,
                'scorepart'  => 0,
                'text'       => '',
                'imgsrc'     => '',
                'imgalt'     => ''
        ];

        // Copy steps into an associative array, indexed by threshold and calculate the total value parts score.
        $steps       = $this->get_steps();
        $sortedsteps = [];
        $total       = 0;
        foreach ($steps as $step) {
            $total                         += $step->scorepart;
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

        // Derive each of their proportion values for discrete score calculation.
        $grade = $this->results['gradeinfo']->grade;
        $sum   = 0;
        foreach ($sortedsteps as $step) {
            // Prevent division by 0.
            if ($total === 0) {
                $step->proportion = 0;
                continue;
            }

            // Define proportion and current step.
            $sum              += $step->scorepart;
            $step->proportion = $sum / $total;
            if ($grade >= $step->threshold) {
                $currentstep = $step;
            }
        }

        // If the sum of score parts is 0 then the linear_score_part value is clamped to 1.
        $linearscorepart = $this->get_properties()['linearscorepart'];
        $linearscorepart = $total === 0 ? 1 : $linearscorepart;

        // Derive the normalised proportion value for linear score calculation.
        $linearproportion = $linearscorepart / $total;
        $gradefactor      = $this->results['gradeinfo']->gradefactor;
        $proportion       = $currentstep->proportion > 0 ? $gradefactor * $linearproportion + $currentstep->proportion : 0;

        // Calculate score.
        $score = $this->weight * $proportion;
        // It should be rounded to 2 significant figures for display and for use by the topics skin.
        $score = (int) floatval(sprintf('%.2g', $score));
        // Add score to current step.
        $currentstep->score = $score;

        // Ensure step has an image.
        if (empty($currentstep->imgsrc)) {
            $defaultimg          = $this->get_default_image();
            $currentstep->imgsrc = $defaultimg->imgsrc;
            $currentstep->imgalt = $defaultimg->imgalt;
        }

        // Return current step.
        $this->currentstep = $currentstep;
        return $this->currentstep;
    }

    /**
     * Get the best image.
     *
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
     * @return object
     */
    public function get_default_image() {
        return (object) [
                'imgsrc' => 'https://picsum.photos/id/66/80/80',
                'imgalt' => 'score de coursemodule'
        ];
    }

    /**
     * This skin require grade.
     *
     * @return true
     */
    public function require_grade() {
        return true;
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
                ['text' => $step->score, 'class' => 'score'],
                ['text' => '/', 'class' => 'fractionbar'],
                ['text' => $this->weight, 'class' => 'scoremax'],
                ['text' => $step->score . '/' . $this->weight, 'class' => 'fullscore'],
                ['text' => isset($step->extratext) ? $step->extratext : '', 'class' => 'extratext']
        ];
    }
}


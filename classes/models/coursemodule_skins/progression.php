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

class progression extends \format_ludic\skin {

    private $currentstep = null;

    public static function get_editor_config(){
        return [
            "settings" => [
                "name"                  => "text",
                "main-css"              => "css",
                "linear-value-part"     => "int",
            ],
            "steps" => [
                "progression-threshold" => "number",
                "fixed-value-part"      => "int",
                "step-image"            => "image",
                "step-text"             => "string",
                "step-css"              => "css"
            ]
        ];
    }

    public static function get_unique_name(){
        return 'cm-score';
    }

    /**
     * Return an instance of this class.
     *
     * @return menubar
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function get_instance() {
        return (object)[
            'id'          => self::get_unique_name(),
            'location'    => 'coursemodule',
            'type'        => 'progression',
            'title'       => 'Progression de base',
            'description' => 'On avance avec des pourcentages',
            'settings'    => self::get_editor_config(),
        ];
    }

    /**
     * Return user current score step.
     *
     * @return mixed|object|null
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
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
            'scoremax'   => 0,
            'scorepart'  => 0,
            'extratext'  => '',
            'extracss'   => '',
            'imgsrc'     => '',
            'imgalt'     => ''
        ];

        // Copy steps into an associative array, indexed by threshold and calculate the total value parts score.
        $sortedsteps = [];
        $total       = 0;
        foreach ($this->steps as $step) {
            $total                         += $step->scorepart;
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

        // Derive each of their proportion values for discrete score calculation.
        $gradeinfo       = $this->get_grade_info();
        $gradeproportion = $gradeinfo->proportion;
        $sum             = 0;
        foreach ($sortedsteps as $step) {
            // Prevent division by 0.
            if ($total === 0) {
                $step->proportion = 0;
                continue;
            }

            // Define proportion and current step.
            $sum              += $step->scorepart;
            $step->proportion = $sum / $total;
            if (($gradeproportion * 100) >= $step->threshold) {
                $currentstep = $step;
            }
        }

        // If the sum of score parts is 0 then the linear_score_part value is clamped to 1.
        $linearscorepart = $this->get_properties()['linearscorepart'];
        $linearscorepart = $total === 0 ? 1 : $linearscorepart;

        // Derive the normalised proportion value for linear score calculation.
        $linearproportion = $linearscorepart / $total;

        // Calculate score proportion.
        if ($currentstep->proportion > 0) {
            $currentstep->proportion = $gradeproportion * $linearproportion + $currentstep->proportion;
            $currentstep->proportion = $currentstep->proportion > 1 ? 1 : $currentstep->proportion;
        }

        // Calculate score.
        $score = $this->get_weight() * $currentstep->proportion;
        // It should be rounded to 2 significant figures for display and for use by the topics skin.
        $score = (int) floatval(sprintf('%.2g', $score));

        // Add score to current step.
        $currentstep->score    = $score;
        $currentstep->grade = $gradeinfo->grade;
        $currentstep->grademax = $gradeinfo->grademax;

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
        $image = $this->get_default_image();
        return count($this->steps) > 0 ? end($this->steps) : $image;
    }

    /**
     * Return default image which is displayed to prevent an error.
     *
     * @return object
     */
    public function get_default_image() {
        global $OUTPUT;
        return (object) [
            'imgsrc' => $OUTPUT->image_url('default', 'format_ludic')->out(),
            'imgalt' => 'Default image.'
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
        $step   = $this->get_current_step();
        $weight = $this->get_weight();
        return [
            ['text' => $step->score, 'class' => 'score'],
            ['text' => (int) $step->grade, 'class' => 'grade'],
            ['text' => '/', 'class' => 'fractionbar-1'],
            ['text' => '/', 'class' => 'fractionbar-2'],
            ['text' => $weight, 'class' => 'scoremax'],
            ['text' => $step->grademax, 'class' => 'grademax'],
            ['text' => $step->score . '/' . $weight, 'class' => 'fullscore'],
            ['text' => (int) $step->grade . '/' . $step->grademax, 'class' => 'fullgrade'],
            ['text' => $step->proportion . '%', 'class' => 'percentage'],
            ['text' => isset($step->extratext) ? $step->extratext : '', 'class' => 'extratext']
        ];
    }

    /**
     * Add additional css if required.
     *
     * @return string
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_additional_css() {
        $step = $this->get_current_step();
        return isset($step->extracss) ? $step->extracss : '';
    }

    /**
     * Return user score.
     *
     * @return mixed
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_score() {
        $step = $this->get_current_step();
        return $step->score;
    }

}


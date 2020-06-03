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

    public static function get_editor_config(){
        return [
            "settings" => [
                "name"                  => "text",
                "description"           => "text",
                "main-css"              => "css",
            ],
            "steps" => [
                "threshold"       => "number",
                "step-image"            => "image",
                "step-text"             => "string",
                "step-css"              => "css"
            ]
        ];
    }

    public static function get_unique_name(){
        return 'section-score';
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
            'location'    => 'section',
            'type'        => 'score',
            'title'       => 'Score de base pour section',
            'description' => 'des points et puis voilà, mais pour la section',
            'settings'    => self::get_editor_config(),
        ];
    }

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
        global $OUTPUT;
        return (object) [
                'imgsrc' => $OUTPUT->image_url('default', 'format_ludic')->out(),
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
                ['text' => $step->score . '<span class="unit">pts</span>', 'class' => 'score number'],
                ['text' => '/', 'class' => 'fractionbar unit'],
                ['text' => $step->scoremax, 'class' => 'scoremax number'],
                ['text' => $step->score . '/' . $step->scoremax, 'class' => 'fullscore number'],
                ['text' => $step->proportion . '<span class="unit">%</span>', 'class' => 'percentage number'],
                ['text' => isset($step->extratext) ? $step->extratext : '', 'class' => 'extratext']
        ];
    }
}

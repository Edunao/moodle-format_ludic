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

class progress extends \format_ludic\skin {

    private $currentstep = null;

    public static function get_editor_config() {
        return [
            "settings" => [
                "name"        => "text",
                "main-css"    => "css",
                "description" => "text",
            ],
            "steps"    => [
                "threshold" => "int",
                // Min percent of progress to display images
                "images"    => [
                    "imgsrc" => "image",
                    "imgalt" => "image",
                ],
                "css"       => "text",

            ]
        ];
    }

    public static function get_unique_name() {
        return 'cm-progress';
    }

    /**
     * Return an instance of this class.
     *
     * @return menubar
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function get_instance() {
        return (object) [
            'id'          => self::get_unique_name(),
            'location'    => 'coursemodule',
            'type'        => 'progress',
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
            'threshold' => 0,
            'percent'   => 0,
            'extratext' => '',
            'css'       => '',
            'images'    => [
                [
                    'imgsrc' => '',
                    'imgalt' => ''
                ]
            ]
        ];

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

        // Get percent of progres
        $percent = $this->get_percent();

        // Get threshold
        foreach ($sortedsteps as $step) {
            if ($step->threshold <= $percent) {
                $currentstep = $step;
            }
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
        return count($this->steps) > 0 ? end($this->steps)->images[0] : $image;
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
        return false;
    }

    /**
     * This skin return only current step images.
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_images_to_render() {
        $images      = [];
        $currentstep = $this->get_current_step();

        foreach ($currentstep->images as $key => $image) {
            $image->class = 'img-step img-step-' . $key;
            $images[]     = $image;
        }

        return $images;
    }

    public function get_texts_to_render() {
        $percent = floor($this->get_percent());
        return [
            ['text'  => $percent,
             'class' => 'percent percent-' . $percent
            ]
        ];
    }

    public function get_additional_css() {
        $step    = $this->get_current_step();
        $percent = $this->get_percent();
        $css     = isset($step->css) ? $step->css : '';
        $css     = str_replace('[percent]', $percent, $css);
        return $css;
    }

    public function get_percent() {
        $results = $this->item->get_user_results();

        if ($results['completioninfo']->state === COMPLETION_DISABLED && $results['gradeinfo']->grademax === 0) {
            return 0;
        }

        if ($results['gradeinfo']->grademax > 0) {
            return $results['gradeinfo']->proportion * 100;
        }

        if ($results['completioninfo']->state == COMPLETION_COMPLETE || $results['completioninfo']->state == COMPLETION_COMPLETE_PASS) {
            return 100;
        }

        return 0;
    }

}


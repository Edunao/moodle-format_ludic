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
 *
 *
 * @package    TODO
 * @subpackage TODO
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     Céline Hernandez <celine@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic\section;

defined('MOODLE_INTERNAL') || die();

class progress extends \format_ludic\skin {

    private $currentstep = null;

    public static function get_editor_config() {
        return [
            "settings"   => [
                "name"        => "text",
                "main-css"    => "css",
                "description" => "text",
            ],
            "properties" => [
                // TODO : demander à Daniel
                "target" => "int",
                // The Target score defines the 100% reference value for the section. If zero then the sum of activity scores will be used
            ],
            "steps"      => [
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
        return 'section-progress';
    }

    public static function get_instance() {
        return new self((object) [
            'id'          => self::get_unique_name(),
            'location'    => 'section',
            'type'        => 'progress',
            'title'       => 'Récompenses d\'activités',
            'description' => 'Chaque activité niveau de réussite des activités change l\'état.',
            'settings'    => self::get_editor_config(),
        ]);
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

    public function require_grade() {
        return true;
    }

    public function get_images_to_render() {
        $images      = [];
        $currentstep = $this->get_current_step();

        foreach ($currentstep->images as $key => $image) {
            $image->class = 'img-step img-step-' . $key;
            $images[]     = $image;
        }

        return $images;
    }

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

        // Copy steps into an associative array, indexed by threshold
        $sortedsteps = [];
        foreach ($this->steps as $step) {
            $sortedsteps[$step->threshold] = $step;
        }
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

        $this->currentstep = $currentstep;
        return $currentstep;
    }

    public function get_percent() {
        $results = $this->item->get_user_results();
        $cms = $this->item->get_course_modules();

        $percent = 0;
        $nbcms   = 0;
        foreach ($results['resultsdetails'] as $cmresult) {

            // Cm has no completion or grade, ignore it
            if ($cmresult['results']['completioninfo']->type === COMPLETION_DISABLED && $cmresult['results']['gradeinfo']->grademax === 0) {
                continue;
            }

            // Cm weight is 0, ignore it too
            $cm = $cms[$cmresult['cmid']];
            if($cm->get_weight() == 0){
                continue;
            }

            // Cm
            if ($cmresult['results']['gradeinfo']->grademax > 0) {
                $percent += $cmresult['results']['gradeinfo']->proportion * $cm->get_weight();
            } else if ($cmresult['results']['completioninfo']->state == COMPLETION_COMPLETE || $cmresult['results']['completioninfo']->state == COMPLETION_COMPLETE_PASS) {
                $percent += 1 * $cm->get_weight();
            }

            $nbcms += $cm->get_weight();
        }

        return $nbcms == 0 ? 0 : $percent * 100 / $nbcms;
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
}
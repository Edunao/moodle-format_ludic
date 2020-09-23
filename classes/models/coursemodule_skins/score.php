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
    private $nextstep    = null;
    private $finalscore  = null;
    private $maxgrade    = 0;
    private $grade = 0;

    public static function get_editor_config() {
        return [
            "settings"   => [
                "title"        => "text",
                "description" => "textarea",
            ],
            "properties"    => [
                'css' => 'textarea',
                'linearscorepart' => 'int',
                "steps" => [
                    'threshold' => 'int',
                    'scorepart' => 'int',
                    'extracss'  => 'textarea',
                    'background' => 'image'
                ],
            ]
        ];
    }

    public static function get_unique_name() {
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
        return new self((object) [
            'id'          => self::get_unique_name(),
            'location'    => 'coursemodule',
            'type'        => 'score',
            'title'       => 'Score activité',
            'description' => 'On gagne des points en fonction de la note de l\'activité',
            'settings'    => self::get_editor_config(),
        ]);
    }

    /**
     * Return user current score step.
     *
     * @return mixed|object|null
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function prepare_data() {

        if ($this->finalscore !== null) {
            return true;
        }

        // Prepare current and next steps
        $currentscorepart = 0;
        $totalscorepart   = 0;
        $linearpart       = $this->get_properties('linearscorepart');

        $weight = $this->get_weight();

        $sortedsteps = $this->get_sorted_steps();

        // Get user grade and step
        $gradeinfo = $this->get_grade_info();
        $this->maxgrade  = $gradeinfo->grademax;
        $this->grade     = $gradeinfo->grade;
        $percent   = $gradeinfo->proportion * 100;

        // Get validated steps and next step
        foreach ($sortedsteps as $step) {

            if ($percent >= $step->threshold) {
                $this->currentstep = $step;
                $currentscorepart  += $step->scorepart;
            } else if ($this->nextstep == null) {
                $this->nextstep = $step;
            }
            $totalscorepart += $step->scorepart;
        }
        if($totalscorepart == 0){
            $linearpart = 1;
        }

        // Prepare next step score threshold
        if($this->nextstep){
            $this->nextstep->thresholdgrade = $this->nextstep->threshold * $this->maxgrade / 100;
        }

        // Get final score form grade, score part and linear part
        $this->finalscore = ($currentscorepart + ($linearpart * $this->grade / $this->maxgrade)) * $weight / ($totalscorepart + $linearpart);

        // Ensure step has an image.
        if (empty($this->currentstep->background)) {
            $defaultimg          = $this->get_default_image();
            $this->currentstep->background = (object)[
                'imgsrc'    => $defaultimg->imgsrc,
                'imgalt'    =>  $defaultimg->imgalt
            ];
        }

        return true;
    }

    /**
     * Get the best image.
     *
     * @return \stdClass
     */
    public function get_edit_image() {
        $image = $this->get_default_image();
        return count($this->steps) > 0 ? end($this->steps)->background : $image;
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
            $step->background
        ];
    }

    public function get_current_step(){
        if($this->currentstep === null){
            $this->prepare_data();
        }

        return $this->currentstep;
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
        $score  = $this->get_score();
        $step   = $this->get_current_step();
        $weight = $this->get_weight();
        return [
            [
                'text'  => round($score, 2),
                'class' => 'score'
            ],
            [
                'text'  => (int) $this->grade,
                'class' => 'grade'
            ],
            [
                'text'  => '/',
                'class' => 'fractionbar-1'
            ],
            [
                'text'  => '/',
                'class' => 'fractionbar-2'
            ],
            [
                'text'  => $weight,
                'class' => 'scoremax'
            ],
            [
                'text'  => (int) $this->grade . '/' . $this->maxgrade,
                'class' => 'fullgrade'
            ],
            [
                'text'  => isset($step->extratext) ? $step->extratext : '',
                'class' => 'extratext'
            ],
            [
                'text'  => isset($this->nextstep->thresholdgrade) ? $this->nextstep->thresholdgrade . '/' . $this->maxgrade : '',
                'class' => 'threshold'
            ],
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
        $currenstep = $this->get_current_step();
        return isset($currenstep->extracss) ? $currenstep->extracss : '';
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

        if($this->finalscore !== null){
            return $this->finalscore;
        }

        $this->prepare_data();
        return $this->finalscore;
    }

    public function get_skin_results() {

        $skinresults = parent::get_skin_results();

        $skinresults['score'] = round($this->get_score(), 2);

        $skinresults['scorehasweight'] = true;

        return $skinresults;
    }

    private function get_sorted_steps(){

        // Prepare steps
        // Copy steps into an associative array, indexed by threshold and calculate the total value parts score.
        $sortedsteps = [];
        foreach ($this->steps as $step) {
            if ($step->scorepart < 0) {
                $step->scorepart = 0;
            }
            $sortedsteps[$step->threshold] = $step;
        }
        ksort($sortedsteps, SORT_NUMERIC);

        // Prepare first step if needed
        if (!array_key_exists(0, $sortedsteps)) {
            $firstkey       = array_keys($sortedsteps)[0];
            $sortedsteps[0] = $sortedsteps[$firstkey];
            unset($sortedsteps[$firstkey]);
        }

        return $sortedsteps;
    }
}


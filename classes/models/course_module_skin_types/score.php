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
 * Activity skin score classes
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../skinned_course_module.php');
require_once(__DIR__ . '/../skin_type.php');
require_once(__DIR__ . '/../skin_template.php');

class skinned_course_module_score extends \format_ludic\skinned_course_module {
    public function __construct(skin_template_course_module_score $template) {
        parent::__construct($template);
        $this->template = $template;
        $this->skintype = new skin_type_course_module_score();
    }
}

class skin_type_course_module_score extends \format_ludic\course_module_skin_type {
    public static function get_name() {
        return 'score';
    }

    public static function get_editor_config() {
        return [
            "steps" => [
                "threshold"   => "int",
                "image"       => "image",
                "text"        => "text",
                "css"         => "textarea",
                "score"       => "text",
            ]
        ];
    }
}

class skin_template_course_module_score extends \format_ludic\course_module_skin_template {

    protected $steps;

    public function __construct($config) {
        // Leave the job of extracting common parameters such as title and description to the parent class.
        parent::__construct($config);

        // Copy steps into an associative array, indexed by threshold and sort it.
        foreach ($config->steps as $step) {
            if (!array_key_exists('threshold', $step)) {
                continue;
            }
            $stepsbythreshod[$step->threshold] = $step;
        }

        // Make sure that there is a first step starting at 0.
        if (!array_key_exists(0, $stepsbythreshod)) {

            // Add first step to start of array.
            $stepsbythreshod[0] = [
                'threshold'  => 0,
                'background' => '',
                'text'       => '',
                'css'        => '',
            ];
        }

        // Sort the steps into threshold order and reindex them starting at 0.
        ksort($stepsbythreshod, SORT_NUMERIC);
        $this->steps = array_values($stepsbythreshod);
    }

    public function get_edit_image() {
        return end($this->steps)->background;
    }

    public function get_images_to_render($skindata) {
        return [$skindata->image];
    }

    public function get_css($skindata) {
        return $this->css . $skindata->css;
    }

    public function get_texts_to_render($skindata) {
        $weight = $skindata->weight;
        return [
            [
                'text'  => $skindata->score,
                'class' => 'score'
            ],
            [
                'text'  => (int) $skindata->grade,
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
                'text'  => $skindata->text,
                'class' => 'extratext'
            ],
        ];
    }

    public function setup_skin_data($skindata, $skinresults, $userdata) {
        // Get user score.
        $grade = ceil($userdata->proportion * 100);

        // Find current step.
        $currentidx = 0;
        $cnt = count($this->steps);
        for ($i = 0; $i < $cnt; ++$i) {
            if ($grade < $this->steps[$i]->threshold) {
                break;
            }
            $currentidx = $i;
        }
        $step = $this->steps[$currentidx];
        $thisthreshold = $step->threshold;
        $nextthreshold = ($currentidx + 1 < count($this->steps)) ?
            $this->steps[$currentidx + 1]->threshold :
            \max($currentidx + 1, 100);
        $diff0         = $grade - $thisthreshold;
        $diff1         = $nextthreshold - $thisthreshold;
        $rawfactor     = ($diff1 > 0) ? $diff0 / $diff1 : 0;
        $cleanfactor   = ceil($rawfactor * 20) / 20;

        $score = format_ludic_resolve_ranges_in_text($step->score, $cleanfactor) * $userdata->weight / 100;
        // Store away the results.
        $skindata->grade    = $grade;
        $skindata->score    = $score;
        $skindata->image    = $step->background;
        $skindata->text     = $step->text;
        $skindata->css      = $step->css;
        $skindata->weight   = $userdata->weight;
        $skinresults->score = $score;

    }
}

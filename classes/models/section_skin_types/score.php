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
 * Section skin score classes
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../skinned_section.php');
require_once(__DIR__ . '/../skin_type.php');
require_once(__DIR__ . '/../skin_template.php');

class skinned_section_score extends \format_ludic\skinned_section {
    public function __construct(skin_template_section_score $template){
        parent::__construct($template);
        $this->template = $template;
        $this->skintype = new skin_type_section_score();
    }
}

class skin_type_section_score extends \format_ludic\section_skin_type {
    public static function get_name() {
        return 'score';
    }

    public static function get_editor_config() {
        return [
            "steps" => [
                "threshold"   => "int",
                "image"       => "background",
                "text"        => "text",
                "css"         => "textarea",
            ]
        ];
    }
}

class skin_template_section_score extends \format_ludic\section_skin_template {

    protected $steps;

    public function __construct($config) {
        // leave the job of extracting common parameters such as title and description to the parent class
        parent::__construct($config);

        // Copy steps into an associative array, indexed by threshold and sort it
        $sortedsteps = [];
        foreach ($config->steps as $step) {
            if (!array_key_exists('threshold', $step)){
                continue;
            }
            $stepsbythreshod[$step->threshold] = $step;
        }

        // make sure that there is a first step starting at 0
        if (!array_key_exists(0, $stepsbythreshod)) {
            $stepsbythreshod[0] = [
                'threshold'  => 0,
                'background' => '',
                'text'       => '',
                'css'        => '',
            ];
        }

        // sort the steps into threshold order and reindex them starting at 0
        ksort($stepsbythreshod, SORT_NUMERIC);
        $this->steps = array_values($stepsbythreshod);
    }

    public function get_edit_image() {
        $image = 'default';
        return count($this->steps) > 0 ? end($this->steps)->background : $image;
    }

    public function get_images_to_render($skindata) {
        return [ $skindata->image ];
    }

    public function get_css($skindata) {
        return $this->css . $skindata->css;
    }

    public function get_texts_to_render($skindata) {
        return [
            [
                'text'  => $skindata->score . '<span class="unit">pts</span>',
                'class' => 'score number'
            ],
            [
                'text'  => '/',
                'class' => 'fractionbar unit'
            ],
            [
                'text'  => $skindata->text,
                'class' => 'extratext'
            ]
        ];
    }

    public function setup_skin_data($skindata, $userdata, $section) {
        // aggregate the course section scores
        $score = 0;
        foreach ($userdata as $cmdata) {
            $score += $cmdata->score;
        }

        // Find current step
        $currentstep = $this->steps[0];
        foreach ($this->steps as $step) {
            if ($score < $step->threshold) {
                break;
            }
            $currentstep = $step;
        }

        // Store away the results
        $skindata->score    = $score;
        $skindata->image    = $currentstep->background;
        $skindata->text     = $currentstep->text;
        $skindata->css      = $currentstep->css;
    }
}

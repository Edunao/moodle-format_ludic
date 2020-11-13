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
 * Section skin progressions classes
 *
 * @package   format_ludic
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     Céline Hernandez <celine@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../skinned_section.php');
require_once(__DIR__ . '/../skin_type.php');
require_once(__DIR__ . '/../skin_template.php');

class skinned_section_progress extends \format_ludic\skinned_section  {
    public function __construct(skin_template_section_progress $template) {
        parent::__construct($template);
        $this->template = $template;
        $this->skintype = new skin_type_section_progress();
    }
}

class skin_type_section_progress extends \format_ludic\section_skin_type {
    public static function get_name() {
        return 'progress';
    }

    public static function get_editor_config() {
        return [
            "steps" => [
                "threshold" => "int",
                "image0"    => "image",
                "image1"    => "image",
                "image2"    => "image",
                "image3"    => "image",
                "image4"    => "image",
                "text"      => "text",
                "css"       => "textarea",
            ]
        ];
    }

    public static function get_target_string_id() {
        return 'cs-progress-target';
    }
}

class skin_template_section_progress extends \format_ludic\section_skin_template {

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
                'image0'     => '',
                'image1'     => '',
                'image2'     => '',
                'image3'     => '',
                'image4'     => '',
                'text'       => '',
                'css'        => '',
            ];
        }

        // Sort the steps into threshold order and reindex them starting at 0.
        ksort($stepsbythreshod, SORT_NUMERIC);
        $this->steps = array_values($stepsbythreshod);
    }

    public function get_edit_image() {
        $image = 'default';
        return count($this->steps) > 0 ? end($this->steps)->image0 : $image;
    }

    public function get_images_to_render($skindata) {
        return $skindata->images;
    }

    public function get_css($skindata) {
        return $this->css . $skindata->css;
    }

    public function get_texts_to_render($skindata) {
        return [
            [
                'text'  => $skindata->progress . '<span class="unit">pts</span>',
                'class' => 'score number'
            ],
            [
                'text'  => $skindata->progress,
                'class' => 'number percent'
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

        // Sum the course section scores.
        $score    = 0;
        $maxscore = 0;
        foreach ($userdata as $cmdata) {
            $score    += $cmdata->score;
            $maxscore += $cmdata->maxscore;
        }
        $maxscore = max($maxscore, 1);

        // Derive the target score for the top end of the progression scale.
        $targetscore   = ($section->target + 0 <= 0) ? $maxscore : max(1, $section->target);
        $laststepscore = max(1, end($this->steps)->threshold);
        $stepfactor    = 1 / $laststepscore;
        $progress      = min(1, $score / $targetscore);

        // Find current step.
        $currentstep = $this->steps[0];
        foreach ($this->steps as $step) {
            if ($progress < $step->threshold * $stepfactor) {
                break;
            }
            $currentstep = $step;
        }

        // Store away the results.
        $skindata->images   = [];
        for ($i = 0; $i < 5; ++$i) {
            $image = $currentstep->{'image' . $i};
            if (!$image) {
                continue;
            }
            $skindata->images[] = $image;
        }
        $skindata->progress = ceil($progress * 100);
        $skindata->text     = $currentstep->text;
        $skindata->css      = format_ludic_resolve_ranges_in_text($currentstep->css, $progress);
    }
}
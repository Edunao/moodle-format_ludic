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
 * Activity skin achievement classes.
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

class skinned_course_module_achievement extends \format_ludic\skinned_course_module {
    public function __construct(skin_template_course_module_achievement $template) {
        parent::__construct($template);
        $this->template = $template;
        $this->skintype = new skin_type_course_module_achievement();
    }
}

class skin_type_course_module_achievement extends \format_ludic\course_module_skin_type {
	private $editimage = "default";
	private $maxscore = 0;

    public static function get_name() {
        return 'achievement';
    }

    public static function get_editor_config() {
        return [
            "steps"    => [
                "state"       => "text",
                "score"       => "int",
                "background"  => "image",
                "text"        => "string",
                "css"         => "css"
            ]
        ];
    }
}

class skin_template_course_module_achievement extends \format_ludic\course_module_skin_template {

    private $steps = [null, null, null, null, null];

    public function __construct($config) {
        // Leave the job of extracting common parameters such as title and description to the parent class.
        parent::__construct($config);

        // a lookup table of state names to achievement levels
        $statemappings = [
            ""              => 0,
            "none"          => 0,
            "incomplete"    => 0,
            "fail"          => 1,
            "pass"          => 2,
            "complete"      => 3,
            "perfect"       => 4,
        ];

        // Iterate over steps.
        $lowest = count($this->steps);
        foreach ($config->steps as $step) {
            // Identify which achievement level the step belongs to.
            $cleanstepstate = \strtolower($step->state);
            if (!isset($statemappings[$cleanstepstate])) {
                // TODO : Display a friendly error before ignoring this entry.
                continue;
            }
            $achievementlevel = $statemappings[$cleanstepstate];
            $lowest = min($lowest, $achievementlevel);
            $this->steps[$achievementlevel] = (object)[
                "score"      => isset($step->score) ? $step->score : 0,
                "background" => isset($step->background) ? $step->background : "default",
                "text"       => isset($step->text) ? $step->text : "",
                "css"        => isset($step->css) ? $step->css : "",
            ];
            // Clamp the score to the 0..100 range.
            $this->steps[$achievementlevel]->score = max($this->steps[$achievementlevel]->score, 0);
            
            $this->maxscore = max($this->maxscore, $this->steps[$achievementlevel]->score);
			if (isset($step->background) && $step->background) {
				$this->editimage = $step->background;
			}

        }

        // If no steps were found at all then just put in a default one.
        if ($lowest == count($this->steps)) {
            --$lowest;
            $this->steps[$lowest] = (object)[
                "score"      => 0,
                "background" => "default",
                "text"       => "",
                "css"        => "",
            ];
        }

        // Fill in any missing steps.
        for ($i = 0; $i < $lowest; ++$i) {
            $this->steps[$i] = $this->steps[$lowest];
        }
        for ($i = $lowest + 1; $i <= 4; ++$i) {
            $this->steps[$i] = $this->steps[$i] ?: $this->steps[$i - 1];
        }
    }

    /**
     * Return best image for course editing.
     *
     * @return \stdClass
     */
    public function get_edit_image() {
        return $this->editimage;
    }


    /**
     * This skin return only current step image.
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_images_to_render($skindata) {
        return [$skindata->background];
    }

    public function get_css($skindata) {
        return $this->css . $skindata->css;
    }

    /**
     * Return all skin texts to render, each text with a class to select it in css.
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_texts_to_render($skindata) {
        return [
            [
                'text'  => $skindata->text,
                'class' => 'extratext'
            ]
        ];
    }

    public function setup_skin_data($skindata, $skinresults, $userdata) {
        // Determine which step to use.
        switch($userdata->richstate) {
            case COMPLETION_INCOMPLETE :
                $idx = 0;
                break;
            case COMPLETION_COMPLETE_FAIL :
                $idx = 1;
                break;
            case COMPLETION_COMPLETE_PASS :
                $idx = 2;
                break;
            case COMPLETION_COMPLETE :
                $idx = 3;
                break;
            case COMPLETION_COMPLETE_PERFECT :
                $idx = 4;
                break;
            default :
                $idx = 0;
                break;
        }
        $step = $this->steps[$idx];

        // Calculate the result score.
        $factor = $this->maxscore ? $step->score / $this->maxscore : 0;
		$cleanfactor = ceil($factor * 20) / 20;
		$score = $cleanfactor * $userdata->weight;

        // Store away the results.
        $skindata->score        = $score;
        $skindata->background   = $step->background;
        $skindata->text         = $step->text;
        $skindata->css          = $step->css;
        $skinresults->score     = $score;
    }
}


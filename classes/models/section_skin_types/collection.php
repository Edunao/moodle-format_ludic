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
 * Section skin collection classes
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

class skinned_section_collection extends \format_ludic\skinned_section  {
    public function __construct(skin_template_section_collection $template) {
        parent::__construct($template);
        $this->template = $template;
        $this->skintype = new skin_type_section_collection();
    }
}

class skin_type_section_collection extends \format_ludic\section_skin_type {
    public static function get_name() {
        return 'collection';
    }

    public static function get_editor_config() {
        return [
            "layouts" => [
                "nbactivities"  => "int",
                "background"    => "image",
                "css"           => "textarea",
            ],
            "stamps" => [
                "todo"          => "image",
                "done"          => "image"
            ]
        ];
    }
}

class skin_template_section_collection extends \format_ludic\section_skin_template {

    protected $layouts = [];
    protected $stamps  = [];

    public function __construct($config) {
        // Leave the job of extracting common parameters such as title and description to the parent class.
        parent::__construct($config);

        $this->stamps     = $config->stamps;
        $this->layouts    = $config->layouts;
    }

    public function get_edit_image() {
        return end($this->layouts)->background;
    }

    public function get_images_to_render($skindata) {
        // Prime the images array with the background image.
        $images = [];
        $images[] = (object)[
            'src'   => $skindata->background,
            'class' => 'img-0'
        ];

        // Randomize stamp order.
        global $COURSE;
        $stamps = $this->stamps;
        srand($COURSE->id);
        shuffle($stamps);

        // Get stamp for each completed activity activity.
        foreach ($skindata->cmstates as $idx => $cmstate) {
            switch ($cmstate) {
                case COMPLETION_COMPLETE :
                    $imagename = "done";
                    break;
                default :
                    $imagename = "todo";
                    break;
            }
            $images[] = (object)[
                'src'   => $stamps[$idx % count($stamps)]->$imagename,
                'class' => 'img-step img-step-' . count($images)
            ];
        }

        // Return the result.
        return $images;
    }

    public function get_css($skindata) {
        return $skindata->css;
    }

    public function get_texts_to_render($skindata) {
        return [];
    }

    public function setup_skin_data($skindata, $userdata, $section) {
        // Select the best layout based on the number of activities in the section.
        $nbactivities = count($userdata);
        $bestlayout = (object)[
            'nbactivities'  => -1,
            'background'    => '',
            'css'           => '',
        ];

        foreach ($this->layouts as $layout) {
            if ($layout->nbactivities <= $nbactivities && $layout->nbactivities > $bestlayout->nbactivities) {
                $bestlayout = $layout;
            }
        }

        // Extract the background and css from the layout.
        $skindata->background = $bestlayout->background;
        $skindata->css = $bestlayout->css;

        // Scan the state records, extracting their properties as required.
        $cmstates = [];
        foreach ($userdata as $cmdata) {
            switch($cmdata->state) {
                case COMPLETION_COMPLETE :
                case COMPLETION_COMPLETE_PASS :
                    break;
                case COMPLETION_COMPLETE_PERFECT :
                    $state = COMPLETION_COMPLETE;
                    break;
                case COMPLETION_DISABLED :
                    $state = COMPLETION_DISABLED;
                    break;
                default :
                    $state = COMPLETION_INCOMPLETE;
                    break;
            }
            // If not visible, then mark it as not to be displayed but keep it in the sequence to stabilise ordering.
            if ($cmdata->visible != 1) {
                $state = COMPLETION_DISABLED;
            }
            $cmstates[$cmdata->cmid] = $state;
        }
        ksort($cmstates);
        $skindata->cmstates  = array_values($cmstates);
        $skindata->sectionid = $section->id;
    }
}

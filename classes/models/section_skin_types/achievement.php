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
 * Section skin achievement classes
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

class skinned_section_achievement extends \format_ludic\skinned_section  {
    public function __construct(skin_template_section_achievement $template) {
        parent::__construct($template);
        $this->template = $template;
        $this->skintype = new skin_type_section_achievement();
    }
}

class skin_type_section_achievement extends \format_ludic\section_skin_type {
    public static function get_name() {
        return 'achievement';
    }

    public static function get_editor_config() {
        return [
                "background"  => "image",
                "finalimage"  => "image",
                "incomplete"  => "image",
                "fail"        => "image",
                "complete"    => "image", // Also for "pass".
                "perfect"     => "image",
        ];
    }
}

class skin_template_section_achievement extends \format_ludic\section_skin_template {

    private $background  = "";
    private $finalimage  = "";
    private $editorimage = "";
    private $stateimages = [];
    private $statenames  = [
        "incomplete",
        "fail",
        "complete",
        "perfect"
    ];

    public function __construct($config) {
        // Leave the job of extracting common parameters such as title and description to the parent class.
        parent::__construct($config);

        $propnames = [
            "background",
            "finalimage",
        ];
        foreach ($propnames as $propname) {
            $this->$propname = (isset($config->$propname)) ? $config->$propname : "";
        }

        foreach ($this->statenames as $idx => $propname) {
            $this->stateimages[$idx] = (isset($config->$propname)) ? $config->$propname : "";
        }
        $this->editorimage = $this->finalimage ?: $this->background;
    }

    public function get_edit_image() {
        return $this->editorimage;
    }

    public function get_images_to_render($skindata) {
        // If we have a 'final' image defined and we have reached then end then display it.
        if ($skindata->counts[3] == $skindata->globalcount && $skindata->globalcount > 0 && $this->finalimage) {
            return [ $this->finalimage ];
        }

        // We're not all done so send the background and whichever parts are active.
        $result = [ (object)[ "src" => $this->background, "class" => 'img-0' ] ];
        for ($i = 0; $i < 4; ++$i) {
            if ($skindata->counts[$i] == 0) {
                continue;
            }

            $imagesrc = $this->stateimages[$i];
            if ($imagesrc == '') {
                continue;
            }
            $result[] = (object)[ "src" => $imagesrc, "class" => 'img-step img-step-' . ($i + 1) ];
        }
        return $result;
    }

    public function get_css($skindata) {
        return $this->css;
    }

    public function get_texts_to_render($skindata) {
        $texts = [];
        for ($i = 0; $i < 4; ++$i) {
            if ($skindata->counts[$i] == 0) {
                continue;
            }
            $class = $this->statenames[$i];
            $texts[] = [
                'text'  => $skindata->counts[$i],
                'class' => "number completion-count  completion-"
                    . $class
                    . (($i == 3 && $skindata->counts[3] == $skindata->globalcount) ? ' perfect' : ' sup-zero'),
            ];
        }

        return $texts;
    }

    public function setup_skin_data($skindata, $userdata, $section) {
        $counts = [ 0, 0, 0, 0 ];
        $globalcount = 0;
        foreach ($userdata as $cmdata) {
            // Determine which slot to use.
            switch($cmdata->state) {
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
                    $idx = 2;
                    break;
                case COMPLETION_COMPLETE_PERFECT :
                    $idx = 3;
                    break;
                default :
                    continue 2;
            }
            ++$counts[$idx];
            ++$globalcount;
        }
        $skindata->counts = $counts;
        $skindata->globalcount = $globalcount;
    }
}

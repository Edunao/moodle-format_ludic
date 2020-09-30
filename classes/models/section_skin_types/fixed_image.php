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
 * Section skin fixed image classes
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

class skinned_section_fixed_image extends \format_ludic\skinned_section {
    public function __construct(skin_template_section_fixed_image $template){
        parent::__construct($template);
        $this->template = $template;
        $this->skintype = new skin_type_section_fixed_image();
    }
}

class skin_type_section_fixed_image extends \format_ludic\section_skin_type {
    public static function get_name() {
        return 'fixed_image';
    }

    public static function get_editor_config() {
        return [
            "background"  => "image",
        ];
    }
}

class skin_template_section_fixed_image extends \format_ludic\section_skin_template {

    protected $background;

    public function __construct($config) {
        // leave the job of extracting common parameters such as title and description to the parent class
        parent::__construct($config);

        $this->background = isset($config->background) ? $config->background : "";
    }

    public function get_edit_image() {
        return $this->background;
    }

    public function get_images_to_render($skindata) {
        return [ $this->background ];
    }

    public function get_css($skindata) {
        return '';
    }

    public function get_texts_to_render($skindata) {
        return [];
    }

    public function setup_skin_data($skindata, $userdata, $section) {
    }
}


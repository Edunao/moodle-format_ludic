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
 * Activity skin menubar.
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

class skinned_course_module_menubar extends \format_ludic\skinned_course_module {
    public function __construct(skin_template_course_module_menubar $template) {
        parent::__construct($template);
        $this->template = $template;
        $this->skintype = new skin_type_course_module_menubar();
    }
}

class skin_type_course_module_menubar extends \format_ludic\course_module_skin_type {
    public static function get_name() {
        return 'menubar';
    }

    public static function get_editor_config() {
        return [];
    }
}

class skin_template_course_module_menubar extends \format_ludic\course_module_skin_template {

    public function get_edit_image() {
        return "system-skins/menubar";
    }

    public function get_images_to_render($skindata) {
        return ['system-skins/menubar'];
    }

    public function get_css($skindata) {
        return $this->css;
    }

    public function get_texts_to_render($skindata) {
        return [];
    }

    public function setup_skin_data($skindata, $skinresults, $userdata) {
    }
}

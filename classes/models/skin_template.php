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
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

abstract class skin_template {
    // Common properties.
    protected $id;
    protected $title;
    protected $description;
    protected $css;

    // Grant public read access to our properties.
    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    abstract public function get_edit_image();
    abstract public function get_images_to_render($skindata);
    abstract public function get_css($skindata);
    abstract public function get_texts_to_render($skindata);
    public function get_extra_html_to_render($skindata) {
        return [];
    }

    public function get_skin_title() {
        return $this->title;
    }
    public function get_skin_description() {
        return $this->description;
    }
}

abstract class course_module_skin_template extends skin_template {
    public function __construct($config) {
        // Copy out base parameter set.
        foreach (['id', 'title', 'description', 'css'] as $propname) {
            $this->$propname = property_exists($config, $propname) ? $config->$propname : "";
        }
    }

    abstract public function setup_skin_data($skindata, $skinresults, $userdata);
}

abstract class section_skin_template extends skin_template {
    public function __construct($config) {
        // Copy out base paramater set.
        foreach (['id', 'title', 'description', 'css'] as $propname) {
            $this->$propname = property_exists($config, $propname) ? $config->$propname : "";
        }
    }

    abstract public function setup_skin_data($skindata, $userdata, $section);

    public function execute_special_action($skindata, $action) {
        debugging('Action called on bad object ' . get_class($this) . ' : '. json_encode($action));
    }
}
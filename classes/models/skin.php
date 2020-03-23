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
 * Extend this class so that the child inherits the context helper.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

abstract class skin extends model {

    public  $location;
    public  $type;
    public  $title;
    public  $description;
    private $properties;
    public  $css;
    public  $selected;

    /**
     * skin constructor.
     *
     * @param $skin
     */
    public function __construct($skin) {
        parent::__construct($skin);
        $this->location    = isset($skin->location) ? $skin->location : null;
        $this->type        = isset($skin->type) ? $skin->type : null;
        $this->title       = isset($skin->title) ? $skin->title : null;
        $this->description = isset($skin->description) ? $skin->description : null;
        $this->properties  = isset($skin->properties) ? $skin->properties : null;
        $this->css         = isset($skin->properties->css) ? $skin->properties->css : null;
    }

    /**
     * Get a skin by instance.
     *
     * @param $skin
     * @return skin|null
     */
    public static function get_by_instance($skin) {
        $classname = '\format_ludic\\' . $skin->location . '\\' . $skin->type;
        return class_exists($classname) ? new $classname($skin) : null;
    }

    /**
     * Get a skin by id.
     *
     * @param $skinid
     * @return skin|null
     * @throws \coding_exception
     */
    public static function get_by_id($skinid) {
        global $PAGE;

        // Skin id is not in config.
        if ($skinid == FORMAT_LUDIC_CM_SKIN_INLINE_ID) {
            return coursemodule\inline::get_instance();
        }

        // Skin id is in config.
        $contexthelper = context_helper::get_instance($PAGE);
        $skins         = $contexthelper->get_skins_config();

        // Skin not found.
        if (!isset($skins[$skinid]) || empty($skins[$skinid])) {
            return null;
        }

        // Return skin.
        return self::get_by_instance($skins[$skinid]);
    }

    public function get_stylesheet($selectorid) {
        // TODO.
        $output = '<style>';
        $output .= '#' . $selectorid . ' ' . $this->css;
        $output .= '</style>';
        return $output;
    }

    // TODO.
    public function get_properties() {
        return !empty($this->properties) ? get_object_vars($this->properties) : [];
    }

    /**
     * Get edit image.
     *
     * @return \stdClass
     */
    public abstract function get_edit_image();

}
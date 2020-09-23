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
 * Activity skin inline.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic\coursemodule;

defined('MOODLE_INTERNAL') || die();

class inline extends \format_ludic\skin {

    public static function get_editor_config() {
        return [
            "settings"   => [
                "title"        => "text",
                "description" => "textarea",
                "css"         => "textarea",
            ],
            "properties" => [
                "background"  => "image",
            ],
        ];
    }

    public static function get_unique_name() {
        return 'cm-inline';
    }

    /**
     * Return inline image for course edition.
     *
     * @return \stdClass
     */
    public function get_edit_image() {
        $background = $this->get_properties('background');
        return (object) [
            'imgsrc' => $background->imgsrc,
            'imgalt' => isset($background->imgalt) ? $background->imgalt : ''
        ];
    }

    /**
     * Return an instance of this class.
     *
     * @return inline
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    static public function get_instance() {
        return new self((object) [
            'id'          => self::get_unique_name(),
            'location'    => 'coursemodule',
            'type'        => 'inline',
            'title'       => get_string('cm-skin-inline-title', 'format_ludic'),
            'description' => get_string('cm-skin-inline-description', 'format_ludic'),
            'settings'    => self::get_editor_config(),
        ]);
    }

    /**
     * This skin does not require grade.
     *
     * @return false
     */
    public function require_grade() {
        return false;
    }

    public function get_images_to_render() {
        $background = $this->get_properties('background');
        return [
            [
                'imgsrc' => $background->imgsrc,
                'imgalt' => isset($background->imgalt) ? $background->imgalt : ''
            ]
        ];
    }

    public function get_skin_results() {

        $skinresults = parent::get_skin_results();

        $skinresults['score'] = false;
        $skinresults['completion'] = false;

        return $skinresults;
    }
}


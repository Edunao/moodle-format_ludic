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
 * Section skin achievement class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic\section;

use format_ludic\form_element;

defined('MOODLE_INTERNAL') || die();

class achievement extends \format_ludic\skin {

    public static function get_editor_config() {
        return [
            "settings"   => [
                "name"        => "text",
                "main-css"    => "css",
                "description" => "text",
            ],
            "properties" => [
                "background-image" => "image",
                "final-image"      => "image"
            ],
            "steps"      => [
                "index"                    => "int",
                "completion-incomplete"    => "image",
                "completion-complete"      => "image",
                "completion-complete-pass" => "image",
                "completion-complete-fail" => "image",
                "step-text"                => "text",
                "step-css"                 => "css"
            ]
        ];
    }

    public static function get_unique_name() {
        return 'section-achievement';
    }

    public static function get_instance() {
        return (object) [
            'id'          => self::get_unique_name(),
            'location'    => 'section',
            'type'        => 'achievement',
            'title'       => 'Récompenses d\'activités',
            'description' => 'Chaque activité niveau de réussite des activités change l\'état.',
            'settings'    => self::get_editor_config(),
        ];
    }

    /**
     * Get the best image.
     *
     * @return \stdClass
     */
    public function get_edit_image() {
        $image = $this->get_properties('final-image');
        return $image ? $image : $this->get_default_image();
    }

    /**
     * Return default image which is displayed to prevent an error.
     *
     * @return object
     */
    public function get_default_image() {
        global $OUTPUT;
        return (object) [
            'imgsrc' => $OUTPUT->image_url('default', 'format_ludic')->out(),
            'imgalt' => 'Default image.'
        ];
    }

    /**
     * This skin don't use or require grade.
     *
     * @return bool
     */
    public function require_grade() {
        return false;
    }

    /**
     * Hidden : id.
     * Text : name.
     * Checkbox : visible.
     * Selection popup : skin id.
     *
     * @return form_element[]
     * @throws \coding_exception
     */
    public function get_images_to_render() {
        $images = [];

        $completioninfo = $this->get_completion_info();

        // ​​If the activities have all been completed, then the final image is displayed.
        if ($completioninfo['perfect']) {
            $images[] = $this->get_properties('final-image');
            return $images;
        }

        // From now this indicator is useless.
        unset($completioninfo['perfect']);

        // Background (optional)
        $baseimage        = $this->get_properties('background-image');
        $baseimage->class = 'img-0';
        $images[]         = $baseimage;

        // Image for completion state
        $steps = $this->get_properties('steps');
        foreach ($completioninfo as $completionkey => $completion) {
            if ($completion['count'] > 0) {
                foreach ($steps as $stepinfo) {
                    if ($stepinfo->state == $completion['state']) {
                        $image = $stepinfo;

                        if (isset($image->imgsrc) && $image->imgsrc != '') {
                            $image->class           = 'img-step img-step-' . $completionkey;
                            $images[$completionkey] = $image;
                            break;
                        }
                    }
                }
            }
        }

        return array_values($images);
    }

    public function get_texts_to_render() {
        $texts = [];

        $completioninfo = $this->get_completion_info();
        $isperfect      = $completioninfo['perfect'];
        unset($completioninfo['perfect']);
        $steps = $this->get_properties('steps');
        foreach ($completioninfo as $completionkey => $completion) {
            foreach ($steps as $stepinfo) {
                if ($stepinfo->state == $completion['state']) {
                    $classes = ' number completion-count ' . $completionkey;
                    if ($completion['count'] > 0) {
                        $classes .= ' sup-zero ';
                    }
                    if ($isperfect) {
                        $classes .= ' perfect ';
                    }
                    $texts[] = ['text'  => $completion['count'],
                                'class' => $classes
                    ];
                    break;
                }
            }
        }

        return $texts;
    }
}
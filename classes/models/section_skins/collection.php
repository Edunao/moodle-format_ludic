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
 * Section skin collection class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic\section;

defined('MOODLE_INTERNAL') || die();

class collection extends \format_ludic\skin {

    public static function get_editor_config() {
        return [
            "settings"   => [
                "title"        => "text",
                "description" => "textarea",
                "css"         => "textarea",
            ],
            "properties" => [
                "baseimage"  => "image",
                "finalimage" => "image",
                "stampimages" => [
                    "image-off" => "image",
                    "image-on"  => "image"
                ]
            ],
        ];
    }

    public static function get_unique_name() {
        return 'section-collection';
    }

    public static function get_instance() {
        return (object) [
            'id'          => self::get_unique_name(),
            'location'    => 'section',
            'type'        => 'collection',
            'title'       => 'Collection de tampons',
            'description' => get_string('skin-section-collection', 'format_ludic'),
            'settings'    => self::get_editor_config(),
        ];
    }

    /**
     * Get the best image.
     *
     * @return \stdClass
     */
    public function get_edit_image() {
        $image = $this->get_properties('finalimage');
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
     * ​​If the activities have all been completed, then the final image is displayed.
     * If not, then the base image is displayed with a set of stamp images on top of it.
     * Each activities display an image relating its state.
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_images_to_render() {
        $images         = [];
        $completioninfo = $this->get_completion_info();
        $sequence       = $this->get_collection_sequence();

        // From now this indicator is useless.
        unset($completioninfo['perfect']);

        // If not, then the base image is displayed with a set of stamp images on top of it.
        $baseimage        = $this->get_properties('baseimage');
        $baseimage->class = 'img-0';
        $images[]         = $baseimage;

        // Ensure to order stamps by index.
        $stamps      = $this->get_properties('stampimages');
        $stampimages = [];
        foreach ($stamps as $index => $stamp) {
            $stampimages[$index] = $stamp;
        }

        // Randomize stamp order
        srand($this->item->dbrecord->id);
        shuffle($stampimages);

        // Get stamp for each completed activity activity
        $completionkeymap = [
            'completion-incomplete'    => 'off',
            'completion-complete-fail' => 'off',
            'completion-complete'      => 'on',
            'completion-complete-pass' => 'on',
        ];
        foreach ($completioninfo as $completionkey => $completion) {
            foreach ($completion['sequence'] as $cmid) {
                if (!array_key_exists($cmid, $sequence)) {
                    continue;
                }
                $imgstate = 'image-' . $completionkeymap[$completionkey];
                $index    = $sequence[$cmid];

                // Check if we have enough stamp for each activity, if not, get an already used stamp
                if (!array_key_exists($index, $stampimages)) {
                    $index = rand(0, (count($stampimages) - 1));
                }
                $stamp          = $stampimages[$index];
                $image          = $stamp->$imgstate;
                $image->class   = 'img-step img-step-' . $index;
                $images[$index] = $image;
            }

        }
        ksort($images);

        return array_values($images);
    }

    /**
     * Get list of activity with
     *
     * @return array|null
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_collection_sequence() {
        $coursemodules = $this->item->get_course_modules();
        $sequence      = $this->item->sequence;

        $collectionsequence = [];
        $i                  = 1;
        foreach ($sequence as $index => $cmid) {
            if (!array_key_exists($cmid, $coursemodules)) {
                continue;
            }
            $cm        = $coursemodules[$cmid];
            $cmresults = $cm->skin->get_skin_results();

            // If cm has no completion, ignore it
            if ($cmresults['completion'] === false) {
                continue;
            }
            // If cm weight is 0, ignore it
            if ($cmresults['weight'] == 0) {
                continue;
            }

            // If not visible, ignore it
            if ($cm->visible != 1) {
                continue;
            }

            $collectionsequence[$i] = $cm->id;
            $i++;
        }

        ksort($collectionsequence);

        return array_flip($collectionsequence);
    }

    /**
     * Add additional css if required.
     *
     * @return string
     */
    public function get_additional_css() {
        $stampscss = $this->get_properties('stampcss');
        $number    = count($this->get_collection_sequence());
        foreach ($stampscss as $stampcss) {
            if ($stampcss->number == $number) {
                return $stampcss->css;
            }
        }
        return '';
    }

}

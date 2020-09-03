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
                "name"        => "text",
                "main-css"    => "css",
                "description" => "text",
            ],
            "properties" => [
                "base-image"  => "image",
                "final-image" => "image"
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
        return 'section-collection';
    }

    public static function get_instance() {
        return (object) [
            'id'          => self::get_unique_name(),
            'location'    => 'section',
            'type'        => 'collection',
            'title'       => 'Collection de tampons',
            'description' => 'Chaque activité fait gagner des tampons',
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

        // TODO Vérifier comment gérer le "perfect"
        // ​​If the activities have all been completed, then the final image is displayed.
        //if ($completioninfo['perfect']) {
        //    $images[] = $this->get_properties('finalimage');
        //    return $images;
        //}

        // From now this indicator is useless.
        unset($completioninfo['perfect']);

        // If not, then the base image is displayed with a set of stamp images on top of it.
        $baseimage        = $this->get_properties('baseimage');
        $baseimage->class = 'img-0';
        $images[]         = $baseimage;

        // Ensure to order stamps by index.
        $stamps      = $this->get_properties('stampimages');
        $stampimages = [];
        foreach ($stamps as $stamp) {
            $stampimages[$stamp->index] = $stamp;
        }

        // Randomize stamp order
        srand($this->item->dbrecord->id);
        shuffle($stampimages);

        // For each state of completion.
        foreach ($completioninfo as $completionkey => $completion) {
            // For each item in sequence.
            foreach ($completion['sequence'] as $id) {
                // Find index.
                $index = $sequence[$id];

                // Find config related to this index.
                $stamp = $stampimages[$index];

                // Take image with current item state.
                $image = $stamp->$completionkey;

                // Add image with class.
                $image->class   = 'img-step img-step-' . $index;
                $images[$index] = $image;
            }

        }
        ksort($images);

        return array_values($images);
    }

    /**
     * Get cm order excluding cm without completion
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function get_collection_sequence() {
        $sectionsequence = array_flip($this->item->get_collection_sequence());
        $completioninfo  = $this->get_completion_info();
        unset($completioninfo['perfect']);

        $tempseq = [];
        foreach ($completioninfo as $completionkey => $completion) {
            foreach ($completion['sequence'] as $cmid) {
                $tempseq[$sectionsequence[$cmid]] = $cmid;
            }
        }
        ksort($tempseq);

        $sequence = [];
        $i        = 1;
        foreach ($tempseq as $index => $cmid) {
            $sequence[$cmid] = $i;
            $i++;
        }

        return $sequence;
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

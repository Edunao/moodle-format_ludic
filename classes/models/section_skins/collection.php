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

    /**
     * Get the best image.
     *
     * @return \stdClass
     */
    public function get_edit_image() {
        $image = $this->get_properties('finalimage');
        return count($image) > 0 ? $image : $this->get_default_image();
    }

    /**
     * Return default image which is displayed to prevent an error.
     *
     * @return object
     */
    public function get_default_image() {
        global $CFG;
        return (object) [
                'imgsrc' => $CFG->wwwroot . '/course/format/ludic/pix/default.svg',
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

        // TODO : Finir implémentation collection.
        $completioninfo['perfect'] = true; // TODO : supprimer cette ligne quand tout sera ok,
        // c'est juste pour sortir au prochain if et ne pas que la suite bug.


        // ​​If the activities have all been completed, then the final image is displayed.
        if ($completioninfo['perfect']) {
            $images[] = $this->get_properties('finalimage');
            return $images;
        }

        // From now this indicator is useless.
        unset($completioninfo['perfect']);

        // If not, then the base image is displayed with a set of stamp images on top of it.
        $baseimage = $this->get_properties('baseimage');
        $baseimage->class = 'img-0';
        $images[]    = $baseimage;

        // Ensure to order stamps by index.
        $stamps = $this->get_properties('stampimages');
        $stampimages = [];
        foreach ($stamps as $stamp) {
            $stampimages[$stamp->index] = $stamp;
        }

        // Reverse sequence to have itemid => index.
        $sequence    = array_flip($this->get_collection_sequence());
        // Todo : shuffle stamps by section. use         srand(); shuffle();
        // Todo : repeat sequence when count stamp > count sequence

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
                $image->class = 'img-' . $index;
                $images[$index] = $image;
            }

        }

        return $images;
    }

    private function get_collection_sequence() {
        return $this->item->get_collection_sequence();
    }

    /**
     * Add additional css if required.
     *
     * @return string
     */
    public function get_additional_css() {
        $stampscss = $this->get_properties('stampcss');
        $number = count($this->get_collection_sequence());
        foreach ($stampscss as $stampcss) {
            if ($stampcss->number == $number) {
                return $stampcss->css;
            }
        }
        return '';
    }

}

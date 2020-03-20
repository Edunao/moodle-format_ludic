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
 * Section skin score class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic\section;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/ludic/lib.php');

class score extends \format_ludic\skin implements \format_ludic\section_skin_interface {

    /**
     * @return string
     */
    public function render_course_view() {
        return 'course score';
    }

    /**
     * @return string
     */
    public function render_section_view() {
        return 'section score';
    }

    /**
     * @return \stdClass
     */
    public function get_edit_image() {
        $image  = $this->get_default_image();
        $images = $this->get_images();
        return count($images) > 0 ? end($images) : $image;
    }

    /**
     * @return array
     */
    public function get_images() {
        $properties = $this->get_properties();
        return isset($properties['images']) ? $properties['images'] : [];
    }

    /**
     * @return \stdClass
     */
    public function get_default_image() {
        return (object) [
                'imgsrc' => 'https://picsum.photos/id/55/80/80',
                'imgalt' => 'score de section'
        ];
    }

}

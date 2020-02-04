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
 * This file contains main class for the course format Ludic
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/lib.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/data_api.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/context_helper.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/header_bar.php');

/**
 * Main class for the Ludic course format
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ludic extends format_base {

    /**
     * Returns true if this course format uses sections
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Returns the information about the ajax support in the given source format
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport          = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Definitions of the additional options that this course format uses for course
     *
     * ludic format uses the following options:
     * - ludic_config
     * - ludic_sharing_key
     *
     * @param bool $foreditform
     * @return array of options
     * @throws coding_exception
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;

        if ($courseformatoptions === false) {
            $courseformatoptions = [
                    'ludic_config'         => [
                            'type'         => PARAM_RAW, 'label' => get_string('ludicconfiglabel', 'format_ludic'),
                            'element_type' => 'hidden'
                    ], 'ludic_sharing_key' => [
                            'type'         => PARAM_RAW, 'label' => get_string('ludicsharingkeylabel', 'format_ludic'),
                            'element_type' => 'hidden',
                    ],
            ];
        }

        return $courseformatoptions;
    }
}
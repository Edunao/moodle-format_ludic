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
 * Ludic course class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

class course extends model {

    private $sections = null;

    public $moodlecourse;
    public $coursemodules;

    /**
     * course constructor.
     *
     * @param $course
     */
    public function __construct($course) {
        $this->moodlecourse = $course;
        parent::__construct($this->moodlecourse);
    }

    /**
     * Return array of course sections.
     *
     * @return section[]
     * @throws \moodle_exception
     */
    public function get_sections() {
        // Retrieve sections if attribute sections is empty.
        if ($this->sections == null) {
            $this->sections = $this->contexthelper->get_sections_by_course_id($this->id);
        }

        return $this->sections;
    }

}
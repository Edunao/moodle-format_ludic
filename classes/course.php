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

namespace format_ludic;

class course extends model {

    public    $course;
    protected $sections;
    public    $coursemodules;

    public function __construct($course) {
        $this->course = $course;
        parent::__construct($this->course);
    }

    public function get_sections() {
        // Retrieve sections if attribute sections is empty.
        if (empty($this->sections)) {
            $dataapi        = $this->contexthelper->get_data_api();
            $this->sections = $dataapi->get_sections_by_course_id($this->id);
        }

        return $this->sections;
    }

}
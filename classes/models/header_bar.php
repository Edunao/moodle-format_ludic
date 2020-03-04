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
 * TODO Header bar.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/ludic/lib.php');

class header_bar extends model {

    public function render() {
        global $PAGE;
        $buttons = $this->get_buttons();

        $renderer = $PAGE->get_renderer('format_ludic');
        $output   = $renderer->render_from_template('format_ludic/headerbar', $buttons);
        return $output;
    }

    public function get_buttons() {
        // TODO.
        return array(
                'buttons' => array(
                        0    => array(
                                'name' => 'title', 'shortname' => 'title', 'link' => 'value', 'left' => false
                        ), 1 => array(
                                'name' => 'Course', 'shortname' => 'course', 'link' => '/course/view.php?id=2', 'left' => true
                        ), 2 => array(
                                'name' => 'Section', 'shortname' => 'section', 'link' => '/course/view.php?id=2&section=1',
                                'left' => true
                        ),
                ),
        );
    }
}

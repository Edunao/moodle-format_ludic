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
 * Ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$contexthelper = \format_ludic\context_helper::get_instance($PAGE);

$context   = $contexthelper->get_course_context();
$editmode  = $contexthelper->is_editing();
$sectionid = $contexthelper->get_section_id();

$PAGE->set_context($context);

// Display course.
$renderer = $PAGE->get_renderer('format_ludic');
if ($editmode) {
    format_ludic_init_edit_mode($context);
    echo $renderer->render_edit_page();
} else {
    if ($sectionid) {
        // Section view.
        echo $renderer->render_section_page($sectionid);
    } else {
        // Course view.
        echo $renderer->render_page();
    }
}
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

function format_ludic_display_page() {
    global $PAGE;

    $contexthelper = \format_ludic\context_helper::get_instance($PAGE);

    $context   = $contexthelper->get_course_context();
    $editmode  = $contexthelper->is_editing();
    $sectionid = $contexthelper->get_section_id();

    $PAGE->set_context($context);

    $renderer = $PAGE->get_renderer('format_ludic');

    // Course editing view.
    if ($editmode) {
	$contexthelper->set_viewmode('courseedit');
        format_ludic_init_edit_mode($context);
        $contexthelper->prefetch_data_edit_mode();
        echo $renderer->render_edit_page();
        return;
    }

    // Section view.
    if ($sectionid) {
        $contexthelper->set_viewmode('section');
        $contexthelper->prefetch_data_section_page_mode();
        echo $renderer->render_section_page($sectionid);
        return;
    }

    // Determine the number of sections in the course.
    $course   = $contexthelper->get_course();
    $sections = $course->get_sections(false);

    // Single section view (combining section view and course overview).
    if (count($sections) == 1) {
        $contexthelper->set_viewmode('section');
        $sectionid = $contexthelper->get_section_id(1);
        $contexthelper->prefetch_data_course_page_mode();
        $contexthelper->prefetch_data_section_page_mode();
        echo $renderer->render_single_section_page($sectionid);
        return;
    }

    // Course overview.
    $contexthelper->set_viewmode('overview');
    $contexthelper->prefetch_data_course_page_mode();
    echo $renderer->render_overview_page();
}

// Display an appropriate page.
format_ludic_display_page();

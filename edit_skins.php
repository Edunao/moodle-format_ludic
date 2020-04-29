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
 *
 *
 * @package    TODO
 * @subpackage TODO
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     Céline Hernandez <celine@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../../config.php';
require_once $CFG->dirroot . '/course/format/ludic/classes/forms/edit_skins_form.php';


$courseid = required_param('id', PARAM_INT);

$course = get_course($courseid);
require_login($courseid);

$context = context_course::instance($courseid);
$PAGE->set_context($context);

$url = new moodle_url('/course/format/ludic/edit_skins.php', array('id'=>$courseid));
$PAGE->set_url($url);

$PAGE->set_pagelayout('incourse');

$contexthelper = \format_ludic\context_helper::get_instance($PAGE);

if(!$contexthelper->can_edit()){
    print_error('nopermissions', 'error');
}

$title = get_string('edit-skins-title', 'format_ludic');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$renderer = $PAGE->get_renderer('format_ludic');

echo $OUTPUT->header();

echo 'coucou';
$skins = $contexthelper->get_skins_format();


//print_object($skins);
//print_object('coucou');
echo $renderer->render_edit_skins_page();

echo $OUTPUT->footer();
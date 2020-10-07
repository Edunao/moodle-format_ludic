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
 * Config File.
 *
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     Céline Hernandez <celine@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/forms/edit_ludic_config.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/data/skin_manager.php');

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);
require_login($courseid);

$context = context_course::instance($courseid);
require_capability('format/ludic:editludicconfig', $context);

$url = new moodle_url('/course/format/ludic/edit_ludic_config.php', array('id' => $courseid));

$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url($url);

$title = get_string('edit-skins-title', 'format_ludic');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$contexthelper = \format_ludic\context_helper::get_instance($PAGE);
$renderer = $PAGE->get_renderer('format_ludic');

// Fetch the existing configuration.
$skinmanager = \format_ludic\skin_manager::get_instance();
$oldconfig   = $skinmanager->get_user_config();

$error = '';

$form = new format_ludic_edit_ludic_config($oldconfig);

if ($newdata = $form->get_data()) {
    // Store images.
    $fs = get_file_storage();
    if ($newdata->ludicimages) {
        file_save_draft_area_files($newdata->ludicimages, $context->id, 'format_ludic', 'ludicimages',
            0, array('accepted_types' => array('image'), 'subdirs' => 1));
    }

    // Construct the new skin set.
    $newskins   = [];
    $maxid      = 0;
    foreach ($oldconfig as $skin) {
        $id             = $skin->id;
        $maxid          = max($id, $maxid);
        $rawskin        = $newdata->{'ludicconfig' . $id};

        # for special skins we apply an extra set of validation rules
        if ($skin->skinname == 'default') {
            $skindata   = $rawskin ? json_decode($rawskin) : null;
            $ok         = true;
            $ok         = $ok && is_object($skindata);
            $ok         = $ok && ($skindata->domain == $skin->domain);
            if ($ok !== true) {
                $error .= '1. ' . get_string('edit-skin-form-error-config', 'format_ludic') . ': ' . $skin->title . ' (' . $skin->skinname . ')' . '<br>';
                continue;
            }
        }

        if (!$rawskin) {
            // The skin has no definition so we are going to skip it (in other words - delete it).
            continue;
        }

        # verify that the text doesn't contain a '</' sequence that could be used for HTML injection
        if (strpos($rawskin, '</') !== false) {
            $error .= '2. ' . get_string('edit-skin-form-error-config', 'format_ludic') . ': ' . $skin->title . '<br>';
            continue;
        }

        # decode the json blob (and ensure that it is valid
        $newskin        = json_decode($rawskin);
        if (!is_object($newskin)) {
            $error .= '3. ' . get_string('edit-skin-form-error-config', 'format_ludic') . ': ' . $skin->title . '<br>';
            continue;
        }

        $newskin->id       = $id;
        $newskin->skinname = isset($skin->skinname) ? $skin->skinname : "";
        $newskin->fullname = $newskin->domain . '/' . $newskin->skinname;
        $newskins[]        = $newskin;
    }

    // Add the 'new skin' to the container (if there is one).
    // Use a while loop for this if() case to make a breakable construct to facilitate error handling.
    while ($newdata->{'ludicconfig-new'}) {
        $rawdata    = $newdata->{'ludicconfig-new'};
        $newskin    = json_decode($rawdata);
        if (!is_object($newskin)) {
            $error .= get_string('edit-skin-form-error-config', 'format_ludic')
                . ': ' . get_string('edit-skin-new', 'format_ludic');
            break;
        }
        $newskin->id = $maxid + 1;
        $newskins[]  = $newskin;
        break;
    }

    // If there were no erros then we're done.
    if ($error == '') {
        $skinmanager->set_user_config($newskins);
        redirect($url);
    }

} else if ($form->is_cancelled()) {
    redirect($CFG->wwwroot . '/course/view.php?id=' . $course->id);
}

echo $OUTPUT->header();
$data     = new stdClass();
$data->id = $course->id;

// Display errors.
if ($error != '') {
    echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';
}

// Ludic images.
$draftitemid = file_get_submitted_draft_itemid('ludicimages');
file_prepare_draft_area($draftitemid, $context->id, 'format_ludic', 'ludicimages', 0);
$data->ludicimages = $draftitemid;

// Populate the form with data.
foreach ($oldconfig as $skin) {
    $id         = $skin->id;
    $skincopy   = clone($skin);
    unset($skincopy->id);
    unset($skincopy->skinname);
    unset($skincopy->fullname);
    $skintext   = json_encode($skincopy, JSON_PRETTY_PRINT);
    $data->{'ludicconfig' . $id} = $skintext;
}

$form->set_data($data);
$form->display();

echo $OUTPUT->footer();

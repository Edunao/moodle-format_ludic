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
require_once $CFG->dirroot . '/course/format/ludic/classes/forms/edit_ludic_config.php';

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

$error = '';

$form = new format_ludic_edit_ludic_config();

if ($newdata = $form->get_data()) {

    // Store images
    $fs = get_file_storage();
    if ($newdata->ludicimages) {
        file_save_draft_area_files($newdata->ludicimages, $context->id, 'format_ludic', 'ludicimages',
            0, array('accepted_types' => array('image'), 'subdirs' => 1));
    }

    // Prepare ludic config and store if
    $newludicconfig = $newdata->ludicconfig;
    if($newludicconfig == ''){
        $defaultconfig = $contexthelper->get_default_skin_config();
        $defaultconfig = ['skins' => $defaultconfig];
        $contexthelper->update_course_format_options(['ludic_config' => json_encode($defaultconfig)]);
    }else{
        $newludicconfig = json_decode($newludicconfig);

        if($newludicconfig == '' || json_last_error() > 0){
            $error = get_string('edit-skin-form-error-config', 'format_ludic');
        }else{

            $hassectiondefault = false;
            $hascmdefault = false;
            foreach ($newludicconfig as $config){
                if($config->location == 'section' && $config->type == 'noludic'){
                    $hassectiondefault = true;
                    continue;
                }

                if($config->location == 'coursemodule' && $config->type == 'inline'){
                    $hascmdefault = true;
                    continue;
                }
            }

            if(!$hassectiondefault){
                $defaultconfig = $contexthelper->get_default_skin_config();
                foreach ($defaultconfig as $config){
                    if($config->location == 'section' && $config->type == 'noludic'){
                        $newludicconfig[] = $config;
                    }
                }

            }

            if(!$hascmdefault){
                $defaultconfig = $contexthelper->get_default_skin_config();
                foreach ($defaultconfig as $config){
                    if($config->location == 'coursemodule' && $config->type == 'inline'){
                        $newludicconfig[] = $config;
                    }
                }
            }

            $newludicconfig = ['skins' => $newludicconfig];
            $contexthelper->update_course_format_options(['ludic_config' => json_encode($newludicconfig)]);
        }
    }

    if($error == ''){
        redirect($url);
    }

}else if ($form->is_cancelled()) {
    redirect($CFG->wwwroot . '/course/view.php?id=' . $course->id);
}

echo $OUTPUT->header();
$data     = new stdClass();
$data->id = $course->id;

// Display errors
if($error != ''){
    echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';
}

// Ludic images
$draftitemid = file_get_submitted_draft_itemid('ludicimages');
file_prepare_draft_area($draftitemid, $context->id, 'format_ludic', 'ludicimages', 0);
$data->ludicimages = $draftitemid;

// Prepare ludic config
if(isset($newdata)){
    $data->ludicconfig = $newdata->ludicconfig;
}else{
    $ludicconfig = $contexthelper->get_course_format_option_by_name('ludic_config');
    $ludicconfig = json_encode(json_decode($ludicconfig)->skins, JSON_PRETTY_PRINT);
    $data->ludicconfig = $ludicconfig;
}


$form->set_data($data);
$form->display();

echo $OUTPUT->footer();

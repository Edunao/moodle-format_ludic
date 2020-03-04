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

// Course lib.
require_once($CFG->dirroot . '/course/format/lib.php');

// Data.
require_once($CFG->dirroot . '/course/format/ludic/classes/data/context_helper.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/data/data_api.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/data/database_api.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/data/file_api.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/data/log_api.php');

// Models.
require_once($CFG->dirroot . '/course/format/ludic/classes/models/model.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/models/course.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/models/section.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/models/course_module.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/models/header_bar.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/models/skin.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/models/coursemodule_skins/coursemodule_skin_interface.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/models/coursemodule_skins/inline.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/models/coursemodule_skins/score.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/models/section_skins/section_skin_interface.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/models/section_skins/score.php');

// Renderable.
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/popup.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/item.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/section.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/course_module.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/form.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/hidden_form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/text_form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/number_form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/checkbox_form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/textarea_form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/select_form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/filepicker_form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/selection_popup_form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/modchooser.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/renderers/renderable/buttons.php');

// Controller.
require_once($CFG->dirroot . '/course/format/ludic/classes/controllers/front_controller_interface.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/controllers/front_controller.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/controllers/controller_base.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/controllers/section.controller.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/controllers/skin.controller.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/controllers/coursemodule.controller.php');

// Form.
require_once($CFG->dirroot . '/course/format/ludic/classes/forms/form.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/forms/section_form.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/forms/coursemodule_skin_score_form.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/forms/elements/form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/forms/elements/checkbox_form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/forms/elements/filepicker_form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/forms/elements/hidden_form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/forms/elements/number_form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/forms/elements/select_form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/forms/elements/selection_popup_form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/forms/elements/text_form_element.php');
require_once($CFG->dirroot . '/course/format/ludic/classes/forms/elements/textarea_form_element.php');

/**
 * Main class for the Ludic course format
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ludic extends \format_base {

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
     * @throws \coding_exception
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

/**
 * Serve the files from the format_ludic file areas.
 *
 * @param \stdClass $course the course object
 * @param \stdClass $cm the course module object
 * @param \stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 * @throws coding_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function format_ludic_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    // Make sure the user is logged in and has access to the module.
    require_login($course, true);

    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.

    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        // If $args is empty the path is '/'.
        $filepath = '/';
    } else {
        // Else $args contains items of the filepath.
        $filepath = '/' . implode('/', $args) . '/';
    }

    // Retrieve the file from the Files API.
    $fs   = get_file_storage();
    $file = $fs->get_file($context->id, 'course', 'section', $itemid, $filepath, $filename);
    if (!$file) {
        // The file does not exist.
        return false;
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

/**
 * Requires javascript for filepicker and modchooser.
 * @param $context
 */
function format_ludic_init_edit_mode($context) {
    global $PAGE;

    // Filepicker.
    $args                 = new \stdClass();
    $args->context        = $context;
    $args->accepted_types = '*';
    $args->return_types   = 2;
    initialise_filepicker($args);

    $PAGE->requires->js('/lib/form/dndupload.js');
    $PAGE->requires->js('/repository/filepicker.js');
    $PAGE->requires->js('/lib/form/filepicker.js');

    // Modchooser.
    $PAGE->requires->yui_module('moodle-course-modchooser', 'M.course.init_chooser', array(
            array(
                    'courseid'         => $context->instanceid,
                    'closeButtonTitle' => null
            )
    ));
}
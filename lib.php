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

format_ludic_require_files();

/**
 * Main class for the Ludic course format
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ludic extends \format_base {

    protected $contexthelper;

    /**
     * format_ludic constructor.
     *
     * @param $format
     * @param $courseid
     */
    protected function __construct($format, $courseid) {
        global $PAGE;
        parent::__construct($format, $courseid);
        $this->contexthelper = \format_ludic\context_helper::get_instance($PAGE);
    }

    /**
     * Add header bar on each course format page for student view.
     *
     * @return format_ludic_header_bar|null
     * @throws moodle_exception
     */
    public function course_content_header() {
        // Ensure that context helper course id is real course id and not site course id (1)
        $this->contexthelper->set_course_id($this->courseid);
        return new format_ludic_header_bar();
    }

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
                    'ludic_config'      => [
                            'type'         => PARAM_RAW,
                            'label'        => get_string('ludicconfiglabel', 'format_ludic'),
                            'element_type' => 'hidden'
                    ],
                    'ludic_sharing_key' => [
                            'type'         => PARAM_RAW,
                            'label'        => get_string('ludicsharingkeylabel', 'format_ludic'),
                            'element_type' => 'hidden',
                    ],
            ];
        }

        return $courseformatoptions;
    }

    /**
     * Whether this format allows to delete sections
     *
     * If format supports deleting sections it is also recommended to define language string
     * 'deletesection' inside the format.
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Returns course section name.
     * This function is used to display section name in drawer.
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     * @return string section name;
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_section_name($section) {
        if (is_object($section)) {
            $sectionnum = $section->section;
        } else {
            $sectionnum = $section;
        }

        $dbapi = $this->contexthelper->get_database_api();
        $name  = $dbapi->get_section_name_by_courseid_and_sectionidx($this->courseid, $sectionnum);
        return !empty($name) ? $name : get_string('default-section-title', 'format_ludic', $sectionnum);
    }

    /**
     * Loads all of the course sections (except section 0) into the navigation
     *
     * @param global_navigation $navigation
     * @param navigation_node $node
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;

        // If section is specified in course/view.php, make sure it is expanded in navigation.
        if ($navigation->includesectionnum === false) {
            $selectedsection = $this->contexthelper->get_section_id();
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                $navigation->includesectionnum = $selectedsection;
            }
        }

        // Check if there are callbacks to extend course navigation.
        parent::extend_course_navigation($navigation, $node);

        // Remove Section 0 from drawer (this section is not directly accessible in this format).
        $dbapi            = $this->contexthelper->get_database_api();
        $generalsectionid = $dbapi->get_section_id_by_courseid_and_sectionidx($this->courseid, 0);
        $generalsection   = $node->get($generalsectionid, navigation_node::TYPE_SECTION);
        if ($generalsection) {
            $generalsection->remove();
        }

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
 * Return weight setting by default.
 *
 * @return string
 */
function format_ludic_get_default_weight_setting() {
    return '0, 100, 200, 300, 400, 500, 600, 700, 800, 900, 1000';
}

/**
 * Get weight options for select element.
 *
 * @return array
 * @throws \dml_exception
 */
function format_ludic_get_weight_options() {
    global $PAGE;
    $config        = \format_ludic\context_helper::get_instance($PAGE)->get_course_format_config();
    $weightoptions = isset($config->weight) ? $config->weight : format_ludic_get_default_weight_setting();
    $weightoptions = explode(',', $weightoptions);
    return array_map('trim', $weightoptions);

}

/**
 * Get default weight (set by default after adding an activity)
 *
 * @return int
 * @throws \dml_exception
 */
function format_ludic_get_default_weight() {
    $weightoptions = format_ludic_get_weight_options();
    $defaultkey    = round(count($weightoptions) / 2, 0, PHP_ROUND_HALF_DOWN);
    return isset($weightoptions[$defaultkey]) ? $weightoptions[$defaultkey] : 0;
}

/**
 * Get access options for select element.
 *
 * @return array
 * @throws \coding_exception
 */
function format_ludic_get_access_options() {

    // Definitions in start of file.
    $access = [
            FORMAT_LUDIC_ACCESS_ACCESSIBLE          => 'access-accessible',
            FORMAT_LUDIC_ACCESS_CHAINED             => 'access-chained',
            FORMAT_LUDIC_ACCESS_DISCOVERABLE        => 'access-discoverable',
            FORMAT_LUDIC_ACCESS_CONTROLLED          => 'access-controlled',
            FORMAT_LUDIC_ACCESS_GROUPED             => 'access-grouped',
            FORMAT_LUDIC_ACCESS_CHAINED_AND_GROUPED => 'access-chained-and-grouped',
    ];

    // Options for <select>.
    $options = [];
    foreach ($access as $value => $identifier) {
        $options[] = [
                'value'       => $value,
                'name'        => get_string($identifier, 'format_ludic'),
                'description' => get_string($identifier . '-desc', 'format_ludic')
        ];
    }

    return $options;
}

/**
 * Requires javascript for filepicker and modchooser.
 *
 * @param $context
 */
function format_ludic_init_edit_mode($context) {
    global $PAGE;

    // Require filepicker js.
    $args                 = new \stdClass();
    $args->context        = $context;
    $args->accepted_types = '*';
    $args->return_types   = 2;
    initialise_filepicker($args);

    $PAGE->requires->js('/lib/form/dndupload.js');
    $PAGE->requires->js('/repository/filepicker.js');
    $PAGE->requires->js('/lib/form/filepicker.js');

    // Require modchooser js.
    $PAGE->requires->yui_module('moodle-course-modchooser', 'M.course.init_chooser', array(
            array(
                    'courseid'         => $context->instanceid,
                    'closeButtonTitle' => null
            )
    ));
}

/**
 * String identifiers required for js.
 *
 * @param $editmode
 * @return array
 */
function format_ludic_get_strings_for_js($editmode) {
    $strings = [
            'error-popup-title',
            'error-popup-content'
    ];

    if ($editmode) {
        $strings[] = 'confirmation-form-exit-title';
        $strings[] = 'confirmation-form-exit-content';
        $strings[] = 'confirmation-popup-title';
        $strings[] = 'confirmation-popup-content';
    }

    return $strings;
}

function format_ludic_require_files() {
    global $CFG;
    // Course lib.
    require_once($CFG->dirroot . '/course/format/lib.php');

    $classesdir = $CFG->dirroot . '/course/format/ludic/classes';
    // Require parent files first to avoid errors later.
    require_once($classesdir . '/models/model.php');
    require_once($classesdir . '/forms/form.php');
    require_once($classesdir . '/forms/elements/form_element.php');
    require_once($classesdir . '/models/skin.php');
    require_once($classesdir . '/renderers/renderable/form_element.php');
    require_once($classesdir . '/renderers/renderable/item.php');

    /**
     * Recursively scan a folder and requires all files once.
     *
     * @param $dir
     */
    function require_files_recursively($dir) {
        global $CFG;

        // Get directory content, ignore dots.
        $nodes = array_diff(scandir($dir), ['.', '..']);

        // Browse the nodes.
        foreach ($nodes as $node) {

            // Complete path.
            $nodepath = $dir . '/' . $node;

            if (is_dir($nodepath)) {
                // If node is a directory, browse it.
                require_files_recursively($nodepath);
            } else {
                // If node is a file, require it.
                require_once($nodepath);
            }

        }
    }

    // Require files recursively by browsing the class tree.
    require_files_recursively($classesdir);
}

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

require_once($CFG->dirroot . '/course/format/lib.php');
require_once(__DIR__ . '/classes/data/context_helper.php');
require_once(__DIR__ . '/classes/renderers/renderable/header_bar.php');


/**
 * Main class for the Ludic course format
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
        // Ensure that context helper course id is real course id and not site course id (1).
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
                ]
            ];
        }

        return $courseformatoptions;
    }

    public function create_edit_form_elements(&$mform, $forsection = false) {
        $elements = array();

        $options = $this->course_format_options(true);

        foreach ($options as $optionname => $option) {
            if (!isset($option['element_type'])) {
                $option['element_type'] = 'text';
            }
            if (!isset($option['label'])) {
                $option['label'] = null;
            }
            $args = array(
                $option['element_type'],
                $optionname,
                $option['label']
            );
            if (!empty($option['element_attributes'])) {
                $args = array_merge($args, $option['element_attributes']);
            }
            $elements[] = call_user_func_array(array(
                $mform,
                'addElement'
            ), $args);
            if (isset($option['help'])) {
                $helpcomponent = 'format_' . $this->get_format();
                if (isset($option['help_component'])) {
                    $helpcomponent = $option['help_component'];
                }
                $mform->addHelpButton($optionname, $option['help'], $helpcomponent);
            }
            if (isset($option['type'])) {
                $mform->setType($optionname, $option['type']);
            }
            if (is_null($mform->getElementValue($optionname)) && isset($option['default'])) {
                $mform->setDefault($optionname, $option['default']);
            }
        }

        return $elements;
    }

    public function page_set_course(\moodle_page $page) {
        global $CFG, $USER;

        // Put teacher in edition mode by defaut.
        $context = context_course::instance($page->course->id);
        if (!$page->user_is_editing()
            && has_capability('moodle/course:manageactivities', $context)
            && $page->pagetype == 'course-view'
        ) {
            $USER->editing = 1;
            redirect($CFG->wwwroot . '/course/view.php?id=' . $page->course->id . '&sesskey=' . sesskey() . '&edit=on');
        }
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
            if ($selectedsection !== null
                && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0')
                && $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)
            ) {
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

    /**
     * The URL to use for the specified course (with section)
     *
     * Please note that course view page /course/view.php?id=COURSEID is hardcoded in many
     * places in core and contributed modules. If course format wants to change the location
     * of the view script, it is not enough to change just this function. Do not forget
     * to add proper redirection.
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if null the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = array()) {
        $courseid = $this->courseid;

        if (array_key_exists('sr', $options)) {
            $sectionno = $options['sr'];
        } else if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }

        if ($sectionno) {
            $url = new moodle_url('/course/view.php', array(
                'id'      => $courseid,
                'section' => $sectionno
            ));
        } else {
            $url = new moodle_url('/course/view.php', array('id' => $courseid));
        }

        return $url;
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
    $fs = get_file_storage();
    if ($filearea == 'skin' || $filearea == 'ludicimages') {
        $file = $fs->get_file($context->id, 'format_ludic', $filearea, $itemid, $filepath, $filename);
    } else {
        $file = $fs->get_file($context->id, 'course', 'section', $itemid, $filepath, $filename);
    }

    if (!$file) {
        // The file does not exist.
        return false;
    }
    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

/**
 * For text that includes ranges of the form range_begin_value..range_end_value (such as 10..20, 0.01..0.99, etc)
 * interpolate the ranges and return the resulting text with resolved results in place of the range texts
 *
 * @param string $txtwithranges the input text, potentially including ranges in place of some values.
 * @param number $factor the interpolation factor used to select a position between the two extents of each interpolated range
 * @return string result text with constants in place of range texts
 */
function format_ludic_resolve_ranges_in_text($txtwithranges, $factor) {
    // Split the string into parts, separating out any range texts that will need to be replaced.
    $parts = preg_split(
        "/([0123456789]+(?:\.[0123456789]+)?\.\.[0123456789]+(?:\.[0123456789]+)?)/",
        $txtwithranges,
        -1,
        PREG_SPLIT_DELIM_CAPTURE
    );

    // Prime the result with the text part that precedes the first range code.
    $result = $parts[0];

    // Iterate over remaining text parts treating them 2 by 2 as a range sequence followed by a non-range text.
    for ($i = 1; $i < count($parts); $i += 2) {
            $rangetxt       = $parts[$i];
            $nonrangetxt    = $parts[$i + 1];

            // Break up the range text and evaluate the interpolated result.
            $rangeparts     = preg_split("/\.\./", $rangetxt);
            $rangefrom      = $rangeparts[0];
            $rangeto        = $rangeparts[1];
            $fulldelta      = $rangeto - $rangefrom;
            $delta          = $fulldelta * $factor;
            $rangeresult    = $rangefrom + $delta;

            // Aggregate the interpolation result into the result accumulator allong with subsequent arbitrary text part.
            $result .= $rangeresult . $nonrangetxt;
    }

    return $result;
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

function format_ludic_get_skin_image_url($fullimgname) {
    global $OUTPUT, $COURSE;
    // Explode full file name to get path.
    $explodedfilename = explode('/', $fullimgname);

    // Check if file exist in database.
    // TODO fix filepath.
    $filepath = '/';

    if (count($explodedfilename) == 1) {
        $fs = get_file_storage();

        $fileinfo = array(
            'contextid' => \context_course::instance($COURSE->id)->id,
            'component' => 'format_ludic',
            'filearea'  => 'ludicimages',
            'filepath'  => $filepath,
            'itemid'    => 0,
            'filename'  => end($explodedfilename),
        );

        $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
            $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
        if ($file) {
            return \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename(),
                false
            )->out();
        }
    }

    // Use plugin files.
    return $OUTPUT->image_url($fullimgname, 'format_ludic')->out();

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
    global $PAGE, $CFG;

    require_once($CFG->dirroot . '/repository/lib.php');

    // Require filepicker js.
    $args                 = new \stdClass();
    $args->context        = $context;
    $args->accepted_types = '*';
    $args->return_types   = 2;
    initialise_filepicker($args);

    $PAGE->requires->js('/lib/form/dndupload.js');
    $PAGE->requires->js('/repository/filepicker.js');
    $PAGE->requires->js('/lib/form/filepicker.js');

    if ($CFG->version < '2020061502') {
        // Require modchooser js.
        $PAGE->requires->yui_module('moodle-course-modchooser', 'M.course.init_chooser', array(
            array(
                'courseid' => $context->instanceid,
                'closeButtonTitle' => null
            )
        ));
    } else {
        // Build an object of config settings that we can then hook into in the Activity Chooser.
        $chooserconfig = (object)[
            'tabmode' => get_config('core', 'activitychoosertabmode'),
        ];
        $PAGE->requires->js_call_amd('core_course/activitychooser', 'init', [$context->instanceid, $chooserconfig]);
    }
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

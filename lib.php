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
                ],
            ];
        }

        return $courseformatoptions;
    }

    function page_set_course(\moodle_page $page) {
        global $CFG, $USER;

        // Put teacher in edition mode by defaut
        $context = context_course::instance($page->course->id);
        if (!$page->user_is_editing() && has_capability('moodle/course:manageactivities', $context) && $page->pagetype == 'course-view') {
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
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') && $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
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
    require_once($classesdir . '/models/skinnable_interface.php');
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
        $nodes = array_diff(scandir($dir), [
            '.',
            '..'
        ]);

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


function format_ludic_get_default_skins_settings() {
    global $OUTPUT;

    $scoreskinstypes = [

        [
            'id'          => 16,
            'location'    => 'section',
            'type'        => 'achievement',
            'title'       => 'Médaille',
            'description' => 'Des médailles en fonction des réussites des activités !',
            'properties'  => [
                'background-image' => [
                    'imgsrc' => $OUTPUT->image_url('default-skins/section-achievement/section-achievement-bg', 'format_ludic')->out(),
                    'imgalt' => 'Fond de base'
                ],
                'final-image'      => [
                    'imgsrc' => $OUTPUT->image_url('default-skins/section-achievement/section-achievement-final', 'format_ludic')->out(),
                    'imgalt' => '100% de réussite !'
                ],
                'steps'            => [
                    [
                        'state'     => COMPLETION_INCOMPLETE,
                        'statestr'  => 'completion-incomplete',
                        'imgsrc'    => '',
                        'imgalt'    => '',
                        'scorepart' => 0,
                    ],
                    [
                        'state'     => COMPLETION_COMPLETE_FAIL,
                        'statestr'  => 'completion-complete-fail',
                        'imgsrc'    => $OUTPUT->image_url('default-skins/section-achievement/section-achievement-fail', 'format_ludic')->out(),
                        'imgalt'    => 'Médaillé ratée',
                        'scorepart' => 0.25,
                    ],
                    [
                        'state'     => COMPLETION_COMPLETE,
                        'statestr'  => 'completion-complete',
                        'imgsrc'    => $OUTPUT->image_url('default-skins/section-achievement/section-achievement-complete', 'format_ludic')->out(),
                        'imgalt'    => 'Médaille bien',
                        'scorepart' => 0.75,
                    ],
                    [
                        'state'     => COMPLETION_COMPLETE_PASS,
                        'statestr'  => 'completion-complete-pass',
                        'imgsrc'    => $OUTPUT->image_url('default-skins/section-achievement/section-achievement-completepass', 'format_ludic')->out(),
                        'imgalt'    => 'Médaille très bien !',
                        'scorepart' => 1,
                    ],
                ],
                'css'              => '
                    .skin-text.completion-count.sup-zero{    
                                display: block !important;
                                background: #1d1061;
                                color: white !important;
                                border-radius: 100%;
                                position: absolute;
                                font-size: 23px !important;
                                width: 39px;
                                padding: 2px 2px;
                                text-align: center;
                     } 
                    .completion-incomplete{
                        top: 31%;
                        left: 55%;
                    }
                    .completion-complete{
                        bottom: 13%;
                        left: 55%;
                    }
                    .completion-complete-fail{
                        bottom: 14%;
                        left: 25%;
                    }
                    .completion-complete-pass{
                        right: 6%;
                        bottom: 14%;
                    }
                    .skin-text.completion-count.sup-zero.perfect{
                        right: 24%;
                        font-size: 40px !important;
                        width: 65px;
                     }
                 
                '
            ]
        ],
        [
            'id'          => 17,
            'skinid'      => 'section-progress',
            'location'    => 'section',
            'type'        => 'progress',
            'title'       => 'Gravir les marches !',
            'description' => 'Plus on réussit, plus on monte des marches',
            'properties'  => [
                'steps' => [
                    [
                        'threshold' => 0,
                        "images"    => [
                            [
                                'imgsrc' => $OUTPUT->image_url('default-skins/section-progress/section-progress-step1', 'format_ludic')->out(),
                                'imgalt' => 'En bas des marches'
                            ]
                        ],
                        "css"       => ''
                    ],
                    [
                        'threshold' => 1,
                        "images"    => [
                            [
                                'imgsrc' => $OUTPUT->image_url('default-skins/section-progress/section-progress-step2', 'format_ludic')->out(),
                                'imgalt' => 'Ascension en cours'
                            ],
                            [
                                'imgsrc' => $OUTPUT->image_url('default-skins/section-progress/section-progress-step2-character', 'format_ludic')->out(),
                                'imgalt' => 'Moi'
                            ],
                        ],
                        "css"       => '
                        .img-step-1{
                             left: calc((43% / 98) * [percent] + (1819% / 98));
                             top: calc((452% / 7) - (4% / 7) * [percent]);
                             width: 16%;     
                                        
                        }'
                    ],
                    [
                        'threshold' => 100,
                        "images"    => [
                            [
                                'imgsrc' => $OUTPUT->image_url('default-skins/section-progress/section-progress-final', 'format_ludic')->out(),
                                'imgalt' => 'En haut des marches'
                            ]
                        ],
                        "css"       => ''
                    ],

                ],
                'css'   => ' 
.skin-hidden-text.percent{
    display: block;
    position: absolute;
    right: 8%;
    bottom: 1%;
    font-size: 4.5rem;
}
.skin-hidden-text.percent:after {
    content: "%";
    font-weight: normal;
    font-family: \'Montserrat-Medium\';
}          
                '
            ]
        ],
        [
            'id'          => 18,
            'skinid'      => 'cm-progress',
            'location'    => 'coursemodule',
            'type'        => 'progress',
            'title'       => 'Gravir les marches !',
            'description' => 'Réussir au mieux pour arriver au bout de l\'escalier (adapté aux activités à score)',
            'properties'  => [
                'steps' => [
                    [
                        'threshold' => 0,
                        "images"    => [
                            [
                                'imgsrc' => $OUTPUT->image_url('default-skins/cm-progress/cm-progress-step1', 'format_ludic')->out(),
                                'imgalt' => 'En bas des marches'
                            ]
                        ],
                        "css"       => ''
                    ],
                    [
                        'threshold' => 1,
                        "images"    => [
                            [
                                'imgsrc' => $OUTPUT->image_url('default-skins/cm-progress/cm-progress-step2', 'format_ludic')->out(),
                                'imgalt' => 'Ascension en cours'
                            ],
                        ],
                        "css"       => ''
                    ],
                    [
                        'threshold' => 100,
                        "images"    => [
                            [
                                'imgsrc' => $OUTPUT->image_url('default-skins/cm-progress/cm-progress-step3', 'format_ludic')->out(),
                                'imgalt' => 'En haut des marches'
                            ]
                        ],
                        "css"       => ''
                    ],

                ],
                'css'   => ' 
.skin-hidden-text.percent{
    display: block;
    position: absolute;
    right: 8%;
    bottom: 1%;
    font-size: 2.5rem;
}
.skin-hidden-text.percent:after {
     content: "%";
     font-weight: normal;
     font-family: \'Montserrat-Medium\';
}          
                '
            ]
        ],

    ];

    $scoreskinstypes = array_merge(format_ludic_get_default_collection_skins(),
        format_ludic_get_default_avatar_skins(), format_ludic_get_default_section_score_skins(),
        format_ludic_get_default_activity_score_skins(), format_ludic_get_default_not_ludic_skins(), $scoreskinstypes);

    return ['skins' => $scoreskinstypes];
}

function format_ludic_get_default_avatar_skins(){
    global $OUTPUT;

    $avatardata = [
        'id'          => 19,
        'skinid'      => 'section-avatar',
        'location'    => 'section',
        'type'        => 'avatar',
        'title'       => 'Avatar',
        'description' => 'Achète des objets pour améliorer ta chambre',
        'properties'  => [
            'background' => [
                [
                    'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/section-avatar-background', 'format_ludic')->out(),
                    'imgalt' => 'Background'
                ]

            ],
            'slots'      => [
                [
                    'name' => 'Gender',
                ],

                [
                    'name' => 'Color',
                ],
                [
                    'name' => 'Hair',
                ],
                [
                    'name' => 'T-shirt',
                ],
                [
                    'name' => 'Desk',
                ],
                [
                    'name' => 'Ground',
                ],
                [
                    'name' => 'Bedside table',
                ],
                [
                    'name' => 'Bed',
                ],
                [
                    'name' => 'Poster',
                ],
                [
                    'name' => 'Equipment',
                ],
                [
                    'name' => 'Banner',
                ],
                [
                    'name' => 'Duvet',
                ]
            ],
            'items'      => [
                // Desk items
                [
                    'name'      => 'Cube',
                    'cost'      => 10,
                    'slot'      => 'Desk',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-desk-cube', 'format_ludic')->out(),
                        'imgalt' => 'Cube'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-desk-cube', 'format_ludic')->out(),
                            'imgalt' => 'Cube'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Lamp and phone',
                    'cost'      => 20,
                    'slot'      => 'Desk',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-desk-lamp', 'format_ludic')->out(),
                        'imgalt' => 'Lamp and phone'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-desk-lamp', 'format_ludic')->out(),
                            'imgalt' => 'Lamp and phone'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Laptop',
                    'cost'      => 30,
                    'slot'      => 'Desk',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-desk-laptop', 'format_ludic')->out(),
                        'imgalt' => 'Laptop'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-desk-laptop', 'format_ludic')->out(),
                            'imgalt' => 'Laptop'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Computer',
                    'cost'      => 40,
                    'slot'      => 'Desk',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-desk-computer', 'format_ludic')->out(),
                        'imgalt' => 'Laptop'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-desk-computer', 'format_ludic')->out(),
                            'imgalt' => 'Laptop'
                        ],
                    ],
                    'css'       => ''
                ],
                // Ground items
                [
                    'name'      => 'Tennis ball',
                    'cost'      => 10,
                    'slot'      => 'Ground',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-ground-tennis', 'format_ludic')->out(),
                        'imgalt' => 'Tennis ball'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-ground-tennis', 'format_ludic')->out(),
                            'imgalt' => 'Tennis ball'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'American Football',
                    'cost'      => 20,
                    'slot'      => 'Ground',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-ground-americanfootball', 'format_ludic')->out(),
                        'imgalt' => 'American Football'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-ground-americanfootball', 'format_ludic')->out(),
                            'imgalt' => 'American Football'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Volleyball',
                    'cost'      => 30,
                    'slot'      => 'Ground',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-ground-volley', 'format_ludic')->out(),
                        'imgalt' => 'Volleyball'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-ground-volley', 'format_ludic')->out(),
                            'imgalt' => 'Volleyball'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Basketball',
                    'cost'      => 40,
                    'slot'      => 'Ground',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-ground-basket', 'format_ludic')->out(),
                        'imgalt' => 'Basketball'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-ground-basket', 'format_ludic')->out(),
                            'imgalt' => 'Basketball'
                        ],
                    ],
                    'css'       => ''
                ],
                // Beside table items
                [
                    'name'      => 'Oldschool lamp',
                    'cost'      => 20,
                    'slot'      => 'Bedside table',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-bedsidetable-oldschool', 'format_ludic')->out(),
                        'imgalt' => ''
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-bedsidetable-oldschool', 'format_ludic')->out(),
                            'imgalt' => ''
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Retro lamp',
                    'cost'      => 30,
                    'slot'      => 'Bedside table',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-bedsidetable-retro', 'format_ludic')->out(),
                        'imgalt' => 'Retro lamp'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-bedsidetable-retro', 'format_ludic')->out(),
                            'imgalt' => 'Retro lamp'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Design lamp',
                    'cost'      => 40,
                    'slot'      => 'Bedside table',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-bedsidetable-design', 'format_ludic')->out(),
                        'imgalt' => 'Design lamp'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-bedsidetable-design', 'format_ludic')->out(),
                            'imgalt' => 'Design lamp'
                        ],
                    ],
                    'css'       => ''
                ],
                // Gender
                [
                    'name'      => 'Female',
                    'cost'      => 0,
                    'slot'      => 'Gender',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-gender-female', 'format_ludic')->out(),
                        'imgalt' => 'Female'
                    ],
                    'images'    => [

                    ],
                    'css'       => '.skin-img.gender-male.img-object{display:none;}',
                ],
                [
                    'name'      => 'Male',
                    'cost'      => 0,
                    'slot'      => 'Gender',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-gender-male', 'format_ludic')->out(),
                        'imgalt' => 'Male'
                    ],
                    'images'    => [

                    ],
                    'css'       => '.skin-img.gender-female.img-object{display:none;}',
                ],
                // Skin colors
                [
                    'name'      => 'Color 1',
                    'cost'      => 0,
                    'slot'      => 'Color',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-color-f-asian', 'format_ludic')->out(),
                            'imgalt'  => 'Asian Female',
                            'classes' => 'gender-female',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-color-m-asian', 'format_ludic')->out(),
                            'imgalt'  => 'Asian Male',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-color-f-asian', 'format_ludic')->out(),
                            'imgalt'  => 'Asian Female',
                            'classes' => 'gender-female',
                            'zindex'  => '10',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-color-m-asian', 'format_ludic')->out(),
                            'imgalt'  => 'Asian Male',
                            'classes' => 'gender-male',
                            'zindex'  => '10',
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Color 2',
                    'cost'      => 0,
                    'slot'      => 'Color',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-color-f-african', 'format_ludic')->out(),
                            'imgalt'  => 'African Female',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-color-m-african', 'format_ludic')->out(),
                            'imgalt'  => 'African Male',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-color-f-african', 'format_ludic')->out(),
                            'imgalt'  => 'African Female',
                            'classes' => 'gender-female',
                            'zindex'  => '10',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-color-m-african', 'format_ludic')->out(),
                            'imgalt'  => 'African Male',
                            'classes' => 'gender-male',
                            'zindex'  => '10',
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Color 3',
                    'cost'      => 0,
                    'slot'      => 'Color',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-color-f-caucasian', 'format_ludic')->out(),
                            'imgalt'  => 'Caucasian Female',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-color-m-caucasian', 'format_ludic')->out(),
                            'imgalt'  => 'Caucasian Male',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-color-f-caucasian', 'format_ludic')->out(),
                            'imgalt'  => 'Caucasian Female',
                            'classes' => 'gender-female',
                            'zindex'  => '10',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-color-m-caucasian', 'format_ludic')->out(),
                            'imgalt'  => 'Caucasian Male',
                            'classes' => 'gender-male',
                            'zindex'  => '10',
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Color 4',
                    'cost'      => 0,
                    'slot'      => 'Color',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-color-f-oriental', 'format_ludic')->out(),
                            'imgalt'  => 'Oriental Female',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-color-m-oriental', 'format_ludic')->out(),
                            'imgalt'  => 'Oriental Male',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-color-f-oriental', 'format_ludic')->out(),
                            'imgalt'  => 'Oriental Female',
                            'classes' => 'gender-female',
                            'zindex'  => '10',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-color-m-oriental', 'format_ludic')->out(),
                            'imgalt'  => 'Oriental Male',
                            'classes' => 'gender-male',
                            'zindex'  => '10',
                        ],

                    ],
                    'css'       => ''
                ],
                // Hair
                [
                    'name'      => 'Hair 1 - Black',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-short-black', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Black',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-1-black', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Black',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-short-black-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Black',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-short-black-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Black',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Black',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-1-black', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Black',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 1 - Purple',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-short-purple', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Purple',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-1-purple', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Purple',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-short-purple-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Purple',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-short-purple-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Purple',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Purple',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-1-purple', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Purple',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 1 - Green',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-short-green', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Green',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-1-green', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Green',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-short-green-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Green',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-short-green-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Green',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Green',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-1-green', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Green',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 1 - Blue',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-short-blue', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Blue',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-1-blue', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Blue',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-short-blue-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Blue',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-short-blue-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Blue',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Blue',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-1-blue', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Blue',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 1 - Yellow',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-short-yellow', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Yellow',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-1-yellow', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Yellow',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-short-yellow-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Yellow',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-short-yellow-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Yellow',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Yellow',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-yellow-m-1-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Yellow',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 1 - Red',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-short-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Red',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-1-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Red',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-short-red-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Red',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-short-red-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Red',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Red',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-1-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Red',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 1 - Orange',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-short-orange', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Orange',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-1-orange', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Orange',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-short-orange-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Orange',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-short-orange-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Orange',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Orange',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-orange-m-1-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 1 - Orange',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 2 - Black',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-curly-black', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Black',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-3-black', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Black',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-curly-black-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Black',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-curly-black-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Black',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-3', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Black',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-3-black', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Black',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 2 - Purple',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-curly-purple', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Purple',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-3-purple', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Purple',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-curly-purple-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Purple',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-curly-purple-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Purple',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-3', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Purple',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-3-purple', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Purple',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 2 - Blue',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-curly-blue', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Blue',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-3-blue', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Blue',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-curly-blue-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Blue',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-curly-blue-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Blue',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-3', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Blue',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-3-blue', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Blue',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 2 - Green',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-curly-green', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Green',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-3-green', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Green',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-curly-green-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Green',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-curly-green-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Green',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-3', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Green',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-3-green', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Green',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 2 - Yellow',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-curly-yellow', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Yellow',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-3-yellow', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Yellow',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-curly-yellow-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Yellow',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-curly-yellow-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Yellow',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-3', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Yellow',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-3-yellow', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Yellow',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 2 - Red',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-curly-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Red',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-3-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Red',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-curly-red-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Red',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-curly-red-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Red',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-3', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Red',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-3-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Red',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 2 - Orange',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-curly-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Orange',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-3-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Orange',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-curly-red-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Orange',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-curly-red-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Orange',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-3', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Orange',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-3-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 2 - Orange',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 3 - Black',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-bunches-black', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Black',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-2-black', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Black',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-bunches-black-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Black',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-bunches-black-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Black',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Black',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-2-black', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Black',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 3 - Purple',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-bunches-purple', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Purple',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-2-purple', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Purple',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-bunches-purple-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Purple',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-bunches-purple-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Purple',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Purple',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-2-purple', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Purple',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 3 - Blue',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-bunches-blue', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Blue',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-2-blue', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Blue',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-bunches-blue-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Blue',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-bunches-blue-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Blue',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Blue',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-2-blue', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Blue',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 3 - Green',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-bunches-green', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Green',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-2-green', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Green',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-bunches-green-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Green',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-bunches-green-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Green',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Green',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-2-green', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Green',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 3 - Yellow',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-bunches-yellow', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Yellow',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-2-yellow', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Yellow',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-bunches-yellow-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Yellow',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-bunches-yellow-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Yellow',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Yellow',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-2-yellow', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Yellow',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 3 - Red',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-bunches-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Red',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-2-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Red',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-bunches-red-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Red',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-bunches-red-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Red',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Red',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-2-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Red',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 3 - Orange',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-bunches-orange', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Orange',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-2-orange', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Orange',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-bunches-orange-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Orange',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-bunches-orange-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Orange',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Orange',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-2-orange', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 3 - Orange',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 4 - Black',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-long-black', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Black',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-4-black', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Black',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-long-black-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Black',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-long-black-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Black',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-4', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Black',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-4-black', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Black',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 4 - Purple',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-long-purple', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Purple',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-4-purple', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Purple',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-long-purple-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Purple',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-long-purple-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Purple',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-4', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Purple',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-4-purple', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Purple',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 4 - Green',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-long-green', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Green',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-4-green', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Green',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-long-green-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Green',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-long-green-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Green',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-4', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Green',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-4-green', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Green',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 4 - Blue',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-long-blue', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Blue',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-4-blue', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Blue',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-long-blue-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Blue',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-long-blue-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Blue',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-4', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Blue',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-4-blue', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Blue',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 4 - Yellow',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-long-yellow', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Yellow',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-4-yellow', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Yellow',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-long-yellow-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Yellow',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-long-yellow-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Yellow',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-4', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Yellow',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-4-yellow', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Yellow',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 4 - Red',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-long-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Red',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-4-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Red',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-long-red-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Red',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-long-red-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Red',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-4', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Red',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-4-red', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Red',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Hair 4 - Orange',
                    'cost'      => 0,
                    'slot'      => 'Hair',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-f-long-orange', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Orange',
                            'classes' => 'gender-female'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-hair-m-4-orange', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Orange',
                            'classes' => 'gender-male'
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-long-orange-1', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Orange',
                            'classes' => 'gender-female',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-f-long-orange-2', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Orange',
                            'classes' => 'gender-female',
                            'zindex'  => '11'
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-4', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Orange',
                            'classes' => 'gender-male',
                            'zindex'  => '9',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-hair-m-4-orange', 'format_ludic')->out(),
                            'imgalt'  => 'Hair 4 - Orange',
                            'classes' => 'gender-male',
                            'zindex'  => '11'
                        ],

                    ],
                    'css'       => ''
                ],
                // Poster
                [
                    'name'      => 'Game poster',
                    'cost'      => 10,
                    'slot'      => 'Poster',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-poster-game', 'format_ludic')->out(),
                        'imgalt' => 'Game poster'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-poster-game', 'format_ludic')->out(),
                            'imgalt' => 'Game poster'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Sport poster',
                    'cost'      => 20,
                    'slot'      => 'Poster',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-poster-sport', 'format_ludic')->out(),
                        'imgalt' => 'Sport poster'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-poster-sport', 'format_ludic')->out(),
                            'imgalt' => 'Sport poster'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Star poster',
                    'cost'      => 30,
                    'slot'      => 'Poster',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-poster-star', 'format_ludic')->out(),
                        'imgalt' => 'Star poster'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-poster-star', 'format_ludic')->out(),
                            'imgalt' => 'Star poster'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Unicorn poster',
                    'cost'      => 40,
                    'slot'      => 'Poster',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-poster-unicorn', 'format_ludic')->out(),
                        'imgalt' => 'Unicorn poster'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-poster-unicorn', 'format_ludic')->out(),
                            'imgalt' => 'Unicorn poster'
                        ],
                    ],
                    'css'       => ''
                ],
                // Equipment
                [
                    'name'      => 'Roller',
                    'cost'      => 10,
                    'slot'      => 'Equipment',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-equipment-roller', 'format_ludic')->out(),
                        'imgalt' => 'Roller'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-equipment-roller', 'format_ludic')->out(),
                            'imgalt' => 'Roller'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Skate',
                    'cost'      => 20,
                    'slot'      => 'Equipment',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-equipment-skate', 'format_ludic')->out(),
                        'imgalt' => 'Skate'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-equipment-skate', 'format_ludic')->out(),
                            'imgalt' => 'Skate'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Trotinette',
                    'cost'      => 30,
                    'slot'      => 'Equipment',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-equipment-trotinette', 'format_ludic')->out(),
                        'imgalt' => 'Trotinette'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-equipment-trotinette', 'format_ludic')->out(),
                            'imgalt' => 'Trotinette'
                        ],
                    ],
                    'css'       => ''
                ],
                // Bed
                [
                    'name'      => 'Pigeon',
                    'cost'      => 10,
                    'slot'      => 'Bed',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-bed-pigeon', 'format_ludic')->out(),
                        'imgalt' => 'Pigeon'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-bed-pigeon', 'format_ludic')->out(),
                            'imgalt' => 'Pigeon',
                            'zindex'  => '5',
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Teddy',
                    'cost'      => 20,
                    'slot'      => 'Bed',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-bed-teddy', 'format_ludic')->out(),
                        'imgalt' => 'Teddy'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-bed-teddy', 'format_ludic')->out(),
                            'imgalt' => 'Teddy',
                            'zindex'  => '5',
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Pingouin',
                    'cost'      => 30,
                    'slot'      => 'Bed',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-bed-pingouin', 'format_ludic')->out(),
                        'imgalt' => 'Pingouin'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-bed-pingouin', 'format_ludic')->out(),
                            'imgalt' => 'Pingouin',
                            'zindex'  => '5',
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Snake',
                    'cost'      => 40,
                    'slot'      => 'Bed',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-bed-snake', 'format_ludic')->out(),
                        'imgalt' => 'Snake'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-bed-snake', 'format_ludic')->out(),
                            'imgalt' => 'Snake',
                            'zindex'  => '5',
                        ],
                    ],
                    'css'       => ''
                ],
                // Banner
                [
                    'name'      => 'Party banner',
                    'cost'      => 10,
                    'slot'      => 'Banner',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-banner-party', 'format_ludic')->out(),
                        'imgalt' => 'Party banner'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-banner-party', 'format_ludic')->out(),
                            'imgalt' => 'Party banner'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Origami banner',
                    'cost'      => 20,
                    'slot'      => 'Banner',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-banner-origami', 'format_ludic')->out(),
                        'imgalt' => 'Origami banner'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-banner-origami', 'format_ludic')->out(),
                            'imgalt' => 'Origami banner'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Lamp banner',
                    'cost'      => 30,
                    'slot'      => 'Banner',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-banner-lamp', 'format_ludic')->out(),
                        'imgalt' => 'Lamp banner'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-banner-lamp', 'format_ludic')->out(),
                            'imgalt' => 'Lamp banner'
                        ],
                    ],
                    'css'       => ''
                ],
                // Duvet
                [
                    'name'      => 'Sport duvet',
                    'cost'      => 10,
                    'slot'      => 'Duvet',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-duvet-sport', 'format_ludic')->out(),
                        'imgalt' => 'Sport duvet'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-duvet-sport', 'format_ludic')->out(),
                            'imgalt' => 'Sport duvet'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Game duvet',
                    'cost'      => 20,
                    'slot'      => 'Duvet',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-duvet-game', 'format_ludic')->out(),
                        'imgalt' => 'Game duvet'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-duvet-game', 'format_ludic')->out(),
                            'imgalt' => 'Game duvet'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Cloud duvet',
                    'cost'      => 30,
                    'slot'      => 'Duvet',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-duvet-cloud', 'format_ludic')->out(),
                        'imgalt' => 'Cloud duvet'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-duvet-cloud', 'format_ludic')->out(),
                            'imgalt' => 'Cloud duvet'
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Star duvet',
                    'cost'      => 40,
                    'slot'      => 'Duvet',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-duvet-star', 'format_ludic')->out(),
                        'imgalt' => 'Star duvet'
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-duvet-star', 'format_ludic')->out(),
                            'imgalt' => 'Star duvet'
                        ],
                    ],
                    'css'       => ''
                ],
                // T-shirt
                [
                    'name'      => 'Cube T-shirt',
                    'cost'      => 10,
                    'slot'      => 'T-shirt',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-tshirt-cube', 'format_ludic')->out(),
                            'imgalt'  => 'Cube T-shirt',
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-tshirt-f-cube', 'format_ludic')->out(),
                            'imgalt'  => 'Cube T-shirt',
                            'classes' => 'gender-female',
                            'zindex'  => '12',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-tshirt-m-cube', 'format_ludic')->out(),
                            'imgalt'  => 'Cube T-shirt',
                            'classes' => 'gender-male',
                            'zindex'  => '12',
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Snake T-shirt',
                    'cost'      => 20,
                    'slot'      => 'T-shirt',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-tshirt-snake', 'format_ludic')->out(),
                            'imgalt'  => 'Snake T-shirt',
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-tshirt-f-snake', 'format_ludic')->out(),
                            'imgalt'  => 'Snake T-shirt',
                            'classes' => 'gender-female',
                            'zindex'  => '12',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-tshirt-m-snake', 'format_ludic')->out(),
                            'imgalt'  => 'Snake T-shirt',
                            'classes' => 'gender-male',
                            'zindex'  => '12',
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Pigeon T-shirt',
                    'cost'      => 30,
                    'slot'      => 'T-shirt',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-tshirt-pigeon', 'format_ludic')->out(),
                            'imgalt'  => 'Pigeon T-shirt',
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-tshirt-f-pigeon', 'format_ludic')->out(),
                            'imgalt'  => 'Pigeon T-shirt',
                            'classes' => 'gender-female',
                            'zindex'  => '12',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-tshirt-m-pigeon', 'format_ludic')->out(),
                            'imgalt'  => 'Pigeon T-shirt',
                            'classes' => 'gender-male',
                            'zindex'  => '12',
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Unicorn T-shirt',
                    'cost'      => 40,
                    'slot'      => 'T-shirt',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-tshirt-unicorn', 'format_ludic')->out(),
                            'imgalt'  => 'Unicorn T-shirt',
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-tshirt-f-unicorn', 'format_ludic')->out(),
                            'imgalt'  => 'Unicorn T-shirt',
                            'classes' => 'gender-female',
                            'zindex'  => '12',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-tshirt-m-unicorn', 'format_ludic')->out(),
                            'imgalt'  => 'Unicorn T-shirt',
                            'classes' => 'gender-male',
                            'zindex'  => '12',
                        ],
                    ],
                    'css'       => ''
                ],
                [
                    'name'      => 'Star T-shirt',
                    'cost'      => 50,
                    'slot'      => 'T-shirt',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-tshirt-star', 'format_ludic')->out(),
                            'imgalt'  => 'Star T-shirt',
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-tshirt-f-star', 'format_ludic')->out(),
                            'imgalt'  => 'Star T-shirt',
                            'classes' => 'gender-female',
                            'zindex'  => '12',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-tshirt-m-star', 'format_ludic')->out(),
                            'imgalt'  => 'Star T-shirt',
                            'classes' => 'gender-male',
                            'zindex'  => '12',
                        ],
                    ],
                    'css'       => ''
                ],

                [
                    'name'      => 'Game T-shirt',
                    'cost'      => 60,
                    'slot'      => 'T-shirt',
                    'shopimage' => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/shop/section-avatar-tshirt-game', 'format_ludic')->out(),
                            'imgalt'  => 'Game T-shirt',
                        ],
                    ],
                    'images'    => [
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-tshirt-f-game', 'format_ludic')->out(),
                            'imgalt'  => 'Game T-shirt',
                            'classes' => 'gender-female',
                            'zindex'  => '12',
                        ],
                        [
                            'imgsrc'  => $OUTPUT->image_url('default-skins/section-avatar/items/section-avatar-tshirt-m-game', 'format_ludic')->out(),
                            'imgalt'  => 'Game T-shirt',
                            'classes' => 'gender-male',
                            'zindex'  => '12',
                        ],
                    ],
                    'css'       => ''
                ],
                // Empty
                [
                    'name'      => '',
                    'cost'      => 0,
                    'slot'      => '',
                    'shopimage' => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/shop/', 'format_ludic')->out(),
                        'imgalt' => ''
                    ],
                    'images'    => [
                        [
                            'imgsrc' => $OUTPUT->image_url('default-skins/section-avatar/items/', 'format_ludic')->out(),
                            'imgalt' => ''
                        ],
                    ],
                    'css'       => ''
                ],
            ],
            'css'        => '
                    .slot-item-price:before{
                        content: "$"
                    }
                '

        ]

    ];


    return [$avatardata];

}

function format_ludic_get_default_section_score_skins(){
    global $OUTPUT;

    $scoreskins = [
        'id'          => 11,
        'skinid'      => 'section-score',
        'location'    => 'section',
        'type'        => 'score',
        'title'       => 'Médaille',
        'description' => 'Des points avec une médaille à la fin !',
        'properties'  => [
            'steps' => [
                [
                    'threshold' => 0,
                    'imgsrc'    => $OUTPUT->image_url('default-skins/section-score-step1', 'format_ludic')->out(),
                    'imgalt'    => 'Aucune récompense'
                ],
                [
                    'threshold' => 300,
                    'imgsrc'    => $OUTPUT->image_url('default-skins/section-score-step2', 'format_ludic')->out(),
                    'imgalt'    => 'Palier des 300 points !'
                ],
                [
                    'threshold' => 500,
                    'imgsrc'    => $OUTPUT->image_url('default-skins/section-score-step3', 'format_ludic')->out(),
                    'imgalt'    => 'Médaille obtenue ! (500 points)'
                ],
                [
                    'threshold' => 600,
                    'imgsrc'    => $OUTPUT->image_url('default-skins/section-score-step4', 'format_ludic')->out(),
                    'imgalt'    => 'Premier trophée obtenu ! (600 points)'
                ],
                [
                    'threshold' => 700,
                    'imgsrc'    => $OUTPUT->image_url('default-skins/section-score-step5', 'format_ludic')->out(),
                    'imgalt'    => 'Couronne obtenue ! (700 points)'
                ],
                [
                    'threshold' => 800,
                    'imgsrc'    => $OUTPUT->image_url('default-skins/section-score-step6', 'format_ludic')->out(),
                    'imgalt'    => 'Trophée obtenu ! (800 points)'
                ]
            ],
            'css'   => ' 
                .skin-text.score{display: block;position: absolute;bottom: 0px;width: 100%;text-align: center;} 
                
                '
        ]
    ];

    return [$scoreskins];
}

function format_ludic_get_default_collection_skins(){
    global $OUTPUT;

    $animalsstamps = [
        'id'          => 15,
        'skinid'      => 'section-collection',
        'location'    => 'section',
        'type'        => 'collection',
        'title'       => 'Collection d\'animaux',
        'description' => 'La progression fait gagner des tampons animaux.',
        'properties'  => [
            'baseimage'   => [
                'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-bg', 'format_ludic')->out(),
                'imgalt' => 'Fond collection'
            ],
            'finalimage'  => [
                'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-bg', 'format_ludic')->out(),
                'imgalt' => 'Fond collection'
            ],
            'stampimages' => [
                [
                    'image-off'    => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-emptystamp', 'format_ludic')->out(),
                        'imgalt' => 'Empty'
                    ],
                    'image-on'      => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-whale', 'format_ludic')->out(),
                        'imgalt' => 'Whale'
                    ]
                ],
                [
                    'image-off'    => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-emptystamp', 'format_ludic')->out(),
                        'imgalt' => 'Empty'
                    ],
                    'image-on'      => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-frog', 'format_ludic')->out(),
                        'imgalt' => 'Frog'
                    ]
                ],
                [
                    'image-off'    => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-emptystamp', 'format_ludic')->out(),
                        'imgalt' => 'Empty'
                    ],
                    'image-on'      => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-walrus', 'format_ludic')->out(),
                        'imgalt' => 'Walrus'
                    ]
                ],
                [
                    'image-off'    => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-emptystamp', 'format_ludic')->out(),
                        'imgalt' => 'Empty'
                    ],
                    'image-on'      => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collecton-owl', 'format_ludic')->out(),
                        'imgalt' => 'Owl'
                    ]
                ],
                [
                    'image-off'    => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-emptystamp', 'format_ludic')->out(),
                        'imgalt' => 'Empty'
                    ],
                    'image-on'      => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-pigeon', 'format_ludic')->out(),
                        'imgalt' => 'Pigeon'
                    ]
                ],
                [
                    'image-off'    => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-emptystamp', 'format_ludic')->out(),
                        'imgalt' => 'Empty'
                    ],
                    'image-on'      => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-snake', 'format_ludic')->out(),
                        'imgalt' => 'Snake'
                    ]
                ],
                [
                    'image-off'    => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-emptystamp', 'format_ludic')->out(),
                        'imgalt' => 'Empty'
                    ],
                    'image-on'      => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-cow', 'format_ludic')->out(),
                        'imgalt' => 'Cow'
                    ]
                ],
                [
                    'image-off'    => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-emptystamp', 'format_ludic')->out(),
                        'imgalt' => 'Empty'
                    ],
                    'image-on'      => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-bear', 'format_ludic')->out(),
                        'imgalt' => 'Bear'
                    ]
                ],
                [
                    'image-off'    => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-emptystamp', 'format_ludic')->out(),
                        'imgalt' => 'Empty'
                    ],
                    'image-on'      => [
                        'imgsrc' => $OUTPUT->image_url('default-skins/collection/section-collection-mouse', 'format_ludic')->out(),
                        'imgalt' => 'Mouse'
                    ],
                ],
            ],
            'stampcss'    => [
                [
                    'number' => 1,
                    'css'    => ''
                ],
                [
                    'number' => 2,
                    'css'    => ''
                ],
                [
                    'number' => 3,
                    'css'    => ''
                ],
                [
                    'number' => 4,
                    'css'    => ''
                ],
                [
                    'number' => 5,
                    'css'    => ''
                ],
                [
                    'number' => 6,
                    'css'    => ''
                ],
                [
                    'number' => 7,
                    'css'    => ''
                ],
                [
                    'number' => 8,
                    'css'    => ''
                ],
                [
                    'number' => 9,
                    'css'    => ''
                ],
            ],
            'css'         => '
                    .img-step{top:2%; width:33% !important;}
                    .img-step-2{left:33%;}
                    .img-step-3{left:64%;}
                    .img-step-4{top:32%;}
                    .img-step-5{top:32%;left:33%;}
                    .img-step-6{top:32%;left:64%;}
                    .img-step-7{top:62%;}
                    .img-step-8{top:62%;left:33%;}
                    .img-step-9{top:62%;left:64%;}'
        ]
    ];


    return [$animalsstamps];
}

function format_ludic_get_default_activity_score_skins(){
    global $OUTPUT;

    $scoreskins = [
        'id'          => 12,
        'skinid'      => 'cm-score',
        'location'    => 'coursemodule',
        'type'        => 'score',
        'title'       => 'Trophée',
        'description' => 'Un trophée en fonction de votre réussite.',
        'properties'  => [
            'steps'           => [
                [
                    'threshold' => 0,
                    'scorepart' => 0,
                    'extratext' => '',
                    'extracss'  => '',
                    'imgsrc'    => $OUTPUT->image_url('default-skins/cm-score-step1', 'format_ludic')->out(),
                    'imgalt'    => 'Pas de trophée'
                ],
                [
                    'threshold' => 50,
                    'scorepart' => 10,
                    'extratext' => '',
                    'extracss'  => '',
                    'imgsrc'    => $OUTPUT->image_url('default-skins/cm-score-step2', 'format_ludic')->out(),
                    'imgalt'    => 'Trophée d\'argent'
                ],
                [
                    'threshold' => 100,
                    'scorepart' => 20,
                    'extratext' => '',
                    'extracss'  => '.skin-text.threshold{display:none;}
                                        .skin-text.score{bottom: 39%}',
                    'imgsrc'    => $OUTPUT->image_url('default-skins/cm-score-step3', 'format_ludic')->out(),
                    'imgalt'    => 'Trophée d\'or'
                ]
            ],
            'linearscorepart' => 2,
            'css'             => '.sub-tile.skin-tile .skin-text {font-size:30px;} 
                            .skin-text.score{display: block;position: absolute;left: 59%;bottom: 49%;font-size:1.5rem;font-weight:bold;} 
                            .skin-text.score::after{content: "pts";font-size:1rem;font-weight:normal;}
                            .skin-text.threshold{display: inline-block;position: absolute;left: 43%;bottom: 17%;font-size:20px !important;font-weight:bold;}
                            .skin-text.threshold::before{content: "SEUIL : ";font-size:20px;font-weight:normal;}'
        ]
    ];

    return [$scoreskins];

}

function format_ludic_get_default_not_ludic_skins(){
    global $OUTPUT;

    $sectionnoludic = [
        'id'          => 10,
        'skinid'      => 'section-noludic',
        'location'    => 'section',
        'type'        => 'noludic',
        'title'       => 'Pas de motivateur',
        'description' => 'Rien d\'autres qu\'une jolie image.',
        'properties'  => [
            'background' => [
                'imgsrc' => $OUTPUT->image_url('default-skins/section-notludic', 'format_ludic')->out(),
                'imgalt' => 'Not ludic',
            ]
        ]
    ];

    $cmnoludic = [
        'id'          => 1,
        'skinid'      => 'cm-inline',
        'location'    => 'coursemodule',
        'type'        => 'inline',
        'title'       => 'Pas de motivateur',
        'description' => 'Une image de cahier ou l\'activité inline.',
        'properties'  => [
            'background' => [
                'imgsrc' => $OUTPUT->image_url('default-skins/cm-notludic', 'format_ludic')->out(),
                'imgalt' => 'Not ludic',
            ],
            'css' => ''
        ]
    ];

    $cmnoludic2 = [
        'id'          =>  23,
        'skinid'      => 'cm-inline',
        'location'    => 'coursemodule',
        'type'        => 'inline',
        'title'       => 'Pas de motivateur mais il est rouge !',
        'description' => 'Une image de cahier ROUGE ou l\'activité inline.',
        'properties'  => [
            'background' => [
                'imgsrc' => $OUTPUT->image_url('default-cm-rouge', 'format_ludic')->out(),
                'imgalt' => 'Not ludic',
            ],
            'css' => ''
        ]
    ];

    return [$sectionnoludic, $cmnoludic, $cmnoludic2];
}
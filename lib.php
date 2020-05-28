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
                        'threshold' => 20,
                        'imgsrc'    => $OUTPUT->image_url('default-skins/section-score-step2', 'format_ludic')->out(),
                        'imgalt'    => 'Moitié des points obtenus !'
                    ],
                    [
                        'threshold' => 30,
                        'imgsrc'    => $OUTPUT->image_url('default-skins/section-score-step3', 'format_ludic')->out(),
                        'imgalt'    => 'Médaille obtenue !'
                    ],
                    [
                        'threshold' => 50,
                        'imgsrc'    => $OUTPUT->image_url('default-skins/section-score-step4', 'format_ludic')->out(),
                        'imgalt'    => 'Premier trophée obtenu !'
                    ],
                    [
                        'threshold' => 80,
                        'imgsrc'    => $OUTPUT->image_url('default-skins/section-score-step5', 'format_ludic')->out(),
                        'imgalt'    => 'Couronne obtenue !'
                    ],
                    [
                        'threshold' => 100,
                        'imgsrc'    => $OUTPUT->image_url('default-skins/section-score-step6', 'format_ludic')->out(),
                        'imgalt'    => 'Trophée obtenu !'
                    ]
                ],
                'css'   => ' 
                .skin-text.score{display: block;position: absolute;bottom: 0px;width: 100%;text-align: center;} 
                
                '
            ]
        ],
        [
            'id'          => 12,
            'skinid'      => 'cm-score',
            'location'    => 'coursemodule',
            'type'        => 'score',
            'title'       => 'Trophée',
            'description' => 'Un trophée en fonction de votre réussite.',
            'properties'  => [
                'steps' => [
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
                        'scorepart' => 1,
                        'extratext' => '',
                        'extracss'  => '',
                        'imgsrc'    => $OUTPUT->image_url('default-skins/cm-score-step2', 'format_ludic')->out(),
                        'imgalt'    => 'Trophée d\'argent'
                    ],
                    [
                        'threshold' => 100,
                        'scorepart' => 2,
                        'extratext' => '',
                        'extracss'  => '',
                        'imgsrc'    => $OUTPUT->image_url('default-skins/cm-score-step3', 'format_ludic')->out(),
                        'imgalt'    => 'Trophée d\'or'
                    ]
                ],
                'linearscorepart' => 1,
                'css'   => '.sub-tile.skin-tile .skin-text {font-size:30px;} 
                            .skin-text.score{display: block;position: absolute;left: 59%;bottom: 49%;font-size:1.5rem;font-weight:bold;} 
                            .skin-text.score::after{content: "pts";font-size:1rem;font-weight:normal;}'

            ]
        ],
        [
            'id'          => 13,
            'skinid'      => 'cm-achievement',
            'location'    => 'coursemodule',
            'type'        => 'achievement',
            'title'       => 'Achivement',
            'description' => 'Plus l\'activité est réussie, plus le personnage monte.',
            'properties'  => [
                'steps' => [
                    [
                        'state'     => COMPLETION_INCOMPLETE,
                        //0
                        'statestr'  => 'completion-incomplete',
                        'imgsrc'    => $OUTPUT->image_url('default-skins/cm-achievement-step1', 'format_ludic')->out(),
                        'imgalt'    => '',
                        'scorepart' => 0,
                        'extratext' => 'En bas'
                    ],
                    [
                        'state'     => COMPLETION_COMPLETE,
                        //1
                        'statestr'  => 'completion-complete',
                        'imgsrc'    => $OUTPUT->image_url('default-skins/cm-achievement-step3', 'format_ludic')->out(),
                        'imgalt'    => '',
                        'scorepart' => 0.75,
                        'extratext' => 'En haut !'
                    ],
                    [
                        'state'     => COMPLETION_COMPLETE_PASS,
                        //2
                        'statestr'  => 'completion-complete-pass',
                        'imgsrc'    => $OUTPUT->image_url('default-skins/cm-achievement-step3', 'format_ludic')->out(),
                        'imgalt'    => '',
                        'scorepart' => 1,
                        'extratext' => 'En haut mais en mieux'
                    ],
                    [
                        'state'     => COMPLETION_COMPLETE_FAIL,
                        //3
                        'statestr'  => 'completion-complete-fail',
                        'imgsrc'    => $OUTPUT->image_url('default-skins/cm-achievement-step1', 'format_ludic')->out(),
                        'imgalt'    => '',
                        'scorepart' => 0.25,
                        'extratext' => 'En bas mais en moins bien'
                    ]
                ],
                'css'   => ''
            ],
        ],
        [
            'id'          => 14,
            'skinid'      => 'cm-progression',
            'location'    => 'coursemodule',
            'type'        => 'progression',
            'title'       => 'Progression ',
            'description' => 'Plus l\'activité est réussie, plus le personnage monte.',
            'properties'  => [
                'steps' => [
                    [
                        'threshold'  => 0,
                        'proportion' => 0,
                        'score'      => 0,
                        'scoremax'   => 0,
                        'scorepart'  => 0,
                        'extratext'  => '',
                        'extracss'   => '',
                        'imgsrc'     => $OUTPUT->image_url('default-skins/cm-achievement-step1', 'format_ludic')->out(),
                        'imgalt'     => 'En bas'
                    ],
                    [
                        'threshold'  => 1,
                        'proportion' => 1,
                        'score'      => 1,
                        'scoremax'   => 99,
                        'scorepart'  => 1,
                        'extratext'  => '',
                        'extracss'   => '',
                        'imgsrc'     => $OUTPUT->image_url('default-skins/cm-achievement-step2', 'format_ludic')->out(),
                        'imgalt'     => 'On monte !'
                    ],
                    [
                        'threshold'  => 2,
                        'proportion' => 100,
                        'score'      => 100,
                        'scoremax'   => 100,
                        'scorepart'  => 100,
                        'extratext'  => '',
                        'extracss'   => '',
                        'imgsrc'     => $OUTPUT->image_url('default-skins/cm-achievement-step3', 'format_ludic')->out(),
                        'imgalt'     => 'Ascension réussie !'
                    ],
                ],
                'css'   => ''
            ],
        ]
    ];

    return ['skins' => $scoreskinstypes];
}

function format_ludic_get_default_skins_settings_old() {
    global $CFG;
    $defaultimage = $CFG->wwwroot . '/course/format/ludic/pix/default.svg';

    return [
        'skins' => [
            11 => [
                'id'          => 11,
                'location'    => 'section',
                'type'        => 'score',
                'title'       => 'Coffre de pièces',
                'description' => 'Ce coffre stock des pièces',
                'properties'  => [
                    'steps' => [
                        [
                            'threshold' => 0,
                            'imgsrc'    => 'https://cdn1.iconfinder.com/data/icons/security-add-on-colored/48/JD-09-512.png',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 10,
                            'imgsrc'    => 'https://picsum.photos/id/101/80/80',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 20,
                            'imgsrc'    => 'https://picsum.photos/id/102/80/80',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 30,
                            'imgsrc'    => 'https://picsum.photos/id/103/80/80',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 40,
                            'imgsrc'    => 'https://picsum.photos/id/104/80/80',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 50,
                            'imgsrc'    => 'https://picsum.photos/id/109/80/80',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 60,
                            'imgsrc'    => 'https://picsum.photos/id/106/80/80',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 80,
                            'imgsrc'    => 'https://picsum.photos/id/107/80/80',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 100,
                            'imgsrc'    => 'https://www.clipartmax.com/png/middle/275-2750625_chest-icon-treasure-chest-icon-png.png',
                            'imgalt'    => ''
                        ]
                    ],
                    'css'   => '
                                {background-color: aliceblue;} .sub-tile.skin-tile {background-color: beige;}
                                .sub-tile.skin-tile .skin-text {font-size:30px;}'
                ]
            ],
            12 => [
                'id'          => 12,
                'location'    => 'section',
                'type'        => 'score',
                'title'       => 'Coffre au trésor',
                'description' => 'Ce coffre stock des trésors. 
                        Commence avec un coffre vide, gagne un numéro tous les 10%.
                        Termine avec un grand coffre !',
                'properties'  => [
                    'steps' => [
                        [
                            'threshold' => 0,
                            'imgsrc'    => 'https://i.pinimg.com/originals/6a/1d/f3/6a1df304403e15c9a4b499e8539853ec.jpg',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 10,
                            'imgsrc'    => 'http://www.pngall.com/wp-content/uploads/2/1-Number-PNG-Pic.png',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 20,
                            'imgsrc'    => 'http://www.pngall.com/wp-content/uploads/2/2-Number-PNG-Pic.png',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 30,
                            'imgsrc'    => 'http://www.pngall.com/wp-content/uploads/2/3-Number-PNG-Pic.png',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 50,
                            'imgsrc'    => 'http://www.pngall.com/wp-content/uploads/2/4-Number-PNG-Pic.png',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 60,
                            'imgsrc'    => 'http://www.pngall.com/wp-content/uploads/2/5-Number-PNG-Pic.png',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 70,
                            'imgsrc'    => 'http://www.pngall.com/wp-content/uploads/2/6-Number-PNG-Pic.png',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 80,
                            'imgsrc'    => 'http://www.pngall.com/wp-content/uploads/2/7-Number-PNG-Pic.png',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 90,
                            'imgsrc'    => 'http://www.pngall.com/wp-content/uploads/2/8-Number-PNG-Pic.png',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 100,
                            'imgsrc'    => 'https://visualpharm.com/assets/324/Treasure%20Chest-595b40b85ba036ed117dacb5.svg',
                            'imgalt'    => ''
                        ]
                    ],
                    'css'   => '{background-color: yellow;}'
                ],
            ],
            14 => [
                'id'          => 14,
                'location'    => 'coursemodule',
                'type'        => 'score',
                'title'       => 'Pokémon feu',
                'description' => 'Petit Salamèche deviendra grand.',
                'properties'  => [
                    'steps'           => [
                        [
                            'threshold' => 0,
                            'scorepart' => 0,
                            'extratext' => 'tu as entre 0 et 9.99',
                            'extracss'  => '{background-color: grey;}',
                            'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/4.png',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 50,
                            'scorepart' => 1,
                            'extratext' => 'tu as entre 10 et 19.99',
                            'extracss'  => '{background-color: blue;}',
                            'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/5.png',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 100,
                            'scorepart' => 1,
                            'extratext' => 'tu as 20, bravo',
                            'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/6.png',
                            'imgalt'    => ''
                        ]
                    ],
                    'linearscorepart' => 1,
                    'css'             => '{background-color: red;} .skin-img {    background-size: 20%;} .skin-text {color: black;}.title-tile {border-top: 1px solid blue;}'
                ]
            ],
            13 => [
                'id'          => 13,
                'location'    => 'coursemodule',
                'type'        => 'score',
                'title'       => 'Pokémon plante',
                'description' => 'Petit Bulbizarre deviendra grand.',
                'properties'  => [
                    'steps'           => [
                        [
                            'threshold' => 0,
                            'scorepart' => 0,
                            'extratext' => 'tu as entre 0 et 9.99',
                            'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/1.png',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 70,
                            'scorepart' => 1,
                            'extratext' => 'tu as entre 10 et 19.99',
                            'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/2.png',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 100,
                            'scorepart' => 1,
                            'extratext' => 'tu as 20, bravo',
                            'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/3.png',
                            'imgalt'    => ''
                        ]
                    ],
                    'linearscorepart' => 2,
                    'css'             => '{background-color: green;}
                                  .skin-img {    background-size: 110%;}
                                 .skin-text {color: yellow;} .title-tile {border-top: 4px solid red;}'
                ],
            ],
            16 => [
                'id'          => 16,
                'location'    => 'coursemodule',
                'type'        => 'score',
                'title'       => 'Pokémon eau',
                'description' => 'Petit Carapuce deviendra grand.',
                'properties'  => [
                    'steps'           => [
                        [
                            'threshold' => 0,
                            'scorepart' => 0,
                            'extratext' => 'tu as entre 0 et 9.99',
                            'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/7.png',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 10,
                            'scorepart' => 1,
                            'extratext' => 'tu as entre 10 et 19.99',
                            'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/8.png',
                            'imgalt'    => ''
                        ],
                        [
                            'threshold' => 20,
                            'scorepart' => 1,
                            'extratext' => 'tu as 20, bravo',
                            'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/9.png',
                            'imgalt'    => ''
                        ]
                    ],
                    'linearscorepart' => 4,
                    'css'             => '{background-color: blue;} .skin-text {color: white;} .title-tile {border-top: 2px solid yellow;}'
                ]
            ],
            15 => [
                'id'          => 15,
                'location'    => 'coursemodule',
                'type'        => 'achievement',
                'title'       => 'Évolution max',
                'description' => 'Plus tu réussis, plus tu évolues',
                'properties'  => [
                    'steps' => [
                        [
                            'state'     => COMPLETION_INCOMPLETE,
                            //0
                            'statestr'  => 'completion-incomplete',
                            'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/63.png',
                            'imgalt'    => '',
                            'scorepart' => 0,
                            'extratext' => 'Abra'
                        ],
                        [
                            'state'     => COMPLETION_COMPLETE,
                            //1
                            'statestr'  => 'completion-complete',
                            'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/65.png',
                            'imgalt'    => '',
                            'scorepart' => 0.75,
                            'extratext' => 'Alakazam'
                        ],
                        [
                            'state'     => COMPLETION_COMPLETE_PASS,
                            //2
                            'statestr'  => 'completion-complete-pass',
                            'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/10037.png',
                            'imgalt'    => '',
                            'scorepart' => 1,
                            'extratext' => 'Méga-Alakazam'
                        ],
                        [
                            'state'     => COMPLETION_COMPLETE_FAIL,
                            //3
                            'statestr'  => 'completion-complete-fail',
                            'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/64.png',
                            'imgalt'    => '',
                            'scorepart' => 0.25,
                            'extratext' => 'Kadabra'
                        ]
                    ],
                    'css'   => '{background-color: purple;} .skin-text {color: white;}'
                ]
            ],
            17 => [
                'id'          => 17,
                'location'    => 'section',
                'type'        => 'collection',
                'title'       => 'Chaque pokémon évolue',
                'description' => 'Collectionne et fais évoluer les pokémons.',
                'properties'  => [
                    'baseimage'   => [
                        'imgsrc' => 'https://i.ytimg.com/vi/XSPntFQODQQ/maxresdefault.jpg',
                        'imgalt' => ''
                    ],
                    'finalimage'  => [
                        'imgsrc' => 'https://images-na.ssl-images-amazon.com/images/I/71xp01I1uML.jpg',
                        'imgalt' => ''
                    ],
                    'stampimages' => [
                        [
                            'index'                    => 1,
                            'completion-incomplete'    => [
                                'imgsrc' => $defaultimage,
                                'imgalt' => ''
                            ],
                            'completion-complete'      => [
                                'imgsrc' => 'https://www.pokebip.com/pokedex-images/artworks/2.png',
                                'imgalt' => ''
                            ],
                            'completion-complete-pass' => [
                                'imgsrc' => 'https://www.pokebip.com/pokedex-images/artworks/3.png',
                                'imgalt' => ''
                            ],
                            'completion-complete-fail' => [
                                'imgsrc' => 'https://www.pokebip.com/pokedex-images/artworks/1.png',
                                'imgalt' => ''
                            ],
                        ],
                        [
                            'index'                    => 2,
                            'completion-incomplete'    => [
                                'imgsrc' => $defaultimage,
                                'imgalt' => ''
                            ],
                            'completion-complete'      => [
                                'imgsrc' => 'https://www.pokebip.com/pokedex-images/artworks/5.png',
                                'imgalt' => ''
                            ],
                            'completion-complete-pass' => [
                                'imgsrc' => 'https://www.pokebip.com/pokedex-images/artworks/6.png',
                                'imgalt' => ''
                            ],
                            'completion-complete-fail' => [
                                'imgsrc' => 'https://www.pokebip.com/pokedex-images/artworks/4.png',
                                'imgalt' => ''
                            ],
                        ],
                        [
                            'index'                    => 3,
                            'completion-incomplete'    => [
                                'imgsrc' => $defaultimage,
                                'imgalt' => ''
                            ],
                            'completion-complete'      => [
                                'imgsrc' => 'https://www.pokebip.com/pokedex-images/artworks/8.png',
                                'imgalt' => ''
                            ],
                            'completion-complete-pass' => [
                                'imgsrc' => 'https://www.pokebip.com/pokedex-images/artworks/9.png',
                                'imgalt' => ''
                            ],
                            'completion-complete-fail' => [
                                'imgsrc' => 'https://www.pokebip.com/pokedex-images/artworks/7.png',
                                'imgalt' => ''
                            ],
                        ],
                    ],
                    'stampcss'    => [
                        [
                            'number' => 1,
                            'css'    => '{background-color: green;}'
                        ],
                        [
                            'number' => 2,
                            'css'    => '{background-color: blue;}'
                        ],
                        [
                            'number' => 3,
                            'css'    => '{background-color: red;}'
                        ],
                        [
                            'number' => 4,
                            'css'    => '{background-color: black;}'
                        ],
                        [
                            'number' => 5,
                            'css'    => '{background-color: beige;}'
                        ],
                        [
                            'number' => 6,
                            'css'    => '{background-color: aliceblue;}'
                        ],
                    ],
                    'css'         => '{background-color: purple;} .skin-text {color: white;} 
                                .skin-img.img-0 {filter: grayscale(1);}
                                .skin-img.img-1 {width:33%;left:0;}.skin-img.img-2 {width:33%;right:0;}'
                ]
            ]
        ]
    ];
}
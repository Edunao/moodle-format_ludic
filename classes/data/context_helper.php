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
 * This class makes it possible to recover all the data necessary for the course.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

use core\session\file;
use format_ludic\section\achievement;
use format_ludic\section\noludic;
use format_ludic\section\score;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/data_api.php');
require_once(__DIR__ . '/file_api.php');
require_once(__DIR__ . '/database_api.php');
require_once(__DIR__ . '/../models/course.php');
require_once(__DIR__ . '/../models/course_module.php');
require_once(__DIR__ . '/../models/section.php');
require_once(__DIR__ . '/skin_manager.php');


// Define all format globals here.

// Always accessible.
define('FORMAT_LUDIC_ACCESS_ACCESSIBLE', 1);

// An activity is visible but not accessible until the previous activity has been completed.
define('FORMAT_LUDIC_ACCESS_CHAINED', 2);

// An activity is not visible or accessible until the previous activity has been completed,
// at which time it appears and becomes accessible.
define('FORMAT_LUDIC_ACCESS_DISCOVERABLE', 3);

// The activity is not visible or accessible unless and until the teacher manually open up access to selected students.
define('FORMAT_LUDIC_ACCESS_CONTROLLED', 4);

// The item will become visible and available at the same moment as it's predecessor.
// (allowing one 'gateway' activity followed by freely available activity set, teacher control of access by activity group, ...)​.
define('FORMAT_LUDIC_ACCESS_GROUPED', 5);

// The item will become visible at the same moment as it's predecessor
// but will only become available after the predecessor has been completed.
define('FORMAT_LUDIC_ACCESS_CHAINED_AND_GROUPED', 6);


/**
 * Class context_helper
 *
 * @package format_ludic
 */
class context_helper {

    /**
     * Singleton.
     *
     * @var context_helper
     */
    public static $instance;

    /**
     * Moodle $PAGE.
     *
     * @var \moodle_page
     */
    private $page = null;


    /**
     * Moodle renderer object.
     *
     * @var \renderer
     */
    private $renderer = null;

    /**
     * Moodle $USER.
     *
     * @var \stdClass
     */
    private $user = null;

    /**
     * Moodle $COURSE.
     *
     * @var \stdClass
     */
    private $course = null;

    /**
     * Current course id.
     *
     * @var int
     */
    private $courseid = null;

    /**
     * Database access.
     *
     * @var database_api|null
     */
    private $dbapi = null;

    /**
     * Manipulate user data.
     *
     * @var data_api
     */
    private $dataapi = null;

    /**
     * Manipulate files.
     *
     * @var file_api
     */
    public $fileapi = null;

    /**
     * Current course context.
     *
     * @var \context_course
     */
    private $context = null;

    /**
     * Return course format moodle config.
     * Use {@link get_config()} with 'format_ludic'.
     *
     * @var \stdClass
     */
    private $config = null;

    /**
     * Current course format.
     * Use {@link course_get_format()}
     *
     * @var \format_base
     */
    private $courseformat = null;

    /**
     * Current course format options.
     *
     * @var array
     */
    private $courseformatoptions = null;

    /**
     * One of current course format options ('ludic_config').
     * In ludic_config you can find all the definitions of skins.
     *
     * @var array
     */
    private $ludicconfig = null;

    /**
     * Current course sections.
     *
     * @var section[]
     */
    private $sections = null;

    /**
     * Current course sections.
     *
     * @var section[]
     */
    private $sectionsbyid = [];

    /**
     * Current course section.
     *
     * @var section
     */
    private $section = null;

    /**
     * Current section->id.
     *
     * @var int
     */
    private $sectionid = null;

    /**
     * Current section->section.
     *
     * @var int
     */
    private $sectionidx = null;

    /**
     * Current course modules.
     *
     * @var course_module[]
     */
    private $coursemodules = null;

    /**
     * Current course module.
     *
     * @var course_module[]
     */
    private $coursemodule = null;

    /**
     * This includes information about the course-modules and the sections on the course.
     * It can also include dynamic data that has been updated for the current user.
     *
     * @var \course_modinfo
     */
    private $courseinfo = null;

    /**
     * Get array from course-module instance to cm_info object within this course, in order of appearance.
     *
     * @var \cm_info[]
     */
    private $coursemodulesinfo = null;

    /**
     * Force student view when true.
     *
     * @var bool
     */
    private $studentview = false;

    /**
     * View mode for section
     *
     * @var string
     */
    private $viewmode = '';

    /**
     * context_helper constructor.
     *
     * @param \moodle_page $page
     */
    public function __construct() {
        global $USER, $PAGE;
        $this->page     = $PAGE;
        $this->user     = $USER;
        $this->courseid = $this->page->course->id;
        $this->dbapi    = new database_api($this);
        $this->dataapi  = new data_api($this);
        $this->fileapi  = new file_api();
    }

    /**
     * @param \moodle_page $page
     * @return context_helper
     */
    public static function get_instance() {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @return \moodle_page
     */
    public function get_page() {
        return $this->page;
    }

    /**
     * @return database_api
     */
    public function get_database_api() {
        return $this->dbapi;
    }

    /**
     * @return data_api
     */
    public function get_data_api() {
        return $this->dataapi;
    }

    /**
     * Return current global $USER.
     *
     * @return \stdClass $USER
     */
    public function get_user() {
        return $this->user;
    }

    /**
     * Return current user id.
     *
     * @return int
     */
    public function get_user_id() {
        return $this->get_user()->id;
    }

    /**
     * Return current course.
     *
     * @return course
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function get_course() {
        if ($this->course === null) {
            $this->course = new course($this->get_moodle_course());
        }
        return $this->course;
    }

    /**
    /**
     * Return current course.
     *
     * @return \stdClass
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function get_moodle_course() {
        if ($this->page->course->id != $this->courseid) {
            $this->page->set_course(get_course($this->courseid));
        }
        return $this->page->course;
    }

    /**
     * Return current course id.
     *
     * @return int
     */
    public function get_course_id() {
        return $this->courseid;
    }

    /**
     * Set course id if current course id is SITE, return current course id.
     *
     * @param $courseid
     * @return int
     */
    public function set_course_id($courseid) {
        if ($this->courseid == SITEID) {
            $this->courseid = $courseid;
        }
        return $this->courseid;
    }

    /**
     * @return string
     */
    public function get_viewmode(){
        return $this->viewmode;
    }

    /**
     * @param $viewmode
     */
    public function set_viewmode($viewmode){
        $this->viewmode = $viewmode;
    }

    /**
     * Return current section id or 0.
     *
     * @param $forcedsectionidx int
     * @return int|mixed|null
     */
    public function get_section_id($forcedsectionidx = 0) {

        // We have a stored section id then return it.
        if ($this->sectionid !== null && !$forcedsectionidx) {
            return $this->sectionid;
        }

        // We haven't got a stored section id then try generating one.
        try {
            $sectionidx = $forcedsectionidx ?: optional_param('section', 0, PARAM_INT);
        } catch (\coding_exception $e) {
            $sectionidx = 0;
        }

        // Replace the null with a 0 to avoid wasting times on trying to re-evaluate next time round.
        $this->sectionid = 0;

        if ($this->page->pagetype == 'course-view-ludic') {

            // We are not in a section.
            if ($sectionidx === 0) {
                return $this->sectionid;
            }

            // We're on a course view page and the course-relative section number is provided
            // so lookup the real section id.
            $courseid = $this->courseid;
            try {
                // Set current section idx.
                $this->sectionidx = $sectionidx;

                // Retrive section id.
                $sectionid = $this->dbapi->get_section_id_by_courseid_and_sectionidx($courseid, $sectionidx);
            } catch (\dml_exception $e) {
                // Error - display course.
                $sectionid = 0;
            }
            $this->sectionid = (int) $sectionid;

        } else if (isset($this->page->cm->section)) {

            // We're in an activity that is declaring its section id so we're in luck.
            $this->sectionid = $this->page->cm->section;

        }
        // Return the stored result.
        return $this->sectionid;
    }

    /**
     * Return current section->section (sectionidx) or -1.
     *
     * @return mixed|null
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_section_idx() {

        // We have a stored section idx then return it.
        if ($this->sectionidx !== null) {
            return $this->sectionidx;
        }
        // Replace the null with a -1 to avoid wasting times on trying to re-evaluate next time round.
        $this->sectionidx = -1;

        $sectionid = $this->get_section_id();
        if ($sectionid > 0) {
            // Retrieve section idx from section id.
            $this->sectionidx = $this->dbapi->get_section_idx_by_id($sectionid);
        }

        // Return the stored result.
        return $this->sectionidx;
    }

    /**
     * Return current cm id or 0.
     *
     * @return int
     */
    public function get_course_module_id() {
        return isset($this->page->cm->id) ? $this->page->cm->id : 0;
    }

    /**
     * Return the renderer.
     *
     * @return object
     */
    public function get_renderer() {
        if ($this->renderer === null) {
            $this->renderer = $this->page->get_renderer('format_ludic');
        }
        return $this->renderer;
    }

    /**
     * Return where the user is in course - course / section / mod.
     *
     * @return string
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function get_domain() {
        $cmid       = $this->get_course_module_id();
        $sectionidx = $this->get_section_idx();

        // On course page by default.
        $domain = 'course';

        if ($cmid > 0) {

            // We are in course module.
            $domain = 'coursemodule';

        } else if ($sectionidx > 0) {

            // We are in course section.
            $domain = 'section';
        }

        return $domain;
    }

    /**
     * Get current url.
     *
     * @return string
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_current_url() {
        $domain = $this->get_domain();
        switch ($domain) {
            case 'section':
                $url = new \moodle_url('/course/view.php', array(
                    'id'      => $this->get_course_id(),
                    'section' => $this->get_section_idx()
                ));
                break;
            case 'coursemodule':
                $cmid    = $this->get_course_module_id();
                $modname = $this->dbapi->get_module_name_by_course_module_id($cmid);
                $url     = new \moodle_url('/mod/' . $modname . '/view.php', array('id' => $cmid));
                break;
            default:
                $url = new \moodle_url('/course/view.php', array('id' => $this->get_course_id()));
        }

        return $url->out();
    }

    /**
     * Return current course context.
     *
     * @return \context_course
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_course_context() {
        if ($this->context === null) {
            $this->context = $this->get_course()->get_context();
        }
        return $this->context;
    }

    /**
     * Return current course context id.
     *
     * @return int
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_context_id() {
        return $this->get_course_context()->id;
    }

    /**
     * Check if user is admin.
     *
     * @return bool
     */
    public function is_user_admin() {
        return is_siteadmin($this->get_user_id());
    }

    /**
     * Check if user is student (true even after changing the role)
     *
     * @return bool
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function user_has_student_role() {
        return !has_capability('moodle/course:viewhiddencourses', $this->get_course_context());
    }

    /**
     * True if in edit mode.
     *
     * @return bool
     */
    public function is_editing() {
        if ($this->studentview) {
            return false;
        }
        return $this->page->user_is_editing();
    }

    /**
     * Return true if user can use edition mode
     *
     * @return bool
     */
    public function can_edit() {
        return $this->page->user_allowed_editing();
    }

    /**
     * Return count of real sections (ignore section 0).
     *
     * @return bool
     * @throws \moodle_exception
     */
    public function count_sections() {
        return count($this->get_sections());
    }

    /**
     * True if student view is forced, else false.
     *
     * @return bool
     */
    public function is_student_view_forced() {
        return $this->studentview;
    }

    /**
     * Force student view.
     */
    public function enable_student_view() {
        $this->studentview = true;
    }

    /**
     * Disable force student view.
     */
    public function disable_student_view() {
        $this->studentview = false;
    }

    /**
     * Checks if the current page type is part of an array of page types.
     *
     * @param $pagetypes
     * @return bool
     */
    public function is_page_type_in($pagetypes) {
        // Get current type page.
        $currenttype = $this->page->pagetype;

        // Check if current type is in given array.
        return in_array($currenttype, $pagetypes);
    }

    /**
     * Get course info of current course.
     *
     * @return \course_modinfo
     * @throws \moodle_exception
     */
    public function get_course_info() {
        if ($this->courseinfo == null) {
            $this->courseinfo = $this->get_course()->get_course_info();
        }
        return $this->courseinfo;
    }

    /**
     * Rebuild course info.
     *
     * @throws \moodle_exception
     */
    public function rebuild_course_info() {
        $this->courseinfo = $this->get_course()->get_course_info();
    }

    /**
     * Get array from course-module instance to cm_info object within this course, in order of appearance.
     *
     * @return \cm_info[]
     * @throws \moodle_exception
     */
    public function get_course_modules_info() {
        if ($this->coursemodulesinfo == null) {
            $cms = $this->get_course_info()->get_cms();
            foreach ($cms as $cmid => $cm) {
                if ($cm->deletioninprogress == 0) {
                    $this->coursemodulesinfo[$cmid] = $cm;
                }
            }
        }
        return $this->coursemodulesinfo;
    }

    /**
     * Get course by id.
     *
     * @param $courseid
     * @return course
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function get_course_by_id($courseid) {
        if ($courseid === $this->courseid) {
            return $this->get_course();
        }
        $course = \get_course($courseid);
        $course = new course($course);
        return $course;
    }

    /**
     * Get all sections of current course.
     *
     * @return section[]
     * @throws \moodle_exception
     */
    public function get_sections() {
        if ($this->sections == null) {
            $this->sections = $this->get_course()->get_sections();
        }
        return $this->sections;
    }

    /**
     * Get current course section or false.
     *
     * @return bool|section
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_section() {
        if ($this->section == null) {
            $sectionid     = $this->get_section_id();
            $this->section = $sectionid > 0 ? $this->get_section_by_id($sectionid) : false;
        }
        return $this->section;
    }

    /**
     * Get section by id or false.
     *
     * @param $sectionid
     * @return section|false
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_section_by_id($sectionid) {
        if (isset($this->sectionsbyid[$sectionid])) {
            return $this->sectionsbyid[$sectionid];
        }

        $sectionrecord  = $this->get_database_api()->get_section_by_id($sectionid);
        $newsection     = new section($sectionrecord);
        $this->sectionsbyid[$sectionid] = $newsection;
        return $newsection;
    }

    public function prefetch_data_edit_mode() {
        $sectionsrecords = $this->get_database_api()->get_course_sections_by_courseid($this->get_course_id());
        foreach ($sectionsrecords as $sectionrecord) {
            $sectionid = $sectionrecord->id;
            $this->sectionsbyid[$sectionid] = new section($sectionrecord);
        }
    }

    public function prefetch_data_section_page_mode() {
        $sectionid     = $this->get_section_id();
        $sectionrecord = $this->get_database_api()->get_section_by_id($sectionid);
        $this->sectionsbyid[$sectionid] = new section($sectionrecord);
    }

    public function prefetch_data_course_page_mode() {
        $sectionsrecords = $this->get_database_api()->get_course_sections_by_courseid($this->get_course_id());
        foreach ($sectionsrecords as $sectionrecord) {
            $sectionid = $sectionrecord->id;
            $this->sectionsbyid[$sectionid] = new section($sectionrecord);
        }
    }

    /**
     * Get section 0 id.
     *
     * @return int
     * @throws \dml_exception
     */
    public function get_global_section_id() {
        return $this->dbapi->get_section_id_by_courseid_and_sectionidx($this->courseid, 0);
    }

    /**
     * Get section 0
     *
     * @return false|section
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_global_section() {
        // Return section 0.
        $globalsectionid = $this->get_global_section_id();
        return $this->get_section_by_id($globalsectionid);
    }

    /**
     * Get section 0 description.
     *
     * @return string
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_global_description() {
        // Get section 0.
        $globalsection = $this->get_global_section();

        // Return section 0 description.
        return !empty($globalsection) ? $globalsection->dbrecord->summary : '';
    }

    /**
     * Get all course modules of current course.
     *
     * @return course_module[]
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_course_modules() {
        if ($this->coursemodules == null) {

            // Get an array of cm_info[].
            $coursemodulesinfo = $this->get_course_modules_info();

            // Instantiate course modules.
            $coursemodules = [];
            foreach ($coursemodulesinfo as $courseinfocm) {
                $coursemodules[] = new course_module($courseinfocm);
            }
            // Set coursemodules in cache.
            $this->coursemodules = $coursemodules;
        }

        // Return an array of course_module[].
        return $this->coursemodules;
    }

    /**
     * Get current course module or false.
     *
     * @return bool|course_module
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_course_module() {
        if ($this->coursemodule == null) {
            $cmid               = $this->get_course_module_id();
            $this->coursemodule = $cmid > 0 ? $this->get_course_module_by_id($cmid) : false;
        }
        return $this->coursemodule;
    }

    /**
     * Get course module by id.
     *
     * @param $cmid
     * @return course_module
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_course_module_by_id($cmid) {
        $courseinfo   = $this->get_course_info();
        $courseinfocm = $courseinfo->get_cm($cmid);
        return new course_module($courseinfocm);
    }

    /**
     * Get course format of current course.
     *
     * @return \format_base
     */
    public function get_course_format() {
        if ($this->courseformat == null) {
            $this->courseformat = $this->get_course_format_by_course_id($this->courseid);
        }
        return $this->courseformat;
    }

    /**
     * Get course format by course id.
     *
     * @param $courseid
     * @return \format_base
     */
    public function get_course_format_by_course_id($courseid) {
        return course_get_format($courseid);
    }

    /**
     * Get course format options of current course.
     *
     * @return array
     */
    public function get_course_format_options() {
        if ($this->courseformatoptions == null) {
            $this->courseformatoptions = $this->get_course_format_options_by_course_id($this->courseid);
        }
        return $this->courseformatoptions;
    }

    /**
     * Get course format option of current course with name = $name.
     *
     * @param $name
     * @return bool|mixed
     */
    public function get_course_format_option_by_name($name) {
        $courseformatoptions = $this->get_course_format_options();
        return isset($courseformatoptions[$name]) ? $courseformatoptions[$name] : false;
    }

    /**
     * Get course format options by course id.
     *
     * @param $courseid
     * @return array
     */
    public function get_course_format_options_by_course_id($courseid) {
        $courseformat = $this->get_course_format_by_course_id($courseid);
        return $courseformat->get_format_options();
    }

    /**
     * Updates format options for a course
     *
     * If $courseformatoptions does not contain property with the option name, the option will not be updated
     *
     * @param \stdClass|array $courseformatoptions return value from {@link moodleform::get_data()} or array with data
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($courseformatoptions) {
        $courseformat = $this->get_course_format();
        $courseformat->update_course_format_options($courseformatoptions);

        $this->courseformat = null;
        $this->courseformatoptions = null;
        $this->get_course_format_options();
        $this->get_course_format();
        return true;
    }

    /**
     * Return all skins that can be applied
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_skins_format() {
        // Cm.
        $noludic                                                     = \format_ludic\coursemodule\inline::get_instance();
        $skins[\format_ludic\coursemodule\inline::get_unique_name()] = $noludic;

        $score                                                      = \format_ludic\coursemodule\score::get_instance();
        $skins[\format_ludic\coursemodule\score::get_unique_name()] = $score;

        $progress                                                      = \format_ludic\coursemodule\progress::get_instance();
        $skins[\format_ludic\coursemodule\progress::get_unique_name()] = $progress;

        // Section.
        $noludic                                                 = \format_ludic\section\noludic::get_instance();
        $skins[\format_ludic\section\noludic::get_unique_name()] = $noludic;

        $sectionscore                                          = \format_ludic\section\score::get_instance();
        $skins[\format_ludic\section\score::get_unique_name()] = $sectionscore;

        $sectioncollection                                          = \format_ludic\section\collection::get_instance();
        $skins[\format_ludic\section\collection::get_unique_name()] = $sectioncollection;

        $sectionprogress                                          = \format_ludic\section\progress::get_instance();
        $skins[\format_ludic\section\progress::get_unique_name()] = $sectionprogress;

        return $skins;
    }

    /**
     * Get default skins.
     * They don't depend on the ludic config !
     * Ignore skin specific to global section.
     *
     * @return skin[]
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_default_skins() {
        return [
            coursemodule\inline::get_instance(),
            section\noludic::get_instance()
        ];
    }

    /**
     * Get section skins.
     *
     * @return skin[]
     */
    public function get_section_skins() {
        // Get all skins.
        $skins = $this->get_skins();

        // Keep only section skin.
        $sectionskins = [];
        foreach ($skins as $skinid => $skin) {
            if ($skin->type == 'noludic') {
                $sectionskins[$skin->id] = $skin;
                continue;
            }
            if ($skin->domain == 'section') {
                $sectionskins[$skin->id] = $skin;
            }
        }

        // Return filtered skins.
        return $sectionskins;
    }

    /**
     * Get course modules skins for course modules in section 0 only.
     *
     * @return skin[]
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_global_section_skins() {
        return [
            coursemodule\menubar::get_instance(),
            coursemodule\stealth::get_instance()
        ];
    }

    /**
     * Get course modules skins.
     *
     * @return skin[]
     */
    public function get_course_module_skins() {

        // Get all skins.
        $skins = $this->get_skins();

        // Keep only course module skin.
        $coursemodulesskins = [];
        foreach ($skins as $skin) {

            if ($skin->domain === 'coursemodule') {
                $coursemodulesskins[$skin->id] = $skin;
            }
        }

        // Return filtered skins.
        return $coursemodulesskins;
    }

    public function get_next_skinid() {
        $maxskinid = 0;
        $skins     = $this->get_skins();
        foreach ($skins as $skin) {
            if ($skin->id > $maxskinid) {
                $maxskinid = $skin->id;
            }
        }
        return $maxskinid + 1;
    }

    public function add_new_skin($skindata) {
        $ludicconfig          = $this->get_course_format_option_by_name('ludic_config');
        $ludicconfig          = json_decode($ludicconfig);
        $ludicconfig->skins[] = $skindata;
        $this->update_course_format_options(['ludic_config' => json_encode($ludicconfig)]);
    }

    public function update_skin() {

    }

    public function delete_skin($skinid) {
        $ludicconfig = $this->get_course_format_option_by_name('ludic_config');
        $ludicconfig = json_decode($ludicconfig);
        foreach ($ludicconfig->skins as $key => $skin) {
            if ($skin->id == $skinid) {
                unset($ludicconfig->skins[$key]);
            }
        }
        $ludicconfig->skins = array_values($ludicconfig->skins);
        $this->update_course_format_options(['ludic_config' => json_encode($ludicconfig)]);
    }

    public function skin_is_used($skinid) {
        $skin = $this->get_skin_by_id($skinid);

        if (!$skin) {
            return true;
        }

        if ($skin->domain == 'section') {
            $sections = $this->get_sections();
            foreach ($sections as $section) {
                if ($section->skinid == $skinid) {
                    return true;
                }
            }
        } else {
            $coursemodules = $this->get_course_modules();
            foreach ($coursemodules as $cm) {
                if ($cm->skinid == $skinid) {
                    return true;
                }
            }
        }

        return false;
    }

    public function is_single_section() {
        $sections = $this->get_sections();
        return count($sections) == 1;
    }
}

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

defined('MOODLE_INTERNAL') || die();

// Define all format globals here.

// Skin inline id.
define('FORMAT_LUDIC_CM_SKIN_INLINE_ID', 1);

// Access.
define('FORMAT_LUDIC_ACCESS_HIDDEN', 0);
define('FORMAT_LUDIC_ACCESS_ACCESSIBLE', 1);
define('FORMAT_LUDIC_ACCESS_CHAINED', 2);
define('FORMAT_LUDIC_ACCESS_DISCOVERABLE', 3);
define('FORMAT_LUDIC_ACCESS_CONTROLLED', 4);
define('FORMAT_LUDIC_ACCESS_GROUPED', 4);
define('FORMAT_LUDIC_ACCESS_CHAINED_AND_GROUPED', 5);

require_once($CFG->dirroot . '/course/format/ludic/lib.php');

class context_helper {

    // Singleton.
    public static $instance;

    // Environment properties.
    private $page                = null;
    private $user                = null;
    private $dbapi               = null;
    private $dataapi             = null;
    private $contextcourse       = null;
    private $ludicconfig         = null;
    private $course              = null;
    private $courseformat        = null;
    private $courseformatoptions = null;
    private $sections            = null;
    private $section             = null;
    private $sectionid           = null;
    private $sectionidx          = null;
    private $coursemodules       = null;
    private $coursemodule        = null;
    private $modinfo             = null;
    private $modinfocms          = null;
    private $config              = null;
    private $weightoptions       = null;

    /**
     * context_helper constructor.
     *
     * @param \moodle_page $page
     */
    public function __construct(\moodle_page $page) {
        global $USER;
        $this->page    = $page;
        $this->user    = $USER;
        $this->dbapi   = new database_api($this);
        $this->dataapi = new data_api($this);
    }

    /**
     * @param \moodle_page $page
     * @return context_helper
     */
    public static function get_instance(\moodle_page $page) {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self($page);
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
     * Return current course id.
     *
     * @return int
     */
    public function get_course_id() {
        return $this->page->course->id;
    }

    /**
     * Return current section id or 0.
     *
     * @return int|mixed|null
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_section_id() {

        // We have a stored section id then return it.
        if ($this->sectionid !== null) {
            return $this->sectionid;
        }

        // We haven't got a stored section id then try generating one.
        $sectionidx = optional_param('section', 0, PARAM_INT);

        // Replace the null with a 0 to avoid wasting times on trying to re-evaluate next time round.
        $this->sectionid = 0;

        if ($this->page->pagetype == 'course-view-ludimoodle') {

            // We're on a course view page and the course-relative section number is provided
            // so lookup the real section id.
            $courseid        = $this->get_course_id();
            $sectionid       = $this->dbapi->get_section_id_by_courseid_and_sectionidx($courseid, $sectionidx);
            $this->sectionid = $sectionid;

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
    public function get_cm_id() {
        return isset($this->page->cm->id) ? $this->page->cm->id : 0;
    }

    /**
     * Return where the user is in course - course / section / mod.
     *
     * @return string
     * @throws \dml_exception
     */
    public function get_location() {
        $cmid       = $this->get_cm_id();
        $sectionidx = $this->get_section_idx();

        // On course page by default.
        $location = 'course';

        if ($cmid > 0) {

            // We are in course module.
            $location = 'mod';

        } else if ($sectionidx > 0) {

            // We are in course section.
            $location = 'section';

        }

        return $location;
    }

    /**
     * Return context course by course id.
     *
     * @return \context_course
     */
    public function get_context_course_by_courseid($courseid) {
        return \context_course::instance($courseid);
    }

    /**
     * Return current context course.
     *
     * @return \context_course
     */
    public function get_context_course() {
        if ($this->contextcourse === null) {
            $courseid            = $this->get_course_id();
            $this->contextcourse = $this->get_context_course_by_courseid($courseid);
        }
        return $this->contextcourse;
    }

    /**
     * Return current context course id.
     *
     * @return int
     */
    public function get_context_course_id() {
        return $this->get_context_course()->id;
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
     * Check if user has the role defined in shortname.
     *
     * @param $roleshortname
     * @return bool
     * @throws \dml_exception
     */
    public function user_has_role_in_course($roleshortname) {
        if (!$roleid = $this->dbapi->get_role_id_by_role_shortname($roleshortname)) {
            return false;
        }
        return user_has_role_assignment($this->get_user_id(), $roleid, $this->get_context_course_id());
    }

    /**
     * Checks if the current user has one role in an array of role shortnames.
     *
     * @param $roleshortnames array
     * @return bool
     * @throws \dml_exception
     */
    public function user_has_one_role_in_course($roleshortnames) {
        $hasrole = false;
        foreach ($roleshortnames as $roleshortname) {
            if (!$roleid = $this->dbapi->get_role_id_by_role_shortname($roleshortname)) {
                $hasrole = false;
                continue;
            }
            $hasrole = user_has_role_assignment($this->get_user_id(), $roleid, $this->get_context_course_id());
        }
        return $hasrole;
    }

    /**
     * True if in edit mode.
     *
     * @return bool
     */
    public function is_editing() {
        return $this->page->user_is_editing();
    }

    /**
     * Checks if the current page type is part of an array of page types.
     *
     * @param $pagetypes
     * @return bool
     */
    public function is_page_type_in($pagetypes) {
        $currenttype = $this->page->pagetype;
        foreach ($pagetypes as $type) {
            if ($type === $currenttype) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get fast mod info of current course.
     *
     * @return \course_modinfo
     * @throws \moodle_exception
     */
    public function get_fast_modinfo() {
        if ($this->modinfo == null) {
            $courseid      = $this->get_course_id();
            $userid        = $this->get_user_id();
            $this->modinfo = get_fast_modinfo($courseid, $userid);
        }
        return $this->modinfo;
    }

    /**
     * Rebuild fast mod info.
     *
     * @throws \moodle_exception
     */
    public function rebuild_fast_modinfo() {
        $courseid      = $this->get_course_id();
        $userid        = $this->get_user_id();
        $this->modinfo = get_fast_modinfo($courseid, $userid);
    }

    /**
     * Get mod info cms of current course.
     *
     * @return \cm_info[]|null
     * @throws \moodle_exception
     */
    public function get_modinfo_cms() {
        if ($this->modinfocms == null) {
            $modinfo          = $this->get_fast_modinfo();
            $this->modinfocms = $modinfo->get_cms();
        }
        return $this->modinfocms;
    }

    /**
     * Get current course.
     *
     * @return course
     * @throws \dml_exception
     */
    public function get_course() {
        if ($this->course == null) {
            $this->course = $this->get_course_by_id($this->get_course_id());
        }
        return $this->course;
    }

    /**
     * Get course by id.
     *
     * @param $courseid
     * @return course|\stdClass
     * @throws \dml_exception
     */
    public function get_course_by_id($courseid) {
        $course = \get_course($courseid);
        $course = new course($course);
        return $course;
    }

    /**
     * Get all sections of current course.
     *
     * @return section[]|null
     * @throws \moodle_exception
     */
    public function get_sections() {
        if ($this->sections == null) {
            $this->sections = $this->get_sections_by_course_id($this->get_course_id());
        }
        return $this->sections;
    }

    /**
     * Return all course sections by course id.
     *
     * @return section[]
     * @throws \moodle_exception
     */
    public function get_sections_by_course_id($courseid) {

        // Get sections list.
        $sectionrecords = $this->dbapi->get_course_sections_by_courseid($courseid);

        // Return section object.
        $sections = [];
        foreach ($sectionrecords as $section) {

            // Ignore section 0.
            if ($section->section == 0) {
                continue;
            }

            $section                     = new section($section);
            $sections[$section->section] = $section;
        }

        return $sections;
    }

    /**
     * Get current course section or false.
     *
     * @return bool|section
     * @throws \coding_exception
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
     * Get section by id.
     *
     * @param $sectionid
     * @return section|false
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_section_by_id($sectionid) {
        $sectionrecord = $this->dbapi->get_section_by_id($sectionid);
        return $sectionrecord ? new section($sectionrecord) : false;
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
            $modinfocms    = $this->get_modinfo_cms();
            $coursemodules = [];
            foreach ($modinfocms as $modinfocm) {
                $coursemodules[] = new course_module($modinfocm);
            }
            $this->coursemodules = $coursemodules;
        }
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
            $cmid               = $this->get_cm_id();
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
        $modinfo   = $this->get_fast_modinfo();
        $modinfocm = $modinfo->get_cm($cmid);
        return new course_module($modinfocm);
    }

    /**
     * Get course format of current course.
     *
     * @return \format_base
     */
    public function get_course_format() {
        if ($this->courseformat == null) {
            $this->courseformat = $this->get_course_format_by_course_id($this->get_course_id());
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
            $this->courseformatoptions = $this->get_course_format_options_by_course_id($this->get_course_id());
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
        return $courseformat->update_course_format_options($courseformatoptions);
    }

    /**
     * @return array
     */
    private function get_ludic_config() {
        if ($this->ludicconfig == null) {

            // Get ludic config (json).
            $ludicconfig = $this->get_course_format_option_by_name('ludic_config');

            // Always return an array.
            if (!empty($ludicconfig)) {
                $this->ludicconfig = get_object_vars(json_decode($ludicconfig));
            } else {
                $this->ludicconfig = [];
            }

        }

        return $this->ludicconfig;
    }

    /**
     * @return array
     */
    public function get_skins_config() {
        $ludicconfig = $this->get_ludic_config();
        return isset($ludicconfig['skins']) ? get_object_vars($ludicconfig['skins']) : [];
    }

    /**
     * @return skin[]
     */
    public function get_skins() {
        $skins        = [];
        $defaultskins = $this->get_default_skins();
        $skinsconfig  = $this->get_skins_config();
        $allskins     = array_merge($defaultskins, $skinsconfig);
        foreach ($allskins as $skin) {
            $skins[$skin->id] = skin::get_by_instance($skin);
        }
        return $skins;
    }

    /**
     * @return array
     */
    public function get_default_skins() {
        return [
                inline::get_instance()
        ];
    }

    /**
     * @return skin[]
     */
    public function get_section_skins() {
        $skins        = $this->get_skins();
        $sectionskins = [];
        foreach ($skins as $skin) {
            if ($skin->location === 'section') {
                $sectionskins[$skin->id] = $skin;
            }
        }
        return $sectionskins;
    }

    /**
     * @return skin[]
     */
    public function get_course_module_skins() {
        $skins              = $this->get_skins();
        $coursemodulesskins = [];
        foreach ($skins as $skin) {
            if ($skin->location === 'coursemodule') {
                $coursemodulesskins[$skin->id] = $skin;
            }
        }
        return $coursemodulesskins;
    }

    /**
     * @param $cmid
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_available_course_module_skins($cmid) {
        $skins   = $this->get_course_module_skins();
        $modname = $this->dbapi->get_module_name_by_course_module_id($cmid);

        // Label can use only inline skin.
        if ($modname === 'label') {
            return [inline::get_instance()];
        }

        $isgraded = $modname ? plugin_supports('mod', $modname, FEATURE_GRADE_HAS_GRADE, false) : false;

        $coursemodulesskins = [];
        foreach ($skins as $skin) {
            if ($skin->require_grade() && !$isgraded) {
                continue;
            }
            $coursemodulesskins[$skin->id] = $skin;
        }
        return $coursemodulesskins;
    }

    /**
     * Create an section in the course defined in $courseid.
     *
     * @param $courseid
     * @return bool|false|section
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function create_section($courseid) {
        $nbsections = $this->dbapi->count_course_sections($courseid);

        if (!$this->dbapi->create_section($courseid, $nbsections)) {
            return false;
        }

        if (!$newsection = $this->dbapi->get_course_last_section($courseid)) {
            return false;
        }

        //rebuild_course_cache($courseid, true);

        return $this->get_section_by_id($newsection->id);
    }

    public function get_course_format_config() {
        if ($this->config == null) {
            $this->config = get_config('format_ludic');
        }

        return $this->config;
    }

    public function get_course_module_weight_options() {
        if ($this->weightoptions == null) {
            $config              = $this->get_course_format_config();
            $weightoptions       = isset($config->weight) ? $config->weight : format_ludic_get_default_weight_setting();
            $weightoptions       = explode(',', $weightoptions);
            $this->weightoptions = array_map('trim', $weightoptions);
        }

        return $this->weightoptions;
    }

    public function get_default_weight() {
        $weightoptions = $this->get_course_module_weight_options();
        $defaultkey    = round(count($weightoptions) / 2, 0, PHP_ROUND_HALF_DOWN);
        return isset($weightoptions[$defaultkey]) ? $weightoptions[$defaultkey] : 0;
    }

    /**
     * @param $cmid
     * @return \stdClass
     * @throws \dml_exception
     */
    public function get_format_ludic_cm_by_cmid($courseid, $cmid) {
        $dbrecord = $this->dbapi->get_format_ludic_cm_by_cmid($cmid);
        if ($dbrecord) {
            return $dbrecord;
        }
        $skin               = skin::get_default_course_module_skin($cmid);
        $dbrecord           = new \stdClass();
        $dbrecord->courseid = $courseid;
        $dbrecord->cmid     = $cmid;
        $dbrecord->skinid   = $skin->id;
        $dbrecord->weight   = $this->get_default_weight();
        $dbrecord->hidden   = 1;
        $dbrecord->linked   = null;

        $newid = $this->dbapi->add_format_ludic_cm_record($dbrecord);
        return $dbrecord;
    }
}
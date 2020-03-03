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

require_once($CFG->dirroot . '/course/format/ludic/lib.php');

class context_helper {

    // Singleton.
    public static $instance;

    // Environment properties.
    private $page            = null;
    private $user            = null;
    private $dbapi           = null;
    private $dataapi         = null;
    private $contextcourse   = null;
    private $courseid        = null;
    private $sectionid       = null;
    private $sectionidx      = null;
    private $section         = null;
    private $sections        = null;
    private $cminfo          = null;
    private $cmsinfo         = null;
    private $currentlocation = null;
    private $modinfo         = null;
    private $modinfocms      = null;

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
     * @return int
     */
    public function get_user_id() {
        return $this->user->id;
    }

    /**
     * @return int
     */
    public function get_course_id() {
        return $this->courseid === null ? $this->page->course->id : $this->courseid;
    }

    /**
     * @return \context_course
     */
    public function get_context_course() {
        if ($this->contextcourse === null) {
            $this->contextcourse = \context_course::instance($this->get_course_id());
        }
        return $this->contextcourse;
    }

    /**
     * @return int
     */
    public function get_context_course_id() {
        return $this->get_context_course()->id;
    }

    /**
     * @return int
     */
    public function get_cm_id() {
        return isset($this->page->cm->id) ? $this->page->cm->id : 0;
    }

    /**
     * @return int
     */
    public function get_attempt_id() {
        return $this->page->url->param('attempt') ? $this->page->url->param('attempt') : 0;
    }

    /**
     * @return int
     */
    public function get_attempt_page() {
        return $this->page->url->param('page') ? $this->page->url->param('page') : 0;
    }

    /**
     * @return int
     * @throws \dml_exception
     */
    public function get_section_idx() {
        if ($this->sectionidx === null) {
            if ($this->page->url->param('section') > 0) {
                $this->sectionidx = $this->page->url->param('section');
            } else if (isset($this->page->cm->section)) {
                // We're in an activity that is declaring its section id
                // so we need to lookup the corresponding course-relative index.
                $sectionid        = $this->page->cm->section;
                $dbapi = $this->get_database_api();
                $this->sectionidx = $sectionid ? $dbapi->get_section_idx_by_id($sectionid) : 0;
            } else {
                $this->sectionidx = -1;
            }
        }

        return $this->sectionidx;
    }

    /**
     * @return int
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_section_id() {
        // If we haven't got a stored section id then try generating one.
        if ($this->sectionid === null) {
            $coursesection = optional_param('section', 0, PARAM_INT);
            if ($this->page->pagetype == 'course-view-ludic') {
                $dbapi = $this->get_database_api();
                // We're on a course view page and the course-relative section number is provided so lookup the real section id.
                $this->sectionid = 0;
                if ($coursesection) {
                    $this->sectionid = $dbapi->get_sectionid_by_courseid_and_sectionidx($this->get_course_id(), $coursesection);
                }
            } else if (isset($this->page->cm->section)) {
                // We're in an activity that is declaring its section id so we're in luck.
                $this->sectionid = $this->page->cm->section;
            } else {
                // No luck so replace the null with a 0 to avoid wasting times on trying to re-evaluate next time round.
                $this->sectionid = $coursesection;
            }
        }
        // Return the stored result.
        return $this->sectionid;
    }

    /**
     * Return where the user is in course - course / section / mod.
     *
     * @return string
     * @throws \dml_exception
     */
    public function get_current_location() {
        if ($this->currentlocation !== null) {
            return $this->currentlocation;
        }
        $cmid       = $this->get_cm_id();
        $sectionidx = $this->get_section_idx();

        // On course page by default.
        $currentlocation = 'course';

        if ($cmid > 0) {
            $currentlocation = 'mod';
        } else if ($sectionidx > 0) {
            $currentlocation = 'section';
        }

        $this->currentlocation = $currentlocation;

        return $this->currentlocation;
    }

    /**
     * @return bool
     */
    public function is_user_admin() {
        return is_siteadmin($this->get_user_id());
    }

    /**
     * @param $roleshortname
     * @return bool
     * @throws \dml_exception
     */
    public function user_has_role_in_course($roleshortname) {
        if (!$roleid = $this->db->get_role_id_by_role_shortname($roleshortname)) {
            return false;
        }
        return user_has_role_assignment($this->get_user_id(), $roleid, $this->get_context_course_id());
    }

    /**
     * @param $roleshortnames array
     * @return bool
     * @throws \dml_exception
     */
    public function user_has_one_role_in_course($roleshortnames) {
        $hasrole = false;
        foreach ($roleshortnames as $roleshortname) {
            if (!$roleid = $this->db->get_role_id_by_role_shortname($roleshortname)) {
                $hasrole = false;
                continue;
            }
            $hasrole = user_has_role_assignment($this->get_user_id(), $roleid, $this->get_context_course_id());
        }
        return $hasrole;
    }

    /**
     * @return bool
     */
    public function is_editing() {
        return $this->page->user_is_editing();
    }

    /**
     * checks if the current page type is part of an array of page types
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
     * @return \course_modinfo
     * @throws \moodle_exception
     */
    public function get_fast_modinfo($courseid, $userid = null) {
        global $USER;
        // TODO cache. if ($this->modinfo == null) {
        if ($userid == null) {
            $userid = $USER->id;
        }
        $this->modinfo = get_fast_modinfo($courseid, $userid);
        //}
        return $this->modinfo;
    }

    /**
     * @param $courseid
     * @param $userid
     * @return \cm_info[]|null
     * @throws \moodle_exception
     */
    public function get_modinfo_cms($courseid, $userid) {
        if ($this->modinfocms == null) {
            $modinfo          = $this->get_fast_modinfo($courseid, $userid);
            $this->modinfocms = $modinfo->get_cms();
        }
        return $this->modinfocms;
    }

}
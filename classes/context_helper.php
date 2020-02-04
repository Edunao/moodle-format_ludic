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
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/ludic/lib.php');

/**
 *  The goal of this class is to provide isolation from the outside world.
 */
class context_helper {

    // Environment properties
    private $page            = null;
    private $user            = null;
    private $dbapi           = null;
    private $dataapi         = null;
    private $logapi          = null;
    private $fileapi         = null;
    private $contextcourse   = null;
    private $courseid        = null;
    private $sectionid       = null;
    private $sectionidx      = null;
    private $section         = null;
    private $sections        = null;
    private $cminfo          = null;
    private $cmsinfo         = null;
    private $modinfo         = null;
    private $currentlocation = null;

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
        $this->logapi  = new log_api($this);
        $this->fileapi = new file_api($this);
    }


    //-------------------------------------------------------------------------
    // Moodle context

    /**
     * @return \moodle_page
     */
    public function get_page() {
        return $this->page;
    }

    /**
     * @return int
     */
    public function get_userid() {
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
                // we're in an activity that is declaring its section id so we need to lookup the corresponding course-relative index
                $sectionid        = $this->page->cm->section;
                $this->sectionidx = $sectionid ? $this->db->get_sectionidx_by_sectionid($sectionid) : 0;
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
        // if we haven't got a stored section id then try generating one
        if ($this->sectionid === null) {
            $coursesection = optional_param('section', 0, PARAM_INT);
            if ($this->page->pagetype == 'course-view-ludic') {
                // we're on a course view page and the course-relative section number is provided so lookup the real section id
                $this->sectionid = $coursesection ?
                        $this->db->get_sectionid_by_courseid_and_sectionidx($this->get_course_id(), $coursesection) : 0;
            } else if (isset($this->page->cm->section)) {
                // we're in an activity that is declaring its section id so we're in luck
                $this->sectionid = $this->page->cm->section;
            } else {
                // no luck so replace the null with a 0 to avoid wasting times on trying to re-evaluate next time round
                $this->sectionid = $coursesection;
            }
        }
        // return the stored result
        return $this->sectionid;
    }

    /**
     * @return string
     */
    public function get_course_fullname() {
        return $this->page->course->fullname;
    }

    /**
     * @return string
     */
    public function get_section_fullname() {
        $section = $this->get_current_section();
        return $section->name;
    }

    /**
     * @return string
     */
    public function get_cm_fullname() {
        return isset($this->page->cm->name) ? $this->page->cm->name : '';
    }

    /**
     * @return array of course sections with a lot of data
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_course_sections() {
        if ($this->sections === null) {

            $this->sections = [];
            $modinfo        = $this->get_modinfo();
            $cms            = $this->get_cms_info();

            // get sections list
            $sectionrecords = $this->db->get_course_sections_by_courseid($this->get_course_id());

            // Fixup the records to make sure that they are complete
            foreach ($sectionrecords as $section) {
                // Section is visible for user.
                $sectioninfo          = $modinfo->get_section_info($section->section);
                $section->uservisible = $sectioninfo->uservisible;

                $section->sequence = explode(',', $section->sequence);
                $sequenceidx       = 0;
                foreach ($section->sequence as $cmid) {
                    if (isset($cms[$cmid])) {
                        $sequenceidx++;

                        $cm      = $cms[$cmid];
                        $cm->idx = $sequenceidx;

                        $section->cms[] = $cm;
                    }
                }

                $this->sections[$section->section] = $section;
            }
        }
        return $this->sections;
    }

    /**
     * Get current section with course modules.
     *
     * @return mixed|null
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_current_section() {
        if ($this->section == null) {

            // Return false if we can't retrieve section id.
            if (!$sectionid = $this->get_section_id()) {
                return false;
            }

            // Get section by id.
            $section = $this->db->get_course_sections_by_id($this->get_section_id());

            // Add course modules to section.
            $cms               = $this->get_cms_info();
            $section->sequence = explode(',', $section->sequence);
            $sequenceidx       = 0;
            foreach ($section->sequence as $cmid) {
                if (isset($cms[$cmid])) {
                    $sequenceidx++;

                    $cm      = $cms[$cmid];
                    $cm->idx = $sequenceidx;

                    $section->cms[] = $cm;
                }
            }

            $this->section = $section;
        }
        return $this->section;
    }

    /**
     * @return array of course modules
     * @throws \moodle_exception
     */
    public function get_cms_info() {
        // if the value of the attribute has already been retrieved then we return it
        if ($this->cmsinfo !== null) {
            return $this->cmsinfo;
        }

        $this->cmsinfo = [];

        $cms = $this->db->get_course_modules_by_courseid($this->courseid);

        $modinfo = $this->get_modinfo();
        foreach ($cms as $cmid => $cminfo) {
            if ($cminfo->visible == 1) {
                $cm                   = $modinfo->get_cm($cmid);
                $cminfo->name         = $cm->name;
                $cminfo->visible      = $cm->uservisible;
                $this->cmsinfo[$cmid] = $cminfo;
            }
        }

        return $this->cmsinfo;
    }

    /**
     * @return object|false : current cminfo with a lot of data
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_cm_info() {
        // If the value of the attribute has already been retrieved then we return it.
        if ($this->cminfo !== null) {
            return $this->cminfo;
        }

        $cmid   = $this->get_cm_id();
        $cminfo = $this->db->get_course_modules_by_id($cmid);

        // Course modules not found, return false.
        if (!$cminfo) {
            $this->cminfo = false;
            return $this->cminfo;
        }

        $modinfo         = $this->get_modinfo();
        $cm              = $modinfo->get_cm($cmid);
        $cminfo->name    = $cm->name;
        $cminfo->visible = $cm->uservisible;

        // Update the value in the cache if it has already been loaded.

        if ($this->cmsinfo !== null) {
            $this->cmsinfo[$cmid] = $cminfo;
        }

        $this->cminfo = $cminfo;

        return $this->cminfo;
    }

    /**
     * @return \course_modinfo
     * @throws \moodle_exception
     */
    public function get_modinfo() {
        if ($this->modinfo === null) {
            $this->modinfo = get_fast_modinfo($this->get_course_id(), $this->get_userid());
        }

        return $this->modinfo;
    }

    /**
     * Return where the user is in course - course / section / mod
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

        // course page by default
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
        return is_siteadmin($this->get_userid());
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
        return user_has_role_assignment($this->get_userid(), $roleid, $this->get_context_course_id());
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
            $hasrole = user_has_role_assignment($this->get_userid(), $roleid, $this->get_context_course_id());
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
}
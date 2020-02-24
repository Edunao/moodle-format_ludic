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
 * Section controller class
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;
require_once($CFG->dirroot . '/course/format/ludic/lib.php');

class section_controller extends controller_base {

    /**
     * Execute an action
     *
     * @return mixed
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public function execute() {
        $action = $this->get_param('action');
        switch ($action) {
            case 'move_to_section' :
                $cmid      = $this->get_param('idtomove', PARAM_INT);
                $sectionid = $this->get_param('toid', PARAM_INT);
                return $this->move_to_section($cmid, $sectionid);
            case 'move_on_section' :
                $cmidtomove = $this->get_param('idtomove', PARAM_INT);
                $aftercmid  = $this->get_param('toid', PARAM_INT);
                return $this->move_on_section($cmidtomove, $aftercmid);
            case 'move_section_to' :
                $sectionidtomove = $this->get_param('idtomove', PARAM_INT);
                $aftersectionid  = $this->get_param('toid', PARAM_INT);
                return $this->move_section_to($sectionidtomove, $aftersectionid);
            case 'get_properties' :
                $sectionid = $this->get_param('id', PARAM_INT);
                return $this->get_properties($sectionid);
            case 'get_children' :
                $sectionid = $this->get_param('id', PARAM_INT);
                return $this->get_children($sectionid);
            // Default case if no parameter is necessary.
            default :
                return $this->$action();
        }
    }

    public function get_parents() {
        global $PAGE;
        $dataapi  = $this->contexthelper->get_data_api();
        $courseid = $this->get_course_id();
        $course   = $dataapi->get_course_by_id($courseid);
        $sections = $course->get_sections();
        $output   = '';
        $renderer = $PAGE->get_renderer('format_ludic');
        foreach ($sections as $section) {
            $output .= $renderer->render_section($section);
        }

        return $output;
    }

    public function get_children($sectionid) {
        global $PAGE;
        $dataapi = $this->contexthelper->get_data_api();
        $section = $dataapi->get_section_by_id($sectionid);

        $coursemodules = $section->get_course_modules();

        $output   = '';
        $renderer = $PAGE->get_renderer('format_ludic');
        foreach ($coursemodules as $order => $coursemodule) {
            $coursemodule->order = $order;
            $output .= $renderer->render_course_module($coursemodule);
        }
        return $output;
    }

    public function get_properties($sectionid) {
        $dataapi = $this->contexthelper->get_data_api();
        $section = $dataapi->get_section_by_id($sectionid);
        return $section->render_form();
    }

    public function move_to_section($cmid, $sectionid) {
        $courseid     = $this->get_course_id();
        $userid       = $this->get_user_id();
        $dataapi      = $this->contexthelper->get_data_api();
        $coursemodule = $dataapi->get_course_module_by_id($courseid, $userid, $cmid);
        $oldsectionid = $coursemodule->sectionid;
        $isvisible    = $coursemodule->move_to_section($sectionid);
        return $this->get_children($oldsectionid);
    }

    public function move_on_section($cmidtomove, $aftercmid) {
        $courseid     = $this->get_course_id();
        $userid       = $this->get_user_id();
        $dataapi      = $this->contexthelper->get_data_api();
        $coursemodule = $dataapi->get_course_module_by_id($courseid, $userid, $cmidtomove);
        $sectionid    = $coursemodule->sectionid;
        $coursemodule->move_on_section($cmidtomove, $aftercmid);
        return $this->get_children($sectionid);
    }

    public function move_section_to($sectionidtomove, $aftersectionid) {
        $dataapi         = $this->contexthelper->get_data_api();
        $dbapi           = $this->contexthelper->get_database_api();
        $sectiontomove   = $dataapi->get_section_by_id($sectionidtomove);
        $aftersectionidx = $dbapi->get_section_idx_by_id($aftersectionid);

        $sectiontomove->move_section_to($aftersectionidx);
        return $this->get_parents();
    }




}

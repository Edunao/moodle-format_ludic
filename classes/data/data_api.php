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
 * Data interface.
 * All user data must be retrieved in this class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

class data_api {

    protected $contexthelper;

    /**
     * data_api constructor.
     *
     * @param $contexthelper context_helper
     */
    public function __construct($contexthelper) {
        $this->contexthelper = $contexthelper;
    }

    /**
     * Return course module user grade, grade min to pass, grade max, and grade weight from gradebook.
     *
     * @param $cminfo \cm_info
     * @return \stdClass
     * @throws \dml_exception
     */
    public function get_course_module_user_grade($cminfo) {
        // Initialize default object.
        $return = (object) [
                'isgradable'  => false,
                'grade'       => 0,
                'grademax'    => 1,
                'grademin'    => 0,
                'gradefactor' => 1
        ];

        // Get data for sql query.
        $courseid = $this->contexthelper->get_course_id();
        $modname  = $cminfo->modname;
        $instance = $cminfo->instance;
        $userid   = $this->contexthelper->get_user_id();

        // Get grade from database.
        $dbapi = $this->contexthelper->get_database_api();
        $grade = $dbapi->get_course_module_user_grade($courseid, $modname, $instance, $userid);

        // No record, return default object.
        if (!$grade) {
            return $return;
        }

        // Set data and return.
        $return->isgradable  = true;
        $return->grade       = $grade->grade !== null ? $grade->grade : $return->grade;
        $return->grademax    = (float) $grade->grademax;
        $return->grademin    = (float) $grade->grademin;
        $return->gradefactor = $grade->usegradefactor === 'used' ? $grade->gradefactor : $return->gradefactor;

        return $return;
    }

    /**
     * Return user completion.
     *
     * @param $cminfo \cm_info
     * @return \stdClass
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function get_course_module_user_completion($cminfo) {
        // Initialize default object.
        $return        = (object) [
                'state'         => COMPLETION_TRACKING_NONE,
                'completion'    => '',
                'completionstr' => ''
        ];
        $completion    = 'completion-disabled';
        $completionstr = 'completion_none';

        // Initialize completion info object.
        $course         = $this->contexthelper->get_moodle_course();
        $completioninfo = new \completion_info($course);

        // Check if completion is enabled on module.
        $hascompletion = $completioninfo->is_enabled($cminfo);
        if ($hascompletion == COMPLETION_DISABLED) {
            // Completion is disabled return default object.
            $return->completion    = $completion;
            $return->completionstr = get_string($completionstr, 'completion');
            return $return;
        }

        // Completion is enabled, get completion state.
        $userid  = $this->contexthelper->get_user_id();
        $modinfo = $this->contexthelper->get_course_info();
        $data    = $completioninfo->get_data($cminfo, false, $userid, $modinfo);

        // Define completion string from state.
        switch ($data->completionstate) {
            case COMPLETION_INCOMPLETE:
                $completion    = 'completion-incomplete';
                $completionstr = 'completion-n';
                break;
            case COMPLETION_COMPLETE:
                $completion    = 'completion-complete';
                $completionstr = 'completion-y';
                break;
            case COMPLETION_COMPLETE_PASS:
                $completion    = 'completion-complete-pass';
                $completionstr = 'completion-pass';
                break;
            case COMPLETION_COMPLETE_FAIL:
                $completion    = 'completion-complete-fail';
                $completionstr = 'completion-fail';
                break;
        }

        // Set data and return.
        $return->state         = $data->completionstate;
        $return->completion    = $completion;
        $return->completionstr = get_string($completionstr, 'completion');
        return $return;
    }

}


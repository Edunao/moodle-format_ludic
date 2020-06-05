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

require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->libdir . '/completionlib.php');

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
                'grade'      => 0,
                'grademax'   => 0,
                'proportion' => 0
        ];

        // When activity max grade is updated to 0, grademax of grade_items and grade_grades are not updated.
        // We need to change gradetype
        $gradeinfo = grade_get_grade_items_for_activity($cminfo);
        if($gradeinfo && end($gradeinfo)->gradetype == 0){
            return $return;
        }

        // Get data for grade api.
        $courseid = $this->contexthelper->get_course_id();
        $modname  = $cminfo->modname;
        $instance = $cminfo->instance;
        $userid   = $this->contexthelper->get_user_id();

        // Get grades.
        $grades = grade_get_grades($courseid, 'mod', $modname, $instance, $userid);
        $grades = $grades->items;

        // No record, return default object.
        if (count($grades) == 0) {
            return $return;
        }

        // Set data and return.
        $grade            = $grades[0];
        $return->grademax = (float) $grade->grademax;
        $return->grademax = $return->grademax > 0 ? $return->grademax : 1;

        // Grade is in first item of this array.
        if (count($grade->grades) > 0) {
            $grade         = reset($grade->grades);
            $return->grade = $grade->grade !== null ? $grade->grade : $return->grade;
            $return->proportion = ($return->grade / $return->grademax);
        }

        return $return;
    }

    public function cm_is_graded($cminfo){
        // Get data for grade api.
        $courseid = $this->contexthelper->get_course_id();
        $modname  = $cminfo->modname;
        $instance = $cminfo->instance;
        $userid   = $this->contexthelper->get_user_id();

        // Get grades.
        $grades = grade_get_grades($courseid, 'mod', $modname, $instance, $userid);
        return count($grades->items) > 0;
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
                'type'          => COMPLETION_DISABLED,
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
                // If cm is not graded, activity is considered as complete and pass
                if($this->cm_is_graded($cminfo)){
                    $completion    = 'completion-complete';
                    $completionstr = 'completion-y';
                    break;
                }
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
        $return->type          = $hascompletion;
        $return->completion    = $completion;
        $return->completionstr = get_string($completionstr, 'completion');
        return $return;
    }

}


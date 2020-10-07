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
 * Abstract controller class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

abstract class controller_base {

    /**
     * @var array
     */
    protected $params = array();

    /**
     * @var context_helper
     */
    protected $contexthelper;

    /**
     * @var \context_course
     */
    private $context;

    /**
     * controller_base constructor.
     *
     * @param $params
     * @throws \moodle_exception
     */
    public function __construct($params) {
        global $PAGE;
        $this->params = $params;
        $this->set_context();
        $this->contexthelper = context_helper::get_instance($PAGE);

    }

    /**
     * Set course context.
     *
     * @throws \moodle_exception
     */
    public function set_context() {
        global $PAGE;
        $this->context = \context_course::instance($this->get_course_id());
        $PAGE->set_context($this->context);
    }

    /**
     * Get course context.
     *
     * @return \context_course context instance
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get request param
     *
     * @param string $paramname
     * @param string $type default null if the type is not important
     * @param mixed $default default value if the param does not exist
     * @return mixed value of the param (or default value)
     * @throws \moodle_exception
     */
    public function get_param($paramname, $type = null, $default = false) {
        if (isset($this->params[$paramname])) {
            $param = $this->params[$paramname];
            if (!empty($type)) {
                $param = $this->check_type($paramname, $param, $type);
            }
            return $param;
        }
        return $default;
    }

    /**
     * Get course id.
     *
     * @return int
     * @throws \moodle_exception
     */
    public function get_course_id() {
        return $this->get_param('courseid', PARAM_INT);
    }

    /**
     * Get user id.
     *
     * @return int
     * @throws \moodle_exception
     */
    public function get_user_id() {
        return $this->get_param('userid', PARAM_INT);
    }

    /**
     * Check that the parameter is of the requested type.
     * Then convert value to ensure type.
     *
     * @param $paramname
     * @param $value
     * @param $type
     * @return int|string|mixed
     * @throws \moodle_exception
     */
    private function check_type($paramname, $value, $type) {
        switch ($type) {
            case PARAM_INT :
                if (!is_integer($value) && !ctype_digit($value)) {
                    print_error('param : ' . $paramname . ' must be an integer for the value : ' . $value);
                }
                return (int) $value;
            case PARAM_RAW :
                if (!is_string($value)) {
                    print_error('param : ' . $paramname . ' must be a string for the value : ' . $value);
                }
                return (string) $value;
            // Add cases for new types here .
            default :
                return $value;
        }
    }

    /**
     * Execute the controller action.
     *
     * @return mixed
     */
    abstract public function execute();

}

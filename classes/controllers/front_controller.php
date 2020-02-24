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
 * Abstract controller class
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

require_once($CFG->dirroot . '/course/format/ludic/lib.php');


/**
 * Class front_controller
 *
 * @package format_ludic
 */
class front_controller implements front_controller_interface {

    /**
     * @var array|null
     */
    protected $params = array();

    /**
     * @var callable
     */
    protected $controller;

    /**
     * @var callable
     */
    protected $action;

    /**
     * @var string
     */
    protected $namespace = 'format_ludic\\';

    /**
     * front_controller constructor.
     *
     * @param null $options
     * @throws \ReflectionException
     * @throws \moodle_exception
     */
    public function __construct($options = null) {
        if (!empty($options)) {
            $this->params = $options;
        } else {
            $this->set_params();
        }

        if (isset($this->params['controller'])) {
            $this->set_controller($this->params['controller']);
        }
        if (isset($this->params['action'])) {
            $this->set_action($this->params['action']);
        }
    }

    /**
     * Set controller
     *
     * @param string $controller
     * @return $this
     * @throws \moodle_exception
     */
    public function set_controller($controller) {
        global $CFG;
        $controller_url = $CFG->dirroot . '/course/format/ludic/classes/controllers/' . $controller . '.controller.php';
        if (!file_exists($controller_url)) {
            print_error('Controller file not found : ' . $controller_url);
        }
        require_once $controller_url;
        
        $controller = strtolower($controller) . "_controller";
        
        if (!class_exists($this->namespace.$controller)) {
            throw new \InvalidArgumentException("The action controller '$controller' has not been defined.");
        }
        $this->controller = $controller;
        return $this;
    }

    /**
     * Set action to call
     *
     * @param string $action
     * @return $this
     * @throws \ReflectionException
     */
    public function set_action($action) {
        $reflector = new \ReflectionClass($this->namespace . $this->controller);
        if (!$reflector->hasMethod($action)) {
            throw new \InvalidArgumentException(
                    "The controller action '$action' has been not defined.");
        }
        $this->action = $action;
        return $this;
    }

    /**
     * Set params from $_GET and $_POST
     */
    public function set_params() {
        $this->params = array_merge($_GET, $_POST);
    }

    /**
     * Execute the controller action
     */
    public function execute() {
        $class = $this->namespace . $this->controller;
        $controller = new $class($this->params);
        return $controller->execute();
    }
}

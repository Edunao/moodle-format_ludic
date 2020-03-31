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
 * Front controller interface.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

interface front_controller_interface {

    /**
     * @return mixed
     */
    public function execute();

    /**
     * Set controller
     *
     * @param callable $controller
     * @return mixed
     */
    public function set_controller($controller);

    /**
     * Set action to call
     *
     * @param callable $action
     * @return mixed
     */
    public function set_action($action);

    /**
     * Set params
     *
     * @return mixed
     */
    public function set_params();
}
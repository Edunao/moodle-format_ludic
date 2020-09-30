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
 * Main ajax file for ludic format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');

$courseid = required_param('courseid', PARAM_INT);

// Check capabilities.
require_login($courseid);

// The front controller will execute the action defined in post/get parameter with the controller defined in post/get parameter.
require_once(__DIR__ . '/../classes/controllers/front_controller.php');
$frontcontroller = new \format_ludic\front_controller();

// Call the controller method and print the result.
echo $frontcontroller->execute();
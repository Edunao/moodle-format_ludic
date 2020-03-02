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
 * Settings for the ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // Weight settings.
    $name        = 'format_ludic/weight';
    $title       = get_string('setting-weight-title', 'format_ludic');
    $description = get_string('setting-weight-description', 'format_ludic');
    $default     = '0, 100, 200, 300, 400, 500, 600, 700, 800, 900, 1000';
    $setting     = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW, 50);
    $settings->add($setting);
}



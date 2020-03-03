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
 * Course module controller class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/ludic/lib.php');

class coursemodule_controller extends controller_base {

    /**
     * Execute an action
     *
     * @return mixed
     * @throws \moodle_exception
     */
    public function execute() {
        $action = $this->get_param('action');
        switch ($action) {
            case 'get_properties' :
                $cmid = $this->get_param('id', PARAM_INT);
                return $this->get_properties($cmid);
            case 'get_children' :
                $cmid = $this->get_param('id', PARAM_INT);
                return $this->get_children($cmid);
            // Default case if no parameter is necessary.
            default :
                return $this->$action();
        }
    }

    public function get_parents() {
        return 'coursemodule::get_parents()';
    }

    public function get_children($cmid) {
        return false;
        return 'coursemodule::get_children('.$cmid.')';
    }

    public function get_properties($cmid) {
        return 'coursemodule::get_properties('.$cmid.')';
    }
}

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
 * Skin controller class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/ludic/lib.php');

class skin_controller extends controller_base {

    /**
     * Execute an action.
     *
     * @return false|string
     * @throws \moodle_exception
     */
    public function execute() {
        $action = $this->get_param('action');
        switch ($action) {
            case 'get_description' :
                $skinid = $this->get_param('id', PARAM_INT);
                return $this->get_description($skinid);
            case 'get_section_skin_selector' :
                $selectedskinid = $this->get_param('selectedid');
                return $this->get_section_skin_selector($selectedskinid);
            case 'get_course_module_skin_selector' :
                $cmid           = $this->get_param('itemid', PARAM_INT);
                $selectedskinid = $this->get_param('selectedid');
                return $this->get_course_module_skin_selector($cmid, $selectedskinid);
            // Default case if no parameter is necessary.
            default :
                return $this->$action();
        }
    }

    /**
     * Get course modules skins for selection in popup.
     *
     * @param $cmid
     * @param $selectedskinid
     * @return mixed
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_course_module_skin_selector($cmid, $selectedskinid) {
        global $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');
        $skins    = $this->contexthelper->get_available_course_module_skins($cmid);

        $content = '';
        foreach ($skins as $skin) {
            if (!empty($selectedskinid) && $selectedskinid == $skin->id) {
                $skin->selected = true;
            }
            $skin->propertiesaction = 'get_description';

            $content .= $renderer->render_skin($skin);
        }

        return $renderer->render_container_items('coursemodule-skin', $content);
    }

    /**
     * Get section skins for selection in popup.
     *
     * @param $selectedskinid
     * @return string
     */
    public function get_section_skin_selector($selectedskinid) {
        global $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');
        $skins    = $this->contexthelper->get_section_skins();

        $content = '';
        foreach ($skins as $skin) {
            if (!empty($selectedskinid) && $selectedskinid == $skin->id) {
                $skin->selected = true;
            }
            $skin->propertiesaction = 'get_description';
            $content                .= $renderer->render_skin($skin);
        }

        return $renderer->render_container_items('section-skin', $content);
    }

    /**
     * Get skin description.
     *
     * @param $skinid
     * @return string
     */
    public function get_description($skinid) {
        $skin = skin::get_by_id($skinid);
        return $skin->description;
    }

}

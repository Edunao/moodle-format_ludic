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
 * Skin controller class
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
     * Execute an action
     *
     * @return bool|int|void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public function execute() {
        $action = $this->get_param('action');
        switch ($action) {
            case 'get_properties' :
                $skinid = $this->get_param('id', PARAM_INT);
                return $this->get_properties($skinid);
            case 'get_children' :
                $skinid = $this->get_param('id', PARAM_INT);
                return $this->get_children($skinid);
            case 'get_cm_skin_selector' :
                return $this->get_cm_skin_selector();
            case 'get_section_skin_selector' :
                return $this->get_section_skin_selector();
            // Default case if no parameter is necessary.
            default :
                return $this->$action();
        }
    }

    public function get_cm_skin_selector() {
        global $PAGE;
        $this->set_context();
        $renderer = $PAGE->get_renderer('format_ludic');
        // TODO $skins = $this->get_cm_skins();.
        $title = 'CM SKIN SELECTION';
        $content = $renderer->render_from_template('format_ludic/test', []);
        $popup = new \format_ludic_popup($title, $content);
        $json = ['html' => $renderer->render_popup($popup)];
        return json_encode($json);
    }
    public function get_section_skin_selector() {
        global $PAGE;
        $this->set_context();
        $renderer = $PAGE->get_renderer('format_ludic');
        // TODO $skins = $this->get_cm_skins();.
        $title = 'SECTION SKIN SELECTION';
        $content = $renderer->render_from_template('format_ludic/test_skin_selection', []);
        $popup = new \format_ludic_popup('popup_skin_section_selector', $title, $content);
        $json = ['html' => $renderer->render_popup($popup)];
        return json_encode($json);
    }

    public function get_children($skinid) {
        return 'NO CHILDREN FOR SKIN => ' . $skinid;
    }

    public function get_properties($skinid) {
        return 'SKIN ' . $skinid . ' PROPERTIES';
    }

}

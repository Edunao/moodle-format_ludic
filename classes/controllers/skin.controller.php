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
            case 'get_section_skin_selector' :
                $selectedskinid = $this->get_param('selectedid');
                return $this->get_section_skin_selector($selectedskinid);
            case 'get_course_module_skin_selector' :
                $cmid           = $this->get_param('itemid', PARAM_INT);
                $selectedskinid = $this->get_param('selectedid');
                return $this->get_course_module_skin_selector($cmid, $selectedskinid);
            case 'get_course_skins_list':
                return $this->get_course_skins_list();
            case 'get_skin_properties':
                $skinid = $this->get_param('id');
                return $this->get_skin_properties($skinid);
            case 'get_skin_types':
                $skinid = $this->get_param('id');
                return $this->get_skin_types($skinid);
            case 'get_skin_types_form':
                $skintypeid = $this->get_param('id');
                return $this->get_skin_types_form($skintypeid);
            case 'get_add_skin_form';
                $skintypeid =  $this->get_param('id');
                return $this->get_add_skin_form($skintypeid);
            case 'validate_form' :
                $skinid = $this->get_param('id');
                $courseid = $this->get_param('courseid', PARAM_INT);
                $data = $this->get_param('data');
                return $this->validate_form($courseid, $skinid, $data);
            case 'delete_skin':
                $skinid = $this->get_param('id', PARAM_INT);
                $courseid = $this->get_param('courseid', PARAM_INT);
                return $this->delete_skin($courseid, $skinid);
            // Avatar action
            case 'avatar_buy_item' :
                $sectionid = $this->get_param('sectionid');
                $slotname  = $this->get_param('slotname');
                $itemname  = $this->get_param('itemname');
                return $this->avatar_buy_item($sectionid, $slotname, $itemname);
            case 'avatar_toggle_item':
                $sectionid = $this->get_param('sectionid');
                $slotname  = $this->get_param('slotname');
                $itemname  = $this->get_param('itemname');
                return $this->avatar_toggle_item($sectionid, $slotname, $itemname);
            default :
                // Default case if the only parameter is id.
                $id = $this->get_param('id', PARAM_INT);
                return $this->$action($id);
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
     * @throws \moodle_exception
     */
    public function get_course_module_skin_selector($cmid, $selectedskinid) {
        global $PAGE;

        // Get data.
        $renderer     = $PAGE->get_renderer('format_ludic');
        $coursemodule = $this->contexthelper->get_course_module_by_id($cmid);
        $skins        = $coursemodule->get_available_skins();
        $title        = 'Skins';

        // Render skins.
        $content = '';
        foreach ($skins as $skin) {
            if (!empty($selectedskinid) && $selectedskinid == $skin->id) {
                $skin->selected = true;
            }

            $content .= $renderer->render_skin($skin);
        }

        // Return skins html in container.
        return $renderer->render_container_items('coursemodule-skin', $this->contexthelper->is_editing(), $content, '', '', $title);
    }

    /**
     * Get section skins for selection in popup.
     *
     * @param $selectedskinid
     * @return string
     */
    public function get_section_skin_selector($selectedskinid) {
        global $PAGE;

        // Get data.
        $renderer = $PAGE->get_renderer('format_ludic');
        $skins    = $this->contexthelper->get_section_skins();

        // Render skins.
        $content = '';
        foreach ($skins as $skin) {
            if (!empty($selectedskinid) && $selectedskinid == $skin->id) {
                $skin->selected = true;
            }
            $content .= $renderer->render_skin($skin);
        }

        // Return skins html in container.
        return $renderer->render_container_items('section-skin', $this->contexthelper->is_editing(), $content);
    }

    /**
     * Get skin description.
     *
     * @param $skinid
     * @return string
     * @throws \coding_exception
     */
    public function get_description($skinid) {
        // Get skin.
        $skin = skin::get_by_id($skinid);

        // Return his description.
        return $skin->description;
    }

    public function get_course_skins_list() {
        global $PAGE;

        // Get data.
        $renderer = $PAGE->get_renderer('format_ludic');
        $skins    = $this->contexthelper->get_skins_format();

        return $renderer->render_skins_list($skins);
    }

    public function get_skin_properties($skinid) {

        $skins = $this->contexthelper->get_skins_format();
        if (!array_key_exists($skinid, $skins)) {
            return false;
        }

        $skin = $skins[$skinid];

        return $skin->description;
    }

    public function get_skin_types($skinid) {
        global $PAGE;

        $allskinstypes = $this->contexthelper->get_skins();
        $skintypes     = [];
        foreach ($allskinstypes as $skintype) {
            if ($skintype->skinid == $skinid) {
                $skintypes[] = $skintype;
            }
        }

        $renderer = $PAGE->get_renderer('format_ludic');

        return $renderer->render_skin_skin_types_list($skinid, $skintypes);
    }

    public function get_skin_types_form($skintypeid) {
        global $PAGE, $COURSE;
        $renderer = $PAGE->get_renderer('format_ludic');

        $skintype = $this->contexthelper->get_skin_by_id($skintypeid);
        $output = '';

        $output .= $renderer->render_edit_skins_form($COURSE->id, $skintype);

        return $output;
    }

    public function get_add_skin_form($skintypeid){
        global $COURSE, $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');

        $skintypes = $this->contexthelper->get_skins_format();

        if(!array_key_exists($skintypeid, $skintypes)){
            return false;
        }

        $output = '';

        $output .= $renderer->render_edit_skins_form($COURSE->id, $skintypes[$skintypeid]);

        return $output;
    }

    /**
     * Validate form.
     * If everything is valid => update and return a success message.
     * Else does not update and return an error message.
     *
     * @param $cmid
     * @param $data
     * @return false|string
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function validate_form($courseid, $skinid, $data) {
        global $DB, $USER, $COURSE;

        // if skin is not int, it's new skin (we have skin type id)
        $newskin = true;
        if(!is_numeric($skinid)){
            //print_object('nouveau skin !');
            $skin = $this->contexthelper->get_skins_format()[$skinid];
        }else{
            $skin = $this->contexthelper->get_skin_by_id($skinid);
            $newskin = false;
            //print_object($this->contexthelper->skin_is_used($skinid));
        }

        // Create form.
        //$form = new edit_skins_form($courseid, $skin);

        //print_object($data);

        $skinsettings = $skin->get_editor_config();

        //print_object($skinsettings);


        // Prepare base skin data
        $skindata = [
            'id' => !$newskin ? $skin->id : $this->contexthelper->get_next_skinid(),
            'skinid'      => $skin->skinid,
            'location'    => $skin->location,
            'type'        => $skin->type,
            'properties'  => []
        ];

        //print_object($skindata);

        foreach ($data as $element){
            $formname = $element['name'];
            $formvalue = $element['value'];

            $explodedname = explode('_', $formname);
            if(count($explodedname) == 1){
                // First layers
                if(array_key_exists($formname, $skinsettings['settings'])){
                    $skindata[$formname] = $formvalue;
                }else if(substr($element['name'], -4) == '-alt' || substr($element['name'], -4) == '-img'){
                        // Image attribute
                        $realname = substr($element['name'], 0, -4);
                        $imgname = substr($element['name'], -4) == '-alt' ? 'imgalt' : 'imgitemid';
                        if(!array_key_exists($realname, $skindata['properties'])){
                            $skindata['properties'][$realname] = new \stdClass();
                        }

                        $skindata['properties'][$realname]->$imgname = $element['value'];

                        if($imgname == 'imgitemid'){
                            $fileid = $this->contexthelper->fileapi->create_skin_file_from_draft($this->contexthelper->get_course_id(),
                                $skindata['skinid'], $skindata['id'], $realname, $element['value']);
                            if(!$fileid){
                                // Draft not found, use previous image
                                if(!$newskin){
                                    $previousproperty = $skin->get_properties($realname);
                                    if(isset($previousproperty->imgfileid)){
                                        $fileid = $previousproperty->imgfileid;
                                    }
                                }
                            }
                            $skindata['properties'][$realname]->imgsrc = $fileid;
                        }
                }
            }
        }


        $skindata['properties'] = (object) $skindata['properties'];

        // TODO : check if all required settings are set
        //print_object('------------');
        //print_object($skindata);
        $skindata = (object) $skindata;
        $this->contexthelper->add_new_skin($skindata);



        $return = array(
            'success' => 1,
            'value'   => 'ok',
            'skintype' => $skindata->skinid,
            'skinid' => $skindata->id,
        );
        return json_encode($return);
    }

    public function delete_skin($courseid, $skinid){
        // Check if skin exist and load it
        //print_object($this->contexthelper->get_skins_config());

        // Check if skin is used => if used, return errors with number of activities


        // If not used, purge course format option and file table
    }

    /*********************************
     * Avatar actions
     */

    public function avatar_buy_item($sectionid, $slotname, $itemname) {
        $section = $this->contexthelper->get_section_by_id($sectionid);
        if ($section->skin->type != 'avatar') {
            return false;
        }
        $section->skin->buy_item($slotname, $itemname);

        global $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');
        return $renderer->render_skinned_tile($section->skin);
    }

    public function avatar_toggle_item($sectionid, $slotname, $itemname) {
        $section = $this->contexthelper->get_section_by_id($sectionid);
        if ($section->skin->type != 'avatar') {
            return false;
        }
        $section->skin->toggle_item($slotname, $itemname);

        global $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');
        return $renderer->render_skinned_tile($section->skin);
    }
}

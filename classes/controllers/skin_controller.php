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

require_once(__DIR__ . '/controller_base.php');

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

            case 'get_course_module_skin_selector_section_zero' :
                $cmid           = $this->get_param('itemid', PARAM_INT);
                $selectedskinid = $this->get_param('selectedid');
                return $this->get_course_module_skin_selector_section_zero($cmid, $selectedskinid);

            case 'get_course_module_skin_selector_main' :
                $cmid           = $this->get_param('itemid', PARAM_INT);
                $selectedskinid = $this->get_param('selectedid');
                return $this->get_course_module_skin_selector_main($cmid, $selectedskinid);

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
                $skintypeid = $this->get_param('id');
                return $this->get_add_skin_form($skintypeid);

            case 'validate_form' :
                $skinid   = $this->get_param('id');
                $courseid = $this->get_param('courseid', PARAM_INT);
                $data     = $this->get_param('data');
                return $this->validate_form($courseid, $skinid, $data);

            case 'delete_skin':
                $skinid   = $this->get_param('id', PARAM_INT);
                $courseid = $this->get_param('courseid', PARAM_INT);
                return $this->delete_skin($courseid, $skinid);

            // Avatar action.
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
    private function get_course_module_skin_selector($cmid, $selectedskinid, $skins) {
        global $PAGE;
        // Get data.
        $renderer = $PAGE->get_renderer('format_ludic');
        $title    = 'Skins';

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

    public function get_course_module_skin_selector_main($cmid, $selectedskinid) {
        $skins = skin_manager::get_instance()->get_all_course_module_skins();
        return $this->get_course_module_skin_selector($cmid, $selectedskinid, $skins);
    }

    public function get_course_module_skin_selector_section_zero($cmid, $selectedskinid) {
        $skins = skin_manager::get_instance()->get_section0_course_module_skins();
        return $this->get_course_module_skin_selector($cmid, $selectedskinid, $skins);
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
        $skins    = skin_manager::get_instance()->get_all_section_skins();

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
        $skin = skin_manager::get_instance()->get_by_id($skinid);

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
        $output   = '';

        $output .= $renderer->render_edit_skins_form($COURSE->id, $skintype);

        return $output;
    }

    public function get_add_skin_form($skintypeid) {
        global $COURSE, $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');

        $skintypes = $this->contexthelper->get_skins_format();

        if (!array_key_exists($skintypeid, $skintypes)) {
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

        // If skin is not int, it's new skin (we have skin type id).
        $newskin = true;
        if (!is_numeric($skinid)) {
            $skin = $this->contexthelper->get_skins_format()[$skinid];
        } else {
            $skin    = $this->contexthelper->get_skin_by_id($skinid);
            $newskin = false;
        }

        $skinsettings = $skin->get_editor_config();

        // Prepare base skin data.
        $skindata = [
            'id'         => !$newskin ? $skin->id : $this->contexthelper->get_next_skinid(),
            'skinid'     => $skin->skinid,
            'domain'     => $skin->domain,
            'type'       => $skin->type,
            'properties' => []
        ];

        // New index for each group.
        $indexesmap  = [];
        $groupsindex = [];

        foreach ($data as $element) {
            $formname  = $element['name'];
            $formvalue = $element['value'];

            $explodedname = explode('_', $formname);

            if (count($explodedname) == 1) {
                // First layers.
                if (array_key_exists($formname, $skinsettings['settings'])) {
                    $skindata[$formname] = $formvalue;
                } else if (substr($element['name'], -4) == '-alt' || substr($element['name'], -4) == '-img') {
                    // Image attribute.
                    $realname = substr($element['name'], 0, -4);
                    $imgname  = substr($element['name'], -4) == '-alt' ? 'imgalt' : 'imgitemid';
                    if (!array_key_exists($realname, $skindata['properties'])) {
                        $skindata['properties'][$realname] = new \stdClass();
                    }

                    $skindata['properties'][$realname]->$imgname = $element['value'];

                    if ($imgname == 'imgitemid') {
                        $fileid = $this->contexthelper->fileapi->create_skin_file_from_draft(
                            $this->contexthelper->get_course_id(),
                            $skindata['skinid'],
                            $skindata['id'],
                            $realname,
                            $element['value']
                        );
                        if (!$fileid) {
                            // Draft not found, use previous image.
                            if (!$newskin) {
                                $previousproperty = $skin->get_properties($realname);
                                if (isset($previousproperty->imgfileid)) {
                                    $fileid = $previousproperty->imgfileid;
                                }
                            }
                        }
                        $skindata['properties'][$realname]->imgsrc = $fileid;
                    }
                } else if (array_key_exists($formname, $skinsettings['properties'])) {
                    $skindata['properties'][$formname] = $formvalue;
                }
            } else {
                $groupname = $explodedname[0];
                // Check if group name exist in settings.
                if (!array_key_exists($groupname, $skinsettings['properties'])) {
                    continue;
                }

                if (!array_key_exists($groupname, $groupsindex)) {
                    $groupsindex[$groupname] = 0;
                }

                $attribute = $explodedname[1];
                if (!array_key_exists($attribute, $skinsettings['properties'][$groupname])) {
                    continue;
                }

                $other = $explodedname[2];
                // Ignored false data.
                if (strpos($other, 'empty') === 0) {
                    continue;
                }

                $currentindex = is_numeric($other) ? $other : substr($other, 0, -4);
                if (!isset($indexesmap[$groupname][$currentindex])) {
                    $indexesmap[$groupname][$currentindex] = $groupsindex[$groupname];
                    $groupsindex[$groupname]++;
                }
                $newindex = $indexesmap[$groupname][$currentindex];
                if (!isset($skindata['properties'][$groupname][$newindex])) {
                    $skindata['properties'][$groupname][$newindex] = new \stdClass();
                }

                // If segment is -alt or -img, particular treatment.
                if (substr($other, -4) == '-alt' || substr($other, -4) == '-img') {

                    $imgname = substr($element['name'], -4) == '-alt' ? 'imgalt' : 'imgitemid';
                    if (!isset($skindata['properties'][$groupname][$newindex]->$attribute)) {
                        $skindata['properties'][$groupname][$newindex]->$attribute = new \stdClass();
                    }

                    $skindata['properties'][$groupname][$newindex]->$attribute->$imgname = $element['value'];

                    if ($imgname == 'imgitemid') {
                        $fileid = $this->contexthelper->fileapi->create_skin_file_from_draft(
                            $this->contexthelper->get_course_id(),
                            $skindata['skinid'],
                            $skindata['id'],
                            $attribute,
                            $element['value']
                        );

                        if (!$fileid) {
                            // Draft not found, use previous image.
                            if (!$newskin) {
                                $groupproperty = $skin->get_properties($groupname);
                                if (isset($groupproperty[$currentindex]->$attribute->imgfileid)) {
                                    $fileid = $groupproperty[$currentindex]->$attribute->imgfileid;
                                }
                            }

                        }

                        $skindata['properties'][$groupname][$newindex]->$attribute->imgsrc = $fileid;
                    }
                } else {
                    $skindata['properties'][$groupname][$newindex][$attribute] = $formvalue;
                }
            }
        }

        $skindata['properties'] = (object) $skindata['properties'];

        // TODO : check if all required settings are set.

        $skindata = (object) $skindata;

        $return = array(
            'success'  => 1,
            'value'    => 'ok',
            'skintype' => $skindata->skinid,
            'skinid'   => $skindata->id,
        );
        return json_encode($return);
    }

    public function delete_skin($courseid, $skinid) {
        if ($this->contexthelper->skin_is_used($skinid)) {
            // Check if skin is used => if used, return errors with number of activities.
            return json_encode(array(
                'success' => 0,
                'value'   => 'Skin is used !'

            ));
        }

        $skin = $this->contexthelper->get_skin_by_id($skinid);
        $this->contexthelper->delete_skin($skinid);

        $return = array(
            'success'  => 1,
            'value'    => 'ok',
            'skintype' => $skin->skinid,
        );
        return json_encode($return);
    }

    /*********************************
     * Avatar actions
     */

    public function avatar_buy_item($sectionid, $slotname, $itemname) {
	$this->contexthelper->set_viewmode('item-buy');
        $section = $this->contexthelper->get_section_by_id($sectionid);
        if ($section->skin->get_full_typename() != 'section-avatar') {
            return false;
        }
        $section->skin->execute_special_action((object) ['type'   => 'buy_item',
                                                         'slotname' => $slotname,
                                                         'itemname' => $itemname
        ]);

        global $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');
        return $renderer->render_skinned_tile($section->skin);
    }

    public function avatar_toggle_item($sectionid, $slotname, $itemname) {
	$this->contexthelper->set_viewmode('item-toggle');
        $section = $this->contexthelper->get_section_by_id($sectionid);
        if ($section->skin->get_full_typename() != 'section-avatar') {
            return false;
        }
        $section->skin->execute_special_action((object) ['type'   => 'toggle_item',
                                                         'slotname' => $slotname,
                                                         'itemname' => $itemname
        ]);

        global $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');
        return $renderer->render_skinned_tile($section->skin);
    }
}

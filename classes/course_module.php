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
 * Ludic course module class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

class course_module extends model {

    public $name;
    public $order;
    public $cminfo;
    public $section;
    public $sectionid;
    public $accessible;

    /**
     * course_module constructor.
     *
     * @param \cm_info $cminfo
     * @throws \dml_exception
     */
    public function __construct(\cm_info $cminfo) {
        parent::__construct($cminfo);
        $dataapi         = $this->contexthelper->get_data_api();
        $this->sectionid = $cminfo->section;
        $this->section   = $dataapi->get_section_by_id($this->sectionid);
        $this->name   = $cminfo->get_formatted_name();
        $this->cminfo = $cminfo;
    }

    /**
     * Move a course module to another section.
     *
     * @param $sectionid
     * @param null $beforeid
     * @return int
     * @throws \dml_exception
     */
    public function move_to_section($sectionid, $beforeid = null) {
        $dataapi = $this->contexthelper->get_data_api();
        $section = $dataapi->get_section_by_id($sectionid);
        if ($sectionid == $this->sectionid) {
            return $this->accessible;
        }
        $this->section = $section;
        $this->sectionid = $sectionid;
        $this->accessible = moveto_module($this->cminfo, $section->sectioninfo, $beforeid);
        return $this->accessible;
    }

    /**
     * Move a course module after a course module on the same section.
     *
     * @param $cmidtomove
     * @param $aftercmid
     * @return bool
     */
    public function move_on_section($cmidtomove, $aftercmid) {
        $sequence    = $this->section->sequence;
        $newsequence = [];
        foreach ($sequence as $key => $id) {
            if ($id != $cmidtomove) {
                $newsequence[] = $id;
            }
            if ($id == $aftercmid) {
                $newsequence[] = $cmidtomove;
            }
        }
        return $this->section->update_sequence($newsequence);
    }

}
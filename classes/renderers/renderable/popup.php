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
 * Popup for ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class format_ludic_popup implements renderable {

    public $popupid;
    public $title;
    public $content;
    public $headericon;

    public function __construct($id, $title = '', $content = '', $headericon = null) {
        $this->popupid = $id;
        $this->title   = $title;
        $this->content = $content;

        // If the header icon is defined, we make sure that there is no missing data.
        if ($headericon) {
            if (!isset($headericon['imgsrc'])) {
                $headericon['imgsrc'] = 'https://picsum.photos/80';
            }
            if (!isset($headericon['imgalt'])) {
                $headericon['imgalt'] = '';
            }
        }
        $this->headericon = $headericon;
    }

}
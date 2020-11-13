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
 * Tab set for forms for ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class format_ludic_form_tabs implements renderable {

    public $tabs;

    /**
     * format_ludic_form_tabs constructor.
     *
     * @param string $tabs
     */
    public function __construct($tabs) {
        $tabvalues = array_values($tabs);

        $this->tabs = [];
        foreach($tabvalues as $idx => $tab) {
            $newtab             = new \stdClass();
            $newtab->tabid      = $idx;
            $newtab->body       = $tab->body;
            $newtab->checked    = ($idx == 0) ? 'checked=1' : '';
            $newtab->menutabs   = [];
            foreach($tabvalues as $menuidx => $menutab) {
                $newmenutab                 = new \stdClass();
                $newmenutab->menutabid      = $menuidx;
                $newmenutab->menutabname    = $menutab->title;
                $newmenutab->manutabclasses = ($menuidx == $idx) ? 'selected' : 'unselected';
                $newtab->menutabs[]         = $newmenutab;
            }
            $this->tabs[] = $newtab;
        }
    }

}
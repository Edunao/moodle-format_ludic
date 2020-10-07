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
 * Buttons for ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class format_ludic_buttons implements renderable {

    public $buttons;
    public $itemid;
    public $itemtype;
    public $selectorid;

    /**
     * format_ludic_buttons constructor.
     *
     * @param $buttons
     * @param null $itemid
     * @param null $itemtype
     * @throws coding_exception
     */
    public function __construct($buttons, $itemid = null, $itemtype = null) {
        $this->itemid     = $itemid;
        $this->itemtype   = $itemtype;
        $this->selectorid = !empty($itemid) && !empty($itemtype) ? 'buttons-' . $itemtype . '-' . $itemid : null;

        // Foreach button and sub button, set order and name.
        foreach ($buttons as $key => $button) {

            // Button.
            $button['order'] = isset($button['order']) ? $button['order'] : $key + 1;
            $button['name']  = get_string($button['identifier'], 'format_ludic');

            // Prepare button attributes.
            $button['attributesstr'] = '';
            if (isset($button['attributes'])) {
                foreach ($button['attributes'] as $name => $value) {
                    $button['attributesstr'] .= ' ' . $name . '="' . $value . '" ';
                }
            }

            // Sub buttons.
            if (isset($button['subbuttons']) && !empty($button['subbuttons'])) {
                foreach ($button['subbuttons'] as $subkey => $subbutton) {
                    $subbutton['order']            = isset($subbutton['order']) ? $subbutton['order'] : $subkey + 1;
                    $subbutton['name']             = get_string($subbutton['identifier'], 'format_ludic');
                    $button['subbuttons'][$subkey] = $subbutton;
                }
            }

            // Set button and sub button with order and name.
            $buttons[$key] = $button;

        }

        $this->buttons = $buttons;
    }

}
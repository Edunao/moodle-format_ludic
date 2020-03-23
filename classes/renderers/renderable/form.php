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
 * Form for ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class format_ludic_form implements renderable {

    public $id;
    public $action;
    public $method;
    public $itemtype;
    public $itemid;
    public $content;

    /**
     * format_ludic_form constructor.
     *
     * @param \format_ludic\form $form
     */
    public function __construct(\format_ludic\form $form) {
        $this->id       = 'ludic-form-' . $form->type . '-' . $form->id;
        $this->action   = '';
        $this->method   = 'post';
        $this->itemid   = $form->id;
        $this->itemtype = $form->type;
        $this->content  = $form->content;
    }
}
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
 * This file contains main class for the course format Ludic form
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

abstract class form {

    public $id;
    public $type;
    public $content;
    public $object;

    /**
     * Context helper
     *
     * @var context_helper
     */
    protected $contexthelper;

    /**
     * @var form_element[]
     */
    protected $elements;

    public function __construct($type, $id = null) {
        global $PAGE;
        $this->id            = $id;
        $this->type          = $type;
        $this->content       = '';
        $this->contexthelper = context_helper::get_instance($PAGE);
        $this->elements      = $this->get_definition();
    }

    public function add_element(form_element $element) {
        $this->elements[] = $element;
    }

    public abstract function get_definition();

    public function render() {
        global $PAGE;
        $elements = $this->elements;
        foreach ($elements as $element) {
            $this->content .= $element->render();
        }
        $renderer = $PAGE->get_renderer('format_ludic');
        return $renderer->render_form($this);
    }

}
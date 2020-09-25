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
 *
 *
 * @package    TODO
 * @subpackage TODO
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     Céline Hernandez <celine@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class format_ludic_edit_ludic_config extends moodleform{

    public function definition() {
        $mform      = $this->_form;

        // Courseid
        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

        // Ludic images
        $mform->addElement('filemanager', 'ludicimages', get_string('edit-skin-images', 'format_ludic'), null, array('accepted_types' => array('image'),  'subdirs' => 0));

        // Ludic config
        $mform->addElement('textarea', 'ludicconfig', get_string('edit-skin-config', 'format_ludic'), 'rows="50"');

        // Save and cancel buttons
        $buttonarray   = array();
        $buttonarray[] = &$mform->createElement('submit', 'saveanddisplay', get_string('savechangesanddisplay'));
        $buttonarray[] = &$mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}
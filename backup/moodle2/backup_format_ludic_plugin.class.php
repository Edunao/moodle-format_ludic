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

defined('MOODLE_INTERNAL') || die();

/**
 * Specialised backup for Ludic course format.
 *
 * @package    format_ludic
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     adrien <adrien@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class backup_format_ludic_plugin extends backup_format_plugin {

    /**
     * Returns the format information to attach to course element
     */
    protected function define_course_plugin_structure() {

        // Define the virtual plugin element with the condition to fulfill.
        $plugin = $this->get_plugin_element(null, '/course/format', 'ludic');


        $pluginwrapper = new backup_nested_element('images',  array('id'), null);

        // Define sources.
        $pluginwrapper->set_source_table('course', array('id' => backup::VAR_COURSEID));

        $pluginwrapper->annotate_files('format_ludic', 'ludicimages', null);

        $plugin->add_child($pluginwrapper);

        // Don't need to annotate ids nor files.
        return $plugin;
    }


    protected function define_section_plugin_structure() {

        // Define the virtual plugin element with the condition to fulfill.
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'ludic');

        // Create one standard named plugin element (the visible container).
        // The sectionid and courseid not required as populated on restore.
        $pluginwrapper = new backup_nested_element($this->get_recommended_name(), null, array('skinid'));

        // Connect the visible container ASAP.
        $plugin->add_child($pluginwrapper);

        // Set source to populate the data.
        $pluginwrapper->set_source_table('format_ludic_cs', array(
                'sectionid' => backup::VAR_SECTIONID,
                'courseid'  => backup::VAR_COURSEID
        ));

        // Don't need to annotate ids nor files.
        return $plugin;
    }

    protected function define_module_plugin_structure() {

        // Define the virtual plugin element with the condition to fulfill.
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'ludic');

        // Create one standard named plugin element (the visible container).
        // The sectionid and courseid not required as populated on restore.
        $pluginwrapper = new backup_nested_element($this->get_recommended_name(), null, array(
                'skinid',
                'weight',
                'access'
        ));

        // Connect the visible container ASAP.
        $plugin->add_child($pluginwrapper);

        // Set source to populate the data.
        $pluginwrapper->set_source_table('format_ludic_cm', array(
                'cmid'     => backup::VAR_MODID,
                'courseid' => backup::VAR_COURSEID
        ));

        // Don't need to annotate ids nor files.
        return $plugin;
    }


}
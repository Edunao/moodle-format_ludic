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
 * Specialised restore for Ludic course format.
 *
 * @package    format_ludic
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     adrien <adrien@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class restore_format_ludic_plugin
 */
class restore_format_ludic_plugin extends restore_format_plugin {

    /** @var int  */
    protected $originalnumsections = 0;

    /**
     * Checks if backup file was made on Moodle before 3.3 and we should respect the 'numsections'
     * and potential "orphaned" sections in the end of the course.
     *
     * @return bool Need to restore numsections.
     */
    protected function need_restore_numsections() {
        $backupinfo = $this->step->get_task()->get_info();
        $backuprelease = $backupinfo->backup_release;
        $prethreethree = version_compare($backuprelease, '3.3', 'lt');
        if ($prethreethree) {
            // Pre version 3.3 so, yes!
            return true;
        }
        $data = $this->connectionpoint->get_data();
        return (isset($data['tags']['numsections']));
    }


    /**
     * Returns the paths to be handled by the plugin at section level
     */
    protected function define_course_plugin_structure() {

        $paths = array();

        $this->add_related_files('format_ludic', 'ludicimages', null);

        return $paths; // And we return the interesting paths.
    }



    /**
     * Returns the paths to be handled by the plugin at section level
     */
    protected function define_section_plugin_structure() {

        $paths = array();

        // Add own format stuff.
        $elename = 'ludicsection';

        $elepath = $this->get_pathfor('/');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths; // And we return the interesting paths.
    }

    /**
     * @param $data
     */
    public function process_ludicsection($data) {
        global $DB;

        $data = (object) $data;

        $data->courseid  = $this->task->get_courseid();
        $data->sectionid = $this->task->get_sectionid();

        if (!$DB->insert_record('format_ludic_cs', $data, true)) {
            throw new moodle_exception('invalidrecordid', 'format_ludic_cs', '',
                    'A configuration already exists for the section ' . $data->sectionid);
        }
    }


    /**
     * Returns the paths to be handled by the plugin at section level
     */
    protected function define_module_plugin_structure() {

        $paths = array();

        // Add own format stuff.
        $elename = 'ludicmodule';

        $elepath = $this->get_pathfor('/');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths; // And we return the interesting paths.
    }

    /**
     * @param $data
     */
    public function process_ludicmodule($data) {
        global $DB;

        $data = (object) $data;

        $data->courseid  = $this->task->get_courseid();
        $data->cmid      = $this->task->get_moduleid();

        if (!$DB->insert_record('format_ludic_cm', $data, true)) {
            throw new moodle_exception('invalidrecordid', 'format_ludic_cm', '',
                    'A configuration already exists for the course module ' . $data->cmid);
        }
    }
}
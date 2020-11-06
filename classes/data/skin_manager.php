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
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

class skin_manager {
    private static $instance    = null;

    private $ludicconfig        = null;
    private $sectionludicconfig = null;
    private $cmludicconfig      = null;
    private $sectionskins       = [];
    private $cmskins            = [];
    private $skintemplates      = [];

    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_by_id($skinid) {
        return $this->get_section_skin($skinid) ?: $this->get_course_module_skin($skinid);
    }

    public function get_section_skin_by_name($name) {
        $skinsconfig = $this->get_section_ludic_config();
        foreach ($skinsconfig as $skinconfig) {
            if ($skinconfig->skinname == $name) {
                return $this->get_section_skin($skinconfig->id);
            }
        }
    }

    public function get_all_section_skins() {
        $skins = [];
        $config = $this->get_section_ludic_config();
        foreach ($config as $skindef) {
            $skin = $this->get_section_skin($skindef->id);
            $isvisible = true;
            $isvisible = $isvisible && $skin;
            $isvisible = $isvisible && (!isset($skin->visible) || $skin->visible != 0);
            if (!$isvisible) {
                continue;
            }
            $skins[] = $skin;
        }

        return $skins;
    }

    public function get_section_skin($skinid) {
        if (isset($this->sectionskins[$skinid])) {
            return $this->sectionskins[$skinid];
        }
        $config = $this->get_section_skin_config($skinid);
        if (!$config) {
            return false;
        }
        $this->sectionskins[$skinid] = $this->build_section_skin($config);
        return $this->sectionskins[$skinid];
    }

    private function get_section_skin_config($skinid) {
        $container = $this->get_section_ludic_config();
        foreach ($container as $config) {
            if ($config->id == $skinid) {
                return $config;
            }
        }

        return false;
    }

    private function build_section_skin($config) {
        include_once(__DIR__ . '/../models/section_skin_types/' . $config->skintype . '.php');
        $templateclassname = '\format_ludic\\skin_template_section_' . $config->skintype;
        if (!class_exists($templateclassname)) {
            return null;
        }

        return new $templateclassname($config);
    }

    public function get_section_ludic_config() {
        $this->fetch_ludic_config();
        return $this->sectionludicconfig;
    }

    public function get_course_module_skin_by_name($name) {
        $skinsconfig = $this->get_course_module_ludic_config();
        foreach ($skinsconfig as $skinconfig) {
            if ($skinconfig->skinname == $name) {
                return $this->get_course_module_skin($skinconfig->id);
            }
        }
    }

    public function get_section0_course_module_skins() {
        // For section 0 we only allow specific reserved options.
        return [
            $this->get_course_module_skin_by_name('inline'),
            $this->get_course_module_skin_by_name('menubar'),
        ];
    }

    public function get_all_course_module_skins() {
        $skins = [];
        $config = $this->get_course_module_ludic_config();
        foreach ($config as $skindef) {
            $skin = $this->get_course_module_skin($skindef->id);
            $isvisible = true;
            $isvisible = $isvisible && $skin;
            $isvisible = $isvisible && (!isset($skindef->visible) || $skindef->visible != 0);
            if (!$isvisible) {
                continue;
            }
            $skins[] = $skin;
        }

        return $skins;
    }

    public function get_course_module_skin($skinid) {

        if (isset($this->cmskins[$skinid])) {
            return $this->cmskins[$skinid];
        }

        $config = $this->get_course_module_skin_config($skinid);
        if (!$config) {
            return false;
        }

        $this->cmskins[$skinid] = $this->build_course_module_skin($config);
        return $this->cmskins[$skinid];
    }

    private function get_course_module_skin_config($skinid) {
        $container = $this->get_course_module_ludic_config();
        foreach ($container as $config) {
            if ($config->id == $skinid) {
                return $config;
            }
        }

        return false;
    }

    public function get_course_module_ludic_config() {
        $this->fetch_ludic_config();
        return $this->cmludicconfig;
    }

    private function build_course_module_skin($config) {
        include_once(__DIR__ . '/../models/course_module_skin_types/' . $config->skintype . '.php');
        $templateclassname = '\format_ludic\\skin_template_course_module_' . $config->skintype;
        if (!class_exists($templateclassname)) {
            return null;
        }

        return new $templateclassname($config);
    }

    public function get_user_config() {
        $ludicconfig = $this->fetch_raw_ludic_config();
        return $ludicconfig->userskins;
    }

    public function set_user_config($userskins) {
        $ludicconfig = $this->fetch_raw_ludic_config();
        $ludicconfig->userskins = $userskins;
        context_helper::get_instance()->update_course_format_options(['ludic_config' => json_encode($ludicconfig)]);
    }

    public function get_default_user_skin_config() {
        $defaultconfig = $this->get_default_ludic_config();
        return $defaultconfig->userskins;
    }

    private function fetch_ludic_config() {
        // If we have already fetched it then just return it.
        if ($this->sectionludicconfig !== null) {
            return $this->ludicconfig;
        }

        $this->sectionludicconfig = [];
        $this->cmludicconfig      = [];

        $ludicconfig = $this->fetch_raw_ludic_config();
        $rawconfig = array_merge($ludicconfig->systemskins, $ludicconfig->userskins);

        foreach ($rawconfig as $skindef) {
            $isvalid = true;
            $isvalid = $isvalid && $skindef;
            $isvalid = $isvalid && isset($skindef->id);
            $isvalid = $isvalid && isset($skindef->domain);
            $isvalid = $isvalid && isset($skindef->skinname);
            $isvalid = $isvalid && isset($skindef->skintype);

            if (!$isvalid) {
                continue;
            }

            if ($skindef->domain == 'section') {
                $this->sectionludicconfig[$skindef->id] = $skindef;
            } else if ($skindef->domain == 'course_module') {
                $this->cmludicconfig[$skindef->id] = $skindef;
            } else {
                continue;
            }
        }
    }

    /**
     * Get ludic config from course format options.
     *
     * @return array
     */
    private function fetch_raw_ludic_config() {

        // Try fetching from the database.
        $ludicconfig = context_helper::get_instance()->get_course_format_option_by_name('ludic_config');
        $ludicconfig = json_decode($ludicconfig);
        if ($ludicconfig && isset($ludicconfig->systemskins) && isset($ludicconfig->userskins) && $ludicconfig->userskins) {
            $this->ludicconfig = (object) $ludicconfig;
            return $this->ludicconfig;
        }

        // config was not found in the database so fetch the default config and write it to the database.
        $ludicconfig = $this->get_default_ludic_config();
        context_helper::get_instance()->update_course_format_options(['ludic_config' => json_encode($ludicconfig)]);
        return $ludicconfig;
    }

    private function get_default_ludic_config() {
        // Construct the system skin set and default user skin set from files on disk.
        $systemskins = $this->read_system_skin_definition_set(1);
        $userskins   = $this->read_default_user_skin_definition_set(count($systemskins) + 1);

        // Construct the result container.
        $ludicconfig = (object) [
            'systemskins' => $systemskins,
            'userskins'   => $userskins,
            'nextid'      => count($systemskins) + count($userskins) + 1
        ];

        return $ludicconfig;
    }

    private function read_system_skin_definition_set($firstid) {
        $systemskinnames = [
            'system_skin_inline',
            'system_skin_menubar',
            'system_skin_stealth',
        ];
        return $this->read_pre_defined_skin_definition_set($systemskinnames, $firstid);
    }

    private function read_default_user_skin_definition_set($firstid) {
        $defaultskinnames = [
            'default_skin_achievement_page',
            'default_skin_non_ludic_course_module',
            'default_skin_grade_as_score',
            //'default_skin_grade_as_score_steps',
            'default_skin_grade_as_abc',
            'default_skin_grade_as_progress',
            'default_skin_progress_as_stairs',
            'default_skin_progress_as_rocket_story',
            'default_skin_non_ludic_section',
            'default_skin_achievement_medals',
            'default_skin_animal_collection',
            'default_skin_section_score',
            'default_skin_bedroom',
        ];
        return $this->read_pre_defined_skin_definition_set($defaultskinnames, $firstid);
    }

    private function read_pre_defined_skin_definition_set($skinset, $nextid) {
        $result = [];
        foreach ($skinset as $skindefinition) {
            $filename = __DIR__ . '/../../skins/' . $skindefinition . '.json';
            $json     = file_get_contents($filename);
            $record   = $this->prepare_skin_definition((object) json_decode($json), $nextid++);
            if (!$record) {
                continue;
            }
            $result[] = $record;
        }
        return $result;
    }

    private function prepare_skin_definition($record, $id) {
        $isvalid = true;
        $isvalid = $isvalid && $record;
        $isvalid = $isvalid && isset($record->domain);
        $isvalid = $isvalid && isset($record->skinname);
        $isvalid = $isvalid && isset($record->skintype);
        if (!$isvalid) {
            return null;
        }

        $record->id       = $id;
        $record->fullname = $record->domain . '/' . $record->skinname;

        return $record;
    }

    /**
     * Build a new skinned tile based on the skin identified by skinid
     *
     * @param $config
     * @param null $item
     * @return skin|null
     */
    public function skin_course_module($skinid, \format_ludic\course_module $cm) {
        $config   = $this->get_course_module_skin_config($skinid);
        $template = $this->build_course_module_skin($config);

        // Prepare to instantiate the object.
        include_once(__DIR__ . '/../models/course_module_skin_types/' . $config->skintype . '.php');
        $classname = '\format_ludic\\skinned_course_module_' . $config->skintype;
        if (!class_exists($classname)) {
            return null;
        }

        // Instantiate and initialise the object.
        $result = new $classname($template);
        $result->initialise($cm);

        // Return the new object.
        return $result;
    }

    public function get_course_module_default_skin($section, $modname) {
        // look for a course module skin by name
        $isinlineonly = plugin_supports('mod', $modname, FEATURE_NO_VIEW_LINK, false);
        $skinname = $section == 0 ? "menubar" : "default";
        $skinname = $isinlineonly ? "inline" : $skinname;
        $result = $this->get_course_module_skin_by_name($skinname);
        if ($result) {
            return $result;
        }

        // no default skin was found so return the first skin that we find instead
        $ludicconfig = $this->get_course_module_ludic_config();
        $firstskin   = reset($ludicconfig);
        $firstskinid = $firstskin->id;
        $result      = $this->get_course_module_skin($firstskinid);
        return $result;
    }

    /**
     * Build a new skinned tile based on the skin identified by skinid
     *
     * @param $config
     * @param null $item
     * @return skin|null
     */
    public function skin_section($skinid, \format_ludic\section $section) {
        $config   = $this->get_section_skin_config($skinid);
        $template = $this->build_section_skin($config);

        // Prepare to instantiate the object.
        include_once(__DIR__ . '/../models/section_skin_types/' . $config->skintype . '.php');
        $classname = '\format_ludic\\skinned_section_' . $config->skintype;
        if (!class_exists($classname)) {
            return null;
        }

        // Instantiate and initialise the object.
        $result = new $classname($template);
        $result->set_section($section);

        // Return the new object.
        return $result;
    }

    public function get_section_default_skin() {
        // look for a section skin named 'default'
        $result = $this->get_section_skin_by_name("default");
        if ($result) {
            return $result;
        }

        // no default skin was found so return the first skin that we find instead
        $ludicconfig = $this->get_section_ludic_config();
        $firstskin   = reset($ludicconfig);
        $firstskinid = $firstskin->id;
        $result      = $this->get_section_skin($firstskinid);
        return $result;
    }
}

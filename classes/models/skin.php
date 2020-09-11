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
 * Extend this class so that the child inherits the context helper.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

abstract class skin extends model {

    public $location;
    public $type;
    public $title;
    public $description;
    public $steps;
    public $selected;
    public $item;
    public $skinid;
    public $id;


    private $weight  = null;
    private $results = null;
    private $properties;
    private $maincss;

    /**
     * skin constructor.
     *
     * @param $skin
     * @param course_module|section $item
     */
    public function __construct($skin, $item = null) {
        parent::__construct($skin);
        $this->location    = isset($skin->location) ? $skin->location : null;
        $this->type        = isset($skin->type) ? $skin->type : null;
        $this->title       = isset($skin->title) ? $skin->title : null;
        $this->description = isset($skin->description) ? $skin->description : null;
        $this->properties  = isset($skin->properties) ? $skin->properties : new \stdClass();
        $this->maincss     = isset($skin->properties->css) ? $skin->properties->css : null;
        $this->id          = isset($skin->id) ? $skin->id : null;

        $this->steps  = isset($skin->properties->steps) ? $skin->properties->steps : [];
        $this->item   = $item;
        $this->skinid = $this->get_unique_name();

        $this->properties->title = $this->title;
        $this->properties->description = $this->description;

    }

    public static function get_unique_name() {
        return self::class;
    }

    /**
     * Get a skin by instance.
     *
     * @param $skin
     * @param null $item
     * @return skin|null
     */
    public static function get_by_instance($skin, $item = null) {
        $classname = '\format_ludic\\' . $skin->location . '\\' . $skin->type;
        return class_exists($classname) ? new $classname($skin, $item) : null;
    }

    /**
     * Get a skin by id.
     *
     * @param $skinid
     * @param null $item
     * @return skin|null
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_by_id($skinid, $item = null) {
        global $PAGE;

        // Skin is not in config.
        if ($skinid == FORMAT_LUDIC_CM_SKIN_INLINE_ID) {
            return coursemodule\inline::get_instance();
        } else if ($skinid == FORMAT_LUDIC_CM_SKIN_MENUBAR_ID) {
            return coursemodule\menubar::get_instance();
        } else if ($skinid == FORMAT_LUDIC_CM_SKIN_STEALTH_ID) {
            return coursemodule\stealth::get_instance();
        } else if ($skinid == FORMAT_LUDIC_CS_SKIN_NOLUDIC_ID) {
            return self::get_by_instance(section\noludic::get_instance(), $item);
        }

        // Skin is in config.
        $contexthelper = context_helper::get_instance($PAGE);
        $skins         = $contexthelper->get_skins();

        // Skin not found.
        if (!isset($skins[$skinid]) || empty($skins[$skinid])) {
            return null;
        }

        // Return skin.
        return self::get_by_instance($skins[$skinid], $item);
    }

    /**
     * Return skin properties, or one property if name is defined.
     *
     * @param null $name
     * @return array|mixed|false
     */
    public function get_properties($name = null) {

        // Ensure properties is array.
        $properties = !empty($this->properties) ? get_object_vars($this->properties) : [];

        // Add title and description has properties

        // Name is defined : return property only.
        if ($name != null) {
            return isset($properties[$name]) ? $properties[$name] : false;
        }

        // Name is null : return all properties.
        return $properties;
    }

    /**
     * Return skinned tile css content prefixed by skin selector id.
     *
     * @param $selectorid
     * @return string
     */
    public function get_css($selectorid) {
        $output   = '';
        $css      = $this->maincss . $this->get_additional_css();
        $css      = str_replace("\n", "", $css);
        $cssarray = [];
        $success  = preg_match_all('/.*?{.*?}/', $css, $cssarray);
        if (!$success) {
            return $output;
        }
        foreach ($cssarray[0] as $cssline) {
            $output .= ' #' . $selectorid . ' ' . $cssline;
        }

        return $output;
    }

    /**
     * Delegate to skin to render skinned tile.
     *
     * @return string
     */
    public function render_skinned_tile() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');
        return $renderer->render_skinned_tile($this);
    }

    /**
     * Get weight from item and return it.
     *
     * @return int
     * @throws \dml_exception
     */
    public function get_weight() {
        if ($this->item !== null && $this->weight === null) {
            $this->weight = $this->item->get_weight();
        }
        return $this->weight;
    }

    /**
     * Get completion info from item and return it.
     *
     * @return \stdClass|null
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_completion_info() {
        if ($this->item !== null && $this->results === null) {
            $this->results = $this->item->get_user_results();
        }
        return isset($this->results['completioninfo']) ? $this->results['completioninfo'] : null;
    }

    /**
     * Get score info from item and return it.
     *
     * @return \stdClass|null
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_grade_info() {
        if ($this->item !== null && $this->results === null) {
            $this->results = $this->item->get_user_results();
        }
        return isset($this->results['gradeinfo']) ? $this->results['gradeinfo'] : null;
    }

    /**
     * @return array
     */
    public function get_skin_results(){
        if($this->item === null){
            return [
                'completion' => false,
                'score' => false,
                'weight' => 0
            ];
        }

        if($this->results === null){
            $this->results = $this->item->get_user_results();
        }
        $skinresults = [];

        // Completion
        // COMPLETION_DISABLED and COMPLETION_INCOMPLETE have the same state value, but COMPLETION_DISABLED has type = 0
        if($this->results['completioninfo']->state === COMPLETION_DISABLED && $this->results['completioninfo']->type == 0){
            $skinresults['completion'] = false;
        } else if($this->results['completioninfo']->state == COMPLETION_COMPLETE || $this->results['completioninfo']->state == COMPLETION_COMPLETE_PASS){
            $skinresults['completion'] = 1;
        }else{
            $skinresults['completion'] = 0;
        }

        // Grade
        if ($this->results['gradeinfo']->grademax > 0) {
            $skinresults['score'] = $this->results['gradeinfo']->proportion;
        }else{
            $skinresults['score'] = false;
        }

        // Weight
        $skinresults['weight'] = $this->item->get_weight();

        return $skinresults;


       return $results;
    }

    /**
     * Get edit image.
     *
     * @return \stdClass
     */
    public abstract function get_edit_image();

    /**
     * This skin use and require grade ?
     *
     * @return bool
     */
    public abstract function require_grade();

    /**
     * Return all classes to add to the root of skinned tile.
     *
     * @return array
     */
    public function get_classes() {
        return [];
    }

    /**
     * Return all images to render.
     *
     * @return array
     */
    public function get_images_to_render() {
        return [];
    }

    /**
     * Return extra html to render before skin title
     *
     * @return array
     */
    public function get_extra_html_to_render() {
        return [];
    }

    /**
     * Return all skin texts to render, each text with a class to select it in css.
     *
     * @return array
     */
    public function get_texts_to_render() {
        return [];
    }

    /**
     * Allows a child to add css depending on a situation for example.
     *
     * @return string
     */
    public function get_additional_css() {
        return '';
    }


    public function get_edit_buttons() {
        global $CFG;


        return [
            [
                'identifier' => 'form-save',
                'action'     => 'saveForm',
                'order'      => 1
            ],
            [
                'identifier' => 'form-revert',
                'action'     => 'revertForm',
                'order'      => 2
            ]
        ];
    }
}
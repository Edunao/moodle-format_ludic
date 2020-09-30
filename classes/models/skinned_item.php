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

require_once(__DIR__ . '/model.php');

abstract class skinned_item extends model {

    // properties accessed directly by controllers and renderers
    protected   $domain;
    protected   $skintype;
    protected   $skinname;
    protected   $title;
    protected   $description;
    protected   $template;

    /**
     * skin constructor.
     *
     * @param $skin
     * @param skin $item
     */
    public function __construct($skin) {
        parent::__construct($skin);
        $this->skin = $skin;
        foreach(['domain', 'skintype', 'skinname', 'title', 'description'] as $propname) {
            $this->$propname = $skin->$propname;
        }
    }

    public function get_skin_id(){
        return $this->template->id;
    }

    public function get_title(){
        return $this->title;
    }

    public function get_type_name(){
        return $this->skintype->get_name();
    }

    public function get_domain_name(){
        return $this->domain;
    }

    abstract public function get_instance_name();


    /**
     * Return skinned tile css content prefixed by skin selector id.
     *
     * @param $selectorid
     * @return string
     */
    public function get_css($selectorid) {
        $output   = '';
        $css      = $this->get_additional_css();
        $css      = str_replace("\n", "", $css);
        $cssarray = [];
        $success  = preg_match_all('/.*?{.*?}/', $css, $cssarray);
        if (!$success) {
            return $output;
        }
        foreach ($cssarray[0] as $cssline) {
            $output .= ' .format-ludic .item #' . $selectorid . ' ' . $cssline;
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
     * Get edit image.
     *
     * @return \stdClass
     */
    abstract public function get_edit_image();

    /**
     * Return all images to render.
     *
     * @return array
     */
    abstract public function get_images_to_render();

    /**
     * Return all skin texts to render, each text with a class to select it in css.
     *
     * @return array
     */
    abstract public function get_texts_to_render();

    /**
     * Allows a child to add css depending on a situation for example.
     *
     * @return string
     */
    abstract public function get_additional_css();

    /**
     * Return extra html to render before skin title
     *
     * @return array
     */
    public function get_extra_html_to_render() {
        return [];
    }

    public function get_edit_buttons() {
        global $COURSE;

        $options = [];

       /* $options[] = [
            'identifier' => 'form-save',
            'action'     => 'saveForm',
            'order'      => 1
        ];*/

        if(is_numeric($this->id) && !$this->contexthelper->skin_is_used($this->id)){
            $options[] = [
                'identifier' => 'form-delete-skin',
                'action'     => 'confirmAndDeleteSkin',
                'order'      => 2,
                'attributes' => [
                    'data-courseid' => $COURSE->id
                ],
            ];
        }

        return $options;
    }
}

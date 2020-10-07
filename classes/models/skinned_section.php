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

require_once(__DIR__ . '/skinned_item.php');

abstract class skinned_section extends \format_ludic\skinned_item {
    protected $skindata;
    protected $section;

    public function set_section($section) {
        $this->section = $section;
    }

    public function get_editor_config() {
        // Delegate to specialisation to declare what they need.
        $config = $this->skintype->get_editor_config() ?: [];

        // Add in common fields (prepending them to the array).
        $config["title"]        = "text";
        $config["description"]  = "textarea";
        $config["css"]          = "textarea";

        return $config;
    }

    private function ensure_initialised() {
        // If we're already initialised then there's nothing to do.
        if ($this->skindata !== null) {
            return;
        }

        // Lookup user results for activities in the section.
        $userresults = $this->section->get_user_results();

        // Setup skindata.
        $this->skindata = new \stdClass();
        $this->template->setup_skin_data($this->skindata, $userresults, $this->section);
    }

    public function get_edit_info() {
        return (object)[
            'imgsrc' => format_ludic_get_skin_image_url($this->template->get_edit_image($this->skindata)),
            'title'       => $this->template->get_skin_title(),
            'description' => $this->template->get_skin_description(),
        ];
    }

    public function get_texts_to_render() {
        $this->ensure_initialised();
        return $this->template->get_texts_to_render($this->skindata);
    }

    public function get_images_to_render() {
        $this->ensure_initialised();
        return $this->template->get_images_to_render($this->skindata);
    }

    public function get_additional_css() {
        $this->ensure_initialised();
        return $this->template->get_css($this->skindata);
    }

    public function get_extra_html_to_render() {
        $this->ensure_initialised();
        return $this->template->get_extra_html_to_render($this->skindata);
    }

    public function get_full_typename() {
        return $this->skintype->get_domain() . '-' . $this->skintype->get_name();
    }

    public function get_instance_title() {
        return $this->section->name;
    }

    public function get_instance_name() {
        return 'section-' . $this->section->id;
    }

    public function execute_special_action($action) {
        $this->ensure_initialised();
        return $this->template->execute_special_action($this->skindata, $action);
    }
}

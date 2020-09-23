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
 * Section skin achievement class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic\section;

use format_ludic\form_element;

defined('MOODLE_INTERNAL') || die();

class achievement extends \format_ludic\skin {

    private $state = null;

    public static function get_editor_config() {
        return [
            "settings"   => [
                "title"       => "text",
                "description" => "textarea",
            ],
            "properties" => [
                'css'              => 'textarea',
                "background-image" => "image",
                "final-image"      => "image",
                "steps"            => [
                    "index"                    => "int",
                    "completion-incomplete"    => "image",
                    "completion-complete"      => "image",
                    "completion-complete-pass" => "image",
                    "completion-complete-fail" => "image",
                    "step-text"                => "text",
                    "step-css"                 => "css"
                ]
            ],

        ];
    }

    public static function get_unique_name() {
        return 'section-achievement';
    }

    public static function get_instance() {
        return new self((object) [
            'id'          => self::get_unique_name(),
            'location'    => 'section',
            'type'        => 'achievement',
            'title'       => 'Récompenses d\'activités',
            'description' => 'Chaque activité niveau de réussite des activités change l\'état.',
            'settings'    => self::get_editor_config(),
        ]);
    }

    /**
     * Get the best image.
     *
     * @return \stdClass
     */
    public function get_edit_image() {
        $image = $this->get_properties('final-image');
        return $image ? $image : $this->get_default_image();
    }

    /**
     * Return default image which is displayed to prevent an error.
     *
     * @return object
     */
    public function get_default_image() {
        global $OUTPUT;
        return (object) [
            'imgsrc' => $OUTPUT->image_url('default', 'format_ludic')->out(),
            'imgalt' => 'Default image.'
        ];
    }

    /**
     * This skin don't use or require grade.
     *
     * @return bool
     */
    public function require_grade() {
        return false;
    }

    /**
     * Hidden : id.
     * Text : name.
     * Checkbox : visible.
     * Selection popup : skin id.
     *
     * @return form_element[]
     * @throws \coding_exception
     */
    public function get_images_to_render() {
        $images = [];

        // ​​If the activities have all been completed, then the final image is displayed.
        if ($this->is_perfect()) {
            $images[] = $this->get_properties('final-image');
            return $images;
        }

        // Background (optional)
        $baseimage        = $this->get_properties('background-image');
        $baseimage->class = 'img-0';
        $images[]         = $baseimage;

        // Image for completion state
        $steps = $this->get_properties('steps');
        $state = $this->get_state();
        foreach($steps as $stepinfo){
            if(isset($state[$stepinfo->state]) && $state[$stepinfo->state] > 0){
                $image = $stepinfo->image;
                if (isset($image->imgsrc) && $image->imgsrc != '') {
                    $image->class           = 'img-step img-step-' . $stepinfo->state;
                    $images[$stepinfo->state] = $image;
                    continue;
                }
            }
        }

        return array_values($images);
    }

    public function get_texts_to_render() {
        $texts          = [];
        $state          = $this->get_state();
        $steps          = $this->get_properties('steps');
        $isperfect = $this->is_perfect();

        foreach($steps as $stepinfo){
            if(isset($state[$stepinfo->state]) && $state[$stepinfo->state] > 0){
                $classes = ' number completion-count  completion-' . $stepinfo->state;
                if ($state[$stepinfo->state] > 0) {
                    $classes .= ' sup-zero ';
                }
                if ($isperfect) {
                    $classes .= ' perfect ';
                }
                $texts[] = [
                    'text'  => $state[$stepinfo->state],
                    'class' => $classes
                ];
                continue;
            }
        }

        return $texts;
    }

    public function get_state() {

        if (!is_null($this->state)) {
            return $this->state;
        }

        $cms    = $this->item->get_course_modules();
        $states = [
            'incomplete' => 0,
            'fail'       => 0,
            'complete'   => 0,
            'disable'    => 0,
            'perfect'    => 0,
        ];
        foreach ($cms as $cm) {
            $userresults = $cm->get_user_results();

            // If cm has no completion and no grade, skip it
            if ($userresults['gradeinfo']->grademax == 0 && $userresults['completioninfo']->type == 0) {
                continue;
            }

            $cmstate         = 'incomplete';
            $completionstate = 'incomplete';
            if ($userresults['completioninfo']->type != 0) {
                if ($userresults['completioninfo']->state == COMPLETION_COMPLETE || $userresults['completioninfo']->state == COMPLETION_COMPLETE_PASS) {
                    $completionstate = 'complete';
                } else if ($userresults['completioninfo']->state === COMPLETION_COMPLETE_FAIL) {
                    $completionstate = 'failed';
                }
            } else {
                $completionstate = 'disable';
            }

            if ($userresults['gradeinfo']->grademax == 0) {
                $cmstate = $completionstate == 'complete' ? 'perfect' : $completionstate;
                $states[$cmstate]++;
                continue;
            }

            if ($userresults['gradeinfo']->grademax > 0) {
                if ($userresults['gradeinfo']->proportion == 1) {
                    $cmstate = $completionstate == 'disable' || $completionstate == 'complete' ? 'perfect' : $completionstate;
                } else {
                    $cmstate = $completionstate;
                }
            }

            $states[$cmstate]++;
        }

        return $states;
    }

    public function is_perfect() {
        $state     = $this->get_state();
        if ($state['perfect'] == 0) {
            return false;
        }
        foreach ($state as $name => $value) {
            if ($name == 'perfect' || $name == 'disable') {
                continue;
            }

            if($value > 0){
                return false;
            }
        }

        return true;
    }
}
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
 * Course module item for ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/item.php');

class format_ludic_course_module extends format_ludic_item {

    public $parentid;

    /**
     * format_ludic_course_module constructor.
     *
     * @param \format_ludic\course_module $coursemodule
     */
    public function __construct(\format_ludic\course_module $coursemodule) {
        global $PAGE, $CFG;

        // General data.
        $this->selectorid   = 'ludic-coursemodule-' . $coursemodule->order;
        $contexthelper      = \format_ludic\context_helper::get_instance($PAGE);
        $this->itemtype     = 'coursemodule';
        $this->id           = $coursemodule->id;
        $this->order        = $coursemodule->order;
        $this->tooltip      = $coursemodule->name;
        $this->parentid     = $coursemodule->sectionid;
        $this->isnotvisible = !$coursemodule->visible;
        $this->child        = true;
        $this->skinid       = $coursemodule->skinid;

        // Edit mode.
        if ($contexthelper->is_editing()) {

            // Add in-edition class.
            $this->editmode = true;

            // Action.
            $this->propertiesaction = 'get_properties';

            // Image.
            $skininfo     = $coursemodule->skin->get_edit_info();
            $this->imgsrc = $skininfo->imgsrc;
            $this->imgalt = '';

            // Title.
            $this->title = $coursemodule->name;

            // Enable drag and drop.
            $this->draggable = true;
            $this->droppable = true;

            // Redirect to course module on double click.
            $this->link2   = $coursemodule->get_link();
            $this->action2 = 'getDataLinkAndRedirectTo2';

        } else {

            // Redirect to course module on click.
            if (!$contexthelper->is_student_view_forced()) {
                $this->link   = $coursemodule->get_link();
                $this->action = 'getDataLinkAndRedirectTo';
            }

            // The skin will render all course module content.
            $this->content = $coursemodule->skin->render_skinned_tile();

            // If completion is manual add an icon for completion.
            $userresults = $coursemodule->get_user_results();
            if ($userresults->type == COMPLETION_TRACKING_MANUAL) {
                $completion     = $userresults->state == COMPLETION_INCOMPLETE ? 'completion-n' : 'completion-y';
                $targetstate    = $userresults->state ? 0 : 1;
                $completionlink = $CFG->wwwroot . '/course/togglecompletion.php?id=' . $coursemodule->id;
                $completionlink .= '&sesskey=' . sesskey() . '&completionstate=' . $targetstate;

                $completionicon = [
                    'imgsrc'   => $CFG->wwwroot . '/course/format/ludic/pix/' . $completion . '.svg',
                    'imgalt'   => $userresults->completionstr,
                    'position' => 'bottom',
                    'classes'  => ' manual-completion '
                ];

                // Toggle completion on click.
                if (!$contexthelper->is_student_view_forced()) {
                    $completionicon['link'] = $completionlink;
                }

                $this->icons[] = $completionicon;

            } else if ($userresults->type != COMPLETION_DISABLED) {
                $completion     = $userresults->state == COMPLETION_INCOMPLETE ? 'completion-auto-n' : 'completion-y';
                $targetstate    = $userresults->state ? 0 : 1;
                $completionlink = "#";

                $completionicon = [
                    'imgsrc'   => $CFG->wwwroot . '/course/format/ludic/pix/' . $completion . '.svg',
                    'imgalt'   => $userresults->completionstr,
                    'position' => 'bottom',
                    'classes'  => ' manual-completion '
                ];

                // Toggle completion on click.
                if (!$contexthelper->is_student_view_forced()) {
                    $completionicon['link'] = $completionlink;
                }

                $this->icons[] = $completionicon;
            }
        }

        // Mod icon for edition or teacher.
        if ($contexthelper->is_editing() || !$contexthelper->user_has_student_role()) {
            $modicon           = $coursemodule->get_mod_icon();
            $modicon->position = 'top';
            $modicon->link     = false;

            $this->icons[] = $modicon;
        }

    }
}

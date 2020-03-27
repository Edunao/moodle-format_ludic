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

class format_ludic_course_module_inline extends format_ludic_item {

    public $modname;
    public $iconsrc;
    public $iconalt;
    public $parentid;

    /**
     * format_ludic_course_module constructor.
     *
     * @param \format_ludic\course_module $coursemodule
     */
    public function __construct(\format_ludic\course_module $coursemodule) {
        global $PAGE, $CFG;

        // General data.
        $contexthelper = \format_ludic\context_helper::get_instance($PAGE);
        $this->selectorid = 'ludic-coursemodule-' . $coursemodule->order;
        $this->itemtype   = 'coursemodule';
        $this->modname    = $coursemodule->cminfo->modname;
        $this->id         = $coursemodule->id;
        $this->order      = $coursemodule->order;
        $this->title      = $coursemodule->name;
        $this->parentid   = $coursemodule->sectionid;
        $this->skinid     = $coursemodule->skinid;
        $this->skintype   = $coursemodule->skin->type;

        $icon          = $coursemodule->get_mod_icon();
        $this->iconsrc = $icon->imgsrc;
        $this->iconalt = $icon->imgalt;
        if (!$contexthelper->is_student_view_forced()) {
            $this->link    = $CFG->wwwroot . '/mod/' . $coursemodule->cminfo->modname . '/view.php?id=' . $coursemodule->id;
        }

        if ($this->modname === 'label') {
            $this->link    = false;
            $this->content = label_get_coursemodule_info($coursemodule->cminfo)->content;
        }
    }
}
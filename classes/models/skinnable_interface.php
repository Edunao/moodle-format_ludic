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
 * Activity skin interface.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

interface skinnable_interface {

    /**
     * Return an array of stdClass with grade and completion state.
     *
     * @return \stdClass[]
     */
    public function get_user_results();

    /**
     * Get skin weight from item.
     *
     * @return int
     */
    public function get_weight();

    /**
     * Get skin title from item.
     *
     * @return string
     */
    public function get_skinned_tile_title();

    /**
     * Get sequence for collection skin.
     * Array of index => id.
     * Index must begin by 1.
     *
     * @return array
     */
    public function get_collection_sequence();

}

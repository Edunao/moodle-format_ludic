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
 * Main js file of format_ludic
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Moodle course format javascript.

M.course = M.course || {};

M.course.format = M.course.format || {};

/**
 * Get sections config for this format
 *
 * The section structure is:
 * <div class="container-parents">
 *  <div class="section">...</div>
 *  <div class="section">...</div>
 *   ...
 * </div>
 *
 * @return {object} section list configuration
 */
M.course.format.get_config = function () {
    return {
        container_node: 'div',
        container_class: 'container-parents',
        section_node: 'div',
        section_class: 'section'
    };
};
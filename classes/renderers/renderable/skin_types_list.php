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
 *
 *
 * @package    TODO
 * @subpackage TODO
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     Céline Hernandez <celine@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/item.php');

class format_ludic_skins_types_list extends format_ludic_item {

    public $selected;
    public $skintypes = [];
    public $skinid    = '';

    /**
     * format_ludic_skin constructor.
     *
     * @param \format_ludic\skin $skin
     */
    public function __construct($skinid, $skintypes) {

        $this->skinid = $skinid;

        foreach ($skintypes as $skintype) {
            $this->skintypes[] = [
                'id'          => $skintype->id,
                'skinid'      => $skintype->skinid,
                'skintype'    => $skintype->type,
                'title'       => $skintype->title,
                'description' => $skintype->description,
                "selectorid"  => $skintype->id,
                'itemtype'    => 'skintype',
                'imgsrc'      => $skintype->get_edit_image(),
                'imgalt'      => $skintype->title . ' icon',

                'propertiesaction' => '',
                'action'           => 'get_skin_types_form',
                'controller'       => 'skin',
                'callback'         => 'displaySkinTypesForm'
            ];
        }
    }
}

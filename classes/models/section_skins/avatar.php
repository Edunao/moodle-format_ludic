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

namespace format_ludic\section;

defined('MOODLE_INTERNAL') || die();

class avatar extends \format_ludic\skin {

    private $itemsdata = null;

    public static function get_editor_config() {
        return [
            "settings"   => [
                "name"        => "text",
                "main-css"    => "css",
                "description" => "text",
            ],
            "properties" => [

            ]
        ];
    }

    public static function get_unique_name() {
        return 'section-avatar';
    }

    public static function get_instance() {
        return (object) [
            'id'          => self::get_unique_name(),
            'location'    => 'section',
            'type'        => 'avatar',
            'title'       => 'Avatar',
            'description' => 'Chaque réussite donne des points à dépenser pour acheter des objects',
            'settings'    => self::get_editor_config(),
        ];
    }

    /**
     * Get the best image.
     *
     * @return \stdClass
     */
    public function get_edit_image() {
        $image = $this->get_default_image();
        return count($this->get_properties('background')) > 0 ? $this->get_properties('background')[0] : $image;
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

    public function require_grade() {
        return true;
    }

    public function get_images_to_render() {
        $images = [];

        // Background images
        foreach ($this->get_properties('background') as $index => $backgroundimage) {
            $backgroundimage->class = 'img-background img-background-' . $index;
            $images[]               = $backgroundimage;
        }

        // User images
        $useritems = $this->item->get_user_skin_data($this->contexthelper->get_user_id());
        $skinitems = $this->get_items_data();
        foreach ($useritems as $itemid => $useritem) {
            if (!$useritem->equipped) {
                continue;
            }

            if (!array_key_exists($itemid, $skinitems)) {
                continue;
            }

            $skinimage = $skinitems[$itemid]['images'];
            foreach ($skinimage as $imageinfo) {
                $imageinfo->classes = isset($imageinfo->classes) ? $imageinfo->classes : '';
                $cleanitemid        = str_replace(' ', '', $itemid);
                $imageinfo->class   = $imageinfo->classes . ' img-object img-slot-' . $useritem->slot . ' img-object-' . $cleanitemid;

                $imageinfo->css = '';
                if (isset($imageinfo->zindex)) {
                    $imageinfo->css .= 'z-index:' . $imageinfo->zindex . ';';
                }

                $images[] = $imageinfo;
            }
        }
        return $images;
    }

    public function get_texts_to_render() {
        $cash = $this->get_user_money();
        return [
            [
                'text'  => $cash,
                'class' => 'cash cash-' . $cash
            ],
            [
                'text'  => "Open Shop",
                'class' => 'no-ludic-event open-shop open-shop-' . $this->item->sectioninfo->id
            ],
        ];
    }

    public function get_user_money() {
        $useritems   = $this->get_user_items_data();
        $userresults = $this->item->get_user_results();
        //print_object($userresults);
        $totalmoney = 1000;

        foreach ($useritems as $itemid => $useritem) {
            $totalmoney -= $useritem->cost;
        }

        return $totalmoney;
    }

    public function get_user_items_data() {
        $currentitems = $this->get_items_data();
        $useritems    = $this->item->get_user_skin_data($this->contexthelper->get_user_id());

        // Add free items in user items list
        foreach ($currentitems as $itemid => $item) {
            if ($item['cost'] > 0) {
                continue;
            }

            if (!array_key_exists($itemid, $useritems)) {
                $useritem = [
                    'itemname' => $item['name'],
                    'slot'     => $item['slot'],
                    'cost'     => $item['cost'],
                    'equipped' => false,
                ];

                $useritems[$itemid] = $useritem;
            }
        }

        // Update data
        $this->item->update_user_skin_data($this->contexthelper->get_user_id(), $useritems);

        return $this->item->get_user_skin_data($this->contexthelper->get_user_id());
    }

    public function buy_item($slotname, $itemname) {
        $currentitems = $this->get_items_data();
        $useritems    = $this->item->get_user_skin_data($this->contexthelper->get_user_id());

        // Get item
        $itemid = $slotname . '-' . $itemname;
        if (!array_key_exists($itemid, $currentitems)) {
            return false;
        }
        $item = $currentitems[$itemid];

        // Check is user can buy this item
        $currentmoney = $this->get_user_money();
        if ($currentmoney < $item['cost']) {
            return false;
        }

        $useritem = [
            'itemname' => $item['name'],
            'slot'     => $item['slot'],
            'cost'     => $item['cost'],
            'equipped' => false,
        ];

        $useritems[$itemid] = $useritem;

        $this->item->update_user_skin_data($this->contexthelper->get_user_id(), $useritems);

        $this->toggle_item($slotname, $itemname);

        return true;
    }

    public function toggle_item($slotname, $itemname) {
        $useritems     = $this->item->get_user_skin_data($this->contexthelper->get_user_id());
        $currentitemid = $slotname . '-' . $itemname;

        if (!array_key_exists($currentitemid, $useritems)) {
            return false;
        }

        $useritems[$currentitemid]->equipped = !$useritems[$currentitemid]->equipped;

        // Disabled all others slots items
        foreach ($useritems as $itemid => $itemdata) {
            if ($currentitemid == $itemid) {
                continue;
            }

            if ($itemdata->slot == $slotname) {
                $useritems[$itemid]->equipped = false;
            }
        }
        $this->item->update_user_skin_data($this->contexthelper->get_user_id(), $useritems);
    }

    public function get_items_data() {

        if (!is_null($this->itemsdata)) {
            return $this->itemsdata;
        }

        $slotsdata = $this->get_properties('slots');
        $itemsdata = $this->get_properties('items');
        $items     = [];
        $slots     = [];
        foreach ($slotsdata as $slotindex => $slotdata) {
            $slot                   = $slotdata;
            $slot->index            = $slotindex;
            $slot->items            = [];
            $slots[$slotdata->name] = (array) $slot;
        }

        foreach ($itemsdata as $itemdata) {
            if (!array_key_exists($itemdata->slot, $slots)) {
                continue;
            }
            $uniqueid         = $itemdata->slot . '-' . $itemdata->name;
            $items[$uniqueid] = (array) $itemdata;
        }

        $this->itemsdata = $items;
        return $this->itemsdata;
    }

    public function get_extra_html_to_render() {

        // Don't print shop popup if we display skin in header bar and it's the current section
        if ($this->item->contextview == 'header' && $this->contexthelper->get_section_id() == $this->item->dbrecord->id) {
            return [];
        }

        global $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');

        $htmls = [];

        // Prepare shop
        $useritems = $this->get_user_items_data();
        $slotsdata = $this->get_properties('slots');
        $itemsdata = $this->get_properties('items');
        $slots     = [];

        foreach ($slotsdata as $slotindex => $slotdata) {
            $slot                   = $slotdata;
            $slot->index            = $slotindex;
            $slot->items            = [];
            $slots[$slotdata->name] = (array) $slot;
        }

        foreach ($itemsdata as $itemdata) {
            $itemdata = (object) $itemdata;
            $itemid   = $itemdata->slot . '-' . $itemdata->name;
            if (!array_key_exists($itemdata->slot, $slots)) {
                continue;
            }

            $itemdata->state = 'notbuy';
            if (array_key_exists($itemid, $useritems)) {
                $itemdata->state = 'notequipped';
                if ($useritems[$itemid]->equipped == true) {
                    $itemdata->state = 'equipped';
                }
            }

            $itemdata->sectionid               = $this->item->dbrecord->id;
            $slots[$itemdata->slot]['items'][] = (array) $itemdata;
        }

        $shopcontent = $renderer->render_avatar_shop($slots);

        $htmls[] = [
            'classes' => 'shop no-ludic-event ',
            'content' => $renderer->render_popup('avatar-shop-' . $this->item->sectioninfo->id, "Magasin", $shopcontent),
        ];

        return $htmls;
    }

    public function get_additional_css() {
        $globalcss = $this->get_properties('css');

        $useritems = $this->item->get_user_skin_data($this->contexthelper->get_user_id());
        $skinitems = $this->get_items_data();
        foreach ($useritems as $itemid => $useritem) {
            if (!$useritem->equipped) {
                continue;
            }

            if (!array_key_exists($itemid, $skinitems)) {
                continue;
            }
            $globalcss .= ' ' . $skinitems[$itemid]['css'];
        }
        return $globalcss;
    }
}
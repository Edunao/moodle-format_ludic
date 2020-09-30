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
 * Section skin avatar classes
 *
 * @package   format_ludic
 * @copyright  2020 Edunao SAS (contact@edunao.com)
 * @author     Céline Hernandez <celine@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../skinned_section.php');
require_once(__DIR__ . '/../skin_type.php');
require_once(__DIR__ . '/../skin_template.php');

class skinned_section_avatar extends \format_ludic\skinned_section {
    function __construct(skin_template_section_avatar $template) {
        parent::__construct($template);
        $this->template = $template;
        $this->skintype = new skin_type_section_avatar();
    }
}

class skin_type_section_avatar extends \format_ludic\section_skin_type {
    public static function get_name() {
        return 'avatar';
    }

    public static function get_editor_config() {
        $config = [
            "background" => "image",
            "slots"      => [
                "name" => "text",
                "icon" => "image",
            ],
            "items"      => [
                'slot'   => "text",
                'name'   => "text",
                'icon'   => 'image',
                'cost'   => 'int',
                'css'    => 'textarea',
                'filter' => 'text',
            ]
        ];
        for ($i = 0; $i < 5; ++$i) {
            $config["items"]['image' . $i] = 'image';
            $config["items"]['zbias' . $i] = 'int';
        }
        return $config;
    }
}

class skin_template_section_avatar extends \format_ludic\section_skin_template {

    protected $background = "";
    protected $slots      = [];
    protected $items      = [];

    function __construct($config) {
        // leave the job of extracting common parameters such as title and description to the parent class
        parent::__construct($config);

        // process background configuration
        $this->background = isset($config->background) ? $config->background : "";

        // process slots configuration
        $slotsdata = $config->slots;
        $slots     = [];
        foreach ($slotsdata as $slotindex => $slotdata) {
            $slot                   = $slotdata;
            $slot->index            = $slotindex;
            $slot->items            = [];
            $slots[$slotdata->name] = (array) $slot;
        }
        $this->slots = $slots;

        // process items configuration
        $itemsdata = $config->items;
        $items     = [];
        foreach ($itemsdata as $itemdata) {
            if (!array_key_exists($itemdata->slot, $slots)) {
                continue;
            }
            $uniqueid         = $itemdata->slot . '-' . $itemdata->name;
            $uniqueid         .= $itemdata->filter != '' ? '-' . $itemdata->filter : '';
            $items[$uniqueid] = (array) $itemdata;
        }
        $this->items = $items;
    }

    public function get_edit_image() {
        return $this->background;
    }

    public function get_images_to_render($skindata) {
        $images = [];

        $images[] = (object) [
            'src'   => $this->background,
            'class' => 'img-background img-background-0',
        ];

        // User images
        global $USER;
        $useritems = $skindata->section->get_user_skin_data($USER->id);
        $skinitems = $this->items;
        foreach ($useritems as $itemid => $useritem) {
            if (!$useritem->equipped) {
                continue;
            }

            if (!array_key_exists($itemid, $skinitems)) {
                continue;
            }

            //$skinimage = $skinitems[$itemid]['images'];
            //print_object( $skinitems[$itemid]);
            for ($i = 0; $i < 5; ++$i) {
                if ($skinitems[$itemid]['image' . $i] == '') {
                    continue;
                }
                $imageinfo        = new \stdClass();
                $imageinfo->src   = $skinitems[$itemid]['image' . $i];
                $cleanitemid      = str_replace(' ', '', $itemid);
                $imageinfo->class = 'filter-' . $skinitems[$itemid]['filter'];
                $imageinfo->class = ' img-object img-slot-' . $useritem->slot . ' img-object-' . $cleanitemid . ' ' . $skinitems[$itemid]['filter'];
                $imageinfo->css   = '';
                if (isset($skinitems[$itemid]['zbias' . $i])) {
                    $imageinfo->css .= ' z-index:' . $skinitems[$itemid]['zbias' . $i] . ';';
                }
                $images[] = $imageinfo;
            }

            /*foreach ($skinimage as $imageinfo) {
                $imageinfo->classes = isset($imageinfo->classes) ? $imageinfo->classes : '';
                $cleanitemid        = str_replace(' ', '', $itemid);
                $imageinfo->class   = $imageinfo->classes . ' img-object img-slot-' . $useritem->slot . ' img-object-' . $cleanitemid;

                $imageinfo->css = '';
                if (isset($imageinfo->zindex)) {
                    $imageinfo->css .= 'z-index:' . $imageinfo->zindex . ';';
                }

                $images[] = $imageinfo;
            }*/
        }
        return $images;
    }

    public function get_css($skindata) {
        $globalcss = $this->css;

        global $USER;
        $useritems = $skindata->section->get_user_skin_data($USER->id);
        $skinitems = $this->items;
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

    public function get_texts_to_render($skindata) {
        $cash = $skindata->cash;
        return [
            [
                'text'  => $cash,
                'class' => 'cash' . ($cash == 0 ? ' cash-0' : '')
            ],
            [
                'text'  => '',
                'class' => 'no-ludic-event open-inventory open-inventory-' . $skindata->section->sectioninfo->id
            ],
        ];
    }

    public function setup_skin_data($skindata, $userdata, $section) {
        // store the user id for later use
        global $USER;
        $skindata->userid = $USER->id;

        // fetch additional user data from the database covering items purchased and equipped by the user
        $skindata->useritems = $section->get_user_skin_data($skindata->userid);

        // Add free items to user items list as required
        foreach ($this->items as $itemid => $item) {
            if ($item['cost'] > 0) {
                continue;
            }

            if (!array_key_exists($itemid, $skindata->useritems)) {
                $useritem                     = (object) [
                    'itemname' => $item['name'],
                    'slot'     => $item['slot'],
                    'cost'     => $item['cost'],
                    'equipped' => false,
                ];
                $skindata->useritems[$itemid] = $useritem;
            }
        }

        // calculate the user's total score and use it as the 'earned cash' value
        $skindata->earnedcash = 0;
        foreach ($userdata as $cmdata) {
            $skindata->earnedcash += $cmdata->score;
        }

        // figure out how much cash the user has earned and spent
        $totalcost = 0;
        foreach ($skindata->useritems as $itemid => $useritem) {
            $totalcost += $useritem->cost;
        }
        $skindata->cash = max(0, $skindata->earnedcash - $totalcost);

        // store away a refernce to the section required for database interaction  for buy_item() and toggle_item()
        $skindata->section = $section;

        // prime the 'selected item' marker - it can be modified in actions such as 'buy item' or 'togge item' and subsequently used at display time
        $skindata->selecteditem = '';
    }

    public function buy_item($skindata, $slotname, $itemname) {
        $skindata->selecteditem = $itemname;
        $currentitems           = $this->items;
        $useritems              = $skindata->useritems;

        // Get item
        //$itemid = $slotname . '-' . $itemname;
        $itemid = $itemname;
        if (!array_key_exists($itemid, $currentitems)) {
            return false;
        }
        $item = $currentitems[$itemid];

        // Check is user can buy this item
        $currentmoney = $skindata->cash;
        if ($currentmoney < $item['cost']) {
            return false;
        }

        // add the item to the user's inventory
        $useritem           = [
            'itemname' => $item['name'],
            'slot'     => $item['slot'],
            'cost'     => $item['cost'],
            'equipped' => false,
        ];
        $useritems[$itemid] = $useritem;
        $skindata->section->update_user_skin_data($skindata->userid, $useritems);
        $skindata->useritems[$itemname] = (object) $useritem;

        // equip the item
        $this->toggle_item($skindata, $slotname, $itemname);

        return true;
    }

    public function toggle_item($skindata, $slotname, $itemname) {
        global $USER;
        $skindata->selecteditem = $itemname;
        $useritems              = $skindata->useritems;
        $currentitemid = $itemname;
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
        $skindata->section->update_user_skin_data($USER->id, $useritems);
    }

    public function get_extra_html_to_render($skindata) {

        // Don't print inventory popup if we display skin in header bar and it's the current section
        if ($skindata->section->contextview == 'header') {
            return [];
        }

        global $PAGE;
        $renderer = $PAGE->get_renderer('format_ludic');

        $htmls = [];

        // Prepare inventory
        $money     = $skindata->cash;
        $useritems = $skindata->useritems;
        $slotsdata = $this->slots;
        $itemsdata = $this->items;
        $slots     = [];

        foreach ($slotsdata as $slotindex => $slotdata) {
            $slot                     = $slotdata;
            $slot['index']            = $slotindex;
            $slot['items']            = [];
            $slots[$slotdata['name']] = (array) $slot;
        }

        foreach ($itemsdata as $itemid => $itemdata) {
            $itemdata = (object) $itemdata;
            if (!array_key_exists($itemdata->slot, $slots)) {
                continue;
            }

            $itemdata->itemid = $itemid;
            $itemdata->state = 'notbuy';
            if (array_key_exists($itemid, $useritems)) {
                $itemdata->state = 'notequipped';
                if ($useritems[$itemid]->equipped == true) {
                    $itemdata->state = 'equipped';
                }
            } else if ($itemdata->cost > $money) {
                $itemdata->cantbuy = true;
            }

            if ($itemdata->cost == 0) {
                $itemdata->isfree = true;
            }

            $itemdata->sectionid = $skindata->section->dbrecord->id;
            if ($itemid == $skindata->selecteditem) {
                $itemdata->isselected = ' selected ';
            } else {
                $itemdata->isselected = ' passelected';
            }

            // Prepare inventory icon
            $iconimg        = $itemdata->icon;
            $icon           = new \stdClass();
            $icon->src      = format_ludic_get_skin_image_url($iconimg);
            $icon->alt      = $itemdata->name;
            $itemdata->icon = $icon;

            $slots[$itemdata->slot]['items'][] = (array) $itemdata;
        }

        $inventorycontent = $renderer->render_avatar_inventory($slots);

        $htmls[] = [
            'classes' => 'inventory no-ludic-event ',
            'content' => $renderer->render_popup('avatar-inventory-' . $skindata->section->sectioninfo->id, "Magasin", $inventorycontent),
        ];

        return $htmls;
    }

    public function execute_special_action($skindata, $action) {
        //print_object('action : ');
        //print_object($action);
        switch ($action->type) {
            case 'toggle_item':
                return $this->toggle_item($skindata, $action->slotname, $action->itemname);
            case 'buy_item' :
                return $this->buy_item($skindata, $action->slotname, $action->itemname);
        }
    }
}
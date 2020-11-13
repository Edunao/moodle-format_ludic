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
    public function __construct(skin_template_section_avatar $template) {
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
            'background' => 'image',
            'maxcash'    => 'int',
            'slots'      => [
                'name' => 'text',
                'icon' => 'image',
            ],
            'items'      => [
                'slot'   => 'text',
                'name'   => 'text',
                'icon'   => 'image',
                'cost'   => 'int',
                'filter' => 'text',
            ]
        ];
        for ($i = 0; $i < 5; ++$i) {
            $config['items']['image' . $i] = 'image';
            $config['items']['zbias' . $i] = 'int';
        }
        return $config;
    }

    public static function get_target_string_id() {
        return 'cs-avatar-target';
    }
}

class skin_template_section_avatar extends \format_ludic\section_skin_template {

    protected $background = "";
    protected $maxcash    = 0;
    protected $slots      = [];
    protected $items      = [];

    public function __construct($config) {
        // Leave the job of extracting common parameters such as title and description to the parent class.
        parent::__construct($config);

        // Store away bas configuration parameters.
        $this->background = isset($config->background) ? $config->background : "";
        $this->maxcash = isset($config->maxcash) ? max($config->maxcash,10) : 1000;

        // Process slots configuration.
        $slotsdata = $config->slots;
        $order     = 0;
        $slots     = [];
        foreach ($slotsdata as $slotindex => $slotdata) {
            $slot                   = $slotdata;
            $slot->order            = $order++;
            $slot->index            = $slotindex;
            $slot->items            = [];
            $slots[$slotdata->name] = (array) $slot;
        }
        $this->slots = $slots;

        // Process items configuration.
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

        // User images.
        $useritems = $skindata->useritems;
        $itemsdata = $this->items;
        foreach ($useritems as $itemid => $useritem) {
            if (!$useritem->equipped) {
                continue;
            }

            if (!array_key_exists($itemid, $itemsdata)) {
                continue;
            }

            for ($i = 0; $i < 5; ++$i) {
                if ($itemsdata[$itemid]['image' . $i] == '') {
                    continue;
                }
                $imageinfo        = new \stdClass();
                $imageinfo->src   = $itemsdata[$itemid]['image' . $i];
                $cleanitemid      = str_replace(' ', '', $itemid);
                $imageinfo->class = 'filter-' . $itemsdata[$itemid]['filter'];
                $imageinfo->class = ' img-object img-slot-' . $useritem->slot
                    . ' img-object-' . $cleanitemid . ' '
                    . $itemsdata[$itemid]['filter'];
                $imageinfo->css   = '';
                if (isset($itemsdata[$itemid]['zbias' . $i])) {
                    $imageinfo->css .= ' z-index:' . $itemsdata[$itemid]['zbias' . $i] . ';';
                }
                $images[] = $imageinfo;
            }
        }
        return $images;
    }

    public function get_css($skindata) {
        $globalcss = $this->css;
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
        // Store the user id for later use.
        global $USER;
        $skindata->userid = $USER->id;

        // Fetch additional user data from the database covering items purchased and equipped by the user.
        $skindata->useritems = $section->get_user_skin_data($skindata->userid);

        // Add free items to user items list as required.
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

        // Sum the activity scores and max scores.
        $score    = 0;
        $maxscore = 0;
        foreach ($userdata as $cmdata) {
            $score    += $cmdata->score;
            $maxscore += $cmdata->maxscore;
        }
        $maxscore = max($maxscore, 1);

        // Derive the target score for the top end of the progression scale.
        $targetscore   = ($section->target + 0 <= 0) ? $maxscore : max(1, $section->target);
        $progress      = $score / $targetscore;

        // Calculate the user's total score and use it as the 'earned cash' value.
        $skindata->earnedcash = intval($progress * $this->maxcash / 5) * 5;

        // Figure out how much cash the user has earned and spent.
        $totalcost = 0;
        foreach ($skindata->useritems as $itemid => $useritem) {
            $totalcost += $useritem->cost;
        }
        $skindata->cash = max(0, $skindata->earnedcash - $totalcost);

        // Store away a refernce to the section required for database interaction  for buy_item() and toggle_item().
        $skindata->section = $section;

        // Prime the 'selected item' marker - it can be modified in actions such as 'buy item'
        // or 'togge item' and subsequently used at display time.
        $skindata->selecteditem = '';
    }

    public function buy_item($skindata, $slotname, $itemname) {
        $skindata->selecteditem = $itemname;
        $currentitems           = $this->items;
        $useritems              = $skindata->useritems;

        // Get item.
        $itemid = $itemname;
        if (!array_key_exists($itemid, $currentitems)) {
            return false;
        }
        $item = $currentitems[$itemid];

        // Check is user can buy this item.
        $currentmoney = $skindata->cash;
        if ($currentmoney < $item['cost']) {
            return false;
        }

        // Dock the cash that was just spent in order to update the remaining cash displayed to the user
        $skindata->cash -= $item['cost'];

        // Add the item to the user's inventory.
        $useritem           = [
            'itemname' => $item['name'],
            'slot'     => $item['slot'],
            'cost'     => $item['cost'],
            'equipped' => false,
        ];
        $useritems[$itemid] = $useritem;
        $skindata->section->update_user_skin_data($skindata->userid, $useritems);
        $skindata->useritems[$itemname] = (object) $useritem;

        // Equip the item.
        $this->toggle_item($skindata, $slotname, $itemname);

        return true;
    }

    public function toggle_item($skindata, $slotname, $itemname) {
        global $USER;
        $skindata->selecteditem = $itemname;
        $useritems              = $skindata->useritems;
        $itemsdata              = $this->items;
        $currentitemid          = $itemname;
        if (!array_key_exists($currentitemid, $useritems)) {
            return false;
        }

        $useritems[$currentitemid]->equipped = !$useritems[$currentitemid]->equipped;

        // Disabled all other items for the slot filled by the current choice.
        foreach ($useritems as $itemid => $itemdata) {
            if ($currentitemid == $itemid) {
                continue;
            }
            if ($itemdata->slot == $slotname) {
                $useritems[$itemid]->equipped = false;
            }
        }

        // Determine what filters are now implied by the set of items equipped by the user.
        $filters = [];
        foreach ($useritems as $itemid => $useritem) {
            if (!$useritem->equipped) {
                continue;
            }
            if (!array_key_exists($itemid, $itemsdata)) {
                continue;
            }
            if (!array_key_exists('define', $itemsdata[$itemid]) || !$itemsdata[$itemid]['define']) {
                continue;
            }
            $filters[$itemsdata[$itemid]['define']] = 1;
        }

        // Disabled items incompatible with updated filters.
        foreach ($useritems as $itemid => $useritemdata) {
            if (!array_key_exists($itemid, $itemsdata)) {
                continue;
            }
            $itemdata = (object)$itemsdata[$itemid];
            if (property_exists($itemdata, 'filter') && $itemdata->filter && !array_key_exists($itemdata->filter, $filters)) {
                $useritems[$itemid]->equipped = false;
            }
        }
        $skindata->section->update_user_skin_data($USER->id, $useritems);
    }

    public function get_extra_html_to_render($skindata) {

        // Don't print inventory popup if we display skin in header bar and it's the current section.
        if ($skindata->section->contextview == 'header') {
            return [];
        }

        global $PAGE;
        $renderer   = $PAGE->get_renderer('format_ludic');
        $money      = $skindata->cash;
        $useritems  = $skindata->useritems;
        $slotsdata  = $this->slots;
        $itemsdata  = $this->items;
        $slots      = [];
        $slotsowned = [];
        $slotsother = [];
        $filters    = [];
        $htmls      = [];

        // Determine what filters are implied by the set of items equipped by the user.
        foreach ($useritems as $itemid => $useritem) {
            if (!$useritem->equipped) {
                continue;
            }
            if (!array_key_exists($itemid, $itemsdata)) {
                continue;
            }
            if (!array_key_exists('define', $itemsdata[$itemid])) {
                continue;
            }
            $filters[$itemsdata[$itemid]['define']] = 1;
        }

        // Prepare inventory.
        $order = 0;
        foreach ($slotsdata as $slotindex => $slotdata) {
            $slot                   = $slotdata;
            $slotname               = $slotdata['name'];
            $slot['order']          = $order++;
            $slot['index']          = $slotindex;
            $slot['items']          = [];
            $slots[$slotname]       = $slot;
        }
        foreach ($itemsdata as $itemid => $itemdata) {
            $itemdata = (object) $itemdata;
            if (!array_key_exists($itemdata->slot, $slots)) {
                continue;
            }
            if (property_exists($itemdata, 'filter') && $itemdata->filter && !array_key_exists($itemdata->filter, $filters)) {
                continue;
            }
            $itemdata->itemid = $itemid;
            $itemdata->state = 'notowned';
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

            // Prepare inventory icon.
            $slotname       = $itemdata->slot;
            $iconimg        = $itemdata->icon;
            $icon           = new \stdClass();
            $icon->src      = format_ludic_get_skin_image_url($iconimg);
            $icon->alt      = $itemdata->name;
            $itemdata->icon = $icon;

            if ($itemdata->state == 'notowned') {
                if (!array_key_exists($slotname, $slotsother)) {
                    $slotsother[$slotname] = $slots[$slotname];
                }
                $slotsother[$slotname]['items'][] = (array) $itemdata;
            } else {
                if (!array_key_exists($slotname, $slotsowned)) {
                    $slotsowned[$slotname] = $slots[$slotname];
                }
                $slotsowned[$slotname]['items'][] = (array) $itemdata;
            }
        }

        // sort the slots into order and render them into an inventory html block.
        usort($slotsowned, function ($a, $b) { return ($a['order'] <=> $b['order']); });
        usort($slotsother, function ($a, $b) { return ($a['order'] <=> $b['order']); });
        $inventorycontent = $renderer->render_avatar_inventory($slotsowned, $slotsother);

        $htmls[] = [
            'classes' => 'inventory no-ludic-event ',
            'content' => $renderer->render_popup(
                'avatar-inventory-' . $skindata->section->sectioninfo->id,
                get_string('cs-avatar-inventory', 'format_ludic'),
                $inventorycontent
            ),
        ];

        return $htmls;
    }

    public function execute_special_action($skindata, $action) {
        switch ($action->type) {
            case 'toggle_item':
                return $this->toggle_item($skindata, $action->slotname, $action->itemname);
            case 'buy_item' :
                return $this->buy_item($skindata, $action->slotname, $action->itemname);
        }
    }
}
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
 * Item (section, bravo, skin) for ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class format_ludic_item implements renderable {

    /**
     * Id attribute of item.
     *
     * @var string
     */
    public $selectorid;

    /**
     * Real id of item in database.
     *
     * @var int
     */
    public $id;

    /**
     * Order to display.
     *
     * @var int
     */
    public $order;

    /**
     * Title of item.
     *
     * @var string
     */
    public $title;

    /**
     * Link if we want to redirect by clicking on the item.
     *
     * @var string
     */
    public $link;

    /**
     * Type of item (section, coursemodule, ...)
     *
     * @var string
     */
    public $itemtype;

    /**
     * Is parent ? (required for js events).
     *
     * @var bool
     */
    public $parent;

    /**
     * Is child ? (required for js events).
     *
     * @var bool
     */
    public $child;

    /**
     * Is selected by user ?
     *
     * @var bool
     */
    public $selected;

    /**
     * Image src of item.
     *
     * @var string
     */
    public $imgsrc;


    /**
     * Html content of item.
     *
     * @var string
     */
    public $content;

    /**
     * Image alt of item.
     *
     * @var string
     */
    public $imgalt;

    /**
     * Linked skin id.
     *
     * @var int
     */
    public $skinid;

    /**
     * Linked skin type.
     *
     * @var int
     */
    public $skintype;

    /**
     * Called function (php) which returns its result on the right side (.container-properties).
     * Called on the controller which has the name equal to the type of the article.
     *
     * @var string
     */
    public $propertiesaction;

    /**
     * Called function (php) which returns its result on the left side (.container-parents).
     *
     * @var string
     */
    public $action;

    /**
     * Name of controller to use for action only.
     *
     * @var string
     */
    public $controller;

    /**
     * Javascript function to call with returned value from action.
     *
     * @var string
     */
    public $callback;

    /**
     * Is draggable ?
     *
     * @var bool
     */
    public $draggable;

    /**
     * Is droppable ?
     *
     * @var bool
     */
    public $droppable;

    /**
     * Item is not visible ?
     *
     * @var bool
     */
    public $isnotvisible;

}
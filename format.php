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
 * Ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$contexthelper = \format_ludic\context_helper::get_instance($PAGE);
$headerbar     = new \format_ludic\header_bar($PAGE);
$output        = $headerbar->render();

$renderer = $PAGE->get_renderer('format_ludic');
$items    = [
        'items' => [
                [
                        'name' => 'Section 1', 'type' => 'section', 'imgsrc' => 'section-img', 'imgalt' => 'section alt'
                ],
                [
                        'name' => 'Section 2', 'type' => 'section', 'imgsrc' => 'section-img 2', 'imgalt' => 'section alt 2'
                ],
                [
                        'name' => 'Section 3', 'type' => 'section', 'imgsrc' => 'section-img 3', 'imgalt' => 'section alt 3'
                ],
        ]
];
$columncontent =  $renderer->render_from_template('format_ludic/blockitems', $items);
$columns   = ['columns' => [
        'width' => 100,
        'content' => $columncontent
]];
$popupcontent       = $renderer->render_from_template('format_ludic/columns', $columns);
$popup    = [
        'content' => $popupcontent, 'title' => 'Pop up en une colonne', 'headericon' => [
                'imgsrc' => 'avatar-img', 'imgalt' => 'avatar alt'
        ]

];
$output   = $renderer->render_from_template('format_ludic/popup', $popup);
echo $output;
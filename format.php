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

$courseid = $COURSE->id;
$context  = \context_course::instance($courseid);
$renderer = $PAGE->get_renderer('format_ludic');
$editmode = $PAGE->user_is_editing();
$params   = ['courseid' => $courseid, 'userid' => $USER->id, 'editmode' => $editmode];

$PAGE->set_context($context);

$staticconfig = [
        'skins' => [
                1 => [
                        'id'         => 1,
                        'location'   => 'section',
                        'type'       => 'score',
                        'title'      => 'Coffre au trésor',
                        'properties' => [
                                'images' => [
                                        [
                                                'threshold' => 0,
                                                'imgsrc'    => 'https://picsum.photos/id/100/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 100,
                                                'imgsrc'    => 'https://picsum.photos/id/101/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 200,
                                                'imgsrc'    => 'https://picsum.photos/id/102/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 300,
                                                'imgsrc'    => 'https://picsum.photos/id/103/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 400,
                                                'imgsrc'    => 'https://picsum.photos/id/104/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 500,
                                                'imgsrc'    => 'https://picsum.photos/id/105/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 600,
                                                'imgsrc'    => 'https://picsum.photos/id/106/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 700,
                                                'imgsrc'    => 'https://picsum.photos/id/107/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 800,
                                                'imgsrc'    => 'https://picsum.photos/id/108/80/80', 'imgalt' => ''
                                        ]
                                ],
                                'css'    => '{background-color: #000;}'
                        ]
                ],
                2 => [
                        'id'         => 2,
                        'location'   => 'section',
                        'type'       => 'score',
                        'title'      => 'Coffre de pièces',
                        'properties' => [
                                'images' => [
                                        [
                                                'threshold' => 0,
                                                'imgsrc'    => 'https://picsum.photos/id/200/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 100,
                                                'imgsrc'    => 'https://picsum.photos/id/201/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 200,
                                                'imgsrc'    => 'https://picsum.photos/id/202/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 300,
                                                'imgsrc'    => 'https://picsum.photos/id/203/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 400,
                                                'imgsrc'    => 'https://picsum.photos/id/204/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 500,
                                                'imgsrc'    => 'https://picsum.photos/id/205/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 600,
                                                'imgsrc'    => 'https://picsum.photos/id/206/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 700,
                                                'imgsrc'    => 'https://picsum.photos/id/207/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 800,
                                                'imgsrc'    => 'https://picsum.photos/id/208/80/80', 'imgalt' => ''
                                        ]
                                ],
                        ],
                        'css'        => '{background-color: #efefef;}'
                ]
        ]
];

$contexthelper = \format_ludic\context_helper::get_instance($PAGE);
$staticconfig  = json_encode($staticconfig);
$contexthelper->update_course_format_options(['ludic_config' => $staticconfig]);
$config = $contexthelper->get_course_format_option_by_name('ludic_config');

var_dump(optional_param('section', 0, PARAM_INT));
var_dump(optional_param('clickon', null, PARAM_URL));

// Display course.
if ($editmode) {
    format_ludic_init_edit_mode($context);
    echo $renderer->render_edit_page();
} else {
    echo $renderer->render_page();
}

// Requires format ludic javascript.
$PAGE->requires->strings_for_js(['confirmation-popup-title', 'confirmation-popup-content'], 'format_ludic');
$PAGE->requires->js('/course/format/ludic/format.js');
$PAGE->requires->js_call_amd('format_ludic/format_ludic', 'init', ['params' => $params]);
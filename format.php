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
                11 => [
                        'id'          => 11,
                        'location'    => 'section',
                        'type'        => 'score',
                        'title'       => 'Coffre au trésor',
                        'description' => 'Ce coffre stock des trésors',
                        'properties'  => [
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
                12 => [
                        'id'          => 12,
                        'location'    => 'section',
                        'type'        => 'score',
                        'title'       => 'Coffre de pièces',
                        'description' => 'Ce coffre stock des pièces',
                        'properties'  => [
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
                        'css'         => '{background-color: #efefef;}'
                ],
                13 => [
                        'id'          => 13,
                        'location'    => 'coursemodule',
                        'type'        => 'score',
                        'title'       => 'sac de bijoux',
                        'description' => 'Ce sac contient quelques bijoux.',
                        'properties'  => [
                                'images' => [
                                        [
                                                'threshold' => 0,
                                                'imgsrc'    => 'https://picsum.photos/id/120/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 100,
                                                'imgsrc'    => 'https://picsum.photos/id/121/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 200,
                                                'imgsrc'    => 'https://picsum.photos/id/122/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 300,
                                                'imgsrc'    => 'https://picsum.photos/id/123/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 400,
                                                'imgsrc'    => 'https://picsum.photos/id/124/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 500,
                                                'imgsrc'    => 'https://picsum.photos/id/125/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 600,
                                                'imgsrc'    => 'https://picsum.photos/id/126/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 700,
                                                'imgsrc'    => 'https://picsum.photos/id/127/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 800,
                                                'imgsrc'    => 'https://picsum.photos/id/128/80/80', 'imgalt' => ''
                                        ]
                                ],
                                'css'    => '{background-color: #000;}'
                        ]
                ],
                14 => [
                        'id'          => 14,
                        'location'    => 'coursemodule',
                        'type'        => 'score',
                        'title'       => 'Sac de pièces',
                        'description' => 'Ce sac contient quelques pièces',
                        'properties'  => [
                                'images' => [
                                        [
                                                'threshold' => 0,
                                                'imgsrc'    => 'https://picsum.photos/id/140/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 100,
                                                'imgsrc'    => 'https://picsum.photos/id/141/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 200,
                                                'imgsrc'    => 'https://picsum.photos/id/142/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 300,
                                                'imgsrc'    => 'https://picsum.photos/id/143/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 400,
                                                'imgsrc'    => 'https://picsum.photos/id/144/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 500,
                                                'imgsrc'    => 'https://picsum.photos/id/145/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 600,
                                                'imgsrc'    => 'https://picsum.photos/id/146/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 700,
                                                'imgsrc'    => 'https://picsum.photos/id/147/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 800,
                                                'imgsrc'    => 'https://picsum.photos/id/158/80/80', 'imgalt' => ''
                                        ]
                                ],
                        ],
                        'css'         => '{background-color: #efefef;}'
                ],
                15 => [
                        'id'          => 15,
                        'location'    => 'coursemodule',
                        'type'        => 'achievement',
                        'title'       => 'chat ou chien ?',
                        'description' => 'La réussite vous permet de voir un chat, l\'échec ne permet de voir qu\'un chien',
                        'properties'  => [
                                'images' => [
                                        [
                                                'state'     => 'achieved',
                                                'imgsrc'    => 'https://picsum.photos/id/219/80/80',
                                                'imgalt'    => '',
                                                'scorepart' => 1,
                                                'extratext' => 'chat signifie gagner'
                                        ],
                                        [
                                                'state'     => 'unachieved',
                                                'imgsrc'    => 'https://picsum.photos/id/237/80/80',
                                                'imgalt'    => '',
                                                'scorepart' => 0,
                                                'extratext' => 'chien signifie perdre'
                                        ]
                                ],
                        ],
                        'css'         => '{background-color: #454545;}'
                ]
        ]
];

$contexthelper = \format_ludic\context_helper::get_instance($PAGE);
$staticconfig  = json_encode($staticconfig);
$contexthelper->update_course_format_options(['ludic_config' => $staticconfig]);

// Display course.
if ($editmode) {
    format_ludic_init_edit_mode($context);
    echo $renderer->render_edit_page();
} else {
    echo $renderer->render_page();
}

// Requires format ludic javascript.
$PAGE->requires->strings_for_js(format_ludic_get_strings_for_js($editmode), 'format_ludic');
$PAGE->requires->js('/course/format/ludic/format.js');
$PAGE->requires->js_call_amd('format_ludic/format_ludic', 'init', ['params' => $params]);
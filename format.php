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

$context   = $contexthelper->get_course_context();
$editmode  = $contexthelper->is_editing();
$sectionid = $contexthelper->get_section_id();


$PAGE->set_context($context);

$staticconfig = [
        'skins' => [
                11 => [
                        'id'          => 11,
                        'location'    => 'section',
                        'type'        => 'score',
                        'title'       => 'Coffre de pièces',
                        'description' => 'Ce coffre stock des pièces',
                        'properties'  => [
                                'steps' => [
                                        [
                                                'threshold' => 0,
                                                'imgsrc'    => 'https://cdn1.iconfinder.com/data/icons/security-add-on-colored/48/JD-09-512.png', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 10,
                                                'imgsrc'    => 'https://picsum.photos/id/101/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 20,
                                                'imgsrc'    => 'https://picsum.photos/id/102/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 30,
                                                'imgsrc'    => 'https://picsum.photos/id/103/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 40,
                                                'imgsrc'    => 'https://picsum.photos/id/104/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 50,
                                                'imgsrc'    => 'https://picsum.photos/id/109/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 60,
                                                'imgsrc'    => 'https://picsum.photos/id/106/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 80,
                                                'imgsrc'    => 'https://picsum.photos/id/107/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 100,
                                                'imgsrc'    => 'https://www.clipartmax.com/png/middle/275-2750625_chest-icon-treasure-chest-icon-png.png', 'imgalt' => ''
                                        ]
                                ],
                                'css'   => '{background-color: #000;}'
                        ]
                ],
                12 => [
                        'id'          => 12,
                        'location'    => 'section',
                        'type'        => 'score',
                        'title'       => 'Coffre au trésor',
                        'description' => 'Ce coffre stock des trésors',
                        'properties'  => [
                                'steps' => [
                                        [
                                                'threshold' => 0,
                                                'imgsrc'    => 'https://i.pinimg.com/originals/6a/1d/f3/6a1df304403e15c9a4b499e8539853ec.jpg', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 10,
                                                'imgsrc'    => 'https://picsum.photos/id/201/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 20,
                                                'imgsrc'    => 'https://picsum.photos/id/202/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 30,
                                                'imgsrc'    => 'https://picsum.photos/id/203/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 50,
                                                'imgsrc'    => 'https://picsum.photos/id/204/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 60,
                                                'imgsrc'    => 'https://picsum.photos/id/205/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 70,
                                                'imgsrc'    => 'https://picsum.photos/id/206/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 80,
                                                'imgsrc'    => 'https://picsum.photos/id/209/80/80', 'imgalt' => ''
                                        ],
                                        [
                                                'threshold' => 100,
                                                'imgsrc'    => 'https://visualpharm.com/assets/324/Treasure%20Chest-595b40b85ba036ed117dacb5.svg', 'imgalt' => ''
                                        ]
                                ],
                                'css'   => '{background-color: #efefef;}'
                        ],
                ],
                14 => [
                        'id'          => 14,
                        'location'    => 'coursemodule',
                        'type'        => 'score',
                        'title'       => 'Pokémon feu',
                        'description' => 'Petit Salamèche deviendra grand.',
                        'properties'  => [
                                'steps'           => [
                                        [
                                                'threshold' => 0,
                                                'scorepart' => 0,
                                                'extratext'      => 'tu as entre 0 et 9.99',
                                                'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/4.png',
                                                'imgalt'    => ''
                                        ],
                                        [
                                                'threshold' => 10,
                                                'scorepart' => 50,
                                                'extratext'      => 'tu as entre 10 et 19.99',
                                                'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/5.png',
                                                'imgalt'    => ''
                                        ],
                                        [
                                                'threshold' => 20,
                                                'scorepart' => 100,
                                                'extratext'      => 'tu as 20, bravo',
                                                'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/6.png',
                                                'imgalt'    => ''
                                        ]
                                ],
                                'linearscorepart' => 0.5,
                                'css'             => '{background-color: red;}'
                        ]
                ],
                13 => [
                        'id'          => 13,
                        'location'    => 'coursemodule',
                        'type'        => 'score',
                        'title'       => 'Pokémon plante',
                        'description' => 'Petit Bulbizarre deviendra grand.',
                        'properties'  => [
                                'steps'           => [
                                        [
                                                'threshold' => 5,
                                                'scorepart' => 0,
                                                'extratext'      => 'tu as entre 0 et 9.99',
                                                'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/1.png',
                                                'imgalt'    => ''
                                        ],
                                        [
                                                'threshold' => 10,
                                                'scorepart' => 50,
                                                'extratext'      => 'tu as entre 10 et 19.99',
                                                'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/2.png',
                                                'imgalt'    => ''
                                        ],
                                        [
                                                'threshold' => 20,
                                                'scorepart' => 100,
                                                'extratext'      => 'tu as 20, bravo',
                                                'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/3.png',
                                                'imgalt'    => ''
                                        ]
                                ],
                                'linearscorepart' => 1,
                                'css'             => '{background-color: green;}'
                        ],
                ],
                16 => [
                        'id'          => 16,
                        'location'    => 'coursemodule',
                        'type'        => 'score',
                        'title'       => 'Pokémon eau',
                        'description' => 'Petit Carapuce deviendra grand.',
                        'properties'  => [
                                'steps'           => [
                                        [
                                                'threshold' => 0,
                                                'scorepart' => 0,
                                                'extratext'      => 'tu as entre 0 et 9.99',
                                                'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/7.png',
                                                'imgalt'    => ''
                                        ],
                                        [
                                                'threshold' => 10,
                                                'scorepart' => 50,
                                                'extratext'      => 'tu as entre 10 et 19.99',
                                                'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/8.png',
                                                'imgalt'    => ''
                                        ],
                                        [
                                                'threshold' => 20,
                                                'scorepart' => 100,
                                                'extratext'      => 'tu as 20, bravo',
                                                'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/9.png',
                                                'imgalt'    => ''
                                        ]
                                ],
                                'linearscorepart' => 0.5,
                                'css'             => '{background-color: red;}'
                        ]
                ],
                15 => [
                        'id'          => 15,
                        'location'    => 'coursemodule',
                        'type'        => 'achievement',
                        'title'       => 'Évolution max',
                        'description' => 'Plus tu réussis, plus tu évolues',
                        'properties'  => [
                                'steps' => [
                                        [
                                                'state'     => COMPLETION_INCOMPLETE, //0
                                                'statestr'  => 'completion-incomplete',
                                                'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/63.png',
                                                'imgalt'    => '',
                                                'scorepart' => 0,
                                                'extratext' => 'Abra'
                                        ],
                                        [
                                                'state'     => COMPLETION_COMPLETE, //1
                                                'statestr'  => 'completion-complete',
                                                'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/65.png',
                                                'imgalt'    => '',
                                                'scorepart' => 0.75,
                                                'extratext' => 'Alakazam'
                                        ],
                                        [
                                                'state'     => COMPLETION_COMPLETE_PASS, //2
                                                'statestr'  => 'completion-complete-pass',
                                                'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/10037.png',
                                                'imgalt'    => '',
                                                'scorepart' => 1,
                                                'extratext' => 'Méga-Alakazam'
                                        ],
                                        [
                                                'state'     => COMPLETION_COMPLETE_FAIL, //3
                                                'statestr'  => 'completion-complete-fail',
                                                'imgsrc'    => 'https://www.pokebip.com/pokedex-images/artworks/64.png',
                                                'imgalt'    => '',
                                                'scorepart' => 0.25,
                                                'extratext' => 'Kadabra'
                                        ]
                                ],
                                'css'   => '{background-color: purple;}'
                        ]
                ]
        ]
];
$staticconfig = json_encode($staticconfig);
$contexthelper->update_course_format_options(['ludic_config' => $staticconfig]);

// Display course.
$renderer = $PAGE->get_renderer('format_ludic');
if ($editmode) {
    format_ludic_init_edit_mode($context);
    echo $renderer->render_edit_page();
} else {
    if ($sectionid) {
        // Section view.
        echo $renderer->render_section_page($sectionid);
    } else {
        // Course view.
        echo $renderer->render_page();
    }
}
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
 * Strings for component 'format_ludic', language 'en'
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// General.
$string['pluginname']      = 'Ludic';
$string['hidefromothers']  = 'Hide section';
$string['showfromothers']  = 'Show section';
$string['sectionname']     = 'Section';
$string['section0name']    = 'Menu bar​ &​ Course Overview​';
$string['topicoutline']    = 'Section';
$string['no-section']      = 'Aucune section disponible';
$string['no-section-help'] = 'Aucune section disponible. Passez en mode édition pour en ajouter.';

// Privacy.
$string['privacy:metadata'] = 'The Ludic format plugin does not store any personal data.';

// Course format options.
$string['ludicconfiglabel']     = 'Ludic course configuration data';
$string['ludicsharingkeylabel'] = 'Value for management of sharing of skin and bravo definitions';

// Skins edition
$string['edit-skins-title'] = 'Edition des skins';

// Header bar.
$string['header-bar-preview-section'] = 'Preview section';
$string['header-bar-student-view']    = 'Student view';
$string['header-bar-teacher-view']    = 'Back to teacher view';
$string['header-no-content']          = 'Aucun contenu disponible';

// Section edition.
$string['default-section-title']  = 'Section {$a}';
$string['label-section-title']    = 'Title';
$string['label-section-visible']  = 'Visible';
$string['duplicate-suffix']       = ' (copy)';
$string['section-skin-selection'] = 'Select a skin for the section';
$string['section-preview']        = 'Section preview';
$string['section-no-cm']          = 'Aucune activité disponible';
$string['edit-title-section']     = 'Sections :';
$string['addsection-button']      = 'Add section';


// Course module edition.
$string['course-module-skin-selection'] = 'Select a skin for the course module';
$string['label-course-module-title']    = 'Title';
$string['label-course-module-visible']  = 'Visible';
$string['label-select-weight']          = 'Weight';
$string['label-select-access']          = 'Accessibility';
$string['edit-title-coursemodule']      = 'Activités :';

// Access.
$string['access-accessible']      = 'Always accessible';
$string['access-accessible-desc'] = 'As it says on the tin – an activity is always accessible';

$string['access-chained']      = 'Chained';
$string['access-chained-desc'] = 'An activity is visible but not accessible until the previous activity has been completed.';

$string['access-discoverable']      = 'Discoverable';
$string['access-discoverable-desc'] = 'An activity is not visible or accessible until the previous activity has been completed, at which time it appears and becomes accessible.​';

$string['access-controlled']      = 'Teacher-controlled';
$string['access-controlled-desc'] = 'The activity is not visible or accessible unless and until the teacher manually open up access to selected students.​';

$string['access-grouped']      = 'Grouped with predecessor';
$string['access-grouped-desc'] = 'The item will become visible and available at the same moment as it\'s predecessor. (allowing one \'gateway\' activity followed by freely available activity set, teacher control of access by activity group, ...)​';

$string['access-chained-and-grouped']      = 'Chained and grouped with predecessor';
$string['access-chained-and-grouped-desc'] = 'The item will become visible at the same moment as it\'s predecessor but will only become available after the predecessor has been completed.​';

// Skin.
$string['cm-skin-inline-title']        = 'In page';
$string['cm-skin-inline-description']  = 'Allows labels and other such activities to be displayed inline';
$string['cm-skin-menubar-title']       = 'Menu bar';
$string['cm-skin-menubar-description'] = 'Allows labels and other such activities to be displayed in menu bar';
$string['cm-skin-stealth-title']       = 'Stealth';
$string['cm-skin-stealth-description'] = 'Allows labels and other such activities to be stealth in course';
$string['cs-skin-noludic-title']       = 'No ludic';
$string['cs-skin-noludic-description'] = 'No ludification.';
$string['label-skin-selection']        = 'Appearance';

// Settings.
$string['setting-weight-title']       = 'Poids des activités.';
$string['setting-weight-description'] = 'Poids possibles pour les activités, séparés par une virgule.';

// Forms.
$string['form-success']         = 'Success';
$string['errors']               = 'Errors';
$string['default-error']        = 'An error has occurred.';
$string['error-required']       = 'You must supply a value here.';
$string['error-str-min-length'] = 'You must enter at least {$a} characters here.';
$string['error-str-max-length'] = 'You must enter no more than {$a} characters here.';
$string['error-int-min']        = 'The value must be greater than {$a}.';
$string['error-int-max']        = 'The value must be less than {$a}.';
$string['error-int-step']       = 'The value must be a multiple of {$a}.';

// Buttons.
$string['form-save']     = 'Save';
$string['form-revert']   = 'Revert';
$string['item-preview']  = 'Preview';
$string['item-open']     = 'Open';
$string['edit']          = 'Edit';
$string['edit-settings'] = 'Settings';
$string['duplicate']     = 'Duplicate';
$string['delete']        = 'Delete';
$string['assign']        = 'Assign roles';
$string['collapsed-alt'] = 'Collapsed';

// Popups.
$string['confirmation-popup-title']   = 'Confirmation';
$string['confirmation-popup-content'] = 'Êtes-vous sûr de vouloir effectuer cette action ?';

$string['error-popup-title']   = 'Une erreur s\'est produite';
$string['error-popup-content'] = 'Erreur, veuillez cliquer sur Confirmer pour rafraîchir la page';

$string['confirmation-form-exit-title']   = 'Quitter l\'édition du formulaire ?';
$string['confirmation-form-exit-content'] = 'Vous êtes en train d\'éditer un formulaire, si vous quittez vous risquez de perdre des données non enregistrées. Êtes-vous sûr de quitter l\'édition du formulaire ?';

$string['close-button-alt']            = 'Fermer';
$string['confirmation-button-confirm'] = 'Confirmer';
$string['confirmation-button-cancel']  = 'Annuler';
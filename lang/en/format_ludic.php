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
$string['section0name']    = 'Overview​';
$string['topicoutline']    = 'Section';
$string['no-section']      = 'No sections available';
$string['no-section-help'] = 'No sections available.';

// Permissions.
$string['ludic:editludicconfig'] = 'Can customize skins';

// Privacy.
$string['privacy:metadata:format_ludic_user_cs_state'] = 'Informations regarding users\'s personal choices while viewing courses (such as avatar customisation choices)';
$string['privacy:metadata:format_ludic_user_cs_state:userid'] = 'Unique identifier of the user';
$string['privacy:metadata:format_ludic_user_cs_state:courseid'] = 'Unique identifier of the course';
$string['privacy:metadata:format_ludic_user_cs_state:sectionid'] = 'Unique identifier of the section';

// Course format options.
$string['ludicconfiglabel']     = 'Ludic course configuration data';
$string['ludicsharingkeylabel'] = 'Value for management of sharing of skin and bravo definitions';

// Skins editing.
$string['edit-skins-title'] = 'Skin customization';
$string['skins-types-list'] = 'Skins types';
$string['skins-list']       = 'Skins';
$string['edit-skin-form-error-config'] = 'ERROR: Invlid data. Save failed.';
$string['edit-skin-images'] = 'Image Library';
$string['edit-skin-config'] = 'Skin Configuration';
$string['edit-skin-new']    = 'New Skin';


// Header bar.
$string['header-bar-menu']            = 'Useful links';
$string['header-bar-preview-section'] = 'Preview section';
$string['header-bar-student-view']    = 'Student view';
$string['header-bar-teacher-view']    = 'Back to edit mode';
$string['header-no-content']          = 'No content available';

// Section edition.
$string['default-section-title']  = 'Section {$a}';
$string['label-section-title']    = 'Title';
$string['label-section-visible']  = 'Visible';
$string['duplicate-suffix']       = ' (copy)';
$string['section-skin-selection'] = 'Select a skin for the section';
$string['section-preview']        = 'Section preview';
$string['section-no-cm']          = 'No content available';
$string['edit-title-section']     = 'Sections :';
$string['addsection-button']      = 'Add section';


// Course module edition.
$string['course-module-skin-selection'] = 'Select a skin for the course module';
$string['label-course-module-title']    = 'Title';
$string['label-course-module-visible']  = 'Visible';
$string['label-select-weight']          = 'Value';
$string['label-select-access']          = 'Accessibility';
$string['edit-title-coursemodule']      = 'Content :';
$string['addcm-button']                 = 'Add content';

// Access.
$string['access-accessible']        = 'Always accessible';
$string['access-accessible-desc']   = 'As it says on the tin – an activity is always accessible';

$string['access-chained']           = 'Chained';
$string['access-chained-desc']      = 'An activity is visible but not accessible until the previous activity has been completed.';

$string['access-discoverable']      = 'Discoverable';
$string['access-discoverable-desc'] = 'An activity is not visible or accessible until the previous activity has been completed, at which time it appears and becomes accessible.​';

$string['access-controlled']        = 'Teacher-controlled';
$string['access-controlled-desc']   = 'The activity is not visible or accessible unless and until the teacher manually open up access to selected students.​';

$string['access-grouped']           = 'Grouped with predecessor';
$string['access-grouped-desc']      = 'The item will become visible and available at the same moment as it\'s predecessor. (allowing one \'gateway\' activity followed by freely available activity set, teacher control of access by activity group, ...)​';

$string['access-chained-and-grouped']      = 'Chained and grouped with predecessor';
$string['access-chained-and-grouped-desc'] = 'The item will become visible at the same moment as it\'s predecessor but will only become available after the predecessor has been completed.​';

// Skin.
$string['cm-skin-inline-title']         = 'In page';
$string['cm-skin-inline-description']   = 'Allows labels and other such content to be displayed inline';
$string['cm-skin-menubar-title']        = 'Menu bar';
$string['cm-skin-menubar-description']  = 'Allows forums and other such content to be accessed from the menu bar';
$string['cm-skin-stealth-title']        = 'Stealth';
$string['cm-skin-stealth-description']  = 'Allows labels and other such content to be stealth in course';
$string['cs-skin-noludic-title']        = 'Static';
$string['cs-skin-noludic-description']  = 'No gamification';
$string['label-skin-selection']         = 'Skin';

$string['skin-avatar-notmoney'] = 'Not enough money !';
$string['skin-avatar-buy'] = 'Buy now !';

// Settings.
$string['setting-weight-title']       = 'Activity Values';
$string['setting-weight-description'] = 'A comma separated list of values for activities to populate teacher menus.';

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
$string['form-save']            = 'Save';
$string['form-revert']          = 'Revert';
$string['form-delete-skin']     = "Delete skin";
$string['form-duplicate-skin']  = "Duplicate skin";
$string['item-preview']         = 'Preview';
$string['item-open']            = 'Go to';
$string['edit']                 = 'Edit';
$string['edit-settings']        = 'Settings';
$string['duplicate']            = 'Duplicate';
$string['delete']               = 'Delete';
$string['assign']               = 'Assign roles';
$string['collapsed-alt']        = 'Collapsed';
$string['editskins']            = 'Customize skins';
$string['editcourse']           = 'Edit course';


// Popups.
$string['confirmation-popup-title']     = 'Confirmation';
$string['confirmation-popup-content']   = 'Are you certain that you want to perform this action ?';

$string['error-popup-title']            = 'An error occurred';
$string['error-popup-content']          = 'Error, please press OK to refresh the page.';

$string['confirmation-form-exit-title']   = 'Are you certain that you want to leave ?';
$string['confirmation-form-exit-content'] = 'If you leave any unsaved edits will be lost.Are you sure that you wish to leave ?';

$string['close-button-alt']            = 'Close';
$string['confirmation-button-confirm'] = 'OK';
$string['confirmation-button-cancel']  = 'Cancel';


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
$string['pluginname']      = 'Ludique';
$string['hidefromothers']  = 'Masquer la section';
$string['showfromothers']  = 'Afficher la section';
$string['sectionname']     = 'Section';
$string['section0name']    = 'Vue d\'ensemble';
$string['topicoutline']    = 'Section';
$string['no-section']      = 'Aucune section disponible';
$string['no-section-help'] = 'Aucune section disponible.';

// Permissions.
$string['ludic:editludicconfig'] = 'Pouvoir modifier les apparences.';

// Privacy.
$string['privacy:metadata:format_ludic_user_cs_state'] = 'Informations concernant les choix personnels de l\'utilisateur durant sa navigation dans les cours (par exemple ses choix sur l\'avatar, achats/choix d\'objets)';
$string['privacy:metadata:format_ludic_user_cs_state:userid'] = 'Identidiant unique de l\'utilisateur';
$string['privacy:metadata:format_ludic_user_cs_state:courseid'] = 'Identifiant unique du cours';
$string['privacy:metadata:format_ludic_user_cs_state:sectionid'] = 'Identifiant unique de la section';

// Course format options.
$string['ludicconfiglabel']     = 'Configuration de cours Ludique.';
$string['ludicsharingkeylabel'] = 'Valeur pour la gestion des partages d\'apparence et la définition des "bravo"';

// Skins editing.
$string['edit-skins-title'] = 'Personnalisation de l\'apparence';
$string['skins-types-list'] = 'Types d\'apparences';
$string['skins-list']       = 'Apparences';
$string['edit-skin-form-error-config'] = 'Erreur : données incorrects. La sauvegarde a échoué.';
$string['edit-skin-images'] = 'Bibliothèque d\'images';
$string['edit-skin-config'] = 'Paramétrer l\'apparence';
$string['edit-skin-new']    = 'Nouvelle apparence';

// Header bar.
$string['header-bar-menu']            = 'Liens utiles';
$string['header-bar-preview-section'] = 'Prévisualiser la section';
$string['header-bar-view-course']     = 'Voir le cours';
$string['header-bar-edit-course']     = 'Retour au cours';
$string['header-bar-edit-skins']      = 'Editer l\'apparence';
$string['header-no-content']          = 'Aucun contenu disponible';

// Section edition.
$string['default-section-title']  = 'Section {$a}';
$string['label-section-title']    = 'Titre';
$string['label-section-visible']  = 'Visible';
$string['duplicate-suffix']       = ' (copie)';
$string['section-skin-selection'] = 'Select an appearance for the section';
$string['section-preview']        = 'Prévisualiser la section';
$string['section-no-cm']          = 'Aucun contenu disponible';
$string['edit-title-section']     = 'Sections :';
$string['addsection-button']      = 'Ajouter une section';

// Course module editing.
$string['course-module-skin-selection'] = 'Sélectionnez une apparence pour l\'activité du cours';
$string['label-course-module-title']    = 'Titre';
$string['label-course-module-visible']  = 'Visible';
$string['label-weight']                 = 'Valeur';
$string['label-move-section']           = 'Déplacer dans une section';
$string['label-select-access']          = 'Accessibilité';
$string['edit-title-coursemodule']      = 'Contenu :';
$string['addcm-button']                 = 'Ajout de contenu';

// Access.
$string['access-accessible']        = 'Toujours accessible';
$string['access-accessible-desc']   = 'Comme écrit, une activité toujours accessible.';

$string['access-chained']           = 'Conditionné au précédent';
$string['access-chained-desc']      = 'Une activité visible mais non accessible avant que l\'activité précédente ne soit complétée.';

$string['access-discoverable']      = 'À découvrir';
$string['access-discoverable-desc'] = 'L\'activité n\'est ni visible ni accessible avant que l\'activité précédente ne soit complétée. A ce moment là, l\'activité apparait et devient accessible.​';

$string['access-controlled']        = 'Contrôlée par l\'enseignant';
$string['access-controlled-desc']   = 'L\'activité n\'est ni visible ni accessible tant que l\'enseignant n\'en ouvre pas manuellement l\'accès à une liste d\'étudiants.​';

$string['access-grouped']           = 'Regroupée au précédent';
$string['access-grouped-desc']      = 'L\'activité deviendra visible et accessible au même moment que son précédesseur. (Permet une activité "passerelle" suivie d\'un ensemble d\'activités en accès libre, contrôlées par l\'enseignant, gérées par groupe...)​';

$string['access-chained-and-grouped']      = 'Conditionnée et regroupée avec le précédent';
$string['access-chained-and-grouped-desc'] = 'L\'activté devient visible au même moment que son précédesseur mais deviendra accessible uniquement après que son précédesseur soit complété.';

// Skin.
$string['label-skin-selection']         = 'Apparence';
$string['cm-skin-inline-title']         = 'Dans la page';
$string['cm-skin-inline-description']   = 'Permettre d\'afficher les étiquettes et contenus de ce type directement dans la page';
$string['cm-skin-menubar-title']        = 'Barre de menu';
$string['cm-skin-menubar-description']  = 'Permettre l\'accès aux forums et contenus de ce type directement depuis la barre de menu';
$string['cm-skin-stealth-title']        = 'Furtive';
$string['cm-skin-stealth-description']  = 'Permettre le mode furtif aux étiquettes et contenus de ce type de ce cours';
$string['cs-skin-noludic-title']        = 'Statique';
$string['cs-skin-noludic-description']  = 'Pas de ludification';

// Skin-specifics.
$string['cs-avatar-target-title']       = 'Valeur cible (Ou 0 pour désactiver)';
$string['cs-avatar-target-help']        = 'Si la valeur du champ n\'est pas zéro alors  then it can be used to set the target value explicitly, for ues when students are not required to complete all possible activities';
$string['cs-avatar-inventory']          = 'Inventaire';
$string['cs-avatar-notmoney']           = 'Pas assez d\'argent !';
$string['cs-avatar-buy']                = 'Acheter !';
$string['cs-progress-target-title']     = 'Valeur cible (Ou 0 pour désactiver)';
$string['cs-progress-target-help']      = 'Si la valeur de ce champ est différente de zéro, il peut être utilisé pour définir explicitement la valeur cible, par exemple lorsque les élèves ne sont pas tenus de réaliser toutes les activités possibles.';
$string['cs-score-target-title']        = 'Valeur cible (Ou 0 pour désactiver)';
$string['cs-score-target-help']         = 'Si la valeur de ce champ est différente de zéro, il peut être utilisé pour définir explicitement la valeur cible, par exemple lorsque les élèves ne sont pas tenus de réaliser toutes les activités possibles.';
$string['cm-score-targetmin-title']     = 'Note la plus basse pour réussir [0..100]';
$string['cm-score-targetmin-help']      = 'Si la valeur de ce champ est différente de zéro, elle remplace le seuil de réussite de la note la plus basse par défaut';
$string['cm-score-targetmax-title']     = 'Note la plus haute pour réussir [0..100]';
$string['cm-score-targetmax-help']      = 'Si la valeur de ce champ est différente de zéro, elle remplace le seuil de réussite de la meilleure note par défaut';

// Settings.
$string['setting-weight-title']       = 'Valeur de l\'activité';
$string['setting-weight-description'] = 'Une liste de valeurs séparées par des virgules pour les activités afin de remplir les menus enseignants.';

// Forms.
$string['form-success']         = 'Modifications sauvegardées';
$string['errors']               = 'Erreurs';
$string['default-error']        = 'Une erreur s\'est produite.';
$string['error-required']       = 'Vous devez remplir ce champ.';
$string['error-str-min-length'] = 'Vous devez saisir au moins {$a} caractères dans ce champ.';
$string['error-str-max-length'] = 'Vous ne devez pas saisir plus de {$a} caractères dans ce champ.';
$string['error-int-min']        = 'La valeur doit être supérieure à {$a}.';
$string['error-int-max']        = 'La valeur doit être inférieure à {$a}.';
$string['error-int-step']       = 'La valeur doit être un multiple de {$a}.';

// Buttons.
$string['form-save']            = 'Sauvegarder';
$string['form-revert']          = 'Rétablir';
$string['form-delete-skin']     = "Supprimer l\'apparence";
$string['form-duplicate-skin']  = "Dupliquer l\'apparence";
$string['item-preview']         = 'Prévisualisation';
$string['item-open']            = 'Aller vers';
$string['edit']                 = 'Éditer';
$string['edit-settings']        = 'Paramètres';
$string['duplicate']            = 'Dupliquer';
$string['delete']               = 'Supprimer';
$string['assign']               = 'Attribution des rôles';
$string['collapsed-alt']        = 'Réduire';
$string['editskins']            = 'Personnaliser les apparences';
$string['editcourse']           = 'Retour au cours';


// Popups.
$string['confirmation-popup-title']     = 'Confirmation';
$string['confirmation-popup-content']   = 'Êtes-vous sûr de vouloir réaliser cette action ?';

$string['error-popup-title']            = 'Une erreur s\'est produite';
$string['error-popup-content']          = 'Erreur, veuillez cliquer sur OK pour rafraîchir la page.';

$string['confirmation-form-exit-title']   = 'Êtes-vous sûr de vouloir quitter cette page ?';
$string['confirmation-form-exit-content'] = 'Si vous quittez la page, toutes les modifications non sauvegardées seont perdues. Êtes-vous sûr de vouloir partir ?';

$string['close-button-alt']            = 'Fermer';
$string['confirmation-button-confirm'] = 'OK';
$string['confirmation-button-cancel']  = 'Annuler';


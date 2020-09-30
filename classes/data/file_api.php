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
 * TODO File gestion interface.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

class file_api {


    public function get_skin_img_from_fileid($fileid){
        global $DB, $CFG;
        $filerecord = $DB->get_record('files', ['id' => $fileid]);

        if(!$filerecord){
            // TODO maybe return default image ?
            return '';
        }

        $fs = get_file_storage();
        $file = $fs->get_file($filerecord->contextid, $filerecord->component, $filerecord->filearea,
            $filerecord->itemid, $filerecord->filepath, $filerecord->filename);
        if(!$file){
            return '';
        }
        return $url = \moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false)->out();
    }

    public function get_skin_img_from_name($fullimgname, $courseid){
        global $OUTPUT;
        // Explode full file name to get path
        $explodedfilename = explode('/', $fullimgname);

        // Check if file exist in database
        // TODO fix filepath
        $filepath = '/';

        if(count($explodedfilename) == 1){
            $fs = get_file_storage();

            $fileinfo = array(
                'contextid' => \context_course::instance($courseid)->id,
                'component' => 'format_ludic',
                'filearea'  => 'ludicimages',
                'filepath'  => $filepath,
                'itemid'    => 0,
                'filename'  => end($explodedfilename),
            );

            $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
            if($file){
                return \moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false)->out();
            }
        }

        // Use plugin files
        return $OUTPUT->image_url($fullimgname, 'format_ludic')->out();

    }

    /**
     * Create moodle file for skin file with filame
     *
     * @param $courseid
     * @param $skintype
     * @param $skinid
     * @param $attribute
     * @param $imgmoodleurl
     * @return int
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function create_skin_file_from_url($courseid, $skintype, $skinid, $attribute, $imgmoodleurl){

        // Prepare file data based on moodleurl
        $filename = basename($imgmoodleurl->get_path());
        $content = file_get_contents($imgmoodleurl->out());

        $fs = get_file_storage();

        $fileinfo = array(
            'contextid' => \context_course::instance($courseid)->id,
            'component' => 'format_ludic',
            'filearea'  => 'skin',
            'filepath'  => '/'.$skintype.'/' .$skinid . '/' . $attribute . '/' ,
            'itemid'    => 0,
            'filename'  => basename($filename),
        );

        // Check if file exist
        $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
            $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
        if($file){
            $file->delete();
        }

        // Create file
        return $fs->create_file_from_string($fileinfo, $content)->get_id();
    }

    public function create_skin_file_from_draft($courseid, $skintype, $skinid, $attribute,$itemid){
        global $DB;

        $draftrecord = $DB->get_record_sql('
            SELECT * 
            FROM {files} 
            WHERE itemid = :itemid AND filearea = :filearea AND mimetype IS NOT NULL
            ', ['itemid' => $itemid, 'filearea' => 'draft']);

        if(!$draftrecord){
            return false;
        }

        $fs = get_file_storage();

        $draftfile = $fs->get_file($draftrecord->contextid, $draftrecord->component, $draftrecord->filearea,
            $draftrecord->itemid, $draftrecord->filepath, $draftrecord->filename);

        $content = $draftfile->get_content();

        $fileinfo = array(
            'contextid' => \context_course::instance($courseid)->id,
            'component' => 'format_ludic',
            'filearea'  => 'skin',
            'filepath'  => '/'.$skintype.'/' .$skinid . '/' . $attribute . '/' ,
            'itemid'    => 0,
            'filename'  => $draftfile->get_filename(),
        );

        // Check if file exist
        $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
            $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
        if($file){
            $file->delete();
        }

        // Create file
        return $fs->create_file_from_string($fileinfo, $content)->get_id();
    }

    public function get_draft_itemid_from_fileid($fileid){
        global $DB;
        $file = $DB->get_record('files', ['id' => $fileid]);
        $draftitemid = file_get_submitted_draft_itemid('background');

        file_prepare_draft_area($draftitemid, $file->contextid, $file->component,'background', 0);

        return $draftitemid;
    }


}


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
 * Provider class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Class provider
 *
 * @package format_summary\privacy
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider{

    /**
     * Returns metadata.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table(
            'format_ludic_user_cs_state',
            [
                'userid' => 'privacy:metadata:format_ludic_user_cs_state:userid',
                'courseid' => 'privacy:metadata:format_ludic_user_cs_state:courseid',
                'sectionid' => 'privacy:metadata:format_ludic_user_cs_state:sectionid',
                'data' => 'privacy:metadata:format_ludic_user_cs_state:data',

            ],
            'privacy:metadata:format_ludic_user_cs_state'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : \core_privacy\local\request\contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "
            SELECT DISTINCT ctx.id
            FROM {course} c
            JOIN {context} ctx
                ON ctx.instanceid = c.id
                AND ctx.contextlevel = :courselevel
            LEFT JOIN {format_ludic_user_cs_state} css
                ON css.courseid = c.id
                AND css.userid = :userid    
            WHERE css.id IS NOT NULL
        ";

        $params = [
            'courselevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        $contexts = $contextlist->get_contexts();

        foreach ($contexts as $context) {
            $userdata = self::get_user_data_for_context($context);
            writer::with_context($context)->export_data([], (object) $userdata);
        }
    }


    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        $contextludicstate = $DB->get_records_sql('
            SELECT
              cs.*
            FROM
              {format_ludic_user_cs_state} cs
            JOIN
              {context} ct ON ct.instanceid = cs.courseid
            WHERE
              ct.id = :contextid
        ', array('contextid' => $context->id));

        foreach ($contextludicstate as $state) {
            $state->data = '';
            $DB->update_record('format_ludic_user_cs_state', $state);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @throws \dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        $contexts = $contextlist->get_contexts();

        foreach ($contexts as $context) {
            $userdata = self::get_user_data_for_context($userid, $context);
            // remove the userid from the cells edited by the user
            foreach ($userdata as $data) {
                $data->data = null;
                $DB->update_record('format_ludic_user_cs_state', $data);
            }
        }
    }

    /**
     * @param $userid
     * @param $context
     * @return array
     * @throws \dml_exception
     */
    public static function get_user_data_for_context($userid, $context) {
        global $DB;

        return $DB->get_records_sql('
            SELECT 
              cs.*
            FROM
              {format_ludic_user_cs_state} cs
              JOIN {context} c ON c.instanceid = cs.courseid
            WHERE
              cs.userid = :userid
              AND 
              c.id = :contextid
        ', array('userid' => $userid, 'contextid' => $context->id));
    }
}
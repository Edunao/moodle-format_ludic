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
class provider implements \core_privacy\local\metadata\provider{

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

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     *
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_course::class)) {
            return;
        }

        $params = [
            'courselevel' => CONTEXT_COURSE,
            'contextid' => $context->id,
        ];

        // Mapping of lesson tables which may contain user data.
        $joins = [
            'format_ludic_user_cs_state',
        ];

        foreach ($joins as $join) {
            $sql = "
                SELECT cx.userid
                  FROM {course} c
                  JOIN {context} ctx
                    ON ctx.instanceid = c.id
                   AND ctx.contextlevel = :courselevel
                  JOIN {{$join}} cx
                    ON cx.courseid = c.id
                 WHERE ctx.id = :contextid";

            $userlist->add_from_sql('userid', $sql, $params);
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {

        $user = $contextlist->get_user();
        $userid = $user->id;

        $courseids = array_reduce($contextlist->get_contexts(), function($carry, $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $carry[] = $context->instanceid;
            }
            return $carry;
        }, []);
        if (empty($courseids)) {
            return;
        }

        // If the context export was requested, then let's at least describe the course.
        foreach ($courseids as $courseid){
            $context = \context_course::instance($courseid);
            $contextdata = helper::get_context_data($context, $user);
            helper::export_context_files($context, $user);
            writer::with_context($context)->export_data([], $contextdata);
        }

        // Export section data

    }
}
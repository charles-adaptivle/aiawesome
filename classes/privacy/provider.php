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
 * Privacy provider for AI Awesome plugin.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aiawesome\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider implementation for AI Awesome plugin.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * @param   collection  $collection The initialised collection to add items to.
     * @return  collection  A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_aiawesome_logs',
            [
                'userid' => 'privacy:metadata:local_aiawesome_logs:userid',
                'courseid' => 'privacy:metadata:local_aiawesome_logs:courseid',
                'sessionid' => 'privacy:metadata:local_aiawesome_logs:sessionid',
                'bytes_up' => 'privacy:metadata:local_aiawesome_logs:bytes_up',
                'bytes_down' => 'privacy:metadata:local_aiawesome_logs:bytes_down',
                'status' => 'privacy:metadata:local_aiawesome_logs:status',
                'error' => 'privacy:metadata:local_aiawesome_logs:error',
                'createdat' => 'privacy:metadata:local_aiawesome_logs:createdat',
                'duration_ms' => 'privacy:metadata:local_aiawesome_logs:duration_ms',
            ],
            'privacy:metadata:local_aiawesome_logs'
        );

        $collection->add_external_location_link(
            'aiservice',
            [
                'context' => 'privacy:metadata:external:aiservice:context',
                'query' => 'privacy:metadata:external:aiservice:query',
            ],
            'privacy:metadata:external:aiservice'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int $userid The user to search.
     * @return  contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // AI interactions happen at system level.
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {local_aiawesome_logs} logs ON ctx.instanceid = logs.courseid
                 WHERE ctx.contextlevel = :contextlevel
                   AND logs.userid = :userid";

        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        // Also add system context if user has any logs.
        $systemcontext = \context_system::instance();
        $haslogs = \local_aiawesome\logging_service::get_user_usage($userid, 0);
        if ($haslogs->total_requests > 0) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context instanceof \context_system) {
            // System context - get all users with logs.
            $sql = "SELECT DISTINCT userid FROM {local_aiawesome_logs}";
            $userlist->add_from_sql('userid', $sql, []);
        } elseif ($context instanceof \context_course) {
            // Course context - get users with logs for this course.
            $sql = "SELECT DISTINCT userid FROM {local_aiawesome_logs} WHERE courseid = ?";
            $userlist->add_from_sql('userid', $sql, [$context->instanceid]);
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $contexts = $contextlist->get_contexts();

        foreach ($contexts as $context) {
            if ($context instanceof \context_system) {
                // Export all user logs.
                $logs = \local_aiawesome\logging_service::get_user_logs($user->id);
                self::export_user_logs($context, $logs);
            } elseif ($context instanceof \context_course) {
                // Export logs for this specific course.
                $logs = $DB->get_records('local_aiawesome_logs', [
                    'userid' => $user->id,
                    'courseid' => $context->instanceid,
                ], 'createdat DESC');
                self::export_user_logs($context, $logs);
            }
        }
    }

    /**
     * Export user logs to the specified context.
     *
     * @param   \context    $context    The context to export to.
     * @param   array       $logs       Array of log records.
     */
    private static function export_user_logs(\context $context, array $logs) {
        if (empty($logs)) {
            return;
        }

        $data = [];
        foreach ($logs as $log) {
            $logdata = [
                'session_id' => $log->sessionid,
                'course_id' => $log->courseid,
                'created_at' => transform::datetime($log->createdat),
                'status' => $log->status,
                'bytes_uploaded' => $log->bytes_up,
                'bytes_downloaded' => $log->bytes_down,
                'duration_ms' => $log->duration_ms,
                'ttff_ms' => $log->ttff_ms,
                'tokens_used' => $log->tokens_used,
            ];

            // Include error if present.
            if (!empty($log->error)) {
                $logdata['error'] = $log->error;
            }

            // Include content if logging is enabled and content exists.
            if (!empty($log->content)) {
                $logdata['content'] = $log->content;
            }

            $data[] = $logdata;
        }

        writer::with_context($context)->export_data(
            [get_string('pluginname', 'local_aiawesome')],
            (object) [
                'ai_interactions' => $data,
                'total_interactions' => count($data),
                'export_date' => transform::datetime(time()),
            ]
        );
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   \context                $context   The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context instanceof \context_system) {
            // Delete all logs (this should be used carefully!).
            $DB->delete_records('local_aiawesome_logs');
        } elseif ($context instanceof \context_course) {
            // Delete logs for this specific course.
            $DB->delete_records('local_aiawesome_logs', ['courseid' => $context->instanceid]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $contexts = $contextlist->get_contexts();

        foreach ($contexts as $context) {
            if ($context instanceof \context_system) {
                // Delete all logs for this user.
                \local_aiawesome\logging_service::delete_user_logs($user->id);
            } elseif ($context instanceof \context_course) {
                // Delete logs for this user in this specific course.
                global $DB;
                $DB->delete_records('local_aiawesome_logs', [
                    'userid' => $user->id,
                    'courseid' => $context->instanceid,
                ]);
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        if ($context instanceof \context_system) {
            // Delete logs for specified users.
            $DB->delete_records_select(
                'local_aiawesome_logs',
                "userid $insql",
                $inparams
            );
        } elseif ($context instanceof \context_course) {
            // Delete logs for specified users in this course.
            $params = array_merge($inparams, ['courseid' => $context->instanceid]);
            $DB->delete_records_select(
                'local_aiawesome_logs',
                "userid $insql AND courseid = :courseid",
                $params
            );
        }
    }
}
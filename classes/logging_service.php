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
 * Logging service for AI Awesome plugin.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aiawesome;

defined('MOODLE_INTERNAL') || die();

/**
 * Logging service class for tracking usage and performance.
 */
class logging_service {

    /**
     * Create a new log entry.
     *
     * @param string $sessionid Session identifier
     * @param int|null $courseid Course ID (null for system context)
     * @return int Log entry ID
     */
    public static function create_log_entry(string $sessionid, ?int $courseid = null): int {
        global $DB, $USER;

        if (!get_config('local_aiawesome', 'enable_logging')) {
            return 0; // Logging disabled.
        }

        $record = (object) [
            'userid' => $USER->id,
            'courseid' => $courseid,
            'sessionid' => $sessionid,
            'bytes_up' => 0,
            'bytes_down' => 0,
            'status' => 'pending',
            'error' => null,
            'content' => null,
            'createdat' => time(),
            'duration_ms' => null,
            'ttff_ms' => null,
            'tokens_used' => null,
            'prompt_tokens' => null,
            'completion_tokens' => null,
            'provider' => get_config('local_aiawesome', 'ai_provider'),
        ];

        return $DB->insert_record('local_aiawesome_logs', $record);
    }

    /**
     * Update log entry with completion data.
     *
     * @param int $logid Log entry ID
     * @param array $data Update data
     * @return bool Success status
     */
    public static function update_log_entry(int $logid, array $data): bool {
        global $DB;

        if (!$logid || !get_config('local_aiawesome', 'enable_logging')) {
            return false;
        }

        $allowed_fields = [
            'bytes_up', 'bytes_down', 'status', 'error', 'content',
            'duration_ms', 'ttff_ms', 'tokens_used', 'prompt_tokens', 
            'completion_tokens', 'provider'
        ];

        $update = (object) ['id' => $logid];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $update->$key = $value;
            }
        }

        // Only include content if explicitly enabled (privacy).
        if (isset($update->content) && !get_config('local_aiawesome', 'log_content')) {
            unset($update->content);
        }

        try {
            return $DB->update_record('local_aiawesome_logs', $update);
        } catch (\Exception $e) {
            debugging('AI Awesome: Failed to update log entry: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Log an error for a session.
     *
     * @param string $sessionid Session identifier
     * @param string $error Error message
     * @param int|null $courseid Course ID
     * @param array $additional Additional data
     * @return int Log entry ID
     */
    public static function log_error(string $sessionid, string $error, ?int $courseid = null, array $additional = []): int {
        $logid = self::create_log_entry($sessionid, $courseid);
        
        if ($logid) {
            $data = array_merge([
                'status' => 'error',
                'error' => $error,
            ], $additional);
            
            self::update_log_entry($logid, $data);
        }

        return $logid;
    }

    /**
     * Check rate limiting for a user.
     *
     * @param int $userid User ID
     * @return bool True if user is within rate limits
     */
    public static function check_rate_limit(int $userid): bool {
        global $DB;

        $ratelimit = get_config('local_aiawesome', 'rate_limit');
        if (!$ratelimit || $ratelimit <= 0) {
            return true; // No rate limiting configured.
        }

        // Check requests in the last hour.
        $since = time() - 3600;
        $count = $DB->count_records_select(
            'local_aiawesome_logs',
            'userid = ? AND createdat >= ?',
            [$userid, $since]
        );

        return $count < $ratelimit;
    }

    /**
     * Get usage statistics for a user.
     *
     * @param int $userid User ID
     * @param int $since Timestamp to count from
     * @return object Usage statistics
     */
    public static function get_user_usage(int $userid, int $since = 0): object {
        global $DB;

        if (!$since) {
            $since = time() - (7 * 24 * 3600); // Last week.
        }

        $sql = "SELECT 
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_requests,
                    COUNT(CASE WHEN status = 'error' THEN 1 END) as failed_requests,
                    SUM(bytes_up) as total_bytes_up,
                    SUM(bytes_down) as total_bytes_down,
                    SUM(tokens_used) as total_tokens,
                    AVG(duration_ms) as avg_duration,
                    AVG(ttff_ms) as avg_ttff
                FROM {local_aiawesome_logs}
                WHERE userid = ? AND createdat >= ?";

        $record = $DB->get_record_sql($sql, [$userid, $since]);
        
        return (object) [
            'total_requests' => (int) ($record->total_requests ?? 0),
            'successful_requests' => (int) ($record->successful_requests ?? 0),
            'failed_requests' => (int) ($record->failed_requests ?? 0),
            'total_bytes_up' => (int) ($record->total_bytes_up ?? 0),
            'total_bytes_down' => (int) ($record->total_bytes_down ?? 0),
            'total_tokens' => (int) ($record->total_tokens ?? 0),
            'avg_duration' => round((float) ($record->avg_duration ?? 0), 2),
            'avg_ttff' => round((float) ($record->avg_ttff ?? 0), 2),
            'period_start' => $since,
            'period_end' => time(),
        ];
    }

    /**
     * Get system-wide usage statistics (for admin reports).
     *
     * @param int $since Timestamp to count from
     * @return object System usage statistics
     */
    public static function get_system_usage(int $since = 0): object {
        global $DB;

        if (!$since) {
            $since = time() - (30 * 24 * 3600); // Last month.
        }

        $sql = "SELECT 
                    COUNT(*) as total_requests,
                    COUNT(DISTINCT userid) as unique_users,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_requests,
                    COUNT(CASE WHEN status = 'error' THEN 1 END) as failed_requests,
                    SUM(bytes_up) as total_bytes_up,
                    SUM(bytes_down) as total_bytes_down,
                    SUM(tokens_used) as total_tokens,
                    AVG(duration_ms) as avg_duration,
                    AVG(ttff_ms) as avg_ttff
                FROM {local_aiawesome_logs}
                WHERE createdat >= ?";

        $record = $DB->get_record_sql($sql, [$since]);
        
        return (object) [
            'total_requests' => (int) ($record->total_requests ?? 0),
            'unique_users' => (int) ($record->unique_users ?? 0),
            'successful_requests' => (int) ($record->successful_requests ?? 0),
            'failed_requests' => (int) ($record->failed_requests ?? 0),
            'success_rate' => $record->total_requests > 0 
                ? round(($record->successful_requests / $record->total_requests) * 100, 2) 
                : 0,
            'total_bytes_up' => (int) ($record->total_bytes_up ?? 0),
            'total_bytes_down' => (int) ($record->total_bytes_down ?? 0),
            'total_tokens' => (int) ($record->total_tokens ?? 0),
            'avg_duration' => round((float) ($record->avg_duration ?? 0), 2),
            'avg_ttff' => round((float) ($record->avg_ttff ?? 0), 2),
            'period_start' => $since,
            'period_end' => time(),
        ];
    }

    /**
     * Clean up old log entries based on retention policy.
     *
     * @param int $retention_days Number of days to retain logs
     * @return int Number of records deleted
     */
    public static function cleanup_old_logs(int $retention_days = 90): int {
        global $DB;

        $cutoff = time() - ($retention_days * 24 * 3600);
        
        return $DB->delete_records_select(
            'local_aiawesome_logs',
            'createdat < ?',
            [$cutoff]
        );
    }

    /**
     * Get logs for a specific user (for GDPR export).
     *
     * @param int $userid User ID
     * @return array Log records
     */
    public static function get_user_logs(int $userid): array {
        global $DB;

        return $DB->get_records(
            'local_aiawesome_logs',
            ['userid' => $userid],
            'createdat DESC'
        );
    }

    /**
     * Delete all logs for a specific user (for GDPR deletion).
     *
     * @param int $userid User ID
     * @return bool Success status
     */
    public static function delete_user_logs(int $userid): bool {
        global $DB;

        return $DB->delete_records('local_aiawesome_logs', ['userid' => $userid]);
    }

    /**
     * Get token usage statistics.
     *
     * @param int $since Timestamp to calculate stats from (default: 30 days ago)
     * @return object Statistics object
     */
    public static function get_token_statistics(int $since = null): object {
        global $DB;

        if ($since === null) {
            $since = time() - (30 * 24 * 3600); // Default: last 30 days.
        }

        // Get overall token stats.
        $sql = "SELECT 
                    COUNT(*) as total_requests,
                    SUM(COALESCE(prompt_tokens, 0)) as total_prompt_tokens,
                    SUM(COALESCE(completion_tokens, 0)) as total_completion_tokens,
                    SUM(COALESCE(tokens_used, 0)) as total_tokens,
                    AVG(COALESCE(prompt_tokens, 0)) as avg_prompt_tokens,
                    AVG(COALESCE(completion_tokens, 0)) as avg_completion_tokens
                FROM {local_aiawesome_logs}
                WHERE createdat >= :since
                  AND status = 'completed'";

        $stats = $DB->get_record_sql($sql, ['since' => $since]);

        // Get stats by provider.
        $sql = "SELECT 
                    provider,
                    COUNT(*) as requests,
                    SUM(COALESCE(prompt_tokens, 0)) as prompt_tokens,
                    SUM(COALESCE(completion_tokens, 0)) as completion_tokens,
                    SUM(COALESCE(tokens_used, 0)) as total_tokens
                FROM {local_aiawesome_logs}
                WHERE createdat >= :since
                  AND status = 'completed'
                  AND provider IS NOT NULL
                GROUP BY provider";

        $provider_stats = $DB->get_records_sql($sql, ['since' => $since]);

        // Get top users by token usage.
        $sql = "SELECT 
                    l.userid,
                    u.firstname,
                    u.lastname,
                    COUNT(*) as requests,
                    SUM(COALESCE(l.tokens_used, 0)) as total_tokens
                FROM {local_aiawesome_logs} l
                JOIN {user} u ON l.userid = u.id
                WHERE l.createdat >= :since
                  AND l.status = 'completed'
                GROUP BY l.userid, u.firstname, u.lastname
                ORDER BY total_tokens DESC
                LIMIT 10";

        $top_users = $DB->get_records_sql($sql, ['since' => $since]);

        // Calculate time-based breakdown (today, this week, this month).
        $today = strtotime('today');
        $week_ago = strtotime('-7 days');
        $month_ago = strtotime('-30 days');

        $today_stats = $DB->get_record_sql(
            "SELECT SUM(COALESCE(tokens_used, 0)) as tokens 
             FROM {local_aiawesome_logs} 
             WHERE createdat >= :since AND status = 'completed'",
            ['since' => $today]
        );

        $week_stats = $DB->get_record_sql(
            "SELECT SUM(COALESCE(tokens_used, 0)) as tokens 
             FROM {local_aiawesome_logs} 
             WHERE createdat >= :since AND status = 'completed'",
            ['since' => $week_ago]
        );

        $month_stats = $DB->get_record_sql(
            "SELECT SUM(COALESCE(tokens_used, 0)) as tokens 
             FROM {local_aiawesome_logs} 
             WHERE createdat >= :since AND status = 'completed'",
            ['since' => $month_ago]
        );

        return (object) [
            'total_requests' => (int) ($stats->total_requests ?? 0),
            'total_tokens' => (int) ($stats->total_tokens ?? 0),
            'total_prompt_tokens' => (int) ($stats->total_prompt_tokens ?? 0),
            'total_completion_tokens' => (int) ($stats->total_completion_tokens ?? 0),
            'avg_prompt_tokens' => round((float) ($stats->avg_prompt_tokens ?? 0), 1),
            'avg_completion_tokens' => round((float) ($stats->avg_completion_tokens ?? 0), 1),
            'tokens_today' => (int) ($today_stats->tokens ?? 0),
            'tokens_this_week' => (int) ($week_stats->tokens ?? 0),
            'tokens_this_month' => (int) ($month_stats->tokens ?? 0),
            'by_provider' => $provider_stats,
            'top_users' => $top_users,
            'period_start' => $since,
            'period_end' => time(),
        ];
    }
}
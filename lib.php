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
 * Library functions for AI Awesome plugin.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Legacy before_http_headers callback (for compatibility).
 *
 * @return void
 */
function local_aiawesome_before_http_headers() {
    global $PAGE, $USER;

    // Debug logging
    error_log("AI Awesome: before_http_headers called for user " . ($USER->id ?? 'unknown'));

    // Don't load on login page, install pages, etc.
    if (!isloggedin() || isguestuser() || during_initial_install()) {
        error_log("AI Awesome: User not logged in or is guest");
        return;
    }

    // Don't load if plugin is disabled.
    if (!get_config('local_aiawesome', 'enabled')) {
        error_log("AI Awesome: Plugin disabled");
        return;
    }

    // Check if user has permission to use the chat.
    $context = context_system::instance();
    if (!has_capability('local/aiawesome:use', $context)) {
        error_log("AI Awesome: User lacks capability");
        return;
    }

    error_log("AI Awesome: Adding assets to page");

    // Add CSS
    $PAGE->requires->css('/local/aiawesome/styles.css');

    // Add JavaScript module
    $PAGE->requires->js_call_amd('local_aiawesome/boot', 'init');

    // Add data attribute to body to indicate feature is available
    $PAGE->add_body_class('aiawesome-enabled');
}

/**
 * Serves the plugin files.
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param context $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file was not found, just send the file otherwise and do not return anything
 */
function local_aiawesome_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    // No files are served by this plugin currently.
    return false;
}

/**
 * Add navigation node for AI Awesome settings.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to extend navigation for
 * @param context $context The context of the course
 */
function local_aiawesome_extend_navigation_course($navigation, $course, $context) {
    // Could add course-specific AI settings here in the future
}

/**
 * Cleanup function called during uninstall.
 *
 * @return bool
 */
function local_aiawesome_uninstall() {
    global $DB;

    // Remove all configuration
    $DB->delete_records('config_plugins', ['plugin' => 'local_aiawesome']);

    // Clear caches
    cache_helper::purge_by_definition('local_aiawesome', 'token_cache');
    cache_helper::purge_by_definition('local_aiawesome', 'config_cache');
    cache_helper::purge_by_definition('local_aiawesome', 'rate_limit_cache');

    return true;
}

/**
 * Get the current context information for AI requests.
 *
 * @param int|null $courseid Optional course ID
 * @return array Context information
 */
function local_aiawesome_get_context($courseid = null) {
    global $USER, $COURSE;

    $context = [
        'userId' => $USER->id,
        'userInfo' => [
            'fullname' => fullname($USER),
            'username' => $USER->username,
        ],
    ];

    // Add course information if available
    if ($courseid && $courseid != SITEID) {
        try {
            $course = get_course($courseid);
            $context['courseId'] = $course->id;
            $context['courseName'] = $course->fullname;
            $context['courseShortName'] = $course->shortname;

            // Get user's enrolled courses
            $enrolledcourses = enrol_get_users_courses($USER->id, true, 'id,fullname,shortname');
            $context['enrolledCourseIds'] = array_keys($enrolledcourses);
            $context['enrolledCourses'] = array_map(function($course) {
                return [
                    'id' => $course->id,
                    'fullname' => $course->fullname,
                    'shortname' => $course->shortname,
                ];
            }, $enrolledcourses);

        } catch (Exception $e) {
            // Course not accessible, continue without course context
            debugging('AI Awesome: Could not load course context: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    } else if ($COURSE && $COURSE->id != SITEID) {
        // Use current course if no specific course ID provided
        $context['courseId'] = $COURSE->id;
        $context['courseName'] = $COURSE->fullname;
        $context['courseShortName'] = $COURSE->shortname;
    }

    return $context;
}

/**
 * Check if the current user can use AI chat.
 *
 * @param context|null $context Context to check (defaults to system)
 * @return bool Whether user can use AI chat
 */
function local_aiawesome_can_use($context = null) {
    if (!$context) {
        $context = context_system::instance();
    }

    // Check if plugin is enabled
    if (!get_config('local_aiawesome', 'enabled')) {
        return false;
    }

    // Check capabilities
    return has_capability('local/aiawesome:use', $context);
}

/**
 * Check if the current user can view AI chat interface.
 *
 * @param context|null $context Context to check (defaults to system)
 * @return bool Whether user can view AI chat
 */
function local_aiawesome_can_view($context = null) {
    if (!$context) {
        $context = context_system::instance();
    }

    // Check if plugin is enabled
    if (!get_config('local_aiawesome', 'enabled')) {
        return false;
    }

    // Check capabilities - use the same capability as 'use'
    return has_capability('local/aiawesome:use', $context);
}
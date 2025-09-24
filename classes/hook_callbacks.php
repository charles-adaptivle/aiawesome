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
 * Hook callbacks for AI Awesome plugin.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aiawesome;

/**
 * Hook callbacks class.
 */
class hook_callbacks {

    /**
     * Callback for before HTTP headers hook to add CSS and JS requirements.
     *
     * @param \core\hook\output\before_http_headers $hook The hook instance
     */
    public static function before_http_headers(\core\hook\output\before_http_headers $hook): void {
        global $PAGE, $USER;

        // Don't load on login page, install pages, etc.
        if (!isloggedin() || isguestuser() || during_initial_install()) {
            return;
        }

        // Don't load if plugin is disabled.
        if (!get_config('local_aiawesome', 'enabled')) {
            return;
        }

        // Check if user has permission to use the chat.
        $context = \context_system::instance();
        if (!has_capability('local/aiawesome:use', $context)) {
            return;
        }

        // Add CSS
        $PAGE->requires->css('/local/aiawesome/styles.css');

        // Add JavaScript module
        $PAGE->requires->js_call_amd('local_aiawesome/boot', 'init');

        // Add data attribute to body to indicate feature is available
        $PAGE->add_body_class('aiawesome-enabled');
    }

    /**
     * Callback for before footer HTML generation hook.
     *
     * @param \core\hook\output\before_footer_html_generation $hook The hook instance
     */
    public static function before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook): void {
        // This hook is kept for any future footer-specific functionality
        // Currently, all initialization is done in before_http_headers
    }
}
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
 * Plugin upgrade steps are defined here.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute local_aiawesome upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_aiawesome_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Migrate from two-mode (auth_mode) to three-provider architecture.
    if ($oldversion < 2025092401) {
        
        // Load migration helper and perform migration.
        require_once(__DIR__ . '/../classes/migration_helper.php');
        
        $migrated = \local_aiawesome\migration_helper::migrate_to_three_providers();
        
        if ($migrated) {
            mtrace('AI Awesome: Successfully migrated to three-provider architecture');
        } else {
            mtrace('AI Awesome: Migration not needed or already completed');
        }
        
        // AI Awesome savepoint reached.
        upgrade_plugin_savepoint(true, 2025092401, 'local', 'aiawesome');
    }

    // Add future upgrade steps here as needed.
    // Example:
    // if ($oldversion < 2025100200) {
    //     // Perform upgrade step
    //     upgrade_plugin_savepoint(true, 2025100200, 'local', 'aiawesome');
    // }

    // Add token tracking fields and provider field.
    if ($oldversion < 2025100101) {
        $table = new xmldb_table('local_aiawesome_logs');
        
        // Add prompt_tokens field.
        $field = new xmldb_field('prompt_tokens', XMLDB_TYPE_INTEGER, '10', null, false, null, null, 'tokens_used');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Add completion_tokens field.
        $field = new xmldb_field('completion_tokens', XMLDB_TYPE_INTEGER, '10', null, false, null, null, 'prompt_tokens');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Add provider field.
        $field = new xmldb_field('provider', XMLDB_TYPE_CHAR, '50', null, false, null, null, 'completion_tokens');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        mtrace('AI Awesome: Added token tracking fields (prompt_tokens, completion_tokens, provider)');
        
        // AI Awesome savepoint reached.
        upgrade_plugin_savepoint(true, 2025100101, 'local', 'aiawesome');
    }

    return true;
}

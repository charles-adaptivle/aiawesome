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
 * Migration helper for AI Awesome plugin three-provider architecture.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aiawesome;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for migrating from old configuration structure to three-provider architecture.
 */
class migration_helper {

    /**
     * Migrate existing configuration to three-provider architecture.
     * This method should be called during plugin upgrade.
     *
     * @return bool True if migration was performed, false if no migration needed
     */
    public static function migrate_to_three_providers() {
        global $DB;
        
        // Check if ai_provider is already set
        $provider = get_config('local_aiawesome', 'ai_provider');
        if (!empty($provider)) {
            // Migration already done
            return false;
        }

        // Check old configurations to determine provider
        $auth_mode = get_config('local_aiawesome', 'auth_mode');
        $digitalocean_endpoint = get_config('local_aiawesome', 'digitalocean_endpoint');
        $openai_api_key = get_config('local_aiawesome', 'openai_api_key');
        $client_id = get_config('local_aiawesome', 'client_id');
        $base_url = get_config('local_aiawesome', 'base_url');

        // Determine provider based on existing configuration
        if (!empty($digitalocean_endpoint)) {
            // DigitalOcean provider was configured
            set_config('ai_provider', 'digitalocean', 'local_aiawesome');
        } else if ($auth_mode === 'oauth' && (!empty($client_id) || !empty($base_url))) {
            // OAuth was configured - migrate to custom_oauth provider
            set_config('ai_provider', 'custom_oauth', 'local_aiawesome');
            
            // Migrate OAuth settings to new keys
            if (!empty($client_id)) {
                set_config('oauth_client_id', $client_id, 'local_aiawesome');
            }
            if (!empty(get_config('local_aiawesome', 'client_secret'))) {
                set_config('oauth_client_secret', get_config('local_aiawesome', 'client_secret'), 'local_aiawesome');
            }
            if (!empty($base_url)) {
                set_config('oauth_base_url', $base_url, 'local_aiawesome');
            }
            if (!empty(get_config('local_aiawesome', 'token_url'))) {
                set_config('oauth_token_url', get_config('local_aiawesome', 'token_url'), 'local_aiawesome');
            }
            if (!empty(get_config('local_aiawesome', 'app_id'))) {
                set_config('oauth_app_id', get_config('local_aiawesome', 'app_id'), 'local_aiawesome');
            }
        } else {
            // Default to OpenAI (direct API key mode)
            set_config('ai_provider', 'openai', 'local_aiawesome');
        }

        // Clear old configurations that are no longer used
        unset_config('auth_mode', 'local_aiawesome');
        
        return true;
    }

    /**
     * Check if migration is needed.
     *
     * @return bool True if migration is needed
     */
    public static function needs_migration() {
        $provider = get_config('local_aiawesome', 'ai_provider');
        return empty($provider);
    }

    /**
     * Get migration status and recommendations.
     *
     * @return array Status information
     */
    public static function get_migration_status() {
        if (!self::needs_migration()) {
            return [
                'needed' => false,
                'current_provider' => get_config('local_aiawesome', 'ai_provider'),
                'message' => 'Configuration is up to date'
            ];
        }

        // Analyze current configuration
        $auth_mode = get_config('local_aiawesome', 'auth_mode');
        $digitalocean_endpoint = get_config('local_aiawesome', 'digitalocean_endpoint');
        $openai_api_key = get_config('local_aiawesome', 'openai_api_key');
        $client_id = get_config('local_aiawesome', 'client_id');
        $base_url = get_config('local_aiawesome', 'base_url');

        $recommendations = [];
        $likely_provider = 'openai'; // default

        if (!empty($digitalocean_endpoint)) {
            $likely_provider = 'digitalocean';
            $recommendations[] = 'DigitalOcean endpoint detected - will migrate to DigitalOcean provider';
        } else if ($auth_mode === 'oauth' && (!empty($client_id) || !empty($base_url))) {
            $likely_provider = 'custom_oauth';
            $recommendations[] = 'OAuth configuration detected - will migrate to Custom OAuth provider';
            $recommendations[] = 'OAuth settings will be moved to oauth_* configuration keys';
        } else if (!empty($openai_api_key)) {
            $recommendations[] = 'OpenAI API key detected - will use OpenAI provider';
        } else {
            $recommendations[] = 'No specific configuration detected - will default to OpenAI provider';
        }

        return [
            'needed' => true,
            'likely_provider' => $likely_provider,
            'recommendations' => $recommendations,
            'message' => 'Migration needed to three-provider architecture'
        ];
    }
}
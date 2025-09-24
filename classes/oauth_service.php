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
 * OAuth2 service for AI Awesome plugin.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aiawesome;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * OAuth2 service class for handling client credentials flow.
 */
class oauth_service {

    /** @var string Cache key prefix for tokens */
    private const CACHE_KEY_PREFIX = 'oauth_token_';

    /** @var \cache Cache instance for tokens */
    private $tokencache;

    /** @var \cache Cache instance for config */
    private $configcache;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->tokencache = \cache::make('local_aiawesome', 'token_cache');
        $this->configcache = \cache::make('local_aiawesome', 'config_cache');
    }

    /**
     * Get a valid access token, either from cache or by requesting a new one.
     *
     * @return object|false Token object with access_token, expires_at, sub properties, or false on failure
     */
    public function get_access_token() {
        global $CFG;

        // Check if we have a cached valid token.
        $cachekey = self::CACHE_KEY_PREFIX . $CFG->dbname;
        $cachedtoken = $this->tokencache->get($cachekey);
        
        if ($cachedtoken && $this->is_token_valid($cachedtoken)) {
            return $cachedtoken;
        }

        // Need to fetch a new token.
        return $this->fetch_new_token();
    }

    /**
     * Fetch a new access token using client credentials flow.
     *
     * @return object|false Token object or false on failure
     */
    private function fetch_new_token() {
        global $CFG;

        $config = $this->get_config();
        if (!$config) {
            debugging('AI Awesome: OAuth configuration is incomplete', DEBUG_DEVELOPER);
            return false;
        }

        $curl = new \curl();
        $curl->setopt([
            'CURLOPT_TIMEOUT' => 30,
            'CURLOPT_CONNECTTIMEOUT' => 10,
        ]);

        $postdata = [
            'grant_type' => 'client_credentials',
            'client_id' => $config->client_id,
            'client_secret' => $config->client_secret,
            'scope' => 'api:read api:write', // Default scope, may need adjustment.
        ];

        $response = $curl->post($config->token_url, $postdata);
        
        if ($curl->get_errno()) {
            debugging('AI Awesome: cURL error during token request: ' . $curl->error, DEBUG_DEVELOPER);
            return false;
        }

        $httpcode = $curl->get_info()['http_code'];
        if ($httpcode !== 200) {
            debugging('AI Awesome: OAuth token request failed with HTTP ' . $httpcode . ': ' . $response, DEBUG_DEVELOPER);
            return false;
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['access_token'])) {
            debugging('AI Awesome: Invalid token response format', DEBUG_DEVELOPER);
            return false;
        }

        // Decode JWT to get expiry and sub claim.
        $tokeninfo = $this->decode_jwt_payload($data['access_token']);
        if (!$tokeninfo) {
            debugging('AI Awesome: Unable to decode JWT token', DEBUG_DEVELOPER);
            return false;
        }

        $token = (object) [
            'access_token' => $data['access_token'],
            'token_type' => $data['token_type'] ?? 'Bearer',
            'expires_at' => $tokeninfo->exp ?? (time() + 3600),
            'sub' => $tokeninfo->sub ?? '',
            'scope' => $data['scope'] ?? '',
        ];

        // Cache the token with appropriate TTL.
        $cachekey = self::CACHE_KEY_PREFIX . $CFG->dbname;
        $cachettl = max(60, $token->expires_at - time() - 300); // 5 min buffer.
        $this->tokencache->set($cachekey, $token);

        return $token;
    }

    /**
     * Check if a token is still valid (not expired).
     *
     * @param object $token Token object
     * @return bool True if valid
     */
    private function is_token_valid($token) {
        if (!$token || !isset($token->expires_at)) {
            return false;
        }

        // Check if token expires in next 5 minutes.
        return $token->expires_at > (time() + 300);
    }

    /**
     * Decode JWT payload (simple base64 decode - not validating signature).
     *
     * @param string $jwt JWT token
     * @return object|false Decoded payload or false
     */
    private function decode_jwt_payload($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return false;
        }

        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
        if (!$payload) {
            return false;
        }

        return json_decode($payload);
    }

    /**
     * Get plugin configuration from cache or database.
     *
     * @return object|false Config object or false if incomplete
     */
    private function get_config() {
        $config = $this->configcache->get('oauth_config');
        
        if (!$config) {
            $config = (object) [
                'token_url' => get_config('local_aiawesome', 'token_url'),
                'client_id' => get_config('local_aiawesome', 'client_id'),
                'client_secret' => get_config('local_aiawesome', 'client_secret'),
                'base_url' => get_config('local_aiawesome', 'base_url'),
                'app_id' => get_config('local_aiawesome', 'app_id'),
            ];
            
            $this->configcache->set('oauth_config', $config);
        }

        // Validate required fields.
        if (empty($config->token_url) || empty($config->client_id) || empty($config->client_secret)) {
            return false;
        }

        return $config;
    }

    /**
     * Clear cached token (useful for testing or after config changes).
     *
     * @return void
     */
    public function clear_token_cache() {
        global $CFG;
        $cachekey = self::CACHE_KEY_PREFIX . $CFG->dbname;
        $this->tokencache->delete($cachekey);
        $this->configcache->purge();
    }

    /**
     * Get authorization header for API requests.
     *
     * @return string|false Authorization header value or false
     */
    public function get_auth_header() {
        $token = $this->get_access_token();
        if (!$token) {
            return false;
        }

        return ($token->token_type ?? 'Bearer') . ' ' . $token->access_token;
    }
}
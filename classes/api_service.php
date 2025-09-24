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
 * API service for AI Awesome plugin supporting multiple authentication methods.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aiawesome;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * API service class for handling both OAuth and direct API token authentication.
 */
class api_service {

    /** @var string Authentication mode: 'oauth' or 'token' */
    private $auth_mode;

    /** @var oauth_service OAuth service instance */
    private $oauth_service;

    /** @var \cache Cache instance for config */
    private $configcache;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->configcache = \cache::make('local_aiawesome', 'config_cache');
        $this->auth_mode = get_config('local_aiawesome', 'auth_mode') ?: 'oauth';
        
        if ($this->auth_mode === 'oauth') {
            $this->oauth_service = new oauth_service();
        }
    }

    /**
     * Get authorization header for API requests.
     *
     * @return string|false Authorization header value or false
     */
    public function get_auth_header() {
        if ($this->auth_mode === 'oauth') {
            return $this->oauth_service->get_auth_header();
        } else {
            // Token mode (e.g., OpenAI).
            $api_key = get_config('local_aiawesome', 'openai_api_key');
            if (empty($api_key)) {
                debugging('AI Awesome: OpenAI API key not configured', DEBUG_DEVELOPER);
                return false;
            }
            return 'Bearer ' . $api_key;
        }
    }

    /**
     * Get API endpoint URL for chat completions.
     *
     * @return string|false API endpoint URL or false
     */
    public function get_api_endpoint() {
        if ($this->auth_mode === 'oauth') {
            $config = $this->get_oauth_config();
            if (!$config || empty($config->base_url)) {
                return false;
            }
            return rtrim($config->base_url, '/') . '/chat/completions';
        } else {
            // OpenAI mode.
            $api_base = get_config('local_aiawesome', 'openai_api_base') ?: 'https://api.openai.com/v1';
            return rtrim($api_base, '/') . '/chat/completions';
        }
    }

    /**
     * Get additional headers needed for API requests.
     *
     * @return array Additional headers
     */
    public function get_additional_headers() {
        $headers = ['Content-Type' => 'application/json'];
        
        if ($this->auth_mode === 'token') {
            // OpenAI specific headers.
            $organization = get_config('local_aiawesome', 'openai_organization');
            $project = get_config('local_aiawesome', 'openai_project');
            
            if (!empty($organization)) {
                $headers['OpenAI-Organization'] = $organization;
            }
            
            if (!empty($project)) {
                $headers['OpenAI-Project'] = $project;
            }
        }
        
        return $headers;
    }

    /**
     * Prepare request payload for chat completion.
     *
     * @param string $message User message
     * @param array $context Additional context information
     * @return array Request payload
     */
    public function prepare_chat_payload($message, $context = []) {
        $config = $this->get_chat_config();
        
        if ($this->auth_mode === 'oauth') {
            // Custom service format (adjust as needed).
            return [
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful AI assistant for a Moodle learning management system. ' .
                                   'Provide helpful, educational responses based on the provided context.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ],
                'max_tokens' => (int)$config->max_tokens,
                'temperature' => (float)$config->temperature,
                'context' => $context,
                'app_id' => $config->app_id,
            ];
        } else {
            // OpenAI format.
            $model = get_config('local_aiawesome', 'openai_model') ?: 'gpt-4o-mini';
            
            $system_message = 'You are a helpful AI assistant integrated into a Moodle learning management system. ';
            if (!empty($context['courseName'])) {
                $system_message .= 'The user is currently in the course: "' . $context['courseName'] . '". ';
            }
            $system_message .= 'Provide helpful, educational responses that are appropriate for the learning context.';
            
            return [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $system_message
                    ],
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ],
                'max_tokens' => (int)$config->max_tokens,
                'temperature' => (float)$config->temperature,
                'stream' => true, // Enable streaming for real-time responses.
            ];
        }
    }

    /**
     * Send chat completion request.
     *
     * @param array $payload Request payload
     * @return array Response data or error information
     */
    public function send_chat_request($payload) {
        $endpoint = $this->get_api_endpoint();
        $auth_header = $this->get_auth_header();
        $additional_headers = $this->get_additional_headers();
        
        if (!$endpoint || !$auth_header) {
            return ['error' => 'API configuration incomplete'];
        }
        
        $curl = new \curl();
        $curl->setopt([
            'CURLOPT_TIMEOUT' => 60,
            'CURLOPT_CONNECTTIMEOUT' => 10,
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_MAXREDIRS' => 3,
        ]);
        
        // Set headers.
        $headers = [
            'Authorization: ' . $auth_header,
        ];
        
        foreach ($additional_headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        
        $curl->setHeader($headers);
        
        // Send request.
        $response = $curl->post($endpoint, json_encode($payload));
        $httpcode = $curl->get_info()['http_code'] ?? 0;
        
        if ($curl->get_errno()) {
            return [
                'error' => 'Network error: ' . $curl->error,
                'error_code' => $curl->get_errno()
            ];
        }
        
        if ($httpcode < 200 || $httpcode >= 300) {
            return [
                'error' => 'API error (HTTP ' . $httpcode . '): ' . $response,
                'error_code' => $httpcode
            ];
        }
        
        $data = json_decode($response, true);
        if (!$data) {
            return ['error' => 'Invalid JSON response'];
        }
        
        return ['success' => true, 'data' => $data];
    }

    /**
     * Get OAuth configuration.
     *
     * @return object|false Config object or false
     */
    private function get_oauth_config() {
        $config = $this->configcache->get('oauth_config');
        
        if (!$config) {
            $config = (object) [
                'base_url' => get_config('local_aiawesome', 'base_url'),
                'app_id' => get_config('local_aiawesome', 'app_id'),
            ];
            
            $this->configcache->set('oauth_config', $config);
        }
        
        return $config;
    }

    /**
     * Get chat configuration.
     *
     * @return object Chat configuration
     */
    private function get_chat_config() {
        $config = $this->configcache->get('chat_config');
        
        if (!$config) {
            $config = (object) [
                'max_tokens' => get_config('local_aiawesome', 'max_tokens') ?: 2000,
                'temperature' => get_config('local_aiawesome', 'temperature') ?: 0.7,
                'app_id' => get_config('local_aiawesome', 'app_id'),
            ];
            
            $this->configcache->set('chat_config', $config);
        }
        
        return $config;
    }

    /**
     * Test API connection.
     *
     * @return array Test result with success/error information
     */
    public function test_connection() {
        $test_payload = $this->prepare_chat_payload('Hello, this is a test message.');
        
        // For testing, we'll send a simple request.
        if ($this->auth_mode === 'token') {
            // Remove streaming for test.
            $test_payload['stream'] = false;
        }
        
        $result = $this->send_chat_request($test_payload);
        
        if (isset($result['success'])) {
            return [
                'success' => true,
                'message' => 'API connection successful',
                'mode' => $this->auth_mode
            ];
        } else {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Unknown error',
                'mode' => $this->auth_mode
            ];
        }
    }

    /**
     * Clear caches.
     *
     * @return void
     */
    public function clear_cache() {
        $this->configcache->purge();
        if ($this->oauth_service) {
            $this->oauth_service->clear_token_cache();
        }
    }
}
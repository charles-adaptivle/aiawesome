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
 * API service class for handling multiple AI providers and authentication methods.
 */
class api_service {

    /** @var string AI provider: 'openai', 'custom_oauth', or 'digitalocean' */
    private $provider;

    /** @var oauth_service OAuth service instance */
    private $oauth_service;

    /** @var \cache Cache instance for config */
    private $configcache;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->configcache = \cache::make('local_aiawesome', 'config_cache');
        $this->provider = get_config('local_aiawesome', 'ai_provider') ?: 'openai';
        
        // Initialize OAuth service for custom_oauth provider
        if ($this->provider === 'custom_oauth') {
            $this->oauth_service = new oauth_service();
        }
    }

    /**
     * Get authorization header for API requests.
     *
     * @return string|false Authorization header value or false
     */
    public function get_auth_header() {
        switch ($this->provider) {
            case 'openai':
                return $this->get_openai_auth_header();
            case 'custom_oauth':
                return $this->get_custom_oauth_auth_header();
            case 'digitalocean':
                return $this->get_digitalocean_auth_header();
            case 'digitalocean_agent':
                return $this->get_digitalocean_agent_auth_header();
            default:
                debugging('AI Awesome: Unknown provider: ' . $this->provider, DEBUG_DEVELOPER);
                return false;
        }
    }

    /**
     * Get OpenAI authorization header (direct API key).
     *
     * @return string|false Authorization header value or false
     */
    private function get_openai_auth_header() {
        $api_key = get_config('local_aiawesome', 'openai_api_key');
        if (empty($api_key)) {
            debugging('AI Awesome: OpenAI API key not configured', DEBUG_DEVELOPER);
            return false;
        }
        return 'Bearer ' . $api_key;
    }

    /**
     * Get Custom OAuth authorization header.
     *
     * @return string|false Authorization header value or false
     */
    private function get_custom_oauth_auth_header() {
        if (!$this->oauth_service) {
            debugging('AI Awesome: OAuth service not initialized', DEBUG_DEVELOPER);
            return false;
        }
        return $this->oauth_service->get_auth_header();
    }

    /**
     * Get DigitalOcean authorization header.
     *
     * @return string|false Authorization header value or false
     */
    private function get_digitalocean_auth_header() {
        $api_key = get_config('local_aiawesome', 'digitalocean_api_key');
        if (!empty($api_key)) {
            return 'Bearer ' . $api_key;
        }
        // No authentication required for some endpoints
        return '';
    }

    /**
     * Get DigitalOcean Agent Platform authorization header.
     *
     * @return string|false Authorization header value or false
     */
    private function get_digitalocean_agent_auth_header() {
        $api_key = get_config('local_aiawesome', 'digitalocean_agent_api_key');
        if (empty($api_key)) {
            debugging('AI Awesome: DigitalOcean Agent API key not configured', DEBUG_DEVELOPER);
            return false;
        }
        return 'Bearer ' . $api_key;
    }

    /**
     * Get API endpoint URL for chat completions.
     *
     * @return string|false API endpoint URL or false
     */
    public function get_api_endpoint() {
        switch ($this->provider) {
            case 'openai':
                return $this->get_openai_endpoint();
            case 'custom_oauth':
                return $this->get_custom_oauth_endpoint();
            case 'digitalocean':
                return $this->get_digitalocean_endpoint();
            case 'digitalocean_agent':
                return $this->get_digitalocean_agent_endpoint();
            default:
                debugging('AI Awesome: Unknown provider: ' . $this->provider, DEBUG_DEVELOPER);
                return false;
        }
    }

    /**
     * Get OpenAI API endpoint.
     *
     * @return string|false API endpoint URL or false
     */
    private function get_openai_endpoint() {
        return 'https://api.openai.com/v1/chat/completions';
    }

    /**
     * Get Custom OAuth API endpoint.
     *
     * @return string|false API endpoint URL or false
     */
    private function get_custom_oauth_endpoint() {
        $base_url = get_config('local_aiawesome', 'oauth_base_url');
        if (empty($base_url)) {
            debugging('AI Awesome: OAuth base URL not configured', DEBUG_DEVELOPER);
            return false;
        }
        return rtrim($base_url, '/') . '/chat/completions';
    }

    /**
     * Get DigitalOcean API endpoint.
     *
     * @return string|false API endpoint URL or false
     */
    private function get_digitalocean_endpoint() {
        $endpoint = get_config('local_aiawesome', 'digitalocean_endpoint');
        if (empty($endpoint)) {
            debugging('AI Awesome: DigitalOcean endpoint not configured', DEBUG_DEVELOPER);
            return false;
        }

        // Ensure proper endpoint format
        $endpoint = rtrim($endpoint, '/');
        if (strpos($endpoint, '/v1/chat/completions') === false) {
            $endpoint .= '/v1/chat/completions';
        }

        return $endpoint;
    }

    /**
     * Get DigitalOcean Agent Platform API endpoint.
     *
     * @return string|false API endpoint URL or false
     */
    private function get_digitalocean_agent_endpoint() {
        $endpoint = get_config('local_aiawesome', 'digitalocean_agent_endpoint');
        if (empty($endpoint)) {
            debugging('AI Awesome: DigitalOcean Agent endpoint not configured', DEBUG_DEVELOPER);
            return false;
        }
        // Agent Platform uses /api/v1/chat/completions path
        return rtrim($endpoint, '/') . '/api/v1/chat/completions';
    }

    /**
     * Get additional headers needed for API requests.
     *
     * @return array Additional headers
     */
    public function get_additional_headers() {
        $headers = ['Content-Type' => 'application/json'];
        
        switch ($this->provider) {
            case 'openai':
                return array_merge($headers, $this->get_openai_additional_headers());
            case 'custom_oauth':
                return array_merge($headers, $this->get_custom_oauth_additional_headers());
            case 'digitalocean':
                return array_merge($headers, $this->get_digitalocean_additional_headers());
            case 'digitalocean_agent':
                return array_merge($headers, $this->get_digitalocean_agent_additional_headers());
            default:
                return $headers;
        }
    }

    /**
     * Get OpenAI-specific additional headers.
     *
     * @return array Additional headers
     */
    private function get_openai_additional_headers() {
        $headers = [];
        
        // OpenAI specific headers.
        $organization = get_config('local_aiawesome', 'openai_organization');
        $project = get_config('local_aiawesome', 'openai_project');
        
        if (!empty($organization)) {
            $headers['OpenAI-Organization'] = $organization;
        }
        
        if (!empty($project)) {
            $headers['OpenAI-Project'] = $project;
        }
        
        return $headers;
    }

    /**
     * Get Custom OAuth-specific additional headers.
     *
     * @return array Additional headers
     */
    private function get_custom_oauth_additional_headers() {
        // Return empty array - OAuth service handles authentication headers
        return [];
    }

    /**
     * Get DigitalOcean-specific additional headers.
     *
     * @return array Additional headers
     */
    private function get_digitalocean_additional_headers() {
        $headers = [];
        
        // Parse custom headers from configuration
        $custom_headers = get_config('local_aiawesome', 'digitalocean_headers');
        if (!empty($custom_headers)) {
            $lines = explode("\n", $custom_headers);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, ':') === false) {
                    continue;
                }
                [$name, $value] = explode(':', $line, 2);
                $headers[trim($name)] = trim($value);
            }
        }
        
        return $headers;
    }

    /**
     * Get DigitalOcean Agent Platform-specific additional headers.
     *
     * @return array Additional headers
     */
    private function get_digitalocean_agent_additional_headers() {
        // Agent Platform uses standard bearer auth, no additional headers needed
        return [];
    }

    /**
     * Prepare request payload for chat completion.
     *
     * @param string $message User message
     * @param array $context Additional context information
     * @return array Request payload
     */
    public function prepare_chat_payload($message, $context = []) {
        switch ($this->provider) {
            case 'openai':
                return $this->prepare_openai_payload($message, $context);
            case 'custom_oauth':
                return $this->prepare_custom_oauth_payload($message, $context);
            case 'digitalocean':
                return $this->prepare_digitalocean_payload($message, $context);
            case 'digitalocean_agent':
                return $this->prepare_digitalocean_agent_payload($message, $context);
            default:
                throw new \Exception('Unknown AI provider: ' . $this->provider);
        }
    }

    /**
     * Prepare OpenAI-specific request payload.
     *
     * @param string $message User message
     * @param array $context Additional context information
     * @return array Request payload
     */
    private function prepare_openai_payload($message, $context = []) {
        $config = $this->get_chat_config();
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
            'stream' => true,
            'stream_options' => [
                'include_usage' => true  // Request usage data in streaming response
            ],
        ];
    }

    /**
     * Prepare Custom OAuth service-specific request payload.
     *
     * @param string $message User message
     * @param array $context Additional context information
     * @return array Request payload
     */
    private function prepare_custom_oauth_payload($message, $context = []) {
        $config = $this->get_chat_config();
        
        // Custom service format (adjust as needed for your specific OAuth service)
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
            'app_id' => get_config('local_aiawesome', 'oauth_app_id'),
        ];
    }

    /**
     * Prepare DigitalOcean-specific request payload.
     *
     * @param string $message User message
     * @param array $context Additional context information
     * @return array Request payload
     */
    private function prepare_digitalocean_payload($message, $context = []) {
        $config = $this->get_chat_config();
        $model = get_config('local_aiawesome', 'digitalocean_model') ?: 'llama3.1:8b';
        
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
            'stream' => true,
        ];
    }

    /**
     * Prepare DigitalOcean Agent Platform-specific request payload.
     *
     * @param string $message User message
     * @param array $context Additional context information
     * @return array Request payload
     */
    private function prepare_digitalocean_agent_payload($message, $context = []) {
        $config = $this->get_chat_config();
        
        // DigitalOcean Agent Platform doesn't require a model field
        // The agent has its model pre-configured
        // Build context-aware system message
        $system_message = 'You are a helpful AI assistant integrated into a Moodle learning management system. ';
        if (!empty($context['courseName'])) {
            $system_message .= 'The user is currently in the course: "' . $context['courseName'] . '". ';
        }
        if (!empty($context['userInfo']['fullname'])) {
            $system_message .= 'The user\'s name is ' . $context['userInfo']['fullname'] . '. ';
        }
        $system_message .= 'Provide helpful, educational responses that are appropriate for the learning context.';
        
        return [
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
            'stream' => true,
        ];
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
                'base_url' => get_config('local_aiawesome', 'oauth_base_url'),
                'app_id' => get_config('local_aiawesome', 'oauth_app_id'),
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
        
        // For testing, remove streaming for OpenAI, DigitalOcean, and DigitalOcean Agent providers.
        if (in_array($this->provider, ['openai', 'digitalocean', 'digitalocean_agent'])) {
            $test_payload['stream'] = false;
            // Remove stream_options when not streaming (OpenAI requires this).
            unset($test_payload['stream_options']);
        }
        
        $result = $this->send_chat_request($test_payload);
        
        if (isset($result['success'])) {
            return [
                'success' => true,
                'message' => 'API connection successful',
                'mode' => $this->provider
            ];
        } else {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Unknown error',
                'mode' => $this->provider
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
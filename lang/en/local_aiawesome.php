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
 * English language pack for Ai awesome
 *
 * @package    local_aiawesome
 * @category   string
 * @copyright  2025 2024 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'AI Awesome';

// Settings page.
$string['settings_header_general'] = 'General Settings';
$string['settings_header_provider'] = 'AI Provider Configuration';
$string['settings_header_openai'] = 'OpenAI Configuration';
$string['settings_header_openai_desc'] = 'Configure OpenAI API settings for direct API access.';
$string['settings_header_logging'] = 'Logging & Privacy';
$string['settings_header_guardrails'] = 'Guardrails & Limits';

$string['setting_enabled'] = 'Enable AI Awesome';
$string['setting_enabled_desc'] = 'Enable the AI chat feature for users with appropriate capabilities.';

$string['setting_default_open'] = 'Default drawer state';
$string['setting_default_open_desc'] = 'Open the AI chat drawer by default when users first access it.';

$string['setting_openai_api_key'] = 'OpenAI API Key';
$string['setting_openai_api_key_desc'] = 'Your OpenAI API key. Keep this secure. Get yours at https://platform.openai.com/api-keys';

$string['setting_openai_model'] = 'OpenAI Model';
$string['setting_openai_model_desc'] = 'The OpenAI model to use (e.g., gpt-4o-mini, gpt-4o, gpt-3.5-turbo).';

$string['setting_openai_organization'] = 'OpenAI Organization ID';
$string['setting_openai_organization_desc'] = 'Your OpenAI organization ID (optional). Leave empty if not using organization billing.';

$string['setting_openai_project'] = 'OpenAI Project ID';
$string['setting_openai_project_desc'] = 'Your OpenAI project ID (optional). Leave empty if not using project-based organization.';

$string['settings_header_testing'] = 'Testing & Diagnostics';
$string['settings_header_testing_desc'] = 'Test your API configuration to ensure it\'s working correctly.';

$string['setting_test_connection'] = 'Connection Test';
$string['test_connection_button'] = 'Test API Connection';
$string['testing'] = 'Testing...';

$string['setting_enable_logging'] = 'Enable usage logging';
$string['setting_enable_logging_desc'] = 'Log usage statistics (no content is logged by default).';

$string['setting_log_content'] = 'Log conversation content';
$string['setting_log_content_desc'] = 'Include conversation content in logs (for debugging only - privacy implications).';

$string['setting_max_tokens'] = 'Maximum tokens per response';
$string['setting_max_tokens_desc'] = 'Limit the maximum number of tokens in AI responses.';

$string['setting_temperature'] = 'Response creativity (temperature)';
$string['setting_temperature_desc'] = 'Control randomness in AI responses (0.0 = deterministic, 1.0 = creative).';

$string['setting_rate_limit'] = 'Rate limit per user (requests/hour)';
$string['setting_rate_limit_desc'] = 'Maximum number of requests per user per hour (0 = unlimited).';

// Capabilities.
$string['aiawesome:view'] = 'View AI chat interface';
$string['aiawesome:use'] = 'Use AI chat feature';
$string['aiawesome:viewlogs'] = 'View AI usage logs';

// UI strings.
$string['chat_toggle_title'] = 'AI Chat Assistant';
$string['chat_placeholder'] = 'Ask me anything about this course...';
$string['chat_send'] = 'Send';
$string['chat_stop'] = 'Stop';
$string['chat_clear'] = 'Clear chat';
$string['chat_close'] = 'Close';

// Error messages.
$string['error_disabled'] = 'AI chat is currently disabled.';
$string['error_no_permission'] = 'You do not have permission to use AI chat.';
$string['error_rate_limit'] = 'Rate limit exceeded. Please try again later.';
$string['error_network'] = 'Network error. Please check your connection.';
$string['error_server'] = 'Server error. Please try again later.';
$string['error_configuration'] = 'AI service is not properly configured.';

// Privacy strings.
$string['privacy:metadata:local_aiawesome_logs'] = 'Stores usage statistics and metadata for AI chat interactions.';
$string['privacy:metadata:local_aiawesome_logs:userid'] = 'The ID of the user who initiated the chat.';
$string['privacy:metadata:local_aiawesome_logs:courseid'] = 'The ID of the course context where the chat occurred.';
$string['privacy:metadata:local_aiawesome_logs:sessionid'] = 'A session identifier for grouping related chat interactions.';
$string['privacy:metadata:local_aiawesome_logs:bytes_up'] = 'Number of bytes sent to the AI service.';
$string['privacy:metadata:local_aiawesome_logs:bytes_down'] = 'Number of bytes received from the AI service.';
$string['privacy:metadata:local_aiawesome_logs:status'] = 'Status of the chat interaction (success, error, etc).';
$string['privacy:metadata:local_aiawesome_logs:error'] = 'Error message if the interaction failed.';
$string['privacy:metadata:local_aiawesome_logs:createdat'] = 'Timestamp when the interaction was created.';
$string['privacy:metadata:local_aiawesome_logs:duration_ms'] = 'Duration of the interaction in milliseconds.';

$string['privacy:metadata:external:aiservice'] = 'User context and queries are sent to the configured AI service for processing.';
$string['privacy:metadata:external:aiservice:context'] = 'Encrypted user and course context information.';
$string['privacy:metadata:external:aiservice:query'] = 'The user\'s question or prompt sent to the AI service.';

// Cache definitions.
$string['cachedef_token_cache'] = 'Cache for OAuth2 access tokens to avoid repeated authentication requests.';
$string['cachedef_config_cache'] = 'Cache for plugin configuration to improve performance.';
$string['cachedef_rate_limit_cache'] = 'Cache for tracking rate limits per user.';

// Provider selections.
$string['provider_openai'] = 'OpenAI (Direct API)';
$string['provider_custom_oauth'] = 'Custom OAuth Service';
$string['provider_digitalocean'] = 'DigitalOcean (Custom Endpoint)';

$string['setting_ai_provider'] = 'AI Provider';
$string['setting_ai_provider_desc'] = 'Choose which AI provider to use for chat completions.';

// Custom OAuth settings.
$string['settings_header_custom_oauth'] = 'Custom OAuth Service Configuration';
$string['settings_header_custom_oauth_desc'] = 'Connect to a third-party AI service using OAuth2 client-credentials flow (e.g., Azure OpenAI, enterprise AI gateway).';

$string['setting_oauth_base_url'] = 'AI Service Base URL';
$string['setting_oauth_base_url_desc'] = 'The base URL for your OAuth-protected AI service API endpoint.';

$string['setting_oauth_token_url'] = 'OAuth Token URL';
$string['setting_oauth_token_url_desc'] = 'The OAuth2 token endpoint for client credentials flow.';

$string['setting_oauth_client_id'] = 'OAuth Client ID';
$string['setting_oauth_client_id_desc'] = 'Client ID for OAuth2 authentication.';

$string['setting_oauth_client_secret'] = 'OAuth Client Secret';
$string['setting_oauth_client_secret_desc'] = 'Client secret for OAuth2 authentication.';

$string['setting_oauth_app_id'] = 'Application ID';
$string['setting_oauth_app_id_desc'] = 'Application identifier sent with requests to the custom service.';

// DigitalOcean settings.
$string['settings_header_digitalocean'] = 'DigitalOcean Configuration';
$string['settings_header_digitalocean_desc'] = 'Configure your custom DigitalOcean-hosted AI model endpoint.';

$string['setting_digitalocean_endpoint'] = 'Endpoint URL';
$string['setting_digitalocean_endpoint_desc'] = 'Full URL to your AI model endpoint (e.g., https://your-droplet.example.com/v1/chat/completions). Must support OpenAI-compatible API format.';

$string['setting_digitalocean_api_key'] = 'API Key (Optional)';
$string['setting_digitalocean_api_key_desc'] = 'API key for authentication if your endpoint requires it. Leave blank if your endpoint does not require authentication.';

$string['setting_digitalocean_model'] = 'Model Name';
$string['setting_digitalocean_model_desc'] = 'Name of the AI model deployed on your endpoint (e.g., llama3.1:8b, deepseek-r1:7b, codellama:7b).';

$string['setting_digitalocean_headers'] = 'Custom Headers (Optional)';
$string['setting_digitalocean_headers_desc'] = 'Additional HTTP headers to send with requests, one per line in "Header-Name: value" format. Use this for custom authentication or configuration requirements.';

// Connection test messages.
$string['digitalocean_test_success'] = 'DigitalOcean connection test successful! Model: {$a->model}, Response time: {$a->response_time}ms';
$string['digitalocean_test_failed'] = 'DigitalOcean connection test failed: {$a}';
$string['digitalocean_test_connectivity_failed'] = 'Cannot reach DigitalOcean endpoint. Please check the URL and ensure your droplet is running.';
$string['digitalocean_test_auth_failed'] = 'Authentication failed. Please check your API key configuration.';
$string['digitalocean_test_model_failed'] = 'Model "{$a}" is not available on your endpoint. Please check your model configuration.';

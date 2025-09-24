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

$string['setting_auth_mode'] = 'Authentication Mode';
$string['setting_auth_mode_desc'] = 'Choose how to authenticate with the AI service. OAuth for custom services, Token for OpenAI API.';
$string['auth_mode_oauth'] = 'OAuth2 (Custom Service)';
$string['auth_mode_token'] = 'API Token (OpenAI)';

$string['setting_base_url'] = 'AI Service Base URL';
$string['setting_base_url_desc'] = 'The base URL for your AI service API endpoint (for OAuth mode).';

$string['setting_app_id'] = 'Application ID';
$string['setting_app_id_desc'] = 'Your application identifier for the AI service (for OAuth mode).';

$string['setting_token_url'] = 'OAuth2 Token URL';
$string['setting_token_url_desc'] = 'The OAuth2 token endpoint for authentication (for OAuth mode).';

$string['setting_client_id'] = 'OAuth2 Client ID';
$string['setting_client_id_desc'] = 'Your OAuth2 client identifier (for OAuth mode).';

$string['setting_client_secret'] = 'OAuth2 Client Secret';
$string['setting_client_secret_desc'] = 'Your OAuth2 client secret (kept secure on server, for OAuth mode).';

$string['setting_openai_api_key'] = 'OpenAI API Key';
$string['setting_openai_api_key_desc'] = 'Your OpenAI API key. Keep this secure. Get yours at https://platform.openai.com/api-keys';

$string['setting_openai_api_base'] = 'OpenAI API Base URL';
$string['setting_openai_api_base_desc'] = 'The base URL for OpenAI API. Use default unless using a proxy or custom endpoint.';

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

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
 * Plugin administration pages are defined here.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_aiawesome', get_string('pluginname', 'local_aiawesome'));

    // General Settings.
    $settings->add(new admin_setting_heading(
        'local_aiawesome/general_header',
        get_string('settings_header_general', 'local_aiawesome'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_aiawesome/enabled',
        get_string('setting_enabled', 'local_aiawesome'),
        get_string('setting_enabled_desc', 'local_aiawesome'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_aiawesome/default_open',
        get_string('setting_default_open', 'local_aiawesome'),
        get_string('setting_default_open_desc', 'local_aiawesome'),
        0
    ));

    // AI Provider Configuration.
    $settings->add(new admin_setting_heading(
        'local_aiawesome/provider_header',
        get_string('settings_header_provider', 'local_aiawesome'),
        ''
    ));

    // Authentication mode selection.
    $auth_modes = [
        'oauth' => get_string('auth_mode_oauth', 'local_aiawesome'),
        'token' => get_string('auth_mode_token', 'local_aiawesome'),
    ];
    $settings->add(new admin_setting_configselect(
        'local_aiawesome/auth_mode',
        get_string('setting_auth_mode', 'local_aiawesome'),
        get_string('setting_auth_mode_desc', 'local_aiawesome'),
        'oauth',
        $auth_modes
    ));

    // OAuth Configuration (shown when OAuth mode is selected).
    $settings->add(new admin_setting_configtext(
        'local_aiawesome/base_url',
        get_string('setting_base_url', 'local_aiawesome'),
        get_string('setting_base_url_desc', 'local_aiawesome'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_aiawesome/app_id',
        get_string('setting_app_id', 'local_aiawesome'),
        get_string('setting_app_id_desc', 'local_aiawesome'),
        '',
        PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_aiawesome/token_url',
        get_string('setting_token_url', 'local_aiawesome'),
        get_string('setting_token_url_desc', 'local_aiawesome'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_aiawesome/client_id',
        get_string('setting_client_id', 'local_aiawesome'),
        get_string('setting_client_id_desc', 'local_aiawesome'),
        ''
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_aiawesome/client_secret',
        get_string('setting_client_secret', 'local_aiawesome'),
        get_string('setting_client_secret_desc', 'local_aiawesome'),
        ''
    ));

    // OpenAI API Configuration (shown when Token mode is selected).
    $settings->add(new admin_setting_heading(
        'local_aiawesome/openai_header',
        get_string('settings_header_openai', 'local_aiawesome'),
        get_string('settings_header_openai_desc', 'local_aiawesome')
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_aiawesome/openai_api_key',
        get_string('setting_openai_api_key', 'local_aiawesome'),
        get_string('setting_openai_api_key_desc', 'local_aiawesome'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_aiawesome/openai_api_base',
        get_string('setting_openai_api_base', 'local_aiawesome'),
        get_string('setting_openai_api_base_desc', 'local_aiawesome'),
        'https://api.openai.com/v1',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_aiawesome/openai_model',
        get_string('setting_openai_model', 'local_aiawesome'),
        get_string('setting_openai_model_desc', 'local_aiawesome'),
        'gpt-4o-mini',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_aiawesome/openai_organization',
        get_string('setting_openai_organization', 'local_aiawesome'),
        get_string('setting_openai_organization_desc', 'local_aiawesome'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_aiawesome/openai_project',
        get_string('setting_openai_project', 'local_aiawesome'),
        get_string('setting_openai_project_desc', 'local_aiawesome'),
        '',
        PARAM_TEXT
    ));

    // Connection test section.
    $settings->add(new admin_setting_heading(
        'local_aiawesome/testing_header',
        get_string('settings_header_testing', 'local_aiawesome'),
        get_string('settings_header_testing_desc', 'local_aiawesome')
    ));

    // Add a custom setting for testing connection.
    $settings->add(new admin_setting_description(
        'local_aiawesome/test_connection',
        get_string('setting_test_connection', 'local_aiawesome'),
        '<div id="aiawesome-test-connection">
            <button type="button" id="aiawesome-test-btn" class="btn btn-secondary">
                ' . get_string('test_connection_button', 'local_aiawesome') . '
            </button>
            <div id="aiawesome-test-result" style="margin-top: 10px;"></div>
        </div>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var btn = document.getElementById("aiawesome-test-btn");
            var result = document.getElementById("aiawesome-test-result");
            
            if (btn) {
                btn.addEventListener("click", function() {
                    btn.disabled = true;
                    btn.textContent = "' . get_string('testing', 'local_aiawesome') . '";
                    result.innerHTML = "";
                    
                    fetch(M.cfg.wwwroot + "/local/aiawesome/test_connection.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded",
                        },
                        body: "sesskey=" + encodeURIComponent(M.cfg.sesskey)
                    })
                    .then(response => response.json())
                    .then(data => {
                        btn.disabled = false;
                        btn.textContent = "' . get_string('test_connection_button', 'local_aiawesome') . '";
                        
                        if (data.success) {
                            result.innerHTML = "<div class=\"alert alert-success\">" +
                                "<strong>✓ Success:</strong> " + data.message + " (Mode: " + data.mode + ")" +
                            "</div>";
                        } else {
                            result.innerHTML = "<div class=\"alert alert-danger\">" +
                                "<strong>✗ Error:</strong> " + data.error + " (Mode: " + (data.mode || "unknown") + ")" +
                            "</div>";
                        }
                    })
                    .catch(error => {
                        btn.disabled = false;
                        btn.textContent = "' . get_string('test_connection_button', 'local_aiawesome') . '";
                        result.innerHTML = "<div class=\"alert alert-danger\">" +
                            "<strong>✗ Network Error:</strong> " + error.message +
                        "</div>";
                    });
                });
            }
        });
        </script>'
    ));

    // Logging & Privacy.
    $settings->add(new admin_setting_heading(
        'local_aiawesome/logging_header',
        get_string('settings_header_logging', 'local_aiawesome'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_aiawesome/enable_logging',
        get_string('setting_enable_logging', 'local_aiawesome'),
        get_string('setting_enable_logging_desc', 'local_aiawesome'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_aiawesome/log_content',
        get_string('setting_log_content', 'local_aiawesome'),
        get_string('setting_log_content_desc', 'local_aiawesome'),
        0
    ));

    // Guardrails & Limits.
    $settings->add(new admin_setting_heading(
        'local_aiawesome/guardrails_header',
        get_string('settings_header_guardrails', 'local_aiawesome'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_aiawesome/max_tokens',
        get_string('setting_max_tokens', 'local_aiawesome'),
        get_string('setting_max_tokens_desc', 'local_aiawesome'),
        '2000',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_aiawesome/temperature',
        get_string('setting_temperature', 'local_aiawesome'),
        get_string('setting_temperature_desc', 'local_aiawesome'),
        '0.7',
        PARAM_FLOAT
    ));

    $settings->add(new admin_setting_configtext(
        'local_aiawesome/rate_limit',
        get_string('setting_rate_limit', 'local_aiawesome'),
        get_string('setting_rate_limit_desc', 'local_aiawesome'),
        '100',
        PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);
}
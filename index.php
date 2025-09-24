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
 * AI Awesome plugin health check and information page.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_aiawesome\oauth_service;
use local_aiawesome\crypto_utils;
use local_aiawesome\logging_service;

require_login();
require_capability('local/aiawesome:viewlogs', context_system::instance());

$PAGE->set_url('/local/aiawesome/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_aiawesome') . ' - Health Check');
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_aiawesome') . ' Health Check');

// Check plugin configuration
$config_ok = true;
$issues = [];

echo html_writer::start_tag('div', ['class' => 'container-fluid']);

// Configuration Check
echo html_writer::start_tag('div', ['class' => 'row']);
echo html_writer::start_tag('div', ['class' => 'col-md-6']);
echo html_writer::tag('h3', 'Configuration Status');

$enabled = get_config('local_aiawesome', 'enabled');
$base_url = get_config('local_aiawesome', 'base_url');
$client_id = get_config('local_aiawesome', 'client_id');
$client_secret = get_config('local_aiawesome', 'client_secret');
$token_url = get_config('local_aiawesome', 'token_url');

echo html_writer::start_tag('table', ['class' => 'table table-striped']);
echo html_writer::tag('tr', 
    html_writer::tag('td', 'Plugin Enabled') . 
    html_writer::tag('td', $enabled ? 
        html_writer::tag('span', '✓ Enabled', ['class' => 'text-success']) : 
        html_writer::tag('span', '❌ Disabled', ['class' => 'text-danger'])
    )
);

echo html_writer::tag('tr', 
    html_writer::tag('td', 'Base URL') . 
    html_writer::tag('td', !empty($base_url) ? 
        html_writer::tag('span', '✓ Configured', ['class' => 'text-success']) : 
        html_writer::tag('span', '❌ Missing', ['class' => 'text-danger'])
    )
);

echo html_writer::tag('tr', 
    html_writer::tag('td', 'OAuth2 Client ID') . 
    html_writer::tag('td', !empty($client_id) ? 
        html_writer::tag('span', '✓ Configured', ['class' => 'text-success']) : 
        html_writer::tag('span', '❌ Missing', ['class' => 'text-danger'])
    )
);

echo html_writer::tag('tr', 
    html_writer::tag('td', 'OAuth2 Client Secret') . 
    html_writer::tag('td', !empty($client_secret) ? 
        html_writer::tag('span', '✓ Configured', ['class' => 'text-success']) : 
        html_writer::tag('span', '❌ Missing', ['class' => 'text-danger'])
    )
);

echo html_writer::tag('tr', 
    html_writer::tag('td', 'Token URL') . 
    html_writer::tag('td', !empty($token_url) ? 
        html_writer::tag('span', '✓ Configured', ['class' => 'text-success']) : 
        html_writer::tag('span', '❌ Missing', ['class' => 'text-danger'])
    )
);

echo html_writer::end_tag('table');
echo html_writer::end_tag('div');

// System Check
echo html_writer::start_tag('div', ['class' => 'col-md-6']);
echo html_writer::tag('h3', 'System Requirements');

echo html_writer::start_tag('table', ['class' => 'table table-striped']);

$crypto_available = crypto_utils::is_crypto_available();
echo html_writer::tag('tr', 
    html_writer::tag('td', 'Crypto Functions') . 
    html_writer::tag('td', $crypto_available ? 
        html_writer::tag('span', '✓ Available', ['class' => 'text-success']) : 
        html_writer::tag('span', '❌ Missing', ['class' => 'text-danger'])
    )
);

$curl_available = function_exists('curl_init');
echo html_writer::tag('tr', 
    html_writer::tag('td', 'cURL Extension') . 
    html_writer::tag('td', $curl_available ? 
        html_writer::tag('span', '✓ Available', ['class' => 'text-success']) : 
        html_writer::tag('span', '❌ Missing', ['class' => 'text-danger'])
    )
);

$json_available = function_exists('json_encode');
echo html_writer::tag('tr', 
    html_writer::tag('td', 'JSON Extension') . 
    html_writer::tag('td', $json_available ? 
        html_writer::tag('span', '✓ Available', ['class' => 'text-success']) : 
        html_writer::tag('span', '❌ Missing', ['class' => 'text-danger'])
    )
);

// Check if built assets exist
$boot_js = __DIR__ . '/amd/build/boot.min.js';
$app_js = __DIR__ . '/amd/build/app.min.js';
$sse_js = __DIR__ . '/amd/build/sse.min.js';

$assets_built = file_exists($boot_js) && file_exists($app_js) && file_exists($sse_js);
echo html_writer::tag('tr', 
    html_writer::tag('td', 'Built Assets') . 
    html_writer::tag('td', $assets_built ? 
        html_writer::tag('span', '✓ Present', ['class' => 'text-success']) : 
        html_writer::tag('span', '❌ Missing (run npm run build)', ['class' => 'text-warning'])
    )
);

echo html_writer::end_tag('table');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Usage Statistics
if (get_config('local_aiawesome', 'enable_logging')) {
    echo html_writer::start_tag('div', ['class' => 'row mt-4']);
    echo html_writer::start_tag('div', ['class' => 'col-12']);
    echo html_writer::tag('h3', 'Usage Statistics (Last 30 Days)');
    
    $stats = logging_service::get_system_usage();
    
    echo html_writer::start_tag('div', ['class' => 'row']);
    
    // Stats cards
    $cards = [
        ['Total Requests', $stats->total_requests, 'primary'],
        ['Unique Users', $stats->unique_users, 'info'],
        ['Success Rate', $stats->success_rate . '%', 'success'],
        ['Avg Duration', $stats->avg_duration . 'ms', 'secondary'],
    ];
    
    foreach ($cards as $card) {
        echo html_writer::start_tag('div', ['class' => 'col-md-3 mb-3']);
        echo html_writer::start_tag('div', ['class' => 'card border-' . $card[2]]);
        echo html_writer::start_tag('div', ['class' => 'card-body text-center']);
        echo html_writer::tag('h5', $card[1], ['class' => 'card-title text-' . $card[2]]);
        echo html_writer::tag('p', $card[0], ['class' => 'card-text']);
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    }
    
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
}

// OAuth Test (if configured)
if ($enabled && !empty($base_url) && !empty($client_id) && !empty($client_secret) && !empty($token_url)) {
    echo html_writer::start_tag('div', ['class' => 'row mt-4']);
    echo html_writer::start_tag('div', ['class' => 'col-12']);
    echo html_writer::tag('h3', 'OAuth2 Connection Test');
    
    try {
        $oauth = new oauth_service();
        $token = $oauth->get_access_token();
        
        if ($token) {
            echo html_writer::tag('div', '✓ OAuth2 authentication successful', ['class' => 'alert alert-success']);
            echo html_writer::tag('p', 'Token expires: ' . date('Y-m-d H:i:s', $token->expires_at));
        } else {
            echo html_writer::tag('div', '❌ OAuth2 authentication failed', ['class' => 'alert alert-danger']);
        }
    } catch (Exception $e) {
        echo html_writer::tag('div', '❌ OAuth2 error: ' . $e->getMessage(), ['class' => 'alert alert-danger']);
    }
    
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
}

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
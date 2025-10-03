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
 * Diagnostic page for AI Awesome plugin.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title('AI Awesome Diagnostics');
$PAGE->set_heading('AI Awesome Plugin Diagnostics');
$PAGE->set_url('/local/aiawesome/diagnostics.php');

// Check if user can use the plugin
$can_use = has_capability('local/aiawesome:use', context_system::instance());
// Add the CSS and JS manually for testing
$PAGE->requires->css('/local/aiawesome/styles.css');
$PAGE->requires->js_call_amd('local_aiawesome/boot', 'init');
echo $OUTPUT->header();

echo '<div class="container-fluid">';

// Add navigation menu
echo '<div class="alert alert-info">';
echo '<h5 class="mb-2">AI Awesome Administration</h5>';
echo '<div class="btn-group" role="group">';
echo '<a href="' . new moodle_url('/admin/settings.php', ['section' => 'local_aiawesome']) . '" class="btn btn-outline-primary btn-sm">⚙️ Settings</a>';
echo '<a href="' . new moodle_url('/local/aiawesome/index.php') . '" class="btn btn-outline-primary btn-sm">🔍 Health Check</a>';
echo '<a href="' . new moodle_url('/local/aiawesome/diagnostics.php') . '" class="btn btn-primary btn-sm">🛠️ Diagnostics</a>';
echo '</div>';
echo '</div>';

echo '<h2>AI Awesome Plugin Diagnostics</h2>';

// Plugin status
echo '<div class="card mb-3">';
echo '<div class="card-header"><h5>Plugin Status</h5></div>';
echo '<div class="card-body">';
echo '<table class="table table-bordered">';
echo '<tr><td><strong>Plugin Enabled</strong></td><td>' . (get_config('local_aiawesome', 'enabled') ? '✅ Yes' : '❌ No') . '</td></tr>';
echo '<tr><td><strong>AI Provider</strong></td><td>' . (get_config('local_aiawesome', 'ai_provider') ?: 'openai') . '</td></tr>';
echo '<tr><td><strong>User Can Use</strong></td><td>' . ($can_use ? '✅ Yes' : '❌ No') . '</td></tr>';
echo '<tr><td><strong>User ID</strong></td><td>' . $USER->id . '</td></tr>';
echo '<tr><td><strong>User Roles</strong></td><td>';

$roles = get_user_roles(context_system::instance(), $USER->id);
foreach ($roles as $role) {
    echo $role->shortname . ' ';
}
echo '</td></tr>';
echo '</table>';
echo '</div>';
echo '</div>';

// Configuration
echo '<div class="card mb-3">';
echo '<div class="card-header"><h5>Configuration</h5></div>';
echo '<div class="card-body">';

$provider = get_config('local_aiawesome', 'ai_provider') ?: 'openai';

echo '<table class="table table-bordered">';

switch ($provider) {
    case 'openai':
        $api_key = get_config('local_aiawesome', 'openai_api_key');
        $model = get_config('local_aiawesome', 'openai_model');
        
        echo '<tr><td><strong>Provider Type</strong></td><td>OpenAI Direct API</td></tr>';
        echo '<tr><td><strong>OpenAI API Key</strong></td><td>' . (!empty($api_key) ? '✅ Set (' . substr($api_key, 0, 7) . '...)' : '❌ Not set') . '</td></tr>';
        echo '<tr><td><strong>OpenAI Model</strong></td><td>' . ($model ?: 'gpt-4o-mini') . '</td></tr>';
        break;
        
    case 'custom_oauth':
        $base_url = get_config('local_aiawesome', 'oauth_base_url');
        $client_id = get_config('local_aiawesome', 'oauth_client_id');
        $client_secret = get_config('local_aiawesome', 'oauth_client_secret');
        $token_url = get_config('local_aiawesome', 'oauth_token_url');
        
        echo '<tr><td><strong>Provider Type</strong></td><td>Custom OAuth Service</td></tr>';
        echo '<tr><td><strong>Base URL</strong></td><td>' . ($base_url ?: 'Not set') . '</td></tr>';
        echo '<tr><td><strong>Client ID</strong></td><td>' . (!empty($client_id) ? '✅ Set' : '❌ Not set') . '</td></tr>';
        echo '<tr><td><strong>Client Secret</strong></td><td>' . (!empty($client_secret) ? '✅ Set' : '❌ Not set') . '</td></tr>';
        echo '<tr><td><strong>Token URL</strong></td><td>' . ($token_url ?: 'Not set') . '</td></tr>';
        break;
        
    case 'digitalocean':
        $endpoint = get_config('local_aiawesome', 'digitalocean_endpoint');
        $model = get_config('local_aiawesome', 'digitalocean_model');
        
        echo '<tr><td><strong>Provider Type</strong></td><td>DigitalOcean Custom Endpoint</td></tr>';
        echo '<tr><td><strong>Endpoint URL</strong></td><td>' . ($endpoint ?: 'Not set') . '</td></tr>';
        echo '<tr><td><strong>Model</strong></td><td>' . ($model ?: 'Not set') . '</td></tr>';
        break;
        
    default:
        echo '<tr><td><strong>Provider Type</strong></td><td>❌ Unknown provider: ' . $provider . '</td></tr>';
        break;
}

echo '</table>';
echo '</div>';
echo '</div>';

// File checks
echo '<div class="card mb-3">';
echo '<div class="card-header"><h5>File Status</h5></div>';
echo '<div class="card-body">';
echo '<table class="table table-bordered">';

$files = [
    'CSS' => '/local/aiawesome/styles.css',
    'Boot JS' => '/local/aiawesome/amd/build/boot.js',
    'App JS' => '/local/aiawesome/amd/build/simple_app.js',
    'SSE JS' => '/local/aiawesome/amd/build/sse.js',
];

foreach ($files as $name => $path) {
    $full_path = $CFG->dirroot . $path;
    $exists = file_exists($full_path);
    $size = $exists ? filesize($full_path) : 0;
    echo '<tr><td><strong>' . $name . '</strong></td><td>' . ($exists ? '✅ Exists (' . round($size/1024, 1) . 'KB)' : '❌ Missing') . '</td></tr>';
}
echo '</table>';
echo '</div>';
echo '</div>';

// Token Usage Statistics
if (get_config('local_aiawesome', 'enable_logging')) {
    echo '<div class="card mb-3">';
    echo '<div class="card-header"><h5>Token Usage Statistics</h5></div>';
    echo '<div class="card-body">';
    
    require_once(__DIR__ . '/classes/logging_service.php');
    $token_stats = \local_aiawesome\logging_service::get_token_statistics();
    
    // Overview cards
    echo '<div class="row mb-3">';
    
    echo '<div class="col-md-3">';
    echo '<div class="card text-center border-primary">';
    echo '<div class="card-body">';
    echo '<h3 class="text-primary">' . number_format($token_stats->tokens_today) . '</h3>';
    echo '<p class="card-text">Tokens Today</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card text-center border-info">';
    echo '<div class="card-body">';
    echo '<h3 class="text-info">' . number_format($token_stats->tokens_this_week) . '</h3>';
    echo '<p class="card-text">Tokens This Week</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card text-center border-success">';
    echo '<div class="card-body">';
    echo '<h3 class="text-success">' . number_format($token_stats->tokens_this_month) . '</h3>';
    echo '<p class="card-text">Tokens This Month</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card text-center border-secondary">';
    echo '<div class="card-body">';
    echo '<h3 class="text-secondary">' . number_format($token_stats->total_requests) . '</h3>';
    echo '<p class="card-text">Total Requests</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // End row
    
    // Detailed breakdown
    echo '<h6>Token Breakdown</h6>';
    echo '<table class="table table-bordered table-sm">';
    echo '<tr><td><strong>Prompt Tokens</strong></td><td>' . number_format($token_stats->total_prompt_tokens) . ' (avg: ' . $token_stats->avg_prompt_tokens . ')</td></tr>';
    echo '<tr><td><strong>Completion Tokens</strong></td><td>' . number_format($token_stats->total_completion_tokens) . ' (avg: ' . $token_stats->avg_completion_tokens . ')</td></tr>';
    echo '<tr><td><strong>Total Tokens</strong></td><td>' . number_format($token_stats->total_tokens) . '</td></tr>';
    echo '</table>';
    
    // Provider breakdown
    if (!empty($token_stats->by_provider)) {
        echo '<h6 class="mt-3">Usage by Provider</h6>';
        echo '<table class="table table-bordered table-sm">';
        echo '<thead><tr><th>Provider</th><th>Requests</th><th>Prompt</th><th>Completion</th><th>Total</th></tr></thead>';
        echo '<tbody>';
        foreach ($token_stats->by_provider as $prov) {
            $provider_name = ucfirst(str_replace('_', ' ', $prov->provider ?: 'Unknown'));
            echo '<tr>';
            echo '<td><strong>' . $provider_name . '</strong></td>';
            echo '<td>' . number_format($prov->requests) . '</td>';
            echo '<td>' . number_format($prov->prompt_tokens) . '</td>';
            echo '<td>' . number_format($prov->completion_tokens) . '</td>';
            echo '<td>' . number_format($prov->total_tokens) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }
    
    // Top users
    if (!empty($token_stats->top_users)) {
        echo '<h6 class="mt-3">Top 10 Users by Token Usage</h6>';
        echo '<table class="table table-bordered table-sm">';
        echo '<thead><tr><th>User</th><th>Requests</th><th>Total Tokens</th></tr></thead>';
        echo '<tbody>';
        foreach ($token_stats->top_users as $user) {
            echo '<tr>';
            echo '<td>' . $user->firstname . ' ' . $user->lastname . '</td>';
            echo '<td>' . number_format($user->requests) . '</td>';
            echo '<td>' . number_format($user->total_tokens) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }
    
    // Cost estimates (OpenAI pricing as of 2025)
    $current_provider = get_config('local_aiawesome', 'ai_provider');
    if ($current_provider === 'openai') {
        echo '<div class="alert alert-info mt-3">';
        echo '<h6>Estimated Cost (OpenAI gpt-4o-mini)</h6>';
        // gpt-4o-mini: $0.150 per 1M input tokens, $0.600 per 1M output tokens
        $input_cost = ($token_stats->total_prompt_tokens / 1000000) * 0.150;
        $output_cost = ($token_stats->total_completion_tokens / 1000000) * 0.600;
        $total_cost = $input_cost + $output_cost;
        echo '<p class="mb-0">';
        echo 'Input: $' . number_format($input_cost, 4) . ' | ';
        echo 'Output: $' . number_format($output_cost, 4) . ' | ';
        echo '<strong>Total: $' . number_format($total_cost, 2) . '</strong>';
        echo '</p>';
        echo '<small class="text-muted">Based on gpt-4o-mini pricing ($0.150/1M input, $0.600/1M output). Actual costs may vary.</small>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
} else {
    echo '<div class="alert alert-warning">Logging is disabled. Enable logging in settings to see token usage statistics.</div>';
}

// Manual test
echo '<div class="card mb-3">';
echo '<div class="card-header"><h5>Manual Test</h5></div>';
echo '<div class="card-body">';
echo '<p>Click the button below to manually trigger the AI chat initialization:</p>';
echo '<button type="button" id="manual-test-btn" class="btn btn-primary">Initialize AI Chat</button>';
echo '<button type="button" id="check-toggle-btn" class="btn btn-secondary ml-2">Check for Toggle Button</button>';
echo '<div id="manual-test-result" style="margin-top: 10px;"></div>';
echo '</div>';
echo '</div>';

// Debug info
echo '<div class="card mb-3">';
echo '<div class="card-header"><h5>Debug Information</h5></div>';
echo '<div class="card-body">';
echo '<div id="debug-info">';
echo '<p><strong>Current User Menu Selectors Found:</strong></p>';
echo '<ul id="selector-results"></ul>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>'; // container-fluid



?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('manual-test-btn');
    var checkBtn = document.getElementById('check-toggle-btn');
    var result = document.getElementById('manual-test-result');
    var selectorResults = document.getElementById('selector-results');
    
    // Check selectors on page load
    checkUserMenuSelectors();
    
    if (btn) {
        btn.addEventListener('click', function() {
            result.innerHTML = '<div class="alert alert-info">Attempting to initialize AI chat...</div>';
            
            // Check if the boot module is loaded
            if (typeof require !== 'undefined') {
                require(['local_aiawesome/boot'], function(boot) {
                    if (boot && boot.init) {
                        try {
                            boot.init();
                            result.innerHTML = '<div class="alert alert-success">✅ AI chat initialization called successfully! Check if the toggle appears in the page.</div>';
                            
                            // Check for toggle after a short delay
                            setTimeout(function() {
                                var toggle = document.querySelector('[data-aiawesome-toggle]');
                                if (toggle) {
                                    result.innerHTML += '<div class="alert alert-success">✅ Toggle button found in DOM!</div>';
                                } else {
                                    result.innerHTML += '<div class="alert alert-warning">⚠️ Toggle button not found. Check console for errors.</div>';
                                }
                            }, 1000);
                            
                        } catch (e) {
                            result.innerHTML = '<div class="alert alert-danger">❌ Error initializing: ' + e.message + '</div>';
                        }
                    } else {
                        result.innerHTML = '<div class="alert alert-warning">⚠️ Boot module loaded but init function not found.</div>';
                    }
                }, function(err) {
                    result.innerHTML = '<div class="alert alert-danger">❌ Failed to load boot module: ' + err.message + '</div>';
                });
            } else {
                result.innerHTML = '<div class="alert alert-danger">❌ RequireJS not available</div>';
            }
        });
    }
    
    if (checkBtn) {
        checkBtn.addEventListener('click', function() {
            checkUserMenuSelectors();
            
            var toggle = document.querySelector('[data-aiawesome-toggle]');
            if (toggle) {
                result.innerHTML = '<div class="alert alert-success">✅ Toggle button found! It should be clickable.</div>';
            } else {
                result.innerHTML = '<div class="alert alert-warning">⚠️ No toggle button found. Try the Initialize button first.</div>';
            }
        });
    }
    
    function checkUserMenuSelectors() {
        var selectors = [
            '.usermenu .dropdown-menu',
            '#user-menu-dropdown',
            '[data-region="user-menu"] .dropdown-menu',
            '.navbar-nav .dropdown-menu:has([data-title="usermenu"])',
            '.usermenu',
            '.navbar-nav',
            '[data-region="user-menu"]'
        ];
        
        selectorResults.innerHTML = '';
        
        selectors.forEach(function(selector) {
            var element = document.querySelector(selector);
            var li = document.createElement('li');
            li.innerHTML = '<code>' + selector + '</code>: ' + (element ? '✅ Found' : '❌ Not found');
            selectorResults.appendChild(li);
        });
    }
});
</script>

<?php
echo $OUTPUT->footer();
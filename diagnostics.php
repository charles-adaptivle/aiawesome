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

echo $OUTPUT->header();

echo '<div class="container-fluid">';
echo '<h2>AI Awesome Plugin Diagnostics</h2>';

// Plugin status
echo '<div class="card mb-3">';
echo '<div class="card-header"><h5>Plugin Status</h5></div>';
echo '<div class="card-body">';
echo '<table class="table table-bordered">';
echo '<tr><td><strong>Plugin Enabled</strong></td><td>' . (get_config('local_aiawesome', 'enabled') ? '✅ Yes' : '❌ No') . '</td></tr>';
echo '<tr><td><strong>Authentication Mode</strong></td><td>' . (get_config('local_aiawesome', 'auth_mode') ?: 'oauth') . '</td></tr>';
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

if (get_config('local_aiawesome', 'auth_mode') === 'token') {
    $api_key = get_config('local_aiawesome', 'openai_api_key');
    echo '<table class="table table-bordered">';
    echo '<tr><td><strong>OpenAI API Key</strong></td><td>' . (!empty($api_key) ? '✅ Set (' . substr($api_key, 0, 7) . '...)' : '❌ Not set') . '</td></tr>';
    echo '<tr><td><strong>OpenAI Model</strong></td><td>' . (get_config('local_aiawesome', 'openai_model') ?: 'gpt-4o-mini') . '</td></tr>';
    echo '<tr><td><strong>OpenAI API Base</strong></td><td>' . (get_config('local_aiawesome', 'openai_api_base') ?: 'https://api.openai.com/v1') . '</td></tr>';
    echo '</table>';
} else {
    echo '<table class="table table-bordered">';
    echo '<tr><td><strong>Base URL</strong></td><td>' . (get_config('local_aiawesome', 'base_url') ?: 'Not set') . '</td></tr>';
    echo '<tr><td><strong>Client ID</strong></td><td>' . (!empty(get_config('local_aiawesome', 'client_id')) ? '✅ Set' : '❌ Not set') . '</td></tr>';
    echo '<tr><td><strong>Client Secret</strong></td><td>' . (!empty(get_config('local_aiawesome', 'client_secret')) ? '✅ Set' : '❌ Not set') . '</td></tr>';
    echo '</table>';
}
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
    'App JS' => '/local/aiawesome/amd/build/app.js',
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

// Add the CSS and JS manually for testing
$PAGE->requires->css('/local/aiawesome/styles.css');
$PAGE->requires->js_call_amd('local_aiawesome/boot', 'init');

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
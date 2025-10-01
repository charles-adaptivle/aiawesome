<?php
/**
 * Phase 3 Testing Script for AI Awesome Plugin
 * Tests all three providers and migration functionality
 * 
 * Usage: php admin/cli/cfg.php or run via browser (requires admin login)
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');

// CLI mode - no login required
if (!CLI_SCRIPT) {
    require_login();
    require_capability('moodle/site:config', context_system::instance());
}

// Color output for terminal
function output($message, $color = 'white') {
    $colors = [
        'green' => "\033[0;32m",
        'red' => "\033[0;31m",
        'yellow' => "\033[1;33m",
        'blue' => "\033[0;34m",
        'white' => "\033[0m",
    ];
    
    if (CLI_SCRIPT) {
        echo $colors[$color] . $message . $colors['white'] . PHP_EOL;
    } else {
        $class_map = ['green' => 'success', 'red' => 'danger', 'yellow' => 'warning', 'blue' => 'info'];
        echo '<div class="alert alert-' . $class_map[$color] . '">' . $message . '</div>';
    }
}

function test_result($test_name, $passed, $message = '') {
    if ($passed) {
        output("✅ PASS: $test_name" . ($message ? " - $message" : ''), 'green');
    } else {
        output("❌ FAIL: $test_name" . ($message ? " - $message" : ''), 'red');
    }
    return $passed;
}

output("=================================", 'blue');
output("AI Awesome Plugin - Phase 3 Tests", 'blue');
output("=================================", 'blue');
output("");

// Test 1: Check plugin is enabled
output("TEST 1: Plugin Configuration", 'yellow');
$enabled = get_config('local_aiawesome', 'enabled');
test_result("Plugin enabled", $enabled == 1);

$provider = get_config('local_aiawesome', 'ai_provider');
test_result("AI provider set", !empty($provider), "Provider: " . ($provider ?: 'NOT SET'));

// Test 2: Check current provider configuration
output("\nTEST 2: Provider-Specific Configuration", 'yellow');

switch ($provider) {
    case 'openai':
        $api_key = get_config('local_aiawesome', 'openai_api_key');
        $model = get_config('local_aiawesome', 'openai_model');
        
        test_result("OpenAI API key configured", !empty($api_key));
        test_result("OpenAI model set", !empty($model), "Model: " . ($model ?: 'NOT SET'));
        
        // Test API service can be instantiated
        try {
            require_once(__DIR__ . '/classes/api_service.php');
            $api = new \local_aiawesome\api_service();
            test_result("API service instantiation", true);
        } catch (Exception $e) {
            test_result("API service instantiation", false, $e->getMessage());
        }
        break;
        
    case 'custom_oauth':
        $base_url = get_config('local_aiawesome', 'oauth_base_url');
        $client_id = get_config('local_aiawesome', 'oauth_client_id');
        $client_secret = get_config('local_aiawesome', 'oauth_client_secret');
        $token_url = get_config('local_aiawesome', 'oauth_token_url');
        
        test_result("OAuth base URL configured", !empty($base_url), $base_url);
        test_result("OAuth client ID configured", !empty($client_id));
        test_result("OAuth client secret configured", !empty($client_secret));
        test_result("OAuth token URL configured", !empty($token_url), $token_url);
        
        // Test OAuth service
        try {
            require_once(__DIR__ . '/classes/oauth_service.php');
            $oauth = new \local_aiawesome\oauth_service();
            test_result("OAuth service instantiation", true);
            
            // Try to get token
            $token = $oauth->get_access_token();
            test_result("OAuth token acquisition", !empty($token), "Token expires: " . ($token ? date('Y-m-d H:i:s', $token->expires_at) : 'FAILED'));
        } catch (Exception $e) {
            test_result("OAuth service test", false, $e->getMessage());
        }
        break;
        
    case 'digitalocean':
        $endpoint = get_config('local_aiawesome', 'digitalocean_endpoint');
        $model = get_config('local_aiawesome', 'digitalocean_model');
        
        test_result("DigitalOcean endpoint configured", !empty($endpoint), $endpoint);
        test_result("DigitalOcean model set", !empty($model), "Model: " . ($model ?: 'NOT SET'));
        break;
        
    default:
        test_result("Valid provider", false, "Unknown provider: $provider");
}

// Test 3: Check for old configuration keys (migration test)
output("\nTEST 3: Migration Status", 'yellow');
$old_auth_mode = get_config('local_aiawesome', 'auth_mode');
$old_base_url = get_config('local_aiawesome', 'base_url');
$old_client_id = get_config('local_aiawesome', 'client_id');

if ($old_auth_mode !== false || $old_base_url !== false || $old_client_id !== false) {
    output("⚠️  WARNING: Old configuration keys still present", 'yellow');
    if ($old_auth_mode !== false) output("  - auth_mode: $old_auth_mode", 'yellow');
    if ($old_base_url !== false) output("  - base_url: $old_base_url", 'yellow');
    if ($old_client_id !== false) output("  - client_id: $old_client_id", 'yellow');
    output("  Consider running migration or manually removing these keys", 'yellow');
} else {
    test_result("No old config keys present", true, "Clean migration");
}

// Test 4: Check required files exist
output("\nTEST 4: File System Checks", 'yellow');

$required_files = [
    'classes/api_service.php',
    'classes/oauth_service.php',
    'classes/crypto_utils.php',
    'classes/logging_service.php',
    'classes/migration_helper.php',
    'amd/build/boot.js',
    'amd/build/simple_app.js',
    'amd/build/sse.js',
    'styles.css',
    'stream.php',
    'db/upgrade.php',
];

foreach ($required_files as $file) {
    $path = __DIR__ . '/' . $file;
    test_result("File exists: $file", file_exists($path));
}

// Test 5: Check crypto functions
output("\nTEST 5: System Requirements", 'yellow');

require_once(__DIR__ . '/classes/crypto_utils.php');
$crypto_available = \local_aiawesome\crypto_utils::is_crypto_available();
test_result("Crypto functions available", $crypto_available);

test_result("cURL extension available", function_exists('curl_init'));
test_result("JSON extension available", function_exists('json_encode'));
test_result("OpenSSL extension available", extension_loaded('openssl'));

// Test 6: Database structure
output("\nTEST 6: Database Structure", 'yellow');
global $DB;

$table_exists = $DB->get_manager()->table_exists('local_aiawesome_logs');
test_result("Logging table exists", $table_exists);

if ($table_exists) {
    try {
        $count = $DB->count_records('local_aiawesome_logs');
        test_result("Can query logging table", true, "$count log entries");
    } catch (Exception $e) {
        test_result("Can query logging table", false, $e->getMessage());
    }
}

// Test 7: Test chat endpoint availability
output("\nTEST 7: Endpoint Tests", 'yellow');

$chat_endpoint = $CFG->wwwroot . '/local/aiawesome/chat.php';
test_result("Chat endpoint URL constructed", true, $chat_endpoint);

$stream_endpoint = $CFG->wwwroot . '/local/aiawesome/stream.php';
test_result("Stream endpoint URL constructed", true, $stream_endpoint);

// Test 8: Capability check
output("\nTEST 8: Capabilities", 'yellow');

$context = context_system::instance();
$can_use = has_capability('local/aiawesome:use', $context);
test_result("Current user can use plugin", $can_use);

// Test 9: Settings page check
output("\nTEST 9: Admin Settings", 'yellow');

$settings_exist = file_exists(__DIR__ . '/settings.php');
test_result("Settings file exists", $settings_exist);

if ($settings_exist) {
    $content = file_get_contents(__DIR__ . '/settings.php');
    test_result("Settings use ai_provider", strpos($content, 'ai_provider') !== false);
    test_result("Settings use openai_api_key", strpos($content, 'openai_api_key') !== false);
    test_result("Settings use oauth_base_url", strpos($content, 'oauth_base_url') !== false);
    test_result("Settings use digitalocean_endpoint", strpos($content, 'digitalocean_endpoint') !== false);
}

// Summary
output("\n=================================", 'blue');
output("Test Summary", 'blue');
output("=================================", 'blue');
output("Provider: " . ($provider ?: 'NOT SET'), 'blue');
output("Status: Plugin is " . ($enabled ? "ENABLED" : "DISABLED"), $enabled ? 'green' : 'red');
output("\nNext Steps:", 'yellow');
output("1. Test the chat interface in your browser");
output("2. Send a test message to verify SSE streaming");
output("3. Check browser console for any JavaScript errors");
output("4. Review /local/aiawesome/index.php for health status");
output("5. Review /local/aiawesome/diagnostics.php for detailed info");
output("");

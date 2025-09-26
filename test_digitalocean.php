<?php
// Test script for DigitalOcean integration
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_aiawesome\api_service;

echo "Testing AI Awesome DigitalOcean Integration\n";
echo "==========================================\n\n";

// Test current provider
$provider = get_config('local_aiawesome', 'ai_provider');
echo "Current provider: " . ($provider ?: 'not set') . "\n";

// Create API service instance
try {
    $api = new api_service();
    
    echo "API Service created successfully\n";
    
    // Test endpoint
    $endpoint = $api->get_api_endpoint();
    echo "API Endpoint: " . ($endpoint ?: 'not configured') . "\n";
    
    // Test auth header
    $auth = $api->get_auth_header();
    echo "Auth Header: " . ($auth ? 'configured' : 'not configured') . "\n";
    
    // Test additional headers
    $headers = $api->get_additional_headers();
    echo "Additional Headers: " . count($headers) . " configured\n";
    
    if ($provider === 'digitalocean') {
        echo "\nDigitalOcean Configuration:\n";
        echo "- Endpoint: " . get_config('local_aiawesome', 'digitalocean_endpoint') . "\n";
        echo "- Model: " . get_config('local_aiawesome', 'digitalocean_model') . "\n";
        echo "- API Key: " . (get_config('local_aiawesome', 'digitalocean_api_key') ? 'set' : 'not set') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
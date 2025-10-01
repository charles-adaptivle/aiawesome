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
 * AJAX endpoint to fetch available models from AI providers.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

// Require login and admin capability.
require_login();
require_capability('moodle/site:config', context_system::instance());

// Check request method.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get input.
$input = json_decode(file_get_contents('php://input'), true);
$provider = $input['provider'] ?? null;

if (!$provider || !in_array($provider, ['openai', 'digitalocean'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid provider']);
    exit;
}

// Validate CSRF token.
if (!isset($input['sesskey']) || $input['sesskey'] !== sesskey()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid session key']);
    exit;
}

header('Content-Type: application/json');

try {
    $models = fetch_models_for_provider($provider);
    echo json_encode(['success' => true, 'models' => $models]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Fetch available models for a provider.
 *
 * @param string $provider Provider name
 * @return array Array of model objects
 * @throws Exception If API call fails
 */
function fetch_models_for_provider(string $provider): array {
    global $CFG;
    
    if ($provider === 'openai') {
        return fetch_openai_models();
    } elseif ($provider === 'digitalocean') {
        return fetch_digitalocean_models();
    }
    
    throw new Exception('Unknown provider');
}

/**
 * Fetch models from OpenAI API.
 *
 * @return array Array of model objects
 * @throws Exception If API call fails
 */
function fetch_openai_models(): array {
    $api_key = get_config('local_aiawesome', 'openai_api_key');
    
    if (empty($api_key)) {
        throw new Exception('OpenAI API key not configured');
    }
    
    $curl = new curl();
    $curl->setHeader('Authorization: Bearer ' . $api_key);
    $curl->setHeader('Content-Type: application/json');
    
    $response = $curl->get('https://api.openai.com/v1/models');
    $httpcode = $curl->info['http_code'];
    
    if ($httpcode !== 200) {
        throw new Exception('OpenAI API error (HTTP ' . $httpcode . ')');
    }
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['data'])) {
        throw new Exception('Invalid response from OpenAI API');
    }
    
    // Filter for chat models and sort by ID.
    $chat_models = array_filter($data['data'], function($model) {
        $id = $model['id'];
        // Include GPT models and common chat models.
        return (strpos($id, 'gpt-') === 0 || 
                strpos($id, 'o1-') === 0 ||
                strpos($id, 'chatgpt-') === 0) &&
               !strpos($id, 'instruct'); // Exclude instruct models.
    });
    
    // Extract and sort model IDs.
    $models = array_map(function($model) {
        return [
            'id' => $model['id'],
            'name' => $model['id'], // OpenAI uses ID as name.
            'created' => $model['created'] ?? null,
        ];
    }, $chat_models);
    
    // Sort by ID (newer models first based on naming convention).
    usort($models, function($a, $b) {
        return strcmp($b['id'], $a['id']);
    });
    
    return $models;
}

/**
 * Fetch models from DigitalOcean API.
 *
 * @return array Array of model objects
 * @throws Exception If API call fails
 */
function fetch_digitalocean_models(): array {
    $endpoint = get_config('local_aiawesome', 'digitalocean_endpoint');
    
    if (empty($endpoint)) {
        throw new Exception('DigitalOcean endpoint not configured');
    }
    
    // DigitalOcean uses OpenAI-compatible API, so models endpoint should work.
    $models_url = rtrim($endpoint, '/') . '/models';
    
    $curl = new curl();
    $curl->setHeader('Content-Type: application/json');
    
    $response = $curl->get($models_url);
    $httpcode = $curl->info['http_code'];
    
    if ($httpcode !== 200) {
        // If models endpoint doesn't exist, return common models.
        return get_default_digitalocean_models();
    }
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['data'])) {
        return get_default_digitalocean_models();
    }
    
    // Extract model IDs.
    $models = array_map(function($model) {
        return [
            'id' => $model['id'],
            'name' => $model['id'],
            'created' => $model['created'] ?? null,
        ];
    }, $data['data']);
    
    return $models;
}

/**
 * Get default DigitalOcean models if API doesn't provide list.
 *
 * @return array Array of default model objects
 */
function get_default_digitalocean_models(): array {
    // Common DigitalOcean models (as of 2025).
    return [
        ['id' => 'meta-llama/Llama-3.2-90B-Vision-Instruct', 'name' => 'Llama 3.2 90B Vision Instruct'],
        ['id' => 'meta-llama/Llama-3.2-11B-Vision-Instruct', 'name' => 'Llama 3.2 11B Vision Instruct'],
        ['id' => 'meta-llama/Llama-3.1-70B-Instruct', 'name' => 'Llama 3.1 70B Instruct'],
        ['id' => 'meta-llama/Llama-3.1-8B-Instruct', 'name' => 'Llama 3.1 8B Instruct'],
        ['id' => 'meta-llama/Meta-Llama-3-70B-Instruct', 'name' => 'Llama 3 70B Instruct'],
        ['id' => 'meta-llama/Meta-Llama-3-8B-Instruct', 'name' => 'Llama 3 8B Instruct'],
    ];
}

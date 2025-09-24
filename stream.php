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
 * Server-Sent Events proxy endpoint for AI Awesome plugin.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

use local_aiawesome\api_service;
use local_aiawesome\crypto_utils;
use local_aiawesome\logging_service;

// Require login and capability.
require_login();
require_capability('local/aiawesome:use', context_system::instance());

// Check if plugin is enabled.
if (!get_config('local_aiawesome', 'enabled')) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['error' => get_string('error_disabled', 'local_aiawesome')]);
    exit;
}

// Validate request method.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get and validate input.
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['query']) || !isset($input['session'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid input - query and session required']);
    exit;
}

// Validate CSRF token.
if (!confirm_sesskey()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid session key']);
    exit;
}

// Check rate limiting.
if (!logging_service::check_rate_limit($USER->id)) {
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['error' => get_string('error_rate_limit', 'local_aiawesome')]);
    exit;
}

$query = trim($input['query']);
$sessionid = $input['session'];
$courseid = $input['courseid'] ?? null;

if (empty($query)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Empty query']);
    exit;
}

// Validate course access if courseid provided.
if ($courseid) {
    try {
        $course = get_course($courseid);
        $context = context_course::instance($courseid);
        require_capability('moodle/course:view', $context);
    } catch (Exception $e) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Course access denied']);
        exit;
    }
}

// Set up SSE headers.
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // For nginx.

// Close session to allow concurrent requests.
session_write_close();

// Initialize services.
$api = new api_service();
$logid = 0;
$starttime = microtime(true);

try {
    // Check API configuration.
    $endpoint = $api->get_api_endpoint();
    $auth_header = $api->get_auth_header();
    
    if (!$endpoint || !$auth_header) {
        send_sse_error('CONFIG_ERROR', 'API service not configured');
        exit;
    }

    // Build context.
    $context_data = [
        'userId' => $USER->id,
        'courseId' => $courseid,
        'userInfo' => [
            'fullname' => fullname($USER),
            'username' => $USER->username,
        ],
    ];

    // Add enrolled courses if available.
    if ($courseid) {
        $context_data['courseName'] = $course->fullname ?? '';
        $enrolled_courses = enrol_get_users_courses($USER->id, true, 'id,fullname,shortname');
        $context_data['enrolledCourseIds'] = array_keys($enrolled_courses);
    }

    // Create log entry.
    $logid = logging_service::create_log_entry($sessionid, $courseid);

    // Prepare request payload using the API service.
    $request_body = $api->prepare_chat_payload($query, $context_data);
    $bytes_up = strlen(json_encode($request_body));

    // Get additional headers.
    $additional_headers = $api->get_additional_headers();

    // Prepare headers array.
    $headers = ['Authorization: ' . $auth_header];
    foreach ($additional_headers as $key => $value) {
        $headers[] = $key . ': ' . $value;
    }
    $headers[] = 'Accept: text/event-stream';

    // Initialize cURL for streaming.
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $endpoint,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_body),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_WRITEFUNCTION => 'handle_sse_chunk',
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    // Global variables for chunk handling.
    global $chunk_buffer, $bytes_received, $first_token_time, $tokens_count;
    $chunk_buffer = '';
    $bytes_received = 0;
    $first_token_time = null;
    $tokens_count = 0;

    // Execute the request.
    $success = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);

    if (!$success || $httpcode !== 200) {
        $error_msg = $curl_error ?: "HTTP $httpcode";
        send_sse_error('UPSTREAM_ERROR', "AI service error: $error_msg");
        
        if ($logid) {
            logging_service::update_log_entry($logid, [
                'status' => 'error',
                'error' => $error_msg,
                'bytes_up' => $bytes_up,
                'duration_ms' => (int) ((microtime(true) - $starttime) * 1000),
            ]);
        }
        exit;
    }

    // Send final event and update log.
    send_sse_event('final_response', ['status' => 'completed']);
    
    if ($logid) {
        $duration = (int) ((microtime(true) - $starttime) * 1000);
        $ttff = $first_token_time ? (int) (($first_token_time - $starttime) * 1000) : null;
        
        logging_service::update_log_entry($logid, [
            'status' => 'completed',
            'bytes_up' => $bytes_up,
            'bytes_down' => $bytes_received,
            'duration_ms' => $duration,
            'ttff_ms' => $ttff,
            'tokens_used' => $tokens_count,
            'content' => get_config('local_aiawesome', 'log_content') ? $query : null,
        ]);
    }

} catch (Exception $e) {
    send_sse_error('SYSTEM_ERROR', 'System error occurred');
    
    if ($logid) {
        logging_service::update_log_entry($logid, [
            'status' => 'error',
            'error' => $e->getMessage(),
            'duration_ms' => (int) ((microtime(true) - $starttime) * 1000),
        ]);
    }
}

/**
 * Handle SSE chunks from upstream.
 *
 * @param resource $curl cURL handle
 * @param string $data Chunk data
 * @return int Number of bytes processed
 */
function handle_sse_chunk($curl, $data): int {
    global $chunk_buffer, $bytes_received, $first_token_time, $tokens_count;
    
    $bytes_received += strlen($data);
    $chunk_buffer .= $data;
    
    // Process complete lines.
    while (($pos = strpos($chunk_buffer, "\n")) !== false) {
        $line = substr($chunk_buffer, 0, $pos);
        $chunk_buffer = substr($chunk_buffer, $pos + 1);
        
        $line = trim($line);
        if (empty($line)) {
            continue;
        }

        // Parse SSE format.
        if (strpos($line, 'data: ') === 0) {
            $json = substr($line, 6);
            $event_data = json_decode($json, true);
            
            if ($event_data) {
                // Track first token time.
                if (!$first_token_time && isset($event_data['text'])) {
                    $first_token_time = microtime(true);
                }
                
                // Count tokens (approximate).
                if (isset($event_data['text'])) {
                    $tokens_count += str_word_count($event_data['text']);
                }
                
                // Forward to client.
                echo "data: " . $json . "\n\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }
        } elseif (strpos($line, 'event: ') === 0) {
            // Forward event type.
            echo $line . "\n";
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }
    }
    
    return strlen($data);
}

/**
 * Send SSE event to client.
 *
 * @param string $event Event type
 * @param array $data Event data
 */
function send_sse_event(string $event, array $data): void {
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level()) {
        ob_flush();
    }
    flush();
}

/**
 * Send SSE error event to client.
 *
 * @param string $code Error code
 * @param string $message Error message
 */
function send_sse_error(string $code, string $message): void {
    send_sse_event('error', ['code' => $code, 'message' => $message]);
}
<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://ivan.dev.test');
header('Access-Control-Allow-Credentials: true');

echo json_encode([
    'authenticated' => isloggedin(),
    'userid' => $USER->id ?? 0,
    'cookies' => $_COOKIE,
    'session_id' => session_id(),
    'session_name' => session_name(),
    'headers' => getallheaders(),
    'post_data' => json_decode(file_get_contents('php://input'), true)
]);
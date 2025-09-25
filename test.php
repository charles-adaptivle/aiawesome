<?php
// Simple test endpoint
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok', 
    'message' => 'Test endpoint working',
    'method' => $_SERVER['REQUEST_METHOD'],
    'path' => $_SERVER['REQUEST_URI'],
    'time' => date('Y-m-d H:i:s')
]);
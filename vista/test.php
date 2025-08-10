<?php
header('Content-Type: application/json');
session_start();

// Simple test response
echo json_encode([
    'success' => true, 
    'message' => 'Test successful',
    'data' => [
        'session_id' => session_id(),
        'cliente_id' => $_SESSION['cliente_id'] ?? 'not set',
        'post_data' => $_POST
    ]
]);
?>

<?php
session_start();
require_once '../config/db.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

// Return user info
echo json_encode([
    'full_name' => $_SESSION['full_name'],
    'role_id' => $_SESSION['role_id']
]);
?>
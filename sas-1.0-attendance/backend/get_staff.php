<?php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Only admins
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != ROLE_ADMIN) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

$result = $conn->query("SELECT user_id, full_name, email, is_active FROM users WHERE role_id = " . ROLE_STAFF);
$staff = [];
while ($row = $result->fetch_assoc()) {
    $staff[] = $row;
}
echo json_encode($staff);
?>
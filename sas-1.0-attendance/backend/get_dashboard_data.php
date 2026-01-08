<?php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Only admins can access
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != ROLE_ADMIN) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Fetch total staff
$result = $conn->query("SELECT COUNT(*) as total_staff FROM users WHERE role_id = " . ROLE_STAFF . " AND is_active = TRUE");
$totalStaff = $result->fetch_assoc()['total_staff'];

// Fetch today's attendance
$today = date('Y-m-d');
$result = $conn->query("SELECT COUNT(*) as today_attendance FROM attendance WHERE date = '$today' AND sign_in_time IS NOT NULL");
$todayAttendance = $result->fetch_assoc()['today_attendance'];

// Return as JSON
echo json_encode([
    'totalStaff' => $totalStaff,
    'todayAttendance' => $todayAttendance
]);
?>
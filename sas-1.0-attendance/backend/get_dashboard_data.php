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

// Fetch today's attendance using prepared statement to prevent SQL injection
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) as today_attendance FROM attendance WHERE date = ? AND sign_in_time IS NOT NULL");
if (!$stmt) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit();
}
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$todayAttendance = $result->fetch_assoc()['today_attendance'];
$stmt->close();

// Return as JSON
echo json_encode([
    'totalStaff' => $totalStaff,
    'todayAttendance' => $todayAttendance
]);
?>
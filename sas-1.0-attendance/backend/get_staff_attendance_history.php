<?php
/**
 * Get Staff Attendance History
 * Returns attendance records for authenticated staff member
 */

header('Content-Type: application/json');
session_start();

require_once '../config/db.php';
require_once '../config/constants.php';

// Only logged-in staff
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != ROLE_STAFF) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit();
}

$user_id = $_SESSION['user_id'];
$month = $_GET['month'] ?? date('Y-m');
$limit = intval($_GET['limit'] ?? 31);

// Validate month format (YYYY-MM)
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid month format. Use YYYY-MM']);
    exit();
}

$month_start = $month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

$stmt = $conn->prepare("
    SELECT 
        attendance_id,
        date,
        sign_in_time,
        sign_out_time,
        CASE 
            WHEN sign_in_time IS NULL THEN 'Absent'
            WHEN sign_out_time IS NULL THEN 'Present (No Sign Out)'
            ELSE 'Present'
        END AS status,
        CASE
            WHEN sign_in_time > '11:00:00' THEN 'Late'
            ELSE 'On Time'
        END AS punctuality
    FROM attendance
    WHERE user_id = ? AND date >= ? AND date <= ?
    ORDER BY date DESC
    LIMIT ?
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param('issi', $user_id, $month_start, $month_end, $limit);
$stmt->execute();
$result = $stmt->get_result();

$attendance = [];
while ($row = $result->fetch_assoc()) {
    $attendance[] = $row;
}
$stmt->close();

// Calculate statistics
$stats_stmt = $conn->prepare("
    SELECT
        COUNT(*) as total_days,
        SUM(CASE WHEN sign_in_time IS NOT NULL THEN 1 ELSE 0 END) as days_present,
        SUM(CASE WHEN sign_in_time IS NULL THEN 1 ELSE 0 END) as days_absent,
        SUM(CASE WHEN sign_in_time > '11:00:00' THEN 1 ELSE 0 END) as late_arrivals
    FROM attendance
    WHERE user_id = ? AND date >= ? AND date <= ?
");

$stats_stmt->bind_param('iss', $user_id, $month_start, $month_end);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

echo json_encode([
    'status' => 'success',
    'month' => $month,
    'data' => $attendance,
    'statistics' => $stats
]);
?>

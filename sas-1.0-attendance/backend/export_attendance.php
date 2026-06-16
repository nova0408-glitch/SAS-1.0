<?php
/**
 * Export Attendance Records
 * Generates CSV file for attendance data
 */

session_start();

require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../config/AuditLog.php';

$auditLog = new AuditLog($conn);

// Only admins
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != ROLE_ADMIN) {
    http_response_code(403);
    die('Access denied');
}

$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$format = $_GET['format'] ?? 'csv'; // csv or json

// Validate date format
if (!strtotime($date_from) || !strtotime($date_to)) {
    http_response_code(400);
    die('Invalid date format');
}

$stmt = $conn->prepare("
    SELECT 
        u.full_name,
        u.email,
        a.date,
        a.sign_in_time,
        a.sign_out_time,
        CASE 
            WHEN a.sign_in_time IS NULL THEN 'Absent'
            WHEN a.sign_out_time IS NULL THEN 'Present (No Sign Out)'
            ELSE 'Present'
        END AS status,
        CASE
            WHEN a.sign_in_time > '11:00:00' THEN 'Late'
            ELSE 'On Time'
        END AS punctuality
    FROM users u
    LEFT JOIN attendance a ON u.user_id = a.user_id AND a.date >= ? AND a.date <= ?
    WHERE u.role_id = ? AND u.is_active = TRUE AND u.is_deleted = FALSE
    ORDER BY u.full_name, a.date DESC
");

if (!$stmt) {
    http_response_code(500);
    die('Database error');
}

$role = ROLE_STAFF;
$stmt->bind_param('ssi', $date_from, $date_to, $role);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
$stmt->close();

$auditLog->log('export_attendance', "From: $date_from To: $date_to Format: $format", 'success');

if ($format === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="attendance_' . date('Y-m-d') . '.json"');
    echo json_encode($data, JSON_PRETTY_PRINT);
} else {
    // CSV format
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Header
    fputcsv($output, ['Full Name', 'Email', 'Date', 'Sign In Time', 'Sign Out Time', 'Status', 'Punctuality']);
    
    // CSV Data
    foreach ($data as $row) {
        fputcsv($output, [
            $row['full_name'],
            $row['email'],
            $row['date'],
            $row['sign_in_time'] ?? 'N/A',
            $row['sign_out_time'] ?? 'N/A',
            $row['status'] ?? 'N/A',
            $row['punctuality'] ?? 'N/A'
        ]);
    }
    
    fclose($output);
}
?>

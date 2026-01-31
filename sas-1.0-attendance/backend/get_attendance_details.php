<?php
/**
 * Get Detailed Attendance Records
 * Returns all staff attendance for a specific date with sign-in/out times
 */

session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Only admins can access
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != ROLE_ADMIN) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

$date = $_GET['date'] ?? date('Y-m-d');

// Validate date format
if (!strtotime($date)) {
    echo json_encode(['error' => 'Invalid date format']);
    exit();
}

// Get all staff with their attendance for the specified date
$query = "
    SELECT 
        u.user_id,
        u.full_name,
        u.email,
        COALESCE(a.sign_in_time, 'Not Signed In') as sign_in_time,
        COALESCE(a.sign_out_time, 'Not Signed Out') as sign_out_time,
        CASE 
            WHEN a.sign_in_time IS NULL THEN 'Absent'
            WHEN a.sign_out_time IS NULL THEN 'Present (No Sign Out)'
            ELSE 'Present'
        END AS status
    FROM users u
    LEFT JOIN attendance a ON u.user_id = a.user_id AND a.date = ?
    WHERE u.role_id = ? AND u.is_active = TRUE
    ORDER BY u.full_name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("si", $date, $role);
$role = ROLE_STAFF;
$stmt->execute();
$result = $stmt->get_result();

$attendance = [];
while ($row = $result->fetch_assoc()) {
    $attendance[] = $row;
}

echo json_encode([
    'status' => 'success',
    'date' => $date,
    'data' => $attendance
]);
?>
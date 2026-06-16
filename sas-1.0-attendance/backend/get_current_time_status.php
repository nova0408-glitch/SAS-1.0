<?php
/**
 * Get Current Time Status
 * Returns current time and whether staff can sign in/out
 */

header('Content-Type: application/json');
session_start();

require_once '../config/db.php';
require_once '../config/constants.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != ROLE_STAFF) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Not authorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$now = new DateTime('now', new DateTimeZone('Africa/Dar_es_Salaam'));
$current = $now->format('H:i:s');
$current_time_display = $now->format('H:i');

// Sign-in allowed 07:00 - 11:00 (matching record_attendance.php)
$can_sign_in  = ($current >= '07:00:00' && $current <= '11:00:00');
$can_sign_out = ($current >= '15:00:00' && $current <= '18:00:00');

$stmt = $conn->prepare("
    SELECT sign_in_time, sign_out_time 
    FROM attendance 
    WHERE user_id = ? AND date = ?
    LIMIT 1
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit();
}

$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

$has_signed_in  = $record && $record['sign_in_time'] !== null;
$has_signed_out = $record && $record['sign_out_time'] !== null;

echo json_encode([
    'status' => 'success',
    'can_sign_in'          => $can_sign_in,
    'can_sign_out'         => $can_sign_out,
    'has_signed_in_today'  => $has_signed_in,
    'has_signed_out_today' => $has_signed_out,
    'current_time'         => $current_time_display,
    'sign_in_window'       => '07:00 - 11:00',
    'sign_out_window'      => '15:00 - 18:00'
]);
?>

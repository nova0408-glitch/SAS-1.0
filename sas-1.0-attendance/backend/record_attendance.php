<?php
/**
 * Staff Attendance Recording Endpoint
 * Handles sign-in and sign-out actions via AJAX
 * Time restrictions:
 *   Sign-in:  07:00 – 11:00
 *   Sign-out: 15:00 – 18:00
 */

session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Only logged-in staff
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != ROLE_STAFF) {
    http_response_code(403);
    echo json_encode([
        "status"  => "error",
        "message" => "Access denied. Please login first."
    ]);
    exit();
}

header('Content-Type: application/json');

$action   = $_GET['action'] ?? '';
$user_id  = $_SESSION['user_id'];
$today    = date('Y-m-d');

// Force Tanzania timezone
date_default_timezone_set('Africa/Dar_es_Salaam');

$now = new DateTime('now');
$currentTime = $now->format('H:i:s');

if ($action === 'sign_in') {
    // Sign-in allowed only 07:00 – 11:00
    $signInStart = '07:00:00';
    $signInEnd   = '11:00:00';

    if ($currentTime < $signInStart || $currentTime > $signInEnd) {
        echo json_encode([
            "status"  => "error",
            "message" => "Sign-in is only allowed between 07:00 and 11:00."
        ]);
        exit();
    }

    // Check if already signed in today
    $stmt = $conn->prepare("SELECT attendance_id FROM attendance WHERE user_id = ? AND date = ? LIMIT 1");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode([
            "status"  => "info",
            "message" => "⚠️ You have already signed in today."
        ]);
    } else {
        $sign_in_time = $currentTime;

        $insert = $conn->prepare("
            INSERT INTO attendance (user_id, date, sign_in_time) 
            VALUES (?, ?, ?)
        ");
        $insert->bind_param("iss", $user_id, $today, $sign_in_time);

        if ($insert->execute()) {
            echo json_encode([
                "status"  => "success",
                "message" => "✅ Signed in at $sign_in_time"
            ]);
        } else {
            echo json_encode([
                "status"  => "error",
                "message" => "Failed to record sign-in. Please try again."
            ]);
        }
        $insert->close();
    }
    $stmt->close();

} elseif ($action === 'sign_out') {
    // Sign-out allowed only 15:00 – 18:00
    $signOutStart = '15:00:00';
    $signOutEnd   = '18:00:00';

    if ($currentTime < $signOutStart || $currentTime > $signOutEnd) {
        echo json_encode([
            "status"  => "error",
            "message" => "Sign-out is only allowed between 15:00 and 18:00."
        ]);
        exit();
    }

    // Fetch today's record
    $stmt = $conn->prepare("
        SELECT attendance_id, sign_in_time, sign_out_time 
        FROM attendance 
        WHERE user_id = ? AND date = ? 
        LIMIT 1
    ");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $stmt->close();

    if (!$record) {
        echo json_encode([
            "status"  => "error",
            "message" => "❌ You must sign in first today."
        ]);
        exit();
    }

    $attendance_id = $record['attendance_id'];
    $sign_in_time  = $record['sign_in_time'];
    $sign_out_time = $record['sign_out_time'];

    // Already signed out
    if ($sign_out_time !== null) {
        echo json_encode([
            "status"  => "info",
            "message" => "⚠️ You have already signed out today at $sign_out_time."
        ]);
        exit();
    }

    // No sign-in (shouldn't happen but safety check)
    if ($sign_in_time === null) {
        echo json_encode([
            "status"  => "error",
            "message" => "❌ No valid sign-in record found."
        ]);
        exit();
    }

    // Record sign-out
    $sign_out_now = $currentTime;

    $update = $conn->prepare("
        UPDATE attendance 
        SET sign_out_time = ? 
        WHERE attendance_id = ?
    ");
    $update->bind_param("si", $sign_out_now, $attendance_id);

    if ($update->execute()) {
        echo json_encode([
            "status"  => "success",
            "message" => "✅ Signed out at $sign_out_now"
        ]);
    } else {
        echo json_encode([
            "status"  => "error",
            "message" => "❌ Failed to record sign-out. Please try again."
        ]);
    }
    $update->close();

} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Invalid action."
    ]);
}

$conn->close();
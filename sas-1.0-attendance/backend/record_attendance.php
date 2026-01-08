<?php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Only logged-in staff
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != ROLE_STAFF) {
    echo json_encode(["status"=>"error","message"=>"Access denied. Please login first."]);
    exit();
}

$action = $_GET['action'] ?? '';
$staff_id = $_SESSION['user_id'];
$today = date('Y-m-d');

header('Content-Type: application/json');

if($action === 'sign_in') {
    $stmt = $conn->prepare("SELECT attendance_id FROM attendance WHERE user_id=? AND date=? LIMIT 1");
    $stmt->bind_param("is", $staff_id, $today);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows === 0) {
        $sign_in_time = date('H:i:s');
        $stmt = $conn->prepare("INSERT INTO attendance (user_id, date, sign_in_time) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $staff_id, $today, $sign_in_time);
        $stmt->execute();
        echo json_encode(["status"=>"success","message"=>"Sign-in recorded at $sign_in_time"]);
    } else {
        echo json_encode(["status"=>"info","message"=>"You have already signed in today."]);
    }

} elseif($action === 'sign_out') {
    $stmt = $conn->prepare("SELECT attendance_id, sign_out_time FROM attendance WHERE user_id=? AND date=? LIMIT 1");
    $stmt->bind_param("is", $staff_id, $today);
    $stmt->execute();
    $stmt->bind_result($attendance_id, $sign_out_time);
    $stmt->fetch();

    if($attendance_id) {
        if(!$sign_out_time) {
            $time = date('H:i:s');
            $update = $conn->prepare("UPDATE attendance SET sign_out_time=? WHERE attendance_id=?");
            $update->bind_param("si", $time, $attendance_id);
            $update->execute();
            echo json_encode(["status"=>"success","message"=>"Sign-out recorded at $time"]);
        } else {
            echo json_encode(["status"=>"info","message"=>"You have already signed out today."]);
        }
    } else {
        echo json_encode(["status"=>"error","message"=>"You must sign in first."]);
    }

} else {
    echo json_encode(["status"=>"error","message"=>"Invalid action."]);
}
?>
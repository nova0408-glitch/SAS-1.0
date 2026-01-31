<?php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != ROLE_STAFF) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$month = $_GET['month'] ?? date('Y-m'); // e.g., 2026-01

$stmt = $conn->prepare("
    SELECT date, sign_in_time, sign_out_time,
           TIMEDIFF(sign_out_time, sign_in_time) AS duration
    FROM attendance 
    WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
    ORDER BY date DESC
");
$stmt->bind_param("is", $user_id, $month);
$stmt->execute();
$result = $stmt->get_result();

$records = [];
while ($row = $result->fetch_assoc()) {
    $records[] = [
        'date' => $row['date'],
        'in'   => $row['sign_in_time'] ?? '-',
        'out'  => $row['sign_out_time'] ?? '-',
        'hours'=> $row['duration'] ? 
                  sprintf('%dh %dm', 
                      floor(strtotime($row['duration']) / 3600),
                      (strtotime($row['duration']) % 3600) / 60
                  ) : '-'
    ];
}

echo json_encode(['records' => $records]);
$stmt->close();
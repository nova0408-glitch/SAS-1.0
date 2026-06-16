<?php
/**
 * Delete Staff Member
 * Handles deletion of staff accounts
 */

session_start();
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../config/csrf.php';

// Only admins
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != ROLE_ADMIN) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        exit();
    }

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    if ($user_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid staff ID.']);
        exit();
    }

    // Prevent deleting admin accounts (role_id = 1)
    $stmt = $conn->prepare("SELECT role_id FROM users WHERE user_id = ?");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($role_id);
    $stmt->fetch();
    $stmt->close();

    if (!$role_id) {
        echo json_encode(['status' => 'error', 'message' => 'Staff member not found.']);
        exit();
    }

    if ($role_id == ROLE_ADMIN) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete admin accounts.']);
        exit();
    }

    // Delete the staff member
    $role = ROLE_STAFF;
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role_id = ?");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("ii", $user_id, $role);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Staff member deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete staff: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>

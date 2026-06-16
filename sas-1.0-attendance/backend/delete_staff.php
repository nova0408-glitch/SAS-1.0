<?php
/**
 * Delete Staff Member (Soft Delete)
 * Implements soft delete by marking staff as deleted instead of hard delete
 */

header('Content-Type: application/json');
session_start();

require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../config/csrf.php';
require_once '../config/AuditLog.php';

$auditLog = new AuditLog($conn);

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
        $auditLog->log('delete_staff', 'Invalid CSRF token', 'failed');
        exit();
    }

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    if ($user_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid staff ID.']);
        exit();
    }

    // Prevent deleting admin accounts (role_id = 1)
    $stmt = $conn->prepare("SELECT role_id, full_name FROM users WHERE user_id = ? AND is_deleted = FALSE");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($role_id, $full_name);
    $stmt->fetch();
    $stmt->close();

    if (!$role_id) {
        echo json_encode(['status' => 'error', 'message' => 'Staff member not found.']);
        exit();
    }

    if ($role_id == ROLE_ADMIN) {
        $auditLog->log('delete_staff', "Attempted to delete admin: $full_name", 'failed');
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete admin accounts.']);
        exit();
    }

    // Soft delete - mark as deleted instead of hard delete
    $stmt = $conn->prepare("UPDATE users SET is_deleted = TRUE, is_active = FALSE WHERE user_id = ? AND role_id = ?");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    
    $role = ROLE_STAFF;
    $stmt->bind_param("ii", $user_id, $role);
    
    if ($stmt->execute()) {
        $auditLog->log('delete_staff', "Deleted staff: $full_name (ID: $user_id)", 'success');
        echo json_encode(['status' => 'success', 'message' => 'Staff member deactivated successfully.']);
    } else {
        $auditLog->log('delete_staff', "Failed to delete staff: $full_name", 'failed');
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete staff: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>

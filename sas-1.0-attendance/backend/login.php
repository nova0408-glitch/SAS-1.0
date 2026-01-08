<?php
/**
 * Admin Login Handler
 * Handles POST requests for admin authentication.
 * Validates input, checks credentials, and sets session on success.
 */

// Start session
session_start();

// Include dependencies
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../config/csrf.php';

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        die('Invalid CSRF token.');
    }

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Basic validation
    if (empty($email) || empty($password)) {
        die('Email and password are required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Invalid email format.');
    }

    // Prepare SQL to prevent SQL injection
    $stmt = $conn->prepare("SELECT user_id, full_name, password_hash, role_id, is_active FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $full_name, $password_hash, $role_id, $is_active);
        $stmt->fetch();

        // Check if account is active
        if (!$is_active) {
            die("This account is inactive.");
        }

        // Verify password
        if (password_verify($password, $password_hash)) {

            // Check if admin
            if ($role_id != ROLE_ADMIN) { // ROLE_ADMIN
                die("Access denied. Only admins can login here.");
            }

            // Login success → set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['role_id'] = $role_id;

            // Redirect to admin dashboard
            header("Location: ../frontend/admin_dashboard.php");
            exit();

        } else {
            die("Invalid password.");
        }

    } else {
        die("Email not found.");
    }

} else {
    die("Invalid request.");
}
?>
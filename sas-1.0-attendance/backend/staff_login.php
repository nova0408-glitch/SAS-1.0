<?php
/**
 * Staff Login Handler
 * Handles POST requests for staff authentication.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');

session_start();

require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../config/csrf.php';
require_once '../config/SessionManager.php';
require_once '../config/RateLimiter.php';
require_once '../config/AuditLog.php';

$rateLimiter = new RateLimiter($conn);
$auditLog = new AuditLog($conn);
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check rate limiting
    if ($rateLimiter->isLimitExceeded($client_ip, 'login')) {
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => 'Too many login attempts. Please try again later.']);
        exit();
    }
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $rateLimiter->recordAttempt($client_ip, 'login', 'failed');
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        exit();
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Basic validation
    if (empty($email) || empty($password)) {
        $rateLimiter->recordAttempt($client_ip, 'login', 'failed');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $rateLimiter->recordAttempt($client_ip, 'login', 'failed');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
        exit();
    }

    // Prepare SQL to prevent SQL injection
    $stmt = $conn->prepare("SELECT user_id, full_name, password_hash, role_id, is_active FROM users WHERE email = ? AND is_deleted = FALSE LIMIT 1");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
        exit();
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $full_name, $password_hash, $role_id, $is_active);
        $stmt->fetch();
        $stmt->close();

        // Check if account is active
        if (!$is_active) {
            $rateLimiter->recordAttempt($client_ip, 'login', 'failed');
            $auditLog->log('login_attempt', "Inactive account: $email", 'failed');
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'This account is inactive.']);
            exit();
        }

        // Verify password
        if (password_verify($password, $password_hash)) {

            // Check if staff
            if ($role_id != ROLE_STAFF) {
                $rateLimiter->recordAttempt($client_ip, 'login', 'failed');
                $auditLog->log('login_attempt', "Non-staff login attempt: $email", 'failed');
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Access denied. Only staff can login here.']);
                exit();
            }

            // Regenerate session ID for security
            SessionManager::regenerateSession();
            
            // Login success → set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['role_id'] = $role_id;
            $_SESSION['last_activity'] = time();

            // Record successful login
            $rateLimiter->recordAttempt($client_ip, 'login', 'success');
            $auditLog->log('staff_login', "Email: $email", 'success');
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Login successful',
                'redirect' => '../frontend/staff_dashboard.php'
            ]);
            exit();

        } else {
            $rateLimiter->recordAttempt($client_ip, 'login', 'failed');
            $auditLog->log('login_attempt', "Invalid password for: $email", 'failed');
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Invalid password.']);
            exit();
        }

    } else {
        $rateLimiter->recordAttempt($client_ip, 'login', 'failed');
        $auditLog->log('login_attempt', "Email not found: $email", 'failed');
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Email not found.']);
        exit();
    }

} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>

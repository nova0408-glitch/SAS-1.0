<!DOCTYPE html>
<html lang="en">
<?php
session_start();
require_once '../config/csrf.php';
$csrf_token = generateCsrfToken();
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Interface - SAS 1.0</title>
    <link rel="stylesheet" href="style2.css">
</head>

<body>
    <div class="login-container">
        <h1>Staff Attendance System</h1>
        <div class="login-card">
            <form id="staffLoginForm" method="POST" action="../backend/staff_login.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="holo-btn">Sign In</button>
            </form>
            <p class="login-note">Sign in here each day you enter or leave the office.</p>
        </div>
    </div>
    <script>
        // Simple client-side check
        document.getElementById('staffLoginForm').addEventListener('submit', function(e) {
            const email = this.email.value.trim();
            const password = this.password.value.trim();
            if (email === '' || password === '') {
                alert('Please enter both email and password.');
                e.preventDefault();
            }
        });
    </script>
</body>


</html>

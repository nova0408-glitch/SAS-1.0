<!DOCTYPE html>
<html lang="en">
<?php
session_start();
require_once '../config/constants.php';
require_once '../config/csrf.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != ROLE_ADMIN) {
    header("Location: index.php");
    exit();
}
$csrf_token = generateCsrfToken();
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - SAS 1.0</title>
    <link rel="stylesheet" href="style1.css">
</head>

<body>
    <div class="dashboard-container">
        <h1>Staff Management</h1>

        <div class="login-card">
            <h3>Add New Staff</h3>
            <form id="addStaffForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="text" name="full_name" placeholder="Full Name" required maxlength="100">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required minlength="8">
                <button type="submit" class="holo-btn">Add Staff</button>
            </form>
        </div>

        <button onclick="window.location.href='admin_dashboard.php'" class="holo-btn">Back to Dashboard</button>
    </div>

    <script>
        document.getElementById('addStaffForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const button = this.querySelector('button');
            button.textContent = 'Adding...';
            button.disabled = true;

            const formData = new FormData(this);

            fetch('../backend/add_staff.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    this.reset();
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                button.textContent = 'Add Staff';
                button.disabled = false;
            });
        });
    </script>
</body>

</html>
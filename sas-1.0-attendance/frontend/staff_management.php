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
            <h2>Add New Staff</h2>
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
        function showMessage(message, type = 'info') {
            // Remove existing message
            let existing = document.querySelector('.message-box');
            if (existing) existing.remove();

            const msgBox = document.createElement('div');
            msgBox.className = 'message-box ' + type;
            msgBox.textContent = message;
            msgBox.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                font-weight: bold;
                z-index: 9999;
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            `;
            
            if (type === 'success') {
                msgBox.style.backgroundColor = '#4CAF50';
                msgBox.style.color = 'white';
            } else if (type === 'error') {
                msgBox.style.backgroundColor = '#f44336';
                msgBox.style.color = 'white';
            } else {
                msgBox.style.backgroundColor = '#2196F3';
                msgBox.style.color = 'white';
            }
            
            document.body.appendChild(msgBox);

            setTimeout(() => {
                msgBox.style.opacity = '0';
                msgBox.style.transition = 'opacity 0.3s ease-out';
                setTimeout(() => msgBox.remove(), 300);
            }, 4000);
        }

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
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.status === 'success') {
                    showMessage('✅ ' + data.message, 'success');
                    document.getElementById('addStaffForm').reset();
                } else {
                    showMessage('❌ ' + data.message, 'error');
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
                showMessage('❌ Error: ' + err.message, 'error');
            })
            .finally(() => {
                button.textContent = 'Add Staff';
                button.disabled = false;
            });
        });
    </script>
</body>

</html>
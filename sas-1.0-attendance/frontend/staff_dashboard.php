<!DOCTYPE html>
<html lang="en">
<?php
session_start();
require_once '../config/constants.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != ROLE_STAFF) {
    header("Location: staff_login.php");
    exit();
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> SAS 1.0</title>
    <link rel="stylesheet" href="style3.css">
</head>

<body>
    <div class="dashboard-container">
        <h1>Welcome, <span id="staffName">Staff</span></h1>

        <div class="dashboard-cards">
            <div class="holo-card">
                <h3>Sign In / Sign Out</h3>
                <button onclick="signIn()" class="holo-btn" id="signInBtn">Sign In</button><br>
                <br>
                <br>
                <button onclick="signOut()" class="holo-btn" id="signOutBtn">Sign Out</button>
            </div>
        </div>
    </div>

    <script>
        function showPopup(message, type = "info") {
            // Remove existing popup if any
            let existing = document.querySelector('.popup');
            if (existing) existing.remove();

            // Create popup
            const popup = document.createElement('div');
            popup.className = 'popup ' + type;
            popup.textContent = message;
            document.body.appendChild(popup);

            // Animate and remove after 3s
            setTimeout(() => popup.remove(), 3000);
        // Load user info
        function loadUserInfo() {
            fetch('../backend/get_user_info.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        window.location.href = 'staff_login.php';
                    } else {
                        document.getElementById('staffName').textContent = data.full_name;
                    }
                })
                .catch(err => console.error(err));
        }

        // Sign In / Sign Out functions
        function signIn() {
            const btn = document.getElementById('signInBtn');
            btn.disabled = true;
            btn.textContent = 'Signing In...';
            fetch('../backend/record_attendance.php?action=sign_in')
                .then(res => res.json())
                .then(data => showPopup(data.message, data.status))
                .catch(err => console.error(err))
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Sign In';
                });
        }

        function signOut() {
            const btn = document.getElementById('signOutBtn');
            btn.disabled = true;
            btn.textContent = 'Signing Out...';
            fetch('../backend/record_attendance.php?action=sign_out')
                .then(res => res.json())
                .then(data => showPopup(data.message, data.status))
                .catch(err => console.error(err))
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Sign Out';
                });
        }
        window.onload = loadUserInfo;
    </script>
</body>

</html>

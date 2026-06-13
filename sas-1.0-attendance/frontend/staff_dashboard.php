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
    <title>SAS 1.0 - Staff Dashboard</title>
    <link rel="stylesheet" href="style3.css">
</head>

<body>
    <div class="dashboard-container">
        <h1>Welcome, <span id="staffName">Staff</span></h1>

        <div class="dashboard-cards">
            <div class="holo-card">
                <h3>Sign In / Sign Out</h3>

                <button id="signInBtn" class="holo-btn" onclick="signIn()">Sign In</button>
                <br><br>
                <button id="signOutBtn" class="holo-btn" onclick="signOut()" disabled>Sign Out</button>

                <p id="statusMessage" style="margin-top: 20px; font-size: 15px; font-weight: 500; min-height: 24px;"></p>
                <p id="timeStatus" style="font-size: 13px; color: #ffaa00; margin-top: 10px;">
                    Checking current time...
                </p>
            </div>
        </div>

        <button onclick="window.location.href='../frontend/staff_login.php'" class="holo-btn" style="margin-top: 30px;">
            Logout
        </button>
    </div>

    <script>
        const signInBtn = document.getElementById('signInBtn');
        const signOutBtn = document.getElementById('signOutBtn');
        const statusMsg = document.getElementById('statusMessage');
        const timeStatus = document.getElementById('timeStatus');

        let isProcessing = false;

        function showPopup(message, type = "info") {
            let existing = document.querySelector('.popup');
            if (existing) existing.remove();

            const popup = document.createElement('div');
            popup.className = `popup ${type}`;
            popup.textContent = message;
            popup.style.cssText = `
                position: fixed; top: 20px; right: 20px; padding: 15px 25px;
                border-radius: 12px; font-weight: bold; color: #000; z-index: 1000;
                box-shadow: 0 4px 15px rgba(0,0,0,0.4); opacity: 0; transform: translateY(-20px);
                transition: all 0.4s ease;
            `;
            if (type === 'success') popup.style.background = 'linear-gradient(45deg, #28a745, #34d058)';
            if (type === 'error')   popup.style.background = 'linear-gradient(45deg, #dc3545, #ff5252)';
            if (type === 'info')    popup.style.background = 'linear-gradient(45deg, #17a2b8, #20c9e0)';

            document.body.appendChild(popup);
            setTimeout(() => { popup.style.opacity = '1'; popup.style.transform = 'translateY(0)'; }, 10);
            setTimeout(() => {
                popup.style.opacity = '0'; popup.style.transform = 'translateY(-20px)';
                setTimeout(() => popup.remove(), 400);
            }, 4000);
        }

        function signIn() {
            if (isProcessing) return;
            isProcessing = true;

            const originalText = signInBtn.textContent;
            signInBtn.disabled = true;
            signInBtn.textContent = 'Signing in...';

            fetch('../backend/record_attendance.php?action=sign_in')
                .then(res => res.json())
                .then(data => {
                    showPopup(data.message, data.status);
                    if (data.status === 'success') {
                        signInBtn.textContent = 'Signed In ✓';
                        signInBtn.style.background = 'linear-gradient(45deg, #28a745, #218838)';
                        signInBtn.style.cursor = 'default';
                        updateStatusMessage();
                    } else {
                        signInBtn.disabled = false;
                        signInBtn.textContent = originalText;
                    }
                })
                .catch(() => {
                    showPopup('Network error – please try again', 'error');
                    signInBtn.disabled = false;
                    signInBtn.textContent = originalText;
                })
                .finally(() => isProcessing = false);
        }

        function signOut() {
            if (isProcessing) return;
            if (!confirm("Are you sure you want to sign out now?")) return;

            isProcessing = true;
            const originalText = signOutBtn.textContent;
            signOutBtn.disabled = true;
            signOutBtn.textContent = 'Signing out...';

            fetch('../backend/record_attendance.php?action=sign_out')
                .then(res => res.json())
                .then(data => {
                    showPopup(data.message, data.status);
                    if (data.status === 'success') {
                        signOutBtn.textContent = 'Signed Out ✓';
                        signOutBtn.style.background = 'linear-gradient(45deg, #6c757d, #5a6268)';
                        signOutBtn.style.cursor = 'default';
                        updateStatusMessage();
                    } else {
                        signOutBtn.disabled = false;
                        signOutBtn.textContent = originalText;
                    }
                })
                .catch(() => {
                    showPopup('Failed to sign out. Try again.', 'error');
                    signOutBtn.disabled = false;
                    signOutBtn.textContent = originalText;
                })
                .finally(() => isProcessing = false);
        }

        function updateButtonStatesAndStatus() {
            fetch('../backend/get_current_time_status.php')
                .then(r => r.json())
                .then(data => {
                
                    if (data.can_sign_in) {
                        signInBtn.disabled = false;
                        signInBtn.textContent = 'Sign In';
                        signInBtn.style.background = '';
                        signInBtn.style.cursor = 'pointer';
                    } else {
                        signInBtn.disabled = true;
                        signInBtn.textContent = 'Sign-in closed (07:00–09:00)';
                        signInBtn.style.background = 'linear-gradient(45deg, #6c757d, #5a6268)';
                        signInBtn.style.cursor = 'not-allowed';
                    }
                    if (data.can_sign_out) {
                        signOutBtn.disabled = false;
                        signOutBtn.textContent = 'Sign Out';
                        signOutBtn.style.background = '';
                        signOutBtn.style.cursor = 'pointer';
                    } else {
                        signOutBtn.disabled = true;
                        signOutBtn.textContent = 'Sign-out closed (15:00–18:00)';
                        signOutBtn.style.background = 'linear-gradient(45deg, #6c757d, #5a6268)';
                        signOutBtn.style.cursor = 'not-allowed';
                    }

               
                    if (!data.has_signed_in_today) {
                        statusMsg.textContent = "You have not signed in today → Marked as Absent";
                        statusMsg.style.color = '#ff4d4d';
                    } else if (data.has_signed_in_today && !data.has_signed_out_today) {
                        if (data.can_sign_out) {
                            statusMsg.textContent = "You have signed in → Please sign out before 18:00";
                            statusMsg.style.color = '#ffaa00';
                        } else {
                            statusMsg.textContent = "Signed in, but not signed out → Status: Not signed out";
                            statusMsg.style.color = '#ffaa00';
                        }
                    } else if (data.has_signed_out_today) {
                        statusMsg.textContent = "You have successfully signed out today ✓";
                        statusMsg.style.color = '#28a745';
                    }

                    timeStatus.textContent = `Current time: ${data.current_time} (EAT)`;
                    timeStatus.style.color = '#aafaff';
                })
                .catch(() => {
                    statusMsg.textContent = "Unable to check status – please refresh the page";
                    statusMsg.style.color = '#ff4d4d';
                });
        }

        function loadUserInfo() {
            fetch('../backend/get_user_info.php')
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        window.location.href = 'staff_login.php';
                    } else {
                        document.getElementById('staffName').textContent = data.full_name;
                    }
                })
                .catch(() => console.error('Failed to load user info'));
        }

        window.addEventListener('load', () => {
            loadUserInfo();
            updateButtonStatesAndStatus();
            setInterval(updateButtonStatesAndStatus, 60000);
        });
    </script>
</body>

</html>


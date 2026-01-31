<!DOCTYPE html>
<html lang="en">
<?php
session_start();
require_once '../config/constants.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != ROLE_ADMIN) {
    header("Location: index.html");
    exit();
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SAS 1.0</title>
    <link rel="stylesheet" href="style1.css">
    <style>
        /* Enhanced CSS for the attendance table */
        .attendance-table-container {
            margin: 20px 0;
            overflow-x: auto; /* Makes table responsive on smaller screens */
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
            background-color: #949292; /* White background for contrast */
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            /* Removed table-layout: fixed; to allow auto-sizing based on content */
        }

        .attendance-table th,
        .attendance-table td {
            padding: 12px 15px; /* Improved padding for better readability */
            text-align: left; /* Left-align text for natural reading */
            border-bottom: 1px solid #ddd; /* Light borders for separation */
            vertical-align: middle; /* Vertical alignment for content */
            overflow-wrap: break-word; /* Break long words to prevent overlap */
             /* Ensure long strings like emails break if needed */
        }

        .attendance-table th {
            background-color: #f4f4f4; /* Light gray header background */
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
            color: #000; /* Black color for header text */
            position: sticky; /* Sticky headers for scrolling */
            top: 0;
            z-index: 1;
        }

        .attendance-table td {
            color: #000; /* Black color for table details */
        }

        .attendance-table tbody tr:nth-child(even) {
            background-color: #f9f9f9; /* Alternating row colors for better visibility */
        }

        .attendance-table tbody tr:hover {
            background-color: #f1f1f1; /* Hover effect for interactivity */
            transition: background-color 0.3s ease;
        }

        /* Status badge styling */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px; /* Pill-shaped badges */
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            min-width: 80px; /* Ensures consistent width */
            text-align: center;
        }

        .status-present {
            background-color: #28a745; /* Green for present */
            color: white;
        }

        .status-absent {
            background-color: #dc3545; /* Red for absent */
            color: white;
        }

        .status-incomplete {
            background-color: #ffc107; /* Yellow for incomplete */
            color: #333;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .attendance-table th,
            .attendance-table td {
                padding: 10px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <h1>Welcome, Admin</h1>

        <div class="dashboard-cards">
            <div class="holo-card">
                <h3>Total Staffs </h3>
                <p id="total-staff">Loading...</p>
            </div>
            <div class="holo-card">
                <h3>Today’s Attendance</h3>
                <p id="today-attendance">Loading...</p>
            </div>
            <div class="holo-card">
                <h3>Staff Management</h3>
                <p><a href="staff_management.php" class="holo-btn">Go</a></p>
            </div>
        </div>

        <!-- Date Selector -->
        <div class="date">
            <label for="attendanceDate">Select Date:</label>
            <input type="date" id="attendanceDate" value="<?php echo date('Y-m-d'); ?>">
        </div>

        <!-- Attendance Table -->
        <div class="attendance-table-container">
            <h2>Attendance Records</h2>
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>Staff Name</th>
                        <th>Email</th>
                        <th>Sign In Time</th>
                        <th>Sign Out Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="attendanceTableBody">
                    <tr>
                        <td colspan="7" style="text-align: center;">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <button onclick="logout()" class="holo-btn logout-btn">Logout</button>
    </div>

    <script>
        function logout() {
            window.location.href = '../backend/logout.php';
        }

        // Load summary data
        function loadDashboard() {
            fetch('../backend/get_dashboard_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        window.location.href = 'index.php';
                    } else {
                        document.getElementById('total-staff').textContent = data.totalStaff;
                        document.getElementById('today-attendance').textContent = data.todayAttendance;
                    }
                })
                .catch(err => console.error(err));
        }

        // Load detailed attendance records
        function loadAttendanceTable() {
            const date = document.getElementById('attendanceDate').value;
            
            fetch(`../backend/get_attendance_details.php?date=${date}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('attendanceTableBody');
                    
                    if (data.error) {
                        tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: red;">${data.error}</td></tr>`;
                        return;
                    }
                    
                    if (data.data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="5" style="text-align: center;">No staff records found.</td></tr>`;
                        return;
                    }
                    
                    let html = '';
                    data.data.forEach(record => {
                        const statusClass = record.status === 'Absent' ? 'status-absent' : 
                                          record.status === 'Present' ? 'status-present' : 
                                          'status-incomplete';
                        
                        html += `
                            <tr>
                                <td>${escapeHtml(record.full_name)}</td>
                                <td>${escapeHtml(record.email)}</td>
                                <td>${escapeHtml(record.sign_in_time)}</td>
                                <td>${escapeHtml(record.sign_out_time)}</td>
                                <td><span class="status-badge ${statusClass}">${escapeHtml(record.status)}</span></td>
                            </tr>
                        `;
                    });
                    
                    tbody.innerHTML = html;
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('attendanceTableBody').innerHTML = 
                        `<tr><td colspan="5" style="text-align: center; color: red;">Error loading attendance data.</td></tr>`;
                });
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // Load on page load
        window.onload = () => {
            loadDashboard();
            loadAttendanceTable();
        };

        // Reload attendance table when date changes
        document.getElementById('attendanceDate').addEventListener('change', loadAttendanceTable);
    </script>
</body>

</html>
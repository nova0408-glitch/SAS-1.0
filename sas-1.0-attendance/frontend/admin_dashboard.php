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
        .attendance-table-container {
            margin: 20px 0;
            overflow-x: auto; 
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); 
            background-color: #949292;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
 
        }

        .attendance-table th,
        .attendance-table td {
            padding: 12px 15px; 
            text-align: left; 
            border-bottom: 1px solid #ddd;
            vertical-align: middle; 
            overflow-wrap: break-word; 
            
        }

        .attendance-table th {
            background-color: #f4f4f4; 
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
            color: #000; 
            position: sticky; 
            top: 0;
            z-index: 1;
        }

        .attendance-table td {
            color: #000; 
        }

        .attendance-table tbody tr:nth-child(even) {
            background-color: #f9f9f9; 
        }

        .attendance-table tbody tr:hover {
            background-color: #f1f1f1; 
            transition: background-color 0.3s ease;
        }

        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px; 
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            min-width: 80px; 
            text-align: center;
        }

        .status-present {
            background-color: #28a745; 
            color: white;
        }

        .status-absent {
            background-color: #dc3545; 
            color: white;
        }

        .status-incomplete {
            background-color: #ffc107; 
            color: #333;
        }

       
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

       
        <div class="date">
            <label for="attendanceDate">Select Date:</label>
            <input type="date" id="attendanceDate" value="<?php echo date('Y-m-d'); ?>">
        </div>

       
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

       
        window.onload = () => {
            loadDashboard();
            loadAttendanceTable();
        };

       
        document.getElementById('attendanceDate').addEventListener('change', loadAttendanceTable);
    </script>
</body>

</html>

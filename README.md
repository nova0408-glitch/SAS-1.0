# SAS-1.0
An attendance tracking system for staffs in an organization (school, hospital... etc ) that tracks daily attendance of each staff and their punctuality for effective work output.

## Features
- Admin dashboard to view total staff and today's attendance.
- Staff sign-in/out functionality.
- Secure authentication with password hashing.
- CSRF protection.
- Staff management for admins.

## Setup
1. Ensure PHP 7.4+, MySQL, and a web server (e.g., Apache) are installed.
2. Clone the repo and navigate to `sas-1.0-attendance/`.
3. Configure database in `.env` file.
4. Import the database schema (create `users` and `attendance` tables).
5. Access `frontend/index.php` for admin login or `frontend/staff_login.php` for staff.

## Database Schema
- `users`: user_id, email, full_name, password_hash, role_id, is_active
- `attendance`: attendance_id, user_id, date, sign_in_time, sign_out_time

## Security
- Uses prepared statements, CSRF tokens, and secure headers.
- Passwords hashed with bcrypt.

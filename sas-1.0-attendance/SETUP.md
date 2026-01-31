# Database Schema Setup for XAMPP

## Option 1: Using phpMyAdmin (Easiest)

1. Start XAMPP and ensure MySQL is running.
2. Open **phpMyAdmin** in your browser: `http://localhost/phpmyadmin`
3. Go to the **SQL** tab at the top.
4. Copy and paste the contents of `schema.sql`.
5. Click **Go** to execute.

## Option 2: Using Command Line

1. Open Command Prompt/Terminal.
2. Navigate to MySQL bin directory (e.g., `C:\xampp\mysql\bin` on Windows).
3. Run:
   ```bash
   mysql -u root < path\to\schema.sql
   ```
   Or use:
   ```bash
   mysql -u root
   ```
   Then paste the SQL commands.

## Option 3: Using HeidiSQL (XAMPP)

1. Open **HeidiSQL** from the XAMPP control panel.
2. Click **File → Load SQL file** and select `schema.sql`.
3. Click **Execute** or press `F9`.

## What Gets Created

- **Database**: `sas_db`
- **Tables**: 
  - `users` (user accounts with roles)
  - `attendance` (sign-in/sign-out records)
- **Sample Data**:
  - Admin account: `admin@example.com` / `AdminPassword123`
  - 2 Staff accounts: `staff1@example.com` / `StaffPassword123` and `staff2@example.com` / `StaffPassword123`
- **Stored Procedure**: `get_daily_attendance()` for reports

## Testing Default Credentials

After schema import, test login:
- **Admin Login**: Go to `http://localhost/sas-1.0-attendance/frontend/index.php`
  - Email: `admin@example.com`
  - Password: `AdminPassword123`
- **Staff Login**: Go to `http://localhost/sas-1.0-attendance/frontend/staff_login.php`
  - Email: `staff1@example.com`
  - Password: `StaffPassword123`

## Notes

- All passwords use bcrypt hashing (PASSWORD_DEFAULT in PHP).
- To change default password hashes, run:
  ```php
  echo password_hash("YourPassword", PASSWORD_DEFAULT);
  ```
  Then update the `INSERT` statements with new hashes.
- Indices are added on frequently queried columns for performance.

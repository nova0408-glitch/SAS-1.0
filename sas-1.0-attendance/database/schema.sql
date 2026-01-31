-- SAS 1.0 Database Schema
-- Import this file in phpMyAdmin or run: mysql -u root sas_db < schema.sql

-- Create Database
CREATE DATABASE IF NOT EXISTS `sas_db`;
USE `sas_db`;

-- Create Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role_id` INT NOT NULL COMMENT '1 = Admin, 2 = Staff',
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Attendance Table
CREATE TABLE IF NOT EXISTS `attendance` (
  `attendance_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `sign_in_time` TIME,
  `sign_out_time` TIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_date` (`date`),
  UNIQUE KEY `unique_user_date` (`user_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Sample Admin User
-- Email: admin@example.com
-- Password: AdminPassword123 (hashed with bcrypt)
INSERT INTO `users` (`email`, `full_name`, `password_hash`, `role_id`, `is_active`) VALUES
('admin@example.com', 'Admin User', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/nas', 1, TRUE);

-- Insert Sample Staff Users
-- Email: staff1@example.com
-- Password: StaffPassword123 (hashed with bcrypt)
INSERT INTO `users` (`email`, `full_name`, `password_hash`, `role_id`, `is_active`) VALUES
('staff1@example.com', 'John Doe', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/nas', 2, TRUE),
('staff2@example.com', 'Jane Smith', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/nas', 2, TRUE);

-- Create a stored procedure for daily attendance report
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `get_daily_attendance` (IN attendance_date DATE)
BEGIN
  SELECT 
    u.user_id,
    u.full_name,
    u.email,
    a.sign_in_time,
    a.sign_out_time,
    CASE 
      WHEN a.sign_in_time IS NULL THEN 'Absent'
      WHEN a.sign_out_time IS NULL THEN 'Present (No Sign Out)'
      ELSE 'Present'
    END AS status
  FROM `users` u
  LEFT JOIN `attendance` a ON u.user_id = a.user_id AND a.date = attendance_date
  WHERE u.role_id = 2 AND u.is_active = TRUE
  ORDER BY u.full_name;
END$$

DELIMITER ;

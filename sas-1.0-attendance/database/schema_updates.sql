-- SAS 1.0 Database Schema Updates
-- Run this to add new tables for audit logging, rate limiting, and soft deletes

USE `sas_db`;

-- Add soft delete to users table
ALTER TABLE `users` ADD COLUMN `is_deleted` BOOLEAN DEFAULT FALSE AFTER `is_active`;
ALTER TABLE `users` ADD INDEX `idx_is_deleted` (`is_deleted`);

-- Create audit logs table
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `log_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT,
  `action` VARCHAR(50) NOT NULL,
  `details` TEXT,
  `status` VARCHAR(20) NOT NULL COMMENT 'success, failed',
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(255),
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create rate limits table
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `limit_id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `status` VARCHAR(20),
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ip_action` (`ip_address`, `action`),
  INDEX `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` VARCHAR(50) NOT NULL COMMENT 'late_signin, no_signin, system_alert',
  `message` TEXT NOT NULL,
  `is_read` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update stored procedure to exclude soft-deleted users
DROP PROCEDURE IF EXISTS `get_daily_attendance`;

DELIMITER $$

CREATE PROCEDURE `get_daily_attendance` (IN attendance_date DATE)
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
  WHERE u.role_id = 2 AND u.is_active = TRUE AND u.is_deleted = FALSE
  ORDER BY u.full_name;
END$$

DELIMITER ;

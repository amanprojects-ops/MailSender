-- Database Schema for Secure Mail Sender

CREATE DATABASE IF NOT EXISTS `mail_sender_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mail_sender_db`;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- SMTP Settings Table
CREATE TABLE IF NOT EXISTS `smtp_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `smtp_host` VARCHAR(255) NOT NULL,
    `smtp_user` VARCHAR(255) NOT NULL,
    `smtp_pass` TEXT NOT NULL, -- Encrypted
    `smtp_port` INT NOT NULL,
    `encryption` ENUM('tls', 'ssl', 'none') DEFAULT 'tls',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Templates Table
CREATE TABLE IF NOT EXISTS `templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `template_name` VARCHAR(100) NOT NULL,
    `language` VARCHAR(20) DEFAULT 'English',
    `template_type` ENUM('plain', 'html') DEFAULT 'plain',
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Email Logs Table
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `recipient` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `status` ENUM('success', 'failed') NOT NULL,
    `error_message` TEXT,
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- create default user
-- Username: admin
-- Password: admin123
INSERT INTO `users` (`username`, `password_hash`) VALUES ('admin', '$2y$10$vfwCFk9i5UktMs6q47/UqOSjTNGPZ7Que8Jadhh0a/RgUSMALLNVa');


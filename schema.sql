-- Database Initialization Schema for ParkirTertib
-- Sesuai DATA_MODEL.md. Jalankan dengan: mysql -u root < schema.sql
-- (pastikan MySQL Laragon sudah Running)
CREATE DATABASE IF NOT EXISTS `parkir_tertib` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `parkir_tertib`;

-- Drop existing tables
DROP TABLE IF EXISTS `report_log`;
DROP TABLE IF EXISTS `report`;
DROP TABLE IF EXISTS `user`;

-- 1. Table: user
CREATE TABLE `user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table: report
CREATE TABLE `report` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `license_plate` VARCHAR(20) NOT NULL,
  `location` VARCHAR(100) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Menunggu', 'Diproses', 'Ditindaklanjuti', 'Selesai') NOT NULL DEFAULT 'Menunggu',
  `admin_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_report_user` (`user_id`),
  KEY `idx_report_status` (`status`),
  KEY `idx_report_license_plate` (`license_plate`),
  KEY `idx_report_location` (`location`),
  CONSTRAINT `fk_report_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table: report_log
CREATE TABLE `report_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_id` INT UNSIGNED NOT NULL,
  `admin_id` INT UNSIGNED NOT NULL,
  `previous_status` ENUM('Menunggu', 'Diproses', 'Ditindaklanjuti', 'Selesai') NOT NULL,
  `new_status` ENUM('Menunggu', 'Diproses', 'Ditindaklanjuti', 'Selesai') NOT NULL,
  `note` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_report` (`report_id`),
  KEY `idx_log_admin` (`admin_id`),
  CONSTRAINT `fk_log_report` FOREIGN KEY (`report_id`) REFERENCES `report` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_log_admin` FOREIGN KEY (`admin_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Seed Data
-- Kedua password adalah hash bcrypt valid dari 'Password123!'
-- (hash placeholder di DATA_MODEL.md tidak valid, diganti hasil password_hash())
INSERT INTO `user` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Satpam Utama', 'satpam@unimed.ac.id', '$2y$10$kQVIVHfz4qTMqiVlexVHyuE.HVIQZ7NEn9AuEFUyoj3b3tz3mfq1m', 'admin'),
(2, 'Ahmad Student', 'ahmad@mhs.unimed.ac.id', '$2y$10$UyzysnAQLgZX5HgmD3wCCuoA49PB4qjaAe352ET2NiwYHuCtCSkpW', 'user');

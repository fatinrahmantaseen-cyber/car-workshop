-- Database Schema for Car Workshop Appointment Management System
-- Database name: car_workshop_db

CREATE DATABASE IF NOT EXISTS `car_workshop_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `car_workshop_db`;

-- --------------------------------------------------------
-- Table structure for: mechanics
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mechanics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for: appointments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_name` VARCHAR(100) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `car_license` VARCHAR(50) NOT NULL,
    `car_engine` VARCHAR(50) NOT NULL,
    `appointment_date` DATE NOT NULL,
    `mechanic_id` INT NOT NULL,
    `status` ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_appointments_mechanic`
        FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    INDEX `idx_appointment_date` (`appointment_date`),
    INDEX `idx_mechanic_date_status` (`mechanic_id`, `appointment_date`, `status`),
    INDEX `idx_client_phone_date` (`phone`, `appointment_date`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for: admins
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data
-- --------------------------------------------------------

-- Insert five sample senior mechanics
INSERT INTO `mechanics` (`name`, `phone`, `is_active`) VALUES
('Rahim', '01711-234567', 1),
('Karim', '01819-345678', 1),
('Jashim', '01912-456789', 1),
('Masud', '01613-567890', 1),
('Helal', '01514-678901', 1);

-- Graduation Management System - SQL Schema
-- Database: checkmate
-- Run this in XAMPP phpMyAdmin to create the graduation tables

-- Ensure students table has graduated status
ALTER TABLE students MODIFY COLUMN status ENUM('regular','transfer','non_ibm','graduated') DEFAULT 'regular';

-- Graduation Records Table
-- Stores information about students who have graduated
CREATE TABLE IF NOT EXISTS graduation_records (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    major_id INT NOT NULL,
    academic_year VARCHAR(32) NOT NULL DEFAULT '',
    year_level VARCHAR(64) NOT NULL DEFAULT '',
    semester VARCHAR(64) NOT NULL DEFAULT '',
    gwa DECIMAL(10,4) DEFAULT NULL,
    graduation_date DATE DEFAULT NULL,
    total_subjects INT UNSIGNED DEFAULT 0,
    pdf_path VARCHAR(768) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_grad_student (student_id),
    INDEX idx_academic_year (academic_year),
    INDEX idx_graduation_date (graduation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Add pdf_path column if table already exists but missing column
-- This will be ignored if column already exists or table doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = 'checkmate' 
                   AND TABLE_NAME = 'graduation_records' 
                   AND COLUMN_NAME = 'pdf_path');
-- Note: The column is included in CREATE TABLE above, this is just a fallback
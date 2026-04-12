-- Faculty Evaluation System Database
-- Optimized for XAMPP/MySQL with proper character set and indexes

-- Create database with proper character set
CREATE DATABASE IF NOT EXISTS checkmate 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
USE checkmate;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist (for fresh install)
DROP TABLE IF EXISTS admin_promotions;
DROP TABLE IF EXISTS pending_instructors;
DROP TABLE IF EXISTS evaluation_feedback;
DROP TABLE IF EXISTS student_attendance;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS instructor_courses;
DROP TABLE IF EXISTS evaluations;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS instructors;
DROP TABLE IF EXISTS program_heads;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS majors;

SET FOREIGN_KEY_CHECKS = 1;

-- Admins Table
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'admin',
    is_demo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Program Heads Table (login as Program Head)
CREATE TABLE IF NOT EXISTS program_heads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    position VARCHAR(100) DEFAULT 'Program Head',
    office_location VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Instructors Table
CREATE TABLE IF NOT EXISTS instructors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    suffix VARCHAR(20),
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    position VARCHAR(100) DEFAULT 'Instructor',
    phone VARCHAR(50),
    birthday DATE,
    avatar VARCHAR(255) DEFAULT NULL,
    avatar_gradient_from VARCHAR(20) DEFAULT '#667eea',
    avatar_gradient_to VARCHAR(20) DEFAULT '#764ba2',
    status ENUM('on duty', 'on leave', 'on travel') DEFAULT 'on duty',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Majors Table (MUST be created before students table)
CREATE TABLE IF NOT EXISTS majors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    major_name VARCHAR(100) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    icon_class VARCHAR(100) DEFAULT 'fas fa-building',
    gradient_from VARCHAR(20) DEFAULT '#d4a843',
    gradient_to VARCHAR(20) DEFAULT '#e8c768',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_major_name (major_name),
    INDEX idx_is_active (is_active),
    UNIQUE KEY uk_major_name (major_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subjects Table
CREATE TABLE IF NOT EXISTS subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    description TEXT,
    units DECIMAL(3,1) DEFAULT 3.0,
    lecture_hours INT DEFAULT 2,
    lab_hours INT DEFAULT 0,
    credit_type VARCHAR(20) DEFAULT 'lec' COMMENT 'lec, lec-lab, practical, project',
    icon_class VARCHAR(100) DEFAULT 'fas fa-book',
    color VARCHAR(20) DEFAULT '#3b82f6',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_subject_code (subject_code),
    INDEX idx_is_active (is_active),
    UNIQUE KEY uk_subject_code (subject_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Major Subjects Table (links majors to subjects with prerequisite info)
CREATE TABLE IF NOT EXISTS major_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    major_id INT NOT NULL,
    subject_id INT NOT NULL,
    year_level VARCHAR(20) DEFAULT '1st Year',
    semester VARCHAR(20) DEFAULT '1st Semester',
    is_required BOOLEAN DEFAULT TRUE,
    is_prerequisite BOOLEAN DEFAULT FALSE,
    prerequisite_for INT DEFAULT NULL COMMENT 'subject_id that this is prerequisite for',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_major_id (major_id),
    INDEX idx_subject_id (subject_id),
    INDEX idx_prerequisite_for (prerequisite_for),
    FOREIGN KEY (major_id) REFERENCES majors(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uk_major_subject (major_id, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students Table
CREATE TABLE IF NOT EXISTS students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    suffix VARCHAR(20),
    email VARCHAR(255) NOT NULL UNIQUE,
    major_id INT,
    year_level VARCHAR(50),
    avatar_initials VARCHAR(5),
    avatar_gradient_from VARCHAR(20) DEFAULT '#3b82f6',
    avatar_gradient_to VARCHAR(20) DEFAULT '#60a5fa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_major_id (major_id),
    INDEX idx_year_level (year_level),
     FOREIGN KEY (major_id) REFERENCES majors(id) ON DELETE SET NULL ON UPDATE CASCADE
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

 -- Mentees Table (student assignments to instructors)
  CREATE TABLE IF NOT EXISTS mentees (
      id INT PRIMARY KEY AUTO_INCREMENT,
      student_id INT NOT NULL,
      first_name VARCHAR(100) NOT NULL,
      last_name VARCHAR(100) NOT NULL,
      email VARCHAR(255) NOT NULL,
      mentor_id INT NOT NULL,
      assigned_by_id INT,
      assigned_by_name VARCHAR(255),
      assignment_notes TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     INDEX idx_mentor_id (mentor_id),
     INDEX idx_student_id (student_id),
     INDEX idx_email (email),
     FOREIGN KEY (mentor_id) REFERENCES instructors(id) ON DELETE CASCADE ON UPDATE CASCADE,
     FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add new columns to mentees table for tracking assignments
ALTER TABLE mentees ADD COLUMN assigned_by_id INT;
ALTER TABLE mentees ADD COLUMN assigned_by_name VARCHAR(255);
ALTER TABLE mentees ADD COLUMN assignment_notes TEXT;

-- Tasks Table (instructor assignments to mentees)
 CREATE TABLE IF NOT EXISTS tasks (
     id INT PRIMARY KEY AUTO_INCREMENT,
     title VARCHAR(200) NOT NULL,
     description TEXT,
     instructor_id INT NOT NULL,
     priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
     due_date DATE,
     status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     INDEX idx_instructor_id (instructor_id),
     INDEX idx_status (status),
     INDEX idx_due_date (due_date),
     FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE ON UPDATE CASCADE
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
 -- Task Assignments Table (links tasks to mentees)
 CREATE TABLE IF NOT EXISTS task_assignments (
     id INT PRIMARY KEY AUTO_INCREMENT,
     task_id INT NOT NULL,
     mentee_id INT NOT NULL,
     assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
     completion_date DATE NULL,
     notes TEXT,
     INDEX idx_task_id (task_id),
     INDEX idx_mentee_id (mentee_id),
     INDEX idx_status (status),
     FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE ON UPDATE CASCADE,
     FOREIGN KEY (mentee_id) REFERENCES mentees(id) ON DELETE CASCADE ON UPDATE CASCADE,
     UNIQUE KEY uk_task_mentee (task_id, mentee_id)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

 -- Reports Table
CREATE TABLE IF NOT EXISTS reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_name VARCHAR(200) NOT NULL,
    report_description VARCHAR(500),
    report_type ENUM('pdf', 'excel') DEFAULT 'pdf',
    icon_class VARCHAR(100) DEFAULT 'fas fa-file-pdf',
    download_count INT DEFAULT 0,
    generated_by VARCHAR(50) DEFAULT 'instructor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_type (report_type),
    INDEX idx_generated_by (generated_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pending Instructor Registrations (self-registration)
CREATE TABLE IF NOT EXISTS pending_instructors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    suffix VARCHAR(20),
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    instructor_id INT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_instructor_id (instructor_id),
    INDEX idx_status (status),
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Promotions: tracks which instructors are promoted (UI Role = Program Head)
CREATE TABLE IF NOT EXISTS admin_promotions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_id INT NOT NULL,
    promoted_to VARCHAR(50) NOT NULL COMMENT 'program_head or admin',
    promoted_by INT NOT NULL COMMENT 'admin user_id',
    promotion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_instructor_id (instructor_id),
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE DATA INSERTS
-- =====================================================

-- Sample Users
-- Note: All passwords are 'password123' (hashed with bcrypt)
-- is_demo = 1 means it's a demo account that requires setup on first login
INSERT INTO admins (first_name, last_name, email, password, role, is_demo) VALUES
('System', 'Administrator', 'admin@cjcm.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- Majors
INSERT INTO majors (major_name, display_name, description, icon_class, gradient_from, gradient_to, sort_order, is_active) VALUES
('Operational Management', 'Operational Management', 'Focuses on business operations, processes, and management strategies to optimize organizational efficiency.', 'fas fa-cogs', '#d4a843', '#e8c768', 1, TRUE),
('Financial Management', 'Financial Management', 'Specializes in financial analysis, accounting, investment decisions, and corporate finance strategies.', 'fas fa-dollar-sign', '#3b82f6', '#60a5fa', 2, TRUE),
('Marketing Management', 'Marketing Management', 'Covers marketing principles, consumer behavior, market research, and strategic marketing planning.', 'fas fa-chart-line', '#ec4899', '#f472b6', 3, TRUE);

-- Subjects (Sample subjects for Operational Management major)
INSERT INTO subjects (subject_code, subject_name, description, units, icon_class, color, is_active) VALUES
('OPM 101', 'Introduction to Operations Management', 'Fundamental concepts of operations management including process design, capacity planning, and inventory management.', 3.0, 'fas fa-cogs', '#d4a843', TRUE),
('OPM 201', 'Production and Operations Management', 'Advanced topics in production planning, scheduling, and quality control.', 3.0, 'fas fa-industry', '#e8c768', TRUE),
('OPM 301', 'Supply Chain Management', 'End-to-end supply chain coordination, logistics, and procurement strategies.', 3.0, 'fas fa-truck', '#3b82f6', TRUE),
('OPM 302', 'Quality Management', 'Total quality management, Six Sigma methodologies, and continuous improvement.', 3.0, 'fas fa-check-double', '#10b981', TRUE),
('OPM 401', 'Strategic Operations', 'Strategic planning for operations, lean management, and business process reengineering.', 3.0, 'fas fa-chess', '#ec4899', TRUE),
('MATH 101', 'Business Mathematics', 'Mathematical techniques for business decision-making including calculus and statistics.', 3.0, 'fas fa-calculator', '#8b5cf6', TRUE),
('STAT 201', 'Business Statistics', 'Statistical analysis methods for business research and decision making.', 3.0, 'fas fa-chart-bar', '#6366f1', TRUE),
('MGMT 101', 'Principles of Management', 'Foundational management principles, organizational behavior, and leadership.', 3.0, 'fas fa-users', '#f59e0b', TRUE);

-- Major Subjects (Link subjects to Operational Management major with prerequisites)
INSERT INTO major_subjects (major_id, subject_id, year_level, semester, is_required, is_prerequisite) VALUES
(1, 1, '1st Year', '1st Semester', TRUE, FALSE),
(1, 6, '1st Year', '1st Semester', TRUE, FALSE),
(1, 7, '1st Year', '2nd Semester', TRUE, FALSE),
(1, 8, '1st Year', '2nd Semester', TRUE, FALSE),
(1, 2, '2nd Year', '1st Semester', TRUE, TRUE),
(1, 3, '2nd Year', '2nd Semester', TRUE, FALSE),
(1, 4, '3rd Year', '1st Semester', TRUE, FALSE),
(1, 5, '4th Year', '1st Semester', TRUE, FALSE);

-- Program Heads
INSERT INTO program_heads (first_name, last_name, email, password, position, office_location) VALUES
('John', 'Head', 'head@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Program Head', 'Room 201, Building A'),
('Sarah', 'Manager', 'manager@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Program Head', 'Room 202, Building A'),
('Robert', 'Marketing', 'marketing@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Program Head', 'Room 203, Building A');

-- Instructors
INSERT INTO instructors (first_name, middle_name, last_name, suffix, email, password, position, phone, birthday, avatar_gradient_from, avatar_gradient_to, status) VALUES
('Jane', NULL, 'Teacher', NULL, 'teacher@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Instructor', '+1 234 567 8900', '1985-06-15', '#667eea', '#764ba2', 'on duty'),
('Michael', 'B.', 'Brown', NULL, 'michael.brown@cjcm.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Instructor', '+1 234 567 8910', '1978-11-22', '#11998e', '#38ef7d', 'on duty'),
('Sarah', 'J.', 'Johnson', NULL, 'sarah.johnson@cjcm.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Instructor', '+1 234 567 8920', '1990-02-14', '#f093fb', '#f5576c', 'on duty'),
('David', NULL, 'Lee', NULL, 'david.lee@cjcm.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Instructor', '+1 234 567 8930', '1982-09-08', '#4facfe', '#00f2fe', 'on duty'),
('Emily', 'R.', 'Davis', NULL, 'emily.davis@cjcm.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Instructor', '+1 234 567 8940', '1987-07-30', '#fa709a', '#fee140', 'on duty'),
('James', 'K.', 'Wilson', NULL, 'james.wilson@cjcm.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Instructor', '+1 234 567 8950', '1984-12-01', '#a18cd1', '#fbc2eb', 'on duty');

-- Students
INSERT INTO students (first_name, middle_name, last_name, suffix, email, major_id, year_level, student_id, avatar_initials, avatar_gradient_from, avatar_gradient_to) VALUES
('John', 'M.', 'Doe', NULL, 'john.doe@student.edu', 1, '3rd Year', 'STU-001', 'JD', '#3b82f6', '#60a5fa'),
('Jane', 'A.', 'Wilson', NULL, 'jane.wilson@student.edu', 1, '2nd Year', 'STU-002', 'JW', '#10b981', '#34d399'),
('Mike', 'R.', 'Johnson', 'Jr.', 'mike.j@student.edu', 3, '3rd Year', 'STU-003', 'MJ', '#8b5cf6', '#a78bfa'),
('Sarah', 'L.', 'Williams', NULL, 'sarah.w@student.edu', 1, '4th Year', 'STU-004', 'SW', '#f43f5e', '#fb7185'),
('Tom', 'B.', 'Brown', NULL, 'tom.b@student.edu', 1, '2nd Year', 'STU-005', 'TB', '#f59e0b', '#fbbf24');

-- Reports
INSERT INTO reports (report_name, report_description, report_type, icon_class, download_count, generated_by) VALUES
('Evaluation Summary Report', 'Spring 2026 Semester', 'pdf', 'fas fa-file-pdf', 8, 'instructor'),
('Course Performance Report', 'All Courses - Academic Year 2025-2026', 'pdf', 'fas fa-file-pdf', 5, 'instructor'),
('Student Grades Export', 'Current Semester', 'excel', 'fas fa-file-excel', 6, 'instructor'),
('Feedback Analysis', 'All Courses - Comprehensive', 'pdf', 'fas fa-file-pdf', 4, 'instructor'),
('Department Performance Report', 'All Departments - Spring 2026', 'pdf', 'fas fa-file-pdf', 10, 'program_head'),
('Instructor Ranking Report', 'Top Performers - Academic Year 2025-2026', 'pdf', 'fas fa-file-pdf', 7, 'program_head'),
('Course Completion Report', 'Evaluation Completion Rates', 'excel', 'fas fa-file-excel', 3, 'program_head');

-- Pending Instructor Registrations (self-registration)
INSERT INTO pending_instructors (first_name, middle_name, last_name, suffix, email, password, instructor_id, status) VALUES
('Alice', NULL, 'Smith', NULL, 'alice.smith@pending.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'pending'),
('Bob', NULL, 'Johnson', NULL, 'bob.johnson@pending.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'pending'),
('Carol', 'M.', 'Williams', NULL, 'carol.williams@pending.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'approved');

-- Add system settings columns to admins table
ALTER TABLE admins ADD COLUMN system_name VARCHAR(255) NULL;
ALTER TABLE admins ADD COLUMN system_tagline TEXT NULL;

-- Add student_id column to students table
ALTER TABLE students ADD COLUMN student_id VARCHAR(50) NULL UNIQUE;

-- Add new columns to subjects table (if not exists)
ALTER TABLE subjects ADD COLUMN lecture_hours INT DEFAULT 2;
ALTER TABLE subjects ADD COLUMN lab_hours INT DEFAULT 0;
ALTER TABLE subjects ADD COLUMN credit_type VARCHAR(20) DEFAULT 'lec' COMMENT 'lec, lec-lab, practical, project';

-- Student Subject Grades Table (grades per subject per semester)
CREATE TABLE IF NOT EXISTS student_subject_grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    major_id INT NOT NULL,
    subject_id INT NOT NULL,
    year_level VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    grade VARCHAR(10),
    remarks TEXT,
    graded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_major (major_id),
    INDEX idx_subject (subject_id),
    INDEX idx_year_semester (year_level, semester),
    UNIQUE KEY uk_student_subject_sem (student_id, subject_id, year_level, semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

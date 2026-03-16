-- Faculty Evaluation System Database

-- Create database
CREATE DATABASE IF NOT EXISTS checkmate;
USE checkmate;

-- Drop existing tables if they exist (for fresh install)
DROP TABLE IF EXISTS evaluation_feedback;
DROP TABLE IF EXISTS student_attendance;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS instructor_courses;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS evaluations;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS instructors;
DROP TABLE IF EXISTS program_heads;
DROP TABLE IF EXISTS admins;

-- Admins Table
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Departments Table
CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_name VARCHAR(200) NOT NULL,
    icon_class VARCHAR(100) DEFAULT 'fas fa-building',
    gradient_from VARCHAR(20) DEFAULT '#d4a843',
    gradient_to VARCHAR(20) DEFAULT '#e8c768',
    instructor_count INT DEFAULT 0,
    course_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Program Heads Table (login as Program Head).
-- When an instructor is promoted via admin, a row is inserted here with the new password
-- so they can sign in as Program Head. Remove promotion deletes this row.
CREATE TABLE IF NOT EXISTS program_heads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    position VARCHAR(100) DEFAULT 'Program Head',
    phone VARCHAR(50),
    office_location VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Instructors Table
CREATE TABLE IF NOT EXISTS instructors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    suffix VARCHAR(20),
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    employee_id VARCHAR(50),
    position VARCHAR(100) DEFAULT 'Instructor',
    phone VARCHAR(50),
    office_location VARCHAR(200),
    avatar_gradient_from VARCHAR(20) DEFAULT '#667eea',
    avatar_gradient_to VARCHAR(20) DEFAULT '#764ba2',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Courses Table
CREATE TABLE IF NOT EXISTS courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) NOT NULL,
    course_name VARCHAR(200) NOT NULL,
    description TEXT,
    department VARCHAR(100),
    instructor_id INT,
    student_count INT DEFAULT 0,
    evaluated_count INT DEFAULT 0,
    schedule VARCHAR(100),
    room VARCHAR(100),
    semester VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Students Table
CREATE TABLE IF NOT EXISTS students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    course_code VARCHAR(20),
    year_level VARCHAR(50),
    attendance_rate DECIMAL(5,2) DEFAULT 0.00,
    avatar_initials VARCHAR(5),
    avatar_gradient_from VARCHAR(20) DEFAULT '#3b82f6',
    avatar_gradient_to VARCHAR(20) DEFAULT '#60a5fa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Evaluations Table
CREATE TABLE IF NOT EXISTS evaluations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_id INT NOT NULL,
    course_id INT,
    department VARCHAR(100),
    rating DECIMAL(3,2),
    feedback TEXT,
    student_name VARCHAR(100),
    semester VARCHAR(50),
    status ENUM('completed', 'pending', 'overdue') DEFAULT 'completed',
    evaluation_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Evaluation Feedback Table (student feedback on instructors)
CREATE TABLE IF NOT EXISTS evaluation_feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_id INT NOT NULL,
    course_id INT,
    course_name VARCHAR(200),
    feedback_text TEXT,
    rating DECIMAL(3,2),
    feedback_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reports Table
CREATE TABLE IF NOT EXISTS reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_name VARCHAR(200) NOT NULL,
    report_description VARCHAR(500),
    report_type ENUM('pdf', 'excel') DEFAULT 'pdf',
    icon_class VARCHAR(100) DEFAULT 'fas fa-file-pdf',
    download_count INT DEFAULT 0,
    generated_by VARCHAR(50) DEFAULT 'instructor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Instructor Courses (linking table for instructor's own courses view)
CREATE TABLE IF NOT EXISTS instructor_courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_id INT NOT NULL,
    course_id INT NOT NULL,
    bg_class VARCHAR(20) DEFAULT 'bg-1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Pending Instructor Registrations (self-registration)
CREATE TABLE IF NOT EXISTS pending_instructors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    employee_id VARCHAR(50) NOT NULL UNIQUE,
    department VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin Promotions: tracks which instructors are promoted (UI Role = Program Head).
-- When status = 'active' and promoted_to = 'program_head', the instructor is shown as Program Head
-- in All Instructors table. Login as Program Head uses program_heads table (email + password set on promote).
CREATE TABLE IF NOT EXISTS admin_promotions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_id INT NOT NULL,
    promoted_to VARCHAR(50) NOT NULL COMMENT 'program_head or admin',
    promoted_by INT NOT NULL COMMENT 'admin user_id',
    promotion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'revoked') DEFAULT 'active',
    FOREIGN KEY (instructor_id) REFERENCES instructors(id)
);

-- =====================================================
-- SAMPLE DATA INSERTS
-- =====================================================

-- Sample Users
-- Note: All passwords are 'password123' (hashed with bcrypt)
-- Admin
INSERT INTO admins (first_name, last_name, email, password, role) VALUES
('System', 'Administrator', 'admin@cjcm.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Departments
INSERT INTO departments (department_name, icon_class, gradient_from, gradient_to, instructor_count, course_count) VALUES
('Operational Management', 'fas fa-cogs', '#d4a843', '#e8c768', 8, 12),
('Financial Management', 'fas fa-dollar-sign', '#3b82f6', '#60a5fa', 5, 8),
('Marketing Management', 'fas fa-chart-line', '#ec4899', '#f472b6', 3, 5);

-- Program Heads
INSERT INTO program_heads (first_name, last_name, email, password, department, position, phone, office_location) VALUES
('John', 'Head', 'head@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operational Management', 'Program Head', '+1 234 567 8901', 'Room 201, Building A'),
('Sarah', 'Manager', 'manager@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Financial Management', 'Program Head', '+1 234 567 8902', 'Room 202, Building A'),
('Robert', 'Marketing', 'marketing@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marketing Management', 'Program Head', '+1 234 567 8903', 'Room 203, Building A');

-- Instructors
INSERT INTO instructors (first_name, last_name, email, password, department, employee_id, position, phone, office_location, avatar_gradient_from, avatar_gradient_to, status) VALUES
('Jane', 'Teacher', 'teacher@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operational Management', 'EMP0001', 'Instructor', '+1 234 567 8900', 'Room 305, Building A', '#667eea', '#764ba2', 'active'),
('Michael', 'Brown', 'michael.brown@cjcm.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Financial Management', 'EMP0002', 'Instructor', '+1 234 567 8910', 'Room 306, Building A', '#11998e', '#38ef7d', 'active'),
('Sarah', 'Johnson', 'sarah.johnson@cjcm.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marketing Management', 'EMP0003', 'Instructor', '+1 234 567 8920', 'Room 307, Building A', '#f093fb', '#f5576c', 'active'),
('David', 'Lee', 'david.lee@cjcm.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operational Management', 'EMP0004', 'Instructor', '+1 234 567 8930', 'Room 308, Building A', '#4facfe', '#00f2fe', 'active'),
('Emily', 'Davis', 'emily.davis@cjcm.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Financial Management', 'EMP0005', 'Instructor', '+1 234 567 8940', 'Room 309, Building A', '#fa709a', '#fee140', 'active'),
('James', 'Wilson', 'james.wilson@cjcm.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marketing Management', 'EMP0006', 'Instructor', '+1 234 567 8950', 'Room 310, Building A', '#a18cd1', '#fbc2eb', 'active');

-- Courses
INSERT INTO courses (course_code, course_name, description, department, instructor_id, student_count, evaluated_count, schedule, room, semester, status) VALUES
('BM101', 'Business Management 101', 'Introduction to business principles, management strategies, and organizational behavior.', 'Operational Management', 1, 45, 42, 'Mon/Wed 9AM', 'Room 101', 'Spring 2026', 'active'),
('MKT201', 'Marketing Principles', 'Fundamentals of marketing strategies, consumer behavior, and market analysis.', 'Marketing Management', 2, 38, 35, 'Tue/Thu 11AM', 'Room 203', 'Spring 2026', 'active'),
('SM301', 'Strategic Management', 'Advanced strategic planning and management concepts.', 'Operational Management', 1, 25, 22, 'Fri 2PM', 'Room 305', 'Spring 2026', 'active'),
('BE201', 'Business Ethics', 'Ethical principles in business and corporate governance.', 'Operational Management', 1, 32, 28, 'Wed 4PM', 'Room 102', 'Spring 2026', 'active'),
('FIN301', 'Financial Accounting', 'Advanced accounting principles, financial reporting, and analysis techniques.', 'Financial Management', 3, 32, 28, 'Mon/Wed 2PM', 'Room 401', 'Spring 2026', 'active'),
('FM201', 'Financial Management 201', 'Financial analysis, investment decisions, and corporate finance strategies.', 'Financial Management', 4, 40, 35, 'Tue/Thu 3PM', 'Room 402', 'Spring 2026', 'active'),
('ENT101', 'Entrepreneurship', 'Fundamentals of entrepreneurship and startup management.', 'Marketing Management', 1, 22, 18, 'Thu 10AM', 'Room 201', 'Fall 2025', 'active'),
('OM101', 'Operations Management 101', 'Introduction to operations management', 'Operational Management', 4, 30, 25, 'Mon 10AM', 'Room 301', 'Spring 2026', 'active'),
('OM201', 'Office Management 201', 'Advanced office management and administration', 'Operational Management', 3, 28, 24, 'Wed 1PM', 'Room 302', 'Spring 2026', 'active');

-- Students
INSERT INTO students (first_name, last_name, email, course_code, year_level, attendance_rate, avatar_initials, avatar_gradient_from, avatar_gradient_to) VALUES
('John', 'Doe', 'john.doe@student.edu', 'BM101', '3rd Year', 95.00, 'JD', '#3b82f6', '#60a5fa'),
('Jane', 'Wilson', 'jane.wilson@student.edu', 'BM101', '2nd Year', 92.00, 'JW', '#10b981', '#34d399'),
('Mike', 'Johnson', 'mike.j@student.edu', 'MKT201', '3rd Year', 88.00, 'MJ', '#8b5cf6', '#a78bfa'),
('Sarah', 'Williams', 'sarah.w@student.edu', 'SM301', '4th Year', 98.00, 'SW', '#f43f5e', '#fb7185'),
('Tom', 'Brown', 'tom.b@student.edu', 'BM101', '2nd Year', 90.00, 'TB', '#f59e0b', '#fbbf24');

-- Evaluations (instructor evaluations with full details)
INSERT INTO evaluations (instructor_id, course_id, department, rating, feedback, student_name, semester, status, evaluation_date) VALUES
-- Instructor 1 (Jane Smith) evaluations
(1, 1, 'Operational Management', 4.80, 'Excellent teaching methodology. Very engaging and practical examples that really help understand the concepts.', 'Student A', 'Spring 2026', 'completed', '2026-03-08'),
(1, 2, 'Marketing Management', 4.60, 'Great examples and case studies. Would love more interactive sessions in class.', 'Student B', 'Spring 2026', 'completed', '2026-03-07'),
(1, 3, 'Operational Management', 4.70, 'Very knowledgeable and explains complex concepts clearly. The group projects are very helpful.', 'Student C', 'Spring 2026', 'completed', '2026-03-06'),
(1, 4, 'Operational Management', 4.50, 'Good overall. Could use more real-world applications and guest speakers.', 'Student D', 'Fall 2025', 'completed', '2025-12-15'),
(1, 7, 'Marketing Management', 4.30, 'Love the interactive activities! Would appreciate more time for discussions.', 'Student E', 'Fall 2025', 'completed', '2025-12-12'),
-- Instructor 2 (Michael Brown) evaluations
(2, 2, 'Marketing Management', 4.50, 'Great examples and case studies.', 'Student F', 'Spring 2026', 'completed', '2026-03-07'),
(2, 6, 'Financial Management', 4.70, 'Excellent financial concepts explained well.', 'Student G', 'Spring 2026', 'completed', '2026-03-05'),
-- Instructor 3 (Sarah Johnson) evaluations
(3, 5, 'Financial Management', 4.20, 'Very engaging marketing strategies.', 'Student H', 'Spring 2026', 'pending', '2026-03-06'),
(3, 9, 'Operational Management', 4.40, 'Loved the interactive sessions!', 'Student I', 'Spring 2026', 'completed', '2026-03-04'),
-- Instructor 4 (David Lee) evaluations
(4, 6, 'Financial Management', 3.80, 'Needs improvement in delivery.', 'Student J', 'Spring 2026', 'overdue', '2026-03-05'),
(4, 8, 'Operational Management', 4.60, 'Very knowledgeable and helpful.', 'Student K', 'Spring 2026', 'completed', '2026-03-03'),
-- Instructor 5 (Emily Davis) evaluations
(5, 6, 'Financial Management', 4.30, 'Good explanations of financial concepts.', 'Student L', 'Spring 2026', 'completed', '2026-03-02'),
(5, 5, 'Financial Management', 4.10, 'Solid teaching but could be more engaging.', 'Student M', 'Spring 2026', 'completed', '2026-03-01');

-- Evaluation Feedback (student comments on instructors)
INSERT INTO evaluation_feedback (instructor_id, course_id, course_name, feedback_text, rating, feedback_date) VALUES
(1, 1, 'Business Management 101', 'Excellent teaching methodology. Very engaging and practical examples that really help understand the concepts.', 5.00, '2026-03-08'),
(1, 2, 'Marketing Principles', 'Great examples and case studies. Would love more interactive sessions in class.', 4.50, '2026-03-05'),
(1, 3, 'Strategic Management', 'Very knowledgeable and explains complex concepts clearly. The group projects are very helpful.', 5.00, '2026-03-03'),
(1, 1, 'Business Management 101', 'Good overall. Could use more real-world applications and guest speakers.', 4.00, '2026-02-28'),
(1, 2, 'Marketing Principles', 'Love the interactive activities! Would appreciate more time for discussions.', 4.50, '2026-02-25');

-- Reports
INSERT INTO reports (report_name, report_description, report_type, icon_class, download_count, generated_by) VALUES
('Evaluation Summary Report', 'Spring 2026 Semester', 'pdf', 'fas fa-file-pdf', 8, 'instructor'),
('Course Performance Report', 'All Courses - Academic Year 2025-2026', 'pdf', 'fas fa-file-pdf', 5, 'instructor'),
('Student Grades Export', 'Current Semester', 'excel', 'fas fa-file-excel', 6, 'instructor'),
('Feedback Analysis', 'All Courses - Comprehensive', 'pdf', 'fas fa-file-pdf', 4, 'instructor'),
('Department Performance Report', 'All Departments - Spring 2026', 'pdf', 'fas fa-file-pdf', 10, 'program_head'),
('Instructor Ranking Report', 'Top Performers - Academic Year 2025-2026', 'pdf', 'fas fa-file-pdf', 7, 'program_head'),
('Course Completion Report', 'Evaluation Completion Rates', 'excel', 'fas fa-file-excel', 3, 'program_head');

-- Instructor Courses (linking for instructor's own view)
INSERT INTO instructor_courses (instructor_id, course_id, bg_class) VALUES
(1, 1, 'bg-1'),
(1, 2, 'bg-2'),
(1, 3, 'bg-3'),
(1, 4, 'bg-4');
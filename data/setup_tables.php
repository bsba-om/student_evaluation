<?php
header('Content-Type: application/json');
require_once 'config.php';

$message = [];

try {
    // Check if subjects table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'subjects'");
    if ($stmt->rowCount() == 0) {
        // Create subjects table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS subjects (
                id INT PRIMARY KEY AUTO_INCREMENT,
                subject_code VARCHAR(20) NOT NULL,
                subject_name VARCHAR(100) NOT NULL,
                description TEXT,
                units DECIMAL(3,1) DEFAULT 3.0,
                lecture_hours INT DEFAULT 2,
                lab_hours INT DEFAULT 0,
                credit_type VARCHAR(20) DEFAULT 'lec',
                default_year_level VARCHAR(20) DEFAULT '1st Year',
                default_semester VARCHAR(20) DEFAULT '1st Semester',
                icon_class VARCHAR(100) DEFAULT 'fas fa-book',
                color VARCHAR(20) DEFAULT '#3b82f6',
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_subject_code (subject_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $message[] = 'Created subjects table';
     } else {
        // Add columns if they don't exist
        try { $pdo->exec("ALTER TABLE subjects ADD COLUMN lecture_hours INT DEFAULT 2"); $message[] = 'Added lecture_hours'; } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE subjects ADD COLUMN lab_hours INT DEFAULT 0"); $message[] = 'Added lab_hours'; } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE subjects ADD COLUMN credit_type VARCHAR(20) DEFAULT 'lec'"); $message[] = 'Added credit_type'; } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE subjects ADD COLUMN default_year_level VARCHAR(20) DEFAULT '1st Year'"); $message[] = 'Added default_year_level'; } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE subjects ADD COLUMN default_semester VARCHAR(20) DEFAULT '1st Semester'"); $message[] = 'Added default_semester'; } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE subjects ADD COLUMN prerequisite_subject_code VARCHAR(255) DEFAULT NULL"); $message[] = 'Added prerequisite_subject_code'; } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE subjects ADD COLUMN prerequisite VARCHAR(50) DEFAULT NULL"); $message[] = 'Added prerequisite'; } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE subjects ADD COLUMN bridging_for VARCHAR(100) DEFAULT NULL"); $message[] = 'Added bridging_for'; } catch (Exception $e) {}
    }

    // Check major_subjects table and add prerequisite column if missing
    try { $pdo->exec("ALTER TABLE major_subjects ADD COLUMN prerequisite VARCHAR(50) DEFAULT NULL"); $message[] = 'Added prerequisite to major_subjects'; } catch (Exception $e) {}
    
    // Check if major_subjects table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'major_subjects'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS major_subjects (
                id INT PRIMARY KEY AUTO_INCREMENT,
                major_id INT NOT NULL,
                subject_id INT NOT NULL,
                year_level VARCHAR(20) DEFAULT '1st Year',
                semester VARCHAR(20) DEFAULT '1st Semester',
                is_required BOOLEAN DEFAULT TRUE,
                is_prerequisite BOOLEAN DEFAULT FALSE,
                prerequisite VARCHAR(50) DEFAULT NULL,
                prerequisite_for INT DEFAULT NULL,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_major_subject (major_id, subject_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $message[] = 'Created major_subjects table';
    }
    
     // Create prerequisite sets tables
     try {
         $pdo->exec("CREATE TABLE IF NOT EXISTS prerequisite_sets (
             id INT AUTO_INCREMENT PRIMARY KEY,
             code VARCHAR(100) NOT NULL UNIQUE,
             major_id INT NOT NULL,
             target_subject_id INT DEFAULT NULL,
             created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
             FOREIGN KEY (major_id) REFERENCES majors(id) ON DELETE CASCADE,
             FOREIGN KEY (target_subject_id) REFERENCES subjects(id) ON DELETE SET NULL
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
         $message[] = 'Created prerequisite_sets table';
     } catch (Exception $e) {}

      try {
          $pdo->exec("CREATE TABLE IF NOT EXISTS prerequisite_set_subjects (
              id INT AUTO_INCREMENT PRIMARY KEY,
              set_id INT NOT NULL,
              subject_id INT NOT NULL,
              FOREIGN KEY (set_id) REFERENCES prerequisite_sets(id) ON DELETE CASCADE,
              FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
              UNIQUE KEY uk_set_subject (set_id, subject_id)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
          $message[] = 'Created prerequisite_set_subjects table';
      } catch (Exception $e) {}

      // Student subject load - tracks which subjects a student is actually enrolled in for each semester
      try {
          $pdo->exec("CREATE TABLE IF NOT EXISTS student_subject_load (
              id INT AUTO_INCREMENT PRIMARY KEY,
              student_id INT NOT NULL,
              major_id INT NOT NULL,
              subject_id INT NOT NULL,
              academic_year VARCHAR(20) NOT NULL,
              year_level VARCHAR(20) NOT NULL,
              semester VARCHAR(20) NOT NULL,
              enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
              FOREIGN KEY (major_id) REFERENCES majors(id) ON DELETE CASCADE,
              FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
              UNIQUE KEY uk_student_semester_subject (student_id, subject_id, academic_year, year_level, semester)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
          $message[] = 'Created student_subject_load table';
      } catch (Exception $e) {}

      echo json_encode(['success' => true, 'message' => implode(', ', $message)]);
 } catch (PDOException $e) {
     echo json_encode(['success' => false, 'message' => $e->getMessage()]);
 }
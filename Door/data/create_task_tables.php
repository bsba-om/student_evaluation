<?php
// Script to add task tables to the database
require_once 'config.php';

try {
    // Check if tasks table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'tasks'");
    $tasksExists = $stmt->fetch();
    
    if (!$tasksExists) {
        echo "Creating tasks table...\n";
        
        $sql = "
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
        ";
        $pdo->exec($sql);
        echo "tasks table created.\n";
    } else {
        echo "tasks table already exists.\n";
    }
    
    // Check if task_assignments table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'task_assignments'");
    $assignmentsExists = $stmt->fetch();
    
    if (!$assignmentsExists) {
        echo "Creating task_assignments table...\n";
        
        $sql = "
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
        ";
        $pdo->exec($sql);
        echo "task_assignments table created.\n";
    } else {
        echo "task_assignments table already exists.\n";
    }
    
    echo "Database update complete!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

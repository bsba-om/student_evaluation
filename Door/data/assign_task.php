<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'instructor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$instructor_id = $_SESSION['user_id'];

require_once 'config.php';

// Check database connection
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$title = trim($input['title'] ?? '');
$description = trim($input['description'] ?? '');
$priority = $input['priority'] ?? 'medium';
$due_date = $input['due_date'] ?? null;
$mentee_ids = $input['mentee_ids'] ?? [];

// Validate
if (empty($title)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Task title is required']);
    exit;
}

if (empty($mentee_ids) || !is_array($mentee_ids) || count($mentee_ids) == 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'At least one mentee must be selected']);
    exit;
}

// Validate priority
$valid_priorities = ['low', 'medium', 'high'];
if (!in_array($priority, $valid_priorities)) {
    $priority = 'medium';
}

// Validate due date
if ($due_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
    $due_date = null;
}

try {
    // Check if tables exist, create if not
    $stmt = $pdo->query("SHOW TABLES LIKE 'tasks'");
    if (!$stmt->fetch()) {
        $pdo->exec("
            CREATE TABLE tasks (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'task_assignments'");
    if (!$stmt->fetch()) {
        $pdo->exec("
            CREATE TABLE task_assignments (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    $pdo->beginTransaction();
    
    // Verify all mentees belong to this instructor
    if (count($mentee_ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($mentee_ids), '?'));
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as cnt FROM mentees 
            WHERE id IN ($placeholders) AND mentor_id = ?
        ");
        $stmt->execute(array_merge($mentee_ids, [$instructor_id]));
        $count = $stmt->fetchColumn();
        
        if ($count != count($mentee_ids)) {
            throw new Exception('Some selected mentees are not assigned to you');
        }
    }
    
    // Insert task
    $stmt = $pdo->prepare("
        INSERT INTO tasks (title, description, instructor_id, priority, due_date)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $description, $instructor_id, $priority, $due_date]);
    $task_id = $pdo->lastInsertId();
    
    // Assign task to mentees
    $stmt = $pdo->prepare("
        INSERT INTO task_assignments (task_id, mentee_id, status)
        VALUES (?, ?, 'pending')
    ");
    
    foreach ($mentee_ids as $mentee_id) {
        $stmt->execute([$task_id, $mentee_id]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Task assigned successfully to ' . count($mentee_ids) . ' mentee(s)',
        'task_id' => $task_id
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

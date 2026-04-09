<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$instructor_id = $_SESSION['user_id'];

require_once 'config.php';

try {
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'tasks'");
    if (!$stmt->fetch()) {
        echo json_encode(['success' => true, 'tasks' => []]);
        exit;
    }
    
    // Fetch tasks with their assignments and mentee details
    $stmt = $pdo->prepare("
        SELECT 
            t.id as task_id,
            t.title,
            t.description,
            t.priority,
            t.due_date,
            t.status as task_status,
            t.created_at,
            GROUP_CONCAT(
                CONCAT(me.first_name, ' ', me.last_name, ' (', s.email, ')')
                SEPARATOR '; '
            ) as mentees_list,
            COUNT(ta.id) as assigned_count
        FROM tasks t
        LEFT JOIN task_assignments ta ON t.id = ta.task_id
        LEFT JOIN mentees m ON ta.mentee_id = m.id
        LEFT JOIN students s ON m.student_id = s.id
        LEFT JOIN instructors i ON t.instructor_id = i.id
        WHERE t.instructor_id = ?
        GROUP BY t.id
        ORDER BY 
            CASE t.priority 
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
            END,
            t.due_date ASC,
            t.created_at DESC
    ");
    
    $stmt->execute([$instructor_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch individual mentees for each task
    foreach ($tasks as &$task) {
        $stmt2 = $pdo->prepare("
            SELECT 
                m.id as mentee_id,
                s.first_name,
                s.last_name,
                s.email,
                s.student_id,
                ta.status as assignment_status,
                ta.completion_date
            FROM task_assignments ta
            JOIN mentees m ON ta.mentee_id = m.id
            JOIN students s ON m.student_id = s.id
            WHERE ta.task_id = ?
            ORDER BY s.last_name, s.first_name
        ");
        $stmt2->execute([$task['task_id']]);
        $task['mentees'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'tasks' => $tasks
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

<?php
session_start();
error_reporting(1);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$mentee_id = isset($_GET['mentee_id']) ? intval($_GET['mentee_id']) : 0;

if (!$mentee_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid mentee ID']);
    exit;
}

require_once 'config.php';

try {
    // Fetch tasks for this specific mentee
    $stmt = $pdo->prepare("
        SELECT 
            t.id as task_id,
            t.title,
            t.description,
            t.priority,
            t.due_date,
            t.status as task_status,
            t.created_at,
            ta.status as assignment_status,
            ta.completion_date
        FROM task_assignments ta
        JOIN tasks t ON ta.task_id = t.id
        WHERE ta.mentee_id = ?
        ORDER BY 
            CASE ta.status 
                WHEN 'pending' THEN 1
                WHEN 'completed' THEN 2
            END,
            t.due_date ASC,
            t.created_at DESC
    ");
    
    $stmt->execute([$mentee_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'tasks' => $tasks
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
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

$input = json_decode(file_get_contents('php://input'), true);

$task_id = isset($input['task_id']) ? intval($input['task_id']) : 0;
$mentee_id = isset($input['mentee_id']) ? intval($input['mentee_id']) : 0;
$status = isset($input['status']) ? trim($input['status']) : '';

if (!$task_id || !$mentee_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid task or mentee ID']);
    exit;
}

if (!in_array($status, ['pending', 'completed'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

require_once 'config.php';

try {
    $stmt = $pdo->prepare("
        UPDATE task_assignments 
        SET status = ?, 
            completion_date = NOW()
        WHERE task_id = ? AND mentee_id = ?
    ");
    
    $stmt->execute([$status, $task_id, $mentee_id]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Task status updated successfully'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
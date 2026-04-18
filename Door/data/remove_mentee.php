<?php
// Door/data/remove_mentee.php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$mentee_id = isset($_POST['mentee_id']) ? intval($_POST['mentee_id']) : 0;

if ($mentee_id <= 0 || $instructor_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE mentee_id = ?");
    $stmt->execute([$mentee_id]);
    
    $stmt = $pdo->prepare("DELETE FROM mentees WHERE id = ? AND mentor_id = ?");
    $stmt->execute([$mentee_id, $instructor_id]);
    
    $pdo->commit();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Mentee removed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Mentee not found or not assigned to this instructor']);
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
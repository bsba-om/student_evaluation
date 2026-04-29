<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$instructor_id = $_SESSION['user_id'];

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$mentee_id = isset($input['mentee_id']) ? intval($input['mentee_id']) : 0;

if ($mentee_id <= 0 || $instructor_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

 try {
     $pdo->beginTransaction();
     
     // Verify the mentee belongs to this instructor
     $checkStmt = $pdo->prepare("SELECT id FROM mentees WHERE id = ? AND mentor_id = ?");
     $checkStmt->execute([$mentee_id, $instructor_id]);
     if (!$checkStmt->fetch()) {
         $pdo->rollBack();
         echo json_encode(['success' => false, 'message' => 'Mentee not found or not assigned to you']);
         exit;
     }
     
     // Delete all task assignments for this mentee (safe even if FK cascade exists)
     $delAssign = $pdo->prepare("DELETE FROM task_assignments WHERE mentee_id = ?");
     $delAssign->execute([$mentee_id]);
     
     // Delete the mentee record
     $stmt = $pdo->prepare("DELETE FROM mentees WHERE id = ?");
     $stmt->execute([$mentee_id]);
     
     $pdo->commit();
     
     echo json_encode(['success' => true, 'message' => 'Mentee and their tasks removed successfully']);
 } catch (PDOException $e) {
     $pdo->rollBack();
     echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
 }
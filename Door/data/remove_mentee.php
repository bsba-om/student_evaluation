<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$user_role = $_SESSION['user_role'] ?? '';
$user_id = $_SESSION['user_id'];

// Allow both instructors and program heads
if (!in_array($user_role, ['instructor', 'program_head'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Handle both JSON input and form-encoded input
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $mentee_id = isset($input['mentee_id']) ? intval($input['mentee_id']) : 0;
    $instructor_id = isset($input['instructor_id']) ? intval($input['instructor_id']) : 0;
} else {
    $mentee_id = isset($_POST['mentee_id']) ? intval($_POST['mentee_id']) : 0;
    $instructor_id = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : 0;
}

// For instructors, force instructor_id to their own ID (security)
if ($user_role === 'instructor') {
    $instructor_id = $user_id;
}

if ($mentee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid mentee ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Build the verification query based on role
    if ($user_role === 'instructor') {
        // Instructor can only remove their own mentees
        $checkStmt = $pdo->prepare("SELECT id FROM mentees WHERE id = ? AND mentor_id = ?");
        $checkStmt->execute([$mentee_id, $instructor_id]);
    } else {
        // Program head can remove any mentee (optionally verify instructor_id)
        if ($instructor_id > 0) {
            $checkStmt = $pdo->prepare("SELECT id FROM mentees WHERE id = ? AND mentor_id = ?");
            $checkStmt->execute([$mentee_id, $instructor_id]);
        } else {
            $checkStmt = $pdo->prepare("SELECT id FROM mentees WHERE id = ?");
            $checkStmt->execute([$mentee_id]);
        }
    }

    if (!$checkStmt->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Mentee not found or not assigned to this instructor']);
        exit;
    }

    // Delete all task assignments for this mentee (safe even if FK cascade exists)
    $delAssign = $pdo->prepare("DELETE FROM task_assignments WHERE mentee_id = ?");
    $delAssign->execute([$mentee_id]);

    // Delete the mentee record
    $stmt = $pdo->prepare("DELETE FROM mentees WHERE id = ?");
    $stmt->execute([$mentee_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Mentee removed successfully']);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
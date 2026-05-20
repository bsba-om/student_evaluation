<?php
require_once __DIR__ . '/../data/session_security.php';

header('Content-Type: application/json');

$role_access = check_role_access('instructor');
if (!$role_access['allowed']) {
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

$task_ids = $input['task_ids'] ?? [];

if (empty($task_ids) || !is_array($task_ids) || count($task_ids) == 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No tasks selected for deletion']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verify all tasks belong to this instructor
    if (count($task_ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($task_ids), '?'));
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as cnt FROM tasks 
            WHERE id IN ($placeholders) AND instructor_id = ?
        ");
        $stmt->execute(array_merge($task_ids, [$instructor_id]));
        $count = $stmt->fetchColumn();
        
        if ($count != count($task_ids)) {
            throw new Exception('Some selected tasks are not yours');
        }
    }

    // Delete task assignments first (due to foreign key)
    $placeholders = implode(',', array_fill(0, count($task_ids), '?'));
    $stmt = $pdo->prepare("DELETE FROM task_assignments WHERE task_id IN ($placeholders)");
    $stmt->execute($task_ids);

    // Delete tasks
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id IN ($placeholders)");
    $stmt->execute($task_ids);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => count($task_ids) . ' task(s) deleted successfully'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
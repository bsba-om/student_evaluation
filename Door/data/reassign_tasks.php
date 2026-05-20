<?php
require_once __DIR__ . '/session_security.php';

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
$mentee_ids = $input['mentee_ids'] ?? [];
$replace = $input['replace'] ?? false;

// Validate
if (empty($task_ids) || !is_array($task_ids) || count($task_ids) == 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'At least one task must be selected']);
    exit;
}

if (empty($mentee_ids) || !is_array($mentee_ids) || count($mentee_ids) == 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'At least one mentee must be selected']);
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

    foreach ($task_ids as $task_id) {
        if ($replace) {
            // Delete existing assignments for this task
            $stmt = $pdo->prepare("DELETE FROM task_assignments WHERE task_id = ?");
            $stmt->execute([$task_id]);
        }

        // Insert new assignments (only if not already assigned to avoid duplicate key)
        foreach ($mentee_ids as $mentee_id) {
            // Check if already assigned
            $check = $pdo->prepare("SELECT COUNT(*) FROM task_assignments WHERE task_id = ? AND mentee_id = ?");
            $check->execute([$task_id, $mentee_id]);
            $exists = $check->fetchColumn();

            if (!$exists) {
                $stmt = $pdo->prepare("INSERT INTO task_assignments (task_id, mentee_id, status) VALUES (?, ?, 'pending')");
                $stmt->execute([$task_id, $mentee_id]);
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Assignees updated successfully'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
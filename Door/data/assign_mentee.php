<?php
// Door/data/assign_mentee.php
header('Content-Type: application/json');
require_once 'config.php';

$instructor_id = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : 0;
$student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
$assignment_notes = isset($_POST['assignment_notes']) ? trim($_POST['assignment_notes']) : '';

$assigned_by_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$assigned_by_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Program Head';

error_log("assign_mentee.php: instructor_id=$instructor_id, student_id=$student_id");

if ($instructor_id <= 0 || $student_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid instructor or student ID']);
    exit;
}

try {
    // First get student details
    $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    
    // Check if mentee already exists for this instructor
    $stmt = $pdo->prepare("SELECT id FROM mentees WHERE mentor_id = ? AND email = ?");
    $stmt->execute([$instructor_id, $student['email']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Student is already assigned as a mentee to this instructor']);
        exit;
    }
    
    // Check if the new columns exist, if not use basic insert
    $columns = "student_id, first_name, last_name, email, mentor_id";
    $values = "?, ?, ?, ?, ?";
    $params = [$student_id, $student['first_name'], $student['last_name'], $student['email'], $instructor_id];
    
    // Try to add extended fields if they exist
    try {
        $testStmt = $pdo->query("SELECT assigned_by_id FROM mentees LIMIT 1");
        $columns .= ", assigned_by_id, assigned_by_name, assignment_notes";
        $values .= ", ?, ?, ?";
        $params[] = $assigned_by_id;
        $params[] = $assigned_by_name;
        $params[] = $assignment_notes;
    } catch (Exception $e) {
        // Columns don't exist yet, use basic insert
    }
    
    $sql = "INSERT INTO mentees ($columns) VALUES ($values)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Get the new mentee ID
    $mentee_id = $pdo->lastInsertId();
    
    error_log("assign_mentee.php: success, mentee_id=$mentee_id");
    echo json_encode(['success' => true, 'message' => 'Mentee assigned successfully', 'mentee_id' => $mentee_id]);
} catch (PDOException $e) {
    error_log("assign_mentee.php: error - " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

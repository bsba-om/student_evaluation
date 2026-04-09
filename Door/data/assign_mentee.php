<?php
// Door/data/assign_mentee.php
header('Content-Type: application/json');
require_once 'config.php';

$instructor_id = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : 0;
$student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;

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
    
     // Insert as mentee
     $stmt = $pdo->prepare("INSERT INTO mentees (student_id, first_name, last_name, email, mentor_id) VALUES (?, ?, ?, ?, ?)");
     $stmt->execute([$student_id, $student['first_name'], $student['last_name'], $student['email'], $instructor_id]);
     
     // Get the new mentee ID
     $mentee_id = $pdo->lastInsertId();
     
     echo json_encode(['success' => true, 'message' => 'Mentee assigned successfully', 'mentee_id' => $mentee_id]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

<?php
// Door/data/assign_mentee.php
header('Content-Type: application/json');
session_start();
require_once 'config.php';

// Disable output buffering so JSON is sent immediately on error
while (ob_get_level()) { ob_end_clean(); }

$instructor_id = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : 0;
$student_id    = isset($_POST['student_id'])    ? intval($_POST['student_id'])    : 0;
$assignment_notes = isset($_POST['assignment_notes']) ? trim($_POST['assignment_notes']) : '';

$assigned_by_id   = isset($_SESSION['user_id'])   ? $_SESSION['user_id']   : null;
$assigned_by_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Program Head';

function json_error($msg) {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

if ($instructor_id <= 0 || $student_id <= 0) {
    json_error('Invalid instructor or student ID');
}

if ($pdo === null) {
    json_error('Database connection failed');
}

try {
    // First get student details
    $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        json_error('Student not found');
    }

    // Check if mentee already exists for this instructor
    $stmt = $pdo->prepare("SELECT id FROM mentees WHERE mentor_id = ? AND email = ?");
    $stmt->execute([$instructor_id, $student['email']]);
    $existing = $stmt->fetch();

    if ($existing) {
        json_error('Student is already assigned as a mentee to this instructor');
    }

    // Check whether the extended columns exist
    $has_extended = false;
    try {
        $testStmt = $pdo->query("SELECT assigned_by_id FROM mentees LIMIT 1");
        $has_extended = true;
    } catch (PDOException $e) {
        $has_extended = false;
    }

    if ($has_extended) {
        $sql = "INSERT INTO mentees (student_id, first_name, last_name, email, mentor_id, assigned_by_id, assigned_by_name, assignment_notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $student['first_name'], $student['last_name'], $student['email'],
                        $instructor_id, $assigned_by_id, $assigned_by_name, $assignment_notes]);
    } else {
        $sql = "INSERT INTO mentees (student_id, first_name, last_name, email, mentor_id)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $student['first_name'], $student['last_name'], $student['email'], $instructor_id]);
    }

    $mentee_id = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'message' => 'Mentee assigned successfully', 'mentee_id' => (int)$mentee_id]);
} catch (PDOException $e) {
    json_error('Database error: ' . $e->getMessage());
}

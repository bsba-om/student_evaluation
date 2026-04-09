<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$allowed_roles = ['instructor', 'program_head', 'admin'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$instructor_id = $_SESSION['user_id'];
$mentee_id = $_GET['mentee_id'] ?? $_POST['mentee_id'] ?? null;

// Also accept student_id as fallback
$student_id = $_GET['student_id'] ?? $_POST['student_id'] ?? null;

if (!$mentee_id && !$student_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Mentee ID is required']);
    exit;
}

// If only student_id provided, look up mentee
if ($student_id && !$mentee_id) {
    require_once 'config.php';
    try {
        $stmt = $pdo->prepare("SELECT id FROM mentees WHERE student_id = ? AND mentor_id = ?");
        $stmt->execute([$student_id, $instructor_id]);
        $mentee = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($mentee) {
            $mentee_id = $mentee['id'];
        }
    } catch (PDOException $e) {
        // ignore
    }
}

if (!$mentee_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Mentee ID is required']);
    exit;
}

require_once 'config.php';

try {
    $stmt = $pdo->prepare("
        SELECT 
            m.id as mentee_id,
            m.student_id,
            m.first_name,
            m.last_name,
            m.email,
            s.student_id as actual_student_id,
            s.avatar_initials,
            s.year_level,
            s.major_id,
            maj.major_name
        FROM mentees m
        LEFT JOIN students s ON m.student_id = s.student_id OR m.email = s.email
        LEFT JOIN majors maj ON s.major_id = maj.id
        WHERE m.id = ? AND m.mentor_id = ?
    ");
    
    $stmt->execute([$mentee_id, $instructor_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Mentee not found or not assigned to you']);
        exit;
    }
    
    $student['avg_rating'] = 0;
    
    echo json_encode([
        'success' => true,
        'student' => $student
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
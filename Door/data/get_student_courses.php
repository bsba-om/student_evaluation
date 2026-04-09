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
$student_id = $_GET['student_id'] ?? $_POST['student_id'] ?? null;

if (!$student_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

require_once 'config.php';

try {
    $courses = [];
    
    echo json_encode([
        'success' => true,
        'courses' => $courses
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'courses' => [], 'message' => 'Database error: ' . $e->getMessage()]);
}
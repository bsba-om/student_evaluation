<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$instructor_id = $_SESSION['user_id'];

$first_name = $_POST['first_name'] ?? '';
$middle_name = $_POST['middle_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$suffix = $_POST['suffix'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$birthday = $_POST['birthday'] ?? null;
$position = $_POST['position'] ?? '';

if (empty($first_name) || empty($last_name) || empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
    exit;
}

require_once 'config.php';

try {
    $stmt = $pdo->prepare("
        UPDATE instructors 
        SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, email = ?, phone = ?, birthday = ?, position = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $first_name,
        $middle_name,
        $last_name,
        $suffix,
        $email,
        $phone,
        $birthday ?: null,
        $position,
        $instructor_id
    ]);
    
    // Update session user name
    $_SESSION['user_name'] = trim($first_name . ' ' . $middle_name . ' ' . $last_name);
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
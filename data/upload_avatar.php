<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (isset($_POST['instructor_id'])) {
    $instructor_id = intval($_POST['instructor_id']);
} elseif (isset($_SESSION['user_id'])) {
    $instructor_id = intval($_SESSION['user_id']);
} else {
    $instructor_id = 0;
}

if ($instructor_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid instructor ID: ' . $instructor_id]);
    exit;
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $error = $_FILES['avatar']['error'] ?? 'no file';
    echo json_encode(['success' => false, 'message' => 'No file uploaded. Error: ' . $error]);
    exit;
}

$file = $_FILES['avatar'];
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($file['type'], $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, WEBP allowed']);
    exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = $instructor_id . '.jpg';
$target_dir = __DIR__ . '/../media/instructors/';

if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

$target_path = $target_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    // Update avatar in database
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE instructors SET avatar = ? WHERE id = ?");
            $stmt->execute([$filename, $instructor_id]);
        } catch (PDOException $e) {
            error_log("Failed to update avatar in database: " . $e->getMessage());
        }
    }
    echo json_encode(['success' => true, 'message' => 'Avatar uploaded successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
}
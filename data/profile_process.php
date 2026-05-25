<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

function jsonResponse($data) {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

require_once __DIR__ . '/config.php';

$action = isset($_POST['action']) ? $_POST['action'] : '';

if (isset($_POST['instructor_id'])) {
    $instructor_id = intval($_POST['instructor_id']);
} elseif (isset($_SESSION['user_id'])) {
    $instructor_id = intval($_SESSION['user_id']);
} else {
    $instructor_id = 0;
}

if ($instructor_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid instructor ID']);
    return;
}

switch ($action) {
    case 'update_personal':
        updatePersonal($pdo, $instructor_id);
        break;
    case 'update_contact':
        updateContact($pdo, $instructor_id);
        break;
    case 'verify_current_password':
        verifyCurrentPassword($pdo, $instructor_id);
        break;
    case 'change_password':
        changePassword($pdo, $instructor_id);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function updatePersonal($pdo, $instructor_id) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $birthday = isset($_POST['birthday']) ? ($_POST['birthday'] === '' ? null : $_POST['birthday']) : null;
    
    if (empty($first_name) || empty($last_name)) {
        echo json_encode(['success' => false, 'message' => 'First name and last name are required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE instructors SET first_name = ?, last_name = ?, position = ?, middle_name = ?, suffix = ?, birthday = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $position, $middle_name, $suffix, $birthday, $instructor_id]);
        echo json_encode(['success' => true, 'message' => 'Personal information updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update: ' . $e->getMessage()]);
    }
}

function updateContact($pdo, $instructor_id) {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM instructors WHERE email = ? AND id != ?");
        $stmt->execute([$email, $instructor_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already in use']);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE instructors SET email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$email, $phone, $instructor_id]);
        echo json_encode(['success' => true, 'message' => 'Contact information updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update: ' . $e->getMessage()]);
    }
}

function changePassword($pdo, $instructor_id) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'All password fields are required']);
        return;
    }
    
    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
        return;
    }
    
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT password FROM instructors WHERE id = ?");
        $stmt->execute([$instructor_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        if (!password_verify($current_password, $row['password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            return;
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE instructors SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $instructor_id]);
        
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to change password']);
    }
}

function verifyCurrentPassword($pdo, $instructor_id) {
    $current_password = $_POST['current_password'] ?? '';
    
    if (empty($current_password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your current password']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT password FROM instructors WHERE id = ?");
        $stmt->execute([$instructor_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        if (!password_verify($current_password, $row['password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            return;
        }
        
        echo json_encode(['success' => true, 'message' => 'Password verified successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to verify password']);
    }
}
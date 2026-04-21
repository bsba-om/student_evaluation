<?php
// Student Management Handler - For Admin and Program Head
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once 'config.php';

// Allow both admin and program_head
$allowed_roles = ['admin', 'program_head'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: ../login.php');
    exit;
}

$action = $_GET['action'] ?? '';

// Add student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_student') {
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $major_id = intval($_POST['major_id'] ?? 0);
    $year_level = $_POST['year_level'] ?? '';
    
    if (empty($first_name) || empty($last_name) || empty($student_id) || empty($email) || empty($major_id) || empty($year_level)) {
        header('Location: ../program_head/pages/student_enrollment.php?error=' . urlencode('Please fill in all required fields'));
        exit;
    }
    
    $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
    $gradient_from = '#3b82f6';
    $gradient_to = '#60a5fa';
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO students (first_name, middle_name, last_name, suffix, student_id, email, major_id, year_level, avatar_initials, avatar_gradient_from, avatar_gradient_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $middle_name ?: null, $last_name, $suffix ?: null, $student_id, $email, $major_id, $year_level, $initials, $gradient_from, $gradient_to]);
            header('Location: ../program_head/pages/student_enrollment.php?success=' . urlencode('Student enrolled successfully!'));
        } catch (PDOException $e) {
            header('Location: ../program_head/pages/student_enrollment.php?error=' . urlencode('Email or Student ID already exists'));
        }
    } else {
        header('Location: ../program_head/pages/student_enrollment.php?success=' . urlencode('Student enrolled successfully! (Demo Mode)'));
    }
    exit;
}

// Search students
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'search') {
    header('Content-Type: application/json');
    $query = trim($_GET['q'] ?? '');
    
    if (strlen($query) < 2) {
        echo json_encode(['success' => false, 'message' => 'Query too short']);
        exit;
    }
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT s.*, m.display_name as major_display, m.major_name 
                                  FROM students s 
                                  LEFT JOIN majors m ON s.major_id = m.id 
                                  WHERE (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ? OR s.email LIKE ?)
                                  ORDER BY s.last_name, s.first_name 
                                  LIMIT 15");
            $searchTerm = '%' . $query . '%';
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $formatted = array_map(function($s) {
                $initials = strtoupper(substr($s['first_name'] ?? '', 0, 1) . substr($s['last_name'] ?? '', 0, 1));
                return [
                    'id' => $s['id'],
                    'first_name' => $s['first_name'],
                    'last_name' => $s['last_name'],
                    'student_id' => $s['student_id'],
                    'email' => $s['email'],
                    'year_level' => $s['year_level'] ?? '',
                    'major_display' => $s['major_display'] ?? $s['major_name'] ?? 'N/A',
                    'initials' => $initials ?: 'NA'
                ];
            }, $students);
            
            echo json_encode(['success' => true, 'students' => $formatted]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database not available']);
    }
    exit;
}

// Get single student
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    header('Content-Type: application/json');
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT s.*, m.display_name as major_display FROM students s LEFT JOIN majors m ON s.major_id = m.id WHERE s.id = ?");
            $stmt->execute([$id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student) {
                echo json_encode(['success' => true, 'student' => $student]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Student not found']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database not available']);
    }
    exit;
}

// Update student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_student') {
    $id = intval($_POST['id'] ?? 0);
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $major_id = intval($_POST['major_id'] ?? 0);
    $year_level = $_POST['year_level'] ?? '';
    
    if (empty($first_name) || empty($last_name) || empty($student_id) || empty($email) || empty($major_id) || empty($year_level)) {
        header('Location: ../program_head/pages/student_enrollment.php?error=' . urlencode('Please fill in all required fields'));
        exit;
    }
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE students SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, student_id = ?, email = ?, major_id = ?, year_level = ? WHERE id = ?");
            $stmt->execute([$first_name, $middle_name ?: null, $last_name, $suffix ?: null, $student_id, $email, $major_id, $year_level, $id]);
            header('Location: ../program_head/pages/student_enrollment.php?success=' . urlencode('Student updated successfully!'));
        } catch (PDOException $e) {
            header('Location: ../program_head/pages/student_enrollment.php?error=' . urlencode('Failed to update student'));
        }
    } else {
        header('Location: ../program_head/pages/student_enrollment.php?success=' . urlencode('Student updated successfully! (Demo Mode)'));
    }
    exit;
}

// Delete student (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_student') {
    header('Content-Type: application/json');
    $student_id = intval($_POST['student_id'] ?? 0);
    
    if ($student_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
        exit;
    }
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete student']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Student deleted successfully! (Demo Mode)']);
    }
    exit;
}

// Import students (CSV, Excel, Word)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'import_students') {
    if (!isset($_FILES['import_file'])) {
        header('Location: ../program_head/pages/student_enrollment.php?error=' . urlencode('No file uploaded'));
        exit;
    }
    
    $file = $_FILES['import_file'];
    $error = $file['error'];
    
    if ($error !== UPLOAD_ERR_OK) {
        header('Location: ../program_head/pages/student_enrollment.php?error=' . urlencode('Upload error'));
        exit;
    }
    
    $tmp_path = $file['tmp_name'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['csv', 'xlsx', 'xls', 'docx'];
    
    if (!in_array($extension, $allowed)) {
        header('Location: ../program_head/pages/student_enrollment.php?error=' . urlencode('Invalid file format'));
        exit;
    }
    
    // Simple CSV import for demo; full implementation would require PhpSpreadsheet library
    $imported = 0;
    $errors = [];
    
    if ($extension === 'csv') {
        if (($handle = fopen($tmp_path, 'r')) !== false) {
            $row_num = 0;
            while (($data = fgetcsv($handle)) !== false) {
                $row_num++;
                if ($row_num == 1) continue; // Skip header
                if (count($data) < 6) {
                    $errors[] = "Row $row_num: insufficient columns";
                    continue;
                }
                list($first_name, $last_name, $student_id, $email, $major_id, $year_level) = $data;
                $first_name = trim($first_name);
                $last_name = trim($last_name);
                $student_id = trim($student_id);
                $email = trim($email);
                
                if (empty($first_name) || empty($last_name) || empty($student_id) || empty($email)) {
                    $errors[] = "Row $row_num: missing required fields";
                    continue;
                }
                
                $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
                $gradient_from = '#3b82f6';
                $gradient_to = '#60a5fa';
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO students (first_name, middle_name, last_name, suffix, student_id, email, major_id, year_level, avatar_initials, avatar_gradient_from, avatar_gradient_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$first_name, '', $last_name, '', $student_id, $email, intval($major_id), $year_level, $initials, $gradient_from, $gradient_to]);
                    $imported++;
                } catch (PDOException $e) {
                    $errors[] = "Row $row_num: " . $e->getMessage();
                }
            }
            fclose($handle);
        }
    } else {
        // For non-CSV, just show a placeholder message (would need PhpSpreadsheet)
        header('Location: ../program_head/pages/student_enrollment.php?error=' . urlencode('Excel/Word import requires additional library configuration'));
        exit;
    }
    
    $msg = "Imported $imported students";
    if (!empty($errors)) {
        $msg .= '; ' . count($errors) . ' errors';
    }
    header('Location: ../program_head/pages/student_enrollment.php?success=' . urlencode($msg));
    exit;
}

// If no valid action, redirect
header('Location: ../program_head/pages/student_enrollment.php');
exit;
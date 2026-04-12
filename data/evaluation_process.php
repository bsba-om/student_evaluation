<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

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
    case 'get_mentees':
        getMentees($pdo, $instructor_id);
        break;
    case 'save_grade':
        saveGrade($pdo, $instructor_id);
        break;
    case 'get_grades':
        getGrades($pdo, $instructor_id);
        break;
    case 'save_subject_grade':
        saveSubjectGrade($pdo, $instructor_id);
        break;
    case 'get_eligible_subjects':
        getEligibleSubjects($pdo, $instructor_id);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getMentees($pdo, $instructor_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT m.id, m.student_id, m.first_name, m.last_name, m.email, m.created_at,
                   s.major_id, s.year_level
            FROM mentees m
            LEFT JOIN students s ON m.student_id = s.id
            WHERE m.mentor_id = ?
            ORDER BY m.last_name, m.first_name
        ");
        $stmt->execute([$instructor_id]);
        $mentees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'mentees' => $mentees]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch mentees']);
    }
}

function saveGrade($pdo, $instructor_id) {
    $student_id = intval($_POST['student_id'] ?? 0);
    $grade = trim($_POST['grade'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    
    if ($student_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid student']);
        return;
    }
    
    if (empty($grade)) {
        echo json_encode(['success' => false, 'message' => 'Grade is required']);
        return;
    }
    
    $valid_grades = ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D', 'F', 'INC', 'DRP'];
    if (!in_array($grade, $valid_grades)) {
        echo json_encode(['success' => false, 'message' => 'Invalid grade']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO student_grades (student_id, instructor_id, grade, remarks, graded_by)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE grade = ?, remarks = ?, updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$student_id, $instructor_id, $grade, $remarks, $instructor_id, $grade, $remarks]);
        
        echo json_encode(['success' => true, 'message' => 'Grade saved successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to save grade']);
    }
}

function getGrades($pdo, $instructor_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT sg.*, m.first_name, m.last_name, m.email
            FROM student_grades sg
            JOIN mentees m ON sg.student_id = m.student_id AND sg.instructor_id = m.mentor_id
            WHERE sg.instructor_id = ?
            ORDER BY m.last_name, m.first_name
        ");
        $stmt->execute([$instructor_id]);
        $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'grades' => $grades]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch grades']);
    }
}

function saveSubjectGrade($pdo, $instructor_id) {
    $student_id = intval($_POST['student_id'] ?? 0);
    $major_id = intval($_POST['major_id'] ?? 0);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $year_level = trim($_POST['year_level'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $grade = trim($_POST['grade'] ?? '');
    
    if ($student_id <= 0 || $subject_id <= 0 || empty($year_level) || empty($semester)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO student_subject_grades (student_id, major_id, subject_id, year_level, semester, grade, graded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE grade = ?, updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$student_id, $major_id, $subject_id, $year_level, $semester, $grade, $instructor_id, $grade]);
        
        echo json_encode(['success' => true, 'message' => 'Grade saved']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to save grade']);
    }
}

function getEligibleSubjects($pdo, $instructor_id) {
    $student_id = intval($_POST['student_id'] ?? 0);
    $major_id = intval($_POST['major_id'] ?? 0);
    $current_year = trim($_POST['current_year'] ?? '1st Year');
    
    if ($student_id <= 0 || $major_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }
    
    $year_order = ['1st Year' => 1, '2nd Year' => 2, '3rd Year' => 3, '4th Year' => 4];
    $current_year_num = $year_order[$current_year] ?? 1;
    $next_year = $current_year_num < 4 ? array_search($current_year_num + 1, $year_order) : '4th Year';
    
    try {
        $stmt = $pdo->prepare("
            SELECT ms.*, sub.subject_code, sub.subject_name, sub.units
            FROM major_subjects ms
            JOIN subjects sub ON ms.subject_id = sub.id
            WHERE ms.major_id = ? AND ms.year_level = ?
            ORDER BY ms.semester, ms.sort_order
        ");
        $stmt->execute([$major_id, $next_year]);
        $next_year_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT subject_id, grade FROM student_subject_grades 
            WHERE student_id = ? AND graded_by = ?
        ");
        $stmt->execute([$student_id, $instructor_id]);
        $completed = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $passed_ids = [];
        foreach ($completed as $c) {
            if (!in_array($c['grade'], ['F', 'DRP', 'INC', ''])) {
                $passed_ids[] = $c['subject_id'];
            }
        }
        
        $eligible = [];
        foreach ($next_year_subjects as $sub) {
            if (!$sub['is_prerequisite']) {
                $eligible[] = $sub;
                continue;
            }
            
            if ($sub['prerequisite_for']) {
                $stmt = $pdo->prepare("SELECT subject_id FROM major_subjects WHERE id = ?");
                $stmt->execute([$sub['prerequisite_for']]);
                $prereq = $stmt->fetch();
                
                if ($prereq && in_array($prereq['subject_id'], $passed_ids)) {
                    $eligible[] = $sub;
                }
            } else {
                $eligible[] = $sub;
            }
        }
        
        echo json_encode(['success' => true, 'subjects' => $eligible]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get eligible subjects']);
    }
}
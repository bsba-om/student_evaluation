<?php
// Door/data/major_process.php
header('Content-Type: application/json');
require_once 'config.php';

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'get_majors':
        getMajors();
        break;
    case 'add_major':
        addMajor();
        break;
    case 'update_major':
        updateMajor();
        break;
    case 'delete_major':
        deleteMajor();
        break;
    case 'get_subjects':
        getSubjects();
        break;
    case 'get_all_subjects':
        getAllSubjects();
        break;
    case 'add_subject':
        addSubject();
        break;
    case 'update_subject':
        updateSubject();
        break;
    case 'delete_subject':
        deleteSubject();
        break;
    case 'get_major_subjects':
        getMajorSubjects();
        break;
    case 'add_major_subject':
        addMajorSubject();
        break;
    case 'remove_major_subject':
        removeMajorSubject();
        break;
    case 'update_major_subject':
        updateMajorSubject();
        break;
    case 'update_major_subject_flag':
        updateMajorSubjectFlag();
        break;
    case 'update_subject_prerequisite':
        updateSubjectPrerequisite();
        break;
    case 'create_prereq_set':
        createPrereqSet();
        break;
    case 'get_prereq_sets':
        getPrereqSets();
        break;
    case 'delete_prereq_set':
        deletePrereqSet();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function updateSubjectPrerequisite() {
    global $pdo;
    $subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
    $prerequisite_codes = isset($_POST['prerequisite_subject_code']) ? trim($_POST['prerequisite_subject_code']) : '';
    
    if ($subject_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid subject ID']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE subjects SET prerequisite_subject_code = ? WHERE id = ?");
        $stmt->execute([$prerequisite_codes, $subject_id]);
        echo json_encode(['success' => true, 'message' => 'Prerequisites saved successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function createPrereqSet() {
    global $pdo;
    $prereq_code = isset($_POST['prereq_code']) ? trim($_POST['prereq_code']) : '';
    $major_id = isset($_POST['major_id']) ? intval($_POST['major_id']) : 0;
    $subject_ids = isset($_POST['subject_ids']) ? $_POST['subject_ids'] : '';
    $target_subject_id = isset($_POST['target_subject_id']) ? intval($_POST['target_subject_id']) : 0;
    
    if (empty($prereq_code)) {
        echo json_encode(['success' => false, 'message' => 'Prerequisites code is required']);
        return;
    }
    if ($major_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please select a major']);
        return;
    }
    
    $ids = json_decode($subject_ids, true);
    if (!is_array($ids) || count($ids) === 0) {
        echo json_encode(['success' => false, 'message' => 'At least one subject is required']);
        return;
    }
    
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS prerequisite_sets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(100) NOT NULL UNIQUE,
            major_id INT NOT NULL,
            target_subject_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        try { $pdo->exec("ALTER TABLE prerequisite_sets ADD COLUMN major_id INT NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE prerequisite_sets ADD COLUMN target_subject_id INT DEFAULT NULL"); } catch (Exception $e) {}
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS prerequisite_set_subjects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            set_id INT NOT NULL,
            subject_id INT NOT NULL,
            FOREIGN KEY (set_id) REFERENCES prerequisite_sets(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
        )");
        
        $stmt = $pdo->prepare("INSERT INTO prerequisite_sets (code, major_id, target_subject_id) VALUES (?, ?, ?)");
        $stmt->execute([$prereq_code, $major_id, $target_subject_id > 0 ? $target_subject_id : null]);
        $set_id = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("INSERT INTO prerequisite_set_subjects (set_id, subject_id) VALUES (?, ?)");
        foreach ($ids as $sid) {
            $stmt->execute([$set_id, intval($sid)]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Prerequisites created successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getPrereqSets() {
    global $pdo;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS prerequisite_sets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(100) NOT NULL UNIQUE,
            major_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        try { $pdo->exec("ALTER TABLE prerequisite_sets ADD COLUMN major_id INT NOT NULL DEFAULT 0"); } catch (Exception $e) {}
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS prerequisite_set_subjects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            set_id INT NOT NULL,
            subject_id INT NOT NULL,
            FOREIGN KEY (set_id) REFERENCES prerequisite_sets(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
        )");
        
        $stmt = $pdo->query("SELECT ps.*, m.display_name as major_name FROM prerequisite_sets ps LEFT JOIN majors m ON ps.major_id = m.id ORDER BY m.display_name ASC, ps.code ASC");
        $sets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sets as &$set) {
            $stmt = $pdo->prepare("SELECT s.* FROM subjects s 
                JOIN prerequisite_set_subjects pss ON s.id = pss.subject_id 
                WHERE pss.set_id = ?");
            $stmt->execute([$set['id']]);
            $set['subjects'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $set['subject_count'] = count($set['subjects']);
            
            if (!empty($set['target_subject_id'])) {
                $stmt2 = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
                $stmt2->execute([$set['target_subject_id']]);
                $target = $stmt2->fetch(PDO::FETCH_ASSOC);
                $set['target_subject'] = $target;
            } else {
                $set['target_subject'] = null;
            }
        }
        
        echo json_encode(['success' => true, 'sets' => $sets]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deletePrereqSet() {
    global $pdo;
    $set_id = isset($_POST['set_id']) ? intval($_POST['set_id']) : 0;
    
    if ($set_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid set ID']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM prerequisite_sets WHERE id = ?");
        $stmt->execute([$set_id]);
        echo json_encode(['success' => true, 'message' => 'Prerequisites set deleted']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getMajors() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM majors ORDER BY sort_order, display_name");
        $majors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($majors as &$major) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM major_subjects WHERE major_id = ?");
            $stmt->execute([$major['id']]);
            $major['subject_count'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE major_id = ?");
            $stmt->execute([$major['id']]);
            $major['student_count'] = $stmt->fetchColumn();
        }
        
        echo json_encode(['success' => true, 'majors' => $majors]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addMajor() {
    global $pdo;
    $major_name = isset($_POST['major_name']) ? trim($_POST['major_name']) : '';
    $display_name = isset($_POST['display_name']) ? trim($_POST['display_name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $icon_class = isset($_POST['icon_class']) ? trim($_POST['icon_class']) : 'fas fa-building';
    $gradient_from = isset($_POST['gradient_from']) ? trim($_POST['gradient_from']) : '#d4a843';
    $gradient_to = isset($_POST['gradient_to']) ? trim($_POST['gradient_to']) : '#e8c768';
    
    if (empty($major_name) || empty($display_name)) {
        echo json_encode(['success' => false, 'message' => 'Major name and display name are required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO majors (major_name, display_name, description, icon_class, gradient_from, gradient_to) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$major_name, $display_name, $description, $icon_class, $gradient_from, $gradient_to]);
        $major_id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Major added successfully', 'major_id' => $major_id]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateMajor() {
    global $pdo;
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $major_name = isset($_POST['major_name']) ? trim($_POST['major_name']) : '';
    $display_name = isset($_POST['display_name']) ? trim($_POST['display_name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $icon_class = isset($_POST['icon_class']) ? trim($_POST['icon_class']) : 'fas fa-building';
    $gradient_from = isset($_POST['gradient_from']) ? trim($_POST['gradient_from']) : '#d4a843';
    $gradient_to = isset($_POST['gradient_to']) ? trim($_POST['gradient_to']) : '#e8c768';
    $is_active = isset($_POST['is_active']) ? boolval($_POST['is_active']) : true;
    
    if ($id <= 0 || empty($major_name) || empty($display_name)) {
        echo json_encode(['success' => false, 'message' => 'Invalid major ID or missing required fields']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE majors SET major_name = ?, display_name = ?, description = ?, icon_class = ?, gradient_from = ?, gradient_to = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$major_name, $display_name, $description, $icon_class, $gradient_from, $gradient_to, $is_active, $id]);
        echo json_encode(['success' => true, 'message' => 'Major updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteMajor() {
    global $pdo;
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid major ID']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM majors WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Major deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getSubjects() {
    global $pdo;
    $major_id = isset($_POST['major_id']) ? intval($_POST['major_id']) : 0;
    
    if ($major_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid major ID']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, ms.year_level, ms.semester, ms.is_required, ms.is_prerequisite, ms.prerequisite_for, ms.sort_order
            FROM subjects s
            LEFT JOIN major_subjects ms ON s.id = ms.subject_id AND ms.major_id = ?
            WHERE s.is_active = 1
            ORDER BY ms.sort_order, s.subject_name
        ");
        $stmt->execute([$major_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'subjects' => $subjects]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getAllSubjects() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM subjects ORDER BY subject_name");
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'subjects' => $subjects]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addSubject() {
    global $pdo;
    $subject_code = isset($_POST['subject_code']) ? trim($_POST['subject_code']) : '';
    $subject_name = isset($_POST['subject_name']) ? trim($_POST['subject_name']) : '';
    $units = isset($_POST['units']) ? floatval($_POST['units']) : 3.0;
    $year_level = isset($_POST['default_year_level']) ? trim($_POST['default_year_level']) : '1st Year';
    $semester = isset($_POST['default_semester']) ? trim($_POST['default_semester']) : '1st Semester';
    $prerequisite = isset($_POST['prerequisite']) ? trim($_POST['prerequisite']) : '';
    
    if (empty($subject_code) || empty($subject_name)) {
        echo json_encode(['success' => false, 'message' => 'Subject code and name are required']);
        return;
    }
    
    try {
        // Check for duplicate subject code first
        $check = $pdo->prepare("SELECT id FROM subjects WHERE subject_code = ?");
        $check->execute([$subject_code]);
        if ($check->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Error: Subject code already exists. Please use a different subject code.']);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name, units, default_year_level, semester, prerequisite) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$subject_code, $subject_name, $units, $year_level, $semester, $prerequisite]);
        $subject_id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Subject added successfully', 'subject_id' => $subject_id]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo json_encode(['success' => false, 'message' => 'Error: Subject code already exists. Please use a different subject code.']);
        } else {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

function updateSubject() {
    global $pdo;
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $subject_code = isset($_POST['subject_code']) ? trim($_POST['subject_code']) : '';
    $subject_name = isset($_POST['subject_name']) ? trim($_POST['subject_name']) : '';
    $units = isset($_POST['units']) ? floatval($_POST['units']) : 3.0;
    $year_level = isset($_POST['default_year_level']) ? trim($_POST['default_year_level']) : '1st Year';
    $semester = isset($_POST['default_semester']) ? trim($_POST['default_semester']) : '1st Semester';
    $prerequisite = isset($_POST['prerequisite']) ? trim($_POST['prerequisite']) : '';
    
    if ($id <= 0 || empty($subject_code) || empty($subject_name)) {
        echo json_encode(['success' => false, 'message' => 'Invalid subject ID or missing required fields']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE subjects SET subject_code = ?, subject_name = ?, units = ?, default_year_level = ?, semester = ?, prerequisite = ? WHERE id = ?");
        $stmt->execute([$subject_code, $subject_name, $units, $year_level, $semester, $prerequisite, $id]);
        echo json_encode(['success' => true, 'message' => 'Subject updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteSubject() {
    global $pdo;
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid subject ID']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Subject deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getMajorSubjects() {
    global $pdo;
    $major_id = isset($_POST['major_id']) ? intval($_POST['major_id']) : 0;
    
    if ($major_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid major ID']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, ms.year_level, ms.semester, ms.is_required, ms.is_prerequisite, ms.sort_order
            FROM subjects s
            INNER JOIN major_subjects ms ON s.id = ms.subject_id
            WHERE ms.major_id = ?
            ORDER BY ms.sort_order, ms.year_level, ms.semester
        ");
        $stmt->execute([$major_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $prerequisites = [];
        foreach ($subjects as $subject) {
            if ($subject['is_prerequisite']) {
                $prerequisites[] = $subject;
            }
        }
        
        echo json_encode(['success' => true, 'subjects' => $subjects, 'prerequisites' => $prerequisites]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addMajorSubject() {
    global $pdo;
    $major_id = isset($_POST['major_id']) ? intval($_POST['major_id']) : 0;
    $subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
    $year_level = isset($_POST['year_level']) ? trim($_POST['year_level']) : '';
    $semester = isset($_POST['semester']) ? trim($_POST['semester']) : '';
    $is_required = isset($_POST['is_required']) ? boolval($_POST['is_required']) : true;
    $is_prerequisite = isset($_POST['is_prerequisite']) ? boolval($_POST['is_prerequisite']) : false;
    
    if ($major_id <= 0 || $subject_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid major or subject ID']);
        return;
    }
    
    // If year_level or semester not provided, get from subject defaults
    try {
        $stmt = $pdo->prepare("SELECT default_year_level, default_semester FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($subject) {
            $year_level = $year_level ?: ($subject['default_year_level'] ?: '1st Year');
            $semester = $semester ?: ($subject['default_semester'] ?: '1st Semester');
        } else {
            $year_level = $year_level ?: '1st Year';
            $semester = $semester ?: '1st Semester';
        }
    } catch (PDOException $e) {
        $year_level = $year_level ?: '1st Year';
        $semester = $semester ?: '1st Semester';
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO major_subjects (major_id, subject_id, year_level, semester, is_required, is_prerequisite) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$major_id, $subject_id, $year_level, $semester, $is_required, $is_prerequisite]);
        echo json_encode(['success' => true, 'message' => 'Subject added to major successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function removeMajorSubject() {
    global $pdo;
    $major_id = isset($_POST['major_id']) ? intval($_POST['major_id']) : 0;
    $subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
    
    if ($major_id <= 0 || $subject_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid major or subject ID']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM major_subjects WHERE major_id = ? AND subject_id = ?");
        $stmt->execute([$major_id, $subject_id]);
        echo json_encode(['success' => true, 'message' => 'Subject removed from major successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateMajorSubject() {
    global $pdo;
    $major_id = isset($_POST['major_id']) ? intval($_POST['major_id']) : 0;
    $subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
    $year_level = isset($_POST['year_level']) ? trim($_POST['year_level']) : '1st Year';
    $semester = isset($_POST['semester']) ? trim($_POST['semester']) : '1st Semester';
    $is_required = isset($_POST['is_required']) ? boolval($_POST['is_required']) : true;
    $is_prerequisite = isset($_POST['is_prerequisite']) ? boolval($_POST['is_prerequisite']) : false;
    
    if ($major_id <= 0 || $subject_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid major or subject ID']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE major_subjects SET year_level = ?, semester = ?, is_required = ?, is_prerequisite = ? WHERE major_id = ? AND subject_id = ?");
        $stmt->execute([$year_level, $semester, $is_required, $is_prerequisite, $major_id, $subject_id]);
        echo json_encode(['success' => true, 'message' => 'Subject updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateMajorSubjectFlag() {
    global $pdo;
    $major_id = isset($_POST['major_id']) ? intval($_POST['major_id']) : 0;
    $subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
    $is_prerequisite = isset($_POST['is_prerequisite']) ? boolval($_POST['is_prerequisite']) : false;
    
    if ($major_id <= 0 || $subject_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid major or subject ID']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE major_subjects SET is_prerequisite = ? WHERE major_id = ? AND subject_id = ?");
        $stmt->execute([$is_prerequisite, $major_id, $subject_id]);
        echo json_encode(['success' => true, 'message' => $is_prerequisite ? 'Subject marked as prerequisite' : 'Prerequisite status removed']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
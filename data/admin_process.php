<?php
error_reporting(0);
ini_set('display_errors', 0);

function jsonResponse($data) {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// Start session with consistent cookie path
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/config.php';

// Ensure admin_promotions table exists (no status column)
if ($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_promotions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            instructor_id INT NOT NULL,
            promoted_to VARCHAR(50) NOT NULL,
            promoted_by INT NOT NULL,
            promotion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (PDOException $e) {
        // ignore
    }
}

// Handle instructor registration (public)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_instructor') {
    header('Content-Type: application/json');

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }

    if (!preg_match('/^(09|\+639)\d{9}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Invalid Philippine phone number']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM instructors WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email already registered']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO instructors (first_name, middle_name, last_name, suffix, email, phone, password, position, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Instructor', 'on duty')");
            $stmt->execute([$first_name, $middle_name ?: null, $last_name, $suffix ?: null, $email, $phone, $hashed_password]);

            echo json_encode(['success' => true, 'message' => 'Registration successful! You can now login.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Registration failed.']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Registration successful! (Demo Mode)']);
    }
    exit;
}

// Admin-only actions
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../Door/login.php');
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'add_instructor') {
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $position = $_POST['position'] ?? 'Instructor';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password)) {
        header('Location: ../Door/admin/dashboard.php?page=add_program_head&error=' . urlencode('Please fill in all required fields'));
        exit;
    }

    if ($password !== $confirm_password) {
        header('Location: ../Door/admin/dashboard.php?page=add_program_head&error=' . urlencode('Passwords do not match'));
        exit;
    }

    if (strlen($password) < 6) {
        header('Location: ../Door/admin/dashboard.php?page=add_program_head&error=' . urlencode('Password must be at least 6 characters'));
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO instructors (first_name, middle_name, last_name, suffix, email, phone, password, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $middle_name ?: null, $last_name, $suffix ?: null, $email, $phone, $hashed_password, $position]);
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor added successfully!'));
        } catch (PDOException $e) {
            header('Location: ../Door/admin/dashboard.php?page=add_program_head&error=' . urlencode('Email already exists'));
        }
    } else {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor added successfully! (Demo Mode)'));
    }
    exit;

} elseif ($action === 'add_student') {
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $major_id = intval($_POST['major_id'] ?? 0);
    $year_level = $_POST['year_level'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($student_id) || empty($email) || empty($major_id) || empty($year_level)) {
        header('Location: ../Door/admin/dashboard.php?page=student_enrollment&error=' . urlencode('Please fill in all required fields'));
        exit;
    }

    $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
    $gradient_from = '#3b82f6';
    $gradient_to = '#60a5fa';

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO students (first_name, middle_name, last_name, suffix, student_id, email, major_id, year_level, avatar_initials, avatar_gradient_from, avatar_gradient_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $middle_name ?: null, $last_name, $suffix ?: null, $student_id, $email, $major_id, $year_level, $initials, $gradient_from, $gradient_to]);
            header('Location: ../Door/admin/dashboard.php?page=student_enrollment&success=' . urlencode('Student enrolled successfully!'));
        } catch (PDOException $e) {
            header('Location: ../Door/admin/dashboard.php?page=student_enrollment&error=' . urlencode('Email or Student ID already exists'));
        }
    } else {
        header('Location: ../Door/admin/dashboard.php?page=student_enrollment&success=' . urlencode('Student enrolled successfully! (Demo Mode)'));
    }
    exit;

} elseif ($action === 'add_program_head') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $position = $_POST['position'] ?? 'Program Head';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password)) {
        header('Location: ../Door/admin/dashboard.php?page=add_program_head&error=' . urlencode('Please fill in all required fields'));
        exit;
    }

    if ($password !== $confirm_password) {
        header('Location: ../Door/admin/dashboard.php?page=add_program_head&error=' . urlencode('Passwords do not match'));
        exit;
    }

    if (strlen($password) < 6) {
        header('Location: ../Door/admin/dashboard.php?page=add_program_head&error=' . urlencode('Password must be at least 6 characters'));
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO program_heads (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $email, $hashed_password]);
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Program head added successfully!'));
        } catch (PDOException $e) {
            header('Location: ../Door/admin/dashboard.php?page=add_program_head&error=' . urlencode('Email already exists'));
        }
    } else {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Program head added successfully! (Demo Mode)'));
    }
    exit;

} elseif ($action === 'remove_program_head') {
    $id = $_GET['id'] ?? 0;
    if (empty($id)) {
        header('Location: ../Door/admin/dashboard.php?page=remove_program_head&error=' . urlencode('Invalid program head ID'));
        exit;
    }
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM program_heads WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Program head removed successfully!'));
        } catch (PDOException $e) {
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Failed to remove program head'));
        }
    } else {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Program head removed successfully! (Demo Mode)'));
    }
    exit;

} elseif ($action === 'quick_add') {
    $type = $_GET['type'] ?? '';
    if ($type === 'demo' && $pdo) {
        $demo_instructors = [
            ['John', 'Harris', 'john.harris@cjcm.edu'],
            ['Sarah', 'Miller', 'sarah.miller@cjcm.edu'],
            ['Robert', 'Wilson', 'robert.wilson@cjcm.edu']
        ];
        $hashed_password = password_hash('password123', PASSWORD_DEFAULT);
        foreach ($demo_instructors as $instructor) {
            try {
                $stmt = $pdo->prepare("INSERT INTO instructors (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$instructor[0], $instructor[1], $instructor[2], $hashed_password]);
            } catch (PDOException $e) {}
        }
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Demo instructors added successfully!'));
    } else {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads');
    }
    exit;

} elseif ($action === 'remove_instructor') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Invalid instructor ID'));
        exit;
    }
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT email FROM instructors WHERE id = ?");
            $stmt->execute([$id]);
            $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($instructor && !empty($instructor['email'])) {
                $stmt = $pdo->prepare("SELECT 1 FROM admin_promotions WHERE instructor_id = ? AND promoted_to = 'program_head'");
                $stmt->execute([$id]);
                if ($stmt->fetch()) {
                    $pdo->prepare("DELETE FROM admin_promotions WHERE instructor_id = ? AND promoted_to = 'program_head'")->execute([$id]);
                    $pdo->prepare("DELETE FROM program_heads WHERE email = ?")->execute([trim($instructor['email'])]);
                }
            }
            $stmt = $pdo->prepare("DELETE FROM instructors WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor removed successfully!'));
        } catch (PDOException $e) {
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Failed to remove instructor'));
        }
    } else {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor removed successfully! (Demo Mode)'));
    }
    exit;

} elseif ($action === 'edit_instructor') {
    $id = $_POST['id'] ?? 0;
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $position = $_POST['position'] ?? 'Instructor';

    if (empty($id) || empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Please fill in all required fields'));
        exit;
    }

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE instructors SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, email = ?, phone = ?, position = ? WHERE id = ?");
            $stmt->execute([$first_name, $middle_name ?: null, $last_name, $suffix ?: null, $email, $phone, $position, $id]);
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor updated successfully!'));
        } catch (PDOException $e) {
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Failed to update instructor'));
        }
    } else {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor updated successfully! (Demo Mode)'));
    }
    exit;

} elseif ($action === 'get_instructor') {
    $id = $_GET['id'] ?? 0;
    header('Content-Type: application/json');
    if (empty($id) || !is_numeric($id) || intval($id) <= 0) {
        echo json_encode(['error' => 'Invalid instructor ID']);
        exit;
    }
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM instructors WHERE id = ?");
            $stmt->execute([$id]);
            $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($instructor === false) {
                echo json_encode(['error' => 'Instructor not found']);
            } else {
                unset($instructor['password']);
                echo json_encode($instructor);
            }
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Database error occurred']);
        }
    } else {
        echo json_encode(['error' => 'Database connection not available']);
    }
    exit;

} elseif ($action === 'promote_instructor') {
    $instructor_id = $_POST['instructor_id'] ?? 0;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (empty($instructor_id) || empty($password)) {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Please fill in all fields'));
        exit;
    }
    if ($password !== $confirm_password) {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Passwords do not match'));
        exit;
    }
    if (strlen($password) < 6) {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Password must be at least 6 characters'));
        exit;
    }
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM instructors WHERE id = ?");
            $stmt->execute([$instructor_id]);
            $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$instructor) {
                header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Instructor not found'));
                exit;
            }
            $stmt = $pdo->prepare("SELECT id FROM admin_promotions WHERE instructor_id = ? AND promoted_to = 'program_head'");
            $stmt->execute([$instructor_id]);
            if ($stmt->fetch()) {
                header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Instructor is already a Program Head'));
                exit;
            }
            $stmt = $pdo->query("SELECT instructor_id FROM admin_promotions WHERE promoted_to = 'program_head'");
            $existing = $stmt->fetch();
            if ($existing && $existing['instructor_id'] != $instructor_id) {
                header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Another instructor is already promoted to Program Head. Remove their promotion first.'));
                exit;
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $email = trim($instructor['email']);
            $stmt = $pdo->prepare("SELECT id FROM program_heads WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Instructor is already a Program Head'));
                exit;
            }
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO program_heads (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$instructor['first_name'], $instructor['last_name'], $email, $hashed_password]);
                $promoted_by = $_SESSION['user_id'] ?? 1;
                $stmt = $pdo->prepare("INSERT INTO admin_promotions (instructor_id, promoted_to, promoted_by) VALUES (?, 'program_head', ?)");
                $stmt->execute([$instructor_id, $promoted_by]);
                $pdo->commit();
                header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor promoted to Program Head successfully!'));
            } catch (PDOException $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (PDOException $e) {
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Promotion failed: ' . $e->getMessage()));
        }
    } else {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor promoted successfully! (Demo Mode)'));
    }
    exit;

} elseif ($action === 'promote_instructor_simple') {
    $instructor_id = $_GET['id'] ?? 0;
    $promote_to = $_GET['promote_to'] ?? '';
    if (empty($instructor_id) || empty($promote_to)) {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Invalid request'));
        exit;
    }
    if ($pdo) {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS admin_promotions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                instructor_id INT NOT NULL,
                promoted_to VARCHAR(50) NOT NULL,
                promoted_by INT NOT NULL,
                promotion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            $stmt = $pdo->prepare("SELECT * FROM instructors WHERE id = ?");
            $stmt->execute([$instructor_id]);
            $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$instructor) {
                header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Instructor not found'));
                exit;
            }
            if ($promote_to === 'program_head') {
                $stmt = $pdo->query("SELECT instructor_id FROM admin_promotions WHERE promoted_to = 'program_head'");
                $existing = $stmt->fetch();
                if ($existing && $existing['instructor_id'] != $instructor_id) {
                    header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Another instructor is already promoted to Program Head. Remove their promotion first.'));
                    exit;
                }
            }
            $default_password = 'password123';
            $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
            if ($promote_to === 'program_head') {
                $stmt = $pdo->prepare("SELECT id FROM program_heads WHERE email = ?");
                $stmt->execute([$instructor['email']]);
                if ($stmt->fetch()) {
                    header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Instructor is already a Program Head'));
                    exit;
                }
                $stmt = $pdo->prepare("INSERT INTO program_heads (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$instructor['first_name'], $instructor['last_name'], $instructor['email'], $hashed_password]);
                $promoted_by = $_SESSION['user_id'] ?? 1;
                $stmt = $pdo->prepare("INSERT INTO admin_promotions (instructor_id, promoted_to, promoted_by) VALUES (?, ?, ?)");
                $stmt->execute([$instructor_id, 'program_head', $promoted_by]);
                header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor promoted to Program Head successfully! Default password: password123'));
            }
        } catch (PDOException $e) {
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Promotion failed: ' . $e->getMessage()));
        }
    } else {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor promoted successfully! (Demo Mode)'));
    }
    exit;

} elseif ($action === 'remove_promotion') {
    $instructor_id = $_GET['id'] ?? 0;
    if (empty($instructor_id)) {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Invalid request'));
        exit;
    }
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT email FROM instructors WHERE id = ?");
            $stmt->execute([$instructor_id]);
            $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($instructor) {
                $stmt = $pdo->prepare("DELETE FROM program_heads WHERE email = ?");
                $stmt->execute([$instructor['email']]);
            }
            $stmt = $pdo->prepare("DELETE FROM admin_promotions WHERE instructor_id = ? AND promoted_to = 'program_head'");
            $stmt->execute([$instructor_id]);
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Program Head access removed successfully'));
        } catch (PDOException $e) {
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Removal failed: ' . $e->getMessage()));
        }
    } else {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Promotion removed successfully! (Demo Mode)'));
    }
    exit;

} elseif ($action === 'accept_instructor') {
    $pending_id = $_GET['id'] ?? 0;
    if (empty($pending_id)) {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Invalid request'));
        exit;
    }
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM pending_instructors WHERE id = ?");
            $stmt->execute([$pending_id]);
            $pending = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$pending) {
                header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Pending registration not found'));
                exit;
            }
            $hashed_password = $pending['password'];
            $stmt = $pdo->prepare("INSERT INTO instructors (first_name, middle_name, last_name, suffix, email, password, position, status) VALUES (?, ?, ?, ?, ?, ?, 'Instructor', 'on duty')");
            $stmt->execute([
                $pending['first_name'],
                $pending['middle_name'],
                $pending['last_name'],
                $pending['suffix'],
                $pending['email'],
                $hashed_password
            ]);
            $stmt = $pdo->prepare("DELETE FROM pending_instructors WHERE id = ?");
            $stmt->execute([$pending_id]);
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor accepted successfully!'));
        } catch (PDOException $e) {
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Accept failed: ' . $e->getMessage()));
        }
    } else {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor accepted! (Demo Mode)'));
    }
    exit;

} elseif ($action === 'decline_instructor') {
    $pending_id = $_GET['id'] ?? 0;
    if (empty($pending_id)) {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Invalid request'));
        exit;
    }
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM pending_instructors WHERE id = ?");
            $stmt->execute([$pending_id]);
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Application declined successfully'));
        } catch (PDOException $e) {
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Decline failed: ' . $e->getMessage()));
        }
    } else {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Application declined! (Demo Mode)'));
    }
    exit;

} else {
    header('Location: ../Door/admin/dashboard.php');
    exit;
}
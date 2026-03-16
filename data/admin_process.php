<?php
// Start session with consistent cookie path so we can read the admin session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once 'config.php';

// Create admin_promotions table if it doesn't exist (for promoting instructors)
if ($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_promotions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            instructor_id INT NOT NULL,
            promoted_to VARCHAR(50) NOT NULL COMMENT 'program_head or admin',
            promoted_by INT NOT NULL COMMENT 'admin user_id',
            promotion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('active', 'revoked') DEFAULT 'active'
        )");
    } catch (PDOException $e) {
        // Table creation failed, continue anyway
    }
}

// Handle instructor registration (no auth required)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_instructor') {
    header('Content-Type: application/json');

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $employee_id = trim($_POST['employee_id'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation (required: first_name, last_name, email, employee_id, department, password)
    if (empty($first_name) || empty($last_name) || empty($email) || empty($employee_id) || empty($department) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit;
    }

    // Department must be one of the allowed values (matches instructors table)
    $allowed_departments = ['Operational Management', 'Financial Management', 'Marketing Management'];
    if (!in_array($department, $allowed_departments)) {
        echo json_encode(['success' => false, 'message' => 'Invalid department selected']);
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

            $stmt = $pdo->prepare("SELECT id FROM instructors WHERE employee_id = ?");
            $stmt->execute([$employee_id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Employee ID already registered']);
                exit;
            }

            // Insert into instructors table (columns match data.sql)
            $stmt = $pdo->prepare("INSERT INTO instructors (first_name, middle_name, last_name, suffix, email, employee_id, department, password, position, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Instructor', 'active')");
            $stmt->execute([$first_name, $middle_name ?: null, $last_name, $suffix ?: null, $email, $employee_id, $department, $hashed_password]);

            echo json_encode(['success' => true, 'message' => 'Registration successful! You can now login.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Registration successful! (Demo Mode)']);
    }
    exit;
}

// Check if user is admin for other actions
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../Door/login.php');
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'add_instructor') {
    // Handle add instructor form submission
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? 'Instructor';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($department) || empty($password)) {
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

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Generate employee ID
    $employee_id = 'EMP' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO instructors (first_name, middle_name, last_name, suffix, email, password, department, employee_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $middle_name, $last_name, $suffix, $email, $hashed_password, $department, $employee_id]);
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor added successfully!'));
        } catch (PDOException $e) {
            header('Location: ../Door/admin/dashboard.php?page=add_program_head&error=' . urlencode('Email already exists'));
        }
    } else {
        // For demo, just redirect with success
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor added successfully! (Demo Mode)'));
    }
    exit;

} elseif ($action === 'add_program_head') {
    // Handle add program head form submission
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? 'Program Head';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($department) || empty($password)) {
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

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO program_heads (first_name, last_name, email, password, department) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $email, $hashed_password, $department]);
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Program head added successfully!'));
        } catch (PDOException $e) {
            header('Location: ../Door/admin/dashboard.php?page=add_program_head&error=' . urlencode('Email already exists'));
        }
    } else {
        // For demo, just redirect with success
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
        // For demo, just redirect with success
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Program head removed successfully! (Demo Mode)'));
    }
    exit;

} elseif ($action === 'quick_add') {
    $type = $_GET['type'] ?? '';

    if ($type === 'demo') {
        // Add demo instructors if database is available
        if ($pdo) {
            $demo_instructors = [
                ['John', 'Harris', 'john.harris@cjcm.edu', 'Operational Management'],
                ['Sarah', 'Miller', 'sarah.miller@cjcm.edu', 'Financial Management'],
                ['Robert', 'Wilson', 'robert.wilson@cjcm.edu', 'Marketing Management']
            ];
            
            $hashed_password = password_hash('password123', PASSWORD_DEFAULT);
            
            foreach ($demo_instructors as $instructor) {
                $employee_id = 'EMP' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                try {
                    $stmt = $pdo->prepare("INSERT INTO instructors (first_name, last_name, email, password, department, employee_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$instructor[0], $instructor[1], $instructor[2], $hashed_password, $instructor[3], $employee_id]);
                } catch (PDOException $e) {
                    // Skip if already exists
                }
            }
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
            // If this instructor is the current Program Head, remove promotion first (only one program head allowed)
            $stmt = $pdo->prepare("SELECT email FROM instructors WHERE id = ?");
            $stmt->execute([$id]);
            $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($instructor && !empty($instructor['email'])) {
                $stmt = $pdo->prepare("SELECT 1 FROM admin_promotions WHERE instructor_id = ? AND promoted_to = 'program_head' AND status = 'active'");
                $stmt->execute([$id]);
                if ($stmt->fetch()) {
                    $pdo->prepare("UPDATE admin_promotions SET status = 'revoked' WHERE instructor_id = ? AND promoted_to = 'program_head' AND status = 'active'")->execute([$id]);
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
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? 'Instructor';

    if (empty($id) || empty($first_name) || empty($last_name) || empty($email) || empty($department)) {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Please fill in all required fields'));
        exit;
    }

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE instructors SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, email = ?, department = ?, position = ? WHERE id = ?");
            $stmt->execute([$first_name, $middle_name, $last_name, $suffix, $email, $department, $position, $id]);
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

    // Validate that id is a positive integer
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
                // Remove password from response for security
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
    $promote_to = $_POST['promote_to'] ?? ''; // 'program_head' or 'admin'
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($instructor_id) || empty($promote_to) || empty($password)) {
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

    if (!in_array($promote_to, ['program_head', 'admin'])) {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Invalid promotion type'));
        exit;
    }

    if ($pdo) {
        try {
            // Get instructor details
            $stmt = $pdo->prepare("SELECT * FROM instructors WHERE id = ?");
            $stmt->execute([$instructor_id]);
            $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$instructor) {
                header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Instructor not found'));
                exit;
            }

            // Check if there's already a program_head promotion
            if ($promote_to === 'program_head') {
                $stmt = $pdo->query("SELECT instructor_id FROM admin_promotions WHERE promoted_to = 'program_head' AND status = 'active'");
                $existing = $stmt->fetch();
                if ($existing && $existing['instructor_id'] != $instructor_id) {
                    header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Another instructor is already promoted to Program Head. Remove their promotion first.'));
                    exit;
                }
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            if ($promote_to === 'program_head') {
                $email = trim($instructor['email']);
                // Check if already a program_head (by email - they must use Program Head login)
                $stmt = $pdo->prepare("SELECT id FROM program_heads WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Instructor is already a Program Head'));
                    exit;
                }

                $pdo->beginTransaction();
                try {
                    // 1. Add to program_heads so they can login as Program Head (email + new password)
                    $stmt = $pdo->prepare("INSERT INTO program_heads (first_name, last_name, email, password, department) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $instructor['first_name'],
                        $instructor['last_name'] ?? '',
                        $email,
                        $hashed_password,
                        $instructor['department'] ?? null
                    ]);

                    // 2. Record promotion so All Instructors table shows Role = Program Head
                    $promoted_by = (int)($_SESSION['user_id'] ?? 1);
                    $stmt = $pdo->prepare("INSERT INTO admin_promotions (instructor_id, promoted_to, promoted_by) VALUES (?, 'program_head', ?)");
                    $stmt->execute([(int)$instructor_id, $promoted_by]);

                    // 3. Update instructor position so Role column shows Program Head
                    $stmt = $pdo->prepare("UPDATE instructors SET position = 'Program Head' WHERE id = ?");
                    $stmt->execute([(int)$instructor_id]);

                    $pdo->commit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Promotion failed: ' . $e->getMessage()));
                    exit;
                }

                header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor promoted to Program Head successfully! They can now log in as Program Head with the new password.'));
            } else {
                // Check if already an admin
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
                $stmt->execute([$instructor['email']]);
                if ($stmt->fetch()) {
                    header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Instructor is already an Admin'));
                    exit;
                }

                // Add to admins table
                $stmt = $pdo->prepare("INSERT INTO admins (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, 'admin')");
                $stmt->execute([$instructor['first_name'], $instructor['last_name'], $instructor['email'], $hashed_password]);

                // Record promotion
                $stmt = $pdo->prepare("INSERT INTO admin_promotions (instructor_id, promoted_to, promoted_by) VALUES (?, ?, ?)");
                $stmt->execute([$instructor_id, 'admin', $_SESSION['user_id']]);

                header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor promoted to Admin successfully!'));
            }
        } catch (PDOException $e) {
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Promotion failed: ' . $e->getMessage()));
        }
    } else {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Instructor promoted successfully! (Demo Mode)'));
    }
    exit;

} elseif ($action === 'remove_promotion') {
    $instructor_id = (int)($_GET['id'] ?? 0);

    if ($instructor_id <= 0) {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Invalid instructor ID'));
        exit;
    }

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT email FROM instructors WHERE id = ?");
            $stmt->execute([$instructor_id]);
            $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$instructor || empty($instructor['email'])) {
                header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Instructor not found'));
                exit;
            }
            $email = trim($instructor['email']);

            $pdo->beginTransaction();
            try {
                // 1. Revoke promotion so Role goes back to Instructor in UI
                $stmt = $pdo->prepare("UPDATE admin_promotions SET status = 'revoked' WHERE instructor_id = ? AND promoted_to = 'program_head' AND status = 'active'");
                $stmt->execute([$instructor_id]);

                // 2. Remove Program Head login
                $stmt = $pdo->prepare("DELETE FROM program_heads WHERE email = ?");
                $stmt->execute([$email]);

                // 3. Reset instructor position back to Instructor
                $stmt = $pdo->prepare("UPDATE instructors SET position = 'Instructor' WHERE id = ?");
                $stmt->execute([$instructor_id]);

                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                throw $e;
            }

            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Program Head promotion removed successfully!'));
        } catch (PDOException $e) {
            header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Failed to remove promotion'));
        }
    } else {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&success=' . urlencode('Promotion removed successfully! (Demo Mode)'));
    }
    exit;

} elseif ($action === 'promote_instructor_simple') {
    // Simple promotion without password modal - uses default password
    $instructor_id = $_GET['id'] ?? 0;
    $promote_to = $_GET['promote_to'] ?? '';

    if (empty($instructor_id) || empty($promote_to)) {
        header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Invalid request'));
        exit;
    }

    if ($pdo) {
        try {
            // First, create admin_promotions table if it doesn't exist
            $pdo->exec("CREATE TABLE IF NOT EXISTS admin_promotions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                instructor_id INT NOT NULL,
                promoted_to VARCHAR(50) NOT NULL,
                promoted_by INT NOT NULL,
                promotion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('active', 'revoked') DEFAULT 'active'
            )");
            
            // Get instructor details
            $stmt = $pdo->prepare("SELECT * FROM instructors WHERE id = ?");
            $stmt->execute([$instructor_id]);
            $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$instructor) {
                header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Instructor not found'));
                exit;
            }

            // Check if there's already a program_head promotion
            if ($promote_to === 'program_head') {
                $stmt = $pdo->query("SELECT instructor_id FROM admin_promotions WHERE promoted_to = 'program_head' AND status = 'active'");
                $existing = $stmt->fetch();
                if ($existing && $existing['instructor_id'] != $instructor_id) {
                    header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Another instructor is already promoted to Program Head. Remove their promotion first.'));
                    exit;
                }
            }

            // Use default password
            $default_password = 'password123';
            $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

            if ($promote_to === 'program_head') {
                // Check if already a program_head
                $stmt = $pdo->prepare("SELECT id FROM program_heads WHERE email = ?");
                $stmt->execute([$instructor['email']]);
                if ($stmt->fetch()) {
                    header('Location: ../Door/admin/dashboard.php?page=manage_program_heads&error=' . urlencode('Instructor is already a Program Head'));
                    exit;
                }

                // Add to program_heads table
                $stmt = $pdo->prepare("INSERT INTO program_heads (first_name, last_name, email, password, department) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$instructor['first_name'], $instructor['last_name'], $instructor['email'], $hashed_password, $instructor['department']]);

                // Record promotion
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

} else {
    header('Location: ../Door/admin/dashboard.php');
    exit;
}

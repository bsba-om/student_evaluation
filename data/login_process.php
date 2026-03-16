<?php
header('Content-Type: application/json');
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($role) || empty($email) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all fields'
        ]);
        exit;
    }

    $allowed_roles = ['admin', 'program_head', 'instructor'];
    if (!in_array($role, $allowed_roles)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid role selected'
        ]);
        exit;
    }
   if (!$pdo) {
        demoLogin($role, $email, $password);
        exit;
    }

    try {   $table = match($role) {
        'admin' => 'admins',
        'program_head' => 'program_heads',
        'instructor' => 'instructors',
        default => 'program_heads'
    };
        
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {       $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $role;
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
      $redirect = match($role) {
            'admin' => '../Door/admin/dashboard.php',
            'program_head' => '../Door/program_head/dashboard.php',
            'instructor' => '../Door/instructor/dashboard.php',
            default => '../Door/program_head/dashboard.php'
        };

            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => $redirect
            ]);
        } else {          demoLogin($role, $email, $password);
        }
    } catch (Exception $e) {      demoLogin($role, $email, $password);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

function demoLogin($role, $email, $password) {    $demo_credentials = [
        'admin' => [
            'email' => 'admin@cjcm.edu',
            'password' => 'password123'
        ],
        'program_head' => [
            'email' => 'head@test.com',
            'password' => 'password123'
        ],
        'instructor' => [
            'email' => 'teacher@test.com',
            'password' => 'password123'
        ]
    ];

    $demo_email = $demo_credentials[$role]['email'] ?? '';
    $demo_password = $demo_credentials[$role]['password'] ?? '';

    if ($email === $demo_email && $password === $demo_password) {
        // Set session
        $_SESSION['user_id'] = 1;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_name'] = match($role) {
            'admin' => 'Administrator',
            'program_head' => 'John Head',
            'instructor' => 'Jane Teacher',
            default => 'User'
        };

        // Redirect based on role
        $redirect = match($role) {
            'admin' => '../Door/admin/dashboard.php',
            'program_head' => '../Door/program_head/dashboard.php',
            'instructor' => '../Door/instructor/dashboard.php',
            default => '../Door/program_head/dashboard.php'
        };

        echo json_encode([
            'success' => true,
            'message' => 'Login successful (Demo Mode)',
            'redirect' => $redirect
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password. Try: ' . $demo_email . ' / ' . $demo_password
        ]);
    }
}

 <?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

function jsonResponse($data) {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

require_once __DIR__ . '/config.php';

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
        jsonResponse([
            'success' => false,
            'message' => 'Please fill in all fields'
        ]);
    }

    $allowed_roles = ['admin', 'program_head', 'instructor'];
    if (!in_array($role, $allowed_roles)) {
        jsonResponse([
            'success' => false,
            'message' => 'Invalid role selected'
        ]);
    }
    if (!$pdo) {
        jsonResponse([
            'success' => false,
            'message' => 'Unable to connect to the database. Please try again later.'
        ]);
    }

    try {
        $table = match($role) {
            'admin' => 'admins',
            'program_head' => 'program_heads',
            'instructor' => 'instructors',
            default => 'program_heads'
        };
        
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

         if ($user && password_verify($password, $user['password'])) {
            // Check if account is approved (for instructors)
            if ($role === 'instructor') {
                // Only block if status is 'pending' or 'rejected' (not approved)
                if (isset($user['status']) && in_array($user['status'], ['pending', 'rejected'])) {
                    jsonResponse([
                        'success' => false,
                        'message' => 'Your account is not yet approved. Please contact your administrator.'
                    ]);
                }
            }
             
             // Check if admin is using demo account and requires setup
             if ($role === 'admin' && isset($user['is_demo']) && $user['is_demo']) {
                 $_SESSION['admin_requires_setup'] = true;
             }
             
             $_SESSION['user_id'] = $user['id'];
             $_SESSION['user_email'] = $user['email'];
             $_SESSION['user_role'] = $role;
             $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            $redirect = match($role) {
                'admin' => 'Door/admin/dashboard.php',
                'program_head' => 'Door/program_head/dashboard.php',
                'instructor' => 'Door/instructor/dashboard.php',
                default => 'Door/program_head/dashboard.php'
            };

            jsonResponse([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => $redirect
            ]);
        } else {
            // User exists but wrong password
            if ($user) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Invalid password. Please try again.'
                ]);
            } else {
                // Email not found in this role's table
                jsonResponse([
                    'success' => false,
                    'message' => 'No account found with this email for the selected role. Please check your email or register first.'
                ]);
            }
        }
    } catch (Exception $e) {
        error_log('Login error: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => 'An error occurred during login. Please try again.'
        ]);
    }
} else {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}


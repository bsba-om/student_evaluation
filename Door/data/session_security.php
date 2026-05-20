<?php
session_start();

/**
 * Check if user is authenticated and has the required role
 * Redirects to login page if not authorized
 * 
 * @param string $required_role The required role (admin, program_head, instructor)
 * @param string $redirect_path Path to redirect to (usually login page)
 */
function check_auth($required_role, $redirect_path = '../login.php') {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        header("Location: " . $redirect_path);
        exit;
    }
    
    // Check role
    if ($_SESSION['user_role'] !== $required_role) {
        // Store a message and redirect
        $_SESSION['auth_message'] = "Access restricted. You don't have permission to access this page.";
        header("Location: " . $redirect_path);
        exit;
    }
    
    // Check account approval status (only for non-admin roles)
    if ($required_role !== 'admin') {
        if (!is_account_approved($_SESSION['user_id'], $required_role)) {
            $_SESSION['auth_message'] = "Your account is not yet approved. Please contact your administrator.";
            header("Location: " . $redirect_path);
            exit;
        }
    }
}

/**
 * Check if an instructor or program head account is approved
 * 
 * @param int $user_id The user ID
 * @param string $role The user role
 * @return bool True if approved, false otherwise
 */
function is_account_approved($user_id, $role) {
    require_once __DIR__ . '/config.php';
    
    try {
        if ($role === 'instructor') {
            $stmt = $pdo->prepare("SELECT status FROM instructors WHERE id = ?");
        } elseif ($role === 'program_head') {
            $stmt = $pdo->prepare("
                SELECT status FROM instructors i 
                INNER JOIN admin_promotions ap ON i.id = ap.instructor_id 
                WHERE ap.instructor_id = ? AND ap.promoted_to = 'program_head' 
                LIMIT 1
            ");
        } else {
            return true; // Admin is always approved
        }
        
        if (!$stmt) {
            return true; // If prepare fails, allow access (fail open for now)
        }
        
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            // User not found in expected table, check alternative for program_head
            if ($role === 'program_head') {
                $stmt = $pdo->prepare("SELECT status FROM program_heads WHERE id = ?");
                $stmt->execute([$user_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
        
        if ($result) {
            // Check if status is 'active' (or equivalent)
            $status = $result['status'] ?? 'active';
            return strtolower($status) === 'active';
        }
        
        // No record found, deny access
        return false;
        
    } catch (PDOException $e) {
        // On database error, deny access (fail safe)
        error_log("Auth check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user has the correct role for the current page
 * Returns an array with access status and information
 * @param string $required_role - The role required to access the page
 * @return array - ['allowed' => boolean, 'current_role' => string, 'required_role' => string, 'message' => string]
 */
function check_role_access($required_role) {
    if (!isset($_SESSION['user_role'])) {
        return [
            'allowed'   => false,
            'current_role' => 'guest',
            'required_role' => $required_role,
            'message'   => 'You need to login to access this page.',
            'action'    => 'login'
        ];
    }

    if ($_SESSION['user_role'] !== $required_role) {
        $role_names = [
            'admin'          => 'Administrator',
            'program_head'   => 'Program Head',
            'instructor'     => 'Instructor'
        ];
        $current_role_name  = $role_names[$_SESSION['user_role']] ?? $_SESSION['user_role'];
        $required_role_name = $role_names[$required_role] ?? $required_role;

        return [
            'allowed'       => false,
            'current_role'  => $_SESSION['user_role'],
            'required_role' => $required_role,
            'message'       => "You are currently logged in as $current_role_name. Please logout and login as $required_role_name.",
            'action'        => 'role_mismatch'
        ];
    }

    return [
        'allowed'       => true,
        'current_role'  => $_SESSION['user_role'],
        'required_role' => $required_role,
        'message'       => '',
        'action'        => 'access_granted'
    ];
}

/**
 * Get the current user's profile data
 * Useful for displaying user info in the UI
 */
function get_current_user_info() {
    if (isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/config.php';
        $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM instructors WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

/**
 * Check role access without redirecting (returns boolean)
 */
function has_role_access($required_role) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    return $_SESSION['user_role'] === $required_role;
}
?>
<?php
// Start session to check login status
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Check if user is already logged in
$is_logged_in = isset($_SESSION['user_role']) && !empty($_SESSION['user_role']);
$user_role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? 'User';

// Determine dashboard URL based on role
$dashboard_url = '';
$role_label = '';
if ($is_logged_in) {
    $dashboard_url = match($user_role) {
        'admin' => './Door/admin/dashboard.php',
        'program_head' => './Door/program_head/dashboard.php',
        'instructor' => './Door/instructor/dashboard.php',
        default => './Door/login.php'
    };
    $role_label = match($user_role) {
        'admin' => 'Administrator',
        'program_head' => 'Program Head',
        'instructor' => 'Instructor',
        default => 'User'
    };
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="./media/LOGO.jpg" type="image/jpeg">
    <title>Faculty Management Evaluation System</title>
    <link rel="stylesheet" href="./css/common.css">
    <link rel="stylesheet" href="./css/landing.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="#" class="nav-brand">
                <img src="./media/nbsc_logo.png" alt="NBSC Logo" class="nav-logo">
                <img src="./media/LOGO.jpg" alt="Logo" class="nav-logo">
                <span class="nav-title">Institute For Business Management</span>
            </a>
            <ul class="nav-links" id="navLinks">
                <?php if ($is_logged_in): ?>
                    <li style="display: flex; align-items: center; gap: 8px; margin-right: 12px;">
                        <span style="color: #d4a843; font-weight: 600; font-size: 13px;">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                        </span>
                        <span style="background: rgba(212, 168, 67, 0.2); color: #b8922f; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                            <?php echo htmlspecialchars($role_label); ?>
                        </span>
                    </li>
                    <li><a href="<?php echo htmlspecialchars($dashboard_url); ?>" class="nav-login-btn" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-home"></i> Return</a></li>
                    <li><a href="./data/logout.php" class="nav-login-btn" style="background: linear-gradient(135deg, #dc2626, #b91c1c);"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="./Door/login.php" class="nav-login-btn">Login</a></li>
                <?php endif; ?>
            </ul>
            <div class="nav-toggle" id="navToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <section class="hero" id="hero">
        <div class="hero-bg">
            <img src="./media/BSBA.jpg" alt="Campus">
        </div>
        <div class="hero-overlay">
            <span class="firefly"></span>
            <span class="firefly"></span>
            <span class="firefly"></span>
            <span class="firefly"></span>
            <span class="firefly"></span>
            <span class="firefly"></span>
            <span class="firefly"></span>
            <span class="firefly"></span>
            <span class="firefly"></span>
            <span class="firefly"></span>
            <span class="firefly"></span>
            <span class="firefly"></span>
        </div>

        <div class="particles" id="particles"></div>

        <div class="hero-content">
            <span class="hero-badge">Welcome IBM</span>
            <h1 class="hero-title">
                Institute For Business Management<br>
                <span class="gold-highlight">Student Evaluation System</span>
            </h1>
            <p class="hero-subtitle">
                Empowering excellence in education through comprehensive student performance tracking, evaluation, and assessment reporting.
            </p>
           
        </div>
    </section>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <img src="./media/LOGO.jpg" alt="Logo" class="footer-logo">
                <span>Student Evaluation System</span>
            </div>
            <p>&copy; 2026 CJCM. All Rights Reserved.</p>
            <ul class="footer-links">
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
                <li><a href="#">Contact</a></li>
            </ul>
        </div>
    </footer>

    </script>
    <script type="module" src="./js/landing.js"></script>
</body>

</html>

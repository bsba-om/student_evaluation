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

// Fetch system settings from database
$system_name = "Student Evaluation System";
$system_tagline = "Empowering excellence in education through comprehensive student performance tracking, evaluation, and assessment reporting.";

// Include DB config if available
$config_path = __DIR__ . '/data/config.php';
if (file_exists($config_path)) {
    require_once $config_path;
    if (isset($pdo) && $pdo) {
        try {
            $stmt = $pdo->query("SELECT system_name, system_tagline FROM admins ORDER BY id LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($settings) {
                if (!empty($settings['system_name'])) {
                    $system_name = $settings['system_name'];
                }
                if (!empty($settings['system_tagline'])) {
                    $system_tagline = $settings['system_tagline'];
                }
            }
        } catch (Exception $e) {
            // Fallback to defaults
        }
    }
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
    <link rel="stylesheet" href="./css/loading.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <!-- Loading Modal Overlay (hidden by default) -->
    <div class="loading-overlay" id="loginLoadingOverlay" style="display:none;">
        <div class="loading-modal">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading your dashboard...</div>
            <div class="loading-subtext">Please wait while we prepare your experience.</div>
        </div>
    </div>
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="#" class="nav-brand">
                <img src="./media/nbsc_logo.png" alt="NBSC Logo" class="nav-logo">
                <img src="./media/LOGO.jpg" alt="Logo" class="nav-logo">
                <span class="nav-title">Institute For Business Management</span>
            </a>
             <ul class="nav-links" id="navLinks">
                 <?php if ($is_logged_in): ?>
                     <li class="user-info">
                         <span class="user-name">
                             <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                         </span>
                         <span class="user-role"><?php echo htmlspecialchars($role_label); ?></span>
                     </li>
                     <li><a href="<?php echo htmlspecialchars($dashboard_url); ?>" class="nav-login-btn nav-return"><i class="fas fa-home"></i> Return</a></li>
                     <li><a href="./data/logout.php" class="nav-login-btn nav-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                 <?php else: ?>
                     <li><a href="#" id="openLoginPanel" class="nav-login-btn"><i class="fas fa-sign-in-alt"></i> Login</a></li>
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

         <div class="hero-content" id="heroContent">
             <span class="hero-badge">Welcome IBM</span>
             <h1 class="hero-title">
                 Institute For Business Management<br>
                 <span class="gold-highlight"><?php echo htmlspecialchars($system_name); ?></span>
             </h1>
             <p class="hero-subtitle">
                 <?php echo htmlspecialchars($system_tagline); ?>
             </p>
            
         </div>
    </section>

    <footer class="footer">
        <div class="footer-content">
             <div class="footer-brand">
                 <img src="./media/LOGO.jpg" alt="Logo" class="footer-logo">
                 <span><?php echo htmlspecialchars($system_name); ?></span>
             </div>
            <p>&copy; 2026 CJCM. All Rights Reserved.</p>
            <ul class="footer-links">
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
                <li><a href="#">Contact</a></li>
            </ul>
        </div>
     </footer>

     <!-- Backdrop for panel -->
     <div class="panel-backdrop" id="panelBackdrop"></div>

     <!-- Sliding Login/Register Panel -->
     <div class="login-panel-slide" id="loginPanel">
         <!-- Login View -->
         <div class="login-slide-card" id="loginView">
             <button type="button" class="close-login" id="closeLoginPanel">&times;</button>
             <div class="login-header">
                 <div class="avatar-circle">
                     <i class="fas fa-user-shield"></i>
                 </div>
                 <h2>Welcome Back</h2>
                 <p class="login-subtitle">Sign in to your account</p>
             </div>

             <div class="role-selector">
                 <label class="field-label">Login As</label>
                 <div class="dropdown-wrapper" id="roleDropdown">
                     <div class="dropdown-trigger" id="dropdownTrigger">
                         <div class="dropdown-trigger-content">
                             <i class="fas fa-user-tag dropdown-icon"></i>
                             <span id="selectedRole">Select Role</span>
                         </div>
                         <i class="fas fa-chevron-down dropdown-arrow" id="dropdownArrow"></i>
                     </div>
                     <div class="dropdown-menu" id="dropdownMenu">
                         <div class="dropdown-item" data-value="admin">
                             <div class="dropdown-item-icon"><i class="fas fa-crown"></i></div>
                             <div class="dropdown-item-info">
                                 <span class="dropdown-item-title">Administrator</span>
                                 <span class="dropdown-item-desc">Full system access</span>
                             </div>
                             <i class="fas fa-check check-icon"></i>
                         </div>
                         <div class="dropdown-item" data-value="program_head">
                             <div class="dropdown-item-icon"><i class="fas fa-user-tie"></i></div>
                             <div class="dropdown-item-info">
                                 <span class="dropdown-item-title">Program Head</span>
                                 <span class="dropdown-item-desc">Department management access</span>
                             </div>
                             <i class="fas fa-check check-icon"></i>
                         </div>
                         <div class="dropdown-item" data-value="instructor">
                             <div class="dropdown-item-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                             <div class="dropdown-item-info">
                                 <span class="dropdown-item-title">Instructor</span>
                                 <span class="dropdown-item-desc">Faculty evaluation access</span>
                             </div>
                             <i class="fas fa-check check-icon"></i>
                         </div>
                     </div>
                 </div>
             </div>

             <form class="login-form" id="loginForm">
                 <div class="input-group">
                     <label class="field-label">Email</label>
                     <div class="input-wrapper">
                         <i class="fas fa-envelope input-icon"></i>
                         <input type="email" id="loginEmail" placeholder="Enter your email" maxlength="50" required>
                         <div class="input-focus-line"></div>
                     </div>
                 </div>

                 <div class="input-group">
                     <label class="field-label">Password</label>
                     <div class="input-wrapper">
                         <i class="fas fa-lock input-icon"></i>
                         <input type="password" id="loginPassword" placeholder="Enter your password" maxlength="50" required>
                         <button type="button" class="toggle-password" id="toggleLoginPassword">
                             <i class="fas fa-eye"></i>
                         </button>
                         <div class="input-focus-line"></div>
                     </div>
                 </div>

                 <button type="submit" class="login-btn" id="loginBtn">
                     <span class="btn-text">Sign In</span>
                     <span class="btn-loader"><i class="fas fa-spinner fa-spin"></i></span>
                     <i class="fas fa-arrow-right btn-arrow"></i>
                 </button>
             </form>

              <div class="demo-credentials" style="font-size: 0.75rem; color: var(--light-text); margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--border-light); position: relative;">
                  <button type="button" id="dismissDemoCredentials" style="position: absolute; top: 8px; right: 8px; background: none; border: none; color: var(--light-text); cursor: pointer; font-size: 1rem; line-height: 1; padding: 4px; opacity: 0.6; transition: opacity 0.2s;">
                      <i class="fas fa-times"></i>
                  </button>
                  <p style="margin-bottom: 6px; font-weight: 600; color: var(--gold-dark);">Demo Credentials:</p>
                  <div style="font-size: 0.7rem; line-height: 1.5; margin-left: 8px;">
                      <div><strong>Admin:</strong> admin@cjcm.edu / password123</div>
                      <div><strong>Instructor:</strong> teacher@test.com / password123</div>
                  </div>
              </div>

             <div class="login-footer" id="loginFooter" style="display:none;">
                 <p style="color: var(--medium-text); font-size: 0.85rem; margin-bottom: 12px;">
                     <i class="fas fa-info-circle" style="color: var(--gold-primary); margin-right: 6px;"></i>
                     Don't have an instructor account?
                 </p>
                 <button type="button" class="create-account-btn" id="switchToRegister">
                     <i class="fas fa-user-plus"></i> Create Instructor Account
                 </button>
             </div>
         </div>

         <!-- Register View -->
         <div class="login-slide-card" id="registerView" style="display:none;">
             <button type="button" class="close-login" id="closeRegisterPanel">&times;</button>
             <div class="login-header">
                 <div class="avatar-circle">
                     <i class="fas fa-chalkboard-teacher"></i>
                 </div>
                 <h2>Register as Instructor</h2>
                 <p class="login-subtitle">Create your instructor account</p>
             </div>

              <form id="registerForm" class="register-form" action="./data/register.php" method="POST">
                  <div class="form-row">
                     <div class="input-group">
                         <label class="field-label" for="regFirstName">First Name</label>
                         <div class="input-wrapper">
                             <i class="fas fa-user input-icon"></i>
                             <input type="text" id="regFirstName" name="first_name" placeholder="Enter first name" required>
                             <div class="input-focus-line"></div>
                         </div>
                     </div>
                     <div class="input-group">
                         <label class="field-label" for="regLastName">Last Name</label>
                         <div class="input-wrapper">
                             <i class="fas fa-user input-icon"></i>
                             <input type="text" id="regLastName" name="last_name" placeholder="Enter last name" required>
                             <div class="input-focus-line"></div>
                         </div>
                     </div>
                 </div>

                 <div class="input-group">
                     <label class="field-label" for="regMiddleName">Middle Name <span class="optional">(optional)</span></label>
                     <div class="input-wrapper">
                         <i class="fas fa-user input-icon"></i>
                         <input type="text" id="regMiddleName" name="middle_name" placeholder="Enter middle name">
                         <div class="input-focus-line"></div>
                     </div>
                 </div>

                 <div class="input-group">
                     <label class="field-label" for="regSuffix">Suffix <span class="optional">(optional)</span></label>
                     <div class="input-wrapper">
                         <i class="fas fa-id-card input-icon"></i>
                         <select id="regSuffix" name="suffix">
                             <option value="">None</option>
                             <option value="Jr.">Jr.</option>
                             <option value="Sr.">Sr.</option>
                             <option value="II">II</option>
                             <option value="III">III</option>
                             <option value="IV">IV</option>
                             <option value="V">V</option>
                         </select>
                     </div>
                 </div>

                 <div class="input-group">
                     <label class="field-label" for="regEmail">Email Address</label>
                     <div class="input-wrapper">
                         <i class="fas fa-envelope input-icon"></i>
                         <input type="email" id="regEmail" name="email" placeholder="e.g. name@school.edu" maxlength="50" required>
                         <div class="input-focus-line"></div>
                     </div>
                 </div>

                 <div class="input-group">
                     <label class="field-label" for="regPhone">Phone Number</label>
                     <div class="input-wrapper">
                         <i class="fas fa-phone input-icon"></i>
                         <input type="tel" id="regPhone" name="phone" placeholder="09xx-xxx-xxxx" maxlength="13" required autocomplete="off">
                         <div class="input-focus-line"></div>
                     </div>
                 </div>

                 <div class="input-group">
                     <label class="field-label" for="regPassword">Password</label>
                     <div class="input-wrapper">
                         <i class="fas fa-lock input-icon"></i>
                         <input type="password" id="regPassword" name="password" placeholder="At least 6 characters" maxlength="50" required minlength="6">
                         <button type="button" class="toggle-password" id="toggleRegPassword">
                             <i class="fas fa-eye"></i>
                         </button>
                         <div class="input-focus-line"></div>
                     </div>
                 </div>

                 <div class="input-group">
                     <label class="field-label" for="regConfirmPassword">Confirm Password</label>
                     <div class="input-wrapper">
                         <i class="fas fa-lock input-icon"></i>
                         <input type="password" id="regConfirmPassword" name="confirm_password" placeholder="Re-enter password" required>
                         <div class="input-focus-line"></div>
                     </div>
                 </div>

                  <button type="submit" class="login-btn" id="registerBtn">
                      <span class="btn-text"><i class="fas fa-user-plus"></i> Create Account</span>
                      <span class="btn-loader"><i class="fas fa-spinner fa-spin"></i></span>
                  </button>

                  <div class="form-message" id="formMessage" style="display:none; margin-top:12px; padding:10px; border-radius:6px; font-size:0.9rem;"></div>

                  <div class="login-footer">
                     <p>Already have an account?</p>
                     <button type="button" class="create-account-btn" id="switchToLogin">
                         <i class="fas fa-arrow-left"></i> Back to Login
                     </button>
                 </div>
             </form>
         </div>
     </div>

     <script src="./function/login.js"></script>
     <script src="./function/register.js"></script>
     <script type="module" src="./js/landing.js"></script>
  </body>

</html>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="https://public-frontend-cos.metadl.com/mgx/img/favicon_atoms.ico" type="image/x-icon">
    <title>Login - Faculty Evaluation System</title>
    <link rel="stylesheet" href="./css/common.css">
    <link rel="stylesheet" href="./css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <script>
      
        window.GOOGLE_CLIENT_ID = '51350034331-htkdt3n26sjrqti6vdrpf0giklsjuvnp.apps.googleusercontent.com';
    </script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        window.addEventListener('load', function() {
            const gIdOnload = document.getElementById('g_id_onload');
            if (gIdOnload && window.GOOGLE_CLIENT_ID && window.GOOGLE_CLIENT_ID !== 'YOUR_CLIENT_ID.apps.googleusercontent.com') {
                gIdOnload.setAttribute('data-client_id', window.GOOGLE_CLIENT_ID);
            }
        });
    </script>
</head>

<body class="login-page">
    <div class="bg-overlay"></div>
    <div class="bg-image"></div>

    <div class="particles" id="particles"></div>

    <div class="main-container">
        <div class="branding-panel">
            <div class="branding-content">
                <div class="logo-wrapper">
                    <img src="./media/LOGO.jpg" alt="University Logo" class="logo">
                </div>
                <h1 class="university-name">Faculty Management</h1>
                <h2 class="university-subtitle">Evaluation System</h2>
                <div class="divider"></div>
                <p class="tagline">Empowering Excellence in Education</p>
                <div class="login-features">
                    <div class="login-feature-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Performance Tracking</span>
                    </div>
                    <div class="login-feature-item">
                        <i class="fas fa-users"></i>
                        <span>Faculty Evaluation</span>
                    </div>
                    <div class="login-feature-item">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Assessment Reports</span>
                    </div>
                </div>
            </div>
            <div class="branding-footer">
                <p>&copy; 2026 CJCM. All Rights Reserved.</p>
            </div>
        </div>

        <div class="login-panel">
            <div class="login-card">
                <a href="./index.html" class="back-home">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Home</span>
                </a>

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
                            <div class="dropdown-item" data-value="program_head">
                                <div class="dropdown-item-icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="dropdown-item-info">
                                    <span class="dropdown-item-title">Program Head</span>
                                    <span class="dropdown-item-desc">Department management access</span>
                                </div>
                                <i class="fas fa-check check-icon"></i>
                            </div>
                            <div class="dropdown-item" data-value="instructor">
                                <div class="dropdown-item-icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
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
                            <input type="email" id="username" placeholder="Enter your email" required>
                            <div class="input-focus-line"></div>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="field-label">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" placeholder="Enter your password" required>
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="input-focus-line"></div>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" id="rememberMe">
                            <span class="checkmark"></span>
                            <span>Remember me</span>
                        </label>
                        <a href="./forgot_password.html" class="forgot-link">Forgot Password?</a>
                    </div>

                    <button type="submit" class="login-btn" id="loginBtn">
                        <span class="btn-text">Sign In</span>
                        <span class="btn-loader" id="btnLoader">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                        <i class="fas fa-arrow-right btn-arrow"></i>
                    </button>
                </form>

                <div class="oauth-divider">
                    <span>or continue with</span>
                </div>
                 
                <div class="google-signin-wrapper">
                    <div class="g_id_signin"
                         data-type="standard"
                         data-shape="rectangular"
                         data-theme="outline"
                         data-text="signin_with"
                         data-size="large"
                         data-width="300"
                         data-logo_alignment="left">
                    </div>
                </div>

                <div class="create-account-section" id="createAccountSection" style="display: none;">
                    <div class="create-account-divider">
                        <span>or</span>
                    </div>
                    <a href="./register_instructor.html" class="create-account-btn">
                        <i class="fas fa-user-plus"></i>
                        <span>Create Instructor Account</span>
                    </a>
                </div>

                <div class="login-footer">
                    <p>Need help? <a href="#">Contact Administrator</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="toast" id="toast">
        <i class="fas fa-check-circle toast-icon"></i>
        <span class="toast-message" id="toastMessage">Login Successful!</span>
    </div>

    <div id="g_id_onload"
         data-client_id="51350034331-htkdt3n26sjrqti6vdrpf0giklsjuvnp.apps.googleusercontent.com"
         data-context="signin"
         data-ux_mode="popup"
         data-callback="handleGoogleCredentialResponse"
         data-auto_prompt="false">
    </div>

    <script type="module" src="./js/login.js"></script>
</body>

</html>
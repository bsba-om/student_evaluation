<?php
// Start session to check login status
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect to their dashboard
if (isset($_SESSION['user_role']) && !empty($_SESSION['user_role'])) {
    $redirect = match($_SESSION['user_role']) {
        'admin' => 'admin/dashboard.php',
        'program_head' => 'program_head/dashboard.php',
        'instructor' => 'instructor/dashboard.php',
        default => 'login.php'
    };
    header('Location: ' . $redirect);
    exit;
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../media/LOGO.jpg" type="image/jpeg">
    <title>Register Instructor - Faculty Evaluation System</title>
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Register-specific: form grid & selects use login color palette */
        .register-form-card { max-width: 420px; animation: fadeInRight 0.8s ease-out 0.4s both; }
        .register-header { text-align: center; margin-bottom: 24px; }
        .register-header h2 { font-size: 1.5rem; font-weight: 700; color: var(--dark-text); margin-bottom: 4px; }
        .register-subtitle { font-size: 0.82rem; color: var(--light-text); font-weight: 400; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .register-form .input-group { margin-bottom: 16px; }
        .optional { font-weight: 400; color: var(--light-text); font-size: 0.7rem; text-transform: none; }
        .input-wrapper select {
            width: 100%; padding: 12px 40px 12px 42px;
            background: var(--off-white); border: 2px solid var(--border-light);
            border-radius: 12px; font-family: 'Poppins', sans-serif; font-size: 0.88rem;
            color: var(--dark-text); cursor: pointer; appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23B8860B' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 14px center;
            transition: var(--transition); outline: none;
        }
        .input-wrapper select:focus {
            border-color: var(--gold-primary); background-color: var(--cream);
            box-shadow: 0 0 0 3px rgba(212, 168, 67, 0.15);
        }
        .register-btn {
            width: 100%; padding: 14px 24px;
            background: linear-gradient(135deg, var(--gold-dark), var(--gold-primary), var(--yellow));
            color: var(--white); border: none; border-radius: 12px;
            font-family: 'Poppins', sans-serif; font-size: 0.95rem; font-weight: 700;
            cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: var(--transition); margin-top: 8px; text-transform: uppercase; letter-spacing: 1px;
        }
        .register-btn:hover { background: linear-gradient(135deg, #D4A843, #FFD700, #F0D68A); color: var(--dark-text); box-shadow: var(--shadow-gold); transform: translateY(-2px); }
        .register-btn:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
        .register-btn.loading .btn-text { display: none; }
        .register-btn .btn-loader { display: none; }
        .register-btn.loading .btn-loader { display: inline-block; }
        .register-signin { text-align: center; margin-top: 20px; font-size: 0.82rem; color: var(--light-text); }
        .register-signin a { color: var(--gold-dark); font-weight: 600; text-decoration: none; transition: var(--transition); }
        .register-signin a:hover { color: var(--gold-primary); text-decoration: underline; }
        .toast { position: fixed; top: 24px; right: 24px; padding: 16px 24px; border-radius: 12px; background: var(--white);
            box-shadow: var(--shadow-card); display: flex; align-items: center; gap: 12px; z-index: 1000;
            transform: translateX(400px); transition: transform 0.3s ease; border-left: 4px solid var(--gold-primary); }
        .toast.show { transform: translateX(0); }
        .toast.success { border-left-color: #10b981; }
        .toast.error { border-left-color: #dc2626; }
        .toast i { font-size: 1.1rem; }
        .toast.success i { color: #10b981; }
        .toast.error i { color: #dc2626; }
        .toast-message { font-size: 0.88rem; font-weight: 500; color: var(--dark-text); }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>

<body class="login-page">
    <div class="bg-overlay"></div>
    <div class="bg-image"></div>

    <div class="main-container">
        <div class="branding-panel">
            <div class="branding-content">
                <div class="logo-wrapper">
                    <img src="../media/LOGO.jpg" alt="CJCM Logo" class="logo">
                </div>
                <h1 class="university-name">Faculty Management</h1>
                <h2 class="university-subtitle">Evaluation System</h2>
                <div class="divider"></div>
                <p class="tagline">Join our faculty and get started</p>
                <div class="login-features">
                    <div class="login-feature-item">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Instructor account</span>
                    </div>
                    <div class="login-feature-item">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Evaluation & reports access</span>
                    </div>
                    <div class="login-feature-item">
                        <i class="fas fa-building"></i>
                        <span>Department integration</span>
                    </div>
                </div>
            </div>
            <div class="branding-footer">
                <p>&copy; 2026 CJCM. All Rights Reserved.</p>
            </div>
        </div>

        <div class="login-panel">
            <div class="register-form-card login-card">
                <a href="login.php" class="back-home">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Login</span>
                </a>

                <div class="register-header login-header">
                    <div class="avatar-circle">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h2>Register as Instructor</h2>
                    <p class="register-subtitle login-subtitle">Create your instructor account</p>
                </div>

                <form id="registerForm" class="register-form">
                    <div class="form-row">
                        <div class="input-group">
                            <label class="field-label" for="firstName">First Name</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" id="firstName" name="first_name" placeholder="Enter first name" required>
                                <div class="input-focus-line"></div>
                            </div>
                        </div>
                        <div class="input-group">
                            <label class="field-label" for="lastName">Last Name</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" id="lastName" name="last_name" placeholder="Enter last name" required>
                                <div class="input-focus-line"></div>
                            </div>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="field-label" for="middleName">Middle Name <span class="optional">(optional)</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="middleName" name="middle_name" placeholder="Enter middle name">
                            <div class="input-focus-line"></div>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="field-label" for="suffix">Suffix <span class="optional">(optional)</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-id-card input-icon"></i>
                            <select id="suffix" name="suffix">
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
                        <label class="field-label" for="email">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="email" name="email" placeholder="e.g. name@school.edu" required>
                            <div class="input-focus-line"></div>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="field-label" for="employeeId">Employee ID</label>
                        <div class="input-wrapper">
                            <i class="fas fa-id-badge input-icon"></i>
                            <input type="text" id="employeeId" name="employee_id" placeholder="e.g. EMP0001" required>
                            <div class="input-focus-line"></div>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="field-label" for="department">Department</label>
                        <div class="input-wrapper">
                            <i class="fas fa-building input-icon"></i>
                            <select id="department" name="department" required>
                                <option value="">Select department</option>
                                <option value="Operational Management">Operational Management (OM)</option>
                                <option value="Financial Management">Financial Management (FM)</option>
                                <option value="Marketing Management">Marketing Management (MM)</option>
                            </select>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="field-label" for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" placeholder="At least 6 characters" required minlength="6">
                            <div class="input-focus-line"></div>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="field-label" for="confirmPassword">Confirm Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="confirmPassword" name="confirm_password" placeholder="Re-enter password" required>
                            <div class="input-focus-line"></div>
                        </div>
                    </div>

                    <button type="submit" class="register-btn" id="registerBtn">
                        <span class="btn-text"><i class="fas fa-user-plus"></i> Create Account</span>
                        <span class="btn-loader"><i class="fas fa-spinner fa-spin"></i></span>
                    </button>
                </form>

                <div class="register-signin">
                    Already have an account? <a href="login.php">Sign In</a>
                </div>
            </div>
        </div>
    </div>

    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span class="toast-message" id="toastMessage">Success!</span>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const middleName = document.getElementById('middleName').value.trim();
            const suffix = document.getElementById('suffix').value;
            const email = document.getElementById('email').value.trim();
            const employeeId = document.getElementById('employeeId').value.trim();
            const department = document.getElementById('department').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const registerBtn = document.getElementById('registerBtn');
            
            if (password !== confirmPassword) {
                showToast('Passwords do not match!', 'error');
                return;
            }
            
            if (password.length < 6) {
                showToast('Password must be at least 6 characters!', 'error');
                return;
            }
            
            if (!department) {
                showToast('Please select a department.', 'error');
                return;
            }
            
            registerBtn.classList.add('loading');
            registerBtn.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'register_instructor');
                formData.append('first_name', firstName);
                formData.append('last_name', lastName);
                formData.append('middle_name', middleName);
                formData.append('suffix', suffix);
                formData.append('email', email);
                formData.append('employee_id', employeeId);
                formData.append('department', department);
                formData.append('password', password);
                
                const response = await fetch('../data/admin_process.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message || 'Registration successful!', 'success');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 1500);
                } else {
                    showToast(result.message || 'Registration failed!', 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
                console.error('Registration error:', error);
            } finally {
                registerBtn.classList.remove('loading');
                registerBtn.disabled = false;
            }
        });
        
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = toast.querySelector('i');
            
            toastMessage.textContent = message;
            toast.className = 'toast ' + type;
            
            if (type === 'success') {
                toastIcon.className = 'fas fa-check-circle';
            } else {
                toastIcon.className = 'fas fa-exclamation-circle';
            }
            
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    </script>
</body>

</html>

<?php
require_once '../../../data/session_security.php';

// Check role access - returns array with access status
$role_access = check_role_access('program_head');
$show_role_modal = !$role_access['allowed'];

$user_name = $_SESSION['user_name'] ?? 'Program Head';

// Only fetch data if access is allowed
if (!$show_role_modal) {
    require_once '../../../data/config.php';
    
    // Fetch program head profile
    $profile = [
        'first_name' => 'Program',
        'last_name' => 'Head',
        'email' => 'head@example.com'
    ];
    
    $ph_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
    try {
        $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM program_heads WHERE id = ?");
        $stmt->execute([$ph_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $profile = $result;
        }
    } catch (PDOException $e) {
        // Keep default profile if query fails
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Program Head Dashboard</title>
    <link rel="stylesheet" href="../../../css/common.css">
    <link rel="stylesheet" href="../style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --gold: #B8860B; --gold-light: #D4A843; --gold-dark: #8B6914; --cream: #f7f5ef; --cream-light: #f0ebe3; --white: #ffffff; --dark-text: #1f1f1f; --dark-text-2: #4a5568; --light-text: #666666; --border-light: #d4cfc5; --border-soft: #e8e4da; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; color: var(--dark-text); overflow-x: hidden; }
        .page-container { padding: 32px; }
        .welcome-banner { background: linear-gradient(160deg, #6b5a00 0%, var(--gold-light) 40%, var(--gold-dark) 100%); border-radius: 20px; padding: 36px 44px; color: white; margin-bottom: 32px; box-shadow: 0 8px 32px rgba(139, 105, 20, 0.4); position: relative; overflow: hidden; }
        .welcome-banner::before { content: ''; position: absolute; top: -50%; right: -10%; width: 300px; height: 300px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; }
        .welcome-banner h1 { font-size: 28px; font-weight: 800; margin: 0 0 12px 0; position: relative; z-index: 1; }
        .welcome-banner p { font-size: 15px; opacity: 0.95; margin: 0; max-width: 600px; position: relative; z-index: 1; }
        .settings-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
        .card { background: var(--white); border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid var(--border-soft); transition: all 0.3s ease; }
        .card:hover { box-shadow: 0 4px 20px rgba(184, 134, 11, 0.2); border-color: var(--gold-light); }
        .card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid var(--cream-light); }
        .card-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; }
        .card-icon.gold { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); }
        .card-icon.blue { background: linear-gradient(135deg, #0284c7, #38bdf8); }
        .card-icon.green { background: linear-gradient(135deg, #059669, #34d399); }
        .card-icon.purple { background: linear-gradient(135deg, #7c3aed, #a78bfa); }
        .card-title { font-size: 18px; font-weight: 700; color: var(--dark-text); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: var(--dark-text-2); margin-bottom: 8px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 16px; border: 2px solid var(--border-light); border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px; color: var(--dark-text); background: var(--white); transition: all 0.2s ease; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(184, 134, 11, 0.15); }
        .toggle-group { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-light); }
        .toggle-group:last-child { border-bottom: none; }
        .toggle-label { display: flex; flex-direction: column; }
        .toggle-label span:first-child { font-size: 14px; font-weight: 600; color: var(--dark-text); }
        .toggle-label span:last-child { font-size: 12px; color: var(--light-text); margin-top: 2px; }
        .toggle-switch { position: relative; width: 52px; height: 28px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--border-light); transition: 0.3s; border-radius: 28px; }
        .toggle-slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 3px; bottom: 3px; background-color: white; transition: 0.3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        input:checked + .toggle-slider { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); }
        input:checked + .toggle-slider:before { transform: translateX(24px); }
        .btn-save { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: white; padding: 14px 28px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(184, 134, 11, 0.3); }
        .btn-reset { background: var(--white); color: var(--dark-text-2); padding: 14px 28px; border: 2px solid var(--border-light); border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; margin-left: 12px; }
        .button-group { margin-top: 24px; padding-top: 24px; border-top: 2px solid var(--cream-light); }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .card { animation: fadeInUp 0.5s ease forwards; }
        .welcome-banner { animation: fadeInUp 0.5s ease forwards; }
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../../../media/LOGO.jpg" alt="Logo" class="sidebar-logo" style="width: 70px; height: 70px; border-radius: 16px; object-fit: cover; border: 3px solid white; background: white; padding: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
            <div class="sidebar-brand"><span class="sidebar-brand-name">IBM</span></div>
        </div>
        <div class="sidebar-user">
            <div class="sidebar-avatar"><i class="fas fa-user"></i></div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo htmlspecialchars($user_name); ?></span>
                <span class="sidebar-user-role">Program Head</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-nav-label">Menu</div>
            <a href="../dashboard.php" class="sidebar-nav-item"><i class="fas fa-chart-pie"></i><span>Overview</span></a>
             <a href="instructors.php" class="sidebar-nav-item"><i class="fas fa-chalkboard-teacher"></i><span>Instructors</span></a>
              <a href="student_enrollment.php" class="sidebar-nav-item"><i class="fas fa-user-graduate"></i><span>Enrollment</span></a>
               <a href="mentee_flow.php" class="sidebar-nav-item"><i class="fas fa-users"></i><span>MenteeFlow</span></a>
              <a href="departments.php" class="sidebar-nav-item"><i class="fas fa-building"></i><span>Departments</span></a>
            <a href="reports.php" class="sidebar-nav-item"><i class="fas fa-file-alt"></i><span>Reports</span></a>
            <a href="settings.php" class="sidebar-nav-item active"><i class="fas fa-cog"></i><span>Settings</span></a>
        </nav>
    </aside>
    <div class="main-content" style="position: relative;">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div><div class="topbar-title">Settings</div><div class="topbar-subtitle">Program Head Panel</div></div>
            </div>
            <div class="topbar-right">
                <div class="topbar-date"><i class="fas fa-calendar-alt"></i><span><?php echo date('F j, Y'); ?></span></div>
                <a href="../../../data/logout.php" class="topbar-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </header>
        <main class="dashboard-content">
            <div class="page-container">
                <div class="welcome-banner">
                    <h1>System Settings</h1>
                    <p>Configure your preferences and manage system settings for the Faculty Evaluation System.</p>
                </div>

                <div class="settings-grid">
                    <!-- Profile Settings -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon gold"><i class="fas fa-user-circle"></i></div>
                            <h3 class="card-title">Profile Settings</h3>
                        </div>
                         <div class="form-group">
                             <label>Full Name</label>
                             <input type="text" value="<?php echo htmlspecialchars(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')); ?>" placeholder="Enter your full name">
                         </div>
                         <div class="form-group">
                             <label>Email Address</label>
                             <input type="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" placeholder="Enter your email">
                         </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon blue"><i class="fas fa-bell"></i></div>
                            <h3 class="card-title">Notification Settings</h3>
                        </div>
                        <div class="toggle-group">
                            <div class="toggle-label"><span>Email Notifications</span><span>Receive email alerts for new evaluations</span></div>
                            <label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label>
                        </div>
                        <div class="toggle-group">
                            <div class="toggle-label"><span>Evaluation Reminders</span><span>Get reminded about pending evaluations</span></div>
                            <label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label>
                        </div>
                        <div class="toggle-group">
                            <div class="toggle-label"><span>Weekly Summary</span><span>Receive weekly performance summary</span></div>
                            <label class="toggle-switch"><input type="checkbox"><span class="toggle-slider"></span></label>
                        </div>
                        <div class="toggle-group">
                            <div class="toggle-label"><span>Instructor Updates</span><span>Get notified about instructor changes</span></div>
                            <label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label>
                        </div>
                    </div>

                    <!-- Evaluation Settings -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon green"><i class="fas fa-clipboard-check"></i></div>
                            <h3 class="card-title">Evaluation Settings</h3>
                        </div>
                        <div class="form-group">
                            <label>Evaluation Period</label>
                            <select><option>Semester</option><option>Quarterly</option><option>Monthly</option></select>
                        </div>
                        <div class="form-group">
                            <label>Default Rating Scale</label>
                            <select><option>1-5 Stars</option><option>1-10 Scale</option><option>ABCDF Grade</option></select>
                        </div>
                        <div class="form-group">
                            <label>Minimum Response Rate (%)</label>
                            <input type="number" value="75" min="0" max="100">
                        </div>
                    </div>

                    <!-- Display Settings -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon purple"><i class="fas fa-palette"></i></div>
                            <h3 class="card-title">Display Settings</h3>
                        </div>
                        <div class="toggle-group">
                            <div class="toggle-label"><span>Dark Mode</span><span>Switch to dark color scheme</span></div>
                            <label class="toggle-switch"><input type="checkbox"><span class="toggle-slider"></span></label>
                        </div>
                        <div class="toggle-group">
                            <div class="toggle-label"><span>Compact View</span><span>Show more items in less space</span></div>
                            <label class="toggle-switch"><input type="checkbox"><span class="toggle-slider"></span></label>
                        </div>
                        <div class="toggle-group">
                            <div class="toggle-label"><span>Show Ratings</span><span>Display ratings in list views</span></div>
                            <label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label>
                        </div>
                        <div class="toggle-group">
                            <div class="toggle-label"><span>Animations</span><span>Enable page animations</span></div>
                            <label class="toggle-switch"><input type="checkbox" checked><span class="toggle-slider"></span></label>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
                    <button class="btn-reset"><i class="fas fa-undo"></i> Reset to Default</button>
                </div>
            </div>
        </main>
    </div>
    <script src="../../../function/dashboard.js"></script>
    <?php if ($show_role_modal): ?>
    <div class="modal-overlay" id="roleMismatchModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;">
        <div style="background: white; border-radius: 16px; padding: 32px; max-width: 450px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(220, 38, 38, 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 40px; color: #dc2626;"></i>
            </div>
            <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 12px;">Access Restricted</h3>
            <p id="roleModalMessage" style="font-size: 14px; color: #6b7280; margin-bottom: 20px;"></p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <a href="../../../data/logout.php" style="background: #dc2626; color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 500;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
                <a href="../../../Door/login.php" style="background: linear-gradient(135deg, #d4a843, #b8922f); color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 500;">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
        </div>
    </div>
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            document.getElementById('roleModalMessage').textContent = <?php echo json_encode($role_access['message']); ?>;
            document.getElementById('roleMismatchModal').style.display = 'flex';
        });
    </script>
    <?php endif; ?>
</body>
</html>

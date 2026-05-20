<?php
require_once '../../../data/session_security.php';

$role_access = check_role_access('program_head');
$show_role_modal = !$role_access['allowed'];

$user_name = $_SESSION['user_name'] ?? 'Program Head';

if (!$show_role_modal) {
    require_once '../../../data/config.php';
    
    $profile = ['first_name' => 'Program', 'last_name' => 'Head', 'email' => 'head@example.com', 'position' => 'Program Head', 'office_location' => ''];
    
    $ph_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
    try {
        $stmt = $pdo->prepare("SELECT * FROM program_heads WHERE id = ?");
        $stmt->execute([$ph_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $profile = $result;
        }
    } catch (PDOException $e) {}
    
    $stats = [
        'total_instructors' => 0,
        'total_students' => 0,
        'total_subjects' => 0,
        'total_majors' => 0
    ];
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM instructors");
        $stats['total_instructors'] = $stmt->fetchColumn();
    } catch (PDOException $e) {}
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM students");
        $stats['total_students'] = $stmt->fetchColumn();
    } catch (PDOException $e) {}
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'subjects'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM subjects");
            $stats['total_subjects'] = $stmt->fetchColumn();
        }
    } catch (PDOException $e) {}
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM majors");
        $stats['total_majors'] = $stmt->fetchColumn();
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Program Head Dashboard</title>
    <link rel="icon" href="../../../media/LOGO.jpg" type="image/jpeg">
    <link rel="stylesheet" href="../../../css/common.css">
    <link rel="stylesheet" href="../style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --gold: #B8860B; --gold-light: #D4A843; --gold-dark: #8B6914; --cream: #f7f5ef; --cream-light: #f0ebe3; --white: #ffffff; --dark-text: #1f1f1f; --dark-text-2: #4a5568; --light-text: #666666; --border-light: #d4cfc5; --border-soft: #e8e4da; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; color: var(--dark-text); overflow-x: hidden; background: var(--cream); }
        .page-container { padding: 32px; }
        
        .section-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid var(--border-light); }
        .section-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; }
        .section-title { font-size: 18px; font-weight: 700; color: var(--dark-text); }
        
        .settings-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 24px; }
        .settings-grid-full { display: grid; grid-template-columns: 1fr; gap: 24px; margin-bottom: 24px; }
        .card { background: var(--white); border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid var(--border-soft); }
        .card:hover { box-shadow: 0 4px 20px rgba(184, 134, 11, 0.15); border-color: var(--gold-light); }
        
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--white); border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid var(--border-soft); text-align: center; }
        .stat-number { font-size: 28px; font-weight: 800; color: var(--gold-dark); }
        .stat-label { font-size: 12px; color: var(--light-text); margin-top: 4px; }
        
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--dark-text-2); margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 14px; border: 2px solid var(--border-light); border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px; color: var(--dark-text); background: var(--white); transition: all 0.2s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(184, 134, 11, 0.15); }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .form-row { display: flex; gap: 16px; align-items: flex-end; }
        .form-row .form-group { flex: 1; }
        
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
        
        .info-box { background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 12px; padding: 16px; border: 1px solid #fbbf24; margin-bottom: 20px; }
        .info-box-title { font-size: 14px; font-weight: 700; color: var(--gold-dark); margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .info-box-text { font-size: 13px; color: var(--dark-text-2); }
        
        .table-responsive { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .data-table th { background: var(--cream); padding: 12px; text-align: left; font-weight: 600; color: var(--dark-text-2); border-bottom: 2px solid var(--border-light); }
        .data-table td { padding: 12px; border-bottom: 1px solid var(--border-light); color: var(--dark-text); }
        .data-table tr:hover { background: var(--cream); }
        
        .btn { padding: 10px 20px; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: white; }
        .btn-secondary { background: var(--cream); color: var(--dark-text); border: 1px solid var(--border-light); }
        .btn-danger { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }
        
        .tab-nav { display: flex; gap: 4px; background: var(--white); padding: 4px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .tab-btn { flex: 1; padding: 12px 20px; border: none; background: transparent; border-radius: 10px; font-size: 14px; font-weight: 600; color: var(--light-text); cursor: pointer; transition: all 0.2s; }
        .tab-btn.active { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: white; }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        
        @media (max-width: 768px) {
            .settings-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
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
            <a href="departments.php" class="sidebar-nav-item"><i class="fas fa-graduation-cap"></i><span>Majors</span></a>
            <a href="reports.php" class="sidebar-nav-item"><i class="fas fa-file-alt"></i><span>Reports</span></a>
            <a href="settings.php" class="sidebar-nav-item active"><i class="fas fa-cog"></i><span>Settings</span></a>
        </nav>
    </aside>
    <div class="main-content" style="position: relative; padding-top: 70px;">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        <header class="topbar" style="position: fixed; top: 0; left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); z-index: 200;">
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
                <!-- Stats -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_instructors']; ?></div>
                        <div class="stat-label"><i class="fas fa-chalkboard-teacher"></i> Instructors</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                        <div class="stat-label"><i class="fas fa-user-graduate"></i> Students</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_subjects']; ?></div>
                        <div class="stat-label"><i class="fas fa-book"></i> Subjects</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_majors']; ?></div>
                        <div class="stat-label"><i class="fas fa-graduation-cap"></i> Majors</div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="tab-nav">
                    <button class="tab-btn active" onclick="switchTab('profile', this)"><i class="fas fa-user"></i> Profile</button>
                    <button class="tab-btn" onclick="switchTab('department', this)"><i class="fas fa-building"></i> Department</button>
                </div>

                <!-- Profile Tab -->
                <div class="tab-content active" id="profileTab">
                    <div class="card">
                        <div class="section-header">
                            <div class="section-icon" style="background: linear-gradient(135deg, #dc2626, #f87171);"><i class="fas fa-lock"></i></div>
                            <h3 class="section-title">Change Password</h3>
                        </div>
                        <form id="passwordForm" onsubmit="changePassword(event)">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" id="currentPassword" required>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" id="newPassword" required>
                                </div>
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" id="confirmPassword" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Change Password</button>
                        </form>
                    </div>
                </div>

                <!-- Department Tab -->
                <div class="tab-content" id="departmentTab">
                    <div class="card">
                        <div class="section-header">
                            <div class="section-icon" style="background: linear-gradient(135deg, #7c3aed, #a78bfa);"><i class="fas fa-cogs"></i></div>
                            <h3 class="section-title">Department Settings</h3>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Department Name</label>
                                <input type="text" id="deptName" name="deptName" value="Business Management" placeholder="Enter department name">
                            </div>
                            <div class="form-group">
                                <label>Academic Year</label>
                                <select id="academicYear" onchange="onAcademicYearChange()">
                                    <option>2025-2026</option>
                                    <option>2026-2027</option>
                                    <option>2027-2028</option>
                                    <option>2028-2029</option>
                                    <option>2029-2030</option>
                                    <option>2030-2031</option>
                                    <option>2031-2032</option>
                                    <option>2032-2033</option>
                                    <option>2033-2034</option>
                                    <option>2034-2035</option>
                                    <option>2035-2036</option>
                                    <option>2036-2037</option>
                                    <option>2037-2038</option>
                                    <option>2038-2039</option>
                                    <option>2039-2040</option>
                                    <option>2040-2041</option>
                                </select>
                            </div>
                        </div>
                        <div style="grid-column: 1 / -1; margin-top: -8px; margin-bottom: 4px;">
                            <span id="ayAutoHint" style="display:none; font-size: 11px; color: var(--gold-dark); background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 6px 12px;">
                                <i class="fas fa-sync-alt"></i> Academic Year set to <strong><span id="ayAutoLabel"></span></strong> — <em>Current Semester</em> and <em>Enrollment Period</em> adjusted below.
                            </span>
                        </div>
                    </div>
                    
                    <div class="card" style="margin-top: 24px;">
                        <div class="section-header">
                            <div class="section-icon" style="background: linear-gradient(135deg, #B8860B, #D4A843);"><i class="fas fa-scroll"></i></div>
                            <h3 class="section-title">Prospectus Header Info</h3>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>School Name</label>
                                <input type="text" id="schoolName" name="school_name" value="Northern Bukidnon State College" placeholder="Northern Bukidnon State College">
                            </div>
                            <div class="form-group">
                                <label>School Address</label>
                                <input type="text" id="schoolAddress" name="school_address" value="Manolo Fortich, Bukidnon" placeholder="Manolo Fortich, Bukidnon">
                            </div>
                            <div class="form-group">
                                <label>Institute/College Name</label>
                                <input type="text" id="instituteName" name="institute_name" value="Institute for Business Management" placeholder="Institute for Business Management">
                            </div>
                            <div class="form-group">
                                <label>Degree Name</label>
                                <input type="text" id="degreeName" name="degree_name" value="Bachelor of Science in Business Administration" placeholder="Bachelor of Science in Business Administration">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="alertContainer" style="position: fixed; top: 80px; right: 20px; z-index: 10000; max-width: 350px;"></div>
    <script src="../../../function/dashboard.js"></script>
    <script>
        // ── tiny helpers below avoid deprecated optional-chaining ('?.') ────────
        // Some older browsers throw "Unexpected token '.'" on '?.'.  We replace
        // every    el?.value       → (el||{}).value
        //             el?.checked   → (el||{}).checked
        //
        // $(id,type) returns element.value / element.checked / empty-fallback
        function _f(id,attr){var e=document.getElementById(id);return e?e[attr]:null;}

        let saveTimeout;
        
function switchTab(tab, btn) {
             document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
             document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.remove('active'); });
             if (btn && btn.classList) btn.classList.add('active');
             var target = document.getElementById(tab + 'Tab');
             if (target) target.classList.add('active');
         }
         
         function showAlert(type, message) {
             const alertDiv = document.createElement('div');
             alertDiv.className = 'alert ' + (type === 'success' ? 'alert-success' : 'alert-error');
             alertDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message;
             document.getElementById('alertContainer').appendChild(alertDiv);
             setTimeout(function() { alertDiv.remove(); }, 3000);
         }
         
         function autoSave() {
             clearTimeout(saveTimeout);
             saveTimeout = setTimeout(function() {
                const formData = new FormData();
                formData.append('action', 'save_settings');
                
                // Collect all settings
                formData.append('deptName',        (document.getElementById('deptName')        || {}).value        || '');
                formData.append('academicYear',    (document.getElementById('academicYear')    || {}).value        || '');
                formData.append('deptDesc',        (document.getElementById('deptDesc')        || {}).value        || '');
                formData.append('currentSemester', (document.getElementById('currentSemester') || {}).value        || '');
                formData.append('enrollmentStatus',(document.getElementById('enrollmentStatus')|| {}).value       || '');
                formData.append('autoAssign',      !!(document.getElementById('autoAssign')      || {}).checked      || false);
                formData.append('requireApproval', !!(document.getElementById('requireApproval') || {}).checked      || false);
                formData.append('publicEval',      !!(document.getElementById('publicEval')      || {}).checked      || false);
                formData.append('emailNotif',      !!(document.getElementById('emailNotif')      || {}).checked      || false);
                formData.append('ratingScale',     (document.getElementById('ratingScale')     || {}).value        || '');
                formData.append('minRating',       (document.getElementById('minRating')       || {}).value        || '');
                formData.append('ratingLabels',    (document.getElementById('ratingLabels')    || {}).value        || '');
                formData.append('minResponse',     (document.getElementById('minResponse')     || {}).value        || '');
                formData.append('evalDeadline',    (document.getElementById('evalDeadline')    || {}).value        || '');
                formData.append('allowLate',       (document.getElementById('allowLate')       || {}).value        || '');
                formData.append('includeComments', !!(document.getElementById('includeComments') || {}).checked      || false);
                formData.append('showRankings',    !!(document.getElementById('showRankings')    || {}).checked      || false);
                formData.append('exportPdf',       !!(document.getElementById('exportPdf')       || {}).checked      || false);
                formData.append('exportExcel',     !!(document.getElementById('exportExcel')     || {}).checked      || false);
                formData.append('notifNewEval',    !!(document.getElementById('notifNewEval')    || {}).checked      || false);
                formData.append('notifReminders',  !!(document.getElementById('notifReminders')  || {}).checked      || false);
                formData.append('notifWeekly',     !!(document.getElementById('notifWeekly')     || {}).checked      || false);
                formData.append('notifInstructor', !!(document.getElementById('notifInstructor') || {}).checked      || false);
                formData.append('notifEnrollment', !!(document.getElementById('notifEnrollment') || {}).checked      || false);
                formData.append('reminderFreq',    (document.getElementById('reminderFreq')    || {}).value        || '');
                formData.append('reminderTime',    (document.getElementById('reminderTime')    || {}).value        || '');
                formData.append('school_name',      (document.getElementById('schoolName')      || {}).value        || '');
                formData.append('school_address',   (document.getElementById('schoolAddress')   || {}).value        || '');
                formData.append('institute_name',   (document.getElementById('instituteName')   || {}).value        || '');
                formData.append('degree_name',      (document.getElementById('degreeName')      || {}).value        || '');
                
                fetch('../../../data/settings_process.php', {
                    method: 'POST',
                    body: formData
                })
.then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) showAlert('success', 'Settings auto-saved');
                });
            }, 500);
        }

        // ── Academic Year → auto-set Current Semester + Enrollment Period ──────

        function autoSetAcademicCalendar(showHint) {
            var sel = document.getElementById('academicYear');
            if (!sel) return;
            var value  = sel.value;              // e.g. "2025-2026"
            var parts  = value.split('-');
            var endYear = parseInt(parts[1]) || (parseInt(parts[0]) + 1);
            // End-year is even → 2nd Semester | odd → 1st Semester
            var computedSem = (endYear % 2 === 0) ? '2nd Semester' : '1st Semester';
            var computedEnroll = 'Open';         // Default; set manually if needed

            var semEl    = document.getElementById('currentSemester');
            var enrollEl = document.getElementById('enrollmentStatus');

            // Only fill when the field is empty — preserves any value saved in DB
            // or typed by the program head.
            if (semEl && !semEl.value)    semEl.value    = computedSem;
            if (enrollEl && !enrollEl.value) enrollEl.value = computedEnroll;

            if (showHint) {
                document.getElementById('ayAutoLabel').textContent = value;
                var hintEl = document.getElementById('ayAutoHint');
                if (hintEl) {
                    hintEl.style.display = 'inline-block';
                    setTimeout(function () { hintEl.style.display = 'none'; }, 4000);
                }
            }
        }

        function onAcademicYearChange() {
            autoSetAcademicCalendar(true);
            autoSave();
        }
        
        // Attach auto-save to all inputs
        document.addEventListener('DOMContentLoaded', function() {
            // Snapshot the HTML-default values so we can detect what was loaded from DB.
            // If a field ends up *different* from its HTML default, DB populated it.
            var _htmlDefaults = {
                academicYear    : (document.getElementById('academicYear')     || {}).value || '',
                currentSemester : (document.getElementById('currentSemester')  || {}).value || '',
                enrollmentStatus: (document.getElementById('enrollmentStatus') || {}).value || ''
            };

            var loadPromise = loadSettings();
            loadPromise &&
            loadPromise.then(function () {
                // Runs AFTER loadSettings has set values from the DB (if any).
                // If a field still matches its HTML default, DB had nothing for it → auto-set.
                var semEl = document.getElementById('currentSemester');
                var enrollEl = document.getElementById('enrollmentStatus');
                if (semEl    && semEl.value    === _htmlDefaults.currentSemester)    semEl.value    = '';
                if (enrollEl && enrollEl.value === _htmlDefaults.enrollmentStatus)   enrollEl.value = '';
                autoSetAcademicCalendar(false);
            });
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(function (input) {
                input.addEventListener('change', autoSave);
                input.addEventListener('keyup', autoSave);
            });
        });
        
        function loadSettings() {
            var formData = new FormData();
            formData.append('action', 'get_settings');
            return fetch('../../../data/settings_process.php', {
                method: 'POST',
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.settings) {
                    const s = data.settings;
                    const deptNameEl = document.getElementById('deptName');
                    const academicYearEl = document.getElementById('academicYear');
                    const deptDescEl = document.getElementById('deptDesc');
                    const currentSemesterEl = document.getElementById('currentSemester');
                    const enrollmentStatusEl = document.getElementById('enrollmentStatus');
                    const autoAssignEl = document.getElementById('autoAssign');
                    const requireApprovalEl = document.getElementById('requireApproval');
                    const publicEvalEl = document.getElementById('publicEval');
                    const emailNotifEl = document.getElementById('emailNotif');
                    const ratingScaleEl = document.getElementById('ratingScale');
                    const minRatingEl = document.getElementById('minRating');
                    const ratingLabelsEl = document.getElementById('ratingLabels');
                    const minResponseEl = document.getElementById('minResponse');
                    const evalDeadlineEl = document.getElementById('evalDeadline');
                    const allowLateEl = document.getElementById('allowLate');
                    const includeCommentsEl = document.getElementById('includeComments');
                    const showRankingsEl = document.getElementById('showRankings');
                    const exportPdfEl = document.getElementById('exportPdf');
                    const exportExcelEl = document.getElementById('exportExcel');
                    const notifNewEvalEl = document.getElementById('notifNewEval');
                    const notifRemindersEl = document.getElementById('notifReminders');
                    const notifWeeklyEl = document.getElementById('notifWeekly');
                    const notifInstructorEl = document.getElementById('notifInstructor');
                    const notifEnrollmentEl = document.getElementById('notifEnrollment');
                    const reminderFreqEl = document.getElementById('reminderFreq');
                    const reminderTimeEl = document.getElementById('reminderTime');
                    const schoolNameEl = document.getElementById('schoolName');
                    const schoolAddressEl = document.getElementById('schoolAddress');
                    const instituteNameEl = document.getElementById('instituteName');
                    const degreeNameEl = document.getElementById('degreeName');

                    if (Object.prototype.hasOwnProperty.call(s, 'deptName') && deptNameEl) deptNameEl.value = s.deptName || '';
                    if (Object.prototype.hasOwnProperty.call(s, 'academicYear') && academicYearEl) academicYearEl.value = s.academicYear || '';
                    if (Object.prototype.hasOwnProperty.call(s, 'deptDesc') && deptDescEl) deptDescEl.value = s.deptDesc || '';
                    if (s.currentSemester && currentSemesterEl) currentSemesterEl.value = s.currentSemester;
                    if (s.enrollmentStatus && enrollmentStatusEl) enrollmentStatusEl.value = s.enrollmentStatus;
                    if (s.autoAssign !== undefined && autoAssignEl) autoAssignEl.checked = s.autoAssign;
                    if (s.requireApproval !== undefined && requireApprovalEl) requireApprovalEl.checked = s.requireApproval;
                    if (s.publicEval !== undefined && publicEvalEl) publicEvalEl.checked = s.publicEval;
                    if (s.emailNotif !== undefined && emailNotifEl) emailNotifEl.checked = s.emailNotif;
                    if (s.ratingScale && ratingScaleEl) ratingScaleEl.value = s.ratingScale;
                    if (s.minRating && minRatingEl) minRatingEl.value = s.minRating;
                    if (s.ratingLabels && ratingLabelsEl) ratingLabelsEl.value = s.ratingLabels;
                    if (s.minResponse && minResponseEl) minResponseEl.value = s.minResponse;
                    if (s.evalDeadline && evalDeadlineEl) evalDeadlineEl.value = s.evalDeadline;
                    if (s.allowLate && allowLateEl) allowLateEl.value = s.allowLate;
                    if (s.includeComments !== undefined && includeCommentsEl) includeCommentsEl.checked = s.includeComments;
                    if (s.showRankings !== undefined && showRankingsEl) showRankingsEl.checked = s.showRankings;
                    if (s.exportPdf !== undefined && exportPdfEl) exportPdfEl.checked = s.exportPdf;
                    if (s.exportExcel !== undefined && exportExcelEl) exportExcelEl.checked = s.exportExcel;
                    if (s.notifNewEval !== undefined && notifNewEvalEl) notifNewEvalEl.checked = s.notifNewEval;
                    if (s.notifReminders !== undefined && notifRemindersEl) notifRemindersEl.checked = s.notifReminders;
                    if (s.notifWeekly !== undefined && notifWeeklyEl) notifWeeklyEl.checked = s.notifWeekly;
                    if (s.notifInstructor !== undefined && notifInstructorEl) notifInstructorEl.checked = s.notifInstructor;
                    if (s.notifEnrollment !== undefined && notifEnrollmentEl) notifEnrollmentEl.checked = s.notifEnrollment;
                    if (s.reminderFreq && reminderFreqEl) reminderFreqEl.value = s.reminderFreq;
                    if (s.reminderTime && reminderTimeEl) reminderTimeEl.value = s.reminderTime;
                    if (s.school_name && schoolNameEl) schoolNameEl.value = s.school_name;
                    else if (s.schoolName && schoolNameEl) schoolNameEl.value = s.schoolName;
                    if (s.school_address && schoolAddressEl) schoolAddressEl.value = s.school_address;
                    else if (s.schoolAddress && schoolAddressEl) schoolAddressEl.value = s.schoolAddress;
                    if (s.institute_name && instituteNameEl) instituteNameEl.value = s.institute_name;
                    else if (s.instituteName && instituteNameEl) instituteNameEl.value = s.instituteName;
                    if (s.degree_name && degreeNameEl) degreeNameEl.value = s.degree_name;
                    else if (s.degreeName && degreeNameEl) degreeNameEl.value = s.degreeName;
                }
                return data;
            });
        }
        
        function saveProfile(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('action', 'update_profile');
            formData.append('first_name', document.getElementById('firstName').value);
            formData.append('last_name', document.getElementById('lastName').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('position', document.getElementById('position').value);
            formData.append('office_location', document.getElementById('officeLocation').value);
            
            fetch('../../../data/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                showAlert(data.success ? 'success' : 'error', data.message || 'Profile saved successfully');
            });
        }
        
        function changePassword(e) {
            e.preventDefault();
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            
            if (newPass !== confirmPass) {
                showAlert('error', 'Passwords do not match');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'change_password');
            formData.append('current_password', document.getElementById('currentPassword').value);
            formData.append('new_password', newPass);
            
            fetch('../../../data/change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                showAlert(data.success ? 'success' : 'error', data.message);
                if (data.success) document.getElementById('passwordForm').reset();
            });
        }
        
        function saveAllSettings() {
            showAlert('success', 'All settings saved successfully!');
            localStorage.setItem('ph_settings', 'saved');
        }
        
        function resetSettings() {
            if (confirm('Reset all settings to default?')) {
                localStorage.removeItem('ph_settings');
                location.reload();
            }
        }
    </script>
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
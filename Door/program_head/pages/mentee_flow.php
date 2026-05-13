<?php
require_once '../../../data/session_security.php';

// Check role access
$role_access = check_role_access('program_head');
$show_role_modal = !$role_access['allowed'];

$user_name = $_SESSION['user_name'] ?? 'Program Head';

if (!$show_role_modal) {
    require_once '../../../data/config.php';
    
    // Fetch all students with student_id
    $students = [];
    try {
        $stmt = $pdo->query("SELECT s.id, s.student_id, s.first_name, s.last_name, s.email, m.major_name 
                             FROM students s 
                             LEFT JOIN majors m ON s.major_id = m.id 
                             ORDER BY s.last_name");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $students = [];
    }
    
    // Fetch all instructors
    $instructors = [];
    try {
        $sql = "SELECT i.id, i.first_name, i.middle_name, i.last_name, i.suffix, i.email, i.position, i.avatar_gradient_from, i.avatar_gradient_to 
                FROM instructors i 
                ORDER BY i.last_name";
        $stmt = $pdo->query($sql);
        $instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $instructors = [];
    }
    
    // Fetch existing mentee assignments
    $menteeAssignments = []; // instructor_id => [student data]
    try {
        $stmt = $pdo->query("SELECT m.id, m.first_name, m.last_name, m.email, m.mentor_id 
                             FROM mentees m 
                             ORDER BY m.last_name");
        $mentees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($mentees as $mentee) {
            $menteeAssignments[$mentee['mentor_id']][] = $mentee;
        }
    } catch (PDOException $e) {
        $menteeAssignments = [];
    }
    
    // Calculate stats for display
    $totalStudents = count($students);
    $assignedMentees = array_sum(array_map('count', $menteeAssignments));
    $unassignedStudents = $totalStudents - $assignedMentees;
    $instructorsWithMentees = count(array_filter($menteeAssignments, fn($m) => count($m) > 0));
    $avgMenteesPerInstructor = count($instructors) > 0 ? round($assignedMentees / count($instructors), 1) : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>MenteeFlow - Program Head</title>
    <link rel="icon" href="../../../media/LOGO.jpg" type="image/jpeg">
    <link rel="stylesheet" href="../../../css/common.css">
    <link rel="stylesheet" href="../style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --gold: #B8860B;
            --gold-light: #D4A843;
            --gold-dark: #8B6914;
            --gold-lighter: #F5E6B8;
            --cream: #f7f5ef;
            --white: #ffffff;
            --dark-text: #1f1f1f;
            --light-text: #666666;
            --border-light: #d4cfc5;
            --success: #059669;
            --success-light: #c6f6d5;
            --danger: #dc2626;
            --danger-light: #fee2e2;
            --info: #0284c7;
            --info-light: #bae6fd;
            --purple: #7c3aed;
            --orange: #ea580c;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark-text);
            background: var(--cream);
        }
        .page-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .welcome-banner {
            background: linear-gradient(160deg, #6b5a00 0%, var(--gold-light) 40%, var(--gold-dark) 100%);
            border-radius: 16px;
            padding: 24px 28px;
            color: var(--white);
            margin-bottom: 24px;
            box-shadow: 0 6px 24px rgba(139, 105, 20, 0.3);
            position: relative;
            overflow: hidden;
        }
        .welcome-banner h1 {
            font-size: 22px;
            font-weight: 800;
            margin: 0 0 8px 0;
        }
        .welcome-banner p {
            font-size: 13px;
            opacity: 0.9;
            margin: 0;
            max-width: 500px;
        }
        .welcome-banner-role {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .stats-row {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 16px 20px;
            border: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            min-width: 160px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }
        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .stat-icon.blue { background: var(--info-light); color: var(--info); }
        .stat-icon.green { background: var(--success-light); color: var(--success); }
        .stat-icon.gold { background: var(--gold-lighter); color: var(--gold-dark); }
        .stat-icon.purple { background: #ede9fe; color: #7c3aed; }
        .stat-icon.orange { background: #ffedd5; color: #ea580c; }
        .stat-info h4 {
            font-size: 20px;
            font-weight: 800;
            color: var(--dark-text);
            margin-bottom: 2px;
        }
        .stat-info p {
            font-size: 12px;
            color: var(--light-text);
        }
        .card {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(184, 134, 11, 0.12);
            border: 1px solid var(--border-soft);
            margin-bottom: 20px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .card-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark-text);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-title i { color: var(--gold); font-size: 18px; }
        .search-box {
            position: relative;
            margin-bottom: 16px;
        }
        .search-box input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            border: 2px solid var(--border-light);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            color: var(--dark-text);
            background: var(--cream-light);
            transition: all 0.2s ease;
        }
        .search-box input:focus {
            outline: none;
            border-color: var(--gold-light);
            background: var(--white);
        }
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
            font-size: 14px;
        }
        .mentee-count {
            background: var(--gold-light);
            color: var(--white);
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 700;
        }
        .list-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: var(--cream-light);
            border-radius: 12px;
            margin-bottom: 8px;
            border: 1px solid var(--border-light);
            transition: all 0.2s ease;
        }
        .list-item:hover {
            border-color: var(--gold-light);
            transform: translateX(4px);
        }
        .student-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }
        .student-info {
            flex: 1;
            min-width: 0;
        }
        .student-name {
            font-weight: 700;
            font-size: 14px;
            color: var(--dark-text);
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .student-meta {
            font-size: 11px;
            color: var(--light-text);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .student-id-badge {
            background: var(--gold-lighter);
            color: var(--gold-dark);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
        }
        .assign-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .instructor-select {
            padding: 6px 10px;
            border: 2px solid var(--border-light);
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            color: var(--dark-text);
            background: var(--white);
            min-width: 140px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .instructor-select:focus {
            outline: none;
            border-color: var(--gold-light);
        }
        .btn-assign {
            padding: 6px 12px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }
        .btn-assign:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(184, 134, 11, 0.3);
        }
        .btn-assign:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .btn-assign.success {
            background: linear-gradient(135deg, var(--success), #34d399);
        }
         .student-assigned {
             background: var(--success-light);
             border-color: var(--success);
         }
         .student-assigned .student-name::after {
             content: " ✓";
             color: var(--success);
             font-weight: 800;
         }
         .student-row.selected {
             background: var(--gold-lighter);
             border-color: var(--gold);
             box-shadow: 0 0 0 2px rgba(184, 134, 11, 0.2);
         }
         .student-row.selected .student-checkbox {
             accent-color: var(--gold-dark);
         }
        .instructor-item {
            margin-bottom: 12px;
        }
        .instructor-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            background: var(--cream-light);
            border-radius: 12px;
            border: 1px solid var(--border-light);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .instructor-header:hover {
            border-color: var(--gold-light);
        }
        .instructor-main {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .instructor-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 14px;
        }
        .instructor-info {
            flex: 1;
        }
        .instructor-name {
            font-weight: 700;
            font-size: 14px;
            color: var(--dark-text);
        }
        .instructor-position {
            font-size: 12px;
            color: var(--light-text);
        }
        .instructor-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-toggle {
            padding: 8px 12px;
            background: var(--white);
            color: var(--gold);
            border: 2px solid var(--gold-light);
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .btn-toggle.active {
            background: var(--gold-light);
            color: white;
        }
        .btn-toggle i {
            transition: transform 0.3s ease;
            font-size: 12px;
        }
        .btn-toggle.active i {
            transform: rotate(180deg);
        }
        .mentees-panel {
            margin-top: 10px;
            padding: 12px;
            background: var(--cream-light);
            border-radius: 10px;
            border: 1px solid var(--border-light);
            display: none;
        }
        .mentees-panel.show {
            display: block;
        }
        .mentees-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            max-height: 280px;
            overflow-y: auto;
        }
        .mentee-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            background: var(--white);
            border-radius: 8px;
            border: 1px solid var(--border-light);
        }
        .mentee-item:hover {
            border-color: var(--gold-light);
        }
        .mentee-item-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mentee-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
        }
        .mentee-item-name {
            font-weight: 600;
            font-size: 13px;
            color: var(--dark-text);
        }
        .mentee-item-email {
            font-size: 11px;
            color: var(--light-text);
        }
        .btn-remove-mentee {
            padding: 6px 10px;
            background: var(--danger-light);
            color: var(--danger);
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
        }
        .btn-remove-mentee:hover {
            background: var(--danger);
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: var(--light-text);
            font-size: 13px;
        }
        .empty-state i {
            font-size: 36px;
            opacity: 0.4;
            color: var(--gold-light);
            margin-bottom: 8px;
        }
        .empty-state p {
            font-style: italic;
            margin: 0;
        }
        
        /* Toast */
        .toast-container {
            position: fixed;
            top: 80px;
            right: 16px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .toast {
            padding: 12px 16px;
            border-radius: 10px;
            color: white;
            font-size: 13px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 260px;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(60px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .toast.success { background: linear-gradient(135deg, var(--success), #34d399); }
        .toast.error { background: linear-gradient(135deg, var(--danger), #f87171); }
        .toast i { font-size: 16px; }
        .toast-close {
            margin-left: auto;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            opacity: 0.7;
            font-size: 16px;
        }
        .toast-close:hover { opacity: 1; }
        
        /* Loading */
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .modal-overlay.show { opacity: 1; visibility: visible; }
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 24px;
            max-width: 360px;
            text-align: center;
            transform: scale(0.9);
            transition: all 0.3s ease;
        }
        .modal-overlay.show .modal-content { transform: scale(1); }
        .modal-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--danger-light);
            color: var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 16px;
        }
        .modal-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 8px;
        }
        .modal-message {
            font-size: 14px;
            color: var(--light-text);
            margin-bottom: 20px;
        }
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .btn-modal {
            padding: 10px 20px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-modal-cancel {
            background: var(--cream-light);
            color: var(--dark-text);
        }
        .btn-modal-cancel:hover { background: var(--border-light); }
        .btn-modal-confirm {
            background: var(--danger);
            color: white;
        }
        .btn-modal-confirm:hover { background: #b91c1c; }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .page-container { padding: 16px; }
            .welcome-banner { padding: 20px; }
            .welcome-banner h1 { font-size: 18px; }
            .welcome-banner p { font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../../../media/LOGO.jpg" alt="Logo" class="sidebar-logo" style="width: 50px; height: 50px; border-radius: 12px; object-fit: cover; border: 2px solid white; background: white; padding: 2px;">
            <div class="sidebar-brand">
                <span class="sidebar-brand-name">IBM</span>
            </div>
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
            <a href="mentee_flow.php" class="sidebar-nav-item active"><i class="fas fa-users"></i><span>MenteeFlow</span></a>
            <a href="departments.php" class="sidebar-nav-item"><i class="fas fa-building"></i><span>Departments</span></a>
            <a href="reports.php" class="sidebar-nav-item"><i class="fas fa-file-alt"></i><span>Reports</span></a>
            <a href="settings.php" class="sidebar-nav-item"><i class="fas fa-cog"></i><span>Settings</span></a>
        </nav>
    </div>
    <div class="main-content" style="position: relative; padding-top: 70px;">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        <header class="topbar" style="position: fixed; top: 0; left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); z-index: 200;">
            <div class="topbar-left">
                <button class="topbar-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div><div class="topbar-title">MenteeFlow</div><div class="topbar-subtitle">Program Head Panel</div></div>
            </div>
            <div class="topbar-right">
                <div class="topbar-date"><i class="fas fa-calendar-alt"></i><span><?php echo date('F j, Y'); ?></span></div>
                <a href="../../../data/logout.php" class="topbar-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </header>
<main class="dashboard-content">
            <div class="page-container">
                <div class="welcome-banner">
                    <div class="welcome-banner-role"><i class="fas fa-user-graduate"></i> MenteeFlow</div>
                    <h1>Mentees Management</h1>
                    <p>Assign students to instructors and manage mentee relationships</p>
                </div>
                
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div>
                        <div class="stat-info">
                            <h4><?php echo $totalStudents; ?></h4>
                            <p>Total Students</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <h4><?php echo $assignedMentees; ?></h4>
                            <p>Assigned Mentees</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon gold"><i class="fas fa-user-plus"></i></div>
                        <div class="stat-info">
                            <h4><?php echo $unassignedStudents; ?></h4>
                            <p>Unassigned Students</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="stat-info">
                            <h4><?php echo $instructorsWithMentees; ?></h4>
                            <p>Instructors with Mentees</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="fas fa-chart-line"></i></div>
                        <div class="stat-info">
                            <h4><?php echo $avgMenteesPerInstructor; ?></h4>
                            <p>Avg Mentees/Instructor</p>
                        </div>
                    </div>
                </div>

                <!-- Auto Assign Section -->
                <div class="card" style="margin-bottom: 20px; border: 2px solid var(--gold-light); background: linear-gradient(135deg, #fffdf5 0%, #fff9e6 100%);">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, var(--gold), var(--gold-light)); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                                <i class="fas fa-magic"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 16px; font-weight: 700; color: var(--dark-text); margin: 0 0 4px 0;">Auto Assign Unassigned Students</h3>
                                <p style="font-size: 13px; color: var(--light-text); margin: 0;">Evenly distribute <strong><?php echo $unassignedStudents; ?></strong> unassigned student(s) across <strong><?php echo count($instructors); ?></strong> instructor(s) using round-robin allocation based on current load.</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <button type="button" class="btn-assign" id="btn-auto-assign" onclick="autoAssignStudents()" style="padding: 12px 24px; font-size: 14px; border-radius: 10px; <?php echo ($unassignedStudents == 0 || count($instructors) == 0) ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>" <?php echo ($unassignedStudents == 0 || count($instructors) == 0) ? 'disabled' : ''; ?>>
                                <i class="fas fa-magic"></i> Auto Assign All
                            </button>
                        </div>
                    </div>
                    <?php if ($unassignedStudents > 0 && count($instructors) > 0): ?>
                    <div style="margin-top: 14px; padding: 10px 14px; background: rgba(184, 134, 11, 0.08); border-radius: 8px; font-size: 12px; color: var(--gold-dark);">
                        <i class="fas fa-info-circle"></i> 
                        Each instructor will receive approximately <strong><?php echo ceil($unassignedStudents / count($instructors)); ?></strong> student(s). 
                        Students will be assigned to instructors with the fewest current mentees first.
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="grid-2">
                    <!-- Students -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user-graduate"></i> Unassigned Students</h3>
                            <span class="mentee-count" id="studentsCount">0</span>
                        </div>
                         <div class="search-box">
                             <i class="fas fa-search"></i>
                             <input type="text" id="studentSearch" placeholder="Search students..." onkeyup="filterStudents()">
                         </div>
                         <!-- Bulk Assignment Bar -->
                         <div class="bulk-assign-bar" id="bulkAssignBar" style="display: none; margin-bottom: 16px; padding: 12px; background: var(--gold-lighter); border-radius: 10px; border: 1px solid var(--gold-light); align-items: center; gap: 12px;">
                             <span style="font-size: 13px; font-weight: 600; color: var(--gold-dark);">
                                 <i class="fas fa-check-square"></i> <span id="selectedCount">0</span> student(s) selected
                             </span>
                             <select class="instructor-select" id="bulk-instructor-select" style="flex: 1; max-width: 200px;">
                                 <option value="">Assign to...</option>
                                 <?php foreach ($instructors as $inst): 
                                     $displayName = $inst['first_name'] . ' ' . $inst['last_name'] . ' (' . ($inst['position'] ?? 'Instructor') . ')';
                                 ?>
                                 <option value="<?php echo $inst['id']; ?>"><?php echo htmlspecialchars($displayName); ?></option>
                                 <?php endforeach; ?>
                             </select>
                              <button type="button" class="btn-assign" id="btn-bulk-assign" disabled onclick="assignSelectedMentees()">
                                  <i class="fas fa-user-plus"></i> Assign Selected
                              </button>
                              <button type="button" class="btn-assign" style="background: var(--light-text);" onclick="clearSelection()">
                                  <i class="fas fa-times"></i> Clear
                              </button>
                         </div>
                         <!-- End Bulk Assignment Bar -->
                         <div id="studentsList">
                             <?php 
                             $assignedEmails = [];
                             foreach ($menteeAssignments as $assignments) {
                                 foreach ($assignments as $mentee) {
                                     $assignedEmails[] = $mentee['email'];
                                 }
                             }
                              $unassignedCount = 0;
                              $unassignedCount = 0;
                              foreach ($students as $student): 
                                $isAssigned = in_array($student['email'], $assignedEmails);
                                if ($isAssigned) continue;
                                $unassignedCount++;
                                $initials = strtoupper(substr($student['first_name'] ?? '', 0, 1) . substr($student['last_name'] ?? '', 0, 1));
                            ?>
                             <div class="list-item student-row" data-name="<?php echo strtolower($student['first_name'] . ' ' . $student['last_name']); ?>" data-email="<?php echo strtolower($student['email']); ?>" data-id="<?php echo strtolower($student['student_id'] ?? ''); ?>" data-student-id="<?php echo $student['id']; ?>" data-student-name="<?php echo htmlspecialchars(addslashes($student['first_name'] . ' ' . $student['last_name'])); ?>" onclick="toggleStudentSelection(<?php echo $student['id']; ?>)" style="cursor: pointer;">
                                 <input type="checkbox" class="student-checkbox" id="check-<?php echo $student['id']; ?>" data-student-id="<?php echo $student['id']; ?>" style="pointer-events: none; width: 18px; height: 18px; accent-color: var(--gold);">
                                 <div class="student-avatar"><?php echo $initials; ?></div>
                                 <div class="student-info">
                                     <div class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                     <div class="student-meta">
                                         <?php if (!empty($student['student_id'])): ?>
                                         <span class="student-id-badge"><?php echo htmlspecialchars($student['student_id']); ?></span>
                                         <?php endif; ?>
                                         <span><?php echo htmlspecialchars($student['email']); ?></span>
                                     </div>
                                 </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if ($unassignedCount == 0): ?>
                                <div class="empty-state">
                                    <i class="fas fa-check-circle"></i>
                                    <p>All students assigned!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Instructors -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chalkboard-teacher"></i> Instructors</h3>
                            <span class="mentee-count" id="instructorsCount"><?php echo count($instructors); ?></span>
                        </div>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="instructorSearch" placeholder="Search instructors..." onkeyup="filterInstructors()">
                        </div>
                        <div id="instructorsList">
                            <?php foreach ($instructors as $inst): 
                                $initials = strtoupper(substr($inst['first_name'] ?? '', 0, 1) . substr($inst['last_name'] ?? '', 0, 1));
                                $assignedMentees = $menteeAssignments[$inst['id']] ?? [];
                                $gradientFrom = $inst['avatar_gradient_from'] ?? '#667eea';
                                $gradientTo = $inst['avatar_gradient_to'] ?? '#764ba2';
                            ?>
                             <div class="instructor-item" id="instructor-item-<?php echo $inst['id']; ?>" data-name="<?php echo strtolower($inst['first_name'] . ' ' . $inst['last_name']); ?>" data-gradient-from="<?php echo htmlspecialchars($gradientFrom); ?>" data-gradient-to="<?php echo htmlspecialchars($gradientTo); ?>">
                                <div class="instructor-header" onclick="toggleMentees(<?php echo $inst['id']; ?>)">
                                    <div class="instructor-main">
                                        <div class="instructor-avatar" style="background: linear-gradient(135deg, <?php echo $gradientFrom; ?>, <?php echo $gradientTo; ?>);">
                                            <?php echo $initials; ?>
                                        </div>
                                        <div class="instructor-info">
                                            <div class="instructor-name"><?php echo htmlspecialchars($inst['first_name'] . ' ' . $inst['last_name']); ?></div>
                                            <div class="instructor-position"><?php echo htmlspecialchars($inst['position'] ?? 'Instructor'); ?></div>
                                        </div>
                                    </div>
                                    <div class="instructor-right">
                                        <span class="mentee-count"><?php echo count($assignedMentees); ?> mentees</span>
                                        <button class="btn-toggle" id="toggle-btn-<?php echo $inst['id']; ?>">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mentees-panel" id="mentees-panel-<?php echo $inst['id']; ?>">
                                    <?php if (empty($assignedMentees)): ?>
                                        <div class="empty-state">
                                            <i class="fas fa-user-slash"></i>
                                            <p>No mentees</p>
                                        </div>
                                    <?php else: ?>
                                        <div style="padding: 8px 12px; border-bottom: 1px solid #eee;">
                                            <input type="text" placeholder="Search mentees..." onkeyup="filterMentees(<?php echo $inst['id']; ?>, this.value)" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px;">
                                        </div>
                                        <div class="mentees-list" id="mentees-list-<?php echo $inst['id']; ?>">
                                            <?php foreach ($assignedMentees as $mentee): 
                                                $mInitials = strtoupper(substr($mentee['first_name'] ?? '', 0, 1) . substr($mentee['last_name'] ?? '', 0, 1));
                                            ?>
                                            <div class="mentee-item" data-mentee-name="<?php echo strtolower($mentee['first_name'] . ' ' . $mentee['last_name']); ?>">
                                                <div class="mentee-item-info">
                                                    <div class="mentee-avatar"><?php echo $mInitials; ?></div>
                                                    <div class="mentee-item-meta">
                                                        <div class="mentee-item-name"><?php echo htmlspecialchars($mentee['first_name'] . ' ' . $mentee['last_name']); ?></div>
                                                        <div style="font-size: 11px; color: #888;"><?php echo htmlspecialchars($mentee['email'] ?? ''); ?></div>
                                                    </div>
                                                </div>
                                                <button class="btn-remove-mentee" onclick="removeMentee(<?php echo $mentee['id']; ?>, <?php echo $inst['id']; ?>, '<?php echo htmlspecialchars(addslashes($mentee['first_name'] . ' ' . $mentee['last_name'])); ?>')">
                                                    <i class="fas fa-times"></i> Remove
                                                </button>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
     <script>
         // Global variable for modal
         let currentRemoveData = null;
         let selectedStudents = new Set();
         
         // Store instructor mentee counts dynamically
         let instructorMenteeCounts = {};
         <?php foreach ($instructors as $inst): ?>
         instructorMenteeCounts[<?php echo $inst['id']; ?>] = <?php echo count($menteeAssignments[$inst['id']] ?? []); ?>;
         <?php endforeach; ?>
         
          const bulkAssignBar = document.getElementById('bulkAssignBar');
          const bulkInstructorSelect = document.getElementById('bulk-instructor-select');
          const bulkAssignBtn = document.getElementById('btn-bulk-assign');
          const selectedCountSpan = document.getElementById('selectedCount');
          
          // Enable/disable button when instructor selection changes
          bulkInstructorSelect.addEventListener('change', function() {
              updateBulkAssignUI();
          });
          
           // Update the count display and button state
           function updateBulkAssignUI() {
               const count = selectedStudents.size;
               selectedCountSpan.textContent = count;
               bulkAssignBtn.disabled = count === 0 || bulkInstructorSelect.value === '';
           }
         
         // Toggle student selection
         function toggleStudentSelection(studentId) {
             const row = document.querySelector(`.student-row[data-student-id="${studentId}"]`);
             const checkbox = document.getElementById(`check-${studentId}`);
             
             if (!row || !checkbox) return;
             
             // Don't allow selection if already assigned
             if (row.classList.contains('student-assigned')) {
                 showToast('Cannot select already assigned student', 'error');
                 return;
             }
             
             if (selectedStudents.has(studentId)) {
                 selectedStudents.delete(studentId);
                 row.classList.remove('selected');
                 checkbox.checked = false;
             } else {
                 selectedStudents.add(studentId);
                 row.classList.add('selected');
                 checkbox.checked = true;
             }
             
             updateBulkAssignUI();
         }
         
         // Clear all selections
         function clearSelection() {
             selectedStudents.forEach(studentId => {
                 const row = document.querySelector(`.student-row[data-student-id="${studentId}"]`);
                 const checkbox = document.getElementById(`check-${studentId}`);
                 if (row) row.classList.remove('selected');
                 if (checkbox) checkbox.checked = false;
             });
             selectedStudents.clear();
             bulkInstructorSelect.value = '';
             updateBulkAssignUI();
         }
         
          // Bulk assign selected students
          async function assignSelectedMentees() {
              console.log('assignSelectedMentees called');
              console.log('Selected students:', selectedStudents);
              console.log('Instructor:', bulkInstructorSelect.value);
              
              const instructorId = bulkInstructorSelect.value;
              if (!instructorId) {
                  showToast('Please select an instructor first.', 'error');
                  return;
              }
              
              if (selectedStudents.size === 0) {
                  showToast('No students selected.', 'error');
                  return;
              }
              
              const instructorName = bulkInstructorSelect.options[bulkInstructorSelect.selectedIndex].text.split(' (')[0];
              const studentIds = Array.from(selectedStudents);
              const total = studentIds.length;
              let successCount = 0;
              let errorCount = 0;
              
              bulkAssignBtn.disabled = true;
              bulkAssignBtn.innerHTML = '<span class="loading"></span> Assigning...';
              
              // Process each student assignment sequentially
              for (const studentId of studentIds) {
                  const row = document.querySelector(`.student-row[data-student-id="${studentId}"]`);
                  const checkbox = document.getElementById(`check-${studentId}`);
                  const studentName = row ? row.dataset.studentName : 'Student';
                  console.log('Assigning student:', studentId, studentName);
                  
                   try {
                       const response = await fetch('../../data/assign_mentee.php', {
                           method: 'POST',
                           headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                           body: 'instructor_id=' + encodeURIComponent(instructorId) + '&student_id=' + encodeURIComponent(studentId)
                       });
                       
                       console.log('Response status:', response.status);
                       const data = await response.json();
                       console.log('Response data:', data);
                       
                       if (data.success) {
                           successCount++;
                           const menteeId = data.mentee_id; // New mentee ID from server
                           // Update instructor panel with menteeId (for removal)
                           updateInstructorData(instructorId, menteeId, studentName, true);
                           if (row) {
                               row.remove();
                           }
                           if (checkbox) {
                               checkbox.checked = false;
                               checkbox.disabled = true;
                           }
                       } else {
                           errorCount++;
                           showToast(`Failed to assign ${studentName}: ${data.message}`, 'error');
                       }
                   } catch (err) {
                      console.error('Error:', err);
                      errorCount++;
                      showToast(`Error assigning ${studentName}: ${err.message}`, 'error');
                  }
              }
              
               // Clear selections and reset UI
               selectedStudents.clear();
               bulkInstructorSelect.value = '';
               updateBulkAssignUI();
               bulkAssignBtn.innerHTML = '<i class="fas fa-user-plus"></i> Assign Selected';
               
               // Show summary toast
               if (successCount > 0 && errorCount === 0) {
                   showToast(`Successfully assigned ${successCount} student(s) to ${instructorName}!`, 'success');
               } else if (successCount > 0) {
                   showToast(`Assigned: ${successCount}, Failed: ${errorCount}`, 'success');
               }
               
               // Update counts
               updateStudentCount();
            }
           
           // Update instructor mentee count and panel
            function updateInstructorData(instructorId, menteeId, studentName, isAdding = true) {
                // Update count
                instructorMenteeCounts[instructorId] = (instructorMenteeCounts[instructorId] || 0) + (isAdding ? 1 : -1);
                const count = Math.max(0, instructorMenteeCounts[instructorId]);
                
                const panel = document.getElementById('mentees-panel-' + instructorId);
                if (!panel) return;
                
                const instructorItem = panel.closest('.instructor-item');
                if (instructorItem) {
                    const countSpan = instructorItem.querySelector('.mentee-count');
                    if (countSpan) {
                        countSpan.textContent = count + ' mentees';
                    }
                }
                
                // Update mentees list inside panel
                let list = panel.querySelector('.mentees-list');
                const emptyState = panel.querySelector('.empty-state');
                
                if (isAdding) {
                    // Remove empty state if present
                    if (emptyState) emptyState.remove();
                    
                    // Create list if doesn't exist
                    if (!list) {
                        list = document.createElement('div');
                        list.className = 'mentees-list';
                        panel.appendChild(list);
                    }
                    
                    // Avoid duplicates
                    if (list.querySelector(`[data-mentee-id="${menteeId}"]`)) return;
                    
                    // Create mentee item
                    const menteeItem = document.createElement('div');
                    menteeItem.className = 'mentee-item';
                    menteeItem.dataset.menteeId = menteeId;
                    menteeItem.innerHTML = `
                        <div class="mentee-item-info">
                            <div class="mentee-avatar" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa);">
                                ${studentName.charAt(0).toUpperCase()}
                            </div>
                            <div class="mentee-item-meta">
                                <div class="mentee-item-name">${studentName}</div>
                            </div>
                        </div>
                        <button class="btn-remove-mentee" onclick="removeMentee(${menteeId}, ${instructorId}, '${studentName.replace(/'/g, "\\'")}')">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    `;
                    list.appendChild(menteeItem);
                } else {
                    // Removal
                    if (list) {
                        const item = list.querySelector(`[data-mentee-id="${menteeId}"]`);
                        if (item) item.remove();
                        
                        // If list is empty, show empty state
                        if (list.children.length === 0) {
                            panel.innerHTML = `
                                <div class="empty-state">
                                    <i class="fas fa-user-slash"></i>
                                    <p>No mentees</p>
                                </div>
                            `;
                        }
                    }
                }
            }
          
         function showToast(message, type = 'info') {
             const container = document.getElementById('toastContainer');
             const toast = document.createElement('div');
             toast.className = `toast ${type}`;
             
             let icon = 'info-circle';
             if (type === 'success') icon = 'check-circle';
             if (type === 'error') icon = 'exclamation-circle';
             
             toast.innerHTML = `
                 <i class="fas fa-${icon}"></i>
                 <span>${message}</span>
                 <button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
             `;
             
             container.appendChild(toast);
             
             setTimeout(() => {
                 toast.style.animation = 'slideIn 0.4s ease reverse';
                 setTimeout(() => toast.remove(), 400);
             }, 4000);
         }
        
        function showRemoveModal(menteeId, instructorId, menteeName) {
            currentRemoveData = { menteeId, instructorId, menteeName };
            document.getElementById('modal-mentee-name').textContent = menteeName;
            document.getElementById('removeModal').classList.add('show');
        }
        
        function hideRemoveModal() {
            document.getElementById('removeModal').classList.remove('show');
            currentRemoveData = null;
        }
        
        function confirmRemoveMentee() {
            if (!currentRemoveData) return;
            
            const { menteeId, instructorId, menteeName } = currentRemoveData;
            hideRemoveModal();
            
            fetch('../../data/remove_mentee.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'mentee_id=' + encodeURIComponent(menteeId) + '&instructor_id=' + encodeURIComponent(instructorId)
            })
            .then(r => r.json())
             .then(data => {
                 if (data.success) {
                     showToast(`<strong>${menteeName}</strong> removed successfully!`, 'success');
                     // Update instructor panel dynamically
                     updateInstructorData(instructorId, menteeId, menteeName, false);
                     // Update student count (since a mentee moved back to unassigned, we'd need to re-fetch students? Actually removal removes assignment but the student might still be a mentee elsewhere? In this system, removal means unassigning from that instructor, student remains as mentee in database but mentor_id set to null? Let's check remove_mentee.php logic.
                     // For now, just reload to refresh the unassigned students list.
                     setTimeout(() => location.reload(), 1200);
                 } else {
                    showToast(data.message || 'Failed to remove mentee', 'error');
                }
            })
            .catch(err => {
                showToast('Error: ' + err.message, 'error');
            });
        }
        
        function updateAssignButton(selectElement) {
            const instructorId = selectElement.value;
            const btn = selectElement.nextElementSibling;
            const studentId = selectElement.dataset.studentId;
            const studentName = selectElement.dataset.studentName;
            
            if (instructorId && studentId && studentName) {
                const instructorName = selectElement.options[selectElement.selectedIndex].text.split(' (')[0];
                btn.disabled = false;
                btn.innerHTML = `<i class="fas fa-user-plus"></i> Assign to ${instructorName}`;
                // Store reference for click handler
                btn.dataset.studentId = studentId;
                btn.dataset.studentName = studentName;
            } else {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-user-plus"></i> Assign';
                delete btn.dataset.studentId;
                delete btn.dataset.studentName;
             }
         }
         
          function assignMentee(studentId, studentName) {
              const select = document.getElementById('instructor-select-' + studentId);
              if (!select) return;
              const instructorId = select.value;
              if (!instructorId) {
                  showToast('Please select an instructor first.', 'error');
                  return;
              }
              
              const row = select.closest('.list-item');
              const btn = row.querySelector('.btn-assign');
              const originalHTML = btn.innerHTML;
              btn.innerHTML = '<span class="loading"></span> Assigning...';
              btn.disabled = true;
              
              fetch('../../data/assign_mentee.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                  body: 'instructor_id=' + encodeURIComponent(instructorId) + '&student_id=' + encodeURIComponent(studentId)
              })
               .then(r => r.json())
               .then(data => {
                   if (data.success) {
                       showToast(`<strong>${studentName}</strong> assigned successfully!`, 'success');
                       // Update instructor panel with the new mentee ID
                       const menteeId = data.mentee_id;
                       updateInstructorData(instructorId, menteeId, studentName, true);
                       // Disable checkbox
                       const checkbox = document.getElementById('check-' + studentId);
                       if (checkbox) {
                           checkbox.checked = false;
                           checkbox.disabled = true;
                       }
                       // Reset select
                       select.value = '';
                       // Update button
                       btn.innerHTML = '<i class="fas fa-check"></i> Assigned';
                       btn.classList.add('success');
                       // Remove row with fade out
                       setTimeout(() => {
                           row.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                           row.style.opacity = '0';
                           row.style.transform = 'translateX(-20px)';
                           setTimeout(() => {
                               row.remove();
                               updateStudentCount();
                           }, 500);
                       }, 800);
                   } else {
                      showToast(data.message || 'Failed to assign mentee', 'error');
                      btn.innerHTML = originalHTML;
                      btn.disabled = false;
                  }
              })
              .catch(err => {
                  showToast('Error: ' + err.message, 'error');
                  btn.innerHTML = originalHTML;
                  btn.disabled = false;
              });
          }
        
         function updateStudentCount() {
             const studentRows = document.querySelectorAll('.student-row:not([style*="display: none"])');
             document.getElementById('studentsCount').textContent = studentRows.length;
             
             // Show/hide bulk assign bar based on if any unassigned students exist
             const totalRows = document.querySelectorAll('.student-row').length;
             if (totalRows > 0) {
                 bulkAssignBar.style.display = 'flex';
             } else {
                 bulkAssignBar.style.display = 'none';
             }
             
             updateBulkAssignUI();
         }
        
        function removeMentee(menteeId, instructorId, menteeName) {
            showRemoveModal(menteeId, instructorId, menteeName);
        }
        
        function toggleMentees(instructorId) {
            const panel = document.getElementById('mentees-panel-' + instructorId);
            const btn = document.getElementById('toggle-btn-' + instructorId);
            panel.classList.toggle('show');
            btn.classList.toggle('active');
        }
        
         function filterStudents() {
             const search = document.getElementById('studentSearch').value.toLowerCase();
             const rows = document.querySelectorAll('.student-row');
             rows.forEach(row => {
                 const name = row.dataset.name || '';
                 const email = row.dataset.email || '';
                 const id = row.dataset.id || '';
                 
                 if (name.includes(search) || email.includes(search) || id.includes(search)) {
                     row.style.display = 'flex';
                 } else {
                     row.style.display = 'none';
                 }
             });
             updateStudentCount();
         }
        
        function filterInstructors() {
            const search = document.getElementById('instructorSearch').value.toLowerCase();
            const items = document.querySelectorAll('.instructor-item');
            items.forEach(item => {
                const name = item.dataset.name || '';
                const instructorId = item.id.replace('instructor-item-', '');
                
                // Check instructor name
                const instructorMatch = name.includes(search);
                
                // Check mentee names
                let menteeMatch = false;
                const menteeList = item.querySelector('.mentees-list');
                if (menteeList && search) {
                    const mentees = menteeList.querySelectorAll('.mentee-item');
                    mentees.forEach(mentee => {
                        const menteeName = mentee.dataset.menteeName || '';
                        if (menteeName.includes(search)) {
                            menteeMatch = true;
                        }
                    });
                }
                
                if (instructorMatch || menteeMatch) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function filterMentees(instructorId, searchTerm) {
            const list = document.getElementById('mentees-list-' + instructorId);
            if (!list) return;
            const mentees = list.querySelectorAll('.mentee-item');
            mentees.forEach(item => {
                const name = item.dataset.menteeName || '';
                if (name.includes(searchTerm.toLowerCase())) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
          // Auto Assign All Unassigned Students
          async function autoAssignStudents() {
              const btn = document.getElementById('btn-auto-assign');
              const studentRows = document.querySelectorAll('.student-row');
              
              if (studentRows.length === 0) {
                  showToast('No unassigned students to assign.', 'error');
                  return;
              }
              
              // Get all instructor IDs and their current mentee counts
              const instructorIds = [];
              const instructorNames = {};
              <?php foreach ($instructors as $inst): ?>
              instructorIds.push(<?php echo $inst['id']; ?>);
              instructorNames[<?php echo $inst['id']; ?>] = "<?php echo htmlspecialchars(addslashes($inst['first_name'] . ' ' . $inst['last_name'])); ?>";
              <?php endforeach; ?>
              
              if (instructorIds.length === 0) {
                  showToast('No instructors available for assignment.', 'error');
                  return;
              }
              
              // Confirm action
              if (!confirm(`Are you sure you want to auto-assign ${studentRows.length} unassigned student(s) evenly across ${instructorIds.length} instructor(s)?`)) {
                  return;
              }
              
              btn.disabled = true;
              btn.innerHTML = '<span class="loading"></span> Auto Assigning...';
              
              // Sort instructors by current mentee count (ascending) for balanced distribution
              instructorIds.sort((a, b) => (instructorMenteeCounts[a] || 0) - (instructorMenteeCounts[b] || 0));
              
              // Collect all unassigned student data
              const studentsToAssign = [];
              studentRows.forEach(row => {
                  studentsToAssign.push({
                      id: row.dataset.studentId,
                      name: row.dataset.studentName
                  });
              });
              
              let successCount = 0;
              let errorCount = 0;
              let instructorIndex = 0;
              
              // Round-robin assignment based on least loaded instructor
              for (const student of studentsToAssign) {
                  // Re-sort by current count each iteration for true load balancing
                  instructorIds.sort((a, b) => (instructorMenteeCounts[a] || 0) - (instructorMenteeCounts[b] || 0));
                  const targetInstructorId = instructorIds[0]; // Always assign to least loaded
                  
                  try {
                      const response = await fetch('../../data/assign_mentee.php', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                          body: 'instructor_id=' + encodeURIComponent(targetInstructorId) + '&student_id=' + encodeURIComponent(student.id)
                      });
                      
                      const data = await response.json();
                      
                      if (data.success) {
                          successCount++;
                          const menteeId = data.mentee_id;
                          // Update instructor panel
                          updateInstructorData(targetInstructorId, menteeId, student.name, true);
                          // Remove student row
                          const row = document.querySelector(`.student-row[data-student-id="${student.id}"]`);
                          if (row) row.remove();
                      } else {
                          errorCount++;
                          console.error(`Failed to assign ${student.name}: ${data.message}`);
                      }
                  } catch (err) {
                      errorCount++;
                      console.error(`Error assigning ${student.name}: ${err.message}`);
                  }
              }
              
              // Show results
              if (successCount > 0 && errorCount === 0) {
                  showToast(`Successfully auto-assigned ${successCount} student(s) across ${instructorIds.length} instructor(s)!`, 'success');
              } else if (successCount > 0) {
                  showToast(`Auto-assigned ${successCount} student(s). ${errorCount} failed.`, 'success');
              } else {
                  showToast(`Auto-assign failed. ${errorCount} error(s) occurred.`, 'error');
              }
              
              // Reset button
              btn.innerHTML = '<i class="fas fa-magic"></i> Auto Assign All';
              btn.disabled = true; // Keep disabled since all students are now assigned
              
              // Update counts
              updateStudentCount();
              
              // Clear any selections
              clearSelection();
              
              // Reload page after short delay to refresh all data
              setTimeout(() => location.reload(), 2000);
          }

          // Auto-dismiss toasts after 4 seconds
          document.addEventListener('DOMContentLoaded', function() {
              setTimeout(() => {
                  document.querySelectorAll('.toast').forEach(t => t.remove());
              }, 4000);
              
              // Update counts and bulk bar visibility
              updateStudentCount();
              
              // Event listener for bulk instructor select change
              document.getElementById('bulk-instructor-select').addEventListener('change', updateBulkAssignUI);
          });
    </script>
    
    <!-- Remove Confirmation Modal -->
    <div class="modal-overlay" id="removeModal">
        <div class="modal-content">
            <div class="modal-icon"><i class="fas fa-user-minus"></i></div>
            <div class="modal-title">Remove Mentee</div>
            <div class="modal-message">Are you sure you want to remove <strong id="modal-mentee-name"></strong> from this instructor?</div>
            <div class="modal-actions">
                <button class="btn-modal btn-modal-cancel" onclick="hideRemoveModal()">Cancel</button>
                <button class="btn-modal btn-modal-confirm" onclick="confirmRemoveMentee()">Remove</button>
            </div>
        </div>
    </div>
</body>
</html>

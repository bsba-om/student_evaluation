<?php
require_once '../../../data/session_security.php';

$role_access = check_role_access('instructor');
$show_role_modal = !$role_access['allowed'];

$instructor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Jane Teacher';

$stats = [
    'total_reports' => 0,
    'pdf_count' => 0,
    'excel_count' => 0,
    'total_downloads' => 0,
    'recent_downloads' => []
];

$report_types = ['pdf', 'excel', 'csv', 'json'];
$all_reports = [];

if (!$show_role_modal) {
    require_once '../../../data/config.php';
    
    try {
        // Get report statistics from reports table
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM reports");
        $stats['total_reports'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM reports WHERE report_type = 'pdf'");
        $stats['pdf_count'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM reports WHERE report_type = 'excel'");
        $stats['excel_count'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT SUM(download_count) as total FROM reports");
        $total = $stmt->fetchColumn();
        $stats['total_downloads'] = $total ?: 0;
        
        // Get all reports
        $stmt = $pdo->query("SELECT * FROM reports ORDER BY created_at DESC");
        $all_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recent activity (sample - would need activity log table in production)
        $stmt = $pdo->query("SELECT report_name, download_count, created_at FROM reports ORDER BY created_at DESC LIMIT 5");
        $stats['recent_downloads'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $stats['total_reports'] = 0;
        $all_reports = [];
    }
}

// Sample data for reports generation (since we don't have all tables)
$mock_data = [
    'majors' => [
        ['name' => 'Operational Management', 'count' => 2, 'gradient' => 'linear-gradient(135deg, #d4a843, #b8922f)'],
        ['name' => 'Marketing Management', 'count' => 1, 'gradient' => 'linear-gradient(135deg, #ec4899, #f472b6)'],
        ['name' => 'Financial Management', 'count' => 1, 'gradient' => 'linear-gradient(135deg, #3b82f6, #60a5fa)']
    ],
    'year_levels' => [
        ['label' => '1st Year', 'count' => 0, 'color' => '#3b82f6'],
        ['label' => '2nd Year', 'count' => 2, 'color' => '#10b981'],
        ['label' => '3rd Year', 'count' => 2, 'color' => '#8b5cf6'],
        ['label' => '4th Year', 'count' => 1, 'color' => '#f59e0b']
    ]
];

$assigned_mentees = [];
$mentee_count = 0;
$new_mentees = [];
$last_viewed = null;

if (!$show_role_modal && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT m.*, s.major_id, s.year_level, maj.display_name as major_name 
                               FROM mentees m 
                               LEFT JOIN students s ON m.student_id = s.id 
                               LEFT JOIN majors maj ON s.major_id = maj.id 
                               WHERE m.mentor_id = ? 
                               ORDER BY m.created_at DESC");
        $stmt->execute([$instructor_id]);
        $assigned_mentees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mentee_count = count($assigned_mentees);
        
        $last_viewed = isset($_SESSION['last_mentee_view']) ? $_SESSION['last_mentee_view'] : null;
        
        foreach ($assigned_mentees as $mentee) {
            if ($last_viewed && strtotime($mentee['created_at']) > strtotime($last_viewed)) {
                $new_mentees[] = $mentee;
            } elseif (!$last_viewed && strtotime($mentee['created_at']) > strtotime('-24 hours')) {
                $new_mentees[] = $mentee;
            }
        }
        
        if ($mentee_count > 0 && !isset($_SESSION['last_mentee_view'])) {
            $_SESSION['last_mentee_view'] = date('Y-m-d H:i:s');
        }
    } catch (PDOException $e) {
        $assigned_mentees = [];
        $mentee_count = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../../media/LOGO.jpg" type="image/jpeg">
    <title>Reports - Faculty Evaluation System</title>
    <link rel="stylesheet" href="../../../css/common.css">
    <link rel="stylesheet" href="../style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --gold: #d4a843;
            --gold-light: #e8c768;
            --gold-lighter: #f5e8c8;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            gap: 16px;
        }
        
        .page-title-area h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark-text);
            margin: 0 0 4px 0;
        }
        
        .page-title-area p {
            color: var(--light-text);
            margin: 0;
            font-size: 14px;
        }
        
        .page-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            border: none;
        }
        
        .btn-primary {
            background: var(--gold);
            color: white;
        }
        
        .btn-primary:hover {
            background: #b8922f;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(212, 168, 67, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: var(--dark-text);
            border: 1px solid var(--border-light);
        }
        
        .btn-secondary:hover {
            background: var(--cream);
            border-color: var(--gold);
        }
        
        .btn-icon {
            padding: 10px;
            border-radius: 8px;
            background: transparent;
            border: 1px solid var(--border-light);
            color: var(--light-text);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-icon:hover {
            background: var(--gold);
            color: white;
            border-color: var(--gold);
        }
        
        .tabs-container {
            margin-bottom: 24px;
        }
        
        .tabs {
            display: flex;
            gap: 4px;
            background: white;
            padding: 4px;
            border-radius: 12px;
            border: 1px solid var(--border-light);
            margin-bottom: 24px;
        }
        
        .tab {
            flex: 1;
            padding: 12px 24px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            background: transparent;
            color: var(--light-text);
            transition: all 0.2s ease;
        }
        
        .tab.active {
            background: var(--gold);
            color: white;
            box-shadow: 0 2px 8px rgba(212, 168, 67, 0.3);
        }
        
        .tab:hover:not(.active) {
            background: var(--cream);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid var(--border-light);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .stat-card:hover {
            border-color: var(--gold-light);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--gold);
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .stat-icon.blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .stat-icon.green { background: linear-gradient(135deg, #059669, #34d399); }
        .stat-icon.purple { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        .stat-icon.orange { background: linear-gradient(135deg, #d4a843, #b8922f); }
        
        .stat-type {
            font-size: 12px;
            color: var(--light-text);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: var(--dark-text);
            line-height: 1;
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--light-text);
            font-weight: 500;
            margin-top: 8px;
        }
        
        .stat-change {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            margin-top: auto;
            padding-top: 12px;
        }
        
        .stat-change.positive { color: var(--success); }
        .stat-change.negative { color: var(--danger); }
        
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .report-card {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border-light);
            overflow: hidden;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .report-card:hover {
            border-color: var(--gold-light);
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        
        .report-card-header {
            padding: 24px;
            background: var(--cream);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .report-icon {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            margin-bottom: 16px;
        }
        
        .report-card-body {
            padding: 24px;
        }
        
        .report-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-text);
            margin: 0 0 8px 0;
            line-height: 1.3;
        }
        
        .report-description {
            font-size: 14px;
            color: var(--light-text);
            margin: 0 0 16px 0;
            line-height: 1.5;
        }
        
        .report-meta {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .report-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .report-badge.pdf {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }
        
        .report-badge.excel {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }
        
        .report-badge.csv {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        
        .report-card-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .download-stats {
            font-size: 12px;
            color: var(--light-text);
        }
        
        .download-stats i {
            margin-right: 4px;
        }
        
        .generated-date {
            font-size: 12px;
            color: var(--light-text);
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-buttons .btn {
            padding: 8px 16px;
            font-size: 12px;
        }
        
        .generator-section {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border-light);
            padding: 32px;
            margin-bottom: 32px;
        }
        
        .generator-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .generator-header i {
            font-size: 28px;
            color: var(--gold);
        }
        
        .generator-header h2 {
            margin: 0;
            font-size: 22px;
            color: var(--dark-text);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark-text);
        }
        
        .form-control {
            padding: 10px 14px;
            border: 2px solid var(--border-light);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            color: var(--dark-text);
            background: white;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 168, 67, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 280px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(60px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .toast.success { background: linear-gradient(135deg, #059669, #34d399); }
        .toast.error { background: linear-gradient(135deg, #dc2626, #f87171); }
        .toast.info { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: var(--light-text);
        }
        
        .empty-state i {
            font-size: 64px;
            color: var(--gold-light);
            margin-bottom: 16px;
            opacity: 0.6;
        }
        
        .empty-state h3 {
            color: var(--dark-text);
            margin-bottom: 8px;
        }
        
        @media (max-width: 768px) {
            .reports-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .generator-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body class="dashboard-page">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../../../media/LOGO.jpg" alt="Logo" class="sidebar-logo" style="width: 70px; height: 70px; border-radius: 16px; object-fit: cover; border: 3px solid white; background: white; padding: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
            <div class="sidebar-brand">
                <span class="sidebar-brand-name">IBM</span>
            </div>
        </div>
        
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo htmlspecialchars($user_name); ?></span>
                <span class="sidebar-user-role">Instructor</span>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="sidebar-nav-label">Menu</div>
            <a href="../dashboard.php" class="sidebar-nav-item">
                <i class="fas fa-chart-pie"></i>
                <span>Overview</span>
            </a>
            <a href="students.php" class="sidebar-nav-item">
                <i class="fas fa-user-graduate"></i>
                <span>Students mentees</span>
            </a>
            <a href="feedback.php" class="sidebar-nav-item">
                <i class="fas fa-comment-dots"></i>
                <span>Feedback</span>
            </a>
            <a href="reports.php" class="sidebar-nav-item active">
                <i class="fas fa-file-alt"></i>
                <span>Reports</span>
            </a>
            <a href="profile.php" class="sidebar-nav-item">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </nav>
    </aside>

    <div class="main-content" style="position: relative;">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <div class="topbar-title">Reports</div>
                    <div class="topbar-subtitle">Instructor Panel</div>
                </div>
            </div>
            
            <div class="topbar-right">
                <div class="topbar-date">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('F j, Y'); ?></span>
                </div>
                <a href="../../../data/logout.php" class="topbar-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="page-header">
                <div class="page-title-area">
                    <h1>Reports & Analytics</h1>
                    <p>Generate, download, and manage reports</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="showGenerator()">
                        <i class="fas fa-plus"></i> Generate New Report
                    </button>
                    <button class="btn btn-secondary" onclick="refreshData()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-type">Total Reports</div>
                            <div class="stat-value"><?php echo number_format($stats['total_reports']); ?></div>
                        </div>
                        <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
                    </div>
                    <div class="stat-label">Available reports</div>
                    <div class="stat-change positive"><i class="fas fa-check"></i> Ready to use</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-type">PDF Reports</div>
                            <div class="stat-value"><?php echo $stats['pdf_count']; ?></div>
                        </div>
                        <div class="stat-icon orange"><i class="fas fa-file-pdf"></i></div>
                    </div>
                    <div class="stat-label">Document format</div>
                    <div class="stat-change positive"><i class="fas fa-arrow-up"></i> Printable</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-type">Excel Reports</div>
                            <div class="stat-value"><?php echo $stats['excel_count']; ?></div>
                        </div>
                        <div class="stat-icon green"><i class="fas fa-file-excel"></i></div>
                    </div>
                    <div class="stat-label">Spreadsheet format</div>
                    <div class="stat-change positive"><i class="fas fa-arrow-up"></i> Editable</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-type">Total Downloads</div>
                            <div class="stat-value"><?php echo number_format($stats['total_downloads']); ?></div>
                        </div>
                        <div class="stat-icon purple"><i class="fas fa-download"></i></div>
                    </div>
                    <div class="stat-label">All-time downloads</div>
                    <div class="stat-change positive"><i class="fas fa-chart-line"></i> Active usage</div>
                </div>
            </div>

            <!-- Mentee Information Report Section -->
            <?php if ($mentee_count > 0): ?>
            <div class="mentee-report-section" style="margin-bottom: 32px;">
                <?php if (!empty($new_mentees)): ?>
                <div class="new-mentee-notification" style="background: linear-gradient(135deg, #059669, #34d399); border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 16px; color: white; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);">
                    <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-user-plus" style="font-size: 20px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 16px; font-weight: 700; margin-bottom: 4px;">
                            New Mentee<?php echo count($new_mentees) > 1 ? 's' : ''; ?> Assigned!
                        </div>
                        <div style="font-size: 14px; opacity: 0.95;">
                            <?php if (count($new_mentees) == 1): ?>
                                <strong><?php echo htmlspecialchars(trim($new_mentees[0]['first_name'] . ' ' . $new_mentees[0]['last_name'])); ?></strong> has been assigned to you by the Program Head.
                            <?php else: ?>
                                <strong><?php echo count($new_mentees); ?></strong> students have been assigned to you: 
                                <?php 
                                $names = [];
                                foreach ($new_mentees as $nm) {
                                    $names[] = htmlspecialchars(trim($nm['first_name'] . ' ' . $nm['last_name']));
                                }
                                echo implode(', ', $names);
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button onclick="this.parentElement.style.display='none'" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="generator-header" style="margin-bottom: 20px;">
                    <i class="fas fa-user-graduate" style="font-size: 28px; color: var(--gold);"></i>
                    <h2 style="margin: 0; font-size: 22px; color: var(--dark-text);">My Mentees</h2>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px;">
                    <?php foreach ($assigned_mentees as $mentee): 
                        $fullName = htmlspecialchars(trim(($mentee['first_name'] ?? '') . ' ' . ($mentee['last_name'] ?? '')));
                        $initials = strtoupper(substr($mentee['first_name'] ?? '', 0, 1) . substr($mentee['last_name'] ?? '', 0, 1));
                        $assignedDate = !empty($mentee['created_at']) ? date('M j, Y', strtotime($mentee['created_at'])) : '-';
                    ?>
                    <div style="background: white; border-radius: 12px; padding: 14px; border: 1px solid var(--border-light); box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.2s ease;" onmouseover="this.style.borderColor='var(--gold-light)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.borderColor='var(--border-light)'; this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.06)'">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #8b5cf6, #a78bfa); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 12px; flex-shrink: 0;">
                                <?php echo $initials; ?>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 13px; font-weight: 700; color: var(--dark-text); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo $fullName; ?>
                                </div>
                                <div style="font-size: 11px; color: var(--light-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo htmlspecialchars($mentee['email'] ?? ''); ?>
                                </div>
                            </div>
                        </div>
                        <div style="margin-top: 10px; display: flex; gap: 6px; flex-wrap: wrap;">
                            <?php if (!empty($mentee['major_name'])): ?>
                            <span style="padding: 2px 8px; background: rgba(212, 168, 67, 0.12); color: #9a7b0a; border-radius: 12px; font-size: 10px; font-weight: 600;">
                                <?php echo htmlspecialchars($mentee['major_name']); ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($mentee['year_level'])): ?>
                            <span style="padding: 2px 8px; background: rgba(59, 130, 246, 0.12); color: #2563eb; border-radius: 12px; font-size: 10px; font-weight: 600;">
                                <?php echo htmlspecialchars($mentee['year_level']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border-light);">
                            <span style="font-size: 10px; color: var(--light-text);">
                                <i class="fas fa-calendar-alt" style="margin-right: 4px;"></i>
                                <?php echo $assignedDate; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: 16px; font-size: 14px; color: var(--light-text); text-align: center;">
                    <strong><?php echo $mentee_count; ?></strong> mentee<?php echo $mentee_count != 1 ? 's' : ''; ?> assigned to you
                </div>
            </div>
            <?php elseif (!$show_role_modal): ?>
            <div class="mentee-report-section" style="margin-bottom: 32px; padding: 40px; background: white; border-radius: 16px; border: 1px solid var(--border-light); text-align: center;">
                <i class="fas fa-user-slash" style="font-size: 48px; color: var(--gold-light); opacity: 0.5; margin-bottom: 16px;"></i>
                <h3 style="margin: 0 0 8px 0; font-size: 18px; color: var(--dark-text);">No Mentees Assigned</h3>
                <p style="margin: 0; font-size: 14px; color: var(--light-text);">No students have been assigned to you yet. Contact the Program Head for mentee assignments.</p>
            </div>
            <?php endif; ?>

            <!-- Report Generator -->
            <div class="generator-section" id="generatorSection" style="display: none;">
                <div class="generator-header">
                    <i class="fas fa-magic"></i>
                    <h2>Generate Custom Report</h2>
                </div>
                <form id="reportForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="reportName">Report Name</label>
                            <input type="text" class="form-control" id="reportName" placeholder="e.g., Student Performance Report" required>
                        </div>
                        <div class="form-group">
                            <label for="reportType">Report Type</label>
                            <select class="form-control" id="reportType" required>
                                <option value="">Select type...</option>
                                <option value="pdf">PDF Document</option>
                                <option value="excel">Excel Spreadsheet</option>
                                <option value="csv">CSV File</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dateFrom">Date From (Optional)</label>
                            <input type="date" class="form-control" id="dateFrom">
                        </div>
                        <div class="form-group">
                            <label for="dateTo">Date To (Optional)</label>
                            <input type="date" class="form-control" id="dateTo">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="hideGenerator()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-file-download"></i> Generate Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Reports Grid -->
            <div class="reports-grid" id="reportsGrid">
                <?php if (empty($all_reports)): ?>
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="fas fa-file-excel"></i>
                    <h3>No Reports Available</h3>
                    <p>No pre-generated reports found. Generate your first report to see it here.</p>
                    <button class="btn btn-primary" onclick="showGenerator()" style="margin-top: 16px;">
                        <i class="fas fa-plus"></i> Create Report
                    </button>
                </div>
                <?php else: ?>
                    <?php foreach ($all_reports as $report): 
                        $icon_class = !empty($report['icon_class']) ? $report['icon_class'] : 'fas fa-file-alt';
                        $gradient = $report['report_type'] == 'pdf' 
                            ? 'linear-gradient(135deg, #dc2626, #ef4444)' 
                            : 'linear-gradient(135deg, #059669, #34d399)';
                        $description = !empty($report['report_description']) ? $report['report_description'] : 'Report generated by the system';
                        $downloads = $report['download_count'] ?? 0;
                        $created = $report['created_at'] ? date('M j, Y', strtotime($report['created_at'])) : 'N/A';
                    ?>
                    <div class="report-card">
                        <div class="report-card-header">
                            <div class="report-icon" style="background: <?php echo $gradient; ?>">
                                <i class="<?php echo htmlspecialchars($icon_class); ?>"></i>
                            </div>
                            <span class="report-badge <?php echo $report['report_type']; ?>">
                                <?php echo strtoupper($report['report_type']); ?>
                            </span>
                        </div>
                        <div class="report-card-body">
                            <h3 class="report-title"><?php echo htmlspecialchars($report['report_name']); ?></h3>
                            <p class="report-description"><?php echo htmlspecialchars($description); ?></p>
                        </div>
                        <div class="report-card-footer">
                            <div class="download-stats">
                                <i class="fas fa-download"></i> <?php echo number_format($downloads); ?> downloads
                            </div>
                            <div class="generated-date">
                                <?php echo $created; ?>
                            </div>
                        </div>
                        <div class="report-card-actions" style="padding: 16px 24px; border-top: 1px solid var(--border-light); display: flex; gap: 8px;">
                            <button class="btn btn-primary" onclick="downloadReport('<?php echo $report['id']; ?>', '<?php echo addslashes($report['report_name']); ?>', '<?php echo $report['report_type']; ?>')">
                                <i class="fas fa-download"></i> Download
                            </button>
                            <button class="btn btn-secondary" onclick="showToast('Preview coming soon!', 'info')">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle';
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

        function showGenerator() {
            document.getElementById('generatorSection').style.display = 'block';
            document.getElementById('generatorSection').scrollIntoView({ behavior: 'smooth' });
        }

        function hideGenerator() {
            document.getElementById('generatorSection').style.display = 'none';
            document.getElementById('reportForm').reset();
        }

        function refreshData() {
            location.reload();
        }

        function downloadReport(reportId, name, type) {
            fetch('../../../data/download_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'report_id=' + encodeURIComponent(reportId)
            })
            .then(response => {
                if (response.ok) {
                    return response.blob();
                }
                throw new Error('Download failed');
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${name.replace(/[^a-z0-9]/gi, '_').toLowerCase()}.${type}`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                showToast('Report downloaded successfully!', 'success');
            })
            .catch(err => {
                showToast('Failed to download report: ' + err.message, 'error');
            });
        }

        document.getElementById('reportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('report_name', document.getElementById('reportName').value);
            formData.append('report_type', document.getElementById('reportType').value);
            formData.append('date_from', document.getElementById('dateFrom').value);
            formData.append('date_to', document.getElementById('dateTo').value);
            formData.append('instructor_id', <?php echo $instructor_id; ?>);
            
            fetch('../../../data/generate_report.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Report generated successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Failed to generate report', 'error');
                }
            })
            .catch(err => {
                showToast('Error: ' + err.message, 'error');
            });
        });

        // Close modals on outside click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        });

        <?php if ($show_role_modal): ?>
        window.addEventListener('DOMContentLoaded', function() {
            showToast('Access restricted. Redirecting...', 'error');
            setTimeout(() => window.location.href = '../../../Door/login.php', 2000);
        });
        <?php endif; ?>

        function toggleLike(reportId) {
            fetch('../../../data/toggle_like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'report_id=' + reportId
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.liked ? 'Added to favorites' : 'Removed from favorites', 'success');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            fetch('../../../data/update_mentee_view.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });
        });

        function downloadMenteeReport() {
            fetch('../../../data/download_mentee_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'instructor_id=' + encodeURIComponent(<?php echo $instructor_id; ?>)
            })
            .then(response => {
                if (response.ok) {
                    return response.blob();
                }
                throw new Error('Download failed');
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const date = new Date().toISOString().split('T')[0];
                a.download = `mentee_report_${date}.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                showToast('Mentee report downloaded successfully!', 'success');
            })
            .catch(err => {
                showToast('Failed to download report: ' + err.message, 'error');
            });
        }
    </script>
</body>
</html>
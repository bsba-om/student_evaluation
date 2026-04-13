<?php
require_once '../../../data/session_security.php';
require_once '../../../data/config.php';

// Role access check
$role_access = check_role_access('instructor');
$show_role_modal = !$role_access['allowed'];

// Initialize variables
$instructor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Jane Teacher';

// Initialize stats
$stats = [
    'total_reports' => 0,
    'pdf_count' => 0,
    'excel_count' => 0,
    'total_downloads' => 0,
    'recent_downloads' => []
];

$report_types = ['pdf', 'excel', 'csv', 'json'];
$all_reports = [];

// Initialize mentee data
$assigned_mentees = [];
$mentee_count = 0;
$new_mentees = [];
$last_viewed = null;

// Sample data for reports generation
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

// Process data if access is allowed
if (!$show_role_modal) {
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
    
    // Get assigned mentees for Assignment History
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
        
        .section-tabs {
            display: flex;
            gap: 4px;
            background: white;
            padding: 6px;
            border-radius: 14px;
            border: 1px solid var(--border-light);
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: relative;
            z-index: 10;
        }
        
        .section-tab {
            flex: 1;
            padding: 14px 20px;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background: transparent;
            color: var(--light-text);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .section-tab.active {
            background: linear-gradient(135deg, var(--gold), #b8922f);
            color: white;
            box-shadow: 0 2px 10px rgba(212, 168, 67, 0.4);
        }
        
        .section-tab:hover:not(.active) {
            background: var(--cream);
            color: var(--dark-text);
        }
        
        .section-tab i { font-size: 14px; }
        
        .section-content { 
            display: none; 
            position: relative;
            z-index: 1;
            padding: 20px 0;
        }
        .section-content.active { 
            display: block; 
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
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
        }
        
        .report-card {
            background: white;
            border-radius: 18px;
            border: 1px solid var(--border-light);
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        .report-card:hover {
            border-color: var(--gold-light);
            transform: translateY(-6px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.1);
        }
        
        .report-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), #d4a843);
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .report-card:hover::before { opacity: 1; }
        
        .report-card-header {
            padding: 20px;
            background: var(--cream);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .report-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .report-card-body {
            padding: 20px;
        }
        
        .report-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark-text);
            margin: 0 0 8px 0;
            line-height: 1.3;
        }
        
        .report-description {
            font-size: 13px;
            color: var(--light-text);
            margin: 0 0 12px 0;
            line-height: 1.5;
        }
        
        .report-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .report-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .report-badge.pdf {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
        }
        
        .report-badge.excel {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #059669;
        }
        
        .report-badge.csv {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #2563eb;
        }
        
        .report-badge.json {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
        }
        
        .toast-close {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            padding: 4px;
            margin-left: auto;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        
        .toast-close:hover {
            opacity: 1;
        }
        
        .search-bar {
            margin-bottom: 24px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px 16px;
            border: 2px solid var(--border-light);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 168, 67, 0.1);
        }
        
        .filter-select {
            min-width: 150px;
            padding: 12px 16px;
            border: 2px solid var(--border-light);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            background: white;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--gold);
        }
        
        .report-card-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafaf8;
        }
        
        .download-stats {
            font-size: 12px;
            color: var(--light-text);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .download-stats i { color: var(--gold); }
        
        .generated-date {
            font-size: 12px;
            color: var(--light-text);
        }
        
        .card-actions {
            display: flex;
            gap: 8px;
            padding: 14px 20px;
            border-top: 1px solid var(--border-light);
        }
        
        .card-btn {
            flex: 1;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
            border: none;
        }
        
        .card-btn-primary {
            background: linear-gradient(135deg, var(--gold), #b8922f);
            color: white;
        }
        
        .card-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(212, 168, 67, 0.4);
        }
        
        .card-btn-secondary {
            background: var(--cream);
            color: var(--dark-text);
            border: 1px solid var(--border-light);
        }
        
        .card-btn-secondary:hover {
            background: white;
            border-color: var(--gold);
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
            <a href="evaluation.php" class="sidebar-nav-item">
                 <i class="fas fa-comment-dots"></i>
                 <span>Evaluation</span>
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
                    <p>Generate, download, and manage your reports</p>
                </div>
            </div>

            <!-- Section Tabs -->
            <div class="section-tabs">
                <button class="section-tab active" onclick="switchSection('reports')">
                    <i class="fas fa-file-alt"></i> Available Reports
                </button>
                <button class="section-tab" onclick="switchSection('mentees')">
                    <i class="fas fa-history"></i> Assignment History
                    <?php if ($mentee_count > 0): ?>
                    <span style="background: var(--gold); color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px;">
                        <?php echo $mentee_count; ?>
                    </span>
                    <?php endif; ?>
                </button>
                <button class="section-tab" onclick="switchSection('generate')">
                    <i class="fas fa-magic"></i> Generate
                </button>
                <button class="section-tab" onclick="switchSection('analytics')">
                    <i class="fas fa-chart-bar"></i> Analytics
                </button>
            </div>

            <!-- Reports Section -->
            <div class="section-content active" id="reportsSection">

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
 
             <!-- Mentees Section -->
             <div class="section-content" id="menteesSection">
                 <?php if (!empty($new_mentees)): ?>
                 <div class="new-mentee-notification" style="background: linear-gradient(135deg, #059669, #34d399); border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 16px; color: white; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);">
                     <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                         <i class="fas fa-user-plus" style="font-size: 20px;"></i>
                     </div>
                     <div style="flex: 1;">
                         <div style="font-size: 16px; font-weight: 700; margin-bottom: 4px;">
                             New Mentees Assigned!
                         </div>
                         <div style="font-size: 14px; opacity: 0.95;">
                             <?php echo count($new_mentees); ?> students have been assigned to you: 
                             <?php 
                             $names = [];
                             foreach ($new_mentees as $nm) {
                                 $names[] = htmlspecialchars(trim($nm['first_name'] . ' ' . $nm['last_name']));
                             }
                             echo implode(', ', $names);
                             ?>
                         </div>
                     </div>
                     <button onclick="this.parentElement.style.display='none'" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                         <i class="fas fa-times"></i>
                     </button>
                 </div>
                 <?php endif; ?>
                 <?php if ($mentee_count > 0): ?>
                 <div style="margin-bottom: 24px;">
                     <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                         <i class="fas fa-bell" style="font-size: 24px; color: var(--gold);"></i>
                         <h2 style="margin: 0; font-size: 20px; color: var(--dark-text);">Assignment Notifications</h2>
                     </div>
                     <p style="margin: 0; font-size: 14px; color: var(--light-text);">Recent student assignments from your Program Head</p>
                 </div>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php foreach ($assigned_mentees as $mentee): 
                        $fullName = htmlspecialchars(trim(($mentee['first_name'] ?? '') . ' ' . ($mentee['last_name'] ?? '')));
                        $initials = strtoupper(substr($mentee['first_name'] ?? '', 0, 1) . substr($mentee['last_name'] ?? '', 0, 1));
                        $assignedDate = !empty($mentee['created_at']) ? date('M j, Y', strtotime($mentee['created_at'])) : '-';
                        $assignedBy = !empty($mentee['assigned_by_name']) ? htmlspecialchars($mentee['assigned_by_name']) : 'Program Head';
                        
                        $transactionMessage = "On " . $assignedDate . ", " . $assignedBy . " has assigned <strong>" . $fullName . "</strong> (" . htmlspecialchars($mentee['email'] ?? '-') . ") to you as a mentee.";
                        if (!empty($mentee['assignment_notes'])) {
                            $transactionMessage .= " <em>Note: " . htmlspecialchars($mentee['assignment_notes']) . "</em>";
                        }
                    ?>
                    <div style="background: white; border-radius: 16px; padding: 20px; border: 1px solid var(--border-light);">
                        <div style="display: flex; align-items: flex-start; gap: 16px;">
                            <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #8b5cf6, #a78bfa); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; flex-shrink: 0;">
                                <?php echo $initials; ?>
                            </div>
                            <div style="flex: 1;">
                                <p style="margin: 0; font-size: 14px; color: var(--dark-text); line-height: 1.6;">
                                    <?php echo $transactionMessage; ?>
                                </p>
                                <div style="margin-top: 12px; display: flex; gap: 8px; align-items: center;">
                                    <span style="padding: 4px 10px; background: rgba(212, 168, 67, 0.15); color: #9a7b0a; border-radius: 20px; font-size: 11px; font-weight: 600;">
                                        <?php echo htmlspecialchars($mentee['year_level'] ?? '-'); ?>
                                    </span>
                                    <?php if (!empty($mentee['major_name'])): ?>
                                    <span style="padding: 4px 10px; background: rgba(139, 92, 246, 0.15); color: #6d28d9; border-radius: 20px; font-size: 11px; font-weight: 600;">
                                        <?php echo htmlspecialchars($mentee['major_name']); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: 20px; text-align: right;">
                    <button class="btn btn-primary" onclick="downloadMenteeReport()">
                        <i class="fas fa-download"></i> Export All Mentees
                    </button>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 60px; background: white; border-radius: 16px; border: 1px solid var(--border-light);">
                    <i class="fas fa-user-slash" style="font-size: 56px; color: var(--gold-light); opacity: 0.5;"></i>
                    <h3 style="margin: 16px 0 8px 0; font-size: 18px; color: var(--dark-text);">No Mentees Assigned</h3>
                    <p style="font-size: 14px; color: var(--light-text);">Contact the Program Head for mentee assignments.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Generate Section -->
            <div class="section-content" id="generateSection">
                <div style="background: white; border-radius: 16px; padding: 24px; border: 1px solid var(--border-light); margin-bottom: 24px;">
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-magic" style="color: var(--gold);"></i> Generate Custom Report
                    </h3>
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
                                <label for="dateFrom">Date From</label>
                                <input type="date" class="form-control" id="dateFrom">
                            </div>
                            <div class="form-group">
                                <label for="dateTo">Date To</label>
                                <input type="date" class="form-control" id="dateTo">
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; justify-content: flex-end;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-file-download"></i> Generate Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Analytics Section -->
            <div class="section-content" id="analyticsSection">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-type">Reports Generated</div>
                                <div class="stat-value"><?php echo $stats['total_reports']; ?></div>
                            </div>
                            <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
                        </div>
                        <div class="stat-label">All time reports</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-type">Total Downloads</div>
                                <div class="stat-value"><?php echo $stats['total_downloads']; ?></div>
                            </div>
                            <div class="stat-icon purple"><i class="fas fa-download"></i></div>
                        </div>
                        <div class="stat-label">Downloads this period</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-type">Mentees</div>
                                <div class="stat-value"><?php echo $mentee_count; ?></div>
                            </div>
                            <div class="stat-icon green"><i class="fas fa-users"></i></div>
                        </div>
                        <div class="stat-label">Assigned students</div>
                    </div>
                </div>
            </div>

             <!-- Search and Filter Bar -->
            <div class="search-bar" id="searchBar">
                <input type="text" class="search-input" id="reportSearch" placeholder="Search reports by name..." oninput="filterReports()">
                <select class="filter-select" id="reportTypeFilter" onchange="filterReports()">
                    <option value="">All Types</option>
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel</option>
                    <option value="csv">CSV</option>
                    <option value="json">JSON</option>
                </select>
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
                         <div class="card-actions">
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
        function switchSection(section) {
            document.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.section-content').forEach(c => c.classList.remove('active'));
            event.target.closest('.section-tab').classList.add('active');
            document.getElementById(section + 'Section').classList.add('active');
        }
        
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

         function showGenerator() {
             switchSection('generate');
         }

         document.getElementById('reportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const reportName = document.getElementById('reportName').value.trim();
            const reportType = document.getElementById('reportType').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            if (!reportName) {
                showToast('Please enter a report name', 'error');
                return;
            }
            
            if (!reportType) {
                showToast('Please select a report type', 'error');
                return;
            }
            
            if (dateFrom && dateTo && new Date(dateFrom) > new Date(dateTo)) {
                showToast('Start date cannot be after end date', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('report_name', reportName);
            formData.append('report_type', reportType);
            formData.append('date_from', dateFrom);
            formData.append('date_to', dateTo);
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
        
        function filterReports() {
            const searchTerm = document.getElementById('reportSearch').value.toLowerCase();
            const typeFilter = document.getElementById('reportTypeFilter').value;
            const reports = document.querySelectorAll('.report-card');
            
            reports.forEach(card => {
                const title = card.querySelector('.report-title').textContent.toLowerCase();
                const type = card.querySelector('.report-badge').textContent.trim().toLowerCase();
                
                const matchesSearch = title.includes(searchTerm);
                const matchesType = !typeFilter || type === typeFilter;
                
                card.style.display = matchesSearch && matchesType ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>
<?php
require_once '../../data/session_security.php';
check_auth('instructor', '../login.php');
require_once '../../data/config.php';

$instructor_id = $_SESSION['user_id'] ?? 1;
$user_name = $_SESSION['user_name'] ?? 'Jane Teacher';

// Initialize variables
$course_count = 0;
$student_count = 0;
$avg_rating = 0;
$new_feedback = 0;
$pending_evaluations = 0;
$new_mentees = 0;
$reports_generated = 0;
$active_tasks = 0;
$recent_evaluations = [];
$recent_feedback = [];
$recent_activities = [];

if ($pdo) {
    try {
        // Get course count from instructor_courses
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM instructor_courses WHERE instructor_id = ?");
        $stmt->execute([$instructor_id]);
        $result = $stmt->fetch();
        $course_count = $result['cnt'] ?? 0;
    } catch (PDOException $e) {
        $course_count = 0;
    }

    try {
        // Get total students across instructor's courses
        $sql = "SELECT COUNT(DISTINCT s.id) as cnt
                FROM students s
                JOIN courses c ON s.course_code = c.course_code
                JOIN instructor_courses ic ON c.id = ic.course_id
                WHERE ic.instructor_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$instructor_id]);
        $result = $stmt->fetch();
        $student_count = $result['cnt'] ?? 0;
    } catch (PDOException $e) {
        $student_count = 0;
    }

    try {
        // Get average rating from evaluations
        $sql = "SELECT COALESCE(AVG(rating),0) as avg_r FROM evaluations WHERE instructor_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$instructor_id]);
        $result = $stmt->fetch();
        $avg_rating = round($result['avg_r'], 1);
    } catch (PDOException $e) {
        $avg_rating = 0;
    }

    try {
        // Get count of new feedback (if there is a 'status' column, count pending feedback)
        // For now assume evaluations with status 'new' or just count of feedback entries without a status is zero
        // We'll leave as 0 or could count from evaluations if a 'is_read' column exists; unknown.
        $new_feedback = 0;
    } catch (PDOException $e) {
        $new_feedback = 0;
    }

    try {
        // Get pending evaluations (evaluations not yet finalized)
        $sql = "SELECT COUNT(*) as cnt FROM evaluations WHERE instructor_id = ? AND (status IS NULL OR status != 'finalized')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$instructor_id]);
        $result = $stmt->fetch();
        $pending_evaluations = $result['cnt'] ?? 0;
    } catch (PDOException $e) {
        $pending_evaluations = 0;
    }

    try {
        // Get new mentees (assigned in last 24 hours)
        $sql = "SELECT COUNT(*) as cnt FROM mentees WHERE mentor_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$instructor_id]);
        $result = $stmt->fetch();
        $new_mentees = $result['cnt'] ?? 0;
    } catch (PDOException $e) {
        $new_mentees = 0;
    }

    try {
        // Get reports generated count
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM reports");
        $result = $stmt->fetch();
        $reports_generated = $result['cnt'] ?? 0;
    } catch (PDOException $e) {
        $reports_generated = 0;
    }

    try {
        // Get active tasks count
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM tasks WHERE instructor_id = ? AND status != 'completed'");
        $stmt->execute([$instructor_id]);
        $result = $stmt->fetch();
        $active_tasks = $result['cnt'] ?? 0;
    } catch (PDOException $e) {
        $active_tasks = 0;
    }

    try {
        // Recent evaluations: course-based summary
        $sql = "SELECT c.course_name, COUNT(e.id) as student_count, AVG(e.rating) as avg_rating, MAX(e.evaluation_date) as evaluation_date
                FROM courses c
                JOIN evaluations e ON c.id = e.course_id
                WHERE e.instructor_id = ?
                GROUP BY c.id
                ORDER BY evaluation_date DESC
                LIMIT 3";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$instructor_id]);
        while ($row = $stmt->fetch()) {
            $recent_evaluations[] = [
                'course_name' => $row['course_name'],
                'student_count' => $row['student_count'],
                'rating' => round($row['avg_rating'], 1),
                'evaluation_date' => $row['evaluation_date']
            ];
        }
    } catch (PDOException $e) {
        $recent_evaluations = [];
    }

    try {
        // Recent feedback: assume evaluations have a 'comments' column
        $sql = "SELECT c.course_name, e.comments as feedback_text, e.rating, e.evaluation_date as feedback_date
                FROM evaluations e
                JOIN courses c ON e.course_id = c.id
                WHERE e.instructor_id = ? AND e.comments IS NOT NULL AND e.comments != ''
                ORDER BY e.evaluation_date DESC
                LIMIT 3";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$instructor_id]);
        while ($row = $stmt->fetch()) {
            $recent_feedback[] = [
                'course_name' => $row['course_name'],
                'feedback_text' => $row['feedback_text'],
                'rating' => $row['rating'],
                'feedback_date' => $row['feedback_date']
            ];
        }
    } catch (PDOException $e) {
        $recent_feedback = [];
    }

    try {
        // Recent activities: combine evaluations, tasks, and mentees
        $activities = [];

        // Recent evaluations
        $sql = "SELECT 'evaluation' as type, c.course_name as title, e.evaluation_date as date, 'Evaluation completed' as description
                FROM evaluations e
                JOIN courses c ON e.course_id = c.id
                WHERE e.instructor_id = ?
                ORDER BY e.evaluation_date DESC
                LIMIT 2";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$instructor_id]);
        while ($row = $stmt->fetch()) {
            $activities[] = $row;
        }

        // Recent task assignments
        $sql = "SELECT 'task' as type, title, created_at as date, 'Task assigned' as description
                FROM tasks
                WHERE instructor_id = ?
                ORDER BY created_at DESC
                LIMIT 2";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$instructor_id]);
        while ($row = $stmt->fetch()) {
            $activities[] = $row;
        }

        // Recent mentees
        $sql = "SELECT 'mentee' as type, CONCAT(s.first_name, ' ', s.last_name) as title, m.created_at as date, 'New mentee assigned' as description
                FROM mentees m
                JOIN students s ON m.student_id = s.id
                WHERE m.mentor_id = ?
                ORDER BY m.created_at DESC
                LIMIT 2";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$instructor_id]);
        while ($row = $stmt->fetch()) {
            $activities[] = $row;
        }

        // Sort all activities by date and take top 5
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        $recent_activities = array_slice($activities, 0, 5);

    } catch (PDOException $e) {
        $recent_activities = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../media/LOGO.jpg" type="image/jpeg">
    <title>Instructor Dashboard - Faculty Evaluation System</title>
    <link rel="stylesheet" href="../../css/common.css">
    <link rel="stylesheet" href="style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Enhanced Dashboard Styles */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .stat-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .stat-card-icon.gold { background: linear-gradient(135deg, #d4a843, #b8922f); }
        .stat-card-icon.blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .stat-card-icon.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .stat-card-icon.green { background: linear-gradient(135deg, #059669, #047857); }
        .stat-card-icon.orange { background: linear-gradient(135deg, #d97706, #b45309); }
        .stat-card-icon.teal { background: linear-gradient(135deg, #0d9488, #0f766e); }
        .stat-card-icon.indigo { background: linear-gradient(135deg, #6366f1, #4f46e5); }

        .stat-card-value {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            line-height: 1;
        }

        .stat-card-label {
            font-size: 13px;
            color: #6b7280;
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

        .stat-change.positive { color: #059669; }
        .stat-change.negative { color: #dc2626; }

        .stat-progress {
            margin-top: 12px;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
        }

        .stat-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .quick-actions {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
        }

        .quick-actions h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .action-btn.primary {
            background: linear-gradient(135deg, #d4a843, #b8922f);
            color: white;
        }

        .action-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212, 168, 67, 0.3);
        }

        .action-btn.secondary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .action-btn.secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        .action-btn.tertiary {
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
        }

        .action-btn.tertiary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(5, 150, 105, 0.3);
        }

        .action-btn.quaternary {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: white;
        }

        .action-btn.quaternary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.3);
        }

        .activity-notifications {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        @media (max-width: 1200px) {
            .activity-notifications {
                grid-template-columns: 1fr;
            }
        }

        .activity-card, .notifications-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .activity-header, .notifications-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-header h3, .notifications-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .view-all {
            font-size: 12px;
            color: #6b7280;
            text-decoration: none;
            font-weight: 500;
        }

        .view-all:hover {
            color: #d4a843;
        }

        .activity-list, .notifications-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .activity-item, .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 16px 24px;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s ease;
        }

        .activity-item:hover, .notification-item:hover {
            background: #f9fafb;
        }

        .activity-item:last-child, .notification-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-icon.evaluation { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }
        .activity-icon.task { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
        .activity-icon.mentee { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #059669; }

        .activity-content {
            flex: 1;
            min-width: 0;
        }

        .activity-title {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .activity-desc {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .activity-time {
            font-size: 11px;
            color: #9ca3af;
        }

        .notification-item.warning { border-left: 4px solid #f59e0b; }
        .notification-item.success { border-left: 4px solid #10b981; }
        .notification-item.info { border-left: 4px solid #3b82f6; }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .notification-desc {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .notification-action {
            font-size: 12px;
            color: #d4a843;
            text-decoration: none;
            font-weight: 600;
        }

        .notification-action:hover {
            color: #b8922f;
        }

        .activity-item.empty, .notification-item.empty {
            justify-content: center;
            padding: 40px 24px;
            color: #9ca3af;
        }

        .activity-item.empty i, .notification-item.empty i {
            font-size: 24px;
            margin-bottom: 8px;
            display: block;
        }

        /* Enhanced Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #d4a843 0%, #b8922f 50%, #a38023 100%);
            border-radius: 20px;
            padding: 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 32px;
            position: relative;
            overflow: hidden;
            flex-wrap: wrap;
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .welcome-banner::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }

        .welcome-banner-content {
            flex: 1;
            z-index: 1;
            min-width: 300px;
        }

        .welcome-banner-role {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .welcome-banner h1 {
            color: white;
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 8px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.3);
            line-height: 1.1;
        }

        .welcome-banner p {
            color: rgba(255,255,255,0.9);
            font-size: 16px;
            line-height: 1.5;
            max-width: 500px;
        }

        .welcome-banner-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            z-index: 1;
        }

        .banner-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .banner-action-btn.primary {
            background: rgba(255,255,255,0.15);
            color: white;
            border-color: rgba(255,255,255,0.3);
        }

        .banner-action-btn.primary:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }

        .banner-action-btn.secondary {
            background: rgba(255,255,255,0.9);
            color: #1f2937;
        }

        .banner-action-btn.secondary:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .banner-action-btn.tertiary {
            background: rgba(255,255,255,0.1);
            color: white;
            border-color: rgba(255,255,255,0.2);
        }

        .banner-action-btn.tertiary:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.25);
        }

        @media (max-width: 768px) {
            .welcome-banner {
                flex-direction: column;
                text-align: center;
                gap: 24px;
            }

            .welcome-banner-actions {
                justify-content: center;
            }

            .welcome-banner h1 {
                font-size: 28px;
            }
        }
    </style>
</head>

<body class="dashboard-page">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../../media/LOGO.jpg" alt="Logo" class="sidebar-logo" style="width: 70px; height: 70px; border-radius: 16px; object-fit: cover; border: 3px solid white; background: white; padding: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
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
             <a href="dashboard.php" class="sidebar-nav-item active">
                 <i class="fas fa-chart-pie"></i>
                 <span>Overview</span>
             </a>
             <a href="pages/students.php" class="sidebar-nav-item">
                 <i class="fas fa-user-graduate"></i>
                 <span>Students mentees</span>
             </a>
             <a href="pages/evaluation.php" class="sidebar-nav-item">
                 <i class="fas fa-comment-dots"></i>
                 <span>Evaluation</span>
             </a>
             <a href="pages/reports.php" class="sidebar-nav-item">
                 <i class="fas fa-file-alt"></i>
                 <span>Reports</span>
             </a>
             <a href="pages/profile.php" class="sidebar-nav-item">
                 <i class="fas fa-user"></i>
                 <span>Profile</span>
             </a>
         </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content" style="position: relative;">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        <!-- Topbar -->
        <header class="topbar" style="left: 260px !important;">
            <div class="topbar-left">
                <button class="topbar-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <div class="topbar-title">Dashboard</div>
                    <div class="topbar-subtitle">Instructor Panel</div>
                </div>
            </div>
            
            <div class="topbar-right">
                <div class="topbar-date">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('F j, Y'); ?></span>
                </div>
                <a href="../../data/logout.php" class="topbar-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="dashboard-content">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-banner-content">
                    <div class="welcome-banner-role">Instructor</div>
                    <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                    <p>Track your course performance, view student evaluations, and improve your teaching methods.</p>
                </div>
                <div class="welcome-banner-actions">
                    <a href="pages/evaluation.php" class="banner-action-btn primary">
                        <i class="fas fa-play-circle"></i>
                        <span>Start Evaluation</span>
                    </a>
                    <a href="pages/students.php" class="banner-action-btn secondary">
                        <i class="fas fa-users"></i>
                        <span>Manage Students</span>
                    </a>
                    <a href="pages/reports.php" class="banner-action-btn tertiary">
                        <i class="fas fa-chart-bar"></i>
                        <span>View Reports</span>
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon gold">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $course_count; ?></div>
                    <div class="stat-card-label">My Courses</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon blue">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $student_count; ?></div>
                    <div class="stat-card-label">Total Students</div>
                    <?php if ($new_mentees > 0): ?>
                    <div class="stat-change positive">
                        <i class="fas fa-plus"></i> <?php echo $new_mentees; ?> new
                    </div>
                    <?php endif; ?>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon purple">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $avg_rating; ?></div>
                    <div class="stat-card-label">My Rating</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon green">
                            <i class="fas fa-comment-dots"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $new_feedback; ?></div>
                    <div class="stat-card-label">New Feedback</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $pending_evaluations; ?></div>
                    <div class="stat-card-label">Pending Evaluations</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon teal">
                            <i class="fas fa-tasks"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $active_tasks; ?></div>
                    <div class="stat-card-label">Active Tasks</div>
                    <?php if ($active_tasks > 0): ?>
                    <div class="stat-progress">
                        <div class="stat-progress-bar" style="width: <?php echo min(($active_tasks / max($student_count, 1)) * 100, 100); ?>%"></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon indigo">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $reports_generated; ?></div>
                    <div class="stat-card-label">Reports Generated</div>
                    <div class="stat-change positive">
                        <i class="fas fa-chart-line"></i> Analytics Ready
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="action-buttons">
                    <a href="pages/evaluation.php" class="action-btn primary">
                        <i class="fas fa-play"></i>
                        <span>Start Evaluation</span>
                    </a>
                    <a href="pages/students.php" class="action-btn secondary">
                        <i class="fas fa-user-plus"></i>
                        <span>Assign Task</span>
                    </a>
                    <a href="pages/reports.php" class="action-btn tertiary">
                        <i class="fas fa-file-download"></i>
                        <span>Generate Report</span>
                    </a>
                    <a href="pages/profile.php" class="action-btn quaternary">
                        <i class="fas fa-user-cog"></i>
                        <span>Update Profile</span>
                    </a>
                </div>
            </div>

            <!-- Recent Activity & Notifications -->
            <div class="activity-notifications">
                <!-- Recent Activity -->
                <div class="activity-card">
                    <div class="activity-header">
                        <h3><i class="fas fa-history"></i> Recent Activity</h3>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <div class="activity-list">
                        <?php if (empty($recent_activities)): ?>
                        <div class="activity-item empty">
                            <i class="fas fa-info-circle"></i>
                            <span>No recent activity</span>
                        </div>
                        <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?php echo $activity['type']; ?>">
                                <?php
                                $icon = 'fas fa-circle';
                                switch ($activity['type']) {
                                    case 'evaluation': $icon = 'fas fa-clipboard-check'; break;
                                    case 'task': $icon = 'fas fa-tasks'; break;
                                    case 'mentee': $icon = 'fas fa-user-plus'; break;
                                }
                                ?>
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                <div class="activity-desc"><?php echo htmlspecialchars($activity['description']); ?></div>
                                <div class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['date'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="notifications-card">
                    <div class="notifications-header">
                        <h3><i class="fas fa-bell"></i> Notifications</h3>
                    </div>
                    <div class="notifications-list">
                        <?php if ($pending_evaluations > 0): ?>
                        <div class="notification-item warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div class="notification-content">
                                <div class="notification-title"><?php echo $pending_evaluations; ?> Pending Evaluations</div>
                                <div class="notification-desc">Complete pending student evaluations</div>
                                <a href="pages/evaluation.php" class="notification-action">Review Now</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($new_mentees > 0): ?>
                        <div class="notification-item success">
                            <i class="fas fa-user-plus"></i>
                            <div class="notification-content">
                                <div class="notification-title"><?php echo $new_mentees; ?> New Mentees</div>
                                <div class="notification-desc">Welcome your newly assigned students</div>
                                <a href="pages/students.php" class="notification-action">View Students</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($active_tasks > 0): ?>
                        <div class="notification-item info">
                            <i class="fas fa-tasks"></i>
                            <div class="notification-content">
                                <div class="notification-title"><?php echo $active_tasks; ?> Active Tasks</div>
                                <div class="notification-desc">Monitor ongoing task progress</div>
                                <a href="pages/students.php" class="notification-action">Check Tasks</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($pending_evaluations == 0 && $new_mentees == 0 && $active_tasks == 0): ?>
                        <div class="notification-item empty">
                            <i class="fas fa-check-circle"></i>
                            <span>All caught up!</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Recent Evaluations Card -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h3><i class="fas fa-clipboard-list"></i> My Recent Evaluations</h3>
                        <a href="pages/evaluations.php" class="view-all">View All</a>
                    </div>
                    <div class="content-card-body">
                        <table class="eval-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Students</th>
                                    <th>Rating</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_evaluations as $eval): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($eval['course_name']); ?></td>
                                    <td><?php echo $eval['student_count']; ?></td>
                                    <td><span class="rating-badge <?php echo $eval['rating'] >= 4.7 ? 'excellent' : ($eval['rating'] >= 4.4 ? 'good' : 'average'); ?>"><?php echo number_format($eval['rating'], 1); ?></span></td>
                                    <td><?php echo date('M j, Y', strtotime($eval['evaluation_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Feedback Card -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h3><i class="fas fa-comment-alt"></i> Recent Feedback</h3>
                        <a href="pages/feedback.php" class="view-all">View All</a>
                    </div>
                    <div class="content-card-body">
                        <div class="feedback-list">
                            <?php foreach ($recent_feedback as $fb): ?>
                            <div class="feedback-item">
                                <div class="feedback-header">
                                    <span class="feedback-course"><?php echo htmlspecialchars($fb['course_name']); ?></span>
                                    <span class="feedback-date"><?php echo date('M j, Y', strtotime($fb['feedback_date'])); ?></span>
                                </div>
                                <p class="feedback-text">"<?php echo htmlspecialchars($fb['feedback_text']); ?>"</p>
                                <div class="feedback-rating">
                                    <?php
                                    $full_stars = floor($fb['rating']);
                                    $half_star = ($fb['rating'] - $full_stars) >= 0.25;
                                    for ($i = 0; $i < $full_stars; $i++) echo '<i class="fas fa-star"></i>';
                                    if ($half_star) echo '<i class="fas fa-star-half-alt"></i>';
                                    for ($i = $full_stars + ($half_star ? 1 : 0); $i < 5; $i++) echo '<i class="far fa-star"></i>';
                                    ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../function/dashboard.js"></script>
    <script src="../../function/session_guard.js"></script>
</body>
</html>

<?php
require_once '../../data/session_security.php';
check_auth('program_head', '../login.php');
require_once '../../data/config.php';

$user_name = $_SESSION['user_name'] ?? 'Program Head';

// Fetch stats with error handling
$total_instructors = 0;
$completed_evaluations = 0;
$active_courses = 0;
$avg_rating = 0;

// Get total instructors
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM instructors");
    $result = $stmt->fetch();
    $total_instructors = $result['cnt'] ?? 0;
} catch (PDOException $e) {
    $total_instructors = 0;
}

// Get evaluation stats
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'evaluations'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM evaluations WHERE status = 'completed'");
        $result = $stmt->fetch();
        $completed_evaluations = $result['cnt'] ?? 0;
        
        $stmt = $pdo->query("SELECT COALESCE(AVG(rating),0) as avg_r FROM evaluations");
        $result = $stmt->fetch();
        $avg_rating = round($result['avg_r'], 1);
    }
} catch (PDOException $e) {
    $completed_evaluations = 0;
    $avg_rating = 0;
}

// Get active courses
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'courses'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM courses WHERE status = 'active'");
        $result = $stmt->fetch();
        $active_courses = $result['cnt'] ?? 0;
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM majors WHERE is_active = 1");
        $result = $stmt->fetch();
        $active_courses = $result['cnt'] ?? 0;
    }
} catch (PDOException $e) {
    $active_courses = 0;
}

// Get total students
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM students");
    $result = $stmt->fetch();
    $total_students = $result['cnt'] ?? 0;
} catch (PDOException $e) {
    $total_students = 0;
}

// Get instructor status counts
$on_duty = 0; $on_leave = 0; $on_travel = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM instructors WHERE status = 'on duty'");
    $on_duty = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM instructors WHERE status = 'on leave'");
    $on_leave = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM instructors WHERE status = 'on travel'");
    $on_travel = $stmt->fetchColumn() ?: 0;
} catch (PDOException $e) {
    $on_duty = 0; $on_leave = 0; $on_travel = 0;
}

// Fetch recent evaluations
$recent_evaluations = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'evaluations'");
    if ($stmt->rowCount() > 0) {
        $sql = "SELECT CONCAT(i.first_name, ' ', i.last_name) as instructor_name, 
                       c.course_name, e.rating, e.evaluation_date 
                FROM evaluations e 
                JOIN instructors i ON e.instructor_id = i.id 
                JOIN courses c ON e.course_id = c.id 
                ORDER BY e.evaluation_date DESC LIMIT 3";
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $recent_evaluations[] = $row;
        }
    }
} catch (PDOException $e) {
    $recent_evaluations = [];
}

// Fetch department performance
$dept_performance = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'evaluations'");
    if ($stmt->rowCount() > 0) {
        $sql = "SELECT COALESCE(e.department, 'General') as department, 
                       COALESCE(AVG(e.rating),0) as avg_rating 
                FROM evaluations e 
                GROUP BY e.department 
                ORDER BY avg_rating DESC";
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dept_performance[] = $row;
        }
    }
} catch (PDOException $e) {
    $dept_performance = [];
}

// Fetch majors for department overview
$majors = [];
try {
    $stmt = $pdo->query("SELECT id, major_name, description, is_active FROM majors ORDER BY major_name");
    $majors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $majors = [];
}

// Fetch students by year (combine 3rd Year variations)
$yearLevels = [];
try {
    $stmt = $pdo->query("SELECT year_level, COUNT(*) as count FROM students WHERE year_level IS NOT NULL AND year_level != '' GROUP BY year_level ORDER BY year_level");
    $raw_year_levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Normalize year levels: combine "3rd Year" and "3rd Year - 2nd Semester"
    $yearLevels = [];
    foreach ($raw_year_levels as $year) {
        $base_year = $year['year_level'];
        // Check if it contains "3rd Year" to combine all 3rd year variants
        if (strpos($base_year, '3rd Year') !== false) {
            $base_year = '3rd Year';
        }
        
        if (isset($yearLevels[$base_year])) {
            $yearLevels[$base_year] += $year['count'];
        } else {
            $yearLevels[$base_year] = $year['count'];
        }
    }
    
    // Convert back to array format and sort by year order
    $year_order = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
    $sorted_yearLevels = [];
    foreach ($year_order as $year) {
        if (isset($yearLevels[$year])) {
            $sorted_yearLevels[] = [
                'year_level' => $year,
                'count' => $yearLevels[$year]
            ];
        }
    }
    $yearLevels = $sorted_yearLevels;
} catch (PDOException $e) {
    $yearLevels = [];
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
    <link rel="icon" href="../../media/LOGO.jpg" type="image/jpeg">
    <title>Dashboard - Program Head</title>
    <link rel="stylesheet" href="../../css/common.css">
    <link rel="stylesheet" href="./style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* Additional Professional Dashboard Styles */
        .stats-highlight {
            background: linear-gradient(135deg, var(--gold-gradient) 0%, var(--gold-gradient-dark) 100%);
            padding: 2px;
            border-radius: 18px;
            position: relative;
            overflow: hidden;
        }
        
        .stats-highlight::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }
        
        .stat-card-inner {
            background: var(--white);
            border-radius: 16px;
            padding: 24px;
            height: 100%;
            position: relative;
            z-index: 1;
        }
        
        /* Department Overview Improvements */
        .dept-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .dept-card {
            background: var(--white);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .dept-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gold-gradient);
        }
        
        .dept-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-gold);
            border-color: var(--gold-light);
        }
        
        .dept-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        
        .dept-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }
        
        .dept-icon.majors { background: var(--gold-gradient); }
        .dept-icon.courses { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .dept-icon.students { background: linear-gradient(135deg, #10b981, #34d399); }
        
        .dept-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 4px;
        }
        
        .dept-subtitle {
            font-size: 12px;
            color: var(--light-text);
            font-weight: 500;
        }
        
        .dept-value {
            font-size: 36px;
            font-weight: 800;
            color: var(--gold-dark);
            margin: 16px 0;
            text-align: center;
        }
        
        .dept-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            background: var(--cream);
            border: 1px solid var(--border-light);
            border-radius: 10px;
            color: var(--dark-text);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .dept-link:hover {
            background: var(--gold-gradient);
            color: white;
            border-color: var(--gold-primary);
            transform: translateY(-2px);
        }
        
        /* Student Year Cards */
        .year-cards-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-top: 20px;
        }
        
        .year-stat-card {
            background: linear-gradient(135deg, var(--cream) 0%, var(--white) 100%);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .year-stat-card::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gold-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .year-stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(212, 168, 67, 0.15);
        }
        
        .year-stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .year-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--light-text);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .year-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--dark-text);
            line-height: 1;
        }
        
        .year-bar {
            height: 4px;
            background: var(--off-white);
            border-radius: 2px;
            margin-top: 12px;
            overflow: hidden;
        }
        
        .year-progress {
            height: 100%;
            background: var(--gold-gradient);
            border-radius: 2px;
            transition: width 0.8s ease;
        }
        
        /* Section Headers */
        .section-header-modern {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border-light);
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark-text);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: var(--gold-dark);
        }
        
        .section-action {
            font-size: 13px;
            font-weight: 600;
            color: var(--gold-dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: gap 0.2s ease;
        }
        
        .section-action:hover {
            gap: 10px;
        }
        
        /* Quick Stats Row */
        .quick-stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }
        
        @media (max-width: 1200px) {
            .quick-stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .quick-stats-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="dashboard-page">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../../media/LOGO.jpg" alt="Logo" class="sidebar-logo">
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
                <span class="sidebar-user-role">Program Head</span>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="sidebar-nav-label">Menu</div>
            <a href="dashboard.php" class="sidebar-nav-item active">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
            <a href="pages/instructors.php" class="sidebar-nav-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Instructors</span>
            </a>
            <a href="pages/student_enrollment.php" class="sidebar-nav-item">
                <i class="fas fa-user-graduate"></i>
                <span>Enrollment</span>
            </a>
            <a href="pages/mentee_flow.php" class="sidebar-nav-item">
                <i class="fas fa-users"></i>
                <span>MenteeFlow</span>
            </a>
            <a href="pages/departments.php" class="sidebar-nav-item">
                <i class="fas fa-building"></i>
                <span>Departments</span>
            </a>
            <a href="pages/reports.php" class="sidebar-nav-item">
                <i class="fas fa-file-alt"></i>
                <span>Reports</span>
            </a>
            <a href="pages/settings.php" class="sidebar-nav-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <div class="topbar-title">Dashboard</div>
                    <div class="topbar-subtitle">Program Head Panel</div>
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

        <main class="dashboard-content">
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" id="alert-<?php echo uniqid(); ?>">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($_GET['success']); ?></span>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error" id="alert-<?php echo uniqid(); ?>">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($_GET['error']); ?></span>
            </div>
            <?php endif; ?>

            <!-- Welcome Banner -->
            <section class="welcome-section">
                <div class="welcome-banner">
                    <div class="welcome-banner-left">
                        <div class="welcome-banner-role">Program Head</div>
                        <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                        <p>Monitor instructor performance, manage evaluations, and track department progress all in one place.</p>
                    </div>
                    <div class="welcome-banner-right">
                        <div class="welcome-stats-mini">
                            <div class="mini-stat">
                                <div class="mini-stat-value"><?php echo $total_instructors; ?></div>
                                <div class="mini-stat-label">Instructors</div>
                            </div>
                            <div class="mini-stat">
                                <div class="mini-stat-value"><?php echo $completed_evaluations; ?></div>
                                <div class="mini-stat-label">Evals Done</div>
                            </div>
                            <div class="mini-stat">
                                <div class="mini-stat-value"><?php echo $total_students; ?></div>
                                <div class="mini-stat-label">Students</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Stats Row -->
            <section class="stats-section">
                <div class="quick-stats-row">
                    <div class="stats-highlight">
                        <div class="stat-card-inner">
                            <div class="stat-card-icon-wrap gold">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-value"><?php echo $total_instructors; ?></div>
                                <div class="stat-card-label">Total Instructors</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-highlight">
                        <div class="stat-card-inner">
                            <div class="stat-card-icon-wrap green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-value"><?php echo $completed_evaluations; ?></div>
                                <div class="stat-card-label">Completed Evaluations</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-highlight">
                        <div class="stat-card-inner">
                            <div class="stat-card-icon-wrap blue">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-value"><?php echo $total_students; ?></div>
                                <div class="stat-card-label">Total Students</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-highlight">
                        <div class="stat-card-inner">
                            <div class="stat-card-icon-wrap purple">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-value"><?php echo $active_courses; ?></div>
                                <div class="stat-card-label">Active Courses</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Current Time Display -->
            <section class="time-section">
                <div class="time-widget">
                    <div class="time-widget-left">
                        <i class="fas fa-clock"></i>
                        <span id="time-greeting">Morning</span>
                    </div>
                    <div class="time-widget-divider"></div>
                    <div id="current-time" class="time-widget-time">--:--:--</div>
                    <div class="time-widget-divider"></div>
                    <div id="current-date" class="time-widget-date">Loading...</div>
                </div>
            </section>

            <script>
                function updateTime() {
                    const now = new Date();
                    const options = { timeZone: 'Asia/Manila', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
                    const timeString = now.toLocaleTimeString('en-US', options);
                    const hour = parseInt(now.toLocaleTimeString('en-US', { timeZone: 'Asia/Manila', hour: '2-digit', hour12: false }));
                    
                    const greetingEl = document.getElementById('time-greeting');
                    if (hour >= 5 && hour < 12) greetingEl.textContent = 'Morning';
                    else if (hour >= 12 && hour < 17) greetingEl.textContent = 'Afternoon';
                    else if (hour >= 17 && hour < 21) greetingEl.textContent = 'Evening';
                    else greetingEl.textContent = 'Night';
                    
                    document.getElementById('current-time').textContent = timeString;
                    const dateOptions = { timeZone: 'Asia/Manila', weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
                    document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', dateOptions);
                }
                updateTime();
                setInterval(updateTime, 1000);
            </script>

            <!-- Main Content Grid -->
            <section class="main-grid-section">
                <div class="dashboard-grid">
                    <!-- Department Overview Card -->
                    <div class="content-card">
                        <div class="content-card-header">
                            <div class="card-header-left">
                                <h3><i class="fas fa-building"></i> Department Overview</h3>
                            </div>
                            <a href="pages/departments.php" class="view-all">
                                Manage <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="content-card-body">
                            <?php if (empty($majors)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-folder-open"></i>
                                    </div>
                                    <p>No departments found</p>
                                    <span>Add departments to get started</span>
                                </div>
                            <?php else: ?>
                                <!-- Department Stats Row -->
                                <div class="dept-stats-row">
                                    <div class="dept-stat-box">
                                        <div class="dept-stat-icon majors">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div class="dept-stat-info">
                                            <div class="dept-stat-value"><?php echo count($majors); ?></div>
                                            <div class="dept-stat-label">Majors</div>
                                        </div>
                                    </div>
                                    
                                    <div class="dept-stat-box">
                                        <div class="dept-stat-icon courses">
                                            <i class="fas fa-book-open"></i>
                                        </div>
                                        <div class="dept-stat-info">
                                            <div class="dept-stat-value"><?php echo $active_courses; ?></div>
                                            <div class="dept-stat-label">Active Courses</div>
                                        </div>
                                    </div>
                                    
                                    <div class="dept-stat-box">
                                        <div class="dept-stat-icon students">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        <div class="dept-stat-info">
                                            <div class="dept-stat-value"><?php echo $total_students; ?></div>
                                            <div class="dept-stat-label">Total Students</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Majors List -->
                                <div class="dept-majors-section">
                                    <div class="section-header-modern">
                                        <h4 class="section-title">
                                            <i class="fas fa-layer-group"></i> Program Majors
                                        </h4>
                                        <a href="pages/departments.php" class="section-action">
                                            View All <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                    
                                    <div class="dept-majors-grid">
                                        <?php 
                                        $display_majors = array_slice($majors, 0, 4);
                                        foreach ($display_majors as $major): 
                                            $major_id = $major['id'];
                                            try {
                                                $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM students WHERE major_id = ?");
                                                $stmt->execute([$major_id]);
                                                $student_count = $stmt->fetchColumn() ?: 0;
                                            } catch (PDOException $e) {
                                                $student_count = 0;
                                            }
                                            $percentage = $total_students > 0 ? min(100, round(($student_count / $total_students) * 100)) : 0;
                                        ?>
                                        <div class="dept-card">
                                            <div class="dept-card-header">
                                                <div class="dept-icon majors">
                                                    <i class="fas fa-graduation-cap"></i>
                                                </div>
                                                <span class="status-badge <?php echo ($major['is_active'] ?? 1) ? 'active' : 'inactive'; ?>">
                                                    <?php echo ($major['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </div>
                                            <h4 class="dept-title"><?php echo htmlspecialchars($major['display_name'] ?? $major['major_name']); ?></h4>
                                            <p class="dept-subtitle"><?php echo htmlspecialchars($major['description'] ?? 'No description'); ?></p>
                                            <div class="dept-value"><?php echo $student_count; ?></div>
                                            <div class="progress-bar">
                                                <div class="progress" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                            <div class="dept-link" style="margin-top: 12px;">
                                                <i class="fas fa-eye"></i> View Details
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <?php if (count($majors) > 4): ?>
                                    <div class="dept-more-section">
                                        <a href="pages/departments.php" class="dept-view-all-btn">
                                            <i class="fas fa-plus-circle"></i> View All <?php echo count($majors); ?> Majors
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Students by Year -->
                                <?php if (!empty($yearLevels)): ?>
                                <div class="year-levels-section">
                                    <div class="section-header-modern">
                                        <h4 class="section-title">
                                            <i class="fas fa-users"></i> Students by Year Level
                                        </h4>
                                    </div>
                                    <div class="year-labels-grid">
                                        <?php foreach ($yearLevels as $year): 
                                            $count = intval($year['count']);
                                        ?>
                                        <div class="year-label-badge">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span class="year-name"><?php echo htmlspecialchars($year['year_level']); ?></span>
                                            <span class="year-count"><?php echo $count; ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right Column: Calendar + Reports -->
                    <div class="right-column-grid">
                        <!-- Calendar Card -->
                        <div class="content-card">
                            <div class="content-card-header">
                                <h3><i class="fas fa-calendar-alt"></i> Calendar</h3>
                            </div>
                            <div class="content-card-body">
                                <div class="calendar-widget">
                                    <div class="calendar-header">
                                        <button onclick="changeMonth(-1)" class="calendar-nav-btn">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <div id="calendar-title" class="calendar-month">January 2024</div>
                                        <button onclick="changeMonth(1)" class="calendar-nav-btn">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                    <div class="calendar-day-names">
                                        <?php $days = ['S','M','T','W','T','F','S']; foreach($days as $d): ?>
                                        <span><?php echo $d; ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <div id="calendar-days" class="calendar-days"></div>
                                </div>
                                
                                <div class="instructor-status-section">
                                    <div class="status-title">Instructor Status</div>
                                    <div class="status-grid">
                                        <div class="status-item on-duty">
                                            <div class="status-icon"><i class="fas fa-briefcase"></i></div>
                                            <div class="status-value"><?php echo $on_duty; ?></div>
                                            <div class="status-label">On Duty</div>
                                        </div>
                                        <div class="status-item on-leave">
                                            <div class="status-icon"><i class="fas fa-bed"></i></div>
                                            <div class="status-value"><?php echo $on_leave; ?></div>
                                            <div class="status-label">On Leave</div>
                                        </div>
                                        <div class="status-item on-travel">
                                            <div class="status-icon"><i class="fas fa-plane"></i></div>
                                            <div class="status-value"><?php echo $on_travel; ?></div>
                                            <div class="status-label">On Travel</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reports Card -->
                        <div class="content-card">
                            <div class="content-card-header gold-header">
                                <h3><i class="fas fa-chart-line"></i> Performance Overview</h3>
                            </div>
                            <div class="content-card-body">
                                <div class="performance-summary">
                                    <div class="perf-stat">
                                        <span class="perf-label">Average Rating</span>
                                        <span class="perf-value"><?php echo number_format($avg_rating, 1); ?>/5.0</span>
                                    </div>
                                    <div class="perf-stat">
                                        <span class="perf-label">Completion Rate</span>
                                        <span class="perf-value"><?php echo $total_instructors > 0 ? round(($completed_evaluations / $total_instructors) * 100) : 0; ?>%</span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($dept_performance)): ?>
                                <div class="perf-list">
                                    <?php foreach (array_slice($dept_performance, 0, 3) as $dept): ?>
                                    <div class="perf-item">
                                        <div class="perf-info">
                                            <span class="perf-name"><?php echo htmlspecialchars($dept['department']); ?></span>
                                            <span class="perf-rating"><?php echo number_format($dept['avg_rating'], 1); ?></span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: <?php echo ($dept['avg_rating'] / 5) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                
                                <a href="pages/reports.php" class="view-all centered-link">
                                    <i class="fas fa-chart-bar"></i> Full Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Recent Activity -->
            <section class="activity-section">
                <div class="section-header-modern">
                    <h2 class="section-title">
                        <i class="fas fa-history"></i> Recent Activity
                    </h2>
                </div>
                <div class="activity-grid">
                    <!-- Recent Evaluations -->
                    <div class="content-card">
                        <div class="content-card-header">
                            <h3><i class="fas fa-star"></i> Recent Evaluations</h3>
                        </div>
                        <div class="content-card-body">
                            <?php if (empty($recent_evaluations)): ?>
                                <div class="empty-state-small">
                                    <i class="fas fa-inbox"></i>
                                    <p>No evaluations yet</p>
                                </div>
                            <?php else: ?>
                                <div class="eval-list">
                                    <?php foreach ($recent_evaluations as $eval): ?>
                                        <div class="eval-item">
                                            <div class="eval-avatar">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <div class="eval-info">
                                                <div class="eval-name"><?php echo htmlspecialchars($eval['instructor_name']); ?></div>
                                                <div class="eval-course"><?php echo htmlspecialchars($eval['course_name']); ?></div>
                                            </div>
                                            <div class="eval-rating">
                                                <div class="rating-badge">
                                                    <i class="fas fa-star"></i> <?php echo number_format($eval['rating'], 1); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Department Performance -->
                    <div class="content-card">
                        <div class="content-card-header">
                            <h3><i class="fas fa-chart-line"></i> Department Performance</h3>
                        </div>
                        <div class="content-card-body">
                            <?php if (empty($dept_performance)): ?>
                                <div class="empty-state-small">
                                    <i class="fas fa-chart-pie"></i>
                                    <p>No performance data</p>
                                </div>
                            <?php else: ?>
                                <div class="performance-list">
                                    <?php foreach (array_slice($dept_performance, 0, 4) as $dept): ?>
                                        <div class="performance-item">
                                            <div class="performance-info">
                                                <span class="performance-name"><?php echo htmlspecialchars($dept['department']); ?></span>
                                                <span class="performance-value"><?php echo number_format($dept['avg_rating'], 1); ?>/5.0</span>
                                            </div>
                                            <div class="progress-bar">
                                                <div class="progress" style="width: <?php echo ($dept['avg_rating'] / 5) * 100; ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="../../function/dashboard.js"></script>
    <script src="../../function/session_guard.js"></script>
</body>
</html>

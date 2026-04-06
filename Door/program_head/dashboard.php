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

// Get total instructors (active)
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM instructors");
    $result = $stmt->fetch();
    $total_instructors = $result['cnt'] ?? 0;
} catch (PDOException $e) {
    $total_instructors = 0;
}

// Get evaluation stats - check if table exists first
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

// Get active courses - check if table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'courses'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM courses WHERE status = 'active'");
        $result = $stmt->fetch();
        $active_courses = $result['cnt'] ?? 0;
    } else {
        // Alternative: count majors as courses if courses table doesn't exist
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM majors WHERE is_active = 1");
        $result = $stmt->fetch();
        $active_courses = $result['cnt'] ?? 0;
    }
} catch (PDOException $e) {
    $active_courses = 0;
}

// Fetch recent evaluations only if table exists
$recent_evaluations = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'evaluations'");
    if ($stmt->rowCount() > 0) {
        $sql = "SELECT 
            CONCAT(i.first_name, ' ', i.last_name) as instructor_name, 
            c.course_name, 
            e.rating, 
            e.evaluation_date 
            FROM evaluations e 
            JOIN instructors i ON e.instructor_id = i.id 
            JOIN courses c ON e.course_id = c.id 
            ORDER BY e.evaluation_date DESC 
            LIMIT 3";
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $recent_evaluations[] = $row;
        }
    }
} catch (PDOException $e) {
    $recent_evaluations = [];
}

// Fetch department performance only if evaluations table exists
$dept_performance = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'evaluations'");
    if ($stmt->rowCount() > 0) {
        $sql = "SELECT 
            COALESCE(e.department, 'General') as department, 
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../media/LOGO.jpg" type="image/jpeg">
    <title>Program Head Dashboard - Faculty Evaluation System</title>
    <link rel="stylesheet" href="../../css/common.css">
    <link rel="stylesheet" href="./style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body class="dashboard-page">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../../media/LOGO.jpg" alt="Logo" class="sidebar-logo" style="width: 70px; height: 70px; border-radius: 16px; object-fit: cover; border: 3px solid white; background: white; padding: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
            <div class="sidebar-brand">
                <span class="sidebar-brand-name">IBM</span>
                <span class="sidebar-brand-sub">Evaluation System</span>
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
                <span>Overview</span>
            </a>
            <a href="pages/evaluations.php" class="sidebar-nav-item">
                <i class="fas fa-clipboard-check"></i>
                <span>Evaluations</span>
            </a>
            <a href="pages/instructors.php" class="sidebar-nav-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Instructors</span>
            </a>
            <a href="pages/courses.php" class="sidebar-nav-item">
                <i class="fas fa-book"></i>
                <span>Courses</span>
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
    <div class="main-content" style="position: relative;">
        <!-- Background Logo -->
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        <!-- Topbar -->
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

        <!-- Dashboard Content -->
        <main class="dashboard-content" style="position: relative; z-index: 1;">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-banner-role">Program Head</div>
                <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p>Monitor instructor performance, manage evaluations, and track department progress all in one place.</p>
            </div>

            <!-- Stats Row -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon gold">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $total_instructors; ?></div>
                    <div class="stat-card-label">Total Instructors</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $completed_evaluations; ?></div>
                    <div class="stat-card-label">Completed Evaluations</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon blue">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $active_courses; ?></div>
                    <div class="stat-card-label">Active Courses</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon purple">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $avg_rating; ?></div>
                    <div class="stat-card-label">Avg. Rating</div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Recent Evaluations Card -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h3><i class="fas fa-clipboard-list"></i> Recent Evaluations</h3>
                        <a href="pages/evaluations.php" class="view-all">View All</a>
                    </div>
                    <div class="content-card-body">
                        <table class="eval-table">
                            <thead>
                                <tr>
                                    <th>Instructor</th>
                                    <th>Course</th>
                                    <th>Rating</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_evaluations as $eval): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($eval['instructor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($eval['course_name']); ?></td>
                                    <td><span class="rating-badge <?php echo $eval['rating'] >= 4.7 ? 'excellent' : ($eval['rating'] >= 4.4 ? 'good' : 'average'); ?>"><?php echo number_format($eval['rating'], 1); ?></span></td>
                                    <td><?php echo date('M j, Y', strtotime($eval['evaluation_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Department Performance Card -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h3><i class="fas fa-chart-bar"></i> Department Performance</h3>
                    </div>
                    <div class="content-card-body">
                        <div class="performance-list">
                            <?php foreach ($dept_performance as $dept): ?>
                            <div class="performance-item">
                                <div class="performance-info">
                                    <span class="performance-name"><?php echo htmlspecialchars($dept['department']); ?></span>
                                    <span class="performance-value"><?php echo number_format($dept['avg_rating'], 1); ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo round($dept['avg_rating'] * 20); ?>%;"></div>
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

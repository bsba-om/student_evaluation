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
$total_students = 0;

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
                ORDER BY e.evaluation_date DESC LIMIT 5";
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
    
    $yearLevels = [];
    foreach ($raw_year_levels as $year) {
        $base_year = $year['year_level'];
        if (strpos($base_year, '3rd Year') !== false) {
            $base_year = '3rd Year';
        }
        
        if (isset($yearLevels[$base_year])) {
            $yearLevels[$base_year] += $year['count'];
        } else {
            $yearLevels[$base_year] = $year['count'];
        }
    }
    
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

// Fetch recent instructor activities
$recent_instructors = [];
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name, status, email FROM instructors ORDER BY id DESC LIMIT 4");
    $recent_instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_instructors = [];
}

// Calculate evaluation completion rate
$eval_completion_rate = $total_instructors > 0 ? round(($completed_evaluations / max($total_instructors, 1)) * 100) : 0;
$eval_completion_rate = min($eval_completion_rate, 100);
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
                        <div class="welcome-actions">
                            <a href="pages/instructors.php" class="welcome-btn">
                                <i class="fas fa-chalkboard-teacher"></i> Manage Instructors
                            </a>
                            <a href="pages/reports.php" class="welcome-btn outline">
                                <i class="fas fa-chart-bar"></i> View Reports
                            </a>
                        </div>
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
                    <a href="pages/instructors.php" class="stats-highlight clickable-stat">
                        <div class="stat-card-inner">
                            <div class="stat-card-icon-wrap gold">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-value"><?php echo $total_instructors; ?></div>
                                <div class="stat-card-label">Total Instructors</div>
                                <div class="stat-card-sub">
                                    <span class="sub-on-duty"><i class="fas fa-circle"></i> <?php echo $on_duty; ?> On Duty</span>
                                    <span class="sub-on-leave"><i class="fas fa-circle"></i> <?php echo $on_leave; ?> On Leave</span>
                                </div>
                            </div>
                            <div class="stat-card-arrow"><i class="fas fa-arrow-right"></i></div>
                        </div>
                    </a>
                    
                    <a href="pages/reports.php" class="stats-highlight clickable-stat">
                        <div class="stat-card-inner">
                            <div class="stat-card-icon-wrap green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-value"><?php echo $completed_evaluations; ?></div>
                                <div class="stat-card-label">Completed Evaluations</div>
                                <div class="stat-card-sub">
                                    <span class="sub-rate"><i class="fas fa-chart-line"></i> <?php echo $eval_completion_rate; ?>% Completion Rate</span>
                                </div>
                            </div>
                            <div class="stat-card-arrow"><i class="fas fa-arrow-right"></i></div>
                        </div>
                    </a>
                    
                    <a href="pages/student_enrollment.php" class="stats-highlight clickable-stat">
                        <div class="stat-card-inner">
                            <div class="stat-card-icon-wrap blue">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-value"><?php echo $total_students; ?></div>
                                <div class="stat-card-label">Total Students</div>
                                <div class="stat-card-sub">
                                    <span class="sub-years"><i class="fas fa-layer-group"></i> <?php echo count($yearLevels); ?> Year Levels</span>
                                </div>
                            </div>
                            <div class="stat-card-arrow"><i class="fas fa-arrow-right"></i></div>
                        </div>
                    </a>
                    
                    <a href="pages/departments.php" class="stats-highlight clickable-stat">
                        <div class="stat-card-inner">
                            <div class="stat-card-icon-wrap purple">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-value"><?php echo $active_courses; ?></div>
                                <div class="stat-card-label">Active Courses</div>
                                <div class="stat-card-sub">
                                    <span class="sub-majors"><i class="fas fa-building"></i> <?php echo count($majors); ?> Majors</span>
                                </div>
                            </div>
                            <div class="stat-card-arrow"><i class="fas fa-arrow-right"></i></div>
                        </div>
                    </a>
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

            <!-- Main Content Grid -->
            <section class="main-grid-section">
                <div class="dashboard-grid">
                    <!-- Department Overview Card -->
                    <div class="content-card wide-card">
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
                                    <a href="pages/departments.php" class="btn-primary" style="margin-top: 16px; display: inline-flex;">
                                        <i class="fas fa-plus"></i> Add Department
                                    </a>
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
                                        <a href="pages/departments.php" class="dept-card">
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
                                        </a>
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
                                 <div class="dept-majors-section">
                                     <div class="section-header-modern">
                                         <h4 class="section-title">
                                             <i class="fas fa-layer-group"></i> Students by Year Level
                                         </h4>
                                         <a href="pages/student_enrollment.php" class="section-action">
                                             View Enrollment <i class="fas fa-arrow-right"></i>
                                         </a>
                                     </div>
                                     
                                     <div class="dept-majors-grid year-majors-grid">
                                         <?php 
                                         $year_colors = [
                                             '1st Year' => ['bg_class' => 'year-green', 'icon' => 'fa-user-graduate'],
                                             '2nd Year' => ['bg_class' => 'year-blue', 'icon' => 'fa-user-graduate'],
                                             '3rd Year' => ['bg_class' => 'year-purple', 'icon' => 'fa-user-graduate'],
                                             '4th Year' => ['bg_class' => 'year-amber', 'icon' => 'fa-user-graduate']
                                         ];
                                         $max_count = 0;
                                         foreach ($yearLevels as $y) {
                                             if (intval($y['count']) > $max_count) $max_count = intval($y['count']);
                                         }
                                         foreach ($yearLevels as $index => $year): 
                                             $count = intval($year['count']);
                                             $bar_pct = $max_count > 0 ? round(($count / $max_count) * 100) : 0;
                                             $pct_total = $total_students > 0 ? round(($count / $total_students) * 100) : 0;
                                             $year_key = $year['year_level'];
                                             $colors = $year_colors[$year_key] ?? $year_colors['1st Year'];
                                         ?>
                                         <a href="pages/student_enrollment.php?year=<?php echo urlencode($year['year_level']); ?>" class="dept-card year-dept-card">
                                             <div class="dept-card-header">
                                                 <div class="dept-icon <?php echo $colors['bg_class']; ?>">
                                                     <i class="fas <?php echo $colors['icon']; ?>"></i>
                                                 </div>
                                                 <span class="status-badge active"><?php echo htmlspecialchars($year['year_level']); ?></span>
                                             </div>
                                             <h4 class="dept-title"><?php echo htmlspecialchars($year['year_level']); ?></h4>
                                             <p class="dept-subtitle">Enrollment</p>
                                             <div class="dept-value"><?php echo $count; ?></div>
                                              <div class="progress-bar">
                                                  <div class="progress" style="width: <?php echo $bar_pct; ?>%;"></div>
                                              </div>
                                          </a>
                                         <?php endforeach; ?>
                                     </div>
                                 </div>
                                 <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right Column: Calendar + Performance -->
                    <div class="right-column-grid">
                        <!-- Calendar Card -->
                        <div class="content-card">
                            <div class="content-card-header">
                                <h3><i class="fas fa-calendar-alt"></i> Calendar</h3>
                                <button onclick="goToToday()" class="calendar-today-btn" title="Go to today">
                                    <i class="fas fa-crosshairs"></i> Today
                                </button>
                            </div>
                            <div class="content-card-body">
                                <div class="calendar-widget">
                                    <div class="calendar-header">
                                        <button onclick="changeMonth(-1)" class="calendar-nav-btn" title="Previous month">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <div id="calendar-title" class="calendar-month">January 2024</div>
                                        <button onclick="changeMonth(1)" class="calendar-nav-btn" title="Next month">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                    <div class="calendar-day-names">
                                        <span>Sun</span>
                                        <span>Mon</span>
                                        <span>Tue</span>
                                        <span>Wed</span>
                                        <span>Thu</span>
                                        <span>Fri</span>
                                        <span>Sat</span>
                                    </div>
                                    <div id="calendar-days" class="calendar-days"></div>
                                </div>
                                
                                <div class="instructor-status-section">
                                    <div class="status-title">Instructor Status</div>
                                    <div class="status-grid">
                                        <a href="pages/instructors.php?filter=on+duty" class="status-item on-duty">
                                            <div class="status-icon"><i class="fas fa-briefcase"></i></div>
                                            <div class="status-value"><?php echo $on_duty; ?></div>
                                            <div class="status-label">On Duty</div>
                                        </a>
                                        <a href="pages/instructors.php?filter=on+leave" class="status-item on-leave">
                                            <div class="status-icon"><i class="fas fa-bed"></i></div>
                                            <div class="status-value"><?php echo $on_leave; ?></div>
                                            <div class="status-label">On Leave</div>
                                        </a>
                                        <a href="pages/instructors.php?filter=on+travel" class="status-item on-travel">
                                            <div class="status-icon"><i class="fas fa-plane"></i></div>
                                            <div class="status-value"><?php echo $on_travel; ?></div>
                                            <div class="status-label">On Travel</div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Overview Card -->
                        <div class="content-card">
                            <div class="content-card-header gold-header">
                                <h3><i class="fas fa-chart-line"></i> Performance Overview</h3>
                                <a href="pages/reports.php" class="view-all">
                                    Full Report <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                            <div class="content-card-body">
                                <div class="performance-summary">
                                    <div class="perf-stat">
                                        <span class="perf-label">Average Rating</span>
                                        <span class="perf-value"><?php echo number_format($avg_rating, 1); ?>/5.0</span>
                                        <div class="perf-stars">
                                            <?php 
                                            $full_stars = floor($avg_rating);
                                            $half_star = ($avg_rating - $full_stars) >= 0.5;
                                            for ($i = 0; $i < 5; $i++):
                                                if ($i < $full_stars): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php elseif ($i === $full_stars && $half_star): ?>
                                                    <i class="fas fa-star-half-alt"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif;
                                            endfor; ?>
                                        </div>
                                    </div>
                                    <div class="perf-stat">
                                        <span class="perf-label">Completion Rate</span>
                                        <span class="perf-value"><?php echo $eval_completion_rate; ?>%</span>
                                        <div class="perf-progress-mini">
                                            <div class="perf-progress-bar" style="width: <?php echo $eval_completion_rate; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($dept_performance)): ?>
                                <div class="perf-list">
                                    <div class="perf-list-title">Department Ratings</div>
                                    <?php foreach (array_slice($dept_performance, 0, 4) as $dept): ?>
                                    <div class="perf-item">
                                        <div class="perf-info">
                                            <span class="perf-name"><?php echo htmlspecialchars($dept['department']); ?></span>
                                            <span class="perf-rating"><?php echo number_format($dept['avg_rating'], 1); ?>/5.0</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: <?php echo ($dept['avg_rating'] / 5) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                    <div class="empty-state-small">
                                        <i class="fas fa-chart-pie"></i>
                                        <p>No performance data yet</p>
                                        <a href="pages/reports.php" class="btn-primary" style="margin-top: 12px; font-size: 12px; padding: 10px 18px;">
                                            <i class="fas fa-plus"></i> Start Evaluation
                                        </a>
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
        </main>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="toast-container"></div>

    <script src="../../function/dashboard.js"></script>
    <script src="../../function/session_guard.js"></script>
    <script>
    // ==========================================
    // CALENDAR FUNCTIONALITY - Fully Working
    // ==========================================
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();

    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    function renderCalendar() {
        const calendarTitle = document.getElementById('calendar-title');
        const calendarDays = document.getElementById('calendar-days');
        
        if (!calendarTitle || !calendarDays) return;

        // Update title
        calendarTitle.textContent = monthNames[currentMonth] + ' ' + currentYear;

        // Get first day of month and total days
        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const totalDays = new Date(currentYear, currentMonth + 1, 0).getDate();
        
        // Today's date for highlighting
        const today = new Date();
        const isCurrentMonth = (today.getMonth() === currentMonth && today.getFullYear() === currentYear);

        // Build calendar HTML
        let html = '';
        
        // Empty cells for days before the 1st
        for (let i = 0; i < firstDay; i++) {
            html += '<div class="calendar-day empty"></div>';
        }
        
        // Day cells
        for (let day = 1; day <= totalDays; day++) {
            const isToday = isCurrentMonth && day === today.getDate();
            const isWeekend = (new Date(currentYear, currentMonth, day).getDay() === 0 || 
                              new Date(currentYear, currentMonth, day).getDay() === 6);
            
            let classes = 'calendar-day';
            if (isToday) classes += ' today';
            if (isWeekend) classes += ' weekend';
            
            html += '<div class="' + classes + '" data-day="' + day + '" onclick="selectDay(' + day + ')">' + day + '</div>';
        }
        
        calendarDays.innerHTML = html;
    }

    function changeMonth(direction) {
        currentMonth += direction;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        } else if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar();
    }

    function goToToday() {
        const today = new Date();
        currentMonth = today.getMonth();
        currentYear = today.getFullYear();
        renderCalendar();
        showToast('Calendar set to today', 'info');
    }

    function selectDay(day) {
        // Remove previous selection
        document.querySelectorAll('.calendar-day.selected').forEach(function(el) {
            el.classList.remove('selected');
        });
        // Add selection
        const dayEl = document.querySelector('.calendar-day[data-day="' + day + '"]');
        if (dayEl) {
            dayEl.classList.add('selected');
        }
        const selectedDate = monthNames[currentMonth] + ' ' + day + ', ' + currentYear;
        showToast('Selected: ' + selectedDate, 'info');
    }

    // Initialize calendar on page load
    document.addEventListener('DOMContentLoaded', function() {
        renderCalendar();
    });

    // ==========================================
    // TIME UPDATE
    // ==========================================
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

    // ==========================================
    // TOAST NOTIFICATIONS
    // ==========================================
    function showToast(message, type) {
        type = type || 'info';
        const container = document.getElementById('toast-container');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        
        const icons = {
            'info': 'fas fa-info-circle',
            'success': 'fas fa-check-circle',
            'warning': 'fas fa-exclamation-triangle',
            'error': 'fas fa-times-circle'
        };
        
        toast.innerHTML = '<i class="' + (icons[type] || icons.info) + '"></i><span>' + message + '</span>';
        container.appendChild(toast);
        
        // Trigger animation
        setTimeout(function() { toast.classList.add('show'); }, 10);
        
        // Auto remove
        setTimeout(function() {
            toast.classList.remove('show');
            setTimeout(function() { toast.remove(); }, 300);
        }, 3000);
    }

    // ==========================================
    // SIDEBAR TOGGLE
    // ==========================================
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('open') && 
            !sidebar.contains(e.target) && 
            e.target !== menuToggle && 
            !menuToggle.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    });

    // ==========================================
    // AUTO-DISMISS ALERTS
    // ==========================================
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function() { alert.remove(); }, 300);
        }, 5000);
    });
    </script>
</body>
</html>
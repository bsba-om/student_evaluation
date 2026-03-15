<?php
require_once '../../data/session_security.php';
check_auth('instructor', '../login.php');
require_once '../../data/config.php';

$instructor_id = $_SESSION['user_id'] ?? 1;
$user_name = $_SESSION['user_name'] ?? 'Jane Teacher';

// Fetch stats
$course_count = 0;
$student_count = 0;
$avg_rating = 0;
$new_feedback = 0;

$sql = "SELECT COUNT(*) as cnt FROM instructor_courses WHERE instructor_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) { $stmt->bind_param("i", $instructor_id); $stmt->execute(); $result = $stmt->get_result(); $row = $result->fetch_assoc(); $course_count = $row['cnt']; $stmt->close(); }

$sql = "SELECT COALESCE(SUM(c.student_count),0) as cnt FROM instructor_courses ic JOIN courses c ON ic.course_id = c.id WHERE ic.instructor_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) { $stmt->bind_param("i", $instructor_id); $stmt->execute(); $result = $stmt->get_result(); $row = $result->fetch_assoc(); $student_count = $row['cnt']; $stmt->close(); }

$sql = "SELECT COALESCE(AVG(e.rating),0) as avg_r FROM evaluations e WHERE e.instructor_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) { $stmt->bind_param("i", $instructor_id); $stmt->execute(); $result = $stmt->get_result(); $row = $result->fetch_assoc(); $avg_rating = round($row['avg_r'], 1); $stmt->close(); }

$sql = "SELECT COUNT(*) as cnt FROM evaluation_feedback WHERE instructor_id = ? AND feedback_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$stmt = $conn->prepare($sql);
if ($stmt) { $stmt->bind_param("i", $instructor_id); $stmt->execute(); $result = $stmt->get_result(); $row = $result->fetch_assoc(); $new_feedback = $row['cnt']; $stmt->close(); }

// Fetch recent evaluations
$recent_evaluations = [];
$sql = "SELECT c.course_name, c.student_count, e.rating, e.evaluation_date FROM evaluations e JOIN courses c ON e.course_id = c.id WHERE e.instructor_id = ? ORDER BY e.evaluation_date DESC LIMIT 3";
$stmt = $conn->prepare($sql);
if ($stmt) { $stmt->bind_param("i", $instructor_id); $stmt->execute(); $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) { $recent_evaluations[] = $row; } $stmt->close(); }

// Fetch recent feedback
$recent_feedback = [];
$sql = "SELECT course_name, feedback_text, rating, feedback_date FROM evaluation_feedback WHERE instructor_id = ? ORDER BY feedback_date DESC LIMIT 2";
$stmt = $conn->prepare($sql);
if ($stmt) { $stmt->bind_param("i", $instructor_id); $stmt->execute(); $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) { $recent_feedback[] = $row; } $stmt->close(); }
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
                <span class="sidebar-user-role">Instructor</span>
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
                <span>My Evaluations</span>
            </a>
            <a href="pages/courses.php" class="sidebar-nav-item">
                <i class="fas fa-book"></i>
                <span>My Courses</span>
            </a>
            <a href="pages/students.php" class="sidebar-nav-item">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <a href="pages/feedback.php" class="sidebar-nav-item">
                <i class="fas fa-comment-dots"></i>
                <span>Feedback</span>
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
        <header class="topbar">
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
                <div class="welcome-banner-role">Instructor</div>
                <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p>Track your course performance, view student evaluations, and improve your teaching methods.</p>
            </div>

            <!-- Stats Row -->
            <div class="stats-row">
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

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
$recent_evaluations = [];
$recent_feedback = [];

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

<?php
require_once '../../../data/session_security.php';

// Check role access
$role_access = check_role_access('instructor');
$show_role_modal = !$role_access['allowed'];

$instructor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Jane Teacher';

// Only fetch data if access is allowed
if (!$show_role_modal) {

// Fetch summary stats
$total_feedback = 0;
$new_this_month = 0;
$avg_rating = 0;

$sql = "SELECT COUNT(*) as cnt, COALESCE(AVG(rating),0) as avg_r FROM evaluation_feedback WHERE instructor_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) { $stmt->bind_param("i", $instructor_id); $stmt->execute(); $result = $stmt->get_result(); $row = $result->fetch_assoc(); $total_feedback = $row['cnt']; $avg_rating = round($row['avg_r'], 1); $stmt->close(); }

$sql = "SELECT COUNT(*) as cnt FROM evaluation_feedback WHERE instructor_id = ? AND feedback_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$stmt = $conn->prepare($sql);
if ($stmt) { $stmt->bind_param("i", $instructor_id); $stmt->execute(); $result = $stmt->get_result(); $row = $result->fetch_assoc(); $new_this_month = $row['cnt']; $stmt->close(); }

// Fetch course list for filter
$course_list = [];
$sql = "SELECT DISTINCT c.course_code, c.course_name FROM evaluation_feedback ef JOIN courses c ON ef.course_id = c.id WHERE ef.instructor_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) { $stmt->bind_param("i", $instructor_id); $stmt->execute(); $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) { $course_list[] = $row; } $stmt->close(); }

// Fetch all feedback
$feedbacks = [];
$sql = "SELECT course_name, feedback_text, rating, feedback_date FROM evaluation_feedback WHERE instructor_id = ? ORDER BY feedback_date DESC";
$stmt = $conn->prepare($sql);
if ($stmt) { $stmt->bind_param("i", $instructor_id); $stmt->execute(); $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) { $feedbacks[] = $row; } $stmt->close(); }

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../../media/LOGO.jpg" type="image/jpeg">
    <title>Feedback - Faculty Evaluation System</title>
    <link rel="stylesheet" href="../../../css/common.css">
    <link rel="stylesheet" href="../style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body class="dashboard-page">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../../../media/LOGO.jpg" alt="Logo" class="sidebar-logo" style="width: 70px; height: 70px; border-radius: 16px; object-fit: cover; border: 3px solid white; background: white; padding: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
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
            <a href="../dashboard.php" class="sidebar-nav-item">
                <i class="fas fa-chart-pie"></i>
                <span>Overview</span>
            </a>
            <a href="evaluations.php" class="sidebar-nav-item">
                <i class="fas fa-clipboard-check"></i>
                <span>My Evaluations</span>
            </a>
            <a href="courses.php" class="sidebar-nav-item">
                <i class="fas fa-book"></i>
                <span>My Courses</span>
            </a>
            <a href="students.php" class="sidebar-nav-item">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <a href="feedback.php" class="sidebar-nav-item active">
                <i class="fas fa-comment-dots"></i>
                <span>Feedback</span>
            </a>
            <a href="reports.php" class="sidebar-nav-item">
                <i class="fas fa-file-alt"></i>
                <span>Reports</span>
            </a>
            <a href="profile.php" class="sidebar-nav-item">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content" style="position: relative;">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <div class="topbar-title">Feedback</div>
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
            <div class="eval-page-header">
                <h2><i class="fas fa-comment-dots"></i> Feedback</h2>
                <div class="mentees-header-right">
                    <select class="eval-filter-select">
                        <option>All Courses</option>
                        <?php foreach ($course_list as $cl): ?>
                        <option><?php echo htmlspecialchars($cl['course_code'] . ' - ' . $cl['course_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="eval-summary-row">
                <div class="eval-summary-card">
                    <div class="eval-summary-icon teal">
                        <i class="fas fa-comment-dots"></i>
                    </div>
                    <div class="eval-summary-info">
                        <h4><?php echo $total_feedback; ?></h4>
                        <p>Total Feedback</p>
                    </div>
                </div>
                <div class="eval-summary-card">
                    <div class="eval-summary-icon amber">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="eval-summary-info">
                        <h4><?php echo $new_this_month; ?></h4>
                        <p>New This Month</p>
                    </div>
                </div>
                <div class="eval-summary-card">
                    <div class="eval-summary-icon rose">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="eval-summary-info">
                        <h4><?php echo $avg_rating; ?></h4>
                        <p>Average Rating</p>
                    </div>
                </div>
            </div>
            
            <div class="content-grid" style="grid-template-columns: 1fr;">
                <div class="content-card">
                    <div class="content-card-header">
                        <h3><i class="fas fa-star"></i> Recent Feedback</h3>
                    </div>
                    <div class="content-card-body">
                        <div class="feedback-list">
                            <?php foreach ($feedbacks as $fb): ?>
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

    <script src="../../../function/dashboard.js"></script>
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

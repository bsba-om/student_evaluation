<?php
require_once '../../../data/session_security.php';

// Check role access
$role_access = check_role_access('instructor');
$show_role_modal = !$role_access['allowed'];

$instructor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Jane Teacher';

// Only fetch data if access is allowed
if (!$show_role_modal) {
    require_once '../../../data/config.php';
    
    // Initialize variables
    $feedback_items = [];
    $total_feedback = 0;
    $avg_rating = 0;
    $recent_feedback = [];
    
    // Fetch feedback data with course and student info
    try {
        $sql = "SELECT e.id, e.rating, e.comments, e.evaluation_date,
                       c.course_name, c.course_code,
                       s.first_name as student_first, s.last_name as student_last
                FROM evaluations e
                JOIN courses c ON e.course_id = c.id
                LEFT JOIN students s ON e.student_id = s.id
                WHERE e.instructor_id = ? AND e.comments IS NOT NULL AND TRIM(e.comments) != ''
                ORDER BY e.evaluation_date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$instructor_id]);
        $feedback_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_feedback = count($feedback_items);
        
        // Calculate overall average rating from evaluations (not just feedback ones)
        $stmt2 = $pdo->prepare("SELECT COALESCE(AVG(rating),0) as avg_r FROM evaluations WHERE instructor_id = ?");
        $stmt2->execute([$instructor_id]);
        $result = $stmt2->fetch();
        $avg_rating = round($result['avg_r'], 1);
        
    } catch (PDOException $e) {
        $feedback_items = [];
        $total_feedback = 0;
        $avg_rating = 0;
    }
    
    // Get recent 5 feedback items for quick view
    $recent_feedback = array_slice($feedback_items, 0, 5);
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
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-banner-role">Instructor</div>
                <h1>Student Feedback</h1>
                <p>Review comments and suggestions from your students</p>
            </div>
            
            <!-- Stats Row -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-comment-dots"></i></div>
                    <div class="stat-info">
                        <h4><?php echo $total_feedback; ?></h4>
                        <p>Total Feedback</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fas fa-star"></i></div>
                    <div class="stat-info">
                        <h4><?php echo $avg_rating; ?></h4>
                        <p>Overall Rating</p>
                    </div>
                </div>
            </div>
            
            <!-- Feedback List -->
            <div class="content-card">
                <div class="content-card-header">
                    <h3><i class="fas fa-comments"></i> All Feedback</h3>
                    <input type="text" id="feedbackSearch" placeholder="Search feedback..." style="width: 250px; padding: 8px 12px; border: 1px solid var(--border-light); border-radius: 8px; font-size: 13px;" onkeyup="filterFeedback()">
                </div>
                <div class="content-card-body">
                    <?php if (empty($feedback_items)): ?>
                    <div class="empty-state" style="padding: 60px 20px;">
                        <i class="fas fa-comment-slash" style="font-size: 48px; opacity: 0.3; margin-bottom: 16px; display: block;"></i>
                        <h3>No Feedback Yet</h3>
                        <p>Students haven't provided any feedback comments.</p>
                    </div>
                    <?php else: ?>
                    <div class="feedback-list" style="display: flex; flex-direction: column; gap: 16px;">
                        <?php foreach ($feedback_items as $fb): 
                            $rating = (float)($fb['rating'] ?? 0);
                            $course = htmlspecialchars($fb['course_name'] ?? $fb['course_code'] ?? 'Unknown Course');
                            $date = date('M j, Y', strtotime($fb['evaluation_date']));
                            $comment = trim($fb['comments'] ?? '');
                            $student_name = '';
                            if (!empty($fb['student_first']) || !empty($fb['student_last'])) {
                                $student_name = htmlspecialchars(trim($fb['student_first'] . ' ' . $fb['student_last']));
                            } else {
                                $student_name = '<span class="text-muted">Anonymous</span>';
                            }
                        ?>
                        <div class="feedback-item" style="background: white; border: 1px solid var(--border-light); border-radius: 12px; padding: 20px; transition: all 0.2s ease;">
                            <div class="feedback-header" style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <span class="feedback-course" style="font-weight: 600; color: var(--gold-dark);"><?php echo $course; ?></span>
                                    <span style="font-size: 12px; color: var(--light-text);"><?php echo $student_name; ?></span>
                                </div>
                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                    <span class="feedback-date" style="font-size: 12px; color: var(--light-text);"><?php echo $date; ?></span>
                                    <span class="rating-badge <?php echo $rating >= 4.7 ? 'excellent' : ($rating >= 4.4 ? 'good' : 'average'); ?>" style="font-size: 12px; padding: 4px 10px;">
                                        <?php echo number_format($rating, 1); ?>
                                    </span>
                                </div>
                            </div>
                            <p class="feedback-text" style="font-size: 14px; color: var(--dark-text); line-height: 1.6; margin: 0;">"<?php echo htmlspecialchars($comment); ?>"</p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function filterFeedback() {
            const input = document.getElementById('feedbackSearch');
            const filter = input.value.toLowerCase();
            const items = document.querySelectorAll('.feedback-item');
            
            items.forEach(item => {
                const text = item.textContent || item.innerText;
                item.style.display = text.toLowerCase().includes(filter) ? 'flex' : 'none';
            });
        }
    </script>
    
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

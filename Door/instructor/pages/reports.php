<?php
require_once '../../../data/session_security.php';

// Check role access
$role_access = check_role_access('instructor');
$show_role_modal = !$role_access['allowed'];

$instructor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Jane Teacher';

// Only fetch data if access is allowed
if (!$show_role_modal) {

// Fetch report stats
$pdf_count = 0;
$excel_count = 0;
$total_downloads = 0;

$sql = "SELECT report_type, COUNT(*) as cnt, SUM(download_count) as downloads FROM reports WHERE generated_by = 'instructor' GROUP BY report_type";
$result = $conn->query($sql);
if ($result) { while ($row = $result->fetch_assoc()) { if ($row['report_type'] == 'pdf') { $pdf_count = $row['cnt']; } else { $excel_count = $row['cnt']; } $total_downloads += $row['downloads']; } }

// Fetch reports
$reports = [];
$sql = "SELECT report_name, report_description, report_type, icon_class FROM reports WHERE generated_by = 'instructor' ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result) { while ($row = $result->fetch_assoc()) { $reports[] = $row; } }

// Fetch quick stats (course ratings for this instructor)
$quick_stats = [];
$sql = "SELECT c.course_code, c.course_name, COALESCE(AVG(e.rating),0) as avg_rating FROM instructor_courses ic JOIN courses c ON ic.course_id = c.id LEFT JOIN evaluations e ON e.course_id = c.id AND e.instructor_id = ic.instructor_id WHERE ic.instructor_id = ? GROUP BY c.id ORDER BY avg_rating DESC LIMIT 3";
$stmt = $conn->prepare($sql);
if ($stmt) { $stmt->bind_param("i", $instructor_id); $stmt->execute(); $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) { $quick_stats[] = $row; } $stmt->close(); }

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
</head>

<body class="dashboard-page">
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

    <!-- Main Content -->
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
            <div class="eval-page-header">
                <h2><i class="fas fa-file-alt"></i> Reports</h2>
            </div>
            
            <div class="eval-summary-row">
                <div class="eval-summary-card">
                    <div class="eval-summary-icon teal">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="eval-summary-info">
                        <h4><?php echo $pdf_count; ?></h4>
                        <p>PDF Reports</p>
                    </div>
                </div>
                <div class="eval-summary-card">
                    <div class="eval-summary-icon amber">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <div class="eval-summary-info">
                        <h4><?php echo $excel_count; ?></h4>
                        <p>Excel Reports</p>
                    </div>
                </div>
                <div class="eval-summary-card">
                    <div class="eval-summary-icon rose">
                        <i class="fas fa-download"></i>
                    </div>
                    <div class="eval-summary-info">
                        <h4><?php echo $total_downloads; ?></h4>
                        <p>Downloads</p>
                    </div>
                </div>
            </div>
            
            <div class="content-grid">
                <div class="content-card">
                    <div class="content-card-header">
                        <h3><i class="fas fa-list"></i> Available Reports</h3>
                    </div>
                    <div class="content-card-body">
                        <div class="report-list">
                            <?php foreach ($reports as $report): ?>
                            <div class="report-item">
                                <div class="report-icon">
                                    <i class="<?php echo htmlspecialchars($report['icon_class']); ?>"></i>
                                </div>
                                <div class="report-info">
                                    <h4><?php echo htmlspecialchars($report['report_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($report['report_description']); ?></p>
                                </div>
                                <button class="btn-primary" style="padding: 8px 16px; font-size: 12px;">
                                    <i class="fas fa-download"></i> Download
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="content-card">
                    <div class="content-card-header">
                        <h3><i class="fas fa-chart-bar"></i> Quick Stats</h3>
                    </div>
                    <div class="content-card-body">
                        <div class="performance-list">
                            <?php foreach ($quick_stats as $stat): ?>
                            <div class="performance-item">
                                <div class="performance-info">
                                    <span class="performance-name"><?php echo htmlspecialchars($stat['course_code'] . ' - ' . $stat['course_name']); ?></span>
                                    <span class="performance-value"><?php echo number_format($stat['avg_rating'], 1); ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo round($stat['avg_rating'] * 20); ?>%;"></div>
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

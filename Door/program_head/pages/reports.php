<?php
require_once '../../../data/session_security.php';

// Check role access - returns array with access status
$role_access = check_role_access('program_head');
$show_role_modal = !$role_access['allowed'];

$user_name = $_SESSION['user_name'] ?? 'Program Head';

// Only fetch data if access is allowed
if (!$show_role_modal) {
    require_once '../../../data/config.php';
    
    // Fetch stats with error handling
    $total_evaluations = 0;
    $avg_rating = 0;
    $active_instructors = 0;
    $completion_rate = 0;
    $top_performers = [];
    $course_performance = [];
    $dept_distribution = [];
    $monthly_evals = [];
    $rating_dist = [0, 0, 0, 0, 0];
    
    // Check which tables exist
    $has_evaluations = false;
    $has_courses = false;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'evaluations'");
        $has_evaluations = $stmt->rowCount() > 0;
        $stmt = $pdo->query("SHOW TABLES LIKE 'courses'");
        $has_courses = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $has_evaluations = false;
        $has_courses = false;
    }
    
    // Total evaluations
    if ($has_evaluations) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM evaluations");
            $result = $stmt->fetch();
            $total_evaluations = $result['cnt'] ?? 0;
        } catch (PDOException $e) {
            $total_evaluations = 0;
        }
    }
    
    // Average rating
    if ($has_evaluations) {
        try {
            $stmt = $pdo->query("SELECT COALESCE(AVG(rating),0) as avg_r FROM evaluations");
            $result = $stmt->fetch();
            $avg_rating = round($result['avg_r'], 1);
        } catch (PDOException $e) {
            $avg_rating = 0;
        }
    }
    
    // Active instructors
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM instructors WHERE status = 'active'");
        $result = $stmt->fetch();
        $active_instructors = $result['cnt'] ?? 0;
    } catch (PDOException $e) {
        $active_instructors = 0;
    }
    
    // Completion rate (from courses)
    if ($has_courses) {
        try {
            $stmt = $pdo->query("SELECT COALESCE(SUM(student_count),0) as total, COALESCE(SUM(evaluated_count),0) as evaluated FROM courses WHERE status = 'active'");
            $result = $stmt->fetch();
            $total_students = $result['total'] ?? 0;
            $evaluated_students = $result['evaluated'] ?? 0;
            $completion_rate = $total_students > 0 ? round(($evaluated_students / $total_students) * 100) : 0;
        } catch (PDOException $e) {
            $completion_rate = 0;
        }
    }
    
    // Top performers (only if evaluations table exists)
    if ($has_evaluations) {
        try {
            $sql = "SELECT 
                CONCAT(i.first_name, ' ', i.last_name) as instructor_name, 
                COALESCE(i.department, 'General') as department,
                COUNT(e.id) as total_evals, 
                COALESCE(AVG(e.rating),0) as avg_rating
                FROM instructors i
                LEFT JOIN evaluations e ON e.instructor_id = i.id
                WHERE i.status = 'active' OR i.status IS NULL
                GROUP BY i.id
                ORDER BY avg_rating DESC
                LIMIT 5";
            $stmt = $pdo->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $top_performers[] = $row;
            }
        } catch (PDOException $e) {
            $top_performers = [];
        }
    }
    
    // Course performance (only if both courses and evaluations exist)
    if ($has_courses && $has_evaluations) {
        try {
            $sql = "SELECT 
                COALESCE(c.course_code, c.course_name) as course_identifier,
                CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
                COALESCE(c.student_count, 0) as student_count, 
                COALESCE(c.evaluated_count, 0) as evaluated_count, 
                COALESCE(AVG(e.rating),0) as avg_rating
                FROM courses c
                LEFT JOIN instructors i ON c.instructor_id = i.id
                LEFT JOIN evaluations e ON e.course_id = c.id
                WHERE c.status = 'active'
                GROUP BY c.id
                ORDER BY avg_rating DESC
                LIMIT 4";
            $stmt = $pdo->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $course_performance[] = $row;
            }
        } catch (PDOException $e) {
            $course_performance = [];
        }
    }
    
    // Department distribution (only if evaluations exists)
    if ($has_evaluations) {
        try {
            $sql = "SELECT COALESCE(department, 'General') as department, COUNT(*) as cnt FROM evaluations GROUP BY department ORDER BY cnt DESC";
            $stmt = $pdo->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dept_distribution[] = $row;
            }
        } catch (PDOException $e) {
            $dept_distribution = [];
        }
    }
    
    // Monthly evaluation counts (only if evaluations exists)
    if ($has_evaluations) {
        try {
            $sql = "SELECT DATE_FORMAT(evaluation_date, '%b') as month_label, COUNT(*) as cnt FROM evaluations GROUP BY DATE_FORMAT(evaluation_date, '%Y-%m') ORDER BY evaluation_date ASC";
            $stmt = $pdo->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $monthly_evals[] = $row;
            }
        } catch (PDOException $e) {
            $monthly_evals = [];
        }
    }
    
    // Rating distribution (only if evaluations exists)
    if ($has_evaluations) {
        try {
            $sql = "SELECT FLOOR(rating) as star, COUNT(*) as cnt FROM evaluations GROUP BY FLOOR(rating) ORDER BY star DESC";
            $stmt = $pdo->query($sql);
            $rating_dist = [0, 0, 0, 0, 0];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $idx = intval($row['star']) - 1;
                if ($idx >= 0 && $idx < 5) {
                    $rating_dist[$idx] = $row['cnt'];
                }
            }
            $rating_dist = array_reverse($rating_dist);
        } catch (PDOException $e) {
            $rating_dist = [0, 0, 0, 0, 0];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Program Head Dashboard</title>
    <link rel="stylesheet" href="../../../css/common.css">
    <link rel="stylesheet" href="../style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --gold: #B8860B; --gold-light: #D4A843; --gold-dark: #8B6914; --cream: #f7f5ef; --cream-light: #f0ebe3; --white: #ffffff; --dark-text: #1f1f1f; --dark-text-2: #4a5568; --light-text: #666666; --border-light: #d4cfc5; --border-soft: #e8e4da; --success: #059669; --success-light: #c6f6d5; --danger: #dc2626; --danger-light: #fee2e2; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; color: var(--dark-text); overflow-x: hidden; }
        .page-container { padding: 32px; }
        .welcome-banner { background: linear-gradient(160deg, #6b5a00 0%, var(--gold-light) 40%, var(--gold-dark) 100%); border-radius: 20px; padding: 36px 44px; color: white; margin-bottom: 32px; box-shadow: 0 8px 32px rgba(139, 105, 20, 0.4); position: relative; overflow: hidden; }
        .welcome-banner::before { content: ''; position: absolute; top: -50%; right: -10%; width: 300px; height: 300px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; }
        .welcome-banner h1 { font-size: 28px; font-weight: 800; margin: 0 0 12px 0; position: relative; z-index: 1; }
        .welcome-banner p { font-size: 15px; opacity: 0.95; margin: 0; max-width: 600px; position: relative; z-index: 1; }
        .report-filters { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
        .filter-group { display: flex; align-items: center; gap: 8px; }
        .filter-group label { font-size: 14px; font-weight: 600; color: var(--dark-text-2); }
        .filter-group select { padding: 10px 16px; border: 2px solid var(--border-light); border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px; color: var(--dark-text); background: var(--white); cursor: pointer; }
        .filter-group select:focus { outline: none; border-color: var(--gold); }
        .btn-generate { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: white; padding: 10px 20px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(184, 134, 11, 0.3); }
        .btn-export { background: var(--white); color: var(--dark-text-2); padding: 10px 20px; border: 2px solid var(--border-light); border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-export:hover { border-color: var(--gold); color: var(--gold-dark); }
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 32px; }
        .stat-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid var(--border-soft); transition: all 0.3s ease; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 5px; }
        .stat-card.gold::before { background: linear-gradient(90deg, var(--gold), var(--gold-light)); }
        .stat-card.green::before { background: linear-gradient(90deg, #059669, #34d399); }
        .stat-card.blue::before { background: linear-gradient(90deg, #0284c7, #38bdf8); }
        .stat-card.purple::before { background: linear-gradient(90deg, #7c3aed, #a78bfa); }
        .stat-card:hover { transform: translateY(-6px); box-shadow: 0 8px 24px rgba(0,0,0,0.15); }
        .stat-card-value { font-size: 36px; font-weight: 800; color: var(--dark-text); line-height: 1; margin-bottom: 8px; }
        .stat-card-label { font-size: 13px; color: var(--light-text); font-weight: 600; }
        .charts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 32px; }
        .chart-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid var(--border-soft); }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .chart-title { font-size: 18px; font-weight: 700; color: var(--dark-text); }
        .chart-container { position: relative; height: 300px; }
        .full-width-chart { grid-column: span 2; }
        .card { background: var(--white); border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid var(--border-soft); margin-bottom: 24px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid var(--cream-light); }
        .card-title { font-size: 18px; font-weight: 700; color: var(--dark-text); display: flex; align-items: center; gap: 10px; }
        .card-title i { color: var(--gold-dark); }
        .data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .data-table th { padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; color: var(--light-text); background: var(--cream-light); border-bottom: 2px solid var(--border-light); }
        .data-table td { padding: 16px; font-size: 14px; color: var(--dark-text-2); border-bottom: 1px solid var(--border-soft); word-wrap: break-word; }
        .data-table tbody tr { transition: all 0.2s ease; }
        .data-table tbody tr:hover { background: var(--cream-light); }
        .progress-bar { height: 10px; background: var(--border-light); border-radius: 10px; overflow: hidden; }
        .progress { height: 100%; border-radius: 10px; }
        .progress.gold { background: linear-gradient(90deg, var(--gold), var(--gold-light)); }
        .progress.green { background: linear-gradient(90deg, #059669, #34d399); }
        .progress.blue { background: linear-gradient(90deg, #0284c7, #38bdf8); }
        .progress.purple { background: linear-gradient(90deg, #7c3aed, #a78bfa); }
        .rank-badge { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; font-size: 14px; font-weight: 700; }
        .rank-badge.gold { background: linear-gradient(135deg, #FFD700, #FFA500); color: white; }
        .rank-badge.silver { background: linear-gradient(135deg, #C0C0C0, #A8A8A8); color: white; }
        .rank-badge.bronze { background: linear-gradient(135deg, #CD7F32, #B8860B); color: white; }
        .rank-badge.default { background: var(--border-light); color: var(--light-text); }
        .btn-view { background: var(--cream-light); color: var(--gold-dark); padding: 8px 16px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .btn-view:hover { background: var(--gold); color: white; }
        .progress-cell { width: 200px; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .stat-card, .chart-card, .card { animation: fadeInUp 0.5s ease forwards; }
        .welcome-banner { animation: fadeInUp 0.5s ease forwards; }
    </style>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../../../media/LOGO.jpg" alt="Logo" class="sidebar-logo" style="width: 70px; height: 70px; border-radius: 16px; object-fit: cover; border: 3px solid white; background: white; padding: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
            <div class="sidebar-brand"><span class="sidebar-brand-name">IBM</span></div>
        </div>
        <div class="sidebar-user">
            <div class="sidebar-avatar"><i class="fas fa-user"></i></div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo htmlspecialchars($user_name); ?></span>
                <span class="sidebar-user-role">Program Head</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-nav-label">Menu</div>
            <a href="../dashboard.php" class="sidebar-nav-item"><i class="fas fa-chart-pie"></i><span>Overview</span></a>
             <a href="instructors.php" class="sidebar-nav-item"><i class="fas fa-chalkboard-teacher"></i><span>Instructors</span></a>
              <a href="student_enrollment.php" class="sidebar-nav-item"><i class="fas fa-user-graduate"></i><span>Enrollment</span></a>
               <a href="mentee_flow.php" class="sidebar-nav-item"><i class="fas fa-users"></i><span>MenteeFlow</span></a>
              <a href="departments.php" class="sidebar-nav-item"><i class="fas fa-building"></i><span>Departments</span></a>
            <a href="reports.php" class="sidebar-nav-item active"><i class="fas fa-file-alt"></i><span>Reports</span></a>
            <a href="settings.php" class="sidebar-nav-item"><i class="fas fa-cog"></i><span>Settings</span></a>
        </nav>
    </aside>
    <div class="main-content" style="position: relative;">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div><div class="topbar-title">Reports</div><div class="topbar-subtitle">Program Head Panel</div></div>
            </div>
            <div class="topbar-right">
                <div class="topbar-date"><i class="fas fa-calendar-alt"></i><span><?php echo date('F j, Y'); ?></span></div>
                <a href="../../../data/logout.php" class="topbar-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </header>
        <main class="dashboard-content">
            <div class="page-container">
                <div class="welcome-banner">
                    <h1>Analytics & Reports</h1>
                    <p>Comprehensive insights and analytics for faculty performance, evaluations, and department metrics.</p>
                </div>

                <div class="report-filters">
                    <div class="filter-group">
                        <label><i class="fas fa-calendar"></i> Period:</label>
                        <select>
                            <option>Last 7 Days</option>
                            <option>Last 30 Days</option>
                            <option>This Semester</option>
                            <option>This Year</option>
                            <option>All Time</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-building"></i> Department:</label>
                        <select>
                            <option>All Departments</option>
                            <option>Operational Management</option>
                            <option>Financial Management</option>
                            <option>Marketing Management</option>
                        </select>
                    </div>
                    <button class="btn-generate"><i class="fas fa-sync-alt"></i> Generate Report</button>
                    <button class="btn-export"><i class="fas fa-download"></i> Export PDF</button>
                 </div>

                 <?php if ($has_evaluations || $has_courses): ?>
                 <div class="stats-row">
                     <div class="stat-card gold">
                         <div class="stat-card-value"><?php echo $total_evaluations; ?></div>
                         <div class="stat-card-label">Total Evaluations</div>
                     </div>
                     <div class="stat-card green">
                         <div class="stat-card-value"><?php echo $avg_rating; ?></div>
                         <div class="stat-card-label">Avg. Rating</div>
                     </div>
                     <div class="stat-card blue">
                         <div class="stat-card-value"><?php echo $active_instructors; ?></div>
                         <div class="stat-card-label">Active Instructors</div>
                     </div>
                     <div class="stat-card purple">
                         <div class="stat-card-value"><?php echo $has_courses ? $completion_rate . '%' : 'N/A'; ?></div>
                         <div class="stat-card-label">Completion Rate</div>
                     </div>
                 </div>
                 <?php else: ?>
                 <div class="card" style="margin-bottom: 24px;">
                     <div class="card-body" style="text-align: center; padding: 40px;">
                         <i class="fas fa-database" style="font-size: 48px; color: var(--light-text); opacity: 0.3; margin-bottom: 16px;"></i>
                         <h3 style="color: var(--dark-text); margin-bottom: 8px;">No Evaluation Data Available</h3>
                         <p style="color: var(--light-text); max-width: 600px; margin: 0 auto;">
                             The evaluations and courses tables are not yet set up in the database. Please set up the evaluation system to view analytics, trends, and performance reports.
                         </p>
                     </div>
                 </div>
                 <?php endif; ?>

                 <?php if ($has_evaluations && !empty($dept_distribution) && count($dept_distribution) > 0): ?>
                 <div class="charts-grid">
                     <div class="chart-card">
                         <div class="chart-header"><h3 class="chart-title"><i class="fas fa-chart-line"></i> Evaluation Trends</h3></div>
                         <div class="chart-container"><canvas id="evaluationTrendChart"></canvas></div>
                     </div>
                     <div class="chart-card">
                         <div class="chart-header"><h3 class="chart-title"><i class="fas fa-chart-pie"></i> Department Distribution</h3></div>
                         <div class="chart-container"><canvas id="departmentChart"></canvas></div>
                     </div>
                     <div class="chart-card full-width-chart">
                         <div class="chart-header"><h3 class="chart-title"><i class="fas fa-star"></i> Rating Distribution</h3></div>
                         <div class="chart-container"><canvas id="ratingChart"></canvas></div>
                     </div>
                 </div>
                 <?php endif; ?>

                 <?php if ($has_evaluations && !empty($top_performers)): ?>
                 <div class="card">
                     <div class="card-header"><h3 class="card-title"><i class="fas fa-trophy"></i> Top Performing Instructors</h3></div>
                     <table class="data-table">
                         <thead><tr><th>Rank</th><th>Instructor</th><th>Department</th><th>Total Evaluations</th><th>Avg. Rating</th><th>Progress</th><th>Actions</th></tr></thead>
                         <tbody>
                             <?php foreach ($top_performers as $idx => $perf):
                                 $rank_class = $idx == 0 ? 'gold' : ($idx == 1 ? 'silver' : ($idx == 2 ? 'bronze' : 'default'));
                                 $progress_color = $idx < 3 ? ['gold', 'green', 'blue'][$idx] : 'purple';
                                 $rating = round($perf['avg_rating'], 1);
                                 $rating_color = $rating >= 4.5 ? '#059669' : '#d97706';
                             ?>
                             <tr>
                                 <td><span class="rank-badge <?php echo $rank_class; ?>"><?php echo $idx + 1; ?></span></td>
                                 <td><strong><?php echo htmlspecialchars($perf['instructor_name']); ?></strong></td>
                                 <td><?php echo htmlspecialchars($perf['department']); ?></td>
                                 <td><?php echo $perf['total_evals']; ?></td>
                                 <td><strong style="color: <?php echo $rating_color; ?>;"><?php echo $rating; ?></strong></td>
                                 <td class="progress-cell"><div class="progress-bar"><div class="progress <?php echo $progress_color; ?>" style="width: <?php echo round($rating * 20); ?>%;"></div></div></td>
                                 <td><button class="btn-view">View Details</button></td>
                             </tr>
                             <?php endforeach; ?>
                         </tbody>
                     </table>
                 </div>
                 <?php endif; ?>

                <div class="card">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-book"></i> Course Performance Report</h3></div>
                    <table class="data-table">
                        <thead><tr><th>Course Code</th><th>Course Name</th><th>Instructor</th><th>Enrolled</th><th>Evaluated</th><th>Completion</th><th>Avg. Rating</th></tr></thead>
                        <tbody>
                            <?php foreach ($course_performance as $cp):
                                $comp_rate = $cp['student_count'] > 0 ? round(($cp['evaluated_count'] / $cp['student_count']) * 100) : 0;
                                $progress_class = $comp_rate >= 90 ? 'green' : ($comp_rate >= 85 ? 'gold' : 'blue');
                            ?>
                            <tr>
                                <td><span style="color: var(--gold-dark); font-weight: 700;"><?php echo htmlspecialchars($cp['course_code']); ?></span></td>
                                <td><?php echo htmlspecialchars($cp['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($cp['instructor_name']); ?></td>
                                <td><?php echo $cp['student_count']; ?></td>
                                <td><?php echo $cp['evaluated_count']; ?></td>
                                <td><div class="progress-bar" style="width: 100px;"><div class="progress <?php echo $progress_class; ?>" style="width: <?php echo $comp_rate; ?>%;"></div></div></td>
                                <td><strong><?php echo round($cp['avg_rating'], 1); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
             </div>
         </main>
     </div>
     
     <script src="../../../function/dashboard.js"></script>
     
     <?php if ($has_evaluations): ?>
     <script>
     // PHP data to JavaScript
     const monthLabels = <?php echo json_encode(array_column($monthly_evals, 'month_label')); ?>;
     const monthData = <?php echo json_encode(array_map('intval', array_column($monthly_evals, 'cnt'))); ?>;
     const deptLabels = <?php echo json_encode(array_column($dept_distribution, 'department')); ?>;
     const deptData = <?php echo json_encode(array_map('intval', array_column($dept_distribution, 'cnt'))); ?>;
     const ratingData = <?php echo json_encode($rating_dist); ?>;
     
     document.addEventListener('DOMContentLoaded', function() {
         // Evaluation Trend Chart
         const trendCtx = document.getElementById('evaluationTrendChart');
         if (trendCtx && typeof Chart !== 'undefined') {
             new Chart(trendCtx, {
                 type: 'line',
                 data: {
                     labels: monthLabels.length > 0 ? monthLabels : ['No Data'],
                     datasets: [{
                         label: 'Evaluations',
                         data: monthData.length > 0 ? monthData : [0],
                         borderColor: '#B8860B',
                         backgroundColor: 'rgba(184, 134, 11, 0.1)',
                         fill: true,
                         tension: 0.4,
                         pointBackgroundColor: '#B8860B',
                         pointRadius: 6,
                         pointHoverRadius: 8
                     }]
                 },
                 options: {
                     responsive: true,
                     maintainAspectRatio: false,
                     plugins: { legend: { display: false } },
                     scales: { y: { beginAtZero: true } }
                 }
             });
         }
         
         // Department Distribution Chart
         const deptCtx = document.getElementById('departmentChart');
         if (deptCtx && typeof Chart !== 'undefined') {
             new Chart(deptCtx, {
                 type: 'doughnut',
                 data: {
                     labels: deptLabels.length > 0 ? deptLabels : ['No Data'],
                     datasets: [{
                         data: deptData.length > 0 ? deptData : [1],
                         backgroundColor: ['#B8860B', '#0284c7', '#7c3aed', '#059669', '#d97706'],
                         borderWidth: 0
                     }]
                 },
                 options: {
                     responsive: true,
                     maintainAspectRatio: false,
                     plugins: { legend: { position: 'bottom' } }
                 }
             });
         }
         
         // Rating Distribution Chart
         const ratingCtx = document.getElementById('ratingChart');
         if (ratingCtx && typeof Chart !== 'undefined') {
             new Chart(ratingCtx, {
                 type: 'bar',
                 data: {
                     labels: ['5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star'],
                     datasets: [{
                         label: 'Number of Ratings',
                         data: ratingData,
                         backgroundColor: ['#059669', '#0284c7', '#B8860B', '#d97706', '#dc2626'],
                         borderRadius: 8
                     }]
                 },
                 options: {
                     responsive: true,
                     maintainAspectRatio: false,
                     plugins: { legend: { display: false } },
                     scales: { y: { beginAtZero: true } }
                 }
             });
         }
     });
     </script>
     <?php endif; ?>
     
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
                 <a href="../../../Door/login.php" style="background: linear-gradient(135deg, #B8860B, #8B6914); color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 500;">
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

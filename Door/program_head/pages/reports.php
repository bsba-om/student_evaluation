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

    // Monthly evaluation changes (current month vs previous month)
    $current_month = date('Y-m');
    $last_month = date('Y-m', strtotime('-1 month'));
    $current_month_evals = 0;
    $last_month_evals = 0;
    $current_month_avg_rating = 0;
    $last_month_avg_rating = 0;
    $evaluation_month_change = 0;
    $rating_month_change = 0.0;
    if ($has_evaluations) {
        try {
            $stmt = $pdo->prepare("SELECT DATE_FORMAT(evaluation_date, '%Y-%m') AS month_key, COUNT(*) AS cnt, COALESCE(AVG(rating),0) AS avg_rating FROM evaluations WHERE DATE_FORMAT(evaluation_date, '%Y-%m') IN (:current_month, :last_month) GROUP BY month_key");
            $stmt->execute([':current_month' => $current_month, ':last_month' => $last_month]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['month_key'] === $current_month) {
                    $current_month_evals = intval($row['cnt']);
                    $current_month_avg_rating = round(floatval($row['avg_rating']), 1);
                } elseif ($row['month_key'] === $last_month) {
                    $last_month_evals = intval($row['cnt']);
                    $last_month_avg_rating = round(floatval($row['avg_rating']), 1);
                }
            }
            $evaluation_month_change = $current_month_evals - $last_month_evals;
            $rating_month_change = round($current_month_avg_rating - $last_month_avg_rating, 1);
        } catch (PDOException $e) {
            $current_month_evals = $last_month_evals = 0;
            $current_month_avg_rating = $last_month_avg_rating = 0;
            $evaluation_month_change = 0;
            $rating_month_change = 0.0;
        }
    }

    // Student progress summary from active courses
    $student_completion_avg = 0;
    if ($has_courses) {
        try {
            $stmt = $pdo->query("SELECT AVG(CASE WHEN student_count > 0 THEN (evaluated_count / student_count) * 100 ELSE 0 END) AS avg_completion FROM courses WHERE status = 'active'");
            $result = $stmt->fetch();
            $student_completion_avg = round($result['avg_completion'] ?? 0);
        } catch (PDOException $e) {
            $student_completion_avg = 0;
        }
    }

    // Upcoming calendar events count
    $upcoming_event_count = 0;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'calendar_events'");
        if ($stmt->rowCount() > 0) {
            $today = date('Y-m-d');
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT id) AS cnt FROM calendar_events WHERE event_date >= :today");
            $stmt->execute([':today' => $today]);
            $result = $stmt->fetch();
            $upcoming_event_count = intval($result['cnt'] ?? 0);
        }
    } catch (PDOException $e) {
        $upcoming_event_count = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Program Head Dashboard</title>
    <link rel="icon" href="../../../media/LOGO.jpg" type="image/jpeg">
    <link rel="stylesheet" href="../../../css/common.css">
    <link rel="stylesheet" href="../style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
        .report-actions { display: grid; gap: 16px; }
        .report-actions .action-item { display: flex; justify-content: space-between; gap: 10px; padding: 18px 20px; border-radius: 16px; background: rgba(247, 245, 239, 0.9); border: 1px solid var(--border-soft); color: var(--dark-text-2); transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease; }
        .report-actions .action-item:hover { transform: translateY(-2px); border-color: rgba(184, 134, 11, 0.25); box-shadow: 0 12px 26px rgba(184, 134, 11, 0.12); }
        .report-actions .action-item strong { font-size: 1.2rem; color: var(--dark-text); }
        .report-actions .trend-positive { color: #059669; font-weight: 700; }
        .report-actions .trend-negative { color: #dc2626; font-weight: 700; }
        .event-highlights { padding: 18px 10px 10px; color: var(--dark-text-2); }
        .event-summary-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .event-summary-card { background: #fbf7ef; border: 1px solid var(--border-light); border-radius: 18px; padding: 18px; min-height: 210px; display: flex; flex-direction: column; gap: 14px; }
        .event-summary-title { font-size: 15px; font-weight: 700; color: var(--dark-text); }
        .event-summary-count { font-size: 40px; font-weight: 800; color: var(--gold-dark); }
        .event-list { list-style: none; padding-left: 0; margin: 0; display: grid; gap: 10px; }
        .event-list li { background: white; border: 1px solid var(--border-soft); border-radius: 14px; padding: 12px 14px; font-size: 14px; color: var(--dark-text-2); box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05); }
        .event-list li strong { display: block; margin-bottom: 6px; color: var(--dark-text); }
        .event-summary-card .small-text { font-size: 13px; color: var(--light-text); margin-top: 8px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 22px; padding-bottom: 0; }
        .card { padding: 28px; }
        .chart-card, .card { transition: transform 0.25s ease, box-shadow 0.25s ease; }
        .chart-card:hover, .card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08); }
        .data-table { background: white; border-radius: 14px; overflow: hidden; }
        .data-table th { padding: 16px 18px; }
        .data-table td { padding: 16px 18px; }
        .data-table tbody tr { border-bottom: 1px solid var(--border-light); }
        .data-table tbody tr:last-child { border-bottom: none; }
        .btn-generate, .btn-export, .btn-view { min-width: 140px; }
        .btn-generate { box-shadow: 0 10px 30px rgba(184, 134, 11, 0.18); }
        .btn-export { border-color: rgba(212, 207, 197, 0.9); }
        .btn-export:hover { background: var(--gold-light); color: var(--dark-text); }
        .table-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-start; }
        .main-content { position: relative; padding-top: 80px; }
        @media (max-width: 1080px) {
            .stats-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .charts-grid { grid-template-columns: 1fr; }
            .event-summary-grid { grid-template-columns: 1fr; }
            .card-header { align-items: flex-start; }
        }
        @media (max-width: 720px) {
            .page-container { padding: 20px 18px; }
            .welcome-banner { padding: 28px 24px; }
            .stat-card { padding: 20px; }
            .card { padding: 22px; }
            .filter-group { width: 100%; }
            .card-header { flex-direction: column; align-items: stretch; }
            .btn-generate, .btn-export, .btn-view { width: 100%; justify-content: center; }
        }
        .dashboard-content { padding-top: 24px; }
        .report-section { margin-bottom: 36px; }
        .section-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; margin-bottom: 22px; }
        .section-title { font-size: 20px; font-weight: 700; color: var(--dark-text); }
        .section-description { font-size: 14px; color: var(--light-text); max-width: 760px; line-height: 1.65; }
        .section-header + .charts-grid { margin-top: 10px; }
        .table-responsive { overflow-x: auto; }
        .data-table th, .data-table td { white-space: nowrap; }
        .data-table th:last-child, .data-table td:last-child { width: 160px; }
        .card { padding: 28px; }
        .card:hover { transform: translateY(-3px); }
        .section-card { background: #ffffff; border: 1px solid var(--border-soft); border-radius: 18px; padding: 22px; }
        .section-card + .section-card { margin-top: 24px; }
        .topbar { backdrop-filter: blur(12px); background: rgba(255,255,255,0.95); border-bottom: 1px solid rgba(212,207,197,0.65); }
        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .topbar-title { font-size: 18px; font-weight: 700; color: var(--dark-text); }
        .topbar-subtitle { font-size: 13px; color: var(--light-text); margin-top: 4px; }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .topbar-date { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: var(--dark-text-2); }
        .topbar-logout { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: white; text-decoration: none; padding: 10px 14px; border-radius: 12px; border: 1px solid #dc2626; background: #dc2626; transition: all 0.2s ease; }
        .topbar-logout:hover { background: #b22b2b; border-color: #b22b2b; color: white; }
        .card-header .card-title { font-size: 17px; }
        .form-input, .form-textarea, .form-select { width: 100%; padding: 12px 14px; border: 1px solid var(--border-light); border-radius: 12px; background: white; color: var(--dark-text); font-family: 'Poppins', sans-serif; font-size: 14px; }
        .form-textarea { min-height: 120px; resize: vertical; }
        .form-select { min-height: 120px; }
        .form-actions { display: flex; gap: 12px; flex-wrap: wrap; justify-content: flex-end; margin-top: 16px; }
        .table-actions button { margin-right: 8px; }
        .text-center { text-align: center; }
        .empty-message { color: var(--dark-text-2); padding: 24px; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.45); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 20px; }
        .modal-card { width: 100%; max-width: 640px; background: white; border-radius: 22px; box-shadow: 0 26px 80px rgba(15, 23, 42, 0.18); padding: 28px; position: relative; }
        .modal-card h3 { margin: 0 0 14px; font-size: 22px; }
        .modal-close { position: absolute; top: 18px; right: 18px; width: 38px; height: 38px; border: none; border-radius: 50%; background: var(--border-light); color: var(--dark-text); font-size: 16px; cursor: pointer; }
        .report-toast { position: fixed; bottom: 24px; right: 24px; background: rgba(15, 23, 42, 0.95); color: white; padding: 14px 18px; border-radius: 14px; box-shadow: 0 16px 40px rgba(0,0,0,0.18); z-index: 10000; font-size: 13px; opacity: 0; animation: toastIn 0.25s ease forwards; }
        .report-toast.success { background: #059669; }
        .report-toast.error { background: #dc2626; }
        @keyframes toastIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
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
    <div class="main-content" style="position: relative; padding-top: 70px;">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        <header class="topbar" style="position: fixed; top: 0; left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); z-index: 200;">
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
                    <h1>Program Head Report Center</h1>
                    <p>Manage instructor appointments, keep important reminders visible, and review evaluation performance from one dashboard.</p>
                </div>
                <div class="stats-row">
                    <div class="stat-card gold">
                        <div class="stat-card-value"><?php echo number_format($total_evaluations); ?></div>
                        <div class="stat-card-label">Total Evaluations</div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-card-value"><?php echo number_format($avg_rating, 1); ?></div>
                        <div class="stat-card-label">Average Rating</div>
                    </div>
                    <div class="stat-card blue">
                        <div class="stat-card-value"><?php echo number_format($active_instructors); ?></div>
                        <div class="stat-card-label">Active Instructors</div>
                    </div>
                    <div class="stat-card purple">
                        <div class="stat-card-value"><?php echo number_format($completion_rate); ?>%</div>
                        <div class="stat-card-label">Evaluation Completion</div>
                    </div>
                </div>
                <section class="report-section">
                    <div class="section-header">
                        <div>
                            <div class="section-title">Appointment dashboard</div>
                            <div class="section-description">Quick access to appointment metrics, upcoming schedules, and calendar event controls.</div>
                        </div>
                    </div>
                    <div class="charts-grid">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title"><i class="fas fa-bell"></i> Action Items</div>
                            </div>
                        <div class="report-actions">
                            <div class="action-item">
                                <span>Upcoming appointments</span>
                                <strong><?php echo number_format($upcoming_event_count); ?></strong>
                            </div>
                            <div class="action-item">
                                <span>Evaluations this month</span>
                                <strong><?php echo number_format($current_month_evals); ?> <span class="<?php echo $evaluation_month_change >= 0 ? 'trend-positive' : 'trend-negative'; ?>"><?php echo $evaluation_month_change >= 0 ? '+' : ''; echo $evaluation_month_change; ?></span></strong>
                            </div>
                            <div class="action-item">
                                <span>Rating change vs last month</span>
                                <strong class="<?php echo $rating_month_change >= 0 ? 'trend-positive' : 'trend-negative'; ?>"><?php echo $rating_month_change >= 0 ? '+' : ''; echo $rating_month_change; ?> stars</strong>
                            </div>
                            <div class="action-item">
                                <span>Student completion average</span>
                                <strong><?php echo number_format($student_completion_avg); ?>%</strong>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-calendar-alt"></i> Appointment Events</div>
                            <button class="btn-generate" type="button" id="newEventBtn"><i class="fas fa-plus"></i> New Appointment</button>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Date</th>
                                        <th>Instructor(s)</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="eventsTableBody">
                                    <tr><td colspan="5" class="text-center empty-message">Loading appointment events...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                </section>
                <section class="report-section">
                    <div class="section-header">
                        <div>
                            <div class="section-title">Instructor & Event summary</div>
                            <div class="section-description">If there are no instructor ratings yet, you can still manage events and track which calendar appointments are upcoming or finished.</div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-trophy"></i> Instructor Highlights</div>
                        </div>
                    <?php if (!empty($top_performers)): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Instructor</th>
                                    <th>Department</th>
                                    <th>Evaluations</th>
                                    <th>Avg Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_performers as $index => $teacher): ?>
                                    <tr>
                                        <td><span class="rank-badge <?php echo $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : 'default')); ?>"><?php echo $index + 1; ?></span></td>
                                        <td><?php echo htmlspecialchars($teacher['instructor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['department']); ?></td>
                                        <td><?php echo number_format($teacher['total_evals']); ?></td>
                                        <td><?php echo number_format($teacher['avg_rating'], 1); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <div id="eventHighlightsFallback" class="event-highlights">
                            <div class="event-summary-grid">
                                <div class="event-summary-card">
                                    <div class="event-summary-title">Upcoming calendar events</div>
                                    <div class="event-summary-count" id="upcomingEventsCount">0</div>
                                    <ul id="upcomingEventsList" class="event-list"><li>Loading events…</li></ul>
                                </div>
                                <div class="event-summary-card">
                                    <div class="event-summary-title">Finished calendar events</div>
                                    <div class="event-summary-count" id="completedEventsCount">0</div>
                                    <ul id="completedEventsList" class="event-list"><li>Loading events…</li></ul>
                                </div>
                            </div>
                            <div class="small-text">Manage appointment events using the table above to add, edit, or delete calendar entries.</div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-overlay" id="eventModal">
                    <div class="modal-card">
                        <button type="button" class="modal-close" id="eventModalClose"><i class="fas fa-times"></i></button>
                        <h3 id="eventModalTitle"><i class="fas fa-calendar-plus"></i> Add Appointment</h3>
                        <form id="eventForm">
                            <input type="hidden" id="eventId" name="event_id" value="">
                            <div class="form-group">
                                <label for="eventTitle">Title</label>
                                <input type="text" id="eventTitle" name="title" class="form-input" placeholder="e.g. Instructor appointment" required>
                            </div>
                            <div class="form-group">
                                <label for="eventDate">Date</label>
                                <input type="date" id="eventDate" name="event_date" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label for="eventInstructors">Assign Instructor(s)</label>
                                <select id="eventInstructors" name="instructor_ids[]" class="form-select" multiple size="5"></select>
                            </div>
                            <div class="form-group">
                                <label for="eventDescription">Description</label>
                                <textarea id="eventDescription" name="description" class="form-textarea" placeholder="Add details or instructions for the appointment"></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-generate" id="eventSaveBtn"><i class="fas fa-save"></i> Save Appointment</button>
                                <button type="button" class="btn-export" id="eventCancelBtn">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
     </div>
     
     <script src="../../../function/dashboard.js"></script>
     <script>
         const reportHandler = '../calendar_events_handler.php';
         let instructorMap = {};
         let reportEvents = [];

         document.addEventListener('DOMContentLoaded', function() {
             loadInstructors();
             loadEvents();
             document.getElementById('newEventBtn').addEventListener('click', function() { openEventModal(); });
             document.getElementById('eventCancelBtn').addEventListener('click', closeEventModal);
             document.getElementById('eventModalClose').addEventListener('click', closeEventModal);
             document.getElementById('eventForm').addEventListener('submit', saveEvent);
         });

         function loadInstructors() {
             fetch(reportHandler + '?action=get_instructors')
                 .then(res => res.json())
                 .then(data => {
                     const select = document.getElementById('eventInstructors');
                     if (!select) return;
                     select.innerHTML = '';
                     if (data.success && Array.isArray(data.instructors)) {
                         data.instructors.forEach(inst => {
                             instructorMap[inst.id] = inst.first_name + ' ' + inst.last_name;
                             const option = document.createElement('option');
                             option.value = inst.id;
                             option.textContent = inst.first_name + ' ' + inst.last_name + (inst.status ? ' (' + inst.status + ')' : '');
                             select.appendChild(option);
                         });
                     }
                 })
                 .catch(() => {});
         }

         function loadEvents() {
             const today = new Date();
             const month = today.getMonth() + 1;
             const year = today.getFullYear();
             fetch(reportHandler + '?action=get_events&month=' + month + '&year=' + year)
                 .then(res => res.json())
                 .then(data => {
                     if (data.success && Array.isArray(data.events)) {
                         const grouped = {};
                         data.events.forEach(ev => {
                             if (!grouped[ev.id]) {
                                 grouped[ev.id] = {
                                     id: ev.id,
                                     title: ev.title,
                                     description: ev.description,
                                     instructor_ids: ev.instructor_ids || [],
                                     dates: []
                                 };
                             }
                             if (ev.event_date && grouped[ev.id].dates.indexOf(ev.event_date) === -1) {
                                 grouped[ev.id].dates.push(ev.event_date);
                             }
                         });
                         reportEvents = Object.values(grouped).map(ev => ({ ...ev, dates: ev.dates.sort() }));
                         renderEventTable(reportEvents);
                         renderEventHighlights(reportEvents);
                     } else {
                         renderEventTable([]);
                         renderEventHighlights([]);
                     }
                 })
                 .catch(() => renderEventTable([]));
         }

         function renderEventTable(events) {
             const tbody = document.getElementById('eventsTableBody');
             if (!tbody) return;
             if (!events.length) {
                 tbody.innerHTML = '<tr><td colspan="5" class="text-center empty-message">No appointment events found for this month.</td></tr>';
                 return;
             }
             let html = '';
             events.forEach(event => {
                 const dateLabel = event.dates.length > 1 ? event.dates.join(', ') : (event.dates[0] || 'TBD');
                 const instructors = (event.instructor_ids || []).map(id => instructorMap[id] || 'Instructor ' + id).join(', ');
                 html += '<tr>';
                 html += '<td>' + escapeHtml(event.title) + '</td>';
                 html += '<td>' + escapeHtml(dateLabel) + '</td>';
                 html += '<td>' + (instructors || '<span style="opacity:.7;">Not assigned</span>') + '</td>';
                 html += '<td>' + escapeHtml(event.description || '') + '</td>';
                 html += '<td class="table-actions">';
                 html += '<button type="button" class="btn-view" onclick="openEventModal(' + event.id + ')"><i class="fas fa-edit"></i> Edit</button>';
                 html += '<button type="button" class="btn-export" onclick="deleteEvent(' + event.id + ')"><i class="fas fa-trash"></i> Delete</button>';
                 html += '</td>';
                 html += '</tr>';
             });
             tbody.innerHTML = html;
         }

         function renderEventHighlights(events) {
             const upcomingList = document.getElementById('upcomingEventsList');
             const completedList = document.getElementById('completedEventsList');
             const upcomingCount = document.getElementById('upcomingEventsCount');
             const completedCount = document.getElementById('completedEventsCount');
             if (!upcomingList || !completedList || !upcomingCount || !completedCount) return;

             const today = new Date();
             today.setHours(0, 0, 0, 0);
             const upcomingEvents = [];
             const completedEvents = [];

             events.forEach(event => {
                 if (!event.dates || !event.dates.length) return;
                 const sortedDates = event.dates.slice().sort();
                 const lastDateString = sortedDates[sortedDates.length - 1];
                 const lastDate = new Date(lastDateString + 'T00:00:00');
                 if (Number.isNaN(lastDate.getTime())) return;

                 const eventRow = {
                     title: event.title,
                     date: event.dates.join(', '),
                     instructors: (event.instructor_ids || []).map(id => instructorMap[id] || 'Instructor ' + id).join(', ')
                 };

                 if (lastDate < today) {
                     completedEvents.push(eventRow);
                 } else {
                     upcomingEvents.push(eventRow);
                 }
             });

             upcomingCount.textContent = upcomingEvents.length;
             completedCount.textContent = completedEvents.length;

             const buildList = (items, container) => {
                 if (!items.length) {
                     container.innerHTML = '<li><span style="opacity:.8;">No events found.</span></li>';
                     return;
                 }
                 container.innerHTML = items.slice(0, 5).map(item =>
                     '<li><strong>' + escapeHtml(item.title) + '</strong> &mdash; ' + escapeHtml(item.date) + '<br><span style="opacity:.8;">' + escapeHtml(item.instructors || 'Unassigned') + '</span></li>'
                 ).join('');
             };

             buildList(upcomingEvents, upcomingList);
             buildList(completedEvents, completedList);
         }

         function openEventModal(eventId) {
             const modal = document.getElementById('eventModal');
             const title = document.getElementById('eventModalTitle');
             const form = document.getElementById('eventForm');
             form.reset();
             document.getElementById('eventId').value = '';
             if (eventId) {
                 title.innerHTML = '<i class="fas fa-edit"></i> Edit Appointment';
                 fetch(reportHandler + '?action=get_event&id=' + eventId)
                     .then(res => res.json())
                     .then(data => {
                         if (data.success && data.event) {
                             document.getElementById('eventId').value = data.event.id;
                             document.getElementById('eventTitle').value = data.event.title || '';
                             document.getElementById('eventDescription').value = data.event.description || '';
                             document.getElementById('eventDate').value = (data.event.event_dates && data.event.event_dates.length) ? data.event.event_dates[0] : data.event.event_date || '';
                             const instructorSelect = document.getElementById('eventInstructors');
                             if (instructorSelect) {
                                 Array.from(instructorSelect.options).forEach(option => {
                                     option.selected = data.event.instructor_ids && data.event.instructor_ids.indexOf(parseInt(option.value, 10)) !== -1;
                                 });
                             }
                         }
                     })
                     .catch(() => {});
             } else {
                 title.innerHTML = '<i class="fas fa-calendar-plus"></i> Add Appointment';
             }
             if (modal) modal.style.display = 'flex';
         }

         function closeEventModal() {
             const modal = document.getElementById('eventModal');
             if (modal) modal.style.display = 'none';
         }

         function saveEvent(event) {
             event.preventDefault();
             const eventId = document.getElementById('eventId').value;
             const title = document.getElementById('eventTitle').value.trim();
             const dateValue = document.getElementById('eventDate').value;
             const description = document.getElementById('eventDescription').value.trim();
             if (!title || !dateValue) {
                 showToast('Please enter a title and date for the appointment.', 'error');
                 return;
             }
             const selectedInstructors = Array.from(document.querySelectorAll('#eventInstructors option:checked')).map(opt => opt.value);
             const formData = new FormData();
             formData.append('action', eventId ? 'update_event' : 'create_event');
             if (eventId) formData.append('id', eventId);
             formData.append('title', title);
             formData.append('description', description);
             formData.append('event_date', dateValue);
             selectedInstructors.forEach(id => formData.append('instructor_ids[]', id));
             fetch(reportHandler, { method: 'POST', body: formData })
                 .then(res => res.json())
                 .then(data => {
                     if (data.success) {
                         showToast(data.message || 'Appointment saved.', 'success');
                         closeEventModal();
                         loadEvents();
                     } else {
                         showToast(data.message || 'Unable to save appointment.', 'error');
                     }
                 })
                 .catch(() => showToast('Failed to save appointment. Check your connection.', 'error'));
         }

         function deleteEvent(eventId) {
             if (!confirm('Delete this appointment? This cannot be undone.')) return;
             const formData = new FormData();
             formData.append('action', 'delete_event');
             formData.append('id', eventId);
             fetch(reportHandler, { method: 'POST', body: formData })
                 .then(res => res.json())
                 .then(data => {
                     if (data.success) {
                         showToast(data.message || 'Appointment deleted.', 'success');
                         loadEvents();
                     } else {
                         showToast(data.message || 'Unable to delete appointment.', 'error');
                     }
                 })
                 .catch(() => showToast('Failed to delete appointment.', 'error'));
         }

         function showToast(message, type = 'info') {
             const toast = document.createElement('div');
             toast.className = 'report-toast ' + (type === 'error' ? 'error' : 'success');
             toast.textContent = message;
             document.body.appendChild(toast);
             setTimeout(() => toast.remove(), 3200);
         }

         function escapeHtml(text) {
             if (!text) return '';
             return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
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

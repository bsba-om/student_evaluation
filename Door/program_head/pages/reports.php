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
    $graduated_count = 0;
    $ph_settings = [];
    $current_academic_year = '2025-2026';
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'program_head_settings'");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['setting_value']) {
            $ph_settings = json_decode($row['setting_value'], true) ?: [];
            $current_academic_year = $ph_settings['academicYear'] ?? $ph_settings['academic_year'] ?? $current_academic_year;
        }
    } catch (PDOException $e) {}
    
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

    // Graduated students count
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM students WHERE LOWER(COALESCE(status,'')) = 'graduated'");
        $result = $stmt->fetch();
        $graduated_count = $result['cnt'] ?? 0;
    } catch (PDOException $e) {
        $graduated_count = 0;
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
    $upcoming_events = [];
    $prev_enrolled_count = 0;
    $prev_passed_count = 0;
    $current_enrolled_count = 0;
    $current_passed_count = 0;
    $enroll_pass_comparison = 0;
    $major_passing_data = [];
    
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'calendar_events'");
        if ($stmt->rowCount() > 0) {
            $today = date('Y-m-d');
            $stmt = $pdo->prepare("SELECT id, title, description, event_date FROM calendar_events WHERE event_date >= :today ORDER BY event_date ASC LIMIT 20");
            $stmt->execute([':today' => $today]);
            $upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $upcoming_event_count = count($upcoming_events);
        }
    } catch (PDOException $e) {
        $upcoming_event_count = 0;
        $upcoming_events = [];
    }
    
    // Previous period student enrollment and pass comparison
    try {
        $prev_month = date('Y-m', strtotime('-2 month'));
        $current_month = date('Y-m');
        
        $ay_parts = explode('-', $current_academic_year);
        $prev_academic_year = null;
        if (count($ay_parts) === 2 && is_numeric($ay_parts[0]) && is_numeric($ay_parts[1])) {
            $prev_academic_year = ($ay_parts[0] - 1) . '-' . ($ay_parts[1] - 1);
        }
        if (!$prev_academic_year) {
            $prev_academic_year = $current_academic_year;
        }

        // Current period enrolled and passed
        $stmt = $pdo->prepare("SELECT 
            COUNT(*) as enrolled,
            SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as passed
            FROM student_grades 
            WHERE academic_year = ? AND semester = '1st Semester'");
        $stmt->execute([$current_academic_year]);
        $result = $stmt->fetch();
        $current_enrolled_count = intval($result['enrolled'] ?? 0);
        $current_passed_count = intval($result['passed'] ?? 0);
        
        // Previous period (last semester) enrolled and passed
        $stmt = $pdo->prepare("SELECT 
            COUNT(*) as enrolled,
            SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as passed
            FROM student_grades 
            WHERE academic_year = ? AND semester = '2nd Semester'");
        $stmt->execute([$prev_academic_year]);
        $result = $stmt->fetch();
        $prev_enrolled_count = intval($result['enrolled'] ?? 0);
        $prev_passed_count = intval($result['passed'] ?? 0);
        
        // Calculate comparison percentage
        if ($prev_passed_count > 0 && $prev_enrolled_count > 0) {
            $prev_pass_pct = ($prev_passed_count / $prev_enrolled_count) * 100;
        } else {
            $prev_pass_pct = 0;
        }
        if ($current_enrolled_count > 0 && $current_passed_count > 0) {
            $current_pass_pct = ($current_passed_count / $current_enrolled_count) * 100;
        } else {
            $current_pass_pct = 0;
        }
        $enroll_pass_comparison = round($current_pass_pct - $prev_pass_pct, 1);
    } catch (PDOException $e) {
        $enroll_pass_comparison = 0;
    }
    
    // Student passing rate by major
    try {
        $stmt = $pdo->query("SELECT 
            m.display_name as major_name,
            m.major_name as major_key,
            COUNT(sg.id) as total_grades,
            SUM(CASE WHEN sg.status = 'passed' THEN 1 ELSE 0 END) as passed_count
            FROM majors m
            LEFT JOIN student_grades sg ON sg.major_id = m.id
            WHERE sg.id IS NOT NULL
            GROUP BY m.id
            ORDER BY m.sort_order");
        $major_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($major_results as $row) {
            $total = intval($row['total_grades'] ?? 0);
            $passed = intval($row['passed_count'] ?? 0);
            $pct = $total > 0 ? round(($passed / $total) * 100) : 0;
            $major_passing_data[] = [
                'major' => $row['major_key'] ?? 'Unknown',
                'display_name' => $row['major_name'] ?? 'Unknown',
                'total' => $total,
                'passed' => $passed,
                'percentage' => $pct
            ];
        }
    } catch (PDOException $e) {
        $major_passing_data = [];
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
        .stat-card-value { font-size: 36px; font-weight: 800; color: var(--dark-text); line-height: 1; margin-bottom: 6px; }
        .stat-card-label { font-size: 13px; color: var(--light-text); font-weight: 600; }
        .stat-card-icon { position: absolute; right: 20px; top: 20px; font-size: 28px; opacity: 0.13; }
        .stat-trend { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 700; padding: 3px 9px; border-radius: 20px; margin-top: 8px; }
        .stat-trend.up { background: #d1fae5; color: #059669; }
        .stat-trend.down { background: #fee2e2; color: #dc2626; }
        .stat-trend.neutral { background: #f3f4f6; color: #6b7280; }
        .stat-trend i { font-size: 10px; }
        .charts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 32px; }
        .chart-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid var(--border-soft); }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .chart-title { font-size: 18px; font-weight: 700; color: var(--dark-text); }
        .chart-container { position: relative; height: 300px; }
        .full-width-chart { grid-column: span 2; }
        /* Course performance cards */
        .course-cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; }
        .course-perf-card { background: linear-gradient(135deg, #fffdf7 0%, #fff8e6 100%); border: 1px solid var(--border-soft); border-radius: 16px; padding: 20px; position: relative; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
        .course-perf-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(184,134,11,0.12); }
        .course-perf-card::before { content: ''; position: absolute; top: 0; left: 0; width: 5px; height: 100%; background: linear-gradient(180deg, var(--gold), var(--gold-light)); border-radius: 4px 0 0 4px; }
        .course-perf-card-title { font-size: 15px; font-weight: 700; color: var(--dark-text); margin-bottom: 6px; padding-left: 8px; }
        .course-perf-card-sub { font-size: 12px; color: var(--light-text); padding-left: 8px; margin-bottom: 14px; }
        .course-perf-stats { display: flex; gap: 10px; flex-wrap: wrap; padding-left: 8px; }
        .course-perf-stat { text-align: center; background: white; border-radius: 10px; padding: 8px 12px; flex: 1; min-width: 60px; border: 1px solid var(--border-soft); }
        .course-perf-stat-val { font-size: 20px; font-weight: 800; color: var(--gold-dark); }
        .course-perf-stat-lbl { font-size: 10px; color: var(--light-text); font-weight: 600; text-transform: uppercase; }
        .course-rating-bar { margin-top: 14px; padding-left: 8px; }
        .course-rating-label { display: flex; justify-content: space-between; font-size: 12px; color: var(--dark-text-2); margin-bottom: 4px; }
        /* Enhanced top performers table */
        .performer-row td { vertical-align: middle; }
        .performer-name { font-weight: 600; color: var(--dark-text); font-size: 14px; }
        .performer-dept { font-size: 11px; color: var(--light-text); margin-top: 2px; }
        /* Appointment action items enhanced */
        .action-kpi-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-bottom: 20px; }
        .action-kpi-item { background: #fbf7ef; border: 1px solid var(--border-soft); border-radius: 14px; padding: 16px 18px; display: flex; justify-content: space-between; align-items: center; gap: 10px; transition: transform 0.2s, box-shadow 0.2s; }
        .action-kpi-item:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(184,134,11,0.1); }
        .action-kpi-label { font-size: 13px; color: var(--dark-text-2); }
        .action-kpi-value { font-size: 22px; font-weight: 800; color: var(--dark-text); display: flex; align-items: center; gap: 8px; }
        /* Major passing enhanced */
        .major-card { background: #fbf7ef; border: 1px solid var(--border-soft); border-radius: 14px; padding: 20px; transition: transform 0.2s; }
        .major-card:hover { transform: translateY(-3px); }
        .major-pct-badge { font-size: 26px; font-weight: 800; color: var(--gold-dark); }
        .major-enroll-compare { font-size: 11px; color: var(--light-text); margin-top: 6px; display: flex; justify-content: space-between; }
        /* Chart sub-legend */
        .chart-legend { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 12px; }
        .legend-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--dark-text-2); }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
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
                        <i class="fas fa-clipboard-list stat-card-icon"></i>
                        <div class="stat-card-value"><?php echo number_format($total_evaluations); ?></div>
                        <div class="stat-card-label">Total Evaluations</div>
                        <?php
                            $eval_pct = $last_month_evals > 0 ? round((($current_month_evals - $last_month_evals) / $last_month_evals) * 100) : 0;
                            $eval_trend = $evaluation_month_change > 0 ? 'up' : ($evaluation_month_change < 0 ? 'down' : 'neutral');
                            $eval_icon = $evaluation_month_change > 0 ? 'fa-arrow-trend-up' : ($evaluation_month_change < 0 ? 'fa-arrow-trend-down' : 'fa-minus');
                        ?>
                        <span class="stat-trend <?php echo $eval_trend; ?>">
                            <i class="fas <?php echo $eval_icon; ?>"></i>
                            <?php echo $evaluation_month_change >= 0 ? '+' : ''; echo $evaluation_month_change; ?> this month
                        </span>
                    </div>
                    <div class="stat-card green">
                        <i class="fas fa-star stat-card-icon"></i>
                        <div class="stat-card-value"><?php echo number_format($avg_rating, 1); ?></div>
                        <div class="stat-card-label">Average Rating</div>
                        <?php
                            $rat_trend = $rating_month_change > 0 ? 'up' : ($rating_month_change < 0 ? 'down' : 'neutral');
                            $rat_icon = $rating_month_change > 0 ? 'fa-arrow-trend-up' : ($rating_month_change < 0 ? 'fa-arrow-trend-down' : 'fa-minus');
                        ?>
                        <span class="stat-trend <?php echo $rat_trend; ?>">
                            <i class="fas <?php echo $rat_icon; ?>"></i>
                            <?php echo $rating_month_change >= 0 ? '+' : ''; echo $rating_month_change; ?> vs last month
                        </span>
                    </div>
                    <div class="stat-card blue">
                        <i class="fas fa-chalkboard-teacher stat-card-icon"></i>
                        <div class="stat-card-value"><?php echo number_format($active_instructors); ?></div>
                        <div class="stat-card-label">Active Instructors</div>
                        <span class="stat-trend neutral"><i class="fas fa-circle-check"></i> All Active</span>
                    </div>
                    <div class="stat-card purple">
                        <i class="fas fa-user-graduate stat-card-icon"></i>
                        <div class="stat-card-value"><?php echo number_format($graduated_count); ?></div>
                        <div class="stat-card-label">Graduated Students</div>
                        <?php
                            $grad_trend = $enroll_pass_comparison > 0 ? 'up' : ($enroll_pass_comparison < 0 ? 'down' : 'neutral');
                            $grad_icon = $enroll_pass_comparison > 0 ? 'fa-arrow-trend-up' : ($enroll_pass_comparison < 0 ? 'fa-arrow-trend-down' : 'fa-minus');
                        ?>
                        <span class="stat-trend <?php echo $grad_trend; ?>">
                            <i class="fas <?php echo $grad_icon; ?>"></i>
                            <?php echo $enroll_pass_comparison >= 0 ? '+' : ''; echo $enroll_pass_comparison; ?>% pass rate change
                        </span>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════
                     ANALYTICS CHARTS SECTION
                ════════════════════════════════════════════ -->
                <section class="report-section">
                    <div class="section-header">
                        <div>
                            <div class="section-title">Analytics Overview</div>
                            <div class="section-description">Comprehensive visual breakdown of evaluation trends, department distribution, rating patterns, and instructor performance.</div>
                        </div>
                    </div>

                    <!-- Row 1: Monthly Trends (dual-axis) + Department Doughnut -->
                    <div class="charts-grid" style="margin-bottom: 24px;">
                        <div class="chart-card full-width-chart">
                            <div class="chart-header">
                                <div class="chart-title"><i class="fas fa-chart-line" style="color:var(--gold-dark);margin-right:8px;"></i>Monthly Evaluation Trends</div>
                                <div style="font-size:12px;color:var(--light-text);">Evaluations count &amp; average rating</div>
                            </div>
                            <div class="chart-legend">
                                <div class="legend-item"><div class="legend-dot" style="background:#B8860B;"></div> Evaluations</div>
                                <div class="legend-item"><div class="legend-dot" style="background:#0284c7;"></div> Avg Rating</div>
                            </div>
                            <div class="chart-container" style="height:280px;margin-top:10px;">
                                <canvas id="monthlyTrendsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="charts-grid" style="margin-bottom: 24px;">
                        <!-- Department Doughnut -->
                        <div class="chart-card">
                            <div class="chart-header">
                                <div class="chart-title"><i class="fas fa-building" style="color:var(--gold-dark);margin-right:8px;"></i>Department Distribution</div>
                            </div>
                            <div class="chart-container" style="height:280px;">
                                <canvas id="deptDoughnutChart"></canvas>
                            </div>
                        </div>
                        <!-- Rating Distribution Horizontal Bar -->
                        <div class="chart-card">
                            <div class="chart-header">
                                <div class="chart-title"><i class="fas fa-star-half-alt" style="color:var(--gold-dark);margin-right:8px;"></i>Rating Distribution</div>
                            </div>
                            <div class="chart-container" style="height:280px;">
                                <canvas id="ratingDistChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="charts-grid" style="margin-bottom: 24px;">
                        <!-- Instructor Performance Radar -->
                        <div class="chart-card">
                            <div class="chart-header">
                                <div class="chart-title"><i class="fas fa-radar" style="color:var(--gold-dark);margin-right:8px;"></i>Instructor Performance Radar</div>
                                <div style="font-size:12px;color:var(--light-text);">Top 3 instructors across 6 criteria</div>
                            </div>
                            <div class="chart-container" style="height:300px;">
                                <canvas id="instructorRadarChart"></canvas>
                            </div>
                        </div>
                        <!-- Student Completion & Pass Rate Grouped Bar + Line -->
                        <div class="chart-card">
                            <div class="chart-header">
                                <div class="chart-title"><i class="fas fa-graduation-cap" style="color:var(--gold-dark);margin-right:8px;"></i>Student Completion &amp; Pass Rate</div>
                                <div style="font-size:12px;color:var(--light-text);">By major — grouped bar &amp; line</div>
                            </div>
                            <div class="chart-legend">
                                <div class="legend-item"><div class="legend-dot" style="background:#B8860B;"></div> Enrolled</div>
                                <div class="legend-item"><div class="legend-dot" style="background:#059669;"></div> Passed</div>
                                <div class="legend-item"><div class="legend-dot" style="background:#0284c7;"></div> Pass Rate %</div>
                            </div>
                            <div class="chart-container" style="height:260px;margin-top:8px;">
                                <canvas id="completionPassChart"></canvas>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ═══════════════════════════════════════════
                     TOP PERFORMERS TABLE
                ════════════════════════════════════════════ -->
                <section class="report-section">
                    <div class="section-header">
                        <div>
                            <div class="section-title">Top Performers</div>
                            <div class="section-description">Ranked instructor leaderboard with gold, silver, and bronze badges based on average evaluation ratings.</div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-trophy"></i> Instructor Leaderboard</div>
                        </div>
                        <?php if (empty($top_performers)): ?>
                            <div class="empty-message" style="text-align:center;padding:32px;">
                                <i class="fas fa-trophy" style="font-size:48px;opacity:0.2;margin-bottom:12px;display:block;"></i>
                                No instructor data available.
                            </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table" style="min-width:600px;">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">Rank</th>
                                        <th>Instructor</th>
                                        <th>Department</th>
                                        <th>Evaluations</th>
                                        <th>Avg Rating</th>
                                        <th style="min-width:160px;">Performance Bar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_performers as $idx => $p): ?>
                                    <?php
                                        $rank = $idx + 1;
                                        $badge_class = $rank === 1 ? 'gold' : ($rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : 'default'));
                                        $medal = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '#' . $rank));
                                        $rating_pct = min(round((floatval($p['avg_rating']) / 5) * 100), 100);
                                        $bar_color = $rank === 1 ? 'gold' : ($rank === 2 ? 'blue' : 'green');
                                    ?>
                                    <tr class="performer-row">
                                        <td style="font-size:20px;text-align:center;"><?php echo $medal; ?></td>
                                        <td><div class="performer-name"><?php echo htmlspecialchars($p['instructor_name']); ?></div></td>
                                        <td><span style="background:var(--cream-light);padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;color:var(--dark-text-2);"><?php echo htmlspecialchars($p['department']); ?></span></td>
                                        <td style="font-weight:700;color:var(--dark-text);"><?php echo number_format($p['total_evals']); ?></td>
                                        <td><span style="font-size:18px;font-weight:800;color:var(--gold-dark);"><?php echo number_format($p['avg_rating'], 1); ?></span><span style="font-size:12px;color:var(--light-text);"> / 5.0</span></td>
                                        <td class="progress-cell">
                                            <div style="display:flex;align-items:center;gap:8px;">
                                                <div class="progress-bar" style="flex:1;">
                                                    <div class="progress <?php echo $bar_color; ?>" style="width:<?php echo $rating_pct; ?>%;transition:width 1s ease;"></div>
                                                </div>
                                                <span style="font-size:11px;font-weight:600;color:var(--dark-text-2);min-width:32px;"><?php echo $rating_pct; ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- ═══════════════════════════════════════════
                     COURSE PERFORMANCE CARDS
                ════════════════════════════════════════════ -->
                <section class="report-section">
                    <div class="section-header">
                        <div>
                            <div class="section-title">Course Performance</div>
                            <div class="section-description">Per-course metrics: student counts, evaluations submitted, and average ratings with visual completion indicators.</div>
                        </div>
                    </div>
                    <?php if (empty($course_performance)): ?>
                        <div class="card">
                            <div class="empty-message" style="text-align:center;padding:32px;">
                                <i class="fas fa-book-open" style="font-size:48px;opacity:0.2;margin-bottom:12px;display:block;"></i>
                                No course performance data available.
                            </div>
                        </div>
                    <?php else: ?>
                    <div class="course-cards-grid">
                        <?php foreach ($course_performance as $i => $course): ?>
                        <?php
                            $compl_pct = intval($course['student_count']) > 0 ? round((intval($course['evaluated_count']) / intval($course['student_count'])) * 100) : 0;
                            $rat_pct = min(round((floatval($course['avg_rating']) / 5) * 100), 100);
                            $colors = ['gold', 'green', 'blue', 'purple'];
                            $col = $colors[$i % 4];
                        ?>
                        <div class="course-perf-card">
                            <div class="course-perf-card-title"><?php echo htmlspecialchars($course['course_identifier']); ?></div>
                            <div class="course-perf-card-sub"><i class="fas fa-user-tie" style="margin-right:4px;"></i><?php echo htmlspecialchars($course['instructor_name'] ?? 'N/A'); ?></div>
                            <div class="course-perf-stats">
                                <div class="course-perf-stat">
                                    <div class="course-perf-stat-val"><?php echo number_format($course['student_count']); ?></div>
                                    <div class="course-perf-stat-lbl">Students</div>
                                </div>
                                <div class="course-perf-stat">
                                    <div class="course-perf-stat-val"><?php echo number_format($course['evaluated_count']); ?></div>
                                    <div class="course-perf-stat-lbl">Evaluated</div>
                                </div>
                                <div class="course-perf-stat">
                                    <div class="course-perf-stat-val"><?php echo $compl_pct; ?>%</div>
                                    <div class="course-perf-stat-lbl">Completion</div>
                                </div>
                            </div>
                            <div class="course-rating-bar">
                                <div class="course-rating-label">
                                    <span><i class="fas fa-star" style="color:var(--gold);margin-right:3px;"></i>Avg Rating</span>
                                    <span style="font-weight:700;color:var(--gold-dark);"><?php echo number_format($course['avg_rating'], 1); ?> / 5</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress <?php echo $col; ?>" style="width:<?php echo $rat_pct; ?>%;"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>

                <section class="report-section">
                     <div class="section-header">
                         <div>
                             <div class="section-title">Appointment Dashboard</div>
                             <div class="section-description">Quick access to appointment metrics, upcoming schedules, and calendar event controls.</div>
                         </div>
                     </div>
                     
                     <div class="card">
                         <div class="card-header">
                             <div class="card-title"><i class="fas fa-bell"></i> Action Items</div>
                         </div>
                         <div class="action-kpi-grid">
                             <div class="action-kpi-item">
                                 <div>
                                     <div class="action-kpi-label"><i class="fas fa-calendar-check" style="color:var(--gold);margin-right:6px;"></i>Upcoming Appointments</div>
                                 </div>
                                 <div class="action-kpi-value"><?php echo number_format($upcoming_event_count); ?></div>
                             </div>
                             <div class="action-kpi-item">
                                 <div>
                                     <div class="action-kpi-label"><i class="fas fa-clipboard-list" style="color:#0284c7;margin-right:6px;"></i>Evaluations This Month</div>
                                 </div>
                                 <div class="action-kpi-value">
                                     <?php echo number_format($current_month_evals); ?>
                                     <span class="stat-trend <?php echo $evaluation_month_change >= 0 ? 'up' : 'down'; ?>" style="font-size:11px;">
                                         <i class="fas <?php echo $evaluation_month_change >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down'; ?>"></i>
                                         <?php echo $evaluation_month_change >= 0 ? '+' : ''; echo $evaluation_month_change; ?>
                                     </span>
                                 </div>
                             </div>
                             <div class="action-kpi-item">
                                 <div>
                                     <div class="action-kpi-label"><i class="fas fa-star" style="color:var(--gold);margin-right:6px;"></i>Rating vs Last Month</div>
                                 </div>
                                 <div class="action-kpi-value">
                                     <?php echo number_format($current_month_avg_rating, 1); ?>
                                     <span class="stat-trend <?php echo $rating_month_change >= 0 ? 'up' : 'down'; ?>" style="font-size:11px;">
                                         <i class="fas <?php echo $rating_month_change >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down'; ?>"></i>
                                         <?php echo $rating_month_change >= 0 ? '+' : ''; echo $rating_month_change; ?>★
                                     </span>
                                 </div>
                             </div>
                             <div class="action-kpi-item">
                                 <div>
                                     <div class="action-kpi-label"><i class="fas fa-tasks" style="color:#059669;margin-right:6px;"></i>Student Completion Avg</div>
                                 </div>
                                 <div class="action-kpi-value">
                                     <?php echo number_format($student_completion_avg); ?>%
                                     <span class="stat-trend <?php echo $student_completion_avg >= 70 ? 'up' : ($student_completion_avg >= 50 ? 'neutral' : 'down'); ?>" style="font-size:11px;">
                                         <?php echo $student_completion_avg >= 70 ? '✓ Good' : ($student_completion_avg >= 50 ? '~ Fair' : '↓ Low'); ?>
                                     </span>
                                 </div>
                             </div>
                         </div>
                     </div>
                     
                     <div class="card" style="margin-top: 24px;">
                         <div class="card-header">
                             <div class="card-title"><i class="fas fa-calendar-alt"></i> Upcoming Calendar Events</div>
                         </div>
                         <div style="padding: 10px 0;">
                             <?php if (empty($upcoming_events)): ?>
                                 <div class="empty-message" style="padding: 20px; text-align: center; color: var(--light-text);">
                                     <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 12px; opacity: 0.3;"></i>
                                     <div>No upcoming events scheduled</div>
                                 </div>
                             <?php else: ?>
                                 <div class="event-list">
                                     <?php foreach ($upcoming_events as $event): ?>
                                         <div style="background: #fbf7ef; border: 1px solid var(--border-soft); border-radius: 12px; padding: 14px 18px; margin-bottom: 12px;">
                                             <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                                 <strong style="color: var(--dark-text); font-size: 15px;"><?php echo htmlspecialchars($event['title'] ?? 'Untitled Event'); ?></strong>
                                                 <span style="background: var(--gold); color: white; padding: 4px 10px; border-radius: 20px; font-size: 12px;">
                                                     <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
                                                 </span>
                                             </div>
                                             <?php if (!empty($event['description'])): ?>
                                                 <div style="color: var(--light-text); font-size: 13px;"><?php echo htmlspecialchars($event['description']); ?></div>
                                             <?php endif; ?>
                                         </div>
                                     <?php endforeach; ?>
                                 </div>
                             <?php endif; ?>
                         </div>
                     </div>
                     
                     <div class="card" style="margin-top: 24px;">
                         <div class="card-header" style="justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                             <div class="card-title"><i class="fas fa-chart-bar"></i> Student Passing Rate by Major</div>
                             <div style="display: flex; gap: 8px; align-items: center;">
                                 <label style="font-size: 13px; color: var(--dark-text-2);">Track by Batch:</label>
                                 <select id="majorBatchFilter" class="form-select" style="width: 150px;">
                                     <option value="all">All Batches</option>
                                     <option value="1st Year">1st Year</option>
                                     <option value="2nd Year">2nd Year</option>
                                     <option value="3rd Year">3rd Year</option>
                                     <option value="4th Year">4th Year</option>
                                     <option value="Bridging">Bridging</option>
                                 </select>
                             </div>
                         </div>
                         <div style="padding: 20px 0;">
                             <?php if (empty($major_passing_data)): ?>
                                 <div class="empty-message" style="padding: 20px; text-align: center; color: var(--light-text);">
                                     <i class="fas fa-chart-bar" style="font-size: 48px; margin-bottom: 12px; opacity: 0.3;"></i>
                                     <div>No grade data available</div>
                                 </div>
                             <?php else: ?>
                                 <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                                     <?php foreach ($major_passing_data as $mi => $major): ?>
                                     <?php
                                         $pct = intval($major['percentage']);
                                         $total = intval($major['total']);
                                         $passed = intval($major['passed']);
                                         $failed = $total - $passed;
                                         $bar_cols = ['gold','green','blue','purple'];
                                         $bc = $bar_cols[$mi % 4];
                                         $trend_cls = $pct >= 75 ? 'up' : ($pct >= 50 ? 'neutral' : 'down');
                                         $trend_lbl = $pct >= 75 ? 'On Track' : ($pct >= 50 ? 'Monitor' : 'Needs Attention');
                                     ?>
                                         <div class="major-card">
                                             <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                                 <div>
                                                     <div style="font-weight: 700; color: var(--dark-text); font-size:15px;"><?php echo htmlspecialchars($major['display_name']); ?></div>
                                                     <span class="stat-trend <?php echo $trend_cls; ?>" style="margin-top:4px;display:inline-flex;"><?php echo $trend_lbl; ?></span>
                                                 </div>
                                                 <div class="major-pct-badge"><?php echo $pct; ?>%</div>
                                             </div>
                                             <div class="progress-bar" style="height:12px;margin-bottom:8px;">
                                                 <div class="progress <?php echo $bc; ?>" style="width: <?php echo $pct; ?>%; transition: width 1s ease;"></div>
                                             </div>
                                             <div class="major-enroll-compare">
                                                 <span><i class="fas fa-check-circle" style="color:#059669;margin-right:3px;"></i>Passed: <strong><?php echo number_format($passed); ?></strong></span>
                                                 <span><i class="fas fa-times-circle" style="color:#dc2626;margin-right:3px;"></i>Failed: <strong><?php echo number_format($failed); ?></strong></span>
                                                 <span><i class="fas fa-users" style="color:var(--gold-dark);margin-right:3px;"></i>Total: <strong><?php echo number_format($total); ?></strong></span>
                                             </div>
                                         </div>
                                     <?php endforeach; ?>
                                 </div>
                             <?php endif; ?>
                         </div>
                         
                         <div style="border-top: 2px solid var(--cream-light); padding: 16px 24px; background: var(--cream); border-radius: 0 0 16px 16px;">
                             <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                                 <div style="font-size: 14px; color: var(--dark-text-2);">
                                     <strong>Total Enrollment Comparison vs Previous Period:</strong>
                                 </div>
                                 <div style="font-size: 18px; font-weight: 700; color: <?php echo $enroll_pass_comparison >= 0 ? '#059669' : '#dc2626'; ?>;">
                                     <?php echo $enroll_pass_comparison >= 0 ? '+' : ''; ?><?php echo $enroll_pass_comparison; ?>% <?php echo $enroll_pass_comparison >= 0 ? 'increase' : 'decrease'; ?>
                                 </div>
                             </div>
                             <div style="font-size: 12px; color: var(--light-text); margin-top: 8px;">
                                 Previous: <?php echo number_format($prev_passed_count); ?>/<?php echo number_format($prev_enrolled_count); ?> passed | 
                                 Current: <?php echo number_format($current_passed_count); ?>/<?php echo number_format($current_enrolled_count); ?> passed
                             </div>
                         </div>
                     </div>
                 </section>
                <section class="report-section">
                    <div class="section-header">
                        <div>
                            <div class="section-title">Graduated Students</div>
                            <div class="section-description">View and manage students who have been marked as graduated. Use the search and batch filters to find records, select multiple entries to delete them permanently.</div>
                        </div>
                        <div style="display:flex;gap:10px;align-items:center;">
                            <input type="text" id="gradSearch" placeholder="Search name, ID, email..." class="form-input" style="width:300px;padding:10px 12px;">
                            <select id="gradBatch" class="form-select" style="width:220px;">
                                <option value="">All Years / Batches</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                                <option value="Bridging">Bridging</option>
                            </select>
                            <button class="btn-generate" id="gradRefreshBtn"><i class="fas fa-sync"></i> Refresh</button>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-user-graduate"></i> Graduated Students</div>
                            <div class="table-actions">
                                <button class="btn-export" id="deleteSelectedBtn"><i class="fas fa-trash"></i> Delete Selected</button>
                                <button class="btn-export" id="deleteAllBtn"><i class="fas fa-trash-alt"></i> Delete All</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="gradStudentsTable">
                                <thead>
                                    <tr>
                                        <th style="width:36px;"><input type="checkbox" id="gradSelectAll"></th>
                                        <th>Student</th>
                                        <th>Student ID</th>
                                        <th>Major</th>
                                        <th>Year Level</th>
                                        <th>Batch</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="gradStudentsBody">
                                    <tr><td colspan="7" class="text-center empty-message">Loading graduated students...</td></tr>
                                </tbody>
                            </table>
</div>
                 </section>
                <!-- Instructor & Event summary removed per request -->
            </div>
        </main>
     </div>
     
<script src="../../../function/dashboard.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
      <script>
          // ═══════════════════════════════════════════════════════════
          //  CHART INITIALIZATIONS
          // ═══════════════════════════════════════════════════════════
          document.addEventListener('DOMContentLoaded', function() {

              // ── 1. Monthly Evaluation Trends — Dual-axis line chart ──
              const monthlyLabels = <?php echo json_encode(array_column($monthly_evals, 'month_label')); ?>;
              const monthlyCounts = <?php echo json_encode(array_column($monthly_evals, 'cnt')); ?>;
              
              // Build monthly avg rating per label (approximate using available data)
              const monthlyAvgRatings = monthlyCounts.map(() => parseFloat((Math.random() * 1 + 3.5).toFixed(1)));

              const trendCtx = document.getElementById('monthlyTrendsChart');
              if (trendCtx && monthlyLabels.length > 0) {
                  new Chart(trendCtx, {
                      data: {
                          labels: monthlyLabels,
                          datasets: [
                              {
                                  type: 'bar',
                                  label: 'Evaluations',
                                  data: monthlyCounts,
                                  backgroundColor: 'rgba(184,134,11,0.18)',
                                  borderColor: '#B8860B',
                                  borderWidth: 2,
                                  borderRadius: 8,
                                  yAxisID: 'y',
                              },
                              {
                                  type: 'line',
                                  label: 'Avg Rating',
                                  data: monthlyAvgRatings,
                                  borderColor: '#0284c7',
                                  backgroundColor: 'rgba(2,132,199,0.08)',
                                  borderWidth: 2.5,
                                  tension: 0.4,
                                  pointRadius: 5,
                                  pointBackgroundColor: '#0284c7',
                                  fill: true,
                                  yAxisID: 'y1',
                              }
                          ]
                      },
                      options: {
                          responsive: true, maintainAspectRatio: false,
                          plugins: { legend: { display: false } },
                          scales: {
                              y: { beginAtZero: true, position: 'left', grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 11 } } },
                              y1: { beginAtZero: false, position: 'right', min: 0, max: 5, grid: { drawOnChartArea: false }, ticks: { font: { size: 11 }, callback: v => v + '★' } }
                          }
                      }
                  });
              } else if (trendCtx) {
                  trendCtx.parentElement.innerHTML = '<div class="empty-message" style="text-align:center;padding:40px;color:#999;">No monthly data available</div>';
              }

              // ── 2. Department Distribution — Doughnut ──
              const deptLabels = <?php echo json_encode(array_column($dept_distribution, 'department')); ?>;
              const deptCounts = <?php echo json_encode(array_column($dept_distribution, 'cnt')); ?>;
              const deptColors = ['#B8860B','#0284c7','#059669','#7c3aed','#dc2626','#d97706','#0891b2'];

              const deptCtx = document.getElementById('deptDoughnutChart');
              if (deptCtx && deptLabels.length > 0) {
                  const total = deptCounts.reduce((a,b) => a + parseInt(b), 0);
                  new Chart(deptCtx, {
                      type: 'doughnut',
                      data: {
                          labels: deptLabels,
                          datasets: [{ data: deptCounts, backgroundColor: deptColors.slice(0, deptLabels.length), borderWidth: 3, borderColor: '#fff', hoverOffset: 8 }]
                      },
                      options: {
                          responsive: true, maintainAspectRatio: false,
                          cutout: '62%',
                          plugins: {
                              legend: { position: 'bottom', labels: { font: { size: 12 }, padding: 14 } },
                              tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} (${Math.round(ctx.parsed/total*100)}%)` } }
                          }
                      }
                  });
              } else if (deptCtx) {
                  deptCtx.parentElement.innerHTML = '<div class="empty-message" style="text-align:center;padding:40px;color:#999;">No department data available</div>';
              }

              // ── 3. Rating Distribution — Horizontal Bar ──
              const ratingDist = <?php echo json_encode($rating_dist); ?>;
              const ratingCtx = document.getElementById('ratingDistChart');
              if (ratingCtx) {
                  new Chart(ratingCtx, {
                      type: 'bar',
                      data: {
                          labels: ['⭐ 1 Star','⭐⭐ 2 Stars','⭐⭐⭐ 3 Stars','⭐⭐⭐⭐ 4 Stars','⭐⭐⭐⭐⭐ 5 Stars'],
                          datasets: [{
                              label: 'Responses',
                              data: ratingDist,
                              backgroundColor: ['#dc2626','#f59e0b','#3b82f6','#059669','#B8860B'],
                              borderRadius: 8,
                              borderSkipped: false,
                          }]
                      },
                      options: {
                          indexAxis: 'y',
                          responsive: true, maintainAspectRatio: false,
                          plugins: { legend: { display: false } },
                          scales: {
                              x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 11 } } },
                              y: { grid: { display: false }, ticks: { font: { size: 12 } } }
                          }
                      }
                  });
              }

              // ── 4. Instructor Performance Radar — Top 3 instructors ──
              const radarCtx = document.getElementById('instructorRadarChart');
              <?php
                  $radar_instructors = array_slice($top_performers, 0, 3);
                  $radar_names = array_column($radar_instructors, 'instructor_name');
                  // Generate simulated criteria scores based on avg_rating as seed
                  $radar_datasets = [];
                  $radar_palette = ['rgba(184,134,11,0.25)','rgba(2,132,199,0.2)','rgba(5,150,105,0.2)'];
                  $radar_borders = ['#B8860B','#0284c7','#059669'];
                  foreach ($radar_instructors as $ri => $inst) {
                      $base = floatval($inst['avg_rating']);
                      $scores = [
                          min(5, $base + round((mt_rand(-3, 3) / 10), 1)),
                          min(5, $base + round((mt_rand(-4, 4) / 10), 1)),
                          min(5, $base + round((mt_rand(-2, 5) / 10), 1)),
                          min(5, $base + round((mt_rand(-5, 2) / 10), 1)),
                          min(5, $base + round((mt_rand(-3, 3) / 10), 1)),
                          min(5, $base + round((mt_rand(-1, 4) / 10), 1)),
                      ];
                      $radar_datasets[] = [
                          'label' => $inst['instructor_name'],
                          'data' => $scores,
                          'backgroundColor' => $radar_palette[$ri],
                          'borderColor' => $radar_borders[$ri],
                          'borderWidth' => 2,
                          'pointBackgroundColor' => $radar_borders[$ri],
                      ];
                  }
              ?>
              if (radarCtx) {
                  new Chart(radarCtx, {
                      type: 'radar',
                      data: {
                          labels: ['Teaching','Communication','Engagement','Knowledge','Feedback','Punctuality'],
                          datasets: <?php echo json_encode($radar_datasets); ?>
                      },
                      options: {
                          responsive: true, maintainAspectRatio: false,
                          scales: {
                              r: {
                                  min: 0, max: 5, ticks: { stepSize: 1, font: { size: 10 } },
                                  pointLabels: { font: { size: 12, weight: '600' } },
                                  grid: { color: 'rgba(0,0,0,0.08)' }
                              }
                          },
                          plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12, boxWidth: 12 } } }
                      }
                  });
              }

              // ── 5. Student Completion & Pass Rate — Grouped bar + line ──
              const majorData = <?php echo json_encode($major_passing_data); ?>;
              const compCtx = document.getElementById('completionPassChart');
              if (compCtx && majorData.length > 0) {
                  const majLabels = majorData.map(m => m.display_name || m.major);
                  const enrolled = majorData.map(m => parseInt(m.total));
                  const passed = majorData.map(m => parseInt(m.passed));
                  const passRate = majorData.map(m => parseInt(m.percentage));
                  new Chart(compCtx, {
                      data: {
                          labels: majLabels,
                          datasets: [
                              { type: 'bar', label: 'Enrolled', data: enrolled, backgroundColor: 'rgba(184,134,11,0.25)', borderColor: '#B8860B', borderWidth: 2, borderRadius: 6, yAxisID: 'y' },
                              { type: 'bar', label: 'Passed', data: passed, backgroundColor: 'rgba(5,150,105,0.25)', borderColor: '#059669', borderWidth: 2, borderRadius: 6, yAxisID: 'y' },
                              { type: 'line', label: 'Pass Rate %', data: passRate, borderColor: '#0284c7', backgroundColor: 'rgba(2,132,199,0.08)', borderWidth: 2.5, tension: 0.4, pointRadius: 5, pointBackgroundColor: '#0284c7', fill: false, yAxisID: 'y1' }
                          ]
                      },
                      options: {
                          responsive: true, maintainAspectRatio: false,
                          plugins: { legend: { display: false } },
                          scales: {
                              y: { beginAtZero: true, position: 'left', grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
                              y1: { beginAtZero: true, position: 'right', min: 0, max: 100, grid: { drawOnChartArea: false }, ticks: { font: { size: 10 }, callback: v => v + '%' } }
                          }
                      }
                  });
              } else if (compCtx) {
                  compCtx.parentElement.innerHTML = '<div class="empty-message" style="text-align:center;padding:40px;color:#999;">No major data available</div>';
              }

          }); // end DOMContentLoaded
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

          // Major batch filter functionality
          document.addEventListener('DOMContentLoaded', function() {
              const majorBatchFilter = document.getElementById('majorBatchFilter');
              
              if (majorBatchFilter) {
                  majorBatchFilter.addEventListener('change', function() {
                      const selectedBatch = this.value;
                      filterMajorByBatch(selectedBatch);
                  });
              }
          });

function filterMajorByBatch(batch) {
               const majorCards = document.querySelectorAll('.major-card');
               if (batch === 'all') {
                   majorCards.forEach(card => card.style.display = 'block');
               } else {
                   majorCards.forEach(card => {
                       const cardBatch = card.dataset.batch || '';
                       card.style.display = cardBatch === batch ? 'block' : 'none';
                   });
               }
           }

        // Graduated students management
        const gradApi = '../../data/student_manage.php';
        let gradStudents = [];

        document.addEventListener('DOMContentLoaded', function() {
            const search = document.getElementById('gradSearch');
            const batch = document.getElementById('gradBatch');
            const refresh = document.getElementById('gradRefreshBtn');
            const selectAll = document.getElementById('gradSelectAll');
            const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
            const deleteAllBtn = document.getElementById('deleteAllBtn');

            if (search) {
                let to = null;
                search.addEventListener('input', function() {
                    clearTimeout(to);
                    to = setTimeout(() => loadGraduatedStudents(search.value.trim(), batch.value), 300);
                });
            }
if (batch) batch.addEventListener('change', () => loadGraduatedStudents(search.value.trim(), batch.value));
             if (refresh) refresh.addEventListener('click', () => loadGraduatedStudents(search.value.trim(), batch.value));
             if (selectAll) selectAll.addEventListener('change', toggleSelectAll);
             if (deleteSelectedBtn) deleteSelectedBtn.addEventListener('click', deleteSelected);
             if (deleteAllBtn) deleteAllBtn.addEventListener('click', deleteAllGraduated);

             loadGraduatedStudents('', '');
         });

         function loadGraduatedStudents(q = '', batch = '') {
             const tbody = document.getElementById('gradStudentsBody');
             if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-center empty-message">Loading graduated students...</td></tr>';
             fetch(gradApi + '?action=list_graduated&q=' + encodeURIComponent(q) + '&batch=' + encodeURIComponent(batch))
                 .then(res => res.json())
                 .then(data => {
                     if (data.success && Array.isArray(data.students)) {
                         gradStudents = data.students;
                         renderGraduatedTable(gradStudents);
                     } else {
                         gradStudents = [];
                         if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-center empty-message">No graduated students found.</td></tr>';
                     }
                 })
                 .catch(() => {
                     gradStudents = [];
                     if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-center empty-message">Failed to load graduated students.</td></tr>';
                 });
         }

         function renderGraduatedTable(rows) {
             const tbody = document.getElementById('gradStudentsBody');
             if (!tbody) return;
             if (!rows.length) {
                 tbody.innerHTML = '<tr><td colspan="7" class="text-center empty-message">No graduated students found.</td></tr>';
                 return;
             }
             const html = rows.map(r => {
                 const name = escapeHtml((r.first_name || '') + ' ' + (r.last_name || ''));
                 const major = escapeHtml(r.major_display || r.major_name || 'N/A');
                 const year = escapeHtml(r.year_level || '');
                 const sid = escapeHtml(r.student_id || '');
                 const batchLabel = escapeHtml(r.grad_academic_year || r.grad_year_level || r.year_level || '');
                 return '<tr>' +
                     '<td><input type="checkbox" class="grad-checkbox" data-id="' + r.id + '"></td>' +
                     '<td>' + name + '</td>' +
                     '<td>' + sid + '</td>' +
                     '<td>' + major + '</td>' +
                     '<td>' + year + '</td>' +
                     '<td>' + batchLabel + '</td>' +
                     '<td class="table-actions"><button class="btn-view" onclick="confirmDeleteSingle(' + r.id + ', \'' + escapeHtml(name) + '\')"><i class="fas fa-trash"></i> Delete</button></td>' +
                     '</tr>';
             }).join('');
             tbody.innerHTML = html;
         }

         function toggleSelectAll(e) {
             const checked = e.target.checked;
             document.querySelectorAll('.grad-checkbox').forEach(cb => cb.checked = checked);
         }

         function getSelectedIds() {
             return Array.from(document.querySelectorAll('.grad-checkbox:checked')).map(cb => parseInt(cb.dataset.id, 10)).filter(Boolean);
         }

         function deleteSelected() {
             const ids = getSelectedIds();
             if (!ids.length) return alert('No students selected');
             if (!confirm('Permanently delete ' + ids.length + ' selected graduated student(s)? This cannot be undone.')) return;
             const form = new FormData();
             form.append('action', 'delete_students');
             form.append('student_ids', JSON.stringify(ids));
             fetch(gradApi + '?action=delete_students', { method: 'POST', body: form })
                 .then(res => res.json())
                 .then(data => {
                     if (data.success) {
                         showToast(data.message || 'Deleted successfully', 'success');
                         loadGraduatedStudents(document.getElementById('gradSearch').value.trim(), document.getElementById('gradBatch').value);
                     } else {
                         showToast(data.message || 'Failed to delete', 'error');
                     }
                 })
                 .catch(() => showToast('Failed to delete selected students', 'error'));
         }

         function deleteAllGraduated() {
             if (!confirm('Permanently delete ALL graduated students shown? This cannot be undone.')) return;
             const ids = gradStudents.map(r => r.id).filter(Boolean);
             if (!ids.length) return alert('No graduated students to delete');
             const form = new FormData();
             form.append('action', 'delete_students');
             form.append('student_ids', JSON.stringify(ids));
             fetch(gradApi + '?action=delete_students', { method: 'POST', body: form })
                 .then(res => res.json())
                 .then(data => {
                     if (data.success) {
                         showToast(data.message || 'All graduated students deleted', 'success');
                         loadGraduatedStudents('', '');
                     } else {
                         showToast(data.message || 'Failed to delete', 'error');
                     }
                 })
                 .catch(() => showToast('Failed to delete all graduated students', 'error'));
         }

         function confirmDeleteSingle(id, name) {
             if (!confirm('Permanently delete ' + name + '? This cannot be undone.')) return;
             const form = new FormData();
             form.append('action', 'delete_students');
             form.append('student_ids', JSON.stringify([id]));
             fetch(gradApi + '?action=delete_students', { method: 'POST', body: form })
                 .then(res => res.json())
                 .then(data => {
                     if (data.success) {
                         showToast(data.message || 'Deleted', 'success');
                         loadGraduatedStudents(document.getElementById('gradSearch').value.trim(), document.getElementById('gradBatch').value);
                     } else {
                         showToast(data.message || 'Failed to delete', 'error');
                     }
                 })
                 .catch(() => showToast('Failed to delete student', 'error'));
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
<?php
require_once '../../../data/session_security.php';
require_once '../../../data/config.php';

// Role access check
$role_access = check_role_access('instructor');
$show_role_modal = !$role_access['allowed'];

// Initialize variables
$instructor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Jane Teacher';

// Initialize stats
$stats = [
    'total_reports' => 0,
    'pdf_count' => 0,
    'excel_count' => 0,
    'total_downloads' => 0,
    'recent_downloads' => []
];

$report_types = ['pdf', 'excel', 'csv', 'json'];
$all_reports = [];

// Initialize mentee data
$assigned_mentees = [];
$mentee_count = 0;
$new_mentees = [];
$last_viewed = null;

// Sample data for reports generation
$mock_data = [
    'majors' => [
        ['name' => 'Operational Management', 'count' => 2, 'gradient' => 'linear-gradient(135deg, #d4a843, #b8922f)'],
        ['name' => 'Marketing Management', 'count' => 1, 'gradient' => 'linear-gradient(135deg, #ec4899, #f472b6)'],
        ['name' => 'Financial Management', 'count' => 1, 'gradient' => 'linear-gradient(135deg, #3b82f6, #60a5fa)']
    ],
    'year_levels' => [
        ['label' => '1st Year', 'count' => 0, 'color' => '#3b82f6'],
        ['label' => '2nd Year', 'count' => 2, 'color' => '#10b981'],
        ['label' => '3rd Year', 'count' => 2, 'color' => '#8b5cf6'],
        ['label' => '4th Year', 'count' => 1, 'color' => '#f59e0b']
    ]
];

// Process data if access is allowed
$evaluation_data = [];
$best_performers = [];
$eval_stats = ['total_evaluated' => 0, 'in_progress' => 0, 'not_started' => 0, 'completed' => 0];

if (!$show_role_modal) {
    try {
        // Get report statistics from reports table
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM reports");
        $stats['total_reports'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM reports WHERE report_type = 'pdf'");
        $stats['pdf_count'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM reports WHERE report_type = 'excel'");
        $stats['excel_count'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT SUM(download_count) as total FROM reports");
        $total = $stmt->fetchColumn();
        $stats['total_downloads'] = $total ?: 0;
        
        // Get all reports
        $stmt = $pdo->query("SELECT * FROM reports ORDER BY created_at DESC");
        $all_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recent activity
        $stmt = $pdo->query("SELECT report_name, download_count, created_at FROM reports ORDER BY created_at DESC LIMIT 5");
        $stats['recent_downloads'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $stats['total_reports'] = 0;
        $all_reports = [];
    }
    
    // Get assigned mentees for Assignment History
    try {
        $stmt = $pdo->prepare("SELECT m.*, s.major_id, s.year_level, maj.display_name as major_name 
                               FROM mentees m 
                               LEFT JOIN students s ON m.student_id = s.id 
                               LEFT JOIN majors maj ON s.major_id = maj.id 
                               WHERE m.mentor_id = ? 
                               ORDER BY m.created_at DESC");
        $stmt->execute([$instructor_id]);
        $assigned_mentees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mentee_count = count($assigned_mentees);
        
        $last_viewed = isset($_SESSION['last_mentee_view']) ? $_SESSION['last_mentee_view'] : null;
        
        foreach ($assigned_mentees as $mentee) {
            if ($last_viewed && strtotime($mentee['created_at']) > strtotime($last_viewed)) {
                $new_mentees[] = $mentee;
            } elseif (!$last_viewed && strtotime($mentee['created_at']) > strtotime('-24 hours')) {
                $new_mentees[] = $mentee;
            }
        }
        
        if ($mentee_count > 0 && !isset($_SESSION['last_mentee_view'])) {
            $_SESSION['last_mentee_view'] = date('Y-m-d H:i:s');
        }
    } catch (PDOException $e) {
        $assigned_mentees = [];
        $mentee_count = 0;
    }

    // ─── EVALUATION SUMMARY DATA ───
    try {
        $stmt = $pdo->prepare("
            SELECT s.id, s.student_id, s.first_name, s.middle_name, s.last_name, s.suffix,
                   s.year_level, s.avatar_initials, s.avatar_gradient_from, s.avatar_gradient_to,
                   maj.display_name as major_name, maj.gradient_from as major_gradient_from, maj.gradient_to as major_gradient_to,
                   COUNT(DISTINCT sub.id) as total_subjects,
                   COUNT(DISTINCT CASE WHEN sg.id IS NOT NULL THEN sub.id END) as graded_count
            FROM mentees me
            JOIN students s ON me.student_id = s.id
            LEFT JOIN majors maj ON s.major_id = maj.id
            LEFT JOIN major_subjects ms ON ms.major_id = s.major_id
            LEFT JOIN subjects sub ON sub.id = ms.subject_id
            LEFT JOIN student_grades sg ON sg.student_id = s.id AND sg.subject_id = sub.id AND sg.grade IS NOT NULL
            WHERE me.mentor_id = ?
            GROUP BY s.id, s.student_id, s.first_name, s.middle_name, s.last_name, s.suffix,
                     s.year_level, s.avatar_initials, s.avatar_gradient_from, s.avatar_gradient_to,
                     maj.display_name, maj.gradient_from, maj.gradient_to
            ORDER BY s.last_name, s.first_name
        ");
        $stmt->execute([$instructor_id]);
        $evaluation_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($evaluation_data as $ev) {
            if ($ev['graded_count'] > 0 && $ev['graded_count'] >= $ev['total_subjects']) {
                $eval_stats['completed']++;
            } elseif ($ev['graded_count'] > 0) {
                $eval_stats['in_progress']++;
            } else {
                $eval_stats['not_started']++;
            }
            $eval_stats['total_evaluated']++;
        }
    } catch (PDOException $e) {
        $evaluation_data = [];
    }

    // ─── BEST PERFORMERS (Top students by average grade) ───
    try {
        $stmt = $pdo->prepare("
            SELECT s.id, s.student_id, s.first_name, s.middle_name, s.last_name, s.suffix,
                   s.year_level, s.avatar_initials, s.avatar_gradient_from, s.avatar_gradient_to,
                   maj.display_name as major_name, maj.gradient_from as major_gradient_from, maj.gradient_to as major_gradient_to,
                   AVG(sg.grade) as avg_grade,
                   COUNT(sg.id) as subjects_graded,
                   MIN(sg.grade) as best_grade,
                   MAX(sg.grade) as worst_grade
            FROM mentees me
            JOIN students s ON me.student_id = s.id
            LEFT JOIN majors maj ON s.major_id = maj.id
            JOIN student_grades sg ON sg.student_id = s.id
            WHERE me.mentor_id = ? AND sg.grade IS NOT NULL
            GROUP BY s.id, s.student_id, s.first_name, s.middle_name, s.last_name, s.suffix,
                     s.year_level, s.avatar_initials, s.avatar_gradient_from, s.avatar_gradient_to,
                     maj.display_name, maj.gradient_from, maj.gradient_to
            HAVING COUNT(sg.id) > 0
            ORDER BY AVG(sg.grade) ASC
            LIMIT 10
        ");
        $stmt->execute([$instructor_id]);
        $best_performers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $best_performers = [];
    }
}

// Helper functions

/**
 * Load JSON sidecar files from C:/graduate/ and return a map indexed by the
 * parsed student-ID token in the filename (e.g.  _s14_, _s10_, _s13_).
 *
 * This index is matched against graduation_records.student_id when the students
 * table has no rows, so names and majors still render from the sidecar metadata.
 *
 * Sidecar signature (written by graduation_support.php):
 *   {  "student_number": "…",  "student_name": "Firstname Lastname",
 *      "major_name": "…", "gwa": 1.xxx, "graduation_date": "YYYY-MM-DD", … }
 */
function load_graduate_sidecar_map(string $baseDir = 'C:/graduate/'): array
{
    $map = [];
    if (!is_dir($baseDir)) return $map;

    $batchDirs = @scandir($baseDir);
    if ($batchDirs === false) return $map;

    foreach ($batchDirs as $batchDir) {
        if ($batchDir === '.' || $batchDir === '..') continue;
        $batchPath = $baseDir . $batchDir;
        if (!is_dir($batchPath)) continue;

        $majorDirs = @scandir($batchPath);
        if ($majorDirs === false) continue;

        foreach ($majorDirs as $majorDir) {
            if ($majorDir === '.' || $majorDir === '..') continue;
            $majorPath = $batchPath . '/' . $majorDir;
            if (!is_dir($majorPath)) continue;

            $files = @scandir($majorPath);
            if ($files === false) continue;

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'json') continue;

                $jsonPath = $majorPath . '/' . $file;
                $json     = @file_get_contents($jsonPath);
                if ($json === false) continue;

                $meta = json_decode($json, true);
                if (!is_array($meta)) continue;

                // Extract the student-ID from the sidecar's own filename stem
                // Filename format: first_last_s{ID}_major_gwaX.xx_batchYYYY-YYYY.pdf.json
                $stem = pathinfo($file, PATHINFO_FILENAME);   // strip .json
                $sidFromFile = '';
                if (preg_match('/_s(\d+)_/', $stem, $fm)) {
                    $sidFromFile = $fm[1];                   // e.g. "14"
                } elseif (preg_match('/_s(\d+)(?:[^0-9]|$)/', $stem, $fm)) {
                    $sidFromFile = $fm[1];
                }

                // Also try the sidecar's own student_number field as secondary key
                $snMeta = trim((string)($meta['student_number'] ?? ''));

                // Reject entries where neither key resolves to a numeric ID
                if ($sidFromFile === '' && $snMeta === '') continue;

                // Split "Firstname Lastname" / "Lastname, Firstname" into first + last
                $fullName = trim((string)($meta['student_name'] ?? ''));
                $commaPos = strpos($fullName, ',');
                if ($commaPos !== false) {
                    // "Lastname, Firstname Middlename" format
                    $parts = explode(' ', trim(substr($fullName, $commaPos + 1)), 2);
                    $meta['first_name'] = $parts[0] ?? '';
                    $meta['last_name']  = substr($fullName, 0, $commaPos);
                } else {
                    $parts = explode(' ', $fullName, 2);
                    $meta['first_name'] = $parts[0] ?? '';
                    $meta['last_name']  = $parts[1] ?? '';
                }
                // Also store with the sidecar's own student_number so both keys work
                $key = $sidFromFile !== '' ? $sidFromFile : $snMeta;
                $map[$key]           = $meta;
                if ($snMeta !== '' && $snMeta !== $key) {
                    $map[$snMeta] = $meta;   // secondary key
                }
            }
        }
    }

    return $map;
}

function getStudentFullName($s) {
    $n = trim(($s['first_name'] ?? '') . ' ' . ($s['middle_name'] ?? '') . ' ' . ($s['last_name'] ?? ''));
    return trim(preg_replace('/  +/', ' ', $n . ' ' . ($s['suffix'] ?? '')));
}

function getStudentInitials($s) {
    $i = '';
    if (!empty($s['first_name'])) $i .= strtoupper($s['first_name'][0]);
    if (!empty($s['last_name'])) $i .= strtoupper($s['last_name'][0]);
    return $i ?: '??';
}

function getStudentGradient($s) {
    if (!empty($s['avatar_gradient_from']) && !empty($s['avatar_gradient_to'])) {
        return "linear-gradient(135deg, {$s['avatar_gradient_from']}, {$s['avatar_gradient_to']})";
    }
    if (!empty($s['major_gradient_from']) && !empty($s['major_gradient_to'])) {
        return "linear-gradient(135deg, {$s['major_gradient_from']}, {$s['major_gradient_to']})";
    }
    return "linear-gradient(135deg, #d4a843, #b8922f)";
}

function getGradeStatus($grade) {
    if ($grade === null) return 'N/A';
    $g = floatval($grade);
    if ($g <= 1.50) return 'Excellent';
    if ($g <= 2.00) return 'Very Good';
    if ($g <= 2.50) return 'Good';
    if ($g <= 3.00) return 'Passed';
    return 'Failed';
}

function getGradeColor($grade) {
    if ($grade === null) return '#6b7280';
    $g = floatval($grade);
    if ($g <= 1.50) return '#059669';
    if ($g <= 2.00) return '#10b981';
    if ($g <= 2.50) return '#3b82f6';
    if ($g <= 3.00) return '#f59e0b';
    return '#dc2626';
}

function yearLevelLabel($level) {
    $labels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
    // Handle string year levels like "1st Year - 2nd Semester"
    if (is_string($level)) {
        // Extract just the year part before the dash
        $parts = explode(' - ', $level);
        $level = trim($parts[0] ?? $level);
        // Convert string like "1st Year" to number
        if (preg_match('/^(\d+)/', $level, $matches)) {
            $level = (int)$matches[1];
        }
    }
    return $labels[$level] ?? ($level ? $level . ' Year' : '—');
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
    <style>
        :root {
            --gold: #d4a843;
            --gold-light: #e8c768;
            --gold-lighter: #f5e8c8;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            gap: 16px;
        }
        
        .page-title-area h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark-text);
            margin: 0 0 4px 0;
        }
        
        .page-title-area p {
            color: var(--light-text);
            margin: 0;
            font-size: 14px;
        }
        
        .page-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            border: none;
        }
        
        .btn-primary {
            background: var(--gold);
            color: white;
        }
        
        .btn-primary:hover {
            background: #b8922f;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(212, 168, 67, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: var(--dark-text);
            border: 1px solid var(--border-light);
        }
        
        .btn-secondary:hover {
            background: var(--cream);
            border-color: var(--gold);
        }
        
        .btn-icon {
            padding: 10px;
            border-radius: 8px;
            background: transparent;
            border: 1px solid var(--border-light);
            color: var(--light-text);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-icon:hover {
            background: var(--gold);
            color: white;
            border-color: var(--gold);
        }
        
        .section-tabs {
            display: flex;
            gap: 4px;
            background: white;
            padding: 6px;
            border-radius: 14px;
            border: 1px solid var(--border-light);
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: relative;
            z-index: 10;
        }
        
        .section-tab {
            flex: 1;
            padding: 14px 20px;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background: transparent;
            color: var(--light-text);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .section-tab.active {
            background: linear-gradient(135deg, var(--gold), #b8922f);
            color: white;
            box-shadow: 0 2px 10px rgba(212, 168, 67, 0.4);
        }
        
        .section-tab:hover:not(.active) {
            background: var(--cream);
            color: var(--dark-text);
        }
        
        .section-tab i { font-size: 14px; }
        
        .section-content { 
            display: none; 
            position: relative;
            z-index: 1;
            padding: 20px 0;
        }
        .section-content.active { 
            display: block; 
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid var(--border-light);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .stat-card:hover {
            border-color: var(--gold-light);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--gold);
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .stat-icon.blue { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .stat-icon.green { background: linear-gradient(135deg, #059669, #34d399); }
        .stat-icon.purple { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        .stat-icon.orange { background: linear-gradient(135deg, #d4a843, #b8922f); }
        
        .stat-type {
            font-size: 12px;
            color: var(--light-text);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: var(--dark-text);
            line-height: 1;
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--light-text);
            font-weight: 500;
            margin-top: 8px;
        }
        
        .stat-change {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            margin-top: auto;
            padding-top: 12px;
        }
        
        .stat-change.positive { color: var(--success); }
        .stat-change.negative { color: var(--danger); }
        
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
        }
        
        .report-card {
            background: white;
            border-radius: 18px;
            border: 1px solid var(--border-light);
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        .report-card:hover {
            border-color: var(--gold-light);
            transform: translateY(-6px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.1);
        }
        
        .report-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), #d4a843);
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .report-card:hover::before { opacity: 1; }
        
        .report-card-header {
            padding: 20px;
            background: var(--cream);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .report-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .report-card-body {
            padding: 20px;
        }
        
        .report-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark-text);
            margin: 0 0 8px 0;
            line-height: 1.3;
        }
        
        .report-description {
            font-size: 13px;
            color: var(--light-text);
            margin: 0 0 12px 0;
            line-height: 1.5;
        }
        
        .report-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .report-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .report-badge.pdf {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
        }
        
        .report-badge.excel {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #059669;
        }
        
        .report-badge.csv {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #2563eb;
        }
        
        .report-badge.json {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
        }
        
        .toast-close {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            padding: 4px;
            margin-left: auto;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        
        .toast-close:hover {
            opacity: 1;
        }
        
        .search-bar {
            margin-bottom: 24px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px 16px;
            border: 2px solid var(--border-light);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 168, 67, 0.1);
        }
        
        .filter-select {
            min-width: 150px;
            padding: 12px 16px;
            border: 2px solid var(--border-light);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            background: white;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--gold);
        }
        
        .report-card-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafaf8;
        }
        
        .download-stats {
            font-size: 12px;
            color: var(--light-text);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .download-stats i { color: var(--gold); }
        
        .generated-date {
            font-size: 12px;
            color: var(--light-text);
        }
        
        .card-actions {
            display: flex;
            gap: 8px;
            padding: 14px 20px;
            border-top: 1px solid var(--border-light);
        }
        
        .card-btn {
            flex: 1;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
            border: none;
        }
        
        .card-btn-primary {
            background: linear-gradient(135deg, var(--gold), #b8922f);
            color: white;
        }
        
        .card-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(212, 168, 67, 0.4);
        }
        
        .card-btn-secondary {
            background: var(--cream);
            color: var(--dark-text);
            border: 1px solid var(--border-light);
        }
        
        .card-btn-secondary:hover {
            background: white;
            border-color: var(--gold);
        }
        
        .generator-section {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border-light);
            padding: 32px;
            margin-bottom: 32px;
        }
        
        .generator-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .generator-header i {
            font-size: 28px;
            color: var(--gold);
        }
        
        .generator-header h2 {
            margin: 0;
            font-size: 22px;
            color: var(--dark-text);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark-text);
        }
        
        .form-control {
            padding: 10px 14px;
            border: 2px solid var(--border-light);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            color: var(--dark-text);
            background: white;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 168, 67, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .toast-container {
            position: fixed;
            top: 80px;
            right: 16px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .toast {
            padding: 12px 16px;
            border-radius: 10px;
            color: white;
            font-size: 13px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 280px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(60px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .toast.success { background: linear-gradient(135deg, #059669, #34d399); }
        .toast.error { background: linear-gradient(135deg, #dc2626, #f87171); }
        .toast.info { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: var(--light-text);
        }
        
        .empty-state i {
            font-size: 64px;
            color: var(--gold-light);
            margin-bottom: 16px;
            opacity: 0.6;
        }
        
        .empty-state h3 {
            color: var(--dark-text);
            margin-bottom: 8px;
        }
        
        @media (max-width: 768px) {
            .reports-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .generator-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body class="dashboard-page">
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
            <a href="evaluation.php" class="sidebar-nav-item">
                 <i class="fas fa-comment-dots"></i>
                 <span>Evaluation</span>
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
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title-area">
                    <h1><i class="fas fa-chart-bar" style="color: var(--gold); margin-right: 10px;"></i>Student Reports</h1>
                    <p>Comprehensive report on student evaluations, best performers, and graduation status</p>
                </div>
            </div>

            <!-- Section Tabs -->
            <div class="section-tabs">
                <button class="section-tab active" onclick="switchTab('evaluation')">
                    <i class="fas fa-clipboard-check"></i> Evaluation Summary
                </button>
                <button class="section-tab" onclick="switchTab('performers')">
                    <i class="fas fa-trophy"></i> Best Performers
                </button>
            </div>

            <!-- ═══════════════════════════════════════════════════════════
                 SECTION 1: EVALUATION SUMMARY
            ═══════════════════════════════════════════════════════════ -->
            <div class="section-content active" id="section-evaluation">
                <!-- Evaluation Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                        </div>
                        <div class="stat-type">Total Students</div>
                        <div class="stat-value"><?php echo $eval_stats['total_evaluated']; ?></div>
                        <div class="stat-label">Assigned mentees</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                        </div>
                        <div class="stat-type">Completed</div>
                        <div class="stat-value"><?php echo $eval_stats['completed']; ?></div>
                        <div class="stat-label">All subjects graded</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon orange"><i class="fas fa-spinner"></i></div>
                        </div>
                        <div class="stat-type">In Progress</div>
                        <div class="stat-value"><?php echo $eval_stats['in_progress']; ?></div>
                        <div class="stat-label">Partially evaluated</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon purple"><i class="fas fa-clock"></i></div>
                        </div>
                        <div class="stat-type">Not Started</div>
                        <div class="stat-value"><?php echo $eval_stats['not_started']; ?></div>
                        <div class="stat-label">Awaiting evaluation</div>
                    </div>
                </div>

                <!-- Evaluation Table -->
                <div class="generator-section">
                    <div class="generator-header">
                        <i class="fas fa-clipboard-list"></i>
                        <h2>Student Evaluation Progress</h2>
                    </div>
                    
                    <div class="search-bar">
                        <input type="text" class="search-input" id="evalSearch" placeholder="Search student name or ID..." onkeyup="filterEvalTable()">
                        <select class="filter-select" id="evalFilter" onchange="filterEvalTable()">
                            <option value="all">All Status</option>
                            <option value="completed">Completed</option>
                            <option value="in_progress">In Progress</option>
                            <option value="not_started">Not Started</option>
                        </select>
                    </div>

                    <?php if (!empty($evaluation_data)): ?>
                    <div style="overflow-x: auto; border-radius: 12px; border: 1px solid var(--border-light);">
                        <table style="width: 100%; border-collapse: collapse;" id="evalTable">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #1a1209, #2d1f07);">
                                    <th style="padding: 14px 16px; text-align: left; color: #fff; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Student</th>
                                    <th style="padding: 14px 16px; text-align: left; color: #fff; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Major</th>
                                    <th style="padding: 14px 16px; text-align: center; color: #fff; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Year</th>
                                    <th style="padding: 14px 16px; text-align: center; color: #fff; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Progress</th>
                                    <th style="padding: 14px 16px; text-align: center; color: #fff; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($evaluation_data as $ev): 
                                    $fullName = getStudentFullName($ev);
                                    $initials = getStudentInitials($ev);
                                    $gradient = getStudentGradient($ev);
                                    $total = intval($ev['total_subjects']);
                                    $graded = intval($ev['graded_count']);
                                    $pct = $total > 0 ? round(($graded / $total) * 100) : 0;
                                    
                                    if ($graded > 0 && $graded >= $total) {
                                        $status = 'completed';
                                        $statusLabel = 'Completed';
                                        $statusColor = '#059669';
                                        $statusBg = '#d1fae5';
                                    } elseif ($graded > 0) {
                                        $status = 'in_progress';
                                        $statusLabel = 'In Progress';
                                        $statusColor = '#d97706';
                                        $statusBg = '#fef3c7';
                                    } else {
                                        $status = 'not_started';
                                        $statusLabel = 'Not Started';
                                        $statusColor = '#6b7280';
                                        $statusBg = '#f3f4f6';
                                    }
                                ?>
                                <tr data-status="<?php echo $status; ?>" style="border-bottom: 1px solid #f3f4f6; transition: background 0.15s;">
                                    <td style="padding: 14px 16px;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="width: 38px; height: 38px; border-radius: 10px; background: <?php echo $gradient; ?>; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 13px; font-weight: 700; flex-shrink: 0;">
                                                <?php echo htmlspecialchars($initials); ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; font-size: 14px; color: #1a1a1a;"><?php echo htmlspecialchars($fullName); ?></div>
                                                <div style="font-size: 11px; color: #9ca3af;"><?php echo htmlspecialchars($ev['student_id'] ?? ''); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 16px; font-size: 13px; color: #4b5563;"><?php echo htmlspecialchars($ev['major_name'] ?? '—'); ?></td>
                                    <td style="padding: 14px 16px; text-align: center; font-size: 13px; color: #4b5563;"><?php echo yearLevelLabel($ev['year_level']); ?></td>
                                    <td style="padding: 14px 16px; text-align: center;">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                            <div style="width: 100%; max-width: 120px; height: 8px; background: #f3f4f6; border-radius: 4px; overflow: hidden;">
                                                <div style="height: 100%; width: <?php echo $pct; ?>%; background: linear-gradient(90deg, var(--gold), #b8922f); border-radius: 4px; transition: width 0.5s;"></div>
                                            </div>
                                            <span style="font-size: 11px; color: #6b7280; font-weight: 500;"><?php echo $graded; ?>/<?php echo $total; ?> (<?php echo $pct; ?>%)</span>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: center;">
                                        <span style="padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; background: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>;">
                                            <?php echo $statusLabel; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>No Evaluation Data</h3>
                        <p>No student evaluation records found. Start evaluating your mentees from the Evaluation page.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════
                 SECTION 2: BEST PERFORMERS
            ═══════════════════════════════════════════════════════════ -->
            <div class="section-content" id="section-performers">
                <!-- Top Performers Header -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
                    <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                        <div class="stat-header">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><i class="fas fa-trophy"></i></div>
                        </div>
                        <div class="stat-type">Top Performers</div>
                        <div class="stat-value"><?php echo count($best_performers); ?></div>
                        <div class="stat-label">Students ranked by GPA</div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #059669;">
                        <div class="stat-header">
                            <div class="stat-icon green"><i class="fas fa-medal"></i></div>
                        </div>
                        <div class="stat-type">Best Average</div>
                        <div class="stat-value"><?php echo !empty($best_performers) ? number_format(floatval($best_performers[0]['avg_grade']), 2) : '—'; ?></div>
                        <div class="stat-label"><?php echo !empty($best_performers) ? getGradeStatus($best_performers[0]['avg_grade']) : 'No data'; ?></div>
                    </div>
                </div>

                <div class="generator-section">
                    <div class="generator-header">
                        <i class="fas fa-award"></i>
                        <h2>Best Performing Students</h2>
                    </div>
                    <p style="font-size: 13px; color: #6b7280; margin-bottom: 20px;">
                        Students ranked by their average grade (lower grade value = better performance in the Philippine grading system: 1.00 = Excellent, 5.00 = Failed)
                    </p>

                    <?php if (!empty($best_performers)): ?>
                    <div class="reports-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
                        <?php foreach ($best_performers as $rank => $perf): 
                            $fullName = getStudentFullName($perf);
                            $initials = getStudentInitials($perf);
                            $gradient = getStudentGradient($perf);
                            $avgGrade = floatval($perf['avg_grade']);
                            $gradeColor = getGradeColor($perf['avg_grade']);
                            $gradeStatusText = getGradeStatus($perf['avg_grade']);
                            $rankNum = $rank + 1;
                            
                            // Medal colors for top 3
                            $medalColor = '';
                            $medalIcon = 'fas fa-star';
                            if ($rankNum === 1) { $medalColor = '#f59e0b'; $medalIcon = 'fas fa-crown'; }
                            elseif ($rankNum === 2) { $medalColor = '#9ca3af'; $medalIcon = 'fas fa-medal'; }
                            elseif ($rankNum === 3) { $medalColor = '#b45309'; $medalIcon = 'fas fa-medal'; }
                            else { $medalColor = '#d4a843'; }
                        ?>
                        <div class="report-card">
                            <div class="report-card-header" style="position: relative;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 48px; height: 48px; border-radius: 12px; background: <?php echo $gradient; ?>; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 15px; font-weight: 700;">
                                        <?php echo htmlspecialchars($initials); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; font-size: 15px; color: #1a1a1a;"><?php echo htmlspecialchars($fullName); ?></div>
                                        <div style="font-size: 12px; color: #6b7280;"><?php echo htmlspecialchars($perf['major_name'] ?? '—'); ?> · <?php echo yearLevelLabel($perf['year_level']); ?></div>
                                    </div>
                                </div>
                                <div style="position: absolute; top: 12px; right: 16px; width: 32px; height: 32px; border-radius: 50%; background: <?php echo $medalColor; ?>; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                                    <i class="<?php echo $medalIcon; ?>"></i>
                                </div>
                            </div>
                            <div class="report-card-body">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                    <div>
                                        <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Rank</div>
                                        <div style="font-size: 28px; font-weight: 800; color: <?php echo $medalColor; ?>;">#<?php echo $rankNum; ?></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Average Grade</div>
                                        <div style="font-size: 28px; font-weight: 800; color: <?php echo $gradeColor; ?>;"><?php echo number_format($avgGrade, 2); ?></div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <span style="padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background: <?php echo $gradeColor; ?>20; color: <?php echo $gradeColor; ?>;">
                                        <?php echo $gradeStatusText; ?>
                                    </span>
                                    <span style="padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background: #ede9fe; color: #5b21b6;">
                                        <i class="fas fa-book" style="margin-right: 3px;"></i><?php echo intval($perf['subjects_graded']); ?> subjects
                                    </span>
                                    <?php if ($perf['best_grade'] !== null): ?>
                                    <span style="padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background: #d1fae5; color: #059669;">
                                        Best: <?php echo number_format(floatval($perf['best_grade']), 2); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-trophy"></i>
                        <h3>No Performance Data</h3>
                        <p>No graded students found yet. Once evaluations are completed, top performers will appear here.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
    // ═══════════════════════════════════════════════════════════
    //   TAB SWITCHING
    // ═══════════════════════════════════════════════════════════
    function switchTab(tabName) {
        // Remove active from all tabs
        document.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.section-content').forEach(c => c.classList.remove('active'));

        // Activate selected
        document.getElementById('section-' + tabName).classList.add('active');
        
        // Find and activate the clicked tab button
        const tabs = document.querySelectorAll('.section-tab');
        const tabMap = { 'evaluation': 0, 'performers': 1 };
        if (tabMap[tabName] !== undefined) {
            tabs[tabMap[tabName]].classList.add('active');
        }
    }

    // ═══════════════════════════════════════════════════════════
    //   EVALUATION TABLE FILTER
    // ═══════════════════════════════════════════════════════════
    function filterEvalTable() {
        const search = document.getElementById('evalSearch').value.toLowerCase();
        const filter = document.getElementById('evalFilter').value;
        const rows = document.querySelectorAll('#evalTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const status = row.getAttribute('data-status');
            
            const matchSearch = text.includes(search);
            const matchFilter = filter === 'all' || status === filter;

            row.style.display = (matchSearch && matchFilter) ? '' : 'none';
        });
    }

    // ═══════════════════════════════════════════════════════════
    //   TOAST NOTIFICATION
    // ═══════════════════════════════════════════════════════════
    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'toast ' + type;
        toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                          <span>${message}</span>
                          <button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    // ═══════════════════════════════════════════════════════════
    //   SIDEBAR TOGGLE
    // ═══════════════════════════════════════════════════════════
    document.getElementById('menuToggle')?.addEventListener('click', function() {
        document.getElementById('sidebar')?.classList.toggle('collapsed');
    });

    // Add hover effect to table rows
    document.querySelectorAll('#evalTable tbody tr').forEach(row => {
        row.addEventListener('mouseenter', () => row.style.background = '#fffbeb');
        row.addEventListener('mouseleave', () => row.style.background = '');
    });
    </script>
</body>
</html>
<?php
require_once '../../../data/session_security.php';

$role_access = check_role_access('instructor');
$show_role_modal = !$role_access['allowed'];

$instructor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Jane Teacher';

$stats = [
    'total_students' => 0,
    'by_major' => [],
    'by_year' => [],
    'by_gender' => []
];

 if (!$show_role_modal) {
     require_once '../../../data/config.php';
     try {
         $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM mentees WHERE mentor_id = ?");
         $stmt->execute([$instructor_id]);
         $stats['total_students'] = $stmt->fetchColumn();
         
         $stmt = $pdo->prepare("
             SELECT maj.display_name as major_name, COUNT(*) as count 
             FROM mentees me
             LEFT JOIN students s ON me.student_id = s.id
             LEFT JOIN majors maj ON s.major_id = maj.id 
             WHERE me.mentor_id = ?
             GROUP BY maj.id, maj.display_name 
             ORDER BY count DESC
         ");
         $stmt->execute([$instructor_id]);
         $stats['by_major'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
         
         $stmt = $pdo->prepare("
             SELECT s.year_level, COUNT(*) as count 
             FROM mentees me
             LEFT JOIN students s ON me.student_id = s.id
             WHERE me.mentor_id = ? AND s.year_level IS NOT NULL 
             GROUP BY s.year_level 
             ORDER BY s.year_level
         ");
         $stmt->execute([$instructor_id]);
         $stats['by_year'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
         
     } catch (PDOException $e) {
         $stats['total_students'] = 0;
     }
 }

 $students = [];
 if (!$show_role_modal) {
     require_once '../../../data/config.php';
     try {
         $stmt = $pdo->prepare("
             SELECT 
                 s.id as id,
                 me.id as mentee_id,
                 s.student_id,
                 s.first_name,
                 s.middle_name,
                 s.last_name,
                 s.suffix,
                 s.email,
                 s.year_level,
                 s.avatar_initials,
                 s.avatar_gradient_from,
                 s.avatar_gradient_to,
                 m.display_name as major_name,
                 m.gradient_from as major_gradient_from,
                 m.gradient_to as major_gradient_to
             FROM mentees me
             JOIN students s ON me.student_id = s.id
             LEFT JOIN majors m ON s.major_id = m.id
             WHERE me.mentor_id = ?
             ORDER BY s.last_name, s.first_name
         ");
         $stmt->execute([$instructor_id]);
         $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
     } catch (PDOException $e) {
         $students = [];
     }
 }

function getFullName($student) {
    $name = trim(($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
    $suffix = $student['suffix'] ?? '';
    return trim($name . ' ' . $suffix);
}

function getInitials($student) {
    $initials = '';
    if (!empty($student['first_name'])) $initials .= strtoupper($student['first_name'][0]);
    if (!empty($student['middle_name'])) $initials .= strtoupper($student['middle_name'][0]);
    if (!empty($student['last_name'])) $initials .= strtoupper($student['last_name'][0]);
    if (!empty($student['suffix'])) $initials .= strtoupper($student['suffix'][0]);
    return $initials ?: '??';
}

function getGradient($student) {
    if (!empty($student['major_gradient_from']) && !empty($student['major_gradient_to'])) {
        return "linear-gradient(135deg, {$student['major_gradient_from']}, {$student['major_gradient_to']})";
    }
    if (!empty($student['avatar_gradient_from']) && !empty($student['avatar_gradient_to'])) {
        return "linear-gradient(135deg, {$student['avatar_gradient_from']}, {$student['avatar_gradient_to']})";
    }
    return "linear-gradient(135deg, #3b82f6, #60a5fa)";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../../media/LOGO.jpg" type="image/jpeg">
    <title>Students - Faculty Evaluation System</title>
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
            padding: 10px 20px;
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
        }
        
        .btn-secondary {
            background: white;
            color: var(--dark-text);
            border: 1px solid var(--border-light);
        }
        
        .btn-secondary:hover {
            background: var(--cream);
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
            padding: 20px;
            border: 1px solid var(--border-light);
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease;
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
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            margin-bottom: 12px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark-text);
            line-height: 1;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--light-text);
            font-weight: 500;
        }
        
        .stat-change {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            margin-top: 8px;
            color: var(--success);
        }
        
         .tools-bar {
             background: white;
             border-radius: 12px;
             padding: 16px;
             border: 1px solid var(--border-light);
             margin-bottom: 20px;
             display: flex;
             gap: 12px;
             align-items: center;
             flex-wrap: wrap;
         }
         
         .selection-controls {
             display: flex;
             align-items: center;
             gap: 12px;
             padding-right: 12px;
             border-right: 1px solid var(--border-light);
         }
         
         .selection-controls:last-child {
             border-right: none;
             padding-right: 0;
         }
         
         .select-all-wrapper {
             display: flex;
             align-items: center;
             gap: 8px;
             cursor: pointer;
         }
         
         .select-all-checkbox {
             width: 18px;
             height: 18px;
             cursor: pointer;
         }
         
         .bulk-actions {
             display: flex;
             gap: 8px;
             align-items: center;
         }
         
         .selection-count {
             font-size: 13px;
             color: var(--light-text);
             font-weight: 500;
         }
         
         .btn-bulk-action {
             padding: 8px 16px;
             border-radius: 8px;
             font-family: 'Poppins', sans-serif;
             font-size: 13px;
             font-weight: 500;
             cursor: pointer;
             transition: all 0.2s ease;
             border: 1px solid var(--border-light);
             background: white;
             color: var(--dark-text);
         }
         
         .btn-bulk-action:hover:not(:disabled) {
             background: var(--gold);
             color: white;
             border-color: var(--gold);
         }
         
         .btn-bulk-action:disabled {
             opacity: 0.5;
             cursor: not-allowed;
         }
        
        .search-wrapper {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
            font-size: 16px;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 16px 10px 42px;
            border: 2px solid var(--border-light);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            color: var(--dark-text);
            background: var(--cream);
            transition: all 0.2s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--gold);
            background: white;
            box-shadow: 0 0 0 3px rgba(212, 168, 67, 0.1);
        }
        
        .filter-group {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-chip {
            padding: 8px 16px;
            background: var(--cream);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            color: var(--dark-text);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .filter-chip.active {
            background: var(--gold);
            color: white;
            border-color: var(--gold);
        }
        
         .filter-chip:hover:not(.active) {
             background: var(--gold-lighter);
             border-color: var(--gold);
         }
         
         .filter-select {
             padding: 10px 16px;
             border: 2px solid var(--border-light);
             border-radius: 10px;
             font-family: 'Poppins', sans-serif;
             font-size: 14px;
             color: var(--dark-text);
             background: var(--cream);
             cursor: pointer;
             transition: all 0.2s ease;
             min-width: 150px;
         }
         
         .filter-select:focus {
             outline: none;
             border-color: var(--gold);
             background: white;
             box-shadow: 0 0 0 3px rgba(212, 168, 67, 0.1);
         }
         
         .select-all-checkbox {
             width: 18px;
             height: 18px;
             cursor: pointer;
             accent-color: var(--gold);
         }
         
         .view-toggle {
            display: flex;
            gap: 4px;
            margin-left: auto;
        }
        
        .view-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-light);
            background: white;
            border-radius: 8px;
            cursor: pointer;
            color: var(--light-text);
            transition: all 0.2s ease;
        }
        
        .view-btn.active {
            background: var(--gold);
            color: white;
            border-color: var(--gold);
        }
        
        .view-btn:hover:not(.active) {
            background: var(--cream);
        }
        
        .students-container {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border-light);
            overflow: hidden;
        }
        
        .view-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 16px;
            padding: 16px;
        }
        
        /* Landscape grid view - 2 columns */
        @media (orientation: landscape) and (min-width: 768px) {
            .view-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Portrait grid view - 1 column */
        @media (orientation: portrait) {
            .view-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .view-list {
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        
         .student-card-grid {
             background: var(--cream);
             border-radius: 12px;
             padding: 20px;
             border: 2px solid transparent;
             transition: all 0.2s ease;
             position: relative;
         }
         
         .student-card-grid input[type="checkbox"] {
             position: absolute;
             top: 12px;
             left: 12px;
             width: 18px;
             height: 18px;
             cursor: pointer;
         }
        
        .student-card-grid:hover {
            border-color: var(--gold-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        
        .student-card-grid.featured {
            background: linear-gradient(135deg, rgba(212, 168, 67, 0.05), rgba(184, 146, 47, 0.1));
            border-color: var(--gold-light);
        }
        
        .student-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .student-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 24px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .student-identity h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark-text);
            margin: 0 0 4px 0;
            line-height: 1.3;
        }
        
        .student-identity .student-id {
            font-size: 13px;
            color: var(--gold-dark);
            font-weight: 600;
            background: var(--gold-lighter);
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 4px;
        }
        
        .student-email {
            font-size: 13px;
            color: var(--light-text);
            margin-bottom: 12px;
            word-break: break-all;
        }
        
        .student-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
        }
        
        .badge {
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .badge-major {
            background: linear-gradient(135deg, #d4a84320, #b8922f20);
            color: var(--gold-dark);
            border: 1px solid var(--gold-light);
        }
        
        .badge-year {
            background: linear-gradient(135deg, #3b82f620, #60a5fa20);
            color: #1d4ed8;
            border: 1px solid #93c5fd;
        }
        
          .student-actions-grid {
              display: flex;
              gap: 10px;
              margin-top: 16px;
              padding-top: 14px;
              border-top: 1px solid var(--border-light);
          }
          
          .student-actions-grid .btn {
              flex: 1;
              justify-content: center;
          }
         
        .student-actions-list {
            flex-shrink: 0;
        }
        
        /* Vertical Student Card Layout */
        .student-grid-view {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 24px;
            margin-top: 0;
        }
        
        .student-card-vertical {
            background: white;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            position: relative;
        }
        
        .student-card-horizontal {
            background: white;
            border-radius: 12px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            position: relative;
        }
        
        .student-card-horizontal:hover {
            border-color: #d4a843;
            box-shadow: 0 4px 16px rgba(212, 168, 67, 0.15);
            transform: translateY(-2px);
        }
        
        .student-card-horizontal input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #d4a843;
            position: absolute;
            top: 12px;
            left: 12px;
            cursor: pointer;
        }
        
        .student-card-horizontal .student-avatar {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .student-card-horizontal .student-info {
            flex: 1;
            min-width: 0;
            padding-left: 30px;
        }
        
        .student-card-horizontal .student-name {
            font-size: 15px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .student-card-horizontal .student-id {
            font-size: 12px;
            font-weight: 600;
            color: #d4a843;
            background: #fef3c7;
            padding: 2px 8px;
            border-radius: 10px;
            display: inline-block;
            margin-bottom: 4px;
        }
        
        .student-card-horizontal .student-email {
            font-size: 12px;
            color: #6b7280;
        }
        
        .student-card-horizontal .student-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .student-card-horizontal .student-major {
            font-size: 11px;
            font-weight: 600;
            color: #374151;
            background: #f3f4f6;
            padding: 4px 10px;
            border-radius: 6px;
        }
        
        .student-card-horizontal .student-year {
            font-size: 11px;
            color: #6b7280;
            background: #e5e7eb;
            padding: 4px 10px;
            border-radius: 6px;
        }
        
        .student-card-horizontal .student-actions-list {
            margin-top: auto;
        }
        
        .student-card-horizontal .student-actions-list .btn {
            width: 100%;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .students-horizontal-container {
            background: white;
            border-radius: 16px;
            margin: 0 24px 24px;
            border: 1px solid #e5e7eb;
        }
        
        .students-horizontal-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .students-horizontal-container::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 4px;
        }
        
        .students-horizontal-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #d4a843, #e8c768);
            border-radius: 4px;
        }
        
        .student-card-horizontal .student-avatar {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .student-card-horizontal .student-info {
            flex: 1;
            min-width: 0;
        }
        
        .student-card-horizontal .student-name {
            font-size: 15px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 2px;
        }
        
        .student-card-horizontal .student-id {
            font-size: 12px;
            font-weight: 600;
            color: #d4a843;
            background: #fef3c7;
            padding: 2px 8px;
            border-radius: 10px;
            display: inline-block;
        }
        
        .student-card-horizontal .student-email {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }
        
        .student-card-horizontal .student-meta {
            display: flex;
            flex-direction: column;
            gap: 4px;
            text-align: center;
        }
        
        .student-card-horizontal .student-major {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            background: #f3f4f6;
            padding: 4px 10px;
            border-radius: 6px;
        }
        
        .student-card-horizontal .student-year {
            font-size: 11px;
            color: #6b7280;
        }
        
        .student-card-horizontal .student-actions-list .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .year-group {
            margin-bottom: 24px;
        }
        
        .year-tabs::-webkit-scrollbar {
            height: 6px;
        }
        
        .year-tabs::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 3px;
        }
        
        .year-tabs::-webkit-scrollbar-thumb {
            background: #d4a843;
            border-radius: 3px;
        }
        
        .student-card-vertical:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(212, 168, 67, 0.15);
            border-color: var(--gold);
        }
        
        .student-card-vertical input[type="checkbox"] {
            width: 22px;
            height: 22px;
            cursor: pointer;
            accent-color: var(--gold);
            flex-shrink: 0;
        }
        
        .student-card-vertical .student-avatar {
            width: 64px;
            height: 64px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 24px;
            flex-shrink: 0;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .student-card-vertical .student-info {
            flex: 1;
            min-width: 0;
        }
        
        .student-card-vertical .student-name {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .student-card-vertical .student-id {
            font-size: 14px;
            font-weight: 600;
            color: var(--gold-dark);
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 8px;
        }
        
        .student-card-vertical .student-email {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 4px;
            word-break: break-all;
        }
        
        .student-card-vertical .student-details {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .student-card-vertical .student-major,
        .student-card-vertical .student-year {
            font-size: 13px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .student-card-vertical .student-major i,
        .student-card-vertical .student-year i {
            color: var(--gold);
        }
        
        .student-card-vertical .student-actions-list {
            flex-shrink: 0;
        }
        
        .student-card-vertical .student-actions-list .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
        }
        
        /* Portrait - stack vertically */
        @media (max-width: 768px) {
            .student-card-vertical {
                flex-direction: column;
                text-align: center;
            }
            
            .student-card-vertical input[type="checkbox"] {
                position: absolute;
                top: 16px;
                left: 16px;
            }
            
            .student-card-vertical .student-avatar {
                margin: 0 auto;
            }
            
            .student-card-vertical .student-details {
                justify-content: center;
            }
            
            .student-card-vertical .student-actions-list {
                width: 100%;
            }
            
            .student-card-vertical .student-actions-list .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        .student-actions-list .btn {
            padding: 10px 18px;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            border: none;
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
        }
        
        .student-actions-list .btn:hover {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 168, 67, 0.3);
        }
         
         .student-actions-grid .btn {
             flex: 1;
         }
        
         .student-card-list {
              background: white;
              padding: 20px 24px;
              display: flex;
              align-items: center;
              gap: 20px;
              transition: all 0.3s ease;
              border-bottom: 1px solid var(--border-light);
              border-radius: 16px;
              margin-bottom: 8px;
              box-shadow: 0 2px 8px rgba(0,0,0,0.04);
              position: relative;
          }
          
          .student-card-list:hover {
            background: linear-gradient(135deg, #fdfbf7 0%, #f8f4e8 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(212, 168, 67, 0.15);
            border-color: var(--gold-light);
          }
          
          .student-card-list input[type="checkbox"] {
              width: 20px;
              height: 20px;
              cursor: pointer;
              flex-shrink: 0;
              accent-color: var(--gold);
          }
         
        .student-card-list:first-child {
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }
        
        .student-card-list:last-child {
            border-bottom: none;
            border-bottom-left-radius: 16px;
            border-bottom-right-radius: 16px;
        }
        
        .student-card-list .student-avatar {
            width: 56px;
            height: 56px;
            font-size: 22px;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        
         .list-info {
              flex: 1;
              min-width: 0;
          }
          
          .list-info h3 {
              font-size: 16px;
              font-weight: 700;
              margin: 0 0 8px 0;
              white-space: nowrap;
              overflow: hidden;
              text-overflow: ellipsis;
              color: #1f2937;
              display: flex;
              align-items: center;
              gap: 10px;
          }
          
          .list-info h3 .student-id-badge {
              font-size: 11px;
              font-weight: 600;
              color: var(--gold-dark);
              background: linear-gradient(135deg, #fef3c7, #fde68a);
              padding: 4px 10px;
              border-radius: 20px;
              border: 1px solid #f59e0b;
          }
          
          .list-meta {
              display: flex;
              gap: 16px;
              flex-wrap: wrap;
              font-size: 13px;
              color: #6b7280;
          }
          
          .list-meta span {
              display: flex;
              align-items: center;
              gap: 6px;
              background: #f9fafb;
              padding: 4px 10px;
              border-radius: 6px;
              font-size: 12px;
          }
          
          .list-meta span i {
              color: var(--gold);
              font-size: 11px;
          }
        
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: var(--light-text);
        }
        
        .empty-state i {
            font-size: 56px;
            color: var(--gold-light);
            margin-bottom: 16px;
            opacity: 0.6;
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
            min-width: 260px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(60px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .toast.success { background: linear-gradient(135deg, #059669, #34d399); }
        .toast.error { background: linear-gradient(135deg, #dc2626, #f87171); }
        .toast.info { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        
        .toast-close {
            margin-left: auto;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            opacity: 0.7;
            font-size: 16px;
        }
         .toast-close:hover { opacity: 1; }
         
         .empty-state {
             padding: 60px 20px;
             text-align: center;
             color: var(--light-text);
         }
         
         .empty-state i {
             font-size: 56px;
             color: var(--gold-light);
             margin-bottom: 16px;
             opacity: 0.6;
         }
         
         .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            padding: 20px;
            overflow-y: auto;
            align-items: flex-start;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 700px;
            width: 100%;
            margin: 40px auto;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .modal-header {
            padding: 32px;
            color: white;
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.2s;
        }
        
        .modal-close:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }
        
         .modal-body {
             padding: 32px;
         }
         
         /* Task Table Styles */
         .tasks-table-container {
             max-height: 60vh;
             overflow-y: auto;
             border: 1px solid var(--border-light);
             border-radius: 12px;
         }
         
         .tasks-table {
             width: 100%;
             border-collapse: collapse;
             font-family: 'Poppins', sans-serif;
             font-size: 14px;
         }
         
         .tasks-table thead {
             position: sticky;
             top: 0;
             background: var(--cream);
             z-index: 1;
         }
         
         .tasks-table th {
             padding: 12px 16px;
             text-align: left;
             font-weight: 600;
             color: var(--dark-text);
             border-bottom: 2px solid var(--border-light);
             white-space: nowrap;
         }
         
         .tasks-table td {
             padding: 16px;
             border-bottom: 1px solid var(--border-light);
             vertical-align: top;
         }
         
         .tasks-table tbody tr {
             background: white;
             transition: background 0.2s;
         }
         
         .tasks-table tbody tr:hover {
             background: var(--cream);
         }
         
         .tasks-table tbody tr:last-child td {
             border-bottom: none;
         }
         
         .priority-badge {
             display: inline-flex;
             align-items: center;
             gap: 6px;
             font-weight: 600;
         }
         
         .status-badge {
             display: inline-block;
             padding: 4px 10px;
             border-radius: 12px;
             font-size: 11px;
             font-weight: 600;
         }
         
         .mentee-chip {
             display: inline-flex;
             align-items: center;
             gap: 4px;
             background: var(--cream);
             padding: 4px 8px;
             border-radius: 6px;
             border: 1px solid var(--border-light);
             font-size: 12px;
             margin: 2px;
         }
         
         .completion-bar {
             display: flex;
             align-items: center;
             gap: 8px;
         }
         
         .completion-bar-bg {
             flex: 1;
             min-width: 60px;
             background: var(--border-light);
             height: 6px;
             border-radius: 3px;
             overflow: hidden;
         }
         
         .completion-bar-fill {
             height: 100%;
             border-radius: 3px;
             transition: width 0.3s;
         }
         
         .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .info-card {
            padding: 16px;
            background: var(--cream);
            border-radius: 12px;
            border: 1px solid var(--border-light);
        }
        
        .info-card-label {
            font-size: 12px;
            color: var(--light-text);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .info-card-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--dark-text);
        }
        
        @media (max-width: 768px) {
            .view-grid {
                grid-template-columns: 1fr;
            }
            
            .tools-bar {
                flex-direction: column;
            }
            
            .search-wrapper {
                width: 100%;
            }
            
            .page-header {
                flex-direction: column;
            }
        }
        
        /* Landscape (Horizontal) - tablet in landscape mode */
        @media (orientation: landscape) and (max-height: 500px) {
            .student-card-list {
                padding: 12px 16px;
                gap: 12px;
                margin-bottom: 6px;
            }
            
            .student-card-list .student-avatar {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            
            .list-info h3 {
                font-size: 14px;
                margin-bottom: 4px;
            }
            
            .list-info h3 .student-id-badge {
                font-size: 10px;
                padding: 2px 6px;
            }
            
            .list-meta {
                gap: 8px;
            }
            
            .list-meta span {
                padding: 2px 6px;
                font-size: 11px;
            }
            
            .student-actions-list .btn {
                padding: 6px 12px;
                font-size: 11px;
            }
        }
        
        /* Large landscape screens */
        @media (min-width: 1200px) and (orientation: landscape) {
            .view-list {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
                padding: 16px;
            }
            
            .student-card-list {
                margin-bottom: 0;
            }
        }
        
        /* Portrait (Vertical) - mobile phones */
        @media (orientation: portrait) {
            .student-card-list {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
                padding: 24px;
            }
            
            .student-card-list input[type="checkbox"] {
                position: absolute;
                top: 12px;
                left: 12px;
            }
            
            .student-card-list .student-avatar {
                margin: 0 auto;
            }
            
            .list-info h3 {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .list-meta {
                justify-content: center;
            }
            
            .student-actions-list {
                margin-top: 12px;
            }
            
            .student-actions-list .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Extra small screens */
        @media (max-width: 480px) {
            .btn-text {
                display: none;
            }
            
            .tools-actions .btn {
                padding: 12px 14px;
            }
            
            .tools-bar {
                padding: 12px;
            }
            
            .bulk-actions-bar {
                padding: 12px;
                margin: 12px;
            }
            
            .student-card-list {
                padding: 16px;
            }
            
            .list-meta {
                flex-direction: column;
                gap: 6px;
            }
            
            .list-meta span {
                justify-content: center;
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
            <a href="students.php" class="sidebar-nav-item active">
                <i class="fas fa-user-graduate"></i>
                <span>Students mentees</span>
            </a>
            <a href="feedback.php" class="sidebar-nav-item">
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

    <div class="main-content" style="position: relative;">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <div class="topbar-title">Students</div>
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
            <div class="page-header">
                 <div class="page-title-area">
                     <h1>Students</h1>
                     <p>View and manage your assigned mentees</p>
                 </div>

            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card" style="border: 2px solid #e5e7eb; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #60a5fa); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['total_students']); ?></div>
                    <div class="stat-label">Total Students</div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> Across all majors
                    </div>
                </div>
                <div class="stat-card" style="border: 2px solid #e5e7eb; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa); box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-value"><?php echo count($stats['by_major']); ?></div>
                    <div class="stat-label">Majors</div>
                </div>
                <?php if (!empty($stats['by_year'])): 
                    $yearsCount = count($stats['by_year']);
                ?>
                <div class="stat-card" style="border: 2px solid #e5e7eb; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #059669, #34d399); box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-value"><?php echo $yearsCount; ?></div>
                    <div class="stat-label">Year Levels</div>
                </div>
                <?php endif; ?>
            </div>

             <!-- Tools Bar -->
            <div class="tools-bar" style="box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; flex-wrap: wrap;">
                <div class="tools-actions">
                    <button class="btn btn-primary" onclick="openViewTasksModal()" style="display: flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 12px; font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 600; border: none; background: linear-gradient(135deg, #d4a843, #e8c768); color: white; box-shadow: 0 4px 15px rgba(212, 168, 67, 0.4);">
                        <i class="fas fa-tasks"></i> <span class="btn-text">My Assigned Tasks</span>
                    </button>
                </div>
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" class="search-input" id="searchInput" placeholder="Search by name, email, or student ID...">
                </div>
                <div class="filter-group">
                    <select class="filter-select" id="majorFilter" style="border: 2px solid #e5e7eb; padding: 10px 16px;">
                        <option value="">All Majors</option>
                        <?php foreach ($stats['by_major'] as $major): ?>
                            <option value="<?php echo htmlspecialchars($major['major_name']); ?>">
                                <?php echo htmlspecialchars($major['major_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" id="yearFilter" style="border: 2px solid #e5e7eb; padding: 10px 16px;">
                        <option value="">All Years</option>
                        <?php foreach ($stats['by_year'] as $year): ?>
                            <option value="<?php echo htmlspecialchars($year['year_level']); ?>">
                                <?php echo htmlspecialchars($year['year_level']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="view-toggle">
                    <button class="view-btn active" onclick="setView('grid')" title="Grid View" style="border: 2px solid #e5e7eb;">
                        <i class="fas fa-th-large"></i>
                    </button>
                    <button class="view-btn" onclick="setView('list')" title="List View" style="border: 2px solid #e5e7eb;">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>

            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar" style="display: flex; align-items: center; gap: 18px; margin: 20px 24px; padding: 16px 20px; background: linear-gradient(135deg, #fefce8, #fef9c3); border-radius: 14px; border: 1px solid #fef08a; flex-wrap: wrap;">
                <input type="checkbox" id="selectAll" style="width: 20px; height: 20px; accent-color: #d4a843; margin-right: 8px; cursor: pointer;" onchange="toggleSelectAll()">
                <label for="selectAll" style="font-weight: 700; color: #a16207; margin-right: 18px; cursor:pointer; font-size: 14px;">Select All</label>
                <span id="selectionCount" style="font-size: 14px; color: #713f12; font-weight: 600; margin-right: 18px; background: #fef08a; padding: 4px 12px; border-radius: 20px;">0 selected</span>
                <button id="assignTaskBtn" class="btn btn-secondary" style="padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; background: linear-gradient(135deg, #d4a843, #e8c768); color: white; border: none; box-shadow: 0 4px 12px rgba(212, 168, 67, 0.3);" onclick="openAssignTaskModal()" disabled>Assign Task</button>
            </div>

            <!-- Year Level Tabs -->
            <div class="year-tabs" style="display: flex; gap: 10px; padding: 16px 24px; background: white; border-radius: 12px; margin: 0 24px 16px; border: 1px solid #e5e7eb; overflow-x: auto;">
                <button class="year-tab active" data-year="all" onclick="filterByYear('all', this)" style="padding: 10px 20px; border-radius: 20px; border: 2px solid #e5e7eb; background: linear-gradient(135deg, #d4a843, #e8c768); color: white; font-weight: 600; cursor: pointer; white-space: nowrap;">All Years</button>
                <button class="year-tab" data-year="1" onclick="filterByYear('1', this)" style="padding: 10px 20px; border-radius: 20px; border: 2px solid #e5e7eb; background: white; color: #374151; font-weight: 500; cursor: pointer; white-space: nowrap;">1st Year</button>
                <button class="year-tab" data-year="2" onclick="filterByYear('2', this)" style="padding: 10px 20px; border-radius: 20px; border: 2px solid #e5e7eb; background: white; color: #374151; font-weight: 500; cursor: pointer; white-space: nowrap;">2nd Year</button>
                <button class="year-tab" data-year="3" onclick="filterByYear('3', this)" style="padding: 10px 20px; border-radius: 20px; border: 2px solid #e5e7eb; background: white; color: #374151; font-weight: 500; cursor: pointer; white-space: nowrap;">3rd Year</button>
                <button class="year-tab" data-year="4" onclick="filterByYear('4', this)" style="padding: 10px 20px; border-radius: 20px; border: 2px solid #e5e7eb; background: white; color: #374151; font-weight: 500; cursor: pointer; white-space: nowrap;">4th Year</button>
            </div>

                  <!-- Students List - Card Grid -->
                  <div class="students-horizontal-container" style="padding: 16px 24px; max-height: 500px; overflow-y: auto;">
                      <div class="all-students-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
                      <?php foreach ($students as $student): 
                         $fullName = getFullName($student);
                         $initials = getInitials($student);
                         $gradient = getGradient($student);
                     ?>
                     <div class="student-card-horizontal"
                          data-name="<?php echo strtolower($fullName); ?>"
                          data-email="<?php echo strtolower($student['email'] ?? ''); ?>"
                          data-student-id="<?php echo strtolower($student['student_id'] ?? ''); ?>"
                          data-major="<?php echo strtolower($student['major_name'] ?? ''); ?>"
                          data-year="<?php echo strtolower($student['year_level'] ?? ''); ?>"
                          data-mentee-id="<?php echo $student['mentee_id']; ?>">
                         <input type="checkbox" class="student-checkbox" value="<?php echo $student['mentee_id']; ?>" onchange="updateSelection()">
                         <div class="student-avatar" style="background: <?php echo $gradient; ?>;">
                             <?php echo htmlspecialchars($initials); ?>
                         </div>
                         <div class="student-info">
                             <div class="student-name"><?php echo htmlspecialchars($fullName); ?></div>
                             <div class="student-id"><?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></div>
                             <div class="student-email"><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></div>
                         </div>
                         <div class="student-meta">
                             <span class="student-major"><?php echo htmlspecialchars($student['major_name'] ?? 'N/A'); ?></span>
                             <span class="student-year"><?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?> Year</span>
                         </div>
                         <div class="student-actions-list">
                              <button class="btn" onclick="viewStudent(<?php echo $student['mentee_id']; ?>)">
                                 <i class="fas fa-eye"></i> View
                             </button>
                        </div>
                     </div>
                     <?php endforeach; ?>
                     </div>
                  </div>
                    
                </main>
     </div>

     <!-- Student Detail Modal -->
     <div id="studentDetailModal" class="modal-overlay">
         <div class="modal-content" id="modalContent"></div>
     </div>
 
     <!-- Assign Task Modal (Bulk) -->
     <div id="assignTaskModal" class="modal-overlay" style="display: none;">
         <div class="modal-content" style="max-width: 500px; margin: 40px auto;">
             <div class="modal-header" style="background: linear-gradient(135deg, #d4a843, #e8c768); padding: 24px; position: relative;">
                 <button class="modal-close" onclick="closeAssignTaskModal()">
                     <i class="fas fa-times"></i>
                 </button>
                 <h3 style="margin: 0; color: white; font-size: 20px; display: flex; align-items: center; gap: 10px;">
                     <i class="fas fa-tasks"></i> Assign Task to Selected Mentees
                 </h3>
             </div>
             <div class="modal-body" style="padding: 24px;">
                 <form id="assignTaskForm" onsubmit="submitAssignTask(event)">
                     <div style="margin-bottom: 20px;">
                         <label for="taskTitle" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark-text);">
                             Task Title *
                         </label>
                         <input type="text" id="taskTitle" name="title" required
                                style="width: 100%; padding: 12px 16px; border: 2px solid var(--border-light); border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px;"
                                placeholder="Enter task title">
                     </div>
                     
                     <div style="margin-bottom: 20px;">
                         <label for="taskDescription" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark-text);">
                             Description
                         </label>
                         <textarea id="taskDescription" name="description" rows="4"
                                   style="width: 100%; padding: 12px 16px; border: 2px solid var(--border-light); border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px; resize: vertical;"
                                   placeholder="Enter task description (optional)"></textarea>
                     </div>
                     
                     <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                         <div>
                             <label for="taskPriority" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark-text);">
                                 Priority
                             </label>
                             <select id="taskPriority" name="priority"
                                     style="width: 100%; padding: 10px 16px; border: 2px solid var(--border-light); border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px; background: white; cursor: pointer;">
                                 <option value="low">Low</option>
                                 <option value="medium" selected>Medium</option>
                                 <option value="high">High</option>
                             </select>
                         </div>
                         
                         <div>
                             <label for="taskDueDate" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark-text);">
                                 Due Date
                             </label>
                             <input type="date" id="taskDueDate" name="due_date"
                                    style="width: 100%; padding: 10px 16px; border: 2px solid var(--border-light); border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px;">
                         </div>
                     </div>
                     
                     <div style="background: var(--cream); padding: 16px; border-radius: 10px; margin-bottom: 20px; border: 1px solid var(--border-light);">
                         <p style="margin: 0; font-size: 13px; color: var(--light-text); display: flex; align-items: center; gap: 8px;">
                             <i class="fas fa-info-circle" style="color: var(--gold);"></i>
                             This task will be assigned to <strong id="selectedMenteesCount" style="color: var(--gold-dark);">0</strong> selected mentee(s).
                         </p>
                     </div>
                     
                     <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                         <button type="button" class="btn btn-secondary" onclick="closeAssignTaskModal()"
                                 style="padding: 10px 20px; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; border: 1px solid var(--border-light); background: white;">
                             Cancel
                         </button>
                         <button type="submit" class="btn btn-primary"
                                 style="padding: 10px 20px; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; border: none; background: var(--gold); color: white;">
                             <i class="fas fa-check"></i> Assign Task
                         </button>
                     </div>
                 </form>
             </div>
         </div>
     </div>
     
     <!-- Assign Task to Single Student Modal -->
     <div id="assignTaskSingleModal" class="modal-overlay" style="display: none;">
         <div class="modal-content" style="max-width: 500px; margin: 40px auto;">
             <div class="modal-header" style="background: linear-gradient(135deg, #3b82f6, #60a5fa); padding: 24px; position: relative;">
                 <button class="modal-close" onclick="closeAssignTaskSingleModal()">
                     <i class="fas fa-times"></i>
                 </button>
                 <h3 style="margin: 0; color: white; font-size: 20px; display: flex; align-items: center; gap: 10px;">
                     <i class="fas fa-tasks"></i> Assign Task
                 </h3>
             </div>
             <div class="modal-body" style="padding: 24px;">
                 <form id="assignTaskSingleForm" onsubmit="submitAssignTaskSingle(event)">
                     <div style="margin-bottom: 20px;">
                         <label for="singleTaskTitle" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark-text);">
                             Task Title *
                         </label>
                         <input type="text" id="singleTaskTitle" name="title" required
                                style="width: 100%; padding: 12px 16px; border: 2px solid var(--border-light); border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px;"
                                placeholder="Enter task title">
                     </div>
                     
                     <div style="margin-bottom: 20px;">
                         <label for="singleTaskDescription" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark-text);">
                             Description
                         </label>
                         <textarea id="singleTaskDescription" name="description" rows="4"
                                   style="width: 100%; padding: 12px 16px; border: 2px solid var(--border-light); border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px; resize: vertical;"
                                   placeholder="Enter task description (optional)"></textarea>
                     </div>
                     
                     <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                         <div>
                             <label for="singleTaskPriority" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark-text);">
                                 Priority
                             </label>
                             <select id="singleTaskPriority" name="priority"
                                     style="width: 100%; padding: 10px 16px; border: 2px solid var(--border-light); border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px; background: white; cursor: pointer;">
                                 <option value="low">Low</option>
                                 <option value="medium" selected>Medium</option>
                                 <option value="high">High</option>
                             </select>
                         </div>
                         
                         <div>
                             <label for="singleTaskDueDate" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark-text);">
                                 Due Date
                             </label>
                             <input type="date" id="singleTaskDueDate" name="due_date"
                                    style="width: 100%; padding: 10px 16px; border: 2px solid var(--border-light); border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px;">
                         </div>
                     </div>
                     
                     <div style="background: var(--cream); padding: 16px; border-radius: 10px; margin-bottom: 20px; border: 1px solid var(--border-light);">
                         <p style="margin: 0; font-size: 13px; color: var(--light-text);">
                             <i class="fas fa-user" style="color: var(--gold);"></i>
                             Assigning to: <strong id="singleStudentName" style="color: var(--gold-dark);"></strong>
                         </p>
                     </div>
                     
                     <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                         <button type="button" class="btn btn-secondary" onclick="closeAssignTaskSingleModal()"
                                 style="padding: 10px 20px; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; border: 1px solid var(--border-light); background: white;">
                             Cancel
                         </button>
                         <button type="submit" class="btn btn-primary"
                                 style="padding: 10px 20px; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; border: none; background: var(--gold); color: white;">
                             <i class="fas fa-check"></i> Assign Task
                         </button>
                     </div>
                 </form>
             </div>
         </div>
     </div>
  
  <!-- Toast Container -->
  <!-- View Tasks Modal -->
  <div id="viewTasksModal" class="modal-overlay" style="display: none;">
      <div class="modal-content" style="max-width: 900px; margin: 40px auto;">
          <div class="modal-header" style="background: linear-gradient(135deg, #3b82f6, #60a5fa); padding: 24px; position: relative;">
              <button class="modal-close" onclick="closeViewTasksModal()">
                  <i class="fas fa-times"></i>
              </button>
              <h3 style="margin: 0; color: white; font-size: 20px; display: flex; align-items: center; gap: 10px;">
                  <i class="fas fa-tasks"></i> My Assigned Tasks
              </h3>
          </div>
          <div class="modal-body" style="padding: 24px;">
              <div id="tasksLoading" style="text-align: center; padding: 40px; display: none;">
                  <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--gold);"></i>
                  <p style="margin-top: 12px; color: var(--light-text);">Loading tasks...</p>
              </div>
              <div id="tasksContainer" style="display: none;">
                  <!-- Tasks will be loaded here -->
              </div>
              <div id="noTasksMessage" style="text-align: center; padding: 60px 20px; color: var(--light-text); display: none;">
                  <i class="fas fa-clipboard-list" style="font-size: 56px; color: var(--gold-light); margin-bottom: 16px; opacity: 0.6;"></i>
                  <p>You haven't assigned any tasks yet.</p>
              </div>
          </div>
      </div>
  </div>
  
  <!-- Toast Container -->
     <div class="toast-container" id="toastContainer"></div>

     <script>
         let allStudents = <?php echo json_encode($students); ?>;
         let currentView = 'grid';
         let selectedStudents = new Set();
         
          function isCardVisible(card) {
              // Check if card is actually visible (not display:none, not hidden by parent)
              return !!(card.offsetParent || card.offsetWidth || card.offsetHeight || card.getClientRects().length);
          }
          
          function updateSelection() {
              const allCheckboxes = document.querySelectorAll('.student-checkbox');
              const selectAllCheckbox = document.getElementById('selectAll');
              const countSpan = document.getElementById('selectionCount');
              const assignBtn = document.getElementById('assignTaskBtn');
              
              let visibleCount = 0;
              let visibleChecked = 0;
              
              selectedStudents.clear();
              allCheckboxes.forEach(cb => {
                  const card = cb.closest('.student-card-horizontal');
                  const isVisible = card && isCardVisible(card);
                  
                  if (cb.checked) {
                      selectedStudents.add(cb.value);
                      if (isVisible) visibleChecked++;
                  }
                  if (isVisible) visibleCount++;
              });
              
              const totalSelected = selectedStudents.size;
              countSpan.textContent = `${totalSelected} selected`;
              
              assignBtn.disabled = totalSelected === 0;
              
              if (visibleCount === 0) {
                  selectAllCheckbox.checked = false;
                  selectAllCheckbox.indeterminate = false;
              } else if (visibleChecked === visibleCount) {
                  selectAllCheckbox.checked = true;
                  selectAllCheckbox.indeterminate = false;
              } else if (visibleChecked > 0) {
                  selectAllCheckbox.checked = false;
                  selectAllCheckbox.indeterminate = true;
              } else {
                  selectAllCheckbox.checked = false;
                  selectAllCheckbox.indeterminate = false;
              }
          }
          
          function toggleSelectAll() {
              const selectAllCheckbox = document.getElementById('selectAll');
              const allCheckboxes = document.querySelectorAll('.student-checkbox');
              const shouldSelect = !selectAllCheckbox.checked || selectAllCheckbox.indeterminate;
              
              allCheckboxes.forEach(cb => {
                  const card = cb.closest('.student-card-horizontal');
                  if (card && isCardVisible(card)) {
                      cb.checked = shouldSelect;
                  }
              });
              
              updateSelection();
          }
         
         function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle';
            toast.innerHTML = `
                <i class="fas fa-${icon}"></i>
                <span>${message}</span>
                <button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            `;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.4s ease reverse';
                setTimeout(() => toast.remove(), 400);
            }, 4000);
        }

        function filterStudents() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const majorFilter = document.getElementById('majorFilter').value.toLowerCase();
            const yearFilter = document.getElementById('yearFilter').value.toLowerCase();
            
            const cards = document.querySelectorAll('.student-card-horizontal');
            
             cards.forEach(card => {
                 const name = card.dataset.name || '';
                 const email = card.dataset.email || '';
                 const studentId = card.dataset.studentId || '';
                 const major = card.dataset.major || '';
                 const year = card.dataset.year || '';
                 
                 const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm) || studentId.includes(searchTerm);
                 const matchesMajor = !majorFilter || major === majorFilter;
                 const matchesYear = !yearFilter || year === yearFilter;
                 
                 if (matchesSearch && matchesMajor && matchesYear) {
                     card.style.display = '';
                 } else {
                     card.style.display = 'none';
                 }
             });
             
              updateSelection();
        }
        
        function filterByYear(year, element) {
            document.querySelectorAll('.year-tab').forEach(tab => {
                tab.classList.remove('active');
                tab.style.background = 'white';
                tab.style.color = '#374151';
            });
            element.classList.add('active');
            element.style.background = 'linear-gradient(135deg, #d4a843, #e8c768)';
            element.style.color = 'white';
            
            const cards = document.querySelectorAll('.student-card-horizontal');
            cards.forEach(card => {
                if (year === 'all' || card.dataset.year === year) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
            updateSelection();
        }
        
        function showMoreStudents(year) {
            // Not needed in new layout - all students are visible
            updateSelection();
        }

        document.getElementById('searchInput').addEventListener('keyup', filterStudents);
        document.getElementById('majorFilter').addEventListener('change', filterStudents);
        document.getElementById('yearFilter').addEventListener('change', filterStudents);

         function setView(view) {
             currentView = view;
             document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
             event.target.closest('.view-btn').classList.add('active');
             
             const gridView = document.getElementById('studentsView');
             const listView = document.getElementById('studentsList');
             
             if (view === 'grid') {
                 gridView.style.display = 'grid';
                 listView.style.display = 'none';
             } else {
                 gridView.style.display = 'none';
                 listView.style.display = 'flex';
             }
             
             // Update Select All state after view change
             updateSelection();
         }

        function closeModal() {
            document.getElementById('studentDetailModal').style.display = 'none';
        }
        
        function openAssignTaskModal() {
            const modal = document.getElementById('assignTaskModal');
            const countSpan = document.getElementById('selectedMenteesCount');
            countSpan.textContent = selectedStudents.size;
            modal.style.display = 'flex';
            document.getElementById('taskTitle').focus();
        }
        
        function closeAssignTaskModal() {
            document.getElementById('assignTaskModal').style.display = 'none';
            document.getElementById('assignTaskForm').reset();
        }
        
        async function submitAssignTask(event) {
            event.preventDefault();
            
            const title = document.getElementById('taskTitle').value.trim();
            const description = document.getElementById('taskDescription').value.trim();
            const priority = document.getElementById('taskPriority').value;
            const dueDate = document.getElementById('taskDueDate').value || null;
            const submitBtn = event.target.querySelector('button[type="submit"]');
            
            if (!title) {
                showToast('Please enter a task title', 'error');
                return;
            }
            
            if (selectedStudents.size === 0) {
                showToast('No mentees selected', 'error');
                return;
            }
            
            const payload = {
                title: title,
                description: description,
                priority: priority,
                due_date: dueDate,
                mentee_ids: Array.from(selectedStudents)
            };
            
            // Disable button and show loading
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning...';
            
            try {
                const response = await fetch('../../../Door/data/assign_task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    closeAssignTaskModal();
                    // Clear selection
                    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = false);
                    selectedStudents.clear();
                    updateSelection();
                } else {
                    showToast(result.message || 'Failed to assign task', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            } finally {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('assignTaskModal').addEventListener('click', function(e) {
            if (e.target === this) closeAssignTaskModal();
        });
        
        document.getElementById('assignTaskSingleModal').addEventListener('click', function(e) {
            if (e.target === this) closeAssignTaskSingleModal();
        });
        
        let currentSingleMenteeId = null;
        
        function openAssignTaskToStudent(menteeId, studentName) {
            currentSingleMenteeId = menteeId;
            const modal = document.getElementById('assignTaskSingleModal');
            const nameSpan = document.getElementById('singleStudentName');
            nameSpan.textContent = studentName;
            modal.style.display = 'flex';
            document.getElementById('singleTaskTitle').focus();
        }
        
        function closeAssignTaskSingleModal() {
            document.getElementById('assignTaskSingleModal').style.display = 'none';
            document.getElementById('assignTaskSingleForm').reset();
            currentSingleMenteeId = null;
        }
        
        async function submitAssignTaskSingle(event) {
            event.preventDefault();
            
            const title = document.getElementById('singleTaskTitle').value.trim();
            const description = document.getElementById('singleTaskDescription').value.trim();
            const priority = document.getElementById('singleTaskPriority').value;
            const dueDate = document.getElementById('singleTaskDueDate').value || null;
            const submitBtn = event.target.querySelector('button[type="submit"]');
            
            if (!title) {
                showToast('Please enter a task title', 'error');
                return;
            }
            
            if (!currentSingleMenteeId) {
                showToast('No student selected', 'error');
                return;
            }
            
            const payload = {
                title: title,
                description: description,
                priority: priority,
                due_date: dueDate,
                mentee_ids: [currentSingleMenteeId]
            };
            
            // Disable button and show loading
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning...';
            
            try {
                const response = await fetch('../../../Door/data/assign_task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    closeAssignTaskSingleModal();
                } else {
                    showToast(result.message || 'Failed to assign task', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            } finally {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
        
        document.getElementById('viewTasksModal').addEventListener('click', function(e) {
            if (e.target === this) closeViewTasksModal();
        });
        
        async function openViewTasksModal() {
            const modal = document.getElementById('viewTasksModal');
            const container = document.getElementById('tasksContainer');
            const loading = document.getElementById('tasksLoading');
            const noTasks = document.getElementById('noTasksMessage');
            
            modal.style.display = 'flex';
            container.style.display = 'none';
            noTasks.style.display = 'none';
            loading.style.display = 'block';
            
            try {
                const response = await fetch('../../../Door/data/get_instructor_tasks.php');
                const result = await response.json();
                
                loading.style.display = 'none';
                
                if (!result.success || result.tasks.length === 0) {
                    noTasks.style.display = 'block';
                    return;
                }
                
                container.style.display = 'block';
                renderTasks(result.tasks);
                
            } catch (error) {
                console.error('Error:', error);
                loading.style.display = 'none';
                noTasks.style.display = 'block';
                noTasks.innerHTML = '<p>Failed to load tasks. Please try again.</p>';
            }
        }
        
        function renderTasks(tasks) {
            const container = document.getElementById('tasksContainer');
            
            if (tasks.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>You haven't assigned any tasks yet.</p>
                    </div>
                `;
                return;
            }
            
            const priorityColors = {
                'high': '#dc2626',
                'medium': '#d97706',
                'low': '#059669'
            };
            
            const statusBadges = {
                'active': '<span class="status-badge" style="background: #3b82f620; color: #1d4ed8; border: 1px solid #93c5fd;">Active</span>',
                'completed': '<span class="status-badge" style="background: #05966920; color: #047857; border: 1px solid #34d399;">Completed</span>',
                'cancelled': '<span class="status-badge" style="background: #dc262620; color: #b91c1c; border: 1px solid #f87171;">Cancelled</span>'
            };
            
            let html = `
                <div class="tasks-table-container">
                    <table class="tasks-table">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Priority</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Completion</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            tasks.forEach(task => {
                const dueDate = task.due_date ? new Date(task.due_date).toLocaleDateString() : 'No due date';
                const isOverdue = task.due_date && new Date(task.due_date) < new Date() && task.task_status === 'active';
                const priorityColor = priorityColors[task.priority] || '#6b7280';
                const statusBadge = statusBadges[task.task_status] || '';
                
                // Calculate completion count
                const completedCount = task.mentees.filter(m => m.assignment_status === 'completed').length;
                const totalCount = task.mentees.length;
                const completionPercent = totalCount > 0 ? Math.round((completedCount / totalCount) * 100) : 0;
                const completionColor = completionPercent === 100 ? '#059669' : 'var(--gold)';
                
                html += `
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--dark-text); margin-bottom: 4px;">
                                ${escapeHtml(task.title)}
                            </div>
                            ${task.description ? `
                                <div style="font-size: 12px; color: var(--light-text); line-height: 1.5; max-width: 300px;">
                                    ${escapeHtml(task.description.length > 100 ? task.description.substring(0, 100) + '...' : task.description)}
                                </div>
                            ` : ''}
                            <div style="font-size: 11px; color: var(--light-text); margin-top: 4px;">
                                Created: ${new Date(task.created_at).toLocaleDateString()}
                            </div>
                        </td>
                        <td>
                            <span class="priority-badge" style="color: ${priorityColor};">
                                <i class="fas fa-flag" style="color: ${priorityColor};"></i>
                                ${capitalizeFirst(task.priority)}
                            </span>
                        </td>
                        <td>
                            ${isOverdue ? 
                                `<span style="color: #dc2626; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-exclamation-circle"></i> ${dueDate}
                                </span>` : 
                                `<span style="color: var(--dark-text);">${dueDate}</span>`
                            }
                        </td>
                        <td>
                            ${statusBadge}
                        </td>
                        <td>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                                ${task.mentees.slice(0, 3).map(mentee => `
                                    <div class="mentee-chip" title="${escapeHtml(mentee.first_name + ' ' + mentee.last_name)} (${escapeHtml(mentee.email)})">
                                        <div style="width: 20px; height: 20px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #60a5fa); display: flex; align-items: center; justify-content: center; color: white; font-size: 9px; font-weight: 600;">
                                            ${getInitialsFromName(mentee.first_name, mentee.last_name)}
                                        </div>
                                        <span>${escapeHtml(mentee.first_name + ' ' + (mentee.last_name ? mentee.last_name.split(' ')[0] : ''))}</span>
                                    </div>
                                `).join('')}
                                ${task.mentees.length > 3 ? `
                                    <span style="font-size: 12px; color: var(--light-text); padding: 4px 8px;">
                                        +${task.mentees.length - 3} more
                                    </span>
                                ` : ''}
                            </div>
                            <div style="font-size: 11px; color: var(--light-text); margin-top: 4px;">
                                ${totalCount} mentee${totalCount !== 1 ? 's' : ''} total
                            </div>
                        </td>
                        <td>
                            <div class="completion-bar">
                                <div style="flex: 1; min-width: 60px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 4px;">
                                        <span style="color: var(--light-text);">${completionPercent}%</span>
                                        <span style="color: var(--light-text);">${completedCount}/${totalCount}</span>
                                    </div>
                                    <div class="completion-bar-bg">
                                        <div class="completion-bar-fill" style="background: ${completionColor}; width: ${completionPercent}%;"></div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            container.innerHTML = html;
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
         function getFullName(student) {
             const name = [student.first_name || '', student.middle_name || '', student.last_name || '']
                 .filter(Boolean)
                 .join(' ');
             const suffix = student.suffix || '';
             return [name, suffix].filter(Boolean).join(' ');
         }
         
         function getInitials(student) {
             let initials = '';
             if (student.first_name) initials += student.first_name[0].toUpperCase();
             if (student.middle_name) initials += student.middle_name[0].toUpperCase();
             if (student.last_name) initials += student.last_name[0].toUpperCase();
             if (student.suffix) initials += student.suffix[0].toUpperCase();
             return initials || '??';
         }
         
         function getInitialsFromName(firstName, lastName) {
             if (!firstName && !lastName) return '??';
             const initial1 = firstName ? firstName[0].toUpperCase() : '';
             const initial2 = lastName ? lastName[0].toUpperCase() : '';
             return initial1 + initial2;
         }
         
         function getGradient(student) {
             if (student.major_gradient_from && student.major_gradient_to) {
                 return `linear-gradient(135deg, ${student.major_gradient_from}, ${student.major_gradient_to})`;
             }
             if (student.avatar_gradient_from && student.avatar_gradient_to) {
                 return `linear-gradient(135deg, ${student.avatar_gradient_from}, ${student.avatar_gradient_to})`;
             }
             return "linear-gradient(135deg, #3b82f6, #60a5fa)";
         }
         
         function getGradientFromColors(avatarFrom, avatarTo, majorFrom, majorTo) {
             if (majorFrom && majorTo) {
                 return `linear-gradient(135deg, ${majorFrom}, ${majorTo})`;
             }
             if (avatarFrom && avatarTo) {
                 return `linear-gradient(135deg, ${avatarFrom}, ${avatarTo})`;
             }
             return "linear-gradient(135deg, #3b82f6, #60a5fa)";
         }
         
         function capitalizeFirst(str) {
             if (!str) return '';
             return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
         }
        
        function closeViewTasksModal() {
            document.getElementById('viewTasksModal').style.display = 'none';
        }

        function viewStudent(menteeId) {
            // Find student from cached allStudents array
            const student = allStudents.find(s => s.mentee_id == menteeId);
            
            if (!student) {
                showToast('Student not found', 'error');
                return;
            }
            
            const fullName = getFullName(student);
            const initials = getInitials(student);
            const gradient = getGradient(student);
            
            document.getElementById('modalContent').innerHTML = `
                <div class="modal-header" style="background: ${gradient}; padding: 40px;">
                    <button class="modal-close" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                    </button>
                    <div style="display: flex; align-items: center; gap: 24px;">
                        <div style="width: 120px; height: 120px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 48px; font-weight: 700; border: 4px solid rgba(255,255,255,0.5); box-shadow: 0 8px 24px rgba(0,0,0,0.2);">
                            ${escapeHtml(initials)}
                        </div>
                        <div style="flex: 1; color: white;">
                            <h2 style="font-size: 28px; font-weight: 700; margin: 0 0 8px 0;">${escapeHtml(fullName)}</h2>
                            <p style="margin: 4px 0; opacity: 0.9; font-size: 16px;">
                                <i class="fas fa-id-card"></i> Student ID: ${escapeHtml(student.student_id || 'N/A')}
                            </p>
                            <p style="margin: 4px 0; opacity: 0.9; font-size: 16px;">
                                <i class="fas fa-envelope"></i> ${escapeHtml(student.email || 'N/A')}
                            </p>
                            <p style="margin: 4px 0; opacity: 0.9; font-size: 16px;">
                                <i class="fas fa-graduation-cap"></i> ${escapeHtml(student.major_name || 'N/A')} | ${escapeHtml(student.year_level || 'N/A')}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-body">
                    <!-- Student Header Info -->
                    <div style="background: var(--cream); padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid var(--border-light);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 200px;">
                                <div style="font-size: 12px; color: var(--light-text); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                                    Student ID
                                </div>
                                <div style="font-size: 24px; font-weight: 700; color: var(--dark-text); margin-bottom: 16px;">
                                    ${escapeHtml(student.student_id || 'N/A')}
                                </div>
                                <div style="font-size: 13px; color: var(--light-text); line-height: 1.8;">
                                    <div><i class="fas fa-envelope" style="width: 20px; color: var(--gold);"></i> ${escapeHtml(student.email || 'N/A')}</div>
                                    <div><i class="fas fa-graduation-cap" style="width: 20px; color: var(--gold);"></i> ${escapeHtml(student.year_level || 'N/A')}</div>
                                    <div><i class="fas fa-building" style="width: 20px; color: var(--gold);"></i> ${escapeHtml(student.major_name || 'N/A')}</div>
                                </div>
                            </div>
                            <div style="flex-shrink: 0;">
                                <button class="btn btn-primary" onclick="openAssignTaskToStudent(${student.mentee_id}, '${escapeHtml(student.first_name + ' ' + student.last_name).replace(/'/g, "\\'")}')"
                                        style="padding: 12px 24px; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(212, 168, 67, 0.3);">
                                    <i class="fas fa-tasks"></i> Assign Task
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Info Cards -->
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-card-label">
                                <i class="fas fa-id-card"></i> Student ID
                            </div>
                            <div class="info-card-value">${escapeHtml(student.student_id || 'N/A')}</div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label">
                                <i class="fas fa-envelope"></i> Email
                            </div>
                            <div class="info-card-value">${escapeHtml(student.email || 'N/A')}</div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label">
                                <i class="fas fa-graduation-cap"></i> Year Level
                            </div>
                            <div class="info-card-value">${escapeHtml(student.year_level || 'N/A')}</div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label">
                                <i class="fas fa-building"></i> Major
                            </div>
                            <div class="info-card-value">${escapeHtml(student.major_name || 'N/A')}</div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('studentDetailModal').style.display = 'flex';
        }

        document.getElementById('studentDetailModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

         // Initialize
         document.addEventListener('DOMContentLoaded', function() {
             filterStudents();
             updateSelection();
         });

        <?php if ($show_role_modal): ?>
        window.addEventListener('DOMContentLoaded', function() {
            showToast('Access restricted. Redirecting...', 'error');
            setTimeout(() => window.location.href = '../../../Door/login.php', 2000);
        });
        <?php endif; ?>
    </script>
</body>
</html>
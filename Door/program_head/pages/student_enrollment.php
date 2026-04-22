<?php
require_once '../../../data/session_security.php';

$role_access = check_role_access('program_head');
$show_role_modal = !$role_access['allowed'];

$user_name = $_SESSION['user_name'] ?? 'Program Head';

// Initialize variables to avoid undefined variable errors
$majors = [];
$students_by_year = [
    '1st Year' => [],
    '2nd Year' => [],
    '3rd Year' => [],
    '4th Year' => []
];
$total_students = 0;

if (!$show_role_modal) {
    require_once '../../../data/config.php';
    
    if ($pdo) {
        try {
            // Fetch majors
            $stmt = $pdo->query("SELECT id, major_name, display_name FROM majors WHERE is_active = 1 ORDER BY sort_order");
            $majors = $stmt->fetchAll();
            
            // Fetch all students with major info
            $stmt = $pdo->query("SELECT s.*, m.display_name as major_display, m.major_name 
                                 FROM students s 
                                 LEFT JOIN majors m ON s.major_id = m.id 
                                 ORDER BY s.year_level, s.last_name, s.first_name");
            $all_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total_students = count($all_students);
            
            // Group students by year level
            foreach ($all_students as $student) {
                $year_level = $student['year_level'] ?? '';
                if (isset($students_by_year[$year_level])) {
                    $students_by_year[$year_level][] = $student;
                }
            }
        } catch (PDOException $e) {
            $majors = [];
            $students_by_year = [
                '1st Year' => [],
                '2nd Year' => [],
                '3rd Year' => [],
                '4th Year' => []
            ];
            $total_students = 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Student Enrollment - Program Head</title>
    <link rel="icon" href="../../../media/LOGO.jpg" type="image/jpeg">
    <link rel="stylesheet" href="../../../css/common.css">
    <link rel="stylesheet" href="../style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ============================================
           STUDENT ENROLLMENT - ENHANCED STYLES
           ============================================ */
        :root {
            --gold: #B8860B;
            --gold-light: #D4A843;
            --gold-dark: #8B6914;
            --gold-glow: rgba(184, 134, 11, 0.15);
            --cream: #f7f5ef;
            --white: #ffffff;
            --dark-text: #1f1f1f;
            --dark-text-2: #2d3748;
            --light-text: #666666;
            --border-light: #d4cfc5;
            --border-soft: #e8e4da;
            --success: #059669;
            --success-bg: rgba(22, 163, 74, 0.08);
            --danger: #dc2626;
            --danger-bg: rgba(220, 38, 38, 0.08);
            --blue: #3b82f6;
            --purple: #7c3aed;
            --teal: #0d9488;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 20px rgba(184, 134, 11, 0.08);
            --shadow-lg: 0 8px 32px rgba(184, 134, 11, 0.15);
            --shadow-xl: 0 20px 60px rgba(0, 0, 0, 0.12);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --transition-fast: 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-normal: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Page Header */
        .page-header-section {
            padding: 28px 0 20px;
            margin-bottom: 28px;
            position: relative;
        }

        .page-header-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border-light), transparent);
        }

        .page-header-section h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--dark-text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 14px;
            letter-spacing: -0.3px;
        }

        .page-header-section h1 i {
            color: var(--gold);
            font-size: 24px;
            background: var(--gold-glow);
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
        }

        .page-header-section p {
            font-size: 14px;
            color: var(--light-text);
            margin: 6px 0 0 58px;
            font-weight: 400;
        }

        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: alertSlideIn 0.4s var(--transition-normal);
            backdrop-filter: blur(8px);
        }

        @keyframes alertSlideIn {
            from { opacity: 0; transform: translateY(-12px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success);
            border: 1px solid rgba(22, 163, 74, 0.2);
        }

        .alert-error {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid rgba(220, 38, 38, 0.2);
        }

        /* Cards */
        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            margin-bottom: 24px;
            transition: all var(--transition-normal);
            overflow: hidden;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            border-color: rgba(184, 134, 11, 0.2);
        }

        .card-header-custom {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--cream) 0%, rgba(212, 168, 67, 0.04) 100%);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .card-title-custom {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark-text-2);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title-custom i {
            color: var(--gold);
        }

        .card-body-custom {
            padding: 24px;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 0;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
            letter-spacing: 0.2px;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-light);
            border-radius: var(--radius-sm);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            color: var(--dark-text);
            background: var(--white);
            transition: all var(--transition-fast);
            box-sizing: border-box;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--gold-light);
            background: rgba(247, 245, 239, 0.5);
            box-shadow: 0 0 0 4px rgba(212, 168, 67, 0.1);
            transform: translateY(-1px);
        }

        .form-input:hover, .form-select:hover {
            border-color: var(--gold-light);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border-light);
        }

        /* Buttons */
        .btn-custom {
            padding: 12px 24px;
            border-radius: var(--radius-sm);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all var(--transition-fast);
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .btn-custom::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.2), transparent);
            opacity: 0;
            transition: opacity var(--transition-fast);
        }

        .btn-custom:hover::after {
            opacity: 1;
        }

        .btn-secondary-custom {
            background: var(--cream);
            color: var(--dark-text);
            border: 1px solid var(--border-light);
        }

        .btn-secondary-custom:hover {
            background: var(--border-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: white;
            box-shadow: 0 4px 14px rgba(184, 134, 11, 0.3);
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, var(--gold-dark), var(--gold));
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(184, 134, 11, 0.4);
        }

        .btn-danger-custom {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);
        }

        .btn-danger-custom:hover {
            background: linear-gradient(135deg, #b91c1c, #dc2626);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(220, 38, 38, 0.4);
        }

        /* Stats Row - Enhanced */
        .stat-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-item {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 24px;
            border: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 18px;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            opacity: 0;
            transition: opacity var(--transition-normal);
        }

        .stat-item:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(184, 134, 11, 0.2);
        }

        .stat-item:hover::before {
            opacity: 1;
        }

        .stat-icon-box {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
            transition: transform var(--transition-normal);
        }

        .stat-item:hover .stat-icon-box {
            transform: scale(1.1) rotate(-5deg);
        }

        .stat-icon-box.gold { background: rgba(212, 168, 67, 0.12); color: var(--gold); }
        .stat-icon-box.blue { background: rgba(59, 130, 246, 0.12); color: var(--blue); }
        .stat-icon-box.purple { background: rgba(124, 58, 237, 0.12); color: var(--purple); }
        .stat-icon-box.teal { background: rgba(13, 148, 136, 0.12); color: var(--teal); }

        .stat-number {
            font-size: 28px;
            font-weight: 800;
            color: var(--dark-text);
            line-height: 1;
            letter-spacing: -0.5px;
        }

        .stat-label-custom {
            font-size: 12px;
            color: var(--light-text);
            font-weight: 600;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Filter Section */
        .filter-section {
            background: var(--white);
            border-radius: var(--radius-md);
            padding: 18px 24px;
            margin-bottom: 24px;
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            transition: all var(--transition-normal);
        }

        .filter-section:hover {
            box-shadow: var(--shadow-md);
        }

        .filter-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark-text);
        }

        .filter-label i {
            color: var(--gold);
        }

        .filter-select {
            padding: 10px 16px;
            border: 2px solid var(--border-light);
            border-radius: var(--radius-sm);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            color: var(--dark-text);
            background: var(--white);
            cursor: pointer;
            min-width: 220px;
            transition: all var(--transition-fast);
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--gold-light);
            box-shadow: 0 0 0 3px rgba(212, 168, 67, 0.12);
        }

        /* ============================================
           SEARCH BAR - TYPEAHEAD ENHANCED
           ============================================ */
        .search-section {
            margin-bottom: 28px;
            position: relative;
            max-width: 100%;
            z-index: 100;
        }

        .filter-section .search-section {
            margin-bottom: 0;
            flex: 1;
            margin-top: 0;
        }

        .search-wrapper {
            position: relative;
        }

        .search-container {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .search-icon {
            position: absolute;
            left: 20px;
            font-size: 18px;
            color: var(--gold);
            z-index: 2;
            transition: all var(--transition-fast);
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: 12px 44px 12px 44px;
            border: 2px solid var(--border-light);
            border-radius: var(--radius-sm);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            color: var(--dark-text);
            background: var(--white);
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-sm);
        }

        .filter-section .search-input {
            padding: 10px 44px 10px 44px;
            font-size: 14px;
        }

        .filter-section .search-icon {
            left: 14px;
            font-size: 16px;
        }

        .filter-section .search-clear {
            right: 10px;
            width: 30px;
            height: 30px;
            font-size: 12px;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(212, 168, 67, 0.12), 0 8px 24px rgba(184, 134, 11, 0.1);
            background: var(--white);
        }

        .search-input:focus ~ .search-icon {
            color: var(--gold-dark);
            transform: scale(1.1);
        }

        .search-input::placeholder {
            color: #999;
            font-weight: 400;
        }

        .search-clear {
            position: absolute;
            right: 16px;
            width: 34px;
            height: 34px;
            border: none;
            background: var(--cream);
            border-radius: 50%;
            color: var(--light-text);
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            transition: all var(--transition-fast);
            z-index: 2;
        }

        .search-clear.visible {
            display: flex;
        }

        .search-clear:hover {
            background: var(--danger);
            color: white;
            transform: rotate(90deg) scale(1.1);
        }

        /* Typeahead Dropdown */
        .typeahead-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            max-height: 440px;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px) scale(0.98);
            transition: all var(--transition-normal);
        }

        .typeahead-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .typeahead-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-light);
            background: linear-gradient(135deg, var(--cream), rgba(212, 168, 67, 0.04));
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .typeahead-header-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--light-text);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .typeahead-header-count {
            font-size: 11px;
            font-weight: 700;
            color: var(--gold-dark);
            background: rgba(212, 168, 67, 0.12);
            padding: 3px 10px;
            border-radius: 12px;
        }

        .typeahead-list {
            max-height: 380px;
            overflow-y: auto;
            scroll-behavior: smooth;
        }

        .typeahead-list::-webkit-scrollbar {
            width: 6px;
        }

        .typeahead-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .typeahead-list::-webkit-scrollbar-thumb {
            background: var(--border-light);
            border-radius: 3px;
        }

        .typeahead-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 20px;
            border-bottom: 1px solid rgba(212, 196, 165, 0.3);
            cursor: pointer;
            transition: all var(--transition-fast);
            position: relative;
        }

        .typeahead-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--gold);
            opacity: 0;
            transition: opacity var(--transition-fast);
            border-radius: 0 3px 3px 0;
        }

        .typeahead-item:last-child {
            border-bottom: none;
        }

        .typeahead-item:hover,
        .typeahead-item.highlighted {
            background: linear-gradient(135deg, rgba(212, 168, 67, 0.04), rgba(212, 168, 67, 0.08));
            padding-left: 24px;
        }

        .typeahead-item:hover::before,
        .typeahead-item.highlighted::before {
            opacity: 1;
        }

        .typeahead-avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 15px;
            flex-shrink: 0;
            box-shadow: 0 3px 10px rgba(184, 134, 11, 0.25);
            transition: transform var(--transition-fast);
        }

        .typeahead-item:hover .typeahead-avatar {
            transform: scale(1.08);
        }

        .typeahead-info {
            flex: 1;
            min-width: 0;
        }

        .typeahead-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .typeahead-meta {
            font-size: 12px;
            color: var(--light-text);
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        .typeahead-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .typeahead-meta i {
            font-size: 10px;
            color: var(--gold);
        }

        .typeahead-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(212, 168, 67, 0.1);
            color: var(--gold-dark);
            white-space: nowrap;
        }

        .typeahead-no-results {
            padding: 48px 20px;
            text-align: center;
            color: var(--light-text);
        }

        .typeahead-no-results i {
            font-size: 40px;
            margin-bottom: 14px;
            opacity: 0.3;
            color: var(--gold-light);
        }

        .typeahead-no-results p {
            font-size: 14px;
            font-weight: 600;
            margin: 0 0 16px;
        }

        .typeahead-add-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            box-shadow: 0 3px 10px rgba(184, 134, 11, 0.25);
        }

        .typeahead-add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(184, 134, 11, 0.35);
        }

        /* Search Highlight */
        .highlight {
            background: linear-gradient(135deg, rgba(212, 168, 67, 0.25), rgba(212, 168, 67, 0.15));
            padding: 1px 3px;
            border-radius: 3px;
            font-weight: 700;
            color: var(--gold-dark);
        }

        /* History Section */
        .typeahead-history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-light);
            background: var(--cream);
        }

        .typeahead-history-header span {
            font-size: 11px;
            font-weight: 700;
            color: var(--light-text);
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .clear-history-btn {
            background: none;
            border: none;
            color: var(--gold-dark);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            padding: 4px 10px;
            border-radius: 6px;
            transition: all var(--transition-fast);
        }

        .clear-history-btn:hover {
            background: var(--gold);
            color: white;
        }

        .typeahead-history-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 20px;
            border-bottom: 1px solid rgba(212, 196, 165, 0.3);
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .typeahead-history-item:hover {
            background: rgba(212, 168, 67, 0.04);
            padding-left: 24px;
        }

        .typeahead-history-item i.history-icon {
            color: var(--gold);
            font-size: 14px;
            opacity: 0.7;
        }

        .typeahead-history-item span {
            flex: 1;
            font-size: 14px;
            font-weight: 500;
            color: var(--dark-text);
        }

        .remove-history-btn {
            width: 26px;
            height: 26px;
            border: none;
            background: transparent;
            color: var(--light-text);
            cursor: pointer;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            transition: all var(--transition-fast);
        }

        .remove-history-btn:hover {
            background: var(--danger);
            color: white;
            transform: scale(1.1);
        }

        /* ============================================
           STUDENT INFO PANEL - ENHANCED
           ============================================ */
        .student-info-panel {
            margin-top: 20px;
            margin-bottom: 28px;
            opacity: 0;
            transform: translateY(20px) scale(0.97);
            transition: all var(--transition-slow);
            pointer-events: none;
        }

        .student-info-panel.visible {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

         .student-info-card-inline {
             background: var(--white);
             border-radius: var(--radius-xl);
             box-shadow: var(--shadow-lg);
             border: 1px solid var(--border-light);
             overflow: hidden;
             max-width: 600px;
             margin: 0 auto;
             transition: all var(--transition-normal);
         }

        .student-info-card-inline:hover {
            box-shadow: var(--shadow-xl);
        }

        .student-info-header {
            background: linear-gradient(135deg, var(--gold-dark) 0%, var(--gold) 50%, var(--gold-light) 100%);
            padding: 28px 28px;
            display: flex;
            align-items: center;
            gap: 18px;
            position: relative;
            overflow: hidden;
        }

        .student-info-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
        }

        .student-info-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: 10%;
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 50%;
        }

        .student-avatar-large {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold-dark);
            font-size: 26px;
            font-weight: 800;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
            border: 3px solid rgba(255, 255, 255, 0.8);
            position: relative;
            z-index: 1;
        }

        .student-info-title {
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .student-info-title h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: white;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .student-info-title span {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
        }

        .student-info-close {
            width: 38px;
            height: 38px;
            border: none;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            color: var(--gold-dark);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all var(--transition-fast);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: absolute;
            top: 18px;
            right: 18px;
            z-index: 2;
        }

        .student-info-close:hover {
            background: white;
            transform: rotate(90deg) scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .student-info-body {
            padding: 28px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0;
        }

        .info-row {
            display: flex;
            flex-direction: column;
            padding: 14px 16px;
            border-bottom: 1px solid rgba(212, 196, 165, 0.3);
            border-right: 1px solid rgba(212, 196, 165, 0.3);
            transition: background var(--transition-fast);
        }

        .info-row:hover {
            background: rgba(212, 168, 67, 0.03);
        }

        .info-row:nth-child(even) {
            border-right: none;
        }

        .info-row:nth-last-child(-n+2) {
            border-bottom: none;
        }

        .info-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--light-text);
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .info-label i {
            color: var(--gold);
            font-size: 12px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--dark-text);
        }

        /* ============================================
           YEAR LEVEL CARDS - ENHANCED
           ============================================ */
         .year-level-section {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
             gap: 24px;
             margin-top: 28px;
         }

        .year-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            overflow: hidden;
            transition: all var(--transition-normal);
        }

        .year-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(184, 134, 11, 0.2);
        }

        .year-card-header {
            padding: 18px 24px;
            background: linear-gradient(135deg, var(--gold-dark), var(--gold), var(--gold-light));
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .year-card-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .year-card-title {
            font-size: 16px;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .year-card-count {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
            backdrop-filter: blur(4px);
            position: relative;
            z-index: 1;
        }

        .year-card-body {
            padding: 16px;
            max-height: 480px;
            overflow-y: auto;
        }

        .year-card-body::-webkit-scrollbar {
            width: 5px;
        }

        .year-card-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .year-card-body::-webkit-scrollbar-thumb {
            background: var(--border-light);
            border-radius: 3px;
        }

        /* Student Row - Enhanced */
        .student-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: var(--cream);
            border-radius: var(--radius-md);
            margin-bottom: 10px;
            border: 1px solid transparent;
            transition: all var(--transition-fast);
            cursor: pointer;
        }

        .student-row:last-child {
            margin-bottom: 0;
        }

        .student-row:hover {
            background: var(--white);
            border-color: var(--gold-light);
            transform: translateX(4px);
            box-shadow: 0 4px 14px rgba(184, 134, 11, 0.1);
        }

        .student-row:active {
            transform: translateX(2px) scale(0.99);
        }

        .student-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(184, 134, 11, 0.2);
            transition: transform var(--transition-fast);
        }

        .student-row:hover .student-avatar {
            transform: scale(1.08);
        }

        .student-details {
            flex: 1;
            min-width: 0;
        }

        .student-fullname {
            font-size: 14px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 5px;
        }

        .student-meta-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 12px;
            color: var(--light-text);
            flex-wrap: wrap;
        }

        .student-meta-info span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .student-meta-info i {
            font-size: 10px;
            color: var(--gold);
        }

        .student-id-tag {
            background: rgba(212, 168, 67, 0.12);
            color: var(--gold-dark);
            padding: 3px 9px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }

        .empty-state-box {
            text-align: center;
            padding: 48px 20px;
            color: var(--light-text);
        }

        .empty-state-box i {
            font-size: 40px;
            margin-bottom: 14px;
            opacity: 0.3;
            color: var(--gold-light);
        }

        .empty-state-box p {
            font-size: 14px;
            font-weight: 500;
            margin: 0;
        }

        /* ============================================
           MODAL STYLES - ENHANCED
           ============================================ */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(6px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 11000;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-dialog {
            background: white;
            border-radius: var(--radius-xl);
            padding: 36px;
            max-width: 380px;
            width: 90%;
            text-align: center;
            animation: modalPop 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.75) translateY(-40px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-icon-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 20px;
        }

        .modal-title-text {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 12px;
        }

        .modal-desc {
            font-size: 14px;
            color: var(--light-text);
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .modal-btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .modal-btn {
            padding: 12px 24px;
            border-radius: var(--radius-sm);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all var(--transition-fast);
        }

        .modal-btn-cancel {
            background: var(--cream);
            color: var(--dark-text);
        }

        .modal-btn-cancel:hover {
            background: var(--border-light);
        }

        .modal-btn-confirm {
            background: var(--danger);
            color: white;
        }

        .modal-btn-confirm:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }

        /* Student Info Modal Card */
         .student-info-card {
             background: var(--white);
             border-radius: var(--radius-xl);
             box-shadow: var(--shadow-xl);
             position: fixed;
             top: 50%;
             left: 50%;
             transform: translate(-50%, -50%);
             width: 90%;
             max-width: 880px;
             max-height: 90vh;
             overflow-y: auto;
             z-index: 10000;
             border: 1px solid var(--border-light);
         }

        .student-info-card::-webkit-scrollbar {
            width: 6px;
        }

        .student-info-card::-webkit-scrollbar-thumb {
            background: var(--border-light);
            border-radius: 3px;
        }

        /* Floating Action Button */
        .floating-action-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: white;
            border: none;
            padding: 16px 28px;
            border-radius: 32px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 8px 28px rgba(184, 134, 11, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            transition: all var(--transition-normal);
        }

        .floating-action-btn:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 12px 36px rgba(184, 134, 11, 0.5);
        }

        .floating-action-btn:active {
            transform: translateY(-2px) scale(0.98);
        }

        .floating-action-btn i {
            transition: transform var(--transition-fast);
        }

        .floating-action-btn:hover i {
            transform: rotate(90deg);
        }

        /* Tab Navigation */
        .tabs-nav {
            display: flex;
            gap: 4px;
            padding: 8px 8px 0;
            margin-bottom: 0;
            border-bottom: 2px solid var(--border-light);
            background: var(--cream);
        }

        .tab-btn-custom {
            padding: 14px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: var(--light-text);
            cursor: pointer;
            transition: all var(--transition-fast);
            margin-bottom: -2px;
            border-radius: var(--radius-sm) var(--radius-sm) 0 0;
        }

        .tab-btn-custom:hover {
            color: var(--gold-dark);
            background: rgba(212, 168, 67, 0.06);
        }

        .tab-btn-custom.active {
            color: var(--gold-dark);
            border-bottom-color: var(--gold);
            background: white;
        }

        .tab-btn-custom i {
            margin-right: 8px;
        }

        .tab-pane {
            display: none;
            padding: 24px;
        }

        .tab-pane.active {
            display: block;
            animation: tabFadeIn 0.3s ease;
        }

        @keyframes tabFadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Import Section */
        .import-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .import-card {
            background: var(--white);
            border: 2px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 28px 20px;
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .import-card:hover {
            border-color: var(--gold-light);
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(184, 134, 11, 0.12);
        }

        .import-card.selected {
            border-color: var(--gold);
            background: rgba(212, 168, 67, 0.06);
            box-shadow: 0 0 0 3px rgba(212, 168, 67, 0.1);
        }

        .import-card i {
            font-size: 36px;
            color: var(--gold);
            margin-bottom: 12px;
        }

        .import-card h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 6px;
        }

        .import-card p {
            font-size: 13px;
            color: var(--light-text);
            margin: 0;
        }

        .import-details {
            background: var(--cream);
            border-radius: var(--radius-md);
            padding: 24px;
            border: 2px dashed var(--border-light);
        }

        .import-details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border-light);
        }

        .import-details-header span {
            font-size: 16px;
            font-weight: 700;
            color: var(--gold-dark);
        }

        .file-upload-box {
            position: relative;
            margin-bottom: 16px;
        }

        .file-input-field {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        .file-upload-area {
            text-align: center;
            padding: 44px 20px;
            border: 2px dashed var(--border-light);
            border-radius: var(--radius-sm);
            background: var(--white);
            transition: all var(--transition-fast);
        }

        .file-input-field:hover + .file-upload-area,
        .file-upload-area:hover {
            border-color: var(--gold-light);
            background: rgba(212, 168, 67, 0.04);
        }

        .file-upload-area i {
            font-size: 44px;
            color: var(--gold);
            margin-bottom: 12px;
        }

        .file-upload-area p {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark-text);
            margin: 0 0 4px;
        }

        .file-upload-area small {
            font-size: 12px;
            color: var(--light-text);
        }

        /* Edit Tab Search */
        .edit-search-wrapper {
            margin-bottom: 24px;
        }

        .edit-search-input-wrapper {
            position: relative;
        }

        .edit-search-input-wrapper i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: var(--gold);
        }

        .edit-search-input-wrapper input {
            width: 100%;
            padding: 14px 20px 14px 52px;
            border: 2px solid var(--border-light);
            border-radius: var(--radius-md);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            color: var(--dark-text);
            background: var(--white);
            transition: all var(--transition-fast);
            box-sizing: border-box;
        }

        .edit-search-input-wrapper input:focus {
            outline: none;
            border-color: var(--gold-light);
            box-shadow: 0 0 0 4px rgba(212, 168, 67, 0.1);
        }

        .edit-search-output {
            background: white;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            max-height: 360px;
            overflow-y: auto;
            margin-top: 8px;
            box-shadow: var(--shadow-md);
            display: none;
        }

        .edit-search-output.show {
            display: block;
            animation: tabFadeIn 0.2s ease;
        }

        .search-result-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px;
            border-bottom: 1px solid rgba(212, 196, 165, 0.3);
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .search-result-row:hover {
            background: var(--cream);
            padding-left: 24px;
        }

        .search-result-row:last-child {
            border-bottom: none;
        }

        .search-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .search-info {
            flex: 1;
        }

        .search-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 4px;
        }

        .search-meta {
            font-size: 12px;
            color: var(--light-text);
            display: flex;
            gap: 12px;
        }

        .search-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .search-meta i {
            font-size: 10px;
            color: var(--gold);
        }

        .search-edit-btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .search-edit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(184, 134, 11, 0.3);
        }

        .no-match {
            text-align: center;
            padding: 40px 20px;
            color: var(--light-text);
        }

        .no-match i {
            font-size: 36px;
            margin-bottom: 12px;
            opacity: 0.3;
            color: var(--gold-light);
        }

        .required-mark {
            color: var(--danger);
        }

        /* ============================================
           RESPONSIVE DESIGN
           ============================================ */
        @media (max-width: 992px) {
            .form-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .year-level-section {
                grid-template-columns: 1fr;
            }
            
            .stat-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-row {
                border-right: none !important;
            }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-custom {
                width: 100%;
                justify-content: center;
            }
            
            .stat-row {
                grid-template-columns: 1fr;
            }
            
            .tabs-nav {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }
            
            .tabs-nav::-webkit-scrollbar {
                display: none;
            }
            
            .tab-btn-custom {
                white-space: nowrap;
                padding: 12px 16px;
                font-size: 13px;
            }

            .import-grid {
                grid-template-columns: 1fr;
            }

            .student-info-card-inline {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .page-header-section h1 {
                font-size: 20px;
            }

            .search-input {
                padding: 14px 48px 14px 48px;
                font-size: 14px;
            }
            
            .year-card-body {
                max-height: 350px;
            }
            
            .student-row {
                padding: 12px;
            }
            
            .floating-action-btn {
                bottom: 20px;
                right: 20px;
                left: 20px;
                width: calc(100% - 40px);
                justify-content: center;
            }
        }

        @media (hover: none) and (pointer: coarse) {
            .btn-custom:hover,
            .student-row:hover,
            .year-card:hover,
            .stat-item:hover,
            .import-card:hover {
                transform: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }

        .student-view-modal {
            max-width: 500px;
        }

        .student-view-modal .student-info-header {
            background: linear-gradient(135deg, var(--gold-dark) 0%, var(--gold) 50%, var(--gold-light) 100%);
            padding: 28px 28px;
            display: flex;
            align-items: center;
            gap: 18px;
            position: relative;
            overflow: hidden;
        }

        .student-view-modal .student-info-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
        }

        .student-view-modal .student-info-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: 10%;
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 50%;
        }

        .student-view-modal .modal-icon-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.95);
            color: var(--gold-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
            border: 3px solid rgba(255, 255, 255, 0.8);
            position: relative;
            z-index: 1;
        }

        .student-view-modal .modal-student-title {
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .student-view-modal .modal-student-title h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: white;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .student-view-modal .modal-student-title span {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
        }

        .student-view-modal .modal-close-btn {
            width: 38px;
            height: 38px;
            border: none;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            color: var(--gold-dark);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all var(--transition-fast);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: absolute;
            top: 18px;
            right: 18px;
            z-index: 2;
        }

        .student-view-modal .modal-close-btn:hover {
            background: white;
            transform: rotate(90deg) scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .student-view-modal .student-info-body {
            padding: 28px;
        }

        .student-view-modal .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0;
        }

        .student-view-modal .info-row {
            display: flex;
            flex-direction: column;
            padding: 14px 16px;
            border-bottom: 1px solid rgba(212, 196, 165, 0.3);
            border-right: 1px solid rgba(212, 196, 165, 0.3);
            transition: background var(--transition-fast);
        }

        .student-view-modal .info-row:hover {
            background: rgba(212, 168, 67, 0.03);
        }

        .student-view-modal .info-row:nth-child(even) {
            border-right: none;
        }

        .student-view-modal .info-row:nth-last-child(-n+2) {
            border-bottom: none;
        }

        .student-view-modal .info-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--light-text);
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .student-view-modal .info-label i {
            color: var(--gold);
            font-size: 12px;
        }

        .student-view-modal .info-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--dark-text);
        }

        .student-view-modal .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            padding-top: 20px;
            border-top: 1px solid var(--border-light);
            margin-top: 20px;
        }
        .typeahead-loading {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .shimmer-line {
            height: 52px;
            background: linear-gradient(90deg, var(--cream) 25%, rgba(212, 168, 67, 0.08) 50%, var(--cream) 75%);
            background-size: 200% 100%;
            border-radius: var(--radius-sm);
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body class="dashboard-page">
    <?php if ($show_role_modal): ?>
    <div class="modal-overlay active" id="roleAccessModal">
        <div class="modal-dialog">
            <div class="modal-icon-circle">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="modal-title-text">Access Restricted</div>
            <div class="modal-desc">
                You do not have permission to access this page. Please contact your administrator.
            </div>
            <div class="modal-btn-group">
                <a href="../dashboard.php" class="modal-btn" style="background: var(--gold); color: white; text-decoration: none;">
                    Go to Dashboard
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$show_role_modal): ?>
    <!-- Student Information Modal -->
    <div class="modal-overlay" id="studentInfoCard">
        <div class="student-info-card">
            <div class="card">
                <div class="card-header-custom">
                    <h3 class="card-title-custom"><i class="fas fa-user-graduate"></i> Student Information</h3>
                    <button type="button" class="btn-custom btn-secondary-custom" style="padding: 8px 12px;" onclick="toggleStudentInfo()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="tabs-nav">
                    <button type="button" class="tab-btn-custom active" data-tab="manual" onclick="showTabContent('manual')">
                        <i class="fas fa-keyboard"></i> Manual Add
                    </button>
                    <button type="button" class="tab-btn-custom" data-tab="import" onclick="showTabContent('import')">
                        <i class="fas fa-file-import"></i> Import
                    </button>
                    <button type="button" class="tab-btn-custom" data-tab="edit" onclick="showTabContent('edit')">
                        <i class="fas fa-pen-to-square"></i> Edit
                    </button>
                </div>

                <!-- Manual Entry Tab -->
                <div id="manual-tab" class="tab-pane active">
                    <form method="POST" action="../../data/student_manage.php?action=add_student">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">First Name <span class="required-mark">*</span></label>
                                <input type="text" class="form-input" name="first_name" placeholder="Enter first name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-input" name="middle_name" placeholder="Enter middle name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Name <span class="required-mark">*</span></label>
                                <input type="text" class="form-input" name="last_name" placeholder="Enter last name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Suffix</label>
                                <select class="form-select" name="suffix">
                                    <option value="">None</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                    <option value="IV">IV</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Student ID <span class="required-mark">*</span></label>
                                <input type="text" class="form-input" name="student_id" placeholder="e.g., STU-001" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email <span class="required-mark">*</span></label>
                                <input type="email" class="form-input" name="email" placeholder="student@edu" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Major <span class="required-mark">*</span></label>
                                <select class="form-select" name="major_id" required>
                                    <option value="">Select Major</option>
                                    <?php foreach ($majors as $major): ?>
                                    <option value="<?php echo $major['id']; ?>"><?php echo htmlspecialchars($major['display_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Year Level <span class="required-mark">*</span></label>
                                <select class="form-select" name="year_level" required>
                                    <option value="">Select Year</option>
                                    <option value="1st Year">1st Year</option>
                                    <option value="2nd Year">2nd Year</option>
                                    <option value="3rd Year">3rd Year</option>
                                    <option value="4th Year">4th Year</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-custom btn-primary-custom">
                                <i class="fas fa-user-plus"></i> Add Student
                            </button>
                            <button type="reset" class="btn-custom btn-secondary-custom">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Import Tab -->
                <div id="import-tab" class="tab-pane">
                    <div class="import-grid">
                        <div class="import-card" data-format="csv">
                            <i class="fas fa-file-csv"></i>
                            <h4>CSV</h4>
                            <p>Import from CSV file</p>
                        </div>
                        <div class="import-card" data-format="excel">
                            <i class="fas fa-file-excel"></i>
                            <h4>Excel</h4>
                            <p>Import from Excel file</p>
                        </div>
                        <div class="import-card" data-format="word">
                            <i class="fas fa-file-word"></i>
                            <h4>Word</h4>
                            <p>Import from Word file</p>
                        </div>
                    </div>

                    <div class="import-details" id="importDetails" style="display: none;">
                        <div class="import-details-header">
                            <span id="formatName">CSV</span>
                            <button type="button" class="btn-custom btn-secondary-custom" id="closeFormat" style="padding: 6px 10px; font-size: 12px;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <form method="POST" action="../../data/student_manage.php?action=import_students" enctype="multipart/form-data" id="importForm">
                            <div class="form-group">
                                <label class="form-label">Upload File <span class="required-mark">*</span></label>
                                <div class="file-upload-box">
                                    <input type="file" class="file-input-field" name="import_file" id="importFile" required>
                                    <div class="file-upload-area" id="uploadArea">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Click to browse or drag file</p>
                                        <small>Allowed formats: CSV, Excel, Word</small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-custom btn-primary-custom">
                                    <i class="fas fa-upload"></i> Import Students
                                </button>
                                <button type="button" class="btn-custom btn-secondary-custom" id="cancelImport">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Edit Tab -->
                <div id="edit-tab" class="tab-pane">
                    <div class="edit-search-wrapper">
                        <div class="edit-search-input-wrapper">
                            <i class="fas fa-user-graduate"></i>
                            <input type="text" id="editStudentSearch" placeholder="Search by Student ID, Name, or Email...">
                        </div>
                        <div id="editSearchResults" class="edit-search-output"></div>
                    </div>

                    <div id="editFormSection" class="card" style="display: none;">
                        <div class="card-header-custom">
                            <h3 class="card-title-custom"><i class="fas fa-user-edit"></i> Edit Student</h3>
                            <button type="button" class="btn-custom btn-secondary-custom" id="closeEditForm" style="padding: 8px 12px;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="card-body-custom">
                            <form method="POST" action="../../data/student_manage.php?action=update_student" id="editStudentForm">
                                <input type="hidden" name="id" id="edit_student_id_hidden">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label">First Name <span class="required-mark">*</span></label>
                                        <input type="text" class="form-input" name="first_name" id="edit_first_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" class="form-input" name="middle_name" id="edit_middle_name">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Last Name <span class="required-mark">*</span></label>
                                        <input type="text" class="form-input" name="last_name" id="edit_last_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Suffix</label>
                                        <select class="form-select" name="suffix" id="edit_suffix">
                                            <option value="">None</option>
                                            <option value="Jr.">Jr.</option>
                                            <option value="Sr.">Sr.</option>
                                            <option value="II">II</option>
                                            <option value="III">III</option>
                                            <option value="IV">IV</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Student ID <span class="required-mark">*</span></label>
                                        <input type="text" class="form-input" name="student_id" id="edit_student_id_field" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Email <span class="required-mark">*</span></label>
                                        <input type="email" class="form-input" name="email" id="edit_email" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Major <span class="required-mark">*</span></label>
                                        <select class="form-select" name="major_id" id="edit_major_id" required>
                                            <option value="">Select Major</option>
                                            <?php foreach ($majors as $major): ?>
                                            <option value="<?php echo $major['id']; ?>"><?php echo htmlspecialchars($major['display_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Year Level <span class="required-mark">*</span></label>
                                        <select class="form-select" name="year_level" id="edit_year_level" required>
                                            <option value="">Select Year</option>
                                            <option value="1st Year">1st Year</option>
                                            <option value="2nd Year">2nd Year</option>
                                            <option value="3rd Year">3rd Year</option>
                                            <option value="4th Year">4th Year</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn-custom btn-primary-custom">
                                        <i class="fas fa-save"></i> Update Student
                                    </button>
                                    <button type="button" class="btn-custom btn-danger-custom" id="deleteStudentBtn">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    <button type="reset" class="btn-custom btn-secondary-custom">
                                        <i class="fas fa-redo"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../../../media/LOGO.jpg" alt="Logo" class="sidebar-logo">
            <div class="sidebar-brand">
                <span class="sidebar-brand-name">IBM</span>
            </div>
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
            <a href="../dashboard.php" class="sidebar-nav-item"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a>
            <a href="instructors.php" class="sidebar-nav-item"><i class="fas fa-chalkboard-teacher"></i><span>Instructors</span></a>
            <a href="student_enrollment.php" class="sidebar-nav-item active"><i class="fas fa-user-graduate"></i><span>Enrollment</span></a>
            <a href="mentee_flow.php" class="sidebar-nav-item"><i class="fas fa-users"></i><span>MenteeFlow</span></a>
            <a href="departments.php" class="sidebar-nav-item"><i class="fas fa-building"></i><span>Departments</span></a>
            <a href="reports.php" class="sidebar-nav-item"><i class="fas fa-file-alt"></i><span>Reports</span></a>
            <a href="settings.php" class="sidebar-nav-item"><i class="fas fa-cog"></i><span>Settings</span></a>
        </nav>
    </div>

    <div class="main-content">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.06; pointer-events: none; z-index: 0;"></div>
        
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div>
                    <div class="topbar-title">Student Enrollment</div>
                    <div class="topbar-subtitle">Program Head Panel</div>
                </div>
            </div>
            <div class="topbar-right">
                <div class="topbar-date"><i class="fas fa-calendar-alt"></i><span><?php echo date('F j, Y'); ?></span></div>
                <a href="../../../data/logout.php" class="topbar-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </header>

        <main class="dashboard-content">
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" id="alert-<?php echo uniqid(); ?>">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($_GET['success']); ?></span>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error" id="alert-<?php echo uniqid(); ?>">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($_GET['error']); ?></span>
            </div>
            <?php endif; ?>

            <div class="page-header-section">
                <h1><i class="fas fa-user-graduate"></i> Student Enrollment</h1>
                <p>Register and manage student accounts in the system</p>
            </div>

            <?php if (!$show_role_modal): ?>
            <!-- Stats Row -->
            <div class="stat-row">
                <div class="stat-item">
                    <div class="stat-icon-box gold">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo $total_students; ?></div>
                        <div class="stat-label-custom">Total Students</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon-box blue">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo count($majors); ?></div>
                        <div class="stat-label-custom">Active Programs</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon-box purple">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo count($students_by_year['1st Year']); ?></div>
                        <div class="stat-label-custom">New Enrollees</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon-box teal">
                        <i class="fas fa-award"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo count($students_by_year['4th Year']); ?></div>
                        <div class="stat-label-custom">Graduating</div>
                    </div>
                </div>
            </div>

            <!-- Filter and Search Section -->
            <?php if (isset($majors) && count($majors) > 0): ?>
            <div class="filter-section">
                <div class="filter-label">
                    <i class="fas fa-filter"></i>
                    <span>Filter by Major:</span>
                </div>
                <select id="majorFilter" class="filter-select" onchange="updateYearCounts()">
                    <option value="">All Programs</option>
                    <?php foreach ($majors as $major): ?>
                    <option value="<?php echo $major['id']; ?>"><?php echo htmlspecialchars($major['display_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="search-section" style="flex: 1; margin-top: 0; margin-left: 16px;">
                    <div class="search-wrapper">
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="studentSearchInput" placeholder="Search students by name, ID, or email..." class="search-input" autocomplete="off" spellcheck="false">
                            <button class="search-clear" id="clearSearch" tabindex="-1">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div id="typeaheadDropdown" class="typeahead-dropdown">
                            <div class="typeahead-header" id="typeaheadHeader" style="display: none;">
                                <span class="typeahead-header-title">Search Results</span>
                                <span class="typeahead-header-count" id="typeaheadCount"></span>
                            </div>
                            <div id="typeaheadList" class="typeahead-list"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Search Bar with Typeahead (when no majors) -->
            <div class="search-section" style="margin-bottom: 28px;">
                <div class="search-wrapper">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="studentSearchInput" placeholder="Search students by name, ID, or email..." class="search-input" autocomplete="off" spellcheck="false">
                        <button class="search-clear" id="clearSearch" tabindex="-1">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="typeaheadDropdown" class="typeahead-dropdown">
                        <div class="typeahead-header" id="typeaheadHeader" style="display: none;">
                            <span class="typeahead-header-title">Search Results</span>
                            <span class="typeahead-header-count" id="typeaheadCount"></span>
                        </div>
                        <div id="typeaheadList" class="typeahead-list"></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Student Info Display Container (Hidden - using modal instead) -->
            <div id="studentInfoPanel" class="student-info-panel" style="display: none;">
                <div class="student-info-card-inline">
                    <div class="student-info-header">
                        <div class="student-avatar-large" id="infoAvatar">--</div>
                        <div class="student-info-title">
                            <h3 id="infoName">Student Name</h3>
                            <span><i class="fas fa-id-badge"></i> <span id="infoStudentId">STU-000</span></span>
                        </div>
                        <button class="student-info-close" onclick="closeStudentPanel()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="student-info-body">
                        <div class="info-grid">
                            <div class="info-row">
                                <div class="info-label"><i class="fas fa-id-card"></i> Student ID</div>
                                <div class="info-value" id="infoIdValue">STU-000</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="fas fa-user"></i> First Name</div>
                                <div class="info-value" id="infoFirstName">--</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="fas fa-user"></i> Middle Name</div>
                                <div class="info-value" id="infoMiddleName">--</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="fas fa-user"></i> Last Name</div>
                                <div class="info-value" id="infoLastName">--</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="fas fa-user-tag"></i> Suffix</div>
                                <div class="info-value" id="infoSuffix">--</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                                <div class="info-value" id="infoEmail">--</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="fas fa-book"></i> Major</div>
                                <div class="info-value" id="infoMajor">--</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="fas fa-calendar-alt"></i> Year Level</div>
                                <div class="info-value" id="infoYearLevel">--</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Year Level Cards -->
            <div class="year-level-section">
                <?php 
                $year_order = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
                $year_icons = ['fas fa-seedling', 'fas fa-leaf', 'fas fa-tree', 'fas fa-crown'];
                foreach ($year_order as $index => $year): 
                    $students = $students_by_year[$year] ?? [];
                ?>
                <div class="year-card" data-year="<?php echo $year; ?>">
                    <div class="year-card-header">
                        <div class="year-card-title">
                            <i class="<?php echo $year_icons[$index]; ?>"></i>
                            <?php echo htmlspecialchars($year); ?>
                        </div>
                        <div class="year-card-count"><?php echo count($students); ?></div>
                    </div>
                    <div class="year-card-body">
                        <?php if (empty($students)): ?>
                        <div class="empty-state-box">
                            <i class="fas fa-user-slash"></i>
                            <p>No students enrolled</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($students as $student): 
                                $initials = strtoupper(substr($student['first_name'] ?? '', 0, 1) . substr($student['last_name'] ?? '', 0, 1));
                            ?>
                            <div class="student-row" data-major-id="<?php echo $student['major_id'] ?? ''; ?>" data-student-id="<?php echo $student['id'] ?? ''; ?>">
                                <div class="student-avatar"><?php echo $initials ?: 'N/A'; ?></div>
                                <div class="student-details">
                                    <div class="student-fullname"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                    <div class="student-meta-info">
                                        <span class="student-id-tag"><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></span>
                                        <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></span>
                                        <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($student['major_display'] ?? ($student['major_name'] ?? 'N/A')); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Floating Action Button -->
    <button id="showStudentInfoBtn" class="floating-action-btn" onclick="toggleStudentInfo()">
        <i class="fas fa-plus"></i> Add Student
    </button>

    <!-- Student View Modal Popup -->
    <div class="modal-overlay" id="studentViewModal">
        <div class="student-info-card student-view-modal">
            <div class="student-info-header">
                <div class="modal-icon-circle" id="viewAvatar">--</div>
                <div class="modal-student-title">
                    <h3 id="viewName">Student Name</h3>
                    <span><i class="fas fa-id-badge"></i> <span id="viewStudentId">STU-000</span></span>
                </div>
                <button class="modal-close-btn" onclick="closeStudentViewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="student-info-body">
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-id-card"></i> Student ID</div>
                        <div class="info-value" id="viewIdValue">STU-000</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-user"></i> First Name</div>
                        <div class="info-value" id="viewFirstName">--</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-user"></i> Middle Name</div>
                        <div class="info-value" id="viewMiddleName">--</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-user"></i> Last Name</div>
                        <div class="info-value" id="viewLastName">--</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-user-tag"></i> Suffix</div>
                        <div class="info-value" id="viewSuffix">--</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                        <div class="info-value" id="viewEmail">--</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-book"></i> Major</div>
                        <div class="info-value" id="viewMajor">--</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-calendar-alt"></i> Year Level</div>
                        <div class="info-value" id="viewYearLevel">--</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // CORE FUNCTIONS
        // ============================================

        // Toggle Student Information Modal
        function toggleStudentInfo() {
            const modal = document.getElementById('studentInfoCard');
            const floatBtn = document.getElementById('showStudentInfoBtn');
            
            if (modal.classList.contains('active')) {
                modal.classList.remove('active');
                floatBtn.style.display = 'flex';
                document.body.style.overflow = '';
            } else {
                modal.classList.add('active');
                floatBtn.style.display = 'none';
                document.body.style.overflow = 'hidden';
            }
        }

        // Tab functionality
        function showTabContent(tabName) {
            document.querySelectorAll('.tab-btn-custom').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            
            document.querySelector('.tab-btn-custom[data-tab="' + tabName + '"]').classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }

        // Close modal when clicking outside
        document.getElementById('studentInfoCard')?.addEventListener('click', function(e) {
            if (e.target === this) {
                toggleStudentInfo();
            }
        });

        // ============================================
        // ALERTS AUTO-DISMISS
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });

            // Initialize floating button visibility
            const modal = document.getElementById('studentInfoCard');
            const floatBtn = document.getElementById('showStudentInfoBtn');
            if (modal && floatBtn) {
                modal.classList.remove('active');
                floatBtn.style.display = 'flex';
            }

            // Add click handlers to student rows in year cards
            document.querySelectorAll('.year-card .student-row').forEach(row => {
                row.addEventListener('click', function() {
                    const studentId = this.dataset.studentId;
                    if (studentId) {
                        viewStudentDetails(studentId);
                    }
                });
            });
        });

        // ============================================
        // IMPORT FUNCTIONALITY
        // ============================================
        const importCards = document.querySelectorAll('.import-card');
        const importDetails = document.getElementById('importDetails');
        const formatName = document.getElementById('formatName');
        const fileInput = document.getElementById('importFile');
        const uploadArea = document.getElementById('uploadArea');
        const closeFormatBtn = document.getElementById('closeFormat');
        const cancelImportBtn = document.getElementById('cancelImport');

        importCards.forEach(card => {
            card.addEventListener('click', () => {
                importCards.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                
                const format = card.getAttribute('data-format');
                formatName.textContent = format.charAt(0).toUpperCase() + format.slice(1) + ' File';
                
                if (format === 'csv') {
                    fileInput.accept = '.csv';
                } else if (format === 'excel') {
                    fileInput.accept = '.xlsx,.xls';
                } else if (format === 'word') {
                    fileInput.accept = '.docx';
                }
                
                importDetails.style.display = 'block';
            });
        });

        closeFormatBtn?.addEventListener('click', () => {
            importCards.forEach(c => c.classList.remove('selected'));
            importDetails.style.display = 'none';
            fileInput.value = '';
        });

        cancelImportBtn?.addEventListener('click', () => {
            importCards.forEach(c => c.classList.remove('selected'));
            importDetails.style.display = 'none';
            fileInput.value = '';
        });

        fileInput?.addEventListener('change', function() {
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                uploadArea.innerHTML = `
                    <i class="fas fa-file-check" style="color: var(--success);"></i>
                    <p style="color: var(--success); font-weight: 700;">${fileName}</p>
                    <small>Click to change file</small>
                `;
            }
        });

        // ============================================
        // MAJOR FILTER
        // ============================================
        function updateYearCounts() {
            const majorId = document.getElementById('majorFilter').value;
            const yearCards = document.querySelectorAll('.year-card');
            
            yearCards.forEach(card => {
                let visibleCount = 0;
                const students = card.querySelectorAll('.student-row');
                students.forEach(student => {
                    const studentMajorId = student.getAttribute('data-major-id');
                    if (majorId === '' || studentMajorId == majorId) {
                        student.style.display = 'flex';
                        student.style.animation = 'tabFadeIn 0.3s ease';
                        visibleCount++;
                    } else {
                        student.style.display = 'none';
                    }
                });
                const countEl = card.querySelector('.year-card-count');
                if (countEl) countEl.textContent = visibleCount;

                // Show/hide empty state
                const emptyState = card.querySelector('.empty-state-box');
                if (visibleCount === 0 && !emptyState) {
                    const body = card.querySelector('.year-card-body');
                    const existingEmpty = body.querySelector('.dynamic-empty');
                    if (!existingEmpty) {
                        const emptyDiv = document.createElement('div');
                        emptyDiv.className = 'empty-state-box dynamic-empty';
                        emptyDiv.innerHTML = '<i class="fas fa-filter"></i><p>No students in this filter</p>';
                        body.appendChild(emptyDiv);
                    }
                } else {
                    const dynamicEmpty = card.querySelector('.dynamic-empty');
                    if (dynamicEmpty) dynamicEmpty.remove();
                }
            });
        }

        // ============================================
        // EDIT TAB SEARCH
        // ============================================
        const editSearchInput = document.getElementById('editStudentSearch');
        const editSearchResults = document.getElementById('editSearchResults');
        const editFormSection = document.getElementById('editFormSection');
        const closeEditFormBtn = document.getElementById('closeEditForm');
        const deleteStudentBtn = document.getElementById('deleteStudentBtn');
        let currentEditStudentId = null;

        editSearchInput?.addEventListener('input', function() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(searchStudentsForEdit, 300);
        });

        async function searchStudentsForEdit() {
            const query = editSearchInput.value.trim();
            if (query.length < 2) {
                editSearchResults.classList.remove('show');
                return;
            }
            try {
                const response = await fetch('../../data/student_manage.php?action=search&q=' + encodeURIComponent(query));
                const data = await response.json();
                
                if (data.success && data.students.length > 0) {
                    displayEditSearchResults(data.students);
                } else {
                    showEditNoResults();
                }
            } catch (error) {
                console.error('Search error:', error);
                showEditNoResults();
            }
        }

        function displayEditSearchResults(students) {
            editSearchResults.innerHTML = students.map(student => `
                <div class="search-result-row" onclick="loadStudentForEdit(${student.id})">
                    <div class="search-avatar">${student.initials}</div>
                    <div class="search-info">
                        <div class="search-name">${escapeHtml(student.first_name + ' ' + student.last_name)}</div>
                        <div class="search-meta">
                            <span><i class="fas fa-id-card"></i> ${escapeHtml(student.student_id)}</span>
                            <span><i class="fas fa-envelope"></i> ${escapeHtml(student.email)}</span>
                        </div>
                    </div>
                    <button class="search-edit-btn"><i class="fas fa-pen"></i> Edit</button>
                </div>
            `).join('');
            editSearchResults.classList.add('show');
        }

        function showEditNoResults() {
            editSearchResults.innerHTML = `
                <div class="no-match">
                    <i class="fas fa-search"></i>
                    <p>No students found</p>
                </div>
            `;
            editSearchResults.classList.add('show');
        }

        async function loadStudentForEdit(studentId) {
            try {
                const response = await fetch('../../data/student_manage.php?action=get&id=' + studentId);
                const data = await response.json();
                
                if (data.success) {
                    const student = data.student;
                    currentEditStudentId = student.id;
                    
                    document.getElementById('edit_student_id_hidden').value = student.id;
                    document.getElementById('edit_first_name').value = student.first_name || '';
                    document.getElementById('edit_middle_name').value = student.middle_name || '';
                    document.getElementById('edit_last_name').value = student.last_name || '';
                    document.getElementById('edit_suffix').value = student.suffix || '';
                    document.getElementById('edit_student_id_field').value = student.student_id || '';
                    document.getElementById('edit_email').value = student.email || '';
                    document.getElementById('edit_major_id').value = student.major_id || '';
                    document.getElementById('edit_year_level').value = student.year_level || '';
                    
                    editSearchResults.classList.remove('show');
                    editFormSection.style.display = 'block';
                    editSearchInput.value = '';
                } else {
                    alert('Error loading student data');
                }
            } catch (error) {
                console.error('Load error:', error);
                alert('Error loading student data');
            }
        }

        closeEditFormBtn?.addEventListener('click', () => {
            editFormSection.style.display = 'none';
            editSearchResults.classList.remove('show');
            editSearchInput.value = '';
            currentEditStudentId = null;
        });

        deleteStudentBtn?.addEventListener('click', () => {
            if (currentEditStudentId && confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
                deleteStudent(currentEditStudentId);
            }
        });

        async function deleteStudent(studentId) {
            try {
                const formData = new FormData();
                formData.append('student_id', studentId);
                
                const response = await fetch('../../data/student_manage.php?action=delete_student', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Student deleted successfully');
                    editFormSection.style.display = 'none';
                    editSearchResults.classList.remove('show');
                    editSearchInput.value = '';
                    currentEditStudentId = null;
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to delete student'));
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert('Error deleting student');
            }
        }

        // ============================================
        // TYPEAHEAD SEARCH - ENHANCED
        // ============================================
        const globalSearchInput = document.getElementById('studentSearchInput');
        const typeaheadDropdown = document.getElementById('typeaheadDropdown');
        const typeaheadList = document.getElementById('typeaheadList');
        const typeaheadHeader = document.getElementById('typeaheadHeader');
        const typeaheadCount = document.getElementById('typeaheadCount');
        const clearSearchBtn = document.getElementById('clearSearch');

        let searchTimeout = null;
        let selectedIndex = -1;
        let searchHistory = JSON.parse(localStorage.getItem('studentSearchHistory') || '[]');

        // Input event
        globalSearchInput?.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const val = this.value.trim();
            
            // Show/hide clear button
            if (val.length > 0) {
                clearSearchBtn.classList.add('visible');
            } else {
                clearSearchBtn.classList.remove('visible');
            }

            selectedIndex = -1;

            if (val.length === 0) {
                if (searchHistory.length > 0) {
                    showSearchHistory();
                } else {
                    hideTypeahead();
                }
                return;
            }

            if (val.length < 2) {
                showTypeaheadMessage('Type at least 2 characters to search...');
                return;
            }

            // Show loading
            showTypeaheadLoading();
            searchTimeout = setTimeout(() => performTypeaheadSearch(val), 180);
        });

        // Focus event
        globalSearchInput?.addEventListener('focus', function() {
            const val = this.value.trim();
            if (val.length >= 2) {
                performTypeaheadSearch(val);
            } else if (val.length === 0 && searchHistory.length > 0) {
                showSearchHistory();
            }
        });

        // Keyboard navigation
        globalSearchInput?.addEventListener('keydown', function(e) {
            const items = typeaheadList.querySelectorAll('.typeahead-item, .typeahead-history-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                highlightItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                highlightItem(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    items[selectedIndex].click();
                }
            } else if (e.key === 'Escape') {
                hideTypeahead();
                globalSearchInput.blur();
            }
        });

        function highlightItem(items) {
            items.forEach((item, i) => {
                if (i === selectedIndex) {
                    item.classList.add('highlighted');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('highlighted');
                }
            });
        }

        // Perform search
        async function performTypeaheadSearch(query) {
            try {
                const response = await fetch('../../data/student_manage.php?action=search&q=' + encodeURIComponent(query));
                if (!response.ok) throw new Error('Network error');
                const data = await response.json();
                
                if (data.success && data.students.length > 0) {
                    addToSearchHistory(query);
                    renderTypeaheadResults(data.students, query);
                } else {
                    showTypeaheadNoResults();
                }
            } catch (error) {
                console.error('Search error:', error);
                showTypeaheadNoResults();
            }
        }

        // Render results
        function renderTypeaheadResults(students, query) {
            typeaheadHeader.style.display = 'flex';
            typeaheadCount.textContent = students.length + ' found';

            typeaheadList.innerHTML = students.map((student, i) => {
                const name = highlightText(student.first_name + ' ' + student.last_name, query);
                const email = highlightText(student.email || '', query);
                const major = highlightText(student.major_display || student.major_name || 'N/A', query);
                const sid = highlightText(student.student_id || '', query);

                return `
                    <div class="typeahead-item" data-student-id="${student.id}" onclick="viewStudentDetails(${student.id})" tabindex="0">
                        <div class="typeahead-avatar">${student.initials || 'NA'}</div>
                        <div class="typeahead-info">
                            <div class="typeahead-name">${name}</div>
                            <div class="typeahead-meta">
                                <span><i class="fas fa-id-card"></i> ${sid}</span>
                                <span><i class="fas fa-envelope"></i> ${email}</span>
                                <span><i class="fas fa-book"></i> ${major}</span>
                            </div>
                        </div>
                        <span class="typeahead-badge">${student.year_level || ''}</span>
                    </div>
                `;
            }).join('');

            showTypeahead();
        }

        // Highlight matching text
        function highlightText(text, query) {
            if (!text || !query) return text || '';
            const regex = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            return text.replace(regex, '<span class="highlight">$1</span>');
        }

        // Show/hide typeahead
        function showTypeahead() {
            typeaheadDropdown.classList.add('active');
        }

        function hideTypeahead() {
            typeaheadDropdown.classList.remove('active');
            selectedIndex = -1;
        }

        function showTypeaheadMessage(msg) {
            typeaheadHeader.style.display = 'none';
            typeaheadList.innerHTML = `
                <div class="typeahead-no-results">
                    <i class="fas fa-info-circle"></i>
                    <p>${msg}</p>
                </div>
            `;
            showTypeahead();
        }

        function showTypeaheadLoading() {
            typeaheadHeader.style.display = 'none';
            typeaheadList.innerHTML = `
                <div class="typeahead-loading">
                    <div class="shimmer-line"></div>
                    <div class="shimmer-line"></div>
                    <div class="shimmer-line"></div>
                </div>
            `;
            showTypeahead();
        }

        function showTypeaheadNoResults() {
            typeaheadHeader.style.display = 'none';
            typeaheadList.innerHTML = `
                <div class="typeahead-no-results">
                    <i class="fas fa-search"></i>
                    <p>No students found matching your search</p>
                    <button class="typeahead-add-btn" onclick="toggleStudentInfo()">
                        <i class="fas fa-plus"></i> Add New Student
                    </button>
                </div>
            `;
            showTypeahead();
        }

        // Search History
        function addToSearchHistory(query) {
            searchHistory = searchHistory.filter(q => q !== query);
            searchHistory.unshift(query);
            searchHistory = searchHistory.slice(0, 5);
            localStorage.setItem('studentSearchHistory', JSON.stringify(searchHistory));
        }

        function showSearchHistory() {
            typeaheadHeader.style.display = 'none';
            typeaheadList.innerHTML = `
                <div class="typeahead-history-header">
                    <span>Recent Searches</span>
                    <button class="clear-history-btn" onclick="clearAllHistory(event)">Clear All</button>
                </div>
                ${searchHistory.map((query, i) => `
                    <div class="typeahead-history-item" onclick="useHistoryItem('${escapeHtml(query)}')" tabindex="0">
                        <i class="fas fa-history history-icon"></i>
                        <span>${escapeHtml(query)}</span>
                        <button class="remove-history-btn" onclick="removeHistoryItem(event, ${i})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `).join('')}
            `;
            showTypeahead();
        }

        function useHistoryItem(query) {
            globalSearchInput.value = query;
            clearSearchBtn.classList.add('visible');
            performTypeaheadSearch(query);
        }

        function removeHistoryItem(event, index) {
            event.stopPropagation();
            searchHistory.splice(index, 1);
            localStorage.setItem('studentSearchHistory', JSON.stringify(searchHistory));
            if (searchHistory.length === 0) {
                hideTypeahead();
            } else {
                showSearchHistory();
            }
        }

        function clearAllHistory(event) {
            event.stopPropagation();
            searchHistory = [];
            localStorage.setItem('studentSearchHistory', JSON.stringify(searchHistory));
            hideTypeahead();
        }

        // Clear search button
        clearSearchBtn?.addEventListener('click', function(e) {
            e.preventDefault();
            globalSearchInput.value = '';
            clearSearchBtn.classList.remove('visible');
            hideTypeahead();
            globalSearchInput.focus();
            selectedIndex = -1;
        });

        // Close typeahead on outside click
        document.addEventListener('click', function(e) {
            const searchSection = document.querySelector('.search-section');
            if (searchSection && !searchSection.contains(e.target)) {
                hideTypeahead();
            }
        });

        // ============================================
        // STUDENT INFO PANEL
        // ============================================
        function closeStudentPanel() {
            const infoPanel = document.getElementById('studentInfoPanel');
            infoPanel.classList.remove('visible');
        }

        async function viewStudentDetails(studentId) {
            try {
                const response = await fetch('../../data/student_manage.php?action=get&id=' + studentId);
                const data = await response.json();
                
                if (data.success) {
                    displayStudentDetails(data.student);
                } else {
                    alert('Error loading student data');
                }
            } catch (error) {
                console.error('Load error:', error);
                alert('Error loading student data');
            }
        }

        function displayStudentDetails(student) {
            // Close typeahead
            hideTypeahead();
            
            const initials = ((student.first_name?.[0] || '') + (student.last_name?.[0] || '')).toUpperCase();
            
            // Populate modal data
            document.getElementById('viewAvatar').textContent = initials || 'NA';
            document.getElementById('viewName').textContent = (student.first_name || '') + ' ' + (student.last_name || '');
            document.getElementById('viewStudentId').textContent = student.student_id || 'N/A';
            document.getElementById('viewIdValue').textContent = student.student_id || 'N/A';
            document.getElementById('viewFirstName').textContent = student.first_name || '--';
            document.getElementById('viewMiddleName').textContent = student.middle_name || '--';
            document.getElementById('viewLastName').textContent = student.last_name || '--';
            document.getElementById('viewSuffix').textContent = student.suffix || '--';
            document.getElementById('viewEmail').textContent = student.email || '--';
            document.getElementById('viewMajor').textContent = student.major_display || student.major_name || '--';
            document.getElementById('viewYearLevel').textContent = student.year_level || '--';
            
            // Show modal popup
            const modal = document.getElementById('studentViewModal');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeStudentViewModal() {
            const modal = document.getElementById('studentViewModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close modal on outside click
        document.getElementById('studentViewModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeStudentViewModal();
            }
        });

        // ============================================
        // UTILITY FUNCTIONS
        // ============================================
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const viewModal = document.getElementById('studentViewModal');
                if (viewModal && viewModal.classList.contains('active')) {
                    closeStudentViewModal();
                    return;
                }
                const modal = document.getElementById('studentInfoCard');
                if (modal && modal.classList.contains('active')) {
                    toggleStudentInfo();
                }
            }
        });
    </script>
</body>
</html>
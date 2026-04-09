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
                // Skip students with year levels not in our defined list (e.g., 5th Year)
            }
        } catch (PDOException $e) {
            // Keep default empty values on error
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
    <link rel="stylesheet" href="../../../css/common.css">
    <link rel="stylesheet" href="../style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { 
            --gold: #B8860B; 
            --gold-light: #D4A843; 
            --gold-dark: #8B6914; 
            --gold-lighter: #F5E6B8;
            --cream: #f7f5ef; 
            --cream-light: #f0ebe3; 
            --white: #ffffff; 
            --dark-text: #1f1f1f; 
            --dark-text-2: #2d3748; 
            --light-text: #666666; 
            --light-text-2: #a0aec0; 
            --border-light: #d4cfc5; 
            --border-soft: #e8e4da; 
            --success: #059669; 
            --danger: #dc2626;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif; 
            color: var(--dark-text); 
            background: var(--cream);
        }
        .page-container { 
            padding: 32px; 
            max-width: 1400px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }
        .page-header h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--dark-text);
        }
        .page-header p {
            font-size: 14px;
            color: var(--light-text);
            margin-top: 4px;
        }
        .btn {
            padding: 12px 20px;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .btn-secondary {
            background: var(--cream-light);
            color: var(--dark-text);
            border: 1px solid var(--border-light);
        }
        .btn-secondary:hover {
            background: var(--border-light);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: white;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--gold-dark), var(--gold));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(184, 134, 11, 0.3);
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(184, 134, 11, 0.12);
            border: 1px solid var(--border-soft);
            margin-bottom: 24px;
        }
        .card-header {
            padding: 20px 24px;
            border-bottom: 2px solid var(--cream-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark-text-2);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-title i { color: var(--gold); }
        
        .card-body {
            padding: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            padding: 24px;
        }
        .form-group { margin-bottom: 0; }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
        }
        .form-input, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-light);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            color: var(--dark-text);
            background: var(--cream-light);
            transition: all 0.2s ease;
        }
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--gold-light);
            background: white;
            box-shadow: 0 0 0 4px rgba(212, 168, 67, 0.15);
        }
        .form-actions {
            padding: 0 24px 24px;
            display: flex;
            gap: 12px;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin: 20px 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-success {
            background: rgba(22, 163, 74, 0.1);
            color: #16a34a;
            border: 1px solid rgba(22, 163, 74, 0.2);
        }
        .alert-error {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
            border: 1px solid rgba(220, 38, 38, 0.2);
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 20px 24px;
            border: 1px solid var(--border-soft);
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .stat-icon.blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .stat-icon.gold { background: rgba(212, 168, 67, 0.1); color: var(--gold); }
        .stat-value { font-size: 24px; font-weight: 800; color: var(--dark-text); }
        .stat-label { font-size: 13px; color: var(--light-text); }
        
        .required { color: #dc2626; }
        
        /* Year Level Containers */
        .year-levels-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
            margin-top: 32px;
        }
        
        .year-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(184, 134, 11, 0.12);
            border: 1px solid var(--border-soft);
            overflow: hidden;
        }
        
        .year-header {
            padding: 20px 24px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .year-title {
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .year-title i {
            font-size: 18px;
        }
        
        .year-count {
            background: rgba(255, 255, 255, 0.25);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
        }
        
        /* Major Filter */
        .major-filter-container {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding: 16px 20px;
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border-light);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
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
            color: var(--gold-dark);
        }
        
        .major-filter-select {
            padding: 10px 16px;
            border: 2px solid var(--border-light);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            color: var(--dark-text);
            background: var(--white);
            cursor: pointer;
            min-width: 200px;
            transition: all 0.2s ease;
        }
        
        .major-filter-select:focus {
            outline: none;
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 3px rgba(212, 168, 67, 0.15);
        }
        
        .year-content {
            padding: 20px;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .student-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: var(--cream-light);
            border-radius: 12px;
            margin-bottom: 12px;
            border: 1px solid var(--border-light);
            transition: all 0.2s ease;
        }
        
        .student-item:hover {
            background: var(--white);
            border-color: var(--gold-light);
            box-shadow: 0 4px 12px rgba(184, 134, 11, 0.15);
            transform: translateY(-2px);
        }
        
        .student-avatar-small {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--gold-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .student-info {
            flex: 1;
            min-width: 0;
        }
        
        .student-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .student-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 12px;
            color: var(--light-text);
            flex-wrap: wrap;
        }
        
        .student-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .student-id-badge {
            background: rgba(212, 168, 67, 0.15);
            color: var(--gold-dark);
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .student-major {
            color: var(--dark-text-2);
            font-weight: 500;
        }
        
        .empty-year {
            text-align: center;
            padding: 40px 20px;
            color: var(--light-text);
            font-size: 14px;
        }
        
        .empty-year i {
            font-size: 36px;
            margin-bottom: 12px;
            opacity: 0.4;
            color: var(--gold-light);
        }
        
        .no-students-message {
            text-align: center;
            padding: 60px 20px;
            color: var(--light-text);
            font-size: 15px;
            background: var(--cream-light);
            border-radius: 16px;
            margin-top: 24px;
            border: 2px dashed var(--border-light);
        }
        
        .no-students-message i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
            color: var(--gold-light);
        }
        
        /* Tabs */
        .tab-nav {
            display: flex;
            gap: 8px;
            padding: 0 24px;
            margin-bottom: 0;
            border-bottom: 2px solid var(--border-light);
            background: var(--cream-light);
        }
        
        .tab-btn {
            padding: 14px 24px;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: var(--light-text);
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: -2px;
        }
        
        .tab-btn:hover {
            color: var(--gold-dark);
            background: rgba(212, 168, 67, 0.05);
        }
        
        .tab-btn.active {
            color: var(--gold-dark);
            border-bottom-color: var(--gold-dark);
            background: white;
        }
        
        .tab-btn i {
            margin-right: 8px;
        }
        
        .tab-content {
            display: none;
            padding: 24px;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Import Container */
        .import-container {
            padding: 24px;
        }
        
        .import-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        @media (max-width: 768px) {
            .import-options {
                grid-template-columns: 1fr;
            }
        }
        
        .import-option-card {
            background: white;
            border: 2px solid var(--border-light);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .import-option-card:hover {
            border-color: var(--gold-light);
            transform: translateY(-4px);
            box-shadow: 0 6px 20px rgba(184, 134, 11, 0.15);
        }
        
        .import-option-card.selected {
            border-color: var(--gold-dark);
            background: rgba(212, 168, 67, 0.08);
        }
        
        .import-option-card i {
            font-size: 36px;
            color: var(--gold-dark);
            margin-bottom: 12px;
        }
        
        .import-option-card h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 8px;
        }
        
        .import-option-card p {
            font-size: 13px;
            color: var(--light-text);
            margin: 0;
        }
        
        .import-selected {
            background: var(--cream-light);
            border-radius: 12px;
            padding: 20px;
            border: 2px dashed var(--border-light);
        }
        
        .import-selected-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border-light);
        }
        
        .import-selected-header span {
            font-size: 16px;
            font-weight: 700;
            color: var(--gold-dark);
        }
        
        .btn-close-format {
            background: none;
            border: none;
            color: var(--light-text);
            cursor: pointer;
            font-size: 18px;
            padding: 4px 8px;
        }
        
        .btn-close-format:hover {
            color: var(--danger);
        }
        
        .file-upload-wrapper {
            position: relative;
            margin-bottom: 16px;
        }
        
        .file-input {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-upload-content {
            text-align: center;
            padding: 40px 20px;
            border: 2px dashed var(--border-light);
            border-radius: 10px;
            background: white;
            transition: all 0.2s ease;
        }
        
        .file-input:hover + .file-upload-content,
        .file-upload-content:hover {
            border-color: var(--gold-light);
            background: rgba(212, 168, 67, 0.05);
        }
        
        .file-upload-content i {
            font-size: 42px;
            color: var(--gold-dark);
            margin-bottom: 12px;
        }
        
        .file-upload-content p {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark-text);
            margin: 0 0 4px;
        }
        
        .file-upload-content small {
            font-size: 12px;
            color: var(--light-text);
        }
        
        .file-name-display {
            margin-top: 12px;
            font-size: 13px;
            color: var(--gold-dark);
            font-weight: 600;
        }
        
        /* Edit Student */
        .edit-student-container {
            padding: 24px;
        }
        
        .search-section {
            margin-bottom: 24px;
        }
        
        .search-box-large {
            position: relative;
            margin-bottom: 16px;
        }
        
        .search-box-large input {
            width: 100%;
            padding: 16px 20px 16px 56px;
            border: 2px solid var(--border-light);
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            color: var(--dark-text);
            background: var(--cream-light);
            transition: all 0.3s ease;
        }
        
        .search-box-large input:focus {
            outline: none;
            border-color: var(--gold-light);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(212, 168, 67, 0.15);
        }
        
        .search-box-large i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: var(--gold-dark);
        }
        
        .search-results {
            background: white;
            border: 1px solid var(--border-light);
            border-radius: 12px;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .search-result-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-light);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .search-result-item:hover {
            background: var(--cream-light);
        }
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        .search-result-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gold-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .search-result-info {
            flex: 1;
        }
        
        .search-result-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 4px;
        }
        
        .search-result-meta {
            font-size: 12px;
            color: var(--light-text);
            display: flex;
            gap: 12px;
        }
        
        .search-result-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .search-result-action {
            padding: 8px 16px;
            background: var(--gold-gradient);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .search-result-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(184, 134, 11, 0.3);
        }
        
        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: var(--light-text);
        }
        
        .no-results i {
            font-size: 36px;
            margin-bottom: 12px;
            opacity: 0.4;
            color: var(--gold-light);
        }
        
        .edit-form-section .card-body {
            padding: 24px;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #b91c1c, #dc2626);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(4px);
        }
        
        .modal-overlay.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.8) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .modal-icon {
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
        
        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 12px;
        }
        
        .modal-message {
            font-size: 14px;
            color: var(--light-text);
            margin-bottom: 24px;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        
        .btn-modal {
            padding: 12px 24px;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        
        .btn-modal-cancel {
            background: var(--cream-light);
            color: var(--dark-text);
        }
        
        .btn-modal-cancel:hover {
            background: var(--border-light);
        }
        
        .btn-modal-confirm {
            background: var(--danger);
            color: white;
        }
        
        .btn-modal-confirm:hover {
            background: #b91c1c;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../../../media/LOGO.jpg" alt="Logo" class="sidebar-logo" style="width: 50px; height: 50px; border-radius: 12px; object-fit: cover; border: 2px solid white; background: white; padding: 2px;">
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
            <a href="../dashboard.php" class="sidebar-nav-item"><i class="fas fa-chart-pie"></i><span>Overview</span></a>
            <a href="instructors.php" class="sidebar-nav-item"><i class="fas fa-chalkboard-teacher"></i><span>Instructors</span></a>
            <a href="student_enrollment.php" class="sidebar-nav-item active"><i class="fas fa-user-graduate"></i><span>Enrollment</span></a>
            <a href="mentee_flow.php" class="sidebar-nav-item"><i class="fas fa-users"></i><span>MenteeFlow</span></a>
            <a href="departments.php" class="sidebar-nav-item"><i class="fas fa-building"></i><span>Departments</span></a>
            <a href="reports.php" class="sidebar-nav-item"><i class="fas fa-file-alt"></i><span>Reports</span></a>
            <a href="settings.php" class="sidebar-nav-item"><i class="fas fa-cog"></i><span>Settings</span></a>
        </nav>
    </div>
    <div class="main-content" style="position: relative;">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div><div class="topbar-title">Student Enrollment</div><div class="topbar-subtitle">Program Head Panel</div></div>
            </div>
            <div class="topbar-right">
                <div class="topbar-date"><i class="fas fa-calendar-alt"></i><span><?php echo date('F j, Y'); ?></span></div>
                <a href="../../../data/logout.php" class="topbar-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </header>
        <main class="page-container">
            <div class="page-header">
                <div>
                    <h1>Student Enrollment</h1>
                    <p>Register new student accounts to the system</p>
                </div>
            </div>
            
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
             
             <script>
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
                 });
             </script>
             
             <?php if ($show_role_modal): ?>
            <div class="modal-overlay" id="roleAccessModal" style="display: flex;">
                <div class="modal-content">
                    <div class="modal-icon" style="background: rgba(239, 68, 68, 0.1); color: #dc2626;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="modal-title">Access Restricted</div>
                    <div class="modal-message">
                        You do not have permission to access this page. Please contact your administrator.
                    </div>
                    <div class="modal-actions">
                        <a href="../dashboard.php" class="btn-modal" style="background: var(--gold); color: white; text-decoration: none;">
                            Go to Dashboard
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!$show_role_modal): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-graduate"></i> Student Information</h3>
                </div>
                
                <!-- Tabs -->
                <div class="tab-nav">
                    <button type="button" class="tab-btn active" data-tab="manual">
                        <i class="fas fa-keyboard"></i> Manual Add Student
                    </button>
                    <button type="button" class="tab-btn" data-tab="import">
                        <i class="fas fa-file-import"></i> Import Students
                    </button>
                    <button type="button" class="tab-btn" data-tab="edit">
                        <i class="fas fa-search-edit"></i> Edit Student
                    </button>
                </div>
                
                <!-- Manual Entry Tab -->
                <div id="manual-tab" class="tab-content active">
                    <form method="POST" action="../../data/student_manage.php?action=add_student">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">First Name <span class="required">*</span></label>
                                <input type="text" class="form-input" name="first_name" placeholder="Enter first name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-input" name="middle_name" placeholder="Enter middle name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Name <span class="required">*</span></label>
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
                                <label class="form-label">Student ID <span class="required">*</span></label>
                                <input type="text" class="form-input" name="student_id" placeholder="e.g., STU-001" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address <span class="required">*</span></label>
                                <input type="email" class="form-input" name="email" placeholder="student@edu" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Major/Program <span class="required">*</span></label>
                                <select class="form-select" name="major_id" required>
                                    <option value="">Select Major</option>
                                    <?php foreach ($majors as $major): ?>
                                    <option value="<?php echo $major['id']; ?>"><?php echo htmlspecialchars($major['display_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Year Level <span class="required">*</span></label>
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
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Enroll Student
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Import Tab -->
                <div id="import-tab" class="tab-content">
                    <div class="import-container">
                        <div class="import-options">
                            <div class="import-option-card" data-format="csv">
                                <i class="fas fa-file-csv"></i>
                                <h4>CSV</h4>
                                <p>Import from .csv file</p>
                            </div>
                            <div class="import-option-card" data-format="excel">
                                <i class="fas fa-file-excel"></i>
                                <h4>Excel</h4>
                                <p>Import from .xlsx file</p>
                            </div>
                            <div class="import-option-card" data-format="word">
                                <i class="fas fa-file-word"></i>
                                <h4>Word</h4>
                                <p>Import from .docx file</p>
                            </div>
                        </div>
                        
                        <div class="import-selected" style="display: none;">
                            <div class="import-selected-header">
                                <span id="selected-format-name">CSV</span>
                                <button type="button" class="btn-close-format"><i class="fas fa-times"></i></button>
                            </div>
                            <form method="POST" action="../../data/student_manage.php?action=import_students" enctype="multipart/form-data" id="import-form">
                                <div class="form-group">
                                    <label class="form-label">Upload File <span class="required">*</span></label>
                                    <div class="file-upload-wrapper">
                                        <input type="file" class="file-input" name="import_file" id="import_file" accept=".csv,.xlsx,.xls,.docx" required>
                                        <div class="file-upload-content">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <p>Click to browse or drag file here</p>
                                            <small>Supported formats: CSV, Excel (.xlsx, .xls), Word (.docx)</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> Import Students
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="cancel-import">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Tab -->
                <div id="edit-tab" class="tab-content">
                    <div class="edit-student-container">
                        <!-- Search Section -->
                        <div class="search-section card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-search"></i> Search Student</h3>
                            </div>
                            <div class="card-body">
                                <div class="search-box-large">
                                    <i class="fas fa-user-graduate"></i>
                                    <input type="text" id="editStudentSearch" placeholder="Search by Student ID, Name, or Email...">
                                </div>
                                <div id="searchResults" class="search-results" style="display: none;"></div>
                            </div>
                        </div>
                        
                        <!-- Edit Form Section -->
                        <div id="editFormSection" class="edit-form-section card" style="display: none;">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-user-edit"></i> Edit Student Information</h3>
                                <button type="button" class="btn btn-secondary" id="closeEditForm">
                                    <i class="fas fa-times"></i> Close
                                </button>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="../../data/student_manage.php?action=update_student" id="editStudentForm">
                                    <input type="hidden" name="id" id="edit_student_id_hidden">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">First Name <span class="required">*</span></label>
                                            <input type="text" class="form-input" name="first_name" id="edit_first_name" placeholder="Enter first name" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Middle Name</label>
                                            <input type="text" class="form-input" name="middle_name" id="edit_middle_name" placeholder="Enter middle name">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Last Name <span class="required">*</span></label>
                                            <input type="text" class="form-input" name="last_name" id="edit_last_name" placeholder="Enter last name" required>
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
                                            <label class="form-label">Student ID <span class="required">*</span></label>
                                            <input type="text" class="form-input" name="student_id" id="edit_student_id_field" placeholder="e.g., STU-001" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Email Address <span class="required">*</span></label>
                                            <input type="email" class="form-input" name="email" id="edit_email" placeholder="student@edu" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Major/Program <span class="required">*</span></label>
                                            <select class="form-select" name="major_id" id="edit_major_id" required>
                                                <option value="">Select Major</option>
                                                <?php foreach ($majors as $major): ?>
                                                <option value="<?php echo $major['id']; ?>"><?php echo htmlspecialchars($major['display_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Year Level <span class="required">*</span></label>
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
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Student
                                        </button>
                                        <button type="button" class="btn btn-danger" id="deleteStudentBtn">
                                            <i class="fas fa-trash"></i> Delete Student
                                        </button>
                                        <button type="reset" class="btn btn-secondary">
                                            <i class="fas fa-redo"></i> Reset
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php
            $total_students = 0;
            if ($pdo) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM students");
                    $total_students = $stmt->fetchColumn();
                } catch (PDOException $e) {
                    $total_students = 0;
                }
            }
            ?>
             <div class="stats-row">
                 <div class="stat-card">
                     <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                     <div>
                         <div class="stat-value"><?php echo $total_students; ?></div>
                         <div class="stat-label">Total Students</div>
                     </div>
                 </div>
                 <div class="stat-card">
                     <div class="stat-icon gold"><i class="fas fa-graduation-cap"></i></div>
                     <div>
                         <div class="stat-value"><?php echo count($majors); ?></div>
                         <div class="stat-label">Active Majors</div>
                     </div>
                 </div>
             </div>
             
             <!-- Students by Year Level -->
             <?php if ($total_students > 0): ?>
             
             <!-- Major Filter -->
             <div class="major-filter-container">
                 <div class="filter-label">
                     <i class="fas fa-filter"></i>
                     <span>Filter by Major:</span>
                 </div>
                 <select id="majorFilter" class="major-filter-select">
                     <option value="">All Majors</option>
                     <?php foreach ($majors as $major): ?>
                     <option value="<?php echo $major['id']; ?>"><?php echo htmlspecialchars($major['display_name']); ?></option>
                     <?php endforeach; ?>
                 </select>
             </div>
             
             <div class="year-levels-container">
                 <?php 
                 $year_order = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
                 foreach ($year_order as $year): 
                     $students = $students_by_year[$year] ?? [];
                 ?>
                 <div class="year-container">
                     <div class="year-header">
                         <div class="year-title">
                             <i class="fas fa-calendar-alt"></i>
                             <?php echo htmlspecialchars($year); ?>
                         </div>
                         <div class="year-count"><?php echo count($students); ?></div>
                     </div>
                     <div class="year-content">
                         <?php if (empty($students)): ?>
                         <div class="empty-year">
                             <i class="fas fa-user-slash"></i>
                             <p>No students enrolled</p>
                         </div>
                         <?php else: ?>
                             <?php foreach ($students as $student): 
                                 $initials = strtoupper(substr($student['first_name'] ?? '', 0, 1) . substr($student['last_name'] ?? '', 0, 1));
                             ?>
                             <div class="student-item" data-major-id="<?php echo $student['major_id'] ?? ''; ?>" data-major-name="<?php echo htmlspecialchars($student['major_display'] ?? ($student['major_name'] ?? '')); ?>">
                                 <div class="student-avatar-small"><?php echo $initials ?: 'N/A'; ?></div>
                                 <div class="student-info">
                                     <div class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                     <div class="student-meta">
                                         <span class="student-id-badge"><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></span>
                                         <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></span>
                                         <span class="student-major"><i class="fas fa-book"></i> <?php echo htmlspecialchars($student['major_display'] ?? ($student['major_name'] ?? 'N/A')); ?></span>
                                     </div>
                                 </div>
                             </div>
                             <?php endforeach; ?>
                         <?php endif; ?>
                     </div>
                 </div>
                 <?php endforeach; ?>
             </div>
              <?php else: ?>
              <div class="no-students-message">
                  <i class="fas fa-users"></i>
                  <p>No students have been enrolled yet. Use the form above to add students to the system.</p>
              </div>
              <?php endif; ?>
              <?php endif; ?>
         </main>
     </div>
     
     <script>
         // Auto-dismiss alerts after 5 seconds
         const alerts = document.querySelectorAll('.alert');
         alerts.forEach(alert => {
             setTimeout(() => {
                 alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                 alert.style.opacity = '0';
                 alert.style.transform = 'translateY(-20px)';
                 setTimeout(() => alert.remove(), 500);
             }, 5000);
         });
         
         // Tab functionality
         const tabBtns = document.querySelectorAll('.tab-btn');
         const tabContents = document.querySelectorAll('.tab-content');
             
             tabBtns.forEach(btn => {
                 btn.addEventListener('click', () => {
                     const targetTab = btn.getAttribute('data-tab');
                     
                     // Remove active class from all tabs and contents
                     tabBtns.forEach(b => b.classList.remove('active'));
                     tabContents.forEach(c => c.classList.remove('active'));
                     
                     // Add active class to clicked tab and corresponding content
                     btn.classList.add('active');
                     document.getElementById(targetTab + '-tab').classList.add('active');
                 });
             });
             
             // Import format selection
             const importOptionCards = document.querySelectorAll('.import-option-card');
             const importSelected = document.querySelector('.import-selected');
             const selectedFormatName = document.getElementById('selected-format-name');
             const fileInput = document.getElementById('import_file');
             const fileUploadContent = document.querySelector('.file-upload-content');
             const btnCloseFormat = document.querySelector('.btn-close-format');
             const btnCancelImport = document.getElementById('cancel-import');
             
             if (importOptionCards.length > 0) {
                 importOptionCards.forEach(card => {
                     card.addEventListener('click', () => {
                         const format = card.getAttribute('data-format');
                         
                         // Mark selected card
                         importOptionCards.forEach(c => c.classList.remove('selected'));
                         card.classList.add('selected');
                         
                         // Update display
                         selectedFormatName.textContent = format.charAt(0).toUpperCase() + format.slice(1) + ' File';
                         
                         // Set accept attribute on file input
                         if (format === 'csv') {
                             fileInput.accept = '.csv';
                         } else if (format === 'excel') {
                             fileInput.accept = '.xlsx,.xls';
                         } else if (format === 'word') {
                             fileInput.accept = '.docx';
                         }
                         
                         // Show selected section
                         importSelected.style.display = 'block';
                     });
                 });
                 
                 // Close format selection
                 btnCloseFormat.addEventListener('click', () => {
                     importOptionCards.forEach(c => c.classList.remove('selected'));
                     importSelected.style.display = 'none';
                     fileInput.value = '';
                 });
                 
                 // Cancel import
                 btnCancelImport.addEventListener('click', () => {
                     importOptionCards.forEach(c => c.classList.remove('selected'));
                     importSelected.style.display = 'none';
                     fileInput.value = '';
                 });
                 
                 // File input change handler
                 fileInput.addEventListener('change', function() {
                     if (this.files.length > 0) {
                         const fileName = this.files[0].name;
                         fileUploadContent.innerHTML = `
                             <i class="fas fa-file-check"></i>
                             <p style="color: var(--gold-dark); font-weight: 700;">${fileName}</p>
                             <small>Click to change file</small>
                         `;
                     }
                  });
              }
              
           // Edit Student Search and Edit Functionality
         const editSearchInput = document.getElementById('editStudentSearch');
         
         // Major filter functionality
         const majorFilter = document.getElementById('majorFilter');
         if (majorFilter) {
             majorFilter.addEventListener('change', function() {
                 const selectedMajorId = this.value;
                 const studentItems = document.querySelectorAll('.student-item');
                 
                  studentItems.forEach(item => {
                      const majorId = item.getAttribute('data-major-id');
                      if (selectedMajorId === '' || majorId == selectedMajorId) {
                          item.style.display = 'flex';
                      } else {
                          item.style.display = 'none';
                      }
                  });
             });
         }
         const searchResults = document.getElementById('searchResults');
         const editFormSection = document.getElementById('editFormSection');
         const closeEditFormBtn = document.getElementById('closeEditForm');
         const editStudentForm = document.getElementById('editStudentForm');
         const deleteStudentBtn = document.getElementById('deleteStudentBtn');
         let currentEditStudentId = null;
         
         if (editSearchInput) {
             let searchTimeout;
             editSearchInput.addEventListener('input', function() {
                 clearTimeout(searchTimeout);
                 searchTimeout = setTimeout(() => {
                     searchStudents();
                 }, 300);
             });
         }
         
         async function searchStudents() {
             const query = document.getElementById('editStudentSearch').value.trim();
             if (query.length < 2) {
                 searchResults.style.display = 'none';
                 return;
             }
             try {
                  const response = await fetch('../../data/student_manage.php?action=search&q=' + encodeURIComponent(query));
                 const data = await response.json();
                 
                 if (data.success && data.students.length > 0) {
                     displaySearchResults(data.students);
                 } else {
                     showNoResults();
                 }
             } catch (error) {
                 console.error('Search error:', error);
                 showNoResults();
             }
         }
         
         function displaySearchResults(students) {
             searchResults.innerHTML = students.map(student => `
                 <div class="search-result-item" onclick="loadStudentForEdit(${student.id})">
                     <div class="search-result-avatar">${student.initials}</div>
                     <div class="search-result-info">
                         <div class="search-result-name">${escapeHtml(student.first_name + ' ' + student.last_name)}</div>
                         <div class="search-result-meta">
                             <span><i class="fas fa-id-card"></i> ${escapeHtml(student.student_id)}</span>
                             <span><i class="fas fa-envelope"></i> ${escapeHtml(student.email)}</span>
                             <span><i class="fas fa-book"></i> ${escapeHtml(student.major_display || student.major_name)}</span>
                         </div>
                     </div>
                     <button class="search-result-action">Edit</button>
                 </div>
             `).join('');
             searchResults.style.display = 'block';
         }
         
         function showNoResults() {
             searchResults.innerHTML = `
                 <div class="no-results">
                     <i class="fas fa-search"></i>
                     <p>No students found</p>
                 </div>
             `;
             searchResults.style.display = 'block';
         }
         
             async function loadStudentForEdit(studentId) {
                 try {
                      const response = await fetch('../../data/student_manage.php?action=get&id=' + studentId);
                 const data = await response.json();
                 
                 if (data.success) {
                     const student = data.student;
                     currentEditStudentId = student.id;
                     
                     // Populate form fields
                     document.getElementById('edit_student_id_hidden').value = student.id;
                     document.getElementById('edit_first_name').value = student.first_name || '';
                     document.getElementById('edit_middle_name').value = student.middle_name || '';
                     document.getElementById('edit_last_name').value = student.last_name || '';
                     document.getElementById('edit_suffix').value = student.suffix || '';
                     document.getElementById('edit_student_id_field').value = student.student_id || '';
                     document.getElementById('edit_email').value = student.email || '';
                     document.getElementById('edit_major_id').value = student.major_id || '';
                     document.getElementById('edit_year_level').value = student.year_level || '';
                     
                     // Show edit form, hide search results
                     searchResults.style.display = 'none';
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
         
         // Close edit form
         if (closeEditFormBtn) {
             closeEditFormBtn.addEventListener('click', () => {
                 editFormSection.style.display = 'none';
                 searchResults.style.display = 'none';
                 editSearchInput.value = '';
                 currentEditStudentId = null;
             });
         }
         
         // Delete student
         if (deleteStudentBtn) {
             deleteStudentBtn.addEventListener('click', () => {
                 if (currentEditStudentId) {
                     if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
                         deleteStudent(currentEditStudentId);
                     }
                 }
             });
         }
         
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
                     searchResults.style.display = 'none';
                     editSearchInput.value = '';
                     currentEditStudentId = null;
                     // Optionally reload page to update stats
                     location.reload();
                 } else {
                     alert('Error: ' + (data.message || 'Failed to delete student'));
                 }
             } catch (error) {
                 console.error('Delete error:', error);
                 alert('Error deleting student');
             }
         }
         
            // Utility function to escape HTML
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        </script>
 </body>
</html>
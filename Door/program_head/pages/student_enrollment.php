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
        /* Student Enrollment Specific Styles - Matching Dashboard Theme */
        :root {
            --gold: #B8860B;
            --gold-light: #D4A843;
            --gold-dark: #8B6914;
            --cream: #f7f5ef;
            --white: #ffffff;
            --dark-text: #1f1f1f;
            --dark-text-2: #2d3748;
            --light-text: #666666;
            --border-light: #d4cfc5;
            --border-soft: #e8e4da;
            --success: #059669;
            --danger: #dc2626;
        }

        .page-header-section {
            padding: 24px 0;
            margin-bottom: 24px;
        }

        .page-header-section h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--dark-text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header-section h1 i {
            color: var(--gold);
            font-size: 22px;
        }

        .page-header-section p {
            font-size: 13px;
            color: var(--light-text);
            margin: 4px 0 0 36px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: rgba(22, 163, 74, 0.08);
            color: #059669;
            border: 1px solid rgba(22, 163, 74, 0.2);
        }

        .alert-error {
            background: rgba(220, 38, 38, 0.08);
            color: #dc2626;
            border: 1px solid rgba(220, 38, 38, 0.2);
        }

        .card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(184, 134, 11, 0.08);
            border: 1px solid var(--border-light);
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 6px 24px rgba(184, 134, 11, 0.12);
        }

        .card-header-custom {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--cream);
            border-radius: 16px 16px 0 0;
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 0;
        }

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
            background: var(--white);
            transition: all 0.2s ease;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--gold-light);
            background: var(--cream);
            box-shadow: 0 0 0 4px rgba(212, 168, 67, 0.12);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border-light);
        }

        .btn-custom {
            padding: 12px 24px;
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

        .btn-secondary-custom {
            background: var(--cream);
            color: var(--dark-text);
            border: 1px solid var(--border-light);
        }

        .btn-secondary-custom:hover {
            background: var(--border-light);
            transform: translateY(-2px);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: white;
            box-shadow: 0 4px 12px rgba(184, 134, 11, 0.25);
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, var(--gold-dark), var(--gold));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(184, 134, 11, 0.35);
        }

        .btn-danger-custom {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
        }

        .btn-danger-custom:hover {
            background: linear-gradient(135deg, #b91c1c, #dc2626);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.35);
        }

        .student-info-card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            z-index: 10000;
            border: 1px solid var(--border-light);
        }

        .stat-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-item {
            background: linear-gradient(135deg, var(--white) 0%, var(--cream) 100%);
            border-radius: 14px;
            padding: 20px;
            border: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(184, 134, 11, 0.15);
        }

        .stat-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-icon-box.gold { background: rgba(212, 168, 67, 0.15); color: var(--gold); }
        .stat-icon-box.blue { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }

        .stat-number {
            font-size: 24px;
            font-weight: 800;
            color: var(--dark-text);
            line-height: 1;
        }

        .stat-label-custom {
            font-size: 12px;
            color: var(--light-text);
            font-weight: 600;
            margin-top: 4px;
        }

        .year-level-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        .year-card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(184, 134, 11, 0.08);
            border: 1px solid var(--border-light);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .year-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(184, 134, 11, 0.15);
        }

        .year-card-header {
            padding: 18px 24px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .year-card-title {
            font-size: 16px;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .year-card-count {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
        }

        .year-card-body {
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
        }

        .student-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: var(--cream);
            border-radius: 12px;
            margin-bottom: 12px;
            border: 1px solid var(--border-light);
            transition: all 0.2s ease;
        }

        .student-row:hover {
            background: var(--white);
            border-color: var(--gold-light);
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(184, 134, 11, 0.12);
        }

        .student-avatar {
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

        .student-details {
            flex: 1;
            min-width: 0;
        }

        .student-fullname {
            font-size: 14px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 4px;
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
            gap: 4px;
        }

        .student-id-tag {
            background: rgba(212, 168, 67, 0.15);
            color: var(--gold-dark);
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .empty-state-box {
            text-align: center;
            padding: 40px 20px;
            color: var(--light-text);
        }

        .empty-state-box i {
            font-size: 36px;
            margin-bottom: 12px;
            opacity: 0.4;
            color: var(--gold-light);
        }

        .filter-section {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid var(--border-light);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
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

        .filter-select:focus {
            outline: none;
            border-color: var(--gold-light);
            box-shadow: 0 0 0 3px rgba(212, 168, 67, 0.15);
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 11000;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-dialog {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 380px;
            width: 90%;
            text-align: center;
            animation: modalPop 0.3s ease;
        }

        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.8) translateY(-30px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-icon-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
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
        }

        .modal-btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .modal-btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
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
        }

        .floating-action-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 30px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 6px 24px rgba(212, 168, 67, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .floating-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(212, 168, 67, 0.5);
        }

        /* Search Bar Styles */
        .search-section {
            margin-bottom: 24px;
            position: relative;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .search-container {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .search-container i {
            position: absolute;
            left: 20px;
            font-size: 18px;
            color: var(--gold-dark);
            z-index: 2;
            transition: color 0.2s ease;
        }

        .search-input {
            width: 100%;
            padding: 16px 50px 16px 52px;
            border: 2px solid var(--border-light);
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            font-weight: 500;
            color: var(--dark-text);
            background: var(--white);
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--gold-light);
            box-shadow: 0 0 0 4px rgba(212, 168, 67, 0.12), 0 4px 12px rgba(0, 0, 0, 0.08);
            background: var(--cream);
        }

        .search-input:focus + i,
        .search-container:focus-within i {
            color: var(--gold-dark);
        }

        .search-input::placeholder {
            color: var(--light-text);
            font-weight: 400;
        }

        .search-clear {
            position: absolute;
            right: 12px;
            width: 32px;
            height: 32px;
            border: none;
            background: var(--cream);
            border-radius: 50%;
            color: var(--light-text);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.2s ease;
            z-index: 2;
        }

        .search-clear:hover {
            background: var(--danger);
            color: white;
            transform: rotate(90deg);
        }

        /* Search Results Container */
        .search-results-container {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: 12px;
            margin-top: 8px;
            max-height: 500px;
            overflow-y: auto;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            display: none;
        }

        .search-results-container.show {
            display: block;
            animation: slideDown 0.2s ease;
        }

        .search-results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-light);
            background: var(--cream);
            border-radius: 12px 12px 0 0;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .search-results-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--light-text);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .search-results-count {
            font-size: 11px;
            font-weight: 600;
            color: var(--gold-dark);
            background: rgba(212, 168, 67, 0.1);
            padding: 2px 8px;
            border-radius: 10px;
        }

        .search-results-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .search-result-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-light);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover,
        .search-result-item:focus {
            background: var(--cream);
            outline: none;
            padding-left: 20px;
        }

        .search-result-item:focus {
            box-shadow: inset 0 0 0 2px var(--gold-light);
        }

        .search-result-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 15px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(184, 134, 11, 0.3);
        }

        .search-result-info {
            flex: 1;
            min-width: 0;
        }

        .search-result-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .search-result-meta {
            font-size: 12px;
            color: var(--light-text);
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .search-result-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .search-result-meta i {
            font-size: 10px;
            color: var(--gold-dark);
        }

        .search-no-results {
            padding: 40px 20px;
            text-align: center;
            color: var(--light-text);
        }

        .search-no-results i {
            font-size: 36px;
            margin-bottom: 10px;
            opacity: 0.4;
            color: var(--gold-light);
        }

        .search-no-results p {
            font-size: 14px;
            font-weight: 600;
            margin: 0;
        }

        /* Search highlight */
        .search-highlight {
            background: rgba(212, 168, 67, 0.2);
            padding: 0 2px;
            border-radius: 2px;
            font-weight: 700;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Student Info Panel */
        .student-info-panel {
            margin-top: 16px;
            margin-bottom: 24px;
        }

        .student-info-card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(184, 134, 11, 0.2);
            border: 1px solid var(--border-light);
            overflow: hidden;
            animation: slideUp 0.3s ease;
            max-width: 600px;
            margin: 0 auto;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .student-info-header {
            background: linear-gradient(135deg, var(--gold-dark), var(--gold), var(--gold-light));
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
        }

        .student-avatar-large {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold-dark);
            font-size: 26px;
            font-weight: 800;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
            border: 2px solid rgba(255, 255, 255, 0.8);
        }

        .student-info-title {
            flex: 1;
        }

        .student-info-title h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: var(--white);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .student-info-title span {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        .student-info-close {
            width: 36px;
            height: 36px;
            border: none;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            color: var(--gold-dark);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            position: absolute;
            top: 16px;
            right: 16px;
        }

        .student-info-close:hover {
            background: var(--white);
            transform: rotate(90deg);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .student-info-body {
            padding: 24px;
        }

        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-light);
            align-items: center;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            width: 120px;
            font-size: 13px;
            font-weight: 600;
            color: var(--light-text);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .info-label i {
            color: var(--gold-dark);
            font-size: 14px;
        }

        .info-value {
            flex: 1;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark-text);
        }

        /* Tab Navigation */
        .tabs-nav {
            display: flex;
            gap: 8px;
            padding: 0;
            margin-bottom: 0;
            border-bottom: 2px solid var(--border-light);
            background: var(--cream);
            border-radius: 16px 16px 0 0;
        }

        .tab-btn-custom {
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

        .tab-btn-custom:hover {
            color: var(--gold-dark);
            background: rgba(212, 168, 67, 0.06);
        }

        .tab-btn-custom.active {
            color: var(--gold-dark);
            border-bottom-color: var(--gold-dark);
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
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Import Section */
        .import-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        @media (max-width: 768px) {
            .import-grid {
                grid-template-columns: 1fr;
            }
        }

        .import-card {
            background: var(--white);
            border: 2px solid var(--border-light);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .import-card:hover {
            border-color: var(--gold-light);
            transform: translateY(-4px);
            box-shadow: 0 6px 20px rgba(184, 134, 11, 0.15);
        }

        .import-card.selected {
            border-color: var(--gold-dark);
            background: rgba(212, 168, 67, 0.08);
        }

        .import-card i {
            font-size: 36px;
            color: var(--gold-dark);
            margin-bottom: 12px;
        }

        .import-card h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 8px;
        }

        .import-card p {
            font-size: 13px;
            color: var(--light-text);
            margin: 0;
        }

        .import-details {
            background: var(--cream);
            border-radius: 12px;
            padding: 20px;
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
        }

        .file-upload-area {
            text-align: center;
            padding: 40px 20px;
            border: 2px dashed var(--border-light);
            border-radius: 10px;
            background: var(--white);
            transition: all 0.2s ease;
        }

        .file-input-field:hover + .file-upload-area,
        .file-upload-area:hover {
            border-color: var(--gold-light);
            background: rgba(212, 168, 67, 0.05);
        }

        .file-upload-area i {
            font-size: 42px;
            color: var(--gold-dark);
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

        /* Search Box */
        .search-container {
            margin-bottom: 24px;
        }

        .search-input-wrapper {
            position: relative;
            margin-bottom: 16px;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: var(--gold-dark);
        }

        .search-input-wrapper input {
            width: 100%;
            padding: 16px 20px 16px 56px;
            border: 2px solid var(--border-light);
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            color: var(--dark-text);
            background: var(--white);
            transition: all 0.3s ease;
        }

        .search-input-wrapper input:focus {
            outline: none;
            border-color: var(--gold-light);
            box-shadow: 0 0 0 4px rgba(212, 168, 67, 0.15);
        }

        .search-output {
            background: white;
            border: 1px solid var(--border-light);
            border-radius: 12px;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            display: none;
        }

        .search-output.show {
            display: block;
        }

        .search-result-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-light);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .search-result-row:hover {
            background: var(--cream);
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

        .search-edit-btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
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
            opacity: 0.4;
            color: var(--gold-light);
        }

        .required-mark {
            color: var(--danger);
        }

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
            
            .year-level-section {
                gap: 16px;
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
            
            .tab-btn-custom i {
                margin-right: 6px;
            }
        }

        @media (max-width: 480px) {
            .form-label {
                font-size: 12px;
            }
            
            .form-input, .form-select {
                padding: 10px 12px;
                font-size: 13px;
            }
            
            .year-card-body {
                max-height: 350px;
            }
            
            .student-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding: 12px;
            }
            
            .student-avatar {
                width: 36px;
                height: 36px;
                font-size: 12px;
            }
            
            .student-fullname {
                font-size: 13px;
            }
            
            .student-meta-info {
                flex-direction: column;
                gap: 6px;
                align-items: flex-start;
            }
            
            .import-grid {
                gap: 12px;
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
            
            .import-card:active {
                transform: scale(0.98);
            }
            
            .student-row:active {
                background: white;
                border-color: var(--gold-light);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
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
                        <i class="fas fa-search-edit"></i> Edit
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
                    <div class="search-container">
                        <div class="search-input-wrapper">
                            <i class="fas fa-user-graduate"></i>
                            <input type="text" id="editStudentSearch" placeholder="Search by Student ID, Name, or Email...">
                        </div>
                        <div id="searchResults" class="search-output"></div>
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
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        
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
            </div>

            <!-- Major Filter -->
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
            </div>
            <?php endif; ?>

            <!-- Search Bar -->
            <div class="search-section">
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="studentSearchInput" placeholder="Search students by name, ID, or email..." class="search-input" autocomplete="off">
                    <button class="search-clear" id="clearSearch" style="display: none;" tabindex="-1">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="searchResults" class="search-results-container">
                    <div class="search-results-header" id="searchResultsHeader" style="display: none;">
                        <span class="search-results-title">Search Results</span>
                        <span class="search-results-count" id="searchResultsCount"></span>
                    </div>
                    <div id="searchResultsList" class="search-results-list"></div>
                </div>
            </div>

            <!-- Student Info Display Container -->
            <div id="studentInfoPanel" class="student-info-panel" style="display: none;">
                <div class="student-info-card">
                    <div class="student-info-header">
                        <div class="student-avatar-large" id="infoAvatar">--</div>
                        <div class="student-info-title">
                            <h3 id="infoName">Student Name</h3>
                            <span id="infoStudentId">STU-000</span>
                        </div>
                        <button class="student-info-close" onclick="closeStudentPanel()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="student-info-body">
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

            <!-- Year Level Cards -->
            <div class="year-level-section">
                <?php 
                $year_order = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
                foreach ($year_order as $year): 
                    $students = $students_by_year[$year] ?? [];
                ?>
                <div class="year-card" data-year="<?php echo $year; ?>">
                    <div class="year-card-header">
                        <div class="year-card-title">
                            <i class="fas fa-calendar-alt"></i>
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
                            <div class="student-row" data-major-id="<?php echo $student['major_id'] ?? ''; ?>">
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

    <script>
        // Toggle Student Information Modal
        function toggleStudentInfo() {
            const modal = document.getElementById('studentInfoCard');
            const floatBtn = document.getElementById('showStudentInfoBtn');
            
            if (modal.classList.contains('active')) {
                modal.classList.remove('active');
                floatBtn.style.display = 'flex';
            } else {
                modal.classList.add('active');
                floatBtn.style.display = 'none';
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

        // Auto-dismiss alerts
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
        });

        // Import format selection
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
                    <i class="fas fa-file-check" style="color: var(--gold-dark);"></i>
                    <p style="color: var(--gold-dark); font-weight: 700;">${fileName}</p>
                    <small>Click to change file</small>
                `;
            }
        });

        // Major filter functionality
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
                        visibleCount++;
                    } else {
                        student.style.display = 'none';
                    }
                });
                const countEl = card.querySelector('.year-card-count');
                if (countEl) countEl.textContent = visibleCount;
            });
        }

        // Edit student search
        const editSearchInput = document.getElementById('editStudentSearch');
        const searchResults = document.getElementById('searchResults');
        const editFormSection = document.getElementById('editFormSection');
        const closeEditFormBtn = document.getElementById('closeEditForm');
        const deleteStudentBtn = document.getElementById('deleteStudentBtn');
        let currentEditStudentId = null;

        editSearchInput?.addEventListener('input', function() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(searchStudents, 300);
        });

        async function searchStudents() {
            const query = editSearchInput.value.trim();
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
                <div class="search-result-row" onclick="loadStudentForEdit(${student.id})">
                    <div class="search-avatar">${student.initials}</div>
                    <div class="search-info">
                        <div class="search-name">${escapeHtml(student.first_name + ' ' + student.last_name)}</div>
                        <div class="search-meta">
                            <span><i class="fas fa-id-card"></i> ${escapeHtml(student.student_id)}</span>
                            <span><i class="fas fa-envelope"></i> ${escapeHtml(student.email)}</span>
                        </div>
                    </div>
                    <button class="search-edit-btn">Edit</button>
                </div>
            `).join('');
            searchResults.classList.add('show');
        }

        function showNoResults() {
            searchResults.innerHTML = `
                <div class="no-match">
                    <i class="fas fa-search"></i>
                    <p>No students found</p>
                </div>
            `;
            searchResults.classList.add('show');
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

        closeEditFormBtn?.addEventListener('click', () => {
            editFormSection.style.display = 'none';
            searchResults.style.display = 'none';
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
                    searchResults.style.display = 'none';
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

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('studentInfoCard');
                if (modal && modal.classList.contains('active')) {
                    toggleStudentInfo();
                }
            }
        });

        // =======================
        // GLOBAL SEARCH FUNCTIONALITY - ENHANCED
        // =======================
        const globalSearchInput = document.getElementById('studentSearchInput');
        const searchResultsContainer = document.getElementById('searchResults');
        const searchResultsList = document.getElementById('searchResultsList');
        const searchResultsHeader = document.getElementById('searchResultsHeader');
        const searchResultsCount = document.getElementById('searchResultsCount');
        const clearSearchBtn = document.getElementById('clearSearch');

        let searchTimeout = null;
        let currentResults = [];
        let selectedIndex = -1;

        // Search input event listeners
        globalSearchInput?.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performGlobalSearch, 300);
            
            // Show clear button
            clearSearchBtn.style.display = this.value.length > 0 ? 'flex' : 'none';
            
            // Reset selected index
            selectedIndex = -1;
        });

        globalSearchInput?.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                performGlobalSearch();
            } else if (this.value.trim().length > 0) {
                // Show message to type at least 2 chars
                showSearchMessage('Type at least 2 characters to search');
            }
        });

        // Keyboard navigation
        globalSearchInput?.addEventListener('keydown', function(e) {
            const items = searchResultsList.querySelectorAll('.search-result-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateSelectedItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, 0);
                updateSelectedItem(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    items[selectedIndex].click();
                } else if (items.length > 0) {
                    // Select first item if none selected
                    items[0].click();
                }
            } else if (e.key === 'Escape') {
                searchResultsContainer.classList.remove('show');
                clearSearchBtn.style.display = 'none';
                globalSearchInput.blur();
            }
        });

        function updateSelectedItem(items) {
            items.forEach((item, index) => {
                if (index === selectedIndex) {
                    item.focus();
                    item.style.background = 'var(--cream)';
                    item.style.paddingLeft = '20px';
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.style.background = '';
                    item.style.paddingLeft = '';
                }
            });
        }

        clearSearchBtn?.addEventListener('click', function(e) {
            e.preventDefault();
            globalSearchInput.value = '';
            searchResultsContainer.classList.remove('show');
            clearSearchBtn.style.display = 'none';
            globalSearchInput.focus();
            selectedIndex = -1;
        });

        async function performGlobalSearch() {
            const query = globalSearchInput.value.trim();
            
            if (query.length < 2) {
                if (query.length > 0) {
                    showSearchMessage('Type at least 2 characters to search');
                } else {
                    searchResultsContainer.classList.remove('show');
                }
                return;
            }
            
            try {
                const response = await fetch('../../data/student_manage.php?action=search&q=' + encodeURIComponent(query));
                const data = await response.json();
                
                if (data.success && data.students.length > 0) {
                    displayGlobalSearchResults(data.students, query);
                } else {
                    showNoGlobalResults();
                }
            } catch (error) {
                console.error('Search error:', error);
                showNoGlobalResults();
            }
        }

        function displayGlobalSearchResults(students, query) {
            // Update header count
            searchResultsCount.textContent = students.length + ' student' + (students.length !== 1 ? 's' : '');
            searchResultsHeader.style.display = 'flex';
            
            searchResultsList.innerHTML = students.map((student, index) => {
                const highlightedName = highlightMatch(student.first_name + ' ' + student.last_name, query) +
                                       ' ' +
                                       highlightMatch(student.student_id, query);
                const highlightedEmail = highlightMatch(student.email, query);
                const highlightedMajor = highlightMatch(student.major_display || student.major_name || 'N/A', query);
                const highlightedYear = student.year_level ? highlightMatch(student.year_level, query) : '';
                
                return `
                    <div class="search-result-item" tabindex="0" role="button" data-student-id="${student.id}" onclick="viewStudentDetails(${student.id})" onkeypress="if(event.key==='Enter'){event.preventDefault();viewStudentDetails(${student.id})}">
                        <div class="search-result-avatar">${student.initials}</div>
                        <div class="search-result-info">
                            <div class="search-result-name">${highlightedName}</div>
                            <div class="search-result-meta">
                                <span><i class="fas fa-envelope"></i> ${highlightedEmail}</span>
                                <span><i class="fas fa-book"></i> ${highlightedMajor}</span>
                                ${student.year_level ? `<span><i class="fas fa-calendar-alt"></i> ${highlightedYear}</span>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            searchResultsContainer.classList.add('show');
            
            // Add keyboard focus handling
            const items = searchResultsList.querySelectorAll('.search-result-item');
            items.forEach((item, index) => {
                item.addEventListener('mouseenter', () => {
                    selectedIndex = index;
                    updateSelectedItem(items);
                });
            });
        }

        function highlightMatch(text, query) {
            if (!text) return '';
            const regex = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            return text.toString().replace(regex, '<span class="search-highlight">$1</span>');
        }

        function showSearchMessage(message) {
            searchResultsHeader.style.display = 'none';
            searchResultsList.innerHTML = `
                <div class="search-no-results">
                    <i class="fas fa-info-circle"></i>
                    <p>${message}</p>
                </div>
            `;
            searchResultsContainer.classList.add('show');
        }

        function showNoGlobalResults() {
            searchResultsHeader.style.display = 'none';
            searchResultsList.innerHTML = `
                <div class="search-no-results">
                    <i class="fas fa-search"></i>
                    <p>No students found matching your search</p>
                </div>
            `;
            searchResultsContainer.classList.add('show');
        }

        function closeStudentPanel() {
            document.getElementById('studentInfoPanel').style.display = 'none';
            globalSearchInput.focus();
        }

        async function displayStudentDetails(student) {
            // Close search results
            searchResultsContainer.classList.remove('show');
            
            // Generate initials
            const initials = (student.first_name?.[0] || '') + (student.last_name?.[0] || '');
            
            // Show info panel
            const infoPanel = document.getElementById('studentInfoPanel');
            infoPanel.style.display = 'block';
            
            // Populate data
            document.getElementById('infoAvatar').textContent = initials.toUpperCase() || 'NA';
            document.getElementById('infoName').textContent = (student.first_name || '') + ' ' + (student.last_name || '');
            document.getElementById('infoStudentId').textContent = student.student_id || 'N/A';
            document.getElementById('infoIdValue').textContent = student.student_id || 'N/A';
            document.getElementById('infoFirstName').textContent = student.first_name || '--';
            document.getElementById('infoMiddleName').textContent = student.middle_name || '--';
            document.getElementById('infoLastName').textContent = student.last_name || '--';
            document.getElementById('infoSuffix').textContent = student.suffix || '--';
            document.getElementById('infoEmail').textContent = student.email || '--';
            document.getElementById('infoMajor').textContent = student.major_display || student.major_name || '--';
            document.getElementById('infoYearLevel').textContent = student.year_level || '--';
            
            // Smooth scroll to panel
            infoPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
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

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('studentInfoCard');
                if (modal && modal.classList.contains('active')) {
                    toggleStudentInfo();
                }
            }
        });
    </script>
</body>
</html>

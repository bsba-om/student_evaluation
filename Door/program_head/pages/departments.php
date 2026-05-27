<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../../data/session_security.php';

$role_access = check_role_access('program_head');
$show_role_modal = !$role_access['allowed'];

$user_name = $_SESSION['user_name'] ?? 'Program Head';

if (!$show_role_modal) {
    require_once '../../../data/config.php';
    
    $majors = [];
    $has_major_subjects = false;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'major_subjects'");
        $has_major_subjects = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $has_major_subjects = false;
    }
    
    try {
        $stmt = $pdo->query("SELECT * FROM majors ORDER BY sort_order, display_name");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $subject_count = 0;
            $student_count = 0;
            try {
                if ($has_major_subjects) {
                    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM major_subjects WHERE major_id = ?");
                    $stmt2->execute([$row['id']]);
                    $subject_count = intval($stmt2->fetchColumn());
                }
            } catch (PDOException $e) {}
            try {
                $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM students WHERE major_id = ?");
                $stmt2->execute([$row['id']]);
                $student_count = intval($stmt2->fetchColumn());
            } catch (PDOException $e) {}
            $row['subject_count'] = $subject_count;
            $row['student_count'] = $student_count;
            $majors[] = $row;
        }
    } catch (PDOException $e) {
        $majors = [];
    }
    
    $all_subjects = [];
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'subjects'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SELECT * FROM subjects WHERE is_active = 1 ORDER BY subject_name");
            $all_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $all_subjects = [];
    }
    
    $ph_settings = [];
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'program_head_settings'");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['setting_value']) {
                $ph_settings = json_decode($row['setting_value'], true) ?: [];
            }
        }
    } catch (PDOException $e) {
        $ph_settings = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Management - Program Head Dashboard</title>
    <link rel="icon" href="../../../media/LOGO.jpg" type="image/jpeg">
    <link rel="stylesheet" href="../../../css/common.css">
    <link rel="stylesheet" href="../style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --gold: #B8860B;
            --gold-light: #D4A843;
            --gold-dark: #8B6914;
            --cream: #f7f5ef;
            --white: #ffffff;
            --dark-text: #1f1f1f;
            --light-text: #666666;
            --border-light: #d4cfc5;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: var(--cream); overflow-x: hidden; }
        .page-container { padding: 24px; }
        .page-header { margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .page-title { font-size: 24px; font-weight: 700; color: var(--dark-text); }
        .page-subtitle { font-size: 13px; color: var(--light-text); margin-top: 4px; }
        .card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid var(--border-light); margin-bottom: 20px; }
        .btn-add { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: white; padding: 10px 18px; border: none; border-radius: 10px; cursor: pointer; font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease; font-family: 'Poppins', sans-serif; }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(184,134,11,0.35); }
        .major-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .major-card { background: var(--cream); border-radius: 12px; padding: 20px; border: 1px solid var(--border-light); transition: all 0.2s ease; }
        .major-card:hover { transform: translateY(-4px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
        .major-card.inactive { opacity: 0.6; }
        .major-header { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
        .major-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; flex-shrink: 0; }
        .major-info { flex: 1; min-width: 0; }
        .major-name { font-size: 16px; font-weight: 700; color: var(--dark-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .major-meta { font-size: 13px; color: var(--light-text); margin-top: 4px; }
        .major-desc { font-size: 13px; color: var(--light-text); margin: 12px 0; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .major-actions { display: flex; gap: 8px; border-top: 1px solid var(--border-light); padding-top: 12px; margin-top: 12px; }
        .btn-action { flex: 1; padding: 8px; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s; font-family: 'Poppins', sans-serif; }
        .btn-view { background: var(--white); color: var(--dark-text); border: 1px solid var(--border-light); }
        .btn-view:hover { background: var(--cream); }
        .btn-edit { background: var(--gold-light); color: white; }
        .btn-edit:hover { background: var(--gold-dark); }
        .btn-delete { background: #ef4444; color: white; }
        .btn-delete:hover { background: #dc2626; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.55); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 16px; }
        .modal-overlay.active { display: flex; }
        .modal { background: white; border-radius: 16px; padding: 24px; max-width: 700px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid var(--cream); }
        .modal-title { font-size: 18px; font-weight: 700; color: var(--dark-text); display: flex; align-items: center; gap: 10px; }
        .modal-title i { color: var(--gold-dark); }
        .modal-close { width: 32px; height: 32px; border: none; background: var(--cream); border-radius: 8px; cursor: pointer; font-size: 18px; color: var(--light-text); display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .modal-close:hover { background: #e5e3dd; color: var(--dark-text); }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .form-group { margin-bottom: 14px; }
        .form-label { display: block; font-size: 12px; font-weight: 700; color: var(--dark-text); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 10px 14px; border: 1.5px solid var(--border-light); border-radius: 10px; font-size: 14px; font-family: 'Poppins', sans-serif; transition: all 0.2s; background: white; color: var(--dark-text); }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(184,134,11,0.12); }
        .form-textarea { min-height: 80px; resize: vertical; }
        .form-section { background: var(--cream); padding: 16px; border-radius: 12px; margin-bottom: 16px; }
        .form-section-title { font-size: 13px; font-weight: 700; color: var(--gold-dark); margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border-light); }
        .btn-submit { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: white; padding: 12px 24px; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 14px; font-family: 'Poppins', sans-serif; transition: all 0.2s; }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(184,134,11,0.3); }
        .btn-cancel { background: var(--cream); color: var(--dark-text); padding: 12px 24px; border: 1.5px solid var(--border-light); border-radius: 10px; cursor: pointer; font-weight: 500; font-size: 14px; font-family: 'Poppins', sans-serif; }
        .btn-cancel:hover { background: #ece9e1; }
        .tab-container { display: flex; gap: 4px; background: var(--cream); padding: 4px; border-radius: 12px; margin-bottom: 20px; }
        .tab { flex: 1; padding: 10px 16px; border: none; background: transparent; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; color: var(--light-text); transition: all 0.2s; font-family: 'Poppins', sans-serif; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .tab.active { background: white; color: var(--dark-text); box-shadow: 0 2px 6px rgba(0,0,0,0.1); font-weight: 600; }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-icon { font-size: 56px; color: var(--light-text); opacity: 0.25; margin-bottom: 16px; }
        .year-header { font-size: 13px; font-weight: 700; color: var(--gold-dark); margin: 20px 0 10px 0; padding: 8px 12px; border-left: 4px solid var(--gold); background: linear-gradient(to right, rgba(184,134,11,0.06), transparent); border-radius: 0 8px 8px 0; display: flex; align-items: center; gap: 8px; }
        .subject-row { display: flex; align-items: center; gap: 12px; padding: 10px 14px; background: var(--cream); border-radius: 10px; margin-bottom: 8px; border: 1px solid transparent; transition: all 0.2s; }
        .subject-row:hover { border-color: var(--border-light); box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .subject-row.prerequisite { border-left: 4px solid #ef4444; background: linear-gradient(135deg, #fef2f2, #fff5f5); }
        .subject-icon { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, var(--gold), var(--gold-dark)); display: flex; align-items: center; justify-content: center; font-size: 16px; color: white; flex-shrink: 0; }
        .subject-info { flex: 1; min-width: 0; }
        .subject-name { font-size: 14px; font-weight: 600; color: var(--dark-text); }
        .subject-meta { font-size: 12px; color: var(--light-text); margin-top: 2px; }
        .subject-badge { font-size: 10px; padding: 4px 10px; border-radius: 20px; font-weight: 700; text-transform: uppercase; white-space: nowrap; flex-shrink: 0; }
        .badge-prereq { background: #fef3c7; color: #92400e; border: 1px solid #fbbf24; }
        .badge-required { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        .btn-icon { width: 30px; height: 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; flex-shrink: 0; }
        .btn-star { background: #fef3c7; color: var(--gold-dark); }
        .btn-star:hover, .btn-star.active { background: var(--gold-dark); color: white; }
        .btn-remove { background: #fee2e2; color: #ef4444; }
        .btn-remove:hover { background: #ef4444; color: white; }
        .btn-edit-subj { background: #dbeafe; color: #1d4ed8; }
        .btn-edit-subj:hover { background: #1d4ed8; color: white; }
        .subject-actions { display: flex; gap: 6px; flex-shrink: 0; }
        .prereq-panel { background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 1px solid #fbbf24; border-radius: 12px; padding: 16px; margin-top: 16px; }
        .prereq-panel-title { font-size: 12px; font-weight: 700; color: var(--gold-dark); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
        .prereq-chain-item { display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: white; border-radius: 8px; margin-bottom: 6px; border: 1px solid rgba(251,191,36,0.3); }
        .prereq-chain-item:last-child { margin-bottom: 0; }
        .prereq-chain-arrow { color: var(--gold-dark); font-size: 11px; text-align: center; padding: 2px 0; }

        /* -- PREREQUISITE CRUD TABLE --------------------------- */
        .prereq-table-wrap { overflow-x: auto; }
        .prereq-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .prereq-table th { background: var(--cream); color: var(--gold-dark); font-weight: 700; padding: 10px 14px; text-align: left; border-bottom: 2px solid var(--border-light); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .prereq-table td { padding: 10px 14px; border-bottom: 1px solid #f0ece4; vertical-align: middle; }
        .prereq-table tr:last-child td { border-bottom: none; }
        .prereq-table tr:hover td { background: #fdfbf6; }
        .prereq-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .prereq-badge.has-prereq { background: #fef3c7; color: #92400e; border: 1px solid #fbbf24; }
        .prereq-badge.no-prereq { background: var(--cream); color: var(--light-text); border: 1px solid var(--border-light); }
        .btn-sm { padding: 5px 10px; border: none; border-radius: 7px; cursor: pointer; font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; transition: all 0.2s; font-family: 'Poppins', sans-serif; }
        .btn-sm-gold { background: var(--cream); color: var(--gold-dark); border: 1px solid var(--gold-light); }
        .btn-sm-gold:hover { background: var(--gold-dark); color: white; }
        .btn-sm-red { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; }
        .btn-sm-red:hover { background: #dc2626; color: white; }
        .btn-sm-blue { background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; }
        .btn-sm-blue:hover { background: #1d4ed8; color: white; }
        .search-bar { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: var(--cream); border-radius: 10px; border: 1.5px solid var(--border-light); transition: all 0.2s; }
        .search-bar:focus-within { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(184,134,11,0.1); }
        .search-bar i { color: var(--light-text); font-size: 14px; }
        .search-bar input { border: none; background: transparent; font-family: 'Poppins', sans-serif; font-size: 14px; color: var(--dark-text); flex: 1; outline: none; }
        .info-tip { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 10px 14px; font-size: 13px; color: #1e40af; display: flex; align-items: flex-start; gap: 8px; margin-bottom: 16px; }
        .info-tip i { margin-top: 1px; flex-shrink: 0; }
        .stat-pills { display: flex; gap: 8px; flex-wrap: wrap; }
        .stat-pill { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background: var(--cream); color: var(--light-text); border: 1px solid var(--border-light); }
        .stat-pill.gold { background: #fef3c7; color: #92400e; border-color: #fbbf24; }

        /* -- PROSPECTUS SCREEN STYLES -------------------------- */
        .pro-wrap { font-family: 'Poppins', sans-serif; font-size: 12px; color: #1a1a1a; background: white; border-radius: 12px; border: 1px solid var(--border-light); overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .pro-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px 10px; background: linear-gradient(to bottom, #fffdf5, #fff); border-bottom: 2px solid #8B6914; }
        .pro-logo { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1.5px solid #8B6914; }
        .pro-title-block { text-align: center; flex: 1; padding: 0 12px; }
        .pro-school { font-size: 14px; font-weight: 700; letter-spacing: .3px; text-transform: uppercase; }
        .pro-address { font-size: 10px; color: #666; margin: 2px 0; }
        .pro-institute { font-size: 11px; font-weight: 700; color: #8B6914; text-transform: uppercase; margin-top: 4px; }
        .pro-degree { font-size: 10px; color: #444; margin: 2px 0; }
        .pro-major { font-size: 11px; font-weight: 600; margin: 2px 0; }
        .pro-label { display: inline-block; margin-top: 4px; padding: 2px 10px; border: 1px solid #8B6914; border-radius: 12px; font-size: 9px; font-weight: 700; color: #8B6914; letter-spacing: .3px; text-transform: uppercase; }
        .pro-body { padding: 10px 12px 12px; }
        .pro-year-block { margin-bottom: 10px; border: 1px solid #e0dbd0; border-radius: 6px; overflow: hidden; }
        .pro-year-header { background: linear-gradient(135deg, #8B6914, #B8860B); color: #fff; padding: 6px 10px; font-size: 11px; font-weight: 700; display: flex; justify-content: space-between; align-items: center; }
        .pro-year-total { font-size: 10px; font-weight: 400; opacity: .85; }
        .pro-sem-row { display: grid; grid-template-columns: 1fr 1fr; padding: 8px 10px; gap: 8px; }
        .pro-sem-label { font-size: 11px; font-weight: 700; color: #333; text-align: center; padding: 4px 0; background: #fde68a; border: 1px solid #d4cfc5; border-radius: 4px 4px 0 0; text-transform: uppercase; letter-spacing: .2px; }
        .pro-table { width: 100%; border-collapse: collapse; font-size: 11px; table-layout: auto; }
        .pro-th { background: #f0ece0; padding: 4px 6px; text-align: left; font-size: 10px; font-weight: 700; color: #8B6914; border: 1px solid #ccc; white-space: nowrap; }
        .pro-table td { border: 1px solid #ddd; padding: 3px 5px; vertical-align: middle; word-wrap: break-word; word-break: break-word; }
        .pro-grade-cell { text-align: center; background: #fafaf8; width: 24px; }
        .pro-code { font-weight: 600; white-space: nowrap; font-size: 11px; }
        .pro-units { text-align: center; font-weight: 500; white-space: nowrap; }
        .pro-prereq-col { color: #888; font-size: 10px; white-space: nowrap; }
        .pro-prereq-screen { display: table-cell; }
        .pro-prereq-row { background: #fff8f8; border-left: 3px solid #dc2626; }
        .pro-star { color: #dc2626; }
        .pro-total-row td { background: #f0ece0; font-weight: 700; color: #8B6914; border-top: 2px solid #B8860B; font-size: 11px; }
        .pro-empty { text-align: center; color: #aaa; font-style: italic; padding: 8px; }
        .pro-grand-total { text-align: right; font-size: 12px; font-weight: 600; padding: 6px 12px; background: #f7f5ef; border: 1px solid #d4cfc5; border-radius: 6px; margin: 0 0 12px 0; }
        .pro-sig-block { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; padding: 12px 0 0; border-top: 2px solid #d4cfc5; }
        .pro-sig-col { text-align: center; }
        .pro-sig-line { border-bottom: 1.5px solid #333; margin-bottom: 4px; height: 20px; }
        .pro-sig-label { font-size: 11px; font-weight: 600; color: #333; }
        .pro-sig-sub { font-size: 10px; color: #888; margin-top: 2px; }
        .pro-legend { font-size: 10px; color: #999; padding: 4px 0; }
        
        /* Responsive table with action column */
        @media screen and (max-width: 768px) {
            .pro-sem-row { grid-template-columns: 1fr; gap: 12px; }
            .pro-table { font-size: 10px; }
            .pro-th, .pro-table td { padding: 4px 6px; }
            .pro-code { font-size: 10px; }
            .pro-th-crud, .pro-crud-col { width: 50px; min-width: 50px; }
            .btn-crud { width: 22px; height: 22px; font-size: 10px; }
        }
        
        /* Drag and drop styles */
        .pro-table tbody tr[draggable="true"] { cursor: grab; will-change: auto; }
        .pro-table tbody tr[draggable="true"]:hover { background: #fdfbf6; }
        .pro-table tbody tr.drag-over-above { 
            border-top: 3px solid #f59e0b !important;
        }
        .pro-table tbody tr.drag-over-below { 
            border-bottom: 3px solid #f59e0b !important;
        }
        .pro-table tbody tr.dragging-row { 
            opacity: 0.35; 
            background: #fef9c3 !important; 
        }
        .pro-table tbody.drag-over-empty { background: #fef3c7; border-radius: 6px; }
        .pro-table tbody.drag-over-empty tr { background: #fef3c7; }
        .drag-handle { cursor: grab; color: #9ca3af; padding: 4px 8px; display: flex; align-items: center; justify-content: center; }
        .drag-handle:hover { color: #f59e0b; background: rgba(245,158,11,0.08); border-radius: 4px; }
        .drag-handle:active, .drag-handle.dragging { cursor: grabbing; color: #f59e0b; }
        .drag-handle i { pointer-events: none; }
        
        /* Drop zone indicator for empty semesters */
        .pro-table tbody.drag-over-empty td.pro-empty {
            background: #fef3c7 !important;
            color: #92400e !important;
            font-style: normal !important;
            border: 2px dashed #f59e0b !important;
        }
        .pro-table tbody.drag-over-empty td.pro-empty::after {
            content: ' – Drop here to move subject';
            font-weight: 600;
        }
        .pro-bridging-block { margin-bottom: 16px; }

        /* -- PRINT STYLES – SINGLE-PAGE PROSPECTUS ---------------------- */
@media print {

    /* -- Paper size config (change these to switch paper types) -- */
    :root {
        --print-paper: A4;           /* A4, Letter, Legal */
    }

    /* A4 ? 210 – 297 mm */
    /* Short Bond (Letter) ? 216 – 279 mm */
    /* Long Bond (Legal) ? 216 – 356 mm */

    /* -- Page setup -- */
    @page {
        size: A4 portrait;
        margin: 6mm 6mm 6mm 6mm;
    }

    /* -- Reset everything -- */
    *, *::before, *::after {
        box-sizing: border-box !important;
    }

    html {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        background: white !important;
        overflow: visible !important;
        font-family: 'Times New Roman', Times, serif !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* -- Hide screen-only columns in print -- */
    .pro-prereq-screen { display: none !important; }
    .pro-th.pro-prereq-screen { display: none !important; }

    /* -- Hide ALL UI except the prospectus -- */
    body > *:not(.main-content),
    .sidebar,
    .topbar,
    .page-header,
    .tab-container,
    #majorsTab,
    #prerequisitesTab,
    .prospectus-controls,
    .prospectus-controls-right,
    .btn-add,
    .btn-edit-mode,
    button,
    .modal-overlay,
    .toast,
    .pro-crud-col,
    .pro-th-crud,
    .drag-handle,
    .btn-crud,
    #printProspectusBtn,
    #editModeBtn {
        display: none !important;
        visibility: hidden !important;
    }

    /* -- Show only the prospectus -- */
    body * {
        visibility: hidden !important;
    }

    #printableProspectus,
    #printableProspectus * {
        visibility: visible !important;
    }

    /* -- Layout reset for wrappers -- */
    .main-content,
    .dashboard-content,
    .page-container,
    .card,
    #subjectsTab,
    #prospectusContent {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        width: 100% !important;
        max-width: none !important;
        overflow: visible !important;
    }

    /* -- Prospectus root -- */
    #printableProspectus {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 198mm !important;       /* A4 210mm - 6mm*2 margins */
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* -- DOCUMENT WRAPPER ----------------------------------------- */
    .pro-wrap {
        width: 198mm !important;
        max-width: 198mm !important;
        font-size: 7pt !important;
        font-family: 'Times New Roman', Times, serif !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        background: white !important;
        overflow: visible !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* -- HEADER --------------------------------------------------- */
    .pro-header {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: space-between !important;
        width: 100% !important;
        padding: 2mm 2mm 1.5mm !important;
        border-top: 2pt solid #8B6914 !important;
        border-bottom: 1.5pt solid #8B6914 !important;
        background: white !important;
        box-sizing: border-box !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .pro-logo {
        width: 24mm !important;
        height: 24mm !important;
        min-width: 24mm !important;
        min-height: 24mm !important;
        object-fit: cover !important;
        border: 2pt solid #8B6914 !important;
        border-radius: 3pt !important;
        flex-shrink: 0 !important;
        display: block !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .pro-title-block {
        flex: 1 1 auto !important;
        text-align: center !important;
        padding: 0 4mm !important;
    }

    .pro-school {
        font-size: 14pt !important;
        font-weight: 700 !important;
        font-family: 'Times New Roman', Times, serif !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5pt !important;
        line-height: 1.1 !important;
        color: #000 !important;
        margin: 0 0 1mm !important;
    }

    .pro-address {
        font-size: 8.5pt !important;
        color: #444 !important;
        font-style: italic !important;
        margin: 0 0 1.5mm !important;
        font-family: 'Times New Roman', Times, serif !important;
    }

    .pro-institute {
        font-size: 10.5pt !important;
        font-weight: 700 !important;
        color: #8B6914 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.4pt !important;
        margin: 0 0 1mm !important;
        font-family: 'Times New Roman', Times, serif !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .pro-degree {
        font-size: 9pt !important;
        color: #222 !important;
        margin: 0 0 1mm !important;
        font-family: 'Times New Roman', Times, serif !important;
    }

    .pro-major {
        font-size: 10pt !important;
        font-weight: 700 !important;
        color: #000 !important;
        margin: 0 0 1.2mm !important;
        font-family: 'Times New Roman', Times, serif !important;
    }

    .pro-label {
        display: inline-block !important;
        padding: 2pt 10pt !important;
        border: 1.2pt solid #8B6914 !important;
        border-radius: 12pt !important;
        font-size: 8pt !important;
        font-weight: 700 !important;
        color: #8B6914 !important;
        letter-spacing: 0.8pt !important;
        text-transform: uppercase !important;
        font-family: 'Times New Roman', Times, serif !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* -- BODY ----------------------------------------------------- */
    .pro-body {
        width: 100% !important;
        padding: 1mm 0 0 !important;
        overflow: visible !important;
    }

    /* -- YEAR BLOCKS ---------------------------------------------- */
    .pro-year-block {
        width: 100% !important;
        margin-bottom: 1mm !important;
        border: 0.5pt solid #bbb !important;
        border-radius: 0 !important;
        overflow: hidden !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .pro-year-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        width: 100% !important;
        padding: 1mm 2mm !important;
        font-size: 7.5pt !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3pt !important;
        font-family: 'Times New Roman', Times, serif !important;
        background: #8B6914 !important;
        color: white !important;
        box-sizing: border-box !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .pro-year-total {
        font-size: 7pt !important;
        font-weight: 400 !important;
        opacity: 0.9 !important;
    }

    /* -- Two-column semester layout -- */
    .pro-sem-row {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 1mm !important;
        padding: 1mm !important;
        width: 100% !important;
        box-sizing: border-box !important;
        background: white !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .pro-sem-label {
        width: 100% !important;
        font-size: 6.5pt !important;
        font-weight: 700 !important;
        padding: 1pt 0 !important;
        text-align: center !important;
        background: #fde68a !important;
        border: 0.5pt solid #d4cfc5 !important;
        border-radius: 0 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.2pt !important;
        margin-bottom: 0 !important;
        color: #333 !important;
        box-sizing: border-box !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* -- TABLES --------------------------------------------------- */
    .pro-table {
        width: 100% !important;
        max-width: 100% !important;
        border-collapse: collapse !important;
        font-size: 6.5pt !important;
        table-layout: fixed !important;
        font-family: 'Times New Roman', Times, serif !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        overflow: hidden !important;
    }

    .pro-th {
        background: #f0ece0 !important;
        padding: 0.8pt 1.5pt !important;
        font-size: 6pt !important;
        font-weight: 700 !important;
        color: #7a5c10 !important;
        border: 0.5pt solid #ccc !important;
        text-align: left !important;
        word-break: break-word !important;
        line-height: 1.1 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .pro-table td {
        border: 0.5pt solid #ddd !important;
        padding: 0.8pt 1.5pt !important;
        line-height: 1.1 !important;
        font-size: 7pt !important;
        word-break: break-word !important;
        vertical-align: middle !important;
        overflow: hidden !important;
    }

    .pro-code {
        font-size: 7pt !important;
        font-weight: 700 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    .pro-units {
        font-size: 7.5pt !important;
        text-align: center !important;
        font-weight: 600 !important;
        white-space: nowrap !important;
    }

    .pro-grade-cell {
        width: 12mm !important;
        max-width: 12mm !important;
        text-align: center !important;
        background: #fafaf8 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .pro-prereq-col {
        font-size: 6pt !important;
        color: #777 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    .pro-prereq-row {
        background: #fff5f5 !important;
        border-left: 1.5pt solid #dc2626 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .pro-star {
        color: #dc2626 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .pro-total-row td {
        background: #f0ece0 !important;
        font-weight: 700 !important;
        color: #8B6914 !important;
        border-top: 1pt solid #B8860B !important;
        font-size: 7pt !important;
        padding: 1pt 2pt !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .pro-empty {
        text-align: center !important;
        color: #aaa !important;
        font-style: italic !important;
        padding: 2pt !important;
        font-size: 6.5pt !important;
    }

    /* -- BRIDGING BLOCK ------------------------------------------- */
    .pro-bridging-block {
        width: 100% !important;
        margin-bottom: 1.5mm !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    /* -- GRAND TOTAL ---------------------------------------------- */
    .pro-grand-total {
        font-size: 8.5pt !important;
        font-weight: 700 !important;
        text-align: right !important;
        padding: 1.5mm 3mm !important;
        margin: 1.5mm 0 !important;
        background: #f0ece0 !important;
        border-top: 1pt solid #B8860B !important;
        border-bottom: 1pt solid #B8860B !important;
        color: #8B6914 !important;
        font-family: 'Times New Roman', Times, serif !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* -- SIGNATURE BLOCK ------------------------------------------ */
    .pro-sig-block {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 8mm !important;
        padding: 2mm 0 0 !important;
        border-top: 1pt solid #aaa !important;
        margin-top: 2mm !important;
        width: 100% !important;
        box-sizing: border-box !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .pro-sig-col {
        text-align: center !important;
    }

    .pro-sig-line {
        border-bottom: 1pt solid #333 !important;
        height: 10mm !important;
        margin-bottom: 1mm !important;
    }

    .pro-sig-label {
        font-size: 7pt !important;
        font-weight: 700 !important;
        color: #222 !important;
        line-height: 1.3 !important;
        font-family: 'Times New Roman', Times, serif !important;
    }

    .pro-sig-sub {
        font-size: 6.5pt !important;
        color: #888 !important;
        margin-top: 0.5mm !important;
        font-style: italic !important;
    }

    /* -- LEGEND --------------------------------------------------- */
    .pro-legend {
        font-size: 6pt !important;
        color: #999 !important;
        margin-top: 1mm !important;
        padding: 0 !important;
        font-style: italic !important;
    }
}

        /* Prospectus select/controls area */
        .prospectus-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .prospectus-controls-left h3 { font-size: 16px; font-weight: 700; color: var(--dark-text); margin: 0; }
        .prospectus-controls-left p { font-size: 13px; color: var(--light-text); margin: 4px 0 0; }
        .prospectus-controls-right { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .btn-edit-mode { background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; padding: 10px 18px; border: none; border-radius: 10px; cursor: pointer; font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease; font-family: 'Poppins', sans-serif; }
        .btn-edit-mode:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35); }
        .btn-edit-mode.active { background: linear-gradient(135deg, #4f46e5, #3730a3); }
        .pro-crud-col { width: 60px; text-align: center; }
        .btn-crud { width: 24px; height: 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 11px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; margin: 0 2px; }
        .btn-crud-edit { background: #dbeafe; color: #1d4ed8; }
        .btn-crud-edit:hover { background: #1d4ed8; color: white; }
        .btn-crud-delete { background: #fee2e2; color: #dc2626; }
        .btn-crud-delete:hover { background: #dc2626; color: white; }
        .pro-th-crud { width: 60px; }

        /* Notification toast */
        .toast { 
            position: fixed; 
            bottom: 24px; 
            right: 24px; 
            background: linear-gradient(135deg, #ffffff, #f8f9fa); 
            color: #1f1f1f; 
            padding: 16px 20px; 
            border-radius: 16px; 
            font-size: 14px; 
            font-weight: 500; 
            display: flex; 
            align-items: center; 
            gap: 14px; 
            transform: translateY(120px) scale(0.9); 
            opacity: 0; 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            z-index: 99999; 
            box-shadow: 0 12px 40px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.05); 
            max-width: 380px; 
            min-width: 280px;
        }
        .toast.show { 
            transform: translateY(0) scale(1); 
            opacity: 1; 
        }
        .toast-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .toast.success .toast-icon { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #16a34a; }
        .toast.error .toast-icon { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #dc2626; }
        .toast.info .toast-icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
        .toast-content { flex: 1; }
        .toast-title { font-weight: 600; margin-bottom: 2px; font-size: 14px; }
        .toast-message { font-size: 13px; color: #6b7280; }
        .toast-close {
            width: 24px;
            height: 24px;
            border: none;
            background: transparent;
            color: #9ca3af;
            cursor: pointer;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 12px;
        }
        .toast-close:hover { background: #f3f4f6; color: #374151; }
        .toast.success { border-left: none; border-top: 3px solid #22c55e; }
        .toast.error { border-left: none; border-top: 3px solid #ef4444; }
        .toast.info { border-left: none; border-top: 3px solid var(--gold); }
        .loading-spinner { display: inline-block; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 0.7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../../../media/LOGO.jpg" alt="Logo" class="sidebar-logo" style="width:70px;height:70px;border-radius:16px;object-fit:cover;border:3px solid white;background:white;padding:4px;box-shadow:0 4px 12px rgba(0,0,0,0.2);">
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
            <a href="departments.php" class="sidebar-nav-item active"><i class="fas fa-graduation-cap"></i><span>Department</span></a>
            <a href="reports.php" class="sidebar-nav-item"><i class="fas fa-file-alt"></i><span>Reports</span></a>
            <a href="settings.php" class="sidebar-nav-item"><i class="fas fa-cog"></i><span>Settings</span></a>
        </nav>
    </aside>

    <div class="main-content" style="position: relative; padding-top: 70px;">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        <header class="topbar" style="position: fixed; top: 0; left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); z-index: 200;">
            <div class="topbar-left">
                <button class="topbar-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div>
                    <div class="topbar-title">Department Management</div>
                    <div class="topbar-subtitle">Program Head Panel</div>
                </div>
            </div>
            <div class="topbar-right">
                <div class="topbar-date"><i class="fas fa-calendar-alt"></i><span><?php echo date('F j, Y'); ?></span></div>
                <a href="../../../data/logout.php" class="topbar-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="page-container">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Department Management</h1>
                        <p class="page-subtitle">Manage majors, subjects, prerequisites, and prospectus</p>
                    </div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button class="btn-add" onclick="switchTab('prerequisites')"><i class="fas fa-sitemap"></i> Prerequisites</button>
                        <button class="btn-add" onclick="showSubjectModal()"><i class="fas fa-book"></i> Add Subject</button>
                        <button class="btn-add" onclick="showMajorModal()"><i class="fas fa-plus"></i> Add Major</button>
                    </div>
                </div>

                <div class="card">
                    <div class="tab-container">
                        <button class="tab active" onclick="switchTab('majors')"><i class="fas fa-graduation-cap"></i> Majors</button>
                        <button class="tab" onclick="switchTab('prerequisites')"><i class="fas fa-sitemap"></i> Prerequisites</button>
                        <button class="tab" onclick="switchTab('subjects')"><i class="fas fa-scroll"></i> Prospectus</button>
                    </div>

                    <!-- -- MAJORS TAB -------------------------------------- -->
                    <div id="majorsTab">
                        <?php if (empty($majors)): ?>
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-graduation-cap"></i></div>
                            <h3 style="font-size:18px;font-weight:700;color:var(--dark-text);margin-bottom:8px;">No Majors Configured</h3>
                            <p style="color:var(--light-text);margin-bottom:20px;">Create your first major to get started.</p>
                            <button class="btn-add" style="margin:0 auto;" onclick="showMajorModal()"><i class="fas fa-plus"></i> Add Major</button>
                        </div>
                        <?php else: ?>
                        <div class="major-grid">
                            <?php foreach ($majors as $major): ?>
                            <div class="major-card <?php echo $major['is_active'] ? '' : 'inactive'; ?>">
                                <div class="major-header">
                                    <div class="major-icon" style="background:linear-gradient(135deg,<?php echo htmlspecialchars($major['gradient_from']); ?>,<?php echo htmlspecialchars($major['gradient_to']); ?>);">
                                        <i class="<?php echo htmlspecialchars($major['icon_class']); ?>"></i>
                                    </div>
                                    <div class="major-info">
                                        <div class="major-name"><?php echo htmlspecialchars($major['display_name']); ?></div>
                                        <div class="major-meta">
                                            <span class="stat-pill" style="font-size:11px;padding:2px 8px;"><?php echo $major['subject_count']; ?> Subjects</span>
                                            <span class="stat-pill" style="font-size:11px;padding:2px 8px;margin-left:4px;"><?php echo $major['student_count']; ?> Students</span>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($major['description']): ?>
                                <div class="major-desc"><?php echo htmlspecialchars($major['description']); ?></div>
                                <?php endif; ?>
                                <div class="major-actions">
                                    <button class="btn-action btn-view" onclick="viewMajorSubjects(<?php echo $major['id']; ?>,'<?php echo htmlspecialchars(addslashes($major['display_name'])); ?>')">
                                        <i class="fas fa-eye"></i> Subjects
                                    </button>
                                    <button class="btn-action btn-edit" onclick="editMajor(<?php echo $major['id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn-action btn-delete" onclick="deleteMajor(<?php echo $major['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- -- PREREQUISITES TAB -------------------------------------- -->
                    <div id="prerequisitesTab" style="display:none;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;gap:12px;flex-wrap:wrap;">
                            <div>
                                <h3 style="font-size:18px;font-weight:700;color:var(--dark-text);margin-bottom:4px;"><i class="fas fa-sitemap" style="color:var(--gold-dark);margin-right:8px;"></i>Prerequisites Management</h3>
                                <p style="font-size:13px;color:var(--light-text);">Create and manage prerequisite sets for subjects</p>
                            </div>
                            <button class="btn-add" onclick="showCreatePrereqModal()">
                                <i class="fas fa-plus"></i> Create Prerequisites
                            </button>
                        </div>
                        <div id="prereqSetsContainer">
                            <div style="text-align:center;padding:40px;color:var(--light-text);">
                                <div style="font-size:48px;opacity:0.2;margin-bottom:12px;"><i class="fas fa-sitemap"></i></div>
                                <p style="font-weight:600;">No prerequisites created yet</p>
                                <p style="font-size:13px;margin-top:4px;">Click "Create Prerequisites" to add a new set</p>
                            </div>
                        </div>
                    </div>

                    <!-- -- PROSPECTUS TAB ---------------------------------- -->
                    <div id="subjectsTab" style="display:none;">
                        <div class="prospectus-controls">
                            <div class="prospectus-controls-left">
                                <h3><i class="fas fa-scroll" style="color:var(--gold-dark);margin-right:8px;"></i>Subject Prospectus</h3>
                                <p>Official curriculum layout – view and print by major</p>
                            </div>
                            <div class="prospectus-controls-right">
                                <select id="prospectusMajorSelect" class="form-select" style="min-width:240px;" onchange="loadProspectus()">
                                    <option value="">– Select Major –</option>
                                    <?php foreach ($majors as $major): ?>
                                    <option value="<?php echo $major['id']; ?>"><?php echo htmlspecialchars($major['display_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn-edit-mode" id="editModeBtn" style="display:none;" onclick="toggleEditMode()">
                                    <i class="fas fa-edit"></i> Edit Mode
                                </button>
                                <button class="btn-add" id="bridgingBtn" style="display:none;" onclick="showBridgingModal()">
                                    <i class="fas fa-exchange-alt"></i> Bridging
                                </button>
                                <button class="btn-add" id="printProspectusBtn" style="display:none;" onclick="printProspectus()">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                        <div id="prospectusContent">
                            <div style="text-align:center;padding:60px 20px;background:var(--cream);border-radius:12px;border:1px solid var(--border-light);">
                                <div style="width:80px;height:80px;border-radius:50%;background:white;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;border:1px solid var(--border-light);">
                                    <i class="fas fa-scroll" style="font-size:32px;color:var(--gold-dark);"></i>
                                </div>
                                <h3 style="font-size:18px;font-weight:700;margin-bottom:8px;">Select a Major</h3>
                                <p style="color:var(--light-text);">Choose a major above to generate the prospectus.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- -- TOAST --------------------------------------------------- -->
    <div class="toast" id="toast">
        <div class="toast-icon"><i class="fas fa-check-circle"></i></div>
        <div class="toast-content">
            <div class="toast-title" id="toastTitle">Success</div>
            <div class="toast-message" id="toastMsg"></div>
        </div>
        <button class="toast-close" onclick="closeToast()"><i class="fas fa-times"></i></button>
    </div>

    <!-- -- BRIDGING SUBJECTS MODAL ---------------------------------------------- -->
    <div class="modal-overlay" id="bridgingModal">
        <div class="modal" style="max-width:800px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-exchange-alt"></i> Bridging Subjects</h3>
                <button class="modal-close" onclick="closeBridgingModal()"><i class="fas fa-times"></i></button>
            </div>
            <div id="bridgingContent" style="max-height:60vh;overflow-y:auto;">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- -- MAJOR MODAL ---------------------------------------------- -->
    <div class="modal-overlay" id="majorModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-graduation-cap"></i> <span id="majorModalTitle">Add Major</span></h3>
                <button class="modal-close" onclick="closeMajorModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="majorForm" onsubmit="saveMajor(event)">
                <input type="hidden" id="majorId" name="id" value="0">
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-info-circle"></i> Basic Details</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Major Code *</label>
                            <input type="text" class="form-input" id="majorName" name="major_name" placeholder="e.g., opm" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Display Name *</label>
                            <input type="text" class="form-input" id="displayName" name="display_name" placeholder="e.g., Operational Management" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" id="majorDesc" name="description" placeholder="Brief description of this major..."></textarea>
                    </div>
                </div>
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-palette"></i> Appearance</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Icon</label>
                            <select class="form-select" id="majorIcon" name="icon_class">
                                <option value="fas fa-graduation-cap">🎓 Graduation Cap</option>
                                <option value="fas fa-cogs">⚙️ Cogs</option>
                                <option value="fas fa-dollar-sign">💲 Finance</option>
                                <option value="fas fa-chart-line">📈 Analytics</option>
                                <option value="fas fa-briefcase">💼 Business</option>
                                <option value="fas fa-users">👥 Management</option>
                                <option value="fas fa-book">📚 Academic</option>
                                <option value="fas fa-laptop">💻 Technology</option>
                                <option value="fas fa-handshake">🤝 Relations</option>
                                <option value="fas fa-globe">🌐 International</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="majorActive" name="is_active">
                                <option value="1">? Active</option>
                                <option value="0">? Inactive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gradient From</label>
                            <input type="color" class="form-input" id="gradientFrom" name="gradient_from" value="#d4a843">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gradient To</label>
                            <input type="color" class="form-input" id="gradientTo" name="gradient_to" value="#8B6914">
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeMajorModal()">Cancel</button>
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Major</button>
                </div>
            </form>
        </div>
    </div>

    <!-- -- SUBJECT MODAL -------------------------------------------- -->
    <div class="modal-overlay" id="subjectModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-book"></i> <span id="subjectModalTitle">Add Subject</span></h3>
                <button class="modal-close" onclick="closeSubjectModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="subjectForm" onsubmit="saveSubject(event)">
                <input type="hidden" id="subjectId" name="id" value="0">
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-tag"></i> Subject Information</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Subject Code *</label>
                            <input type="text" class="form-input" id="subjectCode" name="subject_code" placeholder="e.g., OPM 101" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subject Title *</label>
                            <input type="text" class="form-input" id="subjectName" name="subject_name" placeholder="e.g., Operations Management" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Credit Units *</label>
                            <input type="number" class="form-input" id="subjectUnits" name="units" value="3" step="0.5" min="0" max="10">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prerequisite</label>
                            <input type="text" class="form-input" id="subjectPrerequisite" name="prerequisite" placeholder="e.g., ENG 1">
                        </div>
                    </div>
                    <div class="form-group" id="bridgingForGroup" style="margin-top:10px;display:none;">
                        <label class="form-label"><i class="fas fa-user-graduate"></i> Bridging For (who is this for?)</label>
                        <input type="text" class="form-input" id="subjectBridgingFor" name="bridging_for" placeholder="e.g., SHS NON-ABM, ALS GRADUATE">
                        <p style="font-size:11px;color:var(--light-text);margin-top:4px;">Separate multiple with comma. E.g., "SHS NON-ABM, ALS GRADUATE"</p>
                    </div>
                </div>
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-graduation-cap"></i> Add to Prospectus</div>
                    <p style="font-size:12px;color:var(--light-text);margin-bottom:10px;">Select majors to include this subject in:</p>
                    <div style="max-height:160px;overflow-y:auto;border:1.5px solid var(--border-light);border-radius:10px;padding:8px;background:white;">
                        <?php foreach ($majors as $major): ?>
                        <label style="display:flex;align-items:center;gap:10px;padding:7px 10px;border-radius:8px;cursor:pointer;transition:background 0.15s;" onmouseover="this.style.background='var(--cream)'" onmouseout="this.style.background='transparent'">
                            <input type="checkbox" class="prospectus-major-check" name="prospectus_majors[]" value="<?php echo $major['id']; ?>" style="width:16px;height:16px;accent-color:var(--gold-dark);">
                            <span style="font-size:13px;"><?php echo htmlspecialchars($major['display_name']); ?></span>
                        </label>
                        <?php endforeach; ?>
                        <?php if (empty($majors)): ?>
                        <p style="font-size:13px;color:var(--light-text);padding:8px;font-style:italic;">No majors available. Create a major first.</p>
                        <?php endif; ?>
                    </div>
                    <div class="form-grid" style="margin-top:12px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Year Level</label>
                            <select class="form-select" id="prospectusYearLevel" name="prospectus_year_level">
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                                <option value="Bridging">Bridging</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Semester</label>
                            <select class="form-select" id="prospectusSemester" name="prospectus_semester">
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeSubjectModal()">Cancel</button>
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Subject</button>
                </div>
            </form>
        </div>
    </div>

    <!-- -- SET/EDIT PREREQUISITE MODAL ------------------------------ -->
    <div class="modal-overlay" id="prereqModal" style="display:none;">
        <div class="modal" style="max-width:560px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-sitemap"></i> <span id="prereqModalTitle">Set Prerequisite</span></h3>
                <button class="modal-close" onclick="closePrereqModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="prereqForm" onsubmit="savePrerequisite(event)">
                <input type="hidden" id="prereqSubjectId" name="subject_id" value="0">
                <div class="info-tip" style="margin-bottom:16px;">
                    <i class="fas fa-lightbulb"></i>
                    <div>
                        <strong>How it works:</strong> The selected subject (the one you choose below) must be <em>passed first</em> before a student can take the target subject.
                    </div>
                </div>
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-arrow-right"></i> Target Subject (needs a prerequisite)</div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Subject *</label>
                        <select class="form-select" id="prereqTargetSubject" name="target_subject_id" required onchange="updatePrereqOptions2()">
                            <option value="">– Choose subject –</option>
                        </select>
                    </div>
                </div>
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-arrow-left"></i> Prerequisite Subject (must be passed first)</div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Prerequisite *</label>
                        <select class="form-select" id="prereqSourceSubject" name="prerequisite_subject_id" required>
                            <option value="">– Choose prerequisite –</option>
                        </select>
                    </div>
                </div>
                <div id="prereqPreview" style="display:none;" class="form-section">
                    <div class="form-section-title"><i class="fas fa-eye"></i> Preview</div>
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <div style="background:white;border:1px solid var(--border-light);border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600;" id="prevPrereq">–</div>
                        <div style="color:var(--gold-dark);font-weight:700;font-size:12px;">must pass first ?</div>
                        <div style="background:var(--gold-dark);color:white;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600;" id="prevTarget">–</div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closePrereqModal()">Cancel</button>
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Prerequisite</button>
                </div>
            </form>
        </div>
</div>

    <!-- -- CREATE PREREQUISITES MODAL -------------------------------------- -->
    <div class="modal-overlay" id="createPrereqModal">
        <div class="modal" style="max-width:700px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-sitemap"></i> Create Prerequisites</h3>
                <button class="modal-close" onclick="closeCreatePrereqModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="form-section">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Prerequisites Code *</label>
                    <input type="text" class="form-input" id="prereqSetCode" placeholder="e.g., PRE001" required>
                </div>
            </div>
            <div class="form-section">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Select Major *</label>
                    <select class="form-select" id="prereqMajorSelect" onchange="loadPrereqSubjectsForSelection()">
                        <option value="">– Select Major –</option>
                    </select>
                </div>
            </div>
            <div class="form-section">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Target Subject (needs this prerequisite)</label>
                    <select class="form-select" id="prereqTargetSubjectSelect">
                        <option value="">– Optional: Select target subject –</option>
                    </select>
                </div>
            </div>
            <div class="form-section">
                <div class="form-section-title"><i class="fas fa-book"></i> Select Subjects</div>
                <div id="prereqSubjectsContainer" style="max-height:350px;overflow-y:auto;border:1.5px solid var(--border-light);border-radius:10px;padding:12px;background:white;">
                    <div style="text-align:center;padding:20px;color:var(--light-text);"><i class="fas fa-spinner fa-spin"></i> Select a major first...</div>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeCreatePrereqModal()">Cancel</button>
                <button type="button" class="btn-submit" onclick="savePrereqSet()"><i class="fas fa-check"></i> Finish</button>
            </div>
        </div>
    </div>

    <!-- -- MAJOR SUBJECTS DETAIL MODAL -------------------------------------- -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal" style="max-width:820px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-list"></i> <span id="detailModalTitle">Major Subjects</span></h3>
                <button class="modal-close" onclick="closeDetailModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="info-tip" style="margin-bottom:16px;">
                <i class="fas fa-star" style="color:var(--gold-dark);"></i>
                <span>Click the <strong>star</strong> to mark a subject as a prerequisite for this major. Red-highlighted subjects are prerequisite requirements.</span>
            </div>
            <div id="majorSubjectsList"></div>
            <div id="majorPrereqPanel" style="margin-top:16px;"></div>
        </div>
    </div>

    <!-- -- ADD SUBJECT TO MAJOR MODAL -------------------------------- -->
    <div class="modal-overlay" id="addSubjectModal">
        <div class="modal" style="max-width:540px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-plus-circle"></i> Add Subject to Major</h3>
                <button class="modal-close" onclick="closeAddSubjectModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="addSubjectForm" onsubmit="saveMajorSubject(event)">
                <input type="hidden" id="addMajorId" name="major_id" value="0">
                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label">Select Subject *</label>
                        <select class="form-select" id="addSubjectId" name="subject_id" required onchange="updateDefaultYearSem()">
                            <option value="">Choose a subject...</option>
                        </select>
                    </div>
                </div>
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-calendar"></i> Placement</div>
                    <div class="form-grid">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Year Level</label>
                            <select class="form-select" id="addYearLevel" name="year_level">
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                                <option value="Bridging">Bridging</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Semester</label>
                            <select class="form-select" id="addSemester" name="semester">
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeAddSubjectModal()">Cancel</button>
                    <button type="submit" class="btn-submit"><i class="fas fa-plus"></i> Add Subject</button>
                </div>
            </form>
        </div>
    </div>


    <!-- -- DUPLICATE SUBJECT MODAL ------------------------------------ -->
    <div class="modal-overlay" id="duplicateSubjectModal">
        <div class="modal" style="max-width:380px;text-align:center;">
            <div style="width:64px;height:64px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="fas fa-exclamation-triangle" style="font-size:28px;color:#dc2626;"></i>
            </div>
            <h3 style="font-size:17px;font-weight:700;color:#1f1f1f;margin-bottom:8px;">Subject Code Already Exists</h3>
            <p id="duplicateSubjectMsg" style="font-size:13px;color:#6b7280;margin-bottom:20px;line-height:1.6;"></p>
            <div style="display:flex;gap:10px;justify-content:center;">
                <button type="button" class="btn-cancel" onclick="closeDuplicateSubjectModal()" style="flex:1;">Cancel</button>
                <button type="button" id="duplicateSubjectEditBtn" class="btn-submit" style="flex:1;background:linear-gradient(135deg,#dc2626,#b91c1c);">
                    <i class="fas fa-edit"></i> View Existing
                </button>
            </div>
        </div>
    </div>

    <!-- -- EDIT SUBJECT IN PROSPECTUS MODAL -------------------------- -->
    <div class="modal-overlay" id="editProspectusSubjectModal">
        <div class="modal" style="max-width:520px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-edit"></i> Edit Prospectus Placement</h3>
                <button class="modal-close" onclick="closeEditProspectusModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="editProspectusForm" onsubmit="saveProspectusEdit(event)">
                <input type="hidden" id="editProspectusMajorId" name="major_id">
                <input type="hidden" id="editProspectusSubjectId" name="subject_id">
                <input type="hidden" id="editProspectusDbSubjectId" name="db_subject_id">
                <div class="form-section">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Subject Code</label>
                            <input type="text" class="form-input" id="editProspectusSubjectCode" name="subject_code" placeholder="e.g. NSTP 1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subject Name</label>
                            <input type="text" class="form-input" id="editProspectusSubjectName" name="subject_name" placeholder="e.g. Nat'l Serv. Trng. Program" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Credit Units</label>
                        <input type="number" class="form-input" id="editProspectusUnits" name="units" step="0.5" min="0" max="10" value="3" placeholder="Enter units">
                    </div>
                    <div class="form-grid">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Year Level</label>
                            <select class="form-select" id="editProspectusYearLevel" name="year_level">
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                                <option value="Bridging">Bridging</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Semester</label>
                            <select class="form-select" id="editProspectusSemester" name="semester">
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:16px;">
                        <label class="form-label">Available in Majors</label>
                        <p style="font-size:11px;color:var(--light-text);margin-bottom:8px;">Check/uncheck to add or remove subject from majors:</p>
                        <div id="editProspectusMajorsList" style="max-height:140px;overflow-y:auto;border:1.5px solid var(--border-light);border-radius:10px;padding:8px;background:white;">
                            <!-- Majors checkboxes will be populated here -->
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditProspectusModal()">Cancel</button>
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../../function/dashboard.js"></script>
    <script>
    /* -- DATA FROM PHP ---------------------------------------------- */
    let currentMajorId = 0;
    let majorsData   = <?php echo json_encode($majors, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
let subjectsData = <?php echo json_encode($all_subjects, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
    let phSettings = <?php echo json_encode($ph_settings, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?> || {};
    phSettings.school_name = phSettings.school_name || phSettings.schoolName || '';
    phSettings.school_address = phSettings.school_address || phSettings.schoolAddress || '';
    phSettings.institute_name = phSettings.institute_name || phSettings.instituteName || '';
    phSettings.degree_name = phSettings.degree_name || phSettings.degreeName || '';

    /* -- URL PARAMETER HANDLER ----------------------------------------------- */
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab === 'prerequisites' || tab === 'subjects') {
            switchTab(tab);
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });

    /* -- TOAST NOTIFICATIONS ---------------------------------------------- */
    function toast(msg, type = 'info', duration = 3000, customTitle = null) {
        const el = document.getElementById('toast');
        const titleEl = document.getElementById('toastTitle');
        const msgEl = document.getElementById('toastMsg');
        const iconEl = el.querySelector('.toast-icon');
        
        msgEl.textContent = msg;
        
        // Set title based on type or custom title
        if (customTitle) {
            titleEl.textContent = customTitle;
        } else if (type === 'success') titleEl.textContent = 'Success';
        else if (type === 'error') titleEl.textContent = 'Error';
        else titleEl.textContent = 'Notice';
        
        // Set icon based on type
        let iconClass = 'fa-info-circle';
        if (type === 'success') iconClass = 'fa-check-circle';
        else if (type === 'error') iconClass = 'fa-times-circle';
        else if (type === 'info') iconClass = 'fa-info-circle';
        
        iconEl.innerHTML = `<i class="fas ${iconClass}"></i>`;
        
        el.className = `toast ${type} show`;
        clearTimeout(el._timer);
        el._timer = setTimeout(() => el.classList.remove('show'), duration);
    }
    
    function closeToast() {
        const el = document.getElementById('toast');
        el.classList.remove('show');
        clearTimeout(el._timer);
    }

    /* -- TAB SWITCHER --------------------------------------------- */
    function switchTab(tab) {
        document.querySelectorAll('.tab').forEach((t, i) => {
            const tabName = ['majors','prerequisites','subjects'][i];
            t.classList.toggle('active', tabName === tab);
        });
        document.getElementById('majorsTab').style.display        = tab === 'majors' ? 'block' : 'none';
        document.getElementById('prerequisitesTab').style.display = tab === 'prerequisites' ? 'block' : 'none';
        document.getElementById('subjectsTab').style.display      = tab === 'subjects' ? 'block' : 'none';
        if (tab === 'prerequisites') loadPrereqSets();
    }

    /* -- MAJOR MODAL ---------------------------------------------- */
    function showMajorModal(id = 0) {
        document.getElementById('majorModal').classList.add('active');
        document.getElementById('majorModalTitle').textContent = id ? 'Edit Major' : 'Add Major';
        document.getElementById('majorId').value = id;
        if (id) {
            const m = majorsData.find(m => m.id == id);
            if (m) {
                document.getElementById('majorName').value    = m.major_name;
                document.getElementById('displayName').value  = m.display_name;
                document.getElementById('majorDesc').value    = m.description || '';
                document.getElementById('majorIcon').value    = m.icon_class;
                document.getElementById('gradientFrom').value = m.gradient_from;
                document.getElementById('gradientTo').value   = m.gradient_to;
                document.getElementById('majorActive').value  = m.is_active ? '1' : '0';
            }
        } else {
            document.getElementById('majorForm').reset();
            document.getElementById('gradientFrom').value = '#d4a843';
            document.getElementById('gradientTo').value   = '#8B6914';
        }
    }
    function closeMajorModal() { document.getElementById('majorModal').classList.remove('active'); }
    function editMajor(id)     { showMajorModal(id); }

    function saveMajor(e) {
        e.preventDefault();
        const fd = new FormData(document.getElementById('majorForm'));
        const isEdit = document.getElementById('majorId').value && document.getElementById('majorId').value !== '0';
        fd.append('action', isEdit ? 'update_major' : 'add_major');
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
            .then(r => r.json()).then(d => {
                toast(d.message, d.success ? 'success' : 'error');
                if (d.success) { closeMajorModal(); setTimeout(() => location.reload(), 800); }
            });
    }

    function deleteMajor(id) {
        if (!confirm('Delete this major? All subject associations will also be removed.')) return;
        const fd = new FormData();
        fd.append('action','delete_major'); fd.append('id',id);
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
            .then(r => r.json()).then(d => {
                toast(d.message, d.success ? 'success' : 'error');
                if (d.success) setTimeout(() => location.reload(), 800);
            });
    }

    /* -- DETAIL / MAJOR SUBJECTS MODAL --------------------------- */
    function viewMajorSubjects(majorId, majorName) {
        currentMajorId = majorId;
        document.getElementById('detailModalTitle').textContent = majorName + ' – Subjects';
        document.getElementById('detailModal').classList.add('active');
        loadMajorSubjects(majorId);
    }
    function closeDetailModal() { document.getElementById('detailModal').classList.remove('active'); }

    function loadMajorSubjects(majorId) {
        const container = document.getElementById('majorSubjectsList');
        container.innerHTML = '<div style="text-align:center;padding:30px;"><i class="fas fa-spinner fa-spin" style="font-size:24px;color:var(--gold-dark);"></i></div>';
        const fd = new FormData();
        fd.append('action','get_major_subjects'); fd.append('major_id',majorId);
        
        const fd2 = new FormData();
        fd2.append('action','get_prereq_sets'); fd2.append('major_id',majorId);
        
        Promise.all([
            fetch('../../../data/major_process.php', { method:'POST', body:fd }).then(r => r.json()),
            fetch('../../../data/major_process.php', { method:'POST', body:fd2 }).then(r => r.json())
        ]).then(([data, prereqData]) => {
            if (!data.success || data.subjects.length === 0) {
                container.innerHTML = '<div style="text-align:center;padding:40px;color:var(--light-text);"><i class="fas fa-inbox" style="font-size:48px;opacity:0.2;display:block;margin-bottom:12px;"></i><p>No subjects assigned yet.</p></div>';
                document.getElementById('majorPrereqPanel').innerHTML = '';
                return;
            }
            const byYear = {};
            data.subjects.forEach(s => { 
                const y = s.year_level||'1st Year'; 
                if(!byYear[y]) byYear[y]={};
                const sem = s.semester || '1st Semester';
                if(!byYear[y][sem]) byYear[y][sem]=[];
                byYear[y][sem].push(s);
            });
            let html = '';
            const yearOrder = ['1st Year','2nd Year','3rd Year','4th Year','Bridging'];
            yearOrder.forEach(year => {
                if (!byYear[year]) return;
                html += `<div class="year-header" style="margin-top:16px;"><i class="fas fa-calendar-alt" style="font-size:12px;"></i>${year}</div>`;
                const semOrder = ['1st Semester','2nd Semester'];
                semOrder.forEach(sem => {
                    if (!byYear[year][sem] || byYear[year][sem].length === 0) return;
                    html += `<div style="padding-left:20px;margin-top:8px;font-size:12px;color:var(--gold-dark);font-weight:600;">${sem} <span style="font-weight:400;opacity:0.7;">(${byYear[year][sem].length})</span></div>`;
                    byYear[year][sem].forEach(s => {
                    const isPrereq = prereqData.success && prereqData.sets && prereqData.sets.some(set => 
                        set.subjects && set.subjects.some(subj => subj.id === s.id)
                    );
                    html += `
                    <div class="subject-row ${isPrereq?'prerequisite':''}">
                        <div class="subject-icon"><i class="${s.icon_class||'fas fa-book'}"></i></div>
                        <div class="subject-info">
                            <div class="subject-name">${escHtml(s.subject_code)} – ${escHtml(s.subject_name)}</div>
                            <div class="subject-meta"><i class="fas fa-layer-group" style="font-size:10px;"></i> ${escHtml(s.semester||'')} &nbsp;–&nbsp; ${parseFloat(s.units)||0} Units</div>
                        </div>
                        <span class="subject-badge ${isPrereq?'badge-prereq':'badge-required'}">
                            ${isPrereq?'<i class="fas fa-star"></i> Prerequisite':'<i class="fas fa-check"></i> Required'}
                        </span>
                        <button type="button" class="btn-icon btn-star ${isPrereq?'active':''}" onclick="togglePrerequisite(${majorId},${s.id},${isPrereq?'false':'true'})" title="Toggle prerequisite" style="margin-left:8px;"><i class="fas fa-star"></i></button>
                    </div>`;
                    });
                });
            });
            container.innerHTML = html;

            if (prereqData.success && prereqData.sets && prereqData.sets.length > 0) {
                let pHtml = `<div class="prereq-panel"><div class="prereq-panel-title"><i class="fas fa-sitemap"></i> Prerequisite Chain</div>`;
                let chainItems = [];
                prereqData.sets.forEach(set => {
                    const setMajorId = parseInt(set.major_id);
                    if (setMajorId === majorId) {
                        if (set.target_subject) {
                            chainItems.push({ type: 'target', code: set.code, target: set.target_subject, prerequisites: set.subjects || [] });
                        } else if (set.subjects && set.subjects.length > 0) {
                            chainItems.push({ type: 'group', code: set.code, subjects: set.subjects });
                        }
                    }
                });
                if (chainItems.length === 0) {
                    pHtml += '<div style="color:var(--light-text);font-size:12px;padding:8px;">No prerequisite chains defined for this major</div>';
                } else {
                    chainItems.forEach((item, i) => {
                        if (item.type === 'target') {
                            pHtml += `<div class="prereq-chain-item" style="background:linear-gradient(135deg,#fef2f2,#fff5f5);border-left:3px solid #dc2626;">`;
                            pHtml += `<div style="font-weight:700;color:#dc2626;font-size:13px;"><i class="fas fa-bullseye"></i> ${escHtml(item.code)}</div>`;
                            if (item.prerequisites && item.prerequisites.length > 0) {
                                pHtml += `<div style="margin-top:6px;font-size:11px;color:var(--light-text);">Must pass first:</div>`;
                                item.prerequisites.forEach(p => {
                                    pHtml += `<div style="font-size:12px;margin-top:4px;padding-left:8px;border-left:2px solid var(--gold-primary);"><strong>${escHtml(p.subject_code)}</strong> – ${escHtml(p.subject_name)}</div>`;
                                });
                            }
                            pHtml += `</div>`;
                        } else {
                            pHtml += `<div class="prereq-chain-item" style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border-left:3px solid #f59e0b;">`;
                            pHtml += `<div style="font-weight:700;color:#92400e;font-size:13px;"><i class="fas fa-layer-group"></i> ${escHtml(item.code)}</div>`;
                            if (item.subjects && item.subjects.length > 0) {
                                pHtml += `<div style="margin-top:6px;font-size:11px;color:var(--light-text);">Prerequisite subjects:</div>`;
                                item.subjects.forEach(s => {
                                    pHtml += `<div style="font-size:12px;margin-top:4px;padding-left:8px;border-left:2px solid var(--gold-primary);"><strong>${escHtml(s.subject_code)}</strong> – ${escHtml(s.subject_name)} <span style="color:#6b7280;font-size:10px;">${s.units} units</span></div>`;
                                });
                            }
                            pHtml += `</div>`;
                        }
                        if (i < chainItems.length - 1) pHtml += `<div class="prereq-chain-arrow"><i class="fas fa-arrow-down" style="font-size:10px;"></i></div>`;
                    });
                }
                pHtml += `</div>`;
                document.getElementById('majorPrereqPanel').innerHTML = pHtml;
            } else {
                document.getElementById('majorPrereqPanel').innerHTML = '';
            }
        }).catch(err => {
            console.error('Error loading major subjects:', err);
            container.innerHTML = '<div style="text-align:center;padding:40px;color:var(--light-text);"><i class="fas fa-exclamation-triangle" style="font-size:48px;opacity:0.2;display:block;margin-bottom:12px;"></i><p>Error loading subjects.</p></div>';
        });
    }

    function showAddSubjectToMajor() {
        document.getElementById('addMajorId').value = currentMajorId;
        const sel = document.getElementById('addSubjectId');
        sel.innerHTML = '<option value="">Choose a subject...</option>';
        subjectsData.forEach(s => sel.innerHTML += `<option value="${s.id}" data-year="${s.default_year_level||''}" data-sem="${s.default_semester||''}">${escHtml(s.subject_code)} – ${escHtml(s.subject_name)}</option>`);
        document.getElementById('addSubjectModal').classList.add('active');
    }
    function closeAddSubjectModal() { document.getElementById('addSubjectModal').classList.remove('active'); }

    function updateDefaultYearSem() {
        const opt = document.getElementById('addSubjectId').selectedOptions[0];
        if (opt && opt.getAttribute('data-year')) document.getElementById('addYearLevel').value = opt.getAttribute('data-year');
        if (opt && opt.getAttribute('data-sem'))  document.getElementById('addSemester').value  = opt.getAttribute('data-sem');
    }

    function saveMajorSubject(e) {
        e.preventDefault();
        const fd = new FormData(document.getElementById('addSubjectForm'));
        fd.append('action','add_major_subject'); fd.append('is_required','true'); fd.append('is_prerequisite','false');
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
        .then(r => r.json()).then(d => {
            toast(d.message, d.success ? 'success' : 'error');
            if (d.success) {
                closeAddSubjectModal();
                loadMajorSubjects(currentMajorId);
                refreshSubjectsData();
                if (document.getElementById('prospectusMajorSelect').value == currentMajorId) loadProspectus();
            }
        });
    }

    function removeMajorSubject(majorId, subjectId) {
        if (!confirm('Remove this subject from the major?')) return;
        const fd = new FormData();
        fd.append('action','remove_major_subject'); fd.append('major_id',majorId); fd.append('subject_id',subjectId);
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
            .then(r => r.json()).then(d => {
                toast(d.message, d.success ? 'success' : 'error');
                if (d.success) {
                    loadMajorSubjects(majorId);
                    if (document.getElementById('prospectusMajorSelect').value == majorId) loadProspectus();
                }
            });
    }

    function togglePrerequisite(majorId, subjectId, isPrereq) {
        const fd = new FormData();
        fd.append('action','update_major_subject_flag'); fd.append('major_id',majorId);
        fd.append('subject_id',subjectId); fd.append('is_prerequisite',isPrereq);
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
            .then(r => r.json()).then(d => {
                toast(d.message, d.success ? 'success' : 'error');
                if (d.success) loadMajorSubjects(majorId);
            });
    }

    /* Edit placement in prospectus */
    function openEditProspectus(majorId, subjectId, subjectName, yearLevel, semester) {
        document.getElementById('editProspectusMajorId').value   = majorId;
        document.getElementById('editProspectusSubjectId').value = subjectId;
        document.getElementById('editProspectusSubjectName').value = subjectName;
        document.getElementById('editProspectusYearLevel').value = yearLevel || '1st Year';
        document.getElementById('editProspectusSemester').value  = semester  || '1st Semester';
        document.getElementById('editProspectusSubjectModal').classList.add('active');
    }
    function closeEditProspectusModal() { document.getElementById('editProspectusSubjectModal').classList.remove('active'); }

    function saveProspectusEdit(e) {
        e.preventDefault();

        const dbSubjectId  = document.getElementById('editProspectusDbSubjectId').value;
        const subjectCode  = document.getElementById('editProspectusSubjectCode').value.trim();
        const subjectName  = document.getElementById('editProspectusSubjectName').value.trim();
        const units        = document.getElementById('editProspectusUnits').value;
        const yearLevel    = document.getElementById('editProspectusYearLevel').value;
        const semester     = document.getElementById('editProspectusSemester').value;
        const majorSubjectId = document.getElementById('editProspectusSubjectId').value;
        const majorId      = document.getElementById('editProspectusMajorId').value;

        if (!subjectCode || !subjectName) {
            toast('Subject code and title are required.', 'error');
            return;
        }

        // Step 1: always update subject_code, subject_name, units in subjects table
        const fdSubject = new FormData();
        fdSubject.append('action', 'update_subject');
        fdSubject.append('id', dbSubjectId);
        fdSubject.append('subject_code', subjectCode);
        fdSubject.append('subject_name', subjectName);
        fdSubject.append('units', units);
        fdSubject.append('default_year_level', yearLevel);
        fdSubject.append('default_semester', semester);

        // Step 2: update placement in major_subjects (only when we have a real major_subject row)
        const updatePlacement = majorSubjectId > 0 && majorId > 0;

        const requests = [fetch('../../../data/major_process.php', { method: 'POST', body: fdSubject })];

        if (updatePlacement) {
            const fdPlace = new FormData();
            fdPlace.append('action', 'update_major_subject_placement');
            fdPlace.append('subject_id', majorSubjectId);
            fdPlace.append('major_id', majorId);
            fdPlace.append('db_subject_id', dbSubjectId);
            fdPlace.append('year_level', yearLevel);
            fdPlace.append('semester', semester);
            fdPlace.append('units', units);
            requests.push(fetch('../../../data/major_process.php', { method: 'POST', body: fdPlace }));
        }

        Promise.all(requests)
            .then(responses => Promise.all(responses.map(r => r.json())))
            .then(results => {
                const failed = results.find(d => !d.success);
                if (failed) {
                    toast(failed.message, 'error');
                } else {
                    toast('Subject updated successfully.', 'success');
                    refreshSubjectsData();
                    closeEditProspectusModal();
                    loadMajorSubjects(currentMajorId);
                    if (document.getElementById('prospectusMajorSelect').value == currentMajorId) loadProspectusWithCRUD(false);
                }
            })
            .catch(() => toast('Failed to save changes.', 'error'));
    }

    /* -- SUBJECT MODAL -------------------------------------------- */
    function showSubjectModal(id = 0) {
        document.getElementById('subjectModal').classList.add('active');
        document.getElementById('subjectModalTitle').textContent = id ? 'Edit Subject' : 'Add Subject';
        document.getElementById('subjectId').value = id || 0;
        
        // Show/hide bridging_for field based on year level selection
        const yearLevelSelect = document.getElementById('prospectusYearLevel');
        const bridgingGroup = document.getElementById('bridgingForGroup');
        yearLevelSelect.onchange = function() {
            bridgingGroup.style.display = this.value === 'Bridging' ? 'block' : 'none';
        };
        
        if (id) {
            const s = subjectsData.find(s => s.id == id);
            if (s) {
                document.getElementById('subjectCode').value         = s.subject_code;
                document.getElementById('subjectName').value         = s.subject_name;
                document.getElementById('subjectUnits').value        = s.units;
                document.getElementById('subjectPrerequisite').value = s.prerequisite || '';
                const yr = s.default_year_level || s.year_level || '1st Year';
                document.getElementById('prospectusYearLevel').value = yr;
                if (yr === 'Bridging') {
                    bridgingGroup.style.display = 'block';
                    document.getElementById('subjectBridgingFor').value = s.bridging_for || '';
                } else {
                    bridgingGroup.style.display = 'none';
                }
            }
        } else {
            document.getElementById('subjectForm').reset();
            document.getElementById('subjectUnits').value        = 3;
            document.getElementById('prospectusYearLevel').value = '1st Year';
            document.getElementById('prospectusSemester').value  = '1st Semester';
            document.querySelectorAll('.prospectus-major-check').forEach(cb => cb.checked = false);
        }
    }

    function showDuplicateSubjectModal(message, existing) {
        document.getElementById('duplicateSubjectMsg').textContent =
            message + ' You can view and edit the existing subject below.';
        const btn = document.getElementById('duplicateSubjectEditBtn');
        // Clone to remove any old event listener
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        newBtn.addEventListener('click', function() {
            closeDuplicateSubjectModal();
            closeSubjectModal();
            editProspectusSubject(
                0,
                existing.subject_code      || '',
                existing.subject_name      || '',
                existing.default_year_level || existing.year_level || '1st Year',
                existing.default_semester   || existing.semester   || '1st Semester',
                existing.units              || '3',
                existing.id
            );
        });
        document.getElementById('duplicateSubjectModal').classList.add('active');
    }
    function closeDuplicateSubjectModal() {
        document.getElementById('duplicateSubjectModal').classList.remove('active');
    }

    function closeSubjectModal() { document.getElementById('subjectModal').classList.remove('active'); }

    function saveSubject(e) {
        e.preventDefault();
        const subId = document.getElementById('subjectId').value;
        const isEdit = subId && subId !== '0';
        const fd = new FormData(document.getElementById('subjectForm'));
        fd.append('action', isEdit ? 'update_subject' : 'add_subject');
        if (isEdit) fd.append('id', subId);
        
        // Map form field names to expected PHP names
        const yearLevel = document.getElementById('prospectusYearLevel').value || '1st Year';
        const semester = document.getElementById('prospectusSemester').value || '1st Semester';
        fd.append('default_year_level', yearLevel);
        fd.append('default_semester', semester);
        
        const bridgingFor = document.getElementById('subjectBridgingFor').value || '';
        if (bridgingFor) fd.append('bridging_for', bridgingFor);

        const selectedMajors = Array.from(document.querySelectorAll('.prospectus-major-check:checked')).map(cb => cb.value);

        fetch('../../../data/major_process.php', { method:'POST', body:fd })
        .then(r => r.json()).then(d => {
            if (d.success) {
                toast(d.message, 'success');
                closeSubjectModal();
                refreshSubjectsData();
                if (selectedMajors.length > 0 && d.subject_id) {
                    const yearLevel = document.getElementById('prospectusYearLevel').value || '1st Year';
                    const semester  = document.getElementById('prospectusSemester').value  || '1st Semester';
                    Promise.all(selectedMajors.map(mId => {
                        const mfd = new FormData();
                        mfd.append('action','add_major_subject'); mfd.append('major_id',mId);
                        mfd.append('subject_id',d.subject_id); mfd.append('year_level',yearLevel);
                        mfd.append('semester',semester); mfd.append('is_required','true'); mfd.append('is_prerequisite','false');
                        return fetch('../../../data/major_process.php', { method:'POST', body:mfd });
                    })).then(() => {
                        if (selectedMajors.includes(document.getElementById('prospectusMajorSelect').value)) loadProspectus();
                    });
                }
                if (document.getElementById('prerequisitesTab').style.display !== 'none') loadPrereqSets();
                if (document.getElementById('bridgingModal').classList.contains('active')) {
                    showBridgingModal();
                }
            } else {
                // Duplicate subject code — find existing subject and open its edit modal
                const isDuplicate = d.duplicate === true || (d.message && d.message.toLowerCase().includes('already exists'));
                if (isDuplicate) {
                    // Prefer server-returned existing subject; fall back to local subjectsData
                    const existing = d.existing_subject
                        || subjectsData.find(s => (s.subject_code || '').toUpperCase() === (document.getElementById('subjectCode').value || '').trim().toUpperCase());
                    if (existing) {
                        showDuplicateSubjectModal(d.message, existing);
                        return;
                    }
                }
                toast(d.message, 'error');
            }
        });
    }

    /* -- PREREQUISITES SETS --------------------------------------- */
    let prereqSetsData = [];

    function showCreatePrereqModal() {
        document.getElementById('prereqSetCode').value = '';
        document.getElementById('prereqMajorSelect').value = '';
        document.getElementById('prereqTargetSubjectSelect').innerHTML = '<option value="">– Optional: Select target subject –</option>';
        document.getElementById('prereqSubjectsContainer').innerHTML = '<div style="text-align:center;padding:20px;color:var(--light-text);">Select a major first...</div>';
        const majorSel = document.getElementById('prereqMajorSelect');
        majorSel.innerHTML = '<option value="">– Select Major –</option>';
        majorsData.forEach(m => {
            majorSel.innerHTML += `<option value="${m.id}">${escHtml(m.display_name)}</option>`;
        });
        document.getElementById('createPrereqModal').classList.add('active');
    }

    function closeCreatePrereqModal() {
        document.getElementById('createPrereqModal').classList.remove('active');
    }

    function loadPrereqSubjectsForSelection() {
        const container = document.getElementById('prereqSubjectsContainer');
        const majorId = document.getElementById('prereqMajorSelect').value;
        const targetSel = document.getElementById('prereqTargetSubjectSelect');
        targetSel.innerHTML = '<option value="">– Optional: Select target subject –</option>';
        if (!majorId) {
            container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--light-text);">Select a major first...</div>';
            return;
        }
        container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--light-text);"><i class="fas fa-spinner fa-spin"></i> Loading subjects...</div>';
        const fd = new FormData();
        fd.append('action', 'get_major_subjects');
        fd.append('major_id', majorId);
        
        Promise.all([
            fetch('../../../data/major_process.php', { method: 'POST', body: fd }).then(r => r.json()),
            fetch('../../../data/major_process.php', { method: 'POST', body: (() => { const f = new FormData(); f.append('action', 'get_prereq_sets'); return f; })() }).then(r => r.json())
        ]).then(([subjectData, prereqData]) => {
            let usedSubjectIds = [];
            
            // Get subject IDs from ALL prerequisite sets (global, not per major)
            if (prereqData && prereqData.success && Array.isArray(prereqData.sets)) {
                prereqData.sets.forEach(set => {
                    if (set && set.subjects && Array.isArray(set.subjects)) {
                        set.subjects.forEach(s => { 
                            if (s && s.id) usedSubjectIds.push(Number(s.id)); 
                        });
                    }
                });
            }
            
            // Populate target subject dropdown with all subjects for this major
            const targetSel = document.getElementById('prereqTargetSubjectSelect');
            if (subjectData && subjectData.success && Array.isArray(subjectData.subjects)) {
                const allSubjects = subjectData.subjects;
                allSubjects.forEach(s => {
                    targetSel.innerHTML += `<option value="${s.id}">${escHtml(s.subject_code)} – ${escHtml(s.subject_name)}</option>`;
                });
            }
            
            // Filter out subjects that are already in any prerequisite set
            if (subjectData && subjectData.success && Array.isArray(subjectData.subjects)) {
                const availableSubjects = subjectData.subjects.filter(s => s && s.id && !usedSubjectIds.includes(Number(s.id)));
                
                if (availableSubjects.length === 0) {
                    container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--light-text);">All subjects already assigned to a prerequisite set</div>';
                } else {
                    renderPrereqSubjectsByYear(availableSubjects);
                }
            } else {
                container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--light-text);">No subjects available</div>';
            }
        }).catch(() => {
            container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--light-text);">Failed to load subjects</div>';
        });
    }

    function renderPrereqSubjectsByYear(subjects) {
        const container = document.getElementById('prereqSubjectsContainer');
        const years = ['1st Year', '2nd Year', '3rd Year', '4th Year', 'Bridging'];
        let html = '';
        years.forEach(year => {
            const yearSubjects = subjects.filter(s => {
                const y = s.year_level || s.default_year_level || '1st Year';
                return y === year;
            });
            if (yearSubjects.length === 0) {
                // Show empty section header to make it clear all years exist
                html += `<div class="year-header" style="opacity:${year === 'Bridging' ? 0.5 : 0.3};"><i class="fas fa-calendar-alt"></i> ${year}</div>`;
                html += `<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">`;
                html += `<div><div style="font-size:11px;font-weight:700;color:var(--gold-dark);margin-bottom:8px;padding-left:4px;">1st Semester</div><div style="grid-column:span 2;text-align:center;padding:12px;color:var(--light-text);font-size:12px;">No subjects</div></div>`;
                html += `<div><div style="font-size:11px;font-weight:700;color:var(--gold-dark);margin-bottom:8px;padding-left:4px;">2nd Semester</div><div style="grid-column:span 2;text-align:center;padding:12px;color:var(--light-text);font-size:12px;">No subjects</div></div>`;
                html += `</div>`;
                return;
            }
            
            let sem1Subjects = yearSubjects.filter(s => {
                const sem = s.semester || '';
                return !sem || sem === '1st Semester' || sem.toLowerCase().includes('1st');
            });
            let sem2Subjects = yearSubjects.filter(s => {
                const sem = s.semester || '';
                return sem && (sem === '2nd Semester' || sem.toLowerCase().includes('2nd'));
            });
            
            html += `<div class="year-header"><i class="fas fa-calendar-alt"></i> ${year}</div>`;
            html += `<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">`;
            
            html += `<div>`;
            html += `<div style="font-size:11px;font-weight:700;color:var(--gold-dark);margin-bottom:8px;padding-left:4px;">1st Semester</div>`;
            html += `<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">`;
            if (sem1Subjects.length > 0) {
                sem1Subjects.forEach(s => { html += renderPrereqSubjectCheck(s); });
            } else {
                html += `<div style="grid-column:span 2;text-align:center;padding:12px;color:var(--light-text);font-size:12px;">No subjects</div>`;
            }
            html += `</div></div>`;
            
            html += `<div>`;
            html += `<div style="font-size:11px;font-weight:700;color:var(--gold-dark);margin-bottom:8px;padding-left:4px;">2nd Semester</div>`;
            html += `<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">`;
            if (sem2Subjects.length > 0) {
                sem2Subjects.forEach(s => { html += renderPrereqSubjectCheck(s); });
            } else {
                html += `<div style="grid-column:span 2;text-align:center;padding:12px;color:var(--light-text);font-size:12px;">No subjects</div>`;
            }
            html += `</div></div>`;
            
            html += `</div>`;
        });
        container.innerHTML = html || '<div style="text-align:center;padding:20px;color:var(--light-text);">No subjects available</div>';
    }

    function renderPrereqSubjectCheck(subject) {
        const code = escHtml(subject.subject_code);
        const name = escHtml(subject.subject_name);
        return `<label style="display:grid;grid-template-columns:20px 1fr auto;gap:6px;align-items:center;padding:8px 10px;background:var(--cream);border-radius:8px;cursor:pointer;transition:all 0.15s;border:1px solid var(--border-light);font-size:12px;" onmouseover="this.style.background='#e5e3dd';this.style.borderColor='var(--gold-light)';" onmouseout="this.style.background='var(--cream)';this.style.borderColor='var(--border-light)';">
            <input type="checkbox" class="prereq-subject-check" value="${subject.id}" data-code="${code}" data-name="${name}" style="width:14px;height:14px;accent-color:var(--gold-dark);margin:0;">
            <div style="min-width:0;">
                <div style="font-weight:600;color:var(--dark-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${code}</div>
                <div style="font-size:10px;color:var(--light-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${name}</div>
            </div>
            <div style="font-size:10px;color:var(--light-text);text-align:right;white-space:nowrap;">${parseFloat(subject.units) || 0} units</div>
        </label>`;
    }

    function savePrereqSet() {
        const code = document.getElementById('prereqSetCode').value.trim();
        const majorId = document.getElementById('prereqMajorSelect').value;
        const targetSubjectId = document.getElementById('prereqTargetSubjectSelect').value;
        if (!code) { toast('Please enter a Prerequisites Code', 'error'); return; }
        if (!majorId) { toast('Please select a Major', 'error'); return; }
        const checkboxes = document.querySelectorAll('.prereq-subject-check:checked');
        const selectedSubjects = Array.from(checkboxes).map(cb => ({
            id: cb.value,
            code: cb.getAttribute('data-code'),
            name: cb.getAttribute('data-name')
        }));
        if (selectedSubjects.length === 0) { toast('Please select at least one subject', 'error'); return; }
        const fd = new FormData();
        fd.append('action', 'create_prereq_set');
        fd.append('prereq_code', code);
        fd.append('major_id', majorId);
        fd.append('subject_ids', JSON.stringify(selectedSubjects.map(s => s.id)));
        fd.append('target_subject_id', targetSubjectId);
        fetch('../../../data/major_process.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            toast(d.message || 'Prerequisites created successfully', d.success ? 'success' : 'error');
            if (d.success) {
                closeCreatePrereqModal();
                loadPrereqSets();
            }
        })
        .catch(() => { toast('Failed to create prerequisites', 'error'); });
    }

    function loadPrereqSets() {
        const fd = new FormData();
        fd.append('action', 'get_prereq_sets');
        fetch('../../../data/major_process.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                prereqSetsData = d.sets || [];
                renderPrereqSets();
            }
        })
        .catch(() => {});
    }

    function renderPrereqSets() {
        const container = document.getElementById('prereqSetsContainer');
        if (prereqSetsData.length === 0) {
            container.innerHTML = `<div style="text-align:center;padding:40px;color:var(--light-text);">
                <div style="font-size:48px;opacity:0.2;margin-bottom:12px;"><i class="fas fa-sitemap"></i></div>
                <p style="font-weight:600;">No prerequisites created yet</p>
                <p style="font-size:13px;margin-top:4px;">Click "Create Prerequisites" to add a new set</p>
            </div>`;
            return;
        }
        const byMajor = {};
        prereqSetsData.forEach(set => {
            const m = set.major_name || 'Unknown Major';
            if (!byMajor[m]) byMajor[m] = [];
            byMajor[m].push(set);
        });
        const majorOrder = Object.keys(byMajor).sort();
        let html = '';
        majorOrder.forEach(major => {
            html += `<div style="background:linear-gradient(135deg, var(--gold-dark), var(--gold));border-radius:12px;padding:16px;margin-bottom:20px;page-break-inside:avoid;">
                <div style="color:white;font-size:18px;font-weight:700;">${escHtml(major)}</div>
                <div style="color:rgba(255,255,255,0.8);font-size:13px;margin-top:4px;">${byMajor[major].length} prerequisite set${byMajor[major].length > 1 ? 's' : ''}</div>
            </div>`;
            byMajor[major].forEach(set => {
                let subjectsGrid = '';
                if (set.subjects && set.subjects.length > 0) {
                    subjectsGrid += `<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;padding:12px;">`;
                    set.subjects.forEach(s => {
                        subjectsGrid += `<div style="background:white;border:1px solid var(--border-light);border-radius:8px;padding:10px;font-size:12px;">
                            <div style="font-weight:700;color:var(--dark-text);margin-bottom:4px;">${escHtml(s.subject_code)}</div>
                            <div style="color:var(--light-text);font-size:11px;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(s.subject_name)}</div>
                            <div style="background:var(--cream);padding:4px 8px;border-radius:4px;font-size:10px;color:var(--gold-dark);font-weight:600;text-align:center;">${parseFloat(s.units)||0} units</div>
                        </div>`;
                    });
                    subjectsGrid += `</div>`;
                }
                let targetHtml = '';
                if (set.target_subject) {
                    targetHtml = `<div style="padding:8px 16px;background:#fef3c7;border-top:1px solid #fbbf24;display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-arrow-right" style="color:#92400e;font-size:12px;"></i>
                        <span style="font-size:12px;color:#92400e;font-weight:600;">Required for:</span>
                        <span style="background:white;border:1px solid #fbbf24;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:700;color:#92400e;">${escHtml(set.target_subject.subject_code)}</span>
                        <span style="font-size:11px;color:#666;">${escHtml(set.target_subject.subject_name)}</span>
                    </div>`;
                }
                html += `<div style="background:var(--cream);border-radius:12px;padding:0;margin-bottom:20px;border:1px solid var(--border-light);page-break-inside:avoid;">
                    <div style="padding:12px 16px;border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;align-items:center;">
                        <div style="font-size:15px;font-weight:700;color:var(--dark-text);">${escHtml(set.code)}</div>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <span style="background:var(--gold);color:white;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;">${set.subject_count || 0} subjects</span>
                            <button type="button" class="btn-sm btn-sm-red" onclick="deletePrereqSet(${set.id})"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    ${subjectsGrid}
                    ${targetHtml}
                </div>`;
            });
        });
        container.innerHTML = html;
    }

    function viewPrereqSet(setId) {
        const set = prereqSetsData.find(s => s.id === setId);
        if (!set) return;
        let html = `<div style="padding:20px;max-height:500px;overflow-y:auto;">
            <div style="margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--border-light);">
                <div style="font-size:18px;font-weight:700;color:var(--dark-text);">${escHtml(set.code)}</div>
                <div style="font-size:14px;color:var(--gold-dark);font-weight:600;">${escHtml(set.major_name || 'Unknown Major')}</div>
            </div>`;
        if (set.subjects && set.subjects.length > 0) {
            html += `<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;padding:0 12px;">`;
            set.subjects.forEach(s => {
                html += `<div style="background:white;border:1px solid var(--border-light);border-radius:8px;padding:10px;font-size:12px;">
                    <div style="font-weight:700;color:var(--dark-text);margin-bottom:4px;">${escHtml(s.subject_code)}</div>
                    <div style="color:var(--light-text);font-size:11px;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(s.subject_name)}</div>
                    <div style="background:var(--cream);padding:4px 8px;border-radius:4px;font-size:10px;color:var(--gold-dark);font-weight:600;text-align:center;">${parseFloat(s.units)||0} units</div>
                </div>`;
            });
            html += `</div>`;
        } else {
            html += `<div style="text-align:center;padding:20px;color:var(--light-text);">No subjects in this set</div>`;
        }
        html += `</div>`;
        document.getElementById('prereqSetsContainer').innerHTML = html;
    }

    function deletePrereqSet(setId) {
        if (!confirm('Delete this prerequisite set?')) return;
        const fd = new FormData();
        fd.append('action', 'delete_prereq_set');
        fd.append('set_id', setId);
        fetch('../../../data/major_process.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            toast(d.message || 'Prerequisites set deleted', d.success ? 'success' : 'error');
            if (d.success) loadPrereqSets();
        })
        .catch(() => { toast('Failed to delete', 'error'); });
    }

    /* -- REFRESH SUBJECTS DATA ------------------------------------ */
    function refreshSubjectsData() {
        const fd = new FormData();
        fd.append('action','get_all_subjects');
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
        .then(r => r.json()).then(d => {
            if (d.success && d.subjects) {
                subjectsData = d.subjects;
                const prereqSel = document.getElementById('subjectPrerequisite');
                if (prereqSel) {
                    prereqSel.innerHTML = '<option value="">– None –</option>';
                    d.subjects.forEach(s => {
                        prereqSel.innerHTML += `<option value="${escHtml(s.subject_code)}">${escHtml(s.subject_code)} – ${escHtml(s.subject_name)}</option>`;
                    });
                }
            }
        });
    }

    /* -- PROSPECTUS RENDERING ------------------------------------- */
    function buildProspectusHeader(majorName) {
    const schoolName   = phSettings && phSettings.school_name    || 'Northern Bukidnon State College';
    const schoolAddress = phSettings && phSettings.school_address || 'Manolo Fortich, Bukidnon';
    const instituteName = phSettings && phSettings.institute_name || 'Institute for Business Management';
    const degreeName   = phSettings && phSettings.degree_name    || 'Bachelor of Science in Business Administration';

    return `
    <div class="pro-header">
        <img src="../../../media/LOGO.jpg" alt="School Logo" class="pro-logo">
        <div class="pro-title-block">
            <div class="pro-school">${escHtml(schoolName)}</div>
            <div class="pro-address">${escHtml(schoolAddress)}</div>
            <div style="border-top:1px solid #d4cfc5;margin:4px auto;width:80%;"></div>
            <div class="pro-institute">${escHtml(instituteName)}</div>
            <div class="pro-degree">${escHtml(degreeName)}</div>
            <div class="pro-major">Major in <strong>${escHtml(majorName)}</strong></div>
            <div class="pro-label">&#9733; Student Evaluation Prospectus &#9733;</div>
        </div>
        <img src="../../../media/nbsc_logo.png" alt="Institute Logo" class="pro-logo">
    </div>`;
}

    function buildSemTable(subjects, semLabel, prereqMap = {}, yearLabel = '') {
        let rows = '', total = 0;
        const isSem1 = semLabel.includes('1st');
        const unitsLabel = isSem1 ? 'LEC' : 'UNITS';
        const displayLabel = yearLabel ? (yearLabel.toUpperCase() + ' - ' + (isSem1 ? 'First Semester' : 'Second Semester')) : semLabel;
        if (!subjects || subjects.length === 0) {
            rows = `<tr><td colspan="5" class="pro-empty">No subjects</td></tr>`;
        } else {
            subjects.forEach(s => {
                const u = parseFloat(s.units) || 0;
                total += u;
                const prereqCode = prereqMap[s.id] || s.prerequisite || '';
                rows += `<tr>
                    <td class="pro-grade-cell"></td>
                    <td class="pro-code">${escHtml(s.subject_code||'')}</td>
                    <td>${escHtml(s.subject_name||'')}</td>
                    <td class="pro-prereq-col pro-prereq-screen">${prereqCode ? '<span class="pro-star">★</span> '+escHtml(prereqCode) : '–'}</td>
                    <td class="pro-units">${u%1===0?u:u.toFixed(1)}</td>
                </tr>`;
            });
        }
        const t = total%1===0 ? total : total.toFixed(1);
        rows += `<tr class="pro-total-row"><td colspan="3" style="text-align:right;padding-right:8px;">TOTAL</td><td class="pro-prereq-col pro-prereq-screen"></td><td class="pro-units">${t}</td></tr>`;
        return `<div>
            <div class="pro-sem-label">${displayLabel}</div>
            <table class="pro-table">
                <thead><tr>
                    <th class="pro-th" style="width:28px;">Final Grade</th>
                    <th class="pro-th" style="width:70px;">Course No.</th>
                    <th class="pro-th">Description</th>
                    <th class="pro-th pro-prereq-screen" style="width:60px;">Pre-Req</th>
                    <th class="pro-th" style="width:40px;">${unitsLabel}</th>
                </tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </div>`;
    }

    function renderProspectus(subjects, majorName, prereqMap = {}) {
        const yearOrder = ['1st Year','2nd Year','3rd Year','4th Year'];
        const grouped = {};
        subjects.forEach(s => {
            const y = s.year_level || '1st Year';
            if (!grouped[y]) grouped[y] = [];
            grouped[y].push(s);
        });

        let yearBlocksHtml = '', grandTotal = 0;
        yearOrder.forEach(y => {
            const all  = grouped[y] || [];
            const sem1 = all.filter(s => !s.semester || s.semester.includes('1st'));
            const sem2 = all.filter(s =>  s.semester && s.semester.includes('2nd'));
            const t = all.reduce((a,s)=>a+(parseFloat(s.units)||0),0);
            grandTotal += t;
            const tFmt = t%1===0?t:t.toFixed(1);
            yearBlocksHtml += `
            <div class="pro-year-block">
                <div class="pro-year-header">
                    <span><i class="fas fa-calendar-alt" style="margin-right:6px;font-size:11px;"></i>${y}</span>
                    <span class="pro-year-total">${tFmt} units</span>
                </div>
                <div class="pro-sem-row">
                    ${buildSemTable(sem1,'1st Semester', prereqMap, y)}
                    ${buildSemTable(sem2,'2nd Semester', prereqMap, y)}
                </div>
            </div>`;
        });

        const bridging = subjects.filter(s => s.year_level === 'Bridging');
        let bridgeHtml = '';
        if (bridging.length > 0) {
            const bt = bridging.reduce((a,s)=>a+(parseFloat(s.units)||0),0);
            bridgeHtml = `
            <div class="pro-bridging-block">
                <div class="pro-year-block">
                    <div class="pro-year-header" style="background: linear-gradient(135deg, #8B6914, #B8860B);">
                        <span><i class="fas fa-exchange-alt" style="margin-right:6px;font-size:11px;"></i>Bridging Subjects</span>
                        <span class="pro-year-total">${bt%1===0?bt:bt.toFixed(1)} units</span>
                    </div>
                    <div style="padding:10px 12px 12px;">
                        <table class="pro-table" style="max-width:620px;">
                            <thead><tr>
                                <th class="pro-th" style="width:24px;">Grade</th>
                                <th class="pro-th" style="width:70px;">Code</th>
                                <th class="pro-th">Subject Title</th>
                                <th class="pro-th" style="width:40px;">Units</th>
                                <th class="pro-th" style="width:100px;">Bridging For</th>
                            </tr></thead>
                            <tbody>
                                ${bridging.map(s=>{
                                    const bridgeFor = escHtml(s.bridging_for || '–');
                                    return `<tr><td class="pro-grade-cell"></td><td class="pro-code">${escHtml(s.subject_code||'')}</td><td>${escHtml(s.subject_name||'')}</td><td class="pro-units">${parseFloat(s.units)||0}</td><td class="pro-prereq-col" style="font-size:9pt;">${bridgeFor}</td></tr>`;
                                }).join('')}
                                <tr class="pro-total-row"><td colspan="3" style="text-align:right;padding-right:8px;">Total Units</td><td class="pro-units">${bt%1===0?bt:bt.toFixed(1)}</td><td></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>`;
        }

        const gt = grandTotal%1===0?grandTotal:grandTotal.toFixed(1);
        document.getElementById('prospectusContent').innerHTML = `
        <div class="pro-wrap" id="printableProspectus">
            ${buildProspectusHeader(majorName)}
            <div class="pro-body">
                ${yearBlocksHtml}
                ${bridgeHtml}
                <div class="pro-grand-total">Grand Total: <strong>${gt} units</strong></div>
                <div class="pro-sig-block">
                    <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-label">Student's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
                    <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-label">Adviser's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
                    <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-label">Program Head's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
                </div>
                <div class="pro-legend"><span class="pro-star">★</span> = Prerequisite subject &nbsp;–&nbsp; <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#fef2f2;border-left:3px solid #dc2626;vertical-align:middle;"></span> = prerequisite row</div>
            </div>
        </div>`;
    }

    function renderEmptyProspectus(majorName) {
        const yearOrder = ['1st Year','2nd Year','3rd Year','4th Year'];
        let yearBlocksHtml = '';
        yearOrder.forEach(y => {
            yearBlocksHtml += `
            <div class="pro-year-block">
                <div class="pro-year-header"><span>${y}</span><span class="pro-year-total">0 units</span></div>
                <div class="pro-sem-row">${buildSemTable([],'1st Semester',{},y)}${buildSemTable([],'2nd Semester',{},y)}</div>
            </div>`;
        });
        document.getElementById('prospectusContent').innerHTML = `
        <div class="pro-wrap" id="printableProspectus">
            ${buildProspectusHeader(majorName)}
            <div class="pro-body">
                ${yearBlocksHtml}
                <div class="pro-grand-total">Grand Total: <strong>0 units</strong></div>
                <div class="pro-sig-block">
                    <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-label">Student's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
                    <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-label">Adviser's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
                    <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-label">Program Head's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
                </div>
            </div>
        </div>`;
    }

    function loadProspectus() {
        const majorId   = document.getElementById('prospectusMajorSelect').value;
        const majorName = document.getElementById('prospectusMajorSelect').selectedOptions[0]?.text || '';
        const container = document.getElementById('prospectusContent');
        document.getElementById('printProspectusBtn').style.display = majorId ? 'flex' : 'none';
        document.getElementById('editModeBtn').style.display = majorId ? 'flex' : 'none';
        document.getElementById('bridgingBtn').style.display = majorId ? 'flex' : 'none';
        
        currentEditMajorId = majorId;
        
        if (editMode && majorId) {
            loadProspectusWithCRUD();
            return;
        }

        if (!majorId) {
            container.innerHTML = `<div style="text-align:center;padding:60px 20px;background:var(--cream);border-radius:12px;border:1px solid var(--border-light);">
                <div style="width:80px;height:80px;border-radius:50%;background:white;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;border:1px solid var(--border-light);"><i class="fas fa-scroll" style="font-size:32px;color:var(--gold-dark);"></i></div>
                <h3 style="font-size:18px;font-weight:700;margin-bottom:8px;">Select a Major</h3>
                <p style="color:var(--light-text);">Choose a major above to generate the prospectus.</p>
            </div>`;
            return;
        }

        container.innerHTML = '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:32px;color:var(--gold-dark);"></i><p style="margin-top:12px;color:var(--light-text);">Loading prospectus–</p></div>';

        const fd = new FormData();
        fd.append('action','get_major_subjects'); fd.append('major_id',majorId);
        const fd2 = new FormData();
        fd2.append('action','get_prereq_sets');
        
        Promise.all([
            fetch('../../../data/major_process.php', { method:'POST', body:fd }).then(r => r.json()),
            fetch('../../../data/major_process.php', { method:'POST', body:fd2 }).then(r => r.json())
        ]).then(([data, prereqData]) => {
            let prereqMap = {};
            if (prereqData.success && prereqData.sets) {
                prereqData.sets.forEach(set => {
                    if (set.major_id == majorId) {
                        if (set.target_subject_id && set.target_subject) {
                            prereqMap[set.target_subject_id] = set.code;
                        }
                        if (set.subjects) {
                            set.subjects.forEach(s => {
                                prereqMap[s.id] = set.code;
                            });
                        }
                    }
                });
            }
            if (!data.success || !data.subjects || data.subjects.length === 0) {
                renderEmptyProspectus(majorName);
            } else {
                renderProspectus(data.subjects, majorName, prereqMap);
            }
        })
        .catch(() => renderEmptyProspectus(majorName));
    }

    function printProspectus() {
    const el = document.getElementById('printableProspectus');
    if (!el) return;

    // Ensure the prospectus tab is visible
    if (document.getElementById('subjectsTab').style.display === 'none') {
        switchTab('subjects');
    }

    window.print();
}

    /* -- BRIDGING SUBJECTS MODAL -------------------------------------------- */
    function showBridgingModal() {
        const majorId = document.getElementById('prospectusMajorSelect').value;
        if (!majorId) { toast('Please select a major first', 'error'); return; }
        
        const modal = document.getElementById('bridgingModal');
        modal.classList.add('active');
        
        const container = document.getElementById('bridgingContent');
        container.innerHTML = '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        
        const fd = new FormData();
        fd.append('action', 'get_major_subjects');
        fd.append('major_id', majorId);
        
        fetch('../../../data/major_process.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (!d.success || !d.subjects) {
                container.innerHTML = '<div style="text-align:center;padding:40px;color:var(--light-text);">No subjects found</div>';
                return;
            }
            
            const bridging = d.subjects.filter(s => s.year_level === 'Bridging');
            
            if (bridging.length === 0) {
                container.innerHTML = `
                <div style="text-align:center;padding:40px;">
                    <div style="font-size:48px;color:var(--gold-dark);margin-bottom:12px;opacity:0.3;"><i class="fas fa-exchange-alt"></i></div>
                    <p style="font-weight:600;">No bridging subjects for this major</p>
                    <p style="font-size:13px;color:var(--light-text);margin-bottom:16px;">Add subjects with "Bridging" year level to appear here</p>
                    <button class="btn-add" style="margin:0 auto;" onclick="showSubjectModal(); document.getElementById('prospectusYearLevel').value='Bridging'; document.getElementById('bridgingForGroup').style.display='block';">
                        <i class="fas fa-plus"></i> Add Bridging Subject
                    </button>
                </div>`;
                return;
            }
            
            let html = `
            <div style="overflow-x:auto;">
                <table class="pro-table" style="min-width:100%;">
                    <thead>
                        <tr>
                            <th class="pro-th" style="width:28px;">Grade</th>
                            <th class="pro-th" style="width:70px;">Code</th>
                            <th class="pro-th">Subject Title</th>
                            <th class="pro-th" style="width:40px;">Units</th>
                            <th class="pro-th" style="width:90px;">Bridging For</th>
                            <th class="pro-th" style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody>`;
            
            bridging.forEach(s => {
                const bridgingFor = escHtml(s.bridging_for || '–');
                html += `
                <tr>
                    <td class="pro-grade-cell"></td>
                    <td class="pro-code">${escHtml(s.subject_code || '')}</td>
                    <td>${escHtml(s.subject_name || '')}</td>
                    <td class="pro-units">${parseFloat(s.units) || 0}</td>
                    <td class="pro-prereq-col">${bridgingFor}</td>
                    <td style="text-align:center;">
                        <button class="btn-crud btn-crud-edit" onclick="editBridgingSubject(${s.id})"><i class="fas fa-edit"></i></button>
                    </td>
                </tr>`;
            });
            
            html += `
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px;text-align:center;">
                <button class="btn-add" onclick="showSubjectModal(); document.getElementById('prospectusYearLevel').value='Bridging'; document.getElementById('bridgingForGroup').style.display='block';">
                    <i class="fas fa-plus"></i> Add Bridging Subject
                </button>
            </div>`;
            
            container.innerHTML = html;
        })
        .catch(() => {
            container.innerHTML = '<div style="text-align:center;padding:40px;color:var(--light-text);">Failed to load subjects</div>';
        });
    }
    
    function closeBridgingModal() {
        document.getElementById('bridgingModal').classList.remove('active');
    }
    
    function editBridgingSubject(subjectId) {
        closeBridgingModal();
        showSubjectModal(subjectId);
    }

    /* -- HELPER --------------------------------------------------- */
    let editMode = false;
    let currentEditMajorId = 0;

    function toggleEditMode() {
        editMode = !editMode;
        const btn = document.getElementById('editModeBtn');
        btn.classList.toggle('active', editMode);
        btn.innerHTML = editMode ? '<i class="fas fa-times"></i> Exit Edit' : '<i class="fas fa-edit"></i> Edit Mode';
        
        if (editMode && currentEditMajorId) {
            loadProspectusWithCRUD();
        } else {
            loadProspectus();
        }
    }

    function loadProspectusWithCRUD(showSpinner = true) {
        const majorId = document.getElementById('prospectusMajorSelect').value;
        if (!majorId) return;
        
        currentEditMajorId = majorId;
        
        // Only show the loading spinner when explicitly requested (initial load, major switch, etc.)
        // Skip spinner for background refreshes like after drag-and-drop reorder
        if (showSpinner) {
            document.getElementById('prospectusContent').innerHTML = '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:32px;color:var(--gold-dark);"></i><p style="margin-top:12px;color:var(--light-text);">Loading prospectus–</p></div>';
        }

        const fd = new FormData();
        fd.append('action','get_major_subjects'); fd.append('major_id',majorId);
        
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.subjects || data.subjects.length === 0) {
                renderEmptyProspectusWithCRUD(document.getElementById('prospectusMajorSelect').selectedOptions[0]?.text || '');
            } else {
const majorName = document.getElementById('prospectusMajorSelect').selectedOptions[0] && document.getElementById('prospectusMajorSelect').selectedOptions[0].text || '';
                renderProspectusWithCRUD(data.subjects, majorName);
                // Initialize drag and drop after rendering
                setTimeout(initDragAndDrop, 200);
            }
        })
        .catch(() => renderEmptyProspectusWithCRUD(document.getElementById('prospectusMajorSelect').selectedOptions[0]?.text || ''));
    }

    function buildSemTableWithCRUD(subjects, semLabel, yearLabel = '') {
        let rows = '', total = 0;
        const isSem1 = semLabel.includes('1st');
        const displayLabel = yearLabel ? (yearLabel.toUpperCase() + ' - ' + (isSem1 ? 'First Semester' : 'Second Semester')) : semLabel;
        if (!subjects || subjects.length === 0) {
            rows = `<tr><td colspan="7" class="pro-empty">No subjects</td></tr>`;
        } else {
            subjects.forEach(s => {
                const u = parseFloat(s.units) || 0;
                total += u;
                const prereqCode = s.prerequisite || '–';
                const prereqDisplay = prereqCode !== '–' ? '<span style="color:#dc2626;font-weight:600;">★ ' + escHtml(prereqCode) + '</span>' : '–';
                rows += `<tr draggable="true" data-major-subject-id="${s.major_subject_id}" data-sort-order="${s.sort_order || 0}" data-year-level="${escHtml(s.year_level||'')}" data-semester="${escHtml(s.semester||'')}">
                    <td class="pro-grade-cell"><span class="drag-handle"><i class="fas fa-grip-vertical"></i></span></td>
                    <td class="pro-code">${escHtml(s.subject_code||'')}</td>
                    <td>${escHtml(s.subject_name||'')}</td>
                    <td class="pro-units">${u%1===0?u:u.toFixed(1)}</td>
                    <td class="pro-prereq-col">${prereqDisplay}</td>
                    <td class="pro-crud-col">
                        <button class="btn-crud btn-crud-edit" onclick="editProspectusSubject(${s.major_subject_id}, '${escJs(s.subject_code||'')}', '${escJs(s.subject_name||'')}', '${escJs(s.year_level||'')}', '${escJs(s.semester||'')}', '${escJs(s.units||'')}', ${s.id})" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn-crud btn-crud-delete" onclick="deleteProspectusSubject(${s.major_subject_id}, '${escJs(s.subject_code||'')}', ${s.id})" title="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
            });
        }
        const t = total%1===0 ? total : total.toFixed(1);
        rows += `<tr class="pro-total-row"><td colspan="3" style="text-align:right;padding-right:8px;">Total Units</td><td class="pro-units">${t}</td><td></td><td></td><td></td></tr>`;
        return `<div>
            <div class="pro-sem-label">${displayLabel}</div>
            <table class="pro-table">
                <thead><tr>
                    <th class="pro-th" style="width:30px;"></th>
                    <th class="pro-th" style="width:80px;">Code</th>
                    <th class="pro-th">Subject Title</th>
                    <th class="pro-th" style="width:40px;">Units</th>
                    <th class="pro-th" style="width:75px;">Pre-Req</th>
                    <th class="pro-th pro-th-crud" style="width:60px;">Action</th>
                </tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </div>`;
    }

    function renderProspectusWithCRUD(subjects, majorName) {
        const yearOrder = ['1st Year','2nd Year','3rd Year','4th Year'];
        const grouped = {};
        subjects.forEach(s => {
            const y = s.year_level || '1st Year';
            if (!grouped[y]) grouped[y] = [];
            grouped[y].push(s);
        });

        let yearBlocksHtml = '', grandTotal = 0;
        yearOrder.forEach(y => {
            const all  = grouped[y] || [];
            const sem1 = all.filter(s => !s.semester || s.semester.includes('1st'));
            const sem2 = all.filter(s =>  s.semester && s.semester.includes('2nd'));
            const t = all.reduce((a,s)=>a+(parseFloat(s.units)||0),0);
            grandTotal += t;
            const tFmt = t%1===0?t:t.toFixed(1);
            yearBlocksHtml += `
            <div class="pro-year-block">
                <div class="pro-year-header">
                    <span><i class="fas fa-calendar-alt" style="margin-right:6px;font-size:11px;"></i>${y}</span>
                    <span class="pro-year-total">${tFmt} units</span>
                </div>
                <div class="pro-sem-row">
                    ${buildSemTableWithCRUD(sem1,'1st Semester',y)}
                    ${buildSemTableWithCRUD(sem2,'2nd Semester',y)}
                </div>
            </div>`;
        });

        const bridging = subjects.filter(s => s.year_level === 'Bridging');
        let bridgeHtml = '';
if (bridging.length > 0) {
            const bt = bridging.reduce((a,s)=>a+(parseFloat(s.units)||0),0);
            bridgeHtml = `
            <div class="pro-bridging-block">
                <div class="pro-year-block">
                    <div class="pro-year-header" style="background: linear-gradient(135deg, #8B6914, #B8860B);">
                        <span><i class="fas fa-exchange-alt" style="margin-right:6px;font-size:11px;"></i>Bridging Subjects</span>
                        <span class="pro-year-total">${bt%1===0?bt:bt.toFixed(1)} units</span>
                    </div>
                    <div style="padding:10px 12px 12px;">
                        <table class="pro-table" style="max-width:540px;">
                            <thead><tr>
                                <th class="pro-th" style="width:30px;"></th>
                                <th class="pro-th" style="width:80px;">Code</th>
                                <th class="pro-th">Subject Title</th>
                                <th class="pro-th" style="width:40px;">Units</th>
                                <th class="pro-th" style="width:75px;">Pre-Req</th>
                                <th class="pro-th pro-th-crud" style="width:60px;">Action</th>
                            </tr></thead>
                            <tbody>
                                ${bridging.map(s=>{
                                    const prereqCode = s.prerequisite || '–';
                                    return `<tr draggable="true" data-major-subject-id="${s.major_subject_id}" data-sort-order="${s.sort_order || 0}" data-year-level="${escHtml(s.year_level||'')}" data-semester="${escHtml(s.semester||'')}">
                                        <td class="pro-grade-cell"><span class="drag-handle"><i class="fas fa-grip-vertical"></i></span></td>
                                        <td class="pro-code">${escHtml(s.subject_code||'')}</td>
                                        <td>${escHtml(s.subject_name||'')}</td>
                                        <td class="pro-units">${parseFloat(s.units)||0}</td>
                                        <td class="pro-prereq-col">${prereqCode !== '–' ? '<span style="color:#dc2626;font-weight:600;">★ ' + prereqCode + '</span>' : '–'}</td>
                                        <td class="pro-crud-col">
                                            <button class="btn-crud btn-crud-edit" onclick="editProspectusSubject(${s.major_subject_id}, '${escJs(s.subject_code||'')}', '${escJs(s.subject_name||'')}', '${escJs(s.year_level||'')}', '${escJs(s.semester||'')}', '${escJs(s.units||'')}', ${s.id})" title="Edit"><i class="fas fa-edit"></i></button>
                                            <button class="btn-crud btn-crud-delete" onclick="deleteProspectusSubject(${s.major_subject_id}, '${escJs(s.subject_code||'')}', ${s.id})" title="Delete"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>`;
                                }).join('')}
                                <tr class="pro-total-row"><td colspan="3" style="text-align:right;padding-right:8px;">Total Units</td><td class="pro-units">${bt%1===0?bt:bt.toFixed(1)}</td><td></td><td></td><td></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>`;
        }

        const gt = grandTotal%1===0?grandTotal:grandTotal.toFixed(1);
        document.getElementById('prospectusContent').innerHTML = `
        <div class="pro-wrap" id="printableProspectus">
            ${buildProspectusHeader(majorName)}
            <div class="pro-body">
                ${yearBlocksHtml}
                ${bridgeHtml}
                <div class="pro-grand-total">Grand Total: <strong>${gt} units</strong></div>
                <div class="pro-sig-block">
                    <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-label">Student's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
                    <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-label">Adviser's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
                    <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-label">Program Head's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
                </div>
                <div class="pro-legend"><span class="pro-star">★</span> = Prerequisite subject &nbsp;–&nbsp; <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#fef2f2;border-left:3px solid #dc2626;vertical-align:middle;"></span> = prerequisite row</div>
            </div>
        </div>`;
    }

    function renderEmptyProspectusWithCRUD(majorName) {
        const yearOrder = ['1st Year','2nd Year','3rd Year','4th Year'];
        let yearBlocksHtml = '';
        yearOrder.forEach(y => {
            yearBlocksHtml += `
            <div class="pro-year-block">
                <div class="pro-year-header"><span>${y}</span><span class="pro-year-total">0 units</span></div>
                <div class="pro-sem-row">${buildSemTableWithCRUD([],'1st Semester',y)}${buildSemTableWithCRUD([],'2nd Semester',y)}</div>
            </div>`;
        });
        document.getElementById('prospectusContent').innerHTML = `
        <div class="pro-wrap" id="printableProspectus">
            ${buildProspectusHeader(majorName)}
            <div class="pro-body">
                ${yearBlocksHtml}
                <div class="pro-grand-total">Grand Total: <strong>0 units</strong></div>
                <div class="pro-sig-block">
                    <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-label">Student's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
                    <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-label">Adviser's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
                    <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-label">Program Head's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
                </div>
            </div>
        </div>`;
    }

    function editProspectusSubject(majorSubjectId, subjectCode, subjectName, yearLevel, semester, units, subjectId) {
        document.getElementById('editProspectusMajorId').value    = currentEditMajorId;
        document.getElementById('editProspectusSubjectId').value  = majorSubjectId;
        document.getElementById('editProspectusDbSubjectId').value = subjectId || '';
        document.getElementById('editProspectusSubjectCode').value = subjectCode || '';
        document.getElementById('editProspectusSubjectName').value = subjectName || '';
        document.getElementById('editProspectusUnits').value      = units || '3';
        document.getElementById('editProspectusYearLevel').value  = yearLevel || '1st Year';
        document.getElementById('editProspectusSemester').value   = semester  || '1st Semester';

        // Load majors that have this subject
        loadSubjectMajors(subjectId || subjectCode);

        document.getElementById('editProspectusSubjectModal').classList.add('active');
    }

    function loadSubjectMajors(subjectCodeOrId) {
        const container = document.getElementById('editProspectusMajorsList');
        container.innerHTML = '<div style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        
        const fd = new FormData();
        fd.append('action', 'get_subject_majors');
        fd.append('subject_identifier', subjectCodeOrId);
        
        fetch('../../../data/major_process.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success && d.majors) {
                let html = '';
                d.majors.forEach(m => {
                    const isChecked = m.has_subject ? 'checked' : '';
                    html += `<label style="display:flex;align-items:center;gap:10px;padding:6px 8px;border-radius:6px;cursor:pointer;transition:background 0.15s;" onmouseover="this.style.background='var(--cream)'" onmouseout="this.style.background='transparent'">
                        <input type="checkbox" class="prospectus-major-toggle" data-major-id="${m.id}" ${isChecked} style="width:16px;height:16px;accent-color:var(--gold-dark);" onchange="toggleSubjectMajor(this, ${m.id})">
                        <span style="font-size:13px;">${m.display_name}</span>
                        <span style="font-size:11px;color:${m.has_subject ? '#22c55e' : '#9ca3af'};">${m.has_subject ? '✓ In prospectus' : '– Not in prospectus'}</span>
                    </label>`;
                });
                container.innerHTML = html || '<p style="font-size:12px;color:var(--light-text);padding:8px;font-style:italic;">No majors available.</p>';
            } else {
                container.innerHTML = '<p style="font-size:12px;color:var(--light-text);padding:8px;font-style:italic;">Unable to load majors.</p>';
            }
        })
        .catch(() => {
            container.innerHTML = '<p style="font-size:12px;color:var(--light-text);padding:8px;font-style:italic;">Error loading majors.</p>';
        });
    }

    function toggleSubjectMajor(checkbox, majorId) {
        const subjectCode = document.getElementById('editProspectusSubjectCode').value.trim();
        
        const fd = new FormData();
        if (checkbox.checked) {
            fd.append('action', 'add_major_subject');
            fd.append('major_id', majorId);
            fd.append('subject_identifier', subjectCode);
            fd.append('year_level', document.getElementById('editProspectusYearLevel').value || '1st Year');
            fd.append('semester', document.getElementById('editProspectusSemester').value || '1st Semester');
        } else {
            fd.append('action', 'remove_subject_from_major');
            fd.append('major_id', majorId);
            fd.append('subject_identifier', subjectCode);
        }
        
        fetch('../../../data/major_process.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            toast(d.message, d.success ? 'success' : 'error');
            if (d.success) {
                // Refresh the list
                loadSubjectMajors(subjectCode);
            }
        })
        .catch(() => toast('Failed to update subject in major', 'error'));
    }

    function saveProspectusEdit(e) {
        e.preventDefault();
        const fd = new FormData(document.getElementById('editProspectusForm'));
        fd.append('action', 'update_major_subject_placement');
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => {
            toast(d.message, d.success ? 'success' : 'error');
            if (d.success) {
                document.getElementById('editProspectusSubjectModal').classList.remove('active');
                if (editMode) {
                    loadProspectusWithCRUD();
                } else {
                    loadProspectus();
                }
            }
        });
    }

    function deleteProspectusSubject(majorSubjectId, subjectCode, subjectId) {
        if (!confirm('Permanently delete "' + subjectCode + '" from the database? This will also remove it from all other majors that use this subject.')) return;
        const fd = new FormData();
        fd.append('action', 'delete_subject_permanently');
        fd.append('major_subject_id', majorSubjectId);
        fd.append('subject_id', subjectId);
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => {
            toast(d.message, d.success ? 'success' : 'error');
            if (d.success) {
                if (editMode) {
                    loadProspectusWithCRUD();
                } else {
                    loadProspectus();
                }
            }
        });
    }

    /* -- DRAG AND DROP FOR SUBJECT REORDERING ----------------------- */
    let draggedRow = null;
    let draggedMajorSubjectId = null;
    let dragSourceYearLevel = '';
    let dragSourceSemester = '';
    let lastDragOverRow = null;

    function getTableSemesterInfo(tableEl) {
        const semLabelEl = tableEl?.previousElementSibling;
        const semLabel = semLabelEl?.textContent?.trim() || '';
        
        const yearBlock = tableEl.closest('.pro-year-block');
        let yearLevel = '1st Year';
        if (yearBlock) {
            const yearHeader = yearBlock.querySelector('.pro-year-header span');
            if (yearHeader) {
                const txt = yearHeader.textContent.trim();
                if (txt.includes('1st Year')) yearLevel = '1st Year';
                else if (txt.includes('2nd Year')) yearLevel = '2nd Year';
                else if (txt.includes('3rd Year')) yearLevel = '3rd Year';
                else if (txt.includes('4th Year')) yearLevel = '4th Year';
                else if (txt.includes('Bridging')) yearLevel = 'Bridging';
            }
        }
        
        let semester = '1st Semester';
        if (semLabel.includes('2nd')) semester = '2nd Semester';
        
        return { yearLevel, semester };
    }

    function clearAllDragIndicators() {
        document.querySelectorAll('.pro-table tbody tr.drag-over-above').forEach(r => r.classList.remove('drag-over-above'));
        document.querySelectorAll('.pro-table tbody tr.drag-over-below').forEach(r => r.classList.remove('drag-over-below'));
        document.querySelectorAll('.pro-table tbody.drag-over-empty').forEach(tb => tb.classList.remove('drag-over-empty'));
        lastDragOverRow = null;
    }

    function initDragAndDrop() {
        document.querySelectorAll('.pro-table tbody').forEach(tbody => {
            tbody.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                const rows = tbody.querySelectorAll('tr[draggable="true"]');
                if (rows.length === 0) {
                    tbody.classList.add('drag-over-empty');
                }
            });
            tbody.addEventListener('dragleave', (e) => {
                if (!tbody.contains(e.relatedTarget)) {
                    tbody.classList.remove('drag-over-empty');
                }
            });
            tbody.addEventListener('drop', (e) => {
                e.preventDefault();
                tbody.classList.remove('drag-over-empty');
                if (!draggedRow) return;
                const rows = tbody.querySelectorAll('tr[draggable="true"]');
                if (rows.length === 0) {
                    const tableEl = tbody.closest('.pro-table');
                    const info = getTableSemesterInfo(tableEl);
                    moveSubjectToSemester(draggedMajorSubjectId, info.yearLevel, info.semester);
                }
            });
        });
        
        document.querySelectorAll('.pro-table tbody tr[draggable="true"]').forEach(row => {
            row.addEventListener('dragstart', handleDragStart);
            row.addEventListener('dragend', handleDragEnd);
            row.addEventListener('dragover', handleDragOver);
            row.addEventListener('dragleave', handleDragLeave);
            row.addEventListener('drop', handleDrop);
        });
    }

    function handleDragStart(e) {
        const row = e.target.closest('tr');
        if (!row || row.classList.contains('pro-total-row')) {
            e.preventDefault();
            return;
        }
        
        draggedRow = row;
        draggedMajorSubjectId = row.dataset.majorSubjectId;
        dragSourceYearLevel = row.dataset.yearLevel || '1st Year';
        dragSourceSemester = row.dataset.semester || '1st Semester';
        
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', draggedMajorSubjectId);

        // Create a clean drag image clone to avoid glitchy default ghost
        const clone = row.cloneNode(true);
        clone.style.cssText = 'position:absolute;top:-9999px;left:-9999px;background:#fffdf5;opacity:0.85;border:1px solid #d4a843;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,0.15);pointer-events:none;width:' + row.offsetWidth + 'px;';
        document.body.appendChild(clone);
        e.dataTransfer.setDragImage(clone, 20, row.offsetHeight / 2);
        
        // Clean up clone after a tick
        requestAnimationFrame(() => {
            row.classList.add('dragging-row');
            row.querySelector('.drag-handle')?.classList.add('dragging');
            setTimeout(() => { if (clone.parentNode) clone.parentNode.removeChild(clone); }, 0);
        });
    }

    function handleDragEnd(e) {
        if (draggedRow) {
            draggedRow.classList.remove('dragging-row');
            draggedRow.querySelector('.drag-handle')?.classList.remove('dragging');
        }
        clearAllDragIndicators();
        draggedRow = null;
        draggedMajorSubjectId = null;
        dragSourceYearLevel = '';
        dragSourceSemester = '';
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const row = e.target.closest('tr');
        if (!row || row.classList.contains('pro-total-row') || row === draggedRow) return;
        
        // Only update indicator if the target row changed (prevents constant repainting)
        if (lastDragOverRow === row) {
            // Still update above/below based on mouse position
            const rect = row.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            const isAbove = e.clientY < midY;
            if (isAbove && !row.classList.contains('drag-over-above')) {
                row.classList.remove('drag-over-below');
                row.classList.add('drag-over-above');
            } else if (!isAbove && !row.classList.contains('drag-over-below')) {
                row.classList.remove('drag-over-above');
                row.classList.add('drag-over-below');
            }
            return;
        }
        
        // Clear previous indicators
        clearAllDragIndicators();
        lastDragOverRow = row;
        
        // Determine if dropping above or below
        const rect = row.getBoundingClientRect();
        const midY = rect.top + rect.height / 2;
        if (e.clientY < midY) {
            row.classList.add('drag-over-above');
        } else {
            row.classList.add('drag-over-below');
        }
    }

    function handleDragLeave(e) {
        const row = e.target.closest('tr');
        if (row && !row.contains(e.relatedTarget)) {
            row.classList.remove('drag-over-above');
            row.classList.remove('drag-over-below');
            if (lastDragOverRow === row) lastDragOverRow = null;
        }
    }

    function handleDrop(e) {
        e.preventDefault();
        const row = e.target.closest('tr');
        if (!row || row.classList.contains('pro-total-row') || row === draggedRow || !draggedRow) return;
        
        const targetMajorSubjectId = row.dataset.majorSubjectId;
        const tableEl = row.closest('.pro-table');
        const targetInfo = getTableSemesterInfo(tableEl);
        const draggedSortOrder = parseInt(draggedRow.dataset.sortOrder) || 0;
        const targetSortOrder = parseInt(row.dataset.sortOrder) || 0;
        
        // Perform DOM move immediately for instant visual feedback (no flicker)
        const tbody = row.parentNode;
        if (row.classList.contains('drag-over-above')) {
            tbody.insertBefore(draggedRow, row);
        } else {
            tbody.insertBefore(draggedRow, row.nextSibling);
        }
        
        // Reset visual state immediately
        draggedRow.classList.remove('dragging-row');
        draggedRow.querySelector('.drag-handle')?.classList.remove('dragging');
        clearAllDragIndicators();
        
        // Then persist to server in background
        moveSubjectToPosition(
            draggedMajorSubjectId, 
            targetMajorSubjectId, 
            draggedSortOrder, 
            targetSortOrder,
            targetInfo.yearLevel,
            targetInfo.semester
        );
    }

    function moveSubjectToPosition(draggedId, targetId, draggedOrder, targetOrder, targetYear, targetSem) {
        const fd = new FormData();
        fd.append('action', 'move_subject_position');
        fd.append('dragged_id', draggedId);
        fd.append('target_id', targetId);
        fd.append('dragged_order', draggedOrder);
        fd.append('target_order', targetOrder);
        fd.append('target_year', targetYear);
        fd.append('target_sem', targetSem);
        
        fetch('../../../data/major_process.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                toast(d.message, 'success');
                // Silently reload to sync sort orders from server WITHOUT showing spinner
                loadProspectusWithCRUD(false);
            } else {
                toast(d.message, 'error');
                loadProspectusWithCRUD(false);
            }
        })
        .catch(() => { toast('Failed to move subject', 'error'); loadProspectusWithCRUD(false); });
    }

    function moveSubjectToSemester(majorSubjectId, targetYear, targetSem) {
        const fd = new FormData();
        fd.append('action', 'move_subject_position');
        fd.append('dragged_id', majorSubjectId);
        fd.append('target_id', '0');
        fd.append('dragged_order', '0');
        fd.append('target_order', '999');
        fd.append('target_year', targetYear);
        fd.append('target_sem', targetSem);
        
        fetch('../../../data/major_process.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            toast(d.message, d.success ? 'success' : 'error');
            if (d.success) {
                // Silently reload without spinner after drag-and-drop move
                loadProspectusWithCRUD(false);
            }
        })
        .catch(() => toast('Failed to move subject', 'error'));
    }

    function updateSubjectOrder(firstId, firstOrder, secondId, secondOrder) {
        const fd = new FormData();
        fd.append('action', 'reorder_subjects');
        fd.append('first_id', firstId);
        fd.append('first_order', firstOrder);
        fd.append('second_id', secondId);
        fd.append('second_order', secondOrder);
        
        fetch('../../../data/major_process.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            toast(d.message, d.success ? 'success' : 'error');
            if (d.success) {
                // Silently reload without spinner after reorder
                loadProspectusWithCRUD(false);
            }
        })
        .catch(() => toast('Failed to reorder subjects', 'error'));
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function escJs(str) {
        if (!str) return '';
        return String(str).replace(/\\/g,'\\\\').replace(/'/g,"\\'");
    }
    </script>

    <?php if ($show_role_modal): ?>
    <div class="modal-overlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.55);display:flex;align-items:center;justify-content:center;z-index:99999;">
        <div style="background:white;border-radius:16px;padding:32px;max-width:360px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <div style="width:80px;height:80px;border-radius:50%;background:rgba(220,38,38,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size:40px;color:#dc2626;"></i>
            </div>
            <h3 style="font-size:20px;font-weight:700;margin-bottom:12px;">Access Restricted</h3>
            <p style="font-size:14px;color:#6b7280;margin-bottom:20px;"><?php echo htmlspecialchars($role_access['message'] ?? 'You do not have access.'); ?></p>
            <div style="display:flex;gap:12px;justify-content:center;">
                <a href="../../../data/logout.php" style="background:#dc2626;color:white;padding:10px 20px;border-radius:10px;text-decoration:none;font-weight:500;"><i class="fas fa-sign-out-alt"></i> Logout</a>
               
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
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
        try {
            $stmt = $pdo->query("SELECT * FROM majors ORDER BY display_name");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['subject_count'] = 0;
                $row['student_count'] = 0;
                $majors[] = $row;
            }
        } catch (PDOException $e2) {
            $majors = [];
        }
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Major Management - Program Head Dashboard</title>
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
        .page-header { margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 24px; font-weight: 700; color: var(--dark-text); }
        .page-subtitle { font-size: 13px; color: var(--light-text); margin-top: 4px; }
        .card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid var(--border-light); margin-bottom: 20px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-title { font-size: 16px; font-weight: 700; color: var(--dark-text); display: flex; align-items: center; gap: 8px; }
        .card-title i { color: var(--gold-dark); }
        .btn-add { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: white; padding: 10px 18px; border: none; border-radius: 10px; cursor: pointer; font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease; }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(184, 134, 11, 0.3); }
        .major-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .major-card { background: var(--cream); border-radius: 12px; padding: 20px; border: 1px solid var(--border-light); transition: all 0.2s ease; }
        .major-card:hover { transform: translateY(-4px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .major-card.inactive { opacity: 0.6; }
        .major-header { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
        .major-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; flex-shrink: 0; }
        .major-info { flex: 1; min-width: 0; }
        .major-name { font-size: 16px; font-weight: 700; color: var(--dark-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .major-meta { font-size: 13px; color: var(--light-text); margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .major-desc { font-size: 13px; color: var(--light-text); margin: 12px 0; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .major-actions { display: flex; gap: 8px; border-top: 1px solid var(--border-light); padding-top: 12px; margin-top: 12px; }
        .btn-action { flex: 1; padding: 8px; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s; }
        .btn-view { background: var(--white); color: var(--dark-text); border: 1px solid var(--border-light); }
        .btn-view:hover { background: var(--cream); }
        .btn-edit { background: var(--gold-light); color: white; }
        .btn-edit:hover { background: var(--gold-dark); }
        .btn-delete { background: #ef4444; color: white; }
        .btn-delete:hover { background: #dc2626; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 9999; }
        .modal-overlay.active { display: flex; }
        .modal { background: white; border-radius: 16px; padding: 24px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 18px; font-weight: 700; color: var(--dark-text); }
        .modal-close { width: 32px; height: 32px; border: none; background: var(--cream); border-radius: 8px; cursor: pointer; font-size: 18px; color: var(--light-text); }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--dark-text); margin-bottom: 6px; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 10px 14px; border: 1px solid var(--border-light); border-radius: 10px; font-size: 14px; font-family: 'Poppins', sans-serif; transition: all 0.2s; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(184, 134, 11, 0.1); }
        .form-textarea { min-height: 80px; resize: vertical; }
        .form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px; }
        .btn-submit { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: white; padding: 12px 24px; border: none; border-radius: 10px; cursor: pointer; font-weight: 500; font-size: 14px; }
        .btn-cancel { background: var(--cream); color: var(--dark-text); padding: 12px 24px; border: 1px solid var(--border-light); border-radius: 10px; cursor: pointer; font-weight: 500; font-size: 14px; }
        .subject-list { margin-top: 16px; }
        .subject-item { display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--cream); border-radius: 10px; margin-bottom: 8px; }
        .subject-item.prerequisite { border-left: 3px solid #ef4444; }
        .subject-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; flex-shrink: 0; }
        .subject-info { flex: 1; min-width: 0; }
        .subject-name { font-size: 14px; font-weight: 600; color: var(--dark-text); }
        .subject-meta { font-size: 12px; color: var(--light-text); margin-top: 2px; }
        .subject-badge { font-size: 10px; padding: 5px 10px; border-radius: 20px; font-weight: 600; text-transform: uppercase; white-space: nowrap; }
        .badge-prereq { background: linear-gradient(135deg, #fef3c7, #fde68a); color: var(--gold-dark); border: 1px solid #fbbf24; }
        .badge-required { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; border: 1px solid #3b82f6; }
        .year-header { font-size: 13px; font-weight: 700; color: var(--gold-dark); margin: 20px 0 12px 0; padding-bottom: 8px; border-bottom: 2px solid var(--gold); display: flex; align-items: center; gap: 8px; }
        .year-header::before { content: ''; display: inline-block; width: 4px; height: 16px; background: var(--gold); border-radius: 2px; }
        .subject-row { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: var(--cream); border-radius: 12px; margin-bottom: 8px; transition: all 0.2s ease; }
        .subject-row:hover { transform: translateX(4px); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .subject-row.prerequisite { border-left: 4px solid #ef4444; background: linear-gradient(135deg, #fef2f2, #fee2e2); }
        .subject-details { flex: 1; display: flex; align-items: center; gap: 12px; }
        .subject-actions { display: flex; gap: 6px; }
        .btn-icon { width: 32px; height: 32px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .btn-star { background: #fef3c7; color: var(--gold-dark); }
        .btn-star:hover { background: #fde68a; }
        .btn-star.active { background: var(--gold-dark); color: white; }
        .btn-remove { background: #fee2e2; color: #ef4444; }
        .btn-remove:hover { background: #ef4444; color: white; }
        .prereq-chain { background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 12px; padding: 16px; border: 1px solid #fbbf24; }
        .prereq-chain-title { font-size: 12px; font-weight: 700; color: var(--gold-dark); text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
        .prereq-item { display: flex; align-items: center; gap: 8px; padding: 8px 0; border-bottom: 1px dashed rgba(184, 134, 11, 0.3); }
        .prereq-item:last-child { border-bottom: none; }
        .prereq-arrow { color: var(--gold-dark); font-size: 12px; }
        .prereq-empty { color: var(--light-text); font-size: 13px; font-style: italic; }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-icon { font-size: 64px; color: var(--light-text); opacity: 0.3; margin-bottom: 16px; }
        .empty-title { font-size: 18px; font-weight: 700; color: var(--dark-text); margin-bottom: 8px; }
        .empty-desc { color: var(--light-text); max-width: 400px; margin: 0 auto; }
        .tab-container { display: flex; gap: 4px; background: var(--cream); padding: 4px; border-radius: 10px; margin-bottom: 20px; }
        .tab { flex: 1; padding: 10px 16px; border: none; background: transparent; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; color: var(--light-text); transition: all 0.2s; }
        .tab.active { background: white; color: var(--dark-text); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

        /* ── Prospectus on-screen styles ── */
        .pro-wrap { font-family: 'Poppins', sans-serif; font-size: 13px; color: #1a1a1a; background: white; border-radius: 16px; border: 1px solid var(--border-light); overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .pro-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px 16px; background: linear-gradient(to bottom, #fffdf5, #fff); border-bottom: 3px solid #8B6914; }
        .pro-logo { width: 70px; height: 70px; object-fit: cover; border-radius: 10px; border: 2px solid #8B6914; }
        .pro-title-block { text-align: center; flex: 1; padding: 0 16px; }
        .pro-school { font-size: 16px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; }
        .pro-address { font-size: 12px; color: #666; margin: 3px 0; }
        .pro-institute { font-size: 13px; font-weight: 700; color: #8B6914; text-transform: uppercase; margin-top: 6px; }
        .pro-degree { font-size: 12px; color: #444; margin: 3px 0; }
        .pro-major { font-size: 13px; font-weight: 600; margin: 4px 0; }
        .pro-label { display: inline-block; margin-top: 6px; padding: 3px 14px; border: 1.5px solid #8B6914; border-radius: 20px; font-size: 11px; font-weight: 700; color: #8B6914; letter-spacing: .5px; text-transform: uppercase; }
        .pro-body { padding: 16px 20px 20px; }
        .pro-year-block { margin-bottom: 20px; border: 1px solid #e0dbd0; border-radius: 10px; overflow: hidden; }
        .pro-year-header { background: linear-gradient(135deg, #8B6914, #B8860B); color: #fff; padding: 9px 16px; font-size: 14px; font-weight: 700; display: flex; justify-content: space-between; align-items: center; }
        .pro-year-total { font-size: 11px; font-weight: 400; opacity: .85; }
        .pro-bridge-header { background: linear-gradient(135deg, #555, #777); }
        .pro-sem-row { display: grid; grid-template-columns: 1fr 1fr; padding: 12px 12px 14px; gap: 12px; }
        .pro-sem-label { font-size: 11px; font-weight: 700; color: #8B6914; text-align: center; padding: 5px 0; background: #f7f5ef; border: 1px solid #d4cfc5; border-radius: 6px 6px 0 0; text-transform: uppercase; letter-spacing: .3px; }
        .pro-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .pro-th { background: #f0ece0; padding: 6px 8px; text-align: left; font-size: 11px; font-weight: 700; color: #8B6914; border: 1px solid #ccc; white-space: nowrap; }
        .pro-table td { border: 1px solid #ddd; padding: 5px 8px; vertical-align: middle; }
        .pro-grade-cell { text-align: center; background: #fafaf8; width: 28px; }
        .pro-code { font-weight: 600; white-space: nowrap; font-size: 11px; }
        .pro-units { text-align: center; font-weight: 500; }
        .pro-prereq-col { color: #888; font-size: 11px; }
        .pro-prereq-row { background: linear-gradient(135deg, #fef2f2, #fee2e2); border-left: 3px solid #dc2626; }
        .pro-prereq-row .pro-subj { color: #991b1b; }
        .pro-star { color: #dc2626; }
        .pro-total-row td { background: #f0ece0; font-weight: 700; color: #8B6914; border-top: 2px solid #B8860B; font-size: 11px; }
        .pro-empty { text-align: center; color: #aaa; font-style: italic; padding: 14px; }
        .pro-bridging-block { margin: 0 20px 20px; }
        .pro-grand-total { text-align: right; font-size: 13px; padding: 8px 16px; background: #f7f5ef; border: 1px solid #d4cfc5; border-radius: 8px; margin: 0 20px 16px; }
        .pro-sig-block { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 0 20px 20px; padding: 16px; border-top: 2px solid #d4cfc5; }
        .pro-sig-col { text-align: center; }
        .pro-sig-line { border-bottom: 1.5px solid #333; margin-bottom: 6px; height: 28px; }
        .pro-sig-label { font-size: 12px; font-weight: 600; color: #333; }
        .pro-sig-sub { font-size: 11px; color: #888; margin-top: 3px; }
        .pro-legend { font-size: 11px; color: #999; margin: 0 20px 16px; padding: 6px 0; }
        .pro-legend-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; vertical-align: middle; }
        .pro-legend-dot.prereq { background: #fecaca; border: 1px solid #dc2626; }
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

    <div class="main-content" style="position:relative;">
        <header class="topbar">
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
                        <p class="page-subtitle">Manage departments, subjects, and prerequisites</p>
                    </div>
                    <div style="display:flex;gap:12px;">
                        <button class="btn-add" onclick="showSubjectModal()"><i class="fas fa-book"></i> Add Subject</button>
                        <button class="btn-add" onclick="showMajorModal()"><i class="fas fa-plus"></i> Add Major</button>
                    </div>
                </div>

                <div class="card">
                    <div class="tab-container" style="background:white;">
                        <button class="tab active" onclick="switchTab('majors')"><i class="fas fa-graduation-cap"></i> Majors</button>
                        <button class="tab" onclick="switchTab('subjects')"><i class="fas fa-book"></i> Prospectus</button>
                    </div>

                    <!-- MAJORS TAB -->
                    <div id="majorsTab">
                        <?php if (empty($majors)): ?>
                        <div class="empty-state">
                            <i class="fas fa-graduation-cap empty-icon"></i>
                            <h3 class="empty-title">No Majors Configured</h3>
                            <p class="empty-desc">Create your first major to get started.</p>
                            <button class="btn-add" style="margin:20px auto 0;" onclick="showMajorModal()"><i class="fas fa-plus"></i> Add Major</button>
                        </div>
                        <?php else: ?>
                        <div class="major-grid">
                            <?php foreach ($majors as $major): ?>
                            <div class="major-card <?php echo $major['is_active'] ? '' : 'inactive'; ?>" data-id="<?php echo $major['id']; ?>">
                                <div class="major-header">
                                    <div class="major-icon" style="background:linear-gradient(135deg,<?php echo htmlspecialchars($major['gradient_from']); ?>,<?php echo htmlspecialchars($major['gradient_to']); ?>);">
                                        <i class="<?php echo htmlspecialchars($major['icon_class']); ?>"></i>
                                    </div>
                                    <div class="major-info">
                                        <div class="major-name"><?php echo htmlspecialchars($major['display_name']); ?></div>
                                        <div class="major-meta">
                                            <?php echo $major['subject_count']; ?> Subject<?php echo $major['subject_count'] != 1 ? 's' : ''; ?> |
                                            <?php echo $major['student_count']; ?> Student<?php echo $major['student_count'] != 1 ? 's' : ''; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($major['description']): ?>
                                <div class="major-desc"><?php echo htmlspecialchars($major['description']); ?></div>
                                <?php endif; ?>
                                <div class="major-actions">
                                    <button class="btn-action btn-view" onclick="viewMajorSubjects(<?php echo $major['id']; ?>,'<?php echo htmlspecialchars($major['display_name']); ?>')">
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

                    <!-- PROSPECTUS TAB -->
                    <div id="subjectsTab" style="display:none;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                            <div>
                                <h3 style="font-size:16px;font-weight:700;color:var(--dark-text);margin:0;">
                                    <i class="fas fa-book" style="color:var(--gold-dark);margin-right:8px;"></i>Subject Prospectus
                                </h3>
                                <p style="font-size:13px;color:var(--light-text);margin:4px 0 0 0;">Student Evaluation Prospectus — view by major</p>
                            </div>
                            <div style="display:flex;gap:12px;align-items:center;">
                                <select id="prospectusMajorSelect" class="form-select" style="min-width:220px;" onchange="loadProspectus()">
                                    <option value="">-- Select Major --</option>
                                    <?php foreach ($majors as $major): ?>
                                    <option value="<?php echo $major['id']; ?>"><?php echo htmlspecialchars($major['display_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn-add" id="printProspectusBtn" style="display:none;" onclick="printProspectus()">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                        <div id="prospectusContent">
                            <div class="empty-state" style="padding:60px 20px;text-align:center;background:white;border-radius:12px;border:1px solid var(--border-light);">
                                <div style="width:80px;height:80px;border-radius:50%;background:var(--cream);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                                    <i class="fas fa-graduation-cap" style="font-size:32px;color:var(--gold-dark);"></i>
                                </div>
                                <h3 style="font-size:18px;font-weight:700;color:var(--dark-text);margin-bottom:8px;">Select a Major</h3>
                                <p style="font-size:14px;color:var(--light-text);max-width:400px;margin:0 auto;">Choose a major from the dropdown above to view its prospectus.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- ══ MAJOR MODAL ══════════════════════════════════════ -->
    <div class="modal-overlay" id="majorModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="majorModalTitle">Add Major</h3>
                <button class="modal-close" onclick="closeMajorModal()">&times;</button>
            </div>
            <form id="majorForm" onsubmit="saveMajor(event)">
                <input type="hidden" id="majorId" name="id" value="0">
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
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" id="majorDesc" name="description" placeholder="Enter major description"></textarea>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Icon Class</label>
                        <select class="form-select" id="majorIcon" name="icon_class">
                            <option value="fas fa-graduation-cap">Graduation Cap</option>
                            <option value="fas fa-cogs">Cogs</option>
                            <option value="fas fa-dollar-sign">Dollar Sign</option>
                            <option value="fas fa-chart-line">Chart</option>
                            <option value="fas fa-briefcase">Briefcase</option>
                            <option value="fas fa-users">Users</option>
                            <option value="fas fa-book">Book</option>
                            <option value="fas fa-laptop">Laptop</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="majorActive" name="is_active">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Gradient From</label>
                        <input type="color" class="form-input" id="gradientFrom" name="gradient_from" value="#d4a843">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gradient To</label>
                        <input type="color" class="form-input" id="gradientTo" name="gradient_to" value="#e8c768">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeMajorModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Save Major</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══ SUBJECT MODAL ════════════════════════════════════ -->
    <div class="modal-overlay" id="subjectModal">
        <div class="modal" style="max-width:600px;">
            <div class="modal-header">
                <h3 class="modal-title" id="subjectModalTitle">Add Subject</h3>
                <button class="modal-close" onclick="closeSubjectModal()">&times;</button>
            </div>
            <form id="subjectForm" onsubmit="saveSubject(event)">
                <input type="hidden" id="subjectId" name="id" value="0">
                <div style="background:var(--cream);padding:16px;border-radius:12px;margin-bottom:16px;">
                    <h4 style="font-size:14px;font-weight:700;margin-bottom:12px;color:var(--gold-dark);"><i class="fas fa-info-circle"></i> Basic Information</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Subject Code *</label>
                            <input type="text" class="form-input" id="subjectCode" name="subject_code" placeholder="e.g., OPM 101" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subject Title *</label>
                            <input type="text" class="form-input" id="subjectName" name="subject_name" placeholder="e.g., Introduction to Operations" required>
                        </div>
                    </div>
                </div>
                <div style="background:var(--cream);padding:16px;border-radius:12px;margin-bottom:16px;">
                    <h4 style="font-size:14px;font-weight:700;margin-bottom:12px;color:var(--gold-dark);"><i class="fas fa-clock"></i> Credit & Year/Semester</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Credit Units *</label>
                            <input type="number" class="form-input" id="subjectUnits" name="units" value="3" step="0.5" min="0" max="10">
                            <small style="color:var(--light-text);font-size:11px;">Number of credit hours</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prerequisite</label>
                            <select class="form-select" id="subjectPrerequisite" name="prerequisite">
                                <option value="">-- None --</option>
                                <?php foreach ($all_subjects as $subj): ?>
                                <option value="<?php echo htmlspecialchars($subj['subject_code']); ?>"><?php echo htmlspecialchars($subj['subject_code']); ?> - <?php echo htmlspecialchars($subj['subject_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div style="background:var(--cream);padding:16px;border-radius:12px;margin-bottom:16px;">
                    <h4 style="font-size:14px;font-weight:700;margin-bottom:12px;color:var(--gold-dark);"><i class="fas fa-graduation-cap"></i> Add to Prospectus</h4>
                    <p style="font-size:12px;color:var(--light-text);margin-bottom:10px;">Select majors to add this subject to:</p>
                    <div style="max-height:150px;overflow-y:auto;border:1px solid var(--border-light);border-radius:8px;padding:8px;background:white;">
                        <?php foreach ($majors as $major): ?>
                        <label style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:6px;cursor:pointer;" onmouseover="this.style.background='var(--cream)'" onmouseout="this.style.background='transparent'">
                            <input type="checkbox" class="prospectus-major-check" name="prospectus_majors[]" value="<?php echo $major['id']; ?>" style="width:16px;height:16px;accent-color:var(--gold-dark);">
                            <span style="font-size:13px;color:var(--dark-text);"><?php echo htmlspecialchars($major['display_name']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-group" style="margin-top:10px;">
                        <label class="form-label">Year level and semester:</label>
                        <div class="form-grid">
                            <select class="form-select" id="prospectusYearLevel" name="prospectus_year_level">
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                                <option value="Bridging">Bridging</option>
                            </select>
                            <select class="form-select" id="prospectusSemester" name="prospectus_semester">
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeSubjectModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Save Subject</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══ MAJOR SUBJECTS DETAIL MODAL ══════════════════════ -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal" style="max-width:800px;">
            <div class="modal-header">
                <h3 class="modal-title" id="detailModalTitle">Major Subjects</h3>
                <button class="modal-close" onclick="closeDetailModal()">&times;</button>
            </div>
            <div style="background:var(--cream);padding:12px;border-radius:8px;margin-bottom:16px;font-size:13px;color:var(--light-text);">
                <i class="fas fa-info-circle"></i> Click the star to mark a subject as a prerequisite.
            </div>
            <div style="margin-bottom:16px;">
                <button class="btn-add" onclick="showAddSubjectToMajor()"><i class="fas fa-plus"></i> Add Subject</button>
            </div>
            <div id="majorSubjectsList" class="subject-list"></div>
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-light);">
                <h4 style="font-size:14px;margin-bottom:12px;"><i class="fas fa-sitemap"></i> Prerequisite Chain</h4>
                <div id="prereqChain" style="font-size:13px;color:var(--light-text);"></div>
            </div>
        </div>
    </div>

    <!-- ══ ADD SUBJECT TO MAJOR MODAL ═══════════════════════ -->
    <div class="modal-overlay" id="addSubjectModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add Subject to Major</h3>
                <button class="modal-close" onclick="closeAddSubjectModal()">&times;</button>
            </div>
            <form id="addSubjectForm" onsubmit="saveMajorSubject(event)">
                <input type="hidden" id="addMajorId" name="major_id" value="0">
                <div class="form-group">
                    <label class="form-label">Select Subject *</label>
                    <select class="form-select" id="addSubjectId" name="subject_id" required onchange="updatePrereqOptions();updateDefaultYearSem();">
                        <option value="">Choose a subject...</option>
                        <?php foreach ($all_subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['subject_code']); ?> - <?php echo htmlspecialchars($subject['subject_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" id="addIsPrerequisite" name="is_prerequisite" style="width:18px;height:18px;" onchange="togglePrereqFor()">
                        <span style="font-size:14px;font-weight:500;">This is a Prerequisite Subject</span>
                    </label>
                </div>
                <div class="form-group" id="prereqForGroup" style="display:none;">
                    <label class="form-label">This subject is a prerequisite for:</label>
                    <select class="form-select" id="addPrereqFor" name="prerequisite_for">
                        <option value="">Select subject...</option>
                    </select>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Year Level</label>
                        <select class="form-select" id="addYearLevel" name="year_level">
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Semester</label>
                        <select class="form-select" id="addSemester" name="semester">
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeAddSubjectModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Add Subject</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../../function/dashboard.js"></script>
    <script>
    let currentMajorId = 0;
    let majorsData   = <?php echo json_encode($majors); ?>;
    let subjectsData = <?php echo json_encode($all_subjects); ?>;

    /* ── tab switcher ─────────────────────────────────────── */
    function switchTab(tab) {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab').forEach(t => {
            if (t.textContent.toLowerCase().includes(tab)) t.classList.add('active');
        });
        document.getElementById('majorsTab').style.display   = tab === 'majors'   ? 'block' : 'none';
        document.getElementById('subjectsTab').style.display = tab === 'subjects' ? 'block' : 'none';
    }

    /* ── major modal ──────────────────────────────────────── */
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
        }
    }
    function closeMajorModal()  { document.getElementById('majorModal').classList.remove('active'); }
    function editMajor(id)      { showMajorModal(id); }

    function saveMajor(e) {
        e.preventDefault();
        const fd = new FormData(document.getElementById('majorForm'));
        fd.append('action', document.getElementById('majorId').value ? 'update_major' : 'add_major');
        if (document.getElementById('majorId').value) fd.append('id', document.getElementById('majorId').value);
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
            .then(r => r.json()).then(d => { alert(d.message); if (d.success) { closeMajorModal(); location.reload(); } });
    }

    function deleteMajor(id) {
        if (!confirm('Delete this major? All subject associations will also be removed.')) return;
        const fd = new FormData();
        fd.append('action','delete_major'); fd.append('id',id);
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
            .then(r => r.json()).then(d => { alert(d.message); if (d.success) location.reload(); });
    }

    /* ── detail modal ─────────────────────────────────────── */
    function viewMajorSubjects(majorId, majorName) {
        currentMajorId = majorId;
        document.getElementById('detailModalTitle').textContent = majorName + ' — Subjects';
        document.getElementById('detailModal').classList.add('active');
        loadMajorSubjects(majorId);
    }
    function closeDetailModal() { document.getElementById('detailModal').classList.remove('active'); }

    function loadMajorSubjects(majorId) {
        const fd = new FormData();
        fd.append('action','get_major_subjects'); fd.append('major_id',majorId);
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
        .then(r => r.json()).then(data => {
            const container = document.getElementById('majorSubjectsList');
            if (!data.success || data.subjects.length === 0) {
                container.innerHTML = '<div class="empty-state" style="padding:40px 20px;"><p class="empty-desc">No subjects assigned yet.</p></div>';
                return;
            }
            const byYear = {};
            data.subjects.forEach(s => { if (!byYear[s.year_level]) byYear[s.year_level]=[]; byYear[s.year_level].push(s); });
            let html = '';
            Object.keys(byYear).sort((a,b)=>parseInt(a)-parseInt(b)).forEach(year => {
                html += `<div class="year-header">${year} <span style="font-weight:400;font-size:11px;color:var(--light-text);">(${byYear[year].length} subjects)</span></div>`;
                byYear[year].forEach(s => {
                    html += `
                    <div class="subject-row ${s.is_prerequisite?'prerequisite':''}">
                        <div class="subject-icon" style="background:linear-gradient(135deg,${s.color},${s.color});"><i class="${s.icon_class}"></i></div>
                        <div class="subject-details">
                            <div class="subject-info">
                                <div class="subject-name">${s.subject_code} — ${s.subject_name}</div>
                                <div class="subject-meta"><i class="fas fa-clock" style="font-size:10px;"></i> ${s.semester} &nbsp;|&nbsp; <i class="fas fa-hourglass-half" style="font-size:10px;"></i> ${s.units} Units</div>
                            </div>
                        </div>
                        <span class="subject-badge ${s.is_prerequisite?'badge-prereq':'badge-required'}">
                            <i class="fas fa-${s.is_prerequisite?'star':'check'}"></i> ${s.is_prerequisite?'Prerequisite':'Required'}
                        </span>
                        <div class="subject-actions">
                            <button class="btn-icon btn-star ${s.is_prerequisite?'active':''}" onclick="togglePrerequisite(${majorId},${s.id},${s.is_prerequisite?'false':'true'})" title="Toggle prerequisite"><i class="fas fa-star"></i></button>
                            <button class="btn-icon btn-remove" onclick="removeMajorSubject(${majorId},${s.id})" title="Remove"><i class="fas fa-times"></i></button>
                        </div>
                    </div>`;
                });
            });
            container.innerHTML = html;
            const prereqs = data.subjects.filter(s => s.is_prerequisite);
            let chainHtml = prereqs.length
                ? '<div class="prereq-chain"><div class="prereq-chain-title"><i class="fas fa-sitemap"></i> Prerequisite Chain</div>'
                    + prereqs.map((p,i) => `<div class="prereq-item"><i class="fas fa-star" style="color:#ef4444;"></i> <strong>${p.subject_code}</strong> — ${p.subject_name}</div>`
                        + (i<prereqs.length-1?'<div class="prereq-arrow" style="padding-left:14px;"><i class="fas fa-arrow-down" style="font-size:10px;"></i> must pass first</div>':'')).join('')
                    + '</div>'
                : '<div class="prereq-empty">No prerequisites set for this major.</div>';
            document.getElementById('prereqChain').innerHTML = chainHtml;
        });
    }

    function showAddSubjectToMajor() {
        document.getElementById('addMajorId').value = currentMajorId;
        document.getElementById('addSubjectModal').classList.add('active');
        const sel = document.getElementById('addSubjectId');
        sel.innerHTML = '<option value="">Choose a subject...</option>';
        subjectsData.forEach(s => sel.innerHTML += `<option value="${s.id}" data-year="${s.default_year_level||''}" data-sem="${s.default_semester||''}">${s.subject_code} — ${s.subject_name}</option>`);
    }
    function closeAddSubjectModal() { document.getElementById('addSubjectModal').classList.remove('active'); }

    function updateDefaultYearSem() {
        const opt = document.getElementById('addSubjectId').selectedOptions[0];
        if (opt.getAttribute('data-year')) document.getElementById('addYearLevel').value = opt.getAttribute('data-year');
        if (opt.getAttribute('data-sem'))  document.getElementById('addSemester').value  = opt.getAttribute('data-sem');
    }

    function saveMajorSubject(e) {
        e.preventDefault();
        const fd = new FormData(document.getElementById('addSubjectForm'));
        fd.append('action','add_major_subject'); fd.append('is_required','true');
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
        .then(r => r.json()).then(d => {
            alert(d.message);
            if (d.success) {
                closeAddSubjectModal();
                loadSubjectsForProspectus();
                loadMajorSubjects(currentMajorId);
                if (document.getElementById('prospectusMajorSelect').value == currentMajorId) loadProspectus();
            }
        });
    }

    function removeMajorSubject(majorId, subjectId) {
        if (!confirm('Remove this subject from the major?')) return;
        const fd = new FormData();
        fd.append('action','remove_major_subject'); fd.append('major_id',majorId); fd.append('subject_id',subjectId);
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
            .then(r => r.json()).then(d => { alert(d.message); if (d.success) loadMajorSubjects(majorId); });
    }

    function togglePrerequisite(majorId, subjectId, isPrereq) {
        const fd = new FormData();
        fd.append('action','update_major_subject_flag'); fd.append('major_id',majorId); fd.append('subject_id',subjectId); fd.append('is_prerequisite',isPrereq);
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
            .then(r => r.json()).then(d => { alert(d.message); if (d.success) loadMajorSubjects(majorId); });
    }

    function togglePrereqFor() {
        const chk = document.getElementById('addIsPrerequisite').checked;
        document.getElementById('prereqForGroup').style.display = chk ? 'block' : 'none';
        if (chk) updatePrereqOptions();
    }
    function updatePrereqOptions() {
        const sel = document.getElementById('addPrereqFor');
        const sid = document.getElementById('addSubjectId').value;
        sel.innerHTML = '<option value="">Select subject...</option>';
        if (subjectsData && sid) subjectsData.forEach(s => { if (s.id != sid) sel.innerHTML += `<option value="${s.id}">${s.subject_code} — ${s.subject_name}</option>`; });
    }

    /* ── subject modal ────────────────────────────────────── */
    function showSubjectModal(id = 0) {
        document.getElementById('subjectModal').classList.add('active');
        document.getElementById('subjectModalTitle').textContent = id ? 'Edit Subject' : 'Add Subject';
        document.getElementById('subjectId').value = id;
        if (id) {
            const s = subjectsData.find(s => s.id == id);
            if (s) {
                document.getElementById('subjectCode').value         = s.subject_code;
                document.getElementById('subjectName').value         = s.subject_name;
                document.getElementById('subjectUnits').value        = s.units;
                document.getElementById('subjectPrerequisite').value = s.prerequisite || '';
            }
        } else {
            document.getElementById('subjectForm').reset();
            document.getElementById('prospectusYearLevel').value = '1st Year';
            document.getElementById('prospectusSemester').value  = '1st Semester';
            document.querySelectorAll('.prospectus-major-check').forEach(cb => cb.checked = false);
        }
    }
    function closeSubjectModal() { document.getElementById('subjectModal').classList.remove('active'); }
    function editSubject(id)     { showSubjectModal(id); }

    function saveSubject(e) {
        e.preventDefault();
        const subId = document.getElementById('subjectId').value;
        const fd    = new FormData(document.getElementById('subjectForm'));
        fd.append('action', subId && subId !== '0' ? 'update_subject' : 'add_subject');
        if (subId && subId !== '0') fd.append('id', subId);

        const selectedMajors = Array.from(document.querySelectorAll('.prospectus-major-check:checked')).map(cb => cb.value);

        fetch('../../../data/major_process.php', { method:'POST', body:fd })
        .then(r => r.json()).then(d => {
            alert(d.message);
            if (d.success) {
                closeSubjectModal();
                loadSubjectsForProspectus();
                if (selectedMajors.length > 0) {
                    const yearLevel = document.getElementById('prospectusYearLevel').value || '1st Year';
                    const semester  = document.getElementById('prospectusSemester').value  || '1st Semester';
                    selectedMajors.forEach(mId => {
                        const mfd = new FormData();
                        mfd.append('action','add_major_subject'); mfd.append('major_id',mId);
                        mfd.append('subject_id',d.subject_id); mfd.append('year_level',yearLevel);
                        mfd.append('semester',semester); mfd.append('is_required','true'); mfd.append('is_prerequisite','false');
                        fetch('../../../data/major_process.php', { method:'POST', body:mfd });
                    });
                    if (selectedMajors.includes(document.getElementById('prospectusMajorSelect').value)) loadProspectus();
                }
            }
        });
    }

    function deleteSubject(id) {
        if (!confirm('Delete this subject?')) return;
        const fd = new FormData();
        fd.append('action','delete_subject'); fd.append('id',id);
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
            .then(r => r.json()).then(d => { alert(d.message); if (d.success) location.reload(); });
    }

    function loadSubjectsForProspectus() {
        const fd = new FormData();
        fd.append('action','get_all_subjects');
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
        .then(r => r.json()).then(d => {
            if (d.success) {
                subjectsData = d.subjects || [];
                const sel = document.getElementById('addSubjectId');
                if (sel) {
                    sel.innerHTML = '<option value="">Choose a subject...</option>';
                    subjectsData.forEach(s => sel.innerHTML += `<option value="${s.id}">${s.subject_code} — ${s.subject_name}</option>`);
                }
            }
        });
    }

    /* ════════════════════════════════════════════════════════
       PROSPECTUS — render functions
    ════════════════════════════════════════════════════════ */

    function buildProspectusHeader(majorName) {
        return `
        <div class="pro-header">
            <div><img src="../../../media/LOGO.jpg" alt="Logo" class="pro-logo"></div>
            <div class="pro-title-block">
                <div class="pro-school">NORTHERN BUKIDNON STATE COLLEGE</div>
                <div class="pro-address">Manolo Fortich, Bukidnon</div>
                <div class="pro-institute">INSTITUTE FOR BUSINESS MANAGEMENT</div>
                <div class="pro-degree">Bachelor of Science in Business Administration</div>
                <div class="pro-major">Major in <strong>${majorName}</strong></div>
                <div class="pro-label">Student Evaluation Prospectus</div>
            </div>
            <div><img src="../../../media/LOGO.jpg" alt="Logo" class="pro-logo"></div>
        </div>`;
    }

    function buildSemTable(subjects, semLabel) {
        let rows = '', total = 0;
        if (subjects.length === 0) {
            rows = `<tr><td colspan="5" class="pro-empty">No subjects</td></tr>`;
        } else {
            subjects.forEach(s => {
                const u = parseFloat(s.units) || 0;
                total  += u;
                const prereqClass = s.is_prerequisite ? ' class="pro-prereq-row"' : '';
                rows += `
                <tr${prereqClass}>
                    <td class="pro-grade-cell"></td>
                    <td class="pro-code">${s.subject_code || ''}</td>
                    <td class="pro-subj">${s.subject_name || ''}${s.is_prerequisite ? ' <span class="pro-star">★</span>' : ''}</td>
                    <td class="pro-units">${u % 1 === 0 ? u : u.toFixed(1)}</td>
                    <td class="pro-prereq-col">${s.prerequisite || '—'}</td>
                </tr>`;
            });
        }
        rows += `<tr class="pro-total-row"><td colspan="3" style="text-align:right;padding-right:8px;">Total Units</td><td class="pro-units">${total % 1 === 0 ? total : total.toFixed(1)}</td><td></td></tr>`;

        return `
        <div class="pro-sem-block">
            <div class="pro-sem-label">${semLabel}</div>
            <table class="pro-table">
                <thead><tr>
                    <th class="pro-th" style="width:28px;">Grade</th>
                    <th class="pro-th" style="width:80px;">Code</th>
                    <th class="pro-th">Subject Title</th>
                    <th class="pro-th" style="width:44px;">Units</th>
                    <th class="pro-th" style="width:80px;">Pre-Req</th>
                </tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </div>`;
    }

    function buildBridgingTable(bridging) {
        let rows = '';
        if (!bridging || bridging.length === 0) {
            rows = `<tr><td colspan="5" class="pro-empty">No bridging subjects</td></tr>`;
        } else {
            bridging.forEach(s => {
                rows += `<tr>
                    <td class="pro-grade-cell"></td>
                    <td class="pro-code">${s.subject_code||''}</td>
                    <td class="pro-subj">${s.subject_name||''}</td>
                    <td class="pro-units">${parseFloat(s.units)||0}</td>
                    <td class="pro-prereq-col">${s.prerequisite||'—'}</td>
                </tr>`;
            });
        }
        return `
        <div class="pro-bridging-block">
            <div class="pro-year-block">
                <div class="pro-year-header pro-bridge-header"><i class="fas fa-exchange-alt"></i> Bridging Subjects</div>
                <div style="padding:10px 12px 12px;">
                    <table class="pro-table" style="max-width:520px;">
                        <thead><tr>
                            <th class="pro-th" style="width:28px;">Grade</th>
                            <th class="pro-th" style="width:80px;">Code</th>
                            <th class="pro-th">Subject Title</th>
                            <th class="pro-th" style="width:44px;">Units</th>
                            <th class="pro-th" style="width:80px;">Pre-Req</th>
                        </tr></thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>
        </div>`;
    }

    function buildSignatureBlock() {
        return `
        <div class="pro-sig-block">
            <div class="pro-sig-col">
                <div class="pro-sig-line"></div>
                <div class="pro-sig-label">Student's Signature over Printed Name</div>
                <div class="pro-sig-sub">Date: ___________________</div>
            </div>
            <div class="pro-sig-col">
                <div class="pro-sig-line"></div>
                <div class="pro-sig-label">Adviser's Signature over Printed Name</div>
                <div class="pro-sig-sub">Date: ___________________</div>
            </div>
            <div class="pro-sig-col">
                <div class="pro-sig-line"></div>
                <div class="pro-sig-label">Program Head's Signature over Printed Name</div>
                <div class="pro-sig-sub">Date: ___________________</div>
            </div>
        </div>`;
    }

    function renderEmptyProspectus(majorName) {
        majorName = majorName || 'this Major';
        const years = ['1st Year','2nd Year','3rd Year','4th Year'];
        let yearBlocks = '';
        years.forEach(y => {
            yearBlocks += `
            <div class="pro-year-block">
                <div class="pro-year-header"><i class="fas fa-calendar-alt"></i> ${y}</div>
                <div class="pro-sem-row">
                    ${buildSemTable([],'1st Semester')}
                    ${buildSemTable([],'2nd Semester')}
                </div>
            </div>`;
        });
        document.getElementById('prospectusContent').innerHTML = `
        <div class="pro-wrap" id="printableProspectus">
            ${buildProspectusHeader(majorName)}
            <div style="padding:16px 20px 20px;">
                ${yearBlocks}
                ${buildBridgingTable([])}
                ${buildSignatureBlock()}
            </div>
        </div>`;
    }

    function renderProspectus(subjects, majorName) {
        const yearOrder = ['1st Year','2nd Year','3rd Year','4th Year'];
        const grouped   = {};
        subjects.forEach(s => { const y = s.year_level||'1st Year'; if(!grouped[y]) grouped[y]=[]; grouped[y].push(s); });

        let yearBlocks = '', grandTotal = 0;
        yearOrder.forEach(y => {
            const all  = grouped[y]||[];
            const sem1 = all.filter(s => !s.semester || s.semester.includes('1st'));
            const sem2 = all.filter(s =>  s.semester && s.semester.includes('2nd'));
            const t1   = sem1.reduce((a,s)=>a+(parseFloat(s.units)||0),0);
            const t2   = sem2.reduce((a,s)=>a+(parseFloat(s.units)||0),0);
            const yt   = t1+t2;
            grandTotal += yt;
            yearBlocks += `
            <div class="pro-year-block">
                <div class="pro-year-header">
                    <span><i class="fas fa-calendar-alt" style="margin-right:8px;"></i>${y}</span>
                    <span class="pro-year-total">Total: ${yt%1===0?yt:yt.toFixed(1)} units</span>
                </div>
                <div class="pro-sem-row">
                    ${buildSemTable(sem1,'1st Semester')}
                    ${buildSemTable(sem2,'2nd Semester')}
                </div>
            </div>`;
        });

        const bridging = subjects.filter(s => s.year_level === 'Bridging');

        document.getElementById('prospectusContent').innerHTML = `
        <div class="pro-wrap" id="printableProspectus">
            ${buildProspectusHeader(majorName)}
            <div style="padding:16px 20px 20px;">
                ${yearBlocks}
                ${buildBridgingTable(bridging)}
                <div class="pro-grand-total">Grand Total: <strong>${grandTotal%1===0?grandTotal:grandTotal.toFixed(1)} units</strong></div>
                ${buildSignatureBlock()}
                <div class="pro-legend">
                    <span class="pro-star">★</span> = Prerequisite subject &nbsp;|&nbsp;
                    <span class="pro-legend-dot prereq"></span> Red highlight = prerequisite row
                </div>
            </div>
        </div>`;
    }

    /* ── load prospectus data ─────────────────────────────── */
    function loadProspectus() {
        const majorId   = document.getElementById('prospectusMajorSelect').value;
        const majorName = document.getElementById('prospectusMajorSelect').selectedOptions[0].text;
        const container = document.getElementById('prospectusContent');
        document.getElementById('printProspectusBtn').style.display = majorId ? 'flex' : 'none';

        if (!majorId) {
            container.innerHTML = `
            <div class="empty-state" style="padding:60px 20px;text-align:center;background:white;border-radius:12px;border:1px solid var(--border-light);">
                <div style="width:80px;height:80px;border-radius:50%;background:var(--cream);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                    <i class="fas fa-graduation-cap" style="font-size:32px;color:var(--gold-dark);"></i>
                </div>
                <h3 style="font-size:18px;font-weight:700;color:var(--dark-text);margin-bottom:8px;">Select a Major</h3>
                <p style="font-size:14px;color:var(--light-text);max-width:400px;margin:0 auto;">Choose a major from the dropdown above.</p>
            </div>`;
            return;
        }

        container.innerHTML = '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:32px;color:var(--gold-dark);"></i><p style="margin-top:12px;color:var(--light-text);">Loading prospectus…</p></div>';

        const fd = new FormData();
        fd.append('action','get_major_subjects'); fd.append('major_id',majorId);
        fetch('../../../data/major_process.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.subjects || data.subjects.length === 0) {
                renderEmptyProspectus(majorName);
            } else {
                renderProspectus(data.subjects, majorName);
            }
        })
        .catch(() => renderEmptyProspectus(majorName));
    }

    /* ── print ────────────────────────────────────────────── */
    function printProspectus() {
        const el = document.getElementById('printableProspectus');
        if (!el) { alert('No prospectus loaded.'); return; }
        const majorName = document.getElementById('prospectusMajorSelect').selectedOptions[0].text;

        const win = window.open('', '_blank');
        win.document.write(`<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Prospectus — ${majorName}</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* ── auto-scale: shrinks entire page so content always fits ── */
html {
    /* starts at 7.5pt; clamps down to 5pt if too much content */
    font-size: clamp(5pt, 1.42vw, 7.5pt);
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family:'Poppins',Arial,sans-serif;
    font-size:1rem;
    line-height:1.3;
    color:#1a1a1a;
    background:#fff;
}
@page {
    size: A4 portrait;
    margin: 8mm 10mm 8mm 10mm;
}
/* header */
.pro-wrap { width:100%; }
.pro-header { display:flex;align-items:center;justify-content:space-between;padding-bottom:6pt;border-bottom:2pt solid #8B6914;margin-bottom:8pt; }
.pro-logo { width:52pt;height:52pt;object-fit:cover;border:1.5pt solid #8B6914;border-radius:4pt; }
.pro-title-block { text-align:center;flex:1;padding:0 8pt; }
.pro-school { font-size:1.15rem;font-weight:700;letter-spacing:.5pt;text-transform:uppercase; }
.pro-address { font-size:.85rem;color:#555;margin:1pt 0; }
.pro-institute { font-size:1rem;font-weight:700;color:#8B6914;text-transform:uppercase;margin-top:3pt; }
.pro-degree { font-size:.85rem;color:#333;margin:1pt 0; }
.pro-major { font-size:.95rem;font-weight:600;margin:2pt 0; }
.pro-label { display:inline-block;margin-top:3pt;padding:2pt 8pt;border:1pt solid #8B6914;border-radius:10pt;font-size:.75rem;font-weight:700;color:#8B6914;letter-spacing:.5pt;text-transform:uppercase; }
/* year blocks */
.pro-body { padding:0; }
.pro-year-block { margin-bottom:6pt;page-break-inside:avoid;border:0.5pt solid #e0dbd0;border-radius:3pt;overflow:hidden; }
.pro-year-header { background:#8B6914;color:#fff;padding:3pt 6pt;font-size:.95rem;font-weight:700;display:flex;justify-content:space-between;align-items:center;page-break-after:avoid; }
.pro-year-total { font-size:.75rem;font-weight:400;opacity:.9; }
.pro-bridge-header { background:#555; }
.pro-sem-row { display:grid;grid-template-columns:1fr 1fr;gap:4pt;padding:5pt 5pt 6pt; }
.pro-sem-label { font-size:.7rem;font-weight:700;color:#8B6914;text-align:center;padding:2pt 0;background:#f7f5ef;border:0.5pt solid #d4cfc5;border-radius:2pt 2pt 0 0;text-transform:uppercase;letter-spacing:.3pt; }
/* tables */
.pro-table { width:100%;border-collapse:collapse;font-size:.8rem; }
.pro-th { background:#f0ece0;padding:2pt 3pt;text-align:left;font-weight:700;color:#8B6914;border:0.5pt solid #ccc;white-space:nowrap;font-size:.72rem; }
.pro-table td { border:0.5pt solid #ddd;padding:2pt 3pt;vertical-align:middle; }
.pro-grade-cell { text-align:center;background:#fafaf8; }
.pro-code { font-weight:600;white-space:nowrap; }
.pro-units { text-align:center;font-weight:600; }
.pro-prereq-col { color:#888; }
.pro-prereq-row { background:#fff5f5;border-left:2pt solid #dc2626; }
.pro-prereq-row .pro-subj { color:#991b1b; }
.pro-star { color:#dc2626; }
.pro-total-row td { background:#f0ece0;font-weight:700;color:#8B6914;border-top:1pt solid #8B6914;font-size:.75rem; }
.pro-empty { text-align:center;color:#aaa;font-style:italic;padding:5pt; }
/* bridging */
.pro-bridging-block { margin-top:4pt; }
/* grand total */
.pro-grand-total { text-align:right;font-size:.85rem;padding:3pt 6pt;background:#f7f5ef;border:0.5pt solid #d4cfc5;border-radius:2pt;margin:4pt 0 5pt; }
/* signature */
.pro-sig-block { display:grid;grid-template-columns:repeat(3,1fr);gap:10pt;margin-top:8pt;padding-top:5pt;border-top:1pt solid #ccc;page-break-inside:avoid; }
.pro-sig-col { text-align:center; }
.pro-sig-line { border-bottom:1pt solid #333;margin-bottom:3pt;height:12pt; }
.pro-sig-label { font-size:.75rem;font-weight:700; }
.pro-sig-sub { font-size:.7rem;color:#777;margin-top:2pt; }
/* legend */
.pro-legend { font-size:.7rem;color:#999;margin-top:4pt; }
.pro-legend-dot { display:inline-block;width:7pt;height:7pt;border-radius:50%;vertical-align:middle; }
.pro-legend-dot.prereq { background:#fecaca;border:1pt solid #dc2626; }
/* hide interactive elements */
button { display:none!important; }
</style>
</head>
<body>
${el.outerHTML.replace(/style="padding:16px 20px 20px;"/g, 'style="padding:0;"')}
</body>
</html>`);
        win.document.close();
        win.focus();
        setTimeout(() => { win.print(); }, 500);
    }
    </script>

    <?php if ($show_role_modal): ?>
    <div class="modal-overlay" id="roleMismatchModal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;">
        <div style="background:white;border-radius:16px;padding:32px;max-width:350px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <div style="width:80px;height:80px;border-radius:50%;background:rgba(220,38,38,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size:40px;color:#dc2626;"></i>
            </div>
            <h3 style="font-size:20px;font-weight:700;margin-bottom:12px;">Access Restricted</h3>
            <p id="roleModalMessage" style="font-size:14px;color:#6b7280;margin-bottom:20px;"></p>
            <div style="display:flex;gap:12px;justify-content:center;">
                <a href="../../../data/logout.php" style="background:#dc2626;color:white;padding:10px 20px;border-radius:10px;text-decoration:none;font-weight:500;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <a href="../../../Door/login.php" style="background:linear-gradient(135deg,#d4a843,#b8922f);color:white;padding:10px 20px;border-radius:10px;text-decoration:none;font-weight:500;"><i class="fas fa-sign-in-alt"></i> Login</a>
            </div>
        </div>
    </div>
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            document.getElementById('roleModalMessage').textContent = <?php echo json_encode($role_access['message']); ?>;
        });
    </script>
    <?php endif; ?>
</body>
</html>

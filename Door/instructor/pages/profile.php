<?php
require_once '../../../data/session_security.php';

$role_access = check_role_access('instructor');
$show_role_modal = !$role_access['allowed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../../media/LOGO.jpg" type="image/jpeg">
    <title>My Profile - Faculty Evaluation System</title>
    <link rel="stylesheet" href="../../../css/common.css">
    <link rel="stylesheet" href="../style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --gold: #d4a843;
            --gold-dark: #8B6914;
            --gold-light: #e8c768;
            --cream: #fdfbf7;
            --dark-text: #1f2937;
            --light-text: #6b7280;
            --border: #e5e7eb;
            --primary: #667eea;
            --primary-dark: #5568d3;
            --success: #10b981;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: var(--cream); color: var(--dark-text); }
        .page-container { padding: 24px; }
        .page-title { font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
        .page-subtitle { font-size: 14px; color: var(--light-text); margin-bottom: 24px; }
        
        .profile-header {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            border-radius: 24px;
            padding: 40px;
            display: flex;
            align-items: center;
            gap: 32px;
            box-shadow: 0 8px 32px rgba(212, 168, 67, 0.4);
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
        }
        .profile-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .avatar-wrapper { 
            position: relative; 
            flex-shrink: 0; 
            z-index: 1;
        }
        .avatar-wrapper .avatar-img { 
            width: 140px; 
            height: 140px; 
            border-radius: 50%; 
            object-fit: cover; 
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
            border: 4px solid white;
        }
        .avatar-circle {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 8px 24px rgba(212, 168, 67, 0.4);
            border: 4px solid white;
        }
        .avatar-edit {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 40px;
            height: 40px;
            background: var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            transition: all 0.3s;
            border: 3px solid white;
        }
        .avatar-edit:hover { transform: scale(1.15); background: var(--gold-dark); }
        .profile-info { 
            flex: 1; 
            z-index: 1;
        }
        .profile-name-container {
            margin-bottom: 12px;
        }
        .profile-name { 
            font-size: 48px; 
            font-weight: 900; 
            margin-bottom: 4px;
            color: #ffffff;
            text-shadow: 0 2px 20px rgba(0,0,0,0.4);
            letter-spacing: -1px;
            font-family: 'Poppins', sans-serif;
            line-height: 1.1;
        }
        .profile-id {
            font-size: 14px;
            color: rgba(255,255,255,0.7);
            font-weight: 500;
            background: rgba(255,255,255,0.15);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 4px;
        }
        .profile-role { 
            font-size: 16px; 
            color: rgba(255,255,255,0.9); 
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.2);
            padding: 6px 16px;
            border-radius: 20px;
        }
        
        .btn-edit {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: white;
            box-shadow: 0 4px 12px rgba(212, 168, 67, 0.3);
        }
        .btn-edit:hover { 
            background: linear-gradient(135deg, var(--gold-dark), #6B5210); 
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(212, 168, 67, 0.4);
        }
        .btn-save {
            padding: 10px 24px;
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }
        .btn-save:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .btn-cancel {
            padding: 10px 24px;
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            background: #f3f4f6;
            color: var(--dark-text);
            border: 1px solid var(--border);
        }
        .btn-cancel:hover {
            background: #e5e7eb;
        }
        .btn-edit-mode {
            background: linear-gradient(135deg, #6b7280), #4b5563;
        }
        .info-value.editable {
            background: white;
            border: 2px solid var(--gold);
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
            width: 100%;
            font-family: 'Poppins', sans-serif;
        }
        .info-value.editable:focus {
            outline: none;
            border-color: var(--gold-dark);
            box-shadow: 0 0 0 3px rgba(212,168,67,0.2);
        }
        .edit-mode .info-value { display: none; }
        .edit-mode .edit-input { display: block; }
        .edit-input { display: none; width: 100%; }
        .edit-input input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }
        .edit-input input:focus {
            outline: none;
            border-color: var(--gold);
        }
        .card-header-actions {
            display: flex;
            gap: 12px;
        }
        /* Edit mode styles */
        /* Edit mode styles */
        .profile-section .edit-input { 
            display: none !important;
        }
        
        .profile-section .edit-input input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gold);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            box-sizing: border-box;
            background: white;
            margin-top: 8px;
        }
        
        .profile-section .edit-input input:focus {
            outline: none;
            border-color: var(--gold-dark);
        }
        
        .edit-mode-indicator {
            display: inline-flex !important;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 12px;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        @media (max-width: 1024px) { .profile-grid { grid-template-columns: 1fr; } }
        
        .content-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }
        .card-title { font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .card-title i { color: var(--gold); }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .info-item {
            padding: 16px;
            background: var(--cream);
            border-radius: 12px;
            border: 1px solid var(--border);
        }
        .info-label {
            font-size: 11px;
            color: var(--light-text);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .info-value { font-size: 14px; font-weight: 600; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.2s;
        }
        .form-input:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(212,168,67,0.1); }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-primary { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(212,168,67,0.3); }
        .btn-secondary { background: var(--cream); color: var(--dark-text); border: 1px solid var(--border); }
        
        .form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border); }
        
        .view-mode { display: block; }
        .edit-mode { display: none; }
        
        .tab-nav {
            display: flex;
            gap: 4px;
            background: white;
            padding: 6px;
            border-radius: 14px;
            margin-bottom: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .tab-btn {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 10px;
            background: transparent;
            font-size: 13px;
            font-weight: 600;
            color: var(--light-text);
            cursor: pointer;
            transition: all 0.2s;
        }
        .tab-btn.active { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .toast-container {
            position: fixed; top: 80px; right: 20px; z-index: 9999;
            display: flex; flex-direction: column; gap: 8px;
        }
        .toast {
            padding: 14px 20px; border-radius: 10px; color: white; font-size: 13px; font-weight: 500;
            display: flex; align-items: center; gap: 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn { from { opacity: 0; transform: translateX(60px); } to { opacity: 1; transform: translateX(0); } }
        .toast.success { background: linear-gradient(135deg, #059669, #34d399); }
        .toast.error { background: linear-gradient(135deg, #dc2626, #f87171); }
    </style>
</head>
<body class="dashboard-page">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../../../media/LOGO.jpg" alt="Logo" class="sidebar-logo" style="width: 70px; height: 70px; border-radius: 16px; object-fit: cover; border: 3px solid white; background: white; padding: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
            <div class="sidebar-brand"><span class="sidebar-brand-name">IBM</span></div>
        </div>
        <div class="sidebar-user">
            <div class="sidebar-avatar"><i class="fas fa-user"></i></div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name">Instructor</span>
                <span class="sidebar-user-role">Instructor</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-nav-label">Menu</div>
            <a href="../dashboard.php" class="sidebar-nav-item"><i class="fas fa-chart-pie"></i><span>Overview</span></a>
            <a href="students.php" class="sidebar-nav-item"><i class="fas fa-user-graduate"></i><span>Students</span></a>
            <a href="feedback.php" class="sidebar-nav-item"><i class="fas fa-comment-dots"></i><span>Feedback</span></a>
            <a href="reports.php" class="sidebar-nav-item"><i class="fas fa-file-alt"></i><span>Reports</span></a>
            <a href="profile.php" class="sidebar-nav-item active"><i class="fas fa-user"></i><span>Profile</span></a>
        </nav>
    </aside>
    <div class="main-content" style="position: relative;">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none;"></div>
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div><div class="topbar-title">My Profile</div><div class="topbar-subtitle">Manage your account</div></div>
            </div>
            <div class="topbar-right">
                <div class="topbar-date"><i class="fas fa-calendar-alt"></i><span><?php echo date('F j, Y'); ?></span></div>
                <a href="../../../data/logout.php" class="topbar-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </header>
        <main class="dashboard-content">
            <div class="page-container">
                <!-- Profile Header -->
                <?php
                $instructor_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
                require_once '../../../data/config.php';
                $profile = ['first_name' => '', 'last_name' => '', 'email' => '', 'position' => 'Instructor', 'phone' => '', 'middle_name' => '', 'suffix' => '', 'birthday' => null, 'avatar' => '', 'total_mentees' => 0, 'member_since' => '-' ];
                if ($instructor_id > 0 && $pdo) {
                    try {
                        $stmt = $pdo->prepare("SELECT * FROM instructors WHERE id = ?");
                        $stmt->execute([$instructor_id]);
                        $data = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($data) {
                            $profile = array_merge($profile, $data);
                            if (!empty($data['created_at'])) {
                                $profile['member_since'] = date('F Y', strtotime($data['created_at']));
                            }
                        }
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mentees WHERE mentor_id = ?");
                        $stmt->execute([$instructor_id]);
                        $profile['total_mentees'] = $stmt->fetchColumn();
                    } catch (PDOException $e) {
                        error_log("Profile load error: " . $e->getMessage());
                    }
                } elseif (!empty($_SESSION['user_name'])) {
                    $nameParts = explode(' ', trim($_SESSION['user_name']), 2);
                    $profile['first_name'] = $nameParts[0] ?? '';
                    $profile['last_name'] = $nameParts[1] ?? '';
                    $profile['email'] = $_SESSION['user_email'] ?? '';
                }
                $initials = strtoupper((!empty($profile['first_name']) ? $profile['first_name'][0] : 'I') . (!empty($profile['last_name']) ? $profile['last_name'][0] : '?'));
                $fullName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['middle_name'] ?? '') . ' ' . ($profile['last_name'] ?? '') . ' ' . ($profile['suffix'] ?? ''));
                $fullName = preg_replace('/\s+/', ' ', $fullName);
                if ($fullName === '') $fullName = 'Instructor';
                $avatarPath = '../../../media/instructors/' . $instructor_id . '.jpg';
                $avatarFullPath = dirname(__DIR__, 2) . '/media/instructors/' . $instructor_id . '.jpg';
                $hasAvatar = !empty($profile['avatar']) || file_exists($avatarFullPath);
                $avatarCache = $hasAvatar ? '?t=' . time() : '';
                ?>
                <div class="profile-header">
                    <div class="avatar-wrapper" style="position: relative;">
                        <?php if ($hasAvatar): ?>
                        <?php $avatarSrc = !empty($profile['avatar']) ? $profile['avatar'] : $instructor_id . '.jpg'; ?>
                        <img id="profileAvatar" src="../../../media/instructors/<?php echo $avatarSrc; ?><?php echo $avatarCache; ?>" class="avatar-img">
                        <?php else: ?>
                        <div class="avatar-circle" id="profileAvatar"><?php echo htmlspecialchars($initials); ?></div>
                        <?php endif; ?>
                        <label for="avatarUpload" class="avatar-edit">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="avatarUpload" accept="image/*" style="display: none;" onchange="uploadAvatar(this)">
                    </div>
                    <div class="profile-info">
                        <div class="profile-name-container">
                            <div class="profile-name"><?php echo htmlspecialchars($fullName); ?></div>
                        </div>
                        <div class="profile-role"><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($profile['position'] ?? 'Instructor'); ?></div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="tab-nav">
                    <button class="tab-btn active" onclick="switchTab('personal')"><i class="fas fa-user"></i> Personal</button>
                    <button class="tab-btn" onclick="switchTab('contact')"><i class="fas fa-address-book"></i> Contact</button>
                    <button class="tab-btn" onclick="switchTab('security')"><i class="fas fa-shield-alt"></i> Security</button>
                </div>
                
                <!-- Personal Tab -->
                <div class="tab-content active" id="personalTab">
                    <div class="content-card" id="personalCard">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user"></i> Personal Information</h3>
                            <div class="card-header-actions">
                                <button type="button" class="btn-edit" id="editPersonalBtn" onclick="editPersonal()"><i class="fas fa-edit"></i> Edit</button>
                            </div>
                        </div>
                        <div class="info-grid profile-section" id="personalInfo">
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-user"></i> First Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($profile['first_name'] ?? '-'); ?></div>
                                <div class="edit-input"><input type="text" name="first_name" value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-user"></i> Middle Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($profile['middle_name'] ?? '-'); ?></div>
                                <div class="edit-input"><input type="text" name="middle_name" value="<?php echo htmlspecialchars($profile['middle_name'] ?? ''); ?>"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-user"></i> Last Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($profile['last_name'] ?? '-'); ?></div>
                                <div class="edit-input"><input type="text" name="last_name" value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-user"></i> Suffix</div>
                                <div class="info-value"><?php echo htmlspecialchars($profile['suffix'] ?? '-'); ?></div>
                                <div class="edit-input"><input type="text" name="suffix" value="<?php echo htmlspecialchars($profile['suffix'] ?? ''); ?>"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($profile['email'] ?? '-'); ?></div>
                                <div class="edit-input"><input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" readonly></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-briefcase"></i> Position</div>
                                <div class="info-value"><?php echo htmlspecialchars($profile['position'] ?? '-'); ?></div>
                                <div class="edit-input"><input type="text" name="position" value="<?php echo htmlspecialchars($profile['position'] ?? ''); ?>"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-birthday-cake"></i> Birthday</div>
                                <div class="info-value"><?php echo !empty($profile['birthday']) ? date('F j, Y', strtotime($profile['birthday'])) : '-'; ?></div>
                                <div class="edit-input"><input type="date" name="birthday" value="<?php echo htmlspecialchars($profile['birthday'] ?? ''); ?>"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-toggle-on"></i> Status</div>
                                <div class="info-value"><?php echo htmlspecialchars($profile['status'] ?? '-'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Tab -->
                <div class="tab-content" id="contactTab">
                    <div class="content-card" id="contactCard">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-address-book"></i> Contact Information</h3>
                            <div class="card-header-actions">
                                <button type="button" class="btn-edit" id="editContactBtn" onclick="editContact()"><i class="fas fa-edit"></i> Edit</button>
                            </div>
                        </div>
                        <div class="info-grid profile-section" id="contactInfo">
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-envelope"></i> Email Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($profile['email'] ?? '-'); ?></div>
                                <div class="edit-input"><input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label"><i class="fas fa-phone"></i> Phone Number</div>
                                <div class="info-value"><?php echo htmlspecialchars($profile['phone'] ?: '-'); ?></div>
                                <div class="edit-input"><input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Security Tab -->
                <div class="tab-content" id="securityTab">
                    <div class="content-card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-key"></i> Change Password</h3>
                        </div>
                        
                        <div class="password-warning" style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                            <div style="display: flex; align-items: center; gap: 12px; color: #92400e; font-weight: 600;">
                                <i class="fas fa-info-circle"></i> Password Requirements
                            </div>
                            <ul style="margin-top: 12px; padding-left: 24px; color: #78350f; font-size: 14px; line-height: 1.8;">
                                <li>At least 8 characters long</li>
                                <li>Mix of uppercase and lowercase letters</li>
                                <li>Include at least one number</li>
                                <li>Include at least one special character (!@#$%^&*)</li>
                            </ul>
                        </div>
                        
                        <form id="passwordForm" style="max-width: 500px;">
                            <div class="form-group">
                                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--dark-text);">
                                    <i class="fas fa-lock"></i> Current Password
                                </label>
                                <div style="display: flex; gap: 8px;">
                                    <input type="password" class="form-input" id="currentPassword" name="current_password" required 
                                           style="flex: 1; padding: 14px 16px; border: 2px solid var(--border); border-radius: 10px; font-size: 14px; box-sizing: border-box;"
                                           placeholder="Enter current password">
                                    <button type="button" id="verifyCurrentBtn" onclick="verifyCurrentPassword()" 
                                            style="padding: 14px 20px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: white;">
                                        <i class="fas fa-check"></i> Verify
                                    </button>
                                </div>
                                <div id="currentPasswordError" style="display: none; color: #dc2626; font-size: 12px; margin-top: 8px; padding: 10px; background: #fef2f2; border-radius: 8px; border-left: 3px solid #dc2626;">
                                    <i class="fas fa-exclamation-circle"></i> <span id="currentPasswordErrorText"></span>
                                </div>
                            </div>
                            
                            <div id="newPasswordSection" style="display: none;">
                            
                            <div class="form-group">
                                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--dark-text);">
                                    <i class="fas fa-key"></i> New Password
                                </label>
                                <input type="password" class="form-input" name="new_password" id="newPasswordInput" required minlength="8"
                                       style="width: 100%; padding: 14px 16px; border: 2px solid var(--border); border-radius: 10px; font-size: 14px; box-sizing: border-box;"
                                       placeholder="Enter new password">
                                <div class="password-strength" id="passwordStrength" style="margin-top: 8px;">
                                    <div style="display: flex; gap: 4px;">
                                        <div id="strength1" style="flex: 1; height: 4px; background: #e5e7eb; border-radius: 2px;"></div>
                                        <div id="strength2" style="flex: 1; height: 4px; background: #e5e7eb; border-radius: 2px;"></div>
                                        <div id="strength3" style="flex: 1; height: 4px; background: #e5e7eb; border-radius: 2px;"></div>
                                        <div id="strength4" style="flex: 1; height: 4px; background: #e5e7eb; border-radius: 2px;"></div>
                                    </div>
                                    <div id="strengthText" style="font-size: 12px; margin-top: 4px; color: #6b7280;"></div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--dark-text);">
                                    <i class="fas fa-check-double"></i> Confirm New Password
                                </label>
                                <input type="password" class="form-input" name="confirm_password" id="confirmPasswordInput" required 
                                       style="width: 100%; padding: 14px 16px; border: 2px solid var(--border); border-radius: 10px; font-size: 14px; box-sizing: border-box;"
                                       placeholder="Confirm new password">
                                <div id="matchStatus" style="font-size: 12px; margin-top: 4px;"></div>
                            </div>
                            
                            </div>
                            
                            <div class="form-actions" style="display: flex; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border);">
                                <button type="submit" class="btn" id="changePasswordBtn" 
                                        style="flex: 1; padding: 14px 24px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: white; display: none;">
                                    <i class="fas fa-save"></i> Change Password
                                </button>
                                <button type="button" class="btn" onclick="document.getElementById('passwordForm').reset(); clearPasswordFields();"
                                        style="padding: 14px 24px; border: 1px solid var(--border); border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; background: white; color: var(--dark-text);">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <div class="toast-container" id="toastContainer"></div>
    <script>
        function cancelEdit() {
            if (document.getElementById('personalInfo')?.classList.contains('edit-mode')) {
                cancelPersonal();
            }
            if (document.getElementById('contactInfo')?.classList.contains('edit-mode')) {
                cancelContact();
            }
        }
        
        function switchTab(tab) {
            cancelEdit();
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById(tab + 'Tab').classList.add('active');
        }
        
        function editPersonal() {
            const infoGrid = document.getElementById('personalInfo');
            
            // Toggle visibility for all info items
            const items = infoGrid.querySelectorAll('.info-item');
            items.forEach((item, index) => {
                const infoValue = item.querySelector('.info-value');
                const editInput = item.querySelector('.edit-input');
                
                // Store original display state
                item.dataset.originalDisplay = item.style.display || '';
                
                if (infoValue) infoValue.style.setProperty('display', 'none', 'important');
                if (editInput) {
                    editInput.style.setProperty('display', 'block', 'important');
                    const input = editInput.querySelector('input');
                    if (input && index === 0) {
                        setTimeout(() => input.focus(), 100);
                    }
                }
            });
            
            // Add edit indicator to title
            const cardHeader = document.querySelector('#personalCard .card-header');
            if (cardHeader && !cardHeader.querySelector('.edit-mode-indicator')) {
                const indicator = document.createElement('span');
                indicator.className = 'edit-mode-indicator';
                indicator.style.cssText = 'display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg, var(--gold), var(--gold-dark));color:white;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;';
                indicator.innerHTML = '<i class="fas fa-pen"></i> Editing';
                cardHeader.appendChild(indicator);
            }
            
            // Update buttons
            const actions = document.querySelector('#personalCard .card-header-actions');
            actions.innerHTML = '<button type="button" class="btn-save" id="savePersonalBtn" onclick="savePersonal()" style="padding:10px 24px;border:none;border-radius:10px;font-family:Poppins,sans-serif;font-size:14px;font-weight:600;cursor:pointer;background:linear-gradient(135deg, var(--gold), var(--gold-dark));color:white;"><i class="fas fa-save"></i> Save Changes</button><button type="button" onclick="cancelPersonal()" style="padding:10px 24px;border:none;border-radius:10px;font-family:Poppins,sans-serif;font-size:14px;font-weight:600;cursor:pointer;background:#f3f4f6;color:var(--dark-text);margin-left:8px;"><i class="fas fa-times"></i> Cancel</button>';
        }
        
        function cancelPersonal() {
            const infoGrid = document.getElementById('personalInfo');
            
            // Restore visibility for all info items
            const items = infoGrid.querySelectorAll('.info-item');
            items.forEach(item => {
                const infoValue = item.querySelector('.info-value');
                const editInput = item.querySelector('.edit-input');
                
                if (infoValue) infoValue.style.display = '';
                if (editInput) editInput.style.display = '';
            });
            
            // Remove edit indicator
            const indicator = document.querySelector('#personalCard .edit-mode-indicator');
            if (indicator) indicator.remove();
            
            // Restore buttons
            const actions = document.querySelector('#personalCard .card-header-actions');
            actions.innerHTML = '<button type="button" class="btn-edit" id="editPersonalBtn" onclick="editPersonal()" style="padding:10px 20px;border:none;border-radius:10px;font-family:Poppins,sans-serif;font-size:14px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg, var(--gold), var(--gold-dark));color:white;box-shadow:0 4px 12px rgba(212,168,67,0.3);"><i class="fas fa-edit"></i> Edit</button>';
        }
        
        function savePersonal() {
            const saveBtn = document.getElementById('savePersonalBtn');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            saveBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'update_personal');
            formData.append('instructor_id', <?php echo $instructor_id; ?>);
            
            const inputs = document.querySelectorAll('#personalInfo .edit-input input');
            inputs.forEach(input => {
                if (!input.readOnly) formData.append(input.name, input.value);
            });
            
            fetch('../../../data/profile_process.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                saveBtn.disabled = false;
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                    saveBtn.innerHTML = originalText;
                }
            })
            .catch(err => {
                saveBtn.disabled = false;
                showToast('Error: ' + err.message, 'error');
                saveBtn.innerHTML = originalText;
            });
        }
        
        function editContact() {
            const infoGrid = document.getElementById('contactInfo');
            
            // Toggle visibility for all info items
            const items = infoGrid.querySelectorAll('.info-item');
            items.forEach((item, index) => {
                const infoValue = item.querySelector('.info-value');
                const editInput = item.querySelector('.edit-input');
                
                item.dataset.originalDisplay = item.style.display || '';
                
                if (infoValue) infoValue.style.setProperty('display', 'none', 'important');
                if (editInput) {
                    editInput.style.setProperty('display', 'block', 'important');
                    const input = editInput.querySelector('input');
                    if (input && index === 0) {
                        setTimeout(() => input.focus(), 100);
                    }
                }
            });
            
            // Add edit indicator to header
            const cardHeader = document.querySelector('#contactCard .card-header');
            if (cardHeader && !cardHeader.querySelector('.edit-mode-indicator')) {
                const indicator = document.createElement('span');
                indicator.className = 'edit-mode-indicator';
                indicator.style.cssText = 'display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg, var(--gold), var(--gold-dark));color:white;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;';
                indicator.innerHTML = '<i class="fas fa-pen"></i> Editing';
                cardHeader.appendChild(indicator);
            }
            
            const actions = document.querySelector('#contactCard .card-header-actions');
            actions.innerHTML = '<button type="button" class="btn-save" id="saveContactBtn" onclick="saveContact()" style="padding:10px 24px;border:none;border-radius:10px;font-family:Poppins,sans-serif;font-size:14px;font-weight:600;cursor:pointer;background:linear-gradient(135deg, var(--gold), var(--gold-dark));color:white;"><i class="fas fa-save"></i> Save Changes</button><button type="button" onclick="cancelContact()" style="padding:10px 24px;border:none;border-radius:10px;font-family:Poppins,sans-serif;font-size:14px;font-weight:600;cursor:pointer;background:#f3f4f6;color:var(--dark-text);margin-left:8px;"><i class="fas fa-times"></i> Cancel</button>';
        }
        
        function cancelContact() {
            const infoGrid = document.getElementById('contactInfo');
            
            const items = infoGrid.querySelectorAll('.info-item');
            items.forEach(item => {
                const infoValue = item.querySelector('.info-value');
                const editInput = item.querySelector('.edit-input');
                if (infoValue) infoValue.style.display = '';
                if (editInput) editInput.style.display = '';
            });
            
            const indicator = document.querySelector('#contactCard .edit-mode-indicator');
            if (indicator) indicator.remove();
            
            const actions = document.querySelector('#contactCard .card-header-actions');
            actions.innerHTML = '<button type="button" class="btn-edit" id="editContactBtn" onclick="editContact()" style="padding:10px 20px;border:none;border-radius:10px;font-family:Poppins,sans-serif;font-size:14px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg, var(--gold), var(--gold-dark));color:white;box-shadow:0 4px 12px rgba(212,168,67,0.3);"><i class="fas fa-edit"></i> Edit</button>';
        }
        
        function saveContact() {
            const saveBtn = document.getElementById('saveContactBtn');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            saveBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'update_contact');
            formData.append('instructor_id', <?php echo $instructor_id; ?>);
            
            const inputs = document.querySelectorAll('#contactInfo .edit-input input');
            inputs.forEach(input => formData.append(input.name, input.value));
            
            fetch('../../../data/profile_process.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                saveBtn.disabled = false;
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                    saveBtn.innerHTML = originalText;
                }
            })
            .catch(err => {
                saveBtn.disabled = false;
                showToast('Error: ' + err.message, 'error');
                saveBtn.innerHTML = originalText;
            });
        }
        
        function uploadAvatar(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            if (!file.type.match(/image.*/)) { showToast('Please select an image file', 'error'); return; }
            
            const instructorId = <?php echo $instructor_id; ?>;
            console.log('Uploading avatar for instructor ID:', instructorId);
            
            const formData = new FormData();
            formData.append('avatar', file);
            formData.append('instructor_id', instructorId);
            
            fetch('../../../data/upload_avatar.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                console.log('Upload response:', data);
                if (data.success) {
                    showToast('Profile picture updated!', 'success');
                    const avatarEl = document.getElementById('profileAvatar');
                    if (avatarEl) {
                        const newImg = document.createElement('img');
                        newImg.id = 'profileAvatar';
                        newImg.className = 'avatar-img';
                        newImg.style.cssText = 'width: 120px; height: 120px; border-radius: 50%; object-fit: cover;';
                        newImg.src = '../../../media/instructors/' + instructorId + '.jpg?t=' + Date.now();
                        if (avatarEl.tagName === 'DIV') {
                            avatarEl.parentNode.replaceChild(newImg, avatarEl);
                        } else {
                            avatarEl.src = newImg.src;
                        }
                    }
                } else {
                    showToast(data.message || 'Failed to upload', 'error');
                }
            })
            .catch(err => showToast('Error: ' + err.message, 'error'));
            input.value = '';
        }
        
        function showToast(msg, type) {
            const div = document.createElement('div');
            div.className = 'toast ' + type;
            div.innerHTML = '<i class="fas fa-' + (type=='success'?'check-circle':'exclamation-circle') + '"></i> ' + msg;
            document.getElementById('toastContainer').appendChild(div);
            setTimeout(() => div.remove(), 4000);
        }
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const currentPassword = this.current_password.value;
            const newPassword = this.new_password.value;
            const confirmPassword = this.confirm_password.value;
            
            // Validation checks
            if (currentPassword.length < 1) {
                showToast('Please enter your current password', 'error');
                return;
            }
            
            if (newPassword.length < 8) {
                showToast('Password must be at least 8 characters', 'error');
                return;
            }
            
            if (!/[A-Z]/.test(newPassword) || !/[a-z]/.test(newPassword)) {
                showToast('Password must contain uppercase and lowercase letters', 'error');
                return;
            }
            
            if (!/[0-9]/.test(newPassword)) {
                showToast('Password must contain at least one number', 'error');
                return;
            }
            
            if (!/[!@#$%^&*]/.test(newPassword)) {
                showToast('Password must contain at least one special character (!@#$%^&*)', 'error');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showToast('New passwords do not match', 'error');
                return;
            }
            
            if (currentPassword === newPassword) {
                showToast('New password cannot be the same as current password', 'error');
                return;
            }
            
            // Change password
            const btn = document.getElementById('changePasswordBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Changing...';
            btn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'change_password');
            formData.append('instructor_id', <?php echo $instructor_id; ?>);
            formData.append('current_password', currentPassword);
            formData.append('new_password', newPassword);
            
            fetch('../../../data/profile_process.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                if (data.success) {
                    showToast(data.message, 'success');
                    document.getElementById('passwordForm').reset();
                    clearPasswordFields();
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(err => {
                btn.disabled = false;
                showToast('Error: ' + err.message, 'error');
            });
        });
        
        // Password strength indicator
        document.querySelector('input[name="new_password"]').addEventListener('input', function(e) {
            const password = e.target.value;
            const strength1 = document.getElementById('strength1');
            const strength2 = document.getElementById('strength2');
            const strength3 = document.getElementById('strength3');
            const strength4 = document.getElementById('strength4');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[!@#$%^&*]/.test(password)) strength++;
            
            const colors = ['#e5e7eb', '#ef4444', '#f59e0b', '#10b981', '#059669'];
            const texts = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            
            strength1.style.background = colors[strength >= 1 ? strength : 0];
            strength2.style.background = colors[strength >= 2 ? strength : 0];
            strength3.style.background = colors[strength >= 3 ? strength : 0];
            strength4.style.background = colors[strength >= 4 ? strength : 0];
            
            if (password.length > 0) {
                strengthText.textContent = texts[strength];
                strengthText.style.color = colors[strength];
            } else {
                strengthText.textContent = '';
            }
        });
        
        // Confirm password match indicator
        document.querySelector('input[name="confirm_password"]').addEventListener('input', function(e) {
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = e.target.value;
            const matchStatus = document.getElementById('matchStatus');
            
            if (confirmPassword.length > 0) {
                if (newPassword === confirmPassword) {
                    matchStatus.innerHTML = '<i class="fas fa-check" style="color: #10b981;"></i> Passwords match';
                    matchStatus.style.color = '#10b981';
                } else {
                    matchStatus.innerHTML = '<i class="fas fa-times" style="color: #ef4444;"></i> Passwords do not match';
                    matchStatus.style.color = '#ef4444';
                }
            } else {
                matchStatus.innerHTML = '';
            }
        });
        
        function verifyCurrentPassword() {
            const currentPassword = document.getElementById('currentPassword').value;
            const verifyBtn = document.getElementById('verifyCurrentBtn');
            
            if (!currentPassword) {
                showToast('Please enter your current password', 'error');
                return;
            }
            
            verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            verifyBtn.disabled = true;
            
            // Hide error warning when user types
            document.getElementById('currentPasswordError').style.display = 'none';
            document.getElementById('currentPassword').style.borderColor = '';
            
            const formData = new FormData();
            formData.append('action', 'verify_current_password');
            formData.append('instructor_id', <?php echo $instructor_id; ?>);
            formData.append('current_password', currentPassword);
            
            fetch('../../../data/profile_process.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                verifyBtn.disabled = false;
                const errorDiv = document.getElementById('currentPasswordError');
                const errorText = document.getElementById('currentPasswordErrorText');
                
                if (data.success) {
                    errorDiv.style.display = 'none';
                    showToast(data.message, 'success');
                    document.getElementById('currentPassword').disabled = true;
                    verifyBtn.style.display = 'none';
                    document.getElementById('newPasswordSection').style.display = 'block';
                    document.getElementById('changePasswordBtn').style.display = 'block';
                    document.getElementById('currentPassword').parentElement.querySelector('label').innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Current Password (Verified)';
                } else {
                    errorText.textContent = data.message;
                    errorDiv.style.display = 'flex';
                    document.getElementById('currentPassword').style.borderColor = '#dc2626';
                    verifyBtn.innerHTML = '<i class="fas fa-check"></i> Verify';
                    
                    // Auto-hide error after 3 seconds
                    setTimeout(() => {
                        errorDiv.style.display = 'none';
                        document.getElementById('currentPassword').style.borderColor = '';
                    }, 3000);
                }
            })
            .catch(err => {
                verifyBtn.disabled = false;
                showToast('Error: ' + err.message, 'error');
                verifyBtn.innerHTML = '<i class="fas fa-check"></i> Verify';
            });
        }
        
        function clearPasswordFields() {
            // Reset form visibility
            document.getElementById('newPasswordSection').style.display = 'none';
            document.getElementById('changePasswordBtn').style.display = 'none';
            document.getElementById('currentPassword').disabled = false;
            document.getElementById('currentPassword').value = '';
            document.getElementById('verifyCurrentBtn').style.display = 'inline-flex';
            document.getElementById('verifyCurrentBtn').innerHTML = '<i class="fas fa-check"></i> Verify';
            document.getElementById('currentPassword').parentElement.querySelector('label').innerHTML = '<i class="fas fa-lock"></i> Current Password';
            
            const strength1 = document.getElementById('strength1');
            const strength2 = document.getElementById('strength2');
            const strength3 = document.getElementById('strength3');
            const strength4 = document.getElementById('strength4');
            const strengthText = document.getElementById('strengthText');
            const matchStatus = document.getElementById('matchStatus');
            
            document.getElementById('newPasswordInput').value = '';
            document.getElementById('confirmPasswordInput').value = '';
            
            strength1.style.background = '#e5e7eb';
            strength2.style.background = '#e5e7eb';
            strength3.style.background = '#e5e7eb';
            strength4.style.background = '#e5e7eb';
            strengthText.textContent = '';
            matchStatus.innerHTML = '';
        }
    </script>
</body>
</html>
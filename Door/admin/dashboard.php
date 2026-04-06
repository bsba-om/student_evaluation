<?php
require_once '../../data/session_security.php';
check_auth('admin', '../login.php');

$user_name = $_SESSION['user_name'] ?? 'Administrator';
$user_role = $_SESSION['user_role'] ?? 'admin';
$current_page = $_GET['page'] ?? 'dashboard';

// Get page content
$page_file = __DIR__ . '/pages/' . $current_page . '.php';
if (!file_exists($page_file)) {
    $page_file = __DIR__ . '/pages/dashboard_overview.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../media/LOGO.jpg" type="image/jpeg">
    <title>Admin Dashboard - Faculty Evaluation System</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        :root {
         --gold: #d4a843;
         --gold-light: #e8c768;
         --gold-dark: #b8922f;
         --cream: #fef9f3;
         --white: #ffffff;
         --dark-text: #1a1a2e;
         --light-text: #6b7280;
         --border-light: #e5e7eb;
         --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
         --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
         --danger: #dc2626;
         --success: #16a34a;
         --warning: #f59e0b;
         --info: #3b82f6;
         --purple: #8b5cf6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--cream);
            min-height: 100vh;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1a1a2e 0%, #2d2d44 100%);
            color: white;
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-logo {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 24px;
            color: white;
        }

        .sidebar-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--gold);
            margin-bottom: 4px;
        }

        .sidebar-subtitle {
            font-size: 12px;
            color: rgba(255,255,255,0.6);
        }

        .sidebar-menu {
            padding: 16px 0;
        }

        .menu-section {
            padding: 8px 16px;
            font-size: 11px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.4);
            letter-spacing: 1px;
            margin-top: 16px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 24px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            font-size: 14px;
            font-weight: 500;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.05);
            color: white;
        }

        .menu-item.active {
            background: rgba(212, 168, 67, 0.15);
            color: var(--gold);
            border-left-color: var(--gold);
        }

        .menu-item i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .menu-item span {
            flex: 1;
        }

        .menu-badge {
            background: var(--danger);
            color: white;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 16px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            margin-bottom: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 14px;
        }

        .user-details {
            flex: 1;
            overflow: hidden;
        }

        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 11px;
            color: var(--gold);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px;
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #fca5a5;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background: rgba(220, 38, 38, 0.2);
            color: white;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 24px;
            background: var(--cream);
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-text);
        }

        .page-subtitle {
            font-size: 13px;
            color: var(--light-text);
            margin-top: 4px;
        }

        /* Quick Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
        }

        .stat-icon.gold {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: white;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            color: white;
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #16a34a, #4ade80);
            color: white;
        }

        .stat-icon.purple {
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            color: white;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--dark-text);
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 13px;
            color: var(--light-text);
        }

        /* Table Card */
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark-text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--gold);
        }

        .card-body {
            padding: 0;
        }

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            padding: 14px 20px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--light-text);
            background: var(--cream);
            border-bottom: 1px solid var(--border-light);
        }

        .data-table td {
            padding: 16px 20px;
            font-size: 14px;
            color: var(--dark-text);
            border-bottom: 1px solid var(--border-light);
        }

        .data-table tr:hover td {
            background: var(--cream);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.active {
            background: rgba(22, 163, 74, 0.1);
            color: #16a34a;
        }

        .status-badge.inactive {
            background: rgba(107, 114, 128, 0.1);
            color: #6b7280;
        }

        .status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        /* Action Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(212, 168, 67, 0.4);
        }

        .btn-danger {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
            border: 1px solid rgba(220, 38, 38, 0.2);
        }

        .btn-danger:hover {
            background: #dc2626;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .btn-success {
            background: rgba(22, 163, 74, 0.1);
            color: #16a34a;
            border: 1px solid rgba(22, 163, 74, 0.2);
        }

        .btn-success:hover {
            background: #16a34a;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn i {
            font-size: 14px;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            padding: 24px;
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

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-light);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            color: var(--dark-text);
            background: var(--cream);
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--gold);
            background: white;
            box-shadow: 0 0 0 3px rgba(212, 168, 67, 0.1);
        }

        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-light);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            color: var(--dark-text);
            background: var(--cream);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .form-select:focus {
            outline: none;
            border-color: var(--gold);
            background: white;
            box-shadow: 0 0 0 3px rgba(212, 168, 67, 0.1);
        }

        .form-actions {
            padding: 0 24px 24px;
            display: flex;
            gap: 12px;
        }

        /* Alert/Message */
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

        .alert i {
            font-size: 18px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 24px;
        }

        .empty-state i {
            font-size: 48px;
            color: var(--border-light);
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 18px;
            color: var(--dark-text);
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
            color: var(--light-text);
        }

        /* Toast */
        .toast {
            position: fixed;
            top: 24px;
            right: 24px;
            padding: 16px 24px;
            border-radius: 12px;
            background: white;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            transform: translateX(400px);
            transition: all 0.3s ease;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast-success {
            border-left: 4px solid #16a34a;
        }

        .toast-error {
            border-left: 4px solid #dc2626;
        }

        .toast-icon {
            font-size: 20px;
        }

        .toast-success .toast-icon {
            color: #16a34a;
        }

        .toast-error .toast-icon {
            color: #dc2626;
        }

        .toast-message {
            font-size: 14px;
            font-weight: 500;
            color: var(--dark-text);
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            transform: scale(0.8);
            transition: all 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-overlay.show .modal {
            transform: scale(1);
        }

        .modal-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
        }

        .modal-icon.success {
            background: rgba(22, 163, 74, 0.1);
            color: #16a34a;
        }

        .modal-icon.error {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
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

        .modal-btn {
            padding: 12px 32px;
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-btn-primary {
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: white;
        }

        .modal-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(212, 168, 67, 0.4);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
            }

            .sidebar-header {
                padding: 16px;
            }

            .sidebar-logo {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .sidebar-title, .sidebar-subtitle {
                display: none;
            }

            .menu-item {
                padding: 14px;
                justify-content: center;
            }

            .menu-item span, .menu-badge {
                display: none;
            }

            .main-content {
                margin-left: 80px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-crown"></i>
                </div>
                <h2 class="sidebar-title">Admin Panel</h2>
            </div>

            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item <?php echo $current_page == 'dashboard' || $current_page == 'dashboard_overview' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>

                <div class="menu-section">Management</div>
                 <a href="dashboard.php?page=manage_program_heads" class="menu-item <?php echo $current_page == 'manage_program_heads' || $current_page == 'add_program_head' || $current_page == 'remove_program_head' ? 'active' : ''; ?>">
                     <i class="fas fa-user-tie"></i>
                     <span>Program Heads</span>
                 </a>

                 <a href="dashboard.php?page=system_health" class="menu-item <?php echo $current_page == 'system_health' ? 'active' : ''; ?>">
                     <i class="fas fa-heartbeat"></i>
                     <span>System Health</span>
                 </a>

                 <div class="menu-section">System</div>
                 <a href="dashboard.php?page=settings" class="menu-item <?php echo $current_page == 'settings' ? 'active' : ''; ?>">
                     <i class="fas fa-cog"></i>
                     <span>Settings</span>
                 </a>
            </nav>

            <div class="sidebar-footer">
                 <a href="/bs/data/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <?php
            // Check if admin needs to complete setup (demo account)
            $needs_setup = false;
            if (isset($_SESSION['admin_requires_setup']) && $_SESSION['admin_requires_setup']) {
                $needs_setup = true;
            }
            ?>
            <?php if ($needs_setup): ?>
                <div id="adminSetupSection" style="max-width: 600px; margin: 0 auto 24px; background: white; border-radius: 16px; padding: 24px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-light);">
                    <h2 style="margin-bottom: 16px; font-size: 18px; color: var(--dark-text); display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-user-shield" style="color: var(--gold);"></i> Complete Your Account Setup
                    </h2>
                    <p style="color: var(--light-text); margin-bottom: 20px; font-size: 14px;">
                        For security, please set up your permanent credentials. After saving, the demo account will be disabled.
                    </p>
                    <form id="adminSetupForm">
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label class="form-label">New Email Address</label>
                            <input type="email" name="new_email" class="form-input" required placeholder="Enter new email">
                        </div>
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-input" required minlength="6" placeholder="At least 6 characters">
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-input" required placeholder="Re-enter password">
                        </div>
                        <button type="submit" class="btn btn-primary" style="min-width: 140px;">
                            <i class="fas fa-save"></i> Save & Continue
                        </button>
                    </form>
                    <div id="adminSetupMessage" style="margin-top: 16px; padding: 12px; border-radius: 10px; display: none;"></div>
                </div>
            <?php endif; ?>
            <?php include $page_file; ?>
        </main>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <div class="modal-icon" id="modalIcon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="modal-title" id="modalTitle">Success!</h3>
            <p class="modal-message" id="modalMessage">Operation completed successfully.</p>
            <button class="modal-btn modal-btn-primary" onclick="closeModal()">OK</button>
        </div>
    </div>

    <script>
        // Modal functions
        function showModal(message, type = 'success') {
            const modalOverlay = document.getElementById('modalOverlay');
            const modalIcon = document.getElementById('modalIcon');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            
            if (type === 'success') {
                modalIcon.className = 'modal-icon success';
                modalIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
                modalTitle.textContent = 'Success!';
            } else {
                modalIcon.className = 'modal-icon error';
                modalIcon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
                modalTitle.textContent = 'Error!';
            }
            
            modalMessage.textContent = message;
            modalOverlay.classList.add('show');
        }

        function closeModal() {
            const modalOverlay = document.getElementById('modalOverlay');
            modalOverlay.classList.remove('show');
            // Remove query params from URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Close modal when clicking outside
        document.getElementById('modalOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} toast-icon"></i>
                <span class="toast-message">${message}</span>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Show modal if URL has success parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success')) {
            showModal(decodeURIComponent(urlParams.get('success')), 'success');
        }
        if (urlParams.get('error')) {
            showModal(decodeURIComponent(urlParams.get('error')), 'error');
        }
    </script>
     <script src="../../function/session_guard.js"></script>
     <script>
document.addEventListener('DOMContentLoaded', function() {
    const setupForm = document.getElementById('adminSetupForm');
    if (setupForm) {
        setupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const messageDiv = document.getElementById('adminSetupMessage');
            const submitBtn = setupForm.querySelector('button[type="submit"]');
            const originalBtnContent = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            const formData = new FormData(setupForm);

            fetch('../../data/complete_admin_setup.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;
                if (data.success) {
                    messageDiv.textContent = data.message;
                    messageDiv.style.background = 'rgba(22, 163, 74, 0.1)';
                    messageDiv.style.border = '1px solid rgba(22, 163, 74, 0.2)';
                    messageDiv.style.color = '#16a34a';
                    messageDiv.style.display = 'block';
                    // Reload page after delay to reflect changes
                    setTimeout(() => location.reload(), 2000);
                } else {
                    messageDiv.textContent = data.message;
                    messageDiv.style.background = 'rgba(220, 38, 38, 0.1)';
                    messageDiv.style.border = '1px solid rgba(220, 38, 38, 0.2)';
                    messageDiv.style.color = '#dc2626';
                    messageDiv.style.display = 'block';
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;
                messageDiv.textContent = 'An error occurred. Please try again.';
                messageDiv.style.background = 'rgba(220, 38, 38, 0.1)';
                messageDiv.style.border = '1px solid rgba(220, 38, 38, 0.2)';
                messageDiv.style.color = '#dc2626';
                messageDiv.style.display = 'block';
                console.error('Setup error:', err);
            });
        });
    }
});
</script>
 </body>
</html>

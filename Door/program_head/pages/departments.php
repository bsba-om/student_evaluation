<?php
require_once '../../../data/session_security.php';

// Check role access - returns array with access status
$role_access = check_role_access('program_head');
$show_role_modal = !$role_access['allowed'];

$user_name = $_SESSION['user_name'] ?? 'Program Head';

// Only fetch data if access is allowed
if (!$show_role_modal) {
    require_once '../../../data/config.php';
    
    // Fetch departments with table existence check
    $departments = [];
    try {
        // Check if departments table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'departments'");
        if ($stmt->rowCount() > 0) {
            $sql = "SELECT department_name, icon_class, gradient_from, gradient_to, instructor_count, course_count FROM departments ORDER BY department_name";
            $stmt = $pdo->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $departments[] = $row;
            }
        } else {
            // departments table doesn't exist, leave array empty
            $departments = [];
        }
    } catch (PDOException $e) {
        $departments = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - Program Head Dashboard</title>
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
        body { 
            font-family: 'Poppins', sans-serif; 
            background: var(--cream);
            overflow-x: hidden;
        }
        .page-container { 
            padding: 24px; 
        }
        .page-header { 
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
        .card { 
            background: white; 
            border-radius: 16px; 
            padding: 24px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
            border: 1px solid var(--border-light); 
            margin-bottom: 20px; 
        }
        .card-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px; 
        }
        .card-title { 
            font-size: 16px; 
            font-weight: 700; 
            color: var(--dark-text); 
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-title i {
            color: var(--gold-dark);
        }
        .dept-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 20px; 
        }
        .dept-card { 
            background: var(--cream); 
            border-radius: 12px; 
            padding: 20px; 
            border: 1px solid var(--border-light); 
            display: flex; 
            align-items: center; 
            gap: 16px; 
            transition: all 0.2s ease;
        }
        .dept-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .dept-icon { 
            width: 48px; 
            height: 48px; 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 20px; 
            color: white; 
            flex-shrink: 0;
        }
        .dept-info { 
            flex: 1; 
            min-width: 0;
        }
        .dept-name { 
            font-size: 16px; 
            font-weight: 700; 
            color: var(--dark-text); 
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dept-meta { 
            font-size: 13px; 
            color: var(--light-text); 
            margin-top: 4px; 
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .btn-add {
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(184, 134, 11, 0.3);
        }
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
              <a href="departments.php" class="sidebar-nav-item active"><i class="fas fa-building"></i><span>Departments</span></a>
            <a href="reports.php" class="sidebar-nav-item"><i class="fas fa-file-alt"></i><span>Reports</span></a>
            <a href="settings.php" class="sidebar-nav-item"><i class="fas fa-cog"></i><span>Settings</span></a>
        </nav>
    </aside>
    <div class="main-content" style="position: relative;">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div><div class="topbar-title">Departments</div><div class="topbar-subtitle">Program Head Panel</div></div>
            </div>
            <div class="topbar-right">
                <div class="topbar-date"><i class="fas fa-calendar-alt"></i><span><?php echo date('F j, Y'); ?></span></div>
                <a href="../../../data/logout.php" class="topbar-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </header>
        <main class="dashboard-content">
            <div class="page-container">
                <div class="page-header">
                    <h1 class="page-title">Departments</h1>
                    <p class="page-subtitle">View all departments in the institution</p>
                 </div>
                 <div class="dept-grid">
                     <?php if (empty($departments)): ?>
                     <div class="dept-card" style="grid-column: 1 / -1; justify-content: center; padding: 40px;">
                         <div style="text-align: center;">
                             <i class="fas fa-building" style="font-size: 48px; color: var(--light-text); opacity: 0.3; margin-bottom: 16px;"></i>
                             <h3 style="color: var(--dark-text); margin-bottom: 8px;">No Departments Configured</h3>
                             <p style="color: var(--light-text); max-width: 600px; margin: 0 auto;">
                                 The departments table is not yet set up in the database. Please configure your departments to view them here.
                             </p>
                         </div>
                     </div>
                     <?php else: ?>
                     <?php foreach ($departments as $dept): ?>
                     <div class="dept-card">
                         <div class="dept-icon" style="background: linear-gradient(135deg, <?php echo htmlspecialchars($dept['gradient_from'] ?? '#B8860B'); ?>, <?php echo htmlspecialchars($dept['gradient_to'] ?? '#D4A843'); ?>);"><i class="<?php echo htmlspecialchars($dept['icon_class'] ?? 'fas fa-building'); ?>"></i></div>
                         <div class="dept-info">
                             <div class="dept-name"><?php echo htmlspecialchars($dept['department_name']); ?></div>
                             <div class="dept-meta">
                                 <?php 
                                 $instructor_count = $dept['instructor_count'] ?? 0;
                                 $course_count = $dept['course_count'] ?? 0;
                                 echo $instructor_count . ' Instructor' . ($instructor_count != 1 ? 's' : '') . ' | ' . $course_count . ' Course' . ($course_count != 1 ? 's' : '');
                                 ?>
                             </div>
                         </div>
                     </div>
                     <?php endforeach; ?>
                     <?php endif; ?>
                 </div>
            </div>
        </main>
    </div>
    <script src="../../../function/dashboard.js"></script>
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
                <a href="../../../Door/login.php" style="background: linear-gradient(135deg, #d4a843, #b8922f); color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 500;">
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

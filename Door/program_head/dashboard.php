<?php
require_once '../../data/session_security.php';
check_auth('program_head', '../login.php');
require_once '../../data/config.php';

$user_name = $_SESSION['user_name'] ?? 'Program Head';

// Fetch stats with error handling
$total_instructors = 0;
$completed_evaluations = 0;
$active_courses = 0;
$avg_rating = 0;

// Get total instructors (active)
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM instructors");
    $result = $stmt->fetch();
    $total_instructors = $result['cnt'] ?? 0;
} catch (PDOException $e) {
    $total_instructors = 0;
}

// Get evaluation stats - check if table exists first
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'evaluations'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM evaluations WHERE status = 'completed'");
        $result = $stmt->fetch();
        $completed_evaluations = $result['cnt'] ?? 0;
        
        $stmt = $pdo->query("SELECT COALESCE(AVG(rating),0) as avg_r FROM evaluations");
        $result = $stmt->fetch();
        $avg_rating = round($result['avg_r'], 1);
    }
} catch (PDOException $e) {
    $completed_evaluations = 0;
    $avg_rating = 0;
}

// Get active courses - check if table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'courses'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM courses WHERE status = 'active'");
        $result = $stmt->fetch();
        $active_courses = $result['cnt'] ?? 0;
    } else {
        // Alternative: count majors as courses if courses table doesn't exist
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM majors WHERE is_active = 1");
        $result = $stmt->fetch();
        $active_courses = $result['cnt'] ?? 0;
    }
} catch (PDOException $e) {
    $active_courses = 0;
}

// Get total students enrolled
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM students");
    $result = $stmt->fetch();
    $total_students = $result['cnt'] ?? 0;
} catch (PDOException $e) {
    $total_students = 0;
}

// Get instructor status counts
$on_duty = 0;
$on_leave = 0;
$on_travel = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM instructors WHERE status = 'on duty'");
    $result = $stmt->fetch();
    $on_duty = $result['cnt'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM instructors WHERE status = 'on leave'");
    $result = $stmt->fetch();
    $on_leave = $result['cnt'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM instructors WHERE status = 'on travel'");
    $result = $stmt->fetch();
    $on_travel = $result['cnt'] ?? 0;
} catch (PDOException $e) {
    $on_duty = 0;
    $on_leave = 0;
    $on_travel = 0;
}

// Fetch recent evaluations only if table exists
$recent_evaluations = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'evaluations'");
    if ($stmt->rowCount() > 0) {
        $sql = "SELECT 
            CONCAT(i.first_name, ' ', i.last_name) as instructor_name, 
            c.course_name, 
            e.rating, 
            e.evaluation_date 
            FROM evaluations e 
            JOIN instructors i ON e.instructor_id = i.id 
            JOIN courses c ON e.course_id = c.id 
            ORDER BY e.evaluation_date DESC 
            LIMIT 3";
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $recent_evaluations[] = $row;
        }
    }
} catch (PDOException $e) {
    $recent_evaluations = [];
}

// Fetch department performance only if evaluations table exists
$dept_performance = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'evaluations'");
    if ($stmt->rowCount() > 0) {
        $sql = "SELECT 
            COALESCE(e.department, 'General') as department, 
            COALESCE(AVG(e.rating),0) as avg_rating 
            FROM evaluations e 
            GROUP BY e.department 
            ORDER BY avg_rating DESC";
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dept_performance[] = $row;
        }
    }
} catch (PDOException $e) {
    $dept_performance = [];
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
    <link rel="icon" href="../../media/LOGO.jpg" type="image/jpeg">
    <title>Program Head Dashboard - Faculty Evaluation System</title>
    <link rel="stylesheet" href="../../css/common.css">
    <link rel="stylesheet" href="./style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body class="dashboard-page">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../../media/LOGO.jpg" alt="Logo" class="sidebar-logo" style="width: 70px; height: 70px; border-radius: 16px; object-fit: cover; border: 3px solid white; background: white; padding: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
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
                <span class="sidebar-user-role">Program Head</span>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="sidebar-nav-label">Menu</div>
            <a href="dashboard.php" class="sidebar-nav-item active">
                <i class="fas fa-chart-pie"></i>
                <span>Overview</span>
            </a>
             <a href="pages/instructors.php" class="sidebar-nav-item">
                 <i class="fas fa-chalkboard-teacher"></i>
                 <span>Instructors</span>
             </a>
              <a href="pages/student_enrollment.php" class="sidebar-nav-item">
                  <i class="fas fa-user-graduate"></i>
                  <span>Enrollment</span>
              </a>
               <a href="pages/mentee_flow.php" class="sidebar-nav-item">
                   <i class="fas fa-users"></i>
                   <span>MenteeFlow</span>
               </a>
              <a href="pages/departments.php" class="sidebar-nav-item">
                  <i class="fas fa-building"></i>
                  <span>Departments</span>
              </a>
            <a href="pages/reports.php" class="sidebar-nav-item">
                <i class="fas fa-file-alt"></i>
                <span>Reports</span>
            </a>
            <a href="pages/settings.php" class="sidebar-nav-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content" style="position: relative;">
        <!-- Background Logo -->
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <div class="topbar-title">Dashboard</div>
                    <div class="topbar-subtitle">Program Head Panel</div>
                </div>
            </div>
            
            <div class="topbar-right">
                <div class="topbar-date">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('F j, Y'); ?></span>
                </div>
                <a href="../../data/logout.php" class="topbar-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="dashboard-content" style="position: relative; z-index: 1;">
            <!-- Welcome Banner -->
            <div class="welcome-banner" style="text-align: center; padding: 32px 40px;">
                <div style="display: inline-block; background: rgba(255, 255, 255, 0.2); color: white; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; border: 1px solid rgba(255,255,255,0.3);">Program Head</div>
                <h1 style="font-size: 52px; font-weight: 800; color: white; margin-bottom: 8px; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p style="font-size: 14px; color: rgba(255,255,255,0.95); max-width: 600px; margin: 0 auto 20px;">Monitor instructor performance, manage evaluations, and track department progress all in one place.</p>
                <div style="display: flex; gap: 16px; flex-wrap: wrap; justify-content: center;">
                    <div style="background: linear-gradient(135deg, #fff 0%, #fef9e7 100%); border-radius: 16px; padding: 16px 24px; min-width: 140px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid rgba(212,168,67,0.2); text-align: center; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                        <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #D4A843, #FFD700); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                            <i class="fas fa-chalkboard-teacher" style="color: white; font-size: 18px;"></i>
                        </div>
                        <div style="font-size: 26px; font-weight: 800; color: #2D2D2D; line-height: 1;"><?php echo $total_instructors; ?></div>
                        <div style="font-size: 11px; color: #888; font-weight: 600; margin-top: 4px;">Total Instructors</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #fff 0%, #e8f8f5 100%); border-radius: 16px; padding: 16px 24px; min-width: 140px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid rgba(16,185,129,0.2); text-align: center; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                        <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #10b981, #34d399); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                            <i class="fas fa-check-circle" style="color: white; font-size: 18px;"></i>
                        </div>
                        <div style="font-size: 26px; font-weight: 800; color: #2D2D2D; line-height: 1;"><?php echo $completed_evaluations; ?></div>
                        <div style="font-size: 11px; color: #888; font-weight: 600; margin-top: 4px;">Completed Evaluations</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #fff 0%, #e7f3ff 100%); border-radius: 16px; padding: 16px 24px; min-width: 140px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid rgba(59,130,246,0.2); text-align: center; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                        <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #3b82f6, #60a5fa); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                            <i class="fas fa-user-graduate" style="color: white; font-size: 18px;"></i>
                        </div>
                        <div style="font-size: 26px; font-weight: 800; color: #2D2D2D; line-height: 1;"><?php echo $total_students; ?></div>
                        <div style="font-size: 11px; color: #888; font-weight: 600; margin-top: 4px;">Total Students Enrolled</div>
                    </div>
                </div>
            </div>
            
            <!-- Time Display (Outside) -->
            <div style="display: flex; justify-content: center; margin-top: -20px; margin-bottom: 24px;">
                <div style="display: inline-flex; align-items: center; gap: 16px; background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8a 100%); border-radius: 16px; padding: 16px 28px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); border: 1px solid rgba(30,58,95,0.3); color: white;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-clock" style="color: #FFD700; font-size: 18px;"></i>
                        <span id="time-greeting" style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #FFD700;">Morning</span>
                    </div>
                    <div style="width: 1px; height: 30px; background: rgba(255,255,255,0.3);"></div>
                    <div id="current-time" style="font-size: 28px; font-weight: 800; line-height: 1; color: white;">--:--:--</div>
                    <div style="width: 1px; height: 30px; background: rgba(255,255,255,0.3);"></div>
                    <div id="current-date" style="font-size: 12px; color: rgba(255,255,255,0.8); font-weight: 600;">Loading...</div>
                </div>
            </div>
            <script>
                function updateTime() {
                    const now = new Date();
                    const options = { timeZone: 'Asia/Manila', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
                    const timeString = now.toLocaleTimeString('en-US', options);
                    const hour = parseInt(now.toLocaleTimeString('en-US', { timeZone: 'Asia/Manila', hour: '2-digit', hour12: false }));
                    const greetingEl = document.getElementById('time-greeting');
                    if (hour >= 5 && hour < 12) {
                        greetingEl.textContent = 'Morning';
                    } else if (hour >= 12 && hour < 17) {
                        greetingEl.textContent = 'Afternoon';
                    } else if (hour >= 17 && hour < 21) {
                        greetingEl.textContent = 'Evening';
                    } else {
                        greetingEl.textContent = 'Night';
                    }
                    document.getElementById('current-time').textContent = timeString;
                    const dateOptions = { timeZone: 'Asia/Manila', weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
                    document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', dateOptions);
                }
                updateTime();
                setInterval(updateTime, 1000);
            </script>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Department Overview Card -->
                <div class="content-card" style="background: linear-gradient(160deg, #B8860B 0%, #D4A843 50%, #F0D68A 100%);">
                    <div class="content-card-header" style="background: rgba(255,255,255,0.1); border-bottom: 1px solid rgba(255,255,255,0.2);">
                        <h3 style="color: white;"><i class="fas fa-building" style="color: white;"></i> Department Overview</h3>
                        <a href="pages/departments.php" class="view-all" style="color: white; background: rgba(255,255,255,0.2);">View All</a>
                    </div>
                    <div class="content-card-body" style="background: rgba(255,255,255,0.95); border-radius: 0 0 20px 20px;">
                        <?php
                        // Fetch majors for display
                        $majors = [];
                        try {
                            $stmt = $pdo->query("SELECT id, major_name, description, is_active FROM majors ORDER BY major_name");
                            $majors = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            $majors = [];
                        }
                        ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php if (empty($majors)): ?>
                                <div style="text-align: center; padding: 20px; color: #888;">
                                    <i class="fas fa-folder-open" style="font-size: 32px; margin-bottom: 8px; color: #ccc;"></i>
                                    <p>No departments found</p>
                                </div>
                            <?php else: ?>
                                <!-- Majors Card -->
                                <div style="background: linear-gradient(135deg, #f8f7f4 0%, #f0ede6 100%); border-radius: 12px; padding: 16px; border: 1px solid #E8E4D9;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div style="flex: 1;">
                                            <h4 style="font-size: 16px; font-weight: 800; color: #2D2D2D; margin-bottom: 4px;">Majors</h4>
                                            <p style="font-size: 12px; color: #888;">Manage and organize department majors</p>
                                            <div style="font-size: 20px; font-weight: 800; color: #B8860B; margin-top: 8px;"><?php echo count($majors); ?> Majors</div>
                                        </div>
                                        <a href="pages/departments.php" style="padding: 10px 20px; background: linear-gradient(135deg, #D4A843, #FFD700); color: white; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; box-shadow: 0 2px 8px rgba(212,168,67,0.3);">
                                            <i class="fas fa-eye"></i> Quick View
                                        </a>
                                    </div>
                                </div>

                                <!-- Prerequisites Card -->
                                <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 12px; padding: 16px; border: 1px solid #93c5fd;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div style="flex: 1;">
                                            <h4 style="font-size: 16px; font-weight: 800; color: #1e40af; margin-bottom: 4px;">Prerequisites</h4>
                                            <p style="font-size: 12px; color: #64748b;">Create and manage prerequisite sets for subjects</p>
                                        </div>
                                        <a href="pages/departments.php?tab=prerequisites" style="padding: 10px 20px; background: linear-gradient(135deg, #3b82f6, #60a5fa); color: white; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; box-shadow: 0 2px 8px rgba(59,130,246,0.3);">
                                            <i class="fas fa-eye"></i> Quick View
                                        </a>
                                    </div>
                                </div>

                                <!-- Prospectus Card -->
                                <div style="background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); border-radius: 12px; padding: 16px; border: 1px solid #c4b5fd;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div style="flex: 1;">
                                            <h4 style="font-size: 16px; font-weight: 800; color: #6d28d9; margin-bottom: 4px;">Prospectus</h4>
                                            <p style="font-size: 12px; color: #64748b;">Manage course prospectus and curriculum</p>
                                        </div>
                                        <a href="pages/departments.php?tab=subjects" style="padding: 10px 20px; background: linear-gradient(135deg, #8b5cf6, #a78bfa); color: white; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; box-shadow: 0 2px 8px rgba(139,92,246,0.3);">
                                            <i class="fas fa-eye"></i> Quick View
                                        </a>
                                    </div>
                                </div>

                                <!-- Students by Year Card -->
                                <?php
                                // Get students count by year level
                                $yearLevels = [];
                                try {
                                    $stmt = $pdo->query("SELECT year_level, COUNT(*) as count FROM students WHERE year_level IS NOT NULL AND year_level != '' GROUP BY year_level ORDER BY year_level");
                                    $yearLevels = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch (PDOException $e) {
                                    $yearLevels = [];
                                }
                                ?>
                                <div style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-radius: 12px; padding: 16px; border: 1px solid #6ee7b7;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                        <div style="flex: 1;">
                                            <h4 style="font-size: 16px; font-weight: 800; color: #065f46; margin-bottom: 4px;">Students by Year</h4>
                                            <p style="font-size: 12px; color: #64748b;">Total students per academic year</p>
                                        </div>
                                    </div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                        <?php if (empty($yearLevels)): ?>
                                            <div style="font-size: 12px; color: #888;">No student data available</div>
                                        <?php else: ?>
                                            <?php foreach ($yearLevels as $year): ?>
                                            <div style="background: white; border-radius: 8px; padding: 10px 14px; text-align: center; min-width: 80px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                                <div style="font-size: 20px; font-weight: 800; color: #059669;"><?php echo intval($year['count']); ?></div>
                                                <div style="font-size: 10px; color: #64748b; font-weight: 600;"><?php echo htmlspecialchars($year['year_level']); ?></div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Department Performance Card -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h3><i class="fas fa-calendar-alt"></i> Calendar</h3>
                    </div>
                    <div class="content-card-body">
                        <div style="text-align: center; padding: 10px;">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 16px; margin-bottom: 16px;">
                                <button onclick="changeMonth(-1)" style="background: linear-gradient(135deg, #D4A843, #FFD700); border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; color: white; font-size: 14px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <div id="calendar-title" style="font-size: 18px; font-weight: 700; color: #2D2D2D; min-width: 160px;"></div>
                                <button onclick="changeMonth(1)" style="background: linear-gradient(135deg, #D4A843, #FFD700); border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; color: white; font-size: 14px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; margin-bottom: 8px;">
                                <?php $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; foreach($days as $d): ?>
                                <div style="font-size: 11px; font-weight: 700; color: #888; padding: 6px; text-transform: uppercase;"><?php echo $d; ?></div>
                                <?php endforeach; ?>
                            </div>
                            <div id="calendar-days" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px;"></div>
                        </div>
                        <script>
                            let currentDate = new Date();
                            let currentMonth = currentDate.getMonth();
                            let currentYear = currentDate.getFullYear();
                            
                            function renderCalendar() {
                                const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                document.getElementById('calendar-title').textContent = monthNames[currentMonth] + ' ' + currentYear;
                                
                                const firstDay = new Date(currentYear, currentMonth, 1);
                                const lastDay = new Date(currentYear, currentMonth + 1, 0);
                                const dayOfWeek = firstDay.getDay();
                                const daysInMonth = lastDay.getDate();
                                
                                const today = new Date();
                                let html = '';
                                
                                for (let i = 0; i < dayOfWeek; i++) {
                                    html += '<div style="padding: 8px; font-size: 14px; color: #ccc;">&nbsp;</div>';
                                }
                                
                                for (let day = 1; day <= daysInMonth; day++) {
                                    const isToday = (day === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear());
                                    const bg = isToday ? 'rgba(212,168,67,0.15)' : 'transparent';
                                    const color = isToday ? '#D4A843' : '#2D2D2D';
                                    const border = isToday ? 'border: 2px solid #D4A843;' : '';
                                    html += '<div style="padding: 8px; font-size: 14px; font-weight: 600; color: ' + color + '; background: ' + bg + '; border-radius: 8px; ' + border + '">' + day + '</div>';
                                }
                                
                                document.getElementById('calendar-days').innerHTML = html;
                            }
                            
                            function changeMonth(delta) {
                                currentMonth += delta;
                                if (currentMonth > 11) {
                                    currentMonth = 0;
                                    currentYear++;
                                } else if (currentMonth < 0) {
                                    currentMonth = 11;
                                    currentYear--;
                                }
                                renderCalendar();
                            }
                            
                            renderCalendar();
                        </script>
                        <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #E8E4D9;">
                            <div style="font-size: 14px; font-weight: 700; color: #2D2D2D; text-align: center; margin-bottom: 12px;">Instructor Status</div>
                            <div style="display: flex; gap: 12px; justify-content: center;">
                            <div style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border-radius: 12px; padding: 12px 20px; text-align: center; border: 1px solid rgba(16,185,129,0.2); flex: 1;">
                                <i class="fas fa-briefcase" style="color: #10b981; font-size: 16px; margin-bottom: 4px;"></i>
                                <div style="font-size: 20px; font-weight: 800; color: #2D2D2D;"><?php echo $on_duty; ?></div>
                                <div style="font-size: 10px; color: #888; font-weight: 600;">On Duty</div>
                            </div>
                            <div style="background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%); border-radius: 12px; padding: 12px 20px; text-align: center; border: 1px solid rgba(245,158,11,0.2); flex: 1;">
                                <i class="fas fa-bed" style="color: #f59e0b; font-size: 16px; margin-bottom: 4px;"></i>
                                <div style="font-size: 20px; font-weight: 800; color: #2D2D2D;"><?php echo $on_leave; ?></div>
                                <div style="font-size: 10px; color: #888; font-weight: 600;">On Leave</div>
                            </div>
                            <div style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-radius: 12px; padding: 12px 20px; text-align: center; border: 1px solid rgba(59,130,246,0.2); flex: 1;">
                                <i class="fas fa-plane" style="color: #3b82f6; font-size: 16px; margin-bottom: 4px;"></i>
                                <div style="font-size: 20px; font-weight: 800; color: #2D2D2D;"><?php echo $on_travel; ?></div>
                                <div style="font-size: 10px; color: #888; font-weight: 600;">On Travel</div>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../function/dashboard.js"></script>
    <script src="../../function/session_guard.js"></script>
</body>
</html>
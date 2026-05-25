<?php
require_once '../../data/session_security.php';
check_auth('instructor', '../login.php');
require_once '../../data/config.php';

$instructor_id = $_SESSION['user_id'] ?? 1;
$user_name = $_SESSION['user_name'] ?? 'Jane Teacher';

// ─── AJAX: Instructor Availability ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'save_availability') {
        $dates  = json_decode($_POST['dates']  ?? '[]', true);
        $status = $_POST['status'] ?? 'on_leave'; // on_leave | on_travel
        $note   = trim($_POST['note'] ?? '');

        if (!in_array($status, ['on_leave','on_travel'])) {
            echo json_encode(['success'=>false,'message'=>'Invalid status.']);
            exit;
        }
        if (empty($dates)) {
            echo json_encode(['success'=>false,'message'=>'No dates selected.']);
            exit;
        }

        try {
            // Ensure table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS instructor_availability (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                instructor_id INT NOT NULL,
                date          DATE NOT NULL,
                status        ENUM('on_leave','on_travel','on_duty') NOT NULL DEFAULT 'on_duty',
                note          VARCHAR(255) DEFAULT NULL,
                created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_instr_date (instructor_id, date)
            )");

            $stmt = $pdo->prepare(
                "INSERT INTO instructor_availability (instructor_id, date, status, note)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status=VALUES(status), note=VALUES(note), updated_at=NOW()"
            );
            foreach ($dates as $d) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                    $stmt->execute([$instructor_id, $d, $status, $note ?: null]);
                }
            }
            echo json_encode(['success'=>true,'message'=>count($dates).' date(s) saved.']);
        } catch (PDOException $e) {
            echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
        }
        exit;
    }

    if ($_POST['action'] === 'remove_availability') {
        $dates = json_decode($_POST['dates'] ?? '[]', true);
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS instructor_availability (
                id INT AUTO_INCREMENT PRIMARY KEY,
                instructor_id INT NOT NULL,
                date DATE NOT NULL,
                status ENUM('on_leave','on_travel','on_duty') NOT NULL DEFAULT 'on_duty',
                note VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_instr_date (instructor_id, date)
            )");
            $stmt = $pdo->prepare(
                "DELETE FROM instructor_availability WHERE instructor_id=? AND date=?"
            );
            foreach ($dates as $d) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                    $stmt->execute([$instructor_id, $d]);
                }
            }
            echo json_encode(['success'=>true]);
        } catch (PDOException $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action.']);
    exit;
}

// ─── Load instructor availability ────────────────────────────────────────────
$availability_map = []; // [date => ['status'=>..., 'note'=>...]]
$today_status = 'on_duty';
if ($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS instructor_availability (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            instructor_id INT NOT NULL,
            date          DATE NOT NULL,
            status        ENUM('on_leave','on_travel','on_duty') NOT NULL DEFAULT 'on_duty',
            note          VARCHAR(255) DEFAULT NULL,
            created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_instr_date (instructor_id, date)
        )");
        $stmt = $pdo->prepare(
            "SELECT date, status, note FROM instructor_availability
             WHERE instructor_id = ?
             ORDER BY date ASC"
        );
        $stmt->execute([$instructor_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $availability_map[$row['date']] = [
                'status' => $row['status'],
                'note'   => $row['note'],
            ];
        }
        $todayKey = date('Y-m-d');
        $today_status = isset($availability_map[$todayKey])
            ? $availability_map[$todayKey]['status']
            : 'on_duty';
    } catch (PDOException $e) { $availability_map = []; }
}
$availabilityJson = json_encode($availability_map);

// ─── Initialize Variables ────────────────────────────────────────────────────
$course_count        = 0;
$student_count       = 0;
$avg_rating          = 0;
$new_feedback        = 0;
$pending_evaluations = 0;
$new_mentees         = 0;
$reports_generated   = 0;
$active_tasks        = 0;
$recent_evaluations  = [];
$recent_feedback     = [];
$recent_activities   = [];
$graduated_mentees   = [];
$graduated_mentee_count = 0;
$current_school_year = '2025-2026';
$eval_percentage     = 0;
$evaluated_students  = 0;
$total_eval_students = 0;
$top_mentees_gwa     = [];

// ─── Calendar Events ─────────────────────────────────────────────────────────
$calendar_events_map    = []; // [date => [events]]
$upcoming_events_list   = []; // events in current month from today onward

if ($pdo) {
    // ── Course count ──────────────────────────────────────────────────────────
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM instructor_courses WHERE instructor_id = ?");
        $stmt->execute([$instructor_id]);
        $course_count = $stmt->fetch()['cnt'] ?? 0;
    } catch (PDOException $e) { $course_count = 0; }

    // ── Student count ─────────────────────────────────────────────────────────
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT s.id) as cnt
             FROM students s
             JOIN courses c ON s.course_code = c.course_code
             JOIN instructor_courses ic ON c.id = ic.course_id
             WHERE ic.instructor_id = ?"
        );
        $stmt->execute([$instructor_id]);
        $student_count = $stmt->fetch()['cnt'] ?? 0;
    } catch (PDOException $e) {
        // Fallback: count all mentees for this instructor
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM mentees WHERE mentor_id = ?");
            $stmt->execute([$instructor_id]);
            $student_count = $stmt->fetch()['cnt'] ?? 0;
        } catch (PDOException $e2) { $student_count = 0; }
    }

    // ── Average rating ────────────────────────────────────────────────────────
    try {
        $stmt = $pdo->prepare("SELECT COALESCE(AVG(rating),0) as avg_r FROM evaluations WHERE instructor_id = ?");
        $stmt->execute([$instructor_id]);
        $avg_rating = round($stmt->fetch()['avg_r'], 1);
    } catch (PDOException $e) { $avg_rating = 0; }

    // ── Pending evaluations ───────────────────────────────────────────────────
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as cnt FROM evaluation_sessions
             WHERE instructor_id = ? AND session_status != 'finalized'"
        );
        $stmt->execute([$instructor_id]);
        $pending_evaluations = $stmt->fetch()['cnt'] ?? 0;
    } catch (PDOException $e) { $pending_evaluations = 0; }

    // ── New mentees (last 24 h) ───────────────────────────────────────────────
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as cnt FROM mentees
             WHERE mentor_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)"
        );
        $stmt->execute([$instructor_id]);
        $new_mentees = $stmt->fetch()['cnt'] ?? 0;
    } catch (PDOException $e) { $new_mentees = 0; }

    // ── Reports count ─────────────────────────────────────────────────────────
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM reports");
        $reports_generated = $stmt->fetch()['cnt'] ?? 0;
    } catch (PDOException $e) { $reports_generated = 0; }

    // ── Active tasks ──────────────────────────────────────────────────────────
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as cnt FROM tasks WHERE instructor_id = ? AND status != 'completed'"
        );
        $stmt->execute([$instructor_id]);
        $active_tasks = $stmt->fetch()['cnt'] ?? 0;
    } catch (PDOException $e) { $active_tasks = 0; }

    // ── Top mentees GWA ───────────────────────────────────────────────────────
    try {
        $stmt = $pdo->prepare(
            "SELECT s.id, s.first_name, s.last_name, s.student_id AS student_number,
                    es.gwa, es.semester, es.year_level
             FROM students s
             JOIN mentees m ON s.id = m.student_id
             JOIN evaluation_sessions es ON s.id = es.student_id
             WHERE m.mentor_id = ? AND es.academic_year = ?
               AND es.gwa IS NOT NULL
             ORDER BY es.gwa ASC
             LIMIT 10"
        );
        $stmt->execute([$instructor_id, $current_school_year]);
        while ($row = $stmt->fetch()) {
            $top_mentees_gwa[] = [
                'id'             => $row['id'],
                'name'           => $row['first_name'] . ' ' . $row['last_name'],
                'student_number' => $row['student_number'],
                'gwa'            => $row['gwa'],
                'semester'       => $row['semester'],
                'year_level'     => $row['year_level'],
            ];
        }
    } catch (PDOException $e) { $top_mentees_gwa = []; }

    // ── Graduated mentees ─────────────────────────────────────────────────────
    try {
        $stmt = $pdo->prepare(
            "SELECT s.id, s.first_name, s.last_name, s.student_id AS student_number,
                    gr.gwa, gr.graduation_date
             FROM students s
             JOIN mentees m ON s.id = m.student_id
             JOIN graduation_records gr ON s.id = gr.student_id
             WHERE m.mentor_id = ?
             ORDER BY gr.graduation_date DESC
             LIMIT 5"
        );
        $stmt->execute([$instructor_id]);
        while ($row = $stmt->fetch()) {
            $graduated_mentees[] = [
                'id'              => $row['id'],
                'name'            => $row['first_name'] . ' ' . $row['last_name'],
                'student_number'  => $row['student_number'],
                'gwa'             => $row['gwa'],
                'graduation_date' => $row['graduation_date'],
            ];
        }
        $graduated_mentee_count = count($graduated_mentees);
    } catch (PDOException $e) { $graduated_mentees = []; $graduated_mentee_count = 0; }

    // ── Recent activities ─────────────────────────────────────────────────────
    try {
        $activities = [];

        // Recent tasks
        $stmt = $pdo->prepare(
            "SELECT 'task' as type, title, created_at as date, 'Task assigned' as description
             FROM tasks WHERE instructor_id = ?
             ORDER BY created_at DESC LIMIT 3"
        );
        $stmt->execute([$instructor_id]);
        while ($row = $stmt->fetch()) { $activities[] = $row; }

        // Recent mentees
        $stmt = $pdo->prepare(
            "SELECT 'mentee' as type, CONCAT(s.first_name,' ',s.last_name) as title,
                    m.created_at as date, 'New mentee assigned' as description
             FROM mentees m JOIN students s ON m.student_id = s.id
             WHERE m.mentor_id = ?
             ORDER BY m.created_at DESC LIMIT 3"
        );
        $stmt->execute([$instructor_id]);
        while ($row = $stmt->fetch()) { $activities[] = $row; }

        usort($activities, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
        $recent_activities = array_slice($activities, 0, 6);
    } catch (PDOException $e) { $recent_activities = []; }

    // ── CALENDAR EVENTS ───────────────────────────────────────────────────────
    // Fetch all calendar_events with their event_dates
    try {
        $stmt = $pdo->query(
            "SELECT ce.id, ce.title, ce.description, ce.event_date AS primary_date,
                    ce.created_by, ce.created_at,
                    ed.event_date AS occurrence_date
             FROM calendar_events ce
             JOIN event_dates ed ON ed.event_id = ce.id
             ORDER BY ed.event_date ASC"
        );
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $d = $row['occurrence_date'];
            if (!isset($calendar_events_map[$d])) $calendar_events_map[$d] = [];
            $calendar_events_map[$d][] = [
                'id'          => $row['id'],
                'title'       => $row['title'],
                'description' => $row['description'],
            ];
        }
    } catch (PDOException $e) {
        // Fallback: try calendar_events.event_date only
        try {
            $stmt = $pdo->query(
                "SELECT id, title, description, event_date FROM calendar_events ORDER BY event_date ASC"
            );
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $d = $row['event_date'];
                if (!isset($calendar_events_map[$d])) $calendar_events_map[$d] = [];
                $calendar_events_map[$d][] = [
                    'id'          => $row['id'],
                    'title'       => $row['title'],
                    'description' => $row['description'],
                ];
            }
        } catch (PDOException $e2) {}
    }

    // ── Upcoming events this month ─────────────────────────────────────────────
    try {
        $today       = date('Y-m-d');
        $monthStart  = date('Y-m-01');
        $monthEnd    = date('Y-m-t');

        $stmt = $pdo->prepare(
            "SELECT ce.id, ce.title, ce.description, ed.event_date
             FROM calendar_events ce
             JOIN event_dates ed ON ed.event_id = ce.id
             WHERE ed.event_date >= ? AND ed.event_date <= ?
             ORDER BY ed.event_date ASC"
        );
        $stmt->execute([$today, $monthEnd]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $upcoming_events_list[] = $row;
        }
    } catch (PDOException $e) {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, title, description, event_date
                 FROM calendar_events
                 WHERE event_date >= ? AND event_date <= ?
                 ORDER BY event_date ASC"
            );
            $stmt->execute([date('Y-m-d'), date('Y-m-t')]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $upcoming_events_list[] = $row;
            }
        } catch (PDOException $e2) {}
    }

    // ── Eval progress ──────────────────────────────────────────────────────────
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT es.student_id) as evaluated_count,
                    (SELECT COUNT(*) FROM mentees WHERE mentor_id = ?) as total_students
             FROM evaluation_sessions es
             JOIN mentees m ON es.student_id = m.student_id
             WHERE m.mentor_id = ? AND es.academic_year = ?"
        );
        $stmt->execute([$instructor_id, $instructor_id, $current_school_year]);
        $result = $stmt->fetch();
        $evaluated_students  = $result['evaluated_count'] ?? 0;
        $total_eval_students = $result['total_students']  ?? 0;
        $eval_percentage     = $total_eval_students > 0
            ? round(($evaluated_students / $total_eval_students) * 100) : 0;
    } catch (PDOException $e) { $evaluated_students = 0; $eval_percentage = 0; }
}

// ─── PHP → JS bridge for calendar ────────────────────────────────────────────
$calendarEventsJson = json_encode($calendar_events_map);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../media/LOGO.jpg" type="image/jpeg">
    <title>Instructor Dashboard – Student Evaluation System</title>
    <link rel="stylesheet" href="../../css/common.css">
    <link rel="stylesheet" href="style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
/* ════════════════════════════════════════════
   RESET & BASE
════════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ════════════════════════════════════════════
   STAT CARDS
════════════════════════════════════════════ */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 22px 20px;
    border: 1px solid #e5e7eb;
    transition: transform .25s, box-shadow .25s;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,.1); }
.stat-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; }
.stat-card-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; color: #fff;
}
.stat-card-icon.gold   { background: linear-gradient(135deg,#d4a843,#b8922f); }
.stat-card-icon.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.stat-card-icon.purple { background: linear-gradient(135deg,#8b5cf6,#7c3aed); }
.stat-card-icon.green  { background: linear-gradient(135deg,#059669,#047857); }
.stat-card-icon.teal   { background: linear-gradient(135deg,#0d9488,#0f766e); }
.stat-card-icon.indigo { background: linear-gradient(135deg,#6366f1,#4f46e5); }
.stat-card-icon.orange { background: linear-gradient(135deg,#d97706,#b45309); }

.stat-card-value { font-size: 32px; font-weight: 700; color: #1f2937; line-height: 1; }
.stat-card-label { font-size: 13px; color: #6b7280; font-weight: 500; margin-top: 8px; }
.stat-change { display: flex; align-items: center; gap: 4px; font-size: 12px; margin-top: 10px; }
.stat-change.positive { color: #059669; }
.stat-progress { margin-top: 12px; height: 4px; background: #e5e7eb; border-radius: 2px; overflow: hidden; }
.stat-progress-bar { height: 100%; background: linear-gradient(90deg,#d4a843,#b8922f); border-radius: 2px; }

/* ════════════════════════════════════════════
   WELCOME BANNER
════════════════════════════════════════════ */
.welcome-banner {
    background: linear-gradient(135deg,#d4a843 0%,#b8922f 50%,#a38023 100%);
    border-radius: 20px; padding: 32px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 32px; position: relative; overflow: hidden; flex-wrap: wrap;
    margin-bottom: 24px;
}
.welcome-banner::before {
    content:''; position:absolute; top:-50%; right:-10%;
    width:300px; height:300px; background:rgba(255,255,255,.1); border-radius:50%;
}
.welcome-banner::after {
    content:''; position:absolute; bottom:-30%; left:-5%;
    width:200px; height:200px; background:rgba(255,255,255,.08); border-radius:50%;
}
.welcome-banner-content { flex:1; z-index:1; min-width:300px; }
.welcome-banner-role {
    display:inline-flex; align-items:center; gap:6px;
    background:rgba(255,255,255,.2); color:#fff;
    padding:6px 14px; border-radius:20px;
    font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.5px;
    margin-bottom:12px;
}
.welcome-banner h1 { color:#fff; font-size:34px; font-weight:800; margin-bottom:8px; text-shadow:0 2px 20px rgba(0,0,0,.3); }
.welcome-banner p  { color:rgba(255,255,255,.9); font-size:15px; line-height:1.5; }
.welcome-banner-actions { display:flex; gap:12px; flex-wrap:wrap; z-index:1; }
.banner-action-btn {
    display:inline-flex; align-items:center; gap:8px;
    padding:13px 18px; border-radius:12px; text-decoration:none;
    font-weight:600; font-size:14px; transition:all .3s;
    border:2px solid transparent; box-shadow:0 4px 12px rgba(0,0,0,.2);
}
.banner-action-btn.primary  { background:rgba(255,255,255,.15); color:#fff; border-color:rgba(255,255,255,.3); }
.banner-action-btn.secondary{ background:rgba(255,255,255,.9); color:#1f2937; }
.banner-action-btn.tertiary { background:rgba(255,255,255,.1);  color:#fff; border-color:rgba(255,255,255,.2); }
.banner-action-btn:hover    { transform:translateY(-2px); box-shadow:0 8px 20px rgba(0,0,0,.25); }

/* ════════════════════════════════════════════
   QUICK ACTIONS
════════════════════════════════════════════ */
.quick-actions {
    background:#fff; border-radius:12px; padding:20px 24px;
    border:1px solid #e5e7eb; margin-bottom:24px;
    display:flex; align-items:center; justify-content:space-between;
    gap:18px; flex-wrap:wrap;
}
.quick-actions h3 { font-size:15px; font-weight:700; color:#1f2937; display:flex; align-items:center; gap:8px; flex-shrink:0; }
.action-buttons { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.action-btn {
    display:inline-flex; align-items:center; gap:10px;
    padding:12px 18px; border-radius:10px; text-decoration:none;
    font-weight:600; font-size:13px; transition:all .3s; white-space:nowrap;
}
.action-btn.primary  { background:linear-gradient(135deg,#d4a843,#b8922f); color:#fff; }
.action-btn.secondary{ background:linear-gradient(135deg,#3b82f6,#2563eb); color:#fff; }
.action-btn.tertiary { background:linear-gradient(135deg,#059669,#047857); color:#fff; }
.action-btn.quaternary{ background:linear-gradient(135deg,#7c3aed,#6d28d9); color:#fff; }
.action-btn:hover    { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.15); }

/* ════════════════════════════════════════════
   ACTIVITY & NOTIFICATIONS
════════════════════════════════════════════ */
.activity-notifications {
    display:grid; grid-template-columns:2fr 1fr; gap:24px; margin-bottom:24px;
}
@media(max-width:1100px){ .activity-notifications{ grid-template-columns:1fr; } }
.activity-card, .notifications-card {
    background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden;
}
.activity-header, .notifications-header {
    padding:18px 22px; border-bottom:1px solid #e5e7eb;
    display:flex; justify-content:space-between; align-items:center;
}
.activity-header h3, .notifications-header h3 {
    font-size:15px; font-weight:700; color:#1f2937; display:flex; align-items:center; gap:8px;
}
.activity-list, .notifications-list { max-height:320px; overflow-y:auto; }
.activity-item, .notification-item {
    display:flex; align-items:flex-start; gap:14px;
    padding:14px 22px; border-bottom:1px solid #f3f4f6; transition:background .2s;
}
.activity-item:hover, .notification-item:hover { background:#f9fafb; }
.activity-item:last-child, .notification-item:last-child { border-bottom:none; }
.activity-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.activity-icon.evaluation { background:linear-gradient(135deg,#dbeafe,#bfdbfe); color:#2563eb; }
.activity-icon.task        { background:linear-gradient(135deg,#fef3c7,#fde68a); color:#d97706; }
.activity-icon.mentee      { background:linear-gradient(135deg,#dcfce7,#bbf7d0); color:#059669; }
.activity-content { flex:1; min-width:0; }
.activity-title { font-size:14px; font-weight:600; color:#1f2937; margin-bottom:3px; }
.activity-desc  { font-size:12px; color:#6b7280; margin-bottom:3px; }
.activity-time  { font-size:11px; color:#9ca3af; }
.notification-item.warning { border-left:4px solid #f59e0b; }
.notification-item.success { border-left:4px solid #10b981; }
.notification-item.info    { border-left:4px solid #3b82f6; }
.notification-content { flex:1; }
.notification-title { font-size:14px; font-weight:600; color:#1f2937; margin-bottom:3px; }
.notification-desc  { font-size:12px; color:#6b7280; margin-bottom:6px; }
.notification-action { font-size:12px; color:#d4a843; text-decoration:none; font-weight:600; }
.notification-action:hover { color:#b8922f; }
.view-all { font-size:12px; color:#6b7280; text-decoration:none; font-weight:500; }
.view-all:hover { color:#d4a843; }

/* ════════════════════════════════════════════
   CONTENT GRID (bottom cards)
════════════════════════════════════════════ */
.content-grid {
    display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px;
}
@media(max-width:900px){ .content-grid{ grid-template-columns:1fr; } }
.content-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; }
.content-card-header {
    padding:18px 22px; border-bottom:1px solid #e5e7eb;
    display:flex; justify-content:space-between; align-items:center;
}
.content-card-header h3 { font-size:15px; font-weight:700; color:#1f2937; display:flex; align-items:center; gap:8px; }
.content-card-body { padding:20px 22px; }
.eval-table { width:100%; border-collapse:collapse; font-size:13px; }
.eval-table th { padding:8px 10px; text-align:left; font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid #e5e7eb; }
.eval-table td { padding:10px 10px; border-bottom:1px solid #f3f4f6; color:#374151; }
.eval-table tr:last-child td { border-bottom:none; }
.rating-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
.rating-badge.excellent { background:#dcfce7; color:#059669; }
.rating-badge.good      { background:#dbeafe; color:#2563eb; }
.rating-badge.average   { background:#fef3c7; color:#d97706; }

/* ════════════════════════════════════════════
   CALENDAR SECTION
════════════════════════════════════════════ */
.calendar-section {
    display:grid; grid-template-columns:minmax(0,1.1fr) minmax(0,0.9fr); gap:20px; margin-bottom:24px;
}
@media(max-width:1000px){ .calendar-section{ grid-template-columns:1fr; } }

/* ─── Calendar Widget ─── */
.calendar-widget {
    background:#fff; border-radius:14px; border:1px solid #e5e7eb; overflow:hidden;
}
.calendar-header {
    background:linear-gradient(135deg,#d4a843,#b8922f);
    padding:14px 18px;
    display:flex; align-items:center; justify-content:space-between;
}
.calendar-header h3 { color:#fff; font-size:15px; font-weight:700; display:flex; align-items:center; gap:8px; }
.calendar-nav { display:flex; gap:6px; }
.cal-nav-btn {
    width:28px; height:28px; border:none; background:rgba(255,255,255,.2);
    color:#fff; border-radius:7px; cursor:pointer; font-size:12px;
    display:flex; align-items:center; justify-content:center;
    transition:background .2s;
}
.cal-nav-btn:hover { background:rgba(255,255,255,.35); }
.calendar-month-label { color:#fff; font-size:13px; font-weight:600; min-width:110px; text-align:center; }

.calendar-grid {
    padding:14px 16px;
}
.cal-weekdays {
    display:grid; grid-template-columns:repeat(7,1fr);
    margin-bottom:6px;
}
.cal-weekday {
    text-align:center; font-size:10px; font-weight:600;
    color:#6b7280; text-transform:uppercase; letter-spacing:.4px;
    padding:4px 0;
}
.cal-days { display:grid; grid-template-columns:repeat(7,1fr); gap:3px; }
.cal-day {
    aspect-ratio:1; border-radius:8px; display:flex; flex-direction:column;
    align-items:center; justify-content:flex-start; padding-top:5px;
    cursor:default; position:relative; transition:background .15s;
    font-size:11px; font-weight:500; color:#374151;
    min-height:32px;
}
.cal-day.empty { background:transparent; cursor:default; }
.cal-day.other-month { color:#d1d5db; }
.cal-day.today {
    background:linear-gradient(135deg,#d4a843,#b8922f) !important;
    color:#fff !important; font-weight:700;
}
.cal-day.has-event:not(.today) { background:#fef9ec; }
.cal-day:not(.empty):hover { background:#f3f4f6; }
.cal-day.today:hover { background:linear-gradient(135deg,#c4982d,#a57d22) !important; }

.cal-day-num { line-height:1; }
.cal-event-dots {
    display:flex; gap:2px; flex-wrap:wrap; justify-content:center;
    margin-top:2px; max-width:100%; padding:0 1px;
}
.cal-dot {
    width:4px; height:4px; border-radius:50%;
    background:#d4a843; flex-shrink:0;
}
.cal-dot.blue   { background:#3b82f6; }
.cal-dot.green  { background:#059669; }
.cal-dot.purple { background:#8b5cf6; }

.cal-day.has-event { cursor:pointer; }
.cal-day.has-event:not(.today):hover { background:#fff3cd; }

/* Event tooltip */
.cal-tooltip {
    display:none; position:fixed; z-index:9999;
    background:#1f2937; color:#fff; border-radius:10px;
    padding:10px 14px; font-size:12px;
    box-shadow:0 8px 30px rgba(0,0,0,.25); max-width:240px;
    pointer-events:none;
}
.cal-tooltip.visible { display:block; }
.cal-tooltip-title { font-weight:700; margin-bottom:4px; }
.cal-tooltip-desc  { color:#d1d5db; font-size:11px; }
.cal-tooltip-count { margin-top:6px; color:#fbbf24; font-weight:600; font-size:11px; }

/* ─── Upcoming Events ─── */
.upcoming-events-card {
    background:#fff; border-radius:14px; border:1px solid #e5e7eb; overflow:hidden;
    display:flex; flex-direction:column;
}
.upcoming-header {
    background:linear-gradient(135deg,#1e3a5f,#2d5a8e);
    padding:14px 18px;
    display:flex; align-items:center; justify-content:space-between;
}
.upcoming-header h3 { color:#fff; font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px; }
.upcoming-badge {
    background:rgba(255,255,255,.2); color:#fff;
    padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600;
}
.upcoming-list { flex:1; overflow-y:auto; max-height:340px; }
.upcoming-item {
    display:flex; align-items:flex-start; gap:12px;
    padding:11px 16px; border-bottom:1px solid #f3f4f6;
    transition:background .2s; cursor:default;
}
.upcoming-item:hover { background:#f9fafb; }
.upcoming-item:last-child { border-bottom:none; }
.upcoming-date-badge {
    min-width:42px; height:46px; border-radius:9px;
    background:linear-gradient(135deg,#dbeafe,#bfdbfe);
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    flex-shrink:0;
}
.upcoming-date-badge .day   { font-size:17px; font-weight:700; color:#1e40af; line-height:1; }
.upcoming-date-badge .month { font-size:8px;  font-weight:600; color:#3b82f6; text-transform:uppercase; letter-spacing:.5px; }
.upcoming-item-content { flex:1; min-width:0; }
.upcoming-item-title { font-size:13px; font-weight:600; color:#1f2937; margin-bottom:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.upcoming-item-desc  { font-size:11px; color:#6b7280; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:4px; }
.upcoming-item-days  { font-size:11px; font-weight:600; }
.upcoming-item-days.today-tag { color:#059669; }
.upcoming-item-days.soon-tag  { color:#d97706; }
.upcoming-item-days.later-tag { color:#6b7280; }
.upcoming-empty {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    padding:40px 24px; color:#9ca3af; text-align:center;
}
.upcoming-empty i { font-size:36px; margin-bottom:12px; color:#e5e7eb; }
.upcoming-empty p { font-size:13px; }

/* Day detail panel (below calendar) */
.cal-day-detail {
    padding:12px 16px; border-top:1px solid #f3f4f6;
    display:none; animation:fadeIn .2s ease;
}
.cal-day-detail.visible { display:block; }
.cal-day-detail h4 { font-size:13px; font-weight:700; color:#1f2937; margin-bottom:8px; }
.cal-event-item {
    display:flex; gap:10px; align-items:flex-start;
    padding:8px 0; border-bottom:1px solid #f3f4f6;
}
.cal-event-item:last-child { border-bottom:none; }
.cal-event-icon { width:28px; height:28px; background:#fef9ec; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#d4a843; font-size:12px; flex-shrink:0; }
.cal-event-info .cal-event-title { font-size:13px; font-weight:600; color:#1f2937; }
.cal-event-info .cal-event-desc  { font-size:11px; color:#6b7280; margin-top:2px; }

@keyframes fadeIn { from{opacity:0;transform:translateY(-4px)} to{opacity:1;transform:translateY(0)} }

/* ════════════════════════════════════════════
   INSTRUCTOR AVAILABILITY
════════════════════════════════════════════ */
/* Day cell states */
.cal-day.avail-on_leave  { background:#fee2e2 !important; }
.cal-day.avail-on_travel { background:#fef3c7 !important; }
.cal-day.avail-on_leave.selected-avail,
.cal-day.avail-on_travel.selected-avail { outline:2px solid #374151; outline-offset:-2px; }

.cal-day.selecting:not(.empty):not(.avail-on_leave):not(.avail-on_travel) { background:#e0f2fe !important; }
.cal-day.selected-avail:not(.avail-on_leave):not(.avail-on_travel) { background:#bae6fd !important; outline:2px solid #0284c7; outline-offset:-2px; }

/* Availability badge on calendar header */
.avail-status-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.4px;
}
.avail-status-badge.on_duty   { background:rgba(16,185,129,.2); color:#d1fae5; }
.avail-status-badge.on_leave  { background:rgba(239,68,68,.25);  color:#fee2e2; }
.avail-status-badge.on_travel { background:rgba(245,158,11,.25); color:#fef3c7; }

/* Manage availability toggle button */
.cal-manage-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 13px; border-radius:8px; font-size:12px; font-weight:600;
    cursor:pointer; border:none; transition:all .2s;
    background:rgba(255,255,255,.15); color:#fff;
}
.cal-manage-btn:hover { background:rgba(255,255,255,.28); }
.cal-manage-btn.active { background:rgba(255,255,255,.35); box-shadow:0 0 0 2px rgba(255,255,255,.5); }

/* Availability toolbar (shown below calendar header when mode active) */
.avail-toolbar {
    display:none; padding:10px 16px;
    background:#f0f9ff; border-bottom:1px solid #bae6fd;
    gap:8px; align-items:center; flex-wrap:wrap;
}
.avail-toolbar.visible { display:flex; }
.avail-toolbar-label { font-size:12px; font-weight:600; color:#0369a1; flex-shrink:0; }
.avail-type-btn {
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 12px; border-radius:20px; font-size:11px; font-weight:700;
    cursor:pointer; border:2px solid transparent; transition:all .2s;
}
.avail-type-btn.leave  { background:#fee2e2; color:#b91c1c; border-color:#fca5a5; }
.avail-type-btn.travel { background:#fef3c7; color:#92400e; border-color:#fde68a; }
.avail-type-btn.remove { background:#f3f4f6; color:#6b7280; border-color:#e5e7eb; }
.avail-type-btn.active { box-shadow:0 0 0 2px currentColor; }
.avail-note-input {
    flex:1; min-width:150px; max-width:260px;
    padding:5px 10px; border:1px solid #bae6fd; border-radius:8px;
    font-size:12px; color:#1f2937; outline:none;
}
.avail-note-input:focus { border-color:#0284c7; box-shadow:0 0 0 2px rgba(2,132,199,.15); }
.avail-save-btn {
    padding:5px 14px; border-radius:8px; font-size:12px; font-weight:700;
    background:linear-gradient(135deg,#0284c7,#0369a1); color:#fff;
    border:none; cursor:pointer; transition:all .2s;
}
.avail-save-btn:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(2,132,199,.3); }
.avail-save-btn:disabled { opacity:.5; cursor:not-allowed; transform:none; }
.avail-clear-btn {
    padding:5px 10px; border-radius:8px; font-size:12px; font-weight:600;
    background:#f3f4f6; color:#6b7280; border:1px solid #e5e7eb;
    cursor:pointer; transition:all .2s;
}
.avail-clear-btn:hover { background:#e5e7eb; }
.avail-count-badge {
    font-size:11px; font-weight:700; color:#0369a1;
    background:#e0f2fe; padding:3px 9px; border-radius:20px;
}

/* Legend strip */
.avail-legend {
    display:flex; gap:12px; align-items:center; padding:8px 16px;
    border-top:1px solid #f3f4f6; flex-wrap:wrap;
}
.avail-legend-item {
    display:flex; align-items:center; gap:5px;
    font-size:10px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.3px;
}
.avail-legend-dot {
    width:10px; height:10px; border-radius:3px;
}
.avail-legend-dot.on_leave  { background:#fca5a5; }
.avail-legend-dot.on_travel { background:#fde68a; }
.avail-legend-dot.on_duty   { background:#a7f3d0; }

/* Toast notification */
.avail-toast {
    position:fixed; bottom:28px; right:28px; z-index:10000;
    padding:12px 20px; border-radius:10px; font-size:13px; font-weight:600;
    color:#fff; box-shadow:0 8px 30px rgba(0,0,0,.2);
    animation:toastIn .3s ease; display:none;
}
.avail-toast.show   { display:block; }
.avail-toast.success{ background:linear-gradient(135deg,#059669,#047857); }
.avail-toast.error  { background:linear-gradient(135deg,#dc2626,#b91c1c); }
@keyframes toastIn { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

/* ════════════════════════════════════════════
   RESPONSIVE
════════════════════════════════════════════ */
@media(max-width:768px){
    .welcome-banner { flex-direction:column; text-align:center; }
    .welcome-banner-actions { justify-content:center; }
    .welcome-banner h1 { font-size:26px; }
}
</style>
</head>

<body class="dashboard-page">

<!-- ── Sidebar ──────────────────────────────────────────────────────────────── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="../../media/LOGO.jpg" alt="Logo" class="sidebar-logo"
             style="width:70px;height:70px;border-radius:16px;object-fit:cover;border:3px solid #fff;background:#fff;padding:4px;box-shadow:0 4px 12px rgba(0,0,0,.2);">
        <div class="sidebar-brand">
            <span class="sidebar-brand-name">IBM</span>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-avatar"><i class="fas fa-user"></i></div>
        <div class="sidebar-user-info">
            <span class="sidebar-user-name"><?php echo htmlspecialchars($user_name); ?></span>
            <span class="sidebar-user-role">Instructor</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-nav-label">Menu</div>
        <a href="dashboard.php" class="sidebar-nav-item active">
            <i class="fas fa-chart-pie"></i><span>Overview</span>
        </a>
        <a href="pages/students.php" class="sidebar-nav-item">
            <i class="fas fa-user-graduate"></i><span>Student Mentees</span>
        </a>
        <a href="pages/evaluation.php" class="sidebar-nav-item">
            <i class="fas fa-comment-dots"></i><span>Evaluation</span>
        </a>
        <a href="pages/reports.php" class="sidebar-nav-item">
            <i class="fas fa-file-alt"></i><span>Reports</span>
        </a>
        <a href="pages/profile.php" class="sidebar-nav-item">
            <i class="fas fa-user"></i><span>Profile</span>
        </a>
    </nav>
</aside>

<!-- ── Main Content ──────────────────────────────────────────────────────────── -->
<div class="main-content" style="position:relative;">
    <div style="position:fixed;top:0;left:var(--sidebar-width);right:0;bottom:0;
                background-image:url('../../media/LOGO.jpg');background-size:60%;
                background-position:center;background-repeat:no-repeat;
                opacity:.06;pointer-events:none;z-index:0;"></div>

    <!-- Topbar -->
    <header class="topbar" style="left:260px !important;">
        <div class="topbar-left">
            <button class="topbar-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <div>
                <div class="topbar-title">Dashboard</div>
                <div class="topbar-subtitle">Instructor Panel</div>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-date">
                <i class="fas fa-calendar-alt"></i>
                <span><?php echo date('F j, Y'); ?></span>
            </div>
            <a href="../../data/logout.php" class="topbar-logout">
                <i class="fas fa-sign-out-alt"></i><span>Logout</span>
            </a>
        </div>
    </header>

    <!-- Dashboard Content -->
    <main class="dashboard-content" style="position:relative;z-index:1;">

        <!-- ── Welcome Banner ── -->
        <div class="welcome-banner">
            <div class="welcome-banner-content">
                <div class="welcome-banner-role"><i class="fas fa-chalkboard-teacher"></i> Instructor</div>
                <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p>Track your student evaluations, monitor mentee progress, and manage calendar events — all from one place.</p>
            </div>
            <div class="welcome-banner-actions">
                <a href="pages/evaluation.php" class="banner-action-btn primary">
                    <i class="fas fa-play-circle"></i><span>Start Evaluation</span>
                </a>
                <a href="pages/students.php" class="banner-action-btn secondary">
                    <i class="fas fa-users"></i><span>Manage Students</span>
                </a>
                <a href="pages/reports.php" class="banner-action-btn tertiary">
                    <i class="fas fa-chart-bar"></i><span>View Reports</span>
                </a>
            </div>
        </div>

        <!-- ── Stats Grid ── -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon blue"><i class="fas fa-user-graduate"></i></div>
                </div>
                <div class="stat-card-value"><?php echo $student_count; ?></div>
                <div class="stat-card-label">Total Mentees</div>
                <?php if ($new_mentees > 0): ?>
                <div class="stat-change positive"><i class="fas fa-plus"></i> <?php echo $new_mentees; ?> new today</div>
                <?php endif; ?>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon indigo"><i class="fas fa-file-alt"></i></div>
                </div>
                <div class="stat-card-value"><?php echo $reports_generated; ?></div>
                <div class="stat-card-label">Reports Generated</div>
                <div class="stat-change positive"><i class="fas fa-chart-line"></i> Analytics Ready</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon teal"><i class="fas fa-tasks"></i></div>
                </div>
                <div class="stat-card-value"><?php echo $active_tasks; ?></div>
                <div class="stat-card-label">Active Tasks</div>
                <?php if ($active_tasks > 0): ?>
                <div class="stat-progress">
                    <div class="stat-progress-bar" style="width:<?php echo min($active_tasks * 10, 100); ?>%"></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon orange"><i class="fas fa-clipboard-check"></i></div>
                </div>
                <div class="stat-card-value"><?php echo $eval_percentage; ?>%</div>
                <div class="stat-card-label">Eval Progress</div>
                <div class="stat-progress">
                    <div class="stat-progress-bar" style="width:<?php echo $eval_percentage; ?>%"></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon purple"><i class="fas fa-calendar-check"></i></div>
                </div>
                <div class="stat-card-value"><?php echo count($upcoming_events_list); ?></div>
                <div class="stat-card-label">Upcoming Events</div>
                <div class="stat-change positive"><i class="fas fa-calendar-alt"></i> This month</div>
            </div>
        </div>

        <!-- ── Quick Actions ── -->
        <div class="quick-actions">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            <div class="action-buttons">
                <a href="pages/evaluation.php" class="action-btn primary">
                    <i class="fas fa-play"></i><span>Start Evaluation</span>
                </a>
                <a href="pages/students.php" class="action-btn secondary">
                    <i class="fas fa-user-plus"></i><span>Assign Task</span>
                </a>
                <a href="pages/reports.php" class="action-btn tertiary">
                    <i class="fas fa-file-download"></i><span>View Reports</span>
                </a>
                <a href="pages/profile.php" class="action-btn quaternary">
                    <i class="fas fa-user-cog"></i><span>Update Profile</span>
                </a>
            </div>
        </div>

        <!-- ── Activity & Notifications ── -->
        <div class="activity-notifications">
            <div class="activity-card">
                <div class="activity-header">
                    <h3><i class="fas fa-stream"></i> Recent Activity</h3>
                </div>
                <div class="activity-list">
                    <?php if (empty($recent_activities)): ?>
                    <div class="activity-item">
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;width:100%;padding:10px 0;">
                            <div style="text-align:center;padding:14px;background:linear-gradient(135deg,#dbeafe,#bfdbfe);border-radius:10px;">
                                <div style="font-size:22px;font-weight:700;color:#1f2937;"><?php echo $course_count; ?></div>
                                <div style="font-size:11px;color:#4b5563;">Total Courses</div>
                            </div>
                            <div style="text-align:center;padding:14px;background:linear-gradient(135deg,#dcfce7,#bbf7d0);border-radius:10px;">
                                <div style="font-size:22px;font-weight:700;color:#1f2937;"><?php echo $student_count; ?></div>
                                <div style="font-size:11px;color:#4b5563;">Total Mentees</div>
                            </div>
                            <div style="text-align:center;padding:14px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:10px;">
                                <div style="font-size:22px;font-weight:700;color:#1f2937;"><?php echo $active_tasks; ?></div>
                                <div style="font-size:11px;color:#4b5563;">Active Tasks</div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($recent_activities as $act):
                        $icon = match($act['type']) {
                            'evaluation' => 'fas fa-clipboard-check',
                            'task'       => 'fas fa-tasks',
                            'mentee'     => 'fas fa-user-plus',
                            default      => 'fas fa-circle',
                        };
                    ?>
                    <div class="activity-item">
                        <div class="activity-icon <?php echo $act['type']; ?>">
                            <i class="<?php echo $icon; ?>"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title"><?php echo htmlspecialchars($act['title']); ?></div>
                            <div class="activity-desc"><?php echo htmlspecialchars($act['description']); ?></div>
                            <div class="activity-time"><?php echo date('M j, Y g:i A', strtotime($act['date'])); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="notifications-card">
                <div class="notifications-header">
                    <h3><i class="fas fa-bell"></i> Notifications</h3>
                </div>
                <div class="notifications-list">
                    <?php if ($pending_evaluations > 0): ?>
                    <div class="notification-item warning">
                        <div class="notification-content">
                            <div class="notification-title"><?php echo $pending_evaluations; ?> Pending Evaluations</div>
                            <div class="notification-desc">Complete pending student evaluations</div>
                            <a href="pages/evaluation.php" class="notification-action">Review Now →</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($new_mentees > 0): ?>
                    <div class="notification-item success">
                        <div class="notification-content">
                            <div class="notification-title"><?php echo $new_mentees; ?> New Mentee<?php echo $new_mentees > 1 ? 's' : ''; ?></div>
                            <div class="notification-desc">Newly assigned students to mentor</div>
                            <a href="pages/students.php" class="notification-action">View Students →</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($active_tasks > 0): ?>
                    <div class="notification-item info">
                        <div class="notification-content">
                            <div class="notification-title"><?php echo $active_tasks; ?> Active Task<?php echo $active_tasks > 1 ? 's' : ''; ?></div>
                            <div class="notification-desc">Monitor ongoing task progress</div>
                            <a href="pages/students.php" class="notification-action">Check Tasks →</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (count($upcoming_events_list) > 0): ?>
                    <div class="notification-item info">
                        <div class="notification-content">
                            <div class="notification-title"><?php echo count($upcoming_events_list); ?> Upcoming Event<?php echo count($upcoming_events_list) > 1 ? 's' : ''; ?></div>
                            <div class="notification-desc">Events scheduled this month</div>
                            <a href="#calendar-section" class="notification-action">View Calendar →</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($pending_evaluations == 0 && $new_mentees == 0 && $active_tasks == 0 && count($upcoming_events_list) == 0): ?>
                    <div class="notification-item success">
                        <div class="notification-content">
                            <div class="notification-title">All Caught Up!</div>
                            <div class="notification-desc">No pending actions at the moment.</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════
             CALENDAR + UPCOMING EVENTS
        ════════════════════════════════════════════ -->
        <div class="calendar-section" id="calendar-section">

            <!-- Interactive Calendar -->
            <div class="calendar-widget">
                <div class="calendar-header">
                    <div class="calendar-nav">
                        <button class="cal-nav-btn" id="calPrev"><i class="fas fa-chevron-left"></i></button>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:center;gap:5px;flex:1;">
                        <h3 style="margin:0;"><i class="fas fa-calendar-alt"></i> <span id="calMonthLabel">Loading…</span></h3>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:center;">
                            <span class="avail-status-badge <?php echo $today_status; ?>" id="todayStatusBadge">
                                <?php
                                $icons = ['on_duty'=>'fa-circle-check','on_leave'=>'fa-house-medical','on_travel'=>'fa-plane'];
                                $labels = ['on_duty'=>'On Duty','on_leave'=>'On Leave','on_travel'=>'On Travel'];
                                ?>
                                <i class="fas <?php echo $icons[$today_status]; ?>"></i>
                                <?php echo $labels[$today_status]; ?> Today
                            </span>
                            <button class="cal-manage-btn" id="calManageBtn" title="Set your leave / travel dates">
                                <i class="fas fa-calendar-pen"></i> Set Availability
                            </button>
                        </div>
                    </div>
                    <div class="calendar-nav">
                        <button class="cal-nav-btn" id="calNext"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>

                <!-- Availability Toolbar (shown in edit mode) -->
                <div class="avail-toolbar" id="availToolbar">
                    <span class="avail-toolbar-label"><i class="fas fa-hand-pointer"></i> Click dates to select:</span>
                    <button class="avail-type-btn leave active"  data-type="on_leave"  id="btnLeave"><i class="fas fa-house-medical"></i> On Leave</button>
                    <button class="avail-type-btn travel"        data-type="on_travel" id="btnTravel"><i class="fas fa-plane"></i> On Travel</button>
                    <button class="avail-type-btn remove"        data-type="remove"    id="btnRemove"><i class="fas fa-rotate-left"></i> Reset to Duty</button>
                    <input  class="avail-note-input" id="availNote" placeholder="Optional note (e.g. conference, sick leave)…" maxlength="200">
                    <span   class="avail-count-badge" id="availCount">0 selected</span>
                    <button class="avail-save-btn"  id="availSave" disabled><i class="fas fa-floppy-disk"></i> Save</button>
                    <button class="avail-clear-btn" id="availClearSel"><i class="fas fa-xmark"></i> Clear</button>
                </div>

                <div class="calendar-grid">
                    <div class="cal-weekdays">
                        <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $wd): ?>
                        <div class="cal-weekday"><?php echo $wd; ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="cal-days" id="calDays"></div>
                </div>

                <!-- Day detail panel -->
                <div class="cal-day-detail" id="calDayDetail">
                    <h4 id="calDetailDate"></h4>
                    <div id="calDetailEvents"></div>
                </div>

                <!-- Legend -->
                <div class="avail-legend">
                    <span class="avail-legend-item"><span class="avail-legend-dot on_duty"></span>On Duty</span>
                    <span class="avail-legend-item"><span class="avail-legend-dot on_leave"></span>On Leave</span>
                    <span class="avail-legend-item"><span class="avail-legend-dot on_travel"></span>On Travel</span>
                </div>
            </div>

            <!-- Upcoming Events This Month -->
            <div class="upcoming-events-card">
                <div class="upcoming-header">
                    <h3><i class="fas fa-calendar-week"></i> Upcoming Events</h3>
                    <span class="upcoming-badge"><?php echo date('F Y'); ?></span>
                </div>
                <div class="upcoming-list" id="upcomingList">
                    <?php if (empty($upcoming_events_list)): ?>
                    <div class="upcoming-empty">
                        <i class="fas fa-calendar-times"></i>
                        <p>No upcoming events this month.</p>
                    </div>
                    <?php else: ?>
                    <?php
                    $today = date('Y-m-d');
                    foreach ($upcoming_events_list as $ev):
                        $evDay   = date('j',    strtotime($ev['event_date']));
                        $evMon   = date('M',    strtotime($ev['event_date']));
                        $diff    = (int)((strtotime($ev['event_date']) - strtotime($today)) / 86400);
                        if ($diff === 0) {
                            $daysLabel = '🟢 Today';
                            $daysClass = 'today-tag';
                        } elseif ($diff <= 3) {
                            $daysLabel = "In {$diff} day" . ($diff > 1 ? 's' : '');
                            $daysClass = 'soon-tag';
                        } else {
                            $daysLabel = "In {$diff} days";
                            $daysClass = 'later-tag';
                        }
                    ?>
                    <div class="upcoming-item">
                        <div class="upcoming-date-badge">
                            <span class="day"><?php echo $evDay; ?></span>
                            <span class="month"><?php echo $evMon; ?></span>
                        </div>
                        <div class="upcoming-item-content">
                            <div class="upcoming-item-title"><?php echo htmlspecialchars($ev['title']); ?></div>
                            <?php if (!empty($ev['description'])): ?>
                            <div class="upcoming-item-desc"><?php echo htmlspecialchars($ev['description']); ?></div>
                            <?php endif; ?>
                            <div class="upcoming-item-days <?php echo $daysClass; ?>"><?php echo $daysLabel; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Bottom Content Grid ── -->
        <div class="content-grid">
            <!-- Top Mentees GWA -->
            <div class="content-card">
                <div class="content-card-header">
                    <h3><i class="fas fa-trophy" style="color:#d4a843;"></i> Top Mentees by GWA</h3>
                    <span style="font-size:12px;color:#6b7280;"><?php echo $current_school_year; ?></span>
                </div>
                <div class="content-card-body">
                    <table class="eval-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>GWA</th>
                                <th>Semester</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($top_mentees_gwa)): ?>
                            <tr><td colspan="4" style="text-align:center;color:#9ca3af;padding:24px;">No GWA data available</td></tr>
                            <?php else: ?>
                            <?php foreach ($top_mentees_gwa as $i => $m): ?>
                            <tr>
                                <td><span style="font-weight:700;color:#d4a843;"><?php echo $i + 1; ?></span></td>
                                <td><?php echo htmlspecialchars($m['name']); ?></td>
                                <td>
                                    <span class="rating-badge <?php echo $m['gwa'] <= 1.75 ? 'excellent' : ($m['gwa'] <= 2.50 ? 'good' : 'average'); ?>">
                                        <?php echo number_format($m['gwa'], 2); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($m['semester']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Eval Progress -->
            <div class="content-card">
                <div class="content-card-header">
                    <h3><i class="fas fa-clipboard-list" style="color:#3b82f6;"></i> Evaluation Progress</h3>
                    <span style="font-size:12px;color:#6b7280;"><?php echo $current_school_year; ?></span>
                </div>
                <div class="content-card-body">
                    <div style="margin-bottom:20px;padding:14px;background:linear-gradient(135deg,#dbeafe,#bfdbfe);border-radius:10px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-weight:600;color:#1f2937;">Overall Completion</span>
                            <span style="font-weight:700;color:#2563eb;font-size:18px;"><?php echo $eval_percentage; ?>%</span>
                        </div>
                        <div style="font-size:12px;color:#4b5563;margin-top:4px;">
                            <?php echo $evaluated_students; ?> of <?php echo $total_eval_students; ?> students evaluated
                        </div>
                        <div style="margin-top:8px;height:6px;background:rgba(255,255,255,.5);border-radius:3px;overflow:hidden;">
                            <div style="height:100%;width:<?php echo $eval_percentage; ?>%;background:linear-gradient(90deg,#2563eb,#3b82f6);border-radius:3px;"></div>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div style="padding:12px;background:#f9fafb;border-radius:10px;text-align:center;">
                            <div style="font-size:24px;font-weight:700;color:#059669;"><?php echo $evaluated_students; ?></div>
                            <div style="font-size:11px;color:#6b7280;">Evaluated</div>
                        </div>
                        <div style="padding:12px;background:#f9fafb;border-radius:10px;text-align:center;">
                            <div style="font-size:24px;font-weight:700;color:#d97706;"><?php echo max(0,$total_eval_students - $evaluated_students); ?></div>
                            <div style="font-size:11px;color:#6b7280;">Remaining</div>
                        </div>
                        <div style="padding:12px;background:#f9fafb;border-radius:10px;text-align:center;">
                            <div style="font-size:24px;font-weight:700;color:#7c3aed;"><?php echo $graduated_mentee_count; ?></div>
                            <div style="font-size:11px;color:#6b7280;">Graduated</div>
                        </div>
                        <div style="padding:12px;background:#f9fafb;border-radius:10px;text-align:center;">
                            <div style="font-size:24px;font-weight:700;color:#2563eb;"><?php echo $reports_generated; ?></div>
                            <div style="font-size:11px;color:#6b7280;">Reports</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main><!-- /dashboard-content -->
</div><!-- /main-content -->

<!-- Tooltip -->
<div class="cal-tooltip" id="calTooltip">
    <div class="cal-tooltip-title" id="calTipTitle"></div>
    <div class="cal-tooltip-desc"  id="calTipDesc"></div>
    <div class="cal-tooltip-count" id="calTipCount"></div>
</div>

<!-- Availability Toast -->
<div class="avail-toast" id="availToast"></div>

<!-- ══ Calendar + Availability JavaScript ═══════════════════════════════════ -->
<script>
(function () {
    // ── Data injected from PHP ─────────────────────────────────────────────────
    const EVENTS       = <?php echo $calendarEventsJson; ?>;
    const AVAIL        = <?php echo $availabilityJson; ?>; // {date: {status, note}}
    const INSTRUCTOR_ID = <?php echo (int)$instructor_id; ?>;

    // ── Constants ──────────────────────────────────────────────────────────────
    const MONTHS  = ['January','February','March','April','May','June',
                     'July','August','September','October','November','December'];
    const today   = new Date();
    let   cur     = new Date(today.getFullYear(), today.getMonth(), 1);

    // ── Availability state ─────────────────────────────────────────────────────
    let availMap      = Object.assign({}, AVAIL); // live copy
    let managingMode  = false;
    let selectedDates = new Set();   // dates selected in current edit session
    let activeType    = 'on_leave';  // on_leave | on_travel | remove

    // ── DOM refs ───────────────────────────────────────────────────────────────
    const elLabel      = document.getElementById('calMonthLabel');
    const elDays       = document.getElementById('calDays');
    const elDetail     = document.getElementById('calDayDetail');
    const elDetDate    = document.getElementById('calDetailDate');
    const elDetEvs     = document.getElementById('calDetailEvents');
    const tooltip      = document.getElementById('calTooltip');
    const manageBtn    = document.getElementById('calManageBtn');
    const toolbar      = document.getElementById('availToolbar');
    const availCount   = document.getElementById('availCount');
    const availSave    = document.getElementById('availSave');
    const availClear   = document.getElementById('availClearSel');
    const availNote    = document.getElementById('availNote');
    const toastEl      = document.getElementById('availToast');
    const statusBadge  = document.getElementById('todayStatusBadge');

    // ── Helpers ────────────────────────────────────────────────────────────────
    function pad2(n){ return String(n).padStart(2,'0'); }
    function dateKey(y,m,d){ return `${y}-${pad2(m+1)}-${pad2(d)}`; }

    function showToast(msg, type='success'){
        toastEl.textContent = msg;
        toastEl.className   = `avail-toast show ${type}`;
        clearTimeout(toastEl._t);
        toastEl._t = setTimeout(()=>{ toastEl.classList.remove('show'); }, 3200);
    }

    function updateTodayBadge(){
        const key = dateKey(today.getFullYear(), today.getMonth(), today.getDate());
        const st  = (availMap[key] && availMap[key].status) ? availMap[key].status : 'on_duty';
        const icons  = {on_duty:'fa-circle-check', on_leave:'fa-house-medical', on_travel:'fa-plane'};
        const labels = {on_duty:'On Duty', on_leave:'On Leave', on_travel:'On Travel'};
        statusBadge.className = `avail-status-badge ${st}`;
        statusBadge.innerHTML = `<i class="fas ${icons[st]}"></i> ${labels[st]} Today`;
    }

    function updateSelectionCount(){
        const n = selectedDates.size;
        availCount.textContent = `${n} selected`;
        availSave.disabled = n === 0;
    }

    // ── Render Calendar ────────────────────────────────────────────────────────
    function renderCalendar(){
        const y  = cur.getFullYear();
        const m  = cur.getMonth();
        elLabel.textContent = `${MONTHS[m]} ${y}`;

        const firstDay = new Date(y, m, 1).getDay();
        const daysInM  = new Date(y, m+1, 0).getDate();

        elDays.innerHTML = '';

        for(let i=0; i<firstDay; i++){
            const cell = document.createElement('div');
            cell.className = 'cal-day empty';
            elDays.appendChild(cell);
        }

        for(let d=1; d<=daysInM; d++){
            const key   = dateKey(y, m, d);
            const evs   = EVENTS[key]  || [];
            const avail = availMap[key];
            const isToday = (y===today.getFullYear() && m===today.getMonth() && d===today.getDate());

            const cell = document.createElement('div');
            let cls = 'cal-day';
            if(isToday)             cls += ' today';
            if(evs.length > 0)      cls += ' has-event';
            if(avail && !isToday)   cls += ` avail-${avail.status}`;
            if(selectedDates.has(key)) cls += ' selected-avail';
            cell.className = cls;
            cell.dataset.date = key;

            const num = document.createElement('span');
            num.className = 'cal-day-num';
            num.textContent = d;
            cell.appendChild(num);

            // Availability mini-icon (small corner icon when set)
            if(avail && avail.status !== 'on_duty' && !isToday){
                const ico = document.createElement('span');
                ico.style.cssText = 'font-size:7px;line-height:1;margin-top:1px;opacity:.7;';
                ico.innerHTML = avail.status === 'on_leave'
                    ? '<i class="fas fa-house-medical"></i>'
                    : '<i class="fas fa-plane"></i>';
                cell.appendChild(ico);
            }

            if(evs.length > 0){
                const dots = document.createElement('div');
                dots.className = 'cal-event-dots';
                const colors = ['','blue','green','purple'];
                evs.slice(0,4).forEach((_,i)=>{
                    const dot = document.createElement('span');
                    dot.className = 'cal-dot '+(colors[i]||'');
                    dots.appendChild(dot);
                });
                cell.appendChild(dots);
                if(!managingMode){
                    cell.addEventListener('mouseenter', e=>showTooltip(e,evs));
                    cell.addEventListener('mousemove',  e=>moveTooltip(e));
                    cell.addEventListener('mouseleave', ()=>hideTooltip());
                }
            }

            // Click behaviour differs by mode
            if(managingMode){
                cell.style.cursor = 'pointer';
                cell.addEventListener('click', ()=>toggleDateSelection(key, cell));
                // Tooltip showing existing availability
                if(avail){
                    cell.title = `${avail.status.replace('_',' ')}${avail.note ? ': '+avail.note : ''}`;
                }
            } else {
                if(evs.length > 0){
                    cell.addEventListener('click', ()=>showDetail(key, d, m, y, evs, avail));
                } else if(avail){
                    cell.style.cursor = 'pointer';
                    cell.addEventListener('click', ()=>showDetail(key, d, m, y, [], avail));
                }
            }

            elDays.appendChild(cell);
        }
    }

    // ── Availability mode toggle ───────────────────────────────────────────────
    manageBtn.addEventListener('click', ()=>{
        managingMode = !managingMode;
        manageBtn.classList.toggle('active', managingMode);
        toolbar.classList.toggle('visible', managingMode);
        if(!managingMode){
            selectedDates.clear();
            updateSelectionCount();
            elDetail.classList.remove('visible');
        }
        renderCalendar();
    });

    // Type buttons
    document.querySelectorAll('.avail-type-btn').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            activeType = btn.dataset.type;
            document.querySelectorAll('.avail-type-btn').forEach(b=>b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    // Toggle a date in the selection set
    function toggleDateSelection(key, cell){
        if(selectedDates.has(key)){
            selectedDates.delete(key);
            cell.classList.remove('selected-avail');
        } else {
            selectedDates.add(key);
            cell.classList.add('selected-avail');
        }
        updateSelectionCount();
    }

    // Clear selection
    availClear.addEventListener('click', ()=>{
        selectedDates.clear();
        updateSelectionCount();
        renderCalendar();
    });

    // Save availability
    availSave.addEventListener('click', async ()=>{
        if(selectedDates.size === 0) return;
        availSave.disabled = true;
        availSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

        const dates  = Array.from(selectedDates);
        const isRemove = activeType === 'remove';
        const action = isRemove ? 'remove_availability' : 'save_availability';

        const body = new URLSearchParams({ action, dates: JSON.stringify(dates) });
        if(!isRemove){
            body.set('status', activeType);
            body.set('note',   availNote.value.trim());
        }

        try {
            const resp = await fetch(window.location.href, { method:'POST', body });
            const data = await resp.json();

            if(data.success){
                // Update local availMap
                dates.forEach(d=>{
                    if(isRemove){
                        delete availMap[d];
                    } else {
                        availMap[d] = { status: activeType, note: availNote.value.trim() };
                    }
                });
                selectedDates.clear();
                updateSelectionCount();
                updateTodayBadge();
                renderCalendar();
                showToast(isRemove
                    ? `${dates.length} date(s) reset to On Duty.`
                    : `${dates.length} date(s) marked as ${activeType.replace('_',' ')}.`
                );
                availNote.value = '';
            } else {
                showToast(data.message || 'Save failed.', 'error');
            }
        } catch(err){
            showToast('Network error. Please try again.', 'error');
        } finally {
            availSave.disabled = false;
            availSave.innerHTML = '<i class="fas fa-floppy-disk"></i> Save';
        }
    });

    // ── Tooltip ────────────────────────────────────────────────────────────────
    function showTooltip(e, evs){
        const first = evs[0];
        document.getElementById('calTipTitle').textContent = first.title;
        document.getElementById('calTipDesc').textContent  = first.description || '';
        document.getElementById('calTipCount').textContent =
            evs.length > 1 ? `+${evs.length-1} more event${evs.length>2?'s':''}` : '';
        tooltip.classList.add('visible');
        moveTooltip(e);
    }
    function moveTooltip(e){
        const pad=14, tw=tooltip.offsetWidth||220;
        let x=e.clientX+pad, y=e.clientY+pad;
        if(x+tw>window.innerWidth) x=e.clientX-tw-pad;
        tooltip.style.left=x+'px'; tooltip.style.top=y+'px';
    }
    function hideTooltip(){ tooltip.classList.remove('visible'); }

    // ── Day detail panel ───────────────────────────────────────────────────────
    function showDetail(key, d, m, y, evs, avail){
        elDetail.classList.add('visible');
        const dateLabel = new Date(y,m,d).toLocaleDateString('en-US',
            {weekday:'long',year:'numeric',month:'long',day:'numeric'});
        elDetDate.textContent = dateLabel;
        elDetEvs.innerHTML = '';

        // Show availability info banner if set
        if(avail && avail.status !== 'on_duty'){
            const banner = document.createElement('div');
            const colors = {on_leave:'#fee2e2',on_travel:'#fef3c7'};
            const textColors = {on_leave:'#b91c1c',on_travel:'#92400e'};
            const icons2 = {on_leave:'fa-house-medical',on_travel:'fa-plane'};
            banner.style.cssText = `padding:8px 12px;border-radius:8px;margin-bottom:8px;
                background:${colors[avail.status]};color:${textColors[avail.status]};
                font-size:12px;font-weight:700;display:flex;align-items:center;gap:6px;`;
            banner.innerHTML = `<i class="fas ${icons2[avail.status]}"></i>
                ${avail.status.replace('_',' ').replace(/\b\w/g,c=>c.toUpperCase())}
                ${avail.note ? `<span style="font-weight:400;margin-left:4px;">— ${escHtml(avail.note)}</span>` : ''}`;
            elDetEvs.appendChild(banner);
        }

        if(evs.length === 0 && (!avail || avail.status === 'on_duty')){
            const empty = document.createElement('div');
            empty.style.cssText = 'font-size:12px;color:#9ca3af;padding:8px 0;';
            empty.textContent = 'No events or availability set for this day.';
            elDetEvs.appendChild(empty);
        }

        evs.forEach(ev=>{
            const item = document.createElement('div');
            item.className = 'cal-event-item';
            item.innerHTML = `
                <div class="cal-event-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="cal-event-info">
                    <div class="cal-event-title">${escHtml(ev.title)}</div>
                    ${ev.description?`<div class="cal-event-desc">${escHtml(ev.description)}</div>`:''}
                </div>`;
            elDetEvs.appendChild(item);
        });
    }

    function escHtml(str){
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;')
                          .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Navigation ─────────────────────────────────────────────────────────────
    document.getElementById('calPrev').addEventListener('click', ()=>{
        cur = new Date(cur.getFullYear(), cur.getMonth()-1, 1);
        elDetail.classList.remove('visible');
        renderCalendar();
    });
    document.getElementById('calNext').addEventListener('click', ()=>{
        cur = new Date(cur.getFullYear(), cur.getMonth()+1, 1);
        elDetail.classList.remove('visible');
        renderCalendar();
    });

    // ── Init ───────────────────────────────────────────────────────────────────
    updateTodayBadge();
    renderCalendar();
})();
</script>

<script src="../../function/dashboard.js"></script>
<script src="../../function/session_guard.js"></script>
</body>
</html>
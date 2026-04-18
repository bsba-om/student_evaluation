<?php
require_once '../../../data/session_security.php';

$role_access = check_role_access('instructor');
$show_role_modal = !$role_access['allowed'];

$instructor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Jane Teacher';

$stats = ['total_students' => 0, 'by_major' => [], 'by_year' => [], 'by_gender' => [], 'total_tasks' => 0];

if (!$show_role_modal) {
    require_once '../../../data/config.php';
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mentees WHERE mentor_id = ?");
        $stmt->execute([$instructor_id]);
        $stats['total_students'] = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT maj.display_name as major_name, COUNT(*) as count FROM mentees me LEFT JOIN students s ON me.student_id = s.id LEFT JOIN majors maj ON s.major_id = maj.id WHERE me.mentor_id = ? GROUP BY maj.id, maj.display_name ORDER BY count DESC");
        $stmt->execute([$instructor_id]);
        $stats['by_major'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT s.year_level, COUNT(*) as count FROM mentees me LEFT JOIN students s ON me.student_id = s.id WHERE me.mentor_id = ? AND s.year_level IS NOT NULL GROUP BY s.year_level ORDER BY s.year_level");
        $stmt->execute([$instructor_id]);
        $stats['by_year'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE instructor_id = ?");
        $stmt->execute([$instructor_id]);
        $stats['total_tasks'] = $stmt->fetchColumn();
    } catch (PDOException $e) {}
}

$students = [];
if (!$show_role_modal) {
    require_once '../../../data/config.php';
    try {
        $stmt = $pdo->prepare("SELECT s.id as id, me.id as mentee_id, s.student_id, s.first_name, s.middle_name, s.last_name, s.suffix, s.email, s.year_level, s.avatar_initials, s.avatar_gradient_from, s.avatar_gradient_to, m.display_name as major_name, m.gradient_from as major_gradient_from, m.gradient_to as major_gradient_to FROM mentees me JOIN students s ON me.student_id = s.id LEFT JOIN majors m ON s.major_id = m.id WHERE me.mentor_id = ? ORDER BY s.last_name, s.first_name");
        $stmt->execute([$instructor_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

function getFullName($s) {
    $n = trim(($s['first_name']??'').' '.($s['middle_name']??'').' '.($s['last_name']??''));
    return trim($n.' '.($s['suffix']??''));
}
function getInitials($s) {
    $i = '';
    if (!empty($s['first_name'])) $i .= strtoupper($s['first_name'][0]);
    if (!empty($s['last_name'])) $i .= strtoupper($s['last_name'][0]);
    return $i ?: '??';
}
function getGradient($s) {
    if (!empty($s['major_gradient_from'])&&!empty($s['major_gradient_to'])) return "linear-gradient(135deg,{$s['major_gradient_from']},{$s['major_gradient_to']})";
    if (!empty($s['avatar_gradient_from'])&&!empty($s['avatar_gradient_to'])) return "linear-gradient(135deg,{$s['avatar_gradient_from']},{$s['avatar_gradient_to']})";
    return "linear-gradient(135deg,#d4a843,#8B6914)";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="../../../media/LOGO.jpg" type="image/jpeg">
<title>My Mentees — Instructor</title>
<link rel="stylesheet" href="../../../css/common.css">
<link rel="stylesheet" href="../style/dashboard.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* ══════════════════════════════════════════
   ROOT & BASE
══════════════════════════════════════════ */
:root {
    --gold: #d4a843;
    --gold-light: #e8c768;
    --gold-dark: #8B6914;
    --gold-pale: #fef9ec;
    --gold-border: rgba(212,168,67,.25);
    --ink: #111827;
    --ink-2: #374151;
    --muted: #6b7280;
    --border: #e5e7eb;
    --surface: #ffffff;
    --bg: #f8f7f4;
    --cream: #fdfcf8;
    --radius-card: 18px;
    --radius-pill: 100px;
    --shadow-card: 0 2px 12px rgba(0,0,0,.07), 0 0 0 1px rgba(0,0,0,.04);
    --shadow-hover: 0 12px 32px rgba(0,0,0,.12), 0 0 0 1px rgba(212,168,67,.2);
    --shadow-gold: 0 8px 24px rgba(212,168,67,.3);
    --transition: all .25s cubic-bezier(.4,0,.2,1);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--ink); }

/* ══════════════════════════════════════════
   LAYOUT SHELL
══════════════════════════════════════════ */
.page-shell {
    padding: 28px 28px 48px;
    max-width: 1600px;
}

/* ══════════════════════════════════════════
   PAGE HERO HEADER
══════════════════════════════════════════ */
.hero-banner {
    background: linear-gradient(135deg, #d4a843 0%, #b8922f 40%, #a38023 100%);
    border-radius: 24px;
    padding: 36px 40px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
}
.hero-banner::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse 60% 80% at 90% 50%, rgba(255,255,255,.15) 0%, transparent 70%);
    pointer-events: none;
}
.hero-banner::after {
    content: '';
    position: absolute;
    right: -60px; top: -60px;
    width: 280px; height: 280px;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,.15);
    pointer-events: none;
}
.hero-eyebrow {
    display: flex; align-items: center; gap: 10px;
    font-size: 11px; font-weight: 700; letter-spacing: 2px;
    text-transform: uppercase; color: #fff;
    margin-bottom: 10px;
}
.hero-eyebrow span { width: 32px; height: 2px; background: #fff; border-radius: 2px; }
.hero-title {
    font-family: 'Playfair Display', serif;
    font-size: 38px; font-weight: 800;
    color: #fff;
    line-height: 1.1;
    margin-bottom: 8px;
}
.hero-title em { color: #2d1f07; font-style: normal; }
.hero-sub { font-size: 14px; color: rgba(255,255,255,.85); max-width: 360px; }
.hero-kpis {
    display: flex; gap: 20px; flex-wrap: wrap;
    position: relative; z-index: 1;
}
.hero-kpi {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    backdrop-filter: blur(8px);
    border-radius: 16px;
    padding: 18px 24px;
    text-align: center;
    min-width: 110px;
}
.hero-kpi-num {
    font-family: 'Playfair Display', serif;
    font-size: 36px; font-weight: 800;
    color: #fff;
    line-height: 1;
}
.hero-kpi-label { font-size: 11px; color: rgba(255,255,255,.9); margin-top: 4px; font-weight: 500; }

/* ══════════════════════════════════════════
   TWO-COLUMN LAYOUT
══════════════════════════════════════════ */
.main-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 24px;
    align-items: start;
}
@media (max-width: 1200px) {
    .main-layout { grid-template-columns: 1fr; }
}

/* ══════════════════════════════════════════
   SECTION CARDS
══════════════════════════════════════════ */
.section-card {
    background: var(--surface);
    border-radius: var(--radius-card);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-card);
    overflow: hidden;
}
.section-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 22px 24px 18px;
    border-bottom: 1px solid var(--border);
    gap: 12px; flex-wrap: wrap;
}
.section-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 16px; font-weight: 700; color: var(--ink);
}
.section-title-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 15px;
}

/* ══════════════════════════════════════════
   TOOLBAR — SEARCH + FILTERS
══════════════════════════════════════════ */
.toolbar {
    display: flex; gap: 12px; padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    align-items: center; flex-wrap: wrap;
    background: var(--cream);
}
.search-wrap {
    flex: 1; min-width: 220px;
    position: relative;
}
.search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 14px; }
.search-inp {
    width: 100%; padding: 10px 14px 10px 40px;
    border: 1.5px solid var(--border);
    border-radius: 12px; font-family: 'Poppins', sans-serif;
    font-size: 13px; background: #fff; transition: var(--transition);
}
.search-inp:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(212,168,67,.12); }

.filter-sel {
    padding: 10px 14px; border: 1.5px solid var(--border);
    border-radius: 12px; font-family: 'Poppins', sans-serif;
    font-size: 13px; background: #fff; cursor: pointer; transition: var(--transition);
    color: var(--ink-2);
}
.filter-sel:focus { outline: none; border-color: var(--gold); }

.view-btns { display: flex; gap: 4px; }
.view-btn {
    width: 36px; height: 36px; border-radius: 10px;
    border: 1.5px solid var(--border); background: #fff;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: var(--muted); transition: var(--transition);
}
.view-btn.active { background: var(--gold); color: #fff; border-color: var(--gold); }
.view-btn:hover:not(.active) { background: var(--gold-pale); border-color: var(--gold-border); }

/* ══════════════════════════════════════════
   YEAR TABS
══════════════════════════════════════════ */
.year-tabs {
    display: flex; gap: 6px; padding: 14px 20px 0;
    overflow-x: auto;
}
.year-tab {
    padding: 8px 18px; border-radius: var(--radius-pill);
    border: 1.5px solid var(--border); background: #fff;
    font-size: 12px; font-weight: 600; cursor: pointer;
    color: var(--muted); white-space: nowrap;
    transition: var(--transition); display: flex; align-items: center; gap: 6px;
}
.year-tab .tab-count {
    background: var(--bg); border-radius: 20px;
    padding: 1px 7px; font-size: 11px; font-weight: 700;
    color: var(--muted); transition: var(--transition);
}
.year-tab.active {
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: #fff; border-color: transparent;
    box-shadow: var(--shadow-gold);
}
.year-tab.active .tab-count { background: rgba(255,255,255,.25); color: #fff; }
.year-tab:hover:not(.active) { border-color: var(--gold); color: var(--gold-dark); }

/* ══════════════════════════════════════════
   BULK BAR
══════════════════════════════════════════ */
.bulk-bar {
    display: flex; align-items: center; gap: 14px;
    padding: 12px 20px;
    background: linear-gradient(to right, #fffbeb, #fef3c7);
    border-bottom: 1px solid #fde68a;
    flex-wrap: wrap;
}
.bulk-bar label { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 600; color: #92400e; }
.bulk-bar input[type="checkbox"] { width: 17px; height: 17px; accent-color: var(--gold-dark); cursor: pointer; }
.sel-count {
    background: #fef08a; border-radius: 20px;
    padding: 3px 12px; font-size: 12px; font-weight: 700;
    color: #92400e;
}
.btn-assign {
    padding: 8px 20px; border-radius: 10px;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: #fff; font-family: 'Poppins', sans-serif;
    font-size: 13px; font-weight: 600; border: none; cursor: pointer;
    transition: var(--transition); display: flex; align-items: center; gap: 7px;
    box-shadow: 0 4px 12px rgba(212,168,67,.3);
}
.btn-assign:disabled { background: #9ca3af; box-shadow: none; cursor: not-allowed; }
.btn-assign:not(:disabled):hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(212,168,67,.4); }

/* ══════════════════════════════════════════
   GRID VIEW — STUDENT CARDS
══════════════════════════════════════════ */
.students-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 14px;
    padding: 18px 20px;
    min-height: 350px;
    max-height: 500px;
    overflow-y: auto;
}
.students-grid::-webkit-scrollbar { width: 6px; }
.students-grid::-webkit-scrollbar-track { background: #f3f4f6; border-radius: 3px; }
.students-grid::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 3px; }
.s-card {
    background: var(--cream);
    border: 1.5px solid var(--border);
    border-radius: 16px;
    padding: 16px;
    position: relative;
    transition: var(--transition);
    cursor: pointer;
}
.s-card:hover {
    border-color: var(--gold);
    box-shadow: var(--shadow-hover);
    transform: translateY(-3px);
}
.s-card-check {
    position: absolute; top: 12px; left: 12px;
    width: 16px; height: 16px; accent-color: var(--gold-dark); cursor: pointer;
}
.s-card-top {
    display: flex; align-items: center; gap: 12px;
    padding-left: 22px; margin-bottom: 12px;
}
.s-avatar {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 16px;
    flex-shrink: 0; box-shadow: 0 4px 12px rgba(0,0,0,.15);
}
.s-name { font-size: 14px; font-weight: 700; color: var(--ink); line-height: 1.3; }
.s-sid {
    display: inline-block; margin-top: 3px;
    font-size: 10px; font-weight: 700;
    background: #fef3c7; color: #92400e;
    padding: 2px 8px; border-radius: 20px; font-family: monospace;
}
.s-card-meta {
    display: flex; gap: 6px; flex-wrap: wrap;
    border-top: 1px solid var(--border); padding-top: 10px;
}
.s-badge {
    font-size: 10px; font-weight: 600; padding: 3px 9px;
    border-radius: 8px;
}
.s-badge-major { background: #ede9fe; color: #5b21b6; }
.s-badge-year { background: #e0f2fe; color: #0369a1; }
.s-card-actions {
    display: flex; gap: 6px; margin-top: 12px;
}
.btn-mini {
    flex: 1; padding: 7px; border-radius: 8px;
    border: 1.5px solid var(--border);
    background: #fff; font-family: 'Poppins', sans-serif;
    font-size: 11px; font-weight: 600; cursor: pointer;
    transition: var(--transition); color: var(--ink-2);
    display: flex; align-items: center; justify-content: center; gap: 5px;
}
.btn-mini:hover { background: var(--gold); color: #fff; border-color: var(--gold); }
.btn-mini-view:hover { background: var(--gold); color: #fff; border-color: var(--gold); }

/* ══════════════════════════════════════════
   LIST VIEW — TABLE
══════════════════════════════════════════ */
.students-table-wrap { overflow-x: auto; padding: 0 4px 4px; display: none; }
.students-table { width: 100%; border-collapse: collapse; }
.students-table thead tr { background: linear-gradient(135deg, #1a1209, #2d1f07); }
.students-table th {
    padding: 13px 16px; text-align: left;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .7px;
    color: rgba(255,255,255,.7); white-space: nowrap;
}
.students-table td {
    padding: 13px 16px; border-bottom: 1px solid var(--border);
    vertical-align: middle; font-size: 13px;
}
.students-table tbody tr { transition: background .15s; }
.students-table tbody tr:hover { background: var(--gold-pale); }
.students-table tbody tr:last-child td { border-bottom: none; }
.tbl-avatar {
    width: 36px; height: 36px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 13px;
}
.tbl-name { font-weight: 700; color: var(--ink); }
.tbl-sid { font-size: 11px; font-family: monospace; color: var(--muted); margin-top: 2px; }

/* ══════════════════════════════════════════
   EMPTY STATE
══════════════════════════════════════════ */
.empty-state {
    padding: 64px 24px; text-align: center; color: var(--muted);
}
.empty-state-icon {
    width: 80px; height: 80px; margin: 0 auto 20px;
    border-radius: 50%; background: var(--gold-pale);
    border: 2px dashed var(--gold-border);
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; color: var(--gold);
}
.empty-state h3 { font-size: 18px; font-weight: 700; color: var(--ink-2); margin-bottom: 6px; }

/* ══════════════════════════════════════════
   RIGHT COLUMN — TASKS PANEL
══════════════════════════════════════════ */
.tasks-panel { display: flex; flex-direction: column; gap: 20px; }

.task-filters {
    display: flex; gap: 6px; padding: 16px 20px 0;
    flex-wrap: wrap;
}
.task-filter-chip {
    padding: 6px 14px; border-radius: var(--radius-pill);
    border: 1.5px solid var(--border); background: #fff;
    font-size: 12px; font-weight: 600; color: var(--muted);
    cursor: pointer; transition: var(--transition);
}
.task-filter-chip.active {
    background: var(--gold); color: #fff;
    border-color: var(--gold);
}
.task-filter-chip:hover:not(.active) { border-color: var(--gold); color: var(--gold-dark); }

.tasks-list { padding: 14px 18px; display: flex; flex-direction: column; gap: 10px; min-height: 280px; max-height: 500px; overflow-y: auto; }
.tasks-list::-webkit-scrollbar { width: 5px; }
.tasks-list::-webkit-scrollbar-track { background: #f3f4f6; border-radius: 3px; }
.tasks-list::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 3px; }

.task-card {
    background: var(--cream);
    border: 1.5px solid var(--border);
    border-radius: 14px; padding: 14px 16px;
    transition: var(--transition); position: relative;
    cursor: default;
}
.task-card:hover { border-color: var(--gold-border); box-shadow: 0 4px 16px rgba(0,0,0,.06); }
.task-card.priority-high { border-left: 3px solid #ef4444; }
.task-card.priority-medium { border-left: 3px solid #f59e0b; }
.task-card.priority-low { border-left: 3px solid #22c55e; }
.task-card.completed { opacity: .7; }

.task-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 8px; }
.task-card-title { font-size: 13px; font-weight: 700; color: var(--ink); line-height: 1.35; }
.task-card-title.done { text-decoration: line-through; color: var(--muted); }
.task-status-dot {
    width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 4px;
}
.task-status-dot.pending { background: #f59e0b; }
.task-status-dot.completed { background: #22c55e; }

.task-card-meta {
    display: flex; flex-wrap: wrap; gap: 8px;
    font-size: 11px; color: var(--muted); margin-bottom: 8px;
}
.task-card-meta span { display: flex; align-items: center; gap: 4px; }
.task-card-meta i { color: var(--gold); }

.task-mentees { display: flex; flex-wrap: wrap; gap: 4px; }
.task-mentee-chip {
    display: flex; align-items: center; gap: 5px;
    background: #fff; border: 1px solid var(--border);
    border-radius: 20px; padding: 2px 8px;
    font-size: 11px; font-weight: 500; color: var(--ink-2);
}
.chip-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--gold); }

.task-progress { margin-top: 10px; }
.task-progress-bar { height: 5px; background: var(--border); border-radius: 3px; overflow: hidden; margin-top: 4px; }
.task-progress-fill { height: 100%; border-radius: 3px; background: linear-gradient(to right, var(--gold), var(--gold-light)); transition: width .5s ease; }

.priority-badge {
    font-size: 10px; font-weight: 700;
    padding: 2px 8px; border-radius: 6px;
    text-transform: uppercase; letter-spacing: .4px;
}
.priority-badge.high { background: #fee2e2; color: #ef4444; }
.priority-badge.medium { background: #fef3c7; color: #d97706; }
.priority-badge.low { background: #dcfce7; color: #22c55e; }

/* ══════════════════════════════════════════
   ADD TASK BUTTON IN PANEL
══════════════════════════════════════════ */
.panel-btn {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 18px; border-radius: 12px;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: #fff; border: none; cursor: pointer;
    font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 600;
    transition: var(--transition); box-shadow: 0 4px 12px rgba(212,168,67,.3);
}
.panel-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(212,168,67,.4); }

/* ══════════════════════════════════════════
   MODALS
══════════════════════════════════════════ */
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(17,24,39,.6); backdrop-filter: blur(4px);
    z-index: 9999; padding: 20px;
    align-items: center; justify-content: center;
    overflow-y: auto;
}
.modal-overlay.open { display: flex; }
.modal-box {
    background: #fff; border-radius: 24px;
    box-shadow: 0 32px 80px rgba(0,0,0,.25);
    width: 100%; max-width: 680px;
    animation: modalIn .3s cubic-bezier(.34,1.56,.64,1);
    position: relative; overflow: hidden;
}
.modal-box.wide { max-width: 900px; }
@keyframes modalIn {
    from { opacity: 0; transform: scale(.93) translateY(16px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.modal-hero {
    padding: 32px 36px;
    background: linear-gradient(135deg, #1a1209 0%, #3d2a0a 100%);
    position: relative; overflow: hidden;
}
.modal-hero::after {
    content: ''; position: absolute;
    right: -40px; bottom: -40px;
    width: 180px; height: 180px; border-radius: 50%;
    border: 2px solid rgba(212,168,67,.15);
}
.modal-hero-title {
    font-family: 'Playfair Display', serif;
    font-size: 24px; color: #fff; font-weight: 800;
    margin-bottom: 4px;
}
.modal-hero-sub { font-size: 13px; color: rgba(255,255,255,.5); }
.modal-close-btn {
    position: absolute; top: 18px; right: 18px;
    width: 36px; height: 36px; border-radius: 50%;
    background: rgba(255,255,255,.15); border: none;
    color: #fff; font-size: 16px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: var(--transition);
}
.modal-close-btn:hover { background: rgba(255,255,255,.25); transform: rotate(90deg); }

.modal-body { padding: 32px 36px; }
.form-label { display: block; font-size: 12px; font-weight: 700; color: var(--ink-2); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
.form-control {
    width: 100%; padding: 11px 14px;
    border: 1.5px solid var(--border); border-radius: 12px;
    font-family: 'Poppins', sans-serif; font-size: 14px;
    color: var(--ink); background: var(--cream);
    transition: var(--transition);
}
.form-control:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(212,168,67,.12); background: #fff; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px; }
.form-group { margin-bottom: 18px; }
.form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); }
.btn-cancel {
    padding: 11px 24px; border-radius: 12px;
    border: 1.5px solid var(--border); background: #fff;
    font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 600;
    cursor: pointer; color: var(--ink-2); transition: var(--transition);
}
.btn-cancel:hover { background: var(--bg); }
.btn-submit {
    padding: 11px 28px; border-radius: 12px;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: #fff; border: none;
    font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 600;
    cursor: pointer; transition: var(--transition);
    box-shadow: var(--shadow-gold);
}
.btn-submit:hover { transform: translateY(-1px); box-shadow: 0 12px 28px rgba(212,168,67,.4); }
.btn-submit:disabled { background: #9ca3af; box-shadow: none; cursor: not-allowed; transform: none; }

/* ══════════════════════════════════════════
   STUDENT DETAIL MODAL SPECIFICS
══════════════════════════════════════════ */
.student-modal-hero {
    padding: 36px;
    display: flex; align-items: center; gap: 24px;
    position: relative; overflow: hidden;
}
.student-modal-avatar {
    width: 90px; height: 90px; border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    font-size: 34px; font-weight: 800; color: #fff;
    box-shadow: 0 8px 24px rgba(0,0,0,.2);
    border: 3px solid rgba(255,255,255,.3);
    flex-shrink: 0;
}
.student-modal-name {
    font-family: 'Playfair Display', serif;
    font-size: 26px; font-weight: 800; color: #fff; margin-bottom: 4px;
}
.student-modal-sid {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,.15); padding: 4px 12px;
    border-radius: 20px; font-size: 12px; font-weight: 600; color: rgba(255,255,255,.9);
    margin-bottom: 8px;
}
.student-modal-info { font-size: 13px; color: rgba(255,255,255,.65); }
.student-modal-info span { display: inline-flex; align-items: center; gap: 6px; margin-right: 18px; }
.student-modal-info i { color: var(--gold-light); }

.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
.detail-card {
    background: var(--cream); border: 1px solid var(--border);
    border-radius: 14px; padding: 14px 16px;
}
.detail-card-label { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
.detail-card-label i { color: var(--gold); }
.detail-card-val { font-size: 15px; font-weight: 700; color: var(--ink); }

.modal-task-list { display: flex; flex-direction: column; gap: 10px; max-height: 160px; overflow-y: auto; }
.modal-task-list::-webkit-scrollbar { width: 4px; }
.modal-task-list::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 2px; }

.modal-task-item {
    background: var(--cream); border: 1.5px solid var(--border);
    border-radius: 12px; padding: 14px 16px;
    display: flex; align-items: center; gap: 14px;
    transition: var(--transition);
}
.modal-task-item.done { opacity: .65; }
.modal-task-item:hover { border-color: var(--gold-border); }
.modal-task-check { width: 20px; height: 20px; border-radius: 50%; border: 2px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; }
.modal-task-check.done { background: #22c55e; border-color: #22c55e; color: #fff; }
.modal-task-info { flex: 1; min-width: 0; }
.modal-task-name { font-size: 13px; font-weight: 700; color: var(--ink); }
.modal-task-name.done { text-decoration: line-through; color: var(--muted); }
.modal-task-sub { font-size: 11px; color: var(--muted); margin-top: 2px; }
.btn-complete {
    padding: 7px 14px; border-radius: 8px; border: none; cursor: pointer;
    font-family: 'Poppins', sans-serif; font-size: 11px; font-weight: 600;
    background: linear-gradient(135deg, #059669, #34d399); color: #fff;
    transition: var(--transition); flex-shrink: 0;
}
.btn-complete:hover { transform: scale(1.03); }

/* ══════════════════════════════════════════
   TOAST
══════════════════════════════════════════ */
.toasts {
    position: fixed; bottom: 24px; right: 24px;
    z-index: 99999; display: flex; flex-direction: column; gap: 8px;
}
.toast-item {
    background: var(--ink); color: #fff;
    padding: 14px 18px; border-radius: 14px;
    font-size: 13px; font-weight: 500;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,.2);
    animation: toastIn .35s cubic-bezier(.34,1.56,.64,1);
    min-width: 260px;
    border-left: 4px solid var(--gold);
}
.toast-item.success { border-left-color: #22c55e; }
.toast-item.error { border-left-color: #ef4444; }
.toast-item i { font-size: 16px; }
.toast-item.success i { color: #22c55e; }
.toast-item.error i { color: #ef4444; }
.toast-item .ti-close { margin-left: auto; background: none; border: none; color: rgba(255,255,255,.6); cursor: pointer; font-size: 14px; }
@keyframes toastIn {
    from { opacity: 0; transform: translateX(30px) scale(.95); }
    to { opacity: 1; transform: translateX(0) scale(1); }
}

/* ══════════════════════════════════════════
   TASK PANEL STATS ROW
══════════════════════════════════════════ */
.panel-stats {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 10px; padding: 16px 18px;
    border-bottom: 1px solid var(--border);
}
.panel-stat {
    background: var(--cream); border-radius: 12px;
    padding: 12px 14px; border: 1px solid var(--border);
    text-align: center;
}
.panel-stat-num { font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 800; color: var(--gold-dark); line-height: 1; }
.panel-stat-lbl { font-size: 10px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .4px; margin-top: 2px; }

/* Loading spinner */
.spinner { display: inline-block; width: 20px; height: 20px; border: 2.5px solid rgba(212,168,67,.3); border-top-color: var(--gold); border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body class="dashboard-page">

<!-- ══ SIDEBAR ══════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="../../../media/LOGO.jpg" alt="Logo" class="sidebar-logo" style="width:70px;height:70px;border-radius:16px;object-fit:cover;border:3px solid white;padding:4px;box-shadow:0 4px 12px rgba(0,0,0,.2);">
        <div class="sidebar-brand"><span class="sidebar-brand-name">IBM</span></div>
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
        <a href="../dashboard.php" class="sidebar-nav-item"><i class="fas fa-chart-pie"></i><span>Overview</span></a>
        <a href="students.php" class="sidebar-nav-item active"><i class="fas fa-user-graduate"></i><span>Student Mentees</span></a>
        <a href="evaluation.php" class="sidebar-nav-item"><i class="fas fa-comment-dots"></i><span>Evaluation</span></a>
        <a href="reports.php" class="sidebar-nav-item"><i class="fas fa-file-alt"></i><span>Reports</span></a>
        <a href="profile.php" class="sidebar-nav-item"><i class="fas fa-user"></i><span>Profile</span></a>
    </nav>
</aside>

<!-- ══ MAIN CONTENT ═════════════════════════════════════════════ -->
<div class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <button class="topbar-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <div>
                <div class="topbar-title">Student Mentees</div>
                <div class="topbar-subtitle">Gold Instructor Portal</div>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-date"><i class="fas fa-calendar-alt"></i><span><?php echo date('F j, Y'); ?></span></div>
            <a href="../../../data/logout.php" class="topbar-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </header>

    <main class="dashboard-content">
    <div class="page-shell">

        <!-- ── HERO BANNER ─────────────────────────────────────── -->
        <div class="hero-banner">
            <div style="position:relative;z-index:1;">
                <div class="hero-eyebrow"><span></span> Gold Instructor Portal</div>
                <h1 class="hero-title">Your <em>Mentees</em></h1>
                <p class="hero-sub">Manage, track, and assign tasks to your student mentees all in one place.</p>
            </div>
            <div class="hero-kpis">
                <div class="hero-kpi">
                    <div class="hero-kpi-num"><?php echo $stats['total_students']; ?></div>
                    <div class="hero-kpi-label">Total Students</div>
                </div>
                <div class="hero-kpi">
                    <div class="hero-kpi-num"><?php echo $stats['total_tasks']; ?></div>
                    <div class="hero-kpi-label">Tasks Created</div>
                </div>
                <div class="hero-kpi">
                    <div class="hero-kpi-num"><?php echo count($stats['by_major']); ?></div>
                    <div class="hero-kpi-label">Majors</div>
                </div>
            </div>
        </div>

        <!-- ── TWO COLUMN LAYOUT ───────────────────────────────── -->
        <div class="main-layout">

            <!-- LEFT: STUDENTS ─────────────────────────────────── -->
            <div class="section-card">

                <!-- Toolbar -->
                <div class="toolbar">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" class="search-inp" id="searchInput" placeholder="Search name, ID, email…">
                    </div>
                    <select class="filter-sel" id="majorFilter">
                        <option value="">All Majors</option>
                        <?php foreach ($stats['by_major'] as $m): ?>
                        <option value="<?php echo htmlspecialchars($m['major_name']); ?>">
                            <?php echo htmlspecialchars($m['major_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="view-btns">
                        <button class="view-btn active" id="viewBtnGrid" onclick="setView('grid')" title="Grid View"><i class="fas fa-th-large"></i></button>
                        <button class="view-btn" id="viewBtnList" onclick="setView('list')" title="List View"><i class="fas fa-list"></i></button>
                    </div>
                </div>

                <!-- Year Tabs -->
                <div class="year-tabs">
                    <button class="year-tab active" data-year="all" onclick="filterYear('all')">
                        <i class="fas fa-layer-group"></i> All <span class="tab-count" id="cnt-all">(<?php echo count($students); ?>)</span>
                    </button>
                    <?php for ($y = 1; $y <= 4; $y++): 
                        $cnt = count(array_filter($students, fn($s) => preg_match('/'.$y.'/', $s['year_level']??'')));
                    ?>
                    <button class="year-tab" data-year="<?php echo $y; ?>" onclick="filterYear('<?php echo $y; ?>')">
                        <i class="fas fa-graduation-cap"></i> Year <?php echo $y; ?> <span class="tab-count" id="cnt-<?php echo $y; ?>">(<?php echo $cnt; ?>)</span>
                    </button>
                    <?php endfor; ?>
                </div>

                <!-- Bulk Bar -->
                <div class="bulk-bar">
                    <label for="selectAllCb">
                        <input type="checkbox" id="selectAllCb" onchange="toggleAll()">
                        Select All Visible
                    </label>
                    <span class="sel-count" id="selCount">0 selected</span>
                    <button class="btn-assign" id="assignBulkBtn" disabled onclick="openBulkAssign()">
                        <i class="fas fa-tasks"></i> Assign Task
                    </button>
                </div>

                <!-- Grid View -->
                <div class="students-grid" id="gridView">
                    <?php foreach ($students as $s):
                        $fullName = getFullName($s);
                        $initials = getInitials($s);
                        $gradient = getGradient($s);
                        preg_match('/(\d+)/', $s['year_level']??'', $matches);
                        $yearNum = $matches[1] ?? '';
                    ?>
                    <div class="s-card"
                         data-name="<?php echo strtolower($fullName); ?>"
                         data-email="<?php echo strtolower($s['email']??''); ?>"
                         data-sid="<?php echo strtolower($s['student_id']??''); ?>"
                         data-major="<?php echo strtolower($s['major_name']??''); ?>"
                         data-year="<?php echo $yearNum; ?>"
                         data-mentee="<?php echo $s['mentee_id']; ?>"
                         onclick="viewStudent(<?php echo $s['mentee_id']; ?>)">
                        <input type="checkbox" class="s-card-check stu-cb" value="<?php echo $s['mentee_id']; ?>" onclick="event.stopPropagation()" onchange="updateSel()">
                        <div class="s-card-top">
                            <div class="s-avatar" style="background:<?php echo $gradient; ?>;"><?php echo htmlspecialchars($initials); ?></div>
                            <div>
                                <div class="s-name"><?php echo htmlspecialchars($fullName); ?></div>
                                <div class="s-sid"><?php echo htmlspecialchars($s['student_id']??'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="s-card-meta">
                            <span class="s-badge s-badge-major"><?php echo htmlspecialchars($s['major_name']??'N/A'); ?></span>
                            <span class="s-badge s-badge-year"><?php echo htmlspecialchars($s['year_level']??'N/A'); ?></span>
                        </div>
                        <div class="s-card-actions" onclick="event.stopPropagation()">
                            <button class="btn-mini btn-mini-view" onclick="viewStudent(<?php echo $s['mentee_id']; ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn-mini" onclick="openSingleAssign(<?php echo $s['mentee_id']; ?>, '<?php echo htmlspecialchars(addslashes($fullName)); ?>')">
                                <i class="fas fa-tasks"></i> Assign
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($students)): ?>
                    <div class="empty-state" style="grid-column:1/-1;">
                        <div class="empty-state-icon"><i class="fas fa-user-graduate"></i></div>
                        <h3>No Mentees Yet</h3>
                        <p>Students assigned to you will appear here.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- List View -->
                <div class="students-table-wrap" id="listView">
                    <table class="students-table">
                        <thead>
                            <tr>
                                <th style="width:40px;"></th>
                                <th style="width:40px;"></th>
                                <th>Student</th>
                                <th>ID</th>
                                <th>Major</th>
                                <th>Year</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($students as $s):
                            $fullName = getFullName($s);
                            $initials = getInitials($s);
                            $gradient = getGradient($s);
                            preg_match('/(\d+)/', $s['year_level']??'', $matches);
                            $yearNum = $matches[1] ?? '';
                        ?>
                        <tr class="stu-row"
                            data-name="<?php echo strtolower($fullName); ?>"
                            data-email="<?php echo strtolower($s['email']??''); ?>"
                            data-sid="<?php echo strtolower($s['student_id']??''); ?>"
                            data-major="<?php echo strtolower($s['major_name']??''); ?>"
                            data-year="<?php echo $yearNum; ?>"
                            data-mentee="<?php echo $s['mentee_id']; ?>">
                            <td><input type="checkbox" class="stu-cb-tbl" value="<?php echo $s['mentee_id']; ?>" onchange="updateSel()" style="accent-color:var(--gold-dark);width:16px;height:16px;cursor:pointer;"></td>
                            <td><div class="tbl-avatar" style="background:<?php echo $gradient; ?>;"><?php echo htmlspecialchars($initials); ?></div></td>
                            <td>
                                <div class="tbl-name"><?php echo htmlspecialchars($fullName); ?></div>
                                <div class="tbl-sid"><?php echo htmlspecialchars($s['email']??''); ?></div>
                            </td>
                            <td style="font-family:monospace;font-size:12px;color:var(--muted);"><?php echo htmlspecialchars($s['student_id']??'N/A'); ?></td>
                            <td><span class="s-badge s-badge-major"><?php echo htmlspecialchars($s['major_name']??'N/A'); ?></span></td>
                            <td><span class="s-badge s-badge-year"><?php echo htmlspecialchars($s['year_level']??'N/A'); ?></span></td>
                            <td style="text-align:center;">
                                <div style="display:flex;gap:6px;justify-content:center;">
                                    <button class="btn-mini btn-mini-view" style="flex:none;padding:6px 12px;" onclick="viewStudent(<?php echo $s['mentee_id']; ?>)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-mini" style="flex:none;padding:6px 12px;" onclick="openSingleAssign(<?php echo $s['mentee_id']; ?>, '<?php echo htmlspecialchars(addslashes($fullName)); ?>')"><i class="fas fa-tasks"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div><!-- /LEFT -->

            <!-- RIGHT: TASKS PANEL ──────────────────────────────── -->
            <div class="tasks-panel">
                <div class="section-card">
                    <div class="section-head">
                        <div class="section-title">
                            <div class="section-title-icon"><i class="fas fa-clipboard-list"></i></div>
                            All Tasks
                        </div>
                    </div>

                    <!-- Stats row -->
                    <div class="panel-stats" id="panelStats">
                        <div class="panel-stat">
                            <div class="panel-stat-num" id="statTotal">—</div>
                            <div class="panel-stat-lbl">Total</div>
                        </div>
                        <div class="panel-stat">
                            <div class="panel-stat-num" id="statPending">—</div>
                            <div class="panel-stat-lbl">Pending</div>
                        </div>
                        <div class="panel-stat">
                            <div class="panel-stat-num" id="statDone">—</div>
                            <div class="panel-stat-lbl">Done</div>
                        </div>
                    </div>

                    <!-- Filter chips -->
                    <div class="task-filters">
                        <button class="task-filter-chip active" data-tf="all" onclick="filterTasks('all')">All</button>
                        <button class="task-filter-chip" data-tf="pending" onclick="filterTasks('pending')">Pending</button>
                        <button class="task-filter-chip" data-tf="completed" onclick="filterTasks('completed')">Completed</button>
                        <button class="task-filter-chip" data-tf="high" onclick="filterTasks('high')"><i class="fas fa-flag" style="color:#ef4444;font-size:10px;"></i> High</button>
                        <button class="task-filter-chip" data-tf="overdue" onclick="filterTasks('overdue')">Overdue</button>
                    </div>

                    <!-- Tasks list -->
                    <div class="tasks-list" id="tasksList">
                        <div style="text-align:center;padding:40px;color:var(--muted);">
                            <div class="spinner" style="margin:0 auto 12px;"></div>
                            <div style="font-size:13px;">Loading tasks…</div>
                        </div>
                    </div>
                </div>
            </div><!-- /RIGHT -->

        </div><!-- /main-layout -->
    </div>
    </main>
</div>

<!-- ══ MODAL: STUDENT DETAIL ════════════════════════════════════ -->
<div class="modal-overlay" id="studentModal">
    <div class="modal-box wide">
        <div class="student-modal-hero" id="studentModalHero" style="background:linear-gradient(135deg,#1a1209,#3d2a0a);">
            <div class="student-modal-avatar" id="smAvatar"></div>
            <div style="flex:1;position:relative;z-index:1;">
                <div class="student-modal-name" id="smName"></div>
                <div class="student-modal-sid" id="smSid"><i class="fas fa-id-card"></i> <span></span></div>
                <div class="student-modal-info" id="smInfo"></div>
            </div>
            <div style="position:relative;z-index:1;display:flex;flex-direction:column;gap:10px;flex-shrink:0;">
                <button class="btn-submit" style="white-space:nowrap;" id="smAssignBtn">
                    <i class="fas fa-tasks"></i> Assign Task
                </button>
                <button onclick="" id="smRemoveBtn"
                    style="padding:10px 20px;border-radius:12px;background:linear-gradient(135deg,#dc2626,#f87171);color:#fff;border:none;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">
                    <i class="fas fa-user-minus"></i> Remove
                </button>
            </div>
        </div>
        <div class="modal-body">
            <div class="detail-grid" id="smDetailGrid"></div>
            <div style="font-size:15px;font-weight:700;color:var(--ink);margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-tasks" style="color:var(--gold);"></i> Assigned Tasks
                <span id="smTasksCount" style="background:var(--gold-pale);color:var(--gold-dark);border-radius:20px;padding:2px 10px;font-size:12px;font-weight:700;"></span>
            </div>
            <div class="modal-task-list" id="smTasksList">
                <div style="text-align:center;padding:24px;color:var(--muted);font-size:13px;">
                    <div class="spinner" style="margin:0 auto 10px;"></div> Loading tasks…
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: BULK ASSIGN ════════════════════════════════════════ -->
<div class="modal-overlay" id="bulkModal">
    <div class="modal-box">
        <div class="modal-hero">
            <div class="modal-hero-title"><i class="fas fa-tasks" style="color:var(--gold-light);margin-right:10px;"></i>Assign Task</div>
            <div class="modal-hero-sub" id="bulkModalSub">Assign to selected mentees</div>
        </div>
        <div class="modal-body">
            <form onsubmit="submitTask(event, 'bulk')">
                <div class="form-group">
                    <label class="form-label">Task Title *</label>
                    <input type="text" class="form-control" id="bulkTitle" placeholder="Enter task title…" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" id="bulkDesc" rows="3" placeholder="Optional description…" style="resize:vertical;"></textarea>
                </div>
                <div class="form-row">
                    <div>
                        <label class="form-label">Priority</label>
                        <select class="form-control" id="bulkPriority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="bulkDue">
                    </div>
                </div>
                <div style="background:var(--gold-pale);border:1px solid var(--gold-border);border-radius:12px;padding:14px;font-size:13px;color:var(--gold-dark);display:flex;gap:8px;align-items:center;">
                    <i class="fas fa-info-circle"></i>
                    Assigning to <strong id="bulkCount">0</strong> mentee(s)
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal('bulkModal')">Cancel</button>
                    <button type="submit" class="btn-submit" id="bulkSubmitBtn"><i class="fas fa-check"></i> Assign Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ MODAL: SINGLE ASSIGN ═════════════════════════════════════ -->
<div class="modal-overlay" id="singleModal">
    <div class="modal-box">
        <div class="modal-hero" style="background:linear-gradient(135deg,#0c1a2e,#1e3a5f);">
            <button class="modal-close-btn" onclick="closeModal('singleModal')"><i class="fas fa-times"></i></button>
            <div class="modal-hero-title"><i class="fas fa-user-check" style="color:#60a5fa;margin-right:10px;"></i>Assign Task</div>
            <div class="modal-hero-sub">To: <strong id="singleStudentLbl" style="color:#93c5fd;"></strong></div>
        </div>
        <div class="modal-body">
            <form onsubmit="submitTask(event, 'single')">
                <div class="form-group">
                    <label class="form-label">Task Title *</label>
                    <input type="text" class="form-control" id="singleTitle" placeholder="Enter task title…" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" id="singleDesc" rows="3" placeholder="Optional description…" style="resize:vertical;"></textarea>
                </div>
                <div class="form-row">
                    <div>
                        <label class="form-label">Priority</label>
                        <select class="form-control" id="singlePriority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="singleDue">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal('singleModal')">Cancel</button>
                    <button type="submit" class="btn-submit" id="singleSubmitBtn"><i class="fas fa-check"></i> Assign Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ TOASTS ════════════════════════════════════════════════════ -->
<div class="toasts" id="toastsContainer"></div>

<script src="../../../function/dashboard.js"></script>
<script>
/* ══════════════════════════════════
   DATA FROM PHP
══════════════════════════════════ */
const ALL_STUDENTS = <?php echo json_encode($students, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;

/* ══════════════════════════════════
   STATE
══════════════════════════════════ */
let currentView = 'grid';
let selectedMentees = new Set();
let currentSingleMenteeId = null;
let currentDetailMenteeId = null;
let allTasksData = [];
let currentTaskFilter = 'all';
let currentYearFilter = 'all';

/* ══════════════════════════════════
   TOAST
══════════════════════════════════ */
function toast(msg, type = 'info') {
    const c = document.getElementById('toastsContainer');
    const t = document.createElement('div');
    const icons = { success: 'check-circle', error: 'times-circle', info: 'info-circle' };
    t.className = `toast-item ${type}`;
    t.innerHTML = `<i class="fas fa-${icons[type]||'info-circle'}"></i><span>${msg}</span><button class="ti-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;
    c.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .4s'; setTimeout(() => t.remove(), 400); }, 4000);
}

/* ══════════════════════════════════
   MODAL HELPERS
══════════════════════════════════ */
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); }));

/* ══════════════════════════════════
   VIEW TOGGLE
══════════════════════════════════ */
function setView(v) {
    currentView = v;
    document.getElementById('gridView').style.display = v === 'grid' ? 'grid' : 'none';
    document.getElementById('listView').style.display = v === 'list' ? 'block' : 'none';
    document.getElementById('viewBtnGrid').classList.toggle('active', v === 'grid');
    document.getElementById('viewBtnList').classList.toggle('active', v === 'list');
    updateSel();
}

/* ══════════════════════════════════
   SEARCH / FILTER
══════════════════════════════════ */
function applyFilters() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const maj = document.getElementById('majorFilter').value.toLowerCase();

    document.querySelectorAll('.s-card').forEach(c => {
        const matches = (!q || c.dataset.name.includes(q) || c.dataset.email.includes(q) || c.dataset.sid.includes(q))
            && (!maj || c.dataset.major === maj)
            && (currentYearFilter === 'all' || c.dataset.year === currentYearFilter);
        c.style.display = matches ? '' : 'none';
    });
    document.querySelectorAll('.stu-row').forEach(r => {
        const matches = (!q || r.dataset.name.includes(q) || r.dataset.email.includes(q) || r.dataset.sid.includes(q))
            && (!maj || r.dataset.major === maj)
            && (currentYearFilter === 'all' || r.dataset.year === currentYearFilter);
        r.style.display = matches ? '' : 'none';
    });
    updateSel();
}

document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('majorFilter').addEventListener('change', applyFilters);

function filterYear(y) {
    currentYearFilter = y;
    document.querySelectorAll('.year-tab').forEach(t => t.classList.toggle('active', t.dataset.year === y));
    applyFilters();
}

/* ══════════════════════════════════
   SELECTION
══════════════════════════════════ */
function updateSel() {
    selectedMentees.clear();
    const gCbs = document.querySelectorAll('.stu-cb');
    const lCbs = document.querySelectorAll('.stu-cb-tbl');
    
    gCbs.forEach(cb => { const card = cb.closest('.s-card'); if (card && card.style.display !== 'none' && cb.checked) selectedMentees.add(cb.value); });
    lCbs.forEach(cb => { const row = cb.closest('.stu-row'); if (row && row.style.display !== 'none' && cb.checked) selectedMentees.add(cb.value); });

    const cnt = selectedMentees.size;
    document.getElementById('selCount').textContent = `${cnt} selected`;
    document.getElementById('assignBulkBtn').disabled = cnt === 0;

    const visG = [...gCbs].filter(cb => cb.closest('.s-card')?.style.display !== 'none');
    const visL = [...lCbs].filter(cb => cb.closest('.stu-row')?.style.display !== 'none');
    const visCbs = currentView === 'grid' ? visG : visL;
    const checkedVis = visCbs.filter(cb => cb.checked).length;
    document.getElementById('selectAllCb').checked = visCbs.length > 0 && checkedVis === visCbs.length;
    document.getElementById('selectAllCb').indeterminate = checkedVis > 0 && checkedVis < visCbs.length;
}

function toggleAll() {
    const v = document.getElementById('selectAllCb').checked;
    if (currentView === 'grid') {
        document.querySelectorAll('.s-card:not([style*="display: none"]) .stu-cb, .s-card:not([style*="none"]) .stu-cb').forEach(cb => {
            const card = cb.closest('.s-card');
            if (card && card.style.display !== 'none') cb.checked = v;
        });
    } else {
        document.querySelectorAll('.stu-row').forEach(r => {
            if (r.style.display !== 'none') { const cb = r.querySelector('.stu-cb-tbl'); if (cb) cb.checked = v; }
        });
    }
    updateSel();
}

/* ══════════════════════════════════
   STUDENT DETAIL MODAL
══════════════════════════════════ */
function viewStudent(menteeId) {
    currentDetailMenteeId = menteeId;
    const s = ALL_STUDENTS.find(x => x.mentee_id == menteeId);
    if (!s) return;
    const fullName = [s.first_name, s.middle_name, s.last_name].filter(Boolean).join(' ') + (s.suffix ? ' ' + s.suffix : '');
    const initials = (s.first_name?.[0]||'') + (s.last_name?.[0]||'') || '??';
    const grad = s.major_gradient_from ? `linear-gradient(135deg,${s.major_gradient_from},${s.major_gradient_to})` : 'linear-gradient(135deg,#d4a843,#8B6914)';

    document.getElementById('studentModalHero').style.background = `linear-gradient(135deg,${s.major_gradient_from||'#1a1209'},${s.major_gradient_to||'#3d2a0a'})`;
    document.getElementById('smAvatar').style.background = grad;
    document.getElementById('smAvatar').textContent = initials.toUpperCase();
    document.getElementById('smName').textContent = fullName;
    document.getElementById('smSid').querySelector('span').textContent = s.student_id || 'N/A';
    document.getElementById('smInfo').innerHTML = `
        <span><i class="fas fa-envelope"></i> ${esc(s.email||'N/A')}</span>
        <span><i class="fas fa-graduation-cap"></i> ${esc(s.major_name||'N/A')}</span>
        <span><i class="fas fa-layer-group"></i> ${esc(s.year_level||'N/A')}</span>`;

    document.getElementById('smAssignBtn').onclick = () => { closeModal('studentModal'); openSingleAssign(menteeId, fullName); };
    document.getElementById('smRemoveBtn').onclick = () => removeMentee(menteeId, fullName);

    document.getElementById('smDetailGrid').innerHTML = `
        <div class="detail-card"><div class="detail-card-label"><i class="fas fa-id-card"></i> Student ID</div><div class="detail-card-val">${esc(s.student_id||'N/A')}</div></div>
        <div class="detail-card"><div class="detail-card-label"><i class="fas fa-envelope"></i> Email</div><div class="detail-card-val" style="font-size:13px;">${esc(s.email||'N/A')}</div></div>
        <div class="detail-card"><div class="detail-card-label"><i class="fas fa-building"></i> Major</div><div class="detail-card-val">${esc(s.major_name||'N/A')}</div></div>
        <div class="detail-card"><div class="detail-card-label"><i class="fas fa-layer-group"></i> Year Level</div><div class="detail-card-val">${esc(s.year_level||'N/A')}</div></div>`;

    openModal('studentModal');
    loadStudentTasks(menteeId);
}

async function loadStudentTasks(menteeId) {
    const list = document.getElementById('smTasksList');
    const cnt = document.getElementById('smTasksCount');
    list.innerHTML = '<div style="text-align:center;padding:24px;"><div class="spinner" style="margin:0 auto;"></div></div>';
    try {
        const r = await fetch('../../../Door/data/get_mentee_tasks.php?mentee_id=' + menteeId);
        const d = await r.json();
        const tasks = d.success ? (d.tasks || []) : [];
        cnt.textContent = tasks.length;
        if (!tasks.length) {
            list.innerHTML = '<div style="text-align:center;padding:32px;color:var(--muted);font-size:13px;"><i class="fas fa-clipboard-list" style="font-size:32px;color:var(--gold-light);display:block;margin-bottom:10px;opacity:.6;"></i>No tasks assigned yet.</div>';
            return;
        }
        list.innerHTML = tasks.map(t => {
            const done = t.assignment_status === 'completed';
            const due = t.due_date ? new Date(t.due_date).toLocaleDateString('en-US', {month:'short',day:'numeric',year:'numeric'}) : 'No due date';
            const overdue = t.due_date && new Date(t.due_date) < new Date() && !done;
            return `<div class="modal-task-item ${done?'done':''}">
                <div class="modal-task-check ${done?'done':''}">${done?'<i class="fas fa-check"></i>':''}</div>
                <div class="modal-task-info">
                    <div class="modal-task-name ${done?'done':''}">${esc(t.title)}</div>
                    <div class="modal-task-sub">
                        <span class="priority-badge ${t.priority}">${t.priority}</span>
                        &nbsp;·&nbsp; <i class="fas fa-calendar" style="color:${overdue?'#ef4444':'var(--gold)'};"></i>
                        <span style="color:${overdue?'#ef4444':'inherit'};">${due}</span>
                    </div>
                </div>
                ${!done ? `<button class="btn-complete" onclick="markDone(${t.task_id},${menteeId})"><i class="fas fa-check"></i> Done</button>` : ''}
            </div>`;
        }).join('');
    } catch(e) {
        list.innerHTML = '<div style="text-align:center;padding:24px;color:#ef4444;font-size:13px;">Failed to load tasks.</div>';
    }
}

async function markDone(taskId, menteeId) {
    try {
        const r = await fetch('../../../Door/data/update_task_status.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ task_id: taskId, mentee_id: menteeId, status: 'completed' })
        });
        const d = await r.json();
        if (d.success) { toast('Task marked complete!', 'success'); loadStudentTasks(menteeId); loadAllTasks(); }
        else toast(d.message || 'Failed', 'error');
    } catch(e) { toast('Network error', 'error'); }
}

async function removeMentee(menteeId, name) {
    if (!confirm(`Remove "${name}" from your mentees? All their tasks will be deleted.`)) return;
    try {
        const r = await fetch('../../../Door/data/remove_mentee.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mentee_id: menteeId })
        });
        const d = await r.json();
        if (d.success) { toast('Mentee removed!', 'success'); closeModal('studentModal'); setTimeout(() => location.reload(), 1000); }
        else toast(d.message || 'Failed', 'error');
    } catch(e) { toast('Network error', 'error'); }
}

/* ══════════════════════════════════
   BULK ASSIGN
══════════════════════════════════ */
function openBulkAssign() {
    if (selectedMentees.size === 0) {
        // Assign to all visible if none selected
        const visCards = [...document.querySelectorAll('.s-card')].filter(c => c.style.display !== 'none');
        visCards.forEach(c => selectedMentees.add(c.dataset.mentee));
    }
    document.getElementById('bulkCount').textContent = selectedMentees.size;
    document.getElementById('bulkModalSub').textContent = `Assign to ${selectedMentees.size} selected mentee(s)`;
    document.getElementById('bulkTitle').value = '';
    document.getElementById('bulkDesc').value = '';
    document.getElementById('bulkPriority').value = 'medium';
    document.getElementById('bulkDue').value = '';
    openModal('bulkModal');
}

/* ══════════════════════════════════
   SINGLE ASSIGN
══════════════════════════════════ */
function openSingleAssign(menteeId, name) {
    currentSingleMenteeId = menteeId;
    document.getElementById('singleStudentLbl').textContent = name;
    document.getElementById('singleTitle').value = '';
    document.getElementById('singleDesc').value = '';
    document.getElementById('singlePriority').value = 'medium';
    document.getElementById('singleDue').value = '';
    openModal('singleModal');
}

/* ══════════════════════════════════
   SUBMIT TASK
══════════════════════════════════ */
async function submitTask(e, mode) {
    e.preventDefault();
    const isBulk = mode === 'bulk';
    const title = document.getElementById(isBulk ? 'bulkTitle' : 'singleTitle').value.trim();
    const desc  = document.getElementById(isBulk ? 'bulkDesc' : 'singleDesc').value.trim();
    const prio  = document.getElementById(isBulk ? 'bulkPriority' : 'singlePriority').value;
    const due   = document.getElementById(isBulk ? 'bulkDue' : 'singleDue').value || null;
    const ids   = isBulk ? [...selectedMentees] : [currentSingleMenteeId];
    const btn   = document.getElementById(isBulk ? 'bulkSubmitBtn' : 'singleSubmitBtn');

    if (!title || ids.length === 0) { toast('Please fill in required fields', 'error'); return; }
    btn.disabled = true; btn.innerHTML = '<div class="spinner" style="border-color:rgba(255,255,255,.3);border-top-color:#fff;display:inline-block;"></div> Assigning…';

    try {
        const r = await fetch('../../../Door/data/assign_task.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, description: desc, priority: prio, due_date: due, mentee_ids: ids })
        });
        const d = await r.json();
        if (d.success) {
            toast(d.message || 'Task assigned!', 'success');
            closeModal(isBulk ? 'bulkModal' : 'singleModal');
            if (currentDetailMenteeId && !isBulk) loadStudentTasks(currentDetailMenteeId);
            loadAllTasks();
            selectedMentees.clear();
            document.querySelectorAll('.stu-cb, .stu-cb-tbl').forEach(cb => cb.checked = false);
            updateSel();
        } else toast(d.message || 'Failed to assign', 'error');
    } catch(er) { toast('Network error', 'error'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Assign Task'; }
}

/* ══════════════════════════════════
   TASKS PANEL — LOAD ALL
══════════════════════════════════ */
async function loadAllTasks() {
    const list = document.getElementById('tasksList');
    try {
        const r = await fetch('../../../Door/data/get_instructor_tasks.php');
        const d = await r.json();
        allTasksData = d.success ? (d.tasks || []) : [];
        updateTaskStats();
        renderTasksList();
    } catch(e) {
        list.innerHTML = '<div style="text-align:center;padding:40px;color:var(--muted);font-size:13px;">Failed to load tasks.</div>';
    }
}

function updateTaskStats() {
    const total = allTasksData.length;
    const done  = allTasksData.filter(t => t.mentees?.every(m => m.assignment_status === 'completed')).length;
    const pend  = total - done;
    document.getElementById('statTotal').textContent = total;
    document.getElementById('statPending').textContent = pend;
    document.getElementById('statDone').textContent = done;
}

function filterTasks(tf) {
    currentTaskFilter = tf;
    document.querySelectorAll('.task-filter-chip').forEach(c => c.classList.toggle('active', c.dataset.tf === tf));
    renderTasksList();
}

function renderTasksList() {
    const list = document.getElementById('tasksList');
    const now = new Date();
    let tasks = allTasksData;

    if (currentTaskFilter === 'pending')   tasks = tasks.filter(t => !t.mentees?.every(m => m.assignment_status === 'completed'));
    if (currentTaskFilter === 'completed') tasks = tasks.filter(t =>  t.mentees?.every(m => m.assignment_status === 'completed'));
    if (currentTaskFilter === 'high')      tasks = tasks.filter(t => t.priority === 'high');
    if (currentTaskFilter === 'overdue')   tasks = tasks.filter(t => t.due_date && new Date(t.due_date) < now && !t.mentees?.every(m => m.assignment_status === 'completed'));

    if (!tasks.length) {
        list.innerHTML = `<div class="empty-state" style="padding:40px 20px;">
            <div class="empty-state-icon"><i class="fas fa-clipboard-list"></i></div>
            <h3>No Tasks</h3>
            <p style="font-size:13px;margin-top:4px;">No tasks match this filter.</p>
        </div>`;
        return;
    }

    list.innerHTML = tasks.map(t => {
        const totalM = t.mentees?.length || 0;
        const doneM  = t.mentees?.filter(m => m.assignment_status === 'completed').length || 0;
        const pct = totalM ? Math.round((doneM/totalM)*100) : 0;
        const allDone = pct === 100;
        const due = t.due_date ? new Date(t.due_date).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : null;
        const overdue = t.due_date && new Date(t.due_date) < new Date() && !allDone;

        return `<div class="task-card priority-${t.priority} ${allDone?'completed':''}">
            <div class="task-card-top">
                <div class="task-card-title ${allDone?'done':''}">${esc(t.title)}</div>
                <div class="task-status-dot ${allDone?'completed':'pending'}"></div>
            </div>
            <div class="task-card-meta">
                <span><span class="priority-badge ${t.priority}">${t.priority}</span></span>
                ${due ? `<span><i class="fas fa-calendar" style="color:${overdue?'#ef4444':'var(--gold)'};"></i><span style="color:${overdue?'#ef4444':'inherit'};">${overdue?'Overdue: ':''}${due}</span></span>` : ''}
                <span><i class="fas fa-users"></i> ${totalM} student${totalM!==1?'s':''}</span>
            </div>
            ${t.description ? `<div style="font-size:11px;color:var(--muted);margin-bottom:8px;line-height:1.5;">${esc(t.description.length>80?t.description.slice(0,80)+'…':t.description)}</div>` : ''}
            <div class="task-mentees">
                ${pct === 100 ? `<span class="task-mentee-chip" style="background:#dcfce7;border:1px solid #22c55e;"><span style="color:#22c55e;">✓ All Done</span></span>` : ''}
                ${(t.mentees||[]).slice(0,4).map(m => `<span class="task-mentee-chip"><span class="chip-dot" style="background:${m.assignment_status==='completed'?'#22c55e':'var(--gold)'}"></span>${esc(m.first_name||'')}</span>`).join('')}
                ${totalM > 4 ? `<span class="task-mentee-chip">+${totalM-4} more</span>` : ''}
            </div>
            ${totalM > 1 ? `<div class="task-progress">
                <div style="display:flex;justify-content:space-between;font-size:10px;color:${pct===100?'#22c55e':'var(--muted)'};font-weight:${pct===100?'600':'400'};">
                    <span>${doneM}/${totalM} completed</span><span>${pct === 100 ? '🎉' : pct + '%'}</span>
                </div>
                <div class="task-progress-bar"><div class="task-progress-fill" style="width:${pct}%;background:${pct===100?'linear-gradient(90deg, #22c55e, #4ade80)':'linear-gradient(to right, var(--gold), var(--gold-light))'}"></div></div>
            </div>` : ''}
        </div>`;
    }).join('');
}

/* ══════════════════════════════════
   HELPERS
══════════════════════════════════ */
function esc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ══════════════════════════════════
   INIT
══════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    filterYear('all');
    loadAllTasks();
});

<?php if ($show_role_modal): ?>
window.addEventListener('DOMContentLoaded', () => {
    toast('Access restricted. Redirecting…', 'error');
    setTimeout(() => window.location.href = '../../../Door/login.php', 2000);
});
<?php endif; ?>
</script>
</body>
</html>
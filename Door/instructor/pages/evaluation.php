<?php
// evaluation.php — Instructor Panel
// Place at: views/instructor/pages/evaluation.php

require_once '../../../data/session_security.php';
$role_access = check_role_access('instructor');
$show_role_modal = !$role_access['allowed'];

$instructor_id = $_SESSION['user_id'] ?? 1;
$user_name = $_SESSION['user_name'] ?? 'Instructor';

if (!$show_role_modal) {
    require_once '../../../data/config.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="../../../media/LOGO.jpg" type="image/jpeg">
<title>Evaluation — Instructor Panel</title>
<link rel="stylesheet" href="../../../css/common.css">
<link rel="stylesheet" href="../style/dashboard.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root {
    --gold:#B8860B; --gold-light:#D4A843; --gold-dark:#8B6914;
    --cream:#f7f5ef; --white:#ffffff; --dark:#1f1f1f; --muted:#666;
    --border:#d4cfc5; --danger:#ef4444; --success:#22c55e; --warn:#f59e0b;
    --info:#3b82f6;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;background:var(--cream);overflow-x:hidden;}
.page-wrap{padding:24px;}
.card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.08);border:1px solid var(--border);margin-bottom:20px;}

/* ─── Mentee list ─────────────────────────────────────── */
.mentee-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;}
.mentee-card{background:#fff;border-radius:14px;border:1px solid var(--border);padding:18px;cursor:pointer;transition:all .2s;position:relative;}
.mentee-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.12);}
.mentee-avatar{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;flex-shrink:0;}
.mentee-name{font-size:15px;font-weight:700;color:var(--dark);}
.mentee-sub{font-size:12px;color:var(--muted);margin-top:2px;}
.mentee-badges{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px;}
.badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-gold{background:#fef3c7;color:#92400e;border:1px solid #fbbf24;}
.badge-blue{background:#dbeafe;color:#1e40af;border:1px solid #93c5fd;}
.badge-green{background:#dcfce7;color:#166534;border:1px solid #86efac;}
.badge-red{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.badge-gray{background:var(--cream);color:var(--muted);border:1px solid var(--border);}
.btn{padding:10px 18px;border:none;border-radius:10px;cursor:pointer;font-weight:600;font-size:13px;font-family:'Poppins',sans-serif;display:inline-flex;align-items:center;gap:7px;transition:all .2s;}
.btn-gold{background:linear-gradient(135deg,var(--gold-light),var(--gold-dark));color:#fff;}
.btn-gold:hover{box-shadow:0 4px 12px rgba(184,134,11,.35);}
.btn-outline{background:#fff;color:var(--dark);border:1.5px solid var(--border);}
.btn-outline:hover{background:var(--cream);}
.btn-danger{background:#ef4444;color:#fff;}
.btn-green{background:#22c55e;color:#fff;}

/* ─── Evaluation modal (full-screen overlay) ──────────── */
.eval-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9900;display:none;align-items:flex-start;justify-content:center;overflow-y:auto;padding:20px;}
.eval-overlay.open{display:flex;}
.eval-panel{background:#fff;border-radius:20px;width:100%;max-width:1100px;min-height:90vh;box-shadow:0 24px 80px rgba(0,0,0,.35);display:flex;flex-direction:column;}
.eval-header{background:linear-gradient(135deg,var(--gold-dark),var(--gold-light));border-radius:20px 20px 0 0;padding:20px 28px;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.eval-header-title{font-size:20px;font-weight:700;}
.eval-header-sub{font-size:13px;opacity:.85;margin-top:3px;}
.eval-close{width:36px;height:36px;border:none;background:rgba(255,255,255,.2);border-radius:10px;cursor:pointer;font-size:18px;color:#fff;display:flex;align-items:center;justify-content:center;}
.eval-close:hover{background:rgba(255,255,255,.35);}
.eval-body{padding:24px 28px;flex:1;}

/* ─── Student info bar ─────────────────────────────────── */
.student-bar{display:flex;align-items:center;gap:16px;background:var(--cream);border-radius:14px;padding:16px 20px;margin-bottom:20px;flex-wrap:wrap;border:1px solid var(--border);}
.student-avatar-lg{width:60px;height:60px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:#fff;flex-shrink:0;}
.student-info-name{font-size:18px;font-weight:700;color:var(--dark);}
.student-info-sub{font-size:13px;color:var(--muted);margin-top:2px;}
.stat-chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;}
.stat-chip{padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;}

/* ─── GWA display ──────────────────────────────────────── */
.gwa-bar{display:flex;gap:12px;margin-bottom:18px;flex-wrap:wrap;}
.gwa-card{background:linear-gradient(135deg,var(--gold-dark),var(--gold-light));border-radius:12px;padding:14px 20px;color:#fff;text-align:center;min-width:110px;}
.gwa-value{font-size:26px;font-weight:800;}
.gwa-label{font-size:11px;opacity:.85;margin-top:2px;}
.gwa-stat{background:var(--cream);border-radius:12px;padding:14px 20px;text-align:center;border:1px solid var(--border);min-width:110px;}
.gwa-stat-value{font-size:22px;font-weight:700;color:var(--dark);}
.gwa-stat-label{font-size:11px;color:var(--muted);margin-top:2px;}

/* ─── Prospectus table ─────────────────────────────────── */
.pro-section{margin-bottom:20px;}
.year-band{background:linear-gradient(135deg,var(--gold-dark),var(--gold-light));color:#fff;padding:10px 16px;border-radius:10px 10px 0 0;font-size:14px;font-weight:700;display:flex;align-items:center;justify-content:space-between;}
.sem-wrap{border:1px solid var(--border);border-top:none;border-radius:0 0 10px 10px;overflow:hidden;}
.sem-header{background:#f0ece0;padding:8px 16px;font-size:12px;font-weight:700;color:var(--gold-dark);text-transform:uppercase;letter-spacing:.4px;}
.pro-table{width:100%;border-collapse:collapse;font-size:13px;}
.pro-table th{background:#f7f5ef;padding:9px 12px;text-align:left;font-size:11px;font-weight:700;color:var(--gold-dark);border-bottom:1px solid var(--border);}
.pro-table td{padding:9px 12px;border-bottom:1px solid #f0ece4;vertical-align:middle;}
.pro-table tr:last-child td{border-bottom:none;}
.pro-table tr:hover td{background:#fdfbf6;}
.grade-input-wrap{display:flex;align-items:center;gap:6px;}
.grade-input{width:72px;padding:6px 8px;border:1.5px solid var(--border);border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;text-align:center;transition:all .2s;}
.grade-input:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px rgba(184,134,11,.12);}
.grade-input.graded-pass{border-color:var(--success);background:#f0fdf4;}
.grade-input.graded-fail{border-color:var(--danger);background:#fef2f2;}
.grade-input.graded-cond{border-color:var(--warn);background:#fffbeb;}
.save-grade-btn{width:28px;height:28px;border:none;border-radius:7px;cursor:pointer;background:#dbeafe;color:#1d4ed8;font-size:12px;display:flex;align-items:center;justify-content:center;transition:all .2s;flex-shrink:0;}
.save-grade-btn:hover{background:#1d4ed8;color:#fff;}
.save-grade-btn.saving{background:#fef3c7;color:var(--gold-dark);}
.save-grade-btn.saved{background:#dcfce7;color:#166534;}
.grade-status-pill{padding:3px 8px;border-radius:12px;font-size:10px;font-weight:700;white-space:nowrap;}
.pill-pass{background:#dcfce7;color:#166534;}
.pill-fail{background:#fee2e2;color:#991b1b;}
.pill-cond{background:#fef3c7;color:#92400e;}
.pill-none{background:var(--cream);color:var(--muted);}
.grade-label{font-size:10px;color:var(--muted);margin-top:1px;}
.prereq-flag{color:#ef4444;font-size:10px;margin-left:4px;}

/* ─── Tabs ─────────────────────────────────────────────── */
.eval-tabs{display:flex;gap:4px;background:var(--cream);padding:4px;border-radius:12px;margin-bottom:20px;}
.eval-tab{flex:1;padding:9px 14px;border:none;background:transparent;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;color:var(--muted);transition:all .2s;font-family:'Poppins',sans-serif;display:flex;align-items:center;justify-content:center;gap:7px;}
.eval-tab.active{background:#fff;color:var(--dark);box-shadow:0 2px 6px rgba(0,0,0,.1);font-weight:600;}

/* ─── Advisement ────────────────────────────────────────── */
.adv-section{margin-bottom:18px;}
.adv-section-title{font-size:13px;font-weight:700;margin-bottom:10px;display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:8px;}
.adv-title-rec{background:#dcfce7;color:#166534;}
.adv-title-retake{background:#fee2e2;color:#991b1b;}
.adv-title-cond{background:#fef3c7;color:#92400e;}
.adv-title-block{background:#f1f5f9;color:#475569;}
.adv-title-done{background:#dbeafe;color:#1e40af;}
.adv-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;}
.adv-card{border-radius:10px;padding:12px;border:1px solid var(--border);background:#fff;}
.adv-code{font-size:12px;font-weight:700;color:var(--dark);}
.adv-name{font-size:11px;color:var(--muted);margin-top:2px;}
.adv-reason{font-size:10px;margin-top:6px;padding:4px 8px;border-radius:6px;}
.adv-reason-rec{background:#dcfce7;color:#166534;}
.adv-reason-block{background:#fee2e2;color:#991b1b;}
.adv-reason-retake{background:#fef3c7;color:#92400e;}

/* ─── Finalize ─────────────────────────────────────────── */
.finalize-box{background:var(--cream);border-radius:14px;padding:20px;border:1px solid var(--border);}
.finalize-gwa{font-size:36px;font-weight:800;color:var(--gold-dark);}

/* ─── Loading ──────────────────────────────────────────── */
.spinner{display:inline-block;width:24px;height:24px;border:3px solid rgba(184,134,11,.2);border-top-color:var(--gold-dark);border-radius:50%;animation:spin .7s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}

/* ─── Toast ─────────────────────────────────────────────── */
.toast{position:fixed;bottom:24px;right:24px;background:#1f1f1f;color:#fff;padding:13px 18px;border-radius:12px;font-size:13px;font-weight:500;display:flex;align-items:center;gap:9px;transform:translateY(100px);opacity:0;transition:all .3s;z-index:99999;box-shadow:0 8px 24px rgba(0,0,0,.3);max-width:340px;}
.toast.show{transform:translateY(0);opacity:1;}
.toast.success{border-left:4px solid #22c55e;}
.toast.error{border-left:4px solid #ef4444;}
.toast.info{border-left:4px solid var(--gold);}

/* ─── Search ─────────────────────────────────────────────── */
.search-wrap{display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--cream);border-radius:10px;border:1.5px solid var(--border);transition:all .2s;max-width:320px;}
.search-wrap:focus-within{border-color:var(--gold);}
.search-wrap i{color:var(--muted);font-size:13px;}
.search-wrap input{border:none;background:transparent;font-family:'Poppins',sans-serif;font-size:13px;color:var(--dark);flex:1;outline:none;}

/* ─── Empty ─────────────────────────────────────────────── */
.empty{text-align:center;padding:50px 20px;color:var(--muted);}
.empty i{font-size:52px;opacity:.2;display:block;margin-bottom:14px;}

/* Academic year select */
.ay-select{padding:8px 12px;border:1.5px solid var(--border);border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;color:var(--dark);background:#fff;}

/* remarks textarea */
.remarks-input{width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:8px;font-family:'Poppins',sans-serif;font-size:12px;resize:none;min-height:36px;transition:all .2s;}
.remarks-input:focus{outline:none;border-color:var(--gold);}
</style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════════════ SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="../../../media/LOGO.jpg" alt="Logo" class="sidebar-logo"
             style="width:70px;height:70px;border-radius:16px;object-fit:cover;border:3px solid white;background:white;padding:4px;box-shadow:0 4px 12px rgba(0,0,0,.2);">
        <div class="sidebar-brand"><span class="sidebar-brand-name">IBM</span></div>
    </div>
    <div class="sidebar-user">
        <div class="sidebar-avatar"><i class="fas fa-user"></i></div>
        <div class="sidebar-user-info">
            <span class="sidebar-user-name"><?= htmlspecialchars($user_name) ?></span>
            <span class="sidebar-user-role">Instructor</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-nav-label">Menu</div>
        <a href="../dashboard.php" class="sidebar-nav-item"><i class="fas fa-chart-pie"></i><span>Overview</span></a>
        <a href="students.php" class="sidebar-nav-item"><i class="fas fa-user-graduate"></i><span>Students mentees</span></a>
        <a href="evaluation.php" class="sidebar-nav-item active"><i class="fas fa-comment-dots"></i><span>Evaluation</span></a>
        <a href="reports.php" class="sidebar-nav-item"><i class="fas fa-file-alt"></i><span>Reports</span></a>
        <a href="profile.php" class="sidebar-nav-item"><i class="fas fa-user"></i><span>Profile</span></a>
    </nav>
</aside>

<!-- ═══════════════════════════════════════════════════════════════════ MAIN -->
<div class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <button class="topbar-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <div>
                <div class="topbar-title">Student Evaluation</div>
                <div class="topbar-subtitle">Instructor Panel</div>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-date"><i class="fas fa-calendar-alt"></i><span><?= date('F j, Y') ?></span></div>
            <a href="../../../data/logout.php" class="topbar-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </header>

    <main class="dashboard-content">
        <div class="page-wrap">

            <!-- Page header -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                <div>
                    <h1 style="font-size:22px;font-weight:700;color:var(--dark);">My Mentees</h1>
                    <p style="font-size:13px;color:var(--muted);margin-top:3px;">Select a student to open their prospectus evaluation</p>
                </div>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="menteeSearch" placeholder="Search mentees..." oninput="filterMentees()">
                    </div>
                    <select class="ay-select" id="academicYearSelect">
                        <option value="2025-2026">A.Y. 2025-2026</option>
                        <option value="2024-2025">A.Y. 2024-2025</option>
                        <option value="2026-2027">A.Y. 2026-2027</option>
                    </select>
                </div>
            </div>

            <!-- Mentees container -->
            <div class="card">
                <div id="menteesContainer">
                    <div class="empty"><i class="fas fa-spinner fa-spin"></i><p style="margin-top:10px;">Loading mentees…</p></div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- ═══════════════════════════════════════════════════════ EVALUATION OVERLAY -->
<div class="eval-overlay" id="evalOverlay">
    <div class="eval-panel">

        <!-- Header -->
        <div class="eval-header" id="evalPanelHeader">
            <div>
                <div class="eval-header-title" id="evalHeaderTitle">Student Evaluation</div>
                <div class="eval-header-sub" id="evalHeaderSub"></div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <button class="btn btn-outline" style="background:rgba(255,255,255,.2);border-color:rgba(255,255,255,.4);color:#fff;" onclick="showAdvisement()" id="advisementBtn">
                    <i class="fas fa-lightbulb"></i> Advisement
                </button>
                <button class="btn btn-outline" style="background:rgba(255,255,255,.2);border-color:rgba(255,255,255,.4);color:#fff;" onclick="finalizeEval()" id="finalizeBtn">
                    <i class="fas fa-check-circle"></i> Finalize
                </button>
                <button class="eval-close" onclick="closeEval()"><i class="fas fa-times"></i></button>
            </div>
        </div>

        <!-- Body -->
        <div class="eval-body" id="evalBody">
            <div class="empty"><div class="spinner"></div><p style="margin-top:14px;">Loading evaluation data…</p></div>
        </div>

    </div>
</div>

<!-- ═══════════════════════════════════════════════════════ ADVISEMENT OVERLAY -->
<div class="eval-overlay" id="advOverlay">
    <div class="eval-panel" style="max-width:820px;min-height:auto;">
        <div class="eval-header" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);">
            <div>
                <div class="eval-header-title"><i class="fas fa-lightbulb" style="margin-right:8px;"></i>Subject Advisement</div>
                <div class="eval-header-sub" id="advHeaderSub">Available subjects for next enrollment</div>
            </div>
            <button class="eval-close" onclick="closeAdv()"><i class="fas fa-times"></i></button>
        </div>
        <div class="eval-body" id="advBody">
            <div class="empty"><div class="spinner"></div></div>
        </div>
    </div>
</div>

<div class="toast" id="toast"><span id="toastMsg"></span></div>

<!-- ═══════════════════════════════════════════════════════ SCRIPTS -->
<script src="../../../function/dashboard.js"></script>
<script>
/* ═══ STATE ═══════════════════════════════════════════════════════════════ */
let currentStudent = null;
let currentSubjects = [];
let dirtyGrades = {};   // subject_id → {grade, remarks}

const EVAL_PROCESS = '../../../data/evaluation_process.php';

/* ═══ GRADE HELPERS ═══════════════════════════════════════════════════════ */
const VALID_GRADES = [1.00,1.25,1.50,1.75,2.00,2.25,2.50,2.75,3.00,4.00,5.00];
const GRADE_LABELS = {
    1.00:'Excellent',1.25:'Very Good',1.50:'Very Good',1.75:'Good',
    2.00:'Satisfactory',2.25:'Fair',2.50:'Passing',2.75:'Low Passing',
    3.00:'Barely Passing',4.00:'Conditional',5.00:'Failed'
};

function roundGrade(raw) {
    let closest = 5.00, minDiff = 99;
    VALID_GRADES.forEach(v => { const d = Math.abs(raw - v); if(d < minDiff){minDiff=d;closest=v;} });
    return closest;
}
function gradeStatus(g) {
    if (g <= 3.00) return 'passed';
    if (g === 4.00) return 'conditional';
    return 'failed';
}
function gradeLabel(g) { return GRADE_LABELS[g] || '—'; }
function gradeClass(status) {
    return status==='passed'?'graded-pass':status==='failed'?'graded-fail':status==='conditional'?'graded-cond':'';
}
function pillClass(status) {
    return status==='passed'?'pill-pass':status==='failed'?'pill-fail':status==='conditional'?'pill-cond':'pill-none';
}

/* ═══ TOAST ═══════════════════════════════════════════════════════════════ */
function toast(msg, type='info', dur=3000) {
    const el = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    el.className = `toast ${type} show`;
    clearTimeout(el._t); el._t = setTimeout(()=>el.classList.remove('show'),dur);
}

/* ═══ ESCAPE ═════════════════════════════════════════════════════════════ */
function esc(s){if(!s)return'';return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

/* ═══ LOAD MENTEES ════════════════════════════════════════════════════════ */
function loadMentees() {
    const fd = new FormData(); fd.append('action','get_mentees');
    fetch(EVAL_PROCESS,{method:'POST',body:fd})
    .then(r=>r.json()).then(d=>{
        const c = document.getElementById('menteesContainer');
        if(!d.success||!d.mentees||!d.mentees.length){
            c.innerHTML=`<div class="empty"><i class="fas fa-users"></i><p>No mentees assigned to you yet.</p></div>`;return;
        }
        let html=`<div class="mentee-grid" id="menteeGrid">`;
        d.mentees.forEach(m=>{
            const fullName=`${m.first_name} ${m.middle_name?m.middle_name+' ':''}${m.last_name}${m.suffix?' '+m.suffix:''}`;
            const initials = m.avatar_initials || (m.first_name[0]+(m.last_name[0]||'')).toUpperCase();
            const gradePct = m.total_subjects>0?Math.round(m.graded_count/m.total_subjects*100):0;
            html+=`<div class="mentee-card" onclick="openEval(${JSON.stringify(m).replace(/"/g,'&quot;')})" data-name="${esc(fullName)}">
                <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px;">
                    <div class="mentee-avatar" style="background:linear-gradient(135deg,${esc(m.avatar_gradient_from||'#3b82f6')},${esc(m.avatar_gradient_to||'#60a5fa')});">${esc(initials)}</div>
                    <div>
                        <div class="mentee-name">${esc(fullName)}</div>
                        <div class="mentee-sub">${esc(m.student_number||'—')} &nbsp;·&nbsp; ${esc(m.major_name||'No major')}</div>
                    </div>
                </div>
                <div class="mentee-badges">
                    <span class="badge badge-blue"><i class="fas fa-layer-group" style="font-size:10px;"></i> ${esc(m.year_level||'—')}</span>
                    ${m.major_name?`<span class="badge badge-gold">${esc(m.major_name)}</span>`:''}
                    <span class="badge ${m.graded_count>0?'badge-green':'badge-gray'}">
                        <i class="fas fa-star" style="font-size:10px;"></i> ${m.graded_count}/${m.total_subjects} graded
                    </span>
                </div>
                <div style="margin-top:10px;background:var(--cream);border-radius:20px;height:6px;overflow:hidden;">
                    <div style="height:100%;width:${gradePct}%;background:linear-gradient(to right,var(--gold-light),var(--gold-dark));border-radius:20px;transition:width .4s;"></div>
                </div>
                <div style="font-size:11px;color:var(--muted);margin-top:4px;text-align:right;">${gradePct}% evaluated</div>
                <div style="margin-top:10px;text-align:right;">
                    <span style="font-size:12px;font-weight:600;color:var(--gold-dark);"><i class="fas fa-arrow-right" style="font-size:10px;"></i> Click to evaluate</span>
                </div>
            </div>`;
        });
        html+=`</div>`;
        c.innerHTML=html;
    });
}
loadMentees();

/* ═══ FILTER MENTEES ══════════════════════════════════════════════════════ */
function filterMentees(){
    const q=document.getElementById('menteeSearch').value.toLowerCase();
    document.querySelectorAll('.mentee-card').forEach(c=>{
        c.style.display=c.dataset.name.toLowerCase().includes(q)?'':'none';
    });
}

/* ═══ OPEN EVALUATION ═════════════════════════════════════════════════════ */
function openEval(mentee) {
    if (typeof mentee === 'string') mentee = JSON.parse(mentee);
    currentStudent = mentee;
    dirtyGrades = {};
    document.getElementById('evalOverlay').classList.add('open');
    document.getElementById('evalBody').innerHTML=`<div class="empty"><div class="spinner"></div><p style="margin-top:14px;">Loading evaluation…</p></div>`;
    const fullName = `${mentee.first_name} ${mentee.middle_name?mentee.middle_name+' ':''}${mentee.last_name}${mentee.suffix?' '+mentee.suffix:''}`;
    document.getElementById('evalHeaderTitle').textContent = fullName;
    document.getElementById('evalHeaderSub').textContent = `${mentee.major_name||'No major'} · ${mentee.year_level||'—'} · ${document.getElementById('academicYearSelect').value}`;
    fetchEvalData(mentee.id);
}
function closeEval(){document.getElementById('evalOverlay').classList.remove('open');}
function closeAdv(){document.getElementById('advOverlay').classList.remove('open');}

/* ═══ FETCH EVALUATION DATA ═══════════════════════════════════════════════ */
function fetchEvalData(studentId){
    const fd=new FormData();
    fd.append('action','get_student_evaluation');
    fd.append('student_id',studentId);
    fd.append('academic_year',document.getElementById('academicYearSelect').value);
    fetch(EVAL_PROCESS,{method:'POST',body:fd})
    .then(r=>r.json()).then(d=>{
        if(!d.success){document.getElementById('evalBody').innerHTML=`<div class="empty"><i class="fas fa-exclamation-triangle"></i><p>${esc(d.message)}</p></div>`;return;}
        currentSubjects=d.subjects||[];
        renderEvalBody(d);
    });
}

/* ═══ RENDER EVALUATION BODY ══════════════════════════════════════════════ */
function renderEvalBody(data){
    const student=data.student;
    const subjects=data.subjects||[];
    const gwa=data.gwa_data||{};
    const ay=data.academic_year||'2025-2026';

    const fullName=`${student.first_name} ${student.middle_name?student.middle_name+' ':''}${student.last_name}${student.suffix?' '+student.suffix:''}`;
    const initials=(student.avatar_initials||(student.first_name[0]+(student.last_name[0]||'')).toUpperCase());

    let html=`
    <!-- Student bar -->
    <div class="student-bar">
        <div class="student-avatar-lg" style="background:linear-gradient(135deg,${esc(student.avatar_gradient_from||'#3b82f6')},${esc(student.avatar_gradient_to||'#60a5fa')});">${esc(initials)}</div>
        <div style="flex:1;">
            <div class="student-info-name">${esc(fullName)}</div>
            <div class="student-info-sub">${esc(student.email||'')} &nbsp;·&nbsp; ID: ${esc(student.student_id||'—')}</div>
            <div class="stat-chips">
                <span class="stat-chip badge-blue">Year: ${esc(student.year_level||'—')}</span>
                <span class="stat-chip badge-gold">Major: ${esc(student.major_name||'—')}</span>
                <span class="stat-chip badge-gray">A.Y. ${esc(ay)}</span>
            </div>
        </div>
        <div class="gwa-bar">
            <div class="gwa-card">
                <div class="gwa-value" id="liveGWA">${gwa.gwa!==null&&gwa.gwa!==undefined?parseFloat(gwa.gwa).toFixed(2):'—'}</div>
                <div class="gwa-label">Current GWA</div>
            </div>
            <div class="gwa-stat">
                <div class="gwa-stat-value" id="liveUnitsTaken">${gwa.total_units||0}</div>
                <div class="gwa-stat-label">Units Taken</div>
            </div>
            <div class="gwa-stat">
                <div class="gwa-stat-value" id="liveUnitsPassed">${gwa.units_passed||0}</div>
                <div class="gwa-stat-label">Units Passed</div>
            </div>
        </div>
    </div>`;

    // Prospectus by year & semester
    const yearOrder=['1st Year','2nd Year','3rd Year','4th Year'];
    const byYear={};
    subjects.forEach(s=>{
        const y=s.year_level||'1st Year';
        if(!byYear[y])byYear[y]=[];
        byYear[y].push(s);
    });
    const bridging=subjects.filter(s=>s.year_level==='Bridging');

    html+=`<div style="font-size:15px;font-weight:700;color:var(--dark);margin-bottom:14px;"><i class="fas fa-scroll" style="color:var(--gold-dark);margin-right:8px;"></i>Grade Prospectus</div>`;

    yearOrder.forEach(year=>{
        const all=byYear[year]||[];
        if(!all.length)return;
        const sem1=all.filter(s=>!s.semester||s.semester.includes('1st'));
        const sem2=all.filter(s=>s.semester&&s.semester.includes('2nd'));
        const yearTotal=all.reduce((a,s)=>a+(parseFloat(s.units)||0),0);

        html+=`<div class="pro-section">
            <div class="year-band">
                <span><i class="fas fa-calendar-alt" style="margin-right:8px;font-size:12px;"></i>${year}</span>
                <span style="font-weight:400;font-size:12px;">${yearTotal%1===0?yearTotal:yearTotal.toFixed(1)} total units</span>
            </div>
            <div class="sem-wrap">`;

        if(sem1.length){
            html+=`<div class="sem-header">1st Semester</div>`;
            html+=buildSemTable(sem1,student.id,student.major_id,ay);
        }
        if(sem2.length){
            html+=`<div class="sem-header" style="border-top:1px solid var(--border);">2nd Semester</div>`;
            html+=buildSemTable(sem2,student.id,student.major_id,ay);
        }
        html+=`</div></div>`;
    });

    if(bridging.length){
        html+=`<div class="pro-section">
            <div class="year-band" style="background:linear-gradient(135deg,#7c3aed,#8b5cf6);">
                <span><i class="fas fa-exchange-alt" style="margin-right:8px;font-size:12px;"></i>Bridging Subjects</span>
            </div>
            <div class="sem-wrap">${buildSemTable(bridging,student.id,student.major_id,ay)}</div>
        </div>`;
    }

    if(!subjects.length){
        html+=`<div class="empty"><i class="fas fa-book"></i><p>No subjects in prospectus for this major yet. Please add subjects in Department Management.</p></div>`;
    }

    // Finalize notes area
    html+=`<div class="finalize-box" style="margin-top:20px;">
        <div style="font-size:14px;font-weight:700;color:var(--dark);margin-bottom:10px;"><i class="fas fa-clipboard" style="color:var(--gold-dark);margin-right:8px;"></i>Session Notes</div>
        <textarea class="remarks-input" id="sessionNotes" style="min-height:60px;font-size:13px;" placeholder="Optional notes about this evaluation session…"></textarea>
        <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap;">
            <button class="btn btn-gold" onclick="showAdvisement()"><i class="fas fa-lightbulb"></i> Generate Advisement</button>
            <button class="btn btn-green" onclick="finalizeEval()"><i class="fas fa-check-circle"></i> Finalize Evaluation</button>
        </div>
    </div>`;

    document.getElementById('evalBody').innerHTML=html;
}

/* ═══ BUILD SEMESTER TABLE ════════════════════════════════════════════════ */
function buildSemTable(subjects, studentId, majorId, ay){
    if(!subjects.length) return '<p style="padding:12px 16px;font-size:13px;color:var(--muted);">No subjects this semester.</p>';
    let html=`<table class="pro-table">
        <thead><tr>
            <th style="width:28px;">#</th>
            <th style="width:90px;">Code</th>
            <th>Subject Title</th>
            <th style="width:50px;">Units</th>
            <th style="width:55px;">Pre-Req</th>
            <th style="width:150px;">Grade Input</th>
            <th style="width:110px;">Status</th>
            <th>Remarks</th>
        </tr></thead><tbody>`;
    subjects.forEach((s,i)=>{
        const raw=s.grade_rounded;
        const status=s.grade_status||'not_taken';
        const inputClass=raw?gradeClass(status):'';
        const pillCls=pillClass(status);
        const prereqCode=s.prerequisite||'';
        html+=`<tr id="row-${s.id}">
            <td style="color:var(--muted);font-size:11px;">${i+1}</td>
            <td><span style="font-weight:700;font-size:12px;">${esc(s.subject_code)}</span>${s.is_prerequisite?`<span class="prereq-flag" title="Prerequisite">★</span>`:''}</td>
            <td style="font-size:13px;">${esc(s.subject_name)}</td>
            <td style="text-align:center;font-weight:600;">${parseFloat(s.units)||0}</td>
            <td style="font-size:11px;color:${prereqCode?'#dc2626':'var(--muted)'};">${prereqCode||'—'}</td>
            <td>
                <div class="grade-input-wrap">
                    <input type="number" class="grade-input ${inputClass}" id="g-${s.id}"
                        value="${raw||''}" min="1" max="5" step="0.01"
                        placeholder="e.g. 1.5"
                        onchange="onGradeChange(${s.id},${studentId},${majorId},'${esc(s.semester)}','${esc(s.year_level)}','${esc(ay)}')"
                        title="Enter grade (1.00–5.00)">
                    <button class="save-grade-btn" id="sbtn-${s.id}"
                        onclick="saveGrade(${s.id},${studentId},${majorId},'${esc(s.semester)}','${esc(s.year_level)}','${esc(ay)}')"
                        title="Save grade">
                        <i class="fas fa-save"></i>
                    </button>
                </div>
                <div class="grade-label" id="gl-${s.id}">${s.grade_label||''}</div>
            </td>
            <td>
                <span class="grade-status-pill ${pillCls}" id="pill-${s.id}">
                    ${statusText(status)}
                </span>
                ${raw?`<div style="font-size:10px;color:var(--muted);margin-top:2px;">${raw.toFixed?raw.toFixed(2):raw}</div>`:''}
            </td>
            <td>
                <input type="text" class="grade-input" style="width:100%;font-size:11px;font-weight:400;text-align:left;" 
                    id="rem-${s.id}" value="${esc(s.remarks||'')}" placeholder="Optional remark…"
                    onchange="markDirty(${s.id})">
            </td>
        </tr>`;
    });
    html+=`</tbody></table>`;
    return html;
}

function statusText(s){
    const m={passed:'Passed',failed:'Failed',conditional:'Conditional',not_taken:'Not Graded',incomplete:'Incomplete'};
    return m[s]||'—';
}

/* ═══ GRADE CHANGE HANDLER ════════════════════════════════════════════════ */
function onGradeChange(subjectId, studentId, majorId, sem, yearLvl, ay){
    const inp=document.getElementById('g-'+subjectId);
    const raw=parseFloat(inp.value);
    if(isNaN(raw)||raw<1||raw>5){return;}
    const rounded=roundGrade(raw);
    const status=gradeStatus(rounded);
    const label=gradeLabel(rounded);

    // Preview
    inp.className='grade-input '+gradeClass(status);
    document.getElementById('gl-'+subjectId).textContent=`→ ${rounded.toFixed(2)} ${label}`;
    inp.title=`Rounded: ${rounded.toFixed(2)} (${label})`;

    if(rounded!==raw){
        inp.style.boxShadow='0 0 0 2px var(--warn)';
        toast(`Grade rounded: ${raw} → ${rounded.toFixed(2)} (${label})`,'info',2000);
    } else {
        inp.style.boxShadow='';
    }
    markDirty(subjectId);
}

function markDirty(subjectId){
    const btn=document.getElementById('sbtn-'+subjectId);
    if(btn){btn.style.background='#fef3c7';btn.style.color='var(--gold-dark)';}
}

/* ═══ SAVE GRADE ══════════════════════════════════════════════════════════ */
function saveGrade(subjectId, studentId, majorId, sem, yearLvl, ay){
    const inp=document.getElementById('g-'+subjectId);
    const rem=document.getElementById('rem-'+subjectId);
    const raw=parseFloat(inp.value);
    if(isNaN(raw)||raw<1||raw>5){toast('Enter a grade between 1.00 and 5.00','error');return;}

    const btn=document.getElementById('sbtn-'+subjectId);
    btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled=true;

    const fd=new FormData();
    fd.append('action','save_grade');
    fd.append('student_id',studentId);
    fd.append('subject_id',subjectId);
    fd.append('major_id',majorId);
    fd.append('grade',raw);
    fd.append('semester',sem);
    fd.append('year_level',yearLvl);
    fd.append('academic_year',ay);
    fd.append('remarks',rem?rem.value:'');

    fetch(EVAL_PROCESS,{method:'POST',body:fd})
    .then(r=>r.json()).then(d=>{
        btn.disabled=false;
        if(d.success){
            btn.innerHTML='<i class="fas fa-check"></i>';
            btn.className='save-grade-btn saved';
            setTimeout(()=>{btn.innerHTML='<i class="fas fa-save"></i>';btn.className='save-grade-btn';},2000);

            const rounded=d.grade_rounded;
            const status=d.status;
            const inp2=document.getElementById('g-'+subjectId);
            inp2.className='grade-input '+gradeClass(status);
            inp2.value=parseFloat(rounded).toFixed(2);
            inp2.style.boxShadow='';
            document.getElementById('gl-'+subjectId).textContent=`${d.label}`;
            const pill=document.getElementById('pill-'+subjectId);
            pill.className='grade-status-pill '+pillClass(status);
            pill.textContent=statusText(status);
            toast(`Saved: ${d.label} (${parseFloat(rounded).toFixed(2)})`,'success');
            recalcLiveGWA();
        } else {
            btn.innerHTML='<i class="fas fa-save"></i>';
            toast(d.message||'Save failed','error');
        }
    }).catch(()=>{btn.disabled=false;btn.innerHTML='<i class="fas fa-save"></i>';toast('Network error','error');});
}

/* ═══ LIVE GWA RECALC (client-side) ══════════════════════════════════════ */
function recalcLiveGWA(){
    let totalPoints=0,totalUnits=0,unitsPassed=0;
    document.querySelectorAll('.grade-input').forEach(inp=>{
        const id=inp.id.replace('g-','');
        if(!id||isNaN(Number(id)))return;
        const raw=parseFloat(inp.value);
        if(isNaN(raw)||raw<1||raw>5)return;
        const rounded=roundGrade(raw);
        // Get units from row
        const row=document.getElementById('row-'+id);
        if(!row)return;
        const cells=row.querySelectorAll('td');
        const units=cells[3]?parseFloat(cells[3].textContent):0;
        if(!units)return;
        totalPoints+=rounded*units;
        totalUnits+=units;
        if(gradeStatus(rounded)==='passed')unitsPassed+=units;
    });
    const gwa=totalUnits>0?(totalPoints/totalUnits).toFixed(2):'—';
    document.getElementById('liveGWA').textContent=gwa;
    document.getElementById('liveUnitsTaken').textContent=totalUnits%1===0?totalUnits:totalUnits.toFixed(1);
    document.getElementById('liveUnitsPassed').textContent=unitsPassed%1===0?unitsPassed:unitsPassed.toFixed(1);
}

/* ═══ ADVISEMENT ══════════════════════════════════════════════════════════ */
function showAdvisement(){
    if(!currentStudent){return;}
    document.getElementById('advOverlay').classList.add('open');
    document.getElementById('advBody').innerHTML=`<div class="empty"><div class="spinner"></div><p style="margin-top:14px;">Generating advisement…</p></div>`;
    document.getElementById('advHeaderSub').textContent=`${currentStudent.first_name} ${currentStudent.last_name} — Subject Recommendations`;

    const fd=new FormData();
    fd.append('action','get_advisement');
    fd.append('student_id',currentStudent.id);
    fd.append('major_id',currentStudent.major_id||0);
    fetch(EVAL_PROCESS,{method:'POST',body:fd})
    .then(r=>r.json()).then(d=>{
        if(!d.success){document.getElementById('advBody').innerHTML=`<div class="empty"><p>${esc(d.message)}</p></div>`;return;}
        renderAdvisement(d.advisement,d.current_year);
    });
}

function renderAdvisement(adv,currentYear){
    let html=`<div style="font-size:13px;color:var(--muted);margin-bottom:18px;">Current standing: <strong style="color:var(--dark);">${esc(currentYear)}</strong></div>`;

    function block(title,items,titleClass,cardStyle,reasonClass,reasonFn){
        if(!items||!items.length)return '';
        let h=`<div class="adv-section"><div class="adv-section-title ${titleClass}">${title} <span style="opacity:.7;font-size:11px;">(${items.length})</span></div><div class="adv-grid">`;
        items.forEach(s=>{
            const reason=reasonFn(s);
            h+=`<div class="adv-card ${cardStyle}">
                <div class="adv-code">${esc(s.subject_code)}</div>
                <div class="adv-name">${esc(s.subject_name)}</div>
                <div style="font-size:10px;color:var(--muted);margin-top:3px;">${esc(s.year_level)} · ${esc(s.semester)} · ${parseFloat(s.units)||0} units</div>
                ${reason?`<div class="adv-reason ${reasonClass}">${reason}</div>`:''}
                ${s.grade_rounded?`<div style="margin-top:5px;"><span class="badge ${s.status==='passed'?'badge-green':s.status==='failed'?'badge-red':'badge-gold'}">${parseFloat(s.grade_rounded).toFixed(2)} ${gradeLabel(parseFloat(s.grade_rounded))}</span></div>`:''}
            </div>`;
        });
        h+=`</div></div>`;
        return h;
    }

    html+=block('<i class="fas fa-check-circle"></i> Recommended for Next Enrollment',adv.recommended,'adv-title-rec','','adv-reason-rec',s=>s.reason||'Available');
    html+=block('<i class="fas fa-redo"></i> Must Retake (Failed)',adv.retake,'adv-title-retake','','adv-reason-retake',s=>s.reason||'Failed');
    html+=block('<i class="fas fa-exclamation-triangle"></i> Conditional / Removal Exam',adv.conditional,'adv-title-cond','','adv-reason-retake',s=>s.reason||'Conditional');
    html+=block('<i class="fas fa-lock"></i> Blocked (Prerequisite Required)',adv.blocked,'adv-title-block','','adv-reason-block',s=>s.reason||'Prerequisite not met');
    html+=block('<i class="fas fa-graduation-cap"></i> Completed',adv.completed,'adv-title-done','','',s=>'');

    if(!adv.recommended?.length&&!adv.retake?.length&&!adv.conditional?.length&&!adv.blocked?.length&&!adv.completed?.length){
        html=`<div class="empty"><i class="fas fa-inbox"></i><p>No prospectus subjects found for this major. Please configure the department prospectus first.</p></div>`;
    }

    document.getElementById('advBody').innerHTML=html;
}

/* ═══ FINALIZE ════════════════════════════════════════════════════════════ */
function finalizeEval(){
    if(!currentStudent)return;
    if(!confirm('Finalize this evaluation session? All grades will be locked for this session record.'))return;
    const notes=document.getElementById('sessionNotes')?document.getElementById('sessionNotes').value:'';
    const fd=new FormData();
    fd.append('action','finalize_session');
    fd.append('student_id',currentStudent.id);
    fd.append('major_id',currentStudent.major_id||0);
    fd.append('academic_year',document.getElementById('academicYearSelect').value);
    fd.append('notes',notes);
    fetch(EVAL_PROCESS,{method:'POST',body:fd})
    .then(r=>r.json()).then(d=>{
        toast(d.message||'Finalized!',d.success?'success':'error');
        if(d.success&&d.gwa){
            const gwaEl=document.getElementById('liveGWA');
            if(gwaEl&&d.gwa.gwa) gwaEl.textContent=parseFloat(d.gwa.gwa).toFixed(2);
        }
    });
}
</script>

<?php if($show_role_modal): ?>
<div style="position:fixed;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;z-index:99999;">
    <div style="background:#fff;border-radius:16px;padding:32px;max-width:360px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div style="width:80px;height:80px;border-radius:50%;background:rgba(220,38,38,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <i class="fas fa-exclamation-triangle" style="font-size:40px;color:#dc2626;"></i>
        </div>
        <h3 style="font-size:20px;font-weight:700;margin-bottom:12px;">Access Restricted</h3>
        <p style="font-size:14px;color:#6b7280;margin-bottom:20px;"><?= htmlspecialchars($role_access['message']??'No access.') ?></p>
        <div style="display:flex;gap:12px;justify-content:center;">
            <a href="../../../data/logout.php" style="background:#dc2626;color:#fff;padding:10px 20px;border-radius:10px;text-decoration:none;font-weight:500;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>
<?php endif; ?>
</body>
</html>

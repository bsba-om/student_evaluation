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
:root{--gold:#B8860B;--gold-light:#D4A843;--gold-dark:#8B6914;--cream:#f7f5ef;--white:#fff;--dark:#1f1f1f;--muted:#666;--border:#d4cfc5;--danger:#ef4444;--success:#22c55e;--warn:#f59e0b;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;background:var(--cream);overflow-x:hidden;}
.page-wrap{padding:24px;}
.card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.08);border:1px solid var(--border);margin-bottom:20px;}
.mentee-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;}
.mentee-card{background:#fff;border-radius:14px;border:1px solid var(--border);padding:18px;cursor:pointer;transition:all .2s;}
.mentee-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.12);}
.mentee-avatar{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;flex-shrink:0;}
.badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-gold{background:#fef3c7;color:#92400e;border:1px solid #fbbf24;}
.badge-blue{background:#dbeafe;color:#1e40af;border:1px solid #93c5fd;}
.badge-green{background:#dcfce7;color:#166534;border:1px solid #86efac;}
.badge-gray{background:var(--cream);color:var(--muted);border:1px solid var(--border);}
.badge-red{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.btn{padding:9px 16px;border:none;border-radius:10px;cursor:pointer;font-weight:600;font-size:13px;font-family:'Poppins',sans-serif;display:inline-flex;align-items:center;gap:7px;transition:all .2s;}
.btn-gold{background:linear-gradient(135deg,var(--gold-light),var(--gold-dark));color:#fff;}
.btn-white{background:rgba(255,255,255,.18);border:1.5px solid rgba(255,255,255,.4);color:#fff;}
.btn-white:hover{background:rgba(255,255,255,.3);}
.btn-green{background:#22c55e;color:#fff;}
.btn-blue{background:#3b82f6;color:#fff;}
.eval-overlay{position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:9900;display:none;align-items:flex-start;justify-content:center;overflow-y:auto;padding:16px;}
.eval-overlay.open{display:flex;}
.eval-panel{background:#fff;border-radius:20px;width:100%;max-width:1140px;min-height:92vh;box-shadow:0 24px 80px rgba(0,0,0,.4);display:flex;flex-direction:column;}
.eval-header{background:linear-gradient(135deg,var(--gold-dark),var(--gold-light));border-radius:20px 20px 0 0;padding:18px 26px;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.eval-close{width:34px;height:34px;border:none;background:rgba(255,255,255,.2);border-radius:9px;cursor:pointer;font-size:17px;color:#fff;display:flex;align-items:center;justify-content:center;}
.eval-close:hover{background:rgba(255,255,255,.35);}
.eval-body{padding:20px 26px;flex:1;overflow-y:auto;}
/* Prospectus styles — mirrors department page */
.pro-wrap{font-family:'Poppins',sans-serif;font-size:13px;color:#1a1a1a;background:#fff;border-radius:14px;border:1px solid var(--border);overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);}
.pro-header{display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;background:linear-gradient(to bottom,#fffdf5,#fff);border-bottom:3px solid #8B6914;}
.pro-logo{width:88px;height:88px;object-fit:cover;border-radius:10px;border:2px solid #8B6914;}
.pro-title-block{text-align:center;flex:1;padding:0 14px;}
.pro-school{font-size:15px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;}
.pro-address{font-size:11px;color:#666;margin:3px 0;}
.pro-institute{font-size:12px;font-weight:700;color:#8B6914;text-transform:uppercase;margin-top:5px;}
.pro-degree{font-size:11px;color:#444;margin:3px 0;}
.pro-major-line{font-size:12px;font-weight:600;margin:3px 0;}
.pro-student-info{font-size:11px;color:#555;margin-top:4px;}
.pro-label{display:inline-block;margin-top:5px;padding:2px 12px;border:1.5px solid #8B6914;border-radius:20px;font-size:10px;font-weight:700;color:#8B6914;letter-spacing:.5px;text-transform:uppercase;}
.gwa-strip{display:flex;gap:10px;padding:12px 18px;background:#fffdf5;border-bottom:1px solid var(--border);flex-wrap:wrap;align-items:center;}
.gwa-card{background:linear-gradient(135deg,var(--gold-dark),var(--gold-light));border-radius:10px;padding:10px 18px;color:#fff;text-align:center;min-width:100px;}
.gwa-value{font-size:22px;font-weight:800;line-height:1;}
.gwa-lbl{font-size:10px;opacity:.85;margin-top:2px;}
.gwa-stat{background:var(--cream);border-radius:10px;padding:10px 16px;text-align:center;border:1px solid var(--border);min-width:95px;}
.gwa-stat-val{font-size:18px;font-weight:700;color:var(--dark);}
.gwa-stat-lbl{font-size:10px;color:var(--muted);margin-top:2px;}
.pro-body{padding:14px 18px 18px;}
.pro-year-block{margin-bottom:14px;border:1px solid #e0dbd0;border-radius:10px;overflow:hidden;}
.pro-year-header{background:linear-gradient(135deg,#8B6914,#B8860B);color:#fff;padding:8px 14px;font-size:13px;font-weight:700;display:flex;justify-content:space-between;align-items:center;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
.pro-year-total{font-size:11px;font-weight:400;opacity:.85;}
.pro-sem-row{display:grid;grid-template-columns:1fr 1fr;padding:8px 10px 10px;gap:10px;}
.pro-sem-label{font-size:10px;font-weight:700;color:#8B6914;text-align:center;padding:4px 0;background:#f7f5ef;border:1px solid #d4cfc5;border-radius:5px 5px 0 0;text-transform:uppercase;letter-spacing:.3px;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
.pro-table{width:100%;border-collapse:collapse;font-size:11px;}
.pro-th{background:#f0ece0;padding:5px 7px;text-align:left;font-size:10px;font-weight:700;color:#8B6914;border:1px solid #ccc;white-space:nowrap;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
.pro-table td{border:1px solid #ddd;padding:4px 7px;vertical-align:middle;}
.pro-table tr:hover td{background:#fdfbf6;}
.pro-code{font-weight:700;white-space:nowrap;font-size:10px;}
.pro-units{text-align:center;font-weight:600;}
.pro-prereq-col{color:#888;font-size:10px;}
.pro-prereq-mark{color:#dc2626;font-weight:700;}
.grade-cell-wrap{display:flex;flex-direction:column;align-items:center;gap:2px;}
.grade-input{width:44px;padding:3px 4px;border:1.5px solid #ccc;border-radius:5px;font-family:'Poppins',sans-serif;font-size:10px;font-weight:700;text-align:center;transition:all .2s;background:#fafaf8;}
.grade-input:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 2px rgba(184,134,11,.15);}
.grade-input.g-pass{border-color:#22c55e;background:#f0fdf4;}
.grade-input.g-fail{border-color:#ef4444;background:#fef2f2;}
.grade-input.g-cond{border-color:#f59e0b;background:#fffbeb;}
.save-btn{width:16px;height:16px;border:none;border-radius:3px;cursor:pointer;background:#dbeafe;color:#1d4ed8;font-size:7px;display:flex;align-items:center;justify-content:center;transition:all .15s;}
.save-btn:hover{background:#1d4ed8;color:#fff;}
.save-btn.saved{background:#dcfce7;color:#166534;}
.grade-hint{font-size:8px;color:var(--muted);text-align:center;max-width:50px;line-height:1.2;}
.g-pill{padding:2px 5px;border-radius:4px;font-size:8px;font-weight:700;white-space:nowrap;}
.g-pill.g-pass{background:#dcfce7;color:#166534;}
.g-pill.g-fail{background:#fee2e2;color:#991b1b;}
.g-pill.g-cond{background:#fef3c7;color:#92400e;}
.g-pill.g-none{background:var(--cream);color:var(--muted);}
.pro-total-row td{background:#f0ece0;font-weight:700;color:#8B6914;border-top:2px solid #B8860B;font-size:10px;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
.pro-empty-cell{text-align:center;color:#aaa;font-style:italic;padding:10px;font-size:10px;}
.pro-grand-total{text-align:right;font-size:12px;font-weight:700;padding:7px 14px;background:#f7f5ef;border:1px solid #d4cfc5;border-radius:7px;margin:0 0 14px;}
.pro-sig-block{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;padding:14px 0 0;border-top:2px solid #d4cfc5;}
.pro-sig-col{text-align:center;}
.pro-sig-line{border-bottom:1.5px solid #333;margin-bottom:5px;height:26px;}
.pro-sig-lbl{font-size:11px;font-weight:600;color:#333;}
.pro-sig-sub{font-size:10px;color:#888;margin-top:2px;}
.pro-legend{font-size:10px;color:#999;padding:5px 0;margin-top:8px;}
.pro-bridging-block{margin-bottom:14px;}
.adv-section-title{font-size:12px;font-weight:700;margin-bottom:10px;display:flex;align-items:center;gap:7px;padding:7px 12px;border-radius:8px;}
.adv-title-rec{background:#dcfce7;color:#166534;}
.adv-title-retake{background:#fee2e2;color:#991b1b;}
.adv-title-cond{background:#fef3c7;color:#92400e;}
.adv-title-block{background:#f1f5f9;color:#475569;}
.adv-title-done{background:#dbeafe;color:#1e40af;}
.adv-reason-rec{background:#dcfce7;color:#166534;}
.adv-reason-block{background:#fee2e2;color:#991b1b;}
.adv-reason-retake{background:#fef3c7;color:#92400e;}
.adv-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:10px;}
.adv-card{border-radius:9px;padding:11px;border:1px solid var(--border);background:#fff;}
.adv-code{font-size:12px;font-weight:700;color:var(--dark);}
.adv-name{font-size:10px;color:var(--muted);margin-top:2px;}
.adv-reason{font-size:9px;margin-top:5px;padding:3px 7px;border-radius:5px;}
.spinner{display:inline-block;width:22px;height:22px;border:3px solid rgba(184,134,11,.2);border-top-color:var(--gold-dark);border-radius:50%;animation:spin .7s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}
.empty{text-align:center;padding:50px 20px;color:var(--muted);}
.empty i{font-size:48px;opacity:.2;display:block;margin-bottom:12px;}
.toast{position:fixed;bottom:24px;right:24px;background:#1f1f1f;color:#fff;padding:12px 18px;border-radius:12px;font-size:13px;font-weight:500;display:flex;align-items:center;gap:9px;transform:translateY(100px);opacity:0;transition:all .3s;z-index:99999;box-shadow:0 8px 24px rgba(0,0,0,.3);max-width:340px;}
.toast.show{transform:translateY(0);opacity:1;}
.toast.success{border-left:4px solid #22c55e;}
.toast.error{border-left:4px solid #ef4444;}
.toast.info{border-left:4px solid var(--gold);}
.search-wrap{display:flex;align-items:center;gap:9px;padding:9px 13px;background:var(--cream);border-radius:10px;border:1.5px solid var(--border);max-width:300px;}
.search-wrap:focus-within{border-color:var(--gold);}
.search-wrap input{border:none;background:transparent;font-family:'Poppins',sans-serif;font-size:13px;color:var(--dark);flex:1;outline:none;}
.ay-select{padding:8px 12px;border:1.5px solid var(--border);border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;color:var(--dark);background:#fff;}
.notes-textarea{width:100%;padding:8px 10px;border:1.5px solid var(--border);border-radius:8px;font-family:'Poppins',sans-serif;font-size:12px;resize:vertical;min-height:50px;}
.notes-textarea:focus{outline:none;border-color:var(--gold);}
.session-bar{background:var(--cream);border-radius:10px;padding:14px 18px;border:1px solid var(--border);margin-top:14px;}

/* PRINT */
@media print{
    @page{size:A4 portrait;margin:5mm 6mm;}
    html{font-size:7.5pt;}
    body>*{display:none!important;}
    #printTarget{display:block!important;position:static!important;}
    .pro-wrap{border:none!important;box-shadow:none!important;border-radius:0!important;width:100%!important;font-size:1rem;}
    .pro-header{padding:6pt 8pt 5pt!important;border-bottom:2pt solid #8B6914!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}
    .pro-logo{width:72pt!important;height:72pt!important;border:1pt solid #8B6914!important;}
    .pro-school{font-size:10pt!important;}
    .pro-address,.pro-student-info{font-size:7pt!important;}
    .pro-institute{font-size:8pt!important;}
    .pro-degree{font-size:7pt!important;}
    .pro-major-line{font-size:8pt!important;}
    .pro-label{font-size:7pt!important;padding:1pt 7pt!important;}
    .gwa-strip{display:none!important;}
    .pro-body{padding:4pt 6pt 6pt!important;}
    .pro-year-block{margin-bottom:4pt!important;page-break-inside:avoid!important;border:0.5pt solid #e0dbd0!important;border-radius:2pt!important;}
    .pro-year-header{padding:3pt 7pt!important;font-size:8pt!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}
    .pro-sem-row{padding:4pt 5pt 5pt!important;gap:5pt!important;}
    .pro-sem-label{font-size:6.5pt!important;padding:2pt 0!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}
    .pro-table{font-size:6.5pt!important;}
    .pro-th{padding:2pt 3pt!important;font-size:6pt!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}
    .pro-table td{padding:2pt 3pt!important;}
    .save-btn{display:none!important;}
    .grade-input{border:0.5pt solid #aaa!important;background:transparent!important;font-size:7pt!important;width:30pt!important;}
    .grade-hint{display:none!important;}
    .g-pill{font-size:6pt!important;padding:1pt 3pt!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}
    .pro-th:nth-child(2),.pro-td-status,.pro-th:nth-child(6),.pro-td-prereq{display:none!important;}
    .grade-input{display:none!important;}
    .grade-print{display:inline-block!important;font-size:7pt!important;font-weight:700!important;}
    .pro-total-row td{font-size:6.5pt!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}
    .pro-grand-total{font-size:8pt!important;padding:3pt 7pt!important;margin-bottom:4pt!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}
    .pro-sig-block{gap:10pt!important;padding-top:5pt!important;margin-top:5pt!important;}
    .pro-sig-line{height:14pt!important;}
    .pro-sig-lbl{font-size:7pt!important;}
    .pro-sig-sub{font-size:6.5pt!important;}
    .pro-legend{font-size:6pt!important;}
    .session-bar{display:none!important;}
}
</style>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="../../../media/LOGO.jpg" alt="Logo" class="sidebar-logo" style="width:70px;height:70px;border-radius:16px;object-fit:cover;border:3px solid white;background:white;padding:4px;box-shadow:0 4px 12px rgba(0,0,0,.2);">
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
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                <div>
                    <h1 style="font-size:22px;font-weight:700;color:var(--dark);">My Mentees</h1>
                    <p style="font-size:13px;color:var(--muted);margin-top:3px;">Select a student to open their evaluation prospectus</p>
                </div>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <div class="search-wrap">
                        <i class="fas fa-search" style="color:var(--muted);font-size:13px;"></i>
                        <input type="text" id="menteeSearch" placeholder="Search mentees…" oninput="filterMentees()">
                    </div>
                    <select class="ay-select" id="academicYearSelect">
                        <option value="2025-2026">A.Y. 2025-2026</option>
                        <option value="2024-2025">A.Y. 2024-2025</option>
                        <option value="2026-2027">A.Y. 2026-2027</option>
                    </select>
                </div>
            </div>
            <div class="card">
                <div id="menteesContainer">
                    <div class="empty"><i class="fas fa-spinner fa-spin" style="font-size:32px;opacity:.4;"></i><p style="margin-top:10px;">Loading mentees…</p></div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- EVALUATION OVERLAY -->
<div class="eval-overlay" id="evalOverlay">
    <div class="eval-panel">
        <div class="eval-header">
            <div>
                <div style="font-size:18px;font-weight:700;" id="evalTitle">Student Evaluation</div>
                <div style="font-size:12px;opacity:.85;margin-top:2px;" id="evalSub"></div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <button class="btn btn-white" onclick="showAdvisement()"><i class="fas fa-lightbulb"></i> Advisement</button>
                <button class="btn btn-white" onclick="printProspectus()"><i class="fas fa-print"></i> Print</button>
                <button class="btn btn-white" onclick="finalizeEval()"><i class="fas fa-check-circle"></i> Finalize</button>
                <button class="eval-close" onclick="closeEval()"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <div class="eval-body" id="evalBody">
            <div class="empty"><div class="spinner"></div><p style="margin-top:14px;">Loading prospectus…</p></div>
        </div>
    </div>
</div>

<!-- ADVISEMENT OVERLAY -->
<div class="eval-overlay" id="advOverlay">
    <div class="eval-panel" style="max-width:860px;min-height:auto;">
        <div class="eval-header" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);border-radius:20px 20px 0 0;">
            <div>
                <div style="font-size:17px;font-weight:700;"><i class="fas fa-lightbulb" style="margin-right:8px;"></i>Subject Advisement</div>
                <div style="font-size:12px;opacity:.85;margin-top:2px;" id="advSub"></div>
            </div>
            <button class="eval-close" onclick="document.getElementById('advOverlay').classList.remove('open')"><i class="fas fa-times"></i></button>
        </div>
        <div class="eval-body" id="advBody">
            <div class="empty"><div class="spinner"></div></div>
        </div>
    </div>
</div>

<!-- hidden print clone target -->
<div id="printTarget" style="display:none;"></div>

<div class="toast" id="toast"><span id="toastMsg"></span></div>

<script src="../../../function/dashboard.js"></script>
<script>
const EVAL_PROCESS='../../../data/evaluation_process.php';
const VALID_GRADES=[1.00,1.25,1.50,1.75,2.00,2.25,2.50,2.75,3.00,4.00,5.00];
const GRADE_LABELS={1.00:'Excellent',1.25:'Very Good',1.50:'Very Good',1.75:'Good',2.00:'Satisfactory',2.25:'Fair',2.50:'Passing',2.75:'Low Passing',3.00:'Barely Passing',4.00:'Conditional',5.00:'Failed'};
let phSettings={school_name:'Northern Bukidnon State College',school_address:'Manolo Fortich, Bukidnon',institute_name:'Institute for Business Management',degree_name:'Bachelor of Science in Business Administration'};
let currentStudent=null;
let currentSubjectsData=[];

function roundGrade(r){let c=5.00,d=99;VALID_GRADES.forEach(v=>{const x=Math.abs(r-v);if(x<d){d=x;c=v;}});return c;}
function gradeStatus(g){if(g<=3.00)return'passed';if(g===4.00)return'conditional';return'failed';}
function gradeLabel(g){return GRADE_LABELS[g]||'—';}
function gradeClass(s){return s==='passed'?'g-pass':s==='failed'?'g-fail':s==='conditional'?'g-cond':'';}
function pillClass(s){return'g-pill '+(s==='passed'?'g-pass':s==='failed'?'g-fail':s==='conditional'?'g-cond':'g-none');}
function statusText(s){return{passed:'Passed',failed:'Failed',conditional:'Cond.',not_taken:'—',incomplete:'Inc.'}[s]||'—';}
function esc(s){if(!s)return'';return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function toast(msg,type='info',dur=3000){const el=document.getElementById('toast');document.getElementById('toastMsg').textContent=msg;el.className=`toast ${type} show`;clearTimeout(el._t);el._t=setTimeout(()=>el.classList.remove('show'),dur);}

/* ── LOAD MENTEES ─────────────────────────────────────────── */
function loadMentees(){
    const fd=new FormData();fd.append('action','get_mentees');
    fetch(EVAL_PROCESS,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        const c=document.getElementById('menteesContainer');
        if(!d.success||!d.mentees||!d.mentees.length){c.innerHTML=`<div class="empty"><i class="fas fa-users"></i><p>No mentees assigned to you yet.</p></div>`;return;}
        let html=`<div class="mentee-grid" id="menteeGrid">`;
        d.mentees.forEach(m=>{
            const full=`${m.first_name}${m.middle_name?' '+m.middle_name:''} ${m.last_name}${m.suffix?' '+m.suffix:''}`;
            const init=m.avatar_initials||(m.first_name[0]+(m.last_name[0]||'')).toUpperCase();
            const pct=m.total_subjects>0?Math.round(m.graded_count/m.total_subjects*100):0;
            html+=`<div class="mentee-card" onclick="openEval(${JSON.stringify(m).replace(/"/g,'&quot;')})" data-name="${esc(full.toLowerCase())}">
                <div style="display:flex;align-items:center;gap:13px;margin-bottom:11px;">
                    <div class="mentee-avatar" style="background:linear-gradient(135deg,${esc(m.avatar_gradient_from||'#3b82f6')},${esc(m.avatar_gradient_to||'#60a5fa')});">${esc(init)}</div>
                    <div>
                        <div style="font-size:14px;font-weight:700;color:var(--dark);">${esc(full)}</div>
                        <div style="font-size:11px;color:var(--muted);margin-top:2px;">${esc(m.student_number||'—')} · ${esc(m.major_name||'No major')}</div>
                    </div>
                </div>
                <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:10px;">
                    <span class="badge badge-blue"><i class="fas fa-layer-group" style="font-size:9px;"></i> ${esc(m.year_level||'—')}</span>
                    ${m.major_name?`<span class="badge badge-gold">${esc(m.major_name)}</span>`:''}
                    <span class="badge ${m.graded_count>0?'badge-green':'badge-gray'}"><i class="fas fa-star" style="font-size:9px;"></i> ${m.graded_count}/${m.total_subjects} graded</span>
                </div>
                <div style="background:var(--cream);border-radius:20px;height:5px;overflow:hidden;">
                    <div style="height:100%;width:${pct}%;background:linear-gradient(to right,var(--gold-light),var(--gold-dark));border-radius:20px;"></div>
                </div>
                <div style="font-size:10px;color:var(--muted);margin-top:3px;text-align:right;">${pct}% evaluated</div>
                <div style="margin-top:9px;text-align:right;font-size:11px;font-weight:600;color:var(--gold-dark);"><i class="fas fa-scroll" style="font-size:10px;"></i> Open Prospectus</div>
            </div>`;
        });
        html+=`</div>`;c.innerHTML=html;
    });
}
loadMentees();
function filterMentees(){const q=document.getElementById('menteeSearch').value.toLowerCase();document.querySelectorAll('.mentee-card').forEach(c=>{c.style.display=c.dataset.name.includes(q)?'':'none';});}

/* ── OPEN / CLOSE ────────────────────────────────────────── */
function openEval(m){
    if(typeof m==='string')m=JSON.parse(m);
    currentStudent=m;
    document.getElementById('evalOverlay').classList.add('open');
    document.getElementById('evalBody').innerHTML=`<div class="empty"><div class="spinner"></div><p style="margin-top:14px;">Loading prospectus…</p></div>`;
    const full=`${m.first_name}${m.middle_name?' '+m.middle_name:''} ${m.last_name}${m.suffix?' '+m.suffix:''}`;
    document.getElementById('evalTitle').textContent=full;
    document.getElementById('evalSub').textContent=`${m.major_name||'No major'} · ${m.year_level||'—'} · A.Y. ${document.getElementById('academicYearSelect').value}`;
    const fd=new FormData();fd.append('action','get_student_evaluation');fd.append('student_id',m.id);fd.append('academic_year',document.getElementById('academicYearSelect').value);
    fetch(EVAL_PROCESS,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(!d.success){document.getElementById('evalBody').innerHTML=`<div class="empty"><i class="fas fa-exclamation-triangle"></i><p>${esc(d.message)}</p></div>`;return;}
        renderProspectus(d);
    });
}
function closeEval(){document.getElementById('evalOverlay').classList.remove('open');}

/* ── RENDER PROSPECTUS ───────────────────────────────────── */
function renderProspectus(data){
    const s=data.student,subjects=data.subjects||[],gwa=data.gwa_data||{},ay=data.academic_year||'2025-2026';
    const full=`${s.first_name}${s.middle_name?' '+s.middle_name:''} ${s.last_name}${s.suffix?' '+s.suffix:''}`;

    const header=`<div class="pro-header">
        <img src="../../../media/LOGO.jpg" class="pro-logo" alt="Logo">
        <div class="pro-title-block">
            <div class="pro-school">${esc(phSettings.school_name)}</div>
            <div class="pro-address">${esc(phSettings.school_address)}</div>
            <div class="pro-institute">${esc(phSettings.institute_name)}</div>
            <div class="pro-degree">${esc(phSettings.degree_name)}</div>
            <div class="pro-major-line">Major in <strong>${esc(s.major_name||'—')}</strong></div>
            <div class="pro-student-info"><strong>${esc(full)}</strong> &nbsp;·&nbsp; ID: ${esc(s.student_id||'—')} &nbsp;·&nbsp; ${esc(s.year_level||'—')} &nbsp;·&nbsp; A.Y. ${esc(ay)}</div>
            <div class="pro-label">Student Evaluation Prospectus</div>
        </div>
        <img src="../../../media/nbsc_logo.png" class="pro-logo" alt="Logo" onerror="this.style.display='none'">
    </div>`;

    const gwaStrip=`<div class="gwa-strip">
        <div class="gwa-card"><div class="gwa-value" id="liveGWA">${gwa.gwa!=null?parseFloat(gwa.gwa).toFixed(2):'—'}</div><div class="gwa-lbl">Current GWA</div></div>
        <div class="gwa-stat"><div class="gwa-stat-val" id="liveUnitsTaken">${gwa.total_units||0}</div><div class="gwa-stat-lbl">Units Taken</div></div>
        <div class="gwa-stat"><div class="gwa-stat-val" id="liveUnitsPassed">${gwa.units_passed||0}</div><div class="gwa-stat-lbl">Units Passed</div></div>
        <div class="gwa-stat"><div class="gwa-stat-val" id="liveUnitsFailed" style="color:#ef4444;">${(gwa.total_units||0)-(gwa.units_passed||0)}</div><div class="gwa-stat-lbl">Units w/ Issues</div></div>
        <div style="margin-left:auto;font-size:11px;color:var(--muted);"><i class="fas fa-info-circle"></i> Type grade then click <strong>save</strong></div>
    </div>`;

    const yearOrder=['1st Year','2nd Year','3rd Year','4th Year'];
    const byYear={};subjects.forEach(s2=>{const y=s2.year_level||'1st Year';if(!byYear[y])byYear[y]=[];byYear[y].push(s2);});
    let yearBlocks='',grandTotal=0;
    yearOrder.forEach(year=>{
        const all=byYear[year]||[];if(!all.length)return;
        const sem1=all.filter(s2=>!s2.semester||s2.semester.includes('1st'));
        const sem2=all.filter(s2=>s2.semester&&s2.semester.includes('2nd'));
        const t=all.reduce((a,s2)=>a+(parseFloat(s2.units)||0),0);grandTotal+=t;
        yearBlocks+=`<div class="pro-year-block">
            <div class="pro-year-header"><span><i class="fas fa-calendar-alt" style="margin-right:6px;font-size:11px;"></i>${year}</span><span class="pro-year-total">${t%1===0?t:t.toFixed(1)} units</span></div>
            <div class="pro-sem-row">
                <div><div class="pro-sem-label">1st Semester</div>${buildTable(sem1,s,ay)}</div>
                <div><div class="pro-sem-label">2nd Semester</div>${buildTable(sem2,s,ay)}</div>
            </div>
        </div>`;
    });

    const bridging=subjects.filter(s2=>s2.year_level==='Bridging');
    let bridgeHtml='';
    if(bridging.length){
        const bt=bridging.reduce((a,s2)=>a+(parseFloat(s2.units)||0),0);grandTotal+=bt;
        bridgeHtml=`<div class="pro-bridging-block"><div class="pro-year-block">
            <div class="pro-year-header" style="background:linear-gradient(135deg,#7c3aed,#8b5cf6);"><span><i class="fas fa-exchange-alt" style="margin-right:6px;font-size:11px;"></i>Bridging Subjects</span><span class="pro-year-total">${bt} units</span></div>
            <div style="padding:8px 10px 10px;"><div class="pro-sem-label" style="border-radius:5px 5px 0 0;text-align:left;padding-left:8px;">All Semesters</div>${buildTable(bridging,s,ay)}</div>
        </div></div>`;
    }

    const gt=grandTotal%1===0?grandTotal:grandTotal.toFixed(1);
    const sig=`<div class="pro-sig-block">
        <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-lbl">Student's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
        <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-lbl">Adviser's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
        <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-lbl">Program Head's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
    </div>
    <div class="pro-legend"><span style="color:#dc2626;">★</span> = Prerequisite subject</div>`;

    const proHtml=`<div class="pro-wrap" id="liveProspectus">
        ${header}${gwaStrip}
        <div class="pro-body">
            ${!subjects.length?`<div class="empty"><i class="fas fa-book"></i><p>No subjects configured for this major yet. Set up the prospectus in Department Management first.</p></div>`:''}
            ${yearBlocks}${bridgeHtml}
            ${subjects.length?`<div class="pro-grand-total">Grand Total: <strong>${gt} units</strong></div>`:''}
            ${sig}
        </div>
    </div>`;

    const sessionHtml=`<div class="session-bar">
        <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:8px;"><i class="fas fa-clipboard" style="color:var(--gold-dark);margin-right:6px;"></i>Session Notes</div>
        <textarea class="notes-textarea" id="sessionNotes" placeholder="Optional evaluation notes…"></textarea>
        <div style="display:flex;gap:10px;margin-top:10px;flex-wrap:wrap;">
            <button class="btn btn-blue" onclick="showAdvisement()"><i class="fas fa-lightbulb"></i> Generate Advisement</button>
            <button class="btn btn-green" onclick="finalizeEval()"><i class="fas fa-check-circle"></i> Finalize Evaluation</button>
        </div>
    </div>`;
    document.getElementById('evalBody').innerHTML=proHtml+sessionHtml;
}

/* ── BUILD TABLE ─────────────────────────────────────────── */
function buildTable(subjects,student,ay){
    if(!subjects||!subjects.length){
        return`<table class="pro-table"><thead><tr><th class="pro-th" style="width:58px;">Grade</th><th class="pro-th" style="width:34px;">Status</th><th class="pro-th" style="width:58px;">Code</th><th class="pro-th">Subject Title</th><th class="pro-th" style="width:30px;">Units</th><th class="pro-th" style="width:46px;">Pre-Req</th></tr></thead><tbody><tr><td colspan="6" class="pro-empty-cell">No subjects</td></tr></tbody></table>`;
    }
    let rows='',total=0;
    subjects.forEach(s=>{
        const raw=s.grade_rounded;const status=s.grade_status||'not_taken';
        const inputCls=raw?gradeClass(status):'';const prereq=s.prerequisite||'';
        total+=parseFloat(s.units)||0;
        rows+=`<tr id="row-${s.id}">
            <td><div class="grade-cell-wrap">
                <div style="display:flex;align-items:center;gap:2px;">
                    <input type="number" class="grade-input ${inputCls}" id="g-${s.id}"
                        value="${raw?parseFloat(raw).toFixed(2):''}" min="1" max="5" step="0.01" placeholder="—"
                        onchange="onGradeChange(${s.id},${student.id},${student.major_id},'${esc(s.semester)}','${esc(s.year_level)}','${esc(ay)}')"
                        title="1.00–5.00">
                    <span class="grade-print">${raw?parseFloat(raw).toFixed(2):'—'}</span>
                    <button class="save-btn" id="sbtn-${s.id}"
                        onclick="saveGrade(${s.id},${student.id},${student.major_id},'${esc(s.semester)}','${esc(s.year_level)}','${esc(ay)}')"
                        title="Save"><i class="fas fa-save"></i></button>
                </div>
                <div class="grade-hint" id="gl-${s.id}">${s.grade_label||''}</div>
            </div></td>
            <td class="pro-td-status"><span class="${pillClass(status)}" id="pill-${s.id}">${statusText(status)}</span></td>
            <td class="pro-code">${esc(s.subject_code)}${s.is_prerequisite?'<span style="color:#dc2626;font-size:9px;"> ★</span>':''}</td>
            <td style="font-size:10px;">${esc(s.subject_name)}</td>
            <td class="pro-units">${parseFloat(s.units)||0}</td>
            <td class="pro-td-prereq pro-prereq-col">${prereq?`<span class="pro-prereq-mark">★ ${esc(prereq)}</span>`:'—'}</td>
        </tr>`;
    });
    const t=total%1===0?total:total.toFixed(1);
    rows+=`<tr class="pro-total-row"><td colspan="4" style="text-align:right;padding-right:8px;">Total Units</td><td class="pro-units">${t}</td><td></td></tr>`;
    return`<table class="pro-table"><thead><tr><th class="pro-th" style="width:58px;">Grade</th><th class="pro-th" style="width:34px;">Status</th><th class="pro-th" style="width:58px;">Code</th><th class="pro-th">Subject Title</th><th class="pro-th" style="width:30px;">Units</th><th class="pro-th" style="width:46px;">Pre-Req</th></tr></thead><tbody>${rows}</tbody></table>`;
}

/* ── GRADE CHANGE ─────────────────────────────────────────── */
function onGradeChange(sid,studentId,majorId,sem,year,ay){
    const inp=document.getElementById('g-'+sid);const raw=parseFloat(inp.value);
    if(isNaN(raw)||raw<1||raw>5)return;
    const rounded=roundGrade(raw);const status=gradeStatus(rounded);const label=gradeLabel(rounded);
    inp.className='grade-input '+gradeClass(status);
    document.getElementById('gl-'+sid).textContent=`→ ${rounded.toFixed(2)} ${label}`;
    if(rounded!==raw){inp.style.boxShadow='0 0 0 2px var(--warn)';toast(`Rounded ${raw} → ${rounded.toFixed(2)} (${label})`,'info',2000);}
    else inp.style.boxShadow='';
    const btn=document.getElementById('sbtn-'+sid);if(btn){btn.style.background='#fef3c7';btn.style.color='var(--gold-dark)';}
}

/* ── SAVE GRADE ───────────────────────────────────────────── */
function saveGrade(sid,studentId,majorId,sem,year,ay){
    const inp=document.getElementById('g-'+sid);const raw=parseFloat(inp.value);
    if(isNaN(raw)||raw<1||raw>5){toast('Grade must be 1.00–5.00','error');return;}
    const btn=document.getElementById('sbtn-'+sid);btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';btn.disabled=true;
    const fd=new FormData();fd.append('action','save_grade');fd.append('student_id',studentId);fd.append('subject_id',sid);fd.append('major_id',majorId);fd.append('grade',raw);fd.append('semester',sem);fd.append('year_level',year);fd.append('academic_year',ay);
    fetch(EVAL_PROCESS,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        btn.disabled=false;
        if(d.success){
            btn.innerHTML='<i class="fas fa-check"></i>';btn.className='save-btn saved';
            setTimeout(()=>{btn.innerHTML='<i class="fas fa-save"></i>';btn.className='save-btn';},2000);
            const rounded=d.grade_rounded,status=d.status;
            inp.value=parseFloat(rounded).toFixed(2);inp.className='grade-input '+gradeClass(status);inp.style.boxShadow='';
            document.getElementById('gl-'+sid).textContent=d.label;
            const pill=document.getElementById('pill-'+sid);if(pill){pill.className=pillClass(status);pill.textContent=statusText(status);}
            toast(`Saved: ${d.label} (${parseFloat(rounded).toFixed(2)})`,'success');
            recalcGWA();
        } else {btn.innerHTML='<i class="fas fa-save"></i>';toast(d.message||'Save failed','error');}
    }).catch(()=>{btn.disabled=false;btn.innerHTML='<i class="fas fa-save"></i>';toast('Network error','error');});
}

/* ── GWA ──────────────────────────────────────────────────── */
function recalcGWA(){
    let tp=0,tu=0,up=0;
    document.querySelectorAll('.grade-input').forEach(inp=>{
        const sid=inp.id.replace('g-','');if(!sid||isNaN(Number(sid)))return;
        const raw=parseFloat(inp.value);if(isNaN(raw)||raw<1||raw>5)return;
        const rounded=roundGrade(raw);const row=document.getElementById('row-'+sid);if(!row)return;
        const cells=row.querySelectorAll('td');const units=cells[4]?parseFloat(cells[4].textContent):0;if(!units)return;
        tp+=rounded*units;tu+=units;if(gradeStatus(rounded)==='passed')up+=units;
    });
    const gEl=document.getElementById('liveGWA');if(gEl)gEl.textContent=tu>0?(tp/tu).toFixed(2):'—';
    const utEl=document.getElementById('liveUnitsTaken');if(utEl)utEl.textContent=tu%1===0?tu:tu.toFixed(1);
    const upEl=document.getElementById('liveUnitsPassed');if(upEl)upEl.textContent=up%1===0?up:up.toFixed(1);
    const ufEl=document.getElementById('liveUnitsFailed');if(ufEl)ufEl.textContent=(tu-up)%1===0?(tu-up):(tu-up).toFixed(1);
}

/* ── PRINT ────────────────────────────────────────────────── */
function printProspectus(){
    const el=document.getElementById('liveProspectus');if(!el){toast('No prospectus loaded yet.','error');return;}
    const tdCount=el.querySelectorAll('.pro-table tbody tr').length;
    const scale=Math.max(5.5,8-Math.max(0,tdCount-40)*0.06);
    const sid='_eval_print_style';document.getElementById(sid)?.remove();
    const style=document.createElement('style');style.id=sid;
    style.textContent=`@media print{@page{size:A4 portrait;margin:5mm 6mm;}html{font-size:${scale}pt;}body>*{display:none!important;}#printTarget{display:block!important;position:static!important;}}`;
    document.head.appendChild(style);
    const pt=document.getElementById('printTarget');pt.innerHTML=el.outerHTML;
    pt.querySelectorAll('.save-btn').forEach(b=>b.remove());
    window.print();
    window.addEventListener('afterprint',function onAP(){document.getElementById(sid)?.remove();pt.innerHTML='';window.removeEventListener('afterprint',onAP);},{once:true});
}

/* ── ADVISEMENT ───────────────────────────────────────────── */
function showAdvisement(){
    if(!currentStudent)return;
    document.getElementById('advOverlay').classList.add('open');
    document.getElementById('advSub').textContent=`${currentStudent.first_name} ${currentStudent.last_name} — Next Enrollment Recommendations`;
    document.getElementById('advBody').innerHTML=`<div class="empty"><div class="spinner"></div><p style="margin-top:14px;">Analyzing grades…</p></div>`;
    const fd=new FormData();fd.append('action','get_advisement');fd.append('student_id',currentStudent.id);fd.append('major_id',currentStudent.major_id||0);
    fetch(EVAL_PROCESS,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(!d.success){document.getElementById('advBody').innerHTML=`<div class="empty"><p>${esc(d.message)}</p></div>`;return;}
        renderAdvisement(d.advisement,d.current_year);
    });
}

function renderAdvisement(adv,currentYear){
    let html=`<div style="font-size:13px;color:var(--muted);margin-bottom:16px;"><strong style="color:var(--dark);">Current standing:</strong> ${esc(currentYear)} &nbsp;·&nbsp; Based on graded subjects and prerequisite completion.</div>`;
    function block(title,items,titleClass,reasonClass,reasonFn){
        if(!items||!items.length)return'';
        let h=`<div style="margin-bottom:16px;"><div class="adv-section-title ${titleClass}">${title} <span style="opacity:.7;font-size:11px;">(${items.length})</span></div><div class="adv-grid">`;
        items.forEach(s=>{
            const r=reasonFn(s);
            const gBadge=s.grade_rounded?`<span class="badge ${s.status==='passed'?'badge-green':s.status==='failed'?'badge-red':'badge-gold'}" style="margin-top:4px;">${parseFloat(s.grade_rounded).toFixed(2)} — ${gradeLabel(parseFloat(s.grade_rounded))}</span>`:'';
            h+=`<div class="adv-card"><div class="adv-code">${esc(s.subject_code)}</div><div class="adv-name">${esc(s.subject_name)}</div><div style="font-size:9px;color:var(--muted);margin-top:2px;">${esc(s.year_level)} · ${esc(s.semester)} · ${parseFloat(s.units)||0} units</div>${r?`<div class="adv-reason ${reasonClass}">${r}</div>`:''}${gBadge}</div>`;
        });
        return h+`</div></div>`;
    }
    html+=block(`<i class="fas fa-check-circle"></i> Recommended — Can Enroll`,adv.recommended,'adv-title-rec','adv-reason-rec',s=>s.reason||'Available');
    html+=block(`<i class="fas fa-redo"></i> Must Retake — Failed`,adv.retake,'adv-title-retake','adv-reason-retake',s=>s.reason||'Failed');
    html+=block(`<i class="fas fa-exclamation-triangle"></i> Conditional — Removal Exam Required`,adv.conditional,'adv-title-cond','adv-reason-retake',s=>s.reason||'Grade 4.00');
    html+=block(`<i class="fas fa-lock"></i> Blocked — Prerequisite Not Passed`,adv.blocked,'adv-title-block','adv-reason-block',s=>s.reason||'Prerequisite required');
    html+=block(`<i class="fas fa-graduation-cap"></i> Completed`,adv.completed,'adv-title-done','',s=>'');
    if(!adv.recommended?.length&&!adv.retake?.length&&!adv.conditional?.length&&!adv.blocked?.length&&!adv.completed?.length){
        html=`<div class="empty"><i class="fas fa-inbox"></i><p>No prospectus data found. Configure the department prospectus first.</p></div>`;
    }
    document.getElementById('advBody').innerHTML=html;
}

/* ── FINALIZE ─────────────────────────────────────────────── */
function finalizeEval(){
    if(!currentStudent)return;
    if(!confirm('Finalize this evaluation session? A permanent session record will be created.'))return;
    const notes=document.getElementById('sessionNotes')?document.getElementById('sessionNotes').value:'';
    const fd=new FormData();fd.append('action','finalize_session');fd.append('student_id',currentStudent.id);fd.append('major_id',currentStudent.major_id||0);fd.append('academic_year',document.getElementById('academicYearSelect').value);fd.append('notes',notes);
    fetch(EVAL_PROCESS,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        toast(d.message||'Finalized!',d.success?'success':'error');
        if(d.success&&d.gwa?.gwa){const el=document.getElementById('liveGWA');if(el)el.textContent=parseFloat(d.gwa.gwa).toFixed(2);}
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
        <a href="../../../data/logout.php" style="background:#dc2626;color:#fff;padding:10px 20px;border-radius:10px;text-decoration:none;font-weight:500;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
<?php endif; ?>
</body>
</html>

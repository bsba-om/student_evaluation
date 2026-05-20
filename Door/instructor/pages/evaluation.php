<?php
// evaluation.php — Instructor Panel (Revamped)
require_once '../../../data/session_security.php';
$role_access = check_role_access('instructor');
$show_role_modal = !$role_access['allowed'];
$instructor_id = $_SESSION['user_id'] ?? 1;
$user_name = $_SESSION['user_name'] ?? 'Instructor';
if (!$show_role_modal) { require_once '../../../data/config.php'; }
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
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="evaluation.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
/* ═══════════════════════════════════════════════════════════
   PREMIUM GOLD EVALUATION BUTTON
═══════════════════════════════════════════════════════════ */
.fab-eval-btn {
  position: fixed;
  bottom: 32px;
  right: 32px;
  z-index: 9999;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 14px 26px;
  background: linear-gradient(135deg, #f5c842 0%, #d4a017 35%, #b8860b 70%, #9a6f00 100%);
  color: #fff;
  border: none;
  border-radius: 50px;
  font-family: 'Poppins', sans-serif;
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.5px;
  cursor: pointer;
  box-shadow:
    0 4px 18px rgba(184, 134, 11, 0.55),
    0 2px 6px rgba(0,0,0,0.18),
    inset 0 1px 0 rgba(255,255,255,0.28);
  transition: all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
  border: 1.5px solid rgba(255,255,255,0.22);
  text-shadow: 0 1px 2px rgba(100,60,0,0.35);
}
.fab-eval-btn::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 50px;
  background: linear-gradient(180deg, rgba(255,255,255,0.22) 0%, transparent 60%);
  pointer-events: none;
}
.fab-eval-btn:hover {
  transform: translateY(-3px) scale(1.04);
  box-shadow:
    0 8px 28px rgba(184, 134, 11, 0.7),
    0 4px 12px rgba(0,0,0,0.22),
    inset 0 1px 0 rgba(255,255,255,0.3);
  background: linear-gradient(135deg, #ffd84d 0%, #e0b020 35%, #c4920f 70%, #a87800 100%);
}
.fab-eval-btn:active {
  transform: translateY(0px) scale(0.98);
  box-shadow:
    0 3px 10px rgba(184, 134, 11, 0.45),
    0 1px 4px rgba(0,0,0,0.2);
}
.fab-eval-btn i {
  font-size: 15px;
  filter: drop-shadow(0 1px 2px rgba(100,60,0,0.3));
}
.fab-eval-label {
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.4px;
}

/* ═══════════════════════════════════════════════════════════
   GRADUATION PREMIUM CARD
═══════════════════════════════════════════════════════════ */
.graduation-premium-card {
  position: relative;
  overflow: hidden;
  margin: 8px 0 4px 0;
  padding: 32px 28px 24px 28px;
  background: linear-gradient(145deg, #fff9e6, #fffbf0, #fef3c7);
  border: 2px solid #d4a017;
  border-radius: 20px;
  box-shadow:
    0 12px 40px rgba(212, 160, 23, 0.25),
    0 2px 8px rgba(0,0,0,0.08),
    inset 0 1px 0 rgba(255,255,255,0.9);
  text-align: center;
}
.graduation-premium-card::before {
  content: '';
  position: absolute;
  top: -60px; right: -60px;
  width: 200px; height: 200px;
  background: radial-gradient(circle, rgba(212,160,23,0.12) 0%, transparent 70%);
  pointer-events: none;
}
.graduation-premium-card::after {
  content: '';
  position: absolute;
  bottom: -40px; left: -40px;
  width: 160px; height: 160px;
  background: radial-gradient(circle, rgba(212,160,23,0.08) 0%, transparent 70%);
  pointer-events: none;
}
.graduation-confetti {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background-image:
    radial-gradient(circle, #f5c842 1px, transparent 1px),
    radial-gradient(circle, #d4a017 1px, transparent 1px),
    radial-gradient(circle, rgba(212,160,23,0.4) 1.5px, transparent 1.5px);
  background-size: 60px 60px, 90px 90px, 45px 45px;
  background-position: 10px 10px, 30px 30px, 50px 5px;
  opacity: 0.18;
  pointer-events: none;
  animation: confettiDrift 8s ease-in-out infinite alternate;
}
@keyframes confettiDrift {
  from { background-position: 10px 10px, 30px 30px, 50px 5px; }
  to   { background-position: 20px 25px, 45px 10px, 60px 20px; }
}
.graduation-icon-ring {
  position: relative;
  z-index: 1;
  width: 90px; height: 90px;
  margin: 0 auto 18px auto;
  background: linear-gradient(145deg, #f5c842, #d4a017, #b8860b);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 36px; color: #fff;
  box-shadow:
    0 6px 24px rgba(184,134,11,0.45),
    0 0 0 6px rgba(212,160,23,0.15),
    0 0 0 12px rgba(212,160,23,0.07);
  animation: pulseGold 2.4s ease-in-out infinite;
}
@keyframes pulseGold {
  0%, 100% { box-shadow: 0 6px 24px rgba(184,134,11,0.45), 0 0 0 6px rgba(212,160,23,0.15), 0 0 0 12px rgba(212,160,23,0.07); }
  50% { box-shadow: 0 8px 30px rgba(184,134,11,0.6), 0 0 0 10px rgba(212,160,23,0.2), 0 0 0 18px rgba(212,160,23,0.05); }
}
.graduation-badge-row {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}
.graduation-cap-badge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 5px 14px;
  background: linear-gradient(135deg, #d4a017, #9a6f00);
  color: #fff;
  border-radius: 20px;
  font-size: 11px; font-weight: 700; letter-spacing: 0.5px;
  text-transform: uppercase;
  box-shadow: 0 2px 8px rgba(154,111,0,0.35);
}
.graduation-status-pill {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 5px 14px;
  background: linear-gradient(135deg, #dcfce7, #bbf7d0);
  color: #166534;
  border: 1.5px solid #4ade80;
  border-radius: 20px;
  font-size: 11px; font-weight: 700; letter-spacing: 0.4px;
}
.graduation-lead {
  position: relative; z-index: 1;
  font-size: 13px; color: #92400e; line-height: 1.6;
  margin: 0 0 20px 0;
  max-width: 500px; margin-left: auto; margin-right: auto;
}
.graduation-stat-grid {
  position: relative; z-index: 1;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 14px;
  margin-bottom: 22px;
}
.graduation-stat {
  padding: 16px 12px;
  background: rgba(212,160,23,0.08);
  border: 1.5px solid rgba(212,160,23,0.25);
  border-radius: 14px;
  transition: all 0.2s;
}
.graduation-stat:hover {
  background: rgba(212,160,23,0.14);
  transform: translateY(-2px);
}
.graduation-stat-gold {
  background: linear-gradient(135deg, rgba(212,160,23,0.18), rgba(245,200,66,0.12));
  border-color: rgba(212,160,23,0.45);
}
.graduation-stat-label {
  font-size: 9px; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.7px; color: #a16207; margin-bottom: 6px;
}
.graduation-stat-value {
  font-size: 26px; font-weight: 800; color: #92400e;
  font-family: 'Playfair Display', serif; line-height: 1;
}
.graduation-stat-value.graduation-stat-sm {
  font-size: 14px; font-family: 'Poppins', sans-serif;
  color: #166534; font-weight: 700;
}
.graduation-confirm-btn {
  position: relative; z-index: 1;
  display: inline-flex; align-items: center; gap: 10px;
  padding: 15px 32px;
  background: linear-gradient(135deg, #f5c842 0%, #d4a017 40%, #b8860b 80%, #9a6f00 100%);
  color: #fff;
  border: none; border-radius: 50px;
  font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 700;
  cursor: pointer; letter-spacing: 0.3px;
  box-shadow: 0 6px 24px rgba(184,134,11,0.5), inset 0 1px 0 rgba(255,255,255,0.25);
  transition: all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
  border: 1.5px solid rgba(255,255,255,0.2);
  text-shadow: 0 1px 2px rgba(100,60,0,0.3);
}
.graduation-confirm-btn:hover {
  transform: translateY(-3px) scale(1.03);
  box-shadow: 0 10px 32px rgba(184,134,11,0.65), inset 0 1px 0 rgba(255,255,255,0.28);
  background: linear-gradient(135deg, #ffd84d 0%, #e0b020 40%, #c4920f 80%, #a87800 100%);
}
.graduation-confirm-btn:active {
  transform: translateY(0) scale(0.98);
}
.graduation-confirm-btn:disabled {
  opacity: 0.6; cursor: not-allowed; transform: none;
}
.graduation-footnote {
  position: relative; z-index: 1;
  font-size: 10px; color: #a16207; margin: 12px 0 0 0;
  line-height: 1.5;
}
</style>
</head>
<body>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <img src="../../../media/LOGO.jpg" alt="Logo" class="sidebar-logo"
         style="width:70px;height:70px;border-radius:16px;object-fit:cover;border:3px solid white;background:white;padding:4px;box-shadow:0 4px 12px rgba(0,0,0,.2);">
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
    <a href="students.php" class="sidebar-nav-item"><i class="fas fa-user-graduate"></i><span>Students mentees</span></a>
    <a href="evaluation.php" class="sidebar-nav-item active"><i class="fas fa-comment-dots"></i><span>Evaluation</span></a>
    <a href="setup_graduate.php" class="sidebar-nav-item"><i class="fas fa-award"></i><span>Graduates</span></a>
    <a href="reports.php" class="sidebar-nav-item"><i class="fas fa-file-alt"></i><span>Reports</span></a>
    <a href="profile.php" class="sidebar-nav-item"><i class="fas fa-user"></i><span>Profile</span></a>
  </nav>
</aside>

<div class="main-content">
  <header class="topbar" style="left: 260px !important;">
    <div class="topbar-left">
      <button class="topbar-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
      <div>
        <div class="topbar-title">Student Evaluation</div>
        <div class="topbar-subtitle">Instructor Panel</div>
      </div>
    </div>
    <div class="topbar-right">
      <a href="../../../index.php" class="topbar-return"><i class="fas fa-home"></i><span>Return</span></a>
      <a href="../../../data/logout.php" class="topbar-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
  </header>

  <main class="dashboard-content">
    <div style="position:fixed;top:0;left:260px;right:0;bottom:0;background-image:url('../../../media/LOGO.jpg');background-size:70%;background-position:center;background-repeat:no-repeat;opacity:0.08;pointer-events:none;z-index:0;"></div>
    <div class="page-wrap">

      <!-- HERO BANNER -->
      <div class="hero-banner" style="background:linear-gradient(135deg,#d4a843 0%,#b8922f 40%,#a38023 100%);border-radius:20px;padding:28px 32px;margin-bottom:24px;position:relative;overflow:hidden;display:flex;align-items:flex-start;justify-content:space-between;gap:24px;flex-wrap:wrap;">
        <div style="position:relative;z-index:1;">
          <div class="hero-eyebrow" style="display:flex;align-items:center;gap:8px;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#fff;margin-bottom:8px;">
            <span style="width:24px;height:2px;background:#fff;border-radius:2px;"></span> Instructor Portal | A.Y. 2025-2026
          </div>
          <h1 class="hero-title" style="font-family:'Playfair Display',serif;font-size:32px;font-weight:800;color:#fff;line-height:1.1;margin-bottom:6px;"><em style="color:#2d1f07;font-style:normal;">My Mentees</em></h1>
          <p class="hero-sub" style="font-size:13px;color:rgba(255,255,255,.85);max-width:300px;">Select a student to open their evaluation prospectus</p>
        </div>
        <div style="display:flex;flex-direction:column;gap:12px;align-items:flex-end;position:relative;z-index:1;">
          <div class="hero-search" style="min-width:220px;">
            <i class="fas fa-search"></i>
            <input type="text" id="menteeSearch" placeholder="Search by name, ID, major…" oninput="filterMentees()" onkeyup="if(event.key==='Enter'){const first=document.querySelector('.mentee-card:not([style*=none])');if(first){first.click();}}">
          </div>
          <div class="year-filter-btns" style="display:flex;gap:6px;flex-wrap:wrap;">
            <button class="year-btn active" data-year="all" onclick="filterMenteeYear('all')">All Years</button>
            <button class="year-btn" data-year="1" onclick="filterMenteeYear('1')">1st Year</button>
            <button class="year-btn" data-year="2" onclick="filterMenteeYear('2')">2nd Year</button>
            <button class="year-btn" data-year="3" onclick="filterMenteeYear('3')">3rd Year</button>
            <button class="year-btn" data-year="4" onclick="filterMenteeYear('4')">4th Year</button>
          </div>
        </div>
      </div>

      <!-- STATS ROW -->
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:22px;" id="statsRow"></div>

      <div class="card" style="padding:20px;">
        <div id="menteesContainer">
          <div class="empty-state">
            <div class="spinner" style="font-size:0;width:36px;height:36px;margin:0 auto 12px;"></div>
            <p>Loading mentees…</p>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- EVALUATION OVERLAY -->
<div class="overlay" id="evalOverlay">
  <div class="eval-panel">
    <div class="eval-hdr">
      <div>
        <div class="eval-hdr-name" id="evalName">—</div>
        <div class="eval-hdr-sub" id="evalSub">—</div>
      </div>
      <div class="eval-hdr-actions">
        <button class="hdr-btn hdr-btn-solid" onclick="printProspectus()"><i class="fas fa-print"></i> Print</button>
        <button class="hdr-close" onclick="closeEval()"><i class="fas fa-times"></i></button>
      </div>
    </div>
     <div class="eval-tabs">
       <button class="eval-tab active" id="tab-prospectus" onclick="switchEvalTab('prospectus')">
         <i class="fas fa-scroll"></i> Prospectus
       </button>
      </div>
     <div class="eval-body" id="tab-prospectus-body">
       <div class="empty-state"><div class="spinner"></div></div>
     </div>
   </div>
</div>

<!-- RESULT MODAL -->
<div class="result-modal-overlay" id="resultModal">
  <div class="result-modal" id="resultModalInner">
    <button class="rm-close" onclick="closeResultModal()"><i class="fas fa-times"></i></button>
    <div id="resultModalContent"></div>
  </div>
</div>

<!-- GRADES VIEW MODAL -->
<!-- GRADES VIEW MODAL -->
<div class="grades-modal-overlay" id="gradesModal">
  <div class="grades-modal">
    <div style="background:linear-gradient(145deg,var(--gold-d),#a87120);padding:14px 20px;color:#fff;border-radius:14px 14px 0 0;margin:-20px -20px 0 -20px;text-align:center;">
      <div style="font-size:15px;font-weight:700;font-family:'Playfair Display',serif;" id="gmSemesterTitle">—</div>
      <div style="font-size:12px;opacity:.9;margin-top:2px;" id="gmStudentName">—</div>
    </div>
    <div class="gm-body" style="padding-top:0;">
      <div class="gm-school-header" id="gmSchoolHeader" style="display:none;padding:14px 18px;background:var(--cream);border-bottom:2px solid var(--gold);margin-bottom:12px;text-align:center;">
        <img src="../../../media/LOGO.jpg" style="width:50px;height:50px;object-fit:contain;border-radius:10px;border:2px solid var(--gold-d);margin-bottom:10px;display:block;margin-left:auto;margin-right:auto;" alt="Logo">
        <div style="font-size:10px;color:var(--gold-d);font-weight:700;text-transform:uppercase;letter-spacing:.5px;" id="gmSchoolName"></div>
        <div style="font-size:9px;color:var(--muted);" id="gmSchoolAddress"></div>
        <div style="font-size:10px;font-weight:700;color:var(--gold-d);margin-top:3px;" id="gmInstitute"></div>
        <div style="font-size:9px;color:var(--mid);" id="gmDegree"></div>
      </div>
      <div class="gm-student-info" id="gmStudentInfo" style="display:none;padding:10px 14px;background:linear-gradient(135deg,#fff,#fafaf8);border-radius:10px;margin-bottom:12px;border:1px solid rgba(184,134,11,.2);box-shadow:0 2px 10px rgba(184,134,11,.1);">
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:14px;font-size:11px;justify-content:center;margin-bottom:8px;">
          <div style="display:flex;align-items:center;gap:4px;"><span style="font-weight:700;color:var(--gold-d);">Student:</span> <span style="color:var(--dark);font-weight:600;" id="gmInfoStudent"></span></div>
          <div style="display:flex;align-items:center;gap:4px;"><span style="font-weight:700;color:var(--gold-d);">Student ID:</span> <span style="color:var(--dark);font-weight:500;" id="gmInfoStudentID"></span></div>
          <div style="display:flex;align-items:center;gap:4px;"><span style="font-weight:700;color:var(--gold-d);">Year:</span> <span style="color:var(--dark);font-weight:500;" id="gmInfoYearLevel"></span></div>
          <div style="display:flex;align-items:center;gap:4px;"><span style="font-weight:700;color:var(--gold-d);">Semester:</span> <span style="color:var(--dark);font-weight:500;" id="gmInfoSemester"></span></div>
        </div>
        <div style="text-align:center;font-size:12px;font-weight:700;padding:8px 12px;background:var(--gold-d);color:#fff;border-radius:8px;" id="gmInfoPeriod"></div>
      </div>
      <div class="gm-table-wrap">
        <table class="gm-table">
          <thead>
            <tr>
              <th class="gm-th" style="background:var(--gold-d);color:#fff;">Subject Code</th>
              <th class="gm-th" style="background:var(--gold-d);color:#fff;">Subject Name</th>
              <th class="gm-th" style="background:var(--gold-d);color:#fff;text-align:center;">Units</th>
              <th class="gm-th" style="background:var(--gold-d);color:#fff;text-align:center;">Grade</th>
            </tr>
          </thead>
          <tbody id="gmTableBody"></tbody>
        </table>
      </div>
      <div id="gmEmptyState" class="gm-empty-td" style="display:none;">No grades recorded for this period</div>
      <div class="gm-summary" id="gmSummary" style="display:none;padding:12px 16px;background:linear-gradient(145deg,var(--gold-d),#a87120);border-radius:12px;margin-top:14px;box-shadow:0 4px 12px rgba(184,134,11,.3);">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
          <span style="font-size:12px;font-weight:600;color:#fff;" id="gmSummaryLeft"></span>
          <span style="font-size:14px;font-weight:700;color:#fff;font-family:'Playfair Display',serif;background:rgba(255,255,255,.15);padding:4px 12px;border-radius:20px;" id="gmSummaryGWA"></span>
        </div>
      </div>
      <div class="gm-sig-block" id="gmSigBlock" style="display:none;margin-top:16px;padding-top:14px;border-top:2px solid var(--border);">
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:20px;text-align:center;">
          <div style="font-size:10px;">
            <div style="font-size:14px;font-weight:700;color:var(--dark);margin-bottom:-5mm;" id="gmSigAdvisor"></div>
            <div style="border-bottom:1.5px solid var(--dark);height:24px;margin-bottom:6px;"></div>
            <div style="font-weight:700;color:var(--gold-d);margin-bottom:4px;">Adviser's Signature</div>
            <div style="font-size:9px;color:var(--muted);">Date: ___________________</div>
          </div>
          <div style="font-size:10px;">
            <div style="font-size:14px;font-weight:700;color:var(--dark);margin-bottom:-5mm;" id="gmSigPH"></div>
            <div style="border-bottom:1.5px solid var(--dark);height:24px;margin-bottom:6px;"></div>
            <div style="font-weight:700;color:var(--gold-d);margin-bottom:4px;">Program Head's Signature</div>
            <div style="font-size:9px;color:var(--muted);">Date: ___________________</div>
          </div>
        </div>
      </div>
    </div>
     <div class="gm-footer">
       <div class="gm-hint" id="gmHint"></div>
       <button class="btn btn-gold" onclick="printGradesModal()"><i class="fas fa-print"></i> Print</button>
       <button class="btn-modal-close" onclick="closeGradesModal()"><i class="fas fa-times"></i> Close</button>
     </div>
  </div>
</div>

 <div id="printTarget" style="display:none;"></div>

<!-- GRADUATION PROGRESS MODAL -->
<!-- ═══════════════════════════════════════════════════════════════
     GRADUATION PROGRESS MODAL  (animated step-by-step loading)
     ═══════════════════════════════════════════════════════════════ -->
<style>
@keyframes gpSpin   { to { transform: rotate(360deg); } }
@keyframes gpPulse  { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.65;transform:scale(0.93)} }
@keyframes gpStepIn { from{opacity:0;transform:translateX(-8px)} to{opacity:1;transform:translateX(0)} }
@keyframes gpIconBounce { 0%,100%{transform:translateY(0)} 40%{transform:translateY(-7px)} 70%{transform:translateY(-3px)} }
@keyframes gpDonePop { 0%{transform:scale(0) rotate(-30deg);opacity:0} 60%{transform:scale(1.2) rotate(5deg)} 100%{transform:scale(1) rotate(0);opacity:1} }
@keyframes gpBarShimmer {
  0%   { background-position: -200px 0; }
  100% { background-position: calc(200px + 100%) 0; }
}
#graduationProgressModal {
  position: fixed; top:0; left:0; right:0; bottom:0;
  background: rgba(10,8,3,0.82);
  backdrop-filter: blur(4px);
  display: none;
  z-index: 10000;
  align-items: center;
  justify-content: center;
}
.gpm-card {
  background: #fff;
  border-radius: 24px;
  padding: 36px 32px 30px;
  text-align: center;
  box-shadow: 0 24px 80px rgba(0,0,0,0.38), 0 4px 16px rgba(0,0,0,0.12);
  max-width: 440px; width: 92%;
  position: relative; overflow: hidden;
}
.gpm-card::before {
  content:'';
  position:absolute; top:-60px; right:-60px;
  width:200px; height:200px;
  background: radial-gradient(circle, rgba(212,160,23,0.13) 0%, transparent 70%);
  pointer-events:none;
}
.gpm-icon-wrap {
  width: 84px; height: 84px;
  margin: 0 auto 20px;
  background: linear-gradient(145deg, #f5c842, #d4a017, #b8860b);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 34px; color: #fff;
  box-shadow: 0 6px 24px rgba(184,134,11,0.45), 0 0 0 8px rgba(212,160,23,0.12);
  animation: gpIconBounce 1.8s ease-in-out infinite;
}
.gpm-icon-wrap.done {
  background: linear-gradient(145deg, #4ade80, #16a34a);
  box-shadow: 0 6px 24px rgba(22,163,74,0.5), 0 0 0 8px rgba(74,222,128,0.15);
  animation: gpDonePop 0.55s cubic-bezier(.34,1.56,.64,1) both;
}
.gpm-title {
  font-size: 19px; font-weight: 800; color: #1a1a1a;
  margin: 0 0 6px; font-family: 'Playfair Display', serif;
}
.gpm-subtitle {
  font-size: 12px; color: #888; margin: 0 0 22px; line-height: 1.5;
  min-height: 18px; transition: color .3s;
}
/* progress bar */
.gpm-bar-wrap {
  background: #f0f0f0; border-radius: 12px;
  overflow: hidden; height: 9px; margin-bottom: 8px;
}
#graduationProgressBar {
  height: 100%; width: 0%;
  background: linear-gradient(90deg, #f5c842 0%, #d4a017 50%, #b8860b 100%);
  background-size: 200px 100%;
  transition: width 0.38s cubic-bezier(.4,0,.2,1);
  animation: gpBarShimmer 1.4s linear infinite;
}
#graduationProgressBar.done-bar {
  background: linear-gradient(90deg, #4ade80, #16a34a);
  animation: none;
}
.gpm-pct {
  font-size: 11px; font-weight: 700; color: #b8860b;
  margin-bottom: 20px; letter-spacing: .3px;
}
/* step list */
.gpm-steps {
  text-align: left;
  background: #fafafa;
  border: 1px solid #f0ebe0;
  border-radius: 14px;
  padding: 14px 16px;
  display: flex; flex-direction: column; gap: 10px;
}
.gpm-step {
  display: flex; align-items: center; gap: 11px;
  font-size: 12px; color: #999;
  transition: color .25s;
  animation: gpStepIn .3s ease both;
}
.gpm-step.active  { color: #92400e; font-weight: 600; }
.gpm-step.done    { color: #15803d; font-weight: 600; }
.gpm-step-icon {
  flex-shrink: 0;
  width: 26px; height: 26px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px;
  background: #eee; color: #bbb;
  transition: background .3s, color .3s, box-shadow .3s;
}
.gpm-step.active .gpm-step-icon {
  background: linear-gradient(135deg, #f5c842, #d4a017);
  color: #fff;
  box-shadow: 0 2px 8px rgba(212,160,23,0.45);
}
.gpm-step.done .gpm-step-icon {
  background: linear-gradient(135deg, #4ade80, #16a34a);
  color: #fff;
  box-shadow: 0 2px 8px rgba(22,163,74,0.35);
}
.gpm-step-spin { animation: gpSpin .7s linear infinite; }
.gpm-step-label { line-height: 1.3; }
.gpm-step-sub   { font-size: 10px; color: #aaa; margin-top: 1px; font-weight:400; }
.gpm-step.active .gpm-step-sub { color: #b8860b; }
.gpm-step.done  .gpm-step-sub  { color: #16a34a; }
</style>

<div id="graduationProgressModal" style="display:none;">
  <div class="gpm-card">
    <div class="gpm-icon-wrap" id="gpmIconWrap">
      <i class="fas fa-graduation-cap" id="gpmIcon"></i>
    </div>
    <h2 class="gpm-title" id="gpmTitle">Confirming Graduation</h2>
    <p  class="gpm-subtitle" id="gpmSubtitle">Please wait while we set everything up…</p>
    <div class="gpm-bar-wrap">
      <div id="graduationProgressBar"></div>
    </div>
    <div class="gpm-pct"><span id="progressPercentage">0%</span></div>
    <div class="gpm-steps" id="progressSteps">
      <div class="gpm-step active" id="step1">
        <div class="gpm-step-icon"><i class="fas fa-folder-plus gpm-step-spin"></i></div>
        <div class="gpm-step-label">
          Creating folder structure
          <div class="gpm-step-sub" id="step1sub">C:\graduate\batch …</div>
        </div>
      </div>
      <div class="gpm-step" id="step2">
        <div class="gpm-step-icon"><i class="fas fa-file-pdf"></i></div>
        <div class="gpm-step-label">
          Generating prospectus PDF
          <div class="gpm-step-sub">Building official document layout…</div>
        </div>
      </div>
      <div class="gpm-step" id="step3">
        <div class="gpm-step-icon"><i class="fas fa-save"></i></div>
        <div class="gpm-step-label">
          Saving PDF to disk
          <div class="gpm-step-sub">Writing file to local drive…</div>
        </div>
      </div>
      <div class="gpm-step" id="step4">
        <div class="gpm-step-icon"><i class="fas fa-database"></i></div>
        <div class="gpm-step-label">
          Updating graduation records
          <div class="gpm-step-sub">Locking evaluation &amp; saving record…</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast">
  <div class="toast-icon" id="toastIcon"><i class="fas fa-check"></i></div>
  <span id="toastMsg"></span>
</div>

<script>
const EVAL_PROC = new URL('../../../data/evaluation_process.php', location.href).href;
</script>
<script src="../../../function/dashboard.js"></script>
<script src="js/student_type_handler.js"></script>
<script src="js/evaluation_common.js"></script>
<script src="js/transfer_evaluation.js"></script>
<script src="js/non_ibm_evaluation.js"></script>
<script>
/* ═══════════════════════════════════════════════════════════
   CONSTANTS
══════════════════════════════════════════════════════════ */
const VALID_GRADES = [1.00,1.25,1.50,1.75,2.00,2.25,2.50,2.75,3.00,4.00,5.00];
const GRADE_LABELS = {
  1.00:'Excellent',1.25:'Very Good',1.50:'Very Good',1.75:'Good',
  2.00:'Satisfactory',2.25:'Fair',2.50:'Passing',2.75:'Low Passing',
  3.00:'Barely Passing',4.00:'Conditional',5.00:'Failed'
};
const YEAR_ORDER  = ['1st Year','2nd Year','3rd Year','4th Year','Bridging'];
const YEAR_NUM    = {'1st Year':1,'2nd Year':2,'3rd Year':3,'4th Year':4};
const SEM_NUM     = {'1st Semester':1,'2nd Semester':2};
const YEAR_LABELS = ['1st Year','2nd Year','3rd Year','4th Year'];

let phSettings = {
  school_name:'Northern Bukidnon State College',
  school_address:'Manolo Fortich, Bukidnon',
  institute_name:'Institute for Business Management',
  degree_name:'Bachelor of Science in Business Administration'
};

let currentStudent = null;
let _gradConfirmBtn = null;   // graduationConfirmBtn — shared scope so _graduateRegenerateFlow can also touch it
let loadedSubjects  = [];
let prereqSetsData  = [];
let gradeMap        = {};
let currentAY       = '2025-2026';
let focusYear       = '';
let focusSem        = '';
let graduationRedirectOnClose = false;
let finalizedMap    = {};
/* Set of subject IDs that have been manually unlocked (via password verification)
   so an instructor can edit a single row that would otherwise be locked. */
let manuallyUnlockedSubjects = new Set();

/* Holds the full list of subject IDs confirmed via rmConfirmEnrollmentList.
   promoteStudent reads this to (a) pass it to the server so the enrollment
   list is persisted, and (b) mark those subjects as in-load locally so
   grade inputs are immediately editable after promotion without a reload. */
let confirmedEnrollmentSubjectIds = [];

/* Returns true iff the given subject belongs to the student's CURRENT
   year+semester (based on currentStudent.year_level). Used to gate which
   rows are editable during an ongoing evaluation. */
function isSubjectInCurrentPeriod(sub) {
  if (!currentStudent || !sub) return false;
  const {yr, sem} = parseStudentStanding(currentStudent.year_level || '1st Year - 1st Semester');
  const curYear = YEAR_LABELS[yr - 1] || '1st Year';
  const curTok  = sem === 1 ? '1st' : '2nd';
  const subYear = (sub.year_level || '').trim();
  const subSem  = (sub.semester || '').toLowerCase();
  return subYear === curYear && subSem.includes(curTok);
}

/* Strips the " - Xst/nd/rd/th Semester" suffix from a year_level string so
   only the year portion is shown in display fields (e.g. "4th Year - 2nd Semester"
   → "4th Year").  The raw value must be preserved for parseStudentStanding. */
function yearLevelOnly(yearLevelStr) {
  if (!yearLevelStr) return '—';
  return String(yearLevelStr).replace(/\s*[-–]\s*\d+(st|nd|rd|th)\s*Semester.*/i, '').trim();
}

/* ═══════════════════════════════════════════════════════════
   GRADE HELPERS
═══════════════════════════════════════════════════════════ */
function roundGrade(r) {
  let c = 5.00, d = 99;
  VALID_GRADES.forEach(v => { const x = Math.abs(r-v); if(x<d){d=x;c=v;} });
  return c;
}
function gradeStatus(g) {
  if(g <= 3.00) return 'passed';
  if(g === 4.00) return 'conditional';
  return 'failed';
}
function gradeLabel(g)  { return GRADE_LABELS[g] || '—'; }
function gClass(s)      { return s==='passed'?'gp':s==='failed'?'gf':s==='conditional'?'gc':''; }
function pillClass(s)   { return 'gpill ' + gClass(s||''); }
function statusText(s)  { return {passed:'Passed',failed:'Failed',conditional:'Cond.',not_taken:'—',incomplete:'Inc.'}[s]||'—'; }

/* ═══════════════════════════════════════════════════════════
   PREREQUISITE LOGIC
═══════════════════════════════════════════════════════════ */
function normalizePrereqCode(code) {
  return String(code||'').replace(/\s+/g,'').trim().toUpperCase();
}
function parsePrereqCodes(value) {
  return String(value||'')
    .split(/\s*(?:[;,\/\|]|\u2013|\u2014)\s*|\s+and\s+|\s+or\s+/i)
    .map(normalizePrereqCode)
    .filter(Boolean);
}
function buildPrereqUnlockMap(subjects, gMap, prereqSets, studentMajorId) {
  const byCode = {};
  subjects.forEach(s => {
    const codeKey = normalizePrereqCode(s.subject_code);
    if(codeKey) byCode[codeKey] = s;
  });
  const byId = {};
  subjects.forEach(s => { byId[s.id] = s; });

  const setPrereqs = {};
  if(Array.isArray(prereqSets)) {
    prereqSets.forEach(set => {
      if(set.major_id && parseInt(set.major_id) !== parseInt(studentMajorId)) return;
      if(!set.target_subject_id) return;
      const tid = parseInt(set.target_subject_id);
      if(!setPrereqs[tid]) setPrereqs[tid] = [];
      (set.subjects||[]).forEach(ps => {
        const found = byId[ps.id] || subjects.find(s => normalizePrereqCode(s.subject_code) === normalizePrereqCode(ps.subject_code));
        if(found) setPrereqs[tid].push(found);
      });
    });
  }

  const result = {};
  subjects.forEach(s => {
    const prereqField = s.display_prerequisite || s.prerequisite || s.prerequisite_subject_code || '';
    const prereqCodes = parsePrereqCodes(prereqField);
    const prereqSubjects = prereqCodes.map(code => byCode[code]).filter(Boolean);
    let directLocked = false;
    let directPrereqSubj = prereqSubjects.length ? prereqSubjects[0] : null;
    if(prereqSubjects.length) {
      directLocked = prereqSubjects.some(ps => {
        const pg = gMap[ps.id];
        return !(pg != null && gradeStatus(roundGrade(pg)) === 'passed');
      });
    }

    const setPrereqList = setPrereqs[parseInt(s.id)] || [];
    let setLocked = false, setBlockedBy = [];
    setPrereqList.forEach(ps => {
      const pg = gMap[ps.id];
      if(!(pg != null && gradeStatus(roundGrade(pg)) === 'passed')) { setLocked = true; setBlockedBy.push(ps); }
    });
    result[s.id] = {
      unlocked: !(directLocked || setLocked),
      directPrereqCode: prereqCodes.join(', ') || null,
      directPrereqSubj,
      directPrereqSubjects: prereqSubjects,
      directLocked,
      setLocked, setBlockedBy, setPrereqList
    };
  });
  return result;
}

function parseStudentStanding(yearLevelStr) {
  let yr = 1, sem = 1;
  const m = yearLevelStr.match(/(\d+)(st|nd|rd|th)\s*Year/i);
  if(m) yr = parseInt(m[1]);
  if(/2nd\s*Sem/i.test(yearLevelStr)) sem = 2;
  return {yr, sem};
}
function getNextSemester(yr, sem) {
  return sem === 1 ? {yr, sem:2} : {yr:yr+1, sem:1};
}

/* ═══════════════════════════════════════════════════════════
   TOAST
═══════════════════════════════════════════════════════════ */
function toast(msg, type='info', dur=3200) {
  const el = document.getElementById('toast');
  const ic = document.getElementById('toastIcon');
  document.getElementById('toastMsg').textContent = msg;
  ic.innerHTML = `<i class="fas ${({success:'fa-check-circle',error:'fa-times-circle',info:'fa-info-circle'})[type]||'fa-info-circle'}"></i>`;
  el.className = `toast ${type} show`;
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('show'), dur);
}

/* ═══════════════════════════════════════════════════════════
   LOAD MENTEES
═══════════════════════════════════════════════════════════ */
function loadMentees() {
  const fd = new FormData(); fd.append('action','get_mentees');
  fetch(EVAL_PROC,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    const c = document.getElementById('menteesContainer');
    if(!d.success || !(d.mentees && d.mentees.length)) {
      c.innerHTML = `<div class="empty-state"><i class="fas fa-users"></i><h3>No mentees assigned</h3><p>No mentees are currently assigned to you.</p></div>`;
      return;
    }
    const total = d.mentees.length;
    const graded = d.mentees.filter(m=>m.graded_count>0).length;
    const done   = d.mentees.filter(m=>m.graded_count>0&&m.graded_count>=m.total_subjects).length;
    document.getElementById('statsRow').innerHTML = `
      <div class="card" style="text-align:center;padding:18px 14px;margin-bottom:0;border:1px solid rgba(184,134,11,.12);box-shadow:0 4px 16px rgba(184,134,11,.08);"><div style="font-size:28px;font-weight:800;color:var(--gold-d);font-family:'Playfair Display',serif;text-shadow:0 2px 4px rgba(184,134,11,.2);">${total}</div><div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-top:4px;font-weight:600;">Total Mentees</div></div>
      <div class="card" style="text-align:center;padding:18px 14px;margin-bottom:0;border:1px solid rgba(22,163,74,.12);box-shadow:0 4px 16px rgba(22,163,74,.08);"><div style="font-size:28px;font-weight:800;color:var(--green);font-family:'Playfair Display',serif;">${done}</div><div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-top:4px;font-weight:600;">Fully Evaluated</div></div>
      <div class="card" style="text-align:center;padding:18px 14px;margin-bottom:0;border:1px solid rgba(29,78,216,.12);box-shadow:0 4px 16px rgba(29,78,216,.08);"><div style="font-size:28px;font-weight:800;color:var(--blue);font-family:'Playfair Display',serif;">${graded}</div><div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-top:4px;font-weight:600;">In Progress</div></div>
      <div class="card" style="text-align:center;padding:18px 14px;margin-bottom:0;border:1px solid rgba(217,119,6,.12);box-shadow:0 4px 16px rgba(217,119,6,.08);"><div style="font-size:28px;font-weight:800;color:var(--amber);font-family:'Playfair Display',serif;">${total-graded}</div><div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-top:4px;font-weight:600;">Not Started</div></div>`;
    let html = `<div class="mentee-grid" id="menteeGrid">`;
    d.mentees.forEach(m => {
      const full = `${m.first_name}${m.middle_name?' '+m.middle_name:''} ${m.last_name}${m.suffix?' '+m.suffix:''}`.trim();
      const init = (m.avatar_initials || (m.first_name[0]+(m.last_name && m.last_name[0] ? m.last_name[0] : '')).toUpperCase()).trim();
      const pct  = m.total_subjects>0 ? Math.round(m.graded_count/m.total_subjects*100) : 0;
      const yrNum = (m.year_level||'0').replace(/[^0-9]/g,'');
      const semester = (m.year_level||'').includes('2nd Semester') ? '2nd Semester' : '1st Semester';
      html += `<div class="mentee-card" onclick='openEval(${JSON.stringify(m).replace(/'/g,"&#39;")})' data-name="${esc(full.toLowerCase())}" data-id="${esc((m.student_number||'').toLowerCase())}" data-major="${esc((m.major_name||'').toLowerCase())}" data-year="${yrNum||'0'}" data-semester="${semester}">
        <div class="mc-top">
          <div class="mc-avatar" style="background:linear-gradient(135deg,${esc(m.avatar_gradient_from||'#3b82f6')},${esc(m.avatar_gradient_to||'#60a5fa')});">${esc(init)}</div>
          <div><div class="mc-name">${esc(full)}</div><div class="mc-sub">${esc(m.student_number||'—')} &nbsp;·&nbsp; ${esc(m.major_name||'No major')}</div></div>
        </div>
        <div class="mc-bottom">
          <div class="mc-pills">
            <span class="pill pill-blue"><i class="fas fa-layer-group" style="font-size:9px;margin-right:3px;"></i>${esc(yearLevelOnly(m.year_level)||'—')}</span>
            ${m.major_name?`<span class="pill pill-gold">${esc(m.major_name)}</span>`:''}
            <span class="pill ${m.graded_count>0?'pill-green':'pill-gray'}"><i class="fas fa-star" style="font-size:9px;margin-right:3px;"></i>${m.graded_count}/${m.total_subjects} graded</span>
            ${m.status === 'graduated' ? '<span class="pill pill-graduated"><i class="fas fa-user-graduate" style="font-size:9px;margin-right:3px;"></i>Graduated</span>' : ''}
          </div>
          <div class="mc-progress-track"><div class="mc-progress-bar" style="width:${pct}%;"></div></div>
          <div class="mc-progress-label"><span>${pct}% evaluated</span><span>${m.graded_count} of ${m.total_subjects}</span></div>
        </div>
        <button type="button" class="mc-action evaluate-main-btn"><i class="fas fa-clipboard-check"></i><span>Evaluation</span></button>
      </div>`;
    });
    c.innerHTML = html + '</div>';
  });
}
loadMentees();

function filterMentees() { applyFilters(); }
let currentYearFilter = 'all';
let currentSemFilter  = 'all';

function filterMenteeYear(y) {
  currentYearFilter = y;
  document.querySelectorAll('.year-btn').forEach(b => b.classList.toggle('active', b.dataset.year === y));
  applyFilters();
}
function filterMenteeSem(s) {
  currentSemFilter = s;
  document.querySelectorAll('.sem-btn').forEach(b => {
    const isActive = b.dataset.sem === s;
    b.style.background = isActive ? 'rgba(255,255,255,0.25)' : 'transparent';
    b.style.borderColor = isActive ? 'rgba(255,255,255,0.7)' : 'rgba(255,255,255,0.3)';
    b.style.color = isActive ? '#fff' : 'rgba(255,255,255,0.85)';
    b.style.fontWeight = isActive ? '700' : '600';
  });
  applyFilters();
}
function applyFilters() {
  const q = document.getElementById('menteeSearch').value.toLowerCase();
  document.querySelectorAll('.mentee-card').forEach(c => {
    const matchSearch   = q === '' || (c.dataset.name||'').includes(q) || (c.dataset.id||'').includes(q) || (c.dataset.major||'').includes(q);
    const matchYear     = currentYearFilter === 'all' || (c.dataset.year||'0') === currentYearFilter;
    const matchSemester = currentSemFilter  === 'all' || (c.dataset.semester||'') === currentSemFilter;
    c.style.display = (matchSearch && matchYear && matchSemester) ? '' : 'none';
  });
}



 /* ═══════════════════════════════════════════════════════════
    TAB SWITCHER
 ═══════════════════════════════════════════════════════════ */
  function switchEvalTab(tab) {
    ['prospectus'].forEach(t => {
      const tabEl = document.getElementById(`tab-${t}`);
      const bodyEl = document.getElementById(`tab-${t}-body`);
      if(tabEl) tabEl.classList.toggle('active', t === tab);
      if(bodyEl) bodyEl.style.display = t === tab ? 'block' : 'none';
    });
  }
 
 /* ═══════════════════════════════════════════════════════════
    OPEN / CLOSE EVAL
 ═══════════════════════════════════════════════════════════ */
/* ═══════════════════════════════════════════════════════════
   STUDENT TYPE STATE
═══════════════════════════════════════════════════════════ */
let currentStudentType = null; // 'regular' | 'transfer' | 'non_ibm'

function openEval(m) {
  if(typeof m === 'string') m = JSON.parse(m);

  // Check if student type already saved in database (m.student_type)
  if (m.student_type) {
    currentStudentType = m.student_type;
    _proceedWithEval(m, m.student_type);
    return;
  }

  // Check sessionStorage as fallback (in case DB save hasn't propagated yet)
  const sessionType = StudentTypeHandler.getType(m.id);
  if (sessionType) {
    currentStudentType = sessionType;
    _proceedWithEval(m, sessionType);
    return;
  }

  // Show student type selection modal
  StudentTypeHandler.showTypeSelection(m, function(type, student) {
    currentStudentType = type;
    _proceedWithEval(student, type);
  });
}

function _proceedWithEval(m, studentType) {
  currentStudent = m; gradeMap = {}; loadedSubjects = []; prereqSetsData = [];
  focusYear = ''; focusSem = ''; finalizedMap = {};

  // First, fetch the evaluation data (we need subjects for transfer/non-IBM workflows)
  const fd1 = new FormData(); fd1.append('action','get_student_evaluation'); fd1.append('student_id',m.id); fd1.append('academic_year',currentAY);
  const fd2 = new FormData(); fd2.append('action','get_prereq_sets');

  // Show a loading state in the overlay
  document.getElementById('evalOverlay').classList.add('open');
  switchEvalTab('prospectus');
  const full = `${m.first_name}${m.middle_name?' '+m.middle_name:''} ${m.last_name}${m.suffix?' '+m.suffix:''}`.trim();
  document.getElementById('evalName').innerHTML = full + _getTypeBadgeHTML(studentType);
  document.getElementById('evalSub').textContent  = `${m.major_name||'No major'} · ${yearLevelOnly(m.year_level)||'—'} · A.Y. ${currentAY}`;
  document.getElementById('tab-prospectus-body').innerHTML = `<div class="empty-state"><div class="spinner"></div><p style="margin-top:12px;">Loading prospectus…</p></div>`;

  Promise.all([
    fetch(EVAL_PROC,{method:'POST',body:fd1}).then(r=>r.json()),
    fetch('../../../data/major_process.php',{method:'POST',body:fd2}).then(r=>r.json())
  ]).then(([evalData, prereqData]) => {
    if(!evalData.success) {
      document.getElementById('tab-prospectus-body').innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error</h3><p>${esc(evalData.message)}</p></div>`;
      return;
    }
    if(evalData.ph_settings) {
      phSettings = {...phSettings, ...evalData.ph_settings};
      phSettings.school_name = phSettings.school_name || phSettings.schoolName || '';
      phSettings.school_address = phSettings.school_address || phSettings.schoolAddress || '';
      phSettings.institute_name = phSettings.institute_name || phSettings.instituteName || '';
      phSettings.degree_name = phSettings.degree_name || phSettings.degreeName || '';
    }
    if (evalData.student) {
      currentStudent = {...m, ...evalData.student};
    }
    prereqSetsData = (prereqData.success && prereqData.sets) || [];

    // Store subjects temporarily for workflow modals
    const tempSubjects = evalData.subjects || [];

     if (studentType === 'transfer') {
       // ★ Check if transfer evaluation workflow has already been completed
       // by looking for credited subjects or saved grades from the server data.
       // This prevents re-showing the 4-step modal after browser close / logout.
       const hasCreditedSubjects = tempSubjects.some(s => s.is_credited);
       const hasSavedGrades = tempSubjects.some(s => s.grade_rounded != null);

       if (hasCreditedSubjects || hasSavedGrades) {
         // Transfer evaluation already completed — skip the 4-step modal entirely
         // Build previousSubjects from server data for visual application
         const previousSubjects = {};
         tempSubjects.forEach(s => {
           if (s.is_credited && s.grade_rounded != null) {
             previousSubjects[s.id] = {
               grade: parseFloat(s.grade_rounded),
               validated: true
             };
           }
         });

         // Ensure credited flag is set on evalData.subjects
         Object.keys(previousSubjects).forEach(sid => {
           const ps = previousSubjects[sid];
           if (ps.grade && ps.validated && parseFloat(ps.grade) <= 3.00) {
             const sub = evalData.subjects.find(s => s.id == sid);
             if (sub) sub.is_credited = true;
           }
         });

         renderProspectus(evalData);
         _applyTransferVisuals(previousSubjects);
       } else {
         // First time — show the transfer evaluation modal
         const skipped = TransferEvaluation.init(m, tempSubjects, function(previousSubjects, currentLoad) {
           document.getElementById('evalOverlay').classList.add('open');
           TransferEvaluation.applyCreditsToGradeMap(gradeMap);

           Object.keys(previousSubjects).forEach(sid => {
             const ps = previousSubjects[sid];
             if (ps.grade && ps.validated && parseFloat(ps.grade) <= 3.00) {
               const sub = evalData.subjects.find(s => s.id == sid);
               if (sub) sub.is_credited = true;
             }
           });

           renderProspectus(evalData);
           _applyTransferVisuals(previousSubjects);
         });
         if (!skipped) {
           document.getElementById('evalOverlay').classList.remove('open');
         }
       }
     } else if (studentType === 'non_ibm') {
       // ★ Check if non-IBM evaluation workflow has already been completed
       const hasInLoadSubjects = tempSubjects.some(s => s.is_in_load === true || s.is_in_load === 1 || s.is_in_load === '1');
       const hasSavedGrades = tempSubjects.some(s => s.grade_rounded != null);

       if (hasInLoadSubjects || hasSavedGrades) {
         // Non-IBM evaluation already completed — skip modal, render directly
         evalData.subjects.forEach(sub => {
           sub.is_in_load = !!(sub.is_in_load === true || sub.is_in_load === 1 || sub.is_in_load === '1');
         });
         renderProspectus(evalData);
         setTimeout(() => {
           if (typeof NonIBMEvaluation !== 'undefined') NonIBMEvaluation.applyRestrictions();
         }, 200);
       } else {
         // First time — show the non-IBM evaluation modal
         const skipped = NonIBMEvaluation.init(m, tempSubjects, function(subjectLoad, bridgingSubjects) {
           document.getElementById('evalOverlay').classList.add('open');
           if (subjectLoad) {
             evalData.subjects.forEach(sub => {
               const sidStr = String(sub.id);
               sub.is_in_load = !!subjectLoad[sidStr] || !!subjectLoad[sub.id];
             });
           }
           renderProspectus(evalData);
           setTimeout(() => NonIBMEvaluation.applyRestrictions(), 200);
         });
         if (!skipped) {
           document.getElementById('evalOverlay').classList.remove('open');
         }
       }
    } else {
      // Regular student — proceed directly
      renderProspectus(evalData);
    }
  });
}

function _getTypeBadgeHTML(type) {
  const badges = {
    regular:  '<span class="student-type-badge stb-regular"><i class="fas fa-user-graduate"></i> Regular</span>',
    transfer: '<span class="student-type-badge stb-transfer"><i class="fas fa-exchange-alt"></i> Transfer</span>',
    non_ibm:  '<span class="student-type-badge stb-non_ibm"><i class="fas fa-user-tag"></i> Non-IBM</span>'
  };
  return badges[type] || '';
}

function _applyTransferVisuals(previousSubjects) {
  if (!previousSubjects) return;
  Object.keys(previousSubjects).forEach(sid => {
    const ps = previousSubjects[sid];
    if (!ps.grade || !ps.validated) return;
    const grade = parseFloat(ps.grade);
    if (grade > 3.00) return; // not credited

    const row = document.getElementById('row-' + sid);
    const inp = document.getElementById('g-' + sid);
    const sbtn = document.getElementById('sbtn-' + sid);

    if (row) {
      row.style.background = 'linear-gradient(135deg,#eff6ff,#dbeafe)';
      row.style.borderLeft = '4px solid var(--blue)';

      // Add a "Credited" badge
      const gradeCell = row.querySelector('.grade-cell-wrap');
      if (gradeCell && !gradeCell.querySelector('.te-credited-badge')) {
        const badge = document.createElement('span');
        badge.className = 'te-credited-badge';
        badge.innerHTML = '<i class="fas fa-check-circle" style="font-size:7px;margin-right:3px;"></i>Credited';
        badge.style.cssText = 'display:inline-flex;align-items:center;gap:3px;font-size:8px;padding:2px 6px;background:#dbeafe;color:#1e40af;border-radius:4px;border:1px solid #93c5fd;white-space:nowrap;margin-top:2px;';
        gradeCell.appendChild(badge);
      }

      // Set the grade input to the credited grade
      if (inp) {
        inp.value = grade.toFixed(2);
        inp.disabled = true;
        inp.title = 'Credited from previous school';
        inp.style.background = '#dbeafe';
        inp.style.borderColor = '#93c5fd';
      }
      if (sbtn) sbtn.disabled = true;
    }
  });
}
function closeEval() {
  document.getElementById('evalOverlay').classList.remove('open');
  focusYear = ''; focusSem = '';
}

/* ═══════════════════════════════════════════════════════════
   STICKY EVALUATE BAR (auto-detects current Year + Semester)
══════════════════════════════════════════════════════════════════ */
function buildCombinedBar(gwaData) {
  // Auto-detect current standing from student's year_level string
  const standingStr = (currentStudent && currentStudent.year_level) || '1st Year - 1st Semester';
  const {yr, sem} = parseStudentStanding(standingStr);
  const yearLabel = YEAR_LABELS[yr-1] || '1st Year';
  const semLabel  = sem === 1 ? '1st Semester' : '2nd Semester';

  // Set focusYear/focusSem automatically so downstream logic works unchanged
  focusYear = yearLabel;
  focusSem  = semLabel;

  const fkey = `${yearLabel}|${semLabel}`;
  const isAlreadyFinalized = !!finalizedMap[fkey];

  return `<div class="eval-sticky-bar" id="evalStickyBar">
    <div class="esb-left">
      <span class="esb-eyebrow"><i class="fas fa-graduation-cap"></i> Current Standing</span>
      <div class="esb-title">Evaluating <strong>${esc(yearLabel)} — ${esc(semLabel)}</strong></div>
      <span class="esb-gwa">
        <i class="fas fa-chart-line"></i> Current GWA: <strong id="currentGWA">—</strong>
      </span>
    </div>
    <div class="esb-right">
      <select id="yearFilter" class="esb-filter-select" onchange="onYearChange(this.value)">
        ${YEAR_LABELS.map(y => `<option value="${y}" ${y === yearLabel ? 'selected' : ''}>${y}</option>`).join('')}
      </select>
      <select id="semFilter" class="esb-filter-select" onchange="onSemChange(this.value)">
        <option value="1st Semester" ${semLabel === '1st Semester' ? 'selected' : ''}>1st Semester</option>
        <option value="2nd Semester" ${semLabel === '2nd Semester' ? 'selected' : ''}>2nd Semester</option>
      </select>
      <button class="esb-filter-btn" id="checkStatusBtn" onclick="checkSelectedPeriodStatus()"><i class="fas fa-check-circle"></i> Check</button>
      <span class="esb-period-badge"><i class="fas fa-calendar-alt"></i> A.Y. ${esc(currentAY)}</span>
    </div>
  </div>`;
}

function checkSelectedPeriodStatus() {
  const yearSel = document.getElementById('yearFilter');
  const semSel = document.getElementById('semFilter');
  const selYear = yearSel ? yearSel.value : focusYear;
  const selSem = semSel ? semSel.value : focusSem;
  
  if (!selYear || !selSem) {
    toast('Please select both Year and Semester', 'error');
    return;
  }
  const fkey = `${selYear}|${selSem}`;
  if (finalizedMap[fkey]) {
    showAlreadyEvaluatedModal(selYear, selSem);
  } else {
    focusYear = selYear;
    focusSem = selSem;
    showComingSoonModal();
  }
}

function showComingSoonModal() {
  const modalHtml = `
    <div class="modal-overlay" id="comingSoonModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:15000;display:flex;align-items:center;justify-content:center;padding:20px;">
      <div style="background:linear-gradient(145deg,#fff,#fafaf8);border-radius:16px;padding:28px;max-width:400px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.35);text-align:center;border:1px solid rgba(184,134,11,.2);">
        <div style="width:60px;height:60px;margin:0 auto 16px;background:linear-gradient(135deg,var(--amber-l),var(--amber));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;color:#92400e;"><i class="fas fa-clock"></i></div>
        <h3 style="font-size:18px;font-weight:700;color:var(--dark);margin-bottom:12px;">Coming Soon</h3>
        <p style="font-size:14px;color:var(--muted);margin-bottom:22px;line-height:1.6;">
          <strong>${esc(focusYear)} — ${esc(focusSem)}</strong> is not yet finalized or in progress.
          <br><br>This feature will be available once evaluation begins for this period.
        </p>
        <button onclick="document.getElementById('comingSoonModal').remove()" style="padding:10px 24px;border:none;border-radius:10px;background:linear-gradient(135deg,var(--gold-l),var(--gold-d));color:#fff;font-size:13px;font-weight:600;cursor:pointer;">OK</button>
      </div>
    </div>
  `;
  document.getElementById('comingSoonModal') && document.getElementById('comingSoonModal').remove();
  const modalDiv = document.createElement('div');
  modalDiv.id = 'comingSoonModal';
  modalDiv.innerHTML = modalHtml;
  document.body.appendChild(modalDiv);
}


/* ═══════════════════════════════════════════════════════════
    AUTO-DETECT AND EVALUATE
    Triggered by the single main "Evaluate" button.
    Uses student's current year_level to determine which semester
    to evaluate, then reuses the existing showResultModal flow.
 ══════════════════════════════════════════════════════════════════ */
function autoDetectAndEvaluate() {
  if (!currentStudent) { toast('No student loaded.', 'error'); return; }
  if (window.evaluationLocked) {
    toast('This student has graduated. Evaluation is locked.', 'error', 4500);
    return;
  }

  const standingStr = currentStudent.year_level || '1st Year - 1st Semester';
  const {yr, sem} = parseStudentStanding(standingStr);
  const yearLabel = YEAR_LABELS[yr-1] || '1st Year';
  const semLabel  = sem === 1 ? '1st Semester' : '2nd Semester';
  const semWant   = semLabel === '1st Semester' ? '1st' : '2nd';

  focusYear = yearLabel;
  focusSem  = semLabel;

  const fkey = `${yearLabel}|${semLabel}`;
  if (finalizedMap[fkey]) {
    showAlreadyEvaluatedModal(yearLabel, semLabel);
    return;
  }

  // Normalize matcher — trim + case-insensitive year compare, substring sem match
  const yearMatches = (subYear) =>
    (subYear||'').trim().toLowerCase() === yearLabel.toLowerCase();
  const semMatches  = (subSem) =>
    (subSem||'').toLowerCase().includes(semWant);

  // Build the targeted subject list:
  // Only subjects that are in the confirmed load (is_in_load = true).
  // For the current semester this is always true. For past semesters it
  // reflects the server-persisted enrollment list saved during evaluation.
  let targeted = loadedSubjects.filter(s => s.is_in_load === true);

  // If no subjects selected and NOT finalized, show enrollment modal
  if (!targeted.length && !finalizedMap[fkey]) {
    showEnrollmentResultModal(yearLabel, semLabel);
    return;
  }

  // Check which subjects have grades entered (exclude credited subjects - they already have grade)
  const graded = targeted.filter(s => gradeMap[s.id] != null || s.is_credited);

  // Allow evaluation as long as at least 1 subject has a grade
  if (graded.length === 0) {
    toast('Please enter at least one grade before evaluating.', 'error', 4200);
    const firstInput = document.querySelector('.grade-inp:not([disabled])');
    if (firstInput) firstInput.focus();
    return;
  }

  // Launch the result modal with only the graded subjects for evaluation
  showResultModal(graded, yearLabel, semLabel);
}

/* ═══════════════════════════════════════════════════════════
   SHOW ENROLLMENT RESULT MODAL
   When no subjects are selected and period is not finalized,
   show the enrollment modal directly from Quick Evaluate button.
 ═══════════════════════════════════════════════════════════ */
function showEnrollmentResultModal(yearLabel, semLabel) {
  // Get next year/semester for the Proceed button
  const {yr, sem} = parseStudentStanding(`${yearLabel} - ${semLabel}`);
  const {yr:nYr, sem:nSem} = getNextSemester(yr, sem);
  const nextYearLabel = YEAR_LABELS[nYr-1] || '—';
  const nextSemLabel  = nSem===1 ? '1st Semester' : '2nd Semester';

  // Compute next A.Y.
  const nextAYStr = (() => {
    const parts = currentAY.split('-');
    return parts.length===2 ? `${parseInt(parts[0])+1}-${parseInt(parts[1])+1}` : currentAY;
  })();

  // Get current subjects for this semester
  const currentSubs = loadedSubjects.filter(s => {
    const sy = (s.year_level || '').trim().toLowerCase();
    const ss = (s.semester || '').toLowerCase();
    return sy === yearLabel.toLowerCase() && ss.includes(semLabel === '1st Semester' ? '1st' : '2nd');
  });

// Build prereq unlock map
   const prereqUnlockMapCurrent = buildPrereqUnlockMap(loadedSubjects, gradeMap, prereqSetsData, currentStudent && currentStudent.major_id);

   const bodyHtml = `
    <div style="font-size:13px;font-weight:700;color:var(--gold-d);margin-bottom:10px;">
      <i class="fas fa-list-ul" style="margin-right:7px;"></i>Select Subjects for ${yearLabel} — ${semLabel}
    </div>
    <div id="rmSubjectList" style="max-height:280px;overflow-y:auto;margin-bottom:12px;">
      ${currentSubs.length ? currentSubs.map(s => {
        const isBlocked = !(prereqUnlockMapCurrent[s.id] && prereqUnlockMapCurrent[s.id].unlocked);
        return `<div class="rm-sub-row" data-id="${s.id}" style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#fafaf8;border-radius:8px;margin-bottom:6px;border:1px solid var(--border);${isBlocked ? 'opacity:0.6;' : ''}">
          <input type="checkbox" class="rm-sub-check" data-id="${s.id}" ${isBlocked ? 'disabled' : ''} style="width:16px;height:16px;accent-color:var(--gold);">
          <div style="flex:1;">
            <div style="font-weight:700;color:var(--dark);font-size:12px;">${esc(s.subject_code)} <span style="color:var(--muted);font-weight:400;font-size:11px;">(${parseFloat(s.units)||0}u)</span></div>
            <div style="font-size:11px;color:var(--mid);">${esc(s.subject_name)}</div>
          </div>
          ${isBlocked ? '<span style="font-size:10px;color:var(--amber);font-weight:600;"><i class="fas fa-lock"></i> Locked</span>' : ''}
        </div>`;
      }).join('') : '<div style="padding:20px;text-align:center;color:var(--muted);font-size:12px;">No subjects found for this period.</div>'}
    </div>
    <div style="display:flex;gap:10px;align-items:center;margin-bottom:15px;">
      <span id="rmSelectedCount" style="font-size:12px;font-weight:600;color:var(--gold-d);">0 selected</span>
      <span id="rmTotalUnits" style="font-size:12px;color:var(--muted);">0 units</span>
    </div>
    <button id="rmConfirmBtn" onclick="rmConfirmEnrollmentFromQuickEval('${yearLabel}','${semLabel}')"
      style="width:100%;padding:12px;background:linear-gradient(135deg,var(--gold),var(--gold-d));color:#fff;border:none;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;font-weight:700;cursor:pointer;">
      Confirm Subject Load
    </button>`;

  document.getElementById('resultModalContent').innerHTML = `
    <div class="rm-header rm-pass">
      <div class="rm-icon pass-icon">📚</div>
      <div class="rm-header-text">
        <div class="rm-semester-tag">${esc(yearLabel)} · ${esc(semLabel)} · A.Y. ${esc(currentAY)}</div>
        <div class="rm-verdict">Select Subject Load</div>
        <div class="rm-verdict-sub">Choose the subjects to evaluate for this semester.</div>
      </div>
    </div>
    <div class="rm-body">
      ${bodyHtml}
    </div>`;
  document.getElementById('resultModal').classList.add('open');
}

function rmConfirmEnrollmentFromQuickEval(yearLabel, semLabel) {
  const selected = [];
  document.querySelectorAll('.rm-sub-check:checked').forEach(cb => {
    const id = parseInt(cb.dataset.id);
    const sub = loadedSubjects.find(s => s.id === id);
    if (sub) selected.push(sub);
  });
  if (selected.length === 0) {
    toast('Please select at least one subject', 'error');
    return;
  }
  confirmedEnrollmentSubjectIds = selected.map(s => s.id);
  selected.forEach(s => { s.is_in_load = true; });
  toast(`${selected.length} subjects confirmed for ${yearLabel} — ${semLabel}`, 'success');
  closeResultModal();
  setTimeout(() => autoDetectAndEvaluate(), 300);
}

/* ═══════════════════════════════════════════════════════════
   ON FOCUS CHANGE — blur/active states + auto-scroll
═══════════════════════════════════════════════════════════ */
function onFocusChange() {
  // Filter dropdowns removed — kept as no-op for backward compatibility.
  // focusYear/focusSem are now auto-set from currentStudent.year_level.
  if (currentStudent) {
    const parsed = parseStudentStanding(currentStudent.year_level || '1st Year - 1st Semester');
    focusYear = YEAR_LABELS[parsed.yr - 1] || '1st Year';
    focusSem  = parsed.sem === 1 ? '1st Semester' : '2nd Semester';
  }
  applyFocusVisuals();

  const fkey = `${focusYear}|${focusSem}`;
  if(focusYear && focusSem && finalizedMap[fkey]) {
    showAlreadyEvaluatedModal(focusYear, focusSem);
    return;
  }

  // ★ AUTO-SCROLL only when BOTH Year AND Semester are selected
  if(!focusYear || !focusSem) return;
  requestAnimationFrame(() => {
    let target = null;
    document.querySelectorAll('.pro-year-block[data-year="'+focusYear+'"]').forEach(block => {
      block.querySelectorAll('.pro-sem-col').forEach(col => {
        if(col.dataset.sem === focusSem) target = col;
      });
    });
    if(!target) target = document.querySelector('.pro-year-block[data-year="'+focusYear+'"]');
    if(target) {
      target.scrollIntoView({behavior:'smooth', block:'start'});
      // Pulse outline to confirm navigation
      target.style.outline = '2.5px solid var(--gold-l)';
      target.style.outlineOffset = '3px';
      setTimeout(() => { target.style.outline = ''; target.style.outlineOffset = ''; }, 1400);
      // Focus first unlocked input
      const firstInput = target.querySelector('.grade-inp:not([disabled])');
      if(firstInput) setTimeout(() => firstInput.focus(), 380);
    }
  });
}

function showAlreadyEvaluatedModal(year, sem) {
  const modalHtml = `
    <div class="modal-overlay" id="alreadyEvaluatedOverlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:15000;display:flex;align-items:center;justify-content:center;padding:20px;">
      <div style="background:linear-gradient(145deg,#fff,#fafaf8);border-radius:16px;padding:26px 30px;max-width:400px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.35);text-align:center;border:1px solid rgba(184,134,11,.2);">
        <div style="width:58px;height:58px;margin:0 auto 16px;background:linear-gradient(135deg,var(--amber-l),var(--amber));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;color:#92400e;"><i class="fas fa-exclamation-triangle"></i></div>
        <h3 style="font-size:17px;font-weight:700;color:var(--dark);margin-bottom:10px;">Already Evaluated</h3>
        <p style="font-size:13px;color:var(--muted);margin-bottom:22px;line-height:1.6;">
          <strong>${year} — ${sem}</strong> has already been evaluated and finalized.<br>Do you want to view the grades or continue anyway?
        </p>
         <div id="evalModalButtons" style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
           <button onclick="closeAlreadyEvaluatedModal()" style="padding:10px 20px;border:1px solid var(--border);border-radius:10px;background:var(--cream);color:var(--mid);font-size:13px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;">Cancel</button>
           <button onclick="confirmViewEvaluated('${year}','${sem}')" style="padding:10px 20px;border:none;border-radius:10px;background:linear-gradient(135deg,var(--gold-l),var(--gold-d));color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;">View Grades</button>
           <button onclick="unfinalizeSession('${year}','${sem}')" style="padding:10px 20px;border:none;border-radius:10px;background:linear-gradient(135deg,var(--red),#dc2626);color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;"><i class="fas fa-undo" style="margin-right:6px;"></i> Unfinalize</button>
         </div>
      </div>
    </div>
  `;
document.getElementById('alreadyEvaluatedModal') && document.getElementById('alreadyEvaluatedModal').remove();
   const modalDiv = document.createElement('div');
   modalDiv.id = 'alreadyEvaluatedModal';
   modalDiv.innerHTML = modalHtml;
   document.body.appendChild(modalDiv);
 }

  function unfinalizeSession(year, sem) {
     if(!confirm(`Unfinalize ${year} — ${sem}? This will remove the finalization lock and allow editing grades again.`)) {
       return;
     }

     const fd = new FormData();
     fd.append('action', 'unfinalize_session');
     fd.append('student_id', currentStudent.id);
     fd.append('major_id', currentStudent.major_id || 0);
     fd.append('academic_year', currentAY);
     fd.append('year_level', year);
     fd.append('semester', sem);

     fetch(EVAL_PROC, {method:'POST', body:fd})
       .then(r => r.json())
       .then(data => {
         if(data.success) {
           const fkey = `${year}|${sem}`;
           delete finalizedMap[fkey];

          const semCols = document.querySelectorAll(`.pro-sem-col[data-sem="${sem}"]`);
          semCols.forEach(col => {
            const yearBlock = col.closest('.pro-year-block');
            if (yearBlock && yearBlock.dataset.year === year) {
              const badge = col.querySelector('.sem-finalized-badge-inline');
              if (badge) badge.remove();

              col.querySelectorAll('tr').forEach(row => {
                row.classList.remove('row-finalized');
                const inp = row.querySelector('.grade-inp');
                const sbtn = row.querySelector('.save-btn');
                if (inp) {
                  inp.disabled = false;
                  inp.style.pointerEvents = 'auto';
                  inp.style.background = '';
                  inp.style.borderColor = '';
                  inp.style.opacity = '';
                  inp.title = '1.00 to 5.00 · Enter to save';
                }
                if (sbtn) {
                  sbtn.disabled = false;
                  sbtn.style.pointerEvents = 'auto';
                  sbtn.style.opacity = '';
                }
              });
            }
          });

          closeAlreadyEvaluatedModal();
          applyFocusVisuals();
          toast(`${year} — ${sem} unfinalized successfully!`, 'success', 3000);
        } else {
          toast(data.message || 'Failed to unfinalize session', 'error');
        }
      })
      .catch(err => {
        console.error('Error unfinalizing session:', err);
        toast('Error unfinalizing session', 'error');
      });
   }

function closeAlreadyEvaluatedModal() {
   document.getElementById('alreadyEvaluatedModal') && document.getElementById('alreadyEvaluatedModal').remove();
   clearFocus();
 }

function confirmViewEvaluated(year, sem) {
  closeAlreadyEvaluatedModal();
  openGradesModal(year, sem);
}

/* Filter dropdowns removed — these helpers kept as no-ops for backward
   compatibility with existing calls (e.g. closeAlreadyEvaluatedModal). */
function toggleFilterContainer() { /* no-op: filter UI removed */ }

function clearFocus() {
  // Reset any lingering inline display styles from prior filtering
  document.querySelectorAll('.pro-year-block').forEach(block => {
    block.style.display = '';
    block.classList.remove('yr-blurred','yr-active');
  });
  document.querySelectorAll('.pro-sem-col').forEach(col => {
    col.style.display = '';
    col.classList.remove('sem-blurred','sem-active');
  });
  document.querySelectorAll('.pro-table tbody tr').forEach(row => {
    row.style.display = '';
  });
  // Re-detect current standing so focusYear/focusSem stay auto-synced
  if (currentStudent) {
    const {yr, sem} = parseStudentStanding(currentStudent.year_level || '1st Year - 1st Semester');
    focusYear = YEAR_LABELS[yr-1] || '1st Year';
    focusSem  = sem === 1 ? '1st Semester' : '2nd Semester';
  }
  applyFocusVisuals();
}

   function applyFocusVisuals() {
    // Filter UI removed — no year/sem hiding. Just refresh GWA + evaluate button state.
    document.querySelectorAll('.pro-year-block[data-year]').forEach(block => {
      block.style.display = '';
      block.classList.remove('yr-blurred','yr-active');
      block.querySelectorAll('.pro-sem-col').forEach(col => {
        col.style.display = '';
        col.classList.remove('sem-blurred','sem-active');
        col.querySelectorAll('.pro-table tbody tr').forEach(row => { row.style.display = ''; });
        // Reset total row to original full total
        const totalRow = col.querySelector('.pro-table .pro-total-row');
        if (totalRow && totalRow.dataset.fullTotal !== undefined) {
          const unitCell = totalRow.querySelector('td:nth-child(5)');
          if (unitCell) unitCell.textContent = fmt(parseFloat(totalRow.dataset.fullTotal));
        }
      });
    });

    // Refresh the Evaluate button state on the sticky bar
    const fkey = `${focusYear}|${focusSem}`;
    
    // Refresh the FAB button state (main page floating button)
    const fabEvaluateMain = document.getElementById('fabEvaluateMain');
    if (fabEvaluateMain) {
      if (finalizedMap[fkey]) {
        fabEvaluateMain.innerHTML = '<i class="fas fa-check-circle"></i>';
        fabEvaluateMain.classList.add('done');
      } else {
        fabEvaluateMain.innerHTML = '<i class="fas fa-bolt"></i>';
        fabEvaluateMain.classList.remove('done');
      }
    }

    // Update Current GWA — based on subjects in the current year-semester combination from student's standing
    // Get the current standing from year_level and filter subjects accordingly
    let gwaPoints = 0, gwaUnits = 0;
    const currentStanding = (currentStudent && currentStudent.year_level) || '1st Year - 1st Semester';
    const {yr, sem} = parseStudentStanding(currentStanding);
    const curYearLabel = YEAR_LABELS[yr-1] || '1st Year';
    const curSemWant = sem === 1 ? '1st' : '2nd';
    
    loadedSubjects.forEach(sub => {
      // Filter by current year and semester from student's standing
      if (sub.year_level !== curYearLabel) return;
      const sSem = (sub.semester||'').toLowerCase();
      if (!sSem.includes(curSemWant)) return;
      
      const raw = gradeMap[sub.id];
      if (raw != null) {
        const rounded = roundGrade(parseFloat(raw));
        const units = parseFloat(sub.units) || 0;
        gwaPoints += rounded * units;
        gwaUnits  += units;
      }
    });
    const gwaEl = document.getElementById('currentGWA');
    if (gwaEl) {
      gwaEl.textContent = gwaUnits > 0 ? (gwaPoints / gwaUnits).toFixed(2) : '—';
    }
  }

/* ═══════════════════════════════════════════════════════════
   TRIGGER FINALIZE
═══════════════════════════════════════════════════════════ */
function triggerFinalize() {
  if(!focusYear || !focusSem) { toast('Please select both a Year and Semester to finalize.','error'); return; }
  const fkey = `${focusYear}|${focusSem}`;
  if(finalizedMap[fkey]) { toast('This period is already finalized.','info'); return; }

   const targeted = loadedSubjects.filter(s =>
     s.year_level === focusYear &&
     (s.semester||'').includes(focusSem === '1st Semester' ? '1st' : '2nd') &&
     s.is_in_load !== false // include subjects that are in the student's load (regular or transfer with load)
   );
  if(!targeted.length) { toast('No subjects found for this year/semester.','error'); return; }

  const gradedForFinalize = targeted.filter(s => gradeMap[s.id] != null || s.is_credited);
  const missingForFinalize = targeted.filter(s => gradeMap[s.id] == null && !s.is_credited);
  
  if(gradedForFinalize.length === 0) {
    toast('Please enter at least one grade before finalizing.','error',4000);
    return;
  }

  if(missingForFinalize.length) {
    if(!confirm(`${missingForFinalize.length} subject(s) still have no grade.\n\nDo you want to finalize with only the ${gradedForFinalize.length} graded subject(s)?`)) {
      missingForFinalize.forEach(s => {
        const inp = document.getElementById('g-'+s.id);
        if(inp) { inp.style.borderColor='var(--amber)'; inp.style.boxShadow='0 0 0 3px rgba(217,119,6,.2)'; setTimeout(()=>{inp.style.borderColor='';inp.style.boxShadow='';},2800); }
      });
      return;
    }
  }
  if(!confirm(`Finalize evaluation for ${focusYear} — ${focusSem}?\n\nThis will lock grades for this period and cannot be undone.`)) return;

  targeted.forEach(s => {
    const inp  = document.getElementById('g-'+s.id);
    const sbtn = document.getElementById('sbtn-'+s.id);
    if(inp)  { inp.disabled = true; inp.style.cursor = 'not-allowed'; }
    if(sbtn) { sbtn.disabled = true; }
  });

  finalizedMap[fkey] = true;
  applyFocusVisuals();

  document.querySelectorAll('.pro-year-block[data-year="'+focusYear+'"]').forEach(block => {
    block.querySelectorAll('.pro-sem-col').forEach(col => {
      if(col.dataset.sem === focusSem && !col.querySelector('.sem-finalized-badge')) {
        const badge = document.createElement('div');
        badge.className = 'sem-finalized-badge';
        badge.innerHTML = `<i class="fas fa-check-circle"></i> Finalized`;
        col.insertBefore(badge, col.firstChild);
      }
    });
   });

    const fd = new FormData();
    fd.append('action','finalize_session'); fd.append('student_id',currentStudent.id);
    fd.append('major_id',currentStudent.major_id||0); fd.append('academic_year',currentAY);
    fd.append('year_level',focusYear); fd.append('semester',focusSem);
    fetch(EVAL_PROC,{method:'POST',body:fd}).catch(()=>{});
 }

/* ═══════════════════════════════════════════════════════════
   SHOW RESULT MODAL — with flexible progression + customizable enrollment list
═══════════════════════════════════════════════════════════ */
function showResultModal(subjects, yearLabel, semLabel) {
  let tp=0, tu=0, up=0;
  const passedSubs=[], failedSubs=[], condSubs=[], noGradeSubs=[];

  subjects.forEach(s => {
    const raw = gradeMap[s.id];
    if(raw == null) { noGradeSubs.push(s); return; }
    const rounded = roundGrade(parseFloat(raw));
    const units   = parseFloat(s.units)||0;
    const status  = gradeStatus(rounded);
    tp += rounded * units; tu += units;
    if(status==='passed')      { up += units; passedSubs.push({...s,grade:rounded}); }
    else if(status==='failed') { failedSubs.push({...s,grade:rounded}); }
    else                       { condSubs.push({...s,grade:rounded}); }
  });

  const semGWA   = tu > 0 ? (tp/tu) : null;
  const allPassed = failedSubs.length === 0 && condSubs.length === 0 && noGradeSubs.length === 0;
  const allFailed = passedSubs.length === 0 && failedSubs.length > 0 && condSubs.length === 0;

   // ★ FLEXIBLE PROGRESSION: verdict reflects results but PROMOTION IS ALWAYS OFFERED
   let verdict, headerClass, iconClass, iconContent, verdictSub;
   if(allPassed) {
     verdict='Student PASSED'; headerClass='rm-pass'; iconClass='pass-icon'; iconContent='🎓';
     verdictSub=`All ${passedSubs.length} subject(s) passed with a semester GWA of <strong>${semGWA ? semGWA.toFixed(2) : '—'}</strong>.`;
   } else if(condSubs.length > 0 && failedSubs.length === 0) {
     verdict='CONDITIONAL Status'; headerClass='rm-cond'; iconClass='cond-icon'; iconContent='⚠️';
     verdictSub=`${condSubs.length} subject(s) received a conditional grade (4.00). Removal exam required.`;
   } else if(failedSubs.length > 0) {
     verdict='Has FAILED Subject(s)'; headerClass='rm-fail'; iconClass='fail-icon'; iconContent='📋';
     verdictSub=`${failedSubs.length} subject(s) failed. Student may still proceed; only dependent subjects will be locked.`;
   } else {
     verdict='Results Recorded'; headerClass='rm-mixed'; iconClass='mixed-icon'; iconContent='📄';
     verdictSub='Evaluation complete. Review the details below.';
   }
   
   const cur = window.lastCurriculumStats || {};
   const curriculumReady = cur.complete === true;
const isGraduationEligible = yearLabel === '4th Year' &&
                            semLabel === '2nd Semester' &&
                            allPassed &&
                            noGradeSubs.length === 0 &&
                            passedSubs.length > 0;
   const finalGwaStr = (cur.gwa != null && cur.gwa !== '') ? Number(cur.gwa).toFixed(4) : (semGWA != null ? semGWA.toFixed(2) : '—');
   const totalProgSubjects = (cur.total_required != null && cur.total_required > 0) ? cur.total_required : passedSubs.length;

  // Next semester subjects
  const {yr:cYr, sem:cSem} = parseStudentStanding(`${yearLabel} - ${semLabel}`);
  const {yr:nYr, sem:nSem} = getNextSemester(cYr, cSem);
  const nextYearLabel = YEAR_LABELS[nYr-1] || '—';
  const nextSemLabel  = nSem===1 ? '1st Semester' : '2nd Semester';

  // Compute next A.Y.
  const nextAYStr = (() => {
    const parts = currentAY.split('-');
    return parts.length===2 ? `${parseInt(parts[0])+1}-${parseInt(parts[1])+1}` : currentAY;
  })();

  // Build prereq unlock map to identify blocked next-sem subjects
  const prereqUnlockMapCurrent = buildPrereqUnlockMap(loadedSubjects, gradeMap, prereqSetsData, currentStudent?.major_id);

  const isPreviouslyLoadedUngraded = s => {
    return s.load_year && s.load_semester && gradeMap[s.id] == null && !s.is_credited;
  };

  const nextSemSubsWithStatus = loadedSubjects
    .filter(s => (YEAR_NUM[s.year_level]||0) === nYr && (SEM_NUM[s.semester]||0) === nSem)
    .filter(s => !isPreviouslyLoadedUngraded(s))
    .map(s => ({...s, isBlocked: !(prereqUnlockMapCurrent[s.id]?.unlocked ?? true)}));

  const blockedByFailedPrereq = nextSemSubsWithStatus.filter(sub => {
    const pi = prereqUnlockMapCurrent[sub.id] || {};
    const directFailed = pi.directLocked && pi.directPrereqSubj && gradeMap[pi.directPrereqSubj.id] != null && gradeStatus(roundGrade(gradeMap[pi.directPrereqSubj.id])) === 'failed';
    const setFailed = pi.setLocked && (pi.setBlockedBy||[]).some(ps => gradeMap[ps.id] != null && gradeStatus(roundGrade(gradeMap[ps.id])) === 'failed');
    return directFailed || setFailed;
  });
  const blockedByUnmetPrereq = nextSemSubsWithStatus.filter(sub => {
    if (!sub.isBlocked) return false;
    const pi = prereqUnlockMapCurrent[sub.id] || {};
    const directFailed = pi.directLocked && pi.directPrereqSubj && gradeMap[pi.directPrereqSubj.id] != null && gradeStatus(roundGrade(gradeMap[pi.directPrereqSubj.id])) === 'failed';
    const setFailed = pi.setLocked && (pi.setBlockedBy||[]).some(ps => gradeMap[ps.id] != null && gradeStatus(roundGrade(gradeMap[ps.id])) === 'failed');
    return !directFailed && !setFailed;
  });
  const blockedNextSubs   = nextSemSubsWithStatus.filter(s => s.isBlocked && !blockedByFailedPrereq.some(f => f.id === s.id) && !blockedByUnmetPrereq.some(f => f.id === s.id));
  const availableNextSubs = nextSemSubsWithStatus.filter(s => !s.isBlocked);

  const nextSemStatusInfo = {};
  const normalizeCode = code => String(code||'').replace(/\s+/g,'').trim().toUpperCase();
  const findSubjectByCode = code => {
    const normalized = normalizeCode(code);
    return loadedSubjects.find(ls => normalizeCode(ls.subject_code) === normalized) || null;
  };
  const hasFailedSamePrereqSetMember = subject => {
    if (!Array.isArray(prereqSetsData) || !currentStudent?.major_id) return false;
    return prereqSetsData.some(set => {
      if (set.major_id != currentStudent.major_id) return false;
      if (!Array.isArray(set.subjects)) return false;
      const memberIds = set.subjects.map(ps => parseInt(ps.id));
      if (!memberIds.includes(parseInt(subject.id))) return false;
      return set.subjects.some(ps => parseInt(ps.id) !== parseInt(subject.id) && gradeMap[ps.id] != null && gradeStatus(roundGrade(gradeMap[ps.id])) === 'failed');
    });
  };
  nextSemSubsWithStatus.forEach(s => {
    const pi = prereqUnlockMapCurrent[s.id] || {};
    const directSubjects = pi.directPrereqSubjects || (pi.directPrereqSubj ? [pi.directPrereqSubj] : []);
    const failedDirect = directSubjects.some(ps => {
      const pg = gradeMap[ps.id];
      return pg != null && gradeStatus(roundGrade(pg)) === 'failed';
    });

    const fallbackFailedDirect = !failedDirect ? (() => {
      const prereqField = String(s.display_prerequisite || s.prerequisite || s.prerequisite_subject_code || '');
      const prereqCodes = parsePrereqCodes(prereqField);
      return prereqCodes.some(code => {
        const prereqSub = findSubjectByCode(code);
        return prereqSub && gradeMap[prereqSub.id] != null && gradeStatus(roundGrade(gradeMap[prereqSub.id])) === 'failed';
      });
    })() : false;

    const setFailed = pi.setLocked && (pi.setBlockedBy||[]).some(ps => gradeMap[ps.id] != null && gradeStatus(roundGrade(gradeMap[ps.id])) === 'failed');
    const sameSetFailed = hasFailedSamePrereqSetMember(s);
    nextSemStatusInfo[s.id] = {
      failedDirect: failedDirect || fallbackFailedDirect,
      setFailed,
      sameSetFailed
    };
  });

  // Retake schedule for failed subjects (same semester, next A.Y.)
  const retakeSchedule = failedSubs.map(s => ({
    ...s,
    retakeSem: (s.semester||'1st Semester').includes('1st') ? '1st Semester' : '2nd Semester',
    retakeAY:  nextAYStr
  }));

    // Grade breakdown chips
   const breakdownHtml = `<div class="rm-grade-breakdown">
     <span class="rm-grade-chip rgc-pass"><i class="fas fa-check"></i> ${passedSubs.length} Passed</span>
     ${failedSubs.length?`<span class="rm-grade-chip rgc-fail"><i class="fas fa-times"></i> ${failedSubs.length} Failed</span>`:''}
     ${condSubs.length?`<span class="rm-grade-chip rgc-cond"><i class="fas fa-exclamation"></i> ${condSubs.length} Conditional</span>`:''}
     ${noGradeSubs.length?`<span class="rm-grade-chip rgc-none"><i class="fas fa-minus"></i> ${noGradeSubs.length} No Grade</span>`:''}
   </div>`;

   let bodyHtml = '';

   // Failed subjects
   if(failedSubs.length) {
     bodyHtml += `<div style="font-size:13px;font-weight:700;color:var(--red);margin-bottom:10px;">
       <i class="fas fa-redo" style="margin-right:7px;"></i>Failed Subjects — Must Retake
     </div>
     <div class="rm-subject-list">
       ${failedSubs.map(s=>`<div class="rm-retake-card">
         <div class="rm-sub-code">${esc(s.subject_code)}</div>
         <div class="rm-sub-name">${esc(s.subject_name)}</div>
         <div style="font-size:9px;font-weight:700;color:var(--red);margin-top:3px;">Grade: ${s.grade.toFixed(2)} — Failed</div>
       </div>`).join('')}
     </div>`;
   }

    // ★ PROSPECTUS STYLE ELIGIBLE SUBJECTS TABLE
   if(nextSemSubsWithStatus.length) {
     const totalAvailUnits = availableNextSubs.reduce((a,s)=>a+(parseFloat(s.units)||0),0);
     bodyHtml += `
     <div style="margin-bottom:18px;">
       <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
         <div style="font-size:13px;font-weight:700;color:var(--dark);">
           <i class="fas fa-list-check" style="color:var(--green);margin-right:7px;"></i>
           Select Subject Load for <strong>${nextSemLabel} · ${nextYearLabel}</strong>
           <span style="font-size:10px;color:var(--muted);font-weight:400;margin-left:6px;">— checked subjects will be loaded &amp; become gradeable</span>
         </div>
         <div style="display:flex;gap:6px;">
           <button onclick="rmSelectAll(true)"  style="padding:4px 10px;border:1px solid var(--border);border-radius:6px;background:var(--green-l);color:#166534;font-size:11px;font-weight:600;cursor:pointer;">Select All</button>
           <button onclick="rmSelectAll(false)" style="padding:4px 10px;border:1px solid var(--border);border-radius:6px;background:var(--cream2);color:var(--muted);font-size:11px;font-weight:600;cursor:pointer;">Clear</button>
         </div>
       </div>
       <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;">
         <table style="width:100%;border-collapse:collapse;font-size:11px;">
           <thead>
             <tr style="background:linear-gradient(135deg,var(--gold-d),var(--gold));">
               <th style="width:30px;padding:8px 10px;text-align:center;color:#fff;font-weight:700;border-bottom:2px solid var(--gold-d);">✓</th>
               <th style="padding:8px 10px;text-align:left;color:#fff;font-weight:700;border-bottom:2px solid var(--gold-d);">Course Code</th>
               <th style="padding:8px 10px;text-align:left;color:#fff;font-weight:700;border-bottom:2px solid var(--gold-d);">Subject Title</th>
               <th style="width:60px;padding:8px 10px;text-align:center;color:#fff;font-weight:700;border-bottom:2px solid var(--gold-d);">Units</th>
               <th style="width:80px;padding:8px 10px;text-align:center;color:#fff;font-weight:700;border-bottom:2px solid var(--gold-d);">Status</th>
             </tr>
           </thead>
           <tbody>
             ${availableNextSubs.map(s=>{
               const statusInfo = nextSemStatusInfo[s.id] || {}; 
               const isUnavailable = statusInfo.failedDirect || statusInfo.setFailed || statusInfo.sameSetFailed;
               const statusLabel = isUnavailable ? 'Unavailable' : 'Available';
               const statusStyle = isUnavailable ? 'background:var(--red-l);color:#991b1b;' : 'background:var(--green-l);color:#166534;';
               const disabledAttr = isUnavailable ? 'disabled' : '';
               const checkedAttr = isUnavailable ? '' : 'checked';
               const rowCursor = isUnavailable ? 'default' : 'pointer';
               const rowOpacity = isUnavailable ? 'opacity:.65;' : '';
               const titleText = isUnavailable ? 'Prerequisite failed or another subject in the same prereq group failed' : '';
               return `
             <tr id="rmrow-${s.id}" style="background:#fff;${rowOpacity}cursor:${rowCursor};" ${isUnavailable ? '' : `onclick="document.getElementById('rmchk-${s.id}').click();"`} title="${titleText}">
               <td style="padding:7px 10px;text-align:center;border-bottom:1px solid var(--border);">
                 <input type="checkbox" id="rmchk-${s.id}" ${checkedAttr} onchange="rmToggleSubject(${s.id})" style="width:16px;height:16px;accent-color:var(--gold-d);cursor:pointer;" ${disabledAttr}>
               </td>
               <td style="padding:7px 10px;border-bottom:1px solid var(--border);font-weight:700;color:var(--dark);">${esc(s.subject_code)}</td>
               <td style="padding:7px 10px;border-bottom:1px solid var(--border);">${esc(s.subject_name)}</td>
               <td style="padding:7px 10px;text-align:center;border-bottom:1px solid var(--border);font-weight:600;">${parseFloat(s.units)||0}</td>
               <td style="padding:7px 10px;text-align:center;border-bottom:1px solid var(--border);">
                 <span style="display:inline-block;padding:3px 8px;${statusStyle}border-radius:10px;font-size:9px;font-weight:700;">${statusLabel}</span>
               </td>
             </tr>`;
             }).join('')}
             ${blockedNextSubs.map(s=>`
             <tr style="background:#f9f9f9;opacity:0.7;">
               <td style="padding:7px 10px;text-align:center;border-bottom:1px solid var(--border);">
                 <input type="checkbox" disabled style="width:16px;height:16px;cursor:not-allowed;">
               </td>
               <td style="padding:7px 10px;border-bottom:1px solid var(--border);font-weight:700;color:var(--muted);">${esc(s.subject_code)}</td>
               <td style="padding:7px 10px;border-bottom:1px solid var(--border);color:var(--muted);">${esc(s.subject_name)}</td>
               <td style="padding:7px 10px;text-align:center;border-bottom:1px solid var(--border);font-weight:600;color:var(--muted);">${parseFloat(s.units)||0}</td>
               <td style="padding:7px 10px;text-align:center;border-bottom:1px solid var(--border);">
                 <span style="display:inline-block;padding:3px 8px;background:var(--red-l);color:#991b1b;border-radius:10px;font-size:9px;font-weight:700;"><i class="fas fa-lock" style="margin-right:3px;"></i>Locked</span>
               </td>
             </tr>`).join('')}
</tbody>
          </table>
        </div>
      </div>`;
      
// Add cross-year same semester subjects table
       const sameSemOtherYearSubs = loadedSubjects.filter(s => {
         const sSem = (s.semester || '').toLowerCase();
         const targetSem = nextSemLabel.toLowerCase();
         const sYear = YEAR_NUM[s.year_level] || 0;
         const isSameSem = sSem.includes(targetSem.includes('1st') ? '1st' : '2nd');
         const isDifferentYear = sYear !== cYr;  // Changed from nYr to cYr to include current year subjects
         const rawGrade = gradeMap[s.id];
         const hasFailedGrade = rawGrade != null && gradeStatus(roundGrade(rawGrade)) === 'failed';
         const notTakenOrFailed = rawGrade == null || hasFailedGrade;
         return isSameSem && isDifferentYear && notTakenOrFailed && !isPreviouslyLoadedUngraded(s);
       });
      
      if(sameSemOtherYearSubs.length > 0) {
        bodyHtml += `
        <div style="margin-bottom:18px;">
          <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:10px;">
            <i class="fas fa-plus-circle" style="color:var(--blue);margin-right:7px;"></i>
            Additional Subjects (Same Semester, Other Years)
            <span style="font-size:10px;color:var(--muted);font-weight:400;margin-left:6px;">Includes failed subjects that can be retaken in the same semester.</span>
          </div>
          <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;font-size:11px;">
              <thead>
                <tr style="background:linear-gradient(135deg,var(--blue),#1e40af);">
                  <th style="width:30px;padding:8px 10px;text-align:center;color:#fff;font-weight:700;border-bottom:2px solid #1e40af;">✓</th>
                  <th style="width:60px;padding:8px 10px;text-align:left;color:#fff;font-weight:700;border-bottom:2px solid #1e40af;">Year</th>
                  <th style="padding:8px 10px;text-align:left;color:#fff;font-weight:700;border-bottom:2px solid #1e40af;">Course Code</th>
                  <th style="padding:8px 10px;text-align:left;color:#fff;font-weight:700;border-bottom:2px solid #1e40af;">Subject Title</th>
                  <th style="width:60px;padding:8px 10px;text-align:center;color:#fff;font-weight:700;border-bottom:2px solid #1e40af;">Units</th>
                </tr>
              </thead>
              <tbody>
                ${sameSemOtherYearSubs.map(s=>`
                <tr id="rmrow-extra-${s.id}" style="background:#fff;cursor:pointer;" onclick="document.getElementById('rmchk-extra-${s.id}').click();">
                  <td style="padding:7px 10px;text-align:center;border-bottom:1px solid var(--border);">
                    <input type="checkbox" id="rmchk-extra-${s.id}" onchange="rmToggleExtraSubject(${s.id})" style="width:16px;height:16px;accent-color:var(--blue);cursor:pointer;">
                  </td>
                  <td style="padding:7px 10px;border-bottom:1px solid var(--border);font-weight:600;color:var(--mid);">${esc(s.year_level)}</td>
                  <td style="padding:7px 10px;border-bottom:1px solid var(--border);font-weight:700;color:var(--dark);">${esc(s.subject_code)}</td>
                  <td style="padding:7px 10px;border-bottom:1px solid var(--border);">${esc(s.subject_name)}</td>
                  <td style="padding:7px 10px;text-align:center;border-bottom:1px solid var(--border);font-weight:600;">${parseFloat(s.units)||0}</td>
                </tr>`).join('')}
              </tbody>
            </table>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;flex-wrap:wrap;gap:8px;padding:10px 12px;background:linear-gradient(135deg,var(--gold-d),var(--gold));border-radius:10px;">
            <div style="font-size:12px;color:#fff;font-weight:600;">
              <i class="fas fa-calculator" style="margin-right:4px;"></i>
              <span id="rmSelectedCount">${availableNextSubs.length}</span> subjects · <span id="rmTotalUnits">${totalAvailUnits}</span> units selected (including additional)
            </div>
            <button onclick="rmConfirmEnrollmentList('${nextYearLabel}','${nextSemLabel}')"
              style="padding:8px 18px;background:#fff;color:var(--gold-d);border:none;border-radius:8px;font-family:'Poppins',sans-serif;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:7px;box-shadow:0 3px 10px rgba(0,0,0,.2);transition:all .2s;" id="rmConfirmBtn">
              <i class="fas fa-check-double"></i> Confirm Subject Load
            </button>
          </div>
        </div>`;
        window._rmExtraSubs = sameSemOtherYearSubs;
        window._rmExtraSelectedIds = new Set();
      } else {
        bodyHtml += `
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;flex-wrap:wrap;gap:8px;padding:10px 12px;background:linear-gradient(135deg,var(--gold-d),var(--gold));border-radius:10px;">
          <div style="font-size:12px;color:#fff;font-weight:600;">
            <i class="fas fa-calculator" style="margin-right:4px;"></i>
            <span id="rmSelectedCount">${availableNextSubs.length}</span> subjects · <span id="rmTotalUnits">${totalAvailUnits}</span> units selected
          </div>
          <button onclick="rmConfirmEnrollmentList('${nextYearLabel}','${nextSemLabel}')"
            style="padding:8px 18px;background:#fff;color:var(--gold-d);border:none;border-radius:8px;font-family:'Poppins',sans-serif;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:7px;box-shadow:0 3px 10px rgba(0,0,0,.2);transition:all .2s;" id="rmConfirmBtn">
            <i class="fas fa-check-double"></i> Confirm Subject Load
          </button>
        </div>`;
      }
      
      window._rmAvailableSubs = availableNextSubs;
      window._rmSelectedIds   = new Set(availableNextSubs.filter(s => {
        const statusInfo = nextSemStatusInfo[s.id] || {};
        return !(statusInfo.failedDirect || statusInfo.setFailed || statusInfo.sameSetFailed);
      }).map(s => s.id));
    }

  if(blockedByFailedPrereq.length) {
    bodyHtml += `<div style="margin-top:18px;margin-bottom:18px;">
      <div style="font-size:13px;font-weight:700;color:var(--red);margin-bottom:10px;">
        <i class="fas fa-ban" style="margin-right:7px;"></i>
        Blocked Subjects — Failed Prerequisite
        <span style="font-size:10px;color:var(--muted);font-weight:400;margin-left:6px;">These subjects cannot be loaded because one or more prerequisites were failed.</span>
      </div>
      <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
          <thead>
            <tr style="background:linear-gradient(135deg,var(--red),#b91c1c);">
              <th style="padding:8px 10px;text-align:left;color:#fff;font-weight:700;border-bottom:2px solid #991b1b;">Course Code</th>
              <th style="padding:8px 10px;text-align:left;color:#fff;font-weight:700;border-bottom:2px solid #991b1b;">Subject Title</th>
              <th style="width:70px;padding:8px 10px;text-align:center;color:#fff;font-weight:700;border-bottom:2px solid #991b1b;">Units</th>
              <th style="padding:8px 10px;text-align:left;color:#fff;font-weight:700;border-bottom:2px solid #991b1b;">Prerequisite Details</th>
            </tr>
          </thead>
          <tbody>
            ${blockedByFailedPrereq.map(sub => {
              const pi = prereqUnlockMapCurrent[sub.id] || {};
              const direct = pi.directLocked && pi.directPrereqSubj ? pi.directPrereqSubj : null;
              const directGrade = direct ? gradeMap[direct.id] : null;
              const directText = direct ? `${esc(direct.subject_code)} — ${esc(direct.subject_name)} (${directGrade!=null?parseFloat(directGrade).toFixed(2):'No grade'})` : '';
              const setFails = (pi.setBlockedBy||[]).filter(ps => gradeMap[ps.id] != null && gradeStatus(roundGrade(gradeMap[ps.id])) === 'failed');
              const setText = setFails.map(ps => `${esc(ps.subject_code)} — ${esc(ps.subject_name)} (${parseFloat(gradeMap[ps.id]).toFixed(2)})`).join('<br>');
              const details = directText ? `Failed prerequisite: ${directText}` : setText ? `Failed prerequisite(s):<br>${setText}` : 'Prerequisite failed';
              return `<tr style="background:#fff;">
                <td style="padding:8px 10px;border-bottom:1px solid var(--border);font-weight:700;color:var(--dark);">${esc(sub.subject_code)}</td>
                <td style="padding:8px 10px;border-bottom:1px solid var(--border);color:var(--muted);">${esc(sub.subject_name)}</td>
                <td style="padding:8px 10px;text-align:center;border-bottom:1px solid var(--border);font-weight:600;color:var(--dark);">${parseFloat(sub.units)||0}</td>
                <td style="padding:8px 10px;border-bottom:1px solid var(--border);color:var(--red);font-size:11px;line-height:1.4;">${details}</td>
              </tr>`;
            }).join('')}
          </tbody>
        </table>
      </div>
    </div>`;
  }

  if(blockedByUnmetPrereq.length) {
    bodyHtml += `<div style="margin-top:18px;margin-bottom:18px;">
      <div style="font-size:13px;font-weight:700;color:var(--amber);margin-bottom:10px;">
        <i class="fas fa-exclamation-circle" style="margin-right:7px;"></i>
        Blocked Subjects — Prerequisite Not Yet Met
        <span style="font-size:10px;color:var(--muted);font-weight:400;margin-left:6px;">These subjects cannot be loaded because prerequisite subject(s) are still pending or have no grade.</span>
      </div>
      <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
          <thead>
            <tr style="background:linear-gradient(135deg,var(--amber),#b45309);">
              <th style="padding:8px 10px;text-align:left;color:#fff;font-weight:700;border-bottom:2px solid #92400e;">Course Code</th>
              <th style="padding:8px 10px;text-align:left;color:#fff;font-weight:700;border-bottom:2px solid #92400e;">Subject Title</th>
              <th style="width:70px;padding:8px 10px;text-align:center;color:#fff;font-weight:700;border-bottom:2px solid #92400e;">Units</th>
              <th style="padding:8px 10px;text-align:left;color:#fff;font-weight:700;border-bottom:2px solid #92400e;">Prerequisite Details</th>
            </tr>
          </thead>
          <tbody>
            ${blockedByUnmetPrereq.map(sub => {
              const pi = prereqUnlockMapCurrent[sub.id] || {};
              const direct = pi.directLocked && pi.directPrereqSubj ? pi.directPrereqSubj : null;
              const directGrade = direct ? gradeMap[direct.id] : null;
              const directText = direct ? `${esc(direct.subject_code)} — ${esc(direct.subject_name)} (${directGrade!=null?parseFloat(directGrade).toFixed(2):'No grade'})` : '';
              const setPending = (pi.setBlockedBy||[]).filter(ps => gradeMap[ps.id] == null);
              const setText = setPending.map(ps => `${esc(ps.subject_code)} — ${esc(ps.subject_name)} (No grade)`).join('<br>');
              const details = directText ? `Pending prerequisite: ${directText}` : setText ? `Pending prerequisite(s):<br>${setText}` : 'Prerequisite pending';
              return `<tr style="background:#fff;">
                <td style="padding:8px 10px;border-bottom:1px solid var(--border);font-weight:700;color:var(--dark);">${esc(sub.subject_code)}</td>
                <td style="padding:8px 10px;border-bottom:1px solid var(--border);color:var(--muted);">${esc(sub.subject_name)}</td>
                <td style="padding:8px 10px;text-align:center;border-bottom:1px solid var(--border);font-weight:600;color:var(--dark);">${parseFloat(sub.units)||0}</td>
                <td style="padding:8px 10px;border-bottom:1px solid var(--border);color:#92400e;font-size:11px;line-height:1.4;">${details}</td>
              </tr>`;
            }).join('')}
          </tbody>
        </table>
      </div>
    </div>`;
  }

  // Failed subjects
  if(failedSubs.length) {
    bodyHtml += `<div style="font-size:13px;font-weight:700;color:var(--red);margin-bottom:10px;">
      <i class="fas fa-redo" style="margin-right:7px;"></i>Failed Subjects — Must Retake
    </div>
    <div class="rm-subject-list">
      ${failedSubs.map(s=>`<div class="rm-retake-card">
        <div class="rm-sub-code">${esc(s.subject_code)}</div>
        <div class="rm-sub-name">${esc(s.subject_name)}</div>
        <div style="font-size:9px;font-weight:700;color:var(--red);margin-top:3px;">Grade: ${s.grade.toFixed(2)} — Failed</div>
</div>`).join('')}
    </div>`;
  }

  // Conditional
  if(condSubs.length) {
    bodyHtml += `<div style="font-size:13px;font-weight:700;color:var(--amber);margin-bottom:10px;margin-top:14px;">
      <i class="fas fa-exclamation-triangle" style="margin-right:7px;"></i>Conditional — Removal Exam Required
    </div>
    <div class="rm-subject-list">
      ${condSubs.map(s=>`<div class="rm-sub-card" style="border-left:4px solid var(--amber);">
        <div class="rm-sub-code">${esc(s.subject_code)}</div>
        <div class="rm-sub-name">${esc(s.subject_name)}</div>
        <div style="font-size:9px;font-weight:700;color:var(--amber);margin-top:3px;">Grade: ${s.grade.toFixed(2)} — Conditional (4.00)</div>
      </div>`).join('')}
    </div>`;
  }

  // ★ FLEXIBLE PROGRESSION: Proceed + Stay — both locked until subject load is finalized
  const flexNote = !allPassed ? `
    <div style="padding:10px 14px;background:var(--amber-l);border-radius:10px;border:1px solid var(--amber-b);margin-bottom:10px;">
      <div style="font-size:11px;font-weight:700;color:#92400e;margin-bottom:3px;"><i class="fas fa-info-circle"></i> Flexible Progression</div>
      <div style="font-size:10px;color:#a16207;line-height:1.5;">Student may proceed to the next semester. Only subjects with unmet prerequisites remain locked. Failed subjects without prerequisites can be retaken in a future term.</div>
    </div>` : '';

   // Gate notice shown above the buttons before finalization
   const gateNoticeHtml = `
   <div id="rmFinalizeGate" style="padding:12px 16px;background:linear-gradient(135deg,#fef3c7,#fde68a);border:2px dashed #f59e0b;border-radius:12px;margin-bottom:12px;text-align:center;">
     <div style="font-size:13px;font-weight:700;color:#92400e;margin-bottom:4px;">
       <i class="fas fa-lock" style="margin-right:6px;"></i>Finalize Subject Load to Continue
     </div>
     <div style="font-size:11px;color:#a16207;margin-bottom:10px;line-height:1.5;">
       Confirm and finalize the subject load above, then choose to <strong>Proceed</strong> to the next semester or <strong>Stay</strong> on the current one.
     </div>
     <button id="rmFinalizeLoadBtn" onclick="rmFinalizeSubjectLoad('${yearLabel}','${semLabel}','${nextYearLabel}','${nextSemLabel}')"
       style="padding:10px 24px;background:linear-gradient(135deg,#d97706,#b45309);color:#fff;border:none;border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 14px rgba(217,119,6,.35);transition:all .2s;"
       disabled>
       <i class="fas fa-check-square"></i> Finalize Subject Load
     </button>
     <div style="font-size:10px;color:#92400e;margin-top:8px;opacity:0.8;">
       <i class="fas fa-arrow-up" style="font-size:8px;"></i> Confirm the subject load list above first
     </div>
   </div>`;

   const graduationContainerHtml = isGraduationEligible ? `
   <div id="graduationContainer" class="graduation-premium-card">
     <div class="graduation-confetti" aria-hidden="true"></div>
     <div class="graduation-icon-ring">
       <i class="fas fa-user-graduate"></i>
     </div>
     <div class="graduation-badge-row">
       <span class="graduation-cap-badge"><i class="fas fa-award"></i> Congratulations</span>
       <span class="graduation-status-pill"><i class="fas fa-check-double"></i> Student Eligible for Graduation</span>
     </div>
     <p class="graduation-lead">All required curriculum subjects are completed with passing grades. No outstanding back subjects.</p>
     <div class="graduation-stat-grid">
       <div class="graduation-stat">
         <div class="graduation-stat-label">Total subjects completed</div>
         <div class="graduation-stat-value">${totalProgSubjects}</div>
       </div>
       <div class="graduation-stat graduation-stat-gold">
         <div class="graduation-stat-label">Final GWA</div>
         <div class="graduation-stat-value">${finalGwaStr}</div>
       </div>
       <div class="graduation-stat">
         <div class="graduation-stat-label">Graduation status</div>
         <div class="graduation-stat-value graduation-stat-sm">Eligible — pending confirmation</div>
       </div>
     </div>
     <button type="button" onclick="confirmGraduation('${yearLabel}','${semLabel}')"
       class="graduation-confirm-btn"
       id="graduationConfirmBtn">
       <i class="fas fa-certificate"></i> Confirm graduation &amp; generate prospectus PDF
     </button>
     <p class="graduation-footnote"><i class="fas fa-shield-alt"></i> This marks the student as graduated, saves the official PDF under <strong>C:\\graduate\\batch ${esc(currentAY)}\\…</strong>, and locks further evaluation.</p>
     <div style="display:flex;justify-content:center;margin-top:14px;">
       <button type="button" class="btn-modal-close" onclick="closeResultModal()"><i class="fas fa-times"></i> Close</button>
     </div>
   </div>` : '';

    let actionsHtml = `<div class="rm-actions" style="flex-direction:column;gap:10px;">
      ${flexNote}
      ${isGraduationEligible ? graduationContainerHtml : `
        ${gateNoticeHtml}
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
          <button class="btn-promote" id="rmProceedBtn"
            onclick="promoteStudent('${yearLabel}','${semLabel}','${nextYearLabel}','${nextSemLabel}')"
            disabled title="Finalize subject load first"
            style="opacity:0.45;cursor:not-allowed;filter:grayscale(0.3);">
            <i class="fas fa-lock" id="rmProceedIcon"></i>
            <span id="rmProceedLabel">Proceed to ${nextSemLabel} — ${nextYearLabel}</span>
          </button>
          <button class="btn-stay" id="rmStayBtn"
            onclick="stayStudent('${yearLabel}','${semLabel}')"
            disabled title="Finalize subject load first"
            style="padding:12px 22px;background:linear-gradient(135deg,#64748b,#475569);color:#fff;border:none;border-radius:12px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;cursor:not-allowed;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 14px rgba(100,116,139,.3);transition:all .3s;opacity:0.45;filter:grayscale(0.3);">
            <i class="fas fa-lock" id="rmStayIcon"></i>
            <span id="rmStayLabel">Stay — ${yearLabel} · ${semLabel}</span>
          </button>
          <button class="btn-modal-close" onclick="closeResultModal()"><i class="fas fa-times"></i> Close</button>
        </div>
      `}
    </div>`;

   document.getElementById('resultModalContent').innerHTML = `
     <div class="rm-header ${headerClass}">
       <div class="rm-icon ${iconClass}">${iconContent}</div>
       <div class="rm-header-text">
         <div class="rm-semester-tag">${esc(yearLabel)} · ${esc(semLabel)} · A.Y. ${esc(currentAY)}</div>
         <div class="rm-verdict">${verdict}</div>
         <div class="rm-verdict-sub">${verdictSub}</div>
         ${semGWA!=null?`<div class="rm-gwa-chip"><i class="fas fa-chart-line"></i> Semester GWA: ${semGWA.toFixed(2)}</div>`:''}
       </div>
       <div class="rm-header-actions">
       </div>
     </div>
     <div class="rm-body">
       ${breakdownHtml}
       ${bodyHtml}
       ${actionsHtml}
     </div>`;
document.getElementById('resultModal').classList.add('open');
    
    // Note: Graduation is confirmed only when user clicks the button, not auto-triggered
    

}

function closeResultModal() {
  document.getElementById('resultModal').classList.remove('open');
  clearFocus();
  if (graduationRedirectOnClose) {
    graduationRedirectOnClose = false;
    window.location.href = 'setup_graduate.php';
  }
}

/* ═══════════════════════════════════════════════════════════
   FINALIZE SUBJECT LOAD (inside Result Modal)
   Locks the confirmed subject list and enables Proceed + Stay.
═══════════════════════════════════════════════════════════ */
function rmFinalizeSubjectLoad(fromYear, fromSem, toYear, toSem) {
  // Disable the finalize button to prevent double-click
  const finalizeBtn = document.getElementById('rmFinalizeLoadBtn');
  if(finalizeBtn) {
    finalizeBtn.disabled = true;
    finalizeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finalizing…';
  }

  // Save finalization to server
  const fd = new FormData();
  fd.append('action','finalize_session');
  fd.append('student_id', currentStudent.id);
  fd.append('major_id', currentStudent.major_id||0);
  fd.append('academic_year', currentAY);
  fd.append('year_level', fromYear);
  fd.append('semester', fromSem);
  fetch(EVAL_PROC, {method:'POST', body:fd}).catch(()=>{});

  // Mark as finalized locally
  const fromKey = `${fromYear}|${fromSem}`;
  finalizedMap[fromKey] = true;

  // Update the gate notice to show finalized state
  const gateDiv = document.getElementById('rmFinalizeGate');
  if(gateDiv) {
    gateDiv.style.background = 'linear-gradient(135deg,#dcfce7,#bbf7d0)';
    gateDiv.style.borderColor = '#22c55e';
    gateDiv.style.borderStyle = 'solid';
    gateDiv.innerHTML = `
      <div style="font-size:13px;font-weight:700;color:#166534;margin-bottom:4px;">
        <i class="fas fa-check-circle" style="margin-right:6px;"></i>Subject Load Finalized
      </div>
      <div style="font-size:11px;color:#166534;line-height:1.5;">
        The subject load for <strong>${fromYear} — ${fromSem}</strong> has been finalized and locked.
        Now choose how to proceed for the student.
      </div>`;
  }

  // ★ Enable the Proceed button
  const proceedBtn = document.getElementById('rmProceedBtn');
  if(proceedBtn) {
    proceedBtn.disabled = false;
    proceedBtn.style.opacity = '1';
    proceedBtn.style.cursor = 'pointer';
    proceedBtn.style.filter = '';
    document.getElementById('rmProceedIcon').className = 'fas fa-arrow-circle-up';
  }

  // ★ Enable the Stay button
  const stayBtn = document.getElementById('rmStayBtn');
  if(stayBtn) {
    stayBtn.disabled = false;
    stayBtn.style.opacity = '1';
    stayBtn.style.cursor = 'pointer';
    stayBtn.style.filter = '';
    stayBtn.title = '';
    document.getElementById('rmStayIcon').className = 'fas fa-map-pin';
  }

  toast('Subject load finalized! Choose Proceed or Stay.', 'success', 3500);
  applyFocusVisuals();
}

/* ═══════════════════════════════════════════════════════════
   STAY STUDENT — keeps same year_level, saves finalized load
═══════════════════════════════════════════════════════════ */
function stayStudent(fromYear, fromSem) {
  if(!currentStudent) { toast('No student loaded.', 'error'); return; }

  const fd = new FormData();
  fd.append('action', 'stay_student');
  fd.append('student_id', currentStudent.id);
  fd.append('year_level', fromYear);
  fd.append('semester', fromSem);
  fd.append('academic_year', currentAY);
  if(confirmedEnrollmentSubjectIds.length) {
    fd.append('confirmed_subject_ids', JSON.stringify(confirmedEnrollmentSubjectIds));
  }

  // Disable both buttons while saving
  const proceedBtn = document.getElementById('rmProceedBtn');
  const stayBtn    = document.getElementById('rmStayBtn');
  if(proceedBtn) { proceedBtn.disabled = true; proceedBtn.style.opacity = '0.6'; }
  if(stayBtn)    { stayBtn.disabled = true; stayBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…'; }

  fetch(EVAL_PROC, {method:'POST', body:fd})
    .then(r => r.json())
    .then(data => {
      // Even if the server returns a "not found" action, we still handle it gracefully —
      // the finalization + enrollment list save was already done by rmFinalizeSubjectLoad
      // and rmConfirmEnrollmentList respectively.

      // Unfinalize the current semester so the instructor can continue editing
      const unfinalizefd = new FormData();
      unfinalizefd.append('action', 'unfinalize_session');
      unfinalizefd.append('student_id', currentStudent.id);
      unfinalizefd.append('major_id', currentStudent.major_id || 0);
      unfinalizefd.append('academic_year', currentAY);
      unfinalizefd.append('year_level', fromYear);
      unfinalizefd.append('semester', fromSem);

      return fetch(EVAL_PROC, {method:'POST', body:unfinalizefd})
        .then(r => r.json())
        .then(unfinalizeData => {
          if(unfinalizeData.success) {
            const fkey = `${fromYear}|${fromSem}`;
            delete finalizedMap[fkey];
          }
        })
        .catch(err => console.error('Unfinalize error:', err));
    })
    .then(() => {
      toast(`Student stays at ${fromYear} — ${fromSem}. Subject load saved & unfin realized.`, 'success', 4000);

      setTimeout(() => {
        closeResultModal();
        if(currentStudent) {
          const studentCopy = {...currentStudent};
          closeEval();
          setTimeout(() => openEval(studentCopy), 200);
        }
        loadMentees();
      }, 1200);
    })
     .catch(() => {
       // Network error — still proceed locally since the important data was already saved
       toast(`Student stays at ${fromYear} — ${fromSem}. (offline)`, 'success', 3500);
       const fkey = `${fromYear}|${fromSem}`;
       delete finalizedMap[fkey];

       setTimeout(() => {
         closeResultModal();
         if(currentStudent) {
           const studentCopy = {...currentStudent};
           closeEval();
           setTimeout(() => openEval(studentCopy), 200);
         }
         loadMentees();
       }, 1200);
     });
}

function showGraduationProgressModal() {
  // showGraduationProgressModal — opens the animated loading overlay
  const modal = document.getElementById('graduationProgressModal');
  if (modal) { modal.style.display = 'flex'; }
}

function hideGraduationProgressModal() {
  const modal = document.getElementById('graduationProgressModal');
  if (modal) { modal.style.display = 'none'; }
}

/**
 * updateGraduationProgress(percent, activeStep, subText)
 *  percent    – 0‒100
 *  activeStep – 1‒4  (current step; steps before it are marked done)
 *  subText    – optional string shown under the active step
 */
function updateGraduationProgress(percent, activeStep, subText) {
  // bar
  const bar = document.getElementById('graduationProgressBar');
  const pct = document.getElementById('progressPercentage');
  if (bar) bar.style.width = Math.min(percent, 100) + '%';
  if (pct) pct.textContent  = Math.round(Math.min(percent, 100)) + '%';

  // step configs
  const stepIcons = [
    'fas fa-folder-plus',
    'fas fa-file-pdf',
    'fas fa-save',
    'fas fa-database',
  ];
  const stepIconsDone  = 'fas fa-check';
  const stepIconsActive = [
    'fas fa-folder-open gpm-step-spin',
    'fas fa-cog gpm-step-spin',
    'fas fa-spinner gpm-step-spin',
    'fas fa-database gpm-step-spin',
  ];

  for (let i = 1; i <= 4; i++) {
    const stepEl  = document.getElementById('step' + i);
    if (!stepEl) continue;
    const iconEl  = stepEl.querySelector('.gpm-step-icon');

    stepEl.classList.remove('active', 'done');

    if (i < activeStep) {
      // completed
      stepEl.classList.add('done');
      if (iconEl) iconEl.innerHTML = `<i class="${stepIconsDone}"></i>`;
    } else if (i === activeStep) {
      // currently running
      stepEl.classList.add('active');
      if (iconEl) iconEl.innerHTML = `<i class="${stepIconsActive[i-1]}"></i>`;
      // update sub-text if provided
      if (subText) {
        const sub = stepEl.querySelector('.gpm-step-sub');
        if (sub) sub.textContent = subText;
      }
    } else {
      // pending
      if (iconEl) iconEl.innerHTML = `<i class="${stepIcons[i-1]}"></i>`;
    }
  }

  // subtitle
  const subtitles = [
    'Creating folder structure on local drive…',
    'Rendering official prospectus document…',
    'Writing PDF file to disk…',
    'Locking evaluation & saving record…',
  ];
  const gpmSub = document.getElementById('gpmSubtitle');
  if (gpmSub && activeStep >= 1 && activeStep <= 4) {
    gpmSub.textContent = subtitles[activeStep - 1];
  }
}

function _gpmMarkAllDone(pdfPath) {
  // turn all steps green
  for (let i = 1; i <= 4; i++) {
    const stepEl = document.getElementById('step' + i);
    if (!stepEl) continue;
    stepEl.classList.remove('active');
    stepEl.classList.add('done');
    const iconEl = stepEl.querySelector('.gpm-step-icon');
    if (iconEl) iconEl.innerHTML = '<i class="fas fa-check"></i>';
  }
  // bar done
  const bar = document.getElementById('graduationProgressBar');
  if (bar) { bar.style.width = '100%'; bar.classList.add('done-bar'); }
  const pct = document.getElementById('progressPercentage');
  if (pct) pct.textContent = '100%';
  // icon
  const wrap = document.getElementById('gpmIconWrap');
  const ico  = document.getElementById('gpmIcon');
  if (wrap) wrap.classList.add('done');
  if (ico)  { ico.className = 'fas fa-check'; }
  // title / subtitle
  const t = document.getElementById('gpmTitle');
  const s = document.getElementById('gpmSubtitle');
  if (t) t.textContent = 'Graduation Confirmed!';
  if (s) {
    s.style.color = '#15803d';
    s.textContent = pdfPath
      ? 'PDF saved → ' + pdfPath
      : 'Official prospectus saved to C:\\graduate\\batch …';
  }
}

/* ── main confirm function ─────────────────────────────────────────── */
function confirmGraduation(yearLabel, semLabel) {
  if (!currentStudent) { toast('No student loaded.', 'error'); return; }

  // custom confirm dialog (replace browser confirm for nicer UX if desired)
  if (!confirm(
    'Confirm Graduation?\n\n' +
    'This will:\n' +
    '  • Create C:\\graduate\\batch ' + currentAY + '\\… folder if needed\n' +
    '  • Generate the official prospectus PDF\n' +
    '  • Mark the student as Graduated\n' +
    '  • Lock further evaluation\n\n' +
    'This action CANNOT be undone.'
  )) { return; }

  _gradConfirmBtn = document.getElementById('graduationConfirmBtn');
  if (_gradConfirmBtn) { _gradConfirmBtn.disabled = true; _gradConfirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…'; }

  // ── show modal, kick off animated progress ──────────────────────────
  showGraduationProgressModal();
  updateGraduationProgress(5, 1, 'C:\\graduate\\batch ' + currentAY + '\\…');

  // Smooth fake-progress ticker that pauses near 80% while waiting for server
  let fakeP = 5;
  const fakeTick = setInterval(() => {
    if (fakeP >= 80) { clearInterval(fakeTick); return; }
    const delta = fakeP < 30 ? 8 : fakeP < 55 ? 5 : 2;
    fakeP = Math.min(fakeP + delta * Math.random(), 80);
    const fakeStep = fakeP < 25 ? 1 : fakeP < 50 ? 2 : fakeP < 72 ? 3 : 3;
    updateGraduationProgress(fakeP, fakeStep);
  }, 380);

  // step 1 → 2 after short delay (folder creation is fast)
  setTimeout(() => updateGraduationProgress(28, 2), 900);

  // ── POST to server ──────────────────────────────────────────────────
  const fd = new FormData();
  fd.append('action',        'confirm_graduation');
  fd.append('student_id',    currentStudent.id);
  fd.append('major_id',      currentStudent.major_id || 0);
  fd.append('academic_year', currentAY);
  fd.append('year_level',    yearLabel);
  fd.append('semester',      semLabel);

  fetch(EVAL_PROC, { method:'POST', body:fd, credentials:'same-origin', headers:{'Accept':'application/json'} })
    .then(async r => {
      const text = await r.text();
      if (!r.ok) throw new Error('Server HTTP ' + r.status + ': ' + text);
      if (/^\s*</.test(text)) throw new Error('Server returned HTML (check PHP errors): ' + text.slice(0, 300));
      try { return JSON.parse(text); } catch (e) { throw new Error('Invalid JSON: ' + text.slice(0, 300)); }
    })
    .then(data => {
      clearInterval(fakeTick);

      if (data.success) {
        // animate to step 3 → 4 → 100
        updateGraduationProgress(82, 3, 'Writing to ' + (data.pdf_path || 'C:\\graduate\\…'));
        setTimeout(() => {
          updateGraduationProgress(93, 4);
          setTimeout(() => {
            _gpmMarkAllDone(data.pdf_path || '');

            // pause 1.2 s so user can read "done", then transition
            setTimeout(() => {
              hideGraduationProgressModal();

              // ── open PDF in a new tab ─────────────────────────────────
              if (data.pdf_url) {
                window.open(data.pdf_url, '_blank');
              }

              // Redirect the instructor to the graduate roster page after confirmation.
              setTimeout(() => {
                const batchPart = currentAY || '';
                window.location.href = 'setup_graduate.php?from_eval=1&batch=' + encodeURIComponent(batchPart);
              }, 900);

              // ── swap graduation card to "officially graduated" view ─
              const graduationContainer = document.getElementById('graduationContainer');
              if (graduationContainer) {
                const gradDate = data.graduation_date
                  || new Date().toLocaleDateString('en-PH', {year:'numeric',month:'long',day:'numeric'});
                const savedPath = data.pdf_path
                  ? data.pdf_path.replace(/\//g,'\\')
                  : 'C:\\graduate\\batch ' + esc(currentAY) + '\\…';

                graduationContainer.innerHTML = `
                <div style="position:relative;overflow:hidden;padding:32px 28px 24px;
                     background:linear-gradient(145deg,#f0fdf4,#dcfce7,#bbf7d0);
                     border:2px solid #10b981;border-radius:20px;text-align:center;
                     box-shadow:0 12px 40px rgba(16,185,129,0.22),0 2px 8px rgba(0,0,0,0.07);
                     animation:slideInGreen .5s cubic-bezier(.34,1.56,.64,1);">
                  <style>
                    @keyframes slideInGreen{from{opacity:0;transform:translateY(20px) scale(.97)}to{opacity:1;transform:none}}
                    @keyframes popInG{from{transform:scale(0) rotate(-20deg);opacity:0}60%{transform:scale(1.15)}to{transform:scale(1) rotate(0);opacity:1}}
                  </style>
                  <div style="width:90px;height:90px;margin:0 auto 16px;
                       background:linear-gradient(145deg,#4ade80,#16a34a,#15803d);border-radius:50%;
                       display:flex;align-items:center;justify-content:center;font-size:36px;color:#fff;
                       box-shadow:0 6px 24px rgba(22,163,74,.4),0 0 0 8px rgba(74,222,128,.15),0 0 0 16px rgba(74,222,128,.07);
                       animation:popInG .65s .1s cubic-bezier(.34,1.56,.64,1) both;">
                    <i class="fas fa-user-graduate"></i>
                  </div>
                  <div style="display:inline-flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap;justify-content:center;">
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 14px;
                         background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border-radius:20px;
                         font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;
                         box-shadow:0 2px 8px rgba(22,163,74,.4);">
                      <i class="fas fa-award"></i> Congratulations!
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 14px;
                         background:linear-gradient(135deg,#fef9e7,#fef3c7);color:#92400e;
                         border:1.5px solid #d4a017;border-radius:20px;font-size:11px;font-weight:700;">
                      <i class="fas fa-graduation-cap"></i> Officially Graduated
                    </span>
                  </div>
                  <h3 style="font-size:22px;font-weight:800;color:#166534;margin:0 0 6px;font-family:'Playfair Display',serif;">
                    Student has Graduated!
                  </h3>
                  <p style="font-size:12px;color:#166534;margin:0 0 20px;opacity:.85;">
                    Curriculum completed. Evaluation locked. Official record saved.
                  </p>
                  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px;">
                    <div style="padding:14px;background:rgba(22,163,74,.1);border:1.5px solid rgba(22,163,74,.25);border-radius:14px;">
                      <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#166534;margin-bottom:6px;">Subjects Completed</div>
                      <div style="font-size:28px;font-weight:800;color:#166534;font-family:'Playfair Display',serif;">${data.total_subjects||0}</div>
                    </div>
                    <div style="padding:14px;background:rgba(22,163,74,.1);border:1.5px solid rgba(22,163,74,.25);border-radius:14px;">
                      <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#166534;margin-bottom:6px;">Final GWA</div>
                      <div style="font-size:28px;font-weight:800;color:#166534;font-family:'Playfair Display',serif;">${data.gwa||'—'}</div>
                    </div>
                    <div style="padding:14px;background:rgba(22,163,74,.1);border:1.5px solid rgba(22,163,74,.25);border-radius:14px;">
                      <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#166534;margin-bottom:6px;">Status</div>
                      <div style="font-size:15px;font-weight:700;color:#166534;margin-top:4px;"><i class="fas fa-check-circle"></i> Graduated</div>
                    </div>
                    <div style="padding:14px;background:rgba(22,163,74,.1);border:1.5px solid rgba(22,163,74,.25);border-radius:14px;">
                      <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#166534;margin-bottom:6px;">Graduation Date</div>
                      <div style="font-size:11px;font-weight:700;color:#166534;margin-top:4px;">${gradDate}</div>
                    </div>
                  </div>
                  <div style="margin-bottom:16px;padding:10px 14px;
                       background:rgba(22,163,74,.07);border:1px solid rgba(22,163,74,.18);
                       border-radius:10px;font-size:11px;color:#166534;text-align:left;word-break:break-all;">
                    <i class="fas fa-folder-open" style="color:#d4a017;margin-right:6px;"></i>
                    <strong>Saved to:</strong> ${esc(savedPath)}
                  </div>
                  <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                    <button type="button"
                      onclick="window.open('../../../data/download_graduation_pdf.php?student_id=${currentStudent.id}','_blank')"
                      style="padding:12px 22px;background:linear-gradient(135deg,#10b981,#059669);
                             border:1.5px solid #10b981;border-radius:50px;font-size:13px;font-weight:700;
                             color:#fff;cursor:pointer;display:inline-flex;align-items:center;gap:8px;
                             box-shadow:0 4px 12px rgba(16,185,129,0.3);">
                      <i class="fas fa-file-pdf"></i> Open PDF
                    </button>
                    <button type="button"
                      onclick="loadMentees(); closeResultModal();"
                      style="padding:12px 22px;background:rgba(22,163,74,.1);border:1.5px solid #10b981;
                             border-radius:50px;font-size:13px;font-weight:700;color:#166534;
                             cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
                      <i class="fas fa-times"></i> Close & View Graduate List
                    </button>
                  </div>
                </div>`;
              }

              // lock proceed / stay buttons
              ['rmProceedBtn','rmStayBtn'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.disabled = true; el.style.opacity='0.5'; el.style.cursor='not-allowed'; }
              });

            }, 1200);   // pause after "done" state
          }, 600);      // step 4 settle
        }, 500);        // step 3 settle

       } else {
         clearInterval(fakeTick);
         if (_gradConfirmBtn) { _gradConfirmBtn.disabled = false; _gradConfirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Graduation'; }
        const msg = (data && data.message) ? data.message : 'Graduation confirmation failed.';
        const bar = document.getElementById('graduationProgressBar');
        const pct = document.getElementById('progressPercentage');
        const wrap = document.getElementById('gpmIconWrap');
        const ico  = document.getElementById('gpmIcon');
        const t    = document.getElementById('gpmTitle');
        const s    = document.getElementById('gpmSubtitle');
        if (bar) { bar.style.width = '80%'; bar.style.background = 'linear-gradient(90deg,#ef4444,#dc2626)'; bar.style.animation = 'none'; }
        if (pct) { pct.textContent = '80%'; pct.style.color = '#dc2626'; }
        if (wrap) wrap.style.background = 'linear-gradient(145deg,#fca5a5,#ef4444)';
        if (ico)  { ico.className = 'fas fa-times-circle'; }
        if (t)    { t.textContent = 'Graduation Failed'; t.style.color = '#dc2626'; }
        if (s)    { s.innerHTML = '⚠ ' + esc(msg); s.style.color = '#7f1d1d'; s.style.fontWeight = '600'; s.style.minHeight = 'auto'; }
        // keep modal open; dismiss on backdrop click or Escape
        const nativeClose = hideGraduationProgressModal;
        function dismissError() {
          const m = document.getElementById('graduationProgressModal');
          if (m) { m.style.display = 'none'; }
        }
        hideGraduationProgressModal = function () {
          const m = document.getElementById('graduationProgressModal');
          if (m) { m.style.display = 'none'; }
          hideGraduationProgressModal = nativeClose;
        };
        const modal = document.getElementById('graduationProgressModal');
        if (modal) {
          modal.addEventListener('click', function (e) { if (e.target === modal) dismissError(); }, { once: true });
        }
        document.addEventListener('keydown', function _onEsc(e) { if (e.key === 'Escape') { dismissError(); document.removeEventListener('keydown', _onEsc); } });
        console.error('Error confirming graduation:', msg);
        toast('Error: ' + msg, 'error');
        return;
      }
    })
    .catch(err => {
      clearInterval(fakeTick);
      hideGraduationProgressModal();
      console.error('Error confirming graduation:', err);
      toast('Error confirming graduation: ' + err.message, 'error');
      if(_gradConfirmBtn) {
        _gradConfirmBtn.disabled = false;
        _gradConfirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Graduation';
      }
    });
}

/* ═════════════════════════════════════════════════════════════

/* ═══════════════════════════════════════════════════════════
   GRADES VIEW MODAL
═══════════════════════════════════════════════════════════ */
function openGradesModal(year, sem) {
  // Determine which subjects to display for this year+semester view.
  //
  // A subject is shown when it belongs to this period AND is "officially present":
  //   • is_in_load = true  (explicitly enrolled via the enrollment modal), OR
  //   • is_credited = true (transfer credited), OR
  //   • grade_rounded != null (grade already saved — this covers past finalized
  //     semesters re-opened from "View Grades" after a page refresh, where
  //     load_target_year/sem annotations are no longer available in memory)
  //
  // Cross-year additional subjects (native year_level ≠ requested year) are
  // included when load_target_year/sem match — these are stamped by
  // rmConfirmEnrollmentList in the current session.  After a refresh they are
  // identified by having a saved grade but a different native year_level, which
  // is caught by the graded-fallback above only if they're in the right semester.
  const semTok = sem.includes('1st') ? '1st' : '2nd';

  const filteredSubjects = loadedSubjects.filter(s => {
    const sYear  = (s.year_level||'').trim();
    const sSem   = (s.semester||'').toLowerCase();
    const isGraded  = gradeMap[s.id] != null || s.grade_rounded != null;
    const isInLoad  = !!(s.is_in_load || s.is_credited || isGraded);

    // 1. Native subject of this year+semester — show if in load or graded
    const isNative = sYear === year && sSem.includes(semTok);
    if (isNative && isInLoad) return true;

    // 2. Cross-year additional: enrolled INTO this year+semester this session
    //    (load_target_year/sem are set by rmConfirmEnrollmentList)
    const targetYear = s.load_target_year || '';
    const targetSem  = (s.load_target_sem  || '').toLowerCase();
    if (targetYear && targetSem) {
      const isAdditional = targetYear === year && targetSem.includes(semTok);
      if (isAdditional && !!(s.is_in_load || s.is_credited)) return true;
    }

    return false;
  });

  // Build student name
  const m = currentStudent;
  const fullName = `${m.first_name}${m.middle_name?' '+m.middle_name:''} ${m.last_name}${m.suffix?' '+m.suffix:''}`.trim();
  
  // Get advisor and program head names from global scope
  const advisorNameModal = window.advisorName || '';
  const programHeadNameModal = window.programHeadName || '';

   // Update modal header
   document.getElementById('gmSemesterTitle').textContent = `${year} — ${sem} · A.Y. ${currentAY}`;
   document.getElementById('gmStudentName').textContent = fullName;
   
   // Compute totals for the hint badge
   // "curriculum" total = all subjects natively belonging to this year+semester
   const totalInPeriod = loadedSubjects.filter(s => {
     const sYear = (s.year_level||'').trim();
     const sSem  = (s.semester||'').toLowerCase();
     return sYear === year && sSem.includes(semTok);
   }).length;
   const loadedCount = filteredSubjects.length;
   
   // Show school header with major
   const schoolHeader = document.getElementById('gmSchoolHeader');
   schoolHeader.style.display = 'block';
   document.getElementById('gmSchoolName').textContent = phSettings.school_name || phSettings.schoolName || '';
   document.getElementById('gmSchoolAddress').textContent = phSettings.school_address || phSettings.schoolAddress || '';
   document.getElementById('gmInstitute').textContent = phSettings.institute_name || phSettings.instituteName || '';
   document.getElementById('gmDegree').textContent = `Major in ${m.major_name || '—'}`;
   
   // Update student info block
   const studentInfo = document.getElementById('gmStudentInfo');
   studentInfo.style.display = 'block';
   
   document.getElementById('gmInfoStudent').textContent = fullName;
   document.getElementById('gmInfoStudentID').textContent = m.student_number || m.student_id || '—';
   document.getElementById('gmInfoPeriod').textContent = `${year} — ${sem} · A.Y. ${currentAY}`;
   document.getElementById('gmInfoYearLevel').textContent = year;
   document.getElementById('gmInfoSemester').textContent = sem;

  // Build table rows
  const tbody = document.getElementById('gmTableBody');
  const emptyState = document.getElementById('gmEmptyState');
  
  if(filteredSubjects.length === 0) {
    tbody.innerHTML = '';
    emptyState.style.display = 'block';
    emptyState.innerHTML = `<i class="fas fa-inbox" style="font-size:28px;color:var(--muted);margin-bottom:8px;display:block;"></i>No subjects loaded for this period.<br><span style="font-size:11px;color:var(--muted);">Subjects appear here once confirmed via the Subject Load selection during evaluation.</span>`;
  } else {
    emptyState.style.display = 'none';
    let totalUnits = 0;
    let gwaSum = 0;
    let gradedCount = 0;

    tbody.innerHTML = filteredSubjects.map(s => {
      const units = parseFloat(s.units) || 0;
      totalUnits += units;
      const rawGrade = gradeMap[s.id];
      let gradeDisplay = '—';
      let gradeClass = '';
      
      if(rawGrade != null) {
        const rounded = roundGrade(parseFloat(rawGrade));
        gwaSum += rounded * units;
        gradedCount++;
        gradeDisplay = rounded.toFixed(2);
        if(rounded <= 3.00) gradeClass = 'pass';
        else if(rounded === 4.00) gradeClass = 'cond';
        else gradeClass = 'fail';
      }

      // Is this a cross-year additional subject? Show its native year as a tag.
      const isNative = (s.year_level||'').trim() === year;
      const additionalTag = !isNative
        ? `<span style="display:inline-block;font-size:8px;padding:1px 5px;background:#dbeafe;color:#1e40af;border-radius:4px;border:1px solid #93c5fd;margin-left:4px;white-space:nowrap;font-weight:700;">+${esc(s.year_level)}</span>`
        : '';

      return `
        <tr class="gm-tr">
          <td class="gm-td gm-code">${esc(s.subject_code)}${additionalTag}</td>
          <td class="gm-td gm-subject">${esc(s.subject_name)}</td>
          <td class="gm-td gm-units">${units}</td>
          <td class="gm-td gm-grade ${gradeClass}">${gradeDisplay}</td>
        </tr>
      `;
    }).join('');

    // Compute GWA for this semester
    const gwa = (gradedCount > 0 && totalUnits > 0) ? gwaSum / totalUnits : 0;
    
    // Show summary on gold bar
    const summaryEl = document.getElementById('gmSummary');
    const summaryLeft = document.getElementById('gmSummaryLeft');
    const summaryGWA = document.getElementById('gmSummaryGWA');
    if(gradedCount > 0) {
      summaryEl.style.display = 'block';
      summaryLeft.innerHTML = `<strong>${gradedCount}</strong> of <strong>${filteredSubjects.length}</strong> subjects graded · <strong>${totalUnits}</strong> units`;
      summaryGWA.innerHTML = `GWA: ${gwa.toFixed(2)}`;
    } else {
      summaryEl.style.display = 'none';
    }
    
    // Show signature block (Adviser and Program Head only)
    const sigBlock = document.getElementById('gmSigBlock');
    document.getElementById('gmSigAdvisor').textContent = advisorNameModal || 'Adviser';
    document.getElementById('gmSigPH').textContent = programHeadNameModal || 'Program Head';
    sigBlock.style.display = 'block';
    
    // Update hint
    if(gradedCount > 0 && totalUnits > 0) {
      document.getElementById('gmHint').innerHTML = `
        <i class="fas fa-chart-line" style="margin-right:4px;color:var(--gold-d);"></i>
        <strong>${gradedCount}</strong> of <strong>${filteredSubjects.length}</strong> subjects graded &nbsp;·&nbsp; 
        <strong>${totalUnits}</strong> units &nbsp;·&nbsp; 
        Semester GWA: <strong style="color:var(--gold-d);font-size:13px;">${gwa.toFixed(2)}</strong>
      `;
    } else {
      document.getElementById('gmHint').innerHTML = `
        <span style="color:var(--muted);"><strong>${gradedCount}</strong> of <strong>${filteredSubjects.length}</strong> subjects graded &nbsp;·&nbsp; <strong>${totalUnits}</strong> total units</span>
      `;
    }
    
     // Update hint — clarify that only loaded subjects are shown
    if(gradedCount > 0 && totalUnits > 0) {
      document.getElementById('gmHint').innerHTML = `
        <i class="fas fa-layer-group" style="margin-right:4px;color:var(--gold-d);"></i>
        Showing <strong>${loadedCount}</strong> enrolled subject(s) of <strong>${totalInPeriod}</strong> in curriculum &nbsp;·&nbsp;
        <strong>${gradedCount}</strong> graded &nbsp;·&nbsp; 
        <strong>${totalUnits}</strong> units &nbsp;·&nbsp; 
        Semester GWA: <strong style="color:var(--gold-d);font-size:13px;">${gwa.toFixed(2)}</strong>
      `;
    } else {
      document.getElementById('gmHint').innerHTML = `
        <span style="color:var(--muted);">Showing <strong>${loadedCount}</strong> enrolled subject(s) &nbsp;·&nbsp; <strong>${gradedCount}</strong> of <strong>${loadedCount}</strong> graded &nbsp;·&nbsp; <strong>${totalUnits}</strong> total units</span>
      `;
    }
  }

  document.getElementById('gradesModal').classList.add('open');
}

function closeGradesModal() {
  document.getElementById('gradesModal').classList.remove('open');
}

function printGradesTable() {
  const title = document.getElementById('gmSemesterTitle').textContent;
  const studentName = document.getElementById('gmStudentName').textContent;
  const advisorName = window.advisorName || 'Adviser';
  const programHeadName = window.programHeadName || 'Program Head';
  const table = document.querySelector('.gm-table');
  const schoolName = phSettings.school_name || phSettings.schoolName || 'Northern Bukidnon State College';
  const schoolAddress = phSettings.school_address || phSettings.schoolAddress || 'Manolo Fortich, Bukidnon';
  const instituteName = phSettings.institute_name || phSettings.instituteName || 'Institute for Business Management';
  const degreeName = phSettings.degree_name || phSettings.degreeName || 'Bachelor of Science in Business Administration';
  const logoUrl = '../../../media/LOGO.jpg';
  
  if(!table) return;
  
  const tableClone = table.cloneNode(true);
  
  // Build cleaner print HTML (similar to view grade container)
  const printHTML = `
    <div style="width:180mm;margin:0 auto;padding:12mm;font-family:'Poppins',Arial,sans-serif;font-size:10px;color:#1a1a1a;background:#fff;">
      <!-- Header (Gold Gradient Style) -->
      <div style="background:linear-gradient(145deg,#B8860B,#8B6914);padding:10mm 14mm;color:#fff;border-radius:10px 10px 0 0;margin:-12mm -12mm 10mm -12mm;">
        <div style="display:flex;align-items:center;gap:10mm;">
          <img src="${logoUrl}" style="width:40mm;height:40mm;object-fit:contain;border:2px solid #fff;border-radius:8px;background:#fff;">
          <div style="flex:1;">
            <div style="font-size:14px;font-weight:800;">${title}</div>
            <div style="font-size:11px;opacity:0.9;margin-top:2px;">${studentName}</div>
            <div style="font-size:9px;opacity:0.85;margin-top:4px;">
              <div style="font-weight:700;text-transform:uppercase;">${schoolName}</div>
              <div>${schoolAddress}</div>
              <div>${instituteName}</div>
              <div>${degreeName}</div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Student Info Strip -->
      <div style="display:flex;flex-wrap:wrap;gap:6mm;padding:6mm 8mm;background:linear-gradient(135deg,#fff,#fafaf8);border:1px solid rgba(184,134,11,.2);border-radius:8px;margin-bottom:8mm;font-size:9px;">
        <div><span style="font-weight:700;color:#8B6914;">Student:</span> <span style="color:#333;">${studentName}</span></div>
        <div><span style="font-weight:700;color:#8B6914;">Student ID:</span> <span style="color:#333;">${document.getElementById('gmInfoStudentID').textContent}</span></div>
        <div><span style="font-weight:700;color:#8B6914;">Year:</span> <span style="color:#333;">${document.getElementById('gmInfoYearLevel').textContent}</span></div>
        <div><span style="font-weight:700;color:#8B6914;">Semester:</span> <span style="color:#333;">${document.getElementById('gmInfoSemester').textContent}</span></div>
      </div>
      
      <!-- Period Row -->
      <div style="text-align:center;font-size:10px;font-weight:700;color:#8B6914;padding:5mm;background:linear-gradient(135deg,#fef9ed,#fdf6e8);border-radius:6px;margin-bottom:6mm;">
        ${document.getElementById('gmInfoPeriod').textContent || title}
      </div>
      
      <!-- Table -->
      <div style="border:1px solid rgba(184,134,11,.2);border-radius:8px;overflow:hidden;margin-bottom:8mm;">
        ${tableClone.outerHTML}
      </div>
      
      <!-- Summary -->
      <div style="display:flex;justify-content:space-between;padding:6mm 8mm;background:linear-gradient(145deg,#B8860B,#8B6914);border-radius:8px;font-size:10px;font-weight:700;color:#fff;">
        <div>${document.getElementById('gmSummaryLeft').textContent}</div>
        <div>${document.getElementById('gmSummaryGWA').textContent}</div>
      </div>
      
      <!-- Signatures -->
      <div style="display:flex;justify-content:space-between;margin-top:20mm;padding-top:8mm;border-top:1px solid #ccc;">
        <div style="text-align:center;width:45%;">
          <div style="font-size:10px;font-weight:700;color:#333;margin-bottom:3mm;">${advisorName}</div>
          <div style="border-bottom:1px solid #333;height:15mm;margin-bottom:3mm;"></div>
          <div style="font-size:9px;font-weight:700;color:#8B6914;margin-bottom:2mm;">Adviser's Signature</div>
          <div style="font-size:8px;color:#666;">Date: ___________________</div>
        </div>
        <div style="text-align:center;width:45%;">
          <div style="font-size:10px;font-weight:700;color:#333;margin-bottom:3mm;">${programHeadName}</div>
          <div style="border-bottom:1px solid #333;height:15mm;margin-bottom:3mm;"></div>
          <div style="font-size:9px;font-weight:700;color:#8B6914;margin-bottom:2mm;">Program Head's Signature</div>
          <div style="font-size:8px;color:#666;">Date: ___________________</div>
        </div>
      </div>
    </div>
  `;
  
  // Remove old print container
  const existingPrint = document.getElementById('printContainer');
  if(existingPrint) existingPrint.remove();
  
  // Create print container
  const printContainer = document.createElement('div');
  printContainer.id = 'printContainer';
  printContainer.innerHTML = printHTML;
  printContainer.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:#fff;z-index:99999;overflow:auto;';
  
  // Add table styles
  const tableStyle = document.createElement('style');
  tableStyle.textContent = `
    #printContainer table { width:100%;border-collapse:collapse;font-size:9px; }
    #printContainer th { background:#B8860B;color:#fff;padding:5px 6px;text-align:left;font-weight:700;border-bottom:2px solid #8B6914; }
    #printContainer th:nth-child(3), #printContainer th:nth-child(4) { text-align:center; }
    #printContainer td { padding:4px 6px;border-bottom:1px solid #ddd; }
    #printContainer td:nth-child(3), #printContainer td:nth-child(4) { text-align:center; }
    #printContainer .code { font-weight:700;color:#1a1a1a; }
    #printContainer .subject { color:#4b4b4b; }
    #printContainer .units { font-weight:600;text-align:center; }
    #printContainer .grade { font-weight:800;text-align:center; }
    #printContainer .grade.pass { color:#16a34a; }
    #printContainer .grade.fail { color:#dc2626; }
    #printContainer .grade.cond { color:#d97706; }
  `;
  printContainer.appendChild(tableStyle);
  document.body.appendChild(printContainer);
  
  // Print directly - shows content in print dialog
  window.print();
}

/* ═══════════════════════════════════════════════════════════
   ENROLLMENT LIST HELPERS
═══════════════════════════════════════════════════════════ */
 function rmToggleSubject(id) {
   const row = document.getElementById('rmrow-'+id);
   const chk = document.getElementById('rmchk-'+id);
   if(!row || !chk) return;
   
   if(chk.checked) {
     window._rmSelectedIds.add(id);
     row.style.background = '#fff';
   } else {
     window._rmSelectedIds.delete(id);
     row.style.background = '#f5f5f5';
   }
   rmUpdateTotals();
 }
 
 function rmToggleExtraSubject(id) {
   const row = document.getElementById('rmrow-extra-'+id);
   const chk = document.getElementById('rmchk-extra-'+id);
   if(!row || !chk) return;
   
   if(chk.checked) {
     window._rmExtraSelectedIds.add(id);
     row.style.background = '#e6f0ff';
   } else {
     window._rmExtraSelectedIds.delete(id);
     row.style.background = '#fff';
   }
   rmUpdateTotals();
 }
 
 function rmSelectAll(checked) {
   // Select/deselect regular subjects
   window._rmAvailableSubs.forEach(s => {
     const chk = document.getElementById('rmchk-'+s.id);
     const row = document.getElementById('rmrow-'+s.id);
     if(chk && row) {
       chk.checked = checked;
       if(checked) {
         window._rmSelectedIds.add(s.id);
         row.style.background = '#fff';
       } else {
         window._rmSelectedIds.delete(s.id);
         row.style.background = '#f5f5f5';
       }
     }
   });
   
   // Also select/deselect extra cross-year subjects
   if(window._rmExtraSubs) {
     window._rmExtraSubs.forEach(s => {
       const chk = document.getElementById('rmchk-extra-'+s.id);
       const row = document.getElementById('rmrow-extra-'+s.id);
       if(chk && row) {
         chk.checked = checked;
         if(checked) {
           window._rmExtraSelectedIds.add(s.id);
           row.style.background = '#e6f0ff';
         } else {
           window._rmExtraSelectedIds.delete(s.id);
           row.style.background = '#fff';
         }
       }
     });
   }
   
   rmUpdateTotals();
 }
 function rmSelectAll(checked) {
   // Select/deselect regular subjects
   (window._rmAvailableSubs||[]).forEach(s => {
     const chk = document.getElementById('rmchk-'+s.id);
     if(chk && !chk.disabled) {
       chk.checked = checked;
       rmToggleSubject(s.id);
     }
   });
   
   // Also select/deselect extra cross-year subjects
   if(window._rmExtraSubs) {
     window._rmExtraSubs.forEach(s => {
       const chk = document.getElementById('rmchk-extra-'+s.id);
       if(chk) { chk.checked = checked; rmToggleExtraSubject(s.id); }
     });
   }
 }
 
 function rmUpdateTotals() {
   let count = window._rmSelectedIds.size;
   let units = 0;
   (window._rmAvailableSubs||[]).forEach(s => {
     if(window._rmSelectedIds.has(s.id)) {
       units += parseFloat(s.units)||0;
     }
   });
   
   // Add extra subjects
   if(window._rmExtraSelectedIds && window._rmExtraSubs) {
     count += window._rmExtraSelectedIds.size;
     window._rmExtraSubs.forEach(s => {
       if(window._rmExtraSelectedIds.has(s.id)) {
         units += parseFloat(s.units)||0;
       }
     });
   }
   
   document.getElementById('rmSelectedCount').textContent = count;
   document.getElementById('rmTotalUnits').textContent = units;
 }
 
 function rmConfirmEnrollmentList(toYear, toSem) {
   const selected = (window._rmAvailableSubs||[]).filter(s => window._rmSelectedIds.has(s.id));
   const selectedExtra = (window._rmExtraSubs||[]).filter(s => window._rmExtraSelectedIds.has(s.id));
   const allSelected = [...selected, ...selectedExtra];
   const units    = allSelected.reduce((a,s)=>a+(parseFloat(s.units)||0),0);
   
   // Annotate every confirmed subject with the semester it is being loaded INTO.
   // This is needed so openGradesModal can include cross-year additional subjects
   // (whose native year_level differs from toYear) in the correct grades view.
   allSelected.forEach(s => {
     const subObj = loadedSubjects.find(ls => ls.id === s.id || ls.id === parseInt(s.id));
     if (subObj) {
       subObj.load_target_year = toYear;
       subObj.load_target_sem  = toSem;
     }
     s.load_target_year = toYear;
     s.load_target_sem  = toSem;
   });

// Store for promoteStudent to use when unlocking subjects after promotion
    confirmedEnrollmentSubjectIds = allSelected.map(s => s.id);
    
     // Store for confirmed subjects
    window._confirmedSubjects = allSelected;
    window._confirmedYear = toYear;
    window._confirmedSem = toSem;
    
    if(currentStudent) {
      const fd = new FormData();
      fd.append('action','save_enrollment_list'); fd.append('student_id',currentStudent.id);
      fd.append('academic_year',currentAY); fd.append('subject_ids',JSON.stringify(allSelected.map(s=>s.id)));
      fd.append('to_year',toYear||''); fd.append('to_sem',toSem||'');
      fetch(EVAL_PROC,{method:'POST',body:fd}).catch(()=>{});
    }
    toast(`Subject load confirmed — ${allSelected.length} subjects (${units} units) will be loaded for ${toYear} ${toSem}`,'success',3800);
    
// Update confirm button
     const btn = document.getElementById('rmConfirmBtn');
     if(btn) { 
       btn.innerHTML='<i class="fas fa-check-circle"></i> Load Confirmed'; 
       btn.style.background='linear-gradient(135deg,var(--green),#15803d)'; 
       btn.disabled=true; 
}
    

    
    // ★ Enable the "Finalize Subject Load" button now that a load is confirmed
    const finalizeLoadBtn = document.getElementById('rmFinalizeLoadBtn');
    if(finalizeLoadBtn) {
      finalizeLoadBtn.disabled = false;
      finalizeLoadBtn.style.opacity = '1';
      finalizeLoadBtn.style.cursor = 'pointer';
      finalizeLoadBtn.style.background = 'linear-gradient(135deg,#16a34a,#15803d)';
      finalizeLoadBtn.innerHTML = '<i class="fas fa-check-square"></i> Finalize Subject Load';
      // Update gate notice text
      const gateDiv = document.getElementById('rmFinalizeGate');
      if(gateDiv) {
        const subText = gateDiv.querySelector('div:nth-child(2)');
        if(subText) subText.innerHTML = '<strong style="color:#166534;">' + allSelected.length + ' subjects confirmed.</strong> Click <strong>Finalize Subject Load</strong> to lock the list and enable Proceed / Stay.';
        const hint = gateDiv.querySelector('div:last-child');
if(hint) hint.style.display = 'none';
      }
    }
  }
  

  function printEnrollmentList(year, sem, subjects) {
    // Create print window
    const printWindow = window.open('', '_blank');
    
    const totalUnits = subjects.reduce((a,s)=>a+(parseFloat(s.units)||0),0);
    const studentName = currentStudent ? 
      (currentStudent.first_name + ' ' + (currentStudent.middle_name ? currentStudent.middle_name + ' ' : '') + 
       currentStudent.last_name + (currentStudent.suffix ? ' ' + currentStudent.suffix : '')).trim() : '—';
    const studentId = currentStudent ? (currentStudent.student_number || currentStudent.student_id || '—') : '—';
    
    printWindow.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>Enrollment List — ${year} ${sem}</title>
        <style>
          @page { 
            size: A4 portrait; 
            margin: 15mm 12mm 12mm 12mm; 
          }
          * {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.3;
            box-sizing: border-box;
          }
          body {
            margin: 0;
            padding: 0;
            background: white;
            color: #000;
          }
          .container {
            padding: 0;
          }
          .header {
            text-align: center;
            border-bottom: 2pt solid #8B6914;
            padding-bottom: 3mm;
            margin-bottom: 3mm;
          }
          .school-name {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8pt;
            margin-bottom: 1mm;
            color: #000;
          }
          .school-address {
            font-size: 11pt;
            font-style: italic;
            margin-bottom: 1mm;
            color: #333;
          }
          .institute {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 2mm;
            color: #000;
          }
          .title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin: 4mm 0 3mm 0;
            padding: 2.5mm;
            background: #8B6914;
            color: white;
            letter-spacing: 1pt;
          }
          .student-info {
            border: 1pt solid #333;
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 3mm;
            font-size: 11pt;
          }
          .student-info td {
            border: 1pt solid #333;
            padding: 2mm 2.5mm;
            vertical-align: top;
          }
          .student-info .label {
            font-weight: bold;
            width: 22mm;
            background: #f0ece0;
          }
          table.subjects {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
            margin-bottom: 3mm;
            page-break-inside: avoid;
          }
          table.subjects th {
            background: #8B6914;
            color: white;
            padding: 2mm 1.5mm;
            font-weight: bold;
            text-align: left;
            border: 1pt solid #8B6914;
            font-size: 11pt;
          }
          table.subjects td {
            padding: 2mm 1.5mm;
            border: 1pt solid #333;
            vertical-align: middle;
            font-size: 11pt;
          }
          .units {
            text-align: center;
            font-weight: 600;
            width: 15mm;
          }
          .code {
            width: 25mm;
            font-weight: 700;
          }
          .total-row td {
            background: #f0ece0;
            font-weight: bold;
            color: #000;
            border-top: 1.5pt solid #8B6914;
          }
          .total-label {
            text-align: right;
            padding-right: 3mm;
            font-size: 11pt;
          }
          .footer {
            margin-top: 8mm;
            padding-top: 4mm;
            border-top: 1pt solid #8B6914;
            width: 100%;
          }
          .footer-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
          }
          .footer-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 2mm;
          }
          .sig-line {
            border-bottom: 1pt solid #000;
            height: 6mm;
            margin: 2mm 0;
          }
          .sig-label {
            font-weight: bold;
            color: #8B6914;
            font-size: 10pt;
          }
          .sig-name {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: -4mm;
          }
          .date-line {
            font-size: 10pt;
            color: #333;
          }
        </style>
      </head>
      <body>
        <div class="container">
          <div class="header">
            <div class="school-name">Northern Bukidnon State College</div>
            <div class="school-address">Manolo Fortich, Bukidnon</div>
            <div class="institute">Institute for Business Management</div>
          </div>
          
          <div class="title">
            ENROLLMENT LIST — ${year} · ${sem} · A.Y. ${currentAY}
          </div>
          
          <table class="student-info">
            <tr>
              <td class="label">Student:</td>
              <td>${studentName}</td>
              <td class="label">Student ID:</td>
              <td style="width:35mm;">${studentId}</td>
            </tr>
            <tr>
              <td class="label">Year Level:</td>
              <td style="width:35mm;">${year}</td>
              <td class="label">Semester:</td>
              <td>${sem}</td>
            </tr>
          </table>
          
          <table class="subjects">
            <thead>
              <tr>
                <th class="code">Course Code</th>
                <th>Subject Title</th>
                <th class="units">Units</th>
              </tr>
            </thead>
            <tbody>
              ${subjects.map(s => `
              <tr>
                <td class="code">${s.subject_code}</td>
                <td>${s.subject_name}</td>
                <td class="units">${parseFloat(s.units)||0}</td>
              </tr>`).join('')}
              <tr class="total-row">
                <td colspan="2" class="total-label">TOTAL UNITS</td>
                <td class="units">${totalUnits}</td>
              </tr>
            </tbody>
          </table>
          
          <div class="footer">
            <table class="footer-table">
              <tr>
                <td>
                  <div class="sig-name">${window.advisorName || 'Adviser'}</div>
                  <div class="sig-line"></div>
                  <div class="sig-label">Adviser's Signature</div>
                  <div class="date-line">Date: ___________________</div>
                </td>
                <td>
                  <div class="sig-name">${window.programHeadName || 'Program Head'}</div>
                  <div class="sig-line"></div>
                  <div class="sig-label">Program Head's Signature</div>
                  <div class="date-line">Date: ___________________</div>
                </td>
              </tr>
            </table>
          </div>
        </div>
      </body>
      </html>
    `);
    
    printWindow.document.close();
    
    setTimeout(() => {
      printWindow.print();
      printWindow.addEventListener('afterprint', () => {
        printWindow.close();
      });
    }, 300);
  }

/* ═══════════════════════════════════════════════════════════
   PROMOTE STUDENT
═══════════════════════════════════════════════════════════ */
function promoteStudent(fromYear, fromSem, toYear, toSem) {
  const fd = new FormData();
  fd.append('action','promote_student'); fd.append('student_id',currentStudent.id);
  fd.append('from_year',fromYear); fd.append('from_sem',fromSem);
  fd.append('to_year',toYear); fd.append('to_sem',toSem);
  fd.append('academic_year',currentAY);
  // Pass the confirmed enrollment subject IDs so the server can persist them
  // and so we can mark them as in-load locally for immediate grade editing
  if (confirmedEnrollmentSubjectIds.length) {
    fd.append('confirmed_subject_ids', JSON.stringify(confirmedEnrollmentSubjectIds));
  }

  const oldYearLevel = currentStudent.year_level || '';
  const newYearLevel = `${toYear} - ${toSem}`;
  const newSemLabel = toSem;

  fetch(EVAL_PROC,{method:'POST',body:fd})
    .then(res => res.json())
    .then(data => {
      if(data.success) {
        if (data.graduated) {
          window.evaluationLocked = true;
          toast(data.message || 'Student graduated. Official prospectus PDF saved under C:\\graduate\\…', 'success', 5000);
          if (data.pdf_url) {
            try { window.open(data.pdf_url, '_blank'); } catch (e) {}
          }
          if (currentStudent) {
            currentStudent.status = 'graduated';
            currentStudent.year_level = data.year_level || '4th Year - 2nd Semester';
          }
          setTimeout(() => {
            closeResultModal();
            if (currentStudent) {
              const studentCopy = { ...currentStudent };
              closeEval();
              setTimeout(() => openEval(studentCopy), 200);
            }
            loadMentees();
          }, 1200);
          return;
        }
        if(currentStudent) {
          currentStudent.year_level = newYearLevel;
          currentStudent.semester = newSemLabel;
        }
        document.getElementById('evalSub').textContent = `${currentStudent.major_name||'No major'} · ${toYear} — ${toSem} · A.Y. ${currentAY}`;

        const siStrip = document.querySelector('.student-info-strip');
        if(siStrip) {
          const yearItem = siStrip.querySelector('.si-item:nth-child(3) .si-value');
          const semItem = siStrip.querySelector('.si-item:nth-child(4) .si-value');
          if(yearItem) yearItem.textContent = yearLevelOnly(newYearLevel) || toYear;
          if(semItem) semItem.textContent = newSemLabel;
        }

        // ★ LOCK the evaluated (previous) semester
        const fromKey = `${fromYear}|${fromSem}`;
        finalizedMap[fromKey] = true;

        // Persist finalization of the previous semester so it stays locked
        const fdFinalize = new FormData();
        fdFinalize.append('action','finalize_session');
        fdFinalize.append('student_id',currentStudent.id);
        fdFinalize.append('major_id',currentStudent.major_id||0);
        fdFinalize.append('academic_year',currentAY);
        fdFinalize.append('year_level',fromYear);
        fdFinalize.append('semester',fromSem);
        fetch(EVAL_PROC,{method:'POST',body:fdFinalize}).catch(()=>{});

        // Visually lock all rows of the finalized (previous) semester
        document.querySelectorAll(`.pro-year-block[data-year="${fromYear}"]`).forEach(block => {
          block.querySelectorAll('.pro-sem-col').forEach(col => {
            if(col.dataset.sem === fromSem) {
              if(!col.querySelector('.sem-finalized-badge-inline')) {
                const label = col.querySelector('.pro-sem-label');
                if(label) {
                  const badge = document.createElement('span');
                  badge.className = 'sem-finalized-badge-inline';
                  badge.innerHTML = '<i class="fas fa-check-circle"></i> Finalized';
                  label.appendChild(badge);
                }
              }
              col.querySelectorAll('tr').forEach(row => {
                row.classList.add('row-finalized');
                const inp = row.querySelector('.grade-inp');
                const sbtn = row.querySelector('.save-btn');
                if(inp) { inp.disabled = true; inp.title = 'Semester finalized — locked'; }
                if(sbtn) sbtn.disabled = true;
              });
            }
          });
        });

        // Update focus to the new semester
        focusYear = toYear;
        focusSem  = toSem;

        toast(`Promoted to ${toSem} — ${toYear}! Loading confirmed subject list…`,'success',4000);

        setTimeout(() => {
          closeResultModal();
          // Reopen evaluation with fresh data.
          // confirmedEnrollmentSubjectIds is intentionally kept alive here —
          // renderProspectus will consume it to set is_in_load only for the
          // confirmed subjects, then clear it. This preserves the exact
          // enrollment selection the instructor made in the result modal
          // across the close/reopen cycle, preventing auto-load of the full
          // semester dataset.
          if(currentStudent) {
            const studentCopy = {...currentStudent};
            closeEval();
            setTimeout(() => openEval(studentCopy), 200);
          }
          loadMentees();
        }, 1500);
      } else {
        toast(data.message || 'Failed to promote student','error');
      }
    })
    .catch(err => {
      console.error(err);
      toast('Error promoting student','error');
    });
}

/* ═══════════════════════════════════════════════════════════
   RENDER PROSPECTUS
═══════════════════════════════════════════════════════════ */
 function renderProspectus(data) {
   const s = data.student; const subjects = data.subjects||[];
   const gwaData = data.gwa_data||{}; const ay = data.academic_year||currentAY;
   const prereqSetsMap = data.prereq_map||{};
   const finalizedSessions = data.finalized_sessions||{};
   window.lastCurriculumStats = data.curriculum || null;
   window.evaluationLocked = !!data.evaluation_locked;
   window.graduationRecord = data.graduation_record || null;
   window.advisorName = data.advisor_name || '';
   window.programHeadName = data.program_head_name || '';
   const advisorName = window.advisorName;
   const programHeadName = window.programHeadName;

    loadedSubjects = subjects;
    subjects.forEach(sub => { if(sub.grade_rounded != null) gradeMap[sub.id] = parseFloat(sub.grade_rounded); });

    // ── Determine the student's CURRENT evaluation period (year + sem) ─────
    // Subjects in this period should ALWAYS be in-load so instructors can
    // enter grades during the ongoing evaluation. This fixes the case where
    // transfer / non-IBM workflows previously left the current semester's
    // subjects flagged as "Not in student load".
    const _standingStr = s.year_level || '1st Year - 1st Semester';
    const _st          = parseStudentStanding(_standingStr);
    const _curYear     = YEAR_LABELS[_st.yr - 1] || '1st Year';
    const _curSemTok   = _st.sem === 1 ? '1st' : '2nd';
    const _isCurrentPeriod = (sub) => {
      const sy = (sub.year_level || '').trim();
      const ss = (sub.semester || '').toLowerCase();
      return sy === _curYear && ss.includes(_curSemTok);
    };

    // ── Apply is_in_load based on the confirmed enrollment list (if present) ──
    // When the instructor just clicked "Proceed to…", promoteStudent stores
    // the exact IDs they confirmed. We use those as the EXCLUSIVE gate here
    // so only the selected subjects are editable — not the full semester dump.
    // Once consumed we clear the list so normal logic applies on future opens.
    if (confirmedEnrollmentSubjectIds.length > 0) {
      const confirmedSet = new Set(confirmedEnrollmentSubjectIds.map(id => parseInt(id)));
      subjects.forEach(sub => {
        // Always keep credited subjects as-is; for everything else, only mark
        // in-load if the instructor explicitly selected it in the result modal.
        sub.is_in_load = !!sub.is_credited || confirmedSet.has(parseInt(sub.id));
      });
      // Consumed — clear so subsequent opens use normal logic
      confirmedEnrollmentSubjectIds = [];
    } else {
      // Normal open (no pending promotion) — apply per-student-type rules
      // Mark subject load status based on student type
      if (currentStudentType === 'transfer' && typeof TransferEvaluation !== 'undefined') {
        const currentLoad = TransferEvaluation.getCurrentLoad();
        subjects.forEach(sub => {
          const sid = String(sub.id);
          const isCredited = !!sub.is_credited;
          // Include: credited, explicitly in current load, current standing semester,
          // or server-flagged in-load (from a previously saved enrollment list).
          const serverInLoad = sub.is_in_load === true || sub.is_in_load === 1 || sub.is_in_load === '1';
          sub.is_in_load = isCredited || !!currentLoad[sid] || _isCurrentPeriod(sub) || serverInLoad;
        });
      } else if (currentStudentType === 'non_ibm') {
        // Non-IBM: respect server in-load flags plus always include current period.
        subjects.forEach(sub => {
          const serverInLoad = sub.is_in_load === true || sub.is_in_load === 1 || sub.is_in_load === '1';
          if (_isCurrentPeriod(sub) || serverInLoad) sub.is_in_load = true;
        });
      } else {
        // Regular students: respect the server-persisted is_in_load flag.
        // Subjects in the student's CURRENT standing period are always editable
        // (they are being evaluated right now). Past semesters that have a saved
        // enrollment list honour the server flag — this preserves the "locked"
        // state of subjects that were never officially loaded, even after a
        // page refresh or re-open.
        subjects.forEach(sub => {
          const serverInLoad = sub.is_in_load === true || sub.is_in_load === 1 || sub.is_in_load === '1';
          // Always allow the current standing period; otherwise use server flag.
          sub.is_in_load = _isCurrentPeriod(sub) || serverInLoad;
        });
      }
    }

    // Populate finalizedMap from backend data
  finalizedMap = {};
  Object.keys(finalizedSessions).forEach(key => {
    const [year, sem] = key.split('|');
    const fkey = `${year}|${sem}`;
    finalizedMap[fkey] = true;
  });

   const bridging = subjects.filter(s2 => s2.year_level === 'Bridging');
   const bridgingUnits = (bridging||[]).reduce((a,s2)=>a+(parseFloat(s2.units)||0),0);
   let yearBlocks = ''; let grandTotal = bridgingUnits;

   const prereqUnlockMap = buildPrereqUnlockMap(subjects, gradeMap, prereqSetsData, s.major_id);
   window.currentPrereqSetsMap = prereqSetsMap;

  const full = `${s.first_name}${s.middle_name && s.middle_name[0]?' '+s.middle_name[0]+'.':''} ${s.last_name}${s.suffix?' '+s.suffix:''}`.trim();
  const studentStanding = s.year_level||'1st Year - 1st Semester';
  const semMatch = studentStanding.match(/(\d+)(st|nd|rd|th)\s*Year.*?(\d+)(st|nd|rd|th)\s*Sem/i);
  const currentSem = semMatch ? (semMatch[3]=='1'?'1st':'2nd')+' Semester' : '1st Semester';

  const hdrHtml = `<div class="pro-hdr">
    <img src="../../../media/LOGO.jpg" class="pro-logo" alt="School Logo">
    <div class="pro-title-block">
      <div class="pro-school">${esc(phSettings.school_name || phSettings.schoolName || '')}</div>
      <div class="pro-address">${esc(phSettings.school_address || phSettings.schoolAddress || '')}</div>
      <div style="border-top:1px solid #d4cfc5;margin:4px auto;width:80%;"></div>
      <div class="pro-institute">${esc(phSettings.institute_name || phSettings.instituteName || '')}</div>
      <div class="pro-degree">${esc(phSettings.degree_name || phSettings.degreeName || '')}</div>
      <div class="pro-major-line">Major in <strong>${esc(s.major_name||'—')}</strong></div>
      <div class="pro-label">&#9733; Student Evaluation Prospectus &#9733;</div>
    </div>
    <img src="../../../media/nbsc_logo.png" class="pro-logo" alt="Institute Logo" onerror="this.style.display='none'">
  </div>
  <div class="student-info-strip-print">
    <div class="sip-item"><span class="sip-label">Student:</span><span class="sip-value">${esc(full)}</span></div>
    <div class="sip-item"><span class="sip-label">Student ID:</span><span class="sip-value">${esc(s.student_id||s.student_number||'—')}</span></div>
    <div class="sip-item"><span class="sip-label">Year Level:</span><span class="sip-value">${esc(yearLevelOnly(s.year_level)||'—')}</span></div>
    <div class="sip-item"><span class="sip-label">Semester:</span><span class="sip-value">${esc(currentSem)}</span></div>
  </div>`;

  const gradLockBanner = window.evaluationLocked ? `
  <div class="pro-grad-lock-banner" role="status">
    <i class="fas fa-user-graduate" aria-hidden="true"></i>
    <div><strong>Graduated</strong> — Evaluation is finalized. Grades and subject loads are read-only.</div>
  </div>` : '';

   const byYear = {};
   subjects.forEach(sub => {
     const y = sub.year_level||'1st Year';
     if(!byYear[y]) byYear[y] = [];
     byYear[y].push(sub);
   });

   YEAR_ORDER.forEach(year => {
    if(year === 'Bridging') return;
    const all = byYear[year]||[];
    if(!all.length) return;
    const sem1 = all.filter(s2 => !s2.semester||s2.semester.includes('1st'));
    const sem2 = all.filter(s2 => s2.semester&&s2.semester.includes('2nd'));
    const t = all.reduce((a,s2)=>a+(parseFloat(s2.units)||0),0);
    grandTotal += t;
    
    const isSem1Finalized = finalizedMap[`${year}|1st Semester`];
    const isSem2Finalized = finalizedMap[`${year}|2nd Semester`];
    
    yearBlocks += `<div class="pro-year-block" data-year="${year}">
      <div class="pro-year-hdr">
        <span><i class="fas fa-calendar-alt" style="margin-right:6px;font-size:11px;"></i>${year}</span>
        <span class="pro-year-total">${fmt(t)} units</span>
      </div>
      <div class="pro-sem-row">
        <div class="pro-sem-col" data-sem="1st Semester">
          <div class="pro-sem-label">${year.toUpperCase()} — First Semester ${isSem1Finalized ? '<span class="sem-finalized-badge-inline"><i class="fas fa-check-circle"></i> Finalized</span>' : ''}</div>
          ${buildGradeTable(sem1,s,ay,prereqUnlockMap, isSem1Finalized)}
        </div>
        <div class="pro-sem-col" data-sem="2nd Semester">
          <div class="pro-sem-label">${year.toUpperCase()} — Second Semester ${isSem2Finalized ? '<span class="sem-finalized-badge-inline"><i class="fas fa-check-circle"></i> Finalized</span>' : ''}</div>
          ${buildGradeTable(sem2,s,ay,prereqUnlockMap, isSem2Finalized)}
        </div>
      </div>
    </div>`;
  });

const sigHtml = `<div class="pro-sig-block">
    <div class="pro-sig-col">
      <div class="pro-sig-name">${esc(full)}</div>
      <div class="pro-sig-line"></div>
      <div class="pro-sig-lbl">Student's Signature</div>
      <div class="pro-sig-date">Date: ___________________</div>
    </div>
    <div class="pro-sig-col">
      <div class="pro-sig-name">${esc(advisorName)}</div>
      <div class="pro-sig-line"></div>
      <div class="pro-sig-lbl">Adviser's Signature</div>
      <div class="pro-sig-date">Date: ___________________</div>
    </div>
    <div class="pro-sig-col">
      <div class="pro-sig-name">${esc(programHeadName)}</div>
      <div class="pro-sig-line"></div>
      <div class="pro-sig-lbl">Program Head's Signature</div>
      <div class="pro-sig-date">Date: ___________________</div>
    </div>
  </div>
  <div class="pro-legend">
    <span style="display:inline-block;width:10px;height:10px;background:var(--amber-l);border-left:3px solid var(--amber);border-radius:2px;vertical-align:middle;"></span>
    = Locked (prerequisite not yet passed)
  </div>`;

  const studentInfoHtml = `<div class="student-info-strip">
    <div class="si-item"><span class="si-label">Student</span><span class="si-value">${esc(full)}</span></div>
    <div class="si-item"><span class="si-label">Student ID</span><span class="si-value">${esc(s.student_id||s.student_number||'—')}</span></div>
    <div class="si-item"><span class="si-label">Year Level</span><span class="si-value">${esc(yearLevelOnly(s.year_level)||'1st Year')}</span></div>
    <div class="si-item"><span class="si-label">Semester</span><span class="si-value">${esc(currentSem)}</span></div>
  </div>`;

  const proHtml = `<div class="pro-wrap" id="liveProspectus">
    ${hdrHtml}
    ${gradLockBanner}
    <div class="pro-body">
      ${!subjects.length?`<div class="empty-state"><i class="fas fa-book"></i><h3>No subjects configured</h3><p>Set up the prospectus in Department Management first.</p></div>`:''}
      ${studentInfoHtml}
      ${yearBlocks}
      <div class="pro-bridging-block" style="margin-top:20px;">
        <div class="pro-year-block" data-year="Bridging">
          <div class="pro-year-hdr" style="background:linear-gradient(135deg,var(--gold-d),var(--gold-l));">
            <span><i class="fas fa-exchange-alt" style="margin-right:6px;font-size:11px;"></i>Bridging Subjects</span>
            <span class="pro-year-total">${fmt((bridging||[]).reduce((a,s2)=>a+(parseFloat(s2.units)||0),0))} units</span>
          </div>
          <div class="pro-sem-row">
            <div class="pro-sem-col" data-sem="1st Semester" style="grid-column:1/-1;">
              <div class="pro-sem-label">BRIDGING — Subjects</div>
              <table class="pro-table">
                <thead><tr>
                  <th class="pro-th" style="width:54px;text-align:center;">Final Grade</th>
                  <th class="pro-th pro-th-status" style="width:36px;text-align:center;">Status</th>
                  <th class="pro-th" style="width:62px;">Course No.</th>
                  <th class="pro-th">Description</th>
                  <th class="pro-th" style="width:32px;text-align:center;">Units</th>
                  <th class="pro-th" style="width:46px;">Bridging For</th>
                </tr></thead>
<tbody>
                    ${(bridging||[]).map(sub => {
                      const raw    = gradeMap[sub.id] != null ? gradeMap[sub.id] : null;
                      const status = raw != null ? gradeStatus(roundGrade(raw)) : (sub.grade_status||'not_taken');
                      // ── Bridging lock logic (mirrors buildGradeTable) ──────────────
                      // A bridging subject is editable only when it has been officially
                      // confirmed in the enrollment list (is_in_load = true).  Without
                      // this check the row was always rendered as editable, so after a
                      // page refresh subjects that were never loaded could be graded.
                      const bIsInLoad  = !!sub.is_in_load;
                      const bIsCredited = !!sub.is_credited;
                      const bPrereqPi   = prereqUnlockMap ? (prereqUnlockMap[sub.id]||{unlocked:true}) : {unlocked:true};
                      const bPrereqLocked = !bPrereqPi.unlocked;
                      const bManuallyUnlocked = manuallyUnlockedSubjects.has(parseInt(sub.id)) || manuallyUnlockedSubjects.has(sub.id);
                      const bIsGraduated = s && s.status === 'graduated';
                      let bShouldDisable = bIsGraduated || (window.evaluationLocked === true) || bPrereqLocked || bIsCredited || !bIsInLoad;
                      if (bManuallyUnlocked && !bIsCredited) bShouldDisable = false;
                      const bRowClass = bShouldDisable ? (bIsCredited ? 'row-credited' : 'row-locked') : '';
                      const bLockDesc = !bIsInLoad ? 'Not in student load' : (bPrereqLocked ? 'Prerequisite required' : '');
                      const bBadgeIcon = bPrereqLocked ? 'fa-lock' : 'fa-info-circle';
                      const bBadgeStyle = !bIsInLoad ? 'background:rgba(128,128,128,.15);border-color:#999;color:#666;' : '';
                       return `<tr id="row-${sub.id}" class="${bRowClass}" data-year-level="${esc(sub.year_level)}" data-semester="${esc(sub.semester)}" data-in-load="${bIsInLoad ? '1' : '0'}" data-units="${parseFloat(sub.units)||0}">
                       <td>
<div class="grade-cell-wrap">
                            <div class="grade-row">
                              <input type="number" class="grade-inp ${raw!=null?gClass(status):''}" id="g-${sub.id}"
                                value="${raw!=null?parseFloat(raw).toFixed(2):''}"
                                min="1" max="5" step="0.01" placeholder="—"
                                oninput="onGradeInput(${sub.id}, event)"
                                onkeydown="onGradeKeydown(${sub.id}, event); if(event.key==='Enter'){event.preventDefault();saveGrade(${sub.id},${s.id},${s.major_id},'1st Semester','Bridging','${esc(ay)}');}"
                                onchange="onGradeChange(${sub.id},${s.id},${s.major_id},'1st Semester','Bridging','${esc(ay)}')"
                                ${bShouldDisable ? `disabled title="${bLockDesc||'Locked'}"` : 'title="1.00 to 5.00 · Enter to save"'}>
                              <span class="grade-print" style="display:none;">${raw!=null?parseFloat(raw).toFixed(2):'—'}</span>
                              <button class="save-btn" id="sbtn-${sub.id}" onclick="saveGrade(${sub.id},${s.id},${s.major_id},'1st Semester','Bridging','${esc(ay)}')" title="Save grade" ${bShouldDisable ? 'disabled' : ''}><i class="fas fa-save"></i></button>
                              ${(bShouldDisable && !bIsCredited) ? `<button class="edit-btn" id="ebtn-${sub.id}" onclick="requestEditGrade(${sub.id},${s.id},${s.major_id},'1st Semester','Bridging','${esc(ay)}')" title="Edit this grade (requires password)"><i class="fas fa-edit"></i></button>` : ''}
                            </div>
                            <div class="grade-hint" id="gl-${sub.id}">${sub.grade_label||''}</div>
                            ${bShouldDisable ? `<span class="lock-badge" style="${bBadgeStyle}"><i class="fas ${bBadgeIcon}" style="font-size:7px;"></i>${bLockDesc||'Locked'}</span>` : ''}
                          </div>
                       </td>
                       <td class="pro-td-status"><span class="${pillClass(status)}" id="pill-${sub.id}">${statusText(status)}</span></td>
                       <td class="pro-code">${esc(sub.subject_code)}</td>
                       <td style="font-size:10px;">${esc(sub.subject_name)}</td>
                       <td class="pro-units">${parseFloat(sub.units)||0}</td>
                       <td class="pro-prereq-col">${esc(sub.bridging_for||'—')}</td>
                     </tr>`;
                   }).join('')}
                   <tr class="pro-total-row" data-full-total="${bridgingUnits}">
                     <td></td>
                     <td class="pro-td-status"></td>
                     <td colspan="2" style="text-align:right;padding-right:8px;font-weight:700;color:var(--gold-d);">Total Units</td>
                     <td class="pro-units">${fmt(bridgingUnits)}</td>
                     <td></td>
                   </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      ${subjects.length?`<div class="pro-grand-total">Grand Total: <strong>${fmt(grandTotal)} units</strong></div>`:''}
      ${sigHtml}
    </div>
  </div>`;

  document.getElementById('tab-prospectus-body').innerHTML = buildCombinedBar(gwaData) + proHtml;
}

/* ═══════════════════════════════════════════════════════════
   BUILD GRADE TABLE
 ═══════════════════════════════════════════════════════════ */
 function buildGradeTable(subjects, student, ay, prereqUnlockMap, isFinalized = false) {
   if(!subjects?.length) return `<table class="pro-table">
     <thead><tr>
       <th class="pro-th" style="width:54px;text-align:center;">Grade</th><th class="pro-th pro-th-status" style="width:36px;text-align:center;">Status</th>
       <th class="pro-th" style="width:62px;">Code</th><th class="pro-th">Description</th>
       <th class="pro-th" style="width:32px;text-align:center;">Units</th><th class="pro-th" style="width:46px;">Pre-Req</th>
     </tr></thead>
     <tbody><tr><td colspan="6" class="pro-empty">No subjects</td></tr></tbody>
   </table>`;

   let rows = ''; let total = 0;
   subjects.forEach(sub => {
     const raw    = gradeMap[sub.id] != null ? gradeMap[sub.id] : null;
     const status = raw != null ? gradeStatus(roundGrade(raw)) : (sub.grade_status||'not_taken');
     const inpCls = raw != null ? gClass(status) : '';
     const prereqCode = (sub.display_prerequisite||sub.prerequisite||'').trim();
     const pi = prereqUnlockMap ? (prereqUnlockMap[sub.id]||{unlocked:true}) : {unlocked:true};
     const isPrereqLocked = !pi.unlocked;
     const isInLoad = !!sub.is_in_load;
     const isCredited = !!sub.is_credited;
     const isInCurrentPeriod = isSubjectInCurrentPeriod(sub);
     const isManuallyUnlocked = manuallyUnlockedSubjects.has(parseInt(sub.id)) || manuallyUnlockedSubjects.has(sub.id);

     // A subject is "finalization-locked" when the semester table it lives in is
     // finalized — BUT only if it is NOT an additionally-loaded cross-year subject.
     // Cross-year subjects (e.g. a 1st Year subject re-loaded into a 2nd Year
     // semester as an "Additional Subject") have is_in_load = true but their native
     // year_level doesn't match the current student standing.  Their native semester
     // table may already be finalized, yet they must remain editable in their newly
     // loaded period.  We detect this by checking whether the subject's own semester
     // key is different from the semester table that called buildGradeTable.  In
     // practice the simplest signal is: if isFinalized is true but is_in_load is
     // true AND the subject is NOT in its native current period, it is a cross-year
     // additional — skip the finalization lock so grade input stays open.
     const isCrossYearAdditional = isFinalized && isInLoad && !isInCurrentPeriod && !isCredited;
     const isFinalizedLocked = isFinalized && !isCrossYearAdditional;

     // Determine if input should be disabled.
     // A subject is editable when ALL of the following hold:
     //   1. Prerequisites met (pi.unlocked)
     //   2. Semester not finalization-locked (respects cross-year additional exception)
     //   3. Not a credited transfer subject
     //   4. Is in the student's confirmed load (is_in_load = true)
     //      is_in_load is the single authoritative gate:
     //      • For the current period it is always true (set in renderProspectus).
     //      • For past periods it reflects the server-persisted enrollment list —
     //        subjects that were never officially loaded remain false and stay locked
     //        even after a page refresh or re-open.
     let shouldDisable = (window.evaluationLocked === true) || isPrereqLocked || isFinalizedLocked || isCredited || !isInLoad;
     // Manual per-row unlock via password bypasses all locks except credited
     if (isManuallyUnlocked && !isCredited) shouldDisable = false;

      total += parseFloat(sub.units)||0;

      let lockDesc = '';
      let badgeStyle = '';
      let badgeIcon = 'fa-lock';

      if(isPrereqLocked) {
        const parts = [];
        if(pi.directLocked && pi.directPrereqSubj) parts.push(`Pass ${esc(pi.directPrereqCode)}`);
        if(pi.setLocked && pi.setBlockedBy?.length) pi.setBlockedBy.forEach(b => parts.push(`Pass ${esc(b.subject_code)}`));
        lockDesc = parts.join(', ');
        badgeStyle = ''; // default lock style
        badgeIcon = 'fa-lock';
      } else if(isFinalizedLocked) {
        lockDesc = '';
        badgeStyle = 'display:none;';
      } else if(isCredited) {
        lockDesc = ''; // badge already shown by _applyTransferVisuals
        badgeStyle = 'display:none;';
        badgeIcon = 'fa-check-circle';
      } else if(!isInLoad) {
        // Subject not in the student's confirmed enrollment list.
        // For non-IBM, applyRestrictions will add its own badge; hide ours to avoid duplication.
        // For regular and transfer students, show a clear "Not in student load" badge.
        if (currentStudentType === 'non_ibm') {
          lockDesc = '';
          badgeStyle = 'display:none;';
        } else {
          lockDesc = 'Not in student load';
          badgeStyle = 'background:rgba(128,128,128,.15);border-color:#999;color:#666;';
          badgeIcon = 'fa-info-circle';
        }
      } else if(!isInCurrentPeriod && !isInLoad) {
        // Only show "Not in current term" badge for subjects that are neither
        // in the current period NOR explicitly enrolled (cross-year selected).
        // If is_in_load is true for a non-current-period subject, it was
        // confirmed in the enrollment modal — show no lock badge for it.
        lockDesc = 'Not in current term';
        badgeStyle = 'background:rgba(100,116,139,.15);border-color:#94a3b8;color:#475569;';
        badgeIcon = 'fa-calendar-xmark';
      }

     const isPrereqSetTarget = Array.isArray(prereqSetsData) && prereqSetsData.some(set =>
       set.major_id == currentStudent?.major_id && parseInt(set.target_subject_id) === parseInt(sub.id)
     );
     let rowClass = '';
     if (isFinalizedLocked) {
       // Only apply finalized styling when the lock actually applies —
       // cross-year additionally-loaded subjects are exempt (isFinalizedLocked is false for them).
       rowClass = 'row-finalized';
     } else if (shouldDisable) {
       rowClass = isCredited ? 'row-credited' : 'row-locked';
     }

rows += `<tr id="row-${sub.id}" class="${rowClass}" data-year-level="${esc(sub.year_level)}" data-semester="${esc(sub.semester)}" data-in-load="${sub.is_in_load ? '1' : '0'}" data-units="${parseFloat(sub.units)||0}">
       <td>
         <div class="grade-cell-wrap">
           <div class="grade-row">
             <input type="number" class="grade-inp ${inpCls}" id="g-${sub.id}"
               value="${raw!=null?parseFloat(raw).toFixed(2):''}"
               min="1" max="5" step="0.01" placeholder="—"
               oninput="onGradeInput(${sub.id}, event)"
               onkeydown="onGradeKeydown(${sub.id}, event); if(event.key==='Enter'){event.preventDefault();saveGrade(${sub.id},${student.id},${student.major_id},'${esc(sub.semester)}','${esc(sub.year_level)}','${esc(ay)}');}"
               ${shouldDisable?'disabled title="'+lockDesc+'"':'title="1.00 to 5.00 · Enter to save"'}>
             <span class="grade-print" style="display:none;">${raw!=null?parseFloat(raw).toFixed(2):'—'}</span>
             <button class="save-btn" id="sbtn-${sub.id}"
               onclick="saveGrade(${sub.id},${student.id},${student.major_id},'${esc(sub.semester)}','${esc(sub.year_level)}','${esc(ay)}')"
               ${shouldDisable?'disabled':''} title="Save grade"><i class="fas fa-save"></i></button>
             ${(shouldDisable && !isCredited) ? `<button class="edit-btn" id="ebtn-${sub.id}" onclick="requestEditGrade(${sub.id},${student.id},${student.major_id},'${esc(sub.semester)}','${esc(sub.year_level)}','${esc(ay)}')" title="Edit this grade (requires password)"><i class="fas fa-edit"></i></button>` : ''}
           </div>
           <div class="grade-hint" id="gl-${sub.id}">${sub.grade_label||''}</div>
              ${shouldDisable && !isFinalizedLocked ? `<span class="lock-badge" style="${badgeStyle}"><i class="fas ${badgeIcon}" style="font-size:7px;"></i>${lockDesc||'Locked'}</span>` : ''}
           </div>
       </td>
      <td class="pro-td-status"><span class="${pillClass(status)}" id="pill-${sub.id}">${statusText(status)}</span></td>
      <td class="pro-code">${esc(sub.subject_code)}</td>
      <td style="font-size:10px;">${esc(sub.subject_name)}</td>
      <td class="pro-units">${parseFloat(sub.units)||0}</td>
      <td class="pro-prereq-col">
        ${window.currentPrereqSetsMap && window.currentPrereqSetsMap[sub.id]
          ? esc(window.currentPrereqSetsMap[sub.id])
          : (prereqCode ? esc(prereqCode) : '—')}
        ${isPrereqSetTarget && !prereqCode && !window.currentPrereqSetsMap?.[sub.id]
          ?'<span class="prereq-chain-info"><i class="fas fa-sitemap" style="font-size:7px;"></i> Set</span>':''}
      </td>
    </tr>`;
  });

   let semTotalPoints = 0;
   let semTotalUnits = 0;
   subjects.forEach(sub => {
     const raw = gradeMap[sub.id];
     if (raw != null) {
       const units = parseFloat(sub.units)||0;
       semTotalPoints += parseFloat(raw) * units;
       semTotalUnits += units;
     }
   });
   const semGWA = semTotalUnits > 0 ? (semTotalPoints / semTotalUnits).toFixed(2) : null;

   // Total row — capture original total for resetting after filter
   const originalTotal = total;  // total accumulated from all subjects in this semester

   // Total row — always render 6 cells...
   if (semGWA) {
     rows += `<tr class="pro-total-row" data-full-total="${originalTotal}">
       <td style="text-align:center;font-size:10px;font-weight:700;color:var(--green);background:#f0ece0;">GWA: ${semGWA}</td>
       <td class="pro-td-status"></td>
       <td colspan="2" style="text-align:right;padding-right:8px;font-weight:700;color:var(--gold-d);">Total Units</td>
       <td class="pro-units">${fmt(total)}</td>
       <td></td>
     </tr>`;
   } else {
     rows += `<tr class="pro-total-row" data-full-total="${originalTotal}">
       <td></td>
       <td class="pro-td-status"></td>
       <td colspan="2" style="text-align:right;padding-right:8px;font-weight:700;color:var(--gold-d);">Total Units</td>
       <td class="pro-units">${fmt(total)}</td>
       <td></td>
     </tr>`;
   }
  return `<table class="pro-table">
    <thead><tr>
      <th class="pro-th" style="width:54px;text-align:center;">Final Grade</th><th class="pro-th pro-th-status" style="width:36px;text-align:center;">Status</th>
      <th class="pro-th" style="width:62px;">Course No.</th><th class="pro-th">Description</th>
      <th class="pro-th" style="width:32px;text-align:center;">Units</th><th class="pro-th" style="width:46px;">Pre-Req</th>
    </tr></thead>
    <tbody>${rows}</tbody>
  </table>`;
}

/* ═══════════════════════════════════════════════════════════
     GRADE INPUT FORMATTING
     Progressive input: 2 → 2.2 → 2.22 (max 3 digits)
     Blocks additional input after 3 digits, allows backspace
    ═══════════════════════════════════════════════════════════ */
  function formatGradeInput(val) {
    if (val === '' || val === null || val === undefined) return val;
    let s = String(val).replace(/[^0-9.]/g, '');
    if (s === '' || s === '.') return '';
    let digits = s.replace(/\./g, '');
    if (digits.length > 3) digits = digits.slice(0, 3);
    if (digits.length === 0) return '';
    if (digits.length === 1) return digits;
    if (digits.length === 2) return digits[0] + '.' + digits[1];
    if (digits.length === 3) return digits[0] + '.' + digits[1] + digits[2];
    return val;
  }

  function onGradeInput(sid, evt) {
    const inp = document.getElementById('g-'+sid);
    if (!inp || inp.disabled) return;
    const val = inp.value;
    let digits = val.replace(/\./g, '').replace(/[^0-9]/g, '');
    let formatted = formatGradeInput(val);
    if (formatted && formatted !== val) {
      inp.value = formatted;
    }
  }

  function onGradeKeydown(sid, evt) {
    const inp = document.getElementById('g-'+sid);
    if (!inp || inp.disabled) return;
    const val = inp.value;
    const digits = val.replace(/\./g, '').replace(/[^0-9]/g, '');
    const isDigit = /[0-9]/.test(evt.key);
    const isBackspace = evt.key === 'Backspace';
    const isArrowKey = ['ArrowLeft', 'ArrowRight', 'Delete'].includes(evt.key);
    if (digits.length >= 3 && isDigit && !isBackspace) {
      evt.preventDefault();
    }
  }

 /* ═══════════════════════════════════════════════════════════
    ON GRADE CHANGE
    ═══════════════════════════════════════════════════════════ */
  function onGradeChange(sid, studentId, majorId, sem, year, ay) {
    let inp = document.getElementById('g-'+sid);
    if(!inp) inp = document.getElementById('bg-'+sid);
    if(!inp) { toast('Input not found','error'); return; }
    const raw = parseFloat(inp.value);
    if(isNaN(raw)||raw<1||raw>5) { toast('Grade must be 1.00–5.00','error'); return; }
    const rounded = roundGrade(raw);
    const status  = gradeStatus(rounded);
    inp.className = 'grade-inp '+gClass(status);
    const glEl = document.getElementById('gl-'+sid);
    if(glEl) glEl.textContent = `→ ${rounded.toFixed(2)} ${gradeLabel(rounded)}`;
    inp.style.boxShadow = (rounded !== raw) ? '0 0 0 2px var(--amber)' : '';
    const btn = document.getElementById('sbtn-'+sid);
    if(btn) { btn.style.background = 'var(--amber-l)'; btn.style.color = 'var(--amber)'; }
  }

/* ═══════════════════════════════════════════════════════════
   SAVE GRADE
═══════════════════════════════════════════════════════════ */
function saveGrade(sid, studentId, majorId, sem, year, ay) {
  let inp = document.getElementById('g-'+sid);
  if(!inp) inp = document.getElementById('bg-'+sid);
  if(!inp) { toast('Input field not found','error'); return; }
  const raw = parseFloat(inp.value);
  if(isNaN(raw)||raw<1||raw>5) { toast('Grade must be 1.00–5.00','error'); return; }
  const btn = document.getElementById('sbtn-'+sid);
  if(btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; btn.disabled = true; }

  const fd = new FormData();
  fd.append('action','save_grade'); fd.append('student_id',studentId);
  fd.append('subject_id',sid); fd.append('major_id',majorId);
  fd.append('grade',raw); fd.append('semester',sem);
  fd.append('year_level',year); fd.append('academic_year',ay);

  fetch(EVAL_PROC,{method:'POST',body:fd}).then(r=>r.json()).then(d => {
    if(btn) btn.disabled = false;
    if(d.success) {
      if(btn) { btn.innerHTML = '<i class="fas fa-check"></i>'; btn.className = 'save-btn saved'; setTimeout(()=>{btn.innerHTML='<i class="fas fa-save"></i>';btn.className='save-btn';},2400); }
      const rounded = d.grade_rounded; const status = d.status;
      inp.value = parseFloat(rounded).toFixed(2);
      inp.className = 'grade-inp '+gClass(status); inp.style.boxShadow = '';
      const printSpan = inp.nextElementSibling;
      if(printSpan && printSpan.classList.contains('grade-print')) { printSpan.textContent = parseFloat(rounded).toFixed(2); printSpan.style.display = 'inline-block'; }
      let gl = document.getElementById('gl-'+sid) || document.getElementById('bgl-'+sid);
      if(gl) gl.textContent = d.label||gradeLabel(rounded);
      let pill = document.getElementById('pill-'+sid) || document.getElementById('bpill-'+sid);
      if(pill) { pill.className = pillClass(status); pill.textContent = statusText(status); }
       gradeMap[sid] = parseFloat(rounded);
       // If this subject was temporarily unlocked via password, re-lock it now
       // so the row returns to its normal (finalized / out-of-period) locked state.
       const wasManuallyUnlocked = manuallyUnlockedSubjects.has(parseInt(sid)) || manuallyUnlockedSubjects.has(sid);
       if(wasManuallyUnlocked) {
         manuallyUnlockedSubjects.delete(parseInt(sid));
         manuallyUnlockedSubjects.delete(sid);
         // Restore any visual tint applied by submitEditPassword
         if(inp) { inp.style.background = ''; inp.style.borderColor = ''; }
         const row = document.getElementById('row-'+sid);
         if(row) row.classList.remove('row-edit-unlocked');
       }
       refreshLockStates();
       recalcGWA();
       applyFocusVisuals();
       toast(`Saved: ${d.label||gradeLabel(rounded)} (${parseFloat(rounded).toFixed(2)})`,'success');
      
      setTimeout(() => focusNextInput(sid), 300);
    } else {
      if(btn) btn.innerHTML = '<i class="fas fa-save"></i>';
      toast(d.message||'Save failed','error');
    }
  }).catch(() => {
    if(btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i>'; }
    toast('Network error','error');
  });
}

function focusNextInput(currentSid) {
  const inputs = document.querySelectorAll('.grade-inp:not([disabled])');
  let nextFound = false;
  for(let i = 0; i < inputs.length; i++) {
    if(nextFound && !inputs[i].disabled && inputs[i].value === '') {
      inputs[i].focus();
      inputs[i].select();
      return;
    }
    if(inputs[i].id === 'g-'+currentSid || inputs[i].id === 'bg-'+currentSid) {
      nextFound = true;
    }
  }
  if(inputs.length > 0) {
    for(let i = 0; i < inputs.length; i++) {
      if(!inputs[i].disabled && inputs[i].value === '') {
        inputs[i].focus();
        inputs[i].select();
        return;
      }
    }
  }
}

/* ═══════════════════════════════════════════════════════════
   REFRESH LOCK STATES
═══════════════════════════════════════════════════════════ */
function refreshLockStates() {
  if(!loadedSubjects.length) return;
  const prereqUnlockMap = buildPrereqUnlockMap(loadedSubjects, gradeMap, prereqSetsData, currentStudent?.major_id);
  loadedSubjects.forEach(sub => {
    const pi   = prereqUnlockMap[sub.id]||{unlocked:true};
    const row  = document.getElementById('row-'+sub.id); if(!row) return;
    const inp  = document.getElementById('g-'+sub.id);
    const sbtn = document.getElementById('sbtn-'+sub.id);
    const lockEl = row.querySelector('.lock-badge');
    const subSem = (sub.semester||'');
    const fkey   = `${sub.year_level||''}|${subSem.includes('1st')?'1st Semester':'2nd Semester'}`;
    const isFinalized = finalizedMap[fkey];

    // Check Non-IBM restrictions — but ALWAYS allow subjects that belong to
    // the student's current standing semester (they're being evaluated now).
    const _std = currentStudent ? parseStudentStanding(currentStudent.year_level || '1st Year - 1st Semester') : {yr:1, sem:1};
    const _curY = YEAR_LABELS[_std.yr - 1] || '1st Year';
    const _curTok = _std.sem === 1 ? '1st' : '2nd';
    const _subInCurrentPeriod = (sub.year_level||'').trim() === _curY &&
                                (sub.semester||'').toLowerCase().includes(_curTok);

    // Cross-year additional subjects: their native semester table may be finalized,
    // but because they were explicitly loaded into a different period's enrollment
    // list they must remain editable.  Detect the same way as buildGradeTable:
    // finalized native key + is_in_load true + not in its native current period.
    const isCrossYearAdditional = isFinalized && !!sub.is_in_load && !_subInCurrentPeriod && !sub.is_credited;
    const effectivelyFinalized  = isFinalized && !isCrossYearAdditional;
    const isNonIBMRestricted = currentStudentType === 'non_ibm' &&
                               typeof NonIBMEvaluation !== 'undefined' &&
                               !NonIBMEvaluation.isSubjectAllowed(sub.id) &&
                               !_subInCurrentPeriod;

    // Check Transfer credited subjects
    const isTransferCredited = currentStudentType === 'transfer' && typeof TransferEvaluation !== 'undefined' && TransferEvaluation.isSubjectCredited(sub.id);

    // Check if this subject has been manually unlocked via password
    const isManuallyUnlocked = manuallyUnlockedSubjects.has(parseInt(sub.id)) || manuallyUnlockedSubjects.has(sub.id);

    if(isTransferCredited) {
      // Transfer credited subjects stay locked with their credited grade
      if(inp) { inp.disabled = true; inp.title = 'Credited from previous school'; }
      if(sbtn) sbtn.disabled = true;
    } else if(isManuallyUnlocked) {
      // Per-row password-unlocked: allow editing regardless of period/finalized
      row.classList.remove('row-locked');
      if(inp) { inp.disabled = false; inp.title = '1.00 to 5.00 · Enter to save (edit unlocked)'; }
      if(sbtn) sbtn.disabled = false;
      if(lockEl) lockEl.style.display = 'none';
    } else if(isNonIBMRestricted) {
      // Non-IBM restricted subjects stay locked
      row.classList.add('row-locked');
      if(inp) { inp.disabled = true; inp.title = 'Not in subject load — Non-IBM restriction'; }
      if(sbtn) sbtn.disabled = true;
    } else if(pi.unlocked && !effectivelyFinalized && sub.is_in_load &&
              (_subInCurrentPeriod || !_subInCurrentPeriod)) {
      // Unlock any row where:
      //  - Prerequisites are met (pi.unlocked)
      //  - The semester is not effectively finalized (cross-year additional subjects
      //    are exempt — their native semester may be finalized but they were
      //    re-loaded into a newer period, so editing must remain open)
      //  - The subject is in the student's confirmed load (is_in_load = true)
      // is_in_load is the single source of truth. For the current period it is
      // always true. For past periods it reflects the server-persisted enrollment
      // list — subjects that were never officially loaded stay false (locked) even
      // after a page refresh or re-open.
      row.classList.remove('row-locked');
      if(inp) { inp.disabled = false; inp.title = '1.00 to 5.00 · Enter to save'; }
      if(sbtn) sbtn.disabled = false;
      if(lockEl) lockEl.style.display = 'none';
    } else {
      row.classList.add('row-locked');
      if(inp) inp.disabled = true;
      if(sbtn) sbtn.disabled = true;
      if(lockEl) lockEl.style.display = 'inline-flex';
    }
  });

  // Re-apply Non-IBM restrictions after lock state refresh
  if(currentStudentType === 'non_ibm' && typeof NonIBMEvaluation !== 'undefined') {
    NonIBMEvaluation.applyRestrictions();
  }
}

/* ═══════════════════════════════════════════════════════════
   PER-ROW EDIT (password-gated)
   Allows an instructor to unlock a single subject row for editing
   after verifying their account password. The unlock is transient:
   once saveGrade succeeds, the row re-locks automatically.
═══════════════════════════════════════════════════════════ */
function requestEditGrade(sid, studentId, majorId, sem, year, ay) {
  // Remove any existing edit modal
  document.getElementById('editGradeModal')?.remove();

  const sub = loadedSubjects.find(s => parseInt(s.id) === parseInt(sid));
  const subjectLabel = sub ? `${sub.subject_code} — ${sub.subject_name}` : `Subject #${sid}`;

  const modalHtml = `
    <div id="editGradeModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:15000;display:flex;align-items:center;justify-content:center;padding:20px;">
      <div style="background:linear-gradient(145deg,#fff,#fafaf8);border-radius:16px;padding:26px 30px;max-width:420px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.35);border:1px solid rgba(184,134,11,.2);">
        <div style="width:58px;height:58px;margin:0 auto 14px;background:linear-gradient(135deg,var(--green),#15803d);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;color:#fff;"><i class="fas fa-unlock-alt"></i></div>
        <h3 style="font-size:17px;font-weight:700;color:var(--dark);margin-bottom:6px;text-align:center;font-family:'Playfair Display',serif;">Unlock Grade for Editing</h3>
        <p style="font-size:12px;color:var(--muted);margin-bottom:14px;text-align:center;line-height:1.5;">
          You are about to edit:<br>
          <strong style="color:var(--dark);">${esc(subjectLabel)}</strong><br>
          Enter your password to continue.
        </p>
        <input type="password" id="editGradePassword" placeholder="Your password" style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;margin-bottom:10px;box-sizing:border-box;font-family:'Poppins',sans-serif;">
        <p id="editGradePasswordError" style="font-size:11px;color:var(--red);margin-bottom:10px;display:none;"></p>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button onclick="closeEditGradeModal()" style="padding:9px 18px;border:1px solid var(--border);border-radius:8px;background:var(--cream);color:var(--mid);font-size:12px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;">Cancel</button>
          <button id="editGradeConfirmBtn" onclick="submitEditPassword(${sid},${studentId},${majorId},'${esc(sem)}','${esc(year)}','${esc(ay)}')" style="padding:9px 18px;border:none;border-radius:8px;background:linear-gradient(135deg,var(--green),#15803d);color:#fff;font-size:12px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;"><i class="fas fa-check" style="margin-right:5px;"></i>Verify &amp; Unlock</button>
        </div>
      </div>
    </div>`;
  const wrap = document.createElement('div');
  wrap.innerHTML = modalHtml;
  document.body.appendChild(wrap.firstElementChild);

  const pwdInput = document.getElementById('editGradePassword');
  setTimeout(() => pwdInput?.focus(), 100);
  pwdInput?.addEventListener('keydown', function(e){
    if(e.key === 'Enter') { e.preventDefault(); submitEditPassword(sid, studentId, majorId, sem, year, ay); }
  });
}

function closeEditGradeModal() {
  document.getElementById('editGradeModal')?.remove();
}

function submitEditPassword(sid, studentId, majorId, sem, year, ay) {
  const pwdInput = document.getElementById('editGradePassword');
  const errorEl  = document.getElementById('editGradePasswordError');
  const confirmBtn = document.getElementById('editGradeConfirmBtn');
  if(!pwdInput) return;
  const password = pwdInput.value.trim();
  if(!password) {
    if(errorEl) { errorEl.textContent = 'Please enter your password'; errorEl.style.display = 'block'; }
    return;
  }

  if(confirmBtn) { confirmBtn.disabled = true; confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying…'; }
  if(errorEl) errorEl.style.display = 'none';

  const fd = new FormData();
  fd.append('action', 'verify_password');
  fd.append('password', password);

  fetch(EVAL_PROC, {method:'POST', body:fd})
    .then(r => r.json())
    .then(data => {
      if(data.success) {
        manuallyUnlockedSubjects.add(parseInt(sid));
        // Enable the row's input + save button directly
        const inp  = document.getElementById('g-'+sid);
        const sbtn = document.getElementById('sbtn-'+sid);
        const row  = document.getElementById('row-'+sid);
        if(inp)  { inp.disabled = false; inp.title = '1.00 to 5.00 · Enter to save (edit unlocked)'; inp.style.background = '#f0fdf4'; inp.style.borderColor = '#86efac'; }
        if(sbtn) { sbtn.disabled = false; }
        if(row)  { row.classList.remove('row-locked'); row.classList.add('row-edit-unlocked'); }
        // Hide any lingering lock badges for this row
        row?.querySelectorAll('.lock-badge').forEach(b => { b.style.display = 'none'; });
        // Hide the edit button itself while the row is unlocked
        const ebtn = document.getElementById('ebtn-'+sid);
        if(ebtn) ebtn.style.display = 'none';
        closeEditGradeModal();
        toast('Edit unlocked. Press Enter or click Save to commit the new grade.', 'success', 3200);
        setTimeout(() => { inp?.focus(); inp?.select(); }, 150);
      } else {
        if(errorEl) { errorEl.textContent = data.message || 'Invalid password'; errorEl.style.display = 'block'; }
        if(confirmBtn) { confirmBtn.disabled = false; confirmBtn.innerHTML = '<i class="fas fa-check" style="margin-right:5px;"></i>Verify &amp; Unlock'; }
        pwdInput.focus();
        pwdInput.select();
      }
    })
    .catch(() => {
      if(errorEl) { errorEl.textContent = 'Error verifying password. Please try again.'; errorEl.style.display = 'block'; }
      if(confirmBtn) { confirmBtn.disabled = false; confirmBtn.innerHTML = '<i class="fas fa-check" style="margin-right:5px;"></i>Verify &amp; Unlock'; }
    });
}

/* ═══════════════════════════════════════════════════════════
   RECALCULATE GWA
═══════════════════════════════════════════════════════════ */
function recalcGWA() {
  let tp=0, tu=0, up=0;
  document.querySelectorAll('.grade-inp').forEach(inp => {
    const sid = inp.id.replace('g-','');
    if(!sid || isNaN(Number(sid))) return;
    const raw = parseFloat(inp.value); if(isNaN(raw)||raw<1||raw>5) return;
    const rounded = roundGrade(raw);
    const row  = document.getElementById('row-'+sid); if(!row) return;
    const cells = row.querySelectorAll('td');
    const units = cells[4] ? parseFloat(cells[4].textContent) : 0; if(!units) return;
    tp += rounded * units; tu += units;
    if(gradeStatus(rounded) === 'passed') up += units;
  });
  const gwaEl = document.getElementById('liveGWA'); if(gwaEl) gwaEl.textContent = tu > 0 ? (tp/tu).toFixed(2) : '—';
  const utEl  = document.getElementById('liveUnitsTaken'); if(utEl)  utEl.textContent  = fmt(tu);
  const upEl  = document.getElementById('liveUnitsPassed'); if(upEl) upEl.textContent  = fmt(up);
  const ufEl  = document.getElementById('liveUnitsFailed'); if(ufEl) ufEl.textContent  = fmt(tu-up);
}

/* ═══════════════════════════════════════════════════════════
   BUILD ADVISEMENT
═══════════════════════════════════════════════════════════ */
function buildAdvisement(silent=false) {
  if(!currentStudent) return;
  if(!silent) document.getElementById('tab-advisement-body').innerHTML = `<div class="empty-state"><div class="spinner"></div><p style="margin-top:12px;">Analyzing…</p></div>`;
  const fd = new FormData();
  fd.append('action','get_advisement'); fd.append('student_id',currentStudent.id);
  fd.append('major_id',currentStudent.major_id||0); fd.append('academic_year',currentAY);
  fetch(EVAL_PROC,{method:'POST',body:fd}).then(r=>r.json()).then(d => {
    if(!d.success) { document.getElementById('tab-advisement-body').innerHTML=`<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>${esc(d.message)}</p></div>`; return; }
    renderAdvisement(d);
  });
}

/* ═══════════════════════════════════════════════════════════
   RENDER ADVISEMENT
═══════════════════════════════════════════════════════════ */
function renderAdvisement(d) {
  const adv = d.advisement||{};
  const currentYearStr = currentStudent?.year_level||'1st Year';
  const {yr:cYr,sem:cSem} = parseStudentStanding(currentYearStr);
  const {yr:nYr,sem:nSem} = getNextSemester(cYr,cSem);
  const nextYearLabel = `${['1st','2nd','3rd','4th'][nYr-1]||nYr+'th'} Year`;
  const nextSemLabel  = nSem===1 ? '1st Semester' : '2nd Semester';
  const nextAY = d.next_year||currentAY;

   const rec     = adv.recommended||[];
   const retake  = adv.retake||[];
   const condl   = adv.conditional||[];
   const blocked = adv.blocked||[];
   const done    = (adv.completed||[]).filter(sub => sub.grade_rounded !== undefined && sub.grade_rounded !== null);

  const nextRec  = rec.filter(s2 => (YEAR_NUM[s2.year_level]||1)===nYr && (SEM_NUM[s2.semester]||1)===nSem);
  const laterRec = rec.filter(s2 => !nextRec.includes(s2));

  const badge = document.getElementById('advBadge');
  if(badge) { badge.style.display = nextRec.length ? 'inline-flex' : 'none'; badge.textContent = nextRec.length; }

   let html = `<div class="summary-strip">
     <div class="sum-card sum-done"><div class="sum-num">${done.length}</div><div class="sum-lbl">Completed</div></div>
     <div class="sum-card sum-rec"><div class="sum-num">${nextRec.length}</div><div class="sum-lbl">Enroll Next</div></div>
     <div class="sum-card sum-cond"><div class="sum-num">${condl.length}</div><div class="sum-lbl">Conditional</div></div>
     <div class="sum-card sum-fail"><div class="sum-num">${retake.length}</div><div class="sum-lbl">Must Retake</div></div>
     <div class="sum-card sum-block"><div class="sum-num">${blocked.length}</div><div class="sum-lbl">Blocked</div></div>
   </div>
   <div class="context-banner">
     <div class="context-title"><i class="fas fa-calendar-alt" style="margin-right:6px;"></i>
       Enrollment Recommendation for <strong>${nextSemLabel} — ${nextYearLabel} (${nextAY})</strong>
     </div>
     <div class="context-sub">Current standing: <strong>${esc(currentYearStr)}</strong> &nbsp;·&nbsp; Showing subjects recommended for the upcoming semester.</div>
   </div>
   <div style="text-align:right;margin:16px 0;">
     <button class="btn btn-green" onclick="finalizeEval()" style="font-size:13px;padding:10px 18px;">
       <i class="fas fa-check-circle"></i> Finalize Evaluation
     </button>
   </div>`;

  if(nextRec.length) {
    html += `<div class="adv-section"><div class="adv-sec-title ast-green"><i class="fas fa-check-circle"></i> Recommended for ${nextSemLabel} — ${nextYearLabel} <span style="opacity:.7;font-size:11px;">(${nextRec.length})</span></div><div class="adv-grid">`;
    nextRec.forEach(sub => {
      const unlocks = (loadedSubjects||[]).filter(ls => (ls.prerequisite||'').trim().toUpperCase()===sub.subject_code.trim().toUpperCase());
      html += `<div class="adv-card ac-rec">
        <div class="adv-code">${esc(sub.subject_code)}</div>
        <div class="adv-name">${esc(sub.subject_name)}</div>
        <div class="adv-meta">${esc(sub.year_level)} · ${esc(sub.semester)} · ${parseFloat(sub.units)||0} units</div>
        <div class="adv-reason ar-rec">${esc(sub.reason||'Available for enrollment')}</div>
        ${sub.grade_rounded?`<span class="grade-badge gb-pass">${parseFloat(sub.grade_rounded).toFixed(2)} — ${gradeLabel(parseFloat(sub.grade_rounded))}</span>`:''}
        ${unlocks.length?`<div class="adv-chain"><strong>Completing this unlocks:</strong><br>${unlocks.map(u=>`<span class="unlock-tag"><i class="fas fa-arrow-right" style="font-size:8px;"></i> ${esc(u.subject_code)}</span>`).join(' ')}</div>`:''}
      </div>`;
    });
    html += `</div></div>`;
  }

  if(retake.length) {
    html += `<div class="adv-section"><div class="adv-sec-title ast-red"><i class="fas fa-redo"></i> Must Retake — Failed <span style="opacity:.7;font-size:11px;">(${retake.length})</span></div><div class="adv-grid">`;
    retake.forEach(sub => {
      html += `<div class="adv-card ac-fail">
        <div class="adv-code">${esc(sub.subject_code)}</div>
        <div class="adv-name">${esc(sub.subject_name)}</div>
        <div class="adv-meta">${esc(sub.year_level)} · ${esc(sub.semester)} · ${parseFloat(sub.units)||0} units</div>
        <div class="adv-reason ar-fail">${esc(sub.reason||'Failed — must re-enroll')}</div>
        ${sub.grade_rounded?`<span class="grade-badge gb-fail">${parseFloat(sub.grade_rounded).toFixed(2)} — ${gradeLabel(parseFloat(sub.grade_rounded))}</span>`:''}
      </div>`;
    });
    html += `</div></div>`;
  }

  if(condl.length) {
    html += `<div class="adv-section"><div class="adv-sec-title ast-amber"><i class="fas fa-exclamation-triangle"></i> Conditional — Removal Exam Required <span style="opacity:.7;font-size:11px;">(${condl.length})</span></div><div class="adv-grid">`;
    condl.forEach(sub => {
      html += `<div class="adv-card ac-cond">
        <div class="adv-code">${esc(sub.subject_code)}</div>
        <div class="adv-name">${esc(sub.subject_name)}</div>
        <div class="adv-meta">${esc(sub.year_level)} · ${esc(sub.semester)} · ${parseFloat(sub.units)||0} units</div>
        <div class="adv-reason ar-cond">${esc(sub.reason||'Grade 4.00 — removal exam needed')}</div>
        ${sub.grade_rounded?`<span class="grade-badge gb-cond">${parseFloat(sub.grade_rounded).toFixed(2)}</span>`:''}
      </div>`;
    });
    html += `</div></div>`;
  }

  if(blocked.length) {
    html += `<div class="adv-section"><div class="adv-sec-title ast-slate"><i class="fas fa-lock"></i> Blocked — Prerequisite Not Yet Passed <span style="opacity:.7;font-size:11px;">(${blocked.length})</span></div><div class="adv-grid">`;
    blocked.forEach(sub => {
      const prereqCode = (sub.prerequisite||'').trim().toUpperCase();
      const prereqSubj = (loadedSubjects||[]).find(ls => ls.subject_code.trim().toUpperCase()===prereqCode);
      const prereqGrade = prereqSubj && gradeMap[prereqSubj.id] != null ? gradeMap[prereqSubj.id] : null;
      const setData = Array.isArray(prereqSetsData) ? prereqSetsData.filter(set =>
        set.major_id == currentStudent?.major_id && parseInt(set.target_subject_id)===parseInt(sub.id)
      ) : [];
      let chainHtml = '';
      if(prereqSubj) {
        const ps = prereqGrade != null ? gradeStatus(roundGrade(prereqGrade)) : 'not_taken';
        const pColor = ps==='passed'?'var(--green)':ps==='failed'?'var(--red)':'var(--amber)';
        chainHtml += `<div class="adv-chain"><strong>Must pass first:</strong><br>
          <span class="block-prereq"><i class="fas fa-lock" style="font-size:7px;color:#64748b;"></i> ${esc(prereqSubj.subject_code)} <span style="color:${pColor};font-weight:700;">(${prereqGrade!=null?parseFloat(prereqGrade).toFixed(2):'No grade'})</span></span>
          ${ps==='failed'?'<br><span style="font-size:9px;color:var(--red);display:block;margin-top:3px;"><i class="fas fa-redo"></i> Prerequisite must be retaken</span>':''}
          ${ps==='not_taken'?'<br><span style="font-size:9px;color:var(--amber);display:block;margin-top:3px;"><i class="fas fa-clock"></i> Prerequisite not yet taken</span>':''}
        </div>`;
      }
      setData.forEach(set => {
        const notPassed = (set.subjects||[]).filter(ps => !(gradeMap[ps.id]!=null && gradeStatus(roundGrade(gradeMap[ps.id]))==='passed'));
        if(notPassed.length) chainHtml += `<div class="adv-chain"><strong>Prereq set [${esc(set.code)}] — still need to pass:</strong><br>${notPassed.map(ps=>`<span class="block-prereq"><i class="fas fa-times" style="font-size:7px;color:var(--red);"></i> ${esc(ps.subject_code)}</span>`).join(' ')}</div>`;
      });
      html += `<div class="adv-card ac-block">
        <div class="adv-code">${esc(sub.subject_code)}</div>
        <div class="adv-name">${esc(sub.subject_name)}</div>
        <div class="adv-meta">${esc(sub.year_level)} · ${esc(sub.semester)} · ${parseFloat(sub.units)||0} units</div>
        <div class="adv-reason ar-block">${esc(sub.reason||'Prerequisite required')}</div>
        ${chainHtml}
      </div>`;
    });
    html += `</div></div>`;
  }

  if(laterRec.length) {
    html += `<div class="adv-section"><div class="adv-sec-title ast-blue"><i class="fas fa-calendar-plus"></i> Available in Future Semesters <span style="opacity:.7;font-size:11px;">(${laterRec.length})</span></div><div class="adv-grid">`;
    laterRec.forEach(sub => {
      html += `<div class="adv-card ac-done">
        <div class="adv-code">${esc(sub.subject_code)}</div><div class="adv-name">${esc(sub.subject_name)}</div>
        <div class="adv-meta">${esc(sub.year_level)} · ${esc(sub.semester)} · ${parseFloat(sub.units)||0} units</div>
        <div class="adv-reason ar-done">${esc(sub.year_level)} — ${esc(sub.semester)}</div>
      </div>`;
    });
    html += `</div></div>`;
  }

  if(done.length) {
    html += `<div class="adv-section"><div class="adv-sec-title ast-blue"><i class="fas fa-graduation-cap"></i> Completed Subjects <span style="opacity:.7;font-size:11px;">(${done.length})</span></div><div class="adv-grid">`;
    done.forEach(sub => {
      html += `<div class="adv-card ac-done">
        <div class="adv-code">${esc(sub.subject_code)}</div><div class="adv-name">${esc(sub.subject_name)}</div>
        <div class="adv-meta">${esc(sub.year_level)} · ${esc(sub.semester)}</div>
        ${sub.grade_rounded?`<span class="grade-badge gb-pass">${parseFloat(sub.grade_rounded).toFixed(2)} — ${gradeLabel(parseFloat(sub.grade_rounded))}</span>`:''}
      </div>`;
    });
    html += `</div></div>`;
  }

  if(!rec.length&&!retake.length&&!condl.length&&!blocked.length&&!done.length) {
    html = `<div class="empty-state"><i class="fas fa-inbox"></i><h3>No prospectus data</h3><p>Configure the department prospectus first.</p></div>`;
  }
  document.getElementById('tab-advisement-body').innerHTML = html;
}

/* ═══════════════════════════════════════════════════════════
   PRINT
═══════════════════════════════════════════════════════════ */
 function printProspectus() {
   const el = document.getElementById('liveProspectus');
   if(!el) { toast('No prospectus loaded.','error'); return; }
   const pt = document.getElementById('printTarget');
   pt.innerHTML = el.outerHTML;
   pt.querySelectorAll('.grade-inp').forEach(inp => {
     const span = inp.nextElementSibling;
     if(span && span.classList.contains('grade-print')) span.style.display = 'inline-block';
   });
    pt.querySelectorAll('.save-btn,.grade-hint,.lock-badge,.prereq-chain-info,.gwa-strip,.eval-focus-bar,.sem-finalized-badge').forEach(el2 => el2?.remove && el2.remove());
   window.print();
   window.addEventListener('afterprint', () => { pt.innerHTML = ''; }, {once:true});
 }
 
 function printGradesModal() {
   // Get all content directly from current modal
   const schoolHeader = document.getElementById('gmSchoolHeader').cloneNode(true);
   const studentInfo = document.getElementById('gmStudentInfo').cloneNode(true);
   const tableWrap = document.querySelector('.gm-table-wrap').cloneNode(true);
   const summary = document.getElementById('gmSummary').cloneNode(true);
   const sigBlock = document.getElementById('gmSigBlock').cloneNode(true);
   
   // Create new window for printing
   const printWindow = window.open('', '_blank');
   
   printWindow.document.write(`
     <!DOCTYPE html>
     <html>
     <head>
       <title>Grade Report</title>
       <style>
         @page { size: A4 portrait; margin: 12mm 10mm 12mm 10mm; }
         * {
           font-family: 'Times New Roman', Times, serif;
           line-height: 1.4;
           box-sizing: border-box;
         }
         body {
           margin: 0;
           padding: 0;
           background: white;
         }
         .gm-school-header {
           text-align: center;
           border-bottom: 2pt solid #8B6914;
           padding-bottom: 4mm;
           margin-bottom: 4mm;
         }
         .gm-school-header img {
           width: 18mm;
           height: 18mm;
           object-fit: contain;
           margin: 0 auto 2mm auto;
           display: block;
         }
         #gmSchoolName {
           font-size: 14pt;
           font-weight: bold;
           text-transform: uppercase;
           letter-spacing: 0.5pt;
           margin-bottom: 1mm;
         }
         #gmSchoolAddress {
           font-size: 10pt;
           font-style: italic;
           margin-bottom: 1mm;
         }
         #gmInstitute {
           font-size: 11pt;
           font-weight: bold;
           margin-bottom: 1mm;
         }
         #gmDegree {
           font-size: 10pt;
           font-weight: 600;
           color: #8B6914;
         }
         .gm-student-info {
           border: 1pt solid #ddd;
           padding: 4mm;
           margin-bottom: 4mm;
           background: #fafafa;
         }
         .gm-student-info > div:first-child {
           display: grid;
           grid-template-columns: 1fr 1fr;
           gap: 3mm;
           margin-bottom: 3mm;
         }
         .gm-student-info > div:first-child div {
           display: flex;
           align-items: center;
           gap: 4px;
         }
         #gmInfoPeriod {
           text-align: center;
           font-size: 12pt;
           font-weight: bold;
           background: #8B6914;
           color: white;
           padding: 2mm;
           display: block;
         }
         .gm-student-info span {
           font-size: 10pt;
         }
         .gm-table-wrap {
           page-break-inside: avoid;
         }
         .gm-table {
           width: 100%;
           border-collapse: collapse;
           font-size: 10pt;
         }
         
       
         .gm-td {
           padding: 2mm 2mm;
           border: 1pt solid #ccc;
           vertical-align: middle;
         }
         .gm-grade {
           text-align: center;
           font-weight: bold;
         }
         .gm-units {
           text-align: center;
         }
         .gm-tr:nth-child(even) {
           background: #f8f8f8;
         }
         .gm-summary {
           background: #8B6914;
           color: white;
           padding: 3mm;
           margin-top: 3mm;
         }
         .gm-summary div {
           display: flex;
           justify-content: space-between;
           align-items: center;
         }
         #gmSummaryLeft {
           font-size: 10pt;
         }
         #gmSummaryGWA {
           font-size: 14pt;
           font-weight: bold;
           background: rgba(255,255,255,0.15);
           padding: 1mm 3mm;
           border-radius: 10pt;
         }
         .gm-sig-block {
           margin-top: 8mm;
           padding-top: 4mm;
           border-top: 1pt solid #8B6914;
         }
         .gm-sig-block > div {
           display: grid;
           grid-template-columns: 1fr 1fr;
           gap: 15mm;
         }
         .gm-sig-block > div > div {
           text-align: center;
           display: block;
         }
         #gmSigAdvisor,
         #gmSigPH {
           font-size: 14pt;
           font-weight: bold;
           margin-bottom: -5mm;
           display: block;
           position: relative;
           z-index: 1;
         }
         .gm-sig-block div > div > div:nth-child(2) {
           border-bottom: 1pt solid #333;
           height: 6mm;
           margin-bottom: 1mm;
           display: block;
         }
         .gm-sig-block div > div > div:nth-child(3) {
           font-weight: bold;
           color: #8B6914;
           font-size: 10pt;
           display: block;
         }
         .gm-sig-block div > div > div:nth-child(4) {
           font-size: 9pt;
           color: #666;
           display: block;
         }
       </style>
     </head>
     <body>
     </body>
     </html>
   `);
   
   printWindow.document.close();
   
   // Add content to print window
   printWindow.document.body.appendChild(schoolHeader);
   printWindow.document.body.appendChild(studentInfo);
   printWindow.document.body.appendChild(tableWrap);
   printWindow.document.body.appendChild(summary);
   printWindow.document.body.appendChild(sigBlock);
   
   // Clean up styles
   const elements = printWindow.document.body.querySelectorAll('*');
   elements.forEach(el => {
     el.style.display = '';
     el.style.visibility = 'visible';
     el.style.opacity = '1';
     el.style.margin = '';
     el.style.padding = '';
     el.removeAttribute('style');
   });
   
   setTimeout(() => {
     printWindow.print();
     // Close window after print dialog is closed (both on print or cancel)
     printWindow.addEventListener('afterprint', () => {
       setTimeout(() => {
         printWindow.close();
       }, 100);
     });
     // Fallback for browsers that don't support afterprint
     printWindow.addEventListener('mousemove', function closeWindow() {
       setTimeout(() => {
         printWindow.close();
       }, 500);
       printWindow.removeEventListener('mousemove', closeWindow);
     });
   }, 300);
 }

/* ═══════════════════════════════════════════════════════════
   FINALIZE FROM ADVISEMENT TAB — uses auto-detected standing
═══════════════════════════════════════════════════════════ */
function finalizeEval() {
  if (!currentStudent) return;
  // Ensure focusYear/focusSem are auto-set from student's current standing
  const {yr, sem} = parseStudentStanding(currentStudent.year_level || '1st Year - 1st Semester');
  focusYear = YEAR_LABELS[yr-1] || '1st Year';
  focusSem  = sem === 1 ? '1st Semester' : '2nd Semester';
  triggerFinalize();
}

/* ═══════════════════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════════════════ */
function fmt(v) { return v%1===0 ? v : parseFloat(v).toFixed(1); }
function esc(str) {
  if(!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.getElementById('resultModal').addEventListener('click', function(e) {
  if(e.target === this) closeResultModal();
});
</script>

<?php if($show_role_modal): ?>
<div style="position:fixed;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;z-index:99999;">
  <div style="background:#fff;border-radius:16px;padding:32px;max-width:360px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3);">
    <div style="width:80px;height:80px;border-radius:50%;background:rgba(220,38,38,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
      <i class="fas fa-exclamation-triangle" style="font-size:40px;color:#dc2626;"></i>
    </div>
    <h3 style="font-size:20px;font-weight:700;margin-bottom:12px;">Access Restricted</h3>
    <p style="font-size:14px;color:#6b7280;margin-bottom:20px;"><?php echo htmlspecialchars($role_access['message']??'No access.'); ?></p>
    <a href="../../../data/logout.php" style="background:#dc2626;color:#fff;padding:10px 20px;border-radius:10px;text-decoration:none;font-weight:500;">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
   </div>
 </div>
 <?php endif; ?>

 <!-- Evaluation Wizard Modal -->
 <div class="eval-wizard-overlay" id="evalWizardModal">
   <div class="eval-wizard-modal">
     <div class="eval-wizard-header">
       <i class="fas fa-clipboard-check"></i>
       <h3 id="wizardTitle">Evaluate Semester</h3>
     </div>
     <div class="eval-wizard-body">
       <!-- Step 1: Smart Info Summary -->
       <div class="eval-wizard-step active" id="wizardStep1">
         <h4>Smart Information</h4>
         <p>Review the evaluation summary for this semester before confirming.</p>
         <div id="wizardSummaryContainer"></div>
       </div>
       <!-- Step 2: Subject Load & Promote -->
       <div class="eval-wizard-step" id="wizardStep2">
         <h4>Subject Load for Next Semester</h4>
         <p>Based on the evaluation results, the following subjects are recommended for the next semester.</p>
         <div id="wizardSubjectList"></div>
         <div style="margin-top:8px;font-weight:600;color:var(--dark);">
           Total Units: <span id="wizardTotalUnits">0</span>
         </div>
       </div>
       <!-- Actions -->
       <div class="eval-wizard-actions">
         <button class="eval-btn eval-btn-secondary" onclick="wizardPrevStep()" id="wizardBackBtn" style="display:none;">Back</button>
         <button class="eval-btn eval-btn-secondary" onclick="closeEvaluationWizard()">Cancel</button>
         <button class="eval-btn eval-btn-primary" onclick="wizardNextStep()" id="wizardNextBtn">Continue</button>
         <button class="eval-btn eval-btn-primary" onclick="wizardFinish(true)" id="wizardFinishBtn" style="display:none;">Conform Subject Load and Promote</button>
         <button class="eval-btn eval-btn-secondary" onclick="wizardFinish(false)" id="wizardStayBtn" style="display:none;">Stay on this Level</button>
       </div>
     </div>
</div>
  </div>

  <!-- Floating Evaluation Button - Premium Gold Design -->
  <button class="fab-eval-btn" id="fabEvaluateMain" title="Quick Evaluate Current Student" onclick="quickEvaluate()">
    <i class="fas fa-clipboard-check"></i>
    <span class="fab-eval-label">Evaluation</span>
  </button>

  <script>
  function quickEvaluate() {
    const overlay = document.getElementById('evalOverlay');

    // Case 1 — Nothing is open: show the "open a student first" toast
    if (!overlay || !overlay.classList.contains('open')) {
      toast('Open a student prospectus first by clicking on a mentee card', 'info');
      return;
    }

    // Case 2 — Prospectus is open but the student is graduated:
    //          show PDF locate/regenerate progress
    if (window.evaluationLocked || (currentStudent && currentStudent.status === 'graduated')) {
      const studentId = currentStudent ? currentStudent.id : (window.graduationRecord ? window.graduationRecord.student_id : null);
      const pdfUrl    = window.graduationRecord && window.graduationRecord.pdf_path
                        ? '../../../data/download_graduation_pdf.php?student_id=' + encodeURIComponent(studentId || '')
                        : null;

      showGraduationProgressModal();
      updateGraduationProgress(10, 1, 'Checking graduation records…');

      setTimeout(() => {
        // Jump straight to regenerating the PDF — the file may have been deleted from
        // disk by the instructor. download_graduation_pdf.php would return 404; skip the
        // redundant HEAD check and go straight to rebuild so the browser console shows
        // no spurious ERR_ABORTED 404 error.
        updateGraduationProgress(35, 2, 'Locating saved prospectus…');
        updateGraduationProgress(55, 3, 'Record not found or file was deleted — regenerating prospectus…');
        updateGraduationProgress(75, 4, 'Re-building official document and saving to disk…');
        _graduateRegenerateFlow(studentId);
      }, 800);

      return;
    }

    // Case 3 — Prospectus is open and the student is not graduated: run normal auto-evaluate
    autoDetectAndEvaluate();
  }

  function _graduateRegenerateFlow(studentId) {
    updateGraduationProgress(55, 3, 'Record not found — regenerating prospectus…');

    setTimeout(() => {
      updateGraduationProgress(75, 4, 'Re-building official document and saving to disk…');

      // Build POST payload for regenerate_graduation_pdf
      const fd2 = new FormData();
      fd2.append('action',        'regenerate_graduation_pdf');
      fd2.append('student_id',    currentStudent.id);
      fd2.append('academic_year', currentAY);
      fd2.append('year_level',    currentStudent.year_level || '4th Year');
      fd2.append('semester',      currentStudent.year_level && /2nd/i.test(currentStudent.year_level) ? '2nd Semester' : '2nd Semester');

      fetch(EVAL_PROC, {method:'POST', body:fd2, credentials:'same-origin', headers:{'Accept':'application/json'}})
        .then(async r => {
          const text2 = await r.text();
          // Try JSON first; only treat non-2xx as error after parsing so callers
          // can distinguish "server fatal" (500 with JSON error detail) from
          // "expected JSON failure" (e.g. 404 not-found with structured message).
          let parsed = null;
          try { parsed = JSON.parse(text2); } catch (_) { parsed = null; }

          if (parsed !== null) {
            // The server returned JSON on all non-2xx statuses (graceful shutdown handler).
            // Re-throw so the caller sees it as a rejection — the error message carries
            // the structured explanation already built into the string.
            if (!r.ok) throw new Error('Server HTTP ' + r.status + ': ' + (parsed.message || text2));
            if (parsed.success === false) throw new Error(parsed.message || 'Server reported failure');
            return parsed;
          }

          // Not JSON — could be HTML (PHP start-up error) or something unexpected.
          if (!r.ok) throw new Error('Server HTTP ' + r.status + ': ' + text2.slice(0, 300));
          if (/^\s*</.test(text2)) throw new Error('Server returned HTML (check PHP errors): ' + text2.slice(0, 300));
          throw new Error('Invalid response from server: ' + text2.slice(0, 300));
        })
        .then(data => {
          // restore label
          if (_gradConfirmBtn) {
            _gradConfirmBtn.disabled = false;
            _gradConfirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Graduation';
          }

          if (data.success) {
            // updateWindow
            const newRecord = {
              student_id:  currentStudent.id,
              pdf_path:    data.pdf_path || '',
              academic_year: currentAY,
            };
            window.graduationRecord = { ...window.graduationRecord, ...newRecord };

            updateGraduationProgress(93, 4, 'Saved to ' + (data.pdf_path || '…'));
            setTimeout(() => {
              _gpmMarkAllDone(data.pdf_path || '');
              // pause so instructor can see "done"
              setTimeout(() => {
                hideGraduationProgressModal();
                // Open the freshly saved PDF in a new tab
                if (data.pdf_url) {
                  window.open(data.pdf_url, '_blank');
                }
              }, 1200);
            }, 600);
          } else {
            updateGraduationProgress(90, 4);
            // show error inside the modal
            const bar  = document.getElementById('graduationProgressBar');
            const pct  = document.getElementById('progressPercentage');
            const wrap = document.getElementById('gpmIconWrap');
            const ico  = document.getElementById('gpmIcon');
            const t    = document.getElementById('gpmTitle');
            const s    = document.getElementById('gpmSubtitle');
            if (bar)  { bar.style.width='90%'; bar.style.background='linear-gradient(90deg,#ef4444,#dc2626)'; bar.style.animation='none'; }
            if (pct)  { pct.textContent='90%'; pct.style.color='#dc2626'; }
            if (wrap) { wrap.style.background='linear-gradient(145deg,#fca5a5,#ef4444)'; }
            if (ico)  { ico.className='fas fa-times-circle'; }
            if (t)    { t.textContent='Regeneration Failed'; t.style.color='#dc2626'; }
            if (s)    { s.innerHTML='⚠ ' + esc(data.message || 'Unknown error'); s.style.color='#7f1d1d'; s.style.fontWeight='600'; s.style.minHeight='auto'; }
            toast('Error: ' + (data.message || 'regeneration failed'), 'error');
          }
        })
        .catch(err => {
          if (_gradConfirmBtn) {
            _gradConfirmBtn.disabled = false;
            _gradConfirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Graduation';
          }
          toast('Error regenerating PDF: ' + err.message, 'error');
        });
    }, 700);
  }
  </script>

  </body>
  </html>
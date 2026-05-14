<?php
// setup_graduate.php — Graduate Management System
require_once 'data/session_security.php';
$role_access = check_role_access('instructor');
$show_role_modal = !$role_access['allowed'];
$instructor_id = $_SESSION['user_id'] ?? 1;
$user_name = $_SESSION['user_name'] ?? 'Instructor';
if (!$show_role_modal) { require_once 'data/config.php'; }

// ── Handle PDF generation request ──────────────────────────────────────────────
$pdf_message = '';
if (!$show_role_modal && isset($_POST['action']) && $_POST['action'] === 'generate_pdf') {
  $student_id = intval($_POST['student_id'] ?? 0);
  if ($student_id > 0) {
    // Fetch student data
    $stmt = $conn->prepare("
      SELECT s.*, m.major_name, m.major_code
      FROM students s
      LEFT JOIN majors m ON s.major_id = m.id
      WHERE s.id = ? AND s.status = 'graduated'
    ");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($student) {
      // Determine directory path
      $batch = $student['graduation_batch'] ?? date('Y') . '-' . (date('Y') + 1);
      $majorCode = strtolower($student['major_code'] ?? 'gen');
      $dirPath = "C:\\graduate\\batch {$batch}\\{$majorCode}\\";

      // Build the student's full name for filename
      $lastName  = preg_replace('/[^a-z0-9]/', '_', strtolower(trim($student['last_name']  ?? '')));
      $firstName = preg_replace('/[^a-z0-9]/', '_', strtolower(trim($student['first_name'] ?? '')));
      $filename  = "{$firstName}_{$lastName}_{$majorCode}_batch{$batch}.pdf";
      $fullPath  = $dirPath . $filename;

      // Mark PDF as generated in DB (actual file generation happens client-side via print)
      $now = date('Y-m-d H:i:s');
      $upd = $conn->prepare("UPDATE students SET pdf_path = ?, pdf_generated_at = ? WHERE id = ?");
      $upd->bind_param('ssi', $fullPath, $now, $student_id);
      $upd->execute();
      $upd->close();

      $pdf_message = json_encode(['success' => true, 'path' => $fullPath, 'filename' => $filename]);
    } else {
      $pdf_message = json_encode(['success' => false, 'message' => 'Student not found or not graduated.']);
    }
  }
}

// ── Fetch all graduated students ───────────────────────────────────────────────
$graduates = [];
if (!$show_role_modal) {
  $query = "
    SELECT s.*, m.major_name, m.major_code,
           CONCAT(s.first_name, ' ', IFNULL(s.middle_name,''), ' ', s.last_name, ' ', IFNULL(s.suffix,'')) AS full_name,
           s.graduation_batch, s.graduation_date, s.final_gwa,
           s.pdf_path, s.pdf_generated_at
    FROM students s
    LEFT JOIN majors m ON s.major_id = m.id
    WHERE s.status = 'graduated'
    ORDER BY s.graduation_batch DESC, m.major_code ASC, s.last_name ASC
  ";
  $result = $conn->query($query);
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $batch = $row['graduation_batch'] ?? 'Unknown';
      $major = strtolower($row['major_code'] ?? 'gen');
      $graduates[$batch][$major][] = $row;
    }
    $result->free();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="media/LOGO.jpg" type="image/jpeg">
<title>Graduate Management — IBM System</title>
<link rel="stylesheet" href="css/common.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
:root {
  --gold:    #d4a017;
  --gold-d:  #9a6f00;
  --gold-l:  #f5c842;
  --cream:   #fef9e7;
  --green:   #16a34a;
  --green-l: #dcfce7;
  --dark:    #1a1a2e;
  --mid:     #64748b;
  --muted:   #94a3b8;
  --border:  rgba(212,160,23,.22);
  --bg:      #f8f7f3;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Poppins', sans-serif;
  background: var(--bg);
  color: var(--dark);
  min-height: 100vh;
}

/* ── SIDEBAR ─────────────────────────────────────────── */
.sidebar {
  position: fixed; top: 0; left: 0; bottom: 0; width: 260px;
  background: linear-gradient(180deg, #1a1207 0%, #2d1f07 60%, #3a2a0a 100%);
  z-index: 100; display: flex; flex-direction: column;
  box-shadow: 4px 0 24px rgba(0,0,0,.35);
}
.sidebar-header {
  padding: 24px 20px 16px;
  display: flex; align-items: center; gap: 12px;
  border-bottom: 1px solid rgba(255,255,255,.08);
}
.sidebar-logo {
  width: 52px; height: 52px; border-radius: 12px;
  object-fit: cover; border: 2px solid rgba(212,160,23,.6);
  box-shadow: 0 3px 12px rgba(0,0,0,.3);
}
.sidebar-brand-name {
  font-size: 18px; font-weight: 800; color: var(--gold-l);
  letter-spacing: 1px; font-family: 'Playfair Display', serif;
}
.sidebar-nav { padding: 14px 12px; flex: 1; overflow-y: auto; }
.sidebar-nav-label {
  font-size: 9px; font-weight: 700; letter-spacing: 2px;
  text-transform: uppercase; color: rgba(255,255,255,.3);
  padding: 8px 10px 4px;
}
.sidebar-nav-item {
  display: flex; align-items: center; gap: 12px;
  padding: 11px 14px; border-radius: 10px;
  text-decoration: none; color: rgba(255,255,255,.7);
  font-size: 13px; font-weight: 500;
  transition: all .2s; margin-bottom: 3px;
}
.sidebar-nav-item:hover, .sidebar-nav-item.active {
  background: rgba(212,160,23,.18); color: var(--gold-l);
}
.sidebar-nav-item.active { font-weight: 700; }
.sidebar-nav-item i { width: 18px; text-align: center; font-size: 15px; }

/* ── MAIN ─────────────────────────────────────────────── */
.main-content { margin-left: 260px; min-height: 100vh; }

.topbar {
  position: sticky; top: 0; z-index: 80;
  background: rgba(255,255,255,.92);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--border);
  padding: 14px 28px;
  display: flex; align-items: center; justify-content: space-between;
  box-shadow: 0 2px 12px rgba(0,0,0,.07);
}
.topbar-title { font-size: 18px; font-weight: 700; color: var(--dark); }
.topbar-subtitle { font-size: 11px; color: var(--muted); margin-top: 1px; }
.topbar-right { display: flex; align-items: center; gap: 10px; }
.topbar-btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 8px 16px; border-radius: 10px; border: none;
  font-family: 'Poppins', sans-serif; font-size: 12px; font-weight: 600;
  cursor: pointer; text-decoration: none; transition: all .2s;
}
.topbar-return { background: var(--cream); color: var(--gold-d); }
.topbar-return:hover { background: #fef3c7; }
.topbar-logout { background: #fee2e2; color: #dc2626; }
.topbar-logout:hover { background: #fecaca; }

/* ── HERO BANNER ─────────────────────────────────────── */
.hero-banner {
  background: linear-gradient(135deg, #d4a843 0%, #b8922f 40%, #9a7020 100%);
  border-radius: 20px; padding: 30px 34px;
  margin: 24px 24px 0; position: relative; overflow: hidden;
  display: flex; align-items: center; justify-content: space-between;
  gap: 20px; flex-wrap: wrap;
  box-shadow: 0 8px 32px rgba(184,134,11,.3);
}
.hero-banner::before {
  content: '';
  position: absolute; top: -40px; right: -40px;
  width: 250px; height: 250px;
  background: radial-gradient(circle, rgba(255,255,255,.12), transparent 60%);
  pointer-events: none;
}
.hero-eyebrow {
  display: flex; align-items: center; gap: 8px;
  font-size: 10px; font-weight: 700; letter-spacing: 1.5px;
  text-transform: uppercase; color: rgba(255,255,255,.85);
  margin-bottom: 8px;
}
.hero-title {
  font-family: 'Playfair Display', serif;
  font-size: 30px; font-weight: 800; color: #fff; line-height: 1.1;
}
.hero-sub { font-size: 13px; color: rgba(255,255,255,.8); margin-top: 6px; }
.hero-stats {
  display: flex; gap: 16px; flex-wrap: wrap;
  position: relative; z-index: 1;
}
.hero-stat-card {
  background: rgba(255,255,255,.18);
  backdrop-filter: blur(6px);
  border: 1px solid rgba(255,255,255,.25);
  border-radius: 14px; padding: 14px 20px; text-align: center;
  min-width: 100px;
}
.hero-stat-num { font-size: 26px; font-weight: 800; color: #fff; font-family: 'Playfair Display', serif; }
.hero-stat-label { font-size: 9px; font-weight: 600; text-transform: uppercase; letter-spacing: .8px; color: rgba(255,255,255,.8); margin-top: 2px; }

/* ── PAGE CONTENT ─────────────────────────────────────── */
.page-content { padding: 20px 24px 40px; }

/* ── SEARCH & FILTERS ──────────────────────────────────── */
.filters-row {
  display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
  margin-bottom: 20px;
}
.search-box {
  flex: 1; min-width: 200px;
  display: flex; align-items: center; gap: 10px;
  background: #fff; border: 1.5px solid var(--border);
  border-radius: 12px; padding: 10px 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,.06);
}
.search-box i { color: var(--gold-d); font-size: 14px; }
.search-box input {
  border: none; outline: none; background: transparent;
  font-family: 'Poppins', sans-serif; font-size: 13px;
  color: var(--dark); width: 100%;
}
.filter-select {
  padding: 10px 16px; border: 1.5px solid var(--border);
  border-radius: 12px; background: #fff;
  font-family: 'Poppins', sans-serif; font-size: 12px;
  color: var(--dark); cursor: pointer; outline: none;
  box-shadow: 0 2px 8px rgba(0,0,0,.06);
}
.gen-btn {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 20px; border: none; border-radius: 12px;
  background: linear-gradient(135deg, var(--gold-l), var(--gold), var(--gold-d));
  color: #fff; font-family: 'Poppins', sans-serif;
  font-size: 13px; font-weight: 700; cursor: pointer;
  box-shadow: 0 4px 14px rgba(184,134,11,.35);
  transition: all .25s;
}
.gen-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(184,134,11,.5); }

/* ── BATCH SECTION ────────────────────────────────────── */
.batch-section { margin-bottom: 28px; }
.batch-header {
  display: flex; align-items: center; gap: 14px;
  padding: 14px 20px; margin-bottom: 14px;
  background: linear-gradient(135deg, #2d1f07, #3a2a0a);
  border-radius: 14px; color: #fff;
  box-shadow: 0 4px 16px rgba(0,0,0,.2);
}
.batch-header i { font-size: 18px; color: var(--gold-l); }
.batch-label { font-size: 16px; font-weight: 700; font-family: 'Playfair Display', serif; }
.batch-count { margin-left: auto; font-size: 11px; color: rgba(255,255,255,.65); }

.major-group { margin-bottom: 16px; }
.major-label {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 4px 14px; background: var(--cream);
  border: 1.5px solid var(--border); border-radius: 20px;
  font-size: 11px; font-weight: 700; color: var(--gold-d);
  text-transform: uppercase; letter-spacing: .5px; margin-bottom: 10px;
}

/* ── GRADUATE TABLE ───────────────────────────────────── */
.grad-table-wrap {
  background: #fff; border-radius: 14px;
  border: 1px solid var(--border);
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
}
.grad-table {
  width: 100%; border-collapse: collapse; font-size: 12px;
}
.grad-table th {
  padding: 10px 14px; text-align: left;
  background: linear-gradient(135deg, var(--gold-d), #b8860b);
  color: #fff; font-weight: 700; font-size: 10px;
  text-transform: uppercase; letter-spacing: .5px;
  white-space: nowrap;
}
.grad-table td {
  padding: 12px 14px; border-bottom: 1px solid rgba(0,0,0,.05);
  vertical-align: middle;
}
.grad-table tr:last-child td { border-bottom: none; }
.grad-table tr:hover td { background: var(--cream); }
.grad-name { font-weight: 700; color: var(--dark); font-size: 13px; }
.grad-id { font-size: 10px; color: var(--muted); margin-top: 2px; }
.gwa-chip {
  display: inline-block; padding: 3px 10px;
  background: linear-gradient(135deg, var(--cream), #fef3c7);
  border: 1px solid var(--border); border-radius: 20px;
  font-size: 11px; font-weight: 700; color: var(--gold-d);
}
.status-grad {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 11px; border-radius: 20px;
  background: linear-gradient(135deg, #dcfce7, #bbf7d0);
  color: #166534; border: 1px solid #4ade80;
  font-size: 10px; font-weight: 700;
}
.pdf-badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 4px 10px; border-radius: 8px;
  background: #fee2e2; color: #dc2626;
  font-size: 9px; font-weight: 700; border: 1px solid #fca5a5;
}
.pdf-badge.generated { background: #dcfce7; color: #166534; border-color: #86efac; }
.action-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 12px; border: none; border-radius: 8px;
  font-family: 'Poppins', sans-serif; font-size: 11px; font-weight: 600;
  cursor: pointer; transition: all .2s;
}
.btn-pdf { background: linear-gradient(135deg, #f5c842, #d4a017); color: #fff; box-shadow: 0 2px 8px rgba(212,160,23,.3); }
.btn-pdf:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(212,160,23,.45); }
.btn-view { background: #eff6ff; color: #1e40af; }
.btn-view:hover { background: #dbeafe; }

/* ── EMPTY STATE ──────────────────────────────────────── */
.empty-grad {
  text-align: center; padding: 60px 20px;
  color: var(--muted);
}
.empty-grad i { font-size: 56px; color: rgba(212,160,23,.3); margin-bottom: 16px; display: block; }
.empty-grad h3 { font-size: 18px; color: var(--mid); margin-bottom: 8px; }

/* ── PDF PREVIEW MODAL ────────────────────────────────── */
.pdf-overlay {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(0,0,0,.6); backdrop-filter: blur(6px);
  display: none; align-items: center; justify-content: center; padding: 20px;
}
.pdf-overlay.open { display: flex; }
.pdf-modal {
  background: #fff; border-radius: 20px; width: 100%; max-width: 860px;
  max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;
  box-shadow: 0 24px 60px rgba(0,0,0,.4);
}
.pdf-modal-hdr {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 22px;
  background: linear-gradient(135deg, var(--gold-d), #b8860b);
  color: #fff;
}
.pdf-modal-hdr h3 { font-size: 15px; font-weight: 700; font-family: 'Playfair Display', serif; }
.pdf-modal-body { flex: 1; overflow-y: auto; padding: 24px; }
.close-btn {
  background: rgba(255,255,255,.2); border: none; color: #fff;
  width: 32px; height: 32px; border-radius: 8px;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  font-size: 14px; transition: background .2s;
}
.close-btn:hover { background: rgba(255,255,255,.35); }

/* ── PROSPECTUS PRINT TEMPLATE ───────────────────────── */
.prospectus-sheet {
  width: 100%; max-width: 720px; margin: 0 auto;
  background: #fff; font-family: 'Times New Roman', Times, serif;
  font-size: 11pt; color: #000; padding: 16mm 14mm;
}
.pros-school-hdr {
  text-align: center; border-bottom: 2.5pt solid #8B6914;
  padding-bottom: 6mm; margin-bottom: 6mm;
  display: flex; align-items: center; gap: 14px;
}
.pros-school-hdr img { width: 55px; height: 55px; object-fit: cover; border-radius: 8px; border: 2px solid #8B6914; }
.pros-school-text { text-align: center; flex: 1; }
.pros-school-name { font-size: 13pt; font-weight: bold; text-transform: uppercase; letter-spacing: .7pt; }
.pros-school-addr { font-size: 9pt; color: #555; font-style: italic; }
.pros-institute  { font-size: 10pt; font-weight: bold; margin-top: 2pt; }
.pros-degree     { font-size: 9pt; }
.pros-doc-title {
  text-align: center; font-size: 13pt; font-weight: bold;
  letter-spacing: 1pt; padding: 5pt; margin: 5mm 0;
  background: #8B6914; color: #fff;
}
.pros-grad-badge {
  display: inline-block; padding: 4pt 14pt;
  background: #dcfce7; color: #166534;
  border: 1.5pt solid #4ade80; border-radius: 20pt;
  font-size: 9pt; font-weight: bold;
  margin-bottom: 4mm;
}
.pros-info-grid {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 3mm; border: 1pt solid #ccc;
  border-radius: 4pt; padding: 5mm; margin-bottom: 6mm;
  font-size: 10pt;
}
.pros-info-row { display: flex; gap: 4pt; }
.pros-info-label { font-weight: bold; color: #8B6914; min-width: 90pt; }

.pros-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 5mm; }
.pros-table th {
  background: #8B6914; color: #fff;
  padding: 4pt 5pt; text-align: left;
  border: 1pt solid #6b5209; font-weight: bold;
}
.pros-table td { padding: 3pt 5pt; border: 1pt solid #ccc; }
.pros-table tr:nth-child(even) td { background: #fef9e7; }
.pros-year-hdr td {
  background: linear-gradient(90deg, #f0ece0, #ede8d5);
  font-weight: bold; font-size: 9.5pt; color: #5c4200;
  padding: 4pt 5pt; border: 1pt solid #c9b36c;
}
.grade-pass { color: #166534; font-weight: bold; }
.grade-fail { color: #dc2626; font-weight: bold; }

.pros-summary {
  display: flex; justify-content: space-between;
  padding: 5pt 8pt; background: #8B6914; color: #fff;
  border-radius: 4pt; font-weight: bold; font-size: 10pt;
  margin-bottom: 8mm;
}
.pros-sig-grid {
  display: grid; grid-template-columns: 1fr 1fr 1fr;
  gap: 10mm; margin-top: 8mm; font-size: 9pt;
}
.pros-sig-col { text-align: center; }
.pros-sig-line { border-bottom: 1pt solid #333; height: 20mm; margin-bottom: 3mm; }
.pros-sig-name { font-weight: bold; font-size: 10pt; }
.pros-sig-title { color: #8B6914; font-weight: bold; }
.pros-sig-date { color: #555; font-size: 8pt; margin-top: 2pt; }

/* Toast */
.toast {
  position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
  background: #1a1a2e; color: #fff;
  padding: 12px 22px; border-radius: 12px; font-size: 13px;
  display: none; align-items: center; gap: 10px;
  z-index: 99999; box-shadow: 0 6px 24px rgba(0,0,0,.3);
}
.toast.show { display: flex; animation: fadeInUp .3s; }
@keyframes fadeInUp { from { opacity:0; transform: translateX(-50%) translateY(10px); } to { opacity:1; transform: translateX(-50%) translateY(0); } }
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-header">
    <img src="media/LOGO.jpg" alt="Logo" class="sidebar-logo">
    <div><div class="sidebar-brand-name">IBM</div></div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-nav-label">Menu</div>
    <a href="instructor/dashboard.php" class="sidebar-nav-item"><i class="fas fa-chart-pie"></i><span>Overview</span></a>
    <a href="instructor/pages/students.php" class="sidebar-nav-item"><i class="fas fa-user-graduate"></i><span>Students</span></a>
    <a href="instructor/pages/evaluation.php" class="sidebar-nav-item"><i class="fas fa-clipboard-check"></i><span>Evaluation</span></a>
    <a href="setup_graduate.php" class="sidebar-nav-item active"><i class="fas fa-award"></i><span>Graduates</span></a>
    <a href="instructor/pages/reports.php" class="sidebar-nav-item"><i class="fas fa-file-alt"></i><span>Reports</span></a>
  </nav>
</aside>

<!-- MAIN -->
<div class="main-content">

  <!-- TOPBAR -->
  <header class="topbar">
    <div>
      <div class="topbar-title">Graduate Management</div>
      <div class="topbar-subtitle">Official Graduate Records &amp; Prospectus PDF Generator</div>
    </div>
    <div class="topbar-right">
      <a href="index.php" class="topbar-btn topbar-return"><i class="fas fa-home"></i> Return</a>
      <a href="data/logout.php" class="topbar-btn topbar-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </header>

  <!-- HERO BANNER -->
  <?php
    $totalGrads = 0;
    $totalBatches = count($graduates);
    foreach ($graduates as $batch => $majors) {
      foreach ($majors as $major => $students) {
        $totalGrads += count($students);
      }
    }
  ?>
  <div class="hero-banner" style="margin:24px 24px 0;">
    <div style="position:relative;z-index:1;">
      <div class="hero-eyebrow"><span style="width:24px;height:2px;background:#fff;border-radius:2px;display:inline-block;"></span> Graduate Management System</div>
      <h1 class="hero-title" style="color:#2d1f07;">Graduate Records</h1>
      <p class="hero-sub">Manage graduated students, generate PDF prospectuses, and organize records by batch year and major.</p>
    </div>
    <div class="hero-stats">
      <div class="hero-stat-card">
        <div class="hero-stat-num"><?= $totalGrads ?></div>
        <div class="hero-stat-label">Total Graduates</div>
      </div>
      <div class="hero-stat-card">
        <div class="hero-stat-num"><?= $totalBatches ?></div>
        <div class="hero-stat-label">Batch Years</div>
      </div>
      <div class="hero-stat-card">
        <div class="hero-stat-num"><?= date('Y') ?>–<?= date('Y')+1 ?></div>
        <div class="hero-stat-label">Current Batch</div>
      </div>
    </div>
  </div>

  <div class="page-content">

    <!-- STORAGE INFO BANNER -->
    <div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1.5px solid #93c5fd;border-radius:14px;padding:14px 20px;margin-bottom:20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
      <div style="width:38px;height:38px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;flex-shrink:0;">
        <i class="fas fa-folder-open"></i>
      </div>
      <div style="flex:1;">
        <div style="font-size:12px;font-weight:700;color:#1e40af;margin-bottom:2px;"><i class="fas fa-info-circle" style="margin-right:4px;"></i>Local Storage Directory</div>
        <div style="font-size:11px;color:#1e40af;opacity:.8;">PDFs are saved locally at: <code style="background:rgba(30,64,175,.1);padding:2px 6px;border-radius:4px;font-size:11px;">C:\graduate\batch {YEAR}\{MAJOR}\studentname_major_batchyear.pdf</code></div>
      </div>
      <div style="font-size:11px;color:#3b82f6;font-weight:600;">Auto-creates folders if missing</div>
    </div>

    <!-- FILTERS -->
    <div class="filters-row">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="gradSearch" placeholder="Search by name, ID, major, batch…" oninput="filterGraduates()">
      </div>
      <select class="filter-select" id="batchFilter" onchange="filterGraduates()">
        <option value="all">All Batches</option>
        <?php foreach (array_keys($graduates) as $batch): ?>
          <option value="<?= htmlspecialchars($batch) ?>"><?= htmlspecialchars($batch) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="filter-select" id="majorFilter" onchange="filterGraduates()">
        <option value="all">All Majors</option>
        <option value="om">OM</option>
        <option value="fm">FM</option>
        <option value="mm">MM</option>
      </select>
      <button class="gen-btn" onclick="printAllProspectuses()">
        <i class="fas fa-print"></i> Print All
      </button>
    </div>

    <!-- GRADUATE RECORDS -->
    <?php if (empty($graduates)): ?>
      <div class="empty-grad">
        <i class="fas fa-user-graduate"></i>
        <h3>No Graduated Students Yet</h3>
        <p style="font-size:13px;">Graduate records will appear here once students complete their full curriculum and are confirmed as graduated in the evaluation system.</p>
        <a href="instructor/pages/evaluation.php" style="display:inline-flex;align-items:center;gap:8px;margin-top:20px;padding:11px 22px;background:linear-gradient(135deg,var(--gold-l),var(--gold-d));color:#fff;border-radius:12px;text-decoration:none;font-size:13px;font-weight:700;">
          <i class="fas fa-clipboard-check"></i> Go to Evaluation
        </a>
      </div>
    <?php else: ?>
      <?php foreach ($graduates as $batch => $majors): ?>
        <?php
          $batchTotal = 0;
          foreach ($majors as $m => $sts) $batchTotal += count($sts);
        ?>
        <div class="batch-section" data-batch="<?= htmlspecialchars($batch) ?>">
          <div class="batch-header">
            <i class="fas fa-calendar-alt"></i>
            <div>
              <div class="batch-label">Batch <?= htmlspecialchars($batch) ?></div>
              <div style="font-size:10px;color:rgba(255,255,255,.55);margin-top:2px;">A.Y. <?= htmlspecialchars($batch) ?></div>
            </div>
            <div class="batch-count"><?= $batchTotal ?> graduate<?= $batchTotal !== 1 ? 's' : '' ?></div>
          </div>

          <?php foreach ($majors as $majorKey => $students): ?>
            <div class="major-group" data-major="<?= htmlspecialchars($majorKey) ?>">
              <div class="major-label">
                <i class="fas fa-layer-group"></i>
                <?= strtoupper(htmlspecialchars($majorKey)) ?> — <?= htmlspecialchars($students[0]['major_name'] ?? 'Major') ?>
                <span style="margin-left:6px;background:var(--gold-d);color:#fff;border-radius:12px;padding:1px 8px;font-size:9px;"><?= count($students) ?></span>
              </div>
              <div class="grad-table-wrap">
                <table class="grad-table">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Student Name</th>
                      <th>Student ID</th>
                      <th>Major</th>
                      <th>Final GWA</th>
                      <th>Graduation Date</th>
                      <th>Status</th>
                      <th>PDF File</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($students as $i => $s): ?>
                      <?php
                        $fullName = trim(($s['first_name']??'').' '.($s['middle_name']??'').' '.($s['last_name']??'').' '.($s['suffix']??''));
                        $hasPDF   = !empty($s['pdf_path']);
                        $gwa      = $s['final_gwa'] ? number_format((float)$s['final_gwa'], 4) : '—';
                        $gradDate = $s['graduation_date'] ? date('F j, Y', strtotime($s['graduation_date'])) : '—';
                        $pdfName  = $s['pdf_path'] ? basename($s['pdf_path']) : '—';
                      ?>
                      <tr class="grad-row"
                          data-name="<?= htmlspecialchars(strtolower($fullName)) ?>"
                          data-id="<?= htmlspecialchars(strtolower($s['student_number']??$s['id'])) ?>"
                          data-major="<?= htmlspecialchars(strtolower($majorKey)) ?>"
                          data-batch="<?= htmlspecialchars($batch) ?>">
                        <td style="color:var(--muted);font-weight:600;"><?= $i+1 ?></td>
                        <td>
                          <div class="grad-name"><?= htmlspecialchars($fullName) ?></div>
                          <div class="grad-id"><?= htmlspecialchars($s['student_number'] ?? 'ID '.$s['id']) ?></div>
                        </td>
                        <td style="font-weight:600;color:var(--mid);"><?= htmlspecialchars($s['student_number'] ?? '—') ?></td>
                        <td style="font-weight:700;color:var(--gold-d);"><?= strtoupper(htmlspecialchars($majorKey)) ?></td>
                        <td><span class="gwa-chip"><?= htmlspecialchars($gwa) ?></span></td>
                        <td style="font-size:11px;color:var(--mid);"><?= htmlspecialchars($gradDate) ?></td>
                        <td><span class="status-grad"><i class="fas fa-check-circle"></i> Graduated</span></td>
                        <td>
                          <?php if ($hasPDF): ?>
                            <span class="pdf-badge generated"><i class="fas fa-file-pdf"></i> <?= htmlspecialchars(mb_strimwidth($pdfName, 0, 28, '…')) ?></span>
                          <?php else: ?>
                            <span class="pdf-badge"><i class="fas fa-file"></i> Not generated</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <button class="action-btn btn-pdf" onclick="openProspectus(<?= $s['id'] ?>,'<?= htmlspecialchars(addslashes($fullName)) ?>','<?= htmlspecialchars($batch) ?>','<?= strtoupper(htmlspecialchars($majorKey)) ?>')">
                              <i class="fas fa-eye"></i> View PDF
                            </button>
                            <button class="action-btn btn-pdf" style="background:linear-gradient(135deg,#dc2626,#b91c1c);" onclick="generatePDF(<?= $s['id'] ?>,'<?= htmlspecialchars(addslashes($fullName)) ?>','<?= htmlspecialchars($batch) ?>','<?= strtolower(htmlspecialchars($majorKey)) ?>')">
                              <i class="fas fa-file-pdf"></i> PDF
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div><!-- /.page-content -->
</div><!-- /.main-content -->

<!-- PDF PREVIEW MODAL -->
<div class="pdf-overlay" id="pdfOverlay">
  <div class="pdf-modal">
    <div class="pdf-modal-hdr">
      <h3 id="pdfModalTitle">Prospectus Preview</h3>
      <div style="display:flex;gap:8px;">
        <button class="close-btn" onclick="printProspectus()" title="Print"><i class="fas fa-print"></i></button>
        <button class="close-btn" onclick="closePdfModal()"><i class="fas fa-times"></i></button>
      </div>
    </div>
    <div class="pdf-modal-body" id="pdfModalBody">
      <!-- Prospectus content rendered here -->
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"><i class="fas fa-check-circle"></i><span id="toastMsg"></span></div>

<?php if($show_role_modal): ?>
<div style="position:fixed;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;z-index:99999;">
  <div style="background:#fff;border-radius:16px;padding:32px;max-width:360px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3);">
    <div style="width:80px;height:80px;border-radius:50%;background:rgba(220,38,38,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
      <i class="fas fa-exclamation-triangle" style="font-size:40px;color:#dc2626;"></i>
    </div>
    <h3 style="font-size:20px;font-weight:700;margin-bottom:12px;">Access Restricted</h3>
    <p style="font-size:14px;color:#6b7280;margin-bottom:20px;"><?= htmlspecialchars($role_access['message']??'No access.') ?></p>
    <a href="data/logout.php" style="background:#dc2626;color:#fff;padding:10px 20px;border-radius:10px;text-decoration:none;font-weight:500;">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</div>
<?php endif; ?>

<script>
// ── GRADUATE DATA (PHP → JS) ─────────────────────────────────────────────────
const graduateData = <?= json_encode($graduates, JSON_UNESCAPED_UNICODE) ?>;
const phSettings = {
  school_name:    'Northern Bukidnon State College',
  school_address: 'Manolo Fortich, Bukidnon',
  institute_name: 'Institute for Business Management',
  degree_name:    'Bachelor of Science in Business Administration'
};

// ── SEARCH & FILTER ──────────────────────────────────────────────────────────
function filterGraduates() {
  const q      = document.getElementById('gradSearch').value.toLowerCase().trim();
  const batch  = document.getElementById('batchFilter').value;
  const major  = document.getElementById('majorFilter').value;

  document.querySelectorAll('.grad-row').forEach(row => {
    const matchQ     = !q || row.dataset.name.includes(q) || row.dataset.id.includes(q) || row.dataset.major.includes(q);
    const matchBatch = batch === 'all' || row.dataset.batch === batch;
    const matchMajor = major === 'all' || row.dataset.major === major;
    row.style.display = matchQ && matchBatch && matchMajor ? '' : 'none';
  });

  // Hide empty batch sections
  document.querySelectorAll('.batch-section').forEach(sec => {
    const visible = [...sec.querySelectorAll('.grad-row')].some(r => r.style.display !== 'none');
    sec.style.display = visible ? '' : 'none';
  });
}

// ── BUILD PROSPECTUS HTML ────────────────────────────────────────────────────
function buildProspectusHTML(studentId, studentName, batch, major) {
  // This would typically fetch from server; using JS demo build here
  return `
  <div class="prospectus-sheet" id="prosPrint">
    <!-- School Header -->
    <div class="pros-school-hdr">
      <img src="media/LOGO.jpg" alt="Logo">
      <div class="pros-school-text">
        <div class="pros-school-name">${phSettings.school_name}</div>
        <div class="pros-school-addr">${phSettings.school_address}</div>
        <div class="pros-institute">${phSettings.institute_name}</div>
        <div class="pros-degree">${phSettings.degree_name}</div>
      </div>
    </div>

    <!-- Document Title -->
    <div class="pros-doc-title">OFFICIAL ACADEMIC PROSPECTUS</div>

    <!-- Graduated Badge -->
    <div style="text-align:center;margin-bottom:4mm;">
      <span class="pros-grad-badge"><i class="fas fa-graduation-cap"></i> GRADUATED — Batch ${batch}</span>
    </div>

    <!-- Student Info -->
    <div class="pros-info-grid">
      <div class="pros-info-row"><span class="pros-info-label">Student Name:</span> <strong>${studentName}</strong></div>
      <div class="pros-info-row"><span class="pros-info-label">Major:</span> ${major}</div>
      <div class="pros-info-row"><span class="pros-info-label">Student ID:</span> <span id="pros-sid-${studentId}">—</span></div>
      <div class="pros-info-row"><span class="pros-info-label">Graduation Date:</span> <span id="pros-gdate-${studentId}">—</span></div>
      <div class="pros-info-row"><span class="pros-info-label">Academic Year:</span> A.Y. ${batch}</div>
      <div class="pros-info-row"><span class="pros-info-label">Final GWA:</span> <strong id="pros-gwa-${studentId}" style="color:#8B6914;">—</strong></div>
    </div>

    <!-- Grades Table (fetched from API) -->
    <div id="pros-grades-${studentId}" style="margin-bottom:6mm;">
      <div style="text-align:center;padding:20px;color:#888;"><i class="fas fa-spinner fa-spin"></i> Loading grades…</div>
    </div>

    <!-- Summary -->
    <div class="pros-summary" id="pros-summary-${studentId}">
      <span>Loading summary…</span>
    </div>

    <!-- Signatures -->
    <div class="pros-sig-grid">
      <div class="pros-sig-col">
        <div class="pros-sig-line"></div>
        <div class="pros-sig-name" id="pros-advisor-${studentId}">Adviser</div>
        <div class="pros-sig-title">Academic Adviser</div>
        <div class="pros-sig-date">Date: _________________</div>
      </div>
      <div class="pros-sig-col">
        <div class="pros-sig-line"></div>
        <div class="pros-sig-name" id="pros-ph-${studentId}">Program Head</div>
        <div class="pros-sig-title">Program Head</div>
        <div class="pros-sig-date">Date: _________________</div>
      </div>
      <div class="pros-sig-col">
        <div class="pros-sig-line"></div>
        <div class="pros-sig-name">Registrar</div>
        <div class="pros-sig-title">College Registrar</div>
        <div class="pros-sig-date">Date: _________________</div>
      </div>
    </div>
  </div>`;
}

// ── OPEN PROSPECTUS MODAL ────────────────────────────────────────────────────
function openProspectus(studentId, studentName, batch, major) {
  document.getElementById('pdfModalTitle').textContent = `Prospectus — ${studentName}`;
  document.getElementById('pdfModalBody').innerHTML = buildProspectusHTML(studentId, studentName, batch, major);
  document.getElementById('pdfOverlay').classList.add('open');

  // Fetch detailed data from server
  fetchProspectusData(studentId);
}

function fetchProspectusData(studentId) {
  fetch(`data/evaluation_process.php?action=get_graduate_prospectus&student_id=${studentId}`)
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      const s = data.student || {};
      // Fill in student info
      const sid = document.getElementById(`pros-sid-${studentId}`);
      if (sid) sid.textContent = s.student_number || '—';
      const gdate = document.getElementById(`pros-gdate-${studentId}`);
      if (gdate) gdate.textContent = s.graduation_date || '—';
      const gwa = document.getElementById(`pros-gwa-${studentId}`);
      if (gwa) gwa.textContent = s.final_gwa ? parseFloat(s.final_gwa).toFixed(4) : '—';
      // Grades table
      const gradesDiv = document.getElementById(`pros-grades-${studentId}`);
      if (gradesDiv && data.grades) {
        gradesDiv.innerHTML = buildGradesTable(data.grades);
      }
      // Summary
      const sumDiv = document.getElementById(`pros-summary-${studentId}`);
      if (sumDiv) {
        const total = (data.grades||[]).length;
        const gwaStr = s.final_gwa ? parseFloat(s.final_gwa).toFixed(4) : '—';
        sumDiv.innerHTML = `<span>${total} subjects completed &nbsp;|&nbsp; All units passed</span><span>Final GWA: ${gwaStr}</span>`;
      }
      // Advisor names
      const adv = document.getElementById(`pros-advisor-${studentId}`);
      if (adv && data.advisor_name) adv.textContent = data.advisor_name;
      const ph = document.getElementById(`pros-ph-${studentId}`);
      if (ph && data.program_head_name) ph.textContent = data.program_head_name;
    })
    .catch(() => {
      // Server may not have the endpoint yet — silently fail, show template
    });
}

function buildGradesTable(grades) {
  if (!grades || !grades.length) return '<div style="text-align:center;padding:12px;color:#888;">No grade records found.</div>';

  // Group by year then semester
  const grouped = {};
  grades.forEach(g => {
    const yl = g.year_level || 'Unknown';
    const sm = g.semester || 'Unknown';
    if (!grouped[yl]) grouped[yl] = {};
    if (!grouped[yl][sm]) grouped[yl][sm] = [];
    grouped[yl][sm].push(g);
  });

  let html = `<table class="pros-table">
    <thead><tr>
      <th style="width:80pt;">Subject Code</th>
      <th>Subject Title</th>
      <th style="width:40pt;text-align:center;">Units</th>
      <th style="width:45pt;text-align:center;">Grade</th>
      <th style="width:55pt;text-align:center;">Status</th>
    </tr></thead>
    <tbody>`;

  const yearOrder = ['1st Year','2nd Year','3rd Year','4th Year'];
  yearOrder.forEach(yr => {
    if (!grouped[yr]) return;
    ['1st Semester','2nd Semester'].forEach(sm => {
      if (!grouped[yr][sm]) return;
      html += `<tr class="pros-year-hdr"><td colspan="5">${yr} — ${sm}</td></tr>`;
      let semUnits = 0, semPoints = 0;
      grouped[yr][sm].forEach(g => {
        const grade = g.grade ? parseFloat(g.grade) : null;
        const units = parseFloat(g.units) || 0;
        const isPassed = grade != null && grade <= 3.00;
        const gradeStr = grade ? grade.toFixed(2) : '—';
        const cls = grade ? (isPassed ? 'grade-pass' : 'grade-fail') : '';
        if (grade != null) { semUnits += units; semPoints += grade * units; }
        html += `<tr>
          <td style="font-weight:700;">${g.subject_code||'—'}</td>
          <td>${g.subject_name||'—'}</td>
          <td style="text-align:center;">${units}</td>
          <td style="text-align:center;" class="${cls}">${gradeStr}</td>
          <td style="text-align:center;font-size:8.5pt;" class="${cls}">${grade ? (isPassed ? 'Passed' : 'Failed') : '—'}</td>
        </tr>`;
      });
      if (semUnits > 0) {
        const semGWA = (semPoints / semUnits).toFixed(2);
        html += `<tr><td colspan="2" style="text-align:right;font-weight:bold;color:#8B6914;font-size:9pt;">Semester GWA:</td><td style="text-align:center;font-weight:800;color:#8B6914;">${semGWA}</td><td colspan="2"></td></tr>`;
      }
    });
  });

  html += '</tbody></table>';
  return html;
}

function closePdfModal() {
  document.getElementById('pdfOverlay').classList.remove('open');
}

// ── PRINT PROSPECTUS ─────────────────────────────────────────────────────────
function printProspectus() {
  const content = document.getElementById('pdfModalBody').innerHTML;
  const win = window.open('', '_blank');
  win.document.write(`<!DOCTYPE html><html><head>
    <title>Prospectus</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
      @page { size: A4 portrait; margin: 12mm; }
      body { margin:0; padding:0; background:#fff; }
      .prospectus-sheet { width:100%; padding:0; margin:0; font-family:'Times New Roman',Times,serif; font-size:10pt; color:#000; }
      .pros-school-hdr { display:flex; align-items:center; gap:12px; border-bottom:2.5pt solid #8B6914; padding-bottom:5mm; margin-bottom:5mm; }
      .pros-school-hdr img { width:50px; height:50px; object-fit:cover; border-radius:6px; border:2px solid #8B6914; }
      .pros-school-text { flex:1; }
      .pros-school-name { font-size:12pt; font-weight:bold; text-transform:uppercase; }
      .pros-school-addr { font-size:8.5pt; color:#555; font-style:italic; }
      .pros-institute { font-size:9.5pt; font-weight:bold; margin-top:2pt; }
      .pros-degree { font-size:8.5pt; }
      .pros-doc-title { text-align:center; font-size:12pt; font-weight:bold; letter-spacing:1pt; padding:4pt; margin:4mm 0; background:#8B6914; color:#fff; }
      .pros-grad-badge { display:inline-block; padding:3pt 12pt; background:#dcfce7; color:#166534; border:1pt solid #4ade80; border-radius:20pt; font-size:8.5pt; font-weight:bold; }
      .pros-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:3mm; border:1pt solid #ccc; border-radius:4pt; padding:4mm; margin-bottom:5mm; font-size:9pt; }
      .pros-info-row { display:flex; gap:4pt; }
      .pros-info-label { font-weight:bold; color:#8B6914; min-width:80pt; }
      .pros-table { width:100%; border-collapse:collapse; font-size:8.5pt; margin-bottom:4mm; }
      .pros-table th { background:#8B6914; color:#fff; padding:3pt 4pt; text-align:left; border:1pt solid #6b5209; font-weight:bold; }
      .pros-table td { padding:2.5pt 4pt; border:1pt solid #ccc; }
      .pros-table tr:nth-child(even) td { background:#fef9e7; }
      .pros-year-hdr td { background:#f0ece0; font-weight:bold; font-size:9pt; color:#5c4200; padding:3pt 4pt; border:1pt solid #c9b36c; }
      .grade-pass { color:#166534; font-weight:bold; }
      .grade-fail { color:#dc2626; font-weight:bold; }
      .pros-summary { display:flex; justify-content:space-between; padding:4pt 7pt; background:#8B6914; color:#fff; border-radius:3pt; font-weight:bold; font-size:9.5pt; margin-bottom:7mm; }
      .pros-sig-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8mm; margin-top:7mm; font-size:8.5pt; }
      .pros-sig-col { text-align:center; }
      .pros-sig-line { border-bottom:1pt solid #333; height:18mm; margin-bottom:2mm; }
      .pros-sig-name { font-weight:bold; font-size:9pt; }
      .pros-sig-title { color:#8B6914; font-weight:bold; }
      .pros-sig-date { color:#555; font-size:7.5pt; margin-top:1pt; }
    </style>
  </head><body>${content}</body></html>`);
  win.document.close();
  setTimeout(() => { win.print(); }, 400);
}

// ── GENERATE PDF ─────────────────────────────────────────────────────────────
function generatePDF(studentId, studentName, batch, major) {
  // Determine save path
  const safeFirst = studentName.split(' ')[0].toLowerCase().replace(/[^a-z0-9]/g,'_');
  const safeLast  = studentName.split(' ').pop().toLowerCase().replace(/[^a-z0-9]/g,'_');
  const majorLow  = major.toLowerCase();
  const filename  = `${safeFirst}_${safeLast}_${majorLow}_batch${batch}.pdf`;
  const dirPath   = `C:\\graduate\\batch ${batch}\\${majorLow}\\`;

  // Show progress toast
  showToast(`Generating PDF: ${filename}…`);

  // Notify server to record PDF path
  const fd = new FormData();
  fd.append('action', 'generate_pdf');
  fd.append('student_id', studentId);
  fetch('setup_graduate.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .catch(() => null)
    .finally(() => {
      // Open the prospectus then print (browser PDF save)
      openProspectus(studentId, studentName, batch, major);
      setTimeout(() => {
        showToast(`Save as: ${dirPath}${filename}`, 6000);
        printProspectus();
      }, 800);
    });
}

// ── PRINT ALL ────────────────────────────────────────────────────────────────
function printAllProspectuses() {
  if (!confirm('This will open print dialogs for all visible graduates. Continue?')) return;
  const rows = [...document.querySelectorAll('.grad-row')]
    .filter(r => r.style.display !== 'none');
  if (!rows.length) { showToast('No graduates to print.'); return; }
  showToast(`Preparing ${rows.length} prospectuses…`);
  // For now we just print the current filtered list view
  window.print();
}

// ── TOAST ────────────────────────────────────────────────────────────────────
function showToast(msg, duration = 3000) {
  const t = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), duration);
}

// Click outside to close PDF modal
document.getElementById('pdfOverlay').addEventListener('click', function(e) {
  if (e.target === this) closePdfModal();
});
</script>

</body>
</html>

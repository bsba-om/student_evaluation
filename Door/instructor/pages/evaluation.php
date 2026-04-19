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
<style>
/* ═══════════════════════════════════════════════════════════
   VARIABLES & RESET
═══════════════════════════════════════════════════════════ */
:root {
  --gold:#B8860B; --gold-l:#D4A843; --gold-d:#8B6914;
  --cream:#f7f5ef; --cream2:#ede9df; --white:#fff;
  --dark:#1a1a1a; --mid:#4b4b4b; --muted:#7a7a7a;
  --border:#d4cfc5; --border2:#c5bfb3;
  --green:#16a34a; --green-l:#dcfce7; --green-b:#86efac;
  --red:#dc2626; --red-l:#fee2e2; --red-b:#fca5a5;
  --amber:#d97706; --amber-l:#fef3c7; --amber-b:#fbbf24;
  --blue:#1d4ed8; --blue-l:#dbeafe; --blue-b:#93c5fd;
  --purple:#7c3aed; --purple-l:#f3e8ff;
  --radius:14px; --radius-sm:9px;
  --shadow:0 4px 20px rgba(0,0,0,.10);
  --shadow-lg:0 12px 48px rgba(0,0,0,.18);
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;background:var(--cream);overflow-x:hidden;}
.page-wrap{padding:16px 20px 24px;animation:fadeInUp .5s ease forwards;}
@keyframes fadeInUp{from{opacity:0;transform:translateY(15px);}to{opacity:1;transform:translateY(0);}}

/* ═══════════════════════════════════════════════════════════
   MENTEE GRID
═══════════════════════════════════════════════════════════ */
.mentee-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px;}
.mentee-card{
  background:var(--white);border-radius:var(--radius);border:1px solid var(--border);
  padding:0;cursor:pointer;transition:all .32s cubic-bezier(.23,1,.32,1);
  overflow:hidden;position:relative;
}
.mentee-card::before{
  content:'';position:absolute;top:0;left:0;right:0;height:4px;
  background:linear-gradient(90deg,var(--gold-l),var(--gold-d));
  transform:scaleX(0);transform-origin:left;
  transition:transform .32s cubic-bezier(.23,1,.32,1);
}
.mentee-card:hover{transform:translateY(-6px);box-shadow:0 20px 48px rgba(184,134,11,.2);}
.mentee-card:hover::before{transform:scaleX(1);}
.mc-top{
  padding:18px 18px 14px;display:flex;align-items:center;gap:14px;
  background:linear-gradient(135deg,#fffdf6,#fef9ed);
  border-bottom:1px solid var(--border);
}
.mc-avatar{
  width:52px;height:52px;border-radius:13px;
  display:flex;align-items:center;justify-content:center;
  font-size:17px;font-weight:800;color:#fff;flex-shrink:0;
  box-shadow:0 4px 12px rgba(0,0,0,.18);
  font-family:'Playfair Display',serif;letter-spacing:.5px;
  transition:transform .28s ease;
}
.mentee-card:hover .mc-avatar{transform:scale(1.05);}
.mc-name{font-size:14px;font-weight:700;color:var(--dark);line-height:1.3;}
.mc-sub{font-size:11px;color:var(--muted);margin-top:3px;}
.mc-bottom{padding:13px 18px 16px;}
.mc-pills{display:flex;gap:5px;flex-wrap:wrap;margin-bottom:12px;}
.pill{padding:3px 10px;border-radius:20px;font-size:10px;font-weight:600;white-space:nowrap;}
.pill-gold{background:var(--amber-l);color:#92400e;border:1px solid var(--amber-b);}
.pill-blue{background:var(--blue-l);color:#1e40af;border:1px solid var(--blue-b);}
.pill-green{background:var(--green-l);color:#166534;border:1px solid var(--green-b);}
.pill-gray{background:var(--cream2);color:var(--muted);border:1px solid var(--border);}
.pill-red{background:var(--red-l);color:#991b1b;border:1px solid var(--red-b);}
.mc-progress-track{background:var(--cream2);border-radius:20px;height:5px;overflow:hidden;margin-bottom:5px;}
.mc-progress-bar{height:100%;border-radius:20px;background:linear-gradient(to right,var(--gold-l),var(--gold-d));transition:width .6s ease;}
.mc-progress-label{display:flex;justify-content:space-between;font-size:10px;color:var(--muted);}
.mc-action{
  display:flex;align-items:center;justify-content:center;gap:7px;
  padding:10px;background:linear-gradient(135deg,var(--gold-d),var(--gold));
  color:#fff;font-size:12px;font-weight:600;border-top:1px solid rgba(0,0,0,.06);
  transition:opacity .2s;
}
.mentee-card:hover .mc-action{opacity:.88;}

/* ═══════════════════════════════════════════════════════════
   SEARCH / CONTROLS
═══════════════════════════════════════════════════════════ */
.search-wrap{
  display:flex;align-items:center;gap:9px;
  padding:10px 14px;background:var(--white);
  border-radius:var(--radius-sm);border:1.5px solid var(--border);
  transition:all .25s cubic-bezier(.23,1,.32,1);
}
.search-wrap:focus-within{border-color:var(--gold);box-shadow:0 0 0 3px rgba(184,134,11,.15);}
.search-wrap i{color:var(--muted);font-size:13px;}
 .search-wrap input{border:none;background:transparent;font-family:'Poppins',sans-serif;font-size:13px;color:var(--dark);flex:1;outline:none;}
 .search-wrap input::placeholder{color:rgba(255,255,255,.7);}
 .hero-eyebrow{display:flex;align-items:center;gap:10px;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#fff;margin-bottom:10px;}
 .hero-eyebrow span{width:32px;height:2px;background:#fff;border-radius:2px;}
 .hero-title{font-family:'Playfair Display',serif;font-size:38px;font-weight:800;color:#fff;line-height:1.1;margin-bottom:8px;}
 .hero-title em{color:#2d1f07;font-style:normal;}
 .hero-sub{font-size:14px;color:rgba(255,255,255,.85);max-width:360px;}
 
 /* Enhanced Search Bar for Hero */
 .hero-search{
   display:flex;align-items:center;gap:8px;
   padding:10px 14px;
   background:rgba(255,255,255,0.18);
   border:1.5px solid rgba(255,255,255,0.25);
   border-radius:12px;
   backdrop-filter:blur(8px);
   transition:all .3s ease;
   min-width:180px;
 }
 .hero-search:hover{background:rgba(255,255,255,0.25);border-color:rgba(255,255,255,0.4);}
 .hero-search:focus-within{background:rgba(255,255,255,0.3);border-color:var(--gold);box-shadow:0 0 0 3px rgba(212,168,67,0.25);}
 .hero-search i{color:rgba(255,255,255,0.8);font-size:13px;}
 .hero-search input{
   border:none;background:transparent;
   font-family:'Poppins',sans-serif;font-size:12px;font-weight:500;
   color:#fff;flex:1;outline:none;
   width:120px;
 }
 .hero-search input::placeholder{color:rgba(255,255,255,0.7);}
 .hero-search-btn{
   padding:8px 14px;background:var(--gold);color:#fff;border:none;
   border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;
   transition:all .25s ease;display:flex;align-items:center;gap:6px;
 }
 .hero-search-btn:hover{background:#b8922f;transform:translateY(-1px);box-shadow:0 4px 12px rgba(184,134,11,0.35);}
 .year-btn{padding:8px 14px;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);border-radius:8px;color:rgba(255,255,255,0.8);font-size:11px;font-weight:600;cursor:pointer;transition:all .2s;}
 .year-btn:hover{background:rgba(255,255,255,0.25);border-color:rgba(255,255,255,0.4);color:#fff;}
 .year-btn.active{background:#fff;color:#b8922f;border-color:#fff;font-weight:700;}
.ay-badge{
  padding:8px 14px;background:var(--cream2);border-radius:var(--radius-sm);
  border:1px solid var(--border);font-size:12px;font-weight:600;color:var(--gold-d);
  transition:all .2s;
}
.ay-badge:hover{background:var(--amber-l);border-color:var(--amber-b);}

/* ═══════════════════════════════════════════════════════════
   EVAL OVERLAY
═══════════════════════════════════════════════════════════ */
.overlay{
  position:fixed;inset:0;background:rgba(10,8,5,.72);
  z-index:9900;display:none;align-items:flex-start;
  justify-content:center;overflow-y:auto;padding:14px;
  backdrop-filter:blur(3px);
}
.overlay.open{display:flex;}
.eval-panel{
  background:var(--white);border-radius:22px;
  width:100%;max-width:1200px;
  box-shadow:0 32px 80px rgba(0,0,0,.35);
  display:flex;flex-direction:column;
  min-height:min(96vh,860px);overflow:hidden;
  margin:auto;
  border:1px solid rgba(184,134,11,.15);
}

/* eval header */
.eval-hdr{
  background:linear-gradient(135deg,var(--gold-d) 0%,#a87120 50%,var(--gold-l) 100%);
  padding:18px 24px;color:#fff;
  display:flex;align-items:center;justify-content:space-between;
  flex-wrap:wrap;gap:12px;flex-shrink:0;
  position:relative;overflow:hidden;
}
.eval-hdr::before{
  content:'';position:absolute;top:-40px;right:-60px;
  width:220px;height:220px;border-radius:50%;
  background:rgba(255,255,255,.06);pointer-events:none;
}
.eval-hdr::after{
  content:'';position:absolute;bottom:0;left:0;right:0;height:1px;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.3),transparent);
}
.eval-hdr-name{font-size:17px;font-weight:700;font-family:'Playfair Display',serif;}
.eval-hdr-sub{font-size:11px;opacity:.82;margin-top:2px;}
.eval-hdr-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.hdr-btn{
  padding:9px 16px;border:1.5px solid rgba(255,255,255,.4);
  border-radius:10px;background:rgba(255,255,255,.14);color:#fff;
  cursor:pointer;font-size:12px;font-weight:600;font-family:'Poppins',sans-serif;
  display:inline-flex;align-items:center;gap:6px;transition:all .25s cubic-bezier(.23,1,.32,1);
}
.hdr-btn:hover{background:rgba(255,255,255,.28);transform:translateY(-1px);}
.hdr-btn-solid{background:rgba(255,255,255,.98);color:var(--gold-d);border-color:rgba(255,255,255,.6);}
.hdr-btn-solid:hover{background:#fff;box-shadow:0 4px 16px rgba(0,0,0,.15);}
.hdr-close{
  width:36px;height:36px;border:none;background:rgba(255,255,255,.18);
  border-radius:10px;cursor:pointer;font-size:15px;color:#fff;
  display:flex;align-items:center;justify-content:center;transition:all .25s cubic-bezier(.23,1,.32,1);
}
.hdr-close:hover{background:rgba(255,255,255,.32);transform:rotate(90deg);}

/* eval tabs - modern pill style */
.eval-tabs{
  display:flex;background:var(--cream);border-bottom:2px solid var(--border);
  padding:14px 22px 0;flex-shrink:0;gap:8px;
}
.eval-tab{
  padding:10px 20px;border:none;background:transparent;
  font-family:'Poppins',sans-serif;font-size:13px;font-weight:500;
  color:var(--muted);cursor:pointer;border-radius:24px;
  display:flex;align-items:center;gap:7px;transition:all .25s cubic-bezier(.23,1,.32,1);
  position:relative;
}
.eval-tab::after{
  content:'';position:absolute;bottom:-14px;left:50%;transform:translateX(-50%);
  width:0;height:3px;background:var(--gold);border-radius:3px 3px 0 0;
  transition:width .25s cubic-bezier(.23,1,.32,1);
}
.eval-tab:hover{color:var(--dark);background:rgba(184,134,11,.06);}
.eval-tab.active{
  color:var(--gold-d);background:rgba(184,134,11,.1);font-weight:700;
  box-shadow:0 2px 8px rgba(184,134,11,.15);
}
.eval-tab.active::after{width:60%;}

.eval-body{padding:20px 24px 24px;flex:1;overflow-y:auto;}

/* ═══════════════════════════════════════════════════════════
    GWA STRIP
 ═══════════════════════════════════════════════════════════ */
.gwa-strip{
  display:flex;gap:12px;flex-wrap:wrap;align-items:center;
  padding:16px 20px;background:linear-gradient(135deg,#fffdf6,#fef9ed);
  border:1px solid var(--border);border-radius:var(--radius);margin-bottom:20px;
  box-shadow:0 2px 10px rgba(0,0,0,.05);
}
.gwa-main{
  background:linear-gradient(135deg,var(--gold-d),var(--gold));
  border-radius:12px;padding:14px 22px;color:#fff;text-align:center;min-width:115px;
  box-shadow:0 4px 16px rgba(139,105,20,.35),0 0 0 1px rgba(139,105,20,.1);
  position:relative;overflow:hidden;
}
.gwa-main::after{
  content:'';position:absolute;top:-50%;right:-50%;width:100%;height:100%;
  background:radial-gradient(circle,rgba(255,255,255,.2) 0%,transparent 70%);
}
.gwa-val{font-size:26px;font-weight:800;font-family:'Playfair Display',serif;line-height:1;}
.gwa-lbl{font-size:9px;opacity:.85;margin-top:2px;text-transform:uppercase;letter-spacing:.5px;}
.gwa-stat{
  background:var(--white);border-radius:11px;padding:12px 16px;
  text-align:center;border:1px solid var(--border);min-width:90px;
}
.gwa-stat-val{font-size:20px;font-weight:700;color:var(--dark);}
.gwa-stat-lbl{font-size:9px;color:var(--muted);margin-top:2px;text-transform:uppercase;letter-spacing:.3px;}
.gwa-hint{
  margin-left:auto;font-size:11px;color:var(--muted);
  background:var(--cream);border-radius:8px;padding:8px 12px;border:1px solid var(--border);
  line-height:1.6;
}

/* Student Info Strip */
.student-info-strip{
  display:flex;justify-content:flex-start;align-items:center;flex-wrap:wrap;gap:10px;
  padding:12px 16px;background:linear-gradient(135deg,#fffdf6,#fef9ed);
  border:1px solid var(--border);border-radius:var(--radius);margin-bottom:18px;
  box-shadow:0 2px 10px rgba(0,0,0,.06);
  position:relative;overflow:hidden;
}
.student-info-strip::before{
  content:'';position:absolute;top:0;left:0;right:0;height:3px;
  background:linear-gradient(90deg,var(--gold-d),var(--gold-l),var(--gold-d));
}
.si-item{display:flex;flex-direction:column;gap:2px;padding:4px 10px;background:var(--white);border-radius:6px;border:1px solid var(--border);flex:1;min-width:100px;}
.si-item:hover{background:var(--amber-l);}
.si-label{font-size:9px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;font-weight:600;white-space:nowrap;}
.si-value{font-size:12px;color:var(--dark);font-weight:700;}
@media(max-width:768px){.student-info-strip{flex-direction:column;align-items:stretch;}.si-item{flex:none;width:100%;}}

/* Hide print version in screen mode */
.student-info-strip-print{display:none;}

/* ═══════════════════════════════════════════════════════════
   PROSPECTUS — mirrors department page exactly
═══════════════════════════════════════════════════════════ */
.pro-wrap{
  font-family:'Poppins',sans-serif;font-size:12px;color:var(--dark);
  background:var(--white);border-radius:var(--radius);border:1px solid var(--border);
  overflow:hidden;box-shadow:var(--shadow);
}
.pro-hdr{
  display:flex;align-items:center;justify-content:space-between;
  padding:16px 20px 13px;
  background:linear-gradient(to bottom,#fffdf5,#fff);
  border-bottom:3px solid var(--gold-d);
}
.pro-logo{
  width:74px;height:74px;object-fit:cover;border-radius:10px;
  border:2px solid var(--gold-d);flex-shrink:0;
}
.pro-title-block{text-align:center;flex:1;padding:0 12px;}
.pro-school{font-size:14px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;font-family:'Playfair Display',serif;}
.pro-address{font-size:10px;color:var(--muted);margin:2px 0;}
.pro-institute{font-size:11px;font-weight:700;color:var(--gold-d);text-transform:uppercase;margin-top:4px;letter-spacing:.3px;}
.pro-degree{font-size:10px;color:#444;margin:2px 0;}
.pro-major-line{font-size:11px;font-weight:600;margin:2px 0;}
.pro-student-line{font-size:10px;color:var(--mid);margin-top:3px;}
.pro-label{
  display:inline-block;margin-top:5px;padding:2px 12px;
  border:1.5px solid var(--gold-d);border-radius:20px;
  font-size:9px;font-weight:700;color:var(--gold-d);
  letter-spacing:.5px;text-transform:uppercase;
}
.pro-body{padding:12px 14px 14px;}
.pro-year-block{
  margin-bottom:12px;border:1px solid #e0dbd0;border-radius:10px;overflow:hidden;
}
.pro-year-hdr{
  background:linear-gradient(135deg,var(--gold-d),var(--gold));
  color:#fff;padding:8px 14px;font-size:12px;font-weight:700;
  display:flex;justify-content:space-between;align-items:center;
}
.pro-year-total{font-size:10px;font-weight:400;opacity:.85;}
.pro-sem-row{display:grid;grid-template-columns:1fr 1fr;padding:8px 10px 10px;gap:10px;}
.pro-sem-label{
  font-size:10px;font-weight:700;color:var(--gold-d);text-align:center;
  padding:4px 0;background:#f7f5ef;border:1px solid var(--border);
  border-radius:5px 5px 0 0;text-transform:uppercase;letter-spacing:.3px;
}
.pro-table{width:100%;border-collapse:collapse;font-size:11px;}
.pro-th{
  background:#f0ece0;padding:5px 7px;text-align:left;
  font-size:9.5px;font-weight:700;color:var(--gold-d);
  border:1px solid #ccc;white-space:nowrap;
}
.pro-table td{border:1px solid #ddd;padding:4px 7px;vertical-align:middle;}
.pro-table tr:not(.pro-total-row):hover td{background:#fdfbf6;}
.pro-code{font-weight:700;white-space:nowrap;font-size:10px;}
.pro-units{text-align:center;font-weight:600;white-space:nowrap;}
.pro-prereq-col{color:#888;font-size:9.5px;white-space:nowrap;}
.pro-total-row td{
  background:#f0ece0;font-weight:700;color:var(--gold-d);
  border-top:2px solid var(--gold);font-size:10px;
}
.pro-empty{text-align:center;color:#aaa;font-style:italic;padding:10px;font-size:10px;}
.pro-grand-total{
  text-align:right;font-size:12px;font-weight:700;
  padding:7px 14px;background:#f7f5ef;
  border:1px solid var(--border);border-radius:7px;margin:0 0 12px;
}
.pro-sig-block{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;padding:14px 0 0;border-top:2px solid var(--border);}
.pro-sig-col{text-align:center;}
.pro-sig-line{border-bottom:1.5px solid #333;margin-bottom:5px;height:24px;}
.pro-sig-lbl{font-size:10px;font-weight:600;color:#333;}
.pro-sig-sub{font-size:9px;color:#888;margin-top:2px;}
.pro-legend{font-size:9.5px;color:#999;padding:6px 0;margin-top:6px;}
.pro-star{color:var(--red);font-weight:700;}
.pro-bridging-block{margin-bottom:12px;}

/* Grade cell */
.grade-cell-wrap{display:flex;flex-direction:column;align-items:center;gap:2px;}
.grade-row{display:flex;align-items:center;gap:3px;}
.grade-inp{
  width:52px;padding:4px 5px;border:1.5px solid var(--border);
  border-radius:6px;font-family:'Poppins',sans-serif;font-size:11px;
  font-weight:700;text-align:center;transition:all .25s cubic-bezier(.23,1,.32,1);background:#fafaf8;
}
.grade-inp:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px rgba(184,134,11,.2);}
.grade-inp:hover:not(:focus){border-color:var(--gold-l);}
.grade-inp.gp{border-color:var(--green);background:#f0fdf4;}
.grade-inp.gf{border-color:var(--red);background:#fef2f2;}
.grade-inp.gc{border-color:var(--amber);background:#fffbeb;}
.save-btn{
  width:22px;height:22px;border:none;border-radius:6px;cursor:pointer;
  background:var(--blue-l);color:var(--blue);font-size:9px;
  display:flex;align-items:center;justify-content:center;transition:all .25s cubic-bezier(.23,1,.32,1);
}
.save-btn:hover{background:var(--blue);color:#fff;transform:scale(1.1);}
.save-btn.saved{background:var(--green-l);color:var(--green);}
.save-btn.saved:hover{background:var(--green);color:#fff;}
.grade-hint{font-size:8px;color:var(--muted);text-align:center;max-width:54px;line-height:1.2;}

/* status pills */
.gpill{padding:2px 5px;border-radius:4px;font-size:8px;font-weight:700;}
.gpill.gp{background:var(--green-l);color:#166534;}
.gpill.gf{background:var(--red-l);color:#991b1b;}
.gpill.gc{background:var(--amber-l);color:#92400e;}
.gpill.gn{background:var(--cream2);color:var(--muted);}

/* locked row */
.row-locked td{background:#fffbeb !important;}
.row-locked .grade-inp{pointer-events:none;background:var(--amber-l);border-color:var(--amber-b);opacity:.8;}
.row-locked .save-btn{pointer-events:none;opacity:.35;}
.lock-badge{
  display:inline-flex;align-items:center;gap:3px;font-size:8px;
  padding:2px 5px;background:var(--amber-l);color:#92400e;
  border-radius:4px;border:1px solid var(--amber-b);white-space:nowrap;
}

/* prereq-blocked row (subject not yet passed from prereq set) */
.row-prereqblocked td{background:#fff8f0 !important;opacity:.9;}

/* ═══════════════════════════════════════════════════════════
   ADVISEMENT PANEL
═══════════════════════════════════════════════════════════ */
.adv-panel{padding:4px 0;}
.summary-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:12px;margin-bottom:20px;}
.sum-card{
  background:var(--white);border-radius:14px;padding:16px;
  text-align:center;border:1px solid var(--border);
  box-shadow:0 2px 8px rgba(0,0,0,.06);transition:all .28s cubic-bezier(.23,1,.32,1);
  position:relative;overflow:hidden;
}
.sum-card::before{
  content:'';position:absolute;top:0;left:0;right:0;height:3px;
  transform:scaleX(0);transform-origin:left;
  transition:transform .28s cubic-bezier(.23,1,.32,1);
}
.sum-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.12);}
.sum-card:hover::before{transform:scaleX(1);}
.sum-rec::before{background:var(--green);}
.sum-fail::before{background:var(--red);}
.sum-cond::before{background:var(--amber);}
.sum-block::before{background:#64748b;}
.sum-done::before{background:var(--blue);}
.sum-num{font-size:28px;font-weight:800;font-family:'Playfair Display',serif;line-height:1;}
.sum-lbl{font-size:10px;color:var(--muted);margin-top:4px;text-transform:uppercase;letter-spacing:.4px;}
.sum-rec .sum-num{color:var(--green);}
.sum-fail .sum-num{color:var(--red);}
.sum-cond .sum-num{color:var(--amber);}
.sum-block .sum-num{color:#64748b;}
.sum-done .sum-num{color:var(--blue);}

.context-banner{
  background:linear-gradient(135deg,#eff6ff,#dbeafe);
  border-radius:var(--radius-sm);padding:15px 20px;margin-bottom:20px;
  border:1px solid var(--blue-b);
  box-shadow:0 2px 8px rgba(29,78,216,.1);
}
.context-title{font-size:14px;font-weight:700;color:#1e40af;margin-bottom:4px;}
.context-sub{font-size:12px;color:#1d4ed8;}

.adv-section{margin-bottom:24px;}
.adv-sec-title{
  font-size:13px;font-weight:700;margin-bottom:12px;
  display:flex;align-items:center;gap:8px;
  padding:10px 16px;border-radius:10px;
  box-shadow:0 1px 3px rgba(0,0,0,.05);
}
.ast-green{background:var(--green-l);color:#166534;border:1px solid var(--green-b);}
.ast-red{background:var(--red-l);color:#991b1b;border:1px solid var(--red-b);}
.ast-amber{background:var(--amber-l);color:#92400e;border:1px solid var(--amber-b);}
.ast-slate{background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;}
.ast-blue{background:var(--blue-l);color:#1e40af;border:1px solid var(--blue-b);}

.adv-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(215px,1fr));gap:10px;}
.adv-card{
  border-radius:12px;padding:14px;border:1px solid var(--border);
  background:var(--white);transition:all .28s cubic-bezier(.23,1,.32,1);
}
.adv-card:hover{box-shadow:0 8px 24px rgba(0,0,0,.1);transform:translateY(-2px);}
.adv-card.ac-rec{border-left:4px solid var(--green);}
.adv-card.ac-fail{border-left:4px solid var(--red);}
.adv-card.ac-cond{border-left:4px solid var(--amber);}
.adv-card.ac-block{border-left:4px solid #94a3b8;}
.adv-card.ac-done{border-left:4px solid var(--blue);}
.adv-code{font-size:13px;font-weight:700;color:var(--dark);}
.adv-name{font-size:11px;color:var(--muted);margin-top:2px;line-height:1.4;}
.adv-meta{font-size:9.5px;color:var(--muted);margin-top:3px;}
.adv-reason{font-size:10px;margin-top:7px;padding:4px 8px;border-radius:6px;font-weight:600;}
.ar-rec{background:var(--green-l);color:#166534;}
.ar-fail{background:var(--red-l);color:#991b1b;}
.ar-cond{background:var(--amber-l);color:#92400e;}
.ar-block{background:#f1f5f9;color:#475569;}
.ar-done{background:var(--blue-l);color:#1e40af;}
.adv-chain{margin-top:7px;font-size:9px;color:#6b7280;line-height:1.7;border-top:1px solid #f0ece4;padding-top:5px;}
.adv-chain strong{color:var(--gold-d);}
.unlock-tag{
  display:inline-flex;align-items:center;gap:3px;
  font-size:9px;padding:2px 6px;background:#eff6ff;
  color:var(--blue);border-radius:10px;border:1px solid var(--blue-b);margin:1px;
}
.block-prereq{
  display:inline-flex;align-items:center;gap:4px;
  font-size:9px;padding:3px 7px;background:#f1f5f9;
  color:#475569;border-radius:5px;border:1px solid #cbd5e1;
}
.grade-badge{
  display:inline-flex;align-items:center;gap:3px;
  margin-top:5px;font-size:9.5px;padding:3px 8px;border-radius:12px;font-weight:700;
}
.gb-pass{background:var(--green-l);color:#166534;border:1px solid var(--green-b);}
.gb-fail{background:var(--red-l);color:#991b1b;border:1px solid var(--red-b);}
.gb-cond{background:var(--amber-l);color:#92400e;border:1px solid var(--amber-b);}

/* ═══════════════════════════════════════════════════════════
   SESSION NOTES
═══════════════════════════════════════════════════════════ */
.session-bar{
  background:var(--cream);border-radius:var(--radius);padding:16px 18px;
  border:1px solid var(--border);margin-top:20px;
}
.session-bar textarea{
  width:100%;padding:9px 12px;border:1.5px solid var(--border);
  border-radius:8px;font-family:'Poppins',sans-serif;font-size:12px;
  resize:vertical;min-height:56px;background:var(--white);
}
.session-bar textarea:focus{outline:none;border-color:var(--gold);}

/* ═══════════════════════════════════════════════════════════
    BUTTONS
 ═══════════════════════════════════════════════════════════ */
.btn{
  padding:10px 18px;border:none;border-radius:10px;cursor:pointer;
  font-weight:600;font-size:13px;font-family:'Poppins',sans-serif;
  display:inline-flex;align-items:center;gap:8px;transition:all .28s cubic-bezier(.23,1,.32,1);
}
.btn-gold{background:linear-gradient(135deg,var(--gold-l),var(--gold-d));color:#fff;box-shadow:0 2px 8px rgba(139,105,20,.25);}
.btn-gold:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(139,105,20,.4);}
.btn-green{background:var(--green);color:#fff;}
.btn-green:hover{background:#15803d;transform:translateY(-2px);}
.btn-blue{background:var(--blue);color:#fff;}
.btn-blue:hover{background:#1e40af;transform:translateY(-2px);}

/* ═══════════════════════════════════════════════════════════
    TOAST
 ═══════════════════════════════════════════════════════════ */
.toast{
  position:fixed;bottom:28px;right:28px;
  background:var(--white);color:var(--dark);
  padding:16px 22px;border-radius:14px;font-size:14px;font-weight:500;
  display:flex;align-items:center;gap:12px;
  transform:translateY(120px);opacity:0;transition:all .4s cubic-bezier(.23,1,.32,1);
  z-index:99999;box-shadow:0 12px 40px rgba(0,0,0,.2);max-width:380px;
  border-left:4px solid var(--gold);
}
.toast.show{transform:translateY(0);opacity:1;}
.toast.success{border-left-color:var(--green);}
.toast.error{border-left-color:var(--red);}
.toast.info{border-left-color:var(--amber);}
.toast-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.toast.success .toast-icon{background:var(--green-l);color:var(--green);}
.toast.error .toast-icon{background:var(--red-l);color:var(--red);}
.toast.info .toast-icon{background:var(--amber-l);color:var(--amber);}

/* ═══════════════════════════════════════════════════════════
   MISC
═══════════════════════════════════════════════════════════ */
.spinner{display:inline-block;width:22px;height:22px;border:3px solid rgba(184,134,11,.2);border-top-color:var(--gold-d);border-radius:50%;animation:spin .7s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}
.empty-state{text-align:center;padding:52px 24px;color:var(--muted);}
.empty-state i{font-size:44px;opacity:.18;display:block;margin-bottom:14px;}
.empty-state h3{font-size:15px;font-weight:700;color:var(--dark);margin-bottom:5px;}
.empty-state{animation:fadeInUp .5s ease forwards;}
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
.divider{border:none;border-top:1px solid var(--border);margin:16px 0;}
.card{background:var(--white);border-radius:var(--radius);padding:24px;box-shadow:var(--shadow);border:1px solid var(--border);margin-bottom:20px;}

/* ═══════════════════════════════════════════════════════════
   PREREQ SET DISPLAY IN PROSPECTUS
═══════════════════════════════════════════════════════════ */
.prereq-chain-info{
  display:inline-flex;align-items:center;gap:3px;
  font-size:8.5px;padding:2px 6px;background:var(--red-l);
  color:#991b1b;border-radius:4px;border:1px solid var(--red-b);
  white-space:nowrap;margin-top:2px;
}

/* ═══════════════════════════════════════════════════════════
   PRINT  ─ LONG BOND / LEGAL  (216 × 356mm)  single page
═══════════════════════════════════════════════════════════ */
@media print {

  /* ─ Page setup ─ */
  @page {
    size: A4 portrait;
    margin: 5mm;
  }

  /* ─ Hide everything except the print target ─ */
  body > * { display: none !important; }
  #printTarget { display: block !important; }

  html, body {
    margin: 0 !important; padding: 0 !important;
    width: 100% !important; background: white !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  #printTarget {
    width: 100% !important; max-width: 210mm !important; margin: 0 !important;
    position: static !important;
  }

  /* ─ Pro-wrap ─ */
  .pro-wrap {
    width: 100% !important; max-width: 200mm !important;
    border: none !important; box-shadow: none !important;
    border-radius: 0 !important; background: white !important;
    font-size: 7pt !important;
    font-family: 'Times New Roman', Times, serif !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  /* Header - bigger for A4 */
  .pro-hdr {
    display: flex !important; flex-direction: row !important;
    align-items: center !important; justify-content: space-between !important;
    padding: 3mm 3mm 2mm !important;
    border-top: 2.5pt solid #8B6914 !important;
    border-bottom: 1.5pt solid #8B6914 !important;
    background: white !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    width: 100% !important;
  }
  .pro-logo { width: 22mm !important; height: 22mm !important; border: 1.5pt solid #8B6914 !important; border-radius: 2pt !important; flex-shrink: 0 !important; }
  .pro-title-block { flex: 1 1 auto !important; text-align: center !important; padding: 0 3mm !important; }
  .pro-school { font-size: 14pt !important; font-weight: 700 !important; font-family: 'Times New Roman',serif !important; }
  .pro-address { font-size: 9pt !important; color: #555 !important; font-style: italic !important; }
  .pro-institute { font-size: 11pt !important; font-weight: 700 !important; color: #8B6914 !important; }
  .pro-degree { font-size: 9pt !important; }
  .pro-major-line { font-size: 10pt !important; font-weight: 700 !important; }
  .pro-student-line { font-size: 9pt !important; color: #333 !important; }
  .pro-label { font-size: 8pt !important; padding: 1.5pt 8pt !important; border: 1pt solid #8B6914 !important; color: #8B6914 !important; }

  /* Print student info strip */
  .student-info-strip-print { display: flex !important; justify-content: space-between !important; padding: 1.5mm 2mm !important; background: #fafafa !important; border: 0.5pt solid #ccc !important; margin-bottom: 1mm !important; }
  .sip-item { display: flex !important; gap: 2mm !important; }
  .sip-label { font-size: 9pt !important; font-weight: 700 !important; color: #333 !important; }
  .sip-value { font-size: 9pt !important; }
  
  /* Show student info strip in print, hide interactive elements */
  .gwa-strip, .student-info-strip, .session-bar, .eval-hdr, .eval-tabs { display: none !important; }
  .student-info-strip-print { display: flex !important; }

  /* Body */
  .pro-body { padding: 1mm 2mm 0 !important; overflow: visible !important; width: 100% !important; }

/* Year blocks - compact */
  .pro-year-block {
    margin-bottom: 1mm !important; border: 0.3pt solid #bbb !important;
    border-radius: 0 !important; overflow: hidden !important;
    page-break-inside: avoid !important; break-inside: avoid !important;
    width: 100% !important;
  }
  .pro-year-hdr {
    padding: 1mm 1.5mm !important; font-size: 6.5pt !important; font-weight: 700 !important;
    background: #8B6914 !important; color: white !important;
    width: 100% !important;
  }
  .pro-sem-label {
    font-size: 6.5pt !important; font-weight: 700 !important; padding: 0.8pt 0 !important;
    background: #fde68a !important; border: 0.3pt solid #d4cfc5 !important;
    display: block !important; width: 100% !important;
  }
  .pro-year-hdr {
    padding: 1mm 1.5mm !important; font-size: 6.5pt !important; font-weight: 700 !important;
    background: #8B6914 !important; color: white !important;
  }
  .pro-sem-row { display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 2mm !important; padding: 1.5mm !important; background: white !important; width: 100% !important; }
  .pro-sem-label {
    font-size: 6.5pt !important; font-weight: 700 !important; padding: 0.8pt 0 !important;
    background: #fde68a !important; border: 0.3pt solid #d4cfc5 !important;
  }

  /* Tables - wider columns */
  .pro-table { font-size: 6pt !important; table-layout: auto !important; border-collapse: collapse !important; font-family: 'Times New Roman',Times,serif !important; page-break-inside: avoid !important; width: 100% !important; }
  .pro-th { background: #f0ece0 !important; padding: 0.8pt 1.5pt !important; font-size: 6pt !important; font-weight: 700 !important; color: #7a5c10 !important; border: 0.3pt solid #ccc !important; white-space: nowrap; }
  .pro-table td { border: 0.3pt solid #ddd !important; padding: 0.8pt 1.5pt !important; font-size: 6pt !important; line-height: 1.3 !important; }
  .pro-code { font-size: 6pt !important; font-weight: 700 !important; white-space: nowrap !important; }
  .pro-units { font-size: 6pt !important; text-align: center !important; white-space: nowrap; }
  .pro-prereq-col { font-size: 5.5pt !important; white-space: nowrap; }

  /* Hide input elements, show grade value */
  .grade-inp, .save-btn, .grade-hint, .lock-badge, .prereq-chain-info { display: none !important; }
  .grade-print { display: inline-block !important; font-size: 6pt !important; font-weight: 700 !important; }
  .pro-td-status, .pro-th-status { display: none !important; }
  .gpill { font-size: 5pt !important; padding: 0.3pt 1pt !important; }

  .row-locked td { background: #fffbeb !important; }
  .pro-total-row td { background: #f0ece0 !important; font-weight: 700 !important; color: #8B6914 !important; border-top: 0.5pt solid #B8860B !important; font-size: 6pt !important; }
  .pro-empty { font-size: 5pt !important; }

  /* Bridging */
  .pro-bridging-block { margin-bottom: 1mm !important; page-break-inside: avoid !important; }

  /* Grand total - compact */
  .pro-grand-total { font-size: 7pt !important; font-weight: 700 !important; text-align: right !important; padding: 1mm 2mm !important; margin: 1mm 0 !important; background: #f0ece0 !important; border-top: 0.5pt solid #B8860B !important; color: #8B6914 !important; }

  /* Sig block - compact */
  .pro-sig-block { display: grid !important; grid-template-columns: repeat(3,1fr) !important; gap: 5mm !important; padding: 1mm 0 0 !important; border-top: 0.5pt solid #aaa !important; margin-top: 1mm !important; page-break-inside: avoid !important; }
  .pro-sig-line { border-bottom: 0.5pt solid #333 !important; height: 6mm !important; margin-bottom: 0.5mm !important; }
  .pro-sig-lbl { font-size: 6pt !important; font-weight: 700 !important; }
  .pro-sig-sub { font-size: 5.5pt !important; color: #888 !important; }
  .pro-legend { font-size: 5pt !important; color: #999 !important; margin-top: 0.5mm !important; }
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
      <div class="topbar-date"><i class="fas fa-calendar-alt"></i><span><?php echo date('F j, Y'); ?></span></div>
      <a href="../../../data/logout.php" class="topbar-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
  </header>

  <main class="dashboard-content">
    <div style="position: fixed; top: 0; left: 260px; right: 0; bottom: 0; background-image: url('../../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
    <div class="page-wrap">

      <!-- HERO BANNER -->
      <div class="hero-banner" style="background: linear-gradient(135deg, #d4a843 0%, #b8922f 40%, #a38023 100%); border-radius: 20px; padding: 28px 32px; margin-bottom: 24px; position: relative; overflow: hidden; display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; flex-wrap: wrap;">
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
          <div class="year-filter-btns" style="display:flex;gap:6px;">
            <button class="year-btn active" data-year="all" onclick="filterMenteeYear('all')">All</button>
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

<!-- ══════════════════════════════════════════════════════════
     EVALUATION OVERLAY
══════════════════════════════════════════════════════════ -->
<div class="overlay" id="evalOverlay">
  <div class="eval-panel">

    <div class="eval-hdr">
      <div>
        <div class="eval-hdr-name" id="evalName">—</div>
        <div class="eval-hdr-sub" id="evalSub">—</div>
      </div>
      <div class="eval-hdr-actions">
        <button class="hdr-btn" onclick="switchEvalTab('prospectus')"><i class="fas fa-scroll"></i> Prospectus</button>
        <button class="hdr-btn" onclick="switchEvalTab('advisement')"><i class="fas fa-lightbulb"></i> Advisement</button>
        <button class="hdr-btn hdr-btn-solid" onclick="printProspectus()"><i class="fas fa-print"></i> Print</button>
        <button class="hdr-btn" onclick="finalizeEval()"><i class="fas fa-check-circle"></i> Finalize</button>
        <button class="hdr-close" onclick="closeEval()"><i class="fas fa-times"></i></button>
      </div>
    </div>

    <div class="eval-tabs">
      <button class="eval-tab active" id="tab-prospectus" onclick="switchEvalTab('prospectus')">
        <i class="fas fa-scroll"></i> Prospectus
      </button>
      <button class="eval-tab" id="tab-advisement" onclick="switchEvalTab('advisement')">
        <i class="fas fa-lightbulb"></i> Advisement
        <span id="advBadge" style="display:none;background:var(--green);color:#fff;border-radius:10px;padding:1px 7px;font-size:10px;">0</span>
      </button>
      <button class="eval-tab" id="tab-notes" onclick="switchEvalTab('notes')">
        <i class="fas fa-sticky-note"></i> Session Notes
      </button>
    </div>

    <!-- PROSPECTUS TAB -->
    <div class="eval-body" id="tab-prospectus-body">
      <div class="empty-state"><div class="spinner"></div></div>
    </div>

    <!-- ADVISEMENT TAB -->
    <div class="eval-body" id="tab-advisement-body" style="display:none;">
      <div class="empty-state"><div class="spinner"></div></div>
    </div>

    <!-- NOTES TAB -->
    <div class="eval-body" id="tab-notes-body" style="display:none;">
      <div class="session-bar" style="margin-top:0;">
        <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:10px;">
          <i class="fas fa-clipboard" style="color:var(--gold-d);margin-right:7px;"></i>Evaluation Session Notes
        </div>
        <textarea id="sessionNotes" placeholder="Record observations, advisor recommendations, or any notes for this evaluation session…" style="min-height:120px;"></textarea>
        <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap;">
          <button class="btn btn-blue" onclick="switchEvalTab('advisement')">
            <i class="fas fa-lightbulb"></i> View Advisement
          </button>
          <button class="btn btn-green" onclick="finalizeEval()">
            <i class="fas fa-check-circle"></i> Finalize Session
          </button>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Hidden print target -->
<div id="printTarget" style="display:none;"></div>

<!-- Toast -->
<div class="toast" id="toast">
  <div class="toast-icon" id="toastIcon"><i class="fas fa-check"></i></div>
  <span id="toastMsg"></span>
</div>

<script src="../../../function/dashboard.js"></script>
<script>
/* ═══════════════════════════════════════════════════════════
   CONSTANTS
═══════════════════════════════════════════════════════════ */
const EVAL_PROC = '../../../data/evaluation_process.php';
const VALID_GRADES = [1.00,1.25,1.50,1.75,2.00,2.25,2.50,2.75,3.00,4.00,5.00];
const GRADE_LABELS = {
  1.00:'Excellent',1.25:'Very Good',1.50:'Very Good',1.75:'Good',
  2.00:'Satisfactory',2.25:'Fair',2.50:'Passing',2.75:'Low Passing',
  3.00:'Barely Passing',4.00:'Conditional',5.00:'Failed'
};
const YEAR_ORDER = ['1st Year','2nd Year','3rd Year','4th Year','Bridging'];
const YEAR_NUM = {'1st Year':1,'2nd Year':2,'3rd Year':3,'4th Year':4};
const SEM_NUM  = {'1st Semester':1,'2nd Semester':2};

let phSettings = {
  school_name:'Northern Bukidnon State College',
  school_address:'Manolo Fortich, Bukidnon',
  institute_name:'Institute for Business Management',
  degree_name:'Bachelor of Science in Business Administration'
};

let currentStudent = null;
let loadedSubjects  = [];   // full prospectus template subjects, sorted
let prereqSetsData  = [];   // prereq sets from department
let gradeMap        = {};   // subject_id → rounded grade (float|null)
let currentAY       = '2025-2026';

/* ═══════════════════════════════════════════════════════════
   GRADE HELPERS
═══════════════════════════════════════════════════════════ */
function roundGrade(r) {
  let c = 5.00, d = 99;
  VALID_GRADES.forEach(v => { const x = Math.abs(r-v); if(x<d){d=x;c=v;} });
  return c;
}
function gradeStatus(g) {
  if(g<=3.00) return 'passed';
  if(g===4.00) return 'conditional';
  return 'failed';
}
function gradeLabel(g)  { return GRADE_LABELS[g]||'—'; }
function gClass(s)      { return s==='passed'?'gp':s==='failed'?'gf':s==='conditional'?'gc':''; }
function pillClass(s)   { return 'gpill '+gClass(s||''); }
function statusText(s)  { return {passed:'Passed',failed:'Failed',conditional:'Cond.',not_taken:'—',incomplete:'Inc.'}[s]||'—'; }

/* ═══════════════════════════════════════════════════════════
   PREREQUISITE LOGIC
   Two layers:
   1. subject.prerequisite (text code) — direct prerequisite code
   2. prereqSetsData — department-created prerequisite SETS
      A prereq SET means: all subjects in the set must be PASSED
      before the target_subject_id can be taken.
═══════════════════════════════════════════════════════════ */
function buildPrereqUnlockMap(subjects, gMap, prereqSets, studentMajorId) {
  // subject_code → subject
  const byCode = {};
  subjects.forEach(s => { if(s.subject_code) byCode[s.subject_code.trim().toUpperCase()] = s; });
  // subject_id → subject
  const byId = {};
  subjects.forEach(s => { byId[s.id] = s; });

  // Build: target_subject_id → [ array of prerequisite subjects that must all be passed ]
  const setPrereqs = {};  // target_id → [prerequisite subject objects]
  if(Array.isArray(prereqSets)) {
    prereqSets.forEach(set => {
      // Only apply sets for this student's major
      if(set.major_id && parseInt(set.major_id) !== parseInt(studentMajorId)) return;
      if(!set.target_subject_id) return;
      const tid = parseInt(set.target_subject_id);
      if(!setPrereqs[tid]) setPrereqs[tid] = [];
      (set.subjects||[]).forEach(ps => {
        const found = byId[ps.id] || subjects.find(s=>s.subject_code===ps.subject_code);
        if(found) setPrereqs[tid].push(found);
      });
    });
  }

  const result = {};
  subjects.forEach(s => {
    // Layer 1: direct prerequisite code
    const prereqCode = (s.prerequisite||'').trim().toUpperCase();
    let directLocked = false;
    let directPrereqSubj = null;
    if(prereqCode) {
      directPrereqSubj = byCode[prereqCode]||null;
      if(directPrereqSubj) {
        const pg = gMap[directPrereqSubj.id];
        directLocked = !(pg!=null && gradeStatus(roundGrade(pg))==='passed');
      }
    }

    // Layer 2: prereq set
    const setPrereqList = setPrereqs[parseInt(s.id)]||[];
    let setLocked = false;
    let setBlockedBy = [];
    setPrereqList.forEach(ps => {
      const pg = gMap[ps.id];
      const passed = pg!=null && gradeStatus(roundGrade(pg))==='passed';
      if(!passed){ setLocked=true; setBlockedBy.push(ps); }
    });

    const isLocked = directLocked || setLocked;
    result[s.id] = {
      unlocked: !isLocked,
      directPrereqCode: prereqCode||null,
      directPrereqSubj,
      directLocked,
      setLocked,
      setBlockedBy,
      setPrereqList
    };
  });
  return result;
}

/* ═══════════════════════════════════════════════════════════
   STUDENT'S CURRENT YEAR/SEMESTER from year_level string
═══════════════════════════════════════════════════════════ */
function parseStudentStanding(yearLevelStr) {
  // yearLevelStr like "2nd Year - 1st Semester" or "3rd Year" or "2nd Year, 2nd Semester"
  let yr = 1, sem = 1;
  const yrMatch = yearLevelStr.match(/(\d+)(st|nd|rd|th)\s*Year/i);
  if(yrMatch) yr = parseInt(yrMatch[1]);
  if(/2nd\s*Sem/i.test(yearLevelStr)) sem = 2;
  else if(/1st\s*Sem/i.test(yearLevelStr)) sem = 1;
  return {yr, sem};
}

/* Next semester to advise for */
function getNextSemester(yr, sem) {
  if(sem===1) return {yr, sem:2};
  return {yr:yr+1, sem:1};
}

/* ═══════════════════════════════════════════════════════════
   TOAST
═══════════════════════════════════════════════════════════ */
function toast(msg, type='info', dur=3200) {
  const el=document.getElementById('toast');
  const ic=document.getElementById('toastIcon');
  document.getElementById('toastMsg').textContent=msg;
  const icons={success:'fa-check-circle',error:'fa-times-circle',info:'fa-info-circle'};
  ic.innerHTML=`<i class="fas ${icons[type]||'fa-info-circle'}"></i>`;
  el.className=`toast ${type} show`;
  clearTimeout(el._t);
  el._t=setTimeout(()=>el.classList.remove('show'),dur);
}

/* ═══════════════════════════════════════════════════════════
   LOAD MENTEES + STATS
═══════════════════════════════════════════════════════════ */
function loadMentees() {
  const fd=new FormData(); fd.append('action','get_mentees');
  fetch(EVAL_PROC,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    const c=document.getElementById('menteesContainer');
    if(!d.success||!d.mentees?.length){
      c.innerHTML=`<div class="empty-state"><i class="fas fa-users"></i><h3>No mentees assigned</h3><p>No mentees are currently assigned to you.</p></div>`;
      return;
    }

    // Stats
    const total=d.mentees.length;
    const graded=d.mentees.filter(m=>m.graded_count>0).length;
    const done=d.mentees.filter(m=>m.graded_count>0&&m.graded_count>=m.total_subjects).length;
    const statsEl=document.getElementById('statsRow');
    statsEl.innerHTML=`
      <div class="card" style="text-align:center;padding:16px 12px;margin-bottom:0;">
        <div style="font-size:26px;font-weight:800;color:var(--gold-d);font-family:'Playfair Display',serif;">${total}</div>
        <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-top:2px;">Total Mentees</div>
      </div>
      <div class="card" style="text-align:center;padding:16px 12px;margin-bottom:0;">
        <div style="font-size:26px;font-weight:800;color:var(--green);font-family:'Playfair Display',serif;">${done}</div>
        <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-top:2px;">Fully Evaluated</div>
      </div>
      <div class="card" style="text-align:center;padding:16px 12px;margin-bottom:0;">
        <div style="font-size:26px;font-weight:800;color:var(--blue);font-family:'Playfair Display',serif;">${graded}</div>
        <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-top:2px;">In Progress</div>
      </div>
      <div class="card" style="text-align:center;padding:16px 12px;margin-bottom:0;">
        <div style="font-size:26px;font-weight:800;color:var(--amber);font-family:'Playfair Display',serif;">${total-graded}</div>
        <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-top:2px;">Not Started</div>
      </div>`;

    let html=`<div class="mentee-grid" id="menteeGrid">`;
    d.mentees.forEach(m=>{
      const full=`${m.first_name}${m.middle_name?' '+m.middle_name:''} ${m.last_name}${m.suffix?' '+m.suffix:''}`.trim();
      const init=(m.avatar_initials||(m.first_name[0]+(m.last_name?.[0]||'')).toUpperCase()).trim();
      const pct=m.total_subjects>0?Math.round(m.graded_count/m.total_subjects*100):0;
      const gFrom=m.avatar_gradient_from||'#3b82f6';
      const gTo=m.avatar_gradient_to||'#60a5fa';

      const yrNum = (m.year_level || '0').replace(/[^0-9]/g, '');
      const semester = (m.year_level || '').includes('2nd Semester') ? '2nd Semester' : '1st Semester';
      html+=`<div class="mentee-card"
          onclick='openEval(${JSON.stringify(m).replace(/'/g,"&#39;")})'
          data-name="${esc(full.toLowerCase())}"
          data-year="${yrNum || '0'}"
          data-semester="${semester}">
        <div class="mc-top">
          <div class="mc-avatar" style="background:linear-gradient(135deg,${esc(gFrom)},${esc(gTo)});">${esc(init)}</div>
          <div>
            <div class="mc-name">${esc(full)}</div>
            <div class="mc-sub">${esc(m.student_number||'—')} &nbsp;·&nbsp; ${esc(m.major_name||'No major')}</div>
          </div>
        </div>
        <div class="mc-bottom">
          <div class="mc-pills">
            <span class="pill pill-blue"><i class="fas fa-layer-group" style="font-size:9px;margin-right:3px;"></i>${esc(m.year_level||'—')}</span>
            ${m.major_name?`<span class="pill pill-gold">${esc(m.major_name)}</span>`:''}
            <span class="pill ${m.graded_count>0?'pill-green':'pill-gray'}">
              <i class="fas fa-star" style="font-size:9px;margin-right:3px;"></i>${m.graded_count}/${m.total_subjects} graded
            </span>
          </div>
          <div class="mc-progress-track"><div class="mc-progress-bar" style="width:${pct}%;"></div></div>
          <div class="mc-progress-label"><span>${pct}% evaluated</span><span>${m.graded_count} of ${m.total_subjects}</span></div>
        </div>
        <div class="mc-action"><i class="fas fa-scroll" style="font-size:11px;"></i> Open Prospectus</div>
      </div>`;
    });
    html+=`</div>`;
    c.innerHTML=html;
  });
}
loadMentees();

function filterMentees() {
  const q=document.getElementById('menteeSearch').value.toLowerCase();
  document.querySelectorAll('.mentee-card').forEach(c=>{
    c.style.display=c.dataset.name.includes(q)?'':'none';
  });
}

let currentYearFilter = 'all';
function filterMenteeYear(y) {
  currentYearFilter = y;
  document.querySelectorAll('.year-btn').forEach(b=>b.classList.toggle('active',b.dataset.year===y));
  applyFilters();
}

function applyFilters() {
  const q = document.getElementById('menteeSearch').value.toLowerCase();
  const yearSelect = document.getElementById('filterYearLevel');
  const semSelect = document.getElementById('filterSemester');
  const selectedYear = yearSelect ? yearSelect.value : '';
  const selectedSem = semSelect ? semSelect.value : '';
  const cards = document.querySelectorAll('.mentee-card');
  if(cards.length === 0) return;
  cards.forEach(c=>{
    const name = c.dataset.name || '';
    const yl = c.dataset.year || '0';
    const filterYear = currentYearFilter;
    const matchesSearch = name.includes(q);
    const matchesYearBtn = filterYear === 'all' || yl === filterYear;
    const matchesYearDropdown = selectedYear === '' || yl === selectedYear;
    const matchesSem = selectedSem === '' || (c.dataset.semester || '').includes(selectedSem);
    c.style.display = (matchesSearch && matchesYearBtn && matchesYearDropdown && matchesSem) ? '' : 'none';
  });
}

function filterMentees() {
  applyFilters();
}

/* ═══════════════════════════════════════════════════════════
   EVAL TAB SWITCHER
═══════════════════════════════════════════════════════════ */
function switchEvalTab(tab) {
  ['prospectus','advisement','notes'].forEach(t=>{
    document.getElementById(`tab-${t}`).classList.toggle('active',t===tab);
    document.getElementById(`tab-${t}-body`).style.display=t===tab?'block':'none';
  });
  if(tab==='advisement' && currentStudent) buildAdvisement();
}

/* ═══════════════════════════════════════════════════════════
   OPEN / CLOSE EVAL
═══════════════════════════════════════════════════════════ */
function openEval(m) {
  if(typeof m==='string') m=JSON.parse(m);
  currentStudent=m; gradeMap={}; loadedSubjects=[]; prereqSetsData=[];
  document.getElementById('evalOverlay').classList.add('open');
  switchEvalTab('prospectus');

  const full=`${m.first_name}${m.middle_name?' '+m.middle_name:''} ${m.last_name}${m.suffix?' '+m.suffix:''}`.trim();
  document.getElementById('evalName').textContent=full;
  document.getElementById('evalSub').textContent=`${m.major_name||'No major'} · ${m.year_level||'—'} · A.Y. ${currentAY}`;
  document.getElementById('tab-prospectus-body').innerHTML=
    `<div class="empty-state"><div class="spinner"></div><p style="margin-top:12px;">Loading prospectus template…</p></div>`;
  document.getElementById('tab-advisement-body').innerHTML=
    `<div class="empty-state"><div class="spinner"></div></div>`;

  // Fetch eval data + prereq sets simultaneously
  const fd1=new FormData(); fd1.append('action','get_student_evaluation'); fd1.append('student_id',m.id); fd1.append('academic_year',currentAY);
  const fd2=new FormData(); fd2.append('action','get_prereq_sets');

  Promise.all([
    fetch(EVAL_PROC,{method:'POST',body:fd1}).then(r=>r.json()),
    fetch('../../../data/major_process.php',{method:'POST',body:fd2}).then(r=>r.json())
  ]).then(([evalData, prereqData]) => {
    if(!evalData.success){
      document.getElementById('tab-prospectus-body').innerHTML=
        `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error</h3><p>${esc(evalData.message)}</p></div>`;
      return;
    }
    if(evalData.ph_settings) phSettings={...phSettings,...evalData.ph_settings};
    prereqSetsData=(prereqData.success&&prereqData.sets)||[];
    renderProspectus(evalData);
  });
}

function closeEval() { document.getElementById('evalOverlay').classList.remove('open'); }

/* ═══════════════════════════════════════════════════════════
   RENDER PROSPECTUS (mirrors department page structure)
═══════════════════════════════════════════════════════════ */
 function renderProspectus(data) {
  const s=data.student; const subjects=data.subjects||[];
  const gwaData=data.gwa_data||{}; const ay=data.academic_year||currentAY;
  const prereqSetsMap = data.prereq_map || {};

  loadedSubjects=subjects;

  // Populate gradeMap (all subjects including bridging)
  subjects.forEach(sub => { if(sub.grade_rounded!=null) gradeMap[sub.id]=parseFloat(sub.grade_rounded); });

  // Get Bridging subjects
  const bridging = subjects.filter(s2 => s2.year_level === 'Bridging');

  // Build prereq unlock map
  const prereqUnlockMap=buildPrereqUnlockMap(subjects,gradeMap,prereqSetsData,s.major_id);
  
  // Pass prereqSetsMap to buildGradeTable
  window.currentPrereqSetsMap = prereqSetsMap;

  const full=`${s.first_name}${s.middle_name?' '+s.middle_name:''} ${s.last_name}${s.suffix?' '+s.suffix:''}`.trim();

  // Get current semester from student standing (moved before hdrHtml)
  const studentStanding = s.year_level||'1st Year - 1st Semester';
  const semMatch = studentStanding.match(/(\d+)(st|nd|rd|th)\s*Year.*?(\d+)(st|nd|rd|th)\s*Sem/i);
  const currentSem = semMatch ? (semMatch[3]=='1'?'1st':'2nd')+' Semester' : '1st Semester';

  // Header (same as department page)
  const hdrHtml=`<div class="pro-hdr">
    <img src="../../../media/LOGO.jpg" class="pro-logo" alt="School Logo">
    <div class="pro-title-block">
      <div class="pro-school">${esc(phSettings.school_name)}</div>
      <div class="pro-address">${esc(phSettings.school_address)}</div>
      <div style="border-top:1px solid #d4cfc5;margin:4px auto;width:80%;"></div>
      <div class="pro-institute">${esc(phSettings.institute_name)}</div>
      <div class="pro-degree">${esc(phSettings.degree_name)}</div>
      <div class="pro-major-line">Major in <strong>${esc(s.major_name||'—')}</strong></div>
      <div class="pro-label">&#9733; Student Evaluation Prospectus &#9733;</div>
    </div>
    <img src="../../../media/nbsc_logo.png" class="pro-logo" alt="Institute Logo" onerror="this.style.display='none'">
  </div>
  <div class="student-info-strip-print">
    <div class="sip-item"><span class="sip-label">Student:</span><span class="sip-value">${esc(full)}</span></div>
    <div class="sip-item"><span class="sip-label">Student ID:</span><span class="sip-value">${esc(s.student_id||s.student_number||'—')}</span></div>
    <div class="sip-item"><span class="sip-label">Year Level:</span><span class="sip-value">${esc(s.year_level||'—')}</span></div>
    <div class="sip-item"><span class="sip-label">Semester:</span><span class="sip-value">${esc(currentSem)}</span></div>
  </div>`;

  // GWA strip
  const gwaHtml=`<div class="gwa-strip">
    <div class="gwa-main">
      <div class="gwa-val" id="liveGWA">${gwaData.gwa!=null?parseFloat(gwaData.gwa).toFixed(2):'—'}</div>
      <div class="gwa-lbl">Current GWA</div>
    </div>
    <div class="gwa-stat"><div class="gwa-stat-val" id="liveUnitsTaken">${gwaData.total_units||0}</div><div class="gwa-stat-lbl">Units Taken</div></div>
    <div class="gwa-stat"><div class="gwa-stat-val" id="liveUnitsPassed">${gwaData.units_passed||0}</div><div class="gwa-stat-lbl">Units Passed</div></div>
    <div class="gwa-stat"><div class="gwa-stat-val" id="liveUnitsFailed" style="color:var(--red);">${(gwaData.total_units||0)-(gwaData.units_passed||0)}</div><div class="gwa-stat-lbl">w/ Issues</div></div>
    <div style="display:flex;gap:8px;align-items:center;margin-left:auto;">
      <select id="panelYearLevel" onchange="filterSubjectsByPanel()" style="padding:6px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-family:'Poppins',sans-serif;font-size:12px;font-weight:500;color:#374151;background:#fff;cursor:pointer;min-width:100px;">
        <option value="">All Years</option>
        <option value="1st Year">1st Year</option>
        <option value="2nd Year">2nd Year</option>
        <option value="3rd Year">3rd Year</option>
        <option value="4th Year">4th Year</option>
        <option value="Bridging">Bridging</option>
      </select>
      <select id="panelSemester" onchange="filterSubjectsByPanel()" style="padding:6px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-family:'Poppins',sans-serif;font-size:12px;font-weight:500;color:#374151;background:#fff;cursor:pointer;min-width:100px;">
        <option value="">All Semesters</option>
        <option value="1st Semester">1st Semester</option>
        <option value="2nd Semester">2nd Semester</option>
      </select>
      <span id="evalStatus" style="padding:4px 10px;background:#f3f4f6;border-radius:6px;font-size:11px;font-weight:600;color:#6b7280;"></span>
    </div>
    <div class="gwa-hint">
      Enter grade (1.00–5.00) → click <strong>save</strong><br>
      <span style="background:var(--amber-l);padding:1px 6px;border-radius:4px;font-size:10px;color:#92400e;font-weight:600;">
        <i class="fas fa-lock" style="font-size:9px;"></i> Locked = prerequisite not passed
      </span>
    </div>
  </div>`;

// Build year blocks (same order as department page)
  const byYear={};
  subjects.forEach(sub=>{
    const y=sub.year_level||'1st Year';
    if(!byYear[y]) byYear[y]=[];
    byYear[y].push(sub);
  });

  // Add bridging units to grandTotal
  const bridgingUnits = (bridging || []).reduce((a,s2)=>a+(parseFloat(s2.units)||0),0);

  let yearBlocks=''; let grandTotal=bridgingUnits;
  YEAR_ORDER.forEach(year=>{
    // Skip Bridging - shown in separate table at bottom
    if(year==='Bridging') return;
    const all=byYear[year]||[];
    if(!all.length) return;
    const sem1=all.filter(s2=>!s2.semester||s2.semester.includes('1st'));
    const sem2=all.filter(s2=>s2.semester&&s2.semester.includes('2nd'));
    const t=all.reduce((a,s2)=>a+(parseFloat(s2.units)||0),0);
    grandTotal+=t;
    yearBlocks+=`<div class="pro-year-block" data-year="${year}">
      <div class="pro-year-hdr">
        <span><i class="fas fa-calendar-alt" style="margin-right:6px;font-size:11px;"></i>${year}</span>
        <span class="pro-year-total">${fmt(t)} units</span>
      </div>
      <div class="pro-sem-row">
        <div>
          <div class="pro-sem-label">${year.toUpperCase()} — First Semester</div>
         ${buildGradeTable(sem1,s,ay,prereqUnlockMap)}
      </div>
      <div>
        <div class="pro-sem-label">${year.toUpperCase()} — Second Semester</div>
        ${buildGradeTable(sem2,s,ay,prereqUnlockMap)}
         </div>
      </div>
    </div>`;
});
  const sigHtml=`<div class="pro-sig-block">
    <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-lbl">Student's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
    <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-lbl">Adviser's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
    <div class="pro-sig-col"><div class="pro-sig-line"></div><div class="pro-sig-lbl">Program Head's Signature over Printed Name</div><div class="pro-sig-sub">Date: ___________________</div></div>
  </div>
  <div class="pro-legend">
    <span style="display:inline-block;width:10px;height:10px;background:var(--amber-l);border-left:3px solid var(--amber);border-radius:2px;vertical-align:middle;"></span>
    = Locked (prerequisite not yet passed)
  </div>`;

  // Student info strip for screen mode
  const studentInfoHtml = `<div class="student-info-strip">
    <div class="si-item"><span class="si-label">Student</span><span class="si-value">${esc(full)}</span></div>
    <div class="si-item"><span class="si-label">Student ID</span><span class="si-value">${esc(s.student_id||s.student_number||'—')}</span></div>
    <div class="si-item"><span class="si-label">Year Level</span><span class="si-value">${esc(s.year_level||'1st Year')}</span></div>
    <div class="si-item"><span class="si-label">Semester</span><span class="si-value">${esc(currentSem)}</span></div>
  </div>`;

  const proHtml=`<div class="pro-wrap" id="liveProspectus">
    ${hdrHtml}
    <div class="pro-body">
      ${!subjects.length?`<div class="empty-state"><i class="fas fa-book"></i><h3>No subjects configured</h3><p>Set up the prospectus in Department Management first.</p></div>`:''}
      ${studentInfoHtml}
      ${yearBlocks}

      <!-- Bridging Subjects -->
      <div class="pro-bridging-block" style="margin-top:20px;">
        <div class="pro-year-block">
          <div class="pro-year-hdr" style="background:linear-gradient(135deg,var(--gold-d),var(--gold-l));">
            <span><i class="fas fa-exchange-alt" style="margin-right:6px;font-size:11px;"></i>Bridging Subjects</span>
            <span class="pro-year-total">${fmt(bridging?bridging.reduce((a,s2)=>a+(parseFloat(s2.units)||0),0):0)} units</span>
          </div>
          <div style="padding:10px 12px 12px;">
            <table class="pro-table">
              <thead><tr>
                <th class="pro-th" style="width:54px;">Grade</th>
                <th class="pro-th pro-th-status" style="width:36px;">Status</th>
                <th class="pro-th">Code</th>
                <th class="pro-th">Subject Title</th>
                <th class="pro-th" style="width:32px;">Units</th>
                <th class="pro-th">Bridging For</th>
              </tr></thead>
              <tbody>
                ${bridging?bridging.map(sub=>{
                  const raw=gradeMap[sub.id]!=null?gradeMap[sub.id]:null;
                  const status=raw!=null?gradeStatus(roundGrade(raw)):(sub.grade_status||'not_taken');
                  return `<tr>
                    <td>
                      <div class="grade-cell-wrap">
                        <div class="grade-row">
                          <input type="number" class="grade-inp ${raw!=null?gClass(status):''}" id="g-${sub.id}"
                            value="${raw!=null?parseFloat(raw).toFixed(2):''}"
                            min="1" max="5" step="0.01" placeholder="—"
                            onchange="onGradeChange(${sub.id},${s.id},${s.major_id},'1st Semester','Bridging','${esc(ay)}')">
                          <span class="grade-print" style="display:none;">${raw!=null?parseFloat(raw).toFixed(2):'—'}</span>
                          <button class="save-btn" id="sbtn-${sub.id}"
                            onclick="saveGrade(${sub.id},${s.id},${s.major_id},'1st Semester','Bridging','${esc(ay)}')"
                            title="Save grade"><i class="fas fa-save"></i></button>
                        </div>
                        <div class="grade-hint" id="gl-${sub.id}">${sub.grade_label||''}</div>
                      </div>
                    </td>
                    <td class="pro-td-status"><span class="${pillClass(status)}" id="pill-${sub.id}">${statusText(status)}</span></td>
                    <td class="pro-code">${esc(sub.subject_code)}</td>
                    <td>${esc(sub.subject_name)}</td>
                    <td class="pro-units">${parseFloat(sub.units)||0}</td>
                    <td>${esc(sub.bridging_for||'—')}</td>
                  </tr>`;
                }).join(''):''}
                <tr class="pro-total-row"><td colspan="2" style="text-align:right;padding-right:8px;">Total</td><td class="pro-units">${fmt(bridging?bridging.reduce((a,s2)=>a+(parseFloat(s2.units)||0),0):0)}</td><td colspan="3"></td></tr>
              </tbody>
            </table>
          </div>
        </div>

      ${subjects.length?`<div class="pro-grand-total">Grand Total: <strong>${fmt(grandTotal)} units</strong></div>`:''}
      ${sigHtml}
    </div>
  </div>`;

  document.getElementById('tab-prospectus-body').innerHTML=gwaHtml+proHtml;
  buildAdvisement();
}

/* ═══════════════════════════════════════════════════════════
   BUILD GRADE TABLE (mirrors department pro-table exactly)
═══════════════════════════════════════════════════════════ */
 function buildGradeTable(subjects, student, ay, prereqUnlockMap) {
  if(!subjects?.length) return `<table class="pro-table">
    <thead><tr>
      <th class="pro-th" style="width:54px;">Grade</th>
      <th class="pro-th pro-th-status" style="width:36px;">Status</th>
      <th class="pro-th" style="width:62px;">Code</th>
      <th class="pro-th">Description</th>
      <th class="pro-th" style="width:32px;">Units</th>
      <th class="pro-th" style="width:46px;">Pre-Req</th>
    </tr></thead>
    <tbody><tr><td colspan="6" class="pro-empty">No subjects</td></tr></tbody>
  </table>`;

  let rows=''; let total=0;
  subjects.forEach(sub=>{
    const raw=gradeMap[sub.id]!=null?gradeMap[sub.id]:null;
    const status=raw!=null?gradeStatus(roundGrade(raw)):(sub.grade_status||'not_taken');
    const inpCls=raw!=null?gClass(status):'';
    const prereqCode=(sub.display_prerequisite||sub.prerequisite||'').trim();
    const pi=prereqUnlockMap?(prereqUnlockMap[sub.id]||{unlocked:true}):{unlocked:true};
    const isLocked=!pi.unlocked;
    total+=parseFloat(sub.units)||0;

    // Build lock tooltip
    let lockDesc='';
    if(isLocked){
      const parts=[];
      if(pi.directLocked&&pi.directPrereqSubj) parts.push(`Pass ${esc(pi.directPrereqCode)}`);
      if(pi.setLocked&&pi.setBlockedBy?.length) pi.setBlockedBy.forEach(b=>parts.push(`Pass ${esc(b.subject_code)}`));
      lockDesc=parts.join(', ');
    }

    // Show prereq set badge if this subject is in a prereq set as a target
    const isPrereqSetTarget=Array.isArray(prereqSetsData)&&prereqSetsData.some(set=>
      set.major_id==currentStudent?.major_id&&parseInt(set.target_subject_id)===parseInt(sub.id)
    );

    rows+=`<tr id="row-${sub.id}" class="${isLocked?'row-locked':''}">
      <td>
        <div class="grade-cell-wrap">
          <div class="grade-row">
            <input type="number" class="grade-inp ${inpCls}" id="g-${sub.id}"
              value="${raw!=null?parseFloat(raw).toFixed(2):''}"
              min="1" max="5" step="0.01" placeholder="—"
              onchange="onGradeChange(${sub.id},${student.id},${student.major_id},'${esc(sub.semester)}','${esc(sub.year_level)}','${esc(ay)}')"
              ${isLocked?'disabled title="'+lockDesc+'"':'title="1.00 to 5.00"'}>
            <span class="grade-print" style="display:none;">${raw!=null?parseFloat(raw).toFixed(2):'—'}</span>
            <button class="save-btn" id="sbtn-${sub.id}"
              onclick="saveGrade(${sub.id},${student.id},${student.major_id},'${esc(sub.semester)}','${esc(sub.year_level)}','${esc(ay)}')"
              ${isLocked?'disabled':''} title="Save grade"><i class="fas fa-save"></i></button>
          </div>
          <div class="grade-hint" id="gl-${sub.id}">${sub.grade_label||''}</div>
          ${isLocked?`<span class="lock-badge"><i class="fas fa-lock" style="font-size:7px;"></i>${lockDesc||'Locked'}</span>`:''}
        </div>
      </td>
      <td class="pro-td-status"><span class="${pillClass(status)}" id="pill-${sub.id}">${statusText(status)}</span></td>
      <td class="pro-code">
        ${esc(sub.subject_code)}
      </td>
      <td style="font-size:10px;">${esc(sub.subject_name)}</td>
      <td class="pro-units">${parseFloat(sub.units)||0}</td>
<td class="pro-prereq-col">
          ${window.currentPrereqSetsMap && window.currentPrereqSetsMap[sub.id] 
            ? esc(window.currentPrereqSetsMap[sub.id])
            : (prereqCode ? esc(prereqCode) : '—')}
          ${isPrereqSetTarget&&!prereqCode&&!window.currentPrereqSetsMap[sub.id]
            ?'<span class="prereq-chain-info"><i class="fas fa-sitemap" style="font-size:7px;"></i> Set</span>':''}
        </td>
     </tr>`;
  });

  const t=fmt(total);
  rows+=`<tr class="pro-total-row"><td colspan="4" style="text-align:right;padding-right:8px;">Total Units</td><td class="pro-units">${t}</td><td></td></tr>`;

  return `<table class="pro-table">
    <thead><tr>
      <th class="pro-th" style="width:54px;">Final Grade</th>
      <th class="pro-th pro-th-status" style="width:36px;">Status</th>
      <th class="pro-th" style="width:62px;">Course No.</th>
      <th class="pro-th">Description</th>
      <th class="pro-th" style="width:32px;">Units</th>
      <th class="pro-th" style="width:46px;">Pre-Req</th>
    </tr></thead>
    <tbody>${rows}</tbody>
  </table>`;
}

/* ═══════════════════════════════════════════════════════════
   ON GRADE CHANGE — instant feedback
═══════════════════════════════════════════════════════════ */
function onGradeChange(sid,studentId,majorId,sem,year,ay) {
  let inp=document.getElementById('g-'+sid);
  if(!inp)inp=document.getElementById('bg-'+sid);
  if(!inp){toast('Input not found','error');return;}
  const raw=parseFloat(inp.value);
  if(isNaN(raw)||raw<1||raw>5){toast('Grade must be 1.00–5.00','error');return;}
  const rounded=roundGrade(raw);
  const status=gradeStatus(rounded);
  inp.className='grade-inp '+gClass(status);
  document.getElementById('gl-'+sid).textContent=`→ ${rounded.toFixed(2)} ${gradeLabel(rounded)}`;
  if(rounded!==raw){ inp.style.boxShadow='0 0 0 2px var(--amber)'; }
  else { inp.style.boxShadow=''; }
  const btn=document.getElementById('sbtn-'+sid);
  if(btn){btn.style.background='var(--amber-l)';btn.style.color='var(--amber)';}
}

/* ═══════════════════════════════════════════════════════════
   SAVE GRADE
═══════════════════════════════════════════════════════════ */
function saveGrade(sid,studentId,majorId,sem,year,ay) {
  let inp=document.getElementById('g-'+sid);
  if(!inp)inp=document.getElementById('bg-'+sid);
  if(!inp){toast('Input field not found','error');return;}
  const raw=parseFloat(inp.value);
  if(isNaN(raw)||raw<1||raw>5){toast('Grade must be 1.00–5.00','error');return;}
  const btn=document.getElementById('sbtn-'+sid);
  btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';btn.disabled=true;

  const fd=new FormData();
  fd.append('action','save_grade'); fd.append('student_id',studentId);
  fd.append('subject_id',sid); fd.append('major_id',majorId);
  fd.append('grade',raw); fd.append('semester',sem);
  fd.append('year_level',year); fd.append('academic_year',ay);

  fetch(EVAL_PROC,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    btn.disabled=false;
    if(d.success){
      btn.innerHTML='<i class="fas fa-check"></i>';btn.className='save-btn saved';
      setTimeout(()=>{btn.innerHTML='<i class="fas fa-save"></i>';btn.className='save-btn';},2400);

      const rounded=d.grade_rounded; const status=d.status;
      inp.value=parseFloat(rounded).toFixed(2);
      inp.className='grade-inp '+gClass(status); inp.style.boxShadow='';
      // Also update grade-print span for print view
      const printSpan=inp.nextElementSibling;
      if(printSpan&&printSpan.classList.contains('grade-print')){printSpan.textContent=parseFloat(rounded).toFixed(2);printSpan.style.display='inline-block';}
      let gl=document.getElementById('gl-'+sid);
      if(!gl)gl=document.getElementById('bgl-'+sid);
      if(gl)gl.textContent=d.label||gradeLabel(rounded);
      let pill=document.getElementById('pill-'+sid);
      if(!pill)pill=document.getElementById('bpill-'+sid);
      if(pill){pill.className=pillClass(status);pill.textContent=statusText(status);}

      gradeMap[sid]=parseFloat(rounded);
      refreshLockStates();
      recalcGWA();
      buildAdvisement(true);  // silent rebuild
      toast(`Saved: ${d.label||gradeLabel(rounded)} (${parseFloat(rounded).toFixed(2)})`,'success');
    } else {
      btn.innerHTML='<i class="fas fa-save"></i>';
      toast(d.message||'Save failed','error');
    }
  }).catch(()=>{btn.disabled=false;btn.innerHTML='<i class="fas fa-save"></i>';toast('Network error','error');});
}

/* ═══════════════════════════════════════════════════════════
   REFRESH LOCK STATES after each grade save
═══════════════════════════════════════════════════════════ */
 function refreshLockStates() {
  if(!loadedSubjects.length) return;
  const prereqUnlockMap=buildPrereqUnlockMap(loadedSubjects,gradeMap,prereqSetsData,currentStudent?.major_id);
  loadedSubjects.forEach(sub=>{
    const pi=prereqUnlockMap[sub.id]||{unlocked:true};
    const row=document.getElementById('row-'+sub.id); if(!row) return;
    const inp=document.getElementById('g-'+sub.id);
    const sbtn=document.getElementById('sbtn-'+sub.id);
    const lockEl=row.querySelector('.lock-badge');
    if(pi.unlocked){
      row.classList.remove('row-locked');
      if(inp){inp.disabled=false;inp.title='1.00 to 5.00';}
      if(sbtn){sbtn.disabled=false;}
      if(lockEl) lockEl.style.display='none';
    } else {
      row.classList.add('row-locked');
      if(inp){inp.disabled=true;}
      if(sbtn){sbtn.disabled=true;}
      if(lockEl) lockEl.style.display='inline-flex';
    }
  });
}

/* ═══════════════════════════════════════════════════════════
   RECALCULATE GWA (live, client-side)
═══════════════════════════════════════════════════════════ */
function recalcGWA() {
  let tp=0,tu=0,up=0;
  document.querySelectorAll('.grade-inp').forEach(inp=>{
    const sid=inp.id.replace('g-','');
    if(!sid||isNaN(Number(sid))) return;
    const raw=parseFloat(inp.value); if(isNaN(raw)||raw<1||raw>5) return;
    const rounded=roundGrade(raw);
    const row=document.getElementById('row-'+sid); if(!row) return;
    const cells=row.querySelectorAll('td');
    const units=cells[4]?parseFloat(cells[4].textContent):0; if(!units) return;
    tp+=rounded*units; tu+=units;
    if(gradeStatus(rounded)==='passed') up+=units;
  });
  const el=document.getElementById('liveGWA'); if(el) el.textContent=tu>0?(tp/tu).toFixed(2):'—';
  const utEl=document.getElementById('liveUnitsTaken'); if(utEl) utEl.textContent=fmt(tu);
  const upEl=document.getElementById('liveUnitsPassed'); if(upEl) upEl.textContent=fmt(up);
  const ufEl=document.getElementById('liveUnitsFailed'); if(ufEl) ufEl.textContent=fmt(tu-up);
}

/* ═══════════════════════════════════════════════════════════
   BUILD ADVISEMENT
   Year-level-aware: advises based on NEXT semester from
   the student's current standing.
═══════════════════════════════════════════════════════════ */
function buildAdvisement(silent=false) {
  if(!currentStudent) return;
  if(!silent) document.getElementById('tab-advisement-body').innerHTML=
    `<div class="empty-state"><div class="spinner"></div><p style="margin-top:12px;">Analyzing…</p></div>`;

  const fd=new FormData();
  fd.append('action','get_advisement');
  fd.append('student_id',currentStudent.id);
  fd.append('major_id',currentStudent.major_id||0);
  fd.append('academic_year',currentAY);

  fetch(EVAL_PROC,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    if(!d.success){ document.getElementById('tab-advisement-body').innerHTML=`<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>${esc(d.message)}</p></div>`; return; }
    renderAdvisement(d);
  });
}

/* ═══════════════════════════════════════════════════════════
   RENDER ADVISEMENT — year-level-aware with next sem logic
═══════════════════════════════════════════════════════════ */
function renderAdvisement(d) {
  const adv=d.advisement||{};
  const currentYearStr=currentStudent?.year_level||'1st Year';
  const {yr:cYr,sem:cSem}=parseStudentStanding(currentYearStr);
  const {yr:nYr,sem:nSem}=getNextSemester(cYr,cSem);
  const nextYearLabel=`${['1st','2nd','3rd','4th'][nYr-1]||nYr+'th'} Year`;
  const nextSemLabel=nSem===1?'1st Semester':'2nd Semester';
  const nextAY=d.next_year||currentAY;

  const rec    =adv.recommended||[];
  const retake =adv.retake||[];
  const condl  =adv.conditional||[];
  const blocked=adv.blocked||[];
  const done   =adv.completed||[];

  // Filter recommended to only show next semester's subjects
  const nextRec=rec.filter(s2=>{
    const sYr=YEAR_NUM[s2.year_level]||1;
    const sSem=SEM_NUM[s2.semester]||1;
    return sYr===nYr&&sSem===nSem;
  });
  // Subjects that are recommended but not for next sem
  const laterRec=rec.filter(s2=>!nextRec.includes(s2));

  // Update advisement badge
  const badge=document.getElementById('advBadge');
  if(badge){ badge.style.display=nextRec.length?'inline-flex':'none'; badge.textContent=nextRec.length; }

  // ── Summary strip ──
  let html=`<div class="summary-strip">
    <div class="sum-card sum-done"><div class="sum-num">${done.length}</div><div class="sum-lbl">Completed</div></div>
    <div class="sum-card sum-rec"><div class="sum-num">${nextRec.length}</div><div class="sum-lbl">Enroll Next</div></div>
    <div class="sum-card sum-cond"><div class="sum-num">${condl.length}</div><div class="sum-lbl">Conditional</div></div>
    <div class="sum-card sum-fail"><div class="sum-num">${retake.length}</div><div class="sum-lbl">Must Retake</div></div>
    <div class="sum-card sum-block"><div class="sum-num">${blocked.length}</div><div class="sum-lbl">Blocked</div></div>
  </div>`;

  // ── Context banner ──
  html+=`<div class="context-banner">
    <div class="context-title"><i class="fas fa-calendar-alt" style="margin-right:6px;"></i>
      Enrollment Recommendation for <strong>${nextSemLabel} — ${nextYearLabel} (${nextAY})</strong>
    </div>
    <div class="context-sub">
      Current standing: <strong>${esc(currentYearStr)}</strong> &nbsp;·&nbsp;
      Showing subjects recommended for the upcoming semester.
    </div>
  </div>`;

  // ── NEXT SEMESTER — Can Enroll ──
  if(nextRec.length){
    html+=`<div class="adv-section">
      <div class="adv-sec-title ast-green"><i class="fas fa-check-circle"></i> Recommended for ${nextSemLabel} — ${nextYearLabel} <span style="opacity:.7;font-size:11px;">(${nextRec.length})</span></div>
      <div class="adv-grid">`;
    nextRec.forEach(sub=>{
      const unlocks=(loadedSubjects||[]).filter(ls=>(ls.prerequisite||'').trim().toUpperCase()===sub.subject_code.trim().toUpperCase());
      html+=`<div class="adv-card ac-rec">
        <div class="adv-code">${esc(sub.subject_code)}</div>
        <div class="adv-name">${esc(sub.subject_name)}</div>
        <div class="adv-meta">${esc(sub.year_level)} · ${esc(sub.semester)} · ${parseFloat(sub.units)||0} units</div>
        <div class="adv-reason ar-rec">${esc(sub.reason||'Available for enrollment')}</div>
        ${sub.grade_rounded?`<span class="grade-badge gb-pass">${parseFloat(sub.grade_rounded).toFixed(2)} — ${gradeLabel(parseFloat(sub.grade_rounded))}</span>`:''}
        ${unlocks.length?`<div class="adv-chain"><strong>Completing this unlocks:</strong><br>${unlocks.map(u=>`<span class="unlock-tag"><i class="fas fa-arrow-right" style="font-size:8px;"></i> ${esc(u.subject_code)}</span>`).join(' ')}</div>`:''}
      </div>`;
    });
    html+=`</div></div>`;
  }

  // ── Must Retake ──
  if(retake.length){
    html+=`<div class="adv-section">
      <div class="adv-sec-title ast-red"><i class="fas fa-redo"></i> Must Retake — Failed <span style="opacity:.7;font-size:11px;">(${retake.length})</span></div>
      <div class="adv-grid">`;
    retake.forEach(sub=>{
      html+=`<div class="adv-card ac-fail">
        <div class="adv-code">${esc(sub.subject_code)}</div>
        <div class="adv-name">${esc(sub.subject_name)}</div>
        <div class="adv-meta">${esc(sub.year_level)} · ${esc(sub.semester)} · ${parseFloat(sub.units)||0} units</div>
        <div class="adv-reason ar-fail">${esc(sub.reason||'Failed — must re-enroll')}</div>
        ${sub.grade_rounded?`<span class="grade-badge gb-fail">${parseFloat(sub.grade_rounded).toFixed(2)} — ${gradeLabel(parseFloat(sub.grade_rounded))}</span>`:''}
      </div>`;
    });
    html+=`</div></div>`;
  }

  // ── Conditional ──
  if(condl.length){
    html+=`<div class="adv-section">
      <div class="adv-sec-title ast-amber"><i class="fas fa-exclamation-triangle"></i> Conditional — Removal Exam Required <span style="opacity:.7;font-size:11px;">(${condl.length})</span></div>
      <div class="adv-grid">`;
    condl.forEach(sub=>{
      html+=`<div class="adv-card ac-cond">
        <div class="adv-code">${esc(sub.subject_code)}</div>
        <div class="adv-name">${esc(sub.subject_name)}</div>
        <div class="adv-meta">${esc(sub.year_level)} · ${esc(sub.semester)} · ${parseFloat(sub.units)||0} units</div>
        <div class="adv-reason ar-cond">${esc(sub.reason||'Grade 4.00 — removal exam needed')}</div>
        ${sub.grade_rounded?`<span class="grade-badge gb-cond">${parseFloat(sub.grade_rounded).toFixed(2)}</span>`:''}
      </div>`;
    });
    html+=`</div></div>`;
  }

  // ── Blocked with full prereq chain ──
  if(blocked.length){
    html+=`<div class="adv-section">
      <div class="adv-sec-title ast-slate"><i class="fas fa-lock"></i> Blocked — Prerequisite Not Yet Passed <span style="opacity:.7;font-size:11px;">(${blocked.length})</span></div>
      <div class="adv-grid">`;
    blocked.forEach(sub=>{
      const prereqCode=(sub.prerequisite||'').trim().toUpperCase();
      const prereqSubj=(loadedSubjects||[]).find(ls=>ls.subject_code.trim().toUpperCase()===prereqCode);
      const prereqGrade=prereqSubj&&gradeMap[prereqSubj.id]!=null?gradeMap[prereqSubj.id]:null;
      // Also check prereq sets
      const setData=Array.isArray(prereqSetsData)?prereqSetsData.filter(set=>
        set.major_id==currentStudent?.major_id&&parseInt(set.target_subject_id)===parseInt(sub.id)
      ):[];

      let chainHtml='';
      if(prereqSubj){
        const ps=prereqGrade!=null?gradeStatus(roundGrade(prereqGrade)):'not_taken';
        const pColor=ps==='passed'?'var(--green)':ps==='failed'?'var(--red)':'var(--amber)';
        chainHtml+=`<div class="adv-chain"><strong>Must pass first:</strong><br>
          <span class="block-prereq">
            <i class="fas fa-lock" style="font-size:7px;color:#64748b;"></i>
            ${esc(prereqSubj.subject_code)}
            <span style="color:${pColor};font-weight:700;">(${prereqGrade!=null?parseFloat(prereqGrade).toFixed(2):'No grade'})</span>
          </span>
          ${ps==='failed'?'<br><span style="font-size:9px;color:var(--red);display:block;margin-top:3px;"><i class="fas fa-redo"></i> Prerequisite must be retaken</span>':''}
          ${ps==='not_taken'?'<br><span style="font-size:9px;color:var(--amber);display:block;margin-top:3px;"><i class="fas fa-clock"></i> Prerequisite not yet taken</span>':''}
        </div>`;
      }
      if(setData.length){
        setData.forEach(set=>{
          const notPassed=(set.subjects||[]).filter(ps=>{
            const pg=gradeMap[ps.id]; return !(pg!=null&&gradeStatus(roundGrade(pg))==='passed');
          });
          if(notPassed.length){
            chainHtml+=`<div class="adv-chain"><strong>Prereq set [${esc(set.code)}] — still need to pass:</strong><br>
              ${notPassed.map(ps=>`<span class="block-prereq"><i class="fas fa-times" style="font-size:7px;color:var(--red);"></i> ${esc(ps.subject_code)}</span>`).join(' ')}
            </div>`;
          }
        });
      }

      html+=`<div class="adv-card ac-block">
        <div class="adv-code">${esc(sub.subject_code)}</div>
        <div class="adv-name">${esc(sub.subject_name)}</div>
        <div class="adv-meta">${esc(sub.year_level)} · ${esc(sub.semester)} · ${parseFloat(sub.units)||0} units</div>
        <div class="adv-reason ar-block">${esc(sub.reason||'Prerequisite required')}</div>
        ${chainHtml}
      </div>`;
    });
    html+=`</div></div>`;
  }

  // ── Later recommended (not next sem) ──
  if(laterRec.length){
    html+=`<div class="adv-section">
      <div class="adv-sec-title ast-blue"><i class="fas fa-calendar-plus"></i> Available in Future Semesters <span style="opacity:.7;font-size:11px;">(${laterRec.length})</span></div>
      <div class="adv-grid">`;
    laterRec.forEach(sub=>{
      html+=`<div class="adv-card ac-done">
        <div class="adv-code">${esc(sub.subject_code)}</div>
        <div class="adv-name">${esc(sub.subject_name)}</div>
        <div class="adv-meta">${esc(sub.year_level)} · ${esc(sub.semester)} · ${parseFloat(sub.units)||0} units</div>
        <div class="adv-reason ar-done">${esc(sub.year_level)} — ${esc(sub.semester)}</div>
      </div>`;
    });
    html+=`</div></div>`;
  }

  // ── Completed ──
  if(done.length){
    html+=`<div class="adv-section">
      <div class="adv-sec-title ast-blue"><i class="fas fa-graduation-cap"></i> Completed Subjects <span style="opacity:.7;font-size:11px;">(${done.length})</span></div>
      <div class="adv-grid">`;
    done.forEach(sub=>{
      html+=`<div class="adv-card ac-done">
        <div class="adv-code">${esc(sub.subject_code)}</div>
        <div class="adv-name">${esc(sub.subject_name)}</div>
        <div class="adv-meta">${esc(sub.year_level)} · ${esc(sub.semester)}</div>
        ${sub.grade_rounded?`<span class="grade-badge gb-pass">${parseFloat(sub.grade_rounded).toFixed(2)} — ${gradeLabel(parseFloat(sub.grade_rounded))}</span>`:''}
      </div>`;
    });
    html+=`</div></div>`;
  }

  if(!rec.length&&!retake.length&&!condl.length&&!blocked.length&&!done.length){
    html=`<div class="empty-state"><i class="fas fa-inbox"></i><h3>No prospectus data</h3><p>Configure the department prospectus first.</p></div>`;
  }

  document.getElementById('tab-advisement-body').innerHTML=html;
}

/* ═══════════════════════════════════════════════════════════
   PRINT — LONG BOND PAPER, SINGLE PAGE
═══════════════════════════════════════════════════════════ */
function printProspectus() {
  const el=document.getElementById('liveProspectus');
  if(!el){ toast('No prospectus loaded.','error'); return; }

  const pt=document.getElementById('printTarget');
  pt.innerHTML=el.outerHTML;

  // Prepare for print: show grade-print span, hide inputs
  pt.querySelectorAll('.grade-inp').forEach(inp=>{
    const span=inp.nextElementSibling;
    if(span&&span.classList.contains('grade-print')) span.style.display='inline-block';
  });
  pt.querySelectorAll('.save-btn,.grade-hint,.lock-badge,.prereq-chain-info,.gwa-strip,.session-bar').forEach(el2=>el2?.remove&&el2.remove());

  window.print();
  window.addEventListener('afterprint',()=>{ pt.innerHTML=''; },{once:true});
}

/* ═══════════════════════════════════════════════════════════
   FINALIZE
═══════════════════════════════════════════════════════════ */
function finalizeEval() {
  if(!currentStudent) return;
  if(!confirm('Finalize this evaluation session? A permanent session record will be created.')) return;
  const notes=document.getElementById('sessionNotes')?.value||'';
  const fd=new FormData();
  fd.append('action','finalize_session'); fd.append('student_id',currentStudent.id);
  fd.append('major_id',currentStudent.major_id||0);
  fd.append('academic_year',currentAY); fd.append('notes',notes);
  fetch(EVAL_PROC,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    toast(d.message||'Finalized!',d.success?'success':'error');
    if(d.success&&d.gwa?.gwa){ const el=document.getElementById('liveGWA'); if(el) el.textContent=parseFloat(d.gwa.gwa).toFixed(2); }
  });
}

/* ═══════════════════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════════════════ */
function fmt(v){ return v%1===0?v:parseFloat(v).toFixed(1); }

function parseStudentStanding(str) {
  let yr=1,sem=1;
  const m=str.match(/(\d+)(st|nd|rd|th)?\s*Year/i);
  if(m) yr=parseInt(m[1]);
  if(/2nd\s*Sem/i.test(str)) sem=2;
  else if(/1st\s*Sem/i.test(str)) sem=1;
  return {yr,sem};
}
function getNextSemester(yr,sem) {
  if(sem===1) return {yr,sem:2};
  return {yr:yr+1,sem:1};
}
function esc(str){
  if(!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function filterSubjectsByPanel() {
  const yrSelect = document.getElementById('panelYearLevel');
  const semSelect = document.getElementById('panelSemester');
  const statusEl = document.getElementById('evalStatus');
  const selectedYear = yrSelect ? yrSelect.value : '';
  const selectedSem = semSelect ? semSelect.value : '';
  
  document.querySelectorAll('.pro-year-block').forEach(block => {
    const blockYear = block.dataset.year || '';
    const matchesYear = selectedYear === '' || blockYear === selectedYear;
    block.style.display = matchesYear ? '' : 'none';
  });
  
  let totalSubjects = 0;
  let gradedSubjects = 0;
  
  if (selectedYear || selectedSem) {
    document.querySelectorAll('.pro-year-block').forEach(block => {
      const blockYear = block.dataset.year || '';
      const matchesYear = selectedYear === '' || blockYear === selectedYear;
      if (matchesYear) {
        block.querySelectorAll('.pro-subject-row, .pro-row').forEach(row => {
          const gradeInput = row.querySelector('input[type="number"]') || row.querySelector('.grade-input');
          const gradeSelect = row.querySelector('select');
          const isLocked = row.querySelector('.fa-lock') || row.classList.contains('locked-row');
          let hasGrade = false;
          if (gradeInput && gradeInput.value) hasGrade = true;
          if (gradeSelect && gradeSelect.value && gradeSelect.value !== '') hasGrade = true;
          
          const semText = block.textContent || '';
          const isFirstSem = semText.includes('First Semester');
          const isSecondSem = semText.includes('Second Semester');
          
          let countThis = true;
          if (selectedSem === '1st Semester' && !isFirstSem) countThis = false;
          if (selectedSem === '2nd Semester' && !isSecondSem) countThis = false;
          
          if (countThis && !isLocked) {
            totalSubjects++;
            if (hasGrade) gradedSubjects++;
          }
        });
      }
    });
    
    if (totalSubjects > 0) {
      const pct = Math.round((gradedSubjects / totalSubjects) * 100);
      if (pct === 100) {
        statusEl.innerHTML = '<i class="fas fa-check-circle" style="color:#10b981;"></i> Fully Evaluated';
        statusEl.style.background = '#d1fae5';
        statusEl.style.color = '#065f46';
      } else if (pct > 0) {
        statusEl.innerHTML = '<i class="fas fa-spinner" style="color:#f59e0b;"></i> ' + gradedSubjects + '/' + totalSubjects + ' (' + pct + '%)';
        statusEl.style.background = '#fef3c7';
        statusEl.style.color = '#92400e';
      } else {
        statusEl.innerHTML = '<i class="fas fa-clock" style="color:#6b7280;"></i> Not Started';
        statusEl.style.background = '#f3f4f6';
        statusEl.style.color = '#6b7280';
      }
    } else {
      statusEl.innerHTML = '';
    }
  } else {
    statusEl.innerHTML = '';
  }
}
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
</body>
</html>
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
  transition:all .4s cubic-bezier(.23,1,.32,1);
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
   ★ NEW: FOCUSED EVALUATION — YEAR/SEM FILTER CONTROLS
═══════════════════════════════════════════════════════════ */
.eval-focus-bar{
  display:flex;align-items:center;gap:10px;flex-wrap:wrap;
  padding:14px 20px;
  background:linear-gradient(135deg,#1a1a2e,#16213e);
  border-radius:var(--radius);margin-bottom:18px;
  border:1px solid rgba(255,255,255,.08);
  box-shadow:0 4px 20px rgba(0,0,0,.2);
  position:sticky;top:0;z-index:100;
}
.eval-focus-bar label{font-size:11px;font-weight:700;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.8px;white-space:nowrap;}
.eval-focus-sel{
  padding:9px 14px;border:1.5px solid rgba(255,255,255,.15);
  border-radius:10px;background:rgba(255,255,255,.08);
  font-family:'Poppins',sans-serif;font-size:12px;font-weight:600;
  color:#fff;cursor:pointer;transition:all .25s ease;min-width:130px;
  appearance:none;-webkit-appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 10px center;padding-right:30px;
}
.eval-focus-sel:hover,.eval-focus-sel:focus{border-color:var(--gold-l);background:rgba(212,168,67,.15);outline:none;}
.eval-focus-sel option{background:#1a1a2e;color:#fff;}
.focus-clear-btn{
  padding:9px 16px;border:1.5px solid rgba(255,255,255,.2);border-radius:10px;
  background:transparent;color:rgba(255,255,255,.6);font-family:'Poppins',sans-serif;
  font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;
  display:flex;align-items:center;gap:6px;
}
.focus-clear-btn:hover{background:rgba(255,255,255,.1);color:#fff;}
.focus-active-badge{
  padding:6px 14px;border-radius:20px;background:rgba(212,168,67,.25);
  border:1px solid var(--gold-l);color:var(--gold-l);font-size:11px;font-weight:700;
  display:flex;align-items:center;gap:6px;animation:pulse-badge .6s ease;
}
@keyframes pulse-badge{0%{transform:scale(.9);opacity:.7;}100%{transform:scale(1);opacity:1;}}

/* Finalize button in focus bar */
.btn-finalize{
  padding:10px 20px;border:none;border-radius:10px;cursor:pointer;
  font-family:'Poppins',sans-serif;font-size:12px;font-weight:700;
  background:linear-gradient(135deg,var(--green),#15803d);color:#fff;
  display:inline-flex;align-items:center;gap:8px;
  box-shadow:0 4px 16px rgba(22,163,74,.4);
  transition:all .28s cubic-bezier(.23,1,.32,1);
  margin-left:auto;
}
.btn-finalize:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(22,163,74,.5);}
.btn-finalize:disabled{opacity:.4;cursor:not-allowed;transform:none !important;}

/* Finalized badge overlay on table */
.sem-finalized-badge{
  display:inline-flex;align-items:center;gap:6px;
  padding:5px 14px;background:rgba(22,163,74,.12);
  border:1.5px solid var(--green-b);border-radius:20px;
  color:#166534;font-size:11px;font-weight:700;margin-bottom:8px;
}

/* ═══════════════════════════════════════════════════════════
   ★ NEW: YEAR BLOCK BLUR/FOCUS STATE
═══════════════════════════════════════════════════════════ */
.pro-year-block{
  transition:all .4s cubic-bezier(.23,1,.32,1);
  position:relative;
}
.pro-year-block.yr-blurred{
  filter:blur(3px) grayscale(60%);
  opacity:.35;
  pointer-events:none;
  user-select:none;
}
.pro-year-block.yr-blurred::after{
  content:'';position:absolute;inset:0;
  background:rgba(240,236,224,.4);z-index:10;
  border-radius:10px;pointer-events:none;
}
.pro-year-block.yr-active{
  filter:none;opacity:1;pointer-events:all;
  box-shadow:0 0 0 3px var(--gold-l),0 8px 32px rgba(184,134,11,.2);
  border-color:var(--gold-l);
}
/* Specific semester column blur */
.pro-sem-col.sem-blurred{
  filter:blur(2px) grayscale(50%);
  opacity:.3;pointer-events:none;
  transition:all .35s ease;
}
.pro-sem-col.sem-active{
  filter:none;opacity:1;pointer-events:all;
}

/* Finalized table overlay */
.finalized-lock-overlay{
  position:absolute;inset:0;z-index:20;
  background:rgba(240,253,244,.6);
  border-radius:inherit;pointer-events:none;
  border:2px solid var(--green-b);
}

/* ═══════════════════════════════════════════════════════════
   ★ NEW: FINALIZATION RESULT MODAL
═══════════════════════════════════════════════════════════ */
.result-modal-overlay{
  position:fixed;inset:0;background:rgba(5,5,15,.8);
  z-index:10000;display:none;align-items:center;
  justify-content:center;padding:16px;
  backdrop-filter:blur(6px);
}
.result-modal-overlay.open{display:flex;}
.result-modal{
  background:var(--white);border-radius:24px;
  width:100%;max-width:680px;
  box-shadow:0 40px 100px rgba(0,0,0,.5);
  overflow:hidden;animation:modal-in .45s cubic-bezier(.23,1,.32,1);
  position:relative;
}
@keyframes modal-in{
  from{opacity:0;transform:scale(.88) translateY(24px);}
  to{opacity:1;transform:scale(1) translateY(0);}
}
.rm-header{
  padding:28px 32px 24px;
  position:relative;overflow:hidden;
  display:flex;align-items:flex-start;gap:20px;
}
.rm-header.rm-pass{background:linear-gradient(135deg,#052e16,#14532d,#166534);}
.rm-header.rm-fail{background:linear-gradient(135deg,#450a0a,#7f1d1d,#991b1b);}
.rm-header.rm-cond{background:linear-gradient(135deg,#431407,#7c2d12,#9a3412);}
.rm-header::before{
  content:'';position:absolute;top:-60px;right:-80px;
  width:260px;height:260px;border-radius:50%;
  background:rgba(255,255,255,.06);pointer-events:none;
}
.rm-icon{
  width:72px;height:72px;border-radius:20px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:32px;
  box-shadow:0 8px 24px rgba(0,0,0,.3);
}
.rm-icon.pass-icon{background:rgba(134,239,172,.25);}
.rm-icon.fail-icon{background:rgba(252,165,165,.25);}
.rm-icon.cond-icon{background:rgba(253,230,138,.25);}
.rm-header-text{flex:1;}
.rm-semester-tag{
  display:inline-block;padding:3px 12px;border-radius:20px;
  font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;
  background:rgba(255,255,255,.15);color:rgba(255,255,255,.85);margin-bottom:8px;
}
.rm-verdict{font-size:28px;font-weight:800;color:#fff;font-family:'Playfair Display',serif;line-height:1.2;margin-bottom:6px;}
.rm-verdict-sub{font-size:13px;color:rgba(255,255,255,.75);line-height:1.5;}
.rm-gwa-chip{
  margin-top:10px;display:inline-flex;align-items:center;gap:8px;
  padding:6px 14px;border-radius:20px;background:rgba(255,255,255,.15);
  color:#fff;font-size:12px;font-weight:700;
}
.rm-body{padding:24px 32px 28px;}
.rm-close{
  position:absolute;top:16px;right:16px;
  width:36px;height:36px;background:rgba(255,255,255,.15);border:none;
  border-radius:10px;cursor:pointer;color:#fff;font-size:14px;
  display:flex;align-items:center;justify-content:center;
  transition:all .2s;z-index:10;
}
.rm-close:hover{background:rgba(255,255,255,.28);transform:rotate(90deg);}

/* Subject list in modal */
.rm-subject-list{
  display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));
  gap:8px;margin-bottom:20px;max-height:240px;overflow-y:auto;
  padding-right:4px;
}
.rm-subject-list::-webkit-scrollbar{width:4px;}
.rm-subject-list::-webkit-scrollbar-track{background:var(--cream);}
.rm-subject-list::-webkit-scrollbar-thumb{background:var(--border2);border-radius:2px;}
.rm-sub-card{
  padding:10px 12px;border-radius:10px;border:1px solid var(--border);
  background:var(--cream);transition:all .2s;
}
.rm-sub-card:hover{background:var(--amber-l);border-color:var(--amber-b);}
.rm-sub-code{font-size:12px;font-weight:700;color:var(--dark);}
.rm-sub-name{font-size:10px;color:var(--muted);margin-top:2px;line-height:1.3;}
.rm-sub-units{font-size:9px;color:var(--gold-d);font-weight:600;margin-top:3px;}
.rm-retake-card{
  padding:10px 12px;border-radius:10px;border-left:4px solid var(--red);
  border:1px solid var(--red-b);background:var(--red-l);
}
.rm-retake-card .rm-sub-code{color:#991b1b;}
.rm-retake-card .rm-sub-name{color:#b91c1c;}

/* Modal action buttons */
.rm-actions{display:flex;gap:10px;flex-wrap:wrap;padding-top:16px;border-top:1px solid var(--border);}
.btn-promote{
  padding:12px 24px;border:none;border-radius:12px;cursor:pointer;
  font-family:'Poppins',sans-serif;font-size:14px;font-weight:700;
  background:linear-gradient(135deg,var(--green),#15803d);color:#fff;
  display:inline-flex;align-items:center;gap:10px;
  box-shadow:0 6px 20px rgba(22,163,74,.4);
  transition:all .3s cubic-bezier(.23,1,.32,1);
  flex:1;justify-content:center;
}
.btn-promote:hover{transform:translateY(-2px);box-shadow:0 10px 32px rgba(22,163,74,.55);}
.btn-modal-close{
  padding:12px 20px;border:1.5px solid var(--border);border-radius:12px;cursor:pointer;
  font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;
  background:var(--white);color:var(--mid);
  display:inline-flex;align-items:center;gap:8px;
  transition:all .2s;
}
.btn-modal-close:hover{background:var(--cream);border-color:var(--border2);}

/* Promotion success animation */
.promote-success{
  text-align:center;padding:32px 24px;
  animation:fadeInUp .5s ease forwards;
}
.promote-success i{font-size:52px;color:var(--green);margin-bottom:14px;display:block;}
.promote-success h3{font-size:18px;font-weight:800;color:var(--dark);margin-bottom:8px;font-family:'Playfair Display',serif;}
.promote-success p{font-size:13px;color:var(--muted);}

/* Progress bar in modal */
.rm-grade-breakdown{
  display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;
}
.rm-grade-chip{
  padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;
  display:inline-flex;align-items:center;gap:5px;
}
.rgc-pass{background:var(--green-l);color:#166534;border:1px solid var(--green-b);}
.rgc-fail{background:var(--red-l);color:#991b1b;border:1px solid var(--red-b);}
.rgc-cond{background:var(--amber-l);color:#92400e;border:1px solid var(--amber-b);}
.rgc-none{background:var(--cream2);color:var(--muted);border:1px solid var(--border);}

/* ═══════════════════════════════════════════════════════════
   PRINT  ─ A4 portrait, 5mm margins
═══════════════════════════════════════════════════════════ */
@media print {
  @page { size: A4 portrait; margin: 5mm; }
  body > * { display: none !important; }
  #printTarget { display: block !important; }
  html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; background: white !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
  #printTarget { width: 100% !important; max-width: 210mm !important; margin: 0 !important; position: static !important; }
  .pro-wrap { width: 100% !important; max-width: 200mm !important; border: none !important; box-shadow: none !important; border-radius: 0 !important; background: white !important; font-size: 7pt !important; font-family: 'Times New Roman', Times, serif !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
  .pro-hdr { display: flex !important; flex-direction: row !important; align-items: center !important; justify-content: space-between !important; padding: 3mm 3mm 2mm !important; border-top: 2.5pt solid #8B6914 !important; border-bottom: 1.5pt solid #8B6914 !important; background: white !important; width: 100% !important; }
  .pro-logo { width: 22mm !important; height: 22mm !important; border: 1.5pt solid #8B6914 !important; border-radius: 2pt !important; flex-shrink: 0 !important; }
  .pro-title-block { flex: 1 1 auto !important; text-align: center !important; padding: 0 3mm !important; }
  .pro-school { font-size: 14pt !important; font-weight: 700 !important; font-family: 'Times New Roman',serif !important; }
  .pro-address { font-size: 9pt !important; color: #555 !important; font-style: italic !important; }
  .pro-institute { font-size: 11pt !important; font-weight: 700 !important; color: #8B6914 !important; }
  .pro-degree { font-size: 9pt !important; }
  .pro-major-line { font-size: 10pt !important; font-weight: 700 !important; }
  .pro-student-line { font-size: 9pt !important; color: #333 !important; }
  .pro-label { font-size: 8pt !important; padding: 1.5pt 8pt !important; border: 1pt solid #8B6914 !important; color: #8B6914 !important; }
  .student-info-strip-print { display: flex !important; justify-content: space-between !important; padding: 1.5mm 2mm !important; background: #fafafa !important; border: 0.5pt solid #ccc !important; margin-bottom: 1mm !important; }
  .sip-item { display: flex !important; gap: 2mm !important; }
  .sip-label { font-size: 9pt !important; font-weight: 700 !important; color: #333 !important; }
  .sip-value { font-size: 9pt !important; }
  .gwa-strip, .student-info-strip, .session-bar, .eval-hdr, .eval-tabs, .eval-focus-bar { display: none !important; }
  .student-info-strip-print { display: flex !important; }
  .pro-body { padding: 1mm 2mm 0 !important; overflow: visible !important; width: 100% !important; }
  .pro-year-block { margin-bottom: 1mm !important; border: 0.3pt solid #bbb !important; border-radius: 0 !important; overflow: hidden !important; page-break-inside: avoid !important; break-inside: avoid !important; width: 100% !important; filter: none !important; opacity: 1 !important; box-shadow: none !important; }
  .pro-year-hdr { padding: 1mm 1.5mm !important; font-size: 6.5pt !important; font-weight: 700 !important; background: #8B6914 !important; color: white !important; width: 100% !important; }
  .pro-sem-label { font-size: 6.5pt !important; font-weight: 700 !important; padding: 0.8pt 0 !important; background: #fde68a !important; border: 0.3pt solid #d4cfc5 !important; display: block !important; width: 100% !important; }
  .pro-sem-row { display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 2mm !important; padding: 1.5mm !important; background: white !important; width: 100% !important; }
  .pro-table { font-size: 6pt !important; table-layout: auto !important; border-collapse: collapse !important; font-family: 'Times New Roman',Times,serif !important; page-break-inside: avoid !important; width: 100% !important; }
  .pro-th { background: #f0ece0 !important; padding: 0.8pt 1.5pt !important; font-size: 6pt !important; font-weight: 700 !important; color: #7a5c10 !important; border: 0.3pt solid #ccc !important; white-space: nowrap; }
  .pro-table td { border: 0.3pt solid #ddd !important; padding: 0.8pt 1.5pt !important; font-size: 6pt !important; line-height: 1.3 !important; }
  .pro-code { font-size: 6pt !important; font-weight: 700 !important; white-space: nowrap !important; }
  .pro-units { font-size: 6pt !important; text-align: center !important; white-space: nowrap; }
  .pro-prereq-col { font-size: 5.5pt !important; white-space: nowrap; }
  .grade-inp, .save-btn, .grade-hint, .lock-badge, .prereq-chain-info, .sem-finalized-badge, .finalized-lock-overlay, .btn-finalize { display: none !important; }
  .grade-print { display: inline-block !important; font-size: 6pt !important; font-weight: 700 !important; }
  .pro-td-status, .pro-th-status { display: none !important; }
  .gpill { font-size: 5pt !important; padding: 0.3pt 1pt !important; }
  .row-locked td { background: #fffbeb !important; }
  .pro-total-row td { background: #f0ece0 !important; font-weight: 700 !important; color: #8B6914 !important; border-top: 0.5pt solid #B8860B !important; font-size: 6pt !important; }
  .pro-empty { font-size: 5pt !important; }
  .pro-bridging-block { margin-bottom: 1mm !important; page-break-inside: avoid !important; }
  .pro-grand-total { font-size: 7pt !important; font-weight: 700 !important; text-align: right !important; padding: 1mm 2mm !important; margin: 1mm 0 !important; background: #f0ece0 !important; border-top: 0.5pt solid #B8860B !important; color: #8B6914 !important; }
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
        <button class="hdr-btn hdr-btn-solid" onclick="printProspectus()"><i class="fas fa-print"></i> Print</button>
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

<!-- ══════════════════════════════════════════════════════════
     ★ NEW: FINALIZATION RESULT MODAL
══════════════════════════════════════════════════════════ -->
<div class="result-modal-overlay" id="resultModal">
  <div class="result-modal" id="resultModalInner">
    <button class="rm-close" onclick="closeResultModal()"><i class="fas fa-times"></i></button>
    <div id="resultModalContent"></div>
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
const YEAR_LABELS = ['1st Year','2nd Year','3rd Year','4th Year'];

let phSettings = {
  school_name:'Northern Bukidnon State College',
  school_address:'Manolo Fortich, Bukidnon',
  institute_name:'Institute for Business Management',
  degree_name:'Bachelor of Science in Business Administration'
};

let currentStudent = null;
let loadedSubjects  = [];
let prereqSetsData  = [];
let gradeMap        = {};
let currentAY       = '2025-2026';

// ★ NEW: Focus state
let focusYear  = '';   // e.g. '1st Year' or ''
let focusSem   = '';   // e.g. '1st Semester' or ''
// ★ NEW: Finalization registry  { 'YearLevel|Semester' : true }
let finalizedMap = {};

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
═══════════════════════════════════════════════════════════ */
function buildPrereqUnlockMap(subjects, gMap, prereqSets, studentMajorId) {
  const byCode = {};
  subjects.forEach(s => { if(s.subject_code) byCode[s.subject_code.trim().toUpperCase()] = s; });
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
        const found = byId[ps.id] || subjects.find(s=>s.subject_code===ps.subject_code);
        if(found) setPrereqs[tid].push(found);
      });
    });
  }

  const result = {};
  subjects.forEach(s => {
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

function parseStudentStanding(yearLevelStr) {
  let yr = 1, sem = 1;
  const yrMatch = yearLevelStr.match(/(\d+)(st|nd|rd|th)\s*Year/i);
  if(yrMatch) yr = parseInt(yrMatch[1]);
  if(/2nd\s*Sem/i.test(yearLevelStr)) sem = 2;
  else if(/1st\s*Sem/i.test(yearLevelStr)) sem = 1;
  return {yr, sem};
}

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

function filterMentees() { applyFilters(); }

let currentYearFilter = 'all';
function filterMenteeYear(y) {
  currentYearFilter = y;
  document.querySelectorAll('.year-btn').forEach(b=>b.classList.toggle('active',b.dataset.year===y));
  applyFilters();
}

function applyFilters() {
  const q = document.getElementById('menteeSearch').value.toLowerCase();
  document.querySelectorAll('.mentee-card').forEach(c=>{
    const name = c.dataset.name || '';
    const yl = c.dataset.year || '0';
    const filterYear = currentYearFilter;
    const matchesSearch = name.includes(q);
    const matchesYearBtn = filterYear === 'all' || yl === filterYear;
    c.style.display = (matchesSearch && matchesYearBtn) ? '' : 'none';
  });
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
  focusYear=''; focusSem=''; finalizedMap={};
  document.getElementById('evalOverlay').classList.add('open');
  switchEvalTab('prospectus');

  const full=`${m.first_name}${m.middle_name?' '+m.middle_name:''} ${m.last_name}${m.suffix?' '+m.suffix:''}`.trim();
  document.getElementById('evalName').textContent=full;
  document.getElementById('evalSub').textContent=`${m.major_name||'No major'} · ${m.year_level||'—'} · A.Y. ${currentAY}`;
  document.getElementById('tab-prospectus-body').innerHTML=
    `<div class="empty-state"><div class="spinner"></div><p style="margin-top:12px;">Loading prospectus template…</p></div>`;
  document.getElementById('tab-advisement-body').innerHTML=
    `<div class="empty-state"><div class="spinner"></div></div>`;

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

function closeEval() {
  document.getElementById('evalOverlay').classList.remove('open');
  focusYear=''; focusSem='';
}

/* ═══════════════════════════════════════════════════════════
   ★ NEW: FOCUS BAR — injects the dark year/sem selector bar
═══════════════════════════════════════════════════════════ */
function buildFocusBar() {
  return `<div class="eval-focus-bar" id="evalFocusBar">
    <label><i class="fas fa-filter" style="margin-right:5px;opacity:.6;"></i>Focus View</label>
    <select class="eval-focus-sel" id="focusYearSel" onchange="onFocusChange()">
      <option value="">— All Years —</option>
      <option value="1st Year">1st Year</option>
      <option value="2nd Year">2nd Year</option>
      <option value="3rd Year">3rd Year</option>
      <option value="4th Year">4th Year</option>
      <option value="Bridging">Bridging</option>
    </select>
    <select class="eval-focus-sel" id="focusSemSel" onchange="onFocusChange()">
      <option value="">— All Semesters —</option>
      <option value="1st Semester">1st Semester</option>
      <option value="2nd Semester">2nd Semester</option>
    </select>
    <div id="focusActiveBadge" style="display:none;" class="focus-active-badge">
      <i class="fas fa-eye"></i> <span id="focusBadgeText">—</span>
    </div>
    <button class="focus-clear-btn" onclick="clearFocus()" id="focusClearBtn" style="display:none;">
      <i class="fas fa-times"></i> Clear Filter
    </button>
    <button class="btn-finalize" id="btnFinalize" disabled onclick="triggerFinalize()" style="display:none;">
      <i class="fas fa-lock"></i> Finalize Evaluation
    </button>
  </div>`;
}

/* ═══════════════════════════════════════════════════════════
   ★ NEW: ON FOCUS CHANGE — updates blur/active states
═══════════════════════════════════════════════════════════ */
function onFocusChange() {
  focusYear  = document.getElementById('focusYearSel')?.value  || '';
  focusSem   = document.getElementById('focusSemSel')?.value   || '';
  applyFocusVisuals();
}

function clearFocus() {
  focusYear = ''; focusSem = '';
  const ys = document.getElementById('focusYearSel');
  const ss = document.getElementById('focusSemSel');
  if(ys) ys.value = '';
  if(ss) ss.value = '';
  applyFocusVisuals();
}

function applyFocusVisuals() {
  const hasFilter = !!(focusYear || focusSem);

  // Badge & clear button
  const badge   = document.getElementById('focusActiveBadge');
  const clearBtn= document.getElementById('focusClearBtn');
  const badgeT  = document.getElementById('focusBadgeText');
  if(badge)  badge.style.display  = hasFilter ? 'flex' : 'none';
  if(clearBtn) clearBtn.style.display = hasFilter ? 'flex' : 'none';
  if(badgeT) badgeT.textContent = [focusYear, focusSem].filter(Boolean).join(' · ');

  // Year blocks
  document.querySelectorAll('.pro-year-block[data-year]').forEach(block => {
    const blockYear = block.dataset.year;
    const yearMatch = !focusYear || blockYear === focusYear;

    if(!hasFilter) {
      block.classList.remove('yr-blurred','yr-active');
    } else if(yearMatch) {
      block.classList.remove('yr-blurred');
      block.classList.add('yr-active');
    } else {
      block.classList.add('yr-blurred');
      block.classList.remove('yr-active');
    }

    // Semester columns within this year block
    if(yearMatch && focusSem) {
      block.querySelectorAll('.pro-sem-col').forEach(col => {
        const colSem = col.dataset.sem || '';
        if(colSem === focusSem || !focusSem) {
          col.classList.remove('sem-blurred');
          col.classList.add('sem-active');
        } else {
          col.classList.add('sem-blurred');
          col.classList.remove('sem-active');
        }
      });
    } else {
      block.querySelectorAll('.pro-sem-col').forEach(col => {
        col.classList.remove('sem-blurred','sem-active');
      });
    }
  });

  // Show/hide finalize button
  const fkey   = `${focusYear}|${focusSem}`;
  const btnFin = document.getElementById('btnFinalize');
  if(btnFin) {
    const isFinalized = finalizedMap[fkey];
    if(focusYear && focusSem) {
      btnFin.style.display = 'flex';
      if(isFinalized) {
        btnFin.disabled = true;
        btnFin.innerHTML = '<i class="fas fa-check-circle"></i> Already Finalized';
        btnFin.style.background = 'linear-gradient(135deg,#64748b,#475569)';
      } else {
        btnFin.disabled = false;
        btnFin.innerHTML = '<i class="fas fa-lock"></i> Finalize Evaluation';
        btnFin.style.background = '';
      }
    } else {
      btnFin.style.display = 'none';
    }
  }
}

/* ═══════════════════════════════════════════════════════════
   ★ NEW: TRIGGER FINALIZE — validation, then lock & modal
═══════════════════════════════════════════════════════════ */
function triggerFinalize() {
  if(!focusYear || !focusSem) {
    toast('Please select both a Year and Semester to finalize.','error'); return;
  }
  const fkey = `${focusYear}|${focusSem}`;
  if(finalizedMap[fkey]) { toast('This period is already finalized.','info'); return; }

  // Gather subjects for the selected year+sem
  const targeted = loadedSubjects.filter(s =>
    s.year_level === focusYear &&
    (s.semester||'').includes(focusSem === '1st Semester' ? '1st' : '2nd')
  );

  if(!targeted.length) { toast('No subjects found for this year/semester.','error'); return; }

  // Check all have grades
  const missing = targeted.filter(s => gradeMap[s.id] == null);
  if(missing.length) {
    toast(`${missing.length} subject(s) still have no grade. Please enter all grades before finalizing.`,'error',4000);
    // Flash the missing inputs
    missing.forEach(s => {
      const inp = document.getElementById('g-'+s.id);
      if(inp) { inp.style.animation='none'; inp.style.borderColor='var(--red)'; inp.style.boxShadow='0 0 0 3px rgba(220,38,38,.3)'; setTimeout(()=>{inp.style.borderColor='';inp.style.boxShadow='';},2800); }
    });
    return;
  }

  if(!confirm(`Finalize evaluation for ${focusYear} — ${focusSem}?\n\nThis will lock the grades for this period and cannot be undone.`)) return;

  // Lock inputs for this period
  targeted.forEach(s => {
    const inp  = document.getElementById('g-'+s.id);
    const sbtn = document.getElementById('sbtn-'+s.id);
    if(inp)  { inp.disabled = true; inp.style.cursor='not-allowed'; }
    if(sbtn) { sbtn.disabled = true; }
  });

  finalizedMap[fkey] = true;
  applyFocusVisuals();

  // Show finalized badge on both semester columns
  document.querySelectorAll('.pro-year-block[data-year="'+focusYear+'"]').forEach(block => {
    block.querySelectorAll('.pro-sem-col').forEach(col => {
      if(col.dataset.sem === focusSem) {
        let badge = col.querySelector('.sem-finalized-badge');
        if(!badge) {
          badge = document.createElement('div');
          badge.className = 'sem-finalized-badge';
          badge.innerHTML = `<i class="fas fa-check-circle"></i> Finalized`;
          col.insertBefore(badge, col.firstChild);
        }
      }
    });
  });

  toast(`${focusYear} — ${focusSem} finalized successfully!`,'success',3000);

  // Short delay then show result modal
  setTimeout(() => showResultModal(targeted, focusYear, focusSem), 700);

  // Persist via server if possible
  const fd = new FormData();
  fd.append('action','finalize_session');
  fd.append('student_id', currentStudent.id);
  fd.append('major_id', currentStudent.major_id||0);
  fd.append('academic_year', currentAY);
  fd.append('year_level', focusYear);
  fd.append('semester', focusSem);
  fd.append('notes', document.getElementById('sessionNotes')?.value||'');
  fetch(EVAL_PROC,{method:'POST',body:fd}).catch(()=>{});
}

/* ═══════════════════════════════════════════════════════════
   ★ NEW: SHOW RESULT MODAL
═══════════════════════════════════════════════════════════ */
function showResultModal(subjects, yearLabel, semLabel) {
  // Compute semester GWA and pass/fail analysis
  let tp=0, tu=0, up=0, fp=0, cp=0, np=0;
  const passedSubs=[], failedSubs=[], condSubs=[], noGradeSubs=[];

  subjects.forEach(s => {
    const raw = gradeMap[s.id];
    if(raw == null) { np++; noGradeSubs.push(s); return; }
    const rounded = roundGrade(parseFloat(raw));
    const units = parseFloat(s.units)||0;
    const status = gradeStatus(rounded);
    tp += rounded * units;
    tu += units;
    if(status==='passed')      { up+=units; passedSubs.push({...s, grade:rounded}); }
    else if(status==='failed') { fp+=units; failedSubs.push({...s, grade:rounded}); }
    else                       { cp+=units; condSubs.push({...s, grade:rounded}); }
  });

  const semGWA = tu > 0 ? (tp/tu) : null;
  const hasFailed = failedSubs.length > 0 || condSubs.length > 0;
  const allPassed = failedSubs.length === 0 && condSubs.length === 0 && noGradeSubs.length === 0;

  let verdict, headerClass, iconClass, iconContent, verdictSub;

  if(allPassed) {
    verdict='Student PASSED';
    headerClass='rm-pass';
    iconClass='pass-icon';
    iconContent='🎓';
    verdictSub=`All ${passedSubs.length} subjects passed with a semester GWA of <strong>${semGWA?.toFixed(2)||'—'}</strong>. The student is eligible for promotion.`;
  } else if(condSubs.length > 0 && failedSubs.length === 0) {
    verdict='CONDITIONAL Status';
    headerClass='rm-cond';
    iconClass='cond-icon';
    iconContent='⚠️';
    verdictSub=`${condSubs.length} subject(s) received a conditional grade (4.00) requiring a removal examination.`;
  } else {
    verdict='Student DID NOT PASS';
    headerClass='rm-fail';
    iconClass='fail-icon';
    iconContent='📋';
    verdictSub=`${failedSubs.length} subject(s) failed. The student must retake failed subjects.`;
  }

  // Next semester subjects for promotion
  const {yr:cYr,sem:cSem} = parseStudentStanding(`${yearLabel} - ${semLabel}`);
  const {yr:nYr,sem:nSem} = getNextSemester(cYr, cSem);
  const nextYearLabel = YEAR_LABELS[nYr-1] || '—';
  const nextSemLabel  = nSem===1 ? '1st Semester' : '2nd Semester';

  const nextSemSubs = loadedSubjects.filter(s => {
    if(!allPassed) return false;
    const sYr  = YEAR_NUM[s.year_level]||0;
    const sSem = SEM_NUM[s.semester]||0;
    return sYr===nYr && sSem===nSem;
  });

  // Grade breakdown chips
  const breakdownHtml = `<div class="rm-grade-breakdown">
    <span class="rm-grade-chip rgc-pass"><i class="fas fa-check"></i> ${passedSubs.length} Passed</span>
    ${failedSubs.length?`<span class="rm-grade-chip rgc-fail"><i class="fas fa-times"></i> ${failedSubs.length} Failed</span>`:''}
    ${condSubs.length?`<span class="rm-grade-chip rgc-cond"><i class="fas fa-exclamation"></i> ${condSubs.length} Conditional</span>`:''}
    ${noGradeSubs.length?`<span class="rm-grade-chip rgc-none"><i class="fas fa-minus"></i> ${noGradeSubs.length} No Grade</span>`:''}
  </div>`;

  let bodyHtml = '';

  if(allPassed && nextSemSubs.length) {
    bodyHtml += `<div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:10px;">
      <i class="fas fa-calendar-alt" style="color:var(--green);margin-right:7px;"></i>
      Eligible subjects for <strong>${nextSemLabel} — ${nextYearLabel}</strong>
    </div>
    <div class="rm-subject-list">
      ${nextSemSubs.map(s=>`<div class="rm-sub-card">
        <div class="rm-sub-code">${esc(s.subject_code)}</div>
        <div class="rm-sub-name">${esc(s.subject_name)}</div>
        <div class="rm-sub-units"><i class="fas fa-book" style="font-size:8px;margin-right:3px;"></i>${parseFloat(s.units)||0} units</div>
      </div>`).join('')}
    </div>`;
  }

  if(failedSubs.length) {
    bodyHtml += `<div style="font-size:13px;font-weight:700;color:var(--red);margin-bottom:10px;margin-top:${allPassed?'16px':'0'}px;">
      <i class="fas fa-redo" style="margin-right:7px;"></i>Subjects to Retake
    </div>
    <div class="rm-subject-list">
      ${failedSubs.map(s=>`<div class="rm-retake-card">
        <div class="rm-sub-code">${esc(s.subject_code)}</div>
        <div class="rm-sub-name">${esc(s.subject_name)}</div>
        <div style="font-size:9px;font-weight:700;color:var(--red);margin-top:3px;">Grade: ${s.grade.toFixed(2)} — Failed</div>
      </div>`).join('')}
    </div>`;
  }

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

  // Action buttons
  let actionsHtml = `<div class="rm-actions">`;
  if(allPassed) {
    actionsHtml += `<button class="btn-promote" onclick="promoteStudent('${yearLabel}','${semLabel}','${nextYearLabel}','${nextSemLabel}')">
      <i class="fas fa-arrow-up"></i> Promote to ${nextSemLabel} — ${nextYearLabel}
    </button>`;
  } else {
    actionsHtml += `<div style="flex:1;padding:12px 16px;background:var(--red-l);border-radius:10px;border:1px solid var(--red-b);">
      <div style="font-size:12px;font-weight:700;color:#991b1b;margin-bottom:4px;"><i class="fas fa-info-circle"></i> Promotion Not Available</div>
      <div style="font-size:11px;color:#b91c1c;">Student must pass all subjects or complete removal exams before promotion.</div>
    </div>`;
  }
  actionsHtml += `<button class="btn-modal-close" onclick="closeResultModal()"><i class="fas fa-times"></i> Close</button></div>`;

  const modalHtml = `
    <div class="rm-header ${headerClass}">
      <div class="rm-icon ${iconClass}">${iconContent}</div>
      <div class="rm-header-text">
        <div class="rm-semester-tag">${esc(yearLabel)} · ${esc(semLabel)} · A.Y. ${esc(currentAY)}</div>
        <div class="rm-verdict">${verdict}</div>
        <div class="rm-verdict-sub">${verdictSub}</div>
        ${semGWA!=null?`<div class="rm-gwa-chip"><i class="fas fa-chart-line"></i> Semester GWA: ${semGWA.toFixed(2)}</div>`:''}
      </div>
    </div>
    <div class="rm-body">
      ${breakdownHtml}
      ${bodyHtml}
      ${actionsHtml}
    </div>`;

  document.getElementById('resultModalContent').innerHTML = modalHtml;
  document.getElementById('resultModal').classList.add('open');
}

function closeResultModal() {
  document.getElementById('resultModal').classList.remove('open');
}

/* ═══════════════════════════════════════════════════════════
   ★ NEW: PROMOTE STUDENT
═══════════════════════════════════════════════════════════ */
function promoteStudent(fromYear, fromSem, toYear, toSem) {
  const fd = new FormData();
  fd.append('action','promote_student');
  fd.append('student_id', currentStudent.id);
  fd.append('from_year', fromYear);
  fd.append('from_sem', fromSem);
  fd.append('to_year', toYear);
  fd.append('to_sem', toSem);
  fd.append('academic_year', currentAY);

  // Optimistic update — show success immediately while server saves
  const content = document.getElementById('resultModalContent');
  content.innerHTML = `<div class="promote-success">
    <i class="fas fa-check-circle"></i>
    <h3>Student Promoted!</h3>
    <p>
      <strong>${esc(currentStudent.first_name)} ${esc(currentStudent.last_name)}</strong>
      has been promoted to <strong>${esc(toSem)} — ${esc(toYear)}</strong>.
    </p>
    <p style="margin-top:8px;font-size:12px;color:var(--muted);">The student's academic record has been updated.</p>
    <div style="margin-top:20px;">
      <button class="btn btn-gold" onclick="closeResultModal();loadMentees();">
        <i class="fas fa-check"></i> Done
      </button>
    </div>
  </div>`;

  // Update local data
  if(currentStudent) currentStudent.year_level = `${toYear} - ${toSem}`;
  document.getElementById('evalSub').textContent =
    `${currentStudent.major_name||'No major'} · ${toYear} — ${toSem} · A.Y. ${currentAY}`;

  toast(`Promoted to ${toSem} — ${toYear}!`, 'success', 3500);

  // Server persist
  fetch(EVAL_PROC, {method:'POST', body:fd}).catch(()=>{});
}

/* ═══════════════════════════════════════════════════════════
   RENDER PROSPECTUS (mirrors department page structure)
═══════════════════════════════════════════════════════════ */
function renderProspectus(data) {
  const s=data.student; const subjects=data.subjects||[];
  const gwaData=data.gwa_data||{}; const ay=data.academic_year||currentAY;
  const prereqSetsMap = data.prereq_map || {};

  loadedSubjects=subjects;
  subjects.forEach(sub => { if(sub.grade_rounded!=null) gradeMap[sub.id]=parseFloat(sub.grade_rounded); });

  const bridging = subjects.filter(s2 => s2.year_level === 'Bridging');
  const prereqUnlockMap=buildPrereqUnlockMap(subjects,gradeMap,prereqSetsData,s.major_id);
  window.currentPrereqSetsMap = prereqSetsMap;

  const full=`${s.first_name}${s.middle_name?' '+s.middle_name:''} ${s.last_name}${s.suffix?' '+s.suffix:''}`.trim();

  const studentStanding = s.year_level||'1st Year - 1st Semester';
  const semMatch = studentStanding.match(/(\d+)(st|nd|rd|th)\s*Year.*?(\d+)(st|nd|rd|th)\s*Sem/i);
  const currentSem = semMatch ? (semMatch[3]=='1'?'1st':'2nd')+' Semester' : '1st Semester';

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

  const gwaHtml=`<div class="gwa-strip">
    <div class="gwa-main">
      <div class="gwa-val" id="liveGWA">${gwaData.gwa!=null?parseFloat(gwaData.gwa).toFixed(2):'—'}</div>
      <div class="gwa-lbl">Current GWA</div>
    </div>
    <div class="gwa-stat"><div class="gwa-stat-val" id="liveUnitsTaken">${gwaData.total_units||0}</div><div class="gwa-stat-lbl">Units Taken</div></div>
    <div class="gwa-stat"><div class="gwa-stat-val" id="liveUnitsPassed">${gwaData.units_passed||0}</div><div class="gwa-stat-lbl">Units Passed</div></div>
    <div class="gwa-stat"><div class="gwa-stat-val" id="liveUnitsFailed" style="color:var(--red);">${(gwaData.total_units||0)-(gwaData.units_passed||0)}</div><div class="gwa-stat-lbl">w/ Issues</div></div>
    <div class="gwa-hint">
      Enter grade (1.00–5.00) → click <strong>save</strong><br>
      <span style="background:var(--amber-l);padding:1px 6px;border-radius:4px;font-size:10px;color:#92400e;font-weight:600;">
        <i class="fas fa-lock" style="font-size:9px;"></i> Locked = prerequisite not passed
      </span>
    </div>
  </div>`;

  const byYear={};
  subjects.forEach(sub=>{
    const y=sub.year_level||'1st Year';
    if(!byYear[y]) byYear[y]=[];
    byYear[y].push(sub);
  });

  const bridgingUnits = (bridging || []).reduce((a,s2)=>a+(parseFloat(s2.units)||0),0);
  let yearBlocks=''; let grandTotal=bridgingUnits;

  YEAR_ORDER.forEach(year=>{
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
        <div class="pro-sem-col" data-sem="1st Semester">
          <div class="pro-sem-label">${year.toUpperCase()} — First Semester</div>
          ${buildGradeTable(sem1,s,ay,prereqUnlockMap)}
        </div>
        <div class="pro-sem-col" data-sem="2nd Semester">
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
        <div class="pro-year-block" data-year="Bridging">
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
      </div>
      ${subjects.length?`<div class="pro-grand-total">Grand Total: <strong>${fmt(grandTotal)} units</strong></div>`:''}
      ${sigHtml}
    </div>
  </div>`;

  // ★ Focus bar prepended above the GWA strip
  document.getElementById('tab-prospectus-body').innerHTML = buildFocusBar() + gwaHtml + proHtml;
  buildAdvisement();
}

/* ═══════════════════════════════════════════════════════════
   BUILD GRADE TABLE
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

    let lockDesc='';
    if(isLocked){
      const parts=[];
      if(pi.directLocked&&pi.directPrereqSubj) parts.push(`Pass ${esc(pi.directPrereqCode)}`);
      if(pi.setLocked&&pi.setBlockedBy?.length) pi.setBlockedBy.forEach(b=>parts.push(`Pass ${esc(b.subject_code)}`));
      lockDesc=parts.join(', ');
    }

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
      <td class="pro-code">${esc(sub.subject_code)}</td>
      <td style="font-size:10px;">${esc(sub.subject_name)}</td>
      <td class="pro-units">${parseFloat(sub.units)||0}</td>
      <td class="pro-prereq-col">
        ${window.currentPrereqSetsMap && window.currentPrereqSetsMap[sub.id]
          ? esc(window.currentPrereqSetsMap[sub.id])
          : (prereqCode ? esc(prereqCode) : '—')}
        ${isPrereqSetTarget&&!prereqCode&&!window.currentPrereqSetsMap?.[sub.id]
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
      buildAdvisement(true);
      toast(`Saved: ${d.label||gradeLabel(rounded)} (${parseFloat(rounded).toFixed(2)})`,'success');
    } else {
      btn.innerHTML='<i class="fas fa-save"></i>';
      toast(d.message||'Save failed','error');
    }
  }).catch(()=>{btn.disabled=false;btn.innerHTML='<i class="fas fa-save"></i>';toast('Network error','error');});
}

/* ═══════════════════════════════════════════════════════════
   REFRESH LOCK STATES
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

    // Respect finalized state — don't re-enable finalized inputs
    const subYear = sub.year_level || '';
    const subSem  = sub.semester   || '';
    const fkey = `${subYear}|${subSem.includes('1st')?'1st Semester':'2nd Semester'}`;
    const isFinalized = finalizedMap[fkey];

    if(pi.unlocked && !isFinalized){
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
   RECALCULATE GWA (live)
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
   RENDER ADVISEMENT
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

  const nextRec=rec.filter(s2=>{
    const sYr=YEAR_NUM[s2.year_level]||1;
    const sSem=SEM_NUM[s2.semester]||1;
    return sYr===nYr&&sSem===nSem;
  });
  const laterRec=rec.filter(s2=>!nextRec.includes(s2));

  const badge=document.getElementById('advBadge');
  if(badge){ badge.style.display=nextRec.length?'inline-flex':'none'; badge.textContent=nextRec.length; }

  let html=`<div class="summary-strip">
    <div class="sum-card sum-done"><div class="sum-num">${done.length}</div><div class="sum-lbl">Completed</div></div>
    <div class="sum-card sum-rec"><div class="sum-num">${nextRec.length}</div><div class="sum-lbl">Enroll Next</div></div>
    <div class="sum-card sum-cond"><div class="sum-num">${condl.length}</div><div class="sum-lbl">Conditional</div></div>
    <div class="sum-card sum-fail"><div class="sum-num">${retake.length}</div><div class="sum-lbl">Must Retake</div></div>
    <div class="sum-card sum-block"><div class="sum-num">${blocked.length}</div><div class="sum-lbl">Blocked</div></div>
  </div>`;

  html+=`<div class="context-banner">
    <div class="context-title"><i class="fas fa-calendar-alt" style="margin-right:6px;"></i>
      Enrollment Recommendation for <strong>${nextSemLabel} — ${nextYearLabel} (${nextAY})</strong>
    </div>
    <div class="context-sub">
      Current standing: <strong>${esc(currentYearStr)}</strong> &nbsp;·&nbsp;
      Showing subjects recommended for the upcoming semester.
    </div>
  </div>`;

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

  if(blocked.length){
    html+=`<div class="adv-section">
      <div class="adv-sec-title ast-slate"><i class="fas fa-lock"></i> Blocked — Prerequisite Not Yet Passed <span style="opacity:.7;font-size:11px;">(${blocked.length})</span></div>
      <div class="adv-grid">`;
    blocked.forEach(sub=>{
      const prereqCode=(sub.prerequisite||'').trim().toUpperCase();
      const prereqSubj=(loadedSubjects||[]).find(ls=>ls.subject_code.trim().toUpperCase()===prereqCode);
      const prereqGrade=prereqSubj&&gradeMap[prereqSubj.id]!=null?gradeMap[prereqSubj.id]:null;
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
   PRINT
═══════════════════════════════════════════════════════════ */
function printProspectus() {
  const el=document.getElementById('liveProspectus');
  if(!el){ toast('No prospectus loaded.','error'); return; }

  const pt=document.getElementById('printTarget');
  pt.innerHTML=el.outerHTML;

  pt.querySelectorAll('.grade-inp').forEach(inp=>{
    const span=inp.nextElementSibling;
    if(span&&span.classList.contains('grade-print')) span.style.display='inline-block';
  });
  pt.querySelectorAll('.save-btn,.grade-hint,.lock-badge,.prereq-chain-info,.gwa-strip,.session-bar').forEach(el2=>el2?.remove&&el2.remove());

  window.print();
  window.addEventListener('afterprint',()=>{ pt.innerHTML=''; },{once:true});
}

/* ═══════════════════════════════════════════════════════════
   FINALIZE EVAL (from Notes tab — finalize whole session)
═══════════════════════════════════════════════════════════ */
function finalizeEval() {
  if(!currentStudent) return;
  if(!focusYear || !focusSem) {
    toast('Switch to the Prospectus tab, select a Year and Semester, then use the Finalize button.','info',4500);
    switchEvalTab('prospectus');
    return;
  }
  triggerFinalize();
}

/* ═══════════════════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════════════════ */
function fmt(v){ return v%1===0?v:parseFloat(v).toFixed(1); }
function esc(str){
  if(!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Close modal on backdrop click
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
</body>
</html>
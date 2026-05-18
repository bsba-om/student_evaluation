<?php
/**
 * setup_graduate.php — Graduate records, prospectus PDFs, local folder overview.
 * URL: /student_evaluation/setup_graduate.php
 */

require_once __DIR__ . '/../../../data/session_security.php';
require_once __DIR__ . '/../../../data/config.php';

$role_access = check_role_access('instructor');
if (!$role_access['allowed']) {
    die('Access denied');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Graduate Management — Prospectus</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --gold: #B8860B; --gold-l: #D4A843; --gold-d: #8B6914;
            --cream: #f7f5ef; --white: #fff; --dark: #1a1a1a; --muted: #7a7a7a;
            --border: #d4cfc5; --cream2: #f0ebe3;
            --radius: 14px;
            --shadow: 0 4px 20px rgba(0,0,0,.10);
        }
        body { font-family: 'Poppins', sans-serif; background: var(--cream); margin: 0; padding: 20px; }
        .container { max-width: 1180px; margin: 0 auto; }
        .header {
            background: linear-gradient(135deg, var(--gold-l), var(--gold-d));
            color: #fff; padding: 28px; border-radius: var(--radius);
            margin-bottom: 24px; box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .header h1 { font-family: 'Playfair Display', serif; font-size: 2rem; margin: 0 0 8px; }
        .header p { margin: 0; opacity: .95; font-size: 14px; }
        .tabs { display: flex; flex-wrap: wrap; background: #fff; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); margin-bottom: 22px; }
        .tab {
            flex: 1; min-width: 140px; padding: 14px 16px; text-align: center; cursor: pointer;
            border-right: 1px solid var(--border); background: var(--cream2); font-weight: 600; font-size: 13px;
            transition: background .25s, color .25s;
        }
        .tab:last-child { border-right: none; }
        .tab.active { background: linear-gradient(135deg, var(--gold-l), var(--gold-d)); color: #fff; }
        .tab:hover:not(.active) { background: #fff; }
        .tab-content { display: none; animation: fadeIn .4s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        .card { background: var(--white); border-radius: var(--radius); padding: 22px; margin-bottom: 20px; box-shadow: var(--shadow); border: 1px solid var(--border); }
        .card-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
        .card-header h2 { margin: 0; font-family: 'Playfair Display', serif; color: var(--gold-d); font-size: 1.35rem; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px;
            background: linear-gradient(135deg, var(--gold-l), var(--gold-d)); color: #fff; border: none;
            border-radius: 10px; font-weight: 600; cursor: pointer; font-family: inherit; font-size: 13px;
            box-shadow: 0 4px 14px rgba(184,134,11,.3); transition: transform .2s, box-shadow .2s;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(184,134,11,.38); }
        .btn-secondary { background: linear-gradient(135deg, #16a34a, #15803d); box-shadow: 0 4px 14px rgba(22,163,74,.28); }
        .btn-outline { background: transparent; border: 2px solid var(--gold-d); color: var(--gold-d); box-shadow: none; }
        .btn-back {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }
        .btn-back:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-back:active {
            transform: translateY(0);
        }
        .filter-bar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 14px; }
        .filter-bar select, .filter-bar input { padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: linear-gradient(135deg, var(--gold-d), var(--gold)); color: #fff; text-align: left; padding: 12px 14px; }
        td { padding: 12px 14px; border-top: 1px solid var(--border); }
        tbody tr:nth-child(even) { background: var(--cream2); }
        .no-data { text-align: center; padding: 36px; color: var(--muted); }
        .no-data i { font-size: 36px; color: var(--gold-l); display: block; margin-bottom: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 18px; }
        .stat-card { background: linear-gradient(135deg, #fffdf6, #fef9ed); border-radius: var(--radius); padding: 18px; text-align: center; border: 1px solid rgba(184,134,11,.2); }
        .stat-number { font-size: 2rem; font-weight: 800; font-family: 'Playfair Display', serif; color: var(--gold-d); }
        .stat-label { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-top: 6px; }
        .folder-structure { background: #faf8f4; border-radius: 10px; padding: 14px; border: 1px solid var(--border); font-family: Consolas, monospace; font-size: 12px; }
        .folder-item { padding: 6px 0; }
        #generation-status { margin-top: 14px; padding: 12px; border-radius: 10px; display: none; font-size: 13px; }
        .status-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .status-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>
<div class="container">
        <div class="header">
            <div class="header-title">
                <h1><i class="fas fa-user-graduate"></i> Graduate Management</h1>
                <p>Graduate roster, official prospectus PDFs on disk (<code>C:\graduate\batch YYYY-YYYY\om|fm|mm\</code>), and batch reports.</p>
            </div>
            <button type="button" class="btn-back" onclick="window.history.back()">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>

    <div class="tabs" role="tablist">
        <div class="tab active" data-tab="list" role="tab">Graduate list</div>
        <div class="tab" data-tab="generate" role="tab">Generate PDF</div>
        <div class="tab" data-tab="folders" role="tab">Folder structure</div>
        <div class="tab" data-tab="reports" role="tab">Reports</div>
    </div>

    <div id="tab-list" class="tab-content active">
        <div class="card">
            <div class="card-header">
                <h2>Graduates</h2>
                <button type="button" class="btn btn-secondary" data-tab-trigger="generate"><i class="fas fa-file-pdf"></i> Generate PDF</button>
            </div>
            <div class="filter-bar">
                <select id="filter-batch">
                    <option value="" selected>All batches</option>
                    <option value="2024-2025">2024-2025</option>
                    <option value="2025-2026">2025-2026</option>
                    <option value="2026-2027">2026-2027</option>
                </select>
                <select id="filter-major">
                    <option value="">All majors</option>
                    <option value="OM">OM — Operational Management</option>
                    <option value="FM">FM — Financial Management</option>
                    <option value="MM">MM — Marketing Management</option>
                </select>
                <input type="text" id="search-graduate" placeholder="Search name or ID…">
                <button type="button" class="btn" id="btn-filter"><i class="fas fa-search"></i> Search</button>
                <button type="button" class="btn btn-outline" id="btn-reset"><i class="fas fa-times"></i> Reset</button>
            </div>
            <div id="graduates-table"><div class="no-data"><i class="fas fa-spinner fa-spin"></i><p>Loading…</p></div></div>
        </div>
    </div>

    <div id="tab-generate" class="tab-content">
        <div class="card">
            <div class="card-header"><h2>Generate prospectus PDF</h2></div>
            <form id="prospectus-form">
                <div class="filter-bar">
                    <select id="gen-student" required style="min-width:220px;">
                        <option value="">Select student…</option>
                    </select>
                    <select id="gen-batch" required>
                        <option value="">Batch (A.Y.)</option>
                        <option value="2025-2026">2025-2026</option>
                        <option value="2026-2027">2026-2027</option>
                    </select>
                </div>
                <div class="filter-bar">
                    <input type="text" id="gen-major" placeholder="Major" readonly style="flex:1;">
                    <input type="text" id="gen-year-level" placeholder="Year level" readonly style="flex:1;">
                </div>
                <button type="submit" class="btn btn-secondary" id="generate-btn"><i class="fas fa-file-pdf"></i> Generate PDF &amp; record</button>
                <div id="generation-status"></div>
            </form>
        </div>
    </div>

    <div id="tab-folders" class="tab-content">
        <div class="card">
            <div class="card-header"><h2>Local folders</h2></div>
            <p style="font-size:13px;color:var(--muted);margin-top:0;">Example: <code>C:\graduate\batch 2025-2026\om\studentname_om_batch2025-2026.pdf</code></p>
            <div class="folder-structure" id="folder-structure-host"><div class="no-data"><i class="fas fa-folder-open"></i><p>Load tab to refresh.</p></div></div>
        </div>
    </div>

    <div id="tab-reports" class="tab-content">
        <div class="card">
            <div class="card-header"><h2>Batch reports</h2></div>
            <div class="stats-grid" id="stats-container"></div>
            <div class="filter-bar">
                <select id="report-batch">
                    <option value="">Select batch…</option>
                    <option value="2025-2026">2025-2026</option>
                    <option value="2026-2027">2026-2027</option>
                </select>
                <button type="button" class="btn" id="btn-load-report"><i class="fas fa-chart-bar"></i> Load</button>
            </div>
            <div id="report-content"></div>
        </div>
    </div>
</div>

<script>
const PROC = '../../../data/graduate_process.php';
const DL = '../../../data/download_graduation_pdf.php';

function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
    const pane = document.getElementById('tab-' + name);
    if (pane) pane.classList.add('active');
    const tabBtn = document.querySelector('.tab[data-tab="' + name + '"]');
    if (tabBtn) tabBtn.classList.add('active');
    if (name === 'list') loadGraduates();
    if (name === 'generate') loadStudentsForGeneration();
    if (name === 'folders') loadFolderStructure();
}

document.querySelectorAll('.tab[data-tab]').forEach(el => {
    el.addEventListener('click', () => showTab(el.getAttribute('data-tab')));
});
document.querySelectorAll('[data-tab-trigger]').forEach(el => {
    el.addEventListener('click', () => showTab(el.getAttribute('data-tab-trigger')));
});

function postForm(body) {
    return fetch(PROC, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
}

function loadGraduates() {
    const batch = document.getElementById('filter-batch').value;
    const major = document.getElementById('filter-major').value;
    const search = document.getElementById('search-graduate').value;
    postForm('action=list&batch=' + encodeURIComponent(batch) + '&major=' + encodeURIComponent(major) + '&search=' + encodeURIComponent(search))
        .then(r => r.text())
        .then(html => { document.getElementById('graduates-table').innerHTML = html; });
}

document.getElementById('btn-filter').addEventListener('click', loadGraduates);
document.getElementById('btn-reset').addEventListener('click', () => {
    document.getElementById('filter-batch').value = '';
    document.getElementById('filter-major').value = '';
    document.getElementById('search-graduate').value = '';
    loadGraduates();
});

function loadStudentsForGeneration() {
    postForm('action=get_eligible_students')
        .then(r => r.json())
        .then(res => {
            const sel = document.getElementById('gen-student');
            sel.innerHTML = '<option value="">Select student…</option>';
            (res.students || []).forEach(st => {
                const opt = document.createElement('option');
                opt.value = st.id;
                opt.textContent = (st.first_name + ' ' + st.last_name + ' (' + (st.student_id || st.student_number || '') + ')').trim();
                sel.appendChild(opt);
            });
        });
}

document.getElementById('gen-student').addEventListener('change', function () {
    const id = this.value;
    if (!id) {
        document.getElementById('gen-major').value = '';
        document.getElementById('gen-year-level').value = '';
        return;
    }
    postForm('action=get_student_details&student_id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.student) return;
            const s = res.student;
            document.getElementById('gen-major').value = s.major_name || '';
            document.getElementById('gen-year-level').value = s.year_level || '';
        });
});

document.getElementById('prospectus-form').addEventListener('submit', function (e) {
    e.preventDefault();
    const studentId = document.getElementById('gen-student').value;
    const batchYear = document.getElementById('gen-batch').value;
    if (!studentId || !batchYear) { alert('Select student and batch.'); return; }
    const btn = document.getElementById('generate-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Working…';
    postForm('action=generate_pdf&student_id=' + encodeURIComponent(studentId) + '&batch_year=' + encodeURIComponent(batchYear))
        .then(r => r.json())
        .then(res => {
            const box = document.getElementById('generation-status');
            box.style.display = 'block';
            box.className = res.success ? 'status-success' : 'status-error';
            box.textContent = res.message || (res.success ? 'Done.' : 'Error');
            if (res.success && res.pdf_url) {
                setTimeout(() => { window.open(res.pdf_url, '_blank'); }, 400);
            }
            loadGraduates();
        })
        .catch(() => alert('Request failed'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-file-pdf"></i> Generate PDF & record';
        });
});

function loadFolderStructure() {
    postForm('action=get_folder_structure')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                document.getElementById('folder-structure-host').innerHTML = res.html;
            } else {
                document.getElementById('folder-structure-host').innerHTML = '<div class="no-data"><i class="fas fa-exclamation-triangle"></i><p>Error loading folder structure: ' + res.message + '</p></div>';
            }
        })
        .catch(() => {
            document.getElementById('folder-structure-host').innerHTML = '<div class="no-data"><i class="fas fa-exclamation-triangle"></i><p>Failed to load folder structure</p></div>';
        });
}

function loadReports() {
    const batch = document.getElementById('report-batch').value;
    if (!batch) { alert('Pick a batch.'); return; }
    postForm('action=get_reports&batch=' + encodeURIComponent(batch))
        .then(r => r.text())
        .then(html => { document.getElementById('report-content').innerHTML = html; });
    postForm('action=get_stats&batch=' + encodeURIComponent(batch))
        .then(r => r.text())
        .then(html => { document.getElementById('stats-container').innerHTML = html; });
}
document.getElementById('btn-load-report').addEventListener('click', loadReports);

window.downloadProspectus = function (studentId) {
    window.open(DL + '?student_id=' + encodeURIComponent(studentId), '_blank');
};
window.viewGraduateDetails = function (studentId) {
    alert('Student ID: ' + studentId + '\nOpen the instructor Evaluation page for full prospectus history.');
};

document.addEventListener('DOMContentLoaded', () => {
  const urlParams  = new URLSearchParams(window.location.search);
  const fromEval   = urlParams.get('from_eval') === '1';
  const autoSid    = urlParams.get('student_id') || '';
  const autoBatch  = urlParams.get('batch')      || '';
  const autoPdfUrl = urlParams.get('pdf_url')    || '';

  // Clean URL immediately so refresh won't re-trigger
  history.replaceState({}, document.title, window.location.pathname);

  if (fromEval && autoBatch) {
    // Pre-set the batch filter to match the newly-graduated student's batch
    const batchSel = document.getElementById('filter-batch');
    if (batchSel) {
      let matched = false;
      for (let i = 0; i < batchSel.options.length; i++) {
        if (batchSel.options[i].value === autoBatch) { batchSel.value = autoBatch; matched = true; break; }
      }
      if (!matched) {
        // Batch year not in hardcoded list — add it dynamically
        const opt = document.createElement('option');
        opt.value = autoBatch;
        opt.textContent = autoBatch;
        batchSel.appendChild(opt);
        batchSel.value = autoBatch;
      }
    }
  } else {
    // Default: clear the pre-selected filter so ALL graduates are visible
    const batchSel = document.getElementById('filter-batch');
    if (batchSel) batchSel.value = '';
  }

  // Load the graduate list with the correct filter applied
  loadGraduates();

  // If arriving from evaluation, highlight the newly-graduated student and show a banner
  if (fromEval && autoSid) {
    const tryHighlight = (attempts) => {
      const rows = document.querySelectorAll('#graduates-table tr[data-student-id]');
      if (!rows.length && attempts < 15) { setTimeout(() => tryHighlight(attempts + 1), 400); return; }
      rows.forEach(row => {
        if (String(row.dataset.studentId) === String(autoSid)) {
          row.style.background = 'linear-gradient(135deg,#dcfce7,#bbf7d0)';
          row.style.transition = 'background 2s ease';
          row.scrollIntoView({ behavior: 'smooth', block: 'center' });
          setTimeout(() => { row.style.background = ''; }, 4000);
        }
      });
    };
    setTimeout(() => tryHighlight(0), 500);

    // Show success banner above the graduate table
    const tableHost = document.getElementById('graduates-table');
    if (tableHost) {
      const banner = document.createElement('div');
      banner.id = 'grad-confirm-banner';
      banner.style.cssText = 'padding:14px 18px;background:linear-gradient(135deg,#dcfce7,#bbf7d0);border:1.5px solid #10b981;border-radius:12px;font-size:13px;color:#166534;font-weight:600;margin-bottom:14px;display:flex;align-items:center;gap:10px;';
      const pdfLink = autoPdfUrl
        ? ' <a href="' + decodeURIComponent(autoPdfUrl) + '" target="_blank" style="margin-left:auto;padding:6px 14px;background:#16a34a;color:#fff;border-radius:20px;text-decoration:none;font-size:12px;white-space:nowrap;"><i class="fas fa-download"></i> Download PDF</a>'
        : '';
      banner.innerHTML = '<i class="fas fa-check-circle" style="font-size:20px;flex-shrink:0;"></i><span>Graduation confirmed! The student prospectus has been saved.</span>' + pdfLink;
      tableHost.insertAdjacentElement('beforebegin', banner);
      setTimeout(() => { if (banner.parentNode) banner.parentNode.removeChild(banner); }, 9000);
    }
  }
});
</script>
</body>
</html>
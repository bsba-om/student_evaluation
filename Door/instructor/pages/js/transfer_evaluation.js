/* ═══════════════════════════════════════════════════════════
   TRANSFER EVALUATION MODULE
   Handles the Transfer Student workflow:
   1. Ask for previously completed subjects from previous school
   2. Validate against the prospectus template
   3. Ask for current subject load
   4. Use both previous + current records for evaluation
═══════════════════════════════════════════════════════════ */

const TransferEvaluation = (() => {
  'use strict';

  /* ── State ── */
  let _student = null;
  let _subjects = [];           // all prospectus subjects
  let _previousSubjects = {};   // { subjectId: { grade, school, validated } }
  let _currentLoad = {};        // { subjectId: true }
  let _step = 1;                // 1=previous, 2=validate, 3=current load, 4=done
  let _onComplete = null;

  const STORAGE_KEY = 'transfer_eval_data';

   /* ── Init ── */
  function init(student, subjects, onComplete) {
    _student = student;
    _subjects = subjects || [];
    _onComplete = onComplete;
    _previousSubjects = {};
    _currentLoad = {};
    _step = 1;

    // Load saved data if exists
    _loadSaved();

    // Check if workflow already completed (both previous subjects and current load exist)
    if (Object.keys(_previousSubjects).length > 0 && Object.keys(_currentLoad).length > 0) {
      if (typeof onComplete === 'function') {
        setTimeout(() => onComplete(_previousSubjects, _currentLoad), 0);
      }
      return true; // skipped
    }

    // Show the transfer workflow modal
    _showWorkflow();
    return false; // modal shown
  }

  /* ── Session Persistence ── */
  function _loadSaved() {
    try {
      const raw = sessionStorage.getItem(`${STORAGE_KEY}_${_student.id}`);
      if (raw) {
        const data = JSON.parse(raw);
        _previousSubjects = normalizeObjectKeys(data.previousSubjects || {});

        const savedLoad = data.currentLoad || {};
        _currentLoad = {};

        if (Array.isArray(savedLoad)) {
          savedLoad.forEach(item => {
            const sid = extractSubjectId(item);
            if (sid != null) _currentLoad[String(sid)] = true;
          });
        } else if (savedLoad && typeof savedLoad === 'object') {
          Object.keys(savedLoad).forEach(key => {
            const value = savedLoad[key];
            if (value === true || value === 1 || value === '1' || value === 'true') {
              _currentLoad[String(key)] = true;
            } else if (value && typeof value === 'object') {
              const sid = extractSubjectId(value);
              if (sid != null) _currentLoad[String(sid)] = true;
            }
          });
        }

        if (Object.keys(_previousSubjects).length > 0 && Object.keys(_currentLoad).length > 0) {
          _step = 4; // already completed
        } else if (Object.keys(_previousSubjects).length > 0) {
          _step = 3; // skip to current load
        } else if (Object.keys(_currentLoad).length > 0) {
          _step = 4; // current load exists even without previous subjects
        }
      }
    } catch (e) {}
  }

  function normalizeObjectKeys(obj) {
    if (!obj || typeof obj !== 'object') return {};
    return Object.keys(obj).reduce((acc, key) => {
      acc[String(key)] = obj[key];
      return acc;
    }, {});
  }

  function extractSubjectId(item) {
    if (item == null) return null;
    if (typeof item === 'object') {
      if (item.id != null) return item.id;
      if (item.subject_id != null) return item.subject_id;
      return null;
    }
    return item;
  }

  function _save() {
    try {
      sessionStorage.setItem(`${STORAGE_KEY}_${_student.id}`, JSON.stringify({
        previousSubjects: _previousSubjects,
        currentLoad: _currentLoad
      }));
    } catch (e) {}
  }

  /* ── Get Transfer Data ── */
  function getPreviousSubjects() { return _previousSubjects; }
  function getCurrentLoad() { return _currentLoad; }

  function isSubjectCredited(subjectId) {
    const ps = _previousSubjects[String(subjectId)];
    return ps && ps.validated && ps.grade && parseFloat(ps.grade) <= 3.00;
  }

  function isInCurrentLoad(subjectId) {
    if (subjectId == null) return false;
    const sid = (typeof subjectId === 'object') ? (subjectId.id ?? subjectId.subject_id) : subjectId;
    return !!_currentLoad[String(sid)];
  }

  /* ── Show Workflow Modal ── */
  function _showWorkflow() {
    _removeModal();

    const full = `${_student.first_name}${_student.middle_name ? ' ' + _student.middle_name : ''} ${_student.last_name}${_student.suffix ? ' ' + _student.suffix : ''}`.trim();

    const html = `
    <div class="te-overlay" id="transferEvalModal">
      <div class="te-panel">
        <div class="te-header">
          <div class="te-header-icon"><i class="fas fa-exchange-alt"></i></div>
          <div>
            <div class="te-header-title">Transfer Student Setup</div>
            <div class="te-header-sub">${_escHtml(full)} — ${_escHtml(_student.major_name || '—')}</div>
          </div>
          <button class="te-close" onclick="TransferEvaluation.close()"><i class="fas fa-times"></i></button>
        </div>

        <div class="te-steps">
          <div class="te-step ${_step >= 1 ? 'te-step-active' : ''}" id="teStep1">
            <div class="te-step-num">1</div>
            <div class="te-step-label">Previous Subjects</div>
          </div>
          <div class="te-step-line ${_step >= 2 ? 'te-line-active' : ''}"></div>
          <div class="te-step ${_step >= 2 ? 'te-step-active' : ''}" id="teStep2">
            <div class="te-step-num">2</div>
            <div class="te-step-label">Validate</div>
          </div>
          <div class="te-step-line ${_step >= 3 ? 'te-line-active' : ''}"></div>
          <div class="te-step ${_step >= 3 ? 'te-step-active' : ''}" id="teStep3">
            <div class="te-step-num">3</div>
            <div class="te-step-label">Current Load</div>
          </div>
          <div class="te-step-line ${_step >= 4 ? 'te-line-active' : ''}"></div>
          <div class="te-step ${_step >= 4 ? 'te-step-active' : ''}" id="teStep4">
            <div class="te-step-num">4</div>
            <div class="te-step-label">Proceed</div>
          </div>
        </div>

        <div class="te-body" id="teBody">
          <!-- Dynamic content -->
        </div>

        <div class="te-footer" id="teFooter">
          <!-- Dynamic buttons -->
        </div>
      </div>
    </div>`;

    const div = document.createElement('div');
    div.innerHTML = html;
    document.body.appendChild(div.firstElementChild);

    _renderStep();
  }

  /* ── Render Current Step ── */
  function _renderStep() {
    // Update step indicators
    for (let i = 1; i <= 4; i++) {
      const stepEl = document.getElementById(`teStep${i}`);
      if (stepEl) stepEl.classList.toggle('te-step-active', i <= _step);
    }
    document.querySelectorAll('.te-step-line').forEach((line, idx) => {
      line.classList.toggle('te-line-active', idx + 2 <= _step);
    });

    const body = document.getElementById('teBody');
    const footer = document.getElementById('teFooter');
    if (!body || !footer) return;

    switch (_step) {
      case 1: _renderPreviousSubjects(body, footer); break;
      case 2: _renderValidation(body, footer); break;
      case 3: _renderCurrentLoad(body, footer); break;
      case 4: _renderComplete(body, footer); break;
    }
  }

   /* ── Step 1: Previous Subjects ── */
    function _renderPreviousSubjects(body, footer) {
      // Group subjects by year and semester
      const grouped = {};
      _subjects.forEach(s => {
        const key = `${s.year_level || '1st Year'}|${s.semester || '1st Semester'}`;
        if (!grouped[key]) grouped[key] = [];
        grouped[key].push(s);
      });

      let tableRows = '';
      const sortedKeys = Object.keys(grouped).sort((a, b) => {
        const [yA, sA] = a.split('|');
        const [yB, sB] = b.split('|');
        const yearOrder = { '1st Year': 1, '2nd Year': 2, '3rd Year': 3, '4th Year': 4, 'Bridging': 5 };
        const semesterOrder = { '1st Semester': 1, '2nd Semester': 2, 'Summer': 3 };
        
        const yearDiff = (yearOrder[yA] || 99) - (yearOrder[yB] || 99);
        if (yearDiff !== 0) return yearDiff;
        
        return (semesterOrder[sA] || 99) - (semesterOrder[sB] || 99);
      });

    sortedKeys.forEach(key => {
      const [year, sem] = key.split('|');
      tableRows += `<tr class="te-group-header"><td colspan="5" style="background:linear-gradient(135deg,var(--gold-d),var(--gold));color:#fff;font-weight:700;padding:8px 12px;font-size:11px;">${_escHtml(year)} — ${_escHtml(sem)}</td></tr>`;
      grouped[key].forEach(s => {
        const prev = _previousSubjects[s.id] || {};
        const selected = Object.prototype.hasOwnProperty.call(_previousSubjects, s.id);
        const checked = selected ? 'checked' : '';
        const gradeDisabled = !selected || !prev.grade;
        tableRows += `
        <tr class="te-subject-row" id="te-row-${s.id}">
          <td style="text-align:center;padding:6px;">
            <input type="checkbox" class="te-prev-check" data-sid="${s.id}" ${checked}
              onchange="TransferEvaluation.togglePrevSubject(${s.id}, this.checked)"
              style="width:16px;height:16px;accent-color:var(--blue);cursor:pointer;">
          </td>
          <td style="font-weight:700;font-size:11px;padding:6px 8px;">${_escHtml(s.subject_code)}</td>
          <td style="font-size:10px;padding:6px 8px;">${_escHtml(s.subject_name)}</td>
          <td style="text-align:center;font-weight:600;padding:6px;">${parseFloat(s.units) || 0}</td>
          <td style="padding:6px 8px;">
            <input type="number" class="te-grade-inp" id="te-grade-${s.id}"
              value="${prev.grade || ''}" min="1" max="5" step="0.01" placeholder="—"
              style="width:60px;padding:4px 6px;border:1.5px solid var(--border);border-radius:6px;font-size:11px;font-weight:700;text-align:center;font-family:'Poppins',sans-serif;"
              ${gradeDisabled ? 'disabled' : ''}
              onchange="TransferEvaluation.updatePrevGrade(${s.id}, this.value)">
          </td>
        </tr>`;
      });
    });

    body.innerHTML = `
    <div style="margin-bottom:14px;">
      <div style="padding:14px 18px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid var(--blue-b);border-radius:10px;">
        <div style="font-size:13px;font-weight:700;color:#1e40af;margin-bottom:4px;">
          <i class="fas fa-info-circle" style="margin-right:6px;"></i>Step 1: Previous School Subjects
        </div>
        <div style="font-size:11px;color:#1d4ed8;line-height:1.6;">
          Check the subjects the student has already completed at their previous school and enter the corresponding grade.
          Only subjects with a passing grade (1.00–3.00) will be credited.
        </div>
      </div>
    </div>
    <div style="margin-bottom:10px;display:flex;gap:8px;align-items:center;">
      <input type="text" id="tePrevSearch" placeholder="Search subjects..." oninput="TransferEvaluation.filterPrevSubjects()"
        style="flex:1;padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;font-family:'Poppins',sans-serif;">
      <span style="font-size:11px;color:var(--muted);" id="tePrevCount">${Object.keys(_previousSubjects).filter(k => _previousSubjects[k].grade).length} selected</span>
    </div>
    <div style="max-height:400px;overflow-y:auto;border:1px solid var(--border);border-radius:10px;">
      <table style="width:100%;border-collapse:collapse;font-size:11px;">
        <thead>
          <tr style="background:var(--cream2);">
            <th style="width:36px;padding:8px;text-align:center;font-size:10px;font-weight:700;color:var(--gold-d);">✓</th>
            <th style="padding:8px;text-align:left;font-size:10px;font-weight:700;color:var(--gold-d);">Code</th>
            <th style="padding:8px;text-align:left;font-size:10px;font-weight:700;color:var(--gold-d);">Subject</th>
            <th style="width:50px;padding:8px;text-align:center;font-size:10px;font-weight:700;color:var(--gold-d);">Units</th>
            <th style="width:80px;padding:8px;text-align:center;font-size:10px;font-weight:700;color:var(--gold-d);">Grade</th>
          </tr>
        </thead>
        <tbody>${tableRows}</tbody>
      </table>
    </div>
    <div style="margin-top:10px;padding:8px 12px;background:var(--cream2);border-radius:8px;display:flex;justify-content:space-between;align-items:center;">
      <span style="font-size:10px;color:var(--muted);">
        <i class="fas fa-lightbulb" style="color:var(--amber);margin-right:4px;"></i>
        Tip: Only check subjects the student passed at their previous institution
      </span>
      <span style="font-size:11px;font-weight:700;color:var(--gold-d);" id="tePrevUnits">0 units credited</span>
    </div>`;

    footer.innerHTML = `
    <button class="te-btn-cancel" onclick="TransferEvaluation.close()"><i class="fas fa-times"></i> Cancel</button>
    <button class="te-btn-skip" onclick="TransferEvaluation.skipPrevious()"><i class="fas fa-forward"></i> Skip (No Previous Subjects)</button>
    <button class="te-btn-next" onclick="TransferEvaluation.goToStep(2)"><i class="fas fa-arrow-right"></i> Next: Validate</button>`;

    _updatePrevCount();
  }

  /* ── Step 2: Validation ── */
  function _renderValidation(body, footer) {
    const credited = Object.keys(_previousSubjects).filter(k => {
      const ps = _previousSubjects[k];
      return ps.grade && parseFloat(ps.grade) <= 3.00;
    });
    const failed = Object.keys(_previousSubjects).filter(k => {
      const ps = _previousSubjects[k];
      return ps.grade && parseFloat(ps.grade) > 3.00;
    });

    let creditedHtml = '';
    credited.forEach(sid => {
      const sub = _subjects.find(s => s.id == sid);
      if (!sub) return;
      const grade = parseFloat(_previousSubjects[sid].grade).toFixed(2);
      creditedHtml += `
      <div style="padding:10px 14px;background:var(--green-l);border:1px solid var(--green-b);border-radius:8px;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-check-circle" style="color:var(--green);font-size:16px;"></i>
        <div style="flex:1;">
          <div style="font-size:12px;font-weight:700;color:#166534;">${_escHtml(sub.subject_code)}</div>
          <div style="font-size:10px;color:#15803d;">${_escHtml(sub.subject_name)}</div>
        </div>
        <div style="font-size:12px;font-weight:700;color:#166534;">${grade}</div>
        <span style="padding:3px 8px;background:var(--green);color:#fff;border-radius:10px;font-size:9px;font-weight:700;">CREDITED</span>
      </div>`;
    });

    let failedHtml = '';
    failed.forEach(sid => {
      const sub = _subjects.find(s => s.id == sid);
      if (!sub) return;
      const grade = parseFloat(_previousSubjects[sid].grade).toFixed(2);
      failedHtml += `
      <div style="padding:10px 14px;background:var(--red-l);border:1px solid var(--red-b);border-radius:8px;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-times-circle" style="color:var(--red);font-size:16px;"></i>
        <div style="flex:1;">
          <div style="font-size:12px;font-weight:700;color:#991b1b;">${_escHtml(sub.subject_code)}</div>
          <div style="font-size:10px;color:#b91c1c;">${_escHtml(sub.subject_name)}</div>
        </div>
        <div style="font-size:12px;font-weight:700;color:#991b1b;">${grade}</div>
        <span style="padding:3px 8px;background:var(--red);color:#fff;border-radius:10px;font-size:9px;font-weight:700;">NOT CREDITED</span>
      </div>`;
    });

    const totalCreditedUnits = credited.reduce((acc, sid) => {
      const sub = _subjects.find(s => s.id == sid);
      return acc + (sub ? (parseFloat(sub.units) || 0) : 0);
    }, 0);

    body.innerHTML = `
    <div style="margin-bottom:14px;">
      <div style="padding:14px 18px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid var(--green-b);border-radius:10px;">
        <div style="font-size:13px;font-weight:700;color:#166534;margin-bottom:4px;">
          <i class="fas fa-clipboard-check" style="margin-right:6px;"></i>Step 2: Validation Results
        </div>
        <div style="font-size:11px;color:#15803d;line-height:1.6;">
          Subjects with grades 1.00–3.00 are credited. Failed/conditional grades are not credited and must be retaken.
        </div>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px;">
      <div style="text-align:center;padding:14px;background:var(--green-l);border-radius:10px;border:1px solid var(--green-b);">
        <div style="font-size:24px;font-weight:800;color:var(--green);font-family:'Playfair Display',serif;">${credited.length}</div>
        <div style="font-size:9px;color:#166534;text-transform:uppercase;font-weight:600;">Credited</div>
      </div>
      <div style="text-align:center;padding:14px;background:var(--red-l);border-radius:10px;border:1px solid var(--red-b);">
        <div style="font-size:24px;font-weight:800;color:var(--red);font-family:'Playfair Display',serif;">${failed.length}</div>
        <div style="font-size:9px;color:#991b1b;text-transform:uppercase;font-weight:600;">Not Credited</div>
      </div>
      <div style="text-align:center;padding:14px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:10px;border:1px solid var(--blue-b);">
        <div style="font-size:24px;font-weight:800;color:var(--blue);font-family:'Playfair Display',serif;">${totalCreditedUnits}</div>
        <div style="font-size:9px;color:#1e40af;text-transform:uppercase;font-weight:600;">Units Credited</div>
      </div>
    </div>
    ${credited.length ? `<div style="font-size:12px;font-weight:700;color:#166534;margin-bottom:8px;"><i class="fas fa-check" style="margin-right:6px;"></i>Credited Subjects (${credited.length})</div><div style="display:flex;flex-direction:column;gap:6px;margin-bottom:16px;max-height:200px;overflow-y:auto;">${creditedHtml}</div>` : ''}
    ${failed.length ? `<div style="font-size:12px;font-weight:700;color:#991b1b;margin-bottom:8px;"><i class="fas fa-times" style="margin-right:6px;"></i>Not Credited (${failed.length})</div><div style="display:flex;flex-direction:column;gap:6px;max-height:150px;overflow-y:auto;">${failedHtml}</div>` : ''}
    ${!credited.length && !failed.length ? '<div style="text-align:center;padding:30px;color:var(--muted);"><i class="fas fa-inbox" style="font-size:32px;opacity:.3;display:block;margin-bottom:10px;"></i>No previous subjects were selected</div>' : ''}`;

    footer.innerHTML = `
    <button class="te-btn-back" onclick="TransferEvaluation.goToStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
    <button class="te-btn-next" onclick="TransferEvaluation.goToStep(3)"><i class="fas fa-arrow-right"></i> Next: Current Load</button>`;
  }

   /* ── Step 3: Current Subject Load ── */
    function _renderCurrentLoad(body, footer) {
      // Show only subjects NOT credited from previous school
      const remaining = _subjects.filter(s => !isSubjectCredited(s.id));

      const grouped = {};
      remaining.forEach(s => {
        const key = `${s.year_level || '1st Year'}|${s.semester || '1st Semester'}`;
        if (!grouped[key]) grouped[key] = [];
        grouped[key].push(s);
      });

      let tableRows = '';
      const sortedKeys = Object.keys(grouped).sort((a, b) => {
        const [yA, sA] = a.split('|');
        const [yB, sB] = b.split('|');
        const yearOrder = { '1st Year': 1, '2nd Year': 2, '3rd Year': 3, '4th Year': 4, 'Bridging': 5 };
        const semesterOrder = { '1st Semester': 1, '2nd Semester': 2, 'Summer': 3 };
        
        const yearDiff = (yearOrder[yA] || 99) - (yearOrder[yB] || 99);
        if (yearDiff !== 0) return yearDiff;
        
        return (semesterOrder[sA] || 99) - (semesterOrder[sB] || 99);
      });

    sortedKeys.forEach(key => {
      const [year, sem] = key.split('|');
      tableRows += `<tr class="te-group-header"><td colspan="4" style="background:linear-gradient(135deg,var(--gold-d),var(--gold));color:#fff;font-weight:700;padding:8px 12px;font-size:11px;">${_escHtml(year)} — ${_escHtml(sem)}</td></tr>`;
      grouped[key].forEach(s => {
        const inLoad = _currentLoad[s.id] || false;
        tableRows += `
        <tr class="te-subject-row" id="te-load-row-${s.id}">
          <td style="text-align:center;padding:6px;">
            <input type="checkbox" class="te-load-check" data-sid="${s.id}" ${inLoad ? 'checked' : ''}
              onchange="TransferEvaluation.toggleCurrentLoad(${s.id}, this.checked)"
              style="width:16px;height:16px;accent-color:var(--gold-d);cursor:pointer;">
          </td>
          <td style="font-weight:700;font-size:11px;padding:6px 8px;">${_escHtml(s.subject_code)}</td>
          <td style="font-size:10px;padding:6px 8px;">${_escHtml(s.subject_name)}</td>
          <td style="text-align:center;font-weight:600;padding:6px;">${parseFloat(s.units) || 0}</td>
        </tr>`;
      });
    });

    body.innerHTML = `
    <div style="margin-bottom:14px;">
      <div style="padding:14px 18px;background:linear-gradient(135deg,#fef3c7,#fde68a);border:1px solid var(--amber-b);border-radius:10px;">
        <div style="font-size:13px;font-weight:700;color:#92400e;margin-bottom:4px;">
          <i class="fas fa-book-open" style="margin-right:6px;"></i>Step 3: Current Subject Load
        </div>
        <div style="font-size:11px;color:#a16207;line-height:1.6;">
          Select the subjects the student is currently enrolled in this semester. Previously credited subjects are excluded.
        </div>
      </div>
    </div>
    <div style="margin-bottom:10px;display:flex;gap:8px;align-items:center;">
      <input type="text" id="teLoadSearch" placeholder="Search remaining subjects..." oninput="TransferEvaluation.filterLoadSubjects()"
        style="flex:1;padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;font-family:'Poppins',sans-serif;">
      <span style="font-size:11px;color:var(--muted);" id="teLoadCount">${Object.keys(_currentLoad).filter(k => _currentLoad[k]).length} selected</span>
    </div>
    <div style="max-height:400px;overflow-y:auto;border:1px solid var(--border);border-radius:10px;">
      <table style="width:100%;border-collapse:collapse;font-size:11px;">
        <thead>
          <tr style="background:var(--cream2);">
            <th style="width:36px;padding:8px;text-align:center;font-size:10px;font-weight:700;color:var(--gold-d);">✓</th>
            <th style="padding:8px;text-align:left;font-size:10px;font-weight:700;color:var(--gold-d);">Code</th>
            <th style="padding:8px;text-align:left;font-size:10px;font-weight:700;color:var(--gold-d);">Subject</th>
            <th style="width:50px;padding:8px;text-align:center;font-size:10px;font-weight:700;color:var(--gold-d);">Units</th>
          </tr>
        </thead>
        <tbody>${tableRows}</tbody>
      </table>
    </div>
    <div style="margin-top:10px;padding:8px 12px;background:var(--cream2);border-radius:8px;display:flex;justify-content:space-between;align-items:center;">
      <span style="font-size:10px;color:var(--muted);">
        <i class="fas fa-info-circle" style="color:var(--blue);margin-right:4px;"></i>
        Select subjects the student is currently taking this semester
      </span>
      <span style="font-size:11px;font-weight:700;color:var(--gold-d);" id="teLoadUnits">0 units loaded</span>
    </div>`;

    footer.innerHTML = `
    <button class="te-btn-back" onclick="TransferEvaluation.goToStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
    <button class="te-btn-next" onclick="TransferEvaluation.goToStep(4)"><i class="fas fa-check-circle"></i> Finish Setup</button>`;

    _updateLoadCount();
  }

  /* ── Step 4: Complete ── */
  function _renderComplete(body, footer) {
    const creditedCount = Object.keys(_previousSubjects).filter(k => isSubjectCredited(k)).length;
    const loadCount = Object.keys(_currentLoad).filter(k => _currentLoad[k]).length;
    const creditedUnits = Object.keys(_previousSubjects).filter(k => isSubjectCredited(k)).reduce((acc, sid) => {
      const sub = _subjects.find(s => s.id == sid);
      return acc + (sub ? (parseFloat(sub.units) || 0) : 0);
    }, 0);
    const loadUnits = Object.keys(_currentLoad).filter(k => _currentLoad[k]).reduce((acc, sid) => {
      const sub = _subjects.find(s => s.id == sid);
      return acc + (sub ? (parseFloat(sub.units) || 0) : 0);
    }, 0);

    body.innerHTML = `
    <div style="text-align:center;padding:30px 20px;">
      <div style="width:72px;height:72px;margin:0 auto 18px;background:linear-gradient(135deg,var(--green-l),#bbf7d0);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32px;color:var(--green);">
        <i class="fas fa-check-circle"></i>
      </div>
      <div style="font-size:20px;font-weight:800;color:var(--dark);font-family:'Playfair Display',serif;margin-bottom:8px;">Transfer Setup Complete</div>
      <div style="font-size:13px;color:var(--muted);margin-bottom:24px;">The student's academic standing has been configured. You can now proceed to the evaluation prospectus.</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;max-width:360px;margin:0 auto;">
        <div style="padding:16px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:12px;border:1px solid var(--blue-b);">
          <div style="font-size:28px;font-weight:800;color:var(--blue);font-family:'Playfair Display',serif;">${creditedCount}</div>
          <div style="font-size:9px;color:#1e40af;text-transform:uppercase;font-weight:600;">Credited (${creditedUnits} units)</div>
        </div>
        <div style="padding:16px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:12px;border:1px solid var(--amber-b);">
          <div style="font-size:28px;font-weight:800;color:var(--amber);font-family:'Playfair Display',serif;">${loadCount}</div>
          <div style="font-size:9px;color:#92400e;text-transform:uppercase;font-weight:600;">Current Load (${loadUnits} units)</div>
        </div>
      </div>
    </div>`;

    footer.innerHTML = `
    <button class="te-btn-back" onclick="TransferEvaluation.goToStep(3)"><i class="fas fa-arrow-left"></i> Back</button>
    <button class="te-btn-confirm" onclick="TransferEvaluation.complete()"><i class="fas fa-scroll"></i> Open Evaluation Prospectus</button>`;
  }

  /* ── Navigation ── */
  function goToStep(step) {
    _step = step;
    _save();
    _renderStep();
  }

  function skipPrevious() {
    _previousSubjects = {};
    _step = 3;
    _save();
    _renderStep();
  }

  /* ── Toggle Handlers ── */
  function togglePrevSubject(sid, checked) {
    const gradeInp = document.getElementById(`te-grade-${sid}`);
    if (checked) {
      if (!_previousSubjects[sid]) _previousSubjects[sid] = {};
      _previousSubjects[sid].validated = true;
      if (gradeInp) gradeInp.disabled = false;
    } else {
      delete _previousSubjects[sid];
      if (gradeInp) { gradeInp.disabled = true; gradeInp.value = ''; }
    }
    _save();
    _updatePrevCount();
  }

  function updatePrevGrade(sid, value) {
    if (!_previousSubjects[sid]) _previousSubjects[sid] = {};
    _previousSubjects[sid].grade = value;
    _previousSubjects[sid].validated = true;
    _save();
    _updatePrevCount();
  }

  function toggleCurrentLoad(sid, checked) {
    const key = String(sid);
    if (checked) {
      _currentLoad[key] = true;
    } else {
      delete _currentLoad[key];
    }
    _save();
    _updateLoadCount();
  }

  /* ── Count Updaters ── */
  function _updatePrevCount() {
    const count = Object.keys(_previousSubjects).length;
    const units = Object.keys(_previousSubjects).filter(k => {
      const ps = _previousSubjects[k];
      return ps.grade && parseFloat(ps.grade) <= 3.00;
    }).reduce((acc, sid) => {
      const sub = _subjects.find(s => s.id == sid);
      return acc + (sub ? (parseFloat(sub.units) || 0) : 0);
    }, 0);
    const countEl = document.getElementById('tePrevCount');
    const unitsEl = document.getElementById('tePrevUnits');
    if (countEl) countEl.textContent = `${count} selected`;
    if (unitsEl) unitsEl.textContent = `${units} units credited`;
  }

  function _updateLoadCount() {
    const count = Object.keys(_currentLoad).filter(k => _currentLoad[k]).length;
    const units = Object.keys(_currentLoad).filter(k => _currentLoad[k]).reduce((acc, sid) => {
      const sub = _subjects.find(s => s.id == sid);
      return acc + (sub ? (parseFloat(sub.units) || 0) : 0);
    }, 0);
    const countEl = document.getElementById('teLoadCount');
    const unitsEl = document.getElementById('teLoadUnits');
    if (countEl) countEl.textContent = `${count} selected`;
    if (unitsEl) unitsEl.textContent = `${units} units loaded`;
  }

  /* ── Search Filters ── */
  function filterPrevSubjects() {
    const q = (document.getElementById('tePrevSearch')?.value || '').toLowerCase();
    document.querySelectorAll('.te-subject-row').forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(q) ? '' : 'none';
    });
  }

  function filterLoadSubjects() {
    const q = (document.getElementById('teLoadSearch')?.value || '').toLowerCase();
    document.querySelectorAll('.te-subject-row').forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(q) ? '' : 'none';
    });
  }

  /* ── Complete ── */
  function complete() {
    _save();
    _removeModal();
    if (typeof _onComplete === 'function') {
      _onComplete(_previousSubjects, _currentLoad);
    }
  }

  /* ── Close ── */
  function close() {
    _removeModal();
  }

  function _removeModal() {
    const modal = document.getElementById('transferEvalModal');
    if (modal) modal.remove();
  }

  /* ── Helpers ── */
  function _escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

   /* ── Apply Transfer Credits to Grade Map ── */
   function applyCreditsToGradeMap(gradeMap) {
     Object.keys(_previousSubjects).forEach(sid => {
       const ps = _previousSubjects[sid];
       if (ps.grade && ps.validated && parseFloat(ps.grade) <= 3.00) {
         gradeMap[sid] = parseFloat(ps.grade);
       }
     });
     return gradeMap;
   }

   /* ── Get Filtered Subjects for Prospectus ── */
   function getProspectusSubjects() {
     // Return only subjects in current load for transfer students
     if (Object.keys(_currentLoad).length > 0) {
       return _subjects.filter(s => _currentLoad[String(s.id)] || _currentLoad[s.id]);
     }
     return _subjects;
   }

  /* ── Public API ── */
  return {
    init,
    close,
    goToStep,
    skipPrevious,
    togglePrevSubject,
    updatePrevGrade,
    toggleCurrentLoad,
    filterPrevSubjects,
    filterLoadSubjects,
    complete,
    getPreviousSubjects,
    getCurrentLoad,
    isSubjectCredited,
    isInCurrentLoad,
    applyCreditsToGradeMap,
    getProspectusSubjects
  };
})();
/* ═══════════════════════════════════════════════════════════
   NON-IBM EVALUATION MODULE
   Handles the Non-IBM Student workflow:
   1. Restrict evaluation to loaded subjects only
   2. Auto-load bridging subjects for 1st Year 1st Semester
   3. Bridging subjects replace regular subjects with unmet prereqs
   4. Only after completing bridging can student proceed
═══════════════════════════════════════════════════════════ */

const NonIBMEvaluation = (() => {
  'use strict';

  /* ── State ── */
  let _student = null;
  let _subjects = [];             // all prospectus subjects
  let _subjectLoad = {};          // { subjectId: true } — subjects the student is allowed to take
  let _bridgingSubjects = [];     // auto-loaded bridging subjects for 1st Year 1st Sem
  let _bridgingComplete = false;
  let _onComplete = null;
  let _step = 1;                  // 1=load bridging subjects, 2=load regular subjects, 3=done
  let _setupComplete = false;     // flag to track if the Non-IBM setup is fully completed

  const STORAGE_KEY = 'non_ibm_eval_data';

   /* ── Init ── */
  function init(student, subjects, onComplete) {
    _student = student;
    _subjects = subjects || [];
    _onComplete = onComplete;
    _subjectLoad = {};
    _bridgingSubjects = [];
    _bridgingComplete = false;
    _step = 1;

     _loadSaved();

     // If subject load already exists AND setup is complete, skip modal and call onComplete immediately
     if (Object.keys(_subjectLoad).length > 0 && _setupComplete) {
       // Ensure bridging subjects list is populated for applyRestrictions
       if (_bridgingSubjects.length === 0) {
         _bridgingSubjects = _subjects.filter(s => (s.year_level || '').toLowerCase() === 'bridging');
       }
       if (typeof onComplete === 'function') {
         // Use setTimeout to avoid re-entrancy issues
         setTimeout(() => onComplete(_subjectLoad, _bridgingSubjects), 0);
       }
       return true; // skipped
     }

     _showWorkflow();
     return false; // modal shown
  }

  /* ── Session Persistence ── */
   function _loadSaved() {
     try {
       const raw = sessionStorage.getItem(`${STORAGE_KEY}_${_student.id}`);
       if (raw) {
         const data = JSON.parse(raw);
         _subjectLoad = data.subjectLoad || {};
         _bridgingComplete = data.bridgingComplete || false;
         _setupComplete = data.setupComplete || false;
         if (Object.keys(_subjectLoad).length > 0) {
           _step = 3;
         }
       }
     } catch (e) {}
   }

   function _save() {
     try {
       sessionStorage.setItem(`${STORAGE_KEY}_${_student.id}`, JSON.stringify({
         subjectLoad: _subjectLoad,
         bridgingComplete: _bridgingComplete,
         setupComplete: _setupComplete
       }));
     } catch (e) {}
   }

  /* ── Getters ── */
  function getSubjectLoad() { return _subjectLoad; }
  function isBridgingComplete() { return _bridgingComplete; }

  function isSubjectAllowed(subjectId) {
    return !!_subjectLoad[subjectId];
  }

  /* ── Detect 1st Year 1st Semester ── */
  function _isFirstYearFirstSem() {
    const yl = (_student.year_level || '').toLowerCase();
    return yl.includes('1st year') && (yl.includes('1st sem') || !yl.includes('2nd sem'));
  }

  /* ── Identify Bridging Subjects ── */
  function _identifyBridgingSubjects(gradeMap) {
    const bridging = _subjects.filter(s => (s.year_level || '').toLowerCase() === 'bridging');
    const firstYearFirstSem = _subjects.filter(s =>
      (s.year_level || '').includes('1st Year') &&
      (s.semester || '').includes('1st')
    );

    if (!_isFirstYearFirstSem()) return [];

    // Find regular subjects with unmet prerequisites
    const unmetPrereqSubjects = [];
    firstYearFirstSem.forEach(sub => {
      const prereq = (sub.prerequisite || '').trim().toUpperCase();
      if (!prereq) return;

      // Check if prerequisite is passed
      const prereqSub = _subjects.find(s => (s.subject_code || '').trim().toUpperCase() === prereq);
      if (prereqSub) {
        const grade = gradeMap ? gradeMap[prereqSub.id] : null;
        const passed = grade != null && parseFloat(grade) <= 3.00;
        if (!passed) {
          unmetPrereqSubjects.push(sub);
        }
      }
    });

    // Find bridging subjects that correspond to unmet prerequisites
    const bridgingForUnmet = bridging.filter(bs => {
      const bridgingFor = (bs.bridging_for || '').trim().toUpperCase();
      return unmetPrereqSubjects.some(us =>
        (us.subject_code || '').trim().toUpperCase() === bridgingFor ||
        (us.prerequisite || '').trim().toUpperCase() === bridgingFor
      );
    });

    // If no specific bridging matches, return all bridging subjects for 1st year 1st sem
    return bridgingForUnmet.length > 0 ? bridgingForUnmet : bridging;
  }

  /* ── Show Workflow Modal ── */
  function _showWorkflow() {
    _removeModal();

    const full = `${_student.first_name}${_student.middle_name ? ' ' + _student.middle_name : ''} ${_student.last_name}${_student.suffix ? ' ' + _student.suffix : ''}`.trim();
    const isFirstYearFirstSem = _isFirstYearFirstSem();

    const html = `
    <div class="nim-overlay" id="nonIBMEvalModal">
      <div class="nim-panel">
        <div class="nim-header">
          <div class="nim-header-icon"><i class="fas fa-user-tag"></i></div>
          <div>
            <div class="nim-header-title">Non-IBM Student Setup</div>
            <div class="nim-header-sub">${_escHtml(full)} — ${_escHtml(_student.major_name || '—')}</div>
          </div>
          <button class="nim-close" onclick="NonIBMEvaluation.close()"><i class="fas fa-times"></i></button>
        </div>

        <div class="nim-steps">
           <div class="nim-step ${_step >= 1 ? 'nim-step-active' : ''}" id="nimStep1">
             <div class="nim-step-num">1</div>
             <div class="nim-step-label">Bridging</div>
           </div>
          <div class="nim-step-line ${_step >= 2 ? 'nim-line-active' : ''}"></div>
           <div class="nim-step ${_step >= 2 ? 'nim-step-active' : ''}" id="nimStep2">
             <div class="nim-step-num">2</div>
             <div class="nim-step-label">Regular</div>
           </div>
          <div class="nim-step-line ${_step >= 3 ? 'nim-line-active' : ''}"></div>
          <div class="nim-step ${_step >= 3 ? 'nim-step-active' : ''}" id="nimStep3">
            <div class="nim-step-num">3</div>
            <div class="nim-step-label">Proceed</div>
          </div>
        </div>

        <div class="nim-body" id="nimBody">
          <!-- Dynamic content -->
        </div>

        <div class="nim-footer" id="nimFooter">
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
    for (let i = 1; i <= 3; i++) {
      const stepEl = document.getElementById(`nimStep${i}`);
      if (stepEl) stepEl.classList.toggle('nim-step-active', i <= _step);
    }
    document.querySelectorAll('.nim-step-line').forEach((line, idx) => {
      line.classList.toggle('nim-line-active', idx + 2 <= _step);
    });

    const body = document.getElementById('nimBody');
    const footer = document.getElementById('nimFooter');
    if (!body || !footer) return;

      switch (_step) {
        case 1: _renderBridgingLoad(body, footer); break;
        case 2: _renderRegularLoad(body, footer); break;
        case 3: _renderComplete(body, footer); break;
      }
  }

   /* ── Step 1: Bridging Subject Load ── */
   function _renderBridgingLoad(body, footer) {
    const isFirstYearFirstSem = _isFirstYearFirstSem();
    const bridging = _subjects.filter(s => (s.year_level || '').toLowerCase() === 'bridging');

    let tableRows = '';
    if (bridging.length === 0) {
      tableRows = `<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--muted);font-size:11px;">No bridging subjects configured</td></tr>`;
    } else {
      bridging.forEach(s => {
        const inLoad = _subjectLoad[s.id] || false;
        tableRows += `
        <tr class="nim-subject-row" id="nim-row-${s.id}">
          <td style="text-align:center;padding:6px;">
            <input type="checkbox" class="nim-load-check" data-sid="${s.id}" ${inLoad ? 'checked' : ''}
              onchange="NonIBMEvaluation.toggleSubject(${s.id}, this.checked)"
              style="width:16px;height:16px;accent-color:var(--amber);cursor:pointer;">
          </td>
          <td style="font-weight:700;font-size:11px;padding:6px 8px;">${_escHtml(s.subject_code)}</td>
          <td style="font-size:10px;padding:6px 8px;">${_escHtml(s.subject_name)}</td>
          <td style="text-align:center;font-weight:600;padding:6px;">${parseFloat(s.units) || 0}</td>
        </tr>`;
      });
    }

    body.innerHTML = `
    <div style="margin-bottom:14px;">
      <div style="padding:14px 18px;background:linear-gradient(135deg,#fef3c7,#fde68a);border:1px solid var(--amber-b);border-radius:10px;">
        <div style="font-size:13px;font-weight:700;color:#92400e;margin-bottom:4px;">
          <i class="fas fa-exchange-alt" style="margin-right:6px;"></i>Step 1: Bridging Subjects
        </div>
        <div style="font-size:11px;color:#a16207;line-height:1.6;">
          ${isFirstYearFirstSem ? 'For 1st Year 1st Semester Non-IBM students, select the required bridging subjects that must be completed before proceeding to regular subjects.' : 'Select any bridging subjects the student needs to take.'}
        </div>
      </div>
    </div>
    <div style="margin-bottom:10px;display:flex;gap:8px;align-items:center;">
      <input type="text" id="nimLoadSearch" placeholder="Search bridging subjects..." oninput="NonIBMEvaluation.filterSubjects()"
        style="flex:1;padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;font-family:'Poppins',sans-serif;">
      <span style="font-size:11px;color:var(--muted);" id="nimLoadCount">${Object.keys(_subjectLoad).filter(k => _subjectLoad[k]).length} selected</span>
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
        <i class="fas fa-info-circle" style="color:var(--amber);margin-right:4px;"></i>
        Select bridging subjects that must be completed first
      </span>
      <span style="font-size:11px;font-weight:700;color:var(--gold-d);" id="nimLoadUnits">0 units</span>
    </div>`;

    footer.innerHTML = `
    <button class="nim-btn-back" onclick="NonIBMEvaluation.goToStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
    <button class="nim-btn-next" onclick="NonIBMEvaluation.goToStep(2)"><i class="fas fa-arrow-right"></i> Next: Regular Subjects</button>`;

    _updateLoadCount();
   }

    /* ── Step 2: All Subjects (Regular + Bridging) ── */
    function _renderRegularLoad(body, footer) {
     const isFirstYearFirstSem = _isFirstYearFirstSem();

     // Group ALL subjects by year/semester (including bridging)
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
       const semOrder = { '1st Semester': 1, '2nd Semester': 2, 'Summer': 3 };
       const yearDiff = (yearOrder[yA] || 99) - (yearOrder[yB] || 99);
       if (yearDiff !== 0) return yearDiff;
       return (semOrder[sA] || 99) - (semOrder[sB] || 99);
     });

     sortedKeys.forEach(key => {
       const [year, sem] = key.split('|');
       tableRows += `<tr class="nim-group-header"><td colspan="4" style="background:linear-gradient(135deg,var(--gold-d),var(--gold));color:#fff;font-weight:700;padding:8px 12px;font-size:11px;">${_escHtml(year)} — ${_escHtml(sem)}</td></tr>`;
       grouped[key].forEach(s => {
         const isBridging = (s.year_level || '').toLowerCase() === 'bridging';
         const inLoad = _subjectLoad[s.id] || false;
         const isDisabled = isBridging; // Bridging subjects are auto-selected from step 1 and can't be unchecked
         tableRows += `
         <tr class="nim-subject-row" id="nim-row-${s.id}" style="${isDisabled ? 'opacity:0.7' : ''}">
           <td style="text-align:center;padding:6px;">
             <input type="checkbox" class="nim-load-check" data-sid="${s.id}" ${inLoad ? 'checked' : ''}
               ${isDisabled ? 'disabled' : ''}
               onchange="NonIBMEvaluation.toggleSubject(${s.id}, this.checked)"
               style="width:16px;height:16px;accent-color:var(--amber);cursor:${isDisabled ? 'not-allowed' : 'pointer'};">
           </td>
           <td style="font-weight:700;font-size:11px;padding:6px 8px;">${_escHtml(s.subject_code)}${isBridging ? ' <span style="color:var(--amber);font-size:9px;">(bridging)</span>' : ''}</td>
           <td style="font-size:10px;padding:6px 8px;">${_escHtml(s.subject_name)}</td>
           <td style="text-align:center;font-weight:600;padding:6px;">${parseFloat(s.units) || 0}</td>
         </tr>`;
       });
     });

     body.innerHTML = `
    <div style="margin-bottom:14px;">
      <div style="padding:14px 18px;background:linear-gradient(135deg,#fef3c7,#fde68a);border:1px solid var(--amber-b);border-radius:10px;">
        <div style="font-size:13px;font-weight:700;color:#92400e;margin-bottom:4px;">
          <i class="fas fa-clipboard-list" style="margin-right:6px;"></i>Step 2: All Subjects
        </div>
        <div style="font-size:11px;color:#a16207;line-height:1.6;">
          Review and select all subjects the Non-IBM student is allowed to take. Bridging subjects (marked) are already loaded and must be completed first.
          ${isFirstYearFirstSem ? '<br><strong>Note:</strong> Regular subjects can only be taken after completing required bridging subjects.' : ''}
        </div>
      </div>
    </div>
    <div style="margin-bottom:10px;display:flex;gap:8px;align-items:center;">
      <input type="text" id="nimLoadSearch" placeholder="Search subjects..." oninput="NonIBMEvaluation.filterSubjects()"
        style="flex:1;padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;font-family:'Poppins',sans-serif;">
      <span style="font-size:11px;color:var(--muted);" id="nimLoadCount">${Object.keys(_subjectLoad).filter(k => _subjectLoad[k]).length} selected</span>
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
        <i class="fas fa-exclamation-triangle" style="color:var(--amber);margin-right:4px;"></i>
        Only selected subjects will be available for evaluation
      </span>
      <span style="font-size:11px;font-weight:700;color:var(--gold-d);" id="nimLoadUnits">0 units</span>
    </div>`;

     footer.innerHTML = `
    <button class="nim-btn-back" onclick="NonIBMEvaluation.goToStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
    <button class="nim-btn-next" onclick="NonIBMEvaluation.goToStep(3)"><i class="fas fa-arrow-right"></i> Next: Proceed</button>`;

    _updateLoadCount();
  }

   /* ── Step 3: Complete ── */
   function _renderComplete(body, footer) {
    const loadCount = Object.keys(_subjectLoad).filter(k => _subjectLoad[k]).length;
    const loadUnits = Object.keys(_subjectLoad).filter(k => _subjectLoad[k]).reduce((acc, sid) => {
      const sub = _subjects.find(s => s.id == sid);
      return acc + (sub ? (parseFloat(sub.units) || 0) : 0);
    }, 0);
    const bridgingCount = _bridgingSubjects.length;

    body.innerHTML = `
    <div style="text-align:center;padding:30px 20px;">
      <div style="width:72px;height:72px;margin:0 auto 18px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32px;color:var(--amber);">
        <i class="fas fa-check-circle"></i>
      </div>
      <div style="font-size:20px;font-weight:800;color:var(--dark);font-family:'Playfair Display',serif;margin-bottom:8px;">Non-IBM Setup Complete</div>
      <div style="font-size:13px;color:var(--muted);margin-bottom:24px;">
        The student's subject load has been configured. Evaluation will be restricted to the selected subjects only.
      </div>
      <div style="display:grid;grid-template-columns:${bridgingCount > 0 ? '1fr 1fr' : '1fr'};gap:14px;max-width:360px;margin:0 auto;">
        <div style="padding:16px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:12px;border:1px solid var(--amber-b);">
          <div style="font-size:28px;font-weight:800;color:var(--amber);font-family:'Playfair Display',serif;">${loadCount}</div>
          <div style="font-size:9px;color:#92400e;text-transform:uppercase;font-weight:600;">Subjects (${loadUnits} units)</div>
        </div>
        ${bridgingCount > 0 ? `
        <div style="padding:16px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:12px;border:1px solid var(--blue-b);">
          <div style="font-size:28px;font-weight:800;color:var(--blue);font-family:'Playfair Display',serif;">${bridgingCount}</div>
          <div style="font-size:9px;color:#1e40af;text-transform:uppercase;font-weight:600;">Bridging (Auto-Loaded)</div>
        </div>` : ''}
      </div>
      <div style="margin-top:16px;padding:10px 14px;background:var(--cream2);border-radius:8px;font-size:10px;color:var(--muted);line-height:1.6;">
        <i class="fas fa-shield-alt" style="color:var(--amber);margin-right:4px;"></i>
        Subjects not in the load will be disabled in the prospectus. The student cannot take subjects outside their defined load.
      </div>
    </div>`;

    footer.innerHTML = `
    <button class="nim-btn-back" onclick="NonIBMEvaluation.goToStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
    <button class="nim-btn-confirm" onclick="NonIBMEvaluation.complete()"><i class="fas fa-scroll"></i> Open Evaluation Prospectus</button>`;
  }

  /* ── Navigation ── */
  function goToStep(step) {
    _step = step;
    _save();
    _renderStep();
  }

  /* ── Toggle Handler ── */
  function toggleSubject(sid, checked) {
    if (checked) {
      _subjectLoad[sid] = true;
    } else {
      delete _subjectLoad[sid];
    }
    _save();
    _updateLoadCount();
  }

  function _updateLoadCount() {
    const count = Object.keys(_subjectLoad).filter(k => _subjectLoad[k]).length;
    const units = Object.keys(_subjectLoad).filter(k => _subjectLoad[k]).reduce((acc, sid) => {
      const sub = _subjects.find(s => s.id == sid);
      return acc + (sub ? (parseFloat(sub.units) || 0) : 0);
    }, 0);
    const countEl = document.getElementById('nimLoadCount');
    const unitsEl = document.getElementById('nimLoadUnits');
    if (countEl) countEl.textContent = `${count} selected`;
    if (unitsEl) unitsEl.textContent = `${units} units`;
  }

  /* ── Search Filter ── */
  function filterSubjects() {
    const searchEl = document.getElementById('nimLoadSearch');
    const q = (searchEl && searchEl.value ? searchEl.value : '').toLowerCase();
    document.querySelectorAll('.nim-subject-row').forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(q) ? '' : 'none';
    });
  }

  /* ── Complete ── */
   function complete() {
     _setupComplete = true;
     _save();
     _removeModal();
     if (typeof _onComplete === 'function') {
       _onComplete(_subjectLoad, _bridgingSubjects);
     }
   }

  /* ── Close ── */
  function close() {
    _removeModal();
  }

  function _removeModal() {
    const modal = document.getElementById('nonIBMEvalModal');
    if (modal) modal.remove();
  }

  /* ── Apply Restrictions to Prospectus ── */
  function applyRestrictions() {
    if (!_subjects.length) return;

    _subjects.forEach(sub => {
      const row = document.getElementById('row-' + sub.id);
      if (!row) return;

      const isAllowed = isSubjectAllowed(sub.id);
      const inp = document.getElementById('g-' + sub.id);
      const sbtn = document.getElementById('sbtn-' + sub.id);

      if (!isAllowed) {
        row.classList.add('row-locked');
        row.style.opacity = '0.4';
        if (inp) {
          inp.disabled = true;
          inp.title = 'Not in subject load — Non-IBM restriction';
        }
        if (sbtn) sbtn.disabled = true;

        // Add a "Not in Load" badge if not already present
        const existingBadge = row.querySelector('.nim-restricted-badge');
        if (!existingBadge) {
          const gradeCell = row.querySelector('.grade-cell-wrap');
          if (gradeCell) {
            const badge = document.createElement('span');
            badge.className = 'nim-restricted-badge';
            badge.innerHTML = '<i class="fas fa-ban" style="font-size:7px;margin-right:3px;"></i>Not in Load';
            badge.style.cssText = 'display:inline-flex;align-items:center;gap:3px;font-size:8px;padding:2px 6px;background:#fef3c7;color:#92400e;border-radius:4px;border:1px solid #fbbf24;white-space:nowrap;margin-top:2px;';
            gradeCell.appendChild(badge);
          }
        }
      }
    });

    // Check bridging completion for 1st Year 1st Sem
    if (_isFirstYearFirstSem() && _bridgingSubjects.length > 0) {
      _checkBridgingCompletion();
    }
  }

  /* ── Check Bridging Completion ── */
  function _checkBridgingCompletion() {
    if (!window.gradeMap) return;

    const allBridgingPassed = _bridgingSubjects.every(bs => {
      const grade = window.gradeMap[bs.id];
      return grade != null && parseFloat(grade) <= 3.00;
    });

    _bridgingComplete = allBridgingPassed;
    _save();

    if (!allBridgingPassed) {
      // Lock non-bridging 1st Year 1st Sem subjects
      const firstYearFirstSem = _subjects.filter(s =>
        (s.year_level || '').includes('1st Year') &&
        (s.semester || '').includes('1st') &&
        (s.year_level || '').toLowerCase() !== 'bridging'
      );

      firstYearFirstSem.forEach(sub => {
        if (!_bridgingSubjects.find(bs => bs.id === sub.id)) {
          const row = document.getElementById('row-' + sub.id);
          const inp = document.getElementById('g-' + sub.id);
          const sbtn = document.getElementById('sbtn-' + sub.id);

          if (row && isSubjectAllowed(sub.id)) {
            row.classList.add('row-locked');
            if (inp) {
              inp.disabled = true;
              inp.title = 'Complete bridging subjects first';
            }
            if (sbtn) sbtn.disabled = true;

            const existingBadge = row.querySelector('.nim-bridging-badge');
            if (!existingBadge) {
              const gradeCell = row.querySelector('.grade-cell-wrap');
              if (gradeCell) {
                const badge = document.createElement('span');
                badge.className = 'nim-bridging-badge';
                badge.innerHTML = '<i class="fas fa-hourglass-half" style="font-size:7px;margin-right:3px;"></i>Complete bridging first';
                badge.style.cssText = 'display:inline-flex;align-items:center;gap:3px;font-size:8px;padding:2px 6px;background:#dbeafe;color:#1e40af;border-radius:4px;border:1px solid #93c5fd;white-space:nowrap;margin-top:2px;';
                gradeCell.appendChild(badge);
              }
            }
          }
        }
      });
    }
  }

  /* ── Helpers ── */
  function _escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /* ── Public API ── */
  return {
    init,
    close,
    goToStep,
    toggleSubject,
    filterSubjects,
    complete,
    getSubjectLoad,
    isSubjectAllowed,
    isBridgingComplete,
    applyRestrictions
  };
})();
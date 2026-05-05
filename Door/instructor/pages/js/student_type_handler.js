/* ═══════════════════════════════════════════════════════════
   STUDENT TYPE HANDLER
   Manages student type selection (Regular, Transfer, Non-IBM)
   and routes to the appropriate evaluation workflow.
═══════════════════════════════════════════════════════════ */

const StudentTypeHandler = (() => {
  'use strict';

  /* ── State ── */
  let _currentType = null;        // 'regular' | 'transfer' | 'non_ibm'
  let _pendingStudent = null;     // student object waiting for type selection
  let _typePerStudent = {};       // { studentId: type } — persists in sessionStorage

  /* ── Constants ── */
  const TYPES = {
    regular:  { label: 'Regular Student',  icon: 'fa-user-graduate', color: '#16a34a', gradient: 'linear-gradient(135deg,#16a34a,#15803d)', desc: 'Follows the standard curriculum. Can take subjects continuously each year. If all subjects are passed, progression to the next subjects is allowed.' },
    transfer: { label: 'Transfer Student', icon: 'fa-exchange-alt',  color: '#1d4ed8', gradient: 'linear-gradient(135deg,#1d4ed8,#1e40af)', desc: 'Set up academic standing first. Input previously completed subjects from their previous school, validate against the prospectus, then enter current subject load.' },
    non_ibm:  { label: 'Non-IBM Student',  icon: 'fa-user-tag',     color: '#d97706', gradient: 'linear-gradient(135deg,#d97706,#b45309)', desc: 'Only subjects in the student\'s subject load are allowed. During 1st Year 1st Semester, required bridging subjects are auto-loaded before proceeding.' }
  };

  const STORAGE_KEY = 'eval_student_types';

  /* ── Init ── */
  function init() {
    _loadFromSession();
    _injectModal();
  }

  /* ── Session Storage ── */
  function _loadFromSession() {
    try {
      const raw = sessionStorage.getItem(STORAGE_KEY);
      if (raw) _typePerStudent = JSON.parse(raw);
    } catch (e) { _typePerStudent = {}; }
  }

  function _saveToSession() {
    try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(_typePerStudent)); } catch (e) {}
  }

  /* ── Get / Set Type ── */
  function getType(studentId) {
    return _typePerStudent[studentId] || null;
  }

  function setType(studentId, type) {
    _typePerStudent[studentId] = type;
    _currentType = type;
    _saveToSession();
  }

  function clearType(studentId) {
    if (!studentId) return;
    const oldType = _typePerStudent[studentId];
    delete _typePerStudent[studentId];
    if (_currentType && _currentType === oldType) {
      _currentType = null;
    }
    _saveToSession();
  }

  function getCurrentType() {
    return _currentType;
  }

  /* ── Show Type Selection Modal ── */
  function showTypeSelection(student, callback) {
    _pendingStudent = student;
    const existingType = getType(student.id);

    const modal = document.getElementById('studentTypeModal');
    if (!modal) return callback('regular'); // fallback

    // Update student info in modal
    const full = `${student.first_name}${student.middle_name ? ' ' + student.middle_name : ''} ${student.last_name}${student.suffix ? ' ' + student.suffix : ''}`.trim();
    document.getElementById('stmStudentName').textContent = full;
    document.getElementById('stmStudentInfo').textContent = `${student.major_name || 'No major'} · ${student.year_level || '—'}`;

    // Highlight existing type if any
    document.querySelectorAll('.stm-type-card').forEach(card => {
      card.classList.remove('stm-selected');
      if (existingType && card.dataset.type === existingType) {
        card.classList.add('stm-selected');
      }
    });

    // Show/hide "previously selected" badge
    const prevBadge = document.getElementById('stmPrevBadge');
    if (prevBadge) {
      if (existingType) {
        prevBadge.style.display = 'inline-flex';
        prevBadge.innerHTML = `<i class="fas fa-history" style="margin-right:5px;"></i> Previously: ${TYPES[existingType]?.label || existingType}`;
      } else {
        prevBadge.style.display = 'none';
      }
    }

    // Store callback
    modal._callback = callback;

    // Show modal
    modal.classList.add('open');
  }

   /* ── Confirm Selection ── */
  function confirmSelection(type) {
    const modal = document.getElementById('studentTypeModal');
    if (!modal || !_pendingStudent) return;

    setType(_pendingStudent.id, type);

    // Save to database
    const fd = new FormData();
    fd.append('action', 'save_student_type');
    fd.append('student_id', _pendingStudent.id);
    fd.append('student_type', type);

    // Resolve URL: prefer global EVAL_PROC, else compute from current location
    let url = '../../../data/evaluation_process.php';
    if (typeof EVAL_PROC !== 'undefined' && EVAL_PROC) {
      url = EVAL_PROC;
    } else if (typeof window !== 'undefined' && window.location) {
      // Compute absolute path from the current page location
      const pathParts = window.location.pathname.split('/');
      // Remove last parts until we reach the project root (student_evaluation)
      // Assuming path: /student_evaluation/Door/instructor/pages/evaluation.php
      // We want: /student_evaluation/data/evaluation_process.php
      // Find index of 'student_evaluation' in path
      const projIdx = pathParts.indexOf('student_evaluation');
      if (projIdx !== -1) {
        const newParts = pathParts.slice(0, projIdx + 1).concat(['data', 'evaluation_process.php']);
        url = '/' + newParts.join('/');
      }
    }

    console.log('Saving student type to:', url, {student_id: _pendingStudent.id, type});

    fetch(url, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    }).then(r => {
      const ct = r.headers.get('content-type') || '';
      if (!ct.includes('application/json')) {
        return r.text().then(txt => {
          console.error('Non-JSON response:', txt);
          throw new Error('Server returned non-JSON: ' + txt.substring(0, 200));
        });
      }
      return r.json();
    }).then(data => {
      if (!data.success) {
        console.error('Failed to save student type:', data.message);
        toast('Failed to save student type: ' + (data.message || 'Unknown error'), 'error', 4000);
} else {
  console.log('Student type saved:', type);
  // Refresh mentee list to update student type display
  if (typeof loadMentees === 'function') {
    loadMentees();
  }
}
    }).catch(err => {
      console.error('Error saving student type:', err);
      toast('Error saving student type: ' + err.message, 'error', 4000);
    });

    // Close modal
    modal.classList.remove('open');

    // Invoke callback
    if (typeof modal._callback === 'function') {
      modal._callback(type, _pendingStudent);
    }

    _pendingStudent = null;
  }

  /* ── Close Modal ── */
  function closeModal() {
    const modal = document.getElementById('studentTypeModal');
    if (modal) modal.classList.remove('open');
    _pendingStudent = null;
  }

  /* ── Inject Modal HTML ── */
  function _injectModal() {
    if (document.getElementById('studentTypeModal')) return;

    const html = `
    <div class="stm-overlay" id="studentTypeModal">
      <div class="stm-panel">
        <div class="stm-header">
          <div class="stm-header-icon">
            <i class="fas fa-users-cog"></i>
          </div>
          <div>
            <div class="stm-header-title">Select Student Type</div>
            <div class="stm-header-sub">Choose the classification before opening the evaluation prospectus</div>
          </div>
          <button class="stm-close" onclick="StudentTypeHandler.closeModal()"><i class="fas fa-times"></i></button>
        </div>

        <div class="stm-student-bar">
          <div class="stm-student-avatar"><i class="fas fa-user-graduate"></i></div>
          <div>
            <div class="stm-student-name" id="stmStudentName">—</div>
            <div class="stm-student-meta" id="stmStudentInfo">—</div>
          </div>
          <span class="stm-prev-badge" id="stmPrevBadge" style="display:none;"></span>
        </div>

        <div class="stm-body">
          <div class="stm-type-card" data-type="regular" onclick="StudentTypeHandler.selectCard(this)">
            <div class="stm-card-icon" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);color:#166534;">
              <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stm-card-content">
              <div class="stm-card-title" style="color:#166534;">Regular Student</div>
              <div class="stm-card-desc">Follows the standard curriculum. Can take subjects continuously each year. If all subjects are passed, progression to the next subjects is allowed.</div>
              <div class="stm-card-features">
                <span class="stm-feature"><i class="fas fa-check"></i> Standard curriculum flow</span>
                <span class="stm-feature"><i class="fas fa-check"></i> Continuous progression</span>
                <span class="stm-feature"><i class="fas fa-check"></i> Full prospectus access</span>
              </div>
            </div>
            <div class="stm-card-check"><i class="fas fa-check-circle"></i></div>
          </div>

          <div class="stm-type-card" data-type="transfer" onclick="StudentTypeHandler.selectCard(this)">
            <div class="stm-card-icon" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#1e40af;">
              <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="stm-card-content">
              <div class="stm-card-title" style="color:#1e40af;">Transfer Student</div>
              <div class="stm-card-desc">Set up academic standing first. Input previously completed subjects, validate against the prospectus, then enter current subject load for combined evaluation.</div>
              <div class="stm-card-features">
                <span class="stm-feature"><i class="fas fa-check"></i> Previous school validation</span>
                <span class="stm-feature"><i class="fas fa-check"></i> Subject credit transfer</span>
                <span class="stm-feature"><i class="fas fa-check"></i> Combined evaluation</span>
              </div>
            </div>
            <div class="stm-card-check"><i class="fas fa-check-circle"></i></div>
          </div>

          <div class="stm-type-card" data-type="non_ibm" onclick="StudentTypeHandler.selectCard(this)">
            <div class="stm-card-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e;">
              <i class="fas fa-user-tag"></i>
            </div>
            <div class="stm-card-content">
              <div class="stm-card-title" style="color:#92400e;">Non-IBM Student</div>
              <div class="stm-card-desc">Only subjects in the student's subject load are allowed. During 1st Year 1st Semester, required bridging subjects are auto-loaded before other subjects.</div>
              <div class="stm-card-features">
                <span class="stm-feature"><i class="fas fa-check"></i> Restricted subject load</span>
                <span class="stm-feature"><i class="fas fa-check"></i> Auto bridging subjects</span>
                <span class="stm-feature"><i class="fas fa-check"></i> Controlled progression</span>
              </div>
            </div>
            <div class="stm-card-check"><i class="fas fa-check-circle"></i></div>
          </div>
        </div>

        <div class="stm-footer">
          <button class="stm-btn-cancel" onclick="StudentTypeHandler.closeModal()">
            <i class="fas fa-times"></i> Cancel
          </button>
          <button class="stm-btn-confirm" id="stmConfirmBtn" onclick="StudentTypeHandler.onConfirm()" disabled>
            <i class="fas fa-arrow-right"></i> Continue with Selected Type
          </button>
        </div>
      </div>
    </div>`;

    const div = document.createElement('div');
    div.innerHTML = html;
    document.body.appendChild(div.firstElementChild);
  }

  /* ── Card Selection ── */
  function selectCard(el) {
    document.querySelectorAll('.stm-type-card').forEach(c => c.classList.remove('stm-selected'));
    el.classList.add('stm-selected');
    document.getElementById('stmConfirmBtn').disabled = false;
  }

  /* ── On Confirm ── */
  function onConfirm() {
    const selected = document.querySelector('.stm-type-card.stm-selected');
    if (!selected) return;
    confirmSelection(selected.dataset.type);
  }

  /* ── Public API ── */
  return {
    init,
    showTypeSelection,
    confirmSelection,
    closeModal,
    selectCard,
    onConfirm,
    getType,
    setType,
    clearType,
    getCurrentType,
    TYPES
  };
})();

// Auto-init on DOM ready
document.addEventListener('DOMContentLoaded', () => StudentTypeHandler.init());
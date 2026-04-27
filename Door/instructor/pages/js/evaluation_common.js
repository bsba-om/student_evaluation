/* ═══════════════════════════════════════════════════════════
   EVALUATION COMMON HELPERS
   Shared utilities for transfer_evaluation.js and non_ibm_evaluation.js
   - HTML escaping
   - Year/semester sorting
   - Subject grouping
   - Unit calculations
   - Grade validation (1.00 – 5.00)
   - Session storage helpers with student scoping & cleanup
   - Unsaved-changes confirmation dialog
═══════════════════════════════════════════════════════════ */

const EvalCommon = (() => {
  'use strict';

  /* ── Constants ── */
  const YEAR_ORDER = { '1st Year': 1, '2nd Year': 2, '3rd Year': 3, '4th Year': 4, 'Bridging': 5 };
  const SEMESTER_ORDER = { '1st Semester': 1, '2nd Semester': 2, 'Summer': 3 };
  const MIN_GRADE = 1.00;
  const MAX_GRADE = 5.00;
  const PASSING_GRADE = 3.00;

  // Registry of all evaluation module storage key prefixes. Keep in sync with
  // STORAGE_KEY constants in each evaluation module.
  const STORAGE_KEY_PREFIXES = [
    'transfer_eval_data',
    'non_ibm_eval_data'
  ];

  // Tracks the currently active student id per browser session so we can
  // detect when a new student's evaluation is opened and purge leaked state.
  const ACTIVE_STUDENT_KEY = '__eval_active_student_id__';

  /* ── HTML Escaping ── */
  function escHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  /* ── Year/Semester Sort Comparator ──
     Sorts "YEAR|SEMESTER" composite keys using YEAR_ORDER and SEMESTER_ORDER. */
  function compareYearSemKeys(a, b) {
    const [yA, sA] = a.split('|');
    const [yB, sB] = b.split('|');
    const yearDiff = (YEAR_ORDER[yA] || 99) - (YEAR_ORDER[yB] || 99);
    if (yearDiff !== 0) return yearDiff;
    return (SEMESTER_ORDER[sA] || 99) - (SEMESTER_ORDER[sB] || 99);
  }

  /* ── Group subjects by year + semester ──
     Returns { "YEAR|SEMESTER": [subject, ...] } */
  function groupByYearSem(subjects, opts) {
    const options = opts || {};
    const excludeBridging = !!options.excludeBridging;
    const grouped = {};
    (subjects || []).forEach(s => {
      if (excludeBridging && (s.year_level || '').toLowerCase() === 'bridging') return;
      const key = `${s.year_level || '1st Year'}|${s.semester || '1st Semester'}`;
      if (!grouped[key]) grouped[key] = [];
      grouped[key].push(s);
    });
    return grouped;
  }

  /* ── Get sorted group keys ── */
  function getSortedGroupKeys(grouped) {
    return Object.keys(grouped).sort(compareYearSemKeys);
  }

  /* ── Sum units for given subject ids ── */
  function sumUnits(subjects, subjectIds) {
    if (!Array.isArray(subjectIds)) subjectIds = Object.keys(subjectIds || {});
    return subjectIds.reduce((acc, sid) => {
      const sub = (subjects || []).find(s => s.id == sid);
      return acc + (sub ? (parseFloat(sub.units) || 0) : 0);
    }, 0);
  }

  /* ── Grade Validation ──
     Returns { valid: boolean, message: string, normalized: number|null } */
  function validateGrade(value) {
    if (value === null || value === undefined || value === '') {
      return { valid: false, message: 'Grade is required', normalized: null };
    }
    const str = String(value).trim();
    if (!/^-?\d+(\.\d+)?$/.test(str)) {
      return { valid: false, message: 'Grade must be a number (e.g. 1.75)', normalized: null };
    }
    const num = parseFloat(str);
    if (isNaN(num)) {
      return { valid: false, message: 'Invalid grade value', normalized: null };
    }
    if (num < MIN_GRADE) {
      return {
        valid: false,
        message: `Grade cannot be below ${MIN_GRADE.toFixed(2)}`,
        normalized: null
      };
    }
    if (num > MAX_GRADE) {
      return {
        valid: false,
        message: `Grade cannot be above ${MAX_GRADE.toFixed(2)}`,
        normalized: null
      };
    }
    return { valid: true, message: '', normalized: num };
  }

  function isPassing(grade) {
    const n = parseFloat(grade);
    return !isNaN(n) && n >= MIN_GRADE && n <= PASSING_GRADE;
  }

  /* ── Inline Validation UI ──
     Attach or update an error message adjacent to a grade input element. */
  function showInlineError(inputEl, message) {
    if (!inputEl) return;
    const errorId = inputEl.id ? `${inputEl.id}-err` : null;
    let errorEl = errorId ? document.getElementById(errorId) : null;

    if (!errorEl) {
      errorEl = document.createElement('div');
      if (errorId) errorEl.id = errorId;
      errorEl.className = 'eval-inline-error';
      errorEl.style.cssText = 'color:#b91c1c;font-size:9px;font-weight:600;margin-top:2px;line-height:1.3;font-family:\'Poppins\',sans-serif;';
      // Insert after the input
      if (inputEl.parentNode) {
        inputEl.parentNode.insertBefore(errorEl, inputEl.nextSibling);
      }
    }
    errorEl.textContent = message;
    errorEl.style.display = 'block';
    inputEl.style.borderColor = '#dc2626';
    inputEl.setAttribute('aria-invalid', 'true');
  }

  function clearInlineError(inputEl) {
    if (!inputEl) return;
    const errorId = inputEl.id ? `${inputEl.id}-err` : null;
    const errorEl = errorId ? document.getElementById(errorId) : null;
    if (errorEl) {
      errorEl.style.display = 'none';
      errorEl.textContent = '';
    }
    inputEl.style.borderColor = '';
    inputEl.removeAttribute('aria-invalid');
  }

  /* ── Session Storage Helpers ──
     All evaluation modules should use these to ensure student-scoped keys
     and automatic cleanup when switching students. */
  function _storageKey(prefix, studentId) {
    return `${prefix}_${studentId}`;
  }

  function loadStudentData(prefix, studentId) {
    try {
      const raw = sessionStorage.getItem(_storageKey(prefix, studentId));
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  function saveStudentData(prefix, studentId, data) {
    try {
      sessionStorage.setItem(_storageKey(prefix, studentId), JSON.stringify(data));
      return true;
    } catch (e) {
      return false;
    }
  }

  function clearStudentData(prefix, studentId) {
    try {
      sessionStorage.removeItem(_storageKey(prefix, studentId));
    } catch (e) {}
  }

  /* ── Clear ALL evaluation data for a specific student ── */
  function clearAllDataForStudent(studentId) {
    STORAGE_KEY_PREFIXES.forEach(prefix => clearStudentData(prefix, studentId));
  }

  /* ── Clear any evaluation data that does NOT belong to the given student ──
     This prevents the previous student's data from leaking into a new
     evaluation session when the user switches students without reloading. */
  function purgeOtherStudents(currentStudentId) {
    try {
      const currentId = String(currentStudentId);
      const keysToRemove = [];
      for (let i = 0; i < sessionStorage.length; i++) {
        const key = sessionStorage.key(i);
        if (!key) continue;
        for (const prefix of STORAGE_KEY_PREFIXES) {
          const expectedPrefix = `${prefix}_`;
          if (key.startsWith(expectedPrefix)) {
            const ownerId = key.substring(expectedPrefix.length);
            if (ownerId !== currentId) {
              keysToRemove.push(key);
            }
            break;
          }
        }
      }
      keysToRemove.forEach(k => {
        try { sessionStorage.removeItem(k); } catch (e) {}
      });
    } catch (e) {}
  }

  /* ── Begin an evaluation session for a student ──
     Call this at the start of any evaluation workflow. It records the
     active student and purges stale data from previous students. */
  function beginStudentSession(studentId) {
    if (studentId === null || studentId === undefined) return;
    const currentId = String(studentId);
    let previousId = null;
    try {
      previousId = sessionStorage.getItem(ACTIVE_STUDENT_KEY);
    } catch (e) {}

    if (previousId && previousId !== currentId) {
      // A different student was active before — clean their data out.
      clearAllDataForStudent(previousId);
    }
    // Also defensively purge any other leftover data not belonging to this student.
    purgeOtherStudents(currentId);

    try {
      sessionStorage.setItem(ACTIVE_STUDENT_KEY, currentId);
    } catch (e) {}
  }

  /* ── Unsaved Changes Confirmation Dialog ──
     Shows a modal asking the user to confirm discarding unsaved changes.
     onConfirm: called if user confirms discard
     onCancel:  called if user cancels (optional) */
  function confirmUnsavedChanges(options) {
    const opts = options || {};
    const title = opts.title || 'Unsaved Changes';
    const message = opts.message || 'You have unsaved changes. Are you sure you want to close? Your progress will be lost.';
    const confirmLabel = opts.confirmLabel || 'Discard & Close';
    const cancelLabel = opts.cancelLabel || 'Keep Editing';
    const onConfirm = typeof opts.onConfirm === 'function' ? opts.onConfirm : function () {};
    const onCancel = typeof opts.onCancel === 'function' ? opts.onCancel : function () {};

    // Remove any existing confirm dialog
    const existing = document.getElementById('evalConfirmDialog');
    if (existing) existing.remove();

    const wrapper = document.createElement('div');
    wrapper.id = 'evalConfirmDialog';
    wrapper.style.cssText = [
      'position:fixed',
      'inset:0',
      'background:rgba(15,23,42,0.55)',
      'z-index:100000',
      'display:flex',
      'align-items:center',
      'justify-content:center',
      'font-family:\'Poppins\',sans-serif',
      'animation:evalConfirmFade 160ms ease-out'
    ].join(';') + ';';

    wrapper.innerHTML = `
      <style>
        @keyframes evalConfirmFade { from { opacity:0 } to { opacity:1 } }
      </style>
      <div role="dialog" aria-modal="true" aria-labelledby="evalConfirmTitle"
           style="background:#fff;border-radius:14px;max-width:420px;width:92%;padding:22px 24px;box-shadow:0 20px 50px rgba(0,0,0,.25);border:1px solid #e5e7eb;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
          <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#fef3c7,#fde68a);display:flex;align-items:center;justify-content:center;color:#b45309;font-size:18px;">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          <div id="evalConfirmTitle" style="font-size:15px;font-weight:800;color:#0f172a;">${escHtml(title)}</div>
        </div>
        <div style="font-size:12px;color:#475569;line-height:1.55;margin-bottom:18px;">
          ${escHtml(message)}
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
          <button type="button" id="evalConfirmCancelBtn"
            style="padding:8px 14px;border:1.5px solid #e5e7eb;background:#fff;color:#334155;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;font-family:inherit;">
            ${escHtml(cancelLabel)}
          </button>
          <button type="button" id="evalConfirmOkBtn"
            style="padding:8px 14px;border:none;background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;">
            <i class="fas fa-times" style="margin-right:5px;"></i>${escHtml(confirmLabel)}
          </button>
        </div>
      </div>`;

    document.body.appendChild(wrapper);

    const cleanup = () => {
      if (wrapper && wrapper.parentNode) wrapper.parentNode.removeChild(wrapper);
      document.removeEventListener('keydown', onKey);
    };

    const onKey = (ev) => {
      if (ev.key === 'Escape') {
        cleanup();
        onCancel();
      }
    };
    document.addEventListener('keydown', onKey);

    const cancelBtn = wrapper.querySelector('#evalConfirmCancelBtn');
    const okBtn = wrapper.querySelector('#evalConfirmOkBtn');

    if (cancelBtn) cancelBtn.addEventListener('click', () => { cleanup(); onCancel(); });
    if (okBtn) okBtn.addEventListener('click', () => { cleanup(); onConfirm(); });

    // Click on backdrop cancels (keep editing)
    wrapper.addEventListener('click', (ev) => {
      if (ev.target === wrapper) { cleanup(); onCancel(); }
    });

    // Autofocus cancel to avoid accidental discard on Enter
    setTimeout(() => { if (cancelBtn) cancelBtn.focus(); }, 0);
  }

  /* ── Generic search filter ──
     Toggle display on rows matching a selector based on a query input. */
  function filterRowsByText(rowSelector, query) {
    const q = (query || '').toLowerCase();
    document.querySelectorAll(rowSelector).forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(q) ? '' : 'none';
    });
  }

  /* ── Public API ── */
  return {
    // constants
    YEAR_ORDER,
    SEMESTER_ORDER,
    MIN_GRADE,
    MAX_GRADE,
    PASSING_GRADE,
    STORAGE_KEY_PREFIXES,
    // utils
    escHtml,
    compareYearSemKeys,
    groupByYearSem,
    getSortedGroupKeys,
    sumUnits,
    validateGrade,
    isPassing,
    showInlineError,
    clearInlineError,
    // storage
    loadStudentData,
    saveStudentData,
    clearStudentData,
    clearAllDataForStudent,
    purgeOtherStudents,
    beginStudentSession,
    // dialogs
    confirmUnsavedChanges,
    // filters
    filterRowsByText
  };
})();

// Expose globally for legacy callers
if (typeof window !== 'undefined') {
  window.EvalCommon = EvalCommon;
}
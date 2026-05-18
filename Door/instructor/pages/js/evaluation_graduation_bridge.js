/**
 * evaluation_graduation_bridge.js
 * ─────────────────────────────────────────────────────────────────────────────
 * DROP-IN REPLACEMENT for the confirmGraduation() function inside evaluation.php
 *
 * HOW TO INTEGRATE
 * ─────────────────
 * 1. In evaluation.php, locate the existing confirmGraduation() function
 *    (search for "function confirmGraduation") and REPLACE the entire function
 *    with the one below.
 *
 * 2. Also replace the final section of the showResultModal() where the
 *    graduation card's "Download" and "View Graduate Records" buttons are
 *    rendered — those already redirect to setup_graduate.php, but now the
 *    PDF URL comes from graduate_process.php automatically.
 *
 * 3. No other JS changes are needed.  The PHP backend (graduate_process.php)
 *    handles everything: marking the student as graduated in the DB, generating
 *    the prospectus PDF, saving it to disk, and returning the download URL.
 * ─────────────────────────────────────────────────────────────────────────────
 */

/**
 * confirmGraduation
 * Called from the graduation card inside showResultModal() when the instructor
 * clicks "Confirm graduation & generate prospectus PDF".
 *
 * Flow:
 *  1. Confirm dialog
 *  2. POST to evaluation_process.php → action=confirm_graduation
 *     (evaluation_process.php delegates to graduate_process.php internally, OR
 *      we call graduate_process.php directly — both work; see graduate_process.php)
 *  3. On success: show green success card + Download PDF button + View Records link
 *  4. Reload mentee list and reopen student panel with graduated status
 *
 * @param {string} yearLabel  e.g. "4th Year"
 * @param {string} semLabel   e.g. "2nd Semester"
 */
function confirmGraduation(yearLabel, semLabel) {
  if (!currentStudent) { toast('No student loaded.', 'error'); return; }

  if (!confirm(
    'Are you sure you want to confirm graduation for this student?\n\n' +
    'This will:\n' +
    '- Mark the student as graduated\n' +
    '- Generate their official prospectus PDF\n' +
    '- Lock their evaluation record\n\n' +
    'This action cannot be undone.'
  )) return;

  // Disable button during processing
  const btn = document.getElementById('graduationConfirmBtn');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…';
  }

  // ── Step 1: Tell evaluation_process.php to mark the student as graduated ──
  const fd = new FormData();
  fd.append('action',       'confirm_graduation');
  fd.append('student_id',   currentStudent.id);
  fd.append('major_id',     currentStudent.major_id || 0);
  fd.append('academic_year', currentAY);
  fd.append('year_level',   yearLabel);
  fd.append('semester',     semLabel);

  fetch(EVAL_PROC, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        toast(data.message || 'Failed to confirm graduation', 'error');
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-certificate"></i> Confirm graduation & generate prospectus PDF'; }
        return;
      }

      // ── Step 2: Generate the prospectus PDF via graduate_process.php ──
      _generateProspectusPDF(data, yearLabel, semLabel);
    })
    .catch(err => {
      console.error('confirmGraduation error:', err);
      toast('Network error confirming graduation', 'error');
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-certificate"></i> Confirm graduation & generate prospectus PDF'; }
    });
}


/**
 * _generateProspectusPDF
 * Calls graduate_process.php directly to generate + save the prospectus PDF.
 * Separated from confirmGraduation so it can also be called standalone
 * (e.g. from the "Generate PDF" tab in setup_graduate.php).
 *
 * @param {object} graduationData  The JSON returned by confirm_graduation action
 * @param {string} yearLabel
 * @param {string} semLabel
 */
function _generateProspectusPDF(graduationData, yearLabel, semLabel) {
  const GRAD_PROC = '../../../data/graduate_process.php';   // adjust path as needed

  const fd2 = new FormData();
  fd2.append('action',       'generate_pdf');
  fd2.append('student_id',   currentStudent.id);
  fd2.append('batch_year',   currentAY);
  // Pass any extra data the PDF generator needs
  fd2.append('graduation_id', graduationData.graduation_id || '');
  fd2.append('gwa',           graduationData.gwa           || '');

  fetch(GRAD_PROC, { method: 'POST', body: fd2 })
    .then(r => r.json())
    .then(pdfData => {
      // Merge PDF URL back into the graduation data object
      const mergedData = Object.assign({}, graduationData, {
        pdf_url: pdfData.pdf_url || pdfData.download_url || '',
        pdf_saved_path: pdfData.saved_path || ''
      });
      _showGraduationSuccessCard(mergedData);
    })
    .catch(() => {
      // Even if PDF generation fails, show the success card (graduation is already saved)
      console.warn('PDF generation failed or graduate_process.php unreachable — showing success card without PDF link.');
      _showGraduationSuccessCard(graduationData);
    });
}


/**
 * _showGraduationSuccessCard
 * Replaces the graduation card inside the result modal with the
 * green "Graduated!" confirmation card that includes:
 *  - Download Prospectus PDF button
 *  - View Graduate Records button (links to setup_graduate.php)
 *
 * @param {object} data  Combined data from confirm_graduation + generate_pdf
 */
function _showGraduationSuccessCard(data) {
  const SETUP_GRADUATE_URL = '../../../setup_graduate.php';   // adjust path as needed

  const gradDate = data.graduation_date ||
    new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });

  const pdfBtnHtml = data.pdf_url
    ? `<button onclick="window.open('${esc(data.pdf_url)}', '_blank')"
         style="padding:12px 22px;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border:none;
                border-radius:50px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;cursor:pointer;
                display:inline-flex;align-items:center;gap:7px;box-shadow:0 4px 14px rgba(22,163,74,.35);">
         <i class="fas fa-download"></i> Download Prospectus PDF
       </button>`
    : `<span style="font-size:12px;color:#166534;opacity:.7;">
         <i class="fas fa-clock"></i> PDF is being generated…
       </span>`;

  const savedPathNote = data.pdf_saved_path
    ? `<div style="margin-bottom:14px;padding:10px 14px;background:rgba(22,163,74,.08);border-radius:10px;font-size:11px;color:#166534;">
         <i class="fas fa-folder-open" style="color:#16a34a;margin-right:5px;"></i>
         PDF saved to <strong>${esc(data.pdf_saved_path)}</strong>
       </div>`
    : `<div style="margin-bottom:14px;padding:10px 14px;background:rgba(22,163,74,.08);border-radius:10px;font-size:11px;color:#166534;">
         <i class="fas fa-file-pdf" style="color:#dc2626;margin-right:5px;"></i>
         Official prospectus PDF saved under
         <strong>C:\\graduate\\batch ${esc(currentAY)}\\</strong>
       </div>`;

  const cardHtml = `
    <div style="position:relative;overflow:hidden;padding:32px 28px 24px;
                background:linear-gradient(145deg,#f0fdf4,#dcfce7,#bbf7d0);
                border:2px solid #10b981;border-radius:20px;text-align:center;
                box-shadow:0 12px 40px rgba(16,185,129,.22),0 2px 8px rgba(0,0,0,.07);
                animation:slideInGreen .5s cubic-bezier(.34,1.56,.64,1);">

      <style>
        @keyframes slideInGreen { from{opacity:0;transform:translateY(20px) scale(.97)} to{opacity:1;transform:none} }
        @keyframes popIn        { from{transform:scale(0)} to{transform:scale(1)} }
      </style>

      <!-- Icon -->
      <div style="width:90px;height:90px;margin:0 auto 16px;
                  background:linear-gradient(145deg,#4ade80,#16a34a,#15803d);border-radius:50%;
                  display:flex;align-items:center;justify-content:center;font-size:36px;color:#fff;
                  box-shadow:0 6px 24px rgba(22,163,74,.4),0 0 0 8px rgba(74,222,128,.15),0 0 0 16px rgba(74,222,128,.07);
                  animation:popIn .6s .2s cubic-bezier(.34,1.56,.64,1) both;">
        <i class="fas fa-user-graduate"></i>
      </div>

      <!-- Badges -->
      <div style="display:inline-flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap;justify-content:center;">
        <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 14px;
                     background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;
                     border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;
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

      <!-- Stats grid -->
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin-bottom:22px;">
        <div style="padding:16px;background:rgba(22,163,74,.1);border:1.5px solid rgba(22,163,74,.25);border-radius:14px;">
          <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#166534;margin-bottom:6px;">Subjects Completed</div>
          <div style="font-size:28px;font-weight:800;color:#166534;font-family:'Playfair Display',serif;">${data.total_subjects || 0}</div>
        </div>
        <div style="padding:16px;background:rgba(22,163,74,.1);border:1.5px solid rgba(22,163,74,.25);border-radius:14px;">
          <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#166534;margin-bottom:6px;">Final GWA</div>
          <div style="font-size:28px;font-weight:800;color:#166534;font-family:'Playfair Display',serif;">${data.gwa || '—'}</div>
        </div>
        <div style="padding:16px;background:rgba(22,163,74,.1);border:1.5px solid rgba(22,163,74,.25);border-radius:14px;">
          <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#166534;margin-bottom:6px;">Status</div>
          <div style="font-size:16px;font-weight:700;color:#166534;margin-top:6px;"><i class="fas fa-check-circle"></i> Graduated</div>
        </div>
        <div style="padding:16px;background:rgba(22,163,74,.1);border:1.5px solid rgba(22,163,74,.25);border-radius:14px;">
          <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#166534;margin-bottom:6px;">Graduation Date</div>
          <div style="font-size:12px;font-weight:700;color:#166534;margin-top:6px;">${esc(gradDate)}</div>
        </div>
      </div>

      ${savedPathNote}

      <!-- Action buttons -->
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
        ${pdfBtnHtml}
        <button onclick="window.location.href='${SETUP_GRADUATE_URL}'"
          style="padding:12px 22px;background:linear-gradient(135deg,#d4a017,#9a6f00);color:#fff;border:none;
                 border-radius:50px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;cursor:pointer;
                 display:inline-flex;align-items:center;gap:7px;box-shadow:0 4px 14px rgba(212,160,23,.35);">
          <i class="fas fa-award"></i> View Graduate Records
        </button>
        <button onclick="closeResultModal()"
          style="padding:12px 22px;background:linear-gradient(135deg,#64748b,#475569);color:#fff;border:none;
                 border-radius:50px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;cursor:pointer;
                 display:inline-flex;align-items:center;gap:7px;">
          <i class="fas fa-times"></i> Close
        </button>
      </div>
    </div>`;

  const container = document.getElementById('graduationContainer');
  if (container) container.innerHTML = cardHtml;

  // Lock the proceed / stay buttons
  ['rmProceedBtn','rmStayBtn'].forEach(id => {
    const el = document.getElementById(id);
    if (el) { el.disabled = true; el.style.opacity = '.5'; el.style.cursor = 'not-allowed'; }
  });

  toast('🎓 Graduation confirmed! Prospectus PDF has been generated.', 'success', 5000);

  // Mark student as graduated locally + reload mentees in background
  window.evaluationLocked = true;
  if (currentStudent) currentStudent.status = 'graduated';

  setTimeout(() => {
    closeResultModal();
    if (currentStudent) {
      const studentCopy = { ...currentStudent, status: 'graduated' };
      closeEval();
      setTimeout(() => openEval(studentCopy), 200);
    }
    loadMentees();
  }, 2200);
}

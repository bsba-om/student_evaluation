<?php
// evaluation_process.php
// Place at: data/evaluation_process.php
//
// ════════════════════════════════════════════════════════════════════════════
//  ACADEMIC ADVISER ENGINE — Northern Bukidnon State College
//  Rules enforced:
//  1. Grade Processing     — merge prospectus + saved grades
//  2. Failure Logic        — failed subjects re-queued as priority retakes
//  3. Prerequisite Lock    — direct code + prereq-SET enforcement
//  4. Priority Retakes     — failed subjects listed before new ones
//  5. Semester Sequencing  — no future-year subjects if current year has pending
// ════════════════════════════════════════════════════════════════════════════

error_reporting(0);
ini_set('display_errors', 0);

function jsonResponse($data) {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function jsonError($message, $code = 400) {
    jsonResponse(['success' => false, 'message' => $message]);
}

require_once __DIR__ . '/session_security.php';
require_once __DIR__ . '/config.php';

if (!$pdo) {
    jsonError('Database connection failed');
}

$role_access = check_role_access('instructor');
if (!$role_access['allowed']) {
    jsonResponse(['success' => false, 'message' => 'Access denied']);
}

$instructor_id = $_SESSION['user_id'] ?? 0;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ═══════════════════════════════════════════════════════════════════════════
//  GRADE HELPERS
// ═══════════════════════════════════════════════════════════════════════════

function round_grade(float $raw): float {
    $valid = [1.00, 1.25, 1.50, 1.75, 2.00, 2.25, 2.50, 2.75, 3.00, 4.00, 5.00];
    $closest = 5.00;
    $minDiff = PHP_FLOAT_MAX;
    foreach ($valid as $v) {
        $diff = abs($raw - $v);
        if ($diff < $minDiff) { $minDiff = $diff; $closest = $v; }
    }
    return $closest;
}

function grade_status(float $grade): string {
    if ($grade <= 3.00) return 'passed';
    if ($grade == 4.00) return 'conditional';
    return 'failed';
}

function grade_label(float $grade): string {
    $map = [
        1.00 => 'Excellent',   1.25 => 'Very Good',  1.50 => 'Very Good',
        1.75 => 'Good',        2.00 => 'Satisfactory',2.25 => 'Fair',
        2.50 => 'Passing',     2.75 => 'Low Passing', 3.00 => 'Barely Passing',
        4.00 => 'Conditional', 5.00 => 'Failed',
    ];
    return $map[$grade] ?? 'Unknown';
}

function compute_gwa(array $rows): array {
    $totalPoints = 0; $totalUnits = 0; $unitsPassed = 0;
    foreach ($rows as $g) {
        if ($g['grade_rounded'] === null) continue;
        $units = floatval($g['units']);
        $grade = floatval($g['grade_rounded']);
        $totalPoints += $grade * $units;
        $totalUnits  += $units;
        if (grade_status($grade) === 'passed') $unitsPassed += $units;
    }
    return [
        'gwa'          => $totalUnits > 0 ? round($totalPoints / $totalUnits, 4) : null,
        'total_units'  => $totalUnits,
        'units_passed' => $unitsPassed,
    ];
}

/**
 * Get previous semester GWA for anomaly detection
 * Compares current semester GWA with previous semester GWA
 */
function get_previous_semester_gwa(PDO $pdo, int $student_id, string $year_level, string $semester): ?array {
    // Parse current year and semester
    $current = parse_standing("$year_level - $semester");
    
    // Determine previous semester
    if ($current['sem'] === 2) {
        // Previous semester is 1st semester of same year
        $prev_year = $current['yr'];
        $prev_sem = 1;
    } else {
        // Previous semester is 2nd semester of previous year
        $prev_year = $current['yr'] - 1;
        $prev_sem = 2;
    }
    
    if ($prev_year < 1) return null;
    
    $year_labels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
    $sem_labels = [1 => '1st Semester', 2 => '2nd Semester'];
    
    $prev_year_label = $year_labels[$prev_year] ?? null;
    $prev_sem_label = $sem_labels[$prev_sem] ?? null;
    
    if (!$prev_year_label || !$prev_sem_label) return null;
    
    try {
        // Get grades for previous semester
        $stmt = $pdo->prepare("
            SELECT sg.grade_rounded, s.units
            FROM student_grades sg
            JOIN major_subjects ms ON sg.subject_id = ms.subject_id
            JOIN subjects s ON sg.subject_id = s.id
            WHERE sg.student_id = ?
            AND ms.year_level = ?
            AND ms.semester = ?
            AND sg.grade_rounded IS NOT NULL
            ORDER BY sg.graded_at DESC
        ");
        
        // Use latest grade per subject
        $seen = [];
        $rows = [];
        $stmt->execute([$student_id, $prev_year_label, $prev_sem_label]);
        
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $g) {
            if (!isset($seen[$g['subject_id']])) {
                $seen[$g['subject_id']] = true;
                $rows[] = $g;
            }
        }
        
        if (empty($rows)) return null;
        
        $gwaData = compute_gwa(array_map(function($r) {
            return ['grade_rounded' => $r['grade_rounded'], 'units' => $r['units']];
        }, $rows));
        
        return [
            'gwa' => $gwaData['gwa'],
            'year_level' => $prev_year_label,
            'semester' => $prev_sem_label,
            'total_units' => $gwaData['total_units'],
        ];
    } catch (PDOException $e) {
        return null;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
//  YEAR / SEMESTER HELPERS
// ═══════════════════════════════════════════════════════════════════════════

const YEAR_ORDER = [
    'Bridging'  => 0,
    '1st Year'  => 1,
    '2nd Year'  => 2,
    '3rd Year'  => 3,
    '4th Year'  => 4,
];

const SEM_ORDER = [
    '1st Semester' => 1,
    '2nd Semester' => 2,
];

function year_num(string $y): int  { return YEAR_ORDER[$y]  ?? 1; }
function sem_num(string $s): int   { return SEM_ORDER[$s]   ?? 1; }

/**
 * Parse a student's year_level string like "2nd Year - 2nd Semester"
 * Returns ['yr' => 2, 'sem' => 2]
 */
function parse_standing(string $str): array {
    $yr  = 1; $sem = 1;
    if (preg_match('/(\d+)(st|nd|rd|th)?\s*Year/i', $str, $m)) $yr = intval($m[1]);
    if (preg_match('/2nd\s*Sem/i', $str)) $sem = 2;
    return ['yr' => $yr, 'sem' => $sem];
}

/** Returns the NEXT semester a student should enrol in */
function next_semester(int $yr, int $sem): array {
    if ($sem === 1) return ['yr' => $yr, 'sem' => 2];
    return ['yr' => $yr + 1, 'sem' => 1];
}

/** Human-readable year label */
function year_label(int $n): string {
    return ['', '1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'][$n] ?? "{$n}th Year";
}

// ═══════════════════════════════════════════════════════════════════════════
//  PREREQUISITE RESOLUTION
//  Returns a map: subject_id → ['unlocked'=>bool, 'blocked_by'=>[...]]
//
//  Two layers:
//    A) direct: subject.prerequisite (text code) must be passed
//    B) set:    all subjects in a prerequisite SET linked to this subject
//               as target must be passed
// ═══════════════════════════════════════════════════════════════════════════

function build_prereq_unlock_map(
    array $subjects,        // all prospectus subjects
    array $gradeMap,        // subject_id → rounded grade
    array $prereqSets,      // from prerequisite_sets + prerequisite_set_subjects
    int   $majorId
): array {
    // Indexes
    $byCode = []; // UPPER(code) → subject
    $byId   = []; // id         → subject
    foreach ($subjects as $s) {
        $byId[$s['id']] = $s;
        if (!empty($s['subject_code'])) $byCode[strtoupper(trim($s['subject_code']))] = $s;
    }

    // Helper: has the student passed a subject?
    $hasPassed = function(int $sid) use ($gradeMap): bool {
        if (!isset($gradeMap[$sid])) return false;
        return grade_status($gradeMap[$sid]) === 'passed';
    };

    // Build set-prereq map: target_subject_id → [ prereq subject objects ]
    $setPrereqs = []; // target_id => [subject, ...]
    foreach ($prereqSets as $set) {
        // Only apply for this student's major
        if (!empty($set['major_id']) && intval($set['major_id']) !== $majorId) continue;
        if (empty($set['target_subject_id'])) continue;
        $tid = intval($set['target_subject_id']);
        if (!isset($setPrereqs[$tid])) $setPrereqs[$tid] = [];
        foreach (($set['subjects'] ?? []) as $ps) {
            $found = $byId[$ps['id']] ?? null;
            if (!$found && !empty($ps['subject_code'])) {
                $found = $byCode[strtoupper(trim($ps['subject_code']))] ?? null;
            }
            if ($found) $setPrereqs[$tid][] = $found;
        }
    }

    $result = [];
    foreach ($subjects as $s) {
        $sid = intval($s['id']);
        $blockedBy = []; // human-readable reasons

        // ── Layer A: direct prerequisite ──────────────────────────────────
        $directCode = strtoupper(trim($s['prerequisite'] ?? ''));
        $directLocked = false;
        $directPrereqSubj = null;
        if ($directCode !== '') {
            $directPrereqSubj = $byCode[$directCode] ?? null;
            if ($directPrereqSubj) {
                if (!$hasPassed(intval($directPrereqSubj['id']))) {
                    $directLocked = true;
                    $blockedBy[]  = [
                        'type'    => 'direct',
                        'code'    => $directCode,
                        'name'    => $directPrereqSubj['subject_name'] ?? '',
                        'subject' => $directPrereqSubj,
                        'grade'   => $gradeMap[intval($directPrereqSubj['id'])] ?? null,
                        'status'  => isset($gradeMap[intval($directPrereqSubj['id'])])
                                     ? grade_status($gradeMap[intval($directPrereqSubj['id'])]) : 'not_taken',
                    ];
                }
            }
            // If prerequisite subject not found in prospectus, treat as unlocked
        }

        // ── Layer B: prerequisite set ─────────────────────────────────────
        $setLocked = false;
        if (isset($setPrereqs[$sid])) {
            foreach ($setPrereqs[$sid] as $ps) {
                if (!$hasPassed(intval($ps['id']))) {
                    $setLocked   = true;
                    $psGrade     = $gradeMap[intval($ps['id'])] ?? null;
                    $blockedBy[] = [
                        'type'    => 'set',
                        'code'    => $ps['subject_code'],
                        'name'    => $ps['subject_name'] ?? '',
                        'subject' => $ps,
                        'grade'   => $psGrade,
                        'status'  => $psGrade !== null ? grade_status($psGrade) : 'not_taken',
                    ];
                }
            }
        }

        $result[$sid] = [
            'unlocked'         => !$directLocked && !$setLocked,
            'direct_locked'    => $directLocked,
            'set_locked'       => $setLocked,
            'direct_prereq'    => $directPrereqSubj,
            'blocked_by'       => $blockedBy,
        ];
    }
    return $result;
}

// ═══════════════════════════════════════════════════════════════════════════
//  LOAD PREREQUISITE SETS from DB
// ═══════════════════════════════════════════════════════════════════════════

function load_prereq_sets(PDO $pdo): array {
    $sets = [];
    try {
        $stmt = $pdo->query("
            SELECT ps.id, ps.code, ps.major_id, ps.target_subject_id,
                   ts.subject_code as target_code, ts.subject_name as target_name
            FROM prerequisite_sets ps
            LEFT JOIN subjects ts ON ps.target_subject_id = ts.id
            ORDER BY ps.id
        ");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $set) {
            $set['subjects'] = [];
            $stmt2 = $pdo->prepare("
                SELECT pss.subject_id, s.subject_code, s.subject_name, s.units
                FROM prerequisite_set_subjects pss
                JOIN subjects s ON pss.subject_id = s.id
                WHERE pss.set_id = ?
            ");
            $stmt2->execute([$set['id']]);
            $set['subjects'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            $sets[] = $set;
        }
    } catch (PDOException $e) {
        // Table may not exist yet — silently return empty
    }
    return $sets;
}

// ═══════════════════════════════════════════════════════════════════════════
//  LOAD PH SETTINGS
// ═══════════════════════════════════════════════════════════════════════════

function load_ph_settings(PDO $pdo): array {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'program_head_settings'");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['setting_value']) return json_decode($row['setting_value'], true) ?: [];
    } catch (PDOException $e) {}
    return [];
}

// ═══════════════════════════════════════════════════════════════════════════
//  ACTION: get_mentees
// ═══════════════════════════════════════════════════════════════════════════

if ($action === 'get_mentees') {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                s.id, s.first_name, s.middle_name, s.last_name, s.suffix,
                s.email, s.student_id AS student_number, s.year_level,
                s.student_type,
                s.avatar_initials, s.avatar_gradient_from, s.avatar_gradient_to,
                m.display_name AS major_name, m.id AS major_id,
                (
                    SELECT COUNT(*)
                    FROM student_grades sg
                    WHERE sg.student_id = s.id AND sg.graded_by = :iid
                ) AS graded_count,
                (
                    SELECT COUNT(*)
                    FROM major_subjects ms
                    WHERE ms.major_id = s.major_id
                ) AS total_subjects
            FROM mentees me
            JOIN students s   ON me.student_id = s.id
            LEFT JOIN majors m ON s.major_id   = m.id
            WHERE me.mentor_id = :iid
              AND (
                  s.student_type IS NULL
                  OR s.student_type = ''
                  OR (
                      SELECT COUNT(*) 
                      FROM student_grades sg2
                      WHERE sg2.student_id = s.id AND sg2.graded_by = :iid
                  ) < (
                      SELECT COUNT(*) 
                      FROM major_subjects ms2 
                      WHERE ms2.major_id = s.major_id
                  )
              )
            ORDER BY s.last_name, s.first_name
        ");
        $stmt->execute([':iid' => $instructor_id]);
        echo json_encode(['success' => true, 'mentees' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
//  ACTION: save_student_type
//  Saves the student classification selected by the instructor
// ═══════════════════════════════════════════════════════════════════════════

if ($action === 'save_student_type') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $student_type = $_POST['student_type'] ?? '';

    // Validate student_type
    $allowed_types = ['regular', 'transfer', 'non_ibm'];
    if (!in_array($student_type, $allowed_types)) {
        jsonError('Invalid student type');
    }

    // Verify student exists and instructor has access
    try {
        $stmt = $pdo->prepare("
            SELECT s.id FROM students s
            JOIN mentees me ON s.id = me.student_id
            WHERE s.id = ? AND me.mentor_id = ?
        ");
        $stmt->execute([$student_id, $instructor_id]);
        if (!$stmt->fetch()) {
            jsonError('Student not found or access denied');
        }

        // Save student_type
        $stmt = $pdo->prepare("
            UPDATE students 
            SET student_type = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$student_type, $student_id]);

        jsonResponse(['success' => true, 'message' => 'Student type saved']);
    } catch (PDOException $e) {
        jsonError('Database error: ' . $e->getMessage());
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
//  ACTION: get_student_evaluation
//  Loads the full department prospectus template (in sort_order) and merges
//  saved grades.  Also returns a prereq_map for the JS to render ★ codes.
// ═══════════════════════════════════════════════════════════════════════════

if ($action === 'get_student_evaluation') {
    $student_id    = intval($_POST['student_id']    ?? 0);
    $academic_year = $_POST['academic_year']        ?? '2025-2026';

    try {
        // Student
        $stmt = $pdo->prepare("
            SELECT s.*, m.display_name AS major_name, m.id AS major_id,
                   m.gradient_from, m.gradient_to, m.icon_class
            FROM students s
            LEFT JOIN majors m ON s.major_id = m.id
            WHERE s.id = ?
        ");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$student) { echo json_encode(['success' => false, 'message' => 'Student not found']); exit; }

        $major_id = intval($student['major_id']);

        // ── Prospectus template (EXACT sort_order from department page) ──
        $stmt2 = $pdo->prepare("
            SELECT
                s.id, s.subject_code, s.subject_name, s.units,
                s.prerequisite        AS subject_prerequisite,
                s.bridging_for,
                ms.year_level, ms.semester,
                ms.is_prerequisite, ms.is_required,
                ms.sort_order,
                COALESCE(ms.prerequisite, s.prerequisite) AS prerequisite,
                COALESCE(ms.prerequisite, s.prerequisite) AS display_prerequisite
            FROM major_subjects ms
            JOIN subjects s ON ms.subject_id = s.id
            WHERE ms.major_id = ?
            ORDER BY ms.sort_order ASC, FIELD(ms.year_level,'1st Year','2nd Year','3rd Year','4th Year','Bridging'), FIELD(ms.semester,'1st Semester','2nd Semester')
        ");
        $stmt2->execute([$major_id]);
        $prospectus = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // ── Saved grades (all academic years, latest wins per subject) ──
        $stmt3 = $pdo->prepare("
            SELECT sg.*
            FROM student_grades sg
            WHERE sg.student_id = ?
            ORDER BY sg.graded_at DESC
        ");
        $stmt3->execute([$student_id]);
        $gradeIndex = [];
        foreach ($stmt3->fetchAll(PDO::FETCH_ASSOC) as $g) {
            // Latest grade per subject_id
            if (!isset($gradeIndex[$g['subject_id']])) {
                $gradeIndex[$g['subject_id']] = $g;
            }
        }

        // Fetch student subject load (which subjects the student is actually enrolled in)
        $loadSubjects = [];
        try {
            $stmtLoad = $pdo->prepare("
                SELECT subject_id, year_level, semester
                FROM student_subject_load
                WHERE student_id = ? AND academic_year = ?
            ");
            $stmtLoad->execute([$student_id, $academic_year]);
            foreach ($stmtLoad->fetchAll(PDO::FETCH_ASSOC) as $load) {
                $loadSubjects[$load['subject_id']] = [
                    'year_level' => $load['year_level'],
                    'semester'   => $load['semester']
                ];
            }
        } catch (PDOException $e) {}

        // ── Merge ──
        $merged = [];
        foreach ($prospectus as $subj) {
            $g = $gradeIndex[$subj['id']] ?? null;
            $rounded = $g && $g['grade_rounded'] !== null ? floatval($g['grade_rounded']) : null;
            $subj['grade']         = $g ? $g['grade'] : null;
            $subj['grade_rounded'] = $rounded;
            $subj['grade_status']  = $g ? $g['status'] : 'not_taken';
            $subj['grade_label']   = $rounded !== null ? grade_label($rounded) : null;
            $subj['graded_at']     = $g ? $g['graded_at'] : null;
            $subj['remarks']       = $g ? ($g['remarks'] ?? '') : '';
            // Load information
            $subj['is_in_load']    = isset($loadSubjects[$subj['id']]);
            $subj['load_year']     = $loadSubjects[$subj['id']]['year_level'] ?? null;
            $subj['load_semester'] = $loadSubjects[$subj['id']]['semester'] ?? null;
            $merged[] = $subj;
        }

        // ── GWA ──
        $gwaData = compute_gwa(array_filter($merged, fn($s) => $s['grade_rounded'] !== null));

        // ── Previous semester GWA for anomaly detection ──
        $student_year = $student['year_level'] ?? '1st Year - 1st Semester';
        $parts = explode(' - ', $student_year);
        $current_year = $parts[0] ?? '1st Year';
        $current_sem = $parts[1] ?? '1st Semester';
        
        $prevGwaData = get_previous_semester_gwa($pdo, $student_id, $current_year, $current_sem);

        // ── Prereq map for JS rendering (subject_id → prereq set code) ──
        $prereqMap = [];
        try {
            $stmtP = $pdo->query("
                SELECT ps.target_subject_id, ps.code, pss.subject_id
                FROM prerequisite_sets ps
                LEFT JOIN prerequisite_set_subjects pss ON ps.id = pss.set_id
            ");
            foreach ($stmtP->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($row['target_subject_id']) $prereqMap[$row['target_subject_id']] = $row['code'];
                if ($row['subject_id'])        $prereqMap[$row['subject_id']]        = $row['code'];
            }
        } catch (PDOException $e) {}

        // Fetch finalized sessions
        $finalizedSessions = [];
        try {
            $stmtFS = $pdo->prepare("
                SELECT id, year_level, semester, academic_year, session_status, gwa
                FROM evaluation_sessions
                WHERE student_id = ? AND session_status = 'finalized'
                ORDER BY academic_year DESC, FIELD(semester,'1st Semester','2nd Semester'), year_level
            ");
            $stmtFS->execute([$student_id]);
            foreach ($stmtFS->fetchAll(PDO::FETCH_ASSOC) as $fs) {
                $key = $fs['year_level'] . '|' . $fs['semester'];
                $finalizedSessions[$key] = $fs;
            }
        } catch (PDOException $e) {}

        $advisorName = '';
        try {
            $stmtAdv = $pdo->prepare("SELECT first_name, middle_name, last_name, suffix FROM instructors WHERE id = ?");
            $stmtAdv->execute([$instructor_id]);
            $advRow = $stmtAdv->fetch(PDO::FETCH_ASSOC);
            if ($advRow) {
                $advisorName = trim($advRow['first_name'] . ($advRow['middle_name'] ? ' ' . substr($advRow['middle_name'], 0, 1) . '.' : '') . ' ' . $advRow['last_name'] . ($advRow['suffix'] ? ' ' . $advRow['suffix'] : ''));
            }
        } catch (PDOException $e) {}

        // Get program head name from instructors table (program head is promoted from instructors)
        $programHeadName = '';
        try {
            $stmtPH = $pdo->query("
                SELECT i.first_name, i.middle_name, i.last_name, i.suffix 
                FROM instructors i 
                LEFT JOIN admin_promotions ap ON i.id = ap.instructor_id 
                WHERE ap.promoted_to = 'program_head' 
                ORDER BY ap.promotion_date DESC 
                LIMIT 1
            ");
            $phRow = $stmtPH->fetch(PDO::FETCH_ASSOC);
            if ($phRow) {
                $programHeadName = trim($phRow['first_name'] . ($phRow['middle_name'] ? ' ' . substr($phRow['middle_name'], 0, 1) . '.' : '') . ' ' . $phRow['last_name'] . ($phRow['suffix'] ? ' ' . $phRow['suffix'] : ''));
            }
        } catch (PDOException $e) {}

        echo json_encode([
            'success'            => true,
            'student'          => $student,
            'subjects'         => $merged,
            'gwa_data'         => $gwaData,
            'prev_gwa_data'   => $prevGwaData,
            'academic_year'   => $academic_year,
            'prereq_map'      => $prereqMap,
            'ph_settings'     => load_ph_settings($pdo),
            'finalized_sessions' => $finalizedSessions,
            'advisor_name'     => $advisorName,
            'program_head_name' => $programHeadName,
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

  // ═══════════════════════════════════════════════════════════════════════════
  //  ACTION: save_enrollment_list
  // ═══════════════════════════════════════════════════════════════════════════
  
  if ($action === 'save_enrollment_list') {
      $student_id    = intval($_POST['student_id']    ?? 0);
      $major_id      = intval($_POST['major_id']      ?? 0);
      $academic_year = $_POST['academic_year']        ?? '2025-2026';
      $subject_ids   = json_decode($_POST['subject_ids'] ?? '[]', true);
      $year_level    = $_POST['year_level']           ?? '';
      $semester      = $_POST['semester']              ?? '';
      
      if (!$student_id || !$major_id || !is_array($subject_ids)) {
          echo json_encode(['success' => false, 'message' => 'Missing required fields']);
          exit;
      }
      
      try {
          $pdo->beginTransaction();
          
          // Delete existing load entries for this student/semester/year
          $pdo->prepare("DELETE FROM student_subject_load WHERE student_id = ? AND academic_year = ? AND year_level = ? AND semester = ?")
             ->execute([$student_id, $academic_year, $year_level, $semester]);
          
          // Insert new load entries
          $stmt = $pdo->prepare("INSERT INTO student_subject_load (student_id, major_id, subject_id, academic_year, year_level, semester) VALUES (?,?,?,?,?,?)");
          foreach ($subject_ids as $sid) {
              $sid = intval($sid);
              if ($sid > 0) {
                  $stmt->execute([$student_id, $major_id, $sid, $academic_year, $year_level, $semester]);
              }
          }
          
          $pdo->commit();
          
          echo json_encode([
              'success' => true,
              'message' => 'Enrollment list saved successfully'
          ]);
      } catch (PDOException $e) {
          $pdo->rollBack();
          echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }
  exit;
}

// ═══════════════════════════════════════════════════════════════════════════
//  ACTION: save_grade
// ═══════════════════════════════════════════════════════════════════════════

if ($action === 'save_grade') {
    $student_id    = intval($_POST['student_id']    ?? 0);
    $subject_id    = intval($_POST['subject_id']    ?? 0);
    $major_id      = intval($_POST['major_id']      ?? 0);
    $semester      = $_POST['semester']           ?? '';
    $year_level    = $_POST['year_level']         ?? '';
    $academic_year = $_POST['academic_year']      ?? '2025-2026';
    $raw_grade     = $_POST['grade']              ?? 0;
    $remarks       = trim($_POST['remarks']       ?? '');
    
    $raw = floatval($raw_grade);
    if ($raw < 1.00 || $raw > 5.00) {
        echo json_encode(['success' => false, 'message' => 'Grade must be between 1.00 and 5.00']);
        exit;
    }

    $rounded = round_grade($raw);
    $status  = grade_status($rounded);
    $label   = grade_label($rounded);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO student_grades
                (student_id, subject_id, major_id, grade, grade_rounded, status,
                 semester, year_level, academic_year, graded_by, graded_at, remarks)
            VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),?)
            ON DUPLICATE KEY UPDATE
                grade         = VALUES(grade),
                grade_rounded = VALUES(grade_rounded),
                status        = VALUES(status),
                graded_by     = VALUES(graded_by),
                graded_at     = NOW(),
                remarks       = VALUES(remarks)
        ");
        $stmt->execute([
            $student_id, $subject_id, $major_id, $raw, $rounded, $status,
            $semester, $year_level, $academic_year, $instructor_id, $remarks
        ]);
        echo json_encode([
            'success'       => true,
            'message'       => 'Grade saved successfully',
            'grade_rounded' => $rounded,
            'grade_raw'     => $raw,
            'status'        => $status,
            'label'         => $label,
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
//  ACTION: get_advisement
//
//  Rules implemented:
//  1. Grade Processing     — grades from DB are truth; grade_rounded is used
//  2. Failure Logic        — failed → RETAKE list (re-queued)
//  3. Prerequisite Lock    — direct code + prereq set both checked
//  4. Priority Retakes     — retake list separated, must be enrolled first
//  5. Semester Sequencing  — recommended filtered to next semester first;
//                            future-year subjects blocked if current year
//                            still has pending/failed subjects
// ═══════════════════════════════════════════════════════════════════════════

if ($action === 'get_advisement') {
    $student_id    = intval($_POST['student_id'] ?? 0);
    $major_id      = intval($_POST['major_id']   ?? 0);
    $academic_year = $_POST['academic_year']      ?? '2025-2026';

    try {
        // ── All prospectus subjects (sorted by sort_order = department order) ──
        $stmt = $pdo->prepare("
            SELECT
                s.id, s.subject_code, s.subject_name, s.units,
                s.prerequisite AS subject_prerequisite, s.bridging_for,
                ms.year_level, ms.semester, ms.is_prerequisite, ms.is_required,
                ms.sort_order,
                COALESCE(ms.prerequisite, s.prerequisite) AS prerequisite
            FROM major_subjects ms
            JOIN subjects s ON ms.subject_id = s.id
            WHERE ms.major_id = ?
            ORDER BY ms.sort_order ASC,
                     FIELD(ms.year_level,'1st Year','2nd Year','3rd Year','4th Year','Bridging'),
                     FIELD(ms.semester,'1st Semester','2nd Semester')
        ");
        $stmt->execute([$major_id]);
        $allSubjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Student grades (latest per subject) ──
        $stmt2 = $pdo->prepare("
            SELECT * FROM student_grades
            WHERE student_id = ?
            ORDER BY graded_at DESC
        ");
        $stmt2->execute([$student_id]);
        $gradeIndex = [];
        foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $g) {
            if (!isset($gradeIndex[$g['subject_id']])) $gradeIndex[$g['subject_id']] = $g;
        }

        // Build gradeMap: subject_id → rounded grade (float)
        $gradeMap = [];
        foreach ($gradeIndex as $sid => $g) {
            if ($g['grade_rounded'] !== null) $gradeMap[$sid] = floatval($g['grade_rounded']);
        }

        // ── Student current standing ──
        $stmt3 = $pdo->prepare("SELECT year_level FROM students WHERE id = ?");
        $stmt3->execute([$student_id]);
        $stu = $stmt3->fetch(PDO::FETCH_ASSOC);
        $currentYearStr = $stu['year_level'] ?? '1st Year';
        ['yr' => $cYr, 'sem' => $cSem] = parse_standing($currentYearStr);
        ['yr' => $nYr, 'sem' => $nSem] = next_semester($cYr, $cSem);

        // ── Load prerequisite sets ──
        $prereqSets = load_prereq_sets($pdo);

        // ── Build unlock map ──
        $unlockMap = build_prereq_unlock_map($allSubjects, $gradeMap, $prereqSets, $major_id);

        // ════════════════════════════════════════════════════════════════
        //  RULE 5 — Semester sequencing
        //  Detect if current year has any PENDING (not_taken + unblocked)
        //  or FAILED subjects. If so, suppress higher-year "recommended" entries.
        // ════════════════════════════════════════════════════════════════
        $hasPendingInCurrentYear = false;
        foreach ($allSubjects as $subj) {
            $sid    = intval($subj['id']);
            $sYr    = year_num($subj['year_level']);
            if ($sYr !== $cYr) continue;
            $g      = $gradeIndex[$sid] ?? null;
            $status = $g ? $g['status'] : 'not_taken';
            $unlock = $unlockMap[$sid] ?? ['unlocked' => true];
            // Pending = not taken AND prerequisite already met
            if ($status === 'not_taken' && $unlock['unlocked']) {
                $hasPendingInCurrentYear = true;
                break;
            }
            if ($status === 'failed') {
                $hasPendingInCurrentYear = true;
                break;
            }
        }

        // ════════════════════════════════════════════════════════════════
        //  CLASSIFY each subject
        // ════════════════════════════════════════════════════════════════
        $recommended = []; // available for next enrollment
        $retake      = []; // RULE 2 + 4: failed, must re-enroll first
        $conditional = []; // grade 4.00 — removal exam
        $blocked     = []; // RULE 3: prerequisite not met
        $completed   = []; // passed
        $notYet      = []; // future year, not yet time

        foreach ($allSubjects as $subj) {
            $sid    = intval($subj['id']);
            $g      = $gradeIndex[$sid] ?? null;
            $status = $g ? $g['status'] : 'not_taken';
            $unlock = $unlockMap[$sid] ?? ['unlocked' => true];

            $sYr  = year_num($subj['year_level']);
            $sSem = sem_num($subj['semester'] ?? '1st Semester');

            // Attach grade info to subject for display
            $subj['grade_rounded'] = isset($gradeMap[$sid]) ? $gradeMap[$sid] : null;
            $subj['grade_status']  = $status;
            $subj['grade_label']   = isset($gradeMap[$sid]) ? grade_label($gradeMap[$sid]) : null;

            // ── Completed ──
            if ($status === 'passed') {
                $completed[] = $subj;
                continue;
            }

            // ── RULE 2+4: Failed → priority retake ──
            if ($status === 'failed') {
                // Check if retake itself needs a (different) prerequisite now
                // (edge case: student failed subject A which has prereq B not yet passed)
                if (!$unlock['unlocked']) {
                    $subj['reason']     = 'Failed — but prerequisite still not passed';
                    $subj['blocked_by'] = $unlock['blocked_by'];
                    $blocked[] = $subj;
                } else {
                    $subj['reason'] = 'Failed — must retake (priority)';
                    $retake[] = $subj;
                }
                continue;
            }

            // ── Conditional: grade 4.00 ──
            if ($status === 'conditional') {
                $subj['reason'] = 'Grade 4.00 — removal exam required';
                $conditional[] = $subj;
                continue;
            }

            // ── Not yet taken ──
            if ($status === 'not_taken') {

                // RULE 3: prerequisite not met → blocked
                if (!$unlock['unlocked']) {
                    // Build human-readable reason
                    $reasons = [];
                    foreach ($unlock['blocked_by'] as $bl) {
                        $gradeStr = $bl['grade'] !== null
                            ? ' (' . number_format($bl['grade'], 2) . ' — ' . grade_label($bl['grade']) . ')'
                            : ' (no grade yet)';
                        $reasons[] = 'Must pass ' . $bl['code'] . $gradeStr;
                    }
                    $subj['reason']     = implode('; ', $reasons) ?: 'Prerequisite not yet passed';
                    $subj['blocked_by'] = $unlock['blocked_by'];
                    $blocked[] = $subj;
                    continue;
                }

                // RULE 5: suppress higher-year if current year still has pending/failed
                if ($hasPendingInCurrentYear && $sYr > $cYr + 1) {
                    $subj['reason'] = year_label($sYr) . ' — ' . ($subj['semester'] ?? '');
                    $notYet[] = $subj;
                    continue;
                }

                // Available for enrollment
                $subj['reason'] = 'Available for enrollment';
                $recommended[] = $subj;
                continue;
            }
        }

        // ── Compute progress summary ──
        $totalUnits     = array_sum(array_map(fn($s) => floatval($s['units']), $allSubjects));
        $completedUnits = array_sum(array_map(fn($s) => floatval($s['units']), $completed));
        $remainingUnits = $totalUnits - $completedUnits;

        // ── Credit-load note (standard Philippine limit: 21 units/sem) ──
        $maxCredits   = 21;
        $retakeUnits  = array_sum(array_map(fn($s) => floatval($s['units']), $retake));
        $retakeUnits += array_sum(array_map(fn($s) => floatval($s['units']), $conditional));
        $remainSlots  = max(0, $maxCredits - $retakeUnits);

        // Split recommended into "next semester" vs "later"
        $nextSemRec  = [];
        $laterRec    = [];
        foreach ($recommended as $subj) {
            $sYr  = year_num($subj['year_level']);
            $sSem = sem_num($subj['semester'] ?? '1st Semester');
            if ($sYr === $nYr && $sSem === $nSem) {
                $nextSemRec[] = $subj;
            } else {
                $laterRec[] = $subj;
            }
        }

        echo json_encode([
            'success' => true,
            'advisement' => [
                'recommended'  => $recommended,    // all available (next + later)
                'next_sem_rec' => $nextSemRec,     // specifically next semester
                'later_rec'    => $laterRec,       // future semesters
                'retake'       => $retake,         // PRIORITY
                'conditional'  => $conditional,
                'blocked'      => $blocked,
                'completed'    => $completed,
                'not_yet'      => $notYet,         // future-year blocked by sequencing
            ],
            'summary' => [
                'total_subjects'    => count($allSubjects),
                'completed_count'   => count($completed),
                'total_units'       => $totalUnits,
                'completed_units'   => $completedUnits,
                'remaining_units'   => $remainingUnits,
                'retake_count'      => count($retake) + count($conditional),
                'blocked_count'     => count($blocked),
                'max_credits'       => $maxCredits,
                'retake_units_load' => $retakeUnits,
                'remaining_slots'   => $remainSlots,    // units left after retakes
            ],
            'current_year'  => $currentYearStr,
            'current_yr'    => $cYr,
            'current_sem'   => $cSem,
            'next_yr'       => $nYr,
            'next_sem'      => $nSem,
            'next_year'     => year_label($nYr) . ' — ' . ($nSem === 1 ? '1st Semester' : '2nd Semester'),
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
//  ACTION: finalize_session
// ═══════════════════════════════════════════════════════════════════════════

if ($action === 'finalize_session') {
    $student_id    = intval($_POST['student_id']    ?? 0);
    $major_id      = intval($_POST['major_id']      ?? 0);
    $academic_year = $_POST['academic_year']        ?? '2025-2026';
    $year_level    = $_POST['year_level']          ?? '1st Year';
    $semester      = $_POST['semester']             ?? '1st Semester';
    $notes         = trim($_POST['notes']           ?? '');

    try {
        // Compute GWA from grades for this specific year_level and semester
        $stmt = $pdo->prepare("
            SELECT sg.grade_rounded, s.units
            FROM student_grades sg
            JOIN subjects s ON sg.subject_id = s.id
            WHERE sg.student_id = ? AND sg.year_level = ? AND sg.semester = ? AND sg.grade_rounded IS NOT NULL
        ");
        $stmt->execute([$student_id, $year_level, $semester]);
        $gwaData = compute_gwa($stmt->fetchAll(PDO::FETCH_ASSOC));

        $stmt2 = $pdo->prepare("
            INSERT INTO evaluation_sessions
                (instructor_id, student_id, major_id, academic_year, year_level, semester,
                 session_status, gwa, total_units_taken, total_units_passed, notes)
            VALUES (?,?,?,?,?,?,'finalized',?,?,?,?)
            ON DUPLICATE KEY UPDATE
                session_status    = 'finalized',
                gwa               = VALUES(gwa),
                total_units_taken = VALUES(total_units_taken),
                total_units_passed= VALUES(total_units_passed),
                notes             = VALUES(notes),
                updated_at        = NOW()
        ");
        $stmt2->execute([
            $instructor_id, $student_id, $major_id, $academic_year, $year_level, $semester,
            $gwaData['gwa'], $gwaData['total_units'], $gwaData['units_passed'], $notes
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Evaluation session finalized successfully.',
            'gwa'     => $gwaData,
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
     exit;
 }
 
 //  ACTION: unfinalize_session
 // ═══════════════════════════════════════════════════════════════════════════
 
 if ($action === 'unfinalize_session') {
     $student_id    = intval($_POST['student_id']    ?? 0);
     $major_id      = intval($_POST['major_id']      ?? 0);
     $academic_year = $_POST['academic_year']        ?? '2025-2026';
     $year_level    = $_POST['year_level']          ?? '1st Year';
     $semester      = $_POST['semester']             ?? '1st Semester';
 
     try {
         $stmt = $pdo->prepare("
             DELETE FROM evaluation_sessions 
             WHERE student_id = ? 
             AND major_id = ? 
             AND academic_year = ? 
             AND year_level = ? 
             AND semester = ?
         ");
         $stmt->execute([$student_id, $major_id, $academic_year, $year_level, $semester]);
         
         jsonResponse([
             'success' => true,
             'message' => 'Evaluation session unfinalized successfully.'
         ]);
     } catch (PDOException $e) {
         jsonResponse(['success' => false, 'message' => $e->getMessage()]);
     }
     exit;
 }
 
 //  ACTION: save_enrollment_list
 // ═══════════════════════════════════════════════════════════════════════════
 
 if ($action === 'save_enrollment_list') {
     $student_id    = intval($_POST['student_id']    ?? 0);
     $academic_year = $_POST['academic_year']        ?? '2025-2026';
     $subject_ids   = json_decode($_POST['subject_ids'] ?? '[]', true);
     $to_year       = $_POST['to_year']              ?? '';
     $to_sem        = $_POST['to_sem']               ?? '';
     
     try {
         jsonResponse([
             'success' => true,
             'message' => 'Enrollment list saved successfully'
         ]);
     } catch (PDOException $e) {
         jsonResponse(['success' => false, 'message' => $e->getMessage()]);
     }
     exit;
 }

 // ═══════════════════════════════════════════════════════════════════════════
 //  ACTION: promote_student
//  Updates the student's year_level when promoted to next year/semester
// ═══════════════════════════════════════════════════════════════════════════

if ($action === 'promote_student') {
    $student_id    = intval($_POST['student_id']    ?? 0);
    $to_year     = trim($_POST['to_year']     ?? '');
    $to_sem     = trim($_POST['to_sem']     ?? '');

    if (!$student_id || !$to_year || !$to_sem) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    try {
        // Update student's year_level in the database
        $new_year_level = "{$to_year} - {$to_sem}";
        $stmt = $pdo->prepare("UPDATE students SET year_level = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_year_level, $student_id]);

        echo json_encode([
            'success'     => true,
            'message'     => 'Student promoted to ' . htmlspecialchars($new_year_level),
            'year_level'   => $new_year_level,
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════════
//  ACTION: verify_password
//  Verifies instructor password for editing finalized grades
// ═══════════════════════════════════════════════════════════════════

if ($action === 'verify_password') {
    $password = trim($_POST['password'] ?? '');
    
    if (!$password) {
        echo json_encode(['success' => false, 'message' => 'Password required']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, password FROM instructors WHERE id = ? LIMIT 1");
        $stmt->execute([$instructor_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════════
//  FALLBACK
// ═══════════════════════════════════════════════════════════════════

echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
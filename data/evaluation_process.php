<?php
// evaluation_process.php
// Place at: data/evaluation_process.php

header('Content-Type: application/json');
require_once __DIR__ . '/session_security.php';
require_once __DIR__ . '/config.php';

$role_access = check_role_access('instructor');
if (!$role_access['allowed']) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$instructor_id = $_SESSION['user_id'] ?? 0;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── GRADE ROUNDING (Philippine college system) ─────────────────────────────
function round_grade(float $raw): float {
    $valid = [1.00, 1.25, 1.50, 1.75, 2.00, 2.25, 2.50, 2.75, 3.00, 4.00, 5.00];
    $closest = 5.00;
    $minDiff = PHP_FLOAT_MAX;
    foreach ($valid as $v) {
        $diff = abs($raw - $v);
        if ($diff < $minDiff) {
            $minDiff = $diff;
            $closest = $v;
        }
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
        1.00 => 'Excellent',
        1.25 => 'Very Good',
        1.50 => 'Very Good',
        1.75 => 'Good',
        2.00 => 'Satisfactory',
        2.25 => 'Fair',
        2.50 => 'Passing',
        2.75 => 'Low Passing',
        3.00 => 'Barely Passing',
        4.00 => 'Conditional',
        5.00 => 'Failed',
    ];
    return $map[$grade] ?? 'Unknown';
}

// ─── COMPUTE GWA ─────────────────────────────────────────────────────────────
function compute_gwa(array $grades): array {
    $totalPoints = 0;
    $totalUnits = 0;
    $unitsPassed = 0;
    foreach ($grades as $g) {
        if ($g['grade_rounded'] === null) continue;
        $units = floatval($g['units']);
        $grade = floatval($g['grade_rounded']);
        $totalPoints += $grade * $units;
        $totalUnits += $units;
        if (grade_status($grade) === 'passed') $unitsPassed += $units;
    }
    $gwa = $totalUnits > 0 ? round($totalPoints / $totalUnits, 2) : null;
    return ['gwa' => $gwa, 'total_units' => $totalUnits, 'units_passed' => $unitsPassed];
}

// ─── GET MY MENTEES ───────────────────────────────────────────────────────────
if ($action === 'get_mentees') {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT s.id, s.first_name, s.middle_name, s.last_name, s.suffix,
                   s.email, s.student_id as student_number, s.year_level, s.avatar_initials,
                   s.avatar_gradient_from, s.avatar_gradient_to,
                   m.display_name as major_name, m.id as major_id,
                   m.gradient_from as major_gradient_from, m.gradient_to as major_gradient_to,
                   (SELECT COUNT(*) FROM student_grades sg WHERE sg.student_id = s.id AND sg.graded_by = :iid) as graded_count,
                   (SELECT COUNT(*) FROM major_subjects ms WHERE ms.major_id = s.major_id) as total_subjects
            FROM mentees me
            JOIN students s ON me.student_id = s.id
            LEFT JOIN majors m ON s.major_id = m.id
            WHERE me.mentor_id = :iid
            ORDER BY s.last_name, s.first_name
        ");
        $stmt->execute([':iid' => $instructor_id]);
        $mentees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'mentees' => $mentees]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ─── GET STUDENT FULL PROFILE + PROSPECTUS ───────────────────────────────────
if ($action === 'get_student_evaluation') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $academic_year = $_POST['academic_year'] ?? '2025-2026';

    try {
        // Student info
        $stmt = $pdo->prepare("
            SELECT s.*, m.display_name as major_name, m.id as major_id,
                   m.gradient_from, m.gradient_to, m.icon_class
            FROM students s
            LEFT JOIN majors m ON s.major_id = m.id
            WHERE s.id = ?
        ");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$student) { echo json_encode(['success' => false, 'message' => 'Student not found']); exit; }

        $major_id = $student['major_id'];

        // Get all subjects for this major (prospectus)
        $stmt2 = $pdo->prepare("
            SELECT s.*, ms.year_level, ms.semester, ms.is_prerequisite, ms.is_required,
                   ms.prerequisite, ms.sort_order,
                   COALESCE(ms.prerequisite, s.prerequisite) as display_prerequisite
            FROM major_subjects ms
            JOIN subjects s ON ms.subject_id = s.id
            WHERE ms.major_id = ?
            ORDER BY ms.year_level, ms.semester, ms.sort_order, s.subject_name
        ");
        $stmt2->execute([$major_id]);
        $prospectus = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // Get existing grades for this student
        $stmt3 = $pdo->prepare("
            SELECT sg.*, s.subject_code, s.subject_name, s.units
            FROM student_grades sg
            JOIN subjects s ON sg.subject_id = s.id
            WHERE sg.student_id = ?
            ORDER BY sg.year_level, sg.semester
        ");
        $stmt3->execute([$student_id]);
        $grades_raw = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        // Index grades by subject_id + semester + academic_year
        $grades = [];
        foreach ($grades_raw as $g) {
            $grades[$g['subject_id']] = $g;
        }

        // Merge prospectus + grades
        $subjects_with_grades = [];
        foreach ($prospectus as $subj) {
            $g = $grades[$subj['id']] ?? null;
            $subj['grade'] = $g ? $g['grade'] : null;
            $subj['grade_rounded'] = $g ? $g['grade_rounded'] : null;
            $subj['grade_status'] = $g ? $g['status'] : 'not_taken';
            $subj['grade_label'] = $g && $g['grade_rounded'] ? grade_label(floatval($g['grade_rounded'])) : null;
            $subj['graded_at'] = $g ? $g['graded_at'] : null;
            $subj['remarks'] = $g ? $g['remarks'] : null;
            $subjects_with_grades[] = $subj;
        }

        // GWA
        $gwaData = compute_gwa(array_filter($subjects_with_grades, fn($s) => $s['grade_rounded'] !== null));

        echo json_encode([
            'success' => true,
            'student' => $student,
            'subjects' => $subjects_with_grades,
            'gwa_data' => $gwaData,
            'academic_year' => $academic_year,
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ─── SAVE / UPDATE GRADE ─────────────────────────────────────────────────────
if ($action === 'save_grade') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $major_id   = intval($_POST['major_id'] ?? 0);
    $raw_grade  = $_POST['grade'] ?? '';
    $semester   = $_POST['semester'] ?? '1st Semester';
    $year_level = $_POST['year_level'] ?? '1st Year';
    $academic_year = $_POST['academic_year'] ?? '2025-2026';
    $remarks    = trim($_POST['remarks'] ?? '');

    if (!$student_id || !$subject_id || $raw_grade === '') {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

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
                (student_id, subject_id, major_id, grade, grade_rounded, status, semester, year_level, academic_year, graded_by, graded_at, remarks)
            VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),?)
            ON DUPLICATE KEY UPDATE
                grade=VALUES(grade), grade_rounded=VALUES(grade_rounded), status=VALUES(status),
                graded_by=VALUES(graded_by), graded_at=NOW(), remarks=VALUES(remarks)
        ");
        $stmt->execute([$student_id, $subject_id, $major_id, $raw, $rounded, $status, $semester, $year_level, $academic_year, $instructor_id, $remarks]);
        echo json_encode([
            'success' => true,
            'message' => 'Grade saved successfully',
            'grade_rounded' => $rounded,
            'grade_raw' => $raw,
            'status' => $status,
            'label' => $label,
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ─── GENERATE ADVISEMENT ─────────────────────────────────────────────────────
if ($action === 'get_advisement') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $major_id   = intval($_POST['major_id'] ?? 0);

    try {
        // All prospectus subjects
        $stmt = $pdo->prepare("
            SELECT s.*, ms.year_level, ms.semester, ms.is_prerequisite, ms.is_required,
                   ms.sort_order, s.prerequisite
            FROM major_subjects ms
            JOIN subjects s ON ms.subject_id = s.id
            WHERE ms.major_id = ?
            ORDER BY ms.year_level, ms.semester, ms.sort_order
        ");
        $stmt->execute([$major_id]);
        $allSubjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Student grades
        $stmt2 = $pdo->prepare("SELECT * FROM student_grades WHERE student_id = ?");
        $stmt2->execute([$student_id]);
        $grades = [];
        foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $g) {
            $grades[$g['subject_id']] = $g;
        }

        // Student current info
        $stmt3 = $pdo->prepare("SELECT year_level FROM students WHERE id = ?");
        $stmt3->execute([$student_id]);
        $stu = $stmt3->fetch(PDO::FETCH_ASSOC);
        $currentYear = $stu['year_level'] ?? '1st Year';

        $yearOrder = ['1st Year' => 1, '2nd Year' => 2, '3rd Year' => 3, '4th Year' => 4, 'Bridging' => 0];
        $currentYearNum = $yearOrder[$currentYear] ?? 1;

        // Build advisement
        $recommended = [];
        $retake = [];
        $blocked = [];
        $completed = [];
        $conditional = [];

        // Index passed subjects by code for prerequisite checking
        $passedCodes = [];
        foreach ($grades as $sid => $g) {
            if (in_array($g['status'], ['passed'])) {
                foreach ($allSubjects as $subj) {
                    if ($subj['id'] == $sid) {
                        $passedCodes[] = $subj['subject_code'];
                        break;
                    }
                }
            }
        }

        foreach ($allSubjects as $subj) {
            $g = $grades[$subj['id']] ?? null;
            $status = $g ? $g['status'] : 'not_taken';
            $subjYearNum = $yearOrder[$subj['year_level']] ?? 1;

            // Check prerequisite
            $prereqMet = true;
            $prereqNote = '';
            if (!empty($subj['prerequisite'])) {
                if (!in_array($subj['prerequisite'], $passedCodes)) {
                    $prereqMet = false;
                    $prereqNote = 'Prerequisite not yet passed: ' . $subj['prerequisite'];
                }
            }

            if ($status === 'passed') {
                $completed[] = array_merge($subj, ['grade_rounded' => $g['grade_rounded'], 'status' => 'completed']);
            } elseif ($status === 'failed') {
                $retake[] = array_merge($subj, ['grade_rounded' => $g['grade_rounded'], 'reason' => 'Failed — must retake']);
            } elseif ($status === 'conditional') {
                $conditional[] = array_merge($subj, ['grade_rounded' => $g['grade_rounded'], 'reason' => 'Conditional — removal exam required']);
            } elseif ($status === 'not_taken') {
                if (!$prereqMet) {
                    $blocked[] = array_merge($subj, ['reason' => $prereqNote]);
                } elseif ($subjYearNum <= $currentYearNum + 1) {
                    $recommended[] = array_merge($subj, ['reason' => 'Available for enrollment']);
                }
            }
        }

        echo json_encode([
            'success' => true,
            'advisement' => [
                'recommended' => $recommended,
                'retake' => $retake,
                'blocked' => $blocked,
                'completed' => $completed,
                'conditional' => $conditional,
            ],
            'current_year' => $currentYear,
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ─── FINALIZE EVALUATION SESSION ─────────────────────────────────────────────
if ($action === 'finalize_session') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $major_id   = intval($_POST['major_id'] ?? 0);
    $academic_year = $_POST['academic_year'] ?? '2025-2026';
    $semester = $_POST['semester'] ?? '1st Semester';
    $notes = trim($_POST['notes'] ?? '');

    try {
        // Fetch grades for GWA
        $stmt = $pdo->prepare("
            SELECT sg.grade_rounded, s.units
            FROM student_grades sg
            JOIN subjects s ON sg.subject_id = s.id
            WHERE sg.student_id = ? AND sg.grade_rounded IS NOT NULL
        ");
        $stmt->execute([$student_id]);
        $gwaData = compute_gwa($stmt->fetchAll(PDO::FETCH_ASSOC));

        $stmt2 = $pdo->prepare("
            INSERT INTO evaluation_sessions
                (instructor_id, student_id, major_id, academic_year, semester, session_status, gwa, total_units_taken, total_units_passed, notes)
            VALUES (?,?,?,?,?,'finalized',?,?,?,?)
            ON DUPLICATE KEY UPDATE
                session_status='finalized', gwa=VALUES(gwa),
                total_units_taken=VALUES(total_units_taken), total_units_passed=VALUES(total_units_passed),
                notes=VALUES(notes), updated_at=NOW()
        ");
        $stmt2->execute([
            $instructor_id, $student_id, $major_id, $academic_year, $semester,
            $gwaData['gwa'], $gwaData['total_units'], $gwaData['units_passed'], $notes
        ]);
        echo json_encode(['success' => true, 'message' => 'Evaluation finalized!', 'gwa' => $gwaData]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);

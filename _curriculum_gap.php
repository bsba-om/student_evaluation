<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__.'/data/config.php';
require_once __DIR__.'/data/graduation_support.php';

$STUDENT_ID = 1;   // John Doe
$MAJOR_ID   = 1;   // Operational Management

echo "=== CURRICULUM GAP DIAGNOSIS for student $STUDENT_ID ===\n\n";

// [1] What major_subjects expects
echo "[1] Required subjects for major_id=$MAJOR_ID (from major_subjects)\n";
$reqStmt = $pdo->prepare("
    SELECT s.id AS sid, s.subject_code, s.subject_name, s.units,
           ms.year_level, ms.semester, ms.is_required
    FROM major_subjects ms
    JOIN subjects s ON s.id = ms.subject_id
    WHERE ms.major_id = ?
    ORDER BY ms.sort_order
");
$reqStmt->execute([$MAJOR_ID]);
$allReq = $reqStmt->fetchAll(PDO::FETCH_ASSOC);
$allRequired = array_filter($allReq, fn($r) => (int)$r['is_required'] === 1);
echo "  Total rows in major_subjects: " . count($allReq) . "\n";
echo "  Required (is_required=1):    " . count($allRequired) . "\n";
echo "  Non-required (is_required=0): " . (count($allReq) - count($allRequired)) . "\n\n";

// [2] What student_grades has
echo "[2] Grades entered for student $STUDENT_ID\n";
$gradeStmt = $pdo->prepare("
    SELECT sg.subject_id, sg.grade_rounded, sg.status, sg.semester, sg.year_level,
           s.subject_code, s.subject_name, s.units
    FROM student_grades sg
    JOIN subjects s ON s.id = sg.subject_id
    WHERE sg.student_id = ?
    ORDER BY sg.subject_id
");
$gradeStmt->execute([$STUDENT_ID]);
$grades = $gradeStmt->fetchAll(PDO::FETCH_ASSOC);
echo "  Total grade rows: " . count($grades) . "\n\n";

// [3] Cross-reference: which required subjects have NO grade?
echo "[3] CROSS-REFERENCE — required subjects with NO grade row\n";
$gradeMap = [];
foreach ($grades as $g) {
    $gradeMap[(int)$g['subject_id']] = $g;
}

$missing = $pendingInSem = [];
foreach ($allRequired as $sub) {
    $sid = (int)$sub['sid'];
    if (!isset($gradeMap[$sid])) {
        $missing[] = $sub;
        echo "  [MISSING] id=$sid code={$sub['subject_code']} name={$sub['subject_name']}"
           . " ({$sub['units']}u) {$sub['year_level']}/{$sub['semester']})\n";
    }
}

// [4] Any grades marked failed/conditional (>3.0)?
echo "\n[4] Grades > 3.0 (failed/conditional) blocking graduation\n";
$blocked = [];
foreach ($grades as $g) {
    $gr = (float)($g['grade_rounded'] ?? 0);
    if ($gr > 3.0) {
        $blocked[] = $g;
        echo "  [BLOCKED] id={$g['subject_id']} code={$g['subject_code']} grade=$gr status={$g['status']}\n";
    }
}

// [5] student_curriculum_completion() result
echo "\n[5] student_curriculum_completion(st=$STUDENT_ID, maj=$MAJOR_ID)\n";
$cur = student_curriculum_completion($pdo, $STUDENT_ID, $MAJOR_ID);
echo "  total_required: {$cur['total_required']}\n";
echo "  passed:         {$cur['passed']}\n";
echo "  pending:        {$cur['pending']}\n";
echo "  blocked:        {$cur['blocked']}\n";
echo "  complete:       " . ($cur['complete'] ? 'true' : 'false') . "\n";
echo "  gwa:            " . ($cur['gwa'] ?? 'null') . "\n";

// [6] Root cause summary
echo "\n[6] ROOT CAUSE\n";
if ($missing) {
    echo "  → " . count($missing) . " required subject(s) have NO grade row in student_grades\n";
    echo "  → student_curriculum_completion() returns complete=false (pending > 0)\n";
    echo "  → graduation_complete_workflow() returns {success:false, message:'Curriculum is not fully complete.'}\n";
    echo "  → JS .then(data) fires hideModal + toast('Graduation failed: Curriculum is not fully complete.')\n";
    echo "  → User sees modal close + brief red toast → button restores → looks like 'nothing happened'\n";
} elseif ($blocked) {
    echo "  → " . count($blocked) . " subject(s) are failed/conditional (grade > 3.0)\n";
} else {
    echo "  → Curriculum looks complete (no obvious gap from this script)\n";
    echo "  → Gap might be in a DIFFERENT table view (check major_subjects vs grade_map in showResultModal context)\n";
}

echo "\n=== END DIAGNOSIS ===\n";

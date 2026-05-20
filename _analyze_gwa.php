<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__.'/data/config.php';
require_once __DIR__.'/data/graduation_support.php';

// Will simulate evaluation_process.php JSON output
// endpoint: get_student_evaluation
// same flow as evaluation.php line 926

$student_id = 1;
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Simulating get_student_evaluation for student 1 (currentDB state) ===\n\n";

// Students table
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) { echo "Student not found\n"; exit(1); }
echo "student: id={$student['id']} name={$student['first_name']} {$student['last_name']} status={$student['status']} yl={$student['year_level']}\n";

$major_id = (int)($student['major_id'] ?? 0);
echo "major_id: $major_id\n";

// Curriculum
$curriculum = student_curriculum_completion($pdo, $student_id, $major_id);
echo "\ncurriculum (this IS what lastCurriculumStats receives after page load):\n";
echo "  total_required: {$curriculum['total_required']}\n";
echo "  passed:         {$curriculum['passed']}\n";
echo "  pending:        {$curriculum['pending']}\n";
echo "  blocked:        {$curriculum['blocked']}\n";
echo "  complete:       " . ($curriculum['complete'] ? 'true' : 'false') . "\n";
echo "  gwa:            " . ($curriculum['gwa'] ?? 'null') . "\n";
echo "  units_total:    {$curriculum['units_total']}\n";
echo "  units_passed:   {$curriculum['units_passed']}\n";

echo "\n=== So lastCurriculumStats.gwa = " . ($curriculum['gwa'] ?? 'null') . " ===\n\n";

// semGWA = semester-specific (4th Year / 2nd Sem subjects only)
echo "=== semGWA for 4th Year / 2nd Sem ===\n";
$st4 = $pdo->prepare("
    SELECT s.id, s.subject_code, s.subject_name, s.units,
           sg.grade_rounded, sg.status,
           ms.year_level, ms.semester
    FROM major_subjects ms
    JOIN subjects s ON s.id = ms.subject_id
    LEFT JOIN student_grades sg ON sg.subject_id = s.id AND sg.student_id = ?
    WHERE ms.major_id = ? AND ms.year_level = '4th Year' AND ms.semester LIKE '%2nd%'
    ORDER BY ms.sort_order
");
$st4->execute([$student_id, $major_id]);
$sem2subs = $st4->fetchAll(PDO::FETCH_ASSOC);
$points = 0.0; $units = 0.0;
foreach ($sem2subs as $s) {
    $u = (float)($s['units'] ?? 0);
    $g = ($s['grade_rounded'] !== null) ? (float)$s['grade_rounded'] : null;
    if ($g !== null && $g <= 3) { $points += $g * $u; $units += $u; }
}
echo "  4th/2nd sem graded subjects: " . count($sem2subs) . "\n";
echo "  total points/units = $points / $units\n";
echo "  semGWA = " . ($units > 0 ? round($points/$units, 4) : '—') . "\n";

echo "\n=== QUESTION ANSWER ===\n";
if ($curriculum['gwa'] !== null) {
    echo "lastCurriculumStats.gwa = Full-program GWA (ALL 63 required subjects)\n";
    echo "  currently: " . round($curriculum['gwa'], 4) . "\n";
} else {
    echo "lastCurriculumStats.gwa = null (curriculum incomplete, some subjects pending/blocked)\n";
    echo "  showResultModal will show curveGWA from the graded subjects of the current period\n";
}
echo "semGWA (4th Year/2nd Sem) = weighted avg of just 4th/2nd subjects\n";
echo "Final GWA on the card = cur.gwa (full program) if not null, else semGWA\n";

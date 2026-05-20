<?php
require_once __DIR__.'/data/config.php';
require_once __DIR__.'/data/graduation_support.php';

$sid = 9;  // wadawdaw, opop — 4th Year/2nd Sem, 61 pass, 2 pending
echo "=== SUBJECT GAP for student 9 (wadawdaw, opop) ===\n\n";

$cur = student_curriculum_completion($pdo, $sid, 1);
echo "Curriculum: total={$cur['total_required']} pass={$cur['passed']} pend={$cur['pending']} block={$cur['blocked']} gwa=".($cur['gwa']??'null')." complete=".($cur['complete']?'true':'false')."\n\n";

$gradeMap = [];
foreach($pdo->query("SELECT subject_id,grade_rounded,status FROM student_grades WHERE student_id=$sid")->fetchAll(PDO::FETCH_ASSOC) as $g) {
    $gradeMap[(int)$g['subject_id']] = $g;
}
echo "student_grades rows for student 9: " . count($gradeMap) . "\n";

$st = $pdo->prepare("
    SELECT s.id, s.subject_code, s.subject_name, s.units, ms.year_level, ms.semester
    FROM major_subjects ms
    JOIN subjects s ON s.id = ms.subject_id
    WHERE ms.major_id=1 AND ms.is_required=1
    ORDER BY ms.sort_order
");
$st->execute([1]);
$allReq = $st->fetchAll(PDO::FETCH_ASSOC);

echo "\nMissing subjects (no grade in student_grades):\n";
foreach($allReq as $sub) {
    $sid2 = (int)$sub['id'];
    if (!isset($gradeMap[$sid2])) {
        echo "  id=$sid2 code={$sub['subject_code']} name={$sub['subject_name']}"
           . " ({$sub['units']}u) {$sub['year_level']}/{$sub['semester']})\n";
    }
}

echo "\nOut of " . count($allReq) . " required subjects, " . count($gradeMap) . " have grades.\n";
echo "The 2 missing ones above are the only gap.\n";

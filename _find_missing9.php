<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__.'/data/config.php';

// Find student 9's pending subjects
$sid = 9;
$gradeMap = [];
foreach($pdo->query("SELECT subject_id,grade_rounded,status FROM student_grades WHERE student_id=$sid")->fetchAll(PDO::FETCH_ASSOC) as $g) {
    $gradeMap[(int)$g['subject_id']] = $g;
}

$st = $pdo->prepare("
    SELECT s.id, s.subject_code, s.subject_name, s.units, ms.year_level, ms.semester
    FROM major_subjects ms
    JOIN subjects s ON s.id=ms.subject_id
    WHERE ms.major_id=1 AND ms.is_required=1
    ORDER BY ms.sort_order
");
$st->execute([1]);
$missing = [];
foreach($st->fetchAll(PDO::FETCH_ASSOC) as $sub) {
    if (!isset($gradeMap[(int)$sub['id']])) {
        $missing[] = $sub;
    }
}

echo "Student 9 missing subjects:\n";
foreach($missing as $m) {
    echo "  id={$m['id']} code={$m['subject_code']} name={$m['subject_name']}"
       . " ({$m['units']}u) {$m['year_level']}/{$m['semester']})\n";
}

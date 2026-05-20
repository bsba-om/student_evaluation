<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__.'/data/config.php';
require_once __DIR__.'/data/graduation_support.php';

$_SESSION['user_role'] = 'instructor'; $_SESSION['user_id'] = 8;

echo "=== 1. DIAGNOSE: What exact message does confirm_graduation handler return RIGHT NOW ===\n\n";

// Restore student 1 to regular first (clears graduation lock + grading records)
$pdo->exec("UPDATE students SET status='regular',year_level='4th Year' WHERE id=1");
$pdo->exec("DELETE FROM graduation_records WHERE student_id=1");
$pdo->exec("DELETE FROM student_grades WHERE student_id=1 AND subject_id IN (43,45,51,92)");

echo "[DB] student 1 reset to regular, graduation_records cleared\n";

// Now simulate EXACTLY what the browser POSTs
$student_id    = 1;
$major_id_post = 1;
$academic_year = '2025-2026';
$year_level    = '4th Year';
$semester      = '2nd Semester';

echo "\n[confirm_graduation handler] mentor_id=8  student_id=$student_id\n";

function role_access($req) {
    if (!isset($_SESSION['user_role'])) return ['allowed'=>false];
    return $_SESSION['user_role']===$req ? ['allowed'=>true] : ['allowed'=>false,'msg'=>'wrong role:'.$_SESSION['user_role']];
}
$ra = role_access('instructor');
echo "  access allowed: " . ($ra['allowed']?'YES':'NO') . "\n";
if (!$ra['allowed']) { echo "  reason: " . ($ra['msg'] ?? '') . "\n"; exit; }

echo "  year_level contains '4th': " . (stripos($year_level,'4th')!==false?'YES':'NO') . "\n";
echo "  semester   contains '2nd': " . (stripos($semester,'2nd')!==false?'YES':'NO') . "\n";

$wf = graduation_complete_workflow($pdo, 8, $student_id, $major_id_post, $academic_year, $year_level, $semester);
echo "\n[response] " . json_encode($wf, JSON_PRETTY_PRINT) . "\n";

echo "\n[what JS receives]\n";
echo "  success: " . ($wf['success']?'true':'false') . "\n";
echo "  message: " . ($wf['message'] ?? '') . "\n";
echo "  pdf_url: " . ($wf['pdf_url'] ?? '') . "\n";

echo "\n[cleanup] reset student 1 back\n";
$pdo->exec("UPDATE students SET status='regular',year_level='4th Year' WHERE id=1");
$pdo->exec("DELETE FROM graduation_records WHERE student_id=1");
$pdo->exec("DELETE FROM student_grades WHERE student_id=1 AND subject_id IN (43,45,51,92)");
echo "cleanup done.\n";

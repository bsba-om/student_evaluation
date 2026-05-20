<?php
require_once __DIR__.'/data/config.php';
// Reset student 1: remove graduation lock so user can test the full flow
$pdo->exec("UPDATE students SET status='regular', year_level='4th Year', updated_at=NOW() WHERE id=1");
$pdo->exec("DELETE FROM graduation_records WHERE student_id=1");
$pdo->exec("DELETE FROM student_grades WHERE student_id=1 AND subject_id IN(43,45,51,92)");
// Verify clean state
$cnt = (int)$pdo->query("SELECT COUNT(*) FROM graduation_records WHERE student_id=1")->fetchColumn();
$grd = (int)$pdo->query("SELECT COUNT(*) FROM student_grades WHERE student_id=1 AND subject_id IN(43,45,51,92)")->fetchColumn();
echo "graduation_records rows: $cnt (should be 0)\n";
echo "pending grade rows:      $grd (should be 0)\n";
echo "status: " . $pdo->query("SELECT status FROM students WHERE id=1")->fetchColumn() . " (should be regular)\n";

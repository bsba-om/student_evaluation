<?php
require_once __DIR__.'/data/config.php';
require_once __DIR__.'/data/graduation_support.php';
$_SESSION['user_role'] = 'instructor'; $_SESSION['user_id'] = 8;

echo "=== CHECK: student_curriculum_completion returns gwa=2.00 ===\n";
$res = student_curriculum_completion($pdo, 1, 1);
echo "  complete=" . ($res['complete']?'true':'false')
     . "  total={$res['total_required']}  pass={$res['passed']}  pend={$res['pending']}  block={$res['blocked']}"
     . "  gwa=" . number_format($res['gwa'], 4) . "\n\n";

echo "=== GRADUATION WORKFLOW ===\n";
$wf = graduation_complete_workflow($pdo, 8, 1, 1, '2025-2026', '4th Year', '2nd Semester');
echo json_encode($wf, JSON_PRETTY_PRINT) . "\n\n";
echo "=== WORKFLOW PATH ===\n";
if (!empty($wf['success'])) {
    echo "Path   → .then(data.success=true) → FOLDERS → PDF → DB → REDIRECT\n";
} else {
    echo "Path   → .then(data.success=false) → TOAST ERROR → BUTTON RESTORE\n";
    echo "Code   → " . $wf['message'] . "\n";
}
echo "\n=== JS CHAIN ===\n";
echo "  If error:  clearInterval(fakeTick) → hideModal() → toast() → btn.restore()\n";
echo "  user sees: modal closes, brief red toast, button back to 'Confirm…'\n";
echo "  looks like: 'nothing happened' (only if user missed the toast)\n\n";
echo "=== FAQ ===\n";
echo "  Q: Will I get redirected to setup_graduate.php automatically?\n";
echo "  A: ONLY if success=true. If the workflow fails the JS redirect NEVER fires.\n";
echo "  Q: The toast shows 'This student is already marked as graduated'\n";
echo "  A: That means student 1 is status=graduated from a previous test.\n";
echo "     Run the SQL in insert_missing_grades.sql in phpMyAdmin to restore student 1\n";
echo "     to regular state, then click the button again in the browser.\n";

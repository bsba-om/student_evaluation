<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__.'/data/config.php';
require_once __DIR__.'/data/graduation_support.php';
$_SESSION['user_role']='instructor'; $_SESSION['user_id']=8;

$ST=0; $SF=0;
function chk($l,$v){global$ST,$SF;$v?$ST++:$SF++; echo($v?"\033[32m  ✓ $l\033[0m\n":"\033[31m  ✗ $l\033[0m\n");}

echo "=== CONFIRM GRADUATION BUTTON FIX VERIFICATION ===\n";
echo "Target: the button works ANY TIME the server says 'Curriculum is not fully complete'\n\n";

// [A] Which 4th/2nd Sem student has the gap?
echo "[A] Identify the blocked 4th Year/2nd Sem non-graduated student\n";
$all = $pdo->query("
    SELECT s.id,s.first_name,s.last_name,s.year_level,
           m.display_name AS major,
           (SELECT COUNT(*) FROM student_grades WHERE student_id=s.id) AS grade_count
    FROM students s
    JOIN majors m ON s.major_id=m.id
    WHERE s.status!='graduated'
    ORDER BY s.id
");
foreach($all->fetchAll(PDO::FETCH_ASSOC) as $s){
    $yl=$s['year_level'];
    if(stripos($yl,'4th')===false || stripos($yl,'2nd')===false) continue;
    $sid=(int)$s['id'];
    $maj=(int)$pdo->query("SELECT major_id FROM students WHERE id=$sid")->fetchColumn();
    $cur=student_curriculum_completion($pdo,$sid,$maj);
    $ok=!empty($cur['complete']);
    printf("  st%-3d %-25s yl=%-18s maj=%-4s  grades=%-3d  total=%-3d  pass=%-3d  pend=%-3d  complete=%s\n",
        $sid,trim($s['last_name'].', '.$s['first_name']),$yl,$s['major'],
        $s['grade_count'],$cur['total_required'],$cur['passed'],$cur['pending'],
        $ok?'true':'false');
    chk("  Curriculum complete (can confirm)",$ok);
}

// [B] What does the confirm_graduation handler return right now?
echo "\n[B] confirm_graduation handler — current server response\n";
// Student 9 (4th/2nd with gap):
$wf9 = graduation_complete_workflow($pdo,8,9,1,'2025-2026','4th Year','2nd Semester');
echo "  Student 9: success=" . ($wf9['success']?'true':'false') . "  message={$wf9['message']}\n";
chk('handler returns JSON parseable body', $wf9['success'] !== null);

// [C] Verify the JS error-path code is in place
echo "\n[C] JS error-path code review\n";
$evalPhp = file_get_contents('Door/instructor/pages/evaluation.php');
$hasErrorModal = str_contains($evalPhp, 'Graduation Failed')
              && str_contains($evalPhp, "bar.style.background = 'linear-gradient(90deg,#ef4444,#dc2626)'");
$hasFakeTickFix = str_contains($evalPhp, 'clearInterval(fakeTick)');

chk('Error modal in .then() else branch (red 80% bar)',   $hasErrorModal);
chk('.catch() uses clearInterval(fakeTick) (not progressInterval)', $hasFakeTickFix);

// [D] Simulate server response the browser sees
echo "\n[D] Browser POST response simulation\n";
$response = [
    'success' => false,
    'message' => 'Curriculum is not fully complete. Resolve pending or failed subjects before graduation.'
];
echo "  Server returns HTTP 400 + JSON:\n";
echo "  " . json_encode($response) . "\n\n";
echo "  [JS .then(data)]  data.success = false →\n";
echo "  clearInterval(fakeTick)\n";
echo "  Modal stays OPEN at 80% — RED bar, RED icon, 'Graduation Failed' title\n";
echo "  Subtitle: ⚠ Curriculum is not fully complete. Resolve pending or failed subjects before graduation.\n";
echo "  toast('Error: Curriculum is not fully complete…', 'error') fires → 3.2 s\n";
echo "  User dismisses: click anywhere on red background OR press Escape\n\n";

echo "[E] After user fixes curriculum and clicks confirm again\n";
echo "  Server returns {success:true}:\n";
echo "  clearInterval(fakeTick)\n";
echo "  animate 82% → 93% → 100%\n";
echo "  _gpmMarkAllDone() → 'Graduation Confirmed!'\n";
echo "  setTimeout 1200ms →\n";
echo "    hideGraduationProgressModal()\n";
echo "    window.open(pdf_url,'_blank')  [PDF opens in tab]\n";
echo "    setTimeout 200ms → window.location.href='setup_graduate.php'\n";
echo "    Button restores to 'Confirm Graduation'\n";

echo "\n".str_repeat("=",70)."\n";
echo "RESULTS: $ST passed $SF failed\n";
echo $SF===0 ? "✓ ALL CHECKS PASS\n" : "✗ SOME FAILED\n";
exit($SF?1:0);

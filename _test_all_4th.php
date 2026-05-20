<?php
require_once __DIR__.'/data/config.php';
require_once __DIR__.'/data/graduation_support.php';
$_SESSION['user_role']='instructor'; $_SESSION['user_id']=8;

// Test confirm_graduation for EVERY 4th-Year-2nd-Sem non-graduated student
$all = $pdo->query("SELECT s.id,s.first_name,s.last_name,s.year_level,m.display_name AS major FROM students s JOIN majors m ON s.major_id=m.id WHERE s.status!='graduated' ORDER BY s.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($all as $s) {
    $yl = $s['year_level'];
    $is4th  = stripos($yl, '4th') !== false;
    $is2nd  = stripos($yl, '2nd') !== false;
    if (!$is4th || !$is2nd) continue; // only 4th Year / 2nd Sem

    $sid  = (int)$s['id'];
    $maj  = (int)$pdo->query("SELECT major_id FROM students WHERE id=$sid")->fetchColumn();
    $wf   = graduation_complete_workflow($pdo, 8, $sid, $maj, '2025-2026', '4th Year', '2nd Semester');
    $ok   = !empty($wf['success']);

    printf("  st%-3d %-25s yl=%-18s maj=%-4s  success=%-5s  message=%s\n",
        $sid, trim($s['last_name'].', '.$s['first_name']), $yl, $s['major'],
        $ok?'true':'false', $wf['message']);
    if (!$ok) {
        echo "    → CONFIRMATION WILL FAIL FOR THIS STUDENT\n";
        echo "    → User sees: modal closes + red toast OR red error modal (after fix)\n";
        echo "    → Click anywhere / Escape to dismiss red modal\n\n";
    }
}

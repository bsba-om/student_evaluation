<?php
require_once __DIR__.'/data/config.php';
require_once __DIR__.'/data/graduation_support.php';

// Find ALL students who are NOT graduated but might be in 4th Year/2nd Sem
$candidates = $pdo->query("
    SELECT s.id, s.first_name, s.last_name, s.status, s.year_level, m.display_name AS major
    FROM students s
    LEFT JOIN majors m ON s.major_id = m.id
    WHERE s.status != 'graduated'
    ORDER BY s.last_name
");

echo "=== ALL NON-GRADUATED STUDENTS CHECKED for confirm_graduation eligibility ===\n\n";
foreach ($candidates->fetchAll(PDO::FETCH_ASSOC) as $s) {
    $sid  = (int)$s['id'];
    $majId = (int)($pdo->query("SELECT major_id FROM students WHERE id=$sid")->fetchColumn());
    $cur = student_curriculum_completion($pdo, $sid, $majId);
    $status = $s['status'];
    $yl = $s['year_level'];
    $has4th = stripos($yl, '4th') !== false;
    $has2nd = stripos($yl, '2nd') !== false || stripos($yl, '2') !== false;
    $eligible = $has4th && $has2nd && !empty($cur['complete']);
    echo sprintf(
        "  id=%-3d %-22s status=%-10s yl=%-18s maj=%-4s  " .
        "total=%d pass=%d pend=%d block=%d gwa=%s  → %s\n",
        $sid,
        trim($s['last_name'] . ', ' . $s['first_name']),
        $status,
        $yl,
        $s['major'],
        $cur['total_required'],
        $cur['passed'],
        $cur['pending'],
        $cur['blocked'],
        $cur['gwa'] ?? 'null',
        $eligible ? '✓ confirm_graduation WILL succeed' : ($cur['complete'] ? '✗ not 4th/2nd' : '✗ curriculum incomplete')
    );
}

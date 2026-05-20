<?php
// Quick logic unit test
$tests = [
    ['2025-2026', 'even',  '2nd Semester', 'Open'],
    ['2026-2027', 'odd',   '1st Semester', 'Open'],
    ['2027-2028', 'even',  '2nd Semester', 'Open'],
    ['2040-2041', 'odd',   '1st Semester', 'Open'],
    ['2029-2030', 'even',  '2nd Semester', 'Open'],
    ['2030-2031', 'odd',   '1st Semester', 'Open'],
];

$OK = true;
foreach ($tests as [$ay, $label, $expSem, $expEn]) {
    $parts   = explode('-', $ay);
    $endYear = (int)($parts[1]) ?: ((int)$parts[0] + 1);
    $sem     = ($endYear % 2 === 0) ? '2nd Semester' : '1st Semester';
    $enroll  = 'Open';
    $ok      = ($sem === $expSem) && ($enroll === $expEn);
    $mark    = $ok ? 'OK' : 'FAIL';
    echo "$mark  AY=$ay  endYear=$endYear  ($label)  sem=$sem  enroll=$enroll\n";
    if (!$ok) $OK = false;
}
echo $OK ? "\nAll tests passed\n" : "\nSome FAILED\n";

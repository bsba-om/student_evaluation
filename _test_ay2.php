<?php
// Logic unit test - verify the semester computation for all 16 year options
$years = [
    '2025-2026','2026-2027','2027-2028','2028-2029','2029-2030','2030-2031',
    '2031-2032','2032-2033','2033-2034','2034-2035','2035-2036','2036-2037',
    '2037-2038','2038-2039','2039-2040','2040-2041'
];

echo "AY         | endYear | computedSem\n";
echo "-----------|---------|-------------\n";
foreach ($years as $ay) {
    $parts = explode('-', $ay);
    $end   = (int)($parts[1]) ?: ((int)$parts[0] + 1);
    $sem   = ($end % 2 === 0) ? '2nd Semester' : '1st Semester';
    printf("%-10s | %-7d | %s\n", $ay, $end, $sem);
}

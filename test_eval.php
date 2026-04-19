<?php
require_once 'data/config.php';

$major_id = 1;

try {
    $stmt2 = $pdo->prepare("
        SELECT s.*, ms.year_level, ms.semester, ms.is_prerequisite, ms.is_required,
               ms.prerequisite, ms.sort_order,
               COALESCE(ms.prerequisite, s.prerequisite) as display_prerequisite
        FROM major_subjects ms
        JOIN subjects s ON ms.subject_id = s.id
        WHERE ms.major_id = ?
        ORDER BY ms.year_level, ms.semester, ms.sort_order, s.subject_name
    ");
    $stmt2->execute([$major_id]);
    $prospectus = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Success! Found " . count($prospectus) . " subjects<br>";
    foreach ($prospectus as $subj) {
        echo $subj['subject_code'] . " - " . $subj['subject_name'] . " | Pre-req: " . $subj['display_prerequisite'] . " | Sort: " . $subj['sort_order'] . "<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
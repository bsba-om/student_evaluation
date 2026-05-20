<?php
require 'C:/xampp/htdocs/student_evaluation/Door/data/config.php';
if ($pdo) {
    echo "PDO connection successful\n";
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll();
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "- " . implode(' ', $table) . "\n";
    }
} else {
    echo "PDO connection failed\n";
}
?>
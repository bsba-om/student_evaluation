<?php
require 'C:/xampp/htdocs/student_evaluation/Door/data/config.php';
if ($pdo) {
    echo "PDO connection successful\n";
    $stmt = $pdo->query('DESCRIBE tasks');
    $columns = $stmt->fetchAll();
    echo "Tasks table columns:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
} else {
    echo "PDO connection failed\n";
}
?>
<?php
// Database Configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'checkmate';

// ========================================
// MySQLi Connection (for legacy code)
// ========================================
mysqli_report(MYSQLI_REPORT_OFF);
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
} catch (mysqli_sql_exception $e) {
    $conn = null;
}

// Check mysqli connection
if ($conn === null || $conn->connect_error) {
    $conn = null;
} else {
    $conn->set_charset("utf8mb4");
}

// ========================================
// PDO Connection (recommended)
// ========================================
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pdo = null;
}
?>

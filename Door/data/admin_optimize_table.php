<?php
session_start();
require_once '../data/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$table = $_POST['table'] ?? '';

if (empty($table)) {
    echo json_encode(['success' => false, 'message' => 'No table specified']);
    exit;
}

// Validate table name (security)
$allowed_tables = ['admins', 'instructors', 'students', 'courses', 'majors', 'evaluations', 'reports', 'pending_instructors', 'admin_promotions', 'program_heads'];
if (!in_array($table, $allowed_tables)) {
    echo json_encode(['success' => false, 'message' => 'Invalid table name']);
    exit;
}

try {
    // Start transaction for safety
    $pdo->beginTransaction();
    
    // Optimize table (for MySQL)
    $stmt = $pdo->prepare("OPTIMIZE TABLE `$table`");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Table '{$table}' optimized successfully",
        'details' => $result
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Optimization failed: ' . $e->getMessage()
    ]);
}

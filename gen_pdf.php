<?php
chdir('C:/xampp/htdocs/student_evaluation');
$_SESSION = ['user_id' => 8, 'user_role' => 'instructor'];
require_once __DIR__ . '/data/session_security.php';
require_once __DIR__ . '/data/config.php';
require_once __DIR__ . '/data/graduation_support.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Clean up old PDF first
$old = $pdo->query("SELECT pdf_path FROM graduation_records WHERE student_id = 10")->fetchColumn();
if ($old && is_file($old)) { @unlink($old); echo "Removed old PDF\n"; }

$rv = graduation_regenerate_pdf_only($pdo, 8, 10, '2025-2026', '4th Year - 2nd Semester', '2nd Semester');
echo json_encode($rv, JSON_PRETTY_PRINT) . "\n";
if ($rv['success'] ?? false && $rv['pdf_path'] && is_file($rv['pdf_path'])) {
    echo "Generated: " . $rv['pdf_path'] . " (" . filesize($rv['pdf_path']) . " bytes)\n";
}

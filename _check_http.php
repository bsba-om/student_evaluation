<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__.'/data/config.php';
require_once __DIR__.'/data/graduation_support.php';
$_SESSION['user_role']='instructor'; $_SESSION['user_id']=8;

$wf = graduation_complete_workflow($pdo,8,1,1,'2025-2026','4th Year','2nd Semester');
$ok  = !empty($wf['success']);
$msg = $wf['message'] ?? '';
http_response_code($ok ? 200 : 400);
header('Content-Type: application/json');
echo json_encode($wf, JSON_PRETTY_PRINT);

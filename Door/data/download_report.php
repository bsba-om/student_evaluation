<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Access denied');
}

$report_id = $_POST['report_id'] ?? null;

if (!$report_id) {
    http_response_code(400);
    exit('Report ID required');
}

require_once 'config.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        http_response_code(404);
        exit('Report not found');
    }
    
    // Check if file exists
    $file_path = '../../../reports/' . $report['report_name'] . '_' . $report_id . '.' . $report['report_type'];
    if (!file_exists($file_path)) {
        // Try alternative naming
        $files = glob('../../../reports/*' . $report_id . '.*');
        if (count($files) > 0) {
            $file_path = $files[0];
        } else {
            http_response_code(404);
            exit('Report file not found');
        }
    }
    
    // Increment download count
    $stmt = $pdo->prepare("UPDATE reports SET download_count = download_count + 1 WHERE id = ?");
    $stmt->execute([$report_id]);
    
    // Serve file
    $filename = $report['report_name'] . '.' . $report['report_type'];
    header('Content-Type: ' . getMimeType($report['report_type']));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
    
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database error');
}

function getMimeType($ext) {
    switch ($ext) {
        case 'pdf': return 'application/pdf';
        case 'excel':
        case 'xls': return 'application/vnd.ms-excel';
        case 'csv': return 'text/csv';
        default: return 'application/octet-stream';
    }
}
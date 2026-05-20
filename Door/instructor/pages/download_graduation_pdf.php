<?php
/**
 * download_graduation_pdf.php
 * ─────────────────────────────────────────────────────────────────────────
 * Serves the saved prospectus PDF for a graduated student.
 * Called via: data/download_graduation_pdf.php?student_id=123
 *
 * Place at: <project-root>/data/download_graduation_pdf.php
 * ─────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/session_security.php';
require_once __DIR__ . '/config.php';   // provides $pdo

$role_access = check_role_access('instructor');
if (!$role_access['allowed']) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

$studentId = (int)($_GET['student_id'] ?? 0);
$isInline  = isset($_GET['inline']);
if (!$studentId) {
    http_response_code(400);
    echo 'student_id is required';
    exit;
}

/* ── Look up the saved PDF path in the graduates table ─────────────────── */
try {
    // Try graduation_records table first
    $stmt = $pdo->prepare("
        SELECT gr.pdf_path, s.first_name, s.last_name, gr.academic_year
          FROM graduation_records gr
          JOIN students s ON s.id = gr.student_id
         WHERE gr.student_id = ?
         ORDER BY gr.created_at DESC
         LIMIT 1
    ");
    $stmt->execute([$studentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fallback to graduates table if needed
    if (!$row) {
        $stmt = $pdo->prepare("
            SELECT g.pdf_path, s.first_name, s.last_name, g.academic_year
              FROM graduates g
              JOIN students s ON s.id = g.student_id
             WHERE g.student_id = ?
             ORDER BY g.created_at DESC
             LIMIT 1
        ");
        $stmt->execute([$studentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Database error: ' . htmlspecialchars($e->getMessage());
    exit;
}

if (!$row || !$row['pdf_path']) {
    http_response_code(404);
    echo 'No PDF found for this student. Please generate it first from the Graduate Management page.';
    exit;
}

$filePath = $row['pdf_path'];

/* ── Verify the file exists on disk ────────────────────────────────────── */
if (!file_exists($filePath)) {
    http_response_code(404);
    echo 'PDF file not found on disk: ' . htmlspecialchars($filePath) . '. It may have been moved or deleted. Please regenerate it from the Graduate Management page.';
    exit;
}

/* ── Build a clean download filename ───────────────────────────────────── */
$lastName  = preg_replace('/[^a-z0-9]/', '', strtolower($row['last_name']  ?? 'student'));
$firstName = preg_replace('/[^a-z0-9]/', '', strtolower($row['first_name'] ?? 'name'));
$batch     = preg_replace('/[^0-9\-]/', '', $row['academic_year'] ?? '');
$ext       = pathinfo($filePath, PATHINFO_EXTENSION);   // pdf or html (fallback)
$downloadName = "prospectus_{$lastName}_{$firstName}_batch{$batch}.{$ext}";

/* ── Stream the file ───────────────────────────────────────────────────── */
$mimeType = $ext === 'html' ? 'text/html' : 'application/pdf';

header('Content-Type: ' . $mimeType);
if ($isInline) {
    header('Content-Disposition: inline; filename="' . $downloadName . '"');
} else {
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
}
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($filePath);
exit;

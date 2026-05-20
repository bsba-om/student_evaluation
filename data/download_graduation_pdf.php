<?php
/**
 * download_graduation_pdf.php  —  student_evaluation/data/
 * Serves the saved prospectus PDF for a graduated student.
 *
 * INCLUDES: data/session_security.php
 * INCLUDES: data/config.php
 */

require_once __DIR__ . '/session_security.php';
require_once __DIR__ . '/config.php';

function sendHttpJson(int $httpCode, array $payload): void {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($httpCode);
    echo json_encode($payload);
    exit;
}

$role_access = check_role_access('instructor');
if (!$role_access['allowed']) {
    sendHttpJson(403, ['success' => false, 'message' => 'Access denied']);
}

$studentId = (int)($_GET['student_id'] ?? 0);
$filePath  = $_GET['file_path'] ?? '';
$isInline   = isset($_GET['inline']);
if ($studentId === 0 && empty($filePath)) {
    sendHttpJson(400, ['success' => false, 'message' => 'student_id or file_path is required']);
}

/* ── Look up PDF path in graduation_records if student_id provided ──────────────── */
if ($studentId > 0 && empty($filePath)) {
    try {
        $stmt = $pdo->prepare("
            SELECT gr.pdf_path,
                   gr.graduation_date,
                   gr.gwa,
                   gr.total_subjects,
                   s.first_name, s.last_name,
                   s.student_id AS external_id,
                   gr.academic_year
              FROM graduation_records gr
              JOIN students s ON s.id = gr.student_id
             WHERE gr.student_id = ?
             ORDER BY gr.created_at DESC
             LIMIT 1
        ");
        $stmt->execute([$studentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        sendHttpJson(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

    if (!$row || empty($row['pdf_path'])) {
        sendHttpJson(404, [
            'success' => false,
            'message' => 'No prospectus PDF has been generated for this student yet. Click "Evaluation" then "Confirm Graduation" to create it.',
        ]);
    }

    $filePath    = $row['pdf_path'];
    $firstName   = (string)($row['first_name'] ?? 'student');
    $lastName    = (string)($row['last_name']  ?? '');
    $externalId  = (string)($row['external_id'] ?? $studentId);
    $ay          = (string)($row['academic_year'] ?? '');
    $gradDate    = (string)($row['graduation_date'] ?? '');
} elseif (!empty($filePath)) {
    // When file_path is provided directly, get info from database if possible
    $firstName   = 'student';
    $lastName    = '';
    $externalId  = '';
    $ay          = '';
    $gradDate    = '';
    
    // Try to get student info from the file path if it follows our naming convention
    // Format: {first}_{last}_s{studentId}_{majorSlug}_gwa{value}_batch{YYYY-YYYY}.pdf
    $fileName    = pathinfo($filePath, PATHINFO_FILENAME);
    $nameParts   = explode('_', $fileName);
    if (count($nameParts) >= 4) {
        $firstName = $nameParts[0] ?? 'student';
        $lastName  = $nameParts[1] ?? '';
        if (!empty($nameParts[2]) && $nameParts[2][0] === 's') {
            $externalId = substr($nameParts[2], 1);
        }
    }
}

/* ── Check file existence on disk ────────────────────────────────────────── */
if (!is_file($filePath)) {
    sendHttpJson(404, [
        'success' => false,
        'message' => "The prospectus PDF was deleted from disk ($filePath). It will be automatically regenerated when you open the evaluation panel.",
    ]);
}

$fn = pathinfo($filePath, PATHINFO_EXTENSION);  // 'pdf'

/* ── Stream the file ─────────────────────────────────────────────────────── */
// We use `fpassthru` with `fopen` so the Content-Length header is always
// accurate and there is no race condition (file cannot disappear between
// the fopen call and the fpassthru stream).
$fp = @fopen($filePath, 'rb');
if ($fp === false) {
    sendHttpJson(404, [
        'success' => false,
        'message' => "Cannot open the PDF file on disk ($filePath). It may have been moved or deleted.",
    ]);
}
$fsize = fstat($fp)['size'];

header('Content-Type: application/pdf');
header('Content-Disposition: ' . ($isInline ? 'inline' : 'attachment') . "; filename=\"prospectus_${lastName}_${firstName}_${externalId}_batch{$ay}.{$fn}\"");
header('Content-Length: ' . $fsize);
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

fpassthru($fp);
fclose($fp);
exit;

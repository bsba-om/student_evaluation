<?php
/**
 * Stream a locally stored graduation prospectus PDF (instructor access).
 * Accepts either:
 *   ?student_id=N   → DB lookup via graduation_records (original behaviour)
 *   ?file_path=PATH → Direct filesystem path, no DB hit
 */

require_once __DIR__ . '/session_security.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/graduation_support.php';

if (!$pdo) {
    http_response_code(500);
    echo 'Database unavailable';
    exit;
}

$role = check_role_access('instructor');
if (!$role['allowed']) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

$path = null;

// ── Mode 1: direct file_path (filesystem-based, no DB) ────────────────────
if (isset($_GET['file_path'])) {
    $path = (string) $_GET['file_path'];
}

// ── Mode 2: student_id → DB lookup ────────────────────────────────────────
else {
    $student_id = (int) ($_GET['student_id'] ?? 0);

    if ($student_id <= 0) {
        http_response_code(400);
        echo 'Invalid student';
        exit;
    }

    $instructor_id = (int) ($_SESSION['user_id'] ?? 0);

    $stmt = $pdo->prepare('
        SELECT gr.pdf_path
        FROM graduation_records gr
        INNER JOIN mentees me ON me.student_id = gr.student_id AND me.mentor_id = ?
        WHERE gr.student_id = ?
        LIMIT 1
    ');
    $stmt->execute([$instructor_id, $student_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row['pdf_path'])) {
        http_response_code(404);
        echo 'PDF not found';
        exit;
    }

    $path = (string) $row['pdf_path'];

    if (empty($path)) {
        http_response_code(404);
        echo 'PDF path not recorded for this student.';
        exit;
    }
}

// ── Verify file exists on disk ────────────────────────────────────────────
if (!is_file($path)) {
    http_response_code(410);
    echo 'PDF file recorded in graduation_records but missing on disk: ' .
         htmlspecialchars($path) .
         ' Please click the Evaluation button on the Instructor Panel to re-generate and save a fresh copy.';
    exit;
}

$basename = basename($path);
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . str_replace('"', '', $basename) . '"');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
exit;

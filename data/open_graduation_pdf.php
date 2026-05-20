<?php
/**
 * open_graduation_pdf.php
 * ─────────────────────────────────────────────────────────────────────────
 * Opens the graduation PDF file on the Windows system automatically.
 * This uses shell command to open the file in the default PDF viewer.
 * 
 * Called via AJAX from evaluation.php after graduation confirmation
 * ─────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/session_security.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/graduation_support.php';

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$role = check_role_access('instructor');
if (!$role['allowed']) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$student_id = (int) ($_POST['student_id'] ?? 0);
$instructor_id = (int) ($_SESSION['user_id'] ?? 0);

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Student ID required']);
    exit;
}

try {
    // Verify instructor has access to this student
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
        echo json_encode(['success' => false, 'message' => 'PDF not found']);
        exit;
    }

    $filepath = (string) $row['pdf_path'];
    
    // Verify file exists
    if (!is_file($filepath)) {
        echo json_encode(['success' => false, 'message' => 'PDF file does not exist on disk']);
        exit;
    }

    // On Windows systems, use 'start' command to open the file
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Use start command to open file with default application
        $escaped_path = '"' . str_replace('"', '""', $filepath) . '"';
        $command = 'start "" ' . $escaped_path;
        
        // Execute command using popen for non-blocking execution
        $handle = popen($command, 'r');
        if ($handle) {
            pclose($handle);
            echo json_encode([
                'success' => true,
                'message' => 'PDF file is opening...',
                'filepath' => $filepath
            ]);
            exit;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Could not open PDF file, but it has been saved to: ' . $filepath
            ]);
            exit;
        }
    } else {
        // For Linux/Mac systems
        $command = 'xdg-open ' . escapeshellarg($filepath) . ' > /dev/null 2>&1 &';
        exec($command);
        echo json_encode([
            'success' => true,
            'message' => 'PDF file is opening...',
            'filepath' => $filepath
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}

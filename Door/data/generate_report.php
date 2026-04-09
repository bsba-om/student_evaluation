<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$allowed_roles = ['instructor', 'program_head', 'admin'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$report_name = $_POST['report_name'] ?? '';
$report_type = $_POST['report_type'] ?? '';
$date_from = $_POST['date_from'] ?? null;
$date_to = $_POST['date_to'] ?? null;
$instructor_id = $_SESSION['user_id'];

if (empty($report_name) || empty($report_type)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Report name and type are required']);
    exit;
}

require_once 'config.php';

try {
    // Create reports directory if it doesn't exist
    $upload_dir = '../../../reports/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate filename
    $filename = 'report_' . time() . '_' . preg_replace('/[^a-z0-9]/i', '_', $report_name);
    $file_path = $upload_dir . $filename . '.' . $report_type;
    
    // Generate report data based on type
    $data = [];
    
    // Fetch students data
    $stmt = $pdo->query("
        SELECT 
            s.student_id,
            s.first_name,
            s.last_name,
            s.email,
            s.year_level,
            m.display_name as major_name
        FROM students s
        LEFT JOIN majors m ON s.major_id = m.id
        ORDER BY s.last_name, s.first_name
    ");
    $data['students'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch majors distribution
    $stmt = $pdo->query("
        SELECT m.display_name as major_name, COUNT(*) as count
        FROM students s
        LEFT JOIN majors m ON s.major_id = m.id
        GROUP BY m.id, m.display_name
        ORDER BY count DESC
    ");
    $data['majors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch year level distribution
    $stmt = $pdo->query("
        SELECT year_level, COUNT(*) as count
        FROM students
        WHERE year_level IS NOT NULL
        GROUP BY year_level
        ORDER BY year_level
    ");
    $data['year_levels'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate file based on type
    switch ($report_type) {
        case 'csv':
            generateCSV($file_path, $data);
            break;
        case 'excel':
            generateExcel($file_path, $data);
            break;
        case 'pdf':
            generatePDF($file_path, $data, $report_name);
            break;
        default:
            throw new Exception('Unsupported report type');
    }
    
    // Save report metadata to database
    $stmt = $pdo->prepare("
        INSERT INTO reports (report_name, report_description, report_type, icon_class, generated_by, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $description = "Generated on " . date('F j, Y') . " by " . $_SESSION['user_name'];
    $icon_class = $report_type == 'pdf' ? 'fas fa-file-pdf' : ($report_type == 'excel' ? 'fas fa-file-excel' : 'fas fa-file-csv');
    
    $stmt->execute([$report_name, $description, $report_type, $icon_class, 'instructor']);
    $report_id = $pdo->lastInsertId();
    
    // Update file path with report ID
    $new_file_path = $upload_dir . $filename . '_' . $report_id . '.' . $report_type;
    rename($file_path, $new_file_path);
    
    echo json_encode([
        'success' => true,
        'message' => 'Report generated successfully',
        'report_id' => $report_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function generateCSV($file_path, $data) {
    $fp = fopen($file_path, 'w');
    
    // Add BOM for UTF-8
    fwrite($fp, "\xEF\xBB\xBF");
    
    // Students sheet
    fputcsv($fp, ['Student ID', 'Full Name', 'Email', 'Year Level', 'Major']);
    foreach ($data['students'] as $student) {
        fputcsv($fp, [
            $student['student_id'],
            $student['first_name'] . ' ' . $student['last_name'],
            $student['email'],
            $student['year_level'],
            $student['major_name']
        ]);
    }
    
    fclose($fp);
}

function generateExcel($file_path, $data) {
    $fp = fopen($file_path, 'w');
    
    // Students sheet
    fputcsv($fp, ['Student Report - Generated ' . date('F j, Y')]);
    fputcsv($fp, []);
    fputcsv($fp, ['Student ID', 'Full Name', 'Email', 'Year Level', 'Major']);
    foreach ($data['students'] as $student) {
        fputcsv($fp, [
            $student['student_id'],
            $student['first_name'] . ' ' . $student['last_name'],
            $student['email'],
            $student['year_level'],
            $student['major_name']
        ]);
    }
    
    fputcsv($fp, []);
    fputcsv($fp, ['Major Distribution']);
    fputcsv($fp, ['Major', 'Count']);
    foreach ($data['majors'] as $major) {
        fputcsv($fp, [$major['major_name'], $major['count']]);
    }
    
    fputcsv($fp, []);
    fputcsv($fp, ['Year Level Distribution']);
    fputcsv($fp, ['Year Level', 'Count']);
    foreach ($data['year_levels'] as $year) {
        fputcsv($fp, [$year['year_level'], $year['count']]);
    }
    
    fclose($fp);
}

function generatePDF($file_path, $data, $title) {
    // Simple HTML to PDF (would need a library like TCPDF or Dompdf in production)
    // For now, generate an HTML file that can be printed to PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($title) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            h1 { color: #333; border-bottom: 2px solid #d4a843; padding-bottom: 10px; }
            h2 { color: #555; margin-top: 30px; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th { background: #d4a843; color: white; padding: 10px; text-align: left; }
            td { padding: 10px; border-bottom: 1px solid #ddd; }
            .stats { display: flex; gap: 40px; margin: 20px 0; }
            .stat { text-align: center; }
            .stat-value { font-size: 32px; font-weight: bold; color: #d4a843; }
            .stat-label { color: #666; }
            .footer { margin-top: 40px; color: #999; font-size: 12px; text-align: center; }
        </style>
    </head>
    <body>
        <h1>' . htmlspecialchars($title) . '</h1>
        <p>Generated on: ' . date('F j, Y, g:i a') . '</p>
        
        <h2>Student Statistics</h2>
        <div class="stats">
            <div class="stat">
                <div class="stat-value">' . count($data['students']) . '</div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat">
                <div class="stat-value">' . count($data['majors']) . '</div>
                <div class="stat-label">Different Majors</div>
            </div>
            <div class="stat">
                <div class="stat-value">' . count($data['year_levels']) . '</div>
                <div class="stat-label">Year Levels</div>
            </div>
        </div>
        
        <h2>Student List</h2>
        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Year Level</th>
                    <th>Major</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($data['students'] as $student) {
        $html .= '
                <tr>
                    <td>' . htmlspecialchars($student['student_id']) . '</td>
                    <td>' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</td>
                    <td>' . htmlspecialchars($student['email']) . '</td>
                    <td>' . htmlspecialchars($student['year_level']) . '</td>
                    <td>' . htmlspecialchars($student['major_name']) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <h2>Major Distribution</h2>
        <table>
            <thead>
                <tr>
                    <th>Major</th>
                    <th>Number of Students</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($data['majors'] as $major) {
        $html .= '
                <tr>
                    <td>' . htmlspecialchars($major['major_name']) . '</td>
                    <td>' . $major['count'] . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="footer">
            Generated by Faculty Evaluation System | © ' . date('Y') . '
        </div>
    </body>
    </html>';
    
    file_put_contents($file_path, $html);
}
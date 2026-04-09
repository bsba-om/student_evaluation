<?php
require_once 'config.php';
require_once 'session_security.php';

$instructor_id = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : 0;

if (!$instructor_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid instructor ID']);
    exit;
}

check_role_access('instructor');

try {
    $stmt = $pdo->prepare("SELECT m.id, m.student_id, m.first_name, m.last_name, m.email, m.created_at as assigned_date,
                          s.major_id, s.year_level, maj.display_name as major_name
                          FROM mentees m 
                          LEFT JOIN students s ON m.student_id = s.id 
                          LEFT JOIN majors maj ON s.major_id = maj.id 
                          WHERE m.mentor_id = ? 
                          ORDER BY m.last_name, m.first_name");
    $stmt->execute([$instructor_id]);
    $mentees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($mentees)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No mentees found']);
        exit;
    }
    
    $instructor_stmt = $pdo->prepare("SELECT first_name, last_name FROM instructors WHERE id = ?");
    $instructor_stmt->execute([$instructor_id]);
    $instructor = $instructor_stmt->fetch(PDO::FETCH_ASSOC);
    $instructor_name = $instructor ? $instructor['first_name'] . ' ' . $instructor['last_name'] : 'Instructor';
    
    $csv_data = [];
    $csv_data[] = ['Mentee Report - Generated: ' . date('F j, Y g:i A')];
    $csv_data[] = ['Instructor: ' . $instructor_name];
    $csv_data[] = [''];
    $csv_data[] = ['#', 'Student Name', 'Email', 'Major', 'Year Level', 'Assigned Date'];
    
    foreach ($mentees as $index => $mentee) {
        $full_name = trim(($mentee['first_name'] ?? '') . ' ' . ($mentee['last_name'] ?? ''));
        $csv_data[] = [
            $index + 1,
            $full_name,
            $mentee['email'] ?? '',
            $mentee['major_name'] ?? '-',
            $mentee['year_level'] ?? '-',
            !empty($mentee['assigned_date']) ? date('M j, Y', strtotime($mentee['assigned_date'])) : '-'
        ];
    }
    
    $csv_data[] = [''];
    $csv_data[] = ['Total Mentees: ' . count($mentees)];
    
    $filename = 'mentee_report_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    foreach ($csv_data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

<?php
/**
 * graduate_process.php
 * Backend processing for Graduate Management System
 */

require_once __DIR__ . '/session_security.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/graduation_support.php';

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check role access (instructor or admin)
$role_access = check_role_access('instructor');
if (!$role_access['allowed']) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$instructor_id = (int) ($_SESSION['user_id'] ?? 0);

if ($action === 'list') {
    // List graduates by scanning PDF files on disk under C:/graduate/
    $batch = $_POST['batch'] ?? '';
    $major = $_POST['major'] ?? '';
    $search = $_POST['search'] ?? '';

    try {
        $records = scan_graduate_pdfs(graduation_disk_base());

        // ── filter: batch year ──────────────────────────────────────────────
        if ($batch) {
            $records = array_values(array_filter($records, fn($r) => $r['batch_year'] === $batch));
        }

        // ── filter: major ──────────────────────────────────────────────────
        if ($major) {
            $records = array_values(array_filter($records, function ($r) use ($major) {
                $slug = strtolower($r['major_slug'] ?? '');
                $name = strtolower($r['major_name'] ?? '');
                if ($major === 'OM') return str_contains($slug, 'op') || str_contains($slug, 'om') || str_contains($name, 'operational');
                if ($major === 'FM') return str_contains($slug, 'fn') || str_contains($slug, 'fm') || str_contains($name, 'financial');
                if ($major === 'MM') return str_contains($slug, 'mk') || str_contains($slug, 'mm') || str_contains($name, 'marketing');
                return false;
            }));
        }

        // ── filter: text search ────────────────────────────────────────────
        if ($search) {
            $q = strtolower($search);
            $records = array_values(array_filter($records, function ($r) use ($q) {
                return str_contains(strtolower($r['student_label'] ?? ''), $q)
                    || str_contains(strtolower($r['file_name'] ?? ''), $q);
            }));
        }

        // ── sort by filename ───────────────────────────────────────────────
        usort($records, fn($a, $b) => strcmp($a['file_name'] ?? '', $b['file_name'] ?? ''));

        if (empty($records)) {
            echo '<div class="no-data"><i class="fas fa-user-graduate"></i><p>No graduates found matching the criteria.</p></div>';
            exit;
        }

        $dl = '../../../data/download_graduation_pdf.php?file_path=';

        echo '<div class="table-responsive"><table><thead><tr><th>Name</th><th>Student ID</th><th>Major</th><th>Final GWA</th><th>Batch Year</th><th>Actions</th></tr></thead><tbody>';
        foreach ($records as $rec) {
            $label    = htmlspecialchars($rec['student_label']);
            $sid      = htmlspecialchars($rec['student_number'] ?? 'N/A');
            $majorNm  = htmlspecialchars($rec['major_name']);
            $gwa      = $rec['gwa'] !== null && $rec['gwa'] !== '' ? number_format((float) $rec['gwa'], 2) : 'N/A';
            $batch    = htmlspecialchars($rec['batch_year'] ?? 'N/A');
            $fpath    = $rec['file_path'];
            echo '
            <tr>
                <td>' . $label . '</td>
                <td>' . $sid . '</td>
                <td>' . $majorNm . '</td>
                <td>' . $gwa . '</td>
                <td>' . $batch . '</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-outline btn-sm" onclick="viewGraduateDetails(\'' . $fpath . '\')">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-outline btn-sm" onclick="downloadProspectus(\'' . $fpath . '\')">
                            <i class="fas fa-download"></i> PDF
                        </button>
                    </div>
                </td>
            </tr>';
        }
        echo '</tbody></table></div>';
    } catch (Exception $e) {
        echo '<div class="no-data"><i class="fas fa-exclamation-triangle"></i><p>Error loading graduates: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
    }
    exit;
}

if ($action === 'get_eligible_students') {
    try {
        ensure_graduation_schema($pdo);
        $stmt = $pdo->prepare("
            SELECT s.id            AS student_id,
                   s.first_name,
                   s.last_name,
                   s.middle_name,
                   s.email,
                   s.major_id,
                   s.year_level,
                   s.status,
                   m.display_name   AS major_name
            FROM students s
            JOIN majors m ON s.major_id = m.id
            JOIN mentees me ON me.student_id = s.id AND me.mentor_id = ?
            WHERE s.status != 'graduated'
              AND s.year_level LIKE '%4th Year%'
              AND s.year_level LIKE '%2nd Semester%'
              AND NOT EXISTS (SELECT 1 FROM graduation_records gr WHERE gr.student_id = s.id)
            ORDER BY s.last_name, s.first_name
        ");
        $stmt->execute([$instructor_id]);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $students = [];
        foreach ($candidates as $row) {
            $mid = (int) ($row['major_id'] ?? 0);
            if ($mid <= 0) {
                continue;
            }
            $c = student_curriculum_completion($pdo, (int) ($row['student_id'] ?? $row['id'] ?? 0), $mid);
            if (!empty($c['complete'])) {
                $students[] = $row;
            }
        }

        echo json_encode(['success' => true, 'students' => $students]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_student_details') {
    // Get details for a specific student
    $student_id = intval($_POST['student_id'] ?? 0);
    
    if (!$student_id) {
        echo json_encode(['success' => false, 'message' => 'Student ID required']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare('
            SELECT s.id            AS student_id,
                   s.student_id    AS student_number,
                   s.first_name,
                   s.last_name,
                   s.middle_name,
                   s.email,
                   s.major_id,
                   s.year_level,
                   s.status,
                   s.student_type,
                   m.display_name   AS major_name
            FROM students s
            JOIN majors m ON s.major_id = m.id
            JOIN mentees me ON me.student_id = s.id AND me.mentor_id = ?
            WHERE s.id = ?
        ');
        $stmt->execute([$instructor_id, $student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student) {
            echo json_encode(['success' => true, 'student' => $student]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'generate_pdf') {
    $student_id = (int) ($_POST['student_id'] ?? 0);
    $batch_year = $_POST['batch_year'] ?? '';

    if ($student_id <= 0 || $batch_year === '') {
        echo json_encode(['success' => false, 'message' => 'Student ID and batch year required']);
        exit;
    }

    try {
        ensure_graduation_schema($pdo);

        $stmt = $pdo->prepare('
            SELECT s.id            AS student_id,
                   s.student_id    AS student_number,
                   s.first_name,
                   s.last_name,
                   s.middle_name,
                   s.email,
                   s.major_id,
                   s.year_level,
                   s.status,
                   s.student_type,
                   m.display_name   AS major_name
            FROM students s
            JOIN majors m ON s.major_id = m.id
            JOIN mentees me ON me.student_id = s.id AND me.mentor_id = ?
            WHERE s.id = ?
        ');
        $stmt->execute([$instructor_id, $student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            echo json_encode(['success' => false, 'message' => 'Student not found or access denied']);
            exit;
        }

        $majorId = (int) ($student['major_id'] ?? 0);
        $curriculum = student_curriculum_completion($pdo, $student_id, $majorId);
        if (empty($curriculum['complete'])) {
            echo json_encode(['success' => false, 'message' => 'Curriculum not complete for PDF generation.', 'curriculum' => $curriculum]);
            exit;
        }

        $gwa = (float) ($curriculum['gwa'] ?? 0);

        $advisorName = '';
        $stmtAdv = $pdo->prepare('SELECT first_name, middle_name, last_name, suffix FROM instructors WHERE id = ?');
        $stmtAdv->execute([$instructor_id]);
        $stmtAdv->execute([$instructor_id]);
        if ($advRow = $stmtAdv->fetch(PDO::FETCH_ASSOC)) {
            $advisorName = trim($advRow['first_name'] . ($advRow['middle_name'] ? ' ' . substr((string) $advRow['middle_name'], 0, 1) . '.' : '') . ' ' . $advRow['last_name'] . ($advRow['suffix'] ? ' ' . $advRow['suffix'] : ''));
        }

        $programHeadName = '';
        try {
            $stmtPH = $pdo->query("
                SELECT i.first_name, i.middle_name, i.last_name, i.suffix
                FROM instructors i
                LEFT JOIN admin_promotions ap ON i.id = ap.instructor_id
                WHERE ap.promoted_to = 'program_head'
                ORDER BY ap.promotion_date DESC
                LIMIT 1
            ");
            if ($stmtPH && ($phRow = $stmtPH->fetch(PDO::FETCH_ASSOC))) {
                $programHeadName = trim($phRow['first_name'] . ($phRow['middle_name'] ? ' ' . substr((string) $phRow['middle_name'], 0, 1) . '.' : '') . ' ' . $phRow['last_name'] . ($phRow['suffix'] ? ' ' . $phRow['suffix'] : ''));
            }
        } catch (PDOException $e) {
        }

        $phSettings = [];
        try {
            $stS = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'program_head_settings'");
            $stS->execute();
            $rowS = $stS->fetch(PDO::FETCH_ASSOC);
            if ($rowS && $rowS['setting_value']) {
                $phSettings = json_decode($rowS['setting_value'], true) ?: [];
            }
        } catch (PDOException $e) {
        }

        $pdfErr = null;
        $graduationDate = date('Y-m-d');
        $pdfPath = graduation_generate_prospectus_pdf(
            $pdo,
            $student,
            $batch_year,
            $advisorName,
            $programHeadName,
            $phSettings,
            $gwa,
            $graduationDate,
            $pdfErr
        );

        if (!$pdfPath) {
            echo json_encode(['success' => false, 'message' => $pdfErr ?: 'PDF generation failed']);
            exit;
        }

        $pdo->prepare('UPDATE students SET status = ?, updated_at = NOW() WHERE id = ?')->execute(['graduated', $student_id]);

        $pdo->prepare('
            INSERT INTO graduation_records
                (student_id, major_id, academic_year, year_level, semester, gwa, graduation_date, total_subjects, pdf_path)
            VALUES (?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                gwa = VALUES(gwa),
                graduation_date = VALUES(graduation_date),
                total_subjects = VALUES(total_subjects),
                pdf_path = VALUES(pdf_path),
                updated_at = NOW()
        ')->execute([
            $student_id,
            $majorId,
            $batch_year,
            '4th Year',
            '2nd Semester',
            $gwa,
            $graduationDate,
            (int) $curriculum['total_required'],
            $pdfPath,
        ]);

        $pdfUrl = '../../../data/download_graduation_pdf.php?student_id=' . $student_id;

        echo json_encode([
            'success' => true,
            'message' => 'Prospectus PDF generated successfully',
            'pdf_url' => $pdfUrl,
            'file_path' => $pdfPath,
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_folder_structure') {
    // Get the actual folder structure for display
    try {
        $basePath = 'C:/graduate/';
        $structure = '';
        
        if (is_dir($basePath)) {
            $directories = scandir($basePath);
            foreach ($directories as $dir) {
                if ($dir === '.' || $dir === '..') continue;
                $dirPath = $basePath . $dir;
                if (is_dir($dirPath)) {
                    $structure .= '<div class="folder-item"><i class="fas fa-folder"></i><span>' . htmlspecialchars($dir) . '\\</span></div>';
                    
                    // Scan batch directory
                    $batchDirs = scandir($dirPath);
                    foreach ($batchDirs as $batchDir) {
                        if ($batchDir === '.' || $batchDir === '..') continue;
                        $batchPath = $dirPath . '/' . $batchDir;
                        if (is_dir($batchPath)) {
                            $structure .= '<div class="folder-item" style="margin-left:20px;"><i class="fas fa-folder"></i><span>' . htmlspecialchars($batchDir) . '\\</span></div>';
                            
                            // Scan major directory
                            $majorDirs = scandir($batchPath);
                            foreach ($majorDirs as $majorDir) {
                                if ($majorDir === '.' || $majorDir === '..') continue;
                                $majorPath = $batchPath . '/' . $majorDir;
                                if (is_dir($majorPath)) {
                                    $structure .= '<div class="folder-item" style="margin-left:40px;"><i class="fas fa-folder"></i><span>' . htmlspecialchars($majorDir) . '\\</span></div>';
                                    
                                    // Scan PDF files
                                    $files = scandir($majorPath);
                                    foreach ($files as $file) {
                                        if ($file === '.' || $file === '..') continue;
                                        if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                                            $structure .= '<div class="folder-item" style="margin-left:60px;"><i class="fas fa-file-pdf"></i><span>' . htmlspecialchars($file) . '</span></div>';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $structure = '<div class="no-data"><i class="fas fa-info-circle"></i><p>No graduate folder structure found. PDFs will be created when generating prospectus.</p></div>';
        }
        
        echo json_encode(['success' => true, 'html' => $structure]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error reading folder structure: ' . $e->getMessage()]);
    }
    exit;
}


if ($action === 'get_reports') {
    // Generate reports for a specific batch — DB-based (pulls GWA, date, subjects)
    $batch = $_POST['batch'] ?? '';

    if (!$batch) {
        echo json_encode(['success' => false, 'message' => 'Batch year required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT s.id            AS student_id,
                   s.student_id    AS student_number,
                   s.first_name,
                   s.last_name,
                   s.middle_name,
                   s.email,
                   gr.gwa,
                   gr.graduation_date,
                   gr.total_subjects,
                   m.display_name   AS major_name
            FROM graduation_records gr
            JOIN students s ON s.id = gr.student_id
            JOIN majors m ON s.major_id = m.id
            WHERE gr.academic_year = ?
            ORDER BY s.last_name, s.first_name
        ");
        $stmt->execute([$batch]);
        $graduates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($graduates)) {
            echo '<div class="no-data"><i class="fas fa-info-circle"></i><p>No graduation records found for batch ' . htmlspecialchars($batch) . '.</p></div>';
            exit;
        }

        echo '
        <h3>Graduation Report - Batch ' . htmlspecialchars($batch) . '</h3>
        <p>Generated on: ' . htmlspecialchars(date('F j, Y, g:i a')) . '  ·  Source: database records</p>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Student ID</th>
                        <th>Major</th>
                        <th>GWA</th>
                        <th>Graduation Date</th>
                        <th>Total Subjects</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($graduates as $graduate) {
            echo '
                    <tr>
                        <td>' . htmlspecialchars($graduate['last_name'] . ', ' . $graduate['first_name'] . ' ' . ($graduate['middle_name'] ?? '')) . '</td>
                        <td>' . htmlspecialchars($graduate['student_number'] ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars($graduate['major_name'] ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars($graduate['gwa'] ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars($graduate['graduation_date'] ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars($graduate['total_subjects'] ?? 0) . '</td>
                    </tr>';
        }

        echo '
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px;">
            <button class="btn btn-outline" onclick="window.print()">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>';
    } catch (PDOException $e) {
        echo '<div class="no-data"><i class="fas fa-exclamation-triangle"></i><p>Error generating report: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
    }
    exit;
}

if ($action === 'get_stats') {
    // Get statistics for a specific batch — DB-based (pulls GWA stats, major breakdown)
    $batch = $_POST['batch'] ?? '';

    if (!$batch) {
        echo json_encode(['success' => false, 'message' => 'Batch year required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) as total_graduates,
                AVG(gr.gwa) as average_gwa,
                MIN(gr.gwa) as minimum_gwa,
                MAX(gr.gwa) as maximum_gwa,
                m.display_name as major_name,
                COUNT(*) as major_count
            FROM graduation_records gr
            JOIN students s ON s.id = gr.student_id
            JOIN majors m ON s.major_id = m.id
            WHERE gr.academic_year = ?
            GROUP BY m.display_name
            ORDER BY major_count DESC
        ");
        $stmt->execute([$batch]);
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get overall stats
        $overallStmt = $pdo->prepare("
            SELECT
                COUNT(*) as total_graduates,
                AVG(gr.gwa) as average_gwa
            FROM graduation_records gr
            WHERE gr.academic_year = ?
        ");
        $overallStmt->execute([$batch]);
        $overall = $overallStmt->fetch(PDO::FETCH_ASSOC);

        // Generate stats cards
        $html = '';

        // Overall stats
        $html .= '
        <div class="stat-card">
            <div class="stat-number">' . htmlspecialchars($overall['total_graduates'] ?? 0) . '</div>
            <div class="stat-label">Total Graduates</div>
        </div>';

        foreach ($stats as $stat) {
            $html .= '
            <div class="stat-card">
                <div class="stat-number">' . htmlspecialchars($stat['major_count'] ?? 0) . '</div>
                <div class="stat-label">' . htmlspecialchars($stat['major_name'] ?? 'Unknown') . ' Graduates</div>
            </div>';
        }

        echo $html;
    } catch (PDOException $e) {
        echo '<div class="no-data"><i class="fas fa-exclamation-triangle"></i><p>Error loading statistics: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit;
?>
<?php
/**
 * Graduation schema, curriculum completion checks, disk paths, and FPDF prospectus output.
 */

declare(strict_types=1);

function graduation_pdf_text(string $s): string
{
    $t = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);
    return $t !== false ? $t : preg_replace('/[^\x20-\x7E]/', '?', $s);
}

function graduation_major_slug(string $displayName): string
{
    $n = strtolower(trim($displayName));
    if (str_contains($n, 'operational')) {
        return 'om';
    }
    if (str_contains($n, 'financial')) {
        return 'fm';
    }
    if (str_contains($n, 'marketing')) {
        return 'mm';
    }
    $slug = preg_replace('/[^a-z0-9]+/', '', $n);

    return $slug !== '' ? substr($slug, 0, 14) : 'major';
}

function graduation_student_file_slug(string $first, string $last): string
{
    $a = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($first)));
    $b = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($last)));
    $out = trim($a . '_' . $b, '_');

    return $out !== '' ? $out : 'student';
}

function graduation_disk_base(): string
{
    return 'C:/graduate/';
}

function graduation_batch_folder(string $batchYear): string
{
    return 'batch ' . $batchYear;
}

function ensure_graduation_schema(PDO $pdo): void
{
    try {
        $pdo->exec("ALTER TABLE students MODIFY COLUMN status ENUM('regular','transfer','non_ibm','graduated') DEFAULT 'regular'");
    } catch (Throwable $e) {
        // already migrated or insufficient privilege
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS graduation_records (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        major_id INT NOT NULL,
        academic_year VARCHAR(32) NOT NULL DEFAULT '',
        year_level VARCHAR(64) NOT NULL DEFAULT '',
        semester VARCHAR(64) NOT NULL DEFAULT '',
        gwa DECIMAL(10,4) DEFAULT NULL,
        graduation_date DATE DEFAULT NULL,
        total_subjects INT UNSIGNED DEFAULT 0,
        pdf_path VARCHAR(768) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_grad_student (student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    try {
        $pdo->exec('ALTER TABLE graduation_records ADD COLUMN pdf_path VARCHAR(768) DEFAULT NULL');
    } catch (Throwable $e) {
    }
}

function student_graduation_locked(PDO $pdo, int $studentId): bool
{
    $st = $pdo->prepare('SELECT status FROM students WHERE id = ? LIMIT 1');
    $st->execute([$studentId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row && ($row['status'] ?? '') === 'graduated') {
        return true;
    }
    $gr = $pdo->prepare('SELECT id FROM graduation_records WHERE student_id = ? LIMIT 1');
    $gr->execute([$studentId]);

    return (bool) $gr->fetchColumn();
}

/**
 * Latest grade_rounded per subject_id for a student (by graded_at, then id).
 *
 * @return array<int,float|null>
 */
function graduation_latest_grade_map(PDO $pdo, int $studentId): array
{
    $stmt = $pdo->prepare('SELECT subject_id, grade_rounded, graded_at, id FROM student_grades WHERE student_id = ? ORDER BY graded_at DESC, id DESC');
    $stmt->execute([$studentId]);
    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $g) {
        $sid = (int) $g['subject_id'];
        if (!array_key_exists($sid, $map)) {
            $map[$sid] = $g['grade_rounded'] !== null ? (float) $g['grade_rounded'] : null;
        }
    }

    return $map;
}

/**
 * @return array{
 *   total_required:int,
 *   passed:int,
 *   pending:int,
 *   blocked:int,
 *   complete:bool,
 *   gwa:?float,
 *   units_total:float,
 *   units_passed:float
 * }
 */
function student_curriculum_completion(PDO $pdo, int $studentId, int $majorId): array
{
    $stmt = $pdo->prepare('
        SELECT s.id AS subject_id, s.units
        FROM major_subjects ms
        INNER JOIN subjects s ON s.id = ms.subject_id
        WHERE ms.major_id = ? AND ms.is_required = 1
    ');
    $stmt->execute([$majorId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $gradeMap = graduation_latest_grade_map($pdo, $studentId);

    $totalReq = count($rows);
    $pending = 0;
    $blocked = 0;
    $passed = 0;
    $unitsTotal = 0.0;
    $unitsPassed = 0.0;
    $points = 0.0;

    foreach ($rows as $r) {
        $sid = (int) $r['subject_id'];
        $units = (float) $r['units'];
        $unitsTotal += $units;
        $gr = $gradeMap[$sid] ?? null;
        if ($gr === null) {
            $pending++;

            continue;
        }
        if ($gr <= 3.0) {
            $passed++;
            $unitsPassed += $units;
            $points += $gr * $units;
        } else {
            $blocked++;
        }
    }

    $complete = $totalReq > 0 && $pending === 0 && $blocked === 0 && $passed === $totalReq;
    $gwa = ($complete && $unitsPassed > 0) ? round($points / $unitsPassed, 4) : null;

    return [
        'total_required' => $totalReq,
        'passed' => $passed,
        'pending' => $pending,
        'blocked' => $blocked,
        'complete' => $complete,
        'gwa' => $gwa,
        'units_total' => round($unitsTotal, 2),
        'units_passed' => round($unitsPassed, 2),
    ];
}

function graduation_load_ph_settings(PDO $pdo): array
{
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'program_head_settings'");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['setting_value']) {
            return json_decode($row['setting_value'], true) ?: [];
        }
    } catch (Throwable $e) {
    }

    return [];
}

/**
 * @return array{0:string,1:string}
 */
function graduation_advisor_and_program_head(PDO $pdo, int $instructorId): array
{
    $advisorName = '';
    try {
        $stmtAdv = $pdo->prepare('SELECT first_name, middle_name, last_name, suffix FROM instructors WHERE id = ?');
        $stmtAdv->execute([$instructorId]);
        $advRow = $stmtAdv->fetch(PDO::FETCH_ASSOC);
        if ($advRow) {
            $advisorName = trim($advRow['first_name'] . ($advRow['middle_name'] ? ' ' . substr((string) $advRow['middle_name'], 0, 1) . '.' : '') . ' ' . $advRow['last_name'] . ($advRow['suffix'] ? ' ' . $advRow['suffix'] : ''));
        }
    } catch (Throwable $e) {
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
    } catch (Throwable $e) {
    }

    return [$advisorName, $programHeadName];
}

function graduation_pdf_grade_status(?float $g): string
{
    if ($g === null) {
        return '—';
    }
    if ($g <= 3.0) {
        return 'Passed';
    }
    if ($g < 5.0) {
        return 'Conditional';
    }

    return 'Failed';
}

/**
 * Mark graduated, save record, write prospectus PDF (print-style). Returns payload for JSON.
 *
 * @return array<string,mixed>
 */
function graduation_complete_workflow(
    PDO $pdo,
    int $instructorId,
    int $studentId,
    int $majorIdPost,
    string $academicYear,
    string $yearLevel,
    string $semester
): array {
    $pdfPath = null;
    try {
        ensure_graduation_schema($pdo);

        $stmt = $pdo->prepare('
            SELECT s.*, m.display_name AS major_name
            FROM students s
            JOIN mentees me ON s.id = me.student_id AND me.mentor_id = ?
            LEFT JOIN majors m ON s.major_id = m.id
            WHERE s.id = ?
            LIMIT 1
        ');
        $stmt->execute([$instructorId, $studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$student) {
            return ['success' => false, 'message' => 'Student not found or access denied'];
        }

        if (student_graduation_locked($pdo, $studentId)) {
            return ['success' => false, 'message' => 'This student is already marked as graduated.'];
        }

        $major_id = (int) ($student['major_id'] ?? 0);
        if ($major_id <= 0 || $major_id !== $majorIdPost) {
            return ['success' => false, 'message' => 'Invalid major for this student.'];
        }

        $curriculum = student_curriculum_completion($pdo, $studentId, $major_id);
        if (!$curriculum['complete']) {
            return [
                'success' => false,
                'message' => 'Curriculum is not fully complete. Resolve pending or failed subjects before graduation.',
                'curriculum' => $curriculum,
            ];
        }

        $gwa = (float) ($curriculum['gwa'] ?? 0);
        $totalSubjects = (int) $curriculum['total_required'];
        [$advisorName, $programHeadName] = graduation_advisor_and_program_head($pdo, $instructorId);
        $phSettings = graduation_load_ph_settings($pdo);

        $graduationDate = date('Y-m-d');
        $pdfErr = null;
        $pdfPath = graduation_generate_prospectus_pdf(
            $pdo,
            $student,
            $academicYear,
            $advisorName,
            $programHeadName,
            $phSettings,
            $gwa,
            $graduationDate,
            $pdfErr
        );

        if (!$pdfPath) {
            return ['success' => false, 'message' => 'PDF could not be generated: ' . ($pdfErr ?: 'unknown error')];
        }

        $pdo->beginTransaction();

        $pdo->prepare('UPDATE students SET status = ?, year_level = ?, updated_at = NOW() WHERE id = ?')
            ->execute(['graduated', '4th Year - 2nd Semester', $studentId]);

        $pdo->prepare('
            INSERT INTO graduation_records
                (student_id, major_id, academic_year, year_level, semester, gwa, graduation_date, total_subjects, pdf_path)
            VALUES (?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                major_id = VALUES(major_id),
                academic_year = VALUES(academic_year),
                year_level = VALUES(year_level),
                semester = VALUES(semester),
                gwa = VALUES(gwa),
                graduation_date = VALUES(graduation_date),
                total_subjects = VALUES(total_subjects),
                pdf_path = VALUES(pdf_path),
                updated_at = NOW()
        ')->execute([
            $studentId,
            $major_id,
            $academicYear,
            $yearLevel,
            $semester,
            $gwa,
            $graduationDate,
            $totalSubjects,
            $pdfPath,
        ]);

        $pdo->commit();

        $pdfUrl = '../../../data/download_graduation_pdf.php?student_id=' . $studentId;

        return [
            'success' => true,
            'message' => 'Student graduated. Official prospectus PDF saved under C:\\graduate\\…',
            'total_subjects' => $totalSubjects,
            'gwa' => round($gwa, 4),
            'graduation_date' => $graduationDate,
            'pdf_url' => $pdfUrl,
            'pdf_path' => $pdfPath,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (!empty($pdfPath) && is_file($pdfPath)) {
            @unlink($pdfPath);
        }

        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function graduation_fetch_record(PDO $pdo, int $studentId): ?array
{
    $st = $pdo->prepare('SELECT * FROM graduation_records WHERE student_id = ? LIMIT 1');
    $st->execute([$studentId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function graduation_build_fs_path(string $batchYear, string $majorSlug, string $filename): string
{
    $base = rtrim(graduation_disk_base(), '/') . '/';
    $dir = $base . graduation_batch_folder($batchYear) . '/' . $majorSlug . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    return $dir . $filename;
}

/**
 * Print-style official prospectus PDF (matches on-screen / print grade report layout).
 * Saves under C:/graduate/batch {AY}/{om|fm|mm}/firstname_lastname_major_batch{AY}.pdf
 */
function graduation_generate_prospectus_pdf(
    PDO $pdo,
    array $student,
    string $academicYear,
    string $advisorName,
    string $programHeadName,
    array $phSettings,
    float $gwa,
    string $graduationDateYmd,
    ?string &$errorOut
): ?string {
    $errorOut = null;
    $lib = __DIR__ . '/lib/fpdf186/fpdf.php';
    if (!is_file($lib)) {
        $errorOut = 'FPDF library missing (data/lib/fpdf186/fpdf.php)';

        return null;
    }
    require_once $lib;

    $majorName = (string) ($student['major_name'] ?? '');
    $majorSlug = graduation_major_slug($majorName);
    $slug = graduation_student_file_slug((string) ($student['first_name'] ?? ''), (string) ($student['last_name'] ?? ''));
    $filename = $slug . '_' . $majorSlug . '_batch' . $academicYear . '.pdf';
    $path = graduation_build_fs_path($academicYear, $majorSlug, $filename);

    $studentId = (int) ($student['id'] ?? 0);
    $majorId = (int) ($student['major_id'] ?? 0);

    $stmt = $pdo->prepare('
        SELECT s.id AS subject_id, s.subject_code, s.subject_name, s.units, ms.year_level, ms.semester
        FROM major_subjects ms
        INNER JOIN subjects s ON s.id = ms.subject_id
        WHERE ms.major_id = ? AND ms.is_required = 1
        ORDER BY FIELD(ms.year_level,\'Bridging\',\'1st Year\',\'2nd Year\',\'3rd Year\',\'4th Year\'),
                 FIELD(ms.semester,\'1st Semester\',\'2nd Semester\'),
                 ms.sort_order
    ');
    $stmt->execute([$majorId]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $gradeMap = graduation_latest_grade_map($pdo, $studentId);

    $school = $phSettings['school_name'] ?? 'Northern Bukidnon State College';
    $addr = $phSettings['school_address'] ?? '';
    $inst = $phSettings['institute_name'] ?? '';
    $degree = $phSettings['degree_name'] ?? '';

    $gdTs = strtotime($graduationDateYmd);
    $gdPretty = $gdTs ? date('F j, Y', $gdTs) : $graduationDateYmd;

    $pdf = new FPDF('P', 'mm', 'Letter');
    $pdf->SetMargins(14, 14, 14);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->AddPage();

    $pdf->SetDrawColor(139, 105, 20);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(14, $pdf->GetY(), 202, $pdf->GetY());
    $pdf->Ln(2);

    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->SetTextColor(60, 40, 10);
    $pdf->Cell(0, 7, graduation_pdf_text($school), 0, 1, 'C');
    $pdf->SetFont('Helvetica', 'I', 9);
    $pdf->SetTextColor(80, 80, 80);
    if ($addr !== '') {
        $pdf->Cell(0, 5, graduation_pdf_text($addr), 0, 1, 'C');
    }
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(60, 40, 10);
    if ($inst !== '') {
        $pdf->Cell(0, 5, graduation_pdf_text($inst), 0, 1, 'C');
    }
    if ($degree !== '') {
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, graduation_pdf_text($degree), 0, 1, 'C');
    }
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetTextColor(139, 105, 20);
    $pdf->Cell(0, 6, 'Major in ' . graduation_pdf_text($majorName), 0, 1, 'C');
    $pdf->Ln(1);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'Student Evaluation Prospectus (Official Copy)', 0, 1, 'C');
    $pdf->Ln(3);

    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.15);
    $pdf->SetFillColor(250, 248, 244);
    $pdf->SetTextColor(0, 0, 0);
    $boxY = $pdf->GetY();
    $pdf->Rect(14, $boxY, 188, 28, 'DF');
    $pdf->SetXY(18, $boxY + 3);
    $pdf->SetFont('Helvetica', '', 9);
    $fullName = trim(($student['last_name'] ?? '') . ', ' . ($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ?? ''));
    $pdf->Cell(55, 5, 'Student:', 0, 0);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(115, 5, graduation_pdf_text($fullName), 0, 1);
    $pdf->SetX(18);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(55, 5, 'Student No.:', 0, 0);
    $pdf->Cell(115, 5, graduation_pdf_text((string) ($student['student_id'] ?? $student['student_number'] ?? '')), 0, 1);
    $pdf->SetX(18);
    $pdf->Cell(55, 5, 'Academic Year:', 0, 0);
    $pdf->Cell(55, 5, graduation_pdf_text($academicYear), 0, 0);
    $pdf->Cell(40, 5, 'Graduation date:', 0, 0);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(0, 5, graduation_pdf_text($gdPretty), 0, 1);
    $pdf->SetX(18);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(55, 5, 'Status:', 0, 0);
    $pdf->SetTextColor(22, 101, 52);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(0, 5, 'GRADUATED', 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetY($boxY + 30);

    $drawTableHeader = static function ($pdf): void {
        $pdf->SetFont('Helvetica', 'B', 7.5);
        $pdf->SetFillColor(184, 134, 11);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(18, 6.5, 'Grade', 1, 0, 'C', true);
        $pdf->Cell(24, 6.5, 'Status', 1, 0, 'C', true);
        $pdf->Cell(24, 6.5, 'Code', 1, 0, 'L', true);
        $pdf->Cell(72, 6.5, 'Description', 1, 0, 'L', true);
        $pdf->Cell(14, 6.5, 'Units', 1, 0, 'C', true);
        $pdf->Cell(36, 6.5, 'Year / Semester', 1, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
    };

    $drawTableHeader($pdf);

    $pdf->SetFont('Helvetica', '', 7.5);
    $sumUnits = 0.0;
    $maxY = 252;
    foreach ($subjects as $sub) {
        $sid = (int) ($sub['subject_id'] ?? 0);
        if ($sid === 0) {
            continue;
        }
        if ($pdf->GetY() > $maxY) {
            $pdf->AddPage();
            $drawTableHeader($pdf);
            $pdf->SetFont('Helvetica', '', 7.5);
        }
        $g = $gradeMap[$sid] ?? null;
        $gStr = $g !== null ? number_format($g, 2) : '—';
        $st = graduation_pdf_grade_status($g);
        $code = graduation_pdf_text((string) $sub['subject_code']);
        if (strlen($code) > 14) {
            $code = substr($code, 0, 12) . '.';
        }
        $nameRaw = graduation_pdf_text((string) $sub['subject_name']);
        $name = strlen($nameRaw) > 52 ? substr($nameRaw, 0, 49) . '...' : $nameRaw;
        $units = (float) $sub['units'];
        $sumUnits += $units;
        $unitsStr = (string) $units;
        $ys = graduation_pdf_text(trim(($sub['year_level'] ?? '') . ' / ' . ($sub['semester'] ?? '')));
        if (strlen($ys) > 28) {
            $ys = substr($ys, 0, 25) . '...';
        }

        $pdf->Cell(18, 5.5, $gStr, 1, 0, 'C');
        $pdf->Cell(24, 5.5, graduation_pdf_text($st), 1, 0, 'C');
        $pdf->Cell(24, 5.5, $code, 1, 0, 'L');
        $pdf->Cell(72, 5.5, $name, 1, 0, 'L');
        $pdf->Cell(14, 5.5, $unitsStr, 1, 0, 'C');
        $pdf->Cell(36, 5.5, $ys, 1, 1, 'L');
    }

    if ($pdf->GetY() > $maxY - 14) {
        $pdf->AddPage();
    }
    $pdf->Ln(2);
    $pdf->SetFillColor(184, 134, 11);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(120, 7, '  Curriculum units completed', 1, 0, 'L', true);
    $pdf->Cell(68, 7, number_format($sumUnits, 1) . ' units  ', 1, 1, 'R', true);
    $pdf->Cell(120, 8, '  Final GWA (program)', 1, 0, 'L', true);
    $pdf->Cell(68, 8, number_format($gwa, 4) . '  ', 1, 1, 'R', true);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->Ln(10);
    $pdf->SetDrawColor(139, 105, 20);
    $pdf->Line(14, $pdf->GetY(), 202, $pdf->GetY());
    $pdf->Ln(8);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->Cell(94, 4, 'Prepared by:', 0, 0, 'L');
    $pdf->Cell(0, 4, 'Certified correct:', 0, 1, 'L');
    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(94, 5, graduation_pdf_text($advisorName !== '' ? $advisorName : 'Academic Adviser'), 'T', 0, 'C');
    $pdf->Cell(0, 5, graduation_pdf_text($programHeadName !== '' ? $programHeadName : 'Program Head'), 'T', 1, 'C');
    $pdf->SetFont('Helvetica', '', 7);
    $pdf->Cell(94, 4, "Adviser's signature line", 0, 0, 'C');
    $pdf->Cell(0, 4, "Program Head's signature line", 0, 1, 'C');

    try {
        $pdf->Output('F', $path);
    } catch (Throwable $e) {
        $errorOut = $e->getMessage();

        return null;
    }

    return $path;
}

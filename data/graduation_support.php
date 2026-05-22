<?php
/**
 * Graduation schema, curriculum completion checks, disk paths, and FPDF prospectus output.
 * Layout: 2 Legal-size pages (8.5" x 14")
 *   Page 1: Header, Student Info, 1st Year (both semesters), 2nd Year (both semesters)
 *   Page 2: 3rd Year (both semesters), 4th Year (both semesters), Bridging, Footer
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

function graduation_student_file_slug(string $first, string $last, int $studentId = 0): string
{
    $a = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($first)));
    $b = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($last)));
    $name = trim($a . '_' . $b, '_');
    if ($name === '') $name = 'student';
    $sid = $studentId > 0 ? 's' . $studentId : '';
    return $name . ($sid ? '_' . $sid : '');
}

function graduation_disk_base(): string
{
    return 'C:/graduate/';
}

function graduation_batch_folder(string $batchYear): string
{
    return 'batch ' . $batchYear;
}

function graduation_major_full_name(string $slug): string
{
    $n = strtolower($slug);
    if (str_contains($n, 'op') || str_contains($n, 'om'))  return 'Operational Management';
    if (str_contains($n, 'fn') || str_contains($n, 'fm'))  return 'Financial Management';
    if (str_contains($n, 'mk') || str_contains($n, 'mm'))  return 'Marketing Management';
    return $slug;
}

/**
 * Parse filename-based graduate record into structured fields.
 */
function parse_graduate_pdf(string $relPath, string $baseDir): array
{
    $file   = basename($relPath);
    $stem   = pathinfo($file, PATHINFO_FILENAME);
    $t      = array_values(array_filter(explode('_', $stem)));

    $batchYear  = '';
    $gwaStr     = '';
    $studentId  = '';
    $majorSlug  = '';
    $reserved   = [];

    foreach ($t as $i => $tok) {
        if ($tok === '') continue;

        if (preg_match('/^batch\d{4}-\d{4}$/', $tok)) {
            if (preg_match('/^batch(\d{4}-\d{4})$/', $tok, $bm)) $batchYear = $bm[1];
            $reserved[] = $i;
            continue;
        }

        if (preg_match('/^gwa(\d+(?:\.\d+)?)$/', $tok, $gm)) {
            $gwaStr = $gm[1];
            $reserved[] = $i;
            continue;
        }

        if ($tok[0] === 's' && isset($tok[1]) && ctype_digit(substr($tok, 1))) {
            $studentId  = substr($tok, 1);
            $reserved[] = $i;
            continue;
        }

        if (ctype_digit($tok) && strlen($tok) >= 4) {
            $studentId  = $tok;
            $reserved[] = $i;
            continue;
        }
    }

    $sidPos = null;
    foreach ($t as $idx => $tok) {
        if (preg_match('/^s?\d+$/', $tok) && strlen($tok) >= 2 && ctype_digit(substr($tok, ($tok[0]==='s'?1:0)))) {
            $sidPos = $idx;
            break;
        }
    }
    if ($sidPos !== null && isset($t[$sidPos + 1])) {
        $tok = $t[$sidPos + 1];
        if (preg_match('/^[a-z]{2,10}$/', $tok)) $majorSlug = $tok;
    }

    if ($majorSlug === '') {
        for ($ri = count($t) - 2; $ri >= 0; $ri--) {
            if (in_array($ri, $reserved, true)) continue;
            $tok = $t[$ri];
            if (preg_match('/^[a-z]{2,10}$/', $tok)) {
                $majorSlug = $tok;
                $reserved[] = $ri;
                break;
            }
        }
    }

    $nameTokens = [];
    foreach ($t as $i => $tok) {
        if (!in_array($i, $reserved, true)) $nameTokens[] = $tok;
    }

    if (empty($reserved)) $nameTokens = $t;

    if (count($nameTokens) >= 2) {
        $last  = $nameTokens[0];
        $first = $nameTokens[1];
    } elseif (count($nameTokens) === 1) {
        $last  = $nameTokens[0];
        $first = '';
    } else {
        $last = $first = '';
    }

    $majorName    = graduation_major_full_name($majorSlug);
    $studentLabel = trim(($last ?: $first) . ', ' . ($first ?: ''));

    return [
        'file_path'     => $relPath,
        'file_name'     => $file,
        'batch_year'    => $batchYear,
        'major_slug'    => $majorSlug,
        'major_name'    => $majorName,
        'first_name'    => $first,
        'last_name'     => $last,
        'student_label' => $studentLabel,
        'student_number'=> $studentId !== '' ? $studentId : null,
        'gwa'           => $gwaStr !== '' ? (float) $gwaStr : null,
    ];
}

/**
 * Directory shape:
 *   C:/graduate/
 *     batch 2025-2026/
 *       om/    last_first_s{studentId}_om_gwa{value}_batch2025-2026.pdf
 *       fm/    ...
 *       mm/    ...
 */
function scan_graduate_pdfs(string $baseDir = 'C:/graduate/'): array
{
    $records = [];
    if (!is_dir($baseDir)) return $records;

    $batchDirs = @scandir($baseDir);
    if ($batchDirs === false) return $records;

    foreach ($batchDirs as $batchDir) {
        if ($batchDir === '.' || $batchDir === '..') continue;
        $batchPath = $baseDir . $batchDir;
        if (!is_dir($batchPath)) continue;
        if (!preg_match('/batch\s+(\d{4}-\d{4})/', $batchDir, $bm)) continue;
        $batchYear = $bm[1];

        $majorDirs = @scandir($batchPath);
        if ($majorDirs === false) continue;

        foreach ($majorDirs as $majorDir) {
            if ($majorDir === '.' || $majorDir === '..') continue;
            $majorPath = $batchPath . '/' . $majorDir;
            if (!is_dir($majorPath)) continue;

            $files = @scandir($majorPath);
            if ($files === false) continue;

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'pdf') continue;

                $relPath  = $majorPath . '/' . $file;
                $rec      = parse_graduate_pdf($file, $majorPath);

                $metaPath = $relPath . '.json';
                if (is_file($metaPath)) {
                    $metaJson = @file_get_contents($metaPath);
                    if ($metaJson !== false) {
                        $meta = json_decode($metaJson, true);
                        if (is_array($meta)) {
                            $rec['gwa']            = $meta['gwa']            ?? $rec['gwa'];
                            $rec['graduation_date'] = $meta['graduation_date'] ?? null;
                            $rec['total_subjects']  = $meta['total_subjects']  ?? 0;
                            $rec['units_passed']    = $meta['units_passed']    ?? null;
                            $rec['student_name']    = $meta['student_name']    ?? null;
                            $rec['student_number']  = $meta['student_number']  ?? $rec['student_number'];
                            $rec['document_title']  = $meta['document_title']  ?? null;
                        }
                    }
                }

                $rec['file_path'] = $relPath;
                $records[] = $rec;
            }
        }
    }

    return $records;
}

function ensure_graduation_schema(PDO $pdo): void
{
    try {
        $pdo->exec("ALTER TABLE students MODIFY COLUMN status ENUM('regular','transfer','non_ibm','graduated') DEFAULT 'regular'");
    } catch (Throwable $e) {
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
 * Latest grade_rounded per subject_id for a student.
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
 * @return array{total_required:int, passed:int, pending:int, blocked:int, complete:bool, gwa:?float, units_total:float, units_passed:float}
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
    $gwa = ($unitsPassed > 0) ? round($points / $unitsPassed, 4) : null;

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
 * Mark graduated, save record, write prospectus PDF. Returns payload for JSON.
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
        if ($major_id <= 0) {
            return ['success' => false, 'message' => 'Invalid major for this student.'];
        }
        if ($majorIdPost > 0 && $major_id !== $majorIdPost) {
            return ['success' => false, 'message' => 'Invalid major for this student.'];
        }

        $curriculum = student_curriculum_completion($pdo, $studentId, $major_id);

        // Block graduation only if there are failed subjects (blocked > 0)
        // Pending subjects can be graduated (student can retake later)
        if ($curriculum['blocked'] > 0) {
            return [
                'success' => false,
                'message' => 'Student has failed subjects. These must be retaken and passed before graduation.',
                'curriculum' => $curriculum,
            ];
        }

        $gwa = (float) ($curriculum['gwa'] ?? 0);
        $totalSubjects = (int) $curriculum['passed'];
        [$advisorName, $programHeadName] = graduation_advisor_and_program_head($pdo, $instructorId);
        $phSettings = graduation_load_ph_settings($pdo);

        $isIncomplete = !$curriculum['complete'];
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

        $base       = rtrim(graduation_disk_base(), '\/');
        $folderPath = $base . DIRECTORY_SEPARATOR . graduation_batch_folder($academicYear)
                    . DIRECTORY_SEPARATOR . graduation_major_slug((string)($student['major_name'] ?? ''));

        return [
            'success'          => true,
            'message'          => $isIncomplete 
                ? 'Student graduated (incomplete curriculum). Official prospectus PDF saved under C:\\graduate\\…'
                : 'Student graduated. Official prospectus PDF saved under C:\\graduate\\…',
            'total_subjects'   => $totalSubjects,
            'gwa'              => round($gwa, 4),
            'graduation_date'  => $graduationDate,
            'pdf_url'          => $pdfUrl,
            'pdf_path'         => $pdfPath,
            'folder_path'      => $folderPath,
            'curriculum'       => $curriculum,
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

/**
 * Graduation-only PDF re-generation.
 * @return array{success:bool, pdf_url?:string, pdf_path?:string, message:string}
 */
function graduation_regenerate_pdf_only(
    PDO $pdo,
    int $instructorId,
    int $studentId,
    string $academicYear,
    string $yearLevel,
    string $semester
): array {
    $pdfErr = null;

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

    $major_id = (int) ($student['major_id'] ?? 0);
    if ($major_id <= 0) {
        return ['success' => false, 'message' => 'Student has no major assigned.'];
    }

    $curriculum = student_curriculum_completion($pdo, $studentId, $major_id);

    // Block only if there are failed subjects (blocked > 0)
    if ($curriculum['blocked'] > 0) {
        return [
            'success' => false,
            'message' => 'Student has failed subjects. These must be retaken and passed before regenerating prospectus.',
            'curriculum' => $curriculum,
        ];
    }

    $gwa            = (float) ($curriculum['gwa'] ?? 0);
    $totalSubjects  = (int) $curriculum['passed'];

    [$advisorName, $programHeadName] = graduation_advisor_and_program_head($pdo, $instructorId);
    $phSettings = graduation_load_ph_settings($pdo);

    $pdfPath = graduation_generate_prospectus_pdf(
        $pdo, $student, $academicYear,
        $advisorName, $programHeadName, $phSettings,
        $gwa, date('Y-m-d'), $pdfErr
    );
    if (!$pdfPath) {
        return ['success' => false, 'message' => 'PDF could not be generated: ' . ($pdfErr ?: 'unknown error')];
    }

    $pdo->prepare('
        INSERT INTO graduation_records
            (student_id, major_id, academic_year, year_level, semester, gwa, graduation_date, total_subjects, pdf_path)
        VALUES (?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            major_id      = VALUES(major_id),
            academic_year = VALUES(academic_year),
            year_level    = VALUES(year_level),
            semester      = VALUES(semester),
            pdf_path      = VALUES(pdf_path),
            updated_at    = NOW()
    ')->execute([
        $studentId,
        $major_id,
        $academicYear,
        $yearLevel,
        $semester,
        $gwa,
        date('Y-m-d'),
        $totalSubjects,
        $pdfPath,
    ]);

    return [
        'success'         => true,
        'message'         => 'Prospectus PDF regenerated successfully.',
        'total_subjects'  => $totalSubjects,
        'gwa'             => round($gwa, 4),
        'graduation_date' => date('Y-m-d'),
        'pdf_url'         => '../../../data/download_graduation_pdf.php?student_id=' . $studentId,
        'pdf_path'        => $pdfPath,
    ];
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
    $base     = rtrim(graduation_disk_base(), '\/');
    $batchDir = $base . DIRECTORY_SEPARATOR . graduation_batch_folder($batchYear);
    $majorDir = $batchDir . DIRECTORY_SEPARATOR . $majorSlug;
    $dir      = $majorDir . DIRECTORY_SEPARATOR;

    foreach ([$base, $batchDir, $majorDir] as $segment) {
        if (!is_dir($segment)) {
            if (!@mkdir($segment, 0777, false) && !is_dir($segment)) {
                throw new RuntimeException(
                    'Could not create graduation directory: ' . $segment .
                    ' — check that PHP has write permission to ' . $base
                );
            }
        }
    }

    return $dir . $filename;
}

/**
 * Print-style official prospectus PDF on LEGAL paper (8.5" x 14").
 * 
 * Layout Distribution:
 *   PAGE 1: Header + Student Info + 1st Year (1st & 2nd Sem) + 2nd Year (1st & 2nd Sem)
 *   PAGE 2: 3rd Year (1st & 2nd Sem) + 4th Year (1st & 2nd Sem) + Bridging + Footer
 *
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
    // Font directory / custom font files are not needed when using
    // core FPDF fonts (Helvetica, Times, Courier, …).  Skip the
    // FPDF_FONTPATH define and strict file-existence check so that
    // PDF generation works on servers where the font/ folder was not
    // deployed (e.g. InfinityFree).

    $majorName = (string) ($student['major_name'] ?? '');
    $majorSlug = graduation_major_slug($majorName);
    $studentId = (int) ($student['id'] ?? 0);
    $slug      = graduation_student_file_slug((string) ($student['first_name'] ?? ''), (string) ($student['last_name'] ?? ''), $studentId);
    $gwaPart   = '_gwa' . number_format((float) $gwa, 2, '.', '');
    $filename  = $slug . '_' . $majorSlug . $gwaPart . '_batch' . $academicYear . '.pdf';
    $path      = graduation_build_fs_path($academicYear, $majorSlug, $filename);

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

    // ── student name / year / semester ──────────────────────────────────
    $fullName = trim(($student['last_name'] ?? '') . ', ' . ($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ?? ''));
    $standingStr = (string) ($student['year_level'] ?? '');
    $yrNum = 1; $semNum = 1;
    if (preg_match('/(\d+)(st|nd|rd|th)\s*Year/i', $standingStr, $m))  $yrNum  = (int)$m[1];
    if (preg_match('/(\d+)(st|nd|rd|th)\s*Sem/i',  $standingStr, $m2)) $semNum = (int)$m2[1];
    $yearLabels = ['1st Year','2nd Year','3rd Year','4th Year'];
    $semLabels  = ['1st Semester','2nd Semester'];
    $dispYL = $yearLabels[$yrNum - 1] ?? $standingStr;
    $dispSL = $semLabels[$semNum - 1]  ?? $semNum . 'th Semester';

    /* ═══════════════════════════════════════════════════════════════════
       COLORS
    ═══════════════════════════════════════════════════════════════════ */
    $C_GOLD_D  = [139, 105,  20];
    $C_GOLD    = [184, 134,  11];
    $C_CREAM   = [250, 247, 239];
    $C_TITLE   = [ 50,  40,  10];
    $C_SUB     = [ 90,  80,  60];
    $C_WHITE   = [255, 255, 255];

    /* ═══════════════════════════════════════════════════════════════════
       LEGAL PAPER: 215.9mm x 355.6mm
       Margins: 12mm all sides
       Usable width: 215.9 - 24 = 191.9mm ≈ 192mm
    ═══════════════════════════════════════════════════════════════════ */
    $pdf = new FPDF('P', 'mm', 'Legal');
    $marginLR = 12;
    $marginTB = 10;
    $pdf->SetMargins($marginLR, $marginTB, $marginLR);
    $pdf->SetAutoPageBreak(false);  // We control page breaks manually
    $pdf->AddPage();

    $svgL = $marginLR;
    $pageW = 215.9;
    $pageH = 355.6;
    $svgR = $pageW - $marginLR;
    $svgW = $svgR - $svgL;  // ~192mm

    /* ═══════════════════════════════════════════════════════════════════
       HELPER: Draw gold horizontal rule
    ═══════════════════════════════════════════════════════════════════ */
    $drawGoldRule = function($y, $lw = 0.4) use ($pdf, $svgL, $svgR) {
        $pdf->SetDrawColor(184, 134, 11);
        $pdf->SetLineWidth($lw);
        $pdf->Line($svgL, $y, $svgR, $y);
        return $y;
    };

    /* ═══════════════════════════════════════════════════════════════════
       §1 – SCHOOL / INSTITUTE HEADER
    ═══════════════════════════════════════════════════════════════════ */
    $logoSize = 16;
    $col1 = $logoSize + 3;
    $col3 = $logoSize + 3;
    $col2 = $svgW - $col1 - $col3;

    $y = $pdf->GetY();

     // left logo box
     $pdf->SetDrawColor(...$C_GOLD_D);
     $pdf->SetLineWidth(0.3);
     $pdf->Rect($svgL, $y, $logoSize, $logoSize, 'D');
     $logoPath = __DIR__ . '/../media/LOGO.jpg';
     if (is_file($logoPath)) {
         $pdf->Image($logoPath, $svgL + 0.5, $y + 0.5, $logoSize - 1, $logoSize - 1);
     }

    // title block
    $tx = $svgL + $col1;
    $pdf->SetXY($tx, $y + 0.5);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->SetTextColor(...$C_TITLE);
    $pdf->Cell($col2, 5, graduation_pdf_text($school), 0, 1, 'C');
    $pdf->SetX($tx);
    $pdf->SetFont('Helvetica', 'I', 8);
    $pdf->SetTextColor(...$C_SUB);
    if ($addr !== '') {
        $pdf->Cell($col2, 4, graduation_pdf_text($addr), 0, 1, 'C');
        $pdf->SetX($tx);
    }
    // hairline divider
    $pdf->SetDrawColor(...$C_GOLD_D);
    $pdf->SetLineWidth(0.2);
    $midX = $tx + $col2 / 2;
    $pdf->Line($midX - 22, $pdf->GetY() + 0.3, $midX + 22, $pdf->GetY() + 0.3);
    $pdf->Ln(1);
    $pdf->SetX($tx);
    $pdf->SetFont('Helvetica', 'B', 9.5);
    $pdf->SetTextColor(...$C_GOLD_D);
    $pdf->Cell($col2, 4.5, graduation_pdf_text($inst), 0, 1, 'C');
    $pdf->SetX($tx);
    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetTextColor(...$C_TITLE);
    $pdf->Cell($col2, 4, graduation_pdf_text($degree), 0, 1, 'C');
    $pdf->SetX($tx);
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(...$C_GOLD_D);
    $pdf->Cell($col2, 4.5, 'Major in ' . graduation_pdf_text($majorName), 0, 1, 'C');
    $pdf->SetX($tx);
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(...$C_TITLE);
    $pdf->Cell($col2, 4.5, 'Student Evaluation Prospectus (Official Copy)', 0, 1, 'C');

     // right logo box
     $rx = $svgR - $logoSize;
     $pdf->SetDrawColor(...$C_GOLD_D);
     $pdf->SetLineWidth(0.3);
     $pdf->Rect($rx, $y, $logoSize, $logoSize, 'D');
     $nbscLogo = __DIR__ . '/../media/nbsc_logo.png';
     if (is_file($nbscLogo)) {
         $pdf->Image($nbscLogo, $rx + 0.5, $y + 0.5, $logoSize - 1, $logoSize - 1);
     }

    $yBotHeader = max($pdf->GetY(), $y + $logoSize) + 1;

    // outer header border
    $pdf->SetDrawColor(...$C_GOLD_D);
    $pdf->SetLineWidth(0.5);
    $pdf->Rect($svgL, $y, $svgW, $yBotHeader - $y);
    $drawGoldRule($y, 1.0);

    /* ═══════════════════════════════════════════════════════════════════
       §2 – GRADUATED BANNER + STUDENT INFO STRIP
    ═══════════════════════════════════════════════════════════════════ */
    $pdf->SetY($yBotHeader);
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(...$C_WHITE);
    $pdf->SetFillColor(...$C_GOLD_D);
    $pdf->Cell($svgW, 5.5, "  GRADUATED -- Student Evaluation is Finalized. All records are read-only.", 0, 1, 'L', true);
    $drawGoldRule($pdf->GetY(), 0.3);

    // student info strip
    $siH = 7.5;
    $siY = $pdf->GetY() + 0.3;
    $pdf->SetFillColor(250, 247, 239);
    $pdf->SetDrawColor(...$C_GOLD_D);
    $pdf->SetLineWidth(0.2);
    $pdf->Rect($svgL, $siY, $svgW, $siH);
    $pdf->SetDrawColor(204, 200, 188);
    $col4 = $svgW / 4;
    for ($cv = 1; $cv < 4; $cv++) {
        $gx = $svgL + $cv * $col4;
        $pdf->Line($gx, $siY, $gx, $siY + $siH);
    }
    $siItems = [
        ['Student',      $fullName],
        ['Student No.',  (string)($student['student_id'] ?? $student['student_number'] ?? '—')],
        ['Year Level',   $dispYL],
        ['Semester',     $dispSL],
    ];
    foreach ($siItems as $idx => [$lbl, $val]) {
        $cx = $svgL + $idx * $col4 + 1;
        $pdf->SetXY($cx, $siY + 0.8);
        $pdf->SetFont('Helvetica', '', 6.5);
        $pdf->SetTextColor(130, 100, 50);
        $pdf->Cell($col4 - 2, 3, $lbl . ':');
        $pdf->SetXY($cx, $siY + 0.8 + 3);
        $pdf->SetFont('Helvetica', 'B', 7.5);
        $pdf->SetTextColor(30, 25, 15);
        $pdf->Cell($col4 - 2, 3.5, graduation_pdf_text($val));
    }
    $pdf->SetTextColor(0, 0, 0);
    $drawGoldRule($siY + $siH + 0.5, 0.5);
    $pdf->SetY($siY + $siH + 2.5);

    /* ═══════════════════════════════════════════════════════════════════
       §3 – SUBJECT TABLES
       
       Layout: Full-width tables (no side-by-side columns to avoid overlap)
       Each year level card contains:
         - Year header bar (gold background)
         - 1st Semester table (full width)
         - 2nd Semester table (full width)
       
       Page 1: Header + 1st Year + 2nd Year
       Page 2: 3rd Year + 4th Year + Bridging + Footer
    ═══════════════════════════════════════════════════════════════════ */

    // ── build sorted subject list with attached grades ───────────────────
    $GRADE_ORDER = ['Bridging','1st Year','2nd Year','3rd Year','4th Year'];
    $SEM_ORDER   = ['1st Semester','2nd Semester'];

    $subjectsWithGrade = array_map(function ($sub) use ($gradeMap, $GRADE_ORDER, $SEM_ORDER) {
        $sid = (int) ($sub['subject_id'] ?? 0);
        $g   = $gradeMap[$sid] ?? null;
        $sub['_grade']    = $g;
        $sub['_year_ord'] = array_search(trim((string)($sub['year_level'] ?? '')), $GRADE_ORDER);
        $sub['_sem_ord']  = array_search(trim((string)($sub['semester']   ?? '')), $SEM_ORDER);
        if ($sub['_year_ord'] === false) $sub['_year_ord'] = 999;
        if ($sub['_sem_ord']  === false) $sub['_sem_ord']  = 999;
        return $sub;
    }, $subjects);

    usort($subjectsWithGrade, function ($a, $b) {
        if ($a['_year_ord'] !== $b['_year_ord']) return $a['_year_ord'] <=> $b['_year_ord'];
        if ($a['_sem_ord']  !== $b['_sem_ord'])  return $a['_sem_ord']  <=> $b['_sem_ord'];
        return (int)($a['sort_order'] ?? 0) <=> (int)($b['sort_order'] ?? 0);
    });

    // ── group by year_level ──────────────────────────────────────────────
    $byYear = [];
    foreach ($subjectsWithGrade as $sub) {
        $yr = trim((string)($sub['year_level'] ?? 'Other'));
        $byYear[$yr][] = $sub;
    }

    // ── PDF table column widths (full width = $svgW ≈ 192mm) ─────────────
    // [code, desc, units, prereq, grade]
    $colCode   = 28;
    $colDesc   = 108;
    $colUnits  = 16;
    $colPre    = 18;
    $colGrade  = $svgW - $colCode - $colDesc - $colUnits - $colPre;  // remainder
    $cw        = [$colCode, $colDesc, $colUnits, $colPre, $colGrade];

    $semHdrH   = 5.0;
    $thH       = 5.5;
    $rowH      = 5.0;

    $sumUnitsAll  = 0.0;
    $sumPassedAll = 0.0;
    $semFill = false;

    /* ── drawSemTable: draws a full-width semester table ─────────────── */
    $drawSemTable = function (array $subs, string $label) use (
        $pdf, $cw, $svgW, $svgL, $semHdrH, $thH, $rowH, &$semFill, &$sumUnitsAll, &$sumPassedAll, $C_GOLD_D
    ) {
        if (empty($subs)) return;

        // semester label strip
        $pdf->SetX($svgL);
        $pdf->SetFillColor(247, 245, 239);
        $pdf->SetDrawColor(184, 134, 11);
        $pdf->SetLineWidth(0.15);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetTextColor(139, 105, 20);
        $pdf->Cell($svgW, $semHdrH, '  ' . $label, 1, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);

        // column header
        $pdf->SetX($svgL);
        $pdf->SetFillColor(240, 236, 224);
        $pdf->SetFont('Helvetica', 'B', 6.5);
        $thead = ['CODE', 'DESCRIPTION', 'UNIT', 'PRE-REQ', 'GRADE'];
        foreach ($thead as $ci => $h) {
            $pdf->Cell($cw[$ci], $thH, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // subject rows
        $pdf->SetFont('Helvetica', '', 6.5);
        foreach ($subs as $sub) {
            $g     = $sub['_grade'] ?? null;
            $gStr  = $g !== null ? number_format($g, 2) : '';
            $code  = graduation_pdf_text(trim((string)($sub['subject_code'] ?? '')));
            $name  = graduation_pdf_text((string)($sub['subject_name'] ?? ''));
            if (strlen($name) > 65) $name = substr($name, 0, 62) . '...';
            $units = (float) ($sub['units'] ?? 0);
            $sumUnitsAll += $units;
            if ($g !== null && $g <= 3.0) $sumPassedAll += $units;

            // prereq
            $prereqCodes = [];
            if (!empty($sub['prereq_codes']) && is_array($sub['prereq_codes'])) {
                $prereqCodes = $sub['prereq_codes'];
            } elseif (!empty($sub['prerequisite_code'])) {
                $prereqCodes = [$sub['prerequisite_code']];
            }
            $preStr = $prereqCodes ? graduation_pdf_text(implode(', ', $prereqCodes)) : '';

            // grade colour
            $gColor = [0, 0, 0];
            if ($g !== null) {
                if ($g <= 3.0)      $gColor = [22, 101, 52];
                elseif ($g <= 4.0)  $gColor = [180, 83,  9];
                else                $gColor = [180, 30, 30];
            }

            // alternating fill
            $fillRow = $semFill ? [237, 230, 210] : [255, 253, 248];
            $semFill = !$semFill;

            $pdf->SetX($svgL);
            $pdf->SetFillColor(...$fillRow);
            $pdf->SetFont('Helvetica', '', 6.5);
            $pdf->Cell($cw[0], $rowH, ' ' . $code, 1, 0, 'L', true);
            $pdf->Cell($cw[1], $rowH, ' ' . $name, 1, 0, 'L', true);
            $pdf->Cell($cw[2], $rowH, number_format($units, 1), 1, 0, 'C', true);
            $pdf->SetFont('Helvetica', '', 5.5);
            $pdf->Cell($cw[3], $rowH, $preStr, 1, 0, 'C', true);
            $pdf->SetFont('Helvetica', '', 6.5);
            $pdf->SetTextColor(...$gColor);
            $pdf->Cell($cw[4], $rowH, $gStr, 1, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0);
        }
    };

    /* ── drawYearBlock: draws a complete year level block ─────────────── */
    $drawYearBlock = function (string $yr, array $yearSubs) use (
        $pdf, $svgL, $svgW, $drawSemTable, $drawGoldRule, $C_GOLD_D, $C_WHITE, $rowH, $thH, $semHdrH
    ) {
        $sem1 = array_values(array_filter($yearSubs, fn($s) => stripos($s['semester'] ?? '', '1st') !== false));
        $sem2 = array_values(array_filter($yearSubs, fn($s) => stripos($s['semester'] ?? '', '2nd') !== false));

        $nSubs  = count($yearSubs);
        $yUnits = array_sum(array_map(fn($s) => (float)($s['units'] ?? 0), $yearSubs));

        // Year header bar
        $pdf->SetX($svgL);
        $pdf->SetFillColor(...$C_GOLD_D);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 8);
        $hdrTxt = sprintf(' %s  --  %d Subject%s  --  %.1f units',
            strtoupper($yr), $nSubs, $nSubs !== 1 ? 's' : '', $yUnits);
        $pdf->Cell($svgW, 5.5, $hdrTxt, 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);

        // 1st Semester table
        $lbl1 = $yr . ' - First Semester';
        $drawSemTable($sem1, $lbl1);

        // Small gap between semesters
        if (!empty($sem1) && !empty($sem2)) {
            $pdf->Ln(1.5);
        }

        // 2nd Semester table
        $lbl2 = $yr . ' - Second Semester';
        $drawSemTable($sem2, $lbl2);

        // Gap after year block
        $pdf->Ln(3);
    };

    /* ═══════════════════════════════════════════════════════════════════
       PAGE 1 CONTENT: 1st Year + 2nd Year
       (Bridging goes on page 2 with 3rd/4th year)
    ═══════════════════════════════════════════════════════════════════ */

    // Render 1st Year
    if (isset($byYear['1st Year'])) {
        $drawYearBlock('1st Year', $byYear['1st Year']);
    }

    // Render 2nd Year
    if (isset($byYear['2nd Year'])) {
        $drawYearBlock('2nd Year', $byYear['2nd Year']);
    }

    /* ═══════════════════════════════════════════════════════════════════
       PAGE 2: 3rd Year + 4th Year + Bridging + Footer
    ═══════════════════════════════════════════════════════════════════ */
    $pdf->AddPage();

    // Render 3rd Year
    if (isset($byYear['3rd Year'])) {
        $drawYearBlock('3rd Year', $byYear['3rd Year']);
    }

    // Render 4th Year
    if (isset($byYear['4th Year'])) {
        $drawYearBlock('4th Year', $byYear['4th Year']);
    }

    // Render Bridging (if exists)
    if (isset($byYear['Bridging'])) {
        $drawYearBlock('Bridging', $byYear['Bridging']);
    }

    /* ═══════════════════════════════════════════════════════════════════
       §4 – GRAND TOTALS BAR
    ═══════════════════════════════════════════════════════════════════ */
    $pdf->Ln(3);
    $nSubsAll  = count($subjectsWithGrade);
    $totTxt = sprintf(
        ' %d Subject%s  ·  %.1f Total Units   |   Final GWA: %s',
        $nSubsAll, $nSubsAll !== 1 ? 's' : '',
        $sumUnitsAll, number_format($gwa, 4)
    );
    $pdf->SetX($svgL);
    $pdf->SetFillColor(247, 245, 239);
    $pdf->SetDrawColor(184, 134, 11);
    $pdf->SetLineWidth(0.4);
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(139, 105, 20);
    $pdf->Cell($svgW, 7, $totTxt, 1, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(28); // Increased space to move signature block down by ~20px and removed student info strip

    /* ═════════════════════════════════════════════════════════════════════
       §6 – SIGNATURE BLOCK (3-column grid)
    ═════════════════════════════════════════════════════════════════════ */

    /* ═══════════════════════════════════════════════════════════════════
       §6 – SIGNATURE BLOCK (3-column grid)
    ═══════════════════════════════════════════════════════════════════ */
    $third = $svgW / 3;
    $sigCols = [
        [graduation_pdf_text($fullName), "Student's Signature"],
        [graduation_pdf_text($advisorName    ?? ''), "Adviser's Signature"],
        [graduation_pdf_text($programHeadName ?? ''), "Program Head's Signature"],
    ];

    // signature names with underline
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $sigY = $pdf->GetY();
    foreach ($sigCols as $i => [$name, $role]) {
        $cx = $svgL + $i * $third;
        $pdf->SetXY($cx + 2, $sigY);
        $pdf->Cell($third - 4, 5, $name, 'B', 0, 'C');
    }
    $pdf->Ln(3);

    // role labels
    $pdf->SetFont('Helvetica', '', 7);
    $roleY = $pdf->GetY();
    foreach ($sigCols as $i => [$name, $role]) {
        $cx = $svgL + $i * $third;
        $pdf->SetXY($cx, $roleY);
        $pdf->Cell($third, 4, $role, 0, 0, 'C');
    }
    $pdf->Ln(12);

    /* ═══════════════════════════════════════════════════════════════════
       §7 – LEGEND
    ═══════════════════════════════════════════════════════════════════ */
    $pdf->SetX($svgL);
    $pdf->SetFont('Helvetica', '', 6.5);
    $pdf->SetTextColor(160, 150, 140);
    $pdf->Cell($svgW, 4, 'Dark gold row = Prerequisite-locked subject (cannot be evaluated until the prerequisite is passed)', 0, 1);
    $pdf->SetTextColor(0, 0, 0);

    try {
        $pdf->Output('F', $path);
    } catch (Throwable $e) {
        $errorOut = $e->getMessage();
        return null;
    }

    // ── save JSON sidecar ──
    try {
        $meta = [
            'student_name'     => graduation_pdf_text(trim(($student['first_name'] ?? '').' '.($student['last_name'] ?? ''))),
            'major_name'       => $majorName,
            'batch_year'       => $academicYear,
            'gwa'              => $gwa,
            'graduation_date'  => $graduationDateYmd,
            'total_subjects'   => count($subjectsWithGrade),
            'units_passed'     => round($sumPassedAll, 2),
            'document_title'   => 'Student Evaluation Prospectus (Official Copy)',
            'student_number'   => (string) ($student['student_id'] ?? $student['id'] ?? ''),
        ];
        @file_put_contents($path . '.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } catch (Throwable) {
    }

    return $path;
}
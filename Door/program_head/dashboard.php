<?php
require_once '../../data/session_security.php';
check_auth('program_head', '../login.php');
require_once '../../data/config.php';

$user_name = $_SESSION['user_name'] ?? 'Program Head';

// Fetch stats with error handling
$total_instructors = 0;
$completed_evaluations = 0;
$active_courses = 0;
$avg_rating = 0;
$total_students = 0;

// Get total instructors
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM instructors");
    $result = $stmt->fetch();
    $total_instructors = $result['cnt'] ?? 0;
} catch (PDOException $e) {
    $total_instructors = 0;
}

// Get evaluation stats
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'evaluations'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM evaluations WHERE status = 'completed'");
        $result = $stmt->fetch();
        $completed_evaluations = $result['cnt'] ?? 0;
        
        $stmt = $pdo->query("SELECT COALESCE(AVG(rating),0) as avg_r FROM evaluations");
        $result = $stmt->fetch();
        $avg_rating = round($result['avg_r'], 1);
    }
} catch (PDOException $e) {
    $completed_evaluations = 0;
    $avg_rating = 0;
}

// Get active courses
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'courses'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM courses WHERE status = 'active'");
        $result = $stmt->fetch();
        $active_courses = $result['cnt'] ?? 0;
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM majors WHERE is_active = 1");
        $result = $stmt->fetch();
        $active_courses = $result['cnt'] ?? 0;
    }
} catch (PDOException $e) {
    $active_courses = 0;
}

// Get total students
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM students");
    $result = $stmt->fetch();
    $total_students = $result['cnt'] ?? 0;
} catch (PDOException $e) {
    $total_students = 0;
}

// Get instructor status counts
$on_duty = 0; $on_leave = 0; $on_travel = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM instructors WHERE status = 'on duty'");
    $on_duty = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM instructors WHERE status = 'on leave'");
    $on_leave = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM instructors WHERE status = 'on travel'");
    $on_travel = $stmt->fetchColumn() ?: 0;
} catch (PDOException $e) {
    $on_duty = 0; $on_leave = 0; $on_travel = 0;
}

// Fetch recent evaluations
$recent_evaluations = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'evaluations'");
    if ($stmt->rowCount() > 0) {
        $sql = "SELECT CONCAT(i.first_name, ' ', i.last_name) as instructor_name, 
                       c.course_name, e.rating, e.evaluation_date 
                FROM evaluations e 
                JOIN instructors i ON e.instructor_id = i.id 
                JOIN courses c ON e.course_id = c.id 
                ORDER BY e.evaluation_date DESC LIMIT 5";
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $recent_evaluations[] = $row;
        }
    }
} catch (PDOException $e) {
    $recent_evaluations = [];
}

// Fetch department performance
$dept_performance = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'evaluations'");
    if ($stmt->rowCount() > 0) {
        $sql = "SELECT COALESCE(e.department, 'General') as department, 
                       COALESCE(AVG(e.rating),0) as avg_rating 
                FROM evaluations e 
                GROUP BY e.department 
                ORDER BY avg_rating DESC";
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dept_performance[] = $row;
        }
    }
} catch (PDOException $e) {
    $dept_performance = [];
}

// Fetch majors for department overview
$majors = [];
try {
    $stmt = $pdo->query("SELECT id, major_name, description, is_active FROM majors ORDER BY major_name");
    $majors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $majors = [];
}

// Fetch students by year (combine 3rd Year variations)
$yearLevels = [];
try {
    $stmt = $pdo->query("SELECT year_level, COUNT(*) as count FROM students WHERE year_level IS NOT NULL AND year_level != '' GROUP BY year_level ORDER BY year_level");
    $raw_year_levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $yearLevels = [];
    foreach ($raw_year_levels as $year) {
        $base_year = $year['year_level'];
        if (strpos($base_year, '3rd Year') !== false) {
            $base_year = '3rd Year';
        }
        
        if (isset($yearLevels[$base_year])) {
            $yearLevels[$base_year] += $year['count'];
        } else {
            $yearLevels[$base_year] = $year['count'];
        }
    }
    
    $year_order = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
    $sorted_yearLevels = [];
    foreach ($year_order as $year) {
        if (isset($yearLevels[$year])) {
            $sorted_yearLevels[] = [
                'year_level' => $year,
                'count' => $yearLevels[$year]
            ];
        }
    }
    $yearLevels = $sorted_yearLevels;
} catch (PDOException $e) {
    $yearLevels = [];
}

// Fetch recent instructor activities
$recent_instructors = [];
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name, status, email FROM instructors ORDER BY id DESC LIMIT 4");
    $recent_instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_instructors = [];
}

    // Calculate evaluation completion rate
    $eval_completion_rate = $total_instructors > 0 ? round(($completed_evaluations / max($total_instructors, 1)) * 100) : 0;
    $eval_completion_rate = min($eval_completion_rate, 100);

    // Fetch upcoming events grouped by month
    $upcoming_events_by_month = [];
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'calendar_events'");
        if ($stmt->rowCount() > 0) {
            // Fetch events from today onwards, ordered by date
            $today = date('Y-m-d');
            $sql = "SELECT ce.id, ce.title, ce.description, ce.event_date,
                           GROUP_CONCAT(ei.instructor_id) as instructor_ids
                    FROM calendar_events ce
                    LEFT JOIN event_instructors ei ON ce.id = ei.event_id
                    WHERE ce.event_date >= :today
                    GROUP BY ce.id
                    ORDER BY ce.event_date ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':today' => $today]);
            $all_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group events by month/year
            foreach ($all_events as $event) {
                $date = new DateTime($event['event_date']);
                $month_year_key = $date->format('F Y');
                $month_key = $date->format('Y-m');

                // Process instructor_ids into array
                $event['instructor_ids'] = $event['instructor_ids']
                    ? array_map('intval', explode(',', $event['instructor_ids']))
                    : [];

                // Get instructor names for this event
                if (!empty($event['instructor_ids'])) {
                    $placeholders = implode(',', array_fill(0, count($event['instructor_ids']), '?'));
                    $stmt2 = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM instructors WHERE id IN ($placeholders)");
                    $stmt2->execute($event['instructor_ids']);
                    $instructors = $stmt2->fetchAll(PDO::FETCH_COLUMN);
                    $event['instructor_names'] = $instructors;
                } else {
                    $event['instructor_names'] = [];
                }

                $upcoming_events_by_month[$month_key]['month_name'] = $month_year_key;
                $upcoming_events_by_month[$month_key]['events'][] = $event;
            }

            // Sort by month (chronological)
            ksort($upcoming_events_by_month);
        }
    } catch (PDOException $e) {
        $upcoming_events_by_month = [];
    }

    // Fetch all events for current month
    $current_month_events = [];
    $current_month = date('Y-m');
    $current_month_name = date('F Y');
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'calendar_events'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT ce.id, ce.title, ce.description, ce.event_date,
                       GROUP_CONCAT(ei.instructor_id) as instructor_ids
                FROM calendar_events ce
                LEFT JOIN event_instructors ei ON ce.id = ei.event_id
                WHERE DATE_FORMAT(ce.event_date, '%Y-%m') = :month
                GROUP BY ce.id
                ORDER BY ce.title ASC, ce.event_date ASC
            ");
            $stmt->execute([':month' => $current_month]);
            $month_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group events by title and combine dates
            $grouped_events = [];
            foreach ($month_events as $event) {
                $event['instructor_ids'] = $event['instructor_ids']
                    ? array_map('intval', explode(',', $event['instructor_ids']))
                    : [];

                if (!empty($event['instructor_ids'])) {
                    $placeholders = implode(',', array_fill(0, count($event['instructor_ids']), '?'));
                    $stmt2 = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM instructors WHERE id IN ($placeholders)");
                    $stmt2->execute($event['instructor_ids']);
                    $instructors = $stmt2->fetchAll(PDO::FETCH_COLUMN);
                    $event['instructor_names'] = $instructors;
                } else {
                    $event['instructor_names'] = [];
                }

                // Use title as the key to group events
                $title_key = strtolower(trim($event['title']));
                if (!isset($grouped_events[$title_key])) {
                    $grouped_events[$title_key] = [
                        'id' => $event['id'],
                        'title' => $event['title'],
                        'description' => $event['description'],
                        'dates' => [],
                        'instructor_names' => $event['instructor_names']
                    ];
                }
                
                $grouped_events[$title_key]['dates'][] = $event['event_date'];
            }

            // Convert to array and remove duplicate dates for each event
            foreach ($grouped_events as $key => $event) {
                $event['dates'] = array_unique($event['dates']);
                sort($event['dates']);
                $current_month_events[] = $event;
            }
        }
    } catch (PDOException $e) {
        $current_month_events = [];
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="icon" href="../../media/LOGO.jpg" type="image/jpeg">
    <title>Dashboard - Program Head</title>
    <link rel="stylesheet" href="../../css/common.css">
    <link rel="stylesheet" href="./style/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
    /* ==========================================
       CALENDAR EVENT INDICATORS
       ========================================== */
    .calendar-day {
        position: relative;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 38px;
        transition: all 0.2s ease;
    }
    .calendar-day .day-number {
        line-height: 1;
        font-size: 13px;
        font-weight: 500;
    }
    .calendar-day:hover {
        background: rgba(79, 70, 229, 0.1);
        border-radius: 8px;
    }
    .calendar-day.has-event {
        background: rgba(79, 70, 229, 0.08);
        border-radius: 8px;
    }
    .calendar-day.has-event .day-number {
        font-weight: 700;
        color: #4f46e5;
    }
    .calendar-day.today.has-event .day-number {
        color: #fff;
    }
    .event-indicator {
        display: flex;
        align-items: center;
        gap: 2px;
        margin-top: 2px;
    }
    .event-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: #4f46e5;
        display: inline-block;
    }
    .calendar-day.today .event-dot {
        background: #fff;
    }
    .event-count {
        font-size: 9px;
        font-weight: 700;
        color: #4f46e5;
        margin-left: 1px;
    }
    .calendar-day.today .event-count {
        color: #fff;
    }

    /* ==========================================
       MULTI-EVENT INDICATOR STYLES
       ========================================== */
    .calendar-day.has-multiple-events {
        background: rgba(245, 158, 11, 0.12);
        border-radius: 8px;
    }
    .calendar-day.has-multiple-events .day-number {
        font-weight: 700;
        color: #d97706;
    }
    .calendar-day.today.has-multiple-events {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
    }
    .calendar-day.today.has-multiple-events .day-number {
        color: #fff;
    }
    .calendar-day.has-multiple-events .event-dot {
        background: #d97706;
    }
    .calendar-day.today.has-multiple-events .event-dot {
        background: #fff;
    }
    .calendar-day.has-multiple-events .event-count {
        color: #d97706;
    }
    .calendar-day.today.has-multiple-events .event-count {
        color: #fff;
    }
    .multi-event-badge {
        position: absolute;
        top: 1px;
        right: 1px;
        background: #f59e0b;
        color: #fff;
        font-size: 8px;
        font-weight: 800;
        min-width: 16px;
        height: 16px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        padding: 0 3px;
        box-shadow: 0 1px 3px rgba(245, 158, 11, 0.4);
    }
    .calendar-day.today .multi-event-badge {
        background: #fff;
        color: #4f46e5;
        box-shadow: 0 1px 3px rgba(255, 255, 255, 0.4);
    }

    /* ==========================================
       EVENT MODAL OVERLAY
       ========================================== */
    .event-modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.2s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .event-modal {
        background: #fff;
        border-radius: 16px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s ease;
    }
    .event-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        border-radius: 16px 16px 0 0;
    }
    .event-modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .event-modal-header.view-header {
        background: linear-gradient(135deg, #059669, #10b981);
    }
    .event-modal-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: #fff;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        font-size: 18px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }
    .event-modal-close:hover {
        background: rgba(255, 255, 255, 0.35);
    }
    .event-modal-body {
        padding: 24px;
    }
    .event-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 16px 24px;
        border-top: 1px solid #e5e7eb;
        background: #f9fafb;
        border-radius: 0 0 16px 16px;
    }

    /* ==========================================
       EVENT FORM STYLES
       ========================================== */
    .event-form-group {
        margin-bottom: 18px;
    }
    .event-form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }
    .event-form-group label i {
        color: #4f46e5;
        margin-right: 4px;
        width: 16px;
    }
    .required {
        color: #ef4444;
    }
    .event-form-group input[type="text"],
    .event-form-group textarea {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
        transition: border-color 0.2s, box-shadow 0.2s;
        background: #fff;
        color: #1f2937;
        box-sizing: border-box;
    }
    .event-form-group input[type="text"]:focus,
    .event-form-group textarea:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }
    .event-date-display {
        background: #f3f4f6 !important;
        color: #6b7280 !important;
        cursor: default !important;
        font-weight: 500 !important;
    }

    /* ==========================================
       MULTI-DATE PICKER STYLES
       ========================================== */
    .multi-date-picker-container {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }
    .date-picker-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
    }
    .date-picker-header span {
        font-size: 13px;
        font-weight: 600;
    }
    .date-picker-nav {
        background: rgba(255,255,255,0.2);
        border: none;
        color: #fff;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        transition: background 0.2s;
    }
    .date-picker-nav:hover {
        background: rgba(255,255,255,0.35);
    }
    .date-picker-day-names {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        padding: 6px 8px 2px;
        background: #f9fafb;
    }
    .date-picker-day-names span {
        text-align: center;
        font-size: 10px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
    }
    .date-picker-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        padding: 4px 8px 8px;
        gap: 2px;
    }
    .dp-day {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        margin: 0 auto;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s ease;
        color: #1f2937;
        user-select: none;
    }
    .dp-day:hover {
        background: #eef2ff;
        color: #4f46e5;
    }
    .dp-day.empty {
        cursor: default;
    }
    .dp-day.empty:hover {
        background: transparent;
    }
    .dp-day.selected {
        background: #4f46e5;
        color: #fff;
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.35);
    }
    .dp-day.selected:hover {
        background: #4338ca;
        color: #fff;
    }
    .dp-day.today {
        border: 2px solid #4f46e5;
        font-weight: 700;
    }
    .dp-day.today.selected {
        border-color: #fff;
        box-shadow: 0 0 0 2px #4f46e5, 0 2px 6px rgba(79, 70, 229, 0.35);
    }
    .dp-day.other-month {
        color: #d1d5db;
    }
    .dp-day.other-month:hover {
        background: #f9fafb;
        color: #9ca3af;
    }

    /* Selected dates chips */
    .selected-dates-section {
        padding: 10px 12px;
        border-top: 1px solid #e5e7eb;
        background: #f9fafb;
        min-height: 44px;
    }
    .selected-dates-label {
        font-size: 11px;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .selected-dates-label i {
        color: #4f46e5;
    }
    .selected-dates-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .date-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        background: #eef2ff;
        color: #4f46e5;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #c7d2fe;
        animation: chipIn 0.2s ease;
    }
    @keyframes chipIn {
        from { transform: scale(0.8); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .date-chip-remove {
        background: none;
        border: none;
        color: #4f46e5;
        cursor: pointer;
        font-size: 14px;
        line-height: 1;
        padding: 0;
        display: flex;
        align-items: center;
        opacity: 0.6;
        transition: opacity 0.15s;
    }
    .date-chip-remove:hover {
        opacity: 1;
        color: #ef4444;
    }
    .no-dates-hint {
        font-size: 12px;
        color: #d1d5db;
        font-style: italic;
    }

    /* Multi-date display in view modal */
    .event-view-dates {
        margin-bottom: 16px;
    }
    .view-dates-label {
        font-size: 12px;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .view-dates-label i {
        color: #4f46e5;
    }
    .view-dates-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .view-date-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background: #eef2ff;
        color: #4f46e5;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
        border: 1px solid #c7d2fe;
    }
    .view-date-badge i {
        font-size: 11px;
    }
    .view-date-range {
        font-size: 13px;
        color: #6b7280;
        background: #f3f4f6;
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 500;
    }

    /* ==========================================
       INSTRUCTOR CHECKBOX LIST
       ========================================== */
    .instructor-checkbox-list {
        max-height: 180px;
        overflow-y: auto;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 6px;
        background: #fff;
    }
    .instructor-checkbox-list::-webkit-scrollbar {
        width: 6px;
    }
    .instructor-checkbox-list::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 3px;
    }
    .instructor-checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.15s;
        user-select: none;
    }
    .instructor-checkbox-item:hover {
        background: #f3f4f6;
    }
    .instructor-checkbox-item.checked {
        background: #eef2ff;
    }
    .inst-checkbox {
        display: none;
    }
    .inst-checkmark {
        width: 20px;
        height: 20px;
        border: 2px solid #d1d5db;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    .inst-checkmark i {
        font-size: 10px;
        color: #fff;
        opacity: 0;
        transition: opacity 0.15s;
    }
    .instructor-checkbox-item.checked .inst-checkmark {
        background: #4f46e5;
        border-color: #4f46e5;
    }
    .instructor-checkbox-item.checked .inst-checkmark i {
        opacity: 1;
    }
    .inst-name {
        flex: 1;
        font-size: 13px;
        font-weight: 500;
        color: #1f2937;
    }
    .inst-status {
        font-size: 11px;
        color: #9ca3af;
        background: #f3f4f6;
        padding: 2px 8px;
        border-radius: 10px;
    }
    .instructor-checkbox-item.checked .inst-status {
        background: #e0e7ff;
        color: #4f46e5;
    }
    .loading-instructors,
    .no-instructors {
        text-align: center;
        padding: 20px;
        color: #9ca3af;
        font-size: 13px;
    }
    .loading-instructors i,
    .no-instructors i {
        display: block;
        font-size: 24px;
        margin-bottom: 8px;
    }

    /* ==========================================
       EVENT BUTTONS
       ========================================== */
    .event-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        font-family: 'Poppins', sans-serif;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .event-btn-save {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
    }
    .event-btn-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
    }
    .event-btn-save:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    .event-btn-cancel {
        background: #f3f4f6;
        color: #6b7280;
    }
    .event-btn-cancel:hover {
        background: #e5e7eb;
    }
    .event-btn-edit {
        background: linear-gradient(135deg, #2563eb, #3b82f6);
        color: #fff;
    }
    .event-btn-edit:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
    }
    .event-btn-delete {
        background: #fef2f2;
        color: #ef4444;
        border: 1px solid #fecaca;
    }
    .event-btn-delete:hover {
        background: #ef4444;
        color: #fff;
    }
    .event-btn-add {
        background: linear-gradient(135deg, #059669, #10b981);
        color: #fff;
    }
    .event-btn-add:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.4);
    }

    /* ==========================================
       EVENT VIEW MODAL
       ========================================== */
    .event-view-modal .event-modal-body {
        padding: 24px;
    }
    .event-view-date {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .event-view-date i {
        color: #4f46e5;
    }
    .event-view-title {
        font-size: 22px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 16px;
        line-height: 1.3;
    }
    .view-instructors-label,
    .view-desc-label {
        font-size: 12px;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .view-instructors-label i,
    .view-desc-label i {
        color: #4f46e5;
    }
    .view-instructors-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }
    .view-instructor-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background: #eef2ff;
        color: #4f46e5;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
    }
    .view-instructor-badge i {
        font-size: 11px;
    }
    .view-desc-text {
        font-size: 14px;
        color: #4b5563;
        line-height: 1.7;
        background: #f9fafb;
        padding: 14px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
    }
    .view-desc-text.empty-desc {
        color: #9ca3af;
        font-style: italic;
    }

    /* ==========================================
       DAY EVENTS POPUP
       ========================================== */
    .day-events-popup-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.3);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .day-events-popup {
        background: #fff;
        border-radius: 16px;
        width: 90%;
        max-width: 380px;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
        animation: slideUp 0.25s ease;
        overflow: hidden;
    }
    .day-events-popup-header {
        padding: 16px 20px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
    }
    .day-events-popup-header h4 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .day-events-popup-list {
        padding: 8px;
    }
    .day-event-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 10px;
        cursor: pointer;
        transition: background 0.15s;
    }
    .day-event-item:hover {
        background: #f3f4f6;
    }
    .day-event-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #4f46e5;
        flex-shrink: 0;
    }
    .day-event-info {
        flex: 1;
    }
    .day-event-title {
        font-size: 13px;
        font-weight: 500;
        color: #1f2937;
    }
    .day-event-arrow {
        color: #d1d5db;
        font-size: 12px;
    }
    .day-event-add-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        width: calc(100% - 16px);
        margin: 4px 8px 12px;
        padding: 10px;
        background: #f3f4f6;
        border: 2px dashed #d1d5db;
        border-radius: 10px;
        color: #6b7280;
        font-size: 13px;
        font-weight: 500;
        font-family: 'Poppins', sans-serif;
        cursor: pointer;
        transition: all 0.2s;
    }
    .day-event-add-btn:hover {
        background: #eef2ff;
        border-color: #4f46e5;
        color: #4f46e5;
    }

    /* ==========================================
       UPCOMING EVENTS BY MONTH
       ========================================== */
    .upcoming-events-section {
        margin-top: 28px;
    }

    .events-by-month-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 16px;
    }

    .month-events-card {
        background: var(--white);
        border-radius: 18px;
        box-shadow: var(--shadow-card);
        border: 1px solid var(--border-light);
        overflow: hidden;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .month-events-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 36px rgba(212, 168, 67, 0.15);
        border-color: var(--border-gold);
    }

    .month-card-header {
        padding: 18px 24px;
        background: linear-gradient(135deg, var(--cream) 0%, rgba(212, 168, 67, 0.06) 100%);
        border-bottom: 1px solid var(--border-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .month-title {
        font-size: 15px;
        font-weight: 800;
        color: var(--dark-text);
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
    }

    .month-title i {
        color: var(--gold-dark);
        font-size: 14px;
    }

    .month-event-count {
        font-size: 11px;
        font-weight: 700;
        color: var(--gold-dark);
        background: var(--gold-gradient-soft);
        padding: 4px 12px;
        border-radius: 12px;
        border: 1px solid var(--border-gold);
    }

    .month-card-body {
        padding: 12px;
        max-height: 400px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .month-card-body::-webkit-scrollbar {
        width: 5px;
    }

    .month-card-body::-webkit-scrollbar-track {
        background: transparent;
    }

    .month-card-body::-webkit-scrollbar-thumb {
        background: var(--border-light);
        border-radius: 3px;
    }

    .event-item-compact {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 14px;
        background: var(--cream);
        border-radius: 12px;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.25s ease;
    }

    .event-item-compact:hover {
        background: var(--white);
        border-color: var(--gold-light);
        transform: translateX(4px);
        box-shadow: 0 4px 14px rgba(184, 134, 11, 0.12);
    }

    .event-date-badge-small {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 50px;
        background: var(--gold-gradient);
        color: white;
        border-radius: 10px;
        padding: 6px 8px;
        box-shadow: 0 2px 8px rgba(184, 134, 11, 0.3);
    }

    .event-day-num {
        font-size: 18px;
        font-weight: 900;
        line-height: 1;
        color: white;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
    }

    .event-month-short {
        font-size: 10px;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.85);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 2px;
    }

    .event-details-compact {
        flex: 1;
        min-width: 0;
    }

    .event-title-compact {
        font-size: 13px;
        font-weight: 700;
        color: var(--dark-text);
        margin: 0 0 6px 0;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .event-meta-compact {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        font-size: 11px;
    }

    .event-assigned {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: var(--medium-text);
    }

    .event-assigned i {
        color: var(--gold);
        font-size: 9px;
    }

    .event-action-icon {
        color: var(--light-text);
        font-size: 12px;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .event-item-compact:hover .event-action-icon {
        color: var(--gold-dark);
        transform: translateX(4px);
    }

    .no-upcoming-events {
        grid-column: 1 / -1;
        text-align: center;
        padding: 40px 20px;
        color: var(--light-text);
        background: var(--white);
        border-radius: 16px;
        border: 2px dashed var(--border-light);
    }

    .no-upcoming-events i {
        font-size: 36px;
        margin-bottom: 12px;
        opacity: 0.3;
        color: var(--gold-light);
    }

    .no-upcoming-events p {
        font-size: 14px;
        font-weight: 600;
        margin: 0;
    }

    /* ==========================================
       CURRENT MONTH EVENTS SECTION
       ========================================== */
    .current-month-events-section {
        margin-top: 28px;
    }

    .current-month-events-grid {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 16px;
    }

    .current-month-event-card {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px 20px;
        background: var(--white);
        border-radius: 14px;
        border: 1px solid var(--border-light);
        cursor: pointer;
        transition: all 0.25s ease;
    }

    .current-month-event-card:hover {
        border-color: var(--gold);
        transform: translateX(6px);
        box-shadow: 0 4px 16px rgba(184, 134, 11, 0.15);
    }

    .current-month-event-card.past-event {
        opacity: 0.65;
    }

    .current-month-event-date {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 56px;
        background: linear-gradient(135deg, #059669, #10b981);
        color: white;
        border-radius: 12px;
        padding: 10px 8px;
        box-shadow: 0 3px 10px rgba(5, 150, 105, 0.3);
    }

    .current-month-event-card.past-event .current-month-event-date {
        background: linear-gradient(135deg, #9ca3af, #d1d5db);
        box-shadow: none;
    }

    .current-month-event-day {
        font-size: 24px;
        font-weight: 900;
        line-height: 1;
        color: white;
    }

    .current-month-event-month {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.9);
        margin-top: 2px;
    }

    .current-month-event-info {
        flex: 1;
        min-width: 0;
    }

    .current-month-event-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--dark-text);
        margin: 0 0 6px 0;
        line-height: 1.3;
    }

    .current-month-event-meta {
        display: flex;
        gap: 12px;
        font-size: 12px;
        color: var(--medium-text);
    }

    .current-month-event-instructors {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .current-month-event-instructors i {
        color: #059669;
        font-size: 11px;
    }

    .current-month-event-arrow {
        color: var(--light-text);
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .current-month-event-card:hover .current-month-event-arrow {
        color: #059669;
        transform: translateX(4px);
    }

    .no-current-month-events {
        text-align: center;
        padding: 40px 20px;
        color: var(--light-text);
        background: var(--white);
        border-radius: 16px;
        border: 2px dashed var(--border-light);
        margin-top: 16px;
    }

    .no-current-month-events i {
        font-size: 42px;
        margin-bottom: 14px;
        opacity: 0.25;
        color: #059669;
    }

    .no-current-month-events p {
        font-size: 14px;
        font-weight: 600;
        margin: 0 0 16px 0;
    }

    .current-month-events-list {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        max-height: 420px;
        overflow-y: auto;
        padding-right: 6px;
        scroll-behavior: smooth;
    }

    /* ==========================================
       CUSTOM SCROLLBAR FOR EVENTS
       ========================================== */
    .current-month-events-list::-webkit-scrollbar {
        width: 8px;
    }

    .current-month-events-list::-webkit-scrollbar-track {
        background: rgba(59, 130, 246, 0.08);
        border-radius: 10px;
        margin: 8px 0;
    }

    .current-month-events-list::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg, #3B82F6, #6366F1, #8B5CF6);
        border-radius: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 0 6px rgba(59, 130, 246, 0.3);
    }

    .current-month-events-list::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(180deg, #2563EB, #4F46E5, #7C3AED);
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
    }

    /* Firefox Scrollbar */
    .current-month-events-list {
        scrollbar-color: #3B82F6 rgba(59, 130, 246, 0.08);
        scrollbar-width: thin;
    }

    .current-month-event-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        background: var(--cream);
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .current-month-event-row:hover {
        background: var(--white);
        transform: translateX(4px);
        box-shadow: 0 2px 8px rgba(184, 134, 11, 0.12);
    }

    .current-month-event-row.past-event {
        opacity: 0.6;
    }

    .current-month-event-date-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 40px;
        background: linear-gradient(135deg, #059669, #10b981);
        color: white;
        border-radius: 8px;
        padding: 6px 10px;
    }

    .current-month-event-row.past-event .current-month-event-date-col {
        background: linear-gradient(135deg, #9ca3af, #d1d5db);
    }

    .current-month-event-day-num {
        font-size: 18px;
        font-weight: 900;
        line-height: 1;
    }

    .current-month-event-day-suffix {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .current-month-event-dates {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin: 4px 0 6px 0;
    }

    .event-date-tag {
        display: inline-block;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.1));
        border: 1px solid rgba(99, 102, 241, 0.2);
        color: #4F46E5;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
        white-space: nowrap;
        transition: all 0.2s ease;
    }

    .event-date-tag:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(139, 92, 246, 0.2));
        border-color: rgba(99, 102, 241, 0.4);
        transform: scale(1.05);
    }

    .event-date-more {
        background: linear-gradient(135deg, rgba(168, 85, 247, 0.2), rgba(139, 92, 246, 0.15));
        color: #7C3AED;
        border-color: rgba(168, 85, 247, 0.3);
        font-weight: 700;
    }

    .event-date-more:hover {
        background: linear-gradient(135deg, rgba(168, 85, 247, 0.3), rgba(139, 92, 246, 0.25));
        border-color: rgba(168, 85, 247, 0.5);
    }

    .current-month-event-details {
        flex: 1;
        min-width: 0;
    }

    .current-month-event-name {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 2px;
    }

    .current-month-event-inst {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        color: var(--medium-text);
    }

    .current-month-event-inst i {
        color: #059669;
        font-size: 10px;
    }

    .current-month-event-chevron {
        color: var(--light-text);
        font-size: 11px;
    }

    .current-month-event-row:hover .current-month-event-chevron {
        color: #059669;
        transform: translateX(3px);
    }

    .no-month-events-compact {
        text-align: center;
        padding: 20px;
        color: var(--light-text);
        font-size: 13px;
    }

    .no-month-events-compact i {
        font-size: 24px;
        margin-bottom: 6px;
        opacity: 0.3;
        color: #059669;
        display: block;
    }

    /* ==========================================
       RESPONSIVE LAYOUT FOR SMALLER SCREENS
       ========================================== */
    @media (max-width: 1200px) {
        .current-month-events-list {
            grid-template-columns: 1fr;
            max-height: 480px;
        }
    }

    @media (max-width: 768px) {
        .current-month-events-list {
            grid-template-columns: 1fr;
            max-height: 420px;
        }
        
        .current-month-event-row {
            padding: 10px 12px;
        }
    }
    </style>
</head>

<body class="dashboard-page">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../../media/LOGO.jpg" alt="Logo" class="sidebar-logo">
            <div class="sidebar-brand">
                <span class="sidebar-brand-name">IBM</span>
            </div>
        </div>
        
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo htmlspecialchars($user_name); ?></span>
                <span class="sidebar-user-role">Program Head</span>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="sidebar-nav-label">Menu</div>
            <a href="dashboard.php" class="sidebar-nav-item active">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
            <a href="pages/instructors.php" class="sidebar-nav-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Instructors</span>
            </a>
            <a href="pages/student_enrollment.php" class="sidebar-nav-item">
                <i class="fas fa-user-graduate"></i>
                <span>Enrollment</span>
            </a>
            <a href="pages/mentee_flow.php" class="sidebar-nav-item">
                <i class="fas fa-users"></i>
                <span>MenteeFlow</span>
            </a>
            <a href="pages/departments.php" class="sidebar-nav-item">
                <i class="fas fa-building"></i>
                <span>Departments</span>
            </a>
            <a href="pages/reports.php" class="sidebar-nav-item">
                <i class="fas fa-file-alt"></i>
                <span>Reports</span>
            </a>
            <a href="pages/settings.php" class="sidebar-nav-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <div style="position: fixed; top: 0; left: var(--sidebar-width); right: 0; bottom: 0; background-image: url('../../media/LOGO.jpg'); background-size: 70%; background-position: center; background-repeat: no-repeat; opacity: 0.08; pointer-events: none; z-index: 0;"></div>
        
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <div class="topbar-title">Dashboard</div>
                    <div class="topbar-subtitle">Program Head Panel</div>
                </div>
            </div>
            
            <div class="topbar-right">
                <div class="topbar-date">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('F j, Y'); ?></span>
                </div>
                <a href="../../data/logout.php" class="topbar-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </header>

        <main class="dashboard-content">
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" id="alert-<?php echo uniqid(); ?>">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($_GET['success']); ?></span>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error" id="alert-<?php echo uniqid(); ?>">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($_GET['error']); ?></span>
            </div>
            <?php endif; ?>

            <!-- Welcome Banner -->
            <section class="welcome-section">
                <div class="welcome-banner">
                    <div class="welcome-banner-left">
                        <div class="welcome-banner-role">Program Head</div>
                        <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                        <p>Monitor instructor performance, manage evaluations, and track department progress all in one place.</p>
                        <div class="welcome-actions">
                            <a href="pages/instructors.php" class="welcome-btn">
                                <i class="fas fa-chalkboard-teacher"></i> Manage Instructors
                            </a>
                            <a href="pages/reports.php" class="welcome-btn outline">
                                <i class="fas fa-chart-bar"></i> View Reports
                            </a>
                        </div>
                    </div>
                    <div class="welcome-banner-right">
                        <div class="welcome-stats-mini">
                            <div class="mini-stat">
                                <div class="mini-stat-value"><?php echo $total_instructors; ?></div>
                                <div class="mini-stat-label">Instructors</div>
                            </div>
                            <div class="mini-stat">
                                <div class="mini-stat-value"><?php echo $completed_evaluations; ?></div>
                                <div class="mini-stat-label">Evals Done</div>
                            </div>
                            <div class="mini-stat">
                                <div class="mini-stat-value"><?php echo $total_students; ?></div>
                                <div class="mini-stat-label">Students</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Stats Row -->
            <section class="stats-section">
                <div class="quick-stats-row">
                    <a href="pages/instructors.php" class="stats-highlight clickable-stat">
                        <div class="stat-card-inner">
                            <div class="stat-card-icon-wrap gold">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-value"><?php echo $total_instructors; ?></div>
                                <div class="stat-card-label">Total Instructors</div>
                                <div class="stat-card-sub">
                                    <span class="sub-on-duty"><i class="fas fa-circle"></i> <?php echo $on_duty; ?> On Duty</span>
                                    <span class="sub-on-leave"><i class="fas fa-circle"></i> <?php echo $on_leave; ?> On Leave</span>
                                </div>
                            </div>
                            <div class="stat-card-arrow"><i class="fas fa-arrow-right"></i></div>
                        </div>
                    </a>
                    
                    <a href="pages/reports.php" class="stats-highlight clickable-stat">
                        <div class="stat-card-inner">
                            <div class="stat-card-icon-wrap green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-value"><?php echo $completed_evaluations; ?></div>
                                <div class="stat-card-label">Completed Evaluations</div>
                                <div class="stat-card-sub">
                                    <span class="sub-rate"><i class="fas fa-chart-line"></i> <?php echo $eval_completion_rate; ?>% Completion Rate</span>
                                </div>
                            </div>
                            <div class="stat-card-arrow"><i class="fas fa-arrow-right"></i></div>
                        </div>
                    </a>
                    
                    <a href="pages/student_enrollment.php" class="stats-highlight clickable-stat">
                        <div class="stat-card-inner">
                            <div class="stat-card-icon-wrap blue">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-value"><?php echo $total_students; ?></div>
                                <div class="stat-card-label">Total Students</div>
                                <div class="stat-card-sub">
                                    <span class="sub-years"><i class="fas fa-layer-group"></i> <?php echo count($yearLevels); ?> Year Levels</span>
                                </div>
                            </div>
                            <div class="stat-card-arrow"><i class="fas fa-arrow-right"></i></div>
                        </div>
                    </a>
                    
                    <a href="pages/departments.php" class="stats-highlight clickable-stat">
                        <div class="stat-card-inner">
                            <div class="stat-card-icon-wrap purple">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-value"><?php echo $active_courses; ?></div>
                                <div class="stat-card-label">Active Courses</div>
                                <div class="stat-card-sub">
                                    <span class="sub-majors"><i class="fas fa-building"></i> <?php echo count($majors); ?> Majors</span>
                                </div>
                            </div>
                            <div class="stat-card-arrow"><i class="fas fa-arrow-right"></i></div>
                        </div>
                    </a>
                </div>
            </section>

            <!-- Current Time Display -->
            <section class="time-section">
                <div class="time-widget">
                    <div class="time-widget-left">
                        <i class="fas fa-clock"></i>
                        <span id="time-greeting">Morning</span>
                    </div>
                    <div class="time-widget-divider"></div>
                    <div id="current-time" class="time-widget-time">--:--:--</div>
                    <div class="time-widget-divider"></div>
                    <div id="current-date" class="time-widget-date">Loading...</div>
                </div>
            </section>

            <!-- Main Content Grid -->
            <section class="main-grid-section">
                <div class="dashboard-grid">
                    <!-- Department Overview Card -->
                    <div class="content-card wide-card">
                        <div class="content-card-header">
                            <div class="card-header-left">
                                <h3><i class="fas fa-building"></i> Department Overview</h3>
                            </div>
                            <a href="pages/departments.php" class="view-all">
                                Manage <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="content-card-body">
                            <?php if (empty($majors)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-folder-open"></i>
                                    </div>
                                    <p>No departments found</p>
                                    <span>Add departments to get started</span>
                                    <a href="pages/departments.php" class="btn-primary" style="margin-top: 16px; display: inline-flex;">
                                        <i class="fas fa-plus"></i> Add Department
                                    </a>
                                </div>
                            <?php else: ?>
                                <!-- Department Stats Row -->
                                <div class="dept-stats-row">
                                    <div class="dept-stat-box">
                                        <div class="dept-stat-icon majors">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div class="dept-stat-info">
                                            <div class="dept-stat-value"><?php echo count($majors); ?></div>
                                            <div class="dept-stat-label">Majors</div>
                                        </div>
                                    </div>
                                    
                                    <div class="dept-stat-box">
                                        <div class="dept-stat-icon courses">
                                            <i class="fas fa-book-open"></i>
                                        </div>
                                        <div class="dept-stat-info">
                                            <div class="dept-stat-value"><?php echo $active_courses; ?></div>
                                            <div class="dept-stat-label">Active Courses</div>
                                        </div>
                                    </div>
                                    
                                    <div class="dept-stat-box">
                                        <div class="dept-stat-icon students">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        <div class="dept-stat-info">
                                            <div class="dept-stat-value"><?php echo $total_students; ?></div>
                                            <div class="dept-stat-label">Total Students</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Majors List -->
                                <div class="dept-majors-section">
                                    <div class="section-header-modern">
                                        <h4 class="section-title">
                                            <i class="fas fa-layer-group"></i> Program Majors
                                        </h4>
                                        <a href="pages/departments.php" class="section-action">
                                            View All <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                    
                                    <div class="dept-majors-grid">
                                        <?php 
                                        $display_majors = array_slice($majors, 0, 4);
                                        foreach ($display_majors as $major): 
                                            $major_id = $major['id'];
                                            try {
                                                $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM students WHERE major_id = ?");
                                                $stmt->execute([$major_id]);
                                                $student_count = $stmt->fetchColumn() ?: 0;
                                            } catch (PDOException $e) {
                                                $student_count = 0;
                                            }
                                            $percentage = $total_students > 0 ? min(100, round(($student_count / $total_students) * 100)) : 0;
                                        ?>
                                        <a href="pages/departments.php" class="dept-card">
                                            <div class="dept-card-header">
                                                <div class="dept-icon majors">
                                                    <i class="fas fa-graduation-cap"></i>
                                                </div>
                                                <span class="status-badge <?php echo ($major['is_active'] ?? 1) ? 'active' : 'inactive'; ?>">
                                                    <?php echo ($major['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </div>
                                            <h4 class="dept-title"><?php echo htmlspecialchars($major['display_name'] ?? $major['major_name']); ?></h4>
                                            <p class="dept-subtitle"><?php echo htmlspecialchars($major['description'] ?? 'No description'); ?></p>
                                            <div class="dept-value"><?php echo $student_count; ?></div>
                                            <div class="progress-bar">
                                                <div class="progress" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                            <div class="dept-link" style="margin-top: 12px;">
                                                <i class="fas fa-eye"></i> View Details
                                            </div>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <?php if (count($majors) > 4): ?>
                                    <div class="dept-more-section">
                                        <a href="pages/departments.php" class="dept-view-all-btn">
                                            <i class="fas fa-plus-circle"></i> View All <?php echo count($majors); ?> Majors
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                 <!-- Students by Year -->
                                 <?php if (!empty($yearLevels)): ?>
                                 <div class="dept-majors-section">
                                     <div class="section-header-modern">
                                         <h4 class="section-title">
                                             <i class="fas fa-layer-group"></i> Students by Year Level
                                         </h4>
                                         <a href="pages/student_enrollment.php" class="section-action">
                                             View Enrollment <i class="fas fa-arrow-right"></i>
                                         </a>
                                     </div>
                                     
                                     <div class="dept-majors-grid year-majors-grid">
                                         <?php 
                                         $year_colors = [
                                             '1st Year' => ['bg_class' => 'year-green', 'icon' => 'fa-user-graduate'],
                                             '2nd Year' => ['bg_class' => 'year-blue', 'icon' => 'fa-user-graduate'],
                                             '3rd Year' => ['bg_class' => 'year-purple', 'icon' => 'fa-user-graduate'],
                                             '4th Year' => ['bg_class' => 'year-amber', 'icon' => 'fa-user-graduate']
                                         ];
                                         $max_count = 0;
                                         foreach ($yearLevels as $y) {
                                             if (intval($y['count']) > $max_count) $max_count = intval($y['count']);
                                         }
                                         foreach ($yearLevels as $index => $year): 
                                             $count = intval($year['count']);
                                             $bar_pct = $max_count > 0 ? round(($count / $max_count) * 100) : 0;
                                             $pct_total = $total_students > 0 ? round(($count / $total_students) * 100) : 0;
                                             $year_key = $year['year_level'];
                                             $colors = $year_colors[$year_key] ?? $year_colors['1st Year'];
                                         ?>
                                         <a href="pages/student_enrollment.php?year=<?php echo urlencode($year['year_level']); ?>" class="dept-card year-dept-card">
                                             <div class="dept-card-header">
                                                 <div class="dept-icon <?php echo $colors['bg_class']; ?>">
                                                     <i class="fas <?php echo $colors['icon']; ?>"></i>
                                                 </div>
                                                 <span class="status-badge active"><?php echo htmlspecialchars($year['year_level']); ?></span>
                                             </div>
                                             <h4 class="dept-title"><?php echo htmlspecialchars($year['year_level']); ?></h4>
                                             <p class="dept-subtitle">Enrollment</p>
                                             <div class="dept-value"><?php echo $count; ?></div>
                                              <div class="progress-bar">
                                                  <div class="progress" style="width: <?php echo $bar_pct; ?>%;"></div>
                                              </div>
                                          </a>
<?php endforeach; ?>
                                       </div>
                                   </div>

<!-- Current Month Events -->
                                    <div class="dept-majors-section">
                                        <div class="section-header-modern">
                                            <h4 class="section-title" id="events-section-title">
                                                <i class="fas fa-calendar-check"></i> Events for <?php echo $current_month_name; ?>
                                            </h4>
                                            <span class="month-event-count" id="events-section-count"><?php echo count($current_month_events); ?></span>
                                        </div>
                                        <?php if (!empty($current_month_events)): ?>
                                        <div class="current-month-events-list" id="current-month-events-list">
                                           <?php foreach ($current_month_events as $event): 
                                               $first_date = new DateTime($event['dates'][0]);
                                               $is_past = $first_date < new DateTime('today');
                                           ?>
                                           <div class="current-month-event-row <?php echo $is_past ? 'past-event' : ''; ?>" onclick="viewEvent(<?php echo $event['id']; ?>)">
                                               <div class="current-month-event-date-col">
                                                   <span class="current-month-event-day-num"><?php echo count($event['dates']) > 1 ? count($event['dates']) : $first_date->format('j'); ?></span>
                                                   <span class="current-month-event-day-suffix"><?php echo count($event['dates']) > 1 ? 'dates' : $first_date->format('M'); ?></span>
                                               </div>
                                               <div class="current-month-event-details">
                                                   <span class="current-month-event-name"><?php echo htmlspecialchars($event['title']); ?></span>
                                                   <div class="current-month-event-dates">
                                                       <?php foreach (array_slice($event['dates'], 0, 3) as $date): 
                                                           $d = new DateTime($date);
                                                       ?>
                                                           <span class="event-date-tag"><?php echo $d->format('j'); ?> <?php echo $d->format('M'); ?></span>
                                                       <?php endforeach; ?>
                                                       <?php if (count($event['dates']) > 3): ?>
                                                           <span class="event-date-tag event-date-more">+<?php echo count($event['dates']) - 3; ?></span>
                                                       <?php endif; ?>
                                                   </div>
                                                   <?php if (!empty($event['instructor_names'])): ?>
                                                   <span class="current-month-event-inst"><i class="fas fa-user"></i> <?php echo htmlspecialchars(implode(', ', array_slice($event['instructor_names'], 0, 2))); ?></span>
                                                   <?php endif; ?>
                                               </div>
                                               <div class="current-month-event-chevron"><i class="fas fa-chevron-right"></i></div>
                                           </div>
                                           <?php endforeach; ?>
                                       </div>
<?php else: ?>
                                        <div class="no-month-events-compact" id="no-month-events">
                                            <i class="fas fa-calendar-xmark"></i> No events this month
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                   <?php endif; ?>
                                   
                                   <!-- Upcoming Events Section -->
                                  <?php if (!empty($upcoming_events_by_month)): ?>
                                  <div class="dept-majors-section">
                                      <div class="section-header-modern">
                                          <h4 class="section-title">
                                              <i class="fas fa-calendar-check"></i> Upcoming Events
                                          </h4>
                                          <a href="pages/reports.php" class="section-action">
                                              View All <i class="fas fa-arrow-right"></i>
                                          </a>
                                      </div>
                                      
                                      <div class="events-compact-list">
                                          <?php 
                                          $all_upcoming_events = [];
                                          foreach ($upcoming_events_by_month as $month_key => $month_data) {
                                              foreach ($month_data['events'] as $event) {
                                                  $all_upcoming_events[] = array_merge($event, ['month_name' => $month_data['month_name']]);
                                              }
                                          }
                                          $display_events = array_slice($all_upcoming_events, 0, 5);
                                          
                                          foreach ($display_events as $event): 
                                              $event_date = new DateTime($event['event_date']);
                                          ?>
                                          <div class="event-compact-card" onclick="viewEvent(<?php echo $event['id']; ?>)">
                                              <div class="event-date-badge-compact">
                                                  <span class="event-day-num"><?php echo $event_date->format('j'); ?></span>
                                                  <span class="event-month-short"><?php echo $event_date->format('M'); ?></span>
                                              </div>
                                              <div class="event-info-compact">
                                                  <h5 class="event-title-compact"><?php echo htmlspecialchars($event['title']); ?></h5>
                                                  <div class="event-meta-compact">
                                                      <?php if (!empty($event['instructor_names'])): ?>
                                                      <span class="event-assigned">
                                                          <i class="fas fa-user"></i>
                                                          <?php echo htmlspecialchars(implode(', ', $event['instructor_names'])); ?>
                                                      </span>
                                                      <?php else: ?>
                                                      <span class="event-assigned">
                                                          <i class="fas fa-users"></i> No instructors
                                                      </span>
                                                      <?php endif; ?>
                                                  </div>
                                              </div>
                                              <div class="event-arrow">
                                                  <i class="fas fa-chevron-right"></i>
                                              </div>
                                          </div>
                                          <?php endforeach; ?>
                                          
                                          <?php if (count($all_upcoming_events) > 5): ?>
                                          <div class="events-more-link">
                                              <i class="fas fa-calendar-week"></i> +<?php echo count($all_upcoming_events) - 5; ?> more events
                                          </div>
                                          <?php endif; ?>
                                      </div>
                                  </div>
                                  <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right Column: Calendar + Performance -->
                    <div class="right-column-grid">
                        <!-- Calendar Card -->
                        <div class="content-card">
                            <div class="content-card-header">
                                <h3><i class="fas fa-calendar-alt"></i> Calendar</h3>
                                <button onclick="goToToday()" class="calendar-today-btn" title="Go to today">
                                    <i class="fas fa-crosshairs"></i> Today
                                </button>
                            </div>
                            <div class="content-card-body">
                                <div class="calendar-widget">
                                    <div class="calendar-header">
                                        <button onclick="changeMonth(-1)" class="calendar-nav-btn" title="Previous month">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <div id="calendar-title" class="calendar-month">January 2024</div>
                                        <button onclick="changeMonth(1)" class="calendar-nav-btn" title="Next month">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                    <div class="calendar-day-names">
                                        <span>Sun</span>
                                        <span>Mon</span>
                                        <span>Tue</span>
                                        <span>Wed</span>
                                        <span>Thu</span>
                                        <span>Fri</span>
                                        <span>Sat</span>
                                    </div>
                                    <div id="calendar-days" class="calendar-days"></div>
                                </div>
                                
                                <div class="instructor-status-section">
                                    <div class="status-title">Instructor Status</div>
                                    <div class="status-grid">
                                        <a href="pages/instructors.php?filter=on+duty" class="status-item on-duty">
                                            <div class="status-icon"><i class="fas fa-briefcase"></i></div>
                                            <div class="status-value"><?php echo $on_duty; ?></div>
                                            <div class="status-label">On Duty</div>
                                        </a>
                                        <a href="pages/instructors.php?filter=on+leave" class="status-item on-leave">
                                            <div class="status-icon"><i class="fas fa-bed"></i></div>
                                            <div class="status-value"><?php echo $on_leave; ?></div>
                                            <div class="status-label">On Leave</div>
                                        </a>
                                        <a href="pages/instructors.php?filter=on+travel" class="status-item on-travel">
                                            <div class="status-icon"><i class="fas fa-plane"></i></div>
                                            <div class="status-value"><?php echo $on_travel; ?></div>
                                            <div class="status-label">On Travel</div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Overview Card -->
                        <div class="content-card">
                            <div class="content-card-header gold-header">
                                <h3><i class="fas fa-chart-line"></i> Performance Overview</h3>
                                <a href="pages/reports.php" class="view-all">
                                    Full Report <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                            <div class="content-card-body">
                                <div class="performance-summary">
                                    <div class="perf-stat">
                                        <span class="perf-label">Average Rating</span>
                                        <span class="perf-value"><?php echo number_format($avg_rating, 1); ?>/5.0</span>
                                        <div class="perf-stars">
                                            <?php 
                                            $full_stars = floor($avg_rating);
                                            $half_star = ($avg_rating - $full_stars) >= 0.5;
                                            for ($i = 0; $i < 5; $i++):
                                                if ($i < $full_stars): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php elseif ($i === $full_stars && $half_star): ?>
                                                    <i class="fas fa-star-half-alt"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif;
                                            endfor; ?>
                                        </div>
                                    </div>
                                    <div class="perf-stat">
                                        <span class="perf-label">Completion Rate</span>
                                        <span class="perf-value"><?php echo $eval_completion_rate; ?>%</span>
                                        <div class="perf-progress-mini">
                                            <div class="perf-progress-bar" style="width: <?php echo $eval_completion_rate; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($dept_performance)): ?>
                                <div class="perf-list">
                                    <div class="perf-list-title">Department Ratings</div>
                                    <?php foreach (array_slice($dept_performance, 0, 4) as $dept): ?>
                                    <div class="perf-item">
                                        <div class="perf-info">
                                            <span class="perf-name"><?php echo htmlspecialchars($dept['department']); ?></span>
                                            <span class="perf-rating"><?php echo number_format($dept['avg_rating'], 1); ?>/5.0</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: <?php echo ($dept['avg_rating'] / 5) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                    <div class="empty-state-small">
                                        <i class="fas fa-chart-pie"></i>
                                        <p>No performance data yet</p>
                                        <a href="pages/reports.php" class="btn-primary" style="margin-top: 12px; font-size: 12px; padding: 10px 18px;">
                                            <i class="fas fa-plus"></i> Start Evaluation
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <a href="pages/reports.php" class="view-all centered-link">
                                    <i class="fas fa-chart-bar"></i> Full Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Upcoming Events by Month Section -->
            <?php if (!empty($upcoming_events_by_month)): ?>
            <section class="upcoming-events-section">
                <div class="section-header-modern">
                    <h3 class="section-title">
                        <i class="fas fa-calendar-alt"></i> Upcoming Events
                    </h3>
                </div>
                
                <div class="events-by-month-grid">
                    <?php foreach ($upcoming_events_by_month as $month_key => $month_data): ?>
                    <div class="month-events-card">
                        <div class="month-card-header">
                            <h4 class="month-title">
                                <i class="fas fa-calendar-week"></i> <?php echo htmlspecialchars($month_data['month_name']); ?>
                            </h4>
                            <span class="month-event-count"><?php echo count($month_data['events']); ?> event<?php echo count($month_data['events']) > 1 ? 's' : ''; ?></span>
                        </div>
                        <div class="month-card-body">
                            <?php foreach ($month_data['events'] as $event): 
                                $event_date = new DateTime($event['event_date']);
                                $day_suffix = date('S', strtotime($event['event_date']));
                                $formatted_date = $event_date->format('F j') . $day_suffix;
                            ?>
                            <div class="event-item-compact" onclick="viewEvent(<?php echo $event['id']; ?>)">
                                <div class="event-date-badge-small">
                                    <span class="event-day-num"><?php echo $event_date->format('j'); ?></span>
                                    <span class="event-month-short"><?php echo $event_date->format('M'); ?></span>
                                </div>
                                <div class="event-details-compact">
                                    <h5 class="event-title-compact"><?php echo htmlspecialchars($event['title']); ?></h5>
                                    <div class="event-meta-compact">
                                        <?php if (!empty($event['instructor_names'])): ?>
                                            <span class="event-assigned">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars(implode(', ', array_slice($event['instructor_names'], 0, 2))); ?>
                                                <?php if (count($event['instructor_names']) > 2): ?>
                                                    +<?php echo count($event['instructor_names']) - 2; ?> more
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="event-action-icon">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </main>
    </div>

    <!-- Event Modal -->
    <div id="eventModal" class="event-modal-overlay" style="display:none;">
        <div class="event-modal">
            <div class="event-modal-header">
                <h3 id="eventModalTitle"><i class="fas fa-calendar-plus"></i> Add Event</h3>
                <button class="event-modal-close" onclick="closeEventModal()">&times;</button>
            </div>
            <form id="eventForm" onsubmit="saveEvent(event)">
                <input type="hidden" id="eventId" name="event_id" value="">
                <div class="event-modal-body">
                    <div class="event-form-group">
                        <label for="eventTitle"><i class="fas fa-heading"></i> Event Title <span class="required">*</span></label>
                        <input type="text" id="eventTitle" name="title" placeholder="Enter event title..." required maxlength="255">
                    </div>
                    <div class="event-form-group">
                        <label><i class="fas fa-calendar-day"></i> Event Dates <span class="required">*</span> <span style="font-weight:400;color:#9ca3af;font-size:11px;">(click dates to select, click again to deselect)</span></label>
                        <div class="multi-date-picker-container" id="multiDatePicker">
                            <div class="date-picker-header">
                                <button type="button" class="date-picker-nav" onclick="dpChangeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                                <span id="dpTitle">January 2024</span>
                                <button type="button" class="date-picker-nav" onclick="dpChangeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                            </div>
                            <div class="date-picker-day-names">
                                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                            </div>
                            <div id="dpGrid" class="date-picker-grid"></div>
                            <div class="selected-dates-section">
                                <div class="selected-dates-label"><i class="fas fa-check-circle"></i> Selected Dates</div>
                                <div id="selectedDatesChips" class="selected-dates-chips">
                                    <span class="no-dates-hint">No dates selected yet</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="event-form-group">
                        <label for="eventInstructors"><i class="fas fa-chalkboard-teacher"></i> Assign Instructors</label>
                        <div id="instructorCheckboxes" class="instructor-checkbox-list">
                            <div class="loading-instructors"><i class="fas fa-spinner fa-spin"></i> Loading instructors...</div>
                        </div>
                    </div>
                    <div class="event-form-group">
                        <label for="eventDescription"><i class="fas fa-align-left"></i> Description</label>
                        <textarea id="eventDescription" name="description" placeholder="Add event description..." rows="3" maxlength="1000"></textarea>
                    </div>
                </div>
                <div class="event-modal-footer">
                    <button type="button" class="event-btn event-btn-cancel" onclick="closeEventModal()">Cancel</button>
                    <button type="submit" class="event-btn event-btn-save" id="eventSaveBtn">
                        <i class="fas fa-save"></i> Save Event
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Event View Modal -->
    <div id="eventViewModal" class="event-modal-overlay" style="display:none;">
        <div class="event-modal event-view-modal">
            <div class="event-modal-header view-header">
                <h3><i class="fas fa-calendar-check"></i> Event Details</h3>
                <button class="event-modal-close" onclick="closeEventViewModal()">&times;</button>
            </div>
            <div class="event-modal-body">
                <div class="event-view-date" id="viewEventDate"></div>
                <div class="event-view-title" id="viewEventTitle"></div>
                <div class="event-view-instructors" id="viewEventInstructors"></div>
                <div class="event-view-description" id="viewEventDescription"></div>
            </div>
            <div class="event-modal-footer">
                <button type="button" class="event-btn event-btn-add" id="viewAddBtn" onclick="addAnotherEvent()">
                    <i class="fas fa-plus"></i> Add Another Event
                </button>
                <button type="button" class="event-btn event-btn-edit" id="viewEditBtn" onclick="editViewedEvent()">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button type="button" class="event-btn event-btn-delete" id="viewDeleteBtn" onclick="deleteViewedEvent()">
                    <i class="fas fa-trash"></i> Delete
                </button>
                <button type="button" class="event-btn event-btn-cancel" onclick="closeEventViewModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="toast-container"></div>

    <script src="../../function/dashboard.js"></script>
    <script src="../../function/session_guard.js"></script>
    <script>
    // ==========================================
    // CALENDAR FUNCTIONALITY WITH EVENTS
    // ==========================================
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    let calendarEvents = {}; // { "2024-01-15": [{id, title, ...}], ... }
    let currentViewEventId = null;
    let currentViewEventDate = null;

    // Multi-date picker state
    let dpMonth = new Date().getMonth();
    let dpYear = new Date().getFullYear();
    let selectedDates = []; // Array of "YYYY-MM-DD" strings

    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    const monthShortNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    function formatDateKey(year, month, day) {
        return year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
    }

    function formatDisplayDate(dateStr) {
        const parts = dateStr.split('-');
        const d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        return monthNames[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
    }

    function formatShortDate(dateStr) {
        const parts = dateStr.split('-');
        const d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        return monthShortNames[d.getMonth()] + ' ' + d.getDate();
    }

    function loadCalendarEvents() {
        console.log('loadCalendarEvents called for month', currentMonth + 1, 'year', currentYear);
        fetch('calendar_events_handler.php?action=get_events&month=' + (currentMonth + 1) + '&year=' + currentYear)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    console.log('Events loaded:', data.events.length);
                    calendarEvents = {};
                    data.events.forEach(function(ev) {
                        if (!calendarEvents[ev.event_date]) {
                            calendarEvents[ev.event_date] = [];
                        }
                        calendarEvents[ev.event_date].push(ev);
                    });
                    renderCalendar();
                    updateEventsContainer(data.events);
                } else {
                    console.log('Failed to load events:', data.message);
                }
            })
            .catch(function(err) {
                console.error('Error loading events:', err);
            });
    }

    function updateEventsContainer(events) {
        const titleEl = document.getElementById('events-section-title');
        const countEl = document.getElementById('events-section-count');
        const listEl = document.getElementById('current-month-events-list');
        const noEventsEl = document.getElementById('no-month-events');
        
        if (!titleEl || !countEl || !listEl) {
            console.log('updateEventsContainer: elements not found', {titleEl, countEl, listEl});
            return;
        }
        
        titleEl.innerHTML = '<i class="fas fa-calendar-check"></i> Events for ' + monthNames[currentMonth] + ' ' + currentYear;
        
        if (events.length === 0) {
            listEl.innerHTML = '';
            countEl.textContent = '0';
            if (noEventsEl) noEventsEl.style.display = 'block';
            return;
        }
        
        if (noEventsEl) noEventsEl.style.display = 'none';
        
        // Group events by title to combine multiple dates
        const groupedEvents = {};
        events.forEach(function(ev) {
            const titleKey = (ev.title || '').toLowerCase().trim();
            if (!groupedEvents[titleKey]) {
                groupedEvents[titleKey] = {
                    id: ev.id,
                    title: ev.title,
                    description: ev.description,
                    dates: [],
                    instructor_ids: ev.instructor_ids || []
                };
            }
            // Add date if not already in the list
            if (groupedEvents[titleKey].dates.indexOf(ev.event_date) === -1) {
                groupedEvents[titleKey].dates.push(ev.event_date);
            }
        });
        
        // Sort dates for each event
        Object.values(groupedEvents).forEach(function(ev) {
            ev.dates.sort();
        });
        
        const uniqueEvents = Object.values(groupedEvents);
        countEl.textContent = uniqueEvents.length;
        console.log('updateEventsContainer: grouped', uniqueEvents.length, 'unique events from', events.length, 'date entries');
        
        let html = '';
        uniqueEvents.forEach(function(ev) {
            const firstDate = new Date(ev.dates[0]);
            const day = firstDate.getDate();
            const month = monthShortNames[firstDate.getMonth()];
            const isPast = firstDate < new Date(new Date().toDateString());
            
            // Format dates display
            let dateCountDisplay = ev.dates.length > 1 ? ev.dates.length : day;
            let dateSuffixDisplay = ev.dates.length > 1 ? 'dates' : month;
            
            // Build date tags HTML
            let dateTagsHtml = '';
            const datesToShow = ev.dates.slice(0, 3);
            datesToShow.forEach(function(dateStr) {
                const d = new Date(dateStr);
                const dayNum = d.getDate();
                const monthShort = monthShortNames[d.getMonth()];
                dateTagsHtml += '<span class="event-date-tag">' + dayNum + ' ' + monthShort + '</span>';
            });
            
            if (ev.dates.length > 3) {
                dateTagsHtml += '<span class="event-date-tag event-date-more">+' + (ev.dates.length - 3) + '</span>';
            }
            
            let instructorHtml = '';
            if (ev.instructor_ids && ev.instructor_ids.length > 0) {
                instructorHtml = '<span class="current-month-event-inst"><i class="fas fa-user"></i> Assigned</span>';
            }
            
            html += '<div class="current-month-event-row' + (isPast ? ' past-event' : '') + '" onclick="viewEvent(' + ev.id + ')">';
            html += '<div class="current-month-event-date-col">';
            html += '<span class="current-month-event-day-num">' + dateCountDisplay + '</span>';
            html += '<span class="current-month-event-day-suffix">' + dateSuffixDisplay + '</span>';
            html += '</div>';
            html += '<div class="current-month-event-details">';
            html += '<span class="current-month-event-name">' + ev.title + '</span>';
            if (dateTagsHtml) {
                html += '<div class="current-month-event-dates">' + dateTagsHtml + '</div>';
            }
            html += instructorHtml;
            html += '</div>';
            html += '<div class="current-month-event-chevron"><i class="fas fa-chevron-right"></i></div>';
            html += '</div>';
        });
        
        listEl.innerHTML = html;
    }

    function renderCalendar() {
        const calendarTitle = document.getElementById('calendar-title');
        const calendarDays = document.getElementById('calendar-days');
        
        if (!calendarTitle || !calendarDays) return;

        calendarTitle.textContent = monthNames[currentMonth] + ' ' + currentYear;

        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const totalDays = new Date(currentYear, currentMonth + 1, 0).getDate();
        
        const today = new Date();
        const isCurrentMonth = (today.getMonth() === currentMonth && today.getFullYear() === currentYear);

        let html = '';
        
        for (let i = 0; i < firstDay; i++) {
            html += '<div class="calendar-day empty"></div>';
        }
        
        for (let day = 1; day <= totalDays; day++) {
            const isToday = isCurrentMonth && day === today.getDate();
            const isWeekend = (new Date(currentYear, currentMonth, day).getDay() === 0 || 
                              new Date(currentYear, currentMonth, day).getDay() === 6);
            const dateKey = formatDateKey(currentYear, currentMonth, day);
            const hasEvents = calendarEvents[dateKey] && calendarEvents[dateKey].length > 0;
            const eventCount = hasEvents ? calendarEvents[dateKey].length : 0;
            
            let classes = 'calendar-day';
            if (isToday) classes += ' today';
            if (isWeekend) classes += ' weekend';
            if (hasEvents) classes += ' has-event';
            if (eventCount > 1) classes += ' has-multiple-events';
            
            let indicatorHtml = '';
            if (eventCount === 1) {
                indicatorHtml = '<div class="event-indicator"><span class="event-dot"></span></div>';
            } else if (eventCount > 1) {
                indicatorHtml = '<div class="multi-event-badge">' + eventCount + '</div><div class="event-indicator"><span class="event-dot"></span><span class="event-dot"></span></div>';
            }

            let eventTooltips = '';
            if (hasEvents) {
                const titles = calendarEvents[dateKey].map(function(e) { return e.title; }).join(' | ');
                eventTooltips = ' title="' + titles.replace(/"/g, '&quot;') + '" ';
            }
            
            html += '<div class="' + classes + '" data-day="' + day + '" data-date="' + dateKey + '" ' + eventTooltips + ' onclick="selectDay(' + day + ')">' +
                        '<span class="day-number">' + day + '</span>' +
                        indicatorHtml +
                    '</div>';
        }
        
        calendarDays.innerHTML = html;
    }

    function changeMonth(direction) {
        currentMonth += direction;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        } else if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        loadCalendarEvents();
    }

    function goToToday() {
        const today = new Date();
        currentMonth = today.getMonth();
        currentYear = today.getFullYear();
        loadCalendarEvents();
        showToast('Calendar set to today', 'info');
    }

    function selectDay(day) {
        document.querySelectorAll('.calendar-day.selected').forEach(function(el) {
            el.classList.remove('selected');
        });
        const dayEl = document.querySelector('.calendar-day[data-day="' + day + '"]');
        if (dayEl) {
            dayEl.classList.add('selected');
        }

        const dateKey = formatDateKey(currentYear, currentMonth, day);
        const selectedDate = monthNames[currentMonth] + ' ' + day + ', ' + currentYear;

        // If the date has events, show event view; otherwise open add event modal
        if (calendarEvents[dateKey] && calendarEvents[dateKey].length > 0) {
            showDayEvents(dateKey, selectedDate, day);
        } else {
            openEventModal(day);
        }
    }

    function showDayEvents(dateKey, displayDate, day) {
        const events = calendarEvents[dateKey] || [];
        if (events.length === 0) {
            openEventModal(day);
            return;
        }

        // Always show popup with event list and "Add Another Event" button
        // This allows users to add new events even on dates that already have events
        let eventCountLabel = events.length === 1 ? '1 event' : events.length + ' events';
        let listHtml = '<div class="day-events-popup">';
        listHtml += '<div class="day-events-popup-header">';
        listHtml += '<h4><i class="fas fa-calendar-day"></i> ' + displayDate + ' <span style="font-size:11px;opacity:0.85;font-weight:400;">(' + eventCountLabel + ')</span></h4>';
        listHtml += '</div>';
        listHtml += '<div class="day-events-popup-list">';
        events.forEach(function(ev, idx) {
            let dotColor = events.length > 1 ? (idx === 0 ? '#4f46e5' : '#d97706') : '#4f46e5';
            listHtml += '<div class="day-event-item" onclick="viewEvent(' + ev.id + ')">';
            listHtml += '<div class="day-event-dot" style="background:' + dotColor + ';"></div>';
            listHtml += '<div class="day-event-info">';
            listHtml += '<span class="day-event-title">' + ev.title + '</span>';
            listHtml += '</div>';
            listHtml += '<i class="fas fa-chevron-right day-event-arrow"></i>';
            listHtml += '</div>';
        });
        listHtml += '</div>';
        listHtml += '<button class="day-event-add-btn" onclick="openEventModal(' + day + ')">';
        listHtml += '<i class="fas fa-plus"></i> Add Another Event';
        listHtml += '</button>';
        listHtml += '</div>';

        showDayEventsPopup(listHtml);
    }

    let dayEventsPopup = null;
    function showDayEventsPopup(html) {
        removeDayEventsPopup();
        dayEventsPopup = document.createElement('div');
        dayEventsPopup.className = 'day-events-popup-overlay';
        dayEventsPopup.innerHTML = html;
        dayEventsPopup.onclick = function(e) {
            if (e.target === dayEventsPopup) removeDayEventsPopup();
        };
        document.body.appendChild(dayEventsPopup);
    }

    function removeDayEventsPopup() {
        if (dayEventsPopup) {
            dayEventsPopup.remove();
            dayEventsPopup = null;
        }
    }

    // ==========================================
    // MULTI-DATE PICKER
    // ==========================================
    function dpChangeMonth(direction) {
        dpMonth += direction;
        if (dpMonth > 11) {
            dpMonth = 0;
            dpYear++;
        } else if (dpMonth < 0) {
            dpMonth = 11;
            dpYear--;
        }
        renderDatePicker();
    }

    function renderDatePicker() {
        const titleEl = document.getElementById('dpTitle');
        const gridEl = document.getElementById('dpGrid');
        if (!titleEl || !gridEl) return;

        titleEl.textContent = monthNames[dpMonth] + ' ' + dpYear;

        const firstDay = new Date(dpYear, dpMonth, 1).getDay();
        const totalDays = new Date(dpYear, dpMonth + 1, 0).getDate();
        const today = new Date();
        const isCurrentMonth = (today.getMonth() === dpMonth && today.getFullYear() === dpYear);

        let html = '';

        // Previous month trailing days (grayed out)
        const prevMonthDays = new Date(dpYear, dpMonth, 0).getDate();
        for (let i = firstDay - 1; i >= 0; i--) {
            html += '<div class="dp-day other-month empty">' + (prevMonthDays - i) + '</div>';
        }

        // Current month days
        for (let day = 1; day <= totalDays; day++) {
            const dateKey = formatDateKey(dpYear, dpMonth, day);
            const isToday = isCurrentMonth && day === today.getDate();
            const isSelected = selectedDates.indexOf(dateKey) !== -1;

            let classes = 'dp-day';
            if (isToday) classes += ' today';
            if (isSelected) classes += ' selected';

            html += '<div class="' + classes + '" data-date="' + dateKey + '" onclick="toggleDateSelection(\'' + dateKey + '\')">' + day + '</div>';
        }

        // Next month leading days (grayed out)
        const totalCells = firstDay + totalDays;
        const remaining = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
        for (let i = 1; i <= remaining; i++) {
            html += '<div class="dp-day other-month empty">' + i + '</div>';
        }

        gridEl.innerHTML = html;
        renderSelectedChips();
    }

    function toggleDateSelection(dateKey) {
        const idx = selectedDates.indexOf(dateKey);
        if (idx !== -1) {
            selectedDates.splice(idx, 1);
        } else {
            selectedDates.push(dateKey);
        }
        // Sort dates chronologically
        selectedDates.sort();
        renderDatePicker();
    }

    function removeSelectedDate(dateKey) {
        const idx = selectedDates.indexOf(dateKey);
        if (idx !== -1) {
            selectedDates.splice(idx, 1);
        }
        renderDatePicker();
    }

    function renderSelectedChips() {
        const container = document.getElementById('selectedDatesChips');
        if (!container) return;

        if (selectedDates.length === 0) {
            container.innerHTML = '<span class="no-dates-hint">No dates selected yet</span>';
            return;
        }

        let html = '';
        selectedDates.forEach(function(dateKey) {
            html += '<span class="date-chip">';
            html += '<i class="fas fa-calendar-check" style="font-size:10px;"></i> ';
            html += formatShortDate(dateKey);
            html += '<button type="button" class="date-chip-remove" onclick="removeSelectedDate(\'' + dateKey + '\')">&times;</button>';
            html += '</span>';
        });
        container.innerHTML = html;
    }

    // ==========================================
    // EVENT MODAL - Add / Edit
    // ==========================================
    function openEventModal(day, eventId) {
        removeDayEventsPopup();
        const modal = document.getElementById('eventModal');
        const form = document.getElementById('eventForm');
        const titleEl = document.getElementById('eventModalTitle');

        form.reset();
        document.getElementById('eventId').value = eventId || '';

        // Initialize multi-date picker
        if (eventId) {
            titleEl.innerHTML = '<i class="fas fa-edit"></i> Edit Event';
            document.getElementById('eventSaveBtn').innerHTML = '<i class="fas fa-save"></i> Update Event';
            // Dates will be loaded via preSelectInstructors
            selectedDates = [];
            renderDatePicker();
            loadInstructorCheckboxes(eventId);
        } else {
            titleEl.innerHTML = '<i class="fas fa-calendar-plus"></i> Add Event';
            document.getElementById('eventSaveBtn').innerHTML = '<i class="fas fa-save"></i> Save Event';
            // Pre-select the clicked date
            const dateKey = formatDateKey(currentYear, currentMonth, day);
            selectedDates = [dateKey];
            // Set date picker to the clicked month
            dpMonth = currentMonth;
            dpYear = currentYear;
            renderDatePicker();
            loadInstructorCheckboxes(null);
        }

        modal.style.display = 'flex';
        setTimeout(function() { document.getElementById('eventTitle').focus(); }, 200);
    }

    function closeEventModal() {
        const modal = document.getElementById('eventModal');
        modal.style.display = 'none';
        document.getElementById('eventForm').reset();
        document.getElementById('eventId').value = '';
        selectedDates = [];
    }

    function loadInstructorCheckboxes(selectedEventId) {
        const container = document.getElementById('instructorCheckboxes');
        container.innerHTML = '<div class="loading-instructors"><i class="fas fa-spinner fa-spin"></i> Loading instructors...</div>';

        fetch('calendar_events_handler.php?action=get_instructors')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success && data.instructors.length > 0) {
                    let html = '';
                    data.instructors.forEach(function(inst) {
                        const statusClass = inst.status ? ' status-' + inst.status.replace(/\s+/g, '-') : '';
                        html += '<label class="instructor-checkbox-item' + statusClass + '">';
                        html += '<input type="checkbox" name="instructor_ids[]" value="' + inst.id + '" class="inst-checkbox">';
                        html += '<span class="inst-checkmark"><i class="fas fa-check"></i></span>';
                        html += '<span class="inst-name">' + inst.first_name + ' ' + inst.last_name + '</span>';
                        html += '<span class="inst-status">' + (inst.status || '') + '</span>';
                        html += '</label>';
                    });
                    container.innerHTML = html;

                    // If editing, pre-select instructors and load event data
                    if (selectedEventId) {
                        preSelectInstructors(selectedEventId);
                    }
                } else {
                    container.innerHTML = '<div class="no-instructors"><i class="fas fa-user-slash"></i> No instructors found</div>';
                }
            })
            .catch(function(err) {
                container.innerHTML = '<div class="no-instructors"><i class="fas fa-exclamation-circle"></i> Error loading instructors</div>';
            });
    }

    function preSelectInstructors(eventId) {
        fetch('calendar_events_handler.php?action=get_event&id=' + eventId)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success && data.event) {
                    document.getElementById('eventTitle').value = data.event.title;
                    document.getElementById('eventDescription').value = data.event.description || '';
                    const instIds = data.event.instructor_ids || [];
                    document.querySelectorAll('.inst-checkbox').forEach(function(cb) {
                        if (instIds.indexOf(parseInt(cb.value)) !== -1) {
                            cb.checked = true;
                            cb.closest('.instructor-checkbox-item').classList.add('checked');
                        }
                    });

                    // Load multi-date selection
                    if (data.event.event_dates && data.event.event_dates.length > 0) {
                        selectedDates = data.event.event_dates.slice().sort();
                        // Set date picker to the first date's month
                        const firstDate = selectedDates[0].split('-');
                        dpMonth = parseInt(firstDate[1]) - 1;
                        dpYear = parseInt(firstDate[0]);
                        renderDatePicker();
                    } else if (data.event.event_date) {
                        selectedDates = [data.event.event_date];
                        const parts = data.event.event_date.split('-');
                        dpMonth = parseInt(parts[1]) - 1;
                        dpYear = parseInt(parts[0]);
                        renderDatePicker();
                    }
                }
            })
            .catch(function(err) { console.error(err); });
    }

    function saveEvent(e) {
        e.preventDefault();
        const eventId = document.getElementById('eventId').value;
        const title = document.getElementById('eventTitle').value.trim();
        const description = document.getElementById('eventDescription').value.trim();

        if (!title) {
            showToast('Please enter an event title', 'error');
            return;
        }

        if (selectedDates.length === 0) {
            showToast('Please select at least one date', 'error');
            return;
        }

        // Collect selected instructor IDs
        const instructorIds = [];
        document.querySelectorAll('.inst-checkbox:checked').forEach(function(cb) {
            instructorIds.push(cb.value);
        });

        const formData = new FormData();
        formData.append('action', eventId ? 'update_event' : 'create_event');
        if (eventId) formData.append('id', eventId);
        formData.append('title', title);
        formData.append('description', description);
        // Send primary date for backward compatibility
        formData.append('event_date', selectedDates[0]);
        // Send all selected dates
        selectedDates.forEach(function(date) {
            formData.append('event_dates[]', date);
        });
        instructorIds.forEach(function(id) {
            formData.append('instructor_ids[]', id);
        });

        const saveBtn = document.getElementById('eventSaveBtn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        fetch('calendar_events_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> ' + (eventId ? 'Update Event' : 'Save Event');
            if (data.success) {
                    showToast(data.message, 'success');
                closeEventModal();
                loadCalendarEvents();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(function(err) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Event';
            showToast('Error saving event', 'error');
        });
    }

    // ==========================================
    // EVENT VIEW MODAL
    // ==========================================
    function viewEvent(eventId) {
        removeDayEventsPopup();
        fetch('calendar_events_handler.php?action=get_event&id=' + eventId)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success && data.event) {
                    currentViewEventId = data.event.id;
                    currentViewEventDate = data.event.event_date;
                    const ev = data.event;

                    // Format dates for display - support multi-date
                    let datesHtml = '';
                    if (ev.event_dates && ev.event_dates.length > 0) {
                        if (ev.event_dates.length === 1) {
                            // Single date - show as before
                            const dateObj = new Date(ev.event_dates[0] + 'T00:00:00');
                            const dateStr = dateObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                            datesHtml = '<i class="fas fa-calendar-day"></i> ' + dateStr;
                        } else {
                            // Multiple dates - show as badges
                            datesHtml = '<div class="event-view-dates">';
                            datesHtml += '<div class="view-dates-label"><i class="fas fa-calendar-day"></i> Event Dates (' + ev.event_dates.length + ')</div>';
                            datesHtml += '<div class="view-dates-list">';
                            ev.event_dates.forEach(function(d) {
                                const dateObj = new Date(d + 'T00:00:00');
                                const dateStr = dateObj.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
                                datesHtml += '<span class="view-date-badge"><i class="fas fa-calendar-check"></i> ' + dateStr + '</span>';
                            });
                            datesHtml += '</div></div>';
                        }
                    } else {
                        // Fallback to single event_date
                        const dateObj = new Date(ev.event_date + 'T00:00:00');
                        const dateStr = dateObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                        datesHtml = '<i class="fas fa-calendar-day"></i> ' + dateStr;
                    }

                    document.getElementById('viewEventDate').innerHTML = datesHtml;
                    document.getElementById('viewEventTitle').textContent = ev.title;

                    // Instructors
                    let instHtml = '';
                    if (ev.instructors && ev.instructors.length > 0) {
                        instHtml = '<div class="view-instructors-label"><i class="fas fa-chalkboard-teacher"></i> Assigned Instructors</div>';
                        instHtml += '<div class="view-instructors-list">';
                        ev.instructors.forEach(function(inst) {
                            instHtml += '<span class="view-instructor-badge"><i class="fas fa-user"></i> ' + inst.first_name + ' ' + inst.last_name + '</span>';
                        });
                        instHtml += '</div>';
                    }
                    document.getElementById('viewEventInstructors').innerHTML = instHtml;

                    // Description
                    if (ev.description) {
                        document.getElementById('viewEventDescription').innerHTML = '<div class="view-desc-label"><i class="fas fa-align-left"></i> Description</div><div class="view-desc-text">' + ev.description.replace(/\n/g, '<br>') + '</div>';
                    } else {
                        document.getElementById('viewEventDescription').innerHTML = '<div class="view-desc-label"><i class="fas fa-align-left"></i> Description</div><div class="view-desc-text empty-desc">No description provided</div>';
                    }

                    document.getElementById('eventViewModal').style.display = 'flex';
                } else {
                    showToast('Event not found', 'error');
                }
            })
            .catch(function(err) {
                showToast('Error loading event', 'error');
            });
    }

    function closeEventViewModal() {
        document.getElementById('eventViewModal').style.display = 'none';
        currentViewEventId = null;
        currentViewEventDate = null;
    }

    function editViewedEvent() {
        if (!currentViewEventId) return;
        const editId = currentViewEventId;
        closeEventViewModal();
        // Open edit modal - dates will be loaded from the event data
        openEventModal(null, editId);
    }

    function addAnotherEvent() {
        if (!currentViewEventDate) return;
        const parts = currentViewEventDate.split('-');
        const year = parseInt(parts[0]);
        const month = parseInt(parts[1]) - 1;
        const day = parseInt(parts[2]);
        closeEventViewModal();
        // Navigate calendar to the event's month
        currentMonth = month;
        currentYear = year;
        loadCalendarEvents();
        // Open add event modal with the date pre-selected
        openEventModal(day);
    }

    function deleteViewedEvent() {
        if (!currentViewEventId) return;
        if (!confirm('Are you sure you want to delete this event?')) return;

        const formData = new FormData();
        formData.append('action', 'delete_event');
        formData.append('id', currentViewEventId);

        fetch('calendar_events_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Event deleted successfully', 'success');
                closeEventViewModal();
                loadCalendarEvents();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(function(err) {
            showToast('Error deleting event', 'error');
        });
    }

    // Checkbox visual toggle
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('inst-checkbox')) {
            const item = e.target.closest('.instructor-checkbox-item');
            if (e.target.checked) {
                item.classList.add('checked');
            } else {
                item.classList.remove('checked');
            }
        }
    });

    // Close modals on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEventModal();
            closeEventViewModal();
            removeDayEventsPopup();
        }
    });

    // Initialize calendar on page load
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date();
        currentMonth = today.getMonth();
        currentYear = today.getFullYear();
        loadCalendarEvents();
    });

    // ==========================================
    // TIME UPDATE
    // ==========================================
    function updateTime() {
        const now = new Date();
        const options = { timeZone: 'Asia/Manila', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
        const timeString = now.toLocaleTimeString('en-US', options);
        const hour = parseInt(now.toLocaleTimeString('en-US', { timeZone: 'Asia/Manila', hour: '2-digit', hour12: false }));
        
        const greetingEl = document.getElementById('time-greeting');
        if (hour >= 5 && hour < 12) greetingEl.textContent = 'Morning';
        else if (hour >= 12 && hour < 17) greetingEl.textContent = 'Afternoon';
        else if (hour >= 17 && hour < 21) greetingEl.textContent = 'Evening';
        else greetingEl.textContent = 'Night';
        
        document.getElementById('current-time').textContent = timeString;
        const dateOptions = { timeZone: 'Asia/Manila', weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
        document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', dateOptions);
    }
    updateTime();
    setInterval(updateTime, 1000);

    // ==========================================
    // TOAST NOTIFICATIONS
    // ==========================================
    function showToast(message, type) {
        type = type || 'info';
        const container = document.getElementById('toast-container');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        
        const icons = {
            'info': 'fas fa-info-circle',
            'success': 'fas fa-check-circle',
            'warning': 'fas fa-exclamation-triangle',
            'error': 'fas fa-times-circle'
        };
        
        toast.innerHTML = '<i class="' + (icons[type] || icons.info) + '"></i><span>' + message + '</span>';
        container.appendChild(toast);
        
        // Trigger animation
        setTimeout(function() { toast.classList.add('show'); }, 10);
        
        // Auto remove
        setTimeout(function() {
            toast.classList.remove('show');
            setTimeout(function() { toast.remove(); }, 300);
        }, 3000);
    }

    // ==========================================
    // SIDEBAR TOGGLE
    // ==========================================
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('open') && 
            !sidebar.contains(e.target) && 
            e.target !== menuToggle && 
            !menuToggle.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    });

    // ==========================================
    // AUTO-DISMISS ALERTS
    // ==========================================
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function() { alert.remove(); }, 300);
        }, 5000);
    });
    </script>
</body>
</html>
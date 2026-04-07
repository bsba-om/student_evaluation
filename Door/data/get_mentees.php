<?php
// Door/data/get_mentees.php
header('Content-Type: application/json');
require_once 'config.php';

$instructor_id = isset($_GET['instructor_id']) ? intval($_GET['instructor_id']) : 0;
$result = [];

if ($instructor_id > 0) {
    try {
        // Assuming there is a 'mentees' table with columns: id, first_name, last_name, email, mentor_id
        // If your schema is different, adjust the query accordingly
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM mentees WHERE mentor_id = ?");
        $stmt->execute([$instructor_id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $result = [];
    }
}
echo json_encode($result);

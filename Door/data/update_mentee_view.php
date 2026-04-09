<?php
require_once 'session_security.php';

check_role_access('instructor');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['last_mentee_view'] = date('Y-m-d H:i:s');

echo json_encode(['success' => true]);

<?php
// Door/data/config.php

$creds = [
    'localhost' => [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'name' => 'checkmate',
    ],
];

$server_key = 'localhost';
if (!array_key_exists($server_key, $creds)) {
    $creds[$server_key] = [
        'host' => 'localhost',
        'user' => '',
        'pass' => '',
        'name' => 'checkmate',
    ];
}

$c = $creds[$server_key];
define('DB_HOST', $c['host']);
define('DB_USER', $c['user']);
define('DB_PASS', $c['pass']);
define('DB_NAME', $c['name']);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $pdo = null;
}

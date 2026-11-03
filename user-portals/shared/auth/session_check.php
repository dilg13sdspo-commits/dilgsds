<?php
require_once __DIR__ . '/db.php';
start_secure_session();
header('Content-Type: application/json');

$auth = [
    'authenticated' => false,
];
if (!empty($_SESSION['user_id'])) {
    $auth['authenticated'] = true;
    $auth['user_id'] = $_SESSION['user_id'];
    $auth['username'] = $_SESSION['username'] ?? null;
    $auth['role'] = $_SESSION['role'] ?? null;
}

echo json_encode($auth);

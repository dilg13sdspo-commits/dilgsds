<?php
require_once __DIR__ . '/middleware.php';
header('Content-Type: application/json; charset=utf-8');

function users_api_error($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

function require_admin() {
    require_role('ADMIN');
}

require_admin();

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$username = trim((string)($input['username'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$role = trim((string)($input['role'] ?? 'FO USER'));
$password = (string)($input['password'] ?? '');

if ($username === '' || $email === '' || $password === '') {
    users_api_error('Username, email, and password are required.');
}

if (!in_array($role, ['ADMIN', 'FO USER', 'PO USER'], true)) {
    users_api_error('Invalid role selected.');
}

if (strlen($password) < 8) {
    users_api_error('Password must be at least 8 characters long.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    users_api_error('Please enter a valid email address.');
}

$pdo = db_connect();

$stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
$stmt->execute([':username' => $username, ':email' => $email]);
if ($stmt->fetch()) {
    users_api_error('A user with that username or email already exists.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :hash, :role)');
$stmt->execute([
    ':username' => $username,
    ':email' => $email,
    ':hash' => $hash,
    ':role' => $role,
]);

http_response_code(201);
echo json_encode([
    'success' => true,
    'message' => 'User created successfully.'
]);

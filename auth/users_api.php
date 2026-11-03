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
$fullName = trim((string)($input['full_name'] ?? ''));
$action = trim((string)($input['action'] ?? 'create'));

try {
    $pdo = db_connect();

    if ($action === 'update') {
        $id = (int)($input['id'] ?? 0);
        $fullName = trim((string)($input['full_name'] ?? ''));
        $officeName = trim((string)($input['office_name'] ?? ''));
        if ($id <= 0 || $fullName === '' || $username === '' || $email === '') {
            users_api_error('Name, username, email, and a valid user ID are required.');
        }
        if (!in_array($role, ['ADMIN', 'FO USER', 'PO USER'], true) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            users_api_error('Please provide valid account details.');
        }
        $exists = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
        $exists->execute([':id' => $id]);
        if (!$exists->fetchColumn()) users_api_error('User was not found.', 404);
        if ($officeName === '') users_api_error('Office is required.');
        $officeStmt = $pdo->prepare('SELECT id FROM offices WHERE name = :name AND is_active = 1 LIMIT 1');
        $officeStmt->execute([':name' => $officeName]);
        $officeId = $officeStmt->fetchColumn();
        if (!$officeId) users_api_error('Please select a valid active office.');
        $dupe = $pdo->prepare('SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id LIMIT 1');
        $dupe->execute([':username' => $username, ':email' => $email, ':id' => $id]);
        if ($dupe->fetch()) users_api_error('That username or email is already in use.', 409);
        $stmt = $pdo->prepare('UPDATE users SET full_name = :full_name, username = :username, email = :email, role = :role, office_name = :office_name, office_id = :office_id, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':full_name' => $fullName, ':username' => $username, ':email' => $email, ':role' => $role, ':office_name' => $officeName, ':office_id' => $officeId, ':id' => $id]);
        echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
        exit;
    }

    if ($action === 'reset_password') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0 || strlen($password) < 8) users_api_error('A new password of at least 8 characters is required.');
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $id]);
        if ($stmt->rowCount() !== 1) users_api_error('User was not found.', 404);
        echo json_encode(['success' => true, 'message' => 'Password reset successfully.']);
        exit;
    }

    if ($action === 'toggle_status') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0 || $id === (int)($_SESSION['user_id'] ?? 0)) users_api_error('You cannot disable your own administrator account.');
        $stmt = $pdo->prepare('UPDATE users SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $id]);
        if ($stmt->rowCount() !== 1) users_api_error('User was not found.', 404);
        echo json_encode(['success' => true, 'message' => 'Account status updated.']);
        exit;
    }
} catch (PDOException $e) {
    error_log('User management action failed: ' . $e->getMessage());
    users_api_error('Unable to update the account. Please try again.', 500);
}

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

if ($fullName === '') {
    $fullName = ucwords(str_replace(['.', '_', '-'], ' ', $username));
}

try {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
    $stmt->execute([':username' => $username, ':email' => $email]);
    if ($stmt->fetch()) {
        users_api_error('A user with that username or email already exists.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, full_name, role) VALUES (:username, :email, :hash, :full_name, :role)');
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':hash' => $hash,
        ':full_name' => $fullName,
        ':role' => $role,
    ]);
} catch (PDOException $e) {
    error_log('User creation failed: ' . $e->getMessage());
    users_api_error('Unable to create the account. Please verify the database setup and try again.', 500);
} catch (Throwable $e) {
    error_log('User creation failed: ' . $e->getMessage());
    users_api_error('Unable to create the account. Please try again.', 500);
}

http_response_code(201);
echo json_encode([
    'success' => true,
    'message' => 'User created successfully.'
]);

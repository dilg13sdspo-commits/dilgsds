<?php
require_once __DIR__ . '/middleware.php';

header('Content-Type: application/json; charset=utf-8');
require_same_origin_request();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$username = is_array($input) && is_string($input['username'] ?? null) ? trim($input['username']) : '';
$password = is_array($input) && is_string($input['password'] ?? null) ? $input['password'] : '';

if ($username === '' || $password === '' || strlen($username) > 255 || strlen($password) > 4096) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

try {
    $pdo = db_connect();
    $userColumns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN, 0);
    $selectExtras = [];
    foreach (['is_active', 'must_change_password'] as $optionalColumn) {
        if (in_array($optionalColumn, $userColumns, true)) {
            $selectExtras[] = $optionalColumn;
        }
    }
    $selectFields = 'id, username, email, password_hash, role';
    if ($selectExtras) {
        $selectFields .= ', ' . implode(', ', $selectExtras);
    }
    $stmt = $pdo->prepare("SELECT {$selectFields} FROM users WHERE username = :u OR email = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    if (
        !$user
        || (array_key_exists('is_active', $user) && !(bool)$user['is_active'])
        || !password_verify($password, $user['password_hash'])
    ) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password']);
        exit;
    }

    // Regenerate session id on successful login
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['office_name'] = $user['office_name'] ?? null;
    $_SESSION['office_id'] = $user['office_id'] ?? null;
    $_SESSION['must_change_password'] = !empty($user['must_change_password']);

    // Audit logging must not block a valid login when the legacy table differs.
    try {
        $logStmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, ip, user_agent) VALUES (:uid, :action, :ip, :ua)');
        $logStmt->execute([
            ':uid' => $user['id'],
            ':action' => 'login',
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        ]);
    } catch (Throwable $auditError) {
        error_log('Login audit log failed: ' . $auditError->getMessage());
    }

    echo json_encode(['success' => true, 'role' => $user['role']]);
} catch (Exception $e) {
    error_log('Login request failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
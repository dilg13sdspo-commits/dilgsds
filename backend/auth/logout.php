<?php
require_once __DIR__ . '/db.php';
start_secure_session();

$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/';
$scriptDir = rtrim(dirname($scriptPath), '/');
$projectBase = preg_replace('#/(?:backend/)?auth$#i', '', $scriptDir);
$projectBase = rtrim($projectBase, '/');
$redirectTarget = ($projectBase === '' ? '' : $projectBase) . '/public/pages/index.html';
$requestedRedirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';

if (is_string($requestedRedirect) && preg_match('#^(?:\.\./)?[A-Za-z0-9._/-]+$#', $requestedRedirect)) {
    $relativeRedirect = preg_replace('#^(?:\.\./)+#', '', $requestedRedirect);
    if ($relativeRedirect !== '' && strpos($relativeRedirect, '..') === false) {
        $redirectTarget = ($projectBase === '' ? '' : $projectBase) . '/' . ltrim($relativeRedirect, '/');
    }
}

// Audit
try {
    $pdo = db_connect();
    $uid = $_SESSION['user_id'] ?? null;
    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, ip, user_agent) VALUES (:uid, :action, :ip, :ua)');
    $stmt->execute([
        ':uid' => $uid,
        ':action' => 'logout',
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
    ]);
} catch (Exception $e) {
    // ignore
}

// Destroy session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

if (!headers_sent()) {
    header('Location: ' . $redirectTarget, true, 302);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'redirect' => $redirectTarget]);

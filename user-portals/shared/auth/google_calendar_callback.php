<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/middleware.php';
require_role(['FO USER', 'PO USER']);

$userId = (int)($_SESSION['user_id'] ?? 0);
$code = $_GET['code'] ?? '';
if ($code === '') {
    header('Location: ../po-calendar.php?google_error=missing_code');
    exit;
}

$config = [
    'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
    'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: 'http://127.0.0.1:8000/auth/google_calendar_callback.php',
];

if ($config['client_id'] === '' || $config['client_secret'] === '') {
    header('Location: ../po-calendar.php?google_error=not_configured');
    exit;
}

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'code' => $code,
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => $config['redirect_uri'],
        'grant_type' => 'authorization_code',
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 400 || $response === false) {
    header('Location: ../po-calendar.php?google_error=oauth_failed');
    exit;
}

$data = json_decode($response, true);
if (!isset($data['access_token'])) {
    header('Location: ../po-calendar.php?google_error=invalid_token');
    exit;
}

$pdo = db_connect();
$stmt = $pdo->prepare('INSERT INTO google_calendar_tokens (user_id, access_token, refresh_token, expires_at, scope, connected_at) VALUES (:uid, :access, :refresh, :expires, :scope, NOW()) ON DUPLICATE KEY UPDATE access_token = VALUES(access_token), refresh_token = VALUES(refresh_token), expires_at = VALUES(expires_at), scope = VALUES(scope), connected_at = NOW(), updated_at = NOW()');
$stmt->execute([
    ':uid' => $userId,
    ':access' => $data['access_token'],
    ':refresh' => $data['refresh_token'] ?? '',
    ':expires' => date('Y-m-d H:i:s', time() + (int)($data['expires_in'] ?? 3600)),
    ':scope' => $data['scope'] ?? 'https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/calendar.events',
]);

header('Location: ../po-calendar.php?google_connected=1');
exit;

<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/middleware.php';
require_role(['FO USER', 'PO USER']);

$config = [
    'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
    'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: 'http://127.0.0.1:8000/auth/google_calendar_callback.php',
];

if ($config['client_id'] === '') {
    header('Location: ../po-calendar.php?google_error=not_configured');
    exit;
}

$params = http_build_query([
    'client_id' => $config['client_id'],
    'redirect_uri' => $config['redirect_uri'],
    'response_type' => 'code',
    'scope' => 'https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/calendar.events',
    'access_type' => 'offline',
    'prompt' => 'consent',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit;

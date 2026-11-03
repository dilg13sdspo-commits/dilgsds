<?php
$cfg = require __DIR__ . '/config.php';

function db_connect() {
    global $cfg;
    static $pdo = null;
    if ($pdo) return $pdo;
    $port = $cfg['db_port'] ?? 3307;
    $dsn = "mysql:host={$cfg['db_host']};port={$port};dbname={$cfg['db_name']};charset=utf8mb4";
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], $opts);
    return $pdo;
}

function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        $cookieParams = session_get_cookie_params();
        $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
        session_set_cookie_params([
            'lifetime' => 60*60*24, // 1 day
            'path' => $cookieParams['path'],
            'domain' => $cookieParams['domain'],
            'secure' => !empty($GLOBALS['cfg']['cookie_secure']) || $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

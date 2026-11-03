<?php
// Run once to create an initial ADMIN user. Remove or protect afterwards.
require_once __DIR__ . '/db.php';
start_secure_session();

$cfg = require __DIR__ . '/config.php';
if (php_sapi_name() !== 'cli' && ($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1') {
    echo "This script must be run from CLI or localhost.";
    exit;
}

$stdin = fopen('php://stdin', 'r');
echo "Enter admin username: "; $u = trim(fgets($stdin));
echo "Enter admin email: "; $e = trim(fgets($stdin));
echo "Enter admin password: ";
if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
    $p = trim(fgets($stdin));
} else {
    system('stty -echo'); $p = trim(fgets($stdin)); system('stty echo'); echo PHP_EOL;
}

if (!$u || !$e || !$p) { echo "All fields required\n"; exit; }

try {
    $pdo = db_connect();
    $hash = password_hash($p, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (:u, :e, :h, :r)');
    $stmt->execute([':u'=>$u, ':e'=>$e, ':h'=>$hash, ':r'=>'ADMIN']);
    echo "Admin user created.\n";
} catch (Exception $ex) {
    echo "Failed: " . $ex->getMessage() . "\n";
}

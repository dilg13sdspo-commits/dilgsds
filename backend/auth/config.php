<?php
// Use the local XAMPP MariaDB settings by default.
// This avoids stale sample credentials such as dbuser/dbpass, which cause the PDO access denied error.

$localConfig = __DIR__ . '/config.local.php';
$isProduction = strtolower((string)getenv('APP_ENV')) === 'production';

if ($isProduction) {
    return [
        'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
        'db_port' => (int)(getenv('DB_PORT') ?: 3306),
        'db_name' => getenv('DB_NAME') ?: '',
        'db_user' => getenv('DB_USER') ?: '',
        'db_pass' => getenv('DB_PASS') ?: '',
        'cookie_secure' => true,
    ];
}

if (file_exists($localConfig)) {
    return require $localConfig;
}

return [
    'db_host' => '127.0.0.1',
    'db_name' => 'dilg_sds',
    'db_user' => 'root',
    'db_pass' => '',
    'cookie_secure' => false,
];

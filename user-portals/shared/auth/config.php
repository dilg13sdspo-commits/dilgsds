<?php
// Use the local XAMPP MariaDB settings by default.
// This avoids stale sample credentials such as dbuser/dbpass, which cause the PDO access denied error.

$localConfig = __DIR__ . '/config.local.php';

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

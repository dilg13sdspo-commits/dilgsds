<?php
/**
 * Database Setup Script
 * Run this once to create the database and tables
 */

$cfg = require __DIR__ . '/config.php';

// Connect to MySQL without selecting a database
$dsn = "mysql:host={$cfg['db_host']};charset=utf8mb4";
$opts = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], $opts);
    
    // Create database
    echo "Creating database '{$cfg['db_name']}'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['db_name']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database created.\n\n";
    
    // Select the database
    $pdo->exec("USE `{$cfg['db_name']}`");
    
    // Read and execute the database schema
    $schemaFile = __DIR__ . '/../database.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    echo "Loading database schema...\n";
    $schema = file_get_contents($schemaFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && strpos($statement, '--') !== 0) {
            $pdo->exec($statement);
        }
    }
    
    echo "✓ Database schema loaded.\n\n";
    echo "✓ Setup complete! Database is ready.\n";
    echo "\nNext steps:\n";
    echo "1. Run: php auth/init_admin.php\n";
    echo "2. Create an admin user with your credentials\n";
    
} catch (Exception $ex) {
    echo "✗ Error: " . $ex->getMessage() . "\n";
    exit(1);
}

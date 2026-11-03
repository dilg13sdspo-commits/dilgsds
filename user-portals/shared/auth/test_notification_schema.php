<?php
/**
 * Diagnostic script to verify FO Response Notification schema and functionality
 * This helps identify why notifications aren't being created or displayed
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/middleware.php';

// Only admins can run this
require_role('ADMIN', 'FO USER', 'PO USER');

$pdo = db_connect();

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

$diagnostics = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => [],
    'schema' => [],
    'notifications' => [],
    'errors' => [],
];

try {
    // Check database connection
    $stmt = $pdo->query("SELECT 1");
    $diagnostics['database']['connected'] = $stmt ? true : false;
} catch (Exception $e) {
    $diagnostics['errors'][] = "Database connection error: " . $e->getMessage();
    $diagnostics['database']['connected'] = false;
}

try {
    // Check notifications table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $diagnostics['schema']['notifications_table_exists'] = (bool)$stmt->fetchColumn();
} catch (Exception $e) {
    $diagnostics['errors'][] = "Error checking notifications table: " . $e->getMessage();
}

try {
    // Check for notification columns
    $stmt = $pdo->query("SHOW COLUMNS FROM notifications");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = $row['Type'];
    }
    $diagnostics['schema']['notification_columns'] = $columns;
    
    $required_columns = ['response_status', 'response_submitted_at', 'response_deadline', 'type'];
    $diagnostics['schema']['required_columns_exist'] = [];
    foreach ($required_columns as $col) {
        $diagnostics['schema']['required_columns_exist'][$col] = isset($columns[$col]);
    }
} catch (Exception $e) {
    $diagnostics['errors'][] = "Error checking notification columns: " . $e->getMessage();
}

try {
    // Count all notifications
    $stmt = $pdo->query("SELECT COUNT(*) FROM notifications");
    $diagnostics['notifications']['total_count'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $diagnostics['errors'][] = "Error counting notifications: " . $e->getMessage();
}

try {
    // Count fo_response type notifications
    $stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE type = 'fo_response'");
    $diagnostics['notifications']['fo_response_count'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $diagnostics['errors'][] = "Error counting fo_response notifications: " . $e->getMessage();
}

try {
    // Get recent fo_response notifications
    $stmt = $pdo->query("SELECT id, user_id, title, type, response_status, created_at FROM notifications WHERE type = 'fo_response' ORDER BY created_at DESC LIMIT 10");
    $diagnostics['notifications']['recent_fo_responses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $diagnostics['errors'][] = "Error fetching recent fo_response notifications: " . $e->getMessage();
}

try {
    // Check reports table for responses
    $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE fo_response_at IS NOT NULL");
    $diagnostics['schema']['reports_with_responses'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $diagnostics['errors'][] = "Error checking reports with responses: " . $e->getMessage();
}

try {
    // Get recent report responses
    $stmt = $pdo->query("SELECT id, reference_number, user_id, fo_response_at, status FROM reports WHERE fo_response_at IS NOT NULL ORDER BY fo_response_at DESC LIMIT 10");
    $diagnostics['schema']['recent_report_responses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $diagnostics['errors'][] = "Error fetching recent report responses: " . $e->getMessage();
}

// Set overall success/failure
if (!empty($diagnostics['errors'])) {
    $diagnostics['success'] = false;
}

echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

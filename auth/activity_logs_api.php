<?php
require_once __DIR__ . '/middleware.php';
header('Content-Type: application/json; charset=utf-8');

try {
    require_role('ADMIN');
    $pdo = db_connect();

    $stmt = $pdo->query(
        "SELECT a.id, a.user_id, a.action, a.created_at, a.ip, a.user_agent, a.meta, u.username, u.email, u.role
         FROM audit_logs a
         LEFT JOIN users u ON u.id = a.user_id
         ORDER BY a.created_at DESC
         LIMIT 1000"
    );

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($logs as &$log) {
        $action = strtolower((string) ($log['action'] ?? ''));
        $status = 'SUCCESS';

        if (str_contains($action, 'failed') || str_contains($action, 'denied') || str_contains($action, 'error') || str_contains($action, 'unauthorized')) {
            $status = 'FAILED';
        } elseif (str_contains($action, 'logout') || str_contains($action, 'view') || str_contains($action, 'refresh')) {
            $status = 'INFO';
        } elseif (str_contains($action, 'update') || str_contains($action, 'edit') || str_contains($action, 'create') || str_contains($action, 'add')) {
            $status = 'WARNING';
        }

        $log['status'] = $status;
        $log['display_action'] = preg_replace('/[_-]+/', ' ', (string) $log['action']);
        $log['meta'] = json_decode((string) ($log['meta'] ?? 'null'), true) ?: null;
    }
    unset($log);

    echo json_encode(['logs' => $logs, 'success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to fetch activity logs', 'success' => false]);
}

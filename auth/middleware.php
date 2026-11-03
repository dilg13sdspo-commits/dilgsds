<?php
require_once __DIR__ . '/db.php';
start_secure_session();

function require_same_origin_request() {
    $fetchSite = strtolower($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
    $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    $originParts = parse_url($origin);
    $hostHeader = $_SERVER['HTTP_HOST'] ?? '';
    $hostParts = parse_url('http://' . $hostHeader);
    $requestScheme = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';

    $originHost = strtolower(rtrim((string)($originParts['host'] ?? ''), '.'));
    $requestHost = strtolower(rtrim((string)($hostParts['host'] ?? ''), '.'));
    $originScheme = strtolower((string)($originParts['scheme'] ?? ''));
    $originPort = $originParts['port'] ?? ($originScheme === 'https' ? 443 : 80);
    $requestPort = $hostParts['port'] ?? ($requestScheme === 'https' ? 443 : 80);

    if (
        $fetchSite === 'cross-site'
        || $originHost === ''
        || $requestHost === ''
        || !in_array($originScheme, ['http', 'https'], true)
        || $originHost !== $requestHost
        || $originScheme !== $requestScheme
        || (int)$originPort !== (int)$requestPort
    ) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Cross-origin request rejected']);
        exit;
    }
}

function require_role($allowedRoles = []) {
    if (!is_array($allowedRoles)) $allowedRoles = [$allowedRoles];
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
        require_same_origin_request();
    }

    $role = $_SESSION['role'] ?? null;
    if (empty($_SESSION['user_id']) || !in_array($role, $allowedRoles, true)) {
        // Log unauthorized attempt
        try {
            $pdo = db_connect();
            $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, ip, user_agent, meta) VALUES (NULL, :action, :ip, :ua, :meta)');
            $meta = json_encode(['attempted_role' => $role, 'allowed' => $allowedRoles]);
            $stmt->execute([
                ':action' => 'unauthorized_access',
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                ':meta' => $meta
            ]);
        } catch (Exception $e) {
            // ignore
        }

        // For AJAX/API calls return 401 JSON
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        // Keep portal failures inside the main site's public entry point.
        $scriptBase = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
        $projectRoot = preg_replace('#/user-portals/(fo|po)$#i', '', rtrim($scriptBase, '/'));
        if ($projectRoot !== $scriptBase) {
            $redirectPath = $projectRoot . '/public/pages/index.html';
        } else {
            $redirectPath = rtrim($scriptBase, '/') . '/index.html';
            if ($scriptBase === '/' || $scriptBase === '.') {
                $redirectPath = '/index.html';
            }
        }
        header('Location: ' . $redirectPath, true, 302);
        exit;
    }

    if (!empty($_SESSION['must_change_password'])) {
        $scriptName = strtolower(basename($_SERVER['SCRIPT_NAME'] ?? ''));
        $settingsAction = $_GET['action'] ?? '';
        $passwordChangeAllowed = in_array($scriptName, ['fo-settings.php', 'po-settings.php'], true)
            || (
                $scriptName === 'settings_api.php'
                && (
                    ($settingsAction === 'change_password' && $method === 'POST')
                    || ($method === 'GET' && in_array($settingsAction, ['profile', 'activity_log'], true))
                )
            );
        if (!$passwordChangeAllowed) {
            $isApiRequest = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
                || isset($_SERVER['HTTP_X_REQUESTED_WITH']);
            if ($isApiRequest) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'You must change your temporary password before continuing.']);
                exit;
            }

            $scriptBase = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
            $projectRoot = preg_replace('#/user-portals/(fo|po)$#i', '', rtrim($scriptBase, '/'));
            if ($projectRoot === '' || $projectRoot === '.' || $projectRoot === '/') {
                $projectRoot = '';
            }
            $settingsPage = ($_SESSION['role'] ?? '') === 'PO USER' ? 'po-settings.php' : 'fo-settings.php';
            header('Location: ' . $projectRoot . '/user-portals/' . (($_SESSION['role'] ?? '') === 'PO USER' ? 'po' : 'fo') . '/' . $settingsPage . '?force_password_change=1', true, 302);
            exit;
        }
    }
}

if (!function_exists('get_user_profile_photo')) {
    function get_user_profile_photo(PDO $pdo, int $userId): string {
        try {
            $stmt = $pdo->prepare('SELECT profile_photo FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);
            return trim((string)($stmt->fetchColumn() ?: ''));
        } catch (Throwable $e) {
            return '';
        }
    }
}

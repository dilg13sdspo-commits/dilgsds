<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/middleware.php';
require_role(['FO USER', 'PO USER']);

header('Content-Type: application/json; charset=utf-8');

$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);
$userRole = strtoupper((string)($_SESSION['role'] ?? 'FO USER'));

function ensure_user_settings_columns(PDO $pdo): void {
    $columns = array_map('strtolower', $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN, 0));
    $adds = [
        ['full_name', "ALTER TABLE users ADD COLUMN full_name VARCHAR(150) NULL AFTER email"],
        ['profile_photo', "ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL AFTER full_name"],
        ['contact_number', "ALTER TABLE users ADD COLUMN contact_number VARCHAR(30) NULL AFTER profile_photo"],
        ['email_notifications', "ALTER TABLE users ADD COLUMN email_notifications TINYINT(1) NOT NULL DEFAULT 1 AFTER contact_number"],
        ['language', "ALTER TABLE users ADD COLUMN language VARCHAR(50) NOT NULL DEFAULT 'English' AFTER email_notifications"],
        ['timezone', "ALTER TABLE users ADD COLUMN timezone VARCHAR(80) NOT NULL DEFAULT 'Asia/Manila' AFTER language"],
        ['date_format', "ALTER TABLE users ADD COLUMN date_format VARCHAR(50) NOT NULL DEFAULT 'August 17, 2026' AFTER timezone"],
        ['time_format', "ALTER TABLE users ADD COLUMN time_format VARCHAR(50) NOT NULL DEFAULT '12-hour (AM/PM)' AFTER date_format"],
        ['dashboard_theme', "ALTER TABLE users ADD COLUMN dashboard_theme VARCHAR(30) NOT NULL DEFAULT 'Light' AFTER time_format"],
        ['is_active', "ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER dashboard_theme"],
        ['must_change_password', "ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active"],
    ];

    foreach ($adds as [$column, $sql]) {
        if (!in_array($column, $columns, true)) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $e) {
                // column may already exist
            }
        }
    }
}

function log_settings_audit(PDO $pdo, int $userId, string $action, array $meta = []): void {
    try {
        $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, ip, user_agent, meta) VALUES (:uid, :action, :ip, :ua, :meta)');
        $stmt->execute([
            ':uid' => $userId,
            ':action' => $action,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ':meta' => json_encode($meta),
        ]);
    } catch (Throwable $e) {
        // ignore audit failures
    }
}

function get_last_login(PDO $pdo, int $userId): ?string {
    try {
        $stmt = $pdo->prepare("SELECT created_at FROM audit_logs WHERE user_id = :uid AND action = 'login' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetchColumn();
        return $row ? (string)$row : null;
    } catch (Throwable $e) {
        return null;
    }
}

function clear_remember_tokens(PDO $pdo, int $userId): void {
    try {
        $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = :id')->execute([':id' => $userId]);
    } catch (Throwable $e) {
        // Remember-token storage is optional for installations without persistent login.
    }
}

function get_user_profile(PDO $pdo, int $userId): array {
    ensure_user_settings_columns($pdo);
    $stmt = $pdo->prepare('SELECT id, username, email, role, full_name, profile_photo, contact_number, office_name, office_id, created_at, updated_at, email_notifications, language, timezone, date_format, time_format, dashboard_theme, is_active FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return [];
    }

    $defaultName = strtoupper((string)($user['role'] ?? '')) === 'PO USER' ? 'Provincial Office User' : 'Field Office User';
    $user['full_name'] = trim((string)($user['full_name'] ?? '')) ?: ($user['username'] ?? $defaultName);
    $user['profile_photo'] = trim((string)($user['profile_photo'] ?? '')) ?: '';
    $user['contact_number'] = trim((string)($user['contact_number'] ?? '')) ?: '';
    $user['office_name'] = trim((string)($user['office_name'] ?? '')) ?: 'Field Office';
    $user['email_notifications'] = (int)($user['email_notifications'] ?? 1) === 1;
    $user['language'] = trim((string)($user['language'] ?? 'English')) ?: 'English';
    $user['timezone'] = trim((string)($user['timezone'] ?? 'Asia/Manila')) ?: 'Asia/Manila';
    $user['date_format'] = trim((string)($user['date_format'] ?? 'August 17, 2026')) ?: 'August 17, 2026';
    $user['time_format'] = trim((string)($user['time_format'] ?? '12-hour (AM/PM)')) ?: '12-hour (AM/PM)';
    $user['dashboard_theme'] = trim((string)($user['dashboard_theme'] ?? 'Light')) ?: 'Light';
    $user['is_active'] = (int)($user['is_active'] ?? 1);
    $user['last_login'] = get_last_login($pdo, $userId);
    $user['office_unit'] = strtoupper((string)($user['role'] ?? '')) === 'PO USER' ? 'Provincial Office' : 'Field Office';

    return $user;
}

function avatar_prefix(string $role): string {
    return strtoupper($role) === 'PO USER' ? 'po' : 'fo';
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'profile';

try {
    ensure_user_settings_columns($pdo);

    switch ($action) {
        case 'profile':
            $user = get_user_profile($pdo, $userId);
            echo json_encode(['success' => true, 'user' => $user]);
            break;

        case 'save_profile':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $fullName = trim((string)($input['full_name'] ?? ''));
            $username = trim((string)($input['username'] ?? ''));
            $email = strtolower(trim((string)($input['email'] ?? '')));
            $contactNumber = trim((string)($input['contact_number'] ?? ''));

            if ($fullName === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Full name is required.']);
                exit;
            }
            if ($username === '' || !preg_match("/^[A-Za-z0-9_.@' -]{3,50}$/", $username)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Username must be 3 to 50 characters using letters, numbers, spaces, apostrophes, dots, underscores, and dashes.']);
                exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'A valid email address is required.']);
                exit;
            }
            if ($contactNumber !== '' && !preg_match('/^[0-9+\-\s().]{7,30}$/', $contactNumber)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Please enter a valid contact number.']);
                exit;
            }

            $dupe = $pdo->prepare('SELECT id FROM users WHERE (email = :email OR username = :username) AND id != :uid LIMIT 1');
            $dupe->execute([':email' => $email, ':username' => $username, ':uid' => $userId]);
            if ($dupe->fetch()) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'That username or email is already in use.']);
                exit;
            }

            $stmt = $pdo->prepare('UPDATE users SET full_name = :full_name, username = :username, email = :email, contact_number = :contact_number, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':full_name' => $fullName,
                ':username' => $username,
                ':email' => $email,
                ':contact_number' => $contactNumber !== '' ? $contactNumber : null,
                ':id' => $userId,
            ]);

            $_SESSION['username'] = $username;
            log_settings_audit($pdo, $userId, 'profile_updated', ['fields' => ['full_name', 'username', 'email', 'contact_number']]);

            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'user' => get_user_profile($pdo, $userId),
            ]);
            break;

        case 'upload_avatar':
            if (empty($_FILES['photo']['name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Please choose a photo to upload.']);
                exit;
            }

            $file = $_FILES['photo'];
            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'The photo upload failed. Please try again.']);
                exit;
            }
            if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Profile photo must be 5 MB or smaller.']);
                exit;
            }

            $extensionsByMime = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/webp' => 'webp'];
            $mime = mime_content_type($file['tmp_name']) ?: '';
            if (!isset($extensionsByMime[$mime])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, or WebP images are allowed.']);
                exit;
            }

            $dir = __DIR__ . '/../../../uploads/profile-photos';
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Unable to create the profile photo folder.']);
                exit;
            }

            $prefix = avatar_prefix($userRole);
            $filename = $prefix . '-' . $userId . '-' . bin2hex(random_bytes(6)) . '.' . $extensionsByMime[$mime];
            $target = $dir . '/' . $filename;
            if (!move_uploaded_file($file['tmp_name'], $target)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'The photo could not be uploaded.']);
                exit;
            }

            $relative = '../../uploads/profile-photos/' . $filename;
            $pdo->prepare('UPDATE users SET profile_photo = :photo, updated_at = NOW() WHERE id = :id')->execute([
                ':photo' => $relative,
                ':id' => $userId,
            ]);

            log_settings_audit($pdo, $userId, 'avatar_updated', ['photo' => $relative]);

            echo json_encode(['success' => true, 'message' => 'Profile photo updated.', 'photo' => $relative, 'user' => get_user_profile($pdo, $userId)]);
            break;

        case 'remove_avatar':
            $stmt = $pdo->prepare('SELECT profile_photo FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);
            $existing = trim((string)$stmt->fetchColumn());
            if ($existing !== '') {
                $path = str_starts_with($existing, '../../')
                    ? __DIR__ . '/../../../' . ltrim(substr($existing, 6), '/')
                    : __DIR__ . '/../' . ltrim($existing, '/');
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            $pdo->prepare('UPDATE users SET profile_photo = NULL, updated_at = NOW() WHERE id = :id')->execute([':id' => $userId]);
            log_settings_audit($pdo, $userId, 'avatar_removed');
            echo json_encode(['success' => true, 'message' => 'Profile photo removed.']);
            break;

        case 'save_preferences':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $emailNotifications = !empty($input['email_notifications']) ? 1 : 0;
            $language = in_array((string)($input['language'] ?? 'English'), ['English', 'Filipino', 'Bisaya'], true)
                ? (string)$input['language']
                : 'English';
            $timezone = in_array((string)($input['timezone'] ?? 'Asia/Manila'), ['Asia/Manila', 'UTC', 'Asia/Singapore', 'Asia/Tokyo'], true)
                ? (string)$input['timezone']
                : 'Asia/Manila';
            $dateFormat = in_array((string)($input['date_format'] ?? 'August 17, 2026'), ['August 17, 2026', '17/08/2026', '08/17/2026'], true)
                ? (string)$input['date_format']
                : 'August 17, 2026';
            $timeFormat = in_array((string)($input['time_format'] ?? '12-hour (AM/PM)'), ['12-hour (AM/PM)', '24-hour (HH:MM)'], true)
                ? (string)$input['time_format']
                : '12-hour (AM/PM)';
            $dashboardTheme = in_array((string)($input['dashboard_theme'] ?? 'Light'), ['Light', 'Dark', 'Default'], true)
                ? (string)$input['dashboard_theme']
                : 'Light';

            $stmt = $pdo->prepare('UPDATE users SET email_notifications = :email_notifications, language = :language, timezone = :timezone, date_format = :date_format, time_format = :time_format, dashboard_theme = :dashboard_theme, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':email_notifications' => $emailNotifications,
                ':language' => $language,
                ':timezone' => $timezone,
                ':date_format' => $dateFormat,
                ':time_format' => $timeFormat,
                ':dashboard_theme' => $dashboardTheme,
                ':id' => $userId,
            ]);

            log_settings_audit($pdo, $userId, 'preferences_updated', [
                'language' => $language,
                'timezone' => $timezone,
                'dashboard_theme' => $dashboardTheme,
            ]);

            echo json_encode(['success' => true, 'message' => 'Preferences saved successfully.']);
            break;

        case 'change_password':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $current = (string)($input['current_password'] ?? '');
            $new = (string)($input['new_password'] ?? '');
            $confirm = (string)($input['confirm_password'] ?? '');

            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);
            $hash = $stmt->fetchColumn();

            if (!$hash || !password_verify($current, $hash)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Your current password is incorrect.']);
                exit;
            }

            if (strlen($new) < 8) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long.']);
                exit;
            }
            if (!preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'New password must include both letters and numbers.']);
                exit;
            }
            if ($new !== $confirm) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'The new password and confirmation do not match.']);
                exit;
            }
            if ($current === $new) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Your new password must be different from the current password.']);
                exit;
            }

            $pdo->prepare('UPDATE users SET password_hash = :password_hash, must_change_password = 0, updated_at = NOW() WHERE id = :id')->execute([
                ':password_hash' => password_hash($new, PASSWORD_DEFAULT),
                ':id' => $userId,
            ]);

            $_SESSION['must_change_password'] = false;
            log_settings_audit($pdo, $userId, 'password_changed');
            clear_remember_tokens($pdo, $userId);

            echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
            break;

        case 'activity_log':
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 25)));
            $stmt = $pdo->prepare('SELECT action, ip, user_agent, meta, created_at FROM audit_logs WHERE user_id = :uid ORDER BY created_at DESC LIMIT ' . $limit);
            $stmt->execute([':uid' => $userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $labels = [
                'login' => 'Logged in',
                'logout' => 'Logged out',
                'profile_updated' => 'Profile updated',
                'avatar_updated' => 'Profile photo updated',
                'avatar_removed' => 'Profile photo removed',
                'preferences_updated' => 'Preferences updated',
                'password_changed' => 'Password changed',
                'account_deactivated' => 'Account deactivated',
            ];

            $activities = array_map(static function (array $row) use ($labels) {
                $action = (string)($row['action'] ?? '');
                return [
                    'action' => $action,
                    'label' => $labels[$action] ?? ucwords(str_replace('_', ' ', $action)),
                    'created_at' => $row['created_at'],
                    'ip' => $row['ip'],
                    'device' => substr((string)($row['user_agent'] ?? 'Unknown device'), 0, 80),
                    'meta' => json_decode((string)($row['meta'] ?? ''), true),
                ];
            }, $rows);

            echo json_encode(['success' => true, 'activities' => $activities]);
            break;

        case 'download_data':
            $user = get_user_profile($pdo, $userId);

            $sessionsStmt = $pdo->prepare('SELECT id, selector, created_at, expires_at FROM remember_tokens WHERE user_id = :id ORDER BY created_at DESC');
            $sessionsStmt->execute([':id' => $userId]);
            $sessions = $sessionsStmt->fetchAll(PDO::FETCH_ASSOC);

            $reports = [];
            try {
                $reportsStmt = $pdo->prepare('SELECT id, title, status, priority, category, created_at, updated_at FROM reports WHERE user_id = :uid ORDER BY created_at DESC');
                $reportsStmt->execute([':uid' => $userId]);
                $reports = $reportsStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                // reports table may not exist
            }

            $notifications = [];
            try {
                $notifStmt = $pdo->prepare('SELECT id, title, message, category, type, is_read, created_at FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 100');
                $notifStmt->execute([':uid' => $userId]);
                $notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                // notifications table may not exist
            }

            $activityStmt = $pdo->prepare('SELECT action, created_at, meta FROM audit_logs WHERE user_id = :uid ORDER BY created_at DESC LIMIT 100');
            $activityStmt->execute([':uid' => $userId]);
            $activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

            $payload = [
                'user' => $user,
                'sessions' => $sessions,
                'inquiries' => $reports,
                'notifications' => $notifications,
                'activity_log' => $activities,
                'exported_at' => date('c'),
            ];

            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="dilg-account-data-' . $userId . '.json"');
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            exit;

        case 'sessions':
            $rememberStmt = $pdo->prepare('SELECT selector, created_at, expires_at FROM remember_tokens WHERE user_id = :id ORDER BY created_at DESC LIMIT 10');
            $rememberStmt->execute([':id' => $userId]);
            $sessions = $rememberStmt->fetchAll(PDO::FETCH_ASSOC);

            $payload = [];
            foreach ($sessions as $session) {
                $payload[] = [
                    'label' => 'Saved browser session',
                    'device' => 'Remembered device',
                    'created_at' => $session['created_at'],
                    'expires_at' => $session['expires_at'],
                    'selector' => $session['selector'],
                ];
            }

            if (!$payload) {
                $payload[] = [
                    'label' => 'Current session',
                    'device' => 'This browser',
                    'created_at' => date('Y-m-d H:i:s'),
                    'expires_at' => null,
                    'selector' => 'current-session',
                ];
            }

            echo json_encode(['success' => true, 'sessions' => $payload]);
            break;

        case 'end_other_sessions':
            $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = :id')->execute([':id' => $userId]);
            log_settings_audit($pdo, $userId, 'sessions_cleared');
            echo json_encode(['success' => true, 'message' => 'All saved sessions were cleared.']);
            break;

        case 'deactivate':
            $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = :id')->execute([':id' => $userId]);
            log_settings_audit($pdo, $userId, 'account_deactivated', ['deactivated_at' => date('c')]);
            $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = :id')->execute([':id' => $userId]);
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'Your account has been deactivated.']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unsupported action.']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to process your settings request.']);
}

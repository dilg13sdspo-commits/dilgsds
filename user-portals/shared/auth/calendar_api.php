<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/middleware.php';
require_role(['FO USER', 'PO USER']);

header('Content-Type: application/json; charset=utf-8');

$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);

function ensure_calendar_tables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS calendar_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        office_id INT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        start_at DATETIME NOT NULL,
        end_at DATETIME NOT NULL,
        location VARCHAR(255) NULL,
        category VARCHAR(50) NOT NULL DEFAULT 'Meetings',
        organizer VARCHAR(255) NULL,
        attendees TEXT NULL,
        reminder VARCHAR(50) NULL,
        recurrence VARCHAR(50) NULL,
        source VARCHAR(50) NOT NULL DEFAULT 'local',
        visibility VARCHAR(30) NOT NULL DEFAULT 'private',
        google_event_id VARCHAR(255) NULL,
        calendar_id VARCHAR(255) NULL,
        sync_status VARCHAR(50) NOT NULL DEFAULT 'pending',
        last_synced_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_events (user_id, start_at),
        INDEX idx_google_event (google_event_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $columns = $pdo->query("SHOW COLUMNS FROM calendar_events")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('visibility', $columns, true)) {
        $pdo->exec("ALTER TABLE calendar_events ADD COLUMN visibility VARCHAR(30) NOT NULL DEFAULT 'private' AFTER source");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS google_calendar_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        access_token TEXT NOT NULL,
        refresh_token TEXT NULL,
        expires_at DATETIME NULL,
        scope VARCHAR(255) NULL,
        calendar_id VARCHAR(255) NULL,
        connected_at DATETIME NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS calendar_sync_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        status VARCHAR(50) NOT NULL,
        message TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function ensure_notifications_table(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        office_id INT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        category VARCHAR(50) NOT NULL DEFAULT 'System',
        type VARCHAR(50) NOT NULL DEFAULT 'system',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        related_type VARCHAR(50) NULL,
        related_id INT NULL,
        action_url VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at DATETIME NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_notifications (user_id, is_read, created_at),
        INDEX idx_user_category (user_id, category, created_at),
        INDEX idx_related (related_type, related_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN category VARCHAR(50) NOT NULL DEFAULT 'System' AFTER message");
    } catch (Throwable $e) {}
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'system' AFTER category");
    } catch (Throwable $e) {}
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN related_type VARCHAR(50) NULL AFTER is_read");
    } catch (Throwable $e) {}
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN related_id INT NULL AFTER related_type");
    } catch (Throwable $e) {}
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN action_url VARCHAR(500) NULL AFTER related_id");
    } catch (Throwable $e) {}
}

function google_config(): array {
    return [
        'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
        'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: 'http://127.0.0.1:8000/auth/google_calendar_callback.php',
    ];
}

function json_response($success, $payload = []): void {
    echo json_encode(array_merge(['success' => $success], $payload));
    exit;
}

function normalize_category($value): string {
    $map = [
        'meetings' => 'Meetings',
        'dilg meetings' => 'Meetings',
        'my events' => 'Events',
        'deadlines' => 'Deadlines',
        'deadline' => 'Deadlines',
        'trainings' => 'Trainings',
        'trainings & seminars' => 'Trainings',
        'seminars' => 'Trainings',
        'holidays' => 'Events',
        'holiday' => 'Events',
        'other fo activities' => 'Events',
        'other fo activity' => 'Events',
        'fo activities' => 'Events',
        'events' => 'Events',
    ];
    $normalized = trim((string)$value);
    $lower = strtolower($normalized);
    if (isset($map[$lower])) return $map[$lower];
    if (in_array($normalized, ['Meetings', 'Consultations', 'Programs', 'Deadlines', 'Trainings', 'Events'], true)) return $normalized;
    return 'Events';
}

function normalize_datetime($value): ?string {
    $value = trim((string)$value);
    if ($value === '') return null;
    $date = DateTime::createFromFormat('Y-m-d\TH:i', $value, new DateTimeZone('Asia/Manila'));
    if (!$date) {
        $date = date_create($value, new DateTimeZone('Asia/Manila'));
    }
    return $date ? $date->format('Y-m-d H:i:s') : null;
}

function find_conflict(PDO $pdo, int $userId, string $startAt, string $endAt, ?int $excludeId = null, ?string $role = null): bool {
    $role = strtoupper((string)($role ?? ($_SESSION['role'] ?? '')));
    $sql = 'SELECT id FROM calendar_events WHERE ';
    $params = [':start_at' => $startAt, ':end_at' => $endAt];

    if ($role === 'PO USER') {
        $sql .= '(visibility = :shared_visibility OR user_id = :uid) AND start_at <= :end_at AND end_at >= :start_at';
        $params[':shared_visibility'] = 'shared';
        $params[':uid'] = $userId;
    } else {
        $sql .= 'user_id = :uid AND start_at <= :end_at AND end_at >= :start_at';
        $params[':uid'] = $userId;
    }

    if ($excludeId !== null) {
        $sql .= ' AND id <> :exclude_id';
        $params[':exclude_id'] = $excludeId;
    }
    $sql .= ' LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (bool)$stmt->fetchColumn();
}

function notify_all_po_users(PDO $pdo, int $actorUserId, int $eventId, string $title, string $action): void {
    $role = strtoupper((string)($_SESSION['role'] ?? ''));
    if ($role !== 'PO USER') {
        return;
    }

    $eventDate = date('M d, Y', strtotime($title === '' ? 'now' : 'now'));
    $stmt = $pdo->prepare('SELECT id FROM users WHERE role = :role');
    $stmt->execute([':role' => 'PO USER']);
    $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!$userIds) {
        return;
    }

    $actionText = [
        'created' => 'New shared schedule created',
        'updated' => 'Shared schedule updated',
        'deleted' => 'Shared schedule removed',
    ][$action] ?? 'Calendar update';

    $message = match ($action) {
        'created' => 'A new shared schedule was added: ' . $title,
        'updated' => 'A shared schedule was updated: ' . $title,
        'deleted' => 'A shared schedule was removed: ' . $title,
        default => 'The PO calendar was updated: ' . $title,
    };

    $insert = $pdo->prepare('INSERT INTO notifications (user_id, title, message, category, type, is_read, related_type, related_id, action_url, created_at) VALUES (:user_id, :title, :message, :category, :type, 0, :related_type, :related_id, :action_url, NOW())');
    foreach ($userIds as $targetUserId) {
        $insert->execute([
            ':user_id' => (int)$targetUserId,
            ':title' => $actionText,
            ':message' => $message,
            ':category' => 'Event',
            ':type' => 'calendar_event',
            ':related_type' => 'calendar_event',
            ':related_id' => $eventId,
            ':action_url' => 'po-calendar.php?event=' . $eventId,
        ]);
    }
}

function validate_event_dates($startAt, $endAt): array {
    $start = normalize_datetime($startAt);
    $end = normalize_datetime($endAt);
    if (!$start || !$end) return [null, null, 'Valid start and end dates are required.'];
    if (strtotime($end) < strtotime($start)) return [null, null, 'End date and time cannot be earlier than the start date and time.'];
    return [$start, $end, null];
}

function normalize_status($status): string {
    $status = strtoupper((string)$status);
    return in_array($status, ['PENDING','SUBMITTED','IN PROGRESS','COMPLETED','RESOLVED','REJECTED','RETURNED','OVERDUE','DRAFT'], true) ? $status : 'PENDING';
}

function current_calendar_visibility(): string {
    $role = strtoupper((string)($_SESSION['role'] ?? ''));
    return $role === 'PO USER' ? 'shared' : 'private';
}

ensure_calendar_tables($pdo);
ensure_notifications_table($pdo);

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

if ($action === 'list') {
    $categoryInput = $_GET['category'] ?? 'all';
    $category = strtolower(trim((string)$categoryInput));
    $from = $_GET['from'] ?? null;
    $to = $_GET['to'] ?? null;
    $role = strtoupper((string)($_SESSION['role'] ?? ''));

    if ($role === 'FO USER') {
        $sql = 'SELECT * FROM calendar_events WHERE user_id = :uid OR visibility = :shared_visibility ORDER BY start_at ASC';
        $params = [':uid' => $userId, ':shared_visibility' => 'shared'];
    } elseif ($role === 'PO USER') {
        $sql = 'SELECT * FROM calendar_events WHERE visibility = :shared_visibility OR user_id = :uid ORDER BY start_at ASC';
        $params = [':uid' => $userId, ':shared_visibility' => 'shared'];
    } else {
        $sql = 'SELECT * FROM calendar_events WHERE user_id = :uid ORDER BY start_at ASC';
        $params = [':uid' => $userId];
    }

    if ($category !== '' && strtolower($category) !== 'all') {
        $normalizedCategory = normalize_category($category);
        $sql = preg_replace('/ORDER BY start_at ASC$/', '', $sql);
        $sql .= ' AND category = :category ORDER BY start_at ASC';
        $params[':category'] = $normalizedCategory;
    }

    if ($from) {
        $sql = preg_replace('/ORDER BY start_at ASC$/', '', $sql);
        $sql .= ' AND end_at >= :from ORDER BY start_at ASC';
        $params[':from'] = $from;
    }

    if ($to) {
        $sql = preg_replace('/ORDER BY start_at ASC$/', '', $sql);
        $sql .= ' AND start_at <= :to ORDER BY start_at ASC';
        $params[':to'] = $to;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    json_response(true, ['events' => $events]);
}

if ($action === 'categories') {
    json_response(true, ['categories' => ['All Categories', 'Meetings', 'Consultations', 'Programs', 'Deadlines', 'Trainings', 'Events']]);
}

if ($action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $title = trim((string)($input['title'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    $startAt = trim((string)($input['start_at'] ?? $input['startDate'] ?? ''));
    $endAt = trim((string)($input['end_at'] ?? $input['endDate'] ?? ''));
    $location = trim((string)($input['location'] ?? ''));
    $category = normalize_category($input['category'] ?? 'Meetings');
    $organizer = trim((string)($input['organizer'] ?? ''));
    $attendees = trim((string)($input['attendees'] ?? ''));
    $reminder = trim((string)($input['reminder'] ?? ''));
    $recurrence = trim((string)($input['recurrence'] ?? ''));
    $source = trim((string)($input['source'] ?? 'local'));
    $visibility = current_calendar_visibility();

    [$startAt, $endAt, $dateError] = validate_event_dates($startAt, $endAt);

    if ($title === '' || $dateError) {
        json_response(false, ['error' => $dateError ?: 'Title, start date, and end date are required.']);
    }
    if (find_conflict($pdo, $userId, $startAt, $endAt, null, $_SESSION['role'] ?? '')) {
        json_response(false, ['error' => 'This activity overlaps another activity in your calendar.']);
    }

    $stmt = $pdo->prepare('INSERT INTO calendar_events (user_id, title, description, start_at, end_at, location, category, organizer, attendees, reminder, recurrence, source, visibility, sync_status) VALUES (:uid, :title, :description, :start_at, :end_at, :location, :category, :organizer, :attendees, :reminder, :recurrence, :source, :visibility, :sync_status)');
    $stmt->execute([
        ':uid' => $userId,
        ':title' => $title,
        ':description' => $description,
        ':start_at' => $startAt,
        ':end_at' => $endAt,
        ':location' => $location,
        ':category' => $category,
        ':organizer' => $organizer,
        ':attendees' => $attendees,
        ':reminder' => $reminder,
        ':recurrence' => $recurrence,
        ':source' => $source,
        ':visibility' => $visibility,
        ':sync_status' => $source === 'google' ? 'synced' : 'pending',
    ]);

    $id = (int)$pdo->lastInsertId();
    if (strtoupper((string)($_SESSION['role'] ?? '')) === 'PO USER') {
        notify_all_po_users($pdo, $userId, $id, $title, 'created');
    }
    json_response(true, ['id' => $id, 'message' => 'Event created.']);
}

if ($action === 'update') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        json_response(false, ['error' => 'Event ID is required.']);
    }

    $title = trim((string)($input['title'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    $startAt = trim((string)($input['start_at'] ?? $input['startDate'] ?? ''));
    $endAt = trim((string)($input['end_at'] ?? $input['endDate'] ?? ''));
    $location = trim((string)($input['location'] ?? ''));
    $category = normalize_category($input['category'] ?? 'Meetings');
    $organizer = trim((string)($input['organizer'] ?? ''));
    $attendees = trim((string)($input['attendees'] ?? ''));
    $reminder = trim((string)($input['reminder'] ?? ''));
    $recurrence = trim((string)($input['recurrence'] ?? ''));

    [$startAt, $endAt, $dateError] = validate_event_dates($startAt, $endAt);
    if ($title === '' || $dateError) {
        json_response(false, ['error' => $dateError ?: 'Title, start date, and end date are required.']);
    }
    if (find_conflict($pdo, $userId, $startAt, $endAt, $id, $_SESSION['role'] ?? '')) {
        json_response(false, ['error' => 'This activity overlaps another activity in your calendar.']);
    }

    $visibility = current_calendar_visibility();
    $existingStmt = $pdo->prepare('SELECT title FROM calendar_events WHERE id = :id AND user_id = :uid LIMIT 1');
    $existingStmt->execute([':id' => $id, ':uid' => $userId]);
    $existingTitle = (string)$existingStmt->fetchColumn();

    $stmt = $pdo->prepare('UPDATE calendar_events SET title = :title, description = :description, start_at = :start_at, end_at = :end_at, location = :location, category = :category, organizer = :organizer, attendees = :attendees, reminder = :reminder, recurrence = :recurrence, visibility = :visibility, updated_at = NOW() WHERE id = :id AND user_id = :uid');
    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':start_at' => $startAt,
        ':end_at' => $endAt,
        ':location' => $location,
        ':category' => $category,
        ':organizer' => $organizer,
        ':attendees' => $attendees,
        ':reminder' => $reminder,
        ':recurrence' => $recurrence,
        ':visibility' => $visibility,
        ':id' => $id,
        ':uid' => $userId,
    ]);

    $existsStmt = $pdo->prepare('SELECT id FROM calendar_events WHERE id = :id AND user_id = :uid LIMIT 1');
    $existsStmt->execute([':id' => $id, ':uid' => $userId]);
    if (!$existsStmt->fetchColumn()) json_response(false, ['error' => 'Event not found or not editable.']);

    if (strtoupper((string)($_SESSION['role'] ?? '')) === 'PO USER') {
        notify_all_po_users($pdo, $userId, $id, $existingTitle !== '' ? $existingTitle : $title, 'updated');
    }
    json_response(true, ['message' => 'Event updated.']);
}

if ($action === 'delete') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        json_response(false, ['error' => 'Event ID is required.']);
    }

    $role = strtoupper((string)($_SESSION['role'] ?? ''));
    $existingStmt = $pdo->prepare('SELECT title, user_id, visibility FROM calendar_events WHERE id = :id LIMIT 1');
    $existingStmt->execute([':id' => $id]);
    $existingEvent = $existingStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingEvent) {
        json_response(false, ['error' => 'Event not found.']);
    }

    $isOwnedByCurrentUser = (int)$existingEvent['user_id'] === $userId;
    $isSharedEvent = (string)$existingEvent['visibility'] === 'shared';
    $canDelete = $role === 'PO USER' && ($isOwnedByCurrentUser || $isSharedEvent);

    if (!$canDelete && !$isOwnedByCurrentUser) {
        json_response(false, ['error' => 'You are not allowed to delete this event.']);
    }

    $stmt = $pdo->prepare('DELETE FROM calendar_events WHERE id = :id AND (user_id = :uid OR (:role = :po_role AND visibility = :shared_visibility))');
    $stmt->execute([
        ':id' => $id,
        ':uid' => $userId,
        ':role' => $role,
        ':po_role' => 'PO USER',
        ':shared_visibility' => 'shared',
    ]);

    if ($stmt->rowCount() === 0) {
        json_response(false, ['error' => 'Event not found.']);
    }

    if ($role === 'PO USER') {
        notify_all_po_users($pdo, $userId, $id, (string)($existingEvent['title'] ?? 'Shared schedule'), 'deleted');
    }

    json_response(true, ['message' => 'Event deleted.']);
}

if ($action === 'google_auth_url') {
    $config = google_config();
    if ($config['client_id'] === '') {
        json_response(false, ['error' => 'Google OAuth is not configured on this server.']);
    }

    $params = http_build_query([
        'client_id' => $config['client_id'],
        'redirect_uri' => $config['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/calendar.events',
        'access_type' => 'offline',
        'prompt' => 'consent',
    ]);

    json_response(true, ['url' => 'https://accounts.google.com/o/oauth2/v2/auth?' . $params]);
}

if ($action === 'sync_status') {
    $tokenStmt = $pdo->prepare('SELECT * FROM google_calendar_tokens WHERE user_id = :uid LIMIT 1');
    $tokenStmt->execute([':uid' => $userId]);
    $token = $tokenStmt->fetch();

    if (!$token) {
        json_response(true, ['connected' => false, 'last_synced' => null, 'status' => 'Not Connected']);
    }

    json_response(true, [
        'connected' => true,
        'last_synced' => $token['updated_at'] ?? null,
        'status' => 'Google Calendar Connected',
        'calendar_id' => $token['calendar_id'] ?? null,
    ]);
}

json_response(false, ['error' => 'Unsupported action']);

<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/middleware.php';
require_role(['FO USER', 'PO USER']);

header('Content-Type: application/json; charset=utf-8');

$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);

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
    
    // Add missing columns if they don't exist
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN category VARCHAR(50) NOT NULL DEFAULT 'System' AFTER message");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'system' AFTER category");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN related_type VARCHAR(50) NULL AFTER is_read");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN related_id INT NULL AFTER related_type");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN action_url VARCHAR(500) NULL AFTER related_id");
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN response_status VARCHAR(20) NULL AFTER action_url");
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN response_submitted_at DATETIME NULL AFTER response_status");
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN response_deadline DATETIME NULL AFTER response_submitted_at");
    } catch (Exception $e) {}
}

function seed_demo_notifications(PDO $pdo, int $userId): void {
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid');
    $countStmt->execute([':uid' => $userId]);
    if ((int)$countStmt->fetchColumn() > 0) {
        return;
    }

    $rows = [
        ['Welcome to DILG Provincial Office Dashboard', 'Welcome to the DILG Provincial Office Dashboard. You can manage your inquiries and documents from here.', 'System', 'system', 0, 'system', 0, 'po-dashboard.php', '2026-08-17 10:00:00'],
        ['Inquiry Update', 'Your inquiry regarding "Infrastructure Development" has been updated by the administration.', 'Inquiry', 'inquiry', 0, 'inquiry', 2026, 'po-inquiries.php?view=2026', '2026-08-16 14:30:00'],
        ['New Announcement', 'Office Holiday: Please be informed that our office will be closed on August 21, 2026.', 'Announcement', 'announcement', 1, 'announcement', 2028, 'news.html', '2026-08-16 09:00:00'],
        ['Upcoming Event', 'You have an upcoming event: LGU Consultation on August 20, 2026 at 10:00 AM.', 'Event', 'event', 1, 'event', 2029, 'po-calendar.php?event=2029', '2026-08-15 16:45:00'],
        ['Document Update Required', 'Please update your documents for Inquiry #2026-0084.', 'Document', 'document', 1, 'document', 2030, 'po-documents.php?doc=2030', '2026-08-15 11:20:00'],
        ['New Form Available', 'A new downloadable form is now available for your office requirements.', 'Form', 'form', 1, 'form', 2031, 'programs-and-services.html', '2026-08-14 15:35:00'],
        ['Your Profile Has Been Updated', 'Your account information has been successfully updated.', 'System', 'system', 1, 'system', 0, 'po-settings.php', '2026-08-12 10:10:00'],
        ['Inquiry Completed', 'Your inquiry regarding "Program Participation" has been completed successfully.', 'Inquiry', 'inquiry', 1, 'inquiry', 2033, 'po-inquiries.php?view=2033', '2026-08-11 13:00:00'],
        ['System Maintenance', 'Planned system maintenance will start on August 22, 2026 at 11:00 PM.', 'Alert', 'alert', 0, 'alert', 0, 'po-dashboard.php', '2026-08-10 09:00:00'],
        ['Document Review', 'Your submitted document has been reviewed and is now pending approval.', 'Document', 'document', 0, 'document', 2040, 'po-documents.php?doc=2040', '2026-08-09 12:15:00'],
        ['Meeting Reminder', 'Reminder: Provincial coordination meeting is scheduled today at 3:00 PM.', 'Event', 'event', 0, 'event', 2041, 'po-calendar.php?event=2041', '2026-08-17 08:30:00']
    ];

    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, category, type, is_read, related_type, related_id, action_url, created_at) VALUES (:user_id, :title, :message, :category, :type, :is_read, :related_type, :related_id, :action_url, :created_at)');
    foreach ($rows as $row) {
        $stmt->execute([
            ':user_id' => $userId,
            ':title' => $row[0],
            ':message' => $row[1],
            ':category' => $row[2],
            ':type' => $row[3],
            ':is_read' => $row[4],
            ':related_type' => $row[5],
            ':related_id' => $row[6],
            ':action_url' => $row[7],
            ':created_at' => $row[8]
        ]);
    }
}

function get_time_ago($timestamp) {
    $diff = max(1, time() - $timestamp);
    $minute = 60;
    $hour = 3600;
    $day = 86400;

    if ($diff < $minute) {
        return $diff . ' seconds ago';
    }
    if ($diff < $hour) {
        $mins = floor($diff / $minute);
        return $mins . ' minute' . ($mins === 1 ? '' : 's') . ' ago';
    }
    if ($diff < $day) {
        $hours = floor($diff / $hour);
        return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
    }
    $days = floor($diff / $day);
    return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
}

function apply_tab_filter(string $tab, array &$where, array &$params): void {
    if ($tab === 'all') {
        return;
    }

    switch ($tab) {
        case 'system':
            $where[] = "(n.category = 'System' OR n.type = 'system' OR n.type = 'alert')";
            break;
        case 'inquiry':
        case 'inquiries':
            $where[] = "(n.category = 'Inquiry' OR n.type = 'inquiry' OR n.related_type IN ('inquiry','report'))";
            break;
        case 'fo_response':
        case 'response':
            $where[] = "(n.type = 'fo_response' OR n.type = 'task')";
            break;
        case 'document':
        case 'documents':
            $where[] = "(n.category = 'Document' OR n.type = 'document')";
            break;
        case 'announcement':
            $where[] = "(n.category = 'Announcement' OR n.type = 'announcement')";
            break;
        case 'alert':
            $where[] = "(n.category = 'Alert' OR n.category = 'System' OR n.type = 'alert')";
            break;
        default:
            break;
    }
}

ensure_notifications_table($pdo);

$action = $_GET['action'] ?? ($_POST['action'] ?? 'stats');

if ($action === 'stats') {
    $stats = [
        'total' => 0,
        'unread' => 0,
        'read' => 0
    ];

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid');
    $stmt->execute([':uid' => $userId]);
    $stats['total'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
    $stmt->execute([':uid' => $userId]);
    $stats['unread'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 1');
    $stmt->execute([':uid' => $userId]);
    $stats['read'] = (int)$stmt->fetchColumn();

    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

if ($action === 'list') {
    $tab = $_GET['tab'] ?? 'all';
    $search = trim((string)($_GET['search'] ?? ''));
    $category = trim((string)($_GET['category'] ?? 'all'));
    $status = $_GET['status'] ?? 'all';
    $range = $_GET['range'] ?? 'all';
    $source = trim((string)($_GET['source'] ?? 'all'));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(5, min(50, (int)($_GET['limit'] ?? 8)));
    $offset = ($page - 1) * $limit;

    $where = ['n.user_id = :uid'];
    $params = [':uid' => $userId];

    if ($status === 'unread') {
        $where[] = 'n.is_read = 0';
    } elseif ($status === 'read') {
        $where[] = 'n.is_read = 1';
    }

    apply_tab_filter($tab, $where, $params);

    if ($category !== 'all' && $category !== '') {
        if ($category === 'FO Response') {
            $where[] = "n.type = 'fo_response' AND n.response_status = 'ON_TIME'";
        } elseif ($category === 'Overdue Response') {
            $where[] = "n.type = 'fo_response' AND n.response_status = 'OVERDUE'";
        } else {
            $where[] = 'n.category = :category';
            $params[':category'] = $category;
        }
    }

    if ($source !== 'all' && $source !== '') {
        $where[] = "(n.related_type = :source OR n.category = :source OR n.type = :source)";
        $params[':source'] = $source;
    }

    if ($search !== '') {
        $where[] = '(n.title LIKE :search OR n.message LIKE :search OR n.category LIKE :search OR n.related_type LIKE :search OR n.type LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    if ($range === 'today') {
        $where[] = 'DATE(n.created_at) = CURDATE()';
    } elseif ($range === '7d') {
        $where[] = 'n.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
    } elseif ($range === '30d') {
        $where[] = 'n.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
    } elseif ($range === 'month') {
        $where[] = 'n.created_at >= DATE_FORMAT(NOW(), "%Y-%m-01")';
    }

    $whereSql = implode(' AND ', $where);

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications n WHERE ' . $whereSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $listStmt = $pdo->prepare('SELECT n.*, r.fo_response, r.title AS submission_title, r.priority AS submission_priority, r.due_at AS submission_due_at, r.reference_number AS submission_reference, creator.username AS sender_username, creator.full_name AS sender_name FROM notifications n LEFT JOIN reports r ON n.related_type = \'report\' AND r.id = n.related_id LEFT JOIN users creator ON creator.id = r.user_id WHERE ' . $whereSql . ' ORDER BY n.created_at DESC LIMIT :limit OFFSET :offset');
    foreach ($params as $key => $value) {
        $listStmt->bindValue($key, $value);
    }
    $listStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $listStmt->execute();
    $items = $listStmt->fetchAll(PDO::FETCH_ASSOC);

    $items = array_map(function ($item) {
        $item['time_ago'] = get_time_ago(strtotime($item['created_at']));
        $item['is_read'] = (int)$item['is_read'];
        if ($item['type'] === 'fo_response' && !empty($item['fo_response'])) {
            $item['message'] = preg_replace(
                '/Response \/ Action Taken:\s*.*?(?=\n|$)/s',
                'Response / Action Taken: ' . $item['fo_response'],
                (string)$item['message']
            );
        }
        if ($item['type'] === 'fo_response' && !empty($item['response_status'])) {
            $item['category'] = $item['response_status'] === 'OVERDUE' ? 'Overdue Response' : 'FO Response';
        }
        return $item;
    }, $items);

    echo json_encode([
        'success' => true,
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'has_more' => ($offset + $limit) < $total
    ], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($action === 'mark_read') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($input['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
    } else {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = :uid AND is_read = 0');
        $stmt->execute([':uid' => $userId]);
    }

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'mark_unread') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($input['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 0, read_at = NULL WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = (int)($input['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('DELETE FROM notifications WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'clear_all') {
    $stmt = $pdo->prepare('DELETE FROM notifications WHERE user_id = :uid');
    $stmt->execute([':uid' => $userId]);
    echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
    exit;
}

if ($action === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT n.*, r.fo_response, r.fo_response_at AS report_response_at, r.title AS submission_title, r.priority AS submission_priority, r.due_at AS submission_due_at, r.reference_number AS submission_reference, creator.username AS sender_username, creator.full_name AS sender_name FROM notifications n LEFT JOIN reports r ON n.related_type = \'report\' AND r.id = n.related_id LEFT JOIN users creator ON creator.id = r.user_id WHERE n.id = :id AND n.user_id = :uid LIMIT 1');
    $stmt->execute([':id' => $id, ':uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Notification not found.']);
        exit;
    }
    $row['is_read'] = (int)$row['is_read'];
    if ($row['related_type'] === 'report' && !empty($row['related_id'])) {
        $attachmentStmt = $pdo->prepare('SELECT original_name, file_name, file_path, file_size, mime_type FROM documents WHERE report_id = :report_id ORDER BY created_at DESC');
        $attachmentStmt->execute([':report_id' => (int)$row['related_id']]);
        $row['attachments'] = $attachmentStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $row['attachments'] = [];
    }
    if ($row['type'] === 'fo_response' && !empty($row['fo_response'])) {
        $row['message'] = preg_replace(
            '/Response \/ Action Taken:\s*.*?(?=\n|$)/s',
            'Response / Action Taken: ' . $row['fo_response'],
            (string)$row['message']
        );
    }
    echo json_encode(['success' => true, 'notification' => $row], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($action === 'badge') {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
    $stmt->execute([':uid' => $userId]);
    echo json_encode(['success' => true, 'count' => (int)$stmt->fetchColumn()]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unsupported action']);

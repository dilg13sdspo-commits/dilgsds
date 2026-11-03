<?php
require_once __DIR__ . '/db.php';

function workflow_table_exists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table LIMIT 1');
    $stmt->execute([':table' => $table]);
    return (bool)$stmt->fetchColumn();
}

function workflow_table_usable(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->query('SHOW CREATE TABLE `' . $table . '`');
        return $stmt !== false && $stmt->fetch() !== false;
    } catch (Throwable $e) {
        return false;
    }
}

function workflow_column_exists(PDO $pdo, string $table, string $column): bool {
    if (!workflow_table_exists($pdo, $table) || !workflow_table_usable($pdo, $table)) {
        return false;
    }
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column LIMIT 1');
    $stmt->execute([':table' => $table, ':column' => $column]);
    return (bool)$stmt->fetchColumn();
}

function workflow_ensure_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS offices (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL UNIQUE, office_type ENUM('REGIONAL','MUNICIPALITY','CITY') NOT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    if (!workflow_table_exists($pdo, 'users')) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(150) NULL,
        profile_photo VARCHAR(255) NULL,
        email_notifications TINYINT(1) NOT NULL DEFAULT 1,
        language VARCHAR(50) NOT NULL DEFAULT 'English',
        date_format VARCHAR(50) NOT NULL DEFAULT 'August 17, 2026',
        time_format VARCHAR(50) NOT NULL DEFAULT '12-hour (AM/PM)',
        dashboard_theme VARCHAR(30) NOT NULL DEFAULT 'Light',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        role ENUM('ADMIN','FO USER','PO USER') NOT NULL DEFAULT 'FO USER',
        office_name VARCHAR(150) NULL,
        office_id INT NULL,
        last_login DATETIME NULL,
        must_change_password TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    if (!workflow_table_exists($pdo, 'reports') || !workflow_table_usable($pdo, 'reports')) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec('DROP TABLE IF EXISTS report_recipients');
        $pdo->exec('DROP TABLE IF EXISTS report_responses');
        $pdo->exec('DROP TABLE IF EXISTS task_comments');
        $pdo->exec('DROP TABLE IF EXISTS reports');
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            details TEXT,
            reference_number VARCHAR(40) NULL,
            status ENUM('DRAFT','PENDING','IN_PROGRESS','SUBMITTED','FORWARDED','COMPLETED','RESOLVED','RETURNED','OVERDUE') NOT NULL DEFAULT 'DRAFT',
            submitted_at DATETIME NULL,
            started_at DATETIME NULL,
            completed_at DATETIME NULL,
            reviewed_at DATETIME NULL,
            reviewed_by INT NULL,
            returned_reason TEXT NULL,
            fo_response TEXT NULL,
            fo_response_at DATETIME NULL,
            fo_responded_by INT NULL,
            priority ENUM('LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'MEDIUM',
            due_date DATE NULL,
            due_at DATETIME NULL,
            assigned_to INT NULL,
            recipient_office VARCHAR(150) NULL,
            office_id INT NULL,
            category VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS task_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            user_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_task_comments (report_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS report_responses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            user_id INT NOT NULL,
            office_name VARCHAR(150) NOT NULL,
            response TEXT NOT NULL,
            response_at DATETIME NOT NULL,
            status ENUM('SUBMITTED','OVERDUE','APPROVED','RETURNED') NOT NULL DEFAULT 'SUBMITTED',
            reviewed_at DATETIME NULL,
            reviewed_by INT NULL,
            returned_reason TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_report_response_recipient (report_id, user_id),
            INDEX idx_report_response_report (report_id, status),
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS report_recipients (
            report_id INT NOT NULL,
            user_id INT NOT NULL,
            office_name VARCHAR(150) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (report_id, user_id),
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    if (!workflow_table_exists($pdo, 'documents')) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            report_id INT NULL,
            file_name VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL DEFAULT 0,
            mime_type VARCHAR(120) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    if (!workflow_table_exists($pdo, 'audit_logs')) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action VARCHAR(255) NOT NULL,
            ip VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            meta JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    if (!workflow_column_exists($pdo, 'users', 'office_name')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN office_name VARCHAR(150) NULL AFTER role');
    }
    if (!workflow_column_exists($pdo, 'users', 'office_id')) $pdo->exec('ALTER TABLE users ADD COLUMN office_id INT NULL AFTER office_name');
    if (!workflow_column_exists($pdo, 'users', 'last_login')) $pdo->exec('ALTER TABLE users ADD COLUMN last_login DATETIME NULL AFTER office_id');
    if (!workflow_column_exists($pdo, 'users', 'must_change_password')) $pdo->exec('ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER last_login');
    if (!workflow_column_exists($pdo, 'users', 'full_name')) $pdo->exec('ALTER TABLE users ADD COLUMN full_name VARCHAR(150) NULL');
    if (!workflow_column_exists($pdo, 'users', 'profile_photo')) $pdo->exec('ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL');
    if (!workflow_column_exists($pdo, 'users', 'email_notifications')) $pdo->exec('ALTER TABLE users ADD COLUMN email_notifications TINYINT(1) NOT NULL DEFAULT 1');
    if (!workflow_column_exists($pdo, 'users', 'language')) $pdo->exec("ALTER TABLE users ADD COLUMN language VARCHAR(50) NOT NULL DEFAULT 'English'");
    if (!workflow_column_exists($pdo, 'users', 'date_format')) $pdo->exec("ALTER TABLE users ADD COLUMN date_format VARCHAR(50) NOT NULL DEFAULT 'August 17, 2026'");
    if (!workflow_column_exists($pdo, 'users', 'time_format')) $pdo->exec("ALTER TABLE users ADD COLUMN time_format VARCHAR(50) NOT NULL DEFAULT '12-hour (AM/PM)'");
    if (!workflow_column_exists($pdo, 'users', 'dashboard_theme')) $pdo->exec("ALTER TABLE users ADD COLUMN dashboard_theme VARCHAR(30) NOT NULL DEFAULT 'Light'");
    if (!workflow_column_exists($pdo, 'users', 'is_active')) $pdo->exec('ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1');
    if (!workflow_column_exists($pdo, 'reports', 'submitted_at')) $pdo->exec('ALTER TABLE reports ADD COLUMN submitted_at DATETIME NULL');
    if (!workflow_column_exists($pdo, 'reports', 'due_date')) $pdo->exec('ALTER TABLE reports ADD COLUMN due_date DATE NULL');
    if (!workflow_column_exists($pdo, 'reports', 'assigned_to')) $pdo->exec('ALTER TABLE reports ADD COLUMN assigned_to INT NULL');
    if (!workflow_column_exists($pdo, 'reports', 'priority')) $pdo->exec("ALTER TABLE reports ADD COLUMN priority ENUM('LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'MEDIUM'");
    if (!workflow_column_exists($pdo, 'reports', 'category')) $pdo->exec('ALTER TABLE reports ADD COLUMN category VARCHAR(100) NULL');
    if (!workflow_column_exists($pdo, 'reports', 'recipient_office')) {
        $pdo->exec('ALTER TABLE reports ADD COLUMN recipient_office VARCHAR(150) NULL AFTER assigned_to');
    }
    if (!workflow_column_exists($pdo, 'reports', 'office_id')) {
        $pdo->exec('ALTER TABLE reports ADD COLUMN office_id INT NULL AFTER recipient_office');
    }
    if (!workflow_column_exists($pdo, 'reports', 'reference_number')) {
        $pdo->exec('ALTER TABLE reports ADD COLUMN reference_number VARCHAR(40) NULL AFTER id');
    }
    if (!workflow_column_exists($pdo, 'reports', 'due_at')) {
        $pdo->exec('ALTER TABLE reports ADD COLUMN due_at DATETIME NULL AFTER due_date');
    }
    if (!workflow_column_exists($pdo, 'reports', 'started_at')) {
        $pdo->exec('ALTER TABLE reports ADD COLUMN started_at DATETIME NULL AFTER submitted_at');
    }
    if (!workflow_column_exists($pdo, 'reports', 'completed_at')) {
        $pdo->exec('ALTER TABLE reports ADD COLUMN completed_at DATETIME NULL AFTER started_at');
    }
    if (!workflow_column_exists($pdo, 'reports', 'fo_response')) {
        $pdo->exec('ALTER TABLE reports ADD COLUMN fo_response TEXT NULL AFTER completed_at');
    }
    if (!workflow_column_exists($pdo, 'reports', 'fo_response_at')) {
        $pdo->exec('ALTER TABLE reports ADD COLUMN fo_response_at DATETIME NULL AFTER fo_response');
    }
    if (!workflow_column_exists($pdo, 'reports', 'fo_responded_by')) {
        $pdo->exec('ALTER TABLE reports ADD COLUMN fo_responded_by INT NULL AFTER fo_response_at');
    }
    if (!workflow_column_exists($pdo, 'reports', 'reviewed_at')) {
        $pdo->exec('ALTER TABLE reports ADD COLUMN reviewed_at DATETIME NULL AFTER completed_at');
    }
    if (!workflow_column_exists($pdo, 'reports', 'reviewed_by')) {
        $pdo->exec('ALTER TABLE reports ADD COLUMN reviewed_by INT NULL AFTER reviewed_at');
    }
    if (!workflow_column_exists($pdo, 'reports', 'returned_reason')) {
        $pdo->exec('ALTER TABLE reports ADD COLUMN returned_reason TEXT NULL AFTER reviewed_by');
    }
    if (!workflow_column_exists($pdo, 'documents', 'report_id')) {
        $pdo->exec('ALTER TABLE documents ADD COLUMN report_id INT NULL AFTER user_id');
    }
    $pdo->exec("ALTER TABLE reports MODIFY status ENUM('DRAFT','PENDING','IN_PROGRESS','SUBMITTED','FORWARDED','COMPLETED','RESOLVED','RETURNED','OVERDUE') NOT NULL DEFAULT 'DRAFT'");
    $pdo->exec("UPDATE reports SET due_at = CONCAT(due_date, ' 23:59:59') WHERE due_at IS NULL AND due_date IS NOT NULL");
    $pdo->exec("UPDATE reports SET reference_number = CONCAT('DILG-', YEAR(created_at), '-', LPAD(id, 6, '0')) WHERE reference_number IS NULL OR reference_number = ''");

    $pdo->exec("CREATE TABLE IF NOT EXISTS task_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_id INT NOT NULL,
        user_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_task_comments (report_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS report_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_id INT NOT NULL,
        user_id INT NOT NULL,
        office_name VARCHAR(150) NOT NULL,
        response TEXT NOT NULL,
        response_at DATETIME NOT NULL,
        status ENUM('SUBMITTED','OVERDUE','APPROVED','RETURNED') NOT NULL DEFAULT 'SUBMITTED',
        reviewed_at DATETIME NULL,
        reviewed_by INT NULL,
        returned_reason TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_report_response_recipient (report_id, user_id),
        INDEX idx_report_response_report (report_id, status),
        FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    if (!workflow_column_exists($pdo, 'report_responses', 'reviewed_at')) {
        $pdo->exec('ALTER TABLE report_responses ADD COLUMN reviewed_at DATETIME NULL AFTER status');
    }
    if (!workflow_column_exists($pdo, 'report_responses', 'reviewed_by')) {
        $pdo->exec('ALTER TABLE report_responses ADD COLUMN reviewed_by INT NULL AFTER reviewed_at');
    }
    if (!workflow_column_exists($pdo, 'report_responses', 'returned_reason')) {
        $pdo->exec('ALTER TABLE report_responses ADD COLUMN returned_reason TEXT NULL AFTER reviewed_by');
    }
    $pdo->exec("ALTER TABLE report_responses MODIFY status ENUM('SUBMITTED','OVERDUE','APPROVED','RETURNED') NOT NULL DEFAULT 'SUBMITTED'");
    $pdo->exec("CREATE TABLE IF NOT EXISTS report_recipients (
        report_id INT NOT NULL,
        user_id INT NOT NULL,
        office_name VARCHAR(150) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (report_id, user_id),
        FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        category VARCHAR(50) NOT NULL DEFAULT 'System',
        type VARCHAR(50) NOT NULL DEFAULT 'system',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        related_type VARCHAR(50) NULL,
        related_id INT NULL,
        action_url VARCHAR(500) NULL,
        response_status VARCHAR(20) NULL,
        response_submitted_at DATETIME NULL,
        response_deadline DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at DATETIME NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_workflow_notifications (user_id, is_read, created_at),
        INDEX idx_notification_response (user_id, related_id, response_status, type),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    if (!workflow_column_exists($pdo, 'notifications', 'response_status')) {
        try {
            $pdo->exec('ALTER TABLE notifications ADD COLUMN response_status VARCHAR(20) NULL AFTER action_url');
        } catch (Exception $e) {}
    }
    if (!workflow_column_exists($pdo, 'notifications', 'response_submitted_at')) {
        try {
            $pdo->exec('ALTER TABLE notifications ADD COLUMN response_submitted_at DATETIME NULL AFTER response_status');
        } catch (Exception $e) {}
    }
    if (!workflow_column_exists($pdo, 'notifications', 'response_deadline')) {
        try {
            $pdo->exec('ALTER TABLE notifications ADD COLUMN response_deadline DATETIME NULL AFTER response_submitted_at');
        } catch (Exception $e) {}
    }
}

function workflow_audit(PDO $pdo, int $userId, string $action, int $reportId, array $meta = []): void {
    $meta['report_id'] = $reportId;
    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, ip, user_agent, meta) VALUES (:user_id, :action, :ip, :ua, :meta)');
    $stmt->execute([
        ':user_id' => $userId,
        ':action' => $action,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ':meta' => json_encode($meta),
    ]);
}

function workflow_notify(PDO $pdo, int $userId, string $title, string $message, int $reportId, string $actionUrl): void {
    $userStmt = $pdo->prepare('SELECT id FROM users WHERE id = :user_id AND is_active = 1 LIMIT 1');
    $userStmt->execute([':user_id' => $userId]);
    if (!$userStmt->fetchColumn()) {
        return;
    }

    $existingStmt = $pdo->prepare("SELECT id FROM notifications WHERE user_id = :user_id AND related_type = 'report' AND related_id = :report_id AND type = 'task' AND title = :title LIMIT 1");
    $existingStmt->execute([':user_id' => $userId, ':report_id' => $reportId, ':title' => $title]);
    if ($existingStmt->fetchColumn()) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, category, type, related_type, related_id, action_url, is_read, created_at) VALUES (:user_id, :title, :message, :category, :type, :related_type, :related_id, :action_url, 0, NOW())');
    $stmt->execute([
        ':user_id' => $userId,
        ':title' => $title,
        ':message' => $message,
        ':category' => 'Inquiry',
        ':type' => 'task',
        ':related_type' => 'report',
        ':related_id' => $reportId,
        ':action_url' => $actionUrl,
    ]);
}

function workflow_notify_all_po_users(
    PDO $pdo,
    int $reportId,
    string $title,
    string $message,
    string $actionUrl,
    string $type = 'fo_response',
    ?string $responseStatus = null,
    ?string $responseSubmittedAt = null,
    ?string $responseDeadline = null
): void {
    $poStmt = $pdo->prepare('SELECT id FROM users WHERE role = :role AND is_active = 1 ORDER BY id');
    $poStmt->execute([':role' => 'PO USER']);
    $poUsers = $poStmt->fetchAll(PDO::FETCH_COLUMN);
    if (!$poUsers) {
        return;
    }

    $insert = $pdo->prepare(
        'INSERT INTO notifications (user_id, title, message, category, type, related_type, related_id, action_url, response_status, response_submitted_at, response_deadline, is_read, created_at) VALUES (:user_id, :title, :message, :category, :type, :related_type, :related_id, :action_url, :response_status, :response_submitted_at, :response_deadline, 0, NOW())'
    );

    foreach ($poUsers as $poUserId) {
        $insert->execute([
            ':user_id' => (int)$poUserId,
            ':title' => $title,
            ':message' => $message,
            ':category' => 'Inquiry',
            ':type' => $type,
            ':related_type' => 'report',
            ':related_id' => $reportId,
            ':action_url' => $actionUrl,
            ':response_status' => $responseStatus,
            ':response_submitted_at' => $responseSubmittedAt,
            ':response_deadline' => $responseDeadline,
        ]);
    }
}

/**
 * Create a notification for FO response submission with enhanced details
 * Includes idempotency check to prevent duplicate notifications
 */
function workflow_notify_fo_response(
    PDO $pdo,
    int $poUserId,
    int $reportId,
    string $submissionTitle,
    string $submissionReference,
    string $foUserName,
    DateTime $responseSubmittedAt,
    DateTime $deadline,
    string $responseStatus,
    string $actionUrl,
    string $responseText = ''
): void {
    // Normalize response_status: SUBMITTED → ON_TIME
    $notificationStatus = ($responseStatus === 'SUBMITTED' || $responseStatus === 'ON_TIME') ? 'ON_TIME' : 'OVERDUE';
    
    // Idempotency check: prevent creating duplicate notifications for the same response per PO user.
    // This function is intentionally broadcasted to all PO users, so we only skip if the current PO user already has it.
    $checkStmt = $pdo->prepare(
        'SELECT id FROM notifications WHERE user_id = :user_id AND related_id = :related_id AND related_type = :related_type AND response_status = :response_status AND response_submitted_at = :response_submitted_at AND type = :type LIMIT 1'
    );
    $checkStmt->execute([
        ':user_id' => $poUserId,
        ':related_id' => $reportId,
        ':related_type' => 'report',
        ':response_status' => $notificationStatus,
        ':response_submitted_at' => $responseSubmittedAt->format('Y-m-d H:i:s'),
        ':type' => 'fo_response',
    ]);

    if ($checkStmt->fetchColumn()) {
        return;
    }

    // Calculate time overdue if applicable
    $daysOverdue = 0;
    $hoursOverdue = 0;
    if ($notificationStatus === 'OVERDUE') {
        $interval = $deadline->diff($responseSubmittedAt);
        $daysOverdue = $interval->days;
        $hoursOverdue = $interval->h;
    }
    
    // Build notification title
    $title = 'New Task Received';

    // Build notification message with comprehensive details
    $message = "The FO was sent a submit response.";

    workflow_notify_all_po_users(
        $pdo,
        $reportId,
        $title,
        $message,
        $actionUrl,
        'fo_response',
        $notificationStatus,
        $responseSubmittedAt->format('Y-m-d H:i:s'),
        $deadline->format('Y-m-d H:i:s')
    );
}

function workflow_sync_overdue(PDO $pdo): void {
    $pdo->exec("UPDATE reports SET status = CASE WHEN due_at IS NOT NULL AND fo_response_at > due_at THEN 'OVERDUE' ELSE 'SUBMITTED' END, updated_at = NOW() WHERE (recipient_office IS NULL OR recipient_office <> 'All Municipalities / Cities') AND fo_response_at IS NOT NULL AND status IN ('PENDING','IN_PROGRESS','FORWARDED','OVERDUE')");
    $nowManila = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT r.id, r.user_id, r.assigned_to, r.title, r.reference_number, r.due_at, r.recipient_office FROM reports r WHERE r.due_at IS NOT NULL AND r.due_at < :now_manila AND ((r.recipient_office = 'All Municipalities / Cities' AND EXISTS (SELECT 1 FROM report_recipients rr WHERE rr.report_id = r.id AND NOT EXISTS (SELECT 1 FROM report_responses rp WHERE rp.report_id = r.id AND rp.user_id = rr.user_id))) OR ((r.recipient_office IS NULL OR r.recipient_office <> 'All Municipalities / Cities') AND r.fo_response_at IS NULL AND r.status IN ('PENDING','IN_PROGRESS','FORWARDED')))");
    $stmt->execute([':now_manila' => $nowManila]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$tasks) return;

    $update = $pdo->prepare("UPDATE reports SET status = 'OVERDUE', updated_at = NOW() WHERE id = :id AND due_at < :now_manila AND fo_response_at IS NULL AND status IN ('PENDING','IN_PROGRESS','FORWARDED')");
    foreach ($tasks as $task) {
        if (($task['recipient_office'] ?? '') === 'All Municipalities / Cities') {
            $updateBroadcast = $pdo->prepare("UPDATE reports SET status = 'OVERDUE', updated_at = NOW() WHERE id = :id AND status IN ('PENDING','IN_PROGRESS','FORWARDED')");
            $updateBroadcast->execute([':id' => $task['id']]);
            $recipients = $pdo->prepare('SELECT rr.user_id FROM report_recipients rr WHERE rr.report_id = :id AND NOT EXISTS (SELECT 1 FROM report_responses rp WHERE rp.report_id = rr.report_id AND rp.user_id = rr.user_id)');
            $recipients->execute([':id' => $task['id']]);
            $message = sprintf('%s (%s) passed its deadline and is still incomplete.', $task['title'], $task['reference_number']);
            foreach ($recipients->fetchAll(PDO::FETCH_COLUMN) as $recipientId) workflow_notify($pdo, (int)$recipientId, 'Task Overdue', $message, (int)$task['id'], 'fo-inquiries.php?view=' . (int)$task['id']);
            workflow_notify($pdo, (int)$task['user_id'], 'Task Overdue', $message, (int)$task['id'], 'po-inquiries.php?view=' . (int)$task['id']);
            continue;
        }
        $update->execute([':id' => $task['id'], ':now_manila' => $nowManila]);
        if ($update->rowCount() !== 1) continue;
        workflow_audit($pdo, (int)$task['user_id'], 'task_overdue', (int)$task['id'], ['due_at' => $task['due_at']]);
        $message = sprintf('%s (%s) passed its deadline and is still incomplete.', $task['title'], $task['reference_number']);
        workflow_notify($pdo, (int)$task['user_id'], 'Task Overdue', $message, (int)$task['id'], 'po-inquiries.php?view=' . (int)$task['id']);
        if (!empty($task['assigned_to'])) {
            workflow_notify($pdo, (int)$task['assigned_to'], 'Task Overdue', $message, (int)$task['id'], 'fo-inquiries.php?view=' . (int)$task['id']);
        }
    }
}

function workflow_reference(PDO $pdo, int $id): string {
    return 'DILG-' . date('Y') . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
}

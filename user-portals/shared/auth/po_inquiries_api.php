<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/workflow.php';

require_role('PO USER');

header('Content-Type: application/json');
$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);
workflow_ensure_schema($pdo);
workflow_sync_overdue($pdo);
$action = $_GET['action'] ?? 'list';

function response($success, $payload = []) {
    echo json_encode(array_merge(['success' => $success], $payload));
    exit;
}

if ($action === 'list') {
    $search = trim($_GET['search'] ?? '');
    $statusFilter = $_GET['status'] ?? '';
    $dateRange = $_GET['dateRange'] ?? '';

    $sql = "SELECT r.*, r.reference_number, CASE WHEN r.fo_response_at IS NOT NULL AND r.due_at IS NOT NULL AND r.fo_response_at > r.due_at THEN 'OVERDUE' ELSE r.status END AS effective_status, r.fo_response_at AS response_submitted_at, u.username AS assigned_username, u.full_name AS assigned_name, (SELECT COUNT(*) FROM report_recipients rr WHERE rr.report_id = r.id) AS recipient_count, (SELECT COUNT(*) FROM report_responses rp WHERE rp.report_id = r.id AND rp.response_at IS NOT NULL) AS response_count, (SELECT COUNT(*) FROM report_responses rp WHERE rp.report_id = r.id AND rp.response_at > r.due_at) AS overdue_response_count, (SELECT COUNT(*) FROM report_responses rp WHERE rp.report_id = r.id AND rp.status = 'APPROVED') AS approved_response_count, (SELECT COUNT(*) FROM report_responses rp WHERE rp.report_id = r.id AND rp.status = 'RETURNED') AS returned_response_count, (SELECT GROUP_CONCAT(DISTINCT u2.office_name ORDER BY u2.office_name SEPARATOR ', ') FROM report_recipients rr2 INNER JOIN users u2 ON u2.id = rr2.user_id WHERE rr2.report_id = r.id AND NOT EXISTS (SELECT 1 FROM report_responses rp2 WHERE rp2.report_id = rr2.report_id AND rp2.user_id = rr2.user_id)) AS pending_offices, (SELECT GROUP_CONCAT(DISTINCT rp.office_name ORDER BY rp.office_name SEPARATOR ', ') FROM report_responses rp WHERE rp.report_id = r.id AND rp.response_at IS NOT NULL AND (r.due_at IS NULL OR rp.response_at <= r.due_at) AND rp.status NOT IN ('APPROVED', 'RETURNED')) AS submitted_offices, (SELECT GROUP_CONCAT(DISTINCT rp.office_name ORDER BY rp.office_name SEPARATOR ', ') FROM report_responses rp WHERE rp.report_id = r.id AND rp.response_at > r.due_at AND rp.status <> 'APPROVED' AND rp.status <> 'RETURNED') AS overdue_offices, (SELECT GROUP_CONCAT(DISTINCT rp.office_name ORDER BY rp.office_name SEPARATOR ', ') FROM report_responses rp WHERE rp.report_id = r.id AND rp.status = 'APPROVED') AS approved_offices, (SELECT GROUP_CONCAT(DISTINCT rp.office_name ORDER BY rp.office_name SEPARATOR ', ') FROM report_responses rp WHERE rp.report_id = r.id AND rp.status = 'RETURNED') AS returned_offices FROM reports r LEFT JOIN users u ON u.id = r.assigned_to WHERE EXISTS (SELECT 1 FROM users pu WHERE pu.id = r.user_id AND pu.role = 'PO USER')";
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (title LIKE :search OR details LIKE :search OR category LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    if ($dateRange !== '') {
        if ($dateRange === 'today') {
            $sql .= ' AND DATE(created_at) = CURDATE()';
        } elseif ($dateRange === 'week') {
            $sql .= ' AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)';
        } elseif ($dateRange === 'month') {
            $sql .= ' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())';
        } elseif ($dateRange === 'year') {
            $sql .= ' AND YEAR(created_at) = YEAR(CURDATE())';
        }
    }

    $sql .= ' ORDER BY created_at DESC';

    $stmt = $pdo->prepare($sql);
    $bindIndex = 1;
    foreach ($params as $key => $value) {
        if (is_string($key) && strpos($key, ':') === 0) {
            $stmt->bindValue($key, $value);
        } else {
            $stmt->bindValue($bindIndex, $value, PDO::PARAM_STR);
            $bindIndex++;
        }
    }

    $stmt->execute();
    $inquiries = $stmt->fetchAll();
    foreach ($inquiries as &$inquiry) {
        $inquiry['status'] = $inquiry['effective_status'] ?? $inquiry['status'];
        if ($inquiry['status'] === 'COMPLETED') {
            $inquiry['status'] = 'APPROVED';
        }
        if (($inquiry['recipient_office'] ?? '') === 'All Municipalities / Cities' && (int)$inquiry['recipient_count'] > 0) {
            $pendingCount = (int)$inquiry['recipient_count'] - (int)$inquiry['response_count'];
            $inquiry['status'] = $pendingCount > 0 ? 'PENDING' : ((int)$inquiry['overdue_response_count'] > 0 ? 'OVERDUE' : ((int)$inquiry['approved_response_count'] > 0 ? 'APPROVED' : ((int)$inquiry['returned_response_count'] > 0 ? 'RETURNED' : 'SUBMITTED')));
        }
        if (strtoupper((string)($inquiry['status'] ?? '')) === 'SUBMITTED') {
            $inquiry['status'] = 'IN_PROGRESS';
        }
    }
    unset($inquiry);
    if ($statusFilter !== '') {
        $rawStatuses = array_values(array_filter(array_map('trim', explode(',', $statusFilter))));
        $statuses = [];
        foreach ($rawStatuses as $rawStatus) {
            $normalized = strtoupper($rawStatus);
            if ($normalized === 'UNDER_REVIEW' || $normalized === 'IN_PROGRESS') {
                $statuses[] = 'IN_PROGRESS';
                continue;
            }
            $statuses[] = $normalized;
        }
        if (!empty($statuses)) {
            $inquiries = array_values(array_filter($inquiries, static function ($inquiry) use ($statuses) {
                $status = strtoupper((string)($inquiry['status'] ?? ''));
                return in_array($status, $statuses, true) || (in_array('IN_PROGRESS', $statuses, true) && in_array($status, ['SUBMITTED'], true));
            }));
        }
    }
    response(true, ['inquiries' => $inquiries]);
}

if ($action === 'stats') {
    $statusQuery = "SELECT CASE WHEN r.fo_response_at IS NOT NULL AND r.due_at IS NOT NULL AND r.fo_response_at > r.due_at THEN 'OVERDUE' ELSE r.status END AS status, r.recipient_office, (SELECT COUNT(*) FROM report_recipients rr WHERE rr.report_id = r.id) AS recipient_count, (SELECT COUNT(*) FROM report_responses rp WHERE rp.report_id = r.id AND rp.response_at IS NOT NULL) AS response_count, (SELECT COUNT(*) FROM report_responses rp WHERE rp.report_id = r.id AND rp.response_at > r.due_at) AS overdue_response_count, (SELECT COUNT(*) FROM report_responses rp WHERE rp.report_id = r.id AND rp.status = 'APPROVED') AS approved_response_count, (SELECT COUNT(*) FROM report_responses rp WHERE rp.report_id = r.id AND rp.status = 'RETURNED') AS returned_response_count FROM reports r WHERE EXISTS (SELECT 1 FROM users u WHERE u.id = r.user_id AND u.role = 'PO USER')";
    $stmt = $pdo->prepare($statusQuery);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $stats = [
        'TOTAL' => 0,
        'PENDING' => 0,
        'APPROVED' => 0,
        'IN_PROGRESS' => 0,
        'COMPLETED' => 0,
        'OVERDUE' => 0,
        'RETURNED' => 0,
    ];

    foreach ($rows as $row) {
        $status = strtoupper((string)($row['status'] ?? ''));
        $recipientCount = (int)($row['recipient_count'] ?? 0);

        if (($row['recipient_office'] ?? '') === 'All Municipalities / Cities' && $recipientCount > 0) {
            $pendingCount = max(0, $recipientCount - (int)$row['response_count']);
            $overdueCount = (int)$row['overdue_response_count'];
            $approvedCount = (int)$row['approved_response_count'];
            $returnedCount = (int)$row['returned_response_count'];
            $submittedOnlyCount = max(0, (int)$row['response_count'] - $approvedCount - $returnedCount - $overdueCount);

            $stats['TOTAL'] += $recipientCount;
            $stats['PENDING'] += $pendingCount;
            $stats['APPROVED'] += $approvedCount;
            $stats['OVERDUE'] += $overdueCount;
            $stats['RETURNED'] += $returnedCount;
            $stats['IN_PROGRESS'] += $submittedOnlyCount;
            continue;
        }

        $stats['TOTAL'] += 1;

        if ($status === 'COMPLETED') {
            $status = 'APPROVED';
        }
        if ($status === 'OVERDUE') {
            $stats['OVERDUE']++;
            continue;
        }
        if ($status === 'SUBMITTED' || $status === 'IN_PROGRESS') {
            $stats['IN_PROGRESS']++;
            continue;
        }
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
    }

    response(true, ['stats' => $stats]);
}

if ($action === 'fo_users') {
    $offices = ['All Municipalities / Cities', 'Municipality of Barobo', 'Municipality of Bayabas', 'City of Bislig', 'Municipality of Cagwait', 'Municipality of Cantilan', 'Municipality of Carmen', 'Municipality of Carrascal', 'Municipality of Cortes', 'Municipality of Hinatuan', 'Municipality of Lanuza', 'Municipality of Lianga', 'Municipality of Lingig', 'Municipality of Madrid', 'Municipality of Marihatag', 'Municipality of San Agustin', 'Municipality of San Miguel', 'Municipality of Tagbina', 'Municipality of Tago', 'City of Tandag'];
    $stmt = $pdo->query("SELECT id, username, full_name, office_name FROM users WHERE role = 'FO USER' AND is_active = 1 ORDER BY full_name, username");
    response(true, ['offices' => $offices, 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'create') {
    $data = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
    $title = trim((string)($data['title'] ?? ''));
    $details = trim((string)($data['details'] ?? ''));
    $priority = strtoupper(trim((string)($data['priority'] ?? 'MEDIUM')));
    $foId = (int)($data['assigned_to'] ?? 0);
    $recipientOffice = trim((string)($data['recipient_office'] ?? ''));
    $dueAt = trim((string)($data['due_at'] ?? $data['due_date'] ?? ''));
    if (!in_array($priority, ['LOW', 'MEDIUM', 'HIGH'], true)) {
        $priority = 'MEDIUM';
    }

    $parsedDueAt = DateTime::createFromFormat('Y-m-d\\TH:i', $dueAt) ?: DateTime::createFromFormat('Y-m-d H:i', $dueAt);
    if ($title === '' || $details === '' || !$parsedDueAt) {
        response(false, ['error' => 'Enter a valid due date and time']);
    }
    if ($recipientOffice === '') response(false, ['error' => 'Select an office before saving the submission.']);
    $allowedOffices = ['All Municipalities / Cities', 'Municipality of Barobo', 'Municipality of Bayabas', 'City of Bislig', 'Municipality of Cagwait', 'Municipality of Cantilan', 'Municipality of Carmen', 'Municipality of Carrascal', 'Municipality of Cortes', 'Municipality of Hinatuan', 'Municipality of Lanuza', 'Municipality of Lianga', 'Municipality of Lingig', 'Municipality of Madrid', 'Municipality of Marihatag', 'Municipality of San Agustin', 'Municipality of San Miguel', 'Municipality of Tagbina', 'Municipality of Tago', 'City of Tandag'];
    if (!in_array($recipientOffice, $allowedOffices, true)) response(false, ['error' => 'Selected office is unavailable.']);
    if ($foId <= 0) {
        $foStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'FO USER' AND is_active = 1 AND office_id IS NOT NULL AND (:office = 'All Municipalities / Cities' OR office_name = :office) ORDER BY id LIMIT 1");
        $foStmt->execute([':office' => $recipientOffice]);
        $foId = (int)$foStmt->fetchColumn();
    }
    if ($foId <= 0) response(false, ['error' => 'No active FO user is configured for the selected office.']);
    $foStmt = $pdo->prepare("SELECT id, office_name FROM users WHERE id = :id AND role = 'FO USER' AND is_active = 1 LIMIT 1");
    $foStmt->execute([':id' => $foId]);
    $foUser = $foStmt->fetch(PDO::FETCH_ASSOC);
    if (!$foUser || ($recipientOffice !== 'All Municipalities / Cities' && $foUser['office_name'] !== $recipientOffice)) response(false, ['error' => 'Selected FO user is not assigned to that office.']);
    $dueAtSql = $parsedDueAt->format('Y-m-d H:i:s');

    $pdo->beginTransaction();
    $storedFiles = [];
    try {
        $stmt = $pdo->prepare("INSERT INTO reports (user_id, title, details, status, priority, due_date, due_at, assigned_to, recipient_office, submitted_at, created_at) VALUES (:user_id, :title, :details, 'PENDING', :priority, :due_date, :due_at, :assigned_to, :recipient_office, NOW(), NOW())");
        $stmt->execute([
            ':user_id' => $userId,
            ':title' => $title,
            ':details' => $details,
            ':priority' => $priority,
            ':due_date' => $parsedDueAt->format('Y-m-d'),
            ':due_at' => $dueAtSql,
            ':assigned_to' => $foId,
            ':recipient_office' => $recipientOffice,
        ]);
        $id = (int)$pdo->lastInsertId();
        $recipientStmt = $recipientOffice === 'All Municipalities / Cities'
            ? $pdo->query("SELECT id FROM users WHERE role = 'FO USER' AND is_active = 1 AND office_id IS NOT NULL")
            : null;
        $recipientUsers = $recipientStmt ? $recipientStmt->fetchAll(PDO::FETCH_COLUMN) : [$foId];
        $recipientInsert = $pdo->prepare('INSERT INTO report_recipients (report_id, user_id, office_name) VALUES (:report_id, :user_id, :office_name)');
        foreach ($recipientUsers as $recipientUserId) {
            $recipientInsert->execute([':report_id' => $id, ':user_id' => (int)$recipientUserId, ':office_name' => $recipientOffice]);
        }
        $reference = workflow_reference($pdo, $id);
        $pdo->prepare('UPDATE reports SET reference_number = :reference WHERE id = :id')->execute([':reference' => $reference, ':id' => $id]);
        workflow_audit($pdo, $userId, 'task_created_assigned', $id, ['assigned_to' => $foId, 'status' => 'PENDING']);
        foreach ($recipientUsers as $recipientUserId) {
            workflow_notify($pdo, (int)$recipientUserId, 'New Task Received', '"' . $title . '" was assigned to you by the PO User. Priority: ' . $priority . '. Due: ' . $parsedDueAt->format('F j, Y g:i A'), $id, 'fo-inquiries.php?view=' . $id);
        }
        $files = $_FILES['documents'] ?? [];
        $mimeByExtension = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        ];
        if (!empty($files['name']) && is_array($files['name'])) {
            $uploadDir = dirname(__DIR__) . '/uploads';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) throw new RuntimeException('Could not create the upload directory.');
            $documentStmt = $pdo->prepare('INSERT INTO documents (user_id, report_id, file_name, original_name, file_path, file_size, mime_type) VALUES (:uid, :report_id, :file_name, :original_name, :path, :size, :mime)');
            foreach ($files['name'] as $index => $originalName) {
                $uploadError = $files['error'][$index] ?? UPLOAD_ERR_NO_FILE;
                if ($uploadError === UPLOAD_ERR_NO_FILE || $originalName === '') continue;
                if ($uploadError !== UPLOAD_ERR_OK) throw new RuntimeException('One of the attachments could not be uploaded.');
                $fileSize = (int)($files['size'][$index] ?? 0);
                if ($fileSize > 10 * 1024 * 1024) throw new RuntimeException('Each attachment must be 10 MB or smaller.');
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($files['tmp_name'][$index]);
                if (!isset($mimeByExtension[$extension]) || $mimeType !== $mimeByExtension[$extension]) throw new RuntimeException('Attachments must be PDF, DOC, DOCX, JPG, or PNG files.');
                $targetName = bin2hex(random_bytes(16)) . '.' . $extension;
                $targetFile = $uploadDir . '/' . $targetName;
                if (!move_uploaded_file($files['tmp_name'][$index], $targetFile)) throw new RuntimeException('Could not save one of the attachments.');
                $storedFiles[] = $targetFile;
                $documentStmt->execute([':uid' => $userId, ':report_id' => $id, ':file_name' => $targetName, ':original_name' => basename($originalName), ':path' => 'uploads/' . $targetName, ':size' => $fileSize, ':mime' => $mimeType]);
            }
        }
        $pdo->commit();
        response(true, ['id' => $id, 'reference_number' => $reference]);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        foreach ($storedFiles as $storedFile) if (is_file($storedFile)) @unlink($storedFile);
        response(false, ['error' => $error->getMessage()]);
    }
}

if ($action === 'submit') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true) ?: [];
    $id = (int)($data['id'] ?? 0);

    if ($id <= 0) {
        response(false, ['error' => 'Inquiry ID is required']);
    }

    $stmt = $pdo->prepare('SELECT id, status, title, priority, due_at, user_id, assigned_to, reference_number FROM reports WHERE id = :id AND EXISTS (SELECT 1 FROM users u WHERE u.id = reports.user_id AND u.role = "PO USER") LIMIT 1');
    $stmt->execute([':id' => $id]);
    $inquiry = $stmt->fetch();

    if (!$inquiry) {
        response(false, ['error' => 'Inquiry not found']);
    }

    if (in_array($inquiry['status'], ['DRAFT', 'PENDING', 'RETURNED'], true)) {
            $foId = (int)($data['assigned_to'] ?? 0);
            if ($foId <= 0) {
                $foStmt = $pdo->query("SELECT id FROM users WHERE role = 'FO USER' AND is_active = 1 ORDER BY id LIMIT 1");
                $foId = (int)$foStmt->fetchColumn();
            }
            $foStmt = $pdo->prepare("SELECT id FROM users WHERE id = :id AND role = 'FO USER' AND is_active = 1 LIMIT 1");
            $foStmt->execute([':id' => $foId]);
            if (!$foStmt->fetchColumn()) response(false, ['error' => 'Selected FO user is unavailable.']);
            if ($foId <= 0) response(false, ['error' => 'No active FO user is available for assignment.']);
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE reports SET status = 'PENDING', assigned_to = :assigned_to, submitted_at = NOW(), updated_at = NOW() WHERE id = :id AND EXISTS (SELECT 1 FROM users u WHERE u.id = reports.user_id AND u.role = 'PO USER') AND status IN ('DRAFT','PENDING','RETURNED')");
                $stmt->execute([':assigned_to' => $foId, ':id' => $id]);
                if ($stmt->rowCount() !== 1) throw new RuntimeException('Inquiry cannot be submitted in its current state.');
                workflow_audit($pdo, $userId, 'task_submitted_assigned', $id, ['assigned_to' => $foId]);
                workflow_notify($pdo, $foId, 'New Task Received', '"' . $inquiry['title'] . '" was assigned to you by the PO User. Priority: ' . ($inquiry['priority'] ?? 'MEDIUM') . '. Due: ' . date('F j, Y g:i A', strtotime($inquiry['due_at'])), $id, 'fo-inquiries.php?view=' . $id);
                $pdo->commit();
                response(true, ['message' => 'Inquiry submitted and assigned']);
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                response(false, ['error' => $error->getMessage()]);
            }
    }

    response(false, ['error' => 'Inquiry cannot be submitted from its current status']);
}

if ($action === 'update') {
    $json = file_get_contents('php://input');
    $data = $_POST ?: (json_decode($json, true) ?: []);
    $id = (int)($data['id'] ?? 0);
    $title = trim((string)($data['title'] ?? ''));
    $details = trim((string)($data['details'] ?? ''));
    $category = trim((string)($data['category'] ?? ''));
    $priority = strtoupper(trim((string)($data['priority'] ?? 'MEDIUM')));
    $foId = (int)($data['assigned_to'] ?? 0);
    $recipientOffice = trim((string)($data['recipient_office'] ?? ''));
    $dueAt = trim((string)($data['due_at'] ?? $data['due_date'] ?? ''));

    if ($id <= 0 || $title === '' || $details === '') {
        response(false, ['error' => 'Valid inquiry details are required']);
    }

    if (!in_array($priority, ['LOW', 'MEDIUM', 'HIGH'], true)) {
        $priority = 'MEDIUM';
    }

    $parsedDueAt = DateTime::createFromFormat('Y-m-d\\TH:i', $dueAt) ?: DateTime::createFromFormat('Y-m-d H:i', $dueAt);
    if (!$parsedDueAt || $recipientOffice === '') {
        response(false, ['error' => 'Enter a valid due date and select an office.']);
    }
    $allowedOffices = ['All Municipalities / Cities', 'Municipality of Barobo', 'Municipality of Bayabas', 'City of Bislig', 'Municipality of Cagwait', 'Municipality of Cantilan', 'Municipality of Carmen', 'Municipality of Carrascal', 'Municipality of Cortes', 'Municipality of Hinatuan', 'Municipality of Lanuza', 'Municipality of Lianga', 'Municipality of Lingig', 'Municipality of Madrid', 'Municipality of Marihatag', 'Municipality of San Agustin', 'Municipality of San Miguel', 'Municipality of Tagbina', 'Municipality of Tago', 'City of Tandag'];
    if (!in_array($recipientOffice, $allowedOffices, true)) response(false, ['error' => 'Selected office is unavailable.']);
    if ($foId <= 0) {
        $foLookup = $pdo->prepare("SELECT id FROM users WHERE role = 'FO USER' AND is_active = 1 AND office_id IS NOT NULL AND (:office = 'All Municipalities / Cities' OR office_name = :office) ORDER BY id LIMIT 1");
        $foLookup->execute([':office' => $recipientOffice]);
        $foId = (int)$foLookup->fetchColumn();
    }
    if ($foId <= 0) response(false, ['error' => 'No active FO user is configured for the selected office.']);
    $foStmt = $pdo->prepare("SELECT id, office_name FROM users WHERE id = :id AND role = 'FO USER' AND is_active = 1 LIMIT 1");
    $foStmt->execute([':id' => $foId]);
    $foUser = $foStmt->fetch(PDO::FETCH_ASSOC);
    if (!$foUser || ($recipientOffice !== 'All Municipalities / Cities' && $foUser['office_name'] !== $recipientOffice)) response(false, ['error' => 'Selected FO user is not assigned to that office.']);

    $editableStmt = $pdo->prepare('SELECT id, title, due_at, assigned_to, category, fo_response_at FROM reports WHERE id = :id AND EXISTS (SELECT 1 FROM users u WHERE u.id = reports.user_id AND u.role = "PO USER") AND status NOT IN ("COMPLETED", "RESOLVED") LIMIT 1');
    $editableStmt->execute([':id' => $id]);
    $existing = $editableStmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) response(false, ['error' => 'Inquiry cannot be edited in its current state']);

    $category = $category !== '' ? $category : (string)($existing['category'] ?? '');
    $dueAtSql = $parsedDueAt->format('Y-m-d H:i:s');
    $responseStatusSql = !empty($existing['fo_response_at'])
        ? ", status = CASE WHEN fo_response_at > :new_due_at_status THEN 'OVERDUE' ELSE 'SUBMITTED' END"
        : '';

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE reports SET title = :title, details = :details, category = :category, priority = :priority, due_date = :due_date, due_at = :due_at, assigned_to = :assigned_to, recipient_office = :recipient_office, updated_at = NOW()' . $responseStatusSql . ' WHERE id = :id AND EXISTS (SELECT 1 FROM users u WHERE u.id = reports.user_id AND u.role = "PO USER") AND status NOT IN ("COMPLETED", "RESOLVED")');
        $parameters = [
            ':title' => $title,
            ':details' => $details,
            ':category' => $category,
            ':priority' => $priority,
            ':due_date' => $parsedDueAt->format('Y-m-d'),
            ':due_at' => $dueAtSql,
            ':assigned_to' => $foId,
            ':recipient_office' => $recipientOffice,
            ':id' => $id,
        ];
        if ($responseStatusSql !== '') $parameters[':new_due_at_status'] = $dueAtSql;
        $stmt->execute($parameters);

        if ($stmt->rowCount() > 0) {
            workflow_audit($pdo, $userId, 'submission_updated', $id, ['assigned_to' => $foId, 'fo_response_preserved' => !empty($existing['fo_response_at'])]);
            workflow_notify($pdo, $foId, 'Submission Updated by PO User', 'The PO User updated submission "' . $title . '". Please review the updated details and due date.', $id, 'fo-inquiries.php?view=' . $id);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        response(false, ['error' => $error->getMessage()]);
    }

    response(true, ['message' => 'Inquiry updated']);
}

if ($action === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        response(false, ['error' => 'Inquiry ID is required']);
    }

    $stmt = $pdo->prepare('SELECT * FROM reports WHERE id = :id AND EXISTS (SELECT 1 FROM users u WHERE u.id = reports.user_id AND u.role = "PO USER") LIMIT 1');
    $stmt->execute([':id' => $id]);
    $inquiry = $stmt->fetch();
    if (!$inquiry) {
        response(false, ['error' => 'Inquiry not found']);
    }

    $documentStmt = $pdo->prepare('SELECT d.id, d.original_name, d.file_name, d.file_path, d.file_size, d.mime_type, d.created_at, d.user_id, u.full_name AS uploader_name, u.username AS uploader_username, u.office_name AS uploader_office FROM documents d LEFT JOIN users u ON u.id = d.user_id WHERE d.report_id = :id ORDER BY d.created_at DESC');
    $documentStmt->execute([':id' => $id]);
    $attachments = $documentStmt->fetchAll(PDO::FETCH_ASSOC);
    $inquiry['po_attachments'] = array_values(array_filter($attachments, static fn($file) => (int)$file['user_id'] === (int)$inquiry['user_id']));
    $inquiry['response_attachments'] = array_values(array_filter($attachments, static fn($file) => (int)$file['user_id'] !== (int)$inquiry['user_id']));
    $inquiry['attachments'] = $attachments;
    if (!empty($inquiry['fo_responded_by'])) {
        $foStmt = $pdo->prepare('SELECT username, full_name, office_name FROM users WHERE id = :id LIMIT 1');
        $foStmt->execute([':id' => (int)$inquiry['fo_responded_by']]);
        $inquiry['responded_by_user'] = $foStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    $responseColumns = ['rr.response', 'rr.response_at', "COALESCE(rr.status, CASE WHEN r.due_at IS NOT NULL AND rr.response_at > r.due_at THEN 'OVERDUE' ELSE 'SUBMITTED' END) AS status", 'rr.office_name', 'rr.user_id', 'u.username', 'u.full_name'];
    $reportResponseColumns = $pdo->query('SHOW COLUMNS FROM report_responses')->fetchAll(PDO::FETCH_COLUMN);
    if (is_array($reportResponseColumns) && in_array('returned_reason', $reportResponseColumns, true)) {
        $responseColumns[] = 'rr.returned_reason';
    }
    if (is_array($reportResponseColumns) && in_array('return_reason', $reportResponseColumns, true)) {
        $responseColumns[] = 'rr.return_reason';
    }
    $responseSelect = implode(', ', $responseColumns);
    $responseStmt = $pdo->prepare("SELECT {$responseSelect} FROM report_responses rr INNER JOIN reports r ON r.id = rr.report_id INNER JOIN users u ON u.id = rr.user_id WHERE rr.report_id = :id ORDER BY rr.response_at DESC");
    $responseStmt->execute([':id' => $id]);
    $inquiry['recipient_responses'] = $responseStmt->fetchAll(PDO::FETCH_ASSOC);
    $responseDocumentStmt = $pdo->prepare('SELECT id, original_name, file_name, file_size, mime_type, created_at FROM documents WHERE report_id = :report_id AND user_id = :user_id ORDER BY created_at DESC');
    foreach ($inquiry['recipient_responses'] as &$recipientResponse) {
        $responseDocumentStmt->execute([':report_id' => $id, ':user_id' => (int)$recipientResponse['user_id']]);
        $recipientResponse['documents'] = $responseDocumentStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($recipientResponse);
    $hasStoredRecipientResponse = (bool)array_filter($inquiry['recipient_responses'], static fn($item) => !empty($item['response_at']) || trim((string)($item['response'] ?? '')) !== '');
    if (($inquiry['recipient_office'] ?? '') !== 'All Municipalities / Cities' && !empty($inquiry['fo_response_at']) && !$hasStoredRecipientResponse) {
        $foId = (int)($inquiry['fo_responded_by'] ?? $inquiry['assigned_to'] ?? 0);
        $foStmt = $pdo->prepare('SELECT id, username, full_name, office_name FROM users WHERE id = :id AND role = \'FO USER\' LIMIT 1');
        $foStmt->execute([':id' => $foId]);
        $fo = $foStmt->fetch(PDO::FETCH_ASSOC);
        if ($fo) {
            $responseDocumentStmt->execute([':report_id' => $id, ':user_id' => $foId]);
            $inquiry['recipient_responses'][] = [
                'response' => (string)($inquiry['fo_response'] ?? ''),
                'response_at' => $inquiry['fo_response_at'],
                'status' => in_array(strtoupper((string)$inquiry['status']), ['COMPLETED', 'RESOLVED'], true)
                    ? 'APPROVED'
                    : (strtotime((string)$inquiry['fo_response_at']) > strtotime((string)($inquiry['due_at'] ?? '')) ? 'OVERDUE' : 'SUBMITTED'),
                'office_name' => $fo['office_name'] ?: $inquiry['recipient_office'],
                'user_id' => $foId,
                'username' => $fo['username'],
                'full_name' => $fo['full_name'],
                'documents' => $responseDocumentStmt->fetchAll(PDO::FETCH_ASSOC),
            ];
        }
    }
    $pendingStmt = $pdo->prepare("SELECT NULL AS response, NULL AS response_at, 'PENDING' AS status, COALESCE(u.office_name, rr.office_name) AS office_name, u.username, u.full_name FROM report_recipients rr INNER JOIN users u ON u.id = rr.user_id LEFT JOIN report_responses existing ON existing.report_id = rr.report_id AND existing.user_id = rr.user_id WHERE rr.report_id = :id AND existing.id IS NULL ORDER BY office_name ASC");
    $pendingStmt->execute([':id' => $id]);
    $inquiry['recipient_responses'] = array_merge($inquiry['recipient_responses'], $pendingStmt->fetchAll(PDO::FETCH_ASSOC));
    if (($inquiry['recipient_office'] ?? '') === 'All Municipalities / Cities') {
        $recipientCount = count($inquiry['recipient_responses']);
        $overdueCount = count(array_filter($inquiry['recipient_responses'], static fn($item) => strtoupper((string)($item['status'] ?? '')) === 'OVERDUE'));
        $submittedCount = count(array_filter($inquiry['recipient_responses'], static fn($item) => strtoupper((string)($item['status'] ?? '')) === 'SUBMITTED'));
        $inquiry['status'] = $recipientCount > $submittedCount + $overdueCount ? 'PENDING' : ($overdueCount > 0 ? 'OVERDUE' : 'SUBMITTED');
    } elseif (!empty($inquiry['fo_response_at']) && !empty($inquiry['due_at'])) {
        $inquiry['status'] = strtotime((string)$inquiry['fo_response_at']) > strtotime((string)$inquiry['due_at']) ? 'OVERDUE' : 'SUBMITTED';
    }

    response(true, ['inquiry' => $inquiry]);
}

if ($action === 'complete_response') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true) ?: [];
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) response(false, ['error' => 'Inquiry ID is required']);
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = :id AND EXISTS (SELECT 1 FROM users u WHERE u.id = reports.user_id AND u.role = 'PO USER') AND fo_response_at IS NOT NULL AND status IN ('SUBMITTED', 'OVERDUE') LIMIT 1");
    $stmt->execute([':id' => $id]);
    $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$inquiry) response(false, ['error' => 'The FO response must be submitted before this inquiry can be completed.']);
    $pdo->beginTransaction();
    try {
        $update = $pdo->prepare("UPDATE reports SET status = 'COMPLETED', updated_at = NOW() WHERE id = :id AND EXISTS (SELECT 1 FROM users u WHERE u.id = reports.user_id AND u.role = 'PO USER') AND status IN ('SUBMITTED', 'OVERDUE') AND fo_response_at IS NOT NULL");
        $update->execute([':id' => $id]);
        if ($update->rowCount() !== 1) throw new RuntimeException('This inquiry was already marked as done.');
        workflow_audit($pdo, $userId, 'fo_response_completed', $id, ['status' => 'COMPLETED']);
        if (!empty($inquiry['assigned_to'])) workflow_notify($pdo, (int)$inquiry['assigned_to'], 'Inquiry Completed', 'PO User marked the response done for "' . $inquiry['title'] . '".', $id, 'fo-inquiries.php?view=' . $id);
        $pdo->commit();
        response(true, ['status' => 'COMPLETED']);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        response(false, ['error' => $error->getMessage()]);
    }
}

if ($action === 'delete') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true) ?: [];
    $id = (int)($data['id'] ?? 0);

    if ($id <= 0) {
        response(false, ['error' => 'Inquiry ID is required']);
    }

    $checkStmt = $pdo->prepare('SELECT id FROM reports WHERE id = :id AND EXISTS (SELECT 1 FROM users u WHERE u.id = reports.user_id AND u.role = "PO USER") LIMIT 1');
    $checkStmt->execute([':id' => $id]);
    if (!$checkStmt->fetch()) {
        response(false, ['error' => 'Inquiry not found']);
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM notifications WHERE related_type = :related_type AND related_id = :id')->execute([':related_type' => 'report', ':id' => $id]);
        $pdo->prepare('DELETE FROM documents WHERE report_id = :id')->execute([':id' => $id]);
        $pdo->prepare('DELETE FROM report_responses WHERE report_id = :id')->execute([':id' => $id]);
        $pdo->prepare('DELETE FROM report_recipients WHERE report_id = :id')->execute([':id' => $id]);
        $pdo->prepare('DELETE FROM audit_logs WHERE JSON_UNQUOTE(JSON_EXTRACT(meta, "$.report_id")) = :id')->execute([':id' => (string)$id]);

        $deleteStmt = $pdo->prepare('DELETE FROM reports WHERE id = :id AND EXISTS (SELECT 1 FROM users u WHERE u.id = reports.user_id AND u.role = "PO USER")');
        $deleteStmt->execute([':id' => $id]);

        if ($deleteStmt->rowCount() === 0) {
            throw new RuntimeException('Inquiry not found');
        }

        $pdo->commit();
        response(true, ['message' => 'Inquiry deleted']);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        response(false, ['error' => $error->getMessage()]);
    }
}

response(false, ['error' => 'Invalid action']);

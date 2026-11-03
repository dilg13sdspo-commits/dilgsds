<?php
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/workflow.php';
require_role('FO USER');
header('Content-Type: application/json; charset=utf-8');

$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);
workflow_ensure_schema($pdo);
workflow_sync_overdue($pdo);

function task_response(bool $success, array $payload = []): void {
    echo json_encode(array_merge(['success' => $success], $payload));
    exit;
}

function task_document_mime(string $path, string $extension): ?string {
    $mimeByExtension = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
    ];
    if (!isset($mimeByExtension[$extension])) return null;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    return $mime === $mimeByExtension[$extension] ? $mime : null;
}

function is_broadcast_task(array $task): bool {
    return ($task['recipient_office'] ?? '') === 'All Municipalities / Cities';
}

function get_assigned_task(PDO $pdo, int $taskId, int $userId): array {
    $stmt = $pdo->prepare("SELECT r.*, r.reference_number, creator.username AS creator_username, creator.full_name AS creator_name, assignee.username AS assignee_username, assignee.full_name AS assignee_name FROM reports r LEFT JOIN users creator ON creator.id = r.user_id LEFT JOIN users assignee ON assignee.id = r.assigned_to WHERE r.id = :id AND (r.assigned_to = :uid OR EXISTS (SELECT 1 FROM report_recipients rr WHERE rr.report_id = r.id AND rr.user_id = :uid)) LIMIT 1");
    $stmt->execute([':id' => $taskId, ':uid' => $userId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) task_response(false, ['error' => 'Task not found or not assigned to you.']);
    if (is_broadcast_task($task)) {
        $responseColumns = ['response', 'response_at', 'status'];
        $responseSchema = $pdo->query('SHOW COLUMNS FROM report_responses')->fetchAll(PDO::FETCH_COLUMN);
        if (is_array($responseSchema) && in_array('returned_reason', $responseSchema, true)) {
            $responseColumns[] = 'returned_reason';
        }
        $responseStmt = $pdo->prepare('SELECT ' . implode(', ', $responseColumns) . ' FROM report_responses WHERE report_id = :report_id AND user_id = :user_id LIMIT 1');
        $responseStmt->execute([':report_id' => $taskId, ':user_id' => $userId]);
        $recipientResponse = $responseStmt->fetch(PDO::FETCH_ASSOC);
        $task['fo_response'] = $recipientResponse['response'] ?? null;
        $task['fo_response_at'] = $recipientResponse['response_at'] ?? null;
        $task['status'] = $recipientResponse['status'] ?? 'PENDING';
        $task['returned_reason'] = $recipientResponse['returned_reason'] ?? $task['returned_reason'] ?? null;
        $task['return_reason'] = $task['returned_reason'];
    }
    return $task;
}

$action = $_GET['action'] ?? 'list';
if ($action === 'stats') {
    $stmt = $pdo->prepare("SELECT CASE WHEN r.recipient_office = 'All Municipalities / Cities' THEN COALESCE((SELECT rp.status FROM report_responses rp WHERE rp.report_id = r.id AND rp.user_id = :response_uid LIMIT 1), 'PENDING') ELSE r.status END AS status, COUNT(*) total FROM reports r WHERE r.assigned_to = :uid OR EXISTS (SELECT 1 FROM report_recipients rr WHERE rr.report_id = r.id AND rr.user_id = :uid) GROUP BY status");
    $stmt->execute([':uid' => $userId, ':response_uid' => $userId]);
    $stats = ['NEW' => 0, 'PENDING' => 0, 'IN_PROGRESS' => 0, 'OVERDUE' => 0, 'COMPLETED' => 0, 'NOTIFICATIONS' => 0];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $status = strtoupper((string)$row['status']);
        if ($status === 'SUBMITTED' || $status === 'FORWARDED') $status = 'PENDING';
        if (isset($stats[$status])) $stats[$status] = (int)$row['total'];
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
    $stmt->execute([':uid' => $userId]);
    $stats['NOTIFICATIONS'] = (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports r WHERE (r.assigned_to = :uid OR EXISTS (SELECT 1 FROM report_recipients rr WHERE rr.report_id = r.id AND rr.user_id = :uid)) AND r.status = 'PENDING' AND r.submitted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute([':uid' => $userId]);
    $stats['NEW'] = (int)$stmt->fetchColumn();
    task_response(true, ['stats' => $stats]);
}

if ($action === 'list') {
    $status = strtoupper(trim((string)($_GET['status'] ?? '')));
    $search = trim((string)($_GET['search'] ?? ''));
    $sql = "SELECT r.*, r.reference_number, CASE WHEN r.recipient_office = 'All Municipalities / Cities' THEN COALESCE((SELECT rp.status FROM report_responses rp WHERE rp.report_id = r.id AND rp.user_id = :response_uid LIMIT 1), 'PENDING') ELSE r.status END AS recipient_status, creator.username AS creator_username, creator.full_name AS creator_name FROM reports r LEFT JOIN users creator ON creator.id = r.user_id WHERE (r.assigned_to = :uid OR EXISTS (SELECT 1 FROM report_recipients rr WHERE rr.report_id = r.id AND rr.user_id = :uid))";
    $params = [':uid' => $userId, ':response_uid' => $userId];
    if ($status !== '') {
        $statuses = $status === 'PENDING' ? ['PENDING', 'SUBMITTED', 'FORWARDED'] : [$status];
        $sql .= ' AND (CASE WHEN r.recipient_office = \'All Municipalities / Cities\' THEN COALESCE((SELECT rp.status FROM report_responses rp WHERE rp.report_id = r.id AND rp.user_id = :filter_response_uid LIMIT 1), \'PENDING\') ELSE r.status END) IN (' . implode(',', array_fill(0, count($statuses), '?')) . ')';
        $params[':filter_response_uid'] = $userId;
        foreach ($statuses as $item) $params[] = $item;
    }
    if ($search !== '') {
        $sql .= ' AND (r.title LIKE ? OR r.details LIKE ? OR r.reference_number LIKE ? OR creator.username LIKE ? OR creator.full_name LIKE ?)';
        for ($i = 0; $i < 5; $i++) $params[] = '%' . $search . '%';
    }
    $sql .= ' ORDER BY CASE WHEN r.status = \'OVERDUE\' THEN 0 WHEN r.status IN (\'PENDING\',\'SUBMITTED\',\'FORWARDED\') THEN 1 ELSE 2 END, r.due_at ASC, r.created_at DESC';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) $stmt->bindValue(is_int($key) ? $key + 1 : $key, $value);
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tasks as &$task) $task['status'] = $task['recipient_status'] ?? $task['status'];
    unset($task);
    task_response(true, ['tasks' => $tasks]);
}

$taskId = (int)($_GET['id'] ?? 0);
if ($action === 'detail') {
    $task = get_assigned_task($pdo, $taskId, $userId);
    $pdo->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = :uid AND related_type = \'report\' AND related_id = :id AND is_read = 0')->execute([':uid' => $userId, ':id' => $taskId]);
    $stmt = $pdo->prepare('SELECT c.*, u.username, u.full_name FROM task_comments c LEFT JOIN users u ON u.id = c.user_id WHERE c.report_id = :id ORDER BY c.created_at ASC');
    $stmt->execute([':id' => $taskId]);
    $task['comments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT * FROM audit_logs WHERE JSON_UNQUOTE(JSON_EXTRACT(meta, '$.report_id')) = :id ORDER BY created_at ASC");
    $stmt->execute([':id' => (string)$taskId]);
    $task['history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare('SELECT id, original_name, file_name, file_path, file_size, mime_type, created_at, user_id FROM documents WHERE report_id = :id ORDER BY created_at DESC');
    $stmt->execute([':id' => $taskId]);
    $task['documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $task['po_documents'] = array_values(array_filter($task['documents'], static fn($document) => (int)$document['user_id'] === (int)$task['user_id']));
    $task['fo_documents'] = array_values(array_filter($task['documents'], static fn($document) => (int)$document['user_id'] === (int)$userId));
    task_response(true, ['task' => $task]);
}

if ($action === 'delete_document') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true) ?: [];
    $documentId = (int)($data['id'] ?? 0);
    if ($documentId <= 0) task_response(false, ['error' => 'Document ID is required.']);

    $documentStmt = $pdo->prepare('SELECT id, report_id, file_path FROM documents WHERE id = :id AND user_id = :user_id LIMIT 1');
    $documentStmt->execute([':id' => $documentId, ':user_id' => $userId]);
    $document = $documentStmt->fetch(PDO::FETCH_ASSOC);
    if (!$document) task_response(false, ['error' => 'Document not found or cannot be removed.']);
    $task = get_assigned_task($pdo, (int)$document['report_id'], $userId);

    $deleteStmt = $pdo->prepare('DELETE FROM documents WHERE id = :id AND report_id = :report_id AND user_id = :user_id');
    $deleteStmt->execute([':id' => $documentId, ':report_id' => $task['id'], ':user_id' => $userId]);
    if ($deleteStmt->rowCount() !== 1) task_response(false, ['error' => 'Document could not be removed.']);
    $filePath = dirname(__DIR__) . '/' . ltrim((string)$document['file_path'], '/\\');
    if (is_file($filePath)) @unlink($filePath);
    task_response(true, ['message' => 'Document removed.']);
}

if ($action === 'response') {
    $task = get_assigned_task($pdo, (int)($_POST['id'] ?? 0), $userId);
    $responseText = trim((string)($_POST['response'] ?? ''));
    $responseAtInput = trim((string)($_POST['response_at'] ?? ''));
    $isEdit = (string)($_POST['edit'] ?? '') === '1';
    $responseTimezone = new DateTimeZone('Asia/Manila');
    $responseAt = DateTime::createFromFormat('!Y-m-d\\TH:i', $responseAtInput, $responseTimezone);
    if ($isEdit && !empty($task['fo_response_at'])) {
        $responseAt = DateTime::createFromFormat('!Y-m-d H:i:s', (string)$task['fo_response_at'], $responseTimezone);
        $responseAtInput = $responseAt ? $responseAt->format('Y-m-d\\TH:i') : '';
    }
    $allowedStatuses = ['PENDING', 'SUBMITTED', 'FORWARDED', 'IN_PROGRESS', 'OVERDUE', 'RETURNED'];
    if ($isEdit && !empty($task['fo_response_at'])) $allowedStatuses[] = 'COMPLETED';
    if (!in_array(strtoupper($task['status']), $allowedStatuses, true)) task_response(false, ['error' => 'This inquiry has already been completed.']);
    if (!$responseAt || $responseAt->format('Y-m-d\\TH:i') !== $responseAtInput) task_response(false, ['error' => 'Enter a valid response date and time.']);
    if ($responseText === '' || strlen($responseText) > 1000) task_response(false, ['error' => 'Response / Action Taken is required and must be 1,000 characters or fewer.']);
    $dueAt = !empty($task['due_at']) ? DateTime::createFromFormat('!Y-m-d H:i:s', (string)$task['due_at'], $responseTimezone) : false;
    $responseStatus = $dueAt && $responseAt > $dueAt ? 'OVERDUE' : 'SUBMITTED';

    $files = $_FILES['documents'] ?? [];
    $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $storedFiles = [];
    $broadcastResponse = is_broadcast_task($task);
    $pdo->beginTransaction();
    try {
        if ($broadcastResponse) {
            $responseStmt = $isEdit
                ? $pdo->prepare('UPDATE report_responses SET response = :response, response_at = :response_at, status = :status, updated_at = NOW() WHERE report_id = :report_id AND user_id = :user_id')
                : $pdo->prepare('INSERT INTO report_responses (report_id, user_id, office_name, response, response_at, status) SELECT :report_id, u.id, u.office_name, :response, :response_at, :status FROM users u WHERE u.id = :user_id AND u.role = \'FO USER\' AND u.is_active = 1 AND EXISTS (SELECT 1 FROM report_recipients rr WHERE rr.report_id = :recipient_report_id AND rr.user_id = u.id)');
            $parameters = [':report_id' => $task['id'], ':user_id' => $userId, ':response' => $responseText, ':response_at' => $responseAt->format('Y-m-d H:i:00'), ':status' => $responseStatus];
            if (!$isEdit) $parameters[':recipient_report_id'] = $task['id'];
            $responseStmt->execute($parameters);
            if ($responseStmt->rowCount() !== 1) throw new RuntimeException($isEdit ? 'This response could not be updated.' : 'This municipality has already submitted a response.');
        } else {
            $statusCondition = $isEdit ? "status IN ('SUBMITTED','OVERDUE','COMPLETED','RETURNED') AND fo_response_at IS NOT NULL" : "status IN ('PENDING','SUBMITTED','FORWARDED','IN_PROGRESS','OVERDUE','RETURNED') AND fo_response_at IS NULL";
            $stmt = $pdo->prepare("UPDATE reports SET status = :status, started_at = COALESCE(started_at, NOW()), fo_response = :response, fo_response_at = :response_at, fo_responded_by = :responded_by, updated_at = NOW() WHERE id = :id AND (assigned_to = :uid OR EXISTS (SELECT 1 FROM report_recipients rr WHERE rr.report_id = reports.id AND rr.user_id = :uid)) AND $statusCondition");
            $stmt->execute([':status' => $responseStatus, ':response' => $responseText, ':response_at' => $responseAt->format('Y-m-d H:i:00'), ':responded_by' => $userId, ':id' => $task['id'], ':uid' => $userId]);
            if ($stmt->rowCount() !== 1) throw new RuntimeException('This inquiry already has a submitted response.');
        }

        if (!empty($files['name']) && is_array($files['name'])) {
            $uploadDir = dirname(__DIR__) . '/uploads';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) throw new RuntimeException('Could not create the upload directory.');
            foreach ($files['name'] as $index => $originalName) {
                $uploadError = $files['error'][$index] ?? UPLOAD_ERR_NO_FILE;
                if ($uploadError === UPLOAD_ERR_NO_FILE || $originalName === '') continue;
                if ($uploadError !== UPLOAD_ERR_OK) throw new RuntimeException('One of the proof documents could not be uploaded.');
                if (($files['size'][$index] ?? 0) > 10 * 1024 * 1024) throw new RuntimeException('Each proof document must be 10 MB or smaller.');
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $mimeType = task_document_mime($files['tmp_name'][$index], $extension);
                if (!in_array($extension, $allowedExtensions, true) || $mimeType === null) throw new RuntimeException('Proof documents must be PDF, DOC, DOCX, JPG, or PNG files.');
                $targetName = bin2hex(random_bytes(16)) . '.' . $extension;
                $targetFile = $uploadDir . '/' . $targetName;
                if (!move_uploaded_file($files['tmp_name'][$index], $targetFile)) throw new RuntimeException('Could not save one of the proof documents.');
                $storedFiles[] = $targetFile;
                $documentStmt = $pdo->prepare('INSERT INTO documents (user_id, report_id, file_name, original_name, file_path, file_size, mime_type) VALUES (:uid, :report_id, :file_name, :original_name, :path, :size, :mime)');
                $documentStmt->execute([':uid' => $userId, ':report_id' => $task['id'], ':file_name' => $targetName, ':original_name' => basename($originalName), ':path' => 'uploads/' . $targetName, ':size' => (int)$files['size'][$index], ':mime' => $mimeType]);
            }
        }
        workflow_audit($pdo, $userId, $isEdit ? 'task_response_edited' : 'task_response_submitted', (int)$task['id'], ['response_at' => $responseAt->format('Y-m-d H:i:s'), 'due_at' => $dueAt ? $dueAt->format('Y-m-d H:i:s') : null, 'status' => $responseStatus]);
        
        // Only create notification if this is NOT an edit (new response)
        if (!$isEdit) {
            // Get FO user's full name for notification
            $foUserStmt = $pdo->prepare("SELECT full_name, username FROM users WHERE id = :id LIMIT 1");
            $foUserStmt->execute([':id' => $userId]);
            $foUserData = $foUserStmt->fetch(PDO::FETCH_ASSOC);
            $foUserName = $foUserData['full_name'] ?: $foUserData['username'] ?: 'FO User';
            
            // Try to create enhanced notification with response status (ON TIME or OVERDUE)
            $notificationCreated = false;
            try {
                workflow_notify_fo_response(
                    $pdo,
                    (int)$task['user_id'],  // PO user who created the submission
                    (int)$task['id'],       // Report/Submission ID
                    $task['title'],         // Submission title
                    $task['reference_number'],  // Reference number like DILG-2026-000001
                    $foUserName,            // FO User's name
                    $responseAt,            // When FO submitted the response
                    $dueAt ?: new DateTime(),  // Submission deadline
                    $responseStatus,        // 'ON_TIME' or 'OVERDUE'
                    'po-inquiries.php?view=' . (int)$task['id'], // Link to submission
                    $responseText            // Exact response submitted by the FO user
                );
                $notificationCreated = true;
            } catch (Throwable $notifyError) {
                // Log but don't fail if notification creation has issues
                error_log("Enhanced notification creation error for report {$task['id']}: " . $notifyError->getMessage());
            }
            
            // Fallback: create basic notification if enhanced one failed
            if (!$notificationCreated) {
                try {
                    $responseLabel = $responseStatus === 'OVERDUE' ? 'FO User submitted a late response for "' . $task['title'] . '".' : 'FO User submitted a response for "' . $task['title'] . '".';
                    workflow_notify_all_po_users(
                        $pdo,
                        (int)$task['id'],
                        $responseStatus === 'OVERDUE' ? 'Late FO Response Submitted' : 'FO Response Submitted',
                        $responseLabel,
                        'po-inquiries.php?view=' . (int)$task['id'],
                        'fo_response',
                        $responseStatus === 'OVERDUE' ? 'OVERDUE' : 'ON_TIME',
                        $responseAt->format('Y-m-d H:i:s'),
                        $dueAt ? $dueAt->format('Y-m-d H:i:s') : null
                    );
                } catch (Throwable $fallbackError) {
                    error_log("Fallback notification creation error for report {$task['id']}: " . $fallbackError->getMessage());
                }
            }
        }
        $pdo->commit();
        task_response(true, ['status' => $responseStatus, 'response_at' => $responseAt->format('Y-m-d H:i:00')]);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        foreach ($storedFiles as $storedFile) if (is_file($storedFile)) unlink($storedFile);
        task_response(false, ['error' => $error->getMessage()]);
    }
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
if ($action === 'status') {
    $task = get_assigned_task($pdo, (int)($input['id'] ?? 0), $userId);
    $oldStatus = strtoupper($task['status']);
    $newStatus = strtoupper(trim((string)($input['status'] ?? '')));
    $allowed = ['PENDING' => ['IN_PROGRESS'], 'SUBMITTED' => ['IN_PROGRESS'], 'FORWARDED' => ['IN_PROGRESS'], 'IN_PROGRESS' => ['COMPLETED'], 'OVERDUE' => ['IN_PROGRESS']];
    if (!isset($allowed[$oldStatus]) || !in_array($newStatus, $allowed[$oldStatus], true)) task_response(false, ['error' => 'Invalid status transition.']);
    $pdo->beginTransaction();
    try {
        $startedAt = $newStatus === 'IN_PROGRESS' ? ', started_at = NOW()' : '';
        $completedAt = $newStatus === 'COMPLETED' ? ', completed_at = NOW()' : '';
        $stmt = $pdo->prepare("UPDATE reports SET status = :status, updated_at = NOW()$startedAt$completedAt WHERE id = :id AND (assigned_to = :uid OR EXISTS (SELECT 1 FROM report_recipients rr WHERE rr.report_id = reports.id AND rr.user_id = :uid)) AND status = :old_status");
        $stmt->execute([':status' => $newStatus, ':id' => $task['id'], ':uid' => $userId, ':old_status' => $oldStatus]);
        if ($stmt->rowCount() !== 1) throw new RuntimeException('Task was changed by another request.');
        workflow_audit($pdo, $userId, 'task_status_changed', (int)$task['id'], ['from' => $oldStatus, 'to' => $newStatus]);
        $title = $newStatus === 'COMPLETED' ? 'Task Completed' : 'Task In Progress';
        $message = $newStatus === 'COMPLETED' ? 'FO User completed "' . $task['title'] . '".' : 'FO User started processing "' . $task['title'] . '".';
        workflow_notify($pdo, (int)$task['user_id'], $title, $message, (int)$task['id'], 'po-inquiries.php?view=' . (int)$task['id']);
        $pdo->commit();
        task_response(true, ['status' => $newStatus]);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        task_response(false, ['error' => $error->getMessage()]);
    }
}

if ($action === 'comment') {
    $task = get_assigned_task($pdo, (int)($input['id'] ?? 0), $userId);
    $comment = trim((string)($input['comment'] ?? ''));
    if ($comment === '') task_response(false, ['error' => 'Comment is required.']);
    if (strlen($comment) > 500) task_response(false, ['error' => 'Comment must be 500 characters or fewer.']);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO task_comments (report_id, user_id, comment) VALUES (:report_id, :user_id, :comment)');
        $stmt->execute([':report_id' => $task['id'], ':user_id' => $userId, ':comment' => $comment]);
        workflow_audit($pdo, $userId, 'task_commented', (int)$task['id'], ['comment' => $comment]);
        workflow_notify($pdo, (int)$task['user_id'], 'Task Remark Added', 'FO User added a remark to "' . $task['title'] . '".', (int)$task['id'], 'po-inquiries.php?view=' . (int)$task['id']);
        $pdo->commit();
        task_response(true);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        task_response(false, ['error' => $error->getMessage()]);
    }
}

if ($action === 'upload') {
    $task = get_assigned_task($pdo, (int)($_POST['id'] ?? 0), $userId);
    $file = $_FILES['document'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) task_response(false, ['error' => 'Select a document to upload.']);
    if ($file['size'] > 10 * 1024 * 1024) task_response(false, ['error' => 'Each document must be 10 MB or smaller.']);
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mimeType = task_document_mime($file['tmp_name'], $extension);
    if ($mimeType === null) task_response(false, ['error' => 'This document type is not allowed.']);
    $uploadDir = dirname(__DIR__) . '/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) task_response(false, ['error' => 'Could not create the upload directory.']);
    $safeName = bin2hex(random_bytes(16)) . '.' . $extension;
    $target = $uploadDir . '/' . $safeName;
    if (!move_uploaded_file($file['tmp_name'], $target)) task_response(false, ['error' => 'Could not save the document.']);
    $stmt = $pdo->prepare('INSERT INTO documents (user_id, report_id, file_name, original_name, file_path, file_size, mime_type) VALUES (:uid, :report_id, :file_name, :original_name, :file_path, :file_size, :mime_type)');
    $stmt->execute([':uid' => $userId, ':report_id' => $task['id'], ':file_name' => $safeName, ':original_name' => basename($file['name']), ':file_path' => 'uploads/' . $safeName, ':file_size' => $file['size'], ':mime_type' => $mimeType]);
    workflow_audit($pdo, $userId, 'task_document_uploaded', (int)$task['id'], ['file_name' => $file['name']]);
    workflow_notify($pdo, (int)$task['user_id'], 'Supporting Document Uploaded', 'FO User uploaded a supporting document for "' . $task['title'] . '".', (int)$task['id'], 'po-inquiries.php?view=' . (int)$task['id']);
    task_response(true, ['message' => 'Document uploaded.']);
}

task_response(false, ['error' => 'Invalid task action.']);

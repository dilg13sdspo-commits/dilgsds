<?php
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/workflow.php';
require_role('PO USER');
header('Content-Type: application/json; charset=utf-8');

$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);
workflow_ensure_schema($pdo);
workflow_sync_overdue($pdo);

function reports_response(bool $success, array $payload = []): void {
    echo json_encode(array_merge(['success' => $success], $payload));
    exit;
}

function reports_is_overdue(array $row): bool {
    $responseAt = $row['response_at'] ?? $row['fo_response_at'] ?? null;
    $dueAt = $row['due_at'] ?? null;
    if (empty($responseAt) || empty($dueAt)) {
        return false;
    }

    $responseTs = strtotime((string)$responseAt);
    $dueTs = strtotime((string)$dueAt);
    if ($responseTs === false || $dueTs === false) {
        return false;
    }

    return $responseTs > $dueTs;
}

function reports_status(array $row): string {
    if (reports_is_overdue($row)) {
        return 'OVERDUE';
    }

    $statusValue = strtoupper((string)($row['response_status'] ?? $row['status'] ?? ''));
    if ($statusValue === 'APPROVED') return 'APPROVED';
    if ($statusValue === 'RETURNED') return 'RETURNED';
    if (!empty($row['response_at']) || !empty($row['fo_response_at'])) return 'UNDER REVIEW';
    return 'PENDING';
}

function broadcast_status(array $recipients): string {
    $statuses = array_values(array_filter(array_map(static function ($recipient) {
        if (reports_is_overdue($recipient)) {
            return 'OVERDUE';
        }
        $value = strtoupper((string)($recipient['response_status'] ?? $recipient['status'] ?? ''));
        return $value !== '' ? $value : (empty($recipient['response_at']) ? 'PENDING' : 'UNDER REVIEW');
    }, $recipients)));

    if ($statuses === []) return 'PENDING';
    if (in_array('OVERDUE', $statuses, true)) return 'OVERDUE';
    if (count(array_unique($statuses)) === 1 && $statuses[0] === 'APPROVED') return 'APPROVED';
    if (count(array_unique($statuses)) === 1 && $statuses[0] === 'RETURNED') return 'RETURNED';

    $hasPending = in_array('PENDING', $statuses, true) || in_array('SUBMITTED', $statuses, true) || in_array('UNDER REVIEW', $statuses, true);
    if ($hasPending) return 'UNDER REVIEW';

    if (in_array('APPROVED', $statuses, true) || in_array('RETURNED', $statuses, true)) return 'UNDER REVIEW';

    return 'UNDER REVIEW';
}

function reports_allowed(PDO $pdo, int $reportId, int $userId): ?array {
    $stmt = $pdo->prepare("SELECT r.*, creator.full_name AS creator_name, creator.username AS creator_username, creator.office_name AS creator_office FROM reports r INNER JOIN users creator ON creator.id = r.user_id AND creator.role = 'PO USER' WHERE r.id = :id LIMIT 1");
    $stmt->execute([':id' => $reportId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function reports_rows(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("SELECT r.*, u.office_name AS assigned_office FROM reports r INNER JOIN users owner ON owner.id = r.user_id AND owner.role = 'PO USER' LEFT JOIN users u ON u.id = r.assigned_to ORDER BY r.submitted_at DESC, r.created_at DESC");
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rows = [];
    $recipientStmt = $pdo->prepare("SELECT rr.user_id, COALESCE(u.office_name, rr.office_name) AS lgu_name, u.full_name, u.username, rp.response, rp.response_at, rp.status AS response_status, rp.reviewed_at, rp.reviewed_by, rp.returned_reason FROM report_recipients rr INNER JOIN users u ON u.id = rr.user_id LEFT JOIN report_responses rp ON rp.report_id = rr.report_id AND rp.user_id = rr.user_id WHERE rr.report_id = :id ORDER BY CASE WHEN rp.response_at IS NULL THEN 1 ELSE 0 END, rp.response_at ASC, rr.user_id ASC");
    foreach ($reports as $report) {
        $isBroadcast = ($report['recipient_office'] ?? '') === 'All Municipalities / Cities';
        if ($isBroadcast) {
            $recipientStmt->execute([':id' => $report['id']]);
            $recipients = $recipientStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($recipients as &$recipient) {
                $recipient['lgu_name'] = $recipient['lgu_name'] ?: 'Municipality / City';
                $recipient['approved_at'] = $recipient['reviewed_at'];
                $recipient['status'] = reports_status($recipient);
                $recipient['is_overdue'] = reports_is_overdue($recipient);
            }
            unset($recipient);
            $activeRecipients = array_values(array_filter($recipients, static function ($recipient): bool {
                $status = strtoupper((string)($recipient['response_status'] ?? $recipient['status'] ?? ''));
                return !empty($recipient['response_at']) && !in_array($status, ['APPROVED', 'RETURNED'], true);
            }));
            if (!$activeRecipients) {
                continue;
            }
            $responded = array_values(array_filter($activeRecipients, static fn($recipient) => !empty($recipient['response_at'])));
            foreach ($responded as &$recipient) {
                $recipient['lgu_name'] = $recipient['lgu_name'] ?: 'Municipality / City';
                $recipient['approved_at'] = $recipient['reviewed_at'];
                $recipient['status'] = reports_status($recipient);
                $recipient['is_overdue'] = reports_is_overdue($recipient);
            }
            unset($recipient);
            $row = array_merge($report, $responded[0] ?? ($activeRecipients[0] ?? []));
            $row['recipients'] = $responded;
            $row['lgu_name'] = 'All Municipalities / Cities';
            $row['status'] = broadcast_status($responded ?: $activeRecipients);
            $row['lgu_names'] = array_values(array_unique(array_map(static fn($recipient) => $recipient['lgu_name'], $responded ?: $activeRecipients)));
            $rows[] = $row;
            continue;
        }
        $report['lgu_name'] = $report['assigned_office'] ?: ($report['recipient_office'] ?: 'Municipality / City');
        $report['response_at'] = $report['fo_response_at'];
        $report['response'] = $report['fo_response'];
        $report['response_status'] = $report['status'] === 'RETURNED' ? 'RETURNED' : ($report['status'] === 'COMPLETED' ? 'APPROVED' : null);
        $report['approved_at'] = $report['completed_at'];
        $report['is_overdue'] = reports_is_overdue($report);
        $report['status'] = reports_status($report);
        $report['recipients'] = [[
            'user_id' => $report['assigned_to'] ?? 0,
            'lgu_name' => $report['lgu_name'],
            'full_name' => $report['assigned_name'] ?? '',
            'username' => $report['assigned_username'] ?? '',
            'response' => $report['response'],
            'response_at' => $report['response_at'],
            'response_status' => $report['response_status'],
            'reviewed_at' => $report['reviewed_at'] ?? null,
            'returned_reason' => $report['returned_reason'] ?? null,
            'approved_at' => $report['approved_at'],
            'status' => $report['status'],
        ]];
        $report['lgu_names'] = [$report['lgu_name']];
        $rows[] = $report;
    }
    return $rows;
}

$action = $_GET['action'] ?? 'list';
if ($action === 'list') {
    $search = strtolower(trim((string)($_GET['search'] ?? '')));
    $status = strtoupper(trim((string)($_GET['status'] ?? '')));
    $lgu = strtolower(trim((string)($_GET['lgu'] ?? '')));
    $rows = array_values(array_filter(reports_rows($pdo, $userId), static function (array $row) use ($search, $status, $lgu): bool {
        $recipients = $row['recipients'] ?? [];
        $lguNames = $row['lgu_names'] ?? [(string)($row['lgu_name'] ?? '')];
        $haystack = strtolower(implode(' ', [(string)($row['title'] ?? ''), (string)($row['reference_number'] ?? ''), implode(' ', $lguNames)]));
        $matchesStatus = $status === '' || $row['status'] === $status || in_array($status, array_column($recipients, 'status'), true);
        $matchesLgu = $lgu === '' || in_array($lgu, array_map('strtolower', $lguNames), true);
        return ($search === '' || str_contains($haystack, $search)) && $matchesStatus && $matchesLgu;
    }));
    $lgus = [];
    foreach ($rows as $row) {
        $lgus = array_merge($lgus, $row['lgu_names'] ?? [$row['lgu_name']]);
    }
    reports_response(true, ['reports' => $rows, 'lgus' => array_values(array_unique($lgus))]);
}

if ($action === 'detail') {
    $reportId = (int)($_GET['id'] ?? 0);
    $report = reports_allowed($pdo, $reportId, $userId);
    if (!$report) reports_response(false, ['error' => 'Report not found or access denied.']);
    $recipientUserId = (int)($_GET['recipient_user_id'] ?? 0);
    $response = null;
    $responses = [];
    if (($report['recipient_office'] ?? '') === 'All Municipalities / Cities') {
        $responseStmt = $pdo->prepare('SELECT rp.*, COALESCE(u.office_name, rr.office_name) AS lgu_name, u.full_name, u.username FROM report_responses rp INNER JOIN report_recipients rr ON rr.report_id = rp.report_id AND rr.user_id = rp.user_id INNER JOIN users u ON u.id = rp.user_id WHERE rp.report_id = :id ORDER BY rp.response_at ASC, rp.created_at ASC, rp.user_id ASC');
        $responseStmt->execute([':id' => $reportId]);
        $responses = $responseStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($responses as &$responseRow) {
            $responseRow['status'] = reports_status($responseRow);
        }
        unset($responseRow);
        $response = $recipientUserId > 0 ? (array_values(array_filter($responses, static fn($item) => (int)$item['user_id'] === $recipientUserId))[0] ?? null) : ($responses[0] ?? null);
    } else {
        $report['lgu_name'] = $report['creator_office'] ?: ($report['recipient_office'] ?: 'Municipality / City');
        $report['response'] = $report['fo_response'];
        $report['response_at'] = $report['fo_response_at'];
        $report['response_status'] = $report['status'] === 'COMPLETED' ? 'APPROVED' : $report['status'];
        $response = $report;
        $responses = [$report];
    }
    $documentStmt = $pdo->prepare('SELECT d.id, d.original_name, d.file_name, d.file_path, d.file_size, d.mime_type, d.created_at, u.full_name AS uploader_name, u.username AS uploader_username, u.office_name AS uploader_office FROM documents d LEFT JOIN users u ON u.id = d.user_id WHERE d.report_id = :id' . ($recipientUserId > 0 ? ' AND d.user_id = :recipient' : '') . ' ORDER BY d.created_at DESC');
    $documentParams = [':id' => $reportId];
    if ($recipientUserId > 0) $documentParams[':recipient'] = $recipientUserId;
    $documentStmt->execute($documentParams);
    $historyStmt = $pdo->prepare("SELECT a.id, a.action, a.meta, a.created_at, u.full_name, u.username FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id WHERE JSON_UNQUOTE(JSON_EXTRACT(a.meta, '$.report_id')) = :id ORDER BY a.created_at ASC");
    $historyStmt->execute([':id' => (string)$reportId]);
    reports_response(true, ['report' => $report, 'response' => $response, 'responses' => $responses, 'documents' => $documentStmt->fetchAll(PDO::FETCH_ASSOC), 'history' => $historyStmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'review') {
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $reportId = (int)($data['id'] ?? 0);
    $recipientUserId = (int)($data['recipient_user_id'] ?? 0);
    $decision = strtoupper(trim((string)($data['decision'] ?? '')));
    $reason = trim((string)($data['reason'] ?? ''));
    if (!in_array($decision, ['APPROVE', 'RETURN'], true)) reports_response(false, ['error' => 'Choose approve or return.']);
    if ($decision === 'RETURN' && $reason === '') reports_response(false, ['error' => 'A return reason or remark is required.']);
    if (strlen($reason) > 2000) reports_response(false, ['error' => 'Return remarks must be 2,000 characters or fewer.']);
    $report = reports_allowed($pdo, $reportId, $userId);
    if (!$report) reports_response(false, ['error' => 'Report not found or access denied.']);
    $isBroadcast = ($report['recipient_office'] ?? '') === 'All Municipalities / Cities';
    $pdo->beginTransaction();
    try {
        if ($isBroadcast) {
            if ($recipientUserId <= 0) throw new RuntimeException('Select a municipality or city response.');
            $check = $pdo->prepare("SELECT rp.response, rp.status, u.office_name FROM report_responses rp INNER JOIN users u ON u.id = rp.user_id WHERE rp.report_id = :id AND rp.user_id = :recipient AND rp.status IN ('SUBMITTED','OVERDUE','UNDER REVIEW') LIMIT 1");
            $check->execute([':id' => $reportId, ':recipient' => $recipientUserId]);
            $recipient = $check->fetch(PDO::FETCH_ASSOC);
            if (!$recipient) throw new RuntimeException('This response is no longer awaiting review.');
            $newStatus = $decision === 'APPROVE' ? 'APPROVED' : 'RETURNED';
            $update = $pdo->prepare('UPDATE report_responses SET status = :status, reviewed_at = NOW(), reviewed_by = :reviewed_by, returned_reason = :reason, updated_at = NOW() WHERE report_id = :id AND user_id = :recipient AND status IN (\'SUBMITTED\', \'OVERDUE\', \'UNDER REVIEW\')');
            $update->execute([':status' => $newStatus, ':reviewed_by' => $userId, ':reason' => $decision === 'RETURN' ? $reason : null, ':id' => $reportId, ':recipient' => $recipientUserId]);
            $message = $decision === 'APPROVE' ? 'Your response for "' . $report['title'] . '" was approved by the PO.' : 'Your response for "' . $report['title'] . '" was returned. Reason: ' . $reason;
            workflow_notify($pdo, $recipientUserId, $decision === 'APPROVE' ? 'Response Approved' : 'Response Returned', $message, $reportId, 'fo-inquiries.php?view=' . $reportId);
        } else {
            $reportStatus = strtoupper((string)($report['status'] ?? ''));
            if (empty($report['fo_response_at']) || !in_array($reportStatus, ['SUBMITTED', 'OVERDUE', 'UNDER REVIEW'], true)) throw new RuntimeException('This report is no longer awaiting review.');
            $newStatus = $decision === 'APPROVE' ? 'COMPLETED' : 'RETURNED';
            $update = $pdo->prepare("UPDATE reports SET status = :status, completed_at = CASE WHEN :approved = 'COMPLETED' THEN NOW() ELSE NULL END, reviewed_at = NOW(), reviewed_by = :reviewed_by, returned_reason = :reason, updated_at = NOW() WHERE id = :id AND user_id = :uid AND status IN ('SUBMITTED','OVERDUE','UNDER REVIEW') AND fo_response_at IS NOT NULL");
            $update->execute([':status' => $newStatus, ':approved' => $newStatus, ':reviewed_by' => $userId, ':reason' => $decision === 'RETURN' ? $reason : null, ':id' => $reportId, ':uid' => $userId]);
            if ($update->rowCount() !== 1) throw new RuntimeException('This report is no longer awaiting review.');
            if (!empty($report['assigned_to'])) workflow_notify($pdo, (int)$report['assigned_to'], $decision === 'APPROVE' ? 'Report Approved' : 'Report Returned', $decision === 'APPROVE' ? 'Your response for "' . $report['title'] . '" was approved by the PO.' : 'Your response for "' . $report['title'] . '" was returned. Reason: ' . $reason, $reportId, 'fo-inquiries.php?view=' . $reportId);
        }
        workflow_audit($pdo, $userId, $decision === 'APPROVE' ? 'po_report_approved' : 'po_report_returned', $reportId, ['recipient_user_id' => $recipientUserId ?: null, 'reason' => $reason ?: null]);
        $pdo->commit();
        reports_response(true, ['status' => $newStatus]);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        reports_response(false, ['error' => $error->getMessage()]);
    }
}

reports_response(false, ['error' => 'Invalid action.']);

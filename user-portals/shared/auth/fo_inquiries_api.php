<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/workflow.php';

require_role('FO USER');

header('Content-Type: application/json');
$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);
$officeName = $_SESSION['office_name'] ?? '';
workflow_ensure_schema($pdo);

function response($success, $payload = []) {
    echo json_encode(array_merge(['success' => $success], $payload));
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'review') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $reportId = (int)($input['id'] ?? 0);
    $decision = strtoupper(trim($input['decision'] ?? ''));
    $reason = trim($input['reason'] ?? '');
    
    if (!$reportId) {
        response(false, ['error' => 'Invalid submission ID.']);
    }
    
    if (!in_array($decision, ['APPROVE', 'RETURN'], true)) {
        response(false, ['error' => 'Invalid decision. Use APPROVE or RETURN.']);
    }
    
    if ($decision === 'RETURN' && !$reason) {
        response(false, ['error' => 'Return reason is required.']);
    }
    
    if ($decision === 'RETURN' && strlen($reason) > 2000) {
        response(false, ['error' => 'Return reason exceeds 2000 characters.']);
    }
    
    // Check if the FO user is assigned to this report
    try {
        $stmt = $pdo->prepare("SELECT r.id, r.status, r.user_id, r.recipient_office FROM reports r WHERE r.id = :id");
        $stmt->execute([':id' => $reportId]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            response(false, ['error' => 'Submission not found.']);
        }
        
        // Check if the FO is assigned to this report
        $isAssigned = false;
        if ($report['recipient_office'] === 'All Municipalities / Cities') {
            // Broadcast report - check report_recipients
            $stmt = $pdo->prepare("SELECT id FROM report_recipients WHERE report_id = :rid AND user_id = :uid");
            $stmt->execute([':rid' => $reportId, ':uid' => $userId]);
            $isAssigned = (bool)$stmt->fetch();
        } else {
            // Single recipient - check office match
            $isAssigned = ($report['recipient_office'] === $officeName);
        }
        
        if (!$isAssigned) {
            response(false, ['error' => 'You are not assigned to review this submission.']);
        }
        
        // Check submission status - can only review SUBMITTED reports
        if (!in_array($report['status'], ['SUBMITTED', 'PENDING'], true)) {
            response(false, ['error' => 'This submission cannot be reviewed. Status: ' . $report['status']]);
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        if ($report['recipient_office'] === 'All Municipalities / Cities') {
            // Update report_responses table for broadcast reports
            $stmt = $pdo->prepare("UPDATE report_responses SET status = :status, reviewed_at = NOW(), reviewed_by = :uid, returned_reason = :reason WHERE report_id = :rid AND user_id = :uid2");
            $stmt->execute([
                ':status' => $decision === 'APPROVE' ? 'APPROVED' : 'RETURNED',
                ':uid' => $userId,
                ':reason' => $decision === 'RETURN' ? $reason : null,
                ':rid' => $reportId,
                ':uid2' => $userId
            ]);
        } else {
            // Update reports table for single recipient reports
            $newStatus = $decision === 'APPROVE' ? 'COMPLETED' : 'RETURNED';
            $stmt = $pdo->prepare("UPDATE reports SET status = :status, reviewed_at = NOW(), reviewed_by = :uid, returned_reason = :reason WHERE id = :rid");
            $stmt->execute([
                ':status' => $newStatus,
                ':uid' => $userId,
                ':reason' => $decision === 'RETURN' ? $reason : null,
                ':rid' => $reportId
            ]);
        }
        
        // Send workflow notification to PO user
        $poUserId = $report['user_id'];
        if ($poUserId) {
            $notificationMessage = $decision === 'APPROVE' 
                ? 'Your submission has been approved by the Field Office.'
                : 'Your submission has been returned. Reason: ' . substr($reason, 0, 100) . (strlen($reason) > 100 ? '...' : '');
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, category, type, related_type, related_id, is_read, created_at) VALUES (:uid, :title, :message, :category, :type, :rtype, :rid, 0, NOW())");
            $stmt->execute([
                ':uid' => $poUserId,
                ':title' => $decision === 'APPROVE' ? 'Submission Approved' : 'Submission Returned',
                ':message' => $notificationMessage,
                ':category' => 'WORKFLOW',
                ':type' => $decision === 'APPROVE' ? 'APPROVED' : 'RETURNED',
                ':rtype' => 'REPORT',
                ':rid' => $reportId
            ]);
        }
        
        $pdo->commit();
        
        response(true, [
            'status' => $decision === 'APPROVE' ? 'APPROVED' : 'RETURNED',
            'message' => $decision === 'APPROVE' 
                ? 'Submission approved successfully!'
                : 'Submission returned successfully!'
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        response(false, ['error' => 'Database error: ' . $e->getMessage()]);
    }
}

response(false, ['error' => 'Unknown action.']);
?>

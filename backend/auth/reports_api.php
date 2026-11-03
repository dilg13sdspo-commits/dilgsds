<?php
require_once __DIR__ . '/middleware.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
// Basic routing by method and path
// POST /auth/reports_api.php -> create report (FO USER)
// GET /auth/reports_api.php?action=list -> list reports (role dependent)
// POST /auth/reports_api.php?action=update -> update status (ADMIN or PO USER)

try {
    $pdo = db_connect();
    if ($method === 'POST' && empty($_GET['action'])) {
        // create report
        require_role(['FO USER','ADMIN']);
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $title = trim($data['title'] ?? '');
        $details = trim($data['details'] ?? '');
        if (!$title) { http_response_code(400); echo json_encode(['error'=>'Missing title']); exit; }
        $stmt = $pdo->prepare('INSERT INTO reports (user_id, title, details) VALUES (:uid, :title, :details)');
        $stmt->execute([':uid' => $_SESSION['user_id'], ':title'=>$title, ':details'=>$details]);
        echo json_encode(['success'=>true, 'id'=>$pdo->lastInsertId()]);
        exit;
    }

    if ($method === 'GET' && ($_GET['action'] ?? '') === 'list') {
        require_role(['ADMIN','FO USER','PO USER']);
        $role = $_SESSION['role'];
        if ($role === 'ADMIN') {
            $stmt = $pdo->query('SELECT r.*, u.username FROM reports r LEFT JOIN users u ON u.id=r.user_id ORDER BY r.created_at DESC');
            $rows = $stmt->fetchAll();
            echo json_encode(['reports'=>$rows]);
            exit;
        }
        if ($role === 'FO USER') {
            $stmt = $pdo->prepare('SELECT * FROM reports WHERE user_id = :uid ORDER BY created_at DESC');
            $stmt->execute([':uid'=>$_SESSION['user_id']]);
            echo json_encode(['reports'=>$stmt->fetchAll()]);
            exit;
        }
        if ($role === 'PO USER') {
            // PO USER sees all for monitoring but limited fields
            $stmt = $pdo->query('SELECT id, title, status, created_at FROM reports ORDER BY created_at DESC');
            echo json_encode(['reports'=>$stmt->fetchAll()]);
            exit;
        }
    }

    if ($method === 'POST' && ($_GET['action'] ?? '') === 'update') {
        require_role(['ADMIN','PO USER']);
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($data['id'] ?? 0);
        $status = $data['status'] ?? '';
        if (!$id || !in_array($status, ['PENDING','SUBMITTED','OVERDUE'])) { http_response_code(400); echo json_encode(['error'=>'Invalid']); exit; }
        $stmt = $pdo->prepare('UPDATE reports SET status=:status WHERE id=:id');
        $stmt->execute([':status'=>$status, ':id'=>$id]);
        echo json_encode(['success'=>true]);
        exit;
    }

    http_response_code(404);
    echo json_encode(['error'=>'Not found']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>'Server error']);
}

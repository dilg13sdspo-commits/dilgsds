<?php
ini_set('display_errors', '0');
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/workflow.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
// Basic routing by method and path
// POST /auth/reports_api.php -> create report (FO USER)
// GET /auth/reports_api.php?action=list -> list reports (role dependent)
// POST /auth/reports_api.php?action=update -> update status (ADMIN or PO USER)

try {
    $pdo = db_connect();
    workflow_ensure_schema($pdo);
    workflow_sync_overdue($pdo);
    if ($method === 'POST' && empty($_GET['action'])) {
        // create report
        require_role(['FO USER','ADMIN']);
        $data = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?? []);
        $title = trim($data['title'] ?? '');
        $details = trim($data['details'] ?? '');
        $category = trim($data['category'] ?? '');
        $dueDate = trim($data['due_date'] ?? '');
        $isSubmission = !empty($data['submission']);
        if (!$title) { http_response_code(400); echo json_encode(['error'=>'Missing title']); exit; }
        $parsedDueDate = $dueDate !== '' ? DateTime::createFromFormat('Y-m-d', $dueDate) : false;
        if ($dueDate !== '' && (!$parsedDueDate || $parsedDueDate->format('Y-m-d') !== $dueDate)) { http_response_code(400); echo json_encode(['error'=>'Invalid due date']); exit; }
        $sql = $isSubmission
            ? 'INSERT INTO reports (user_id, title, details, category, due_date, status, submitted_at) VALUES (:uid, :title, :details, :category, :due_date, \'PENDING\', NOW())'
            : 'INSERT INTO reports (user_id, title, details, category, due_date) VALUES (:uid, :title, :details, :category, :due_date)';
        $stmt = $pdo->prepare($sql);
        $files = $_FILES['documents'] ?? [];
        if (!empty($files['name']) && is_array($files['name'])) {
            $columns = $pdo->query('SHOW COLUMNS FROM documents')->fetchAll(PDO::FETCH_COLUMN);
            $documentMigrations = [
                'user_id' => 'ALTER TABLE documents ADD COLUMN user_id INT NULL AFTER id',
                'file_name' => 'ALTER TABLE documents ADD COLUMN file_name VARCHAR(255) NULL',
                'original_name' => 'ALTER TABLE documents ADD COLUMN original_name VARCHAR(255) NULL',
                'file_path' => 'ALTER TABLE documents ADD COLUMN file_path VARCHAR(500) NULL',
                'file_size' => 'ALTER TABLE documents ADD COLUMN file_size INT NOT NULL DEFAULT 0',
                'mime_type' => 'ALTER TABLE documents ADD COLUMN mime_type VARCHAR(120) NULL'
            ];
            foreach ($documentMigrations as $column => $migration) {
                if (!in_array($column, $columns, true)) $pdo->exec($migration);
            }
        }
        $pdo->beginTransaction();
        $stmt->execute([':uid' => $_SESSION['user_id'], ':title'=>$title, ':details'=>$details, ':category'=>$category ?: null, ':due_date'=>$dueDate ?: null]);
        $reportId = (int)$pdo->lastInsertId();
        $storedFiles = [];
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
        if (!empty($files['name']) && is_array($files['name'])) {
            $uploadDir = dirname(__DIR__) . '/uploads';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) throw new RuntimeException('Could not create the upload directory.');
            foreach ($files['name'] as $index => $originalName) {
                if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('One of the documents could not be uploaded.');
                if (($files['size'][$index] ?? 0) > 10 * 1024 * 1024) throw new RuntimeException('Each document must be 10 MB or smaller.');
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($files['tmp_name'][$index]);
                if (!isset($mimeByExtension[$extension]) || $mimeType !== $mimeByExtension[$extension]) throw new RuntimeException('One of the document types is not allowed.');
                $targetName = bin2hex(random_bytes(16)) . '.' . $extension;
                $targetFile = $uploadDir . '/' . $targetName;
                if (!move_uploaded_file($files['tmp_name'][$index], $targetFile)) throw new RuntimeException('Could not save one of the documents.');
                $storedFiles[] = $targetFile;
                $documentStmt = $pdo->prepare('INSERT INTO documents (user_id, report_id, file_name, original_name, file_path, file_size, mime_type) VALUES (:uid, :report_id, :file_name, :original_name, :path, :size, :mime)');
                $documentStmt->execute([':uid' => $_SESSION['user_id'], ':report_id' => $reportId, ':file_name' => $targetName, ':original_name' => basename($originalName), ':path' => 'uploads/' . $targetName, ':size' => (int)$files['size'][$index], ':mime' => $mimeType]);
            }
        }
        $pdo->commit();
        echo json_encode(['success'=>true, 'id'=>$reportId]);
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

    if ($method === 'POST' && ($_GET['action'] ?? '') === 'delete') {
        require_role(['FO USER']);
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid submission.']);
            exit;
        }
        $stmt = $pdo->prepare('DELETE FROM reports WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $_SESSION['user_id']]);
        if ($stmt->rowCount() !== 1) {
            http_response_code(404);
            echo json_encode(['error' => 'Submission not found.']);
            exit;
        }
        echo json_encode(['success' => true]);
        exit;
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
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    foreach ($storedFiles ?? [] as $storedFile) if (is_file($storedFile)) unlink($storedFile);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage() ?: 'Server error']);
}

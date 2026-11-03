<?php
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/workflow.php';
require_role(['PO USER', 'FO USER', 'ADMIN']);

$documentId = (int)($_GET['id'] ?? 0);
if ($documentId <= 0) {
    http_response_code(400);
    exit('Document ID is required.');
}

$pdo = db_connect();
workflow_ensure_schema($pdo);
$userId = (int)($_SESSION['user_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM documents WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $documentId]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

$role = strtoupper((string)($_SESSION['role'] ?? ''));
if ($document && $role === 'FO USER') {
    $accessStmt = $pdo->prepare(
        'SELECT 1 FROM documents d
         LEFT JOIN reports r ON r.id = d.report_id
         WHERE d.id = :document_id AND (
            d.user_id = :document_uploader
            OR r.user_id = :report_owner
            OR r.assigned_to = :assignee
            OR EXISTS (SELECT 1 FROM report_recipients rr WHERE rr.report_id = d.report_id AND rr.user_id = :recipient)
         ) LIMIT 1'
    );
    $accessStmt->execute([
        ':document_id' => $documentId,
        ':document_uploader' => $userId,
        ':report_owner' => $userId,
        ':assignee' => $userId,
        ':recipient' => $userId,
    ]);
    if (!$accessStmt->fetchColumn()) $document = false;
}

$path = false;
if ($document) {
    $sharedRoot = dirname(__DIR__);
    $siteRoot = dirname(dirname($sharedRoot));
    $fileName = basename((string)($document['file_name'] ?? ''));
    if ($fileName === '' || $fileName === '.' || $fileName === '..' || strpbrk($fileName, "/\\\0") !== false) {
        http_response_code(404);
        exit('Document not found.');
    }

    $uploadRoots = array_filter([
        realpath($sharedRoot . '/uploads'),
        realpath($siteRoot . '/uploads'),
        realpath($siteRoot . '/uploads/documents'),
    ]);
    foreach ($uploadRoots as $uploadRoot) {
        $candidate = realpath($uploadRoot . DIRECTORY_SEPARATOR . $fileName);
        if (!$candidate || !is_file($candidate)) continue;
        $rootPrefix = rtrim($uploadRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $comparePath = DIRECTORY_SEPARATOR === '\\' ? strtolower($candidate) : $candidate;
        $comparePrefix = DIRECTORY_SEPARATOR === '\\' ? strtolower($rootPrefix) : $rootPrefix;
        if (strncmp($comparePath, $comparePrefix, strlen($comparePrefix)) === 0) {
            $path = $candidate;
            break;
        }
    }
}

if (!$document || !$path || !is_file($path)) {
    http_response_code(404);
    exit('Document not found.');
}

$mimeType = '';
if (class_exists('finfo')) {
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = strtolower((string)$fileInfo->file($path));
}
if ($mimeType === '' || $mimeType === 'application/octet-stream' || $mimeType === 'binary/octet-stream') {
    $storedMime = strtolower((string)($document['mime_type'] ?? ''));
    if (preg_match('#^[a-z0-9][a-z0-9.+-]*/[a-z0-9][a-z0-9.+-]*$#i', $storedMime)) {
        $mimeType = $storedMime;
    }
}
if ($mimeType === '' || $mimeType === 'application/octet-stream' || $mimeType === 'binary/octet-stream') {
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $extensionMap = [
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'html' => 'text/html',
        'htm' => 'text/html',
        'xml' => 'application/xml',
        'json' => 'application/json',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];
    $mimeType = $extensionMap[$extension] ?? 'application/octet-stream';
}
$inlineMimeTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.ms-excel',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/bmp',
    'text/plain',
    'text/csv',
];
$shouldInline = ($_GET['mode'] ?? '') === 'inline' && in_array($mimeType, $inlineMimeTypes, true);
$disposition = $shouldInline ? 'inline' : 'attachment';
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($path));
$downloadName = preg_replace('/[\x00-\x1F\x7F"\\\\]/', '_', basename((string)$document['original_name']));
header('Content-Disposition: ' . $disposition . '; filename="' . ($downloadName !== '' ? $downloadName : 'document') . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
<?php
require_once __DIR__ . '/auth/middleware.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

require_role('ADMIN');

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error occurred.']);
    exit;
}

$file = $_FILES['file'];
$fileName = basename((string)$file['name']);
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowedTypes = [
    'application/pdf' => ['pdf'],
    'application/msword' => ['doc'],
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
    'application/vnd.ms-excel' => ['xls'],
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
    'application/vnd.ms-powerpoint' => ['ppt'],
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['pptx'],
    'application/zip' => ['docx', 'xlsx', 'pptx'],
    'text/plain' => ['txt'],
    'image/png' => ['png'],
    'image/jpeg' => ['jpg', 'jpeg'],
    'image/gif' => ['gif'],
];
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

if (
    $file['size'] > 20 * 1024 * 1024
    || !isset($allowedTypes[$mime])
    || !in_array($extension, $allowedTypes[$mime], true)
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File type not allowed or file exceeds 20 MB.']);
    exit;
}

$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to prepare upload directory.']);
    exit;
}

$targetName = bin2hex(random_bytes(16)) . '.' . $extension;
$targetFile = $uploadDir . '/' . $targetName;

if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file.']);
    exit;
}

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$publicUrl = ($basePath === '' ? '' : $basePath) . '/uploads/' . rawurlencode($targetName);

echo json_encode(['success' => true, 'url' => $publicUrl, 'title' => $fileName, 'number' => '', 'year' => date('Y')]);
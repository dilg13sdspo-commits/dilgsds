<?php
require_once __DIR__ . '/middleware.php';

header('Content-Type: application/json; charset=utf-8');

$dataDir = dirname(__DIR__) . '/data';
$dataFile = $dataDir . '/organizational_structure.json';
$uploadDir = dirname(__DIR__) . '/uploads/organizational-structure';

function org_structure_error($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

function org_structure_load($dataFile, $dataDir) {
    if (!is_dir($dataDir) && !mkdir($dataDir, 0755, true) && !is_dir($dataDir)) {
        org_structure_error('Unable to access organizational structure data.', 500);
    }

    if (!is_file($dataFile)) {
        return ['image_url' => 'Logos/DILG SDS Org Structure updated.png'];
    }

    $data = json_decode(file_get_contents($dataFile), true);
    if (!is_array($data) || empty($data['image_url']) || !is_string($data['image_url'])) {
        org_structure_error('Organizational structure data is invalid.', 500);
    }
    return $data;
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'GET') {
    echo json_encode(org_structure_load($dataFile, $dataDir));
    exit;
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    org_structure_error('Unsupported request method.', 405);
}

require_role('ADMIN');
$file = $_FILES['image'] ?? null;
if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    org_structure_error('Choose an organizational structure image to upload.');
}
if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (int)($file['size'] ?? 0) > 10 * 1024 * 1024) {
    org_structure_error('Images must be up to 10 MB.');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
$extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
if (!isset($extensions[$mime])) {
    org_structure_error('Only JPG, PNG, GIF, and WEBP images are allowed.');
}

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    org_structure_error('Unable to create the organizational structure upload folder.', 500);
}

$filename = bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
$destination = $uploadDir . '/' . $filename;
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    org_structure_error('Unable to upload the organizational structure image.', 500);
}

$data = ['image_url' => 'uploads/organizational-structure/' . $filename];
$encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (file_put_contents($dataFile, $encoded . PHP_EOL, LOCK_EX) === false) {
    org_structure_error('Image uploaded, but unable to save the organizational structure data.', 500);
}

echo json_encode(['success' => true, 'image_url' => $data['image_url']]);

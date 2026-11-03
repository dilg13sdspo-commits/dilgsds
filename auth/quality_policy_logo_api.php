<?php
require_once __DIR__ . '/middleware.php';
header('Content-Type: application/json; charset=utf-8');

$dataFile = dirname(__DIR__) . '/data/quality_policy_logo.json';

function quality_policy_logo_error($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

function quality_policy_logo_url($relativePath) {
    $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $applicationRoot = dirname(dirname($scriptPath));
    if ($applicationRoot === '.' || $applicationRoot === '/') {
        $applicationRoot = '';
    }
    return rtrim($applicationRoot, '/') . '/' . ltrim($relativePath, '/');
}

function quality_policy_logo_load($file) {
    if (!is_file($file)) {
        return '';
    }
    $contents = file_get_contents($file);
    if ($contents === false) {
        quality_policy_logo_error('The saved logo setting could not be read.', 500);
    }
    $data = json_decode($contents, true);
    if (!is_array($data)) {
        quality_policy_logo_error('The saved logo setting is invalid.', 500);
    }
    return isset($data['logo']) && is_string($data['logo']) ? $data['logo'] : '';
}

function quality_policy_logo_save($file, $relativePath) {
    $contents = json_encode(['logo' => $relativePath], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($contents === false) {
        quality_policy_logo_error('The logo setting could not be encoded.', 500);
    }
    $written = file_put_contents($file, $contents, LOCK_EX);
    if ($written === false || $written !== strlen($contents)) {
        quality_policy_logo_error('The logo setting could not be saved.', 500);
    }
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'GET') {
    $logo = quality_policy_logo_load($dataFile);
    echo json_encode([
        'logo_url' => quality_policy_logo_url($logo !== '' ? $logo : 'DILG Seal.png'),
        'has_custom_logo' => $logo !== '',
    ]);
    exit;
}
if ($method !== 'POST') {
    quality_policy_logo_error('Unsupported request method.', 405);
}

require_role('ADMIN');
$action = $_POST['action'] ?? '';
if ($action === 'reset') {
    quality_policy_logo_save($dataFile, '');
    echo json_encode(['success' => true]);
    exit;
}
if ($action !== 'upload') {
    quality_policy_logo_error('Unsupported logo action.');
}
if (empty($_FILES['logo']) || $_FILES['logo']['error'] === UPLOAD_ERR_NO_FILE) {
    quality_policy_logo_error('Choose a logo image to upload.');
}
if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK || $_FILES['logo']['size'] > 5 * 1024 * 1024) {
    quality_policy_logo_error('The logo must be a valid image no larger than 5 MB.');
}

$temporaryFile = $_FILES['logo']['tmp_name'];
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryFile);
$extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$image = getimagesize($temporaryFile);
if (!isset($extensions[$mime]) || $image === false || ($image['mime'] ?? '') !== $mime) {
    quality_policy_logo_error('Upload a JPG, PNG, or WebP logo image.');
}

$uploadDirectory = dirname(__DIR__) . '/uploads/quality-policy-logo';
if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
    quality_policy_logo_error('The logo upload folder could not be created.', 500);
}
$filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
if (!move_uploaded_file($temporaryFile, $uploadDirectory . '/' . $filename)) {
    quality_policy_logo_error('The logo image could not be uploaded.', 500);
}

$relativePath = 'uploads/quality-policy-logo/' . $filename;
quality_policy_logo_save($dataFile, $relativePath);
echo json_encode([
    'success' => true,
    'logo_url' => quality_policy_logo_url($relativePath),
    'has_custom_logo' => true,
]);

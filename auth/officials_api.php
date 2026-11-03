<?php
require_once __DIR__ . '/middleware.php';

header('Content-Type: application/json; charset=utf-8');

$dataDir = dirname(__DIR__) . '/data';
$dataFile = $dataDir . '/elected_officials.json';
$uploadDir = dirname(__DIR__) . '/uploads/officials';
$publicUploadPrefix = 'uploads/officials/';

function officials_error($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

function officials_load($dataFile, $dataDir) {
    if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);
    if (!file_exists($dataFile)) {
        $records = officials_migrate_static();
        file_put_contents($dataFile, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $records;
    }
    $records = json_decode(file_get_contents($dataFile), true);
    return is_array($records) ? $records : [];
}

function officials_save($dataFile, $records) {
    if (file_put_contents($dataFile, json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
        officials_error('Unable to save officials data.', 500);
    }
}

function officials_text($node) {
    return trim(preg_replace('/\s+/', ' ', $node ? $node->textContent : ''));
}

function officials_migrate_static() {
    $file = dirname(__DIR__) . '/elected-officials.html';
    if (!class_exists('DOMDocument') || !file_exists($file)) return [];
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTMLFile($file);
    $xpath = new DOMXPath($dom);
    $records = [];
    $id = 1;
    foreach ($xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' eo-card ')]") as $card) {
        $title = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' eo-office-title ')]//h2", $card)->item(0);
        $name = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' eo-official-name ')]", $card)->item(0);
        $logo = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' eo-seal ')]//img", $card)->item(0);
        $photo = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' eo-photo ')]", $card)->item(0);
        $details = [];
        foreach ($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' eo-contact-info ')]//p", $card) as $item) {
            $text = officials_text($item);
            if ($text !== '' && $text !== officials_text($name)) $details[] = $text;
        }
        foreach ($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' eo-expand-inner ')]//p", $card) as $item) {
            $text = officials_text($item);
            if ($text !== '') $details[] = $text;
        }
        $links = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' eo-actions ')]//a", $card);
        $facebook = '';
        $website = '';
        $profile = '';
        foreach ($links as $link) {
            $label = strtoupper(officials_text($link));
            if (strpos($label, 'FACEBOOK') !== false) $facebook = $link->getAttribute('href');
            elseif (strpos($label, 'WEBSITE') !== false) $website = $link->getAttribute('href');
            elseif (strpos($label, 'PROFILE') !== false) $profile = $link->getAttribute('href');
        }
        $records[] = [
            'id' => $id++, 'office_title' => officials_text($title),
            'official_name' => officials_text($name), 'contact_details' => implode("\n", $details),
            'logo_path' => $logo ? $logo->getAttribute('src') : '', 'photo_path' => $photo ? $photo->getAttribute('src') : '',
            'facebook_url' => $facebook, 'website_url' => $website, 'profile_url' => $profile,
            'sort_order' => count($records) + 1, 'is_active' => true
        ];
    }
    return $records;
}

function officials_input() {
    if (!empty($_POST)) return $_POST;
    $input = json_decode(file_get_contents('php://input'), true);
    return is_array($input) ? $input : [];
}

function officials_clean_url($value) {
    $value = trim((string)$value);
    if ($value === '' || $value === '#') return '';
    if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)) {
        officials_error('Links must be valid HTTP or HTTPS URLs.');
    }
    return $value;
}

function officials_upload($field, $uploadDir, $prefix) {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return '';
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK || $_FILES[$field]['size'] > 5 * 1024 * 1024) officials_error('Images must be JPG, PNG, GIF, WEBP, or SVG files up to 5 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$field]['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) officials_error('Only JPG, PNG, GIF, and WEBP images are allowed.');
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $filename = bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . '/' . $filename)) officials_error('Unable to upload image.', 500);
    return $prefix . $filename;
}

$records = officials_load($dataFile, $dataDir);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    usort($records, function ($a, $b) { return ((int)$a['sort_order']) <=> ((int)$b['sort_order']); });
    $isAdmin = !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'ADMIN';
    if (!$isAdmin) {
        $records = array_values(array_filter($records, function ($record) { return !empty($record['is_active']); }));
    }
    echo json_encode(['officials' => array_values($records)]);
    exit;
}

require_role('ADMIN');
$input = officials_input();
$action = $input['action'] ?? $_GET['action'] ?? '';

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    $records = array_values(array_filter($records, function ($record) use ($id) { return (int)$record['id'] !== $id; }));
    officials_save($dataFile, $records);
    echo json_encode(['success' => true]);
    exit;
}

if (!in_array($action, ['create', 'update'], true)) officials_error('Unsupported action.');
$record = [
    'office_title' => trim($input['office_title'] ?? ''), 'official_name' => trim($input['official_name'] ?? ''),
    'contact_details' => trim($input['contact_details'] ?? ''), 'logo_path' => trim($input['logo_path'] ?? ''),
    'photo_path' => trim($input['photo_path'] ?? ''), 'facebook_url' => officials_clean_url($input['facebook_url'] ?? ''),
    'website_url' => officials_clean_url($input['website_url'] ?? ''), 'profile_url' => officials_clean_url($input['profile_url'] ?? ''),
    'sort_order' => max(1, (int)($input['sort_order'] ?? 1)), 'is_active' => !empty($input['is_active']),
    'photo_position_x' => max(0, min(100, (int)($input['photo_position_x'] ?? 50))),
    'photo_position_y' => max(0, min(100, (int)($input['photo_position_y'] ?? 50)))
];
if ($record['office_title'] === '' || $record['official_name'] === '') officials_error('Office title and official name are required.');
$newLogo = officials_upload('logo', $uploadDir, $publicUploadPrefix);
$newPhoto = officials_upload('photo', $uploadDir, $publicUploadPrefix);
if ($newLogo !== '') $record['logo_path'] = $newLogo;
if ($newPhoto !== '') $record['photo_path'] = $newPhoto;
foreach (['logo_path' => 'logo', 'photo_path' => 'photo'] as $field => $label) {
    $path = $record[$field];
    if ($path !== '' && (preg_match('/[\x00-\x1F]/', $path) || strpos($path, '..') !== false || !preg_match('/^(https?:\/\/|[A-Za-z0-9 _.,()\/-]+$)/', $path))) {
        officials_error('Invalid ' . $label . ' path.');
    }
}

if ($action === 'create') {
    $ids = array_map(function ($item) { return (int)$item['id']; }, $records);
    $record['id'] = $ids ? max($ids) + 1 : 1;
    $records[] = $record;
} else {
    $id = (int)($input['id'] ?? 0);
    $found = false;
    foreach ($records as &$existing) {
        if ((int)$existing['id'] === $id) { $record['id'] = $id; $existing = $record; $found = true; break; }
    }
    unset($existing);
    if (!$found) officials_error('Official not found.', 404);
}
officials_save($dataFile, $records);
echo json_encode(['success' => true, 'official' => $record]);

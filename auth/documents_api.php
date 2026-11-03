<?php
require_once __DIR__ . '/middleware.php';
header('Content-Type: application/json; charset=utf-8');

$dataDir = dirname(__DIR__) . '/data';
$dataFile = $dataDir . '/documents.json';
$uploadDir = dirname(__DIR__) . '/uploads/documents';

function documents_error($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

function documents_default_data() {
    return [
        'pbac' => [
            ['id' => 1, 'title' => 'SVP Checklist', 'description' => 'Small Value Procurement checklist.', 'link_url' => 'svp-checklist.html', 'sort_order' => 1, 'is_active' => true],
            ['id' => 2, 'title' => 'BAC Advisory', 'description' => 'Procurement advisory and updates.', 'link_url' => 'bac-advisory.html', 'sort_order' => 2, 'is_active' => true],
        ],
        'quality' => [
            ['id' => 1, 'title' => 'Quality Policy', 'description' => 'Quality Management System policy.', 'link_url' => 'quality-policy.html', 'sort_order' => 1, 'is_active' => true],
        ],
        'annual' => [
            ['id' => 1, 'title' => '2024 Annual Report', 'description' => 'Provincial annual report.', 'link_url' => 'annual-report.html', 'sort_order' => 1, 'is_active' => true],
        ],
        'privacy' => [
            ['id' => 1, 'title' => 'RA 10173', 'description' => 'Data privacy and personal information protection.', 'link_url' => 'ra-10173.html', 'sort_order' => 1, 'is_active' => true],
        ],
    ];
}

function documents_load($file, $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!file_exists($file)) {
        $data = documents_default_data();
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $data;
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : documents_default_data();
}

function documents_save($file, $data) {
    if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
        documents_error('Unable to save document records.', 500);
    }
}

function documents_upload($field, $uploadDir) {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return '';
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK || $_FILES[$field]['size'] > 10 * 1024 * 1024) {
        documents_error('Documents must be 10 MB or smaller.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES[$field]['tmp_name']);
    $extensionsByMime = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'text/plain' => 'txt',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($extensionsByMime[$mime])) {
        documents_error('Only PDF, Office files, text files, and images are allowed.');
    }

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $name = bin2hex(random_bytes(12)) . '.' . $extensionsByMime[$mime];
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . '/' . $name)) {
        documents_error('Unable to upload document.', 500);
    }

    return 'uploads/documents/' . $name;
}

function documents_valid_url($value) {
    $value = trim((string) $value);
    if ($value === '') return '';
    if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)) {
        documents_error('Links must be valid HTTP or HTTPS URLs.');
    }
    return $value;
}

function documents_valid_file_url($value) {
    $value = trim((string)$value);
    if ($value === '') return '';
    if (preg_match('#^https?://#i', $value)) return documents_valid_url($value);
    if (!preg_match('#^uploads/documents/[a-f0-9]{24}\.(?:pdf|doc|docx|xls|xlsx|ppt|pptx|txt|jpg|jpeg|png|gif|webp)$#i', $value)) {
        documents_error('File links must point to an uploaded document or a valid HTTP or HTTPS URL.');
    }
    return $value;
}

$data = documents_load($dataFile, $dataDir);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    foreach ($data as $key => $items) {
        usort($data[$key], function ($a, $b) { return ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0)); });
        $data[$key] = array_values(array_filter($items, function ($row) { return !empty($row['is_active']); }));
    }
    echo json_encode(['documents' => $data]);
    exit;
}

require_role('ADMIN');
$input = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?: []);
$action = $input['action'] ?? '';
$category = trim((string) ($input['category'] ?? ''));
if ($category === '' || !isset($data[$category])) {
    documents_error('Invalid document category.');
}

if ($action === 'delete') {
    $id = (int) ($input['id'] ?? 0);
    $data[$category] = array_values(array_filter($data[$category], function ($row) use ($id) { return (int) $row['id'] !== $id; }));
    documents_save($dataFile, $data);
    echo json_encode(['success' => true]);
    exit;
}

if (!in_array($action, ['create', 'update'], true)) {
    documents_error('Unsupported action.');
}

$record = [
    'title' => trim((string) ($input['title'] ?? '')),
    'description' => trim((string) ($input['description'] ?? '')),
    'link_url' => documents_valid_url($input['link_url'] ?? ''),
    'sort_order' => max(1, (int) ($input['sort_order'] ?? 1)),
    'is_active' => !empty($input['is_active']),
];

if ($record['title'] === '') {
    documents_error('Document title is required.');
}

$uploaded = documents_upload('document_file', $uploadDir);
if ($uploaded !== '') {
    $record['file_url'] = $uploaded;
}
if (!empty($input['file_url'])) {
    $record['file_url'] = documents_valid_file_url($input['file_url']);
}

if ($action === 'create') {
    $ids = array_map(function ($row) { return (int) $row['id']; }, $data[$category]);
    $record['id'] = $ids ? max($ids) + 1 : 1;
    $data[$category][] = $record;
} else {
    $id = (int) ($input['id'] ?? 0);
    $found = false;
    foreach ($data[$category] as &$row) {
        if ((int) $row['id'] === $id) {
            $record['id'] = $id;
            $row = $record;
            $found = true;
            break;
        }
    }
    unset($row);
    if (!$found) documents_error('Document record not found.', 404);
}

documents_save($dataFile, $data);
echo json_encode(['success' => true, 'record' => $record]);

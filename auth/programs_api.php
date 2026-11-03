<?php
require_once __DIR__ . '/middleware.php';
header('Content-Type: application/json; charset=utf-8');
$dataDir = dirname(__DIR__) . '/data';
$dataFile = $dataDir . '/programs.json';

function programs_error($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

function programs_url($value) {
    $value = trim((string) $value);
    if ($value === '' || $value === '#') {
        return $value;
    }
    if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?: ''), ['http', 'https'], true)) {
        programs_error('Links must be valid HTTP or HTTPS URLs.');
    }
    return $value;
}

function programs_update_timestamp($row) {
    if (empty($row['updated_at'])) {
        $row['updated_at'] = date('c');
    }
    return $row;
}

function programs_migrate() {
    $file = dirname(__DIR__) . '/programs-and-services.html';
    if (!class_exists('DOMDocument') || !is_file($file)) {
        return [];
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTMLFile($file);
    $xpath = new DOMXPath($dom);
    $rows = [];
    $id = 1;
    $order = 1;

    foreach ($xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' accordion-item ')]") as $item) {
        $heading = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' accordion-title ')]", $item)->item(0);
        $category = trim(preg_replace('/\s+/', ' ', $heading ? $heading->textContent : 'Programs & Services'));

        foreach ($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' programs-list ')]//a", $item) as $link) {
            $rows[] = [
                'id' => $id++,
                'category' => $category,
                'title' => trim(preg_replace('/\s+/', ' ', $link->textContent)),
                'link_url' => $link->getAttribute('href'),
                'sort_order' => $order++,
                'is_active' => true,
                'updated_at' => date('c')
            ];
        }
    }

    return $rows;
}

function programs_load($file, $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (!file_exists($file)) {
        $rows = programs_migrate();
        file_put_contents($file, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $rows;
    }

    $rows = json_decode(file_get_contents($file), true);
    if (!is_array($rows)) {
        return [];
    }

    return array_map('programs_update_timestamp', $rows);
}

function programs_save($file, $rows) {
    $normalized = array_values(array_map('programs_update_timestamp', $rows));
    if (file_put_contents($file, json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
        programs_error('Unable to save programs.', 500);
    }
}

$rows = programs_load($dataFile, $dataDir);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    usort($rows, function ($a, $b) {
        return ((int) $a['sort_order']) <=> ((int) $b['sort_order']);
    });

    $isAdmin = !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'ADMIN';
    if (!$isAdmin) {
        $rows = array_values(array_filter($rows, function ($r) {
            return !empty($r['is_active']);
        }));
    }

    echo json_encode(['programs' => $rows]);
    exit;
}

require_role('ADMIN');
$input = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?: []);
$action = $input['action'] ?? '';
$id = (int) ($input['id'] ?? 0);

if ($action === 'delete') {
    $rows = array_values(array_filter($rows, function ($r) use ($id) {
        return (int) $r['id'] !== $id;
    }));
    programs_save($dataFile, $rows);
    echo json_encode(['success' => true]);
    exit;
}

if (!in_array($action, ['create', 'update'], true)) {
    programs_error('Unsupported action.');
}

$row = [
    'category' => trim($input['category'] ?? ''),
    'title' => trim($input['title'] ?? ''),
    'link_url' => programs_url($input['link_url'] ?? ''),
    'sort_order' => max(1, (int) ($input['sort_order'] ?? 1)),
    'is_active' => !empty($input['is_active']),
    'updated_at' => trim((string) ($input['updated_at'] ?? date('c')))
];

if ($row['category'] === '' || $row['title'] === '') {
    programs_error('Category and title are required.');
}

if ($action === 'create') {
    $ids = array_map(function ($r) {
        return (int) $r['id'];
    }, $rows);
    $row['id'] = $ids ? max($ids) + 1 : 1;
    $rows[] = $row;
} else {
    $found = false;
    foreach ($rows as &$existing) {
        if ((int) $existing['id'] === $id) {
            $row['id'] = $id;
            $existing = $row;
            $found = true;
            break;
        }
    }
    unset($existing);

    if (!$found) {
        programs_error('Program not found.', 404);
    }
}

programs_save($dataFile, $rows);
echo json_encode(['success' => true, 'program' => programs_update_timestamp($row)]);

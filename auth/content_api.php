<?php
require_once __DIR__ . '/middleware.php';
header('Content-Type: application/json; charset=utf-8');

$dataDir = dirname(__DIR__) . '/data';
$dataFile = $dataDir . '/managed_pages.json';
$charterDataFile = $dataDir . '/citizen_charter.json';
$sections = ['about', 'charter', 'legal', 'contact', 'lgrc-gad'];

function content_error($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

function content_public_files() {
    return [
        'about' => 'about-gad.html',
        'charter' => 'citizen-charter.html',
        'legal' => 'legal-opinions.html',
        'contact' => 'contact-us.html',
        'lgrc-gad' => 'LGRC-and-GAD.html',
    ];
}

function content_extract_record($publicFile, $defaultTitle) {
    $record = [
        'id' => 1,
        'title' => $defaultTitle,
        'description' => '',
        'link_url' => '',
        'is_active' => true,
        'updated_at' => date('c'),
    ];

    if (!class_exists('DOMDocument') || !is_file($publicFile)) {
        return $record;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTMLFile($publicFile);
    $xpath = new DOMXPath($dom);

    $titleNode = $xpath->query('//h1 | //title')->item(0);
    if ($titleNode && trim($titleNode->textContent) !== '') {
        $record['title'] = trim(preg_replace('/\s+/', ' ', $titleNode->textContent));
    }

    $description = '';
    foreach ($xpath->query('//main//p | //article//p | //body//p') as $pNode) {
        $text = trim(preg_replace('/\s+/', ' ', $pNode->textContent));
        if ($text !== '' && strlen($text) > 20) {
            $description = $text;
            break;
        }
    }
    if ($description !== '') {
        $record['description'] = $description;
    }

    return $record;
}

function content_parse_charter_items($publicFile) {
    if (!class_exists('DOMDocument') || !is_file($publicFile)) {
        return [];
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTMLFile($publicFile);
    $xpath = new DOMXPath($dom);
    $items = [];
    $index = 1;

    foreach ($xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' accordion-item ')]") as $node) {
        $titleNode = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' accordion-title ')]", $node)->item(0);
        $title = trim(preg_replace('/\s+/', ' ', $titleNode ? $titleNode->textContent : ''));
        if ($title === '') {
            continue;
        }

        $link = $node->getAttribute('data-doc-url');
        if ($link === '') {
            $anchor = $xpath->query(".//a", $node)->item(0);
            $link = $anchor ? $anchor->getAttribute('href') : '';
        }

        $contentHtml = '';
        $bodyNode = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' accordion-body ')]", $node)->item(0);
        if ($bodyNode) {
            foreach ($bodyNode->childNodes as $child) {
                $contentHtml .= $dom->saveHTML($child);
            }
        }

        $items[] = [
            'id' => $index,
            'title' => $title,
            'description' => 'Citizen’s Charter service entry',
            'content_html' => $contentHtml,
            'link_url' => $link,
            'is_active' => true,
            'updated_at' => date('c'),
        ];
        $index++;
    }

    return $items;
}

function content_seed_data() {
    $seed = [];
    $publicFiles = content_public_files();
    foreach ($publicFiles as $section => $file) {
        if ($section === 'charter') {
            $seed[$section] = content_parse_charter_items(dirname(__DIR__) . '/' . $file);
            continue;
        }

        $defaultTitle = [
            'about' => 'About Us',
            'legal' => 'Legal Opinions',
            'contact' => 'Contact Us',
            'lgrc-gad' => 'LGRC & GAD',
        ][$section] ?? ucfirst(str_replace('-', ' ', $section));
        $record = content_extract_record(dirname(__DIR__) . '/' . $file, $defaultTitle);
        $seed[$section] = [$record];
    }
    return $seed;
}

function content_load($file, $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!file_exists($file)) {
        $seed = content_seed_data();
        content_write_json($file, $seed);
        return $seed;
    }

    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) {
        $data = [];
    }

    foreach (content_public_files() as $section => $publicFile) {
        if ($section === 'charter') {
            $parsed = content_parse_charter_items(dirname(__DIR__) . '/' . $publicFile);
            if (!isset($data[$section]) || !is_array($data[$section]) || count($data[$section]) === 0 || count($data[$section]) !== count($parsed)) {
                $data[$section] = $parsed;
            }
            continue;
        }

        if (!isset($data[$section]) || !is_array($data[$section]) || count($data[$section]) === 0) {
            $defaultTitle = [
                'about' => 'About Us',
                'legal' => 'Legal Opinions',
                'contact' => 'Contact Us',
                'lgrc-gad' => 'LGRC & GAD',
            ][$section] ?? ucfirst(str_replace('-', ' ', $section));
            $data[$section] = [content_extract_record(dirname(__DIR__) . '/' . $publicFile, $defaultTitle)];
        }
    }

    content_write_json($file, $data);
    return $data;
}

function content_write_json($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        content_error('Unable to encode page content.', 500);
    }

    $handle = fopen($file, 'wb');
    if ($handle === false) {
        content_error('Unable to open page content file for writing.', 500);
    }

    $written = fwrite($handle, $json);
    fclose($handle);

    if ($written === false || $written !== strlen($json)) {
        content_error('Unable to save page content.', 500);
    }
}

function content_save($file, $data) {
    content_write_json($file, $data);
}

$data = content_load($dataFile, $dataDir);
$section = trim((string)($_GET['section'] ?? $_POST['section'] ?? ''));
if (!in_array($section, $sections, true)) content_error('Invalid managed page section.');
if ($section === 'charter') {
    $charterData = content_load($charterDataFile, $dataDir);
    $data = array_merge($data, $charterData);
    $data['charter'] = $charterData['charter'] ?? content_parse_charter_items(dirname(__DIR__) . '/citizen-charter.html');
    if (!isset($data['charter']) || !is_array($data['charter']) || count($data['charter']) === 0) {
        $data['charter'] = content_parse_charter_items(dirname(__DIR__) . '/citizen-charter.html');
    }

    $parsedCharter = content_parse_charter_items(dirname(__DIR__) . '/citizen-charter.html');
    foreach ($data['charter'] as &$charterRow) {
        $rowId = (int)($charterRow['id'] ?? 0);
        if (!array_key_exists('content_html', $charterRow) && isset($parsedCharter[$rowId - 1])) {
            $charterRow['content_html'] = $parsedCharter[$rowId - 1]['content_html'];
        }
    }
    unset($charterRow);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_role('ADMIN');
    echo json_encode(['records' => array_values($data[$section] ?? [])]);
    exit;
}

require_role('ADMIN');
$input = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?: []);
$action = $input['action'] ?? '';
if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    $data[$section] = array_values(array_filter($data[$section] ?? [], function ($row) use ($id) { return (int)($row['id'] ?? 0) !== $id; }));
    if ($section === 'charter') {
        content_save($charterDataFile, ['charter' => $data[$section]]);
    } else {
        content_save($dataFile, $data);
    }
    echo json_encode(['success' => true]);
    exit;
}
if (!in_array($action, ['create', 'update'], true)) content_error('Unsupported action.');
$title = trim((string)($input['title'] ?? ''));
if ($title === '') content_error('Title is required.');
$record = ['title' => $title, 'description' => trim((string)($input['description'] ?? '')), 'link_url' => trim((string)($input['link_url'] ?? '')), 'is_active' => !empty($input['is_active']), 'updated_at' => date('c')];
if ($section === 'charter') {
    $record['content_html'] = trim((string)($input['content_html'] ?? ''));
}
if ($action === 'create') {
    $ids = array_map(function ($row) { return (int)($row['id'] ?? 0); }, $data[$section] ?? []);
    $record['id'] = $ids ? max($ids) + 1 : 1;
    $data[$section][] = $record;
} else {
    $id = (int)($input['id'] ?? 0);
    $found = false;
    foreach ($data[$section] ?? [] as &$row) {
        if ((int)($row['id'] ?? 0) === $id) { $record['id'] = $id; $row = $record; $found = true; break; }
    }
    unset($row);
    if (!$found) content_error('Managed page record not found.', 404);
}
if ($section === 'charter') {
    content_save($charterDataFile, ['charter' => $data[$section]]);
} else {
    content_save($dataFile, $data);
}
echo json_encode(['success' => true, 'record' => $record]);

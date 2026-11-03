<?php
require_once __DIR__ . '/middleware.php';
header('Content-Type: application/json; charset=utf-8');

$pageFiles = [
    'about-lgrc' => ['label' => 'About LGRC', 'file' => 'about-lgrc.html'],
    'about-gad' => ['label' => 'About GAD', 'file' => 'about-gad.html'],
    'gad-corner' => ['label' => 'GAD Corner', 'file' => 'gad-corner.html'],
    'msac' => ['label' => 'MSAC', 'file' => 'msac.html'],
];
$page = trim((string)($_GET['page'] ?? ''));

function lgrc_gad_text_error($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

function lgrc_gad_text_nodes($page, $pageFiles) {
    if (!isset($pageFiles[$page])) {
        lgrc_gad_text_error('Invalid LGRC and GAD page.');
    }
    if (!class_exists('DOMDocument')) {
        lgrc_gad_text_error('The server cannot read page content because the PHP DOM extension is unavailable.', 500);
    }

    $file = dirname(__DIR__) . '/public/pages/' . $pageFiles[$page]['file'];
    if (!is_file($file) || !is_readable($file)) {
        lgrc_gad_text_error('The public page content file is unavailable.', 500);
    }

    $previousLibxmlSetting = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = $dom->loadHTMLFile($file);
    libxml_clear_errors();
    libxml_use_internal_errors($previousLibxmlSetting);
    if (!$loaded) {
        lgrc_gad_text_error('The public page content could not be read.', 500);
    }

    $xpath = new DOMXPath($dom);
    $scopeXPath = [
        'about-lgrc' => '//main/*[contains(@class, "lgrc-")]',
        'about-gad' => '//main/*[contains(concat(" ", normalize-space(@class), " "), " gad-hero ") or contains(concat(" ", normalize-space(@class), " "), " gad-page-panel ")]',
        'gad-corner' => '//main/*[contains(concat(" ", normalize-space(@class), " "), " gad-corner-panel ")]',
        'msac' => '//main/*[contains(concat(" ", normalize-space(@class), " "), " msac-page ")]',
    ][$page];
    $roots = $xpath->query($scopeXPath);
    if ($roots === false) {
        lgrc_gad_text_error('The public page text fields could not be identified.', 500);
    }

    $fields = [];
    foreach ($roots as $root) {
      $nodes = $xpath->query('.//text()[normalize-space(.) != ""]', $root);
      if ($nodes === false) {
          lgrc_gad_text_error('The public page text fields could not be identified.', 500);
      }
      foreach ($nodes as $node) {
        $excluded = false;
        for ($ancestor = $node->parentNode; $ancestor && $ancestor !== $root; $ancestor = $ancestor->parentNode) {
            if ($ancestor instanceof DOMElement) {
                $tag = strtolower($ancestor->tagName);
                $classes = ' ' . preg_replace('/\s+/', ' ', trim($ancestor->getAttribute('class'))) . ' ';
                if (in_array($tag, ['script', 'style', 'svg', 'button'], true) || strpos($classes, ' site-footer ') !== false) {
                    $excluded = true;
                    break;
                }
            }
        }
        if ($excluded) {
            continue;
        }
        $parent = $node->parentNode;
        $tag = $parent instanceof DOMElement ? strtolower($parent->tagName) : 'text';
        $classes = $parent instanceof DOMElement ? trim($parent->getAttribute('class')) : '';
        $context = trim(preg_replace('/\s+/u', ' ', $node->nodeValue));
        $preview = preg_replace('/^(.{0,90}).*/us', '$1', $context);
        $location = $tag . ($classes !== '' ? ' · ' . $classes : '');
        $fields[] = [
            'key' => 'text-' . count($fields),
            'tag' => $location,
            'original' => $node->nodeValue,
            'preview' => $preview,
        ];
      }
    }

    return $fields;
}

function lgrc_gad_text_load($file) {
    if (!is_file($file)) {
        return [];
    }
    $json = file_get_contents($file);
    if ($json === false) {
        lgrc_gad_text_error('Saved page text could not be read.', 500);
    }
    $data = json_decode($json, true);
    if (!is_array($data)) {
        lgrc_gad_text_error('Saved page text data is invalid.', 500);
    }
    return $data;
}

function lgrc_gad_text_save($file, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        lgrc_gad_text_error('Page text could not be encoded.', 500);
    }
    $written = file_put_contents($file, $json, LOCK_EX);
    if ($written === false || $written !== strlen($json)) {
        lgrc_gad_text_error('Page text could not be saved.', 500);
    }
}

function lgrc_gad_text_upload_logo($file, $data) {
    if (empty($_FILES['logo']) || $_FILES['logo']['error'] === UPLOAD_ERR_NO_FILE) {
        lgrc_gad_text_error('Choose a logo image to upload.');
    }
    if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK || $_FILES['logo']['size'] > 5 * 1024 * 1024) {
        lgrc_gad_text_error('The logo must be a valid image no larger than 5 MB.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['logo']['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $image = getimagesize($_FILES['logo']['tmp_name']);
    if (!isset($extensions[$mime]) || $image === false || ($image['mime'] ?? '') !== $mime) {
        lgrc_gad_text_error('Upload a JPG, PNG, or WebP logo image.');
    }

    $uploadDir = dirname(__DIR__) . '/public/uploads/lgrc-gad';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        lgrc_gad_text_error('The logo upload folder could not be created.', 500);
    }
    $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . '/' . $name)) {
        lgrc_gad_text_error('The logo image could not be uploaded.', 500);
    }

    $data[$_GET['page']]['logo_url'] = '../uploads/lgrc-gad/' . $name;
    lgrc_gad_text_save($file, $data);
    echo json_encode(['success' => true, 'logo_url' => $data[$_GET['page']]['logo_url']]);
    exit;
}

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'POST'], true)) {
    lgrc_gad_text_error('Unsupported request method.', 405);
}

$fields = lgrc_gad_text_nodes($page, $pageFiles);
$dataFile = dirname(__DIR__) . '/data/lgrc_gad_text.json';
$data = lgrc_gad_text_load($dataFile);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $overrides = isset($data[$page]) && is_array($data[$page]) ? $data[$page] : [];
    $responseFields = [];
    foreach ($fields as $field) {
        $key = $field['key'];
        $overridden = array_key_exists($key, $overrides) && is_string($overrides[$key]);
        $responseFields[] = [
            'key' => $key,
            'tag' => $field['tag'],
            'preview' => $field['preview'],
            'original' => $field['original'],
            'value' => $overridden ? $overrides[$key] : $field['original'],
            'overridden' => $overridden,
        ];
    }
    echo json_encode([
        'page' => $page,
        'label' => $pageFiles[$page]['label'],
        'logo_url' => $page === 'about-lgrc' ? (string)($overrides['logo_url'] ?? '../../lgrc-aurora-logo.jpg') : '',
        'has_custom_logo' => $page === 'about-lgrc' && !empty($overrides['logo_url']),
        'fields' => $responseFields,
        'overrides' => array_intersect_key($overrides, array_column($fields, null, 'key')),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

require_role('ADMIN');
$action = $_POST['action'] ?? '';
if ($page !== 'about-lgrc' && in_array($action, ['upload_logo', 'reset_logo'], true)) {
    lgrc_gad_text_error('Logo uploads are only available for the About LGRC page.');
}
if ($action === 'upload_logo') {
    lgrc_gad_text_upload_logo($dataFile, $data);
}
if ($action === 'reset_logo') {
    unset($data[$page]['logo_url']);
    lgrc_gad_text_save($dataFile, $data);
    echo json_encode(['success' => true]);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    lgrc_gad_text_error('The submitted page text is not valid JSON.');
}

$action = $input['action'] ?? '';
if ($action === 'reset') {
    $logoUrl = $data[$page]['logo_url'] ?? '';
    if ($logoUrl !== '') {
        $data[$page] = ['logo_url' => $logoUrl];
    } else {
        unset($data[$page]);
    }
    lgrc_gad_text_save($dataFile, $data);
    echo json_encode(['success' => true]);
    exit;
}
if ($action !== 'save' || !isset($input['fields']) || !is_array($input['fields'])) {
    lgrc_gad_text_error('Invalid page text update.');
}

$validKeys = array_fill_keys(array_column($fields, 'key'), true);
$savedFields = [];
foreach ($input['fields'] as $key => $value) {
    if (!is_string($key) || !isset($validKeys[$key]) || !is_string($value)) {
        lgrc_gad_text_error('The submitted page text contains an invalid field.');
    }
    if (strlen($value) > 10000) {
        lgrc_gad_text_error('Each text field must be 10,000 bytes or less.');
    }
    $savedFields[$key] = $value;
}
if (count($savedFields) !== count($fields)) {
    lgrc_gad_text_error('Please submit all page text fields.');
}

$logoUrl = $data[$page]['logo_url'] ?? '';
$data[$page] = $savedFields;
if ($logoUrl !== '') {
    $data[$page]['logo_url'] = $logoUrl;
}
lgrc_gad_text_save($dataFile, $data);
echo json_encode(['success' => true]);

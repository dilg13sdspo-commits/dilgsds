<?php
require_once __DIR__ . '/middleware.php';
header('Content-Type: application/json; charset=utf-8');

$dataDir = dirname(__DIR__) . '/data';
$dataFile = $dataDir . '/news.json';
$uploadDir = dirname(__DIR__) . '/uploads/news';

function news_error($message, $status = 400) { http_response_code($status); echo json_encode(['error' => $message]); exit; }
function news_text($node) { return trim(preg_replace('/\s+/', ' ', $node ? $node->textContent : '')); }
function news_migrate() {
    $file = dirname(__DIR__) . '/news.html';
    if (!class_exists('DOMDocument') || !is_file($file)) return [];
    libxml_use_internal_errors(true); $dom = new DOMDocument(); $dom->loadHTMLFile($file); $xpath = new DOMXPath($dom); $records = []; $id = 1;
    foreach ($xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' video-card ') or contains(concat(' ', normalize-space(@class), ' '), ' katon-card ')]") as $card) {
        $titleNode = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " video-date ") or contains(concat(" ", normalize-space(@class), " "), " video-title ") or contains(concat(" ", normalize-space(@class), " "), " katon-title ")]', $card)->item(0);
        $descNode = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " video-desc ") or contains(concat(" ", normalize-space(@class), " "), " katon-desc ")]', $card)->item(0);
        $media = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " video-media ")]//iframe | .//*[contains(concat(" ", normalize-space(@class), " "), " katon-media ")]//img', $card)->item(0);
        $link = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " katon-title ")]//a', $card)->item(0);
        $cardText = strtolower(news_text($card));
        $type = strpos($cardText, 'beyondthedesk') !== false || strpos($cardText, 'beyond the desk') !== false ? 'beyond' : (strpos($cardText, 'ganap sa sursur') !== false ? 'ganap' : (strpos(' ' . $card->getAttribute('class') . ' ', ' katon-card ') !== false ? 'katon' : 'video'));
        $records[] = ['id' => $id++, 'type' => $type, 'title' => news_text($titleNode), 'description' => news_text($descNode), 'published_date' => $titleNode && strpos($titleNode->getAttribute('class'), 'video-date') !== false ? news_text($titleNode) : '', 'media_url' => $media ? $media->getAttribute($media->tagName === 'iframe' ? 'src' : 'src') : '', 'link_url' => $link ? $link->getAttribute('href') : '', 'is_active' => true, 'sort_order' => count($records) + 1];
    }
    return $records;
}
function news_load($file, $dir) { if (!is_dir($dir)) mkdir($dir, 0755, true); if (!file_exists($file)) { $rows = news_migrate(); file_put_contents($file, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX); return $rows; } $rows = json_decode(file_get_contents($file), true); return is_array($rows) ? $rows : []; }
function news_normalize_titles($rows) {
    foreach ($rows as &$row) {
        if (!in_array($row['type'] ?? '', ['video', 'beyond', 'ganap'], true) || trim((string)($row['description'] ?? '')) === '') continue;
        $title = trim((string)($row['title'] ?? ''));
        $date = trim((string)($row['published_date'] ?? ''));
        if ($title === '' || ($date !== '' && $title === $date)) {
            $description = trim((string)$row['description']);
            $sentence = preg_split('/(?<=[.!?])\s+/', $description, 2)[0] ?? $description;
            $row['title'] = mb_strlen($sentence) > 120 ? rtrim(mb_substr($sentence, 0, 117)) . '...' : $sentence;
        }
        $dateSource = (string)($row['title'] ?? '') . ' ' . (string)($row['description'] ?? '');
        if (($row['type'] ?? '') === 'ganap' && trim((string)($row['published_date'] ?? '')) === '' && preg_match('/\b(January|February|March|April|May|June|July|August|September|October|November|December)\s+(20\d{2})\b/i', $dateSource, $dateMatch)) {
            $row['published_date'] = $dateMatch[1] . ' ' . $dateMatch[2];
        }
    }
    unset($row);
    return $rows;
}
function news_save($file, $rows) { if (file_put_contents($file, json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) news_error('Unable to save news data.', 500); }
function news_url($value) { $value = trim((string)$value); if ($value === '') return ''; if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)) news_error('Links must be valid HTTP or HTTPS URLs.'); return $value; }
function news_upload($field, $dir) { if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return ''; if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK || $_FILES[$field]['size'] > 5 * 1024 * 1024) news_error('Images must be up to 5 MB.'); $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$field]['tmp_name']); $types = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp']; if (!isset($types[$mime])) news_error('Only JPG, PNG, GIF, and WEBP images are allowed.'); if (!is_dir($dir)) mkdir($dir, 0755, true); $name = bin2hex(random_bytes(12)) . '.' . $types[$mime]; if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dir . '/' . $name)) news_error('Unable to upload image.', 500); return 'uploads/news/' . $name; }

$records = news_normalize_titles(news_load($dataFile, $dataDir));
if ($_SERVER['REQUEST_METHOD'] === 'GET') { usort($records, function ($a, $b) { return ((int)$a['sort_order']) <=> ((int)$b['sort_order']); }); $isAdmin = !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'ADMIN'; if (!$isAdmin) $records = array_values(array_filter($records, function ($row) { return !empty($row['is_active']); })); echo json_encode(['news' => $records]); exit; }
require_role('ADMIN');
$input = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?: []); $action = $input['action'] ?? '';
if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    // attempt to remove uploaded file if present
    $uploadRoot = realpath($uploadDir) ?: $uploadDir;
    foreach ($records as $r) {
        if ((int)($r['id'] ?? 0) === $id) {
            $m = trim($r['media_url'] ?? '');
            if ($m !== '' && strpos($m, 'uploads/news/') === 0) {
                $candidate = realpath(dirname(__DIR__) . '/' . $m);
                if ($candidate && strpos($candidate, $uploadRoot) === 0 && is_file($candidate)) {
                    @unlink($candidate);
                }
            }
            break;
        }
    }
    $records = array_values(array_filter($records, function ($row) use ($id) { return (int)$row['id'] !== $id; }));
    news_save($dataFile, $records);
    echo json_encode(['success'=>true]); exit;
}
if (!in_array($action, ['create', 'update'], true)) news_error('Unsupported action.');
$record = ['type' => in_array($input['type'] ?? '', ['video','katon','beyond','ganap'], true) ? $input['type'] : 'video', 'title' => trim($input['title'] ?? ''), 'description' => trim($input['description'] ?? ''), 'published_date' => trim($input['published_date'] ?? ''), 'media_url' => trim($input['media_url'] ?? ''), 'link_url' => news_url($input['link_url'] ?? ''), 'sort_order' => max(1, (int)($input['sort_order'] ?? 1)), 'is_active' => !empty($input['is_active'])];
if ($record['title'] === '') news_error('Title is required.'); $newImage = news_upload('image', $uploadDir); if ($newImage !== '') $record['media_url'] = $newImage;
if ($action === 'create') { $ids = array_map(function ($row) { return (int)$row['id']; }, $records); $record['id'] = $ids ? max($ids) + 1 : 1; $records[] = $record; } else { $id = (int)($input['id'] ?? 0); $found = false; foreach ($records as &$row) { if ((int)$row['id'] === $id) { $record['id'] = $id; $row = $record; $found = true; break; } } unset($row); if (!$found) news_error('News item not found.', 404); }
news_save($dataFile, $records); echo json_encode(['success'=>true, 'news'=>$record]);

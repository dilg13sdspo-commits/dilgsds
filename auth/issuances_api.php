<?php
require_once __DIR__ . '/middleware.php';
header('Content-Type: application/json; charset=utf-8');

$dataDir = dirname(__DIR__) . '/data';
$dataFile = $dataDir . '/issuances.json';

function issuances_error($message, $status = 400) { http_response_code($status); echo json_encode(['error' => $message]); exit; }

function issuances_parse_public_array($file, $variable, $category) {
    if (!is_file($file)) return [];
    $source = file_get_contents($file);
    if ($source === false || !preg_match('/(?:const|let|var)\s+' . preg_quote($variable, '/') . '\s*=\s*\[(.*?)\];/s', $source, $arrayMatch)) return [];
    preg_match_all("/\['((?:\\\\.|[^'])*)',\s*'((?:\\\\.|[^'])*)',\s*'((?:\\\\.|[^'])*)',\s*'((?:\\\\.|[^'])*)'\]/", $arrayMatch[1], $matches, PREG_SET_ORDER);
    if (!$matches) {
        preg_match_all("/\{\s*(?:number|code):\s*'((?:\\\\.|[^'])*)',\s*date:\s*'((?:\\\\.|[^'])*)',\s*title:\s*'((?:\\\\.|[^'])*)',\s*url:\s*'((?:\\\\.|[^'])*)'\s*\}/", $arrayMatch[1], $matches, PREG_SET_ORDER);
    }
    $rows = [];
    foreach ($matches as $match) {
        $rows[] = [
            'category' => $category,
            'title' => trim(stripcslashes($match[3])),
            'reference_number' => trim(stripcslashes($match[1])),
            'issued_date' => trim(stripcslashes($match[2])),
            'link_url' => trim(stripcslashes($match[4])),
            'summary' => '',
            'is_active' => true,
            'sort_order' => count($rows) + 1
        ];
    }
    return $rows;
}

function issuances_migrate($existing = []) {
    $root = dirname(__DIR__);
    $sources = [
        ['file' => $root . '/joint-circulars.html', 'variable' => 'circulars', 'category' => 'joint-circulars'],
        ['file' => $root . '/memo-circulars.html', 'variable' => 'memoCirculars', 'category' => 'memo-circulars'],
        ['file' => $root . '/presidential-directives.html', 'variable' => 'presidentialDirectives', 'category' => 'presidential-directives'],
        ['file' => $root . '/republic-acts.html', 'variable' => 'republicActs', 'category' => 'republic-acts']
    ];
    $publicRows = [];
    foreach ($sources as $source) $publicRows = array_merge($publicRows, issuances_parse_public_array($source['file'], $source['variable'], $source['category']));
    if (!$publicRows) return $existing;

    $rows = $existing;
    $known = [];
    foreach ($rows as $row) $known[($row['category'] ?? '') . '|' . ($row['link_url'] ?? '') . '|' . ($row['title'] ?? '')] = true;
    $nextId = $rows ? max(array_map(function ($row) { return (int)($row['id'] ?? 0); }, $rows)) + 1 : 1;
    foreach ($publicRows as $row) {
        $key = $row['category'] . '|' . $row['link_url'] . '|' . $row['title'];
        if (isset($known[$key])) continue;
        $row['id'] = $nextId++;
        $rows[] = $row;
        $known[$key] = true;
    }
    return $rows;
}

function issuances_seed_defaults() {
    return [
        ['id' => 1, 'category' => 'joint-circulars', 'title' => 'Guidelines on the Submission of Certified True Copies of Documents Signed by Local Chief Executives in Lieu of Originals', 'reference_number' => 'DILG-DBM JMC No. 03 S. 2025', 'issued_date' => '2025-07-18', 'link_url' => 'https://dilg.gov.ph/issuances/jc/Guidelines-on-the-Submission-of-Certified-True-Copies-of-Documents-Signed-by-Local-Chief-Executives-in-Lieu-of-Originals/215', 'summary' => 'Updated guidance for local chief executives submitting certified copies of documents in place of originals.', 'is_active' => true, 'sort_order' => 1],
        ['id' => 2, 'category' => 'joint-circulars', 'title' => 'Creation, Reconstitution, and Strengthening of Local Literacy Councils at the City and Municipality Levels', 'reference_number' => 'DepEd-DILG JMC No. 1, s. 2024', 'issued_date' => '2025-04-11', 'link_url' => 'https://dilg.gov.ph/issuances/jc/Creation-Reconstitution-and-Strengthening-of-Local-Literacy-Councils-at-the-City-and-Municipality-Levels/209', 'summary' => 'Guidance for creating and strengthening local literacy councils in cities and municipalities.', 'is_active' => true, 'sort_order' => 2],
        ['id' => 3, 'category' => 'joint-circulars', 'title' => 'Streamlined Guidelines for the Issuance of Permits and Clearances for the Implementation of Energy Infrastructure Projects (EIPs)', 'reference_number' => 'DILG-DOE-ARTA-DPWH JMC No. 2025-001', 'issued_date' => '2025-12-22', 'link_url' => 'https://dilg.gov.ph/issuances/jc/Streamlined-Guidelines-for-the-Issuance-of-Permits-and-Clearances-for-the-Implementation-of-Energy-Infrastructure-Projects-EIPs/221', 'summary' => 'Measures to streamline permits and clearances for energy infrastructure implementation.', 'is_active' => true, 'sort_order' => 3],
        ['id' => 4, 'category' => 'memo-circulars', 'title' => 'Adoption and Implementation of the Philippine Plan of Action for Nutrition (PPAN) 2023-2028', 'reference_number' => '2024-071', 'issued_date' => '2024-05-14', 'link_url' => 'https://dilg.gov.ph/issuances/mc/Adoption-and-Implementation-of-the-Philippine-Plan-of-Action-for-Nutrition-PPAN-2023-2028/3924', 'summary' => 'Rules and guidance for adopting the PPAN 2023-2028 at the local level.', 'is_active' => true, 'sort_order' => 1],
        ['id' => 5, 'category' => 'memo-circulars', 'title' => 'Inventory and Monitoring of Local Online Gaming Operations', 'reference_number' => '2024-083', 'issued_date' => '2024-06-24', 'link_url' => 'https://dilg.gov.ph/issuances/mc/Inventory-and-Monitoring-of-Local-Online-Gaming-Operations/3939', 'summary' => 'Monitoring and inventory procedures for local online gaming operations.', 'is_active' => true, 'sort_order' => 2],
        ['id' => 6, 'category' => 'memo-circulars', 'title' => 'Closure of all Internet Gaming Licenses (IGLs) and Philippine Offshore Gaming Operations (POGOs)', 'reference_number' => '2025-001', 'issued_date' => '2025-01-02', 'link_url' => 'https://dilg.gov.ph/issuances/mc/Closure-of-all-Internet-Gaming-Licenses-IGLs-and-Philippine-Offshore-Gaming-Operations-POGOs/4032', 'summary' => 'Directive to close all IGLs and POGOs nationwide.', 'is_active' => true, 'sort_order' => 3],
        ['id' => 7, 'category' => 'presidential-directives', 'title' => 'Directing a Moratorium on the Transfer of Regional Offices of All Department and Agencies from Zamboanga City to Pagadian City Pursuant to Executive Order No. 429, Series of 1990', 'reference_number' => 'OP_MC-11', 'issued_date' => '2010-12-30', 'link_url' => 'https://dilg.gov.ph/issuances/pd/Directing-a-Moratorium-on-the-Transfer-of-Regional-Offices-of-All-Department-and-Agencies-from-Zamboanga-City-to-Pagadian-City-Pursuant-to-Executive-Order-No-42944-Series-of-1990/1', 'summary' => 'Moratorium directive on transfers of regional offices.', 'is_active' => true, 'sort_order' => 1],
        ['id' => 8, 'category' => 'presidential-directives', 'title' => 'Extending the Submission of the Report of the Inter-agency Study on the Region IX Regional Center Transfer and its Impact on the Region', 'reference_number' => 'OP_MC-11-A', 'issued_date' => '2011-07-12', 'link_url' => 'https://dilg.gov.ph/issuances/pd/Extending-the-Submission-of-the-Report-of-the-Inter45agency-Study-on-the-Region-IX-Regional-Center-Transfer-and-its-Impact-on-the-Region/3', 'summary' => 'Extension to submit the regional center transfer impact study.', 'is_active' => true, 'sort_order' => 2],
        ['id' => 9, 'category' => 'presidential-directives', 'title' => 'Directing the use of the Procurement Service and Philippine Government Electronic Procurement System in Procurement Activities in accordance with RA 9184', 'reference_number' => 'AO No. 17', 'issued_date' => '2012-02-13', 'link_url' => 'https://dilg.gov.ph/issuances/pd/Directing-the-use-of-the-Procurement-Service-and-Philippine-Government-Electronic-Procurement-System-in-Procurement-Activities-in-accordance-with-RA-9184/5', 'summary' => 'Use of the procurement service and e-procurement system for government procurement.', 'is_active' => true, 'sort_order' => 3],
        ['id' => 10, 'category' => 'republic-acts', 'title' => 'Implementing Rules and Regulations of Republic Act 10176. Also Known as "An Act Reviving the Observance of Arbor Day by Authorizing the Local Government Units the responsibilities for celebrating the day for the tree planting as annual event"', 'reference_number' => 'RA 10176', 'issued_date' => '2014-04-03', 'link_url' => 'https://dilg.gov.ph/issuances/ra/Implementing-Rules-and-Regulations-of-Republic-Act-10176-Also-Known-as-An-Act-Reviving-the-Observance-of-Arbor-Day-by-Authorizing-the-Local-Government-Units-the-responsibilities-for-celebrating-the-day-for-the-tree-planting-as-annual-event/12', 'summary' => 'IRR for the observance of Arbor Day and local government responsibilities.', 'is_active' => true, 'sort_order' => 1],
        ['id' => 11, 'category' => 'republic-acts', 'title' => 'Implementing Rules and Regulations of RA 11292, The SGLG Act of 2019', 'reference_number' => 'IRR of RA 11292', 'issued_date' => '2020-12-29', 'link_url' => 'https://dilg.gov.ph/issuances/ra/Implementing-Rules-and-Regulations-of-RA-11292-The-SGLG-Act-of-2019/14', 'summary' => 'Implementation rules for the Seal of Good Local Governance Act.', 'is_active' => true, 'sort_order' => 2],
        ['id' => 12, 'category' => 'republic-acts', 'title' => 'RA 8792 Phil eCommerce Act of 2000', 'reference_number' => 'RA 8792', 'issued_date' => '2010-08-29', 'link_url' => 'https://dilg.gov.ph/issuances/ra/RA-8792-Phil-eCommerce-Act-of-2000/4', 'summary' => 'eCommerce Act establishing the legal framework for electronic commerce in the Philippines.', 'is_active' => true, 'sort_order' => 3]
    ];
}

function issuances_load($file, $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!file_exists($file)) {
        $rows = issuances_seed_defaults();
        file_put_contents($file, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $rows;
    }

    $rows = json_decode(file_get_contents($file), true);
    if (!is_array($rows) || count($rows) === 0) {
        $rows = issuances_seed_defaults();
        file_put_contents($file, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    $migratedRows = issuances_migrate($rows);
    if (count($migratedRows) !== count($rows)) {
        file_put_contents($file, json_encode(array_values($migratedRows), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
    return $migratedRows;
}

function issuances_save($file, $rows) {
    if (file_put_contents($file, json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) issuances_error('Unable to save issuances.', 500);
}

function issuances_url($value) {
    $value = trim((string)$value);
    if ($value === '') return '';
    if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)) issuances_error('Links must be valid HTTP or HTTPS URLs.');
    return $value;
}

$rows = issuances_load($dataFile, $dataDir);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    usort($rows, function ($a, $b) {
        $aDate = !empty($a['issued_date']) ? strtotime($a['issued_date']) : 0;
        $bDate = !empty($b['issued_date']) ? strtotime($b['issued_date']) : 0;
        if ($aDate === $bDate) {
            return ((int)($b['sort_order'] ?? 0)) <=> ((int)($a['sort_order'] ?? 0));
        }
        return $bDate <=> $aDate;
    });
    $isAdmin = !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'ADMIN';
    if (!$isAdmin) $rows = array_values(array_filter($rows, function ($r) { return !empty($r['is_active']); }));
    echo json_encode(['issuances' => $rows]);
    exit;
}

require_role('ADMIN');

$input = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?: []);
$action = $input['action'] ?? '';

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    $rows = array_values(array_filter($rows, function ($r) use ($id) { return (int)$r['id'] !== $id; }));
    issuances_save($dataFile, $rows);
    echo json_encode(['success' => true]);
    exit;
}

if (!in_array($action, ['create', 'update'], true)) issuances_error('Unsupported action.');

$record = [
    'category' => in_array($input['category'] ?? '', ['joint-circulars', 'memo-circulars', 'presidential-directives', 'republic-acts'], true) ? $input['category'] : 'joint-circulars',
    'title' => trim($input['title'] ?? ''),
    'reference_number' => trim($input['reference_number'] ?? ''),
    'issued_date' => trim($input['issued_date'] ?? ''),
    'link_url' => issuances_url($input['link_url'] ?? ''),
    'summary' => trim($input['summary'] ?? ''),
    'sort_order' => max(1, (int)($input['sort_order'] ?? 1)),
    'is_active' => !empty($input['is_active'])
];

if ($record['title'] === '') issuances_error('Title is required.');

if ($action === 'create') {
    $ids = array_map(function ($r) { return (int)$r['id']; }, $rows);
    $record['id'] = $ids ? max($ids) + 1 : 1;
    $rows[] = $record;
} else {
    $id = (int)($input['id'] ?? 0);
    $found = false;
    foreach ($rows as &$r) {
        if ((int)($r['id'] ?? 0) === $id) {
            $record['id'] = $id;
            $r = $record;
            $found = true;
            break;
        }
    }
    unset($r);
    if (!$found) issuances_error('Issuance not found.', 404);
}

issuances_save($dataFile, $rows);
echo json_encode(['success' => true, 'issuance' => $record]);

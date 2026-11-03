<?php
require_once __DIR__ . '/middleware.php';
header('Content-Type: application/json; charset=utf-8');

$dataDir = dirname(__DIR__) . '/data';
$contactFile = $dataDir . '/contact_info.json';

function loadContacts() {
    global $contactFile, $dataDir;
    
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    if (!file_exists($contactFile)) {
        $default = [
            [
                'id' => 1,
                'title' => 'Contact Us | DILG Surigao del Sur',
                'address' => '2/F Provincial Capitol Bldg., Capitol Hills, Telaje, City of Tandag, Surigao del Sur',
                'phone_number' => '(086) 123-4567',
                'email_address' => 'info@surigaodelsur.dilg.gov.ph',
                'office_hours' => 'Monday - Friday, 8:00 AM - 5:00 PM',
                'description' => 'We are here to serve you. For inquiries, suggestions, or concerns, feel free to reach out to us.',
                'map_location' => '',
                'is_active' => true,
                'updated_at' => date('c')
            ]
        ];
        file_put_contents($contactFile, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $default;
    }

    $data = json_decode(file_get_contents($contactFile), true);
    return is_array($data) ? $data : [];
}

function saveContacts($data) {
    global $contactFile;
    return file_put_contents($contactFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $records = loadContacts();
    http_response_code(200);
    echo json_encode(['records' => $records]);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_role('ADMIN');

$action = $_POST['action'] ?? '';
$records = loadContacts();

if ($action === 'create') {
    $newId = empty($records) ? 1 : max(array_column($records, 'id')) + 1;

    $newRecord = [
        'id' => $newId,
        'title' => $_POST['title'] ?? '',
        'address' => $_POST['address'] ?? '',
        'phone_number' => $_POST['phone_number'] ?? '',
        'email_address' => $_POST['email_address'] ?? '',
        'office_hours' => $_POST['office_hours'] ?? '',
        'description' => $_POST['description'] ?? '',
        'map_location' => $_POST['map_location'] ?? '',
        'is_active' => ($_POST['is_active'] ?? '0') === '1',
        'updated_at' => date('c')
    ];

    $records[] = $newRecord;

    if (saveContacts($records)) {
        http_response_code(201);
        echo json_encode(['id' => $newId, 'message' => 'Record created']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save record']);
    }
    exit;
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $found = false;

    foreach ($records as &$record) {
        if ($record['id'] === $id) {
            $record['title'] = $_POST['title'] ?? $record['title'];
            $record['address'] = $_POST['address'] ?? $record['address'];
            $record['phone_number'] = $_POST['phone_number'] ?? $record['phone_number'];
            $record['email_address'] = $_POST['email_address'] ?? $record['email_address'];
            $record['office_hours'] = $_POST['office_hours'] ?? $record['office_hours'];
            $record['description'] = $_POST['description'] ?? $record['description'];
            $record['map_location'] = $_POST['map_location'] ?? $record['map_location'];
            $record['is_active'] = ($_POST['is_active'] ?? '0') === '1';
            $record['updated_at'] = date('c');
            $found = true;
            break;
        }
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'Record not found']);
        exit;
    }

    if (saveContacts($records)) {
        http_response_code(200);
        echo json_encode(['message' => 'Record updated']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update record']);
    }
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $original_count = count($records);
    $records = array_values(array_filter($records, fn($r) => $r['id'] !== $id));

    if (count($records) === $original_count) {
        http_response_code(404);
        echo json_encode(['error' => 'Record not found']);
        exit;
    }

    if (saveContacts($records)) {
        http_response_code(200);
        echo json_encode(['message' => 'Record deleted']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete record']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);

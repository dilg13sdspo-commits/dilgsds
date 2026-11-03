<?php
header('Content-Type: application/json; charset=utf-8');

$file = dirname(dirname(__DIR__)) . '/data/citizen_charter.json';
$data = is_file($file) ? json_decode(file_get_contents($file), true) : [];
$records = is_array($data['charter'] ?? null) ? $data['charter'] : [];

$records = array_values(array_filter($records, function ($record) {
    return !isset($record['is_active']) || $record['is_active'];
}));

echo json_encode(['records' => $records], JSON_UNESCAPED_SLASHES);
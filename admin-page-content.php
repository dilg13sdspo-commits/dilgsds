<?php
require_once __DIR__ . '/auth/middleware.php';
require_role('ADMIN');
$section = $_GET['section'] ?? 'about';
$config = [
    'about' => ['label' => 'About Us', 'public' => 'about-gad.html'],
    'charter' => ['label' => "Citizen's Charter", 'public' => 'citizen-charter.html'],
    'legal' => ['label' => 'Legal Opinions', 'public' => 'legal-opinions.html'],
    'contact' => ['label' => 'Contact Us', 'public' => 'contact-us.html'],
    'lgrc-gad' => ['label' => 'MSAC', 'public' => 'LGRC-and-GAD.html']
];
if (!isset($config[$section])) {
    http_response_code(404);
    exit('Unknown page section.');
}
$page = $config[$section];

function load_public_dilg_opinions($file) {
  if (!class_exists('DOMDocument') || !is_file($file)) {
    return [];
  }

  libxml_use_internal_errors(true);
  $dom = new DOMDocument();
  $dom->loadHTMLFile($file);
  $xpath = new DOMXPath($dom);
  $items = [];

  foreach ($xpath->query('//ol[contains(concat(" ", normalize-space(@class), " "), " dilg-opinion-list ")]/li') as $index => $itemNode) {
    $link = $xpath->query('.//a', $itemNode)->item(0);
    $reference = $xpath->query('.//strong', $itemNode)->item(0);
    $title = trim(preg_replace('/\s+/', ' ', $link ? $link->textContent : ''));
    $ref = trim(preg_replace('/^Reference\s*(Number)?\s*:\s*/i', '', preg_replace('/\s+/', ' ', $reference ? $reference->textContent : '')));

    if ($title === '' || $ref === '') {
      continue;
    }

    $items[] = [
      'ref' => $ref,
      'title' => $title,
      'link' => $link ? $link->getAttribute('href') : ''
    ];
  }

  return $items;
}

function load_public_caraga_opinions($file) {
  if (!class_exists('DOMDocument') || !is_file($file)) return [];
  libxml_use_internal_errors(true);
  $dom = new DOMDocument();
  $dom->loadHTMLFile($file);
  $xpath = new DOMXPath($dom);
  $items = [];
  $panes = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " tab-pane ") and @id]');
  foreach ($panes as $pane) {
    if (!$pane instanceof DOMElement) continue;
    $year = $pane->getAttribute('id');
    if (!preg_match('/^20\d{2}$/', $year)) continue;
    foreach ($xpath->query('.//ul[contains(concat(" ", normalize-space(@class), " "), " opinion-list ")]/li', $pane) as $itemNode) {
      $link = $xpath->query('.//a', $itemNode)->item(0);
      $reference = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " opinion-ref ")]', $itemNode)->item(0);
      $title = trim(preg_replace('/\s+/', ' ', $link ? $link->textContent : ''));
      $ref = trim(preg_replace('/\s+/', ' ', $reference ? $reference->textContent : ''));
      if ($title === '' || $ref === '') continue;
      $items[] = ['ref' => $ref, 'title' => $title, 'link' => $link ? $link->getAttribute('href') : '', 'year' => $year];
    }
  }
  return $items;
}

$legalTabData = [
    'dilg' => [
        'label' => 'DILG Philippines',
        'subtitle' => 'National legal opinions issued by the Department of the Interior and Local Government.',
        'items' => [
            ['ref' => 'LO-021s2026', 'title' => 'Whether SK Federation president be allowed to concurrently serve and receive compensation as a contractual/job order employee in a state university', 'link' => 'https://www.dilg.gov.ph/PDF_File/issuances/legal_opinions/dilg-legalopinions-2026525_a735125c47.pdf'],
            ['ref' => 'LO-020s2026', 'title' => 'ClarificatioN on the Decision rendered by the City of Manila- Non Disciplinary Case -AWOL, Administrative case- Dishonesty', 'link' => 'https://www.dilg.gov.ph/PDF_File/issuances/legal_opinions/dilg-legalopinions-2026525_6d817b915d.pdf'],
            ['ref' => 'LO-019s2026', 'title' => 'Whether the term “local fiestas” under Presidential Decree No. 449 includes barangay fiestas; whether cockfighting activities may be legally conducted during voter registration days; and whether civil society organizations may request the holding of special cockfighting activities for charitable purposes.', 'link' => 'https://www.dilg.gov.ph/PDF_File/issuances/legal_opinions/dilg-legalopinions-2026525_9bec351fc4.pdf'],
            ['ref' => 'LO-018s2026', 'title' => 'Whether a Sangguniang Barangay Member may be considered present in a regular session under different attendance circumstances.', 'link' => 'https://www.dilg.gov.ph/PDF_File/issuances/legal_opinions/dilg-legalopinions-2026525_db3e529252.pdf'],
            ['ref' => 'LO-017s2026', 'title' => 'Mediating/settling dispute between the LGU and a water concessionaire- SAVS- Cebu', 'link' => 'https://www.dilg.gov.ph/PDF_File/issuances/legal_opinions/dilg-legalopinions-2026525_726f5dcc27.pdf'],
            ['ref' => 'LO-016s2026', 'title' => 'Ineligible and annulling proclamation on the ground of failure to meet the one-year residency requirement- Tarlac City', 'link' => 'https://www.dilg.gov.ph/PDF_File/issuances/legal_opinions/dilg-legalopinions-2026525_380b46c5fe.pdf'],
        ]
    ],
    'caraga' => [
        'label' => 'Caraga Region',
        'subtitle' => 'Regional legal opinions archive covering Surigao del Sur and nearby Caraga LGUs.',
        'items' => [
            ['ref' => 'DILG13 LO NO.01, S.2025', 'title' => 'OBTAINING COPIES OF BARANGAY RECORDS ON MATTERS UNDER MEDIATION', 'link' => 'https://drive.google.com/file/d/1JKofiNqn61RoxGXsyY6v6rfEY-nj6eD4/view?pli=1'],
            ['ref' => 'DILG 13 LO NO.06, S.2025', 'title' => 'DELIBERATE IN ACTION OF SANGGUNIAN', 'link' => 'https://drive.google.com/file/d/1CpZDR_oGaFYeXNrIukalU-zvffqN4buy/view'],
            ['ref' => 'DILG13 LO NO.15, S.2025', 'title' => 'INTERPRETATION OF BARANGAY UNITS ADJOINING EACH OTHER', 'link' => 'https://drive.google.com/file/d/1sh9ItQqouUrUxuoCpLTLbsJqer2OiLAJ/view'],
            ['ref' => 'DILG13 LO NO.17, S.2025', 'title' => 'CAN MAYOR PRACTICE MD PROFESSION', 'link' => 'https://drive.google.com/file/d/1u4YwCEGl0HOAfJ4DTsfusDLul9YeLD3U/view'],
            ['ref' => 'DILG13 LO NO.20, S.2025', 'title' => 'ACCREDITATION OF RELIGIOUS SECTOR', 'link' => 'https://drive.google.com/file/d/1xKNHuTIThZFrdxZ2UvJesWurrc-tFdfL/view'],
            ['ref' => 'DILG13 LO NO.21, S.2025', 'title' => 'WON ACCOUNTANT SHOULD SECURE MAYORS APPROVAL', 'link' => 'https://drive.google.com/file/d/1FoPUrCTHRRtvod2lvv2sHXpPCe4QYUuu/view'],
        ]
    ]
];

  $publicDilgOpinions = load_public_dilg_opinions(__DIR__ . '/legal-opinions.html');
  if ($publicDilgOpinions) {
    $legalTabData['dilg']['items'] = $publicDilgOpinions;
  }
  $publicCaragaOpinions = load_public_caraga_opinions(__DIR__ . '/legal-opinions.html');
  if ($publicCaragaOpinions) {
    $legalTabData['caraga']['items'] = $publicCaragaOpinions;
  }
  $caragaYears = array_values(array_unique(array_column($legalTabData['caraga']['items'], 'year')));
  rsort($caragaYears);

// Load LGRC & GAD categories
$lgrcGadCategories = [];
if ($section === 'lgrc-gad') {
    $dataDir = __DIR__ . '/data';
    $dataFile = $dataDir . '/managed_pages.json';
    if (file_exists($dataFile)) {
        $data = json_decode(file_get_contents($dataFile), true);
        if (isset($data['lgrc-gad']) && is_array($data['lgrc-gad'])) {
            $lgrcGadCategories = $data['lgrc-gad'];
        }
    }
    // Sort by ID to ensure correct order
    usort($lgrcGadCategories, function($a, $b) {
        return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
    });
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo htmlspecialchars($page['label']); ?> Management - DILG SDS</title>
  <link rel="stylesheet" href="styles.css?v=20260831-citizens-charter-dashboard">
  <style>
    .page-content-admin {
      width: 100%;
      max-width: 1360px;
      margin: 0 auto;
      padding: 0;
    }

    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin: 0 0 18px;
    }

    .page-header h1 {
      margin: 0 0 4px;
      color: #0f2a6b;
      font-size: clamp(1.8rem, 1.8vw, 2.2rem);
      line-height: 1.15;
      font-weight: 800;
      letter-spacing: -0.03em;
    }

    .page-header p {
      margin: 0;
      color: #64748b;
      font-size: 0.88rem;
      line-height: 1.5;
    }

    .add-record-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      border: 0;
      border-radius: 10px;
      background: linear-gradient(135deg, #1f5fcf 0%, #174da8 100%);
      color: #fff;
      min-height: 44px;
      padding: 0 18px;
      font-size: 0.92rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 8px 18px rgba(31, 95, 207, 0.22);
      transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .add-record-button:hover,
    .add-record-button:focus-visible {
      transform: translateY(-1px);
      box-shadow: 0 12px 20px rgba(31, 95, 207, 0.24);
      outline: none;
    }

    .content-management-layout {
      display: grid;
      grid-template-columns: minmax(0, 1fr);
      gap: 20px;
      align-items: start;
      transition: grid-template-columns 0.22s ease;
    }

    .content-management-layout.has-editor {
      grid-template-columns: minmax(0, 1fr);
    }

    .content-list-panel,
    .editor-panel {
      min-width: 0;
    }

    .editor-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(15, 42, 107, 0.08);
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.2s ease, visibility 0.2s ease;
      z-index: 1090;
    }

    .editor-backdrop.is-open {
      opacity: 1;
      visibility: visible;
    }

    .records-card,
    .editor-card {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 18px;
      box-shadow: 0 12px 28px rgba(15, 42, 107, 0.06);
      overflow: hidden;
    }

    .records-card {
      padding: 0;
    }

    .records-toolbar {
      display: grid;
      grid-template-columns: auto minmax(300px, 1fr) 240px 160px auto;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      border-bottom: 1px solid #edf2f7;
      background: #fff;
    }

    .records-heading {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
      flex: 1 1 auto;
    }

    .records-icon {
      width: 36px;
      height: 36px;
      display: grid;
      place-items: center;
      border-radius: 8px;
      background: rgba(31, 95, 207, 0.08);
      color: #1f5fcf;
      flex-shrink: 0;
    }

    .records-icon svg {
      width: 16px;
      height: 16px;
      fill: none;
      stroke: currentColor;
      stroke-width: 2.2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .records-heading h2 {
      margin: 0 0 2px 0;
      color: #0f2a6b;
      font-size: 1.08rem;
      font-weight: 800;
      line-height: 1.3;
    }

    .records-heading p {
      margin: 0;
      color: #64748b;
      font-size: 0.82rem;
      line-height: 1.4;
    }

    .records-tools {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 12px;
      width: 100%;
    }

    .search-field,
    .filter-field {
      position: relative;
      display: flex;
      align-items: center;
      background: #fff;
      border: 1px solid #dfe7f0;
      border-radius: 8px;
      min-height: 40px;
      overflow: hidden;
      box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.02);
    }

    .search-field {
      flex: 1 1 auto;
      max-width: 280px;
      flex-shrink: 0;
      padding: 0;
    }

    .filter-field {
      width: 160px;
      flex-shrink: 0;
    }

    .clear-filters-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 40px;
      padding: 0 16px;
      border: 1px solid #dfe7f0;
      border-radius: 8px;
      background: #fff;
      color: #1f5fcf;
      font-size: 0.86rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.15s ease;
      white-space: nowrap;
      flex-shrink: 0;
    }

    .clear-filters-button:hover,
    .clear-filters-button:focus-visible {
      background: #f0f6ff;
      border-color: #cfe0ff;
      outline: none;
    }

    .search-field svg {
      display: none;
    }

    .search-field input,
    .filter-field select,
    .editor-form input,
    .editor-form textarea {
      width: 100%;
      border: 0;
      background: transparent;
      color: #0f172a;
      font: inherit;
      box-sizing: border-box;
    }

    .search-field input {
      padding: 0 12px;
      width: 100%;
      min-height: 40px;
      outline: none;
      font-size: 0.87rem;
      color: #334155;
      background: transparent;
      border: 0;
    }

    .search-field input::placeholder {
      color: #9ca3af;
    }

    .filter-field {
      width: 160px;
      flex-shrink: 0;
    }

    .filter-field select {
      min-height: 40px;
      padding: 0 30px 0 10px;
      appearance: none;
      background-image: linear-gradient(45deg, transparent 50%, #64748b 50%), linear-gradient(135deg, #64748b 50%, transparent 50%);
      background-position: calc(100% - 10px) calc(50% - 2px), calc(100% - 6px) calc(50% - 2px);
      background-size: 5px 5px, 5px 5px;
      background-repeat: no-repeat;
      font-size: 0.86rem;
      color: #334155;
      outline: none;
      font-weight: 500;
    }

    .records-table-wrap {
      overflow-x: auto;
      background: #fff;
    }

    .records-table {
      width: 100%;
      border-collapse: collapse;
      min-width: 760px;
    }

    .records-table thead th {
      text-align: left;
      padding: 12px 18px;
      background: #f8fafc;
      color: #64748b;
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      border-bottom: 1px solid #e2e8f0;
    }

    .records-table tbody td {
      padding: 16px 18px;
      border-bottom: 1px solid #edf2f7;
      vertical-align: middle;
    }

    .records-table tbody tr {
      transition: background-color 0.18s ease, box-shadow 0.18s ease;
    }

    .records-table tbody tr:hover {
      background: linear-gradient(90deg, rgba(31, 95, 207, 0.035) 0%, rgba(31, 95, 207, 0.018) 100%);
      box-shadow: inset 0 0 0 1px rgba(31, 95, 207, 0.04);
    }

    .record-title-wrap {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }

    .record-doc-icon {
      width: 36px;
      height: 36px;
      display: grid;
      place-items: center;
      border-radius: 10px;
      background: #f1f5f9;
      border: 1px solid #dfe7f0;
      color: #3b4b6d;
      flex-shrink: 0;
    }

    .record-doc-icon svg {
      width: 18px;
      height: 18px;
      stroke: currentColor;
      fill: none;
      stroke-width: 1.8;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .record-title {
      display: flex;
      flex-direction: column;
      gap: 5px;
      min-width: 0;
    }

    .record-title strong {
      color: #0f172a;
      font-size: 0.96rem;
      line-height: 1.4;
      font-weight: 700;
      text-decoration: none;
      word-break: break-word;
    }

    .record-subtitle {
      color: #64748b;
      font-size: 0.78rem;
      line-height: 1.4;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      min-height: 24px;
      padding: 0 9px;
      border-radius: 999px;
      font-size: 0.7rem;
      line-height: 1;
      font-weight: 700;
      border: 1px solid transparent;
      white-space: nowrap;
    }

    .status-badge::before {
      content: "";
      width: 8px;
      height: 8px;
      border-radius: 50%;
      display: inline-block;
      background: currentColor;
      opacity: 0.9;
    }

    .status-badge--published {
      background: rgba(16, 185, 129, 0.12);
      color: #0d8d5e;
      border-color: rgba(16, 185, 129, 0.18);
    }

    .status-badge--draft {
      background: rgba(245, 158, 11, 0.12);
      color: #b7791f;
      border-color: rgba(245, 158, 11, 0.18);
    }

    .table-date {
      color: #475569;
      font-size: 0.86rem;
      white-space: nowrap;
    }

    .actions-cell {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 8px;
    }

    .icon-button {
      position: relative;
      width: 32px;
      height: 32px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      border: 1px solid #dfe7f0;
      background: #fff;
      color: #334155;
      cursor: pointer;
      transition: all 0.18s ease;
      padding: 0;
    }

    .icon-button:hover,
    .icon-button:focus-visible {
      border-color: #cfe0ff;
      background: #f0f6ff;
      color: #1f5fcf;
      outline: none;
    }

    .icon-button:hover::after,
    .icon-button:focus-visible::after {
      content: attr(title);
      position: absolute;
      left: 50%;
      bottom: calc(100% + 8px);
      transform: translateX(-50%);
      background: #0f172a;
      color: #fff;
      font-size: 11px;
      font-weight: 600;
      line-height: 1.2;
      white-space: nowrap;
      padding: 6px 8px;
      border-radius: 6px;
      box-shadow: 0 8px 18px rgba(15, 42, 107, 0.18);
      z-index: 5;
      pointer-events: none;
    }

    .icon-button--danger:hover,
    .icon-button--danger:focus-visible {
      border-color: rgba(220, 38, 38, 0.22);
      background: rgba(254, 226, 226, 0.8);
      color: #b91c1c;
    }

    .icon-button svg {
      width: 15px;
      height: 15px;
      fill: none;
      stroke: currentColor;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .records-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      padding: 16px 18px 18px;
      background: #fff;
      border-top: 1px solid #edf2f7;
      flex-wrap: wrap;
    }

    .records-summary {
      color: #64748b;
      font-size: 0.85rem;
    }

    .pagination {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }

    .page-btn {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      border: 1px solid #dfe7f0;
      background: #fff;
      color: #475569;
      font-weight: 700;
      cursor: pointer;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .page-btn.is-current {
      background: #1f5fcf;
      border-color: #1f5fcf;
      color: #fff;
      box-shadow: 0 8px 16px rgba(31, 95, 207, 0.2);
    }

    .page-btn:disabled {
      opacity: 0.45;
      cursor: not-allowed;
    }

    .editor-panel {
      position: fixed;
      top: 0;
      right: 0;
      bottom: 0;
      width: min(420px, 100%);
      z-index: 1100;
      display: flex;
      justify-content: flex-end;
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transform: translateX(100%);
      transition: opacity 0.22s ease, transform 0.22s ease, visibility 0.22s ease;
    }

    .editor-panel.is-open {
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
      transform: translateX(0);
    }

    .editor-card {
      padding: 20px 18px 18px;
      width: 100%;
      height: 100vh;
      min-height: 100vh;
      border-radius: 0;
      box-shadow: -8px 0 24px rgba(15, 42, 107, 0.12);
      position: relative;
      overflow-y: auto;
      background: #f4f7fb;
      border: 0;
    }

    .editor-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 18px;
      min-height: 40px;
    }

    .editor-header h3 {
      margin: 0;
      color: #0f2a6b;
      font-size: 1.22rem;
      font-weight: 800;
      line-height: 1.3;
    }

    .form-hint {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 28px;
      padding: 0 10px;
      border-radius: 999px;
      background: rgba(31, 95, 207, 0.08);
      color: #1f5fcf;
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      border: 1px solid rgba(31, 95, 207, 0.08);
    }

    .editor-close-btn {
      width: 30px;
      height: 30px;
      border: 0;
      border-radius: 8px;
      background: transparent;
      color: #64748b;
      font-size: 1.5rem;
      line-height: 1;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0;
      margin-left: auto;
    }

    .editor-close-btn:hover,
    .editor-close-btn:focus-visible {
      background: rgba(148, 163, 184, 0.12);
      color: #0f2a6b;
      outline: none;
    }

    .editor-close-btn:hover,
    .editor-close-btn:focus-visible {
      background: #f0f6ff;
      color: #1f5fcf;
      border-color: #cfe0ff;
      outline: none;
    }

    .editor-form {
      display: grid;
      gap: 14px;
    }

    .editor-form label {
      display: grid;
      gap: 7px;
      color: #334155;
      font-size: 0.76rem;
      font-weight: 700;
      letter-spacing: 0.02em;
    }

    .editor-form input,
    .editor-form textarea {
      border: 1px solid #dfe7f0;
      border-radius: 10px;
      background: #ffffff;
      min-height: 42px;
      padding: 10px 12px;
      color: #0f172a;
      font-size: 0.9rem;
      outline: none;
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .editor-form input:focus,
    .editor-form textarea:focus,
    .search-field:focus-within,
    .filter-field:focus-within {
      border-color: #b8d0ff;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
      background: #ffffff;
    }

    .editor-form textarea {
      min-height: 120px;
      resize: vertical;
    }

    .checkbox-row {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 2px 0 0;
      color: #334155;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .checkbox-row input {
      width: 16px;
      height: 16px;
      accent-color: #1f5fcf;
      min-height: auto;
      border-radius: 4px;
      margin: 0;
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 6px;
      padding-top: 6px;
    }

    .secondary-button,
    .primary-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 40px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.9rem;
      cursor: pointer;
      padding: 0 16px;
      transition: all 0.18s ease;
    }

    .secondary-button {
      background: #fff;
      border: 1px solid #dfe7f0;
      color: #334155;
    }

    .secondary-button:hover,
    .secondary-button:focus-visible {
      border-color: #cfe0ff;
      color: #1f5fcf;
      outline: none;
    }

    .primary-button {
      border: 0;
      background: linear-gradient(135deg, #1f5fcf 0%, #174da8 100%);
      color: #fff;
      box-shadow: 0 10px 18px rgba(31, 95, 207, 0.18);
    }

    .primary-button:hover,
    .primary-button:focus-visible {
      transform: translateY(-1px);
      box-shadow: 0 14px 18px rgba(31, 95, 207, 0.2);
      outline: none;
    }

    .form-status {
      min-height: 22px;
      margin: 0;
      color: #0f766e;
      font-size: 0.82rem;
      font-weight: 600;
    }

    .info-card {
      margin-top: 22px;
      background: linear-gradient(180deg, #f8fbff 0%, #edf4ff 100%);
      border: 1px solid #dfeaff;
      border-radius: 14px;
      color: #0f2a6b;
      box-shadow: 0 10px 24px rgba(15, 42, 107, 0.04);
      padding: 18px 20px;
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }

    .info-card__icon {
      width: 34px;
      height: 34px;
      min-width: 34px;
      border-radius: 10px;
      background: #dfeaff;
      display: grid;
      place-items: center;
      color: #1f5fcf;
      font-weight: 800;
      font-size: 1.05rem;
    }

    .info-card__content h3 {
      margin: 0 0 8px;
      font-size: 1.02rem;
      font-weight: 800;
      color: #0f2a6b;
    }

    .info-card__content p {
      margin: 0;
      color: #4b5d78;
      line-height: 1.7;
      font-size: 0.94rem;
    }

    @media (max-width: 1100px) {
      .content-management-layout {
        grid-template-columns: 1fr;
      }

      .editor-card {
        position: static;
      }
    }

    @media (max-width: 760px) {
      .page-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .records-toolbar {
        flex-direction: column;
        align-items: stretch;
      }

      .records-tools {
        width: 100%;
      }

      .search-field {
        width: 100%;
        flex: 1 1 100%;
      }

      .filter-field {
        flex: 1 1 150px;
      }

      .records-footer {
        align-items: flex-start;
      }
    }

    .legal-content-admin {
      display: block;
    }

    .legal-admin-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 14px;
      flex-wrap: wrap;
    }

    .legal-admin-toolbar .records-heading {
      flex: 1 1 auto;
    }

    .legal-admin-tabs {
      display: flex;
      gap: 4px;
      margin: 0 0 16px;
      padding: 6px;
      background: #fff;
      border: 1px solid #dfe7f0;
      border-radius: 12px;
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
      overflow-x: auto;
    }

    .legal-admin-tab {
      flex: 1 0 0;
      min-width: 180px;
      border: 0;
      border-radius: 9px;
      background: transparent;
      color: #2d4a73;
      padding: 12px 18px;
      font-size: 0.86rem;
      font-weight: 800;
      letter-spacing: 0.01em;
      cursor: pointer;
      transition: background 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
      white-space: nowrap;
    }

    .legal-admin-tab:hover,
    .legal-admin-tab.is-active {
      background: #edf4ff;
      color: #0f2a6b;
      box-shadow: 0 4px 12px rgba(15, 42, 107, 0.08);
    }

    .legal-workspace {
      display: block;
    }

    .legal-list {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      box-shadow: 0 12px 28px rgba(15, 42, 107, 0.04);
      padding: 20px;
      box-sizing: border-box;
    }

    .records-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 14px;
    }

    .records-header h2 {
      margin: 0;
      color: #0f2a6b;
      font-size: 1.05rem;
      font-weight: 800;
    }

    .add-record-button,
    .legal-add-record-button {
      border: 1px solid #a9c5f7;
      border-radius: 8px;
      background: #fff;
      color: #1f5fcf;
      font-weight: 800;
      font-size: 0.8rem;
      padding: 9px 14px;
      cursor: pointer;
    }

    .add-record-button:hover,
    .legal-add-record-button:hover {
      background: #edf4ff;
    }

    .records-toolbar {
      display: grid;
      grid-template-columns: minmax(220px, 1fr) minmax(140px, 180px) minmax(140px, 180px);
      align-items: end;
      gap: 10px;
      margin-bottom: 12px;
    }

    .legal-admin-panel:not(#legal-admin-panel-caraga) .records-toolbar {
      grid-template-columns: minmax(220px, 1fr) minmax(180px, 220px);
    }

    .legal-admin-panel .records-toolbar label:nth-child(2) {
      min-width: 180px;
    }

    .records-toolbar label {
      display: flex;
      flex-direction: column;
      gap: 6px;
      color: #475569;
      font-size: 0.72rem;
      font-weight: 700;
    }

    .records-toolbar input,
    .records-toolbar select {
      width: 100%;
      min-height: 38px;
      box-sizing: border-box;
      border: 1px solid #cbd5e1;
      border-radius: 7px;
      background: #fff;
      color: #334155;
      padding: 9px 10px;
      font: inherit;
    }

    .records-hint {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 12px;
      padding: 9px 12px;
      border: 1px solid #d9e6fa;
      border-radius: 8px;
      background: #f1f6ff;
      color: #2457a6;
      font-size: 0.72rem;
      font-weight: 700;
    }

    .records-hint span {
      display: grid;
      place-items: center;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      background: #1f5fcf;
      color: #fff;
      font-size: 0.65rem;
      font-weight: 800;
    }

    .legal-admin-panel {
      display: none;
      background: transparent;
      border: 0;
      border-radius: 0;
      box-shadow: none;
      overflow: visible;
    }

    .legal-admin-panel.is-active {
      display: block;
    }

    .legal-table-wrap {
      overflow-x: auto;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
    }

    .legal-table {
      width: 100%;
      min-width: 900px;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .legal-table th {
      padding: 10px 12px;
      background: #f8fafc;
      border-bottom: 1px solid #e2e8f0;
      color: #64748b;
      text-align: left;
      font-size: 0.62rem;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .legal-table td {
      padding: 16px 12px;
      border-bottom: 1px solid #e2e8f0;
      color: #334155;
      font-size: 0.82rem;
      vertical-align: middle;
    }

    .legal-table tr:last-child td {
      border-bottom: 0;
    }

    .legal-table td:nth-child(1) { width: 15%; }
    .legal-table td:nth-child(2) { width: 44%; }
    .legal-table td:nth-child(3) { width: 14%; }
    .legal-table td:nth-child(4) { width: 15%; }
    .legal-table td:nth-child(5) { width: 12%; }

    .legal-ref-badge {
      display: inline-flex;
      align-items: center;
      min-height: 26px;
      padding: 0 10px;
      border-radius: 8px;
      background: #eef4ff;
      color: #1f5fcf;
      font-size: 0.7rem;
      font-weight: 800;
      letter-spacing: 0.02em;
      text-transform: uppercase;
    }

    .legal-title {
      display: block;
      color: #0f2a6b;
      font-weight: 800;
      line-height: 1.5;
    }

    .legal-region-label {
      display: inline-flex;
      align-items: center;
      min-height: 26px;
      padding: 0 10px;
      border-radius: 999px;
      background: #eef4ff;
      color: #1f5fcf;
      font-size: 0.72rem;
      font-weight: 700;
    }

    .legal-public-link {
      display: inline-flex;
      align-items: center;
      min-height: 28px;
      padding: 0 10px;
      border-radius: 8px;
      background: #e8f7ef;
      color: #0c8d5d;
      font-size: 0.7rem;
      font-weight: 800;
      border: 1px solid rgba(16, 185, 129, 0.15);
      text-decoration: none;
    }

    .legal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 6px;
    }

    .legal-actions button {
      min-width: 52px;
      min-height: 30px;
      padding: 6px 10px;
      border-radius: 6px;
      border: 1px solid #bfd5f9;
      background: #fff;
      color: #1f5fcf;
      font-size: 0.7rem;
      font-weight: 700;
      cursor: pointer;
    }

    .legal-actions .delete {
      border-color: #f2bebe;
      color: #c33434;
    }

    .legal-actions button {
      min-width: 32px;
      width: 32px;
      height: 32px;
      padding: 6px;
      border-radius: 8px;
      font-size: 0;
    }

    .legal-actions button svg {
      width: 16px;
      height: 16px;
      fill: none;
      stroke: currentColor;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .legal-editor-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(15, 42, 107, 0.12);
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.2s ease, visibility 0.2s ease;
      z-index: 1090;
    }

    .legal-editor-backdrop.is-open {
      opacity: 1;
      visibility: visible;
    }

    .legal-editor-panel {
      position: fixed;
      top: 50%;
      right: 24px;
      transform: translateY(-50%) translateX(calc(100% + 24px));
      width: min(520px, calc(100vw - 32px));
      max-height: 90vh;
      transition: transform 0.22s ease;
      z-index: 1100;
    }

    .legal-editor-panel.is-open {
      transform: translateY(-50%) translateX(0);
    }

    .legal-editor-card {
      background: #fff;
      border: 1px solid #dfe7f0;
      border-radius: 18px;
      box-shadow: 0 22px 56px rgba(15, 42, 107, 0.14);
      overflow: hidden;
    }

    .legal-editor-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 12px;
      padding: 18px 20px 14px;
      border-bottom: 1px solid #edf2f7;
      background: linear-gradient(180deg, #f8fbff 0%, #fff 100%);
    }

    .legal-editor-header h3 {
      margin: 0 0 4px;
      color: #0f2a6b;
      font-size: 1.2rem;
      font-weight: 800;
    }

    .legal-editor-header p {
      margin: 0;
      color: #64748b;
      font-size: 0.82rem;
    }

    .legal-editor-close {
      border: 0;
      background: transparent;
      color: #475569;
      font-size: 2rem;
      line-height: 1;
      cursor: pointer;
      margin-top: -4px;
    }

    .legal-editor-form {
      padding: 18px 20px 20px;
      display: grid;
      gap: 14px;
    }

    .legal-editor-form label {
      display: grid;
      gap: 8px;
      color: #334155;
      font-size: 0.84rem;
      font-weight: 700;
    }

    .legal-editor-form input,
    .legal-editor-form textarea,
    .legal-editor-form select {
      width: 100%;
      box-sizing: border-box;
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      min-height: 42px;
      background: #fff;
      padding: 10px 12px;
      font: inherit;
      color: #0f172a;
    }

    .legal-editor-form textarea {
      min-height: 120px;
      resize: vertical;
    }

    .legal-editor-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      padding-top: 6px;
    }

    .legal-editor-actions button {
      min-height: 38px;
      border-radius: 9px;
      padding: 0 14px;
      font-size: 0.82rem;
      font-weight: 700;
      cursor: pointer;
    }

    .legal-editor-actions .secondary {
      border: 1px solid #dfe7f0;
      background: #fff;
      color: #334155;
    }

    .legal-editor-actions .primary {
      border: 0;
      background: linear-gradient(135deg, #1f5fcf 0%, #174da8 100%);
      color: #fff;
      box-shadow: 0 8px 18px rgba(31, 95, 207, 0.2);
    }

    /* Match the Home Management right-side editor without changing legal fields. */
    .legal-editor-backdrop {
      background: transparent;
    }

    .legal-editor-panel {
      top: 0;
      right: 0;
      bottom: 0;
      width: min(390px, 100%);
      max-height: none;
      transform: translateX(100%);
    }

    .legal-editor-panel.is-open {
      transform: translateX(0);
    }

    .legal-editor-card {
      min-height: 100vh;
      border: 0;
      border-radius: 0;
      box-shadow: -8px 0 24px rgba(15, 42, 107, 0.14);
      background: #f4f7fb;
    }

    .legal-editor-header {
      padding: 24px 24px 16px;
      background: #f4f7fb;
      border-bottom: 0;
    }

    .legal-editor-header h3 {
      margin-bottom: 6px;
      font-size: 1.15rem;
    }

    .legal-editor-header p {
      font-size: 0.78rem;
    }

    .legal-editor-close {
      font-size: 1.3rem;
    }

    .legal-editor-form {
      padding: 0 24px 24px;
      gap: 0;
    }

    .legal-editor-form label {
      gap: 5px;
      margin: 10px 0 5px;
      font-size: 0.74rem;
    }

    .legal-editor-form input,
    .legal-editor-form textarea,
    .legal-editor-form select {
      min-height: 38px;
      padding: 9px 10px;
      border-radius: 7px;
      font-size: 0.84rem;
    }

    .legal-editor-form textarea {
      min-height: 105px;
    }

    .legal-editor-actions {
      align-items: center;
      gap: 8px;
      margin-top: 16px;
      padding-top: 0;
    }

    .legal-editor-actions button {
      min-height: 36px;
      padding: 8px 13px;
      border-radius: 7px;
      font-size: 0.78rem;
    }

    .lgrc-tabs {
      display: none !important;
    }

    .lgrc-tab {
      display: none !important;
    }

    .lgrc-section-shell {
      border: 1px solid #dfe7f0;
      border-radius: 16px;
      background: #fff;
      box-shadow: 0 8px 24px rgba(15, 42, 107, 0.04);
      overflow: hidden;
    }

    .lgrc-section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 18px 20px;
      border-bottom: 1px solid #edf2f7;
      background: #f7faff;
    }

    .lgrc-section-title {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }

    .lgrc-section-icon {
      width: 38px;
      height: 38px;
      display: grid;
      place-items: center;
      border-radius: 10px;
      background: rgba(31, 95, 207, 0.08);
      color: #1f5fcf;
      flex-shrink: 0;
    }

    .lgrc-section-icon svg {
      width: 19px;
      height: 19px;
      fill: none;
      stroke: currentColor;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .lgrc-section-copy h2 {
      margin: 0;
      color: #0f2a6b;
      font-size: 1.05rem;
      font-weight: 800;
    }

    .lgrc-section-copy p {
      margin: 4px 0 0;
      color: #64748b;
      font-size: 0.88rem;
    }

    .lgrc-section-tools {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 10px;
      flex-wrap: wrap;
      margin-left: auto;
    }

    .lgrc-search-field,
    .lgrc-filter-field {
      display: flex;
      align-items: center;
      height: 40px;
      border: 1px solid #dfe7f0;
      border-radius: 10px;
      background: #fff;
      overflow: hidden;
      box-sizing: border-box;
    }

    .lgrc-search-field {
      min-width: 220px;
    }

    .lgrc-search-field svg {
      width: 16px;
      height: 16px;
      margin-left: 12px;
      stroke: #6b7280;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
      flex-shrink: 0;
    }

    .lgrc-search-field input,
    .lgrc-filter-field select {
      width: 100%;
      min-height: 40px;
      border: 0;
      background: transparent;
      color: #0f172a;
      font: inherit;
      outline: none;
      box-sizing: border-box;
    }

    .lgrc-search-field input {
      padding: 0 12px 0 8px;
      font-size: 0.9rem;
    }

    .lgrc-filter-field {
      min-width: 140px;
    }

    .lgrc-filter-field select {
      appearance: none;
      padding: 0 34px 0 12px;
      background-image: linear-gradient(45deg, transparent 50%, #64748b 50%), linear-gradient(135deg, #64748b 50%, transparent 50%);
      background-position: calc(100% - 18px) calc(50% - 2px), calc(100% - 12px) calc(50% - 2px);
      background-size: 6px 6px, 6px 6px;
      background-repeat: no-repeat;
      color: #334155;
      font-size: 0.88rem;
    }

    .lgrc-empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      min-height: 360px;
      padding: 32px 20px;
      color: #0f2a6b;
    }

    .lgrc-empty-state-icon {
      width: 96px;
      height: 96px;
      display: grid;
      place-items: center;
      margin-bottom: 18px;
      border-radius: 50%;
      background: rgba(31, 95, 207, 0.06);
      color: #1f5fcf;
      border: 1px solid rgba(31, 95, 207, 0.12);
    }

    .lgrc-empty-state-icon svg {
      width: 44px;
      height: 44px;
      fill: none;
      stroke: currentColor;
      stroke-width: 1.8;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .lgrc-empty-state h3 {
      margin: 0;
      font-size: clamp(1.7rem, 2vw, 2.2rem);
      line-height: 1.2;
      letter-spacing: -0.02em;
      color: #0f2a6b;
    }

    .lgrc-empty-state p {
      margin: 10px 0 0;
      color: #64748b;
      font-size: 1rem;
    }

    /* LGRC Editor Tabs */
    .editor-tabs-container {
      display: flex;
      gap: 0;
      padding: 0;
      margin: 0 0 16px;
      border-bottom: 1px solid #e2e8f0;
    }

    .editor-tabs {
      display: flex;
      gap: 0;
      width: 100%;
    }

    .editor-tab {
      flex: 1;
      border: 0;
      border-bottom: 2px solid transparent;
      background: transparent;
      color: #64748b;
      padding: 12px 16px;
      font-size: 0.76rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.18s ease;
      white-space: nowrap;
      text-align: center;
    }

    .editor-tab:hover {
      background: #f8fafc;
      color: #334155;
    }

    .editor-tab.is-active {
      background: #edf4ff;
      border-bottom-color: #1f5fcf;
      color: #1f5fcf;
    }

    /* Preview Content */
    .editor-preview {
      padding: 16px 0;
      max-height: 60vh;
      overflow-y: auto;
    }

    .preview-content {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 20px;
      line-height: 1.6;
      color: #334155;
      font-size: 0.875rem;
    }

    .preview-content h1,
    .preview-content h2,
    .preview-content h3,
    .preview-content h4,
    .preview-content h5,
    .preview-content h6 {
      margin: 16px 0 8px;
      color: #0f2a6b;
      font-weight: 700;
    }

    .preview-content h1 { font-size: 1.5rem; }
    .preview-content h2 { font-size: 1.3rem; }
    .preview-content h3 { font-size: 1.1rem; }

    .preview-content p {
      margin: 0 0 12px;
    }

    .preview-content ul,
    .preview-content ol {
      margin: 12px 0;
      padding-left: 24px;
    }

    .preview-content li {
      margin: 6px 0;
    }

    .preview-content a {
      color: #1f5fcf;
      text-decoration: none;
    }

    .preview-content a:hover {
      text-decoration: underline;
    }

    @media (max-width: 640px) {
      .legal-admin-panel__head {
        padding: 18px 18px 10px;
      }

      .legal-admin-list li {
        padding: 16px 18px;
      }

      .legal-admin-toolbar {
        align-items: flex-start;
      }

      .lgrc-tabs {
        width: 100%;
        overflow-x: auto;
      }

      .editor-preview {
        max-height: 50vh;
      }
    }
  </style>
</head>
<body class="admin-dashboard-ref-body">
  <div class="admin-portal-shell">
    <?php include __DIR__ . '/admin-sidebar.php'; ?>
    <main class="admin-main">
      <?php if ($section === 'legal') : ?>
        <section class="page-content-admin legal-content-admin">
          <header class="page-header">
            <div>
              <h1><?php echo htmlspecialchars($page['label']); ?> Management</h1>
              <p>Manage the legal opinions archive by region while keeping the public pages unchanged.</p>
            </div>
            <button type="button" class="add-record-button admin-add-button" id="legal-add-record-button">+ Add Legal Opinion</button>
          </header>

          <nav class="legal-admin-tabs" aria-label="Legal opinions regions">
            <?php foreach ($legalTabData as $key => $tab) : ?>
              <button type="button" class="legal-admin-tab <?php echo $key === 'dilg' ? 'is-active' : ''; ?>" data-legal-tab="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($tab['label']); ?></button>
            <?php endforeach; ?>
          </nav>

          <div class="legal-workspace">
            <?php foreach ($legalTabData as $key => $tab) : ?>
              <section class="legal-admin-panel <?php echo $key === 'dilg' ? 'is-active' : ''; ?>" id="legal-admin-panel-<?php echo htmlspecialchars($key); ?>" role="tabpanel" aria-label="<?php echo htmlspecialchars($tab['label']); ?>">
                <div class="legal-list">
                  <div class="records-header">
                    <h2><?php echo htmlspecialchars($tab['label']); ?></h2>
                  </div>

                  <div class="records-toolbar">
                    <label>
                      <input type="search" placeholder="Search content...">
                    </label>
                    <label>
                      <select>
                        <option>All Status</option>
                        <option>Published</option>
                        <option>Draft</option>
                      </select>
                    </label>
                    <?php if ($key === 'caraga') : ?>
                      <label>
                        <select class="caraga-year-filter" aria-label="Filter Caraga opinions by year">
                          <option value="all">All Years</option>
                          <?php foreach ($caragaYears as $year) : ?><option value="<?php echo htmlspecialchars($year); ?>"><?php echo htmlspecialchars($year); ?></option><?php endforeach; ?>
                        </select>
                      </label>
                    <?php endif; ?>
                  </div>

                  <div class="records-hint"><span>i</span> Regional entries are grouped to match the public website sections.</div>

                  <div class="legal-table-wrap">
                    <table class="legal-table">
                      <thead>
                        <tr>
                          <th>Reference</th>
                          <th>Title</th>
                          <th>Region</th>
                          <th>Public link</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($tab['items'] as $index => $item) : ?>
                          <tr data-legal-tab="<?php echo htmlspecialchars($key); ?>" data-legal-index="<?php echo htmlspecialchars((string)$index); ?>" data-legal-year="<?php echo htmlspecialchars($item['year'] ?? ''); ?>">
                            <td><span class="legal-ref-badge"><?php echo htmlspecialchars($item['ref']); ?></span></td>
                            <td><span class="legal-title"><?php echo htmlspecialchars($item['title']); ?></span></td>
                            <td><span class="legal-region-label"><?php echo htmlspecialchars($tab['label']); ?></span></td>
                            <td><a href="<?php echo htmlspecialchars($item['link']); ?>" class="legal-public-link" target="_blank" rel="noopener noreferrer">Public link</a></td>
                            <td>
                              <div class="legal-actions">
                                <button type="button" data-legal-action="view" data-legal-tab="<?php echo htmlspecialchars($key); ?>" data-legal-index="<?php echo htmlspecialchars((string)$index); ?>" aria-label="View legal opinion" title="View"><svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg></button>
                                <button type="button" data-legal-action="edit" data-legal-tab="<?php echo htmlspecialchars($key); ?>" data-legal-index="<?php echo htmlspecialchars((string)$index); ?>" aria-label="Edit legal opinion" title="Edit"><svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg></button>
                                <button type="button" class="delete" data-legal-action="delete" data-legal-tab="<?php echo htmlspecialchars($key); ?>" data-legal-index="<?php echo htmlspecialchars((string)$index); ?>" aria-label="Delete legal opinion" title="Delete"><svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg></button>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </section>
            <?php endforeach; ?>
          </div>

          <div class="legal-editor-backdrop" id="legal-editor-backdrop"></div>
          <aside class="legal-editor-panel" id="legal-editor-panel" aria-hidden="true">
            <div class="legal-editor-card">
              <div class="legal-editor-header">
                <div>
                  <h3 id="legal-editor-heading">Edit Legal Opinion</h3>
                  <p id="legal-editor-subtitle">Update the legal opinion details.</p>
                </div>
                <button type="button" class="legal-editor-close" id="legal-close-editor" aria-label="Close editor">×</button>
              </div>

              <form class="legal-editor-form" id="legal-editor-form">
                <input type="hidden" id="legal-editor-tab" name="legalTab" />
                <input type="hidden" id="legal-editor-index" name="legalIndex" />

                <label>
                  Reference
                  <input id="legal-reference" name="reference" type="text" placeholder="LO-021s2026" required>
                </label>

                <label>
                  Title
                  <textarea id="legal-title" name="title" placeholder="Enter legal opinion title" required></textarea>
                </label>

                <label>
                  Region
                  <select id="legal-region" name="region">
                    <?php foreach ($legalTabData as $tabKey => $tab) : ?>
                      <option value="<?php echo htmlspecialchars($tabKey); ?>"><?php echo htmlspecialchars($tab['label']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>

                <label>
                  Public Link
                  <input id="legal-link" name="link" type="url" placeholder="https://example.com" required>
                </label>

                <div class="legal-editor-actions">
                  <button type="button" class="secondary" id="legal-cancel-editor">Cancel</button>
                  <button type="submit" class="primary">Save Changes</button>
                </div>
              </form>
            </div>
          </aside>
        </section>
      <?php elseif ($section === 'lgrc-gad') : ?>
        <section class="page-content-admin">
          <header class="page-header">
            <div>
              <h1><?php echo htmlspecialchars($page['label']); ?> Management</h1>
              <p>Manage content for: About MSAC.</p>
            </div>
            <button type="button" class="add-record-button admin-add-button" id="lgrc-add-record-button">+ Add MSAC Content</button>
          </header>

          <div class="editor-backdrop" id="lgrc-editor-backdrop"></div>

          <div class="content-management-layout">
            <section class="content-list-panel">
              <div class="lgrc-section-shell">
                <div class="lgrc-section-header">
                  <div class="lgrc-section-title">
                    <div class="lgrc-section-icon" aria-hidden="true">
                      <svg viewBox="0 0 24 24"><path d="M7 3h8l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M15 3v5h5"/><path d="M9 13h6M9 17h6M9 9h2"/></svg>
                    </div>
                    <div class="lgrc-section-copy">
                      <h2>About MSAC</h2>
                      <p>Manage the MSAC content section.</p>
                    </div>
                  </div>

                  <div class="lgrc-section-tools">
                    <label class="lgrc-search-field" aria-label="Search categories">
                      <input id="lgrc-record-search" type="search" placeholder="Search title..." />
                    </label>

                    <label class="lgrc-filter-field" aria-label="Filter categories by status">
                      <select id="lgrc-record-filter">
                        <option value="all">Filter</option>
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                      </select>
                    </label>
                  </div>
                </div>

                <div class="records-table-wrap">
                  <table class="records-table">
                    <thead>
                      <tr>
                        <th style="width: 20%;">Order</th>
                        <th style="width: 35%;">Title</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 22%;">Last Updated</th>
                        <th style="width: 8%;">Actions</th>
                      </tr>
                    </thead>
                    <tbody id="lgrc-records-body"></tbody>
                  </table>
                </div>

                <div class="records-footer">
                  <div class="records-summary" id="lgrc-records-summary"></div>
                  <nav class="pagination" id="lgrc-pagination" aria-label="Pagination"></nav>
                </div>
              </div>
            </section>

            <aside class="editor-panel" id="lgrc-editor-panel" aria-hidden="true">
              <div class="editor-card">
                <div class="editor-header">
                  <h3 id="lgrc-form-heading">Edit MSAC Content</h3>
                  <span class="form-hint" id="lgrc-form-mode-tag">Edit</span>
                  <button type="button" class="editor-close-btn" id="lgrc-close-editor" aria-label="Close editor">×</button>
                </div>

                <div class="editor-tabs-container">
                  <div class="editor-tabs">
                    <button type="button" class="editor-tab is-active" data-lgrc-editor-tab="form">
                      <span>✎ Edit</span>
                    </button>
                    <button type="button" class="editor-tab" data-lgrc-editor-tab="preview">
                      <span>👁 Preview</span>
                    </button>
                  </div>
                </div>

                <form class="editor-form" id="lgrc-content-form" data-lgrc-editor-view="form">
                  <input type="hidden" id="lgrc-content-id" name="id" />

                  <label for="lgrc-content-title">Title
                    <input id="lgrc-content-title" name="title" type="text" placeholder="Enter category title" required>
                  </label>

                  <label for="lgrc-content-description">Content / Description (HTML supported)
                    <textarea id="lgrc-content-description" name="description" placeholder="Enter content, HTML, or Markdown..." style="min-height: 200px;"></textarea>
                  </label>

                  <label for="lgrc-content-link">Link URL (optional)
                    <input id="lgrc-content-link" name="link_url" type="url" placeholder="https://example.com">
                  </label>

                  <label class="checkbox-row">
                    <input id="lgrc-content-active" name="is_active" type="checkbox" checked>
                    <span>Published on public page</span>
                  </label>

                  <div class="form-actions">
                    <button type="button" class="secondary-button" id="lgrc-clear-content">Cancel</button>
                    <button type="submit" class="primary-button" id="lgrc-save-content-button">Save Content</button>
                  </div>

                  <p class="form-status" id="lgrc-content-status" role="status" aria-live="polite"></p>
                </form>

                <div class="editor-preview" id="lgrc-content-preview" data-lgrc-editor-view="preview" style="display: none;">
                  <div class="preview-content" id="lgrc-preview-html"></div>
                </div>
              </div>
            </aside>
          </div>
        </section>
      <?php else: ?>
      <section class="page-content-admin">
        <header class="page-header">
          <div>
            <h1><?php echo htmlspecialchars($page['label']); ?> Management</h1>
            <p>Manage published content for this website section.</p>
          </div>
          <button type="button" class="add-record-button admin-add-button" id="add-record-button">+ Add New Content</button>
        </header>

        <div class="editor-backdrop" id="editor-backdrop"></div>

        <div class="content-management-layout">
          <section class="content-list-panel">
            <div class="records-card">
              <div class="records-toolbar">
                <div class="records-heading">
                  <div class="records-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M7 3h8l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M15 3v5h5"/><path d="M9 13h6M9 17h6M9 9h2"/></svg>
                  </div>
                  <div>
                    <h2>Page Records</h2>
                    <p>List of all <?php echo htmlspecialchars($page['label']); ?> service entries.</p>
                  </div>
                </div>

                        <div class="records-tools">
                  <label class="search-field" aria-label="Search records by title">
                    <input id="record-search" type="search" placeholder="Search title..." />
                  </label>

                  <label class="filter-field" aria-label="Filter records by status">
                    <select id="record-filter">
                      <option value="all">All Statuses</option>
                      <option value="published">Published</option>
                      <option value="draft">Draft</option>
                    </select>
                  </label>

                  <button type="button" class="clear-filters-button" id="record-clear-filters">Clear Filters</button>
                </div>
              </div>

              <div class="records-table-wrap">
                <table class="records-table">
                  <thead>
                    <tr>
                      <th>Title</th>
                      <th>Status</th>
                      <th>Last Updated</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody id="records-body"></tbody>
                </table>
              </div>

              <div class="records-footer">
                <div class="records-summary" id="records-summary">Showing 0 entries</div>
                <nav class="pagination" id="pagination" aria-label="Pagination"></nav>
              </div>
            </div>
          </section>

          <aside class="editor-panel" id="editor-panel" aria-hidden="true">
            <div class="editor-card">
              <div class="editor-header">
                <h3 id="form-heading">Add New Content</h3>
                <button type="button" class="editor-close-btn" id="close-editor" aria-label="Close editor">×</button>
              </div>

              <form class="editor-form" id="content-form">
                <input type="hidden" id="content-id" name="id" />

                <label for="content-title">Title
                  <input id="content-title" name="title" type="text" placeholder="Enter title" required>
                </label>

                <label for="content-description">Content / Description
                  <textarea id="content-description" name="description" placeholder="Enter content or description..."></textarea>
                </label>

                <?php if ($section === 'charter'): ?>
                <label for="content-html">Detailed Charter Content (HTML supported)
                  <textarea id="content-html" name="content_html" placeholder="Edit the detailed tables, requirements, and note shown inside this charter entry..." style="min-height: 260px;"></textarea>
                </label>
                <?php endif; ?>

                <label for="content-link">Link URL (optional)
                  <input id="content-link" name="link_url" type="url" placeholder="https://example.com">
                </label>

                <label class="checkbox-row">
                  <input id="content-active" name="is_active" type="checkbox" checked>
                  <span>Published on public page</span>
                </label>

                <div class="form-actions">
                  <button type="button" class="secondary-button" id="clear-content">Clear</button>
                  <button type="submit" class="primary-button" id="save-content-button">Save Content</button>
                </div>

                <p class="form-status" id="content-status" role="status" aria-live="polite"></p>
              </form>
            </div>
          </aside>
        </div>
      </section>
      <?php endif; ?>
    </main>
  </div>

  <script>
    (function () {
      var api = 'auth/content_api.php?section=<?php echo rawurlencode($section); ?>';
      var pageSize = 5;
      var currentPage = 1;
      var currentSearch = '';
      var currentFilter = 'all';
      var records = [];

      var form = document.getElementById('content-form');
      var formHeading = document.getElementById('form-heading');
      var formModeTag = document.getElementById('form-mode-tag');
      var recordsBody = document.getElementById('records-body');
      var summaryEl = document.getElementById('records-summary');
      var paginationEl = document.getElementById('pagination');
      var searchInput = document.getElementById('record-search');
      var filterSelect = document.getElementById('record-filter');
      var clearFiltersButton = document.getElementById('record-clear-filters');
      var statusMessage = document.getElementById('content-status');
      var clearButton = document.getElementById('clear-content');
      var addButton = document.getElementById('add-record-button');
      var saveContentButton = document.getElementById('save-content-button');
      var editorPanel = document.getElementById('editor-panel');
      var editorBackdrop = document.getElementById('editor-backdrop');
      var closeEditorButton = document.getElementById('close-editor');

      function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
          return ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
          })[char];
        });
      }

      function formatDate(value) {
        if (!value) {
          return '—';
        }

        var date = new Date(value);
        if (Number.isNaN(date.getTime())) {
          return value;
        }

        return date.toLocaleString('en-PH', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
      }

      function setFormMode(isEdit) {
        formHeading.textContent = isEdit ? "Edit Citizen's Charter" : 'Add New Content';
        if (formModeTag) {
          formModeTag.style.display = 'none';
        }
        if (saveContentButton) {
          saveContentButton.textContent = isEdit ? 'Save Changes' : 'Save Content';
        }
      }

      function closeEditor() {
        var layout = document.querySelector('.content-management-layout');
        if (editorPanel) {
          editorPanel.classList.remove('is-open');
          editorPanel.setAttribute('aria-hidden', 'true');
        }
        if (layout) {
          layout.classList.remove('has-editor');
        }
        if (editorBackdrop) {
          editorBackdrop.classList.remove('is-open');
        }
      }

      function openEditor() {
        var layout = document.querySelector('.content-management-layout');
        if (editorPanel) {
          editorPanel.classList.add('is-open');
          editorPanel.setAttribute('aria-hidden', 'false');
        }
        if (layout) {
          layout.classList.add('has-editor');
        }
        if (editorBackdrop) {
          editorBackdrop.classList.add('is-open');
        }
      }

      function clearForm() {
        form.reset();
        document.getElementById('content-id').value = '';
        document.getElementById('content-active').checked = true;
        statusMessage.textContent = '';
        setFormMode(false);
        closeEditor();
      }

      function renderPagination(totalItems) {
        var totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        if (currentPage > totalPages) {
          currentPage = totalPages;
        }

        var buttons = [];
        buttons.push('<button type="button" class="page-btn" data-page="prev" ' + (currentPage === 1 ? 'disabled' : '') + ' aria-label="Previous page">&lsaquo;</button>');

        for (var i = 1; i <= totalPages; i += 1) {
          buttons.push('<button type="button" class="page-btn ' + (i === currentPage ? 'is-current' : '') + '" data-page="' + i + '">' + i + '</button>');
        }

        buttons.push('<button type="button" class="page-btn" data-page="next" ' + (currentPage === totalPages ? 'disabled' : '') + ' aria-label="Next page">&rsaquo;</button>');
        paginationEl.innerHTML = buttons.join('');
      }

      function renderTable() {
        var filtered = records.filter(function (row) {
          var text = (row.title || '') + ' ' + (row.description || '');
          var matchesSearch = !currentSearch || text.toLowerCase().includes(currentSearch.toLowerCase());
          var status = row.is_active ? 'published' : 'draft';
          var matchesFilter = currentFilter === 'all' || status === currentFilter;
          return matchesSearch && matchesFilter;
        });

        var totalItems = filtered.length;
        var totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        currentPage = Math.min(currentPage, totalPages);

        var start = (currentPage - 1) * pageSize;
        var pageItems = filtered.slice(start, start + pageSize);

        if (!pageItems.length) {
          recordsBody.innerHTML = '<tr><td colspan="4"><div class="empty-state" style="padding: 26px 18px; text-align: center; color: #64748b;">' +
            '<div style="display:inline-flex; align-items:center; justify-content:center; width:46px; height:46px; border-radius:12px; background:#edf4ff; color:#1f5fcf; margin-bottom:10px;">' +
            '<svg viewBox="0 0 24 24" style="width:20px; height:20px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round;" aria-hidden="true"><path d="M7 3h8l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M15 3v5h5"/><path d="M9 13h6M9 17h6M9 9h2"/></svg>' +
            '</div><div style="font-size:0.96rem; font-weight:700; color:#0f2a6b; margin-bottom:4px;">No Citizen\'s Charter records yet</div><div style="font-size:0.8rem; color:#64748b;">Create a new record to get started.</div></div></td></tr>';
        } else {
          recordsBody.innerHTML = pageItems.map(function (row) {
            var statusClass = row.is_active ? 'status-badge--published' : 'status-badge--draft';
            var statusText = row.is_active ? 'Published' : 'Draft';

            return '<tr>' +
              '<td>' +
                '<div class="record-title-wrap">' +
                  '<div class="record-doc-icon" aria-hidden="true">' +
                    '<svg viewBox="0 0 24 24"><path d="M7 3h8l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M15 3v5h5"/><path d="M9 13h6M9 17h6M9 9h2"/></svg>' +
                  '</div>' +
                  '<div class="record-title">' +
                    '<strong>' + escapeHtml(row.title || '') + '</strong>' +
                    '<span class="record-subtitle">' + escapeHtml(row.description || "Citizen's Charter service entry") + '</span>' +
                  '</div>' +
                '</div>' +
              '</td>' +
              '<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>' +
              '<td><span class="table-date">' + escapeHtml(formatDate(row.updated_at)) + '</span></td>' +
              '<td>' +
                '<div class="actions-cell">' +
                  '<button type="button" class="icon-button" data-action="view" data-id="' + row.id + '" aria-label="View content" title="View">' +
                    '<svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>' +
                  '</button>' +
                  '<button type="button" class="icon-button" data-action="edit" data-id="' + row.id + '" aria-label="Edit content" title="Edit">' +
                    '<svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>' +
                  '</button>' +
                  '<button type="button" class="icon-button icon-button--danger" data-action="delete" data-id="' + row.id + '" aria-label="Delete content" title="Delete">' +
                    '<svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>' +
                  '</button>' +
                '</div>' +
              '</td>' +
            '</tr>';
          }).join('');
        }

        var startNumber = totalItems ? start + 1 : 0;
        var endNumber = Math.min(start + pageSize, totalItems);
        summaryEl.textContent = 'Showing ' + startNumber + ' to ' + endNumber + ' of ' + totalItems + ' entries';

        renderPagination(totalItems);
      }

      function loadRecords() {
        fetch(api)
          .then(function (response) {
            if (!response.ok) {
              throw new Error('Unable to load records.');
            }
            return response.json();
          })
          .then(function (data) {
            records = Array.isArray(data.records) ? data.records : [];
            currentPage = 1;
            renderTable();
          })
          .catch(function () {
            recordsBody.innerHTML = '<tr><td colspan="4"><div class="empty-state" style="padding: 26px 18px; text-align: center; color: #b91c1c;">' +
              '<div style="display:inline-flex; align-items:center; justify-content:center; width:46px; height:46px; border-radius:12px; background:#fff1f2; color:#b91c1c; margin-bottom:10px;">' +
              '<svg viewBox="0 0 24 24" style="width:20px; height:20px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round;" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16h.01"/></svg>' +
              '</div><div style="font-size:0.96rem; font-weight:700; color:#0f2a6b; margin-bottom:4px;">Unable to load records</div><div style="font-size:0.8rem; color:#64748b;">Please refresh the page or try again later.</div></div></td></tr>';
            summaryEl.textContent = 'Showing 0 entries';
            paginationEl.innerHTML = '';
          });
      }

      function populateForm(row) {
        document.getElementById('content-id').value = row.id || '';
        document.getElementById('content-title').value = row.title || '';
        document.getElementById('content-description').value = row.description || '';
        var detailField = document.getElementById('content-html');
        if (detailField) {
          detailField.value = row.content_html || '';
        }
        document.getElementById('content-link').value = row.link_url || '';
        document.getElementById('content-active').checked = !!row.is_active;
        statusMessage.textContent = 'Editing selected record.';
        setFormMode(true);
      }

      if (form) {
      recordsBody.addEventListener('click', function (event) {
        var actionButton = event.target.closest('[data-action]');
        if (!actionButton) {
          return;
        }

        var action = actionButton.getAttribute('data-action');
        var id = actionButton.getAttribute('data-id');
        var row = records.find(function (item) {
          return String(item.id) === String(id);
        });

        if (!row) {
          return;
        }

        if (action === 'view') {
          if (row.link_url) {
            window.open(row.link_url, '_blank', 'noopener,noreferrer');
          }
          return;
        }

        if (action === 'edit') {
          populateForm(row);
          openEditor();
          return;
        }

        if (action === 'delete') {
          if (!window.confirm('Delete this content record?')) {
            return;
          }

          var deleteBody = new URLSearchParams({
            action: 'delete',
            section: '<?php echo htmlspecialchars($section, ENT_QUOTES); ?>',
            id: String(id)
          });

          fetch('auth/content_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: deleteBody.toString()
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('Delete failed.');
              }
              statusMessage.textContent = 'Record deleted successfully.';
              loadRecords();
            })
            .catch(function () {
              statusMessage.textContent = 'Unable to delete record.';
            });
        }
      });

      form.addEventListener('submit', function (event) {
        event.preventDefault();

        var id = document.getElementById('content-id').value;
        var payload = new URLSearchParams({
          action: id ? 'update' : 'create',
          section: '<?php echo htmlspecialchars($section, ENT_QUOTES); ?>',
          id: id || '',
          title: document.getElementById('content-title').value.trim(),
          description: document.getElementById('content-description').value.trim(),
          link_url: document.getElementById('content-link').value.trim(),
          is_active: document.getElementById('content-active').checked ? '1' : '0'
        });

        var detailField = document.getElementById('content-html');
        if (detailField) {
          payload.set('content_html', detailField.value.trim());
        }

        if (!payload.get('title')) {
          statusMessage.textContent = 'Title is required.';
          return;
        }

        fetch('auth/content_api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: payload.toString()
        })
          .then(function (response) {
            return response.json().then(function (data) {
              if (!response.ok || data.error) {
                throw new Error(data.error || 'Unable to save record.');
              }
              return data;
            });
          })
          .then(function () {
            statusMessage.textContent = id ? 'Record updated successfully.' : 'Record created successfully.';
            clearForm();
            loadRecords();
          })
          .catch(function (error) {
            statusMessage.textContent = error.message || 'Unable to save record.';
          });
      });

      clearButton.addEventListener('click', clearForm);
      closeEditorButton.addEventListener('click', closeEditor);
      editorBackdrop.addEventListener('click', closeEditor);
      addButton.addEventListener('click', function () {
        clearForm();
        openEditor();
        document.getElementById('content-title').focus();
      });

      searchInput.addEventListener('input', function () {
        currentSearch = this.value.trim();
        currentPage = 1;
        renderTable();
      });

      filterSelect.addEventListener('change', function () {
        currentFilter = this.value;
        currentPage = 1;
        renderTable();
      });

      if (clearFiltersButton) {
        clearFiltersButton.addEventListener('click', function () {
          currentSearch = '';
          currentFilter = 'all';
          searchInput.value = '';
          filterSelect.value = 'all';
          currentPage = 1;
          renderTable();
        });
      }

      paginationEl.addEventListener('click', function (event) {
        var target = event.target.closest('[data-page]');
        if (!target) {
          return;
        }

        var pageValue = target.getAttribute('data-page');
        var filtered = records.filter(function (row) {
          var text = (row.title || '') + ' ' + (row.description || '');
          var matchesSearch = !currentSearch || text.toLowerCase().includes(currentSearch.toLowerCase());
          var status = row.is_active ? 'published' : 'draft';
          var matchesFilter = currentFilter === 'all' || status === currentFilter;
          return matchesSearch && matchesFilter;
        });

        var totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));

        if (pageValue === 'prev') {
          currentPage = Math.max(1, currentPage - 1);
        } else if (pageValue === 'next') {
          currentPage = Math.min(totalPages, currentPage + 1);
        } else {
          currentPage = Number(pageValue);
        }

        renderTable();
      });

      }

      // MSAC Management functionality
      if (document.getElementById('lgrc-record-search')) {
        var lgrcApi = 'auth/content_api.php?section=lgrc-gad';
        var lgrcPageSize = 5;
        var lgrcCurrentPage = 1;
        var lgrcCurrentSearch = '';
        var lgrcCurrentFilter = 'all';
        var lgrcCurrentTab = '';
        var lgrcRecords = [];

        var lgrcRecordsBody = document.getElementById('lgrc-records-body');
        var lgrcSummaryEl = document.getElementById('lgrc-records-summary');
        var lgrcPaginationEl = document.getElementById('lgrc-pagination');
        var lgrcSearchInput = document.getElementById('lgrc-record-search');
        var lgrcFilterSelect = document.getElementById('lgrc-record-filter');
        var lgrcStatusMessage = document.getElementById('lgrc-content-status');
        var lgrcForm = document.getElementById('lgrc-content-form');
        var lgrcFormHeading = document.getElementById('lgrc-form-heading');
        var lgrcFormModeTag = document.getElementById('lgrc-form-mode-tag');
        var lgrcEditorPanel = document.getElementById('lgrc-editor-panel');
        var lgrcEditorBackdrop = document.getElementById('lgrc-editor-backdrop');
        var lgrcCloseEditorButton = document.getElementById('lgrc-close-editor');
        var lgrcAddButton = document.getElementById('lgrc-add-record-button');
        var lgrcClearButton = document.getElementById('lgrc-clear-content');
        var lgrcTabs = document.querySelectorAll('.lgrc-tab');

        function lgrcSetFormMode(isEdit) {
          lgrcFormHeading.textContent = isEdit ? 'Edit MSAC Content' : 'Add MSAC Content';
          lgrcFormModeTag.textContent = isEdit ? 'Edit' : 'New';
        }

        function lgrcCloseEditor() {
          lgrcEditorPanel.classList.remove('is-open');
          lgrcEditorPanel.setAttribute('aria-hidden', 'true');
          lgrcEditorBackdrop.classList.remove('is-open');
        }

        function lgrcOpenEditor() {
          lgrcEditorPanel.classList.add('is-open');
          lgrcEditorPanel.setAttribute('aria-hidden', 'false');
          lgrcEditorBackdrop.classList.add('is-open');
        }

        function lgrcClearForm() {
          lgrcForm.reset();
          document.getElementById('lgrc-content-id').value = '';
          document.getElementById('lgrc-content-active').checked = true;
          lgrcStatusMessage.textContent = '';
          lgrcSetFormMode(false);
          lgrcCloseEditor();
        }

        function lgrcRenderPagination(totalItems) {
          var totalPages = Math.max(1, Math.ceil(totalItems / lgrcPageSize));
          if (lgrcCurrentPage > totalPages) {
            lgrcCurrentPage = totalPages;
          }

          var buttons = [];
          buttons.push('<button type="button" class="page-btn" data-page="prev" ' + (lgrcCurrentPage === 1 ? 'disabled' : '') + ' aria-label="Previous page">&lsaquo;</button>');

          for (var i = 1; i <= totalPages; i += 1) {
            buttons.push('<button type="button" class="page-btn ' + (i === lgrcCurrentPage ? 'is-current' : '') + '" data-page="' + i + '">' + i + '</button>');
          }

          buttons.push('<button type="button" class="page-btn" data-page="next" ' + (lgrcCurrentPage === totalPages ? 'disabled' : '') + ' aria-label="Next page">&rsaquo;</button>');
          lgrcPaginationEl.innerHTML = buttons.join('');
        }

        function lgrcRenderTable() {
          var filtered = lgrcRecords.filter(function (row) {
            var text = (row.title || '') + ' ' + (row.description || '');
            var matchesSearch = !lgrcCurrentSearch || text.toLowerCase().includes(lgrcCurrentSearch.toLowerCase());
            var status = row.is_active ? 'published' : 'draft';
            var matchesFilter = lgrcCurrentFilter === 'all' || status === lgrcCurrentFilter;
            return matchesSearch && matchesFilter;
          });

          var totalItems = filtered.length;
          var totalPages = Math.max(1, Math.ceil(totalItems / lgrcPageSize));
          lgrcCurrentPage = Math.min(lgrcCurrentPage, totalPages);

          var start = (lgrcCurrentPage - 1) * lgrcPageSize;
          var pageItems = filtered.slice(start, start + lgrcPageSize);

          if (!pageItems.length) {
            lgrcRecordsBody.innerHTML = '<tr><td colspan="5"><div class="lgrc-empty-state"><div class="lgrc-empty-state-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 3h8l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M15 3v5h5"/><path d="M9 13h6M9 17h6M9 9h2"/></svg></div><h3>No MSAC content available yet</h3><p>Add your first MSAC content to get started.</p></div></td></tr>';
            lgrcSummaryEl.textContent = '';
            lgrcPaginationEl.innerHTML = '';
            return;
          } else {
            lgrcRecordsBody.innerHTML = pageItems.map(function (row, index) {
              var statusClass = row.is_active ? 'status-badge--published' : 'status-badge--draft';
              var statusText = row.is_active ? 'Published' : 'Draft';
              var orderNum = start + index + 1;

              return '<tr>' +
                '<td style="text-align: center; font-weight: 700; color: #64748b;">' + orderNum + '</td>' +
                '<td>' +
                  '<div class="record-title-wrap">' +
                    '<div class="record-doc-icon" aria-hidden="true">' +
                      '<svg viewBox="0 0 24 24"><path d="M7 3h8l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M15 3v5h5"/><path d="M9 13h6M9 17h6M9 9h2"/></svg>' +
                    '</div>' +
                    '<div class="record-title">' +
                      '<strong>' + escapeHtml(row.title || '') + '</strong>' +
                    '</div>' +
                  '</div>' +
                '</td>' +
                '<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>' +
                '<td><span class="table-date">' + escapeHtml(formatDate(row.updated_at)) + '</span></td>' +
                '<td>' +
                  '<div class="actions-cell">' +
                    '<button type="button" class="icon-button" data-action="edit" data-id="' + row.id + '" aria-label="Edit content" title="Edit">' +
                      '<svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>' +
                    '</button>' +
                    '<button type="button" class="icon-button icon-button--danger" data-action="delete" data-id="' + row.id + '" aria-label="Delete content" title="Delete">' +
                      '<svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>' +
                    '</button>' +
                  '</div>' +
                '</td>' +
              '</tr>';
            }).join('');
          }

          var startNumber = totalItems ? start + 1 : 0;
          var endNumber = Math.min(start + lgrcPageSize, totalItems);
          lgrcSummaryEl.textContent = totalItems ? ('Showing ' + startNumber + ' to ' + endNumber + ' of ' + totalItems + ' entries') : '';

          lgrcRenderPagination(totalItems);
        }

        function lgrcLoadRecords() {
          fetch(lgrcApi)
            .then(function (response) {
              if (!response.ok) {
                throw new Error('Unable to load records.');
              }
              return response.json();
            })
            .then(function (data) {
              lgrcRecords = Array.isArray(data.records) ? data.records : [];
              lgrcCurrentTab = lgrcCurrentTab || (lgrcTabs.length ? lgrcTabs[0].getAttribute('data-lgrc-tab') : '');

              if (lgrcCurrentTab && lgrcRecords.length) {
                var selectedRecord = lgrcRecords.find(function (row) {
                  return String(row.id) === String(lgrcCurrentTab);
                });
                if (selectedRecord) {
                  lgrcPopulateForm(selectedRecord);
                }
              }

              lgrcCurrentPage = 1;
              lgrcRenderTable();
            })
            .catch(function () {
              lgrcRecordsBody.innerHTML = '<tr><td colspan="5"><div style="padding: 18px 0; color: #b91c1c; text-align: center;">Unable to load records.</div></td></tr>';
              lgrcSummaryEl.textContent = 'Showing 0 entries';
              lgrcPaginationEl.innerHTML = '';
            });
        }

        function lgrcPopulateForm(row) {
          document.getElementById('lgrc-content-id').value = row.id || '';
          document.getElementById('lgrc-content-title').value = row.title || '';
          document.getElementById('lgrc-content-description').value = row.description || '';
          document.getElementById('lgrc-content-link').value = row.link_url || '';
          document.getElementById('lgrc-content-active').checked = !!row.is_active;
          lgrcStatusMessage.textContent = 'Editing: ' + row.title;
          lgrcSetFormMode(true);
          lgrcUpdatePreview();
        }

        function lgrcUpdatePreview() {
          var content = document.getElementById('lgrc-content-description').value;
          var previewHtml = document.getElementById('lgrc-preview-html');
          if (content) {
            previewHtml.innerHTML = content;
          } else {
            previewHtml.innerHTML = '<p style="color: #9ca3af; font-style: italic;">No content yet. Start typing in the editor to see preview.</p>';
          }
        }

        // Setup preview tab switching
        var lgrcEditorTabs = document.querySelectorAll('[data-lgrc-editor-tab]');
        var lgrcEditorViews = document.querySelectorAll('[data-lgrc-editor-view]');

        lgrcEditorTabs.forEach(function (tab) {
          tab.addEventListener('click', function (e) {
            e.preventDefault();
            var targetView = tab.getAttribute('data-lgrc-editor-tab');
            
            // Update active tab
            lgrcEditorTabs.forEach(function (t) {
              t.classList.toggle('is-active', t === tab);
            });
            
            // Update visible view
            lgrcEditorViews.forEach(function (view) {
              var isTarget = view.getAttribute('data-lgrc-editor-view') === targetView;
              view.style.display = isTarget ? 'block' : 'none';
            });

            // Update preview when switching to it
            if (targetView === 'preview') {
              lgrcUpdatePreview();
            }
          });
        });

        // Update preview as user types
        document.getElementById('lgrc-content-description').addEventListener('input', lgrcUpdatePreview);

        lgrcRecordsBody.addEventListener('click', function (event) {
          var actionButton = event.target.closest('[data-action]');
          if (!actionButton) {
            return;
          }

          var action = actionButton.getAttribute('data-action');
          var id = actionButton.getAttribute('data-id');
          var row = lgrcRecords.find(function (item) {
            return String(item.id) === String(id);
          });

          if (!row) {
            return;
          }

          if (action === 'edit') {
            lgrcPopulateForm(row);
            lgrcOpenEditor();
            return;
          }

          if (action === 'delete') {
            if (!window.confirm('Delete this content record?')) {
              return;
            }

            var deleteBody = new URLSearchParams({
              action: 'delete',
              section: 'lgrc-gad',
              id: String(id)
            });

            fetch('auth/content_api.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
              body: deleteBody.toString()
            })
              .then(function (response) {
                if (!response.ok) {
                  throw new Error('Delete failed.');
                }
                lgrcStatusMessage.textContent = 'Record deleted successfully.';
                lgrcLoadRecords();
              })
              .catch(function () {
                lgrcStatusMessage.textContent = 'Unable to delete record.';
              });
          }
        });

        lgrcForm.addEventListener('submit', function (event) {
          event.preventDefault();

          var id = document.getElementById('lgrc-content-id').value;
          var payload = new URLSearchParams({
            action: id ? 'update' : 'create',
            section: 'lgrc-gad',
            id: id || '',
            title: document.getElementById('lgrc-content-title').value.trim(),
            description: document.getElementById('lgrc-content-description').value.trim(),
            link_url: document.getElementById('lgrc-content-link').value.trim(),
            is_active: document.getElementById('lgrc-content-active').checked ? '1' : '0'
          });

          if (!payload.get('title')) {
            lgrcStatusMessage.textContent = 'Title is required.';
            return;
          }

          fetch('auth/content_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: payload.toString()
          })
            .then(function (response) {
              return response.json().then(function (data) {
                if (!response.ok || data.error) {
                  throw new Error(data.error || 'Unable to save record.');
                }
                return data;
              });
            })
            .then(function () {
              lgrcStatusMessage.textContent = id ? 'Record updated successfully.' : 'Record created successfully.';
              lgrcClearForm();
              lgrcLoadRecords();
            })
            .catch(function (error) {
              lgrcStatusMessage.textContent = error.message || 'Unable to save record.';
            });
        });

        lgrcClearButton.addEventListener('click', lgrcClearForm);
        lgrcCloseEditorButton.addEventListener('click', lgrcCloseEditor);
        lgrcEditorBackdrop.addEventListener('click', lgrcCloseEditor);
        lgrcAddButton.addEventListener('click', function () {
          lgrcClearForm();
          lgrcOpenEditor();
          document.getElementById('lgrc-content-title').focus();
        });

        lgrcSearchInput.addEventListener('input', function () {
          lgrcCurrentSearch = this.value.trim();
          lgrcCurrentPage = 1;
          lgrcRenderTable();
        });

        lgrcFilterSelect.addEventListener('change', function () {
          lgrcCurrentFilter = this.value;
          lgrcCurrentPage = 1;
          lgrcRenderTable();
        });

        lgrcPaginationEl.addEventListener('click', function (event) {
          var target = event.target.closest('[data-page]');
          if (!target) {
            return;
          }

          var pageValue = target.getAttribute('data-page');
          var filtered = lgrcRecords.filter(function (row) {
            var text = (row.title || '') + ' ' + (row.description || '');
            var matchesSearch = !lgrcCurrentSearch || text.toLowerCase().includes(lgrcCurrentSearch.toLowerCase());
            var status = row.is_active ? 'published' : 'draft';
            var matchesFilter = lgrcCurrentFilter === 'all' || status === lgrcCurrentFilter;
            return matchesSearch && matchesFilter;
          });
          var totalPages = Math.max(1, Math.ceil(filtered.length / lgrcPageSize));

          if (pageValue === 'prev') {
            lgrcCurrentPage = Math.max(1, lgrcCurrentPage - 1);
          } else if (pageValue === 'next') {
            lgrcCurrentPage = Math.min(totalPages, lgrcCurrentPage + 1);
          } else {
            lgrcCurrentPage = Number(pageValue);
          }

          lgrcRenderTable();
        });

        lgrcTabs.forEach(function (tab) {
          tab.addEventListener('click', function () {
            lgrcCurrentTab = tab.getAttribute('data-lgrc-tab');

            lgrcTabs.forEach(function (item) {
              item.classList.toggle('is-active', item === tab);
            });

            var selectedRow = lgrcRecords.find(function (row) {
              return String(row.id) === String(lgrcCurrentTab);
            });

            if (selectedRow) {
              lgrcPopulateForm(selectedRow);
              lgrcOpenEditor();
              var previewTab = document.querySelector('[data-lgrc-editor-tab="preview"]');
              if (previewTab) {
                previewTab.click();
              }
            }

            lgrcCurrentPage = 1;
            lgrcRenderTable();
          });
        });

        lgrcCurrentTab = lgrcTabs.length ? lgrcTabs[0].getAttribute('data-lgrc-tab') : '';
        lgrcLoadRecords();
      }

      if (document.querySelectorAll('.legal-admin-tab').length) {
        document.querySelectorAll('.legal-admin-tab').forEach(function (tab) {
          tab.addEventListener('click', function () {
            var target = this.getAttribute('data-legal-tab');

            document.querySelectorAll('.legal-admin-tab').forEach(function (item) {
              item.classList.toggle('is-active', item === this);
            }, this);

            document.querySelectorAll('.legal-admin-panel').forEach(function (panel) {
              panel.classList.toggle('is-active', panel.id === 'legal-admin-panel-' + target);
            });
          });
        });
      }

      var legalData = <?php echo json_encode($legalTabData); ?>;
      var activeLegalTab = 'dilg';
      var legalEditor = document.getElementById('legal-editor-panel');
      var legalEditorBackdrop = document.getElementById('legal-editor-backdrop');
      var legalEditorForm = document.getElementById('legal-editor-form');

      function closeLegalEditor() {
        if (legalEditor) {
          legalEditor.classList.remove('is-open');
          legalEditor.setAttribute('aria-hidden', 'true');
        }
        if (legalEditorBackdrop) legalEditorBackdrop.classList.remove('is-open');
      }

      document.querySelectorAll('.legal-admin-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
          activeLegalTab = this.getAttribute('data-legal-tab') || 'dilg';
        });
      });

      var caragaYearFilter = document.querySelector('.caraga-year-filter');
      if (caragaYearFilter) {
        caragaYearFilter.addEventListener('change', function () {
          var year = this.value;
          document.querySelectorAll('#legal-admin-panel-caraga tr[data-legal-tab="caraga"]').forEach(function (row) {
            row.hidden = year !== 'all' && row.getAttribute('data-legal-year') !== year;
          });
        });
      }

      var legalAddButton = document.getElementById('legal-add-record-button');
      if (legalAddButton && legalEditorForm) {
        legalAddButton.addEventListener('click', function () {
          legalEditorForm.reset();
          document.getElementById('legal-editor-tab').value = activeLegalTab;
          document.getElementById('legal-editor-index').value = '';
          document.getElementById('legal-region').value = activeLegalTab;
          document.getElementById('legal-editor-heading').textContent = 'Add Legal Opinion';
          document.getElementById('legal-editor-subtitle').textContent = 'Add a legal opinion to the selected region.';
          legalEditor.classList.add('is-open');
          legalEditor.setAttribute('aria-hidden', 'false');
          legalEditorBackdrop.classList.add('is-open');
          document.getElementById('legal-reference').focus();
        });
      }

      document.getElementById('legal-close-editor')?.addEventListener('click', closeLegalEditor);
      document.getElementById('legal-cancel-editor')?.addEventListener('click', closeLegalEditor);

      legalEditorForm?.addEventListener('submit', function (event) {
        event.preventDefault();
        var tabKey = document.getElementById('legal-editor-tab').value || activeLegalTab;
        var tab = legalData[tabKey];
        if (!tab) return;
        var item = {
          ref: document.getElementById('legal-reference').value.trim(),
          title: document.getElementById('legal-title').value.trim(),
          link: document.getElementById('legal-link').value.trim()
        };
        if (!item.ref || !item.title || !item.link) return;
        tab.items.push(item);
        var panel = document.getElementById('legal-admin-panel-' + tabKey);
        var body = panel ? panel.querySelector('.legal-table tbody') : null;
        if (body) {
          var row = document.createElement('tr');
          row.setAttribute('data-legal-tab', tabKey);
          row.setAttribute('data-legal-index', String(tab.items.length - 1));
          row.innerHTML = '<td><span class="legal-ref-badge"></span></td><td><span class="legal-title"></span></td><td><span class="legal-region-label"></span></td><td><a class="legal-public-link" target="_blank" rel="noopener noreferrer">Public link</a></td><td><div class="legal-actions"><button type="button" data-legal-action="view" data-legal-tab="' + tabKey + '" data-legal-index="' + (tab.items.length - 1) + '" aria-label="View legal opinion" title="View"><svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg></button><button type="button" data-legal-action="edit" data-legal-tab="' + tabKey + '" data-legal-index="' + (tab.items.length - 1) + '" aria-label="Edit legal opinion" title="Edit"><svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg></button><button type="button" class="delete" data-legal-action="delete" data-legal-tab="' + tabKey + '" data-legal-index="' + (tab.items.length - 1) + '" aria-label="Delete legal opinion" title="Delete"><svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg></button></div></td>';
          row.querySelector('.legal-ref-badge').textContent = item.ref;
          row.querySelector('.legal-title').textContent = item.title;
          row.querySelector('.legal-region-label').textContent = tab.label;
          row.querySelector('.legal-public-link').href = item.link;
          body.appendChild(row);
        }
        closeLegalEditor();
      });
      legalEditorBackdrop?.addEventListener('click', closeLegalEditor);

      document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-legal-action]');
        if (!button) {
          return;
        }

        var tabKey = button.getAttribute('data-legal-tab');
        var index = Number(button.getAttribute('data-legal-index'));

        if (button.getAttribute('data-legal-action') === 'view') {
          var viewItem = legalData[tabKey] && legalData[tabKey].items[index];
          if (viewItem && viewItem.link) window.open(viewItem.link, '_blank', 'noopener,noreferrer');
          return;
        }

        if (button.getAttribute('data-legal-action') === 'edit') {
          var editItem = legalData[tabKey] && legalData[tabKey].items[index];
          if (!editItem) return;
          document.getElementById('legal-editor-tab').value = tabKey;
          document.getElementById('legal-editor-index').value = String(index);
          document.getElementById('legal-region').value = tabKey;
          document.getElementById('legal-reference').value = editItem.ref || '';
          document.getElementById('legal-title').value = editItem.title || '';
          document.getElementById('legal-link').value = editItem.link || '';
          document.getElementById('legal-editor-heading').textContent = 'Edit Legal Opinion';
          document.getElementById('legal-editor-subtitle').textContent = 'Update the legal opinion details.';
          legalEditor.classList.add('is-open');
          legalEditor.setAttribute('aria-hidden', 'false');
          legalEditorBackdrop.classList.add('is-open');
          return;
        }

        if (!window.confirm('Delete this legal opinion item?')) {
          return;
        }

        if (legalData[tabKey] && legalData[tabKey].items[index]) {
          legalData[tabKey].items.splice(index, 1);
          var row = document.querySelector('.legal-table tr[data-legal-tab="' + tabKey + '"][data-legal-index="' + index + '"]');
          if (row) {
            row.remove();
          }
        }
      });

      if (form) {
        clearForm();
        loadRecords();
      }
    })();
  </script>
</body>
</html>

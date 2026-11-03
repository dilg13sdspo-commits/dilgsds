<?php
require_once __DIR__ . '/auth/middleware.php';
require_role('ADMIN');
$pdo = db_connect();

function count_html_nodes($filename, $query) {
  if (!class_exists('DOMDocument') || !is_file($filename)) return 0;
  libxml_use_internal_errors(true);
  $document = new DOMDocument();
  $document->loadHTMLFile($filename);
  $xpath = new DOMXPath($document);
  return $xpath->query($query)->length;
}

function count_html_class($filename, $className) {
  return count_html_nodes($filename, "//*[contains(concat(' ', normalize-space(@class), ' '), ' " . $className . " ')]");
}

function count_files_by_type($directory, $extensions) {
  if (!is_dir($directory)) return 0;
  $count = 0;
  $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
  foreach ($iterator as $file) {
    if ($file->isFile() && in_array(strtolower($file->getExtension()), $extensions, true)) $count++;
  }
  return $count;
}

function percentage($part, $total) {
  return $total > 0 ? round(($part / $total) * 100) : 0;
}

function count_active_json_records($filename) {
  if (!is_file($filename)) return null;
  $records = json_decode(file_get_contents($filename), true);
  if (!is_array($records)) return null;
  return count(array_filter($records, function ($record) { return !empty($record['is_active']); }));
}

// Metrics derived from the database and the current website files.
$totalUsers = (int) $pdo->query('SELECT COUNT(*) AS c FROM users')->fetchColumn();
$roleCounts = ['ADMIN' => 0, 'PO USER' => 0, 'FO USER' => 0];
foreach ($pdo->query('SELECT role, COUNT(*) AS c FROM users GROUP BY role') as $row) $roleCounts[$row['role']] = (int) $row['c'];
$adminPercent = percentage($roleCounts['ADMIN'], $totalUsers);
$poPercent = percentage($roleCounts['PO USER'], $totalUsers);
$foPercent = percentage($roleCounts['FO USER'], $totalUsers);
$totalNews = count_active_json_records(__DIR__ . '/data/news.json');
if ($totalNews === null) $totalNews = count_html_class(__DIR__ . '/news.html', 'video-card') + count_html_class(__DIR__ . '/news.html', 'katon-card');
$totalIssuances = count_html_nodes(__DIR__ . '/issuances.html', "//*[contains(concat(' ', normalize-space(@class), ' '), ' jmc-table ')]//tbody/tr");
$totalPrograms = count_html_class(__DIR__ . '/programs-and-services.html', 'program-link');
$activeUsers = 'N/A';

$reportCounts = ['PENDING' => 0, 'SUBMITTED' => 0, 'OVERDUE' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) AS c FROM reports GROUP BY status') as $row) $reportCounts[$row['status']] = (int) $row['c'];
$pendingCount = $reportCounts['PENDING'] + $reportCounts['OVERDUE'];
$pendingActions = [];
$reportQuery = $pdo->query("SELECT r.title, r.status, u.username FROM reports r LEFT JOIN users u ON u.id = r.user_id WHERE r.status IN ('PENDING', 'OVERDUE') ORDER BY r.created_at DESC LIMIT 10");
foreach ($reportQuery as $report) {
  $pendingActions[] = [
    'item' => $report['title'], 'type' => 'Report', 'submittedBy' => $report['username'] ?: 'Unknown user',
    'status' => ucfirst(strtolower($report['status'])), 'statusColor' => $report['status'] === 'OVERDUE' ? 'danger' : ($report['status'] === 'SUBMITTED' ? 'success' : 'warning')
  ];
}
$recentActivity = $pdo->query("SELECT a.action, a.created_at, u.username FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 6")->fetchAll();

// News CRUD moved to Home Management. Dashboard no longer handles What's New server-side; use admin-home.php or auth/news_api.php as the canonical management surface.
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>ADMIN Dashboard — DILG SDS</title>
  <link rel="stylesheet" href="styles.css?v=20260814-admin-ref-01">
  <style>
    .sidebar-settings-link {
      display: flex;
      align-items: center;
      gap: 10px;
      width: 100%;
      min-height: 36px;
      padding: 7px 10px;
      margin: 0;
      box-sizing: border-box;
      border: 0;
      background: transparent;
      color: rgba(233, 241, 255, 0.9);
      text-decoration: none;
      font: inherit;
      font-size: 0.94rem;
      font-weight: 600;
      border-radius: 12px;
      outline: none;
      cursor: pointer;
      transition: background-color 180ms ease, transform 180ms ease, color 180ms ease;
      text-align: left;
      white-space: nowrap;
      appearance: none;
      -webkit-appearance: none;
    }
    .sidebar-settings-link:hover,
    .sidebar-settings-link:focus-visible {
      background: #1f5fcf;
      color: #ffffff;
      transform: translateX(4px);
    }
    .sidebar-settings-link .nav-icon {
      width: 20px;
      height: 20px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      opacity: 0.96;
      flex-shrink: 0;
    }
    .sidebar-settings-link .nav-icon svg {
      width: 18px;
      height: 18px;
      fill: currentColor;
    }
    .settings-modal {
      position: fixed;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1200;
      padding: 20px;
    }

    .settings-modal.is-open {
      display: flex;
    }

    .settings-modal__backdrop {
      position: absolute;
      inset: 0;
      background: rgba(10, 20, 36, 0.42);
      backdrop-filter: blur(2px);
    }

    .settings-modal__panel {
      position: relative;
      z-index: 1;
      width: min(980px, calc(100vw - 32px));
      max-height: min(92vh, 980px);
      display: flex;
      flex-direction: column;
      background: #f3f6fb;
      color: #1e2d4d;
      border: 1px solid rgba(18, 38, 77, 0.12);
      border-radius: 18px;
      box-shadow: 0 28px 70px rgba(13, 25, 61, 0.18);
      padding: 0;
      overflow: hidden;
    }

    .settings-modal__header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      padding: 18px 20px 14px;
      background: rgba(255, 255, 255, 0.38);
      border-bottom: 1px solid rgba(18, 38, 77, 0.1);
    }

    .settings-modal__heading {
      display: flex;
      align-items: center;
      gap: 14px;
      min-width: 0;
    }

    .settings-modal__icon {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(31, 95, 207, 0.08);
      color: #1f5fcf;
      border: 1px solid rgba(31, 95, 207, 0.12);
      flex-shrink: 0;
    }

    .settings-modal__icon svg {
      width: 22px;
      height: 22px;
      stroke: currentColor;
      fill: none;
      stroke-width: 1.9;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .settings-modal__heading h2 {
      margin: 0;
      font-size: clamp(1.9rem, 2vw, 2.6rem);
      line-height: 1.15;
      letter-spacing: -0.04em;
      color: #0f2a6b;
      font-weight: 800;
    }

    .settings-modal__heading p {
      margin: 4px 0 0;
      color: #5d6d86;
      font-size: 0.94rem;
      line-height: 1.5;
    }

    .settings-modal__close {
      border: 1px solid rgba(18, 38, 77, 0.12);
      background: rgba(255, 255, 255, 0.7);
      color: #173a77;
      width: 40px;
      height: 40px;
      border-radius: 12px;
      font-size: 1.7rem;
      line-height: 1;
      cursor: pointer;
      transition: background-color 0.15s ease, border-color 0.15s ease;
      flex-shrink: 0;
    }

    .settings-modal__close:hover,
    .settings-modal__close:focus-visible {
      background: #ffffff;
      border-color: rgba(18, 38, 77, 0.2);
      outline: none;
    }

    .settings-modal__body {
      flex: 1 1 auto;
      min-height: 0;
      overflow-y: auto;
      padding: 16px 20px 0;
      background: #f3f6fb;
    }

    .settings-form {
      display: grid;
      gap: 0;
      background: #f3f6fb;
      margin: 0;
    }

    .settings-section {
      border-top: 1px solid rgba(18, 38, 77, 0.08);
      padding-top: 18px;
      margin-top: 10px;
    }

    .settings-section:first-of-type {
      border-top: none;
      margin-top: 0;
      padding-top: 4px;
    }

    .settings-section__header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 0 0 16px;
      color: #1c4bb4;
      font-size: 0.78rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .settings-section__header .section-icon {
      width: 18px;
      height: 18px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #1f5fcf;
    }

    .settings-section__header svg {
      width: 18px;
      height: 18px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .settings-form__row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
    }

    .settings-field {
      display: grid;
      gap: 7px;
      margin-bottom: 18px;
      min-width: 0;
    }

    .settings-field--compact {
      margin-bottom: 0;
    }

    .settings-field label {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 0.92rem;
      font-weight: 700;
      color: #1d325c;
    }

    .settings-field .required {
      color: #d64a4a;
      font-weight: 800;
    }

    .settings-field input,
    .settings-field select {
      width: 100%;
      min-height: 46px;
      border: 1px solid rgba(18, 38, 77, 0.14);
      border-radius: 10px;
      padding: 0 12px;
      background: rgba(255, 255, 255, 0.9);
      color: #1d325c;
      font: inherit;
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
      transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
    }

    .settings-field input:hover,
    .settings-field select:hover {
      border-color: rgba(18, 38, 77, 0.22);
    }

    .settings-field input:focus,
    .settings-field select:focus {
      outline: none;
      border-color: rgba(31, 95, 207, 0.7);
      box-shadow: 0 0 0 3px rgba(31, 95, 207, 0.12);
      background: #ffffff;
    }

    .settings-field input[aria-invalid="true"],
    .settings-field select[aria-invalid="true"] {
      border-color: rgba(214, 74, 74, 0.75);
      box-shadow: 0 0 0 3px rgba(214, 74, 74, 0.08);
    }

    .settings-field small,
    .settings-field__message {
      color: #667a96;
      font-size: 0.74rem;
      line-height: 1.5;
    }

    .settings-field__message {
      min-height: 1.2em;
      display: block;
      margin-top: 2px;
      color: #667a96;
    }

    .settings-field__message.is-error {
      color: #b63d3d;
      font-weight: 600;
    }

    .settings-field--inline {
      display: block;
      margin-bottom: 0;
    }

    .settings-option-group {
      display: inline-flex;
      width: 100%;
      gap: 0;
      border: 1px solid rgba(18, 38, 77, 0.12);
      border-radius: 12px;
      background: rgba(255,255,255,0.72);
      padding: 4px;
      margin-top: 4px;
      transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
    }

    .settings-option-group.is-warning {
      border-color: rgba(202, 137, 13, 0.35);
      background: rgba(255, 249, 234, 0.72);
      box-shadow: inset 0 0 0 1px rgba(202, 137, 13, 0.05);
    }

    .settings-option {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      flex: 1 1 0;
      min-height: 42px;
      border-radius: 9px;
      font-weight: 700;
      color: #2b3c5d;
      cursor: pointer;
      user-select: none;
      transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
    }

    .settings-option input {
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }

    .settings-option input:checked + span {
      background: rgba(31, 95, 207, 0.12);
      color: #0f2a6b;
      box-shadow: inset 0 0 0 1px rgba(31, 95, 207, 0.2);
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 9px;
    }

    .settings-option span {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 100%;
      border-radius: 9px;
      transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
    }

    .settings-system__row {
      display: grid;
      grid-template-columns: minmax(0, 1.2fr) minmax(0, 1.4fr);
      gap: 18px;
      align-items: stretch;
    }

    .settings-info-box {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      min-height: 100%;
      background: rgba(31, 95, 207, 0.08);
      border: 1px solid rgba(31, 95, 207, 0.12);
      border-radius: 12px;
      padding: 14px 16px;
      color: #1f5fcf;
      margin-bottom: 18px;
      transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
    }

    .settings-info-box.is-warning {
      background: rgba(202, 137, 13, 0.08);
      border-color: rgba(202, 137, 13, 0.2);
      box-shadow: inset 0 0 0 1px rgba(202, 137, 13, 0.08);
      color: #8b5e00;
    }

    .settings-info-box__icon {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: rgba(31, 95, 207, 0.12);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      flex-shrink: 0;
    }

    .settings-info-box p {
      margin: 0;
      color: #274b86;
      font-size: 0.88rem;
      line-height: 1.5;
    }

    .settings-modal__footer {
      position: sticky;
      bottom: 0;
      z-index: 2;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      padding: 14px 20px 18px;
      border-top: 1px solid rgba(18, 38, 77, 0.08);
      background: rgba(243, 246, 251, 0.96);
      backdrop-filter: blur(10px);
      margin-top: 10px;
      flex-wrap: wrap;
    }
    .settings-status {
      min-height: 22px;
      margin: 10px 0 0;
      font-size: 0.8rem;
      font-weight: 700;
      color: #1c5d3d;
    }

    .settings-status.is-error {
      color: #b63d3d;
    }

    .settings-status.is-saving {
      color: #234b9e;
    }

    .settings-modal__meta {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #5d6d86;
      font-size: 0.76rem;
      font-weight: 600;
    }

    .settings-modal__meta svg {
      width: 14px;
      height: 14px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .settings-modal__footer-actions {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-left: auto;
    }

    .settings-btn {
      border: 1px solid transparent;
      border-radius: 10px;
      min-height: 42px;
      padding: 0 16px;
      font-weight: 800;
      font-size: 0.92rem;
      cursor: pointer;
      transition: background-color 0.15s ease, border-color 0.15s ease, opacity 0.15s ease, transform 0.15s ease;
    }

    .settings-btn:hover,
    .settings-btn:focus-visible {
      outline: none;
      transform: translateY(-1px);
    }

    .settings-btn--secondary {
      background: #ffffff;
      border-color: rgba(18, 38, 77, 0.12);
      color: #1d325c;
    }

    .settings-btn--primary {
      background: linear-gradient(180deg, #1f5fcf 0%, #184db5 100%);
      color: #ffffff;
      box-shadow: 0 10px 18px rgba(31, 95, 207, 0.14);
    }

    .settings-btn--primary:disabled {
      opacity: 0.55;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    @media (max-width: 720px) {
      .settings-modal__header {
        padding: 16px 16px 12px;
      }

      .settings-modal__heading {
        align-items: flex-start;
      }

      .settings-modal__heading h2 {
        font-size: 1.8rem;
      }

      .settings-form {
        padding: 12px 16px 14px;
      }

      .settings-form__row,
      .settings-system__row {
        grid-template-columns: 1fr;
      }

      .settings-modal__footer {
        flex-direction: column;
        align-items: stretch;
      }

      .settings-modal__footer-actions {
        width: 100%;
        margin-left: 0;
        justify-content: flex-end;
      }
    }

    /* Keep system settings compact and consistent with the reports dialog. */
    .settings-modal {
      padding: 16px;
    }

    .settings-modal__panel {
      width: min(520px, calc(100vw - 32px));
      max-height: calc(100vh - 32px);
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 20px 50px rgba(13, 25, 61, 0.2);
    }

    .settings-modal__header {
      padding: 18px 24px;
      background: #ffffff;
      border-bottom: 1px solid #e5ecf5;
    }

    .settings-modal__heading {
      gap: 0;
    }

    .settings-modal__icon,
    .settings-modal__heading p {
      display: none;
    }

    .settings-modal__heading h2 {
      font-size: 1.25rem;
      letter-spacing: 0;
    }

    .settings-modal__close {
      width: 32px;
      height: 32px;
      border: 0;
      background: transparent;
      font-size: 1.4rem;
    }

    .settings-modal__body {
      padding: 0 24px;
      background: #ffffff;
    }

    .settings-form {
      background: #ffffff;
    }

    .settings-section,
    .settings-section:first-of-type {
      border-top: 0;
      padding: 18px 0 0;
      margin: 0;
    }

    .settings-section__header,
    .settings-field small,
    .settings-field__message,
    .settings-info-box {
      display: none;
    }

    .settings-field {
      gap: 6px;
      margin-bottom: 16px;
    }

    .settings-field label {
      font-size: 0.88rem;
    }

    .settings-field input,
    .settings-field select {
      min-height: 40px;
      border-radius: 8px;
      font-size: 0.9rem;
    }

    .settings-form__row,
    .settings-system__row {
      gap: 12px;
    }

    .settings-modal__actions {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
      margin-top: 8px;
      padding-top: 16px;
      border-top: 1px solid #e5ecf5;
    }

    .settings-modal__footer {
      padding: 16px 24px;
      background: #ffffff;
      border-top: 1px solid #e5ecf5;
    }

    .settings-modal__meta {
      display: none;
    }

    .settings-modal__footer-actions {
      width: 100%;
      gap: 12px;
      justify-content: flex-end;
    }

    .settings-btn {
      min-height: 40px;
      border-radius: 8px;
      font-size: 0.9rem;
    }
  </style>
</head>
<body class="admin-dashboard-ref-body">


  <div class="admin-portal-shell">
    <?php include __DIR__ . '/admin-sidebar.php'; ?>


    <main class="admin-main">

      <header class="dashboard-top-bar" aria-label="Admin dashboard header">
        <div class="welcome-panel">
          <p class="welcome-eyebrow">DILG Administration</p>
          <h1 class="welcome-title">Welcome back, Administrator</h1>
        </div>

        <div class="dashboard-top-actions-right">
          <div class="search-bar">
            <svg class="search-icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27a6.471 6.471 0 0 0 1.48-5.34c0-3.59-2.91-6.5-6.5-6.5s-6.5 2.91-6.5 6.5 2.91 6.5 6.5 6.5c1.35 0 2.61-.38 3.68-1.04l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            <input id="dashboard-search" type="search" placeholder="Search DILG records..." aria-label="Search DILG records">
          </div>
          <span id="dashboard-search-status" class="dashboard-search-status" role="status" aria-live="polite"></span>
          <div class="header-notifications">
            <button class="notification-btn" type="button" aria-label="Notifications">
              <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.96 5.36 6.3 7.92 6.3 11v5l-2 2v1h15.7v-1l-2-2z"/></svg>
              <span class="notification-badge"><?= $pendingCount ?></span>
            </button>
          </div>
          <div class="header-user">
            <div class="user-avatar">A</div>
            <div class="user-info">
              <div class="user-name">Administrator</div>
              <div class="user-role">Super Admin</div>
            </div>
          </div>
        </div>
      </header>

      <section class="stat-grid" aria-label="Key system metrics">
        <article class="metric-card" id="user-management">
          <div class="metric-card__top">
            <div class="metric-card__icon metric-card__icon--blue">
              <svg viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-2.67 0-8 1.34-8 4v1h16v-1c0-2.66-5.33-4-8-4zM8 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-1.34 0-4 1.17-4 2.5V16h8v-.5C12 14.17 9.34 13 8 13z"/></svg>
            </div>
            <div class="metric-card__trend metric-card__trend--up">
              <span>Live</span>
              <span>Database</span>
            </div>
          </div>
          <div class="metric-card__label">Total Users</div>
          <div class="metric-card__value"><?= htmlspecialchars($totalUsers) ?></div>
          <div class="metric-card__breakdown"><?= $roleCounts['ADMIN'] ?> Admins • <?= $roleCounts['PO USER'] ?> PO • <?= $roleCounts['FO USER'] ?> FO</div>
        </article>

        <article class="metric-card">
          <div class="metric-card__top">
            <div class="metric-card__icon metric-card__icon--purple">
              <svg viewBox="0 0 24 24"><path d="M7 3h10a2 2 0 0 1 2 2v14l-4-3-4 3-4-3-4 3V5a2 2 0 0 1 2-2zm2 4h6v2H9V7zm0 4h8v2H9v-2z"/></svg>
            </div>
            <div class="metric-card__trend metric-card__trend--up">
              <span>Live</span>
              <span>Website files</span>
            </div>
          </div>
          <div class="metric-card__label">Published News</div>
          <div class="metric-card__value"><?= htmlspecialchars($totalNews) ?></div>
          <div class="metric-card__breakdown"><?= $totalNews ?> published website items</div>
        </article>

        <article class="metric-card">
          <div class="metric-card__top">
            <div class="metric-card__icon metric-card__icon--green">
              <svg viewBox="0 0 24 24"><path d="M5 4h10l4 4v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm9 1.5V9h3.5L14 5.5zM7 12h10v2H7v-2zm0 4h7v2H7v-2z"/></svg>
            </div>
            <div class="metric-card__trend metric-card__trend--up">
              <span>Live</span>
              <span>Website files</span>
            </div>
          </div>
          <div class="metric-card__label">Issuances</div>
          <div class="metric-card__value"><?= htmlspecialchars($totalIssuances) ?></div>
          <div class="metric-card__breakdown"><?= $totalIssuances ?> entries in issuances.html</div>
        </article>

        <article class="metric-card">
          <div class="metric-card__top">
            <div class="metric-card__icon metric-card__icon--orange">
              <svg viewBox="0 0 24 24"><path d="M4 6h16v12H4V6zm2 2v8h12V8H6zm2 2h8v2H8v-2zm0 4h5v2H8v-2z"/></svg>
            </div>
            <div class="metric-card__trend metric-card__trend--up">
              <span>Live</span>
              <span>Website files</span>
            </div>
          </div>
          <div class="metric-card__label">Programs & Services</div>
          <div class="metric-card__value"><?= htmlspecialchars($totalPrograms) ?></div>
          <div class="metric-card__breakdown"><?= $totalPrograms ?> program links</div>
        </article>

      </section>

      <div class="dashboard-grid-2col">
        <article class="panel panel--chart" id="reports-analytics">
          <div class="panel-header">
            <h3>Website Content Inventory</h3>
            <div class="chart-time-selector"><span class="time-btn active">Current files</span></div>
          </div>
          <div class="chart-empty-state">Historical activity is unavailable because website content is stored in static files.</div>
          <div class="chart-summary">
            <div class="summary-item">
              <strong><?= $totalNews ?></strong>
              <span>Total News</span>
              <small>Current website count</small>
            </div>
            <div class="summary-item">
              <strong><?= $totalIssuances ?></strong>
              <span>Total Issuances</span>
              <small>Current website count</small>
            </div>
            <div class="summary-item">
              <strong><?= $totalPrograms ?></strong>
              <span>Total Programs</span>
              <small>Current website count</small>
            </div>
          </div>
        </article>

        <article class="panel panel--activity">
          <div class="panel-header">
            <h3>Recent System Activity</h3>
          </div>
          <ul class="activity-list">
            <?php if (!$recentActivity): ?>
            <li><span class="activity-text"><strong>No recorded system activity</strong></span></li>
            <?php else: ?>
            <?php foreach ($recentActivity as $activity): ?>
            <li>
              <span class="status-pill status-pill--blue">LOG</span>
              <span class="activity-text">
                <strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $activity['action']))) ?></strong><br>
                <small>By: <?= htmlspecialchars($activity['username'] ?: 'System') ?></small>
              </span>
              <small class="activity-time"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($activity['created_at']))) ?></small>
            </li>
            <?php endforeach; ?>
            <?php endif; ?>
          </ul>
          <div class="activity-footer">
            <a href="#" class="view-all-link">View all activities →</a>
          </div>
        </article>
      </div>

      <div class="dashboard-grid-2col">
        <article class="panel panel--donut">
          <h3>Users by Role</h3>
          <div class="donut-wrap">
            <div class="donut-chart" aria-label="Users by role chart">
              <div class="donut-center">
                <strong><?= $totalUsers ?></strong>
                <span>Total Users</span>
              </div>
            </div>
            <ul class="role-legend">
              <li><span class="legend-dot legend-dot--blue"></span> <strong>Admins</strong> <span class="legend-value"><?= $roleCounts['ADMIN'] ?> (<?= $adminPercent ?>%)</span></li>
              <li><span class="legend-dot legend-dot--green"></span> <strong>PO Users</strong> <span class="legend-value"><?= $roleCounts['PO USER'] ?> (<?= $poPercent ?>%)</span></li>
              <li><span class="legend-dot legend-dot--purple"></span> <strong>FO Users</strong> <span class="legend-value"><?= $roleCounts['FO USER'] ?> (<?= $foPercent ?>%)</span></li>
            </ul>
          </div>
          <p class="role-note">User distribution across all roles in the system</p>
        </article>

        <article class="panel panel--pending">
          <div class="panel-header">
            <h3>Pending Actions</h3>
            <a href="auth/reports_api.php?action=list" class="view-all-link">View All Reports →</a>
          </div>
          <div class="pending-table-wrap">
            <table class="pending-table">
              <thead>
                <tr>
                  <th>ITEM</th>
                  <th>TYPE</th>
                  <th>SUBMITTED BY</th>
                  <th>STATUS</th>
                  <th>ACTION</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$pendingActions): ?>
                <tr><td colspan="5">No pending reports.</td></tr>
                <?php endif; ?>
                <?php foreach ($pendingActions as $action): ?>
                <tr>
                  <td class="item-cell"><strong><?= htmlspecialchars($action['item']) ?></strong></td>
                  <td><span class="badge badge-type"><?= htmlspecialchars($action['type']) ?></span></td>
                  <td><?= htmlspecialchars($action['submittedBy']) ?></td>
                  <td><span class="status-badge status-badge--<?= htmlspecialchars($action['statusColor']) ?>"><?= htmlspecialchars($action['status']) ?></span></td>
                  <td>
                    <button class="action-link" type="button">Review</button>
                    <button class="action-link secondary" type="button">Edit</button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </article>
      </div>

    </main>
  </div>

  <div class="settings-modal" id="settings-modal" aria-hidden="true">
    <div class="settings-modal__backdrop" data-close-settings></div>
    <div class="settings-modal__panel" role="dialog" aria-modal="true" aria-labelledby="settings-title" tabindex="-1">
      <div class="settings-modal__header">
        <h2 id="settings-title">System Settings</h2>
        <button type="button" class="settings-modal__close" aria-label="Close settings" data-close-settings>×</button>
      </div>

      <form class="settings-form" id="system-settings-form" novalidate>
        <label class="settings-field">Website Title <span class="required">*</span><input id="websiteTitle" name="websiteTitle" type="text" value="DILG SDS" required aria-invalid="false" /></label>
        <div class="settings-form__row">
          <label class="settings-field">Contact Email <span class="required">*</span><input id="contactEmail" name="contactEmail" type="email" value="info@dilg.gov.ph" required aria-invalid="false" /></label>
          <label class="settings-field">Default Timezone <span class="required">*</span><select id="timezone" name="timezone" required aria-invalid="false"><option value="Asia/Manila" selected>Asia/Manila (GMT+08:00)</option><option value="UTC">UTC</option><option value="America/New_York">America/New_York</option><option value="Europe/London">Europe/London</option></select></label>
        </div>
        <div class="settings-form__row">
          <label class="settings-field">Maintenance Mode<select id="maintenanceModeGroup" name="maintenanceMode"><option value="off" selected>Off</option><option value="on">On</option></select></label>
          <label class="settings-field">Default Publishing <span class="required">*</span><select id="publishState" name="publishState" required aria-invalid="false"><option value="published" selected>Published</option><option value="draft">Draft</option><option value="archived">Archived</option></select></label>
        </div>
        <div class="settings-modal__actions">
          <button type="button" class="settings-btn settings-btn--secondary" data-close-settings>Cancel</button>
          <button type="submit" class="settings-btn settings-btn--primary" disabled>Save Changes</button>
        </div>
        <p class="settings-status" id="settings-status" role="status" aria-live="polite"></p>
      </form>
    </div>
  </div>

  <script>
  (() => {
    const dashboardSearch = document.getElementById('dashboard-search');
    const dashboardSearchStatus = document.getElementById('dashboard-search-status');
    const searchableSections = Array.from(document.querySelectorAll('.metric-card, .panel'));

    function applyDashboardSearch() {
      const query = dashboardSearch.value.trim().toLowerCase();
      const matchingSections = searchableSections.filter((section) => {
        const matches = !query || section.textContent.toLowerCase().includes(query);
        section.hidden = !matches;
        return matches;
      });

      if (dashboardSearchStatus) {
        dashboardSearchStatus.textContent = query
          ? `${matchingSections.length} matching section${matchingSections.length === 1 ? '' : 's'}`
          : '';
      }
    }

    if (dashboardSearch) {
      dashboardSearch.addEventListener('input', applyDashboardSearch);
      dashboardSearch.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') applyDashboardSearch();
      });
    }

    const settingsModal = document.getElementById('settings-modal');
    const settingsForm = document.getElementById('system-settings-form');
    const settingsStatus = document.getElementById('settings-status');
    const saveButton = settingsForm.querySelector('.settings-btn--primary');
    const closeButtons = document.querySelectorAll('[data-close-settings]');
    const maintenanceModeGroup = document.getElementById('maintenanceModeGroup');
    const maintenanceInfoBox = document.getElementById('maintenanceInfoBox');
    const focusableSelector = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';

    const defaultSettings = {
      websiteTitle: 'DILG SDS',
      contactEmail: 'info@dilg.gov.ph',
      timezone: 'Asia/Manila',
      publishState: 'published',
      maintenanceMode: 'off'
    };

    const appState = {
      saved: { ...defaultSettings },
      current: { ...defaultSettings },
      dirty: false,
      isSubmitting: false,
      lastFocused: null
    };

    function parseSavedSettings() {
      try {
        const saved = JSON.parse(localStorage.getItem('dilgSystemSettings') || '{}');
        return { ...defaultSettings, ...saved };
      } catch (error) {
        return { ...defaultSettings };
      }
    }

    function getCurrentValues() {
      const formData = new FormData(settingsForm);
      return {
        websiteTitle: String(formData.get('websiteTitle') || '').trim(),
        contactEmail: String(formData.get('contactEmail') || '').trim(),
        timezone: String(formData.get('timezone') || 'Asia/Manila'),
        publishState: String(formData.get('publishState') || 'published'),
        maintenanceMode: String(formData.get('maintenanceMode') || 'off')
      };
    }

    function setStatus(message, type = 'success') {
      if (!settingsStatus) return;
      settingsStatus.textContent = message || '';
      settingsStatus.classList.remove('is-error', 'is-saving');
      if (type === 'error') settingsStatus.classList.add('is-error');
      if (type === 'saving') settingsStatus.classList.add('is-saving');
    }

    function syncMaintenanceModeState() {
      const enabled = settingsForm.querySelector('[name="maintenanceMode"]')?.value === 'on';
      if (maintenanceModeGroup) {
        maintenanceModeGroup.classList.toggle('is-warning', enabled);
      }
      if (maintenanceInfoBox) {
        maintenanceInfoBox.classList.toggle('is-warning', enabled);
        const description = maintenanceInfoBox.querySelector('p');
        if (description) {
          description.textContent = enabled
            ? 'Maintenance mode is active. Visitors will see the maintenance page until this setting is switched off.'
            : 'When enabled, visitors will see the maintenance page while this mode is active.';
        }
      }
    }

    function validate(values) {
      const errors = {};

      if (!values.websiteTitle) {
        errors.websiteTitle = 'Website title is required.';
      }

      if (!values.contactEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.contactEmail)) {
        errors.contactEmail = 'A valid email address is required.';
      }

      if (!values.timezone) {
        errors.timezone = 'Timezone is required.';
      }

      if (!values.publishState) {
        errors.publishState = 'Publishing status is required.';
      }

      return errors;
    }

    function markFieldValidity(inputName, message) {
      const input = settingsForm.querySelector(`[name="${inputName}"]`);
      const messageNode = document.getElementById(`${inputName}Error`);
      if (!input) return;
      input.setAttribute('aria-invalid', message ? 'true' : 'false');
      if (messageNode) {
        messageNode.textContent = message || '';
        messageNode.classList.toggle('is-error', Boolean(message));
      }
    }

    function syncValidationState() {
      const values = getCurrentValues();
      const errors = validate(values);

      ['websiteTitle', 'contactEmail', 'timezone', 'publishState'].forEach((fieldName) => {
        const hasError = Boolean(errors[fieldName]);
        markFieldValidity(fieldName, hasError ? errors[fieldName] : '');
      });

      appState.current = values;
      appState.dirty = JSON.stringify(appState.current) !== JSON.stringify(appState.saved);
      saveButton.disabled = appState.isSubmitting || !appState.dirty || Object.keys(errors).length > 0;
      return errors;
    }

    function applySavedSettings() {
      const savedValues = parseSavedSettings();
      appState.saved = { ...savedValues };
      appState.current = { ...savedValues };

      settingsForm.websiteTitle.value = savedValues.websiteTitle;
      settingsForm.contactEmail.value = savedValues.contactEmail;
      settingsForm.timezone.value = savedValues.timezone;
      settingsForm.publishState.value = savedValues.publishState;
      const maintenanceMode = settingsForm.querySelector('[name="maintenanceMode"]');
      if (maintenanceMode) maintenanceMode.value = savedValues.maintenanceMode;

      syncValidationState();
    }

    function closeSettingsModal(force = false) {
      if (!settingsModal) return;

      const shouldWarn = appState.dirty && !force && !appState.isSubmitting;
      if (shouldWarn && !window.confirm('You have unsaved changes. Discard them?')) {
        return;
      }

      settingsModal.classList.remove('is-open');
      settingsModal.setAttribute('aria-hidden', 'true');
      setStatus('');
      appState.isSubmitting = false;
      saveButton.disabled = true;
      if (appState.lastFocused && typeof appState.lastFocused.focus === 'function') {
        appState.lastFocused.focus();
      }
    }

    function openSettingsModal() {
      if (!settingsModal) return;

      appState.lastFocused = document.activeElement;
      settingsModal.classList.add('is-open');
      settingsModal.setAttribute('aria-hidden', 'false');
      setStatus('');
      requestAnimationFrame(() => {
        const focusTarget = settingsForm.querySelector('input:not([disabled]), select:not([disabled]), button:not([disabled])');
        focusTarget && focusTarget.focus();
      });
    }

    closeButtons.forEach((button) => {
      button.addEventListener('click', () => closeSettingsModal());
    });

    settingsModal.addEventListener('click', (event) => {
      if (event.target === settingsModal || event.target.classList.contains('settings-modal__backdrop')) {
        closeSettingsModal();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (!settingsModal || !settingsModal.classList.contains('is-open')) return;
      if (event.key === 'Escape') {
        event.preventDefault();
        closeSettingsModal();
      }
      if (event.key === 'Tab' && settingsModal.classList.contains('is-open')) {
        const focusable = Array.from(settingsModal.querySelectorAll(focusableSelector)).filter((el) => !el.disabled && el.offsetParent !== null);
        if (!focusable.length) {
          event.preventDefault();
          return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    });

    settingsForm.addEventListener('input', () => {
      const errors = syncValidationState();
      if (Object.keys(errors).length > 0) {
        setStatus('Please fix the highlighted required fields before saving.', 'error');
      } else {
        setStatus('');
      }
    });

    settingsForm.addEventListener('change', () => {
      const errors = syncValidationState();
      if (Object.keys(errors).length > 0) {
        setStatus('Please fix the highlighted required fields before saving.', 'error');
      } else {
        setStatus('');
      }
      syncMaintenanceModeState();
    });

    settingsForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const values = getCurrentValues();
      const errors = validate(values);

      if (Object.keys(errors).length > 0) {
        Object.keys(errors).forEach((key) => markFieldValidity(key, 'true'));
        setStatus('Please fill in all required details before saving.', 'error');
        return;
      }

      appState.isSubmitting = true;
      saveButton.disabled = true;
      setStatus('Saving changes…', 'saving');

      try {
        await new Promise((resolve) => setTimeout(resolve, 500));
        localStorage.setItem('dilgSystemSettings', JSON.stringify(values));
        appState.saved = { ...values };
        appState.current = { ...values };
        appState.dirty = false;
        setStatus('Settings saved successfully.', 'success');
        setTimeout(() => closeSettingsModal(true), 700);
      } catch (error) {
        setStatus('We were unable to save the settings. Please try again.', 'error');
      } finally {
        appState.isSubmitting = false;
        syncValidationState();
      }
    });

    document.querySelectorAll('[data-open-settings]').forEach((button) => {
      button.addEventListener('click', openSettingsModal);
    });

    document.getElementById('logout-btn').addEventListener('click', async function (event) {
      event.preventDefault();
      try {
        await fetch('auth/logout.php', { method: 'POST' });
      } finally {
        window.location.href = 'index.html';
      }
    });

    applySavedSettings();
    syncMaintenanceModeState();
    closeSettingsModal(true);
  })();
  </script>
</body>
</html>

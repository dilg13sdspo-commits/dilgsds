<?php
require_once __DIR__ . '/../shared/auth/middleware.php';
require_once __DIR__ . '/../shared/auth/po_icons.php';
require_role('PO USER');

$pdo = db_connect();
$userName = $_SESSION['username'] ?? 'Provincial User';
$userId = (int)($_SESSION['user_id'] ?? 0);
$profilePhoto = get_user_profile_photo($pdo, $userId);

$officeProfile = [
    'name' => 'Provincial Office of Surigao del Sur',
    'location' => 'Capitiol Hills, Telaje, Tandag, Surigao del Sur, Philippines',
    'phone' => '+63-86-251-8888',
    'phone_href' => 'tel:+63862518888',
    'email' => 'dilg13sdspo@gmail.com',
    'email_href' => 'mailto:po@dilg.gov.ph',
    'map_href' => 'https://www.google.com/maps/search/?api=1&query=Butuan+Surigao+del+Sur+Philippines',
    'hours' => 'Monday - Friday: 8:00 AM - 5:00 PM',
    'services' => [
        'Local Government Units Support',
        'Permits & Licensing',
        'Community Programs',
    ],
];

function office_status_label($status) {
    $status = strtoupper((string) $status);
    $map = [
        'DRAFT' => 'Draft',
        'PENDING' => 'Pending',
        'SUBMITTED' => 'Pending',
        'FORWARDED' => 'In Progress',
        'RESOLVED' => 'Completed',
        'RETURNED' => 'Returned',
        'OVERDUE' => 'Overdue',
        'COMPLETED' => 'Completed',
    ];
    return $map[$status] ?? ucfirst(strtolower(str_replace('_', ' ', $status)));
}

function office_status_class($status) {
    $status = strtoupper((string) $status);
    $map = [
        'DRAFT' => 'draft',
        'PENDING' => 'pending',
        'SUBMITTED' => 'pending',
        'FORWARDED' => 'in-progress',
        'RESOLVED' => 'completed',
        'RETURNED' => 'returned',
        'OVERDUE' => 'overdue',
        'COMPLETED' => 'completed',
    ];
    return $map[$status] ?? 'pending';
}

$notificationCount = 0;
try {
    $notificationCount = (int) $pdo->prepare('SELECT COUNT(*) FROM audit_logs WHERE user_id = :uid AND action NOT IN (\'login\', \'logout\', \'unauthorized_access\') AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)')->execute([':uid' => $userId]);
} catch (Exception $e) {
    $notificationCount = 0;
}

$notificationCount = 0;
try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM audit_logs WHERE user_id = :uid AND action NOT IN (\'login\', \'logout\', \'unauthorized_access\') AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
    $stmt->execute([':uid' => $userId]);
    $notificationCount = (int) $stmt->fetchColumn();
} catch (Exception $e) {
    $notificationCount = 0;
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Office - DILG PO Dashboard</title>
  <style>
    :root {
      --po-bg: #f4f7fb;
      --po-surface: #ffffff;
      --po-sidebar: #5c2d17;
      --po-sidebar-deep: #3d1f11;
      --po-accent: #d88a2d;
      --po-heading: #17263c;
      --po-text: #233147;
      --po-muted: #68768d;
      --po-border: rgba(27, 40, 60, 0.08);
      --po-shadow: 0 12px 28px rgba(31, 42, 61, 0.08);
      --po-blue: #2d7ff9;
      --po-green: #20b883;
      --po-red: #f05050;
      --po-gold: #f5a623;
      --po-purple: #8a52f5;
      --po-orange-soft: #fff4e5;
      --po-amber-soft: #fff8e1;
      --po-gray-soft: #eef3f8;
      --po-gray-border: #dfe7ef;
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      font-family: "Segoe UI", Arial, sans-serif;
      background: var(--po-bg);
      color: var(--po-text);
    }
    a { text-decoration: none; }
    button, input { font: inherit; }

    .po-shell,
    .shell {
      display: flex;
      min-height: 100vh;
      background: linear-gradient(180deg, #f6f8fc 0%, #f3f6fb 100%);
    }

    .po-sidebar,
    .sidebar {
      width: 220px;
      background: linear-gradient(180deg, #0b3d91 0%, #1456a0 100%);
      color: #fff;
      display: flex;
      flex-direction: column;
      padding: 18px 14px;
      box-shadow: inset -1px 0 0 rgba(255,255,255,0.08);
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 8px 8px 18px;
      border-bottom: 1px solid rgba(255,255,255,0.13);
    }

    .brand-mark {
      width: 52px;
      height: 52px;
      border-radius: 16px;
      background: rgba(255,255,255,0.12);
      display: grid;
      place-items: center;
      font-weight: 800;
      color: #f9d39c;
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
    }

    .brand-copy { line-height: 1.15; min-width: 0; }
    .brand-copy strong {
      display: block;
      font-size: 1rem;
      letter-spacing: 0.08em;
    }
    .brand-copy span {
      display: block;
      font-size: 0.72rem;
      opacity: 0.8;
      margin-top: 4px;
    }

    .nav {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-top: 16px;
      padding: 0 4px;
    }

    .nav-item {
      width: 100%;
      border: none;
      background: transparent;
      color: rgba(255,255,255,0.9);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      text-align: left;
      border-radius: 10px;
      padding: 12px 10px;
      cursor: pointer;
      transition: all 0.2s ease;
      font-weight: 600;
    }
    .nav-item:hover, .nav-item.active {
      background: rgba(255,255,255,0.12);
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.06);
    }
    .nav-item.active {
      background: rgba(255,255,255,0.18);
      box-shadow: inset 3px 0 0 rgba(255,255,255,0.8);
    }

    .nav-main { display: inline-flex; align-items: center; gap: 10px; min-width: 0; }
    .nav-icon { width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
    .nav-label { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nav-badge {
      min-width: 22px;
      height: 22px;
      border-radius: 999px;
      background: var(--po-accent);
      color: #fff;
      display: grid;
      place-items: center;
      font-size: 0.72rem;
      font-weight: 800;
      padding: 0 6px;
    }

    .sidebar-footer {
      margin-top: auto;
      padding-top: 14px;
      border-top: 1px solid rgba(255,255,255,0.12);
    }

    .footer-pill {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 8px 0;
      color: rgba(255,255,255,0.88);
      font-size: 0.82rem;
      font-weight: 600;
    }

    .seal {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      background: rgba(255,255,255,0.12);
      color: #f9d39c;
      font-size: 0.8rem;
      font-weight: 800;
    }

    .po-main {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-width: 0;
    }

    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      background: rgba(255,255,255,0.88);
      backdrop-filter: blur(6px);
      border-bottom: 1px solid var(--po-border);
      padding: 16px 22px;
    }

    .topbar-title {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }

    .title-mark {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      background: var(--po-accent);
      color: #fff;
      font-weight: 800;
      display: grid;
      place-items: center;
      box-shadow: var(--po-shadow);
    }

    .title-copy strong {
      display: block;
      font-size: 0.92rem;
      line-height: 1.2;
      color: var(--po-heading);
    }

    .title-copy span {
      display: block;
      color: var(--po-muted);
      font-size: 0.72rem;
      margin-top: 2px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .topbar-tools {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-left: auto;
    }

    .search {
      display: flex;
      align-items: center;
      gap: 8px;
      min-width: 280px;
      background: #f5f7fa;
      border: 1px solid var(--po-border);
      border-radius: 12px;
      padding: 10px 12px;
      color: var(--po-muted);
    }

    .search input {
      border: 0;
      background: transparent;
      flex: 1;
      color: var(--po-text);
      font-size: 0.92rem;
      outline: none;
    }

    .notif {
      position: relative;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #fff;
      border: 1px solid var(--po-border);
      color: var(--po-text);
      box-shadow: 0 4px 10px rgba(31, 42, 61, 0.04);
    }

    .notif .counter {
      position: absolute;
      top: -5px;
      right: -4px;
      min-width: 18px;
      height: 18px;
      border-radius: 50%;
      background: var(--po-red);
      color: #fff;
      display: grid;
      place-items: center;
      font-size: 0.65rem;
      font-weight: 700;
      padding: 0 4px;
    }

    .profile-wrap {
      position: relative;
    }

    .profile {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #fff;
      border: 1px solid var(--po-border);
      border-radius: 18px;
      padding: 7px 10px;
      color: var(--po-text);
      cursor: pointer;
    }

    .avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: linear-gradient(135deg, #e69d34, #ca5b17);
      color: #fff;
      display: grid;
      place-items: center;
      font-weight: 800;
      font-size: 0.85rem;
    }

    .profile-meta {
      display: flex;
      flex-direction: column;
      line-height: 1.2;
      text-align: left;
    }

    .profile-meta strong {
      font-size: 0.82rem;
    }

    .profile-meta span {
      font-size: 0.68rem;
      color: var(--po-muted);
    }

    .page-body {
      flex: 1;
      padding: 24px 26px 32px;
    }

    .page-header {
      margin-bottom: 18px;
    }

    .page-title-wrap {
      display: flex;
      align-items: center;
      gap: 16px;
      background: rgba(255,255,255,0.75);
      border: 1px solid var(--po-border);
      border-radius: 16px;
      padding: 18px 20px;
      box-shadow: 0 10px 20px rgba(31, 42, 61, 0.04);
    }

    .page-icon {
      width: 50px;
      height: 50px;
      border-radius: 14px;
      background: linear-gradient(135deg, #edf6ff, #eef7ff);
      color: var(--po-blue);
      display: grid;
      place-items: center;
      font-size: 1.6rem;
      border: 1px solid rgba(45, 127, 249, 0.12);
    }

    .page-header h1 {
      margin: 0;
      font-size: clamp(2rem, 3vw, 2.3rem);
      color: var(--po-heading);
      letter-spacing: -0.02em;
    }

    .breadcrumb {
      margin-top: 6px;
      font-size: 0.82rem;
      color: var(--po-muted);
    }

    .breadcrumb strong {
      color: var(--po-text);
      font-weight: 600;
    }

    .page-card {
      background: var(--po-surface);
      border: 1px solid var(--po-border);
      border-radius: 16px;
      box-shadow: 0 10px 22px rgba(31, 42, 61, 0.04);
      overflow: hidden;
    }

    .card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 20px 22px 16px;
      border-bottom: 2px solid rgba(45, 127, 249, 0.25);
    }

    .card-header h2 {
      margin: 0;
      font-size: clamp(1.4rem, 2vw, 1.8rem);
      color: var(--po-heading);
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0;
      padding: 18px 22px 8px;
    }

    .info-item {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      padding: 18px 12px 18px 8px;
      border-bottom: 1px solid rgba(31, 42, 61, 0.06);
    }

    .info-icon {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      display: grid;
      place-items: center;
      font-size: 1.2rem;
      flex-shrink: 0;
      background: #f5f8ff;
      color: var(--po-blue);
      border: 1px solid rgba(45, 127, 249, 0.16);
    }

    .info-item:nth-child(2n) .info-icon { background: #f9f1f8; color: #bf4ea1; }
    .info-item:nth-child(3n) .info-icon { background: #fff7e8; color: #d28d1a; }
    .info-item:nth-child(4n) .info-icon { background: #f1f8f5; color: var(--po-green); }
    .info-item:nth-child(5n) .info-icon { background: #f2f1ff; color: var(--po-purple); }
    .info-item:nth-child(6n) .info-icon { background: #edf8ff; color: #1b8fd8; }

    .info-copy {
      min-width: 0;
      display: flex;
      flex-direction: column;
      gap: 4px;
      color: var(--po-text);
    }

    .info-label {
      margin: 0;
      color: var(--po-muted);
      font-size: 0.82rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .text-value {
      color: var(--po-heading);
      font-weight: 600;
      font-size: 1.05rem;
      line-height: 1.5;
      word-break: break-word;
    }

    .text-value a {
      color: var(--po-text);
    }

    .services-list {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 4px;
    }

    .service-tag {
      display: inline-flex;
      align-items: center;
      padding: 6px 10px;
      border-radius: 999px;
      background: #f3f9ff;
      color: #2a5db8;
      border: 1px solid rgba(45, 127, 249, 0.15);
      font-size: 0.8rem;
      font-weight: 600;
    }

    .table-card {
      margin-top: 26px;
    }

    .section-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 20px 22px 14px;
      border-bottom: 1px solid var(--po-border);
    }

    .section-head h2 {
      margin: 0;
      color: var(--po-heading);
      font-size: clamp(1.35rem, 2vw, 1.7rem);
    }

    .view-all {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 9px 14px;
      border-radius: 10px;
      font-weight: 700;
      background: #edf5ff;
      color: var(--po-blue);
      border: 1px solid rgba(45, 127, 249, 0.15);
    }

    .table-wrap {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 640px;
      background: #fff;
    }

    th, td {
      padding: 15px 18px;
      text-align: left;
      border-bottom: 1px solid var(--po-border);
      vertical-align: middle;
    }

    th {
      color: var(--po-muted);
      text-transform: uppercase;
      font-size: 0.74rem;
      letter-spacing: 0.08em;
      background: rgba(245, 247, 251, 0.8);
      font-weight: 700;
    }

    td {
      color: var(--po-text);
      font-size: 0.96rem;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 7px 10px;
      border-radius: 999px;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.02em;
      text-transform: uppercase;
    }

    .status-badge.pending { background: #fff4d9; color: #b96f00; }
    .status-badge.in-progress { background: #e8f0ff; color: #2b66d8; }
    .status-badge.completed { background: #eafaf1; color: #177847; }
    .status-badge.returned { background: #ffe6e8; color: #b53049; }
    .status-badge.overdue { background: #ffe8d8; color: #ba4d00; }
    .status-badge.draft { background: #eef2f7; color: #54657d; }

    .table-action {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      border-radius: 10px;
      border: 1px solid var(--po-gray-border);
      background: #fff;
      color: var(--po-text);
      cursor: pointer;
      transition: all 0.2s ease;
      font-size: 1rem;
    }

    .table-action:hover {
      background: #f3f7ff;
      border-color: rgba(45, 127, 249, 0.2);
      color: var(--po-blue);
    }

    .empty-state {
      padding: 32px 20px;
      text-align: center;
      color: var(--po-muted);
      font-size: 0.95rem;
    }

    .error-box {
      padding: 18px 22px;
      color: #8c2d1b;
      background: #fff0ef;
      border: 1px solid rgba(240, 80, 80, 0.25);
      border-radius: 12px;
      margin-top: 12px;
      font-weight: 600;
    }

    .loading-shell {
      opacity: 1;
      transition: opacity 0.25s ease;
    }

    .loading-shell.is-loaded {
      opacity: 1;
    }

    @media (max-width: 980px) {
      .po-shell,
      .shell { flex-direction: column; }
      .po-sidebar,
      .sidebar { width: 100%; }
      .nav { flex-direction: row; flex-wrap: wrap; }
      .nav-item { width: calc(50% - 6px); }
      .topbar { flex-wrap: wrap; }
      .topbar-tools { width: 100%; justify-content: space-between; }
      .search { min-width: 180px; flex: 1; }
    }

    @media (max-width: 720px) {
      .page-body { padding: 18px 16px 24px; }
      .topbar { padding: 12px 16px; }
      .topbar-title { width: 100%; }
      .topbar-tools { width: 100%; justify-content: space-between; }
      .search { min-width: 0; }
      .info-grid { grid-template-columns: 1fr; }
      .info-item { padding-inline: 6px; }
      .page-title-wrap { padding: 15px 16px; }
      .card-header, .section-head { padding: 16px; }
      .nav-item { width: 100%; }
    }
  </style>
</head>
<body>
  <div class="shell">
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-mark">PO</div>
        <div class="brand-copy">
          <strong>DILG</strong>
          <span>Provincial Office User</span>
        </div>
      </div>

      <div style="display:flex;align-items:center;gap:10px;margin:14px 4px 0;padding:10px 8px;border:1px solid rgba(255,255,255,.12);border-radius:12px;background:rgba(255,255,255,.08);"><div style="width:36px;height:36px;display:grid;place-items:center;flex:0 0 36px;overflow:hidden;border:1px solid rgba(255,255,255,.28);border-radius:50%;background:rgba(255,255,255,.14);font-size:.82rem;font-weight:800;"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="PO User profile photo" style="width:100%;height:100%;object-fit:cover;display:block;"><?php else: ?><?= htmlspecialchars(strtoupper(substr($userName, 0, 2))) ?><?php endif; ?></div><div style="min-width:0;"><span style="display:flex;align-items:center;gap:6px;font-size:.72rem;color:rgba(255,255,255,.88);"><span style="width:7px;height:7px;border-radius:50%;background:#34d399;"></span>Online</span><span style="display:block;margin-top:3px;color:#fff;font-size:.78rem;font-weight:700;"><?= htmlspecialchars($userName) ?></span></div></div>

      <nav class="nav" aria-label="Primary navigation">
        <button class="nav-item" type="button" onclick="window.location.href='dashboard.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('dashboard') ?></span><span class="nav-label">Dashboard</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='inquiries.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('submissions') ?></span><span class="nav-label">My Submissions</span></span></button>
        <button class="nav-item active" type="button" onclick="window.location.href='office.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('office') ?></span><span class="nav-label">My Office</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='calendar.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('calendar') ?></span><span class="nav-label">Calendar</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='reports.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('reports') ?></span><span class="nav-label">Reports</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='notifications.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('notifications') ?></span><span class="nav-label">Notifications</span></span><span class="nav-badge"><?= htmlspecialchars((string) $notificationCount) ?></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='settings.php'"><span class="nav-main"><span class="nav-icon">&#9881;</span><span class="nav-label">Settings</span></span></button>
        <button class="nav-item" id="logout-btn" type="button"><span class="nav-main"><span class="nav-icon">&#10145;</span><span class="nav-label">Log Out</span></span></button>
      </nav>

      <div class="sidebar-footer">
        <div class="footer-pill">
          <div class="seal">D</div>
          <div>Provincial Services</div>
        </div>
      </div>
    </aside>

    <div class="main">
      <header class="topbar">
        <div class="topbar-title">
          <div class="title-mark">D</div>
          <div class="title-copy">
            <strong>DEPARTMENT OF THE INTERIOR AND LOCAL GOVERNMENT</strong>
            <span>Province of Surigao del Sur &bull; Provincial Office Dashboard</span>
          </div>
        </div>

        <div class="topbar-tools">
          <label class="search" aria-label="Search dashboard">
            <span><?= po_icon('search') ?></span>
            <input id="officeSearch" type="search" placeholder="Search dashboard..." />
          </label>

          <div class="notif" aria-label="Notifications" onclick="window.location.href='notifications.php'" style="cursor:pointer;">
            <?= po_icon('notifications') ?>
            <span class="counter"><?= htmlspecialchars((string) $notificationCount) ?></span>
          </div>

          <div class="profile-wrap">
            <button class="profile" type="button" aria-expanded="false">
              <span class="avatar"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="PO User profile photo" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;"><?php else: ?><?= htmlspecialchars(strtoupper(substr($userName, 0, 2))) ?><?php endif; ?></span>
              <span class="profile-meta">
                <strong><?= htmlspecialchars($userName) ?></strong>
                <span>Provincial Office</span>
              </span>
            </button>
          </div>
        </div>
      </header>

      <main class="page-body loading-shell">
        <div class="page-header">
          <div class="page-title-wrap">
            <div class="page-icon"><?= po_icon('office') ?></div>
            <div>
              <h1>My Office</h1>
              <div class="breadcrumb"><strong>Dashboard</strong> <span>&rsaquo;</span> My Office</div>
            </div>
          </div>
        </div>

        <section class="page-card" aria-label="Provincial Office Information">
          <div class="card-header">
            <h2>Provincial Office Information</h2>
          </div>

          <div class="info-grid">
            <div class="info-item">
              <div class="info-icon"><?= po_icon('office') ?></div>
              <div class="info-copy">
                <p class="info-label">Office Name</p>
                <div class="text-value"><?= htmlspecialchars($officeProfile['name']) ?></div>
              </div>
            </div>

            <div class="info-item">
              <div class="info-icon"><?= po_icon('location') ?></div>
              <div class="info-copy">
                <p class="info-label">Location</p>
                <div class="text-value"><a href="<?= htmlspecialchars($officeProfile['map_href']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($officeProfile['location']) ?></a></div>
              </div>
            </div>

            <div class="info-item">
              <div class="info-icon"><?= po_icon('phone') ?></div>
              <div class="info-copy">
                <p class="info-label">Contact Number</p>
                <div class="text-value"><a href="<?= htmlspecialchars($officeProfile['phone_href']) ?>"><?= htmlspecialchars($officeProfile['phone']) ?></a></div>
              </div>
            </div>

            <div class="info-item">
              <div class="info-icon">&#9432;</div>
              <div class="info-copy">
                <p class="info-label">Email</p>
                <div class="text-value"><a href="<?= htmlspecialchars($officeProfile['email_href']) ?>"><?= htmlspecialchars($officeProfile['email']) ?></a></div>
              </div>
            </div>

            <div class="info-item">
              <div class="info-icon"><?= po_icon('clock') ?></div>
              <div class="info-copy">
                <p class="info-label">Office Hours</p>
                <div class="text-value"><?= htmlspecialchars($officeProfile['hours']) ?></div>
              </div>
            </div>

            <div class="info-item">
              <div class="info-icon"><?= po_icon('notifications') ?></div>
              <div class="info-copy">
                <p class="info-label">Services</p>
                <div class="services-list">
                  <?php foreach ($officeProfile['services'] as $service): ?>
                    <span class="service-tag"><a href="../../programs-and-services.html" style="color: inherit;"><?= htmlspecialchars($service) ?></a></span>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </section>

      </main>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const loader = document.querySelector('.loading-shell');
      if (loader) loader.classList.add('is-loaded');

      const logoutBtn = document.getElementById('logout-btn');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
          fetch('../shared/auth/logout.php', { method: 'POST' }).finally(function () {
            window.location.href = '../../index.html';
          });
        });
      }
    });
  </script>
</body>
</html>

<?php
require_once __DIR__ . '/../shared/auth/middleware.php';
require_once __DIR__ . '/../shared/auth/po_icons.php';
require_role('PO USER');

$pdo = db_connect();
$userName = $_SESSION['username'] ?? 'Provincial User';
$userId = (int)($_SESSION['user_id'] ?? 0);
$profilePhoto = get_user_profile_photo($pdo, $userId);

$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
$stmt->execute([':uid' => $userId]);
$notificationCount = (int)$stmt->fetchColumn();

$tab = $_GET['tab'] ?? 'all';
$categoryFilter = $_GET['category'] ?? 'all';
$rangeFilter = $_GET['range'] ?? 'all';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Messages - DILG PO Dashboard</title>
  <style>
    :root {
      --po-sidebar: #5c2d17;
      --po-sidebar-deep: #3d1f11;
      --po-bg: #f5f7fb;
      --po-surface: #ffffff;
      --po-heading: #1f2d3d;
      --po-text: #243447;
      --po-muted: #69798a;
      --po-border: #e6edf4;
      --po-accent: #2d7ff9;
      --po-accent-soft: #edf4ff;
      --po-warning: #f5a623;
      --po-warning-soft: #fff5e8;
      --po-green: #2ab36d;
      --po-green-soft: #ebfaf4;
      --po-red: #e86161;
      --po-red-soft: #fff0f2;
      --po-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
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
    button, input, select { font: inherit; }

    .po-shell { display: flex; min-height: 100vh; }

    .po-sidebar {
      width: 220px;
      background: linear-gradient(180deg, #0b3d91, #1456a0);
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
      border-bottom: 1px solid rgba(255,255,255,0.12);
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
    }
    .brand-copy { line-height: 1.1; }
    .brand-copy strong { display: block; font-size: 1rem; letter-spacing: .08em; }
    .brand-copy span { display: block; font-size: .72rem; opacity: .8; margin-top: 4px; }

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
      color: rgba(255,255,255,.9);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      text-align: left;
      border-radius: 10px;
      padding: 12px 10px;
      cursor: pointer;
      transition: all .2s ease;
      font-weight: 600;
    }
    .nav-item:hover, .nav-item.active {
      background: rgba(255,255,255,.12);
      box-shadow: inset 0 0 0 1px rgba(255,255,255,.06);
    }
    .nav-item.active {
      background: rgba(255,255,255,.18);
      box-shadow: inset 3px 0 0 rgba(255,255,255,.8);
    }
    .nav-main { display: inline-flex; align-items: center; gap: 10px; min-width: 0; }
    .nav-icon { width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; }
    .nav-label { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nav-badge {
      min-width: 22px;
      height: 22px;
      border-radius: 999px;
      background: var(--po-warning);
      color: #fff;
      display: grid;
      place-items: center;
      font-size: .72rem;
      font-weight: 800;
      padding: 0 6px;
    }
    .sidebar-footer {
      margin-top: auto;
      padding-top: 14px;
      border-top: 1px solid rgba(255,255,255,.12);
    }
    .footer-pill {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 8px 0;
      font-weight: 700;
      opacity: .95;
    }
    .seal {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      background: rgba(255,255,255,.12);
      display: grid;
      place-items: center;
      font-size: .75rem;
      font-weight: 800;
    }

    .po-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    .topbar {
      background: rgba(255,255,255,.86);
      border-bottom: 1px solid var(--po-border);
      backdrop-filter: blur(6px);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 16px 22px;
    }
    .topbar-title { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .title-mark {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      background: var(--po-accent);
      color: #fff;
      display: grid;
      place-items: center;
      font-weight: 800;
    }
    .title-copy strong {
      display: block;
      font-size: .9rem;
      color: var(--po-heading);
    }
    .title-copy span {
      display: block;
      font-size: .72rem;
      color: var(--po-muted);
      margin-top: 2px;
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
      min-width: 270px;
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
      outline: none;
      color: var(--po-text);
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
      color: var(--po-heading);
      cursor: pointer;
    }
    .notif .counter {
      position: absolute;
      top: -4px;
      right: -4px;
      min-width: 18px;
      height: 18px;
      border-radius: 999px;
      background: #f05050;
      color: #fff;
      display: grid;
      place-items: center;
      padding: 0 4px;
      font-size: .65rem;
      font-weight: 700;
    }
    .profile {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #fff;
      border: 1px solid var(--po-border);
      border-radius: 18px;
      padding: 7px 10px;
      cursor: pointer;
      color: var(--po-text);
    }
    .avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, #f0b33f, #d17025);
      color: #fff;
      font-weight: 800;
      font-size: .82rem;
    }
    .profile-meta { display: flex; flex-direction: column; line-height: 1.2; text-align: left; }
    .profile-meta strong { font-size: .82rem; }
    .profile-meta span { font-size: .68rem; color: var(--po-muted); }

    .page-inner { padding: 22px; }
    .page-header {
      margin-bottom: 18px;
    }
    .header-card {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 18px 20px;
      background: rgba(255,255,255,.8);
      border: 1px solid var(--po-border);
      border-radius: 16px;
      box-shadow: 0 8px 18px rgba(31,42,61,.04);
    }
    .page-icon {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      background: #edf4ff;
      color: var(--po-accent);
      display: grid;
      place-items: center;
      font-size: 1.7rem;
    }
    .page-header h1 {
      margin: 0;
      font-size: clamp(1.8rem,2.5vw,2.5rem);
      color: var(--po-heading);
    }
    .breadcrumb {
      margin-top: 6px;
      font-size: .82rem;
      color: var(--po-muted);
    }
    .page-description {
      margin: 12px 0 0;
      color: var(--po-muted);
      font-size: .95rem;
    }

    .messages-shell {
      background: var(--po-surface);
      border: 1px solid var(--po-border);
      border-radius: 16px;
      box-shadow: var(--po-shadow);
      overflow: hidden;
    }
    .toolbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      border-bottom: 1px solid var(--po-border);
      background: #fff;
    }
    .tab-list {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      flex: 1;
      min-width: 0;
    }
    .tab {
      border: 1px solid var(--po-border);
      background: #f8fafc;
      color: var(--po-muted);
      border-radius: 10px;
      padding: 9px 12px;
      cursor: pointer;
      font-weight: 700;
      white-space: nowrap;
    }
    .tab.active {
      background: var(--po-accent-soft);
      border-color: rgba(45,127,249,.12);
      color: var(--po-accent);
    }
    .toolbar-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .filter-btn, .mark-btn, .action-btn, .soft-btn {
      border-radius: 10px;
      padding: 9px 12px;
      font-weight: 700;
      cursor: pointer;
    }
    .filter-btn {
      border: 1px solid var(--po-border);
      background: #fff;
      color: var(--po-text);
    }
    .mark-btn {
      border: 1px solid var(--po-accent);
      background: var(--po-accent);
      color: #fff;
    }

    .message-layout { display: grid; grid-template-columns: 1.3fr .9fr; min-height: 720px; }
    .message-list-panel { border-right: 1px solid var(--po-border); background: #fff; }
    .messages-toolbar { display: flex; align-items: center; gap: 12px; padding: 14px 16px 10px; }
    .search-wrap {
      flex: 1;
      display: flex;
      align-items: center;
      gap: 8px;
      background: #f8fafc;
      border: 1px solid var(--po-border);
      border-radius: 12px;
      padding: 10px 12px;
      color: var(--po-muted);
    }
    .search-wrap input {
      border: 0;
      outline: none;
      background: transparent;
      flex: 1;
      color: var(--po-text);
    }
    .message-list { background: #fff; }
    .message-row {
      display: grid;
      grid-template-columns: 10px 42px 1fr auto;
      gap: 12px;
      align-items: flex-start;
      padding: 16px 16px;
      border-bottom: 1px solid var(--po-border);
      cursor: pointer;
      transition: background .2s ease;
    }
    .message-row:hover { background: #fafcff; }
    .message-row.selected { background: #f5f9ff; }
    .message-row.unread { background: #f9fbff; }
    .message-row.unread .message-title { font-weight: 800; }
    .message-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: var(--po-accent);
      margin-top: 10px;
    }
    .message-row.read .message-dot { background: transparent; }
    .message-icon {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      display: grid;
      place-items: center;
      background: #f3f7fb;
      font-size: 1.2rem;
    }
    .message-body { min-width: 0; }
    .message-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 4px;
    }
    .message-title {
      margin: 0;
      color: var(--po-heading);
      font-size: 1.02rem;
      line-height: 1.3;
    }
    .status-chip {
      border-radius: 999px;
      padding: 4px 8px;
      font-size: .66rem;
      font-weight: 700;
      background: #eef2f8;
      color: var(--po-muted);
    }
    .status-chip.unread {
      background: #edf4ff;
      color: var(--po-accent);
    }
    .message-preview {
      margin: 0;
      color: var(--po-muted);
      font-size: .9rem;
      line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .message-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-top: 8px;
      color: var(--po-muted);
      font-size: .76rem;
    }
    .meta-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 4px 8px;
      border-radius: 999px;
      color: #fff;
      font-weight: 700;
      font-size: .68rem;
    }
    .pagination-wrap {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 12px 16px 14px;
      border-top: 1px solid var(--po-border);
      background: #fff;
      color: var(--po-muted);
      font-size: .82rem;
    }
    .pager {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .page-btn {
      min-width: 32px;
      height: 32px;
      border: 1px solid var(--po-border);
      background: #fff;
      border-radius: 8px;
      color: var(--po-text);
      cursor: pointer;
    }
    .page-btn.active {
      background: var(--po-accent);
      color: #fff;
      border-color: var(--po-accent);
    }
    .page-btn:disabled { opacity: .45; cursor: not-allowed; }

    .detail-panel {
      background: #fff;
      padding: 20px;
      display: flex;
      flex-direction: column;
    }
    .detail-empty {
      display: flex;
      align-items: center;
      justify-content: center;
      flex: 1;
      color: var(--po-muted);
      text-align: center;
      border: 1px dashed var(--po-border);
      border-radius: 14px;
      background: #fafcff;
    }
    .detail-card {
      flex: 1;
      border: 1px solid var(--po-border);
      border-radius: 16px;
      background: #fff;
      padding: 18px;
      display: flex;
      flex-direction: column;
      min-height: 100%;
    }
    .detail-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 18px;
    }
    .detail-icon {
      width: 58px;
      height: 58px;
      border-radius: 18px;
      display: grid;
      place-items: center;
      font-size: 1.8rem;
      background: #edf4ff;
      color: var(--po-accent);
      flex-shrink: 0;
    }
    .detail-title { flex: 1; }
    .detail-title h2 {
      margin: 0 0 6px;
      font-size: 1.15rem;
      color: var(--po-heading);
    }
    .detail-date {
      color: var(--po-muted);
      font-size: .8rem;
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    .detail-badge {
      border-radius: 999px;
      padding: 4px 8px;
      font-size: .7rem;
      font-weight: 700;
      color: #fff;
      background: var(--po-accent);
    }
    .detail-content {
      margin-top: 20px;
      color: var(--po-text);
      line-height: 1.75;
      flex: 1;
    }
    .detail-meta-box {
      background: #f8fafc;
      border: 1px solid var(--po-border);
      border-radius: 12px;
      padding: 12px 14px;
      margin-top: 18px;
      color: var(--po-muted);
      font-size: .82rem;
      display: grid;
      gap: 8px;
    }
    .detail-actions {
      display: flex;
      gap: 10px;
      margin-top: 18px;
    }
    .primary-action, .danger-action, .secondary-action {
      border-radius: 10px;
      padding: 10px 16px;
      font-weight: 700;
      cursor: pointer;
      border: 1px solid transparent;
    }
    .primary-action {
      background: var(--po-accent);
      color: #fff;
      border-color: var(--po-accent);
    }
    .danger-action {
      background: #fff;
      color: var(--po-red);
      border-color: rgba(232,97,97,.25);
    }
    .secondary-action {
      background: #fff;
      color: var(--po-text);
      border-color: var(--po-border);
    }

    .modal {
      position: fixed;
      inset: 0;
      background: rgba(17, 24, 39, 0.3);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 30;
      padding: 20px;
    }
    .modal.open { display: flex; }
    .modal-card {
      width: min(520px, 100%);
      background: #fff;
      border-radius: 18px;
      border: 1px solid var(--po-border);
      box-shadow: var(--po-shadow);
      padding: 18px;
    }
    .modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 14px;
    }
    .modal-header h3 { margin: 0; color: var(--po-heading); }
    .close-modal {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      border: 1px solid var(--po-border);
      background: #fff;
      cursor: pointer;
    }
    .filter-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }
    .field {
      display: flex;
      flex-direction: column;
      gap: 6px;
      color: var(--po-muted);
      font-size: .8rem;
    }
    .field select {
      width: 100%;
      padding: 10px 12px;
      border-radius: 10px;
      border: 1px solid var(--po-border);
      background: #fff;
      color: var(--po-text);
    }
    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 18px;
    }

    @media (max-width: 980px) {
      .po-shell { flex-direction: column; }
      .po-sidebar { width: 100%; }
      .nav { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .message-layout { grid-template-columns: 1fr; }
      .message-list-panel { border-right: 0; border-bottom: 1px solid var(--po-border); }
      .topbar-tools { width: 100%; justify-content: space-between; }
      .search { min-width: 160px; }
    }
    @media (max-width: 640px) {
      .page-inner { padding: 16px; }
      .topbar { padding: 12px 16px; }
      .header-card { flex-direction: column; align-items: flex-start; }
      .toolbar { flex-direction: column; align-items: stretch; }
      .toolbar-actions { justify-content: space-between; }
      .messages-toolbar { flex-direction: column; align-items: stretch; }
      .filter-grid { grid-template-columns: 1fr; }
      .detail-actions { flex-direction: column; }
      .search { min-width: 0; }
    }
  </style>
</head>
<body>
  <div class="shell">
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-mark">D</div>
        <div class="brand-copy">
          <strong>DILG</strong>
          <span>Provincial Office User</span>
        </div>
      </div>

      <div style="display:flex;align-items:center;gap:10px;margin:14px 4px 0;padding:10px 8px;border:1px solid rgba(255,255,255,.12);border-radius:12px;background:rgba(255,255,255,.08);"><div style="width:36px;height:36px;display:grid;place-items:center;flex:0 0 36px;overflow:hidden;border:1px solid rgba(255,255,255,.28);border-radius:50%;background:rgba(255,255,255,.14);font-size:.82rem;font-weight:800;"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="PO User profile photo" style="width:100%;height:100%;object-fit:cover;display:block;"><?php else: ?><?= htmlspecialchars(strtoupper(substr($userName, 0, 2))) ?><?php endif; ?></div><div style="min-width:0;"><span style="display:flex;align-items:center;gap:6px;font-size:.72rem;color:rgba(255,255,255,.88);"><span style="width:7px;height:7px;border-radius:50%;background:#34d399;"></span>Online</span><span style="display:block;margin-top:3px;color:#fff;font-size:.78rem;font-weight:700;"><?= htmlspecialchars($userName) ?></span></div></div>

      <nav class="nav" aria-label="Primary navigation">
        <button class="nav-item" type="button" onclick="window.location.href='dashboard.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('dashboard') ?></span><span class="nav-label">Dashboard</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='inquiries.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('submissions') ?></span><span class="nav-label">My Submissions</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='office.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('office') ?></span><span class="nav-label">My Office</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='calendar.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('calendar') ?></span><span class="nav-label">Calendar</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='reports.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('reports') ?></span><span class="nav-label">Reports</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='notifications.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('notifications') ?></span><span class="nav-label">Notifications</span></span><span class="nav-badge" id="sidebarBadge"><?= htmlspecialchars((string)$notificationCount) ?></span></button>        <button class="nav-item" type="button" onclick="window.location.href='settings.php'"><span class="nav-main"><span class="nav-icon"></span><span class="nav-label">Settings</span></span></button>
        <button class="nav-item" id="logout-btn" type="button"><span class="nav-main"><span class="nav-icon"></span><span class="nav-label">Log Out</span></span></button>
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
            <strong>Department of the Interior and Local Government</strong>
            <span>Province of Surigao del Sur  Provincial Office Dashboard</span>
          </div>
        </div>

        <div class="topbar-tools">
          <label class="search" aria-label="Search message dashboard">
            <span><?= po_icon('search') ?></span>
            <input id="globalSearch" type="search" placeholder="Search dashboard..." />
          </label>

          <div class="notif" aria-label="Notifications" onclick="window.location.href='notifications.php'" style="cursor:pointer;"><?= po_icon('notifications') ?><span class="counter" id="headerBadge"><?= htmlspecialchars((string)$notificationCount) ?></span></div>

          <div class="profile" aria-expanded="false">
            <span class="avatar"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="PO User profile photo" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;"><?php else: ?><?= htmlspecialchars(strtoupper(substr($userName, 0, 2))) ?><?php endif; ?></span>
            <span class="profile-meta">
              <strong><?= htmlspecialchars($userName) ?></strong>
              <span>Provincial Office</span>
            </span>
          </div>
        </div>
      </header>

      <main class="page-inner">
        <div class="page-header">
          <div class="header-card">
            <div class="page-icon"><?= po_icon('document') ?></div>
            <div>
              <h1>Messages</h1>
              <div class="breadcrumb">Dashboard &nbsp;&nbsp;&nbsp;&nbsp; Messages</div>
            </div>
          </div>
          <p class="page-description">View and manage your notifications and system messages.</p>
        </div>

        <div class="messages-shell">
          <div class="toolbar">
            <div class="tab-list">
              <button class="tab active" data-tab="all" type="button">All Messages</button>
              <button class="tab" data-tab="system" type="button">System Notifications</button>
              <button class="tab" data-tab="inquiry" type="button">Inquiry Updates</button>
              <button class="tab" data-tab="announcement" type="button">Announcements</button>
              <button class="tab" data-tab="alert" type="button">Alerts</button>
            </div>
            <div class="toolbar-actions">
              <button class="filter-btn" id="filterBtn" type="button">Filter</button>
              <button class="mark-btn" id="markAllReadBtn" type="button">Mark all as read</button>
            </div>
          </div>

          <div class="messages-toolbar">
            <label class="search-wrap" aria-label="Search messages">
              <span><?= po_icon('search') ?></span>
              <input id="messageSearch" type="search" placeholder="Search messages..." value="" />
            </label>
          </div>

          <div class="message-layout">
            <section class="message-list-panel">
              <div id="messageList" class="message-list"></div>
              <div class="pagination-wrap">
                <div id="pageInfo">Showing 0 to 0 of 0 messages</div>
                <div id="pager" class="pager"></div>
              </div>
            </section>

            <aside class="detail-panel">
              <div id="detailPanel" class="detail-empty">Select a message to view details.</div>
            </aside>
          </div>
        </div>
      </main>
    </div>
  </div>

  <div id="filterModal" class="modal" aria-hidden="true">
    <div class="modal-card">
      <div class="modal-header">
        <h3>Filter messages</h3>
        <button type="button" class="close-modal" id="closeFilterModal"></button>
      </div>

      <div class="filter-grid">
        <div class="field">
          <label>Category</label>
          <select id="filterCategory">
            <option value="all">All categories</option>
            <option value="System">System</option>
            <option value="Inquiry">Inquiry</option>
            <option value="Announcement">Announcement</option>
            <option value="Event">Event</option>
            <option value="Document">Document</option>
            <option value="Form">Form</option>
            <option value="Alert">Alert</option>
          </select>
        </div>
        <div class="field">
          <label>Status</label>
          <select id="filterStatus">
            <option value="all">All status</option>
            <option value="unread">Unread</option>
            <option value="read">Read</option>
          </select>
        </div>
        <div class="field">
          <label>Date range</label>
          <select id="filterRange">
            <option value="all">All time</option>
            <option value="today">Today</option>
            <option value="7d">Last 7 days</option>
            <option value="30d">Last 30 days</option>
            <option value="month">This month</option>
          </select>
        </div>
        <div class="field">
          <label>Source</label>
          <select id="filterSource">
            <option value="all">All sources</option>
            <option value="System">System</option>
            <option value="Administration">Administration</option>
            <option value="Inquiry">Inquiry</option>
            <option value="Calendar">Calendar</option>
            <option value="Documents">Documents</option>
            <option value="Forms">Forms</option>
          </select>
        </div>
      </div>

      <div class="modal-actions">
        <button type="button" class="soft-btn" id="clearFiltersBtn">Clear</button>
        <button type="button" class="mark-btn" id="applyFiltersBtn">Apply Filters</button>
      </div>
    </div>
  </div>

  <script>
    const state = {
      tab: 'all',
      search: '',
      category: 'all',
      status: 'all',
      range: 'all',
      source: 'all',
      page: 1,
      limit: 8,
      selectedId: null,
      total: 0
    };

    const messageList = document.getElementById('messageList');
    const detailPanel = document.getElementById('detailPanel');
    const pageInfo = document.getElementById('pageInfo');
    const pager = document.getElementById('pager');

    function updateBadges(unreadCount) {
      const badgeEls = [document.getElementById('sidebarBadge'), document.getElementById('headerBadge')];
      badgeEls.forEach((el) => {
        if (!el) return;
        el.textContent = unreadCount;
        el.style.display = unreadCount > 0 ? 'grid' : 'none';
      });
    }

    function updateTabState() {
      document.querySelectorAll('.tab').forEach((button) => {
        button.classList.toggle('active', button.dataset.tab === state.tab);
      });
    }

    function buildQuery() {
      const params = new URLSearchParams({
        action: 'list',
        tab: state.tab,
        category: state.category,
        status: state.status,
        range: state.range,
        source: state.source,
        page: state.page,
        limit: state.limit
      });

      if (state.search.trim()) {
        params.set('search', state.search.trim());
      }
      return params.toString();
    }

    function formatBadge(category) {
      const palette = {
        System: '#64748b',
        Inquiry: '#3b82f6',
        Announcement: '#f59e0b',
        Event: '#10b981',
        Document: '#ef4444',
        Form: '#8b5cf6',
        Alert: '#ef4444'
      };
      return palette[category] || '#64748b';
    }

    function iconForCategory(category) {
      const icons = {
        System: <?= json_encode(po_icon('settings')) ?>,
        Inquiry: <?= json_encode(po_icon('document')) ?>,
        Announcement: <?= json_encode(po_icon('announcement')) ?>,
        Event: <?= json_encode(po_icon('calendar')) ?>,
        Document: <?= json_encode(po_icon('document')) ?>,
        Form: <?= json_encode(po_icon('submissions')) ?>,
        Alert: <?= json_encode(po_icon('overdue')) ?>
      };
      return icons[category] || icons.System;
    }

    function renderMessages(items) {
      if (!items.length) {
        messageList.innerHTML = '<div class="detail-empty" style="min-height:260px;border:none;border-top:1px solid var(--po-border);">No messages match your filters.</div>';
        detailPanel.innerHTML = '<div class="detail-empty">No message selected.</div>';
        return;
      }

      messageList.innerHTML = items.map((item) => {
        const rowClass = [
          'message-row',
          item.is_read ? 'read' : 'unread',
          item.id === state.selectedId ? 'selected' : ''
        ].join(' ');

        return `
          <div class="${rowClass}" data-id="${item.id}" data-read="${item.is_read ? '1' : '0'}">
            <div class="message-dot" aria-hidden="true"></div>
            <div class="message-icon">${iconForCategory(item.category)}</div>
            <div class="message-body">
              <div class="message-header">
                <h3 class="message-title">${item.title}</h3>
                <span class="status-chip ${item.is_read ? '' : 'unread'}">${item.is_read ? 'Read' : 'Unread'}</span>
              </div>
              <p class="message-preview">${item.message}</p>
              <div class="message-meta">
                <span class="meta-badge" style="background:${formatBadge(item.category)};">${item.category}</span>
                <span>${item.time_ago || item.created_at}</span>
              </div>
            </div>
            <div aria-hidden="true" style="color:var(--po-muted);font-size:1.4rem;padding-top:6px;"></div>
          </div>
        `;
      }).join('');

      messageList.querySelectorAll('.message-row').forEach((row) => {
        row.addEventListener('click', () => {
          const id = Number(row.dataset.id);
          state.selectedId = id;
          setSelectedMessage(id);
          markRead(id, true);
        });
      });

      if (!state.selectedId && items.length) {
        state.selectedId = Number(items[0].id);
      }
      setSelectedMessage(state.selectedId);
    }

    function setSelectedMessage(id) {
      const selected = id && document.querySelector('.message-row[data-id="' + id + '"]');
      document.querySelectorAll('.message-row').forEach((row) => {
        row.classList.toggle('selected', Number(row.dataset.id) === Number(id));
      });
      if (!selected) return;
      fetch('../shared/auth/notifications_api.php?action=detail&id=' + id)
        .then((response) => response.json())
        .then((data) => {
          if (!data.success || !data.notification) {
            detailPanel.innerHTML = '<div class="detail-empty">Unable to load this message.</div>';
            return;
          }
          const msg = data.notification;
          const statusLabel = msg.is_read ? 'Read' : 'Unread';
          const source = msg.related_type ? String(msg.related_type).replace(/_/g, ' ') : 'System';
          const dateText = new Date(msg.created_at).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
          detailPanel.innerHTML = `
            <div class="detail-card">
              <div class="detail-header">
                <div class="detail-icon">${iconForCategory(msg.category || 'System')}</div>
                <div class="detail-title">
                  <h2>${msg.title}</h2>
                  <div class="detail-date">
                    <span>${dateText}</span>
                    <span class="detail-badge" style="background:${formatBadge(msg.category || 'System')};">${msg.category || 'System'}</span>
                    <span class="status-chip ${msg.is_read ? '' : 'unread'}">${statusLabel}</span>
                  </div>
                </div>
              </div>

              <div class="detail-content">
                <p>${msg.message}</p>
                <div class="detail-meta-box">
                  <div><strong>Source:</strong> ${source}</div>
                  <div><strong>Module:</strong> ${msg.related_type || 'System'}</div>
                  <div><strong>Action:</strong> ${msg.action_url ? '<a href="' + msg.action_url + '" style="color:#2d7ff9;">Open related item</a>' : 'N/A'}</div>
                </div>
              </div>

              <div class="detail-actions">
                <button class="primary-action" data-read-action="toggle" data-id="${msg.id}" type="button">${msg.is_read ? 'Mark as Unread' : 'Mark as Read'}</button>
                <button class="danger-action" data-delete-id="${msg.id}" type="button">Delete</button>
                ${msg.action_url ? '<button class="secondary-action" onclick="window.location.href=\'' + msg.action_url + '\'" type="button">Open Related</button>' : ''}
              </div>
            </div>
          `;

          detailPanel.querySelector('[data-read-action]')?.addEventListener('click', () => {
            const id = Number(detailPanel.querySelector('[data-read-action]').dataset.id);
            if (detailPanel.querySelector('[data-read-action]').textContent.includes('Unread')) {
              markUnread(id);
            } else {
              markRead(id, false);
            }
          });

          detailPanel.querySelector('[data-delete-id]')?.addEventListener('click', () => {
            deleteMessage(Number(detailPanel.querySelector('[data-delete-id]').dataset.deleteId));
          });
        })
        .catch(() => {
          detailPanel.innerHTML = '<div class="detail-empty">Unable to load this message.</div>';
        });
    }

    function renderPagination(total, page, limit) {
      const totalPages = Math.max(1, Math.ceil(total / limit));
      const pages = [];
      for (let i = 1; i <= totalPages; i++) {
        pages.push(i);
      }

      pageInfo.textContent = total === 0 ? 'Showing 0 to 0 of 0 messages' : `Showing ${((page - 1) * limit) + 1} to ${Math.min(page * limit, total)} of ${total} messages`;

      let buttons = '';
      buttons += `<button class="page-btn" type="button" ${page <= 1 ? 'disabled' : ''} data-page="prev"></button>`;
      pages.forEach((pageNumber) => {
        buttons += `<button class="page-btn ${pageNumber === page ? 'active' : ''}" type="button" data-page="${pageNumber}">${pageNumber}</button>`;
      });
      buttons += `<button class="page-btn" type="button" ${page >= totalPages ? 'disabled' : ''} data-page="next"></button>`;
      pager.innerHTML = buttons;

      pager.querySelectorAll('.page-btn').forEach((button) => {
        const targetPage = button.dataset.page;
        if (!targetPage) return;
        button.addEventListener('click', () => {
          if (targetPage === 'prev') {
            state.page = Math.max(1, page - 1);
          } else if (targetPage === 'next') {
            state.page = Math.min(totalPages, page + 1);
          } else {
            state.page = Number(targetPage);
          }
          fetchMessages();
        });
      });
    }

    function fetchMessages() {
      const url = '../shared/auth/notifications_api.php?' + buildQuery();
      fetch(url)
        .then((response) => response.json())
        .then((data) => {
          if (!data.success) {
            messageList.innerHTML = '<div class="detail-empty" style="min-height:260px;border:none;border-top:1px solid var(--po-border);">Unable to load messages.</div>';
            detailPanel.innerHTML = '<div class="detail-empty">Unable to load message detail.</div>';
            return;
          }

          renderMessages(data.items || []);
          renderPagination(Number(data.total || 0), Number(data.page || state.page), Number(data.limit || state.limit));
        })
        .catch(() => {
          messageList.innerHTML = '<div class="detail-empty" style="min-height:260px;border:none;border-top:1px solid var(--po-border);">Unable to load messages.</div>';
          detailPanel.innerHTML = '<div class="detail-empty">Unable to load message detail.</div>';
        });
    }

    function fetchStats() {
      fetch('../shared/auth/notifications_api.php?action=stats')
        .then((response) => response.json())
        .then((data) => {
          if (!data.success || !data.stats) return;
          updateBadges(Number(data.stats.unread || 0));
        })
        .catch(() => {});
    }

    function markRead(id, keepSelection = false) {
      fetch('../shared/auth/notifications_api.php?action=mark_read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: Number(id) })
      })
        .then(() => {
          fetchStats();
          if (keepSelection && state.selectedId) {
            setSelectedMessage(state.selectedId);
          }
          fetchMessages();
        })
        .catch(() => {});
    }

    function markUnread(id) {
      fetch('../shared/auth/notifications_api.php?action=mark_unread', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: Number(id) })
      })
        .then(() => {
          fetchStats();
          fetchMessages();
        })
        .catch(() => {});
    }

    function deleteMessage(id) {
      fetch('../shared/auth/notifications_api.php?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: Number(id) })
      })
        .then(() => {
          fetchStats();
          state.selectedId = null;
          fetchMessages();
        })
        .catch(() => {});
    }

    function openFilterModal() {
      document.getElementById('filterModal').classList.add('open');
      document.getElementById('filterModal').setAttribute('aria-hidden', 'false');
    }

    function closeFilterModal() {
      document.getElementById('filterModal').classList.remove('open');
      document.getElementById('filterModal').setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('.tab').forEach((button) => {
      button.addEventListener('click', () => {
        state.tab = button.dataset.tab;
        state.page = 1;
        updateTabState();
        fetchMessages();
      });
    });

    document.getElementById('messageSearch').addEventListener('input', (event) => {
      state.search = event.target.value;
      state.page = 1;
      fetchMessages();
    });

    document.getElementById('filterBtn').addEventListener('click', openFilterModal);
    document.getElementById('closeFilterModal').addEventListener('click', closeFilterModal);

    document.getElementById('applyFiltersBtn').addEventListener('click', () => {
      state.category = document.getElementById('filterCategory').value;
      state.status = document.getElementById('filterStatus').value;
      state.range = document.getElementById('filterRange').value;
      state.source = document.getElementById('filterSource').value;
      state.page = 1;
      closeFilterModal();
      fetchMessages();
    });

    document.getElementById('clearFiltersBtn').addEventListener('click', () => {
      document.getElementById('filterCategory').value = 'all';
      document.getElementById('filterStatus').value = 'all';
      document.getElementById('filterRange').value = 'all';
      document.getElementById('filterSource').value = 'all';
      state.category = 'all';
      state.status = 'all';
      state.range = 'all';
      state.source = 'all';
      state.page = 1;
      closeFilterModal();
      fetchMessages();
    });

    document.getElementById('markAllReadBtn').addEventListener('click', () => {
      fetch('../shared/auth/notifications_api.php?action=mark_read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: 0 })
      })
        .then(() => {
          fetchStats();
          fetchMessages();
        })
        .catch(() => {});
    });

    document.getElementById('logout-btn').addEventListener('click', () => {
      fetch('../shared/auth/logout.php', { method: 'POST' }).finally(() => {
        window.location.href = '../../index.html';
      });
    });

    document.getElementById('filterModal').addEventListener('click', (event) => {
      if (event.target.id === 'filterModal') {
        closeFilterModal();
      }
    });

    document.getElementById('globalSearch').addEventListener('input', (event) => {
      document.getElementById('messageSearch').value = event.target.value;
      state.search = event.target.value;
      state.page = 1;
      fetchMessages();
    });

    updateTabState();
    fetchStats();
    fetchMessages();
  </script>
</body>
</html>

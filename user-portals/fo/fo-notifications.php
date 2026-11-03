<?php
require_once __DIR__ . '/../shared/auth/db.php';
require_once __DIR__ . '/../shared/auth/middleware.php';
require_once __DIR__ . '/../shared/auth/workflow.php';
require_role('FO USER');

$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);
$profilePhoto = get_user_profile_photo($pdo, $userId);
$userName = $_SESSION['username'] ?? 'FO User';
$officeName = $_SESSION['office_name'] ?? 'Field Office';
workflow_ensure_schema($pdo);
workflow_sync_overdue($pdo);

function ensure_notifications_table(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        office_id INT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        category VARCHAR(50) NOT NULL DEFAULT 'System',
        type VARCHAR(50) NOT NULL DEFAULT 'system',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        related_type VARCHAR(50) NULL,
        related_id INT NULL,
        action_url VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at DATETIME NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_notifications (user_id, is_read, created_at),
        INDEX idx_user_category (user_id, category, created_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN category VARCHAR(50) NOT NULL DEFAULT 'System' AFTER message");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'system' AFTER category");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN related_type VARCHAR(50) NULL AFTER is_read");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN related_id INT NULL AFTER related_type");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN action_url VARCHAR(500) NULL AFTER related_id");
    } catch (Exception $e) {}
}

ensure_notifications_table($pdo);

$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
$stmt->execute([':uid' => $userId]);
$unreadCount = (int)$stmt->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Notifications - DILG FO Dashboard</title>
  <style>
    :root {
      --fo-green-900: #0b3f34;
      --fo-green-800: #0f4d3d;
      --fo-green-700: #1d6050;
      --fo-bg: #edf3f1;
      --fo-surface: #ffffff;
      --fo-border: rgba(15, 51, 38, 0.12);
      --fo-text: #1d2a2b;
      --fo-muted: #64747a;
      --fo-shadow: 0 12px 28px rgba(18, 32, 28, 0.08);
      --fo-red: #e65d5d;
      --fo-yellow: #f0b648;
      --fo-blue: #2d80f2;
      --fo-green: #2fb778;
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      background: var(--fo-bg);
      color: var(--fo-text);
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
    a { color: inherit; text-decoration: none; }
    button, input { font: inherit; }

    .layout {
      display: flex;
      min-height: 100vh;
      background: linear-gradient(180deg, #edf4f1 0%, #f4f7f7 100%);
    }

    .sidebar {
      width: 270px;
      background: linear-gradient(180deg, #0b3d91 0%, #1456a0 100%);
      color: #fff;
      display: flex;
      flex-direction: column;
      padding: 18px 14px 12px;
      box-shadow: inset -1px 0 0 rgba(255,255,255,0.08);
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 8px 8px 18px;
      border-bottom: 1px solid rgba(255,255,255,0.12);
    }
    .brand-logo {
      width: 48px; height: 48px; border-radius: 50%;
      background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1);
      display: grid; place-items: center; font-weight: 800; font-size: 1.15rem;
    }
    .brand-copy strong { display: block; font-size: 1.05rem; letter-spacing: 0.06em; line-height: 1.08; }
    .brand-copy span { display: block; font-size: 0.68rem; opacity: 0.8; margin-top: 4px; line-height: 1.4; }

    .profile-box {
      display: flex; align-items: center; gap: 12px; margin: 18px 6px 8px; padding: 10px 8px;
      background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px;
    }
    .profile-avatar {
      width: 36px; height: 36px; flex: 0 0 36px; border-radius: 50%; display: grid; place-items: center;
      background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.15); font-weight: 800; color: #fff;
    }
    .profile-avatar img, .trigger-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }
    .profile-role {
      display: flex; align-items: center; gap: 7px; font-size: 0.8rem; color: rgba(255,255,255,0.88);
    }
    .status-dot {
      width: 8px; height: 8px; border-radius: 50%; background: #34d399; box-shadow: 0 0 0 4px rgba(52,211,153,0.15);
    }

    .nav { margin-top: 8px; display: flex; flex-direction: column; gap: 4px; padding: 0 4px; }
    .nav-item {
      width: 100%; border: none; border-radius: 10px; background: transparent; color: rgba(255,255,255,0.9);
      display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 11px 10px; text-align: left; cursor: pointer; font-weight: 600; transition: all 0.2s ease;
    }
    .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.12); box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08); }
    .nav-item.active { background: rgba(255,255,255,0.16); box-shadow: inset 3px 0 0 rgba(255,255,255,0.8); }
    .nav-main { display: inline-flex; align-items: center; gap: 10px; min-width: 0; }
    .nav-icon { font-size: 1rem; }
    .nav-icon svg, .control-icon, .notif-icon svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; vertical-align: middle; }
    .nav-label { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nav-badge {
      min-width: 18px; height: 18px; border-radius: 999px; padding: 0 6px; background: rgba(255,255,255,0.18); color: #fff; display: grid; place-items: center; font-size: 0.68rem; font-weight: 800;
    }
    .sidebar-footer { margin-top: auto; border-top: 1px solid rgba(255,255,255,0.12); padding-top: 12px; }
    .footer-brand { display: flex; align-items: center; gap: 10px; padding: 4px 8px 0; color: rgba(255,255,255,0.92); font-weight: 700; font-size: 0.85rem; }
    .footer-seal { width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,0.1); display: grid; place-items: center; border: 1px solid rgba(255,255,255,0.08); font-weight: 800; }

    .main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
    .topbar {
      display: flex; align-items: center; justify-content: space-between; gap: 20px; background: rgba(255,255,255,0.92); border-bottom: 1px solid var(--fo-border); backdrop-filter: blur(8px); padding: 14px 20px; position: sticky; top: 0; z-index: 20;
    }
    .topbar-left { display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1; }
    .menu-btn { width: 42px; height: 42px; border: 1px solid var(--fo-border); background: #fff; border-radius: 12px; display: grid; place-items: center; box-shadow: var(--fo-shadow); cursor: pointer; color: var(--fo-green-800); }
    .page-title { min-width: 0; flex: 1; }
    .page-title h1 { margin: 0; font-size: clamp(1.4rem, 2vw, 2.05rem); font-weight: 700; line-height: 1.2; color: var(--fo-green-900); }
    .page-title p { margin: 6px 0 0; color: var(--fo-muted); font-size: 0.92rem; }
    .topbar-right { display: flex; align-items: center; gap: 12px; }
    .search-box { display: flex; align-items: center; gap: 8px; width: min(320px, 30vw); min-width: 180px; background: #fff; border: 1px solid var(--fo-border); border-radius: 12px; padding: 10px 12px; box-shadow: var(--fo-shadow); color: var(--fo-muted); }
    .search-box input { width: 100%; border: none; background: transparent; color: var(--fo-text); outline: none; font-size: 0.92rem; }
    .notif-button { position: relative; width: 42px; height: 42px; border: 1px solid var(--fo-border); background: #fff; border-radius: 12px; display: grid; place-items: center; box-shadow: var(--fo-shadow); cursor: pointer; color: var(--fo-green-800); }
    .notif-count { position: absolute; top: -6px; right: -5px; min-width: 18px; height: 18px; border-radius: 999px; display: grid; place-items: center; padding: 0 5px; background: var(--fo-red); color: #fff; font-size: 0.68rem; font-weight: 800; }
    .profile-wrap { position: relative; }
    .profile-trigger { display: flex; align-items: center; gap: 10px; padding: 7px 10px 7px 8px; background: #fff; border: 1px solid var(--fo-border); border-radius: 12px; box-shadow: var(--fo-shadow); cursor: pointer; color: var(--fo-text); }
    .trigger-avatar { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #1f9d73, #2d80f2); display: grid; place-items: center; color: #fff; font-weight: 800; font-size: 0.8rem; }
    .trigger-meta { display: flex; flex-direction: column; line-height: 1.15; text-align: left; }
    .trigger-meta strong { font-size: 0.9rem; }
    .trigger-meta span { font-size: 0.7rem; color: var(--fo-muted); }
    .profile-dropdown { position: absolute; top: calc(100% + 9px); right: 0; width: 180px; background: #fff; border: 1px solid var(--fo-border); border-radius: 12px; box-shadow: 0 18px 34px rgba(16,35,35,0.14); padding: 8px; display: none; z-index: 30; }
    .profile-wrap.open .profile-dropdown { display: block; }
    .profile-dropdown a { display: block; padding: 10px 12px; border-radius: 8px; color: var(--fo-text); font-weight: 600; cursor: pointer; }
    .profile-dropdown a:hover { background: #f3f8f7; color: var(--fo-green-800); }

    .content { padding: 22px 20px 24px; display: flex; flex-direction: column; gap: 18px; }
    .notif-shell {
      background: rgba(255,255,255,0.96); border: 1px solid var(--fo-border); border-radius: 16px; box-shadow: var(--fo-shadow); overflow: hidden;
    }
    .notif-header {
      display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 24px 20px 18px; border-bottom: 1px solid var(--fo-border); flex-wrap: wrap;
    }
    .notif-title-wrap { display: flex; align-items: center; gap: 12px; }
    .notif-icon { width: 46px; height: 46px; border-radius: 14px; background: rgba(240, 182, 72, 0.12); color: var(--fo-yellow); display: grid; place-items: center; font-size: 1.5rem; }
    .notif-icon svg { width: 24px; height: 24px; }
    .notif-title-wrap h2 { margin: 0; font-size: clamp(1.8rem, 2.5vw, 2.2rem); color: var(--fo-green-900); }
    .notif-title-wrap p { margin: 4px 0 0; color: var(--fo-muted); font-size: 0.95rem; }
    .notif-actions {
      display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    }
    .mark-all-btn, .filter-btn {
      border: 1px solid var(--fo-border); background: #fff; color: var(--fo-green-800); border-radius: 10px; font-weight: 700; padding: 10px 16px; cursor: pointer; white-space: nowrap;
    }
    .mark-all-btn:hover, .filter-btn:hover { background: #f4faf8; }

    .tabs-bar {
      display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 14px 18px; border-bottom: 1px solid var(--fo-border); background: #fff; overflow-x: auto;
    }
    .tab-btn {
      border: 1px solid var(--fo-border); background: #fff; color: var(--fo-muted); border-radius: 8px; padding: 8px 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; font-size: 0.9rem;
    }
    .tab-btn.active {
      background: #f0f5f0; border-color: rgba(15,77,61,0.25); color: var(--fo-green-800);
    }
    .tab-count {
      display: inline-grid; place-items: center; min-width: 20px; height: 20px; border-radius: 999px; padding: 0 6px; background: rgba(15,77,61,0.12); font-size: 0.7rem; font-weight: 800; color: var(--fo-green-800);
    }

    .notif-list-panel { background: #fff; }
    .notif-list { display: flex; flex-direction: column; min-height: 400px; }
    .notif-row {
      display: grid; grid-template-columns: 46px 1fr auto; gap: 14px; align-items: start; margin: 0 8px; padding: 16px 14px; border: 1px solid var(--fo-border); border-top: 0; cursor: pointer; transition: background 0.2s ease; position: relative;
    }
    .notif-row:hover { background: #f8faf9; }
    .notif-row.unread { background: #f9fbfa; }
    .notif-dot { width: 44px; height: 44px; border-radius: 12px; background: #dbeafe; color: #2563eb; margin-top: 0; flex-shrink: 0; position: relative; }
    .notif-dot::after { content: ''; position: absolute; inset: 11px; border: 2px solid currentColor; border-radius: 3px; }
    .notif-row.read .notif-dot { background: #ecfdf5; color: #047857; }
    .notif-row.unread .notif-dot::before { content: ''; position: absolute; width: 9px; height: 9px; border-radius: 50%; background: #2563eb; right: -5px; top: -5px; }
    .notif-content { min-width: 0; }
    .notif-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 6px; }
    .notif-title { margin: 0; font-size: 1.02rem; color: var(--fo-text); font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .notif-row.unread .notif-title { font-weight: 800; }
    .notif-desc {
      margin: 0; color: var(--fo-muted); font-size: 0.9rem; line-height: 1.5; max-height: 3.2em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    }
    .notif-footer { display: flex; align-items: center; gap: 10px; margin-top: 8px; flex-wrap: wrap; }
    .notif-badge {
      display: inline-block; border-radius: 999px; padding: 4px 9px; font-size: 0.7rem; font-weight: 700; color: #fff;
    }
    .notif-time { font-size: 0.75rem; color: var(--fo-muted); }
    .notif-meta { display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; color: var(--fo-muted); font-size: 0.75rem; }
    .notif-meta strong { color: #f97316; font-weight: 700; }
    .notif-meta .priority-high { color: #dc2626; }
    .notif-meta .priority-low { color: #047857; }
    .notif-sender { margin-top: 7px; color: var(--fo-muted); font-size: 0.75rem; }
    .notif-menu {
      position: relative; width: 32px; height: 32px; border: none; background: #fff; border-radius: 8px; cursor: pointer; color: var(--fo-muted); transition: background 0.2s; flex-shrink: 0; padding: 0; display: grid; place-items: center;
    }
    .notif-menu:hover { background: #f5f7f6; }
    .menu-dropdown {
      position: absolute; top: calc(100% + 6px); right: 0; width: 140px; background: #fff; border: 1px solid var(--fo-border); border-radius: 10px; box-shadow: 0 10px 24px rgba(18,32,28,0.12); padding: 4px; display: none; z-index: 40;
    }
    .notif-row.menu-open .menu-dropdown { display: block; }
    .menu-item {
      display: block; width: 100%; text-align: left; border: none; background: transparent; color: var(--fo-text); padding: 8px 10px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.85rem;
    }
    .menu-item:hover { background: #f5f7f6; color: var(--fo-green-800); }
    .menu-item.danger { color: var(--fo-red); }
    .menu-item.danger:hover { background: rgba(230, 93, 93, 0.08); }

    .empty-state { display: grid; place-items: center; min-height: 300px; color: var(--fo-muted); text-align: center; padding: 20px; font-size: 0.95rem; }

    .pagination-bar {
      display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 14px 18px; border-top: 1px solid var(--fo-border); background: #fff; flex-wrap: wrap;
    }
    .page-info { color: var(--fo-muted); font-size: 0.82rem; }
    .page-controls { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .page-button {
      width: 34px; height: 34px; border: 1px solid var(--fo-border); border-radius: 8px; background: #fff; color: var(--fo-green-800); font-weight: 700; cursor: pointer; display: grid; place-items: center; font-size: 0.85rem;
    }
    .page-button.active { background: var(--fo-green-900); color: #fff; border-color: var(--fo-green-900); }
    .page-button:disabled { opacity: 0.45; cursor: not-allowed; }

    .filter-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 50; grid-place-items: center; padding: 20px; }
    .filter-modal.open { display: grid; }
    .filter-card { background: #fff; border-radius: 14px; box-shadow: var(--fo-shadow); width: 100%; max-width: 380px; padding: 20px; }
    .filter-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
    .filter-header h3 { margin: 0; font-size: 1.1rem; color: var(--fo-text); }
    .close-btn { background: none; border: none; color: var(--fo-muted); font-size: 1.4rem; cursor: pointer; padding: 0; }
    .filter-field { margin-bottom: 16px; }
    .filter-field label { display: block; font-weight: 700; color: var(--fo-text); font-size: 0.85rem; margin-bottom: 6px; }
    .filter-field select { width: 100%; border: 1px solid var(--fo-border); border-radius: 8px; padding: 8px 10px; color: var(--fo-text); background: #fff; font-weight: 600; }
    .filter-actions { display: flex; gap: 10px; margin-top: 18px; }
    .filter-clear, .filter-apply { flex: 1; border: none; border-radius: 8px; padding: 10px; font-weight: 700; cursor: pointer; }
    .filter-clear { background: #fff; color: var(--fo-green-800); border: 1px solid var(--fo-border); }
    .filter-clear:hover { background: #f5f7f6; }
    .filter-apply { background: var(--fo-green-900); color: #fff; }
    .filter-apply:hover { background: var(--fo-green-800); }

    .show-entries { display: flex; align-items: center; gap: 8px; margin-left: auto; }
    .show-entries select { border: 1px solid var(--fo-border); border-radius: 8px; padding: 6px 8px; color: var(--fo-text); background: #fff; font-weight: 600; font-size: 0.85rem; }

    @media (max-width: 1024px) {
      .sidebar { width: 240px; }
      .notif-header { padding: 18px; }
      .notif-row { padding: 14px; }
    }

    @media (max-width: 768px) {
      .layout { flex-direction: column; }
      .sidebar { width: 100%; }
      .topbar { flex-wrap: wrap; gap: 12px; }
      .topbar-left { order: 1; width: 100%; }
      .topbar-right { order: 2; width: 100%; justify-content: space-between; }
      .search-box { width: 100%; }
      .notif-header { flex-direction: column; align-items: flex-start; gap: 14px; }
      .notif-row { grid-template-columns: auto 1fr auto; }
      .pagination-bar { flex-direction: column; align-items: flex-start; }
      .page-controls { width: 100%; justify-content: flex-start; }
    }

    @media (max-width: 560px) {
      .content { padding: 14px; }
      .notif-header { padding: 16px; }
      .tabs-bar { padding: 12px 14px; gap: 6px; }
      .tab-btn { padding: 6px 10px; font-size: 0.8rem; }
      .notif-row { grid-template-columns: auto 1fr 28px; gap: 10px; padding: 12px 14px; }
      .notif-title { font-size: 0.95rem; }
      .notif-desc { font-size: 0.85rem; }
      .filter-modal { padding: 10px; }
      .filter-card { max-width: 100%; }
    }
  </style>
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-logo">D</div>
        <div class="brand-copy">
          <strong>DILG PORTAL</strong>
          <span>Department of the Interior and Local Governance</span>
        </div>
      </div>

      <div class="profile-box">
        <div class="profile-avatar"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="FO User profile photo"><?php else: ?><?= htmlspecialchars(strtoupper(substr($userName, 0, 2))) ?><?php endif; ?></div>
        <div class="profile-meta">
          <div class="profile-role"><span class="status-dot"></span> Online</div>
          <div style="font-size:0.82rem;font-weight:700; margin-top:3px;"><?= htmlspecialchars($userName) ?></div>
          <div style="font-size:0.68rem;opacity:.8;"><?= htmlspecialchars($officeName) ?></div>
        </div>
      </div>

      <nav class="nav" aria-label="FO User navigation">
        <button class="nav-item" type="button" onclick="window.location.href='dashboard.php'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5M5.5 10.5V20h13v-9.5M9 20v-5h6v5"/></svg></span><span class="nav-label">Dashboard</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='inquiries.php'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6M9 17h6"/></svg></span><span class="nav-label">My Submissions</span></span></button>
        <button class="nav-item" type="button" onclick="location.href='../../news.html'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11 16 6v12L4 13zM4 11v2M16 9h3a2 2 0 0 1 0 4h-3"/></svg></span><span class="nav-label">Announcements</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='calendar.php'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg></span><span class="nav-label">Calendar &amp; Activities</span></span></button>
        <button class="nav-item active" type="button" onclick="location.href='notifications.php'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></span><span class="nav-label">Notifications</span></span><span class="nav-badge" id="sidebarBadge"><?= htmlspecialchars((string)$unreadCount) ?></span></button>
        <button class="nav-item" type="button" onclick="location.href='settings.php'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19 15l1 1-2 2-1-1M9 19H7v-3M5 9l-1-1 2-2 1 1M15 5V3h2v2"/></svg></span><span class="nav-label">Profile Settings</span></span></button>
        <button class="nav-item" type="button" id="logout-sidebar"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/></svg></span><span class="nav-label">Log Out</span></span></button>
      </nav>

      <div class="sidebar-footer">
        <div class="footer-brand">
          <div class="footer-seal">D</div>
          <div>Department of the Interior and Local Government</div>
        </div>
      </div>
    </aside>

    <main class="main">
      <header class="topbar">
        <div class="topbar-left">
          <button class="menu-btn" type="button" aria-label="Toggle menu">&#9776;</button>
          <div class="page-title">
            <h1>FO User Dashboard</h1>
            <p>Welcome back! Here's your field office performance at a glance.</p>
          </div>
        </div>

        <div class="topbar-right">
          <label class="search-box" aria-label="Search dashboard">
            <span><svg class="control-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg></span>
            <input type="search" placeholder="Search inquiries, documents..." />
          </label>

          <button class="notif-button" type="button" aria-label="Notifications">
            <svg class="control-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
            <span class="notif-count" id="headerBadge"><?= htmlspecialchars((string)$unreadCount) ?></span>
          </button>

          <div class="profile-wrap">
            <button class="profile-trigger" type="button" aria-expanded="false">
              <span class="trigger-avatar"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="FO User profile photo"><?php else: ?><?= htmlspecialchars(strtoupper(substr($userName, 0, 2))) ?><?php endif; ?></span>
              <span class="trigger-meta">
                <strong><?= htmlspecialchars($userName) ?></strong>
                <span>Field Office User</span>
              </span>
            </button>
            <div class="profile-dropdown">
              <a href="settings.php">Profile</a>
              <a href="settings.php">Settings</a>
              <a href="#" id="logout-menu">Log Out</a>
            </div>
          </div>
        </div>
      </header>

      <div class="content">
        <section class="notif-shell">
          <div class="notif-header">
            <div class="notif-title-wrap">
              <div class="notif-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></div>
              <div>
                <h2>Notifications</h2>
                <p>Stay updated with important alerts and updates.</p>
              </div>
            </div>
            <div class="notif-actions">
              <button id="markAllReadBtn" class="mark-all-btn" type="button">&#10003; Mark all as read</button>
              <button id="filterBtn" class="filter-btn" type="button">Filter</button>
            </div>
          </div>

          <div class="tabs-bar">
            <button class="tab-btn active" type="button" data-tab="all">All <span class="tab-count" id="allCount">0</span></button>
            <button class="tab-btn" type="button" data-tab="unread">Unread <span class="tab-count" id="unreadCount">0</span></button>
            <button class="tab-btn" type="button" data-tab="read">Read (Mark done) <span class="tab-count" id="readCount">0</span></button>
          </div>

          <div class="notif-list-panel">
            <div id="notifList" class="notif-list"></div>
          </div>

          <div class="pagination-bar">
            <div class="page-info">Showing <span id="pageStart">0</span> to <span id="pageEnd">0</span> of <span id="pageTotal">0</span> notifications</div>
            <div class="show-entries">
              <label>Show</label>
              <select id="entriesPerPage">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="20">20</option>
                <option value="50">50</option>
              </select>
              <label>entries</label>
            </div>
            <div id="paginationControls" class="page-controls"></div>
          </div>
        </section>
      </div>
    </main>
  </div>

  <div id="filterModal" class="filter-modal">
    <div class="filter-card">
      <div class="filter-header">
        <h3>Filter notifications</h3>
        <button type="button" class="close-btn" id="closeFilterBtn">&times;</button>
      </div>
      <div class="filter-field">
        <label for="filterRange">Date range</label>
        <select id="filterRange">
          <option value="all">Any time</option>
          <option value="today">Today</option>
          <option value="7d">Last 7 days</option>
          <option value="30d">Last 30 days</option>
        </select>
      </div>
      <div class="filter-actions">
        <button type="button" class="filter-clear" id="clearFilterBtn">Clear</button>
        <button type="button" class="filter-apply" id="applyFilterBtn">Apply Filters</button>
      </div>
    </div>
  </div>

  <script>
    const state = {
      tab: 'all',
      category: 'all',
      status: 'all',
      range: 'all',
      page: 1,
      limit: 10,
      selectedId: null,
      list: [],
      total: 0
    };

    const tabButtons = document.querySelectorAll('.tab-btn');
    const notifListEl = document.getElementById('notifList');
    const sidebarBadgeEl = document.getElementById('sidebarBadge');
    const headerBadgeEl = document.getElementById('headerBadge');

    const categoryIcons = {
      'System': '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19 15l1 1-2 2-1-1M9 19H7v-3M5 9l-1-1 2-2 1 1M15 5V3h2v2"/></svg>',
      'Inquiry': '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6M9 17h6"/></svg>',
      'Document': '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6M9 17h6"/></svg>',
      'Announcement': '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11 16 6v12L4 13zM4 11v2M16 9h3a2 2 0 0 1 0 4h-3"/></svg>',
      'Calendar': '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg>'
    };

    const categoryColors = {
      'System': '#64748b',
      'Inquiry': '#3b82f6',
      'Document': '#8c67d4',
      'Announcement': '#f59e0b',
      'Calendar': '#10b981'
    };

    function getIconForCategory(category) {
      return categoryIcons[category] || '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>';
    }

    function getColorForCategory(category) {
      return categoryColors[category] || '#64748b';
    }

    async function loadNotifications() {
      const params = new URLSearchParams({
        action: 'list',
        tab: state.tab,
        category: state.category,
        status: state.status,
        range: state.range,
        page: state.page,
        limit: state.limit
      });

      try {
        const response = await fetch('../shared/auth/notifications_api.php?' + params.toString());
        const result = await response.json();
        if (!result.success) throw new Error('Failed to load');
        
        state.list = result.items || [];
        state.total = Number(result.total || 0);
        renderNotifications();
        renderPagination();
      } catch (error) {
        notifListEl.innerHTML = '<div class="empty-state">Unable to load notifications.</div>';
      }
    }

    async function loadCounts() {
      try {
        const response = await fetch('../shared/auth/notifications_api.php?action=stats');
        const result = await response.json();
        if (!result.success) throw new Error('Failed to load stats');

        const total = Number(result.stats.total || 0);
        const unread = Number(result.stats.unread || 0);
        const read = Number(result.stats.read || 0);

        document.getElementById('allCount').textContent = total;
        document.getElementById('unreadCount').textContent = unread;
        document.getElementById('readCount').textContent = read;

        if (headerBadgeEl) {
          headerBadgeEl.textContent = unread;
          headerBadgeEl.style.display = unread > 0 ? 'grid' : 'none';
        }
        if (sidebarBadgeEl) {
          sidebarBadgeEl.textContent = unread;
          sidebarBadgeEl.style.display = unread > 0 ? 'grid' : 'none';
        }
      } catch (error) {
        console.error('Failed to load counts:', error);
      }
    }

    function renderNotifications() {
      if (!state.list.length) {
        notifListEl.innerHTML = '<div class="empty-state">No notifications to display.</div>';
        return;
      }

      notifListEl.innerHTML = state.list.map((item) => {
        const isUnread = Number(item.is_read) === 0;
        const icon = getIconForCategory(item.category || 'System');
        const color = getColorForCategory(item.category || 'System');
        const readClass = isUnread ? 'unread' : 'read';
        const timeAgo = formatTime(item.created_at);
        const reference = item.submission_reference || (item.related_type === 'report' && item.related_id ? `Submission #${item.related_id}` : '');
        const sender = item.sender_name || item.sender_username || 'PO User';
        const priority = item.submission_priority || '';
        const dueDate = item.submission_due_at ? formatDate(item.submission_due_at) : '';

        return `
          <div class="notif-row ${readClass}" data-id="${item.id}" data-url="${escapeHtml(item.action_url || (item.related_type === 'report' && item.related_id ? 'inquiries.php?view=' + item.related_id : ''))}" role="link" tabindex="0" aria-label="Open notification: ${escapeHtml(item.title)}">
            <div class="notif-dot"></div>
            <div class="notif-content">
              <div class="notif-head">
                <h3 class="notif-title">${escapeHtml(item.title)}</h3>
              </div>
              <p class="notif-desc">${escapeHtml(item.message)}</p>
              <div class="notif-footer">
                <span class="notif-badge" style="background:${color};">${escapeHtml(item.category || 'System')}</span>
                <span class="notif-meta"><span>&bull; ${escapeHtml(timeAgo)}</span>${reference ? `<span>|</span><span>${escapeHtml(reference)}</span>` : ''}${priority ? `<span>|</span><span>Priority: <strong class="priority-${escapeHtml(priority.toLowerCase())}">${escapeHtml(priority)}</strong></span>` : ''}${dueDate ? `<span>|</span><span>Due: ${escapeHtml(dueDate)}</span>` : ''}</span>
              </div>
              ${reference ? `<div class="notif-sender">From ${escapeHtml(sender)}</div>` : ''}
            </div>
            <div>
              <button class="notif-menu" type="button" data-id="${item.id}" aria-label="Actions">&#8942;</button>
              <div class="menu-dropdown">
                <button class="menu-item" data-action="mark-read" data-id="${item.id}">${isUnread ? '&#10003; Mark as Read' : '&#9675; Mark as Unread'}</button>
                <button class="menu-item" data-action="delete" data-id="${item.id}" style="border-top:1px solid var(--fo-border); margin-top:4px; padding-top:8px;">Delete</button>
                ${item.action_url ? `<button class="menu-item" data-action="open" data-url="${escapeHtml(item.action_url)}" style="border-top:1px solid var(--fo-border); margin-top:4px; padding-top:8px;">View Details</button>` : ''}
              </div>
            </div>
          </div>
        `;
      }).join('');

      document.querySelectorAll('.notif-menu').forEach((btn) => {
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          const row = btn.closest('.notif-row');
          document.querySelectorAll('.notif-row.menu-open').forEach((r) => {
            if (r !== row) r.classList.remove('menu-open');
          });
          row.classList.toggle('menu-open');
        });
      });

      document.querySelectorAll('.notif-row').forEach((row) => {
        const openNotification = async () => {
          const url = row.dataset.url;
          await markRead(Number(row.dataset.id));
          if (url) window.location.href = url;
        };
        row.addEventListener('click', (event) => {
          if (event.target.closest('.notif-menu, .menu-dropdown')) return;
          openNotification();
        });
        row.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openNotification();
          }
        });
      });

      document.querySelectorAll('.menu-item').forEach((item) => {
        item.addEventListener('click', async (e) => {
          e.stopPropagation();
          const id = Number(item.dataset.id);
          const action = item.dataset.action;

          if (action === 'mark-read') {
            const isCurrentlyRead = document.querySelector(`.notif-row[data-id="${id}"]`).classList.contains('read');
            if (isCurrentlyRead) {
              await markUnread(id);
            } else {
              await markRead(id);
            }
          } else if (action === 'delete') {
            if (confirm('Delete this notification?')) {
              await deleteNotification(id);
            }
          } else if (action === 'open' && item.dataset.url) {
            await markRead(id);
            window.location.href = item.dataset.url;
          }
          document.querySelector(`.notif-row[data-id="${id}"] .notif-menu`).closest('.notif-row').classList.remove('menu-open');
        });
      });

      document.addEventListener('click', () => {
        document.querySelectorAll('.notif-row.menu-open').forEach((r) => r.classList.remove('menu-open'));
      });
    }

    function renderPagination() {
      const totalPages = Math.max(1, Math.ceil(state.total / state.limit));
      const start = state.total === 0 ? 0 : ((state.page - 1) * state.limit) + 1;
      const end = Math.min(state.page * state.limit, state.total);

      document.getElementById('pageStart').textContent = start;
      document.getElementById('pageEnd').textContent = end;
      document.getElementById('pageTotal').textContent = state.total;

      let buttons = `<button class="page-button" data-page="prev" ${state.page <= 1 ? 'disabled' : ''}>&lsaquo;</button>`;
      for (let i = 1; i <= totalPages; i++) {
        buttons += `<button class="page-button ${i === state.page ? 'active' : ''}" data-page="${i}">${i}</button>`;
      }
      buttons += `<button class="page-button" data-page="next" ${state.page >= totalPages ? 'disabled' : ''}>&rsaquo;</button>`;

      document.getElementById('paginationControls').innerHTML = buttons;

      document.querySelectorAll('#paginationControls .page-button').forEach((btn) => {
        btn.addEventListener('click', () => {
          const page = btn.dataset.page;
          if (page === 'prev') {
            state.page = Math.max(1, state.page - 1);
          } else if (page === 'next') {
            state.page = Math.min(totalPages, state.page + 1);
          } else {
            state.page = Number(page);
          }
          loadNotifications();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });
      });
    }

    async function markRead(id) {
      try {
        const response = await fetch('../shared/auth/notifications_api.php?action=mark_read', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: Number(id) })
        });
        const result = await response.json();
        if (result.success) {
          await loadCounts();
          await loadNotifications();
        }
      } catch (error) {
        console.error('Error marking as read:', error);
      }
    }

    async function markUnread(id) {
      try {
        const response = await fetch('../shared/auth/notifications_api.php?action=mark_unread', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: Number(id) })
        });
        const result = await response.json();
        if (result.success) {
          await loadCounts();
          await loadNotifications();
        }
      } catch (error) {
        console.error('Error marking as unread:', error);
      }
    }

    async function deleteNotification(id) {
      try {
        const response = await fetch('../shared/auth/notifications_api.php?action=delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: Number(id) })
        });
        const result = await response.json();
        if (result.success) {
          await loadCounts();
          state.page = 1;
          await loadNotifications();
        }
      } catch (error) {
        console.error('Error deleting notification:', error);
      }
    }

    function formatTime(dateStr) {
      if (!dateStr) return 'Recently';
      const date = new Date(dateStr);
      const now = new Date();
      const diffMs = now - date;
      const diffMins = Math.floor(diffMs / 60000);
      const diffHours = Math.floor(diffMs / 3600000);
      const diffDays = Math.floor(diffMs / 86400000);

      if (diffMins < 1) return 'Just now';
      if (diffMins < 60) return `${diffMins}m ago`;
      if (diffHours < 24) return `${diffHours}h ago`;
      if (diffDays < 7) return `${diffDays}d ago`;
      return date.toLocaleDateString();
    }

    function formatDate(dateStr) {
      const date = new Date(String(dateStr).replace(' ', 'T'));
      return Number.isNaN(date.getTime()) ? String(dateStr) : date.toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    }

    function escapeHtml(text) {
      const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
      return String(text).replace(/[&<>"']/g, (m) => map[m]);
    }

    tabButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        tabButtons.forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
        state.tab = btn.dataset.tab;
        state.page = 1;
        state.status = state.tab === 'all' ? 'all' : state.tab;
        loadNotifications();
      });
    });

    document.getElementById('filterBtn').addEventListener('click', () => {
      document.getElementById('filterModal').classList.add('open');
    });

    document.getElementById('closeFilterBtn').addEventListener('click', () => {
      document.getElementById('filterModal').classList.remove('open');
    });

    document.getElementById('filterModal').addEventListener('click', (e) => {
      if (e.target.id === 'filterModal') {
        document.getElementById('filterModal').classList.remove('open');
      }
    });

    document.getElementById('applyFilterBtn').addEventListener('click', () => {
      state.range = document.getElementById('filterRange').value;
      state.page = 1;
      document.getElementById('filterModal').classList.remove('open');
      loadNotifications();
    });

    document.getElementById('clearFilterBtn').addEventListener('click', () => {
      document.getElementById('filterRange').value = 'all';
      state.range = 'all';
      state.page = 1;
      loadNotifications();
    });

    document.getElementById('markAllReadBtn').addEventListener('click', async () => {
      try {
        const response = await fetch('../shared/auth/notifications_api.php?action=mark_read', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: 0 })
        });
        const result = await response.json();
        if (result.success) {
          await loadCounts();
          await loadNotifications();
        }
      } catch (error) {
        console.error('Error marking all as read:', error);
      }
    });

    document.getElementById('entriesPerPage').addEventListener('change', (e) => {
      state.limit = Number(e.target.value);
      state.page = 1;
      loadNotifications();
    });

    document.getElementById('logout-sidebar').addEventListener('click', () => {
      fetch('../shared/auth/logout.php', { method: 'POST' }).finally(() => {
        window.location.href = '../../index.html';
      });
    });

    document.getElementById('logout-menu').addEventListener('click', () => {
      fetch('../shared/auth/logout.php', { method: 'POST' }).finally(() => {
        window.location.href = '../../index.html';
      });
    });

    document.querySelector('.profile-trigger').addEventListener('click', () => {
      document.querySelector('.profile-wrap').classList.toggle('open');
    });

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.profile-wrap')) {
        document.querySelector('.profile-wrap').classList.remove('open');
      }
    });

    loadCounts();
    loadNotifications();
  </script>
</body>
</html>

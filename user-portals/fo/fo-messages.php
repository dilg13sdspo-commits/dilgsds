
<?php
require_once __DIR__ . '/../shared/auth/db.php';
require_once __DIR__ . '/../shared/auth/middleware.php';
require_role('FO USER');

$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);
$profilePhoto = get_user_profile_photo($pdo, $userId);
$userName = $_SESSION['username'] ?? 'FO User';
$officeName = $_SESSION['office_name'] ?? 'Field Office';

function ensure_message_table(PDO $pdo): void {
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
    } catch (Exception $e) {
        // Column already exists, ignore
    }
    
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'system' AFTER category");
    } catch (Exception $e) {
        // Column already exists, ignore
    }
    
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN related_type VARCHAR(50) NULL AFTER is_read");
    } catch (Exception $e) {
        // Column already exists, ignore
    }
    
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN related_id INT NULL AFTER related_type");
    } catch (Exception $e) {
        // Column already exists, ignore
    }
    
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN action_url VARCHAR(500) NULL AFTER related_id");
    } catch (Exception $e) {
        // Column already exists, ignore
    }
}

function seed_fo_messages(PDO $pdo, int $userId): void {
    $count = (int) $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid')->execute([':uid' => $userId]);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid');
    $stmt->execute([':uid' => $userId]);
    if ((int)$stmt->fetchColumn() > 0) return;

    $rows = [
        ['System Notification', 'Welcome to DILG Field Operations Dashboard. Track your inquiries and access important documents here.', 'System', 'system', 0, 'system', 0, 'dashboard.php', '2026-08-17 10:00:00'],
        ['Inquiry Status Update', 'Your inquiry "Building Permit Request" has been updated. Please check the status for more details.', 'Inquiry', 'inquiry', 0, 'inquiry', 101, 'inquiries.php?view=101', '2026-08-16 14:30:00'],
        ['New Document Available', 'A new program guide has been uploaded. Check the Documents section for more information.', 'Document', 'document', 1, 'document', 205, '../../programs-and-services.html', '2026-08-15 09:15:00'],
        ['Reminder', 'Don\'t forget to submit your monthly report by the end of this week.', 'System', 'reminder', 1, 'reminder', 0, 'dashboard.php', '2026-08-14 15:00:00'],
        ['Document Review', 'Your submitted document has been reviewed and is awaiting final approval.', 'Document', 'document', 0, 'document', 221, '../../programs-and-services.html', '2026-08-13 18:10:00'],
        ['Office Announcement', 'There will be a general staff meeting tomorrow at 9:00 AM in the conference hall.', 'System', 'announcement', 1, 'announcement', 0, '../../news.html', '2026-08-12 11:00:00'],
    ];

    $insert = $pdo->prepare('INSERT INTO notifications (user_id, title, message, category, type, is_read, related_type, related_id, action_url, created_at) VALUES (:user_id, :title, :message, :category, :type, :is_read, :related_type, :related_id, :action_url, :created_at)');
    foreach ($rows as $row) {
        $insert->execute([
            ':user_id' => $userId,
            ':title' => $row[0],
            ':message' => $row[1],
            ':category' => $row[2],
            ':type' => $row[3],
            ':is_read' => $row[4],
            ':related_type' => $row[5],
            ':related_id' => $row[6],
            ':action_url' => $row[7],
            ':created_at' => $row[8],
        ]);
    }
}

ensure_message_table($pdo);
seed_fo_messages($pdo, $userId);

$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
$stmt->execute([':uid' => $userId]);
$unreadCount = (int)$stmt->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Messages - DILG FO Dashboard</title>
  <style>
    :root {
      --fo-green-900: #0b3f34;
      --fo-green-800: #0f4d3d;
      --fo-green-700: #1d6050;
      --fo-green-600: #2c7b67;
      --fo-bg: #edf3f1;
      --fo-surface: #ffffff;
      --fo-soft: #f5f8f7;
      --fo-border: rgba(15, 51, 38, 0.12);
      --fo-text: #1d2a2b;
      --fo-muted: #64747a;
      --fo-shadow: 0 12px 28px rgba(18, 32, 28, 0.08);
      --fo-red: #e65d5d;
      --fo-green: #2fb778;
      --fo-blue: #2d80f2;
      --fo-yellow: #f0b648;
      --fo-purple: #8c67d4;
      --fo-gray: #6b7280;
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
    button, input, textarea { font: inherit; }

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
    .nav-icon svg, .control-icon, .messages-icon svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; vertical-align: middle; }
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
    .page-title h1 { margin: 0; font-size: clamp(1.4rem, 2vw, 2.05rem); font-weight: 700; line-height: 1.2; color: var(--fo-green-900); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .page-title p { margin: 6px 0 0; color: var(--fo-muted); font-size: 0.92rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
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
    .profile-dropdown a { display: block; padding: 10px 12px; border-radius: 8px; color: var(--fo-text); font-weight: 600; }
    .profile-dropdown a:hover { background: #f3f8f7; color: var(--fo-green-800); }

    .content { padding: 22px 20px 24px; display: flex; flex-direction: column; gap: 18px; }
    .messages-shell {
      background: rgba(255,255,255,0.96); border: 1px solid var(--fo-border); border-radius: 16px; box-shadow: var(--fo-shadow); overflow: hidden;
    }
    .messages-header {
      display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 24px 20px 18px; border-bottom: 1px solid var(--fo-border);
    }
    .messages-title-wrap { display: flex; align-items: center; gap: 12px; }
    .messages-icon { width: 46px; height: 46px; border-radius: 14px; background: rgba(22,123,92,0.08); color: var(--fo-green-700); display: grid; place-items: center; font-size: 1.5rem; }
    .messages-icon svg { width: 24px; height: 24px; }
    .messages-title-wrap h2 { margin: 0; font-size: clamp(1.9rem, 2vw, 2.4rem); color: var(--fo-green-900); }
    .messages-title-wrap p { margin: 4px 0 0; color: var(--fo-muted); font-size: 0.95rem; }
    .mark-all-btn {
      border: 1px solid var(--fo-border); background: #fff; color: var(--fo-green-800); border-radius: 10px; font-weight: 700; padding: 10px 16px; cursor: pointer;
    }
    .mark-all-btn:hover { background: #f4faf8; }

    .tabs-bar {
      display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding: 16px 18px 0;
    }
    .tab-btn {
      border: 1px solid var(--fo-border); background: #fff; color: var(--fo-muted); border-radius: 9px; padding: 8px 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
    }
    .tab-btn.active {
      background: #edf6f1; border-color: rgba(15,77,61,0.25); color: var(--fo-green-800);
    }
    .tab-count {
      display:inline-grid; place-items:center; min-width: 20px; height: 20px; border-radius: 999px; padding: 0 6px; background: rgba(15,77,61,0.08); font-size: 0.7rem; color: var(--fo-green-800);
    }

    .messages-layout {
      display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(300px, 0.9fr); gap: 0; border-top: 1px solid var(--fo-border); margin-top: 12px;
    }
    .message-list-panel {
      border-right: 1px solid var(--fo-border); background: #fff;
    }
    .message-list {
      display: flex; flex-direction: column;
      min-height: 480px;
    }
    .message-item {
      display: flex; align-items: center; gap: 12px; padding: 16px 18px; border-bottom: 1px solid var(--fo-border); cursor: pointer; transition: background 0.2s ease;
    }
    .message-item:hover { background: #f8faf9; }
    .message-item.active { background: #f3faf6; }
    .message-item.unread { background: #f9fbfa; }
    .message-avatar {
      width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; background: rgba(24,54,48,0.06); font-size: 1.05rem; flex-shrink: 0;
    }
    .message-main { flex: 1; min-width: 0; }
    .message-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 4px; }
    .message-title { margin: 0; font-size: 1.02rem; color: var(--fo-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .status-pill {
      display:inline-flex; align-items:center; justify-content:center; border-radius: 999px; background: rgba(15,77,61,0.08); color: var(--fo-green-800); font-size: 0.68rem; font-weight: 700; padding: 4px 8px;
    }
    .status-pill.unread { background: rgba(227,65,65,0.1); color: #a93a3a; }
    .message-preview {
      margin: 0; color: var(--fo-muted); font-size: 0.9rem; line-height: 1.45; max-height: 2.7em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    }
    .message-meta { display: flex; align-items: center; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
    .meta-tag {
      display: inline-block; border-radius: 999px; padding: 4px 8px; font-size: 0.7rem; font-weight: 700; color: #fff;
    }
    .meta-time { font-size: 0.75rem; color: var(--fo-muted); }
    .message-item.unread .message-title { font-weight: 800; }
    .message-item .chevron { color: var(--fo-muted); font-size: 1.2rem; }

    .detail-panel { background: #fff; }
    .detail-card { padding: 22px 20px; min-height: 480px; display: flex; flex-direction: column; }
    .detail-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; }
    .detail-icon { width: 48px; height: 48px; border-radius: 14px; background: rgba(15,77,61,0.08); display: grid; place-items: center; font-size: 1.4rem; color: var(--fo-green-800); }
    .detail-main { flex: 1; }
    .detail-main h3 { margin: 0; font-size: 1.15rem; color: var(--fo-text); }
    .detail-sub { margin-top: 8px; color: var(--fo-muted); font-size: 0.8rem; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .detail-sub .status-pill { font-size: 0.68rem; }
    .detail-body {
      margin-top: 20px; border-top: 1px solid var(--fo-border); padding-top: 20px; color: var(--fo-text); line-height: 1.7; font-size: 0.96rem;
    }
    .detail-body p { margin: 0 0 18px; }
    .detail-box {
      background: #f7faf9; border: 1px solid var(--fo-border); border-radius: 12px; padding: 14px; color: var(--fo-text);
    }
    .detail-box strong { color: var(--fo-green-800); }
    .detail-actions {
      margin-top: auto; display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding-top: 20px; border-top: 1px solid var(--fo-border); margin-top: 22px;
    }
    .primary-action, .secondary-action, .danger-action {
      border-radius: 10px; padding: 10px 14px; font-weight: 700; cursor: pointer; border: 1px solid var(--fo-border);
    }
    .primary-action { background: var(--fo-green-900); color: #fff; border: none; }
    .secondary-action { background: #fff; color: var(--fo-green-800); }
    .danger-action { background: #fff; color: #b84040; }
    .reply-box {
      margin-top: 20px; display: none; }
    .reply-box textarea {
      width: 100%; border: 1px solid var(--fo-border); border-radius: 12px; min-height: 100px; padding: 12px; resize: vertical; color: var(--fo-text); background: #fff;
    }
    .reply-box.open { display: block; }

    .empty-state {
      display: grid; place-items: center; min-height: 220px; color: var(--fo-muted); font-size: 0.95rem; text-align: center; padding: 20px;
    }
    .pagination {
      display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 14px 18px 18px; border-top: 1px solid var(--fo-border); background: #fff;
    }
    .pagination .page-info { color: var(--fo-muted); font-size: 0.82rem; }
    .page-controls { display:flex; align-items:center; gap:8px; }
    .page-button {
      width: 34px; height: 34px; border: 1px solid var(--fo-border); border-radius: 9px; background: #fff; color: var(--fo-green-800); font-weight: 700; cursor:pointer;
    }
    .page-button.active { background: var(--fo-green-900); color: #fff; border-color: var(--fo-green-900); }
    .page-button:disabled { opacity: 0.45; cursor: not-allowed; }

    @media (max-width: 980px) {
      .layout { flex-direction: column; }
      .sidebar { width: 100%; }
      .nav { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .messages-layout { grid-template-columns: 1fr; }
      .message-list-panel { border-right: none; border-bottom: 1px solid var(--fo-border); }
      .topbar { flex-wrap: wrap; }
      .topbar-right { width: 100%; justify-content: space-between; }
      .search-box { width: 100%; }
    }

    @media (max-width: 560px) {
      .content { padding: 14px; }
      .messages-header { flex-direction: column; align-items: flex-start; }
      .tabs-bar { padding-left: 12px; padding-right: 12px; }
      .tab-btn { flex: 1 1 calc(50% - 8px); justify-content: center; }
      .detail-actions { flex-wrap: wrap; justify-content: flex-start; }
      .message-item { padding-left: 12px; padding-right: 12px; }
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
        <button class="nav-item" type="button" onclick="location.href='../../programs-and-services.html'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v10M8 10l4 4 4-4M5 19h14"/></svg></span><span class="nav-label">Downloads</span></span></button>
        <button class="nav-item" type="button" onclick="location.href='notifications.php'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></span><span class="nav-label">Notifications</span></span><span class="nav-badge"><?= htmlspecialchars((string)$unreadCount) ?></span></button>
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
          <button class="menu-btn" type="button" aria-label="Toggle menu">?</button>
          <div class="page-title">
            <h1>FO User Dashboard</h1>
            <p>Welcome back! Heres your field office performance at a glance.</p>
          </div>
        </div>

        <div class="topbar-right">
          <label class="search-box" aria-label="Search dashboard">
            <span><svg class="control-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg></span>
            <input id="globalSearch" type="search" placeholder="Search inquiries, documents..." />
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
        <section class="messages-shell">
          <div class="messages-header">
            <div class="messages-title-wrap">
              <div class="messages-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6h14v10H8l-3 3zM8 10h8M8 13h5"/></svg></div>
              <div>
                <h2>Messages</h2>
                <p>Stay updated with important notifications and messages.</p>
              </div>
            </div>
            <button id="markAllReadBtn" class="mark-all-btn" type="button">Mark all as read</button>
          </div>

          <div class="tabs-bar">
            <button class="tab-btn active" type="button" data-tab="all">All <span class="tab-count" id="allCount">0</span></button>
            <button class="tab-btn" type="button" data-tab="unread">Unread <span class="tab-count" id="unreadCount">0</span></button>
            <button class="tab-btn" type="button" data-tab="system">System</button>
            <button class="tab-btn" type="button" data-tab="inquiry">Inquiries</button>
            <button class="tab-btn" type="button" data-tab="document">Documents</button>
          </div>

          <div class="messages-layout">
            <div class="message-list-panel">
              <div id="messageList" class="message-list"></div>
              <div class="pagination">
                <div id="pageInfo" class="page-info">Showing 0 to 0 of 0 messages</div>
                <div id="paginationControls" class="page-controls"></div>
              </div>
            </div>

            <aside class="detail-panel">
              <div id="detailPanel" class="detail-card">
                <div class="empty-state">Select a message to view its details.</div>
              </div>
            </aside>
          </div>
        </section>
      </div>
    </main>
  </div>

  <script>
    const state = {
      tab: 'all',
      status: 'all',
      page: 1,
      limit: 4,
      selectedId: null,
      search: '',
      total: 0,
      list: []
    };

    const tabs = document.querySelectorAll('.tab-btn');
    const messageListEl = document.getElementById('messageList');
    const detailPanelEl = document.getElementById('detailPanel');
    const pageInfoEl = document.getElementById('pageInfo');
    const pageControlsEl = document.getElementById('paginationControls');
    const unreadBadgeEl = document.getElementById('headerBadge');
    const sidebarBadgeEl = document.getElementById('sidebarBadge');

    function getCategoryColors(category) {
      const colors = {
        System: '#2ca66a',
        Inquiry: '#2d80f2',
        Document: '#8c67d4',
        Reminder: '#f0b648',
        Announcement: '#f0932b'
      };
      return colors[category] || '#6b7280';
    }

    function getCategoryIcon(category) {
      const icons = {
        System: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11 16 6v12L4 13zM4 11v2M16 9h3a2 2 0 0 1 0 4h-3"/></svg>',
        Inquiry: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6M9 17h6"/></svg>',
        Document: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6M9 17h6"/></svg>',
        Reminder: '?',
        Announcement: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11 16 6v12L4 13zM4 11v2M16 9h3a2 2 0 0 1 0 4h-3"/></svg>'
      };
      return icons[category] || '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6h14v10H8l-3 3zM8 10h8M8 13h5"/></svg>';
    }

    function getTabQuery() {
      const tab = state.tab;
      if (tab === 'all') return { category: 'all', status: 'all' };
      if (tab === 'unread') return { category: 'all', status: 'unread' };
      if (tab === 'system') return { category: 'System', status: 'all' };
      if (tab === 'inquiry') return { category: 'Inquiry', status: 'all' };
      if (tab === 'document') return { category: 'Document', status: 'all' };
      return { category: 'all', status: 'all' };
    }

    async function loadMessages() {
      const filters = getTabQuery();
      const params = new URLSearchParams({
        action: 'list',
        page: state.page,
        limit: state.limit,
        status: filters.status,
        category: filters.category,
        tab: state.tab,
        search: state.search || ''
      });

      try {
        const response = await fetch('../shared/auth/notifications_api.php?' + params.toString());
        const result = await response.json();
        if (!result.success) {
          throw new Error('Unreadable data');
        }
        state.list = result.items || [];
        state.total = Number(result.total || 0);
        renderMessages();
        renderPagination();
      } catch (error) {
        messageListEl.innerHTML = '<div class="empty-state">Unable to load messages right now.</div>';
        detailPanelEl.innerHTML = '<div class="empty-state">No message selected.</div>';
      }
    }

    function updateCounts() {
      // Fetch updated stats and refresh the sidebar/top badges.
      fetch('../shared/auth/notifications_api.php?action=stats')
        .then((res) => res.json())
        .then((data) => {
          const unread = Number((data && data.stats && data.stats.unread) || 0);
          const total = Number((data && data.stats && data.stats.total) || 0);
          if (unreadBadgeEl) unreadBadgeEl.textContent = unread;
          if (sidebarBadgeEl) sidebarBadgeEl.textContent = unread;
          if (unreadBadgeEl) unreadBadgeEl.style.display = unread > 0 ? 'grid' : 'none';
          if (sidebarBadgeEl) sidebarBadgeEl.style.display = unread > 0 ? 'grid' : 'none';
          document.getElementById('allCount').textContent = total;
          document.getElementById('unreadCount').textContent = unread;
        })
        .catch(() => {});
    }

    function renderMessages() {
      if (!state.list.length) {
        messageListEl.innerHTML = '<div class="empty-state">No messages match this filter.</div>';
        detailPanelEl.innerHTML = '<div class="empty-state">No message selected.</div>';
        return;
      }

      if (!state.selectedId && state.list[0]) {
        state.selectedId = Number(state.list[0].id);
      }

      messageListEl.innerHTML = state.list.map((item) => {
        const activeClass = Number(item.id) === Number(state.selectedId) ? 'active' : '';
        const unreadClass = Number(item.is_read) === 0 ? 'unread' : '';
        const pillText = Number(item.is_read) === 0 ? 'Unread' : 'Read';
        const color = getCategoryColors(item.category || 'System');
        return `
          <div class="message-item ${activeClass} ${unreadClass}" data-id="${item.id}" tabindex="0">
            <div class="message-avatar">${getCategoryIcon(item.category || 'System')}</div>
            <div class="message-main">
              <div class="message-head">
                <h3 class="message-title">${item.title}</h3>
                <span class="status-pill ${Number(item.is_read) === 0 ? 'unread' : ''}">${pillText}</span>
              </div>
              <p class="message-preview">${item.message}</p>
              <div class="message-meta">
                <span class="meta-tag" style="background:${color};">${item.category || 'System'}</span>
                <span class="meta-time">${formatMessageTime(item.created_at)}</span>
              </div>
            </div>
            <div class="chevron"></div>
          </div>
        `;
      }).join('');

      messageListEl.querySelectorAll('.message-item').forEach((node) => {
        node.addEventListener('click', async () => {
          const itemId = Number(node.dataset.id);
          state.selectedId = itemId;
          renderMessages();
          await markRead(itemId);
          renderDetail(itemId);
        });
      });

      renderDetail(state.selectedId || Number(state.list[0].id));
    }

    function renderDetail(id) {
      const item = state.list.find((message) => Number(message.id) === Number(id));
      if (!item) {
        detailPanelEl.innerHTML = '<div class="empty-state">No message selected.</div>';
        return;
      }

      const isRead = Number(item.is_read) === 1;
      const color = getCategoryColors(item.category || 'System');
      const detailHtml = `
        <div class="detail-card">
          <div class="detail-top">
            <div class="detail-icon">${getCategoryIcon(item.category || 'System')}</div>
            <div class="detail-main">
              <h3>${item.title}</h3>
              <div class="detail-sub">
                <span>${formatMessageTime(item.created_at)}</span>
                <span class="meta-tag" style="background:${color};">${item.category || 'System'}</span>
                <span class="status-pill ${isRead ? '' : 'unread'}">${isRead ? 'Read' : 'Unread'}</span>
              </div>
            </div>
          </div>
          <div class="detail-body">
            <p>${item.message}</p>
            <div class="detail-box">
              <strong>Message details</strong><br>
              Source: ${item.related_type || 'System'}<br>
              Action: ${item.action_url ? '<a href="' + item.action_url + '" style="color:#1b6fb6;">Open related page</a>' : 'None'}
            </div>
            <div class="reply-box" id="replyBox">
              <textarea id="replyInput" placeholder="Write a reply..."></textarea>
            </div>
          </div>
          <div class="detail-actions">
            <button class="primary-action" type="button" id="primaryActionBtn">${isRead ? 'Mark as Read' : 'Mark as Read'}</button>
            <button class="secondary-action" type="button" id="replyBtn">Reply</button>
            <button class="danger-action" type="button" id="deleteBtn">Delete</button>
          </div>
        </div>
      `;
      detailPanelEl.innerHTML = detailHtml;

      document.getElementById('primaryActionBtn').addEventListener('click', () => markRead(Number(item.id)));
      document.getElementById('replyBtn').addEventListener('click', () => {
        const box = document.getElementById('replyBox');
        box.classList.toggle('open');
      });
      document.getElementById('deleteBtn').addEventListener('click', () => deleteMessage(Number(item.id)));
    }

    function renderPagination() {
      const totalPages = Math.max(1, Math.ceil(state.total / state.limit));
      const pageButtons = [];
      for (let pageNumber = 1; pageNumber <= totalPages; pageNumber++) {
        pageButtons.push(`<button class="page-button ${pageNumber === state.page ? 'active' : ''}" type="button" data-page="${pageNumber}">${pageNumber}</button>`);
      }

      pageInfoEl.textContent = state.total === 0 ? 'Showing 0 to 0 of 0 messages' : `Showing ${((state.page - 1) * state.limit) + 1} to ${Math.min(state.page * state.limit, state.total)} of ${state.total} messages`;
      pageControlsEl.innerHTML = `
        <button class="page-button" type="button" data-page="prev" ${state.page <= 1 ? 'disabled' : ''}></button>
        ${pageButtons.join('')}
        <button class="page-button" type="button" data-page="next" ${state.page >= totalPages ? 'disabled' : ''}></button>
      `;

      pageControlsEl.querySelectorAll('.page-button').forEach((button) => {
        button.addEventListener('click', () => {
          const value = button.dataset.page;
          if (value === 'prev') {
            state.page = Math.max(1, state.page - 1);
          } else if (value === 'next') {
            state.page = Math.min(totalPages, state.page + 1);
          } else {
            state.page = Number(value);
          }
          loadMessages();
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
          updateCounts();
          loadMessages();
        }
      } catch (error) {
        console.error('Could not mark message read', error);
      }
    }

    async function deleteMessage(id) {
      if (!window.confirm('Delete this message?')) return;
      try {
        const response = await fetch('../shared/auth/notifications_api.php?action=delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: Number(id) })
        });
        const result = await response.json();
        if (result.success) {
          state.selectedId = null;
          updateCounts();
          loadMessages();
        }
      } catch (error) {
        console.error('Could not delete message', error);
      }
    }

    function formatMessageTime(value) {
      if (!value) return 'Recently';
      const date = new Date(value);
      const now = new Date();
      const diffHours = (now - date) / 3600000;
      if (diffHours < 24) {
        return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
      }
      if (diffHours < 48) {
        return 'Yesterday';
      }
      return date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
    }

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        tabs.forEach((item) => item.classList.toggle('active', item === tab));
        state.tab = tab.dataset.tab;
        state.page = 1;
        loadMessages();
      });
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
          updateCounts();
          loadMessages();
        }
      } catch (error) {
        console.error('Could not mark all messages read', error);
      }
    });

    document.getElementById('globalSearch').addEventListener('input', (event) => {
      state.search = (event.target.value || '').trim();
      state.page = 1;
      loadMessages();
    });

    document.getElementById('logout-sidebar').addEventListener('click', () => {
      fetch('../shared/auth/logout.php', { method: 'POST' }).finally(() => { window.location.href = '../../index.html'; });
    });
    document.getElementById('logout-menu').addEventListener('click', () => {
      fetch('../shared/auth/logout.php', { method: 'POST' }).finally(() => { window.location.href = '../../index.html'; });
    });
    document.querySelector('.profile-trigger').addEventListener('click', () => {
      const wrap = document.querySelector('.profile-wrap');
      wrap.classList.toggle('open');
    });

    updateCounts();
    loadMessages();
  </script>
</body>
</html>

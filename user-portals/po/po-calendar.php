<?php
require_once __DIR__ . '/../shared/auth/middleware.php';
require_once __DIR__ . '/../shared/auth/po_icons.php';
require_role('PO USER');

$pdo = db_connect();
$profilePhoto = get_user_profile_photo($pdo, (int)($_SESSION['user_id'] ?? 0));

function ensure_calendar_tables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS calendar_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        office_id INT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        start_at DATETIME NOT NULL,
        end_at DATETIME NOT NULL,
        location VARCHAR(255) NULL,
        category VARCHAR(50) NOT NULL DEFAULT 'Meetings',
        organizer VARCHAR(255) NULL,
        attendees TEXT NULL,
        reminder VARCHAR(50) NULL,
        recurrence VARCHAR(50) NULL,
        source VARCHAR(50) NOT NULL DEFAULT 'local',
        google_event_id VARCHAR(255) NULL,
        calendar_id VARCHAR(255) NULL,
        sync_status VARCHAR(50) NOT NULL DEFAULT 'pending',
        last_synced_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_events (user_id, start_at),
        INDEX idx_google_event (google_event_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS google_calendar_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        access_token TEXT NOT NULL,
        refresh_token TEXT NULL,
        expires_at DATETIME NULL,
        scope VARCHAR(255) NULL,
        calendar_id VARCHAR(255) NULL,
        connected_at DATETIME NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS calendar_sync_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        status VARCHAR(50) NOT NULL,
        message TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

ensure_calendar_tables($pdo);

$userName = $_SESSION['username'] ?? 'Provincial User';
$userId = (int)($_SESSION['user_id'] ?? 0);

function calendar_count($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

try {
    $notificationCount = calendar_count($pdo, "SELECT COUNT(*) FROM audit_logs WHERE user_id = :uid AND action NOT IN ('login', 'logout', 'unauthorized_access') AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", [':uid' => $userId]);
} catch (Throwable $e) {
    $notificationCount = 0;
}

try {
    $googleTokenStmt = $pdo->prepare('SELECT * FROM google_calendar_tokens WHERE user_id = :uid LIMIT 1');
    $googleTokenStmt->execute([':uid' => $userId]);
    $googleConnected = $googleTokenStmt->fetch() !== false;
} catch (Throwable $e) {
    $googleConnected = false;
}

$eventCategories = ['Meetings', 'Consultations', 'Programs', 'Deadlines', 'Trainings', 'Events'];
$categoryColors = [
  'Meetings' => '#5a8dee',
  'Consultations' => '#55b68a',
  'Programs' => '#f2b63d',
  'Deadlines' => '#d65b5b',
  'Trainings' => '#9d75ee',
  'Events' => '#51b7d7',
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Calendar - DILG PO Dashboard</title>
  <style>
    :root {
      --po-sidebar: #5c2d17;
      --po-sidebar-deep: #3d1f11;
      --po-bg: #f3f7fb;
      --po-surface: #ffffff;
      --po-text: #1f2e3d;
      --po-muted: #6d7a88;
      --po-border: #e4ebf2;
      --po-accent: #2f7ef7;
      --po-accent-soft: #edf5ff;
      --po-blue-strong: #2f7ef7;
      --po-blue-soft: #eaf3ff;
      --po-green: #56b88c;
      --po-green-soft: #eafaf1;
      --po-red: #d65b5b;
      --po-red-soft: #ffe9ec;
      --po-purple: #9d75ee;
      --po-purple-soft: #f4efff;
      --po-gold: #f2b63d;
      --po-gold-soft: #fff3d6;
      --po-cyan: #51b7d7;
      --po-cyan-soft: #ebfbff;
      --po-shadow: 0 12px 28px rgba(31, 42, 61, 0.06);
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

    .po-shell,
    .shell {
      display: flex;
      min-height: 100vh;
      background: linear-gradient(180deg, #f6f8fc 0%, #f3f6fb 100%);
    }

    .po-sidebar,
    .sidebar {
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
      padding: 6px 8px 18px;
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
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
    }

    .brand-copy { line-height: 1.15; }
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

    .nav-main { display: inline-flex; align-items: center; gap: 10px; }
    .nav-icon { width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; }
    .nav-badge {
      min-width: 22px;
      height: 22px;
      border-radius: 999px;
      background: #d88a2d;
      color: #fff;
      font-size: 0.72rem;
      font-weight: 800;
      display: grid;
      place-items: center;
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
      color: rgba(255,255,255,0.88);
      font-size: 0.82rem;
      font-weight: 600;
      padding: 10px 8px 0;
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
      flex: 1;
    }

    .title-mark {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      background: var(--po-accent);
      color: #fff;
      display: grid;
      place-items: center;
      font-weight: 800;
      box-shadow: var(--po-shadow);
    }

    .title-copy strong {
      display: block;
      font-size: 0.92rem;
      color: var(--po-text);
      line-height: 1.2;
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
      gap: 18px;
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
      outline: none;
      color: var(--po-text);
      font-size: 0.92rem;
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
    }

    .notif .counter {
      position: absolute;
      top: -5px;
      right: -4px;
      min-width: 18px;
      height: 18px;
      border-radius: 50%;
      background: #f05050;
      color: #fff;
      display: grid;
      place-items: center;
      font-size: 0.65rem;
      font-weight: 700;
      padding: 0 4px;
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
      font-size: 0.82rem;
    }

    .profile-meta {
      display: flex;
      flex-direction: column;
      line-height: 1.2;
      text-align: left;
    }

    .profile-meta strong { font-size: 0.82rem; }
    .profile-meta span { font-size: 0.68rem; color: var(--po-muted); }

    .page-body {
      padding: 24px 20px 30px;
    }

    .page-header {
      margin-bottom: 18px;
    }

    .page-title-wrap {
      display: flex;
      align-items: center;
      gap: 16px;
      background: rgba(255,255,255,0.78);
      border: 1px solid var(--po-border);
      border-radius: 16px;
      padding: 18px 20px;
      box-shadow: 0 8px 18px rgba(31, 42, 61, 0.04);
    }

    .page-icon {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      background: #edf5ff;
      color: var(--po-accent);
      display: grid;
      place-items: center;
      font-size: 1.7rem;
      border: 1px solid rgba(47, 126, 247, 0.12);
    }

    .page-title-wrap h1 {
      margin: 0;
      font-size: clamp(2rem, 3.2vw, 2.6rem);
      color: var(--po-text);
      letter-spacing: -0.02em;
    }

    .breadcrumb {
      margin-top: 6px;
      font-size: 0.82rem;
      color: var(--po-muted);
    }

    .breadcrumb strong {
      font-weight: 700;
      color: var(--po-text);
    }

    .calendar-shell {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 290px;
      gap: 18px;
      align-items: start;
    }

    .main-panel {
      background: var(--po-surface);
      border: 1px solid var(--po-border);
      border-radius: 16px;
      box-shadow: var(--po-shadow);
      overflow: hidden;
    }

    .calendar-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 14px 16px;
      border-bottom: 1px solid var(--po-border);
      background: #fff;
    }

    .calendar-controls {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .nav-btn,
    .view-btn,
    .primary-btn,
    .ghost-btn {
      border: 1px solid var(--po-border);
      border-radius: 10px;
      background: #fff;
      color: var(--po-text);
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .nav-btn {
      width: 34px;
      height: 34px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.15rem;
    }

    .today-btn {
      padding: 8px 12px;
      background: #f8fafc;
      color: var(--po-text);
      border: 1px solid var(--po-border);
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
    }

    .month-label {
      font-weight: 700;
      font-size: 1.1rem;
      color: var(--po-text);
      min-width: 150px;
      text-align: center;
    }

    .view-switch {
      display: flex;
      align-items: center;
      gap: 8px;
      background: #f5f7fb;
      padding: 4px;
      border-radius: 10px;
      border: 1px solid var(--po-border);
    }

    .view-btn {
      border: 0;
      padding: 8px 12px;
      border-radius: 8px;
      color: var(--po-muted);
      background: transparent;
      font-weight: 600;
    }

    .view-btn.active {
      background: var(--po-accent);
      color: #fff;
      box-shadow: 0 8px 18px rgba(47, 126, 247, 0.2);
    }

    .primary-btn {
      background: var(--po-accent);
      border-color: var(--po-accent);
      color: #fff;
      padding: 9px 14px;
      font-weight: 700;
      box-shadow: 0 8px 18px rgba(47, 126, 247, 0.18);
    }

    .ghost-btn {
      background: #f8fafc;
      padding: 9px 12px;
      font-weight: 600;
    }

    .calendar-grid {
      padding: 12px 14px 18px;
    }

    .calendar-weekdays,
    .calendar-days {
      display: grid;
      grid-template-columns: repeat(7, minmax(0, 1fr));
      gap: 0;
      border-left: 1px solid var(--po-border);
      border-top: 1px solid var(--po-border);
    }

    .weekday {
      padding: 14px 8px;
      text-align: center;
      font-size: 0.9rem;
      font-weight: 700;
      color: var(--po-muted);
      border-right: 1px solid var(--po-border);
      border-bottom: 1px solid var(--po-border);
      background: #f9fbff;
    }

    .day-cell {
      position: relative;
      min-height: 110px;
      border-right: 1px solid var(--po-border);
      border-bottom: 1px solid var(--po-border);
      background: #fff;
      padding: 8px 8px 6px;
      cursor: pointer;
      overflow: hidden;
    }

    .day-cell.muted { background: #fbfcfe; color: #a1a9b3; }
    .day-cell.today {
      background: #f3f9ff;
      box-shadow: inset 0 0 0 1px rgba(47, 126, 247, 0.15);
    }

    .day-number {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 24px;
      height: 24px;
      border-radius: 50%;
      font-size: 0.8rem;
      font-weight: 700;
      margin-bottom: 6px;
    }

    .day-cell.today .day-number {
      background: var(--po-accent);
      color: #fff;
    }

    .event-pill {
      display: block;
      margin-top: 4px;
      font-size: 0.68rem;
      border-radius: 6px;
      padding: 4px 6px;
      line-height: 1.25;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      color: #fff;
      font-weight: 600;
    }

    .event-pill.meetings { background: rgba(90, 141, 238, 0.9); }
    .event-pill.consultations { background: rgba(86, 184, 140, 0.9); }
    .event-pill.programs { background: rgba(242, 182, 61, 0.9); }
    .event-pill.deadlines { background: rgba(214, 91, 91, 0.9); }
    .event-pill.trainings { background: rgba(157, 117, 238, 0.9); }
    .event-pill.events { background: rgba(81, 183, 215, 0.9); }

    .right-panel {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .panel {
      background: var(--po-surface);
      border: 1px solid var(--po-border);
      border-radius: 16px;
      box-shadow: var(--po-shadow);
      overflow: hidden;
    }

    .panel-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 16px 14px 12px;
      border-bottom: 1px solid var(--po-border);
    }

    .panel-head h3 {
      margin: 0;
      font-size: 1.05rem;
      color: var(--po-text);
    }

    .link-btn {
      border: 0;
      background: transparent;
      color: var(--po-accent);
      font-weight: 700;
      cursor: pointer;
    }

    .events-list {
      padding: 10px 14px 14px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .mini-event {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 8px 0;
      border-bottom: 1px solid var(--po-border);
    }

    .mini-event:last-child { border-bottom: 0; }

    .mini-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      margin-top: 8px;
      flex-shrink: 0;
    }

    .mini-event strong {
      display: block;
      margin-bottom: 2px;
      font-size: 0.9rem;
      color: var(--po-text);
    }

    .mini-event small {
      display: block;
      color: var(--po-muted);
      line-height: 1.45;
    }

    .filter-card {
      padding: 12px 14px 14px;
    }

    .filter-card label {
      display: block;
      font-weight: 700;
      color: var(--po-text);
      margin-bottom: 10px;
      font-size: 0.9rem;
    }

    .filter-select {
      width: 100%;
      padding: 9px 10px;
      border: 1px solid var(--po-border);
      border-radius: 10px;
      background: #fff;
      color: var(--po-text);
      margin-bottom: 14px;
    }

    .filter-list {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-bottom: 14px;
    }

    .check-item {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--po-text);
      font-size: 0.9rem;
    }

    .check-item input { accent-color: var(--po-accent); }

    .apply-btn {
      width: 100%;
      padding: 11px 12px;
      background: var(--po-accent);
      color: #fff;
      border: 0;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 700;
    }

    .legend {
      padding: 12px 14px 14px;
    }

    .legend-list {
      display: flex;
      flex-wrap: wrap;
      gap: 10px 12px;
      margin-top: 10px;
    }

    .legend-item {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.8rem;
      color: var(--po-text);
    }

    .legend-color {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      display: inline-block;
    }

    .sync-box {
      background: var(--po-surface);
      border: 1px solid var(--po-border);
      border-radius: 16px;
      box-shadow: var(--po-shadow);
      padding: 18px 16px;
    }

    .sync-box h3 {
      margin: 0 0 10px;
      font-size: 1.05rem;
      color: var(--po-text);
    }

    .sync-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 12px 0;
      border-top: 1px solid var(--po-border);
    }

    .sync-row:first-of-type { border-top: 0; }

    .sync-service {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 600;
    }

    .sync-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 6px 9px;
      border-radius: 999px;
      font-size: 0.72rem;
      font-weight: 700;
      border: 1px solid transparent;
    }

    .sync-badge.connected {
      background: #eafaf1;
      color: #177847;
      border-color: rgba(86, 184, 140, 0.2);
    }

    .sync-badge.neutral {
      background: #f5f7fa;
      color: var(--po-muted);
      border-color: rgba(109,122,136,0.15);
    }

    .sync-btn {
      background: var(--po-accent-soft);
      color: var(--po-accent);
      border: 1px solid rgba(47, 126, 247, 0.18);
      border-radius: 9px;
      padding: 8px 12px;
      font-weight: 700;
      cursor: pointer;
    }

    .modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(16, 21, 31, 0.44);
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }

    .modal.active { display: flex; }

    .modal-card {
      width: min(700px, calc(100vw - 28px));
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 14px 40px rgba(13, 18, 28, 0.18);
      border: 1px solid var(--po-border);
      overflow: hidden;
    }

    #eventModal .modal-card {
      width: min(573px, calc(100vw - 20px));
      height: min(600px, calc(100vh - 20px));
      max-height: calc(100vh - 20px);
      display: flex;
      flex-direction: column;
    }

    .modal-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 18px 20px;
      border-bottom: 1px solid var(--po-border);
      background: #fbfcff;
    }

    .modal-head h3 {
      margin: 0;
      color: var(--po-text);
      font-size: 1.35rem;
    }

    .close-btn {
      border: 0;
      background: transparent;
      color: var(--po-muted);
      font-size: 1.6rem;
      cursor: pointer;
    }
    .danger-btn { background: var(--po-red-soft); color: var(--po-red); border: 1px solid rgba(214,91,91,.2); border-radius: 10px; padding: 9px 12px; font-weight: 700; cursor: pointer; }
    .form-error { display: none; padding: 9px 11px; margin-bottom: 12px; border-radius: 8px; background: var(--po-red-soft); color: #a62f3a; font-size: .78rem; }
    .form-error.active { display: block; }
    .detail-grid { display: grid; gap: 10px; }
    .detail-row { display: flex; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px solid var(--po-border); font-size: .82rem; }
    .detail-row strong { color: var(--po-muted); font-weight: 600; }
    .detail-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
    .calendar-toast { position: fixed; right: 22px; bottom: 22px; z-index: 1100; padding: 11px 15px; border-radius: 9px; background: #1f8a55; color: #fff; box-shadow: 0 10px 24px rgba(31,42,61,.18); font-size: .8rem; opacity: 0; transform: translateY(8px); pointer-events: none; transition: opacity .2s ease, transform .2s ease; }
    .calendar-toast.active { opacity: 1; transform: translateY(0); }
    .calendar-list { display: grid; gap: 8px; padding: 14px; }
    .list-event { display: grid; grid-template-columns: 96px 1fr auto; gap: 12px; align-items: center; padding: 12px; border: 1px solid var(--po-border); border-radius: 10px; cursor: pointer; }
    .list-event:hover { border-color: var(--po-accent); background: var(--po-accent-soft); }
    .list-event-date, .list-event-meta { color: var(--po-muted); font-size: .75rem; }
    .list-event-title { font-weight: 700; font-size: .82rem; }
    .list-event-meta { margin-top: 3px; }
    .list-event .mini-dot { margin-top: 0; }
    .week-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px; padding: 14px; }
    .week-column { min-height: 420px; padding: 9px; border: 1px solid var(--po-border); border-radius: 9px; background: #fbfcff; }
    .week-column h4 { margin: 0 0 8px; color: var(--po-muted); font-size: .72rem; text-align: center; }
    .week-event { margin-top: 7px; padding: 7px; border-radius: 6px; color: #fff; font-size: .66rem; line-height: 1.3; cursor: pointer; }
    .week-event:hover { filter: brightness(.94); }

    .modal-body {
      padding: 18px 20px 20px;
    }

    #eventModal .modal-body {
      min-height: 0;
      overflow-y: auto;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
    }

    .field {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .field.full { grid-column: 1 / -1; }

    .field label {
      color: var(--po-text);
      font-weight: 700;
      font-size: 0.82rem;
    }

    .field input,
    .field select,
    .field textarea {
      width: 100%;
      padding: 10px 12px;
      border-radius: 10px;
      border: 1px solid var(--po-border);
      background: #fff;
      color: var(--po-text);
      resize: vertical;
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 18px;
    }

    @media (max-width: 980px) {
      .po-shell,
      .shell { flex-direction: column; }
      .po-sidebar,
      .sidebar { width: 100%; }
      .nav { flex-direction: row; flex-wrap: wrap; }
      .nav-item { width: calc(50% - 6px); }
      .calendar-shell { grid-template-columns: 1fr; }
      .topbar-tools { width: 100%; justify-content: space-between; }
      .search { min-width: 180px; flex: 1; }
    }

    @media (max-width: 720px) {
      .page-body { padding: 18px 14px 24px; }
      .page-title-wrap { padding: 16px; }
      .calendar-toolbar { flex-wrap: wrap; }
      .form-grid { grid-template-columns: 1fr; }
      .nav-item { width: 100%; }
      .search { width: 100%; }
      .topbar { padding: 12px 16px; }
      .week-grid { overflow-x: auto; grid-template-columns: repeat(7, 130px); }
      .list-event { grid-template-columns: 1fr; gap: 5px; }
      #eventModal .modal-card {
        width: calc(100vw - 28px);
        height: min(600px, calc(100vh - 28px));
        max-height: calc(100vh - 28px);
      }
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
        <button class="nav-item" type="button" onclick="window.location.href='office.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('office') ?></span><span class="nav-label">My Office</span></span></button>
        <button class="nav-item active" type="button" onclick="window.location.href='calendar.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('calendar') ?></span><span class="nav-label">Calendar</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='reports.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('reports') ?></span><span class="nav-label">Reports</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='notifications.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('notifications') ?></span><span class="nav-label">Notifications</span></span><span class="nav-badge"><?= htmlspecialchars((string) $notificationCount) ?></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='settings.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('settings') ?></span><span class="nav-label">Settings</span></span></button>
        <button class="nav-item" id="logout-btn" type="button"><span class="nav-main"><span class="nav-icon"><?= po_icon('logout') ?></span><span class="nav-label">Log Out</span></span></button>
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
            <input id="calendarSearch" type="search" placeholder="Search dashboard..." />
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

      <main class="page-body">
        <div class="page-header">
          <div class="page-title-wrap">
            <div class="page-icon"><?= po_icon('calendar') ?></div>
            <div>
              <h1>Calendar</h1>
              <div class="breadcrumb"><strong>Dashboard</strong> <span>&rsaquo;</span> Calendar</div>
            </div>
          </div>
        </div>

        <div class="calendar-shell">
          <section class="main-panel">
            <div class="calendar-toolbar">
              <div class="calendar-controls">
                <button class="nav-btn" type="button" data-action="prev">&lsaquo;</button>
                <button class="today-btn" type="button" data-action="today">Today</button>
                <button class="nav-btn" type="button" data-action="next">&rsaquo;</button>
                <div id="monthLabel" class="month-label">August 2026</div>
              </div>

              <div class="calendar-controls">
                <div class="view-switch" aria-label="Calendar views">
                  <button class="view-btn active" type="button" data-view="month">Month</button>
                  <button class="view-btn" type="button" data-view="week">Week</button>
                  <button class="view-btn" type="button" data-view="list">List</button>
                </div>
                <button id="addEventBtn" class="primary-btn" type="button">+ Add Event</button>
              </div>
            </div>

            <div id="calendarGrid" class="calendar-grid"></div>
          </section>

          <aside class="right-panel">
            <div class="panel">
              <div class="panel-head">
                <h3>Upcoming Events</h3>
                <button class="link-btn" type="button" id="viewAllEventsBtn">See all</button>
              </div>
              <div id="upcomingEvents" class="events-list"></div>
            </div>

            <div class="panel">
              <div class="panel-head">
                <h3>Completed Events</h3>
              </div>
              <div id="completedEvents" class="events-list"></div>
            </div>

            <div class="panel filter-card">
              <label>Event Category</label>
              <select id="filterCategory" class="filter-select">
                <option value="All Categories">All Categories</option>
                <?php foreach ($eventCategories as $category): ?>
                  <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                <?php endforeach; ?>
              </select>

              <div class="filter-list">
                <?php foreach ($eventCategories as $category): ?>
                  <label class="check-item"><input type="checkbox" class="category-check" value="<?= htmlspecialchars($category) ?>" checked /> <?= htmlspecialchars($category) ?></label>
                <?php endforeach; ?>
              </div>

              <button id="applyFilterBtn" class="apply-btn" type="button">Apply Filter</button>
            </div>

            <div class="panel">
              <div class="panel-head">
                <h3>Calendar Legend</h3>
              </div>
              <div class="legend">
                <div class="legend-list">
                  <?php foreach ($eventCategories as $category): ?>
                    <span class="legend-item"><span class="legend-color" style="background: <?= htmlspecialchars($categoryColors[$category]) ?>;"></span><?= htmlspecialchars($category) ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <div class="sync-box">
              <h3>Google Calendar</h3>
              <div class="sync-row">
                <div class="sync-service"><?= po_icon('calendar') ?> <span>Google Calendar</span></div>
                <span id="googleStatusBadge" class="sync-badge <?= $googleConnected ? 'connected' : 'neutral' ?>"><?= $googleConnected ? 'Connected' : 'Not Connected' ?></span>
              </div>
              <div class="sync-row">
                <div class="sync-service"><?= po_icon('document') ?> <span>Outlook Calendar</span></div>
                <span class="sync-badge neutral">Planned</span>
              </div>
              <div class="sync-row">
                <div class="sync-service"><?= po_icon('calendar') ?> <span>iCal / Apple Calendar</span></div>
                <span class="sync-badge neutral">Planned</span>
              </div>
              <div class="sync-row" style="padding-bottom: 0;">
                <button id="googleConnectBtn" class="sync-btn" type="button"><?= $googleConnected ? 'Open in Google Calendar' : 'Connect Google Calendar' ?></button>
                <button id="syncNowBtn" class="ghost-btn" type="button">Sync Now</button>
              </div>
            </div>
          </aside>
        </div>
      </main>
    </div>
  </div>

  <div id="eventModal" class="modal" aria-modal="true" role="dialog">
    <div class="modal-card">
      <div class="modal-head">
        <h3 id="eventModalTitle">Add Event</h3>
        <button type="button" class="close-btn" data-close="eventModal">&times;</button>
      </div>
      <div class="modal-body">
        <form id="eventForm">
          <div id="eventFormError" class="form-error" role="alert"></div>
          <div class="form-grid">
            <div class="field full">
              <label for="eventTitle">Event Title</label>
              <input id="eventTitle" name="title" required type="text" placeholder="e.g. LGU Consultation" />
            </div>
            <div class="field full">
              <label for="eventDescription">Description</label>
              <textarea id="eventDescription" name="description" rows="3" placeholder="Short event description"></textarea>
            </div>
            <div class="field">
              <label for="eventStartDate">Start Date</label>
              <input id="eventStartDate" required type="date" />
            </div>
            <div class="field">
              <label for="eventStartTime">Start Time</label>
              <input id="eventStartTime" required type="time" />
            </div>
            <div class="field">
              <label for="eventEndDate">End Date</label>
              <input id="eventEndDate" required type="date" />
            </div>
            <div class="field">
              <label for="eventEndTime">End Time</label>
              <input id="eventEndTime" required type="time" />
            </div>
            <div class="field">
              <label for="eventLocation">Location</label>
              <input id="eventLocation" name="location" type="text" placeholder="Office / Venue" />
            </div>
            <div class="field">
              <label for="eventCategory">Category</label>
              <select id="eventCategory" name="category">
                <?php foreach ($eventCategories as $category): ?>
                  <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label for="eventOrganizer">Organizer</label>
              <input id="eventOrganizer" name="organizer" type="text" value="Provincial Office" />
            </div>
            <div class="field">
              <label for="eventAttendees">Attendees</label>
              <input id="eventAttendees" name="attendees" type="text" placeholder="LGU, Office, Staff" />
            </div>
            <div class="field">
              <label for="eventReminder">Reminder</label>
              <select id="eventReminder" name="reminder">
                <option value="">None</option>
                <option value="15 minutes">15 minutes</option>
                <option value="1 hour">1 hour</option>
                <option value="1 day">1 day</option>
              </select>
            </div>
            <div class="field">
              <label for="eventRecurrence">Recurrence</label>
              <select id="eventRecurrence" name="recurrence">
                <option value="">None</option>
                <option value="Weekly">Weekly</option>
                <option value="Monthly">Monthly</option>
              </select>
            </div>
          </div>
          <div class="form-actions">
            <button type="button" class="ghost-btn" data-close="eventModal">Cancel</button>
            <button type="submit" class="primary-btn" id="saveEventBtn">Save Event</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div id="eventDetailsModal" class="modal" aria-modal="true" role="dialog">
    <div class="modal-card">
      <div class="modal-head"><h3>Event Details</h3><button type="button" class="close-btn" data-close="eventDetailsModal">&times;</button></div>
      <div class="modal-body"><div id="eventDetailsBody" class="detail-grid"></div><div class="detail-actions"><button type="button" class="danger-btn" id="deleteEventBtn">Delete</button><button type="button" class="primary-btn" id="editEventBtn">Edit Event</button></div></div>
    </div>
  </div>
  <div id="calendarToast" class="calendar-toast" role="status"></div>

  <script>
    const categoryColors = <?= json_encode($categoryColors) ?>;
    const state = {
      view: 'month',
      date: new Date(),
      selectedCategory: 'All Categories',
      selectedCategories: new Set(<?= json_encode($eventCategories) ?>),
      events: []
    };

    const monthLabel = document.getElementById('monthLabel');
    const calendarGrid = document.getElementById('calendarGrid');
    const upcomingEvents = document.getElementById('upcomingEvents');
    const completedEvents = document.getElementById('completedEvents');
    const filterCategory = document.getElementById('filterCategory');
    const eventModal = document.getElementById('eventModal');
    const eventDetailsModal = document.getElementById('eventDetailsModal');
    const eventForm = document.getElementById('eventForm');
    const eventFormError = document.getElementById('eventFormError');
    const calendarToast = document.getElementById('calendarToast');
    let editingEventId = null;
    let selectedEvent = null;

    function showToast(message) {
      calendarToast.textContent = message;
      calendarToast.classList.add('active');
      window.clearTimeout(showToast.timer);
      showToast.timer = window.setTimeout(() => calendarToast.classList.remove('active'), 2600);
    }

    function formatDateTimeForInput(date) {
      const pad = value => String(value).padStart(2, '0');
      return `${date.getFullYear()}-${pad(date.getMonth()+1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }

    function formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function formatDateInput(date) {
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }

    function formatTimeInput(date) {
      return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
    }

    function formatShortDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    function getA11yDate(dateValue) {
      const date = new Date(dateValue);
      return date.toISOString().slice(0, 10);
    }

    async function loadEvents() {
      try {
        calendarGrid.innerHTML = '<div class="empty-state">Loading calendar events...</div>';
        const response = await fetch('../shared/auth/calendar_api.php?action=list');
        const data = await response.json();
        if (!data.success) {
          state.events = [];
          return;
        }
        state.events = Array.isArray(data.events) ? data.events : [];
        render();
      } catch (error) {
        console.error('Failed to load calendar events', error);
        state.events = [];
        render();
      }
    }

    function getFilteredEvents() {
      return state.events.filter(event => {
        const matchesCategory = state.selectedCategories.has(event.category) || state.selectedCategory === 'All Categories' && state.selectedCategories.size === <?= count($eventCategories) ?>;
        return matchesCategory;
      });
    }

    function getMonthViewDays(date) {
      const year = date.getFullYear();
      const month = date.getMonth();
      const firstDay = new Date(year, month, 1);
      const lastDay = new Date(year, month + 1, 0);
      const startOffset = firstDay.getDay();
      const totalDays = lastDay.getDate();
      const cells = [];
      const prevMonthLastDate = new Date(year, month, 0).getDate();

      for (let i = 0; i < startOffset; i++) {
        cells.push({ date: new Date(year, month - 1, prevMonthLastDate - (startOffset - i - 1)), muted: true });
      }

      for (let day = 1; day <= totalDays; day++) {
        cells.push({ date: new Date(year, month, day), muted: false });
      }

      while (cells.length % 7 !== 0) {
        const nextDay = cells.length - (firstDay.getDay() + totalDays) + 1;
        cells.push({ date: new Date(year, month + 1, nextDay), muted: true });
      }

      return cells;
    }

    function renderMonthCalendar() {
      const cells = getMonthViewDays(state.date);
      const year = state.date.getFullYear();
      const month = state.date.getMonth();
      monthLabel.textContent = state.date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

      const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
      const weekdayMarkup = weekdays.map(day => `<div class="weekday">${day}</div>`).join('');

      const daysMarkup = cells.map(cell => {
        const d = cell.date;
        const dayKey = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        const events = getFilteredEvents().filter(event => {
          const start = new Date(event.start_at);
          const end = new Date(event.end_at);
          const dayStart = new Date(d); dayStart.setHours(0, 0, 0, 0);
          const dayEnd = new Date(d); dayEnd.setHours(23, 59, 59, 999);
          return start <= dayEnd && end >= dayStart;
        });

        const isToday = d.toDateString() === new Date().toDateString();
        const dayClasses = ['day-cell'];
        if (cell.muted) dayClasses.push('muted');
        if (isToday) dayClasses.push('today');

        return `
          <div class="${dayClasses.join(' ')}" data-day="${dayKey}">
            <div class="day-number">${d.getDate()}</div>
            ${events.slice(0, 3).map(event => `
              <span class="event-pill ${String(event.category).toLowerCase()}" data-event-id="${event.id}" title="${escapeHtml(event.title)}">
                ${escapeHtml(event.title)}
              </span>
            `).join('')}
          </div>
        `;
      }).join('');

      calendarGrid.innerHTML = `
        <div class="calendar-weekdays">${weekdayMarkup}</div>
        <div class="calendar-days">${daysMarkup}</div>
      `;

      document.querySelectorAll('.day-cell').forEach(cell => {
        cell.addEventListener('click', () => {
          const day = cell.dataset.day;
          const base = new Date(day + 'T12:00:00');
          openEventModal(null, base);
        });
      });
      document.querySelectorAll('[data-event-id]').forEach(item => {
        item.addEventListener('click', event => {
          event.stopPropagation();
          showEventDetails(Number(item.dataset.eventId));
        });
      });
    }

    function renderUpcomingEvents() {
      const now = new Date();
      const sortedEvents = getFilteredEvents().slice().sort((a, b) => new Date(a.start_at) - new Date(b.start_at));
      const upcoming = sortedEvents.filter(event => new Date(event.end_at) >= now).slice(0, 6);
      const completed = sortedEvents.filter(event => new Date(event.end_at) < now).sort((a, b) => new Date(b.end_at) - new Date(a.end_at)).slice(0, 4);

      if (!upcoming.length) {
        upcomingEvents.innerHTML = '<div class="empty-state">No upcoming events for the selected filter.</div>';
      } else {
        upcomingEvents.innerHTML = upcoming.map(event => renderMiniEvent(event, false)).join('');
      }

      if (!completed.length) {
        completedEvents.innerHTML = '<div class="empty-state">No completed events.</div>';
      } else {
        completedEvents.innerHTML = completed.map(event => renderMiniEvent(event, true)).join('');
      }
      bindEventDetails();
    }

    function renderMiniEvent(event, isCompleted) {
        const category = event.category || 'Meetings';
        const color = categoryColors[category] || '#5a8dee';
        return `
          <div class="mini-event" data-event-id="${event.id}" role="button" tabindex="0" aria-label="Open ${escapeHtml(event.title)} details" style="cursor:pointer;">
            <span class="mini-dot" style="background:${color};"></span>
            <div>
              <strong>${escapeHtml(event.title)}</strong>
              <small>Category: ${escapeHtml(category)}</small>
              <small>${isCompleted ? 'Completed' : (new Date(event.start_at) < new Date() ? 'In progress' : 'Upcoming')} &bull; ${formatShortDate(event.start_at)} &bull; ${new Date(event.start_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })} - ${new Date(event.end_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}</small>
            </div>
          </div>
        `;
    }

    function renderListView() {
      const events = getFilteredEvents().slice().sort((a, b) => new Date(a.start_at) - new Date(b.start_at));
      calendarGrid.innerHTML = `<div class="calendar-list">${events.length ? events.map(event => `
        <div class="list-event" data-event-id="${event.id}">
          <span class="list-event-date">${formatDate(event.start_at)}<br>${new Date(event.start_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}</span>
          <div><div class="list-event-title">${escapeHtml(event.title)}</div><div class="list-event-meta">${escapeHtml(event.category || 'Events')}${event.location ? ` &bull; ${escapeHtml(event.location)}` : ''}</div></div>
          <span class="mini-dot" style="background:${categoryColors[event.category] || '#5a8dee'}"></span>
        </div>`).join('') : '<div class="empty-state">No events for the selected filter.</div>'}</div>`;
      bindEventDetails();
    }

    function renderWeekView() {
      const weekStart = new Date(state.date);
      weekStart.setDate(state.date.getDate() - state.date.getDay());
      const columns = Array.from({ length: 7 }, (_, index) => {
        const day = new Date(weekStart);
        day.setDate(weekStart.getDate() + index);
        const dayEvents = getFilteredEvents().filter(event => {
          const start = new Date(event.start_at);
          const end = new Date(event.end_at);
          const dayStart = new Date(day); dayStart.setHours(0, 0, 0, 0);
          const dayEnd = new Date(day); dayEnd.setHours(23, 59, 59, 999);
          return start <= dayEnd && end >= dayStart;
        });
        return `<div class="week-column"><h4>${day.toLocaleDateString('en-US', { weekday: 'short' })}<br>${day.getDate()}</h4>${dayEvents.map(event => `<div class="week-event" data-event-id="${event.id}" style="background:${categoryColors[event.category] || '#5a8dee'}">${escapeHtml(event.title)}<br><small>${new Date(event.start_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}</small></div>`).join('')}</div>`;
      }).join('');
      calendarGrid.innerHTML = `<div class="week-grid">${columns}</div>`;
      bindEventDetails();
    }

    function bindEventDetails() {
      document.querySelectorAll('[data-event-id]').forEach(item => {
        item.addEventListener('click', event => {
          event.stopPropagation();
          showEventDetails(Number(item.dataset.eventId));
        });
        item.addEventListener('keydown', event => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            showEventDetails(Number(item.dataset.eventId));
          }
        });
      });
    }

    function render() {
      if (state.view === 'month') renderMonthCalendar();
      else if (state.view === 'week') renderWeekView();
      else renderListView();
      renderUpcomingEvents();
    }

    function updateCategorySelection() {
      const selected = [...document.querySelectorAll('.category-check:checked')].map(item => item.value);
      if (selected.length === 0) {
        state.selectedCategory = 'All Categories';
        filterCategory.value = 'All Categories';
        return;
      }
      state.selectedCategory = filterCategory.value === 'All Categories' ? 'All Categories' : filterCategory.value;
    }

    function changeMonth(by) {
      state.date = new Date(state.date.getFullYear(), state.date.getMonth() + by, 1);
      render();
    }

    function openEventModal(eventToEdit = null, selectedDate = null) {
      editingEventId = eventToEdit ? Number(eventToEdit.id) : null;
      eventForm.reset();
      eventFormError.classList.remove('active');
      document.getElementById('eventModalTitle').textContent = editingEventId ? 'Edit Event' : 'Add Event';
      document.getElementById('saveEventBtn').textContent = editingEventId ? 'Update Event' : 'Save Event';
      if (eventToEdit) {
        const start = new Date(eventToEdit.start_at);
        const end = new Date(eventToEdit.end_at);
        document.getElementById('eventTitle').value = eventToEdit.title || '';
        document.getElementById('eventDescription').value = eventToEdit.description || '';
        document.getElementById('eventStartDate').value = formatDateInput(start);
        document.getElementById('eventStartTime').value = formatTimeInput(start);
        document.getElementById('eventEndDate').value = formatDateInput(end);
        document.getElementById('eventEndTime').value = formatTimeInput(end);
        document.getElementById('eventLocation').value = eventToEdit.location || '';
        document.getElementById('eventCategory').value = eventToEdit.category || 'Meetings';
        document.getElementById('eventOrganizer').value = eventToEdit.organizer || 'Provincial Office';
        document.getElementById('eventAttendees').value = eventToEdit.attendees || '';
        document.getElementById('eventReminder').value = eventToEdit.reminder || '';
        document.getElementById('eventRecurrence').value = eventToEdit.recurrence || '';
      } else if (selectedDate) {
        document.getElementById('eventStartDate').value = formatDateInput(selectedDate);
        document.getElementById('eventEndDate').value = formatDateInput(selectedDate);
        document.getElementById('eventStartTime').value = '09:00';
        document.getElementById('eventEndTime').value = '10:00';
      }
      eventModal.classList.add('active');
    }

    function closeEventModal() {
      eventModal.classList.remove('active');
      eventForm.reset();
      editingEventId = null;
    }

    function showEventDetails(eventId) {
      selectedEvent = state.events.find(event => Number(event.id) === eventId);
      if (!selectedEvent) return;
      const start = new Date(selectedEvent.start_at);
      const end = new Date(selectedEvent.end_at);
      const now = new Date();
      const status = end < now ? 'Completed' : start < now ? 'In Progress' : 'Upcoming';
      document.getElementById('eventDetailsBody').innerHTML = `
        <div class="detail-row"><strong>Title</strong><span>${escapeHtml(selectedEvent.title)}</span></div>
        <div class="detail-row"><strong>Category</strong><span>${escapeHtml(selectedEvent.category || 'Events')}</span></div>
        <div class="detail-row"><strong>Schedule</strong><span>${formatDate(selectedEvent.start_at)} ${start.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}<br>to ${formatDate(selectedEvent.end_at)} ${end.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}</span></div>
        <div class="detail-row"><strong>Status</strong><span>${status}</span></div>
        <div class="detail-row"><strong>Location</strong><span>${escapeHtml(selectedEvent.location || 'No location specified')}</span></div>
        <div class="detail-row"><strong>Description</strong><span>${escapeHtml(selectedEvent.description || 'No description provided')}</span></div>`;
      eventDetailsModal.classList.add('active');
    }

    async function saveEvent(event) {
      event.preventDefault();
      const formData = new FormData(eventForm);
      const startDate = document.getElementById('eventStartDate').value;
      const startTime = document.getElementById('eventStartTime').value;
      const endDate = document.getElementById('eventEndDate').value;
      const endTime = document.getElementById('eventEndTime').value;
      const payload = {
        id: editingEventId,
        title: formData.get('title'),
        description: formData.get('description'),
        start_at: `${startDate} ${startTime}:00`,
        end_at: `${endDate} ${endTime}:00`,
        location: formData.get('location'),
        category: formData.get('category'),
        organizer: formData.get('organizer'),
        attendees: formData.get('attendees'),
        reminder: formData.get('reminder'),
        recurrence: formData.get('recurrence'),
        source: 'local'
      };

      if (!payload.title || !startDate || !startTime || !endDate || !endTime) {
        eventFormError.textContent = 'Title, start date, start time, end date, and end time are required.';
        eventFormError.classList.add('active');
        return;
      }
      if (new Date(payload.end_at) < new Date(payload.start_at)) {
        eventFormError.textContent = 'End date and time cannot be earlier than the start date and time.';
        eventFormError.classList.add('active');
        return;
      }

      try {
        const wasEditing = Boolean(editingEventId);
        const response = await fetch(`../shared/auth/calendar_api.php?action=${editingEventId ? 'update' : 'create'}`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!data.success) {
          eventFormError.textContent = data.error || 'Unable to save event.';
          eventFormError.classList.add('active');
          return;
        }
        closeEventModal();
        showToast(wasEditing ? 'Event updated successfully.' : 'Event saved successfully.');
        await loadEvents();
      } catch (error) {
        console.error('Failed to create event', error);
        eventFormError.textContent = 'Unable to save the event right now.';
        eventFormError.classList.add('active');
      }
    }

    async function deleteEvent(eventId) {
      if (!confirm('Delete this event? This action cannot be undone.')) return;
      try {
        const response = await fetch('../shared/auth/calendar_api.php?action=delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: eventId })
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.error || 'Unable to delete event.');
        eventDetailsModal.classList.remove('active');
        showToast('Event deleted successfully.');
        await loadEvents();
      } catch (error) {
        alert(error.message || 'Unable to delete the event right now.');
      }
    }

    async function syncGoogleCalendar() {
      const response = await fetch('../shared/auth/calendar_api.php?action=sync_status');
      const data = await response.json();
      if (!data.connected) {
        window.location.href = '../shared/auth/google_calendar_oauth.php';
        return;
      }
      window.open('https://calendar.google.com/calendar/u/0/r', '_blank');
    }

    function escapeHtml(value) {
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    document.querySelector('[data-action="prev"]').addEventListener('click', () => changeMonth(-1));
    document.querySelector('[data-action="next"]').addEventListener('click', () => changeMonth(1));
    document.querySelector('[data-action="today"]').addEventListener('click', () => {
      state.date = new Date();
      render();
    });

    document.querySelectorAll('.view-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.view-btn').forEach(item => item.classList.toggle('active', item === btn));
        state.view = btn.dataset.view;
        if (state.view === 'month') renderMonthCalendar();
        else if (state.view === 'week') renderWeekView();
        else renderListView();
      });
    });

    filterCategory.addEventListener('change', () => {
      state.selectedCategory = filterCategory.value;
      state.selectedCategories = filterCategory.value === 'All Categories' ? new Set(<?= json_encode($eventCategories) ?>) : new Set([filterCategory.value]);
      document.querySelectorAll('.category-check').forEach(check => {
        check.checked = filterCategory.value === 'All Categories' || check.value === filterCategory.value;
      });
      render();
    });

    document.querySelectorAll('.category-check').forEach(check => {
      check.addEventListener('change', () => {
        const values = [...document.querySelectorAll('.category-check:checked')].map(item => item.value);
        if (values.length === 0) {
          filterCategory.value = 'All Categories';
          state.selectedCategory = 'All Categories';
          state.selectedCategories = new Set(<?= json_encode($eventCategories) ?>);
        } else {
          state.selectedCategories = new Set(values);
          filterCategory.value = values.length === 1 ? values[0] : 'All Categories';
          state.selectedCategory = values.length === 1 ? values[0] : 'All Categories';
        }
        render();
      });
    });

    document.getElementById('applyFilterBtn').addEventListener('click', () => {
      const values = [...document.querySelectorAll('.category-check:checked')].map(item => item.value);
      state.selectedCategories = new Set(values.length ? values : <?= json_encode($eventCategories) ?>);
      state.selectedCategory = filterCategory.value;
      render();
    });

    document.getElementById('addEventBtn').addEventListener('click', openEventModal);
    document.getElementById('editEventBtn').addEventListener('click', () => {
      if (!selectedEvent) return;
      eventDetailsModal.classList.remove('active');
      openEventModal(selectedEvent);
    });
    document.getElementById('deleteEventBtn').addEventListener('click', () => {
      if (selectedEvent) deleteEvent(selectedEvent.id);
    });
    document.getElementById('googleConnectBtn').addEventListener('click', syncGoogleCalendar);
    document.getElementById('syncNowBtn').addEventListener('click', async () => {
      await loadEvents();
      const badge = document.getElementById('googleStatusBadge');
      if (badge) {
        badge.textContent = 'Connected';
        badge.className = 'sync-badge connected';
      }
    });
    document.getElementById('viewAllEventsBtn').addEventListener('click', () => {
      state.selectedCategory = 'All Categories';
      state.selectedCategories = new Set(<?= json_encode($eventCategories) ?>);
      filterCategory.value = 'All Categories';
      document.querySelectorAll('.category-check').forEach(check => { check.checked = true; });
      render();
    });

    document.querySelectorAll('[data-close="eventModal"]').forEach(btn => {
      btn.addEventListener('click', closeEventModal);
    });
    document.querySelectorAll('[data-close="eventDetailsModal"]').forEach(btn => {
      btn.addEventListener('click', () => eventDetailsModal.classList.remove('active'));
    });

    eventModal.addEventListener('click', (event) => {
      if (event.target === eventModal) closeEventModal();
    });
    eventDetailsModal.addEventListener('click', (event) => {
      if (event.target === eventDetailsModal) eventDetailsModal.classList.remove('active');
    });

    eventForm.addEventListener('submit', saveEvent);

    document.getElementById('logout-btn').addEventListener('click', () => {
      fetch('../shared/auth/logout.php', { method: 'POST' }).finally(() => {
        window.location.href = '../../index.html';
      });
    });

    render();
    loadEvents();
  </script>
</body>
</html>

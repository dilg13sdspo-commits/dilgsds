<?php
require_once __DIR__ . '/../shared/auth/db.php';
require_once __DIR__ . '/../shared/auth/middleware.php';
require_role('FO USER');

$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);
$profilePhoto = get_user_profile_photo($pdo, $userId);
$userName = $_SESSION['username'] ?? 'FO User';
$officeName = $_SESSION['office_name'] ?? 'Field Office';

function ensure_fo_calendar_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS calendar_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        office_id INT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        start_at DATETIME NOT NULL,
        end_at DATETIME NOT NULL,
        location VARCHAR(255) NULL,
        category VARCHAR(80) NOT NULL DEFAULT 'My Events',
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
        INDEX idx_google_event (google_event_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

ensure_fo_calendar_schema($pdo);

$googleTokenStmt = $pdo->prepare('SELECT * FROM google_calendar_tokens WHERE user_id = :uid LIMIT 1');
$googleTokenStmt->execute([':uid' => $userId]);
$googleConnected = $googleTokenStmt->fetch() !== false;

$categoryPalette = [
    'My Events' => '#2fb778',
    'DILG Meetings' => '#6cbf6a',
    'Deadlines' => '#ea6a6a',
    'Trainings & Seminars' => '#b784e2',
    'Holidays' => '#f39f52',
    'Other FO Activities' => '#5aa3d8',
];

$allCategories = array_keys($categoryPalette);
$pendingGoogleStatus = $googleConnected ? 'Connected' : 'Not Connected';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Calendar & Activities | FO User Dashboard</title>
  <style>
    :root {
      --fo-green-900: #0b3f34;
      --fo-green-800: #0f4d3d;
      --fo-green-700: #1d6050;
      --fo-green-600: #2c7b67;
      --fo-panel: #ffffff;
      --fo-soft: #f4f7f5;
      --fo-surface: #eef2ef;
      --fo-border: rgba(15, 51, 38, 0.12);
      --fo-text: #22343a;
      --fo-muted: #66757a;
      --fo-white: #fff;
      --fo-shadow: 0 12px 28px rgba(18, 32, 28, 0.08);
      --fo-red: #e65d5d;
      --fo-green: #46b882;
      --fo-yellow: #f0b648;
      --fo-purple: #b884e8;
      --fo-blue: #6ca7db;
      --fo-orange: #f2a65b;
      --fo-status: #0c8d5b;
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: #edf3f1;
      color: var(--fo-text);
    }
    a { color: inherit; text-decoration: none; }
    button, input, select, textarea { font: inherit; }
    .sr-only { position:absolute; left:-9999px; }

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
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.1);
      display: grid;
      place-items: center;
      font-weight: 800;
      font-size: 1.15rem;
    }
    .brand-copy strong {
      display: block;
      font-size: 1.05rem;
      letter-spacing: 0.06em;
      line-height: 1.08;
    }
    .brand-copy span {
      display: block;
      font-size: 0.68rem;
      opacity: 0.8;
      margin-top: 4px;
      line-height: 1.4;
    }

    .profile-box {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 18px 6px 8px;
      padding: 10px 8px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 14px;
    }

    .profile-avatar {
      flex: 0 0 36px;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      background: rgba(255,255,255,0.12);
      border: 1px solid rgba(255,255,255,0.15);
      font-weight: 800;
      color: #fff;
    }
    .profile-avatar img, .trigger-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }

    .profile-role {
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: 0.8rem;
      color: rgba(255,255,255,0.88);
    }
    .status-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #34d399;
      box-shadow: 0 0 0 4px rgba(52,211,153,0.15);
    }

    .nav { margin-top: 8px; display:flex; flex-direction:column; gap:4px; padding:0 4px; }
    .nav-item {
      width:100%;
      border:none;
      border-radius:10px;
      background:transparent;
      color:rgba(255,255,255,0.9);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      padding:11px 10px;
      text-align:left;
      cursor:pointer;
      font-weight:600;
      transition: all 0.2s ease;
    }
    .nav-item:hover, .nav-item.active {
      background: rgba(255,255,255,0.12);
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
    }
    .nav-item.active {
      background: rgba(255,255,255,0.16);
      box-shadow: inset 3px 0 0 rgba(255,255,255,0.8);
    }
    .nav-main { display:inline-flex; align-items:center; gap:10px; min-width:0; }
    .nav-icon { font-size: 1rem; }
    .nav-icon svg, .control-icon, .page-icon svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; vertical-align: middle; }
    .nav-label { white-space: nowrap; overflow:hidden; text-overflow: ellipsis; }
    .nav-badge {
      min-width:18px; height:18px; border-radius:999px; padding:0 6px; background: rgba(255,255,255,0.18);
      color:#fff; display:grid; place-items:center; font-size:0.68rem; font-weight:800;
    }

    .sidebar-footer {
      margin-top:auto; border-top:1px solid rgba(255,255,255,0.12); padding-top:12px;
    }
    .footer-brand {
      display:flex; align-items:center; gap:10px; padding:4px 8px 0; color:rgba(255,255,255,0.92); font-weight:700; font-size:0.85rem;
    }
    .footer-seal {
      width:38px; height:38px; border-radius:50%; background: rgba(255,255,255,0.1);
      display:grid; place-items:center; border:1px solid rgba(255,255,255,0.08); font-weight:800;
    }

    .main { flex:1; min-width:0; display:flex; flex-direction:column; }

    .topbar {
      display:flex; align-items:center; justify-content:space-between; gap:20px;
      background: rgba(255,255,255,0.92);
      border-bottom:1px solid var(--fo-border);
      padding:14px 20px; position:sticky; top:0; z-index:30;
    }
    .topbar-left { display:flex; align-items:center; gap:12px; flex:1; min-width:0; }
    .menu-btn {
      width:42px; height:42px; border:1px solid var(--fo-border); background:#fff; border-radius:12px;
      display:grid; place-items:center; box-shadow:var(--fo-shadow); cursor:pointer; color: var(--fo-green-800);
    }
    .page-title { min-width:0; flex:1; }
    .page-title h1 {
      margin:0; font-size: clamp(1.4rem, 2vw, 2.05rem); font-weight:700; line-height:1.2; color: var(--fo-green-900);
      white-space: nowrap; overflow:hidden; text-overflow: ellipsis;
    }
    .page-title p {
      margin:6px 0 0; color: var(--fo-muted); font-size:0.92rem; max-width:760px;
      white-space: nowrap; overflow:hidden; text-overflow: ellipsis;
    }
    .topbar-right { display:flex; align-items:center; gap:12px; }
    .search-box {
      display:flex; align-items:center; gap:8px; width:min(320px, 30vw); min-width:180px;
      background:#fff; border:1px solid var(--fo-border); border-radius:12px; padding:10px 12px; box-shadow: var(--fo-shadow); color:var(--fo-muted);
    }
    .search-box input {
      width:100%; border:none; background:transparent; color:var(--fo-text); outline:none; font-size:0.92rem;
    }
    .notif-button {
      position:relative; width:42px; height:42px; border:1px solid var(--fo-border); background:#fff; border-radius:12px;
      display:grid; place-items:center; box-shadow:var(--fo-shadow); cursor:pointer; color: var(--fo-green-800);
    }
    .notif-count {
      position:absolute; top:-6px; right:-5px; min-width:18px; height:18px; border-radius:999px; display:grid; place-items:center; padding:0 5px;
      background: var(--fo-red); color:#fff; font-size:0.68rem; font-weight:800;
    }
    .profile-wrap { position:relative; }
    .profile-trigger {
      display:flex; align-items:center; gap:10px; padding:7px 10px 7px 8px; background:#fff; border:1px solid var(--fo-border);
      border-radius:12px; box-shadow:var(--fo-shadow); cursor:pointer; color: var(--fo-text);
    }
    .trigger-avatar {
      width:34px; height:34px; border-radius:50%; background: linear-gradient(135deg, #1f9d73, #2d80f2); display:grid; place-items:center;
      color:#fff; font-weight:800; font-size:0.8rem;
    }
    .trigger-meta { display:flex; flex-direction:column; line-height:1.15; text-align:left; }
    .trigger-meta strong { font-size:0.9rem; }
    .trigger-meta span { font-size:0.7rem; color: var(--fo-muted); }
    .profile-dropdown {
      position:absolute; top:calc(100% + 9px); right:0; width:180px; background:#fff; border:1px solid var(--fo-border); border-radius:12px;
      box-shadow: 0 18px 34px rgba(16,35,35,0.14); padding:8px; display:none; z-index:30;
    }
    .profile-wrap.open .profile-dropdown { display:block; }
    .profile-dropdown a { display:block; padding:10px 12px; border-radius:8px; color:var(--fo-text); font-weight:600; }
    .profile-dropdown a:hover { background:#f3f8f7; color: var(--fo-green-800); }

    .page-shell {
      flex:1; display:flex; flex-direction:column; gap:18px; padding:22px 20px 24px;
    }
    .calendar-panel {
      background: var(--fo-panel); border:1px solid var(--fo-border); border-radius:14px; box-shadow: var(--fo-shadow);
      padding: 18px 18px 12px; display:flex; flex-direction:column; gap:18px;
    }
    .page-header {
      display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding-bottom:10px; border-bottom:1px solid var(--fo-border);
    }
    .page-heading { display:flex; align-items:center; gap:12px; }
    .page-icon {
      width:42px; height:42px; border-radius:12px; background: rgba(35, 111, 89, 0.1); color:var(--fo-green-800);
      display:grid; place-items:center; font-size:1.3rem;
    }
    .page-heading h2 {
      margin:0; font-size:clamp(1.75rem, 2vw, 2.3rem); color:var(--fo-green-900); line-height:1.1;
    }
    .page-heading p { margin:4px 0 0; color:var(--fo-muted); font-size:0.95rem; }
    .header-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .secondary-btn, .primary-btn, .icon-btn, .ghost-btn, .filter-chip {
      border-radius:10px; border:1px solid var(--fo-border); background:#fff; color:var(--fo-green-800); padding:10px 14px; font-weight:700; cursor:pointer;
      transition:all 0.2s ease;
    }
    .secondary-btn:hover, .primary-btn:hover, .icon-btn:hover, .ghost-btn:hover, .filter-chip:hover { transform: translateY(-1px); }
    .primary-btn {
      border:none; background: linear-gradient(135deg, var(--fo-green-700), var(--fo-green-600)); color:#fff; box-shadow:0 8px 18px rgba(30,96,80,0.18);
    }
    .icon-btn {
      display:inline-flex; align-items:center; gap:8px; padding:9px 12px;
    }
    .icon-btn.google { background:#f8fbf9; }
    .icon-btn.sync { background:#f8fbf9; }

    .calendar-layout {
      display:grid; grid-template-columns: 230px minmax(0, 1fr) 270px; gap:18px;
      align-items:start;
    }
    .mini-panel, .side-panel {
      background: var(--fo-soft); border:1px solid var(--fo-border); border-radius:12px; padding:12px;
    }
    .mini-panel h3, .side-panel h3 {
      margin:0 0 12px; font-size:1rem; color:var(--fo-green-900);
    }
    .mini-calendar { background:#fff; border:1px solid var(--fo-border); border-radius:10px; }
    .mini-month-head {
      display:flex; align-items:center; justify-content:space-between; padding:10px 12px; font-weight:700; color:var(--fo-green-900); border-bottom:1px solid var(--fo-border);
    }
    .mini-days {
      display:grid; grid-template-columns: repeat(7, 1fr); gap:4px; padding:10px 8px 8px;
    }
    .mini-day-name, .mini-day-num {
      display:grid; place-items:center; border-radius:7px; font-size:0.7rem; color:var(--fo-muted);
    }
    .mini-day-name { height:24px; font-weight:700; }
    .mini-day-num {
      height:26px; cursor:pointer; border:1px solid transparent; font-weight:600; color:var(--fo-text); background:transparent; padding:0; font-family:inherit;
    }
    .mini-day-num.muted { color:#b0b8bb; }
    .mini-day-num.today { background: rgba(28,120,92,0.12); color:var(--fo-green-900); border-color: rgba(28,120,92,0.2); }
    .mini-day-num.active { background: var(--fo-green-900); color:#fff; }

    .calendar-list {
      margin-top:12px; display:flex; flex-direction:column; gap:8px;
    }
    .calendar-check {
      display:flex; align-items:center; gap:8px; padding:6px 8px; border-radius:8px; background:#fff; border:1px solid var(--fo-border); color:var(--fo-text); font-size:0.85rem;
    }
    .calendar-check input { accent-color: var(--fo-green-800); }
    .calendar-check .swatch { width:10px; height:10px; border-radius:50%; display:inline-block; }
    .add-calendar {
      margin-top:12px; background:#fff; color:var(--fo-green-800); border:1px dashed rgba(15, 77, 61, 0.4);
      border-radius:10px; padding:10px 12px; width:100%; font-weight:700; cursor:pointer;
    }

    .upcoming-box {
      margin-top:12px; background:#fff; border:1px solid var(--fo-border); border-radius:10px; padding:10px;
    }
    .upcoming-head {
      display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:10px;
    }
    .upcoming-head h4 { margin:0; font-size:0.95rem; color:var(--fo-green-900); }
    .link-btn { background:transparent; border:none; color:var(--fo-green-800); font-weight:700; cursor:pointer; }
    .event-list { display:flex; flex-direction:column; gap:10px; }
    .event-item {
      display:flex; align-items:flex-start; gap:10px; padding:8px 6px; border-radius:8px; background:#f9faf9; border:1px solid transparent;
    }
    .event-item .bullet { width:9px; height:9px; border-radius:50%; margin-top:8px; flex-shrink:0; }
    .event-item strong { display:block; font-size:0.86rem; color:var(--fo-text); }
    .event-item small { display:block; margin-top:2px; color:var(--fo-muted); line-height:1.35; }

    .main-calendar-area {
      background:#fff; border:1px solid var(--fo-border); border-radius:14px; box-shadow: var(--fo-shadow); overflow:hidden;
    }
    .calendar-toolbar {
      display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; padding:14px 16px; border-bottom:1px solid var(--fo-border); background:#f9fbfa;
    }
    .month-nav { display:flex; align-items:center; gap:10px; }
    .month-control {
      width:36px; height:36px; border-radius:10px; border:1px solid var(--fo-border); background:#fff; cursor:pointer; color:var(--fo-green-900); font-size:1.25rem;
    }
    .today-btn {
      border:1px solid var(--fo-border); background:#fff; border-radius:10px; color:var(--fo-green-800); padding:8px 12px; font-weight:700; cursor:pointer;
    }
    .month-title {
      min-width:180px; text-align:center; font-weight:700; font-size:1.85rem; color:var(--fo-green-900);
    }
    .view-switch {
      display:flex; align-items:center; gap:6px; background:#f4f7f5; border:1px solid var(--fo-border); border-radius:10px; padding:4px;
    }
    .view-switch button {
      border:none; background:transparent; color:var(--fo-muted); font-weight:700; padding:8px 12px; border-radius:8px; cursor:pointer;
    }
    .view-switch button.active {
      background: var(--fo-green-900); color:#fff; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.03);
    }

    .calendar-grid {
      display:flex; flex-direction:column; border-top:1px solid var(--fo-border);
    }
    .calendar-weekdays {
      display:grid; grid-template-columns: repeat(7, minmax(0, 1fr)); background:#f9fbfa;
      border-bottom:1px solid var(--fo-border);
    }
    .weekday {
      padding:12px 8px; text-align:center; font-size:0.78rem; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:var(--fo-muted);
      border-right:1px solid var(--fo-border);
    }
    .weekday:last-child { border-right:none; }

    .days-grid {
      display:grid; grid-template-columns: repeat(7, minmax(0, 1fr));
      min-height: 620px;
    }
    .day-cell {
      min-height: 120px; border-right:1px solid var(--fo-border); border-bottom:1px solid var(--fo-border); padding:8px 6px 6px; background:#fff; position:relative; cursor:pointer; transition: background 0.2s ease;
    }
    .day-cell:hover { background:#fafdfb; }
    .day-cell.muted { background:#fbfcfb; color:#b4bbc0; }
    .day-cell.today { background: rgba(17, 100, 80, 0.02); }
    .day-cell.selected { box-shadow: inset 0 0 0 2px var(--fo-green-600); }
    .day-cell .day-number {
      display:inline-flex; align-items:center; justify-content:center; min-width:26px; height:26px; border-radius:50%; font-weight:700; margin-bottom:8px; padding:0 8px;
    }
    .day-cell.today .day-number { background: var(--fo-green-900); color:#fff; }
    .event-pill {
      display:block; width:100%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; border-radius:6px; color:#243b3d; padding:5px 7px; font-size:0.74rem; font-weight:700; margin-bottom:4px; border:1px solid rgba(0,0,0,0.04);
    }
    .event-pill.multi-day {
      position: relative;
      padding-right: 20px;
      box-shadow: inset 0 0 0 1px rgba(16, 42, 36, 0.04);
    }
    .event-pill.multi-day::after {
      content: "->";
      position: absolute;
      right: 6px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 0.7rem;
      opacity: 0.75;
    }
    .event-pill.my-events { background: rgba(47,183,120,0.12); color:#186b4c; }
    .event-pill.dilg-meetings { background: rgba(108,191,106,0.12); color:#3f7d3f; }
    .event-pill.consultations { background: rgba(74,144,226,0.12); color:#285fa8; }
    .event-pill.programs { background: rgba(244,185,66,0.14); color:#9d6802; }
    .event-pill.deadlines { background: rgba(234,106,106,0.12); color:#b34242; }
    .event-pill.trainings-and-seminars { background: rgba(184,132,232,0.12); color:#7d57b5; }
    .event-pill.holidays { background: rgba(243,159,82,0.12); color:#b26014; }
    .event-pill.other-fo-activities { background: rgba(108,167,219,0.12); color:#396c95; }

    .agenda-panel, .week-panel, .day-panel {
      min-height: 220px; padding:16px; background:#fff; display:none;
    }
    .agenda-panel.active, .week-panel.active, .day-panel.active { display:block; }

    .agenda-list {
      list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px;
    }
    .agenda-item {
      display:flex; align-items:flex-start; gap:14px; padding:12px 10px; border:1px solid var(--fo-border); border-radius:10px; background:#f9fbfa;
    }
    .agenda-date {
      min-width:110px; background:#fff; border:1px solid var(--fo-border); border-radius:8px; padding:8px 10px; text-align:center; font-weight:700; color:var(--fo-green-900);
    }
    .agenda-date small { display:block; color:var(--fo-muted); font-size:0.72rem; }
    .week-grid { display:grid; grid-template-columns:repeat(7, minmax(0, 1fr)); gap:8px; }
    .week-day { min-height:220px; border:1px solid var(--fo-border); border-radius:10px; padding:8px; background:#fbfdfc; }
    .week-day.selected { box-shadow:inset 0 0 0 2px var(--fo-green-600); }
    .week-day h4 { margin:0 0 8px; color:var(--fo-green-900); font-size:0.78rem; }
    .week-event { display:block; width:100%; margin:0 0 6px; padding:6px; border:0; border-left:3px solid var(--fo-green-600); border-radius:6px; background:#eef8f3; color:var(--fo-text); text-align:left; cursor:pointer; font-size:0.72rem; }
    .week-event strong { display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .week-event small { color:var(--fo-muted); }
    @media (max-width: 760px) { .week-grid { grid-template-columns:1fr; } .week-day { min-height:0; } }

    .empty-state {
      text-align:center; color:var(--fo-muted); padding:26px 12px; font-weight:600;
    }

    .modal {
      position:fixed; inset:0; background: rgba(10,26,22,0.35); display:none; align-items:center; justify-content:center; padding:20px; z-index:100;
    }
    .modal.active { display:flex; }
    .modal-card {
      width:min(760px, 100%); background:#fff; border-radius:16px; border:1px solid var(--fo-border); box-shadow: 0 28px 70px rgba(10,36,31,0.22);
    }
    .modal-head {
      display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 18px; border-bottom:1px solid var(--fo-border);
    }
    .modal-head h3 { margin:0; color:var(--fo-green-900); font-size:1.26rem; }
    .close-btn {
      width:34px; height:34px; border:none; border-radius:10px; background:#f4f8f6; color:var(--fo-green-800); font-size:1.4rem; cursor:pointer;
    }
    .modal-body { padding:18px; }
    .event-form { display:flex; flex-direction:column; gap:16px; }
    .form-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:14px; }
    .field { display:flex; flex-direction:column; gap:7px; }
    .field.full { grid-column:1 / -1; }
    .field label { font-size:0.84rem; font-weight:700; color:var(--fo-green-900); }
    .field input, .field select, .field textarea {
      width:100%; border:1px solid var(--fo-border); border-radius:10px; padding:10px 11px; background:#fff; color:var(--fo-text);
    }
    .field textarea { min-height:98px; resize:vertical; }
    .modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:4px; }
    .ghost-btn { background:#fff; }
    .danger-btn {
      background: #fff2f2;
      border-color: rgba(182, 58, 58, 0.24);
      color: #b53d3d;
    }

    .confirm-modal {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.22);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 200;
      padding: 20px;
    }
    .confirm-modal.active { display: flex; }
    .confirm-card {
      width: min(420px, 100%);
      background: rgba(255, 255, 255, 0.96);
      border-radius: 14px;
      box-shadow: 0 14px 40px rgba(0,0,0,0.18);
      border: 1px solid rgba(17, 24, 39, 0.08);
      padding: 18px 18px 14px;
    }
    .confirm-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;
    }
    .confirm-icon {
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: linear-gradient(135deg, #2f7df6, #5a9df7);
      display: grid;
      place-items: center;
      color: #fff;
      font-size: 0.7rem;
      font-weight: 700;
      flex-shrink: 0;
    }
    .confirm-title {
      font-size: 1rem;
      font-weight: 700;
      color: #1f2937;
    }
    .confirm-message {
      font-size: 0.95rem;
      line-height: 1.5;
      color: #1f2937;
      margin: 10px 0 18px;
      padding-left: 30px;
    }
    .confirm-actions {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      padding-left: 30px;
    }
    .confirm-primary,
    .confirm-secondary {
      min-width: 120px;
      border-radius: 999px;
      border: 1px solid #7c90ff;
      padding: 10px 18px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .confirm-primary {
      background: linear-gradient(180deg, #4d5ef5, #3d4fe6);
      color: #fff;
      border-color: #3d4fe6;
    }
    .confirm-secondary {
      background: #fff;
      color: #2d3748;
      border-color: rgba(31, 41, 55, 0.12);
    }

    @media (max-width: 1200px) {
      .calendar-layout { grid-template-columns: 220px minmax(0, 1fr); }
      .side-panel { grid-column: 1 / -1; }
    }
    @media (max-width: 950px) {
      .layout { flex-direction:column; }
      .sidebar { width:100%; }
      .nav { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .page-shell { padding:14px; }
      .calendar-layout { grid-template-columns: 1fr; }
      .main-calendar-area { order:1; }
      .mini-panel, .side-panel { order:2; }
      .topbar { flex-wrap:wrap; }
      .topbar-right { width:100%; justify-content:space-between; }
      .search-box { width:100%; }
    }
    @media (max-width: 560px) {
      .calendar-panel { padding:14px; }
      .page-header { flex-direction:column; }
      .header-actions { width:100%; justify-content:flex-start; }
      .month-title { font-size:1.4rem; }
      .form-grid { grid-template-columns:1fr; }
      .search-box { min-width: 0; }
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
        <button class="nav-item active" type="button" onclick="window.location.href='calendar.php'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01"/></svg></span><span class="nav-label">Calendar &amp; Activities</span></span></button>
        <button class="nav-item" type="button" onclick="location.href='notifications.php'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></span><span class="nav-label">Notifications</span></span><span class="nav-badge">0</span></button>
        <button class="nav-item" type="button" onclick="location.href='settings.php'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.9 1.9-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V20h-2.7v-.09a1.7 1.7 0 0 0-1.03-1.56 1.7 1.7 0 0 0-1.88.34l-.06.06-1.9-1.9.06-.06A1.7 1.7 0 0 0 7.8 15a1.7 1.7 0 0 0-1.56-1.03H6v-2.7h.24A1.7 1.7 0 0 0 7.8 10a1.7 1.7 0 0 0-.34-1.88L7.4 8.06l1.9-1.9.06.06A1.7 1.7 0 0 0 10.4 6a1.7 1.7 0 0 0 1.03-1.56V4h2.7v.09A1.7 1.7 0 0 0 15.2 6a1.7 1.7 0 0 0 1.88-.34l.06-.06 1.9 1.9-.06.06A1.7 1.7 0 0 0 18.6 10a1.7 1.7 0 0 0 1.56 1.03H20v2.7h-.09A1.7 1.7 0 0 0 19.4 15Z"/></svg></span><span class="nav-label">Profile Settings</span></span></button>
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
            <input id="global-search" type="search" placeholder="Search inquiries, documents..." />
          </label>

          <button class="notif-button" type="button" aria-label="Notifications">
            <svg class="control-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
            <span class="notif-count">0</span>
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

      <div class="page-shell">
        <div class="calendar-panel">
          <div class="page-header">
            <div class="page-heading">
              <div class="page-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01"/></svg></div>
              <div>
                <h2>Calendar &amp; Activities</h2>
                <p>View and manage your schedule, events, and important activities.</p>
              </div>
            </div>

            <div class="header-actions">
              <button id="googleSyncBtn" class="icon-btn google" type="button">&#9729; <span>Google Calendar</span></button>
              <button id="refreshCalendarBtn" class="icon-btn sync" type="button">&#8635; <span>Refresh</span></button>
              <button id="createEventBtn" class="primary-btn" type="button">+ Add Activity</button>
            </div>
          </div>

          <div class="calendar-layout">
            <aside class="mini-panel">
              <h3>Calendars</h3>
              <div class="mini-calendar">
                <div class="mini-month-head">
                  <button class="month-control" type="button" data-action="prevMini">&lsaquo;</button>
                  <span id="miniMonthLabel">May 2026</span>
                  <button class="month-control" type="button" data-action="nextMini">&rsaquo;</button>
                </div>
                <div class="mini-days" id="miniCalendarGrid"></div>
              </div>

              <div class="calendar-list">
                <?php foreach ($allCategories as $category): ?>
                  <label class="calendar-check">
                    <input type="checkbox" class="category-check" value="<?= htmlspecialchars($category) ?>" checked />
                    <span class="swatch" style="background: <?= htmlspecialchars($categoryPalette[$category]) ?>;"></span>
                    <span><?= htmlspecialchars($category) ?></span>
                  </label>
                <?php endforeach; ?>
                <button class="add-calendar" type="button">+ Add Calendar</button>
              </div>
            </aside>

            <section class="main-calendar-area">
              <div class="calendar-toolbar">
                <div class="month-nav">
                  <button class="month-control" type="button" data-action="prevMain">&lsaquo;</button>
                  <button class="today-btn" type="button" data-action="today">Today</button>
                  <button class="month-control" type="button" data-action="nextMain">&rsaquo;</button>
                  <div id="mainMonthLabel" class="month-title">May 2026</div>
                </div>

                <div class="view-switch" aria-label="Calendar view selector">
                  <button class="active" type="button" data-view="month">Month</button>
                  <button type="button" data-view="week">Week</button>
                  <button type="button" data-view="day">Day</button>
                  <button type="button" data-view="agenda">Agenda</button>
                </div>
              </div>

              <div id="monthView" class="calendar-grid active-view">
                <div class="calendar-weekdays">
                  <div class="weekday">Sun</div><div class="weekday">Mon</div><div class="weekday">Tue</div><div class="weekday">Wed</div><div class="weekday">Thu</div><div class="weekday">Fri</div><div class="weekday">Sat</div>
                </div>
                <div id="monthGrid" class="days-grid"></div>
              </div>

              <div id="weekView" class="week-panel">
                <div class="empty-state">Week view is ready and will render the selected week schedule.</div>
              </div>

              <div id="dayView" class="day-panel">
                <div class="empty-state">Day view is ready and will render details for the selected date.</div>
              </div>

              <div id="agendaView" class="agenda-panel">
                <ul id="agendaList" class="agenda-list"></ul>
              </div>
            </section>

            <aside class="side-panel">
              <div class="upcoming-box">
                <div class="upcoming-head">
                  <h4>Upcoming Events</h4>
                  <button class="link-btn" type="button" id="viewAllBtn">See all</button>
                </div>
                <div id="upcomingList" class="event-list"></div>
              </div>

              <div class="upcoming-box" style="margin-top:12px;">
                <div class="upcoming-head">
                  <h4>Completed Events</h4>
                </div>
                <div id="completedList" class="event-list"></div>
              </div>

              <div class="upcoming-box" style="margin-top:12px;">
                <div class="upcoming-head">
                  <h4>Google Calendar</h4>
                  <span id="googleBadge" class="status-tag" style="display:inline-block; background: rgba(46, 125, 93, 0.08); color: #1a654c; border-radius:999px; padding:4px 8px; font-size:0.72rem; font-weight:700;">
                    <?= htmlspecialchars($pendingGoogleStatus) ?>
                  </span>
                </div>
                <div class="event-item" style="margin-top:8px;">
                  <span class="bullet" style="background: #5fb783;"></span>
                  <div>
                    <strong>Sync status</strong>
                    <small id="syncDescription"><?= $googleConnected ? 'Connected and ready to pull Google events.' : 'Not connected yet. Connect your Google account to sync events.' ?></small>
                  </div>
                </div>
              </div>
            </aside>
          </div>
        </div>
      </div>
    </main>
  </div>

  <div id="eventModal" class="modal" aria-modal="true" role="dialog">
    <div class="modal-card">
      <div class="modal-head">
        <h3>Create Event</h3>
        <button type="button" class="close-btn" data-close="eventModal">&times;</button>
      </div>
      <div class="modal-body">
        <form id="eventForm" class="event-form">
          <div class="form-grid">
            <div class="field full">
              <label for="eventTitle">Title</label>
              <input id="eventTitle" name="title" required type="text" placeholder="Event title" />
            </div>
            <div class="field full">
              <label for="eventDescription">Description</label>
              <textarea id="eventDescription" name="description" placeholder="Short description about the event"></textarea>
            </div>
            <div class="field">
              <label for="eventStart">Starts</label>
              <input id="eventStart" name="start_at" required type="datetime-local" />
            </div>
            <div class="field">
              <label for="eventEnd">Ends</label>
              <input id="eventEnd" name="end_at" required type="datetime-local" />
            </div>
            <div class="field">
              <label for="eventLocation">Location</label>
              <input id="eventLocation" name="location" type="text" placeholder="Office / venue" />
            </div>
            <div class="field">
              <label for="eventCategory">Category</label>
              <select id="eventCategory" name="category" required>
                <?php foreach ($allCategories as $category): ?>
                  <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label for="eventOrganizer">Organizer</label>
              <input id="eventOrganizer" name="organizer" type="text" value="Field Office" />
            </div>
            <div class="field">
              <label for="eventAttendees">Attendees</label>
              <input id="eventAttendees" name="attendees" type="text" placeholder="LGU, Staff, Partners" />
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
          <div class="modal-actions">
            <button type="button" id="deleteEventBtn" class="ghost-btn danger-btn" style="display:none; margin-right:auto;">Delete Event</button>
            <button type="button" class="ghost-btn" data-close="eventModal">Cancel</button>
            <button type="submit" class="primary-btn">Save Event</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    const state = {
      view: 'month',
      date: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
      selectedDate: new Date(),
      selectedCategories: new Set(['My Events', 'DILG Meetings', 'Consultations', 'Programs', 'Deadlines', 'Trainings & Seminars', 'Holidays', 'Other FO Activities']),
      events: [],
      currentEventId: null
    };

    const categoryMap = <?= json_encode($categoryPalette, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const mainMonthLabel = document.getElementById('mainMonthLabel');
    const miniMonthLabel = document.getElementById('miniMonthLabel');
    const monthGrid = document.getElementById('monthGrid');
    const agendaList = document.getElementById('agendaList');
    const upcomingList = document.getElementById('upcomingList');
    const completedList = document.getElementById('completedList');
    const eventModal = document.getElementById('eventModal');
    const eventForm = document.getElementById('eventForm');
    const eventModalTitle = document.querySelector('#eventModal .modal-head h3');
    const deleteEventBtn = document.getElementById('deleteEventBtn');
    const googleBadge = document.getElementById('googleBadge');
    const syncDescription = document.getElementById('syncDescription');

    function pad(value) { return String(value).padStart(2, '0'); }

    function formatDateTimeForInput(date) {
      return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }

    function formatMonthLabel(date) {
      return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }

    function toDateObject(value) {
      return value ? new Date(value) : null;
    }

    function escapeHtml(value) {
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function getCategoryClass(category) {
      const categoryName = getCategoryLabel(category);
      return String(categoryName || 'My Events')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '') || 'my-events';
    }

    function getCategoryLabel(category) {
      const normalized = category || 'My Events';
      const aliases = {
        Meetings: 'DILG Meetings',
        Meeting: 'DILG Meetings',
        Programs: 'Programs',
        Program: 'Programs',
        Consultations: 'Consultations',
        Consultation: 'Consultations',
        Trainings: 'Trainings & Seminars',
        Training: 'Trainings & Seminars',
        Events: 'My Events',
        Event: 'My Events'
      };
      return aliases[normalized] || normalized || 'My Events';
    }

    function getCategoryColor(category) {
      const label = getCategoryLabel(category);
      return categoryMap[label] || categoryMap[category] || '#2fb778';
    }

    function getVisibleEvents() {
      return state.events.filter(event => {
        const category = event.category || 'My Events';
        const uiCategory = getCategoryLabel(category);
        const fallbackAliases = {
          Meetings: 'DILG Meetings',
          Programs: 'Programs',
          Consultations: 'Consultations',
          Trainings: 'Trainings & Seminars',
          Events: 'My Events'
        };
        const available = new Set([
          category,
          uiCategory,
          fallbackAliases[category] || category,
          categoryMap[uiCategory] ? uiCategory : null
        ].filter(Boolean));
        return [...available].some(value => state.selectedCategories.has(value));
      });
    }

    function isMultiDayEvent(event) {
      if (!event || !event.start_at || !event.end_at) {
        return false;
      }
      const start = new Date(event.start_at);
      const end = new Date(event.end_at);
      return end.getTime() > start.getTime() + (24 * 60 * 60 * 1000);
    }

    function formatEventDateRange(event) {
      const start = new Date(event.start_at);
      const end = new Date(event.end_at);
      const startDate = start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      const endDate = end.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      const dateText = start.toDateString() === end.toDateString() ? startDate : `${startDate} - ${endDate}`;
      return `${dateText} - ${start.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })} - ${end.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`;
    }

    function buildMiniCalendar(date) {
      const year = date.getFullYear();
      const month = date.getMonth();
      const first = new Date(year, month, 1);
      const last = new Date(year, month + 1, 0);
      const startDay = first.getDay();
      const days = [];
      const prevMonthTotal = new Date(year, month, 0).getDate();

      for (let i = 0; i < startDay; i++) {
        days.push({ value: prevMonthTotal - startDay + i + 1, muted: true, date: new Date(year, month - 1, prevMonthTotal - startDay + i + 1) });
      }
      for (let d = 1; d <= last.getDate(); d++) {
        days.push({ value: d, muted: false, date: new Date(year, month, d) });
      }
      while (days.length % 7 !== 0) {
        const nextVal = days.length - (first.getDay() + last.getDate()) + 1;
        days.push({ value: nextVal, muted: true, date: new Date(year, month + 1, nextVal) });
      }

      const miniGrid = document.getElementById('miniCalendarGrid');
      miniMonthLabel.textContent = formatMonthLabel(date);
      const names = ['S','M','T','W','T','F','S'];
      miniGrid.innerHTML = names.map(name => `<div class="mini-day-name">${name}</div>`).join('') + days.map(item => {
        const isToday = !item.muted && date.getFullYear() === new Date().getFullYear() && date.getMonth() === new Date().getMonth() && item.value === new Date().getDate();
        const isActive = state.selectedDate && item.date.toDateString() === state.selectedDate.toDateString();
        const itemKey = `${item.date.getFullYear()}-${pad(item.date.getMonth() + 1)}-${pad(item.date.getDate())}`;
        return `<button class="mini-day-num ${item.muted ? 'muted' : ''} ${isToday ? 'today' : ''} ${isActive ? 'active' : ''}" type="button" data-day="${itemKey}">${item.value}</button>`;
      }).join('');

      miniGrid.querySelectorAll('[data-day]').forEach((day) => {
        day.addEventListener('click', () => {
          const selectedDate = new Date(day.dataset.day + 'T12:00:00');
          state.selectedDate = selectedDate;
          if (selectedDate.getMonth() !== state.date.getMonth() || selectedDate.getFullYear() !== state.date.getFullYear()) {
            state.date = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
          }
          refreshCalendarUi();
          applyView(state.view);
        });
      });
    }

    function renderMonthGrid() {
      const year = state.date.getFullYear();
      const month = state.date.getMonth();
      const first = new Date(year, month, 1);
      const startIndex = first.getDay();
      const lastDay = new Date(year, month + 1, 0).getDate();
      const prevMonthLast = new Date(year, month, 0).getDate();
      const visible = [];

      for (let i = 0; i < startIndex; i++) {
        visible.push({ date: new Date(year, month - 1, prevMonthLast - startIndex + i + 1), muted: true });
      }
      for (let d = 1; d <= lastDay; d++) {
        visible.push({ date: new Date(year, month, d), muted: false });
      }
      while (visible.length % 7 !== 0) {
        const next = visible.length - (startIndex + lastDay) + 1;
        visible.push({ date: new Date(year, month + 1, next), muted: true });
      }

      mainMonthLabel.textContent = formatMonthLabel(state.date);
      monthGrid.innerHTML = visible.map(({ date, muted }) => {
        const dayKey = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
        const todays = getVisibleEvents().filter(event => {
          const eventStart = new Date(event.start_at);
          const eventEnd = new Date(event.end_at);
          const dayStart = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0, 0);
          const dayEnd = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 23, 59, 59, 999);
          return eventStart <= dayEnd && eventEnd >= dayStart;
        });
        const isToday = new Date().toDateString() === date.toDateString();
        const isSelected = state.selectedDate && state.selectedDate.toDateString() === date.toDateString();
          return `
            <div class="day-cell ${muted ? 'muted' : ''} ${isToday ? 'today' : ''} ${isSelected ? 'selected' : ''}" data-day="${dayKey}">
              <div class="day-number">${date.getDate()}</div>
              ${todays.slice(0, 3).map(event => {
                const multiDay = isMultiDayEvent(event) ? 'multi-day' : '';
                const eventColor = getCategoryColor(event.category);
                return `<span class="event-pill ${getCategoryClass(event.category)} ${multiDay}" data-event-id="${event.id}" title="${escapeHtml(event.title)}" style="background:${eventColor}1A;border-color:${eventColor}55;color:${eventColor};">${escapeHtml(event.title)}</span>`;
              }).join('')}
            </div>
          `;
      }).join('');

      monthGrid.querySelectorAll('.day-cell').forEach(cell => {
        cell.addEventListener('click', (event) => {
          if (event.target.closest('.event-pill')) {
            return;
          }
          state.selectedDate = new Date(cell.dataset.day + 'T12:00:00');
          if (state.selectedDate.getMonth() !== state.date.getMonth() || state.selectedDate.getFullYear() !== state.date.getFullYear()) {
            state.date = new Date(state.selectedDate.getFullYear(), state.selectedDate.getMonth(), 1);
          }
          refreshCalendarUi();
        });
      });

      monthGrid.querySelectorAll('.event-pill').forEach(pill => {
        pill.addEventListener('click', (event) => {
          event.stopPropagation();
          const eventId = Number(pill.dataset.eventId);
          const selectedEvent = state.events.find(item => Number(item.id) === eventId);
          if (selectedEvent) {
            openEventModal('edit', selectedEvent);
          }
        });
      });
    }

    function renderAgenda() {
      const filtered = getVisibleEvents().slice().sort((a, b) => new Date(a.start_at) - new Date(b.start_at));
      if (!filtered.length) {
        agendaList.innerHTML = '<li class="empty-state">No agenda items for the selected filters.</li>';
        return;
      }

      agendaList.innerHTML = filtered.slice(0, 8).map(event => {
        const start = new Date(event.start_at);
        const end = new Date(event.end_at);
        const color = getCategoryColor(event.category);
        return `
          <li class="agenda-item" data-event-id="${event.id}" style="cursor:pointer;">
            <div class="agenda-date">
              ${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
              <small>${start.toLocaleDateString('en-US', { weekday: 'short' })}</small>
            </div>
            <div style="flex:1; min-width:0;">
              <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:${color};"></span>
                <strong style="font-size:0.96rem;">${escapeHtml(event.title)}</strong>
              </div>
              <div style="color:var(--fo-muted); font-size:0.8rem; line-height:1.5;">
                ${start.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })} - ${end.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}<br>
                ${escapeHtml(event.location || 'Field Office')} - Category: ${escapeHtml(getCategoryLabel(event.category))}
              </div>
            </div>
          </li>
        `;
      }).join('');
      agendaList.querySelectorAll('[data-event-id]').forEach((item) => item.addEventListener('click', () => {
        const event = state.events.find((entry) => Number(entry.id) === Number(item.dataset.eventId));
        if (event) openEventModal('edit', event);
      }));
    }

    function renderDayView() {
      const selectedKey = `${state.selectedDate.getFullYear()}-${pad(state.selectedDate.getMonth() + 1)}-${pad(state.selectedDate.getDate())}`;
      const selectedEvents = getVisibleEvents().filter((event) => {
        const start = new Date(event.start_at);
        const end = new Date(event.end_at);
        const dayStart = new Date(selectedKey + 'T00:00:00');
        const dayEnd = new Date(selectedKey + 'T23:59:59');
        return start <= dayEnd && end >= dayStart;
      }).sort((a, b) => new Date(a.start_at) - new Date(b.start_at));
      const dayLabel = state.selectedDate.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
      const dayView = document.getElementById('dayView');
      dayView.innerHTML = `<h3>${escapeHtml(dayLabel)}</h3>` + (selectedEvents.length ? selectedEvents.map((event) => `<div class="agenda-item" data-event-id="${event.id}" style="cursor:pointer;"><div class="agenda-date">${new Date(event.start_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}<small>${escapeHtml(getCategoryLabel(event.category))}</small></div><div><strong>${escapeHtml(event.title)}</strong><p>Category: ${escapeHtml(getCategoryLabel(event.category))}<br>${escapeHtml(event.location || event.description || 'No additional details.')}</p></div></div>`).join('') : '<div class="empty-state">No activities scheduled for this date.</div>');
      dayView.querySelectorAll('[data-event-id]').forEach((item) => item.addEventListener('click', () => {
        const event = state.events.find((entry) => Number(entry.id) === Number(item.dataset.eventId));
        if (event) openEventModal('edit', event);
      }));
    }

    function renderWeekView() {
      const weekStart = new Date(state.selectedDate);
      weekStart.setHours(0, 0, 0, 0);
      weekStart.setDate(weekStart.getDate() - weekStart.getDay());
      const days = Array.from({ length: 7 }, (_, index) => {
        const day = new Date(weekStart);
        day.setDate(weekStart.getDate() + index);
        return day;
      });
      const weekEvents = getVisibleEvents();
      document.getElementById('weekView').innerHTML = `<div class="week-grid">${days.map((day) => {
        const dayStart = new Date(day); const dayEnd = new Date(day); dayEnd.setHours(23, 59, 59, 999);
        const events = weekEvents.filter((event) => new Date(event.start_at) <= dayEnd && new Date(event.end_at) >= dayStart).sort((a, b) => new Date(a.start_at) - new Date(b.start_at));
        return `<div class="week-day ${day.toDateString() === state.selectedDate.toDateString() ? 'selected' : ''}"><h4>${day.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })}</h4>${events.length ? events.map((event) => `<button class="week-event" style="border-left-color:${getCategoryColor(event.category)}" type="button" data-event-id="${event.id}"><strong>${escapeHtml(event.title)}</strong><small>Category: ${escapeHtml(getCategoryLabel(event.category))}</small><small>${new Date(event.start_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}${isMultiDayEvent(event) ? ' - Multi-day' : ''}</small></button>`).join('') : '<div class="empty-state">No activities</div>'}</div>`;
      }).join('')}</div>`;
      document.querySelectorAll('#weekView [data-event-id]').forEach((button) => button.addEventListener('click', () => {
        const event = state.events.find((item) => Number(item.id) === Number(button.dataset.eventId));
        if (event) openEventModal('edit', event);
      }));
    }

    function fillEventForm(event = null) {
      const defaults = {
        title: '',
        description: '',
        start_at: '',
        end_at: '',
        location: '',
        category: 'My Events',
        organizer: 'Field Office',
        attendees: '',
        reminder: '',
        recurrence: ''
      };

      const values = event ? {
        title: event.title || '',
        description: event.description || '',
        start_at: event.start_at ? event.start_at.slice(0, 16) : '',
        end_at: event.end_at ? event.end_at.slice(0, 16) : '',
        location: event.location || '',
        category: getCategoryLabel(event.category),
        organizer: event.organizer || 'Field Office',
        attendees: event.attendees || '',
        reminder: event.reminder || '',
        recurrence: event.recurrence || ''
      } : defaults;

      eventForm.title.value = values.title;
      eventForm.description.value = values.description;
      eventForm.start_at.value = values.start_at;
      eventForm.end_at.value = values.end_at;
      eventForm.location.value = values.location;
      eventForm.category.value = values.category;
      if (!eventForm.category.value) {
        eventForm.category.value = 'My Events';
      }
      eventForm.organizer.value = values.organizer;
      eventForm.attendees.value = values.attendees;
      eventForm.reminder.value = values.reminder;
      eventForm.recurrence.value = values.recurrence;
    }

    function renderUpcoming() {
      const now = new Date();
      const filtered = getVisibleEvents()
        .filter((event) => new Date(event.end_at) >= now)
        .slice()
        .sort((a, b) => new Date(a.start_at) - new Date(b.start_at))
        .slice(0, 5);
      if (!filtered.length) {
        upcomingList.innerHTML = '<div class="empty-state">No upcoming events.</div>';
      } else {
        upcomingList.innerHTML = filtered.map(event => {
          const color = getCategoryColor(event.category);
          return `
            <div class="event-item" data-event-id="${event.id}" role="button" tabindex="0" style="cursor:pointer;">
              <span class="bullet" style="background: ${color};"></span>
              <div>
                <strong>${escapeHtml(event.title)}</strong>
                <small>Category: ${escapeHtml(getCategoryLabel(event.category))}</small>
                <small>${formatEventDateRange(event)}</small>
              </div>
            </div>
          `;
        }).join('');

        upcomingList.querySelectorAll('[data-event-id]').forEach(item => {
          const openSelectedEvent = () => {
            const selected = state.events.find(event => Number(event.id) === Number(item.dataset.eventId));
            if (selected) openEventModal('edit', selected);
          };
          item.addEventListener('click', openSelectedEvent);
          item.addEventListener('keydown', (event) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openSelectedEvent(); } });
        });
      }

      const completed = getVisibleEvents()
        .filter((event) => new Date(event.end_at) < now)
        .slice()
        .sort((a, b) => new Date(b.end_at) - new Date(a.end_at))
        .slice(0, 5);
      if (!completed.length) {
        completedList.innerHTML = '<div class="empty-state">No completed events.</div>';
        return;
      }

      completedList.innerHTML = completed.map(event => {
        const color = getCategoryColor(event.category);
        return `<div class="event-item" data-event-id="${event.id}" role="button" tabindex="0" style="cursor:pointer;"><span class="bullet" style="background:${color};"></span><div><strong>${escapeHtml(event.title)}</strong><small>Category: ${escapeHtml(getCategoryLabel(event.category))}</small><small>Completed - ${formatEventDateRange(event)}</small></div></div>`;
      }).join('');
      completedList.querySelectorAll('[data-event-id]').forEach(item => {
        const openCompletedEvent = () => {
          const selected = state.events.find(event => Number(event.id) === Number(item.dataset.eventId));
          if (selected) openEventModal('edit', selected);
        };
        item.addEventListener('click', openCompletedEvent);
        item.addEventListener('keydown', (event) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openCompletedEvent(); } });
      });
    }

    function applyView(view) {
      state.view = view;
      document.querySelectorAll('.view-switch button').forEach(btn => btn.classList.toggle('active', btn.dataset.view === view));
      document.getElementById('monthView').style.display = view === 'month' ? 'flex' : 'none';
      document.getElementById('weekView').style.display = view === 'week' ? 'block' : 'none';
      document.getElementById('dayView').style.display = view === 'day' ? 'block' : 'none';
      document.getElementById('agendaView').style.display = view === 'agenda' ? 'block' : 'none';
      if (view === 'agenda') renderAgenda();
      if (view === 'day') renderDayView();
      if (view === 'week') renderWeekView();
    }

    async function loadEvents() {
      try {
        const response = await fetch('../shared/auth/calendar_api.php?action=list');
        const data = await response.json();
        if (data && data.success) {
          state.events = Array.isArray(data.events) ? data.events : [];
        } else {
          state.events = [];
        }
      } catch (error) {
        console.error('Calendar event load failed', error);
        state.events = [];
      }
      refreshCalendarUi();
    }

    function refreshCalendarUi() {
      buildMiniCalendar(state.date);
      renderMonthGrid();
      renderUpcoming();
      renderAgenda();
      renderDayView();
      renderWeekView();
    }

    function openEventModal(mode = 'create', event = null) {
      state.currentEventId = event && event.id ? Number(event.id) : null;
      const isEdit = mode === 'edit' && !!event;
      eventModalTitle.textContent = isEdit ? 'Edit Event' : 'Create Event';
      deleteEventBtn.style.display = isEdit ? 'inline-flex' : 'none';
      fillEventForm(event);
      eventModal.classList.add('active');
    }
    function closeEventModal() {
      eventModal.classList.remove('active');
      state.currentEventId = null;
      eventForm.reset();
      eventModalTitle.textContent = 'Create Event';
      deleteEventBtn.style.display = 'none';
    }

    async function saveEvent(event) {
      event.preventDefault();
      const form = new FormData(eventForm);
      const payload = {
        title: form.get('title'),
        description: form.get('description'),
        start_at: form.get('start_at'),
        end_at: form.get('end_at'),
        location: form.get('location'),
        category: form.get('category'),
        organizer: form.get('organizer'),
        attendees: form.get('attendees'),
        reminder: form.get('reminder'),
        recurrence: form.get('recurrence'),
        source: 'local'
      };

      if (!payload.title || !payload.category || !payload.start_at || !payload.end_at) {
        alert('Title, category, start date, and end date are required.');
        return;
      }
      if (new Date(payload.end_at) < new Date(payload.start_at)) {
        alert('End date and time cannot be earlier than the start date and time.');
        return;
      }

      const action = state.currentEventId ? 'update' : 'create';
      if (state.currentEventId) {
        payload.id = state.currentEventId;
      }

      try {
        const response = await fetch(`../shared/auth/calendar_api.php?action=${action}`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (!result.success) {
          alert(result.error || 'Unable to save event.');
          return;
        }
        closeEventModal();
        await loadEvents();
      } catch (error) {
        console.error('Save event failed', error);
        alert('Unable to save the event right now.');
      }
    }

    deleteEventBtn.addEventListener('click', async () => {
      if (!state.currentEventId) return;
      if (!confirm('Delete this event?')) return;

      try {
        const response = await fetch('../shared/auth/calendar_api.php?action=delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: state.currentEventId })
        });
        const result = await response.json();
        if (!result.success) {
          alert(result.error || 'Unable to delete event.');
          return;
        }
        closeEventModal();
        await loadEvents();
      } catch (error) {
        console.error('Delete event failed', error);
        alert('Unable to delete the event right now.');
      }
    });

    async function refreshGoogleSync() {
      try {
        const res = await fetch('../shared/auth/calendar_api.php?action=sync_status');
        const data = await res.json();
        if (!data || !data.connected) {
          googleBadge.textContent = 'Not Connected';
          googleBadge.style.background = 'rgba(118, 128, 131, 0.12)';
          googleBadge.style.color = '#4b5a62';
          syncDescription.textContent = 'Not connected yet. Connect your Google account to sync events.';
          return;
        }
        googleBadge.textContent = 'Connected';
        googleBadge.style.background = 'rgba(46, 125, 93, 0.08)';
        googleBadge.style.color = '#1a654c';
        syncDescription.textContent = 'Connected and ready to pull Google events.';
      } catch (error) {
        console.error('Sync status failed', error);
      }
    }

    document.querySelector('[data-action="prevMini"]').addEventListener('click', () => {
      state.date = new Date(state.date.getFullYear(), state.date.getMonth() - 1, 1);
      state.selectedDate = new Date(state.date.getFullYear(), state.date.getMonth(), 1);
      refreshCalendarUi();
    });
    document.querySelector('[data-action="nextMini"]').addEventListener('click', () => {
      state.date = new Date(state.date.getFullYear(), state.date.getMonth() + 1, 1);
      state.selectedDate = new Date(state.date.getFullYear(), state.date.getMonth(), 1);
      refreshCalendarUi();
    });
    document.querySelector('[data-action="prevMain"]').addEventListener('click', () => {
      state.date = new Date(state.date.getFullYear(), state.date.getMonth() - 1, 1);
      state.selectedDate = new Date(state.date.getFullYear(), state.date.getMonth(), 1);
      refreshCalendarUi();
    });
    document.querySelector('[data-action="nextMain"]').addEventListener('click', () => {
      state.date = new Date(state.date.getFullYear(), state.date.getMonth() + 1, 1);
      state.selectedDate = new Date(state.date.getFullYear(), state.date.getMonth(), 1);
      refreshCalendarUi();
    });
    document.querySelector('[data-action="today"]').addEventListener('click', () => {
      state.date = new Date();
      state.selectedDate = new Date();
      refreshCalendarUi();
    });

    document.querySelectorAll('.view-switch button').forEach(button => {
      button.addEventListener('click', () => applyView(button.dataset.view));
    });

    document.getElementById('createEventBtn').addEventListener('click', () => openEventModal('create'));
    document.getElementById('refreshCalendarBtn').addEventListener('click', loadEvents);
    document.getElementById('googleSyncBtn').addEventListener('click', async () => {
      try {
        const response = await fetch('../shared/auth/calendar_api.php?action=google_auth_url');
        const data = await response.json();
        if (data.success && data.url) {
          window.location.href = data.url;
          return;
        }
        syncDescription.textContent = data.error || 'Google Calendar authorization is unavailable.';
      } catch (error) {
        syncDescription.textContent = 'Unable to start Google Calendar authorization.';
      }
    });
    document.getElementById('viewAllBtn').addEventListener('click', () => {
      state.selectedCategories = new Set(Object.keys(categoryMap));
      document.querySelectorAll('.category-check').forEach(check => {
        check.checked = true;
      });
      refreshCalendarUi();
    });

    document.querySelectorAll('[data-close="eventModal"]').forEach(el => {
      el.addEventListener('click', closeEventModal);
    });

    eventModal.addEventListener('click', event => {
      if (event.target === eventModal) closeEventModal();
    });
    eventForm.addEventListener('submit', saveEvent);

    document.querySelectorAll('.category-check').forEach(check => {
      check.addEventListener('change', () => {
        const value = check.value;
        if (check.checked) {
          state.selectedCategories.add(value);
        } else {
          state.selectedCategories.delete(value);
        }
        if (state.selectedCategories.size === 0) {
          state.selectedCategories = new Set(Object.keys(categoryMap));
          document.querySelectorAll('.category-check').forEach(item => { item.checked = true; });
        }
        refreshCalendarUi();
      });
    });

    document.getElementById('logout-sidebar').addEventListener('click', () => {
      fetch('../shared/auth/logout.php', { method: 'POST' }).finally(() => { window.location.href = '../../index.html'; });
    });
    document.getElementById('logout-menu').addEventListener('click', () => {
      fetch('../shared/auth/logout.php', { method: 'POST' }).finally(() => { window.location.href = '../../index.html'; });
    });

    refreshCalendarUi();
    applyView('month');
    refreshGoogleSync();
    loadEvents();
  </script>
</body>
</html>

<?php
require_once __DIR__ . '/../shared/auth/middleware.php';
require_role('FO USER');

$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);
$userName = $_SESSION['username'] ?? 'FO User';

function ensure_fo_settings_columns(PDO $pdo): void {
    try {
        $columns = array_map('strtolower', $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN, 0));
    } catch (Throwable $e) {
        return;
    }

    $adds = [
        ['full_name', "ALTER TABLE users ADD COLUMN full_name VARCHAR(150) NULL AFTER email"],
        ['profile_photo', "ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL AFTER full_name"],
        ['contact_number', "ALTER TABLE users ADD COLUMN contact_number VARCHAR(30) NULL AFTER profile_photo"],
        ['email_notifications', "ALTER TABLE users ADD COLUMN email_notifications TINYINT(1) NOT NULL DEFAULT 1 AFTER contact_number"],
        ['language', "ALTER TABLE users ADD COLUMN language VARCHAR(50) NOT NULL DEFAULT 'English' AFTER email_notifications"],
        ['timezone', "ALTER TABLE users ADD COLUMN timezone VARCHAR(80) NOT NULL DEFAULT 'Asia/Manila' AFTER language"],
        ['date_format', "ALTER TABLE users ADD COLUMN date_format VARCHAR(50) NOT NULL DEFAULT 'August 17, 2026' AFTER timezone"],
        ['time_format', "ALTER TABLE users ADD COLUMN time_format VARCHAR(50) NOT NULL DEFAULT '12-hour (AM/PM)' AFTER date_format"],
        ['dashboard_theme', "ALTER TABLE users ADD COLUMN dashboard_theme VARCHAR(30) NOT NULL DEFAULT 'Light' AFTER time_format"],
        ['is_active', "ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER dashboard_theme"],
        ['office_name', "ALTER TABLE users ADD COLUMN office_name VARCHAR(150) NULL AFTER role"],
        ['office_id', "ALTER TABLE users ADD COLUMN office_id INT NULL AFTER office_name"],
    ];

    foreach ($adds as [$column, $sql]) {
        if (!in_array($column, $columns, true)) {
            try {
                $pdo->exec($sql);
                $columns[] = $column;
            } catch (Throwable $e) {
                // ignore migration errors; page keeps safe defaults if the column is unavailable
            }
        }
    }
}

ensure_fo_settings_columns($pdo);

$stmt = $pdo->prepare('SELECT id, username, email, role, full_name, profile_photo, contact_number, office_name, office_id, created_at, updated_at, email_notifications, language, timezone, date_format, time_format, dashboard_theme, is_active FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

if (!$user) {
    header('Location: index.html');
    exit;
}

$user['full_name'] = trim((string)($user['full_name'] ?? '')) ?: trim((string)$userName ?: 'Field Office User');
$user['profile_photo'] = trim((string)($user['profile_photo'] ?? '')) ?: '';
$user['contact_number'] = trim((string)($user['contact_number'] ?? '')) ?: 'Not provided';
$user['email_notifications'] = (int)($user['email_notifications'] ?? 1) === 1;
$user['language'] = trim((string)($user['language'] ?? 'English')) ?: 'English';
$user['timezone'] = trim((string)($user['timezone'] ?? 'Asia/Manila')) ?: 'Asia/Manila';
$user['date_format'] = trim((string)($user['date_format'] ?? 'August 17, 2026')) ?: 'August 17, 2026';
$user['time_format'] = trim((string)($user['time_format'] ?? '12-hour (AM/PM)')) ?: '12-hour (AM/PM)';
$user['dashboard_theme'] = trim((string)($user['dashboard_theme'] ?? 'Light')) ?: 'Light';
$user['is_active'] = (int)($user['is_active'] ?? 1);
$user['office_name'] = trim((string)($user['office_name'] ?? '')) ?: 'Field Office';

$initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $user['full_name'] ?: $user['username']), 0, 2) ?: 'FO');

try {
    $notificationStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
    $notificationStmt->execute([':uid' => $userId]);
    $notificationCount = (int)$notificationStmt->fetchColumn();
} catch (Throwable $e) {
    $notificationCount = 0;
}

try {
    $loginStmt = $pdo->prepare("SELECT created_at FROM audit_logs WHERE user_id = :uid AND action = 'login' ORDER BY created_at DESC LIMIT 1");
    $loginStmt->execute([':uid' => $userId]);
    $lastLogin = $loginStmt->fetchColumn();
    $lastLogin = $lastLogin ? date('F j, Y \\a\\t g:i A', strtotime((string)$lastLogin)) : 'Not available yet';
} catch (Throwable $e) {
    $lastLogin = 'Not available yet';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FO User Settings - DILG Portal</title>
  <style>
    :root {
      --green-900: #0d3e32;
      --green-800: #114d40;
      --green-700: #1c5f4f;
      --green-600: #2f725f;
      --green-500: #2ea77d;
      --green-soft: #ebf7f2;
      --bg: #edf2f1;
      --panel: #ffffff;
      --panel-soft: #f8faf9;
      --line: rgba(15, 62, 50, 0.09);
      --shadow: 0 18px 36px rgba(12, 35, 30, 0.08);
      --text: #1d2a2b;
      --muted: #6e7a7d;
      --blue: #2d80f2;
      --red: #e54848;
      --yellow: #f7b94a;
      --success: #1c8b5b;
      --danger-soft: #fdf0f0;
      --green-pill: #eafaf3;
      --sidebar-text: rgba(255,255,255,0.9);
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: var(--bg);
      color: var(--text);
    }
    a { text-decoration: none; color: inherit; }
    button, input, select { font: inherit; }
    button { cursor: pointer; }
    img { max-width: 100%; display: block; }

    .shell {
      display: flex;
      min-height: 100vh;
      background: linear-gradient(180deg, #edf3f2 0%, #f7f9f8 100%);
    }

    .sidebar {
      width: 270px;
      background: linear-gradient(180deg, #0b3d91 0%, #1456a0 100%);
      color: white;
      display: flex;
      flex-direction: column;
      padding: 18px 14px 14px;
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
      width: 46px;
      height: 46px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.10);
      font-size: 1.05rem;
      font-weight: 800;
    }

    .brand-copy strong {
      display: block;
      font-size: 1.05rem;
      letter-spacing: 0.06em;
      line-height: 1.1;
    }
    .brand-copy span {
      display: block;
      font-size: 0.67rem;
      opacity: 0.8;
      margin-top: 4px;
      line-height: 1.4;
    }

    .user-summary {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 18px 6px 12px;
      padding: 10px 8px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 14px;
    }

    .user-avatar {
      width: 36px;
      height: 36px;
      flex: 0 0 36px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, #e5f7f1, #d5f4e5);
      color: var(--green-900);
      font-weight: 800;
      overflow: hidden;
      border: 2px solid rgba(255,255,255,0.15);
    }
    .user-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .status-row {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.7rem;
      opacity: 0.9;
      color: rgba(255,255,255,0.85);
    }
    .status-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #36d07a;
      box-shadow: 0 0 0 2px rgba(54,208,122,0.18);
    }
    .user-role {
      font-size: 0.8rem;
      font-weight: 700;
      margin-top: 3px;
    }
    .user-office {
      max-width: 150px;
      margin-top: 2px;
      color: rgba(255,255,255,0.82);
      font-size: 0.68rem;
      line-height: 1.2;
    }

    .nav {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-top: 8px;
      padding: 0 4px;
    }

    .nav-item {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      border: 0;
      background: transparent;
      color: var(--sidebar-text);
      border-radius: 12px;
      padding: 12px 10px;
      text-align: left;
      transition: 0.2s ease;
      font-weight: 600;
    }
    .nav-item:hover { background: rgba(255,255,255,0.06); }
    .nav-item.active {
      background: rgba(255,255,255,0.12);
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.06);
    }
    .nav-item .nav-main {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      min-width: 0;
    }
    .nav-icon {
      width: 18px;
      text-align: center;
      flex-shrink: 0;
      color: #fff;
    }
    .nav-icon svg {
      width: 17px;
      height: 17px;
      fill: none;
      stroke: #fff;
      stroke-width: 1.8;
      stroke-linecap: round;
      stroke-linejoin: round;
      vertical-align: middle;
    }
    .nav-label {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .nav-badge {
      min-width: 22px;
      padding: 0 6px;
      height: 20px;
      border-radius: 999px;
      background: #f5c761;
      color: #0e2d22;
      display: grid;
      place-items: center;
      font-size: 0.7rem;
      font-weight: 800;
    }
    .nav-item.active .nav-badge { background: #f1d98a; }

    .logout-row {
      margin-top: auto;
      padding-top: 12px;
      border-top: 1px solid rgba(255,255,255,0.12);
    }

    .main-panel {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
    }

    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 14px 22px;
      background: rgba(255,255,255,0.9);
      border-bottom: 1px solid var(--line);
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .topbar-left {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }
    .menu-btn {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      border: 1px solid var(--line);
      background: #fff;
      color: var(--text);
      font-size: 1.1rem;
      display: none;
    }
    .welcome-tag {
      min-width: 0;
    }
    .welcome-tag h1 {
      margin: 0;
      font-size: clamp(1.25rem, 2vw, 2rem);
      color: var(--text);
    }
    .welcome-tag p {
      margin: 4px 0 0;
      color: var(--muted);
      font-size: 0.93rem;
    }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-left: auto;
    }

    .search-box {
      display: flex;
      align-items: center;
      gap: 8px;
      min-width: 260px;
      background: #f5f8f7;
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 9px 12px;
      color: var(--muted);
    }
    .search-box input {
      border: 0;
      background: transparent;
      outline: none;
      width: 100%;
      color: var(--text);
      font: inherit;
    }

    .notif-btn {
      position: relative;
      width: 42px;
      height: 42px;
      border-radius: 50%;
      border: 1px solid var(--line);
      background: #fff;
      color: var(--text);
      display: grid;
      place-items: center;
      font-size: 1.08rem;
    }
    .notif-count {
      position: absolute;
      top: -4px;
      right: -4px;
      min-width: 20px;
      height: 20px;
      border-radius: 50%;
      background: var(--red);
      color: #fff;
      display: grid;
      place-items: center;
      font-size: 0.7rem;
      font-weight: 800;
      padding: 0 5px;
    }

    .user-mini {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 6px 10px;
    }
    .user-mini .avatar-small {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      font-size: 0.75rem;
      font-weight: 800;
      color: #fff;
      background: linear-gradient(135deg, #174d40, #2ea77d);
      overflow: hidden;
    }
    .user-mini .avatar-small img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .user-mini-wrap {
      display: flex;
      flex-direction: column;
      line-height: 1.2;
    }
    .user-mini-wrap strong {
      font-size: 0.82rem;
      color: var(--text);
    }
    .user-mini-wrap span {
      font-size: 0.67rem;
      color: var(--muted);
    }

    .content {
      padding: 22px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .page-header {
      background: rgba(255,255,255,0.88);
      border: 1px solid var(--line);
      border-radius: 18px;
      padding: 20px 22px;
      box-shadow: 0 8px 18px rgba(12, 25, 20, 0.03);
      margin-bottom: 18px;
    }
    .page-header h2 {
      margin: 0;
      font-size: clamp(1.7rem, 2vw, 2.5rem);
      color: var(--text);
    }
    .page-header p {
      margin: 8px 0 0;
      color: var(--muted);
      font-size: 0.97rem;
    }

    .workspace {
      display: grid;
      grid-template-columns: minmax(0, 2.1fr) minmax(270px, 0.8fr);
      gap: 20px;
      align-items: start;
      flex: 1;
    }

    .panel {
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 18px;
      box-shadow: var(--shadow);
      padding: 16px 18px 18px;
    }

    .tabs {
      display: inline-flex;
      gap: 8px;
      padding: 4px;
      border: 1px solid var(--line);
      background: #f6f9f8;
      border-radius: 12px;
      margin-bottom: 16px;
      flex-wrap: wrap;
    }
    .tab-btn {
      border: 0;
      background: transparent;
      padding: 10px 16px;
      border-radius: 10px;
      color: var(--muted);
      font-weight: 700;
      min-width: 120px;
    }
    .tab-btn.active {
      background: #fff;
      color: var(--green-700);
      box-shadow: 0 3px 12px rgba(12,25,20,0.04);
      border: 1px solid rgba(18,92,77,0.08);
    }

    .tab-panel {
      display: none;
    }
    .tab-panel.active {
      display: block;
    }

    .section-card {
      border: 1px solid var(--line);
      border-radius: 14px;
      background: #fff;
      padding: 14px 16px;
      margin-bottom: 16px;
    }
    .section-card:last-child { margin-bottom: 0; }
    .card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 14px;
    }
    .card-title {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.05rem;
      font-weight: 800;
      color: var(--text);
    }
    .card-title .icon-wrap {
      width: 30px;
      height: 30px;
      border-radius: 10px;
      display: grid;
      place-items: center;
      background: #eef9f4;
      color: var(--green-700);
    }

    .primary-btn, .secondary-btn, .danger-btn, .ghost-btn, .pill-btn {
      border-radius: 10px;
      padding: 10px 16px;
      font-weight: 700;
      border: 1px solid transparent;
      transition: transform 0.2s ease;
    }
    .primary-btn:hover, .secondary-btn:hover, .danger-btn:hover, .ghost-btn:hover, .pill-btn:hover { transform: translateY(-1px); }
    .primary-btn {
      background: var(--green-700);
      color: #fff;
    }
    .secondary-btn {
      background: #fff;
      color: var(--green-700);
      border-color: rgba(27, 94, 79, 0.2);
    }
    .danger-btn {
      background: #fff;
      color: var(--red);
      border-color: rgba(229, 72, 72, 0.2);
    }
    .ghost-btn {
      border-color: rgba(10, 91, 76, 0.15);
      background: #fff;
      color: var(--green-700);
    }
    .pill-btn {
      background: var(--green-pill);
      border-color: rgba(29, 120, 89, 0.12);
      color: var(--green-700);
    }
    .primary-btn:disabled, .secondary-btn:disabled, .danger-btn:disabled {
      opacity: 0.65;
      cursor: not-allowed;
      transform: none;
    }

    .account-grid {
      display: grid;
      grid-template-columns: 170px minmax(0, 1fr);
      gap: 18px;
      align-items: start;
    }
    .avatar-box {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
      padding-top: 8px;
    }
    .main-avatar {
      width: 116px;
      height: 116px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      overflow: hidden;
      background: linear-gradient(135deg, #1f6960, #2da57a);
      color: #fff;
      border: 4px solid #eefaf5;
      box-shadow: 0 12px 22px rgba(12, 63, 52, 0.14);
      font-size: 2.3rem;
      font-weight: 800;
    }
    .main-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .detail-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px 18px;
    }
    .detail-item {
      display: flex;
      flex-direction: column;
      gap: 5px;
      min-width: 0;
    }
    .detail-label {
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--muted);
      font-weight: 700;
    }
    .detail-value {
      color: var(--text);
      font-size: 1rem;
      font-weight: 600;
      word-break: break-word;
    }
    .role-chip {
      display: inline-flex;
      align-items: center;
      width: fit-content;
      background: rgba(29, 102, 80, 0.08);
      color: var(--green-700);
      border-radius: 999px;
      padding: 5px 10px;
      font-size: 0.74rem;
      letter-spacing: 0.04em;
      font-weight: 800;
    }
    .status-chip {
      display: inline-flex;
      align-items: center;
      width: fit-content;
      border-radius: 999px;
      padding: 5px 10px;
      font-size: 0.7rem;
      font-weight: 800;
      letter-spacing: 0.04em;
    }
    .status-chip.active {
      background: rgba(28, 139, 91, 0.12);
      color: var(--success);
    }
    .status-chip.inactive {
      background: rgba(229, 72, 72, 0.08);
      color: var(--red);
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
    }
    .field-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
      min-width: 0;
    }
    .field-group.full {
      grid-column: 1 / -1;
    }
    .field-label {
      font-size: 0.8rem;
      color: var(--muted);
      font-weight: 700;
    }
    .input-wrap {
      border: 1px solid var(--line);
      background: #fff;
      border-radius: 10px;
      padding: 10px 12px;
      min-height: 46px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .input-wrap input, .input-wrap select {
      border: 0;
      background: transparent;
      width: 100%;
      color: var(--text);
      outline: none;
      font: inherit;
    }
    .password-input {
      position: relative;
    }
    .password-input input {
      padding-right: 38px;
    }
    .toggle-password {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      border: 0;
      background: transparent;
      color: var(--blue);
      cursor: pointer;
      width: 28px;
      height: 28px;
      padding: 5px;
      line-height: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .toggle-password svg {
      width: 18px;
      height: 18px;
    }
    .form-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 14px;
    }

    .switch-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      min-height: 46px;
    }
    .switch {
      position: relative;
      width: 48px;
      height: 28px;
      border-radius: 999px;
      background: #dfe8e7;
      display: inline-block;
      transition: 0.2s ease;
    }
    .switch input {
      position: absolute;
      inset: 0;
      opacity: 0;
      z-index: 2;
      cursor: pointer;
    }
    .switch .slider {
      position: absolute;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: #fff;
      top: 4px;
      left: 4px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
      transition: 0.2s ease;
    }
    .switch.on {
      background: #1dbb73;
    }
    .switch.on .slider {
      transform: translateX(20px);
    }

    .actions-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-top: 8px;
    }
    .action-item {
      width: 100%;
      border: 1px solid var(--line);
      background: #fff;
      border-radius: 12px;
      padding: 14px 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      text-align: left;
    }
    .action-item.download { border-left: 4px solid #7bbcff; background: #f4fbff; }
    .action-item.change { border-left: 4px solid #8fd7b6; background: #f3fff8; }
    .action-item.deactivate { border-left: 4px solid #f5b8b8; background: #fff6f6; }
    .action-main {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .action-icon {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      display: grid;
      place-items: center;
      background: rgba(255,255,255,0.8);
      border: 1px solid rgba(11,30,25,0.06);
      font-size: 1rem;
    }
    .action-text {
      font-weight: 700;
      color: var(--text);
    }
    .action-sub {
      font-size: 0.78rem;
      color: var(--muted);
      margin-top: 2px;
    }
    .chevron {
      color: var(--muted);
      font-size: 1.25rem;
      line-height: 1;
    }

    .help-box {
      margin-top: 18px;
      border: 1px solid var(--line);
      border-radius: 14px;
      background: #f5faf7;
      padding: 14px 16px;
    }
    .help-box h3 {
      margin: 0 0 8px;
      font-size: 1.05rem;
      color: var(--text);
    }
    .help-box p {
      margin: 0;
      color: var(--muted);
      font-size: 0.93rem;
      line-height: 1.6;
    }
    .support-btn {
      margin-top: 12px;
      width: 100%;
      border-radius: 10px;
      background: #dff3ea;
      color: var(--green-700);
      border: 1px solid rgba(27, 101, 77, 0.15);
      padding: 10px 12px;
      font-weight: 800;
    }

    .status-message {
      display: none;
      margin-top: 12px;
      padding: 10px 12px;
      border-radius: 10px;
      font-size: 0.92rem;
      font-weight: 600;
    }
    .status-message.show { display: block; }
    .status-message.success {
      background: #eefaf3;
      color: #136d4a;
      border: 1px solid rgba(29,187,115,0.12);
    }
    .status-message.error {
      background: #fff0f0;
      color: #b64040;
      border: 1px solid rgba(229,72,72,0.12);
    }

    .activity-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-top: 8px;
    }
    .activity-item {
      display: grid;
      grid-template-columns: auto 1fr auto;
      gap: 12px;
      align-items: center;
      background: #f9fbfa;
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 12px 14px;
    }
    .activity-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: var(--green-500);
      display: inline-block;
    }
    .activity-info strong {
      display: block;
      font-size: 0.96rem;
      color: var(--text);
    }
    .activity-info span {
      display: block;
      color: var(--muted);
      font-size: 0.78rem;
      margin-top: 4px;
    }
    .activity-state {
      border-radius: 999px;
      background: var(--green-pill);
      color: var(--green-700);
      font-size: 0.72rem;
      font-weight: 800;
      padding: 5px 8px;
    }

    .empty-state {
      border: 1px dashed var(--line);
      border-radius: 12px;
      padding: 20px 18px;
      text-align: center;
      color: var(--muted);
      background: #fafcfb;
    }

    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(10, 17, 16, 0.4);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
      z-index: 50;
    }
    .modal-backdrop.open { display: flex; }
    .modal {
      width: min(460px, 100%);
      background: #fff;
      border-radius: 18px;
      border: 1px solid var(--line);
      box-shadow: var(--shadow);
      overflow: hidden;
    }
    .modal-header {
      padding: 18px 18px 10px;
      font-weight: 800;
      font-size: 1.12rem;
      color: var(--text);
    }
    .modal-body {
      padding: 0 18px 18px;
      color: var(--muted);
      line-height: 1.6;
    }
    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      padding: 0 18px 18px;
    }

    @media (max-width: 1120px) {
      .workspace { grid-template-columns: 1fr; }
    }
    @media (max-width: 920px) {
      .shell { flex-direction: column; }
      .sidebar { width: 100%; }
      .nav { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .search-box { min-width: 180px; }
    }
    @media (max-width: 680px) {
      .content { padding: 16px; }
      .topbar { padding: 12px 16px; }
      .menu-btn { display: inline-block; }
      .search-box { display: none; }
      .welcome-tag p { display: none; }
      .page-header { padding: 16px 18px; }
      .tabs { width: 100%; }
      .tab-btn { flex: 1 1 110px; min-width: 0; }
      .account-grid, .detail-grid, .form-grid { grid-template-columns: 1fr; }
      .user-mini { display: none; }
    }
  </style>
</head>
<body>
  <div class="shell">
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-mark">D</div>
        <div class="brand-copy">
          <strong>DILG PORTAL</strong>
          <span>Department of the Interior and Local Governance</span>
        </div>
      </div>

      <div class="user-summary">
        <div class="user-avatar" id="sidebarAvatarWrap">
          <?php if (!empty($user['profile_photo'])): ?>
            <img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile photo" />
          <?php else: ?>
            <?= htmlspecialchars($initials) ?>
          <?php endif; ?>
        </div>
        <div>
          <div class="status-row"><span class="status-dot"></span> Online</div>
          <div class="user-role"><?= htmlspecialchars($user['username']) ?></div>
          <div class="user-office" id="sidebarUserOffice"><?= htmlspecialchars($user['office_name']) ?></div>
        </div>
      </div>

      <nav class="nav" aria-label="FO User navigation">
        <button class="nav-item" type="button" onclick="window.location.href='dashboard.php'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5M5.5 10.5V20h13v-9.5M9 20v-5h6v5"/></svg></span><span class="nav-label">Dashboard</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='inquiries.php'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6M9 17h6"/></svg></span><span class="nav-label">My Submissions</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='../../news.html'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11 16 6v12L4 13zM4 11v2M16 9h3a2 2 0 0 1 0 4h-3"/></svg></span><span class="nav-label">Announcements</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='calendar.php'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg></span><span class="nav-label">Calendar &amp; Activities</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='notifications.php'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></span><span class="nav-label">Notifications</span></span><span class="nav-badge"><?= htmlspecialchars((string)$notificationCount) ?></span></button>
        <button class="nav-item active" type="button" onclick="window.location.href='settings.php'"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19 15l1 1-2 2-1-1M9 19H7v-3M5 9l-1-1 2-2 1 1M15 5V3h2v2"/></svg></span><span class="nav-label">Profile Settings</span></span></button>
        <div class="logout-row">
          <button class="nav-item" type="button" id="sidebarLogoutBtn"><span class="nav-main"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/></svg></span><span class="nav-label">Logout</span></span></button>
        </div>
      </nav>
    </aside>

    <div class="main-panel">
      <header class="topbar">
        <div class="topbar-left">
          <button class="menu-btn" type="button" aria-label="Toggle navigation">?</button>
          <div class="welcome-tag">
            <h1>FO User Dashboard</h1>
          </div>
        </div>

        <div class="topbar-right">
          <label class="search-box" aria-label="Search dashboard">
            <span><svg class="control-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg></span>
            <input type="search" placeholder="Search inquiries, documents..." />
          </label>

          <button class="notif-btn" type="button" aria-label="Notifications">
            <svg class="control-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
            <?php if ((int)$notificationCount > 0): ?><span class="notif-count"><?= htmlspecialchars((string)$notificationCount) ?></span><?php endif; ?>
          </button>

          <div class="user-mini">
            <div class="avatar-small" id="topHeaderAvatar">
              <?php if (!empty($user['profile_photo'])): ?>
                <img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile photo" />
              <?php else: ?>
                <?= htmlspecialchars($initials) ?>
              <?php endif; ?>
            </div>
            <div class="user-mini-wrap">
              <strong id="headerUserName"><?= htmlspecialchars($user['full_name']) ?></strong>
              <span>Field Office User</span>
            </div>
          </div>
        </div>
      </header>

      <main class="content">
        <?php if (($_GET['force_password_change'] ?? '') === '1'): ?>
          <p role="alert" style="padding:12px 16px;border:1px solid #e8c76a;border-radius:8px;background:#fff8db;color:#604b12;">A temporary password is active. Change it in the Security tab before continuing.</p>
        <?php endif; ?>
        <div class="page-header">
          <h2>Settings</h2>
          <p>Manage your account information, preferences, and security settings.</p>
        </div>

        <div class="workspace">
          <section class="panel">
            <div class="tabs" role="tablist" aria-label="Settings sections">
              <button class="tab-btn active" type="button" data-tab="account" aria-selected="true">Account Information</button>
              <button class="tab-btn" type="button" data-tab="preferences" aria-selected="false">Preferences</button>
              <button class="tab-btn" type="button" data-tab="security" aria-selected="false">Security</button>
              <button class="tab-btn" type="button" data-tab="activity" aria-selected="false">Activity Log</button>
            </div>

            <div id="tab-account" class="tab-panel active" role="tabpanel">
              <div class="section-card">
                <div class="card-head">
                  <div class="card-title"><span class="icon-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/></svg></span> Account Information</div>
                  <button class="secondary-btn" type="button" id="editProfileBtn">Edit Profile</button>
                </div>

                <div class="account-grid">
                  <div class="avatar-box">
                    <div class="main-avatar" id="mainAvatarImage">
                      <?php if (!empty($user['profile_photo'])): ?>
                        <img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile photo" />
                      <?php else: ?>
                        <?= htmlspecialchars($initials) ?>
                      <?php endif; ?>
                    </div>
                    <button class="ghost-btn" type="button" id="changePhotoBtn">Upload Photo</button>
                    <input id="avatarInput" type="file" accept="image/png,image/jpeg,image/webp" hidden />
                  </div>

                  <div class="detail-grid">
                    <div class="detail-item">
                      <div class="detail-label">Username</div>
                      <div class="detail-value" id="fieldUsername"><?= htmlspecialchars($user['username']) ?></div>
                    </div>
                    <div class="detail-item">
                      <div class="detail-label">Email Address</div>
                      <div class="detail-value" id="fieldEmail"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <div class="detail-item">
                      <div class="detail-label">Full Name</div>
                      <div class="detail-value" id="fieldName"><?= htmlspecialchars($user['full_name']) ?></div>
                    </div>
                    <div class="detail-item">
                      <div class="detail-label">Role</div>
                      <div class="detail-value"><span class="role-chip" id="fieldRole"><?= htmlspecialchars($user['role']) ?></span></div>
                    </div>
                          <div class="detail-item">
                      <div class="detail-label">Office / Unit</div>
                            <div class="detail-value" id="fieldOffice"><?= htmlspecialchars($user['office_name']) ?></div>
                    </div>
                    <div class="detail-item">
                      <div class="detail-label">Account Status</div>
                      <div class="detail-value"><span class="status-chip <?= $user['is_active'] ? 'active' : 'inactive' ?>"><?= $user['is_active'] ? 'Active' : 'Inactive' ?></span></div>
                    </div>
                    <div class="detail-item">
                      <div class="detail-label">Date Created</div>
                      <div class="detail-value"><?= htmlspecialchars(date('F j, Y', strtotime($user['created_at'] ?? 'now'))) ?></div>
                    </div>
                    <div class="detail-item">
                      <div class="detail-label">Last Login</div>
                      <div class="detail-value"><?= htmlspecialchars($lastLogin) ?></div>
                    </div>
                    <div class="detail-item">
                      <div class="detail-label">Contact Number</div>
                      <div class="detail-value" id="fieldContact"><?= htmlspecialchars($user['contact_number']) ?></div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="section-card" id="profileEditor" style="display:none;">
                <div class="card-head">
                  <div class="card-title"><span class="icon-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4"/></svg></span> Edit Profile</div>
                </div>
                <form id="profileForm">
                  <div class="form-grid">
                    <div class="field-group">
                      <label class="field-label" for="profileFullName">Full Name</label>
                      <div class="input-wrap">
                        <input id="profileFullName" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required />
                      </div>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="profileUsername">Username</label>
                      <div class="input-wrap">
                        <input id="profileUsername" name="username" value="<?= htmlspecialchars($user['username']) ?>" required />
                      </div>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="profileEmail">Email Address</label>
                      <div class="input-wrap">
                        <input id="profileEmail" name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" required />
                      </div>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="profileContact">Contact Number</label>
                      <div class="input-wrap">
                        <input id="profileContact" name="contact_number" value="<?= htmlspecialchars($user['contact_number'] === 'Not provided' ? '' : $user['contact_number']) ?>" placeholder="Enter contact number" />
                      </div>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="profileOffice">Assigned Office / Unit</label>
                      <div class="input-wrap">
                        <input id="profileOffice" value="<?= htmlspecialchars($user['office_name']) ?>" readonly aria-readonly="true" />
                      </div>
                    </div>
                  </div>
                  <div class="form-actions">
                    <button class="primary-btn" type="submit">Save Changes</button>
                    <button class="secondary-btn" type="button" id="cancelProfileEdit">Cancel</button>
                  </div>
                  <div id="profileMessage" class="status-message"></div>
                </form>
              </div>
            </div>

            <div id="tab-preferences" class="tab-panel" role="tabpanel">
              <div class="section-card">
                <div class="card-head">
                  <div class="card-title"><span class="icon-wrap">?</span> Preferences</div>
                </div>
                <form id="preferencesForm">
                  <div class="form-grid">
                    <div class="field-group full">
                      <div class="switch-row">
                        <div>
                          <div style="font-weight:800; color:var(--text);">Email Notifications</div>
                          <small style="color:var(--muted);">Receive important system updates and notices.</small>
                        </div>
                        <label class="switch <?= $user['email_notifications'] ? 'on' : '' ?>" aria-label="Toggle email notifications">
                          <input type="checkbox" id="emailNotifications" <?= $user['email_notifications'] ? 'checked' : '' ?> />
                          <span class="slider"></span>
                        </label>
                      </div>
                    </div>

                    <div class="field-group">
                      <label class="field-label" for="languageSelect">Language</label>
                      <div class="input-wrap">
                        <select id="languageSelect">
                          <option value="English" <?= $user['language'] === 'English' ? 'selected' : '' ?>>English</option>
                          <option value="Filipino" <?= $user['language'] === 'Filipino' ? 'selected' : '' ?>>Filipino</option>
                          <option value="Bisaya" <?= $user['language'] === 'Bisaya' ? 'selected' : '' ?>>Bisaya</option>
                        </select>
                      </div>
                    </div>

                    <div class="field-group">
                      <label class="field-label" for="timezoneSelect">Timezone</label>
                      <div class="input-wrap">
                        <select id="timezoneSelect">
                          <option value="Asia/Manila" <?= $user['timezone'] === 'Asia/Manila' ? 'selected' : '' ?>>GMT+08:00) Asia/Manila</option>
                          <option value="UTC" <?= $user['timezone'] === 'UTC' ? 'selected' : '' ?>>UTC</option>
                          <option value="Asia/Singapore" <?= $user['timezone'] === 'Asia/Singapore' ? 'selected' : '' ?>>Asia/Singapore</option>
                          <option value="Asia/Tokyo" <?= $user['timezone'] === 'Asia/Tokyo' ? 'selected' : '' ?>>Asia/Tokyo</option>
                        </select>
                      </div>
                    </div>

                    <div class="field-group">
                      <label class="field-label" for="dateFormatSelect">Date Format</label>
                      <div class="input-wrap">
                        <select id="dateFormatSelect">
                          <option value="August 17, 2026" <?= $user['date_format'] === 'August 17, 2026' ? 'selected' : '' ?>>August 17, 2026</option>
                          <option value="17/08/2026" <?= $user['date_format'] === '17/08/2026' ? 'selected' : '' ?>>17/08/2026</option>
                          <option value="08/17/2026" <?= $user['date_format'] === '08/17/2026' ? 'selected' : '' ?>>08/17/2026</option>
                        </select>
                      </div>
                    </div>

                    <div class="field-group">
                      <label class="field-label" for="timeFormatSelect">Time Format</label>
                      <div class="input-wrap">
                        <select id="timeFormatSelect">
                          <option value="12-hour (AM/PM)" <?= $user['time_format'] === '12-hour (AM/PM)' ? 'selected' : '' ?>>12-hour (AM/PM)</option>
                          <option value="24-hour (HH:MM)" <?= $user['time_format'] === '24-hour (HH:MM)' ? 'selected' : '' ?>>24-hour (HH:MM)</option>
                        </select>
                      </div>
                    </div>

                    <div class="field-group">
                      <label class="field-label" for="themeSelect">Dashboard Theme</label>
                      <div class="input-wrap">
                        <select id="themeSelect">
                          <option value="Light" <?= $user['dashboard_theme'] === 'Light' ? 'selected' : '' ?>>Light</option>
                          <option value="Dark" <?= $user['dashboard_theme'] === 'Dark' ? 'selected' : '' ?>>Dark</option>
                          <option value="Default" <?= $user['dashboard_theme'] === 'Default' ? 'selected' : '' ?>>Default</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div class="form-actions">
                    <button class="primary-btn" type="submit">Save Preferences</button>
                  </div>
                  <div id="preferencesMessage" class="status-message"></div>
                </form>
              </div>
            </div>

            <div id="tab-security" class="tab-panel" role="tabpanel">
              <div class="section-card">
                <div class="card-head">
                  <div class="card-title"><span class="icon-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span> Security</div>
                </div>
                <div class="form-grid">
                  <div class="field-group">
                    <div class="field-label">Security Status</div>
                    <div class="input-wrap" style="justify-content:space-between;">
                      <span>Account protection is healthy</span>
                      <span class="status-chip active">Secure</span>
                    </div>
                  </div>
                  <div class="field-group">
                    <div class="field-label">Last Password Change</div>
                    <div class="input-wrap">
                      <span>Recently updated</span>
                    </div>
                  </div>
                </div>

                <form id="passwordForm" style="margin-top:18px;">
                  <div class="form-grid">
                    <div class="field-group">
                      <label class="field-label" for="currentPassword">Current Password</label>
                      <div class="input-wrap password-input">
                        <input id="currentPassword" type="password" placeholder="Enter current password" required />
                        <button type="button" class="toggle-password" data-target="currentPassword" aria-label="Show password"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button>
                      </div>
                    </div>
                    <div class="field-group"></div>
                    <div class="field-group">
                      <label class="field-label" for="newPassword">New Password</label>
                      <div class="input-wrap password-input">
                        <input id="newPassword" type="password" placeholder="Enter new password" required minlength="8" />
                        <button type="button" class="toggle-password" data-target="newPassword" aria-label="Show password"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button>
                      </div>
                    </div>
                    <div class="field-group">
                      <label class="field-label" for="confirmPassword">Confirm New Password</label>
                      <div class="input-wrap password-input">
                        <input id="confirmPassword" type="password" placeholder="Confirm new password" required minlength="8" />
                        <button type="button" class="toggle-password" data-target="confirmPassword" aria-label="Show password"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button>
                      </div>
                    </div>
                  </div>
                  <div class="form-actions">
                    <button class="primary-btn" type="submit">Update Password</button>
                  </div>
                  <div id="passwordMessage" class="status-message"></div>
                </form>
              </div>
            </div>

            <div id="tab-activity" class="tab-panel" role="tabpanel">
              <div class="section-card">
                <div class="card-head">
                  <div class="card-title"><span class="icon-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12v18H6zM9 7h6M9 11h6M9 15h4"/></svg></span> Activity Log</div>
                </div>
                <div id="activityList" class="activity-list"></div>
              </div>
            </div>
          </section>

          <aside class="panel">
            <div class="card-head" style="margin-bottom:16px;">
              <div class="card-title"><span class="icon-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m13 2-9 12h7l-1 8 9-12h-7z"/></svg></span> Quick Actions</div>
            </div>

            <div class="actions-list">
              <button type="button" class="action-item change" id="quickEditProfile">
                <div class="action-main">
                  <span class="action-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4"/></svg></span>
                  <div>
                    <div class="action-text">Edit Profile</div>
                    <div class="action-sub">Update your account information</div>
                  </div>
                </div>
                <span class="chevron">&rsaquo;</span>
              </button>

              <button type="button" class="action-item change" id="quickPasswordChange">
                <div class="action-main">
                  <span class="action-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
                  <div>
                    <div class="action-text">Change Password</div>
                    <div class="action-sub">Update your account password</div>
                  </div>
                </div>
                <span class="chevron">&rsaquo;</span>
              </button>

              <button type="button" class="action-item download" id="quickDownloadData">
                <div class="action-main">
                  <span class="action-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v10M8 10l4 4 4-4M5 19h14"/></svg></span>
                  <div>
                    <div class="action-text">Download My Data</div>
                    <div class="action-sub">Export your account data</div>
                  </div>
                </div>
                <span class="chevron">&rsaquo;</span>
              </button>

              <button type="button" class="action-item deactivate" id="quickDeactivate">
                <div class="action-main">
                  <span class="action-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="m8 8 8 8"/></svg></span>
                  <div>
                    <div class="action-text">Deactivate Account</div>
                    <div class="action-sub">Temporarily disable access</div>
                  </div>
                </div>
                <span class="chevron">&rsaquo;</span>
              </button>
            </div>

            <div class="help-box">
              <h3>Need Help?</h3>
              <p>If you need assistance with your account, settings, or support team, please contact the DILG assistance desk.</p>
              <button type="button" class="support-btn" id="contactSupportBtn">Contact Support</button>
            </div>
          </aside>
        </div>
      </main>
    </div>
  </div>

  <div id="confirmModal" class="modal-backdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
      <div class="modal-header" id="confirmTitle">Confirm action</div>
      <div class="modal-body" id="confirmText">Are you sure you want to continue?</div>
      <div class="modal-actions">
        <button type="button" class="secondary-btn" id="modalCancelBtn">Cancel</button>
        <button type="button" class="primary-btn" id="modalConfirmBtn">Proceed</button>
      </div>
    </div>
  </div>

  <script>
    const state = {
      activeTab: 'account',
      pendingAction: null,
      currentUser: null
    };

    function showMessage(el, type, message) {
      if (!el) return;
      el.className = 'status-message show ' + type;
      el.textContent = message;
    }

    function setTab(tabName) {
      state.activeTab = tabName;
      document.querySelectorAll('.tab-btn').forEach((btn) => {
        const active = btn.dataset.tab === tabName;
        btn.classList.toggle('active', active);
        btn.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      document.querySelectorAll('.tab-panel').forEach((panel) => {
        panel.classList.toggle('active', panel.id === 'tab-' + tabName);
      });
    }

    document.querySelectorAll('#passwordForm .toggle-password').forEach((button) => {
      button.addEventListener('click', () => {
        const target = document.getElementById(button.dataset.target);
        const isHidden = target.type === 'password';
        target.type = isHidden ? 'text' : 'password';
        button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        button.innerHTML = isHidden
          ? '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3 3 18 18M10.6 6.2A9.8 9.8 0 0 1 12 6c6.5 0 10 6 10 6a17.7 17.7 0 0 1-3.1 3.8M6.2 6.2C3.6 8.2 2 12 2 12s3.5 6 10 6c1.5 0 2.8-.3 4-.8M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>'
          : '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>';
      });
    });

    function applyUserState(user) {
      state.currentUser = user;
      const fullName = user.full_name || user.username || 'Field Office User';
      const initials = (fullName.replace(/[^A-Za-z0-9]/g, '').slice(0, 2) || 'FO').toUpperCase();

      document.getElementById('fieldUsername').textContent = user.username || '';
      document.getElementById('fieldEmail').textContent = user.email || '';
      document.getElementById('fieldName').textContent = fullName;
      document.getElementById('fieldRole').textContent = user.role || 'FO USER';
      document.getElementById('fieldOffice').textContent = user.office_name || 'Field Office';
      document.getElementById('fieldContact').textContent = (user.contact_number && user.contact_number !== 'Not provided') ? user.contact_number : 'Not provided';
      document.getElementById('headerUserName').textContent = fullName;
      document.getElementById('sidebarUserOffice').textContent = user.office_name || 'Field Office';
      document.getElementById('profileFullName').value = fullName;
      document.getElementById('profileUsername').value = user.username || '';
      document.getElementById('profileEmail').value = user.email || '';
      document.getElementById('profileContact').value = user.contact_number && user.contact_number !== 'Not provided' ? user.contact_number : '';
      document.getElementById('profileOffice').value = user.office_name || 'Field Office';

      const topAvatar = document.getElementById('topHeaderAvatar');
      const sideAvatar = document.getElementById('sidebarAvatarWrap');
      const mainAvatar = document.getElementById('mainAvatarImage');
      if (user.profile_photo) {
        const photoUrl = user.profile_photo + (user.profile_photo.includes('?') ? '&' : '?') + 'v=' + Date.now();
        topAvatar.innerHTML = '<img src="' + photoUrl + '" alt="Profile photo" />';
        sideAvatar.innerHTML = '<img src="' + photoUrl + '" alt="Profile photo" />';
        mainAvatar.innerHTML = '<img src="' + photoUrl + '" alt="Profile photo" />';
      } else {
        topAvatar.textContent = initials;
        sideAvatar.textContent = initials;
        mainAvatar.textContent = initials;
      }

      const statusChip = document.querySelector('.status-chip');
      if (statusChip) {
        statusChip.textContent = user.is_active ? 'Active' : 'Inactive';
        statusChip.classList.toggle('active', !!user.is_active);
        statusChip.classList.toggle('inactive', !user.is_active);
      }
    }

    async function fetchProfile() {
      try {
        const response = await fetch('../shared/auth/settings_api.php?action=profile');
        const result = await response.json();
        if (!response.ok || !result.success || !result.user) {
          throw new Error(result.message || 'Unable to load account details.');
        }
        applyUserState(result.user);
      } catch (error) {
        console.error(error);
      }
    }

    async function loadActivityLog() {
      const activityList = document.getElementById('activityList');
      activityList.innerHTML = '<div class="empty-state">Loading activity...</div>';
      try {
        const response = await fetch('../shared/auth/settings_api.php?action=activity_log&limit=8');
        const result = await response.json();
        if (!response.ok || !result.success || !Array.isArray(result.activities)) {
          throw new Error(result.message || 'Unable to load activity log.');
        }

        if (!result.activities.length) {
          activityList.innerHTML = '<div class="empty-state">No account activity has been recorded yet.</div>';
          return;
        }

        activityList.innerHTML = result.activities.map((activity) => {
          const label = activity.label || 'Account activity';
          const ts = activity.created_at ? new Date(activity.created_at).toLocaleString() : 'Unknown time';
          const stateText = activity.status || 'Completed';
          return `
            <div class="activity-item">
              <span class="activity-dot"></span>
              <div class="activity-info">
                <strong>${label}</strong>
                <span>${ts}${activity.device ? ' | ' + activity.device : ''}</span>
              </div>
              <span class="activity-state">${stateText}</span>
            </div>
          `;
        }).join('');
      } catch (error) {
        console.error(error);
        activityList.innerHTML = '<div class="empty-state">Unable to load recent activity right now.</div>';
      }
    }

    const modal = document.getElementById('confirmModal');
    const modalText = document.getElementById('confirmText');
    const modalConfirmBtn = document.getElementById('modalConfirmBtn');
    const modalCancelBtn = document.getElementById('modalCancelBtn');

    function openModal(message, callback) {
      modalText.textContent = message;
      state.pendingAction = callback;
      modal.classList.add('open');
    }

    function closeModal() {
      modal.classList.remove('open');
      state.pendingAction = null;
    }

    modalConfirmBtn.addEventListener('click', () => {
      if (typeof state.pendingAction === 'function') {
        state.pendingAction();
      }
      closeModal();
    });
    modalCancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
      if (event.target === modal) closeModal();
    });

    document.querySelectorAll('.tab-btn').forEach((button) => {
      button.addEventListener('click', () => setTab(button.dataset.tab));
    });

    document.getElementById('editProfileBtn').addEventListener('click', () => {
      setTab('account');
      document.getElementById('profileEditor').style.display = 'block';
    });
    document.getElementById('cancelProfileEdit').addEventListener('click', () => {
      document.getElementById('profileEditor').style.display = 'none';
      const profileForm = document.getElementById('profileForm');
      if (state.currentUser) {
        profileForm.reset();
        document.getElementById('profileFullName').value = state.currentUser.full_name || '';
        document.getElementById('profileUsername').value = state.currentUser.username || '';
        document.getElementById('profileEmail').value = state.currentUser.email || '';
        document.getElementById('profileContact').value = state.currentUser.contact_number && state.currentUser.contact_number !== 'Not provided' ? state.currentUser.contact_number : '';
      }
    });

    document.getElementById('quickEditProfile').addEventListener('click', () => {
      setTab('account');
      document.getElementById('profileEditor').style.display = 'block';
      document.getElementById('profileFullName').focus();
    });
    document.getElementById('quickPasswordChange').addEventListener('click', () => setTab('security'));
    document.getElementById('quickDownloadData').addEventListener('click', () => {
      window.location.href = '../shared/auth/settings_api.php?action=download_data';
    });
    document.getElementById('quickDeactivate').addEventListener('click', () => {
      openModal('This will deactivate your account and sign you out. You can contact support to reactivate it later.', async () => {
        try {
          const response = await fetch('../shared/auth/settings_api.php?action=deactivate', { method: 'POST' });
          const result = await response.json();
          if (!response.ok || !result.success) {
            throw new Error(result.message || 'Unable to deactivate account.');
          }
          window.location.href = '../../index.html';
        } catch (error) {
          alert(error.message || 'Unable to deactivate account.');
        }
      });
    });

    document.getElementById('contactSupportBtn').addEventListener('click', () => {
      window.location.href = 'help.php';
    });

    document.getElementById('sidebarLogoutBtn').addEventListener('click', () => {
      fetch('../shared/auth/logout.php', { method: 'POST' }).finally(() => {
        window.location.href = '../../index.html';
      });
    });

    document.getElementById('profileForm').addEventListener('submit', async (event) => {
      event.preventDefault();
      const payload = {
        full_name: document.getElementById('profileFullName').value.trim(),
        username: document.getElementById('profileUsername').value.trim(),
        email: document.getElementById('profileEmail').value.trim(),
        contact_number: document.getElementById('profileContact').value.trim()
      };

      try {
        const response = await fetch('../shared/auth/settings_api.php?action=save_profile', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Unable to save profile changes.');
        }
        await fetchProfile();
        document.getElementById('profileEditor').style.display = 'none';
        showMessage(document.getElementById('profileMessage'), 'success', result.message || 'Profile updated successfully.');
      } catch (error) {
        showMessage(document.getElementById('profileMessage'), 'error', error.message || 'Unable to save profile changes.');
      }
    });

    document.getElementById('preferencesForm').addEventListener('submit', async (event) => {
      event.preventDefault();
      const payload = {
        email_notifications: document.getElementById('emailNotifications').checked ? 1 : 0,
        language: document.getElementById('languageSelect').value,
        timezone: document.getElementById('timezoneSelect').value,
        date_format: document.getElementById('dateFormatSelect').value,
        time_format: document.getElementById('timeFormatSelect').value,
        dashboard_theme: document.getElementById('themeSelect').value
      };

      try {
        const response = await fetch('../shared/auth/settings_api.php?action=save_preferences', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Unable to save preferences.');
        }
        showMessage(document.getElementById('preferencesMessage'), 'success', result.message || 'Preferences saved successfully.');
      } catch (error) {
        showMessage(document.getElementById('preferencesMessage'), 'error', error.message || 'Unable to save preferences.');
      }
    });

    document.getElementById('passwordForm').addEventListener('submit', async (event) => {
      event.preventDefault();
      const currentPassword = document.getElementById('currentPassword').value;
      const newPassword = document.getElementById('newPassword').value;
      const confirmPassword = document.getElementById('confirmPassword').value;

      if (newPassword.length < 8) {
        showMessage(document.getElementById('passwordMessage'), 'error', 'New password must be at least 8 characters long.');
        return;
      }
      if (!/[A-Za-z]/.test(newPassword) || !/[0-9]/.test(newPassword)) {
        showMessage(document.getElementById('passwordMessage'), 'error', 'New password must include both letters and numbers.');
        return;
      }
      if (newPassword !== confirmPassword) {
        showMessage(document.getElementById('passwordMessage'), 'error', 'New password and confirmation do not match.');
        return;
      }

      try {
        const response = await fetch('../shared/auth/settings_api.php?action=change_password', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            current_password: currentPassword,
            new_password: newPassword,
            confirm_password: confirmPassword
          })
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Unable to update password.');
        }
        document.getElementById('passwordForm').reset();
        showMessage(document.getElementById('passwordMessage'), 'success', result.message || 'Password updated successfully.');
      } catch (error) {
        showMessage(document.getElementById('passwordMessage'), 'error', error.message || 'Unable to update password.');
      }
    });

    document.getElementById('changePhotoBtn').addEventListener('click', () => {
      document.getElementById('avatarInput').click();
    });

    document.getElementById('avatarInput').addEventListener('change', async (event) => {
      const file = event.target.files && event.target.files[0];
      if (!file) return;

      if (file.size > 5 * 1024 * 1024) {
        alert('Profile photo must be 5 MB or smaller.');
        event.target.value = '';
        return;
      }
      const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
      if (!allowedTypes.includes(file.type)) {
        alert('Only JPG, PNG, or WebP images are allowed.');
        event.target.value = '';
        return;
      }

      const formData = new FormData();
      formData.append('photo', file);

      try {
        const response = await fetch('../shared/auth/settings_api.php?action=upload_avatar', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Unable to upload profile photo.');
        }
        await fetchProfile();
        showMessage(document.getElementById('profileMessage'), 'success', result.message || 'Profile photo updated.');
      } catch (error) {
        alert(error.message || 'Unable to upload profile photo.');
      } finally {
        event.target.value = '';
      }
    });

    document.getElementById('emailNotifications').addEventListener('change', function () {
      this.closest('.switch').classList.toggle('on', this.checked);
    });

    setTab('account');
    fetchProfile().then(() => {
      if (new URLSearchParams(window.location.search).get('edit') === '1') {
        document.getElementById('profileEditor').style.display = 'block';
        document.getElementById('profileFullName').focus();
      }
    });
    loadActivityLog();
  </script>
</body>
</html>

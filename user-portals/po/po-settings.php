<?php
require_once __DIR__ . '/../shared/auth/middleware.php';
require_once __DIR__ . '/../shared/auth/po_icons.php';
require_once __DIR__ . '/../shared/auth/workflow.php';
require_role('PO USER');

$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);
workflow_ensure_schema($pdo);
workflow_sync_overdue($pdo);

function ensure_user_settings_columns(PDO $pdo): void {
    try {
        $columns = array_map('strtolower', $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN, 0));
    } catch (Throwable $e) {
        return;
    }

    $sqls = [
        ['full_name', "ALTER TABLE users ADD COLUMN full_name VARCHAR(150) NULL AFTER email"],
        ['profile_photo', "ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL AFTER full_name"],
        ['email_notifications', "ALTER TABLE users ADD COLUMN email_notifications TINYINT(1) NOT NULL DEFAULT 1 AFTER profile_photo"],
        ['language', "ALTER TABLE users ADD COLUMN language VARCHAR(50) NOT NULL DEFAULT 'English' AFTER email_notifications"],
        ['date_format', "ALTER TABLE users ADD COLUMN date_format VARCHAR(50) NOT NULL DEFAULT 'August 17, 2026' AFTER language"],
        ['time_format', "ALTER TABLE users ADD COLUMN time_format VARCHAR(50) NOT NULL DEFAULT '12-hour (AM/PM)' AFTER date_format"],
        ['dashboard_theme', "ALTER TABLE users ADD COLUMN dashboard_theme VARCHAR(30) NOT NULL DEFAULT 'Light' AFTER time_format"],
        ['is_active', "ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER dashboard_theme"],
    ];

    foreach ($sqls as [$column, $alterSql]) {
        if (!in_array($column, $columns, true)) {
            try {
                $pdo->exec($alterSql);
                $columns[] = $column;
            } catch (Throwable $e) {
                // ignore migration errors; the page will continue with safe defaults
            }
        }
    }
}

ensure_user_settings_columns($pdo);

$stmt = $pdo->prepare('SELECT id, username, email, role, full_name, profile_photo, created_at, email_notifications, language, date_format, time_format, dashboard_theme, is_active FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

if (!$user) {
    header('Location: index.html');
    exit;
}

$user['full_name'] = trim((string)($user['full_name'] ?? '')) ?: (trim((string)($_SESSION['username'] ?? '')) ?: 'Provincial Office User');
$user['profile_photo'] = trim((string)($user['profile_photo'] ?? '')) ?: '';
$user['email_notifications'] = (int)($user['email_notifications'] ?? 1) === 1;
$user['language'] = trim((string)($user['language'] ?? 'English')) ?: 'English';
$user['date_format'] = trim((string)($user['date_format'] ?? 'August 17, 2026')) ?: 'August 17, 2026';
$user['time_format'] = trim((string)($user['time_format'] ?? '12-hour (AM/PM)')) ?: '12-hour (AM/PM)';
$user['dashboard_theme'] = trim((string)($user['dashboard_theme'] ?? 'Light')) ?: 'Light';
$user['is_active'] = (int)($user['is_active'] ?? 1);
$displayUserName = trim((string)($_SESSION['username'] ?? '')) ?: $user['full_name'];

$initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $user['full_name'] ?: $user['username']), 0, 2) ?: 'PO');
$notificationStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
$notificationStmt->execute([':uid' => $userId]);
$notificationCount = (int)$notificationStmt->fetchColumn();
$submissionStatsStmt = $pdo->prepare("SELECT CASE WHEN r.fo_response_at IS NOT NULL AND r.due_at IS NOT NULL AND r.fo_response_at > r.due_at THEN 'OVERDUE' ELSE r.status END AS status, r.recipient_office, (SELECT COUNT(*) FROM report_recipients rr WHERE rr.report_id = r.id) AS recipient_count, (SELECT COUNT(*) FROM report_responses rp WHERE rp.report_id = r.id AND rp.response_at IS NOT NULL) AS response_count, (SELECT COUNT(*) FROM report_responses rp WHERE rp.report_id = r.id AND rp.response_at > r.due_at) AS overdue_response_count FROM reports r WHERE EXISTS (SELECT 1 FROM users u WHERE u.id = r.user_id AND u.role = 'PO USER')");
$submissionStatsStmt->execute();
$submissionStats = ['TOTAL' => 0, 'PENDING' => 0, 'SUBMITTED' => 0, 'OVERDUE' => 0];
foreach ($submissionStatsStmt->fetchAll(PDO::FETCH_ASSOC) as $submissionStat) {
  $status = strtoupper((string)($submissionStat['status'] ?? ''));
  if (($submissionStat['recipient_office'] ?? '') === 'All Municipalities / Cities' && (int)$submissionStat['recipient_count'] > 0) {
    $submissionStats['PENDING'] += max(0, (int)$submissionStat['recipient_count'] - (int)$submissionStat['response_count']);
    $overdueResponses = (int)$submissionStat['overdue_response_count'];
    $submissionStats['OVERDUE'] += $overdueResponses;
    $submissionStats['SUBMITTED'] += max(0, (int)$submissionStat['response_count'] - $overdueResponses);
  } elseif ($status === 'PENDING') {
    $submissionStats['PENDING']++;
  } elseif ($status === 'SUBMITTED') {
    $submissionStats['SUBMITTED']++;
  } elseif ($status === 'OVERDUE') {
    $submissionStats['OVERDUE']++;
  }
}
$submissionStats['TOTAL'] = $submissionStats['PENDING'] + $submissionStats['SUBMITTED'] + $submissionStats['OVERDUE'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Settings - DILG PO Dashboard</title>
  <style>
    :root {
      --po-bg: #f4f7fb;
      --po-surface: #ffffff;
      --po-sidebar: #5c2d17;
      --po-sidebar-deep: #3d1f11;
      --po-accent: #2d7ff9;
      --po-accent-soft: #edf4ff;
      --po-heading: #1f2d3d;
      --po-text: #243447;
      --po-muted: #68768d;
      --po-border: #e7edf4;
      --po-shadow: 0 12px 28px rgba(31, 42, 61, 0.08);
      --po-green: #2bb673;
      --po-red: #df5b5b;
      --po-gold: #f5a623;
      --po-amber-soft: #fff8e8;
      --po-orange-soft: #fff1ea;
      --po-gray-soft: #f6f8fb;
      --po-success-bg: #edf9f3;
      --po-error-bg: #fdeaea;
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      font-family: "Segoe UI", Arial, sans-serif;
      background: var(--po-bg);
      color: var(--po-text);
    }
    a { text-decoration: none; color: inherit; }
    button, input, select { font: inherit; }
    button { cursor: pointer; }

    .po-shell,
    .shell {
      display: flex;
      min-height: 100vh;
      background: linear-gradient(180deg, #f7f8fc 0%, #f2f5fa 100%);
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
      background: var(--po-gold);
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
      font-weight: 700;
      opacity: 0.95;
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
      display: grid;
      place-items: center;
      font-weight: 800;
    }

    .title-copy { min-width: 0; }
    .title-copy strong {
      display: block;
      font-size: 0.9rem;
      color: var(--po-heading);
    }
    .title-copy span {
      display: block;
      font-size: 0.72rem;
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
      font-size: 0.65rem;
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
      color: var(--po-text);
    }
    .avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, #1eb7d8, #0d8ecd);
      color: #fff;
      font-weight: 800;
      font-size: 0.82rem;
      overflow: hidden;
    }
    .avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .profile-meta { display: flex; flex-direction: column; line-height: 1.2; text-align: left; }
    .profile-meta strong { font-size: 0.82rem; }
    .profile-meta span { font-size: 0.68rem; color: var(--po-muted); }

    .page-inner {
      padding: 22px;
    }

    .settings-header {
      margin-bottom: 18px;
      padding: 16px 18px;
      background: rgba(255,255,255,0.8);
      border: 1px solid var(--po-border);
      border-radius: 16px;
      box-shadow: 0 8px 18px rgba(31,42,61,.04);
    }

    .header-row {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .header-icon {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      background: #edf4ff;
      color: var(--po-accent);
      display: grid;
      place-items: center;
      font-size: 1.7rem;
    }
    .settings-header h1 {
      margin: 0;
      font-size: clamp(1.8rem, 2.5vw, 2.5rem);
      color: var(--po-heading);
    }
    .breadcrumb {
      margin-top: 6px;
      color: var(--po-muted);
      font-size: 0.82rem;
    }
    .page-subtitle {
      margin: 12px 0 0;
      color: var(--po-muted);
      font-size: 0.95rem;
    }

    .settings-layout {
      display: grid;
      grid-template-columns: minmax(0, 1.8fr) minmax(280px, 0.9fr);
      gap: 20px;
      align-items: start;
    }
    .settings-panel {
      background: var(--po-surface);
      border: 1px solid var(--po-border);
      border-radius: 18px;
      box-shadow: var(--po-shadow);
      padding: 16px 18px 18px;
    }
    .settings-tabs {
      display: inline-flex;
      gap: 8px;
      background: #f5f7fb;
      border: 1px solid var(--po-border);
      border-radius: 12px;
      padding: 4px; 
      width: fit-content;
      margin-bottom: 18px;
    }
    .tab-btn {
      border: 0;
      background: transparent;
      color: var(--po-muted);
      padding: 10px 18px;
      border-radius: 10px;
      font-weight: 700;
      min-width: 110px;
    }
    .tab-btn.active {
      background: #fff;
      color: var(--po-accent);
      box-shadow: 0 2px 8px rgba(31,42,61,0.05);
      border: 1px solid rgba(45,127,249,.12);
    }

    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    .quick-overview { margin-top: 16px; }
    .overview-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 8px; }
    .overview-card { display: block; padding: 10px; border: 1px solid var(--po-border); border-radius: 10px; background: #fbfcfe; color: var(--po-heading); text-decoration: none; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
    .overview-card:hover, .overview-card:focus-visible { transform: translateY(-2px); border-color: var(--po-accent); box-shadow: 0 8px 18px rgba(31,42,61,.08); outline: none; }
    .overview-card .overview-icon { width: 30px; height: 30px; display: grid; place-items: center; border-radius: 9px; margin-bottom: 7px; background: #f3f7ff; color: var(--po-accent); }
    .overview-card .overview-icon svg { width: 16px; height: 16px; }
    .overview-card.pending .overview-icon { background: var(--po-amber-soft); color: var(--po-gold); }
    .overview-card.submitted .overview-icon { background: var(--po-success-bg); color: var(--po-green); }
    .overview-card.notifications .overview-icon { background: var(--po-orange-soft); color: var(--po-red); }
    .overview-value { display: block; font-size: 1.4rem; font-weight: 800; line-height: 1; }
    .overview-label { display: block; margin-top: 5px; color: var(--po-muted); font-size: .68rem; font-weight: 700; }
    .overview-link { display: block; margin-top: 6px; color: var(--po-accent); font-size: .64rem; font-weight: 700; }
    .security-tips { margin-top: 16px; }
    .security-tip { display: flex; align-items: flex-start; gap: 10px; padding: 11px 0; border-top: 1px solid var(--po-border); color: var(--po-heading); font-size: .76rem; text-decoration: none; }
    .security-tip:first-of-type { border-top: 0; }
    .security-tip svg { width: 17px; height: 17px; color: var(--po-accent); flex-shrink: 0; margin-top: 1px; }
    .security-tip strong, .security-tip small { display: block; }
    .security-tip small { margin-top: 3px; color: var(--po-muted); font-size: .67rem; }

    .section-box {
      border: 1px solid var(--po-border);
      border-radius: 14px;
      background: #fff;
      padding: 12px 14px;
      margin-bottom: 16px;
    }
    .section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 12px;
    }
    .section-title {
      display: flex;
      align-items: center;
      gap: 10px;
      color: var(--po-heading);
      font-size: 1.05rem;
      font-weight: 700;
    }
    .section-icon {
      width: 30px;
      height: 30px;
      border-radius: 10px;
      display: grid;
      place-items: center;
      background: #f3f7ff;
      color: var(--po-accent);
      font-size: 1rem;
    }
    .mini-btn {
      border: 1px solid var(--po-border);
      background: #fff;
      color: var(--po-accent);
      border-radius: 10px;
      padding: 8px 12px;
      font-weight: 700;
    }

    .account-grid {
      display: grid;
      grid-template-columns: 170px minmax(0, 1fr);
      gap: 18px;
      align-items: start;
      padding-top: 4px;
    }
    .avatar-box {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
      padding-top: 8px;
    }
    .profile-avatar-large {
      width: 118px;
      height: 118px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, #18a9d2, #0e8ec9);
      color: white;
      font-size: 2.4rem;
      font-weight: 800;
      overflow: hidden;
      box-shadow: 0 12px 18px rgba(32,116,191,.18);
      border: 4px solid #edf5ff;
    }
    .profile-avatar-large img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .change-photo-btn {
      border: 1px solid var(--po-accent);
      background: #fff;
      color: var(--po-accent);
      border-radius: 999px;
      padding: 6px 14px;
      font-weight: 700;
    }
    .details-list {
      display: grid;
      grid-template-columns: repeat(2, minmax(0,1fr));
      gap: 14px 18px;
      padding-top: 4px;
    }
    .detail-item {
      display: flex;
      flex-direction: column;
      gap: 4px;
      min-width: 0;
    }
    .detail-label {
      font-size: 0.78rem;
      color: var(--po-muted);
      text-transform: uppercase;
      letter-spacing: 0.04em;
      font-weight: 700;
    }
    .detail-value {
      font-size: 1rem;
      color: var(--po-text);
      font-weight: 600;
      word-break: break-word;
    }
    .role-badge {
      display: inline-flex;
      align-items: center;
      width: fit-content;
      background: #eef4ff;
      color: var(--po-accent);
      border-radius: 999px;
      padding: 6px 10px;
      font-size: 0.76rem;
      font-weight: 800;
      letter-spacing: 0.04em;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0,1fr));
      gap: 14px;
    }
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
      min-width: 0;
    }
    .form-group.full {
      grid-column: 1 / -1;
    }
    .form-label {
      color: var(--po-muted);
      font-size: 0.82rem;
      font-weight: 700;
    }
    .input-wrap {
      display: flex;
      align-items: center;
      gap: 8px;
      border: 1px solid var(--po-border);
      background: #fff;
      padding: 10px 12px;
      border-radius: 10px;
      min-height: 46px;
    }
    .input-wrap input,
    .input-wrap select {
      border: 0;
      background: transparent;
      width: 100%;
      outline: none;
      color: var(--po-text);
      font-size: 0.96rem;
    }
    .toggle-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 0 2px;
      min-height: 40px;
      color: var(--po-heading);
      font-weight: 600;
    }
    .switch {
      position: relative;
      width: 48px;
      height: 28px;
      border-radius: 999px;
      background: #d8e0ea;
      display: inline-block;
      transition: all 0.2s ease;
      box-shadow: inset 0 0 0 1px rgba(15,23,42,0.05);
    }
    .switch input {
      position: absolute;
      inset: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
      z-index: 2;
    }
    .switch .slider {
      position: absolute;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: #fff;
      top: 4px;
      left: 4px;
      transition: all 0.2s ease;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .switch input:checked + .slider {
      transform: translateX(20px);
    }
    .switch.on {
      background: var(--po-green);
    }

    .action-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-top: 6px;
    }
    .action-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 14px 14px;
      border: 1px solid var(--po-border);
      border-left: 4px solid transparent;
      border-radius: 12px;
      background: #fff;
      transition: border-color .2s ease;
    }
    .action-item.download { border-left-color: #7db6ff; background: #f3faff; }
    .action-item.session { border-left-color: #f3b24a; background: #fffaf1; }
    .action-item.deactivate { border-left-color: #f17676; background: #fff3f3; }
    .action-main {
      display: flex;
      align-items: center;
      gap: 10px;
      color: var(--po-heading);
      font-weight: 700;
    }
    .action-icon {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      display: grid;
      place-items: center;
      background: rgba(255,255,255,0.9);
      border: 1px solid rgba(17,24,39,0.06);
      font-size: 1rem;
    }
    .action-sub {
      color: var(--po-muted);
      font-size: 0.8rem;
      margin-top: 2px;
    }
    .arrow {
      font-size: 1.2rem;
      color: var(--po-muted);
    }

    .primary-btn, .secondary-btn, .danger-btn {
      border-radius: 10px;
      padding: 11px 16px;
      font-weight: 700;
      border: 1px solid transparent;
      transition: all 0.2s ease;
    }
    .primary-btn {
      background: var(--po-accent);
      color: #fff;
    }
    .secondary-btn {
      background: #fff;
      color: var(--po-accent);
      border-color: var(--po-accent);
    }
    .danger-btn {
      background: #fff;
      color: var(--po-red);
      border-color: #f3c1c1;
    }
    .primary-btn:hover, .secondary-btn:hover, .danger-btn:hover {
      transform: translateY(-1px);
    }
    .primary-btn:disabled, .secondary-btn:disabled, .danger-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    .form-actions {
      display: flex;
      gap: 10px;
      justify-content: flex-start;
      margin-top: 8px;
      flex-wrap: wrap;
    }

    .status-message {
      margin-top: 10px;
      padding: 11px 12px;
      border-radius: 10px;
      display: none;
      font-size: 0.92rem;
      font-weight: 600;
    }
    .status-message.show { display: block; }
    .status-message.success {
      display: block;
      background: var(--po-success-bg);
      color: #166f47;
      border: 1px solid rgba(43,182,115,0.15);
    }
    .status-message.error {
      display: block;
      background: var(--po-error-bg);
      color: #a63c3c;
      border: 1px solid rgba(223,91,91,0.15);
    }

    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(17,24,39,0.45);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
      z-index: 40;
    }
    .modal-backdrop.open { display: flex; }
    .modal {
      width: min(480px, 100%);
      background: #fff;
      border: 1px solid var(--po-border);
      border-radius: 18px;
      box-shadow: var(--po-shadow);
      overflow: hidden;
    }
    .modal-header {
      padding: 18px 18px 10px;
      font-weight: 800;
      color: var(--po-heading);
      font-size: 1.1rem;
    }
    .modal-body {
      padding: 6px 18px 18px;
      color: var(--po-muted);
      line-height: 1.6;
    }
    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      padding: 0 18px 18px;
    }
    .hidden-file-input {
      display: none;
    }

    .security-box {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .password-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
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
      color: var(--po-accent);
      border-radius: 5px;
      padding: 5px 7px;
      font-size: 0.75rem;
      font-weight: 700;
      line-height: 1;
      cursor: pointer;
    }
    .toggle-password.is-visible {
      background: #e8f0ff;
    }

    .footer {
      text-align: center;
      color: var(--po-muted);
      font-size: 0.8rem;
      padding: 12px 0 0;
      margin-top: 10px;
    }

    @media (max-width: 1120px) {
      .settings-layout { grid-template-columns: 1fr; }
    }
    @media (max-width: 980px) {
      .po-shell,
      .shell { flex-direction: column; }
      .po-sidebar,
      .sidebar { width: 100%; }
      .nav { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); }
      .search { min-width: 180px; }
    }
    @media (max-width: 680px) {
      .page-inner { padding: 16px; }
      .topbar { padding: 12px 16px; }
      .topbar-tools { gap: 8px; }
      .search { display: none; }
      .settings-header { padding: 14px 16px; }
      .header-row { flex-direction: column; align-items: flex-start; }
      .account-grid, .details-list, .form-grid, .password-row { grid-template-columns: 1fr; }
      .settings-tabs { width: 100%; }
      .tab-btn { flex: 1; min-width: 0; }
      .overview-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
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

      <div style="display:flex;align-items:center;gap:10px;margin:14px 4px 0;padding:10px 8px;border:1px solid rgba(255,255,255,.12);border-radius:12px;background:rgba(255,255,255,.08);"><div style="width:36px;height:36px;display:grid;place-items:center;flex:0 0 36px;overflow:hidden;border:1px solid rgba(255,255,255,.28);border-radius:50%;background:rgba(255,255,255,.14);font-size:.82rem;font-weight:800;"><?php if (!empty($user['profile_photo'])): ?><img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="PO User profile photo" style="width:100%;height:100%;object-fit:cover;display:block;"><?php else: ?><?= htmlspecialchars($initials) ?><?php endif; ?></div><div style="min-width:0;"><span style="display:flex;align-items:center;gap:6px;font-size:.72rem;color:rgba(255,255,255,.88);"><span style="width:7px;height:7px;border-radius:50%;background:#34d399;"></span>Online</span><span style="display:block;margin-top:3px;color:#fff;font-size:.78rem;font-weight:700;"><?= htmlspecialchars($displayUserName) ?></span></div></div>

      <nav class="nav" aria-label="Primary navigation">
        <button class="nav-item" type="button" onclick="window.location.href='dashboard.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('dashboard') ?></span><span class="nav-label">Dashboard</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='inquiries.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('submissions') ?></span><span class="nav-label">My Submissions</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='office.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('office') ?></span><span class="nav-label">My Office</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='calendar.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('calendar') ?></span><span class="nav-label">Calendar</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='reports.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('reports') ?></span><span class="nav-label">Reports</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='notifications.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('notifications') ?></span><span class="nav-label">Notifications</span></span><span class="nav-badge" id="sidebarBadge"><?= (int)$notificationCount > 0 ? htmlspecialchars((string)$notificationCount) : '' ?></span></button>
        <button class="nav-item active" type="button" onclick="window.location.href='settings.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('settings') ?></span><span class="nav-label">Settings</span></span></button>
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
            <strong>Department of the Interior and Local Government</strong>
            <span>Province of Surigao del Sur &bull; Provincial Office Dashboard</span>
          </div>
        </div>

        <div class="topbar-tools">
          <label class="search" aria-label="Search dashboard">
            <span><?= po_icon('search') ?></span>
            <input id="globalSearch" type="search" placeholder="Search dashboard..." />
          </label>

          <div class="notif" aria-label="Notifications" onclick="window.location.href='notifications.php'" style="cursor:pointer;"><?= po_icon('notifications') ?><span class="counter" id="headerBadge"><?= (int)$notificationCount > 0 ? htmlspecialchars((string)$notificationCount) : '' ?></span></div>

          <div class="profile" aria-expanded="false">
            <span class="avatar" id="topAvatar"><?php if (!empty($user['profile_photo'])): ?><img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile photo"><?php else: ?><?= htmlspecialchars($initials) ?><?php endif; ?></span>
            <span class="profile-meta">
              <strong><?= htmlspecialchars($displayUserName) ?></strong>
              <span>Provincial Office</span>
            </span>
          </div>
        </div>
      </header>

      <main class="page-inner">
        <?php if (($_GET['force_password_change'] ?? '') === '1'): ?>
          <p role="alert" style="padding:12px 16px;border:1px solid #e8c76a;border-radius:8px;background:#fff8db;color:#604b12;">A temporary password is active. Change it in the Security tab before continuing.</p>
        <?php endif; ?>
        <div class="settings-header">
          <div class="header-row">
            <div class="header-icon"><?= po_icon('settings') ?></div>
            <div>
              <h1>Settings</h1>
              <div class="breadcrumb">Dashboard &nbsp;&nbsp;&rsaquo;&nbsp;&nbsp; Settings</div>
            </div>
          </div>
          <p class="page-subtitle">Manage your account information, preferences, and security settings.</p>
        </div>

        <div class="settings-layout">
          <section class="settings-panel">
            <div class="settings-tabs" role="tablist" aria-label="Settings tabs">
              <button class="tab-btn active" type="button" data-tab="account" role="tab" aria-selected="true">Account</button>
              <button class="tab-btn" type="button" data-tab="preferences" role="tab" aria-selected="false">Preferences</button>
              <button class="tab-btn" type="button" data-tab="security" role="tab" aria-selected="false">Security</button>
            </div>

            <div id="tab-account" class="tab-panel active" role="tabpanel">
              <div class="section-box">
                <div class="section-header">
                  <div class="section-title"><span class="section-icon"><?= po_icon('user') ?></span> Account Information</div>
                  <button class="mini-btn" id="editProfileBtn" type="button">Edit Profile</button>
                </div>

                <div class="account-grid">
                  <div class="avatar-box">
                    <div class="profile-avatar-large" id="profileAvatarLarge">
                      <?php if (!empty($user['profile_photo'])): ?>
                        <img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile photo" />
                      <?php else: ?>
                        <?= htmlspecialchars($initials) ?>
                      <?php endif; ?>
                    </div>
                    <button class="change-photo-btn" id="changePhotoBtn" type="button">Change Photo</button>
                    <input type="file" id="photoInput" class="hidden-file-input" accept="image/png,image/jpeg,image/webp" />
                  </div>

                  <div class="details-list">
                    <div class="detail-item">
                      <div class="detail-label">Username</div>
                      <div class="detail-value" id="displayUsername"><?= htmlspecialchars($user['username']) ?></div>
                    </div>
                    <div class="detail-item">
                      <div class="detail-label">Email Address</div>
                      <div class="detail-value" id="displayEmail"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <div class="detail-item">
                      <div class="detail-label">Full Name</div>
                      <div class="detail-value" id="displayFullName"><?= htmlspecialchars($user['full_name']) ?></div>
                    </div>
                    <div class="detail-item">
                      <div class="detail-label">Role</div>
                      <div class="detail-value"><span class="role-badge" id="displayRole"><?= htmlspecialchars($user['role']) ?></span></div>
                    </div>
                    <div class="detail-item">
                      <div class="detail-label">Office</div>
                      <div class="detail-value">DILG Provincial Office</div>
                    </div>
                    <div class="detail-item">
                      <div class="detail-label">Member Since</div>
                      <div class="detail-value"><?= htmlspecialchars(date('F j, Y', strtotime($user['created_at'] ?? 'now'))) ?></div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="section-box" id="profileEditBox" style="display:none;">
                <div class="section-header">
                  <div class="section-title"><span class="section-icon"><?= po_icon('create') ?></span> Edit Profile</div>
                </div>
                <form id="profileForm">
                  <div class="form-grid">
                    <div class="form-group">
                      <label class="form-label" for="profileUsername">Username</label>
                      <div class="input-wrap">
                        <input id="profileUsername" name="username" value="<?= htmlspecialchars($user['username']) ?>" required />
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="form-label" for="profileEmail">Email Address</label>
                      <div class="input-wrap">
                        <input id="profileEmail" name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" required />
                      </div>
                    </div>
                    <div class="form-group full">
                      <label class="form-label" for="profileFullName">Full Name</label>
                      <div class="input-wrap">
                        <input id="profileFullName" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required />
                      </div>
                    </div>
                  </div>
                  <div class="form-actions" style="margin-top:14px;">
                    <button class="primary-btn" type="submit">Save Changes</button>
                    <button class="secondary-btn" type="button" id="cancelEditProfile">Cancel</button>
                  </div>
                  <div id="profileMessage" class="status-message"></div>
                </form>
              </div>

              <div class="section-box quick-overview" aria-labelledby="quickOverviewTitle">
                <div class="section-header">
                  <div class="section-title" id="quickOverviewTitle"><span class="section-icon"><?= po_icon('total') ?></span> Quick Overview</div>
                </div>
                <div class="overview-grid">
                  <a class="overview-card" href="inquiries.php">
                    <span class="overview-icon"><?= po_icon('document') ?></span>
                    <span class="overview-value"><?= (int)$submissionStats['TOTAL'] ?></span>
                    <span class="overview-label">Total Submissions</span>
                    <span class="overview-link">View all</span>
                  </a>
                  <a class="overview-card pending" href="inquiries.php?status=PENDING">
                    <span class="overview-icon"><?= po_icon('pending') ?></span>
                    <span class="overview-value"><?= (int)$submissionStats['PENDING'] ?></span>
                    <span class="overview-label">Pending</span>
                    <span class="overview-link">For review</span>
                  </a>
                  <a class="overview-card submitted" href="inquiries.php?status=SUBMITTED">
                    <span class="overview-icon"><?= po_icon('submitted') ?></span>
                    <span class="overview-value"><?= (int)$submissionStats['SUBMITTED'] ?></span>
                    <span class="overview-label">Submitted</span>
                    <span class="overview-link">Completed</span>
                  </a>
                  <a class="overview-card overdue" href="inquiries.php?status=OVERDUE">
                    <span class="overview-icon"><?= po_icon('overdue') ?></span>
                    <span class="overview-value"><?= (int)$submissionStats['OVERDUE'] ?></span>
                    <span class="overview-label">Overdue</span>
                    <span class="overview-link">Needs attention</span>
                  </a>
                  <a class="overview-card notifications" href="notifications.php">
                    <span class="overview-icon"><?= po_icon('notifications') ?></span>
                    <span class="overview-value"><?= (int)$notificationCount ?></span>
                    <span class="overview-label">Notifications</span>
                    <span class="overview-link">Unread</span>
                  </a>
                </div>
              </div>
            </div>

            <div id="tab-preferences" class="tab-panel" role="tabpanel">
              <div class="section-box">
                <div class="section-header">
                  <div class="section-title"><span class="section-icon"><?= po_icon('settings') ?></span> Preferences</div>
                </div>

                <form id="preferencesForm">
                  <div class="form-grid">
                    <div class="form-group full">
                      <div class="toggle-row">
                        <div>
                          <div style="font-weight:700; color:var(--po-heading);">Notifications</div>
                          <small style="color:var(--po-muted);">Email notifications are enabled</small>
                        </div>
                        <label class="switch <?= $user['email_notifications'] ? 'on' : '' ?>" aria-label="Toggle email notifications">
                          <input type="checkbox" id="emailNotifications" <?= $user['email_notifications'] ? 'checked' : '' ?> />
                          <span class="slider"></span>
                        </label>
                      </div>
                    </div>

                    <div class="form-group">
                      <label class="form-label" for="languageSelect">Language</label>
                      <div class="input-wrap">
                        <select id="languageSelect" name="language">
                          <option value="English" <?= $user['language'] === 'English' ? 'selected' : '' ?>>English</option>
                          <option value="Filipino" <?= $user['language'] === 'Filipino' ? 'selected' : '' ?>>Filipino</option>
                          <option value="Bisaya" <?= $user['language'] === 'Bisaya' ? 'selected' : '' ?>>Bisaya</option>
                        </select>
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="form-label" for="dateFormatSelect">Date Format</label>
                      <div class="input-wrap">
                        <select id="dateFormatSelect" name="date_format">
                          <option value="August 17, 2026" <?= $user['date_format'] === 'August 17, 2026' ? 'selected' : '' ?>>August 17, 2026</option>
                          <option value="17/08/2026" <?= $user['date_format'] === '17/08/2026' ? 'selected' : '' ?>>17/08/2026</option>
                          <option value="08/17/2026" <?= $user['date_format'] === '08/17/2026' ? 'selected' : '' ?>>08/17/2026</option>
                        </select>
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="form-label" for="timeFormatSelect">Time Format</label>
                      <div class="input-wrap">
                        <select id="timeFormatSelect" name="time_format">
                          <option value="12-hour (AM/PM)" <?= $user['time_format'] === '12-hour (AM/PM)' ? 'selected' : '' ?>>12-hour (AM/PM)</option>
                          <option value="24-hour (HH:MM)" <?= $user['time_format'] === '24-hour (HH:MM)' ? 'selected' : '' ?>>24-hour (HH:MM)</option>
                        </select>
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="form-label" for="themeSelect">Dashboard Theme</label>
                      <div class="input-wrap">
                        <select id="themeSelect" name="dashboard_theme">
                          <option value="Light" <?= $user['dashboard_theme'] === 'Light' ? 'selected' : '' ?>>Light</option>
                          <option value="Dark" <?= $user['dashboard_theme'] === 'Dark' ? 'selected' : '' ?>>Dark</option>
                          <option value="Default" <?= $user['dashboard_theme'] === 'Default' ? 'selected' : '' ?>>Default</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  <div class="form-actions" style="margin-top:14px;">
                    <button class="primary-btn" type="submit">Save Preferences</button>
                  </div>
                  <div id="preferencesMessage" class="status-message"></div>
                </form>
              </div>
            </div>

            <div id="tab-security" class="tab-panel" role="tabpanel">
              <div class="section-box security-box">
                <div class="section-header">
                  <div class="section-title"><span class="section-icon"><?= po_icon('lock') ?></span> Change Password</div>
                </div>

                <form id="passwordForm">
                  <div class="password-row">
                    <div class="form-group">
                      <label class="form-label" for="currentPassword">Current Password</label>
                      <div class="input-wrap password-input">
                        <input id="currentPassword" type="password" placeholder="Enter current password" required />
                        <button type="button" class="toggle-password" data-target="currentPassword" aria-label="Show password">Show</button>
                      </div>
                    </div>
                    <div class="form-group"></div>
                    <div class="form-group">
                      <label class="form-label" for="newPassword">New Password</label>
                      <div class="input-wrap password-input">
                        <input id="newPassword" type="password" placeholder="Enter new password" required minlength="8" />
                        <button type="button" class="toggle-password" data-target="newPassword" aria-label="Show password">Show</button>
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="form-label" for="confirmPassword">Confirm New Password</label>
                      <div class="input-wrap password-input">
                        <input id="confirmPassword" type="password" placeholder="Confirm new password" required minlength="8" />
                        <button type="button" class="toggle-password" data-target="confirmPassword" aria-label="Show password">Show</button>
                      </div>
                    </div>
                  </div>
                  <div class="form-actions" style="margin-top:14px;">
                    <button class="primary-btn" type="submit">Update Password</button>
                  </div>
                  <div id="passwordMessage" class="status-message"></div>
                </form>
              </div>
            </div>
          </section>

          <aside class="settings-panel" style="padding:16px;">
            <div class="section-header">
              <div class="section-title"><span class="section-icon"><?= po_icon('shield') ?></span> Account Actions</div>
            </div>
            <div class="action-list">
              <button class="action-item download" type="button" id="downloadDataBtn">
                <div class="action-main">
                    <span class="action-icon"><?= po_icon('download') ?></span>
                  <div>
                    <div>Download Account Data</div>
                    <div class="action-sub">Export your account details</div>
                  </div>
                </div>
                <span class="arrow">&rsaquo;</span>
              </button>

              <button class="action-item session" type="button" id="sessionManagementBtn">
                <div class="action-main">
                  <span class="action-icon"><?= po_icon('document') ?></span>
                  <div>
                    <div>Session Management</div>
                    <div class="action-sub">View and manage active sessions</div>
                  </div>
                </div>
                <span class="arrow">&rsaquo;</span>
              </button>

              <button class="action-item deactivate" type="button" id="deactivateAccountBtn">
                <div class="action-main">
                    <span class="action-icon"><?= po_icon('overdue') ?></span>
                  <div>
                    <div>Deactivate Account</div>
                    <div class="action-sub">Temporarily disable your account</div>
                  </div>
                </div>
                <span class="arrow">&rsaquo;</span>
              </button>
            </div>

            <div class="section-box security-tips" aria-labelledby="securityTipsTitle">
              <div class="section-header">
                <div class="section-title" id="securityTipsTitle"><span class="section-icon"><?= po_icon('shield') ?></span> Security Tips</div>
              </div>
              <a class="security-tip" href="#tab-security" data-settings-tab="security"><span><?= po_icon('lock') ?></span><span><strong>Use a strong password</strong><small>Combine letters, numbers, and symbols.</small></span></a>
              <a class="security-tip" href="#tab-preferences" data-settings-tab="preferences"><span><?= po_icon('shield') ?></span><span><strong>Enable two-factor authentication</strong><small>Review your account preferences.</small></span></a>
              <a class="security-tip" href="notifications.php"><span><?= po_icon('notifications') ?></span><span><strong>Keep your email updated</strong><small>Receive important account notifications.</small></span></a>
              <a class="security-tip" href="#tab-security" data-settings-tab="security"><span><?= po_icon('logout') ?></span><span><strong>Log out on shared devices</strong><small>Protect your account from unauthorized access.</small></span></a>
            </div>
          </aside>
        </div>

        <div class="footer">&copy; 2026 Department of the Interior and Local Governance. All rights reserved.</div>
      </main>
    </div>
  </div>

  <div id="confirmModal" class="modal-backdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
      <div class="modal-header" id="confirmTitle">Confirm Action</div>
      <div class="modal-body" id="confirmText">Are you sure you want to proceed?</div>
      <div class="modal-actions">
        <button class="secondary-btn" type="button" id="confirmCancelBtn">Cancel</button>
        <button class="primary-btn" type="button" id="confirmOkBtn">Confirm</button>
      </div>
    </div>
  </div>

  <script>
    const state = {
      activeTab: 'account',
      pendingAction: null,
      formDirty: false
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

    document.querySelectorAll('.tab-btn').forEach((btn) => {
      btn.addEventListener('click', () => setTab(btn.dataset.tab));
    });

    document.querySelectorAll('[data-settings-tab]').forEach((link) => {
      link.addEventListener('click', (event) => {
        event.preventDefault();
        setTab(link.dataset.settingsTab);
      });
    });

    const profileForm = document.getElementById('profileForm');
    const preferencesForm = document.getElementById('preferencesForm');
    const passwordForm = document.getElementById('passwordForm');
    const profileEditBox = document.getElementById('profileEditBox');

    function toggleEditProfile(show) {
      profileEditBox.style.display = show ? 'block' : 'none';
    }

    function applyProfileToPage(profile) {
      if (!profile) return;
      const username = profile.username || '';
      const email = profile.email || '';
      const fullName = profile.full_name || username || 'Provincial Office User';
      const photo = profile.profile_photo ? profile.profile_photo + (profile.profile_photo.includes('?') ? '&' : '?') + 'v=' + Date.now() : '';
      document.getElementById('displayUsername').textContent = username;
      document.getElementById('displayEmail').textContent = email;
      document.getElementById('displayFullName').textContent = fullName;
      document.getElementById('profileUsername').value = username;
      document.getElementById('profileEmail').value = email;
      document.getElementById('profileFullName').value = fullName;
      document.querySelectorAll('.profile-meta strong').forEach((element) => { element.textContent = username || fullName; });

      const initial = fullName.replace(/[^A-Za-z0-9]/g, '').slice(0, 2).toUpperCase() || 'PO';
      const avatarMarkup = photo
        ? `<img src="${photo}?v=${Date.now()}" alt="Profile photo" />`
        : initial;
      document.getElementById('topAvatar').innerHTML = avatarMarkup;
      document.getElementById('profileAvatarLarge').innerHTML = avatarMarkup;
    }

    document.getElementById('editProfileBtn').addEventListener('click', () => toggleEditProfile(true));
    document.getElementById('cancelEditProfile').addEventListener('click', () => toggleEditProfile(false));

    const modal = document.getElementById('confirmModal');
    const confirmText = document.getElementById('confirmText');
    const confirmOkBtn = document.getElementById('confirmOkBtn');
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');

    function openConfirmModal(message, onConfirm) {
      confirmText.textContent = message;
      state.pendingAction = onConfirm;
      modal.classList.add('open');
    }

    function closeConfirmModal() {
      modal.classList.remove('open');
      state.pendingAction = null;
    }

    confirmCancelBtn.addEventListener('click', closeConfirmModal);
    modal.addEventListener('click', (event) => {
      if (event.target === modal) closeConfirmModal();
    });

    confirmOkBtn.addEventListener('click', () => {
      if (typeof state.pendingAction === 'function') {
        state.pendingAction();
      }
      closeConfirmModal();
    });

    profileForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(profileForm);
      const payload = {
        username: formData.get('username')?.toString().trim(),
        email: formData.get('email')?.toString().trim(),
        full_name: formData.get('full_name')?.toString().trim(),
      };

      try {
        const response = await fetch('../shared/auth/settings_api.php?action=save_profile', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Unable to update profile.');
        }

        applyProfileToPage(data.user || payload);
        showMessage(document.getElementById('profileMessage'), 'success', data.message || 'Profile updated successfully.');
        toggleEditProfile(false);
      } catch (error) {
        showMessage(document.getElementById('profileMessage'), 'error', error.message || 'Unable to update profile.');
      }
    });

    preferencesForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const payload = {
        email_notifications: document.getElementById('emailNotifications').checked ? 1 : 0,
        language: document.getElementById('languageSelect').value,
        date_format: document.getElementById('dateFormatSelect').value,
        time_format: document.getElementById('timeFormatSelect').value,
        dashboard_theme: document.getElementById('themeSelect').value,
      };

      try {
        const response = await fetch('../shared/auth/settings_api.php?action=save_preferences', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Unable to save preferences.');
        }
        showMessage(document.getElementById('preferencesMessage'), 'success', data.message || 'Preferences saved successfully.');
      } catch (error) {
        showMessage(document.getElementById('preferencesMessage'), 'error', error.message || 'Unable to save preferences.');
      }
    });

    passwordForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const current = document.getElementById('currentPassword').value;
      const newPassword = document.getElementById('newPassword').value;
      const confirm = document.getElementById('confirmPassword').value;

      if (newPassword.length < 8) {
        showMessage(document.getElementById('passwordMessage'), 'error', 'New password must be at least 8 characters long.');
        return;
      }
      if (newPassword !== confirm) {
        showMessage(document.getElementById('passwordMessage'), 'error', 'The new password and confirmation do not match.');
        return;
      }

      try {
        const response = await fetch('../shared/auth/settings_api.php?action=change_password', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ current_password: current, new_password: newPassword, confirm_password: confirm })
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Unable to change password.');
        }
        passwordForm.reset();
        showMessage(document.getElementById('passwordMessage'), 'success', data.message || 'Password updated successfully.');
      } catch (error) {
        showMessage(document.getElementById('passwordMessage'), 'error', error.message || 'Unable to change password.');
      }
    });

    document.querySelectorAll('.toggle-password').forEach((button) => {
      button.addEventListener('click', () => {
        const target = document.getElementById(button.dataset.target);
        const isHidden = target.type === 'password';
        target.type = isHidden ? 'text' : 'password';
        button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        button.textContent = isHidden ? 'Hide' : 'Show';
        button.classList.toggle('is-visible', isHidden);
      });
    });

    document.getElementById('changePhotoBtn').addEventListener('click', () => {
      document.getElementById('photoInput').click();
    });

    document.getElementById('photoInput').addEventListener('change', async (event) => {
      const file = event.target.files && event.target.files[0];
      if (!file) return;
      const formData = new FormData();
      formData.append('photo', file);

      try {
        const response = await fetch('../shared/auth/settings_api.php?action=upload_avatar', {
          method: 'POST',
          body: formData
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Unable to upload photo.');
        }

        applyProfileToPage(data.user || { profile_photo: data.photo });
        showMessage(document.getElementById('profileMessage'), 'success', data.message || 'Profile photo updated.');
      } catch (error) {
        showMessage(document.getElementById('profileMessage'), 'error', error.message || 'Unable to upload photo.');
      } finally {
        event.target.value = '';
      }
    });

    document.getElementById('downloadDataBtn').addEventListener('click', () => {
      window.location.href = '../shared/auth/settings_api.php?action=download_data';
    });

    document.getElementById('sessionManagementBtn').addEventListener('click', async () => {
      try {
        const response = await fetch('../shared/auth/settings_api.php?action=sessions');
        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Unable to load sessions.');
        }
        const sessions = data.sessions || [];
        if (!sessions.length) {
          alert('No active saved sessions were found.');
          return;
        }
        const lines = sessions.map((session) => `${session.device} - created ${session.created_at}`).join('\n');
        alert('Saved sessions:\n' + lines + '\n\nYou can clear all saved sessions from the current browser to sign out of other devices.');
      } catch (error) {
        alert(error.message || 'Unable to load sessions.');
      }
    });

    document.getElementById('deactivateAccountBtn').addEventListener('click', () => {
      openConfirmModal('Are you sure you want to deactivate your account? This will disable access and you can contact support to reactivate it.', async () => {
        try {
          const response = await fetch('../shared/auth/settings_api.php?action=deactivate', { method: 'POST' });
          const data = await response.json();
          if (!response.ok || !data.success) {
            throw new Error(data.message || 'Unable to deactivate account.');
          }
          alert(data.message || 'Your account has been deactivated.');
          window.location.href = '../../index.html';
        } catch (error) {
          alert(error.message || 'Unable to deactivate account.');
        }
      });
    });

    document.getElementById('logout-btn').addEventListener('click', () => {
      fetch('../shared/auth/logout.php', { method: 'POST' }).finally(() => {
        window.location.href = '../../index.html';
      });
    });

    document.getElementById('emailNotifications').addEventListener('change', function () {
      this.closest('.switch').classList.toggle('on', this.checked);
    });

    document.getElementById('globalSearch').addEventListener('input', () => {
      if (document.getElementById('globalSearch').value.trim()) {
        document.querySelector('.search').style.borderColor = '#2d7ff9';
      } else {
        document.querySelector('.search').style.borderColor = '#e7edf4';
      }
    });

    setTab('account');
  </script>
</body>
</html>

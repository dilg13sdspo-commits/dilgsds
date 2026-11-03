<?php
require_once __DIR__ . '/../shared/auth/middleware.php';
require_once __DIR__ . '/../shared/auth/po_icons.php';
require_role('PO USER');

$pdo = db_connect();
$userName = $_SESSION['username'] ?? 'Provincial User';
$userId = (int)($_SESSION['user_id'] ?? 0);
$profilePhoto = get_user_profile_photo($pdo, $userId);

function po_notification_count(PDO $pdo, int $userId, ?int $readFlag = null): int {
    $sql = 'SELECT COUNT(*) FROM notifications WHERE user_id = :uid';
    $params = [':uid' => $userId];
    if ($readFlag !== null) {
        $sql .= ' AND is_read = :read';
        $params[':read'] = $readFlag;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function notification_time_ago($ts) {
    $diff = max(1, time() - $ts);
    $minute = 60;
    $hour = 3600;
    $day = 86400;

    if ($diff < $minute) {
        return $diff . ' seconds ago';
    }
    if ($diff < $hour) {
        $mins = floor($diff / $minute);
        return $mins . ' minute' . ($mins === 1 ? '' : 's') . ' ago';
    }
    if ($diff < $day) {
        $hours = floor($diff / $hour);
        return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
    }
    $days = floor($diff / $day);
    return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
}

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
        INDEX idx_related (related_type, related_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

ensure_notifications_table($pdo);

$counts = [
    'total' => po_notification_count($pdo, $userId),
    'unread' => po_notification_count($pdo, $userId, 0),
    'read' => po_notification_count($pdo, $userId, 1),
];

$notificationCount = $counts['unread'];

$badgeCss = [
    'Inquiry' => '#3B82F6',
    'Announcement' => '#F59E0B',
    'Event' => '#10B981',
    'Document' => '#EF4444',
    'Form' => '#8B5CF6',
    'System' => '#64748B',
    'FO Response' => '#10B981',  // Green for on-time responses
];

$categoryIcons = [
    'Inquiry' => 'document',
    'Announcement' => 'announcement',
    'Event' => 'calendar',
    'Document' => 'document',
    'Form' => 'submissions',
    'System' => '&#9881;',
    'FO Response' => '&#10003;',
];

$today = new DateTime();
$tab = $_GET['tab'] ?? 'all';
$categoryFilter = $_GET['category'] ?? 'All Categories';
$dateFilter = $_GET['range'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 8;
$offset = ($page - 1) * $limit;

$sql = 'SELECT n.*, r.fo_response, r.title AS submission_title, r.priority AS submission_priority, r.due_at AS submission_due_at, r.reference_number AS submission_reference FROM notifications n LEFT JOIN reports r ON n.related_type = \'report\' AND r.id = n.related_id WHERE n.user_id = :uid';
$params = [':uid' => $userId];

if ($tab === 'unread') {
  $sql .= ' AND n.is_read = 0';
} elseif ($tab === 'read') {
  $sql .= ' AND n.is_read = 1';
}

if ($categoryFilter !== 'All Categories' && $categoryFilter !== '') {
  if ($categoryFilter === 'FO Response') {
    $sql .= " AND n.type = 'fo_response' AND n.response_status = 'ON_TIME'";
  } elseif ($categoryFilter === 'Overdue Response') {
    $sql .= " AND n.type = 'fo_response' AND n.response_status = 'OVERDUE'";
  } else {
    $sql .= ' AND n.category = :category';
    $params[':category'] = $categoryFilter;
  }
}

if ($dateFilter === 'today') {
  $sql .= ' AND DATE(n.created_at) = CURDATE()';
} elseif ($dateFilter === '7d') {
  $sql .= ' AND n.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
} elseif ($dateFilter === '30d') {
  $sql .= ' AND n.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
} elseif ($dateFilter === 'month') {
  $sql .= ' AND n.created_at >= DATE_FORMAT(NOW(), "%Y-%m-01")';
}

$baseSql = $sql;
$recordStmt = $pdo->prepare($baseSql . ' ORDER BY n.created_at DESC LIMIT :limit OFFSET :offset');
foreach ($params as $key => $value) {
    $recordStmt->bindValue($key, $value);
}
$recordStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$recordStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$recordStmt->execute();
$notifications = $recordStmt->fetchAll();

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM (' . $baseSql . ') AS notification_rows');
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalNotifications = (int)$countStmt->fetchColumn();

$showingStart = $totalNotifications === 0 ? 0 : $offset + 1;
$showingEnd = min($offset + count($notifications), $totalNotifications);

foreach ($notifications as &$row) {
    $row['time_ago'] = notification_time_ago(strtotime($row['created_at']));
    $row['isUnread'] = (int)$row['is_read'] === 0;
    $row['badgeColor'] = $badgeCss[$row['category']] ?? '#64748B';
    $row['icon'] = $categoryIcons[$row['category']] ?? 'notifications';

    if ($row['type'] === 'fo_response' && !empty($row['fo_response'])) {
      $row['message'] = preg_replace(
        '/Response \/ Action Taken:\s*.*?(?=\n|$)/s',
        'Response / Action Taken: ' . $row['fo_response'],
        (string)$row['message']
      );
    }
    
    // Handle FO response notifications with status display
    if ($row['type'] === 'fo_response' && !empty($row['response_status'])) {
        $isOverdue = $row['response_status'] === 'OVERDUE';
        $row['badgeColor'] = $isOverdue ? '#EF4444' : '#10B981';  // Red for overdue, green for on-time
        $row['category'] = $isOverdue ? 'Overdue Response' : 'FO Response';
        $row['statusIcon'] = $isOverdue ? '&#10060;' : '&#9989;';
    }
}
unset($row);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Notifications - DILG PO Dashboard</title>
  <style>
    :root {
      --po-sidebar: #5c2d17;
      --po-sidebar-deep: #3d1f11;
      --po-bg: #f3f7fb;
      --po-surface: #ffffff;
      --po-text: #1f2e3d;
      --po-muted: #6d7a88;
      --po-border: #e6edf4;
      --po-accent: #2f7ef7;
      --po-accent-soft: #edf5ff;
      --po-green: #2bb673;
      --po-green-soft: #ebfaf3;
      --po-gold: #f2b63d;
      --po-red: #d65b5b;
      --po-red-soft: #fff0f1;
      --po-purple: #9d75ee;
      --po-purple-soft: #f5f0ff;
      --po-shadow: 0 12px 28px rgba(31, 42, 61, 0.06);
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "Segoe UI", Arial, sans-serif; background: var(--po-bg); color: var(--po-text); }
    a { text-decoration: none; }
    button, input, select { font: inherit; }
    .po-shell, .shell { display: flex; min-height: 100vh; }
    .po-sidebar, .sidebar { width: 220px; background: linear-gradient(180deg, #0b3d91, #1456a0); color: #fff; display: flex; flex-direction: column; padding: 18px 14px; }
    .brand { display: flex; align-items: center; gap: 12px; padding: 6px 8px 18px; border-bottom: 1px solid rgba(255,255,255,.12); }
    .brand-mark { width: 52px; height: 52px; border-radius: 16px; background: rgba(255,255,255,0.12); display: grid; place-items: center; font-weight: 800; }
    .brand-copy { line-height: 1.1; }
    .brand-copy strong { display: block; font-size: 1rem; letter-spacing: .08em; }
    .brand-copy span { display: block; font-size: .72rem; opacity: .8; margin-top: 4px; }
    .sidebar-profile { display: flex; align-items: center; gap: 10px; margin: 14px 4px 0; padding: 10px 8px; border: 1px solid rgba(255,255,255,.12); border-radius: 12px; background: rgba(255,255,255,.08); }
    .sidebar-profile-avatar { width: 36px; height: 36px; display: grid; place-items: center; flex: 0 0 36px; overflow: hidden; border: 1px solid rgba(255,255,255,.28); border-radius: 50%; background: rgba(255,255,255,.14); font-size: .82rem; font-weight: 800; }
    .sidebar-profile-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .sidebar-profile-info { min-width: 0; }
    .sidebar-profile-status { display: flex; align-items: center; gap: 6px; color: rgba(255,255,255,.88); font-size: .72rem; }
    .sidebar-profile-status::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: #34d399; box-shadow: 0 0 0 3px rgba(52,211,153,.14); }
    .sidebar-profile-name { display: block; margin-top: 3px; color: #fff; font-size: .78rem; font-weight: 700; }
    .nav { display: flex; flex-direction: column; gap: 6px; margin-top: 16px; padding: 0 4px; }
    .nav-item { width: 100%; border: none; background: transparent; color: rgba(255,255,255,.9); display: flex; align-items: center; justify-content: space-between; gap: 8px; text-align: left; border-radius: 10px; padding: 12px 10px; cursor: pointer; transition: all .2s ease; font-weight: 600; }
    .nav-item:hover, .nav-item.active { background: rgba(255,255,255,.12); }
    .nav-item.active { background: rgba(255,255,255,.18); box-shadow: inset 3px 0 0 rgba(255,255,255,.8); }
    .nav-main { display: inline-flex; align-items: center; gap: 10px; }
    .nav-icon { width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; }
    .nav-badge { min-width: 22px; height: 22px; border-radius: 999px; background: #d88a2d; color: #fff; font-size: .72rem; font-weight: 800; display: grid; place-items: center; padding: 0 6px; }
    .sidebar-footer { margin-top: auto; padding-top: 14px; border-top: 1px solid rgba(255,255,255,.12); }
    .footer-pill { display: flex; align-items: center; gap: 10px; color: rgba(255,255,255,.88); font-size: .82rem; font-weight: 600; padding: 10px 8px 0; }
    .seal { width: 26px; height: 26px; border-radius: 50%; display: grid; place-items: center; background: rgba(255,255,255,.12); }
    .po-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; background: rgba(255,255,255,.88); backdrop-filter: blur(6px); border-bottom: 1px solid var(--po-border); padding: 16px 22px; }
    .topbar-title { display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1; }
    .title-mark { width: 34px; height: 34px; border-radius: 10px; background: var(--po-accent); color: #fff; display: grid; place-items: center; font-weight: 800; }
    .title-copy strong { display: block; font-size: .92rem; }
    .title-copy span { display: block; color: var(--po-muted); font-size: .72rem; margin-top: 2px; }
    .topbar-tools { display: flex; align-items: center; gap: 18px; margin-left: auto; }
    .search { display: flex; align-items: center; gap: 8px; min-width: 280px; background: #f5f7fa; border: 1px solid var(--po-border); border-radius: 12px; padding: 10px 12px; color: var(--po-muted); }
    .search input { border: 0; background: transparent; flex: 1; outline: none; color: var(--po-text); }
    .notif { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: #fff; border: 1px solid var(--po-border); color: var(--po-text); }
    .notif .counter { position: absolute; top: -5px; right: -4px; min-width: 18px; height: 18px; border-radius: 50%; background: #f05050; color: #fff; display: grid; place-items: center; font-size: .65rem; font-weight: 700; padding: 0 4px; }
    .profile { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid var(--po-border); border-radius: 18px; padding: 7px 10px; }
    .avatar { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #e69d34, #ca5b17); color: #fff; display: grid; place-items: center; font-weight: 800; font-size: .82rem; }
    .profile-meta { display: flex; flex-direction: column; line-height: 1.2; text-align: left; }
    .profile-meta strong { font-size: .82rem; }
    .profile-meta span { font-size: .68rem; color: var(--po-muted); }
    .page-body { padding: 24px 20px 30px; }
    .page-header { margin-bottom: 18px; }
    .page-title-wrap { display: flex; align-items: center; gap: 16px; background: rgba(255,255,255,.78); border: 1px solid var(--po-border); border-radius: 16px; padding: 18px 20px; box-shadow: 0 8px 18px rgba(31,42,61,.04); }
    .page-icon { width: 52px; height: 52px; border-radius: 14px; background: #edf5ff; color: var(--po-accent); display: grid; place-items: center; font-size: 1.7rem; }
    .page-title-wrap h1 { margin: 0; font-size: clamp(1.8rem,2.6vw,2.4rem); }
    .breadcrumb { margin-top: 6px; font-size: .82rem; color: var(--po-muted); }
    .breadcrumb strong { color: var(--po-text); }
    .page-subtitle { margin: 12px 0 0; color: var(--po-muted); }
    .notifications-layout { display: grid; grid-template-columns: minmax(0,1fr); gap: 20px; }
    .panel { background: var(--po-surface); border: 1px solid var(--po-border); border-radius: 16px; box-shadow: var(--po-shadow); }
    .main-panel { overflow: hidden; }
    .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; margin-bottom: 18px; }
    .summary-card { width: 100%; background: #fff; border: 1px solid var(--po-border); border-radius: 14px; padding: 18px 16px; display: flex; align-items: center; gap: 14px; text-align: left; color: inherit; cursor: pointer; transition: all .2s ease; }
    .summary-card:hover, .summary-card.active { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(31,42,61,.08); border-color: rgba(47,126,247,.35); }
    .summary-icon { width: 46px; height: 46px; border-radius: 12px; display: grid; place-items: center; font-size: 1.3rem; background: #f6f9fc; color: var(--po-text); }
    .summary-card.highlight .summary-icon { background: #eef5ff; color: var(--po-accent); }
    .summary-value { font-size: 2rem; font-weight: 800; letter-spacing: -.05em; }
    .summary-label { color: var(--po-muted); font-size: .8rem; }
    .summary-meta { margin-top: 4px; font-size: .76rem; color: var(--po-muted); }
    .list-shell { border: 1px solid var(--po-border); border-radius: 14px; background: #fff; overflow: hidden; }
    .list-actions { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 16px; border-bottom: 1px solid var(--po-border); }
    .tabs { display: flex; align-items: center; gap: 10px; }
    .tab { border: 1px solid transparent; background: transparent; border-radius: 10px; padding: 8px 12px; color: var(--po-muted); font-weight: 700; cursor: pointer; }
    .tab.active { background: var(--po-accent-soft); color: var(--po-accent); border-color: rgba(47,126,247,.1); }
    .mark-read-btn { border: 1px solid var(--po-border); background: #fff; border-radius: 10px; padding: 9px 12px; color: var(--po-text); font-weight: 700; cursor: pointer; }
    .list-action-buttons { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
    .clear-notifications-btn { border: 1px solid #f2b8b5; background: #fff7f6; border-radius: 10px; padding: 9px 12px; color: #b42318; font-weight: 700; cursor: pointer; }
    .clear-notifications-btn:hover { background: #feeceb; }
    .notification-list { background: #fff; }
    .notification-row { display: flex; align-items: flex-start; gap: 14px; padding: 14px 16px; border-bottom: 1px solid var(--po-border); transition: background .2s ease; }
    .notification-row:hover { background: #fafcff; }
    .notification-row.unread { background: #f9fbff; }
    .bulk-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--po-accent); margin-top: 10px; }
    .notification-row:not(.unread) .bulk-dot { background: transparent; }
    .notification-icon { width: 42px; height: 42px; border-radius: 12px; background: #f3f6fb; display: grid; place-items: center; font-size: 1.2rem; }
    .notification-main { flex: 1; min-width: 0; }
    .notification-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .notification-title { margin: 0; font-size: 1.02rem; color: var(--po-text); }
    .notification-desc { margin: 6px 0 0; color: var(--po-muted); line-height: 1.5; overflow-wrap: anywhere; }
    .response-label { color: var(--po-text); font-weight: 700; }
    .response-text { color: var(--po-text); font-weight: 600; overflow-wrap: anywhere; }
    .response-meta { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--po-border); color: var(--po-muted); font-size: .82rem; }
    .response-meta strong { color: var(--po-text); }
    .response-meta-separator { color: #b5c0ca; }
    .response-meta .open-submission-btn { margin-left: auto; }
    .notification-meta { display: flex; align-items: center; gap: 10px; margin-top: 8px; flex-wrap: wrap; }
    .notification-tag { display: inline-flex; align-items: center; padding: 5px 8px; border: 0; border-radius: 999px; font-size: .72rem; font-weight: 700; color: #fff; cursor: pointer; }
    .notification-time { color: var(--po-muted); font-size: .75rem; }
    .open-submission-btn { border: 1px solid #b9e8d2; background: #effaf5; color: #087f52; border-radius: 7px; padding: 4px 8px; font-size: .72rem; font-weight: 700; cursor: pointer; }
    .open-submission-btn:hover { background: #d9f6e8; }
    .notification-actions { position: relative; }
    .menu-button { width: 32px; height: 32px; border: 1px solid var(--po-border); background: #fff; border-radius: 8px; cursor: pointer; color: var(--po-text); }
    .menu { position: absolute; right: 0; top: calc(100% + 8px); background: #fff; border: 1px solid var(--po-border); border-radius: 12px; box-shadow: var(--po-shadow); padding: 8px; min-width: 160px; display: none; z-index: 10; }
    .menu.open { display: block; }
    .menu button { width: 100%; background: transparent; border: 0; padding: 8px 10px; border-radius: 8px; text-align: left; color: var(--po-text); cursor: pointer; }
    .menu button:hover { background: #f4f8ff; }
    .empty-box { padding: 42px 20px; text-align: center; color: var(--po-muted); }
    .empty-box h3 { margin: 0 0 10px; color: var(--po-text); }
    .right-panel { display: flex; flex-direction: column; gap: 18px; }
    .filter-card { padding: 16px 16px 14px; }
    .filter-card h3 { margin: 0 0 12px; font-size: 1.05rem; }
    .filter-group { margin-top: 14px; }
    .filter-group label { display: block; margin-bottom: 8px; font-weight: 700; color: var(--po-text); font-size: .85rem; }
    .filter-select { width: 100%; border: 1px solid var(--po-border); border-radius: 10px; background: #fff; padding: 9px 10px; color: var(--po-text); }
    .check-list { display: flex; flex-direction: column; gap: 8px; }
    .check-item { display: flex; align-items: center; gap: 8px; color: var(--po-text); font-size: .9rem; }
    .check-item input { accent-color: var(--po-accent); }
    .filter-actions { display: flex; gap: 8px; margin-top: 16px; }
    .ghost-btn, .primary-btn { border-radius: 10px; padding: 10px 12px; font-weight: 700; cursor: pointer; }
    .ghost-btn { border: 1px solid var(--po-border); background: #fff; color: var(--po-text); }
    .primary-btn { border: 1px solid var(--po-accent); background: var(--po-accent); color: #fff; }
    .muted-row { color: var(--po-muted); font-size: .8rem; }
    @media (max-width: 980px) {
      .po-shell, .shell { flex-direction: column; }
      .po-sidebar, .sidebar { width: 100%; }
      .nav { flex-direction: row; flex-wrap: wrap; }
      .nav-item { width: calc(50% - 6px); }
      .notifications-layout { grid-template-columns: 1fr; }
      .summary-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
      .topbar-tools { width: 100%; justify-content: space-between; }
      .search { min-width: 180px; flex: 1; }
    }
    @media (max-width: 640px) {
      .summary-grid { grid-template-columns: 1fr; }
      .topbar { padding: 12px 16px; }
      .search { min-width: 0; }
      .notification-row { padding: 12px; }
      .list-actions { flex-direction: column; align-items: stretch; }
      .tabs { flex-wrap: wrap; }
      .page-body { padding: 16px 14px 24px; }
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
      <div class="sidebar-profile" aria-label="PO User online">
        <div class="sidebar-profile-avatar">
          <?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="PO User profile photo"><?php else: ?><?= htmlspecialchars(strtoupper(substr($userName, 0, 2))) ?><?php endif; ?>
        </div>
        <div class="sidebar-profile-info"><span class="sidebar-profile-status">Online</span><span class="sidebar-profile-name"><?= htmlspecialchars($userName) ?></span></div>
      </div>
      <nav class="nav" aria-label="Primary navigation">
        <button class="nav-item" type="button" onclick="window.location.href='dashboard.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('dashboard') ?></span><span class="nav-label">Dashboard</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='inquiries.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('submissions') ?></span><span class="nav-label">My Submissions</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='office.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('office') ?></span><span class="nav-label">My Office</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='calendar.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('calendar') ?></span><span class="nav-label">Calendar</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='reports.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('reports') ?></span><span class="nav-label">Reports</span></span></button>
        <button class="nav-item active" type="button" onclick="window.location.href='notifications.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('notifications') ?></span><span class="nav-label">Notifications</span></span><span class="nav-badge" id="sidebarBadge"><?= htmlspecialchars((string)$notificationCount) ?></span></button>
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
            <input id="notificationsSearch" type="search" placeholder="Search dashboard..." />
          </label>
          <div class="notif" aria-label="Notifications" onclick="window.location.href='notifications.php'" style="cursor:pointer;">
            <?= po_icon('notifications') ?>
            <span class="counter" id="topBadge"><?= htmlspecialchars((string)$notificationCount) ?></span>
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
            <div class="page-icon"><?= po_icon('notifications') ?></div>
            <div>
              <h1>Notifications</h1>

              <div class="breadcrumb"><strong>Dashboard</strong> <span>&rsaquo;</span> Notifications</div>
            </div>
          </div>
          <p class="page-subtitle">Stay updated with important alerts, updates, and activities.</p>
        </div>

        <div class="notifications-layout">
          <section class="main-panel panel">
            <div class="summary-grid">
              <button class="summary-card highlight" data-filter="all" type="button" aria-label="Show all notifications" onclick="selectNotificationView('all')">
                <div class="summary-icon"><?= po_icon('notifications') ?></div>
                <div>
                  <div class="summary-value" id="totalCount"><?= htmlspecialchars((string)$counts['total']) ?></div>
                  <div class="summary-label">Total Notifications</div>
                  <div class="summary-meta">All time</div>
                </div>
              </button>
              <button class="summary-card" data-filter="unread" type="button" aria-label="Show unread notifications" onclick="selectNotificationView('unread')">
                <div class="summary-icon" style="background:#ebf6ff;color:#2f7ef7;"><?= po_icon('pending') ?></div>
                <div>
                  <div class="summary-value" id="unreadCount"><?= htmlspecialchars((string)$counts['unread']) ?></div>
                  <div class="summary-label">Unread</div>
                  <div class="summary-meta">New notifications</div>
                </div>
              </button>
              <button class="summary-card" data-filter="read" type="button" aria-label="Show read notifications" onclick="selectNotificationView('read')">
                <div class="summary-icon" style="background:#ebfaf3;color:#2bb673;">&#10003;</div>
                <div>
                  <div class="summary-value" id="readCount"><?= htmlspecialchars((string)$counts['read']) ?></div>
                  <div class="summary-label">Read</div>
                  <div class="summary-meta">Marked as read</div>
                </div>
              </button>
              <button class="summary-card" data-filter="all" type="button" aria-label="Show all notifications" onclick="selectNotificationView('all')">
                <div class="summary-icon" style="background:#fff7e8;color:#d88a2d;"><?= po_icon('document') ?></div>
                <div>
                  <div class="summary-value" id="allCount"><?= htmlspecialchars((string)$counts['total']) ?></div>
                  <div class="summary-label">Show</div>
                  <div class="summary-meta">All notifications</div>
                </div>
              </button>
            </div>

            <div class="list-shell">
              <div class="list-actions">
                <div class="tabs" id="tabs">
                  <button class="tab active" data-tab="all" type="button">All</button>
                  <button class="tab" data-tab="unread" type="button">Unread</button>
                  <button class="tab" data-tab="read" type="button">Read</button>
                </div>
                <div class="list-action-buttons">
                  <button class="mark-read-btn" id="markAllReadBtn" type="button">Mark all as read</button>
                  <button class="clear-notifications-btn" id="clearNotificationsBtn" type="button">Clear notifications</button>
                </div>
              </div>

              <div id="notificationsContainer" class="notification-list">
                <?php if (empty($notifications)): ?>
                  <div class="empty-box">
                    <h3>No notifications found</h3>
                    <p>Your notifications will appear here when there are new updates.</p>
                  </div>
                <?php else: ?>
                  <?php foreach ($notifications as $item): ?>
                    <div class="notification-row <?= (int)$item['is_read'] === 0 ? 'unread' : '' ?>" data-id="<?= (int)$item['id'] ?>" data-related-id="<?= (int)($item['related_id'] ?? 0) ?>" data-action-url="<?= htmlspecialchars($item['action_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                      <div class="bulk-dot" aria-hidden="true"></div>
                      <div class="notification-icon"><?= po_icon($item['icon']) ?></div>
                      <div class="notification-main" role="button" tabindex="0" data-open="<?= (int)$item['id'] ?>">
                        <div class="notification-head">
                          <h3 class="notification-title"><?= $item['type'] === 'fo_response' ? 'New Task Received' : htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        </div>
                        <?php if ($item['type'] === 'fo_response'): ?>
                          <p class="notification-desc">The FO was sent a submit response.</p>
                          <div class="response-text"><span class="response-label">Response / Action Taken:</span> <?= htmlspecialchars($item['fo_response'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                          <div class="response-meta">
                            <span>Inquiry</span><span class="response-meta-separator">&bull;</span>
                            <span><?= htmlspecialchars($item['time_ago'], ENT_QUOTES, 'UTF-8') ?></span><span class="response-meta-separator">&bull;</span>
                            <span><strong>Priority:</strong> <?= htmlspecialchars(strtoupper($item['submission_priority'] ?? 'MEDIUM'), ENT_QUOTES, 'UTF-8') ?></span><span class="response-meta-separator">&bull;</span>
                            <span><strong>Due:</strong> <?= htmlspecialchars($item['submission_due_at'] ? date('F j, Y g:i A', strtotime($item['submission_due_at'])) : 'Not specified', ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($item['related_id'])): ?><button type="button" class="open-submission-btn" data-open-submission="<?= (int)$item['related_id'] ?>">Open submission</button><?php endif; ?>
                          </div>
                        <?php else: ?>
                          <p class="notification-desc"><?= htmlspecialchars($item['message'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <div class="notification-meta">
                          <?php if ($item['type'] !== 'fo_response'): ?><button type="button" class="notification-tag" data-category-filter="<?= htmlspecialchars($item['category'], ENT_QUOTES, 'UTF-8') ?>" style="background:<?= htmlspecialchars($item['badgeColor']) ?>;"><?= htmlspecialchars($item['category']) ?></button>
                          <span class="notification-time"><?= htmlspecialchars($item['time_ago']) ?></span><?php endif; ?>
                        </div>
                      </div>
                      <div class="notification-actions">
                        <button class="menu-button" type="button" aria-label="Open notification actions">&#8942;</button>
                        <div class="menu" role="menu">
                          <button type="button" data-action="read" data-id="<?= (int)$item['id'] ?>">Mark as read</button>
                          <button type="button" data-action="unread" data-id="<?= (int)$item['id'] ?>">Mark as unread</button>
                          <button type="button" data-action="view" data-id="<?= (int)$item['id'] ?>">View details</button>
                          <button type="button" data-action="delete" data-id="<?= (int)$item['id'] ?>">Delete</button>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>

              <div class="muted-row" style="padding: 10px 16px 14px;">
                Showing <?= $totalNotifications === 0 ? 0 : $showingStart ?> to <?= $showingEnd ?> of <?= $totalNotifications ?> notifications
              </div>
            </div>
          </section>

        </div>
      </main>
    </div>
  </div>

  <script>
    const state = {
      tab: 'all',
      category: 'all',
      dateRange: 'all',
      page: 1,
      limit: 8,
    };

    function setBadge(count) {
      const badgeEls = [document.getElementById('sidebarBadge'), document.getElementById('topBadge')];
      badgeEls.forEach(el => {
        if (el) {
          el.textContent = count;
          el.style.display = count > 0 ? 'grid' : 'none';
        }
      });
    }

    function reloadStats() {
      fetch('../shared/auth/notifications_api.php?action=stats')
        .then(r => r.json())
        .then(data => {
          if (!data.success) return;
          document.getElementById('totalCount').textContent = data.stats.total;
          document.getElementById('unreadCount').textContent = data.stats.unread;
          document.getElementById('readCount').textContent = data.stats.read;
          document.getElementById('allCount').textContent = data.stats.total;
          setBadge(data.stats.unread);
        })
        .catch(() => {});
    }

    function fetchNotifications() {
      const params = new URLSearchParams({
        action: 'list',
        tab: state.tab,
        category: state.category,
        range: state.dateRange,
        page: state.page,
        limit: state.limit,
      });

      fetch('../shared/auth/notifications_api.php?' + params.toString())
        .then(res => res.json())
        .then(data => {
          const container = document.getElementById('notificationsContainer');
          if (!data.success || !Array.isArray(data.items)) {
            container.innerHTML = '<div class="empty-box"><h3>Unable to load notifications</h3><p>Please try again.</p></div>';
            return;
          }

          if (data.items.length === 0) {
            container.innerHTML = '<div class="empty-box"><h3>No notifications found</h3><p>Your notifications will appear here when there are new updates.</p></div>';
            return;
          }

          const notificationIcons = {
            Inquiry: <?= json_encode(po_icon('document')) ?>,
            Announcement: <?= json_encode(po_icon('announcement')) ?>,
            Event: <?= json_encode(po_icon('calendar')) ?>,
            Document: <?= json_encode(po_icon('document')) ?>,
            Form: <?= json_encode(po_icon('submissions')) ?>,
            System: <?= json_encode(po_icon('settings')) ?>,
            'Overdue Response': <?= json_encode(po_icon('document')) ?>,
            'FO Response': <?= json_encode(po_icon('document')) ?>
          };

          container.innerHTML = data.items.map(item => {
            const tagStyle = 'background:' + (item.category === 'Inquiry' ? '#3B82F6' : item.category === 'Announcement' ? '#F59E0B' : item.category === 'Event' ? '#10B981' : item.category === 'Document' ? '#EF4444' : item.category === 'Form' ? '#8B5CF6' : item.category === 'Overdue Response' ? '#EF4444' : item.category === 'FO Response' ? '#10B981' : '#64748B') + ';';
            const messageDisplay = item.type === 'fo_response'
              ? 'The FO was sent a submit response.'
              : item.message.substring(0, 150) + (item.message.length > 150 ? '...' : '');
            return `
              <div class="notification-row ${item.is_read ? '' : 'unread'}" data-id="${item.id}" data-related-id="${item.related_id || 0}" data-action-url="${escapeHtml(item.action_url || '')}">
                <div class="bulk-dot" aria-hidden="true"></div>
                <div class="notification-icon">${notificationIcons[item.category] || notificationIcons.System}</div>
                <div class="notification-main" role="button" tabindex="0" data-open="${item.id}">
                  <div class="notification-head">
                    <h3 class="notification-title">${item.type === 'fo_response' ? 'New Task Received' : escapeHtml(item.title)}</h3>
                  </div>
                  <p class="notification-desc">${escapeHtml(messageDisplay)}</p>
                  ${item.type === 'fo_response' ? `<div class="response-text"><span class="response-label">Response / Action Taken:</span> ${escapeHtml(item.fo_response || '')}</div><div class="response-meta"><span>Inquiry</span><span class="response-meta-separator">&bull;</span><span>${escapeHtml(item.time_ago)}</span><span class="response-meta-separator">&bull;</span><span><strong>Priority:</strong> ${escapeHtml(String(item.submission_priority || 'MEDIUM').toUpperCase())}</span><span class="response-meta-separator">&bull;</span><span><strong>Due:</strong> ${escapeHtml(formatNotificationDate(item.submission_due_at))}</span>${item.related_id ? `<button type="button" class="open-submission-btn" data-open-submission="${item.related_id}">Open submission</button>` : ''}</div>` : ''}
                  <div class="notification-meta">
                    ${item.type !== 'fo_response' ? `<button type="button" class="notification-tag" data-category-filter="${escapeHtml(item.category)}" style="${tagStyle}">${escapeHtml(item.category)}</button><span class="notification-time">${escapeHtml(item.time_ago)}</span>` : ''}
                  </div>
                </div>
                <div class="notification-actions">
                  <button class="menu-button" type="button" aria-label="Open notification actions">&#8942;</button>
                  <div class="menu" role="menu">
                    <button type="button" data-action="read" data-id="${item.id}">Mark as read</button>
                    <button type="button" data-action="unread" data-id="${item.id}">Mark as unread</button>
                    <button type="button" data-action="view" data-id="${item.id}">View details</button>
                    <button type="button" data-action="delete" data-id="${item.id}">Delete</button>
                  </div>
                </div>
              </div>
            `;
          }).join('');

          const showingNode = document.querySelector('.muted-row');
          if (showingNode) {
            showingNode.textContent = `Showing ${data.total === 0 ? 0 : ((state.page - 1) * state.limit) + 1} to ${Math.min(state.page * state.limit, data.total)} of ${data.total} notifications`;
          }

          bindHandlers();
        })
        .catch(() => {
          // Keep the server-rendered notifications visible if refresh fails.
          const container = document.getElementById('notificationsContainer');
          if (!container.querySelector('.notification-row')) {
            container.innerHTML = '<div class="empty-box"><h3>Unable to load notifications</h3><p>Please try again.</p></div>';
          }
        });
    }

    function updateTabState() {
      document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.tab === state.tab);
      });
      document.querySelectorAll('.summary-card').forEach(card => {
        card.classList.toggle('active', card.dataset.filter === state.tab);
      });
    }

    function formatNotificationDate(value) {
      if (!value) return 'Not specified';
      const date = new Date(String(value).replace(' ', 'T'));
      if (Number.isNaN(date.getTime())) return String(value);
      return date.toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    }

    function bindHandlers() {
      document.querySelectorAll('.menu-button').forEach(btn => {
        btn.addEventListener('click', (event) => {
          event.stopPropagation();
          const menu = btn.parentElement.querySelector('.menu');
          document.querySelectorAll('.menu').forEach(m => m.classList.remove('open'));
          menu.classList.toggle('open');
        });
      });

      document.querySelectorAll('.notification-main').forEach(item => {
        item.addEventListener('click', () => {
          const id = Number(item.dataset.open);
          const row = item.closest('.notification-row');
          const relatedId = Number(row?.dataset.relatedId || 0);
          const destination = relatedId ? 'inquiries.php?view=' + encodeURIComponent(relatedId) : (row?.dataset.actionUrl || '');
          markRead(id, true, destination);
        });
      });

      document.querySelectorAll('.open-submission-btn').forEach(button => {
        button.addEventListener('click', event => {
          event.stopPropagation();
          const notificationRow = button.closest('.notification-row');
          const notificationId = Number(notificationRow?.dataset.id || 0);
          const submissionId = Number(button.dataset.openSubmission || 0);
          if (notificationId && submissionId) {
            markRead(notificationId, true, 'inquiries.php?view=' + encodeURIComponent(submissionId));
          }
        });
      });

      document.querySelectorAll('.notification-tag').forEach(tag => {
        tag.addEventListener('click', event => {
          event.stopPropagation();
          state.category = tag.dataset.categoryFilter || 'all';
          state.page = 1;
          fetchNotifications();
        });
      });

      document.querySelectorAll('.menu button').forEach(item => {
        item.addEventListener('click', () => {
          const id = Number(item.dataset.id);
          const action = item.dataset.action;
          if (action === 'read') markRead(id, false);
          if (action === 'unread') markUnread(id);
          if (action === 'view') viewDetail(id);
          if (action === 'delete') deleteNotification(id);
          document.querySelectorAll('.menu').forEach(m => m.classList.remove('open'));
        });
      });
    }

    function markRead(id, navigate = false, actionUrl = '') {
      fetch('../shared/auth/notifications_api.php?action=mark_read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      }).then(() => {
        reloadStats();
        fetchNotifications();
        if (navigate) {
          if (actionUrl) window.location.href = actionUrl;
          else {
            const row = document.querySelector('.notification-row[data-id="' + id + '"]');
            if (row) row.classList.remove('unread');
          }
        }
      });
    }

    function markUnread(id) {
      fetch('../shared/auth/notifications_api.php?action=mark_unread', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      }).then(() => {
        reloadStats();
        fetchNotifications();
      });
    }

    function deleteNotification(id) {
      fetch('../shared/auth/notifications_api.php?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      }).then(() => {
        reloadStats();
        fetchNotifications();
      });
    }

    function viewDetail(id) {
      fetch('../shared/auth/notifications_api.php?action=detail&id=' + id)
        .then(r => r.json())
        .then(data => {
          if (!data.success) return;
          const item = data.notification;
          
          // Create a proper modal for FO response notifications
          const messageLines = item.message.split('\n').filter(line => line.trim());
          const responseSection = item.type === 'fo_response' && item.fo_response
            ? `
              <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 8px; font-size: 0.95rem; color: #1f2937;">FO User Response / Action Taken</h3>
                <div style="background: #effaf5; padding: 14px; border-radius: 8px; border-left: 4px solid #10b981; color: #1f2937; line-height: 1.7; white-space: pre-wrap; overflow-wrap: anywhere;">${escapeHtml(item.fo_response)}</div>
              </div>
            `
            : '';
          const attachmentSection = item.type === 'fo_response' && Array.isArray(item.attachments) && item.attachments.length
            ? `
              <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 8px; font-size: 0.95rem; color: #1f2937;">Proof of Response</h3>
                <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border-left: 4px solid #64748b;">
                  ${item.attachments.map(file => `<div style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding:7px 0; border-bottom:1px solid #e5e7eb;"><span style="overflow-wrap:anywhere;">${escapeHtml(file.original_name || file.file_name || 'Attachment')}</span><a href="${escapeHtml(String(file.file_path || '').replace(/^\/+/, '') || '#')}" target="_blank" rel="noopener" style="color:#2563eb; font-weight:600; white-space:nowrap;">Open</a></div>`).join('')}
                </div>
              </div>
            `
            : '<div style="margin-bottom: 20px; color: #6b7280; font-size: 0.9rem;">No proof attachments were submitted.</div>';
          let modalContent = `
            <div style="background: white; border-radius: 12px; padding: 24px; max-width: 600px; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 25px rgba(0,0,0,0.15);">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb;">
                <h2 style="margin: 0; font-size: 1.3rem; color: #1f2937;">${escapeHtml(item.title)}</h2>
                <button onclick="this.closest('[role=dialog]').remove()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">&times;</button>
              </div>
              <div style="margin-bottom: 20px;">
                <div style="background: #f3f4f6; padding: 12px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                  <p style="margin: 0; font-size: 0.9rem; color: #374151; line-height: 1.8; white-space: pre-line;">
                    ${messageLines.map(line => escapeHtml(line)).join('\n')}
                  </p>
                </div>
              </div>
              ${responseSection}
              ${attachmentSection}
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
                <div>
                  <span style="font-weight: 600; color: #374151; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Category</span>
                  <p style="margin: 4px 0 0; color: #1f2937; font-size: 0.95rem;">${escapeHtml(item.category)}</p>
                </div>
                <div>
                  <span style="font-weight: 600; color: #374151; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Status</span>
                  <p style="margin: 4px 0 0; color: #1f2937; font-size: 0.95rem;">${item.is_read ? '&#10003; Read' : '&#9679; Unread'}</p>
                </div>
              </div>
              <div style="display: flex; gap: 8px; margin-top: 20px;">
                <button onclick="fetch('../shared/auth/notifications_api.php?action=mark_read', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id:${id}})}).then(() => location.reload())" style="flex: 1; padding: 10px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Mark as Read</button>
                <button onclick="this.closest('[role=dialog]').remove()" style="flex: 1; padding: 10px 16px; background: #e5e7eb; color: #1f2937; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Close</button>
              </div>
            </div>
          `;
          
          // Create modal overlay
          const modal = document.createElement('div');
          modal.setAttribute('role', 'dialog');
          modal.setAttribute('aria-modal', 'true');
          modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.2s ease-in;
          `;
          modal.innerHTML = modalContent;
          
          // Add animation
          const style = document.createElement('style');
          style.textContent = '@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }';
          document.head.appendChild(style);
          
          // Close on background click
          modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
          });
          
          // Close on Escape key
          const closeOnEscape = (e) => {
            if (e.key === 'Escape') {
              modal.remove();
              document.removeEventListener('keydown', closeOnEscape);
            }
          };
          document.addEventListener('keydown', closeOnEscape);
          
          document.body.appendChild(modal);
        })
        .catch(err => console.error('Error loading notification detail:', err));
    }

    document.querySelectorAll('.tab').forEach(tab => {
      tab.addEventListener('click', () => {
        state.tab = tab.dataset.tab;
        state.page = 1;
        updateTabState();
        fetchNotifications();
      });
    });

    document.getElementById('markAllReadBtn').addEventListener('click', () => {
      fetch('../shared/auth/notifications_api.php?action=mark_read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: 0 })
      }).then(() => {
        reloadStats();
        fetchNotifications();
      });
    });

    document.getElementById('clearNotificationsBtn').addEventListener('click', () => {
      if (!window.confirm('Clear all of your PO notifications? This cannot be undone.')) return;

      const button = document.getElementById('clearNotificationsBtn');
      button.disabled = true;
      fetch('../shared/auth/notifications_api.php?action=clear_all', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({})
      })
        .then(response => response.json())
        .then(data => {
          if (!data.success) throw new Error(data.error || 'Unable to clear notifications.');
          state.page = 1;
          reloadStats();
          fetchNotifications();
        })
        .catch(error => window.alert(error.message))
        .finally(() => {
          button.disabled = false;
        });
    });

    function selectNotificationView(filter) {
      state.tab = filter === 'unread' || filter === 'read' ? filter : 'all';
      state.category = 'all';
      state.dateRange = 'all';
      state.page = 1;
      updateTabState();
      fetchNotifications();
    }

    document.querySelectorAll('.summary-card').forEach(card => {
      const activateCard = () => {
        const val = card.dataset.filter;
        selectNotificationView(val);
      };
      card.addEventListener('click', activateCard);
      card.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          activateCard();
        }
      });
    });

    document.getElementById('logout-btn').addEventListener('click', () => {
      fetch('../shared/auth/logout.php', { method: 'POST' }).finally(() => {
        window.location.href = '../../index.html';
      });
    });

    document.addEventListener('click', (event) => {
      if (!event.target.closest('.menu-button') && !event.target.closest('.menu')) {
        document.querySelectorAll('.menu').forEach(m => m.classList.remove('open'));
      }
    });

    updateTabState();
    setBadge(<?= (int)$notificationCount ?>);
    reloadStats();
    // Bind the server-rendered rows immediately, before the AJAX refresh completes.
    bindHandlers();
    fetchNotifications();
  </script>
</body>
</html>

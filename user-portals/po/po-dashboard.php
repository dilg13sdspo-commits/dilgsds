<?php
require_once __DIR__ . '/../shared/auth/middleware.php';
require_once __DIR__ . '/../shared/auth/workflow.php';
require_role('PO USER');

$pdo = db_connect();
$workflowTimezone = new DateTimeZone('Asia/Manila');
workflow_ensure_schema($pdo);
workflow_sync_overdue($pdo);
$userName = $_SESSION['username'] ?? 'Provincial User';
$userId = (int)($_SESSION['user_id'] ?? 0);
$profilePhoto = get_user_profile_photo($pdo, $userId);
$philippineTime = new DateTime('now', new DateTimeZone('Asia/Manila'));
$greetingHour = (int)$philippineTime->format('G');
$timeGreeting = $greetingHour < 12 ? 'Good morning' : ($greetingHour < 18 ? 'Good afternoon' : 'Good evening');

function po_count($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

$statusStmt = $pdo->prepare("SELECT CASE WHEN r.fo_response_at IS NOT NULL AND r.due_at IS NOT NULL AND r.fo_response_at > r.due_at THEN 'OVERDUE' ELSE r.status END AS status, r.recipient_office, (SELECT COUNT(*) FROM report_recipients rr WHERE rr.report_id = r.id) AS recipient_count, (SELECT COUNT(*) FROM report_responses rp WHERE rp.report_id = r.id AND rp.response_at IS NOT NULL) AS response_count, (SELECT COUNT(*) FROM report_responses rp WHERE rp.report_id = r.id AND rp.response_at > r.due_at AND rp.status NOT IN ('APPROVED', 'RETURNED')) AS overdue_response_count, (SELECT COUNT(*) FROM report_responses rp WHERE rp.report_id = r.id AND rp.status = 'APPROVED') AS approved_response_count, (SELECT COUNT(*) FROM report_responses rp WHERE rp.report_id = r.id AND rp.status = 'RETURNED') AS returned_response_count FROM reports r WHERE EXISTS (SELECT 1 FROM users u WHERE u.id = r.user_id AND u.role = 'PO USER')");
$statusStmt->execute();
$pendingCount = 0;
$submittedCount = 0;
$overdueCount = 0;
$approvedCount = 0;
$returnedCount = 0;
foreach ($statusStmt->fetchAll(PDO::FETCH_ASSOC) as $statusRow) {
  $status = strtoupper((string)($statusRow['status'] ?? ''));
  $isBroadcast = ($statusRow['recipient_office'] ?? '') === 'All Municipalities / Cities';
  if ($isBroadcast && (int)$statusRow['recipient_count'] > 0) {
    $pendingCount += max(0, (int)$statusRow['recipient_count'] - (int)$statusRow['response_count']);
    $overdueResponses = (int)$statusRow['overdue_response_count'];
    $approvedResponses = (int)$statusRow['approved_response_count'];
    $returnedResponses = (int)$statusRow['returned_response_count'];
    $submittedOnlyResponses = max(0, (int)$statusRow['response_count'] - $approvedResponses - $returnedResponses - $overdueResponses);
    $overdueCount += $overdueResponses;
    $submittedCount += $submittedOnlyResponses;
    $approvedCount += $approvedResponses;
    $returnedCount += $returnedResponses;
  } elseif ($status === 'PENDING') {
    $pendingCount++;
  } elseif ($status === 'SUBMITTED' || $status === 'IN_PROGRESS') {
    $submittedCount++;
  } elseif ($status === 'OVERDUE') {
    $overdueCount++;
  } elseif (in_array($status, ['COMPLETED', 'RESOLVED', 'APPROVED'], true)) {
    $approvedCount++;
  } elseif ($status === 'RETURNED') {
    $returnedCount++;
  }
}
$totalInquiries = $pendingCount + $submittedCount + $overdueCount + $approvedCount + $returnedCount;
$overallPercentage = $totalInquiries > 0
  ? (($pendingCount + $submittedCount + $overdueCount) / $totalInquiries) * 100
  : 0;
$pendingPercentage = $totalInquiries > 0 ? ($pendingCount / $totalInquiries) * 100 : 0;
$submittedPercentage = $totalInquiries > 0 ? ($submittedCount / $totalInquiries) * 100 : 0;
$overduePercentage = $totalInquiries > 0 ? ($overdueCount / $totalInquiries) * 100 : 0;
$notificationCount = po_count($pdo, 'SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0', [':uid' => $userId]);

$recentReports = $pdo->query(
    "SELECT r.id, r.title, r.status, r.created_at, u.username AS requester
     FROM reports r
     LEFT JOIN users u ON u.id = r.user_id
     ORDER BY r.created_at DESC
     LIMIT 6"
)->fetchAll();

$trendPeriods = ['weekly' => [], 'monthly' => []];
foreach (['weekly' => 6, 'monthly' => 29] as $period => $days) {
  for ($i = $days; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = po_count($pdo, 'SELECT COUNT(*) FROM reports r WHERE EXISTS (SELECT 1 FROM users u WHERE u.id = r.user_id AND u.role = "PO USER") AND DATE(r.created_at) = :d', [':d' => $date]);
    $trendPeriods[$period][] = ['label' => date('M d', strtotime($date)), 'value' => $count];
  }
}

$recentNotifications = $pdo->query('SELECT * FROM notifications WHERE user_id = ' . (int)$userId . ' ORDER BY created_at DESC LIMIT 5')->fetchAll();
foreach ($recentNotifications as &$notification) {
    $notification['time'] = strtotime($notification['created_at']);
}
unset($notification);

if (empty($recentNotifications)) {
    $recentNotifications = [[
        'title' => 'No notifications yet',
        'message' => 'Your notifications will appear here when there are new updates.',
        'time' => time(),
        'category' => 'System',
    ]];
}

function po_time_ago($ts) {
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

  function po_icon($name, $class = '') {
    $paths = [
      'dashboard' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V20h13v-9.5M9 20v-5h6v5"/>',
      'submissions' => '<path d="M7 3h7l4 4v14H7z"/><path d="M14 3v5h4M10 12h5M10 16h5"/>',
      'office' => '<path d="M4 20V6l8-3 8 3v14M2 20h20M8 9h2M14 9h2M8 13h2M14 13h2M10 20v-4h4v4"/>',
      'announcement' => '<path d="m4 11 12-5v12L4 13zM4 11v2M16 9h3a2 2 0 0 1 0 4h-3M7 14l1.5 5h3L10 15"/>',
      'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01"/>',
      'reports' => '<path d="M5 20V10M12 20V4M19 20v-7"/>',
      'notifications' => '<path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
      'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.9 1.9-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V20h-2.7v-.09a1.7 1.7 0 0 0-1.03-1.56 1.7 1.7 0 0 0-1.88.34l-.06.06-1.9-1.9.06-.06A1.7 1.7 0 0 0 7.8 15a1.7 1.7 0 0 0-1.56-1.03H6v-2.7h.24A1.7 1.7 0 0 0 7.8 10a1.7 1.7 0 0 0-.34-1.88L7.4 8.06l1.9-1.9.06.06a1.7 1.7 0 0 0 1.88.34 1.7 1.7 0 0 0 1.03-1.56V5h2.7v.09A1.7 1.7 0 0 0 16 6.65a1.7 1.7 0 0 0 1.88-.34l.06-.06 1.9 1.9-.06.06A1.7 1.7 0 0 0 19.4 10a1.7 1.7 0 0 0 1.56 1.03H21v2.7h-.09A1.7 1.7 0 0 0 19.4 15Z"/>',
      'logout' => '<path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/>',
      'search' => '<circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/>',
      'create' => '<path d="M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4M4 20l1-4"/>',
      'document' => '<path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6M9 17h6"/>',
      'download' => '<path d="M12 4v10M8 10l4 4 4-4M5 19h14"/>',
      'phone' => '<path d="M6 4h3l1.5 4-2 1.5a14 14 0 0 0 5 5l1.5-2 4 1.5v3a2 2 0 0 1-2 2C10.4 19.5 4.5 13.6 4.5 6a2 2 0 0 1 1.5-2Z"/>',
      'total' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16M8 14h8M8 17h5"/>',
      'pending' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/>',
      'submitted' => '<circle cx="12" cy="12" r="8"/><path d="m8 12 2.5 2.5L16 9"/>',
      'overdue' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v5M12 16h.01"/>',
      'quick-document' => '<path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6M9 17h6"/>',
      'advisory' => '<path d="M4 11 16 6v12L4 13zM4 11v2M16 9h3a2 2 0 0 1 0 4h-3M7 14l1.5 5h3L10 15"/>',
    ];

    $path = $paths[$name] ?? $paths['document'];
    $classAttribute = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
    return '<svg' . $classAttribute . ' viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
  }

$quickLinks = [
    ['label' => "Citizen's Charter", 'icon' => 'document', 'href' => '../../citizen-charter.html'],
    ['label' => 'Issuances', 'icon' => 'document', 'href' => '../../issuances.html'],
    ['label' => 'Forms & Templates', 'icon' => 'submissions', 'href' => '../../programs-and-services.html'],
    ['label' => 'Guidelines', 'icon' => 'reports', 'href' => '../../annual-report.html'],
    ['label' => 'DILG Directory', 'icon' => 'office', 'href' => '../../contact-us.html'],
    ['label' => 'FAQs', 'icon' => 'announcement', 'href' => '../../about-gad.html'],
    ['label' => 'Contact Us', 'icon' => 'phone', 'href' => '../../contact-us.html'],
    ['label' => 'Download Center', 'icon' => 'download', 'href' => '../../programs-and-services.html'],
];

$calendarItems = [
    ['date' => '2026-05-15', 'title' => 'Provincial Coordinating Meeting', 'time' => '9:00 AM', 'location' => 'Provincial Capitol Conference Room'],
    ['date' => '2026-05-22', 'title' => 'Capacity Building for LGUs', 'time' => '8:30 AM', 'location' => 'DILG Provincial Office'],
    ['date' => '2026-05-30', 'title' => 'Monitoring & Evaluation Visit', 'time' => '10:00 AM', 'location' => 'Municipality of Cantilan'],
    ['date' => '2026-06-05', 'title' => 'CSO Grants Orientation', 'time' => '1:00 PM', 'location' => 'DILG Provincial Office'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Provincial Office Dashboard - DILG SDS</title>
  <style>
    :root {
      --po-bg: #f4f7fb;
      --po-surface: #ffffff;
      --po-sidebar: #5c2d17;
      --po-sidebar-deep: #3d1f11;
      --po-accent: #d88a2d;
      --po-accent-soft: #fff4e5;
      --po-heading: #17263c;
      --po-text: #233147;
      --po-muted: #68768d;
      --po-border: rgba(27, 40, 60, 0.08);
      --po-shadow: 0 12px 28px rgba(31, 42, 61, 0.08);
      --po-green: #20b883;
      --po-blue: #2d7ff9;
      --po-purple: #8a52f5;
      --po-red: #f05050;
      --po-gold: #f5a623;
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

    .brand-copy {
      line-height: 1.15;
      min-width: 0;
    }
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

    .sidebar-profile {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 14px 4px 0;
      padding: 10px 8px;
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 12px;
      background: rgba(255,255,255,0.08);
    }
    .sidebar-profile-avatar {
      width: 36px;
      height: 36px;
      display: grid;
      place-items: center;
      flex: 0 0 36px;
      overflow: hidden;
      border: 1px solid rgba(255,255,255,0.28);
      border-radius: 50%;
      background: rgba(255,255,255,0.14);
      font-size: 0.82rem;
      font-weight: 800;
    }
    .sidebar-profile-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .sidebar-profile-info { min-width: 0; }
    .sidebar-profile-status { display: flex; align-items: center; gap: 6px; font-size: 0.72rem; color: rgba(255,255,255,0.88); }
    .sidebar-profile-status::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: #34d399; box-shadow: 0 0 0 3px rgba(52,211,153,0.14); }
    .sidebar-profile-name { display: block; margin-top: 3px; color: #fff; font-size: 0.78rem; font-weight: 700; }

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

    .nav-main {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      min-width: 0;
    }
    .nav-icon {
      width: 18px;
      height: 18px;
      display: inline-flex;
      align-items: center;
      flex-shrink: 0;
    }
    .nav-icon svg {
      width: 18px;
      height: 18px;
      justify-content: center;
      font-size: 1rem;
      flex-shrink: 0;
    }
    .nav-label {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
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
      padding: 8px 8px 0;
      font-weight: 700;
      opacity: 0.95;
    }
    .footer-pill .seal {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      background: rgba(255,255,255,0.08);
      font-size: 0.8rem;
      color: #ffdcb6;
    }

    .content {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
    }

    .topbar {
      background: rgba(255,255,255,0.93);
      border-bottom: 1px solid var(--po-border);
      backdrop-filter: blur(10px);
      padding: 16px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      position: sticky;
      top: 0;
      z-index: 30;
    }

    .topbar-title {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }

    .title-mark {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: rgba(216,138,45,0.09);
      display: grid;
      place-items: center;
      font-weight: 800;
      color: var(--po-sidebar);
      flex-shrink: 0;
    }
    .title-copy {
      min-width: 0;
    }
    .title-copy strong {
      display: block;
      font-size: 0.7rem;
      letter-spacing: 0.08em;
      color: var(--po-sidebar);
      text-transform: uppercase;
    }
    .title-copy span {
      display: block;
      font-size: 0.74rem;
      color: var(--po-muted);
      margin-top: 2px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .topbar-tools {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-shrink: 0;
    }

    .search {
      display: flex;
      align-items: center;
      gap: 10px;
      width: min(320px, 38vw);
      min-width: 180px;
      background: #fff;
      border: 1px solid var(--po-border);
      border-radius: 12px;
      padding: 10px 12px;
      box-shadow: var(--po-shadow);
    }
    .search input {
      width: 100%;
      border: none;
      outline: none;
      background: transparent;
      color: var(--po-text);
      font-size: 0.95rem;
    }
    .search > span svg { width: 16px; height: 16px; color: var(--po-purple); }

    .notif {
      position: relative;
      width: 42px;
      height: 42px;
      border-radius: 12px;
      border: 1px solid var(--po-border);
      background: #fff;
      display: grid;
      place-items: center;
      box-shadow: var(--po-shadow);
      font-size: 1.1rem;
    }
    .notif .counter {
      position: absolute;
      top: -4px;
      right: -4px;
      min-width: 18px;
      height: 18px;
      padding: 0 5px;
      border-radius: 999px;
      background: var(--po-red);
      color: #fff;
      display: grid;
      place-items: center;
      font-size: 0.68rem;
      font-weight: 800;
    }
    .notif > svg { width: 18px; height: 18px; color: var(--po-accent); }

    .profile-wrap {
      position: relative;
    }
    .profile {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #fff;
      border: 1px solid var(--po-border);
      border-radius: 12px;
      padding: 7px 10px 7px 8px;
      box-shadow: var(--po-shadow);
      cursor: pointer;
      color: var(--po-text);
    }
    .avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, #2d7ff9, #00a4a3);
      color: #fff;
      display: grid;
      place-items: center;
      font-weight: 800;
      font-size: 0.8rem;
      flex-shrink: 0;
    }
    .profile-meta {
      display: flex;
      flex-direction: column;
      line-height: 1.2;
    }
    .profile-meta strong {
      font-size: 0.9rem;
    }
    .profile-meta span {
      font-size: 0.7rem;
      color: var(--po-muted);
    }
    .profile-menu {
      position: absolute;
      top: calc(100% + 10px);
      right: 0;
      background: #fff;
      border: 1px solid var(--po-border);
      border-radius: 12px;
      box-shadow: 0 18px 34px rgba(27, 40, 60, 0.14);
      width: 170px;
      padding: 8px;
      display: none;
      z-index: 40;
    }
    .profile-wrap.open .profile-menu { display: block; }
    .profile-menu a {
      display: block;
      padding: 10px 12px;
      border-radius: 8px;
      color: var(--po-text);
      font-weight: 600;
    }
    .profile-menu a:hover {
      background: #f5f9ff;
      color: var(--po-sidebar);
    }

    .main {
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .welcome {
      background: linear-gradient(135deg, #fffaf5 0%, #fff 100%);
      border: 1px solid rgba(216,138,45,0.18);
      border-radius: 18px;
      padding: 18px 20px;
      box-shadow: var(--po-shadow);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }
    .welcome-copy h2 {
      margin: 0;
      font-size: clamp(1.5rem, 2vw, 2rem);
      color: var(--po-heading);
    }
    .welcome-copy p {
      margin: 6px 0 0;
      color: var(--po-muted);
      font-size: 0.96rem;
    }
    .welcome-badge {
      width: 54px;
      height: 54px;
      border-radius: 16px;
      background: var(--po-accent-soft);
      display: grid;
      place-items: center;
      font-size: 1.5rem;
      color: var(--po-sidebar);
      flex-shrink: 0;
    }
    .welcome-badge svg { width: 24px; height: 24px; }

    .quick-actions {
      display: grid;
      grid-template-columns: repeat(5, minmax(120px, 1fr));
      gap: 12px;
    }
    .action {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background: #fff;
      border: 1px solid var(--po-border);
      border-radius: 14px;
      box-shadow: var(--po-shadow);
      color: var(--po-text);
      min-height: 86px;
      padding: 12px 10px;
      text-align: center;
      font-weight: 700;
      transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .action:hover {
      transform: translateY(-1px);
      box-shadow: 0 14px 24px rgba(31, 42, 61, 0.09);
    }
    .action-icon {
      width: 38px;
      height: 38px;
      border-radius: 12px;
      background: rgba(92,45,23,0.06);
      display: grid;
      place-items: center;
      color: var(--po-sidebar);
    }
    .action-icon svg { width: 20px; height: 20px; }

    .stats {
      display: grid;
      grid-template-columns: repeat(6, minmax(140px, 1fr));
      gap: 12px;
    }
    .stat-card {
      background: #fff;
      border: 1px solid var(--po-border);
      border-radius: 14px;
      box-shadow: var(--po-shadow);
      padding: 14px;
      display: flex;
      align-items: center;
      gap: 12px;
      min-height: 96px;
    }
    .stat-card[data-href] { cursor: pointer; transition: transform .18s ease, box-shadow .18s ease; }
    .stat-card[data-href]:hover, .stat-card[data-href]:focus-visible { transform: translateY(-2px); box-shadow: 0 14px 24px rgba(31, 42, 61, .1); outline: none; }
    .stat-icon {
      width: 46px;
      height: 46px;
      border-radius: 12px;
      display: grid;
      place-items: center;
      flex-shrink: 0;
      background: rgba(45,127,249,0.08);
      color: var(--po-blue);
    }
    .stat-icon svg { width: 24px; height: 24px; }
    .stat-card:nth-child(2) .stat-icon { background: rgba(245,166,35,0.1); color: var(--po-gold); }
    .stat-card:nth-child(3) .stat-icon { background: rgba(32,184,131,0.1); color: var(--po-green); }
    .stat-card:nth-child(4) .stat-icon { background: rgba(138,82,245,0.1); color: var(--po-purple); }
    .stat-card:nth-child(5) .stat-icon { background: rgba(240,80,80,0.09); color: var(--po-red); }
    .stat-card:nth-child(6) .stat-icon { background: rgba(92,45,23,0.08); color: var(--po-sidebar); }
    .stat-body { min-width: 0; }
    .stat-label {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--po-muted);
      font-weight: 800;
    }
    .stat-value {
      margin-top: 4px;
      font-size: clamp(1.4rem, 2vw, 2rem);
      font-weight: 800;
      line-height: 1.2;
      color: var(--po-heading);
    }
    .stat-note {
      margin-top: 5px;
      font-size: 0.75rem;
      color: var(--po-green);
      font-weight: 700;
    }
    .stat-note.down { color: var(--po-red); }
    .stat-breakdown {
      display: flex;
      flex-wrap: wrap;
      gap: 4px 8px;
      margin-top: 5px;
      font-size: 0.68rem;
      font-weight: 700;
      line-height: 1.2;
    }
    .stat-breakdown span { white-space: nowrap; }
    .stat-breakdown .pending { color: var(--po-gold); }
    .stat-breakdown .submitted { color: var(--po-green); }
    .stat-breakdown .overdue { color: var(--po-red); }

    .grid {
      display: grid;
      grid-template-columns: minmax(0, 1.7fr) minmax(260px, 0.9fr);
      gap: 18px;
    }
    .panel {
      background: var(--po-surface);
      border: 1px solid var(--po-border);
      border-radius: 16px;
      box-shadow: var(--po-shadow);
      padding: 16px;
    }
    .panel-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 14px;
    }
    .panel-head h3 {
      margin: 0;
      font-size: 1.05rem;
      color: var(--po-heading);
    }
    .panel-action {
      border: 1px solid var(--po-border);
      background: #f7f9fc;
      color: var(--po-sidebar);
      border-radius: 10px;
      padding: 8px 12px;
      font-weight: 700;
      cursor: pointer;
    }
    .panel-action select {
      border: 0;
      background: transparent;
      color: inherit;
      font: inherit;
      cursor: pointer;
      outline: none;
    }
    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }

    .legend {
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
      color: var(--po-muted);
      font-size: 0.8rem;
      margin-bottom: 12px;
    }
    .legend-item { display: inline-flex; align-items: center; gap: 7px; }
    .dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      display: inline-block;
    }

    .chart-wrap {
      height: 220px;
      border: 1px solid rgba(27, 40, 60, 0.04);
      border-radius: 12px;
      background: linear-gradient(180deg, #fff, #fffaf5);
      overflow: hidden;
    }

    .donut-wrap {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 14px;
    }
    .donut {
      position: relative;
      width: 200px;
      height: 200px;
      border-radius: 50%;
      background: conic-gradient(var(--po-accent) 0 42%, var(--po-blue) 42% 64%, var(--po-purple) 64% 100%);
    }
    .donut::before {
      content: "";
      position: absolute;
      inset: 30px;
      border-radius: 50%;
      background: #fff;
      box-shadow: inset 0 0 0 1px rgba(27,40,60,0.04);
    }
    .donut-center {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      text-align: center;
      z-index: 1;
      font-weight: 800;
      color: var(--po-heading);
    }
    .donut-center strong { font-size: 2rem; }
    .donut-center span { font-size: 0.8rem; color: var(--po-muted); }

    .status-list {
      width: 100%;
      display: grid;
      gap: 8px;
    }
    .status-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.88rem;
      gap: 8px;
    }
    .status-row .left {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--po-muted);
    }
    .status-row strong { color: var(--po-heading); }

    .notif-list {
      display: grid;
      gap: 12px;
    }
    .notif-item {
      display: grid;
      grid-template-columns: 36px 1fr;
      gap: 10px;
      padding: 10px 8px;
      border: 1px solid rgba(27,40,60,0.05);
      border-radius: 12px;
      background: #f9fbff;
    }
    .notif-icon {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: rgba(240,80,80,0.08);
      display: grid;
      place-items: center;
      color: var(--po-red);
    }
    .notif-item strong { display: block; margin-bottom: 4px; font-size: 0.88rem; }
    .notif-item p {
      margin: 0 0 5px;
      color: var(--po-muted);
      font-size: 0.82rem;
      line-height: 1.4;
    }
    .notif-item small { color: #7d8ba6; font-size: 0.72rem; }

    .calendar {
      display: grid;
      gap: 12px;
    }
    .calendar-item {
      display: grid;
      grid-template-columns: 54px 1fr auto;
      gap: 12px;
      align-items: center;
      background: #fafcff;
      border: 1px solid rgba(27,40,60,0.05);
      border-radius: 12px;
      padding: 10px 8px;
    }
    .date-box {
      width: 54px;
      height: 54px;
      border-radius: 12px;
      background: rgba(92,45,23,0.08);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: var(--po-sidebar);
      font-weight: 800;
      line-height: 1;
    }
    .date-box strong { font-size: 1.1rem; }
    .date-box span { font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.06em; }
    .calendar-copy strong { display: block; font-size: 0.88rem; margin-bottom: 4px; }
    .calendar-copy small { color: var(--po-muted); }
    .cal-btn {
      border: 1px solid var(--po-border);
      background: #fff;
      color: var(--po-sidebar);
      border-radius: 10px;
      padding: 8px 10px;
      font-weight: 700;
      cursor: pointer;
    }

    .lower-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.35fr) minmax(0, 0.95fr);
      gap: 18px;
    }

    .table-wrap {
      overflow: hidden;
      border: 1px solid rgba(27,40,60,0.05);
      border-radius: 12px;
      background: #fff;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 12px 12px;
      text-align: left;
      border-bottom: 1px solid rgba(27,40,60,0.06);
      font-size: 0.88rem;
      vertical-align: middle;
    }
    thead th {
      background: #f7f9fc;
      color: var(--po-muted);
      font-size: 0.72rem;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      font-weight: 800;
    }
    tbody tr:hover { background: #fafcff; }

    .status-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      padding: 6px 9px;
      font-size: 0.72rem;
      font-weight: 700;
      border: 1px solid transparent;
    }
    .status-pending { background: rgba(245,166,35,0.12); color: #b66d00; border-color: rgba(245,166,35,0.18); }
    .status-submitted { background: rgba(45,127,249,0.10); color: #0d65d7; border-color: rgba(45,127,249,0.18); }
    .status-overdue { background: rgba(240,80,80,0.10); color: #c52d2d; border-color: rgba(240,80,80,0.18); }
    .status-resolved { background: rgba(32,184,131,0.12); color: #0f8d68; border-color: rgba(32,184,131,0.18); }

    .links-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }
    .link-box {
      display: flex;
      align-items: center;
      gap: 10px;
      min-height: 58px;
      background: #f8faff;
      border: 1px solid rgba(27,40,60,0.05);
      border-radius: 12px;
      padding: 10px 12px;
      color: var(--po-text);
      font-weight: 700;
    }
    .link-box .link-icon {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      background: rgba(92,45,23,0.08);
      display: grid;
      place-items: center;
    }
    .link-box .link-icon svg { width: 18px; height: 18px; color: var(--po-sidebar); }
    .notif-icon svg { width: 19px; height: 19px; }

    .advisory {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      padding: 14px 16px;
      background: linear-gradient(90deg, rgba(216,138,45,0.08), rgba(216,138,45,0.02));
      border: 1px solid rgba(216,138,45,0.18);
      border-radius: 14px;
      box-shadow: var(--po-shadow);
    }
    .advisory-main {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }
    .advisory-main .icon {
      width: 38px;
      height: 38px;
      border-radius: 12px;
      background: rgba(216,138,45,0.12);
      display: grid;
      place-items: center;
      flex-shrink: 0;
    }
    .advisory-main .icon svg { width: 20px; height: 20px; }
    .advisory-copy strong {
      display: block;
      font-size: 0.75rem;
      letter-spacing: 0.06em;
      color: var(--po-sidebar);
      text-transform: uppercase;
    }
    .advisory-copy span {
      display: block;
      margin-top: 4px;
      color: var(--po-text);
      font-size: 0.94rem;
    }
    .advisory button {
      border: none;
      background: var(--po-sidebar);
      color: #fff;
      border-radius: 10px;
      padding: 10px 14px;
      font-weight: 700;
      cursor: pointer;
    }

    .footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      color: var(--po-muted);
      font-size: 0.82rem;
      border-top: 1px solid rgba(27,40,60,0.06);
      padding-top: 12px;
    }

    @media (max-width: 1200px) {
      .stats { grid-template-columns: repeat(3, minmax(160px, 1fr)); }
      .quick-actions { grid-template-columns: repeat(3, minmax(140px, 1fr)); }
      .grid, .lower-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 820px) {
      .po-shell,
      .shell { flex-direction: column; }
      .po-sidebar,
      .sidebar { width: 100%; }
      .nav { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .topbar { flex-wrap: wrap; }
      .search { width: 100%; }
      .stats { grid-template-columns: repeat(2, minmax(150px, 1fr)); }
      .topbar-tools { margin-left: auto; }
      .footer { flex-direction: column; }
    }

    @media (max-width: 560px) {
      .main { padding: 14px; }
      .welcome { flex-direction: column; align-items: flex-start; }
      .quick-actions, .stats { grid-template-columns: 1fr; }
      .nav { grid-template-columns: 1fr; }
      .title-copy span { display: none; }
      table th:nth-child(3), table td:nth-child(3) { display: none; }
      .links-grid { grid-template-columns: 1fr; }
      .calendar-item { grid-template-columns: 54px 1fr; }
      .cal-btn { grid-column: 2 / 3; }
      .advisory { flex-direction: column; align-items: flex-start; }
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

      <div style="display:flex;align-items:center;gap:10px;margin:14px 4px 0;padding:10px 8px;border:1px solid rgba(255,255,255,.12);border-radius:12px;background:rgba(255,255,255,.08);">
        <div style="width:36px;height:36px;display:grid;place-items:center;flex:0 0 36px;overflow:hidden;border:1px solid rgba(255,255,255,.28);border-radius:50%;background:rgba(255,255,255,.14);font-size:.82rem;font-weight:800;">
          <?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="PO User profile photo" style="width:100%;height:100%;object-fit:cover;display:block;"><?php else: ?><?= htmlspecialchars(strtoupper(substr($userName, 0, 2))) ?><?php endif; ?>
        </div>
        <div style="min-width:0;"><span style="display:flex;align-items:center;gap:6px;font-size:.72rem;color:rgba(255,255,255,.88);"><span style="width:7px;height:7px;border-radius:50%;background:#34d399;box-shadow:0 0 0 3px rgba(52,211,153,.14);"></span>Online</span><span style="display:block;margin-top:3px;color:#fff;font-size:.78rem;font-weight:700;"><?= htmlspecialchars($userName) ?></span></div>
      </div>

      <nav class="nav" aria-label="Primary navigation">
        <button class="nav-item active" type="button" onclick="window.location.href='dashboard.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('dashboard') ?></span><span class="nav-label">Dashboard</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='inquiries.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('submissions') ?></span><span class="nav-label">My Submissions</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='office.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('office') ?></span><span class="nav-label">My Office</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='calendar.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('calendar') ?></span><span class="nav-label">Calendar</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='reports.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('reports') ?></span><span class="nav-label">Reports</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='notifications.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('notifications') ?></span><span class="nav-label">Notifications</span></span><span class="nav-badge"><?= htmlspecialchars((string)$notificationCount) ?></span></button>
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

    <div class="content">
      <header class="topbar">
        <div class="topbar-title">
          <div class="title-mark">D</div>
          <div class="title-copy">
            <strong>Department of the Interior and Local Government</strong>
            <span>Province of Surigao del Sur &bull; Provincial Office Dashboard</span>
          </div>
        </div>

        <div class="topbar-tools">
          <label class="search" aria-label="Search reports">
            <span><?= po_icon('search') ?></span>
            <input id="global-search" type="search" placeholder="Search dashboard..." />
          </label>

          <div class="notif" aria-label="Notifications" onclick="window.location.href='notifications.php'" style="cursor:pointer;"><?= po_icon('notifications') ?><span class="counter"><?= htmlspecialchars((string)$notificationCount) ?></span></div>

          <div class="profile-wrap">
            <button class="profile" type="button" aria-expanded="false">
              <span class="avatar"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="PO User profile photo" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;"><?php else: ?><?= htmlspecialchars(strtoupper(substr($userName, 0, 2))) ?><?php endif; ?></span>
              <span class="profile-meta">
                <strong><?= htmlspecialchars($userName) ?></strong>
                <span>Provincial Office</span>
              </span>
            </button>
            <div class="profile-menu" role="menu">
              <a href="settings.php">Profile</a>
              <a href="#" id="logout-menu">Log Out</a>
            </div>
          </div>
        </div>
      </header>

      <main class="main">
        <section class="welcome">
          <div class="welcome-copy">
            <h2><?= htmlspecialchars($timeGreeting) ?>, <?= htmlspecialchars($userName) ?></h2>
            <p>Your provincial office dashboard is updated with the latest inquiries and actions.</p>
          </div>
          <div class="welcome-badge"><?= po_icon('submitted') ?></div>
        </section>

        <section class="quick-actions">
          <a class="action" href="inquiries.php?create=1">
            <span class="action-icon"><?= po_icon('create') ?></span>
            <span>Create New Submit</span>
          </a>
          <a class="action" href="inquiries.php">
            <span class="action-icon"><?= po_icon('document') ?></span>
            <span>My Submissions</span>
          </a>
          <a class="action" href="../../programs-and-services.html">
            <span class="action-icon"><?= po_icon('download') ?></span>
            <span>Download Forms</span>
          </a>
          <a class="action" href="../../contact-us.html">
            <span class="action-icon"><?= po_icon('phone') ?></span>
            <span>Contact DILG</span>
          </a>
        </section>

        <section class="stats" aria-label="Summary statistics">
          <article class="stat-card" role="link" tabindex="0" data-href="inquiries.php?status=ALL" data-status="TOTAL" aria-label="View total submissions and status totals">
            <div class="stat-icon"><?= po_icon('total') ?></div>
            <div class="stat-body">
              <div class="stat-label">Total</div>
              <div class="stat-value"><?= htmlspecialchars((string)$totalInquiries) ?></div>
              <div class="stat-breakdown" aria-label="Overall percentage of pending, submitted, and overdue">
                <span class="submitted">Overall <?= htmlspecialchars(number_format($overallPercentage, 1)) ?>%</span>
              </div>
            </div>
          </article>

          <article class="stat-card" role="link" tabindex="0" data-href="inquiries.php?status=PENDING" data-status="PENDING" aria-label="View pending submissions">
            <div class="stat-icon"><?= po_icon('pending') ?></div>
            <div class="stat-body">
              <div class="stat-label">Pending</div>
              <div class="stat-value"><?= htmlspecialchars((string)$pendingCount) ?></div>
              <div class="stat-note down"><?= htmlspecialchars(number_format($pendingPercentage, 1)) ?>% of total</div>
            </div>
          </article>

          <article class="stat-card" role="link" tabindex="0" data-href="inquiries.php?status=IN_PROGRESS" data-status="IN_PROGRESS" aria-label="View inquiries under review">
            <div class="stat-icon"><?= po_icon('submitted') ?></div>
            <div class="stat-body">
              <div class="stat-label">Under Review</div>
              <div class="stat-value"><?= htmlspecialchars((string)$submittedCount) ?></div>
              <div class="stat-note"><?= htmlspecialchars(number_format($submittedPercentage, 1)) ?>% of total</div>
            </div>
          </article>

          <article class="stat-card" role="link" tabindex="0" data-href="inquiries.php?status=OVERDUE" data-status="OVERDUE" aria-label="View overdue submissions">
            <div class="stat-icon"><?= po_icon('overdue') ?></div>
            <div class="stat-body">
              <div class="stat-label">Overdue</div>
              <div class="stat-value"><?= htmlspecialchars((string)$overdueCount) ?></div>
              <div class="stat-note"><?= htmlspecialchars(number_format($overduePercentage, 1)) ?>% of total</div>
            </div>
          </article>

          <article class="stat-card" role="link" tabindex="0" data-href="inquiries.php?status=APPROVED" data-status="APPROVED" aria-label="View approved submissions">
            <div class="stat-icon"><?= po_icon('submitted') ?></div>
            <div class="stat-body">
              <div class="stat-label">Approved</div>
              <div class="stat-value"><?= htmlspecialchars((string)$approvedCount) ?></div>
              <div class="stat-note">PO approved reports</div>
            </div>
          </article>

          <article class="stat-card" role="link" tabindex="0" data-href="inquiries.php?status=RETURNED" data-status="RETURNED" aria-label="View returned submissions">
            <div class="stat-icon"><?= po_icon('overdue') ?></div>
            <div class="stat-body">
              <div class="stat-label">Returned</div>
              <div class="stat-value"><?= htmlspecialchars((string)$returnedCount) ?></div>
              <div class="stat-note">Needs FO correction</div>
            </div>
          </article>

        </section>

        <section class="grid">
          <article class="panel">
            <div class="panel-head">
              <h3>Inquiries Overview</h3>
              <label class="panel-action" for="trend-period">
                <span class="sr-only">Chart period</span>
                <select id="trend-period" aria-label="Select chart period">
                  <option value="monthly" selected>Monthly</option>
                  <option value="weekly">Weekly</option>
                </select>
              </label>
            </div>
            <div class="legend">
              <span class="legend-item"><span class="dot" style="background:#d88a2d"></span>Pending</span>
              <span class="legend-item"><span class="dot" style="background:#2d7ff9"></span>Submitted</span>
              <span class="legend-item"><span class="dot" style="background:#8a52f5"></span>Overdue</span>
            </div>
            <div class="chart-wrap">
              <svg id="trend-chart" width="100%" height="100%" viewBox="0 0 620 220" preserveAspectRatio="none"></svg>
            </div>
          </article>

          <article class="panel">
            <div class="panel-head">
              <h3>Inquiries by Status</h3>
            </div>
            <div class="donut-wrap">
              <div class="donut" aria-label="Status chart">
                <div class="donut-center">
                  <div>
                    <strong><?= htmlspecialchars((string)$totalInquiries) ?></strong>
                    <span>Total</span>
                  </div>
                </div>
              </div>

              <div class="status-list">
                <div class="status-row">
                  <span class="left"><span class="dot" style="background:#d88a2d"></span>Pending</span>
                  <strong><?= htmlspecialchars((string)$pendingCount) ?></strong>
                </div>
                <div class="status-row">
                  <span class="left"><span class="dot" style="background:#2d7ff9"></span>Submitted</span>
                  <strong><?= htmlspecialchars((string)$submittedCount) ?></strong>
                </div>
                <div class="status-row">
                  <span class="left"><span class="dot" style="background:#8a52f5"></span>Overdue</span>
                  <strong><?= htmlspecialchars((string)$overdueCount) ?></strong>
                </div>
              </div>
            </div>
          </article>
        </section>

        <section class="advisory">
          <div class="advisory-main">
            <div class="icon"><?= po_icon('advisory') ?></div>
            <div class="advisory-copy">
              <strong>Announcement / Advisory</strong>
              <span>DILG Advisory: 2026 program support and civic orientation schedules are now open.</span>
            </div>
          </div>
          <button type="button">View Details</button>
        </section>

        <div class="footer">
          <div>&copy; 2026 Department of the Interior and Local Government.</div>
          <div>Privacy Policy &bull; Terms of Use &bull; v2.1.0</div>
        </div>
      </main>
    </div>
  </div>

  <script>
    const profileWrap = document.querySelector('.profile-wrap');
    const profileButton = document.querySelector('.profile');

    profileButton.addEventListener('click', function () {
      const isOpen = profileWrap.classList.toggle('open');
      profileButton.setAttribute('aria-expanded', String(isOpen));
    });

    document.addEventListener('click', function (event) {
      if (!profileWrap.contains(event.target)) {
        profileWrap.classList.remove('open');
        profileButton.setAttribute('aria-expanded', 'false');
      }
    });

    const logoutButtons = [document.getElementById('logout-btn'), document.getElementById('logout-menu')];
    logoutButtons.forEach(function (button) {
      if (!button) return;
      button.addEventListener('click', async function (event) {
        event.preventDefault();
        try {
          await fetch('../shared/auth/logout.php', { method: 'POST' });
        } catch (error) {
          // Ignore fetch failure and continue to redirect.
        }
        window.location.href = '../../index.html';
      });
    });

    const searchInput = document.getElementById('global-search');
    if (searchInput) {
      searchInput.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter') return;
        const term = this.value.trim();
        const destination = term
          ? 'inquiries.php?search=' + encodeURIComponent(term)
          : 'inquiries.php';
        window.location.href = destination;
      });
    }

    function normalizeCardStatus(status) {
      const value = String(status || '').trim().toUpperCase();
      if (value === 'COMPLETED') return 'APPROVED';
      if (value === 'SUBMITTED' || value === 'IN_PROGRESS') return 'IN_PROGRESS';
      return value;
    }

    function countOfficeEntries(value) {
      return String(value || '')
        .split(',')
        .map((entry) => entry.trim())
        .filter(Boolean).length;
    }

    function countInquiryTotalEntries(inquiry) {
      if (!inquiry) return 0;
      const totalEntries = Number(inquiry.recipient_count || 0);
      if (totalEntries > 0) return totalEntries;
      return 1;
    }

    function countExactStatus(inquiries, targetStatus) {
      if (!Array.isArray(inquiries)) return 0;

      return inquiries.reduce((total, inquiry) => {
        const status = normalizeCardStatus(inquiry.status);
        const pending = countOfficeEntries(inquiry.pending_offices);
        const submitted = countOfficeEntries(inquiry.submitted_offices);
        const overdue = countOfficeEntries(inquiry.overdue_offices);
        const returned = countOfficeEntries(inquiry.returned_offices);
        const approved = countOfficeEntries(inquiry.approved_offices);
        const totalEntries = Number(inquiry.recipient_count || 0);
        const isBroadcast = inquiry.recipient_office === 'All Municipalities / Cities';

        if (targetStatus === 'PENDING') {
          const value = totalEntries > 0
            ? (isBroadcast ? pending || Math.max(0, totalEntries - (submitted + overdue + returned + approved)) : (status === 'PENDING' ? 1 : 0))
            : (status === 'PENDING' ? 1 : 0);
          return total + value;
        }

        if (targetStatus === 'IN_PROGRESS') {
          const value = totalEntries > 0
            ? (isBroadcast ? submitted : (['SUBMITTED', 'IN_PROGRESS'].includes(status) ? 1 : 0))
            : (['SUBMITTED', 'IN_PROGRESS'].includes(status) ? 1 : 0);
          return total + value;
        }

        if (targetStatus === 'OVERDUE') {
          const value = totalEntries > 0 && isBroadcast ? overdue : (status === 'OVERDUE' ? 1 : 0);
          return total + value;
        }

        if (targetStatus === 'RETURNED') {
          const value = totalEntries > 0 && isBroadcast ? returned : (status === 'RETURNED' ? 1 : 0);
          return total + value;
        }

        if (targetStatus === 'APPROVED') {
          const value = totalEntries > 0 && isBroadcast ? approved : (status === 'APPROVED' ? 1 : 0);
          return total + value;
        }

        return total;
      }, 0);
    }

    async function syncDashboardStats() {
      try {
        const response = await fetch('../shared/auth/po_inquiries_api.php?action=list');
        const data = await response.json();
        if (!data.success || !Array.isArray(data.inquiries)) return;

        const inquiries = data.inquiries;
        const total = inquiries.reduce((sum, inquiry) => sum + countInquiryTotalEntries(inquiry), 0);
        const pending = countExactStatus(inquiries, 'PENDING');
        const inProgress = countExactStatus(inquiries, 'IN_PROGRESS');
        const overdue = countExactStatus(inquiries, 'OVERDUE');
        const approved = countExactStatus(inquiries, 'APPROVED');
        const returned = countExactStatus(inquiries, 'RETURNED');

        const cards = document.querySelectorAll('.stat-card[data-status]');
        const values = {
          TOTAL: total,
          PENDING: pending,
          IN_PROGRESS: inProgress,
          OVERDUE: overdue,
          APPROVED: approved,
          RETURNED: returned,
        };

        cards.forEach((card) => {
          const key = card.dataset.status;
          const value = Number(values[key] || 0);
          card.querySelector('.stat-value').textContent = value;

          const note = card.querySelector('.stat-note, .stat-breakdown span');
          if (note && total > 0) {
            const percent = ((value / total) * 100) || 0;
            if (key === 'TOTAL') {
              note.textContent = 'Overall ' + percent.toFixed(1) + '%';
            } else {
              note.textContent = percent.toFixed(1) + '% of total';
            }
          } else if (note && total === 0) {
            note.textContent = key === 'TOTAL' ? 'Overall 0.0%' : '0.0% of total';
          }
        });
      } catch (error) {
        console.error('Unable to sync dashboard stats:', error);
      }
    }

    document.querySelectorAll('.stat-card[data-href]').forEach(function (card) {
      const navigate = function () {
        window.location.href = card.dataset.href;
      };
      card.addEventListener('click', navigate);
      card.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          navigate();
        }
      });
    });

    syncDashboardStats();

    const trendPeriods = <?= json_encode($trendPeriods, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const trendSvg = document.getElementById('trend-chart');
    const trendPeriodSelect = document.getElementById('trend-period');
    function renderTrend(period) {
      const trendData = trendPeriods[period] || trendPeriods.monthly || [];
      if (!trendSvg || !trendData.length) return;
      const width = 620;
      const height = 220;
      const padding = { top: 18, right: 18, bottom: 28, left: 28 };
      const values = trendData.map(item => Number(item.value) || 0);
      const max = Math.max(...values, 1);
      const stepX = (width - padding.left - padding.right) / Math.max(values.length - 1, 1);
      const points = values.map((value, index) => {
        const x = padding.left + index * stepX;
        const y = height - padding.bottom - (value / max) * (height - padding.top - padding.bottom);
        return { x, y, label: trendData[index].label };
      });

      const linePath = points.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ');
      const areaPath = `${linePath} L ${points[points.length - 1].x} ${height - padding.bottom} L ${points[0].x} ${height - padding.bottom} Z`;
      const labelStep = trendData.length > 14 ? 5 : 1;
      const labels = points.map((point, index) => index % labelStep === 0 || index === points.length - 1
        ? `<text x="${point.x}" y="${height - 8}" text-anchor="middle" fill="#7b8ba6" font-size="10">${point.label}</text>`
        : '').join('');
      const dots = points.map((point) => `<circle cx="${point.x}" cy="${point.y}" r="4" fill="#2d7ff9" stroke="#fff" stroke-width="2" />`).join('');
      const grid = Array.from({ length: 4 }, (_, index) => {
        const y = padding.top + index * ((height - padding.top - padding.bottom) / 3);
        return `<line x1="${padding.left}" x2="${width - padding.right}" y1="${y}" y2="${y}" stroke="#edf2f8" stroke-width="1"/>`;
      }).join('');

      trendSvg.innerHTML = `
        <defs>
          <linearGradient id="areaFill" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0%" stop-color="#2d7ff9" stop-opacity="0.23"/>
            <stop offset="100%" stop-color="#2d7ff9" stop-opacity="0.02"/>
          </linearGradient>
        </defs>
        ${grid}
        <path d="${areaPath}" fill="url(#areaFill)"/>
        <path d="${linePath}" fill="none" stroke="#2d7ff9" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        ${dots}
        ${labels}
      `;
    }
    if (trendPeriodSelect) {
      trendPeriodSelect.addEventListener('change', function () {
        renderTrend(this.value);
      });
    }
    renderTrend(trendPeriodSelect ? trendPeriodSelect.value : 'monthly');
  </script>
</body>
</html>

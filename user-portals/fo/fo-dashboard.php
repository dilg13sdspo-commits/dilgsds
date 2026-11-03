<?php
require_once __DIR__ . '/../shared/auth/middleware.php';
require_once __DIR__ . '/../shared/auth/workflow.php';
require_role('FO USER');

$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);
workflow_ensure_schema($pdo);
workflow_sync_overdue($pdo);
$profilePhoto = get_user_profile_photo($pdo, $userId);
$foProfileStmt = $pdo->prepare("SELECT full_name, username, office_name FROM users WHERE id = :id AND role = 'FO USER' AND is_active = 1 LIMIT 1");
$foProfileStmt->execute([':id' => $userId]);
$foProfile = $foProfileStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$officeName = trim((string)($foProfile['office_name'] ?? $_SESSION['office_name'] ?? 'Assigned Field Office'));
$userName = trim((string)($foProfile['full_name'] ?? '')) ?: trim((string)($foProfile['username'] ?? $_SESSION['username'] ?? '')) ?: ($officeName ?: 'FO User');
$dashboardDate = date('l, F j, Y');
$philippineTime = new DateTime('now', new DateTimeZone('Asia/Manila'));
$greetingHour = (int)$philippineTime->format('G');
$timeGreeting = $greetingHour < 12 ? 'Good morning' : ($greetingHour < 18 ? 'Good afternoon' : 'Good evening');

function fo_count($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function fo_icon($name, $class = '') {
  $paths = [
    'dashboard' => '<path d="M3 11.5 12 4l9 7.5M5.5 10.5V20h13v-9.5M9 20v-5h6v5"/>',
    'submissions' => '<path d="M7 3h7l4 4v14H7zM14 3v5h4M10 12h5M10 16h5"/>',
    'announcement' => '<path d="M4 11 16 6v12L4 13zM4 11v2M16 9h3a2 2 0 0 1 0 4h-3"/>',
    'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/>',
    'messages' => '<path d="M5 6h14v10H8l-3 3zM8 10h8M8 13h5"/>',
    'notifications' => '<path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
    'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.9 1.9-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V20h-2.7v-.09a1.7 1.7 0 0 0-1.03-1.56 1.7 1.7 0 0 0-1.88.34l-.06.06-1.9-1.9.06-.06A1.7 1.7 0 0 0 7.8 15a1.7 1.7 0 0 0-1.56-1.03H6v-2.7h.24A1.7 1.7 0 0 0 7.8 10a1.7 1.7 0 0 0-.34-1.88L7.4 8.06l1.9-1.9.06.06A1.7 1.7 0 0 0 10.4 6a1.7 1.7 0 0 0 1.03-1.56V4h2.7v.09A1.7 1.7 0 0 0 15.2 6a1.7 1.7 0 0 0 1.88-.34l.06-.06 1.9 1.9-.06.06A1.7 1.7 0 0 0 18.6 10a1.7 1.7 0 0 0 1.56 1.03H20v2.7h-.09A1.7 1.7 0 0 0 19.4 15Z"/>',
    'logout' => '<path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/>',
    'search' => '<circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/>',
    'pending' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/>',
    'submitted' => '<circle cx="12" cy="12" r="8"/><path d="m8 12 2.5 2.5L16 9"/>',
    'overdue' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v5M12 16h.01"/>',
    'document' => '<path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6M9 17h6"/>',
    'download' => '<path d="M12 4v10M8 10l4 4 4-4M5 19h14"/>',
    'phone' => '<path d="M6 4h3l1.5 4-2 1.5a14 14 0 0 0 5 5l1.5-2 4 1.5v3a2 2 0 0 1-2 2C10.4 19.5 4.5 13.6 4.5 6a2 2 0 0 1 1.5-2Z"/>'
    , 'create' => '<path d="M12 5v14M5 12h14"/>'
    , 'upload' => '<path d="M12 15V4M8 8l4-4 4 4M5 19h14"/>'
    , 'help' => '<circle cx="12" cy="12" r="8"/><path d="M9.8 9a2.3 2.3 0 1 1 3.9 1.7c-.9.8-1.7 1.2-1.7 2.8M12 16h.01"/>'
    , 'guidelines' => '<path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6M9 17h6"/>'
    , 'analytics' => '<path d="M5 20V10M12 20V4M19 20v-7"/>'
  ];
  $path = $paths[$name] ?? $paths['document'];
  $classAttribute = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
  return '<svg' . $classAttribute . ' viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

function fo_time_ago($timestamp) {
    $diff = max(1, time() - (int) $timestamp);
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

$recipientCondition = '(assigned_to = :uid OR EXISTS (SELECT 1 FROM report_recipients rr WHERE rr.report_id = reports.id AND rr.user_id = :uid))';
$foStatusExpression = "(CASE WHEN recipient_office COLLATE utf8mb4_general_ci = 'All Municipalities / Cities' COLLATE utf8mb4_general_ci THEN COALESCE((SELECT CAST(status AS CHAR) COLLATE utf8mb4_general_ci FROM report_responses rp WHERE rp.report_id = reports.id AND rp.user_id = :response_uid LIMIT 1), 'PENDING' COLLATE utf8mb4_general_ci) ELSE CAST(status AS CHAR) COLLATE utf8mb4_general_ci END)";
$totalInquiries = fo_count($pdo, "SELECT COUNT(*) FROM reports WHERE $recipientCondition", [':uid' => $userId]);
$pendingCount = fo_count($pdo, "SELECT COUNT(*) FROM reports WHERE $recipientCondition AND ($foStatusExpression) IN ('PENDING','FORWARDED')", [':uid' => $userId, ':response_uid' => $userId]);
$submittedCount = fo_count($pdo, "SELECT COUNT(*) FROM reports WHERE $recipientCondition AND ($foStatusExpression) = 'SUBMITTED'", [':uid' => $userId, ':response_uid' => $userId]);
$inProgressCount = fo_count($pdo, "SELECT COUNT(*) FROM reports WHERE $recipientCondition AND ($foStatusExpression) = 'IN_PROGRESS'", [':uid' => $userId, ':response_uid' => $userId]);
$resolvedCount = fo_count($pdo, "SELECT COUNT(*) FROM reports WHERE $recipientCondition AND ($foStatusExpression) = 'COMPLETED'", [':uid' => $userId, ':response_uid' => $userId]);
$approvedCount = fo_count($pdo, "SELECT COUNT(*) FROM reports WHERE $recipientCondition AND ($foStatusExpression) IN ('APPROVED','COMPLETED','RESOLVED')", [':uid' => $userId, ':response_uid' => $userId]);
$returnedCount = fo_count($pdo, "SELECT COUNT(*) FROM reports WHERE $recipientCondition AND ($foStatusExpression) = 'RETURNED'", [':uid' => $userId, ':response_uid' => $userId]);
$overdueCount = fo_count($pdo, "SELECT COUNT(*) FROM reports WHERE $recipientCondition AND ($foStatusExpression) = 'OVERDUE'", [':uid' => $userId, ':response_uid' => $userId]);
$unreadNotifications = fo_count($pdo, "SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0", [':uid' => $userId]);

try {
    $documentCount = fo_count($pdo, 'SELECT COUNT(*) FROM documents WHERE user_id = :uid', [':uid' => $userId]);
} catch (Exception $e) {
    $documentCount = 0;
}

$recentReports = $pdo->prepare("SELECT id, title, status, created_at FROM reports WHERE $recipientCondition ORDER BY created_at DESC LIMIT 6");
$recentReports->execute([':uid' => $userId]);
$recentReports = $recentReports->fetchAll();

$foTaskStatusExpression = str_replace('reports.', 'r.', $foStatusExpression);
$assignedTasksStmt = $pdo->prepare("SELECT r.id, r.reference_number, r.title, $foTaskStatusExpression AS status, r.priority, r.due_at, r.fo_response, r.fo_response_at, u.username AS sender_username, u.full_name AS sender_name FROM reports r LEFT JOIN users u ON u.id = r.user_id WHERE (r.assigned_to = :uid OR EXISTS (SELECT 1 FROM report_recipients rr WHERE rr.report_id = r.id AND rr.user_id = :uid)) ORDER BY CASE WHEN ($foTaskStatusExpression) = 'OVERDUE' THEN 0 WHEN ($foTaskStatusExpression) IN ('PENDING','FORWARDED','IN_PROGRESS') THEN 1 ELSE 2 END, r.updated_at DESC LIMIT 8");
$assignedTasksStmt->bindValue(':uid', $userId, PDO::PARAM_INT);
$assignedTasksStmt->bindValue(':response_uid', $userId, PDO::PARAM_INT);
$assignedTasksStmt->execute();
$assignedTasks = $assignedTasksStmt->fetchAll();

$activityTrend = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $value = fo_count($pdo, "SELECT COUNT(*) FROM reports WHERE $recipientCondition AND DATE(created_at) = :day", [':uid' => $userId, ':day' => $date]);
    $activityTrend[] = ['label' => date('M j', strtotime($date)), 'value' => $value];
}

$notifications = [];
foreach ($recentReports as $report) {
    $notifications[] = [
        'title' => 'Inquiry update',
        'message' => 'Your inquiry, ' . $report['title'] . ', was recently updated.',
        'time' => strtotime($report['created_at'])
    ];
}

$logRows = $pdo->prepare("SELECT action, created_at, meta FROM audit_logs WHERE user_id = :uid AND action NOT IN ('login', 'logout', 'unauthorized_access') ORDER BY created_at DESC LIMIT 4");
$logRows->execute([':uid' => $userId]);
foreach ($logRows->fetchAll() as $log) {
    $notifications[] = [
        'title' => ucfirst(str_replace('_', ' ', $log['action'])),
        'message' => $log['meta'] ?: 'System activity recorded for your office.',
        'time' => strtotime($log['created_at'])
    ];
}

usort($notifications, function ($a, $b) { return $b['time'] <=> $a['time']; });
$notifications = array_slice($notifications, 0, 5);

$calendarItems = [
    ['date' => '2026-08-20', 'title' => 'Provincial Coordination Meeting', 'time' => '9:00 AM', 'type' => 'Meeting'],
    ['date' => '2026-08-24', 'title' => 'Local Governance Review', 'time' => '2:00 PM', 'type' => 'Review'],
    ['date' => '2026-08-28', 'title' => 'Public Service Forum', 'time' => '1:30 PM', 'type' => 'Forum'],
    ['date' => '2026-09-02', 'title' => 'Document Compliance Session', 'time' => '10:00 AM', 'type' => 'Training']
];

$documents = [];
try {
    $docRows = $pdo->prepare('SELECT id, original_name, file_name, file_path, file_size, created_at FROM documents WHERE user_id = :uid ORDER BY created_at DESC LIMIT 6');
    $docRows->execute([':uid' => $userId]);
    $documents = $docRows->fetchAll();
} catch (Exception $e) {
    $documents = [];
}

if (empty($documents)) {
    $documents = [
        ['original_name' => 'Building Permit Application Form', 'file_path' => '../../programs-and-services.html', 'file_size' => 0, 'created_at' => date('Y-m-d H:i:s')],
        ['original_name' => 'Project Proposal Form', 'file_path' => '../../programs-and-services.html', 'file_size' => 0, 'created_at' => date('Y-m-d H:i:s')],
        ['original_name' => 'DILG Memorandum Circular', 'file_path' => '../../issuances.html', 'file_size' => 0, 'created_at' => date('Y-m-d H:i:s')],
        ['original_name' => 'Acknowledgement Receipt', 'file_path' => '../../annual-report.html', 'file_size' => 0, 'created_at' => date('Y-m-d H:i:s')],
    ];
}

$performance = [
    ['label' => 'On-time Responses', 'value' => 85, 'color' => '#22c55e'],
    ['label' => 'Inquiries Resolved', 'value' => 75, 'color' => '#3b82f6'],
    ['label' => 'Documents Submitted', 'value' => 90, 'color' => '#8b5cf6'],
    ['label' => 'Client Satisfaction', 'value' => 96, 'color' => '#f59e0b'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FO User Dashboard - DILG</title>
  <style>
    :root {
      --green-900: #0f3e2f;
      --green-800: #1a4f3d;
      --green-700: #215e4d;
      --green-600: #2d725e;
      --green-500: #2ea37a;
      --bg: #eef3f1;
      --panel: #ffffff;
      --panel-soft: #f5f9f8;
      --text: #192a2e;
      --muted: #697780;
      --line: rgba(16, 49, 43, 0.08);
      --shadow: 0 14px 28px rgba(16, 39, 33, 0.08);
      --yellow: #f4b740;
      --orange: #f59e0b;
      --blue: #2d80f2;
      --purple: #7a5af8;
      --red: #ef4444;
      --green: #22c55e;
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
    button, input, textarea { font: inherit; }
    .sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); }

    .layout {
      display: flex;
      min-height: 100vh;
      background: linear-gradient(180deg, #edf4f1 0%, #f4f7f7 100%);
    }

    .sidebar {
      width: 270px;
      background: linear-gradient(180deg, #0b3d91 0%, #1456a0 100%);
      color: white;
      display: flex;
      flex-direction: column;
      padding: 18px 14px 12px;
      position: relative;
      box-shadow: inset -1px 0 0 rgba(255,255,255,0.06);
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
      display: grid;
      place-items: center;
      border: 1px solid rgba(255,255,255,0.1);
      font-weight: 800;
    }

    .brand-copy strong {
      display: block;
      font-size: 1.06rem;
      letter-spacing: 0.06em;
      line-height: 1.1;
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
      background: rgba(255,255,255,0.12);
      display: grid;
      place-items: center;
      font-weight: 800;
      color: #fff;
      border: 1px solid rgba(255,255,255,0.15);
    }
    .profile-avatar img, .trigger-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }

    .profile-meta {
      flex: 1;
      min-width: 0;
    }

    .profile-role {
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: 0.8rem;
      color: rgba(255,255,255,0.86);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .status-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #32d782;
      box-shadow: 0 0 0 4px rgba(50, 215, 130, 0.15);
    }

    .nav {
      margin-top: 8px;
      display: flex;
      flex-direction: column;
      gap: 4px;
      padding: 0 4px 12px;
    }

    .nav-item {
      width: 100%;
      border: none;
      border-radius: 10px;
      background: transparent;
      color: var(--sidebar-text);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 11px 10px;
      text-align: left;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s ease;
    }

    .nav-item:hover,
    .nav-item.active {
      background: rgba(255,255,255,0.12);
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
    }

    .nav-main {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      min-width: 0;
    }

    .nav-icon { font-size: 1rem; display: inline-flex; align-items: center; color: currentColor; }
    .nav-icon svg, .search-box svg, .notif-button svg, .summary-icon svg, .announce-icon svg, .notif-icon svg, .doc-icon svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; vertical-align: middle; }
    .nav-label { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nav-badge {
      min-width: 18px;
      height: 18px;
      border-radius: 999px;
      padding: 0 6px;
      background: rgba(255,255,255,0.18);
      color: #fff;
      display: grid;
      place-items: center;
      font-size: 0.68rem;
      font-weight: 800;
    }

    .sidebar-footer {
      margin-top: auto;
      border-top: 1px solid rgba(255,255,255,0.12);
      padding-top: 12px;
    }

    .footer-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 4px 8px 0;
      color: rgba(255,255,255,0.92);
      font-weight: 700;
      font-size: 0.85rem;
    }

    .footer-seal {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: rgba(255,255,255,0.1);
      display: grid;
      place-items: center;
      border: 1px solid rgba(255,255,255,0.08);
    }

    .main {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
    }

    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      background: rgba(255,255,255,0.92);
      border-bottom: 1px solid var(--line);
      backdrop-filter: blur(8px);
      padding: 14px 20px;
      position: sticky;
      top: 0;
      z-index: 20;
    }

    .topbar-left {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
      flex: 1;
    }

    .menu-btn {
      width: 42px;
      height: 42px;
      border: 1px solid var(--line);
      background: #fff;
      border-radius: 12px;
      display: grid;
      place-items: center;
      box-shadow: var(--shadow);
      cursor: pointer;
      color: var(--green-800);
      flex-shrink: 0;
    }

    .page-title {
      min-width: 0;
      flex: 1;
    }

    .page-title h1 {
      margin: 0;
      font-size: clamp(1.4rem, 2.2vw, 2.1rem);
      color: var(--green-900);
      line-height: 1.2;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .page-title p {
      margin: 6px 0 0;
      color: var(--muted);
      font-size: 0.92rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-shrink: 0;
    }

    .search-box {
      display: flex;
      align-items: center;
      gap: 8px;
      width: min(320px, 30vw);
      min-width: 180px;
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 10px 12px;
      box-shadow: var(--shadow);
      color: var(--muted);
    }

    .search-box input {
      width: 100%;
      border: none;
      background: transparent;
      color: var(--text);
      outline: none;
      font-size: 0.92rem;
    }

    .notif-button {
      position: relative;
      width: 42px;
      height: 42px;
      border: 1px solid var(--line);
      background: #fff;
      border-radius: 12px;
      display: grid;
      place-items: center;
      box-shadow: var(--shadow);
      cursor: pointer;
      color: var(--green-800);
    }

    .notif-count {
      position: absolute;
      top: -6px;
      right: -5px;
      min-width: 18px;
      height: 18px;
      border-radius: 999px;
      display: grid;
      place-items: center;
      padding: 0 5px;
      background: var(--red);
      color: #fff;
      font-size: 0.68rem;
      font-weight: 800;
    }

    .profile-wrap {
      position: relative;
    }

    .profile-trigger {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 7px 10px 7px 8px;
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 12px;
      box-shadow: var(--shadow);
      cursor: pointer;
      color: var(--text);
    }

    .trigger-avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: linear-gradient(135deg, #1f9d73, #2d80f2);
      display: grid;
      place-items: center;
      color: #fff;
      font-weight: 800;
      font-size: 0.8rem;
    }

    .trigger-meta {
      display: flex;
      flex-direction: column;
      line-height: 1.15;
      text-align: left;
    }

    .trigger-meta strong { font-size: 0.9rem; }
    .trigger-meta span { font-size: 0.7rem; color: var(--muted); }

    .profile-dropdown {
      position: absolute;
      top: calc(100% + 9px);
      right: 0;
      width: 180px;
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 12px;
      box-shadow: 0 18px 34px rgba(16, 35, 35, 0.14);
      padding: 8px;
      display: none;
      z-index: 30;
    }
    .profile-wrap.open .profile-dropdown { display: block; }
    .profile-dropdown a {
      display: block;
      padding: 10px 12px;
      border-radius: 8px;
      color: var(--text);
      font-weight: 600;
    }
    .profile-dropdown a:hover { background: #f3f8f7; color: var(--green-800); }

    .content {
      padding: 22px 20px 24px;
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .welcome-banner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      background: linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(242,248,246,0.96) 100%);
      border: 1px solid var(--line);
      border-radius: 18px;
      padding: 18px 20px;
      box-shadow: var(--shadow);
    }

    .welcome-copy h2 {
      margin: 0;
      font-size: clamp(1.6rem, 2.4vw, 2.2rem);
      color: var(--green-900);
    }
    .welcome-copy p {
      margin: 8px 0 0;
      color: var(--muted);
      font-size: 0.95rem;
    }

    .welcome-icon {
      width: 54px;
      height: 54px;
      border-radius: 16px;
      background: rgba(34,197,94,0.09);
      color: var(--green-700);
      display: grid;
      place-items: center;
      font-size: 1.5rem;
      flex-shrink: 0;
    }

    .summary-grid {
      display: grid;
      grid-template-columns: repeat(6, minmax(0, 1fr));
      gap: 12px;
    }

    .summary-card {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 14px;
      box-shadow: var(--shadow);
      padding: 12px 10px; 
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 6px;
      min-height: 92px;
      text-align: center;
      min-width: 0;
    }
    .summary-card:nth-child(7) {
      display: none;
    }
    .summary-card[data-href] { cursor: pointer; transition: transform 0.18s ease, box-shadow 0.18s ease; }
    .summary-card[data-href]:hover,
    .summary-card[data-href]:focus-visible { transform: translateY(-2px); box-shadow: 0 14px 26px rgba(16, 35, 35, 0.12); outline: none; }

    .report-viewer-modal { position: fixed; inset: 0; z-index: 80; display: none; align-items: center; justify-content: center; padding: 18px; background: rgba(15, 35, 49, 0.64); }
    .report-viewer-modal.open { display: flex; }
    .report-viewer-card { width: min(1180px, 100%); height: min(94vh, 900px); display: flex; flex-direction: column; overflow: hidden; background: #fff; border: 1px solid #dbe5ec; border-radius: 16px; box-shadow: 0 24px 70px rgba(8, 24, 55, 0.28); }
    .report-viewer-head { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 14px 18px; border-bottom: 1px solid #e2e8ef; }
    .report-viewer-head h2 { margin: 0; color: #0f2a6b; font-size: 1.05rem; }
    .report-viewer-head p { margin: 3px 0 0; color: #627487; font-size: 0.75rem; }
    .report-viewer-actions { display: flex; align-items: center; gap: 8px; }
    .report-viewer-actions a, .report-viewer-close { display: inline-flex; align-items: center; justify-content: center; min-height: 34px; padding: 7px 12px; border: 1px solid #d7e0ea; border-radius: 8px; background: #fff; color: #0f2a6b; font-size: 0.78rem; font-weight: 700; cursor: pointer; }
    .report-viewer-close { padding: 7px 10px; font-size: 1rem; }
    .report-viewer-frame { position: relative; flex: 1; min-height: 0; background: #f4f7fb; }
    .report-viewer-frame iframe { width: 100%; height: 100%; border: 0; display: block; background: #fff; }
    .report-viewer-fallback { display: none; position: absolute; inset: 0; align-items: center; justify-content: center; padding: 24px; text-align: center; color: #627487; background: #fff; }
    .report-viewer-fallback.show { display: flex; }
    .report-viewer-fallback a { margin-left: 5px; color: #0f2a6b; font-weight: 700; }
    @media (max-width: 600px) { .report-viewer-modal { padding: 8px; } .report-viewer-card { height: 96vh; border-radius: 12px; } .report-viewer-head { align-items: flex-start; } .report-viewer-head p, .report-viewer-actions a { display: none; } }

    .summary-icon {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      display: grid;
      place-items: center;
      background: rgba(17, 107, 88, 0.08);
      color: var(--green-700);
      font-size: 1.2rem;
      flex-shrink: 0;
    }
    .summary-card:nth-child(2) .summary-icon { background: rgba(244,183,64,0.12); color: var(--yellow); }
    .summary-card:nth-child(3) .summary-icon { background: rgba(45,128,242,0.10); color: var(--blue); }
    .summary-card:nth-child(4) .summary-icon { background: rgba(122,90,248,0.10); color: var(--purple); }
    .summary-card:nth-child(5) .summary-icon { background: rgba(239,68,68,0.10); color: var(--red); }
    .summary-card:nth-child(6) .summary-icon { background: rgba(25,42,46,0.08); color: var(--green-800); }

    .summary-body {
      min-width: 0;
      display: flex;
      flex-direction: column;
      gap: 3px;
      width: 100%;
    }
    .summary-label {
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--muted);
      font-weight: 700;
      line-height: 1.2;
    }
    .summary-value {
      margin-top: 0px;
      font-size: 1.6rem;
      font-weight: 800;
      color: var(--text);
      line-height: 1;
    }
    .summary-note {
      margin-top: 1px;
      font-size: 0.65rem;
      font-weight: 600;
      color: var(--green-700);
      white-space: normal;
      line-height: 1.15;
    }
    .summary-note.down { color: var(--red); }

    .announcement-bar {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 14px;
      box-shadow: var(--shadow);
      padding: 12px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .announce-left {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
      flex: 1;
    }
    .announce-icon {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      background: rgba(255, 193, 7, 0.12);
      display: grid;
      place-items: center;
      color: var(--orange);
      flex-shrink: 0;
    }
    .announce-copy {
      min-width: 0;
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }
    .announce-copy strong {
      font-size: 0.8rem;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--orange);
    }
    .announce-copy span {
      color: var(--text);
      font-size: 0.96rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .view-detail-btn {
      border: 1px solid var(--line);
      background: #f6faf9;
      color: var(--green-800);
      border-radius: 10px;
      padding: 9px 12px;
      font-weight: 700;
      cursor: pointer;
      white-space: nowrap;
    }

    .main-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.7fr) minmax(260px, 0.9fr);
      gap: 18px;
    }
    .panel {
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 16px;
      box-shadow: var(--shadow);
      padding: 16px;
    }
    .panel-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 12px;
    }
    .panel-head h3 {
      margin: 0;
      font-size: 1.05rem;
      color: var(--green-900);
    }
    .panel-action {
      border: 1px solid var(--line);
      background: #f7faf9;
      color: var(--green-800);
      border-radius: 10px;
      padding: 8px 12px;
      font-weight: 700;
      cursor: pointer;
    }
    .assigned-tasks-panel { margin: 18px 0; }
    .assigned-task-list { display: grid; gap: 8px; }
    .assigned-task-row { display: grid; grid-template-columns: 1.3fr 1fr auto; gap: 12px; align-items: center; padding: 11px 12px; border: 1px solid var(--line); border-radius: 10px; color: var(--text); }
    .assigned-task-row:hover { border-color: var(--green-500); background: var(--panel-soft); }
    .assigned-task-row strong, .assigned-task-row small { display: block; }
    .assigned-task-row strong { color: var(--green-800); font-size: 0.78rem; }
    .assigned-task-row small { color: var(--muted); font-size: 0.72rem; margin-top: 3px; }
    .task-status { border-radius: 999px; padding: 5px 8px; font-size: 0.67rem; font-weight: 800; white-space: nowrap; background: #eef2f2; color: var(--muted); }
    .task-status.pending { background: #fff5dd; color: #a96800; }
    .task-status.in_progress { background: #e8f1ff; color: #2868bf; }
    .task-status.overdue { background: #ffeded; color: #c53e3e; }
    .legend {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      font-size: 0.8rem;
      color: var(--muted);
      margin-bottom: 12px;
    }
    .legend-item { display: inline-flex; align-items: center; gap: 6px; }
    .dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; }
    .chart-wrap {
      height: 220px;
      border: 1px solid rgba(16,49,43,0.04);
      border-radius: 12px;
      background: linear-gradient(180deg, #fff 0%, #fafefc 100%);
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
      background: conic-gradient(#7a5af8 0 30%, #2d80f2 30% 55%, #22c55e 55% 85%, #f4b740 85% 100%);
    }
    .donut::before {
      content: "";
      position: absolute;
      inset: 28px;
      background: #fff;
      border-radius: 50%;
      box-shadow: inset 0 0 0 1px rgba(16,49,43,0.04);
    }
    .donut-center {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      text-align: center;
      z-index: 1;
      color: var(--text);
      font-weight: 800;
    }
    .donut-center strong { font-size: 2rem; line-height: 1; }
    .donut-center span { font-size: 0.8rem; color: var(--muted); }
    .status-list { width: 100%; display: grid; gap: 8px; }
    .status-row {
      display: flex; justify-content: space-between; align-items: center; gap: 8px;
      font-size: 0.87rem;
    }
    .status-row .left { display: inline-flex; align-items: center; gap: 8px; color: var(--muted); }
    .status-row strong { color: var(--text); }

    .quick-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(110px,1fr));
      gap: 12px;
    }
    .quick-action {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 12px 10px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 8px;
      min-height: 86px;
      text-align: center;
      box-shadow: var(--shadow);
      color: var(--text);
      font-weight: 700;
      cursor: pointer;
      transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .quick-action:hover {
      transform: translateY(-1px);
      box-shadow: 0 16px 24px rgba(16, 35, 35, 0.1);
    }
    .quick-icon {
      width: 38px;
      height: 38px;
      border-radius: 12px;
      display: grid;
      place-items: center;
      background: rgba(17, 107, 88, 0.08);
      color: var(--green-800);
      font-size: 1.2rem;
    }

    .lower-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.35fr) minmax(0, 0.95fr);
      gap: 18px;
    }

    .table-wrap {
      overflow: hidden;
      border: 1px solid rgba(16,49,43,0.06);
      border-radius: 12px;
      background: #fff;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      border-bottom: 1px solid rgba(16,49,43,0.06);
      padding: 12px 12px;
      text-align: left;
      font-size: 0.88rem;
      vertical-align: middle;
    }
    thead th {
      background: #f7faf9;
      color: var(--muted);
      font-size: 0.72rem;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      font-weight: 800;
    }
    tbody tr:hover { background: #fafdfc; }

    .status-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      border: 1px solid transparent;
      padding: 6px 9px;
      font-size: 0.72rem;
      font-weight: 700;
    }
    .status-pending { background: rgba(244,183,64,0.12); color: #a86a00; border-color: rgba(244,183,64,0.16); }
    .status-submitted { background: rgba(45,128,242,0.10); color: #0754c7; border-color: rgba(45,128,242,0.16); }
    .status-overdue { background: rgba(239,68,68,0.10); color: #c52d2d; border-color: rgba(239,68,68,0.16); }
    .status-resolved { background: rgba(34,197,94,0.12); color: #118a4f; border-color: rgba(34,197,94,0.16); }

    .icon-btn {
      border: 1px solid var(--line);
      background: #f8faf9;
      border-radius: 8px;
      width: 28px;
      height: 28px;
      display: inline-grid;
      place-items: center;
      cursor: pointer;
      color: var(--green-800);
      margin-left: 6px;
      font-size: 0.9rem;
    }

    .calendar-list {
      display: grid;
      gap: 12px;
    }
    .calendar-item {
      display: grid;
      grid-template-columns: 52px 1fr auto;
      gap: 10px;
      align-items: center;
      padding: 10px 8px;
      border: 1px solid rgba(16,49,43,0.06);
      border-radius: 12px;
      background: #fafdfc;
    }
    .date-box {
      width: 52px;
      height: 52px;
      border-radius: 12px;
      background: rgba(17,107,88,0.08);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: var(--green-800);
      font-weight: 800;
      line-height: 1;
    }
    .date-box strong { font-size: 1.05rem; }
    .date-box span { font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.05em; }
    .calendar-copy strong { display: block; font-size: 0.86rem; margin-bottom: 4px; }
    .calendar-copy small { color: var(--muted); }
    .calendar-btn {
      border: 1px solid var(--line);
      background: #fff;
      color: var(--green-800);
      border-radius: 10px;
      padding: 8px 10px;
      font-weight: 700;
      cursor: pointer;
    }

    .notif-list {
      display: grid;
      gap: 12px;
    }
    .notif-item {
      display: grid;
      grid-template-columns: 34px 1fr;
      gap: 10px;
      padding: 10px 8px;
      border: 1px solid rgba(16,49,43,0.05);
      border-radius: 12px;
      background: #f9fbfa;
    }
    .notif-icon {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      background: rgba(239,68,68,0.08);
      display: grid;
      place-items: center;
      color: var(--red);
    }
    .notif-item strong { display: block; margin-bottom: 4px; }
    .notif-item p {
      margin: 0 0 4px;
      color: var(--muted);
      font-size: 0.82rem;
      line-height: 1.35;
    }
    .notif-item small { color: #7d8d96; font-size: 0.72rem; }

    .performance-list {
      display: grid;
      gap: 14px;
      margin-top: 6px;
    }
    .performance-row {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .performance-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      font-size: 0.84rem;
      color: var(--muted);
    }
    .progress-track {
      width: 100%;
      height: 10px;
      border-radius: 999px;
      background: #edf2f0;
      overflow: hidden;
    }
    .progress-fill {
      height: 100%;
      border-radius: inherit;
      background: linear-gradient(90deg, var(--green-600), var(--green-500));
    }

    .docs-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }
    .doc-item {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #f8faf9;
      border: 1px solid rgba(16,49,43,0.05);
      border-radius: 12px;
      padding: 10px 12px;
      min-height: 62px;
      color: var(--text);
      font-weight: 700;
    }
    .doc-icon {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      background: rgba(17,107,88,0.08);
      display: grid;
      place-items: center;
      color: var(--green-800);
      flex-shrink: 0;
    }
    .doc-meta {
      min-width: 0;
      flex: 1;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .doc-download {
      border: none;
      background: transparent;
      color: var(--green-700);
      font-size: 1.1rem;
      cursor: pointer;
    }

    .footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      color: var(--muted);
      font-size: 0.8rem;
      padding-top: 8px;
      border-top: 1px solid rgba(16,49,43,0.06);
    }

    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(10, 18, 17, 0.45);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 99;
      padding: 20px;
    }
    .modal-backdrop.open { display: flex; }
    .modal-backdrop { overflow-y: auto; }
    .submission-modal { width: min(1120px, 100%); max-height: calc(100vh - 32px); overflow-y: auto; }
    .submission-modal .modal-head { padding: 20px 24px; }
    .submission-modal .modal-body { padding: 18px 24px 24px; }
    .submission-layout { display: grid; grid-template-columns: minmax(0, 1fr) 280px; gap: 18px; }
    .submission-section { border: 1px solid var(--line); border-radius: 12px; padding: 16px; margin-bottom: 16px; }
    .submission-section h4 { margin: 0 0 14px; color: var(--green-800); font-size: 0.95rem; }
    .submission-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 16px; }
    .submission-fields .full-width { grid-column: 1 / -1; }
    .submission-modal .field { margin-bottom: 0; }
    .submission-modal .field input, .submission-modal .field select, .submission-modal .field textarea { min-height: 40px; }
    .submission-modal .field textarea { min-height: 82px; }
    .upload-zone { border: 1px dashed #b7c9c3; border-radius: 10px; padding: 18px; text-align: center; color: var(--muted); cursor: pointer; background: #fbfdfc; }
    .upload-zone:hover, .upload-zone.dragover { border-color: var(--green-600); background: #f1faf6; }
    .upload-zone svg { width: 24px; height: 24px; color: var(--green-700); margin-bottom: 6px; }
    .attached-file { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 10px; padding: 9px 10px; border: 1px solid var(--line); border-radius: 8px; font-size: 0.82rem; }
    .guide-card { border: 1px solid var(--line); border-radius: 12px; padding: 16px; background: #f7fbf9; margin-bottom: 14px; }
    .guide-card h4 { margin: 0 0 12px; color: var(--green-800); }
    .guide-item { display: grid; grid-template-columns: 20px 1fr; gap: 8px; margin: 12px 0; font-size: 0.78rem; color: var(--muted); }
    .guide-item strong { display: block; color: var(--text); margin-bottom: 2px; }
    .guide-check { color: var(--green-600); font-weight: 800; }
    @media (max-width: 900px) {
      .modal-backdrop { align-items: flex-start; padding: 8px; }
      .modal.submission-modal { max-height: calc(100vh - 16px); }
      .submission-layout, .submission-fields { grid-template-columns: 1fr; }
      .submission-fields .full-width { grid-column: auto; }
    }
    .modal {
      width: min(520px, 100%);
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 20px 52px rgba(8, 20, 18, 0.2);
      overflow: hidden;
      border: 1px solid rgba(16,49,43,0.08);
    }
    .modal.submission-modal {
      width: min(1120px, 100%);
      max-height: calc(100vh - 32px);
      overflow-y: auto;
    }
    .modal-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 18px 20px 12px;
      border-bottom: 1px solid rgba(16,49,43,0.08);
    }
    .modal-head h3 {
      margin: 0;
      color: var(--green-900);
    }
    .close-btn {
      border: none;
      width: 34px;
      height: 34px;
      border-radius: 10px;
      background: #f3f7f6;
      color: var(--green-800);
      cursor: pointer;
    }
    .modal-body { padding: 18px 20px 20px; }
    .field { display: flex; flex-direction: column; gap: 8px; margin-bottom: 14px; }
    .field label { font-weight: 700; color: var(--text); }
    .field input,
    .field textarea {
      width: 100%;
      border: 1px solid rgba(16,49,43,0.12);
      border-radius: 10px;
      padding: 10px 12px;
      color: var(--text);
      background: #fff;
      outline: none;
    }
    .field textarea { min-height: 120px; resize: vertical; }
    .field select { width: 100%; border: 1px solid rgba(16,49,43,0.12); border-radius: 10px; padding: 10px 12px; color: var(--text); background: #fff; outline: none; }
    .field-counter { align-self: flex-end; margin-top: -4px; color: var(--muted); font-size: 0.7rem; }
    .modal-actions {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 6px;
    }
    .modal-actions button {
      min-height: 38px;
      padding: 9px 14px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      line-height: 1;
    }
    .secondary-btn {
      border: 1px solid var(--line);
      background: #fff;
      color: var(--green-800);
      border-radius: 10px;
      padding: 10px 14px;
      font-weight: 700;
      cursor: pointer;
    }
    .primary-btn {
      border: none;
      background: linear-gradient(135deg, var(--green-700), var(--green-600));
      color: #fff;
      border-radius: 10px;
      padding: 9px 14px;
      font-weight: 700;
      cursor: pointer;
    }
    .primary-btn svg { width: 15px; height: 15px; }
    .toast {
      position: fixed;
      right: 20px;
      bottom: 20px;
      background: rgba(16,49,43,0.96);
      color: #fff;
      padding: 12px 16px;
      border-radius: 12px;
      font-size: 0.9rem;
      box-shadow: 0 16px 36px rgba(16,49,43,0.2);
      opacity: 0;
      pointer-events: none;
      transform: translateY(10px);
      transition: all 0.2s ease;
      z-index: 100;
    }
    .toast.show { opacity: 1; transform: translateY(0); }

    @media (max-width: 1200px) {
      .summary-grid { grid-template-columns: repeat(3, minmax(150px, 1fr)); }
      .main-grid, .lower-grid { grid-template-columns: 1fr; }
      .search-box { width: min(260px, 30vw); }
    }

    @media (max-width: 820px) {
      .layout { flex-direction: column; }
      .sidebar { width: 100%; }
      .nav { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); }
      .topbar { flex-wrap: wrap; }
      .topbar-right { width: 100%; justify-content: space-between; }
      .search-box { width: 100%; }
      .summary-grid { grid-template-columns: repeat(2, minmax(140px, 1fr)); }
    }

    @media (max-width: 560px) {
      .content { padding: 14px; }
      .welcome-banner { flex-direction: column; align-items: flex-start; }
      .summary-grid, .quick-grid, .docs-grid { grid-template-columns: 1fr; }
      .nav { grid-template-columns: 1fr; }
      .announcement-bar { flex-direction: column; align-items: flex-start; }
      .page-title p { display: none; }
      table th:nth-child(3), table td:nth-child(3) { display: none; }
      .calendar-item { grid-template-columns: 52px 1fr; }
      .calendar-btn { grid-column: 2 / 3; }
      .footer { flex-direction: column; align-items: flex-start; }
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
        <button class="nav-item active" type="button" onclick="window.location.href='dashboard.php'"><span class="nav-main"><span class="nav-icon"><?= fo_icon('dashboard') ?></span><span class="nav-label">Dashboard</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='inquiries.php'"><span class="nav-main"><span class="nav-icon"><?= fo_icon('submissions') ?></span><span class="nav-label">My Submissions</span></span></button>
             <button class="nav-item" type="button" onclick="location.href='../../news.html'"><span class="nav-main"><span class="nav-icon"><?= fo_icon('announcement') ?></span><span class="nav-label">Announcements</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='calendar.php'"><span class="nav-main"><span class="nav-icon"><?= fo_icon('calendar') ?></span><span class="nav-label">Calendar &amp; Activities</span></span></button>
        <button class="nav-item" type="button" onclick="location.href='notifications.php'"><span class="nav-main"><span class="nav-icon"><?= fo_icon('notifications') ?></span><span class="nav-label">Notifications</span></span><span class="nav-badge"><?= htmlspecialchars((string)$unreadNotifications) ?></span></button>
        <button class="nav-item" type="button" onclick="location.href='settings.php'"><span class="nav-main"><span class="nav-icon"><?= fo_icon('settings') ?></span><span class="nav-label">Profile Settings</span></span></button>
        <button class="nav-item" type="button" id="logout-sidebar"><span class="nav-main"><span class="nav-icon"><?= fo_icon('logout') ?></span><span class="nav-label">Log Out</span></span></button>
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
            <p><?= htmlspecialchars($officeName) ?> | Welcome back! Here's your field office performance at a glance.</p>
          </div>
        </div>

        <div class="topbar-right">
          <label class="search-box" aria-label="Search dashboard">
            <span><?= fo_icon('search') ?></span>
            <input id="global-search" type="search" placeholder="Search inquiries, documents..." />
          </label>

          <button class="notif-button" type="button" aria-label="Notifications" onclick="window.location.href='notifications.php'">
            <?= fo_icon('notifications') ?>
            <span class="notif-count"><?= htmlspecialchars((string)$unreadNotifications) ?></span>
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
              <a href="settings.php?edit=1">Profile</a>
              <a href="settings.php">Settings</a>
              <a href="#" id="logout-menu">Log Out</a>
            </div>
          </div>
        </div>
      </header>

      <div class="content">
        <section class="welcome-banner">
          <div class="welcome-copy">
            <h2><?= htmlspecialchars($timeGreeting) ?>, <?= htmlspecialchars($userName) ?></h2>
            <p>Here's an overview of your inquiry activity, office updates, and required follow-ups for <?= htmlspecialchars($dashboardDate) ?>.</p>
          </div>
          <div class="welcome-icon">&#10003;</div>
        </section>

        <section class="summary-grid" aria-label="Summary cards">
          <article class="summary-card" role="link" tabindex="0" data-href="inquiries.php?status=ALL" aria-label="View all submissions">
            <div class="summary-icon"><?= fo_icon('submissions') ?></div>
            <div class="summary-body">
              <div class="summary-label">All Submissions</div>
              <div class="summary-value"><?= htmlspecialchars((string)$totalInquiries) ?></div>
              <div class="summary-note">Total submissions</div>
            </div>
          </article>

          <article class="summary-card" role="link" tabindex="0" data-href="inquiries.php?status=PENDING" aria-label="View pending reports">
            <div class="summary-icon"><?= fo_icon('pending') ?></div>
            <div class="summary-body">
              <div class="summary-label">Pending Reports</div>
              <div class="summary-value"><?= htmlspecialchars((string)$pendingCount) ?></div>
              <div class="summary-note down">- 5% from last month</div>
            </div>
          </article>

          <article class="summary-card" role="link" tabindex="0" data-href="inquiries.php?status=SUBMITTED" aria-label="View submitted reports">
            <div class="summary-icon"><?= fo_icon('submitted') ?></div>
            <div class="summary-body">
              <div class="summary-label">Submitted Reports </div>
              <div class="summary-value"><?= htmlspecialchars((string)$submittedCount) ?></div>
              <div class="summary-note">+ 9% from last month</div>
            </div>
          </article>
        
        
        <article class="summary-card" role="link" tabindex="0" data-href="inquiries.php?status=OVERDUE" aria-label="View overdue reports">
            <div class="summary-icon"><?= fo_icon('overdue') ?></div>
            <div class="summary-body">
              <div class="summary-label">Overdue Reports</div>
              <div class="summary-value"><?= htmlspecialchars((string)$overdueCount) ?></div>
              <div class="summary-note">+ 12% from last month</div>
            </div>
          </article>

          <article class="summary-card" role="link" tabindex="0" data-href="inquiries.php?status=RETURNED" aria-label="View returned reports">
            <div class="summary-icon"><?= fo_icon('overdue') ?></div>
            <div class="summary-body">
              <div class="summary-label">Return</div>
              <div class="summary-value"><?= htmlspecialchars((string)$returnedCount) ?></div>
              <div class="summary-note">Requires correction</div>
            </div>
          </article>

          <article class="summary-card" role="link" tabindex="0" data-href="inquiries.php?status=APPROVED" aria-label="View approved reports">
            <div class="summary-icon"><?= fo_icon('submitted') ?></div>
            <div class="summary-body">
              <div class="summary-label">Approve</div>
              <div class="summary-value"><?= htmlspecialchars((string)$approvedCount) ?></div>
              <div class="summary-note">Approved by PO</div>
            </div>
          </article>
        </section>

        <section class="announcement-bar">
          <div class="announce-left">
            <div class="announce-icon"><?= fo_icon('announcement') ?></div>
            <div class="announce-copy">
              <strong>Announcement</strong>
              <span>DILG Advisory: 2026 CSO Grants Program is now open for applications.</span>
            </div>
          </div>
          <button class="view-detail-btn" type="button">View Details</button>
        </section>

        <section class="panel assigned-tasks-panel" id="pending-tasks">
          <div class="panel-head">
            <h3>Assigned Tasks &amp; Responses</h3>
            <a class="panel-action" href="inquiries.php">View all tasks</a>
          </div>
          <?php if (!$assignedTasks): ?>
            <div class="empty-state">No assigned tasks yet.</div>
          <?php else: ?>
            <div class="assigned-task-list">
              <?php foreach ($assignedTasks as $task): ?>
                <?php $taskStatus = strtoupper($task['status']); $taskDue = $task['due_at'] ? date('M j, Y g:i A', strtotime($task['due_at'])) : 'No deadline'; ?>
                <a class="assigned-task-row" href="inquiries.php?view=<?= (int)$task['id'] ?>">
                  <span><strong><?= htmlspecialchars($task['reference_number'] ?: 'DILG-' . str_pad((string)$task['id'], 6, '0', STR_PAD_LEFT)) ?></strong><small><?= htmlspecialchars($task['title']) ?></small><?php if (!empty($task['fo_response'])): ?><small>Response: <?= htmlspecialchars(mb_strimwidth($task['fo_response'], 0, 80, '...')) ?></small><?php endif; ?></span>
                  <span><small>From <?= htmlspecialchars($task['sender_name'] ?: $task['sender_username'] ?: 'PO User') ?></small><small>Due <?= htmlspecialchars($taskDue) ?></small></span>
                  <span class="task-status <?= strtolower($taskStatus) ?>"><?= htmlspecialchars($taskStatus === 'SUBMITTED' ? 'SUBMITTED' : ($taskStatus === 'FORWARDED' ? 'PENDING' : $taskStatus)) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <section class="main-grid">
          <article class="panel">
            <div class="panel-head">
              <h3>My Inquiries Overview</h3>
              <button class="panel-action" type="button">This Month</button>
            </div>
            <div class="legend">
              <span class="legend-item"><span class="dot" style="background:#7a5af8"></span> Submitted</span>
              <span class="legend-item"><span class="dot" style="background:#22c55e"></span> In-Progress</span>
              <span class="legend-item"><span class="dot" style="background:#f4b740"></span> Resolved</span>
            </div>
            <div class="chart-wrap">
              <svg id="trend-svg" width="100%" height="100%" viewBox="0 0 620 220" preserveAspectRatio="none"></svg>
            </div>
          </article>

          <article class="panel">
            <div class="panel-head">
              <h3>Inquiries by Status</h3>
            </div>
            <div class="donut-wrap">
              <div class="donut" aria-label="Inquiries by status">
                <div class="donut-center">
                  <div>
                    <strong><?= htmlspecialchars((string)$totalInquiries) ?></strong>
                    <span>Total</span>
                  </div>
                </div>
              </div>

              <div class="status-list">
                <div class="status-row">
                  <span class="left"><span class="dot" style="background:#7a5af8"></span>Submitted</span>
                  <strong><?= htmlspecialchars((string)$inProgressCount) ?></strong>
                </div>
                <div class="status-row">
                  <span class="left"><span class="dot" style="background:#22c55e"></span>Pending</span>
                  <strong><?= htmlspecialchars((string)$pendingCount) ?></strong>
                </div>
                <div class="status-row">
                  <span class="left"><span class="dot" style="background:#f4b740"></span>Resolved</span>
                  <strong><?= htmlspecialchars((string)$resolvedCount) ?></strong>
                </div>
              </div>
            </div>
          </article>
        </section>

        <div class="footer">
          <div>&copy; 2026 Department of the Interior and Local Government. All rights reserved.</div>
          <div>Privacy Policy &bull; Terms of Use &bull; Contact Support</div>
        </div>
      </div>
    </main>
  </div>

  <div class="report-viewer-modal" id="report-viewer-modal" aria-hidden="true">
    <div class="report-viewer-card" role="dialog" aria-modal="true" aria-labelledby="report-viewer-title">
      <div class="report-viewer-head">
        <div><h2 id="report-viewer-title">2024 Annual Report</h2><p>DILG Surigao del Sur publication</p></div>
        <div class="report-viewer-actions">
          <a href="https://online.fliphtml5.com/uyttw/eyon/#p=5" target="_blank" rel="noopener">Open in New Tab</a>
          <button class="report-viewer-close" type="button" id="close-report-viewer" aria-label="Close annual report">&times;</button>
        </div>
      </div>
      <div class="report-viewer-frame">
        <iframe id="report-viewer-frame" title="2024 Annual Report publication" src="about:blank"></iframe>
        <div class="report-viewer-fallback" id="report-viewer-fallback">The publication could not be embedded.<a href="https://online.fliphtml5.com/uyttw/eyon/#p=5" target="_blank" rel="noopener">Open the report</a></div>
      </div>
    </div>
  </div>

  <div class="modal-backdrop" id="inquiry-modal" aria-hidden="true">
    <div class="modal submission-modal" role="dialog" aria-modal="true" aria-labelledby="inquiry-modal-title">
      <div class="modal-head">
        <div><h3 id="inquiry-modal-title">Create New Submission</h3><small>Submit reports and documents to DILG.</small></div>
        <button class="close-btn" type="button" aria-label="Close create inquiry" id="close-inquiry-modal">&times;</button>
      </div>
      <div class="modal-body">
        <form id="inquiry-form">
          <div class="submission-layout">
            <div>
              <section class="submission-section">
                <h4>1. Submission Information</h4>
                <div class="submission-fields">
                  <div class="field full-width"><label for="inquiry-category">Report/Document Type *</label><select id="inquiry-category" required><option value="">Select report type</option><option value="Administrative">Administrative Report</option><option value="Compliance">Compliance Report</option><option value="Program">Program Report</option><option value="Other">Other</option></select></div>
                  <div class="field"><label for="reporting-period">Reporting Period *</label><input id="reporting-period" type="month" required></div>
                  <div class="field"><label for="inquiry-due-date">Due Date *</label><input id="inquiry-due-date" type="date" required></div>
                  <div class="field full-width"><label for="inquiry-title">Title *</label><input id="inquiry-title" name="title" type="text" maxlength="150" required placeholder="Enter submission title"><small class="field-counter"><span id="title-count">0</span>/150</small></div>
                  <div class="field full-width"><label for="inquiry-details">Description/Remarks</label><textarea id="inquiry-details" name="details" maxlength="500" placeholder="Provide details about this submission..."></textarea><small class="field-counter"><span id="details-count">0</span>/500</small></div>
                </div>
              </section>
              <section class="submission-section">
                <h4>2. Attach Required Documents</h4>
                <div class="upload-zone" id="submission-dropzone" tabindex="0"><span><?= fo_icon('upload') ?></span><strong>Drag and drop files here or click to browse</strong><small>Supports: PDF, JPG, PNG, Excel, Word (Max 10MB per file)</small></div>
                <div id="attached-files" aria-live="polite"></div>
              </section>
              <section class="submission-section">
                <h4>3. Relevant Information</h4>
                <div class="submission-fields">
                  <div class="field"><label for="municipality">Municipality/City *</label><select id="municipality" required><option value="">Select municipality/city</option><option value="Bayabas">Bayabas</option><option value="Carrascal">Carrascal</option><option value="Cantilan">Cantilan</option><option value="Tandag City">Tandag City</option><option value="Surigao City">Surigao City</option><option value="Other">Other</option></select></div>
                  <div class="field"><label for="prepared-by">Prepared By *</label><input id="prepared-by" required placeholder="Enter your name"></div>
                  <div class="field"><label for="designation">Designation</label><input id="designation" placeholder="Enter your designation"></div>
                  <div class="field"><label for="contact-number">Contact Number</label><input id="contact-number" type="tel" placeholder="Enter contact number"></div>
                </div>
              </section>
            </div>
            <aside>
              <div class="guide-card"><h4>Submission Guidelines</h4><div class="guide-item"><span class="guide-check">&#10003;</span><div><strong>Complete Information</strong>Ensure all required fields are filled out correctly.</div></div><div class="guide-item"><span class="guide-check">&#10003;</span><div><strong>Correct Documents</strong>Upload the required documents in the specified format.</div></div><div class="guide-item"><span class="guide-check">&#10003;</span><div><strong>Timely Submission</strong>Submit before the due date to avoid delays.</div></div><div class="guide-item"><span class="guide-check">&#10003;</span><div><strong>Data Accuracy</strong>Verify all information before submitting.</div></div></div>
              <div class="guide-card"><h4>Submission Workflow</h4><div class="guide-item"><span>1.</span><div><strong>Create Submission</strong>Fill out the submission form and attach documents.</div></div><div class="guide-item"><span>2.</span><div><strong>Review by FO</strong>Field Office will review your submission.</div></div><div class="guide-item"><span>3.</span><div><strong>For Verification</strong>May be returned for additional information.</div></div><div class="guide-item"><span>4.</span><div><strong>Approved</strong>Submission is approved and processed.</div></div></div>
            </aside>
          </div>
          <div class="modal-actions">
            <button class="secondary-btn" type="button" id="cancel-inquiry">Cancel</button>
            <button class="primary-btn" type="submit"><?= fo_icon('submitted') ?> Submit Submission</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <input id="doc-input" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,image/*" style="display:none" />
  <div class="toast" id="toast"></div>

  <script>
    const toast = document.getElementById('toast');
    const showToast = (message) => {
      toast.textContent = message;
      toast.classList.add('show');
      clearTimeout(showToast.timer);
      showToast.timer = setTimeout(() => toast.classList.remove('show'), 2200);
    };

    const profileWrap = document.querySelector('.profile-wrap');
    const profileTrigger = document.querySelector('.profile-trigger');
    profileTrigger?.addEventListener('click', () => {
      const isOpen = profileWrap.classList.toggle('open');
      profileTrigger.setAttribute('aria-expanded', String(isOpen));
    });
    document.addEventListener('click', (event) => {
      if (!profileWrap.contains(event.target)) {
        profileWrap.classList.remove('open');
        profileTrigger.setAttribute('aria-expanded', 'false');
      }
    });

    const inquiryModal = document.getElementById('inquiry-modal');
    const modalOpenBtn = document.getElementById('create-nav');
    let selectedSubmissionFiles = [];

    const renderSubmissionFiles = () => {
      const attachedFiles = document.getElementById('attached-files');
      attachedFiles.innerHTML = selectedSubmissionFiles.map((file) => {
        const safeName = file.name.replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[character]);
        return `<div class="attached-file"><span>${safeName}</span><small>${(file.size / 1024 / 1024).toFixed(2)} MB</small></div>`;
      }).join('');
    };

    const selectSubmissionFiles = (files) => {
      const oversizedFile = files.find((file) => file.size > 10 * 1024 * 1024);
      if (oversizedFile) {
        showToast(`${oversizedFile.name} exceeds the 10 MB limit.`);
        return;
      }
      selectedSubmissionFiles = files;
      renderSubmissionFiles();
    };

    const readJsonResponse = async (response) => {
      const body = await response.text();
      try {
        return JSON.parse(body);
      } catch (error) {
        throw new Error(response.status === 401 ? 'Your session has expired. Please log in again.' : 'The server returned an invalid response. Please try again.');
      }
    };

    const closeInquiryModal = () => {
      inquiryModal.classList.remove('open');
      inquiryForm?.reset();
      document.getElementById('attached-files').innerHTML = '';
      document.getElementById('title-count').textContent = '0';
      document.getElementById('details-count').textContent = '0';
      selectedSubmissionFiles = [];
      document.getElementById('doc-input').value = '';
    };
    const openInquiryModal = () => inquiryModal.classList.add('open');

    modalOpenBtn?.addEventListener('click', openInquiryModal);
    document.getElementById('close-inquiry-modal')?.addEventListener('click', closeInquiryModal);
    document.getElementById('cancel-inquiry')?.addEventListener('click', closeInquiryModal);
    inquiryModal?.addEventListener('click', (event) => {
      if (event.target === inquiryModal) closeInquiryModal();
    });

    const inquiryForm = document.getElementById('inquiry-form');
    inquiryForm?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const payload = {
        title: document.getElementById('inquiry-title').value.trim(),
        details: document.getElementById('inquiry-details').value.trim(),
        category: document.getElementById('inquiry-category').value,
        due_date: document.getElementById('inquiry-due-date').value,
        reporting_period: document.getElementById('reporting-period').value,
        municipality: document.getElementById('municipality').value,
        prepared_by: document.getElementById('prepared-by').value.trim(),
        designation: document.getElementById('designation').value.trim(),
        contact_number: document.getElementById('contact-number').value.trim()
      };

      if (!payload.category || !document.getElementById('reporting-period').value || !payload.due_date || !payload.title || !document.getElementById('municipality').value.trim() || !document.getElementById('prepared-by').value.trim()) {
        showToast('Please complete all required fields before submitting.');
        return;
      }
      if (!selectedSubmissionFiles.length) {
        showToast('Please attach at least one required document before submitting.');
        document.getElementById('submission-dropzone')?.focus();
        return;
      }

      const submitButton = inquiryForm.querySelector('button[type="submit"]');
      submitButton.disabled = true;
      submitButton.textContent = 'Submitting...';
      const fullDetails = [
        payload.details,
        `Reporting period: ${payload.reporting_period}`,
        `Municipality/City: ${payload.municipality}`,
        `Prepared by: ${payload.prepared_by}`,
        payload.designation ? `Designation: ${payload.designation}` : '',
        payload.contact_number ? `Contact number: ${payload.contact_number}` : ''
      ].filter(Boolean).join('\n');

      try {
        const submissionData = new FormData();
        Object.entries({ ...payload, details: fullDetails, submission: true }).forEach(([key, value]) => submissionData.append(key, value));
        selectedSubmissionFiles.forEach((file) => submissionData.append('documents[]', file));
        const response = await fetch('../shared/auth/reports_api.php', {
          method: 'POST',
          cache: 'no-store',
          headers: { 'Accept': 'application/json' },
          body: submissionData
        });
        const result = await readJsonResponse(response);
        if (!response.ok || !result.success) {
          throw new Error(result.error || 'Failed to submit inquiry.');
        }

        inquiryForm.reset();
        closeInquiryModal();
        showToast('Submission created successfully.');
        setTimeout(() => { window.location.href = 'inquiries.php'; }, 650);
      } catch (error) {
        showToast(error.message || 'Unable to submit inquiry.');
      } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = '<?= fo_icon('submitted') ?> Submit Submission';
      }
    });

    const submissionDropzone = document.getElementById('submission-dropzone');
    submissionDropzone?.addEventListener('click', () => document.getElementById('doc-input')?.click());
    submissionDropzone?.addEventListener('keydown', (event) => { if (event.key === 'Enter' || event.key === ' ') document.getElementById('doc-input')?.click(); });
    submissionDropzone?.addEventListener('dragover', (event) => { event.preventDefault(); submissionDropzone.classList.add('dragover'); });
    submissionDropzone?.addEventListener('dragleave', () => submissionDropzone.classList.remove('dragover'));
    submissionDropzone?.addEventListener('drop', (event) => { event.preventDefault(); submissionDropzone.classList.remove('dragover'); if (event.dataTransfer.files.length) selectSubmissionFiles([...event.dataTransfer.files]); });
    document.getElementById('inquiry-title')?.addEventListener('input', (event) => { document.getElementById('title-count').textContent = event.target.value.length; });
    document.getElementById('inquiry-details')?.addEventListener('input', (event) => { document.getElementById('details-count').textContent = event.target.value.length; });

    const quickButtons = document.querySelectorAll('[data-action]');
    quickButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const action = button.dataset.action;
        switch (action) {
          case 'create-inquiry':
            openInquiryModal();
            break;
          case 'check-status':
            location.href = 'inquiries.php';
            break;
          case 'download-forms':
            location.href = '../../programs-and-services.html';
            break;
          default:
            break;
        }
      });
    });

    document.querySelectorAll('.summary-card[data-href]').forEach((card) => {
      const navigate = () => { window.location.href = card.dataset.href; };
      card.addEventListener('click', navigate);
      card.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          navigate();
        }
      });
    });

    const reportViewerModal = document.getElementById('report-viewer-modal');
    const reportViewerFrame = document.getElementById('report-viewer-frame');
    const reportViewerFallback = document.getElementById('report-viewer-fallback');
    const reportViewerUrl = 'https://online.fliphtml5.com/uyttw/eyon/#p=5';
    const openReportViewer = () => {
      reportViewerModal.classList.add('open');
      reportViewerModal.setAttribute('aria-hidden', 'false');
      reportViewerFallback.classList.remove('show');
      if (reportViewerFrame.src === 'about:blank' || !reportViewerFrame.src) reportViewerFrame.src = reportViewerUrl;
    };
    const closeReportViewer = () => {
      reportViewerModal.classList.remove('open');
      reportViewerModal.setAttribute('aria-hidden', 'true');
    };
    document.getElementById('reports-analytics-nav')?.addEventListener('click', openReportViewer);
    document.getElementById('open-report-viewer')?.addEventListener('click', openReportViewer);
    document.getElementById('close-report-viewer')?.addEventListener('click', closeReportViewer);
    reportViewerModal?.addEventListener('click', (event) => {
      if (event.target === reportViewerModal) closeReportViewer();
    });
    reportViewerFrame?.addEventListener('error', () => reportViewerFallback.classList.add('show'));
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && reportViewerModal?.classList.contains('open')) closeReportViewer();
    });

    const docInput = document.getElementById('doc-input');
    docInput?.addEventListener('change', () => {
      const files = [...docInput.files];
      const oversizedFile = files.find((file) => file.size > 10 * 1024 * 1024);
      if (oversizedFile) {
        showToast(`${oversizedFile.name} exceeds the 10 MB limit.`);
        docInput.value = '';
        return;
      }
      selectSubmissionFiles(files);
    });

    const searchInput = document.getElementById('global-search');
    searchInput?.addEventListener('input', function () {
      const term = this.value.trim().toLowerCase();
      document.querySelectorAll('#inquiries-table tbody tr').forEach((row) => {
        const value = (row.dataset.search || row.textContent || '').toLowerCase();
        row.style.display = value.includes(term) ? '' : 'none';
      });
    });

    const logoutButtons = [document.getElementById('logout-sidebar'), document.getElementById('logout-menu')];
    logoutButtons.forEach((button) => {
      if (!button) return;
      button.addEventListener('click', async (event) => {
        event.preventDefault();
        try {
          await fetch('../shared/auth/logout.php', { method: 'POST' });
        } catch (error) {}
        window.location.href = '../../index.html';
      });
    });

    const trendData = <?= json_encode($activityTrend, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const trendSvg = document.getElementById('trend-svg');
    if (trendSvg && trendData.length) {
      const width = 620;
      const height = 220;
      const padding = { top: 18, right: 18, bottom: 28, left: 28 };
      const maxValue = Math.max(...trendData.map((item) => Number(item.value) || 0), 1);
      const stepX = (width - padding.left - padding.right) / Math.max(trendData.length - 1, 1);
      const points = trendData.map((item, index) => {
        const x = padding.left + index * stepX;
        const y = height - padding.bottom - ((Number(item.value) || 0) / maxValue) * (height - padding.top - padding.bottom);
        return { x, y, label: item.label };
      });

      const linePath = points.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ');
      const areaPath = `${linePath} L ${points[points.length - 1].x} ${height - padding.bottom} L ${points[0].x} ${height - padding.bottom} Z`;
      const grid = Array.from({ length: 4 }, (_, index) => {
        const y = padding.top + index * ((height - padding.top - padding.bottom) / 3);
        return `<line x1="${padding.left}" y1="${y}" x2="${width - padding.right}" y2="${y}" stroke="#edf3f1" stroke-width="1"/>`;
      }).join('');
      const labels = points.map((point) => `<text x="${point.x}" y="${height - 8}" text-anchor="middle" fill="#7b8d99" font-size="10">${point.label}</text>`).join('');
      const dots = points.map((point) => `<circle cx="${point.x}" cy="${point.y}" r="4" fill="#2d80f2" stroke="#fff" stroke-width="2"/>`).join('');

      trendSvg.innerHTML = `
        <defs>
          <linearGradient id="fillTrend" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0%" stop-color="#2d80f2" stop-opacity="0.18"/>
            <stop offset="100%" stop-color="#2d80f2" stop-opacity="0.02"/>
          </linearGradient>
        </defs>
        ${grid}
        <path d="${areaPath}" fill="url(#fillTrend)"/>
        <path d="${linePath}" fill="none" stroke="#2d80f2" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        ${dots}
        ${labels}
      `;
    }
  </script>
</body>
</html>

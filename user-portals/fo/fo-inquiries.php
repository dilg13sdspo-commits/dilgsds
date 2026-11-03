<?php
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
$notificationStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
$notificationStmt->execute([':uid' => $userId]);
$unreadNotifications = (int)$notificationStmt->fetchColumn();

// Get status counts
$recipientCondition = '(assigned_to = ' . $userId . ' OR EXISTS (SELECT 1 FROM report_recipients rr WHERE rr.report_id = reports.id AND rr.user_id = ' . $userId . '))';
$foStatusExpression = "(CASE WHEN recipient_office COLLATE utf8mb4_general_ci = 'All Municipalities / Cities' COLLATE utf8mb4_general_ci THEN COALESCE((SELECT CAST(status AS CHAR) COLLATE utf8mb4_general_ci FROM report_responses rp WHERE rp.report_id = reports.id AND rp.user_id = $userId LIMIT 1), 'PENDING' COLLATE utf8mb4_general_ci) ELSE CAST(status AS CHAR) COLLATE utf8mb4_general_ci END)";
$statusCounts = [
  'PENDING' => (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE $recipientCondition AND $foStatusExpression IN ('PENDING','FORWARDED')")->fetchColumn(),
  'SUBMITTED' => (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE $recipientCondition AND $foStatusExpression = 'SUBMITTED'")->fetchColumn(),
  'IN_PROGRESS' => (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE $recipientCondition AND $foStatusExpression = 'IN_PROGRESS'")->fetchColumn(),
  'COMPLETED' => (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE $recipientCondition AND $foStatusExpression = 'COMPLETED'")->fetchColumn(),
  'APPROVED' => (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE $recipientCondition AND $foStatusExpression IN ('APPROVED','COMPLETED','RESOLVED')")->fetchColumn(),
  'RETURNED' => (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE $recipientCondition AND $foStatusExpression = 'RETURNED'")->fetchColumn(),
  'OVERDUE' => (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE $recipientCondition AND $foStatusExpression = 'OVERDUE'")->fetchColumn(),
];

$summary = [
    'total' => array_sum($statusCounts),
    'pending' => $statusCounts['PENDING'],
    'inProgress' => $statusCounts['SUBMITTED'],
    'resolved' => $statusCounts['COMPLETED'],
    'closed' => $statusCounts['OVERDUE'],
];

// Get all inquiries
$stmt = $pdo->prepare("SELECT r.id, r.reference_number, r.title, r.details, CASE WHEN r.recipient_office COLLATE utf8mb4_general_ci = 'All Municipalities / Cities' COLLATE utf8mb4_general_ci THEN COALESCE((SELECT CAST(rp.status AS CHAR) COLLATE utf8mb4_general_ci FROM report_responses rp WHERE rp.report_id = r.id AND rp.user_id = :response_uid LIMIT 1), 'PENDING' COLLATE utf8mb4_general_ci) ELSE CAST(r.status AS CHAR) COLLATE utf8mb4_general_ci END AS status, r.priority, r.category, r.recipient_office, r.created_at, r.updated_at, r.submitted_at, r.fo_response_at, r.due_date, r.due_at, u.username AS sender_username, u.full_name AS sender_name FROM reports r LEFT JOIN users u ON u.id = r.user_id WHERE (r.assigned_to = :uid OR EXISTS (SELECT 1 FROM report_recipients rr WHERE rr.report_id = r.id AND rr.user_id = :uid)) ORDER BY r.created_at DESC");
$stmt->execute([':uid' => $userId, ':response_uid' => $userId]);
$allInquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination
$itemsPerPage = (int)($_GET['per_page'] ?? 10);
$totalItems = count($allInquiries);
$totalPages = max(1, ceil($totalItems / $itemsPerPage));
$currentPage = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$startIdx = ($currentPage - 1) * $itemsPerPage;
$inquiries = array_slice($allInquiries, $startIdx, $itemsPerPage);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Inquiries - FO User Dashboard</title>
  <style>
    :root {
      --green-900: #0f3e2f;
      --green-800: #1a4f3d;
      --green-700: #215e4d;
      --bg: #eef3f1;
      --panel: #ffffff;
      --text: #1a2a2d;
      --text-light: #5a6d73;
      --muted: #667782;
      --line: rgba(17, 50, 44, 0.08);
      --shadow: 0 14px 28px rgba(16, 39, 33, 0.08);
      --yellow: #f4b740;
      --orange: #f59e0b;
      --blue: #2d80f2;
    }
    
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg);
      color: var(--text);
    }
    
    a { color: inherit; text-decoration: none; }
    button { font: inherit; border: none; background: none; cursor: pointer; }
    
    .layout { display: flex; min-height: 100vh; }
    
    .sidebar {
      width: 260px;
      background: linear-gradient(180deg, #0b3d91, #1456a0);
      color: white;
      padding: 18px 12px;
      overflow-y: auto;
      flex-shrink: 0;
    }
    
    .sidebar-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 0 0 16px;
      border-bottom: 1px solid rgba(255,255,255,0.12);
      margin-bottom: 16px;
    }
    
    .sidebar-logo {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(255,255,255,0.1);
      display: grid;
      place-items: center;
      font-weight: 800;
      font-size: 1.1rem;
    }
    
    .sidebar-brand strong { display: block; font-size: 0.95rem; }
    .sidebar-brand small { display: block; font-size: 0.6rem; opacity: 0.75; }
    
    .sidebar-user {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 8px;
      background: rgba(255,255,255,0.06);
      border-radius: 10px;
      margin-bottom: 14px;
    }
    
    .user-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: rgba(255,255,255,0.15);
      display: grid;
      place-items: center;
      font-weight: 800;
      font-size: 0.9rem;
    }
    .user-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }
    
    .user-info small { display: block; font-size: 0.7rem; opacity: 0.85; }
    .user-info .user-status { display: flex; align-items: center; gap: 5px; font-size: 0.68rem; opacity: 0.9; }
    .user-status::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: #34d399; box-shadow: 0 0 0 2px rgba(52,211,153,.16); }
    
    .sidebar-nav {
      display: flex;
      flex-direction: column;
      gap: 3px;
    }
    
    .sidebar-nav button {
      width: 100%;
      padding: 10px 10px;
      text-align: left;
      border-radius: 9px;
      color: rgba(255,255,255,0.85);
      font-weight: 600;
      font-size: 0.9rem;
      transition: all 0.2s;
    }
    
    .sidebar-nav button:hover,
    .sidebar-nav button.active {
      background: rgba(255,255,255,0.12);
      color: white;
    }
    
    .nav-label { display: inline-block; margin-left: 8px; }
    .nav-badge { margin-left: auto; display: inline-block; min-width: 20px; height: 20px; line-height: 20px; border-radius: 999px; background: #f4b740; color: white; text-align: center; font-size: 0.65rem; font-weight: 800; }
    
    .main {
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    
    .topbar {
      background: white;
      border-bottom: 1px solid var(--line);
      padding: 16px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
    }
    
    .topbar-left {
      display: flex;
      align-items: center;
      gap: 12px;
      flex: 1;
    }
    
    .page-icon {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      background: linear-gradient(135deg, #f6bd4b, #f29b16);
      display: grid;
      place-items: center;
      font-size: 0;
      flex-shrink: 0;
    }

    .page-icon svg {
      width: 20px;
      height: 20px;
      stroke: #fff;
    }

    .nav-svg,
    .page-icon svg,
    .stat-icon svg,
    .search-icon svg {
      width: 18px;
      height: 18px;
      fill: none;
      stroke: currentColor;
      stroke-width: 1.8;
      stroke-linecap: round;
      stroke-linejoin: round;
      vertical-align: middle;
    }
    
    .page-title h1 {
      margin: 0;
      font-size: 1.6rem;
      font-weight: 700;
    }
    
    .page-title p {
      margin: 4px 0 0;
      font-size: 0.9rem;
      color: var(--text-light);
    }
    
    .topbar-right {
      flex-shrink: 0;
    }
    
    .btn-create {
      padding: 10px 18px;
      background: linear-gradient(180deg, #215e4d, #0f3e2f);
      color: white;
      border-radius: 8px;
      font-weight: 700;
      font-size: 0.95rem;
      transition: all 0.2s;
    }
    
    .btn-create:hover {
      box-shadow: 0 6px 16px rgba(15, 62, 47, 0.2);
    }
    
    .content {
      flex: 1;
      padding: 24px;
      overflow-y: auto;
    }
    
    .stat-grid {
      display: grid;
      grid-template-columns: repeat(6, minmax(0, 1fr));
      gap: 12px;
      margin-bottom: 24px;
    }
    
    .stat-card {
      background: white;
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 12px 10px;
      box-shadow: var(--shadow);
      min-height: 92px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 6px;
      text-align: center;
    }

    .stat-icon { width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center; margin: 0; background: rgba(29,141,111,0.1); color: var(--green-700); }
    .stat-icon svg { width: 20px; height: 20px; }
    .stat-card:nth-child(2) .stat-icon { background: rgba(244,183,64,0.12); color: var(--yellow); }
    .stat-card:nth-child(3) .stat-icon { background: rgba(45,128,242,0.10); color: var(--blue); }
    .stat-card:nth-child(4) .stat-icon { background: rgba(122,90,248,0.10); color: var(--purple); }
    .stat-card:nth-child(5) .stat-icon { background: rgba(239,68,68,0.10); color: var(--red); }
    .stat-card:nth-child(6) .stat-icon { background: rgba(25,42,46,0.08); color: var(--green-800); }
    .stat-label { font-size: 0.65rem; color: var(--muted); margin-bottom: 0px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
    .stat-value { font-size: 1.6rem; font-weight: 800; color: var(--text); line-height: 1; }
    .stat-desc { font-size: 0.65rem; color: var(--green-700); font-weight: 600; margin-top: 1px; line-height: 1.15; white-space: normal; }
    
    .inquiry-section {
      background: white;
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 20px;
      box-shadow: var(--shadow);
    }
    
    .section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
      gap: 16px;
    }
    
    .section-header h2 {
      margin: 0;
      font-size: 1.1rem;
      font-weight: 700;
    }
    
    .filters-wrap {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
      flex-wrap: wrap;
    }
    
    .search-box {
      flex: 1;
      min-width: 280px;
      position: relative;
    }
    
    .search-box input {
      width: 100%;
      padding: 10px 12px 10px 36px;
      border: 1px solid var(--line);
      border-radius: 8px;
      background: #f9fbfa;
      font-size: 0.9rem;
    }
    
    .search-icon {
      position: absolute;
      left: 10px;
      top: 50%;
      transform: translateY(-50%);
      opacity: 0.6;
    }
    
    .filter-controls {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .filter-select {
      padding: 9px 10px;
      border: 1px solid var(--line);
      border-radius: 8px;
      background: white;
      font-size: 0.9rem;
      min-width: 120px;
    }
    
    .btn-filter {
      padding: 9px 14px;
      border: 1px solid var(--line);
      background: white;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.9rem;
    }
    
    .btn-clear {
      color: #ef4444;
      background: transparent;
      border: none;
      font-weight: 700;
      font-size: 0.9rem;
      cursor: pointer;
    }
    
    .table-wrap {
      overflow-x: auto;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
    }
    
    thead tr {
      background: #f7faf9;
      border-bottom: 1px solid var(--line);
    }
    
    th {
      padding: 12px 14px;
      text-align: left;
      font-weight: 700;
      font-size: 0.75rem;
      color: var(--text);
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }
    
    tbody tr {
      border-bottom: 1px solid var(--line);
    }
    
    tbody tr:hover {
      background: #f9fbfa;
    }
    
    td {
      padding: 14px;
      font-size: 0.9rem;
      color: var(--text);
    }
    
    .ref-no {
      font-weight: 700;
      color: var(--blue);
    }
    
    .status-badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: capitalize;
    }
    
    .status-pending { background: #ffeaa7; color: #d63031; }
    .status-inprogress { background: #dfe6e9; color: #2d3436; }
    .status-submitted { background: #dfe6e9; color: #2d3436; }
    .status-forwarded { background: #dfe6e9; color: #2d3436; }
    .status-resolved { background: #55efc4; color: #00b894; }
    .status-closed { background: #dfe6e9; color: #636e72; }
    .status-overdue { background: #ffb3ba; color: #b71c1c; }
    
    .priority-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 16px;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
    }
    
    .priority-low { background: #d4edda; color: #155724; }
    .priority-medium { background: #fff3cd; color: #856404; }
    .priority-high { background: #f8d7da; color: #721c24; }
    
    .action-btn {
      padding: 6px 12px;
      background: white;
      border: 1px solid var(--line);
      border-radius: 6px;
      font-weight: 600;
      font-size: 0.85rem;
      cursor: pointer;
      color: var(--blue);
      transition: all 0.2s;
    }
    
    .action-btn:hover {
      background: var(--blue);
      color: white;
      border-color: var(--blue);
    }
    
    .pagination-wrap {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 16px;
      padding-top: 16px;
      border-top: 1px solid var(--line);
    }
    
    .pagination-info {
      font-size: 0.9rem;
      color: var(--muted);
    }
    
    .pagination-controls {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .pagination-controls label {
      font-size: 0.9rem;
      color: var(--muted);
      margin-right: 6px;
    }
    
    .entries-select {
      padding: 6px 8px;
      border: 1px solid var(--line);
      border-radius: 6px;
      background: white;
      font-size: 0.9rem;
    }
    
    .page-btn {
      min-width: 32px;
      height: 32px;
      padding: 0;
      border: 1px solid var(--line);
      background: white;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      font-size: 0.85rem;
      transition: all 0.2s;
    }
    
    .page-btn.active {
      background: var(--green-700);
      color: white;
      border-color: var(--green-700);
    }
    
    .page-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    
    .empty-state {
      text-align: center;
      padding: 48px 20px;
    }
    
    .empty-icon { font-size: 4rem; opacity: 0.4; margin-bottom: 16px; }
    .empty-title { font-size: 1.1rem; font-weight: 700; color: var(--text); margin-bottom: 8px; }
    .empty-text { font-size: 0.9rem; color: var(--muted); margin-bottom: 20px; }
    
    .btn-empty { padding: 10px 16px; background: linear-gradient(180deg, #215e4d, #0f3e2f); color: white; border-radius: 8px; font-weight: 700; cursor: pointer; }
    
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.3);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }
    
    .modal.active { display: flex; }
    
    .modal-content {
      background: white;
      border-radius: 12px;
      padding: 28px;
      max-width: 600px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .modal-header h2 {
      margin: 0;
      font-size: 1.3rem;
      font-weight: 700;
    }
    
    .modal-close {
      font-size: 1.6rem;
      color: #999;
      background: none;
      border: none;
      cursor: pointer;
    }

    #detail-modal { background: rgba(17, 31, 42, .56); padding: 16px; }
    #detail-modal .modal-content { display: flex; flex-direction: column; width: min(100%, 760px); max-width: 760px; height: min(94vh, 900px); max-height: 94vh; padding: 0; overflow: hidden; border: 1px solid #dce5e5; box-shadow: 0 24px 70px rgba(15, 35, 49, .24); }
    #detail-modal .modal-header { margin: 0; padding: 18px 22px; border-bottom: 1px solid #e4eaec; }
    #detail-modal .modal-header h2 { display: flex; align-items: center; gap: 12px; color: #172536; font-size: 1.35rem; }
    #detail-modal .modal-header h2::before { content: '\01F4C4'; display: grid; place-items: center; width: 40px; height: 40px; border-radius: 11px; background: #075b49; font-size: 1.15rem; }
    #detail-modal .modal-close { display: grid; place-items: center; width: 38px; height: 38px; padding: 0; border: 1px solid #dce4e8; border-radius: 7px; color: #172536; font-size: 1.5rem; }
    .fo-detail-scroll { flex: 1 1 auto; min-height: 0; padding: 20px 22px; overflow-y: auto; }
    .fo-detail-reference { display: flex; align-items: center; gap: 14px; padding: 16px; margin-bottom: 16px; border-left: 4px solid #176650; border-radius: 6px; background: #f3f8f6; }
    .fo-detail-reference-icon, .fo-detail-icon { display: grid; place-items: center; flex: 0 0 40px; width: 40px; height: 40px; border-radius: 50%; }
    .fo-detail-reference-icon { background: #dcf1e9; color: #075b49; }
    .fo-detail-reference-label, .fo-detail-label { display: block; margin-bottom: 4px; color: #5b6a7b; font-size: .68rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .fo-detail-reference-number { color: #172536; font-size: 1.1rem; font-weight: 800; }
    .fo-detail-reference-title { margin-top: 3px; color: #5b6a7b; font-size: .82rem; }
    .fo-detail-grid { display: grid; grid-template-columns: 1fr 1fr; border: 1px solid #e0e7ea; border-radius: 7px; overflow: hidden; }
    .fo-detail-field { display: flex; align-items: flex-start; gap: 12px; min-height: 88px; padding: 16px; border-bottom: 1px solid #e0e7ea; }
    .fo-detail-field:nth-child(odd) { border-right: 1px solid #e0e7ea; }
    .fo-detail-field:nth-last-child(-n+2) { border-bottom: 0; }
    .fo-detail-icon.status { background: #fff0d3; color: #a45300; }
    .fo-detail-icon.priority { background: #fde8e8; color: #dc2626; }
    .fo-detail-icon.date { background: #e4f5ee; color: #087453; }
    .fo-detail-icon.updated { background: #f0eaff; color: #5b21b6; }
    .fo-detail-value { color: #1d2d40; font-size: .85rem; line-height: 1.45; }
    .fo-detail-badge { display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: .7rem; font-weight: 800; }
    .fo-detail-badge.pending { color: #a45300; background: #fff0c7; }
    .fo-detail-badge.in_progress { color: #155e75; background: #dff4fb; }
    .fo-detail-badge.completed { color: #06734f; background: #d9f6e9; }
    .fo-detail-badge.overdue { color: #b42318; background: #fde8e7; }
    .fo-detail-badge.high { color: #cf2525; background: #fde4e4; }
    .fo-detail-badge.medium { color: #916000; background: #fff1cf; }
    .fo-detail-badge.low { color: #176650; background: #e3f5ed; }
    .fo-detail-section { margin-top: 16px; border: 1px solid #e0e7ea; border-radius: 7px; overflow: hidden; }
    .fo-detail-section-title { padding: 14px 16px 8px; color: #5b6a7b; font-size: .68rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .fo-detail-section-body { padding: 0 16px 16px; color: #1d2d40; font-size: .85rem; line-height: 1.55; overflow-wrap: anywhere; }
    .fo-detail-section-body a { color: #075fd1; }
    .fo-po-section { border-color: #c9ddf8; border-left: 3px solid #2d80f2; }
    .fo-po-section .fo-detail-section-title { padding: 14px 16px; background: #f2f7ff; color: #145aa8; }
    .fo-po-description-label, .fo-attachment-heading { display: block; margin-bottom: 6px; color: #536579; font-size: .72rem; font-weight: 800; text-transform: uppercase; }
    .fo-po-description { padding: 11px 12px; border: 1px solid #e0e9f4; border-radius: 6px; background: #fbfdff; }
    .fo-po-meta { display: flex; justify-content: space-between; gap: 14px; margin-top: 12px; color: #536579; font-size: .78rem; }
    .fo-po-meta strong { color: #26384a; }
    .fo-po-documents { margin-top: 14px; padding-top: 12px; border-top: 1px solid #e0e9f4; }
    .fo-po-document { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 9px 10px; border: 1px solid #dbe5f0; border-radius: 6px; background: #fff; }
    .fo-po-document + .fo-po-document { margin-top: 7px; }
    .fo-po-document-info { min-width: 0; }
    .fo-po-document-info strong { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #26384a; font-size: .8rem; }
    .fo-po-document-info small { color: #64748b; font-size: .7rem; }
    .fo-po-document-actions { display: flex; flex: 0 0 auto; gap: 10px; }
    .fo-po-document-actions a { font-size: .74rem; font-weight: 700; }
    .fo-po-empty { padding: 10px; border: 1px dashed #cbd7e6; border-radius: 6px; color: #64748b; font-size: .78rem; }
    .fo-response-panel { border-color: #cde8dc; border-left: 3px solid #20a464; }
    .fo-response-panel .fo-response-header { background: #f1faf5; color: #176650; }
    .fo-response-panel .fo-response-header small { color: #477a68; }
    .fo-response-panel .fo-detail-section-body { padding: 16px; }
    .fo-detail-textarea { width: 100%; min-height: 54px; box-sizing: border-box; resize: vertical; padding: 10px; border: 1px solid #d6e0e5; border-radius: 5px; font: inherit; font-size: .8rem; }
    .fo-detail-input-row, .fo-detail-upload-row { display: flex; align-items: flex-end; gap: 10px; margin-top: 12px; }
    .fo-detail-input-row button, .fo-detail-upload-row button { white-space: nowrap; }
    .fo-detail-upload-row input { flex: 1; min-width: 0; font-size: .76rem; }
    .fo-response-header { padding: 14px 16px; background: #eaf2ff; color: #173b78; }
    .fo-response-header strong { display: block; font-size: .9rem; }
    .fo-response-header small { color: #476289; }
    .fo-response-edit { float: right; padding: 5px 9px; border: 1px solid #9bb7e4; border-radius: 5px; background: #fff; color: #31557d; font-size: .72rem; font-weight: 700; cursor: pointer; }
    .fo-response-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr); gap: 14px; }
    .fo-response-field label { display: block; margin-bottom: 6px; color: #334e7b; font-size: .72rem; font-weight: 800; }
    .fo-response-field input, .fo-response-field textarea { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #cbd7e6; border-radius: 6px; font: inherit; font-size: .82rem; }
    .fo-response-field textarea { min-height: 92px; resize: vertical; }
    .fo-response-dropzone { padding: 16px; border: 1px dashed #9bb7e4; border-radius: 6px; color: #536b91; text-align: center; cursor: pointer; background: #f8fbff; }
    .fo-response-dropzone:hover, .fo-response-dropzone.dragover { border-color: #2563b8; background: #eef5ff; }
    .fo-detail-flow { display: flex; align-items: center; justify-content: space-around; gap: 10px; text-align: center; color: #64748b; }
    .fo-detail-flow strong.active { color: #087453; }
    .fo-detail-flow small { font-weight: 400; }
    .fo-detail-document { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
    .fo-detail-document small { display: block; color: #64748b; }
    .fo-response-documents { margin-top: 14px; padding-top: 12px; border-top: 1px solid #dbe5f0; }
    .fo-response-documents-title { display: block; margin-bottom: 8px; color: #334e7b; font-size: .72rem; font-weight: 800; text-transform: uppercase; }
    .fo-response-document { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 10px; margin-top: 7px; border: 1px solid #dbe5f0; border-radius: 6px; background: #f8fbff; }
    .fo-response-document-name { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .8rem; }
    .fo-response-document a { flex: 0 0 auto; color: #075fd1; font-size: .76rem; font-weight: 700; }
    .fo-response-document-actions { display: flex; align-items: center; gap: 10px; flex: 0 0 auto; }
    .fo-response-document-remove { display: none; padding: 3px 7px; border: 1px solid #e2b8b8; border-radius: 4px; background: #fff; color: #b42318; font-size: .72rem; font-weight: 700; cursor: pointer; }
    .fo-response-document-remove:hover, .fo-response-document-remove:focus-visible { background: #fff1f0; border-color: #b42318; outline: 0; }
    #fo-response-documents.editing .fo-response-document-remove { display: inline-block; }
    .fo-detail-footer { display: flex; flex: 0 0 auto; justify-content: flex-end; gap: 10px; padding: 16px 22px; border-top: 1px solid #e4eaec; background: #fff; }
    .fo-detail-footer button { min-width: 140px; }
    .fo-detail-footer .btn-primary { background: #087453; border-color: #087453; }
    .fo-detail-footer .btn-primary:hover { background: #065d43; border-color: #065d43; }
    .fo-detail-subtitle { display: block; margin-top: 2px; color: #64748b; font-size: .74rem; font-weight: 400; }
    @media (max-width: 620px) { #detail-modal { padding: 7px; } .fo-detail-scroll { padding: 12px; } .fo-detail-grid, .fo-response-grid { grid-template-columns: 1fr; } .fo-detail-field:nth-child(odd) { border-right: 0; } .fo-detail-field:nth-last-child(-n+2) { border-bottom: 1px solid #e0e7ea; } .fo-detail-field:last-child { border-bottom: 0; } .fo-detail-input-row, .fo-detail-upload-row, .fo-detail-footer { flex-direction: column; align-items: stretch; } .fo-detail-footer button { width: 100%; } }
    
    .form-group {
      margin-bottom: 16px;
    }
    
    .form-group label {
      display: block;
      font-weight: 700;
      margin-bottom: 6px;
      color: var(--text);
      font-size: 0.9rem;
    }
    
    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--line);
      border-radius: 8px;
      font-size: 0.9rem;
      box-sizing: border-box;
    }
    
    .form-group textarea {
      resize: vertical;
      min-height: 120px;
    }
    
    .modal-actions {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
      margin-top: 24px;
    }
    
    .btn-secondary {
      padding: 10px 16px;
      border: 1px solid var(--line);
      background: white;
      border-radius: 8px;
      font-weight: 700;
      cursor: pointer;
    }
    
    .btn-primary {
      padding: 10px 16px;
      background: linear-gradient(180deg, #215e4d, #0f3e2f);
      color: white;
      border-radius: 8px;
      font-weight: 700;
      cursor: pointer;
    }
    .submission-feedback { min-height: 20px; margin-top: 12px; color: #c0392b; font-size: 0.82rem; font-weight: 600; }
    .btn-danger { padding: 10px 16px; background: #fff1f0; color: #c0392b; border: 1px solid #f2c3bd; border-radius: 8px; font-weight: 700; cursor: pointer; }
    .btn-danger:hover { background: #ffe5e1; }
    .action-btn.delete-row { margin-left: 6px; color: #c0392b; border-color: #f2c3bd; background: #fff8f7; }
    .action-btn.delete-row:hover { background: #ffe5e1; }

    .submission-modal-content { max-width: 1120px; padding: 0; }
    .submission-modal-content .modal-header { padding: 20px 24px 16px; border-bottom: 1px solid var(--line); margin-bottom: 0; }
    .submission-modal-content .modal-header small { display: block; color: var(--muted); margin-top: 4px; }
    .submission-form { padding: 18px 24px 24px; }
    .submission-layout { display: grid; grid-template-columns: minmax(0, 1fr) 280px; gap: 18px; }
    .submission-section { border: 1px solid var(--line); border-radius: 12px; padding: 16px; margin-bottom: 16px; }
    .submission-section h3 { margin: 0 0 14px; color: var(--green-800); font-size: 0.95rem; }
    .submission-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 16px; }
    .submission-fields .full-width { grid-column: 1 / -1; }
    .submission-form .form-group { margin-bottom: 0; }
    .submission-form .form-group input, .submission-form .form-group select, .submission-form .form-group textarea { min-height: 40px; }
    .submission-form .form-group textarea { min-height: 82px; }
    .field-counter { float: right; color: var(--muted); font-size: 0.7rem; font-weight: 400; }
    .upload-zone { border: 1px dashed #b7c9c3; border-radius: 10px; padding: 18px; text-align: center; color: var(--muted); cursor: pointer; background: #fbfdfc; }
    .upload-zone:hover, .upload-zone.dragover { border-color: var(--green-700); background: #f1faf6; }
    .upload-zone strong, .upload-zone small { display: block; }
    .upload-zone small { margin-top: 3px; font-size: 0.72rem; }
    .attached-file { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 10px; padding: 9px 10px; border: 1px solid var(--line); border-radius: 8px; font-size: 0.82rem; }
    .attached-file > span:first-child { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .attached-file small { margin-left: auto; color: #64748b; white-space: nowrap; }
    .attached-file-remove { flex: 0 0 auto; padding: 3px 7px; border: 1px solid #e2b8b8; border-radius: 4px; background: #fff; color: #b42318; font-size: .72rem; font-weight: 700; cursor: pointer; }
    .attached-file-remove:hover, .attached-file-remove:focus-visible { background: #fff1f0; border-color: #b42318; outline: 0; }
    .guide-card { border: 1px solid var(--line); border-radius: 12px; padding: 16px; background: #f7fbf9; margin-bottom: 14px; }
    .guide-card h3 { margin: 0 0 12px; color: var(--green-800); font-size: 0.95rem; }
    .guide-item { display: grid; grid-template-columns: 20px 1fr; gap: 8px; margin: 12px 0; font-size: 0.78rem; color: var(--muted); }
    .guide-item strong { display: block; color: var(--text); margin-bottom: 2px; }
    .guide-check { color: var(--green-700); font-weight: 800; }
    @media (max-width: 800px) {
      .submission-layout, .submission-fields { grid-template-columns: 1fr; }
      .submission-fields .full-width { grid-column: auto; }
      .submission-form { padding: 14px; }
    }
    
    @media (max-width: 1200px) {
      .stat-grid { grid-template-columns: repeat(3, 1fr); }
    }
    
    @media (max-width: 900px) {
      .layout { flex-direction: column; }
      .sidebar { width: 100%; height: auto; }
      .stat-grid { grid-template-columns: repeat(2, 1fr); }
      .filter-controls { flex-wrap: wrap; }
      .filter-select, .btn-filter { min-width: 100%; }
    }
    
    @media (max-width: 600px) {
      .topbar { flex-direction: column; align-items: flex-start; }
      .topbar-left { flex-direction: column; }
      .stat-grid { grid-template-columns: 1fr; }
      .filters-wrap { flex-direction: column; }
      .search-box { min-width: 100%; }
      th, td { padding: 8px 10px; font-size: 0.8rem; }
      .pagination-wrap { flex-direction: column; gap: 12px; }
    }
  </style>
</head>
<body>
  <div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-brand">
        <div class="sidebar-logo">D</div>
        <div>
          <strong>DILG PORTAL</strong>
          <small>Department of the Interior and Local Governance</small>
        </div>
      </div>
      
      <div class="sidebar-user">
        <div class="user-avatar"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="FO User profile photo"><?php else: ?><?= htmlspecialchars(strtoupper(substr($userName, 0, 1))) ?><?php endif; ?></div>
        <div class="user-info">
          <small class="user-status">Online</small>
          <small style="font-weight: 700;"><?= htmlspecialchars($userName) ?></small>
          <small><?= htmlspecialchars($officeName) ?></small>
        </div>
      </div>
      
      <nav class="sidebar-nav">
        <button type="button" onclick="window.location.href='dashboard.php'"><svg class="nav-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5M5.5 10.5V20h13v-9.5M9 20v-5h6v5"/></svg> <span class="nav-label">Dashboard</span></button>
        <button type="button" class="active" onclick="window.location.href='inquiries.php'"><svg class="nav-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6M9 17h6"/></svg> <span class="nav-label">My Submissions</span></button>
        <button type="button" onclick="window.location.href='../../news.html'"><svg class="nav-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11 16 6v12L4 13zM4 11v2M16 9h3a2 2 0 0 1 0 4h-3"/></svg> <span class="nav-label">Announcements</span></button>
        <button type="button" onclick="window.location.href='dashboard.php#calendar'"><svg class="nav-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01"/></svg> <span class="nav-label">Calendar & Activities</span></button>
        <button type="button" onclick="window.location.href='notifications.php'"><svg class="nav-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg> <span class="nav-label">Notifications</span><span class="nav-badge">0</span></button>
        <button type="button" onclick="window.location.href='settings.php'"><svg class="nav-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.9 1.9-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V20h-2.7v-.09a1.7 1.7 0 0 0-1.03-1.56 1.7 1.7 0 0 0-1.88.34l-.06.06-1.9-1.9.06-.06A1.7 1.7 0 0 0 7.8 15a1.7 1.7 0 0 0-1.56-1.03H6v-2.7h.24A1.7 1.7 0 0 0 7.8 10a1.7 1.7 0 0 0-.34-1.88L7.4 8.06l1.9-1.9.06.06A1.7 1.7 0 0 0 10.4 6a1.7 1.7 0 0 0 1.03-1.56V4h2.7v.09A1.7 1.7 0 0 0 15.2 6a1.7 1.7 0 0 0 1.88-.34l.06-.06 1.9 1.9-.06.06A1.7 1.7 0 0 0 18.6 10a1.7 1.7 0 0 0 1.56 1.03H20v2.7h-.09A1.7 1.7 0 0 0 19.4 15Z"/></svg> <span class="nav-label">Profile Settings</span></button>
        <button type="button" style="margin-top: auto;" onclick="logout()"><svg class="nav-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/></svg> <span class="nav-label">Logout</span></button>
      </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="main">
      <!-- Topbar -->
      <header class="topbar">
        <div class="topbar-left">
          <div class="page-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6M9 17h6"/></svg></div>
          <div class="page-title">
            <h1>My Submissions</h1>
            <p>Track and manage your submitted requests</p>
          </div>
        </div>
        <div class="topbar-right">

        </div>
      </header>
      
      <!-- Content Area -->
      <div class="content">
        <!-- Summary Cards -->




        <div class="stat-grid">
          <div class="stat-card stat-card-link" role="link" tabindex="0" data-status="ALL">
            <div class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l4 4v14H7zM14 3v5h4M10 12h5M10 16h5"/></svg></div>
            <div class="stat-label">All Submissions</div>
            <div class="stat-value"><?= htmlspecialchars((string)$summary['total']) ?></div>
            <div class="stat-desc">Total submissions</div>
          </div>
          <div class="stat-card stat-card-link" role="link" tabindex="0" data-status="PENDING">
            <div class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/></svg></div>
            <div class="stat-label">Pending Reports</div>
            <div class="stat-value"><?= $summary['pending'] ?></div>
            <div class="stat-desc">+ 20% from last month</div>
          </div>
          <div class="stat-card stat-card-link" role="link" tabindex="0" data-status="SUBMITTED">
            <div class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="m8 12 2.5 2.5L16 9"/></svg></div>
            <div class="stat-label">Submitted Reports</div>
            <div class="stat-value"><?= $summary['inProgress'] ?></div>
            <div class="stat-desc">Needs review</div>
          </div>
          <div class="stat-card stat-card-link" role="link" tabindex="0" data-status="OVERDUE">
            <div class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v5M12 16h.01"/></svg></div>
            <div class="stat-label">Overdue Reports</div>
            <div class="stat-value"><?= $summary['closed'] ?></div>
            <div class="stat-desc">In review</div>
          </div>
          <div class="stat-card stat-card-link" role="link" tabindex="0" data-status="RETURNED">
            <div class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v5M12 16h.01"/></svg></div>
            <div class="stat-label">Returned</div>
            <div class="stat-value"><?= htmlspecialchars((string)$statusCounts['RETURNED']) ?></div>
            <div class="stat-desc">Requires correction</div>
          </div>
          <div class="stat-card stat-card-link" role="link" tabindex="0" data-status="APPROVED">
            <div class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="m8 12 2.5 2.5L16 9"/></svg></div>
            <div class="stat-label">Approved</div>
            <div class="stat-value"><?= htmlspecialchars((string)$statusCounts['APPROVED']) ?></div>
            <div class="stat-desc">Approved by PO</div>
          </div>
          </div>
        
        <!-- Inquiry Section -->
        <div class="inquiry-section">
          <div class="section-header">
            <h2>LIST OF SUBMISSIONS</h2>
          </div>
          
          <!-- Filters -->
          <div class="filters-wrap">
            <div class="search-box">
              <span class="search-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg></span>
              <input id="search-input" type="text" placeholder="Search inquiries by title, reference number, or keywords..." />
            </div>
            
            <div class="filter-controls">
              <select id="status-filter" class="filter-select" onchange="applyFilters()">
                <option value="">All Status</option>
                <option value="PENDING">Pending</option>
                <option value="SUBMITTED">Submitted</option>
                <option value="OVERDUE">Overdue</option>
                <option value="RETURNED">Returned</option>
                <option value="APPROVED">Approved</option>
              </select>
              
              <select id="category-filter" class="filter-select" onchange="applyFilters()">
                <option value="">All Categories</option>
                <option value="Administrative">Administrative</option>
                <option value="Technical">Technical</option>
                <option value="Logistics">Logistics</option>
                <option value="Capacity Building">Capacity Building</option>
              </select>
              
              <select id="date-filter" class="filter-select" onchange="applyFilters()">
                <option value="">All Time</option>
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="90">Last 90 Days</option>
              </select>
              
              <button class="btn-filter" type="button" onclick="applyFilters()">Filters</button>
              <button class="btn-clear" onclick="clearFilters()">Clear Filters</button>
            </div>
          </div>
          
          <!-- Table -->
          <?php if (empty($allInquiries)): ?>
            <div class="empty-state">
              <div class="empty-icon"><svg class="nav-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6M9 17h6"/></svg></div>
              <div class="empty-title">No inquiries found</div>
              <div class="empty-text">You haven't submitted any inquiries yet.</div>
            </div>
          <?php else: ?>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Submission #</th>
                    <th>Title</th>
                    <th>Date &amp; Time Submitted</th>
                    <th>Submitted By</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Due Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="table-body">
                  <?php foreach ($inquiries as $item): ?>
                    <?php
                      $refNo = $item['reference_number'] ?: 'DILG-' . date('Y', strtotime($item['created_at'])) . '-' . str_pad($item['id'], 6, '0', STR_PAD_LEFT);
                      $status = strtoupper($item['status'] ?? 'SUBMITTED');
                      $statusClass = 'status-' . strtolower(str_replace('FORWARDED', 'PENDING', $status));
                      $priority = strtoupper($item['priority'] ?? 'MEDIUM');
                      $priorityClass = 'priority-' . strtolower($priority);
                      $dateSubmitted = !empty($item['fo_response_at']) ? date('M d, Y h:i A', strtotime($item['fo_response_at'])) : ($item['submitted_at'] ? date('M d, Y h:i A', strtotime($item['submitted_at'])) : date('M d, Y h:i A', strtotime($item['created_at'])));
                      $lastUpdated = date('M d, Y h:i A', strtotime($item['updated_at']));
                      $dueAt = $item['due_at'] ? date('M d, Y h:i A', strtotime($item['due_at'])) : 'Not specified';
                    ?>
                    <tr data-search="<?= htmlspecialchars(strtolower($item['title'] . ' ' . $refNo . ' ' . ($item['category'] ?? ''))) ?>" data-status="<?= htmlspecialchars($status) ?>" data-category="<?= htmlspecialchars($item['category'] ?? '') ?>" data-date="<?= htmlspecialchars(date('Y-m-d', strtotime($item['created_at']))) ?>">
                      <td class="ref-no"><?= htmlspecialchars($refNo) ?></td>
                      <td><?= htmlspecialchars($item['title'] ?? 'Untitled') ?></td>
                      <td><?= htmlspecialchars($dateSubmitted) ?></td>
                      <td><?= htmlspecialchars($item['sender_name'] ?: $item['sender_username'] ?: 'PO User') ?></td>
                      <td><span class="status-badge <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($status === 'FORWARDED' ? 'PENDING' : $status) ?></span></td>
                      <td><span class="priority-badge <?= htmlspecialchars($priorityClass) ?>"><?= htmlspecialchars($priority) ?></span></td>
                      <td><?= htmlspecialchars($dueAt) ?></td>
                      <td>
                        <?php $detailData = ['id' => (int)$item['id'], 'ref' => $refNo, 'title' => $item['title'] ?? '', 'details' => $item['details'] ?? '', 'status' => $status, 'priority' => $priority, 'category' => $item['category'] ?? '', 'submitted' => $dateSubmitted, 'updated' => $lastUpdated, 'due' => $item['due_at'] ?: $item['due_date']]; ?>
                        <button class="action-btn view-submission" type="button" data-submission="<?= htmlspecialchars(json_encode($detailData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>">View</button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            
            <!-- Pagination -->
            <div class="pagination-wrap">
              <div class="pagination-info">
                Showing <?= max(1, $startIdx + 1) ?> to <?= min($totalItems, $startIdx + $itemsPerPage) ?> of <?= $totalItems ?> inquiries
              </div>
              
              <div class="pagination-controls">
                <label for="entries-select">Show</label>
                <select id="entries-select" class="entries-select" onchange="changePerPage(this.value)">
                  <option value="10" <?= $itemsPerPage == 10 ? 'selected' : '' ?>>10</option>
                  <option value="25" <?= $itemsPerPage == 25 ? 'selected' : '' ?>>25</option>
                  <option value="50" <?= $itemsPerPage == 50 ? 'selected' : '' ?>>50</option>
                  <option value="100" <?= $itemsPerPage == 100 ? 'selected' : '' ?>>100</option>
                </select>
                <span style="color: var(--muted); font-size: 0.9rem;">entries</span>
                
                <button class="page-btn" <?= $currentPage == 1 ? 'disabled' : '' ?> onclick="goToPage(1)">&laquo;</button>
                <button class="page-btn" <?= $currentPage == 1 ? 'disabled' : '' ?> onclick="goToPage(<?= $currentPage - 1 ?>)">&lsaquo;</button>
                
                <?php for ($i = max(1, $currentPage - 1); $i <= min($totalPages, $currentPage + 1); $i++): ?>
                  <button class="page-btn <?= $i == $currentPage ? 'active' : '' ?>" onclick="goToPage(<?= $i ?>)"><?= $i ?></button>
                <?php endfor; ?>
                
                <button class="page-btn" <?= $currentPage == $totalPages ? 'disabled' : '' ?> onclick="goToPage(<?= $currentPage + 1 ?>)">&rsaquo;</button>
                <button class="page-btn" <?= $currentPage == $totalPages ? 'disabled' : '' ?> onclick="goToPage(<?= $totalPages ?>)">&raquo;</button>
              </div>
            </div>
            <div class="empty-state" id="filtered-empty-state" hidden>
              <div class="empty-icon"><svg class="nav-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6M9 17h6"/></svg></div>
              <div class="empty-title">No new submissions</div>
              <div class="empty-text">No new submission has been sent by a PO User.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>
  
  <!-- Create Submission Modal -->
  <div id="create-modal" class="modal">
    <div class="modal-content submission-modal-content">
      <div class="modal-header">
        <div><h2>Create New Submission</h2><small>Submit reports and documents to DILG.</small></div>
        <button class="modal-close" type="button" aria-label="Close create submission" onclick="closeModal('create-modal')">?</button>
      </div>
      <form class="submission-form" id="submission-form" onsubmit="submitSubmission(event)">
        <div class="submission-layout">
          <div>
            <section class="submission-section">
              <h3>1. Submission Information</h3>
              <div class="submission-fields">
                <div class="form-group full-width"><label for="submission-category">Report/Document Type *</label><select id="submission-category" required><option value="">Select report type</option><option value="Administrative">Administrative Report</option><option value="Compliance">Compliance Report</option><option value="Program">Program Report</option><option value="Other">Other</option></select></div>
                <div class="form-group"><label for="reporting-period">Reporting Period *</label><input id="reporting-period" type="month" required></div>
                <div class="form-group"><label for="submission-due-date">Due Date *</label><input id="submission-due-date" type="date" required></div>
                <div class="form-group full-width"><label for="submission-title">Title * <span class="field-counter"><span id="submission-title-count">0</span>/150</span></label><input id="submission-title" type="text" maxlength="150" required placeholder="Enter submission title"></div>
                <div class="form-group full-width"><label for="submission-details">Description/Remarks <span class="field-counter"><span id="submission-details-count">0</span>/500</span></label><textarea id="submission-details" maxlength="500" placeholder="Provide details about this submission..."></textarea></div>
              </div>
            </section>
            <section class="submission-section">
              <h3>2. Attach Required Documents</h3>
              <div class="upload-zone" id="submission-dropzone" tabindex="0"><strong>?</strong><strong>Drag and drop files here or click to browse</strong><small>Supports: PDF, JPG, PNG, Excel, Word (Max 10MB per file)</small></div>
              <input id="submission-file-input" type="file" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,image/*" hidden>
              <div id="submission-files" aria-live="polite"></div>
            </section>
            <section class="submission-section">
              <h3>3. Relevant Information</h3>
              <div class="submission-fields">
                <div class="form-group"><label for="municipality">Municipality/City *</label><select id="municipality" required><option value="">Select municipality/city</option><option>Bayabas</option><option>Carrascal</option><option>Cantilan</option><option>Tandag City</option><option>Surigao City</option><option>Other</option></select></div>
                <div class="form-group"><label for="prepared-by">Prepared By *</label><input id="prepared-by" required placeholder="Enter your name"></div>
                <div class="form-group"><label for="designation">Designation</label><input id="designation" placeholder="Enter your designation"></div>
                <div class="form-group"><label for="contact-number">Contact Number</label><input id="contact-number" type="tel" placeholder="Enter contact number"></div>
              </div>
            </section>
          </div>
          <aside>
            <div class="guide-card"><h3>Submission Guidelines</h3><div class="guide-item"><span class="guide-check">?</span><div><strong>Complete Information</strong>Ensure all required fields are filled out correctly.</div></div><div class="guide-item"><span class="guide-check">?</span><div><strong>Correct Documents</strong>Upload the required documents in the specified format.</div></div><div class="guide-item"><span class="guide-check">?</span><div><strong>Timely Submission</strong>Submit before the due date to avoid delays.</div></div><div class="guide-item"><span class="guide-check">?</span><div><strong>Data Accuracy</strong>Verify all information before submitting.</div></div></div>
            <div class="guide-card"><h3>Submission Workflow</h3><div class="guide-item"><span>1.</span><div><strong>Create Submission</strong>Fill out the submission form and attach documents.</div></div><div class="guide-item"><span>2.</span><div><strong>Review by FO</strong>Field Office will review your submission.</div></div><div class="guide-item"><span>3.</span><div><strong>For Verification</strong>May be returned for additional information.</div></div><div class="guide-item"><span>4.</span><div><strong>Approved</strong>Submission is approved and processed.</div></div></div>
          </aside>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" onclick="closeModal('create-modal')">Cancel</button>
          <button type="submit" class="btn-primary" id="submit-submission">Submit Submission</button>
        </div>
        <div class="submission-feedback" id="submission-feedback" role="status" aria-live="polite"></div>
      </form>
    </div>
  </div>
  
  <!-- Detail Modal -->
  <div id="detail-modal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <button class="modal-close" onclick="closeModal('detail-modal')">?</button>
      </div>
      <div class="fo-detail-scroll">
      <div class="fo-detail-reference"><span class="fo-detail-reference-icon">&#9636;</span><div><span class="fo-detail-reference-label">Reference Number</span><div class="fo-detail-reference-number" id="detail-ref">FO-0000</div><div class="fo-detail-reference-title" id="detail-title">Inquiry Title</div></div></div>
      <div class="fo-detail-grid">
        <div class="fo-detail-field"><span class="fo-detail-icon status">&#9673;</span><div><span class="fo-detail-label">Status</span><div class="fo-detail-value" id="detail-status">PENDING</div></div></div>
        <div class="fo-detail-field"><span class="fo-detail-icon priority">&#8593;</span><div><span class="fo-detail-label">Priority</span><div class="fo-detail-value" id="detail-priority">MEDIUM</div></div></div>
        <div class="fo-detail-field"><span class="fo-detail-icon date">&#9632;</span><div><span class="fo-detail-label">Date Submitted</span><div class="fo-detail-value" id="detail-submitted">-</div></div></div>
        <div class="fo-detail-field"><span class="fo-detail-icon date">&#8987;</span><div><span class="fo-detail-label">Due Date &amp; Time</span><div class="fo-detail-value" id="detail-due">-</div></div></div>
      </div>
      <section class="fo-detail-section fo-po-section"><div class="fo-detail-section-title">&#128228; &nbsp; SUBMISSION SENT BY PO</div><div class="fo-detail-section-body"><span class="fo-po-description-label">Description</span><div class="fo-po-description" id="detail-details">No description provided</div><div class="fo-po-meta"><span><strong>Sent By:</strong> PO User</span><span><strong>Sent Date &amp; Time:</strong> <span id="detail-sent">-</span></span></div><div class="fo-po-documents"><span class="fo-attachment-heading">Original PO Attachments</span><div id="document-list"><div class="fo-po-empty">No attachments submitted.</div></div></div></div></section>
      <section class="fo-detail-section" id="return-reason-panel" hidden>
        <div class="fo-detail-section-title" style="display:flex;align-items:center;gap:8px;">
          <span style="display:inline-flex;align-items:center;justify-content:center; width:18px; height:18px; border-radius:50%; background:#fbe9e7; color:#b42318; font-size:12px; font-weight:700;">?</span>
          <span>RETURN MESSAGE FROM PO</span>
        </div>
        <div class="fo-detail-section-body" style="border:1px solid #f3b4ab; border-radius:8px; background:#fff5f3; padding:0; overflow:hidden; margin-top:0;">
          <div style="padding:12px 14px 0; font-size:11px; font-weight:700; letter-spacing:0.04em; color:#b42318; text-transform:uppercase;">Return Reason / Message</div>
          <div id="return-reason-text" style="padding:0 14px 14px; min-height:42px; line-height:1.6; color:#4a433d; white-space:pre-wrap;">No return reason provided.</div>
        </div>
      </section>
      <section class="fo-detail-section fo-response-panel"><div class="fo-response-header"><button type="button" class="fo-response-edit" id="edit-response" hidden>Edit Response</button><strong>FO RESPONSE</strong><small id="response-state">Provide the response and update status for the PO.</small></div><div class="fo-detail-section-body"><form id="response-form" autocomplete="off"><div class="fo-response-grid"><div class="fo-response-field"><label for="response-at">Response Date &amp; Time <em>*</em></label><input id="response-at" name="response_at" type="datetime-local" autocomplete="off" required></div><div class="fo-response-field"><label for="response-text">Response / Action Taken <em>*</em></label><textarea id="response-text" name="response" maxlength="1000" placeholder="Type your response or action taken here..." required></textarea><small id="response-counter" style="display:block;text-align:right;color:#64748b">0 / 1000</small></div></div><div class="fo-response-field" style="margin-top:14px"><label>Proof of Response <span>(Optional)</span></label><div class="fo-response-dropzone" id="response-dropzone">Drag and drop files here or <button type="button" class="btn-secondary" id="response-browse">Choose Files</button><small style="display:block;margin-top:5px">PDF, DOC, DOCX, JPG, PNG (Max 10 MB each)</small></div><input id="response-files" name="documents[]" type="file" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" hidden><div id="response-file-list"></div></div><div class="fo-response-documents" id="response-documents" hidden><span class="fo-response-documents-title">Submitted Documents</span><div id="response-document-list"></div></div><div id="response-feedback" role="status" aria-live="polite" style="margin-top:10px;color:#b42318"></div></form></div></section>
      </div>
      <div class="fo-detail-footer">
        <button type="button" class="btn-secondary" onclick="closeModal('detail-modal')">Cancel</button>
        <button type="submit" form="response-form" class="btn-primary" id="submit-response">Submit Response</button>
      </div>
    </div>
  </div>

  <script>
    const searchInput = document.getElementById('search-input');
    const statusFilter = document.getElementById('status-filter');
    const categoryFilter = document.getElementById('category-filter');
    const dateFilter = document.getElementById('date-filter');
    
    function openCreateModal() {
      document.getElementById('create-modal').classList.add('active');
      document.getElementById('submission-title').focus();
    }
    
    function closeModal(id) {
      if (id === 'detail-modal' && detailRemarkDirty && !confirm('You have an unsaved remark. Close without saving?')) return;
      document.getElementById(id).classList.remove('active');
      if (id === 'detail-modal') detailRemarkDirty = false;
      if (id === 'create-modal') {
        document.querySelector(`#${id} form`).reset();
        document.getElementById('submission-files').innerHTML = '';
        selectedSubmissionFiles = [];
      }
    }
    
    let activeSubmissionId = 0;
    let detailRemarkDirty = false;
    let responseEditMode = false;

    function viewDetail(id, ref, title, details, status, priority, category, submitted, updated, due) {
      activeSubmissionId = id;
      document.getElementById('detail-ref').textContent = 'Reference: ' + ref;
      document.getElementById('detail-title').textContent = title;
      const statusBadge = document.getElementById('detail-status');
      statusBadge.innerHTML = `<span class="detail-badge ${String(status).toLowerCase()}">${escapeHtml(String(status).replace('_', ' '))}</span>`;
      document.getElementById('detail-priority').textContent = priority;
      document.getElementById('detail-priority').innerHTML = `<span class="detail-badge ${String(priority).toLowerCase()}">${escapeHtml(String(priority))}</span>`;
      document.getElementById('detail-submitted').textContent = formatDetailDate(submitted);
      document.getElementById('detail-sent').textContent = formatDetailDate(submitted);
      document.getElementById('detail-due').textContent = formatDetailDate(due);
      document.getElementById('detail-details').innerHTML = linkifyDetailText(details || 'No description provided');
      detailRemarkDirty = false;
      responseEditMode = false;
      document.getElementById('response-form').reset();
      document.getElementById('response-state').textContent = 'Loading response status...';
      document.getElementById('response-counter').textContent = '0 / 1000';
      document.getElementById('response-feedback').textContent = '';
      document.getElementById('response-file-list').innerHTML = '';
      document.getElementById('response-documents').hidden = true;
      document.getElementById('response-documents').classList.remove('editing');
      document.getElementById('response-document-list').innerHTML = '';
      document.getElementById('document-list').innerHTML = '<div class="fo-po-empty">Loading attachments...</div>';
      document.getElementById('return-reason-panel').hidden = true;
      document.getElementById('return-reason-text').textContent = 'No return reason provided.';
      document.getElementById('submit-response').disabled = false;
      document.getElementById('edit-response').hidden = true;
      
      document.getElementById('detail-modal').classList.add('active');
      fetch(`../shared/auth/fo_tasks_api.php?action=detail&id=${id}`).then(readJsonResponse).then(data => {
        if (!data.success || !data.task) return;
        const task = data.task;
        const comments = (task.comments || []).map(item => `<div style="margin-bottom: 6px;"><strong>${escapeHtml(item.full_name || item.username || 'User')}:</strong> ${escapeHtml(item.comment)}</div>`).join('');
        const taskComments = document.getElementById('task-comments');
        if (taskComments) taskComments.innerHTML = comments || 'No remarks yet.';
        document.getElementById('detail-due').textContent = formatDetailDate(task.due_at || task.due_date);
        document.getElementById('detail-sent').textContent = formatDetailDate(task.submitted_at || task.created_at);
        const responseSubmitted = Boolean(task.fo_response_at);
        const returnReason = String(task.returned_reason || task.return_reason || '').trim();
        const returnPanel = document.getElementById('return-reason-panel');
        const returnText = document.getElementById('return-reason-text');
        if (returnPanel && returnText) {
          const hasReturnReason = returnReason.length > 0;
          returnPanel.hidden = !hasReturnReason;
          returnText.textContent = hasReturnReason ? returnReason : 'No return reason provided.';
        }
        document.getElementById('response-text').value = task.fo_response || '';
        document.getElementById('response-counter').textContent = `${(task.fo_response || '').length} / 1000`;
        document.getElementById('response-at').value = responseSubmitted ? String(task.fo_response_at).replace(' ', 'T').slice(0, 16) : '';
        document.getElementById('response-text').disabled = responseSubmitted;
        document.getElementById('response-at').disabled = responseSubmitted;
        document.getElementById('submit-response').style.display = responseSubmitted ? 'none' : '';
        document.getElementById('response-dropzone').style.display = responseSubmitted ? 'none' : '';
        document.getElementById('edit-response').hidden = !responseSubmitted;
        document.getElementById('edit-response').textContent = 'Edit Response';
        document.getElementById('response-state').textContent = responseSubmitted ? `Response submitted on ${formatDetailDate(task.fo_response_at)}` : 'Field Office Task';
        const poDocuments = task.po_documents || [];
        const responseDocuments = task.fo_documents || [];
        const documentLink = item => `../shared/auth/download_document.php?id=${encodeURIComponent(item.id)}`;
        document.getElementById('document-list').innerHTML = poDocuments.length ? poDocuments.map(item => `<div class="fo-po-document"><span class="fo-po-document-info"><strong title="${escapeHtml(item.original_name || item.file_name || 'Document')}">${escapeHtml(item.original_name || item.file_name || 'Document')}</strong><small>${escapeHtml(item.mime_type || 'File')} | ${formatDetailFileSize(item.file_size)} | ${escapeHtml(formatDetailDate(item.created_at))}</small></span><span class="fo-po-document-actions"><a href="${documentLink(item)}&amp;mode=inline" target="_blank" rel="noopener">Preview</a><a href="${documentLink(item)}" target="_blank" rel="noopener" download>Download</a></span></div>`).join('') : '<div class="fo-po-empty">No attachments submitted.</div>';
        document.getElementById('response-document-list').innerHTML = responseDocuments.map(item => `<div class="response-document" data-response-document-id="${item.id}"><span class="response-document-name" title="${escapeHtml(item.original_name || item.file_name || 'Document')}">${escapeHtml(item.original_name || item.file_name || 'Document')} <small>${formatDetailFileSize(item.file_size)}</small></span><span class="response-document-actions"><a href="${documentLink(item)}&mode=inline" target="_blank" rel="noopener">Open</a><button type="button" class="response-document-remove" data-document-id="${item.id}" data-document-name="${escapeHtml(item.original_name || item.file_name || 'Document')}">Remove</button></span></div>`).join('');
        document.querySelectorAll('.fo-response-document-remove').forEach(button => button.addEventListener('click', () => removeFoResponseDocument(button)));
        document.getElementById('response-documents').hidden = responseDocuments.length === 0;
      }).catch(() => {});
    }

    async function submitFoResponse(event) {
      event.preventDefault();
      const form = document.getElementById('response-form');
      const responseText = document.getElementById('response-text').value.trim();
      const responseAt = document.getElementById('response-at').value;
      const feedback = document.getElementById('response-feedback');
      if (!responseAt || !responseText) { feedback.textContent = 'Response date/time and action taken are required.'; return; }
      const formData = new FormData(form);
      formData.append('id', activeSubmissionId);
      if (!formData.has('response_at')) formData.append('response_at', responseAt);
      if (responseEditMode) formData.append('edit', '1');
      const button = document.getElementById('submit-response');
      button.disabled = true;
      button.textContent = 'Submitting...';
      feedback.textContent = '';
      try {
        const result = await readJsonResponse(await fetch('../shared/auth/fo_tasks_api.php?action=response', { method: 'POST', headers: { 'Accept': 'application/json' }, body: formData }));
        if (!result.success) throw new Error(result.error || 'Unable to submit response.');
        feedback.style.color = '#087453';
        feedback.textContent = 'Response submitted successfully.';
        window.setTimeout(() => window.location.reload(), 500);
      } catch (error) {
        feedback.style.color = '#b42318';
        feedback.textContent = error.message || 'Unable to submit response.';
        button.disabled = false;
        button.textContent = 'Submit Response';
      }
    }

    document.getElementById('response-form').addEventListener('submit', submitFoResponse);
    document.getElementById('edit-response').addEventListener('click', () => {
      responseEditMode = true;
      document.getElementById('response-text').disabled = false;
      document.getElementById('response-at').disabled = true;
      document.getElementById('response-dropzone').style.display = '';
      document.getElementById('submit-response').style.display = '';
      document.getElementById('submit-response').textContent = 'Save Edited Response';
      document.getElementById('edit-response').hidden = true;
      document.getElementById('response-documents').classList.add('editing');
      document.getElementById('response-feedback').textContent = '';
    });

    async function removeFoResponseDocument(button) {
      const documentId = Number(button.dataset.documentId);
      if (!documentId || !confirm(`Remove ${button.dataset.documentName || 'this document'}?`)) return;
      button.disabled = true;
      try {
        const result = await readJsonResponse(await fetch('../shared/auth/fo_tasks_api.php?action=delete_document', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ id: documentId })
        }));
        if (!result.success) throw new Error(result.error || 'Unable to remove document.');
        button.closest('.fo-response-document')?.remove();
        const documents = document.getElementById('response-document-list');
        if (!documents.children.length) document.getElementById('response-documents').hidden = true;
      } catch (error) {
        button.disabled = false;
        document.getElementById('response-feedback').textContent = error.message || 'Unable to remove document.';
      }
    }
    document.getElementById('response-text').addEventListener('input', event => { document.getElementById('response-counter').textContent = `${event.target.value.length} / 1000`; });
    const responseDropzone = document.getElementById('response-dropzone');
    const responseFiles = document.getElementById('response-files');
    document.getElementById('response-browse').addEventListener('click', event => { event.stopPropagation(); responseFiles.click(); });
    responseDropzone.addEventListener('click', () => responseFiles.click());
    responseDropzone.addEventListener('dragover', event => { event.preventDefault(); responseDropzone.classList.add('dragover'); });
    responseDropzone.addEventListener('dragleave', () => responseDropzone.classList.remove('dragover'));
    responseDropzone.addEventListener('drop', event => { event.preventDefault(); responseDropzone.classList.remove('dragover'); responseFiles.files = event.dataTransfer.files; renderResponseFiles(); });
    responseFiles.addEventListener('change', renderResponseFiles);
    function renderResponseFiles() {
      document.getElementById('response-file-list').innerHTML = [...responseFiles.files].map((file, index) => `<div class="attached-file"><span title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</span><small>${(file.size / 1024 / 1024).toFixed(2)} MB</small><button type="button" class="attached-file-remove" data-response-file-index="${index}" aria-label="Remove ${escapeHtml(file.name)}">Remove</button></div>`).join('');
      document.querySelectorAll('[data-response-file-index]').forEach(button => button.addEventListener('click', () => {
        const files = [...responseFiles.files];
        files.splice(Number(button.dataset.responseFileIndex), 1);
        const dataTransfer = new DataTransfer();
        files.forEach(file => dataTransfer.items.add(file));
        responseFiles.files = dataTransfer.files;
        renderResponseFiles();
      }));
    }

    document.querySelectorAll('.view-submission').forEach(button => {
      button.addEventListener('click', () => {
        const data = JSON.parse(button.dataset.submission);
        viewDetail(data.id, data.ref, data.title, data.details, data.status, data.priority, data.category, data.submitted, data.updated, data.due);
      });
    });

    let selectedSubmissionFiles = [];

    function renderSubmissionFiles() {
      document.getElementById('submission-files').innerHTML = selectedSubmissionFiles.map(file => `<div class="attached-file"><span>${escapeHtml(file.name)}</span><small>${(file.size / 1024 / 1024).toFixed(2)} MB</small></div>`).join('');
    }

    function escapeHtml(value) {
      return value.replace(/[&<>'\"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '\"': '&quot;' })[character]);
    }

    function linkifyDetailText(value) {
      return escapeHtml(String(value)).replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>');
    }

    function formatDetailDate(value) {
      if (!value) return '-';
      const date = new Date(String(value).replace(' ', 'T'));
      return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function formatDetailFileSize(bytes) {
      const size = Number(bytes || 0);
      return size ? `${(size / 1024 / 1024).toFixed(2)} MB` : 'Size unavailable';
    }

    async function readSubmissionResponse(response) {
      const body = await response.text();
      try {
        return JSON.parse(body);
      } catch (error) {
        throw new Error(response.status === 401 ? 'Your session has expired. Please log in again.' : 'The server returned an invalid response. Please try again.');
      }
    }

    async function readJsonResponse(response) {
      const body = await response.text();
      let data;
      try {
        data = JSON.parse(body);
      } catch (error) {
        throw new Error(response.status === 401 ? 'Your session has expired. Please log in again.' : 'The server returned an invalid response. Please try again.');
      }
      if (!response.ok) throw new Error(data.error || 'The request could not be completed.');
      return data;
    }

    function showSubmissionFeedback(message) {
      document.getElementById('submission-feedback').textContent = message;
    }

    function submitSubmission(event) {
      event.preventDefault();
      const title = document.getElementById('submission-title').value.trim();
      const details = document.getElementById('submission-details').value.trim();
      const reportingPeriod = document.getElementById('reporting-period').value;
      const municipality = document.getElementById('municipality').value;
      const preparedBy = document.getElementById('prepared-by').value.trim();
      if (!title || !reportingPeriod || !municipality || !preparedBy || !document.getElementById('submission-category').value || !document.getElementById('submission-due-date').value) {
        alert('Please complete all required fields before submitting.');
        return;
      }
      if (!selectedSubmissionFiles.length) {
        alert('Please attach at least one required document before submitting.');
        document.getElementById('submission-dropzone')?.focus();
        return;
      }
      const submitButton = document.getElementById('submit-submission');
      submitButton.disabled = true;
      submitButton.textContent = 'Submitting...';
      const fullDetails = [details, `Reporting period: ${reportingPeriod}`, `Municipality/City: ${municipality}`, `Prepared by: ${preparedBy}`, `Designation: ${document.getElementById('designation').value.trim()}`, `Contact number: ${document.getElementById('contact-number').value.trim()}`].filter(Boolean).join('\n');
      const submissionData = new FormData();
      Object.entries({ title, details: fullDetails, category: document.getElementById('submission-category').value, due_date: document.getElementById('submission-due-date').value, submission: true }).forEach(([key, value]) => submissionData.append(key, value));
      selectedSubmissionFiles.forEach(file => submissionData.append('documents[]', file));
      fetch('../shared/auth/reports_api.php', {
        method: 'POST',
        cache: 'no-store',
        headers: { 'Accept': 'application/json' },
        body: submissionData
      })
      .then(readSubmissionResponse)
      .then(d => {
        if (d.success) {
          closeModal('create-modal');
          location.reload();
        } else {
          throw new Error(d.error || 'Failed to submit the submission.');
        }
      })
      .catch(e => showSubmissionFeedback(e.message || 'Unable to submit the submission.'))
      .finally(() => { submitButton.disabled = false; submitButton.textContent = 'Submit Submission'; });
    }

    const submissionDropzone = document.getElementById('submission-dropzone');
    const submissionFileInput = document.getElementById('submission-file-input');
    submissionDropzone?.addEventListener('click', () => submissionFileInput.click());
    submissionDropzone?.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') submissionFileInput.click(); });
    submissionDropzone?.addEventListener('dragover', event => { event.preventDefault(); submissionDropzone.classList.add('dragover'); });
    submissionDropzone?.addEventListener('dragleave', () => submissionDropzone.classList.remove('dragover'));
    submissionDropzone?.addEventListener('drop', event => { event.preventDefault(); submissionDropzone.classList.remove('dragover'); selectedSubmissionFiles = [...event.dataTransfer.files]; renderSubmissionFiles(); });
    submissionFileInput?.addEventListener('change', event => { selectedSubmissionFiles = [...event.target.files]; renderSubmissionFiles(); });
    document.getElementById('submission-title')?.addEventListener('input', event => { document.getElementById('submission-title-count').textContent = event.target.value.length; });
    document.getElementById('submission-details')?.addEventListener('input', event => { document.getElementById('submission-details-count').textContent = event.target.value.length; });
    
    function applyFilters() {
      const term = (searchInput?.value || '').trim().toLowerCase();
      const status = (statusFilter?.value || '').toUpperCase();
      const category = (categoryFilter?.value || '').toLowerCase();
      const dateRange = parseInt(dateFilter?.value || '0');
      const now = new Date();
      
      const tableBody = document.getElementById('table-body');
      if (!tableBody) return;
      const tableWrap = tableBody.closest('.table-wrap');
      const paginationWrap = tableWrap?.nextElementSibling;
      const filteredEmptyState = document.getElementById('filtered-empty-state');
      let visibleRows = 0;
      
      tableBody.querySelectorAll('tr').forEach(row => {
        const search = (row.dataset.search || '').toLowerCase();
        const rowStatus = row.dataset.status || '';
        const rowCategory = (row.dataset.category || '').toLowerCase();
        const rowDate = new Date(row.dataset.date || '');
        const daysDiff = (now - rowDate) / (1000 * 60 * 60 * 24);
        
        let show = !term || search.includes(term);
        if (status && status !== 'ALL') show = show && rowStatus === status;
        if (category) show = show && rowCategory.includes(category);
        if (dateRange) show = show && daysDiff <= dateRange;
        
        row.style.display = show ? '' : 'none';
        if (show) visibleRows++;
      });

      const noPendingSubmissions = status === 'PENDING' && visibleRows === 0;
      tableWrap?.toggleAttribute('hidden', noPendingSubmissions);
      paginationWrap?.toggleAttribute('hidden', noPendingSubmissions);
      filteredEmptyState?.toggleAttribute('hidden', !noPendingSubmissions);
    }
    
    function clearFilters() {
      searchInput.value = '';
      statusFilter.value = '';
      categoryFilter.value = '';
      dateFilter.value = '';
      applyFilters();
    }
    
    function goToPage(page) {
      window.location.href = '?page=' + page + '&per_page=' + (document.getElementById('entries-select')?.value || 10);
    }
    
    function changePerPage(value) {
      window.location.href = '?page=1&per_page=' + value;
    }
    
    function logout() {
      fetch('../shared/auth/logout.php', { method: 'POST' })
        .then(() => window.location.href = '../../index.html')
        .catch(() => window.location.href = '../../index.html');
    }
    
    searchInput?.addEventListener('input', applyFilters);

    document.querySelectorAll('.stat-card-link').forEach(card => {
      const navigate = () => {
        if (card.dataset.href) {
          window.location.href = card.dataset.href;
          return;
        }
        statusFilter.value = card.dataset.status || '';
        applyFilters();
      };
      card.addEventListener('click', navigate);
      card.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          navigate();
        }
      });
    });

    const initialStatus = new URLSearchParams(window.location.search).get('status');
    if (initialStatus && ['PENDING', 'SUBMITTED', 'OVERDUE'].includes(initialStatus.toUpperCase())) {
      statusFilter.value = initialStatus.toUpperCase();
      applyFilters();
    }

    const initialView = Number(new URLSearchParams(window.location.search).get('view') || 0);
    if (initialView > 0) {
      fetch(`../shared/auth/fo_tasks_api.php?action=detail&id=${initialView}`).then(readJsonResponse).then(data => {
        if (!data.success || !data.task) return;
        const task = data.task;
        viewDetail(task.id, task.reference_number || `DILG-${task.id}`, task.title, task.details, task.status, task.priority, task.category, task.submitted_at || task.created_at, task.updated_at);
      }).catch(() => {});
    }
    
    // Close modals when clicking outside
    document.getElementById('create-modal').addEventListener('click', (e) => {
      if (e.target.id === 'create-modal') closeModal('create-modal');
    });
    document.getElementById('detail-modal').addEventListener('click', (e) => {
      if (e.target.id === 'detail-modal') closeModal('detail-modal');
    });

    setInterval(() => {
      if (!document.querySelector('.modal.active')) window.location.reload();
    }, 30000);
  </script>
</body>
</html>

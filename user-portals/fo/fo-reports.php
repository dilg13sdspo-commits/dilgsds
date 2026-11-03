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
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports &amp; Analytics - DILG FO Dashboard</title>
  <style>
    :root { --navy: #0b3d91; --navy-light: #1456a0; --green: #0f3e2f; --bg: #eef3f1; --text: #192a2e; --muted: #697780; --line: rgba(16,49,43,.1); }
    * { box-sizing: border-box; }
    body { margin: 0; background: linear-gradient(180deg,#edf4f1,#f4f7f7); color: var(--text); font-family: "Segoe UI",Tahoma,sans-serif; }
    a { color: inherit; text-decoration: none; }
    .layout { display: flex; min-height: 100vh; }
    .sidebar { width: 270px; flex: 0 0 270px; padding: 18px 14px; color: #fff; background: linear-gradient(180deg,var(--navy),var(--navy-light)); display: flex; flex-direction: column; }
    .brand { display: flex; gap: 12px; align-items: center; padding: 8px 8px 18px; border-bottom: 1px solid rgba(255,255,255,.14); }
    .brand-logo,.profile-avatar,.footer-seal { display: grid; place-items: center; border-radius: 50%; font-weight: 800; }
    .brand-logo { width: 48px; height: 48px; background: rgba(255,255,255,.1); }
    .brand-copy strong { display: block; letter-spacing: .06em; }
    .brand-copy span { display: block; margin-top: 4px; font-size: .68rem; opacity: .8; line-height: 1.35; }
    .profile-box { display: flex; align-items: center; gap: 8px; margin: 14px 6px 8px; padding: 8px 7px; border: 1px solid rgba(255,255,255,.1); border-radius: 12px; background: rgba(255,255,255,.07); }
    .profile-avatar { width: 36px; height: 36px; flex: 0 0 36px; background: rgba(255,255,255,.15); }
    .profile-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }
    .profile-role { font-size: .68rem; line-height: 1.1; }
    .status-dot { display: inline-block; width: 8px; height: 8px; margin-right: 5px; border-radius: 50%; background: #34d399; }
    .nav { display: flex; flex-direction: column; gap: 4px; margin-top: 8px; }
    .nav-item { display: flex; align-items: center; gap: 10px; width: 100%; padding: 11px 10px; border: 0; border-radius: 10px; color: rgba(255,255,255,.9); background: transparent; text-align: left; font: inherit; font-weight: 600; cursor: pointer; }
    .nav-item:hover,.nav-item.active { background: rgba(255,255,255,.15); }
    .nav-item svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .nav-badge { margin-left: auto; min-width: 20px; padding: 2px 6px; border-radius: 999px; background: #f4b740; text-align: center; font-size: .68rem; }
    .sidebar-footer { margin-top: auto; padding-top: 12px; border-top: 1px solid rgba(255,255,255,.14); font-size: .78rem; }
    .footer-brand { display: flex; align-items: center; gap: 10px; }
    .footer-seal { width: 36px; height: 36px; background: rgba(255,255,255,.1); }
    .main { flex: 1; min-width: 0; }
    .topbar { display: flex; align-items: center; padding: 14px 20px; border-bottom: 1px solid var(--line); background: rgba(255,255,255,.94); }
    .menu-btn { width: 42px; height: 42px; margin-right: 12px; border: 1px solid var(--line); border-radius: 12px; background: #fff; color: var(--green); font-size: 1.15rem; cursor: pointer; }
    .page-title h1 { margin: 0; color: var(--green); font-size: 1.8rem; }
    .page-title p { margin: 4px 0 0; color: var(--muted); font-size: .86rem; }
    .content { padding: 22px 20px 32px; }
    .report-card { overflow: hidden; border: 1px solid #dfe6ee; border-radius: 16px; background: #fff; box-shadow: 0 16px 36px rgba(8,24,55,.08); }
    .report-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding: 22px; border-bottom: 1px solid #e2e8ef; }
    .report-head h2 { margin: 0; color: #0f2a6b; font-size: 1.4rem; text-transform: uppercase; }
    .report-head p { margin: 6px 0 0; color: var(--muted); font-size: .85rem; }
    .report-link { padding: 9px 13px; border: 1px solid #d7e0ea; border-radius: 8px; color: #0f2a6b; background: #fff; font-size: .78rem; font-weight: 700; white-space: nowrap; }
    .viewer { position: relative; height: min(78vh, 820px); min-height: 560px; background: #f7faff; }
    .viewer iframe { width: 100%; height: 100%; border: 0; display: block; }
    .viewer-note { padding: 12px 22px; border-top: 1px solid #e2e8ef; color: var(--muted); font-size: .76rem; }
    .profile-box > div:last-child { min-width: 0; }
    .profile-box > div:last-child strong, .profile-box > div:last-child small { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .profile-box > div:last-child strong { margin-top: 2px; font-size: .74rem; }
    .profile-box > div:last-child small { margin-top: 1px; color: rgba(255,255,255,.8); font-size: .6rem; }
    @media (max-width: 760px) { .layout { flex-direction: column; } .sidebar { width: 100%; flex-basis: auto; } .nav { display: grid; grid-template-columns: repeat(2,1fr); } .sidebar-footer { display: none; } .report-head { flex-direction: column; padding: 16px; } .content { padding: 14px; } .viewer { height: 70vh; min-height: 460px; } }
  </style>
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <div class="brand"><div class="brand-logo">D</div><div class="brand-copy"><strong>DILG PORTAL</strong><span>Department of the Interior and Local Governance</span></div></div>
      <div class="profile-box"><div class="profile-avatar"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="FO User profile photo"><?php else: ?><?= htmlspecialchars(strtoupper(substr($userName, 0, 2))) ?><?php endif; ?></div><div><div class="profile-role"><span class="status-dot"></span>Online</div><strong><?= htmlspecialchars($userName) ?></strong><small><?= htmlspecialchars($officeName) ?></small></div></div>
      <nav class="nav" aria-label="FO User navigation">
        <a class="nav-item" href="dashboard.php"><span>Dashboard</span></a>
        <a class="nav-item" href="inquiries.php"><span>?</span>My Submissions</a>
        <a class="nav-item active" href="reports.php"><span>?</span>Reports</a>
        <a class="nav-item" href="../../news.html"><span>?</span>Announcements</a>
        <a class="nav-item" href="calendar.php"><span>?</span>Calendar &amp; Activities</a>
        <a class="nav-item" href="notifications.php"><span>?</span>Notifications <span class="nav-badge"><?= htmlspecialchars((string)$unreadNotifications) ?></span></a>
        <a class="nav-item" href="settings.php"><span>?</span>Profile Settings</a>
      </nav>
      <div class="sidebar-footer"><div class="footer-brand"><div class="footer-seal">D</div><span>Department of the Interior and Local Government</span></div></div>
    </aside>
    <main class="main">
      <header class="topbar"><button class="menu-btn" type="button" aria-label="Dashboard menu">?</button><div class="page-title"><h1>Reports &amp; Analytics</h1><p>Review DILG publications and reports from your FO dashboard.</p></div></header>
      <div class="content">
        <section class="report-card" aria-labelledby="annual-report-title">
          <div class="report-head"><div><h2 id="annual-report-title">2024 Annual Report</h2><p>DILG Surigao del Sur publication</p></div><a class="report-link" href="https://online.fliphtml5.com/uyttw/eyon/#p=5" target="_blank" rel="noopener">Open in New Tab</a></div>
          <div class="viewer"><iframe title="2024 Annual Report publication" src="https://online.fliphtml5.com/uyttw/eyon/#p=5"></iframe></div>
          <div class="viewer-note">The publication opens at page 5. Use the viewer controls to browse the complete annual report.</div>
        </section>
      </div>
    </main>
  </div>
</body>
</html>

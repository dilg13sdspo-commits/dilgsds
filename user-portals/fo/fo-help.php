<?php
require_once __DIR__ . '/../shared/auth/middleware.php';
require_role('FO USER');

// Redirect to the new FAQs & Guidelines page
header('Location: fo-faq-guidelines.php', true, 301);
exit;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FO Help Center</title>
  <style>
    :root { --green-900:#0f3e2f; --green-800:#1a4f3d; --bg:#eef3f1; --panel:#fff; --text:#192a2e; --muted:#697780; --line:rgba(16,49,43,0.08); --shadow:0 14px 28px rgba(16,39,33,0.08); --yellow:#f4b740; --blue:#2d80f2; }
    * { box-sizing:border-box; }
    body { margin:0; font-family:"Segoe UI", Arial, sans-serif; background:var(--bg); color:var(--text); }
    a { text-decoration:none; color:inherit; }
    button { font:inherit; }
    .layout { display:flex; min-height:100vh; }
    .sidebar { width:270px; background:linear-gradient(180deg,#0b3d91,#1456a0); color:white; padding:18px 14px; }
    .brand { display:flex; align-items:center; gap:12px; padding:8px 8px 18px; border-bottom:1px solid rgba(255,255,255,0.1); }
    .brand-logo { width:48px; height:48px; border-radius:50%; background:rgba(255,255,255,0.08); display:grid; place-items:center; font-weight:800; }
    .brand-copy strong { display:block; letter-spacing:0.06em; }
    .brand-copy span { display:block; font-size:0.68rem; opacity:0.8; margin-top:4px; }
    .nav { display:flex; flex-direction:column; gap:5px; margin-top:18px; }
    .nav-item { width:100%; border:none; background:transparent; color:rgba(255,255,255,0.9); text-align:left; padding:11px 10px; border-radius:10px; display:flex; align-items:center; justify-content:space-between; cursor:pointer; }
    .nav-item.active, .nav-item:hover { background:rgba(255,255,255,0.12); }
    .nav-main { display:inline-flex; align-items:center; gap:10px; }
    .nav-label { font-weight:600; }
    .nav-badge { min-width:22px; height:22px; border-radius:999px; background:var(--yellow); color:#fff; display:grid; place-items:center; font-weight:800; font-size:0.72rem; padding:0 6px; }
    .main { flex:1; padding:26px; }
    .header { background:var(--panel); border:1px solid var(--line); border-radius:16px; box-shadow:var(--shadow); padding:20px 24px; display:flex; justify-content:space-between; align-items:center; }
    .header h1 { margin:0; font-size:2rem; }
    .back-link { display:inline-flex; align-items:center; padding:10px 14px; border-radius:10px; border:1px solid var(--line); background:#f7faf9; color:var(--text); }
    .panel { background:var(--panel); border:1px solid var(--line); border-radius:16px; box-shadow:var(--shadow); margin-top:22px; padding:22px; }
    .faq-list { display:flex; flex-direction:column; gap:16px; }
    .faq-item { border:1px solid var(--line); border-radius:12px; padding:18px; background:#fafdfc; }
    .faq-item h3 { margin:0 0 8px; font-size:1.04rem; }
    .faq-item p { margin:0; color:var(--muted); line-height:1.6; }
    @media (max-width:860px) { .layout { flex-direction:column; } .sidebar { width:100%; } .main { padding:18px; } .header { flex-direction:column; align-items:flex-start; } }
  </style>
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-logo">D</div>
        <div class="brand-copy"><strong>DILG PORTAL</strong><span>Department of the Interior and Local Governance</span></div>
      </div>
      <nav class="nav">
        <button class="nav-item" type="button" onclick="window.location.href='dashboard.php'"><span class="nav-main"><span></span><span class="nav-label">Dashboard</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='dashboard.php#inquiries-table'"><span class="nav-main"><span></span><span class="nav-label">My Submissionst</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='../../news.html'"><span class="nav-main"><span></span><span class="nav-label">Announcements</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='dashboard.php#calendar-panel'"><span class="nav-main"><span></span><span class="nav-label">Calendar &amp; Activities</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='notifications.php'"><span class="nav-main"><span></span><span class="nav-label">Notifications</span></span><span class="nav-badge"><?= htmlspecialchars((string)$unreadNotifications) ?></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='settings.php'"><span class="nav-main"><span></span><span class="nav-label">Profile Settings</span></span></button>
      </nav>
    </aside>

    <main class="main">
      <div class="header">
        <h1>Help Center</h1>
        <a class="back-link" href="dashboard.php">Back to Dashboard</a>
      </div>

      <div class="panel" id="faqs">
        <div class="faq-list">
          <div class="faq-item">
            <h3>How do I create a new inquiry?</h3>
            <p>Use the quick action button labeled Create Inquiry from the dashboard or scroll to the My Recent Inquiries section and submit a new request.</p>
          </div>
          <div class="faq-item">
            <h3>Where can I see my inquiry status?</h3>
            <p>Open the My Inquiries section in the dashboard, which shows the latest status, reference number, and last update of each request.</p>
          </div>
          <div class="faq-item">
            <h3>How do I upload supporting documents?</h3>
            <p>Use the Upload Document quick action from the dashboard to send supporting files directly to the system for review.</p>
          </div>
          <div class="faq-item">
            <h3>Where do I find guidance and forms?</h3>
            <p>Use the Programs & Services and Downloads links from the side menu to access forms, announcements, and agency guidance.</p>
          </div>
          <div class="faq-item">
            <h3>Who can I contact for assistance?</h3>
            <p>Use the Request Assistance quick action or the Office Transactions option for support and official office coordination.</p>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>

<?php
require_once __DIR__ . '/../shared/auth/middleware.php';
require_once __DIR__ . '/../shared/auth/po_icons.php';
require_role('PO USER');

$pdo = db_connect();
$profilePhoto = get_user_profile_photo($pdo, (int)($_SESSION['user_id'] ?? 0));
$userName = $_SESSION['username'] ?? 'Provincial User';
$userId = (int)($_SESSION['user_id'] ?? 0);

$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
$stmt->execute([':uid' => $userId]);
$notificationCount = (int)$stmt->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Help Center - DILG PO Dashboard</title>
  <style>
    :root {
      --po-sidebar: #5c2d17;
      --po-sidebar-deep: #3d1f11;
      --po-bg: #f5f7fb;
      --po-surface: #ffffff;
      --po-heading: #1f2d3d;
      --po-text: #243447;
      --po-muted: #69798a;
      --po-border: #e6edf4;
      --po-accent: #2d7ff9;
      --po-accent-soft: #edf4ff;
      --po-warning: #f5a623;
      --po-green: #2ab36d;
      --po-red: #e86161;
      --po-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
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

    .po-shell { display: flex; min-height: 100vh; }
    .po-sidebar {
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
      padding: 8px 8px 18px;
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
    }
    .brand-copy { line-height: 1.1; }
    .brand-copy strong { display: block; font-size: 1rem; letter-spacing: .08em; }
    .brand-copy span { display: block; font-size: .72rem; opacity: .8; margin-top: 4px; }
    .nav { display: flex; flex-direction: column; gap: 6px; margin-top: 16px; padding: 0 4px; }
    .nav-item {
      width: 100%;
      border: none;
      background: transparent;
      color: rgba(255,255,255,.9);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      text-align: left;
      border-radius: 10px;
      padding: 12px 10px;
      cursor: pointer;
      transition: all .2s ease;
      font-weight: 600;
    }
    .nav-item:hover, .nav-item.active {
      background: rgba(255,255,255,.12);
      box-shadow: inset 0 0 0 1px rgba(255,255,255,.06);
    }
    .nav-item.active {
      background: rgba(255,255,255,.18);
      box-shadow: inset 3px 0 0 rgba(255,255,255,.8);
    }
    .nav-main { display: inline-flex; align-items: center; gap: 10px; min-width: 0; }
    .nav-icon { width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; }
    .nav-label { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nav-badge {
      min-width: 22px;
      height: 22px;
      border-radius: 999px;
      background: var(--po-warning);
      color: #fff;
      display: grid;
      place-items: center;
      font-size: .72rem;
      font-weight: 800;
      padding: 0 6px;
    }
    .sidebar-footer { margin-top: auto; padding-top: 14px; border-top: 1px solid rgba(255,255,255,.12); }
    .footer-pill {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 8px 0;
      font-weight: 700;
      opacity: .95;
    }
    .seal {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      background: rgba(255,255,255,.12);
      display: grid;
      place-items: center;
      font-size: .75rem;
      font-weight: 800;
    }

    .po-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    .topbar {
      background: rgba(255,255,255,.86);
      border-bottom: 1px solid var(--po-border);
      backdrop-filter: blur(6px);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 16px 22px;
    }
    .topbar-title { display: flex; align-items: center; gap: 12px; min-width: 0; }
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
    .title-copy strong {
      display: block;
      font-size: .9rem;
      color: var(--po-heading);
    }
    .title-copy span {
      display: block;
      font-size: .72rem;
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
      font-size: .65rem;
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
      background: linear-gradient(135deg, #f0b33f, #d17025);
      color: #fff;
      font-weight: 800;
      font-size: .82rem;
    }
    .profile-meta { display: flex; flex-direction: column; line-height: 1.2; text-align: left; }
    .profile-meta strong { font-size: .82rem; }
    .profile-meta span { font-size: .68rem; color: var(--po-muted); }

    .page-inner { padding: 22px; }
    .page-header {
      margin-bottom: 18px;
    }
    .header-card {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 18px 20px;
      background: rgba(255,255,255,.8);
      border: 1px solid var(--po-border);
      border-radius: 16px;
      box-shadow: 0 8px 18px rgba(31,42,61,.04);
    }
    .page-icon {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      background: #edf4ff;
      color: var(--po-accent);
      display: grid;
      place-items: center;
      font-size: 1.7rem;
    }
    .page-header h1 {
      margin: 0;
      font-size: clamp(1.8rem,2.5vw,2.5rem);
      color: var(--po-heading);
    }
    .breadcrumb {
      margin-top: 6px;
      font-size: .82rem;
      color: var(--po-muted);
    }
    .page-description {
      margin: 12px 0 0;
      color: var(--po-muted);
      font-size: .95rem;
    }

    .help-layout {
      display: grid;
      grid-template-columns: minmax(0, 1.7fr) 320px;
      gap: 20px;
      align-items: start;
    }
    .main-panel {
      background: #fff;
      border: 1px solid var(--po-border);
      border-radius: 16px;
      box-shadow: var(--po-shadow);
      padding: 16px;
    }
    .search-row {
      display: grid;
      grid-template-columns: 1fr 120px;
      gap: 12px;
      margin-bottom: 18px;
    }
    .search-box {
      display: flex;
      align-items: center;
      gap: 8px;
      border: 1px solid var(--po-border);
      border-radius: 12px;
      background: #f8fafc;
      padding: 12px 14px;
      color: var(--po-muted);
    }
    .search-box input {
      border: 0;
      background: transparent;
      outline: none;
      flex: 1;
      color: var(--po-text);
    }
    .primary-btn {
      border: 1px solid var(--po-accent);
      background: var(--po-accent);
      color: #fff;
      border-radius: 10px;
      padding: 10px 12px;
      font-weight: 700;
      cursor: pointer;
    }
    .primary-btn.secondary {
      background: #fff;
      color: var(--po-accent);
    }
    .topic-grid {
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 12px;
      margin-bottom: 18px;
    }
    .topic-card {
      border: 1px solid var(--po-border);
      border-radius: 12px;
      background: #f8fafc;
      padding: 12px 10px;
      text-align: center;
      cursor: pointer;
      transition: all .2s ease;
    }
    .topic-card.active {
      background: var(--po-accent-soft);
      border-color: rgba(45,127,249,.14);
      box-shadow: inset 0 0 0 1px rgba(45,127,249,.06);
    }
    .topic-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: grid;
      place-items: center;
      margin: 0 auto 8px;
      background: #fff;
      border: 1px solid var(--po-border);
      font-size: 1.2rem;
    }
    .topic-card.active .topic-icon {
      background: var(--po-accent-soft);
    }
    .topic-card strong { display: block; font-size: .96rem; color: var(--po-heading); }
    .topic-card small { color: var(--po-muted); }

    .faq-box {
      border: 1px solid var(--po-border);
      border-radius: 14px;
      background: #fff;
      overflow: hidden;
    }
    .faq-header {
      margin: 0 0 14px;
      font-size: 1.1rem;
      color: var(--po-heading);
    }
    .faq-item {
      border-top: 1px solid var(--po-border);
      background: #fff;
    }
    .faq-item:first-child { border-top: 0; }
    .faq-toggle {
      width: 100%;
      background: transparent;
      border: 0;
      text-align: left;
      padding: 16px 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      cursor: pointer;
      color: var(--po-heading);
      font-weight: 700;
    }
    .faq-answer {
      display: none;
      padding: 0 18px 18px;
      color: var(--po-muted);
      line-height: 1.7;
    }
    .faq-item.open .faq-answer { display: block; }
    .faq-item.open .faq-icon { transform: rotate(180deg); }
    .faq-icon {
      width: 20px;
      height: 20px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      color: var(--po-accent);
      transition: transform .2s ease;
      font-weight: 700;
    }
    .faq-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: var(--po-accent);
      font-weight: 700;
      margin-top: 12px;
    }
    .sidebar-panel {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }
    .support-card {
      background: #fff;
      border: 1px solid var(--po-border);
      border-radius: 16px;
      box-shadow: var(--po-shadow);
      padding: 18px 16px;
    }
    .support-card h3 { margin: 0 0 12px; color: var(--po-heading); }
    .support-card p { margin: 0; color: var(--po-muted); line-height: 1.6; }
    .phone, .email, .office {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      margin-top: 12px;
      color: var(--po-heading);
      font-weight: 600;
      line-height: 1.5;
    }
    .support-card .primary-btn {
      width: 100%;
      margin-top: 16px;
    }
    .popular-card {
      background: #fff;
      border: 1px solid var(--po-border);
      border-radius: 16px;
      box-shadow: var(--po-shadow);
      padding: 18px 16px;
    }
    .popular-card h3 { margin: 0 0 12px; color: var(--po-heading); }
    .article-item {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      padding: 10px 0;
      border-top: 1px solid var(--po-border);
    }
    .article-item:first-child { border-top: 0; }
    .article-text strong { display: block; margin-bottom: 4px; color: var(--po-heading); }
    .article-text span { color: var(--po-muted); font-size: .8rem; }
    .article-link { color: var(--po-accent); font-weight: 700; }
    .view-all { display: inline-block; margin-top: 10px; color: var(--po-accent); font-weight: 700; }

    .empty-state {
      border: 1px dashed var(--po-border);
      border-radius: 14px;
      background: #fafcff;
      color: var(--po-muted);
      text-align: center;
      padding: 32px 20px;
      margin-top: 14px;
    }
    .article-detail {
      display: none;
      background: #fff;
      border: 1px solid var(--po-border);
      border-radius: 16px;
      box-shadow: var(--po-shadow);
      padding: 20px;
      margin-top: 18px;
    }
    .article-detail.open { display: block; }
    .article-detail h2 { margin: 0 0 10px; color: var(--po-heading); }
    .article-meta { color: var(--po-muted); font-size: .8rem; margin-bottom: 14px; }
    .article-content { color: var(--po-text); line-height: 1.8; }
    .article-actions { margin-top: 18px; display: flex; gap: 10px; align-items: center; }
    .ghost-btn {
      border: 1px solid var(--po-border);
      background: #fff;
      color: var(--po-text);
      border-radius: 10px;
      padding: 9px 12px;
      cursor: pointer;
    }
    .footer {
      text-align: center;
      color: var(--po-muted);
      font-size: .82rem;
      padding: 12px 0 0;
      margin-top: 10px;
    }

    @media (max-width: 980px) {
      .po-shell { flex-direction: column; }
      .po-sidebar { width: 100%; }
      .nav { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); }
      .help-layout { grid-template-columns: 1fr; }
      .topic-grid { grid-template-columns: repeat(3, minmax(0,1fr)); }
      .search { min-width: 160px; }
    }
    @media (max-width: 640px) {
      .page-inner { padding: 16px; }
      .topbar { padding: 12px 16px; }
      .header-card { flex-direction: column; align-items: flex-start; }
      .search-row { grid-template-columns: 1fr; }
      .topic-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
      .article-item { flex-direction: column; }
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

      <div style="display:flex;align-items:center;gap:10px;margin:14px 4px 0;padding:10px 8px;border:1px solid rgba(255,255,255,.12);border-radius:12px;background:rgba(255,255,255,.08);"><div style="width:36px;height:36px;display:grid;place-items:center;flex:0 0 36px;overflow:hidden;border:1px solid rgba(255,255,255,.28);border-radius:50%;background:rgba(255,255,255,.14);font-size:.82rem;font-weight:800;"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="PO User profile photo" style="width:100%;height:100%;object-fit:cover;display:block;"><?php else: ?><?= htmlspecialchars(strtoupper(substr($userName, 0, 2))) ?><?php endif; ?></div><div style="min-width:0;"><span style="display:flex;align-items:center;gap:6px;font-size:.72rem;color:rgba(255,255,255,.88);"><span style="width:7px;height:7px;border-radius:50%;background:#34d399;"></span>Online</span><span style="display:block;margin-top:3px;color:#fff;font-size:.78rem;font-weight:700;"><?= htmlspecialchars($userName) ?></span></div></div>

      <nav class="nav" aria-label="Primary navigation">
        <button class="nav-item" type="button" onclick="window.location.href='dashboard.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('dashboard') ?></span><span class="nav-label">Dashboard</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='inquiries.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('submissions') ?></span><span class="nav-label">My Submissions</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='office.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('office') ?></span><span class="nav-label">My Office</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='calendar.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('calendar') ?></span><span class="nav-label">Calendar</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='reports.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('reports') ?></span><span class="nav-label">Reports</span></span></button>
        <button class="nav-item" type="button" onclick="window.location.href='notifications.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('notifications') ?></span><span class="nav-label">Notifications</span></span><span class="nav-badge" id="sidebarBadge"><?= htmlspecialchars((string)$notificationCount) ?></span></button>
        <button class="nav-item active" type="button" onclick="window.location.href='help.php'"><span class="nav-main"><span class="nav-icon">?</span><span class="nav-label">Help Center</span></span></button>
        <button class="nav-item" id="logout-btn" type="button"><span class="nav-main"><span class="nav-icon"></span><span class="nav-label">Log Out</span></span></button>
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
            <span>Province of Surigao del Sur  Provincial Office Dashboard</span>
          </div>
        </div>

        <div class="topbar-tools">
          <label class="search" aria-label="Search dashboard">
            <span><?= po_icon('search') ?></span>
            <input id="globalSearch" type="search" placeholder="Search dashboard..." />
          </label>

          <div class="notif" aria-label="Notifications" onclick="window.location.href='notifications.php'" style="cursor:pointer;"><?= po_icon('notifications') ?><span class="counter" id="headerBadge"><?= htmlspecialchars((string)$notificationCount) ?></span></div>

          <div class="profile" aria-expanded="false">
            <span class="avatar"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="PO User profile photo" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;"><?php else: ?><?= htmlspecialchars(strtoupper(substr($userName, 0, 2))) ?><?php endif; ?></span>
            <span class="profile-meta">
              <strong><?= htmlspecialchars($userName) ?></strong>
              <span>Provincial Office</span>
            </span>
          </div>
        </div>
      </header>

      <main class="page-inner">
        <div class="page-header">
          <div class="header-card">
            <div class="page-icon">?</div>
            <div>
              <h1>Help Center</h1>
              <div class="breadcrumb">Dashboard &nbsp;&nbsp;&nbsp;&nbsp; Help Center</div>
            </div>
          </div>
          <p class="page-description">Find answers to common questions and learn how to use the Provincial Office Dashboard.</p>
        </div>

        <div class="help-layout">
          <section class="main-panel">
            <div class="search-row">
              <label class="search-box" aria-label="Search for help articles">
                <span><?= po_icon('search') ?></span>
                <input id="helpSearch" type="search" placeholder="Search for help articles..." />
              </label>
              <button class="primary-btn" id="searchBtn" type="button">Search</button>
            </div>

            <div id="topicGrid" class="topic-grid"></div>

            <h3 class="faq-header">Frequently Asked Questions</h3>
            <div id="faqList" class="faq-box"></div>

            <div id="articleDetail" class="article-detail"></div>
          </section>

          <aside class="sidebar-panel">
            <div class="support-card">
              <h3>Still Need Help?</h3>
              <p>Can't find the answer you're looking for? Our support team is here to assist you.</p>

              <div class="phone"><?= po_icon('phone') ?> <span>+63 86-251-8888</span></div>
              <div class="email">?? <span>dilg13posds@gmail.com</span></div>
              <div class="office"><?= po_icon('location') ?> <span>Provincial Office of Surigao del Sur, Capitol Hills, Telaje, Tandag,Surigao del Sur, Philippines</span></div>
              <div class="office"><?= po_icon('clock') ?> <span>Mon - Fri, 8:00 AM - 5:00 PM</span></div>

              <button class="primary-btn" id="contactSupportBtn" type="button">Contact Support</button>
            </div>

            <div class="popular-card">
              <h3>Popular Articles</h3>
              <div id="popularList"></div>
              <a href="#" id="viewAllArticles" class="view-all">View all articles ?</a>
            </div>
          </aside>
        </div>

        <div class="footer"> 2026 Department of the Interior and Local Government. All rights reserved.</div>
      </main>
    </div>
  </div>

  <script>
    const state = {
      category: 'all',
      search: '',
      selectedArticleId: null,
      articleList: [],
      faqList: []
    };

    const topicConfig = [
      { key: 'all', label: 'All Topics', icon: 'document', countKey: 'all' },
      { key: 'Inquiries', label: 'Inquiries', icon: 'submissions', countKey: 'Inquiries' },
      { key: 'Documents', label: 'Documents', icon: 'document', countKey: 'Documents' },
      { key: 'Account', label: 'Account', icon: 'user', countKey: 'Account' },
      { key: 'Technical', label: 'Technical', icon: '', countKey: 'Technical' }
    ];

    function badgeCount(categoryKey, counts) {
      return counts[categoryKey] || 0;
    }

    function renderTopics(counts) {
      const container = document.getElementById('topicGrid');
      container.innerHTML = topicConfig.map((topic) => {
        const active = state.category === topic.key ? 'active' : '';
        return `
          <button class="topic-card ${active}" data-category="${topic.key}" type="button">
            <div class="topic-icon">${topic.icon}</div>
            <strong>${topic.label}</strong>
            <small>${badgeCount(topic.countKey, counts)} articles</small>
          </button>
        `;
      }).join('');

      container.querySelectorAll('.topic-card').forEach((button) => {
        button.addEventListener('click', () => {
          state.category = button.dataset.category;
          state.selectedArticleId = null;
          renderTopics(window.helpCounts || { all: 0, Inquiries: 0, Documents: 0, Account: 0, Technical: 0 });
          loadArticles();
        });
      });
    }

    function updateBadges(unreadCount) {
      const badgeEls = [document.getElementById('sidebarBadge'), document.getElementById('headerBadge')];
      badgeEls.forEach((el) => {
        if (!el) return;
        el.textContent = unreadCount;
        el.style.display = unreadCount > 0 ? 'grid' : 'none';
      });
    }

    function loadArticles() {
      const params = new URLSearchParams({ action: 'list', category: state.category, search: state.search });
      fetch('../shared/auth/help_api.php?' + params.toString())
        .then((response) => response.json())
        .then((data) => {
          if (!data.success) {
            renderFaq([]);
            renderPopular([]);
            document.getElementById('articleDetail').classList.remove('open');
            document.getElementById('articleDetail').innerHTML = '<div class="empty-state">Unable to load help articles.</div>';
            return;
          }

          state.articleList = data.articles || [];
          state.faqList = data.faqs || [];
          window.helpCounts = data.counts || { all: 0, Inquiries: 0, Documents: 0, Account: 0, Technical: 0 };
          renderTopics(window.helpCounts);
          renderFaq(state.faqList);
          renderPopular(data.popular || []);

          if (state.articleList.length && state.selectedArticleId) {
            openArticle(state.selectedArticleId);
          } else {
            document.getElementById('articleDetail').classList.remove('open');
            document.getElementById('articleDetail').innerHTML = '';
          }

          if (!state.articleList.length) {
            document.getElementById('faqList').innerHTML = '<div class="empty-state">No help articles found.</div>';
          }
        })
        .catch(() => {
          document.getElementById('faqList').innerHTML = '<div class="empty-state">Unable to load help articles.</div>';
        });
    }

    function renderFaq(items) {
      const faqList = document.getElementById('faqList');
      if (!items.length) {
        faqList.innerHTML = '<div class="empty-state">No help articles found.</div>';
        return;
      }

      faqList.innerHTML = items.map((item, index) => {
        const isOpen = index === 0 ? 'open' : '';
        return `
          <div class="faq-item ${isOpen}" data-id="${item.id}">
            <button class="faq-toggle" type="button">
              <span>${item.title}</span>
              <span class="faq-icon">^</span>
            </button>
            <div class="faq-answer">
              <p>${item.content}</p>
              <a href="#" class="faq-link" data-article-id="${item.id}">View guide ?</a>
            </div>
          </div>
        `;
      }).join('');

      faqList.querySelectorAll('.faq-toggle').forEach((button) => {
        button.addEventListener('click', () => {
          const item = button.closest('.faq-item');
          item.classList.toggle('open');
        });
      });

      faqList.querySelectorAll('[data-article-id]').forEach((link) => {
        link.addEventListener('click', (event) => {
          event.preventDefault();
          openArticle(Number(link.dataset.articleId));
        });
      });
    }

    function renderPopular(items) {
      const popularList = document.getElementById('popularList');
      if (!items.length) {
        popularList.innerHTML = '<div class="empty-state">No popular articles available.</div>';
        return;
      }

      popularList.innerHTML = items.slice(0, 4).map((item) => `
        <div class="article-item">
          <div class="article-text">
            <strong>${item.title}</strong>
            <span>${item.category}</span>
          </div>
          <a href="#" class="article-link" data-article-id="${item.id}">View</a>
        </div>
      `).join('');

      popularList.querySelectorAll('[data-article-id]').forEach((link) => {
        link.addEventListener('click', (event) => {
          event.preventDefault();
          openArticle(Number(link.dataset.articleId));
        });
      });
    }

    function escapeHtml(str) {
      return String(str || '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }[ch]));
    }

    function openArticle(id) {
      fetch('../shared/auth/help_api.php?action=detail&id=' + id)
        .then((response) => response.json())
        .then((data) => {
          if (!data.success || !data.article) {
            document.getElementById('articleDetail').classList.add('open');
            document.getElementById('articleDetail').innerHTML = '<div class="empty-state">Article could not be loaded.</div>';
            return;
          }

          const article = data.article;
          const related = data.related || [];
          state.selectedArticleId = Number(article.id);
          const articleHtml = `
            <div>
              <h2>${escapeHtml(article.title)}</h2>
              <div class="article-meta">${escapeHtml(article.category)}  Updated ${new Date(article.updated_at || article.created_at).toLocaleDateString()}</div>
              <div class="article-content">${escapeHtml(article.content).replace(/\n/g, '<br><br>')}</div>
              <div class="article-actions">
                <button class="ghost-btn" id="backToHelp" type="button">Back to Help Center</button>
                <button class="primary-btn secondary" type="button" onclick="window.location.href='dashboard.php'">Return to Dashboard</button>
              </div>
              ${related.length ? `<div style="margin-top:18px;"><strong>Related articles</strong><ul style="padding-left:20px; margin-top:10px; color:var(--po-muted);">${related.map((item) => `<li><a href="#" data-article-id="${item.id}" style="color:var(--po-accent);">${escapeHtml(item.title)}</a></li>`).join('')}</ul></div>` : ''}
            </div>
          `;

          const detail = document.getElementById('articleDetail');
          detail.classList.add('open');
          detail.innerHTML = articleHtml;

          document.getElementById('backToHelp')?.addEventListener('click', () => {
            detail.classList.remove('open');
            detail.innerHTML = '';
          });

          detail.querySelectorAll('[data-article-id]').forEach((link) => {
            link.addEventListener('click', (event) => {
              event.preventDefault();
              openArticle(Number(link.dataset.articleId));
            });
          });
        });
    }

    function handleSearch() {
      state.search = document.getElementById('helpSearch').value.trim();
      state.selectedArticleId = null;
      loadArticles();
    }

    document.getElementById('searchBtn').addEventListener('click', handleSearch);
    document.getElementById('helpSearch').addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        handleSearch();
      }
    });

    document.getElementById('globalSearch').addEventListener('input', (event) => {
      document.getElementById('helpSearch').value = event.target.value;
      state.search = event.target.value.trim();
      loadArticles();
    });

    document.getElementById('contactSupportBtn').addEventListener('click', () => {
      window.location.href = 'messages.php';
    });

    document.getElementById('viewAllArticles').addEventListener('click', (event) => {
      event.preventDefault();
      state.category = 'all';
      state.search = '';
      document.getElementById('helpSearch').value = '';
      loadArticles();
    });

    document.getElementById('logout-btn').addEventListener('click', () => {
      fetch('../shared/auth/logout.php', { method: 'POST' }).finally(() => {
        window.location.href = '../../index.html';
      });
    });

    fetch('../shared/auth/notifications_api.php?action=stats')
      .then((response) => response.json())
      .then((data) => {
        if (data.success && data.stats) {
          updateBadges(Number(data.stats.unread || 0));
        }
      });

    loadArticles();
  </script>
</body>
</html>

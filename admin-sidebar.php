<?php
// Shared admin sidebar include. Computes current page and outputs the navigation with active state.
$current = basename($_SERVER['PHP_SELF']);
$website_pages = ['admin-home.php', 'admin-page-content.php', 'admin-lgrc-gad.php', 'admin-news.php', 'admin-issuances.php', 'admin-programs.php', 'admin-officials.php', 'admin-documents.php', 'admin-contact-us.php'];
$website_is_active = in_array($current, $website_pages, true);
?>
<aside class="admin-sidebar" aria-label="Sidebar navigation">
  <div class="admin-sidebar__title" aria-label="Department of the Interior and Local Government">
    <img class="admin-sidebar__brand-logo" src="Logos/Department_of_the_Interior_and_Local_Government_(DILG)_Seal_-_Logo.svg" alt="DILG logo" />
    <div class="admin-sidebar__brand-text">
      <span class="admin-sidebar__brand-name">
        <span class="admin-sidebar__brand-line">Department of the Interior and Local Government</span>
      </span>
      <span class="admin-sidebar__brand-subtitle">SURIGAO DEL SUR</span>
    </div>
  </div>
  <nav class="admin-sidebar__nav">
    <a href="admin-dashboard.php" class="<?php echo $current==='admin-dashboard.php' ? 'is-active' : ''; ?>" aria-label="Dashboard: overview of website activity and administration metrics" title="Overview and administration metrics">
      <span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11.5L12 4l8 7.5V20a1 1 0 0 1-1 1h-4v-7H9v7H5a1 1 0 0 1-1-1v-8.5z"/></svg></span>
      <span>Dashboard</span>
    </a>
    <a href="admin-users.php" class="<?php echo $current==='admin-users.php' ? 'is-active' : ''; ?>" aria-label="User Management: manage administrator and staff accounts" title="Manage administrator and staff accounts">
      <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-3.33 0-8 1.79-8 4v1h16v-1c0-2.21-4.67-4-8-4z"/></svg></span>
      <span>User Management</span>
    </a>

    <details class="sidebar-submenu" <?php echo $website_is_active ? 'open' : ''; ?> >
      <summary class="sidebar-submenu__summary">
        <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg></span>
        <span>Website Content</span>
        <span class="submenu-chevron" aria-hidden="true"></span>
      </summary>
      <div class="sidebar-submenu__items">
        <a href="admin-home.php" class="<?php echo $current==='admin-home.php' ? 'is-active' : ''; ?>" aria-label="Home Management: manage homepage slides and featured content" title="Manage homepage content"><span class="submenu-item-icon"><svg viewBox="0 0 24 24"><path d="M4 11.5L12 4l8 7.5V20H4z"/></svg></span><span>Home Management</span></a>
        <a href="admin-documents.php" class="<?php echo $current==='admin-documents.php' ? 'is-active' : ''; ?>"><span class="submenu-item-icon"><svg viewBox="0 0 24 24"><path d="M4 6h6l2 2h8v11H4z"/></svg></span><span>Documents</span></a>
        <a href="admin-officials.php" class="<?php echo $current==='admin-officials.php' ? 'is-active' : ''; ?>"><span class="submenu-item-icon"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 19c0-3 2.5-5 6-5s6 2 6 5M16 6a3 3 0 0 1 0 5M16 14c3 0 5 2 5 5"/></svg></span><span>Elected Officials</span></a>
        <a href="admin-page-content.php?section=charter" class="<?php echo $current==='admin-page-content.php' && ($_GET['section'] ?? '')==='charter' ? 'is-active' : ''; ?>"><span class="submenu-item-icon"><svg viewBox="0 0 24 24"><path d="M6 4h9a3 3 0 0 1 3 3v13H9a3 3 0 0 0-3 3V4z"/><path d="M6 20h12M10 8h5M10 12h5"/></svg></span><span>Citizen's Charter</span></a>
        <details class="sidebar-submenu sidebar-submenu--nested" <?php echo $current === 'admin-lgrc-gad.php' ? 'open' : ''; ?>>
          <summary class="sidebar-submenu__summary">
            <span class="submenu-item-icon"><svg viewBox="0 0 24 24"><circle cx="8" cy="8" r="3"/><circle cx="16" cy="8" r="3"/><path d="M2.5 19c0-3 2-5 5.5-5M21.5 19c0-3-2-5-5.5-5M8 15c1-.7 2.4-1 4-1s3 .3 4 1v5H8z"/></svg></span>
            <span>LGRC and GAD</span>
            <span class="submenu-chevron" aria-hidden="true"></span>
          </summary>
          <div class="sidebar-submenu__nested-items">
            <a href="admin-lgrc-gad.php?page=about-lgrc" class="<?php echo $current === 'admin-lgrc-gad.php' && ($_GET['page'] ?? 'about-lgrc') === 'about-lgrc' ? 'is-active' : ''; ?>" title="Edit About LGRC text"><span>About LGRC</span></a>
            <a href="admin-lgrc-gad.php?page=about-gad" class="<?php echo $current === 'admin-lgrc-gad.php' && ($_GET['page'] ?? '') === 'about-gad' ? 'is-active' : ''; ?>" title="Edit About GAD text"><span>About GAD</span></a>
            <a href="admin-lgrc-gad.php?page=gad-corner" class="<?php echo $current === 'admin-lgrc-gad.php' && ($_GET['page'] ?? '') === 'gad-corner' ? 'is-active' : ''; ?>" title="Edit GAD Corner text"><span>GAD Corner</span></a>
            <a href="admin-lgrc-gad.php?page=msac" class="<?php echo $current === 'admin-lgrc-gad.php' && ($_GET['page'] ?? '') === 'msac' ? 'is-active' : ''; ?>" title="Edit MSAC text"><span>MSAC</span></a>
          </div>
        </details>
        <a href="admin-news.php" class="<?php echo $current==='admin-news.php' ? 'is-active' : ''; ?>"><span class="submenu-item-icon"><svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="14" rx="1"/><path d="M7 9h10M7 13h6M7 16h4"/></svg></span><span>News Management</span></a>
        <a href="admin-issuances.php" class="<?php echo $current==='admin-issuances.php' ? 'is-active' : ''; ?>"><span class="submenu-item-icon"><svg viewBox="0 0 24 24"><path d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h4"/></svg></span><span>Issuances Management</span></a>
        <a href="admin-page-content.php?section=legal" class="<?php echo $current==='admin-page-content.php' && ($_GET['section'] ?? '')==='legal' ? 'is-active' : ''; ?>"><span class="submenu-item-icon"><svg viewBox="0 0 24 24"><path d="M12 4v16M7 7h10M6 7l-3 6a3 3 0 0 0 6 0L6 7zM18 7l-3 6a3 3 0 0 0 6 0l-3-6zM5 20h14"/></svg></span><span>Legal Opinions</span></a>
        <a href="admin-programs.php" class="<?php echo $current==='admin-programs.php' ? 'is-active' : ''; ?>"><span class="submenu-item-icon"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/></svg></span><span>Programs &amp; Services</span></a>
        <a href="admin-contact-us.php" class="<?php echo $current==='admin-contact-us.php' ? 'is-active' : ''; ?>"><span class="submenu-item-icon"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></span><span>Contact Us</span></a>
      </div>
    </details>

    <a href="admin-reports.php" class="<?php echo $current==='admin-reports.php' ? 'is-active' : ''; ?>" aria-label="Reports and Analytics: review detailed report data and analysis" title="Review detailed report data and analysis">
      <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M18 3H6a2 2 0 0 0-2 2v14l4-3 4 3 4-3 4 3V5a2 2 0 0 0-2-2zm-1 9H7V8h10v4zm-1-6H8V5h8v1z"/></svg></span>
      <span>Reports &amp; Analytics</span>
    </a>

    <button type="button" class="sidebar-settings-link" data-open-settings>
      <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M19.14 12.94a7.49 7.49 0 0 0 .05-.94 7.49 7.49 0 0 0-.05-.94l2.03-1.58a.5.5 0 0 0 .12-.65l-1.92-3.32a.5.5 0 0 0-.6-.18l-2.39.96a6.94 6.94 0 0 0-1.63-.94L14.5 2.5a.5.5 0 0 0-.5-.5h-3.99a.5.5 0 0 0-.5.5l-.25 2.45a6.94 6.94 0 0 0-1.63.94l-2.39-.96a.5.5 0 0 0-.6.18L2.71 9.83a.5.5 0 0 0 .12.65l2.03 1.58a7.49 7.49 0 0 0-.05.94 7.49 7.49 0 0 0 .05.94L2.83 15.52a.5.5 0 0 0-.12.65l1.92 3.32a.5.5 0 0 0 .6.18l2.39-.96c.5.38 1.04.69 1.63.94l.25 2.45a.5.5 0 0 0 .5.5h3.99a.5.5 0 0 0 .5-.5l.25-2.45c.59-.25 1.13-.56 1.63-.94l2.39.96a.5.5 0 0 0 .6-.18l1.92-3.32a.5.5 0 0 0-.12-.65l-2.03-1.58zM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5z"/></svg></span>
      <span>System Settings</span>
    </button>
    <a href="admin-activity-logs.php" class="sidebar-activity-link" aria-label="Activity Logs" title="View activity logs">
      <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM7 9h10M7 13h7M7 17h4"/></svg></span>
      <span>Activity Logs</span>
    </a>
    <a href="auth/logout.php?redirect=../index.html" id="logout-btn" class="sidebar-logout">
      <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5v3H3v4h7v3zm9-13h-7v2h7v12h-7v2h7a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/></svg></span>
      <span>Log Out</span>
    </a>
  </nav>
</aside>
<style>
  .admin-sidebar {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 320px;
    min-width: 320px;
    height: 100vh;
    max-height: 100vh;
    background: linear-gradient(180deg, rgba(15, 42, 107, 0.98) 0%, rgba(8, 22, 50, 0.96) 100%);
    border-right: 1px solid rgba(255, 255, 255, 0.08);
    box-sizing: border-box;
    overflow: hidden;
  }
  .admin-sidebar__title {
    position: sticky;
    top: 0;
    z-index: 1;
    background: rgba(15, 42, 107, 0.98);
    backdrop-filter: blur(6px);
  }
  .admin-sidebar__nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 8px 10px 18px;
    box-sizing: border-box;
    overflow-y: auto;
    overflow-x: hidden;
    flex: 1 1 auto;
  }
  .admin-sidebar__nav a,
  .admin-sidebar__nav button.sidebar-settings-link,
  .sidebar-settings-link,
  .sidebar-logout {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    min-height: 42px;
    padding: 9px 12px;
    margin: 0;
    box-sizing: border-box;
    border: 0;
    border-radius: 10px;
    background: transparent;
    color: rgba(232, 240, 255, 0.92);
    text-decoration: none;
    font: inherit;
    font-size: 0.92rem;
    font-weight: 700;
    line-height: 1.2;
    text-align: left;
    cursor: pointer;
    transition: background-color 0.2s ease, transform 0.2s ease, color 0.2s ease;
  }
  .admin-sidebar__nav a:hover,
  .admin-sidebar__nav a:focus-visible,
  .admin-sidebar__nav button.sidebar-settings-link:hover,
  .admin-sidebar__nav button.sidebar-settings-link:focus-visible,
  .sidebar-settings-link:hover,
  .sidebar-settings-link:focus-visible,
  .sidebar-logout:hover,
  .sidebar-logout:focus-visible {
    background: #1f5fcf;
    color: #ffffff;
    transform: translateX(4px);
    outline: none;
  }
  .admin-sidebar__nav a.is-active,
  .admin-sidebar__nav a.is-active:hover {
    background: #1f5fcf;
    color: #ffffff;
    box-shadow: inset 3px 0 0 #ffffff;
    transform: none;
  }
  .sidebar-section-label {
    display: none;
  }

  .sidebar-submenu__summary .nav-icon svg { fill: none; stroke: currentColor; }
  .submenu-item-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    flex: 0 0 18px;
  }
  .submenu-item-icon svg {
    width: 17px;
    height: 17px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.7;
    stroke-linecap: round;
    stroke-linejoin: round;
  }
  .sidebar-submenu__items { border-left: 0; margin-left: 0; padding-left: 18px; }
  .sidebar-submenu__items a { gap: 10px; }
  .sidebar-submenu--nested { width: 100%; }
  .sidebar-submenu--nested > .sidebar-submenu__summary { min-height: 32px; padding: 6px 9px; font-size: 0.84rem; }
  .sidebar-submenu__nested-items { display: flex; flex-direction: column; gap: 2px; margin: 2px 0 2px 16px; padding-left: 10px; border-left: 1px solid rgba(190, 211, 255, 0.25); }
  .sidebar-submenu__nested-items a { min-height: 30px; padding: 6px 9px; border-radius: 8px; color: rgba(233, 241, 255, 0.82); font-size: 0.82rem; font-weight: 600; }
  .sidebar-submenu__nested-items a:hover,
  .sidebar-submenu__nested-items a:focus-visible { background: #1f5fcf; color: #ffffff; transform: none; outline: none; }
  .submenu-chevron {
    margin-left: auto;
    width: 20px;
    height: 20px;
    border-right: 2px solid currentColor;
    border-bottom: 2px solid currentColor;
    transform: rotate(135deg) scale(0.42);
    transform-origin: center;
    transition: transform 0.2s ease;
  }
  .sidebar-submenu[open] .submenu-chevron {
    transform: rotate(45deg) scale(0.42);
  }
</style>

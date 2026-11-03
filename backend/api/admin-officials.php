<?php
require_once __DIR__ . '/../auth/middleware.php';
require_role('ADMIN');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Elected Officials Management - DILG SDS</title>
  <link rel="stylesheet" href="styles.css?v=20260828-officials-layout-02">
  <style>
    .officials-admin { max-width: 1280px; margin: 0 auto; width: 100%; }
    .officials-admin h1 { color: #0f2a6b; margin: 0 0 6px; }
    .officials-admin > p { color: #64748b; margin: 0 0 22px; }
    .officials-admin-grid { display: grid; grid-template-columns: minmax(280px, 360px) 1fr; gap: 22px; align-items: start; }
    .official-form, .official-list { background: #fff; border: 1px solid #dfe7f0; border-radius: 12px; padding: 22px; box-shadow: 0 8px 24px rgba(15,42,107,.06); }
    .official-form h2, .official-list h2 { color: #0f2a6b; margin: 0 0 18px; font-size: 1.15rem; }
    .official-form label { display: block; color: #334155; font-weight: 700; font-size: .78rem; margin: 12px 0 5px; }
    .official-form input, .official-form textarea { width: 100%; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 7px; padding: 10px 11px; font: inherit; color: #1e293b; }
    .official-form textarea { min-height: 90px; resize: vertical; }
    .official-form input[type=file] { padding: 7px; }
    .image-adjuster { margin-top: 12px; border: 1px solid #dfe7f0; background: #f8fafc; border-radius: 10px; padding: 12px; }
    .image-adjuster__preview { width: 100%; aspect-ratio: 1 / 1; overflow: hidden; background: linear-gradient(135deg, #edf4ff, #f8fafc); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
    .image-adjuster__preview img { display: none; width: 100%; height: 100%; object-fit: cover; object-position: 50% 50%; transition: object-position .15s ease, transform .15s ease; }
    .image-adjuster__controls { display: grid; gap: 8px; }
    .image-adjuster__controls label { margin: 0; font-size: .76rem; color: #334155; }
    .image-adjuster__controls input[type=range] { width: 100%; }
    .image-position-scale { display: flex; justify-content: space-between; margin-top: -5px; color: #64748b; font-size: .68rem; }
    .form-actions { display: flex; gap: 9px; margin-top: 18px; }
    .form-actions button, .official-row button { border: 0; border-radius: 7px; padding: 9px 13px; cursor: pointer; font-weight: 700; }
    #save-official { background: #1f5fcf; color: #fff; }
    #cancel-edit { background: #e2e8f0; color: #334155; }
    .official-row { display: grid; grid-template-columns: 54px minmax(0, 1fr) auto; gap: 13px; align-items: center; padding: 12px 0; border-bottom: 1px solid #e2e8f0; }
    .official-row:last-child { border-bottom: 0; }
    .official-row img { width: 54px; height: 54px; object-fit: contain; border-radius: 50%; background: #eef3f8; }
    .official-row strong { display: block; color: #1e293b; }
    .official-row small { color: #64748b; }
    .official-row button { background: #e8eef8; color: #0f2a6b; margin-left: 5px; }
    .empty-state, .status-message { color: #64748b; padding: 18px 0; }
    .status-message { min-height: 18px; font-weight: 700; }
    @media (max-width: 850px) { .officials-admin-grid { grid-template-columns: 1fr; } }
    .officials-admin { max-width: none; width: 100%; margin: 0; }
    .officials-admin-grid { display: block; width: 100%; }
    .official-list { width: 100%; box-sizing: border-box; }
    .official-form-drawer { position: fixed; inset: 0 0 0 auto; z-index: 1100; width: min(430px, 100%); overflow-y: auto; background: #fff; box-shadow: -10px 0 28px rgba(15,42,107,.16); transform: translateX(100%); visibility: hidden; transition: transform .22s ease, visibility .22s ease; }
    .official-form-drawer.is-open { transform: translateX(0); visibility: visible; }
    .official-form-drawer .official-form { min-height: 100vh; box-sizing: border-box; border: 0; border-radius: 0; box-shadow: none; padding: 24px; }
    .official-form-drawer .form-title-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .official-form-drawer .form-title-row h2 { margin: 0; }
    .official-form-close { border: 0; background: transparent; color: #64748b; font-size: 1.45rem; line-height: 1; cursor: pointer; }
    .official-form-close:hover { color: #0f2a6b; }
    .official-list-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
    .official-list-header h2 { margin: 0; }
    .add-official { border: 1px solid #8db5f4; border-radius: 7px; background: #fff; color: #1f5fcf; padding: 9px 13px; font-weight: 700; cursor: pointer; }
    .add-official:hover { background: #edf4ff; }
    .official-form .form-actions { position: sticky; bottom: 0; margin: 20px -24px -24px; padding: 16px 24px 24px; background: #fff; }
    .official-form-drawer { width: min(400px, 100vw); }
    .official-form-drawer .official-form { padding: 18px 20px 0; }
    .official-form-drawer .official-form h2 { margin-bottom: 14px; font-size: 1.05rem; }
    .official-form-drawer .official-form label { margin: 9px 0 4px; font-size: .73rem; }
    .official-form-drawer .official-form input,
    .official-form-drawer .official-form textarea { min-height: 36px; padding: 8px 10px; font-size: .82rem; }
    .official-form-drawer .official-form textarea { min-height: 68px; }
    .official-form-drawer .image-adjuster { margin-top: 8px; padding: 8px; border-radius: 8px; }
    .official-form-drawer .image-adjuster__preview { width: 100%; max-width: 110px; aspect-ratio: 1 / 1; margin: 0 auto 8px; border-radius: 7px; }
    .official-form-drawer .image-adjuster__controls { gap: 5px; }
    .official-form-drawer .image-adjuster__controls label { margin: 0; font-size: .68rem; }
    .official-form-drawer .image-adjuster__controls input[type=range] { min-height: 16px; padding: 0; }
    .official-form-drawer .image-position-scale { font-size: .6rem; }
    .official-form-drawer .form-actions { position: sticky; bottom: 0; z-index: 2; margin: 16px -20px 0; padding: 12px 20px 16px; border-top: 1px solid #e2e8f0; background: rgba(255,255,255,.98); }
    .official-form-drawer .form-actions button { min-height: 36px; padding: 8px 12px; font-size: .78rem; }
    .official-form-drawer .status-message { padding: 8px 0 14px; font-size: .76rem; }
    .official-form-drawer #is-active { width: auto; min-height: auto; }
    @media (max-width: 520px) { .official-form-drawer { width: 100%; } }
    .officials-admin { max-width: none; margin: 0; }
    .officials-admin-grid { display: block; width: 100%; }
    .official-list { width: 100%; box-sizing: border-box; padding: 20px; }
    .official-list-header { margin-bottom: 12px; }
    .official-filters { display: grid; grid-template-columns: minmax(220px, 1fr) repeat(3, minmax(130px, 160px)); gap: 8px; margin-bottom: 12px; }
    .official-filters label { display: flex; flex-direction: column; gap: 5px; color: #475569; font-size: .7rem; font-weight: 700; }
    .official-filters input, .official-filters select { width: 100%; min-height: 36px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 7px; padding: 8px 10px; background: #fff; color: #334155; font: inherit; }
    .official-hint { margin-bottom: 10px; padding: 9px 11px; border: 1px solid #d9e6fa; border-radius: 6px; background: #f1f6ff; color: #2457a6; font-size: .72rem; font-weight: 700; }
    .official-table-wrap { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 8px; }
    .official-table { width: 100%; min-width: 780px; border-collapse: collapse; table-layout: fixed; }
    .official-table th { padding: 10px 9px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #64748b; text-align: left; font-size: .62rem; letter-spacing: .04em; text-transform: uppercase; }
    .official-table td { padding: 10px 9px; border-bottom: 1px solid #e2e8f0; color: #334155; font-size: .74rem; vertical-align: middle; }
    .official-table tr:last-child td { border-bottom: 0; }
    .official-table th:nth-child(1) { width: 7%; } .official-table th:nth-child(2) { width: 10%; } .official-table th:nth-child(3) { width: 29%; } .official-table th:nth-child(4) { width: 16%; } .official-table th:nth-child(5) { width: 11%; } .official-table th:nth-child(6) { width: 10%; } .official-table th:nth-child(7) { width: 17%; }
    .official-order { display: inline-grid; place-items: center; width: 27px; height: 27px; border-radius: 6px; background: #f1f5fa; color: #29466e; font-weight: 800; }
    .official-drag { color: #6d83a4; font-size: 1rem; }
    .official-photo-thumb { width: 42px; height: 42px; display: block; object-fit: cover; border-radius: 50%; background: #eef3f8; }
    .official-name { display: block; color: #1e365c; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .official-meta { display: block; margin-top: 3px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .official-sector { color: #415a7d; }
    .official-status { display: inline-flex; align-items: center; gap: 5px; padding: 5px 8px; border-radius: 5px; background: #e4f7ec; color: #137a48; font-size: .65rem; font-weight: 800; }
    .official-status:before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .official-status--draft { background: #fff2d5; color: #a96b00; }
    .official-actions { display: flex; gap: 5px; justify-content: flex-end; }
    .official-actions button { min-height: 30px; padding: 6px 9px; border: 1px solid #bdd1f4; border-radius: 6px; background: #fff; color: #1f5fcf; font-size: .7rem; cursor: pointer; }
    .official-actions .delete { border-color: #f2bebe; color: #c33434; }
    .official-table-footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding-top: 12px; color: #64748b; font-size: .72rem; }
    .official-pagination { display: flex; gap: 5px; }
    .official-pagination button { width: 30px; height: 30px; border: 1px solid #d8e2ef; border-radius: 6px; background: #fff; color: #334155; cursor: pointer; }
    .official-pagination button.active, .official-pagination button:hover { background: #2f6cdf; border-color: #2f6cdf; color: #fff; }
    @media (max-width: 850px) { .official-filters { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 560px) { .official-filters { grid-template-columns: 1fr; } .official-table-footer { align-items: flex-start; flex-direction: column; } }
  </style>
</head>
<body class="admin-dashboard-ref-body admin-officials-page">
  <div class="admin-portal-shell">
    <?php include __DIR__ . '/admin-sidebar.php'; ?>
    <div hidden aria-hidden="true">
      <div class="admin-sidebar__title" aria-label="Department of the Interior and Local Government">
        <img class="admin-sidebar__brand-logo" src="Logos/Department_of_the_Interior_and_Local_Government_(DILG)_Seal_-_Logo.svg" alt="DILG logo" />
        <div class="admin-sidebar__brand-text">
          <span class="admin-sidebar__brand-name">
            <span class="admin-sidebar__brand-line">Department of the Interior and Local Government</span>
          </span>
          <span class="admin-sidebar__brand-subtitle">SURIGAO DEL SUR</span>
        </div>
      </div>
<?php $current = basename($_SERVER['PHP_SELF']); ?>
      <nav class="admin-sidebar__nav">
        <div class="sidebar-section-label">ADMINISTRATION</div>
        <a href="admin-dashboard.php" class="<?php echo $current==='admin-dashboard.php' ? 'is-active' : ''; ?>" aria-label="Dashboard: overview of website activity and administration metrics" title="Overview and administration metrics">
          <span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11.5L12 4l8 7.5V20a1 1 0 0 1-1 1h-4v-7H9v7H5a1 1 0 0 1-1-1v-8.5z"/></svg></span>
          <span>Dashboard</span>
        </a>
        <a href="admin-users.php" class="<?php echo $current==='admin-users.php' ? 'is-active' : ''; ?>" aria-label="User Management: manage administrator and staff accounts" title="Manage administrator and staff accounts">
          <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-3.33 0-8 1.79-8 4v1h16v-1c0-2.21-4.67-4-8-4z"/></svg></span>
          <span>User Management</span>
        </a>

        <div class="sidebar-section-label">CONTENT MANAGEMENT</div>
        <a href="admin-home.php" class="<?php echo $current==='admin-home.php' ? 'is-active' : ''; ?>" aria-label="Home Management: manage homepage slides and featured content" title="Manage homepage content">
          <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 11.5L12 4l8 7.5V20H4z"/></svg></span>
          <span>Home Management</span>
        </a>
        <a href="admin-documents.php" class="<?php echo $current==='admin-documents.php' ? 'is-active' : ''; ?>" aria-label="Documents Management: manage PBAC, annual reports, and official documents" title="Manage document records">
          <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M7 3h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm6 1.5V9h4.5"/></svg></span>
          <span>Documents</span>
        </a>
        <a href="admin-officials.php" class="<?php echo $current==='admin-officials.php' ? 'is-active' : ''; ?>" aria-label="Elected Officials: manage published official profiles" title="Manage elected official profiles">
          <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg></span>
          <span>Elected Officials</span>
        </a>
        <a href="admin-news.php" class="<?php echo $current==='admin-news.php' ? 'is-active' : ''; ?>" aria-label="News Management: manage news articles and publications" title="Manage news publications">
          <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 5h16v10H7l-3 3V5z"/></svg></span>
          <span>News Management</span>
        </a>
        <a href="admin-issuances.php" class="<?php echo $current==='admin-issuances.php' ? 'is-active' : ''; ?>" aria-label="Issuances Management: manage published circulars and legal issuances" title="Manage published issuances">
          <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M5 4h10l4 4v12H5z"/></svg></span>
          <span>Issuances Management</span>
        </a>
        <a href="admin-programs.php" class="<?php echo $current==='admin-programs.php' ? 'is-active' : ''; ?>" aria-label="Programs and Services: manage published programs and services" title="Manage programs and services">
          <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h10M4 17h16"/></svg></span>
          <span>Programs &amp; Services</span>
        </a>
        <div class="sidebar-section-label">REPORTS &amp; ANALYTICS</div>
        <a href="admin-dashboard.php#reports-analytics" class="<?php echo $current==='admin-dashboard.php' ? 'is-active' : ''; ?>" aria-label="Reports and Analytics: review operational reports and activity" title="Review reports and activity">
          <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M18 3H6a2 2 0 0 0-2 2v14l4-3 4 3 4-3 4 3V5a2 2 0 0 0-2-2zm-1 9H7V8h10v4zm-1-6H8V5h8v1z"/></svg></span>
          <span>Reports &amp; Analytics</span>
        </a>

        <div class="sidebar-section-label">SYSTEM</div>
        <button type="button" class="sidebar-settings-link" data-open-settings aria-label="System Settings: manage portal configuration" title="Manage portal configuration">
          <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M19.14 12.94a7.49 7.49 0 0 0 .05-.94 7.49 7.49 0 0 0-.05-.94l2.03-1.58a.5.5 0 0 0 .12-.65l-1.92-3.32a.5.5 0 0 0-.6-.18l-2.39.96a6.94 6.94 0 0 0-1.63-.94L14.5 2.5a.5.5 0 0 0-.5-.5h-3.99a.5.5 0 0 0-.5.5l-.25 2.45a6.94 6.94 0 0 0-1.63.94l-2.39-.96a.5.5 0 0 0-.6.18L2.71 9.83a.5.5 0 0 0 .12.65l2.03 1.58a7.49 7.49 0 0 0-.05.94 7.49 7.49 0 0 0 .05.94L2.83 15.52a.5.5 0 0 0-.12.65l1.92 3.32a.5.5 0 0 0 .6.18l2.39-.96c.5.38 1.04.69 1.63.94l.25 2.45a.5.5 0 0 0 .5.5h3.99a.5.5 0 0 0 .5-.5l.25-2.45c.59-.25 1.13-.56 1.63-.94l2.39.96a.5.5 0 0 0 .6-.18l1.92-3.32a.5.5 0 0 0-.12-.65l-2.03-1.58zM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5z"/></svg></span>
          <span>System Settings</span>
        </button>
        <a href="auth/logout.php?redirect=../index.html" id="logout-btn" class="sidebar-logout"><span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5v3H3v4h7v3zm9-13h-7v2h7v12h-7v2h7a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/></svg></span><span>Log Out</span></a>
      </nav>
    </div>
    <!-- aside removed: using shared include -->
    <main class="admin-main">
      <section class="officials-admin">
        <h1>Elected Officials</h1>
        <p>Manage logos, names, photos, contact information, Facebook pages, and websites.</p>
        <div class="officials-admin-grid">
          <aside class="official-form-drawer" id="official-form-drawer" aria-hidden="true">
          <form class="official-form" id="official-form" enctype="multipart/form-data">
            <div class="form-title-row"><h2 id="form-title">Add Official</h2><button type="button" class="official-form-close" id="close-official-form" aria-label="Close official editor">×</button></div>
            <input type="hidden" name="id" id="official-id">
            <label for="office-title">Office / LGU</label><input id="office-title" name="office_title" required>
            <label for="official-name">Official name</label><input id="official-name" name="official_name" required>
            <label for="contact-details">Contact information</label><textarea id="contact-details" name="contact_details" placeholder="One detail per line"></textarea>
            <label for="logo-path">Logo path or URL</label><input id="logo-path" name="logo_path" placeholder="Logos/example.png">
            <div class="image-adjuster" aria-label="Logo adjustment controls">
              <div class="image-adjuster__preview"><img id="official-logo-preview" alt="Logo preview" src=""></div>
              <div class="image-adjuster__controls">
                <label for="official-logo-horizontal">Horizontal Position <output id="official-logo-horizontal-value">50%</output></label>
                <input id="official-logo-horizontal" type="range" min="0" max="100" step="1" value="50">
                <div class="image-position-scale"><span>Left 0%</span><span>Right 100%</span></div>
                <label for="official-logo-vertical">Vertical Position <output id="official-logo-vertical-value">50%</output></label>
                <input id="official-logo-vertical" type="range" min="0" max="100" step="1" value="50">
                <div class="image-position-scale"><span>Top 0%</span><span>Bottom 100%</span></div>
              </div>
            </div>
            <label for="logo">Upload new logo</label><input id="logo" name="logo" type="file" accept="image/*,.svg">
            <label for="photo-path">Photo path or URL</label><input id="photo-path" name="photo_path" placeholder="Logos/Local Officials Image/example.jpg">
            <div class="image-adjuster" aria-label="Photo adjustment controls">
              <div class="image-adjuster__preview"><img id="official-photo-preview" alt="Official photo preview" src=""></div>
              <div class="image-adjuster__controls">
                <label for="official-photo-horizontal">Horizontal Position <output id="official-photo-horizontal-value">50%</output></label>
                <input id="official-photo-horizontal" type="range" min="0" max="100" step="1" value="50">
                <div class="image-position-scale"><span>Left 0%</span><span>Right 100%</span></div>
                <label for="official-photo-vertical">Vertical Position <output id="official-photo-vertical-value">50%</output></label>
                <input id="official-photo-vertical" type="range" min="0" max="100" step="1" value="50">
                <div class="image-position-scale"><span>Top 0%</span><span>Bottom 100%</span></div>
              </div>
            </div>
            <label for="photo">Upload new photo</label><input id="photo" name="photo" type="file" accept="image/*,.svg">
            <input id="photo-position-x" name="photo_position_x" type="hidden" value="50">
            <input id="photo-position-y" name="photo_position_y" type="hidden" value="50">
            <label for="facebook-url">Facebook URL (optional)</label><input id="facebook-url" name="facebook_url" type="url">
            <label for="website-url">Website URL (optional)</label><input id="website-url" name="website_url" type="url">
            <label for="profile-url">LGU profile URL (optional)</label><input id="profile-url" name="profile_url" type="text" inputmode="url" placeholder="Leave blank or enter # if unavailable">
            <label for="sort-order">Display order</label><input id="sort-order" name="sort_order" type="number" min="1" value="1">
            <label><input id="is-active" name="is_active" type="checkbox" checked> Show on public directory</label>
            <div class="form-actions"><button id="save-official" type="submit">Save Changes</button><button id="cancel-edit" type="button">Cancel</button></div>
            <div class="status-message" id="form-status" role="status"></div>
          </form>
          </aside>
          <section class="official-list">
            <div class="official-list-header"><h2>Directory Records</h2><button type="button" class="add-official" id="add-official">+ Add Official</button></div>
            <div class="official-filters">
              <label><span>Search officials</span><input id="official-search" type="search" placeholder="Search official name..."></label>
              <label>Level<select id="official-level"><option value="all">Level: All</option><option value="legislative">Legislative</option><option value="executive">Executive</option><option value="local">Local Government</option></select></label>
              <label>LGU<select id="official-lgu"><option value="all">LGU: All</option></select></label>
              <label>Status<select id="official-status-filter"><option value="all">Status: All</option><option value="published">Published</option><option value="draft">Draft</option></select></label>
            </div>
            <div class="official-hint">ⓘ Drag and drop the rows to reorder the officials.</div>
            <div id="official-list"><div class="empty-state">Loading...</div></div>
          </section>
        </div>
      </section>
    </main>
  </div>
<script>
(function () {
  var api = 'auth/officials_api.php';
  var form = document.getElementById('official-form');
  var formDrawer = document.getElementById('official-form-drawer');
  var list = document.getElementById('official-list');
  var status = document.getElementById('form-status');
  var search = document.getElementById('official-search');
  var levelFilter = document.getElementById('official-level');
  var lguFilter = document.getElementById('official-lgu');
  var statusFilter = document.getElementById('official-status-filter');
  var page = 1;
  var pageSize = 8;
  var fields = ['office_title', 'official_name', 'contact_details', 'logo_path', 'photo_path', 'facebook_url', 'website_url', 'profile_url', 'sort_order'];
  var officialLogoPreview = document.getElementById('official-logo-preview');
  var officialPhotoPreview = document.getElementById('official-photo-preview');
  var officialLogoHorizontal = document.getElementById('official-logo-horizontal');
  var officialLogoVertical = document.getElementById('official-logo-vertical');
  var officialPhotoHorizontal = document.getElementById('official-photo-horizontal');
  var officialPhotoVertical = document.getElementById('official-photo-vertical');
  var officialPhotoPositionX = document.getElementById('photo-position-x');
  var officialPhotoPositionY = document.getElementById('photo-position-y');
  var pendingOfficialLogo = null;
  var pendingOfficialPhoto = null;
  function esc(value) { return String(value || '').replace(/[&<>"']/g, function (char) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]); }); }
  function load() { fetch(api).then(function (r) { return r.json(); }).then(function (data) { var records = data.officials || []; var values = {}; records.forEach(function (item) { values[item.office_title] = true; }); var current = lguFilter.value; lguFilter.innerHTML = '<option value="all">LGU: All</option>' + Object.keys(values).sort().map(function (value) { return '<option value="' + esc(value) + '">' + esc(value) + '</option>'; }).join(''); lguFilter.value = values[current] ? current : 'all'; render(records); }).catch(function () { list.innerHTML = '<div class="empty-state">Unable to load records.</div>'; }); }
  function getLevel(item) { var text = String(item.office_title || '').toLowerCase(); if (text.indexOf('district') !== -1 || text.indexOf('congress') !== -1) return 'Legislative'; if (text.indexOf('governor') !== -1 || text.indexOf('provincial') !== -1) return 'Executive'; return 'Local Government'; }
  function render(records) {
    var term = search.value.trim().toLowerCase();
    var level = levelFilter.value;
    var lgu = lguFilter.value;
    var recordStatus = statusFilter.value;
    records.forEach(function (item) { item._level = getLevel(item); item._status = item.is_active ? 'published' : 'draft'; });
    records = records.filter(function (item) { return (!term || (String(item.official_name || '') + ' ' + String(item.office_title || '')).toLowerCase().indexOf(term) !== -1) && (level === 'all' || item._level.toLowerCase().indexOf(level) !== -1) && (lgu === 'all' || item.office_title === lgu) && (recordStatus === 'all' || item._status === recordStatus); });
    if (!records.length) { list.innerHTML = '<div class="empty-state">No officials found.</div>'; return; }
    var pages = Math.max(1, Math.ceil(records.length / pageSize)); page = Math.min(page, pages); var start = (page - 1) * pageSize; var visible = records.slice(start, start + pageSize);
    list.innerHTML = '<div class="official-table-wrap"><table class="official-table"><thead><tr><th>Order</th><th>Photo</th><th>Official / Office</th><th>Sector</th><th>Status</th><th>Order</th><th>Actions</th></tr></thead><tbody>' + visible.map(function (item, index) { var image = esc(item.photo_path || item.logo_path || ''); return '<tr draggable="true"><td><span class="official-drag">⋮</span> <span class="official-order">' + (start + index + 1) + '</span></td><td><img class="official-photo-thumb" src="' + image + '" alt="" onerror="this.onerror=null;this.src=\'Logos/Department_of_the_Interior_and_Local_Government_(DILG)_Seal_-_Logo.svg\';"></td><td><span class="official-name">' + esc(item.official_name) + '</span><span class="official-meta">' + esc(item.office_title) + '</span></td><td><span class="official-sector">' + item._level + '</span></td><td><span class="official-status' + (item._status === 'draft' ? ' official-status--draft' : '') + '">' + (item._status === 'published' ? 'Published' : 'Draft') + '</span></td><td>' + esc(item.sort_order || start + index + 1) + '</td><td><div class="official-actions"><button type="button" data-edit="' + item.id + '">Edit</button><button type="button" class="delete" data-delete="' + item.id + '">Delete</button></div></td></tr>'; }).join('') + '</tbody></table></div><div class="official-table-footer"><span>Showing ' + (start + 1) + ' to ' + Math.min(start + pageSize, records.length) + ' of ' + records.length + ' officials</span><div class="official-pagination"><button type="button" data-page="prev">‹</button>' + Array.from({ length: pages }, function (_, index) { return '<button type="button" data-page="' + (index + 1) + '" class="' + (index + 1 === page ? 'active' : '') + '">' + (index + 1) + '</button>'; }).join('') + '<button type="button" data-page="next">›</button></div></div>';
  }
  function updatePreviewTransform(target, horizontal, vertical, horizontalValue, verticalValue) {
    if (!target) return;
    var x = Number(horizontal && horizontal.value ? horizontal.value : 50);
    var y = Number(vertical && vertical.value ? vertical.value : 50);
    target.style.objectPosition = x + '% ' + y + '%';
    target.style.transform = 'scale(1.12) translate(' + ((x - 50) * -0.16) + '%, ' + ((y - 50) * -0.16) + '%)';
    if (horizontalValue) horizontalValue.textContent = x + '%';
    if (verticalValue) verticalValue.textContent = y + '%';
    if (target === officialPhotoPreview) {
      if (officialPhotoPositionX) officialPhotoPositionX.value = x;
      if (officialPhotoPositionY) officialPhotoPositionY.value = y;
    }
  }
  function populateImagePreview(selector, value) {
    var image = document.querySelector(selector);
    if (!image) return;
    image.removeAttribute('style');
    image.src = value || '';
    image.style.display = value ? 'block' : 'none';
    image.onload = function () { image.style.display = 'block'; };
    image.onerror = function () { image.style.display = 'none'; image.removeAttribute('src'); };
    if (value) {
      var isLogo = selector.indexOf('logo') >= 0;
      updatePreviewTransform(image, isLogo ? officialLogoHorizontal : officialPhotoHorizontal, isLogo ? officialLogoVertical : officialPhotoVertical, document.getElementById(isLogo ? 'official-logo-horizontal-value' : 'official-photo-horizontal-value'), document.getElementById(isLogo ? 'official-logo-vertical-value' : 'official-photo-vertical-value'));
    }
  }
  function buildAdjustedImageFile(file, horizontalControl, verticalControl) {
    // Preserve the original file; position controls are applied through object-position at render time.
    return Promise.resolve(file);
  }
  function openFormDrawer(title) { document.getElementById('form-title').textContent = title; formDrawer.classList.add('is-open'); formDrawer.setAttribute('aria-hidden', 'false'); }
  function clearForm() { form.reset(); document.getElementById('official-id').value = ''; document.getElementById('form-title').textContent = 'Add Official'; document.getElementById('save-official').textContent = 'Save Changes'; document.getElementById('is-active').checked = true; status.textContent = ''; if (officialLogoPreview) { officialLogoPreview.src = ''; officialLogoPreview.style.display = 'none'; } if (officialPhotoPreview) { officialPhotoPreview.src = ''; officialPhotoPreview.style.display = 'none'; } pendingOfficialLogo = null; pendingOfficialPhoto = null; formDrawer.classList.remove('is-open'); formDrawer.setAttribute('aria-hidden', 'true'); }
  list.addEventListener('click', function (event) { var id = event.target.getAttribute('data-edit') || event.target.getAttribute('data-delete'); if (!id) return; if (event.target.hasAttribute('data-delete')) { if (!confirm('Delete this official?')) return; var body = new URLSearchParams({ action: 'delete', id: id }); fetch(api, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: body }).then(function (r) { return r.json(); }).then(function (data) { if (data.error) throw new Error(data.error); load(); }); return; } fetch(api).then(function (r) { return r.json(); }).then(function (data) { var item = (data.officials || []).find(function (record) { return String(record.id) === String(id); }); if (!item) return; fields.forEach(function (name) { document.getElementById(name.replace('_', '-')).value = item[name] || ''; }); if (item.logo_path) populateImagePreview('#official-logo-preview', item.logo_path); else officialLogoPreview.src = '';
 if (item.photo_path) populateImagePreview('#official-photo-preview', item.photo_path); else officialPhotoPreview.src = '';
 officialPhotoHorizontal.value = item.photo_position_x == null ? 50 : item.photo_position_x;
 officialPhotoVertical.value = item.photo_position_y == null ? 50 : item.photo_position_y;
 if (officialPhotoPositionX) officialPhotoPositionX.value = officialPhotoHorizontal.value;
 if (officialPhotoPositionY) officialPhotoPositionY.value = officialPhotoVertical.value;
 updatePreviewTransform(officialPhotoPreview, officialPhotoHorizontal, officialPhotoVertical, document.getElementById('official-photo-horizontal-value'), document.getElementById('official-photo-vertical-value'));
 document.getElementById('official-id').value = item.id; document.getElementById('is-active').checked = !!item.is_active; document.getElementById('save-official').textContent = 'Save Changes'; openFormDrawer('Edit Official'); }); });
  form.addEventListener('submit', function (event) { event.preventDefault(); var data = new FormData(form); var logoFile = document.getElementById('logo') && document.getElementById('logo').files && document.getElementById('logo').files[0] ? document.getElementById('logo').files[0] : null; var photoFile = document.getElementById('photo') && document.getElementById('photo').files && document.getElementById('photo').files[0] ? document.getElementById('photo').files[0] : null; data.append('action', data.get('official-id') ? 'update' : (document.getElementById('official-id').value ? 'update' : 'create')); if (document.getElementById('official-id').value) data.set('action', 'update'); Promise.resolve().then(function () {
    var tasks = [];
    if (logoFile) tasks.push(buildAdjustedImageFile(logoFile, officialLogoHorizontal, officialLogoVertical).then(function () {}));
    if (photoFile) tasks.push(buildAdjustedImageFile(photoFile, officialPhotoHorizontal, officialPhotoVertical).then(function () {}));
    return Promise.all(tasks).then(function () { return data; });
  }).then(function (payload) { return fetch(api, { method: 'POST', body: payload }); }).then(function (r) { return r.json(); }).then(function (result) { if (result.error) throw new Error(result.error); status.textContent = 'Saved.'; clearForm(); load(); }).catch(function (error) { status.textContent = error.message; }); });
  if (officialLogoHorizontal) officialLogoHorizontal.addEventListener('input', function () { updatePreviewTransform(officialLogoPreview, officialLogoHorizontal, officialLogoVertical, document.getElementById('official-logo-horizontal-value'), document.getElementById('official-logo-vertical-value')); });
  if (officialLogoVertical) officialLogoVertical.addEventListener('input', function () { updatePreviewTransform(officialLogoPreview, officialLogoHorizontal, officialLogoVertical, document.getElementById('official-logo-horizontal-value'), document.getElementById('official-logo-vertical-value')); });
  if (officialPhotoHorizontal) officialPhotoHorizontal.addEventListener('input', function () { updatePreviewTransform(officialPhotoPreview, officialPhotoHorizontal, officialPhotoVertical, document.getElementById('official-photo-horizontal-value'), document.getElementById('official-photo-vertical-value')); });
  if (officialPhotoVertical) officialPhotoVertical.addEventListener('input', function () { updatePreviewTransform(officialPhotoPreview, officialPhotoHorizontal, officialPhotoVertical, document.getElementById('official-photo-horizontal-value'), document.getElementById('official-photo-vertical-value')); });
  if (document.getElementById('logo')) {
    document.getElementById('logo').addEventListener('change', function (event) {
      var file = event.target.files && event.target.files[0];
      if (!file) return;
      pendingOfficialLogo = file;
      var reader = new FileReader();
      reader.onload = function (loadEvent) {
        officialLogoPreview.src = loadEvent.target.result;
        officialLogoPreview.style.display = 'block';
        officialLogoPreview.onload = function () { officialLogoPreview.style.display = 'block'; };
        document.getElementById('logo-path').value = '';
        updatePreviewTransform(officialLogoPreview, officialLogoHorizontal, officialLogoVertical, document.getElementById('official-logo-horizontal-value'), document.getElementById('official-logo-vertical-value'));
      };
      reader.readAsDataURL(file);
    });
  }
  if (document.getElementById('photo')) {
    document.getElementById('photo').addEventListener('change', function (event) {
      var file = event.target.files && event.target.files[0];
      if (!file) return;
      pendingOfficialPhoto = file;
      var reader = new FileReader();
      reader.onload = function (loadEvent) {
        officialPhotoPreview.src = loadEvent.target.result;
        officialPhotoPreview.style.display = 'block';
        officialPhotoPreview.onload = function () { officialPhotoPreview.style.display = 'block'; };
        document.getElementById('photo-path').value = '';
        updatePreviewTransform(officialPhotoPreview, officialPhotoHorizontal, officialPhotoVertical, document.getElementById('official-photo-horizontal-value'), document.getElementById('official-photo-vertical-value'));
      };
      reader.readAsDataURL(file);
    });
  }
  [['logo-path', officialLogoPreview, officialLogoHorizontal, officialLogoVertical, 'official-logo-horizontal-value', 'official-logo-vertical-value'], ['photo-path', officialPhotoPreview, officialPhotoHorizontal, officialPhotoVertical, 'official-photo-horizontal-value', 'official-photo-vertical-value']].forEach(function (entry) {
    var pathInput = document.getElementById(entry[0]);
    if (!pathInput) return;
    pathInput.addEventListener('input', function () {
      var value = pathInput.value.trim();
      if (!value) return;
      entry[1].src = value;
      entry[1].style.display = 'block';
      entry[1].onload = function () { entry[1].style.display = 'block'; };
      entry[1].onerror = function () { entry[1].style.display = 'none'; entry[1].removeAttribute('src'); };
      updatePreviewTransform(entry[1], entry[2], entry[3], document.getElementById(entry[4]), document.getElementById(entry[5]));
    });
  });
  document.getElementById('cancel-edit').addEventListener('click', clearForm);
  document.getElementById('close-official-form').addEventListener('click', clearForm);
  document.getElementById('add-official').addEventListener('click', function () { clearForm(); openFormDrawer('Add Official'); document.getElementById('office-title').focus(); });
  function refreshFilteredOfficials() { page = 1; load(); }
  search.addEventListener('input', refreshFilteredOfficials);
  levelFilter.addEventListener('change', refreshFilteredOfficials);
  statusFilter.addEventListener('change', refreshFilteredOfficials);
  lguFilter.addEventListener('change', refreshFilteredOfficials);
  list.addEventListener('click', function (event) {
    var pageButton = event.target.closest('[data-page]');
    if (!pageButton) return;
    var value = pageButton.getAttribute('data-page');
    var pages = Math.max(1, Math.ceil(list.querySelectorAll('tbody tr').length ? Number(list.querySelector('.official-table-footer span').textContent.match(/of (\d+)/)[1]) / pageSize : 1));
    if (value === 'prev') page = Math.max(1, page - 1);
    else if (value === 'next') page = Math.min(pages, page + 1);
    else page = Number(value) || 1;
    load();
  });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && formDrawer.classList.contains('is-open')) clearForm(); });
  document.getElementById('logout-btn').addEventListener('click', function (event) { event.preventDefault(); fetch('../auth/logout.php', {method: 'POST'}).finally(function () { location.href = 'index.html'; }); });
  load();
}());

const settingsModal = document.getElementById('settings-modal');
const settingsForm = document.getElementById('system-settings-form');
const settingsStatus = document.getElementById('settings-status');

function openSettingsModal() {
  if (!settingsModal) return;
  settingsModal.classList.add('is-open');
  settingsModal.setAttribute('aria-hidden', 'false');
  if (settingsStatus) settingsStatus.textContent = '';
}

function closeSettingsModal() {
  if (!settingsModal) return;
  settingsModal.classList.remove('is-open');
  settingsModal.setAttribute('aria-hidden', 'true');
}

document.querySelectorAll('[data-open-settings]').forEach(function (button) {
  button.addEventListener('click', openSettingsModal);
});

document.querySelectorAll('[data-close-settings]').forEach(function (button) {
  button.addEventListener('click', closeSettingsModal);
});

if (settingsModal) {
  settingsModal.addEventListener('click', function (event) {
    if (event.target === settingsModal || event.target.classList.contains('settings-modal__backdrop')) {
      closeSettingsModal();
    }
  });
}

document.addEventListener('keydown', function (event) {
  if (event.key === 'Escape' && settingsModal && settingsModal.classList.contains('is-open')) {
    closeSettingsModal();
  }
});

if (settingsForm) {
  const savedSettings = JSON.parse(localStorage.getItem('dilgSystemSettings') || '{}');
  if (savedSettings.websiteTitle) settingsForm.websiteTitle.value = savedSettings.websiteTitle;
  if (savedSettings.contactEmail) settingsForm.contactEmail.value = savedSettings.contactEmail;
  if (savedSettings.timezone) settingsForm.timezone.value = savedSettings.timezone;
  if (savedSettings.maintenanceMode) settingsForm.maintenanceMode.value = savedSettings.maintenanceMode;
  if (savedSettings.publishState) settingsForm.publishState.value = savedSettings.publishState;

  settingsForm.addEventListener('submit', function (event) {
    event.preventDefault();
    const formData = new FormData(settingsForm);
    const values = Object.fromEntries(formData.entries());
    localStorage.setItem('dilgSystemSettings', JSON.stringify(values));
    if (settingsStatus) settingsStatus.textContent = 'Settings saved successfully.';
    closeSettingsModal();
  });
}
</script>
<div class="settings-modal" id="settings-modal" aria-hidden="true">
  <div class="settings-modal__backdrop" data-close-settings></div>
  <div class="settings-modal__panel" role="dialog" aria-modal="true" aria-labelledby="settings-title">
    <div class="settings-modal__header">
      <h2 id="settings-title">System Settings</h2>
      <button type="button" class="settings-modal__close" aria-label="Close settings" data-close-settings>×</button>
    </div>
    <form class="settings-form" id="system-settings-form">
      <label>
        Website Title
        <input type="text" name="websiteTitle" value="DILG SDS" />
      </label>
      <div class="settings-form__row">
        <label>
          Contact Email
          <input type="email" name="contactEmail" value="info@dilg.gov.ph" />
        </label>
        <label>
          Default Timezone
          <select name="timezone">
            <option value="Asia/Manila" selected>Asia/Manila</option>
            <option value="UTC">UTC</option>
            <option value="America/New_York">America/New_York</option>
          </select>
        </label>
      </div>
      <div class="settings-form__row">
        <label>
          Maintenance Mode
          <select name="maintenanceMode">
            <option value="off" selected>Off</option>
            <option value="on">On</option>
          </select>
        </label>
        <label>
          Default Publishing
          <select name="publishState">
            <option value="published" selected>Published</option>
            <option value="draft">Draft</option>
          </select>
        </label>
      </div>
      <div class="settings-modal__actions">
        <button type="button" class="settings-btn settings-btn--secondary" data-close-settings>Cancel</button>
        <button type="submit" class="settings-btn settings-btn--primary">Save Settings</button>
      </div>
    </form>
    <p class="settings-status" id="settings-status" aria-live="polite"></p>
  </div>
</div>
</body>
</html>

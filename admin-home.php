<?php
require_once __DIR__ . '/auth/middleware.php';
require_role('ADMIN');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Home Management - DILG SDS</title>
  <link rel="stylesheet" href="styles.css?v=20260814-admin-ref-01">
  <style>
    .home-admin { max-width: 1280px; width: 100%; margin: 0 auto; }
    .home-admin h1 { color: #0f2a6b; margin: 0 0 6px; }
    .home-admin > p { color: #64748b; margin: 0 0 22px; }
    .home-grid { display: grid; grid-template-columns: 360px minmax(0, 1fr); gap: 22px; }
    .home-form, .home-list { background: #fff; border: 1px solid #dfe7f0; border-radius: 12px; padding: 22px; box-shadow: 0 8px 24px rgba(15,42,107,.06); }
    .home-form h2, .home-list h2 { color: #0f2a6b; margin: 0 0 16px; font-size: 1.15rem; }
    .home-form label { display: block; color: #334155; font-weight: 700; font-size: .78rem; margin: 11px 0 5px; }
    .home-form input, .home-form textarea, .home-form select { width: 100%; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 7px; padding: 10px; font: inherit; }
    .home-form textarea { min-height: 105px; resize: vertical; }
    .home-form input[type=file] { padding: 7px; }
    .image-adjuster { margin-top: 12px; border: 1px solid #dfe7f0; background: #f8fafc; border-radius: 10px; padding: 12px; }
    .image-adjuster__preview { width: 100%; aspect-ratio: 16 / 9; overflow: hidden; background: linear-gradient(135deg, #edf4ff, #f8fafc); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
    .image-adjuster__preview img { display: none; width: 100%; height: 100%; object-fit: cover; object-position: 50% 50%; transition: object-position .15s ease, transform .15s ease; }
    .image-adjuster__controls { display: grid; gap: 8px; }
    .image-adjuster__controls label { margin: 0; font-size: .76rem; color: #334155; }
    .image-adjuster__controls input[type=range] { width: 100%; }
    .image-position-scale { display: flex; justify-content: space-between; margin-top: -5px; color: #64748b; font-size: .68rem; }
    .records-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
    .records-toolbar label { display: flex; flex-direction: column; gap: 6px; color: #475569; font-size: .75rem; font-weight: 700; }
    .records-toolbar select { min-width: 200px; }
    .actions { display: flex; gap: 9px; margin-top: 16px; }
    .actions button, .home-row button { border: 0; border-radius: 7px; padding: 9px 13px; cursor: pointer; font-weight: 700; }
    #save { background: #1f5fcf; color: #fff; }
    #clear { background: #e2e8f0; color: #334155; }
    .home-row { display: grid; grid-template-columns: 82px minmax(0,1fr) auto; gap: 13px; align-items: center; padding: 12px 0; border-bottom: 1px solid #e2e8f0; }
    .home-row img { width: 82px; height: 58px; object-fit: cover; border-radius: 7px; background: #eef3f8; }
    .home-row strong { display: block; color: #1e293b; }
    .home-row small { color: #64748b; }
    .home-row button { background: #e8eef8; color: #0f2a6b; margin-left: 5px; }
    .status { color: #64748b; padding: 14px 0; font-weight: 700; }
    @media (max-width: 850px) { .home-grid { grid-template-columns: 1fr; } .records-toolbar { display: block; } .records-toolbar select { width: 100%; } }

    .home-admin { max-width: 1280px; }
    .home-admin h1 { font-size: 1.9rem; font-weight: 800; letter-spacing: 0; }
    .home-admin > p { font-size: .9rem; }
    .home-grid { grid-template-columns: 335px minmax(0, 1fr); gap: 16px; align-items: start; }
    .home-form, .home-list { padding: 18px 20px; border-radius: 10px; box-shadow: 0 5px 18px rgba(15,42,107,.05); }
    .home-form h2, .home-list h2 { font-size: 1.05rem; margin-bottom: 16px; }
    .home-form label { font-size: .74rem; margin: 10px 0 5px; }
    .home-form input, .home-form textarea, .home-form select, .records-toolbar select { min-height: 38px; padding: 9px 10px; font-size: .84rem; }
    .home-form textarea { min-height: 82px; }
    .image-adjuster { margin-top: 10px; padding: 10px; border-radius: 8px; }
    .image-adjuster__preview { aspect-ratio: 16 / 8; margin-bottom: 0; }
    .image-adjuster__controls { display: grid; gap: 5px; }
    .image-adjuster__controls label { margin: 0; color: #334155; font-size: .68rem; }
    .image-adjuster__controls input[type=range] { width: 100%; min-height: 16px; padding: 0; }
    .image-position-scale { margin-top: -3px; font-size: .6rem; }
    .image-upload { position: relative; display: block; margin-top: 8px; border: 1px dashed #b9c9df; border-radius: 8px; background: #f7faff; padding: 12px; text-align: center; color: #46658e; cursor: pointer; }
    .image-upload strong { display: block; color: #1f5fcf; font-size: .78rem; }
    .image-upload small { display: block; margin-top: 3px; font-size: .68rem; }
    .image-upload input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .actions { align-items: center; justify-content: flex-end; gap: 8px; }
    .actions button { min-height: 36px; padding: 8px 13px; font-size: .78rem; }
    #preview-content { background: #fff; color: #1f5fcf; border: 1px solid #9bbcf4; }
    .records-toolbar { justify-content: flex-end; }
    .records-toolbar label { font-size: .72rem; }
    .records-toolbar select { min-width: 145px; }
    .add-slider { min-height: 36px; padding: 8px 12px; border: 1px solid #8db5f4; border-radius: 7px; background: #fff; color: #1f5fcf; font-size: .76rem; font-weight: 800; cursor: pointer; }
    .add-slider:hover { background: #edf4ff; }
    .records-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
    .records-header h2 { margin: 0; }
    .records-hint { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; padding: 9px 11px; border: 1px solid #d9e6fa; border-radius: 6px; background: #f1f6ff; color: #2457a6; font-size: .72rem; font-weight: 700; }
    .records-hint span { display: grid; place-items: center; width: 16px; height: 16px; border-radius: 50%; background: #1f5fcf; color: #fff; font-size: .65rem; }
    .home-table-wrap { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 8px; }
    .home-table { width: 100%; min-width: 680px; border-collapse: collapse; table-layout: fixed; }
    .home-table th { padding: 9px 10px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #64748b; text-align: left; font-size: .62rem; letter-spacing: .04em; text-transform: uppercase; }
    .home-table td { padding: 9px 10px; border-bottom: 1px solid #e2e8f0; color: #334155; font-size: .74rem; vertical-align: middle; }
    .home-table tr:last-child td { border-bottom: 0; }
    .home-table tr.is-dragging { opacity: .55; background: #edf4ff; }
    .home-table th:nth-child(1) { width: 7%; } .home-table th:nth-child(2) { width: 15%; } .home-table th:nth-child(3) { width: 34%; } .home-table th:nth-child(4) { width: 14%; } .home-table th:nth-child(5) { width: 14%; } .home-table th:nth-child(6) { width: 16%; }
    .drag-cell { color: #6d83a4; font-size: 1.1rem; cursor: grab; text-align: center; }
    .order-number { display: inline-grid; place-items: center; width: 27px; height: 27px; border-radius: 6px; background: #f1f5fa; color: #29466e; font-weight: 800; }
    .home-preview-thumb { width: 72px; height: 44px; display: block; object-fit: cover; border-radius: 6px; background: #eef3f8; }
    .home-title { display: block; color: #1e365c; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .home-subtitle { display: block; margin-top: 2px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .home-status { display: inline-flex; align-items: center; gap: 5px; padding: 5px 8px; border-radius: 5px; background: #e4f7ec; color: #137a48; font-size: .65rem; font-weight: 800; }
    .home-status:before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .home-status--draft { background: #fff2d5; color: #a96b00; }
    .home-date { color: #3d5477; white-space: nowrap; }
    .home-record-actions { display: flex; justify-content: flex-end; gap: 5px; }
    .home-record-actions button { display: inline-flex; align-items: center; justify-content: center; width: 32px; min-width: 32px; height: 30px; padding: 6px; border: 1px solid #bdd1f4; border-radius: 6px; background: #fff; color: #1f5fcf; font-size: 0; }
    .home-record-actions button svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .home-record-actions .delete { border-color: #f2bebe; color: #c33434; }
    .home-record-actions button:hover { background: #edf4ff; }
    .home-table-footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding-top: 12px; color: #64748b; font-size: .72rem; }
    .home-pagination { display: flex; gap: 5px; }
    .home-pagination button { width: 30px; height: 30px; border: 1px solid #d8e2ef; border-radius: 6px; background: #fff; color: #334155; cursor: pointer; font-weight: 700; }
    .home-pagination button.active, .home-pagination button:hover { background: #2f6cdf; border-color: #2f6cdf; color: #fff; }
    @media (max-width: 850px) { .home-grid { grid-template-columns: 1fr; } .records-header { align-items: flex-start; flex-direction: column; } .records-toolbar { width: 100%; justify-content: space-between; } }
    .home-workspace { display: block; }
    .home-list { min-height: 0; }
    .home-tabs { display: flex; gap: 4px; margin-bottom: 16px; padding: 4px; border: 1px solid #dfe7f0; border-radius: 8px; background: #fff; overflow-x: auto; }
    .home-tab { flex: 1 0 auto; border: 0; border-bottom: 2px solid transparent; border-radius: 5px; background: transparent; color: #465d7e; padding: 10px 14px; font-size: .76rem; font-weight: 700; cursor: pointer; white-space: nowrap; }
    .home-tab:hover, .home-tab.is-active { background: #edf4ff; border-bottom-color: #1f5fcf; color: #1f5fcf; }
    .home-editor-panel { position: fixed; z-index: 1100; inset: 0 0 0 auto; width: min(390px, 100%); overflow-y: auto; transform: translateX(100%); visibility: hidden; transition: transform .22s ease, visibility .22s ease; background: #f4f7fb; box-shadow: -8px 0 24px rgba(15,42,107,.14); }
    .home-editor-panel.is-open { transform: translateX(0); visibility: visible; }
    .home-editor-panel .home-form { min-height: 100vh; border: 0; border-radius: 0; box-shadow: none; padding: 24px; }
    .home-editor-panel .home-form h2:after { content: none; }
    .editor-close { float: right; border: 0; background: transparent; color: #64748b; font-size: 1.3rem; line-height: 1; cursor: pointer; }
    .editor-close:hover { color: #0f2a6b; }
    .home-editor-panel .home-form h2 { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .manual-order-field, #sort_order { display: none; }
    .toggle-field { display: flex !important; align-items: center; gap: 9px; }
    .toggle-field input { position: absolute; opacity: 0; }
    .toggle-ui { width: 34px; height: 20px; border-radius: 999px; background: #cbd5e1; position: relative; transition: background .18s ease; }
    .toggle-ui:after { content: ''; position: absolute; width: 16px; height: 16px; left: 2px; top: 2px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.2); transition: transform .18s ease; }
    .toggle-field input:checked + .toggle-ui { background: #1f5fcf; }
    .toggle-field input:checked + .toggle-ui:after { transform: translateX(14px); }
    .home-status--scheduled { background: #e8efff; color: #315da9; }
    .home-status--archived { background: #edf0f4; color: #657083; }
    .home-preview-thumb.is-broken { object-fit: contain; padding: 8px; }
    .home-list { width: 100%; box-sizing: border-box; padding: 20px; }
    .home-list .records-header { margin-bottom: 12px; }
    .home-list .records-toolbar { display: grid; grid-template-columns: minmax(220px, 1fr) repeat(2, minmax(130px, 160px)); align-items: end; justify-content: initial; gap: 8px; margin-bottom: 12px; }
    .home-list .records-toolbar label { display: flex; flex-direction: column; gap: 5px; color: #475569; font-size: .7rem; font-weight: 700; }
    .home-list .records-toolbar input, .home-list .records-toolbar select { width: 100%; min-height: 36px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 7px; padding: 8px 10px; background: #fff; color: #334155; font: inherit; }
    .home-list .records-toolbar .home-search { justify-content: flex-end; }
    .home-list .records-toolbar .home-search input { margin-top: 19px; }
    .home-list .records-header .add-slider { min-height: 36px; }
    .home-list .records-hint { margin-bottom: 10px; }
    @media (max-width: 850px) { .home-list .records-toolbar { grid-template-columns: 1fr 1fr; } .home-list .records-toolbar .home-search { grid-column: 1 / -1; } .home-list .records-toolbar .home-search input { margin-top: 0; } }
    @media (max-width: 560px) { .home-list .records-toolbar { grid-template-columns: 1fr; } .home-list .records-toolbar .home-search { grid-column: auto; } }
    @media (max-width: 850px) { .home-editor-panel { width: 100%; } }
  </style>
</head>
<body class="admin-dashboard-ref-body">
  <div class="admin-portal-shell">
    <?php include __DIR__ . '/admin-sidebar.php'; ?>
    <!-- sidebar replaced by shared include -->
    <!-- sidebar markup removed: using shared include above -->


    <main class="admin-main">
      <section class="home-admin">
        <h1>Home Management</h1>
        <p>Manage homepage slides, advisory, Central News, Provincial News, and What’s New content.</p>
        <nav class="home-tabs" aria-label="Homepage content sections">
          <button type="button" class="home-tab is-active" data-tab="slides">Homepage Slides</button>
          <button type="button" class="home-tab" data-tab="central">Central News</button>
          <button type="button" class="home-tab" data-tab="provincial">Provincial News</button>
          <button type="button" class="home-tab" data-tab="featured">What's New</button>
          <button type="button" class="home-tab" data-tab="ticker">Advisory</button>
        </nav>
        <div class="home-workspace">
          <section class="home-list">
            <div class="records-header">
              <h2>Homepage Records</h2>
              <button type="button" class="add-slider admin-add-button" id="add-slider">+ Add Home Content</button>
            </div>
            <div class="records-toolbar">
              <label class="home-search"><span class="sr-only">Search content</span><input id="records-search" type="search" placeholder="Search content..."></label>
              <label>Status<select id="records-filter"><option value="all">All Status</option><option value="published">Published</option><option value="draft">Draft</option><option value="scheduled">Scheduled</option><option value="archived">Archived</option></select></label>
              <label>Section<select id="records-section"><option value="all">All Sections</option><option value="slides" selected>Homepage Slides</option><option value="central">Central News</option><option value="provincial">Provincial News</option><option value="featured">What's New</option><option value="ticker">Advisory</option></select></label>
            </div>
            <div class="records-hint"><span>i</span> Drag and drop the rows to reorder the slides.</div>
            <div id="records"><div class="status">Loading...</div></div>
          </section>
          <aside class="home-editor-panel" id="home-editor-panel" aria-hidden="true">
          <form class="home-form" id="form" enctype="multipart/form-data">
            <h2><span id="form-title">Add Home Content</span><button type="button" class="editor-close" id="close-editor" aria-label="Close editor">×</button></h2>
            <input type="hidden" id="id" name="id">
            <label for="group">Content area</label>
            <select id="group" name="group">
              <option value="slides">Homepage Slider</option>
              <option value="ticker">Advisory Ticker</option>
              <option value="central">Central News</option>
              <option value="provincial">Provincial News</option>
              <option value="featured">What's New</option>
            </select>
            <label for="title">Title</label>
            <input id="title" name="title" required>
            <label for="date">Date</label>
            <input id="date" name="date" placeholder="August 24, 2026">
            <label for="description">Description</label>
            <textarea id="description" name="description"></textarea>
            <label for="image_url">Image URL (optional)</label>
            <input id="image_url" name="image_url" placeholder="Logos/example.jpg or https://...">
            <div class="image-adjuster" aria-label="Image adjustment controls">
              <div class="image-adjuster__preview">
                <img id="home-image-preview" alt="Selected file preview" src="">
              </div>
              <div class="image-adjuster__controls">
                <label for="home-image-horizontal">Horizontal Position <output id="home-image-horizontal-value">50%</output></label>
                <input id="home-image-horizontal" type="range" min="0" max="100" step="1" value="50">
                <div class="image-position-scale"><span>Left 0%</span><span>Right 100%</span></div>
                <label for="home-image-vertical">Vertical Position <output id="home-image-vertical-value">50%</output></label>
                <input id="home-image-vertical" type="range" min="0" max="100" step="1" value="50">
                <div class="image-position-scale"><span>Top 0%</span><span>Bottom 100%</span></div>
              </div>
            </div>
            <label for="image">Image</label>
            <label class="image-upload">
              <strong>Click to upload or drag and drop</strong>
              <small>JPG, PNG, WebP · Max 8MB</small>
              <input id="image" name="image" type="file" accept="image/*">
            </label>
            <input id="image-position-x" name="image_position_x" type="hidden" value="50">
            <input id="image-position-y" name="image_position_y" type="hidden" value="50">
            <label for="link_url">Link URL (optional)</label>
            <input id="link_url" name="link_url" type="url">
            <label for="content-status">Status</label>
            <select id="content-status" name="status"><option value="published">Published</option><option value="draft">Draft</option><option value="scheduled">Scheduled</option><option value="archived">Archived</option></select>
            <label class="manual-order-field" for="sort_order">Display order</label>
            <input id="sort_order" name="sort_order" type="number" min="1" value="1">
            <label class="toggle-field"><input id="active" name="is_active" type="checkbox" checked><span class="toggle-ui"></span><span>Show on homepage</span></label>
            <div class="actions">
              <button id="clear" type="button">Cancel</button>
              <button id="save" type="submit">Save Content</button>
              <button id="preview-content" type="button">Preview</button>
            </div>
            <div class="status" id="status" role="status"></div>
          </form>
          </aside>
        </div>
      </section>
    </main>
  </div>

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

  <script>
    (function () {
      var api = 'auth/home_api.php';
      var form = document.getElementById('form');
      var root = document.getElementById('records');
      var status = document.getElementById('status');
      var group = document.getElementById('group');
      var recordsFilter = document.getElementById('records-filter');
      var recordsSection = document.getElementById('records-section');
      var recordsSearch = document.getElementById('records-search');
      var contentStatus = document.getElementById('content-status');
      var homePreview = document.getElementById('home-image-preview');
      var homeHorizontal = document.getElementById('home-image-horizontal');
      var homeVertical = document.getElementById('home-image-vertical');
      var homeHorizontalValue = document.getElementById('home-image-horizontal-value');
      var homeVerticalValue = document.getElementById('home-image-vertical-value');
      var homePositionX = document.getElementById('image-position-x');
      var homePositionY = document.getElementById('image-position-y');
      var pendingHomeImage = null;
      var currentRows = [];
      var recordsData = {};
      var currentPage = 1;
      var pageSize = 6;

      function esc(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
          return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
      }

      function load() {
        fetch(api)
          .then(function (response) { return response.json(); })
          .then(render)
          .catch(function () {
            root.innerHTML = '<div class="status">Unable to load homepage records.</div>';
          });
      }

      function render(data) {
        recordsData = data || {};
        var rows = [];
        Object.keys(recordsData).forEach(function (key) {
          (recordsData[key] || []).forEach(function (item) {
            item._group = key;
            rows.push(item);
          });
        });

        var selectedStatus = recordsFilter ? recordsFilter.value : 'all';
        var selectedSection = recordsSection ? recordsSection.value : 'all';
        var searchTerm = recordsSearch ? recordsSearch.value.trim().toLowerCase() : '';
        if (selectedStatus !== 'all') {
          rows = rows.filter(function (row) {
            return selectedStatus === (row.is_active ? 'published' : 'draft');
          });
        }
        if (selectedSection !== 'all') rows = rows.filter(function (row) { return row._group === selectedSection; });
        if (searchTerm) rows = rows.filter(function (row) {
          var searchableText = [row.title, row.description, row.date, row.image_url, row.link_url, row._group].filter(Boolean).join(' ').toLowerCase();
          return searchableText.indexOf(searchTerm) !== -1;
        });

        if (!rows.length) {
          root.innerHTML = '<div class="status">No records in this category.</div>';
          return;
        }

        currentRows = rows.sort(function (a, b) {
          return Number(a.sort_order || 0) - Number(b.sort_order || 0);
        });
        var pageCount = Math.max(1, Math.ceil(currentRows.length / pageSize));
        currentPage = Math.min(currentPage, pageCount);
        var firstRecord = (currentPage - 1) * pageSize;
        var visibleRows = currentRows.slice(firstRecord, firstRecord + pageSize);
        root.innerHTML = '<div class="home-table-wrap"><table class="home-table"><thead><tr><th>Order</th><th>Preview</th><th>Title</th><th>Status</th><th>Date Added</th><th>Actions</th></tr></thead><tbody>' + visibleRows.map(function (row, index) {
          var image = row.image_url || '';
          var statusLabel = row.status || (row.is_active ? 'Published' : 'Draft');
          var statusClass = statusLabel === 'Published' ? '' : ' home-status--' + statusLabel.toLowerCase();
          return '<tr draggable="true" data-row-index="' + index + '">' +
            '<td class="drag-cell" title="Drag to reorder">⋮⋮ <span class="order-number">' + (firstRecord + index + 1) + '</span></td>' +
            '<td><img class="home-preview-thumb" src="' + esc(image) + '" alt="" onerror="this.onerror=null;this.classList.add(\'is-broken\');this.src=\'Logos/Department_of_the_Interior_and_Local_Government_(DILG)_Seal_-_Logo.svg\';"></td>' +
            '<td><span class="home-title">' + esc(row.title) + '</span><span class="home-subtitle">' + esc(row._group) + '</span></td>' +
            '<td><span class="home-status' + statusClass + '">' + esc(statusLabel) + '</span></td>' +
            '<td><span class="home-date">' + esc(row.date || 'Aug 24, 2026') + '</span></td>' +
            '<td><div class="home-record-actions"><button type="button" data-edit="' + row._group + ':' + row.id + '" aria-label="Edit home content" title="Edit"><svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg></button><button type="button" class="delete" data-delete="' + row._group + ':' + row.id + '" aria-label="Delete home content" title="Delete"><svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg></button></div></td>' +
            '</tr>';
        }).join('') + '</tbody></table></div><div class="home-table-footer"><span>Showing ' + (firstRecord + 1) + ' to ' + Math.min(firstRecord + pageSize, currentRows.length) + ' of ' + currentRows.length + ' entries</span><div class="home-pagination"><button type="button" data-page="prev">‹</button>' + Array.from({ length: pageCount }, function (_, pageIndex) { return '<button type="button" data-page="' + (pageIndex + 1) + '" class="' + (pageIndex + 1 === currentPage ? 'active' : '') + '">' + (pageIndex + 1) + '</button>'; }).join('') + '<button type="button" data-page="next">›</button></div></div>';
      }

      function updateHomePreviewTransform() {
        if (!homePreview) return;
        var horizontal = Number(homeHorizontal && homeHorizontal.value ? homeHorizontal.value : 50);
        var vertical = Number(homeVertical && homeVertical.value ? homeVertical.value : 50);
        homePreview.style.objectPosition = horizontal + '% ' + vertical + '%';
        homePreview.style.transform = 'scale(1.12) translate(' + ((horizontal - 50) * -0.16) + '%, ' + ((vertical - 50) * -0.16) + '%)';
        if (homeHorizontalValue) homeHorizontalValue.textContent = horizontal + '%';
        if (homeVerticalValue) homeVerticalValue.textContent = vertical + '%';
        if (homePositionX) homePositionX.value = horizontal;
        if (homePositionY) homePositionY.value = vertical;
      }

      function buildAdjustedImageFile(file) {
        // Keep the original file intact; alignment is stored as metadata and applied at render time.
        return Promise.resolve(file);
      }

      function clearForm() {
        form.reset();
        document.getElementById('id').value = '';
        document.getElementById('form-title').textContent = 'Add Home Content';
        document.getElementById('active').checked = true;
        if (homePreview) { homePreview.style.display = 'none'; homePreview.removeAttribute('src'); }
        status.textContent = '';
        document.getElementById('home-editor-panel').classList.remove('is-open');
        document.getElementById('home-editor-panel').setAttribute('aria-hidden', 'true');
      }

      function openEditor() {
        document.getElementById('home-editor-panel').classList.add('is-open');
        document.getElementById('home-editor-panel').setAttribute('aria-hidden', 'false');
      }

      root.addEventListener('click', function (event) {
        var pageButton = event.target.closest('[data-page]');
        if (pageButton) {
          var pageValue = pageButton.getAttribute('data-page');
          var pageCount = Math.max(1, Math.ceil(currentRows.length / pageSize));
          if (pageValue === 'prev') currentPage = Math.max(1, currentPage - 1);
          else if (pageValue === 'next') currentPage = Math.min(pageCount, currentPage + 1);
          else currentPage = Number(pageValue) || 1;
          render(currentRows.reduce(function (groups, row) {
            (groups[row._group] || (groups[row._group] = [])).push(row);
            return groups;
          }, {}));
          return;
        }
        var deleteKey = event.target.getAttribute('data-delete');
        if (deleteKey) {
          var deleteParts = deleteKey.split(':');
          if (!window.confirm('Delete this homepage record?')) return;
          var deletePayload = new FormData();
          deletePayload.append('action', 'delete');
          deletePayload.append('group', deleteParts[0]);
          deletePayload.append('id', deleteParts[1]);
          fetch(api, { method: 'POST', body: deletePayload }).then(function () { load(); });
          return;
        }
        if (event.target.closest('.home-tab')) return;
        var key = event.target.getAttribute('data-edit');
        if (!key) return;

        var parts = key.split(':');
        var currentGroup = parts[0];
        var id = parts[1];

        fetch(api)
          .then(function (response) { return response.json(); })
          .then(function (data) {
            var row = (data[currentGroup] || []).find(function (item) { return String(item.id) === id; });
            if (!row) return;
            group.value = currentGroup;
            ['title', 'date', 'description', 'image_url', 'link_url', 'sort_order'].forEach(function (field) {
              document.getElementById(field).value = row[field] || '';
            });
            if (homePreview) {
              homePreview.style.display = row.image_url ? 'block' : 'none';
              homePreview.src = row.image_url || '';
              homePreview.onload = function () { homePreview.style.display = 'block'; };
              homePreview.onerror = function () { homePreview.style.display = 'none'; homePreview.removeAttribute('src'); };
            }
            var savedX = row.image_position_x == null ? 50 : row.image_position_x;
            var savedY = row.image_position_y == null ? 50 : row.image_position_y;
            if (homePositionX) homePositionX.value = savedX;
            if (homePositionY) homePositionY.value = savedY;
            if (homeHorizontal) homeHorizontal.value = savedX;
            if (homeVertical) homeVertical.value = savedY;
            updateHomePreviewTransform();
            document.getElementById('id').value = row.id;
            document.getElementById('active').checked = !!row.is_active;
            if (contentStatus) contentStatus.value = row.status || (row.is_active ? 'published' : 'draft');
            document.getElementById('form-title').textContent = 'Edit Home Content';
            openEditor();
            window.scrollTo({ top: 0, behavior: 'smooth' });
          });
      });

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (contentStatus) document.getElementById('active').checked = contentStatus.value === 'published';
        var formData = new FormData(form);
        var fileInput = document.getElementById('image');
        var selectedFile = fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        formData.append('action', document.getElementById('id').value ? 'update' : 'create');

        Promise.resolve()
          .then(function () {
            if (!selectedFile) return formData;
            return buildAdjustedImageFile(selectedFile).then(function () { return formData; });
          })
          .then(function (payload) {
            return fetch(api, { method: 'POST', body: payload });
          })
          .then(function (response) { return response.json(); })
          .then(function (result) {
            if (result.error) throw new Error(result.error);
            status.textContent = 'Saved.';
            clearForm();
            load();
          })
          .catch(function (error) {
            status.textContent = error.message || 'Unable to save.';
          });
      });

      if (recordsFilter) {
        recordsFilter.addEventListener('change', function () { currentPage = 1; render(recordsData); });
      }

      if (homeHorizontal) homeHorizontal.addEventListener('input', updateHomePreviewTransform);
      if (homeVertical) homeVertical.addEventListener('input', updateHomePreviewTransform);

      if (document.getElementById('image')) {
        document.getElementById('image').addEventListener('change', function (event) {
          var file = event.target.files && event.target.files[0];
          if (!file) return;
          pendingHomeImage = file;
          var reader = new FileReader();
          reader.onload = function (loadEvent) {
            if (homePreview) {
              homePreview.style.display = 'block';
              homePreview.src = loadEvent.target.result;
              homePreview.onload = function () { homePreview.style.display = 'block'; };
              document.getElementById('image_url').value = '';
              updateHomePreviewTransform();
            }
          };
          reader.readAsDataURL(file);
        });
      }

      if (document.getElementById('image_url')) {
        document.getElementById('image_url').addEventListener('input', function (event) {
          var value = event.target.value.trim();
          if (homePreview && value) {
            homePreview.style.display = 'block';
            homePreview.src = value;
            homePreview.onload = function () { homePreview.style.display = 'block'; };
            homePreview.onerror = function () { homePreview.style.display = 'none'; homePreview.removeAttribute('src'); };
            updateHomePreviewTransform();
          }
        });
      }

      document.getElementById('clear').addEventListener('click', clearForm);

      document.getElementById('preview-content').addEventListener('click', function () {
        var url = document.getElementById('link_url').value.trim() || document.getElementById('image_url').value.trim();
        if (url) window.open(url, '_blank', 'noopener');
        else if (homePreview && homePreview.src) window.open(homePreview.src, '_blank', 'noopener');
      });

      document.getElementById('add-slider').addEventListener('click', function () {
        clearForm();
        group.value = 'slides';
        openEditor();
        document.getElementById('title').focus();
      });
      document.getElementById('close-editor').addEventListener('click', clearForm);

      document.querySelectorAll('.home-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
          document.querySelectorAll('.home-tab').forEach(function (item) { item.classList.remove('is-active'); });
          tab.classList.add('is-active');
          recordsSection.value = tab.getAttribute('data-tab');
          currentPage = 1;
          load();
        });
      });
      if (recordsSection) recordsSection.addEventListener('change', function () { currentPage = 1; render(recordsData); });
      if (recordsSearch) recordsSearch.addEventListener('input', function () {
        currentPage = 1;
        render(recordsData);
      });

      document.getElementById('logout-btn').addEventListener('click', function (event) {
        event.preventDefault();
        fetch('auth/logout.php', { method: 'POST' }).finally(function () {
          location.href = 'index.html';
        });
      });

      root.addEventListener('dragstart', function (event) {
        var row = event.target.closest('tr[draggable]');
        if (row) row.classList.add('is-dragging');
      });

      root.addEventListener('dragend', function (event) {
        var row = event.target.closest('tr[draggable]');
        if (row) row.classList.remove('is-dragging');
      });

      var settingsModal = document.getElementById('settings-modal');
      function openSettingsModal() {
        settingsModal.classList.add('is-open');
        settingsModal.setAttribute('aria-hidden', 'false');
      }
      function closeSettingsModal() {
        settingsModal.classList.remove('is-open');
        settingsModal.setAttribute('aria-hidden', 'true');
      }
      document.querySelectorAll('[data-open-settings]').forEach(function (button) {
        button.addEventListener('click', openSettingsModal);
      });
      document.querySelectorAll('[data-close-settings]').forEach(function (button) {
        button.addEventListener('click', closeSettingsModal);
      });
      settingsModal.addEventListener('click', function (event) {
        if (event.target === settingsModal || event.target.classList.contains('settings-modal__backdrop')) {
          closeSettingsModal();
        }
      });
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && settingsModal.classList.contains('is-open')) {
          closeSettingsModal();
        }
      });
      var savedSettings = JSON.parse(localStorage.getItem('dilgSystemSettings') || '{}');
      var settingsForm = document.getElementById('system-settings-form');
      if (savedSettings.websiteTitle) settingsForm.websiteTitle.value = savedSettings.websiteTitle;
      if (savedSettings.contactEmail) settingsForm.contactEmail.value = savedSettings.contactEmail;
      if (savedSettings.timezone) settingsForm.timezone.value = savedSettings.timezone;
      if (savedSettings.maintenanceMode) settingsForm.maintenanceMode.value = savedSettings.maintenanceMode;
      if (savedSettings.publishState) settingsForm.publishState.value = savedSettings.publishState;
      settingsForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var values = Object.fromEntries(new FormData(settingsForm).entries());
        localStorage.setItem('dilgSystemSettings', JSON.stringify(values));
        document.getElementById('settings-status').textContent = 'Settings saved successfully.';
        closeSettingsModal();
      });

      load();
    }());
  </script>
</body>
</html>

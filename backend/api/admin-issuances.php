<?php
require_once __DIR__ . '/../auth/middleware.php';
require_role('ADMIN');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Issuances Management - DILG SDS</title>
  <link rel="stylesheet" href="styles.css?v=20260831-issuances-dashboard">
  <style>
    .page-content-admin {
      width: 100%;
      max-width: 1360px;
      margin: 0 auto;
      padding: 0;
    }

    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin: 0 0 24px;
    }

    .page-header h1 {
      margin: 0 0 6px;
      color: #0f2a6b;
      font-size: clamp(2rem, 2vw, 2.4rem);
      line-height: 1.15;
      font-weight: 800;
      letter-spacing: -0.03em;
    }

    .page-header p {
      margin: 0;
      color: #64748b;
      font-size: 1rem;
    }

    .add-record-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      border: 0;
      border-radius: 10px;
      background: linear-gradient(135deg, #1f5fcf 0%, #174da8 100%);
      color: #fff;
      min-height: 44px;
      padding: 0 18px;
      font-size: 0.92rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 8px 18px rgba(31, 95, 207, 0.22);
      transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .add-record-button:hover,
    .add-record-button:focus-visible {
      transform: translateY(-1px);
      box-shadow: 0 12px 20px rgba(31, 95, 207, 0.24);
      outline: none;
    }

    .content-management-layout {
      display: grid;
      grid-template-columns: minmax(0, 1fr);
      gap: 22px;
      align-items: start;
    }

    .records-card,
    .editor-card {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 18px;
      box-shadow: 0 12px 28px rgba(15, 42, 107, 0.06);
      overflow: hidden;
    }

    .records-card {
      padding: 0;
    }

    .records-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      padding: 18px 20px 14px;
      border-bottom: 1px solid #edf2f7;
      background: #f8fbff;
    }

    .records-heading {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }

    .records-icon {
      width: 40px;
      height: 40px;
      display: grid;
      place-items: center;
      border-radius: 12px;
      background: rgba(31, 95, 207, 0.08);
      color: #1f5fcf;
      flex-shrink: 0;
    }

    .records-icon svg {
      width: 20px;
      height: 20px;
      fill: none;
      stroke: currentColor;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .records-heading h2 {
      margin: 0;
      color: #0f2a6b;
      font-size: 1.1rem;
      font-weight: 800;
    }

    .records-heading p {
      margin: 4px 0 0;
      color: #64748b;
      font-size: 0.86rem;
    }

    .records-tools {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
      flex: 1 1 440px;
      justify-content: flex-end;
      min-width: 0;
    }

    .search-field,
    .filter-field {
      position: relative;
      display: flex;
      align-items: center;
      background: #fff;
      border: 1px solid #dfe7f0;
      border-radius: 10px;
      min-height: 40px;
      overflow: hidden;
    }

    .search-field {
      width: min(280px, 100%);
      flex: 1 1 240px;
      min-width: 0;
    }

    .search-field svg {
      width: 16px;
      height: 16px;
      margin-left: 12px;
      stroke: #6b7280;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
      flex-shrink: 0;
    }

    .search-field input,
    .filter-field select,
    .editor-form input,
    .editor-form textarea,
    .editor-form select {
      width: 100%;
      border: 0;
      background: transparent;
      color: #0f172a;
      font: inherit;
      box-sizing: border-box;
    }

    .search-field input {
      padding: 0 12px 0 8px;
      min-height: 40px;
      outline: none;
      font-size: 0.9rem;
    }

    .filter-field {
      min-width: 150px;
      flex: 0 1 150px;
    }

    .filter-field select {
      min-height: 40px;
      padding: 0 35px 0 12px;
      appearance: none;
      background-image: linear-gradient(45deg, transparent 50%, #64748b 50%), linear-gradient(135deg, #64748b 50%, transparent 50%);
      background-position: calc(100% - 18px) calc(50% - 2px), calc(100% - 12px) calc(50% - 2px);
      background-size: 6px 6px, 6px 6px;
      background-repeat: no-repeat;
      font-size: 0.88rem;
      color: #334155;
      outline: none;
    }

    .records-table-wrap {
      overflow-x: auto;
      background: #fff;
    }

    .records-table {
      width: 100%;
      border-collapse: collapse;
      min-width: 760px;
    }

    .records-table thead th {
      text-align: left;
      padding: 12px 18px;
      background: #f8fafc;
      color: #64748b;
      font-size: 0.72rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      font-weight: 800;
    }

    .records-table tbody td {
      padding: 18px;
      border-top: 1px solid #edf2f7;
      color: #334155;
      font-size: 0.92rem;
      vertical-align: middle;
      background: #fff;
    }

    .records-table tbody tr:hover td {
      background: #f8fbff;
    }

    .record-title {
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 3px;
    }

    .record-subtitle {
      color: #64748b;
      font-size: 0.8rem;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.01em;
    }

    .status-badge::before {
      content: "";
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: currentColor;
    }

    .status-published {
      background: #e8f8ed;
      color: #137a48;
    }

    .status-draft {
      background: #fff3d9;
      color: #ac6b00;
    }

    .updated-label {
      color: #334155;
      font-weight: 600;
    }

    .row-actions {
      display: flex;
      align-items: center;
      gap: 8px;
      justify-content: flex-start;
    }

    .icon-button {
      width: 34px;
      height: 34px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid #dfe7f0;
      border-radius: 8px;
      background: #fff;
      color: #1f5fcf;
      cursor: pointer;
      transition: background 0.18s ease, transform 0.18s ease, border-color 0.18s ease;
    }

    .icon-button:hover,
    .icon-button:focus-visible {
      background: #edf4ff;
      border-color: #bfd4ff;
      transform: translateY(-1px);
      outline: none;
    }

    .icon-button.delete {
      color: #b42318;
      border-color: #f2d8d8;
    }

    .icon-button.delete:hover,
    .icon-button.delete:focus-visible {
      background: #fff1f0;
      border-color: #f1bdbd;
    }

    .icon-button svg {
      width: 15px;
      height: 15px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .records-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 16px 20px 18px;
      border-top: 1px solid #edf2f7;
      background: #fff;
      color: #64748b;
      font-size: 0.82rem;
    }

    .pagination {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 6px;
    }

    .pagination button {
      border: 1px solid #dfe7f0;
      border-radius: 8px;
      background: #fff;
      color: #334155;
      min-width: 32px;
      height: 32px;
      padding: 0 8px;
      cursor: pointer;
      font-weight: 700;
    }

    .pagination button.active,
    .pagination button:hover {
      background: #1f5fcf;
      border-color: #1f5fcf;
      color: #fff;
    }

    .editor-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(15, 42, 107, 0.26);
      backdrop-filter: blur(2px);
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.2s ease, visibility 0.2s ease;
      z-index: 1090;
    }

    .editor-backdrop.is-open {
      opacity: 1;
      visibility: visible;
    }

    .editor-panel {
      position: fixed;
      top: 0;
      right: 0;
      width: min(420px, 100%);
      height: 100vh;
      background: #f4f7fb;
      box-shadow: -8px 0 24px rgba(15, 42, 107, 0.14);
      transform: translateX(100%);
      visibility: hidden;
      transition: transform 0.22s ease, visibility 0.22s ease;
      z-index: 1100;
      overflow-y: auto;
    }

    .editor-panel.is-open {
      transform: translateX(0);
      visibility: visible;
    }

    .editor-card {
      min-height: 100vh;
      border: 0;
      border-radius: 0;
      box-shadow: none;
      background: #f4f7fb;
    }

    .editor-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 24px 24px 18px;
      border-bottom: 1px solid #e2e8f0;
      background: #fff;
    }

    .editor-header span {
      display: block;
      color: #1f5fcf;
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
    }

    .editor-header h2 {
      margin: 4px 0 0;
      color: #0f2a6b;
      font-size: 1.35rem;
      font-weight: 800;
    }

    .close-button {
      width: 34px;
      height: 34px;
      border: 1px solid #dfe7f0;
      border-radius: 10px;
      background: #fff;
      color: #334155;
      font-size: 1.3rem;
      line-height: 1;
      cursor: pointer;
    }

    .editor-form {
      padding: 20px 24px 32px;
      display: grid;
      gap: 14px;
    }

    .editor-form label {
      display: grid;
      gap: 7px;
      color: #334155;
      font-size: 0.76rem;
      font-weight: 700;
    }

    .editor-form input,
    .editor-form textarea,
    .editor-form select {
      width: 100%;
      box-sizing: border-box;
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      padding: 11px 12px;
      background: #fff;
      font: inherit;
      color: #0f172a;
      outline: none;
    }

    .editor-form textarea {
      min-height: 110px;
      resize: vertical;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }

    .toggle-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 8px 0 2px;
    }

    .switch {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-weight: 700;
      color: #334155;
      cursor: pointer;
    }

    .switch input {
      display: none;
    }

    .switch-indicator {
      position: relative;
      width: 42px;
      height: 24px;
      border-radius: 999px;
      background: #cbd5e1;
      transition: background 0.18s ease;
    }

    .switch-indicator::after {
      content: "";
      position: absolute;
      left: 3px;
      top: 3px;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: #fff;
      box-shadow: 0 2px 5px rgba(15, 42, 107, 0.12);
      transition: transform 0.18s ease;
    }

    .switch input:checked + .switch-indicator {
      background: #1f5fcf;
    }

    .switch input:checked + .switch-indicator::after {
      transform: translateX(18px);
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 6px;
    }

    .secondary-button,
    .primary-button {
      border: 0;
      border-radius: 10px;
      min-height: 42px;
      padding: 0 16px;
      cursor: pointer;
      font-size: 0.9rem;
      font-weight: 800;
    }

    .secondary-button {
      background: #e2e8f0;
      color: #334155;
    }

    .primary-button {
      background: linear-gradient(135deg, #1f5fcf 0%, #174da8 100%);
      color: #fff;
      box-shadow: 0 8px 18px rgba(31, 95, 207, 0.2);
    }

    .form-status {
      min-height: 20px;
      color: #0f766e;
      font-size: 0.8rem;
      font-weight: 700;
    }

    .empty-state {
      text-align: center;
      color: #64748b;
      padding: 28px 16px;
      font-weight: 700;
    }

    .category-tabs {
      display: flex;
      gap: 4px;
      margin-bottom: 16px;
      padding: 4px;
      border: 1px solid #dfe7f0;
      border-radius: 8px;
      background: #fff;
      overflow-x: auto;
    }

    .category-tab {
      flex: 1 0 auto;
      border: 0;
      border-bottom: 2px solid transparent;
      border-radius: 5px;
      background: transparent;
      color: #465d7e;
      padding: 10px 14px;
      font-size: 0.76rem;
      font-weight: 700;
      cursor: pointer;
      white-space: nowrap;
      transition: all 0.18s ease;
    }

    .category-tab:hover {
      background: #edf4ff;
    }

    .category-tab.is-active {
      background: #edf4ff;
      border-bottom-color: #1f5fcf;
      color: #1f5fcf;
    }

    @media (max-width: 780px) {
      .page-header,
      .records-toolbar,
      .records-footer {
        flex-direction: column;
        align-items: flex-start;
      }

      .records-tools {
        width: 100%;
        flex-basis: 100%;
        justify-content: stretch;
      }

      .search-field,
      .filter-field {
        flex: 1 1 100%;
        width: 100%;
      }

      .form-grid {
        grid-template-columns: 1fr;
      }

      .category-tabs {
        width: 100%;
      }

      .category-tab {
        flex: 0 1 auto;
        padding: 9px 12px;
        font-size: 0.72rem;
      }
    }
  </style>
</head>
<body>
  <div class="admin-portal-shell">
    <?php include __DIR__ . '/admin-sidebar.php'; ?>

    <main class="admin-main">
      <section class="page-content-admin">
        <div class="page-header">
          <div>
            <h1>Issuances Management</h1>
            <p>Manage public issuances, circulars, and administrative memos for the website.</p>
          </div>
          <button type="button" class="add-record-button" id="add-record-button">+ Add New Content</button>
        </div>

        <nav class="category-tabs" aria-label="Issuance categories">
          <button type="button" class="category-tab is-active" data-category="joint-circulars">Joint Circulars</button>
          <button type="button" class="category-tab" data-category="memo-circulars">Memo Circulars</button>
          <button type="button" class="category-tab" data-category="presidential-directives">Presidential Directives</button>
          <button type="button" class="category-tab" data-category="republic-acts">Republic Acts</button>
        </nav>

        <div class="content-management-layout">
          <section class="content-list-panel">
            <div class="records-card">
              <div class="records-toolbar">
                <div class="records-heading">
                  <div class="records-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M7 4.5h7l5 5V18a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6.5a2 2 0 0 1 2-2z"/><path d="M14 4.5V10h5M9 13h6M9 17h6"/></svg>
                  </div>
                  <div>
                    <h2>Issuance Records</h2>
                    <p id="category-label">Published and draft entries</p>
                  </div>
                </div>

                <div class="records-tools">
                  <label class="search-field" aria-label="Search issuances">
                    <input id="issuance-search" type="search" placeholder="Search by title or reference">
                  </label>

                  <label class="filter-field" aria-label="Filter by status">
                    <select id="issuance-status-filter">
                      <option value="all">All Status</option>
                      <option value="published">Published</option>
                      <option value="draft">Draft</option>
                    </select>
                  </label>
                </div>
              </div>

              <div class="records-table-wrap">
                <table class="records-table" aria-live="polite">
                  <thead>
                    <tr>
                      <th>Title</th>
                      <th>Status</th>
                      <th>Last Updated</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody id="issuance-table-body">
                    <tr>
                      <td colspan="4" class="empty-state">Loading...</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="records-footer">
                <span id="records-summary">Showing 0 entries</span>
                <div class="pagination" id="records-pagination"></div>
              </div>
            </div>
          </section>
        </div>
      </section>
    </main>
  </div>

  <div class="editor-backdrop" id="editor-backdrop"></div>

  <aside class="editor-panel" id="issuance-editor-panel" aria-hidden="true">
    <div class="editor-card">
      <div class="editor-header">
        <div>
          <span>Issuances</span>
          <h2 id="editor-title">Add New Content</h2>
        </div>
        <button type="button" id="close-editor" class="close-button" aria-label="Close editor">×</button>
      </div>

      <form class="editor-form" id="issuance-form">
        <input type="hidden" id="issuance-id" name="id">

        <label for="issuance-category">
          Category
          <select id="issuance-category" name="category">
            <option value="joint-circulars">Joint Circulars</option>
            <option value="memo-circulars">Memo Circulars</option>
            <option value="presidential-directives">Presidential Directives</option>
            <option value="republic-acts">Republic Acts</option>
          </select>
        </label>

        <label for="issuance-title">
          Title
          <input id="issuance-title" name="title" required>
        </label>

        <div class="form-grid">
          <label for="issuance-number">
            Reference Number
            <input id="issuance-number" name="reference_number" placeholder="DILG-2026-001">
          </label>

          <label for="issuance-order">
            Display Order
            <input id="issuance-order" name="sort_order" type="number" min="1" value="1">
          </label>
        </div>

        <div class="form-grid">
          <label for="issuance-date">
            Issue Date
            <input id="issuance-date" name="issued_date" type="date">
          </label>

          <label for="issuance-status">
            Status
            <select id="issuance-status" name="is_active">
              <option value="1">Published</option>
              <option value="0">Draft</option>
            </select>
          </label>
        </div>

        <label for="issuance-link">
          Link URL
          <input id="issuance-link" name="link_url" type="url" placeholder="https://...">
        </label>

        <label for="issuance-summary">
          Summary
          <textarea id="issuance-summary" name="summary"></textarea>
        </label>

        <div class="toggle-row">
          <span>Visible on public page</span>
          <label class="switch" aria-label="Visible on public page">
            <input id="issuance-active" name="is_active_toggle" type="checkbox" checked>
            <span class="switch-indicator"></span>
          </label>
        </div>

        <div class="form-actions">
          <button type="button" class="secondary-button" id="clear-issuance">Clear</button>
          <button type="submit" class="primary-button" id="save-issuance">Save Content</button>
        </div>

        <div class="form-status" id="form-status" role="status"></div>
      </form>
    </div>
  </aside>

  <script>
    (function () {
      var api = 'auth/issuances_api.php';
      var form = document.getElementById('issuance-form');
      var tableBody = document.getElementById('issuance-table-body');
      var status = document.getElementById('form-status');
      var editorPanel = document.getElementById('issuance-editor-panel');
      var editorBackdrop = document.getElementById('editor-backdrop');
      var searchInput = document.getElementById('issuance-search');
      var filterSelect = document.getElementById('issuance-status-filter');
      var paginationNode = document.getElementById('records-pagination');
      var summaryNode = document.getElementById('records-summary');
      var categoryLabel = document.getElementById('category-label');
      var pageSize = 8;
      var currentPage = 1;
      var currentCategory = 'joint-circulars';
      var allRows = [];
      var categoryMap = {
        'joint-circulars': 'Joint Circulars',
        'memo-circulars': 'Memo Circulars',
        'presidential-directives': 'Presidential Directives',
        'republic-acts': 'Republic Acts'
      };

      function esc(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
          return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
      }

      function formatDate(value) {
        if (!value) return '—';
        var date = new Date(value);
        if (Number.isNaN(date.getTime())) {
          return value;
        }
        return date.toLocaleDateString('en-US', {
          month: 'short',
          day: 'numeric',
          year: 'numeric'
        });
      }

      function getStatusLabel(item) {
        return item && item.is_active ? 'Published' : 'Draft';
      }

      function getStatusClass(item) {
        return item && item.is_active ? 'status-published' : 'status-draft';
      }

      function renderRows(rows) {
        if (!rows.length) {
          tableBody.innerHTML = '<tr><td colspan="4" class="empty-state">No issuances matched your search.</td></tr>';
          summaryNode.textContent = 'Showing 0 entries';
          paginationNode.innerHTML = '';
          return;
        }

        var totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;

        var start = (currentPage - 1) * pageSize;
        var visible = rows.slice(start, start + pageSize);

        tableBody.innerHTML = visible.map(function (item) {
          var title = esc(item.title || 'Untitled issuance');
          var reference = esc(item.reference_number || 'No reference');
          var statusLabel = getStatusLabel(item);
          var statusClass = getStatusClass(item);
          var updated = formatDate(item.issued_date || item.updated_at || '');

          return '<tr>' +
            '<td><div class="record-title">' + title + '</div><div class="record-subtitle">' + reference + '</div></td>' +
            '<td><span class="status-badge ' + statusClass + '">' + statusLabel + '</span></td>' +
            '<td><span class="updated-label">' + updated + '</span></td>' +
            '<td>' +
              '<div class="row-actions">' +
                '<button class="icon-button" type="button" data-action="view" data-id="' + item.id + '" aria-label="View issuance">' +
                  '<svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>' +
                '</button>' +
                '<button class="icon-button" type="button" data-action="edit" data-id="' + item.id + '" aria-label="Edit issuance">' +
                  '<svg viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path></svg>' +
                '</button>' +
                '<button class="icon-button delete" type="button" data-action="delete" data-id="' + item.id + '" aria-label="Delete issuance">' +
                  '<svg viewBox="0 0 24 24"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6M14 11v6"></path></svg>' +
                '</button>' +
              '</div>' +
            '</td>' +
          '</tr>';
        }).join('');

        var startLabel = (rows.length === 0) ? 0 : start + 1;
        var endLabel = Math.min(start + visible.length, rows.length);
        summaryNode.textContent = 'Showing ' + startLabel + '-' + endLabel + ' of ' + rows.length + ' entries';

        var pageButtons = [];
        for (var i = 1; i <= totalPages; i += 1) {
          pageButtons.push('<button type="button" ' + (i === currentPage ? 'class="active"' : '') + ' data-page="' + i + '">' + i + '</button>');
        }
        paginationNode.innerHTML = pageButtons.join('');
      }

      function applyFilters() {
        var query = (searchInput.value || '').trim().toLowerCase();
        var statusFilter = filterSelect.value;

        var filtered = allRows.filter(function (item) {
          var category = item.category || 'joint-circulars';
          var matchesCategory = category === currentCategory;
          var matchesSearch = !query || (item.title || '').toLowerCase().includes(query) || (item.reference_number || '').toLowerCase().includes(query);
          var matchesStatus = statusFilter === 'all' || (statusFilter === 'published' && !!item.is_active) || (statusFilter === 'draft' && !item.is_active);
          return matchesCategory && matchesSearch && matchesStatus;
        });

        currentPage = 1;
        renderRows(filtered);
      }

      function openEditor(mode, item) {
        editorPanel.classList.add('is-open');
        editorBackdrop.classList.add('is-open');
        editorPanel.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        if (mode === 'add') {
          form.reset();
          document.getElementById('issuance-id').value = '';
          document.getElementById('issuance-category').value = currentCategory;
          document.getElementById('editor-title').textContent = 'Add New Content';
          document.getElementById('save-issuance').textContent = 'Save Content';
          document.getElementById('issuance-status').value = '1';
          document.getElementById('issuance-active').checked = true;
          status.textContent = '';
          return;
        }

        if (!item) return;

        document.getElementById('issuance-id').value = item.id;
        document.getElementById('issuance-category').value = item.category || 'joint-circulars';
        document.getElementById('issuance-title').value = item.title || '';
        document.getElementById('issuance-number').value = item.reference_number || '';
        document.getElementById('issuance-date').value = item.issued_date || '';
        document.getElementById('issuance-link').value = item.link_url || '';
        document.getElementById('issuance-summary').value = item.summary || '';
        document.getElementById('issuance-order').value = item.sort_order || 1;
        document.getElementById('issuance-status').value = item.is_active ? '1' : '0';
        document.getElementById('issuance-active').checked = !!item.is_active;
        document.getElementById('editor-title').textContent = mode === 'view' ? 'View Issuance' : 'Edit Issuance';
        document.getElementById('save-issuance').textContent = 'Save Changes';
        status.textContent = '';
      }

      function closeEditor() {
        editorPanel.classList.remove('is-open');
        editorBackdrop.classList.remove('is-open');
        editorPanel.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
      }

      function clearForm() {
        form.reset();
        document.getElementById('issuance-id').value = '';
        document.getElementById('issuance-status').value = '1';
        document.getElementById('issuance-active').checked = true;
        document.getElementById('issuance-order').value = 1;
        status.textContent = '';
      }

      function loadIssuances() {
        fetch(api)
          .then(function (response) { return response.json(); })
          .then(function (data) {
            allRows = Array.isArray(data.issuances) ? data.issuances : [];
            applyFilters();
          })
          .catch(function () {
            tableBody.innerHTML = '<tr><td colspan="4" class="empty-state">Unable to load issuances.</td></tr>';
            summaryNode.textContent = 'Showing 0 entries';
            paginationNode.innerHTML = '';
          });
      }

      function submitForm(event) {
        event.preventDefault();
        var formData = new FormData(form);
        var id = document.getElementById('issuance-id').value;
        var isChecked = document.getElementById('issuance-active').checked;
        var selectedStatus = document.getElementById('issuance-status').value;
        formData.append('action', id ? 'update' : 'create');
        formData.set('is_active', isChecked || selectedStatus === '1' ? '1' : '0');

        status.textContent = 'Saving...';

        fetch(api, {
          method: 'POST',
          body: formData
        })
          .then(function (response) { return response.json(); })
          .then(function (result) {
            if (result.error) throw new Error(result.error);
            status.textContent = 'Saved successfully.';
            form.reset();
            clearForm();
            closeEditor();
            loadIssuances();
          })
          .catch(function (error) {
            status.textContent = error.message || 'Unable to save issuance.';
          });
      }

      tableBody.addEventListener('click', function (event) {
        var button = event.target.closest('button[data-action]');
        if (!button) return;

        var action = button.getAttribute('data-action');
        var id = button.getAttribute('data-id');

        if (action === 'delete') {
          if (!confirm('Delete this issuance?')) return;
          fetch(api, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'delete', id: id })
          })
            .then(function () { return loadIssuances(); })
            .catch(function () {});
          return;
        }

        var item = allRows.find(function (row) {
          return String(row.id) === String(id);
        });
        if (!item) return;

        openEditor(action === 'view' ? 'view' : 'edit', item);
      });

      paginationNode.addEventListener('click', function (event) {
        var button = event.target.closest('button[data-page]');
        if (!button) return;
        currentPage = Number(button.getAttribute('data-page')) || 1;
        renderRows(allRows.filter(function (item) {
          var category = item.category || 'joint-circulars';
          var query = (searchInput.value || '').trim().toLowerCase();
          var statusFilter = filterSelect.value;
          var matchesCategory = category === currentCategory;
          var matchesSearch = !query || (item.title || '').toLowerCase().includes(query) || (item.reference_number || '').toLowerCase().includes(query);
          var matchesStatus = statusFilter === 'all' || (statusFilter === 'published' && !!item.is_active) || (statusFilter === 'draft' && !item.is_active);
          return matchesCategory && matchesSearch && matchesStatus;
        }));
      });

      searchInput.addEventListener('input', applyFilters);
      filterSelect.addEventListener('change', applyFilters);
      form.addEventListener('submit', submitForm);
      document.getElementById('add-record-button').addEventListener('click', function () { openEditor('add'); });
      document.getElementById('close-editor').addEventListener('click', closeEditor);
      editorBackdrop.addEventListener('click', closeEditor);
      document.getElementById('clear-issuance').addEventListener('click', function () {
        clearForm();
        closeEditor();
      });

      // Category tab switching
      var categoryTabs = document.querySelectorAll('.category-tab');
      categoryTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
          currentCategory = tab.getAttribute('data-category');
          categoryTabs.forEach(function (t) { t.classList.remove('is-active'); });
          tab.classList.add('is-active');
          categoryLabel.textContent = categoryMap[currentCategory] + ' • Published and draft entries';
          currentPage = 1;
          applyFilters();
        });
      });

      document.getElementById('issuance-active').addEventListener('change', function () {
        document.getElementById('issuance-status').value = this.checked ? '1' : '0';
      });

      document.getElementById('issuance-status').addEventListener('change', function () {
        document.getElementById('issuance-active').checked = this.value === '1';
      });

      (function () {
        var toggle = document.getElementById('sidebar-toggle');
        if (!toggle) return;
        var body = document.body;
        function apply(collapsed) {
          body.classList.toggle('sidebar-collapsed', collapsed);
          toggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
          toggle.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
          toggle.innerHTML = collapsed ? '<span>›</span>' : '<span>‹</span>';
        }
        try {
          apply(localStorage.getItem('dilg-admin-sidebar-collapsed') === '1');
        } catch (error) {
          apply(false);
        }
        toggle.addEventListener('click', function () {
          var collapsed = body.classList.toggle('sidebar-collapsed');
          try {
            localStorage.setItem('dilg-admin-sidebar-collapsed', collapsed ? '1' : '0');
          } catch (error) {}
          toggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
          toggle.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
          toggle.innerHTML = collapsed ? '<span>›</span>' : '<span>‹</span>';
        });
      }());

      loadIssuances();
    }());
  </script>
</body>
</html>


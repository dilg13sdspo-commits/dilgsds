<?php
require_once __DIR__ . '/../auth/middleware.php';
require_role('ADMIN');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Programs Management - DILG SDS</title>
  <link rel="stylesheet" href="styles.css?v=20260831-programs-redesign-3">
  <style>
    :root {
      --navy-900: #0f2a6b;
      --navy-800: #173d7a;
      --blue-600: #1f5fcf;
      --blue-500: #2f6cdf;
      --blue-100: #edf4ff;
      --slate-50: #f8fafc;
      --slate-100: #f1f5f9;
      --slate-200: #e2e8f0;
      --slate-300: #cbd5e1;
      --slate-500: #64748b;
      --slate-700: #334155;
      --success-100: #eafaf2;
      --success-500: #1d8d5d;
      --warning-100: #fff4dd;
      --warning-500: #b7791f;
      --danger-100: #ffe9e9;
      --danger-500: #c63d3d;
      --shadow-soft: 0 10px 24px rgba(15,42,107,.08);
    }

    body.admin-dashboard-ref-body {
      background: #f3f6fb;
      margin: 0;
    }

    .admin-main {
      background: #f3f6fb;
      min-height: calc(100vh - 72px);
    }

    .programs-admin {
      max-width: 1280px;
      width: 100%;
      margin: 0 auto;
      padding: 28px 24px 42px;
      box-sizing: border-box;
    }

    .programs-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 20px;
      margin-bottom: 20px;
    }

    .programs-header h1 {
      margin: 0;
      font-size: clamp(2rem, 2.4vw, 2.8rem);
      line-height: 1.1;
      color: var(--navy-900);
      letter-spacing: -0.04em;
    }

    .programs-header p {
      margin: 8px 0 0;
      color: var(--slate-500);
      font-size: .97rem;
    }

    .primary-btn,
    .secondary-btn,
    .icon-btn,
    .page-btn {
      border: 0;
      border-radius: 10px;
      cursor: pointer;
      font: inherit;
      transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease;
    }

    .primary-btn:hover,
    .secondary-btn:hover,
    .icon-btn:hover,
    .page-btn:hover {
      transform: translateY(-1px);
    }

    .primary-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 44px;
      padding: 0 18px;
      background: linear-gradient(180deg, #1f5fcf, #174ea9);
      color: #fff;
      font-weight: 700;
      box-shadow: 0 10px 20px rgba(31,95,207,.22);
    }

    .secondary-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 40px;
      padding: 0 16px;
      background: #edf2f8;
      color: var(--slate-700);
      font-weight: 700;
      border: 1px solid var(--slate-200);
    }

    .programs-table-card {
      background: #fff;
      border: 1px solid rgba(148,163,184,.22);
      border-radius: 18px;
      box-shadow: var(--shadow-soft);
      overflow: hidden;
    }

    .programs-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      padding: 22px 22px 16px;
      border-bottom: 1px solid var(--slate-200);
      background: linear-gradient(180deg, rgba(255,255,255,1), rgba(248,250,252,.86));
    }

    .records-title {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .records-title__icon {
      width: 30px;
      height: 30px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      background: #edf4ff;
      color: var(--blue-600);
      border: 1px solid rgba(31,95,207,.14);
    }

    .records-title__icon svg {
      width: 16px;
      height: 16px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .records-title h2 {
      margin: 0;
      font-size: 1.05rem;
      color: var(--navy-900);
      letter-spacing: -0.02em;
    }

    .programs-toolbar__right {
      min-width: min(100%, 330px);
      display: flex;
      justify-content: flex-end;
    }

    .search-wrap {
      position: relative;
      width: 100%;
      max-width: 320px;
    }

    .search-wrap svg {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      width: 16px;
      height: 16px;
      stroke: #7f8daa;
      fill: none;
      stroke-width: 2;
      pointer-events: none;
    }

    .search-wrap input {
      width: 100%;
      min-height: 40px;
      padding: 0 12px 0 38px;
      border-radius: 10px;
      border: 1px solid var(--slate-300);
      background: #fff;
      color: var(--slate-700);
      font: inherit;
      box-sizing: border-box;
    }

    .programs-filters {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
      padding: 18px 22px 0;
    }

    .programs-filters label {
      display: grid;
      gap: 7px;
      min-width: 0;
      color: var(--slate-700);
      font-size: .76rem;
      font-weight: 700;
    }

    .programs-filters select,
    .field input,
    .field select,
    .field textarea {
      min-height: 40px;
      padding: 0 12px;
      border-radius: 9px;
      border: 1px solid var(--slate-300);
      background: #fff;
      color: var(--slate-700);
      font: inherit;
      box-sizing: border-box;
    }

    .programs-filters select {
      width: 100%;
      min-width: 0;
    }

    .search-wrap input:focus,
    .programs-filters select:focus,
    .field input:focus,
    .field select:focus,
    .field textarea:focus {
      outline: 0;
      border-color: rgba(31,95,207,.4);
      box-shadow: 0 0 0 3px rgba(31,95,207,.12);
    }

    .program-table-wrap {
      padding: 18px 22px 0;
      overflow-x: auto;
    }

    .program-table {
      width: 100%;
      min-width: 920px;
      border-collapse: collapse;
      table-layout: fixed;
      border: 1px solid var(--slate-200);
      border-radius: 12px;
      overflow: hidden;
    }

    .program-table th,
    .program-table td {
      text-align: left;
      padding: 14px 12px;
      border-bottom: 1px solid var(--slate-200);
      vertical-align: middle;
    }

    .program-table th {
      background: var(--slate-50);
      color: var(--slate-500);
      text-transform: uppercase;
      letter-spacing: .05em;
      font-size: .7rem;
      font-weight: 800;
    }

    .program-table tbody tr {
      background: #fff;
    }

    .program-table tbody tr:hover {
      background: rgba(237,244,255,.45);
    }

    .program-table td {
      color: var(--slate-700);
      font-size: .87rem;
    }

    .program-title {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      min-width: 0;
    }

    .program-file-badge {
      width: 32px;
      height: 32px;
      min-width: 32px;
      border-radius: 8px;
      background: var(--blue-100);
      color: var(--blue-600);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid rgba(31,95,207,.12);
      margin-top: 2px;
    }

    .program-file-badge svg {
      width: 16px;
      height: 16px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .program-name {
      display: block;
      margin-bottom: 4px;
      color: #243a66;
      font-weight: 700;
      line-height: 1.3;
    }

    .program-doc-link {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      color: var(--blue-600);
      text-decoration: none;
      font-weight: 600;
      white-space: nowrap;
    }

    .program-doc-link:hover {
      text-decoration: underline;
    }

    .category-pill {
      display: inline-flex;
      align-items: center;
      padding: 5px 8px;
      border-radius: 999px;
      background: rgba(31,95,207,.08);
      border: 1px solid rgba(31,95,207,.12);
      color: #2d5ea7;
      font-size: .72rem;
      font-weight: 700;
      max-width: 100%;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 5px 9px;
      border-radius: 999px;
      font-size: .7rem;
      font-weight: 800;
      border: 1px solid transparent;
      letter-spacing: .01em;
    }

    .status-badge.published {
      background: var(--success-100);
      color: var(--success-500);
      border-color: rgba(29,141,93,.16);
    }

    .status-badge.draft {
      background: var(--warning-100);
      color: var(--warning-500);
      border-color: rgba(183,121,31,.14);
    }

    .sort-order {
      display: inline-flex;
      justify-content: center;
      align-items: center;
      min-width: 34px;
      height: 30px;
      border-radius: 7px;
      background: var(--slate-100);
      border: 1px solid var(--slate-200);
      color: var(--navy-900);
      font-weight: 700;
    }

    .last-updated {
      color: var(--slate-500);
      font-size: .8rem;
      white-space: nowrap;
    }

    .actions-cell {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 8px;
    }

    .icon-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 30px;
      height: 30px;
      border-radius: 8px;
      border: 1px solid rgba(148,163,184,.32);
      background: #fff;
      color: var(--navy-900);
    }

    .icon-btn svg {
      width: 14px;
      height: 14px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .icon-btn.edit {
      color: var(--blue-600);
      background: #edf4ff;
      border-color: rgba(31,95,207,.12);
    }

    .icon-btn.delete {
      color: var(--danger-500);
      background: #fff3f3;
      border-color: rgba(198,61,61,.12);
    }

    .program-table-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 16px 22px 20px;
      border-top: 1px solid var(--slate-200);
      color: var(--slate-500);
      font-size: .82rem;
    }

    .program-pagination {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .page-btn {
      min-width: 32px;
      height: 32px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      border: 1px solid var(--slate-200);
      background: #fff;
      color: var(--slate-700);
      font-weight: 700;
    }

    .page-btn.active {
      background: var(--blue-600);
      border-color: var(--blue-600);
      color: #fff;
      box-shadow: 0 8px 18px rgba(31,95,207,.18);
    }

    .page-btn.nav {
      width: 34px;
      padding: 0;
    }

    .page-btn:disabled {
      opacity: .55;
      cursor: not-allowed;
      transform: none;
    }

    .empty-state {
      padding: 28px 18px;
      text-align: center;
      color: var(--slate-500);
      background: #fff;
    }

    .program-drawer {
      position: fixed;
      inset: 0 0 0 auto;
      z-index: 1200;
      width: min(440px, 100vw);
      pointer-events: none;
      visibility: hidden;
    }

    .program-drawer.is-open {
      pointer-events: auto;
      visibility: visible;
    }

    .program-drawer__backdrop {
      position: absolute;
      inset: 0;
      background: rgba(15,42,107,.18);
      opacity: 0;
      transition: opacity .2s ease;
    }

    .program-drawer.is-open .program-drawer__backdrop {
      opacity: 1;
    }

    .program-drawer__panel {
      position: absolute;
      top: 0;
      right: 0;
      width: min(440px, 100vw);
      height: 100%;
      background: #fff;
      box-shadow: -18px 0 40px rgba(15,42,107,.18);
      transform: translateX(100%);
      transition: transform .22s ease;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .program-drawer.is-open .program-drawer__panel {
      transform: translateX(0);
    }

    .program-drawer__header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 22px 22px 18px;
      border-bottom: 1px solid var(--slate-200);
      background: linear-gradient(180deg, #fff, #f8fafc);
    }

    .program-drawer__header h2 {
      margin: 0;
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--navy-900);
      letter-spacing: -0.02em;
    }

    .close-drawer {
      border: 0;
      background: transparent;
      color: var(--slate-500);
      font-size: 1.8rem;
      line-height: 1;
      cursor: pointer;
      padding: 0;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .close-drawer:hover {
      color: var(--slate-700);
    }

    .program-form {
      padding: 24px 22px 22px;
      overflow-y: auto;
      flex: 1;
    }

    .form-section {
      margin-bottom: 22px;
    }

    .form-section-title {
      font-size: .8rem;
      font-weight: 800;
      color: var(--slate-500);
      text-transform: uppercase;
      letter-spacing: .05em;
      margin-bottom: 12px;
      display: block;
    }

    .field {
      display: grid;
      gap: 8px;
      margin-bottom: 16px;
    }

    .field label {
      color: var(--slate-700);
      font-size: .8rem;
      font-weight: 700;
    }

    .field input,
    .field select,
    .field textarea {
      width: 100%;
      min-height: 44px;
      padding: 10px 12px;
      border-radius: 8px;
      border: 1px solid var(--slate-300);
      background: #fff;
      color: var(--slate-700);
      font: inherit;
      box-sizing: border-box;
    }

    .field select {
      padding: 10px 12px;
    }

    .field input:focus,
    .field select:focus,
    .field textarea:focus {
      outline: 0;
      border-color: rgba(31,95,207,.5);
      box-shadow: 0 0 0 3px rgba(31,95,207,.12);
    }

    .field textarea {
      min-height: 90px;
      resize: vertical;
      padding-top: 10px;
      padding-bottom: 10px;
    }

    .document-section {
      display: grid;
      gap: 12px;
      padding: 14px;
      background: var(--slate-50);
      border-radius: 8px;
      border: 1px solid var(--slate-200);
    }

    .document-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px;
      background: #fff;
      border-radius: 6px;
      border: 1px solid var(--slate-200);
    }

    .document-icon {
      width: 32px;
      height: 32px;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      background: var(--blue-100);
      color: var(--blue-600);
    }

    .document-info {
      flex: 1;
      min-width: 0;
    }

    .document-name {
      display: block;
      font-size: .8rem;
      font-weight: 700;
      color: var(--slate-700);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .document-size {
      display: block;
      font-size: .7rem;
      color: var(--slate-500);
    }

    .document-actions {
      display: flex;
      gap: 6px;
    }

    .doc-action-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 32px;
      height: 32px;
      border-radius: 6px;
      border: 1px solid var(--slate-200);
      background: #fff;
      color: var(--slate-700);
      font-size: .75rem;
      font-weight: 600;
      cursor: pointer;
      transition: all .15s ease;
    }

    .doc-action-btn:hover {
      border-color: var(--slate-300);
      background: var(--slate-50);
    }

    .doc-action-btn.delete {
      color: var(--danger-500);
    }

    .no-document {
      text-align: center;
      padding: 12px;
      color: var(--slate-500);
      font-size: .8rem;
    }

    .drawer-actions {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 18px 22px 22px;
      border-top: 1px solid var(--slate-200);
    }

    .drawer-actions .secondary-btn {
      flex: 1;
    }

    .drawer-actions .primary-btn {
      flex: 1;
    }

    @media (max-width: 860px) {
      .programs-header {
        flex-direction: column;
        align-items: stretch;
      }

      .programs-toolbar {
        flex-direction: column;
        align-items: stretch;
      }

      .programs-toolbar__right {
        width: 100%;
        justify-content: stretch;
      }

      .search-wrap {
        max-width: none;
      }

      .programs-filters {
        grid-template-columns: 1fr;
      }

      .program-table-footer {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>
<body class="admin-dashboard-ref-body">
  <div class="admin-portal-shell">
    <?php include __DIR__ . '/admin-sidebar.php'; ?>
    <main class="admin-main">
      <section class="programs-admin">
        <div class="programs-header">
          <div>
            <h1>Programs &amp; Services Management</h1>
            <p>Manage program categories, links, display order, and public visibility.</p>
          </div>
          <button type="button" class="primary-btn" id="add-program-btn">+ Add New Program</button>
        </div>

        <section class="programs-table-card" aria-label="Program Records Dashboard">
          <div class="programs-toolbar">
            <div class="records-title">
              <span class="records-title__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M7 3h8l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M15 3v5h5"/><path d="M8 11h8M8 15h8"/></svg>
              </span>
              <h2>Program Records</h2>
            </div>
            <div class="programs-toolbar__right">
              <label class="search-wrap" aria-label="Search Programs">
                <input type="search" id="program-search" placeholder="Search programs or services..." aria-label="Search programs or services">
              </label>
            </div>
          </div>

          <div class="programs-filters">
            <label>
              Category
              <select id="category-filter">
                <option value="all">All categories</option>
              </select>
            </label>
            <label>
              Status
              <select id="status-filter">
                <option value="all">All statuses</option>
                <option value="published">Published</option>
                <option value="draft">Draft</option>
              </select>
            </label>
            <label>
              Sort by
              <select id="sort-filter">
                <option value="order_asc">Display Order: Asc</option>
                <option value="order_desc">Display Order: Desc</option>
                <option value="title_asc">Title: A-Z</option>
                <option value="date_desc">Last Updated</option>
              </select>
            </label>
          </div>

          <div class="program-table-wrap">
            <table class="program-table">
              <thead>
                <tr>
                  <th style="width: 28%;">Program / Service</th>
                  <th style="width: 18%;">Category</th>
                  <th style="width: 12%;">Status</th>
                  <th style="width: 10%;">Display Order</th>
                  <th style="width: 12%;">Last Updated</th>
                  <th style="width: 20%;">Actions</th>
                </tr>
              </thead>
              <tbody id="program-table-body"></tbody>
            </table>
          </div>

          <div class="program-table-footer">
            <div id="program-results-meta">Showing 0 to 0 of 0 entries</div>
            <div class="program-pagination" id="program-pagination" aria-label="Pagination"></div>
          </div>
        </section>
      </section>
    </main>
  </div>

  <aside class="program-drawer" id="program-drawer" aria-hidden="true">
    <div class="program-drawer__backdrop" data-close-drawer></div>
    <div class="program-drawer__panel">
      <div class="program-drawer__header">
        <h2 id="program-drawer-title">Add New Program</h2>
        <button type="button" class="close-drawer" data-close-drawer aria-label="Close form">×</button>
      </div>

      <form id="program-form" class="program-form">
        <input type="hidden" id="program-id" name="id">

        <div class="form-section">
          <div class="field">
            <label for="program-title">Program Name <span aria-hidden="true">*</span></label>
            <input id="program-title" name="title" type="text" placeholder="Enter program or service name" required>
          </div>

          <div class="field">
            <label for="program-category">Category <span aria-hidden="true">*</span></label>
            <select id="program-category" name="category" required>
              <option value="">Select category</option>
            </select>
          </div>

          <div class="field">
            <label for="program-status">Status <span aria-hidden="true">*</span></label>
            <select id="program-status" name="is_active" required>
              <option value="1">Published</option>
              <option value="0">Draft</option>
            </select>
          </div>

          <div class="field">
            <label for="program-order">Display Order <span aria-hidden="true">*</span></label>
            <input id="program-order" name="sort_order" type="number" min="1" value="1" required>
          </div>
        </div>

        <div class="form-section">
          <span class="form-section-title">Program Document</span>
          <div class="document-section">
            <div id="program-document-display"></div>
            <div>
              <input type="hidden" id="program-link" name="link_url">
              <button type="button" class="doc-action-btn" id="program-change-file-btn" style="width: 100%; justify-content: center;">
                + Change File
              </button>
            </div>
          </div>
        </div>
      </form>

      <div class="drawer-actions">
        <button type="button" class="secondary-btn" data-close-drawer>Cancel</button>
        <button type="submit" class="primary-btn" id="program-save-btn" form="program-form">Save Changes</button>
      </div>
    </div>
  </aside>

  <script>
    const apiUrl = 'auth/programs_api.php';
    const programTableBody = document.getElementById('program-table-body');
    const programResultsMeta = document.getElementById('program-results-meta');
    const programPagination = document.getElementById('program-pagination');
    const programSearch = document.getElementById('program-search');
    const categoryFilter = document.getElementById('category-filter');
    const statusFilter = document.getElementById('status-filter');
    const sortFilter = document.getElementById('sort-filter');
    const addProgramBtn = document.getElementById('add-program-btn');
    const drawer = document.getElementById('program-drawer');
    const programForm = document.getElementById('program-form');
    const drawerTitle = document.getElementById('program-drawer-title');
    const programIdField = document.getElementById('program-id');
    const programCategoryField = document.getElementById('program-category');
    const programTitleField = document.getElementById('program-title');
    const programLinkField = document.getElementById('program-link');
    const programStatusField = document.getElementById('program-status');
    const programOrderField = document.getElementById('program-order');
    const programDocDisplay = document.getElementById('program-document-display');
    const programChangeFileBtn = document.getElementById('program-change-file-btn');
    const programSaveBtn = document.getElementById('program-save-btn');

    let allPrograms = [];
    let currentPage = 1;
    const pageSize = 5;

    function escapeHtml(value) {
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function formatLastUpdated(value) {
      if (!value) {
        return '—';
      }

      const date = new Date(value);
      if (Number.isNaN(date.getTime())) {
        return '—';
      }

      return new Intl.DateTimeFormat('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }).format(date);
    }

    function openDrawer(mode, program = null) {
      const isEdit = mode === 'edit' && program;
      programForm.reset();
      drawerTitle.textContent = isEdit ? 'Edit Program' : 'Add New Program';
      programIdField.value = isEdit ? String(program.id) : '';
      programCategoryField.value = isEdit ? (program.category || '') : '';
      programTitleField.value = isEdit ? (program.title || '') : '';
      programLinkField.value = isEdit ? (program.link_url || '') : '';
      programStatusField.value = isEdit ? (program.is_active ? '1' : '0') : '1';
      programOrderField.value = isEdit ? Number(program.sort_order || 1) : 1;
      updateDocumentDisplay(isEdit && program ? program : null);
      drawer.classList.add('is-open');
      drawer.setAttribute('aria-hidden', 'false');
      setTimeout(() => {
        programTitleField.focus();
      }, 80);
    }

    function updateDocumentDisplay(program) {
      if (!program || !program.link_url) {
        programDocDisplay.innerHTML = '<div class="no-document">No document selected</div>';
        return;
      }

      const url = program.link_url;
      const fileName = url.includes('/') ? url.split('/').pop().split('?')[0] : 'Document';
      const displayName = fileName && fileName.length > 0 ? fileName : 'View Document';

      programDocDisplay.innerHTML = '<div class="document-item">' +
        '<span class="document-icon"><svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>' +
        '<div class="document-info">' +
          '<span class="document-name">' + escapeHtml(displayName) + '</span>' +
          '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer" class="document-size" style="color: var(--blue-600); text-decoration: none; cursor: pointer;">View Document →</a>' +
        '</div>' +
        '<div class="document-actions">' +
          '<button type="button" class="doc-action-btn delete" data-action="remove-doc" title="Remove this document">✕</button>' +
        '</div>' +
      '</div>';

      programDocDisplay.querySelector('[data-action="remove-doc"]').addEventListener('click', e => {
        e.preventDefault();
        programLinkField.value = '';
        updateDocumentDisplay(null);
      });
    }

    function closeDrawer() {
      drawer.classList.remove('is-open');
      drawer.setAttribute('aria-hidden', 'true');
      programForm.reset();
      programIdField.value = '';
    }

    function getStatusBadge(isActive) {
      const klass = isActive ? 'published' : 'draft';
      const label = isActive ? 'Published' : 'Draft';
      return '<span class="status-badge ' + klass + '">' + label + '</span>';
    }

    function buildCategoryOptions(programs) {
      const categories = [...new Set(programs.map(item => item.category).filter(Boolean))].sort((a, b) => a.localeCompare(b));
      const categoryOptions = '<option value="">Select category</option>' + categories.map(category => '<option value="' + escapeHtml(category) + '">' + escapeHtml(category) + '</option>').join('');
      programCategoryField.innerHTML = categoryOptions;
      const currentCategory = categoryFilter.value;
      categoryFilter.innerHTML = '<option value="all">All categories</option>' + categories.map(category => '<option value="' + escapeHtml(category) + '">' + escapeHtml(category) + '</option>').join('');
      if (categories.includes(currentCategory)) {
        categoryFilter.value = currentCategory;
      }
    }

    function getFilteredPrograms() {
      const searchValue = (programSearch.value || '').trim().toLowerCase();
      const categoryValue = categoryFilter.value;
      const statusValue = statusFilter.value;
      const sortValue = sortFilter.value;

      let filtered = allPrograms.filter(program => {
        const matchesSearch = !searchValue ||
          (program.title || '').toLowerCase().includes(searchValue) ||
          (program.category || '').toLowerCase().includes(searchValue);

        const matchesCategory = categoryValue === 'all' || program.category === categoryValue;
        const matchesStatus = statusValue === 'all' ||
          (statusValue === 'published' ? !!program.is_active : !program.is_active);

        return matchesSearch && matchesCategory && matchesStatus;
      });

      filtered = filtered.sort((a, b) => {
        switch (sortValue) {
          case 'order_desc':
            return Number(b.sort_order || 0) - Number(a.sort_order || 0);
          case 'title_asc':
            return String(a.title || '').localeCompare(String(b.title || ''));
          case 'date_desc':
            return new Date(b.updated_at || 0) - new Date(a.updated_at || 0);
          case 'order_asc':
          default:
            return Number(a.sort_order || 0) - Number(b.sort_order || 0);
        }
      });

      return filtered;
    }

    function renderPagination(totalItems) {
      const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
      if (totalItems === 0 || totalPages <= 1) {
        programPagination.innerHTML = '';
        return;
      }

      const buttons = [];
      buttons.push('<button type="button" class="page-btn nav" data-page="prev" ' + (currentPage === 1 ? 'disabled' : '') + ' aria-label="Previous page">‹</button>');

      for (let page = 1; page <= totalPages; page += 1) {
        buttons.push('<button type="button" class="page-btn ' + (page === currentPage ? 'active' : '') + '" data-page="' + page + '">' + page + '</button>');
      }

      buttons.push('<button type="button" class="page-btn nav" data-page="next" ' + (currentPage === totalPages ? 'disabled' : '') + ' aria-label="Next page">›</button>');
      programPagination.innerHTML = buttons.join('');

      programPagination.querySelectorAll('[data-page]').forEach(button => {
        button.addEventListener('click', () => {
          const target = button.getAttribute('data-page');
          if (target === 'prev') {
            currentPage = Math.max(1, currentPage - 1);
          } else if (target === 'next') {
            currentPage = Math.min(totalPages, currentPage + 1);
          } else {
            currentPage = Number(target);
          }
          renderPrograms();
        });
      });
    }

    function renderPrograms() {
      const filteredPrograms = getFilteredPrograms();
      const totalPages = Math.max(1, Math.ceil(filteredPrograms.length / pageSize));

      if (currentPage > totalPages) {
        currentPage = totalPages;
      }

      const startIndex = (currentPage - 1) * pageSize;
      const paginatedPrograms = filteredPrograms.slice(startIndex, startIndex + pageSize);

      if (!paginatedPrograms.length) {
        programTableBody.innerHTML = '<tr><td colspan="6"><div class="empty-state">No records match your current search or filter.</div></td></tr>';
        programResultsMeta.textContent = 'Showing 0 to 0 of 0 entries';
        renderPagination(0);
        return;
      }

      programTableBody.innerHTML = paginatedPrograms.map(program => {
        const docLink = program.link_url && /^https?:\/\//i.test(program.link_url)
          ? '<a class="program-doc-link" href="' + escapeHtml(program.link_url) + '" target="_blank" rel="noopener noreferrer">View Program Document</a>'
          : '<span class="program-doc-link" style="color:#64748b; cursor:default;">No document link</span>';

        return '<tr>' +
          '<td>' +
            '<div class="program-title">' +
              '<span class="program-file-badge" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 3h8l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M15 3v5h5"/><path d="M8 11h8M8 15h8"/></svg></span>' +
              '<div><span class="program-name">' + escapeHtml(program.title || 'Untitled Program') + '</span>' + docLink + '</div>' +
            '</div>' +
          '</td>' +
          '<td><span class="category-pill">' + escapeHtml(program.category || 'Uncategorized') + '</span></td>' +
          '<td>' + getStatusBadge(Boolean(program.is_active)) + '</td>' +
          '<td><span class="sort-order">' + escapeHtml(String(program.sort_order || 1)) + '</span></td>' +
          '<td class="last-updated">' + escapeHtml(formatLastUpdated(program.updated_at)) + '</td>' +
          '<td><div class="actions-cell">' +
            '<button type="button" class="icon-btn edit" data-action="edit" data-id="' + program.id + '" aria-label="Edit program"><svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg></button>' +
            '<button type="button" class="icon-btn delete" data-action="delete" data-id="' + program.id + '" aria-label="Delete program"><svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg></button>' +
          '</div></td>' +
        '</tr>';
      }).join('');

      const showingStart = filteredPrograms.length === 0 ? 0 : startIndex + 1;
      const showingEnd = Math.min(startIndex + paginatedPrograms.length, filteredPrograms.length);
      programResultsMeta.textContent = 'Showing ' + showingStart + ' to ' + showingEnd + ' of ' + filteredPrograms.length + ' entries';
      renderPagination(filteredPrograms.length);

      programTableBody.querySelectorAll('[data-action]').forEach(button => {
        button.addEventListener('click', event => {
          const id = Number(event.currentTarget.dataset.id);
          const action = event.currentTarget.dataset.action;

          if (action === 'edit') {
            const program = allPrograms.find(item => Number(item.id) === id);
            if (program) {
              openDrawer('edit', program);
            }
          }

          if (action === 'delete') {
            if (!window.confirm('Delete this program record?')) {
              return;
            }

            fetch(apiUrl, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
              },
              body: new URLSearchParams({
                action: 'delete',
                id: String(id)
              })
            })
              .then(response => response.json())
              .then(result => {
                if (result && result.success) {
                  loadPrograms();
                } else {
                  window.alert(result && result.error ? result.error : 'Unable to delete program.');
                }
              })
              .catch(() => window.alert('Unable to delete program.'));
          }
        });
      });
    }

    async function loadPrograms() {
      try {
        const response = await fetch(apiUrl, { cache: 'no-store' });
        const payload = await response.json();
        allPrograms = Array.isArray(payload && payload.programs) ? payload.programs : [];
        buildCategoryOptions(allPrograms);
        currentPage = 1;
        renderPrograms();
      } catch (error) {
        programTableBody.innerHTML = '<tr><td colspan="6"><div class="empty-state">Unable to load program records right now.</div></td></tr>';
        programResultsMeta.textContent = 'Showing 0 to 0 of 0 entries';
      }
    }

    addProgramBtn.addEventListener('click', () => openDrawer('add'));

    document.querySelectorAll('[data-close-drawer]').forEach(button => {
      button.addEventListener('click', closeDrawer);
    });

    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && drawer.classList.contains('is-open')) {
        closeDrawer();
      }
    });

    programForm.addEventListener('submit', async event => {
      event.preventDefault();
      const payload = new FormData(programForm);
      const programId = programIdField.value.trim();
      const linkUrl = programLinkField.value.trim();

      payload.set('action', programId ? 'update' : 'create');
      payload.set('id', programId || '');
      payload.set('sort_order', String(Number(programOrderField.value || 1)));
      payload.set('is_active', programStatusField.value === '1' ? '1' : '0');
      payload.set('link_url', linkUrl);

      try {
        const response = await fetch(apiUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
          },
          body: new URLSearchParams(payload).toString()
        });

        const result = await response.json();

        if (!response.ok || (result && result.error)) {
          throw new Error(result && result.error ? result.error : 'Unable to save program.');
        }

        closeDrawer();
        await loadPrograms();
      } catch (error) {
        window.alert(error && error.message ? error.message : 'Unable to save program.');
      }
    });

    programSearch.addEventListener('input', () => {
      currentPage = 1;
      renderPrograms();
    });

    categoryFilter.addEventListener('change', () => {
      currentPage = 1;
      renderPrograms();
    });

    statusFilter.addEventListener('change', () => {
      currentPage = 1;
      renderPrograms();
    });

    sortFilter.addEventListener('change', () => {
      currentPage = 1;
      renderPrograms();
    });

    loadPrograms();
  </script>
</body>
</html>


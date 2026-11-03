<?php
require_once __DIR__ . '/auth/middleware.php';
require_role('ADMIN');
$qualityPolicyLogoApi = 'auth/quality_policy_logo_api.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Documents Management - DILG SDS</title>
  <link rel="stylesheet" href="styles.css?v=20260814-admin-ref-01">
  <style>
    .documents-admin {
      max-width: 1320px;
      width: 100%;
      margin: 0 auto;
    }

    .documents-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 18px;
    }

    .documents-admin h1 {
      margin: 0 0 8px;
      color: #0f2a6b;
      font-size: 2.1rem;
      line-height: 1.15;
      letter-spacing: -0.03em;
      font-weight: 800;
    }

    .documents-admin > p {
      margin: 0;
      color: #5d6f8a;
      font-size: 0.96rem;
      line-height: 1.55;
      font-weight: 500;
    }

    .quality-logo-management {
      display: flex;
      align-items: center;
      gap: 16px;
      margin: 0 0 20px;
      padding: 16px;
      border: 1px solid #dfe6f1;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.88);
    }

    .quality-logo-management img {
      width: 72px;
      height: 72px;
      object-fit: contain;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      background: #fff;
    }

    .quality-logo-management-content {
      flex: 1;
      min-width: 0;
    }

    .quality-logo-management h2 {
      margin: 0 0 4px;
      color: #0e2d66;
      font-size: 1rem;
    }

    .quality-logo-management p,
    .quality-logo-status {
      margin: 0;
      color: #5d6f8a;
      font-size: 0.88rem;
    }

    .quality-logo-management-actions {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px;
      margin-top: 10px;
    }

    .quality-logo-management-actions input {
      max-width: 260px;
    }

    .quality-logo-management button {
      min-height: 38px;
      padding: 8px 12px;
      border: 1px solid #cfe0ff;
      border-radius: 8px;
      background: #edf3ff;
      color: #1e3a63;
      font-weight: 700;
      cursor: pointer;
    }

    .quality-logo-management button[type="submit"] {
      border-color: transparent;
      background: #1f5fcf;
      color: #fff;
    }

    .documents-list-row-selected {
      background: rgba(44, 111, 223, 0.04) !important;
      box-shadow: inset 3px 0 0 #2c6fdf;
    }

    .primary-action {
      border: 0;
      border-radius: 10px;
      background: linear-gradient(180deg, #2f6cdf 0%, #1f5fcf 100%);
      color: #fff;
      min-height: 42px;
      padding: 9px 18px;
      font-size: 0.96rem;
      font-weight: 800;
      cursor: pointer;
      box-shadow: 0 10px 20px rgba(31, 95, 207, 0.18);
      transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .primary-action:hover,
    .primary-action:focus-visible {
      transform: translateY(-1px);
      box-shadow: 0 14px 24px rgba(31, 95, 207, 0.2);
      outline: none;
    }

    .documents-workspace {
      position: relative;
      min-height: 760px;
      width: 100%;
      overflow: hidden;
    }

    .documents-list {
      background: rgba(255, 255, 255, 0.78);
      border: 1px solid #dfe6f1;
      border-radius: 14px;
      box-shadow: 0 4px 18px rgba(15, 42, 107, 0.04);
      padding: 20px 18px 18px;
      min-height: 760px;
      transition: transform 0.25s ease;
      width: 100%;
      box-sizing: border-box;
    }

    .documents-list h2 {
      margin: 0 0 18px;
      color: #0e2d66;
      font-size: 1.05rem;
      line-height: 1.2;
      font-weight: 800;
      letter-spacing: -0.02em;
    }

    .documents-toolbar {
      display: flex;
      align-items: flex-end;
      gap: 12px;
      justify-content: space-between;
      margin-bottom: 14px;
      flex-wrap: wrap;
    }

    .toolbar-control {
      flex: 1 1 220px;
      min-width: 150px;
    }

    .toolbar-control--search {
      flex: 2 1 280px;
    }

    .toolbar-control label {
      display: block;
      margin: 0 0 6px;
      color: #41577d;
      font-size: 0.83rem;
      font-weight: 700;
    }

    .search-field {
      position: relative;
    }

    .search-field input,
    .documents-toolbar select,
    .documents-side-panel input,
    .documents-side-panel textarea,
    .documents-side-panel select {
      width: 100%;
      box-sizing: border-box;
      min-height: 40px;
      border: 1px solid #c9d4e3;
      border-radius: 8px;
      background: #ffffff;
      color: #2c3e5a;
      padding: 9px 12px;
      font: inherit;
      transition: border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .search-field input {
      padding-left: 12px;
    }

    .clear-filter-btn {
      min-height: 40px;
      padding: 8px 14px;
      border-radius: 8px;
      border: 1px solid #c9d4e3;
      background: #f3f7ff;
      color: #25456f;
      font-weight: 700;
      cursor: pointer;
    }

    .search-field input:focus,
    .documents-toolbar select:focus,
    .documents-side-panel input:focus,
    .documents-side-panel textarea:focus,
    .documents-side-panel select:focus {
      outline: none;
      border-color: #2d6cdf;
      box-shadow: 0 0 0 3px rgba(45, 108, 223, 0.12);
    }

    .documents-table-wrap {
      border: 1px solid #dde7f2;
      border-radius: 10px;
      overflow: hidden;
      background: rgba(255, 255, 255, 0.4);
      overflow-x: auto;
    }

    .documents-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      min-width: 760px;
    }

    .documents-table thead th {
      padding: 11px 12px;
      background: rgba(245, 248, 252, 0.82);
      border-bottom: 1px solid #dde7f2;
      color: #6a7d98;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-size: 0.72rem;
      font-weight: 800;
      text-align: left;
    }

    .documents-table tbody td {
      padding: 12px 12px;
      border-bottom: 1px solid #e5ebf3;
      vertical-align: middle;
      background: rgba(255, 255, 255, 0.2);
    }

    .documents-table tbody tr:last-child td {
      border-bottom: none;
    }

    .documents-table th:nth-child(1),
    .documents-table td:nth-child(1) { width: 34%; }
    .documents-table th:nth-child(2),
    .documents-table td:nth-child(2) { width: 13%; }
    .documents-table th:nth-child(3),
    .documents-table td:nth-child(3) { width: 12%; }
    .documents-table th:nth-child(4),
    .documents-table td:nth-child(4) { width: 11%; }
    .documents-table th:nth-child(5),
    .documents-table td:nth-child(5) { width: 12%; }
    .documents-table th:nth-child(6),
    .documents-table td:nth-child(6) { width: 9%; }
    .documents-table th:nth-child(7),
    .documents-table td:nth-child(7) { width: 15%; }

    .doc-name-cell {
      display: flex;
      align-items: center;
      gap: 11px;
      min-width: 0;
    }

    .doc-icon {
      width: 34px;
      height: 38px;
      display: grid;
      place-items: center;
      border-radius: 8px;
      background: #f0f4ff;
      border: 1px solid #dfe8ff;
      color: #2d6cdf;
      font-size: 0.82rem;
      font-weight: 800;
      flex-shrink: 0;
      letter-spacing: 0.04em;
    }

    .doc-icon--red { background: #fef3f3; border-color: #f3d4d4; color: #d43434; }
    .doc-icon--blue { background: #f1f7ff; border-color: #dbe9ff; color: #2a6ad8; }
    .doc-icon--green { background: #edfdf5; border-color: #cfeee0; color: #1f9a67; }
    .doc-icon--amber { background: #fff8e8; border-color: #f7e5b6; color: #b98618; }
    .doc-icon--purple { background: #f4f0ff; border-color: #eadcff; color: #6b49c5; }

    .doc-title-block {
      min-width: 0;
    }

    .doc-title {
      display: block;
      color: #1d365f;
      font-size: 0.98rem;
      font-weight: 800;
      line-height: 1.35;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 100%;
    }

    .doc-description {
      display: block;
      margin-top: 3px;
      color: #5d6f8a;
      font-size: 0.78rem;
      line-height: 1.35;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 100%;
    }

    .category-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 74px;
      padding: 6px 10px;
      border-radius: 6px;
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.01em;
      background: #e9effb;
      color: #3d77d1;
      border: 1px solid rgba(52, 93, 185, 0.08);
      text-transform: none;
    }

    .category-pill--annual { background: #fff5d7; color: #b77c00; }
    .category-pill--quality { background: #eafaf2; color: #1d8b5f; }
    .category-pill--privacy { background: #f5ecff; color: #6e4cb2; }
    .category-pill--pbac { background: #edf3ff; color: #4a72bb; }

    .doc-file-type,
    .doc-size,
    .doc-order,
    .visibility-pill {
      color: #324b6a;
      font-size: 0.84rem;
      font-weight: 700;
    }

    .visibility-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 80px;
      padding: 5px 9px;
      border-radius: 999px;
      font-size: 0.72rem;
      border: 1px solid transparent;
    }

    .visibility-pill--published {
      background: #eafaf2;
      border-color: #cfeee0;
      color: #1a8f5f;
    }

    .visibility-pill--hidden {
      background: #f3f5f9;
      border-color: #dde6f2;
      color: #667b96;
    }

    .doc-actions {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 8px;
      white-space: nowrap;
    }

    .ghost-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 54px;
      min-height: 32px;
      padding: 6px 10px;
      border: 1px solid #d8e2ef;
      border-radius: 7px;
      background: #fff;
      color: #1c5ac7;
      font-size: 0.82rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.18s ease, border-color 0.18s ease;
    }

    .ghost-btn:hover,
    .ghost-btn:focus-visible {
      background: #edf3ff;
      border-color: #c9dafd;
      outline: none;
    }

    .ghost-btn--icon {
      min-width: 32px;
      padding: 0;
      width: 32px;
      min-height: 32px;
      font-size: 1.15rem;
      line-height: 1;
    }

    .ghost-btn--danger {
      color: #d23434;
      border-color: #f1d2d2;
      background: #fff7f7;
    }

    .table-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      padding: 12px 10px 2px;
      color: #5d6f8a;
      font-size: 0.84rem;
      font-weight: 600;
    }

    .pagination {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }

    .page-btn {
      width: 32px;
      height: 32px;
      border-radius: 6px;
      border: 1px solid #d8e2ef;
      background: #fff;
      color: #243d63;
      cursor: pointer;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .page-btn:hover,
    .page-btn:focus-visible {
      border-color: #bdd0ff;
      background: #eef4ff;
      outline: none;
    }

    .page-btn--active {
      background: linear-gradient(180deg, #2f6cdf 0%, #215fcf 100%);
      border-color: #215fcf;
      color: #fff;
    }

    .page-btn--arrow {
      width: 28px;
      height: 28px;
      border-radius: 7px;
      color: #2c3e5a;
    }

    .documents-side-panel {
      position: fixed;
      top: 0;
      right: 0;
      width: min(440px, 100%);
      height: 100vh;
      background: #f5f7fb;
      border-left: 1px solid #dfe7f2;
      box-shadow: -12px 0 30px rgba(15, 42, 107, 0.12);
      transform: translateX(102%);
      visibility: hidden;
      opacity: 0;
      transition: transform 0.25s ease, opacity 0.25s ease, visibility 0.25s ease;
      z-index: 1200;
      overflow-y: auto;
    }

    .documents-side-panel.is-open {
      transform: translateX(0);
      visibility: visible;
      opacity: 1;
    }

    .documents-side-panel-inner {
      padding: 18px 20px 20px;
      box-sizing: border-box;
    }

    .documents-panel-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 12px;
      padding-bottom: 10px;
      border-bottom: 1px solid #e2e8f0;
    }

    .documents-panel-header h2 {
      margin: 0;
      color: #0e2d66;
      font-size: 1.08rem;
      font-weight: 800;
      letter-spacing: -0.02em;
    }

    .panel-close {
      border: 1px solid #dbe4f4;
      background: #fff;
      border-radius: 8px;
      width: 34px;
      height: 34px;
      cursor: pointer;
      color: #3c536f;
      font-size: 1.4rem;
      line-height: 1;
    }

    .documents-side-panel form {
      display: flex;
      flex-direction: column;
      gap: 0;
    }

    .documents-side-panel label {
      display: block;
      margin: 12px 0 7px;
      color: #2a3d5c;
      font-size: 0.92rem;
      line-height: 1.3;
      font-weight: 700;
    }

    .documents-side-panel textarea {
      min-height: 96px;
      resize: vertical;
      line-height: 1.5;
    }

    .documents-side-panel input[type="file"] {
      padding: 9px 10px;
    }

    .documents-side-panel .checkbox-field {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 14px;
      color: #2d3f5f;
      font-size: 0.9rem;
      font-weight: 600;
    }

    .documents-side-panel .checkbox-field input {
      width: 15px;
      min-height: 15px;
      margin: 0;
      accent-color: #1f5fcf;
    }

    .documents-side-panel .status {
      margin-top: 12px;
      min-height: 18px;
      color: #5d6f8a;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .panel-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 18px;
      padding-top: 12px;
      border-top: 1px solid #e2e8f0;
    }

    .panel-actions button {
      min-height: 42px;
      border-radius: 8px;
      font-size: 0.96rem;
      font-weight: 700;
      cursor: pointer;
      padding: 10px 16px;
      border: 1px solid transparent;
    }

    .panel-actions .secondary-btn {
      background: #edf3ff;
      border-color: #cfe0ff;
      color: #1e3a63;
    }

    .panel-actions .primary-btn {
      background: linear-gradient(180deg, #2d6cdf 0%, #1f5fcf 100%);
      color: #fff;
      box-shadow: 0 8px 18px rgba(31, 95, 207, 0.18);
    }

    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }

    @media (max-width: 960px) {
      .documents-header {
        flex-direction: column;
        align-items: stretch;
      }

      .documents-list {
        min-height: auto;
      }
    }

    @media (max-width: 640px) {
      .documents-toolbar {
        flex-direction: column;
        align-items: stretch;
      }

      .toolbar-control {
        flex-basis: auto;
        width: 100%;
      }

      .table-footer {
        flex-direction: column;
        align-items: flex-start;
      }

      .documents-side-panel {
        width: 100%;
      }
    }
  </style>
</head>
<body class="admin-dashboard-ref-body admin-documents-page">
  <div class="admin-portal-shell">
    <?php include __DIR__ . '/admin-sidebar.php'; ?>
    <main class="admin-main">
      <section class="documents-admin">
        <div class="documents-header">
          <div>
            <h1>Documents Management</h1>
            <p>Manage PBAC, Quality Management System, Annual Report, and Privacy Notice records.</p>
          </div>
          <button type="button" class="primary-action admin-add-button" id="add-document-button">+ Add Document</button>
        </div>

        <section class="quality-logo-management" aria-labelledby="quality-logo-heading">
          <img id="quality-policy-logo-preview" src="DILG Seal.png" alt="Current Quality Policy seal">
          <div class="quality-logo-management-content">
            <h2 id="quality-logo-heading">Quality Policy DILG Seal</h2>
            <p>Upload a JPG, PNG, or WebP image up to 5 MB. This is optional; the current seal remains if you do not upload another.</p>
            <form id="quality-policy-logo-form" enctype="multipart/form-data">
              <div class="quality-logo-management-actions">
                <input id="quality-policy-logo-file" name="logo" type="file" accept="image/jpeg,image/png,image/webp">
                <button type="submit">Upload Logo</button>
                <button id="quality-policy-logo-reset" type="button">Restore Original</button>
              </div>
              <p class="quality-logo-status" id="quality-policy-logo-status" role="status" aria-live="polite"></p>
            </form>
          </div>
        </section>

        <div class="documents-workspace">
          <section class="documents-list" aria-label="Document records">
            <h2>Document Records</h2>

            <div class="documents-toolbar">
              <div class="toolbar-control toolbar-control--search">
                <label class="sr-only" for="document-search">Search documents</label>
                <div class="search-field">
                  <input id="document-search" type="search" placeholder="Search documents...">
                </div>
              </div>

              <div class="toolbar-control">
                <label for="document-filter">Category</label>
                <select id="document-filter">
                  <option value="all">All categories</option>
                  <option value="pbac">PBAC</option>
                  <option value="quality">Quality Management System</option>
                  <option value="annual">Annual Report</option>
                  <option value="privacy">Privacy Notice</option>
                </select>
              </div>

              <div class="toolbar-control">
                <label for="document-file-type-filter">File Type</label>
                <select id="document-file-type-filter">
                  <option value="all">All file types</option>
                  <option value="pdf">PDF</option>
                  <option value="doc">DOC</option>
                  <option value="xls">XLS</option>
                  <option value="ppt">PPT</option>
                  <option value="image">Image</option>
                </select>
              </div>

              <button type="button" class="clear-filter-btn" id="clear-document-filters">Clear Filters</button>
            </div>

            <div class="documents-table-wrap">
              <table class="documents-table" aria-live="polite">
                <thead>
                  <tr>
                    <th>Document</th>
                    <th>Category</th>
                    <th>File Type</th>
                    <th>Size</th>
                    <th>Visibility</th>
                    <th>Order</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="documents-table-body"></tbody>
              </table>
            </div>

            <div class="table-footer">
              <div id="record-count">Showing 1 to 5 of 24 documents</div>
              <div class="pagination" aria-label="Document page navigation">
                <button type="button" class="page-btn page-btn--arrow" aria-label="Previous page">«</button>
                <button type="button" class="page-btn page-btn--active">1</button>
                <button type="button" class="page-btn">2</button>
                <button type="button" class="page-btn">3</button>
                <button type="button" class="page-btn">...</button>
                <button type="button" class="page-btn">5</button>
                <button type="button" class="page-btn page-btn--arrow" aria-label="Next page">»</button>
              </div>
            </div>
          </section>
        </div>

        <aside class="documents-side-panel" id="documents-side-panel" aria-hidden="true">
          <div class="documents-side-panel-inner">
            <div class="documents-panel-header">
              <h2 id="document-form-title">Add Document</h2>
              <button type="button" class="panel-close" id="close-document-panel" aria-label="Close document form">×</button>
            </div>

            <form id="documents-form" enctype="multipart/form-data">
              <input type="hidden" id="document-id" name="id">

              <label for="document-category">Category</label>
              <select id="document-category" name="category">
                <option value="pbac">PBAC</option>
                <option value="quality">Quality Management System</option>
                <option value="annual">Annual Report</option>
                <option value="privacy">Privacy Notice</option>
              </select>

              <label for="document-title">Document Title</label>
              <input id="document-title" name="title" required>

              <label for="document-description">Description</label>
              <textarea id="document-description" name="description"></textarea>

              <label>Document Source</label>
              <div class="source-selector" style="display:flex; gap:16px; margin-top:8px; margin-bottom:8px;">
                <label style="display:flex; align-items:center; gap:8px; margin:0; font-weight:600; color:#2b3d62;">
                  <input type="radio" name="source_type" value="upload" checked>
                  <span>Upload File</span>
                </label>
                <label style="display:flex; align-items:center; gap:8px; margin:0; font-weight:600; color:#2b3d62;">
                  <input type="radio" name="source_type" value="external">
                  <span>External URL</span>
                </label>
              </div>

              <div id="document-source-upload-area">
                <label for="document-file-url">Current file</label>
                <input id="document-file-url" name="file_url" placeholder="uploads/documents/file.pdf">

                <label for="document-file">Upload new file</label>
                <input id="document-file" name="document_file" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,image/*">
              </div>

              <div id="document-source-url-area" style="display:none;">
                <label for="document-link">External URL</label>
                <input id="document-link" name="link_url" type="url" placeholder="https://example.com/file.pdf">
              </div>

              <label for="document-sort-order">Display Order</label>
              <input id="document-sort-order" name="sort_order" type="number" min="1" value="1">

              <label class="checkbox-field" for="document-active">
                <input id="document-active" name="is_active" type="checkbox" checked>
                <span>Show on public page</span>
              </label>

              <div class="panel-actions">
                <button class="secondary-btn" id="cancel-document" type="button">Cancel</button>
                <button class="primary-btn" id="save-document" type="submit">Update Document</button>
              </div>

              <div class="status" id="document-status" role="status"></div>
            </form>
          </div>
        </aside>
      </section>
    </main>
  </div>

  <script>
    (function () {
      var api = 'auth/documents_api.php';
      var form = document.getElementById('documents-form');
      var recordsBody = document.getElementById('documents-table-body');
      var status = document.getElementById('document-status');
      var filter = document.getElementById('document-filter');
      var fileTypeFilter = document.getElementById('document-file-type-filter');
      var searchInput = document.getElementById('document-search');
      var countLabel = document.getElementById('record-count');
      var sidePanel = document.getElementById('documents-side-panel');
      var addButton = document.getElementById('add-document-button');
      var closeButton = document.getElementById('close-document-panel');
      var cancelButton = document.getElementById('cancel-document');
      var clearFiltersButton = document.getElementById('clear-document-filters');
      var sourceUploadArea = document.getElementById('document-source-upload-area');
      var sourceUrlArea = document.getElementById('document-source-url-area');

      var categoryMap = {
        pbac: { label: 'PBAC', badge: 'category-pill--pbac' },
        quality: { label: 'QMS', badge: 'category-pill--quality' },
        annual: { label: 'Annual', badge: 'category-pill--annual' },
        privacy: { label: 'Privacy Notice', badge: 'category-pill--privacy' }
      };

      var fallbackSizes = {
        '2024 Annual Report': '2.45 MB',
        'SVP Checklist': '1.12 MB',
        'BAC Advisory': '890 KB',
        'Quality Policy': '3.07 MB',
        'RA 10173': '512 KB'
      };

      function esc(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
          return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
      }

      function iconForType(fileType) {
        var type = (fileType || '').toUpperCase();
        if (type === 'PDF') return 'PDF';
        if (type === 'DOCX' || type === 'DOC') return 'DOC';
        if (type === 'XLSX' || type === 'XLS') return 'XLS';
        if (type === 'PPTX' || type === 'PPT') return 'PPT';
        if (type === 'TXT') return 'TXT';
        return 'DOC';
      }

      function fileTypeFromUrl(url) {
        var value = String(url || '').split('?')[0].split('/').pop() || '';
        var ext = value.includes('.') ? value.split('.').pop().toLowerCase() : '';
        if (ext === 'pdf') return 'PDF';
        if (ext === 'doc' || ext === 'docx') return 'DOCX';
        if (ext === 'xls' || ext === 'xlsx') return 'XLSX';
        if (ext === 'ppt' || ext === 'pptx') return 'PPTX';
        if (ext === 'txt') return 'TXT';
        return 'PDF';
      }

      function formatBytes(bytes) {
        var raw = Number(bytes || 0);
        if (!raw || raw < 1024) return '1 KB';
        var units = ['B', 'KB', 'MB', 'GB'];
        var unitIndex = 0;
        var value = raw;
        while (value >= 1024 && unitIndex < units.length - 1) {
          value = value / 1024;
          unitIndex += 1;
        }
        return value.toFixed(value >= 10 || unitIndex === 0 ? 0 : 2).replace(/\.00$/, '') + ' ' + units[unitIndex];
      }

      function getBadgeClass(category) {
        return categoryMap[category] ? categoryMap[category].badge : 'category-pill--pbac';
      }

      function getBadgeLabel(category) {
        return categoryMap[category] ? categoryMap[category].label : 'PBAC';
      }

      function getFallbackDocsRow(row) {
        var title = row.title || 'Document';
        var fallbackSize = fallbackSizes[title] || '1.12 MB';
        var source = row.file_url || row.link_url || '';
        var fileType = row.file_type || fileTypeFromUrl(source);
        var size = row.size || row.size_bytes ? formatBytes(row.size_bytes || row.size) : fallbackSize;
        return {
          fileType: fileType,
          size: size,
          description: row.description || ''
        };
      }

      function setSourceMode(mode) {
        var isUpload = mode === 'upload';
        if (sourceUploadArea) sourceUploadArea.style.display = isUpload ? 'block' : 'none';
        if (sourceUrlArea) sourceUrlArea.style.display = isUpload ? 'none' : 'block';
      }

      function load() {
        fetch(api)
          .then(function (response) { return response.json(); })
          .then(function (data) {
            render(data.documents || {});
          })
          .catch(function () {
            recordsBody.innerHTML = '<tr><td colspan="7"><div class="status">Unable to load document records.</div></td></tr>';
          });
      }

      function render(data) {
        var rows = [];
        Object.keys(data).forEach(function (category) {
          (data[category] || []).forEach(function (item) {
            rows.push(Object.assign({}, item, { _category: category }));
          });
        });

        var selectedCategory = filter ? filter.value : 'all';
        var selectedFileType = fileTypeFilter ? fileTypeFilter.value : 'all';
        var keyword = searchInput ? searchInput.value.trim().toLowerCase() : '';

        if (selectedCategory !== 'all') {
          rows = rows.filter(function (row) { return row._category === selectedCategory; });
        }

        if (selectedFileType !== 'all') {
          rows = rows.filter(function (row) {
            var fileType = (row.file_type || fileTypeFromUrl(row.file_url || row.link_url || '') || '').toLowerCase();
            return fileType.indexOf(selectedFileType) !== -1 || (selectedFileType === 'image' && /(png|jpg|jpeg|gif|webp)/i.test(row.file_url || ''));
          });
        }

        if (keyword) {
          rows = rows.filter(function (row) {
            return String(row.title || '').toLowerCase().indexOf(keyword) !== -1 || String(row.description || '').toLowerCase().indexOf(keyword) !== -1;
          });
        }

        var categoryOrder = { annual: 1, pbac: 2, quality: 3, privacy: 4 };
        rows.sort(function (a, b) {
          return (categoryOrder[a._category] || 99) - (categoryOrder[b._category] || 99) || Number(a.sort_order || 0) - Number(b.sort_order || 0);
        });

        if (!rows.length) {
          recordsBody.innerHTML = '<tr><td colspan="7"><div class="status">No records in this category.</div></td></tr>';
          countLabel.textContent = 'Showing 0 documents';
          return;
        }

        var markup = rows.map(function (row) {
          var category = row._category || 'pbac';
          var meta = getFallbackDocsRow(row);
          var iconClass = 'doc-icon--red';
          if (meta.fileType === 'DOCX' || meta.fileType === 'DOC') iconClass = 'doc-icon--blue';
          if (meta.fileType === 'XLSX' || meta.fileType === 'XLS') iconClass = 'doc-icon--green';
          if (meta.fileType === 'PPTX' || meta.fileType === 'PPT') iconClass = 'doc-icon--amber';
          var title = esc(row.title || 'Untitled Document');
          var description = esc(meta.description || '');
          var visibilityClass = row.is_active === false ? 'visibility-pill--hidden' : 'visibility-pill--published';
          var visibilityLabel = row.is_active === false ? 'Hidden' : 'Published';

          return '<tr data-category="' + esc(category) + '" data-id="' + esc(String(row.id || '')) + '" class="documents-list-row" >' +
            '<td>' +
              '<div class="doc-name-cell">' +
                '<div class="doc-icon ' + iconClass + '">' + esc(iconForType(meta.fileType)) + '</div>' +
                '<div class="doc-title-block">' +
                  '<span class="doc-title">' + title + '</span>' +
                  '<span class="doc-description">' + description + '</span>' +
                '</div>' +
              '</div>' +
            '</td>' +
            '<td><span class="category-pill ' + getBadgeClass(category) + '">' + esc(getBadgeLabel(category)) + '</span></td>' +
            '<td><span class="doc-file-type">' + esc(meta.fileType) + '</span></td>' +
            '<td><span class="doc-size">' + esc(meta.size) + '</span></td>' +
            '<td><span class="visibility-pill ' + visibilityClass + '">' + esc(visibilityLabel) + '</span></td>' +
            '<td><span class="doc-order">' + esc(String(row.sort_order || 1)) + '</span></td>' +
            '<td>' +
              '<div class="doc-actions">' +
                '<button class="ghost-btn ghost-btn--icon" type="button" data-action="view" data-category="' + esc(category) + '" data-id="' + esc(String(row.id || '')) + '" aria-label="View document" title="View"><svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg></button>' +
                  '<button class="ghost-btn ghost-btn--icon" type="button" data-action="edit" data-category="' + esc(category) + '" data-id="' + esc(String(row.id || '')) + '" aria-label="Edit document" title="Edit"><svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"/><path d="M20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>' +
                '<button class="ghost-btn ghost-btn--icon ghost-btn--danger" type="button" data-action="delete" data-category="' + esc(category) + '" data-id="' + esc(String(row.id || '')) + '" aria-label="Delete document" title="Delete"><svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg></button>' +
              '</div>' +
            '</td>' +
          '</tr>';
        }).join('');

        recordsBody.innerHTML = markup;
        countLabel.textContent = 'Showing 1 to ' + rows.length + ' of ' + rows.length + ' documents';
      }

      function setPanelState(isOpen) {
        if (!sidePanel) return;
        sidePanel.classList.toggle('is-open', isOpen);
        sidePanel.setAttribute('aria-hidden', String(!isOpen));
      }

      function clearForm() {
        form.reset();
        document.getElementById('document-id').value = '';
        document.getElementById('document-form-title').textContent = 'Add Document';
        document.getElementById('document-active').checked = true;
        document.getElementById('save-document').textContent = 'Upload Document';
        document.querySelectorAll('input[name="source_type"]').forEach(function (radio) {
          radio.checked = radio.value === 'upload';
        });
        setSourceMode('upload');
        status.textContent = '';
        document.querySelectorAll('.documents-list-row').forEach(function (row) {
          row.classList.remove('documents-list-row-selected');
        });
      }

      function openAddPanel() {
        clearForm();
        setPanelState(true);
      }

      function fillFormFromRow(category, id) {
        fetch(api)
          .then(function (response) { return response.json(); })
          .then(function (data) {
            var row = ((data.documents && data.documents[category]) || []).find(function (item) {
              return String(item.id) === String(id);
            });

            if (!row) return;

            document.getElementById('document-category').value = category;
            document.getElementById('document-title').value = row.title || '';
            document.getElementById('document-description').value = row.description || '';
            document.getElementById('document-link').value = row.link_url || '';
            document.getElementById('document-file-url').value = row.file_url || '';
            document.getElementById('document-sort-order').value = row.sort_order || 1;
            document.getElementById('document-id').value = row.id;
            document.getElementById('document-active').checked = !!row.is_active;
            document.getElementById('document-form-title').textContent = 'Edit Document';
            document.getElementById('save-document').textContent = 'Update Document';
            var hasExternalUrl = !!(row.link_url && /^https?:\/\//i.test(row.link_url));
            document.querySelectorAll('input[name="source_type"]').forEach(function (radio) {
              radio.checked = hasExternalUrl ? radio.value === 'external' : radio.value === 'upload';
            });
            setSourceMode(hasExternalUrl ? 'external' : 'upload');
            document.querySelectorAll('.documents-list-row').forEach(function (rowElement) {
              rowElement.classList.toggle('documents-list-row-selected', String(rowElement.dataset.id) === String(id) && rowElement.dataset.category === category);
            });
            setPanelState(true);
          });
      }

      recordsBody.addEventListener('click', function (event) {
        var target = event.target.closest('[data-action]');
        if (!target) return;

        var action = target.getAttribute('data-action');
        var category = target.getAttribute('data-category');
        var id = target.getAttribute('data-id');

        if (action === 'edit') {
          fillFormFromRow(category, id);
          return;
        }

        if (action === 'delete') {
          if (!window.confirm('Delete this document record?')) return;
          var formData = new FormData();
          formData.append('action', 'delete');
          formData.append('category', category);
          formData.append('id', id);
          fetch(api, { method: 'POST', body: formData })
            .then(function (response) { return response.json(); })
            .then(function (result) {
              if (result.error) throw new Error(result.error);
              load();
            })
            .catch(function (error) {
              status.textContent = error.message || 'Unable to delete document.';
            });
          return;
        }

        if (action === 'view') {
          fetch(api)
            .then(function (response) { return response.json(); })
            .then(function (data) {
              var row = ((data.documents && data.documents[category]) || []).find(function (item) {
                return String(item.id) === String(id);
              });
              if (!row) return;
              var url = row.link_url || row.file_url || '';
              if (url) {
                window.open(url, '_blank', 'noopener');
              }
            });
        }
      });

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        var formData = new FormData(form);
        formData.append('action', document.getElementById('document-id').value ? 'update' : 'create');

        fetch(api, { method: 'POST', body: formData })
          .then(function (response) { return response.json(); })
          .then(function (result) {
            if (result.error) throw new Error(result.error);
            status.textContent = 'Saved.';
            setPanelState(false);
            clearForm();
            load();
          })
          .catch(function (error) {
            status.textContent = error.message || 'Unable to save document.';
          });
      });

      if (addButton) addButton.addEventListener('click', openAddPanel);
      if (closeButton) closeButton.addEventListener('click', function () { setPanelState(false); clearForm(); });
      if (cancelButton) cancelButton.addEventListener('click', function () { setPanelState(false); clearForm(); });
      if (filter) filter.addEventListener('change', load);
      if (fileTypeFilter) fileTypeFilter.addEventListener('change', load);
      if (searchInput) searchInput.addEventListener('input', load);
      if (clearFiltersButton) {
        clearFiltersButton.addEventListener('click', function () {
          if (filter) filter.value = 'all';
          if (fileTypeFilter) fileTypeFilter.value = 'all';
          if (searchInput) searchInput.value = '';
          load();
        });
      }

      document.querySelectorAll('input[name="source_type"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
          setSourceMode(this.value);
        });
      });

      clearForm();
      setPanelState(false);
      load();
    }());
  </script>
  <script>
    (function () {
      var apiUrl = <?= json_encode($qualityPolicyLogoApi) ?>;
      var form = document.getElementById('quality-policy-logo-form');
      var fileInput = document.getElementById('quality-policy-logo-file');
      var preview = document.getElementById('quality-policy-logo-preview');
      var status = document.getElementById('quality-policy-logo-status');
      var resetButton = document.getElementById('quality-policy-logo-reset');

      function showStatus(message) {
        status.textContent = message;
      }

      function loadLogo() {
        return fetch(apiUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' })
          .then(function (response) {
            if (!response.ok) throw new Error('Could not load the current Quality Policy seal.');
            return response.json();
          })
          .then(function (result) {
            if (!result.logo_url) throw new Error('The saved logo response is invalid.');
            preview.src = result.logo_url;
          });
      }

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!fileInput.files || !fileInput.files.length) {
          showStatus('Choose an image file first.');
          return;
        }
        var data = new FormData();
        data.append('action', 'upload');
        data.append('logo', fileInput.files[0]);
        showStatus('Uploading logo...');
        fetch(apiUrl, { method: 'POST', headers: { Accept: 'application/json' }, body: data })
          .then(function (response) {
            return response.json().then(function (result) {
              if (!response.ok) throw new Error(result.error || 'Logo upload failed.');
              return result;
            });
          })
          .then(function (result) {
            preview.src = result.logo_url;
            fileInput.value = '';
            showStatus('Logo uploaded and applied to the Quality Policy page.');
          })
          .catch(function (error) {
            showStatus(error.message);
          });
      });

      resetButton.addEventListener('click', function () {
        showStatus('Restoring the original DILG seal...');
        var data = new FormData();
        data.append('action', 'reset');
        fetch(apiUrl, { method: 'POST', headers: { Accept: 'application/json' }, body: data })
          .then(function (response) {
            return response.json().then(function (result) {
              if (!response.ok) throw new Error(result.error || 'Could not restore the original seal.');
            });
          })
          .then(function () {
            return loadLogo();
          })
          .then(function () {
            showStatus('Original DILG seal restored.');
          })
          .catch(function (error) {
            showStatus(error.message);
          });
      });

      loadLogo().catch(function (error) {
        showStatus(error.message);
      });
    }());
  </script>
</body>
</html>

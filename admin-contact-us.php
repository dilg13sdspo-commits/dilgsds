<?php
require_once __DIR__ . '/auth/middleware.php';
require_role('ADMIN');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us Management - DILG</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Admin Dashboard Styles */
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #eef3f9;
        }

        .admin-container {
            display: grid;
            grid-template-columns: auto 1fr;
            min-height: 100vh;
        }

        .admin-main-content {
            display: flex;
            flex-direction: column;
            padding: 32px 40px;
            overflow-y: auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .page-header-left h1 {
            margin: 0 0 8px;
            color: #0f1f3b;
            font-size: 2rem;
            font-weight: 700;
        }

        .page-header-left p {
            margin: 0;
            color: #64748b;
            font-size: 0.95rem;
        }

        .add-button {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95rem;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            transition: all 0.2s ease;
        }

        .add-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
        }

        .records-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .records-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 28px;
            border-bottom: 1px solid #e2e8f0;
            gap: 16px;
        }

        .records-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .records-icon {
            width: 40px;
            height: 40px;
            background: #eef4ff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
        }

        .records-icon svg {
            width: 20px;
            height: 20px;
        }

        .records-header-text h2 {
            margin: 0 0 2px;
            color: #0f1f3b;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .records-header-text p {
            margin: 0;
            color: #64748b;
            font-size: 0.8rem;
        }

        .records-tools {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .search-input {
            padding: 10px 14px;
            border: 1px solid #dfe7f0;
            border-radius: 8px;
            font-size: 0.9rem;
            width: 220px;
            box-sizing: border-box;
        }

        .search-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .filter-select {
            padding: 10px 14px;
            border: 1px solid #dfe7f0;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            background: white;
            box-sizing: border-box;
        }

        .filter-select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .records-table {
            width: 100%;
            border-collapse: collapse;
        }

        .records-table thead {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .records-table th {
            padding: 14px 28px;
            text-align: left;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .records-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.15s ease;
        }

        .records-table tbody tr:hover {
            background: #f8fafc;
        }

        .records-table td {
            padding: 18px 28px;
            color: #334155;
            font-size: 0.9rem;
        }

        .record-title-cell {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .record-doc-icon {
            width: 36px;
            height: 36px;
            background: #eef4ff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            flex-shrink: 0;
        }

        .record-doc-icon svg {
            width: 16px;
            height: 16px;
        }

        .record-title-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .record-title-info strong {
            color: #0f1f3b;
            font-weight: 600;
        }

        .record-subtitle {
            color: #64748b;
            font-size: 0.8rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-badge.published {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-badge.draft {
            background: #fef3c7;
            color: #ca8a04;
        }

        .actions-cell {
            display: flex;
            gap: 5px;
            justify-content: flex-end;
        }

        .icon-btn {
            width: 32px;
            height: 30px;
            border: 1px solid #bdd1f4;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1f5fcf;
            transition: all 0.15s ease;
        }

        .icon-btn.delete {
            border-color: #f2bebe;
            color: #c33434;
        }

        .icon-btn:hover {
            border-color: #bfdbfe;
            background: #f0f6ff;
            color: #2563eb;
        }

        .icon-btn.delete:hover {
            border-color: #fecaca;
            background: #fee2e2;
            color: #dc2626;
        }

        .icon-btn svg {
            width: 16px;
            height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .records-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 28px;
            border-top: 1px solid #e2e8f0;
            background: white;
            font-size: 0.85rem;
            color: #64748b;
        }

        .records-summary {
            font-size: 0.85rem;
            color: #64748b;
        }

        .records-summary.hidden {
            display: none;
        }

        .pagination {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .pagination.hidden {
            display: none;
        }

        .page-btn {
            width: 32px;
            height: 32px;
            border: 1px solid #dfe7f0;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            color: #475569;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-btn:hover:not(:disabled) {
            border-color: #bfdbfe;
            background: #f0f6ff;
            color: #2563eb;
        }

        .page-btn.current {
            background: #2563eb;
            border-color: #2563eb;
            color: white;
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Modal / Drawer Styles */
        .editor-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.25);
            z-index: 40;
        }

        .editor-overlay.open {
            display: block;
        }

        .editor-panel {
            position: fixed;
            right: 0;
            top: 0;
            bottom: 0;
            width: 480px;
            background: white;
            box-shadow: -4px 0 24px rgba(0, 0, 0, 0.12);
            z-index: 50;
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            overflow: hidden;
        }

        .editor-panel.open {
            transform: translateX(0);
        }

        .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            background: white;
        }

        .editor-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .editor-header-left h3 {
            margin: 0;
            color: #0f1f3b;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .edit-badge {
            background: #eef4ff;
            color: #2563eb;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .editor-close-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 1.5rem;
            color: #64748b;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .editor-close-btn:hover {
            background: #f1f5f9;
            color: #334155;
        }

        .editor-body {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 20px;
        }

        .form-group label {
            color: #475569;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .form-group.required label::after {
            content: " *";
            color: #dc2626;
        }

        .form-group input,
        .form-group textarea {
            padding: 10px 12px;
            border: 1px solid #dfe7f0;
            border-radius: 6px;
            font-size: 0.9rem;
            font-family: inherit;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-group.optional input,
        .form-group.optional textarea {
            min-height: 100px;
        }

        .map-preview {
            width: 100%;
            height: 160px;
            background: #f1f5f9;
            border-radius: 8px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 0.85rem;
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }

        .map-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 20px 0 24px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #2563eb;
        }

        .checkbox-group label {
            cursor: pointer;
            color: #475569;
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0;
        }

        .form-status {
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            margin-bottom: 12px;
            display: none;
        }

        .form-status.show {
            display: block;
        }

        .form-status.success {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .form-status.error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .editor-footer {
            display: flex;
            gap: 12px;
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            background: white;
        }

        .editor-footer button {
            flex: 1;
            padding: 11px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            font-size: 0.9rem;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #dfe7f0;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
        }

        .btn-save {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .btn-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
        }

        @media (max-width: 1024px) {
            .admin-container {
                grid-template-columns: 1fr;
            }

            .editor-panel {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .admin-main-content {
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .records-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .records-tools {
                width: 100%;
                flex-direction: column;
            }

            .search-input,
            .filter-select {
                width: 100%;
            }
        }
    </style>
</head>
<body class="admin-dashboard-ref-body">
    <div class="admin-container">
        <?php include __DIR__ . '/admin-sidebar.php'; ?>

        <main class="admin-main-content">
            <header class="page-header">
                <div class="page-header-left">
                    <h1>Contact Us Management</h1>
                    <p>Manage published content for this website section.</p>
                </div>
                <button class="add-button admin-add-button" onclick="openEditor(null)">+ Add New Content</button>
            </header>

            <div class="records-card">
                <div class="records-header">
                    <div class="records-header-left">
                        <div class="records-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                        </div>
                        <div class="records-header-text">
                            <h2>Page Records</h2>
                            <p id="page-records-desc">List of all Contact Us service entries.</p>
                        </div>
                    </div>

                    <div class="records-tools">
                        <input type="text" class="search-input" id="searchInput" placeholder="Search title...">
                        <select class="filter-select" id="filterSelect">
                            <option value="all">Filter</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                        <button style="padding: 10px 14px; border: 1px solid #dfe7f0; background: white; border-radius: 8px; cursor: pointer; font-size: 0.85rem; color: #2563eb; font-weight: 600;" onclick="clearFilters()">Clear Filters</button>
                    </div>
                </div>

                <table class="records-table">
                    <thead>
                        <tr>
                            <th>TITLE</th>
                            <th>STATUS</th>
                            <th>LAST UPDATED</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                    </tbody>
                </table>

                <div class="records-footer">
                    <span class="records-summary" id="recordsSummary"></span>
                    <div class="pagination" id="pagination"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Editor Overlay -->
    <div class="editor-overlay" id="editorOverlay"></div>

    <!-- Editor Panel -->
    <div class="editor-panel" id="editorPanel">
        <div class="editor-header">
            <div class="editor-header-left">
                <h3 id="editorTitle">Edit Contact Us Content</h3>
                <span class="edit-badge" id="editBadge">EDIT</span>
            </div>
            <button type="button" class="editor-close-btn" onclick="closeEditor()">×</button>
        </div>

        <div class="editor-body">
            <div id="formStatus" class="form-status"></div>

            <form id="contactForm" onsubmit="saveForm(event)">
                <input type="hidden" id="recordId">

                <div class="form-group required">
                    <label for="titleInput">Page Title</label>
                    <input type="text" id="titleInput" name="title" required>
                </div>

                <div class="form-group required">
                    <label for="addressInput">Address</label>
                    <textarea id="addressInput" name="address" required></textarea>
                </div>

                <div class="form-group">
                    <label for="phoneInput">Phone Number</label>
                    <input type="tel" id="phoneInput" name="phone_number">
                </div>

                <div class="form-group">
                    <label for="emailInput">Email Address</label>
                    <input type="email" id="emailInput" name="email_address">
                </div>

                <div class="form-group">
                    <label for="hoursInput">Office Hours</label>
                    <input type="text" id="hoursInput" name="office_hours">
                </div>

                <div class="form-group">
                    <label for="descriptionInput">Description</label>
                    <textarea id="descriptionInput" name="description"></textarea>
                </div>

                <div class="form-group optional">
                    <label for="mapInput">Map Location (Optional)</label>
                    <input type="url" id="mapInput" name="map_location" placeholder="https://maps.google.com/...">
                    <div class="map-preview" id="mapPreview"></div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="publishedCheckbox" name="is_active" checked>
                    <label for="publishedCheckbox">Published on public page</label>
                </div>
            </form>
        </div>

        <div class="editor-footer">
            <button type="button" class="btn-cancel" onclick="closeEditor()">Cancel</button>
            <button type="button" class="btn-save" onclick="document.getElementById('contactForm').dispatchEvent(new Event('submit'))">Save Changes</button>
        </div>
    </div>

    <script>
        let records = [];
        let currentPage = 1;
        const pageSize = 10;
        let searchTerm = '';
        let filterStatus = 'all';

        async function loadRecords() {
            try {
                const response = await fetch('auth/contact_api.php');
                const data = await response.json();
                records = data.records || [];
                currentPage = 1;
                renderTable();
            } catch (error) {
                console.error('Error loading records:', error);
                document.getElementById('tableBody').innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 40px; color: #dc2626;">Error loading records</td></tr>';
            }
        }

        function renderTable() {
            const filtered = records.filter(record => {
                const searchMatches = !searchTerm || 
                    record.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                    (record.address && record.address.toLowerCase().includes(searchTerm.toLowerCase()));
                
                const statusMatches = filterStatus === 'all' || 
                    (filterStatus === 'published' && record.is_active) ||
                    (filterStatus === 'draft' && !record.is_active);
                
                return searchMatches && statusMatches;
            });

            const totalItems = filtered.length;
            const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
            currentPage = Math.min(currentPage, totalPages);

            const start = (currentPage - 1) * pageSize;
            const pageRecords = filtered.slice(start, start + pageSize);

            const tbody = document.getElementById('tableBody');
            if (pageRecords.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 40px; color: #64748b;">No records found</td></tr>';
            } else {
                tbody.innerHTML = pageRecords.map(record => `
                    <tr>
                        <td>
                            <div class="record-title-cell">
                                <div class="record-doc-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                </div>
                                <div class="record-title-info">
                                    <strong>${escapeHtml(record.title)}</strong>
                                    <span class="record-subtitle">${escapeHtml(record.address || '')}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge ${record.is_active ? 'published' : 'draft'}">
                                ${record.is_active ? 'Published' : 'Draft'}
                            </span>
                        </td>
                        <td>${formatDate(record.updated_at)}</td>
                        <td>
                            <div class="actions-cell">
                                <button type="button" class="icon-btn" onclick="editRecord(${record.id})" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"/>
                                        <path d="M20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                                    </svg>
                                </button>
                                <button type="button" class="icon-btn delete" onclick="deleteRecord(${record.id})" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }

            updateSummaryAndPagination(totalItems, totalPages);
        }

        function updateSummaryAndPagination(totalItems, totalPages) {
            const summaryEl = document.getElementById('recordsSummary');
            const paginationEl = document.getElementById('pagination');

            // HIDE PAGINATION AND SUMMARY WHEN ONLY 1 RECORD
            if (totalItems <= 1) {
                summaryEl.classList.add('hidden');
                paginationEl.classList.add('hidden');
                return;
            }

            summaryEl.classList.remove('hidden');
            paginationEl.classList.remove('hidden');

            const start = (currentPage - 1) * pageSize + 1;
            const end = Math.min(currentPage * pageSize, totalItems);
            summaryEl.textContent = `Showing ${start} to ${end} of ${totalItems} entries`;

            paginationEl.innerHTML = '';
            
            if (totalPages > 1) {
                const prevBtn = document.createElement('button');
                prevBtn.className = 'page-btn';
                prevBtn.textContent = '‹';
                prevBtn.disabled = currentPage === 1;
                prevBtn.onclick = () => { currentPage--; renderTable(); };
                paginationEl.appendChild(prevBtn);

                for (let i = 1; i <= totalPages; i++) {
                    const btn = document.createElement('button');
                    btn.className = 'page-btn' + (i === currentPage ? ' current' : '');
                    btn.textContent = i;
                    btn.onclick = () => { currentPage = i; renderTable(); };
                    paginationEl.appendChild(btn);
                }

                const nextBtn = document.createElement('button');
                nextBtn.className = 'page-btn';
                nextBtn.textContent = '›';
                nextBtn.disabled = currentPage === totalPages;
                nextBtn.onclick = () => { currentPage++; renderTable(); };
                paginationEl.appendChild(nextBtn);
            }
        }

        function formatDate(dateStr) {
            if (!dateStr) return '—';
            try {
                const date = new Date(dateStr);
                return date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch {
                return '—';
            }
        }

        function escapeHtml(text) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, c => map[c]);
        }

        function openEditor(record) {
            const overlay = document.getElementById('editorOverlay');
            const panel = document.getElementById('editorPanel');
            
            document.getElementById('recordId').value = record ? record.id : '';
            document.getElementById('titleInput').value = record ? record.title : '';
            document.getElementById('addressInput').value = record ? (record.address || '') : '';
            document.getElementById('phoneInput').value = record ? (record.phone_number || '') : '';
            document.getElementById('emailInput').value = record ? (record.email_address || '') : '';
            document.getElementById('hoursInput').value = record ? (record.office_hours || '') : '';
            document.getElementById('descriptionInput').value = record ? (record.description || '') : '';
            document.getElementById('mapInput').value = record ? (record.map_location || '') : '';
            document.getElementById('publishedCheckbox').checked = record ? record.is_active : true;

            updateMapPreview();

            if (record) {
                document.getElementById('editorTitle').textContent = 'Edit Contact Us Content';
                document.getElementById('editBadge').textContent = 'EDIT';
            } else {
                document.getElementById('editorTitle').textContent = 'Add New Contact Us Entry';
                document.getElementById('editBadge').textContent = 'NEW';
            }

            overlay.classList.add('open');
            panel.classList.add('open');
        }

        function closeEditor() {
            document.getElementById('editorOverlay').classList.remove('open');
            document.getElementById('editorPanel').classList.remove('open');
            document.getElementById('formStatus').classList.remove('show');
        }

        function updateMapPreview() {
            const mapUrl = document.getElementById('mapInput').value;
            const mapPreview = document.getElementById('mapPreview');
            
            if (mapUrl) {
                // Try to extract embed URL from Google Maps
                let embedUrl = mapUrl;
                if (mapUrl.includes('google.com/maps')) {
                    embedUrl = mapUrl.replace('/maps', '/maps/embed').replace('?', '?output=embed&');
                }
                mapPreview.innerHTML = `<iframe width="100%" height="100%" style="border: none; border-radius: 8px;" src="${embedUrl}" allowfullscreen="" loading="lazy"></iframe>`;
            } else {
                mapPreview.innerHTML = 'Map preview will appear here';
            }
        }

        document.getElementById('mapInput').addEventListener('change', updateMapPreview);

        async function editRecord(id) {
            const record = records.find(r => r.id === id);
            if (record) {
                openEditor(record);
            }
        }

        async function deleteRecord(id) {
            if (!confirm('Are you sure you want to delete this record?')) return;

            try {
                const response = await fetch('auth/contact_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'delete', id })
                });

                const data = await response.json();
                if (data.error) {
                    showMessage(data.error, 'error');
                } else {
                    showMessage('Record deleted successfully', 'success');
                    loadRecords();
                }
            } catch (error) {
                showMessage('Error deleting record', 'error');
            }
        }

        async function saveForm(event) {
            event.preventDefault();

            const id = document.getElementById('recordId').value;
            const formData = new URLSearchParams({
                action: id ? 'update' : 'create',
                id: id || '',
                title: document.getElementById('titleInput').value,
                address: document.getElementById('addressInput').value,
                phone_number: document.getElementById('phoneInput').value,
                email_address: document.getElementById('emailInput').value,
                office_hours: document.getElementById('hoursInput').value,
                description: document.getElementById('descriptionInput').value,
                map_location: document.getElementById('mapInput').value,
                is_active: document.getElementById('publishedCheckbox').checked ? '1' : '0'
            });

            try {
                const response = await fetch('auth/contact_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });

                const data = await response.json();
                if (data.error) {
                    showMessage(data.error, 'error');
                } else {
                    showMessage(id ? 'Record updated successfully' : 'Record created successfully', 'success');
                    setTimeout(() => {
                        closeEditor();
                        loadRecords();
                    }, 600);
                }
            } catch (error) {
                showMessage('Error saving record', 'error');
            }
        }

        function showMessage(message, type) {
            const el = document.getElementById('formStatus');
            el.textContent = message;
            el.className = `form-status show ${type}`;
        }

        function clearFilters() {
            searchTerm = '';
            filterStatus = 'all';
            document.getElementById('searchInput').value = '';
            document.getElementById('filterSelect').value = 'all';
            currentPage = 1;
            renderTable();
        }

        document.getElementById('searchInput').addEventListener('input', (e) => {
            searchTerm = e.target.value;
            currentPage = 1;
            renderTable();
        });

        document.getElementById('filterSelect').addEventListener('change', (e) => {
            filterStatus = e.target.value;
            currentPage = 1;
            renderTable();
        });

        document.getElementById('editorOverlay').addEventListener('click', closeEditor);

        loadRecords();
    </script>
</body>
</html>

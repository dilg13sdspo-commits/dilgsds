<?php
require_once __DIR__ . '/../shared/auth/middleware.php';
require_once __DIR__ . '/../shared/auth/po_icons.php';
require_role('PO USER');
$pdo = db_connect();
$userName = $_SESSION['username'] ?? 'Provincial User';
$userId = (int)($_SESSION['user_id'] ?? 0);
$profilePhoto = get_user_profile_photo($pdo, $userId);
$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
$stmt->execute([':uid' => $userId]);
$notificationCount = (int)$stmt->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reports - DILG PO Dashboard</title>
<style>
:root { --blue: #103b83; --blue-dark: #0b2e69; --gold: #d88a2d; --bg: #f4f7fb; --text: #17263c; --muted: #68768d; --border: #e4e9ef; --red: #c5192d; --green: #107c4e; }
* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body { margin: 0; font-family: "Segoe UI", Arial, sans-serif; color: var(--text); background: var(--bg); }
a { text-decoration: none; }
button, input, select { font: inherit; }

.shell { min-height: 100vh; display: flex; }

.sidebar { width: 220px; flex: 0 0 220px; padding: 18px 14px; color: #fff; background: linear-gradient(180deg, #0b3d91, #1456a0); display: flex; flex-direction: column; }
.brand { display: flex; align-items: center; gap: 10px; padding: 7px 8px 18px; border-bottom: 1px solid rgba(255,255,255,.14); }
.brand-mark { width: 34px; height: 34px; border-radius: 9px; background: rgba(255,255,255,.14); display: grid; place-items: center; font-weight: 800; }
.brand strong { display: block; font-size: .9rem; }
.brand small { display: block; font-size: .62rem; opacity: .78; margin-top: 2px; }

.sidebar-profile { display: flex; align-items: center; gap: 10px; margin: 14px 4px 0; padding: 10px 8px; border: 1px solid rgba(255,255,255,.12); border-radius: 12px; background: rgba(255,255,255,.08); }
.sidebar-profile-avatar { width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.28); display: grid; place-items: center; font-size: .82rem; font-weight: 800; flex: 0 0 36px; overflow: hidden; }
.sidebar-profile-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
.sidebar-profile-info { min-width: 0; }
.sidebar-profile-status { display: flex; align-items: center; gap: 6px; font-size: .72rem; color: rgba(255,255,255,.88); }
.sidebar-profile-status span { width: 7px; height: 7px; border-radius: 50%; background: #34d399; }
.sidebar-profile-name { display: block; margin-top: 3px; color: #fff; font-size: .78rem; font-weight: 700; }

.nav { display: flex; flex-direction: column; gap: 6px; margin-top: 16px; padding: 0 4px; flex: 1; }
.nav-item { width: 100%; border: none; background: transparent; color: rgba(255,255,255,.9); display: flex; align-items: center; justify-content: space-between; gap: 8px; text-align: left; border-radius: 10px; padding: 12px 10px; cursor: pointer; transition: all 0.2s ease; font-weight: 600; }
.nav-item:hover, .nav-item.active { background: rgba(255,255,255,.12); box-shadow: inset 0 0 0 1px rgba(255,255,255,.06); }
.nav-item.active { background: rgba(255,255,255,.18); box-shadow: inset 3px 0 0 rgba(255,255,255,.8); }
.nav-main { display: inline-flex; align-items: center; gap: 10px; min-width: 0; }
.nav-icon { width: 18px; height: 18px; display: inline-flex; align-items: center; flex-shrink: 0; }
.nav-icon svg { width: 18px; height: 18px; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.nav-label { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.nav-badge { min-width: 22px; height: 22px; border-radius: 999px; background: var(--gold); color: #fff; display: grid; place-items: center; font-size: 0.72rem; font-weight: 800; padding: 0 6px; }

.content { min-width: 0; flex: 1; display: flex; flex-direction: column; }

.topbar { height: 72px; display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 12px 24px; background: #fff; border-bottom: 1px solid var(--border); flex: 0 0 72px; }
.title { display: flex; align-items: center; gap: 10px; min-width: 0; }
.title-mark { width: 34px; height: 34px; background: var(--gold); color: #fff; border-radius: 9px; display: grid; place-items: center; font-weight: 800; }
.title strong { display: block; font-size: 1.02rem; text-transform: uppercase; letter-spacing: .02em; }
.title small { display: block; color: var(--muted); font-size: .67rem; margin-top: 2px; }

.search-box { position: relative; }
.search-box input { width: 350px; max-width: 100%; padding: 11px 14px; border: 1px solid var(--border); border-radius: 10px; background: #f9fafc; font: inherit; color: var(--text); }
.search-box input::placeholder { color: var(--muted); }
.btn-refresh { padding: 10px 16px; background: #edf3ff; color: var(--blue); border: 1px solid rgba(16,59,131,.18); border-radius: 10px; font: inherit; font-weight: 600; cursor: pointer; }
.btn-refresh:hover { background: #dce8ff; }

.main { flex: 1; overflow-y: auto; padding: 24px; }
.card { background: #fff; border: 1px solid var(--border); border-radius: 18px; box-shadow: 0 12px 40px rgba(20,34,54,.04); overflow: hidden; display: flex; flex-direction: column; height: 100%; }

.card-header { padding: 24px; border-bottom: 1px solid var(--border); }
.card-title { margin: 0 0 8px; font-size: 1.3rem; font-weight: 700; color: var(--text); }
.card-desc { margin: 0; font-size: .95rem; color: var(--muted); }

.tabs { display: flex; gap: 12px; flex-wrap: wrap; padding: 0 20px; border-bottom: 1px solid var(--border); overflow-x: auto; }
.tab { padding: 12px 16px; border: none; background: none; color: var(--muted); font-size: .95rem; font-weight: 600; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s; white-space: nowrap; }
.tab:hover { color: var(--text); }
.tab.active { color: var(--blue); border-bottom-color: var(--blue); }

.toolbar { padding: 20px; border-bottom: 1px solid var(--border); display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
.filter-group { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
.filter-label { font-size: .78rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
.filter-select { min-width: 200px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; background: #fff; font: inherit; color: var(--text); }

.table-wrap { padding: 20px; flex: 1; overflow-y: auto; }
.table-empty { text-align: center; padding: 40px 20px; color: var(--muted); font-size: .95rem; }

.table { width: 100%; border-collapse: collapse; }
.table th { padding: 14px 12px; text-align: left; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); border-bottom: 1px solid var(--border); }
.table td { padding: 14px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; font-size: .9rem; }
.table tbody tr { cursor: pointer; transition: background 0.2s; }
.table tbody tr:hover { background: #f8fbff; }

.cell-num { font-weight: 600; color: var(--blue); }
.cell-title { font-weight: 600; color: var(--text); }
.cell-submitted-by { color: var(--muted); font-size: .9rem; }
.submitted-by-dropdown { position: relative; display: inline-block; }
.submitted-by-dropdown summary {
  list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; padding: 6px 8px; border: 1px solid var(--border); border-radius: 7px; background: #fff; color: var(--text); font-size: .86rem; font-weight: 600;
}
.submitted-by-dropdown summary::-webkit-details-marker { display: none; }
.submitted-by-dropdown summary::after { content: '?'; font-size: .8rem; color: var(--muted); }
.submitted-by-dropdown-panel {
  position: absolute; z-index: 20; left: 0; top: calc(100% + 6px); min-width: 220px; background: #fff; border: 1px solid var(--border); border-radius: 10px; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12); padding: 8px; display: flex; flex-direction: column; gap: 6px;
}
.submitted-by-dropdown-item {
  padding: 8px 10px; border-radius: 6px; color: var(--text); font-size: .82rem; background: #f7f9fc; cursor: pointer;
}
.submitted-by-dropdown-item:hover { background: #eef5ff; }
.submitted-by-dropdown { position: relative; display: inline-block; }
.submitted-by-dropdown summary {
  list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; padding: 6px 8px; border: 1px solid var(--border); border-radius: 7px; background: #fff; color: var(--text); font-size: .86rem; font-weight: 600;
}
.submitted-by-dropdown summary::-webkit-details-marker { display: none; }
.submitted-by-dropdown summary::after { content: '?'; font-size: .8rem; color: var(--muted); }
.submitted-by-dropdown-panel {
  position: absolute; z-index: 20; left: 0; top: calc(100% + 6px); min-width: 220px; background: #fff; border: 1px solid var(--border); border-radius: 10px; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12); padding: 8px; display: flex; flex-direction: column; gap: 6px;
}
.submitted-by-dropdown-item {
  padding: 8px 10px; border-radius: 6px; color: var(--text); font-size: .82rem; background: #f7f9fc; cursor: pointer;
}
.submitted-by-dropdown-item:hover { background: #eef5ff; }
.cell-status { display: inline-block; padding: 6px 12px; border-radius: 6px; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.status-under-review { background: #e3f1ff; color: #075b91; }
.status-pending { background: #fff8e1; color: #a67600; }
.status-approved { background: #d1f4e0; color: #107c4e; }
.status-returned { background: #fce7eb; color: var(--red); }
.status-overdue { background: #ffe0e2; color: #bd1f2d; }

.priority-badge { display: inline-block; font-size: .75rem; font-weight: 700; color: #fff; padding: 4px 8px; border-radius: 4px; }
.priority-high { background: #c5192d; }
.priority-medium { background: #f5a623; }
.priority-low { background: #20b883; }

.actions { display: flex; gap: 8px; }
.action-btn { width: 32px; height: 32px; border: none; background: #edf3ff; border-radius: 6px; cursor: pointer; display: grid; place-items: center; color: var(--blue); transition: all 0.2s; font-size: 1.2rem; }
.action-btn:hover { background: #dce8ff; }

.footer { padding: 14px 20px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; color: var(--muted); font-size: .88rem; }
.pager { display: flex; gap: 12px; }
.pager-btn { padding: 8px 12px; border: 1px solid var(--border); background: #fff; border-radius: 8px; font: inherit; font-weight: 600; cursor: pointer; }
.pager-btn:disabled { opacity: .45; cursor: not-allowed; }

.modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,.5); z-index: 1000; justify-content: center; align-items: center; }
.modal.active { display: flex; }
.modal-card { width: 90%; max-width: 900px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; border-radius: 12px; background: #fff; box-shadow: 0 20px 60px rgba(0,0,0,.15); }
.modal-head { padding: 24px 28px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.modal-head h2 { margin: 0; font-size: 1.5rem; color: var(--text); }
.close-btn { background: none; border: none; font-size: 1.7rem; cursor: pointer; color: var(--muted); }
.close-btn:hover { color: var(--text); }
.modal-body { padding: 28px; overflow-y: auto; flex: 1; background: #fff; }
.modal-actions { padding: 24px 28px; border-top: 1px solid var(--border); background: #f8faff; display: flex; justify-content: flex-end; gap: 12px; }
.modal-actions[style*="display: none"] { display: none; }

.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px; padding-bottom: 28px; border-bottom: 1px solid var(--border); }
.detail-row { display: flex; flex-direction: column; gap: 8px; }
.detail-label { font-size: .76rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }
.detail-value { font-size: 1rem; font-weight: 600; color: var(--text); }

.section { margin-bottom: 24px; }
.section-title { font-size: .9rem; font-weight: 700; color: #5a6b7f; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 12px; }
.section-text { color: var(--text); line-height: 1.6; font-size: .95rem; }

.documents-list { display: flex; flex-direction: column; gap: 10px; }
.document-item { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; background: #f8faff; border: 1px solid #e0e8f0; border-radius: 8px; transition: all 0.2s; }
.document-item:hover { background: #eef5ff; border-color: var(--blue); }
.document-main { display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1; }
.document-link { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--blue); font-weight: 600; flex: 1; min-width: 0; }
.document-link:hover { color: var(--blue-dark); }
.document-icon { font-size: 1.2rem; }
.document-name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.document-size { font-size: .85rem; color: var(--muted); white-space: nowrap; margin-left: 10px; }
.document-open-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 88px; padding: 8px 14px; border-radius: 8px; background: var(--blue); color: #fff; text-decoration: none; font-weight: 700; font-size: .86rem; }
.document-open-btn:hover { background: var(--blue-dark); }

.btn { padding: 12px 28px; border: none; border-radius: 8px; font: inherit; font-weight: 700; cursor: pointer; transition: all 0.2s; }
.btn-primary { background: var(--blue); color: #fff; }
.btn-primary:hover { background: var(--blue-dark); }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; }
.btn-secondary { background: #e4ecf7; color: var(--blue); border: 2px solid var(--blue); }
.btn-secondary:hover { background: #dce8ff; }
.btn-secondary:disabled { opacity: .6; cursor: not-allowed; }
.btn-success { background: #1f9d65; color: #fff; }
.btn-success:hover { background: #177a4d; }
.btn-success:disabled { opacity: .6; cursor: not-allowed; }

.return-box { display: none; padding: 24px 28px 20px; border-top: 1px solid var(--border); background: linear-gradient(180deg, #fff7f5 0%, #fff 100%); }
.return-box.active { display: block; }
.return-box label { display: block; font-size: .95rem; font-weight: 700; color: var(--text); margin-bottom: 12px; }
.return-box textarea { width: 100%; min-height: 110px; padding: 12px 14px; border: 1px solid #c9d4df; border-radius: 10px; resize: vertical; font: inherit; background: #fff; box-shadow: inset 0 1px 2px rgba(17,24,39,0.03); }
.return-box textarea:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px rgba(17, 94, 180, 0.08); }
.return-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 16px; }

@media (max-width: 768px) {
  .sidebar { width: 60px; }
  .brand, .sidebar-profile, .nav span, .badge { display: none; }
  .nav a { width: 44px; height: 44px; padding: 12px; justify-content: center; }
  .detail-grid { grid-template-columns: 1fr; }
  .tabs { flex-direction: column; }
  .toolbar { flex-direction: column; }
}
</style>
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-mark">D</div>
      <div>
        <strong>DILG</strong>
        <small>Provincial Office User</small>
      </div>
    </div>
    <div class="sidebar-profile">
      <div class="sidebar-profile-avatar">
        <?php if ($profilePhoto): ?>
          <img src="<?= htmlspecialchars($profilePhoto) ?>" alt="PO User profile photo">
        <?php else: ?>
          <?= htmlspecialchars(strtoupper(substr($userName, 0, 2))) ?>
        <?php endif; ?>
      </div>
      <div class="sidebar-profile-info">
        <div class="sidebar-profile-status"><span></span>Online</div>
        <div class="sidebar-profile-name"><?= htmlspecialchars($userName) ?></div>
      </div>
    </div>
    <nav class="nav" aria-label="PO User navigation">
      <button class="nav-item" type="button" onclick="window.location.href='dashboard.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('dashboard') ?></span><span class="nav-label">Dashboard</span></span></button>
      <button class="nav-item" type="button" onclick="window.location.href='inquiries.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('submissions') ?></span><span class="nav-label">My Submissions</span></span></button>
      <button class="nav-item" type="button" onclick="window.location.href='office.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('office') ?></span><span class="nav-label">My Office</span></span></button>
      <button class="nav-item" type="button" onclick="window.location.href='calendar.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('calendar') ?></span><span class="nav-label">Calendar</span></span></button>
      <button class="nav-item active" type="button" onclick="window.location.href='reports.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('reports') ?></span><span class="nav-label">Reports</span></span></button>
      <button class="nav-item" type="button" onclick="window.location.href='notifications.php'"><span class="nav-main"><span class="nav-icon"><?= po_icon('notifications') ?></span><span class="nav-label">Notifications</span></span><span class="nav-badge"><?= htmlspecialchars((string)$notificationCount) ?></span></button>
      <button class="nav-item" type="button" onclick="logout()"><span class="nav-main"><span class="nav-icon"><?= po_icon('logout') ?></span><span class="nav-label">Logout</span></span></button>
    </nav>
  </aside>

  <div class="content">
    <header class="topbar">
      <div class="title">
        <div class="title-mark">R</div>
        <div>
          <strong>Reports</strong>
          <small>Provincial Office</small>
        </div>
      </div>
      <div style="display:flex; gap:10px; align-items:center; flex:1; justify-content:flex-end;">
        <div class="search-box">
          <input id="search" type="search" placeholder="Search submission, title, municipality or FO...">
        </div>
        <button class="btn-refresh" id="refresh">Refresh</button>
      </div>
    </header>

    <main class="main">
      <div class="card">
        <div class="card-header">
          <h2 class="card-title">List of Submits</h2>
          <p class="card-desc">Review, approve, or return FO submissions and manage the review process efficiently.</p>
        </div>

        <div class="tabs" id="statusTabs">
          <button class="tab active" data-status="UNDER REVIEW">Under Review</button>
        </div>

        <div class="toolbar">
          <div class="filter-group">
            <span class="filter-label">Municipality / City:</span>
            <select id="lguFilter" class="filter-select">
              <option value="">All Municipalities / Cities</option>
            </select>
          </div>
        </div>

        <div class="table-wrap">
          <div id="tableContainer"></div>
        </div>

        <div class="footer">
          <span id="countLabel">0 entries</span>
          <div class="pager">
            <button class="pager-btn" id="previous">Previous</button>
            <span id="pageLabel">1 / 1</span>
            <button class="pager-btn" id="next">Next</button>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<div class="modal" id="detailModal">
  <div class="modal-card">
    <div class="modal-head">
      <h2 id="detailTitle">Submission Details</h2>
      <button class="close-btn" id="closeModal">&times;</button>
    </div>
    <div class="modal-body" id="detailBody"></div>
    <div class="return-box" id="returnBox">
      <label for="returnReason">Reason or remarks for returning this report:</label>
      <textarea id="returnReason" maxlength="2000" placeholder="Explain what the FO must correct..."></textarea>
      <div class="return-actions">
        <button class="btn btn-secondary" type="button" onclick="closeReturnBox()">Cancel</button>
        <button class="btn btn-success" type="button" onclick="submitAction('APPROVE')">Approve</button>
        <button class="btn btn-primary" type="button" onclick="submitAction('RETURN')">Submit Return</button>
      </div>
    </div>
    <div class="modal-actions" id="modalActions"></div>
  </div>
</div>

<script>
const API = '../shared/auth/po_reports_api.php';
let allRows = [], filteredRows = [], page = 1, selectedItem = null, modalCanReview = false;
const PAGE_SIZE = 10;
const $ = (id) => document.getElementById(id);
const esc = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[c]));

function formatDate(dateStr) {
  if (!dateStr) return 'Not submitted';
  const d = new Date(String(dateStr).replace(' ', 'T'));
  return isNaN(d.getTime()) ? esc(dateStr) : d.toLocaleString([], { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function isOverdueSubmission(response, report) {
  const responseAt = response && (response.response_at || response.fo_response_at);
  const dueAt = report && (report.due_at || response && response.due_at);
  if (!responseAt || !dueAt) return false;
  const responseTs = new Date(String(responseAt).replace(' ', 'T')).getTime();
  const dueTs = new Date(String(dueAt).replace(' ', 'T')).getTime();
  return !isNaN(responseTs) && !isNaN(dueTs) && responseTs > dueTs;
}

function getPriorityBadge(priority) {
  const p = String(priority || '').toUpperCase();
  if (p === 'HIGH') return '<span class="priority-badge priority-high">High</span>';
  if (p === 'MEDIUM') return '<span class="priority-badge priority-medium">Medium</span>';
  return '<span class="priority-badge priority-low">Low</span>';
}

function getStatusClass(status) {
  const s = String(status || '').toUpperCase();
  if (s === 'APPROVED') return 'status-approved';
  if (s === 'RETURNED') return 'status-returned';
  if (s === 'OVERDUE') return 'status-overdue';
  if (s === 'UNDER REVIEW' || s === 'SUBMITTED') return 'status-under-review';
  return 'status-pending';
}

async function load() {
  const container = $('tableContainer');
  container.innerHTML = '<div class="table-empty">Loading submissions...</div>';

  try {
    const response = await fetch(API + '?action=list', { credentials: 'same-origin' });
    const data = await response.json();
    if (!data.success) throw new Error(data.error || 'Unable to load submissions.');

    allRows = (data.reports || []).filter(row => {
      const status = String(row.status || '').toUpperCase();
      return ['UNDER REVIEW', 'SUBMITTED', 'OVERDUE'].includes(status);
    });

    const lguFilter = $('lguFilter');
    if (lguFilter) {
      const current = lguFilter.value;
      const lgus = [...new Set((data.lgus || []).filter(Boolean))].sort();
      lguFilter.innerHTML = '<option value="">All Municipalities / Cities</option>' + lgus.map(lg => `<option value="${esc(lg)}">${esc(lg)}</option>`).join('');
      if (current && [...lguFilter.options].some(opt => opt.value === current)) lguFilter.value = current;
    }

    page = 1;
    filter();
  } catch (error) {
    container.innerHTML = '<div class="table-empty">' + esc(error.message) + '</div>';
  }
}

function filter() {
  const searchTerm = ($('search') ? $('search').value : '').toLowerCase().trim();
  const lgu = $('lguFilter') ? $('lguFilter').value : '';
  const status = document.querySelector('.tab.active')?.dataset.status || 'UNDER REVIEW';

  filteredRows = allRows.filter(row => {
    const title = (row.title || '').toLowerCase();
    const ref = (row.reference_number || '').toLowerCase();
    const lguName = (row.lgu_name || '').toLowerCase();
    const submitted = (row.full_name || row.assigned_name || '').toLowerCase();
    const haystack = `${title} ${ref} ${lguName} ${submitted}`;
    const rowStatus = String(row.status || '').toUpperCase();

    const matchSearch = !searchTerm || haystack.includes(searchTerm);
    const matchLgu = !lgu || row.lgu_name === lgu;
    const matchStatus = !status || rowStatus === status || (status === 'UNDER REVIEW' && ['SUBMITTED', 'OVERDUE'].includes(rowStatus));

    return matchSearch && matchLgu && matchStatus;
  });

  page = 1;
  render();
}

function render() {
  const totalPages = Math.max(1, Math.ceil(filteredRows.length / PAGE_SIZE));
  page = Math.min(page, totalPages);
  const start = (page - 1) * PAGE_SIZE;
  const visible = filteredRows.slice(start, start + PAGE_SIZE);

  const container = $('tableContainer');
  if (!visible.length) {
    container.innerHTML = '<div class="table-empty">No submissions match the selected filters.</div>';
    updateFooter(0, totalPages);
    return;
  }

  let html = '<table class="table"><thead><tr><th>SUBMISSION #</th><th>TITLE</th><th>SUBMITTED BY (FO)</th><th>MUNICIPALITY / CITY</th><th>DATE & TIME SUBMITTED</th><th>STATUS</th><th>PRIORITY</th><th>DUE DATE</th><th>LAST UPDATED</th></tr></thead><tbody>';
  
  visible.forEach(row => {
    const refNum = esc(row.reference_number || 'N/A');
    const title = esc(row.title || 'Untitled');
    const submittedByList = Array.isArray(row.recipients)
      ? row.recipients
          .filter(item => item && item.response_at && (item.full_name || item.username || item.lgu_name) && !['APPROVED', 'RETURNED'].includes(String(item.response_status || item.status || '').toUpperCase()))
          .map((item, index) => ({
            rank: index + 1,
            name: item.full_name || item.username || item.lgu_name || 'FO User',
            userId: item.user_id || 0
          }))
      : [];
    const primarySubmitter = submittedByList[0]?.name || row.full_name || row.assigned_name || 'Unknown';
    const submittedByCell = submittedByList.length > 1
      ? `<details class="submitted-by-dropdown" onclick="event.stopPropagation();"><summary onclick="event.stopPropagation();">${esc(`${submittedByList[0].rank}. ${primarySubmitter}`)}</summary><div class="submitted-by-dropdown-panel" onclick="event.stopPropagation();">${submittedByList.map(item => `<div class="submitted-by-dropdown-item" onclick="event.stopPropagation(); openDetail('${row.id}', '${item.userId || 0}')"><span style="display:inline-block; min-width:22px; font-weight:700; color:#1f3b78;">${item.rank}.</span> ${esc(item.name)}</div>`).join('')}</div></details>`
      : `<span>${esc(primarySubmitter)}</span>`;
    const muni = esc(row.lgu_name || 'Municipality / City');
    const submitTime = formatDate(row.response_at || row.fo_response_at);
    const status = String(row.status || 'PENDING').toUpperCase();
    const priority = esc(row.priority || 'MEDIUM');
    const dueDate = formatDate(row.due_at);
    const updated = formatDate(row.updated_at);

    html += `<tr onclick="event.stopPropagation(); openRankDetail('${row.id}')">
      <td class="cell-num">${refNum}</td>
      <td class="cell-title">${title}</td>
      <td class="cell-submitted-by">${submittedByCell}</td>
      <td>${muni}</td>
      <td>${submitTime}</td>
      <td><span class="cell-status ${getStatusClass(status)}">${esc(status)}</span></td>
      <td>${getPriorityBadge(priority)}</td>
      <td>${dueDate}</td>
      <td>${updated}</td>
    </tr>`;
  });

  html += '</tbody></table>';
  container.innerHTML = html;
  updateFooter(filteredRows.length, totalPages);
}

function updateFooter(total, totalPages) {
  const counter = $('countLabel');
  const pageLabel = $('pageLabel');
  if (counter) counter.textContent = `Showing 1 to ${Math.min(PAGE_SIZE, total)} of ${total} entries`;
  if (pageLabel) pageLabel.textContent = `${page} / ${totalPages}`;
}

function renderModalActions() {
  const actions = $('modalActions');
  if (!actions) return;

  if (modalCanReview && $('returnBox')?.classList.contains('active')) {
    actions.style.display = 'none';
    return;
  }

  if (modalCanReview) {
    actions.style.display = 'flex';
    actions.innerHTML = `
      <button class="btn btn-secondary" type="button" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary" type="button" onclick="submitAction('APPROVE')">Approve</button>
    `;
    return;
  }

  actions.style.display = 'flex';
  actions.innerHTML = '<button class="btn btn-primary" type="button" onclick="closeModal()">OK</button>';
}

function closeModal() {
  const modal = $('detailModal');
  if (modal) modal.classList.remove('active');
  selectedItem = null;
  modalCanReview = false;
  const reason = $('returnReason');
  if (reason) reason.value = '';
  const box = $('returnBox');
  if (box) box.classList.remove('active');
  renderModalActions();
}

async function logout() {
  try {
    await fetch('../shared/auth/logout.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    });
  } catch (error) {
    // Ignore logout request errors and continue to redirect.
  }

  window.location.href = '../../index.html';
}

function openReturnBox() {
  const box = $('returnBox');
  if (!box || !modalCanReview) return;
  box.classList.add('active');
  renderModalActions();
  const reason = $('returnReason');
  if (reason) {
    reason.value = reason.value || '';
    setTimeout(() => reason.focus(), 50);
  }
}

function closeReturnBox() {
  const box = $('returnBox');
  if (!box) return;
  box.classList.remove('active');
  const reason = $('returnReason');
  if (reason) reason.value = '';
  renderModalActions();
}

function toggleReturnBox() {
  const box = $('returnBox');
  if (!box) return;
  if (box.classList.contains('active')) {
    closeReturnBox();
    return;
  }
  openReturnBox();
}

async function openDetail(id, recipient) {
  if (!id) return;
  selectedItem = { id, recipient: recipient || 0 };
  const modal = $('detailModal');
  const body = $('detailBody');
  const title = $('detailTitle');
  if (modal) modal.classList.add('active');
  if (title) title.textContent = 'Submission Details';
  if (body) body.innerHTML = '<div class="table-empty">Loading submission details...</div>';
  const box = $('returnBox');
  if (box) box.classList.remove('active');
  modalCanReview = false;
  renderModalActions();

  try {
    const resp = await fetch(`${API}?action=detail&id=${encodeURIComponent(id)}&recipient_user_id=${encodeURIComponent(recipient || 0)}`, { credentials: 'same-origin' });
    const data = await resp.json();
    if (!data.success) throw new Error(data.error);

    const report = data.report || {};
    const response = data.response || data.responses?.[0] || {};
    const documents = data.documents || [];
    const isOverdue = isOverdueSubmission(response, report);
    const statusLabel = isOverdue ? 'OVERDUE' : String(response.status || (response.response_at ? 'UNDER REVIEW' : 'PENDING')).toUpperCase();
    const canReview = ['SUBMITTED', 'OVERDUE', 'UNDER REVIEW'].includes(statusLabel);
    modalCanReview = canReview;

    let documentsHtml = '';
    if (documents.length > 0) {
      documentsHtml = `<div class="section"><h3 class="section-title">Attached Documents</h3><div class="documents-list">`;
      documents.forEach(doc => {
        const fileSize = doc.file_size ? ` (${(doc.file_size / 1024).toFixed(1)} KB)` : '';
        documentsHtml += `<div class="document-item"><div class="document-main"><a href="../shared/auth/download_document.php?id=${encodeURIComponent(doc.id)}&mode=inline" target="_blank" rel="noopener noreferrer" class="document-link"><span class="document-icon"></span><span class="document-name">${esc(doc.original_name)}</span><span class="document-size">${fileSize}</span></a></div><a href="../shared/auth/download_document.php?id=${encodeURIComponent(doc.id)}&mode=inline" target="_blank" rel="noopener noreferrer" class="document-open-btn">Open</a></div>`;
      });
      documentsHtml += `</div></div>`;
    }

    if (body) {
      const overdueText = isOverdue
        ? `<div style="margin-bottom:18px; padding:14px 16px; border:1px solid #f1b5b6; border-left:5px solid #d93025; background:#fff0f0; color:#a12727; border-radius:8px; font-weight:700;">
             <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">OVERDUE SUBMISSION</div>
             <div style="font-size:.92rem; font-weight:600;">The FO submitted this response after the due date set by the PO.</div>
           </div>`
        : '';
      const overdueDetails = isOverdue
        ? `<div style="margin-top:12px; padding:12px 14px; border:1px solid #f1b5b6; border-radius:8px; background:#fff7f7; color:#7c1d1d; font-size:.92rem; line-height:1.5;">
             <strong>Detected automatically:</strong><br>
             FO submitted at ${formatDate(response.response_at)}<br>
             Due date set by PO: ${formatDate(report.due_at)}
           </div>`
        : '';

      body.innerHTML = `
        ${overdueText}
        <div class="detail-grid">
          <div class="detail-row"><span class="detail-label">Municipality / City</span><span class="detail-value">${esc(response.lgu_name || report.creator_office || 'Municipality / City')}</span></div>
          <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value"><span class="cell-status ${getStatusClass(statusLabel)}">${esc(statusLabel)}</span>${isOverdue ? '<div style="display:block; margin-top:6px; color:#7c1d1d; font-size:.75rem; font-weight:600;">Detected automatically</div>' : ''}</span></div>
          <div class="detail-row"><span class="detail-label">Date Submitted</span><span class="detail-value">${formatDate(response.response_at)}</span></div>
          <div class="detail-row"><span class="detail-label">Date Reviewed / Approved</span><span class="detail-value">${formatDate(response.reviewed_at || (response.status === 'APPROVED' ? report.completed_at : null))}</span></div>
          <div class="detail-row"><span class="detail-label">Reference Number</span><span class="detail-value">${esc(report.reference_number || 'N/A')}</span></div>
          <div class="detail-row"><span class="detail-label">PO Due Date</span><span class="detail-value">${formatDate(report.due_at)}</span></div>
        </div>
        ${overdueDetails}
        <div class="section"><h3 class="section-title">Report Information</h3><p class="section-text">${esc(report.details || 'No report description provided.')}</p></div>
        <div class="section"><h3 class="section-title">FO Response</h3><p class="section-text">${esc(response.response || report.fo_response || 'No response submitted yet.')}</p></div>
        ${documentsHtml}
      `;
    }

    renderModalActions();
    if (canReview) {
      openReturnBox();
    } else {
      closeReturnBox();
    }
  } catch (error) {
    if (body) body.innerHTML = '<div class="table-empty">' + esc(error.message) + '</div>';
  }
}

function openRankDetail(reportId) {
  const modal = $('detailModal');
  const title = $('detailTitle');
  const body = $('detailBody');
  const row = (allRows || []).find(item => String(item.id) === String(reportId)) || (filteredRows || []).find(item => String(item.id) === String(reportId));
  if (!modal || !body || !row) return;

  const recipients = Array.isArray(row.recipients)
    ? row.recipients
        .filter(item => item && item.response_at && (item.full_name || item.username || item.lgu_name) && !['APPROVED', 'RETURNED'].includes(String(item.response_status || item.status || '').toUpperCase()))
        .sort((a, b) => new Date(a.response_at) - new Date(b.response_at))
        .map((item, index) => ({
          rank: index + 1,
          municipality: item.lgu_name || item.office_name || 'Municipality / City',
          name: item.full_name || item.username || 'FO User',
          userId: item.user_id || 0
        }))
    : [];

  if (title) title.textContent = 'FO Submission Rank';
  modal.classList.add('active');
  body.innerHTML = `
    <div style="padding: 8px 0;">
      <div style="font-size:1.08rem; color:var(--muted); margin-bottom:18px; font-weight:500;">Ranking of FO submissions by municipality/city.</div>
      ${recipients.length ? recipients.map((item, idx) => `
        <div onclick="event.stopPropagation(); openDetail('${reportId}', '${item.userId || 0}')" style="cursor:pointer; display:flex; align-items:center; gap:16px; padding:18px 18px; margin-bottom:16px; border:1px solid var(--border); border-radius:12px; background:${idx % 2 === 0 ? '#edf5ff' : '#f1f7ed'}; box-shadow: inset 0 0 0 1px rgba(16,59,131,0.04);">
          <div style="width:52px; height:52px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:800; color:${idx % 2 === 0 ? '#103b83' : '#107c4e'}; background:${idx % 2 === 0 ? '#dfeafc' : '#dfeee3'};">${item.rank}</div>
          <div style="display:flex; flex-direction:column; justify-content:center; min-width:0; flex:1;">
            <div style="font-size:1.1rem; font-weight:800; color:var(--text); margin-bottom:4px;">${esc(item.municipality)}</div>
            <div style="font-size:1.05rem; color:var(--text); font-weight:600;">${esc(item.name)}</div>
          </div>
        </div>
      `).join('') : '<div class="table-empty">No FO submissions are available for this report.</div>'}
    </div>
  `;

  modalCanReview = false;
  const box = $('returnBox');
  if (box) box.classList.remove('active');
  renderModalActions();
  const actions = $('modalActions');
  if (actions) {
    actions.style.display = 'none';
    actions.innerHTML = '';
  }
}

async function submitAction(decision) {
  if (!selectedItem) return;

  if (decision === 'RETURN') {
    const reason = ($('returnReason') ? $('returnReason').value.trim() : '');
    if (!reason) {
      alert('Please enter a return reason.');
      return;
    }
    if (!confirm('Are you sure you want to return this submission to the FO?')) return;
    sendReview('RETURN', reason);
    return;
  }

  if (decision === 'APPROVE') {
    if (!confirm('Are you sure you want to approve this submission?')) return;
    sendReview('APPROVE', '');
  }
}

async function sendReview(decision, reason) {
  const btns = document.querySelectorAll('.modal-actions .btn');
  btns.forEach(b => b.disabled = true);
  const origText = [];
  btns.forEach((b, i) => { origText[i] = b.textContent; b.textContent = 'Processing...'; });

  try {
    const resp = await fetch(API + '?action=review', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ id: selectedItem.id, recipient_user_id: Number(selectedItem.recipient), decision, reason })
    });
    const result = await resp.json();
    if (!result.success) throw new Error(result.error);
    alert(decision === 'APPROVE' ? 'Submission approved successfully!' : 'Submission returned successfully!');
    closeModal();
    await load();
  } catch (error) {
    alert(error.message || 'The action failed. Please try again.');
    btns.forEach((b, i) => { b.disabled = false; b.textContent = origText[i]; });
  }
}

// Event Listeners
if ($('search')) $('search').addEventListener('input', filter);
if ($('lguFilter')) $('lguFilter').addEventListener('change', filter);
if ($('refresh')) $('refresh').addEventListener('click', load);
if ($('previous')) $('previous').addEventListener('click', () => { if (page > 1) { page--; render(); } });
if ($('next')) $('next').addEventListener('click', () => { const tp = Math.max(1, Math.ceil(filteredRows.length / PAGE_SIZE)); if (page < tp) { page++; render(); } });
if ($('closeModal')) $('closeModal').addEventListener('click', closeModal);
if ($('detailModal')) $('detailModal').addEventListener('click', e => { if (e.target.id === 'detailModal') closeModal(); });

document.querySelectorAll('.tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    filter();
  });
});

// Load data on page load
load();
</script>
</body>
</html>
<?php
require_once __DIR__ . '/auth/middleware.php';
require_role('ADMIN');
$pdo = db_connect();
$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$roleCounts = ['ADMIN' => 0, 'FO USER' => 0, 'PO USER' => 0];
foreach ($pdo->query('SELECT role, COUNT(*) AS count FROM users GROUP BY role') as $row) $roleCounts[$row['role']] = (int) $row['count'];
$users = $pdo->query('SELECT id, username, email, role, full_name, office_name, is_active, created_at FROM users ORDER BY username ASC')->fetchAll();
$offices = ['ADMIN' => 'DILG Central Office', 'FO USER' => 'DILG Field Office', 'PO USER' => 'DILG Provincial Office'];
$officeOptions = $pdo->query('SELECT name FROM offices WHERE is_active = 1 ORDER BY name ASC')->fetchAll(PDO::FETCH_COLUMN);
function user_display_name($username) { return ucwords(str_replace(['.', '_', '-'], ' ', $username)); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User Management - DILG SDS</title>
  <link rel="stylesheet" href="styles.css?v=20260814-admin-ref-01">
  <style>
    .admin-portal-shell {
      position: relative;
      display: grid;
      grid-template-columns: 320px minmax(0, 1fr);
      min-height: 100vh;
      transition: grid-template-columns 0.22s ease;
    }
    .admin-sidebar {
      position: relative;
      width: 320px;
      min-width: 320px;
      background: linear-gradient(180deg, rgba(15, 42, 107, 0.98) 0%, rgba(8, 22, 50, 0.96) 100%);
      border-right: 1px solid rgba(255, 255, 255, 0.08);
      transition: width 0.22s ease, min-width 0.22s ease;
    }
    .admin-sidebar__title {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 16px 18px 12px 14px;
      color: rgba(255,255,255,0.96);
      font-weight: 800;
      min-height: 76px;
      box-sizing: border-box;
      position: relative;
      padding-right: 28px;
    }
    .admin-sidebar__brand-logo {
      width: 42px;
      height: 42px;
      object-fit: contain;
      border-radius: 50%;
      background: rgba(255,255,255,0.08);
      padding: 4px;
      box-sizing: border-box;
      flex-shrink: 0;
      align-self: center;
    }
    .admin-sidebar__brand-text {
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 2px;
      min-width: 0;
      line-height: 1.18;
      white-space: normal;
      flex: 1;
    }
    .admin-sidebar__brand-name {
      display: flex;
      flex-direction: column;
      font-size: 0.74rem;
      letter-spacing: 0.01em;
      text-transform: none;
      color: #ffffff;
      font-weight: 700;
      line-height: 1.18;
      white-space: normal;
      overflow: visible;
      gap: 0;
    }
    .admin-sidebar__brand-line {
      display: block;
      line-height: 1.18;
    }
    .admin-sidebar__brand-subtitle {
      font-size: 0.58rem;
      letter-spacing: 0.09em;
      text-transform: uppercase;
      color: rgba(220, 232, 255, 0.8);
      font-weight: 700;
      white-space: nowrap;
      line-height: 1.3;
    }
    .admin-main {
      min-width: 0;
      width: 100%;
      overflow-y: auto;
      overflow-x: hidden;
    }
    .sidebar-collapse-toggle {
      position: absolute;
      top: 18px;
      right: 8px;
      left: auto;
      transform: none;
      width: 18px;
      height: 18px;
      border: 0;
      border-radius: 0;
      background: transparent;
      color: #ffffff;
      box-shadow: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      z-index: 40;
      transition: left 0.22s ease, transform 0.22s ease, opacity 0.22s ease;
      padding: 0;
    }
    .sidebar-collapse-toggle span {
      display: inline-block;
      font-size: 2rem;
      line-height: 1;
      transform: translateX(-1px);
      transition: transform 0.22s ease;
      color: #ffffff;
    }
    body.sidebar-collapsed .admin-portal-shell,
    .sidebar-collapsed .admin-portal-shell {
      grid-template-columns: 90px minmax(0, 1fr);
    }
    .sidebar-collapsed .admin-sidebar {
      width: 90px;
      min-width: 90px;
    }
    .sidebar-collapsed .admin-sidebar .admin-sidebar__title,
    .sidebar-collapsed .admin-sidebar .sidebar-section-label,
    .sidebar-collapsed .admin-sidebar .admin-sidebar__nav a span:last-child,
    .sidebar-collapsed .admin-sidebar .sidebar-settings-link span:last-child,
    .sidebar-collapsed .admin-sidebar .sidebar-logout span:last-child {
      display: none;
    }
    .sidebar-collapsed .admin-sidebar .admin-sidebar__nav a,
    .sidebar-collapsed .admin-sidebar .sidebar-settings-link,
    .sidebar-collapsed .admin-sidebar .sidebar-logout {
      justify-content: center;
      padding-left: 0;
      padding-right: 0;
    }
    .sidebar-collapsed .admin-sidebar .admin-sidebar__nav a .nav-icon,
    .sidebar-collapsed .admin-sidebar .sidebar-settings-link .nav-icon,
    .sidebar-collapsed .admin-sidebar .sidebar-logout .nav-icon {
      margin: 0;
    }
    .sidebar-collapsed .sidebar-collapse-toggle {
      right: 8px;
      left: auto;
      background: transparent;
      color: #ffffff;
      box-shadow: none;
    }
    .sidebar-collapsed .sidebar-collapse-toggle span {
      transform: rotate(180deg) translateX(-1px);
      color: #ffffff;
    }
    .admin-users-page { min-height: 100%; display: grid; gap: 16px; }
    .admin-users-header, .admin-users-toolbar, .admin-users-table-panel, .admin-users-detail { background: #fff; border: 1px solid #e1e8f2; border-radius: 10px; box-shadow: 0 2px 8px rgba(15,42,107,.04); }
    .admin-users-header { display: flex; justify-content: space-between; align-items: center; padding: 22px 24px; gap: 18px; }
    .admin-users-header h1 { margin: 0 0 5px; color: #12264d; font-size: 1.6rem; }
    .admin-users-header p { margin: 0; color: #64748b; font-size: .85rem; }
    .admin-users-add { border: 0; border-radius: 5px; padding: 11px 16px; background: #1f5fcf; color: #fff; font-weight: 700; cursor: pointer; }
    .admin-users-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .admin-users-summary article { display: flex; align-items: center; gap: 12px; padding: 16px; background: #fff; border: 1px solid #e1e8f2; border-radius: 9px; }
    .admin-users-summary span { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 50%; background: #e5efff; color: #1f5fcf; font-weight: 800; }
    .admin-users-summary article:nth-child(2) span { background: #d9f7eb; color: #0b9d68; }
    .admin-users-summary article:nth-child(3) span { background: #fff0d2; color: #d98200; }
    .admin-users-summary article:nth-child(4) span { background: #efe1ff; color: #8138d4; }
    .admin-users-summary small, .admin-users-summary strong { display: block; }
    .admin-users-summary small { color: #64748b; font-size: .68rem; font-weight: 800; text-transform: uppercase; }
    .admin-users-summary strong { color: #12264d; font-size: 1.35rem; }
    .admin-users-toolbar { display: grid; grid-template-columns: 1.6fr repeat(3, 1fr) auto; gap: 10px; padding: 14px 16px; }
    .admin-users-toolbar input, .admin-users-toolbar select, .admin-users-toolbar button { min-width: 0; padding: 10px; border: 1px solid #d6e0ec; border-radius: 5px; background: #fff; color: #334155; font: inherit; font-size: .78rem; }
    .admin-users-toolbar button { cursor: pointer; }
    .admin-users-content { display: grid; grid-template-columns: minmax(0, 1fr) 280px; gap: 14px; }
    .admin-users-table-panel { overflow: hidden; }
    .admin-users-table-panel h2, .admin-users-detail h2 { margin: 0; padding: 15px 16px; color: #12264d; font-size: .86rem; }
    .admin-users-table { width: 100%; border-collapse: collapse; font-size: .72rem; }
    .admin-users-table th { padding: 10px 12px; background: #f8fafc; color: #64748b; text-align: left; font-size: .61rem; text-transform: uppercase; }
    .admin-users-table td { padding: 11px 12px; border-top: 1px solid #edf1f6; color: #334155; vertical-align: middle; }
    .admin-users-table tr:hover { background: #f8fbff; }
    .admin-users-table .user-name { color: #12264d; font-weight: 700; }
    .admin-users-table small { color: #64748b; }
    .user-role, .user-status { display: inline-block; padding: 4px 7px; border-radius: 4px; font-size: .62rem; font-weight: 800; }
    .user-role { background: #e8f0ff; color: #1f5fcf; }
    .user-status { background: #dff8eb; color: #087b4d; }
    .user-action { border: 0; background: transparent; color: #1f5fcf; cursor: pointer; font-weight: 700; padding: 3px; }
    .admin-users-detail { padding-bottom: 18px; }
    .admin-users-detail h2 { border-bottom: 1px solid #edf1f6; }
    .user-avatar-large { width: 48px; height: 48px; display: grid; place-items: center; margin: 18px 18px 10px; border-radius: 50%; background: #e5efff; color: #1f5fcf; font-size: 1.2rem; font-weight: 800; }
    .user-detail-name, .user-detail-role { margin-left: 18px; }
    .user-detail-name { margin-top: 0; margin-bottom: 3px; color: #12264d; font-size: 1rem; }
    .user-detail-role { margin-top: 0; color: #64748b; font-size: .74rem; }
    .user-detail-list { display: grid; gap: 10px; margin: 18px; }
    .user-detail-list div { display: flex; justify-content: space-between; gap: 12px; font-size: .7rem; }
    .user-detail-list dt { color: #64748b; } .user-detail-list dd { margin: 0; color: #334155; text-align: right; font-weight: 600; }
    .user-detail-actions { display: grid; gap: 8px; margin: 18px; }
    .user-detail-actions button { border: 0; border-radius: 5px; padding: 9px; background: #1f5fcf; color: #fff; font-weight: 700; cursor: pointer; }
    .user-detail-actions button:last-child { background: #e8eef8; color: #1f5fcf; }
    .admin-user-modal { position: fixed; inset: 0; display: none; place-items: center; z-index: 2000; }
    .admin-user-modal.is-open { display: grid; }
    .admin-user-modal__backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.45); }
    .admin-user-modal__panel { position: relative; width: min(560px, calc(100vw - 32px)); background: #fff; border: 1px solid #dfe7f2; border-radius: 14px; box-shadow: 0 18px 52px rgba(15, 42, 107, 0.18); padding: 20px 22px 18px; }
    .admin-user-modal__header { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding-bottom: 12px; border-bottom: 1px solid #edf2f8; }
    .admin-user-modal__header h2 { margin: 0; color: #12264d; font-size: 1.5rem; }
    .admin-user-modal__close { border: 0; background: transparent; color: #475569; font-size: 2rem; line-height: 1; cursor: pointer; }
    .admin-user-modal__form { display: grid; gap: 14px; margin-top: 16px; }
    .admin-user-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .admin-user-form-field { display: grid; gap: 7px; }
    .admin-user-form-field--full { grid-column: 1 / -1; }
    .admin-user-form-field label { color: #1d325c; font-size: .82rem; font-weight: 700; }
    .admin-user-form-field input, .admin-user-form-field select { width: 100%; min-height: 44px; border: 1px solid #d6e0ec; border-radius: 8px; padding: 0 12px; background: #fff; color: #1e293b; font: inherit; box-sizing: border-box; }
    .admin-user-form-field input:focus, .admin-user-form-field select:focus { outline: 2px solid rgba(31, 95, 207, 0.18); border-color: #1f5fcf; }
    .admin-user-modal__message { min-height: 18px; font-size: .78rem; color: #b42318; }
    .admin-user-modal__message.is-success { color: #087b4d; }
    .admin-user-modal__actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px; }
    .admin-user-modal__cancel, .admin-user-modal__submit { border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 700; cursor: pointer; }
    .admin-user-modal__cancel { background: #ecf3ff; color: #1f5fcf; }
    .admin-user-modal__submit { background: #1f5fcf; color: #fff; }
    @media (max-width: 1050px) { .admin-users-content { grid-template-columns: 1fr; } .admin-users-detail { order: -1; } }
    @media (max-width: 800px) { .admin-users-summary { grid-template-columns: repeat(2, 1fr); } .admin-users-toolbar { grid-template-columns: 1fr 1fr; } .admin-users-toolbar input { grid-column: 1 / -1; } .admin-users-table { min-width: 760px; } .admin-users-table-panel { overflow-x: auto; } .admin-user-form-grid { grid-template-columns: 1fr; } }
    @media (max-width: 520px) { .admin-users-header { align-items: flex-start; flex-direction: column; } }
  </style>
</head>
<body>
  <div class="admin-portal-shell" id="admin-portal-shell">
    <button type="button" class="sidebar-collapse-toggle" id="sidebar-toggle" aria-label="Collapse sidebar"><span>‹</span></button>
    <?php include __DIR__ . '/admin-sidebar.php'; ?>
    <main class="admin-main">
      <section class="admin-users-page">
        <header class="admin-users-header"><div><h1>User Management</h1><p>Manage system users, roles, permissions, and account status.</p></div><button class="admin-users-add admin-add-button" type="button">+&nbsp; Add New User</button></header>
        <div class="admin-users-summary" aria-label="User summary">
          <article><span>♟</span><div><small>Total Users</small><strong><?= $totalUsers ?></strong></div></article>
          <article><span>◆</span><div><small>Admin Users</small><strong><?= $roleCounts['ADMIN'] ?></strong></div></article>
          <article><span>●</span><div><small>FO Users</small><strong><?= $roleCounts['FO USER'] ?></strong></div></article>
          <article><span>●</span><div><small>PO Users</small><strong><?= $roleCounts['PO USER'] ?></strong></div></article>
        </div>
        <div class="admin-users-toolbar" role="search"><input id="user-search" type="search" placeholder="Search user..." aria-label="Search users"><select id="role-filter" aria-label="Filter by role"><option value="">All Roles</option><option>ADMIN</option><option>FO USER</option><option>PO USER</option></select><select id="office-filter" aria-label="Filter by office"><option value="">All Offices</option><option>DILG Central Office</option><option>DILG Field Office</option><option>DILG Provincial Office</option></select><select id="status-filter" aria-label="Filter by status"><option value="">All Statuses</option><option>Active</option><option>Inactive</option></select><button type="button" id="clear-filters">Clear</button></div>
        <div class="admin-users-content">
          <section class="admin-users-table-panel"><h2>User Accounts</h2><div style="overflow-x:auto"><table class="admin-users-table"><thead><tr><th>Name</th><th>Username / Email</th><th>Role</th><th>Office</th><th>Status</th><th>Last Login</th><th>Actions</th></tr></thead><tbody>
          <?php foreach ($users as $user): $office = $user['office_name'] ?: ($offices[$user['role']] ?? 'DILG Office'); $displayName = $user['full_name'] ?? user_display_name($user['username']); $status = (int)$user['is_active'] === 1 ? 'Active' : 'Inactive'; ?><tr data-user data-id="<?= (int)$user['id'] ?>" data-name="<?= htmlspecialchars($displayName) ?>" data-username="<?= htmlspecialchars($user['username']) ?>" data-email="<?= htmlspecialchars($user['email']) ?>" data-role="<?= htmlspecialchars($user['role']) ?>" data-search="<?= htmlspecialchars(strtolower($displayName.' '.$user['username'].' '.$user['email'].' '.$office)) ?>" data-office="<?= htmlspecialchars($office) ?>" data-status="<?= htmlspecialchars($status) ?>"><td class="user-name"><?= htmlspecialchars($displayName) ?></td><td><?= htmlspecialchars($user['username']) ?><br><small><?= htmlspecialchars($user['email']) ?></small></td><td><span class="user-role"><?= htmlspecialchars($user['role'] === 'ADMIN' ? 'Admin' : substr($user['role'], 0, 2)) ?></span></td><td><?= htmlspecialchars($office) ?></td><td><span class="user-status"><?= $status === 'Active' ? '● Active' : '● Inactive' ?></span></td><td><?= htmlspecialchars(date('M j, Y', strtotime($user['created_at']))) ?><br><?= htmlspecialchars(date('g:i A', strtotime($user['created_at']))) ?></td><td><button type="button" class="user-action" data-view-user>View</button><button type="button" class="user-action" data-edit-user>Edit</button></td></tr><?php endforeach; ?>
          </tbody></table></div></section>
          <?php $first = $users[0] ?? null; $firstOffice = $first ? ($first['office_name'] ?: ($offices[$first['role']] ?? 'DILG Office')) : '-'; ?><aside class="admin-users-detail"><h2>Account Details</h2><div class="user-avatar-large"><?= $first ? htmlspecialchars(strtoupper(substr($first['username'], 0, 1))) : '?' ?></div><h3 class="user-detail-name" id="detail-name"><?= $first ? htmlspecialchars($first['full_name'] ?: user_display_name($first['username'])) : 'No user selected' ?></h3><p class="user-detail-role" id="detail-role"><?= $first ? htmlspecialchars($first['role']) : '' ?></p><dl class="user-detail-list"><div><dt>Username / Email</dt><dd id="detail-email"><?= $first ? htmlspecialchars($first['email']) : '-' ?></dd></div><div><dt>Office</dt><dd id="detail-office"><?= htmlspecialchars($firstOffice) ?></dd></div><div><dt>Status</dt><dd id="detail-status">Active</dd></div><div><dt>Last Login</dt><dd id="detail-login"><?= $first ? htmlspecialchars(date('M j, Y g:i A', strtotime($first['created_at']))) : '-' ?></dd></div></dl><div class="user-detail-actions"><button type="button" id="detail-edit-user">Edit User</button><button type="button" id="detail-reset-password">Reset Password</button><button type="button" id="detail-toggle-status">Disable Account</button></div></aside>
        </div>
      </section>
    </main>
  </div>

  <div class="admin-user-modal" id="user-modal" aria-hidden="true">
    <div class="admin-user-modal__backdrop" data-close-user-modal></div>
    <div class="admin-user-modal__panel" role="dialog" aria-modal="true" aria-labelledby="user-modal-title">
      <div class="admin-user-modal__header">
        <h2 id="user-modal-title">Add New User</h2>
        <button class="admin-user-modal__close" type="button" aria-label="Close add user modal" data-close-user-modal>×</button>
      </div>

      <form id="add-user-form" class="admin-user-modal__form" novalidate>
        <div class="admin-user-form-grid">
          <div class="admin-user-form-field admin-user-form-field--full">
            <label for="new-user-username">Username</label>
            <input id="new-user-username" name="username" type="text" maxlength="100" required>
          </div>

          <div class="admin-user-form-field admin-user-form-field--full">
            <label for="new-user-full-name">Full Name</label>
            <input id="new-user-full-name" name="full_name" type="text" maxlength="150" required>
          </div>

          <div class="admin-user-form-field admin-user-form-field--full">
            <label for="new-user-email">Email</label>
            <input id="new-user-email" name="email" type="email" maxlength="255" required>
          </div>

          <div class="admin-user-form-field">
            <label for="new-user-role">Role</label>
            <select id="new-user-role" name="role" required>
              <option value="ADMIN">ADMIN</option>
              <option value="FO USER">FO USER</option>
              <option value="PO USER">PO USER</option>
            </select>
          </div>

          <div class="admin-user-form-field">
            <label for="new-user-office">Office</label>
            <input id="new-user-office" name="office_name" type="text" value="Assigned by role" list="office-options" readonly>
            <datalist id="office-options"><?php foreach ($officeOptions as $officeOption): ?><option value="<?= htmlspecialchars($officeOption) ?>"><?php endforeach; ?></datalist>
          </div>

          <div class="admin-user-form-field admin-user-form-field--full">
            <label for="new-user-password">Password</label>
            <input id="new-user-password" name="password" type="password" minlength="8" required>
          </div>

          <div class="admin-user-form-field admin-user-form-field--full">
            <label for="new-user-confirm-password">Confirm Password</label>
            <input id="new-user-confirm-password" name="password_confirm" type="password" minlength="8" required>
          </div>
        </div>

        <div id="user-modal-message" class="admin-user-modal__message" aria-live="polite"></div>

        <div class="admin-user-modal__actions">
          <button type="button" class="admin-user-modal__cancel" data-close-user-modal>Cancel</button>
          <button type="submit" class="admin-user-modal__submit">Create User</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) {
      const savedSidebarState = localStorage.getItem('dilgSidebarCollapsed') === 'true';
      if (savedSidebarState) {
        document.body.classList.add('sidebar-collapsed');
      }

      sidebarToggle.addEventListener('click', function () {
        const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('dilgSidebarCollapsed', String(isCollapsed));
      });
    }

    const rows = [...document.querySelectorAll('[data-user]')];
    const controls = ['user-search', 'role-filter', 'office-filter', 'status-filter']
      .map((id) => document.getElementById(id))
      .filter(Boolean);

    const filterUsers = () => {
      if (!rows.length) return;

      const searchInput = controls[0];
      const roleFilter = controls[1];
      const officeFilter = controls[2];
      const statusFilter = controls[3];

      rows.forEach((row) => {
        const text = String(row.dataset.search || '').toLowerCase();
        const searchMatch = !searchInput || !searchInput.value || text.includes(searchInput.value.toLowerCase());
        const roleMatch = !roleFilter || !roleFilter.value || row.dataset.role === roleFilter.value;
        const officeMatch = !officeFilter || !officeFilter.value || row.dataset.office === officeFilter.value;
        const statusMatch = !statusFilter || !statusFilter.value || row.dataset.status === statusFilter.value;
        row.hidden = !(searchMatch && roleMatch && officeMatch && statusMatch);
      });
    };

    controls.forEach((control) => {
      control.addEventListener('input', filterUsers);
      control.addEventListener('change', filterUsers);
    });

    const clearButton = document.getElementById('clear-filters');
    if (clearButton) {
      clearButton.addEventListener('click', () => {
        controls.forEach((control) => { control.value = ''; });
        filterUsers();
      });
    }

    rows.forEach((row) => {
      const button = row.querySelector('[data-view-user]');
      if (!button) return;

      button.addEventListener('click', () => {
        const detailName = document.getElementById('detail-name');
        const detailRole = document.getElementById('detail-role');
        const detailEmail = document.getElementById('detail-email');
        const detailOffice = document.getElementById('detail-office');
        const detailLogin = document.getElementById('detail-login');

        if (!detailName || !detailRole || !detailEmail || !detailOffice || !detailLogin) return;

        selectUser(row);
        detailLogin.textContent = (row.children[5]?.textContent || '').replace(/\s+/g, ' ').trim();
      });
      row.querySelector('[data-edit-user]')?.addEventListener('click', () => editUser(row));
    });

    document.getElementById('detail-edit-user')?.addEventListener('click', () => editUser(selectedUserRow));
    document.getElementById('detail-reset-password')?.addEventListener('click', async () => {
      if (!selectedUserRow) return;
      const password = window.prompt('Enter a new password (at least 8 characters):');
      if (password === null) return;
      if (password.length < 8) { window.alert('Password must be at least 8 characters long.'); return; }
      try { await postUserAction({ action: 'reset_password', id: Number(selectedUserRow.dataset.id), password }); window.alert('Password reset successfully.'); }
      catch (error) { window.alert(error.message); }
    });
    document.getElementById('detail-toggle-status')?.addEventListener('click', async () => {
      if (!selectedUserRow) return;
      const verb = selectedUserRow.dataset.status === 'Active' ? 'disable' : 'enable';
      if (!window.confirm(`Are you sure you want to ${verb} this account?`)) return;
      try { await postUserAction({ action: 'toggle_status', id: Number(selectedUserRow.dataset.id) }); window.location.reload(); }
      catch (error) { window.alert(error.message); }
    });

    const addUserButton = document.querySelector('.admin-users-add');
    const addUserModal = document.getElementById('user-modal');
    const addUserForm = document.getElementById('add-user-form');
    const addUserMessage = document.getElementById('user-modal-message');
    const userRoleField = document.getElementById('new-user-role');
    const officeField = document.getElementById('new-user-office');
    const passwordField = document.getElementById('new-user-password');
    const passwordConfirmField = document.getElementById('new-user-confirm-password');
    let selectedUserRow = rows[0] || null;
    let editingUserId = 0;

    if (userRoleField && officeField) {
      const officeMap = {
        ADMIN: 'DILG Central Office',
        'FO USER': 'DILG Field Office',
        'PO USER': 'DILG Provincial Office'
      };

      const syncOfficeField = () => {
        officeField.value = officeMap[userRoleField.value] || 'Assigned by role';
      };

      userRoleField.addEventListener('change', syncOfficeField);
      syncOfficeField();
    }

    const closeUserModal = () => {
      if (!addUserModal) return;
      addUserModal.classList.remove('is-open');
      addUserModal.setAttribute('aria-hidden', 'true');
      if (addUserForm) addUserForm.reset();
      editingUserId = 0;
      document.getElementById('user-modal-title').textContent = 'Add New User';
      document.querySelector('.admin-user-modal__submit').textContent = 'Create User';
      if (passwordField) passwordField.required = true;
      if (passwordConfirmField) passwordConfirmField.required = true;
      if (officeField) officeField.readOnly = true;
      if (addUserMessage) {
        addUserMessage.textContent = '';
        addUserMessage.classList.remove('is-success');
      }
    };

    const openUserModal = () => {
      if (!addUserModal) return;
      addUserModal.classList.add('is-open');
      addUserModal.setAttribute('aria-hidden', 'false');
      if (userRoleField) {
        userRoleField.value = 'ADMIN';
        if (officeField) officeField.value = 'DILG Central Office';
      }
      document.getElementById('new-user-full-name').value = '';
      setTimeout(() => document.getElementById('new-user-username')?.focus(), 50);
    };

    if (addUserButton) {
      addUserButton.addEventListener('click', openUserModal);
    }

    const selectUser = (row) => {
      selectedUserRow = row;
      document.getElementById('detail-name').textContent = row.dataset.name || '';
      document.getElementById('detail-role').textContent = row.dataset.role || '';
      document.getElementById('detail-email').textContent = row.dataset.email || '';
      document.getElementById('detail-office').textContent = row.dataset.office || '';
      document.getElementById('detail-status').textContent = row.dataset.status || 'Active';
      document.getElementById('detail-toggle-status').textContent = row.dataset.status === 'Active' ? 'Disable Account' : 'Enable Account';
    };

    const editUser = (row) => {
      if (!row) return;
      selectUser(row);
      editingUserId = Number(row.dataset.id);
      document.getElementById('user-modal-title').textContent = 'Edit User';
      document.querySelector('.admin-user-modal__submit').textContent = 'Save Changes';
      document.getElementById('new-user-username').value = row.dataset.username || '';
      document.getElementById('new-user-full-name').value = row.dataset.name || '';
      document.getElementById('new-user-email').value = row.dataset.email || '';
      document.getElementById('new-user-role').value = row.dataset.role || 'FO USER';
      officeField.value = row.dataset.office || 'Assigned by role';
      officeField.readOnly = false;
      passwordField.required = false;
      passwordConfirmField.required = false;
      addUserModal.classList.add('is-open');
      addUserModal.setAttribute('aria-hidden', 'false');
    };

    const postUserAction = async (payload) => {
      const response = await fetch('auth/users_api.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(payload) });
      const result = await response.json().catch(() => ({}));
      if (!response.ok || !result.success) throw new Error(result.error || 'Unable to update user.');
      return result;
    };

    document.addEventListener('click', (event) => {
      if (event.target instanceof HTMLElement && event.target.matches('[data-close-user-modal]')) {
        closeUserModal();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && addUserModal && addUserModal.classList.contains('is-open')) {
        closeUserModal();
      }
    });

    if (addUserForm) {
      addUserForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(addUserForm);
        const username = String(formData.get('username') || '').trim();
        const fullName = String(formData.get('full_name') || '').trim();
        const officeName = String(formData.get('office_name') || '').trim();
        const email = String(formData.get('email') || '').trim();
        const role = String(formData.get('role') || 'FO USER').trim();
        const password = String(formData.get('password') || '');
        const passwordConfirm = String(formData.get('password_confirm') || '');

        if (!username || !fullName || !email || (editingUserId && !officeName) || (!editingUserId && (!password || !passwordConfirm))) {
          if (addUserMessage) {
            addUserMessage.textContent = 'Please complete all required fields.';
            addUserMessage.classList.remove('is-success');
          }
          return;
        }

        if (password && password.length < 8) {
          if (addUserMessage) {
            addUserMessage.textContent = 'Password must be at least 8 characters long.';
            addUserMessage.classList.remove('is-success');
          }
          return;
        }

        if (password && password !== passwordConfirm) {
          if (addUserMessage) {
            addUserMessage.textContent = 'Passwords do not match.';
            addUserMessage.classList.remove('is-success');
          }
          return;
        }

        if (addUserMessage) {
          addUserMessage.textContent = 'Creating user...';
          addUserMessage.classList.remove('is-success');
        }

        try {
          if (editingUserId) {
            await postUserAction({ action: 'update', id: editingUserId, full_name: fullName, username, email, role, office_name: officeName });
            if (addUserMessage) { addUserMessage.textContent = 'User updated successfully.'; addUserMessage.classList.add('is-success'); }
            setTimeout(() => window.location.reload(), 400);
            return;
          }
          const response = await fetch('auth/users_api.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ username, email, role, password })
          });

          const result = await response.json().catch(() => ({ error: 'Unexpected server response.' }));

          if (!response.ok) {
            throw new Error(result.error || 'Unable to create user.');
          }

          if (addUserMessage) {
            addUserMessage.textContent = 'User created successfully.';
            addUserMessage.classList.add('is-success');
          }

          setTimeout(() => {
            closeUserModal();
            window.location.reload();
          }, 600);
        } catch (error) {
          if (addUserMessage) {
            addUserMessage.textContent = error.message || 'Unable to create user.';
            addUserMessage.classList.remove('is-success');
          }
        }
      });
    }
  </script>
</body>
</html>

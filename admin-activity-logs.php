<?php
require_once __DIR__ . '/auth/middleware.php';
require_role('ADMIN');
$pdo = db_connect();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Activity Logs - DILG SDS</title>
  <link rel="stylesheet" href="styles.css?v=20260901-activity-logs-02">
  <style>
    :root {
      --navy-900: #0f2a6b;
      --navy-800: #123a7c;
      --navy-700: #1a4b93;
      --navy-600: #295dbd;
      --blue-500: #1f5fcf;
      --blue-50: #edf4ff;
      --sky-100: #eaf3ff;
      --teal-500: #1da77a;
      --teal-100: #dffaf0;
      --amber-500: #d58f00;
      --amber-100: #fff1cf;
      --red-500: #d94d4d;
      --red-100: #fdeaea;
      --slate-50: #f8fafc;
      --slate-100: #edf2f7;
      --slate-200: #dfe7f0;
      --slate-300: #c9d5e4;
      --slate-500: #64748b;
      --slate-700: #334155;
      --slate-900: #0f172a;
      --bg: #f4f7fb;
      --card: #ffffff;
      --border: rgba(15, 42, 107, 0.08);
      --shadow-sm: 0 6px 18px rgba(17, 24, 39, 0.04);
      --shadow-md: 0 10px 24px rgba(15, 42, 107, 0.08);
      --radius-sm: 10px;
      --radius-md: 14px;
      --radius-lg: 18px;
      --sidebar-width: 320px;
      --sidebar-width-collapsed: 92px;
      --content-max: 1480px;
      --surface-muted: #f8fafc;
    }

    * { box-sizing: border-box; }

    html, body { margin: 0; min-height: 100%; }

    body.admin-dashboard-ref-body {
      margin: 0;
      min-height: 100vh;
      background: var(--bg);
      color: var(--slate-900);
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }

    button, input, select { font: inherit; }

    .admin-portal-shell {
      display: grid;
      grid-template-columns: var(--sidebar-width) minmax(0, 1fr);
      min-height: 100vh;
      background: var(--bg);
      transition: grid-template-columns 0.2s ease;
    }

    .admin-portal-shell.is-collapsed {
      grid-template-columns: var(--sidebar-width-collapsed) minmax(0, 1fr);
    }

    .admin-main {
      min-width: 0;
      height: 100vh;
      overflow: auto;
      background: var(--bg);
    }

    .logs-shell {
      max-width: var(--content-max);
      margin: 0 auto;
      padding: 28px 28px 36px;
    }

    .logs-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 20px;
    }

    .logs-header__title {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .logs-header__title h1 {
      margin: 0;
      color: var(--navy-900);
      font-size: clamp(2rem, 2.7vw, 2.8rem);
      letter-spacing: -0.05em;
      line-height: 1.1;
    }

    .logs-header__meta {
      margin: 0;
      color: var(--slate-500);
      font-size: 0.94rem;
    }

    .logs-header__actions {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
      justify-content: flex-end;
    }

    .header-status {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(31, 95, 207, 0.06);
      border: 1px solid rgba(31, 95, 207, 0.12);
      border-radius: 999px;
      padding: 8px 12px;
      color: var(--navy-900);
      font-size: 0.8rem;
      font-weight: 700;
      min-height: 38px;
    }

    .header-status__dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      background: var(--teal-500);
      box-shadow: 0 0 0 4px rgba(29, 167, 122, 0.12);
    }

    .header-button,
    .header-button--ghost {
      border: 1px solid var(--slate-200);
      background: #fff;
      color: var(--navy-900);
      min-height: 38px;
      padding: 0 14px;
      border-radius: 10px;
      font-weight: 700;
      cursor: pointer;
      transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
    }

    .header-button:hover,
    .header-button:focus-visible,
    .header-button--ghost:hover,
    .header-button--ghost:focus-visible {
      background: var(--blue-50);
      border-color: rgba(31, 95, 207, 0.22);
      transform: translateY(-1px);
      outline: none;
    }

    .header-button {
      background: linear-gradient(180deg, #ffffff 0%, #f3f8ff 100%);
    }

    .header-button--ghost {
      background: #fff;
    }

    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 16px;
      margin-bottom: 18px;
    }

    .kpi-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      padding: 18px 18px 16px;
      position: relative;
      overflow: hidden;
    }

    .kpi-card::before {
      content: "";
      position: absolute;
      inset: 0 auto auto 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, rgba(31, 95, 207, 0.9), rgba(31, 95, 207, 0.25));
      opacity: 0;
    }

    .kpi-card--blue::before { opacity: 1; }
    .kpi-card--green::before { background: linear-gradient(90deg, rgba(29, 167, 122, 0.9), rgba(29, 167, 122, 0.25)); opacity: 1; }
    .kpi-card--red::before { background: linear-gradient(90deg, rgba(217, 77, 77, 0.9), rgba(217, 77, 77, 0.25)); opacity: 1; }
    .kpi-card--purple::before { background: linear-gradient(90deg, rgba(122, 92, 255, 0.9), rgba(122, 92, 255, 0.25)); opacity: 1; }

    .kpi-card__content {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 10px;
    }

    .kpi-card__icon {
      width: 46px;
      height: 46px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      background: var(--blue-50);
      color: var(--navy-700);
      border: 1px solid rgba(31, 95, 207, 0.12);
      flex-shrink: 0;
    }

    .kpi-card--green .kpi-card__icon { background: var(--teal-100); color: var(--teal-500); }
    .kpi-card--red .kpi-card__icon { background: var(--red-100); color: var(--red-500); }
    .kpi-card--purple .kpi-card__icon { background: rgba(123, 92, 255, 0.08); color: #6846d8; }

    .kpi-card__icon svg {
      width: 20px;
      height: 20px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .kpi-card__label {
      display: block;
      color: var(--slate-500);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-size: 0.68rem;
      font-weight: 800;
      margin-bottom: 8px;
    }

    .kpi-card__value {
      color: var(--navy-900);
      font-size: clamp(1.8rem, 2vw, 2.35rem);
      font-weight: 800;
      letter-spacing: -0.04em;
      line-height: 1.1;
    }

    .kpi-card__trend {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: var(--slate-500);
      font-size: 0.74rem;
      font-weight: 700;
      padding-top: 4px;
    }

    .kpi-card__trend--positive { color: var(--teal-500); }
    .kpi-card__trend--negative { color: var(--red-500); }

    .content-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.75fr) minmax(300px, 0.95fr);
      gap: 18px;
      margin-bottom: 18px;
    }

    .panel {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      overflow: hidden;
    }

    .chart-panel {
      padding: 18px 18px 12px;
    }

    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }

    .panel-header__title {
      margin: 0;
      color: var(--navy-900);
      font-size: 1.05rem;
      font-weight: 800;
    }

    .panel-header__meta {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--slate-500);
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 800;
    }

    .chart-legend {
      display: flex;
      align-items: center;
      gap: 18px;
      margin-top: 8px;
      color: var(--slate-500);
      font-size: 0.72rem;
      font-weight: 700;
    }

    .legend-swatch {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      display: inline-block;
      margin-right: 6px;
      vertical-align: middle;
    }

    .legend-swatch--blue { background: #1f5fcf; }
    .legend-swatch--green { background: #1da77a; }

    .chart-wrap {
      width: 100%;
      min-height: 260px;
      border-radius: 12px;
      background: linear-gradient(180deg, rgba(31, 95, 207, 0.02), rgba(31, 95, 207, 0.0));
      border: 1px solid rgba(15, 42, 107, 0.04);
      padding: 8px 8px 0;
      position: relative;
    }

    .chart-svg {
      width: 100%;
      height: 240px;
      display: block;
    }

    .filter-panel {
      padding: 18px;
    }

    .toolbar-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
      margin-bottom: 12px;
    }

    .field {
      display: flex;
      flex-direction: column;
      gap: 6px;
      min-width: 0;
    }

    .field--full { grid-column: 1 / -1; }

    .field label {
      color: var(--slate-500);
      font-size: 0.68rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .field input,
    .field select {
      width: 100%;
      min-height: 42px;
      border-radius: 10px;
      border: 1px solid var(--slate-300);
      background: #fff;
      color: var(--slate-900);
      padding: 10px 12px;
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .field input:focus,
    .field select:focus {
      outline: none;
      border-color: rgba(31, 95, 207, 0.72);
      box-shadow: 0 0 0 3px rgba(31, 95, 207, 0.12);
    }

    .toolbar-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 12px;
    }

    .button-secondary,
    .button-primary {
      border: 1px solid transparent;
      border-radius: 10px;
      min-height: 40px;
      padding: 0 14px;
      font-weight: 700;
      cursor: pointer;
      transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
    }

    .button-secondary {
      background: #fff;
      border-color: var(--slate-200);
      color: var(--navy-900);
    }

    .button-secondary:hover,
    .button-secondary:focus-visible {
      background: var(--slate-50);
      outline: none;
    }

    .button-primary {
      background: linear-gradient(180deg, #1f5fcf 0%, #184db5 100%);
      color: #fff;
      box-shadow: 0 8px 16px rgba(31, 95, 207, 0.16);
    }

    .button-primary:hover,
    .button-primary:focus-visible {
      background: linear-gradient(180deg, #234fbd 0%, #163e96 100%);
      outline: none;
    }

    .table-panel {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      overflow: hidden;
    }

    .table-panel__header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      padding: 18px 18px 0;
      margin-bottom: 6px;
    }

    .table-title {
      margin: 0;
      color: var(--navy-900);
      font-size: 1.05rem;
      font-weight: 800;
    }

    .table-controls {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      justify-content: flex-end;
    }

    .search-shell {
      position: relative;
      min-width: min(100%, 420px);
      flex: 1 1 260px;
    }

    .search-shell svg {
      position: absolute;
      top: 50%;
      left: 12px;
      transform: translateY(-50%);
      width: 16px;
      height: 16px;
      stroke: var(--slate-500);
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .search-shell input {
      width: 100%;
      min-height: 40px;
      border-radius: 10px;
      border: 1px solid var(--slate-300);
      background: #fff;
      padding: 0 12px 0 38px;
      color: var(--slate-900);
    }

    .search-shell input:focus {
      outline: none;
      border-color: rgba(31, 95, 207, 0.72);
      box-shadow: 0 0 0 3px rgba(31, 95, 207, 0.12);
    }

    .page-size {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--slate-500);
      font-size: 0.78rem;
      font-weight: 700;
    }

    .page-size select {
      min-height: 36px;
      border-radius: 8px;
      border: 1px solid var(--slate-300);
      background: #fff;
      padding: 0 10px;
      color: var(--slate-900);
    }

    .table-wrap {
      overflow: auto;
      border-top: 1px solid rgba(15, 42, 107, 0.06);
      margin-top: 12px;
    }

    .audit-table {
      width: 100%;
      min-width: 980px;
      border-collapse: collapse;
      background: #fff;
    }

    .audit-table th {
      background: var(--surface-muted);
      color: var(--slate-500);
      text-align: left;
      font-size: 0.7rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      padding: 12px 14px;
      border-bottom: 1px solid rgba(15, 42, 107, 0.08);
      white-space: nowrap;
    }

    .audit-table td {
      padding: 12px 14px;
      border-bottom: 1px solid rgba(15, 42, 107, 0.06);
      color: var(--slate-700);
      font-size: 0.86rem;
      vertical-align: top;
    }

    .audit-table tbody tr {
      transition: background-color 0.15s ease;
    }

    .audit-table tbody tr:hover {
      background: rgba(31, 95, 207, 0.02);
    }

    .audit-table tbody tr.is-expanded {
      background: rgba(31, 95, 207, 0.02);
    }

    .activity-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 0.68rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      border: 1px solid transparent;
      line-height: 1;
      white-space: nowrap;
    }

    .activity-badge--login,
    .activity-badge--success {
      background: rgba(29, 167, 122, 0.1);
      color: #0d8a5e;
      border-color: rgba(29, 167, 122, 0.18);
    }

    .activity-badge--logout,
    .activity-badge--warning {
      background: rgba(213, 143, 0, 0.09);
      color: #9a6a00;
      border-color: rgba(213, 143, 0, 0.14);
    }

    .activity-badge--update,
    .activity-badge--info {
      background: rgba(31, 95, 207, 0.09);
      color: #1a4ca7;
      border-color: rgba(31, 95, 207, 0.16);
    }

    .activity-badge--delete,
    .activity-badge--failed,
    .status-badge--failed {
      background: rgba(217, 77, 77, 0.09);
      color: #b52f2f;
      border-color: rgba(217, 77, 77, 0.14);
    }

    .user-meta {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 0;
    }

    .user-avatar {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: linear-gradient(135deg, #e9f1ff 0%, #dfeafd 100%);
      color: var(--navy-900);
      font-size: 0.7rem;
      font-weight: 800;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      border: 1px solid rgba(31, 95, 207, 0.12);
    }

    .user-meta__text {
      min-width: 0;
    }

    .user-meta__name {
      display: block;
      color: var(--slate-900);
      font-weight: 700;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 180px;
    }

    .user-meta__role {
      display: block;
      font-size: 0.68rem;
      font-weight: 600;
      color: var(--slate-500);
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .ip-pill {
      display: inline-flex;
      align-items: center;
      border-radius: 6px;
      padding: 4px 8px;
      background: var(--slate-50);
      border: 1px solid var(--slate-200);
      color: var(--slate-700);
      font-family: "Consolas", "SFMono-Regular", monospace;
      font-size: 0.74rem;
      font-weight: 700;
    }

    .timestamp-cell {
      min-width: 180px;
    }

    .timestamp-cell__absolute {
      display: block;
      color: var(--slate-500);
      font-size: 0.68rem;
      margin-top: 4px;
    }

    .row-action {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 28px;
      height: 28px;
      background: transparent;
      border: 1px solid rgba(15, 42, 107, 0.08);
      border-radius: 8px;
      color: var(--navy-900);
      cursor: pointer;
    }

    .row-action:hover,
    .row-action:focus-visible {
      background: var(--blue-50);
      outline: none;
    }

    .row-action svg {
      width: 14px;
      height: 14px;
      fill: none;
      stroke: currentColor;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .details-cell {
      display: flex;
      justify-content: flex-start;
      align-items: center;
      gap: 8px;
    }

    .details-row {
      display: none;
      background: rgba(31, 95, 207, 0.02);
    }

    .details-row.is-visible {
      display: table-row;
    }

    .details-panel {
      padding: 14px 18px 18px;
      color: var(--slate-700);
    }

    .details-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
    }

    .detail-box {
      background: #fff;
      border: 1px solid rgba(15, 42, 107, 0.08);
      border-radius: 10px;
      padding: 10px 12px;
    }

    .detail-box__label {
      display: block;
      color: var(--slate-500);
      font-size: 0.66rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .detail-box__value {
      color: var(--slate-900);
      font-size: 0.82rem;
      font-weight: 600;
      word-break: break-word;
    }

    .table-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      padding: 12px 18px 18px;
      color: var(--slate-500);
      font-size: 0.76rem;
      font-weight: 700;
      flex-wrap: wrap;
    }

    .pager {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
    }

    .pager button {
      min-width: 32px;
      min-height: 32px;
      padding: 0 10px;
      border-radius: 8px;
      border: 1px solid var(--slate-200);
      background: #fff;
      color: var(--navy-900);
      font-weight: 700;
      cursor: pointer;
    }

    .pager button.is-active {
      background: var(--blue-500);
      border-color: var(--blue-500);
      color: #fff;
    }

    .pager button:disabled {
      opacity: 0.45;
      cursor: not-allowed;
    }

    .panel-state {
      padding: 24px 16px;
      text-align: center;
      color: var(--slate-500);
    }

    .panel-state__title {
      color: var(--navy-900);
      font-weight: 800;
      margin-bottom: 8px;
    }

    .panel-state--error {
      color: var(--red-500);
    }

    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }

    @media (max-width: 1100px) {
      .content-grid { grid-template-columns: 1fr; }
      .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .logs-shell { padding: 22px 18px 28px; }
    }

    @media (max-width: 860px) {
      .admin-portal-shell {
        grid-template-columns: 1fr;
      }

      .admin-sidebar {
        position: sticky;
        top: 0;
        z-index: 50;
        width: 100%;
        min-width: 100%;
      }

      .admin-portal-shell.is-collapsed {
        grid-template-columns: 1fr;
      }

      .logs-header {
        flex-direction: column;
        align-items: stretch;
      }

      .logs-header__actions {
        justify-content: flex-start;
      }
    }

    @media (max-width: 640px) {
      .kpi-grid,
      .toolbar-grid,
      .details-grid {
        grid-template-columns: 1fr;
      }

      .table-panel__header { flex-direction: column; align-items: stretch; }
      .table-controls { justify-content: stretch; }
      .page-size { width: 100%; justify-content: space-between; }
      .table-footer { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body class="admin-dashboard-ref-body">
  <div class="admin-portal-shell" id="activityShell">
    <?php include __DIR__ . '/admin-sidebar.php'; ?>
    <main class="admin-main" aria-label="Activity logs dashboard">
      <div class="logs-shell">
        <header class="logs-header">
          <div class="logs-header__title">
            <h1>Activity Logs</h1>
            <p class="logs-header__meta">Track all administrative actions and system events in real time.</p>
          </div>
          <div class="logs-header__actions">
            <span class="header-status" aria-live="polite"><span class="header-status__dot" aria-hidden="true"></span>Live</span>
            <button id="refreshButton" class="header-button--ghost" type="button" aria-label="Refresh activity logs">Refresh</button>
            <button id="exportButton" class="header-button" type="button" aria-label="Export filtered activity logs">Export</button>
          </div>
        </header>

        <section class="kpi-grid" aria-label="Activity summary metrics">
          <article class="kpi-card kpi-card--blue" aria-label="Total events">
            <div class="kpi-card__content">
              <div>
                <span class="kpi-card__label">Total Events</span>
                <div class="kpi-card__value" id="totalEventsValue">0</div>
              </div>
              <div class="kpi-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M8 3v2M16 3v2M4 9h16M5 6h14a1 1 0 0 1 1 1v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a1 1 0 0 1 1-1z"/></svg>
              </div>
            </div>
            <div class="kpi-card__trend" aria-label="Based on all loaded audit log records">All loaded audit records</div>
          </article>

          <article class="kpi-card kpi-card--green" aria-label="Today events">
            <div class="kpi-card__content">
              <div>
                <span class="kpi-card__label">Today’s Events</span>
                <div class="kpi-card__value" id="todayEventsValue">0</div>
              </div>
              <div class="kpi-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>
              </div>
            </div>
            <div class="kpi-card__trend" aria-label="Based on audit log records created today">Since midnight</div>
          </article>

          <article class="kpi-card kpi-card--red" aria-label="Failed events">
            <div class="kpi-card__content">
              <div>
                <span class="kpi-card__label">Failed Events</span>
                <div class="kpi-card__value" id="failedEventsValue">0</div>
              </div>
              <div class="kpi-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
              </div>
            </div>
            <div class="kpi-card__trend kpi-card__trend--negative" aria-label="Failed records in the loaded audit logs">Marked failed in audit logs</div>
          </article>

          <article class="kpi-card kpi-card--purple" aria-label="Active users">
            <div class="kpi-card__content">
              <div>
                <span class="kpi-card__label">Active Users</span>
                <div class="kpi-card__value" id="activeUsersValue">0</div>
              </div>
              <div class="kpi-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1"/><circle cx="9.5" cy="7" r="3.5"/><path d="M20 19v-1a4 4 0 0 0-3-3.87M16 4.13a4 4 0 0 1 0 7.75"/></svg>
              </div>
            </div>
            <div class="kpi-card__trend kpi-card__trend--positive" aria-label="Active in last 15 minutes"><span>●</span>Last 15 minutes</div>
          </article>
        </section>

        <section class="content-grid" aria-label="Activity overview and filters">
          <article class="panel chart-panel" aria-label="Seven day activity chart">
            <div class="panel-header">
              <h2 class="panel-header__title">Activity Overview</h2>
              <div class="panel-header__meta" aria-label="Time range"><span>Last 7 Days</span></div>
            </div>
            <div class="chart-wrap">
              <svg id="activityChart" class="chart-svg" viewBox="0 0 760 260" preserveAspectRatio="none" role="img" aria-label="Seven day activity graph"></svg>
            </div>
            <div class="chart-legend" aria-label="Chart legend">
              <span><span class="legend-swatch legend-swatch--blue" aria-hidden="true"></span>Events</span>
              <span><span class="legend-swatch legend-swatch--green" aria-hidden="true"></span>Successful</span>
            </div>
          </article>

          <aside class="panel filter-panel" aria-label="Advanced activity filters">
            <div class="panel-header" style="margin-bottom: 14px;">
              <h2 class="panel-header__title">Filters</h2>
            </div>
            <div class="toolbar-grid">
              <div class="field">
                <label for="filterDateRange">Date Range</label>
                <select id="filterDateRange" aria-label="Filter by date range">
                  <option value="all">All time</option>
                  <option value="today">Today</option>
                  <option value="week">Last 7 days</option>
                  <option value="month">Last 30 days</option>
                </select>
              </div>

              <div class="field">
                <label for="filterAction">Action</label>
                <select id="filterAction" aria-label="Filter by action type">
                  <option value="all">All actions</option>
                  <option value="login">Login</option>
                  <option value="logout">Logout</option>
                  <option value="update">Update</option>
                  <option value="delete">Delete</option>
                  <option value="failed">Failed</option>
                </select>
              </div>

              <div class="field">
                <label for="filterUser">User</label>
                <select id="filterUser" aria-label="Filter by user">
                  <option value="all">All users</option>
                </select>
              </div>

              <div class="field">
                <label for="filterStatus">Status</label>
                <select id="filterStatus" aria-label="Filter by event status">
                  <option value="all">All statuses</option>
                  <option value="success">Success</option>
                  <option value="failed">Failed</option>
                  <option value="warning">Warning</option>
                  <option value="info">Info</option>
                </select>
              </div>

              <div class="field field--full">
                <label for="filterIp">IP Address</label>
                <input id="filterIp" type="text" placeholder="Enter IP address" aria-label="Filter by IP address">
              </div>
            </div>
            <div class="toolbar-actions">
              <button id="clearFiltersButton" type="button" class="button-secondary">Clear filters</button>
              <button id="applyFiltersButton" type="button" class="button-primary">Apply filters</button>
            </div>
          </aside>
        </section>

        <section class="table-panel" aria-label="Audit log list">
          <div class="table-panel__header">
            <h2 class="table-title">System Events</h2>
            <div class="table-controls">
              <label class="search-shell" aria-label="Search log table">
                <input id="tableSearch" type="search" placeholder="Search action, user, IP or status..." aria-label="Search by action, user, IP or status">
              </label>
              <label class="page-size" aria-label="Rows per page">
                <span>Rows</span>
                <select id="pageSizeSelect" aria-label="Rows per page selector">
                  <option value="10" selected>10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                </select>
              </label>
            </div>
          </div>

          <div id="tableState" class="table-wrap" role="status" aria-live="polite"></div>
          <div class="table-footer">
            <span id="summaryText">Showing 0 to 0 of 0 entries</span>
            <div class="pager" id="pager" aria-label="Pagination controls"></div>
          </div>
        </section>
      </div>
    </main>
  </div>

  <script>
    (() => {
      /**
       * @typedef {'all'|'today'|'week'|'month'} DateRange
       * @typedef {'all'|'login'|'logout'|'update'|'delete'|'failed'|'success'|'warning'|'info'} FilterValue
       * @typedef {{
       *   id: number,
       *   user_id: number | null,
       *   username?: string | null,
       *   email?: string | null,
       *   role?: string | null,
       *   action: string,
       *   display_action?: string,
       *   status: string,
       *   ip?: string | null,
       *   user_agent?: string | null,
       *   created_at: string,
       *   meta?: Record<string, any> | null
       * }} ActivityRecord
       */

      const state = {
        records: [],
        currentPage: 1,
        pageSize: 10,
        search: '',
        filters: {
          dateRange: 'all',
          action: 'all',
          user: 'all',
          status: 'all',
          ip: ''
        },
        expandedRows: new Set(),
        lastRefresh: null
      };

      const ids = {
        tableState: document.getElementById('tableState'),
        pager: document.getElementById('pager'),
        summaryText: document.getElementById('summaryText'),
        tableSearch: document.getElementById('tableSearch'),
        pageSizeSelect: document.getElementById('pageSizeSelect'),
        filterDateRange: document.getElementById('filterDateRange'),
        filterAction: document.getElementById('filterAction'),
        filterUser: document.getElementById('filterUser'),
        filterStatus: document.getElementById('filterStatus'),
        filterIp: document.getElementById('filterIp'),
        clearFiltersButton: document.getElementById('clearFiltersButton'),
        applyFiltersButton: document.getElementById('applyFiltersButton'),
        refreshButton: document.getElementById('refreshButton'),
        exportButton: document.getElementById('exportButton'),
        activityChart: document.getElementById('activityChart'),
        totalEventsValue: document.getElementById('totalEventsValue'),
        todayEventsValue: document.getElementById('todayEventsValue'),
        failedEventsValue: document.getElementById('failedEventsValue'),
        activeUsersValue: document.getElementById('activeUsersValue'),
        sidebarToggle: document.getElementById('sidebarToggle'),
        activityShell: document.getElementById('activityShell')
      };

      const fallbackRecords = [
        { id: 1, user_id: 1, username: 'admin', role: 'ADMIN', action: 'LOGIN', status: 'SUCCESS', ip: '192.168.1.10', created_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString() },
        { id: 2, user_id: 1, username: 'admin', role: 'ADMIN', action: 'LOGOUT', status: 'INFO', ip: '192.168.1.10', created_at: new Date(Date.now() - 12 * 60 * 60 * 1000).toISOString() },
        { id: 3, user_id: 3, username: 'juan.delacruz', role: 'FO USER', action: 'UPDATE_CONTENT', status: 'WARNING', ip: '192.168.1.38', created_at: new Date(Date.now() - 18 * 60 * 60 * 1000).toISOString() },
        { id: 4, user_id: 7, username: 'guest', role: 'FO USER', action: 'FAILED_LOGIN', status: 'FAILED', ip: '192.168.1.55', created_at: new Date(Date.now() - 38 * 60 * 60 * 1000).toISOString() },
        { id: 5, user_id: 1, username: 'admin', role: 'ADMIN', action: 'ADD_USER', status: 'SUCCESS', ip: '192.168.1.10', created_at: new Date(Date.now() - 55 * 60 * 60 * 1000).toISOString() },
        { id: 6, user_id: 2, username: 'jane.doe', role: 'ADMIN', action: 'UPDATE_REPORT', status: 'SUCCESS', ip: '192.168.1.19', created_at: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString() },
        { id: 7, user_id: 5, username: 'ritchie', role: 'PO USER', action: 'DELETE_FILE', status: 'WARNING', ip: '192.168.1.77', created_at: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString() },
        { id: 8, user_id: 1, username: 'admin', role: 'ADMIN', action: 'LOGIN', status: 'SUCCESS', ip: '192.168.1.10', created_at: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString() },
        { id: 9, user_id: 8, username: 'may.reyes', role: 'FO USER', action: 'VIEW_REPORT', status: 'INFO', ip: '192.168.1.81', created_at: new Date(Date.now() - 6 * 24 * 60 * 60 * 1000).toISOString() },
        { id: 10, user_id: 1, username: 'admin', role: 'ADMIN', action: 'UPDATE_CONTENT', status: 'SUCCESS', ip: '192.168.1.10', created_at: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString() },
        { id: 11, user_id: 11, username: 'nelson', role: 'PO USER', action: 'FAILED_LOGIN', status: 'FAILED', ip: '10.0.0.13', created_at: new Date(Date.now() - 8 * 24 * 60 * 60 * 1000).toISOString() }
      ];

      const actionMeta = {
        login: { label: 'Login', kind: 'success' },
        logout: { label: 'Logout', kind: 'info' },
        update: { label: 'Update', kind: 'warning' },
        delete: { label: 'Delete', kind: 'warning' },
        failed: { label: 'Failed Login', kind: 'failed' },
        default: { label: 'Action', kind: 'info' }
      };

      function normalizeRecord(raw) {
        const actionText = String(raw.action || raw.display_action || 'UNKNOWN');
        const normalizedAction = actionText.replace(/[_-]+/g, ' ').trim();
        const lowerAction = normalizedAction.toLowerCase();
        const computedStatus = String(raw.status || '').toUpperCase();

        let record = {
          ...raw,
          id: Number(raw.id ?? 0),
          user_id: raw.user_id ?? null,
          username: raw.username || 'System',
          role: raw.role || 'SYSTEM',
          action: normalizedAction,
          display_action: normalizedAction,
          status: computedStatus || inferStatus(lowerAction),
          ip: raw.ip || 'Unknown',
          created_at: raw.created_at || new Date().toISOString(),
          meta: raw.meta || null
        };

        return record;
      }

      function inferStatus(action) {
        if (action.includes('failed') || action.includes('denied') || action.includes('error')) return 'FAILED';
        if (action.includes('logout') || action.includes('view')) return 'INFO';
        if (action.includes('update') || action.includes('edit') || action.includes('delete')) return 'WARNING';
        return 'SUCCESS';
      }

      function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char] || char));
      }

      function formatRelativeTime(dateValue) {
        const diffMs = Date.now() - new Date(dateValue).getTime();
        const diffMinutes = Math.max(1, Math.round(diffMs / 60000));
        if (diffMinutes < 60) return `${diffMinutes} min ago`;
        const diffHours = Math.round(diffMinutes / 60);
        if (diffHours < 24) return `${diffHours} hour${diffHours === 1 ? '' : 's'} ago`;
        const diffDays = Math.round(diffHours / 24);
        if (diffDays < 7) return `${diffDays} day${diffDays === 1 ? '' : 's'} ago`;
        return new Date(dateValue).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
      }

      function formatAbsoluteTime(dateValue) {
        return new Date(dateValue).toLocaleString('en-US', {
          month: 'short',
          day: 'numeric',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit'
        });
      }

      function getActionType(action) {
        const lower = String(action || '').toLowerCase();
        if (lower.includes('login')) return 'login';
        if (lower.includes('logout')) return 'logout';
        if (lower.includes('update') || lower.includes('edit')) return 'update';
        if (lower.includes('delete')) return 'delete';
        if (lower.includes('failed')) return 'failed';
        return 'default';
      }

      function getStatusClass(status) {
        const normalized = String(status || '').toLowerCase();
        if (normalized.includes('failed')) return 'status-badge--failed';
        if (normalized.includes('warning')) return 'activity-badge--warning';
        if (normalized.includes('info')) return 'activity-badge--info';
        if (normalized.includes('success')) return 'activity-badge--success';
        return 'activity-badge--info';
      }

      function getBadgeLabel(action, status) {
        const type = getActionType(action);
        const meta = actionMeta[type] || actionMeta.default;
        return meta.label.toUpperCase();
      }

      function humanizeStatus(status) {
        const value = String(status || '').toLowerCase();
        if (value === 'success') return 'Success';
        if (value === 'failed') return 'Failed';
        if (value === 'warning') return 'Warning';
        if (value === 'info') return 'Info';
        return 'Success';
      }

      function setLoadingState() {
        ids.tableState.innerHTML = `
          <div class="panel-state" aria-busy="true">
            <div class="panel-state__title">Loading activity data</div>
            <div>Preparing the latest admin events…</div>
          </div>
        `;
        ids.pager.innerHTML = '';
      }

      function setErrorState(errorMessage = 'Unable to load activity logs.') {
        ids.tableState.innerHTML = `
          <div class="panel-state panel-state--error" aria-live="assertive">
            <div class="panel-state__title">Unable to load data</div>
            <div>${escapeHtml(errorMessage)}</div>
          </div>
        `;
        ids.summaryText.textContent = 'Showing 0 to 0 of 0 entries';
        ids.pager.innerHTML = '';
      }

      function renderKpis(records) {
        const total = records.length;
        const today = records.filter((record) => {
          const createdAt = new Date(record.created_at);
          const now = new Date();
          return createdAt.toDateString() === now.toDateString();
        }).length;

        const failed = records.filter((record) => String(record.status).toLowerCase() === 'failed').length;
        const activeUsers = new Set(records.filter((record) => {
          const createdAt = new Date(record.created_at);
          const diffMs = Date.now() - createdAt.getTime();
          return diffMs <= 1000 * 60 * 15 && record.username;
        }).map((record) => record.username)).size;

        ids.totalEventsValue.textContent = total.toString();
        ids.todayEventsValue.textContent = today.toString();
        ids.failedEventsValue.textContent = failed.toString();
        ids.activeUsersValue.textContent = activeUsers.toString();
      }

      function buildChart(records) {
        const now = new Date();
        const points = [];

        for (let day = 6; day >= 0; day -= 1) {
          const current = new Date(now);
          current.setHours(0, 0, 0, 0);
          current.setDate(current.getDate() - day);
          const eventsForDay = records.filter((record) => {
            const date = new Date(record.created_at);
            return date.toDateString() === current.toDateString();
          }).length;
          points.push({ label: current.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }), value: eventsForDay });
        }

        const width = 760;
        const height = 240;
        const padding = { top: 16, right: 12, bottom: 30, left: 18 };
        const maxValue = Math.max(1, ...points.map((point) => point.value));

        const chartPoints = points.map((point, index) => {
          const x = padding.left + (index * (width - padding.left - padding.right)) / (points.length - 1);
          const y = height - padding.bottom - (point.value / maxValue) * (height - padding.top - padding.bottom);
          return { ...point, x, y };
        });

        const linePath = chartPoints.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ');
        const areaPath = `${linePath} L ${chartPoints[chartPoints.length - 1].x} ${height - padding.bottom} L ${chartPoints[0].x} ${height - padding.bottom} Z`;

        const labels = points.map(({ label }, index) => {
          const x = padding.left + (index * (width - padding.left - padding.right)) / (points.length - 1);
          return `<text x="${x}" y="${height - 8}" text-anchor="middle" fill="#64748b" font-size="10" font-family="Segoe UI, sans-serif">${escapeHtml(label)}</text>`;
        }).join('');

        const yTicks = Array.from({ length: 5 }, (_, tickIndex) => {
          const step = maxValue / 4;
          const value = Math.round(maxValue - (tickIndex * step));
          const y = padding.top + (tickIndex * (height - padding.top - padding.bottom)) / 4;
          return `<g>
            <line x1="${padding.left}" x2="${width - padding.right}" y1="${y}" y2="${y}" stroke="rgba(148,163,184,0.2)" stroke-dasharray="3 4" />
            <text x="${padding.left + 2}" y="${y + 4}" fill="#64748b" font-size="10" font-family="Segoe UI, sans-serif">${value}</text>
          </g>`;
        }).join('');

        ids.activityChart.innerHTML = `
          <defs>
            <linearGradient id="chartFill" x1="0" x2="0" y1="0" y2="1">
              <stop offset="0%" stop-color="rgba(31,95,207,0.22)"/>
              <stop offset="100%" stop-color="rgba(31,95,207,0.01)"/>
            </linearGradient>
          </defs>
          ${yTicks}
          <path d="${areaPath}" fill="url(#chartFill)" />
          <path d="${linePath}" fill="none" stroke="#1f5fcf" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
          ${chartPoints.map((point) => `<circle cx="${point.x}" cy="${point.y}" r="4" fill="#1f5fcf" stroke="#fff" stroke-width="2" />`).join('')}
          ${labels}
        `;
      }

      function applyFilters() {
        state.currentPage = 1;
        renderTable();
      }

      function getFilteredRecords() {
        const searchTerm = state.search.trim().toLowerCase();

        return state.records.filter((record) => {
          const actionText = `${record.action} ${record.username || ''} ${record.ip || ''} ${record.status || ''}`.toLowerCase();
          const matchesSearch = !searchTerm || actionText.includes(searchTerm);

          const matchesAction = state.filters.action === 'all' || (
            state.filters.action === 'failed' ? String(record.status).toLowerCase() === 'failed' : (
              actionText.includes(state.filters.action.toLowerCase())
            )
          );

          const matchesUser = state.filters.user === 'all' || (record.username || '').toLowerCase() === state.filters.user.toLowerCase();
          const matchesStatus = state.filters.status === 'all' || String(record.status).toLowerCase() === state.filters.status.toLowerCase();
          const matchesIp = !state.filters.ip || String(record.ip || '').toLowerCase().includes(state.filters.ip.toLowerCase());

          const recordDate = new Date(record.created_at);
          const now = new Date();
          const diffDays = (now.getTime() - recordDate.getTime()) / (1000 * 60 * 60 * 24);

          let matchesDate = true;
          if (state.filters.dateRange === 'today') {
            matchesDate = recordDate.toDateString() === now.toDateString();
          } else if (state.filters.dateRange === 'week') {
            matchesDate = diffDays <= 7;
          } else if (state.filters.dateRange === 'month') {
            matchesDate = diffDays <= 30;
          }

          return matchesSearch && matchesAction && matchesUser && matchesStatus && matchesIp && matchesDate;
        });
      }

      function populateUserFilter(records) {
        const userNames = [...new Set(records.map((record) => record.username).filter(Boolean))].sort((a, b) => a.localeCompare(b));
        const previousSelection = state.filters.user;

        ids.filterUser.innerHTML = ['<option value="all">All users</option>']
          .concat(userNames.map((user) => `<option value="${escapeHtml(user)}">${escapeHtml(user)}</option>`))
          .join('');

        if (userNames.includes(previousSelection)) {
          ids.filterUser.value = previousSelection;
        } else {
          ids.filterUser.value = 'all';
          state.filters.user = 'all';
        }
      }

      function renderTable() {
        const filtered = getFilteredRecords();
        const pageCount = Math.max(1, Math.ceil(filtered.length / state.pageSize));
        if (state.currentPage > pageCount) state.currentPage = pageCount;

        const startIndex = (state.currentPage - 1) * state.pageSize;
        const endIndex = startIndex + state.pageSize;
        const visibleRecords = filtered.slice(startIndex, endIndex);

        if (!visibleRecords.length) {
          ids.tableState.innerHTML = `
            <div class="panel-state" aria-live="polite">
              <div class="panel-state__title">No activity matches your filters</div>
              <div>Try removing one or more filters or use a broader search term.</div>
            </div>
          `;
          ids.summaryText.textContent = 'Showing 0 to 0 of 0 entries';
          ids.pager.innerHTML = '';
          return;
        }

        const rowsHtml = visibleRecords.map((record) => {
          const isExpanded = state.expandedRows.has(record.id);
          const actionType = getActionType(record.action);
          const badgeClass = actionType === 'failed' ? 'activity-badge--delete' : `activity-badge--${actionMeta[actionType]?.kind || 'info'}`;
          const statusClass = getStatusClass(record.status);
          const initials = (record.username || 'S').split(/\s|[._-]/).filter(Boolean).slice(0, 2).map((part) => part[0]?.toUpperCase() || '').join('') || 'S';

          return `
            <tr class="${isExpanded ? 'is-expanded' : ''}" data-row-id="${record.id}">
              <td>
                <span class="activity-badge ${badgeClass}">${escapeHtml(getBadgeLabel(record.action, record.status))}</span>
              </td>
              <td>
                <div class="user-meta">
                  <span class="user-avatar" aria-hidden="true">${escapeHtml(initials)}</span>
                  <span class="user-meta__text">
                    <span class="user-meta__name">${escapeHtml(record.username || 'System')}</span>
                    <span class="user-meta__role">${escapeHtml(record.role || 'SYSTEM')}</span>
                  </span>
                </div>
              </td>
              <td><span class="ip-pill">${escapeHtml(record.ip || 'Unknown')}</span></td>
              <td>
                <div class="timestamp-cell">
                  <span>${escapeHtml(formatRelativeTime(record.created_at))}</span>
                  <span class="timestamp-cell__absolute">${escapeHtml(formatAbsoluteTime(record.created_at))}</span>
                </div>
              </td>
              <td>
                <span class="activity-badge ${statusClass}">${escapeHtml(humanizeStatus(record.status))}</span>
              </td>
              <td class="details-cell">
                <button class="row-action" type="button" aria-label="Toggle details for ${escapeHtml(record.username || 'System')}" aria-expanded="${isExpanded}">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10l5 5 5-5"/></svg>
                </button>
              </td>
            </tr>
            <tr class="details-row ${isExpanded ? 'is-visible' : ''}">
              <td colspan="6">
                <div class="details-panel">
                  <div class="details-grid">
                    <div class="detail-box">
                      <span class="detail-box__label">User Agent</span>
                      <div class="detail-box__value">${escapeHtml(record.user_agent || 'Not recorded')}</div>
                    </div>
                    <div class="detail-box">
                      <span class="detail-box__label">Location</span>
                      <div class="detail-box__value">${escapeHtml(record.meta?.location || 'Local system')}</div>
                    </div>
                    <div class="detail-box">
                      <span class="detail-box__label">Event Details</span>
                      <div class="detail-box__value">${escapeHtml(record.meta?.message || 'No additional metadata available')}</div>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          `;
        }).join('');

        ids.tableState.innerHTML = `
          <table class="audit-table" aria-label="Activity log records">
            <thead>
              <tr>
                <th scope="col">Action</th>
                <th scope="col">User</th>
                <th scope="col">IP Address</th>
                <th scope="col">Timestamp</th>
                <th scope="col">Status</th>
                <th scope="col" style="width: 48px; text-align: center;">Details</th>
              </tr>
            </thead>
            <tbody>
              ${rowsHtml}
            </tbody>
          </table>
        `;

        const rowButtons = ids.tableState.querySelectorAll('.row-action');
        rowButtons.forEach((button) => {
          button.addEventListener('click', () => {
            const rowElement = button.closest('tr[data-row-id]');
            const rowId = Number(rowElement?.dataset.rowId || 0);
            if (!rowId) return;

            if (state.expandedRows.has(rowId)) {
              state.expandedRows.delete(rowId);
            } else {
              state.expandedRows.add(rowId);
            }
            renderTable();
          });
        });

        const startNumber = filtered.length ? startIndex + 1 : 0;
        const endNumber = Math.min(endIndex, filtered.length);
        ids.summaryText.textContent = `Showing ${startNumber} to ${endNumber} of ${filtered.length} entries`;

        const pagerButtons = ['<button type="button" data-page="prev" aria-label="Previous page" ' + (state.currentPage === 1 ? 'disabled' : '') + '>‹</button>'];
        for (let page = 1; page <= pageCount; page += 1) {
          pagerButtons.push(`<button type="button" class="${page === state.currentPage ? 'is-active' : ''}" data-page="${page}" aria-label="Page ${page}">${page}</button>`);
        }
        pagerButtons.push(`<button type="button" data-page="next" aria-label="Next page" ${state.currentPage === pageCount ? 'disabled' : ''}>›</button>`);
        ids.pager.innerHTML = pagerButtons.join('');

        ids.pager.querySelectorAll('button[data-page]').forEach((button) => {
          button.addEventListener('click', () => {
            const pageValue = button.dataset.page;
            if (pageValue === 'prev') {
              state.currentPage = Math.max(1, state.currentPage - 1);
            } else if (pageValue === 'next') {
              state.currentPage = Math.min(pageCount, state.currentPage + 1);
            } else {
              state.currentPage = Number(pageValue);
            }
            renderTable();
          });
        });
      }

      function resetFilters() {
        state.filters = { dateRange: 'all', action: 'all', user: 'all', status: 'all', ip: '' };
        state.search = '';
        state.currentPage = 1;
        ids.filterDateRange.value = 'all';
        ids.filterAction.value = 'all';
        ids.filterUser.value = 'all';
        ids.filterStatus.value = 'all';
        ids.filterIp.value = '';
        ids.tableSearch.value = '';
        renderTable();
      }

      function syncInputsToState() {
        state.filters.dateRange = ids.filterDateRange.value;
        state.filters.action = ids.filterAction.value;
        state.filters.user = ids.filterUser.value;
        state.filters.status = ids.filterStatus.value;
        state.filters.ip = ids.filterIp.value.trim();
      }

      async function refreshLogs() {
        setLoadingState();
        try {
          const response = await fetch('auth/activity_logs_api.php', {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
          });

          if (!response.ok) {
            throw new Error('Request failed');
          }

          const payload = await response.json();
          const rawRecords = Array.isArray(payload.logs) ? payload.logs : [];
          state.records = rawRecords.map(normalizeRecord);
          state.expandedRows.clear();
          populateUserFilter(state.records);
          renderKpis(state.records);
          buildChart(state.records);
          renderTable();
          state.lastRefresh = new Date();
        } catch (error) {
          state.records = [];
          populateUserFilter(state.records);
          renderKpis(state.records);
          buildChart(state.records);
          renderTable();
          setErrorState('Using sample activity data because the live endpoint is unavailable.');
        }
      }

      ids.pageSizeSelect.addEventListener('change', (event) => {
        state.pageSize = Number(event.target.value || 10);
        state.currentPage = 1;
        renderTable();
      });

      ids.tableSearch.addEventListener('input', (event) => {
        state.search = event.target.value.trim();
        state.currentPage = 1;
        renderTable();
      });

      ids.clearFiltersButton.addEventListener('click', resetFilters);
      ids.applyFiltersButton.addEventListener('click', () => {
        syncInputsToState();
        applyFilters();
      });

      ids.refreshButton.addEventListener('click', refreshLogs);
      ids.exportButton.addEventListener('click', () => {
        const filteredRecords = getFilteredRecords();
        const csvRows = [
          ['Action', 'User', 'IP Address', 'Status', 'Timestamp'],
          ...filteredRecords.map((record) => [record.action, record.username || 'System', record.ip || 'Unknown', record.status, record.created_at])
        ];

        const csv = csvRows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'activity-logs-export.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
      });

      refreshLogs();
    })();
  </script>
</body>
</html>

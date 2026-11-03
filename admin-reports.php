<?php
require_once __DIR__ . '/auth/middleware.php';
require_role('ADMIN');
$pdo = db_connect();
$reportCounts = ['PENDING' => 0, 'SUBMITTED' => 0, 'OVERDUE' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) AS count FROM reports GROUP BY status') as $row) {
  $reportCounts[$row['status']] = (int) $row['count'];
}
$totalReports = array_sum($reportCounts);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reports &amp; Analytics - DILG SDS</title>
  <link rel="stylesheet" href="styles.css?v=20260901-reports-dashboard-01">
  <style>
    :root {
      --navy: #0f2a6b;
      --blue: #1f5fcf;
      --bg: #f5f7fb;
      --card: #ffffff;
      --line: #dfe7f0;
      --muted: #64748b;
      --warning: #f3a62a;
      --success: #2bb673;
      --danger: #e65050;
      --shadow: 0 10px 24px rgba(15,42,107,0.06);
    }

    .admin-main { background: var(--bg); }
    .reports-shell {
      width: 100%;
      max-width: 1400px;
      margin: 0 auto;
      padding: 28px 28px 36px;
      box-sizing: border-box;
    }

    .reports-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 18px;
      margin-bottom: 22px;
    }

    .reports-header h1 {
      margin: 0;
      color: var(--navy);
      font-size: clamp(2rem, 2.5vw, 2.6rem);
      letter-spacing: -0.04em;
    }

    .reports-header p {
      margin: 8px 0 0;
      color: var(--muted);
      font-size: 0.95rem;
    }

    .export-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 42px;
      padding: 0 18px;
      border: 1px solid var(--line);
      background: #fff;
      color: var(--navy);
      border-radius: 10px;
      cursor: pointer;
      font-weight: 700;
      box-shadow: 0 4px 14px rgba(15,42,107,0.04);
    }

    .export-btn:hover, .export-btn:focus-visible {
      background: #f3f8ff;
      outline: none;
      border-color: rgba(31,95,207,0.35);
    }

    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 16px;
      margin-bottom: 20px;
    }

    .kpi-card {
      display: flex;
      gap: 14px;
      align-items: center;
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 14px;
      box-shadow: var(--shadow);
      padding: 18px 18px 16px;
      min-height: 110px;
    }

    .kpi-icon {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .kpi-icon svg {
      width: 24px;
      height: 24px;
      stroke: currentColor;
      fill: none;
      stroke-width: 1.9;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .kpi-icon--blue { background: #edf4ff; color: var(--blue); }
    .kpi-icon--amber { background: #fff5e3; color: #d79d2a; }
    .kpi-icon--green { background: #eafaf1; color: #20a769; }
    .kpi-icon--red { background: #ffe7e7; color: var(--danger); }

    .kpi-meta { flex: 1; min-width: 0; }
    .kpi-label {
      display: block;
      margin-bottom: 8px;
      font-size: 0.68rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--muted);
    }

    .kpi-value {
      display: block;
      font-size: clamp(1.8rem, 2vw, 2.3rem);
      font-weight: 800;
      line-height: 1;
      letter-spacing: -0.04em;
      color: var(--navy);
      margin: 0;
    }

    .kpi-trend {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-top: 8px;
      font-size: 0.7rem;
      font-weight: 700;
      color: #2d7d3a;
    }

    .kpi-trend--amber { color: #b7791f; }
    .kpi-trend--red { color: #bf3030; }

    .kpi-trend svg {
      width: 14px;
      height: 14px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .filterbar {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
      gap: 12px;
      align-items: end;
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 12px;
      box-shadow: var(--shadow);
      padding: 12px 14px;
      margin-bottom: 20px;
    }

    .field {
      display: flex;
      flex-direction: column;
      gap: 6px;
      min-width: 0;
    }

    .field label {
      font-size: 0.68rem;
      font-weight: 800;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      color: var(--muted);
    }

    .field input, .field select {
      width: 100%;
      min-height: 42px;
      box-sizing: border-box;
      border: 1px solid #d4dce8;
      border-radius: 8px;
      background: #fff;
      color: #1e293b;
      padding: 10px 12px;
      font: inherit;
    }

    .field input:focus, .field select:focus {
      border-color: rgba(31,95,207,0.7);
      box-shadow: 0 0 0 3px rgba(31,95,207,0.12);
      outline: none;
    }

    .clear-btn {
      min-height: 42px;
      padding: 0 14px;
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 10px;
      color: var(--navy);
      font-weight: 700;
      cursor: pointer;
    }

    .clear-btn:hover, .clear-btn:focus-visible {
      background: #f8fafc;
      outline: none;
    }

    .chart-grid {
      display: grid;
      grid-template-columns: 1.4fr 1fr 1.1fr;
      gap: 18px;
      margin-bottom: 22px;
    }

    .panel-card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 14px;
      box-shadow: var(--shadow);
      padding: 18px 20px 16px;
    }

    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 10px;
      margin-bottom: 14px;
    }

    .panel-header h2 {
      margin: 0;
      font-size: 1.02rem;
      color: var(--navy);
      letter-spacing: -0.02em;
    }

    .panel-header a {
      text-decoration: none;
      color: var(--blue);
      font-size: 0.76rem;
      font-weight: 700;
    }

    .trend-svg {
      width: 100%;
      height: 170px;
      display: block;
      border-radius: 10px;
      background: linear-gradient(180deg, rgba(31,95,207,0.05), rgba(31,95,207,0));
      border: 1px solid rgba(31,95,207,0.06);
    }

    .chart-legend {
      margin-top: 8px;
      color: var(--muted);
      font-size: 0.75rem;
      font-weight: 700;
    }

    .status-donut {
      display: flex;
      justify-content: center;
      align-items: center;
      padding-top: 8px;
    }

    .donut {
      width: 170px;
      height: 170px;
      border-radius: 50%;
      position: relative;
      display: grid;
      place-items: center;
      background: conic-gradient(#2bb673 0 71.1%, #f3a62a 71.1% 89.6%, #e65050 89.6% 100%);
    }

    .donut::before {
      content: "";
      position: absolute;
      inset: 24px;
      border-radius: 50%;
      background: #fff;
      box-shadow: inset 0 0 0 1px rgba(15,42,107,0.04);
    }

    .donut-center {
      position: relative;
      z-index: 1;
      text-align: center;
    }

    .donut-center strong {
      display: block;
      font-size: 1.8rem;
      line-height: 1;
      letter-spacing: -0.04em;
      color: var(--navy);
    }

    .donut-center span {
      font-size: 0.68rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 800;
    }

    .donut-legend {
      display: grid;
      gap: 10px;
      margin-top: 16px;
    }

    .legend-row {
      display: grid;
      grid-template-columns: auto 1fr auto;
      align-items: center;
      gap: 10px;
      font-size: 0.8rem;
      color: #334155;
    }

    .legend-dot {
      display: inline-block;
      width: 10px;
      height: 10px;
      border-radius: 50%;
    }

    .legend-dot--green { background: var(--success); }
    .legend-dot--amber { background: var(--warning); }
    .legend-dot--red { background: var(--danger); }

    .legend-value {
      color: var(--muted);
      font-size: 0.75rem;
      font-weight: 700;
    }

    .office-bars {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 10px;
      height: 168px;
      padding-top: 10px;
    }

    .bar-col {
      flex: 1;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      align-items: center;
      gap: 8px;
    }

    .bar {
      width: 100%;
      max-width: 44px;
      min-height: 30px;
      border-radius: 8px 8px 0 0;
      background: linear-gradient(180deg, rgba(31,95,207,0.8), rgba(31,95,207,0.95));
    }

    .bar-col span {
      font-size: 0.72rem;
      color: var(--muted);
      font-weight: 700;
      text-align: center;
    }

    .table-card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 14px;
      box-shadow: var(--shadow);
      padding: 18px 18px 8px;
    }

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      margin-bottom: 10px;
    }

    .table-header h2 {
      margin: 0;
      color: var(--navy);
      font-size: 1.05rem;
    }

    .table-header p {
      margin: 4px 0 0;
      color: var(--muted);
      font-size: 0.8rem;
    }

    .table-search {
      position: relative;
      width: min(360px, 100%);
    }

    .table-search svg {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      width: 16px;
      height: 16px;
      stroke: var(--muted);
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .table-search input {
      width: 100%;
      min-height: 40px;
      box-sizing: border-box;
      border: 1px solid #d2dce9;
      border-radius: 8px;
      background: #fff;
      padding: 0 12px 0 38px;
      font: inherit;
      color: #1e293b;
    }

    .table-wrap {
      overflow-x: auto;
      border-top: 1px solid #edf1f6;
      margin-top: 12px;
    }

    .data-table {
      width: 100%;
      border-collapse: collapse;
      min-width: 900px;
    }

    .data-table th {
      background: #f8fafc;
      text-align: left;
      padding: 12px 12px;
      color: var(--muted);
      font-size: 0.68rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      border-bottom: 1px solid #edf1f6;
    }

    .data-table td {
      padding: 13px 12px;
      border-bottom: 1px solid #edf1f6;
      color: #334155;
      font-size: 0.84rem;
      vertical-align: middle;
    }

    .data-table td:first-child {
      color: var(--navy);
      font-weight: 700;
    }

    .report-title {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 0;
    }

    .report-title__icon {
      width: 28px;
      height: 28px;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #edf4ff;
      color: var(--blue);
      flex-shrink: 0;
    }

    .report-title__icon svg {
      width: 16px;
      height: 16px;
      fill: none;
      stroke: currentColor;
      stroke-width: 1.8;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .report-title__meta {
      display: flex;
      flex-direction: column;
      gap: 3px;
      min-width: 0;
    }

    .report-title__meta strong {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 280px;
    }

    .report-title__meta small {
      color: var(--muted);
      font-size: 0.72rem;
    }

    .status-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 86px;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 0.7rem;
      font-weight: 800;
      letter-spacing: 0.02em;
      text-transform: uppercase;
      border: 1px solid transparent;
    }

    .status-pill--pending {
      background: rgba(243,166,42,0.12);
      color: #b36b00;
      border-color: rgba(243,166,42,0.14);
    }
    .status-pill--submitted {
      background: rgba(43,182,115,0.12);
      color: #1d8d5d;
      border-color: rgba(43,182,115,0.18);
    }
    .status-pill--overdue {
      background: rgba(230,80,80,0.12);
      color: #bf3030;
      border-color: rgba(230,80,80,0.14);
    }

    .action-group {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .action-btn {
      border: 1px solid #dbe8ff;
      background: #edf4ff;
      color: var(--navy);
      border-radius: 8px;
      padding: 7px 10px;
      font-size: 0.72rem;
      font-weight: 700;
      cursor: pointer;
    }

    .action-btn:hover, .action-btn:focus-visible {
      background: var(--blue);
      border-color: var(--blue);
      color: #fff;
      outline: none;
    }

    .action-btn--ghost {
      border-color: #e5ecf5;
      background: #ffffff;
      color: var(--navy);
    }

    .table-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      padding: 14px 0 8px;
      color: var(--muted);
      font-size: 0.76rem;
      font-weight: 700;
    }

    .pager {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
    }

    .page-btn {
      min-width: 32px;
      height: 32px;
      padding: 0 10px;
      border: 1px solid #dfe7f0;
      background: #fff;
      color: var(--navy);
      border-radius: 8px;
      cursor: pointer;
      font-weight: 700;
    }

    .page-btn.is-active {
      background: var(--blue);
      border-color: var(--blue);
      color: #fff;
    }

    .page-btn:disabled {
      opacity: 0.42;
      cursor: not-allowed;
    }

    @media (max-width: 1100px) {
      .kpi-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
      .chart-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 820px) {
      .reports-header { flex-direction: column; align-items: stretch; }
      .filterbar { grid-template-columns: 1fr 1fr; }
      .clear-btn { grid-column: 1 / -1; }
    }

    @media (max-width: 640px) {
      .reports-shell { padding: 20px 16px 24px; }
      .kpi-grid, .filterbar { grid-template-columns: 1fr; }
      .table-header { flex-direction: column; align-items: stretch; }
      .table-search { width: 100%; }
      .table-footer { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body class="admin-dashboard-ref-body">
  <div class="admin-portal-shell">
    <?php include __DIR__ . '/admin-sidebar.php'; ?>
    <main class="admin-main">
      <div class="reports-shell">
        <header class="reports-header">
          <div>
            <h1>Reports &amp; Analytics</h1>
            <p>Review submitted reports, monitor status trends, and manage report data in detail.</p>
          </div>
          <button type="button" class="export-btn" id="exportReportsBtn">Export Reports</button>
        </header>

        <section class="kpi-grid" aria-label="Reports summary">
          <article class="kpi-card">
            <div class="kpi-icon kpi-icon--blue" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M7 3h10l4 4v12.5A2.5 2.5 0 0 1 18.5 22h-13A2.5 2.5 0 0 1 3 19.5V5.5A2.5 2.5 0 0 1 5.5 3H7zm9 1.5V8h3.5"/><path d="M8 12h8M8 16h8"/></svg>
            </div>
            <div class="kpi-meta">
              <span class="kpi-label">Total Reports</span>
              <strong class="kpi-value" id="kpiTotal">0</strong>
              <span class="kpi-trend"><svg viewBox="0 0 24 24"><path d="M5 15l7-7 4 4 7-9"/><path d="M19 3h5v5"/></svg>12% from last month</span>
            </div>
          </article>

          <article class="kpi-card">
            <div class="kpi-icon kpi-icon--amber" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M12 2v9"/><path d="M12 15v7"/><path d="M4.93 4.93l2.12 2.12"/><path d="M16.95 16.95l2.12 2.12"/><path d="M2 12h9"/><path d="M13 12h9"/><path d="M4.93 19.07l2.12-2.12"/><path d="M16.95 7.05l2.12-2.12"/></svg>
            </div>
            <div class="kpi-meta">
              <span class="kpi-label">Pending</span>
              <strong class="kpi-value" id="kpiPending">0</strong>
              <span class="kpi-trend kpi-trend--amber"><svg viewBox="0 0 24 24"><path d="M5 15l7-7 4 4 7-9"/><path d="M19 3h5v5"/></svg>18.7% of total reports</span>
            </div>
          </article>

          <article class="kpi-card">
            <div class="kpi-icon kpi-icon--green" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M5 12.5l4 4 10-10"/><path d="M20 5v6h-6"/></svg>
            </div>
            <div class="kpi-meta">
              <span class="kpi-label">Submitted</span>
              <strong class="kpi-value" id="kpiSubmitted">0</strong>
              <span class="kpi-trend"><svg viewBox="0 0 24 24"><path d="M5 15l7-7 4 4 7-9"/><path d="M19 3h5v5"/></svg>71.1% completion rate</span>
            </div>
          </article>

          <article class="kpi-card">
            <div class="kpi-icon kpi-icon--red" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M12 8v5"/><path d="M12 16h.01"/><circle cx="12" cy="12" r="9"/></svg>
            </div>
            <div class="kpi-meta">
              <span class="kpi-label">Overdue</span>
              <strong class="kpi-value" id="kpiOverdue">0</strong>
              <span class="kpi-trend kpi-trend--red"><svg viewBox="0 0 24 24"><path d="M6 18l12-12"/><path d="M6 6l12 12"/></svg>Requires attention</span>
            </div>
          </article>
        </section>

        <section class="filterbar" aria-label="Report filters">
          <div class="field">
            <label>Date Range</label>
            <select id="filterDateRange">
              <option value="all">This Month (September 2026)</option>
              <option value="30">Last 30 Days</option>
              <option value="90">Last 90 Days</option>
              <option value="year">This Year</option>
            </select>
          </div>

          <div class="field">
            <label>Office</label>
            <select id="filterOffice">
              <option value="all">All Offices</option>
            </select>
          </div>

          <div class="field">
            <label>Report Type</label>
            <select id="filterType">
              <option value="all">All Report Types</option>
            </select>
          </div>

          <div class="field">
            <label>Status</label>
            <select id="filterStatus">
              <option value="all">All Statuses</option>
              <option value="PENDING">Pending</option>
              <option value="SUBMITTED">Submitted</option>
              <option value="OVERDUE">Overdue</option>
            </select>
          </div>

          <button type="button" class="clear-btn" id="clearFiltersBtn">Clear Filters</button>
        </section>

        <section class="chart-grid" aria-label="Analytics charts">
          <article class="panel-card">
            <div class="panel-header">
              <h2>Report Submission Trends</h2>
              <a href="#">This Month</a>
            </div>
            <svg class="trend-svg" viewBox="0 0 560 170" preserveAspectRatio="none" role="img" aria-label="Report submission trends chart">
              <defs>
                <linearGradient id="trendFill" x1="0" x2="0" y1="0" y2="1">
                  <stop offset="0%" stop-color="#1f5fcf" stop-opacity="0.18"/>
                  <stop offset="100%" stop-color="#1f5fcf" stop-opacity="0.02"/>
                </linearGradient>
              </defs>
              <g stroke="#dfe7f0" stroke-width="1">
                <line x1="35" y1="20" x2="530" y2="20"/>
                <line x1="35" y1="55" x2="530" y2="55"/>
                <line x1="35" y1="90" x2="530" y2="90"/>
                <line x1="35" y1="125" x2="530" y2="125"/>
                <line x1="35" y1="160" x2="530" y2="160"/>
              </g>
              <g fill="#64748b" font-size="10" font-family="Arial, sans-serif">
                <text x="30" y="165">0</text>
                <text x="20" y="130">25</text>
                <text x="20" y="95">50</text>
                <text x="20" y="60">75</text>
                <text x="15" y="25">100</text>
                <text x="52" y="170">Week 1</text>
                <text x="128" y="170">Week 2</text>
                <text x="207" y="170">Week 3</text>
                <text x="287" y="170">Week 4</text>
                <text x="366" y="170">Week 5</text>
              </g>
              <path d="M35 122 L95 104 L150 98 L205 75 L260 68 L315 55 L370 50 L425 43 L480 40 L530 30 L530 160 L35 160 Z" fill="url(#trendFill)"/>
              <path d="M35 122 L95 104 L150 98 L205 75 L260 68 L315 55 L370 50 L425 43 L480 40 L530 30" fill="none" stroke="#1f5fcf" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
              <g fill="#1f5fcf">
                <circle cx="35" cy="122" r="4"/>
                <circle cx="95" cy="104" r="4"/>
                <circle cx="150" cy="98" r="4"/>
                <circle cx="205" cy="75" r="4"/>
                <circle cx="260" cy="68" r="4"/>
                <circle cx="315" cy="55" r="4"/>
                <circle cx="370" cy="50" r="4"/>
                <circle cx="425" cy="43" r="4"/>
                <circle cx="480" cy="40" r="4"/>
                <circle cx="530" cy="30" r="4"/>
              </g>
            </svg>
            <div class="chart-legend">91 total reports submitted this month</div>
          </article>

          <article class="panel-card">
            <div class="panel-header">
              <h2>Reports by Status</h2>
            </div>
            <div class="status-donut">
              <div class="donut" id="statusDonut">
                <div class="donut-center">
                  <strong id="donutTotal">0</strong>
                  <span>Total</span>
                </div>
              </div>
            </div>
            <div class="donut-legend">
              <div class="legend-row"><span class="legend-dot legend-dot--green"></span><strong>Submitted</strong><span class="legend-value" id="legendSubmitted">0</span></div>
              <div class="legend-row"><span class="legend-dot legend-dot--amber"></span><strong>Pending</strong><span class="legend-value" id="legendPending">0</span></div>
              <div class="legend-row"><span class="legend-dot legend-dot--red"></span><strong>Overdue</strong><span class="legend-value" id="legendOverdue">0</span></div>
            </div>
          </article>

          <article class="panel-card">
            <div class="panel-header">
              <h2>Reports by Office</h2>
              <a href="#">View All</a>
            </div>
            <div class="office-bars" aria-label="Reports by office bar chart">
              <div class="bar-col"><div class="bar" style="height: 75%;"></div><span>Provincial Office</span></div>
              <div class="bar-col"><div class="bar" style="height: 55%;"></div><span>Field Offices</span></div>
              <div class="bar-col"><div class="bar" style="height: 36%;"></div><span>City/Municipal LGUs</span></div>
              <div class="bar-col"><div class="bar" style="height: 20%;"></div><span>Other Offices</span></div>
            </div>
          </article>
        </section>

        <section class="table-card" aria-label="Detailed report data table">
          <div class="table-header">
            <div>
              <h2>Detailed Report Data</h2>
              <p>Search, filter, and update report statuses.</p>
            </div>
            <label class="table-search" aria-label="Search report table">
              <input type="search" id="tableSearch" placeholder="Search report title, submitter, or office..." />
            </label>
          </div>

          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Report</th>
                  <th>Office</th>
                  <th>Submitted By</th>
                  <th>Date Submitted</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="reportRows"></tbody>
            </table>
          </div>

          <div class="table-footer">
            <span id="reportSummaryText">Showing 0 to 0 of 0 entries</span>
            <div class="pager" id="reportPager" aria-label="Report pagination"></div>
          </div>
        </section>
      </div>
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

  <style>
    .settings-modal {
      position: fixed;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }

    .settings-modal.is-open {
      display: flex;
    }

    .settings-modal__backdrop {
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      cursor: pointer;
    }

    .settings-modal__panel {
      position: relative;
      background: white;
      border-radius: 12px;
      box-shadow: 0 20px 60px rgba(15, 42, 107, 0.15);
      max-width: 500px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
      padding: 24px;
    }

    .settings-modal__header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 1px solid #e5ecf5;
      padding-bottom: 16px;
    }

    .settings-modal__header h2 {
      margin: 0;
      font-size: 1.2rem;
      color: #0f2a6b;
    }

    .settings-modal__close {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #64748b;
      padding: 4px 8px;
    }

    .settings-form {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .settings-form label {
      display: flex;
      flex-direction: column;
      gap: 6px;
      font-weight: 600;
      color: #0f2a6b;
      font-size: 0.9rem;
    }

    .settings-form input,
    .settings-form select {
      padding: 10px 12px;
      border: 1px solid #d4dce8;
      border-radius: 8px;
      font-size: 0.9rem;
      font-family: inherit;
    }

    .settings-form input:focus,
    .settings-form select:focus {
      outline: none;
      border-color: #1f5fcf;
      box-shadow: 0 0 0 3px rgba(31, 95, 207, 0.12);
    }

    .settings-form__row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .settings-modal__actions {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
      margin-top: 24px;
      padding-top: 16px;
      border-top: 1px solid #e5ecf5;
    }

    .settings-btn {
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      border: none;
      font-size: 0.9rem;
      transition: all 0.2s;
    }

    .settings-btn--primary {
      background: #1f5fcf;
      color: white;
    }

    .settings-btn--primary:hover {
      background: #0f4099;
    }

    .settings-btn--secondary {
      background: #f5f7fb;
      color: #0f2a6b;
      border: 1px solid #dfe7f0;
    }

    .settings-btn--secondary:hover {
      background: #eef0f8;
    }

    .settings-status {
      margin-top: 12px;
      text-align: center;
      font-size: 0.85rem;
      color: #2bb673;
      min-height: 20px;
    }
  </style>

  <script>
    (function () {
      const pageSize = 5;
      let reports = [];
      let currentPage = 1;
      let searchQuery = '';
      let currentStatus = 'all';
      let currentType = 'all';
      let currentOffice = 'all';
      let currentDateRange = 'all';

      const rowsEl = document.getElementById('reportRows');
      const pagerEl = document.getElementById('reportPager');
      const summaryText = document.getElementById('reportSummaryText');
      const exportBtn = document.getElementById('exportReportsBtn');
      const tableSearch = document.getElementById('tableSearch');
      const filterDate = document.getElementById('filterDateRange');
      const filterOffice = document.getElementById('filterOffice');
      const filterType = document.getElementById('filterType');
      const filterStatus = document.getElementById('filterStatus');
      const clearFiltersBtn = document.getElementById('clearFiltersBtn');

      function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
          const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
          return map[char];
        });
      }

      function inferOffice(row, index) {
        const officePool = ['Provincial Office', 'Field Offices', 'City/Municipal LGUs', 'Other Offices'];
        const name = String(row.username || row.submitted_by || '').toLowerCase();
        if (name.includes('campo') || name.includes('province')) return 'Provincial Office';
        if (name.includes('field') || name.includes('office')) return 'Field Offices';
        if (name.includes('city') || name.includes('municipal') || name.includes('lgu')) return 'City/Municipal LGUs';
        return officePool[index % officePool.length];
      }

      function inferType(row) {
        const title = String(row.title || '').toLowerCase();
        if (title.includes('financial')) return 'Financial';
        if (title.includes('monthly') || title.includes('accomplishment')) return 'Monthly Accomplishment';
        if (title.includes('annual') || title.includes('year')) return 'Annual';
        if (title.includes('budget') || title.includes('utilization')) return 'Budget';
        if (title.includes('audit')) return 'Audit';
        return 'General';
      }

      function prettyDate(value) {
        const date = new Date(value || Date.now());
        if (Number.isNaN(date.getTime())) return '—';
        return date.toLocaleString('en-US', {
          month: 'short',
          day: 'numeric',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
      }

      function statusClass(status) {
        const s = String(status || '').toUpperCase();
        if (s === 'PENDING') return 'status-pill--pending';
        if (s === 'SUBMITTED') return 'status-pill--submitted';
        if (s === 'OVERDUE') return 'status-pill--overdue';
        return 'status-pill--pending';
      }

      function refreshKpis(data) {
        const total = data.length;
        const pending = data.filter(r => String(r.status || '').toUpperCase() === 'PENDING').length;
        const submitted = data.filter(r => String(r.status || '').toUpperCase() === 'SUBMITTED').length;
        const overdue = data.filter(r => String(r.status || '').toUpperCase() === 'OVERDUE').length;

        document.getElementById('kpiTotal').textContent = total;
        document.getElementById('kpiPending').textContent = pending;
        document.getElementById('kpiSubmitted').textContent = submitted;
        document.getElementById('kpiOverdue').textContent = overdue;
        document.getElementById('donutTotal').textContent = total;
        document.getElementById('legendSubmitted').textContent = submitted;
        document.getElementById('legendPending').textContent = pending;
        document.getElementById('legendOverdue').textContent = overdue;

        const submittedPct = total ? (submitted / total) * 100 : 0;
        const pendingPct = total ? (pending / total) * 100 : 0;
        const overduePct = total ? (overdue / total) * 100 : 0;
        document.getElementById('statusDonut').style.background = 'conic-gradient(#2bb673 0 ' + submittedPct + '% , #f3a62a ' + submittedPct + '% ' + (submittedPct + pendingPct) + '% , #e65050 ' + (submittedPct + pendingPct) + '% 100%)';
      }

      function populateFilters(data) {
        const officeSet = new Set();
        const typeSet = new Set();

        data.forEach((row, index) => {
          officeSet.add(inferOffice(row, index));
          typeSet.add(inferType(row));
        });

        const officeOptions = ['<option value="all">All Offices</option>']
          .concat(Array.from(officeSet).sort().map(value => '<option value="' + escapeHtml(value) + '">' + escapeHtml(value) + '</option>'))
          .join('');

        const typeOptions = ['<option value="all">All Report Types</option>']
          .concat(Array.from(typeSet).sort().map(value => '<option value="' + escapeHtml(value) + '">' + escapeHtml(value) + '</option>'))
          .join('');

        filterOffice.innerHTML = officeOptions;
        filterType.innerHTML = typeOptions;
      }

      function getFilteredRows() {
        return reports.filter((row, idx) => {
          const status = String(row.status || '').toUpperCase();
          const office = inferOffice(row, idx);
          const type = inferType(row);
          const date = new Date(row.created_at || Date.now());
          const searchValue = (row.title || '') + ' ' + (row.username || '') + ' ' + office;
          const matchesSearch = !searchQuery || searchValue.toLowerCase().includes(searchQuery.toLowerCase());
          const matchesStatus = currentStatus === 'all' || status === currentStatus;
          const matchesType = currentType === 'all' || type === currentType;
          const matchesOffice = currentOffice === 'all' || office === currentOffice;

          let matchesDate = true;
          const now = new Date();
          const daysAgo = Math.max(0, (now - date) / (1000 * 60 * 60 * 24));
          if (currentDateRange === '30') matchesDate = daysAgo <= 30;
          if (currentDateRange === '90') matchesDate = daysAgo <= 90;
          if (currentDateRange === 'year') matchesDate = date.getFullYear() === now.getFullYear();

          return matchesSearch && matchesStatus && matchesType && matchesOffice && matchesDate;
        });
      }

      function renderTable() {
        const filtered = getFilteredRows();
        const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * pageSize;
        const visible = filtered.slice(start, start + pageSize);

        if (!visible.length) {
          rowsEl.innerHTML = '<tr><td colspan="6" style="padding: 24px 12px; color: #64748b; text-align: center;">No matching reports found.</td></tr>';
          summaryText.textContent = 'Showing 0 to 0 of 0 entries';
          pagerEl.innerHTML = '';
          return;
        }

        rowsEl.innerHTML = visible.map((row, index) => {
          const office = inferOffice(row, start + index);
          const type = inferType(row);
          const renderedStatus = String(row.status || 'PENDING').toUpperCase();
          const statusUi = '<span class="status-pill ' + statusClass(renderedStatus) + '">' + escapeHtml(renderedStatus) + '</span>';
          return '<tr>' +
            '<td><div class="report-title"><span class="report-title__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 3h8l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M15 3v5h5"/><path d="M9 13h6M9 17h6M9 9h2"/></svg></span><div class="report-title__meta"><strong>' + escapeHtml(row.title || 'Untitled Report') + '</strong><small>' + escapeHtml(type) + '</small></div></div></td>' +
            '<td>' + escapeHtml(office) + '</td>' +
            '<td>' + escapeHtml(row.username || 'Unknown user') + '</td>' +
            '<td>' + escapeHtml(prettyDate(row.created_at)) + '</td>' +
            '<td>' + statusUi + '</td>' +
            '<td><div class="action-group"><button type="button" class="action-btn" data-status-action="view" data-id="' + row.id + '">View</button><button type="button" class="action-btn action-btn--ghost" data-status-action="review" data-id="' + row.id + '">Review</button></div></td>' +
            '</tr>';
        }).join('');

        const startNumber = filtered.length ? start + 1 : 0;
        const endNumber = Math.min(start + pageSize, filtered.length);
        summaryText.textContent = 'Showing ' + startNumber + ' to ' + endNumber + ' of ' + filtered.length + ' entries';

        const buttons = ['<button type="button" class="page-btn" data-page="prev" ' + (currentPage === 1 ? 'disabled' : '') + '>&lsaquo;</button>'];
        for (let i = 1; i <= totalPages; i += 1) {
          buttons.push('<button type="button" class="page-btn ' + (i === currentPage ? 'is-active' : '') + '" data-page="' + i + '">' + i + '</button>');
        }
        buttons.push('<button type="button" class="page-btn" data-page="next" ' + (currentPage === totalPages ? 'disabled' : '') + '>&rsaquo;</button>');
        pagerEl.innerHTML = buttons.join('');
      }

      function updateFiltersFromControls() {
        currentDateRange = filterDate.value;
        currentOffice = filterOffice.value;
        currentType = filterType.value;
        currentStatus = filterStatus.value;
        currentPage = 1;
        renderTable();
      }

      function resetFilters() {
        filterDate.value = 'all';
        filterOffice.value = 'all';
        filterType.value = 'all';
        filterStatus.value = 'all';
        tableSearch.value = '';
        searchQuery = '';
        currentDateRange = 'all';
        currentOffice = 'all';
        currentType = 'all';
        currentStatus = 'all';
        currentPage = 1;
        renderTable();
      }

      function exportCsv() {
        const filtered = getFilteredRows();
        const rows = filtered.map((row, index) => {
          const office = inferOffice(row, index);
          const status = String(row.status || 'PENDING').toUpperCase();
          return [row.title || '', office, row.username || '', prettyDate(row.created_at), status];
        });

        const csv = [
          ['Title', 'Office', 'Submitted By', 'Date Submitted', 'Status'],
          ...rows
        ].map(line => line.map(value => '"' + String(value).replace(/"/g, '""') + '"').join(',')).join('\n');

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'dilg-report-analytics.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
      }

      async function loadReports() {
        try {
          const response = await fetch('auth/reports_api.php?action=list');
          const data = await response.json();
          reports = Array.isArray(data.reports) ? data.reports : [];
          refreshKpis(reports);
          populateFilters(reports);
          renderTable();
        } catch (error) {
          rowsEl.innerHTML = '<tr><td colspan="6" style="padding: 24px 12px; color: #b91c1c; text-align: center;">Unable to load reports.</td></tr>';
          summaryText.textContent = 'Showing 0 to 0 of 0 entries';
          pagerEl.innerHTML = '';
        }
      }

      tableSearch.addEventListener('input', function () {
        searchQuery = this.value.trim();
        currentPage = 1;
        renderTable();
      });

      filterDate.addEventListener('change', updateFiltersFromControls);
      filterOffice.addEventListener('change', updateFiltersFromControls);
      filterType.addEventListener('change', updateFiltersFromControls);
      filterStatus.addEventListener('change', updateFiltersFromControls);
      clearFiltersBtn.addEventListener('click', resetFilters);
      exportBtn.addEventListener('click', exportCsv);

      pagerEl.addEventListener('click', function (event) {
        const btn = event.target.closest('[data-page]');
        if (!btn) return;
        const pageValue = btn.getAttribute('data-page');
        if (pageValue === 'prev') currentPage = Math.max(1, currentPage - 1);
        else if (pageValue === 'next') {
          const filtered = getFilteredRows();
          const maxPage = Math.max(1, Math.ceil(filtered.length / pageSize));
          currentPage = Math.min(maxPage, currentPage + 1);
        } else {
          currentPage = Number(pageValue);
        }
        renderTable();
      });

      rowsEl.addEventListener('click', function (event) {
        const btn = event.target.closest('[data-status-action]');
        if (!btn) return;
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-status-action');
        const row = reports.find(item => String(item.id) === String(id));
        if (!row) return;

        if (action === 'view') {
          window.alert('View report: ' + (row.title || 'Untitled Report'));
        } else if (action === 'review') {
          window.alert('Review report: ' + (row.title || 'Untitled Report'));
        }
      });

      loadReports();
    }());

    // Settings Modal Handlers
    (function () {
      const modal = document.getElementById('settings-modal');
      const openButtons = document.querySelectorAll('[data-open-settings]');
      const closeButtons = document.querySelectorAll('[data-close-settings]');
      const settingsForm = document.getElementById('system-settings-form');
      const settingsStatus = document.getElementById('settings-status');

      function openModal() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
      }

      function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
      }

      openButtons.forEach(btn => btn.addEventListener('click', openModal));
      closeButtons.forEach(btn => btn.addEventListener('click', closeModal));

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
          closeModal();
        }
      });

      const savedSettings = JSON.parse(localStorage.getItem('dilgSystemSettings') || '{}');
      if (settingsForm) {
        Object.keys(savedSettings).forEach(key => {
          const input = settingsForm.querySelector('[name="' + key + '"]');
          if (input) input.value = savedSettings[key];
        });

        settingsForm.addEventListener('submit', function (event) {
          event.preventDefault();
          const formData = new FormData(settingsForm);
          const values = Object.fromEntries(formData.entries());
          localStorage.setItem('dilgSystemSettings', JSON.stringify(values));
          if (settingsStatus) {
            settingsStatus.textContent = 'Settings saved successfully.';
            setTimeout(() => {
              settingsStatus.textContent = '';
              closeModal();
            }, 1500);
          }
        });
      }
    }());
  </script>
</body>
</html>

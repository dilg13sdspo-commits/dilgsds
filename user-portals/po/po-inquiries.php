<?php
require_once __DIR__ . '/../shared/auth/middleware.php';
require_once __DIR__ . '/../shared/auth/po_icons.php';
require_once __DIR__ . '/../shared/auth/workflow.php';
require_role('PO USER');

$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);
$profilePhoto = get_user_profile_photo($pdo, $userId);
workflow_ensure_schema($pdo);
workflow_sync_overdue($pdo);
$userName = $_SESSION['username'] ?? 'Provincial User';
$notificationCountStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
$notificationCountStmt->execute([':uid' => $userId]);
$notificationCount = (int)$notificationCountStmt->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Inquiries - DILG PO Dashboard</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    :root {
      --dilg-brown: #5d4037;
      --dilg-orange: #ff9800;
      --dilg-light-brown: #8d6e63;
      --bg-light: #f5f3f0;
      --text-primary: #1a1a1a;
      --text-secondary: #666666;
      --text-muted: #999999;
      --border-light: #e0e0e0;
      --white: #ffffff;
      --success: #4caf50;
      --warning: #ff9800;
      --danger: #f44336;
      --info: #2196f3;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: var(--bg-light);
      color: var(--text-primary);
      line-height: 1.6;
    }
    
    .wrapper {
      display: flex;
      min-height: 100vh;
    }
    
    .sidebar {
      width: 220px;
      background: linear-gradient(180deg, #0b3d91 0%, #1456a0 100%);
      color: var(--white);
      padding: 1rem 0 1.25rem;
      box-shadow: 2px 0 4px rgba(0,0,0,0.1);
    }
    
    .sidebar-brand {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      padding: 0.75rem 1rem 1rem;
      margin-bottom: 0.75rem;
      border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    
    .sidebar-brand .logo-mark {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: rgba(255,255,255,0.12);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      color: #fff;
      font-size: 1.1rem;
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
    }
    
    .sidebar-brand .logo {
      font-weight: 700;
      font-size: 1.1rem;
      margin-bottom: 0.1rem;
    }
    
    .sidebar-brand .label {
      font-size: 0.75rem;
      opacity: 0.8;
    }
    
    .sidebar-nav {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 0.2rem;
      padding: 0.15rem 0.6rem 0;
    }
    
    .sidebar-nav li a {
      display: flex;
      align-items: center;
      gap: 0.7rem;
      padding: 0.7rem 0.9rem;
      border-radius: 10px;
      color: rgba(255,255,255,0.85);
      text-decoration: none;
      transition: all 0.25s ease;
      font-weight: 500;
      font-size: 0.97rem;
      line-height: 1.2;
    }
    
    .sidebar-nav li a:hover,
    .sidebar-nav li a.active {
      background: rgba(255,255,255,0.09);
      color: var(--white);
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
    }

    .sidebar-nav li a .nav-icon {
      width: 22px;
      text-align: center;
      font-size: 0.9rem;
      opacity: 0.95;
    }
    
    .main-content {
      flex: 1;
      overflow-y: auto;
    }
    
    .topbar {
      background: rgba(255,255,255,0.82);
      border: 1px solid rgba(133, 121, 116, 0.32);
      border-radius: 18px;
      margin: 14px 18px 0;
      padding: 14px 18px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 18px;
      box-shadow: 0 8px 18px rgba(45, 37, 31, 0.05);
    }
    
    .topbar-left {
      display: flex;
      align-items: center;
      gap: 0.9rem;
      flex: 1;
      min-width: 0;
    }

    .brand-mark {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      background: linear-gradient(135deg, #f5a623, #d98018);
      color: #fff;
      display: grid;
      place-items: center;
      font-weight: 800;
      font-size: 1.2rem;
      box-shadow: 0 8px 18px rgba(217, 128, 24, 0.18);
      flex-shrink: 0;
    }

    .sidebar-profile { display: flex; align-items: center; gap: 10px; width: calc(100% - 16px); min-height: 58px; margin: 14px 8px 0; padding: 10px 8px; border: 1px solid rgba(255,255,255,.12); border-radius: 12px; background: rgba(255,255,255,.08); color: #fff; }
    .sidebar-profile-avatar { width: 36px; height: 36px; display: grid; place-items: center; flex: 0 0 36px; overflow: hidden; border: 1px solid rgba(255,255,255,.28); border-radius: 50%; background: rgba(255,255,255,.14); font-size: .82rem; font-weight: 800; }
    .sidebar-profile-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .sidebar-profile-info { min-width: 0; }
    .sidebar-profile-status { display: flex; align-items: center; gap: 6px; color: rgba(255,255,255,.88); font-size: .72rem; }
    .sidebar-profile-status::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: #34d399; box-shadow: 0 0 0 3px rgba(52,211,153,.14); }
    .sidebar-profile-name { display: block; margin-top: 3px; color: #fff; font-size: .78rem; font-weight: 700; }
    .sidebar > div[style*="display:flex"] { width: calc(100% - 16px) !important; min-height: 58px; margin: 14px 8px 0 !important; padding: 10px 8px !important; }
    .sidebar > div[style*="display:flex"] > div:first-child { width: 36px !important; height: 36px !important; flex: 0 0 36px !important; }
    
    .breadcrumb {
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 0.12rem;
      min-width: 0;
      line-height: 1.15;
    }
    
    .breadcrumb .main-title {
      color: #2d2d2d;
      font-weight: 800;
      letter-spacing: 0.01em;
      font-size: clamp(1.1rem, 1.5vw, 1.9rem);
      text-transform: uppercase;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .breadcrumb .sub-title {
      color: #6f6f6f;
      font-size: 0.8rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    
    .topbar-right {
      display: flex;
      align-items: center;
      gap: 14px;
      flex-shrink: 0;
    }
    
    .search-box {
      position: relative;
      width: min(440px, 38vw);
      min-width: 200px;
    }
    
    .search-box input {
      width: 100%;
      padding: 0.9rem 3.4rem 0.9rem 1rem;
      border: 1px solid #d8d8d8;
      border-radius: 14px;
      background: rgba(255,255,255,0.7);
      font-size: 0.96rem;
      color: #5d5d5d;
      box-shadow: inset 0 1px 0 rgba(0,0,0,0.02);
    }
    
    .search-box input::placeholder {
      color: #8c8c8c;
      font-size: 0.95rem;
    }
    
    .search-box input:focus {
      outline: none;
      border-color: #d0d0d0;
      box-shadow: 0 0 0 4px rgba(90, 117, 160, 0.04);
    }
    
    .search-icon {
      position: absolute;
      right: 0.7rem;
      top: 50%;
      transform: translateY(-50%);
      width: 28px;
      height: 28px;
      border-radius: 10px;
      background: linear-gradient(135deg, #f5a623, #e58d12);
      display: grid;
      place-items: center;
      font-size: 1.1rem;
      color: #fff;
      box-shadow: 0 8px 16px rgba(245, 166, 35, 0.2);
      font-weight: 700;
    }
    
    .header-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .notification-badge {
      position: relative;
      width: 36px;
      height: 36px;
      border-radius: 12px;
      background: rgba(255,255,255,0.9);
      border: 1px solid rgba(151, 125, 112, 0.18);
      display: grid;
      place-items: center;
      color: #745d53;
      font-size: 1.1rem;
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
      cursor: pointer;
    }

    .notification-badge::after {
      content: "";
      position: absolute;
      top: -5px;
      right: -3px;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: #f44f5a;
      border: 2px solid #fff;
      box-shadow: 0 2px 4px rgba(244, 79, 90, 0.25);
    }
    
    .user-profile {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.35rem 0.7rem 0.35rem 0.35rem;
      border-radius: 14px;
      background: rgba(255,255,255,0.9);
      border: 1px solid rgba(131, 118, 112, 0.2);
      min-width: 0;
      cursor: pointer;
    }
    
    .user-avatar {
      width: 36px;
      height: 36px;
      border-radius: 12px;
      background: linear-gradient(135deg, #f5a623, #d98018);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: 800;
      font-size: 0.95rem;
      box-shadow: 0 8px 16px rgba(217, 128, 24, 0.18);
      flex-shrink: 0;
    }

    .user-text {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      justify-content: center;
      line-height: 1.2;
      min-width: 0;
    }

    .user-name {
      color: #2d2d2d;
      font-weight: 700;
      font-size: 1.05rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .user-role {
      color: #777;
      font-size: 0.72rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    
    .page-content {
      padding: 2rem;
    }
    
    .page-header {
      margin-bottom: 2rem;
    }
    
    .page-header-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
    }
    
    .page-title {
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      color: var(--text-primary);
    }
    
    .page-description {
      color: var(--text-secondary);
      font-size: 0.95rem;
    }
    
    .btn {
      padding: 0.6rem 1.5rem;
      border: none;
      border-radius: 4px;
      font-weight: 600;
      cursor: pointer;
      font-size: 0.9rem;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .btn-primary {
      background: var(--dilg-brown);
      color: var(--white);
    }
    
    .btn-primary:hover {
      background: #4d3030;
    }
    
    .btn-secondary {
      background: var(--border-light);
      color: var(--text-primary);
    }
    
    .btn-secondary:hover {
      background: #d0d0d0;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }
    
    .stat-card {
      background: var(--white);
      border-radius: 8px;
      padding: 1.5rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      border-left: 4px solid var(--dilg-brown);
      border: 1px solid var(--border-light);
      cursor: pointer;
      text-align: left;
      transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
      appearance: none;
      -webkit-appearance: none;
    }

    .stat-card:hover,
    .stat-card:focus-visible {
      transform: translateY(-1px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.08);
      border-color: var(--dilg-orange);
      outline: none;
    }
    
    .stat-card.draft { border-left-color: #9e9e9e; }
    .stat-card.pending { border-left-color: #ff9800; }
    .stat-card.forwarded { border-left-color: #2196f3; }
    .stat-card.resolved { border-left-color: #4caf50; }
    .stat-card.returned { border-left-color: #f44336; }
    
    .stat-icon {
      font-size: 1.5rem;
      margin-bottom: 0.5rem;
    }
    
    .stat-label {
      font-size: 0.8rem;
      color: var(--text-muted);
      text-transform: uppercase;
      margin-bottom: 0.3rem;
    }
    
    .stat-value {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--text-primary);
    }
    
    .content-wrapper {
      display: grid;
      grid-template-columns: 1fr 300px;
      gap: 2rem;
    }
    
    .inquiries-section {
      background: var(--white);
      border-radius: 8px;
      padding: 2rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .section-title {
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      color: var(--text-primary);
    }
    
    .inquiry-list {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    
    .inquiry-item {
      border: 1px solid var(--border-light);
      border-radius: 6px;
      padding: 1rem;
      transition: all 0.3s ease;
    }
    
    .inquiry-item:hover {
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      border-color: var(--dilg-orange);
    }
    
    .inquiry-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 0.75rem;
    }
    
    .inquiry-title {
      font-weight: 600;
      color: var(--text-primary);
      font-size: 1rem;
    }
    
    .status-badge {
      padding: 0.3rem 0.75rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      white-space: nowrap;
    }
    
    .status-badge.draft { background: #f0f0f0; color: #666; }
    .status-badge.pending { background: #fff3e0; color: #ff6f00; }
    .status-badge.submitted { background: #e3f2fd; color: #1976d2; }
    .status-badge.forwarded { background: #e1f5fe; color: #01579b; }
    .status-badge.resolved { background: #e8f5e9; color: #2e7d32; }
    .status-badge.returned { background: #f1eaff; color: #7c4dcb; }
    
    .inquiry-meta {
      display: flex;
      gap: 1.5rem;
      font-size: 0.85rem;
      color: var(--text-muted);
      margin-bottom: 0.75rem;
    }
    
    .inquiry-actions {
      display: flex;
      gap: 0.5rem;
    }
    
    .inquiry-actions button {
      padding: 0.4rem 0.8rem;
      font-size: 0.8rem;
      border: 1px solid var(--dilg-brown);
      background: transparent;
      color: var(--dilg-brown);
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .inquiry-actions button:hover {
      background: var(--dilg-brown);
      color: var(--white);
    }
    
    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
    }
    
    .empty-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }
    
    .empty-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 0.5rem;
    }
    
    .empty-description {
      color: var(--text-secondary);
      margin-bottom: 1.5rem;
    }
    
    .filter-panel {
      background: var(--white);
      border-radius: 8px;
      padding: 1.5rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      height: fit-content;
    }
    
    .filter-group {
      margin-bottom: 1.5rem;
    }
    
    .filter-group:last-child {
      margin-bottom: 0;
    }
    
    .filter-label {
      font-weight: 600;
      font-size: 0.9rem;
      color: var(--text-primary);
      margin-bottom: 0.75rem;
      display: block;
    }
    
    .filter-options {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    
    .filter-option {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      cursor: pointer;
    }
    
    .filter-option input[type="checkbox"],
    .filter-option input[type="radio"] {
      cursor: pointer;
    }
    
    .filter-option label {
      cursor: pointer;
      margin: 0;
      font-weight: 400;
    }
    
    .clear-filters {
      width: 100%;
      padding: 0.75rem;
      background: transparent;
      border: 1px solid var(--dilg-brown);
      color: var(--dilg-brown);
      border-radius: 4px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .clear-filters:hover {
      background: var(--dilg-brown);
      color: var(--white);
    }
    
    .quick-tips {
      background: var(--white);
      border-radius: 8px;
      padding: 1.5rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      margin-top: 1.5rem;
    }
    
    .quick-tips-title {
      font-weight: 700;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .quick-tips-list {
      list-style: none;
    }
    
    .quick-tips-list li {
      padding: 0.5rem 0;
      color: var(--text-secondary);
      font-size: 0.9rem;
      padding-left: 1.5rem;
      position: relative;
    }
    
    .quick-tips-list li:before {
      content: "\\2022";
      position: absolute;
      left: 0;
    }
    
    .loading {
      text-align: center;
      padding: 2rem;
      color: var(--text-muted);
    }

    .page-content { max-width: 1440px; margin: 0 auto; padding: 2rem 2.2rem 3rem; }
    .page-header { margin-bottom: 1.4rem; }
    .page-title { font-size: 1.65rem; margin-bottom: 0.35rem; }
    .page-description { font-size: 0.84rem; color: #748096; }
    .stats-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1rem; }
    .stat-card {
      min-height: 150px;
      padding: 1.15rem 1.15rem 0;
      border: 1px solid #e6eaf0;
      border-top: 3px solid var(--dilg-brown);
      border-left: 1px solid #e6eaf0;
      border-radius: 10px;
      box-shadow: 0 5px 18px rgba(54, 42, 36, 0.06);
      position: relative;
      overflow: hidden;
    }
    .stat-card::after {
      content: "";
      position: absolute;
      left: 0;
      right: 0;
      bottom: 0;
      height: 42px;
      background: rgba(93, 64, 55, 0.035);
      pointer-events: none;
    }
    .stat-card.total { border-top-color: #667085; }
    .stat-card.pending { border-top-color: #f2a43b; }
    .stat-card.reviewing { border-top-color: #4d8ed8; }
    .stat-card.resolved { border-top-color: #49a968; }
    .stat-card.returned { border-top-color: #e76868; }
    .stat-card.overdue { border-top-color: #e76868; }
    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      margin-bottom: 0.7rem;
      background: #f8f5f3;
      color: var(--dilg-brown);
    }
    .stat-icon svg { width: 26px; height: 26px; }
    .stat-card.total .stat-icon { background: #f5f5f5; color: #667085; }
    .stat-card.pending .stat-icon { background: #fff4e4; color: #f09a27; }
    .stat-card.reviewing .stat-icon { background: #edf5ff; color: #2879c9; }
    .stat-card.resolved .stat-icon { background: #eaf7ee; color: #319b52; }
    .stat-card.returned .stat-icon { background: #edf5ff; color: #2879c9; }
    .stat-card.overdue .stat-icon { background: #fff0f0; color: #d93030; }
    .stat-card.returned { border-top-color: #9b6de8; }
    .stat-card.returned .stat-icon { background: #f1eaff; color: #895fd1; }
    .stat-label { font-size: 0.68rem; color: #7d8796; letter-spacing: 0.04em; }
    .stat-card .stat-label::after { content: " INQUIRIES"; }
    .stat-card:first-child .stat-label::after { content: " SUBMISSIONS"; }
    .stat-card.pending .stat-label::after { content: ""; }
    .stat-card.reviewing .stat-label::after { content: ""; }
    .stat-card.resolved .stat-label::after { content: ""; }
    .stat-card.returned .stat-label::after { content: ""; }
    .stat-card.overdue .stat-label::after { content: ""; }
    .stat-value { font-size: 1.65rem; line-height: 1; margin-top: 0.15rem; }
    .content-wrapper { grid-template-columns: minmax(0, 1fr) 280px; gap: 1rem; align-items: start; }
    .inquiries-section, .filter-panel, .quick-tips {
      border: 1px solid #e6eaf0;
      border-radius: 10px;
      box-shadow: 0 5px 18px rgba(54, 42, 36, 0.05);
    }
    .inquiries-section { padding: 1.15rem; }
    .section-title { font-size: 1rem; margin-bottom: 0; }
    .submission-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin: 0.9rem 0 0.8rem; }
    .submission-tabs { display: flex; gap: 0.45rem; flex-wrap: wrap; }
    .submission-tab { border: 1px solid #e4e7ec; background: #fff; color: #667085; border-radius: 7px; padding: 0.42rem 0.75rem; font-size: 0.72rem; cursor: pointer; }
    .submission-tab.active { background: var(--dilg-brown); border-color: var(--dilg-brown); color: #fff; }
    .list-search { width: 190px; border: 1px solid #dfe4eb; border-radius: 7px; padding: 0.55rem 0.7rem; font-size: 0.76rem; }
    .inquiry-table-wrap { overflow-x: auto; border: 1px solid #edf0f3; border-radius: 8px; }
    .inquiry-table { width: 100%; border-collapse: collapse; min-width: 680px; }
    .inquiry-table th { background: #fafbfc; color: #667085; font-size: 0.67rem; text-transform: uppercase; letter-spacing: 0.04em; text-align: left; padding: 0.75rem 0.7rem; border-bottom: 1px solid #e9edf1; }
    .inquiry-table td { padding: 0.75rem 0.7rem; border-bottom: 1px solid #edf0f3; color: #475467; font-size: 0.76rem; vertical-align: middle; }
    .inquiry-table tr:last-child td { border-bottom: 0; }
    .inquiry-ref { color: #1d2939; font-weight: 700; }
    .inquiry-subject { color: #1d2939; font-weight: 600; }
    .inquiry-subject small, .inquiry-office small { display: block; color: #98a2b3; font-weight: 400; margin-top: 0.15rem; }
    .broadcast-tracking { display: grid; gap: 6px; margin-top: 6px; font-size: .66rem; line-height: 1.35; }
    .broadcast-summary { display: grid; gap: 2px; color: #475467; }
    .broadcast-summary-label { width: fit-content; padding: 2px 5px; border-radius: 4px; color: #287a43; background: #edf8f0; font-size: .6rem; font-weight: 800; text-transform: uppercase; letter-spacing: .02em; }
    .broadcast-summary-label.broadcast-pending { color: #ff6f00; background: #fff3e0; }
    .broadcast-summary-label.broadcast-overdue { color: #c62828; background: #ffebee; }
    .broadcast-summary-item { display: block; color: #344054; overflow-wrap: anywhere; }
    .broadcast-more { padding: 2px 5px; border: 1px solid #d9dee7; border-radius: 4px; background: #fff; color: var(--dilg-brown); font-size: .61rem; font-weight: 700; cursor: pointer; }
    .broadcast-more:hover, .broadcast-more:focus-visible { border-color: var(--dilg-brown); background: #fff8f5; outline: 0; }
    .municipality-popover { position: fixed; z-index: 1000; display: none; width: min(290px, calc(100vw - 24px)); max-height: min(280px, calc(100vh - 24px)); overflow-y: auto; padding: 10px; border: 1px solid #dfe4eb; border-radius: 8px; background: #fff; box-shadow: 0 12px 28px rgba(16, 24, 40, .16); }
    .municipality-popover.is-open { display: block; }
    .municipality-popover-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding-bottom: 7px; border-bottom: 1px solid #edf0f3; color: #344054; font-size: .69rem; font-weight: 800; text-transform: uppercase; }
    .municipality-popover-close { border: 0; background: transparent; color: #667085; font-size: 1rem; line-height: 1; cursor: pointer; }
    .municipality-popover-list { display: grid; gap: 4px; margin-top: 8px; }
    .municipality-popover-item { padding: 4px 5px; border-radius: 4px; color: #475467; font-size: .7rem; overflow-wrap: anywhere; }
    .municipality-popover-item:hover { background: #f7f8fa; }
    .broadcast-submitted { color: #287a43; }
    .broadcast-overdue { color: #c53e3e; }
    .broadcast-pending { color: #a96800; }
    .municipality-dropdown { position: relative; min-width: 170px; }
    .municipality-dropdown summary { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 7px 9px; border: 1px solid #d9dee7; border-radius: 6px; background: #fff; color: #344054; font-size: .69rem; font-weight: 700; cursor: pointer; list-style: none; }
    .municipality-dropdown summary::-webkit-details-marker { display: none; }
    .municipality-dropdown summary::after { content: '?'; color: #667085; font-size: .85rem; line-height: 1; }
    .municipality-dropdown[open] summary { border-color: var(--dilg-brown); color: var(--dilg-brown); }
    .municipality-dropdown-panel { position: fixed; z-index: 1000; display: none; width: 280px; max-height: min(340px, 55vh); overflow-y: auto; overscroll-behavior: contain; padding: 10px; border: 1px solid #dfe4eb; border-radius: 8px; background: #fff; box-shadow: 0 12px 28px rgba(16, 24, 40, .16); scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
    .municipality-dropdown[open] .municipality-dropdown-panel { display: block; }
    .municipality-dropdown-panel::-webkit-scrollbar { width: 6px; }
    .municipality-dropdown-panel::-webkit-scrollbar-thumb { border-radius: 6px; background: #cbd5e1; }
    .municipality-dropdown-group + .municipality-dropdown-group { margin-top: 8px; padding-top: 8px; border-top: 1px solid #edf0f3; }
    .municipality-dropdown-group-title { display: block; margin-bottom: 4px; font-size: .62rem; font-weight: 800; text-transform: uppercase; }
    .municipality-dropdown-group.submitted .municipality-dropdown-group-title { color: #287a43; }
    .municipality-dropdown-group.overdue .municipality-dropdown-group-title { color: #c62828; }
    .municipality-dropdown-group.pending .municipality-dropdown-group-title { color: #ff6f00; }
    .municipality-dropdown-group.returned .municipality-dropdown-group-title { color: #7c4dcb; }
    .municipality-dropdown-group.approved .municipality-dropdown-group-title { color: #1f8f5f; }
    .municipality-dropdown-item { padding: 4px 5px; border-radius: 4px; color: #475467; font-size: .68rem; overflow-wrap: anywhere; }
    .municipality-dropdown-item:hover { background: #f7f8fa; }
    .table-status { display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 6px; padding: 0.32rem 0.55rem; font-size: 0.67rem; font-weight: 700; }
    .table-status::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .table-status.pending { background: #fff5e6; color: #d88916; }
    .table-status.submitted { background: #edf8f0; color: #156ae9be; }
    .table-status.returned { background: #f1eaff; color: #7c4dcb; }
    .table-status.overdue { background: #fff0f0; color: #d93030; }
    .table-status.high { background: #fff0f0; color: #d64b4b; }
    .table-status.medium { background: #fff5e6; color: #d88916; }
    .table-status.low { background: #edf8f0; color: #328b4b; }
    .table-status:not(.pending):not(.submitted):not(.returned):not(.overdue):not(.high):not(.medium):not(.low) { background: #f1f4f7; color: #287a43; }
    .inquiry-table th:last-child, .inquiry-table td:last-child { position: sticky; right: 0; z-index: 2; min-width: 112px; background: #fff; box-shadow: -8px 0 12px rgba(31, 41, 55, .06); }
    .inquiry-table th:last-child { background: #fafbfc; z-index: 3; }
    .table-actions { display: flex; justify-content: flex-end; gap: 0.35rem; flex-wrap: nowrap; white-space: nowrap; }
    .table-action { width: 32px; height: 30px; border: 1px solid #e1e5ea; border-radius: 6px; background: #fff; color: #667085; cursor: pointer; display: grid; place-items: center; }
    .table-action:hover { color: var(--dilg-brown); border-color: var(--dilg-brown); }
    .table-action.delete:hover { color: #d64b4b; border-color: #d64b4b; background: #fff5f5; }
    .table-action svg { width: 15px; height: 15px; }
    .list-footer { display: flex; justify-content: space-between; color: #98a2b3; font-size: 0.72rem; padding-top: 0.8rem; }
    .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 0.8rem; }
    .pagination button { min-width: 30px; height: 30px; border: 1px solid #e1e5ea; border-radius: 6px; background: #fff; color: #667085; cursor: pointer; font-size: .72rem; }
    .pagination button.active, .pagination button:hover { background: var(--dilg-brown); color: #fff; border-color: var(--dilg-brown); }
    .filter-panel { padding: 1.15rem; }
    .filter-panel .section-title { margin-bottom: 1.2rem; }
    .filter-group { margin-bottom: 1.25rem; }
    .filter-label { font-size: 0.72rem; margin-bottom: 0.55rem; }
    .filter-options { gap: 0.65rem; }
    .filter-option { font-size: 0.76rem; color: #475467; }
    .clear-filters { border: 0; padding: 0.65rem; font-size: 0.75rem; }
    .quick-tips { display: none; }
    .quick-tips-title svg { width: 16px; height: 16px; }
    .sidebar-nav li a .nav-icon svg { width: 17px; height: 17px; }
    .notification-badge svg { width: 17px; height: 17px; }
    .inquiry-meta svg { width: 13px; height: 13px; vertical-align: -2px; }
    .empty-icon svg { width: 42px; height: 42px; }

    .sidebar { background: linear-gradient(180deg, #0b3d91 0%, #1456a0 100%); }
    .sidebar-nav li a:hover, .sidebar-nav li a.active { background: rgba(255,255,255,0.1); }

    @media (max-width: 900px) {
      .stats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
      .content-wrapper { grid-template-columns: 1fr; }
    }
    @media (max-width: 620px) {
      .page-content { padding: 1.2rem 0.9rem 2rem; }
      .stats-grid { grid-template-columns: 1fr 1fr; gap: 0.6rem; }
      .stat-card { min-height: 132px; padding: 0.8rem; }
      .submission-toolbar { align-items: stretch; flex-direction: column; }
      .list-search { width: 100%; }
      .municipality-dropdown-panel { width: min(280px, calc(100vw - 24px)); max-height: 55vh; }
    }
    
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }
    
    .modal.active {
      display: flex;
    }
    
    .modal-content {
      background: var(--white);
      border-radius: 8px;
      padding: 2rem;
      max-width: 600px;
      width: 90%;
      max-height: 80vh;
      overflow-y: auto;
    }

    #detailModal { background: rgba(18, 32, 44, .58); padding: 16px; }
    #detailModal .modal-content { width: min(100%, 980px); max-width: 980px; max-height: 94vh; padding: 0; overflow: hidden; border: 1px solid #dfe6e4; box-shadow: 0 24px 70px rgba(15, 35, 49, .24); }
    .submission-detail-scroll { max-height: calc(94vh - 150px); overflow-y: auto; padding: 20px 22px; }
    .submission-detail-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 18px 22px; border-bottom: 1px solid #e4eaec; }
    .submission-detail-header h2 { margin: 0; color: #172536; font-size: 1.3rem; }
    .submission-detail-back { border: 1px solid #dce4e8; border-radius: 7px; padding: 8px 12px; background: #fff; color: #334155; cursor: pointer; }
    .submission-detail-print { border: 1px solid #dce4e8; border-radius: 7px; padding: 8px 12px; background: #fff; color: #334155; cursor: pointer; }
    .submission-detail-card { margin-bottom: 14px; border: 1px solid #e0e7ea; border-radius: 8px; overflow: hidden; }
    .submission-detail-card h3 { margin: 0; padding: 12px 15px; background: #f3f8f6; color: #176650; font-size: .78rem; letter-spacing: .03em; text-transform: uppercase; }
    .submission-detail-card-body { padding: 15px; color: #26384a; font-size: .84rem; line-height: 1.55; overflow-wrap: anywhere; }
    .submission-detail-card-body a { color: #075fd1; text-decoration: underline; }
    .submission-detail-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 0 22px; }
    .submission-detail-meta div { padding: 8px 0; border-bottom: 1px solid #edf0f1; }
    .submission-detail-meta strong { display: inline-block; min-width: 105px; color: #5b6a7b; font-size: .7rem; text-transform: uppercase; }
    .submission-detail-badge { display: inline-block; padding: 5px 10px; border-radius: 999px; background: #e4f5ee; color: #087453; font-size: .7rem; font-weight: 800; }
    .submission-detail-badge.pending { background: #fff1cf; color: #9a5b00; }
    .submission-detail-badge.submitted { background: #eaf2ff; color: #155ad9; }
    .submission-detail-badge.completed { background: #d9f6e9; color: #06734f; }
    .submission-detail-badge.approved { background: #d9f6e9; color: #06734f; }
    .submission-detail-badge.returned { background: #f1eaff; color: #7c4dcb; }
    .submission-detail-badge.returned_overdue { background: #fde8e7; color: #b42318; border: 1px solid #fcc5c0; }
    .submission-detail-badge.approved_overdue { background: #fde8e7; color: #b42318; border: 1px solid #fcc5c0; }
    .submission-detail-badge.all-status { background: #f0f2f5; color: #5b6a7b; }
    .submission-detail-priority { font-weight: 800; color: #cf2525; }
    .submission-response-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(250px, 1fr); gap: 22px; }
    .submission-detail-note { padding: 12px; border-left: 3px solid #2d80f2; background: #f2f7ff; color: #31557d; }
    .submission-detail-card.sent-by-po { border-color: #c9ddf8; border-left: 3px solid #2d80f2; }
    .submission-detail-card.sent-by-po h3 { color: #145aa8; background: #f2f7ff; }
    .submission-detail-card.fo-response { border-color: #cde8dc; border-left: 3px solid #20a464; }
    .submission-detail-card.fo-response h3 { color: #176650; background: #f1faf5; }
    .submission-detail-card.fo-response .submission-detail-card-body { padding: 18px 16px; }
    .submission-detail-card.fo-response .submission-response-grid { align-items: stretch; gap: 24px; }
    .submission-detail-card.fo-response .submission-response-grid > div:first-child { padding: 4px 20px 4px 0; border-right: 1px solid #e1ece6; }
    .submission-detail-card.fo-response .submission-response-grid > div:first-child > strong { display: block; color: #536579; font-size: .72rem; letter-spacing: .02em; text-transform: uppercase; }
    .submission-detail-card.fo-response .submission-detail-meta { grid-template-columns: 1fr; gap: 0; padding: 0 12px; border-radius: 7px; background: #f7fbf8; }
    .submission-detail-card.fo-response .submission-detail-meta div { display: grid; grid-template-columns: minmax(92px, .82fr) minmax(0, 1.18fr); align-items: start; gap: 10px; padding: 9px 0; border-bottom: 1px solid #e1ece6; }
    .submission-detail-card.fo-response .submission-detail-meta div:last-child { border-bottom: 0; }
    .submission-detail-card.fo-response .submission-detail-meta strong { min-width: 0; color: #536579; font-size: .66rem; line-height: 1.35; }
    .submission-detail-card.fo-response .submission-detail-meta div > :not(strong) { color: #26384a; font-size: .78rem; line-height: 1.4; overflow-wrap: anywhere; }
    .submitted-office-responses { margin-top: 18px; padding-top: 14px; border-top: 1px solid #e1ece6; }
    .submitted-office-response-group + .submitted-office-response-group { margin-top: 16px; }
    .submitted-office-response-group > strong { display: block; margin-bottom: 8px; color: #536579; font-size: .72rem; letter-spacing: .02em; text-transform: uppercase; }
    .submitted-office-response { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 9px 11px; border: 1px solid #e1ece6; border-radius: 6px; background: #f7fbf8; }
    .submitted-office-response.clickable { display: block; cursor: pointer; }
    .submitted-office-response.clickable:hover, .submitted-office-response.clickable.expanded { border-color: #8bc9a9; background: #f1faf5; }
    .submitted-office-response-summary { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .submitted-office-response-details { display: none; margin-top: 10px; padding-top: 9px; border-top: 1px solid #d7e9dd; color: #26384a; font-size: .76rem; }
    .submitted-office-response.expanded .submitted-office-response-details { display: block; }
    .submitted-office-response-details p { margin: 0 0 8px; white-space: pre-wrap; overflow-wrap: anywhere; }
    .submitted-office-response-details a { margin-right: 10px; color: #075fd1; text-decoration: underline; }
    .submitted-office-response-details small { color: #667782; }
    .submitted-office-response + .submitted-office-response { margin-top: 7px; }
    .submitted-office-response strong, .submitted-office-response small { display: block; }
    .submitted-office-response strong { color: #26384a; font-size: .8rem; }
    .submitted-office-response small { margin-top: 2px; color: #667782; font-size: .72rem; }
    .submitted-office-empty { padding: 9px 11px; border: 1px dashed #cfded6; border-radius: 6px; color: #667782; font-size: .76rem; background: #fbfdfc; }
    .submission-detail-badge.overdue { background: #fde8e7; color: #b42318; }
    .submission-detail-card.attachments { border-color: #d9d4f5; border-left: 3px solid #6654c7; }
    .submission-detail-card.attachments h3 { color: #51419e; background: #f7f5ff; }
    .detail-section-heading { display: flex; align-items: center; gap: 8px; }
    .detail-section-icon { font-size: 1rem; }
    .detail-message { margin-top: 7px; color: #26384a; line-height: 1.55; white-space: normal; overflow-wrap: anywhere; }
    .detail-sent-by { margin-top: 13px; color: #536579; font-size: .8rem; }
    .detail-sent-by strong { color: #26384a; }
    .detail-sent-date { float: right; color: #536579; font-size: .78rem; }
    .detail-attachments-empty { display: flex; align-items: center; justify-content: center; gap: 10px; min-height: 55px; border: 1px dashed #cfd7e4; border-radius: 6px; color: #68788a; font-size: .82rem; }
    .detail-attachments-empty strong { color: #26384a; }
    .detail-attachment-label { margin-bottom: 8px; color: #536579; font-size: .78rem; font-weight: 700; }
    .submission-detail-files { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 10px; }
    .submission-detail-file { display: flex; align-items: center; gap: 10px; padding: 11px; border: 1px solid #e0e7ea; border-radius: 7px; background: #fff; }
    .submission-detail-file-icon { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 50%; background: #eef2f5; color: #334155; font-size: 1.1rem; flex-shrink: 0; }
    .submission-detail-file strong { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .78rem; }
    .submission-detail-file small { display: block; color: #64748b; font-size: .7rem; }
    .submission-detail-file a { margin-left: auto; color: #075fd1; font-size: .72rem; font-weight: 700; }
    .submission-detail-file-actions { display: flex; gap: 9px; margin-left: auto; white-space: nowrap; }
    .submission-detail-file-actions a { margin-left: 0; }
    .submission-detail-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 15px 22px; border-top: 1px solid #e4eaec; }
    .submission-detail-footer button { min-width: 125px; }
    @media (max-width: 650px) { #detailModal { padding: 7px; } .submission-detail-scroll { padding: 12px; } .submission-detail-meta, .submission-response-grid { grid-template-columns: 1fr; } .submission-detail-card.fo-response .submission-response-grid > div:first-child { padding: 0 0 14px; border-right: 0; border-bottom: 1px solid #e1ece6; } .submission-detail-card.fo-response .submission-detail-meta { padding: 0 10px; } .submission-detail-footer { flex-direction: column; } .submission-detail-footer button { width: 100%; } }
    
    .modal-close {
      float: right;
      font-size: 1.5rem;
      cursor: pointer;
      color: var(--text-muted);
    }
    
    .form-group {
      margin-bottom: 1rem;
    }
    
    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.3rem;
    }
    
    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 0.6rem;
      border: 1px solid var(--border-light);
      border-radius: 4px;
      font-family: inherit;
    }

    .form-group input[aria-invalid="true"] {
      border-color: #d32f2f;
    }

    .field-error {
      display: none;
      margin-top: 0.35rem;
      color: #d32f2f;
      font-size: 0.85rem;
    }

    .field-error.visible {
      display: block;
    }
    
    .form-group textarea {
      resize: vertical;
      min-height: 100px;
    }
    
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--dilg-brown);
      box-shadow: 0 0 0 3px rgba(93, 64, 55, 0.1);
    }

    .office-selector { position: relative; }
    .office-trigger { display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0.6rem; border: 1px solid var(--border-light); border-radius: 4px; background: #fff; color: var(--text-primary); font: inherit; text-align: left; cursor: pointer; }
    .office-trigger[aria-expanded="true"] { border-color: #176dcc; box-shadow: 0 0 0 3px rgba(23, 109, 204, .12); }
    .office-trigger .placeholder { color: #777; }
    .office-trigger .chevron { color: #5d6b78; transition: transform .2s ease; }
    .office-trigger[aria-expanded="true"] .chevron { transform: rotate(180deg); }
    .office-menu { display: none; position: absolute; z-index: 20; top: calc(100% + 5px); left: 0; right: 0; overflow: hidden; border: 1px solid #cbd7e2; border-radius: 6px; background: #fff; box-shadow: 0 12px 28px rgba(15, 35, 49, .16); }
    .office-menu.open { display: block; }
    .office-search { position: relative; padding: 7px; border-bottom: 1px solid #e2e8ef; }
    .office-search input { width: 100%; padding: 8px 34px 8px 10px; border: 1px solid #d3dce5; border-radius: 4px; font: inherit; }
    .office-search svg { position: absolute; top: 15px; right: 17px; width: 16px; height: 16px; fill: none; stroke: #6b7785; stroke-width: 2; }
    .office-options { max-height: 245px; overflow-y: auto; padding: 4px 0; }
    .office-option { display: block; width: 100%; padding: 8px 10px; border: 0; background: #fff; color: #26384a; font: inherit; font-size: .9rem; text-align: left; cursor: pointer; }
    .office-option:hover, .office-option:focus { background: #edf5ff; outline: none; }
    .office-option.regional { background: #176dcc; color: #fff; font-weight: 700; }
    .office-option.regional:hover, .office-option.regional:focus { background: #125cae; }
    .office-empty { padding: 10px; color: #778492; font-size: .85rem; }
    .attachment-dropzone { border: 1px dashed #9ebde3; border-radius: 6px; padding: 16px 12px; background: #f7fbff; color: #55708d; text-align: center; cursor: pointer; }
    .attachment-dropzone:hover, .attachment-dropzone.dragover { border-color: #176dcc; background: #eef6ff; }
    .attachment-dropzone strong, .attachment-dropzone small { display: block; }
    .attachment-dropzone strong { font-size: .88rem; }
    .attachment-dropzone small { margin-top: 5px; font-size: .74rem; }
    .choose-files-btn { margin-top: 9px; padding: 7px 12px; border: 1px solid #d2dce6; border-radius: 5px; background: #fff; color: #26384a; font: inherit; font-size: .78rem; font-weight: 700; cursor: pointer; }
    .attachment-list { display: grid; gap: 6px; margin-top: 8px; }
    .attachment-item { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 7px 9px; border: 1px solid #e0e7ee; border-radius: 5px; background: #fff; font-size: .78rem; }
    .attachment-item small { color: #718096; }
    .attachment-remove { border: 0; background: transparent; color: #b42318; font: inherit; font-weight: 700; cursor: pointer; }
    
    .form-actions {
      display: flex;
      gap: 1rem;
      margin-top: 1.5rem;
      flex-wrap: wrap;
    }
    
    .form-actions .btn {
      flex: 1;
      min-width: 100px;
    }
    
    .notification {
      position: fixed;
      top: 1rem;
      right: 1rem;
      padding: 1rem 1.5rem;
      background: var(--success);
      color: var(--white);
      border-radius: 4px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      z-index: 2000;
      display: none;
    }
    
    .notification.active {
      display: block;
      animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    
    @media (max-width: 768px) {
      .wrapper {
        flex-direction: column;
      }
      
      .sidebar {
        width: 100%;
      }
      
      .topbar {
        flex-direction: column;
        gap: 1rem;
      }
      
      .search-box {
        width: 100%;
      }
      
      .content-wrapper {
        grid-template-columns: 1fr;
      }
      
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .page-content {
        padding: 1rem;
      }
      
      .page-header-top {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <div class="logo-mark">D</div>
        <div>
          <div class="logo">DILG</div>
          <div class="label">Provincial Office User</div>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:10px;margin:14px 4px 0;padding:10px 8px;border:1px solid rgba(255,255,255,.12);border-radius:12px;background:rgba(255,255,255,.08);"><div style="width:36px;height:36px;display:grid;place-items:center;flex:0 0 36px;overflow:hidden;border:1px solid rgba(255,255,255,.28);border-radius:50%;background:rgba(255,255,255,.14);font-size:.82rem;font-weight:800;"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="PO User profile photo" style="width:100%;height:100%;object-fit:cover;display:block;"><?php else: ?><?= htmlspecialchars(strtoupper(substr($userName, 0, 2))) ?><?php endif; ?></div><div style="min-width:0;"><span style="display:flex;align-items:center;gap:6px;font-size:.72rem;color:rgba(255,255,255,.88);"><span style="width:7px;height:7px;border-radius:50%;background:#34d399;"></span>Online</span><span style="display:block;margin-top:3px;color:#fff;font-size:.78rem;font-weight:700;"><?= htmlspecialchars($userName) ?></span></div></div>
      <nav class="sidebar-nav">
        <li><a href="dashboard.php"><span class="nav-icon"><?= po_icon('dashboard') ?></span><span>Dashboard</span></a></li>
        <li><a href="inquiries.php" class="active"><span class="nav-icon"><?= po_icon('submissions') ?></span><span>My Submissions</span></a></li>
        <li><a href="office.php"><span class="nav-icon"><?= po_icon('office') ?></span><span>My Office</span></a></li>
        <li><a href="calendar.php"><span class="nav-icon"><?= po_icon('calendar') ?></span><span>Calendar</span></a></li>
        <li><a href="reports.php"><span class="nav-icon"><?= po_icon('reports') ?></span><span>Reports</span></a></li>
        <li><a href="notifications.php"><span class="nav-icon"><?= po_icon('notifications') ?></span><span>Notifications</span></a></li>
        <li><a href="settings.php"><span class="nav-icon"><?= po_icon('settings') ?></span><span>Settings</span></a></li>
        <li><a href="#" onclick="logout()"><span class="nav-icon"><?= po_icon('logout') ?></span><span>Log Out</span></a></li>
      </nav>
    </aside>
    
    <div class="main-content">
      <header class="topbar">
        <div class="topbar-left">
          <div class="brand-mark">D</div>
          <div class="breadcrumb">
            <div class="main-title">DEPARTMENT OF THE INTERIOR AND LOCAL GOVERNMENT</div>
            <div class="sub-title">Province of Surigao del Sur &bull; Provincial Office Dashboard</div>
          </div>
        </div>

        <div class="topbar-right">
          <div class="search-box">
            <input type="text" id="globalSearch" placeholder="Search dashboard...">
            <span class="search-icon"><?= po_icon('search') ?></span>
          </div>

          <div class="header-actions">
            <button type="button" class="notification-badge" aria-label="Open notifications" onclick="window.location.href='notifications.php'"><?= po_icon('notifications') ?></button>

            <button type="button" class="user-profile" aria-label="Open profile settings" onclick="window.location.href='settings.php'">
              <div class="user-avatar"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="PO User profile photo" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;"><?php else: ?><?= htmlspecialchars(strtoupper(substr($userName, 0, 1))) ?><?php endif; ?></div>
              <div class="user-text">
                <span class="user-name"><?= htmlspecialchars($userName) ?></span>
                <span class="user-role">Provincial Office</span>
              </div>
            </button>
          </div>
        </div>
      </header>
      
      <div class="page-content">
        <div class="page-header">
          <div class="page-header-top">
            <div>
              <h1 class="page-title">My Submissions</h1>
              <p class="page-description">View and manage all inquiries you've submitted to the FO for review and response.</p>
            </div>
            <button class="btn btn-primary" onclick="openCreateModal()">+ Create New Submit</button>
          </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid" id="statsContainer">
          <button type="button" class="stat-card total" data-status="ALL" data-stat="TOTAL">
            <div class="stat-icon"><?= po_icon('document') ?></div>
            <div class="stat-label">Total</div>
            <div class="stat-value">0</div>
          </button>
          <button type="button" class="stat-card pending" data-status="PENDING" data-stat="PENDING">
            <div class="stat-icon"><?= po_icon('pending') ?></div>
            <div class="stat-label">Pending</div>
            <div class="stat-value">0</div>
          </button>
          <button type="button" class="stat-card reviewing" data-status="IN_PROGRESS" data-stat="IN_PROGRESS">
            <div class="stat-icon"><?= po_icon('eye') ?></div>
            <div class="stat-label">Under Review</div>
            <div class="stat-value">0</div>
          </button>
          <button type="button" class="stat-card overdue" data-status="OVERDUE" data-stat="OVERDUE">
            <div class="stat-icon"><?= po_icon('overdue') ?></div>
            <div class="stat-label">Overdue</div>
            <div class="stat-value">0</div>
          </button> 
          <button type="button" class="stat-card returned" data-status="RETURNED" data-stat="RETURNED">
            <div class="stat-icon"><?= po_icon('returned') ?></div>
            <div class="stat-label">Returned</div>
            <div class="stat-value">0</div>
          </button>
          <button type="button" class="stat-card resolved" data-status="APPROVED" data-stat="APPROVED">
            <div class="stat-icon"><?= po_icon('approved') ?></div>
            <div class="stat-label">Approved</div>
            <div class="stat-value">0</div>
          </button>
        </div>

        <div class="content-wrapper" id="inquiriesSection">
          <!-- Inquiries List -->
          <div class="inquiries-section">
            <h2 class="section-title">List of Submits</h2>
            <div class="submission-toolbar">
              <div class="submission-tabs" role="tablist" aria-label="Inquiry status">
                <button type="button" class="submission-tab active" data-status-tab="ALL">All</button>
                <button type="button" class="submission-tab" data-status-tab="PENDING">Pending</button>
                <button type="button" class="submission-tab" data-status-tab="IN_PROGRESS">Under Review</button>
                <button type="button" class="submission-tab" data-status-tab="OVERDUE">Overdue</button>
                <button type="button" class="submission-tab" data-status-tab="RETURNED">Return</button>
                <button type="button" class="submission-tab" data-status-tab="APPROVED">Approved</button>
              </div>
              <input id="inquirySearch" class="list-search" type="search" placeholder="Search inquiries...">
            </div>
            <div id="inquiriesContainer" class="inquiry-list">
              <div class="loading">Loading inquiries...</div>
            </div>
          </div>

          <aside>
            <div class="filter-panel">
              <h3 class="section-title" style="margin-top: 0;">Filter Inquiries</h3>
              <div class="filter-group">
                <label class="filter-label">Status</label>
                <div class="filter-options">
                  <div class="filter-option"><input type="checkbox" id="filter-all-status" checked onchange="applyFilters()"><label for="filter-all-status">All Status</label></div>
                  <div class="filter-option"><input type="checkbox" id="filter-in-progress" data-status="IN_PROGRESS" onchange="applyFilters()"><label for="filter-in-progress">Under Review</label></div>
                  <div class="filter-option"><input type="checkbox" id="filter-pending" data-status="PENDING" onchange="applyFilters()"><label for="filter-pending">Pending Review</label></div>
                  <div class="filter-option"><input type="checkbox" id="filter-approved" data-status="APPROVED" onchange="applyFilters()"><label for="filter-approved">Approved</label></div>
                  <div class="filter-option"><input type="checkbox" id="filter-returned" data-status="RETURNED" onchange="applyFilters()"><label for="filter-returned">Return</label></div>
                  <div class="filter-option"><input type="checkbox" id="filter-overdue" data-status="OVERDUE" onchange="applyFilters()"><label for="filter-overdue">Overdue</label></div>
                </div>
              </div>
              <div class="filter-group">
                <label class="filter-label">Date Range</label>
                <div class="filter-options">
                  <div class="filter-option"><input type="radio" id="date-all" name="dateRange" value="" checked onchange="applyFilters()"><label for="date-all">All Time</label></div>
                  <div class="filter-option"><input type="radio" id="date-today" name="dateRange" value="today" onchange="applyFilters()"><label for="date-today">Today</label></div>
                  <div class="filter-option"><input type="radio" id="date-week" name="dateRange" value="week" onchange="applyFilters()"><label for="date-week">This Week</label></div>
                  <div class="filter-option"><input type="radio" id="date-month" name="dateRange" value="month" onchange="applyFilters()"><label for="date-month">This Month</label></div>
                  <div class="filter-option"><input type="radio" id="date-year" name="dateRange" value="year" onchange="applyFilters()"><label for="date-year">This Year</label></div>
                </div>
              </div>
              <button class="clear-filters" type="button" onclick="clearFilters()">Clear Filters</button>
            </div>
          </aside>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Create Inquiry Modal -->
  <div id="createModal" class="modal">
    <div class="modal-content">
      <span class="modal-close" onclick="closeCreateModal()">&times;</span>
      <h2 style="margin-bottom: 1.5rem;" id="createModalTitle">Create New Submit</h2>
      <form id="createForm">
        <div class="form-group">
          <label>Submit Title *</label>
          <input type="text" id="inquiryTitle" required placeholder="Enter the subject of your submit">
        </div>
        <div class="form-group">
          <label>Priority</label>
          <select id="inquiryPriority">
            <option value="MEDIUM">Medium (Default)</option>
            <option value="LOW">Low</option>
            <option value="HIGH">High</option>
          </select>
        </div>
        <div class="form-group">
          <label for="officeSearch">Assign to FO User *</label>
          <div class="office-selector" id="officeSelector">
            <input type="hidden" id="inquiryAssignee" value="">
            <input type="hidden" id="inquiryRecipientOffice" value="">
            <button class="office-trigger" type="button" id="officeTrigger" aria-haspopup="listbox" aria-expanded="false" aria-controls="officeMenu"><span class="placeholder" id="officeValue">Select an office</span><span class="chevron" aria-hidden="true">?</span></button>
            <div class="office-menu" id="officeMenu">
              <div class="office-search"><input type="search" id="officeSearch" placeholder="Search offices" autocomplete="off" aria-label="Search offices"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"></circle><path d="m16 16 4 4"></path></svg></div>
              <div class="office-options" id="officeOptions" role="listbox" aria-label="Available offices"><div class="office-empty">Loading offices...</div></div>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label for="inquiryDueDate">Due Date &amp; Time *</label>
          <input type="datetime-local" id="inquiryDueDate" required aria-describedby="dueDateError">
          <span class="field-error" id="dueDateError">The due date and time are required.</span>
        </div>
        <div class="form-group">
          <label>Details *</label>
          <textarea id="inquiryDetails" required placeholder="Provide detailed information about your inquiry..."></textarea>
        </div>
        <div class="form-group">
          <label for="inquiryDocuments">Document Attachment</label>
          <div class="attachment-dropzone" id="attachmentDropzone" tabindex="0" role="button" aria-describedby="attachmentHelp">
            <strong>Drag and drop files here</strong>
            <button type="button" class="choose-files-btn" id="chooseFilesBtn">Choose Files</button>
            <small id="attachmentHelp">PDF, DOC, DOCX, JPG, PNG (Max 10 MB each)</small>
          </div>
          <input type="file" id="inquiryDocuments" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple hidden>
          <div class="attachment-list" id="attachmentList" aria-live="polite"></div>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-primary" id="saveSubmissionBtn" onclick="submitForm()">Save &amp; Submit</button>
          <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Inquiry Detail Modal -->
  <div id="detailModal" class="modal">
    <div class="modal-content">
      <div class="submission-detail-header"><button type="button" class="submission-detail-back" onclick="closeSubmissionDetails()">&larr; Back</button><h2>Submission Details</h2><div><button type="button" class="submission-detail-print" onclick="printSubmissionDetails()">Print</button></div></div>
      <div class="submission-detail-scroll" id="detailBody"><div class="loading">Loading submission details...</div></div>
      <div class="submission-detail-footer"><button type="button" class="btn btn-primary" id="editSubmissionBtn" hidden>Edit Submission</button><button type="button" class="btn btn-secondary" onclick="closeSubmissionDetails()">Close</button></div>
    </div>
  </div>

  <!-- Notification -->
  <div id="notification" class="notification"></div>
  
  <script>
    const API_BASE = '../shared/auth/po_inquiries_api.php';
    let allInquiries = [];
    let visibleInquiries = [];
    let editingSubmissionId = 0;
    let editingSubmissionCategory = '';
    let officeUsers = [];
    let officeNames = [];
    let selectedInquiryFiles = [];
    let activeStatusFilter = 'ALL';
    const inquiryPageSize = 5;
    
    // Initialize page
    function init() {
      loadStats();
      loadInquiries();
    }

    function normalizeCardStatus(status) {
      const value = String(status || '').trim().toUpperCase();
      if (value === 'COMPLETED') return 'APPROVED';
      if (value === 'SUBMITTED' || value === 'IN_PROGRESS') return 'IN_PROGRESS';
      return value;
    }

    function countOfficeEntries(value) {
      return String(value || '')
        .split(',')
        .map((entry) => entry.trim())
        .filter(Boolean).length;
    }

    function countExactStatus(inquiries, targetStatus) {
      if (!Array.isArray(inquiries)) return 0;

      return inquiries.reduce((total, inquiry) => {
        const status = normalizeCardStatus(inquiry.status);
        const pending = countOfficeEntries(inquiry.pending_offices);
        const submitted = countOfficeEntries(inquiry.submitted_offices);
        const overdue = countOfficeEntries(inquiry.overdue_offices);
        const returned = countOfficeEntries(inquiry.returned_offices);
        const approved = countOfficeEntries(inquiry.approved_offices);
        const totalEntries = Number(inquiry.recipient_count || 0);
        const isBroadcast = inquiry.recipient_office === 'All Municipalities / Cities';

        if (targetStatus === 'PENDING') {
          const value = totalEntries > 0 && isBroadcast ? pending || Math.max(0, totalEntries - (submitted + overdue + returned + approved)) : (status === 'PENDING' ? 1 : 0);
          return total + value;
        }

        if (targetStatus === 'IN_PROGRESS') {
          const value = totalEntries > 0 && isBroadcast ? submitted : (['SUBMITTED', 'IN_PROGRESS'].includes(status) ? 1 : 0);
          return total + value;
        }

        if (targetStatus === 'OVERDUE') {
          const value = totalEntries > 0 && isBroadcast ? overdue : (status === 'OVERDUE' ? 1 : 0);
          return total + value;
        }

        if (targetStatus === 'RETURNED') {
          const value = totalEntries > 0 && isBroadcast ? returned : (status === 'RETURNED' ? 1 : 0);
          return total + value;
        }

        if (targetStatus === 'APPROVED') {
          const value = totalEntries > 0 && isBroadcast ? approved : (status === 'APPROVED' ? 1 : 0);
          return total + value;
        }

        return total;
      }, 0);
    }

    function countInquiryTotalEntries(inquiry) {
      if (!inquiry) return 0;
      const totalEntries = Number(inquiry.recipient_count || 0);
      if (totalEntries > 0) return totalEntries;
      return 1;
    }

    function syncStatusCardTotals(stats = null) {
      const cards = document.querySelectorAll('.stat-card');
      const baseStats = stats || { PENDING: 0, IN_PROGRESS: 0, APPROVED: 0, OVERDUE: 0, RETURNED: 0, TOTAL: 0 };
      const exactCounts = Array.isArray(allInquiries)
        ? {
            TOTAL: allInquiries.reduce((sum, inquiry) => sum + countInquiryTotalEntries(inquiry), 0),
            PENDING: countExactStatus(allInquiries, 'PENDING'),
            IN_PROGRESS: countExactStatus(allInquiries, 'IN_PROGRESS'),
            APPROVED: countExactStatus(allInquiries, 'APPROVED'),
            OVERDUE: countExactStatus(allInquiries, 'OVERDUE'),
            RETURNED: countExactStatus(allInquiries, 'RETURNED'),
          }
        : baseStats;
      const values = {
        TOTAL: exactCounts.TOTAL ?? baseStats.TOTAL ?? 0,
        PENDING: exactCounts.PENDING ?? baseStats.PENDING ?? 0,
        IN_PROGRESS: exactCounts.IN_PROGRESS ?? baseStats.IN_PROGRESS ?? 0,
        APPROVED: exactCounts.APPROVED ?? baseStats.APPROVED ?? 0,
        OVERDUE: exactCounts.OVERDUE ?? baseStats.OVERDUE ?? 0,
        RETURNED: exactCounts.RETURNED ?? baseStats.RETURNED ?? 0,
      };

      cards.forEach((card) => {
        const key = card.dataset.stat;
        card.querySelector('.stat-value').textContent = values[key] ?? 0;
      });
    }
    
    // Load statistics
    async function loadStats() {
      try {
        const response = await fetch(`${API_BASE}?action=stats`);
        const data = await response.json();
        
        if (data.success) {
          const stats = data.stats;
          syncStatusCardTotals(stats);
        }
      } catch (error) {
        console.error('Error loading stats:', error);
      }
    }

    // Load inquiries
    async function loadInquiries() {
      try {
        const search = document.getElementById('globalSearch')?.value || '';
        const params = new URLSearchParams({ action: 'list', search });
        
        const response = await fetch(`${API_BASE}?${params}`);
        const data = await response.json();
        
        if (data.success) {
          allInquiries = data.inquiries;
          syncStatusCardTotals({
            TOTAL: (allInquiries || []).length,
            PENDING: countExactStatus(allInquiries, 'PENDING'),
            IN_PROGRESS: countExactStatus(allInquiries, 'IN_PROGRESS'),
            APPROVED: countExactStatus(allInquiries, 'APPROVED'),
            OVERDUE: countExactStatus(allInquiries, 'OVERDUE'),
            RETURNED: countExactStatus(allInquiries, 'RETURNED'),
          });
          const requestedStatus = new URLSearchParams(window.location.search).get('status');
          if (requestedStatus) {
            applyStatusSelection(requestedStatus);
          } else {
            renderInquiries(allInquiries);
          }
        }
      } catch (error) {
        console.error('Error loading inquiries:', error);
        showNotification('Error loading inquiries', 'error');
      }
    }
    
    function closeMunicipalityPopover(event, button) {
      event.stopPropagation();
      const popover = button.closest('.municipality-popover');
      if (popover) popover.classList.remove('is-open');
      const trigger = popover?.previousElementSibling;
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
    }

    function toggleMunicipalityPopover(event, button) {
      event.stopPropagation();
      document.querySelectorAll('.municipality-popover.is-open').forEach(popover => {
        popover.classList.remove('is-open');
        popover.previousElementSibling?.setAttribute('aria-expanded', 'false');
      });
      const popover = button.nextElementSibling;
      if (!popover) return;
      popover.classList.add('is-open');
      button.setAttribute('aria-expanded', 'true');
      const triggerRect = button.getBoundingClientRect();
      const popoverWidth = Math.min(290, window.innerWidth - 24);
      const left = Math.min(Math.max(12, triggerRect.left), window.innerWidth - popoverWidth - 12);
      const topBelow = triggerRect.bottom + 6;
      const top = topBelow + popover.offsetHeight <= window.innerHeight - 12
        ? topBelow
        : Math.max(12, triggerRect.top - popover.offsetHeight - 6);
      popover.style.left = `${left}px`;
      popover.style.top = `${top}px`;
    }

    document.addEventListener('click', () => {
      document.querySelectorAll('.municipality-popover.is-open').forEach(popover => {
        popover.classList.remove('is-open');
        popover.previousElementSibling?.setAttribute('aria-expanded', 'false');
      });
    });

    // Render inquiries
    function renderInquiries(inquiries, page = 1) {
      const container = document.getElementById('inquiriesContainer');
      const categoryInquiries = inquiries.filter(inquiry => inquiryMatchesStatus(inquiry, activeStatusFilter));
      visibleInquiries = categoryInquiries;
      window.visibleInquiryResults = categoryInquiries;
      
      if (categoryInquiries.length === 0) {
        container.innerHTML = `
          <div class="empty-state">
            <div class="empty-icon"><?= po_icon('document') ?></div>
            <div class="empty-title">No inquiries yet</div>
            <div class="empty-description">You haven't created any inquiries.</div>
            <button class="btn btn-primary" onclick="openCreateModal()">+ Create New Submit</button>
          </div>
        `;
        return;
      }

      const totalPages = Math.max(1, Math.ceil(categoryInquiries.length / inquiryPageSize));
      const safePage = Math.min(Math.max(1, page), totalPages);
      const pageItems = categoryInquiries.slice((safePage - 1) * inquiryPageSize, safePage * inquiryPageSize);
      const rows = pageItems.map(inquiry => {
        const status = (inquiry.status || '').toUpperCase();
        let displayStatus = status;
        
        // Handle broadcast submissions (All Municipalities / Cities)
        if (inquiry.recipient_office === 'All Municipalities / Cities') {
          // In Under Review section, show SUBMITTED instead of PENDING if there are any submitted offices
          if (activeStatusFilter === 'IN_PROGRESS' && status === 'PENDING' && (inquiry.submitted_offices || inquiry.overdue_offices)) {
            displayStatus = inquiry.submitted_offices ? 'SUBMITTED' : 'OVERDUE';
          }
          // For other filters, use the filter name if appropriate
          else if (['APPROVED', 'SUBMITTED', 'RETURNED', 'OVERDUE'].includes(activeStatusFilter)
            && ((activeStatusFilter === 'APPROVED' && inquiry.approved_offices) || (activeStatusFilter === 'SUBMITTED' && inquiry.submitted_offices) || (activeStatusFilter === 'RETURNED' && inquiry.returned_offices) || (activeStatusFilter === 'OVERDUE' && inquiry.overdue_offices))) {
            displayStatus = activeStatusFilter;
          }
        }

        // Only adjust the display label for the Under Review view; other statuses remain unchanged.
        if (activeStatusFilter === 'IN_PROGRESS' && displayStatus === 'PENDING') {
          displayStatus = 'SUBMITTED';
        }

        const statusClass = displayStatus.toLowerCase();
        const submittedAt = inquiry.response_submitted_at || inquiry.fo_response_at || inquiry.created_at;
        const submittedDate = new Date(String(submittedAt).replace(' ', 'T'));
        const formattedDate = submittedDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const formattedTime = submittedDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        const priority = (inquiry.priority || 'MEDIUM').toUpperCase();
        const updatedDate = new Date(inquiry.updated_at || inquiry.created_at);
        const updatedText = updatedDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const dueDate = inquiry.due_at ? new Date(inquiry.due_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : 'Not specified';
        const assignedOffice = inquiry.recipient_office || inquiry.assigned_office || 'Office not assigned';
        const pendingOffices = inquiry.pending_offices || '';
        const submittedOffices = inquiry.submitted_offices || '';
        const approvedOffices = inquiry.approved_offices || '';
        const returnedOffices = inquiry.returned_offices || '';
        const overdueOffices = inquiry.overdue_offices || '';
        const renderDropdownGroup = (officeNames, className, label) => {
          const offices = officeNames.split(',').map(office => office.trim()).filter(Boolean);
          if (!offices.length) return '';
          return `<div class="municipality-dropdown-group ${className}"><span class="municipality-dropdown-group-title">${escapeHtml(label)} (${offices.length})</span>${offices.map(office => `<div class="municipality-dropdown-item">${escapeHtml(office)}</div>`).join('')}</div>`;
        };
        const statusDisplay = assignedOffice === 'All Municipalities / Cities' && activeStatusFilter === 'ALL'
          ? `${approvedOffices ? '<span class="table-status approved">APPROVED</span>' : ''}${returnedOffices ? '<span class="table-status returned">RETURNED</span>' : ''}${submittedOffices ? '<span class="table-status submitted">SUBMITTED</span>' : ''}${overdueOffices ? '<span class="table-status overdue">OVERDUE</span>' : ''}${pendingOffices ? '<span class="table-status pending">PENDING</span>' : ''}`
          : `<span class="table-status ${statusClass}">${escapeHtml(displayStatus)}</span>`;
        const dropdownGroups = activeStatusFilter === 'APPROVED'
          ? renderDropdownGroup(approvedOffices, 'approved', 'Approved')
          : activeStatusFilter === 'RETURNED'
          ? renderDropdownGroup(returnedOffices, 'returned', 'Return')
          : activeStatusFilter === 'SUBMITTED'
          ? renderDropdownGroup(submittedOffices, 'submitted', 'Submitted')
          : activeStatusFilter === 'OVERDUE'
            ? renderDropdownGroup(overdueOffices, 'overdue', 'Overdue')
            : activeStatusFilter === 'PENDING'
              ? renderDropdownGroup(pendingOffices, 'pending', 'Pending')
              : activeStatusFilter === 'IN_PROGRESS'
                ? `${renderDropdownGroup(submittedOffices, 'submitted', 'Submitted')}${renderDropdownGroup(overdueOffices, 'overdue', 'Overdue')}`
                : `${renderDropdownGroup(submittedOffices, 'submitted', 'Submitted')}${renderDropdownGroup(overdueOffices, 'overdue', 'Overdue')}${renderDropdownGroup(pendingOffices, 'pending', 'Pending')}${renderDropdownGroup(returnedOffices, 'returned', 'Return')}${renderDropdownGroup(approvedOffices, 'approved', 'Approved')}`;
        const officeDisplay = assignedOffice === 'All Municipalities / Cities'
          ? `<details class="municipality-dropdown"><summary>All Municipalities / Cities</summary><div class="municipality-dropdown-panel">${dropdownGroups || '<div class="municipality-dropdown-item">No municipality responses in this category.</div>'}</div></details>`
          : escapeHtml(assignedOffice);
        const actions = `<button class="table-action" type="button" title="View inquiry" aria-label="View inquiry" onclick="viewInquiry(${inquiry.id})"><?= po_icon('view') ?></button>
          <button class="table-action delete" type="button" title="Delete inquiry" aria-label="Delete inquiry" onclick="deleteInquiry(${inquiry.id})">&times;</button>`;

        return `
          <tr>
            <td><span class="inquiry-ref">SUBS-${new Date(inquiry.created_at).getFullYear()}-${String(inquiry.id).padStart(4, '0')}</span></td>
            <td><span class="inquiry-subject">${escapeHtml(inquiry.title)}</span></td>
            <td>${formattedDate}<small>${formattedTime}</small></td>
            <td>${statusDisplay}</td>
            <td><span class="table-status ${priority.toLowerCase()}">${escapeHtml(priority.charAt(0) + priority.slice(1).toLowerCase())}</span></td>
            <td>${escapeHtml(dueDate)}</td>
            <td>${updatedText}</td>
            <td>${officeDisplay}</td>
            <td><div class="table-actions">${actions}</div></td>
          </tr>
        `;
      }).join('');

      container.innerHTML = `
        <div class="inquiry-table-wrap">
          <table class="inquiry-table">
            <thead><tr><th>Submission #</th><th>Title</th><th>Date &amp; Time Submitted</th><th>Status</th><th>Priority</th><th>Due Date</th><th>Last Updated</th><th>Assigned Municipality / City</th><th>Actions</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
        <div class="list-footer"><span>Showing ${(safePage - 1) * inquiryPageSize + 1} to ${Math.min(safePage * inquiryPageSize, categoryInquiries.length)} of ${categoryInquiries.length} entries</span><span>All submissions are up to date</span></div>
        <div class="pagination" aria-label="Submission pages">
          ${Array.from({ length: totalPages }, (_, index) => `<button type="button" class="${index + 1 === safePage ? 'active' : ''}" onclick="renderInquiries(window.visibleInquiryResults, ${index + 1})">${index + 1}</button>`).join('')}
        </div>
      `;

      document.querySelectorAll('.municipality-dropdown').forEach((dropdown) => {
        const summary = dropdown.querySelector('summary');
        const panel = dropdown.querySelector('.municipality-dropdown-panel');
        summary.addEventListener('click', () => {
          window.requestAnimationFrame(() => {
            if (!dropdown.open) return;
            const summaryRect = summary.getBoundingClientRect();
            const panelWidth = panel.getBoundingClientRect().width;
            const panelHeight = panel.getBoundingClientRect().height;
            const left = Math.min(Math.max(12, summaryRect.right - panelWidth), window.innerWidth - panelWidth - 12);
            const top = summaryRect.bottom + 5 + panelHeight <= window.innerHeight - 12
              ? summaryRect.bottom + 5
              : Math.max(12, summaryRect.top - panelHeight - 5);
            panel.style.left = `${left}px`;
            panel.style.top = `${top}px`;
          });
        });
      });
    }
    
    function applyStatusSelection(status) {
      const allCheckbox = document.getElementById('filter-all-status');
      const statusCheckboxes = document.querySelectorAll('.filter-options input[type="checkbox"][data-status]');
      activeStatusFilter = status;
      document.querySelectorAll('[data-status-tab]').forEach((tab) => {
        tab.classList.toggle('active', tab.dataset.statusTab === status);
      });

      if (status === 'ALL') {
        if (allCheckbox) allCheckbox.checked = true;
        statusCheckboxes.forEach(cb => cb.checked = false);
        renderInquiries(allInquiries);
        return;
      }

      if (allCheckbox) allCheckbox.checked = false;
      statusCheckboxes.forEach(cb => {
        cb.checked = cb.dataset.status === status;
      });
      applyFilters();
    }

    function inquiryMatchesStatus(inquiry, selectedStatus) {
      const inquiryStatus = String(inquiry.status || '').trim().toUpperCase();
      const normalizedSelectedStatus = String(selectedStatus || '').trim().toUpperCase();
      if (normalizedSelectedStatus === 'ALL') return true;
      if (inquiry.recipient_office === 'All Municipalities / Cities') {
        if (normalizedSelectedStatus === 'IN_PROGRESS') return ['SUBMITTED', 'IN_PROGRESS', 'OVERDUE'].includes(inquiryStatus) || Boolean(inquiry.submitted_offices) || Boolean(inquiry.overdue_offices);
        if (normalizedSelectedStatus === 'SUBMITTED') return Boolean(inquiry.submitted_offices) || inquiryStatus === 'SUBMITTED';
        if (normalizedSelectedStatus === 'APPROVED') return Boolean(inquiry.approved_offices) || inquiryStatus === 'APPROVED';
        if (normalizedSelectedStatus === 'RETURNED') return Boolean(inquiry.returned_offices) || inquiryStatus === 'RETURNED';
        if (normalizedSelectedStatus === 'OVERDUE') return Boolean(inquiry.overdue_offices) || inquiryStatus === 'OVERDUE';
        if (normalizedSelectedStatus === 'PENDING') return Boolean(inquiry.pending_offices) || inquiryStatus === 'PENDING';
        return false;
      }
      return inquiryStatus === normalizedSelectedStatus || (normalizedSelectedStatus === 'IN_PROGRESS' && ['SUBMITTED', 'IN_PROGRESS', 'OVERDUE'].includes(inquiryStatus));
    }

    // Apply filters
    function applyFilters() {
      const allCheckbox = document.getElementById('filter-all-status');
      const selectedStatuses = Array.from(document.querySelectorAll('.filter-options input[type="checkbox"]:checked'))
        .filter(cb => cb.dataset.status)
        .map(cb => cb.dataset.status);

      if (allCheckbox && allCheckbox.checked && selectedStatuses.length === 0) {
        activeStatusFilter = 'ALL';
        renderInquiries(allInquiries);
        return;
      }

      let filtered = allInquiries;
      activeStatusFilter = selectedStatuses.length === 1 ? selectedStatuses[0] : 'ALL';

      if (selectedStatuses.length > 0) {
        filtered = filtered.filter(i => selectedStatuses.some(selectedStatus => inquiryMatchesStatus(i, selectedStatus)));
      }

      const selectedDateRange = document.querySelector('input[name="dateRange"]:checked')?.value || '';
      if (selectedDateRange) {
        const now = new Date();
        const start = new Date(now);
        if (selectedDateRange === 'today') start.setHours(0, 0, 0, 0);
        if (selectedDateRange === 'week') { start.setDate(now.getDate() - now.getDay()); start.setHours(0, 0, 0, 0); }
        if (selectedDateRange === 'month') { start.setDate(1); start.setHours(0, 0, 0, 0); }
        if (selectedDateRange === 'year') { start.setMonth(0, 1); start.setHours(0, 0, 0, 0); }
        filtered = filtered.filter(i => new Date(i.created_at) >= start);
      }

      renderInquiries(filtered);
      syncStatusCardTotals({
        TOTAL: (allInquiries || []).length,
        PENDING: countExactStatus(allInquiries, 'PENDING'),
        IN_PROGRESS: countExactStatus(allInquiries, 'IN_PROGRESS'),
        APPROVED: countExactStatus(allInquiries, 'APPROVED'),
        OVERDUE: countExactStatus(allInquiries, 'OVERDUE'),
        RETURNED: countExactStatus(allInquiries, 'RETURNED'),
      });
    }
    
    // Clear filters
    function clearFilters() {
      const allCheckbox = document.getElementById('filter-all-status');
      if (allCheckbox) allCheckbox.checked = true;
      document.querySelectorAll('.filter-options input[type="checkbox"][data-status]').forEach(cb => cb.checked = false);
      const allDate = document.getElementById('date-all');
      if (allDate) allDate.checked = true;
      activeStatusFilter = 'ALL';
      document.querySelectorAll('[data-status-tab]').forEach(tab => tab.classList.toggle('active', tab.dataset.statusTab === 'ALL'));
      renderInquiries(allInquiries);
    }
    
    // Open create modal
    function openCreateModal() {
      editingSubmissionId = 0;
      editingSubmissionCategory = '';
      document.getElementById('createModalTitle').textContent = 'Create New Submit';
      document.getElementById('saveSubmissionBtn').textContent = 'Save & Submit';
      document.getElementById('createModal').classList.add('active');
      loadFOUsers();
    }

    async function openEditSubmission(inquiry) {
      closeSubmissionDetails();
      editingSubmissionId = Number(inquiry.id);
      editingSubmissionCategory = inquiry.category || '';
      document.getElementById('createModalTitle').textContent = 'Edit Submission';
      document.getElementById('saveSubmissionBtn').textContent = 'Save Changes';
      document.getElementById('inquiryTitle').value = inquiry.title || '';
      document.getElementById('inquiryPriority').value = inquiry.priority || 'MEDIUM';
      document.getElementById('inquiryDetails').value = inquiry.details || '';
      document.getElementById('inquiryDueDate').value = String(inquiry.due_at || '').replace(' ', 'T').slice(0, 16);
      await loadFOUsers();
      setOfficeSelection(inquiry.recipient_office || '', inquiry.assigned_to || '');
      document.getElementById('createModal').classList.add('active');
    }

    async function loadFOUsers() {
      if (officeNames.length) return;
      try {
        const response = await fetch(`${API_BASE}?action=fo_users`);
        const data = await response.json();
        if (!data.success) return;
        officeNames = data.offices || [];
        officeUsers = data.users || [];
        renderOfficeOptions();
      } catch (error) {
        showNotification('Unable to load FO users', 'error');
      }
    }

    function renderOfficeOptions() {
      const term = document.getElementById('officeSearch').value.trim().toLowerCase();
      const options = officeNames.filter((office) => office.toLowerCase().includes(term));
      document.getElementById('officeOptions').innerHTML = options.length
        ? options.map((office) => `<button type="button" class="office-option${office === 'Regional Office' ? ' regional' : ''}" role="option" data-office="${office.replace(/&/g, '&amp;')}">${office}</button>`).join('')
        : '<div class="office-empty">No matching offices</div>';
      document.querySelectorAll('.office-option').forEach((option) => option.addEventListener('click', () => setOfficeSelection(option.dataset.office)));
    }

    function setOfficeSelection(office, assignedTo = '') {
      document.getElementById('inquiryRecipientOffice').value = office;
      document.getElementById('inquiryAssignee').value = assignedTo || (officeUsers.find((user) => user.office_name === office)?.id || '');
      const value = document.getElementById('officeValue');
      value.textContent = office || 'Select an office';
      value.classList.toggle('placeholder', !office);
      setOfficeMenuOpen(false);
    }

    function setOfficeMenuOpen(open) {
      document.getElementById('officeMenu').classList.toggle('open', open);
      document.getElementById('officeTrigger').setAttribute('aria-expanded', String(open));
      if (open) document.getElementById('officeSearch').focus();
    }

    document.getElementById('officeTrigger')?.addEventListener('click', () => setOfficeMenuOpen(!document.getElementById('officeMenu').classList.contains('open')));
    document.getElementById('officeSearch')?.addEventListener('input', renderOfficeOptions);
    document.addEventListener('click', (event) => { if (!document.getElementById('officeSelector')?.contains(event.target)) setOfficeMenuOpen(false); });
    document.getElementById('officeSearch')?.addEventListener('keydown', (event) => { if (event.key === 'Escape') setOfficeMenuOpen(false); });
    
    // Close create modal
    function closeCreateModal() {
      document.getElementById('createModal').classList.remove('active');
      document.getElementById('createForm').reset();
      selectedInquiryFiles = [];
      renderInquiryFiles();
      editingSubmissionId = 0;
      editingSubmissionCategory = '';
      document.getElementById('createModalTitle').textContent = 'Create New Submit';
      document.getElementById('saveSubmissionBtn').textContent = 'Save & Submit';
      document.getElementById('inquiryDueDate').removeAttribute('aria-invalid');
      document.getElementById('dueDateError').classList.remove('visible');
      setOfficeSelection('');
    }

    function renderInquiryFiles() {
      const list = document.getElementById('attachmentList');
      if (!list) return;
      list.innerHTML = selectedInquiryFiles.map((file, index) => `<div class="attachment-item"><span>${escapeHtml(file.name)} <small>(${formatSubmissionSize(file.size)})</small></span><button type="button" class="attachment-remove" data-file-index="${index}" aria-label="Remove ${escapeHtml(file.name)}">Remove</button></div>`).join('');
      list.querySelectorAll('.attachment-remove').forEach((button) => button.addEventListener('click', () => {
        selectedInquiryFiles.splice(Number(button.dataset.fileIndex), 1);
        renderInquiryFiles();
      }));
    }

    function addInquiryFiles(files) {
      const allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
      const incoming = [...files];
      const invalid = incoming.find((file) => !allowedExtensions.includes(file.name.split('.').pop().toLowerCase()));
      if (invalid) { showNotification('Only PDF, DOC, DOCX, JPG, and PNG files are allowed.', 'error'); return; }
      const oversized = incoming.find((file) => file.size > 10 * 1024 * 1024);
      if (oversized) { showNotification(`${oversized.name} exceeds the 10 MB limit.`, 'error'); return; }
      selectedInquiryFiles = [...selectedInquiryFiles, ...incoming];
      renderInquiryFiles();
    }

    const attachmentDropzone = document.getElementById('attachmentDropzone');
    const inquiryDocuments = document.getElementById('inquiryDocuments');
    attachmentDropzone?.addEventListener('click', (event) => { if (event.target !== document.getElementById('chooseFilesBtn')) inquiryDocuments.click(); });
    document.getElementById('chooseFilesBtn')?.addEventListener('click', () => inquiryDocuments.click());
    inquiryDocuments?.addEventListener('change', () => { addInquiryFiles(inquiryDocuments.files); inquiryDocuments.value = ''; });
    attachmentDropzone?.addEventListener('keydown', (event) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); inquiryDocuments.click(); } });
    attachmentDropzone?.addEventListener('dragover', (event) => { event.preventDefault(); attachmentDropzone.classList.add('dragover'); });
    attachmentDropzone?.addEventListener('dragleave', () => attachmentDropzone.classList.remove('dragover'));
    attachmentDropzone?.addEventListener('drop', (event) => { event.preventDefault(); attachmentDropzone.classList.remove('dragover'); addInquiryFiles(event.dataTransfer.files); });
    
    // Submit form
    async function submitForm() {
      const title = document.getElementById('inquiryTitle').value;
      const details = document.getElementById('inquiryDetails').value;
      const priority = document.getElementById('inquiryPriority').value;
      const assignedTo = document.getElementById('inquiryAssignee').value;
      const recipientOffice = document.getElementById('inquiryRecipientOffice').value;
      const dueDateInput = document.getElementById('inquiryDueDate');
      const dueDateError = document.getElementById('dueDateError');
      const dueDate = dueDateInput.value;
      
      if (!title || !details) {
        showNotification('Title and details are required', 'error');
        return;
      }

      if (!dueDate) {
        dueDateInput.setAttribute('aria-invalid', 'true');
        dueDateError.classList.add('visible');
        dueDateInput.focus();
        return;
      }

      if (!recipientOffice) {
        showNotification('Select an office before saving the submission', 'error');
        document.getElementById('inquiryAssignee').focus();
        return;
      }

      dueDateInput.removeAttribute('aria-invalid');
      dueDateError.classList.remove('visible');
      
      try {
        const action = editingSubmissionId ? 'update' : 'create';
        const payload = { title, details, priority, assigned_to: assignedTo, recipient_office: recipientOffice, due_at: dueDate };
        if (editingSubmissionId) payload.category = editingSubmissionCategory;
        if (editingSubmissionId) payload.id = editingSubmissionId;
        const submissionData = new FormData();
        Object.entries(payload).forEach(([key, value]) => submissionData.append(key, value));
        selectedInquiryFiles.forEach((file) => submissionData.append('documents[]', file));
        const response = await fetch(`${API_BASE}?action=${action}`, {
          method: 'POST',
          body: submissionData
        });
        
        const data = await response.json();
        
        if (data.success) {
          showNotification(editingSubmissionId ? 'Submission updated successfully' : 'Inquiry created and assigned to the FO user', 'success');
          
          closeCreateModal();
          loadInquiries();
          loadStats();
        } else {
          showNotification(data.error || (editingSubmissionId ? 'Error updating submission' : 'Error creating inquiry'), 'error');
        }
      } catch (error) {
        showNotification(editingSubmissionId ? 'Error updating submission' : 'Error creating inquiry', 'error');
      }
    }
    
    // Submit inquiry
    async function submitInquiry(inquiryId) {
      try {
        const response = await fetch(`${API_BASE}?action=submit`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: inquiryId })
        });
        
        const data = await response.json();
        
        if (data.success) {
          showNotification('Inquiry submitted successfully', 'success');
          loadInquiries();
          loadStats();
        } else {
          showNotification(data.error || 'Error submitting inquiry', 'error');
        }
      } catch (error) {
        showNotification('Error submitting inquiry', 'error');
      }
    }
    
    // Delete inquiry
    async function deleteInquiry(inquiryId) {
      if (!confirm('Are you sure you want to delete this inquiry?')) return;
      
      try {
        const response = await fetch(`${API_BASE}?action=delete`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: inquiryId })
        });
        
        const data = await response.json();
        
        if (data.success) {
          showNotification('Inquiry deleted successfully', 'success');
          loadInquiries();
          loadStats();
        } else {
          showNotification(data.error || 'Error deleting inquiry', 'error');
        }
      } catch (error) {
        showNotification('Error deleting inquiry', 'error');
      }
    }
    
    function closeSubmissionDetails() {
      document.getElementById('detailModal')?.classList.remove('active');
    }

    function formatSubmissionDate(value) {
      if (!value) return 'Not available';
      const date = new Date(String(value).replace(' ', 'T'));
      return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function formatSubmissionSize(bytes) {
      const size = Number(bytes || 0);
      if (!size) return 'Size unavailable';
      return size < 1024 * 1024 ? `${(size / 1024).toFixed(2)} KB` : `${(size / 1024 / 1024).toFixed(2)} MB`;
    }

    function submissionFileUrl(path) {
      const value = String(path || '');
      if (!value || value.includes('://') || value.startsWith('javascript:')) return '#';
      return value.replace(/^\/+/, '');
    }

    function printSubmissionDetails() {
      const content = document.getElementById('detailBody')?.innerHTML || '';
      const printWindow = window.open('', '_blank', 'width=900,height=700');
      if (!printWindow) return;
      printWindow.document.write(`<html><head><title>Submission Details</title><style>body{font:14px Arial;color:#1d2939;padding:24px}section{border:1px solid #dfe6e4;margin:0 0 14px;padding:14px;border-radius:6px}h3{color:#176650;font-size:13px;text-transform:uppercase}a{color:#075fd1}</style></head><body>${content}</body></html>`);
      printWindow.document.close();
      printWindow.focus();
      printWindow.print();
    }

    function renderDetailAttachments(files, emptyText = 'No attachments submitted.', showUploader = false) {
      if (!files.length) return `<div class="detail-attachments-empty"><span>&#128196;</span><span>${emptyText}<br><strong>Total: 0 file(s)</strong></span></div>`;
      return `<div class="submission-detail-files">${files.map(file => `<div class="submission-detail-file"><span class="submission-detail-file-icon">&#128196;</span><span><strong title="${escapeHtml(file.original_name || file.file_name || 'Document')}">${escapeHtml(file.original_name || file.file_name || 'Document')}</strong><small>${showUploader ? `Uploaded by: ${escapeHtml(file.uploader_name || file.uploader_username || 'FO User')}${file.uploader_office ? ` (${escapeHtml(file.uploader_office)})` : ''}<br>` : ''}${escapeHtml(file.mime_type || 'File')} | ${escapeHtml(formatSubmissionSize(file.file_size))} | ${escapeHtml(formatSubmissionDate(file.created_at))}</small></span><span class="submission-detail-file-actions"><a href="../shared/auth/download_document.php?id=${encodeURIComponent(file.id)}&amp;mode=inline" target="_blank" rel="noopener">Preview</a><a href="../shared/auth/download_document.php?id=${encodeURIComponent(file.id)}" target="_blank" rel="noopener" download>Download</a></span></div>`).join('')}</div><p><strong>Total:</strong> ${files.length} file(s)</p>`;
    }

    // View inquiry
    function viewInquiry(inquiryId) {
      if (!inquiryId) return;

      fetch(`${API_BASE}?action=detail&id=${inquiryId}`)
        .then((response) => response.json())
        .then((data) => {
          if (!data.success || !data.inquiry) {
            showNotification(data.error || 'Inquiry details are unavailable.', 'error');
            return;
          }

          const inquiry = data.inquiry;
          const modal = document.getElementById('detailModal');
          const body = document.getElementById('detailBody');
          if (!modal || !body) return;
          const editButton = document.getElementById('editSubmissionBtn');
          const canEdit = !['COMPLETED', 'RESOLVED'].includes(String(inquiry.status || '').toUpperCase());
          if (editButton) {
            editButton.hidden = !canEdit;
            editButton.onclick = () => openEditSubmission(inquiry);
          }

          const status = String(inquiry.status || 'PENDING');
          const isPendingStatus = status.toUpperCase() === 'PENDING';
          const dueDate = inquiry.due_at ? formatSubmissionDate(inquiry.due_at) : 'Not specified';
          const poFiles = inquiry.po_attachments || [];
          const visibleRecipientStatuses = ['PENDING', 'SUBMITTED', 'APPROVED', 'OVERDUE', 'RETURNED'];
          const recipientResponses = (inquiry.recipient_responses || []).filter(item => visibleRecipientStatuses.includes(String(item.status || '').toUpperCase()));
          const responseFiles = (Array.isArray(inquiry.response_attachments) && inquiry.response_attachments.length > 0)
            ? inquiry.response_attachments
            : recipientResponses.flatMap(item => {
                if (!Array.isArray(item.documents)) return [];
                return item.documents.map(file => ({
                  ...file,
                  uploader_name: file.uploader_name || item.full_name || item.username || 'FO User',
                  uploader_username: file.uploader_username || item.username || 'FO User',
                  uploader_office: file.uploader_office || item.office_name || ''
                }));
              });
          const hasSubmittedRecipientResponse = recipientResponses.some(item => ['SUBMITTED', 'APPROVED'].includes(String(item.status || '').toUpperCase()));
          const isSubmittedView = activeStatusFilter === 'SUBMITTED' && inquiry.recipient_office === 'All Municipalities / Cities' && hasSubmittedRecipientResponse;
          const isReturnView = activeStatusFilter === 'RETURNED';
          const isOverdueView = activeStatusFilter === 'OVERDUE';
          const isApprovedView = activeStatusFilter === 'APPROVED';
          const isTotalView = activeStatusFilter === 'ALL';
          const isUnderReviewWithPending = activeStatusFilter === 'IN_PROGRESS' && status.toUpperCase() === 'PENDING';
          const displayStatus = isReturnView ? 'RETURNED' : (isOverdueView ? 'OVERDUE' : (isApprovedView ? 'APPROVED' : (isUnderReviewWithPending ? 'SUBMITTED' : status)));
          const overviewStatusMap = {
            PENDING: 'FO Pending',
            SUBMITTED: 'FO Under Review',
            IN_PROGRESS: 'FO Under Review',
            OVERDUE: 'FO Overdue',
            RETURNED: 'FO Returned',
            APPROVED: 'FO Approved',
            COMPLETED: 'FO Approved'
          };
          const statusLabel = isTotalView ? 'All Total' : (isReturnView ? 'FO Returned' : (isOverdueView ? 'FO Overdue' : (isApprovedView ? 'FO Approved' : overviewStatusMap[String(displayStatus || 'PENDING').toUpperCase()] || String(displayStatus || 'PENDING').replace('_', ' '))));
          const statusBadgeClass = activeStatusFilter === 'ALL' ? 'all-status' : (isSubmittedView ? 'submitted' : displayStatus.toLowerCase());
          const sentDate = inquiry.submitted_at || inquiry.created_at;
          const isAllMunicipalities = inquiry.recipient_office === 'All Municipalities / Cities';
          const isSingleOfficeSubmission = Boolean(inquiry.recipient_office && inquiry.recipient_office !== 'All Municipalities / Cities');
          const singleOfficeResponse = Array.isArray(inquiry.recipient_responses)
            ? inquiry.recipient_responses.find((item) => {
                const targetOffice = String(inquiry.recipient_office || '').trim();
                const responseOffice = String(item.office_name || '').trim();
                const responseUserId = String(item.user_id || '');
                const assignedUserId = String(inquiry.assigned_to || '');
                if (!targetOffice) return false;
                return responseOffice === targetOffice || responseUserId === assignedUserId || responseOffice.includes(targetOffice) || targetOffice.includes(responseOffice);
              }) || null
            : null;
          const singleOfficeStatus = singleOfficeResponse ? String(singleOfficeResponse.status || '').toUpperCase() : 'PENDING';
          const singleOfficeResponseText = singleOfficeResponse && singleOfficeResponse.response ? singleOfficeResponse.response : '';
          const singleOfficeResponseAt = singleOfficeResponse && singleOfficeResponse.response_at ? singleOfficeResponse.response_at : null;
          const singleOfficeDocuments = singleOfficeResponse && Array.isArray(singleOfficeResponse.documents) ? singleOfficeResponse.documents : [];
          const singleOfficeMarkup = isSingleOfficeSubmission ? (() => {
            const officeName = inquiry.recipient_office || 'Municipality / City';
            const officerName = singleOfficeResponse?.full_name || singleOfficeResponse?.username || 'FO User';
            if (singleOfficeStatus === 'PENDING') {
              return `
                <div class="single-office-response-card" style="border:1px solid #ffe0b2; border-radius:8px; background:#fffbf0; overflow:hidden; margin-top:0;">
                  <div style="padding:12px 14px; display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                    <div>
                      <div style="font-weight:700; color:#333; font-size:0.96rem;">${escapeHtml(officeName)}</div>
                      <div style="font-size:0.8rem; color:#666; margin-top:4px;">${escapeHtml(officerName)} | No response submitted yet</div>
                    </div>
                    <span style="display:inline-block; background:#f59e0b; color:#fff; padding:4px 10px; border-radius:6px; font-size:0.7rem; font-weight:700; white-space:nowrap;">PENDING</span>
                  </div>
                </div>
              `;
            }
            return `
              <div class="single-office-response-card" style="border:1px solid #d1fae5; border-radius:8px; background:#f0fdf4; overflow:hidden; margin-top:0;">
                <div style="padding:12px 14px; border-bottom:1px solid #d1fae5;">
                  <div style="font-weight:700; color:#333; font-size:0.96rem;">${escapeHtml(officeName)}</div>
                  <div style="font-size:0.8rem; color:#666; margin-top:4px;">${escapeHtml(officerName)}</div>
                </div>
                <div style="padding:12px 14px; border-bottom:1px solid #d1fae5;">
                  <div style="font-size:0.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; margin-bottom:6px;">Response Date &amp; Time</div>
                  <div style="font-weight:600; color:#1f2937;">${escapeHtml(formatSubmissionDate(singleOfficeResponseAt || inquiry.fo_response_at))}</div>
                </div>
                <div style="padding:12px 14px; border-bottom:1px solid #d1fae5;">
                  <div style="font-size:0.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; margin-bottom:6px;">Action Taken</div>
                  <div style="color:#4b5563; line-height:1.6; white-space:pre-wrap;">${linkifySubmissionDetails(singleOfficeResponseText || inquiry.fo_response || 'No response provided.')}</div>
                </div>
                ${singleOfficeDocuments.length ? `<div style="padding:12px 14px; border-bottom:1px solid #d1fae5;"><div style="font-size:0.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; margin-bottom:8px;">FO Attachments (${singleOfficeDocuments.length})</div>${renderDetailAttachments(singleOfficeDocuments, 'No response documents.', true)}</div>` : ''}
                <div style="padding:12px 14px; display:flex; align-items:center; gap:8px; background:#ecfdf5; border-top:1px solid #d1fae5;">
                  <span style="display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:6px; background:#10b981; color:#fff; font-weight:700; font-size:0.7rem;">?</span>
                  <span style="font-weight:700; color:#10b981; font-size:0.82rem; text-transform:uppercase;">${escapeHtml(singleOfficeStatus)}</span>
                </div>
              </div>
            `;
          })() : '';
          const renderOfficeGroup = (status, label) => {
            const normalizedStatus = String(status || '').toUpperCase();
            const responses = recipientResponses.filter(item => String(item.status || '').toUpperCase() === normalizedStatus);
            const rows = responses.length ? responses.map(item => {
              const documents = item.documents || [];
              const documentLinks = documents.length ? documents.map(file => `<a href="../shared/auth/download_document.php?id=${encodeURIComponent(file.id)}&amp;mode=inline" target="_blank" rel="noopener" onclick="event.stopPropagation()">Preview</a><a href="../shared/auth/download_document.php?id=${encodeURIComponent(file.id)}" target="_blank" rel="noopener" download onclick="event.stopPropagation()">Download</a>`).join('') : '<small>No documents sent.</small>';
              const returnMessage = item.returned_reason || item.return_reason || item.reason || '';
              const responseText = item.response || 'No response text provided.';
              const isOverdue = item.response_at && inquiry.due_at && new Date(item.response_at) > new Date(inquiry.due_at);
              const attachmentsMarkup = documents.length
                ? `<div class="submitted-office-attachments" style="margin-top:12px;">
                    <strong style="display:block; margin-bottom:6px; color:#8a4b0b;">FO Attachments</strong>
                    <div class="submission-detail-files">${documents.map(file => `<div class="submission-detail-file" style="display:flex; align-items:flex-start; gap:8px; padding:8px 0; border-top:1px solid #e8ecef;">
                      <span class="submission-detail-file-icon" style="font-size:1.1rem;">&#128196;</span>
                      <span style="flex:1; min-width:0;">
                        <strong title="${escapeHtml(file.original_name || file.file_name || 'Document')}" style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(file.original_name || file.file_name || 'Document')}</strong>
                        <small style="display:block; color:#607285;">${escapeHtml(file.mime_type || 'File')} | ${escapeHtml(formatSubmissionSize(file.file_size))} | ${escapeHtml(formatSubmissionDate(file.created_at))}</small>
                      </span>
                      <span class="submission-detail-file-actions" style="display:flex; gap:8px; font-size:.8rem; white-space:nowrap;">
                        <a href="../shared/auth/download_document.php?id=${encodeURIComponent(file.id)}&amp;mode=inline" target="_blank" rel="noopener">Preview</a>
                        <a href="../shared/auth/download_document.php?id=${encodeURIComponent(file.id)}" target="_blank" rel="noopener" download>Download</a>
                      </span>
                    </div>`).join('')}</div>
                    <p style="margin:8px 0 0;"><strong>Total:</strong> ${documents.length} file(s)</p>
                  </div>`
                : `<div class="submitted-office-attachments" style="margin-top:12px; color:#5a6672;">No documents sent.</div>`;
              const responseDetails = normalizedStatus === 'PENDING'
                ? '<p>No response submitted yet.</p>'
                : normalizedStatus === 'RETURNED'
                  ? `
                    <div style="margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid #e8ecef;">
                      <small style="color:#667782;"><strong>Submitted Date:</strong> ${escapeHtml(formatSubmissionDate(item.response_at))}${isOverdue ? ' <span style="color:#b42318; font-weight:800;">OVERDUE</span>' : ''}</small>
                    </div>
                    ${responseText ? `
                      <div class="submitted-office-response-box" style="margin-top:12px; padding:0; border:none; border-radius:0; background:transparent;">
                        <strong style="display:block; margin-bottom:6px; color:#8a4b0b;">FO Response</strong>
                        <p style="margin:0; white-space:pre-wrap;">${linkifySubmissionDetails(responseText)}</p>
                      </div>
                    ` : ''}
                    ${attachmentsMarkup}
                    ${returnMessage ? `
                      <div class="submitted-office-return-box" style="margin-top:12px; padding:12px 14px; border:1px solid #d8dfe3; border-radius:8px; background:#fff9f3;">
                        <strong style="display:block; margin-bottom:6px; color:#8a4b0b;">PO Return Reason</strong>
                        <p style="margin:0; white-space:pre-wrap;">${linkifySubmissionDetails(returnMessage)}</p>
                      </div>
                    ` : ''}
                  `
                  : `
                    <p>${linkifySubmissionDetails(responseText)}</p>${attachmentsMarkup}`;
              const displayStatus = isOverdue && (normalizedStatus === 'RETURNED' || normalizedStatus === 'APPROVED') ? normalizedStatus + '_OVERDUE' : normalizedStatus;
              const statusBadgeClass = isOverdue && (normalizedStatus === 'RETURNED' || normalizedStatus === 'APPROVED') ? normalizedStatus.toLowerCase() + '_overdue' : (normalizedStatus === 'PENDING' ? 'pending' : normalizedStatus.toLowerCase());
              const summaryText = normalizedStatus === 'PENDING'
                ? 'No response submitted yet'
                : normalizedStatus === 'RETURNED'
                  ? ''
                  : escapeHtml(formatSubmissionDate(item.response_at));
              return `<div class="submitted-office-response clickable" role="button" tabindex="0" aria-expanded="false" onclick="this.classList.toggle('expanded'); this.setAttribute('aria-expanded', this.classList.contains('expanded'))" onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); this.click(); }"><div class="submitted-office-response-summary"><div><strong>${escapeHtml(item.office_name || 'Municipality / City')}</strong><small>${escapeHtml(item.full_name || item.username || 'FO User')} | ${summaryText}</small></div><span class="submission-detail-badge ${statusBadgeClass}">${escapeHtml(displayStatus)}</span></div><div class="submitted-office-response-details">${responseDetails}</div></div>`;
            }).join('') : '<div class="submitted-office-empty">No municipality/city responses in this category.</div>';
            return `<div class="submitted-office-response-group"><strong>${label} (${responses.length})</strong>${rows}</div>`;
          };
          const submittedOfficeMarkup = !isAllMunicipalities ? '' : activeStatusFilter === 'OVERDUE'
            ? `<div class="submitted-office-responses">${renderOfficeGroup('OVERDUE', 'Overdue Municipality / City Responses')}</div>`
            : activeStatusFilter === 'APPROVED'
              ? `<div class="submitted-office-responses">${renderOfficeGroup('APPROVED', 'Approved Municipality / City Responses')}</div>`
              : activeStatusFilter === 'SUBMITTED'
                ? `<div class="submitted-office-responses">${renderOfficeGroup('SUBMITTED', 'Submitted by Municipality / City')}</div>`
                : activeStatusFilter === 'PENDING'
                  ? `<div class="submitted-office-responses">${renderOfficeGroup('PENDING', 'Pending Municipality / City Responses')}</div>`
                  : activeStatusFilter === 'RETURNED'
                    ? `<div class="submitted-office-responses">${renderOfficeGroup('RETURNED', 'Returned Municipality / City Responses')}</div>`
                    : activeStatusFilter === 'IN_PROGRESS'
                      ? `<div class="submitted-office-responses">${renderOfficeGroup('SUBMITTED', 'Submitted by Municipality / City')}${renderOfficeGroup('OVERDUE', 'Overdue Municipality / City Responses')}</div>`
                      : activeStatusFilter === 'ALL'
                        ? `<div class="submitted-office-responses">${renderOfficeGroup('SUBMITTED', 'Submitted by Municipality / City')}${renderOfficeGroup('PENDING', 'Pending Municipality / City Responses')}${renderOfficeGroup('OVERDUE', 'Overdue Municipality / City Responses')}${renderOfficeGroup('RETURNED', 'Returned Municipality / City Responses')}${renderOfficeGroup('APPROVED', 'Approved Municipality / City Responses')}</div>`
                        : `<div class="submitted-office-responses">${renderOfficeGroup('SUBMITTED', 'Submitted by Municipality / City')}${renderOfficeGroup('APPROVED', 'Approved Municipality / City Responses')}${renderOfficeGroup('OVERDUE', 'Overdue Municipality / City Responses')}</div>`;
          body.innerHTML = `
            <section class="submission-detail-card"><h3><span class="detail-section-heading"><span class="detail-section-icon">&#128196;</span>Submission Overview</span></h3><div class="submission-detail-card-body"><div class="submission-detail-meta">
              <div><strong>Reference Number</strong>${escapeHtml(inquiry.reference_number || `DILG-${inquiry.id}`)}</div><div><strong>Status</strong><span class="submission-detail-badge ${statusBadgeClass}">${escapeHtml(statusLabel)}</span></div>
              <div><strong>Title</strong>${escapeHtml(inquiry.title || 'Untitled')}</div><div><strong>Created</strong>${escapeHtml(formatSubmissionDate(inquiry.created_at))}</div>
              <div><strong>Priority</strong><span class="submission-detail-priority">${escapeHtml(inquiry.priority || 'MEDIUM')}</span></div><div><strong>Due Date &amp; Time</strong>${escapeHtml(dueDate)}</div>
            </div></div></section>
            <section class="submission-detail-card sent-by-po"><h3><span class="detail-section-heading"><span class="detail-section-icon">&#128228;</span>Submission Sent by PO</span></h3><div class="submission-detail-card-body"><div class="detail-message"><strong>Description</strong><br>${linkifySubmissionDetails(inquiry.details || 'No description provided.')}</div><div class="detail-sent-by"><strong>Sent By:</strong> PO User <span class="detail-sent-date"><strong>Sent Date</strong> ${escapeHtml(formatSubmissionDate(sentDate))}</span></div>${poFiles.length ? `<div class="detail-attachment-label">Original PO Attachments</div>${renderDetailAttachments(poFiles)}` : '<div class="detail-attachment-label">Original PO Attachments</div><div class="detail-attachments-empty"><span>&#128196;</span><span>No attachments submitted.<br><strong>Total: 0 file(s)</strong></span></div>'}</div></section>
            <section class="submission-detail-card fo-response"><h3><span class="detail-section-heading"><span class="detail-section-icon">&#128172;</span>FO Response</span></h3><div class="submission-detail-card-body">${isSingleOfficeSubmission ? singleOfficeMarkup : `${inquiry.fo_response ? `<div><strong>Response / Action Taken</strong><div class="detail-message">${linkifySubmissionDetails(inquiry.fo_response)}</div></div>` : ''}${submittedOfficeMarkup}`}</div></section>
            <section class="submission-detail-card"><h3><span class="detail-section-heading"><span class="detail-section-icon">&#128065;</span>For PO Review</span></h3><div class="submission-detail-card-body"><div class="submission-detail-note">${inquiry.fo_response_at ? (status === 'OVERDUE' ? 'The FO response was submitted after the PO due date and is marked overdue.' : 'The FO response is ready for your review.') : 'The system is waiting for the FO User response.'}</div></div></section>
          `;

          modal.classList.add('active');
        })
        .catch(() => showNotification('Unable to load inquiry details.', 'error'));
    }
    
    // Show notification
    function showNotification(message, type = 'success') {
      const notification = document.getElementById('notification');
      notification.textContent = message;
      notification.style.background = type === 'error' ? '#f44336' : '#4caf50';
      notification.classList.add('active');
      
      setTimeout(() => {
        notification.classList.remove('active');
      }, 3000);
    }
    
    // Escape HTML
    function escapeHtml(text) {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text.replace(/[&<>"']/g, m => map[m]);
    }

    function linkifySubmissionDetails(value) {
      return escapeHtml(String(value)).replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>').replace(/\n/g, '<br>');
    }
    
    // Logout
    function logout() {
      fetch('../shared/auth/logout.php', { method: 'POST' })
        .finally(() => window.location.href = '../../index.html');
    }
    
    // Search
    document.getElementById('globalSearch')?.addEventListener('input', () => {
      loadInquiries();
    });

    document.getElementById('inquirySearch')?.addEventListener('input', (event) => {
      const term = event.target.value.trim().toLowerCase();
      renderInquiries(allInquiries.filter((inquiry) => {
        const matchesText = [inquiry.title, inquiry.details, inquiry.category, inquiry.status, inquiry.recipient_office]
          .filter(Boolean)
          .some((value) => String(value).toLowerCase().includes(term));
        return matchesText && inquiryMatchesStatus(inquiry, activeStatusFilter);
      }));
    });

    document.querySelectorAll('[data-status-tab]').forEach((tab) => {
      tab.addEventListener('click', () => {
        applyStatusSelection(tab.dataset.statusTab || 'ALL');
      });
    });

    document.querySelectorAll('.stat-card').forEach((card) => {
      card.addEventListener('click', () => {
        applyStatusSelection(card.dataset.status || 'ALL');
      });
    });

    document.getElementById('filter-all-status')?.addEventListener('change', () => {
      if (document.getElementById('filter-all-status').checked) {
        document.querySelectorAll('.filter-options input[type="checkbox"][data-status]').forEach(cb => cb.checked = false);
        renderInquiries(allInquiries);
      }
    });

    document.querySelectorAll('.filter-options input[type="checkbox"][data-status]').forEach((checkbox) => {
      checkbox.addEventListener('change', () => {
        if (checkbox.checked) document.getElementById('filter-all-status').checked = false;
        applyFilters();
      });
    });
    
    // Close modal on outside click
    document.getElementById('createModal')?.addEventListener('click', (e) => {
      if (e.target.id === 'createModal') closeCreateModal();
    });
    
    // Initialize on load
    document.addEventListener('DOMContentLoaded', function () {
      const searchTerm = new URLSearchParams(window.location.search).get('search') || '';
      const globalSearch = document.getElementById('globalSearch');
      if (globalSearch) globalSearch.value = searchTerm;
      init();
      const params = new URLSearchParams(window.location.search);
      const viewId = params.get('view');
      const createRequested = params.get('create') === '1';
      if (viewId) {
        setTimeout(() => viewInquiry(Number(viewId)), 200);
      }
      if (createRequested) {
        setTimeout(openCreateModal, 200);
      }
    });

    document.getElementById('detailModal')?.addEventListener('click', (e) => {
      if (e.target.id === 'detailModal') {
        e.target.classList.remove('active');
      }
    });

    setInterval(() => {
      if (!document.querySelector('.modal.active')) {
        loadStats();
        loadInquiries();
      }
    }, 30000);
  </script>
</body>
</html>

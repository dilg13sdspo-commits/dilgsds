<?php
require_once __DIR__ . '/../shared/auth/middleware.php';
require_once __DIR__ . '/../shared/auth/po_icons.php';
require_role('PO USER');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Documents - DILG PO Dashboard</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; }
    .container { max-width: 1000px; margin: 0 auto; padding: 2rem; }
    h1 { margin-bottom: 1.5rem; color: #333; }
    .doc-card { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
    .doc-info h3 { margin-bottom: 0.5rem; }
    .doc-info p { color: #666; font-size: 0.9rem; }
    .doc-actions { display: flex; gap: 1rem; }
    .btn { padding: 0.5rem 1rem; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn:hover { background: #f5f5f5; }
    .back-link { color: #2d7ff9; text-decoration: none; margin-bottom: 1rem; display: inline-block; }
  </style>
</head>
<body>
  <div class="container">
    <a href="dashboard.php" class="back-link">Back to Dashboard</a>
    <h1><?= po_icon('document') ?> Documents</h1>
    
    <div class="doc-card">
      <div class="doc-info">
        <h3>Citizen's Charter</h3>
        <p>Standard government procedures and guidelines</p>
      </div>
      <div class="doc-actions">
        <a href="../../citizen-charter.html" class="btn">View</a>
      </div>
    </div>
    
    <div class="doc-card">
      <div class="doc-info">
        <h3>Issuances & Memoranda</h3>
        <p>Official memoranda and circulars</p>
      </div>
      <div class="doc-actions">
        <a href="../../issuances.html" class="btn">View</a>
      </div>
    </div>
    
    <div class="doc-card">
      <div class="doc-info">
        <h3>Programs & Services</h3>
        <p>Available programs and services</p>
      </div>
      <div class="doc-actions">
        <a href="../../programs-and-services.html" class="btn">View</a>
      </div>
    </div>
    
    <div class="doc-card">
      <div class="doc-info">
        <h3>Annual Reports</h3>
        <p>Yearly performance reports</p>
      </div>
      <div class="doc-actions">
        <a href="../../annual-report.html" class="btn">View</a>
      </div>
    </div>
  </div>
</body>
</html>

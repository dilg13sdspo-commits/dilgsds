<?php
require_once __DIR__ . '/../shared/auth/middleware.php';
require_role('PO USER');

$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $details = trim($_POST['details'] ?? '');
    
    if ($title && $details) {
        $stmt = $pdo->prepare('INSERT INTO reports (user_id, title, details, status) VALUES (:uid, :title, :details, :status)');
        $stmt->execute([':uid' => $userId, ':title' => $title, ':details' => $details, ':status' => 'PENDING']);
        header('Location: po-inquiries.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create Inquiry - DILG PO Dashboard</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; }
    .container { max-width: 600px; margin: 0 auto; padding: 2rem; }
    h1 { margin-bottom: 1.5rem; color: #333; }
    .form-group { margin-bottom: 1.5rem; }
    label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; }
    input, textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; }
    textarea { resize: vertical; min-height: 150px; }
    input:focus, textarea:focus { outline: none; border-color: #2d7ff9; box-shadow: 0 0 0 3px rgba(45, 127, 249, 0.1); }
    .button-group { display: flex; gap: 1rem; }
    button { padding: 0.75rem 1.5rem; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; }
    .btn-submit { background: #2d7ff9; color: white; }
    .btn-submit:hover { background: #1e5ed4; }
    .btn-cancel { background: #e5e7eb; color: #333; text-decoration: none; display: inline-flex; align-items: center; }
    .btn-cancel:hover { background: #d1d5db; }
    .back-link { color: #2d7ff9; text-decoration: none; margin-bottom: 1rem; display: inline-block; }
  </style>
</head>
<body>
  <div class="container">
    <a href="dashboard.php" class="back-link">Back to Dashboard</a>
    <h1>Create New Inquiry</h1>
    
    <form method="POST">
      <div class="form-group">
        <label for="title">Inquiry Title *</label>
        <input type="text" id="title" name="title" required placeholder="Enter inquiry title">
      </div>
      
      <div class="form-group">
        <label for="details">Details *</label>
        <textarea id="details" name="details" required placeholder="Provide detailed information about your inquiry"></textarea>
      </div>
      
      <div class="button-group">
        <button type="submit" class="btn-submit">Submit Inquiry</button>
        <a href="dashboard.php" class="btn-cancel">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>

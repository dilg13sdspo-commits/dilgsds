<?php
require_once __DIR__ . '/../shared/auth/db.php';
require_once __DIR__ . '/../shared/auth/middleware.php';
require_role('FO USER');

$pdo = db_connect();
$userId = (int)($_SESSION['user_id'] ?? 0);
$profilePhoto = get_user_profile_photo($pdo, $userId);
$userName = $_SESSION['username'] ?? 'FO User';
$officeName = $_SESSION['office_name'] ?? 'Field Office';

function ensure_notifications_table(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        office_id INT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        category VARCHAR(50) NOT NULL DEFAULT 'System',
        type VARCHAR(50) NOT NULL DEFAULT 'system',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        related_type VARCHAR(50) NULL,
        related_id INT NULL,
        action_url VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at DATETIME NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_notifications (user_id, is_read, created_at),
        INDEX idx_user_category (user_id, category, created_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// Ensure FAQ tables exist
function ensure_faq_tables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS faq_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        display_name VARCHAR(100) NOT NULL,
        icon VARCHAR(50) NULL,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS faqs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        question VARCHAR(500) NOT NULL,
        answer TEXT NOT NULL,
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES faq_categories(id) ON DELETE CASCADE,
        INDEX idx_category (category_id),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS resources (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT NULL,
        file_type VARCHAR(50) NOT NULL,
        file_size VARCHAR(50) NOT NULL,
        icon VARCHAR(100) NULL,
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// Seed FAQ data
function seed_faq_data(PDO $pdo): void {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM faq_categories');
    $stmt->execute();
    if ((int)$stmt->fetchColumn() > 0) return;

    $categories = [
        ['inquiries', 'Inquiries', '', 'Questions about submitting and managing inquiries'],
        ['documents', 'Documents', '', 'Document handling and requirements'],
        ['account', 'Account', '', 'Account settings and management'],
        ['technical', 'Technical', '', 'Technical issues and troubleshooting']
    ];

    $catInsert = $pdo->prepare('INSERT INTO faq_categories (name, display_name, icon, description) VALUES (:name, :display_name, :icon, :description)');
    $catIds = [];
    foreach ($categories as [$name, $display, $icon, $desc]) {
        $catInsert->execute([':name' => $name, ':display_name' => $display, ':icon' => $icon, ':description' => $desc]);
        $catIds[$name] = $pdo->lastInsertId();
    }

    $faqs = [
        ['inquiries', 'How do I create a new inquiry?', 'Use the quick action button labeled "Create Inquiry" from the dashboard or navigate to "My Inquiries" section. Fill in the required details and submit your request. You will receive a reference number for tracking.'],
        ['inquiries', 'Where can I see my inquiry status?', 'Open the "My Inquiries" section in the dashboard, which shows the latest status, reference number, and last update of each request. Click on any inquiry to view complete details.'],
        ['inquiries', 'What types of inquiries can I submit?', 'You can submit various types of inquiries including program information requests, service applications, compliance queries, and office coordination matters. Refer to the guidelines for specific requirements.'],
        ['inquiries', 'How long does it take to process my inquiry?', 'Processing times vary based on inquiry type and complexity. Typical inquiries are processed within 5-7 business days. You will receive notifications on any status updates.'],
        ['documents', 'How do I upload supporting documents?', 'Use the "Upload Document" option in your inquiry details or from the dashboard to send supporting files directly to the system. Accepted formats include PDF, DOC, DOCX, XLS, and XLSX.'],
        ['documents', 'What are the document size and format requirements?', 'Each document must not exceed 10 MB in size. Acceptable file formats are PDF, Microsoft Word (DOC/DOCX), and Microsoft Excel (XLS/XLSX). Ensure documents are clear and legible.'],
        ['documents', 'Where do I find document templates?', 'Document templates are available in the "Programs & Services" section and in the "Guidelines & Resources" panel. Download the "Document Requirements" guide for detailed specifications.'],
        ['documents', 'Can I upload multiple documents for one inquiry?', 'Yes, you can upload multiple documents. Simply attach each file sequentially. Ensure all related documents are clearly labeled and organized.'],
        ['documents', 'How do I track the status of my uploaded documents?', 'Visit your inquiry details to see the document review status. You will receive notifications when documents are reviewed or if additional documentation is needed.'],
        ['account', 'How do I update my account information?', 'Visit "Profile Settings" from the sidebar menu. Update your contact information, office details, and preferences. Changes are saved immediately.'],
        ['account', 'Can I change my password?', 'Yes, go to "Profile Settings" and select "Change Password". Enter your current password and new password twice for confirmation.'],
        ['account', 'What should I do if I forgot my password?', 'Click "Forgot Password" on the login page. Follow the instructions to verify your identity and reset your password via email.'],
        ['technical', 'The page is loading slowly. What can I do?', 'Clear your browser cache and cookies, then refresh the page. Ensure you have a stable internet connection. Try using a different browser if the issue persists.'],
        ['technical', 'I cannot upload files. What should I do?', 'Ensure your file size does not exceed 10 MB and is in an acceptable format. Try a different browser or clear your browser cache. Contact support if the issue continues.'],
    ];

    $faqInsert = $pdo->prepare('INSERT INTO faqs (category_id, question, answer, sort_order) VALUES (:cat_id, :question, :answer, :sort)');
    $sort = 0;
    foreach ($faqs as [$catName, $q, $a]) {
        $faqInsert->execute([
            ':cat_id' => $catIds[$catName],
            ':question' => $q,
            ':answer' => $a,
            ':sort' => $sort++
        ]);
    }
}

// Seed resources
function seed_resources(PDO $pdo): void {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM resources');
    $stmt->execute();
    if ((int)$stmt->fetchColumn() > 0) return;

    $resources = [
        ['FO User Guide', 'Comprehensive guide for Field Office Users', 'PDF', '2.4 MB', '', 1],
        ['Inquiry Process Flow', 'Step-by-step inquiry submission process', 'PDF', '1.8 MB', '', 2],
        ['Document Requirements', 'List of required documents for various requests', 'PDF', '1.2 MB', '', 3],
        ['Data Privacy Policy', 'Information on data privacy and protection', 'PDF', '850 KB', '', 4],
        ['System User Manual', 'Detailed system features and functionalities', 'PDF', '3.1 MB', '', 5],
        ['Office Directory', 'Contact information for DILG offices', 'XLSX', '980 KB', '', 6],
    ];

    $resInsert = $pdo->prepare('INSERT INTO resources (name, description, file_type, file_size, icon, sort_order) VALUES (:name, :desc, :type, :size, :icon, :sort)');
    foreach ($resources as [$name, $desc, $type, $size, $icon, $sort]) {
        $resInsert->execute([
            ':name' => $name,
            ':desc' => $desc,
            ':type' => $type,
            ':size' => $size,
            ':icon' => $icon,
            ':sort' => $sort
        ]);
    }
}

ensure_faq_tables($pdo);
ensure_notifications_table($pdo);
seed_faq_data($pdo);
seed_resources($pdo);

// Get FAQ categories
$stmt = $pdo->prepare('SELECT * FROM faq_categories ORDER BY id ASC');
$stmt->execute();
$categories = $stmt->fetchAll();

// Get unread notification/message counts (both use notifications table)
$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
$stmt->execute([':uid' => $userId]);
$unreadNotifications = (int)$stmt->fetchColumn();
$unreadMessages = $unreadNotifications;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs & Guidelines - DILG Portal FO Dashboard</title>
    <style>
        :root {
            --fo-green-900: #0b3f34;
            --fo-green-800: #0f4d3d;
            --fo-green-700: #137a5f;
            --fo-bg: #f0f4f3;
            --fo-panel: #ffffff;
            --fo-text: #1a282c;
            --fo-text-light: #64747f;
            --fo-border: #d8dfe5;
            --fo-shadow: 0 2px 8px rgba(11, 63, 52, 0.08);
            --fo-shadow-lg: 0 8px 24px rgba(11, 63, 52, 0.12);
            --fo-red: #e53935;
            --fo-yellow: #fbc02d;
            --fo-blue: #1976d2;
            --fo-green: #43a047;
            --fo-accent-blue: #2196f3;
        }
        
        * { box-sizing: border-box; }
        
        html, body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--fo-bg);
            color: var(--fo-text);
        }
        
        a { text-decoration: none; color: inherit; }
        button { cursor: pointer; border: none; background: none; font-family: inherit; }
        
        .layout {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #0b3d91, #1456a0);
            color: white;
            padding: 20px 14px;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 8px 18px;
            margin-bottom: 8px;
        }
        
        .brand-icon {
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .brand-text strong {
            display: block;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .brand-text span {
            display: block;
            font-size: 0.7rem;
            opacity: 0.85;
            margin-top: 2px;
            line-height: 1.2;
        }
        
        .nav-items {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 16px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 11px 10px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: rgba(255, 255, 255, 0.9);
            text-align: left;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }
        
        .nav-item-label {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .nav-badge {
            min-width: 24px;
            height: 24px;
            background: var(--fo-yellow);
            color: #1a282c;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
            flex-shrink: 0;
        }
        
        .main-container {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .topbar {
            background: var(--fo-panel);
            border-bottom: 1px solid var(--fo-border);
            padding: 16px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--fo-shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .topbar-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--fo-text);
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .search-bar {
            flex: 1;
            max-width: 300px;
            padding: 8px 14px;
            border: 1px solid var(--fo-border);
            border-radius: 8px;
            font-size: 0.9rem;
            background: #f8f9fa;
        }
        
        .search-bar:focus {
            outline: none;
            border-color: var(--fo-green-700);
            background: white;
        }
        
        .icon-button {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: transparent;
            border: 1px solid var(--fo-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .icon-button:hover {
            background: #f8f9fa;
            border-color: var(--fo-green-700);
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--fo-red);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
        }
        
        .profile-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .profile-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--fo-green-700), var(--fo-green-900));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
        }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }
        
        .profile-info {
            text-align: right;
        }
        
        .profile-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--fo-text);
        }
        
        .profile-role {
            font-size: 0.75rem;
            color: var(--fo-text-light);
        }
        
        .content {
            flex: 1;
            padding: 28px;
            overflow-y: auto;
        }
        
        .page-header {
            background: var(--fo-panel);
            border: 1px solid var(--fo-border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--fo-shadow);
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }
        
        .page-header-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--fo-accent-blue), var(--fo-green-700));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            flex-shrink: 0;
        }
        
        .page-header-content h1 {
            margin: 0 0 8px;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--fo-text);
        }
        
        .page-header-content p {
            margin: 0;
            font-size: 0.95rem;
            color: var(--fo-text-light);
            line-height: 1.5;
        }
        
        .faq-container {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 24px;
        }
        
        .faq-main {
            background: var(--fo-panel);
            border: 1px solid var(--fo-border);
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--fo-shadow);
        }
        
        .faq-controls {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            align-items: center;
        }
        
        .search-input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid var(--fo-border);
            border-radius: 8px;
            font-size: 0.9rem;
            background: #f8f9fa;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--fo-green-700);
            background: white;
        }
        
        .category-dropdown {
            padding: 10px 14px;
            border: 1px solid var(--fo-border);
            border-radius: 8px;
            font-size: 0.9rem;
            background: white;
            min-width: 180px;
        }
        
        .category-dropdown:focus {
            outline: none;
            border-color: var(--fo-green-700);
        }
        
        .tab-group {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            border-bottom: 1px solid var(--fo-border);
            padding-bottom: 16px;
        }
        
        .tab {
            padding: 8px 16px;
            border: none;
            background: transparent;
            color: var(--fo-text-light);
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
            position: relative;
            white-space: nowrap;
        }
        
        .tab:hover {
            color: var(--fo-text);
        }
        
        .tab.active {
            color: var(--fo-green-700);
            border-bottom-color: var(--fo-green-700);
        }
        
        .tab-count {
            background: #e0e0e0;
            color: #666;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 6px;
        }
        
        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .faq-item {
            border: 1px solid var(--fo-border);
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
            transition: all 0.2s ease;
        }
        
        .faq-item:hover {
            box-shadow: var(--fo-shadow);
            border-color: var(--fo-green-700);
        }
        
        .faq-item.open {
            background: white;
            box-shadow: var(--fo-shadow);
        }
        
        .faq-question {
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            user-select: none;
        }
        
        .faq-question-text {
            flex: 1;
            font-weight: 500;
            color: var(--fo-text);
            font-size: 0.95rem;
        }
        
        .faq-toggle {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }
        
        .faq-item.open .faq-toggle {
            transform: rotate(180deg);
        }
        
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
            background: white;
        }
        
        .faq-item.open .faq-answer {
            max-height: 500px;
        }
        
        .faq-answer-text {
            padding: 16px;
            line-height: 1.6;
            color: var(--fo-text-light);
            font-size: 0.9rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--fo-text-light);
        }
        
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 12px;
        }
        
        .resources-panel {
            background: var(--fo-panel);
            border: 1px solid var(--fo-border);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--fo-shadow);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .resources-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .resources-header h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--fo-text);
        }
        
        .resources-header a {
            font-size: 0.85rem;
            color: var(--fo-accent-blue);
            font-weight: 500;
        }
        
        .resources-header a:hover {
            text-decoration: underline;
        }
        
        .resource-item {
            background: #f8f9fa;
            border: 1px solid var(--fo-border);
            border-radius: 8px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .resource-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--fo-green-700), var(--fo-green-900));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
            color: white;
        }
        
        .resource-info {
            flex: 1;
            min-width: 0;
        }
        
        .resource-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--fo-text);
            margin: 0 0 4px;
        }
        
        .resource-details {
            display: flex;
            gap: 8px;
            align-items: center;
            font-size: 0.8rem;
            color: var(--fo-text-light);
        }
        
        .resource-type {
            background: #e0e0e0;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .resource-download {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            background: transparent;
            border: 1px solid var(--fo-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        
        .resource-download:hover {
            background: white;
            border-color: var(--fo-green-700);
            color: var(--fo-green-700);
        }
        
        .support-section {
            background: linear-gradient(135deg, var(--fo-accent-blue), var(--fo-green-700));
            border-radius: 8px;
            padding: 16px;
            color: white;
            text-align: center;
            margin-top: 16px;
        }
        
        .support-section h4 {
            margin: 0 0 8px;
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        .support-section p {
            margin: 0 0 12px;
            font-size: 0.85rem;
            opacity: 0.95;
        }
        
        .support-btn {
            background: white;
            color: var(--fo-accent-blue);
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .support-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .faq-container {
                grid-template-columns: 1fr;
            }
            
            .resources-panel {
                grid-column: 1;
                order: 2;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                width: 260px;
                height: 100vh;
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-container {
                margin-left: 0;
            }
            
            .topbar {
                padding: 12px 16px;
            }
            
            .topbar-right {
                gap: 8px;
            }
            
            .search-bar {
                max-width: 120px;
                padding: 6px 10px;
            }
            
            .content {
                padding: 16px;
            }
            
            .page-header {
                padding: 16px;
                gap: 12px;
            }
            
            .page-header-icon {
                width: 44px;
                height: 44px;
                font-size: 1.4rem;
            }
            
            .page-header-content h1 {
                font-size: 1.3rem;
            }
            
            .faq-main {
                padding: 16px;
            }
            
            .faq-controls {
                flex-direction: column;
                gap: 8px;
            }
            
            .search-input, .category-dropdown {
                width: 100%;
            }
            
            .tab-group {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .profile-info {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .topbar {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
            
            .page-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .page-header-icon {
                width: 48px;
                height: 48px;
            }
            
            .page-header-content h1 {
                font-size: 1.2rem;
            }
            
            .faq-question-text {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-icon">D</div>
                <div class="brand-text">
                    <strong>DILG PORTAL</strong>
                    <span>Department of the Interior and Local Governance</span>
                </div>
            </div>
            
            <nav class="nav-items">
                <button class="nav-item" onclick="window.location.href='dashboard.php'">
                    <span class="nav-item-label">?? Dashboard</span>
                </button>
                <button class="nav-item" onclick="window.location.href='dashboard.php'">
                    <span class="nav-item-label">?? My Inquiries</span>
                </button>
                <button class="nav-item" onclick="window.location.href='../../contact-us.html'">
                    <span class="nav-item-label">?? Office Transactions</span>
                </button>
                <button class="nav-item" onclick="window.location.href='../../programs-and-services.html'">
                    <span class="nav-item-label">?? Programs & Services</span>
                </button>
                <button class="nav-item" onclick="window.location.href='../../news.html'">
                    <span class="nav-item-label">?? Announcements</span>
                </button>
                <button class="nav-item" onclick="window.location.href='dashboard.php'">
                    <span class="nav-item-label">?? Calendar & Activities</span>
                </button>
                <button class="nav-item" onclick="window.location.href='../../programs-and-services.html'">
                    <span class="nav-item-label">?? Downloads</span>
                </button>
                <button class="nav-item" onclick="window.location.href='messages.php'">
                    <span class="nav-item-label">?? Messages</span>
                    <?php if($unreadMessages > 0): ?>
                        <span class="nav-badge"><?= htmlspecialchars((string)$unreadMessages) ?></span>
                    <?php endif; ?>
                </button>
                <button class="nav-item" onclick="window.location.href='notifications.php'">
                    <span class="nav-item-label">?? Notifications</span>
                    <?php if($unreadNotifications > 0): ?>
                        <span class="nav-badge"><?= htmlspecialchars((string)$unreadNotifications) ?></span>
                    <?php endif; ?>
                </button>
                <button class="nav-item active" onclick="window.location.href='faq-guidelines.php'">
                    <span class="nav-item-label">?? FAQs & Guidelines</span>
                </button>
                <button class="nav-item" onclick="window.location.href='settings.php'">
                    <span class="nav-item-label">?? Profile Settings</span>
                </button>
            </nav>
        </aside>
        
        <div class="main-container">
            <!-- Top Bar -->
            <div class="topbar">
                <div class="topbar-title">FO User Dashboard</div>
                <div class="topbar-right">
                    <input type="text" class="search-bar" placeholder="Search inquiries, documents...">
                    
                    <button class="icon-button">
                        ??
                        <?php if($unreadNotifications > 0): ?>
                            <span class="notification-badge"><?= $unreadNotifications ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <div class="profile-section">
                        <div class="profile-avatar"><?php if ($profilePhoto): ?><img src="<?= htmlspecialchars($profilePhoto) ?>" alt="FO User profile photo"><?php else: ?><?= htmlspecialchars(substr($userName, 0, 1)) ?><?php endif; ?></div>
                        <div class="profile-info">
                            <div class="profile-name"><?= htmlspecialchars($userName) ?></div>
                            <div class="profile-role"><?= htmlspecialchars($officeName) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="page-header-icon">?</div>
                    <div class="page-header-content">
                        <h1>FAQs & Guidelines</h1>
                        <p>Find answers to common questions and access important guidelines and resources.</p>
                    </div>
                </div>
                
                <!-- FAQ Container -->
                <div class="faq-container">
                    <!-- Main FAQ Section -->
                    <div class="faq-main">
                        <div class="faq-controls">
                            <input type="text" class="search-input" id="searchInput" placeholder="Search FAQs...">
                            <select class="category-dropdown" id="categoryFilter">
                                <option value="">All Categories</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['name']) ?>">
                                        <?= htmlspecialchars($cat['display_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- FAQ Tabs -->
                        <div class="tab-group" id="tabGroup">
                            <button class="tab active" data-category="">
                                All <span class="tab-count" id="count-all">0</span>
                            </button>
                            <?php foreach($categories as $cat): ?>
                                <button class="tab" data-category="<?= htmlspecialchars($cat['name']) ?>">
                                    <?= htmlspecialchars($cat['display_name']) ?> <span class="tab-count" id="count-<?= htmlspecialchars($cat['name']) ?>">0</span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- FAQ List -->
                        <div class="faq-list" id="faqList">
                            <div class="empty-state">
                                <div class="empty-state-icon"></div>
                                <p>Loading FAQs...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resources Panel -->
                    <div class="resources-panel">
                        <div class="resources-header">
                            <h3>Guidelines & Resources</h3>
                            <a href="#view-all-resources">View All</a>
                        </div>
                        
                        <div id="resourcesList">
                            <div class="empty-state" style="padding: 20px;">
                                <div class="empty-state-icon"></div>
                                <p>Loading resources...</p>
                            </div>
                        </div>
                        
                        <!-- Support Section -->
                        <div class="support-section">
                            <h4>Still need help?</h4>
                            <p>Can't find what you're looking for? Our support team is here to help.</p>
                            <button class="support-btn" onclick="openContactSupport()">?? Contact Support</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let allFaqs = [];
        let allResources = [];
        let currentCategory = '';
        let currentSearchTerm = '';
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadFaqs();
            loadResources();
            attachEventListeners();
        });
        
        function attachEventListeners() {
            // Search input
            document.getElementById('searchInput').addEventListener('input', (e) => {
                currentSearchTerm = e.target.value.toLowerCase();
                filterAndDisplayFaqs();
            });
            
            // Category filter dropdown
            document.getElementById('categoryFilter').addEventListener('change', (e) => {
                currentCategory = e.target.value;
                document.querySelectorAll('.tab').forEach(tab => {
                    if(tab.dataset.category === currentCategory) {
                        tab.classList.add('active');
                    } else {
                        tab.classList.remove('active');
                    }
                });
                filterAndDisplayFaqs();
            });
            
            // Tab clicks
            document.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    currentCategory = tab.dataset.category;
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    document.getElementById('categoryFilter').value = currentCategory;
                    filterAndDisplayFaqs();
                });
            });
        }
        
        function loadFaqs() {
            fetch('../shared/auth/faqs_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=list'
            })
            .then(r => r.json())
            .then(data => {
                allFaqs = data.faqs || [];
                updateTabCounts();
                filterAndDisplayFaqs();
            })
            .catch(err => {
                console.error('Error loading FAQs:', err);
                document.getElementById('faqList').innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>Error loading FAQs. Please refresh the page.</p></div>';
            });
        }
        
        function loadResources() {
            fetch('../shared/auth/faqs_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=resources'
            })
            .then(r => r.json())
            .then(data => {
                allResources = data.resources || [];
                displayResources();
            })
            .catch(err => {
                console.error('Error loading resources:', err);
                document.getElementById('resourcesList').innerHTML = '<div class="empty-state" style="padding: 20px;"><div class="empty-state-icon"></div><p>Error loading resources.</p></div>';
            });
        }
        
        function updateTabCounts() {
            const counts = {};
            counts['all'] = allFaqs.length;
            
            allFaqs.forEach(faq => {
                if(!counts[faq.category_name]) counts[faq.category_name] = 0;
                counts[faq.category_name]++;
            });
            
            document.querySelectorAll('.tab-count').forEach(el => {
                const id = el.id.replace('count-', '');
                el.textContent = counts[id] || 0;
            });
        }
        
        function filterAndDisplayFaqs() {
            let filtered = allFaqs;
            
            if(currentCategory) {
                filtered = filtered.filter(faq => faq.category_name === currentCategory);
            }
            
            if(currentSearchTerm) {
                filtered = filtered.filter(faq => 
                    faq.question.toLowerCase().includes(currentSearchTerm) ||
                    faq.answer.toLowerCase().includes(currentSearchTerm)
                );
            }
            
            displayFaqs(filtered);
        }
        
        function displayFaqs(faqs) {
            const list = document.getElementById('faqList');
            
            if(!faqs || faqs.length === 0) {
                list.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No FAQs found. Try a different search or category.</p></div>';
                return;
            }
            
            list.innerHTML = faqs.map(faq => `
                <div class="faq-item" data-id="${faq.id}">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <div class="faq-question-text">${escapeHtml(faq.question)}</div>
                        <div class="faq-toggle">?</div>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-text">${escapeHtml(faq.answer)}</div>
                    </div>
                </div>
            `).join('');
        }
        
        function toggleFaq(questionEl) {
            const item = questionEl.closest('.faq-item');
            item.classList.toggle('open');
        }
        
        function displayResources() {
            const list = document.getElementById('resourcesList');
            
            if(!allResources || allResources.length === 0) {
                list.innerHTML = '<div class="empty-state" style="padding: 20px;"><div class="empty-state-icon"></div><p>No resources available.</p></div>';
                return;
            }
            
            list.innerHTML = allResources.map(res => `
                <div class="resource-item">
                    <div class="resource-icon">${escapeHtml(res.icon || '')}</div>
                    <div class="resource-info">
                        <p class="resource-name">${escapeHtml(res.name)}</p>
                        <div class="resource-details">
                            <span class="resource-type">${escapeHtml(res.file_type)}</span>
                            <span>${escapeHtml(res.file_size)}</span>
                        </div>
                    </div>
                    <button class="resource-download" title="Download ${escapeHtml(res.name)}" onclick="downloadResource(${res.id}, '${escapeHtml(res.name)}')"></button>
                </div>
            `).join('');
        }
        
        function downloadResource(id, name) {
            alert(`Download initiated for: ${name}\n\nNote: In a production environment, this would trigger an actual file download. The resource metadata is stored in the database for future integration with a file storage system.`);
        }
        
        function openContactSupport() {
            window.location.href = '../../contact-us.html';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
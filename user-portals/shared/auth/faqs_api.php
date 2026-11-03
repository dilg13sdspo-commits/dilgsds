<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/middleware.php';

require_role('FO USER');

$pdo = db_connect();
$action = $_POST['action'] ?? 'list';

header('Content-Type: application/json');

function ensure_faq_tables(PDO $pdo): void {
    try {
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
    } catch (Exception $e) {
        // Tables might already exist
    }
}

function seed_faq_data(PDO $pdo): void {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM faq_categories');
    $stmt->execute();
    if ((int)$stmt->fetchColumn() > 0) return;

    $categories = [
        ['inquiries', 'Inquiries', '📝', 'Questions about submitting and managing inquiries'],
        ['documents', 'Documents', '📄', 'Document handling and requirements'],
        ['account', 'Account', '👤', 'Account settings and management'],
        ['technical', 'Technical', '⚙️', 'Technical issues and troubleshooting']
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

function seed_resources(PDO $pdo): void {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM resources');
    $stmt->execute();
    if ((int)$stmt->fetchColumn() > 0) return;

    $resources = [
        ['FO User Guide', 'Comprehensive guide for Field Office Users', 'PDF', '2.4 MB', '📘', 1],
        ['Inquiry Process Flow', 'Step-by-step inquiry submission process', 'PDF', '1.8 MB', '📊', 2],
        ['Document Requirements', 'List of required documents for various requests', 'PDF', '1.2 MB', '📋', 3],
        ['Data Privacy Policy', 'Information on data privacy and protection', 'PDF', '850 KB', '🔒', 4],
        ['System User Manual', 'Detailed system features and functionalities', 'PDF', '3.1 MB', '📚', 5],
        ['Office Directory', 'Contact information for DILG offices', 'XLSX', '980 KB', '📞', 6],
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
seed_faq_data($pdo);
seed_resources($pdo);

switch ($action) {
    case 'list':
        $stmt = $pdo->prepare('
            SELECT f.id, f.question, f.answer, fc.name as category_name, fc.display_name as category_display
            FROM faqs f
            JOIN faq_categories fc ON f.category_id = fc.id
            WHERE f.is_active = 1
            ORDER BY fc.id, f.sort_order
        ');
        $stmt->execute();
        $faqs = $stmt->fetchAll();
        echo json_encode(['success' => true, 'faqs' => $faqs]);
        break;

    case 'resources':
        $stmt = $pdo->prepare('
            SELECT id, name, description, file_type, file_size, icon
            FROM resources
            WHERE is_active = 1
            ORDER BY sort_order
        ');
        $stmt->execute();
        $resources = $stmt->fetchAll();
        echo json_encode(['success' => true, 'resources' => $resources]);
        break;

    case 'detail':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid FAQ ID']);
            exit;
        }
        $stmt = $pdo->prepare('SELECT * FROM faqs WHERE id = :id AND is_active = 1');
        $stmt->execute([':id' => $id]);
        $faq = $stmt->fetch();
        echo json_encode(['success' => $faq !== false, 'faq' => $faq]);
        break;

    case 'search':
        $query = '%' . ($_POST['q'] ?? '') . '%';
        $stmt = $pdo->prepare('
            SELECT f.id, f.question, f.answer, fc.name as category_name, fc.display_name as category_display
            FROM faqs f
            JOIN faq_categories fc ON f.category_id = fc.id
            WHERE f.is_active = 1 AND (f.question LIKE :query OR f.answer LIKE :query)
            ORDER BY fc.id, f.sort_order
        ');
        $stmt->execute([':query' => $query]);
        $faqs = $stmt->fetchAll();
        echo json_encode(['success' => true, 'faqs' => $faqs]);
        break;

    case 'stats':
        $stmt = $pdo->prepare('
            SELECT fc.name, fc.display_name, COUNT(f.id) as count
            FROM faq_categories fc
            LEFT JOIN faqs f ON fc.id = f.category_id AND f.is_active = 1
            GROUP BY fc.id
            ORDER BY fc.id
        ');
        $stmt->execute();
        $stats = $stmt->fetchAll();
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}

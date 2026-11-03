<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/middleware.php';
require_role('PO USER');

header('Content-Type: application/json; charset=utf-8');

$pdo = db_connect();

function ensure_help_articles_table(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS help_articles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        summary TEXT,
        content LONGTEXT NOT NULL,
        category VARCHAR(50) NOT NULL DEFAULT 'General',
        article_type VARCHAR(30) NOT NULL DEFAULT 'article',
        keywords TEXT,
        status VARCHAR(20) NOT NULL DEFAULT 'published',
        author VARCHAR(120) DEFAULT 'System',
        featured TINYINT(1) NOT NULL DEFAULT 0,
        view_count INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function seed_help_articles(PDO $pdo): void {
    $count = (int)$pdo->query('SELECT COUNT(*) FROM help_articles')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $rows = [
        [
            'How do I create a new inquiry?',
            'Learn how to submit a new inquiry from the Provincial Office Dashboard.',
            'To create a new inquiry, go to My Inquiries from the left-side menu. Click the Create Inquiry button, complete the required title and details, attach any supporting documents if needed, review the information, and submit. Once submitted, the inquiry will appear in your list and can be tracked through its current status.',
            'Inquiries',
            'faq',
            'inquiry, create, submit, dashboard',
            'published',
            'DILG Support',
            1,
            0,
            '2026-08-17 08:00:00'
        ],
        [
            'How can I track the status of my inquiries?',
            'Understand how to monitor inquiry progress using the My Inquiries module.',
            'Open My Inquiries from the dashboard sidebar. Every inquiry shows its current state, including Submitted, Pending, In Progress, Approved, Returned, or Completed. You can review updates, read internal notes, and check the last update date from the list. This allows you to monitor or follow up on pending actions without leaving the dashboard.',
            'Inquiries',
            'faq',
            'status, inquiry, progress, monitor',
            'published',
            'DILG Support',
            1,
            0,
            '2026-08-17 08:05:00'
        ],
        [
            'What documents are available?',
            'Find downloadable forms, issuances, and provincial guidance documents.',
            'The Documents and Programs & Services sections contain available forms, issuances, Citizen’s Charter items, and government guidance materials. Use the Documents module to view or download any relevant file required for your office processes. If a specific document is required for a submitted inquiry, the system will usually point you to the relevant page or form.',
            'Documents',
            'faq',
            'documents, forms, issuances, download',
            'published',
            'DILG Support',
            1,
            0,
            '2026-08-17 08:10:00'
        ],
        [
            'How do I get support?',
            'Find the right support contact or route a concern to the correct office.',
            'You can contact the Provincial Office through the official contact details listed in the Help Center or by using the Contact Support action in the support panel. For service-related issues, you may also submit an inquiry through My Inquiries so the relevant office can review and respond to your request.',
            'Account',
            'faq',
            'support, contact, assistance, help',
            'published',
            'DILG Support',
            1,
            0,
            '2026-08-17 08:15:00'
        ],
        [
            'How do I update my profile settings?',
            'Manage your account information and profile preferences in the dashboard.',
            'Open Settings from the sidebar to update your profile and account preferences. You can review your assigned office information, change contact preferences, and manage notification settings. If a setting is restricted or requires administrative approval, the system will guide you with the next steps.',
            'Account',
            'faq',
            'account, profile, settings, update',
            'published',
            'DILG Support',
            1,
            0,
            '2026-08-17 08:20:00'
        ],
        [
            'How to submit documents',
            'Step-by-step guide for attaching and submitting documents in the system.',
            'To submit documents, open the Documents module or use the relevant inquiry details page. Upload the required files, verify the preferred file format and category, and complete the required metadata. Once the upload is complete, the system will associate the document with your inquiry or office record. If a document is incomplete or a required item is missing, the system will notify you to correct it.',
            'Documents',
            'article',
            'documents, upload, submission, files',
            'published',
            'DILG Support',
            1,
            45,
            '2026-08-17 08:30:00'
        ],
        [
            'Understanding inquiry status',
            'A quick guide to the meaning of each inquiry status.',
            'Inquiry status values help you understand how a request is progressing. Submitted means a request has been received; Pending means it is waiting for review; In Progress indicates active handling; Approved or Resolved means the request has moved to resolution; and Returned means additional information or correction is required. Use the status values in My Inquiries to monitor and act appropriately.',
            'Inquiries',
            'article',
            'status, inquiry, progress, pending',
            'published',
            'DILG Support',
            1,
            36,
            '2026-08-17 08:40:00'
        ],
        [
            'Account management',
            'Review account and profile settings for your provincial office login.',
            'Your account contains your username, assigned office role, and basic contact preferences. You can access Settings from the sidebar to review and update permitted profile information. For sensitive changes such as password resets or role changes, contact your administrator or the Provincial Office support team.',
            'Account',
            'article',
            'account, profile, settings, secure',
            'published',
            'DILG Support',
            1,
            27,
            '2026-08-17 08:45:00'
        ],
        [
            'How to use notifications and messages',
            'Use the dashboard notification and message tools to stay informed.',
            'Notifications and Messages are connected to your current PO User activities. Check the bell icon for unread alerts, then open Messages for a complete audit trail of system notices, inquiry updates, announcements, and alerts. Mark items as read or unread as needed, and click the action link to navigate to the related page.',
            'Technical',
            'article',
            'notifications, messages, bell, alerts',
            'published',
            'DILG Support',
            1,
            18,
            '2026-08-17 09:00:00'
        ],
        [
            'Troubleshooting dashboard access',
            'Help if you cannot access a feature or dashboard section.',
            'If the dashboard does not load or a page is unavailable, confirm that you are logged in as a PO User and that your session is active. Refresh the page, verify your internet connection, and then revisit the page from the sidebar. If the issue continues, contact the Provincial Office support line or submit a support request through the Help Center.',
            'Technical',
            'article',
            'access, login, dashboard, troubleshooting',
            'published',
            'DILG Support',
            0,
            12,
            '2026-08-17 09:10:00'
        ],
        [
            'Calendar and event reminders',
            'View and manage upcoming office events and reminders.',
            'Use the Calendar page to review upcoming office events, consultations, and training sessions. Event-related reminders may appear in your notifications and messages feed. When you click a reminder, the system can direct you to the corresponding event entry in the calendar.',
            'Technical',
            'article',
            'calendar, events, reminders, schedule',
            'published',
            'DILG Support',
            0,
            10,
            '2026-08-17 09:15:00'
        ],
        [
            'How to use the support contact page',
            'Use the Provincial Office contact details and support channels.',
            'The support panel features the official Provincial Office contact information, including telephone numbers, office hours, and email support. Use this information when you need direct assistance or when the system is not able to answer a question through self-service pages.',
            'Account',
            'article',
            'contact, support, office, phone, email',
            'published',
            'DILG Support',
            0,
            6,
            '2026-08-17 09:20:00'
        ]
    ];

    $stmt = $pdo->prepare('INSERT INTO help_articles (title, summary, content, category, article_type, keywords, status, author, featured, view_count, created_at) VALUES (:title, :summary, :content, :category, :article_type, :keywords, :status, :author, :featured, :view_count, :created_at)');
    foreach ($rows as $row) {
        $stmt->execute([
            ':title' => $row[0],
            ':summary' => $row[1],
            ':content' => $row[2],
            ':category' => $row[3],
            ':article_type' => $row[4],
            ':keywords' => $row[5],
            ':status' => $row[6],
            ':author' => $row[7],
            ':featured' => $row[8],
            ':view_count' => $row[9],
            ':created_at' => $row[10]
        ]);
    }
}

ensure_help_articles_table($pdo);
seed_help_articles($pdo);

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $category = trim((string)($_GET['category'] ?? 'all'));
    $search = trim((string)($_GET['search'] ?? ''));

    $where = ['status = :status'];
    $params = [':status' => 'published'];

    if ($category !== 'all' && $category !== '') {
        $where[] = 'category = :category';
        $params[':category'] = $category;
    }

    if ($search !== '') {
        $where[] = '(title LIKE :search OR summary LIKE :search OR content LIKE :search OR category LIKE :search OR keywords LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    $sql = 'SELECT * FROM help_articles WHERE ' . implode(' AND ', $where) . ' ORDER BY featured DESC, view_count DESC, created_at DESC';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $counts = [
        'all' => 0,
        'Inquiries' => 0,
        'Documents' => 0,
        'Account' => 0,
        'Technical' => 0,
    ];

    $countStmt = $pdo->prepare('SELECT category, COUNT(*) AS total FROM help_articles WHERE status = :status GROUP BY category');
    $countStmt->execute([':status' => 'published']);
    foreach ($countStmt->fetchAll() as $row) {
        $counts[$row['category']] = (int)$row['total'];
    }
    $counts['all'] = (int)$pdo->query('SELECT COUNT(*) FROM help_articles WHERE status = "published"')->fetchColumn();

    echo json_encode([
        'success' => true,
        'articles' => $articles,
        'counts' => $counts,
        'faqs' => array_values(array_filter($articles, fn($article) => $article['article_type'] === 'faq')),
        'popular' => array_values(array_filter($articles, fn($article) => (int)$article['featured'] === 1 || (int)$article['view_count'] > 15))
    ]);
    exit;
}

if ($action === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Article not found.']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM help_articles WHERE id = :id AND status = :status LIMIT 1');
    $stmt->execute([':id' => $id, ':status' => 'published']);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$article) {
        echo json_encode(['success' => false, 'error' => 'Article not found.']);
        exit;
    }

    $pdo->prepare('UPDATE help_articles SET view_count = view_count + 1 WHERE id = :id')->execute([':id' => $id]);

    $relatedSql = 'SELECT * FROM help_articles WHERE status = :status AND category = :category AND id != :id ORDER BY view_count DESC, created_at DESC LIMIT 3';
    $relatedStmt = $pdo->prepare($relatedSql);
    $relatedStmt->execute([':status' => 'published', ':category' => $article['category'], ':id' => $id]);
    $related = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'article' => $article, 'related' => $related]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unsupported action']);

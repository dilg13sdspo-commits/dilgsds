<?php
/**
 * Seed sample PO reports with "Under Review" status for testing
 * Run from the project root: php auth/seed_po_reports.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

try {
    $pdo = db_connect();
    
    // First, ensure we have PO and FO users
    $poUser = $pdo->query("SELECT id FROM users WHERE role = 'PO USER' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $foUser = $pdo->query("SELECT id FROM users WHERE role = 'FO USER' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if (!$poUser || !$foUser) {
        echo "Error: Need at least one PO USER and one FO USER in the database.\n";
        echo "Run seed_po_accounts.php and seed_fo_accounts.php first.\n";
        exit(1);
    }
    
    $poUserId = (int)$poUser['id'];
    $foUserId = (int)$foUser['id'];
    
    echo "Seeding PO Reports with Under Review status...\n";
    echo "PO User ID: $poUserId\n";
    echo "FO User ID: $foUserId\n\n";
    
    // Sample reports data
    $reports = [
        [
            'title' => 'Municipal Development Report Q2 2026',
            'details' => 'Comprehensive report on municipal development initiatives and progress for the second quarter of 2026.',
            'category' => 'Administrative',
            'reference_number' => 'DILG-2026-000001',
        ],
        [
            'title' => 'Infrastructure Assessment Report',
            'details' => 'Assessment and evaluation of local infrastructure projects including roads, water systems, and public facilities.',
            'category' => 'Technical',
            'reference_number' => 'DILG-2026-000002',
        ],
        [
            'title' => 'Capacity Building Program Report',
            'details' => 'Summary of capacity building and training programs conducted for local government officials and staff.',
            'category' => 'Capacity Building',
            'reference_number' => 'DILG-2026-000003',
        ],
        [
            'title' => 'Disaster Risk Management Status Update',
            'details' => 'Status update on disaster risk management programs, preparedness activities, and mitigation measures.',
            'category' => 'Logistics',
            'reference_number' => 'DILG-2026-000004',
        ],
        [
            'title' => 'Budget Utilization Report',
            'details' => 'Detailed report on budget allocation, utilization, and financial performance for the current fiscal year.',
            'category' => 'Administrative',
            'reference_number' => 'DILG-2026-000005',
        ],
    ];
    
    foreach ($reports as $report) {
        // Insert report with FO response pre-filled to mark as "Under Review"
        $stmt = $pdo->prepare('
            INSERT INTO reports (
                user_id, title, details, category, reference_number, 
                status, recipient_office, assigned_to, submitted_at, fo_response, fo_response_at, fo_responded_by, created_at
            ) VALUES (
                :user_id, :title, :details, :category, :reference_number,
                :status, :recipient_office, :assigned_to, :submitted_at, :fo_response, :fo_response_at, :fo_responded_by, NOW()
            )
        ');
        
        $submitted = date('Y-m-d H:i:s', strtotime('-3 days'));
        $responded = date('Y-m-d H:i:s', strtotime('-2 days'));
        $foResponse = "FO response to the report: We have reviewed the submitted report and have the following observations and recommendations for improvement.";
        
        $stmt->execute([
            ':user_id' => $poUserId,
            ':title' => $report['title'],
            ':details' => $report['details'],
            ':category' => $report['category'],
            ':reference_number' => $report['reference_number'],
            ':status' => 'SUBMITTED',
            ':recipient_office' => 'Test Municipality',
            ':assigned_to' => $foUserId,
            ':submitted_at' => $submitted,
            ':fo_response' => $foResponse,
            ':fo_response_at' => $responded,
            ':fo_responded_by' => $foUserId,
        ]);
        
        $reportId = (int)$pdo->lastInsertId();
        
        // Add report recipient
        $recipientStmt = $pdo->prepare('
            INSERT INTO report_recipients (report_id, user_id, office_name)
            VALUES (:report_id, :user_id, :office_name)
        ');
        
        $recipientStmt->execute([
            ':report_id' => $reportId,
            ':user_id' => $foUserId,
            ':office_name' => 'Test Municipality',
        ]);
        
        echo "✓ Created report: {$report['reference_number']} - {$report['title']}\n";
    }
    
    echo "\n✓ Successfully seeded " . count($reports) . " sample reports with 'Under Review' status!\n";
    echo "These reports should now appear in the PO Reports page.\n";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

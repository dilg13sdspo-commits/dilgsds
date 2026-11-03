<?php
/**
 * Provision FO accounts with random temporary passwords.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/workflow.php';

$accounts = [
    'Municipality of Barobo' => ['username' => 'fo_barobo'],
    'Municipality of Bayabas' => ['username' => 'fo_bayabas'],
    'City of Bislig' => ['username' => 'fo_bislig'],
    'Municipality of Cagwait' => ['username' => 'fo_cagwait'],
    'Municipality of Cantilan' => ['username' => 'fo_cantilan'],
    'Municipality of Carmen' => ['username' => 'fo_carmen'],
    'Municipality of Carrascal' => ['username' => 'fo_carrascal'],
    'Municipality of Cortes' => ['username' => 'fo_cortes'],
    'Municipality of Hinatuan' => ['username' => 'fo_hinatuan'],
    'Municipality of Lanuza' => ['username' => 'fo_lanuza'],
    'Municipality of Lianga' => ['username' => 'fo_lianga'],
    'Municipality of Lingig' => ['username' => 'fo_lingig'],
    'Municipality of Madrid' => ['username' => 'fo_madrid'],
    'Municipality of Marihatag' => ['username' => 'fo_marihatag'],
    'Municipality of San Agustin' => ['username' => 'fo_sanagustin'],
    'Municipality of San Miguel' => ['username' => 'fo_sanmiguel'],
    'Municipality of Tagbina' => ['username' => 'fo_tagbina'],
    'Municipality of Tago' => ['username' => 'fo_tago'],
    'City of Tandag' => ['username' => 'fo_tandag'],
];

$pdo = db_connect();
workflow_ensure_schema($pdo);
$pdo->beginTransaction();
try {
    $officeStmt = $pdo->prepare("INSERT INTO offices (name, office_type) VALUES (:name, :type) ON DUPLICATE KEY UPDATE is_active = 1");
    $officeIdStmt = $pdo->prepare('SELECT id FROM offices WHERE name = :name LIMIT 1');
    $userStmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
    $insertUser = $pdo->prepare('INSERT INTO users (username, email, password_hash, full_name, role, office_name, office_id, is_active, must_change_password) VALUES (:username, :email, :hash, :full_name, \'FO USER\', :office_name, :office_id, 1, 1)');
    $updateUser = $pdo->prepare('UPDATE users SET full_name = :full_name, role = \'FO USER\', office_name = :office_name, office_id = :office_id, is_active = 1, must_change_password = 1 WHERE id = :id');
    $passwordStmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');

    foreach ($accounts as $office => $data) {
        $type = str_starts_with($office, 'City of ') ? 'CITY' : 'MUNICIPALITY';
        $username = $data['username'];
        $password = bin2hex(random_bytes(16));
        $email = $username . '@dilg.gov.ph';

        $officeStmt->execute([':name' => $office, ':type' => $type]);
        $officeIdStmt->execute([':name' => $office]);
        $officeId = (int)$officeIdStmt->fetchColumn();
        $userStmt->execute([':username' => $username, ':email' => $email]);
        $existing = $userStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $updateUser->execute([
                ':full_name' => $office . ' FO User',
                ':office_name' => $office,
                ':office_id' => $officeId,
                ':id' => $existing['id'],
            ]);
            $passwordStmt->execute([
                ':hash' => password_hash($password, PASSWORD_DEFAULT),
                ':id' => $existing['id'],
            ]);
            echo "Reset {$username} ({$office}); temporary password: {$password}\n";
            continue;
        }

        $insertUser->execute([
            ':username' => $username,
            ':email' => $email,
            ':hash' => password_hash($password, PASSWORD_DEFAULT),
            ':full_name' => $office . ' FO User',
            ':office_name' => $office,
            ':office_id' => $officeId,
        ]);
        echo "Created {$username} ({$office}); temporary password: {$password}\n";
    }

    $pdo->commit();
    echo "FO accounts ready to use.\n";
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "Provisioning failed: {$error->getMessage()}\n");
    exit(1);
}

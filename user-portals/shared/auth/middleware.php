<?php
// Portal pages use the main project's session and role enforcement.
require_once __DIR__ . '/../../../auth/middleware.php';

if (!function_exists('get_user_profile_photo')) {
    function get_user_profile_photo(PDO $pdo, int $userId): string {
        try {
            $stmt = $pdo->prepare('SELECT profile_photo FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);
            $photo = trim((string)($stmt->fetchColumn() ?: ''));
            if ($photo !== '' && !preg_match('#^(?:https?:)?//#i', $photo) && $photo[0] !== '/') {
                $storedPhoto = ltrim($photo, '/\\');
                $fileCandidates = [dirname(__DIR__) . '/' . $storedPhoto];
                if (strpos($storedPhoto, 'uploads/') !== 0) {
                    $fileCandidates[] = dirname(__DIR__) . '/uploads/' . $storedPhoto;
                }
                $filePath = '';
                foreach ($fileCandidates as $candidate) {
                    if (is_file($candidate)) {
                        $filePath = $candidate;
                        break;
                    }
                }
                if ($filePath === '') {
                    return '';
                }
                $photo = '../shared/' . ltrim(str_replace('\\', '/', $storedPhoto), '/');
                if (strpos($storedPhoto, 'uploads/') !== 0) {
                    $photo = '../shared/uploads/' . ltrim(str_replace('\\', '/', $storedPhoto), '/');
                }
            }
            return $photo;
        } catch (Throwable $e) {
            return '';
        }
    }
}

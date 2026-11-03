<?php
/**
 * DILG Website - Main Entry Point
 * Map the root route to the organized public frontend while preserving the existing app structure.
 */

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');

if ($base === '.' || $base === '\\' || $base === '') {
    $base = '';
}

$request = $requestPath;
if ($base !== '' && strpos($request, $base) === 0) {
    $request = substr($request, strlen($base));
}

$request = '/' . ltrim($request, '/');

if ($request === '/' || $request === '/index.php' || $request === '/index.html') {
    header('Location: /DILG Website22/public/index.php');
    exit;
}

if (preg_match('#^/public(/.*)?$#', $request)) {
    $publicTarget = __DIR__ . '/public/pages/index.html';
    if (file_exists($publicTarget)) {
        header('Location: /DILG Website22/public/pages/index.html');
        exit;
    }
}

if (preg_match('#^/public/pages/.+\.html$#', $request)) {
    $page = __DIR__ . $request;
    if (file_exists($page)) {
        include $page;
        exit;
    }
}

if (preg_match('#^/(backend|auth|public)/#', $request) || preg_match('#\.(php|html)$#', $request)) {
    $resolved = __DIR__ . $request;
    if (file_exists($resolved)) {
        if (str_ends_with($request, '.php')) {
            include $resolved;
        } else {
            readfile($resolved);
        }
        exit;
    }
}

header('Location: /DILG Website22/public/index.php');
exit;
?>

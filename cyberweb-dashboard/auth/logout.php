<?php
require_once __DIR__ . '/bootstrap.php';

// The logout button submits a POST form with the existing CSRF token.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !valid_csrf()) {
    http_response_code(403);
    exit('Invalid logout request. Please use the Logout button.');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}
session_destroy();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Location: /cyberweb-dashboard/login.php', true, 303);
exit;
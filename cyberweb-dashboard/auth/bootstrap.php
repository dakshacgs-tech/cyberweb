<?php
// Included by the UI and authentication endpoints.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('CYBERWEB_MAIN_SID');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/cyberweb-dashboard/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function valid_csrf(): bool {
    return isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        && hash_equals(csrf_token(), $_POST['csrf_token']);
}
function flash_and_redirect(string $message, string $target): void {
    $_SESSION['flash'] = $message;
    header('Location: ../' . $target, true, 303);
    exit;
}
function pull_flash(): ?string {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_string($flash) ? $flash : null;
}

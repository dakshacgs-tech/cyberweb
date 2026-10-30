<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

if (!valid_csrf()) {
    flash_and_redirect(
        'Session expired. Refresh and try again.',
        'login.php'
    );
}

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');

// Check empty fields
if ($username === '' || $password === '') {
    flash_and_redirect(
        'Please enter username and password.',
        'login.php'
    );
}

// Accept username "admin" or admin email
if (strtolower($username) === 'admin') {
    $email = 'admin@cyberweb.local';
} else {
    $email = $username;
}

require_once __DIR__ . '/../api/db.php';

try {

    $stmt = $pdo->prepare(
        "SELECT id, first_name, email, password
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log('Login DB error: ' . $e->getMessage());

    flash_and_redirect(
        'Login is temporarily unavailable.',
        'login.php'
    );
}

// Verify password
if (!$user || !password_verify($password, $user['password'])) {

    flash_and_redirect(
        'Invalid username or password.',
        'login.php'
    );
}

// Login successful
session_regenerate_id(true);

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['user_name'] = $user['first_name'];
$_SESSION['email'] = $user['email'];

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

session_write_close();
header('Location: /cyberweb-dashboard/index.php', true, 303);
exit;

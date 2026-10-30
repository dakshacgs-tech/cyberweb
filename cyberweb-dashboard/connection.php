<?php
/**
 * Backward-compatible connection check for old /connection.php links.
 * Application database access is implemented in api/db.php.
 * Remove this optional check page in production if not needed.
 */
declare(strict_types=1);
require_once __DIR__ . '/auth/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
$connected = false;
try {
    require_once __DIR__ . '/api/db.php';
    $connected = isset($pdo) && $pdo instanceof PDO && $pdo->query('SELECT 1')->fetchColumn() == 1;
} catch (Throwable $e) {
    error_log('Cyberweb connection check failed: ' . $e->getMessage());
}
$status = $connected ? 'Database connected successfully' : 'Database connection unavailable';
http_response_code($connected ? 200 : 503);
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Cyberweb Database Connection</title>
<style>body{font:16px/1.6 system-ui,Arial,sans-serif;background:#f5f7fb;color:#17233d;margin:0;padding:50px 20px}.card{max-width:600px;margin:auto;background:white;border-radius:16px;padding:30px;box-shadow:0 12px 40px #17233d13}h1{font-size:25px;margin:0 0 10px}.status{font-weight:700;color:<?= $connected ? "#116b40" : "#a12323" ?>}a{color:#184fa3}code{background:#f1f3f7;padding:3px 6px;border-radius:4px}</style></head><body>
<main class="card"><h1>Cyberweb Dashboard — Connection Check</h1><p class="status" role="status"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></p><p>Database connection file: <code>api/db.php</code></p><p><a href="/cyberweb-dashboard/login.php">Open Login</a> · <a href="/cyberweb-dashboard/index.php">Open Dashboard</a> · <a href="/cyberweb-dashboard/projects.php">My Projects</a></p><p>This page is a compatibility check. It does not display database credentials.</p></main>
</body></html>

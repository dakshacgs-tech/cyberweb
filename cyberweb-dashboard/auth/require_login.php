<?php
require_once __DIR__ . '/bootstrap.php';
if (empty($_SESSION['user_id'])) {
    header('Location: /cyberweb-dashboard/login.php', true, 302);
    exit;
}

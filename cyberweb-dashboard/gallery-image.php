<?php
// A binary image response must not contain HTML, warnings, or stray whitespace.
ob_start();
ini_set('display_errors', '0');
require_once __DIR__ . '/auth/require_login.php';
require_once __DIR__ . '/api/db.php';

$userId = (int)$_SESSION['user_id'];
$projectId = filter_var($_GET['project_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$imageId = filter_var($_GET['image_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$projectId || !$imageId) { ob_end_clean(); http_response_code(400); exit; }

try {
    $stmt = $pdo->prepare('SELECT file_name FROM gallery_images WHERE id=? AND project_id=? AND user_id=? LIMIT 1');
    $stmt->execute([(int)$imageId, (int)$projectId, $userId]);
    $name = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Gallery image database error: ' . $e->getMessage());
    ob_end_clean(); http_response_code(500); exit;
}

if (!$name || !preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', (string)$name)) {
    ob_end_clean(); http_response_code(404); exit;
}
$mime = ['jpg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'][pathinfo((string)$name, PATHINFO_EXTENSION)];
$path = __DIR__ . '/storage/gallery/' . (int)$projectId . '/' . $name;
if (!is_file($path) || !is_readable($path)) { ob_end_clean(); http_response_code(404); exit; }
$image = @getimagesize($path);
if (!$image || ($image['mime'] ?? '') !== $mime) {
    error_log('Invalid saved gallery image: project ' . $projectId . ', image ' . $imageId);
    ob_end_clean(); http_response_code(415); exit;
}
$handle = @fopen($path, 'rb');
if (!$handle) { ob_end_clean(); http_response_code(500); exit; }

// Drop any output from included files before the first image byte is streamed.
ob_end_clean();
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline');
header('Cache-Control: private, no-store, max-age=0');
// Deliberately omit Content-Length (PHP/Apache compression can change it).
if (@fpassthru($handle) === false) {
    error_log('Failed streaming gallery image: project ' . $projectId . ', image ' . $imageId);
}
fclose($handle);
exit;

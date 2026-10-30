<?php
// Shared data access for the project-specific hotel editor and previews.
require_once __DIR__ . '/team_data.php';
function hotel_schema(PDO $pdo): void {
    cyberweb_team_schema($pdo);
    $pdo->exec("CREATE TABLE IF NOT EXISTS hotel_details (
        project_id INT UNSIGNED NOT NULL PRIMARY KEY,
        user_id INT NOT NULL,
        hotel_name VARCHAR(150) NOT NULL,
        phone VARCHAR(40) NOT NULL DEFAULT '',
        address TEXT NOT NULL,
        amenities TEXT NOT NULL,
        things_to_do TEXT NOT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_hotel_details_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_images (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        project_id INT UNSIGNED NOT NULL,
        user_id INT NOT NULL,
        file_name VARCHAR(64) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_gallery_owner (project_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS hotel_faqs (
        project_id INT UNSIGNED NOT NULL,
        user_id INT NOT NULL,
        faq_position TINYINT UNSIGNED NOT NULL,
        question VARCHAR(250) NOT NULL,
        answer TEXT NOT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (project_id, faq_position),
        INDEX idx_hotel_faqs_owner (project_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function hotel_escape($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function hotel_project(PDO $pdo, int $projectId, int $userId): ?array {
    $stmt = $pdo->prepare('SELECT id, name, property_name, location, team_member, template FROM projects WHERE id=? AND user_id=? LIMIT 1');
    $stmt->execute([$projectId, $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function hotel_record(PDO $pdo, int $projectId, int $userId): ?array {
    $stmt = $pdo->prepare('SELECT hotel_name, phone, address, amenities, things_to_do FROM hotel_details WHERE project_id=? AND user_id=? LIMIT 1');
    $stmt->execute([$projectId, $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function hotel_gallery(PDO $pdo, int $projectId, int $userId): array {
    $stmt = $pdo->prepare('SELECT id, file_name FROM gallery_images WHERE project_id=? AND user_id=? ORDER BY id ASC');
    $stmt->execute([$projectId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// FAQ positions are tied to one project, not to the chosen theme. A theme change
// will retain the same three optional questions and answers.
function hotel_faqs(PDO $pdo, int $projectId, int $userId): array {
    $stmt = $pdo->prepare('SELECT faq_position, question, answer FROM hotel_faqs WHERE project_id=? AND user_id=? AND faq_position BETWEEN 1 AND 3 ORDER BY faq_position ASC');
    $stmt->execute([$projectId, $userId]);
    $result = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $result[(int)$row['faq_position']] = ['question' => (string)$row['question'], 'answer' => (string)$row['answer']];
    }
    return $result;
}

// Keep broken file references visible to the editor, but never render them as images.
// This is useful when a dashboard update accidentally replaced storage/gallery.
function hotel_gallery_availability(array $photos, int $projectId): array {
    $available = [];
    $missing = [];
    foreach ($photos as $photo) {
        $fileName = (string)($photo['file_name'] ?? '');
        $path = dirname(__DIR__) . '/storage/gallery/' . $projectId . '/' . $fileName;
        if (preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $fileName)
            && is_file($path) && is_readable($path) && filesize($path) > 0) {
            $available[] = $photo;
        } else {
            $missing[] = $photo;
        }
    }
    return ['available' => $available, 'missing' => $missing];
}

function hotel_lines(string $value, int $limit = 30): array {
    $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
    $result = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') $result[] = $line;
        if (count($result) >= $limit) break;
    }
    return $result;
}

<?php
// Team folders are database-backed groupings within My Projects, not operating-system folders.
function cyberweb_team_members(): array {
    return ['Shravani', 'Priti Patel', 'Sushma', 'Sapna', 'Kamini', 'Rahul Uke',
        'Gaurav', 'Satish', 'Rajesh', 'Jitesh', 'daksha', 'ananaya'];
}

function cyberweb_team_schema(PDO $pdo): void {
    static $ready = false;
    if ($ready) return;

    $column = $pdo->query("SHOW COLUMNS FROM projects LIKE 'team_member'");
    if (!$column->fetch(PDO::FETCH_ASSOC)) {
        try {
            // Preserve the existing location column/data for compatibility with older projects.
            $pdo->exec("ALTER TABLE projects ADD COLUMN team_member VARCHAR(100) NOT NULL DEFAULT '' AFTER location");
        } catch (PDOException $e) {
            // Another request may have performed the migration concurrently.
            $verify = $pdo->query("SHOW COLUMNS FROM projects LIKE 'team_member'");
            if (!$verify->fetch(PDO::FETCH_ASSOC)) throw $e;
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS template_settings (
        project_id INT UNSIGNED NOT NULL,
        user_id INT NOT NULL,
        template VARCHAR(30) NOT NULL,
        brand_name VARCHAR(150) NOT NULL DEFAULT '',
        headline VARCHAR(160) NOT NULL DEFAULT '',
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (project_id, user_id, template),
        INDEX idx_template_settings_owner (user_id, project_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ready = true;
}

function cyberweb_template_settings(PDO $pdo, int $projectId, int $userId): array {
    $stmt = $pdo->prepare('SELECT template,brand_name,headline FROM template_settings WHERE project_id=? AND user_id=?');
    $stmt->execute([$projectId, $userId]);
    $settings = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $settings[$row['template']] = ['brand' => (string)$row['brand_name'], 'headline' => (string)$row['headline']];
    }
    return $settings;
}

function cyberweb_save_template_setting(PDO $pdo, int $projectId, int $userId, string $template, string $brand, string $headline): void {
    $stmt = $pdo->prepare('INSERT INTO template_settings (project_id,user_id,template,brand_name,headline)
        VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE brand_name=VALUES(brand_name),headline=VALUES(headline)');
    $stmt->execute([$projectId, $userId, $template, $brand, $headline]);
}

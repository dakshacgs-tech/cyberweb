-- Run once in phpMyAdmin > cyberweb > SQL. Does not modify users or authentication tables.
CREATE TABLE IF NOT EXISTS projects (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 name VARCHAR(150) NOT NULL,
 property_name VARCHAR(150) NOT NULL DEFAULT '',
 location VARCHAR(150) NOT NULL DEFAULT '',
 team_member VARCHAR(100) NOT NULL DEFAULT '',
 template VARCHAR(30) NOT NULL DEFAULT '',
 status ENUM('draft','in_progress','completed','published') NOT NULL DEFAULT 'draft',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_projects_user_updated (user_id,updated_at),
 INDEX idx_projects_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

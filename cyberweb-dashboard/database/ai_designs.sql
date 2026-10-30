-- Additive migration for existing cyberweb database. Import ONLY this file, not the full dump.
CREATE TABLE IF NOT EXISTS ai_generation_jobs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 request_id CHAR(32) NOT NULL UNIQUE,
 project_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
 status ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
 input_json LONGTEXT NOT NULL, system_prompt LONGTEXT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, completed_at TIMESTAMP NULL,
 INDEX idx_owner(project_id,user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS ai_generated_designs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, job_id BIGINT UNSIGNED NOT NULL,
 project_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
 design_number TINYINT UNSIGNED NOT NULL, style_name VARCHAR(90) NOT NULL,
 archive_name VARCHAR(100) NOT NULL, sha256 CHAR(64) NOT NULL,
 selected_at TIMESTAMP NULL DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_job_design(job_id, design_number), INDEX idx_design_owner(project_id,user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS ai_design_downloads (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, design_id BIGINT UNSIGNED NOT NULL,
 project_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
 downloaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_download_owner(project_id,user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

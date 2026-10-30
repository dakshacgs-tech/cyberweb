-- CYBERWEB team member folders and saved template text.
-- Only needed if the automatic schema upgrade fails because the app DB user lacks ALTER/CREATE rights.
-- IMPORTANT: Run this ALTER once for an older projects table. If team_member already exists,
-- skip the ALTER statement and run only CREATE TABLE below.
ALTER TABLE projects ADD COLUMN team_member VARCHAR(100) NOT NULL DEFAULT '' AFTER location;

CREATE TABLE IF NOT EXISTS template_settings (
  project_id INT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  template VARCHAR(30) NOT NULL,
  brand_name VARCHAR(150) NOT NULL DEFAULT '',
  headline VARCHAR(160) NOT NULL DEFAULT '',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (project_id, user_id, template),
  INDEX idx_template_settings_owner (user_id, project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

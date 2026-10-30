-- Import once in the existing cyberweb database. No existing project data removed.
ALTER TABLE ai_generation_jobs
 MODIFY COLUMN status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending';
-- No extra database privileges or API credentials needed.

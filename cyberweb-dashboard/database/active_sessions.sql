-- Import once into the cyberweb database in phpMyAdmin.
CREATE TABLE IF NOT EXISTS login_capacity_lock (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY
) ENGINE=InnoDB;
INSERT IGNORE INTO login_capacity_lock (id) VALUES (1);
CREATE TABLE IF NOT EXISTS active_login_sessions (
    session_token CHAR(64) NOT NULL PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    last_seen DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_last_seen (last_seen),
    KEY idx_user_id (user_id)
) ENGINE=InnoDB;

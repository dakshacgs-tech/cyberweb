-- CYBERWEB hotel content and gallery (run after database/projects.sql).
-- The application also creates these tables automatically if the DB account has CREATE permission.
CREATE TABLE IF NOT EXISTS hotel_details (
  project_id INT UNSIGNED NOT NULL PRIMARY KEY,
  user_id INT NOT NULL,
  hotel_name VARCHAR(150) NOT NULL,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  address TEXT NOT NULL,
  amenities TEXT NOT NULL,
  things_to_do TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_hotel_details_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gallery_images (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  file_name VARCHAR(64) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_gallery_owner (project_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Three optional FAQs per hotel project. This table is also created automatically.
CREATE TABLE IF NOT EXISTS hotel_faqs (
  project_id INT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  faq_position TINYINT UNSIGNED NOT NULL,
  question VARCHAR(250) NOT NULL,
  answer TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (project_id, faq_position),
  INDEX idx_hotel_faqs_owner (project_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

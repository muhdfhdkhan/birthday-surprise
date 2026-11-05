-- ============================================================
-- Birthday Surprise — seed data (import AFTER schema.sql)
--
--   C:\xampp\mysql\bin\mysql.exe -u root -e "source X:/Vibe/Birthday/database/seed.sql"
--
-- The admin account is NOT created here on deploys — the web
-- installer (public/install.php) asks you to choose the first
-- admin username + password when setting the app up.
-- ============================================================

SET NAMES utf8mb4;

USE birthday_surprise;

-- Default admin ----------------------------------------------
INSERT INTO admins (username, email, password_hash, display_name, role, must_change_password, is_active)
VALUES ('admin', 'admin@localhost.test',
        '$2y$12$hob.AQBVEh4psOGMZsSkxOQNe9x3Zz53lz58kFGepNBvDxxnSP0Z6',
        'Admin', 'super_admin', 0, 1)
ON DUPLICATE KEY UPDATE username = username;

-- Starter app settings ---------------------------------------
INSERT INTO app_settings (setting_key, setting_value) VALUES
  ('app_name',             'Birthday Surprise'),
  ('app_timezone',         'UTC'),
  ('maintenance_mode',     '0'),
  ('log_retention_days',   '90'),
  ('uploads_max_mb',       '5'),
  ('uploads_allowed_types','jpeg,jpg,png,webp'),
  ('access_window_hours',  '24'),
  ('visitor_privacy_note', 'We log visits and IP addresses to keep this page safe.')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

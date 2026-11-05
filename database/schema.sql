-- ============================================================
-- Birthday Surprise — database schema (MySQL 8+ / MariaDB 10.6+)
-- Engine: InnoDB · Charset: utf8mb4 · Foreign keys + indexed queries
--
-- Import via phpMyAdmin:  Import tab → choose this file (no database selected)
-- Import via CLI:
--   C:\xampp\mysql\bin\mysql.exe -u root -e "source X:/Vibe/Birthday/database/schema.sql"
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS birthday_surprise
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE birthday_surprise;

-- ---------- 1. admins --------------------------------------
DROP TABLE IF EXISTS admins;
CREATE TABLE admins (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username             VARCHAR(60)  NOT NULL,
  email                VARCHAR(190) NOT NULL,
  password_hash        VARCHAR(255) NOT NULL,
  display_name         VARCHAR(120) NOT NULL DEFAULT 'Admin',
  role                 ENUM('super_admin','admin') NOT NULL DEFAULT 'admin',
  must_change_password TINYINT(1)   NOT NULL DEFAULT 0,
  is_active            TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at        DATETIME     NULL,
  last_login_ip        VARCHAR(45)  NULL,
  created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admins_username (username),
  UNIQUE KEY uq_admins_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 2. app_settings --------------------------------
DROP TABLE IF EXISTS app_settings;
CREATE TABLE app_settings (
  setting_key   VARCHAR(120) NOT NULL,
  setting_value TEXT         NULL,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 3. rate_limits ---------------------------------
DROP TABLE IF EXISTS rate_limits;
CREATE TABLE rate_limits (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rate_key          VARCHAR(190)   NOT NULL,
  attempts          INT UNSIGNED   NOT NULL DEFAULT 0,
  window_started_at DATETIME       NOT NULL,
  expires_at        DATETIME       NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rate_key (rate_key),
  KEY idx_rate_limits_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 4. birthday_profiles ---------------------------
DROP TABLE IF EXISTS birthday_profiles;
CREATE TABLE birthday_profiles (
  id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  display_name          VARCHAR(120) NOT NULL,
  slug                  VARCHAR(120) NOT NULL,
  profile_image         VARCHAR(255) NULL,
  birthday_at           DATETIME     NOT NULL,
  timezone              VARCHAR(64)  NOT NULL DEFAULT 'UTC',
  status                ENUM('draft','scheduled','published','paused','archived') NOT NULL DEFAULT 'draft',
  publish_at            DATETIME     NULL,
  block_before_birthday TINYINT(1)   NOT NULL DEFAULT 0,
  allow_early_peek      TINYINT(1)   NOT NULL DEFAULT 1,
  allow_share           TINYINT(1)   NOT NULL DEFAULT 0,
  created_by            INT UNSIGNED NULL,
  deleted_at            DATETIME     NULL,
  created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_profiles_slug (slug),
  KEY idx_profiles_status (status),
  KEY idx_profiles_birthday (birthday_at),
  CONSTRAINT fk_profiles_creator FOREIGN KEY (created_by) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 5. profile_access (per-profile password) --------
DROP TABLE IF EXISTS profile_access;
CREATE TABLE profile_access (
  id               INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  profile_id       INT UNSIGNED      NOT NULL,
  password_hash    VARCHAR(255)      NOT NULL,
  access_hours     SMALLINT UNSIGNED NOT NULL DEFAULT 24,
  max_reentry_uses SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  reentry_policy   ENUM('approve_required','open') NOT NULL DEFAULT 'approve_required',
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_profile_access_profile (profile_id),
  CONSTRAINT fk_access_profile FOREIGN KEY (profile_id) REFERENCES birthday_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 6. birthday_content (one row per profile) -------
DROP TABLE IF EXISTS birthday_content;
CREATE TABLE birthday_content (
  id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  profile_id            INT UNSIGNED NOT NULL,
  welcome_message       TEXT         NULL,
  gift_categories       TEXT         NULL,
  complaint_placeholder VARCHAR(255) NULL,
  sorry_required        TINYINT(1)   NOT NULL DEFAULT 1,
  sections_enabled      VARCHAR(120) NOT NULL DEFAULT 'welcome,unlock,sorry,reveal,memories,letter,replies,finale',
  letter_text           MEDIUMTEXT   NULL,
  secret_unlock_rule    ENUM('after_sorry','after_candles','manual','never') NOT NULL DEFAULT 'after_sorry',
  secret_message        TEXT         NULL,
  final_message         TEXT         NULL,
  post_birthday_message TEXT         NULL,
  updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_content_profile (profile_id),
  CONSTRAINT fk_content_profile FOREIGN KEY (profile_id) REFERENCES birthday_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 7. sorry_messages (ordered apologies) -----------
DROP TABLE IF EXISTS sorry_messages;
CREATE TABLE sorry_messages (
  id            INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  profile_id    INT UNSIGNED      NOT NULL,
  body          TEXT              NOT NULL,
  cat_action    VARCHAR(60)       NOT NULL DEFAULT 'begging',
  forgive_label VARCHAR(80)       NOT NULL DEFAULT 'Okay, I forgive you 💖',
  angry_label   VARCHAR(80)       NOT NULL DEFAULT 'Still angry 😾',
  sort_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active     TINYINT(1)        NOT NULL DEFAULT 1,
  created_at    DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sorry_profile_order (profile_id, sort_order),
  CONSTRAINT fk_sorry_profile FOREIGN KEY (profile_id) REFERENCES birthday_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 8. birthday_messages (ordered wishes) -----------
DROP TABLE IF EXISTS birthday_messages;
CREATE TABLE birthday_messages (
  id         INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  profile_id INT UNSIGNED      NOT NULL,
  body       TEXT              NOT NULL,
  cat_action VARCHAR(60)       NOT NULL DEFAULT 'dancing',
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active  TINYINT(1)        NOT NULL DEFAULT 1,
  created_at DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_wishes_profile_order (profile_id, sort_order),
  CONSTRAINT fk_wishes_profile FOREIGN KEY (profile_id) REFERENCES birthday_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 9. gallery_images -------------------------------
DROP TABLE IF EXISTS gallery_images;
CREATE TABLE gallery_images (
  id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  profile_id  INT UNSIGNED      NOT NULL,
  file_name   VARCHAR(190)      NOT NULL,
  thumb_name  VARCHAR(190)      NULL,
  caption     VARCHAR(255)      NULL,
  alt_text    VARCHAR(255)      NULL,
  memory_note TEXT              NULL,
  taken_on    DATE              NULL,
  sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_cover    TINYINT(1)        NOT NULL DEFAULT 0,
  is_active   TINYINT(1)        NOT NULL DEFAULT 1,
  created_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_gallery_profile_order (profile_id, sort_order),
  KEY idx_gallery_cover (profile_id, is_cover),
  CONSTRAINT fk_gallery_profile FOREIGN KEY (profile_id) REFERENCES birthday_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 10. media_assets (audio / effects) --------------
DROP TABLE IF EXISTS media_assets;
CREATE TABLE media_assets (
  id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  profile_id     INT UNSIGNED  NULL,
  kind           ENUM('music','sfx','animation') NOT NULL DEFAULT 'music',
  file_name      VARCHAR(190)  NOT NULL,
  label          VARCHAR(120)  NULL,
  mime_type      VARCHAR(80)   NULL,
  start_point    DECIMAL(10,3) NOT NULL DEFAULT 0.000,
  default_volume DECIMAL(4,3)  NOT NULL DEFAULT 0.350,
  is_active      TINYINT(1)    NOT NULL DEFAULT 1,
  created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_media_profile (profile_id, kind),
  CONSTRAINT fk_media_profile FOREIGN KEY (profile_id) REFERENCES birthday_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 11. profile_theme_settings ----------------------
DROP TABLE IF EXISTS profile_theme_settings;
CREATE TABLE profile_theme_settings (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  profile_id        INT UNSIGNED NOT NULL,
  theme             ENUM('pastel_cats','romantic_pink','premium_night') NOT NULL DEFAULT 'pastel_cats',
  accent_color      CHAR(7)      NULL,
  font_pair         VARCHAR(60)  NOT NULL DEFAULT 'poppins_nunito',
  confetti_enabled  TINYINT(1)   NOT NULL DEFAULT 1,
  fireworks_enabled TINYINT(1)   NOT NULL DEFAULT 1,
  hearts_enabled    TINYINT(1)   NOT NULL DEFAULT 1,
  balloons_enabled  TINYINT(1)   NOT NULL DEFAULT 1,
  dodging_angry_btn TINYINT(1)   NOT NULL DEFAULT 1,
  animation_speed   ENUM('slow','normal','fast') NOT NULL DEFAULT 'normal',
  lightweight_mode  TINYINT(1)   NOT NULL DEFAULT 0,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_theme_profile (profile_id),
  CONSTRAINT fk_theme_profile FOREIGN KEY (profile_id) REFERENCES birthday_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 12. visitors (pseudonymous) ---------------------
DROP TABLE IF EXISTS visitors;
CREATE TABLE visitors (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  visitor_uid   CHAR(36)     NOT NULL,
  ip_address    VARCHAR(45)  NULL,
  ip_hash       CHAR(64)     NULL,
  browser       VARCHAR(120) NULL,
  device        VARCHAR(40)  NULL,
  os            VARCHAR(60)  NULL,
  visit_count   INT UNSIGNED NOT NULL DEFAULT 1,
  first_seen_at DATETIME     NOT NULL,
  last_seen_at  DATETIME     NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_visitor_uid (visitor_uid),
  KEY idx_visitors_ip (ip_address),
  KEY idx_visitors_last_seen (last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 13. activity_logs -------------------------------
DROP TABLE IF EXISTS activity_logs;
CREATE TABLE activity_logs (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  profile_id   INT UNSIGNED   NULL,
  visitor_id   BIGINT UNSIGNED NULL,
  visitor_uid  VARCHAR(64)    NULL,
  session_id   VARCHAR(128)   NULL,
  ip_address   VARCHAR(45)    NULL,
  event        VARCHAR(60)    NOT NULL,
  page         VARCHAR(150)   NULL,
  stage        VARCHAR(40)    NULL,
  browser      VARCHAR(120)   NULL,
  device       VARCHAR(40)    NULL,
  os           VARCHAR(60)    NULL,
  referrer     VARCHAR(512)   NULL,
  created_at   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_logs_profile_time (profile_id, created_at),
  KEY idx_logs_ip (ip_address),
  KEY idx_logs_session (session_id),
  KEY idx_logs_event (event),
  KEY idx_logs_time (created_at),
  CONSTRAINT fk_logs_profile FOREIGN KEY (profile_id) REFERENCES birthday_profiles (id) ON DELETE CASCADE,
  CONSTRAINT fk_logs_visitor FOREIGN KEY (visitor_id) REFERENCES visitors (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 14. access_requests (re-entry) ------------------
DROP TABLE IF EXISTS access_requests;
CREATE TABLE access_requests (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  profile_id  INT UNSIGNED NOT NULL,
  visitor_uid VARCHAR(64)  NULL,
  session_id  VARCHAR(128) NULL,
  note        VARCHAR(500) NULL,
  status      ENUM('pending','approved','rejected','expired') NOT NULL DEFAULT 'pending',
  decided_by  INT UNSIGNED NULL,
  reason      VARCHAR(255) NULL,
  decided_at  DATETIME     NULL,
  expires_at  DATETIME     NULL,
  ip_address  VARCHAR(45)  NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_requests_profile_status (profile_id, status),
  KEY idx_requests_status (status),
  KEY idx_requests_created (created_at),
  CONSTRAINT fk_requests_profile FOREIGN KEY (profile_id) REFERENCES birthday_profiles (id) ON DELETE CASCADE,
  CONSTRAINT fk_requests_decider FOREIGN KEY (decided_by) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 15. access_grants (hashed one-time codes) -------
DROP TABLE IF EXISTS access_grants;
CREATE TABLE access_grants (
  id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  profile_id  INT UNSIGNED      NOT NULL,
  request_id  INT UNSIGNED      NULL,
  code_hash   VARCHAR(255)      NOT NULL,
  max_uses    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  uses_left   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  expires_at  DATETIME          NOT NULL,
  redeemed_at DATETIME          NULL,
  redeemed_ip VARCHAR(45)       NULL,
  is_revoked  TINYINT(1)        NOT NULL DEFAULT 0,
  created_by  INT UNSIGNED      NULL,
  created_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_grants_profile (profile_id),
  KEY idx_grants_expires (expires_at),
  KEY idx_grants_request (request_id),
  CONSTRAINT fk_grants_profile FOREIGN KEY (profile_id) REFERENCES birthday_profiles (id) ON DELETE CASCADE,
  CONSTRAINT fk_grants_request FOREIGN KEY (request_id) REFERENCES access_requests (id) ON DELETE SET NULL,
  CONSTRAINT fk_grants_admin   FOREIGN KEY (created_by)  REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 16. visitor_responses (gift / complaint) --------
DROP TABLE IF EXISTS visitor_responses;
CREATE TABLE visitor_responses (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  profile_id  INT UNSIGNED NOT NULL,
  visitor_uid VARCHAR(64)  NULL,
  type        ENUM('gift','complaint','reaction') NOT NULL,
  body        TEXT         NULL,
  category    VARCHAR(80)  NULL,
  reaction    VARCHAR(16)  NULL,
  status      ENUM('unread','read','archived') NOT NULL DEFAULT 'unread',
  ip_address  VARCHAR(45)  NULL,
  session_id  VARCHAR(128) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_responses_profile_type (profile_id, type, status),
  KEY idx_responses_created (created_at),
  CONSTRAINT fk_responses_profile FOREIGN KEY (profile_id) REFERENCES birthday_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 17. admin_audit_logs ----------------------------
DROP TABLE IF EXISTS admin_audit_logs;
CREATE TABLE admin_audit_logs (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id   INT UNSIGNED NULL,
  action     VARCHAR(80)  NOT NULL,
  entity     VARCHAR(60)  NOT NULL DEFAULT '',
  entity_id  INT UNSIGNED NULL,
  ip_address VARCHAR(45)  NULL,
  details    TEXT         NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_admin_time (admin_id, created_at),
  KEY idx_audit_action (action),
  CONSTRAINT fk_audit_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

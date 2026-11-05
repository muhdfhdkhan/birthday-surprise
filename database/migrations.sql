-- ============================================================
-- Birthday Surprise — incremental migrations (phases 3-6)
-- Run once against an existing install:
--   C:\xampp\mysql\bin\mysql.exe -u root -e "source X:/Vibe/Birthday/database/migrations.sql"
-- (Fresh installs skip this file — schema.sql already includes these columns.)
-- ============================================================

SET NAMES utf8mb4;
USE birthday_surprise;

-- Phase 5: gift & complaint form settings (+ optional sorry toggle)
ALTER TABLE birthday_content
  ADD COLUMN gift_categories TEXT NULL AFTER welcome_message,
  ADD COLUMN complaint_placeholder VARCHAR(255) NULL AFTER gift_categories,
  ADD COLUMN sorry_required TINYINT(1) NOT NULL DEFAULT 1 AFTER complaint_placeholder;

-- =====================================================================================
-- FGKIRS — foto do atleta (migration 008_athlete_photo), para o phpMyAdmin.
-- Pode rodar de novo sem erro. Cole o texto inteiro na aba SQL.
-- =====================================================================================

SET NAMES utf8mb4;

ALTER TABLE `athletes` ADD COLUMN IF NOT EXISTS `photo` VARCHAR(255) NULL;

INSERT IGNORE INTO schema_migrations (version) VALUES ('008_athlete_photo');

-- ---------------------------------------------------------------- conferência
SHOW COLUMNS FROM `athletes` LIKE 'photo';

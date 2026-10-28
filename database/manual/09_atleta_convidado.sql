-- =====================================================================================
-- FGKIRS — atleta convidado (migration 010_athlete_guest), para o phpMyAdmin.
-- Marca o atleta inscrito por um dojo mas que é de outro dojo ou clube, e guarda a origem.
-- Pode rodar de novo sem erro. Cole o texto inteiro na aba SQL.
-- Sem este arquivo o site funciona: só a opção "convidado" fica escondida no cadastro de atleta.
-- =====================================================================================

SET NAMES utf8mb4;

ALTER TABLE `athletes` ADD COLUMN IF NOT EXISTS `is_guest` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `athletes` ADD COLUMN IF NOT EXISTS `guest_origin` VARCHAR(120) NULL;

INSERT IGNORE INTO schema_migrations (version) VALUES ('010_athlete_guest');

-- ---------------------------------------------------------------- conferência
SHOW COLUMNS FROM `athletes` LIKE 'is_guest';
SHOW COLUMNS FROM `athletes` LIKE 'guest_origin';

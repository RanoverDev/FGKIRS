-- =====================================================================================
-- FGKIRS — Parte 1 de 4 — migration 001: FKs de inscrição sem CASCADE
--
-- Feito para o MariaDB 10.6 da KingHost (usa ADD ... IF NOT EXISTS / DROP ... IF EXISTS), com os nomes
-- reais das FKs do banco de produção. Só comandos simples: nada de PREPARE nem information_schema
-- (que travam em servidor compartilhado). Cada parte é idempotente: se algo interromper, rode a mesma
-- parte de novo.
--
-- Rode as quatro partes NESTA ORDEM (01a, 01b, 01c, 01d), uma de cada vez, no phpMyAdmin:
-- selecione o banco > Importar > escolha o arquivo > Executar. Faça o backup (Exportar) antes da 01a.
-- =====================================================================================

SET NAMES utf8mb4;

-- Migration 001: nenhuma exclusão apaga inscrição em cascata (ON DELETE CASCADE -> RESTRICT).
-- Continuam em CASCADE de propósito: championship_categories.championship_id, championship_entries.championship_athlete_id
-- e championship_team_members.*.

ALTER TABLE `championship_categories`
  DROP FOREIGN KEY IF EXISTS `championship_categories_ibfk_2`,
  ADD CONSTRAINT `fk_champcat_category` FOREIGN KEY IF NOT EXISTS (`category_id`) REFERENCES `competition_categories`(`id`) ON DELETE RESTRICT;

ALTER TABLE `championship_athletes`
  DROP FOREIGN KEY IF EXISTS `championship_athletes_ibfk_1`,
  DROP FOREIGN KEY IF EXISTS `championship_athletes_ibfk_2`,
  ADD CONSTRAINT `fk_champathletes_championship` FOREIGN KEY IF NOT EXISTS (`championship_id`) REFERENCES `championships`(`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_champathletes_dojo` FOREIGN KEY IF NOT EXISTS (`dojo_id`) REFERENCES `dojos`(`id`) ON DELETE RESTRICT;

ALTER TABLE `championship_entries`
  DROP FOREIGN KEY IF EXISTS `championship_entries_ibfk_2`,
  ADD CONSTRAINT `fk_entries_category` FOREIGN KEY IF NOT EXISTS (`category_id`) REFERENCES `competition_categories`(`id`) ON DELETE RESTRICT;

ALTER TABLE `championship_teams`
  DROP FOREIGN KEY IF EXISTS `championship_teams_ibfk_1`,
  DROP FOREIGN KEY IF EXISTS `championship_teams_ibfk_2`,
  DROP FOREIGN KEY IF EXISTS `championship_teams_ibfk_3`,
  ADD CONSTRAINT `fk_teams_championship` FOREIGN KEY IF NOT EXISTS (`championship_id`) REFERENCES `championships`(`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_teams_dojo` FOREIGN KEY IF NOT EXISTS (`dojo_id`) REFERENCES `dojos`(`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_teams_category` FOREIGN KEY IF NOT EXISTS (`category_id`) REFERENCES `competition_categories`(`id`) ON DELETE RESTRICT;

ALTER TABLE `championship_referees`
  DROP FOREIGN KEY IF EXISTS `championship_referees_ibfk_1`,
  DROP FOREIGN KEY IF EXISTS `championship_referees_ibfk_2`,
  ADD CONSTRAINT `fk_referees_championship` FOREIGN KEY IF NOT EXISTS (`championship_id`) REFERENCES `championships`(`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_referees_dojo` FOREIGN KEY IF NOT EXISTS (`dojo_id`) REFERENCES `dojos`(`id`) ON DELETE RESTRICT;

-- ---------------------------------------------------------------- conferência desta parte
SHOW CREATE TABLE championship_entries;
-- Esperado: `fk_entries_category` aparece SEM "ON DELETE CASCADE" (RESTRICT é o padrão e o MariaDB não o escreve)
-- e `championship_entries_ibfk_1` (championship_athlete_id) continua com ON DELETE CASCADE.

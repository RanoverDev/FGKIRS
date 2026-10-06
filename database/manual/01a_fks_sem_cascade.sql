-- =====================================================================================
-- FGKIRS — Parte 1 de 4 — migration 001: FKs de inscrição sem CASCADE
--
-- Rode as quatro partes NESTA ORDEM (01a, 01b, 01c, 01d), uma de cada vez, no phpMyAdmin:
-- selecione o banco > Importar > escolha o arquivo > Executar. Cada parte é idempotente: se algo
-- interromper, pode rodar a mesma parte de novo. Faça o backup (Exportar) antes da 01a.
-- =====================================================================================

SET NAMES utf8mb4;
SET @db = DATABASE();

-- ---------------------------------------------------------------- 001: FKs sem CASCADE em inscrição
-- championship_categories.category_id
SET @already = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_categories' AND k.COLUMN_NAME='category_id' AND rc.REFERENCED_TABLE_NAME='competition_categories' AND rc.DELETE_RULE='RESTRICT');
SET @drops = (SELECT GROUP_CONCAT(CONCAT('DROP FOREIGN KEY `', rc.CONSTRAINT_NAME, '`') SEPARATOR ', ') FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_categories' AND k.COLUMN_NAME='category_id' AND rc.REFERENCED_TABLE_NAME='competition_categories');
SET @sql = NULL;
SET @sql = IF(@already > 0, 'SELECT 1', CONCAT('ALTER TABLE `championship_categories` ', IFNULL(CONCAT(@drops, ', '), ''), 'ADD CONSTRAINT `fk_champcat_category` FOREIGN KEY (`category_id`) REFERENCES `competition_categories`(id) ON DELETE RESTRICT'));
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

-- championship_athletes.championship_id
SET @already = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_athletes' AND k.COLUMN_NAME='championship_id' AND rc.REFERENCED_TABLE_NAME='championships' AND rc.DELETE_RULE='RESTRICT');
SET @drops = (SELECT GROUP_CONCAT(CONCAT('DROP FOREIGN KEY `', rc.CONSTRAINT_NAME, '`') SEPARATOR ', ') FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_athletes' AND k.COLUMN_NAME='championship_id' AND rc.REFERENCED_TABLE_NAME='championships');
SET @sql = NULL;
SET @sql = IF(@already > 0, 'SELECT 1', CONCAT('ALTER TABLE `championship_athletes` ', IFNULL(CONCAT(@drops, ', '), ''), 'ADD CONSTRAINT `fk_champathletes_championship` FOREIGN KEY (`championship_id`) REFERENCES `championships`(id) ON DELETE RESTRICT'));
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

-- championship_athletes.dojo_id
SET @already = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_athletes' AND k.COLUMN_NAME='dojo_id' AND rc.REFERENCED_TABLE_NAME='dojos' AND rc.DELETE_RULE='RESTRICT');
SET @drops = (SELECT GROUP_CONCAT(CONCAT('DROP FOREIGN KEY `', rc.CONSTRAINT_NAME, '`') SEPARATOR ', ') FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_athletes' AND k.COLUMN_NAME='dojo_id' AND rc.REFERENCED_TABLE_NAME='dojos');
SET @sql = NULL;
SET @sql = IF(@already > 0, 'SELECT 1', CONCAT('ALTER TABLE `championship_athletes` ', IFNULL(CONCAT(@drops, ', '), ''), 'ADD CONSTRAINT `fk_champathletes_dojo` FOREIGN KEY (`dojo_id`) REFERENCES `dojos`(id) ON DELETE RESTRICT'));
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

-- championship_entries.category_id
SET @already = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_entries' AND k.COLUMN_NAME='category_id' AND rc.REFERENCED_TABLE_NAME='competition_categories' AND rc.DELETE_RULE='RESTRICT');
SET @drops = (SELECT GROUP_CONCAT(CONCAT('DROP FOREIGN KEY `', rc.CONSTRAINT_NAME, '`') SEPARATOR ', ') FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_entries' AND k.COLUMN_NAME='category_id' AND rc.REFERENCED_TABLE_NAME='competition_categories');
SET @sql = NULL;
SET @sql = IF(@already > 0, 'SELECT 1', CONCAT('ALTER TABLE `championship_entries` ', IFNULL(CONCAT(@drops, ', '), ''), 'ADD CONSTRAINT `fk_entries_category` FOREIGN KEY (`category_id`) REFERENCES `competition_categories`(id) ON DELETE RESTRICT'));
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

-- championship_teams.championship_id
SET @already = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_teams' AND k.COLUMN_NAME='championship_id' AND rc.REFERENCED_TABLE_NAME='championships' AND rc.DELETE_RULE='RESTRICT');
SET @drops = (SELECT GROUP_CONCAT(CONCAT('DROP FOREIGN KEY `', rc.CONSTRAINT_NAME, '`') SEPARATOR ', ') FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_teams' AND k.COLUMN_NAME='championship_id' AND rc.REFERENCED_TABLE_NAME='championships');
SET @sql = NULL;
SET @sql = IF(@already > 0, 'SELECT 1', CONCAT('ALTER TABLE `championship_teams` ', IFNULL(CONCAT(@drops, ', '), ''), 'ADD CONSTRAINT `fk_teams_championship` FOREIGN KEY (`championship_id`) REFERENCES `championships`(id) ON DELETE RESTRICT'));
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

-- championship_teams.dojo_id
SET @already = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_teams' AND k.COLUMN_NAME='dojo_id' AND rc.REFERENCED_TABLE_NAME='dojos' AND rc.DELETE_RULE='RESTRICT');
SET @drops = (SELECT GROUP_CONCAT(CONCAT('DROP FOREIGN KEY `', rc.CONSTRAINT_NAME, '`') SEPARATOR ', ') FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_teams' AND k.COLUMN_NAME='dojo_id' AND rc.REFERENCED_TABLE_NAME='dojos');
SET @sql = NULL;
SET @sql = IF(@already > 0, 'SELECT 1', CONCAT('ALTER TABLE `championship_teams` ', IFNULL(CONCAT(@drops, ', '), ''), 'ADD CONSTRAINT `fk_teams_dojo` FOREIGN KEY (`dojo_id`) REFERENCES `dojos`(id) ON DELETE RESTRICT'));
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

-- championship_teams.category_id
SET @already = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_teams' AND k.COLUMN_NAME='category_id' AND rc.REFERENCED_TABLE_NAME='competition_categories' AND rc.DELETE_RULE='RESTRICT');
SET @drops = (SELECT GROUP_CONCAT(CONCAT('DROP FOREIGN KEY `', rc.CONSTRAINT_NAME, '`') SEPARATOR ', ') FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_teams' AND k.COLUMN_NAME='category_id' AND rc.REFERENCED_TABLE_NAME='competition_categories');
SET @sql = NULL;
SET @sql = IF(@already > 0, 'SELECT 1', CONCAT('ALTER TABLE `championship_teams` ', IFNULL(CONCAT(@drops, ', '), ''), 'ADD CONSTRAINT `fk_teams_category` FOREIGN KEY (`category_id`) REFERENCES `competition_categories`(id) ON DELETE RESTRICT'));
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

-- championship_referees.championship_id
SET @already = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_referees' AND k.COLUMN_NAME='championship_id' AND rc.REFERENCED_TABLE_NAME='championships' AND rc.DELETE_RULE='RESTRICT');
SET @drops = (SELECT GROUP_CONCAT(CONCAT('DROP FOREIGN KEY `', rc.CONSTRAINT_NAME, '`') SEPARATOR ', ') FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_referees' AND k.COLUMN_NAME='championship_id' AND rc.REFERENCED_TABLE_NAME='championships');
SET @sql = NULL;
SET @sql = IF(@already > 0, 'SELECT 1', CONCAT('ALTER TABLE `championship_referees` ', IFNULL(CONCAT(@drops, ', '), ''), 'ADD CONSTRAINT `fk_referees_championship` FOREIGN KEY (`championship_id`) REFERENCES `championships`(id) ON DELETE RESTRICT'));
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

-- championship_referees.dojo_id
SET @already = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_referees' AND k.COLUMN_NAME='dojo_id' AND rc.REFERENCED_TABLE_NAME='dojos' AND rc.DELETE_RULE='RESTRICT');
SET @drops = (SELECT GROUP_CONCAT(CONCAT('DROP FOREIGN KEY `', rc.CONSTRAINT_NAME, '`') SEPARATOR ', ') FROM information_schema.REFERENTIAL_CONSTRAINTS rc JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME AND k.TABLE_NAME=rc.TABLE_NAME WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME='championship_referees' AND k.COLUMN_NAME='dojo_id' AND rc.REFERENCED_TABLE_NAME='dojos');
SET @sql = NULL;
SET @sql = IF(@already > 0, 'SELECT 1', CONCAT('ALTER TABLE `championship_referees` ', IFNULL(CONCAT(@drops, ', '), ''), 'ADD CONSTRAINT `fk_referees_dojo` FOREIGN KEY (`dojo_id`) REFERENCES `dojos`(id) ON DELETE RESTRICT'));
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

-- ---------------------------------------------------------------- conferência desta parte
SELECT rc.TABLE_NAME AS tabela, rc.CONSTRAINT_NAME AS constraint_fk, rc.DELETE_RULE AS regra
FROM information_schema.REFERENTIAL_CONSTRAINTS rc
WHERE rc.CONSTRAINT_SCHEMA=@db AND rc.TABLE_NAME LIKE 'championship%' AND rc.DELETE_RULE='CASCADE';
-- Esperado: só 4 linhas CASCADE (championship_categories_ibfk_1, championship_entries_ibfk_1 e as 2 de championship_team_members)

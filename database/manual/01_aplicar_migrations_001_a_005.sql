-- =====================================================================================
-- FGKIRS — migrations 001 a 005 em SQL puro, para importar no phpMyAdmin.
--
-- Equivale a `php migrate.php` (database/migrations/001 a 005). É idempotente: cada passo confere
-- o que já existe, então pode ser importado de novo depois de uma falha no meio. No fim registra as
-- cinco migrations em schema_migrations, para um futuro `php migrate.php` não repetir.
--
-- ANTES: exporte o banco inteiro (phpMyAdmin > Exportar > Rápido, formato SQL) e guarde fora do servidor.
-- COMO: phpMyAdmin > selecione o banco > Importar > escolha este arquivo > Executar.
--       Se parar com erro, leia a mensagem, não apague nada e me envie o texto.
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

-- ---------------------------------------------------------------- 002: atletas e nível de faixa
CREATE TABLE IF NOT EXISTS athletes (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  dojo_id             INT NOT NULL,
  user_id             INT NULL,
  full_name           VARCHAR(255) NOT NULL,
  birth_date          DATE NOT NULL,
  gender              ENUM('M','F') NOT NULL,
  document            VARCHAR(14) NULL,
  style_id            INT NULL,
  graduation_id       INT NULL,
  weight_kg           DECIMAL(5,2) NULL,
  height_cm           SMALLINT UNSIGNED NULL,
  is_para_karate      TINYINT(1) NOT NULL DEFAULT 0,
  fgkirs_registration VARCHAR(30) NULL,
  cbki_registration   VARCHAR(30) NULL,
  status              ENUM('active','inactive','pending') NOT NULL DEFAULT 'active',
  created_by          INT NOT NULL,
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_document (document),
  UNIQUE KEY uq_user (user_id),
  INDEX idx_dojo_name (dojo_id, full_name),
  CONSTRAINT fk_athletes_dojo       FOREIGN KEY (dojo_id)       REFERENCES dojos(id)               ON DELETE RESTRICT,
  CONSTRAINT fk_athletes_user       FOREIGN KEY (user_id)       REFERENCES users(id)               ON DELETE SET NULL,
  CONSTRAINT fk_athletes_style      FOREIGN KEY (style_id)      REFERENCES martial_arts_styles(id) ON DELETE SET NULL,
  CONSTRAINT fk_athletes_graduation FOREIGN KEY (graduation_id) REFERENCES graduations(id)         ON DELETE SET NULL,
  CONSTRAINT fk_athletes_created_by FOREIGN KEY (created_by)    REFERENCES users(id)               ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql = NULL;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='graduations' AND COLUMN_NAME='level')=0,
  'ALTER TABLE `graduations` ADD COLUMN `level` TINYINT UNSIGNED NULL AFTER `order_rank`, ADD INDEX `idx_level` (`level`)', 'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

-- Sugestão de nível para o presidente revisar (faixa preta nunca abaixo de 11). is_black_belt pode não existir.
SET @sql = NULL;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='graduations' AND COLUMN_NAME='is_black_belt')>0,
  'UPDATE graduations SET level = LEAST(20, GREATEST(order_rank + 1, IF(is_black_belt = 1, 11, 1))) WHERE level IS NULL',
  'UPDATE graduations SET level = LEAST(20, GREATEST(order_rank + 1, 1)) WHERE level IS NULL');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

SET @sql = NULL;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='championship_athletes' AND COLUMN_NAME='athlete_id')=0,
  'ALTER TABLE `championship_athletes` ADD COLUMN `athlete_id` INT NULL AFTER `user_id`, ADD INDEX `idx_athlete` (`athlete_id`)', 'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

-- ---------------------------------------------------------------- 003: regulamento
CREATE TABLE IF NOT EXISTS rulesets (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  age_policy  ENUM('event_date','year_end') NOT NULL DEFAULT 'event_date',
  is_default  TINYINT(1) NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS age_divisions (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  ruleset_id  INT NOT NULL,
  code        VARCHAR(20) NOT NULL,
  name        VARCHAR(60) NOT NULL,
  age_min     TINYINT UNSIGNED NULL,
  age_max     TINYINT UNSIGNED NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_code (ruleset_id, code),
  CONSTRAINT fk_age_divisions_ruleset FOREIGN KEY (ruleset_id) REFERENCES rulesets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS belt_divisions (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  ruleset_id  INT NOT NULL,
  code        VARCHAR(20) NOT NULL,
  name        VARCHAR(60) NOT NULL,
  level_min   TINYINT UNSIGNED NOT NULL,
  level_max   TINYINT UNSIGNED NOT NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_code (ruleset_id, code),
  CONSTRAINT fk_belt_divisions_ruleset FOREIGN KEY (ruleset_id) REFERENCES rulesets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS weight_classes (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  ruleset_id       INT NOT NULL,
  age_division_id  INT NOT NULL,
  gender           ENUM('M','F') NOT NULL,
  name             VARCHAR(60) NOT NULL,
  weight_min       DECIMAL(5,2) NULL,
  weight_max       DECIMAL(5,2) NULL,
  sort_order       INT NOT NULL DEFAULT 0,
  INDEX idx_lookup (age_division_id, gender),
  CONSTRAINT fk_weight_classes_ruleset FOREIGN KEY (ruleset_id)      REFERENCES rulesets(id)      ON DELETE CASCADE,
  CONSTRAINT fk_weight_classes_age     FOREIGN KEY (age_division_id) REFERENCES age_divisions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ruleset_disciplines (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  ruleset_id       INT NOT NULL,
  code             VARCHAR(20) NOT NULL,
  name             VARCHAR(80) NOT NULL,
  modality         ENUM('kata','kumite') NOT NULL,
  format           ENUM('individual','team') NOT NULL,
  split_gender     TINYINT(1) NOT NULL DEFAULT 1,
  split_belt       TINYINT(1) NOT NULL DEFAULT 1,
  split_weight     TINYINT(1) NOT NULL DEFAULT 0,
  team_size        TINYINT UNSIGNED NULL,
  team_reserves    TINYINT UNSIGNED NULL,
  sort_order       INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_code (ruleset_id, code),
  CONSTRAINT fk_disciplines_ruleset FOREIGN KEY (ruleset_id) REFERENCES rulesets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ruleset_discipline_ages (
  discipline_id    INT NOT NULL,
  age_division_id  INT NOT NULL,
  PRIMARY KEY (discipline_id, age_division_id),
  CONSTRAINT fk_disc_ages_discipline FOREIGN KEY (discipline_id)   REFERENCES ruleset_disciplines(id) ON DELETE CASCADE,
  CONSTRAINT fk_disc_ages_age        FOREIGN KEY (age_division_id) REFERENCES age_divisions(id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- 004: regulamento atual (só se ainda não existir)
SET @seed = ((SELECT COUNT(*) FROM rulesets WHERE name = 'Regulamento FGKIRS (atual)') = 0);

INSERT INTO rulesets (name, age_policy, is_default, is_active)
SELECT 'Regulamento FGKIRS (atual)', 'event_date', 1, 1 FROM DUAL WHERE @seed;

SET @rs = (SELECT MIN(id) FROM rulesets WHERE name = 'Regulamento FGKIRS (atual)');

INSERT INTO age_divisions (ruleset_id, code, name, age_min, age_max, sort_order)
SELECT * FROM (
  SELECT @rs AS r, 'MIR' AS c, 'Mirim' AS n, 7 AS a, 10 AS b, 1 AS o
  UNION ALL SELECT @rs, 'INF',   'Infantil',        11,   12,   2
  UNION ALL SELECT @rs, 'IJU',   'Infanto Juvenil', 13,   14,   3
  UNION ALL SELECT @rs, 'JUV',   'Juvenil',         15,   17,   4
  UNION ALL SELECT @rs, 'ADU',   'Adulto',          18,   35,   5
  UNION ALL SELECT @rs, 'MAS',   'Master',          36,   NULL, 6
  UNION ALL SELECT @rs, 'EQ-14', 'Até 14 anos',     NULL, 14,   7
  UNION ALL SELECT @rs, 'EQ-15', '15 anos acima',   15,   NULL, 8
) x WHERE @seed;

INSERT INTO belt_divisions (ruleset_id, code, name, level_min, level_max, sort_order)
SELECT * FROM (
  SELECT @rs AS r, 'COL' AS c, 'Faixa Colorida' AS n, 1 AS a, 10 AS b, 1 AS o
  UNION ALL SELECT @rs, 'PRE', 'Faixa Preta', 11, 20, 2
) x WHERE @seed;

INSERT INTO weight_classes (ruleset_id, age_division_id, gender, name, weight_min, weight_max, sort_order)
SELECT @rs, a.id, g.gender, w.name, w.wmin, w.wmax, w.sort_order
FROM age_divisions a
CROSS JOIN (SELECT 'M' AS gender UNION ALL SELECT 'F') g
CROSS JOIN (
  SELECT 'até 50kg (provisório)' AS name, NULL AS wmin, 50.00 AS wmax, 1 AS sort_order
  UNION ALL
  SELECT 'acima de 50kg (provisório)', 50.00, NULL, 2
) w
WHERE @seed AND a.ruleset_id = @rs AND a.code NOT LIKE 'EQ-%';

INSERT INTO ruleset_disciplines (ruleset_id, code, name, modality, format, split_gender, split_belt, split_weight, team_size, sort_order)
SELECT * FROM (
  SELECT @rs AS r, 'KTI' AS c, 'Kata Individual' AS n, 'kata' AS m, 'individual' AS f, 1 AS sg, 1 AS sb, 0 AS sw, NULL AS ts, 1 AS o
  UNION ALL SELECT @rs, 'KMI', 'Kumite Individual',           'kumite', 'individual', 1, 1, 1, NULL, 2
  UNION ALL SELECT @rs, 'KTE', 'Kata Equipe',                 'kata',   'team',       1, 0, 0, 3,    3
  UNION ALL SELECT @rs, 'KME', 'Kumite Equipe (Revezamento)', 'kumite', 'team',       1, 0, 0, 3,    4
) x WHERE @seed;

INSERT INTO ruleset_discipline_ages (discipline_id, age_division_id)
SELECT d.id, a.id
FROM ruleset_disciplines d
JOIN age_divisions a ON a.ruleset_id = d.ruleset_id
WHERE @seed AND d.ruleset_id = @rs
  AND ((d.code = 'KTE' AND a.code LIKE 'EQ-%') OR (d.code <> 'KTE' AND a.code NOT LIKE 'EQ-%'));

-- ---------------------------------------------------------------- 005: categorias do evento e nova inscrição
SET @sql = NULL;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='championships' AND COLUMN_NAME='registration_model')=0,
  'ALTER TABLE `championships` ADD COLUMN `registration_model` ENUM(''legacy'',''v2'') NOT NULL DEFAULT ''legacy''', 'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

SET @sql = NULL;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='championships' AND COLUMN_NAME='ruleset_id')=0,
  'ALTER TABLE `championships` ADD COLUMN `ruleset_id` INT NULL', 'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

SET @sql = NULL;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='championships' AND COLUMN_NAME='age_policy')=0,
  'ALTER TABLE `championships` ADD COLUMN `age_policy` ENUM(''event_date'',''year_end'') NOT NULL DEFAULT ''event_date''', 'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

SET @sql = NULL;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='championships' AND COLUMN_NAME='max_individual_entries')=0,
  'ALTER TABLE `championships` ADD COLUMN `max_individual_entries` TINYINT UNSIGNED NOT NULL DEFAULT 2', 'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

SET @sql = NULL;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='championships' AND COLUMN_NAME='fee_first_entry')=0,
  'ALTER TABLE `championships` ADD COLUMN `fee_first_entry` DECIMAL(8,2) NULL', 'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

SET @sql = NULL;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='championships' AND COLUMN_NAME='fee_extra_entry')=0,
  'ALTER TABLE `championships` ADD COLUMN `fee_extra_entry` DECIMAL(8,2) NULL', 'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

SET @sql = NULL;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='championships' AND COLUMN_NAME='weigh_in_tolerance_kg')=0,
  'ALTER TABLE `championships` ADD COLUMN `weigh_in_tolerance_kg` DECIMAL(4,2) NOT NULL DEFAULT 0.00', 'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

SET @sql = NULL;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db AND TABLE_NAME='championships' AND CONSTRAINT_NAME='fk_championships_ruleset')=0,
  'ALTER TABLE `championships` ADD CONSTRAINT `fk_championships_ruleset` FOREIGN KEY (`ruleset_id`) REFERENCES `rulesets`(`id`) ON DELETE SET NULL', 'SELECT 1');
PREPARE s FROM @sql;
EXECUTE s;
DEALLOCATE PREPARE s;

CREATE TABLE IF NOT EXISTS event_categories (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    championship_id  INT NOT NULL,
    code             VARCHAR(40) NOT NULL,
    name             VARCHAR(200) NOT NULL,
    short_name       VARCHAR(160) NOT NULL DEFAULT '',
    discipline_code  VARCHAR(20) NOT NULL DEFAULT '',
    discipline_name  VARCHAR(80) NOT NULL DEFAULT '',
    modality         ENUM('kata','kumite') NOT NULL,
    format           ENUM('individual','team') NOT NULL,
    gender           ENUM('M','F','X') NOT NULL,
    age_min          TINYINT UNSIGNED NULL,
    age_max          TINYINT UNSIGNED NULL,
    belt_level_min   TINYINT UNSIGNED NULL,
    belt_level_max   TINYINT UNSIGNED NULL,
    weight_min       DECIMAL(5,2) NULL,
    weight_max       DECIMAL(5,2) NULL,
    team_size        TINYINT UNSIGNED NULL,
    team_reserves    TINYINT UNSIGNED NULL,
    is_para_karate   TINYINT(1) NOT NULL DEFAULT 0,
    max_entries      SMALLINT UNSIGNED NULL,
    status           ENUM('open','closed','merged','cancelled') NOT NULL DEFAULT 'open',
    merged_into_id   INT NULL,
    discipline_id    INT NULL,
    age_division_id  INT NULL,
    belt_division_id INT NULL,
    weight_class_id  INT NULL,
    sort_order       INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_code (championship_id, code),
    INDEX idx_match (championship_id, format, modality, gender, status),
    CONSTRAINT fk_event_categories_championship FOREIGN KEY (championship_id) REFERENCES championships(id)    ON DELETE RESTRICT,
    CONSTRAINT fk_event_categories_merged       FOREIGN KEY (merged_into_id)  REFERENCES event_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS championship_registrations (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    championship_id  INT NOT NULL,
    athlete_id       INT NOT NULL,
    dojo_id          INT NOT NULL,
    full_name        VARCHAR(255) NOT NULL,
    gender           ENUM('M','F') NOT NULL,
    birth_date       DATE NOT NULL,
    age_at_event     TINYINT UNSIGNED NOT NULL,
    graduation_id    INT NULL,
    belt_level       TINYINT UNSIGNED NULL,
    declared_weight  DECIMAL(5,2) NULL,
    official_weight  DECIMAL(5,2) NULL,
    is_para_karate   TINYINT(1) NOT NULL DEFAULT 0,
    status           ENUM('draft','submitted','confirmed','cancelled') NOT NULL DEFAULT 'draft',
    fee_total        DECIMAL(8,2) NULL,
    paid_at          DATETIME NULL,
    submitted_at     DATETIME NULL,
    registered_by    INT NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_athlete_event (championship_id, athlete_id),
    INDEX idx_event_dojo_status (championship_id, dojo_id, status),
    CONSTRAINT fk_registrations_championship FOREIGN KEY (championship_id) REFERENCES championships(id) ON DELETE RESTRICT,
    CONSTRAINT fk_registrations_athlete      FOREIGN KEY (athlete_id)      REFERENCES athletes(id)      ON DELETE RESTRICT,
    CONSTRAINT fk_registrations_dojo         FOREIGN KEY (dojo_id)         REFERENCES dojos(id)         ON DELETE RESTRICT,
    CONSTRAINT fk_registrations_user         FOREIGN KEY (registered_by)   REFERENCES users(id)         ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS registration_entries (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    registration_id   INT NOT NULL,
    event_category_id INT NOT NULL,
    status            ENUM('active','cancelled','moved') NOT NULL DEFAULT 'active',
    is_override       TINYINT(1) NOT NULL DEFAULT 0,
    override_reason   VARCHAR(255) NULL,
    override_by       INT NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_entry (registration_id, event_category_id),
    INDEX idx_category_status (event_category_id, status),
    CONSTRAINT fk_entries_registration   FOREIGN KEY (registration_id)   REFERENCES championship_registrations(id) ON DELETE RESTRICT,
    CONSTRAINT fk_entries_event_category FOREIGN KEY (event_category_id) REFERENCES event_categories(id)           ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_teams (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    championship_id   INT NOT NULL,
    dojo_id           INT NOT NULL,
    event_category_id INT NOT NULL,
    name              VARCHAR(120) NULL,
    status            ENUM('draft','submitted','confirmed','cancelled') NOT NULL DEFAULT 'draft',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_dojo (championship_id, dojo_id),
    CONSTRAINT fk_event_teams_championship FOREIGN KEY (championship_id)   REFERENCES championships(id)    ON DELETE RESTRICT,
    CONSTRAINT fk_event_teams_dojo         FOREIGN KEY (dojo_id)           REFERENCES dojos(id)            ON DELETE RESTRICT,
    CONSTRAINT fk_event_teams_category     FOREIGN KEY (event_category_id) REFERENCES event_categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_team_members (
    team_id          INT NOT NULL,
    registration_id  INT NOT NULL,
    role             ENUM('starter','reserve') NOT NULL DEFAULT 'starter',
    position         TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (team_id, registration_id),
    CONSTRAINT fk_team_members_team         FOREIGN KEY (team_id)         REFERENCES event_teams(id)                ON DELETE CASCADE,
    CONSTRAINT fk_team_members_registration FOREIGN KEY (registration_id) REFERENCES championship_registrations(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS registration_log (
    id               BIGINT AUTO_INCREMENT PRIMARY KEY,
    registration_id  INT NOT NULL,
    action           VARCHAR(40) NOT NULL,
    details          JSON NULL,
    user_id          INT NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_registration (registration_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- registro das migrations aplicadas
CREATE TABLE IF NOT EXISTS schema_migrations (
  version    VARCHAR(100) PRIMARY KEY,
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (version) VALUES
  ('001_fk_restrict'), ('002_athletes'), ('003_rulesets'), ('004_seed_ruleset_fgkirs'), ('005_event_registration_v2');

-- ---------------------------------------------------------------- conferência (deve listar tudo "ok")
SELECT 'athletes' AS item, COUNT(*) AS existe FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='athletes'
UNION ALL SELECT 'rulesets', COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rulesets'
UNION ALL SELECT 'event_categories', COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='event_categories'
UNION ALL SELECT 'championship_registrations', COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='championship_registrations'
UNION ALL SELECT 'graduations.level', COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='graduations' AND COLUMN_NAME='level'
UNION ALL SELECT 'championships.registration_model', COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='championships' AND COLUMN_NAME='registration_model'
UNION ALL SELECT 'regulamento padrão (1 linha)', COUNT(*) FROM rulesets WHERE is_default = 1
UNION ALL SELECT 'divisões etárias do regulamento padrão (8)', (SELECT COUNT(*) FROM age_divisions WHERE ruleset_id=@rs);

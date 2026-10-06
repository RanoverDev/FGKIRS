-- =====================================================================================
-- FGKIRS — Parte 2 de 4 — migration 002: tabela athletes e nível de faixa
--
-- Rode as quatro partes NESTA ORDEM (01a, 01b, 01c, 01d), uma de cada vez, no phpMyAdmin:
-- selecione o banco > Importar > escolha o arquivo > Executar. Cada parte é idempotente: se algo
-- interromper, pode rodar a mesma parte de novo. Faça o backup (Exportar) antes da 01a.
-- =====================================================================================

SET NAMES utf8mb4;
SET @db = DATABASE();

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

-- ---------------------------------------------------------------- conferência desta parte
SELECT COUNT(*) AS athletes_existe FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='athletes';
SELECT id, belt_name, order_rank, level FROM graduations ORDER BY style_id, order_rank;
-- Esperado: athletes_existe = 1 e todas as faixas com um nível preenchido

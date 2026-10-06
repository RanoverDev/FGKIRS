-- =====================================================================================
-- FGKIRS — Parte 2 de 4 — migration 002: tabela athletes e nível de faixa
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

-- Migration 002: cadastro permanente de atletas e nível comum de faixa entre estilos.

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

ALTER TABLE `graduations`
  ADD COLUMN IF NOT EXISTS `level` TINYINT UNSIGNED NULL AFTER `order_rank`,
  ADD INDEX IF NOT EXISTS `idx_level` (`level`);

-- Sugestão de nível para o presidente revisar (faixa preta nunca abaixo de 11). Só preenche o que está vazio.
UPDATE graduations SET level = LEAST(20, GREATEST(order_rank + 1, IF(is_black_belt = 1, 11, 1))) WHERE level IS NULL;

ALTER TABLE `championship_athletes`
  ADD COLUMN IF NOT EXISTS `athlete_id` INT NULL AFTER `user_id`,
  ADD INDEX IF NOT EXISTS `idx_athlete` (`athlete_id`);

-- ---------------------------------------------------------------- conferência desta parte
SELECT COUNT(*) AS atletas_cadastrados FROM athletes;
SELECT id, belt_name, order_rank, is_black_belt, level FROM graduations ORDER BY style_id, order_rank;
-- Esperado: atletas_cadastrados = 0 (ainda vazio) e todas as faixas com um nível preenchido

-- =====================================================================================
-- FGKIRS — Parte 3 de 4 — migrations 003 e 004: regulamento e regulamento atual
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

SET NAMES utf8mb4;

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

-- ---------------------------------------------------------------- conferência desta parte
SELECT r.name, (SELECT COUNT(*) FROM age_divisions WHERE ruleset_id=r.id) AS idades, (SELECT COUNT(*) FROM weight_classes WHERE ruleset_id=r.id) AS pesos, (SELECT COUNT(*) FROM ruleset_disciplines WHERE ruleset_id=r.id) AS disciplinas FROM rulesets r;
-- Esperado: 1 regulamento com 8 idades, 24 pesos e 4 disciplinas

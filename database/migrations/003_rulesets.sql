-- Fase 3: regulamento (divisões de idade, faixa e peso, e disciplinas).
-- CASCADE aqui é seguro: são tabelas de regra, sem inscrição, resultado ou dinheiro.
-- As categorias de um evento (fase 4) copiam os limites, então mexer no regulamento não altera evento criado.

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

-- weight_min é exclusivo e weight_max inclusivo, para "até 50" e "acima de 50" não deixarem vão nem sobreposição
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

-- Divisões etárias que cada disciplina usa (ex.: kata equipe só roda em "Até 14" e "15 acima")
CREATE TABLE IF NOT EXISTS ruleset_discipline_ages (
  discipline_id    INT NOT NULL,
  age_division_id  INT NOT NULL,
  PRIMARY KEY (discipline_id, age_division_id),
  CONSTRAINT fk_disc_ages_discipline FOREIGN KEY (discipline_id)   REFERENCES ruleset_disciplines(id) ON DELETE CASCADE,
  CONSTRAINT fk_disc_ages_age        FOREIGN KEY (age_division_id) REFERENCES age_divisions(id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

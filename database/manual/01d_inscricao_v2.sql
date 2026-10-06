-- =====================================================================================
-- FGKIRS — Parte 4 de 4 — migration 005: categorias do evento e nova inscrição (+ registro das migrations)
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

-- Migration 005: categorias do evento, inscrição com status e taxa, equipes e auditoria.
-- Eventos que já existem continuam 'legacy' (modelo antigo); só os criados daqui em diante nascem 'v2'.

ALTER TABLE `championships`
  ADD COLUMN IF NOT EXISTS `registration_model` ENUM('legacy','v2') NOT NULL DEFAULT 'legacy',
  ADD COLUMN IF NOT EXISTS `ruleset_id` INT NULL,
  ADD COLUMN IF NOT EXISTS `age_policy` ENUM('event_date','year_end') NOT NULL DEFAULT 'event_date',
  ADD COLUMN IF NOT EXISTS `max_individual_entries` TINYINT UNSIGNED NOT NULL DEFAULT 2,
  ADD COLUMN IF NOT EXISTS `fee_first_entry` DECIMAL(8,2) NULL,
  ADD COLUMN IF NOT EXISTS `fee_extra_entry` DECIMAL(8,2) NULL,
  ADD COLUMN IF NOT EXISTS `weigh_in_tolerance_kg` DECIMAL(4,2) NOT NULL DEFAULT 0.00;

ALTER TABLE `championships`
  ADD CONSTRAINT `fk_championships_ruleset` FOREIGN KEY IF NOT EXISTS (`ruleset_id`) REFERENCES `rulesets`(`id`) ON DELETE SET NULL;

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

-- ---------------------------------------------------------------- conferência (as consultas só funcionam se tudo existir)
SELECT version AS migration_aplicada FROM schema_migrations ORDER BY version;
SELECT registration_model, COUNT(*) AS eventos FROM championships GROUP BY registration_model;
SELECT (SELECT COUNT(*) FROM event_categories) AS categorias_de_evento,
       (SELECT COUNT(*) FROM championship_registrations) AS inscricoes_v2,
       (SELECT COUNT(*) FROM rulesets WHERE is_default = 1) AS regulamento_padrao,
       (SELECT COUNT(*) FROM age_divisions) AS divisoes_etarias;
-- Esperado: 5 migrations listadas; os eventos que já existiam como 'legacy'; 0, 0, 1 e 8 na última consulta

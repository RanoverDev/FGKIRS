-- =====================================================================================
-- FGKIRS — certificado de graduação (migration 007_certificates), para o phpMyAdmin.
-- Cria a coluna da arte de fundo no estilo e a tabela do histórico de certificados emitidos.
-- Pode rodar de novo sem erro. Cole o texto inteiro na aba SQL.
-- =====================================================================================

SET NAMES utf8mb4;

ALTER TABLE `martial_arts_styles` ADD COLUMN IF NOT EXISTS `certificate_background` VARCHAR(100) NULL;

CREATE TABLE IF NOT EXISTS `athlete_certificates` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `athlete_id`       INT NOT NULL,
    `style_id`         INT NOT NULL,
    `graduation_id`    INT NULL,
    `graduation_label` VARCHAR(60) NOT NULL,
    `graduation_date`  DATE NOT NULL,
    `body_html`        TEXT NOT NULL,
    `issued_by`        INT NOT NULL,
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_athlete_date` (`athlete_id`, `graduation_date`),
    CONSTRAINT `fk_certificates_athlete`    FOREIGN KEY (`athlete_id`)    REFERENCES `athletes` (`id`)            ON DELETE RESTRICT,
    CONSTRAINT `fk_certificates_style`      FOREIGN KEY (`style_id`)      REFERENCES `martial_arts_styles` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_certificates_graduation` FOREIGN KEY (`graduation_id`) REFERENCES `graduations` (`id`)         ON DELETE SET NULL,
    CONSTRAINT `fk_certificates_issued_by`  FOREIGN KEY (`issued_by`)     REFERENCES `users` (`id`)               ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (version) VALUES ('007_certificates');

-- ---------------------------------------------------------------- conferência
SHOW COLUMNS FROM `martial_arts_styles` LIKE 'certificate_background';
SHOW TABLES LIKE 'athlete_certificates';

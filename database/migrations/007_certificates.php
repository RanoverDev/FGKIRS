<?php

/**
 * Certificado de graduação: arte de fundo por estilo e histórico dos certificados emitidos.
 * Idempotente (o MySQL não faz rollback de DDL).
 */

return function (PDO $pdo): void {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'martial_arts_styles' AND COLUMN_NAME = 'certificate_background'"
    );
    $stmt->execute();

    if (!$stmt->fetchColumn()) {
        $pdo->exec("ALTER TABLE martial_arts_styles ADD COLUMN certificate_background VARCHAR(100) NULL");
        echo "\n  martial_arts_styles.certificate_background criada";
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS athlete_certificates (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            athlete_id       INT NOT NULL,
            style_id         INT NOT NULL,
            graduation_id    INT NULL,
            graduation_label VARCHAR(60) NOT NULL,
            graduation_date  DATE NOT NULL,
            body_html        TEXT NOT NULL,
            issued_by        INT NOT NULL,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_athlete_date (athlete_id, graduation_date),
            CONSTRAINT fk_certificates_athlete    FOREIGN KEY (athlete_id)    REFERENCES athletes(id)            ON DELETE RESTRICT,
            CONSTRAINT fk_certificates_style      FOREIGN KEY (style_id)      REFERENCES martial_arts_styles(id) ON DELETE RESTRICT,
            CONSTRAINT fk_certificates_graduation FOREIGN KEY (graduation_id) REFERENCES graduations(id)         ON DELETE SET NULL,
            CONSTRAINT fk_certificates_issued_by  FOREIGN KEY (issued_by)     REFERENCES users(id)               ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "\n  athlete_certificates ok\n  ";
};

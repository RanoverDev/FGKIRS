<?php

/**
 * Fase 2: cadastro permanente de atletas e nível comum de faixa entre estilos.
 *
 * Idempotente: cada passo confere o que já existe, então dá para rodar de novo
 * depois de uma falha no meio (o MySQL não faz rollback de DDL).
 */

return function (PDO $pdo): void {
    $columnExists = function (string $table, string $column) use ($pdo): bool {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c"
        );
        $stmt->execute(['t' => $table, 'c' => $column]);

        return (bool) $stmt->fetchColumn();
    };

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS athletes (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "\n  athletes ok";

    if (!$columnExists('graduations', 'level')) {
        $pdo->exec(
            "ALTER TABLE graduations
             ADD COLUMN level TINYINT UNSIGNED NULL AFTER order_rank,
             ADD INDEX idx_level (level)"
        );
        echo "\n  graduations.level criada";
    }

    // Sugestão para o presidente revisar: posição na ordem do estilo; faixa preta nunca abaixo de 11
    $suggested = $pdo->exec(
        "UPDATE graduations
         SET level = LEAST(20, GREATEST(order_rank + 1, IF(is_black_belt = 1, 11, 1)))
         WHERE level IS NULL"
    );
    echo "\n  graduations.level sugerido em $suggested linha(s)";

    if (!$columnExists('championship_athletes', 'athlete_id')) {
        $pdo->exec(
            "ALTER TABLE championship_athletes
             ADD COLUMN athlete_id INT NULL AFTER user_id,
             ADD INDEX idx_athlete (athlete_id)"
        );
        echo "\n  championship_athletes.athlete_id criada";
    }

    echo "\n  ";
};

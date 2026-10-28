<?php

/**
 * Chaves de disputa, resultados e base do ranking.
 *
 * brackets/bracket_matches guardam o sorteio e as lutas de cada categoria do evento; event_results guarda
 * a colocação final de cada participante (é daqui que saem o pódio e o ranking). Nada usa CASCADE: chave
 * e resultado são histórico. Participante é polimórfico (inscrição individual ou equipe) conforme o tipo
 * da chave, então os ids de participante não têm FK; a integridade fica no BracketService.
 */

return function (PDO $pdo): void {
    $tables = [
        "CREATE TABLE IF NOT EXISTS brackets (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            championship_id   INT NOT NULL,
            event_category_id INT NOT NULL,
            participant_type  ENUM('registration','team') NOT NULL,
            size              SMALLINT UNSIGNED NOT NULL,
            status            ENUM('draft','running','finished') NOT NULL DEFAULT 'draft',
            created_by        INT NOT NULL,
            created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_bracket_category (event_category_id),
            INDEX idx_bracket_championship (championship_id),
            CONSTRAINT fk_brackets_championship FOREIGN KEY (championship_id)   REFERENCES championships(id)    ON DELETE RESTRICT,
            CONSTRAINT fk_brackets_category     FOREIGN KEY (event_category_id) REFERENCES event_categories(id) ON DELETE RESTRICT,
            CONSTRAINT fk_brackets_user         FOREIGN KEY (created_by)        REFERENCES users(id)            ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS bracket_matches (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            bracket_id  INT NOT NULL,
            round       TINYINT UNSIGNED NOT NULL,
            position    SMALLINT UNSIGNED NOT NULL,
            slot_a_id   INT NULL,
            slot_b_id   INT NULL,
            winner_id   INT NULL,
            score_a     VARCHAR(20) NULL,
            score_b     VARCHAR(20) NULL,
            decided_at  DATETIME NULL,
            decided_by  INT NULL,
            UNIQUE KEY uq_match_position (bracket_id, round, position),
            CONSTRAINT fk_bracket_matches_bracket FOREIGN KEY (bracket_id) REFERENCES brackets(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS event_results (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            championship_id   INT NOT NULL,
            event_category_id INT NOT NULL,
            place             TINYINT UNSIGNED NOT NULL,
            participant_type  ENUM('registration','team') NOT NULL,
            registration_id   INT NULL,
            team_id           INT NULL,
            athlete_id        INT NULL,
            dojo_id           INT NOT NULL,
            points            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_results_category (event_category_id, place),
            INDEX idx_results_championship (championship_id),
            INDEX idx_results_athlete (athlete_id),
            INDEX idx_results_dojo (dojo_id),
            CONSTRAINT fk_results_championship FOREIGN KEY (championship_id)   REFERENCES championships(id)    ON DELETE RESTRICT,
            CONSTRAINT fk_results_category     FOREIGN KEY (event_category_id) REFERENCES event_categories(id) ON DELETE RESTRICT,
            CONSTRAINT fk_results_registration FOREIGN KEY (registration_id)   REFERENCES championship_registrations(id) ON DELETE RESTRICT,
            CONSTRAINT fk_results_team         FOREIGN KEY (team_id)           REFERENCES event_teams(id)      ON DELETE RESTRICT,
            CONSTRAINT fk_results_athlete      FOREIGN KEY (athlete_id)        REFERENCES athletes(id)         ON DELETE RESTRICT,
            CONSTRAINT fk_results_dojo         FOREIGN KEY (dojo_id)           REFERENCES dojos(id)            ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }

    echo "\n  brackets, bracket_matches e event_results ok\n  ";
};

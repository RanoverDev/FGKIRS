<?php

/** Foto do atleta (arquivo em public/uploads/athletes). Idempotente. */

return function (PDO $pdo): void {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'athletes' AND COLUMN_NAME = 'photo'"
    );
    $stmt->execute();

    if (!$stmt->fetchColumn()) {
        $pdo->exec("ALTER TABLE athletes ADD COLUMN photo VARCHAR(255) NULL");
        echo "\n  athletes.photo criada";
    }

    echo "\n  ";
};

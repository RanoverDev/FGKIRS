<?php

/** Atleta convidado: inscrito por um dojo, mas de outro dojo ou clube. Idempotente. */

return function (PDO $pdo): void {
    $columnExists = function (string $column) use ($pdo): bool {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'athletes' AND COLUMN_NAME = :c"
        );
        $stmt->execute(['c' => $column]);

        return (bool) $stmt->fetchColumn();
    };

    if (!$columnExists('is_guest')) {
        $pdo->exec("ALTER TABLE athletes ADD COLUMN is_guest TINYINT(1) NOT NULL DEFAULT 0");
        echo "\n  athletes.is_guest criada";
    }

    if (!$columnExists('guest_origin')) {
        $pdo->exec("ALTER TABLE athletes ADD COLUMN guest_origin VARCHAR(120) NULL");
        echo "\n  athletes.guest_origin criada";
    }

    echo "\n  ";
};

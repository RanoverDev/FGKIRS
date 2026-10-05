<?php

/**
 * Fase 1: nenhuma exclusão apaga inscrição em cascata (ON DELETE CASCADE -> RESTRICT).
 *
 * O nome de cada FK é descoberto em information_schema, porque os nomes *_ibfk_N foram
 * gerados pelo MySQL e podem divergir entre ambientes. Idempotente: FK que já é RESTRICT é
 * pulada, então dá para rodar de novo depois de uma falha no meio.
 *
 * Mantêm CASCADE: championship_entries.championship_athlete_id, championship_team_members.*
 * e championship_categories.championship_id.
 */

return function (PDO $pdo): void {
    // tabela, coluna, tabela referenciada, nome novo da constraint
    $foreignKeys = [
        ['championship_categories', 'category_id',     'competition_categories', 'fk_champcat_category'],
        ['championship_athletes',   'championship_id', 'championships',          'fk_champathletes_championship'],
        ['championship_athletes',   'dojo_id',         'dojos',                  'fk_champathletes_dojo'],
        ['championship_entries',    'category_id',     'competition_categories', 'fk_entries_category'],
        ['championship_teams',      'championship_id', 'championships',          'fk_teams_championship'],
        ['championship_teams',      'dojo_id',         'dojos',                  'fk_teams_dojo'],
        ['championship_teams',      'category_id',     'competition_categories', 'fk_teams_category'],
        ['championship_referees',   'championship_id', 'championships',          'fk_referees_championship'],
        ['championship_referees',   'dojo_id',         'dojos',                  'fk_referees_dojo'],
    ];

    $lookup = $pdo->prepare(
        "SELECT rc.CONSTRAINT_NAME, rc.DELETE_RULE
         FROM information_schema.REFERENTIAL_CONSTRAINTS rc
         JOIN information_schema.KEY_COLUMN_USAGE k
           ON k.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
          AND k.CONSTRAINT_NAME   = rc.CONSTRAINT_NAME
          AND k.TABLE_NAME        = rc.TABLE_NAME
         WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
           AND rc.TABLE_NAME = :table
           AND k.COLUMN_NAME = :column
           AND rc.REFERENCED_TABLE_NAME = :ref"
    );

    foreach ($foreignKeys as [$table, $column, $ref, $newName]) {
        $lookup->execute(['table' => $table, 'column' => $column, 'ref' => $ref]);
        $existing = $lookup->fetchAll(PDO::FETCH_ASSOC);

        if ($existing !== [] && $existing[0]['DELETE_RULE'] === 'RESTRICT') {
            echo "\n  $table.$column já é RESTRICT";
            continue;
        }

        $drop = '';
        foreach ($existing as $fk) {
            $drop .= 'DROP FOREIGN KEY `' . $fk['CONSTRAINT_NAME'] . '`, ';
        }

        // Um ALTER por FK: DROP + ADD no mesmo comando é atômico no MySQL
        $pdo->exec(
            "ALTER TABLE `$table` {$drop}ADD CONSTRAINT `$newName`
             FOREIGN KEY (`$column`) REFERENCES `$ref`(id) ON DELETE RESTRICT"
        );
        echo "\n  $table.$column -> RESTRICT ($newName)";
    }

    echo "\n  ";
};

<?php

namespace Models;

use Core\Database;
use PDO;

/** Arbitros e equipe de mesa que o dojo indica para o campeonato. */
class ChampionshipReferee
{
    public const ROLES = [
        'referee'     => 'Árbitro',
        'table_judge' => 'Juiz de mesa',
        'timekeeper'  => 'Cronometrista',
        'scorer'      => 'Anotador',
    ];

    public const QUALIFICATIONS = [
        'Regional',
        'Estadual',
        'Nacional',
        'Internacional',
    ];

    public static function find(int $id): ?array
    {
        try {
            $row = Database::getInstance()->query(
                "SELECT * FROM championship_referees WHERE id = :id LIMIT 1",
                ['id' => $id]
            )->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (\Exception $e) {
            error_log('ChampionshipReferee::find error: ' . $e->getMessage());
            return null;
        }
    }

    public static function forDojo(int $championshipId, int $dojoId): array
    {
        try {
            return Database::getInstance()->query(
                "SELECT * FROM championship_referees
                 WHERE championship_id = :championship_id AND dojo_id = :dojo_id
                 ORDER BY name ASC",
                ['championship_id' => $championshipId, 'dojo_id' => $dojoId]
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('ChampionshipReferee::forDojo error: ' . $e->getMessage());
            return [];
        }
    }

    public static function forChampionship(int $championshipId): array
    {
        try {
            return Database::getInstance()->query(
                "SELECT r.*, d.name AS dojo_name
                 FROM championship_referees r
                 JOIN dojos d ON d.id = r.dojo_id
                 WHERE r.championship_id = :id
                 ORDER BY d.name ASC, r.name ASC",
                ['id' => $championshipId]
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('ChampionshipReferee::forChampionship error: ' . $e->getMessage());
            return [];
        }
    }

    /** Usuarios do dojo ainda nao indicados como arbitros neste evento. */
    public static function searchDojoMembers(int $championshipId, int $dojoId, string $term): array
    {
        try {
            return Database::getInstance()->query(
                "SELECT u.id, u.name, u.role, u.photo
                 FROM users u
                 WHERE u.dojo_id = :dojo_id
                   AND u.name LIKE :term
                   AND u.id NOT IN (
                        SELECT user_id FROM championship_referees
                        WHERE championship_id = :championship_id AND user_id IS NOT NULL
                   )
                 ORDER BY u.name ASC
                 LIMIT 15",
                [
                    'dojo_id'         => $dojoId,
                    'term'            => '%' . $term . '%',
                    'championship_id' => $championshipId,
                ]
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('ChampionshipReferee::searchDojoMembers error: ' . $e->getMessage());
            return [];
        }
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();

        $db->query(
            "INSERT INTO championship_referees
                (championship_id, dojo_id, user_id, name, role, qualification, notes, registered_by)
             VALUES
                (:championship_id, :dojo_id, :user_id, :name, :role, :qualification, :notes, :registered_by)",
            $data
        );

        return (int) $db->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->query(
            "DELETE FROM championship_referees WHERE id = :id",
            ['id' => $id]
        );
    }
}

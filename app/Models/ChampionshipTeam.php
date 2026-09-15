<?php

namespace Models;

use Core\Database;
use PDO;

/**
 * Equipes de kata e kumite (revezamento) inscritas por um dojo.
 *
 * Os integrantes saem sempre da aba Atletas: nao ha cadastro de atleta direto
 * na equipe.
 */
class ChampionshipTeam
{
    public static function find(int $id): ?array
    {
        try {
            $row = Database::getInstance()->query(
                "SELECT t.*, c.name AS category_name, c.modality, c.team_size,
                        c.age_min, c.age_max, c.gender AS category_gender
                 FROM championship_teams t
                 JOIN competition_categories c ON c.id = t.category_id
                 WHERE t.id = :id
                 LIMIT 1",
                ['id' => $id]
            )->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (\Exception $e) {
            error_log('ChampionshipTeam::find error: ' . $e->getMessage());
            return null;
        }
    }

    /** Equipes do dojo no evento, cada uma com a lista de integrantes. */
    public static function forDojo(int $championshipId, int $dojoId): array
    {
        try {
            $db = Database::getInstance();

            $teams = $db->query(
                "SELECT t.*, c.name AS category_name, c.modality, c.team_size,
                        c.age_label, c.gender AS category_gender
                 FROM championship_teams t
                 JOIN competition_categories c ON c.id = t.category_id
                 WHERE t.championship_id = :championship_id AND t.dojo_id = :dojo_id
                 ORDER BY c.sort_order ASC, t.id ASC",
                ['championship_id' => $championshipId, 'dojo_id' => $dojoId]
            )->fetchAll(PDO::FETCH_ASSOC);

            if (!$teams) {
                return [];
            }

            $members = $db->query(
                "SELECT m.team_id, m.position, a.id AS athlete_id, a.name, a.birth_date, a.gender
                 FROM championship_team_members m
                 JOIN championship_teams t ON t.id = m.team_id
                 JOIN championship_athletes a ON a.id = m.championship_athlete_id
                 WHERE t.championship_id = :championship_id AND t.dojo_id = :dojo_id
                 ORDER BY m.position ASC, a.name ASC",
                ['championship_id' => $championshipId, 'dojo_id' => $dojoId]
            )->fetchAll(PDO::FETCH_ASSOC);

            $byTeam = [];
            foreach ($members as $member) {
                $byTeam[(int) $member['team_id']][] = $member;
            }

            foreach ($teams as &$team) {
                $team['members'] = $byTeam[(int) $team['id']] ?? [];
            }

            return $teams;
        } catch (\Exception $e) {
            error_log('ChampionshipTeam::forDojo error: ' . $e->getMessage());
            return [];
        }
    }

    /** Quantas equipes o dojo ja inscreveu em cada categoria. */
    public static function countsByCategory(int $championshipId, int $dojoId): array
    {
        try {
            $rows = Database::getInstance()->query(
                "SELECT category_id, COUNT(*) AS total
                 FROM championship_teams
                 WHERE championship_id = :championship_id AND dojo_id = :dojo_id
                 GROUP BY category_id",
                ['championship_id' => $championshipId, 'dojo_id' => $dojoId]
            )->fetchAll(PDO::FETCH_ASSOC);

            return array_column($rows, 'total', 'category_id');
        } catch (\Exception $e) {
            error_log('ChampionshipTeam::countsByCategory error: ' . $e->getMessage());
            return [];
        }
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();

        $db->query(
            "INSERT INTO championship_teams (championship_id, dojo_id, category_id, name)
             VALUES (:championship_id, :dojo_id, :category_id, :name)",
            $data
        );

        return (int) $db->lastInsertId();
    }

    public static function rename(int $id, ?string $name): void
    {
        Database::getInstance()->query(
            "UPDATE championship_teams SET name = :name WHERE id = :id",
            ['name' => $name, 'id' => $id]
        );
    }

    /** Substitui a composicao da equipe pelos atletas informados. */
    public static function syncMembers(int $teamId, array $athleteIds): void
    {
        $db = Database::getInstance();

        $db->query(
            "DELETE FROM championship_team_members WHERE team_id = :team_id",
            ['team_id' => $teamId]
        );

        $position = 1;
        foreach ($athleteIds as $athleteId) {
            $db->query(
                "INSERT IGNORE INTO championship_team_members
                    (team_id, championship_athlete_id, position)
                 VALUES (:team_id, :athlete_id, :position)",
                ['team_id' => $teamId, 'athlete_id' => (int) $athleteId, 'position' => $position++]
            );
        }
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->query(
            "DELETE FROM championship_teams WHERE id = :id",
            ['id' => $id]
        );
    }

    /** Equipes de todos os dojos, para o painel do Presidente. */
    public static function forChampionship(int $championshipId): array
    {
        try {
            $db = Database::getInstance();

            $teams = $db->query(
                "SELECT t.*, d.name AS dojo_name, c.name AS category_name, c.modality
                 FROM championship_teams t
                 JOIN dojos d ON d.id = t.dojo_id
                 JOIN competition_categories c ON c.id = t.category_id
                 WHERE t.championship_id = :id
                 ORDER BY d.name ASC, c.sort_order ASC",
                ['id' => $championshipId]
            )->fetchAll(PDO::FETCH_ASSOC);

            if (!$teams) {
                return [];
            }

            $members = $db->query(
                "SELECT m.team_id, a.name
                 FROM championship_team_members m
                 JOIN championship_teams t ON t.id = m.team_id
                 JOIN championship_athletes a ON a.id = m.championship_athlete_id
                 WHERE t.championship_id = :id
                 ORDER BY m.position ASC",
                ['id' => $championshipId]
            )->fetchAll(PDO::FETCH_ASSOC);

            $byTeam = [];
            foreach ($members as $member) {
                $byTeam[(int) $member['team_id']][] = $member['name'];
            }

            foreach ($teams as &$team) {
                $team['member_names'] = $byTeam[(int) $team['id']] ?? [];
            }

            return $teams;
        } catch (\Exception $e) {
            error_log('ChampionshipTeam::forChampionship error: ' . $e->getMessage());
            return [];
        }
    }
}

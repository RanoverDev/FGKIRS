<?php

namespace Repositories;

use Core\Database;
use PDO;

class EventTeamRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        $row = $this->db->query("SELECT * FROM event_teams WHERE id = :id", ['id' => $id])->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** Equipes do evento (opcionalmente de um dojo), com integrantes e categoria. */
    public function forChampionship(int $championshipId, ?int $dojoId = null, bool $includeCancelled = false): array
    {
        $where  = ['t.championship_id = :c'];
        $params = ['c' => $championshipId];

        if ($dojoId !== null) {
            $where[] = 't.dojo_id = :d';
            $params['d'] = $dojoId;
        }
        if (!$includeCancelled) {
            $where[] = "t.status <> 'cancelled'";
        }

        $teams = $this->db->query(
            "SELECT t.*, c.name AS category_name, c.short_name, d.name AS dojo_name
             FROM event_teams t
             JOIN event_categories c ON c.id = t.event_category_id
             JOIN dojos d ON d.id = t.dojo_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY d.name, c.sort_order, t.id",
            $params
        )->fetchAll(PDO::FETCH_ASSOC);

        if ($teams === []) {
            return [];
        }

        $in = implode(',', array_map(static fn ($t) => (int) $t['id'], $teams));
        $members = [];
        foreach ($this->db->query(
            "SELECT m.team_id, m.registration_id, m.role, m.position, r.full_name AS name
             FROM event_team_members m
             JOIN championship_registrations r ON r.id = m.registration_id
             WHERE m.team_id IN ($in) ORDER BY m.role = 'reserve', m.position"
        )->fetchAll(PDO::FETCH_ASSOC) as $member) {
            $members[(int) $member['team_id']][] = $member;
        }

        foreach ($teams as &$team) {
            $team['members'] = $members[(int) $team['id']] ?? [];
        }

        return $teams;
    }

    /** Registrations já escaladas em equipe ativa da categoria (um atleta não entra em duas equipes da mesma categoria). */
    public function registrationIdsInCategory(int $categoryId, ?int $exceptTeamId = null): array
    {
        return array_map('intval', $this->db->query(
            "SELECT m.registration_id FROM event_team_members m
             JOIN event_teams t ON t.id = m.team_id
             WHERE t.event_category_id = :c AND t.status <> 'cancelled' AND t.id <> :except",
            ['c' => $categoryId, 'except' => $exceptTeamId ?? 0]
        )->fetchAll(PDO::FETCH_COLUMN));
    }

    public function hasActiveTeam(int $registrationId): bool
    {
        return (bool) $this->db->query(
            "SELECT 1 FROM event_team_members m JOIN event_teams t ON t.id = m.team_id
             WHERE m.registration_id = :r AND t.status <> 'cancelled' LIMIT 1",
            ['r' => $registrationId]
        )->fetchColumn();
    }

    public function create(int $championshipId, int $dojoId, int $categoryId, ?string $name): int
    {
        $this->db->query(
            "INSERT INTO event_teams (championship_id, dojo_id, event_category_id, name) VALUES (:c, :d, :cat, :n)",
            ['c' => $championshipId, 'd' => $dojoId, 'cat' => $categoryId, 'n' => $name]
        );

        return (int) $this->db->lastInsertId();
    }

    public function addMember(int $teamId, int $registrationId, string $role, int $position): void
    {
        $this->db->query(
            "INSERT INTO event_team_members (team_id, registration_id, role, position) VALUES (:t, :r, :role, :p)",
            ['t' => $teamId, 'r' => $registrationId, 'role' => $role, 'p' => $position]
        );
    }

    public function setStatus(int $id, string $status): void
    {
        $this->db->query("UPDATE event_teams SET status = :s WHERE id = :id", ['s' => $status, 'id' => $id]);
    }

    public function moveCategory(int $fromCategoryId, int $toCategoryId): void
    {
        $this->db->query(
            "UPDATE event_teams SET event_category_id = :to WHERE event_category_id = :from",
            ['to' => $toCategoryId, 'from' => $fromCategoryId]
        );
    }
}

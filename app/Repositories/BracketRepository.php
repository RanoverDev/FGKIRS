<?php

namespace Repositories;

use Core\Database;
use PDO;

/** SQL de chaves, lutas e resultados do evento. Regras ficam em Services\BracketService e Domain\Bracket. */
class BracketRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** As tabelas só existem depois do SQL 08_chaves; antes disso as telas avisam em vez de quebrar. */
    public function tablesExist(): bool
    {
        foreach (['brackets', 'bracket_matches', 'event_results'] as $table) {
            if (!$this->db->query("SHOW TABLES LIKE '$table'")->fetchColumn()) {
                return false;
            }
        }

        return true;
    }

    public function findByCategory(int $categoryId): ?array
    {
        $row = $this->db->query("SELECT * FROM brackets WHERE event_category_id = :c", ['c' => $categoryId])
            ->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** Chaves do evento indexadas pela categoria. */
    public function forChampionship(int $championshipId): array
    {
        $out = [];

        foreach ($this->db->query("SELECT * FROM brackets WHERE championship_id = :c", ['c' => $championshipId])
            ->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[(int) $row['event_category_id']] = $row;
        }

        return $out;
    }

    public function create(int $championshipId, int $categoryId, string $type, int $size, int $userId): int
    {
        $this->db->query(
            "INSERT INTO brackets (championship_id, event_category_id, participant_type, size, created_by)
             VALUES (:c, :cat, :t, :s, :u)",
            ['c' => $championshipId, 'cat' => $categoryId, 't' => $type, 's' => $size, 'u' => $userId]
        );

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $size, string $status): void
    {
        $this->db->query("UPDATE brackets SET size = :s, status = :st WHERE id = :id", ['s' => $size, 'st' => $status, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->query("DELETE FROM bracket_matches WHERE bracket_id = :id", ['id' => $id]);
        $this->db->query("DELETE FROM brackets WHERE id = :id", ['id' => $id]);
    }

    public function deleteMatches(int $bracketId): void
    {
        $this->db->query("DELETE FROM bracket_matches WHERE bracket_id = :id", ['id' => $bracketId]);
    }

    /** @return array[] lutas com os nomes que a árvore usa (a, b, winner) e os placares */
    public function matches(int $bracketId): array
    {
        return $this->db->query(
            "SELECT round, position, slot_a_id AS a, slot_b_id AS b, winner_id AS winner, score_a, score_b, decided_at
             FROM bracket_matches WHERE bracket_id = :id ORDER BY round, position",
            ['id' => $bracketId]
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Grava as lutas da árvore. Placar e data só sobrevivem enquanto a luta tem vencedor.
     *
     * @param list<array{round: int, position: int, a: ?int, b: ?int, winner: ?int}> $rows
     */
    public function saveMatches(int $bracketId, array $rows): void
    {
        foreach ($rows as $row) {
            $this->db->query(
                "INSERT INTO bracket_matches (bracket_id, round, position, slot_a_id, slot_b_id, winner_id)
                 VALUES (:b, :r, :p, :a, :sb, :w)
                 ON DUPLICATE KEY UPDATE
                    slot_a_id = VALUES(slot_a_id), slot_b_id = VALUES(slot_b_id), winner_id = VALUES(winner_id),
                    score_a    = IF(VALUES(winner_id) IS NULL, NULL, score_a),
                    score_b    = IF(VALUES(winner_id) IS NULL, NULL, score_b),
                    decided_at = IF(VALUES(winner_id) IS NULL, NULL, decided_at),
                    decided_by = IF(VALUES(winner_id) IS NULL, NULL, decided_by)",
                ['b' => $bracketId, 'r' => $row['round'], 'p' => $row['position'], 'a' => $row['a'], 'sb' => $row['b'], 'w' => $row['winner']]
            );
        }
    }

    public function recordDecision(int $bracketId, int $round, int $position, ?string $scoreA, ?string $scoreB, int $userId): void
    {
        $this->db->query(
            "UPDATE bracket_matches SET score_a = :sa, score_b = :sb, decided_at = NOW(), decided_by = :u
             WHERE bracket_id = :b AND round = :r AND position = :p",
            ['sa' => $scoreA, 'sb' => $scoreB, 'u' => $userId, 'b' => $bracketId, 'r' => $round, 'p' => $position]
        );
    }

    // ───────────────────────────── Participantes ─────────────────────────────

    /** @return array<int, int> categoria => quantos podem competir (individuais e equipes enviadas ou confirmadas) */
    public function participantCounts(int $championshipId): array
    {
        $counts = [];

        $rows = $this->db->query(
            "SELECT e.event_category_id AS category_id, COUNT(*) AS n
             FROM registration_entries e
             JOIN championship_registrations r ON r.id = e.registration_id
             WHERE r.championship_id = :c AND e.status = 'active' AND r.status IN ('submitted', 'confirmed')
             GROUP BY e.event_category_id",
            ['c' => $championshipId]
        )->fetchAll(PDO::FETCH_ASSOC);

        $teams = $this->db->query(
            "SELECT event_category_id AS category_id, COUNT(*) AS n FROM event_teams
             WHERE championship_id = :c AND status IN ('submitted', 'confirmed') GROUP BY event_category_id",
            ['c' => $championshipId]
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach (array_merge($rows, $teams) as $row) {
            $counts[(int) $row['category_id']] = (int) $row['n'];
        }

        return $counts;
    }

    /**
     * Quem compete na categoria individual: inscrições enviadas ou confirmadas com entrada ativa.
     *
     * @return array<int, array{id: int, name: string, dojo_id: int, dojo_name: string, athlete_id: ?int}>
     */
    public function registrationParticipants(int $categoryId): array
    {
        return $this->indexById($this->db->query(
            "SELECT r.id, r.full_name AS name, r.dojo_id, d.name AS dojo_name, r.athlete_id
             FROM registration_entries e
             JOIN championship_registrations r ON r.id = e.registration_id
             JOIN dojos d ON d.id = r.dojo_id
             WHERE e.event_category_id = :c AND e.status = 'active' AND r.status IN ('submitted', 'confirmed')
             ORDER BY r.full_name",
            ['c' => $categoryId]
        )->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<int, array{id: int, name: string, dojo_id: int, dojo_name: string, athlete_id: ?int}> */
    public function teamParticipants(int $categoryId): array
    {
        return $this->indexById($this->db->query(
            "SELECT t.id, COALESCE(NULLIF(t.name, ''), CONCAT('Equipe ', d.name)) AS name,
                    t.dojo_id, d.name AS dojo_name, NULL AS athlete_id
             FROM event_teams t
             JOIN dojos d ON d.id = t.dojo_id
             WHERE t.event_category_id = :c AND t.status IN ('submitted', 'confirmed')
             ORDER BY d.name",
            ['c' => $categoryId]
        )->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Dados de exibição de participantes que já estão na chave (mesmo cancelados depois).
     *
     * @param int[] $ids
     * @return array<int, array{id: int, name: string, dojo_id: int, dojo_name: string, athlete_id: ?int}>
     */
    public function labels(string $type, array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return [];
        }

        $in = implode(',', $ids); // inteiros normalizados acima

        $sql = $type === 'team'
            ? "SELECT t.id, COALESCE(NULLIF(t.name, ''), CONCAT('Equipe ', d.name)) AS name, t.dojo_id, d.name AS dojo_name, NULL AS athlete_id
               FROM event_teams t JOIN dojos d ON d.id = t.dojo_id WHERE t.id IN ($in)"
            : "SELECT r.id, r.full_name AS name, r.dojo_id, d.name AS dojo_name, r.athlete_id
               FROM championship_registrations r JOIN dojos d ON d.id = r.dojo_id WHERE r.id IN ($in)";

        return $this->indexById($this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    }

    // ───────────────────────────── Resultados ─────────────────────────────

    /** @param list<array{place: int, type: string, id: int, athlete_id: ?int, dojo_id: int, points: int}> $rows */
    public function replaceResults(int $championshipId, int $categoryId, array $rows): void
    {
        $this->db->query("DELETE FROM event_results WHERE event_category_id = :c", ['c' => $categoryId]);

        foreach ($rows as $row) {
            $this->db->query(
                "INSERT INTO event_results
                    (championship_id, event_category_id, place, participant_type, registration_id, team_id, athlete_id, dojo_id, points)
                 VALUES (:ch, :cat, :pl, :t, :reg, :team, :ath, :dojo, :pts)",
                [
                    'ch' => $championshipId, 'cat' => $categoryId, 'pl' => $row['place'], 't' => $row['type'],
                    'reg' => $row['type'] === 'registration' ? $row['id'] : null,
                    'team' => $row['type'] === 'team' ? $row['id'] : null,
                    'ath' => $row['athlete_id'], 'dojo' => $row['dojo_id'], 'pts' => $row['points'],
                ]
            );
        }
    }

    public function deleteResults(int $categoryId): void
    {
        $this->db->query("DELETE FROM event_results WHERE event_category_id = :c", ['c' => $categoryId]);
    }

    /** Resultados do evento com o nome de quem competiu, do 1º para trás dentro de cada categoria. */
    public function resultsForChampionship(int $championshipId): array
    {
        return $this->db->query(
            "SELECT x.*, c.name AS category_name, c.short_name, c.discipline_name, c.modality, c.format, c.sort_order,
                    d.name AS dojo_name,
                    COALESCE(r.full_name, NULLIF(t.name, ''), CONCAT('Equipe ', d.name)) AS participant_name
             FROM event_results x
             JOIN event_categories c ON c.id = x.event_category_id
             JOIN dojos d ON d.id = x.dojo_id
             LEFT JOIN championship_registrations r ON r.id = x.registration_id
             LEFT JOIN event_teams t ON t.id = x.team_id
             WHERE x.championship_id = :c
             ORDER BY c.sort_order, c.id, x.place, participant_name",
            ['c' => $championshipId]
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ranking de atletas entre eventos: soma de pontos e medalhas.
     *
     * @param array{year?: int, modality?: string} $filters
     */
    public function athleteRanking(array $filters = []): array
    {
        [$where, $params] = $this->rankingFilter($filters);

        return $this->db->query(
            "SELECT a.id, a.full_name AS name, d.name AS dojo_name,
                    SUM(x.points) AS points,
                    SUM(x.place = 1) AS golds, SUM(x.place = 2) AS silvers, SUM(x.place = 3) AS bronzes,
                    COUNT(*) AS participations
             FROM event_results x
             JOIN athletes a ON a.id = x.athlete_id
             JOIN dojos d ON d.id = a.dojo_id
             JOIN championships ch ON ch.id = x.championship_id
             JOIN event_categories c ON c.id = x.event_category_id
             WHERE x.athlete_id IS NOT NULL $where
             GROUP BY a.id, a.full_name, d.name
             ORDER BY points DESC, golds DESC, silvers DESC, bronzes DESC, a.full_name ASC",
            $params
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function dojoRanking(array $filters = []): array
    {
        [$where, $params] = $this->rankingFilter($filters);

        return $this->db->query(
            "SELECT d.id, d.name, d.city,
                    SUM(x.points) AS points,
                    SUM(x.place = 1) AS golds, SUM(x.place = 2) AS silvers, SUM(x.place = 3) AS bronzes
             FROM event_results x
             JOIN dojos d ON d.id = x.dojo_id
             JOIN championships ch ON ch.id = x.championship_id
             JOIN event_categories c ON c.id = x.event_category_id
             WHERE 1 = 1 $where
             GROUP BY d.id, d.name, d.city
             ORDER BY points DESC, golds DESC, silvers DESC, bronzes DESC, d.name ASC",
            $params
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return int[] anos com campeonatos que têm resultado */
    public function rankingYears(): array
    {
        return array_map('intval', $this->db->query(
            "SELECT DISTINCT YEAR(ch.event_date) FROM event_results x
             JOIN championships ch ON ch.id = x.championship_id ORDER BY 1 DESC"
        )->fetchAll(PDO::FETCH_COLUMN));
    }

    private function rankingFilter(array $filters): array
    {
        $where  = '';
        $params = [];

        if (!empty($filters['year'])) {
            $where .= ' AND YEAR(ch.event_date) = :year';
            $params['year'] = (int) $filters['year'];
        }

        if (!empty($filters['modality']) && in_array($filters['modality'], ['kata', 'kumite'], true)) {
            $where .= ' AND c.modality = :modality';
            $params['modality'] = $filters['modality'];
        }

        return [$where, $params];
    }

    private function indexById(array $rows): array
    {
        $out = [];

        foreach ($rows as $row) {
            $row['id']                = (int) $row['id'];
            $row['dojo_id']           = (int) $row['dojo_id'];
            $row['athlete_id']        = $row['athlete_id'] === null ? null : (int) $row['athlete_id'];
            $out[$row['id']]          = $row;
        }

        return $out;
    }
}

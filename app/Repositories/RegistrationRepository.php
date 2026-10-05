<?php

namespace Repositories;

use Core\Database;
use PDO;

/** SQL das inscrições do evento (atleta no evento, categorias e auditoria). */
class RegistrationRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        $row = $this->db->query("SELECT * FROM championship_registrations WHERE id = :id", ['id' => $id])
            ->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** Inscrições do dojo indexadas por athlete_id, cada uma com suas entradas ativas. */
    public function forDojoByAthlete(int $championshipId, int $dojoId): array
    {
        $rows = $this->db->query(
            "SELECT * FROM championship_registrations WHERE championship_id = :c AND dojo_id = :d",
            ['c' => $championshipId, 'd' => $dojoId]
        )->fetchAll(PDO::FETCH_ASSOC);

        return $this->attachEntries($rows, true);
    }

    /**
     * Painel do Presidente: todas as inscrições do evento, com dojo, categorias e filtros.
     *
     * @param array{status?: string, dojo_id?: int, category_id?: int} $filters
     */
    public function forChampionship(int $championshipId, array $filters = []): array
    {
        $where  = ['r.championship_id = :c'];
        $params = ['c' => $championshipId];

        if (!empty($filters['status'])) {
            $where[] = 'r.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['dojo_id'])) {
            $where[] = 'r.dojo_id = :dojo';
            $params['dojo'] = (int) $filters['dojo_id'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = "EXISTS (SELECT 1 FROM registration_entries e WHERE e.registration_id = r.id
                                AND e.event_category_id = :cat AND e.status = 'active')";
            $params['cat'] = (int) $filters['category_id'];
        }

        $rows = $this->db->query(
            "SELECT r.*, d.name AS dojo_name, g.belt_name
             FROM championship_registrations r
             JOIN dojos d ON d.id = r.dojo_id
             LEFT JOIN graduations g ON g.id = r.graduation_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY d.name ASC, r.full_name ASC",
            $params
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_values($this->attachEntries($rows, false));
    }

    /** Inscrições não canceladas do dojo (para montar equipes e resumos). */
    public function activeForDojo(int $championshipId, int $dojoId): array
    {
        return array_values($this->attachEntries($this->db->query(
            "SELECT * FROM championship_registrations
             WHERE championship_id = :c AND dojo_id = :d AND status <> 'cancelled' ORDER BY full_name ASC",
            ['c' => $championshipId, 'd' => $dojoId]
        )->fetchAll(PDO::FETCH_ASSOC), false));
    }

    public function save(?int $id, array $data): int
    {
        $update = ['dojo_id', 'full_name', 'gender', 'birth_date', 'age_at_event', 'graduation_id', 'belt_level', 'declared_weight', 'is_para_karate'];

        if ($id !== null) {
            $data = array_intersect_key($data, array_flip($update));

            $this->db->query(
                "UPDATE championship_registrations SET
                    dojo_id = :dojo_id, full_name = :full_name, gender = :gender, birth_date = :birth_date,
                    age_at_event = :age_at_event, graduation_id = :graduation_id, belt_level = :belt_level,
                    declared_weight = :declared_weight, is_para_karate = :is_para_karate
                 WHERE id = :id",
                $data + ['id' => $id]
            );

            return $id;
        }

        $this->db->query(
            "INSERT INTO championship_registrations
                (championship_id, athlete_id, dojo_id, full_name, gender, birth_date, age_at_event, graduation_id,
                 belt_level, declared_weight, is_para_karate, registered_by)
             VALUES
                (:championship_id, :athlete_id, :dojo_id, :full_name, :gender, :birth_date, :age_at_event, :graduation_id,
                 :belt_level, :declared_weight, :is_para_karate, :registered_by)",
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    public function setStatus(int $id, string $status, array $extra = []): void
    {
        $sets   = ['status = :status'];
        $params = ['status' => $status, 'id' => $id];

        foreach (['fee_total', 'paid_at', 'submitted_at'] as $column) {
            if (array_key_exists($column, $extra)) {
                $sets[] = "$column = :$column";
                $params[$column] = $extra[$column];
            }
        }

        $this->db->query("UPDATE championship_registrations SET " . implode(', ', $sets) . " WHERE id = :id", $params);
    }

    public function setFee(int $id, ?float $fee): void
    {
        $this->db->query("UPDATE championship_registrations SET fee_total = :f WHERE id = :id", ['f' => $fee, 'id' => $id]);
    }

    /** Cria a entrada ou reativa uma cancelada: a linha é única por inscrição e categoria. */
    public function activateEntry(int $registrationId, int $categoryId, bool $override = false, ?string $reason = null, ?int $by = null): void
    {
        $this->db->query(
            "INSERT INTO registration_entries (registration_id, event_category_id, status, is_override, override_reason, override_by)
             VALUES (:r, :c, 'active', :o, :reason, :by)
             ON DUPLICATE KEY UPDATE status = 'active', is_override = VALUES(is_override),
                override_reason = VALUES(override_reason), override_by = VALUES(override_by)",
            ['r' => $registrationId, 'c' => $categoryId, 'o' => (int) $override, 'reason' => $reason, 'by' => $by]
        );
    }

    public function setEntryStatus(int $registrationId, int $categoryId, string $status): void
    {
        $this->db->query(
            "UPDATE registration_entries SET status = :s WHERE registration_id = :r AND event_category_id = :c",
            ['s' => $status, 'r' => $registrationId, 'c' => $categoryId]
        );
    }

    public function cancelAllEntries(int $registrationId): void
    {
        $this->db->query(
            "UPDATE registration_entries SET status = 'cancelled' WHERE registration_id = :r AND status = 'active'",
            ['r' => $registrationId]
        );
    }

    /** @return array[] entradas ativas da inscrição, com a categoria */
    public function activeEntries(int $registrationId): array
    {
        return $this->db->query(
            "SELECT e.*, c.name AS category_name, c.short_name, c.discipline_code, c.format
             FROM registration_entries e
             JOIN event_categories c ON c.id = e.event_category_id
             WHERE e.registration_id = :r AND e.status = 'active'
             ORDER BY c.sort_order",
            ['r' => $registrationId]
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function log(int $registrationId, string $action, int $userId, array $details = []): void
    {
        $this->db->query(
            "INSERT INTO registration_log (registration_id, action, details, user_id) VALUES (:r, :a, :d, :u)",
            ['r' => $registrationId, 'a' => $action, 'd' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null, 'u' => $userId]
        );
    }

    public function logFor(int $registrationId): array
    {
        return $this->db->query(
            "SELECT l.*, u.name AS user_name FROM registration_log l
             LEFT JOIN users u ON u.id = l.user_id WHERE l.registration_id = :r ORDER BY l.id DESC",
            ['r' => $registrationId]
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array{athletes: int, entries: int, dojos: int, fee: float} totais das inscrições não canceladas */
    public function totals(int $championshipId): array
    {
        $row = $this->db->query(
            "SELECT COUNT(*) AS athletes, COUNT(DISTINCT r.dojo_id) AS dojos, COALESCE(SUM(r.fee_total), 0) AS fee,
                    (SELECT COUNT(*) FROM registration_entries e
                       JOIN championship_registrations r2 ON r2.id = e.registration_id
                      WHERE r2.championship_id = :c2 AND r2.status <> 'cancelled' AND e.status = 'active') AS entries
             FROM championship_registrations r
             WHERE r.championship_id = :c AND r.status <> 'cancelled'",
            ['c' => $championshipId, 'c2' => $championshipId]
        )->fetch(PDO::FETCH_ASSOC);

        return [
            'athletes' => (int) $row['athletes'], 'entries' => (int) $row['entries'],
            'dojos' => (int) $row['dojos'], 'fee' => (float) $row['fee'],
        ];
    }

    /**
     * @param bool $byAthlete indexa por athlete_id (grade) em vez de lista
     */
    private function attachEntries(array $rows, bool $byAthlete): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
        $in  = implode(',', $ids); // ids inteiros vindos do próprio banco

        $entries = [];
        foreach ($this->db->query(
            "SELECT e.registration_id, e.event_category_id, e.is_override, e.override_reason,
                    c.name AS category_name, c.short_name, c.discipline_code, c.format
             FROM registration_entries e
             JOIN event_categories c ON c.id = e.event_category_id
             WHERE e.status = 'active' AND e.registration_id IN ($in)
             ORDER BY c.sort_order"
        )->fetchAll(PDO::FETCH_ASSOC) as $entry) {
            $entries[(int) $entry['registration_id']][] = $entry;
        }

        $out = [];
        foreach ($rows as $row) {
            $row['entries'] = $entries[(int) $row['id']] ?? [];
            $byAthlete ? $out[(int) $row['athlete_id']] = $row : $out[] = $row;
        }

        return $out;
    }
}

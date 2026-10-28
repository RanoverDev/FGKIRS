<?php

namespace Repositories;

use Core\Database;
use Domain\Championship\EventCategory;
use Domain\Championship\EventCategoryDraft;
use PDO;

/** SQL das categorias do evento. Nada aqui apaga categoria: ela fecha, é juntada ou fica. */
class EventCategoryRepository
{
    private Database $db;

    /** Inscritos ativos por categoria: entradas ativas de inscrições que não foram canceladas. */
    private const ENROLLED = "(SELECT COUNT(*) FROM registration_entries e
                               JOIN championship_registrations r ON r.id = e.registration_id
                               WHERE e.event_category_id = c.id AND e.status = 'active' AND r.status <> 'cancelled')";

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** @return array[] linhas com a contagem de inscritos (enrolled), na ordem do evento */
    public function rows(int $championshipId): array
    {
        return $this->db->query(
            "SELECT c.*, " . self::ENROLLED . " AS enrolled,
                    (SELECT COUNT(*) FROM event_teams t WHERE t.event_category_id = c.id AND t.status <> 'cancelled') AS team_count
             FROM event_categories c
             WHERE c.championship_id = :id
             ORDER BY c.sort_order, c.id",
            ['id' => $championshipId]
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return EventCategory[] */
    public function forChampionship(int $championshipId): array
    {
        return array_map([EventCategory::class, 'fromRow'], $this->rows($championshipId));
    }

    public function count(int $championshipId): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM event_categories WHERE championship_id = :id",
            ['id' => $championshipId]
        )->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $row = $this->db->query(
            "SELECT c.*, " . self::ENROLLED . " AS enrolled FROM event_categories c WHERE c.id = :id",
            ['id' => $id]
        )->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Grava só os códigos que o evento ainda não tem: regerar nunca duplica nem sobrescreve.
     *
     * @param EventCategoryDraft[] $drafts
     * @return array{inserted: int, skipped: int}
     */
    public function insertMissing(int $championshipId, array $drafts): array
    {
        $existing = array_flip($this->db->query(
            "SELECT code FROM event_categories WHERE championship_id = :id",
            ['id' => $championshipId]
        )->fetchAll(PDO::FETCH_COLUMN));

        $nextOrder = (int) $this->db->query(
            "SELECT COALESCE(MAX(sort_order), 0) FROM event_categories WHERE championship_id = :id",
            ['id' => $championshipId]
        )->fetchColumn();

        $inserted = $skipped = 0;

        foreach ($drafts as $d) {
            if (isset($existing[$d->code])) {
                $skipped++;
                continue;
            }

            $this->db->query(
                "INSERT INTO event_categories
                    (championship_id, code, name, short_name, discipline_code, discipline_name, modality, format, gender,
                     age_min, age_max, belt_level_min, belt_level_max, weight_min, weight_max, team_size, team_reserves,
                     discipline_id, age_division_id, belt_division_id, weight_class_id, sort_order, measure)
                 VALUES
                    (:championship_id, :code, :name, :short_name, :discipline_code, :discipline_name, :modality, :format, :gender,
                     :age_min, :age_max, :belt_min, :belt_max, :weight_min, :weight_max, :team_size, :team_reserves,
                     :discipline_id, :age_id, :belt_id, :weight_id, :sort_order, :measure)",
                [
                    'championship_id' => $championshipId, 'code' => $d->code, 'name' => $d->name,
                    'short_name' => $d->shortName, 'discipline_code' => $d->disciplineCode,
                    'discipline_name' => $d->disciplineName, 'modality' => $d->modality, 'format' => $d->format,
                    'gender' => $d->gender, 'age_min' => $d->ageMin, 'age_max' => $d->ageMax,
                    'belt_min' => $d->beltLevelMin, 'belt_max' => $d->beltLevelMax,
                    'weight_min' => $d->weightMin, 'weight_max' => $d->weightMax,
                    'team_size' => $d->teamSize, 'team_reserves' => $d->teamReserves,
                    'discipline_id' => $d->disciplineId, 'age_id' => $d->ageDivisionId,
                    'belt_id' => $d->beltDivisionId, 'weight_id' => $d->weightClassId,
                    'sort_order' => ++$nextOrder, 'measure' => $d->measure,
                ]
            );
            $inserted++;
        }

        return ['inserted' => $inserted, 'skipped' => $skipped];
    }

    public function setStatus(int $id, string $status): void
    {
        $this->db->query("UPDATE event_categories SET status = :s WHERE id = :id", ['s' => $status, 'id' => $id]);
    }

    public function markMerged(int $id, int $intoId): void
    {
        $this->db->query(
            "UPDATE event_categories SET status = 'merged', merged_into_id = :into WHERE id = :id",
            ['into' => $intoId, 'id' => $id]
        );
    }
}

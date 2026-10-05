<?php

namespace Repositories;

use Core\Database;
use Domain\Championship\AgeDivision;
use Domain\Championship\BeltDivision;
use Domain\Championship\Discipline;
use Domain\Championship\Ruleset;
use Domain\Championship\WeightClass;
use PDO;

/**
 * Só SQL do regulamento. As gravações de bloco (idades, faixas, pesos, disciplinas) trocam o
 * conjunto inteiro: linha com id é atualizada, sem id é criada, e a que sumiu do formulário é
 * removida. Dado inválido sobe como InvalidArgumentException com mensagem pronta para a tela.
 */
class RulesetRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(): array
    {
        return $this->db->query(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM age_divisions WHERE ruleset_id = r.id) AS age_count,
                    (SELECT COUNT(*) FROM belt_divisions WHERE ruleset_id = r.id) AS belt_count,
                    (SELECT COUNT(*) FROM ruleset_disciplines WHERE ruleset_id = r.id) AS discipline_count
             FROM rulesets r
             ORDER BY r.is_default DESC, r.name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $row = $this->db->query("SELECT * FROM rulesets WHERE id = :id", ['id' => $id])->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function load(int $id): Ruleset
    {
        $row = $this->find($id);

        if (!$row) {
            throw new \RuntimeException("Regulamento $id não encontrado.");
        }

        $ages = array_map(
            static fn (array $r) => new AgeDivision(
                (int) $r['id'], $r['code'], $r['name'],
                $r['age_min'] !== null ? (int) $r['age_min'] : null,
                $r['age_max'] !== null ? (int) $r['age_max'] : null,
                (int) $r['sort_order']
            ),
            $this->db->query(
                "SELECT * FROM age_divisions WHERE ruleset_id = :id ORDER BY sort_order, id",
                ['id' => $id]
            )->fetchAll(PDO::FETCH_ASSOC)
        );

        $belts = array_map(
            static fn (array $r) => new BeltDivision(
                (int) $r['id'], $r['code'], $r['name'], (int) $r['level_min'], (int) $r['level_max'], (int) $r['sort_order']
            ),
            $this->db->query(
                "SELECT * FROM belt_divisions WHERE ruleset_id = :id ORDER BY sort_order, id",
                ['id' => $id]
            )->fetchAll(PDO::FETCH_ASSOC)
        );

        $weights = array_map(
            static fn (array $r) => new WeightClass(
                (int) $r['id'], (int) $r['age_division_id'], $r['gender'], $r['name'],
                $r['weight_min'] !== null ? (float) $r['weight_min'] : null,
                $r['weight_max'] !== null ? (float) $r['weight_max'] : null,
                (int) $r['sort_order']
            ),
            $this->db->query(
                "SELECT * FROM weight_classes WHERE ruleset_id = :id ORDER BY sort_order, id",
                ['id' => $id]
            )->fetchAll(PDO::FETCH_ASSOC)
        );

        $links = [];
        foreach ($this->db->query(
            "SELECT l.discipline_id, l.age_division_id
             FROM ruleset_discipline_ages l
             JOIN ruleset_disciplines d ON d.id = l.discipline_id
             WHERE d.ruleset_id = :id",
            ['id' => $id]
        )->fetchAll(PDO::FETCH_ASSOC) as $link) {
            $links[(int) $link['discipline_id']][] = (int) $link['age_division_id'];
        }

        $disciplines = array_map(
            static fn (array $r) => new Discipline(
                (int) $r['id'], $r['code'], $r['name'], $r['modality'], $r['format'],
                (bool) $r['split_gender'], (bool) $r['split_belt'], (bool) $r['split_weight'],
                $r['team_size'] !== null ? (int) $r['team_size'] : null,
                $r['team_reserves'] !== null ? (int) $r['team_reserves'] : null,
                $links[(int) $r['id']] ?? [],
                (int) $r['sort_order']
            ),
            $this->db->query(
                "SELECT * FROM ruleset_disciplines WHERE ruleset_id = :id ORDER BY sort_order, id",
                ['id' => $id]
            )->fetchAll(PDO::FETCH_ASSOC)
        );

        return new Ruleset((int) $row['id'], $row['name'], $row['age_policy'], $ages, $belts, $weights, $disciplines);
    }

    public function create(string $name, string $agePolicy): int
    {
        $name = $this->requireName($name);

        $this->db->query(
            "INSERT INTO rulesets (name, age_policy) VALUES (:name, :policy)",
            ['name' => $name, 'policy' => $this->agePolicy($agePolicy)]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateMeta(int $id, string $name, string $agePolicy, bool $isActive, bool $isDefault): void
    {
        $name = $this->requireName($name);

        $this->transaction(function () use ($id, $name, $agePolicy, $isActive, $isDefault) {
            // Só um regulamento é o padrão, e o padrão não pode ficar inativo
            if ($isDefault) {
                $this->db->query("UPDATE rulesets SET is_default = 0 WHERE id <> :id", ['id' => $id]);
                $isActive = true;
            }

            $this->db->query(
                "UPDATE rulesets SET name = :name, age_policy = :policy, is_active = :active, is_default = :def WHERE id = :id",
                [
                    'name' => $name, 'policy' => $this->agePolicy($agePolicy),
                    'active' => (int) $isActive, 'def' => (int) $isDefault, 'id' => $id,
                ]
            );
        });
    }

    /** Cópia completa (divisões, pesos, disciplinas e vínculos) para criar a próxima versão sem mexer na atual. */
    public function duplicate(int $id): int
    {
        $source = $this->load($id);

        return $this->transaction(function () use ($source) {
            $this->db->query(
                "INSERT INTO rulesets (name, age_policy, is_default, is_active) VALUES (:name, :policy, 0, 1)",
                ['name' => mb_substr($source->name, 0, 108) . ' (cópia)', 'policy' => $source->agePolicy]
            );
            $newId = (int) $this->db->lastInsertId();

            $ageMap = [];
            foreach ($source->ageDivisions as $a) {
                $this->db->query(
                    "INSERT INTO age_divisions (ruleset_id, code, name, age_min, age_max, sort_order)
                     VALUES (:r, :code, :name, :min, :max, :sort)",
                    ['r' => $newId, 'code' => $a->code, 'name' => $a->name, 'min' => $a->ageMin, 'max' => $a->ageMax, 'sort' => $a->sortOrder]
                );
                $ageMap[$a->id] = (int) $this->db->lastInsertId();
            }

            foreach ($source->beltDivisions as $b) {
                $this->db->query(
                    "INSERT INTO belt_divisions (ruleset_id, code, name, level_min, level_max, sort_order)
                     VALUES (:r, :code, :name, :min, :max, :sort)",
                    ['r' => $newId, 'code' => $b->code, 'name' => $b->name, 'min' => $b->levelMin, 'max' => $b->levelMax, 'sort' => $b->sortOrder]
                );
            }

            foreach ($source->weightClasses as $w) {
                $this->db->query(
                    "INSERT INTO weight_classes (ruleset_id, age_division_id, gender, name, weight_min, weight_max, sort_order)
                     VALUES (:r, :age, :gender, :name, :min, :max, :sort)",
                    [
                        'r' => $newId, 'age' => $ageMap[$w->ageDivisionId], 'gender' => $w->gender, 'name' => $w->name,
                        'min' => $w->weightMin, 'max' => $w->weightMax, 'sort' => $w->sortOrder,
                    ]
                );
            }

            foreach ($source->disciplines as $d) {
                $this->db->query(
                    "INSERT INTO ruleset_disciplines
                        (ruleset_id, code, name, modality, format, split_gender, split_belt, split_weight, team_size, team_reserves, sort_order)
                     VALUES (:r, :code, :name, :modality, :format, :sg, :sb, :sw, :size, :reserves, :sort)",
                    [
                        'r' => $newId, 'code' => $d->code, 'name' => $d->name, 'modality' => $d->modality, 'format' => $d->format,
                        'sg' => (int) $d->splitGender, 'sb' => (int) $d->splitBelt, 'sw' => (int) $d->splitWeight,
                        'size' => $d->teamSize, 'reserves' => $d->teamReserves, 'sort' => $d->sortOrder,
                    ]
                );
                $disciplineId = (int) $this->db->lastInsertId();

                foreach ($d->ageDivisionIds as $oldAgeId) {
                    $this->db->query(
                        "INSERT INTO ruleset_discipline_ages (discipline_id, age_division_id) VALUES (:d, :a)",
                        ['d' => $disciplineId, 'a' => $ageMap[$oldAgeId]]
                    );
                }
            }

            return $newId;
        });
    }

    public function saveAgeDivisions(int $rulesetId, array $rows): void
    {
        $clean = [];
        foreach (array_values($rows) as $position => $row) {
            $min = $this->nullableInt($row['age_min'] ?? null);
            $max = $this->nullableInt($row['age_max'] ?? null);

            if ($min !== null && $max !== null && $min > $max) {
                throw new \InvalidArgumentException('Na divisão etária "' . trim((string) ($row['name'] ?? '')) . '", a idade mínima não pode passar da máxima.');
            }

            $clean[] = [
                'id'   => (int) ($row['id'] ?? 0),
                'code' => $this->requireCode($row['code'] ?? ''),
                'name' => $this->requireText($row['name'] ?? '', 'o nome da divisão etária', 60),
                'age_min' => $min, 'age_max' => $max, 'sort_order' => $position + 1,
            ];
        }

        $this->assertUnique(array_column($clean, 'code'), 'Código de divisão etária repetido');

        $this->replaceRows($rulesetId, 'age_divisions', $clean, ['code', 'name', 'age_min', 'age_max', 'sort_order']);
    }

    public function saveBeltDivisions(int $rulesetId, array $rows): void
    {
        $clean = [];
        foreach (array_values($rows) as $position => $row) {
            $min = (int) ($row['level_min'] ?? 0);
            $max = (int) ($row['level_max'] ?? 0);

            if ($min < 1 || $max > 20 || $min > $max) {
                throw new \InvalidArgumentException('Os níveis da divisão de faixa devem ficar entre 1 e 20, com o mínimo não maior que o máximo.');
            }

            $clean[] = [
                'id'   => (int) ($row['id'] ?? 0),
                'code' => $this->requireCode($row['code'] ?? ''),
                'name' => $this->requireText($row['name'] ?? '', 'o nome da divisão de faixa', 60),
                'level_min' => $min, 'level_max' => $max, 'sort_order' => $position + 1,
            ];
        }

        $this->assertUnique(array_column($clean, 'code'), 'Código de divisão de faixa repetido');

        $this->replaceRows($rulesetId, 'belt_divisions', $clean, ['code', 'name', 'level_min', 'level_max', 'sort_order']);
    }

    public function saveWeightClasses(int $rulesetId, array $rows): void
    {
        $ageIds   = $this->idsOf('age_divisions', $rulesetId);
        $position = [];
        $clean    = [];

        foreach ($rows as $row) {
            $ageId  = (int) ($row['age_division_id'] ?? 0);
            $gender = (string) ($row['gender'] ?? '');

            if (!in_array($ageId, $ageIds, true) || !in_array($gender, ['M', 'F'], true)) {
                throw new \InvalidArgumentException('Divisão de peso ligada a uma idade ou sexo inválido.');
            }

            $min = $this->nullableDecimal($row['weight_min'] ?? null);
            $max = $this->nullableDecimal($row['weight_max'] ?? null);
            $name = $this->requireText($row['name'] ?? '', 'o nome da divisão de peso', 60);

            if ($min === null && $max === null) {
                throw new \InvalidArgumentException("A divisão de peso \"$name\" precisa de peso mínimo, máximo ou os dois.");
            }
            if ($min !== null && $max !== null && $min >= $max) {
                throw new \InvalidArgumentException("Na divisão de peso \"$name\", o peso mínimo deve ser menor que o máximo.");
            }

            $group = $ageId . $gender;
            $position[$group] = ($position[$group] ?? 0) + 1;

            $clean[] = [
                'id' => (int) ($row['id'] ?? 0), 'age_division_id' => $ageId, 'gender' => $gender, 'name' => $name,
                'weight_min' => $min, 'weight_max' => $max, 'sort_order' => $position[$group],
            ];
        }

        $this->replaceRows($rulesetId, 'weight_classes', $clean, ['age_division_id', 'gender', 'name', 'weight_min', 'weight_max', 'sort_order']);
    }

    public function saveDisciplines(int $rulesetId, array $rows): void
    {
        $ageIds = $this->idsOf('age_divisions', $rulesetId);
        $clean  = [];
        $links  = [];

        foreach (array_values($rows) as $position => $row) {
            $modality = (string) ($row['modality'] ?? '');
            $format   = (string) ($row['format'] ?? '');

            if (!in_array($modality, ['kata', 'kumite'], true) || !in_array($format, ['individual', 'team'], true)) {
                throw new \InvalidArgumentException('Modalidade ou formato de disciplina inválido.');
            }

            $name     = $this->requireText($row['name'] ?? '', 'o nome da disciplina', 80);
            $teamSize = $this->nullableInt($row['team_size'] ?? null);

            if ($format === 'team' && ($teamSize === null || $teamSize < 2)) {
                throw new \InvalidArgumentException("Informe o tamanho da equipe (mínimo 2) na disciplina \"$name\".");
            }

            $selected = array_values(array_unique(array_map('intval', (array) ($row['ages'] ?? []))));
            if (array_diff($selected, $ageIds)) {
                throw new \InvalidArgumentException("A disciplina \"$name\" usa uma divisão etária que não existe neste regulamento.");
            }

            $code = $this->requireCode($row['code'] ?? '');
            $clean[] = [
                'id' => (int) ($row['id'] ?? 0), 'code' => $code, 'name' => $name, 'modality' => $modality, 'format' => $format,
                'split_gender' => empty($row['split_gender']) ? 0 : 1,
                'split_belt'   => empty($row['split_belt']) ? 0 : 1,
                'split_weight' => empty($row['split_weight']) ? 0 : 1,
                'team_size'    => $format === 'team' ? $teamSize : null,
                'team_reserves' => $format === 'team' ? $this->nullableInt($row['team_reserves'] ?? null) : null,
                'sort_order'   => $position + 1,
            ];
            $links[$code] = $selected;
        }

        $this->assertUnique(array_column($clean, 'code'), 'Código de disciplina repetido');

        $this->transaction(function () use ($rulesetId, $clean, $links) {
            $ids = $this->replaceRows(
                $rulesetId, 'ruleset_disciplines', $clean,
                ['code', 'name', 'modality', 'format', 'split_gender', 'split_belt', 'split_weight', 'team_size', 'team_reserves', 'sort_order'],
                true
            );

            foreach ($clean as $i => $row) {
                $disciplineId = $ids[$i];
                $this->db->query("DELETE FROM ruleset_discipline_ages WHERE discipline_id = :d", ['d' => $disciplineId]);

                foreach ($links[$row['code']] as $ageId) {
                    $this->db->query(
                        "INSERT INTO ruleset_discipline_ages (discipline_id, age_division_id) VALUES (:d, :a)",
                        ['d' => $disciplineId, 'a' => $ageId]
                    );
                }
            }
        });
    }

    /**
     * @param string[] $columns colunas gravadas além de id e ruleset_id
     * @return int[] id de cada linha, na ordem recebida
     */
    private function replaceRows(int $rulesetId, string $table, array $rows, array $columns, bool $inTransaction = false): array
    {
        $work = function () use ($rulesetId, $table, $rows, $columns) {
            $existing = $this->idsOf($table, $rulesetId);
            $kept     = [];
            $ids      = [];

            foreach ($rows as $row) {
                $params = array_intersect_key($row, array_flip($columns));

                if ($row['id'] > 0) {
                    if (!in_array($row['id'], $existing, true)) {
                        throw new \InvalidArgumentException('Linha não pertence a este regulamento.');
                    }

                    $sets = implode(', ', array_map(static fn ($c) => "$c = :$c", $columns));
                    $this->db->query("UPDATE $table SET $sets WHERE id = :id", $params + ['id' => $row['id']]);
                    $kept[] = $ids[] = $row['id'];
                    continue;
                }

                $cols = implode(', ', $columns);
                $vals = implode(', ', array_map(static fn ($c) => ":$c", $columns));
                $this->db->query(
                    "INSERT INTO $table (ruleset_id, $cols) VALUES (:ruleset_id, $vals)",
                    $params + ['ruleset_id' => $rulesetId]
                );
                $kept[] = $ids[] = (int) $this->db->lastInsertId();
            }

            foreach (array_diff($existing, $kept) as $removedId) {
                $this->db->query("DELETE FROM $table WHERE id = :id", ['id' => $removedId]);
            }

            return $ids;
        };

        return $inTransaction ? $work() : $this->transaction($work);
    }

    /** @return int[] */
    private function idsOf(string $table, int $rulesetId): array
    {
        return array_map(
            'intval',
            $this->db->query("SELECT id FROM $table WHERE ruleset_id = :r", ['r' => $rulesetId])->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    private function transaction(callable $work): mixed
    {
        $this->db->beginTransaction();

        try {
            $result = $work();
            $this->db->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function requireName(string $name): string
    {
        return $this->requireText($name, 'o nome do regulamento', 120);
    }

    private function requireText(mixed $value, string $label, int $max): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new \InvalidArgumentException("Informe $label.");
        }

        return mb_substr($value, 0, $max);
    }

    private function requireCode(mixed $value): string
    {
        $code = strtoupper(trim((string) $value));

        if (!preg_match('/^[A-Z0-9-]{1,20}$/', $code)) {
            throw new \InvalidArgumentException('Código inválido. Use só letras, números e hífen (até 20 caracteres), sem espaços.');
        }

        return $code;
    }

    private function agePolicy(string $policy): string
    {
        return $policy === 'year_end' ? 'year_end' : 'event_date';
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function nullableDecimal(mixed $value): ?float
    {
        return $value === null || trim((string) $value) === '' ? null : (float) str_replace(',', '.', (string) $value);
    }

    private function assertUnique(array $values, string $message): void
    {
        $duplicates = array_keys(array_filter(array_count_values($values), static fn ($n) => $n > 1));

        if ($duplicates) {
            throw new \InvalidArgumentException("$message: " . implode(', ', $duplicates) . '.');
        }
    }
}

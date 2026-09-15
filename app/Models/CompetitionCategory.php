<?php

namespace Models;

use Core\Database;
use PDO;

/**
 * Catalogo de categorias de disputa da federacao (kata e kumite, individual e
 * equipe). E global: as mesmas categorias valem para todos os campeonatos.
 */
class CompetitionCategory
{
    public const MODALITIES = [
        'kata'   => 'Kata',
        'kumite' => 'Kumite',
    ];

    public const ENTRY_TYPES = [
        'individual' => 'Individual',
        'team'       => 'Equipe',
    ];

    public const GENDERS = [
        'M' => 'Masculino',
        'F' => 'Feminino',
        'X' => 'Misto',
    ];

    public const BELT_GROUPS = [
        'colored' => 'Faixa Colorida',
        'black'   => 'Faixa Preta',
        'any'     => 'Qualquer graduação',
    ];

    public static function all(array $filters = []): array
    {
        $where  = [];
        $params = [];

        foreach (['entry_type', 'modality', 'gender'] as $field) {
            if (!empty($filters[$field])) {
                $where[] = "$field = :$field";
                $params[$field] = $filters[$field];
            }
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $where[] = 'is_active = :is_active';
            $params['is_active'] = (int) $filters['is_active'];
        }

        $sql = 'SELECT * FROM competition_categories';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY sort_order ASC, name ASC';

        try {
            return Database::getInstance()->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('CompetitionCategory::all error: ' . $e->getMessage());
            return [];
        }
    }

    public static function find(int $id): ?array
    {
        try {
            $row = Database::getInstance()->query(
                "SELECT * FROM competition_categories WHERE id = :id LIMIT 1",
                ['id' => $id]
            )->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (\Exception $e) {
            error_log('CompetitionCategory::find error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Categorias individuais compativeis com o atleta, cruzando sexo, idade e
     * graduacao. O peso nao entra aqui: para kumite ele e pedido num passo
     * separado e validado contra a faixa da categoria escolhida.
     */
    public static function forIndividual(string $gender, string $beltGroup, int $age): array
    {
        try {
            return Database::getInstance()->query(
                "SELECT * FROM competition_categories
                 WHERE is_active = 1
                   AND entry_type = 'individual'
                   AND (gender = :gender OR gender = 'X')
                   AND (belt_group = :belt_group OR belt_group = 'any')
                   AND (age_min IS NULL OR age_min <= :age_low)
                   AND (age_max IS NULL OR age_max >= :age_high)
                 ORDER BY modality ASC, sort_order ASC",
                [
                    'gender'     => $gender,
                    'belt_group' => $beltGroup,
                    'age_low'    => $age,
                    'age_high'   => $age,
                ]
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('CompetitionCategory::forIndividual error: ' . $e->getMessage());
            return [];
        }
    }

    /** Categorias de equipe ativas, agrupadas por modalidade na tela. */
    public static function teams(): array
    {
        return self::all(['entry_type' => 'team', 'is_active' => 1]);
    }

    public static function matchesAge(array $category, int $age): bool
    {
        if ($category['age_min'] !== null && $age < (int) $category['age_min']) {
            return false;
        }

        if ($category['age_max'] !== null && $age > (int) $category['age_max']) {
            return false;
        }

        return true;
    }

    public static function matchesGender(array $category, string $gender): bool
    {
        return $category['gender'] === 'X' || $category['gender'] === $gender;
    }

    public static function requiresWeight(array $category): bool
    {
        return $category['modality'] === 'kumite'
            && $category['entry_type'] === 'individual'
            && ($category['weight_min'] !== null || $category['weight_max'] !== null);
    }

    /**
     * weight_min e exclusivo e weight_max e inclusivo, para que "ate 50kg" e
     * "acima de 50kg" cubram todos os pesos sem se sobrepor.
     */
    public static function weightFits(array $category, ?float $weight): bool
    {
        if (!self::requiresWeight($category)) {
            return true;
        }

        if ($weight === null || $weight <= 0) {
            return false;
        }

        if ($category['weight_min'] !== null && $weight <= (float) $category['weight_min']) {
            return false;
        }

        if ($category['weight_max'] !== null && $weight > (float) $category['weight_max']) {
            return false;
        }

        return true;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();

        $db->query(
            "INSERT INTO competition_categories
                (name, modality, entry_type, gender, age_min, age_max, age_label,
                 belt_group, weight_min, weight_max, team_size, sort_order, is_active)
             VALUES
                (:name, :modality, :entry_type, :gender, :age_min, :age_max, :age_label,
                 :belt_group, :weight_min, :weight_max, :team_size, :sort_order, :is_active)",
            self::sanitize($data)
        );

        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $params = self::sanitize($data);
        $params['id'] = $id;

        Database::getInstance()->query(
            "UPDATE competition_categories SET
                name = :name, modality = :modality, entry_type = :entry_type, gender = :gender,
                age_min = :age_min, age_max = :age_max, age_label = :age_label,
                belt_group = :belt_group, weight_min = :weight_min, weight_max = :weight_max,
                team_size = :team_size, sort_order = :sort_order, is_active = :is_active
             WHERE id = :id",
            $params
        );
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->query(
            "DELETE FROM competition_categories WHERE id = :id",
            ['id' => $id]
        );
    }

    /** Quantas inscricoes (individuais + equipes) ja apontam para a categoria. */
    public static function usageCount(int $id): int
    {
        try {
            $db = Database::getInstance();

            $entries = (int) $db->query(
                "SELECT COUNT(*) FROM championship_entries WHERE category_id = :id",
                ['id' => $id]
            )->fetchColumn();

            $teams = (int) $db->query(
                "SELECT COUNT(*) FROM championship_teams WHERE category_id = :id",
                ['id' => $id]
            )->fetchColumn();

            return $entries + $teams;
        } catch (\Exception $e) {
            error_log('CompetitionCategory::usageCount error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Faixas etarias usadas pela federacao. Kata equipe roda so em duas bandas;
     * as demais disputas usam as seis bandas nomeadas.
     */
    public const AGE_BANDS = [
        ['label' => 'Mirim',           'min' => 7,  'max' => 10],
        ['label' => 'Infantil',        'min' => 11, 'max' => 12],
        ['label' => 'Infanto Juvenil', 'min' => 13, 'max' => 14],
        ['label' => 'Juvenil',         'min' => 15, 'max' => 17],
        ['label' => 'Adulto',          'min' => 18, 'max' => 35],
        ['label' => 'Master',          'min' => 36, 'max' => null],
    ];

    public const TEAM_KATA_BANDS = [
        ['label' => 'Até 14 anos',   'min' => null, 'max' => 14],
        ['label' => '15 anos acima', 'min' => 15,   'max' => null],
    ];

    public const DEFAULT_TEAM_SIZE = 3;

    /**
     * Catalogo inicial gravado na primeira execucao. Depois disso e o
     * Presidente quem manda: nada aqui sobrescreve o que estiver no banco.
     */
    public static function defaultCatalog(): array
    {
        // weight_min e exclusivo e weight_max inclusivo, para "ate 50kg" e
        // "acima de 50kg" cobrirem todos os pesos sem sobreposicao nem vao
        $weightRanges = [
            ['label' => 'até 50kg',      'min' => null,  'max' => 50.00],
            ['label' => 'acima de 50kg', 'min' => 50.00, 'max' => null],
        ];

        $genders    = ['M' => 'Masculino', 'F' => 'Feminino'];
        $beltGroups = ['colored' => 'Faixa Colorida', 'black' => 'Faixa Preta'];

        $rows  = [];
        $order = 0;

        foreach ($genders as $gender => $genderLabel) {
            foreach (self::AGE_BANDS as $band) {
                $ageLabel = self::formatAgeLabel($band);

                foreach ($beltGroups as $beltGroup => $beltLabel) {
                    $rows[] = self::catalogRow(
                        "Kata Individual — $genderLabel — $ageLabel — $beltLabel",
                        'kata',
                        'individual',
                        $gender,
                        $band,
                        $beltGroup,
                        null,
                        null,
                        null,
                        ++$order
                    );

                    foreach ($weightRanges as $weight) {
                        $rows[] = self::catalogRow(
                            "Kumite Individual — $genderLabel — $ageLabel — $beltLabel — {$weight['label']}",
                            'kumite',
                            'individual',
                            $gender,
                            $band,
                            $beltGroup,
                            $weight['min'],
                            $weight['max'],
                            null,
                            ++$order
                        );
                    }
                }
            }
        }

        foreach ($genders as $gender => $genderLabel) {
            foreach (self::TEAM_KATA_BANDS as $band) {
                $rows[] = self::catalogRow(
                    "Kata Equipe — $genderLabel — {$band['label']}",
                    'kata',
                    'team',
                    $gender,
                    $band,
                    'any',
                    null,
                    null,
                    self::DEFAULT_TEAM_SIZE,
                    ++$order
                );
            }

            foreach (self::AGE_BANDS as $band) {
                $rows[] = self::catalogRow(
                    "Kumite Equipe (Revezamento) — $genderLabel — " . self::formatAgeLabel($band),
                    'kumite',
                    'team',
                    $gender,
                    $band,
                    'any',
                    null,
                    null,
                    self::DEFAULT_TEAM_SIZE,
                    ++$order
                );
            }
        }

        return $rows;
    }

    private static function catalogRow(
        string $name,
        string $modality,
        string $entryType,
        string $gender,
        array $band,
        string $beltGroup,
        ?float $weightMin,
        ?float $weightMax,
        ?int $teamSize,
        int $order
    ): array {
        return [
            'name'       => $name,
            'modality'   => $modality,
            'entry_type' => $entryType,
            'gender'     => $gender,
            'age_min'    => $band['min'],
            'age_max'    => $band['max'],
            'age_label'  => $band['label'],
            'belt_group' => $beltGroup,
            'weight_min' => $weightMin,
            'weight_max' => $weightMax,
            'team_size'  => $teamSize,
            'sort_order' => $order,
        ];
    }

    public static function formatAgeLabel(array $band): string
    {
        return match (true) {
            $band['min'] !== null && $band['max'] !== null => "{$band['label']} ({$band['min']} a {$band['max']})",
            $band['min'] !== null                         => "{$band['label']} ({$band['min']} acima)",
            default                                       => "{$band['label']} (até {$band['max']})",
        };
    }

    private static function sanitize(array $data): array
    {
        $entryType = isset(self::ENTRY_TYPES[$data['entry_type'] ?? '']) ? $data['entry_type'] : 'individual';
        $modality  = isset(self::MODALITIES[$data['modality'] ?? '']) ? $data['modality'] : 'kata';

        $weightMin = ($modality === 'kumite' && $entryType === 'individual' && $data['weight_min'] !== null && $data['weight_min'] !== '')
            ? (float) $data['weight_min'] : null;
        $weightMax = ($modality === 'kumite' && $entryType === 'individual' && $data['weight_max'] !== null && $data['weight_max'] !== '')
            ? (float) $data['weight_max'] : null;

        return [
            'name'       => trim((string) ($data['name'] ?? '')),
            'modality'   => $modality,
            'entry_type' => $entryType,
            'gender'     => isset(self::GENDERS[$data['gender'] ?? '']) ? $data['gender'] : 'X',
            'age_min'    => ($data['age_min'] ?? '') !== '' ? (int) $data['age_min'] : null,
            'age_max'    => ($data['age_max'] ?? '') !== '' ? (int) $data['age_max'] : null,
            'age_label'  => trim((string) ($data['age_label'] ?? '')) ?: null,
            'belt_group' => isset(self::BELT_GROUPS[$data['belt_group'] ?? '']) ? $data['belt_group'] : 'any',
            'weight_min' => $weightMin,
            'weight_max' => $weightMax,
            'team_size'  => $entryType === 'team' && ($data['team_size'] ?? '') !== '' ? (int) $data['team_size'] : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active'  => !empty($data['is_active']) ? 1 : 0,
        ];
    }
}

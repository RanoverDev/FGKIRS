<?php

namespace Models;

use Core\Database;
use PDO;

/**
 * Atleta inscrito em um campeonato.
 *
 * Guarda um snapshot de nome, nascimento e graduacao: se o aluno for promovido
 * depois do evento, a inscricao daquele campeonato nao muda retroativamente.
 */
class ChampionshipAthlete
{
    public const BELT_GROUPS = [
        'colored' => 'Faixa Colorida',
        'black'   => 'Faixa Preta',
    ];

    public const GENDERS = [
        'M' => 'Masculino',
        'F' => 'Feminino',
    ];

    public static function styleOptions(): array
    {
        return Database::getInstance()->query(
            "SELECT id, name FROM martial_arts_styles ORDER BY name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function graduationOptions(): array
    {
        return Database::getInstance()->query(
            "SELECT g.id, g.belt_name, g.belt_color, g.is_black_belt, g.style_id, s.name AS style_name
             FROM graduations g
             JOIN martial_arts_styles s ON s.id = g.style_id
             ORDER BY s.name ASC, g.order_rank ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Idade completada na data da competicao. */
    public static function ageOn(string $birthDate, string $referenceDate): int
    {
        try {
            $birth = new \DateTimeImmutable($birthDate);
            $ref   = new \DateTimeImmutable($referenceDate);
        } catch (\Exception $e) {
            return 0;
        }

        return (int) $birth->diff($ref)->y;
    }

    public static function find(int $id): ?array
    {
        try {
            $row = Database::getInstance()->query(
                "SELECT a.*, g.belt_name, g.belt_color, s.name AS style_name, d.name AS dojo_name
                 FROM championship_athletes a
                 LEFT JOIN graduations g ON g.id = a.graduation_id
                 LEFT JOIN martial_arts_styles s ON s.id = a.style_id
                 LEFT JOIN dojos d ON d.id = a.dojo_id
                 WHERE a.id = :id
                 LIMIT 1",
                ['id' => $id]
            )->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (\Exception $e) {
            error_log('ChampionshipAthlete::find error: ' . $e->getMessage());
            return null;
        }
    }

    /** Atletas do dojo no evento, ja com as categorias inscritas anexadas. */
    public static function forDojo(int $championshipId, int $dojoId): array
    {
        try {
            $db = Database::getInstance();

            $athletes = $db->query(
                "SELECT a.*, g.belt_name, g.belt_color, s.name AS style_name
                 FROM championship_athletes a
                 LEFT JOIN graduations g ON g.id = a.graduation_id
                 LEFT JOIN martial_arts_styles s ON s.id = a.style_id
                 WHERE a.championship_id = :championship_id AND a.dojo_id = :dojo_id
                 ORDER BY a.name ASC",
                ['championship_id' => $championshipId, 'dojo_id' => $dojoId]
            )->fetchAll(PDO::FETCH_ASSOC);

            if (!$athletes) {
                return [];
            }

            $entries = $db->query(
                "SELECT e.*, c.name AS category_name, c.modality, c.entry_type
                 FROM championship_entries e
                 JOIN championship_athletes a ON a.id = e.championship_athlete_id
                 JOIN competition_categories c ON c.id = e.category_id
                 WHERE a.championship_id = :championship_id AND a.dojo_id = :dojo_id
                 ORDER BY c.modality ASC, c.sort_order ASC",
                ['championship_id' => $championshipId, 'dojo_id' => $dojoId]
            )->fetchAll(PDO::FETCH_ASSOC);

            $byAthlete = [];
            foreach ($entries as $entry) {
                $byAthlete[(int) $entry['championship_athlete_id']][] = $entry;
            }

            foreach ($athletes as &$athlete) {
                $athlete['entries'] = $byAthlete[(int) $athlete['id']] ?? [];
            }

            return $athletes;
        } catch (\Exception $e) {
            error_log('ChampionshipAthlete::forDojo error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Alunos do dojo disponiveis para inscricao (autocomplete). Ja exclui quem
     * foi inscrito neste campeonato.
     */
    public static function searchDojoStudents(int $championshipId, int $dojoId, string $term): array
    {
        try {
            return Database::getInstance()->query(
                "SELECT u.id, u.name, u.photo,
                        ap.birth_date, ap.gender, ap.weight, ap.height, ap.is_para_karate,
                        ap.style_id, ap.graduation_id,
                        g.belt_name, g.belt_color, g.is_black_belt,
                        s.name AS style_name
                 FROM users u
                 LEFT JOIN athlete_profiles ap ON ap.user_id = u.id
                 LEFT JOIN graduations g ON g.id = ap.graduation_id
                 LEFT JOIN martial_arts_styles s ON s.id = ap.style_id
                 WHERE u.dojo_id = :dojo_id
                   AND u.name LIKE :term
                   AND u.id NOT IN (
                        SELECT user_id FROM championship_athletes
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
            error_log('ChampionshipAthlete::searchDojoStudents error: ' . $e->getMessage());
            return [];
        }
    }

    /** Ficha do aluno do dojo, usada para montar o snapshot da inscricao. */
    public static function dojoStudent(int $dojoId, int $userId): ?array
    {
        try {
            $row = Database::getInstance()->query(
                "SELECT u.id, u.name,
                        ap.birth_date, ap.gender, ap.weight, ap.height, ap.is_para_karate,
                        ap.style_id, ap.graduation_id
                 FROM users u
                 LEFT JOIN athlete_profiles ap ON ap.user_id = u.id
                 WHERE u.id = :user_id AND u.dojo_id = :dojo_id
                 LIMIT 1",
                ['user_id' => $userId, 'dojo_id' => $dojoId]
            )->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (\Exception $e) {
            error_log('ChampionshipAthlete::dojoStudent error: ' . $e->getMessage());
            return null;
        }
    }

    /** 'black' ou 'colored' a partir da flag da graduacao. */
    public static function beltGroupFor(?int $graduationId): string
    {
        if (!$graduationId) {
            return 'colored';
        }

        try {
            $isBlack = Database::getInstance()->query(
                "SELECT is_black_belt FROM graduations WHERE id = :id LIMIT 1",
                ['id' => $graduationId]
            )->fetchColumn();

            return $isBlack ? 'black' : 'colored';
        } catch (\Exception $e) {
            error_log('ChampionshipAthlete::beltGroupFor error: ' . $e->getMessage());
            return 'colored';
        }
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();

        $db->query(
            "INSERT INTO championship_athletes
                (championship_id, dojo_id, user_id, name, gender, birth_date,
                 style_id, graduation_id, belt_group, weight, height, is_guest,
                 is_para_karate, registered_by)
             VALUES
                (:championship_id, :dojo_id, :user_id, :name, :gender, :birth_date,
                 :style_id, :graduation_id, :belt_group, :weight, :height, :is_guest,
                 :is_para_karate, :registered_by)",
            $data
        );

        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $data['id'] = $id;

        Database::getInstance()->query(
            "UPDATE championship_athletes SET
                name = :name, gender = :gender, birth_date = :birth_date,
                style_id = :style_id, graduation_id = :graduation_id,
                belt_group = :belt_group, weight = :weight, height = :height,
                is_para_karate = :is_para_karate
             WHERE id = :id",
            $data
        );
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->query(
            "DELETE FROM championship_athletes WHERE id = :id",
            ['id' => $id]
        );
    }

    public static function entries(int $athleteId): array
    {
        try {
            return Database::getInstance()->query(
                "SELECT e.*, c.name AS category_name, c.modality
                 FROM championship_entries e
                 JOIN competition_categories c ON c.id = e.category_id
                 WHERE e.championship_athlete_id = :id
                 ORDER BY c.modality ASC, c.sort_order ASC",
                ['id' => $athleteId]
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('ChampionshipAthlete::entries error: ' . $e->getMessage());
            return [];
        }
    }

    public static function addEntry(int $athleteId, int $categoryId, ?float $weight): void
    {
        Database::getInstance()->query(
            "INSERT IGNORE INTO championship_entries (championship_athlete_id, category_id, weight)
             VALUES (:athlete_id, :category_id, :weight)",
            ['athlete_id' => $athleteId, 'category_id' => $categoryId, 'weight' => $weight]
        );
    }

    public static function findEntry(int $entryId): ?array
    {
        try {
            $row = Database::getInstance()->query(
                "SELECT e.*, a.dojo_id, a.championship_id
                 FROM championship_entries e
                 JOIN championship_athletes a ON a.id = e.championship_athlete_id
                 WHERE e.id = :id
                 LIMIT 1",
                ['id' => $entryId]
            )->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (\Exception $e) {
            error_log('ChampionshipAthlete::findEntry error: ' . $e->getMessage());
            return null;
        }
    }

    public static function removeEntry(int $entryId): void
    {
        Database::getInstance()->query(
            "DELETE FROM championship_entries WHERE id = :id",
            ['id' => $entryId]
        );
    }

    /**
     * Atletas do dojo que cabem numa categoria de equipe. A equipe so pode ser
     * montada com quem ja foi cadastrado na aba Atletas.
     */
    public static function eligibleForTeam(
        int $championshipId,
        int $dojoId,
        array $category,
        string $eventDate,
        ?int $excludeTeamId = null
    ): array {
        $athletes = self::forDojo($championshipId, $dojoId);
        $eligible = [];

        foreach ($athletes as $athlete) {
            $age = self::ageOn($athlete['birth_date'], $eventDate);

            if (!CompetitionCategory::matchesAge($category, $age)) {
                continue;
            }

            if (!CompetitionCategory::matchesGender($category, $athlete['gender'])) {
                continue;
            }

            $athlete['age'] = $age;
            $eligible[] = $athlete;
        }

        return $eligible;
    }

    /**
     * Todos os atletas do evento, ja com as categorias anexadas, para o painel
     * e a impressao do Presidente.
     */
    public static function forChampionship(int $championshipId): array
    {
        try {
            $db = Database::getInstance();

            $athletes = $db->query(
                "SELECT a.*, d.name AS dojo_name, d.city AS dojo_city,
                        g.belt_name, g.belt_color, s.name AS style_name
                 FROM championship_athletes a
                 JOIN dojos d ON d.id = a.dojo_id
                 LEFT JOIN graduations g ON g.id = a.graduation_id
                 LEFT JOIN martial_arts_styles s ON s.id = a.style_id
                 WHERE a.championship_id = :id
                 ORDER BY d.name ASC, a.name ASC",
                ['id' => $championshipId]
            )->fetchAll(PDO::FETCH_ASSOC);

            if (!$athletes) {
                return [];
            }

            $entries = $db->query(
                "SELECT e.*, c.name AS category_name, c.modality
                 FROM championship_entries e
                 JOIN championship_athletes a ON a.id = e.championship_athlete_id
                 JOIN competition_categories c ON c.id = e.category_id
                 WHERE a.championship_id = :id
                 ORDER BY c.modality ASC, c.sort_order ASC",
                ['id' => $championshipId]
            )->fetchAll(PDO::FETCH_ASSOC);

            $byAthlete = [];
            foreach ($entries as $entry) {
                $byAthlete[(int) $entry['championship_athlete_id']][] = $entry;
            }

            foreach ($athletes as &$athlete) {
                $athlete['entries'] = $byAthlete[(int) $athlete['id']] ?? [];
            }

            return $athletes;
        } catch (\Exception $e) {
            error_log('ChampionshipAthlete::forChampionship error: ' . $e->getMessage());
            return [];
        }
    }
}

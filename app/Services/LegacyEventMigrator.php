<?php

namespace Services;

use Domain\Championship\AgePolicy;
use PDO;

/**
 * Leva um evento do modelo antigo (legacy) para o novo (v2) como histórico.
 *
 * Cada evento roda numa transação própria e só fica gravado se as contagens e as inscrições
 * (atleta × categoria, equipes e integrantes) baterem antes e depois. Qualquer divergência desfaz
 * aquele evento e é reportada. As tabelas antigas não são tocadas: só lidas.
 *
 * Recebe o PDO para rodar tanto no script de linha de comando quanto nos testes.
 */
final class LegacyEventMigrator
{
    /** [código, nome] da disciplina equivalente a cada par modalidade/tipo do catálogo antigo. */
    private const DISCIPLINES = [
        'kata:individual'   => ['KTI', 'Kata Individual'],
        'kumite:individual' => ['KMI', 'Kumite Individual'],
        'kata:team'         => ['KTE', 'Kata Equipe'],
        'kumite:team'       => ['KME', 'Kumite Equipe (Revezamento)'],
    ];

    /** Níveis comuns de faixa colorida e preta, como no seed do regulamento atual. */
    private const BELT_LEVELS = ['colored' => [1, 10], 'black' => [11, 20]];

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array[] eventos ainda no modelo antigo */
    public function pending(): array
    {
        return $this->pdo->query(
            "SELECT * FROM championships WHERE registration_model = 'legacy' ORDER BY event_date, id"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array{id: int, title: string, status: string, before: array, after: array, messages: string[]}
     *         status: migrated | dry-run | skipped | failed
     */
    public function migrate(array $championship, bool $dryRun): array
    {
        $id     = (int) $championship['id'];
        $report = ['id' => $id, 'title' => $championship['title'], 'status' => 'failed', 'before' => [], 'after' => [], 'messages' => []];

        if ($blocker = $this->blocker($championship)) {
            return ['status' => 'skipped', 'messages' => [$blocker]] + $report;
        }

        $report['before'] = $this->legacyCounts($id);
        $this->pdo->beginTransaction();

        try {
            $this->copy($championship);
            $report['after'] = $this->v2Counts($id);

            $problems = $this->compare($id, $report['before'], $report['after']);
            if ($problems) {
                throw new \RuntimeException(implode(' | ', $problems));
            }

            $this->pdo->prepare("UPDATE championships SET registration_model = 'v2' WHERE id = :id")->execute(['id' => $id]);

            $dryRun ? $this->pdo->rollBack() : $this->pdo->commit();
            $report['status'] = $dryRun ? 'dry-run' : 'migrated';
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $report['status']     = 'failed';
            $report['messages'][] = $e->getMessage();
            $report['after']      = [];
        }

        return $report;
    }

    /** Motivo para não migrar o evento agora, ou null. Inscrição aberta ou atleta sem cadastro travam. */
    private function blocker(array $championship): ?string
    {
        $now = time();

        if ($championship['status'] !== 'draft' && $championship['status'] !== 'closed'
            && $now >= strtotime($championship['registration_start']) && $now <= strtotime($championship['registration_end'])) {
            return 'inscrições ainda abertas: migre depois do encerramento';
        }

        $orphans = $this->pdo->prepare(
            "SELECT name FROM championship_athletes WHERE championship_id = :id AND athlete_id IS NULL"
        );
        $orphans->execute(['id' => $championship['id']]);
        $names = $orphans->fetchAll(PDO::FETCH_COLUMN);

        if ($names) {
            return count($names) . ' atleta(s) sem athlete_id (' . implode(', ', array_slice($names, 0, 5))
                . (count($names) > 5 ? '…' : '') . '): rode scripts/migrate_athletes.php antes';
        }

        return null;
    }

    private function copy(array $championship): void
    {
        $id     = (int) $championship['id'];
        $status = strtotime($championship['event_date']) < strtotime('today') ? 'confirmed' : 'submitted';
        $policy = AgePolicy::fromColumn($championship['age_policy'] ?? null);

        // 1. Categorias usadas no evento, com os limites do catálogo antigo copiados
        $categoryMap = [];
        $used = $this->pdo->prepare(
            "SELECT c.* FROM competition_categories c
             WHERE c.id IN (SELECT e.category_id FROM championship_entries e
                              JOIN championship_athletes a ON a.id = e.championship_athlete_id
                             WHERE a.championship_id = :a
                            UNION SELECT t.category_id FROM championship_teams t WHERE t.championship_id = :t)
             ORDER BY c.sort_order, c.id"
        );
        $used->execute(['a' => $id, 't' => $id]);

        $insertCategory = $this->pdo->prepare(
            "INSERT INTO event_categories
                (championship_id, code, name, short_name, discipline_code, discipline_name, modality, format, gender,
                 age_min, age_max, belt_level_min, belt_level_max, weight_min, weight_max, team_size, status, sort_order)
             VALUES
                (:championship_id, :code, :name, :name_short, :discipline_code, :discipline_name, :modality, :format, :gender,
                 :age_min, :age_max, :belt_min, :belt_max, :weight_min, :weight_max, :team_size, 'closed', :sort_order)"
        );

        foreach ($used->fetchAll(PDO::FETCH_ASSOC) as $category) {
            [$disciplineCode, $disciplineName] = self::DISCIPLINES[$category['modality'] . ':' . $category['entry_type']];
            [$beltMin, $beltMax] = self::BELT_LEVELS[$category['belt_group']] ?? [null, null];

            $insertCategory->execute([
                'championship_id' => $id, 'code' => 'LEG-' . $category['id'], 'name' => $category['name'],
                'name_short' => mb_substr($category['name'], 0, 160), 'discipline_code' => $disciplineCode,
                'discipline_name' => $disciplineName, 'modality' => $category['modality'], 'format' => $category['entry_type'],
                'gender' => $category['gender'], 'age_min' => $category['age_min'], 'age_max' => $category['age_max'],
                'belt_min' => $beltMin, 'belt_max' => $beltMax, 'weight_min' => $category['weight_min'],
                'weight_max' => $category['weight_max'], 'team_size' => $category['entry_type'] === 'team' ? $category['team_size'] : null,
                'sort_order' => $category['sort_order'],
            ]);
            $categoryMap[(int) $category['id']] = (int) $this->pdo->lastInsertId();
        }

        // 2. Atletas do evento viram inscrições, com a foto dos dados que a linha antiga já guardava
        $registrations = [];
        $athletes = $this->pdo->prepare(
            "SELECT a.*, g.level AS belt_level FROM championship_athletes a
             LEFT JOIN graduations g ON g.id = a.graduation_id
             WHERE a.championship_id = :id ORDER BY a.id"
        );
        $athletes->execute(['id' => $id]);

        $insertRegistration = $this->pdo->prepare(
            "INSERT INTO championship_registrations
                (championship_id, athlete_id, dojo_id, full_name, gender, birth_date, age_at_event, graduation_id,
                 belt_level, declared_weight, is_para_karate, status, submitted_at, registered_by)
             VALUES
                (:championship_id, :athlete_id, :dojo_id, :full_name, :gender, :birth_date, :age, :graduation_id,
                 :belt_level, :weight, :para, :status, :submitted_at, :registered_by)"
        );
        $log = $this->pdo->prepare(
            "INSERT INTO registration_log (registration_id, action, details, user_id) VALUES (:r, 'migrated_from_legacy', :d, :u)"
        );

        foreach ($athletes->fetchAll(PDO::FETCH_ASSOC) as $athlete) {
            $insertRegistration->execute([
                'championship_id' => $id, 'athlete_id' => $athlete['athlete_id'], 'dojo_id' => $athlete['dojo_id'],
                'full_name' => $athlete['name'], 'gender' => $athlete['gender'], 'birth_date' => $athlete['birth_date'],
                'age' => max(0, $policy->ageOn(new \DateTimeImmutable($athlete['birth_date']), new \DateTimeImmutable($championship['event_date']))),
                'graduation_id' => $athlete['graduation_id'], 'belt_level' => $athlete['belt_level'],
                'weight' => $athlete['weight'], 'para' => (int) $athlete['is_para_karate'], 'status' => $status,
                'submitted_at' => $athlete['created_at'], 'registered_by' => $athlete['registered_by'],
            ]);

            $registrationId = (int) $this->pdo->lastInsertId();
            $registrations[(int) $athlete['id']] = $registrationId;

            $log->execute([
                'r' => $registrationId, 'u' => $athlete['registered_by'],
                'd' => json_encode(['legacy_athlete_id' => (int) $athlete['id']]),
            ]);
        }

        // 3. Entradas individuais (o peso do kumite vira o peso declarado quando o atleta não tinha um)
        $entries = $this->pdo->prepare(
            "SELECT e.* FROM championship_entries e
             JOIN championship_athletes a ON a.id = e.championship_athlete_id WHERE a.championship_id = :id ORDER BY e.id"
        );
        $entries->execute(['id' => $id]);

        $insertEntry   = $this->pdo->prepare(
            "INSERT INTO registration_entries (registration_id, event_category_id, status) VALUES (:r, :c, 'active')"
        );
        $fillWeight = $this->pdo->prepare(
            "UPDATE championship_registrations SET declared_weight = :w WHERE id = :id AND declared_weight IS NULL"
        );

        foreach ($entries->fetchAll(PDO::FETCH_ASSOC) as $entry) {
            $insertEntry->execute([
                'r' => $registrations[(int) $entry['championship_athlete_id']],
                'c' => $categoryMap[(int) $entry['category_id']],
            ]);

            if ($entry['weight'] !== null) {
                $fillWeight->execute(['w' => $entry['weight'], 'id' => $registrations[(int) $entry['championship_athlete_id']]]);
            }
        }

        // 4. Equipes e integrantes
        $teams = $this->pdo->prepare("SELECT * FROM championship_teams WHERE championship_id = :id ORDER BY id");
        $teams->execute(['id' => $id]);

        $insertTeam = $this->pdo->prepare(
            "INSERT INTO event_teams (championship_id, dojo_id, event_category_id, name, status, created_at)
             VALUES (:c, :d, :cat, :name, :status, :created)"
        );
        $members = $this->pdo->prepare("SELECT * FROM championship_team_members WHERE team_id = :t ORDER BY position, id");
        $insertMember = $this->pdo->prepare(
            "INSERT INTO event_team_members (team_id, registration_id, role, position) VALUES (:t, :r, 'starter', :p)"
        );

        foreach ($teams->fetchAll(PDO::FETCH_ASSOC) as $team) {
            $insertTeam->execute([
                'c' => $id, 'd' => $team['dojo_id'], 'cat' => $categoryMap[(int) $team['category_id']],
                'name' => $team['name'], 'status' => $status, 'created' => $team['created_at'],
            ]);
            $teamId = (int) $this->pdo->lastInsertId();

            $members->execute(['t' => $team['id']]);
            foreach ($members->fetchAll(PDO::FETCH_ASSOC) as $member) {
                $insertMember->execute([
                    't' => $teamId, 'r' => $registrations[(int) $member['championship_athlete_id']], 'p' => $member['position'],
                ]);
            }
        }
    }

    /** @return array{athletes: int, entries: int, teams: int, members: int, dojos: int} */
    private function legacyCounts(int $id): array
    {
        return $this->counts($id, [
            'athletes' => "SELECT COUNT(*) FROM championship_athletes WHERE championship_id = :id",
            'entries'  => "SELECT COUNT(*) FROM championship_entries e JOIN championship_athletes a ON a.id = e.championship_athlete_id WHERE a.championship_id = :id",
            'teams'    => "SELECT COUNT(*) FROM championship_teams WHERE championship_id = :id",
            'members'  => "SELECT COUNT(*) FROM championship_team_members m JOIN championship_teams t ON t.id = m.team_id WHERE t.championship_id = :id",
            'dojos'    => "SELECT COUNT(DISTINCT dojo_id) FROM championship_athletes WHERE championship_id = :id",
        ]);
    }

    private function v2Counts(int $id): array
    {
        return $this->counts($id, [
            'athletes' => "SELECT COUNT(*) FROM championship_registrations WHERE championship_id = :id",
            'entries'  => "SELECT COUNT(*) FROM registration_entries e JOIN championship_registrations r ON r.id = e.registration_id WHERE r.championship_id = :id AND e.status = 'active'",
            'teams'    => "SELECT COUNT(*) FROM event_teams WHERE championship_id = :id",
            'members'  => "SELECT COUNT(*) FROM event_team_members m JOIN event_teams t ON t.id = m.team_id WHERE t.championship_id = :id",
            'dojos'    => "SELECT COUNT(DISTINCT dojo_id) FROM championship_registrations WHERE championship_id = :id",
        ]);
    }

    private function counts(int $id, array $queries): array
    {
        $out = [];

        foreach ($queries as $key => $sql) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            $out[$key] = (int) $stmt->fetchColumn();
        }

        return $out;
    }

    /**
     * Contagens iguais não bastam: confere também quem está em qual categoria e quem está em qual equipe,
     * pelo nome da categoria (que é copiado sem alteração).
     *
     * @return string[] divergências encontradas
     */
    private function compare(int $id, array $before, array $after): array
    {
        $problems = [];

        foreach ($before as $key => $value) {
            if ($value !== $after[$key]) {
                $problems[] = "$key: antes $value, depois {$after[$key]}";
            }
        }

        $set = function (string $sql) use ($id): array {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            sort($rows);

            return $rows;
        };

        $entriesBefore = $set(
            "SELECT CONCAT(a.athlete_id, '|', c.name) FROM championship_entries e
             JOIN championship_athletes a ON a.id = e.championship_athlete_id
             JOIN competition_categories c ON c.id = e.category_id WHERE a.championship_id = :id"
        );
        $entriesAfter = $set(
            "SELECT CONCAT(r.athlete_id, '|', c.name) FROM registration_entries e
             JOIN championship_registrations r ON r.id = e.registration_id
             JOIN event_categories c ON c.id = e.event_category_id WHERE r.championship_id = :id AND e.status = 'active'"
        );

        if ($entriesBefore !== $entriesAfter) {
            $problems[] = 'atletas e categorias não conferem com o evento antigo';
        }

        $teamsBefore = $set(
            "SELECT CONCAT(c.name, '|', COALESCE(t.name, ''), '|', COALESCE(GROUP_CONCAT(a.athlete_id ORDER BY a.athlete_id), ''))
             FROM championship_teams t
             JOIN competition_categories c ON c.id = t.category_id
             LEFT JOIN championship_team_members m ON m.team_id = t.id
             LEFT JOIN championship_athletes a ON a.id = m.championship_athlete_id
             WHERE t.championship_id = :id GROUP BY t.id, c.name, t.name"
        );
        $teamsAfter = $set(
            "SELECT CONCAT(c.name, '|', COALESCE(t.name, ''), '|', COALESCE(GROUP_CONCAT(r.athlete_id ORDER BY r.athlete_id), ''))
             FROM event_teams t
             JOIN event_categories c ON c.id = t.event_category_id
             LEFT JOIN event_team_members m ON m.team_id = t.id
             LEFT JOIN championship_registrations r ON r.id = m.registration_id
             WHERE t.championship_id = :id GROUP BY t.id, c.name, t.name"
        );

        if ($teamsBefore !== $teamsAfter) {
            $problems[] = 'equipes e integrantes não conferem com o evento antigo';
        }

        return $problems;
    }
}

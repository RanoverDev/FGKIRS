<?php

namespace Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use Services\LegacyEventMigrator;

/**
 * Migra um evento antigo de verdade (atletas, entradas e equipes) e confere o resultado.
 * Precisa de um banco com as migrations aplicadas e o catálogo antigo semeado; sem
 * FGKIRS_TEST_DB_HOST o teste é pulado. Tudo roda numa transação desfeita no tearDown.
 */
final class LegacyEventMigratorTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $host = getenv('FGKIRS_TEST_DB_HOST');

        if ($host === false || $host === '') {
            $this->markTestSkipped('Defina FGKIRS_TEST_DB_HOST, FGKIRS_TEST_DB_NAME e FGKIRS_TEST_DB_USER para rodar.');
        }

        $this->pdo = new PDO(
            "mysql:host=$host;dbname=" . getenv('FGKIRS_TEST_DB_NAME') . ';charset=utf8mb4',
            (string) getenv('FGKIRS_TEST_DB_USER'),
            (string) getenv('FGKIRS_TEST_DB_PASS'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        // O migrador abre transação por evento; o isolamento do teste é por limpeza explícita no tearDown
        $this->pdo->exec("DELETE FROM championships WHERE title LIKE 'TESTE-LEGACY%'");
    }

    protected function tearDown(): void
    {
        if (!isset($this->pdo)) {
            return;
        }

        foreach ($this->pdo->query("SELECT id FROM championships WHERE title LIKE 'TESTE-LEGACY%'")->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $this->wipe((int) $id);
        }
    }

    private function wipe(int $id): void
    {
        foreach ([
            "DELETE l FROM registration_log l JOIN championship_registrations r ON r.id = l.registration_id WHERE r.championship_id = $id",
            "DELETE m FROM event_team_members m JOIN event_teams t ON t.id = m.team_id WHERE t.championship_id = $id",
            "DELETE FROM event_teams WHERE championship_id = $id",
            "DELETE e FROM registration_entries e JOIN championship_registrations r ON r.id = e.registration_id WHERE r.championship_id = $id",
            "DELETE FROM championship_registrations WHERE championship_id = $id",
            "DELETE FROM event_categories WHERE championship_id = $id",
            "DELETE m FROM championship_team_members m JOIN championship_teams t ON t.id = m.team_id WHERE t.championship_id = $id",
            "DELETE FROM championship_teams WHERE championship_id = $id",
            "DELETE e FROM championship_entries e JOIN championship_athletes a ON a.id = e.championship_athlete_id WHERE a.championship_id = $id",
            "DELETE FROM championship_athletes WHERE championship_id = $id",
            "DELETE FROM championship_categories WHERE championship_id = $id",
            "DELETE FROM championships WHERE id = $id",
        ] as $sql) {
            $this->pdo->exec($sql);
        }
    }

    private function scalar(string $sql): mixed
    {
        return $this->pdo->query($sql)->fetchColumn();
    }

    private function category(string $name): int
    {
        $id = $this->scalar("SELECT id FROM competition_categories WHERE name = " . $this->pdo->quote($name));
        $this->assertNotFalse($id, "Categoria do catálogo não encontrada: $name");

        return (int) $id;
    }

    /** Evento antigo com 3 atletas (1 avulso), 4 entradas e 1 equipe de 3. Devolve o id. */
    private function legacyEvent(string $title, string $eventDate, string $start, string $end, bool $linkAthletes = true): int
    {
        $this->pdo->exec(
            "INSERT INTO championships (title, event_date, registration_start, registration_end, status, created_by)
             VALUES ('$title', '$eventDate', '$start', '$end', 'published', 1)"
        );
        $id = (int) $this->pdo->lastInsertId();

        $athletes = [];
        foreach ([
            ['Atleta Um', 'M', '2014-05-10', 2, 45.5, 0],
            ['Atleta Dois', 'M', '2013-04-04', 1, 38, 0],
            ['Atleta Avulso', 'F', '2012-03-03', 1, null, 1],
        ] as $i => [$name, $gender, $birth, $graduation, $weight, $guest]) {
            $athleteId = $linkAthletes ? (int) $this->scalar("SELECT id FROM athletes ORDER BY id LIMIT 1 OFFSET " . ($i)) : null;
            $stmt = $this->pdo->prepare(
                "INSERT INTO championship_athletes
                    (championship_id, dojo_id, athlete_id, name, gender, birth_date, graduation_id, weight, is_guest, registered_by)
                 VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, 2)"
            );
            $stmt->execute([$id, $athleteId, $name, $gender, $birth, $graduation, $weight, $guest]);
            $athletes[] = (int) $this->pdo->lastInsertId();
        }

        $kataColored = $this->category('Kata Individual — Masculino — Infantil (11 a 12) — Faixa Colorida');
        $kataJuvenil = $this->category('Kata Individual — Masculino — Infanto Juvenil (13 a 14) — Faixa Colorida');
        $kumite      = $this->category('Kumite Individual — Masculino — Infantil (11 a 12) — Faixa Colorida — até 50kg');
        $kataF       = $this->category('Kata Individual — Feminino — Infantil (11 a 12) — Faixa Colorida');
        $team        = $this->category('Kata Equipe — Masculino — Até 14 anos');

        foreach ([[0, $kataColored, null], [0, $kumite, 45.5], [1, $kataJuvenil, null], [2, $kataF, null]] as [$index, $categoryId, $weight]) {
            $this->pdo->prepare("INSERT INTO championship_entries (championship_athlete_id, category_id, weight) VALUES (?, ?, ?)")
                ->execute([$athletes[$index], $categoryId, $weight]);
        }

        $this->pdo->prepare("INSERT INTO championship_teams (championship_id, dojo_id, category_id, name) VALUES (?, 1, ?, 'Equipe A')")
            ->execute([$id, $team]);
        $teamId = (int) $this->pdo->lastInsertId();
        foreach ($athletes as $position => $athleteId) {
            $this->pdo->prepare("INSERT INTO championship_team_members (team_id, championship_athlete_id, position) VALUES (?, ?, ?)")
                ->execute([$teamId, $athleteId, $position + 1]);
        }

        return $id;
    }

    private function event(int $id): array
    {
        return $this->pdo->query("SELECT * FROM championships WHERE id = $id")->fetch();
    }

    public function testMigratesAnOldEventWithMatchingCounts(): void
    {
        $id = $this->legacyEvent('TESTE-LEGACY passado', '2025-03-01', '2025-01-01 00:00:00', '2025-02-15 00:00:00');

        $report = (new LegacyEventMigrator($this->pdo))->migrate($this->event($id), false);

        $this->assertSame('migrated', $report['status'], implode(' ', $report['messages']));
        $this->assertSame($report['before'], $report['after']);
        $this->assertSame(['athletes' => 3, 'entries' => 4, 'teams' => 1, 'members' => 3, 'dojos' => 1], $report['after']);
        $this->assertSame('v2', $this->scalar("SELECT registration_model FROM championships WHERE id = $id"));
        $this->assertSame('3', (string) $this->scalar("SELECT COUNT(*) FROM championship_registrations WHERE championship_id = $id AND status = 'confirmed'"), 'evento passado fica confirmado');
        $this->assertSame('3', (string) $this->scalar("SELECT COUNT(*) FROM registration_log l JOIN championship_registrations r ON r.id = l.registration_id WHERE r.championship_id = $id AND l.action = 'migrated_from_legacy'"));
    }

    public function testCopiesCategoryLimitsAndMapsBeltGroupsToLevels(): void
    {
        $id = $this->legacyEvent('TESTE-LEGACY limites', '2025-03-01', '2025-01-01 00:00:00', '2025-02-15 00:00:00');
        (new LegacyEventMigrator($this->pdo))->migrate($this->event($id), false);

        $row = $this->pdo->query(
            "SELECT * FROM event_categories WHERE championship_id = $id AND name LIKE 'Kumite Individual%'"
        )->fetch();

        $this->assertSame('KMI', $row['discipline_code']);
        $this->assertSame('11', (string) $row['age_min']);
        $this->assertSame('12', (string) $row['age_max']);
        $this->assertSame('1', (string) $row['belt_level_min']);
        $this->assertSame('10', (string) $row['belt_level_max']);
        $this->assertSame('50.00', $row['weight_max']);
        $this->assertSame('closed', $row['status']);
        $this->assertStringStartsWith('LEG-', $row['code']);

        $team = $this->pdo->query("SELECT * FROM event_categories WHERE championship_id = $id AND format = 'team'")->fetch();
        $this->assertSame('KTE', $team['discipline_code']);
        $this->assertNull($team['belt_level_min']);
        $this->assertSame('3', (string) $team['team_size']);
    }

    public function testRegistrationSnapshotKeepsTheOldRowData(): void
    {
        $id = $this->legacyEvent('TESTE-LEGACY foto', '2025-03-01', '2025-01-01 00:00:00', '2025-02-15 00:00:00');
        (new LegacyEventMigrator($this->pdo))->migrate($this->event($id), false);

        $row = $this->pdo->query(
            "SELECT full_name, age_at_event, belt_level, declared_weight FROM championship_registrations
             WHERE championship_id = $id AND full_name = 'Atleta Um'"
        )->fetch();

        $this->assertSame(10, (int) $row['age_at_event'], '2014-05-10 em 01/03/2025 tem 10 anos');
        $this->assertSame('2', (string) $row['belt_level']);
        $this->assertSame('45.50', $row['declared_weight']);

        $filled = $this->scalar("SELECT declared_weight FROM championship_registrations WHERE championship_id = $id AND full_name = 'Atleta Avulso'");
        $this->assertNull($filled, 'sem peso no atleta nem na entrada, continua sem peso');
    }

    public function testDryRunWritesNothing(): void
    {
        $id = $this->legacyEvent('TESTE-LEGACY dry', '2025-03-01', '2025-01-01 00:00:00', '2025-02-15 00:00:00');

        $report = (new LegacyEventMigrator($this->pdo))->migrate($this->event($id), true);

        $this->assertSame('dry-run', $report['status']);
        $this->assertSame($report['before'], $report['after']);
        $this->assertSame('legacy', $this->scalar("SELECT registration_model FROM championships WHERE id = $id"));
        $this->assertSame('0', (string) $this->scalar("SELECT COUNT(*) FROM championship_registrations WHERE championship_id = $id"));
        $this->assertSame('0', (string) $this->scalar("SELECT COUNT(*) FROM event_categories WHERE championship_id = $id"));
    }

    public function testRunningTwiceDoesNotDuplicate(): void
    {
        $id       = $this->legacyEvent('TESTE-LEGACY duas vezes', '2025-03-01', '2025-01-01 00:00:00', '2025-02-15 00:00:00');
        $migrator = new LegacyEventMigrator($this->pdo);

        $migrator->migrate($this->event($id), false);

        $pendingIds = array_map(static fn (array $e) => (int) $e['id'], $migrator->pending());
        $this->assertNotContains($id, $pendingIds, 'evento migrado deixa de ser pendente');
        $this->assertSame('3', (string) $this->scalar("SELECT COUNT(*) FROM championship_registrations WHERE championship_id = $id"));
    }

    public function testEventWithOpenRegistrationIsSkipped(): void
    {
        $id = $this->legacyEvent(
            'TESTE-LEGACY aberto', date('Y-m-d', strtotime('+30 days')),
            date('Y-m-d H:i:s', strtotime('-1 day')), date('Y-m-d H:i:s', strtotime('+5 days'))
        );

        $report = (new LegacyEventMigrator($this->pdo))->migrate($this->event($id), false);

        $this->assertSame('skipped', $report['status']);
        $this->assertStringContainsString('inscrições ainda abertas', $report['messages'][0]);
        $this->assertSame('legacy', $this->scalar("SELECT registration_model FROM championships WHERE id = $id"));
    }

    public function testAthletesWithoutAthleteIdBlockTheEvent(): void
    {
        $id = $this->legacyEvent('TESTE-LEGACY sem cadastro', '2025-03-01', '2025-01-01 00:00:00', '2025-02-15 00:00:00', false);

        $report = (new LegacyEventMigrator($this->pdo))->migrate($this->event($id), false);

        $this->assertSame('skipped', $report['status']);
        $this->assertStringContainsString('migrate_athletes.php', $report['messages'][0]);
        $this->assertSame('0', (string) $this->scalar("SELECT COUNT(*) FROM championship_registrations WHERE championship_id = $id"));
    }

    public function testMismatchRollsTheEventBack(): void
    {
        $id = $this->legacyEvent('TESTE-LEGACY colisão', '2025-03-01', '2025-01-01 00:00:00', '2025-02-15 00:00:00');

        // Dois atletas do evento antigo ligados ao mesmo cadastro: a inscrição nova é única por atleta e evento
        $this->pdo->exec(
            "UPDATE championship_athletes SET athlete_id = (SELECT x.athlete_id FROM (SELECT athlete_id FROM championship_athletes WHERE championship_id = $id ORDER BY id LIMIT 1) x)
             WHERE championship_id = $id"
        );

        $report = (new LegacyEventMigrator($this->pdo))->migrate($this->event($id), false);

        $this->assertSame('failed', $report['status']);
        $this->assertSame('legacy', $this->scalar("SELECT registration_model FROM championships WHERE id = $id"));
        $this->assertSame('0', (string) $this->scalar("SELECT COUNT(*) FROM championship_registrations WHERE championship_id = $id"));
        $this->assertSame('0', (string) $this->scalar("SELECT COUNT(*) FROM event_categories WHERE championship_id = $id"));
        $this->assertSame('3', (string) $this->scalar("SELECT COUNT(*) FROM championship_athletes WHERE championship_id = $id"), 'tabelas antigas intactas');
    }
}

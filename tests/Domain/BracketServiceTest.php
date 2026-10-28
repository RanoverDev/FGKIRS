<?php

namespace Tests\Domain;

use PHPUnit\Framework\TestCase;
use Repositories\EventCategoryRepository;
use Services\BracketService;
use Services\RegistrationException;
use Services\RegistrationService;
use Tests\Support\FakeBracketRepository;

/** BracketService de ponta a ponta com repositórios em memória (sem banco). */
final class BracketServiceTest extends TestCase
{
    private FakeBracketRepository $repo;
    private BracketService $service;
    private array $championship = ['id' => 1];

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = ['user_id' => 1, 'user_authenticated' => true, 'user_role' => 'admin'];

        $this->repo = new FakeBracketRepository();

        $categories = new class extends EventCategoryRepository {
            public function __construct()
            {
            }

            private function row(int $id, string $name, string $gender = 'M'): array
            {
                return [
                    'id' => $id, 'championship_id' => 1, 'name' => $name, 'short_name' => $name, 'format' => 'individual',
                    'modality' => 'kumite', 'discipline_code' => 'KMI', 'discipline_name' => 'Kumite', 'gender' => $gender,
                    'status' => 'open', 'sort_order' => $id, 'enrolled' => 0,
                ];
            }

            public function rows(int $championshipId): array
            {
                return [$this->row(10, '-60 kg'), $this->row(11, '+60 kg'), $this->row(12, 'Feminino', 'F')];
            }

            public function find(int $id): ?array
            {
                foreach ($this->rows(1) as $row) {
                    if ($row['id'] === $id) {
                        return $row;
                    }
                }

                return $id === 99 ? ['championship_id' => 2] + $this->row(99, 'Outro evento') : null;
            }
        };

        $registrations = new class extends RegistrationService {
            public array $moves = [];

            public function __construct()
            {
            }

            public function moveEntry(array $championship, int $registrationId, int $fromCategoryId, int $toCategoryId, string $reason, int $adminId): void
            {
                $this->moves[] = [$registrationId, $fromCategoryId, $toCategoryId, $reason];
            }
        };

        $this->service = new class($this->repo, $categories, $registrations) extends BracketService {
            protected function transaction(callable $work): void
            {
                $work();
            }
        };

        foreach ([1 => 'Ana', 2 => 'Bia', 3 => 'Caio', 4 => 'Davi', 5 => 'Edu'] as $id => $name) {
            $this->repo->person(10, $id, $name, $id % 2 + 1);
        }
    }

    public function testGenerateBuildsABracketForEveryParticipant(): void
    {
        $this->service->generate($this->championship, 10, 1);

        $state = $this->service->state($this->championship, 10);

        $this->assertSame(8, $state['bracket']['size']);
        $this->assertSame(3, $state['bracket']['rounds']);
        $this->assertSame('draft', $state['bracket']['status']);
        $this->assertSame([], $state['missing']);
        $this->assertSame([], $state['extra']);
        $this->assertCount(3, $state['rounds']);
        $this->assertSame('Quartas de final', $state['rounds'][0]['label']);

        $names = [];
        foreach ($state['rounds'][0]['matches'] as $m) {
            foreach ([$m['a'], $m['b']] as $p) {
                if ($p) {
                    $names[] = $p['name'];
                }
            }
        }
        sort($names);
        $this->assertSame(['Ana', 'Bia', 'Caio', 'Davi', 'Edu'], $names);
    }

    public function testFullTournamentPersistsPlacementsAndPoints(): void
    {
        $this->service->generate($this->championship, 10, 1);

        // Resolve a chave sempre dando a vitória ao menor id
        for ($round = 1; $round <= 3; $round++) {
            $state = $this->service->state($this->championship, 10);
            foreach ($state['rounds'][$round - 1]['matches'] as $m) {
                if ($m['a'] && $m['b'] && $m['winner'] === null) {
                    $winner = min($m['a']['id'], $m['b']['id']);
                    $this->service->setWinner($this->championship, 10, $round, $m['position'], $winner, '3', '1', 1);
                }
            }
        }

        $state = $this->service->state($this->championship, 10);

        $this->assertTrue($state['bracket']['finished']);
        $this->assertSame('finished', $state['bracket']['status']);
        $this->assertSame('Ana', $state['podium'][0]['name']);
        $this->assertSame(1, $state['podium'][0]['place']);

        $byPlace = [];
        foreach ($this->repo->results[10] as $r) {
            $byPlace[$r['place']][] = $r;
        }

        $this->assertCount(1, $byPlace[1]);
        $this->assertSame(10, $byPlace[1][0]['points']);
        $this->assertSame(1001, $byPlace[1][0]['athlete_id']);
        $this->assertSame(7, $byPlace[2][0]['points']);
        $this->assertCount(2, $byPlace[3]);
        $this->assertSame(5, $byPlace[3][0]['points']);
        $this->assertCount(5 - 1 - 1 - 2, $byPlace[5] ?? [], 'quem perdeu nas quartas');
    }

    public function testResultsFreezeTheDrawUntilCleared(): void
    {
        $this->service->generate($this->championship, 10, 1);

        $match = null;
        foreach ($this->service->state($this->championship, 10)['rounds'][0]['matches'] as $m) {
            if ($m['a'] && $m['b']) {
                $match = $m;
                break;
            }
        }
        $this->service->setWinner($this->championship, 10, 1, $match['position'], $match['a']['id'], null, null, 1);

        try {
            $this->service->swap($this->championship, 10, 0, 1, 1);
            $this->fail('devia travar');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('resultados', $e->getMessage());
        }

        try {
            $this->service->generate($this->championship, 10, 1);
            $this->fail('devia pedir confirmação');
        } catch (\DomainException) {
            $this->assertTrue(true);
        }

        $this->service->clearResults($this->championship, 10);
        $this->service->swap($this->championship, 10, 0, 1, 1);
        $this->assertFalse($this->service->state($this->championship, 10)['bracket']['hasResults']);
        $this->assertSame([], $this->repo->results[10]);
    }

    public function testSyncRemovesCancelledAndInsertsLateRegistrations(): void
    {
        $this->service->generate($this->championship, 10, 1);

        unset($this->repo->participants[10][2]);
        $this->repo->person(10, 6, 'Fabio', 1);

        $before = $this->service->state($this->championship, 10);
        $this->assertSame(['Bia'], array_column($before['extra'], 'name'));
        $this->assertSame(['Fabio'], array_column($before['missing'], 'name'));

        $this->service->sync($this->championship, 10);

        $after = $this->service->state($this->championship, 10);
        $this->assertSame([], $after['extra']);
        $this->assertSame([], $after['missing']);
    }

    public function testSyncFailsWhenThereIsNoRoomForNewcomers(): void
    {
        $this->repo->person(10, 6, 'Fabio', 1);
        $this->repo->person(10, 7, 'Gui', 2);
        $this->repo->person(10, 8, 'Hugo', 1);
        $this->service->generate($this->championship, 10, 1);

        $this->repo->person(10, 9, 'Ivo', 2);

        $this->expectException(\DomainException::class);
        $this->service->sync($this->championship, 10);
    }

    public function testSingleParticipantIsChampionWithoutPoints(): void
    {
        $this->repo->person(11, 50, 'Solo', 1);
        $this->service->generate($this->championship, 11, 1);

        $state = $this->service->state($this->championship, 11);

        $this->assertTrue($state['bracket']['finished']);
        $this->assertSame(0, $this->repo->results[11][0]['points']);
        $this->assertSame(1, $this->repo->results[11][0]['place']);
    }

    public function testGenerateAllSkipsCategoriesWithoutParticipants(): void
    {
        $this->repo->person(11, 50, 'Solo', 1);

        $this->assertSame(2, $this->service->generateAll($this->championship, 1));
        $this->assertSame(0, $this->service->generateAll($this->championship, 1), 'não refaz o que já existe');
        $this->assertArrayNotHasKey(12, $this->repo->brackets);
    }

    public function testCannotTouchCategoriesFromAnotherEvent(): void
    {
        $this->expectException(\DomainException::class);
        $this->service->generate($this->championship, 99, 1);
    }

    public function testNeedsParticipantsToDraw(): void
    {
        $this->expectException(\DomainException::class);
        $this->service->generate($this->championship, 12, 1);
    }

    public function testOnlyThePresidentChangesBrackets(): void
    {
        $_SESSION['user_role'] = 'sensei';

        $this->expectException(RegistrationException::class);
        $this->service->generate($this->championship, 10, 1);
    }

    public function testSenseiCanStillReadTheBracket(): void
    {
        $this->service->generate($this->championship, 10, 1);
        $_SESSION['user_role'] = 'sensei';

        $state = $this->service->state($this->championship, 10);

        $this->assertFalse($state['canEdit']);
    }

    public function testMoveParticipantUsesRegistrationServiceAndSyncsBothBrackets(): void
    {
        $this->service->generate($this->championship, 10, 1);
        $this->repo->person(11, 60, 'Zeca', 1);
        $this->service->generate($this->championship, 11, 1);

        // Simula o efeito do moveEntry: Edu sai da categoria 10 e entra na 11
        $edu = $this->repo->participants[10][5];
        unset($this->repo->participants[10][5]);
        $this->repo->participants[11][5] = $edu;

        $warnings = $this->service->moveParticipant($this->championship, 10, 5, 11, 'pesagem', 1);

        $this->assertSame([], $warnings);
        $this->assertSame([], $this->service->state($this->championship, 10)['extra']);
        $this->assertSame([], $this->service->state($this->championship, 11)['missing']);
    }

    public function testDeleteRemovesBracketAndPodium(): void
    {
        $this->service->generate($this->championship, 10, 1);
        $this->service->delete($this->championship, 10);

        $this->assertNull($this->repo->findByCategory(10));
        $this->assertNull($this->service->state($this->championship, 10)['bracket']);
    }
}

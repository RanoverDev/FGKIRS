<?php

namespace Tests\Support;

use Repositories\BracketRepository;

/** Repositório em memória: deixa o BracketService ser testado inteiro sem banco. */
final class FakeBracketRepository extends BracketRepository
{
    public array $brackets = [];
    public array $matchRows = [];
    public array $results = [];
    /** @var array<int, array<int, array>> categoria => participantes (id => linha) */
    public array $participants = [];
    public array $decisions = [];
    /** Todo mundo que já existiu: o rótulo de quem saiu da categoria continua no banco. */
    private array $known = [];
    private int $nextId = 1;

    public function __construct()
    {
    }

    public function tablesExist(): bool
    {
        return true;
    }

    public function findByCategory(int $categoryId): ?array
    {
        return $this->brackets[$categoryId] ?? null;
    }

    public function forChampionship(int $championshipId): array
    {
        return $this->brackets;
    }

    public function create(int $championshipId, int $categoryId, string $type, int $size, int $userId): int
    {
        $id = $this->nextId++;
        $this->brackets[$categoryId] = [
            'id' => $id, 'championship_id' => $championshipId, 'event_category_id' => $categoryId,
            'participant_type' => $type, 'size' => $size, 'status' => 'draft',
        ];

        return $id;
    }

    public function update(int $id, int $size, string $status): void
    {
        foreach ($this->brackets as $cat => $b) {
            if ($b['id'] === $id) {
                $this->brackets[$cat]['size']   = $size;
                $this->brackets[$cat]['status'] = $status;
            }
        }
    }

    public function delete(int $id): void
    {
        $this->deleteMatches($id);
        foreach ($this->brackets as $cat => $b) {
            if ($b['id'] === $id) {
                unset($this->brackets[$cat]);
            }
        }
    }

    public function deleteMatches(int $bracketId): void
    {
        unset($this->matchRows[$bracketId]);
    }

    public function matches(int $bracketId): array
    {
        $rows = array_values($this->matchRows[$bracketId] ?? []);
        usort($rows, static fn ($x, $y) => [$x['round'], $x['position']] <=> [$y['round'], $y['position']]);

        return $rows;
    }

    public function saveMatches(int $bracketId, array $rows): void
    {
        foreach ($rows as $row) {
            $key      = $row['round'] . ':' . $row['position'];
            $previous = $this->matchRows[$bracketId][$key] ?? ['score_a' => null, 'score_b' => null, 'decided_at' => null];
            $keep     = $row['winner'] !== null;

            $this->matchRows[$bracketId][$key] = $row + [
                'score_a'    => $keep ? $previous['score_a'] : null,
                'score_b'    => $keep ? $previous['score_b'] : null,
                'decided_at' => $keep ? $previous['decided_at'] : null,
            ];
        }
    }

    public function recordDecision(int $bracketId, int $round, int $position, ?string $scoreA, ?string $scoreB, int $userId): void
    {
        $key = "$round:$position";
        $this->matchRows[$bracketId][$key]['score_a']    = $scoreA;
        $this->matchRows[$bracketId][$key]['score_b']    = $scoreB;
        $this->matchRows[$bracketId][$key]['decided_at'] = 'now';
        $this->decisions[] = [$bracketId, $round, $position];
    }

    public function registrationParticipants(int $categoryId): array
    {
        return $this->participants[$categoryId] ?? [];
    }

    public function teamParticipants(int $categoryId): array
    {
        return $this->participants[$categoryId] ?? [];
    }

    public function participantCounts(int $championshipId): array
    {
        return array_map('count', $this->participants);
    }

    public function labels(string $type, array $ids): array
    {
        return array_intersect_key($this->known, array_flip($ids));
    }

    public function replaceResults(int $championshipId, int $categoryId, array $rows): void
    {
        $this->results[$categoryId] = $rows;
    }

    public function deleteResults(int $categoryId): void
    {
        unset($this->results[$categoryId]);
    }

    public function person(int $categoryId, int $id, string $name, int $dojo): void
    {
        $this->participants[$categoryId][$id] = $this->known[$id] = [
            'id' => $id, 'name' => $name, 'dojo_id' => $dojo, 'dojo_name' => "Dojo $dojo", 'athlete_id' => 1000 + $id,
        ];
    }
}

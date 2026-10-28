<?php

namespace Services;

use Core\Database;
use Domain\Bracket\BracketGenerator;
use Domain\Bracket\BracketTree;
use Domain\Ranking\PointsTable;
use Helpers\Auth;
use Repositories\BracketRepository;
use Repositories\EventCategoryRepository;

/**
 * Casos de uso das chaves. Ver chave é livre para quem vê o evento; qualquer mudança é do Presidente
 * (RegistrationGuard::assertAdmin). Regra de chave mora em Domain\Bracket; aqui ficam permissão, banco
 * e a gravação do pódio (event_results), refeita a cada mudança para nunca divergir da chave.
 *
 * Falha de regra volta como \DomainException (mensagem pronta para a tela).
 */
class BracketService
{
    public function __construct(
        private BracketRepository $brackets = new BracketRepository(),
        private EventCategoryRepository $categories = new EventCategoryRepository(),
        private RegistrationService $registrations = new RegistrationService(),
    ) {
    }

    // ───────────────────────────── Leitura ─────────────────────────────

    /** Categorias do evento com quantos podem competir e o andamento da chave. */
    public function overview(int $championshipId): array
    {
        $counts   = $this->brackets->participantCounts($championshipId);
        $brackets = $this->brackets->forChampionship($championshipId);
        $out      = [];

        foreach ($this->categories->rows($championshipId) as $row) {
            if (in_array($row['status'], ['merged', 'cancelled'], true)) {
                continue;
            }

            $bracket = $brackets[(int) $row['id']] ?? null;
            $row['participants'] = $counts[(int) $row['id']] ?? 0;
            $row['bracket']      = $bracket ? ['status' => $bracket['status'], 'size' => (int) $bracket['size']] : null;
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Tudo que a tela da chave precisa, já com nomes: rodadas, quem falta entrar, quem saiu e o pódio.
     */
    public function state(array $championship, int $categoryId): array
    {
        $category = $this->category($championship, $categoryId);
        $type     = $this->typeOf($category);
        $bracket  = $this->brackets->findByCategory($categoryId);
        $current  = $this->currentParticipants($category);

        $base = [
            'category' => [
                'id' => (int) $category['id'], 'name' => $category['name'], 'shortName' => $category['short_name'] ?: $category['name'],
                'modality' => $category['modality'], 'format' => $category['format'], 'status' => $category['status'],
            ],
            'type'      => $type,
            'canEdit'   => Auth::isAdmin(),
            'eligible'  => count($current),
            'bracket'   => null,
        ];

        if (!$bracket) {
            return $base + [
                'rounds'      => [],
                'missing'     => array_values(array_map(static fn (array $p) => ['id' => $p['id'], 'name' => $p['name'], 'dojo' => $p['dojo_name']], $current)),
                'extra'       => [],
                'podium'      => [],
                'moveTargets' => [],
            ];
        }

        $tree   = $this->tree($bracket);
        $rows   = [];
        foreach ($this->brackets->matches((int) $bracket['id']) as $row) {
            $rows[(int) $row['round']][(int) $row['position']] = $row;
        }

        $inTree  = $tree->participants();
        $labels  = $this->brackets->labels($type, $inTree) + $current;
        $label   = static fn (?int $id): ?array => $id === null ? null : [
            'id' => $id, 'name' => $labels[$id]['name'] ?? ('#' . $id), 'dojo' => $labels[$id]['dojo_name'] ?? '',
        ];

        $rounds = [];
        for ($round = 1; $round <= $tree->rounds(); $round++) {
            $matches = [];
            for ($position = 0; $position < $tree->size() >> $round; $position++) {
                $m   = $tree->match($round, $position);
                $row = $rows[$round][$position] ?? [];

                $matches[] = [
                    'position' => $position,
                    'a'        => $label($m['a']),
                    'b'        => $label($m['b']),
                    'winner'   => $m['winner'],
                    'bye'      => $tree->isBye($round, $position),
                    'scoreA'   => $row['score_a'] ?? null,
                    'scoreB'   => $row['score_b'] ?? null,
                ];
            }

            $rounds[] = ['round' => $round, 'label' => BracketTree::roundLabel($round, $tree->rounds()), 'matches' => $matches];
        }

        $podium = [];
        foreach ($tree->placements() as $id => $place) {
            if ($place <= 3) {
                $podium[] = ['place' => $place] + ($label($id) ?? []);
            }
        }
        usort($podium, static fn ($x, $y) => $x['place'] <=> $y['place'] ?: strcmp($x['name'], $y['name']));

        return array_merge($base, [
            'bracket' => [
                'id' => (int) $bracket['id'], 'status' => $bracket['status'], 'size' => $tree->size(), 'rounds' => $tree->rounds(),
                'hasResults' => $tree->hasResults(), 'finished' => $tree->isFinished(),
            ],
            'rounds'      => $rounds,
            'missing'     => array_values(array_map($label, array_keys(array_diff_key($current, array_flip($inTree))))),
            'extra'       => array_values(array_map($label, array_diff($inTree, array_keys($current)))),
            'podium'      => $podium,
            'moveTargets' => $type === 'registration' ? $this->moveTargets($championship, $category) : [],
        ]);
    }

    // ───────────────────────────── Escrita (Presidente) ─────────────────────────────

    /** Sorteia a chave da categoria. Com resultados, só com $reset (apaga a chave e os resultados). */
    public function generate(array $championship, int $categoryId, int $userId, bool $reset = false): void
    {
        RegistrationGuard::assertAdmin();

        $category = $this->category($championship, $categoryId);
        $this->assertDrawable($category);

        $current = $this->currentParticipants($category);
        if ($current === []) {
            throw new \DomainException('Nenhum participante com inscrição enviada ou confirmada nesta categoria.');
        }

        $bracket = $this->brackets->findByCategory($categoryId);
        if ($bracket && $this->tree($bracket)->hasResults() && !$reset) {
            throw new \DomainException('Esta chave já tem resultados. Confirme para apagar tudo e sortear de novo.');
        }

        $draw = BracketGenerator::draw(array_map(
            static fn (array $p) => ['id' => $p['id'], 'dojo' => $p['dojo_id']],
            array_values($current)
        ));
        $tree = BracketTree::fromSlots($draw['size'], $draw['slots']);

        $this->transaction(function () use ($championship, $category, $bracket, $tree, $userId) {
            if ($bracket) {
                $this->brackets->deleteMatches((int) $bracket['id']);
                $this->brackets->update((int) $bracket['id'], $tree->size(), 'draft');
                $id = (int) $bracket['id'];
            } else {
                $id = $this->brackets->create((int) $championship['id'], (int) $category['id'], $this->typeOf($category), $tree->size(), $userId);
            }

            $this->brackets->saveMatches($id, $tree->toRows());
            $this->finalize([
                'id' => $id, 'championship_id' => (int) $championship['id'], 'event_category_id' => (int) $category['id'],
                'participant_type' => $this->typeOf($category),
            ], $tree);
        });
    }

    /** Sorteia todas as categorias que ainda não têm chave e têm participantes. @return int quantas foram criadas */
    public function generateAll(array $championship, int $userId): int
    {
        RegistrationGuard::assertAdmin();

        $created = 0;

        foreach ($this->overview((int) $championship['id']) as $row) {
            if ($row['bracket'] === null && $row['participants'] > 0) {
                $this->generate($championship, (int) $row['id'], $userId);
                $created++;
            }
        }

        return $created;
    }

    /** Troca duas vagas da primeira rodada (as vagas vão de 0 até size-1, na ordem de cima para baixo). */
    public function swap(array $championship, int $categoryId, int $slotA, int $slotB, int $userId): void
    {
        $this->mutate($championship, $categoryId, static fn (BracketTree $t) => $t->swapSlots($slotA, $slotB));
    }

    public function setWinner(array $championship, int $categoryId, int $round, int $position, int $participantId, ?string $scoreA, ?string $scoreB, int $userId): void
    {
        $this->mutate(
            $championship,
            $categoryId,
            static fn (BracketTree $t) => $t->setWinner($round, $position, $participantId),
            fn (array $bracket) => $this->brackets->recordDecision(
                (int) $bracket['id'], $round, $position, $this->score($scoreA), $this->score($scoreB), $userId
            )
        );
    }

    public function clearWinner(array $championship, int $categoryId, int $round, int $position): void
    {
        $this->mutate($championship, $categoryId, static fn (BracketTree $t) => $t->clearWinner($round, $position));
    }

    public function clearResults(array $championship, int $categoryId): void
    {
        $this->mutate($championship, $categoryId, static fn (BracketTree $t) => $t->clearAllResults());
    }

    /**
     * Acerta a chave com as inscrições de agora: quem saiu dá passagem ao adversário e quem entrou ocupa
     * vaga livre. Sem vaga, é preciso sortear de novo.
     */
    public function sync(array $championship, int $categoryId): void
    {
        $category = $this->category($championship, $categoryId);
        $current  = array_keys($this->currentParticipants($category));

        $this->mutate($championship, $categoryId, static function (BracketTree $tree) use ($current) {
            $inTree  = $tree->participants();
            $removed = array_diff($inTree, $current);
            $added   = array_diff($current, $inTree);

            if ($removed === [] && $added === []) {
                return;
            }
            if ($tree->hasResults()) {
                throw new \DomainException('A chave já tem resultados. Limpe os resultados para atualizar o sorteio, ou sorteie de novo.');
            }

            foreach ($removed as $id) {
                $tree->remove($id);
            }
            foreach ($added as $id) {
                if (!$tree->insert($id)) {
                    throw new \DomainException('Há mais inscritos do que vagas na chave. Sorteie de novo para criar uma chave maior.');
                }
            }
        });
    }

    /** Apaga a chave e o pódio dela (com resultados só com $reset). */
    public function delete(array $championship, int $categoryId, bool $reset = false): void
    {
        RegistrationGuard::assertAdmin();
        $this->category($championship, $categoryId);

        $bracket = $this->brackets->findByCategory($categoryId);
        if (!$bracket) {
            return;
        }
        if ($this->tree($bracket)->hasResults() && !$reset) {
            throw new \DomainException('Esta chave já tem resultados. Confirme para apagar tudo.');
        }

        $this->transaction(function () use ($bracket, $categoryId) {
            $this->brackets->deleteResults($categoryId);
            $this->brackets->delete((int) $bracket['id']);
        });
    }

    /**
     * Muda a inscrição de categoria (ajuste de peso, por exemplo) e acerta as duas chaves.
     *
     * @return string[] avisos (o que não deu para acertar sozinho na chave)
     */
    public function moveParticipant(array $championship, int $fromCategoryId, int $registrationId, int $toCategoryId, string $reason, int $userId): array
    {
        RegistrationGuard::assertAdmin();

        $this->category($championship, $fromCategoryId);
        $this->category($championship, $toCategoryId);

        $this->registrations->moveEntry($championship, $registrationId, $fromCategoryId, $toCategoryId, $reason, $userId);

        $warnings = [];

        foreach ([$fromCategoryId => 'de origem', $toCategoryId => 'de destino'] as $categoryId => $role) {
            if (!$this->brackets->findByCategory($categoryId)) {
                continue;
            }

            try {
                $this->sync($championship, $categoryId);
            } catch (\DomainException $e) {
                $warnings[] = "Chave $role: " . $e->getMessage();
            }
        }

        return $warnings;
    }

    // ───────────────────────────── Internos ─────────────────────────────

    /** Aplica a mudança na árvore e grava lutas, andamento e pódio numa transação. */
    private function mutate(array $championship, int $categoryId, callable $change, ?callable $afterSave = null): void
    {
        RegistrationGuard::assertAdmin();
        $this->category($championship, $categoryId);

        $bracket = $this->brackets->findByCategory($categoryId)
            ?? throw new \DomainException('Esta categoria ainda não tem chave. Faça o sorteio primeiro.');

        $tree = $this->tree($bracket);
        $change($tree);

        $this->transaction(function () use ($bracket, $tree, $afterSave) {
            $this->brackets->saveMatches((int) $bracket['id'], $tree->toRows());

            if ($afterSave) {
                $afterSave($bracket);
            }

            $this->finalize($bracket, $tree);
        });
    }

    /** Andamento da chave e pódio (event_results) sempre derivados da árvore. */
    private function finalize(array $bracket, BracketTree $tree): void
    {
        $status = $tree->isFinished() ? 'finished' : ($tree->hasResults() ? 'running' : 'draft');
        $this->brackets->update((int) $bracket['id'], $tree->size(), $status);

        $type      = $bracket['participant_type'];
        $places    = $tree->placements();
        $labels    = $this->brackets->labels($type, array_keys($places));
        $contested = count($tree->participants()) >= 2;
        $rows      = [];

        foreach ($places as $id => $place) {
            if (!isset($labels[$id])) {
                continue;
            }

            $rows[] = [
                'place' => $place, 'type' => $type, 'id' => $id, 'athlete_id' => $labels[$id]['athlete_id'],
                'dojo_id' => $labels[$id]['dojo_id'], 'points' => PointsTable::forPlace($place, $contested),
            ];
        }

        $this->brackets->replaceResults((int) $bracket['championship_id'], (int) $bracket['event_category_id'], $rows);
    }

    private function tree(array $bracket): BracketTree
    {
        return BracketTree::fromRows((int) $bracket['size'], $this->brackets->matches((int) $bracket['id']));
    }

    /** @return array<int, array{id: int, name: string, dojo_id: int, dojo_name: string, athlete_id: ?int}> */
    private function currentParticipants(array $category): array
    {
        return $this->typeOf($category) === 'team'
            ? $this->brackets->teamParticipants((int) $category['id'])
            : $this->brackets->registrationParticipants((int) $category['id']);
    }

    private function typeOf(array $category): string
    {
        return $category['format'] === 'team' ? 'team' : 'registration';
    }

    private function assertDrawable(array $category): void
    {
        if (in_array($category['status'], ['merged', 'cancelled'], true)) {
            throw new \DomainException('Esta categoria foi juntada ou cancelada e não tem chave.');
        }
    }

    /** Categoria do evento (nunca de outro evento). */
    private function category(array $championship, int $categoryId): array
    {
        $category = $this->categories->find($categoryId);

        if (!$category || (int) $category['championship_id'] !== (int) $championship['id']) {
            throw new \DomainException('Categoria não encontrada neste evento.');
        }

        return $category;
    }

    /** Categorias para onde o atleta pode ir: mesma disciplina e sexo, ainda abertas ou fechadas. */
    private function moveTargets(array $championship, array $category): array
    {
        $targets = [];

        foreach ($this->categories->rows((int) $championship['id']) as $row) {
            if ((int) $row['id'] === (int) $category['id'] || $row['format'] !== 'individual'
                || $row['discipline_code'] !== $category['discipline_code'] || $row['gender'] !== $category['gender']
                || !in_array($row['status'], ['open', 'closed'], true)) {
                continue;
            }

            $targets[] = ['id' => (int) $row['id'], 'name' => $row['short_name'] ?: $row['name']];
        }

        return $targets;
    }

    private function score(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, 20);
    }

    protected function transaction(callable $work): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $work();
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }
}

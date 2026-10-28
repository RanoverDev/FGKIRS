<?php

namespace Domain\Bracket;

/**
 * Chave de eliminatória simples. Puro: não acessa banco, sessão nem $_POST.
 *
 * As lutas ficam em [rodada][posição]; a rodada 1 é a primeira (as vagas do sorteio) e a última é a
 * final. O vencedor da luta (r, p) ocupa a vaga A de (r+1, p/2) quando p é par e a B quando é ímpar.
 * Quem passa direto (luta com um só atleta na rodada 1) é "bye": avança sozinho e não conta como luta.
 *
 * Participante é só um id inteiro (inscrição ou equipe, o chamador sabe qual).
 */
final class BracketTree
{
    /** @var array<int, array<int, array{a: ?int, b: ?int, winner: ?int}>> */
    private array $matches = [];

    private function __construct(private int $size, private int $rounds)
    {
    }

    /** @param list<?int> $slots vagas da rodada 1, na ordem do sorteio (null = vaga vazia/bye) */
    public static function fromSlots(int $size, array $slots): self
    {
        $tree = self::blank($size);

        for ($i = 0; $i < $size; $i++) {
            $tree->matches[1][intdiv($i, 2)][$i % 2 === 0 ? 'a' : 'b'] = $slots[$i] ?? null;
        }

        $tree->normalize();

        return $tree;
    }

    /** @param list<array{round: int, position: int, a: ?int, b: ?int, winner: ?int}> $rows */
    public static function fromRows(int $size, array $rows): self
    {
        $tree = self::blank($size);

        foreach ($rows as $row) {
            if (isset($tree->matches[(int) $row['round']][(int) $row['position']])) {
                $tree->matches[(int) $row['round']][(int) $row['position']] = [
                    'a'      => $row['a'] === null ? null : (int) $row['a'],
                    'b'      => $row['b'] === null ? null : (int) $row['b'],
                    'winner' => $row['winner'] === null ? null : (int) $row['winner'],
                ];
            }
        }

        return $tree;
    }

    public static function isValidSize(int $size): bool
    {
        return $size >= 2 && ($size & ($size - 1)) === 0;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function rounds(): int
    {
        return $this->rounds;
    }

    /** @return list<array{round: int, position: int, a: ?int, b: ?int, winner: ?int}> */
    public function toRows(): array
    {
        $rows = [];

        foreach ($this->matches as $round => $list) {
            foreach ($list as $position => $m) {
                $rows[] = ['round' => $round, 'position' => $position] + $m;
            }
        }

        return $rows;
    }

    /** @return array{a: ?int, b: ?int, winner: ?int} */
    public function match(int $round, int $position): array
    {
        return $this->matches[$round][$position] ?? throw new \DomainException('Luta não encontrada nesta chave.');
    }

    /** @return list<?int> vagas da rodada 1 */
    public function slots(): array
    {
        $slots = [];

        foreach ($this->matches[1] as $m) {
            $slots[] = $m['a'];
            $slots[] = $m['b'];
        }

        return $slots;
    }

    /** @return list<int> */
    public function participants(): array
    {
        return array_values(array_filter($this->slots(), static fn (?int $id) => $id !== null));
    }

    /** Luta com um só atleta na primeira rodada: ele passa sem lutar. */
    public function isBye(int $round, int $position): bool
    {
        $m = $this->match($round, $position);

        return $round === 1 && (($m['a'] === null) xor ($m['b'] === null));
    }

    /** Há resultado de luta de verdade (com dois atletas)? Os byes não contam. */
    public function hasResults(): bool
    {
        foreach ($this->matches as $list) {
            foreach ($list as $m) {
                if ($m['winner'] !== null && $m['a'] !== null && $m['b'] !== null) {
                    return true;
                }
            }
        }

        return false;
    }

    public function champion(): ?int
    {
        return $this->matches[$this->rounds][0]['winner'];
    }

    public function isFinished(): bool
    {
        return $this->champion() !== null;
    }

    // ───────────────────────────── Resultados ─────────────────────────────

    public function setWinner(int $round, int $position, int $participant): void
    {
        $m = $this->match($round, $position);

        if ($m['a'] === null || $m['b'] === null) {
            throw new \DomainException('Esta luta ainda não tem os dois atletas definidos.');
        }
        if ($participant !== $m['a'] && $participant !== $m['b']) {
            throw new \DomainException('O vencedor precisa ser um dos dois atletas da luta.');
        }
        if ($m['winner'] === $participant) {
            return;
        }
        if ($m['winner'] !== null && $this->nextWinner($round, $position) !== null) {
            throw new \DomainException('Desfaça primeiro o resultado da luta seguinte para trocar o vencedor desta.');
        }

        $this->matches[$round][$position]['winner'] = $participant;
        $this->normalize();
    }

    public function clearWinner(int $round, int $position): void
    {
        $m = $this->match($round, $position);

        if ($m['a'] === null || $m['b'] === null) {
            throw new \DomainException('Esta luta não tem resultado para desfazer.');
        }
        if ($m['winner'] === null) {
            return;
        }
        if ($this->nextWinner($round, $position) !== null) {
            throw new \DomainException('Desfaça primeiro o resultado da luta seguinte.');
        }

        $this->matches[$round][$position]['winner'] = null;
        $this->normalize();
    }

    /** Apaga todos os resultados de luta, mantendo o sorteio. */
    public function clearAllResults(): void
    {
        $this->resetWinners();
        $this->normalize();
    }

    // ───────────────────────────── Sorteio ─────────────────────────────

    /** Troca duas vagas da rodada 1 (uma pode estar vazia). Só antes de haver resultados. */
    public function swapSlots(int $i, int $j): void
    {
        if ($i < 0 || $j < 0 || $i >= $this->size || $j >= $this->size) {
            throw new \DomainException('Vaga inválida.');
        }

        $this->structural(function () use ($i, $j) {
            $slots = $this->slots();
            [$slots[$i], $slots[$j]] = [$slots[$j], $slots[$i]];
            $this->setSlots($slots);
        });
    }

    /** Tira o atleta da chave; o adversário dele passa direto. */
    public function remove(int $participant): void
    {
        $this->structural(function () use ($participant) {
            $this->setSlots(array_map(static fn (?int $id) => $id === $participant ? null : $id, $this->slots()));
        });
    }

    /** Coloca o atleta numa vaga livre (de preferência onde ele pega um adversário). Falso se não houver. */
    public function insert(int $participant): bool
    {
        if (in_array($participant, $this->participants(), true)) {
            return true;
        }

        $slots = $this->slots();
        $free  = array_keys(array_filter($slots, static fn (?int $id) => $id === null));

        if ($free === []) {
            return false;
        }

        // Prefere a vaga cujo par já tem um atleta (o par vira uma luta de verdade)
        usort($free, fn (int $x, int $y) => ($slots[$y ^ 1] !== null) <=> ($slots[$x ^ 1] !== null));

        $this->structural(function () use ($participant, $free) {
            $slots = $this->slots();
            $slots[$free[0]] = $participant;
            $this->setSlots($slots);
        });

        return true;
    }

    // ───────────────────────────── Colocação ─────────────────────────────

    /**
     * Colocação de quem já foi decidido: campeão 1º, vice 2º, derrotados da semifinal 3º (os dois),
     * da quartas 5º, das oitavas 9º, e assim por diante. Só entra quem perdeu uma luta de verdade.
     *
     * @return array<int, int> participante => colocação
     */
    public function placements(): array
    {
        $places = [];

        for ($round = 1; $round <= $this->rounds; $round++) {
            foreach ($this->matches[$round] as $m) {
                if ($m['winner'] === null || $m['a'] === null || $m['b'] === null) {
                    continue;
                }

                $loser          = $m['winner'] === $m['a'] ? $m['b'] : $m['a'];
                $places[$loser] = (2 ** ($this->rounds - $round)) + 1;
            }
        }

        if ($this->champion() !== null) {
            $places[$this->champion()] = 1;
        }

        return $places;
    }

    public static function roundLabel(int $round, int $rounds): string
    {
        return match ($rounds - $round) {
            0       => 'Final',
            1       => 'Semifinal',
            2       => 'Quartas de final',
            3       => 'Oitavas de final',
            default => 'Rodada ' . $round,
        };
    }

    // ───────────────────────────── Internos ─────────────────────────────

    private static function blank(int $size): self
    {
        if (!self::isValidSize($size)) {
            throw new \InvalidArgumentException('O tamanho da chave precisa ser uma potência de 2 (2, 4, 8, 16...).');
        }

        $rounds = (int) log($size, 2);
        $tree   = new self($size, $rounds);

        for ($round = 1; $round <= $rounds; $round++) {
            for ($position = 0; $position < $size >> $round; $position++) {
                $tree->matches[$round][$position] = ['a' => null, 'b' => null, 'winner' => null];
            }
        }

        return $tree;
    }

    /** Mudança de sorteio: só sem resultados; zera os avanços automáticos, aplica e recalcula. */
    private function structural(callable $edit): void
    {
        if ($this->hasResults()) {
            throw new \DomainException('A chave já tem resultados. Limpe os resultados antes de mudar o sorteio.');
        }

        $this->resetWinners();
        $edit();
        $this->normalize();
    }

    private function resetWinners(): void
    {
        foreach ($this->matches as $round => $list) {
            foreach (array_keys($list) as $position) {
                $this->matches[$round][$position]['winner'] = null;
            }
        }
    }

    /** @param list<?int> $slots */
    private function setSlots(array $slots): void
    {
        for ($i = 0; $i < $this->size; $i++) {
            $this->matches[1][intdiv($i, 2)][$i % 2 === 0 ? 'a' : 'b'] = $slots[$i] ?? null;
        }
    }

    private function nextWinner(int $round, int $position): ?int
    {
        return $round < $this->rounds ? $this->matches[$round + 1][$position >> 1]['winner'] : null;
    }

    /**
     * Deixa a chave coerente: bye avança sozinho, vencedor que não está mais na luta é descartado e as
     * vagas das rodadas seguintes vêm sempre dos vencedores da rodada anterior.
     */
    private function normalize(): void
    {
        for ($round = 1; $round <= $this->rounds; $round++) {
            foreach ($this->matches[$round] as $position => $m) {
                $winner = $m['winner'];

                if ($round === 1 && (($m['a'] === null) xor ($m['b'] === null))) {
                    $winner = $m['a'] ?? $m['b'];
                } elseif ($m['a'] === null || $m['b'] === null || ($winner !== $m['a'] && $winner !== $m['b'])) {
                    $winner = null;
                }

                $this->matches[$round][$position]['winner'] = $winner;

                if ($round < $this->rounds) {
                    $this->matches[$round + 1][$position >> 1][$position % 2 === 0 ? 'a' : 'b'] = $winner;
                }
            }
        }
    }
}

<?php

namespace Domain\Bracket;

/** Sorteio da chave: tamanho, ordem das vagas, byes para os primeiros sorteados e dojos separados na 1ª rodada. */
final class BracketGenerator
{
    /** Menor potência de 2 que comporta todos (mínimo 2). */
    public static function sizeFor(int $participants): int
    {
        $size = 2;

        while ($size < $participants) {
            $size *= 2;
        }

        return $size;
    }

    /**
     * Ordem clássica das cabeças: em chave de 8, as vagas recebem as posições [1,8,4,5,2,7,3,6]; assim os
     * primeiros sorteados enfrentam os últimos e só se encontram no fim.
     *
     * @return list<int>
     */
    public static function seedOrder(int $size): array
    {
        $order = [1];

        while (count($order) < $size) {
            $n    = count($order) * 2;
            $next = [];

            foreach ($order as $seed) {
                $next[] = $seed;
                $next[] = $n + 1 - $seed;
            }

            $order = $next;
        }

        return $order;
    }

    /**
     * @param list<array{id: int, dojo: int}> $participants
     * @param (callable(array): array)|null $shuffle injeção para teste; o padrão embaralha de verdade
     * @return array{size: int, slots: list<?int>}
     */
    public static function draw(array $participants, ?callable $shuffle = null): array
    {
        $size     = self::sizeFor(count($participants));
        $shuffled = array_values(($shuffle ?? self::shuffle(...))($participants));
        $slots    = [];

        // Seeds acima do número de atletas são byes: os primeiros sorteados são os que passam direto
        foreach (self::seedOrder($size) as $seed) {
            $slots[] = $shuffled[$seed - 1]['id'] ?? null;
        }

        $dojoOf = [];
        foreach ($shuffled as $p) {
            $dojoOf[$p['id']] = $p['dojo'];
        }

        return ['size' => $size, 'slots' => self::separateDojos($slots, $dojoOf)];
    }

    /**
     * Desfaz confrontos entre atletas do mesmo dojo na 1ª rodada, trocando-os de lugar quando existe uma
     * troca que não cria outro confronto igual. Se não houver jeito (dojo com mais da metade dos atletas),
     * o confronto fica.
     *
     * @param list<?int> $slots
     * @param array<int, int> $dojoOf
     * @return list<?int>
     */
    private static function separateDojos(array $slots, array $dojoOf): array
    {
        $clash = static fn (?int $x, ?int $y): bool => $x !== null && $y !== null && $dojoOf[$x] === $dojoOf[$y];
        $count = count($slots);

        for ($pass = 0; $pass < 10; $pass++) {
            $changed = false;

            for ($p = 0; $p < intdiv($count, 2); $p++) {
                if (!$clash($slots[2 * $p], $slots[2 * $p + 1])) {
                    continue;
                }

                for ($j = 0; $j < $count; $j++) {
                    if ($slots[$j] === null || intdiv($j, 2) === $p) {
                        continue;
                    }

                    $mate = $slots[$j ^ 1];

                    if (!$clash($slots[2 * $p], $slots[$j]) && !$clash($slots[2 * $p + 1], $mate)) {
                        [$slots[2 * $p + 1], $slots[$j]] = [$slots[$j], $slots[2 * $p + 1]];
                        $changed = true;
                        break;
                    }
                }
            }

            if (!$changed) {
                break;
            }
        }

        return $slots;
    }

    private static function shuffle(array $items): array
    {
        for ($i = count($items) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }

        return $items;
    }
}

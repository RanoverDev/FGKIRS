<?php

namespace Domain\Ranking;

/** Pontos do ranking por colocação (a tabela fica aqui para a federação ajustar num lugar só). */
final class PointsTable
{
    private const BY_PLACE = [1 => 10, 2 => 7, 3 => 5, 5 => 3];

    /** Participação (9º em diante) vale 1 ponto. Categoria sem disputa (um só atleta) não pontua. */
    public static function forPlace(int $place, bool $contested = true): int
    {
        if (!$contested || $place < 1) {
            return 0;
        }

        return self::BY_PLACE[$place] ?? 1;
    }

    public static function placeLabel(int $place): string
    {
        return $place . 'º';
    }
}

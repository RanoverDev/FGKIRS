<?php

namespace Domain\Championship;

final class FeeCalculator
{
    /**
     * Primeira categoria cobra fee_first_entry e as demais fee_extra_entry. Sem taxa configurada
     * no evento o total é null (não cobra); sem taxa extra, as demais custam o mesmo que a primeira.
     */
    public static function total(int $entryCount, ?float $first, ?float $extra): ?float
    {
        if ($first === null) {
            return null;
        }

        if ($entryCount <= 0) {
            return 0.0;
        }

        return round($first + ($entryCount - 1) * ($extra ?? $first), 2);
    }
}

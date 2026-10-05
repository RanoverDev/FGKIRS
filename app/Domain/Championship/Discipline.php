<?php

namespace Domain\Championship;

final readonly class Discipline
{
    /**
     * @param int[] $ageDivisionIds divisões etárias em que a disciplina é disputada
     */
    public function __construct(
        public ?int $id,
        public string $code,
        public string $name,
        public string $modality,
        public string $format,
        public bool $splitGender,
        public bool $splitBelt,
        public bool $splitWeight,
        public ?int $teamSize,
        public ?int $teamReserves,
        public array $ageDivisionIds,
        public int $sortOrder = 0,
    ) {
    }

    public function usesAgeDivision(AgeDivision $age): bool
    {
        return in_array($age->id, $this->ageDivisionIds, true);
    }
}

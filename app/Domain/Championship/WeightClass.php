<?php

namespace Domain\Championship;

/** weightMin é exclusivo e weightMax inclusivo: "até 50" e "acima de 50" não deixam vão nem sobreposição. */
final readonly class WeightClass
{
    public function __construct(
        public ?int $id,
        public ?int $ageDivisionId,
        public string $gender,
        public string $name,
        public ?float $weightMin,
        public ?float $weightMax,
        public int $sortOrder = 0,
    ) {
    }

    /** Trecho do código da categoria: "50" (até 50), "50+" (acima de 50), "45_50" (faixa fechada). */
    public function codePart(): string
    {
        $format = static fn (float $kg): string => rtrim(rtrim(number_format($kg, 2, '.', ''), '0'), '.');

        return match (true) {
            $this->weightMin !== null && $this->weightMax !== null => $format($this->weightMin) . '_' . $format($this->weightMax),
            $this->weightMin !== null                              => $format($this->weightMin) . '+',
            $this->weightMax !== null                              => $format($this->weightMax),
            default                                                => 'LIVRE',
        };
    }
}

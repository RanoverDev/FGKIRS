<?php

namespace Domain\Championship;

/** Tudo que vira uma linha de event_categories (fase 4), com os limites já copiados do regulamento. */
final readonly class EventCategoryDraft
{
    public function __construct(
        public string $code,
        public string $name,
        public string $modality,
        public string $format,
        public string $gender,
        public ?int $ageMin,
        public ?int $ageMax,
        public ?int $beltLevelMin,
        public ?int $beltLevelMax,
        public ?float $weightMin,
        public ?float $weightMax,
        public ?int $teamSize,
        public ?int $disciplineId,
        public ?int $ageDivisionId,
        public ?int $beltDivisionId,
        public ?int $weightClassId,
        public int $sortOrder,
        public string $disciplineName,
        public string $ageLabel,
    ) {
    }
}

<?php

namespace Domain\Championship;

/** Resultado de EligibilityService::suggest: a categoria única da disciplina, ou o motivo de não haver. */
final readonly class Suggestion
{
    public function __construct(
        public ?EventCategory $category,
        public ?string $reason,
    ) {
    }
}

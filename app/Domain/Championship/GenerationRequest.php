<?php

namespace Domain\Championship;

/** O que o evento escolheu usar do regulamento. */
final readonly class GenerationRequest
{
    /**
     * @param Discipline[]   $disciplines
     * @param AgeDivision[]  $ageDivisions
     * @param BeltDivision[] $beltDivisions
     */
    public function __construct(
        public array $disciplines,
        public array $ageDivisions,
        public array $beltDivisions,
    ) {
    }

    public static function everything(Ruleset $ruleset): self
    {
        return new self($ruleset->disciplines, $ruleset->ageDivisions, $ruleset->beltDivisions);
    }
}

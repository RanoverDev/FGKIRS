<?php

namespace Domain\Championship;

final readonly class Ruleset
{
    /**
     * @param AgeDivision[]  $ageDivisions
     * @param BeltDivision[] $beltDivisions
     * @param WeightClass[]  $weightClasses
     * @param Discipline[]   $disciplines
     */
    public function __construct(
        public ?int $id,
        public string $name,
        public string $agePolicy,
        public array $ageDivisions,
        public array $beltDivisions,
        public array $weightClasses,
        public array $disciplines,
    ) {
    }

    /** @return WeightClass[] */
    public function weightClassesFor(AgeDivision $age, string $gender): array
    {
        $classes = array_filter(
            $this->weightClasses,
            static fn (WeightClass $w) => $w->ageDivisionId === $age->id && $w->gender === $gender
        );

        usort($classes, static fn (WeightClass $a, WeightClass $b) => $a->sortOrder <=> $b->sortOrder);

        return $classes;
    }
}

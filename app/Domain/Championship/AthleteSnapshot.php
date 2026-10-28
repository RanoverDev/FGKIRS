<?php

namespace Domain\Championship;

/** O atleta como ele estava no dia do evento: é o que a elegibilidade enxerga. */
final readonly class AthleteSnapshot
{
    public function __construct(
        public string $gender,
        public int $age,
        public ?int $beltLevel,
        public ?float $weight,
        public bool $isParaKarate = false,
        public bool $isBlackBelt = false,
        public bool $hasFgkirsRegistration = true,
        public ?float $height = null,
    ) {
    }

    public static function of(
        string $gender,
        string $birthDate,
        string $eventDate,
        AgePolicy $policy,
        ?int $beltLevel,
        ?float $weight,
        bool $isParaKarate = false,
        bool $isBlackBelt = false,
        bool $hasFgkirsRegistration = true,
        ?float $height = null,
    ): self {
        return new self(
            $gender,
            $policy->ageOn(new \DateTimeImmutable($birthDate), new \DateTimeImmutable($eventDate)),
            $beltLevel,
            $weight,
            $isParaKarate,
            $isBlackBelt,
            $hasFgkirsRegistration,
            $height,
        );
    }
}

<?php

namespace Domain\Championship;

enum AgePolicy: string
{
    case EventDate = 'event_date';
    case YearEnd   = 'year_end';

    public static function fromColumn(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::EventDate;
    }

    /**
     * event_date: anos completos no dia do evento. year_end: ano do evento menos ano de nascimento,
     * ou seja, a idade que o atleta completa naquele ano.
     */
    public function ageOn(\DateTimeInterface $birthDate, \DateTimeInterface $eventDate): int
    {
        return match ($this) {
            self::EventDate => (int) $birthDate->diff($eventDate)->format('%r%y'),
            self::YearEnd   => (int) $eventDate->format('Y') - (int) $birthDate->format('Y'),
        };
    }
}

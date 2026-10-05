<?php

namespace Tests\Domain;

use Domain\Championship\AgePolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AgePolicyTest extends TestCase
{
    public static function eventDateCases(): array
    {
        return [
            'aniversário no dia do evento'    => ['2010-06-15', '2026-06-15', 16],
            'véspera do aniversário'          => ['2010-06-15', '2026-06-14', 15],
            'dia seguinte ao aniversário'     => ['2010-06-15', '2026-06-16', 16],
            'nascido em 29/02, véspera'       => ['2008-02-29', '2026-02-28', 17],
            'nascido em 29/02, em 01/03'      => ['2008-02-29', '2026-03-01', 18],
            'nascido em 29/02, ano bissexto'  => ['2008-02-29', '2028-02-29', 20],
            'evento antes do nascimento'      => ['2026-06-15', '2026-06-14', 0],
        ];
    }

    #[DataProvider('eventDateCases')]
    public function testEventDatePolicy(string $birth, string $event, int $expected): void
    {
        $age = AgePolicy::EventDate->ageOn(new \DateTimeImmutable($birth), new \DateTimeImmutable($event));

        $this->assertSame($expected, $age);
    }

    public function testYearEndPolicyUsesTheAgeCompletedInTheEventYear(): void
    {
        $event = new \DateTimeImmutable('2026-03-10');

        // Nasceu em dezembro: ainda não fez 16 em março, mas completa no ano
        $this->assertSame(16, AgePolicy::YearEnd->ageOn(new \DateTimeImmutable('2010-12-31'), $event));
        $this->assertSame(16, AgePolicy::YearEnd->ageOn(new \DateTimeImmutable('2010-01-01'), $event));
        $this->assertSame(15, AgePolicy::EventDate->ageOn(new \DateTimeImmutable('2010-12-31'), $event));
    }

    public function testPoliciesDifferAtTheYearTurn(): void
    {
        $birth = new \DateTimeImmutable('2014-11-20');

        $this->assertSame(11, AgePolicy::EventDate->ageOn($birth, new \DateTimeImmutable('2026-05-01')));
        $this->assertSame(12, AgePolicy::YearEnd->ageOn($birth, new \DateTimeImmutable('2026-05-01')));
    }

    public function testFromColumnFallsBackToEventDate(): void
    {
        $this->assertSame(AgePolicy::YearEnd, AgePolicy::fromColumn('year_end'));
        $this->assertSame(AgePolicy::EventDate, AgePolicy::fromColumn('qualquer coisa'));
        $this->assertSame(AgePolicy::EventDate, AgePolicy::fromColumn(null));
    }
}

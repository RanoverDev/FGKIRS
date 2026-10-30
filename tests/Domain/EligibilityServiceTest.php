<?php

namespace Tests\Domain;

use Domain\Championship\AthleteSnapshot;
use Domain\Championship\EligibilityService;
use Domain\Championship\EventCategory;
use PHPUnit\Framework\TestCase;

final class EligibilityServiceTest extends TestCase
{
    private EligibilityService $service;

    protected function setUp(): void
    {
        $this->service = new EligibilityService();
    }

    private function category(int $id, array $o = []): EventCategory
    {
        return new EventCategory(
            id: $id,
            code: $o['code'] ?? "C$id",
            name: $o['name'] ?? "Categoria $id",
            shortName: $o['short'] ?? "Cat $id",
            disciplineCode: $o['discipline'] ?? 'KMI',
            disciplineName: 'Kumite Individual',
            modality: $o['modality'] ?? 'kumite',
            format: $o['format'] ?? 'individual',
            gender: $o['gender'] ?? 'M',
            ageMin: array_key_exists('ageMin', $o) ? $o['ageMin'] : 11,
            ageMax: array_key_exists('ageMax', $o) ? $o['ageMax'] : 12,
            beltLevelMin: $o['beltMin'] ?? null,
            beltLevelMax: $o['beltMax'] ?? null,
            weightMin: $o['wMin'] ?? null,
            weightMax: $o['wMax'] ?? null,
            teamSize: null,
            teamReserves: null,
            isParaKarate: $o['para'] ?? false,
            maxEntries: $o['max'] ?? null,
            status: $o['status'] ?? 'open',
            enrolled: $o['enrolled'] ?? 0,
            measure: $o['measure'] ?? 'weight',
        );
    }

    private function athlete(array $o = []): AthleteSnapshot
    {
        return new AthleteSnapshot(
            gender: $o['gender'] ?? 'M',
            age: $o['age'] ?? 12,
            beltLevel: array_key_exists('belt', $o) ? $o['belt'] : 3,
            weight: array_key_exists('weight', $o) ? $o['weight'] : 40.0,
            isParaKarate: $o['para'] ?? false,
            isBlackBelt: $o['black'] ?? false,
            hasFgkirsRegistration: $o['reg'] ?? true,
            height: $o['height'] ?? null,
        );
    }

    private function reasonFor(AthleteSnapshot $athlete, EventCategory $category): ?string
    {
        return $this->service->evaluate($athlete, [$category])['rejected'][$category->id] ?? null;
    }

    public function testHeightCategoryUsesHeightAndIgnoresWeight(): void
    {
        $upTo155 = $this->category(1, ['measure' => 'height', 'wMax' => 155.0]);
        $above155 = $this->category(2, ['measure' => 'height', 'wMin' => 155.0]);

        $short = $this->athlete(['weight' => null, 'height' => 155.0]);
        $tall = $this->athlete(['weight' => null, 'height' => 156.0]);

        $this->assertNull($this->reasonFor($short, $upTo155));
        $this->assertSame('Altura fora da divisão', $this->reasonFor($short, $above155));
        $this->assertSame('Altura fora da divisão', $this->reasonFor($tall, $upTo155));
        $this->assertNull($this->reasonFor($tall, $above155));
    }

    public function testHeightCategoryAsksForHeightWhenMissing(): void
    {
        $this->assertSame(
            'Informe a altura',
            $this->reasonFor($this->athlete(['height' => null]), $this->category(1, ['measure' => 'height', 'wMax' => 155.0]))
        );
    }

    public function testWeightCategoryIgnoresHeight(): void
    {
        $this->assertNull($this->reasonFor(
            $this->athlete(['weight' => 40.0, 'height' => 190.0]),
            $this->category(1, ['wMax' => 50.0])
        ));
    }

    public function testParaAthleteIsSuggestedTheParaCategoryOverTheAgeOne(): void
    {
        $regular = $this->category(1, ['gender' => 'X', 'ageMin' => null, 'ageMax' => 99]);
        $para    = $this->category(2, ['gender' => 'X', 'ageMin' => null, 'ageMax' => null, 'para' => true]);

        $paraAthlete = $this->service->suggest($this->athlete(['para' => true]), [$regular, $para], 'KMI');
        $this->assertSame(2, $paraAthlete->category?->id);

        $other = $this->service->suggest($this->athlete(['para' => false]), [$regular, $para], 'KMI');
        $this->assertSame(1, $other->category?->id);
    }

    public function testEligibleCategoryIsReturned(): void
    {
        $result = $this->service->evaluate($this->athlete(), [$this->category(1)]);

        $this->assertCount(1, $result['eligible']);
        $this->assertSame([], $result['rejected']);
    }

    public function testEachRejectionReason(): void
    {
        $this->assertSame('Categoria fechada', $this->reasonFor($this->athlete(), $this->category(1, ['status' => 'closed'])));
        $this->assertSame('Categoria fechada', $this->reasonFor($this->athlete(), $this->category(1, ['status' => 'merged'])));
        $this->assertSame('Sexo diferente', $this->reasonFor($this->athlete(['gender' => 'F']), $this->category(1)));
        $this->assertSame('Idade 13 fora da faixa', $this->reasonFor($this->athlete(['age' => 13]), $this->category(1)));
        $this->assertSame('Idade 10 fora da faixa', $this->reasonFor($this->athlete(['age' => 10]), $this->category(1)));
        $this->assertSame('Graduação fora da divisão', $this->reasonFor($this->athlete(['belt' => 11]), $this->category(1, ['beltMin' => 1, 'beltMax' => 10])));
        $this->assertSame('Graduação sem nível definido', $this->reasonFor($this->athlete(['belt' => null]), $this->category(1, ['beltMin' => 1, 'beltMax' => 10])));
        $this->assertSame('Informe o peso', $this->reasonFor($this->athlete(['weight' => null]), $this->category(1, ['wMax' => 50.0])));
        $this->assertSame('Peso fora da divisão', $this->reasonFor($this->athlete(['weight' => 55.0]), $this->category(1, ['wMax' => 50.0])));
        $this->assertSame('Exclusiva para-karatê', $this->reasonFor($this->athlete(), $this->category(1, ['para' => true])));
        $this->assertSame('Vagas esgotadas', $this->reasonFor($this->athlete(), $this->category(1, ['max' => 5, 'enrolled' => 5])));
    }

    public function testParaAthleteFitsParaCategory(): void
    {
        $this->assertNull($this->reasonFor($this->athlete(['para' => true]), $this->category(1, ['para' => true])));
    }

    public function testBlackBeltWithoutRegistrationIsBlockedEverywhere(): void
    {
        $athlete = $this->athlete(['black' => true, 'reg' => false]);

        $this->assertSame('Faixa preta exige matrícula FGKIRS', $this->reasonFor($athlete, $this->category(1)));
        $this->assertSame('Faixa preta exige matrícula FGKIRS', $this->service->suggest($athlete, [$this->category(1)], 'KMI')->reason);
    }

    public function testWeightExactlyAtTheLimit(): void
    {
        $upTo50    = $this->category(1, ['wMax' => 50.0]);
        $above50   = $this->category(2, ['wMin' => 50.0]);
        $fifty     = $this->athlete(['weight' => 50.00]);
        $fiftyAndC = $this->athlete(['weight' => 50.01]);

        $this->assertNull($this->reasonFor($fifty, $upTo50));
        $this->assertSame('Peso fora da divisão', $this->reasonFor($fifty, $above50));
        $this->assertSame('Peso fora da divisão', $this->reasonFor($fiftyAndC, $upTo50));
        $this->assertNull($this->reasonFor($fiftyAndC, $above50));
    }

    public function testGenderXAcceptsAnyGender(): void
    {
        $open = $this->category(1, ['gender' => 'X']);

        $this->assertNull($this->reasonFor($this->athlete(['gender' => 'M']), $open));
        $this->assertNull($this->reasonFor($this->athlete(['gender' => 'F']), $open));
    }

    public function testCategoryWithoutBeltLimitAcceptsAthleteWithoutLevel(): void
    {
        $this->assertNull($this->reasonFor($this->athlete(['belt' => null]), $this->category(1)));
    }

    public function testSuggestReturnsTheSingleMatchingCategory(): void
    {
        $categories = [
            $this->category(1, ['wMax' => 50.0, 'beltMin' => 1, 'beltMax' => 10]),
            $this->category(2, ['wMin' => 50.0, 'beltMin' => 1, 'beltMax' => 10]),
            $this->category(3, ['wMax' => 50.0, 'beltMin' => 11, 'beltMax' => 20]),
            $this->category(4, ['gender' => 'F', 'wMax' => 50.0, 'beltMin' => 1, 'beltMax' => 10]),
        ];

        $suggestion = $this->service->suggest($this->athlete(['weight' => 40.0]), $categories, 'KMI');

        $this->assertSame(1, $suggestion->category?->id);
        $this->assertNull($suggestion->reason);
    }

    public function testSuggestIgnoresOtherDisciplinesAndTeamCategories(): void
    {
        $categories = [
            $this->category(1, ['discipline' => 'KTI', 'modality' => 'kata']),
            $this->category(2, ['format' => 'team']),
        ];

        $suggestion = $this->service->suggest($this->athlete(), $categories, 'KMI');

        $this->assertNull($suggestion->category);
        $this->assertSame('Disciplina sem categorias neste evento', $suggestion->reason);
    }

    public function testSuggestAsksForWeightWhenOnlyTheWeightIsMissing(): void
    {
        $categories = [$this->category(1, ['wMax' => 50.0]), $this->category(2, ['wMin' => 50.0])];

        $suggestion = $this->service->suggest($this->athlete(['weight' => null]), $categories, 'KMI');

        $this->assertNull($suggestion->category);
        $this->assertSame('Informe o peso', $suggestion->reason);
    }

    public function testSuggestExplainsAgeOutsideTheEventRanges(): void
    {
        $categories = [$this->category(1), $this->category(2, ['gender' => 'F'])];

        $suggestion = $this->service->suggest($this->athlete(['age' => 6]), $categories, 'KMI');

        $this->assertNull($suggestion->category);
        $this->assertSame('Idade 6 fora das faixas do evento', $suggestion->reason);
    }

    public function testSuggestRefusesWhenTheRulesetOverlaps(): void
    {
        $categories = [$this->category(1, ['code' => 'A']), $this->category(2, ['code' => 'B'])];

        $previous = ini_set('error_log', '/dev/null');
        $suggestion = $this->service->suggest($this->athlete(), $categories, 'KMI');
        ini_set('error_log', (string) $previous);

        $this->assertNull($suggestion->category);
        $this->assertStringContainsString('Mais de uma categoria', (string) $suggestion->reason);
    }

    public function testSuggestSkipsClosedCategories(): void
    {
        $categories = [$this->category(1, ['status' => 'closed'])];

        $suggestion = $this->service->suggest($this->athlete(), $categories, 'KMI');

        $this->assertNull($suggestion->category);
        $this->assertSame('Categoria fechada', $suggestion->reason);
    }

    public function testClosedCategoryThatFitsIsTheClosestMatch(): void
    {
        $categories = [
            $this->category(1, ['status' => 'closed']),
            $this->category(2, ['gender' => 'F']),
            $this->category(3, ['ageMin' => 15, 'ageMax' => 17]),
        ];

        $suggestion = $this->service->suggest($this->athlete(), $categories, 'KMI');

        $this->assertNull($suggestion->category);
        $this->assertSame('Categoria fechada', $suggestion->reason);
    }

    public function testAsOpenReevaluatesAClosedCategoryByTheOtherRules(): void
    {
        $closed = $this->category(1, ['status' => 'closed', 'wMax' => 50.0]);

        $this->assertSame('Categoria fechada', $this->reasonFor($this->athlete(), $closed));
        $this->assertNull($this->reasonFor($this->athlete(), $closed->asOpen()));
        $this->assertSame('Peso fora da divisão', $this->reasonFor($this->athlete(['weight' => 60.0]), $closed->asOpen()));
        $this->assertSame('merged', $this->category(2, ['status' => 'merged'])->asOpen()->status, 'juntada continua juntada');
    }
}

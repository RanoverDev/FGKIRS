<?php

namespace Tests\Domain;

use Domain\Championship\AgeDivision;
use Domain\Championship\CategoryGenerator;
use Domain\Championship\Discipline;
use Domain\Championship\EventCategoryDraft;
use Domain\Championship\GenerationRequest;
use Domain\Championship\Ruleset;
use Domain\Championship\WeightClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\FgkirsRuleset;

final class CategoryGeneratorTest extends TestCase
{
    private CategoryGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new CategoryGenerator();
    }

    /** @return EventCategoryDraft[] */
    private function generateAll(Ruleset $ruleset): array
    {
        return $this->generator->generate($ruleset, GenerationRequest::everything($ruleset));
    }

    private static function beltGroup(?int $levelMin): string
    {
        return match ($levelMin) {
            null    => 'any',
            11      => 'black',
            default => 'colored',
        };
    }

    public function testSeedGeneratesTheOfficialSetOf88Categories(): void
    {
        $drafts = $this->generateAll(FgkirsRuleset::build());

        $this->assertCount(88, $drafts);

        $byDiscipline = [];
        foreach ($drafts as $d) {
            $byDiscipline[$d->disciplineCode] = ($byDiscipline[$d->disciplineCode] ?? 0) + 1;
        }

        // Kata individual: 2 sexos × 6 idades × 2 faixas. Kumite individual: o mesmo × 2 pesos.
        // Kata equipe: 2 sexos × 2 idades. Kumite equipe: 2 sexos × 6 idades.
        $this->assertSame(['KTI' => 24, 'KMI' => 48, 'KTE' => 4, 'KME' => 12], $byDiscipline);

        $names = array_map(static fn (EventCategoryDraft $d) => str_replace(' (provisório)', '', $d->name), $drafts);

        foreach ([
            'Kata Individual — Masculino — Infantil (11 a 12) — Faixa Colorida',
            'Kumite Individual — Feminino — Master (36 acima) — Faixa Preta — acima de 50kg',
            'Kata Equipe — Masculino — Até 14 anos',
            'Kata Equipe — Feminino — 15 anos acima',
            'Kumite Equipe (Revezamento) — Masculino — Mirim (7 a 10)',
        ] as $expected) {
            $this->assertContains($expected, $names);
        }
    }

    public function testLimitsAreCopiedFromTheDivisions(): void
    {
        $byCode = [];
        foreach ($this->generateAll(FgkirsRuleset::build()) as $d) {
            $byCode[$d->code] = $d;
        }

        $kumite = $byCode['KMI-M-INF-COL-50'];
        $this->assertSame([11, 12, 1, 10, null, 50.0], [$kumite->ageMin, $kumite->ageMax, $kumite->beltLevelMin, $kumite->beltLevelMax, $kumite->weightMin, $kumite->weightMax]);

        $heavy = $byCode['KMI-F-ADU-PRE-50+'];
        $this->assertSame([11, 20, 50.0, null], [$heavy->beltLevelMin, $heavy->beltLevelMax, $heavy->weightMin, $heavy->weightMax]);

        $team = $byCode['KTE-M-EQ-14'];
        $this->assertSame([null, 14, null, 3], [$team->ageMin, $team->ageMax, $team->beltLevelMin, $team->teamSize]);
    }

    public function testDisciplineWithoutGenderSplitGeneratesGenderX(): void
    {
        $ruleset = FgkirsRuleset::build();
        $open    = new Discipline(9, 'KTO', 'Kata Aberto', 'kata', 'individual', false, false, false, null, null, [1, 2]);

        $drafts = $this->generator->generate($ruleset, new GenerationRequest(
            [$open], $ruleset->ageDivisions, $ruleset->beltDivisions
        ));

        $this->assertCount(2, $drafts);
        foreach ($drafts as $draft) {
            $this->assertSame('X', $draft->gender);
            $this->assertStringNotContainsString('Masculino', $draft->name);
            $this->assertStringContainsString('-X-', $draft->code);
        }
    }

    public function testWeightOnlyAppearsWhenDisciplineSplitsByWeight(): void
    {
        foreach ($this->generateAll(FgkirsRuleset::build()) as $draft) {
            $isWeighted = $draft->weightClassId !== null;

            $this->assertSame(
                $draft->modality === 'kumite' && $draft->format === 'individual',
                $isWeighted,
                $draft->name
            );
            $this->assertSame($isWeighted, $draft->weightMin !== null || $draft->weightMax !== null);
        }
    }

    public function testCodesAreUniqueWithinAGeneration(): void
    {
        $codes = array_map(static fn (EventCategoryDraft $d) => $d->code, $this->generateAll(FgkirsRuleset::build()));

        $this->assertSame($codes, array_values(array_unique($codes)));
        $this->assertContains('KMI-M-INF-COL-50', $codes);
        $this->assertContains('KMI-M-INF-COL-50+', $codes);
        $this->assertContains('KTE-F-EQ-14', $codes);
    }

    public function testDuplicateCodesAreRejected(): void
    {
        $base = FgkirsRuleset::build();
        $age  = $base->ageDivisions[0];

        $ruleset = new Ruleset(1, 'x', 'event_date', [$age], $base->beltDivisions, [
            new WeightClass(1, $age->id, 'M', 'até 50', null, 50.0, 1),
            new WeightClass(2, $age->id, 'M', 'até 50 de novo', null, 50.0, 2),
        ], [$base->disciplines[1]]);

        $this->expectException(\DomainException::class);
        $this->generator->generate($ruleset, GenerationRequest::everything($ruleset));
    }

    public function testChangingAnAgeDivisionChangesAllItsCategories(): void
    {
        $ruleset = FgkirsRuleset::build();
        $before  = $this->generateAll($ruleset);

        $ages = $ruleset->ageDivisions;
        $ages[1] = new AgeDivision(2, 'INF', 'Infantil', 10, 12, 2);
        $changed = new Ruleset(1, $ruleset->name, 'event_date', $ages, $ruleset->beltDivisions, $ruleset->weightClasses, $ruleset->disciplines);
        $after   = $this->generateAll($changed);

        $this->assertCount(count($before), $after);

        $infantil = array_filter($after, static fn (EventCategoryDraft $d) => $d->ageDivisionId === 2);
        // por sexo: kata (2 faixas) + kumite (2 faixas × 2 pesos) + kumite equipe (1)
        $this->assertCount(2 * (2 + 4 + 1), $infantil);
        foreach ($infantil as $draft) {
            $this->assertSame(10, $draft->ageMin, $draft->name);
            $this->assertStringContainsString('Infantil (10 a 12)', $draft->name);
        }

        $others = array_filter($after, static fn (EventCategoryDraft $d) => $d->ageDivisionId !== 2);
        $this->assertSame(
            array_map(static fn ($d) => $d->name, array_filter($before, static fn ($d) => $d->ageDivisionId !== 2)),
            array_map(static fn ($d) => $d->name, $others)
        );
    }

    public function testHeightClassesGenerateCategoriesMeasuredInHeight(): void
    {
        $ruleset = FgkirsRuleset::build();

        // Infanto Juvenil (id 3), feminino: por altura, até 1,55 m e acima de 1,55 m
        $weights = array_values(array_filter(
            $ruleset->weightClasses,
            static fn (WeightClass $w) => !($w->ageDivisionId === 3 && $w->gender === 'F')
        ));
        $weights[] = new WeightClass(911, 3, 'F', 'até 1,55m', null, 155.0, 1, 'height');
        $weights[] = new WeightClass(912, 3, 'F', 'acima de 1,55m', 155.0, null, 2, 'height');

        $changed = new Ruleset(1, $ruleset->name, 'event_date', $ruleset->ageDivisions, $ruleset->beltDivisions, $weights, $ruleset->disciplines);
        $drafts  = array_filter(
            $this->generateAll($changed),
            static fn (EventCategoryDraft $d) => $d->ageDivisionId === 3 && $d->gender === 'F' && $d->disciplineCode === 'KMI'
        );

        $this->assertNotEmpty($drafts);
        foreach ($drafts as $d) {
            $this->assertSame('height', $d->measure);
            $this->assertMatchesRegularExpression('/-H155\+?$/', $d->code);
        }

        $others = array_filter($this->generateAll($changed), static fn (EventCategoryDraft $d) => $d->measure === 'weight');
        $this->assertNotEmpty($others);
    }

    public function testMixedAllBeltsAgeAndParaCategoryGenerateTheTwoExtraBrackets(): void
    {
        $base  = FgkirsRuleset::build();
        $under6 = new AgeDivision(20, 'U6', 'Até 6 anos', null, 6, 0, true, true);

        $disciplines = array_map(
            static fn (Discipline $d) => in_array($d->code, ['KTI', 'KMI'], true)
                ? new Discipline($d->id, $d->code, $d->name, $d->modality, $d->format, $d->splitGender, $d->splitBelt, $d->splitWeight,
                    $d->teamSize, $d->teamReserves, [...$d->ageDivisionIds, 20], $d->sortOrder, true)
                : $d,
            $base->disciplines
        );

        $ruleset = new Ruleset(1, $base->name, 'event_date', [$under6, ...$base->ageDivisions], $base->beltDivisions, $base->weightClasses, $disciplines);
        $before  = count($this->generateAll($base));
        $drafts  = $this->generateAll($ruleset);

        $this->assertCount($before + 4, $drafts);

        $byCode = [];
        foreach ($drafts as $d) {
            $byCode[$d->code] = $d;
        }

        foreach (['KTI', 'KMI'] as $code) {
            $u6 = $byCode["$code-X-U6"];
            $this->assertSame('X', $u6->gender);
            $this->assertNull($u6->ageMin);
            $this->assertSame(6, $u6->ageMax);
            $this->assertNull($u6->beltLevelMin);
            $this->assertNull($u6->weightMin);
            $this->assertFalse($u6->isParaKarate);

            $para = $byCode["$code-PARA"];
            $this->assertTrue($para->isParaKarate);
            $this->assertSame('X', $para->gender);
            $this->assertNull($para->ageMax);
            $this->assertNull($para->beltLevelMin);
        }

        $this->assertArrayNotHasKey('KTE-PARA', $byCode);
        $this->assertArrayNotHasKey('KTI-M-U6', $byCode);
    }

    public function testAddingWeightClassesGrowsOnlyThatAge(): void
    {
        $ruleset = FgkirsRuleset::build();
        $before  = $this->generateAll($ruleset);

        // Infantil (id 2), masculino: três pesos no lugar de dois
        $weights = array_values(array_filter(
            $ruleset->weightClasses,
            static fn (WeightClass $w) => !($w->ageDivisionId === 2 && $w->gender === 'M')
        ));
        $weights[] = new WeightClass(901, 2, 'M', 'até 40kg', null, 40.0, 1);
        $weights[] = new WeightClass(902, 2, 'M', 'de 40 a 55kg', 40.0, 55.0, 2);
        $weights[] = new WeightClass(903, 2, 'M', 'acima de 55kg', 55.0, null, 3);

        $changed = new Ruleset(1, $ruleset->name, 'event_date', $ruleset->ageDivisions, $ruleset->beltDivisions, $weights, $ruleset->disciplines);
        $after   = $this->generateAll($changed);

        // 2 faixas × (3 - 2) pesos no kumite individual masculino infantil
        $this->assertCount(count($before) + 2, $after);

        $countByAge = static function (array $drafts): array {
            $counts = [];
            foreach ($drafts as $d) {
                $counts[$d->ageDivisionId] = ($counts[$d->ageDivisionId] ?? 0) + 1;
            }
            ksort($counts);

            return $counts;
        };

        $diff = array_filter(
            array_map(
                static fn ($age) => ($countByAge($after)[$age] ?? 0) - ($countByAge($before)[$age] ?? 0),
                array_keys($countByAge($after))
            )
        );

        $this->assertSame([1 => 2], $diff);
    }

    public function testWithoutWeightClassesTheCategoryGoesOutWithoutWeightLimit(): void
    {
        $base    = FgkirsRuleset::build();
        $ruleset = new Ruleset(1, 'x', 'event_date', [$base->ageDivisions[0]], $base->beltDivisions, [], [$base->disciplines[1]]);

        $drafts = $this->generator->generate($ruleset, GenerationRequest::everything($ruleset));

        $this->assertCount(4, $drafts); // 2 sexos × 2 faixas
        foreach ($drafts as $draft) {
            $this->assertNull($draft->weightClassId);
            $this->assertNull($draft->weightMin);
            $this->assertNull($draft->weightMax);
        }
    }

    public function testRequestNarrowsTheGeneration(): void
    {
        $ruleset = FgkirsRuleset::build();

        $drafts = $this->generator->generate($ruleset, new GenerationRequest(
            [$ruleset->disciplines[0]],
            [$ruleset->ageDivisions[0]],
            [$ruleset->beltDivisions[1]],
        ));

        $this->assertSame(
            ['KTI-M-MIR-PRE', 'KTI-F-MIR-PRE'],
            array_map(static fn (EventCategoryDraft $d) => $d->code, $drafts)
        );
    }

    public function testCodeAndLabelFormats(): void
    {
        $this->assertSame('50', (new WeightClass(null, 1, 'M', 'a', null, 50.0))->codePart());
        $this->assertSame('50+', (new WeightClass(null, 1, 'M', 'a', 50.0, null))->codePart());
        $this->assertSame('45_52.5', (new WeightClass(null, 1, 'M', 'a', 45.0, 52.5))->codePart());
        $this->assertSame('Infantil (11 a 12)', (new AgeDivision(1, 'INF', 'Infantil', 11, 12))->label());
        $this->assertSame('Master (36 acima)', (new AgeDivision(1, 'MAS', 'Master', 36, null))->label());
        $this->assertSame('Até 14 anos', (new AgeDivision(1, 'EQ-14', 'Até 14 anos', null, 14))->label());
    }
}

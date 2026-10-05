<?php

namespace Tests\Domain;

use Domain\Championship\AgeDivision;
use Domain\Championship\CategoryGenerator;
use Domain\Championship\Discipline;
use Domain\Championship\EventCategoryDraft;
use Domain\Championship\GenerationRequest;
use Domain\Championship\Ruleset;
use Domain\Championship\WeightClass;
use Models\CompetitionCategory;
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

    /** Chave que descreve uma categoria pelos limites, ignorando ordem e o aviso de peso provisório. */
    private static function signature(
        string $name,
        string $modality,
        string $format,
        string $gender,
        ?int $ageMin,
        ?int $ageMax,
        string $beltGroup,
        ?float $weightMin,
        ?float $weightMax,
        ?int $teamSize
    ): string {
        return json_encode([
            str_replace(' (provisório)', '', $name),
            $modality, $format, $gender, $ageMin, $ageMax, $beltGroup, $weightMin, $weightMax, $teamSize,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function testSeedGeneratesExactlyTheCurrentCatalog(): void
    {
        $generated = array_map(
            fn (EventCategoryDraft $d) => self::signature(
                $d->name, $d->modality, $d->format, $d->gender, $d->ageMin, $d->ageMax,
                self::beltGroup($d->beltLevelMin), $d->weightMin, $d->weightMax, $d->teamSize
            ),
            $this->generateAll(FgkirsRuleset::build())
        );

        $catalog = array_map(
            static fn (array $r) => self::signature(
                $r['name'], $r['modality'], $r['entry_type'], $r['gender'], $r['age_min'], $r['age_max'],
                $r['belt_group'],
                $r['weight_min'] !== null ? (float) $r['weight_min'] : null,
                $r['weight_max'] !== null ? (float) $r['weight_max'] : null,
                $r['team_size']
            ),
            CompetitionCategory::defaultCatalog()
        );

        $this->assertCount(88, $generated);
        $this->assertEqualsCanonicalizing($catalog, $generated);
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

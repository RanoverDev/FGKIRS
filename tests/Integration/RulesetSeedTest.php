<?php

namespace Tests\Integration;

use Domain\Championship\CategoryGenerator;
use Domain\Championship\GenerationRequest;
use Models\CompetitionCategory;
use PHPUnit\Framework\TestCase;
use Repositories\RulesetRepository;

/**
 * Confere o seed SQL (004) contra o catálogo atual. Precisa de um banco com as migrations
 * aplicadas; sem FGKIRS_TEST_DB_HOST o teste é pulado.
 *
 * FGKIRS_TEST_DB_HOST=127.0.0.1\;port=3306 FGKIRS_TEST_DB_NAME=t FGKIRS_TEST_DB_USER=root vendor/bin/phpunit --testsuite Integration
 */
final class RulesetSeedTest extends TestCase
{
    protected function setUp(): void
    {
        $host = getenv('FGKIRS_TEST_DB_HOST');

        if ($host === false || $host === '') {
            $this->markTestSkipped('Defina FGKIRS_TEST_DB_HOST, FGKIRS_TEST_DB_NAME e FGKIRS_TEST_DB_USER para rodar.');
        }

        defined('DB_HOST') || define('DB_HOST', $host);
        defined('DB_NAME') || define('DB_NAME', (string) getenv('FGKIRS_TEST_DB_NAME'));
        defined('DB_USER') || define('DB_USER', (string) getenv('FGKIRS_TEST_DB_USER'));
        defined('DB_PASS') || define('DB_PASS', (string) getenv('FGKIRS_TEST_DB_PASS'));
    }

    public function testSeededRulesetGeneratesTheCurrentCatalog(): void
    {
        $repository = new RulesetRepository();
        $default    = array_values(array_filter($repository->all(), static fn (array $r) => $r['is_default']))[0] ?? null;

        $this->assertNotNull($default, 'Nenhum regulamento padrão encontrado.');

        $ruleset = $repository->load((int) $default['id']);
        $drafts  = (new CategoryGenerator())->generate($ruleset, GenerationRequest::everything($ruleset));

        $signature = static fn (string $name, string $modality, string $format, string $gender, ?int $min, ?int $max, ?float $wMin, ?float $wMax): string
            => json_encode([str_replace(' (provisório)', '', $name), $modality, $format, $gender, $min, $max, $wMin, $wMax], JSON_UNESCAPED_UNICODE);

        $generated = array_map(
            static fn ($d) => $signature($d->name, $d->modality, $d->format, $d->gender, $d->ageMin, $d->ageMax, $d->weightMin, $d->weightMax),
            $drafts
        );

        $catalog = array_map(
            static fn (array $r) => $signature(
                $r['name'], $r['modality'], $r['entry_type'], $r['gender'], $r['age_min'], $r['age_max'],
                $r['weight_min'] !== null ? (float) $r['weight_min'] : null,
                $r['weight_max'] !== null ? (float) $r['weight_max'] : null
            ),
            CompetitionCategory::defaultCatalog()
        );

        $this->assertCount(88, $drafts);
        $this->assertEqualsCanonicalizing($catalog, $generated);
    }
}

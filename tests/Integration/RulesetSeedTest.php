<?php

namespace Tests\Integration;

use Domain\Championship\CategoryGenerator;
use Domain\Championship\GenerationRequest;
use PHPUnit\Framework\TestCase;
use Repositories\RulesetRepository;

/**
 * Confere que o seed SQL (004) gera as 88 categorias oficiais. Precisa de um banco com as migrations
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

    public function testSeededRulesetGeneratesTheOfficialCategories(): void
    {
        $repository = new RulesetRepository();
        $default    = array_values(array_filter($repository->all(), static fn (array $r) => $r['is_default']))[0] ?? null;

        $this->assertNotNull($default, 'Nenhum regulamento padrão encontrado.');

        $ruleset = $repository->load((int) $default['id']);
        $drafts  = (new CategoryGenerator())->generate($ruleset, GenerationRequest::everything($ruleset));

        $byDiscipline = [];
        foreach ($drafts as $d) {
            $byDiscipline[$d->disciplineCode] = ($byDiscipline[$d->disciplineCode] ?? 0) + 1;
        }

        $this->assertCount(88, $drafts);
        $this->assertSame(['KTI' => 24, 'KMI' => 48, 'KTE' => 4, 'KME' => 12], $byDiscipline);
    }
}

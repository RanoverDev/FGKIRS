<?php

namespace Tests\Domain;

use Domain\Bracket\BracketGenerator;
use Domain\Bracket\BracketTree;
use Domain\Ranking\PointsTable;
use PHPUnit\Framework\TestCase;

final class BracketGeneratorTest extends TestCase
{
    private static function people(int $n, callable $dojo): array
    {
        return array_map(static fn (int $i) => ['id' => $i, 'dojo' => $dojo($i)], range(1, $n));
    }

    private static function keep(array $items): array
    {
        return $items;
    }

    public function testSizeIsNextPowerOfTwoWithMinimumOfTwo(): void
    {
        $this->assertSame(2, BracketGenerator::sizeFor(0));
        $this->assertSame(2, BracketGenerator::sizeFor(1));
        $this->assertSame(2, BracketGenerator::sizeFor(2));
        $this->assertSame(4, BracketGenerator::sizeFor(3));
        $this->assertSame(8, BracketGenerator::sizeFor(5));
        $this->assertSame(16, BracketGenerator::sizeFor(16));
        $this->assertSame(32, BracketGenerator::sizeFor(17));
    }

    public function testSeedOrderPairsFirstWithLast(): void
    {
        $this->assertSame([1, 4, 2, 3], BracketGenerator::seedOrder(4));
        $this->assertSame([1, 8, 4, 5, 2, 7, 3, 6], BracketGenerator::seedOrder(8));
    }

    public function testEveryParticipantAppearsOnceAndByesAreSpread(): void
    {
        foreach ([1, 2, 3, 5, 6, 7, 9, 13, 16] as $n) {
            $draw  = BracketGenerator::draw(self::people($n, static fn (int $i) => $i), self::keep(...));
            $slots = $draw['slots'];

            $this->assertCount($draw['size'], $slots);
            $present = array_values(array_filter($slots, static fn ($s) => $s !== null));
            sort($present);
            $this->assertSame(range(1, $n), $present, "n=$n");

            $tree = BracketTree::fromSlots($draw['size'], $slots);
            for ($p = 0; $p < $draw['size'] / 2; $p++) {
                $m = $tree->match(1, $p);
                $this->assertFalse($m['a'] === null && $m['b'] === null, "n=$n: luta vazia");
            }
        }
    }

    public function testFirstDrawnAthletesGetTheByes(): void
    {
        $draw = BracketGenerator::draw(self::people(6, static fn (int $i) => $i), self::keep(...));
        $tree = BracketTree::fromSlots($draw['size'], $draw['slots']);

        $byeWinners = [];
        for ($p = 0; $p < 4; $p++) {
            if ($tree->isBye(1, $p)) {
                $byeWinners[] = $tree->match(1, $p)['winner'];
            }
        }
        sort($byeWinners);

        $this->assertSame([1, 2], $byeWinners);
    }

    public function testSameDojoAthletesAreSeparatedInTheFirstRoundWhenPossible(): void
    {
        $people = self::people(8, static fn (int $i) => $i <= 4 ? 100 : 200);

        $draw = BracketGenerator::draw($people, self::keep(...));
        $dojoOf = array_column($people, 'dojo', 'id');

        for ($p = 0; $p < 4; $p++) {
            [$a, $b] = [$draw['slots'][2 * $p], $draw['slots'][2 * $p + 1]];
            $this->assertNotSame($dojoOf[$a], $dojoOf[$b], "luta $p ficou entre atletas do mesmo dojo");
        }
    }

    public function testClashStaysWhenSeparationIsImpossible(): void
    {
        $draw = BracketGenerator::draw(self::people(2, static fn () => 1), self::keep(...));

        $this->assertSame([1, 2], $draw['slots']);
    }

    public function testRandomDrawIsAlwaysAValidBracket(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $n    = random_int(1, 20);
            $draw = BracketGenerator::draw(self::people($n, static fn (int $k) => $k % 3));
            $present = array_values(array_filter($draw['slots'], static fn ($s) => $s !== null));
            sort($present);

            $this->assertSame(range(1, $n), $present);
            $this->assertTrue(BracketTree::isValidSize($draw['size']));
        }
    }

    public function testPointsTable(): void
    {
        $this->assertSame(10, PointsTable::forPlace(1));
        $this->assertSame(7, PointsTable::forPlace(2));
        $this->assertSame(5, PointsTable::forPlace(3));
        $this->assertSame(3, PointsTable::forPlace(5));
        $this->assertSame(1, PointsTable::forPlace(9));
        $this->assertSame(0, PointsTable::forPlace(1, false));
    }
}

<?php

namespace Tests\Domain;

use Domain\Bracket\BracketTree;
use PHPUnit\Framework\TestCase;

final class BracketTreeTest extends TestCase
{
    public function testEightAthletesPlayThroughToChampion(): void
    {
        $tree = BracketTree::fromSlots(8, [1, 2, 3, 4, 5, 6, 7, 8]);

        $this->assertSame(3, $tree->rounds());
        $this->assertFalse($tree->hasResults());

        foreach ([[1, 0, 1], [1, 1, 3], [1, 2, 5], [1, 3, 7]] as [$r, $p, $w]) {
            $tree->setWinner($r, $p, $w);
        }

        $this->assertSame(['a' => 1, 'b' => 3, 'winner' => null], $tree->match(2, 0));
        $this->assertSame(['a' => 5, 'b' => 7, 'winner' => null], $tree->match(2, 1));

        $tree->setWinner(2, 0, 3);
        $tree->setWinner(2, 1, 5);
        $this->assertFalse($tree->isFinished());

        $tree->setWinner(3, 0, 5);

        $this->assertTrue($tree->isFinished());
        $this->assertSame(5, $tree->champion());
        $this->assertSame(
            [2 => 5, 4 => 5, 6 => 5, 8 => 5, 1 => 3, 7 => 3, 3 => 2, 5 => 1],
            $tree->placements()
        );
    }

    public function testPlacementsGiveTwoThirdPlaces(): void
    {
        $tree = BracketTree::fromSlots(4, [10, 20, 30, 40]);
        $tree->setWinner(1, 0, 10);
        $tree->setWinner(1, 1, 30);
        $tree->setWinner(2, 0, 10);

        $this->assertSame([20 => 3, 40 => 3, 30 => 2, 10 => 1], $tree->placements());
    }

    public function testByesAdvanceAloneAndAreNotResults(): void
    {
        $tree = BracketTree::fromSlots(4, [1, null, 2, 3]);

        $this->assertTrue($tree->isBye(1, 0));
        $this->assertFalse($tree->isBye(1, 1));
        $this->assertFalse($tree->hasResults());
        $this->assertSame(1, $tree->match(2, 0)['a']);
        $this->assertNull($tree->match(2, 0)['b']);

        $tree->setWinner(1, 1, 3);
        $this->assertSame(3, $tree->match(2, 0)['b']);

        $tree->setWinner(2, 0, 1);
        $this->assertSame(1, $tree->champion());
        $this->assertSame([2 => 3, 3 => 2, 1 => 1], $tree->placements());
    }

    public function testSingleParticipantIsChampionWithoutFighting(): void
    {
        $tree = BracketTree::fromSlots(2, [7, null]);

        $this->assertTrue($tree->isFinished());
        $this->assertSame(7, $tree->champion());
        $this->assertSame([7 => 1], $tree->placements());
        $this->assertFalse($tree->hasResults());
    }

    public function testCannotPickWinnerOutsideTheMatchOrBeforeBothAreKnown(): void
    {
        $tree = BracketTree::fromSlots(4, [1, 2, 3, 4]);

        $this->expectException(\DomainException::class);
        $tree->setWinner(1, 0, 3);
    }

    public function testCannotDecideAMatchStillWaitingForOpponent(): void
    {
        $tree = BracketTree::fromSlots(4, [1, 2, 3, 4]);

        $this->expectException(\DomainException::class);
        $tree->setWinner(2, 0, 1);
    }

    public function testUndoRequiresNextMatchToBeUndecidedFirst(): void
    {
        $tree = BracketTree::fromSlots(4, [1, 2, 3, 4]);
        $tree->setWinner(1, 0, 1);
        $tree->setWinner(1, 1, 3);
        $tree->setWinner(2, 0, 1);

        try {
            $tree->clearWinner(1, 0);
            $this->fail('devia recusar');
        } catch (\DomainException) {
            $this->assertSame(1, $tree->champion());
        }

        $tree->clearWinner(2, 0);
        $tree->clearWinner(1, 0);

        $this->assertNull($tree->match(2, 0)['a']);
        $this->assertSame(3, $tree->match(2, 0)['b']);
    }

    public function testChangingAWinnerNeedsTheNextMatchUndecided(): void
    {
        $tree = BracketTree::fromSlots(4, [1, 2, 3, 4]);
        $tree->setWinner(1, 0, 1);
        $tree->setWinner(1, 1, 3);
        $tree->setWinner(2, 0, 3);

        $this->expectException(\DomainException::class);
        $tree->setWinner(1, 0, 2);
    }

    public function testChangingAWinnerWithoutDownstreamResultMovesTheNextSlot(): void
    {
        $tree = BracketTree::fromSlots(4, [1, 2, 3, 4]);
        $tree->setWinner(1, 0, 1);
        $tree->setWinner(1, 0, 2);

        $this->assertSame(2, $tree->match(2, 0)['a']);
    }

    public function testSwapSlotsBeforeResultsAndBlockedAfter(): void
    {
        $tree = BracketTree::fromSlots(4, [1, 2, 3, 4]);
        $tree->swapSlots(0, 3);

        $this->assertSame([4, 2, 3, 1], $tree->slots());

        $tree->setWinner(1, 0, 4);

        $this->expectException(\DomainException::class);
        $tree->swapSlots(0, 2);
    }

    public function testSwapWithEmptySlotRecomputesByes(): void
    {
        $tree = BracketTree::fromSlots(4, [1, null, 2, 3]);
        $this->assertSame(1, $tree->match(2, 0)['a']);

        $tree->swapSlots(0, 1);

        $this->assertSame([null, 1, 2, 3], $tree->slots());
        $this->assertSame(1, $tree->match(2, 0)['a']);

        $tree->swapSlots(1, 2);

        $this->assertSame([null, 2, 1, 3], $tree->slots());
        $this->assertSame(2, $tree->match(2, 0)['a']);
        $this->assertNull($tree->match(2, 0)['b']);
    }

    public function testRemoveGivesOpponentAWalkoverAndInsertFillsFreeSlot(): void
    {
        $tree = BracketTree::fromSlots(4, [1, 2, 3, 4]);
        $tree->remove(2);

        $this->assertTrue($tree->isBye(1, 0));
        $this->assertSame(1, $tree->match(2, 0)['a']);

        $this->assertTrue($tree->insert(9));
        $this->assertSame([1, 9, 3, 4], $tree->slots());
        $this->assertFalse($tree->isBye(1, 0));
        $this->assertNull($tree->match(2, 0)['a']);

        $this->assertFalse($tree->insert(10), 'sem vaga livre');
        $this->assertTrue($tree->insert(9), 'quem já está na chave não duplica');
    }

    public function testClearAllResultsKeepsTheDraw(): void
    {
        $tree = BracketTree::fromSlots(4, [1, null, 2, 3]);
        $tree->setWinner(1, 1, 3);
        $this->assertTrue($tree->hasResults());

        $tree->clearAllResults();

        $this->assertFalse($tree->hasResults());
        $this->assertSame([1, null, 2, 3], $tree->slots());
        $this->assertSame(1, $tree->match(2, 0)['a'], 'o bye continua avançando');
        $this->assertNull($tree->match(2, 0)['b']);
    }

    public function testRowsRoundTrip(): void
    {
        $tree = BracketTree::fromSlots(8, [1, 2, 3, null, 5, 6, 7, 8]);
        $tree->setWinner(1, 0, 2);

        $copy = BracketTree::fromRows(8, $tree->toRows());

        $this->assertSame($tree->toRows(), $copy->toRows());
        $this->assertSame(7, count($copy->toRows()));
    }

    public function testRejectsSizesThatAreNotPowersOfTwo(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        BracketTree::fromSlots(6, []);
    }

    public function testRoundLabels(): void
    {
        $this->assertSame('Final', BracketTree::roundLabel(3, 3));
        $this->assertSame('Semifinal', BracketTree::roundLabel(2, 3));
        $this->assertSame('Quartas de final', BracketTree::roundLabel(1, 3));
        $this->assertSame('Oitavas de final', BracketTree::roundLabel(1, 4));
        $this->assertSame('Rodada 1', BracketTree::roundLabel(1, 5));
    }
}

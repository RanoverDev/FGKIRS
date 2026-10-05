<?php

namespace Tests\Domain;

use Domain\Championship\FeeCalculator;
use PHPUnit\Framework\TestCase;

final class FeeCalculatorTest extends TestCase
{
    public function testFirstEntryAndExtras(): void
    {
        $this->assertSame(0.0, FeeCalculator::total(0, 60.0, 40.0));
        $this->assertSame(60.0, FeeCalculator::total(1, 60.0, 40.0));
        $this->assertSame(100.0, FeeCalculator::total(2, 60.0, 40.0));
        $this->assertSame(140.0, FeeCalculator::total(3, 60.0, 40.0));
    }

    public function testNoFeeConfiguredMeansNoCharge(): void
    {
        $this->assertNull(FeeCalculator::total(2, null, 40.0));
        $this->assertNull(FeeCalculator::total(0, null, null));
    }

    public function testMissingExtraFeeRepeatsTheFirst(): void
    {
        $this->assertSame(150.0, FeeCalculator::total(3, 50.0, null));
    }

    public function testFreeFirstEntryWithPaidExtra(): void
    {
        $this->assertSame(30.0, FeeCalculator::total(2, 0.0, 30.0));
    }

    public function testRoundsToCents(): void
    {
        $this->assertSame(0.3, FeeCalculator::total(2, 0.1, 0.2));
    }
}

<?php

namespace Tests\Domain;

use Domain\Championship\RegistrationStatus as S;
use PHPUnit\Framework\TestCase;

final class RegistrationStatusTest extends TestCase
{
    public function testAllowedTransitions(): void
    {
        $this->assertTrue(S::Draft->canTransitionTo(S::Submitted));
        $this->assertTrue(S::Submitted->canTransitionTo(S::Confirmed));
        $this->assertTrue(S::Submitted->canTransitionTo(S::Draft));
        $this->assertTrue(S::Cancelled->canTransitionTo(S::Draft));

        foreach ([S::Draft, S::Submitted, S::Confirmed] as $from) {
            $this->assertTrue($from->canTransitionTo(S::Cancelled), $from->value);
        }
    }

    public function testInvalidTransitionsAreRefused(): void
    {
        $this->assertFalse(S::Draft->canTransitionTo(S::Confirmed), 'confirmar exige envio antes');
        $this->assertFalse(S::Confirmed->canTransitionTo(S::Draft));
        $this->assertFalse(S::Confirmed->canTransitionTo(S::Submitted));
        $this->assertFalse(S::Cancelled->canTransitionTo(S::Confirmed));
        $this->assertFalse(S::Cancelled->canTransitionTo(S::Submitted));

        foreach (S::cases() as $status) {
            $this->assertFalse($status->canTransitionTo($status), "$status->value → $status->value");
        }
    }

    public function testOnlyConfirmedLocksTheSensei(): void
    {
        $this->assertFalse(S::Confirmed->isEditableBySensei());
        $this->assertTrue(S::Draft->isEditableBySensei());
        $this->assertTrue(S::Submitted->isEditableBySensei());
    }
}

<?php

namespace Tests\Domain;

use Models\Athlete;
use PHPUnit\Framework\TestCase;

final class AthleteGuestTest extends TestCase
{
    public function testRegularAthleteIsNotAGuest(): void
    {
        $this->assertSame([['is_guest' => 0, 'guest_origin' => null], null], Athlete::guestFields([]));
        $this->assertSame([['is_guest' => 0, 'guest_origin' => null], null], Athlete::guestFields(['is_guest' => '0', 'guest_origin' => 'Dojo X']));
    }

    public function testGuestNeedsAnOrigin(): void
    {
        [, $error] = Athlete::guestFields(['is_guest' => '1', 'guest_origin' => '   ']);

        $this->assertSame('Informe o dojo ou clube de origem do atleta convidado.', $error);
    }

    public function testGuestKeepsTheTrimmedOriginUpToTheLimit(): void
    {
        [$fields, $error] = Athlete::guestFields(['is_guest' => '1', 'guest_origin' => '  Dojo Shotokan Pelotas ']);

        $this->assertNull($error);
        $this->assertSame(['is_guest' => 1, 'guest_origin' => 'Dojo Shotokan Pelotas'], $fields);

        [$long] = Athlete::guestFields(['is_guest' => '1', 'guest_origin' => str_repeat('a', 300)]);
        $this->assertSame(120, mb_strlen($long['guest_origin']));
    }
}

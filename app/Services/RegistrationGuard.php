<?php

namespace Services;

use Helpers\Auth;
use Helpers\DojoScope;
use Models\Championship;

/**
 * Travas de escrita do módulo de inscrição, aplicadas nos serviços e não nos controllers:
 * escopo de dojo (pela sessão), evento no modelo novo e prazo de inscrição.
 */
final class RegistrationGuard
{
    /** Escrita em nome de um dojo: id de outro dojo recebe 403; fora do prazo só o Presidente passa. */
    public static function assertWritable(array $championship, int $dojoId): void
    {
        DojoScope::assertDojo($dojoId);
        self::assertV2($championship);

        if (!Auth::isAdmin() && !Championship::isRegistrationOpen($championship)) {
            throw RegistrationException::single('As inscrições deste evento estão encerradas.', 403);
        }
    }

    public static function assertAdmin(): void
    {
        if (!Auth::isAdmin()) {
            throw RegistrationException::single('Apenas o Presidente pode fazer esta operação.', 403);
        }
    }

    public static function assertV2(array $championship): void
    {
        if (($championship['registration_model'] ?? 'legacy') !== 'v2') {
            throw RegistrationException::single('Este evento usa o modelo de inscrição antigo.', 422);
        }
    }
}

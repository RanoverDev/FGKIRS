<?php

namespace Domain\Championship;

enum RegistrationStatus: string
{
    case Draft     = 'draft';
    case Submitted = 'submitted';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Rascunho',
            self::Submitted => 'Enviada',
            self::Confirmed => 'Confirmada',
            self::Cancelled => 'Cancelada',
        };
    }

    /** Cancelar vale de qualquer estado; cancelada volta a rascunho se o sensei reinscrever o atleta. */
    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft     => in_array($next, [self::Submitted, self::Cancelled], true),
            self::Submitted => in_array($next, [self::Draft, self::Confirmed, self::Cancelled], true),
            self::Confirmed => $next === self::Cancelled,
            self::Cancelled => $next === self::Draft,
        };
    }

    /** O sensei só mexe enquanto a inscrição não foi confirmada pela federação. */
    public function isEditableBySensei(): bool
    {
        return $this !== self::Confirmed;
    }
}

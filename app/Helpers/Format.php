<?php

namespace Helpers;

/**
 * Formatacao de exibicao. O banco guarda so os digitos; a mascara e
 * responsabilidade da camada de apresentacao.
 */
class Format
{
    public static function phone(?string $digits): string
    {
        $d = preg_replace('/\D/', '', (string) $digits);

        return match (strlen($d)) {
            11      => sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7)),
            10      => sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6)),
            default => $d,
        };
    }

    public static function cpf(?string $value): string
    {
        $d = preg_replace('/\D/', '', (string) $value);

        if (strlen($d) !== 11) {
            return trim((string) $value);
        }

        return sprintf('%s.%s.%s-%s', substr($d, 0, 3), substr($d, 3, 3), substr($d, 6, 3), substr($d, 9));
    }

    public static function cnpj(?string $value): string
    {
        $d = preg_replace('/\D/', '', (string) $value);

        if (strlen($d) !== 14) {
            return trim((string) $value);
        }

        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($d, 0, 2),
            substr($d, 2, 3),
            substr($d, 5, 3),
            substr($d, 8, 4),
            substr($d, 12)
        );
    }

    public static function cep(?string $value): string
    {
        $d = preg_replace('/\D/', '', (string) $value);

        return strlen($d) === 8 ? substr($d, 0, 5) . '-' . substr($d, 5) : trim((string) $value);
    }

    /** "2 inscrições" / "1 inscrição" — o plural do pt-BR nem sempre e so somar "s" */
    public static function plural(int $count, string $singular, string $plural): string
    {
        return $count . ' ' . ($count === 1 ? $singular : $plural);
    }

    /** 49.5 -> "49,5 kg"; null -> "—" */
    public static function weight(?float $kg, string $empty = '—'): string
    {
        return $kg !== null ? number_format($kg, 1, ',', '') . ' kg' : $empty;
    }
}

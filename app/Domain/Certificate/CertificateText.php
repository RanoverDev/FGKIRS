<?php

namespace Domain\Certificate;

/**
 * Texto padrão do certificado de graduação. Sem banco, sessão ou $_POST.
 *
 * Cada dado vem num <span data-var="..."> para a tela de emissão poder atualizar a data e a
 * graduação enquanto o sensei edita; o atributo é descartado ao gravar (ver Helpers\RichText).
 */
final class CertificateText
{
    /** "4º Kyu - Marrom" → "4º KYU"; sem o padrão "Nº Kyu/Dan", usa o nome inteiro em maiúsculas. */
    public static function graduationLabel(string $beltName): string
    {
        $beltName = trim($beltName);

        if (preg_match('/^(\d+\s*[º°o]?)\s*(kyu|dan)\b/iu', $beltName, $m)) {
            $number = preg_replace('/\s+/', '', $m[1]);
            $number = preg_replace('/[°o]$/u', 'º', $number);
            $number = str_ends_with($number, 'º') ? $number : $number . 'º';

            return $number . ' ' . mb_strtoupper($m[2], 'UTF-8');
        }

        return mb_strtoupper($beltName, 'UTF-8');
    }

    public static function defaultBody(
        string $athleteName,
        string $gender,
        string $graduationLabel,
        string $styleName,
        \DateTimeInterface $date,
        string $city,
        string $state,
        string $dojoName
    ): string {
        $e = static fn (string $v): string => htmlspecialchars(mb_strtoupper(trim($v), 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $approved = $gender === 'F' ? 'aprovada' : 'aprovado';
        $location = trim($city) === '' ? '' : $e($city) . (trim($state) === '' ? '' : ' - ' . $e($state));

        return '<p>Certificamos que <b><span data-var="name">' . $e($athleteName) . '</span></b>, registrado na '
            . '<b>FGKIRS</b>, participou dos exames regularmente sendo ' . $approved . ' para '
            . '<b><span data-var="graduation">' . htmlspecialchars($graduationLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span></b> '
            . 'no estilo <b><span data-var="style">' . $e($styleName) . '</span></b> em '
            . '<span data-var="date">' . $date->format('d/m/Y') . '</span> na cidade de '
            . '<b><span data-var="city">' . $location . '</span></b>, no dojo '
            . '<b><span data-var="dojo">' . $e($dojoName) . '</span></b>.</p>'
            . '<p style="text-align:center;font-size:15px;font-weight:700;margin-top:66px">'
            . 'Homologado por esta Federação de Karatê, entidade estadual de Administração do Desporto.</p>';
    }
}

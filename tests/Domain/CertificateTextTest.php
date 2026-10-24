<?php

namespace Tests\Domain;

use Domain\Certificate\CertificateText;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CertificateTextTest extends TestCase
{
    #[DataProvider('labels')]
    public function testGraduationLabel(string $beltName, string $expected): void
    {
        $this->assertSame($expected, CertificateText::graduationLabel($beltName));
    }

    public static function labels(): array
    {
        return [
            'kyu com cor'   => ['4º Kyu - Marrom', '4º KYU'],
            'dan'           => ['1º Dan - Preta', '1º DAN'],
            'sem o º'       => ['10 kyu', '10º KYU'],
            'grau ordinal o' => ['2o Kyu', '2º KYU'],
            'fora do padrão' => ['Faixa Branca', 'FAIXA BRANCA'],
        ];
    }

    public function testDefaultBodyFillsEveryVariable(): void
    {
        $html = CertificateText::defaultBody(
            'maria da silva',
            'F',
            '4º KYU',
            'Goju-ryu',
            new \DateTimeImmutable('2025-08-14'),
            'Campo Bom',
            'RS',
            'Goju Kai Sul'
        );

        $this->assertStringContainsString('MARIA DA SILVA', $html);
        $this->assertStringContainsString('aprovada', $html);
        $this->assertStringContainsString('GOJU-RYU', $html);
        $this->assertStringContainsString('14/08/2025', $html);
        $this->assertStringContainsString('CAMPO BOM - RS', $html);
        $this->assertStringContainsString('GOJU KAI SUL', $html);
        $this->assertStringContainsString('<b><span data-var="city">CAMPO BOM - RS</span></b>', $html);
        $this->assertStringContainsString('<b><span data-var="dojo">GOJU KAI SUL</span></b>', $html);
        $this->assertStringContainsString('Homologado por esta Federação de Karatê', $html);
    }

    public function testDefaultBodyEscapesData(): void
    {
        $html = CertificateText::defaultBody(
            '<script>alert(1)</script>',
            'M',
            '1º DAN',
            'Shotokan',
            new \DateTimeImmutable('2025-01-02'),
            '',
            '',
            'Dojo & Cia'
        );

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('aprovado', $html);
        $this->assertStringContainsString('DOJO &amp; CIA', $html);
    }
}

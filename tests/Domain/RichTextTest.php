<?php

namespace Tests\Domain;

use Helpers\CertificateBackground;
use Helpers\RichText;
use PHPUnit\Framework\TestCase;

final class RichTextTest extends TestCase
{
    public function testKeepsAllowedFormatting(): void
    {
        $out = RichText::sanitize('<p>Olá <b>mundo</b> <i>x</i> <u>y</u><br><span style="color:#ff0000;font-size:24px">z</span></p>');

        $this->assertSame('<p>Olá <b>mundo</b> <i>x</i> <u>y</u><br><span style="color:#ff0000;font-size:24px">z</span></p>', $out);
    }

    public function testDropsScriptsHandlersAndDangerousStyles(): void
    {
        $out = RichText::sanitize(
            '<p onclick="x()">a<script>alert(1)</script><img src=x onerror=alert(1)>'
            . '<a href="javascript:alert(1)">link</a>'
            . '<span style="color:red;background:url(javascript:x);position:fixed;font-size:900px">b</span></p>'
        );

        $this->assertStringNotContainsString('script', $out);
        $this->assertStringNotContainsString('onclick', $out);
        $this->assertStringNotContainsString('onerror', $out);
        $this->assertStringNotContainsString('javascript', $out);
        $this->assertStringNotContainsString('<a', $out);
        $this->assertStringNotContainsString('position', $out);
        $this->assertStringNotContainsString('background', $out);
        $this->assertStringNotContainsString('font-size', $out);
        $this->assertStringContainsString('link', $out);
    }

    public function testDropsDataVarAndEmptyInput(): void
    {
        $this->assertSame('<span>x</span>', RichText::sanitize('<span data-var="name">x</span>'));
        $this->assertSame('', RichText::sanitize('   '));
        $this->assertSame('', RichText::plainText('<p> <br> </p>'));
    }

    public function testBackgroundDimensions(): void
    {
        $this->assertNull(CertificateBackground::dimensionError(1920, 1440));
        $this->assertNull(CertificateBackground::dimensionError(1440, 1080));
        $this->assertNotNull(CertificateBackground::dimensionError(1920, 1080));
        $this->assertNotNull(CertificateBackground::dimensionError(800, 600));
    }
}

<?php

namespace Tests\Domain;

use Helpers\AthletePhoto;
use PHPUnit\Framework\TestCase;

final class AthletePhotoTest extends TestCase
{
    private array $tmp = [];

    protected function tearDown(): void
    {
        array_map('unlink', $this->tmp);
    }

    private function file(string $contents, int $error = UPLOAD_ERR_OK, ?int $size = null): array
    {
        $path = tempnam(sys_get_temp_dir(), 'ph');
        file_put_contents($path, $contents);
        $this->tmp[] = $path;

        return ['name' => 'f', 'tmp_name' => $path, 'error' => $error, 'size' => $size ?? strlen($contents)];
    }

    public function testNoFileIsNotAnError(): void
    {
        $this->assertNull(AthletePhoto::validate(null));
        $this->assertNull(AthletePhoto::validate(['error' => UPLOAD_ERR_NO_FILE, 'size' => 0]));
        $this->assertFalse(AthletePhoto::hasUpload(['error' => UPLOAD_ERR_NO_FILE]));
    }

    public function testRejectsFilesOverTwoMegabytes(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

        $this->assertNull(AthletePhoto::validate($this->file($png)));
        $this->assertSame('A foto deve ter no máximo 2 MB.', AthletePhoto::validate($this->file($png, UPLOAD_ERR_OK, AthletePhoto::MAX_BYTES + 1)));
        $this->assertSame('A foto deve ter no máximo 2 MB.', AthletePhoto::validate($this->file($png, UPLOAD_ERR_INI_SIZE, 0)));
    }

    public function testRejectsNonImages(): void
    {
        $this->assertSame('A foto precisa ser um arquivo JPG, PNG ou WebP.', AthletePhoto::validate($this->file('<?php echo 1;')));
        $this->assertSame('A foto precisa ser um arquivo JPG, PNG ou WebP.', AthletePhoto::validate($this->file('GIF89a' . str_repeat("\0", 20))));
    }
}

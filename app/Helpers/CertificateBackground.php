<?php

namespace Helpers;

/**
 * Arte de fundo do certificado, uma por estilo. Nome fixo (style-{id}.png|jpg): subir uma arte
 * nova substitui a anterior, sem acumular arquivos.
 */
class CertificateBackground
{
    public const MAX_BYTES   = 8 * 1024 * 1024;
    public const MIN_WIDTH   = 1200;
    private const MIME_EXT   = ['image/png' => 'png', 'image/jpeg' => 'jpg'];

    public static function directory(): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/certificates';
    }

    public static function url(?string $filename): ?string
    {
        if (!$filename) {
            return null;
        }

        $path = self::directory() . '/' . $filename;

        return '/uploads/certificates/' . rawurlencode($filename) . '?v=' . (is_file($path) ? filemtime($path) : 0);
    }

    /** Proporção 4:3 (com 1,5% de folga) e largura mínima para não imprimir pixelado. */
    public static function dimensionError(int $width, int $height): ?string
    {
        if ($width < self::MIN_WIDTH) {
            return 'A arte do certificado precisa ter pelo menos ' . self::MIN_WIDTH . 'px de largura.';
        }

        if ($height <= 0 || abs($width / $height - 4 / 3) > (4 / 3) * 0.015) {
            return 'A arte do certificado precisa ter proporção 4:3 (ex.: 1920×1440 ou 1440×1080).';
        }

        return null;
    }

    /**
     * Valida e grava a arte do estilo. Devolve [nome do arquivo, erro]; com erro nada é gravado.
     *
     * @return array{0: ?string, 1: ?string}
     */
    public static function store(array $file, int $styleId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [null, in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? 'A arte do certificado é grande demais (máximo 8 MB).'
                : 'Não foi possível receber o arquivo da arte do certificado.'];
        }

        if ($file['size'] > self::MAX_BYTES) {
            return [null, 'A arte do certificado é grande demais (máximo 8 MB).'];
        }

        $info = @getimagesize($file['tmp_name']);
        $ext  = $info ? (self::MIME_EXT[$info['mime']] ?? null) : null;
        if ($ext === null) {
            return [null, 'A arte do certificado precisa ser um arquivo PNG ou JPG.'];
        }

        $error = self::dimensionError((int) $info[0], (int) $info[1]);
        if ($error !== null) {
            return [null, $error];
        }

        $dir = self::directory();
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return [null, 'Não foi possível criar a pasta de certificados no servidor.'];
        }

        $filename = "style-$styleId.$ext";
        if (!move_uploaded_file($file['tmp_name'], "$dir/$filename")) {
            return [null, 'Não foi possível salvar a arte do certificado.'];
        }

        // Troca de PNG por JPG (ou o contrário): remove o arquivo da extensão antiga
        foreach (self::MIME_EXT as $other) {
            if ($other !== $ext) {
                ImageProcessor::delete("$dir/style-$styleId.$other");
            }
        }

        return [$filename, null];
    }

    public static function delete(?string $filename): void
    {
        if ($filename) {
            ImageProcessor::delete(self::directory() . '/' . basename($filename));
        }
    }
}

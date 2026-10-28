<?php

namespace Helpers;

/** Foto do atleta: até 2 MB na entrada, no máximo 400 px de largura depois de processada. */
class AthletePhoto
{
    public const MAX_BYTES = 2 * 1024 * 1024;
    public const MAX_WIDTH = 400;
    private const MIMES    = ['image/jpeg', 'image/png', 'image/webp'];

    public static function directory(): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/athletes';
    }

    public static function url(?string $filename): ?string
    {
        return $filename ? '/uploads/athletes/' . rawurlencode($filename) : null;
    }

    public static function hasUpload(?array $file): bool
    {
        return $file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /** Mensagem de erro, ou null quando não há arquivo ou ele está dentro das regras. */
    public static function validate(?array $file): ?string
    {
        if (!self::hasUpload($file)) {
            return null;
        }

        if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $file['size'] > self::MAX_BYTES) {
            return 'A foto deve ter no máximo 2 MB.';
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Não foi possível receber a foto. Tente novamente.';
        }

        $info = @getimagesize($file['tmp_name']);
        if (!$info || !in_array($info['mime'], self::MIMES, true)) {
            return 'A foto precisa ser um arquivo JPG, PNG ou WebP.';
        }

        return null;
    }

    /** Reduz para 400 px de largura (nunca amplia) e grava. Devolve o nome do arquivo ou null. */
    public static function store(array $file): ?string
    {
        $filename = ImageProcessor::process($file, self::directory(), self::MAX_WIDTH, 80);

        return $filename === false ? null : $filename;
    }

    public static function delete(?string $filename): void
    {
        if ($filename) {
            ImageProcessor::delete(self::directory() . '/' . basename($filename));
        }
    }
}

<?php

namespace Helpers;

class ImageProcessor
{
    /**
     * Process an uploaded image file: resize and save as WebP.
     * Supports JPEG, PNG, WebP sources via GD; HEIC via Imagick (if available).
     *
     * @param array  $uploadedFile  Entry from $_FILES (single file)
     * @param string $targetDir     Absolute directory to save the processed image
     * @param int    $maxWidth      Max width in pixels (0 = use IMG_MAX_WIDTH)
     * @param int    $quality       WebP quality 0-100 (0 = use IMG_QUALITY)
     * @return string|false Filename (without path) on success, false on failure
     */
    public static function process(
        array $uploadedFile,
        string $targetDir,
        int $maxWidth = 0,
        int $quality = 0
    ): string|false {
        if (!isset($uploadedFile['tmp_name']) || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
            error_log('Image upload error: ' . ($uploadedFile['error'] ?? 'unknown'));
            return false;
        }

        $ext = strtolower(pathinfo($uploadedFile['name'] ?? '', PATHINFO_EXTENSION));
        $tmpPath = $uploadedFile['tmp_name'];

        if ($ext === 'heic') {
            return self::processHeic($tmpPath, $targetDir, $maxWidth, $quality);
        }

        $imageInfo = getimagesize($tmpPath);
        if ($imageInfo === false) {
            error_log('Invalid image file');
            return false;
        }

        $sourceImage = match ($imageInfo['mime']) {
            'image/jpeg' => imagecreatefromjpeg($tmpPath),
            'image/png'  => imagecreatefrompng($tmpPath),
            'image/webp' => imagecreatefromwebp($tmpPath),
            default      => false,
        };

        if ($sourceImage === false) {
            error_log('Unsupported or unreadable image format: ' . $imageInfo['mime']);
            return false;
        }

        return self::resizeAndSave($sourceImage, $targetDir, $maxWidth, $quality);
    }

    /**
     * Process a local image file path (e.g. extracted from ZIP): resize and save as WebP.
     * Supports JPEG, PNG, WebP, HEIC sources.
     */
    public static function processFromPath(
        string $sourcePath,
        string $targetDir,
        int $maxWidth = 0,
        int $quality = 0
    ): string|false {
        if (!file_exists($sourcePath) || !is_readable($sourcePath)) {
            return false;
        }

        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        if ($ext === 'heic') {
            return self::processHeic($sourcePath, $targetDir, $maxWidth, $quality);
        }

        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            error_log("ImageProcessor: getimagesize failed for $sourcePath");
            return false;
        }

        $sourceImage = match ($imageInfo['mime']) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png'  => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default      => false,
        };

        if ($sourceImage === false) {
            error_log("ImageProcessor: unsupported or unreadable mime={$imageInfo['mime']} file=$sourcePath");
            return false;
        }

        return self::resizeAndSave($sourceImage, $targetDir, $maxWidth, $quality);
    }

    public static function delete(string $filePath): bool
    {
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    // ── private helpers ───────────────────────────────────────────────────────

    /**
     * Process a HEIC file using the Imagick extension (must be installed with libheif).
     */
    private static function processHeic(
        string $sourcePath,
        string $targetDir,
        int $maxWidth,
        int $quality
    ): string|false {
        if (!extension_loaded('imagick')) {
            error_log('HEIC upload requires the Imagick PHP extension with libheif support');
            return false;
        }

        try {
            $imagick = new \Imagick($sourcePath);
            $imagick->setImageFormat('webp');

            $maxWidth = $maxWidth > 0 ? $maxWidth : IMG_MAX_WIDTH;
            if ($imagick->getImageWidth() > $maxWidth) {
                $imagick->resizeImage($maxWidth, 0, \Imagick::FILTER_LANCZOS, 1);
            }

            $quality = $quality > 0 ? $quality : IMG_QUALITY;
            $imagick->setImageCompressionQuality($quality);

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $filename = self::generateRandomFilename();
            $imagick->writeImage(rtrim($targetDir, '/') . '/' . $filename);
            $imagick->destroy();

            return $filename;
        } catch (\Exception $e) {
            error_log('HEIC processing error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Resize a GD image resource and save it as WebP.
     * Destroys the source image resource when done.
     */
    private static function resizeAndSave(
        \GdImage $sourceImage,
        string $targetDir,
        int $maxWidth,
        int $quality
    ): string|false {
        $originalWidth  = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        $maxWidth = $maxWidth > 0 ? $maxWidth : IMG_MAX_WIDTH;
        if ($originalWidth > $maxWidth) {
            $ratio     = $maxWidth / $originalWidth;
            $newWidth  = $maxWidth;
            $newHeight = (int) round($originalHeight * $ratio);
        } else {
            $newWidth  = $originalWidth;
            $newHeight = $originalHeight;
        }

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($resizedImage, 255, 255, 255);
        imagefill($resizedImage, 0, 0, $white);

        imagecopyresampled(
            $resizedImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                error_log("ImageProcessor: failed to create directory $targetDir");
                imagedestroy($sourceImage);
                imagedestroy($resizedImage);
                return false;
            }
        }

        $filename = self::generateRandomFilename();
        $filePath = rtrim($targetDir, '/') . '/' . $filename;
        $quality  = $quality > 0 ? $quality : IMG_QUALITY;

        if (!function_exists('imagewebp')) {
            error_log("ImageProcessor: imagewebp() not available — GD built without WebP support");
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);
            return false;
        }

        $saved = imagewebp($resizedImage, $filePath, $quality);

        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        if (!$saved) {
            error_log("ImageProcessor: imagewebp() failed writing to $filePath");
        }

        return $saved ? $filename : false;
    }

    private static function generateRandomFilename(): string
    {
        return time() . '_' . bin2hex(random_bytes(16)) . '.webp';
    }
}

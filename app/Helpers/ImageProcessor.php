<?php

namespace Helpers;

class ImageProcessor
{
    /**
     * Process uploaded JPG image: resize and optimize.
     *
     * @param array  $uploadedFile  Entry from $_FILES
     * @param string $targetDir     Directory to save the processed image
     * @param int    $maxWidth      Max width in pixels (default: IMG_MAX_WIDTH from config)
     * @param int    $quality       JPEG quality 0-100 (default: IMG_QUALITY from config)
     * @return string|false Filename on success, false on failure
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

        $tmpPath = $uploadedFile['tmp_name'];

        $imageInfo = getimagesize($tmpPath);
        if ($imageInfo === false) {
            error_log('Invalid image file');
            return false;
        }

        // Only JPEG accepted
        if ($imageInfo['mime'] !== 'image/jpeg') {
            error_log('Rejected non-JPEG upload: ' . $imageInfo['mime']);
            return false;
        }

        $sourceImage = imagecreatefromjpeg($tmpPath);
        if ($sourceImage === false) {
            error_log('Failed to read JPEG');
            return false;
        }

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        $maxWidth = $maxWidth > 0 ? $maxWidth : IMG_MAX_WIDTH;
        if ($originalWidth > $maxWidth) {
            $ratio = $maxWidth / $originalWidth;
            $newWidth = $maxWidth;
            $newHeight = (int) round($originalHeight * $ratio);
        } else {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($resizedImage, 255, 255, 255);
        imagefill($resizedImage, 0, 0, $white);

        imagecopyresampled(
            $resizedImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        $filename = self::generateRandomFilename();

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filePath = rtrim($targetDir, '/') . '/' . $filename;
        $quality = $quality > 0 ? $quality : IMG_QUALITY;
        $saved = imagejpeg($resizedImage, $filePath, $quality);

        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        return $saved ? $filename : false;
    }

    /**
     * Process a local file path (e.g. extracted from ZIP): resize and optimize to JPEG.
     * Supports JPEG, PNG and WebP sources.
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

        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            return false;
        }

        $sourceImage = match ($imageInfo['mime']) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png' => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default => false,
        };

        if ($sourceImage === false) {
            return false;
        }

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        $maxWidth = $maxWidth > 0 ? $maxWidth : IMG_MAX_WIDTH;
        if ($originalWidth > $maxWidth) {
            $ratio = $maxWidth / $originalWidth;
            $newWidth = $maxWidth;
            $newHeight = (int) round($originalHeight * $ratio);
        } else {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($resizedImage, 255, 255, 255);
        imagefill($resizedImage, 0, 0, $white);

        imagecopyresampled(
            $resizedImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        $filename = self::generateRandomFilename();

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filePath = rtrim($targetDir, '/') . '/' . $filename;
        $quality = $quality > 0 ? $quality : IMG_QUALITY;
        $saved = imagejpeg($resizedImage, $filePath, $quality);

        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        return $saved ? $filename : false;
    }

    public static function delete(string $filePath): bool
    {
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    private static function generateRandomFilename(): string
    {
        return time() . '_' . bin2hex(random_bytes(16)) . '.jpg';
    }
}

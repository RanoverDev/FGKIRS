<?php

namespace Helpers;

/**
 * ImageProcessor Class
 * Handles image upload, conversion to JPG, resize, and optimization
 */
class ImageProcessor
{
    /**
     * Process uploaded image: convert to JPG, resize, optimize quality
     *
     * @param array $uploadedFile File from $_FILES array
     * @param string $targetDir Directory to save the processed image
     * @return string|false Filename of saved image or false on failure
     */
    public static function process(array $uploadedFile, string $targetDir): string|false
    {
        // Validate upload
        if (!isset($uploadedFile['tmp_name']) || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
            error_log('Image upload error: ' . ($uploadedFile['error'] ?? 'Unknown error'));
            return false;
        }

        $tmpPath = $uploadedFile['tmp_name'];

        // Detect image type and create image resource
        $imageInfo = getimagesize($tmpPath);
        if ($imageInfo === false) {
            error_log('Invalid image file');
            return false;
        }

        $mimeType = $imageInfo['mime'];
        $sourceImage = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($tmpPath),
            'image/png' => imagecreatefrompng($tmpPath),
            'image/gif' => imagecreatefromgif($tmpPath),
            'image/webp' => imagecreatefromwebp($tmpPath),
            default => false,
        };

        if ($sourceImage === false) {
            error_log('Unsupported image type: ' . $mimeType);
            return false;
        }

        // Get original dimensions
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        // Calculate new dimensions (max width 1200px, maintain aspect ratio)
        $maxWidth = IMG_MAX_WIDTH; // From config.php
        if ($originalWidth > $maxWidth) {
            $ratio = $maxWidth / $originalWidth;
            $newWidth = $maxWidth;
            $newHeight = (int) round($originalHeight * $ratio);
        } else {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        // Create new image with calculated dimensions
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG/GIF (will be converted to white background in JPG)
        $white = imagecolorallocate($resizedImage, 255, 255, 255);
        imagefill($resizedImage, 0, 0, $white);

        // Resize image
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

        // Generate random filename
        $filename = self::generateRandomFilename();

        // Ensure target directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filePath = rtrim($targetDir, '/') . '/' . $filename;

        // Save as JPG with specified quality
        $quality = IMG_QUALITY; // From config.php (55)
        $saved = imagejpeg($resizedImage, $filePath, $quality);

        // Free memory
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        return $saved ? $filename : false;
    }

    /**
     * Generate random filename with .jpg extension
     *
     * @return string Random filename
     */
    private static function generateRandomFilename(): string
    {
        $randomString = bin2hex(random_bytes(16));
        $timestamp = time();
        return $timestamp . '_' . $randomString . '.jpg';
    }

    /**
     * Delete image file
     *
     * @param string $filePath Full path to the file
     * @return bool True if deleted, false otherwise
     */
    public static function delete(string $filePath): bool
    {
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }

        return false;
    }
}

<?php

namespace Helpers;

class ImageProcessor
{
    /** Extensões aceitas nos uploads (HEIC/HEIF = fotos do iPhone). */
    public const ACCEPTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'];

    private const HEIC_BRANDS = ['heic', 'heix', 'hevc', 'hevx', 'heim', 'heis', 'mif1', 'msf1', 'heif'];

    /**
     * Process an uploaded image file: resize and save as WebP.
     * Supports JPEG, PNG, WebP sources via GD; HEIC/HEIF (iPhone) via Imagick with libheif.
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

        return self::processFromPath($uploadedFile['tmp_name'], $targetDir, $maxWidth, $quality);
    }

    /**
     * Process a local image file path (upload tmp file or extracted from ZIP): resize and save as WebP.
     * The format is detected from the file content, never from the extension.
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

        $maxWidth = $maxWidth > 0 ? $maxWidth : IMG_MAX_WIDTH;

        if (self::isHeic($sourcePath)) {
            $sourceImage = self::loadHeic($sourcePath, $maxWidth);
        } else {
            $imageInfo = @getimagesize($sourcePath);
            if ($imageInfo === false) {
                error_log("ImageProcessor: invalid image file $sourcePath");
                return false;
            }

            $sourceImage = match ($imageInfo['mime']) {
                'image/jpeg' => self::applyExifOrientation(@imagecreatefromjpeg($sourcePath), $sourcePath),
                'image/png'  => @imagecreatefrompng($sourcePath),
                'image/webp' => @imagecreatefromwebp($sourcePath),
                default      => false,
            };

            if ($sourceImage === false) {
                error_log("ImageProcessor: unsupported or unreadable mime={$imageInfo['mime']} file=$sourcePath");
                return false;
            }
        }

        if ($sourceImage === false) {
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

    /**
     * True when the server can decode HEIC/HEIF (Imagick built with libheif).
     * When false, the admin forms convert HEIC in the browser before uploading.
     */
    public static function supportsHeic(): bool
    {
        if (!extension_loaded('imagick')) {
            return false;
        }

        try {
            return \Imagick::queryFormats('HEIC') !== [];
        } catch (\Throwable) {
            return false;
        }
    }

    // ── private helpers ───────────────────────────────────────────────────────

    private static function isHeic(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        $head = fread($handle, 12);
        fclose($handle);

        return $head !== false
            && strlen($head) === 12
            && substr($head, 4, 4) === 'ftyp'
            && \in_array(strtolower(substr($head, 8, 4)), self::HEIC_BRANDS, true);
    }

    /**
     * Decode HEIC via Imagick into a GD image (JPEG in memory), already rotated
     * and shrunk to $maxWidth, so the rest of the pipeline is identical to other formats.
     */
    private static function loadHeic(string $path, int $maxWidth): \GdImage|false
    {
        if (!self::supportsHeic()) {
            error_log('HEIC upload requires the Imagick PHP extension with libheif support');
            return false;
        }

        try {
            $imagick = new \Imagick($path);
            $imagick->setIteratorIndex(0);
            self::orientImagick($imagick);

            if ($imagick->getImageWidth() > $maxWidth) {
                $imagick->resizeImage($maxWidth, 0, \Imagick::FILTER_LANCZOS, 1);
            }

            $imagick->setImageBackgroundColor('white');
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(92);
            $blob = $imagick->getImageBlob();
            $imagick->clear();

            return imagecreatefromstring($blob);
        } catch (\Throwable $e) {
            error_log('HEIC processing error: ' . $e->getMessage());
            return false;
        }
    }

    private static function orientImagick(\Imagick $imagick): void
    {
        if (method_exists($imagick, 'autoOrient')) {
            $imagick->autoOrient();
            return;
        }

        $degrees = match ($imagick->getImageOrientation()) {
            \Imagick::ORIENTATION_BOTTOMRIGHT => 180,
            \Imagick::ORIENTATION_RIGHTTOP    => 90,
            \Imagick::ORIENTATION_LEFTBOTTOM  => 270,
            default                           => 0,
        };
        if ($degrees !== 0) {
            $imagick->rotateImage('white', $degrees);
            $imagick->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
        }
    }

    /**
     * Phone JPEGs store rotation in EXIF instead of in the pixels; GD ignores it,
     * so photos taken in portrait would come out sideways.
     */
    private static function applyExifOrientation(\GdImage|false $image, string $path): \GdImage|false
    {
        if ($image === false || !function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $degrees = match ((int) ($exif['Orientation'] ?? 1)) {
            3       => 180,
            6       => -90,
            8       => 90,
            default => 0,
        };

        if ($degrees === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $degrees, 0);
        return $rotated === false ? $image : $rotated;
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

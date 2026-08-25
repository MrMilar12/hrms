<?php
// Centralized file upload handling: validation, EXIF stripping, resize + thumbnail generation.

class Uploader
{
    /**
     * Handles a single uploaded image file.
     *
     * @param array $file A single entry from $_FILES (already checked for UPLOAD_ERR_OK by caller).
     * @param string $destinationDir Absolute directory to store the file in (created if missing).
     * @return array{file_path: string, thumbnail_path: string, file_type: string, file_size: int}
     * @throws RuntimeException on validation failure.
     */
    public static function handleImage(array $file, string $destinationDir): array
    {
        $requiredGdFunctions = ['imagecreatetruecolor', 'imagecopyresampled', 'imagejpeg', 'imagepng'];
        foreach ($requiredGdFunctions as $function) {
            if (!function_exists($function)) {
                throw new RuntimeException('Image processing is unavailable because the PHP GD extension is not enabled. Enable extension=gd in XAMPP\\php\\php.ini and restart Apache.');
            }
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed with error code ' . $file['error']);
        }

        if ($file['size'] > MAX_UPLOAD_SIZE) {
            throw new RuntimeException('File exceeds the maximum allowed size of ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB.');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_IMAGE_EXTENSIONS, true)) {
            throw new RuntimeException('File extension not allowed.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, ALLOWED_IMAGE_MIME_TYPES, true)) {
            throw new RuntimeException('File content does not match an allowed image type.');
        }

        $dimensions = @getimagesize($file['tmp_name']);
        if (!$dimensions || empty($dimensions[0]) || empty($dimensions[1])) {
            throw new RuntimeException('The uploaded file is not a valid image.');
        }
        $width = (int) $dimensions[0];
        $height = (int) $dimensions[1];
        if ($width > intdiv(IMAGE_MAX_PIXELS, $height)) {
            throw new RuntimeException('Image dimensions are too large.');
        }

        $decoder = match ($mimeType) {
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/webp' => 'imagecreatefromwebp',
        };
        $encoder = match ($mimeType) {
            'image/jpeg' => 'imagejpeg',
            'image/png' => 'imagepng',
            'image/webp' => 'imagewebp',
        };
        if (!function_exists($decoder) || !function_exists($encoder)) {
            throw new RuntimeException('This PHP GD installation does not support ' . strtoupper($extension) . ' images. Please upload JPEG or PNG instead.');
        }

        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $safeName = bin2hex(random_bytes(16)) . '.' . $extension;
        $destPath = rtrim($destinationDir, '/\\') . DIRECTORY_SEPARATOR . $safeName;
        $thumbName = 'thumb_' . $safeName;
        $thumbPath = rtrim($destinationDir, '/\\') . DIRECTORY_SEPARATOR . $thumbName;

        $image = self::createImageFromFile($file['tmp_name'], $mimeType);

        // Resize main image (strips EXIF/GPS as a side effect of re-encoding via GD).
        self::resizeAndSave($image, $destPath, $mimeType, IMAGE_MAX_DIMENSION);
        self::resizeAndSave($image, $thumbPath, $mimeType, IMAGE_THUMB_DIMENSION);

        imagedestroy($image);

        return [
            'file_path' => $destPath,
            'thumbnail_path' => $thumbPath,
            'file_type' => $mimeType,
            'file_size' => $file['size'],
        ];
    }

    private static function createImageFromFile(string $path, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            default => throw new RuntimeException('Unsupported image type.'),
        };
    }

    private static function resizeAndSave($image, string $destPath, string $mimeType, int $maxDimension): void
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $ratio = min(1, $maxDimension / max($width, $height));
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        if ($mimeType === 'image/png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        match ($mimeType) {
            'image/jpeg' => imagejpeg($resized, $destPath, 85),
            'image/png' => imagepng($resized, $destPath, 6),
            'image/webp' => imagewebp($resized, $destPath, 85),
            default => throw new RuntimeException('Unsupported image type.'),
        };

        imagedestroy($resized);
    }
}

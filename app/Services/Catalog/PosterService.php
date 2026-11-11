<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PosterService
{
    public const MAX_WIDTH = 600;

    public const MAX_SIZE_BYTES = 307200; // 300 KB

    public const MAX_INPUT_BYTES = 2097152; // 2 MB

    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Validate content MIME, decode with GD, resize to max 600px width,
     * re-encode to WebP <= 300KB, store with random name on public disk,
     * discard original.
     *
     * @throws ValidationException
     */
    public function processAndStore(UploadedFile $file): string
    {
        if ($file->getSize() > self::MAX_INPUT_BYTES) {
            throw ValidationException::withMessages([
                'poster' => 'The poster file size must not exceed 2 MB.',
            ]);
        }

        $realPath = $file->getRealPath();
        if (! $realPath || ! file_exists($realPath)) {
            throw ValidationException::withMessages([
                'poster' => 'The uploaded file is not accessible.',
            ]);
        }

        // Validate MIME type by content (not extension)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($realPath);

        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'poster' => 'The poster must be an image of type JPEG, PNG, or WebP.',
            ]);
        }

        // Decode with GD
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($realPath),
            'image/png' => @imagecreatefrompng($realPath),
            default => @imagecreatefromwebp($realPath), // 'image/webp' — the only remaining allowed MIME
        };

        if ($image === false) {
            throw ValidationException::withMessages([
                'poster' => 'The poster image could not be processed or is corrupted.',
            ]);
        }

        // Proportional resize to max 600 px wide
        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        if ($origWidth > self::MAX_WIDTH) {
            $newWidth = self::MAX_WIDTH;
            $newHeight = (int) max(1, round(($origHeight / $origWidth) * self::MAX_WIDTH));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagedestroy($image);
            $image = $resized;
        }

        // Re-encode to WebP max 300 KB
        $quality = 85;
        $webpData = null;

        do {
            ob_start();
            imagewebp($image, null, $quality);
            $webpData = (string) ob_get_clean();
            $quality -= 10;
        } while (strlen($webpData) > self::MAX_SIZE_BYTES && $quality >= 20);

        imagedestroy($image);

        if (strlen($webpData) > self::MAX_SIZE_BYTES) {
            throw ValidationException::withMessages([
                'poster' => 'The poster image could not be compressed below 300 KB.',
            ]);
        }

        // Random filename, stored in storage/app/public/posters/<random>.webp
        $filename = Str::random(40).'.webp';
        $path = "posters/{$filename}";

        Storage::disk('public')->put($path, $webpData);

        return "local:{$path}";
    }

    /**
     * Delete existing local poster file if applicable.
     */
    public function deleteIfLocal(?string $posterPath): void
    {
        if ($posterPath !== null && str_starts_with($posterPath, 'local:')) {
            $relativePath = substr($posterPath, 6);
            Storage::disk('public')->delete($relativePath);
        }
    }
}

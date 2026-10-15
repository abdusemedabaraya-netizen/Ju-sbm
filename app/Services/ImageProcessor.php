<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

/**
 * Central GD-based image pre-processing, shared by the profile photo upload
 * and the evidence upload.
 *
 * Both entry points need the same guarantees: the GD extension is present, the
 * file really is an image and not an "image bomb", and the EXIF orientation tag
 * is applied BEFORE re-encoding so the pixels are never rotated twice.
 */
class ImageProcessor
{
    /** Refuse absurdly large bitmaps (decompression-bomb protection). */
    private const MAX_PIXELS = 25_000_000;

    /**
     * Re-encode the picture in its own format, dropping every metadata block
     * (EXIF/GPS/camera/date) and any hidden payload. PNG keeps transparency.
     *
     * @param  string  $errorKey  validation bag the failure is reported on
     */
    public function stripMetadata(string $path, string $errorKey = 'files'): string
    {
        [$img, $type] = $this->load($path, $errorKey);

        ob_start();
        if ($type === IMAGETYPE_PNG) {
            imagesavealpha($img, true);
            imagepng($img);
        } else {
            imagejpeg($img, null, 90);
        }
        $data = ob_get_clean();
        imagedestroy($img);

        return $data;
    }

    /**
     * Centre-square crop, resize to $size and re-encode as a JPEG. Used for
     * avatars, where a fixed square and no metadata are both required.
     */
    public function squareJpeg(string $path, int $size = 256, string $errorKey = 'photo', int $quality = 85): string
    {
        $src = $this->load($path, $errorKey)[0];

        $w    = imagesx($src);
        $h    = imagesy($src);
        $side = min($w, $h);
        $x    = intdiv($w - $side, 2);
        $y    = intdiv($h - $side, 2);

        $dst = imagecreatetruecolor($size, $size);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // white behind transparent PNGs
        imagecopyresampled($dst, $src, 0, 0, $x, $y, $size, $size, $side, $side);

        ob_start();
        imagejpeg($dst, null, $quality);
        $data = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $data;
    }

    /**
     * Validate and decode an image file, applying the EXIF orientation.
     *
     * @return array{0: \GdImage, 1: int}  the bitmap and its IMAGETYPE_* constant
     */
    private function load(string $path, string $errorKey): array
    {
        if (! extension_loaded('gd')) {
            throw ValidationException::withMessages([
                $errorKey => 'Image processing is unavailable on the server (enable the PHP GD extension).',
            ]);
        }

        $info = @getimagesize($path);
        if (! $info || ($info[0] * $info[1]) > self::MAX_PIXELS) {
            throw ValidationException::withMessages([
                $errorKey => 'That image is not valid or is too large.',
            ]);
        }

        $img = match ($info[2]) {
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            default        => false,
        };

        if (! $img) {
            throw ValidationException::withMessages([
                $errorKey => 'That image could not be read.',
            ]);
        }

        return [$this->fixOrientation($img, $path, $info[2]), $info[2]];
    }

    /**
     * Rotate the bitmap according to the EXIF orientation tag so the picture is
     * upright before the tag is discarded by the re-encode.
     */
    private function fixOrientation(\GdImage $img, string $path, int $type): \GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $img;
        }

        $orientation = @exif_read_data($path)['Orientation'] ?? 1;
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        return $angle ? (imagerotate($img, $angle, 0) ?: $img) : $img;
    }
}

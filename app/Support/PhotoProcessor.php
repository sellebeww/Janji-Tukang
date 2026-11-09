<?php

namespace App\Support;

use GdImage;
use Illuminate\Validation\ValidationException;

/**
 * I6: foto mentah -> JPEG baru. Karena dikodekan ulang lewat GD, seluruh metadata (EXIF, GPS, komentar) tidak ikut.
 * Orientasi dari EXIF diterapkan ke piksel dulu supaya foto kamera ponsel tidak miring.
 */
class PhotoProcessor
{
    public const MAX_SIDE = 1600;

    // shortcut: batas 30 MP agar GD tidak melampaui memory_limit 128M; naikkan bila memory_limit dinaikkan.
    public const MAX_PIXELS = 30_000_000;

    /** @return array{bytes: string, width: int, height: int} */
    public static function normalize(string $bytes): array
    {
        $info = @getimagesizefromstring($bytes);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw self::invalid('File bukan foto yang bisa dibaca. Gunakan JPEG, PNG, atau WebP (bukan HEIC).');
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw self::invalid('Resolusi foto terlalu besar. Kecilkan dulu resolusi kamera atau fotonya.');
        }

        $img = @imagecreatefromstring($bytes) ?: throw self::invalid('Foto rusak dan tidak bisa dibaca.');
        if ($info[2] === IMAGETYPE_JPEG) {
            $img = self::orient($img, $bytes);
        }

        [$w, $h] = [imagesx($img), imagesy($img)];
        $ratio = min(1, self::MAX_SIDE / max($w, $h));
        [$nw, $nh] = [max(1, (int) round($w * $ratio)), max(1, (int) round($h * $ratio))];

        // Kanvas putih baru: meratakan transparansi PNG/WebP dan memastikan tak ada sisa dari sumber.
        $out = imagecreatetruecolor($nw, $nh);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagejpeg($out, null, 80);

        return ['bytes' => ob_get_clean(), 'width' => $nw, 'height' => $nh];
    }

    // shortcut: hanya rotasi (3, 6, 8); orientasi cermin (2, 4, 5, 7) jarang dan diabaikan.
    private static function orient(GdImage $img, string $bytes): GdImage
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $bytes);
        rewind($stream);
        $exif = function_exists('exif_read_data') ? @exif_read_data($stream) : false;
        fclose($stream);

        $deg = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 0] ?? 0;

        return $deg ? (imagerotate($img, $deg, 0) ?: $img) : $img;
    }

    private static function invalid(string $msg): ValidationException
    {
        return ValidationException::withMessages(['foto' => $msg]);
    }
}

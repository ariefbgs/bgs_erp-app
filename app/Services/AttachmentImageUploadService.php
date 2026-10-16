<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class AttachmentImageUploadService
{
    public function store(UploadedFile $file, string $directory, int $maxDimension = 2400): string
    {
        $mime = $file->getMimeType();
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            if (str_starts_with((string) $mime, 'image/')) {
                throw ValidationException::withMessages(['attachment' => 'Format gambar tidak didukung untuk kompresi.']);
            }
            $path = $file->store($directory, 'public');
            if (!$path) { throw new \RuntimeException('Attachment tidak dapat disimpan.'); }
            return $path;
        }
        if (!extension_loaded('gd') || !function_exists('imagewebp')) {
            throw ValidationException::withMessages(['attachment' => 'PHP GD dengan WebP wajib aktif untuk kompresi gambar.']);
        }
        $info = @getimagesize($file->getPathname());
        if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 20000000) {
            throw ValidationException::withMessages(['attachment' => 'Gambar tidak valid atau melebihi 20 megapixel.']);
        }
        $decoder = match ($info[2]) {
            IMAGETYPE_JPEG => 'imagecreatefromjpeg',
            IMAGETYPE_PNG => 'imagecreatefrompng',
            IMAGETYPE_GIF => 'imagecreatefromgif',
            IMAGETYPE_WEBP => 'imagecreatefromwebp',
            default => null,
        };
        $image = null;
        $output = null;
        $temporary = null;
        try {
            if (!$decoder || !function_exists($decoder) || !($image = @$decoder($file->getPathname()))) {
                throw new \RuntimeException('Gambar tidak dapat dibaca.');
            }
            if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                $exif = @exif_read_data($file->getPathname());
                $orientation = (int) ($exif['Orientation'] ?? 1);
                if (in_array($orientation, [2, 4, 5, 7], true)) {
                    imageflip($image, IMG_FLIP_HORIZONTAL);
                }
                $angle = match ($orientation) { 3, 4 => 180, 5, 6 => -90, 7, 8 => 90, default => 0 };
                if ($angle) {
                    $rotated = imagerotate($image, $angle, 0);
                    if (!$rotated) { throw new \RuntimeException('Orientasi gambar gagal diproses.'); }
                    imagedestroy($image);
                    $image = $rotated;
                }
            }
            $width = imagesx($image);
            $height = imagesy($image);
            $scale = min(1, $maxDimension / max($width, $height));
            $output = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
            imagealphablending($output, false);
            imagesavealpha($output, true);
            imagefill($output, 0, 0, imagecolorallocatealpha($output, 0, 0, 0, 127));
            if (!imagecopyresampled($output, $image, 0, 0, 0, 0, imagesx($output), imagesy($output), $width, $height)) {
                throw new \RuntimeException('Resize gambar gagal.');
            }
            $absolute = \Illuminate\Support\Facades\Storage::disk('public')->path($directory);
            if (!is_dir($absolute) && !@mkdir($absolute, 0755, true) && !is_dir($absolute)) {
                throw new \RuntimeException('Folder upload tidak dapat dibuat: ' . $absolute);
            }
            if (!is_writable($absolute)) {
                throw new \RuntimeException('Folder upload tidak dapat ditulis: ' . $absolute);
            }
            $filename = bin2hex(random_bytes(16)) . '.webp';
            $temporary = tempnam($absolute, '.image-');
            if (!$temporary || !imagewebp($output, $temporary, 82) || filesize($temporary) === 0) {
                throw new \RuntimeException('Kompresi WebP gagal.');
            }
            if (!rename($temporary, $absolute . DIRECTORY_SEPARATOR . $filename)) {
                throw new \RuntimeException('Gambar hasil kompresi tidak dapat disimpan.');
            }
            $temporary = null;
            return trim($directory, '/') . '/' . $filename;
        } finally {
            if ($image) { imagedestroy($image); }
            if ($output) { imagedestroy($output); }
            if ($temporary && is_file($temporary)) { @unlink($temporary); }
        }
    }
}

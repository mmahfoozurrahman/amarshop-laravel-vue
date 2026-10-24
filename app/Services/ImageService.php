<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Handles the three kinds of images used by the store:
 *  - real admin uploads (public disk, path stored as "storage/...")
 *  - generated SVG placeholders for demo/seed data (public/images/**)
 *  - demo photos for the seeded catalogue: cropped into public/images/** by
 *    DemoImageSeeder, optionally (re)generated from the original photos in
 *    storage/app/seed-images/**
 */
class ImageService
{
    /**
     * Optional folder holding the original demo photos. It sits outside the web
     * root because the files are only read while seeding; when it is missing the
     * JPEGs already generated in public/images are reused as they are.
     */
    public const DEMO_SOURCE_DIR = 'seed-images';

    public const PALETTES = [
        'teal' => ['#0f766e', '#14b8a6'],
        'navy' => ['#1e3a8a', '#3b82f6'],
        'amber' => ['#b45309', '#f59e0b'],
        'rose' => ['#9f1239', '#fb7185'],
        'violet' => ['#5b21b6', '#a78bfa'],
        'emerald' => ['#065f46', '#34d399'],
        'slate' => ['#1e293b', '#64748b'],
        'cyan' => ['#155e75', '#22d3ee'],
    ];

    /** @var array<int, string>|null */
    private static ?array $demoSourceCache = null;

    public function storeUpload(UploadedFile $file, string $folder): string
    {
        $path = $file->store($folder, 'public');

        return 'storage/'.$path;
    }

    public function deleteIfManaged(?string $path): void
    {
        if ($path && Str::startsWith($path, 'storage/')) {
            Storage::disk('public')->delete(Str::after($path, 'storage/'));
        }
    }

    /**
     * Remove a file this service generated inside public/ — used when the SVG
     * art of a demo record is replaced by a demo photo.
     */
    public function deleteGenerated(?string $path): void
    {
        if ($path && Str::startsWith($path, 'images/')) {
            $absolute = public_path($path);

            if (is_file($absolute)) {
                unlink($absolute);
            }
        }
    }

    /**
     * Product/category art with the item initials.
     */
    public function placeholder(
        string $relativePath,
        string $label,
        string $palette = 'teal',
        int $width = 640,
        int $height = 640,
    ): string {
        [$start, $end] = self::PALETTES[$palette] ?? self::PALETTES['teal'];
        $initials = $this->initials($label);
        $labelEsc = e(Str::limit($label, 34));
        $gradientId = 'g'.substr(md5($relativePath), 0, 8);
        $fontSize = (int) ($width * 0.26);
        $labelSize = (int) ($width * 0.045);
        $circleOne = (int) ($width * 0.42);
        $circleTwo = (int) ($width * 0.24);

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}" role="img" aria-label="{$labelEsc}">
    <defs>
        <linearGradient id="{$gradientId}" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="{$start}"/>
            <stop offset="100%" stop-color="{$end}"/>
        </linearGradient>
    </defs>
    <rect width="{$width}" height="{$height}" fill="url(#{$gradientId})"/>
    <circle cx="{$width}" cy="0" r="{$circleOne}" fill="#ffffff" opacity="0.08"/>
    <circle cx="0" cy="{$height}" r="{$circleTwo}" fill="#ffffff" opacity="0.12"/>
    <text x="50%" y="48%" text-anchor="middle" dominant-baseline="middle" font-family="Segoe UI, Arial, sans-serif"
          font-size="{$fontSize}" font-weight="700" fill="#ffffff" opacity="0.9">{$initials}</text>
    <text x="50%" y="76%" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif"
          font-size="{$labelSize}" font-weight="600" fill="#ffffff" opacity="0.85">{$labelEsc}</text>
    <text x="50%" y="92%" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif"
          font-size="{$labelSize}" fill="#ffffff" opacity="0.55">AmarShop</text>
</svg>
SVG;

        return $this->write($relativePath, $svg);
    }

    /**
     * Decorative wide banner art for the home slider (1600 × 600, 8:3).
     *
     * The slide title/subtitle/button are rendered by the Vue overlay, so no
     * text is baked into the artwork — uploaded photos behave the same way.
     */
    public function hero(string $relativePath, string $palette = 'teal'): string
    {
        [$start, $end] = self::PALETTES[$palette] ?? self::PALETTES['teal'];
        $gradientId = 'g'.substr(md5($relativePath), 0, 8);

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="600" viewBox="0 0 1600 600" role="img" aria-label="Promotional banner">
    <defs>
        <linearGradient id="{$gradientId}" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="{$start}"/>
            <stop offset="100%" stop-color="{$end}"/>
        </linearGradient>
    </defs>
    <rect width="1600" height="600" fill="url(#{$gradientId})"/>
    <circle cx="1380" cy="120" r="260" fill="#ffffff" opacity="0.10"/>
    <circle cx="1500" cy="470" r="180" fill="#ffffff" opacity="0.08"/>
    <circle cx="1180" cy="330" r="90" fill="#ffffff" opacity="0.12"/>
    <circle cx="820" cy="80" r="140" fill="#ffffff" opacity="0.05"/>
</svg>
SVG;

        return $this->write($relativePath, $svg);
    }

    /**
     * Photo for a seeded demo record: a bundled photo is cropped to the target
     * size and written as a compressed JPEG under public/images/**.
     *
     * Returns null when the photo is missing or unreadable, which lets the caller
     * keep using the generated SVG art instead.
     */
    public function demoPhotoFrom(string $relativePath, string $sourceName, int $width, int $height, int $quality = 82): ?string
    {
        $source = $this->demoSourcePath($sourceName);

        if ($source === null) {
            return null;
        }

        $image = $this->readImage($source);

        if ($image === null) {
            return null;
        }

        $canvas = $this->coverCrop($image, $width, $height);

        imagedestroy($image);

        $absolute = public_path($relativePath);
        $directory = dirname($absolute);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        imagejpeg($canvas, $absolute, $quality);

        imagedestroy($canvas);

        return $relativePath;
    }

    /**
     * Curated list of the bundled demo photos: files that are too small for the
     * target sizes and duplicates — the same content under another name, or a
     * "Copy of …" — are skipped, and the list is sorted, so the assignment is
     * stable across runs and machines.
     *
     * @return array<int, string>
     */
    public function demoSources(): array
    {
        if (self::$demoSourceCache !== null) {
            return self::$demoSourceCache;
        }

        $directory = storage_path('app/'.self::DEMO_SOURCE_DIR);
        $files = is_dir($directory) ? (scandir($directory) ?: []) : [];
        $sources = [];
        $seen = [];

        foreach ($files as $file) {
            if (str_starts_with($file, 'Copy of') || str_starts_with($file, '.')) {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$file;

            if (! is_file($path) || ! $this->isUsableSource($path)) {
                continue;
            }

            $hash = md5_file($path);

            if ($hash === false || isset($seen[$hash])) {
                continue;
            }

            $seen[$hash] = true;
            $sources[] = $file;
        }

        sort($sources, SORT_STRING);

        return self::$demoSourceCache = $sources;
    }

    public function demoSourcePath(string $sourceName): ?string
    {
        $path = storage_path('app/'.self::DEMO_SOURCE_DIR.DIRECTORY_SEPARATOR.$sourceName);

        return is_file($path) ? $path : null;
    }

    private function write(string $relativePath, string $contents): string
    {
        $absolute = public_path($relativePath);
        $directory = dirname($absolute);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($absolute, $contents);

        return $relativePath;
    }

    private function isUsableSource(string $absolute): bool
    {
        $info = @getimagesize($absolute);

        if ($info === false) {
            return false;
        }

        // The smallest target is the 360 × 200 brand tile, so anything below
        // 400 × 300 would only be upscaled.
        return $info[0] >= 400 && $info[1] >= 300;
    }

    private function readImage(string $absolute): ?GdImage
    {
        $info = @getimagesize($absolute);

        if ($info === false) {
            return null;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absolute),
            IMAGETYPE_PNG => @imagecreatefrompng($absolute),
            IMAGETYPE_GIF => @imagecreatefromgif($absolute),
            IMAGETYPE_WEBP => @imagecreatefromwebp($absolute),
            IMAGETYPE_BMP => @imagecreatefrombmp($absolute),
            default => false,
        };

        return $image === false ? null : $image;
    }

    /**
     * Scale + centre-crop a source image to the exact target size (the same
     * result as the CSS `object-fit: cover` the UI uses).
     */
    private function coverCrop(GdImage $image, int $width, int $height): GdImage
    {
        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);
        $targetRatio = $width / $height;

        if ($sourceWidth / $sourceHeight > $targetRatio) {
            $cropWidth = (int) round($sourceHeight * $targetRatio);
            $cropHeight = $sourceHeight;
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int) round($sourceWidth / $targetRatio);
        }

        $canvas = imagecreatetruecolor($width, $height);

        imagecopyresampled(
            $canvas,
            $image,
            0,
            0,
            (int) round(($sourceWidth - $cropWidth) / 2),
            (int) round(($sourceHeight - $cropHeight) / 2),
            $width,
            $height,
            $cropWidth,
            $cropHeight,
        );

        return $canvas;
    }

    private function initials(string $label): string
    {
        $words = preg_split('/\s+/', trim($label)) ?: [];
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= Str::upper(Str::substr($word, 0, 1));
        }

        return $initials !== '' ? $initials : 'SE';
    }
}

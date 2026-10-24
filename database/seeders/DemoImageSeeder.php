<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Slider;
use App\Services\ImageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Replaces the generated SVG art of the seeded demo records with the demo photos
 * in `public/images/**` (products 640×640, categories 560×420, brand logos
 * 360×200, hero slides 1600×600).
 *
 * When the optional source photos are present in `storage/app/seed-images`, they
 * are cropped + compressed on the fly; when they are not, the JPEGs that were
 * generated from them earlier are adopted as they are. Either way a record without
 * a demo photo keeps the generated SVG art.
 *
 * Only the image columns are written, so it is safe to run on a database that
 * already holds QA data: stock, prices, orders and customers stay untouched.
 */
class DemoImageSeeder extends Seeder
{
    /**
     * Output folder + target size per record type. The sizes match the generated
     * SVG art they replace, so the layout does not change.
     */
    private const TYPES = [
        'category' => ['folder' => 'images/categories', 'width' => 560, 'height' => 420],
        'brand' => ['folder' => 'images/brands', 'width' => 360, 'height' => 200],
        'product' => ['folder' => 'images/products', 'width' => 640, 'height' => 640],
        'slider' => ['folder' => 'images/sliders', 'width' => 1600, 'height' => 600],
    ];

    /**
     * Hand-picked hero photos: wide landscape shots without baked-in text, so the
     * title/subtitle/button overlay of the slider stays readable.
     */
    private const SLIDE_PHOTOS = [
        'Mega Season Sale' => '0005148.jpg',
        'New in Electronics' => 'sunset-5408739_1920.jpg',
        'Home Refresh Week' => '0004997.jpg',
    ];

    /**
     * Source photo indexes already handed out per record type, so no two demo
     * records of the same type end up with the same photo.
     *
     * @var array<string, array<int, bool>>
     */
    private array $usedSources = [];

    public function run(): void
    {
        $images = app(ImageService::class);

        if ($images->demoSources() === []) {
            $this->command?->warn(
                'No source photos in storage/app/'.ImageService::DEMO_SOURCE_DIR
                .' — the demo photos already in public/images are used as they are.',
            );
        }

        foreach (Category::orderBy('id')->get() as $category) {
            $this->swap($images, $category, 'image', 'category');
        }

        foreach (Brand::orderBy('id')->get() as $brand) {
            $this->swap($images, $brand, 'logo', 'brand');
        }

        foreach (Product::orderBy('id')->get() as $product) {
            // Products uploaded through the admin panel keep their own picture.
            if ($this->hasDemoImage($product, 'thumbnail')) {
                $this->swap($images, $product, 'thumbnail', 'product');
            }
        }

        $this->slides($images);
    }

    private function swap(ImageService $images, Model $record, string $column, string $type): void
    {
        ['folder' => $folder, 'width' => $width, 'height' => $height] = self::TYPES[$type];

        $path = "{$folder}/{$record->slug}.jpg";
        $source = $this->pick($images, $type, $record->slug);

        if ($source !== null) {
            $images->demoPhotoFrom($path, $source, $width, $height);
        }

        $this->store($images, $record, $column, $path, "{$folder}/{$record->slug}.svg");
    }

    /**
     * Pick the photo for a record: the hash of its key decides where in the sorted
     * list to start, then the first free slot is taken — deterministic, and no
     * photo is used twice within a type (as long as there are enough photos).
     */
    private function pick(ImageService $images, string $type, string $key): ?string
    {
        $sources = $images->demoSources();
        $count = count($sources);

        if ($count === 0) {
            return null;
        }

        if (count($this->usedSources[$type] ?? []) >= $count) {
            $this->usedSources[$type] = [];
        }

        $index = abs(crc32("{$type}:{$key}")) % $count;

        while (isset($this->usedSources[$type][$index])) {
            $index = ($index + 1) % $count;
        }

        $this->usedSources[$type][$index] = true;

        return $sources[$index];
    }

    private function slides(ImageService $images): void
    {
        ['folder' => $folder, 'width' => $width, 'height' => $height] = self::TYPES['slider'];

        foreach (Slider::orderBy('sort_order')->orderBy('id')->get() as $index => $slider) {
            $path = "{$folder}/".Str::slug($slider->title).'.jpg';
            $photo = self::SLIDE_PHOTOS[$slider->title] ?? null;

            if ($photo !== null) {
                $images->demoPhotoFrom($path, $photo, $width, $height);
            }

            $this->store($images, $slider, 'image', $path, "{$folder}/slide-".($index + 1).'.svg');
        }
    }

    /**
     * Point the record at the demo photo when that file exists on disk — generated
     * from a source photo just now, or left over from an earlier run — otherwise the
     * generated SVG art stays in place.
     *
     * @param  string  $path  relative path inside public/, e.g. images/products/x.jpg
     * @param  string|null  $replacedArt  the generated SVG this photo takes over
     */
    private function store(ImageService $images, Model $record, string $column, string $path, ?string $replacedArt = null): void
    {
        if (! is_file(public_path($path))) {
            return;
        }

        $previous = $record->getRawOriginal($column);

        $record->forceFill([$column => $path])->save();

        if (is_string($previous) && $previous !== $path) {
            $images->deleteGenerated($previous);
        }

        if ($replacedArt && $replacedArt !== $path) {
            $images->deleteGenerated($replacedArt);
        }
    }

    private function hasDemoImage(Model $record, string $column): bool
    {
        $value = $record->getRawOriginal($column);

        return is_string($value) && str_starts_with($value, 'images/');
    }
}

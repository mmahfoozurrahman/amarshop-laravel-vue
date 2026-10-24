<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Services\ImageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $images = app(ImageService::class);

        $categories = [
            ['name' => 'Electronics', 'palette' => 'cyan', 'description' => 'Laptops, audio gear and smart gadgets for everyday life.'],
            ['name' => 'Fashion', 'palette' => 'rose', 'description' => 'Everyday wear, footwear and accessories that fit your style.'],
            ['name' => 'Home & Living', 'palette' => 'amber', 'description' => 'Warm, minimal pieces that make a house feel like home.'],
            ['name' => 'Beauty & Care', 'palette' => 'violet', 'description' => 'Skincare and personal care essentials for a daily routine.'],
            ['name' => 'Sports & Outdoor', 'palette' => 'emerald', 'description' => 'Training, cycling and outdoor gear for an active lifestyle.'],
            ['name' => 'Books & Stationery', 'palette' => 'navy', 'description' => 'Bestsellers, journals and notebooks worth keeping.'],
        ];

        foreach ($categories as $category) {
            $slug = Str::slug($category['name']);
            $image = $images->placeholder("images/categories/{$slug}.svg", $category['name'], $category['palette'], 560, 420);

            Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $category['name'],
                    'image' => $image,
                    'description' => $category['description'],
                    'is_active' => true,
                ],
            );
        }
    }
}

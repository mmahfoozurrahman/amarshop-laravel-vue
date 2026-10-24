<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Services\ImageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $images = app(ImageService::class);

        $brands = [
            ['name' => 'TechNova', 'palette' => 'cyan', 'description' => 'Computing and smart devices built for work and play.'],
            ['name' => 'SoundWave', 'palette' => 'navy', 'description' => 'Audio products tuned for rich, room-filling sound.'],
            ['name' => 'UrbanThread', 'palette' => 'rose', 'description' => 'Contemporary fashion with everyday comfort in mind.'],
            ['name' => 'CasaLiving', 'palette' => 'amber', 'description' => 'Simple, functional homeware for modern living.'],
            ['name' => 'GlowLab', 'palette' => 'violet', 'description' => 'Dermatologist-inspired skincare and beauty essentials.'],
            ['name' => 'FitPulse', 'palette' => 'emerald', 'description' => 'Training equipment for home workouts and the gym.'],
            ['name' => 'PageCraft', 'palette' => 'slate', 'description' => 'Books, journals and stationery for curious minds.'],
            ['name' => 'TrailGear', 'palette' => 'teal', 'description' => 'Outdoor-ready gear for runners and cyclists.'],
        ];

        foreach ($brands as $brand) {
            $slug = Str::slug($brand['name']);
            $logo = $images->placeholder("images/brands/{$slug}.svg", $brand['name'], $brand['palette'], 360, 200);

            Brand::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $brand['name'],
                    'logo' => $logo,
                    'description' => $brand['description'],
                    'is_active' => true,
                ],
            );
        }
    }
}

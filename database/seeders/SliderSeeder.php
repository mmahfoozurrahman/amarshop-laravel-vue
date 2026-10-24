<?php

namespace Database\Seeders;

use App\Models\Slider;
use App\Services\ImageService;
use Illuminate\Database\Seeder;

class SliderSeeder extends Seeder
{
    public function run(): void
    {
        $images = app(ImageService::class);

        $sliders = [
            [
                'title' => 'Mega Season Sale',
                'subtitle' => 'Save on hand-picked essentials across every category.',
                'button_text' => 'Shop the sale',
                'link_url' => '/products',
                'palette' => 'teal',
            ],
            [
                'title' => 'New in Electronics',
                'subtitle' => 'Laptops, audio and smart gear, freshly restocked.',
                'button_text' => 'Explore electronics',
                'link_url' => '/categories/electronics',
                'palette' => 'navy',
            ],
            [
                'title' => 'Home Refresh Week',
                'subtitle' => 'Simple pieces that make your space feel like home.',
                'button_text' => 'Browse home & living',
                'link_url' => '/categories/home-living',
                'palette' => 'amber',
            ],
        ];

        foreach ($sliders as $index => $slider) {
            $image = $images->hero(
                'images/sliders/slide-'.($index + 1).'.svg',
                $slider['palette'],
            );

            Slider::updateOrCreate(
                ['title' => $slider['title']],
                [
                    'subtitle' => $slider['subtitle'],
                    'image' => $image,
                    'button_text' => $slider['button_text'],
                    'link_url' => $slider['link_url'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}

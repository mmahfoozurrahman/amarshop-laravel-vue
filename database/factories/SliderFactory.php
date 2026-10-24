<?php

namespace Database\Factories;

use App\Models\Slider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slider>
 */
class SliderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'subtitle' => fake()->sentence(10),
            'image' => null,
            'button_text' => 'Shop now',
            'link_url' => '/products',
            'sort_order' => fake()->numberBetween(1, 5),
            'is_active' => true,
        ];
    }
}

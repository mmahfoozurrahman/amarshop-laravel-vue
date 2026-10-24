<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ImageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $images = app(ImageService::class);
        $categories = Category::pluck('id', 'slug');
        $brands = Brand::pluck('id', 'slug');

        $palettes = [
            'electronics' => 'cyan',
            'fashion' => 'rose',
            'home-living' => 'amber',
            'beauty-care' => 'violet',
            'sports-outdoor' => 'emerald',
            'books-stationery' => 'navy',
        ];

        $position = 0;

        foreach ($this->catalogue() as $item) {
            $position++;
            $slug = Str::slug($item['name']);
            $palette = $palettes[$item['category']] ?? 'teal';
            $thumbnail = $images->placeholder("images/products/{$slug}.svg", $item['name'], $palette);

            $price = (float) $item['price'];
            $salePrice = $position % 3 === 0 ? round($price * 0.85, 2) : null;

            Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'brand_id' => $brands[$item['brand']] ?? null,
                    'category_id' => $categories[$item['category']] ?? null,
                    'name' => $item['name'],
                    'sku' => 'SE-'.Str::upper(Str::substr($item['category'], 0, 3)).'-'.str_pad((string) $position, 3, '0', STR_PAD_LEFT),
                    'thumbnail' => $thumbnail,
                    'price' => $price,
                    'sale_price' => $salePrice,
                    'stock' => $item['stock'],
                    'short_description' => $item['short'],
                    'description' => $this->describe($item),
                    'is_featured' => $item['featured'] ?? false,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalogue(): array
    {
        return [
            ...$this->electronics(),
            ...$this->fashion(),
            ...$this->homeAndLiving(),
            ...$this->beauty(),
            ...$this->sports(),
            ...$this->books(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function electronics(): array
    {
        return [
            ['category' => 'electronics', 'brand' => 'technova', 'name' => 'NovaBook Air 14 Inch Laptop', 'price' => 78500, 'stock' => 12, 'featured' => true, 'short' => '13th-gen processor, 16GB RAM, 512GB SSD and a 14-inch IPS display.'],
            ['category' => 'electronics', 'brand' => 'soundwave', 'name' => 'PulseBuds Pro Wireless Earbuds', 'price' => 5250, 'stock' => 40, 'featured' => true, 'short' => 'Active noise cancellation with 30-hour battery life.'],
            ['category' => 'electronics', 'brand' => 'soundwave', 'name' => 'SoundWave Party 2.1 Speaker', 'price' => 4300, 'stock' => 25, 'short' => 'Room-filling 60W sound with deep bass and Bluetooth 5.3.'],
            ['category' => 'electronics', 'brand' => 'technova', 'name' => 'NovaView 27 Inch 4K Monitor', 'price' => 36900, 'stock' => 9, 'featured' => true, 'short' => 'Colour-accurate 4K IPS panel with USB-C power delivery.'],
            ['category' => 'electronics', 'brand' => 'technova', 'name' => 'GaN 65W Fast Charger', 'price' => 2450, 'stock' => 60, 'short' => 'Compact three-port charger for laptop, tablet and phone.'],
            ['category' => 'electronics', 'brand' => 'technova', 'name' => 'NovaCam Full HD Webcam', 'price' => 3200, 'stock' => 30, 'short' => '1080p streaming camera with dual noise-cancelling mics.'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fashion(): array
    {
        return [
            ['category' => 'fashion', 'brand' => 'urbanthread', 'name' => 'Urban Slim Fit Denim Jacket', 'price' => 3850, 'stock' => 22, 'featured' => true, 'short' => 'Mid-wash stretch denim with a clean, modern cut.'],
            ['category' => 'fashion', 'brand' => 'urbanthread', 'name' => 'Classic Cotton Panjabi', 'price' => 2950, 'stock' => 30, 'short' => 'Breathable cotton panjabi with subtle embroidery.'],
            ['category' => 'fashion', 'brand' => 'urbanthread', 'name' => 'Everyday Canvas Sneakers', 'price' => 2450, 'stock' => 45, 'featured' => true, 'short' => 'Cushioned insole and durable canvas upper for daily wear.'],
            ['category' => 'fashion', 'brand' => 'urbanthread', 'name' => 'Linen Blend Casual Shirt', 'price' => 1850, 'stock' => 35, 'short' => 'Lightweight linen blend that keeps you cool all day.'],
            ['category' => 'fashion', 'brand' => 'urbanthread', 'name' => 'Full Grain Leather Belt', 'price' => 1150, 'stock' => 50, 'short' => 'Hand-finished leather belt with a brushed metal buckle.'],
            ['category' => 'fashion', 'brand' => 'urbanthread', 'name' => 'All Weather Windbreaker', 'price' => 4250, 'stock' => 18, 'short' => 'Water-resistant shell with packable hood and taped seams.'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function homeAndLiving(): array
    {
        return [
            ['category' => 'home-living', 'brand' => 'casaliving', 'name' => 'Ceramic Aroma Diffuser', 'price' => 1650, 'stock' => 26, 'short' => 'Ultrasonic diffuser with warm ambient light and timer.'],
            ['category' => 'home-living', 'brand' => 'casaliving', 'name' => 'Nordic Table Lamp', 'price' => 2450, 'stock' => 20, 'featured' => true, 'short' => 'Minimal oak base lamp with a soft linen shade.'],
            ['category' => 'home-living', 'brand' => 'casaliving', 'name' => 'Cotton Blend Bedsheet Set', 'price' => 3250, 'stock' => 24, 'short' => 'King-size sheet set with two pillow covers.'],
            ['category' => 'home-living', 'brand' => 'casaliving', 'name' => 'Bamboo Storage Basket', 'price' => 950, 'stock' => 40, 'short' => 'Handwoven basket for blankets, toys or laundry.'],
            ['category' => 'home-living', 'brand' => 'casaliving', 'name' => 'Non Stick Cookware Set', 'price' => 5450, 'stock' => 15, 'featured' => true, 'short' => 'Five-piece induction friendly set with glass lids.'],
            ['category' => 'home-living', 'brand' => 'casaliving', 'name' => 'Minimal Wall Clock', 'price' => 1350, 'stock' => 32, 'short' => 'Silent sweep movement with a matte finish dial.'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function beauty(): array
    {
        return [
            ['category' => 'beauty-care', 'brand' => 'glowlab', 'name' => 'Vitamin C Glow Serum', 'price' => 1250, 'stock' => 55, 'featured' => true, 'short' => '10% vitamin C serum that brightens and evens skin tone.'],
            ['category' => 'beauty-care', 'brand' => 'glowlab', 'name' => 'Hydra Boost Moisturizer', 'price' => 950, 'stock' => 50, 'short' => 'Lightweight gel cream with hyaluronic acid and ceramides.'],
            ['category' => 'beauty-care', 'brand' => 'glowlab', 'name' => 'Matte Lipstick Trio', 'price' => 1450, 'stock' => 38, 'short' => 'Three long-wear shades in a travel friendly box.'],
            ['category' => 'beauty-care', 'brand' => 'glowlab', 'name' => 'Aloe Soothing Gel', 'price' => 550, 'stock' => 70, 'short' => 'Multi-purpose soothing gel for face, hands and body.'],
            ['category' => 'beauty-care', 'brand' => 'glowlab', 'name' => 'Charcoal Detox Face Wash', 'price' => 650, 'stock' => 65, 'short' => 'Deep cleansing daily face wash with activated charcoal.'],
            ['category' => 'beauty-care', 'brand' => 'glowlab', 'name' => 'Rose Water Toner', 'price' => 450, 'stock' => 60, 'short' => 'Alcohol free toner that refreshes and balances skin.'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sports(): array
    {
        return [
            ['category' => 'sports-outdoor', 'brand' => 'fitpulse', 'name' => 'FitPulse Yoga Mat', 'price' => 1850, 'stock' => 28, 'short' => '6mm anti-slip mat with carrying strap.'],
            ['category' => 'sports-outdoor', 'brand' => 'fitpulse', 'name' => 'Adjustable Dumbbell 20kg', 'price' => 5450, 'stock' => 14, 'featured' => true, 'short' => 'Quick-change plates from 2.5kg to 20kg per hand.'],
            ['category' => 'sports-outdoor', 'brand' => 'trailgear', 'name' => 'TrailGear Running Shoes', 'price' => 4250, 'stock' => 22, 'featured' => true, 'short' => 'Responsive foam midsole with a breathable knit upper.'],
            ['category' => 'sports-outdoor', 'brand' => 'trailgear', 'name' => 'Insulated Water Bottle 1L', 'price' => 950, 'stock' => 48, 'short' => 'Double-wall steel bottle, keeps drinks cold for 24 hours.'],
            ['category' => 'sports-outdoor', 'brand' => 'fitpulse', 'name' => 'Resistance Band Set', 'price' => 1250, 'stock' => 36, 'short' => 'Five resistance levels with door anchor and handles.'],
            ['category' => 'sports-outdoor', 'brand' => 'trailgear', 'name' => 'TrailGear Cycling Helmet', 'price' => 3250, 'stock' => 16, 'short' => 'Vented shell with adjustable fit dial and rear light.'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function books(): array
    {
        return [
            ['category' => 'books-stationery', 'brand' => 'pagecraft', 'name' => 'The Silent Patient Paperback', 'price' => 650, 'stock' => 40, 'featured' => true, 'short' => 'A psychological thriller that keeps you guessing.'],
            ['category' => 'books-stationery', 'brand' => 'pagecraft', 'name' => 'Atomic Habits Hardcover', 'price' => 1150, 'stock' => 34, 'featured' => true, 'short' => 'Practical guide to building better habits, one day at a time.'],
            ['category' => 'books-stationery', 'brand' => 'pagecraft', 'name' => 'Bengali Poetry Collection', 'price' => 450, 'stock' => 50, 'short' => 'A curated anthology of classic and modern Bengali poems.'],
            ['category' => 'books-stationery', 'brand' => 'pagecraft', 'name' => 'The Pragmatic Programmer', 'price' => 2250, 'stock' => 18, 'short' => 'Timeless advice for software craftsmanship and clean code.'],
            ['category' => 'books-stationery', 'brand' => 'pagecraft', 'name' => 'Children Story Treasury', 'price' => 850, 'stock' => 30, 'short' => 'Illustrated bedtime stories for young readers.'],
            ['category' => 'books-stationery', 'brand' => 'pagecraft', 'name' => 'Blank Leather Journal A5', 'price' => 750, 'stock' => 44, 'featured' => true, 'short' => 'Refillable leather journal with 180 pages of thick paper.'],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function describe(array $item): string
    {
        return "{$item['name']} is part of the AmarShop curated collection, chosen for reliable everyday "
            ."performance at a fair price.\n\n{$item['short']} Every unit is quality checked before dispatch and "
            ."covered by our 7-day easy return policy.\n\nHighlights: authentic product, secure packaging, and "
            .'cash-on-delivery available inside Dhaka. Ships within 1-3 business days across Bangladesh.';
    }
}

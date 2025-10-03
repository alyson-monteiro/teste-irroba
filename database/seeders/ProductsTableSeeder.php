<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Product::truncate();
        Schema::enableForeignKeyConstraints();

        $products = [
            [
                'sku' => 'SKU-STARTER-001',
                'name' => 'Starter Protein Powder',
                'price' => 29.99,
                'currency' => 'USD',
                'stock' => 150,
                'description' => 'High quality whey protein powder for daily nutrition, sourced from grass-fed dairy.',
                'images' => [
                    'https://cdn.example.com/products/starter-protein-front.jpg',
                    'https://cdn.example.com/products/starter-protein-back.jpg',
                ],
                'tags' => ['nutrition', 'protein', 'fitness'],
            ],
            [
                'sku' => 'SKU-HOME-COFFEE-002',
                'name' => 'Home Barista Coffee Grinder',
                'price' => 89.95,
                'currency' => 'USD',
                'stock' => 65,
                'description' => 'Burr grinder with precision settings for espresso to french press brews.',
                'images' => [
                    'https://cdn.example.com/products/coffee-grinder-main.jpg',
                    'https://cdn.example.com/products/coffee-grinder-detail.jpg',
                ],
                'tags' => ['coffee', 'kitchen', 'appliance'],
            ],
            [
                'sku' => 'SKU-OFFICE-CHAIR-003',
                'name' => 'Ergonomic Mesh Office Chair',
                'price' => 219.00,
                'currency' => 'USD',
                'stock' => 80,
                'description' => 'Breathable mesh back, adjustable lumbar support, and 4D armrests for ultimate comfort.',
                'images' => [
                    'https://cdn.example.com/products/mesh-chair-front.jpg',
                    'https://cdn.example.com/products/mesh-chair-side.jpg',
                ],
                'tags' => ['office', 'chair', 'ergonomic'],
            ],
            [
                'sku' => 'SKU-KITCHEN-SKILLET-004',
                'name' => 'Seasoned Cast Iron Skillet 12in',
                'price' => 49.50,
                'currency' => 'USD',
                'stock' => 220,
                'description' => 'Pre-seasoned cast iron skillet suitable for stove, oven, or campfire cooking.',
                'images' => [
                    'https://cdn.example.com/products/cast-iron-skillet-top.jpg',
                ],
                'tags' => ['kitchen', 'cookware', 'cast-iron'],
            ],
            [
                'sku' => 'SKU-TRAVEL-BLANKET-005',
                'name' => 'Compact Travel Blanket',
                'price' => 34.95,
                'currency' => 'USD',
                'stock' => 310,
                'description' => 'Ultra-soft and lightweight travel blanket with carrying pouch for flights and road trips.',
                'images' => [
                    'https://cdn.example.com/products/travel-blanket-blue.jpg',
                ],
                'tags' => ['travel', 'comfort', 'blanket'],
            ],
            [
                'sku' => 'SKU-GARDEN-TOOLS-006',
                'name' => 'Garden Hand Tool Set',
                'price' => 59.99,
                'currency' => 'USD',
                'stock' => 120,
                'description' => 'Five-piece stainless steel garden tool set with ergonomic handles and storage bag.',
                'images' => [
                    'https://cdn.example.com/products/garden-tool-set.jpg',
                ],
                'tags' => ['garden', 'tools', 'outdoor'],
            ],
            [
                'sku' => 'SKU-KIDS-BLOCKS-007',
                'name' => 'Creative Builder Blocks 150pc',
                'price' => 42.00,
                'currency' => 'USD',
                'stock' => 95,
                'description' => 'Colorful building blocks set to inspire creativity in children ages 3 and up.',
                'images' => [
                    'https://cdn.example.com/products/builder-blocks-main.jpg',
                ],
                'tags' => ['kids', 'education', 'toys'],
            ],
            [
                'sku' => 'SKU-SMARTWATCH-008',
                'name' => 'Fitness Smartwatch Pro',
                'price' => 199.99,
                'currency' => 'USD',
                'stock' => 140,
                'description' => 'Advanced smartwatch with heart-rate monitoring, GPS, and 7-day battery life.',
                'images' => [
                    'https://cdn.example.com/products/smartwatch-pro-black.jpg',
                ],
                'tags' => ['wearable', 'fitness', 'electronics'],
            ],
            [
                'sku' => 'SKU-AUDIO-EARBUDS-009',
                'name' => 'Noise Cancelling Wireless Earbuds',
                'price' => 149.99,
                'currency' => 'USD',
                'stock' => 185,
                'description' => 'True wireless earbuds with active noise cancellation and wireless charging case.',
                'images' => [
                    'https://cdn.example.com/products/wireless-earbuds-case.jpg',
                ],
                'tags' => ['audio', 'wireless', 'electronics'],
            ],
            [
                'sku' => 'SKU-OUTDOOR-TENT-010',
                'name' => 'Four-Person Backpacking Tent',
                'price' => 259.00,
                'currency' => 'USD',
                'stock' => 70,
                'description' => 'Lightweight waterproof tent with easy setup and durable aluminum poles.',
                'images' => [
                    'https://cdn.example.com/products/backpacking-tent.jpg',
                ],
                'tags' => ['outdoor', 'camping', 'gear'],
            ],
        ];

        foreach ($products as $product) {
            Product::create([
                'sku' => $product['sku'],
                'name' => $product['name'],
                'slug' => Str::slug($product['name']).'-featured',
                'price' => $product['price'],
                'currency' => $product['currency'],
                'stock' => $product['stock'],
                'description' => $product['description'],
                'images' => $product['images'],
                'tags' => $product['tags'],
                'is_active' => true,
            ]);
        }
    }
}

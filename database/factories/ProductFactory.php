<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(rand(2, 4), true);
        $images = collect(range(1, rand(2, 4)))->map(function ($index) use ($name) {
            return fake()->imageUrl(800, 800, $name, true, "image-{$index}");
        })->all();

        $tags = collect(fake()->unique()->words(rand(3, 6)))->map(fn ($word) => Str::slug($word))->all();

        return [
            'sku' => strtoupper('SKU-'.fake()->unique()->bothify('??##??##')),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'stock' => fake()->numberBetween(0, 500),
            'price' => fake()->randomFloat(2, 5, 499),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP', 'BRL']),
            'description' => fake()->paragraphs(rand(2, 4), true),
            'images' => $images,
            'tags' => $tags,
            'is_active' => fake()->boolean(90),
        ];
    }
}

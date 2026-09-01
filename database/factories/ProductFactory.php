<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 5, 500),
            'compare_price' => fake()->boolean(30) ? fake()->randomFloat(2, 10, 650) : null,
            'stock' => fake()->numberBetween(0, 200),
            'category_id' => Category::factory(),
            'images' => [fake()->imageUrl(640, 480, 'product', true), fake()->imageUrl(640, 480, 'product', true)],
            'status' => fake()->randomElement(['active', 'active', 'active', 'inactive', 'out_of_stock']),
            'is_featured' => fake()->boolean(20),
            'weight' => fake()->randomFloat(2, 0.1, 25),
        ];
    }
}
